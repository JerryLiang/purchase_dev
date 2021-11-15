<?php

/**
 * 生成1688订单和拍单
 * User: 叶凡立
 * Date: 2020/11/03
 */
class Ali_order_advanced_new extends MY_Controller
{
    /**
     * init
     */
    public function __construct()
    {
//        parent::__construct();
        $this->load->model('ali/Ali_order_advanced_new_model', 'advanced_new_model');
        $this->load->model('ali/Ali_order_advanced_model', 'advanced_model');
        $this->load->library('rediss');
    }

    /**
     * 获取备货单查询条件
     * @return array
     */
    private function get_suggest_params()
    {
        // 统一维护，请勿在此额外添加
        $this->load->model('purchase_suggest/demand_public_model');
        $param = $this->demand_public_model->get_search_params();
        $res = [];
        foreach ($param as $val){
            $res[$val] = $this->input->get_post($val);
        }
        if($res['list_type'] == 2 && empty($res['suggest_status'])){
            $res['suggest_status'] = [SUGGEST_STATUS_NOT_FINISH, SUGGEST_STATUS_REBORN];
        }

//        $res['demand_type_id'] = PURCHASE_DEMAND_TYPE_PLAN;// 需求类型(计划单)
        return $res;
    }

    /**
     * 根据用户组，获取对应的用户id
     */
    private function get_user_by_group($group)
    {
        $this->load->model('user/User_group_model', 'User_group_model');
        $groupids = $this->User_group_model->getGroupPersonData($group);
        $groupdatas = [];
        if(!empty($groupids)){
            $groupdatas = array_column($groupids,'value');
        }
        return $groupdatas;
    }

