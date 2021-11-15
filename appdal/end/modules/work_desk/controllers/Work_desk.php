<?php


class Work_desk extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('work_desk/Work_desk_model');
    }

    /**
     * 各个模块数据刷新接口
     * /Work_desk/refresh_data
     */
    public function refresh_data()
    {
        try {
            $category_id = $this->input->get_post('category_id');
            $res = explode('_', $category_id);
            if (!empty($res) && 2 == count($res)) {
                $category_id = $res[1];//小组类型（1-非海外仓组，2-海外仓组）
            } else {
                $this->error_json('参数category_id格式错误');
            }
            $module_arr = $this->input->get_post('module');//模块标识（数组）
            if (!in_array($category_id, [1, 2])) {
                $this->error_json('参数错误');
            }
            if (empty($module_arr) OR !is_array($module_arr)) {
                $this->error_json('参数错误');
            }
            //方法前缀(non_oversea_-非海外仓，oversea_-海外仓)
            $prefix = (1 == $category_id) ? 'non_oversea_' : 'oversea_';
            foreach ($module_arr as $module ){
                if (!method_exists($this->Work_desk_model, $method_name = $prefix . $module)) {
                    $this->error_json('方法[' . $method_name . ']不存在');
                }
                //限制刷新频率
                $this->_limit_refresh($prefix . $module);

                //设置需要权限控制
                $this->Work_desk_model->set_need_permission(TRUE);

                //设置用户权限
                $this->Work_desk_model->init_authorization();

                //删除模块数据（删除10分钟之前的数据）
                $result = $this->Work_desk_model->delete_data($category_id,$module);
                if (!$result['flag']) {
                    throw new Exception($result['msg']);
                }

                //调用汇总模块数据方法
                $result = call_user_func_array([$this->Work_desk_model, $method_name], []);

                if ($result['flag']) {
                    //汇总数据成功，缓存操作时间
                    $this->rediss->setData(strtoupper($prefix . $module) . '_' . getActiveUserId(), time());
                } else {
                    throw new Exception($result['msg']);
                }
            }
            $this->success_json([], NULL, '刷新成功');
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 统计SKU 屏蔽到期数量
     * @params $uid  int  用户ID
     * @author:luxu
     * @time:2020/10/23
     **/
    public function SumConfirmData($categoryid,$groupid,$buyer_id){

        try{

            $result = $this->Work_desk_model->GetConfirmUser($categoryid,$groupid,$buyer_id);
            $count = $this->Work_desk_model->getConfirmData($result);
            return $count;

        }catch ( Exception $exp ){
            echo $exp->getMessage();

        }
    }

    /**
     * 工作台-数据展示接口
     * /Work_desk/get_data_list
     */
    public function get_data_list()
    {
        $params = [
            'category_id' => $this->input->get_post('category_id'),
            'group_id' => $this->input->get_post('group_id'),
            'buyer_id' => $this->input->get_post('buyer_id'),
            'module' => $this->input->get_post('module'),
        ];

        if (empty($params['category_id'])) {
            $this->error_json('小组类型不能为空');
        }
        if (!empty($params['buyer_id']) && !is_array($params['buyer_id'])) {
            $this->error_json('参数buyer_id格式错误');
        }
        if (!empty($params['module']) && !is_array($params['module'])) {
            $this->error_json('参数module格式错误');
        }

        $res = explode('_', $params['category_id']);
        if (!empty($res) && 2 == count($res)) {
            $params['category_id'] = $res[1];
        } else {
            $this->error_json('参数category_id格式错误');
        }
        $params['buyer_id'] = !empty($params['buyer_id']) && is_array($params['buyer_id']) ? array_filter($params['buyer_id']) :[];
        $params['module'] = !empty($params['module']) && is_array($params['module']) ? array_filter($params['module']) :[];
        $data = $this->Work_desk_model->get_data_list($params);

        if( isset($data['other_pending']) && !empty($data['other_pending'])){
            $confirmCount = $this->SumConfirmData($params['category_id'],$params['group_id'],$params['buyer_id']);
            $data['other_pending']['items']['to_be_confirmed']['label'] = 'sku屏蔽到期待确认';
            $data['other_pending']['items']['to_be_confirmed']['count_num'] = $confirmCount;
            $data['other_pending']['items']['to_be_confirmed']['url_info'] = [

                'method' => 'get',
                'params' => [['status'=>'50','estima_status'=>'4','estimate_time_start'=>date("Y-m-d H:i:s"),'estimate_time_end'=>date("Y-m-d H:i:s",strtotime("+3 day")),'limit'=>'20']],
                'url' =>['api/abnormal/product_scree/get_scree_list']
            ];
        }

        if( !isset($data['role_flag']) ){

            $roleDatas = $this->Work_desk_model->getRoleData();
            if( True ==  $roleDatas){

                $data['role'] = 1;
            }else{
                $data['role'] = 2;
            }
        }



        if (!empty($data)) {
            $this->success_json($data);
        } else {
            $this->error_json('没有工作台数据权限');
        }
    }

    /**
     * 获取小组类型
     * /Work_desk/get_category_list
     */
    public function get_category_list()
    {
        $this->load->model('user/User_group_model', 'User_group_model');
        $data = $this->User_group_model->get_category_dropdown_list();
        $this->success_json($data);
    }

    /**
     * 根据小组类型获取小组
     * /Work_desk/get_group_by_category
     */
    public function get_group_by_category()
    {
        $category_id = $this->input->get_post('category_id');//小组类型（1-非海外仓组，2-海外仓组）
        if (empty($category_id)) {
            $this->error_json('参数category_id不能为空');
        }
        $res = explode('_', $category_id);
        if (!empty($res) && 2 == count($res)) {
            //$group_type：角色类型（4-属于权限中心‘admin’角色，3-属于‘超级数据组’角色，2-属于’非海外仓组‘或’海外仓组‘角色）
            //$category_id:小组类型（1-非海外仓组，2-海外仓组）
            list($group_type, $category_id) = $res;
        } else {
            $this->error_json('参数category_id格式错误');
        }
        $this->load->model('user/User_group_model', 'User_group_model');

        $data = $this->User_group_model->get_group_dropdown_list($group_type, $category_id);
        $this->success_json($data);
    }

    /**
     * 根据小组id获取采购员
     * /Work_desk/get_buyer_by_group
     */
    public function get_buyer_by_group()
    {
        $group_id = $this->input->get_post('group_id');//小组id
        if (empty($group_id)) {
            $this->error_json('参数group_id不能为空');
        }
        $this->load->model('user/User_group_model', 'User_group_model');

        $data = $this->User_group_model->get_buyer_by_group($group_id);
        $this->success_json($data);
    }

    /**
     * 限制刷新频率
     * @param string $module 模块
     * @throws Exception
     */
    private function _limit_refresh($module)
    {
        //判断操作时间是否太过于频繁
        $opt_time = $this->rediss->getData(strtoupper($module) . '_' . getActiveUserId());
        //最近5分钟内不允许再次操作，防止频繁刷新
        if (bcsub(time(), (int)$opt_time) <= 600) {
            throw new Exception('刷新过于频繁，请稍后再试（间隔为10分钟）');
        }
    }

}