    /**
     * 1688一键下单（自动建单、自动拍单、自动下单确认、自动审核、自动请款）
     */
    public function advanced_one_key_create_order()
    {
        $suggest_ids        = $this->input->get_post('ids');    // 备货单ID
        $action             = $this->input->get_post('action');    // 操作类型：11688一键下单，2.1688一键拍单
        $ids = [];
        if($suggest_ids && !empty($suggest_ids)){
            $ids = is_array($suggest_ids)?$suggest_ids:explode(',',$suggest_ids);
        }else{
            $params = $this->get_suggest_params();
            $params['list_type']                = 2;
            $params['id']                       = $this->input->get_post('id'); //勾选数据
//            $params['demand_type_id']           = PURCHASE_DEMAND_TYPE_PLAN; // 需求类型(计划单)
//            $params['suggest_status']           = SUGGEST_STATUS_NOT_FINISH; // 需求类型)
//            $params['is_create_order']          = SUGGEST_ORDER_STATUS_N; // 是否生成采购单
            $page                               = $this->input->get_post('offset');
            $limit                              = $this->input->get_post('limit');
            if(empty($page) || $page < 0)$page  = 1;
            $limit                              = query_limit_range($limit);
            $params['page']                     = $page;
            $params['limit']                    = query_limit_range($limit);
            $params['offset']                   = ($page - 1) * $limit;
            if(isset($params['group_ids']) && !empty($params['group_ids'])){
                $params['groupdatas'] = $this->get_user_by_group($params['group_ids']);
            }

            $this->load->model('purchase_suggest/suggest_lock_model');
            $is_lock_res = $this->suggest_lock_model->validate_is_lock_time();
            if ($is_lock_res['code'] == 500) $this->error_json($is_lock_res['message']);
            if ($is_lock_res['code'] == 200 && !empty($is_lock_res['message'])) $this->error_json('锁单时间内,不能生成采购单');

            // 获取采购建议列表
            $params['exclude_scree'] = true;
//            $this->load->model('purchase_suggest/purchase_suggest_model');
//            $suggest_list = $this->purchase_suggest_model->get_list($params,null,null);

            $this->load->model('purchase_suggest/purchase_demand_model');
            $suggest_list = $this->purchase_demand_model->get_demanding_list($params, true);
            if(!isset($suggest_list['data_list']) || empty($suggest_list['data_list'])){
                $this->error_json('没有符合条件的需求单数据');
            }
            foreach ($suggest_list['data_list'] as $val){
                $ids[] = $val['id'];
            }
        }

        $handle_action = "";
        switch ($action){
            case 1:
                $handle_action = '1688一键下单';
                break;
            case 2:
                $handle_action = '1688一键拍单';
                break;
            case 3:
                $handle_action = '一键生成采购单';
                break;
            default:
                $handle_action = '1688一键下单';
        }

        if(count($ids) == 0)$this->error_json("没有获取到可以【{$handle_action}】的数据，请确认操作。");
        // 投递任务
        $date = date("Y-m-d H:i:s");
        $uid = getActiveUserId();
        $user = getActiveUserName();
        $handle_query = [
            "list"  => $ids,
            "uid"   => $uid,
            "user"  => $user,
            "action"=> $action,
        ];
        $create_tsak = [
            "user_id"       => $uid,
            "user_name"     => $user,
            "handle_status" => 0,
            "handle_action" => $handle_action,
            "handle_msg"    => "等待处理",
            "handle_all"    => count($ids),
            "handle_query"  => json_encode($handle_query),
            "success_num"   => 0,
            "error_num"     => 0,
            "create_at"     => $date,
        ];
        $id = $this->advanced_new_model->callback_create_error($create_tsak, [], "create");

        $log = [
            "action_name"   => "advanced_one_key_create_order",
            "create_at"     => $this->get_microtime(),
        ];
        try{
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9509, 0.5)) {
                $data = [
                    "id"    => $id,
                    "list"  => $ids,
                    "uid"   => $uid,
                    "user"  => $user,
                    "action"=> $action,
                ];
                $data = json_encode($data);
                $client->send($data);
                $log['request_data'] = $data;
                $client->recv();
                $client->close();
            }
        }catch (Exception $e){
            $log['context_err'] = $e->getMessage();
        }

        $this->success_json([], '', "已提交处理，请点击“消息” > “数据处理“查看进度。");
    }

    /**
     * 1688一键拍单
     */
    public function one_key_create_order()
    {
        $this->advanced_one_key_create_order();
    }

    /**
     * 一键生成采购单
     */
    public function one_key_create_purchase()
    {
        $this->advanced_one_key_create_order();
    }

    /**
     * 获取处理结果列表
     */
    public function get_handle_create_list()
    {
        $params = [
            "id"                => $this->input->get_post('id'),
            "user_id"           => $this->input->get_post('user_id'),
            "handle_status"     => $this->input->get_post('handle_status'),
            "handle_action"     => $this->input->get_post('handle_action'),
            "create_at_start"   => $this->input->get_post('create_at_start'),
            "create_at_end"     => $this->input->get_post('create_at_end'),
            "page"              => $this->input->get_post('page'),
            "limit"             => $this->input->get_post('limit'),
        ];
        $data = $this->advanced_new_model->get_handle_create_list($params);

        $res = [
            "aggregate_data" => $this->advanced_new_model->get_handle_select_data(),
        ];
        $page_data = [
            "total_all" => 0,
            "page" => $params['page'],
            "limit" => $params['limit']
        ];
        if(isset($data['code']) && $data['code'] == 1 && is_array($data['msg'])){
            $res = array_merge($res, $data['msg']);
            $this->success_json([
                "aggregate_data" => $res["aggregate_data"],
                "data_list" => $res["data_list"]
            ], $res["page_data"], '获取成功');
        }
        if(isset($data['msg']) && is_string($data['msg']))$this->success_json($res, $page_data, $data['msg']);

        $this->error_json('获取数据失败');
    }

    /**
     * 获取处理结果下的采购单列表
     */
    public function get_handle_create_order_list()
    {
        $id     = $this->input->get_post('id');    // 处理ID
        $page   = $this->input->get_post('page');
        $limit  = $this->input->get_post('limit');
        $res = [
            'data_list' => [],
        ];
        $page_data = [
            'total_all' => 0,
            "page"      => !empty($page)?(int)$page:1,
            "limit"     => !empty($limit)?(int)$limit:20,
        ];
        $data = $this->advanced_new_model->get_handle_create_order_list($id, $page, $limit);
        if(isset($data['code']) && $data['code'] == 1 && isset($data['msg']['value'])){
            $res['data_list'] = $data['msg']['value'];
            $page_data = [
                'total_all' => isset($data['msg']['total_all'])?$data['msg']['total_all']:0,
                "page"      => !empty($page)?(int)$page:1,
                "limit"     => !empty($limit)?(int)$limit:20,
            ];
            $this->success_json($res, $page_data, '获取成功');
        }
        if(isset($data['msg']))$this->success_json($res, $page_data, $data['msg']);
        $this->error_json('获取数据失败');
    }

}
