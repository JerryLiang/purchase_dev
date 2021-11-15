<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/11/29
 * Time: 14:43
 */

class Logistics_state_config extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Logistics_state_config_model');
    }

    /**
     * 获取物流承运商列表数据
     * /system/logistics_state_config/get_data_list?uid=1528&code=a2u
     */
    public function get_data_list()
    {
        $params = [
            'name' => $this->input->get_post('name'), // 承运商名
            'code' => $this->input->get_post('code'), // 承运商编码
        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) OR $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data = $this->Logistics_state_config_model->get_list_data($params, $offsets, $limit, $page);
        $this->success_json($data);
    }

    /**
     * 批量编辑物流轨迹状态匹配规则
     * /system/logistics_state_config/rule_batch_edit?uid=1528&data_initial={"rule":{"collect":"已揽件","shipped":"已发货","pick_up_point":{"address":"省,市,区,镇","relation":"AND","keyword":"关键字1,关键字2"},"deliver":"派件中","received":"已签收"},"status":"1","ids":"10001,10002"}
     * @author Justin
     * @date 2019/11/30
     */
    public function rule_batch_edit()
    {
        $data = $this->input->post_get('data_initial'); //数组格式
        if (empty($data) OR !is_json($data)) $this->error_json('请求参数错误');

        //验证必填字段
        $data = json_decode($data, true);
        if (!isset($data['ids']) OR empty($data["ids"]) OR !isset($data['status']) OR !is_numeric($data['status'])
            OR !isset($data['rule']) OR !isset($data['rule']['collect']) OR !isset($data['rule']['shipped']) OR !isset($data['rule']['deliver']) OR !isset($data['rule']['received'])
            OR !isset($data['rule']['pick_up_point']) OR !isset($data['rule']['pick_up_point']['address'])
            OR !isset($data['rule']['pick_up_point']['relation']) OR !isset($data['rule']['pick_up_point']['keyword'])
        ) {
            $this->error_json('请求参数错误');
        } elseif (empty($data['rule']['collect'])) {
            $this->error_json('"已揽件"匹配规则不能为空');
        } elseif (empty($data['rule']['shipped'])) {
            $this->error_json('"已发货"匹配规则不能为空');
        } elseif (empty($data['rule']['pick_up_point']['address']) && empty($data['rule']['pick_up_point']['keyword'])) {
            $this->error_json('"已到提货点"匹配规则不能为空');
        } elseif (!in_array(strtoupper($data['rule']['pick_up_point']['relation']), ['AND', 'OR'])) {
            $this->error_json('"已到提货点"匹配规则关联参数错误');
        } elseif (empty($data['rule']['deliver'])) {
            $this->error_json('"派件中"匹配规则不能为空');
        } elseif (empty($data['rule']['received'])) {
            $this->error_json('"已签收"匹配规则不能为空');
        }

        //规范数据格式
        $data['rule']['collect'] = str_replace('，', ',', $data['rule']['collect']);
        $data['rule']['shipped'] = str_replace('，', ',', $data['rule']['shipped']);
        $data['rule']['pick_up_point']['address'] = str_replace('，', ',', $data['rule']['pick_up_point']['address']);
        $data['rule']['pick_up_point']['keyword'] = str_replace('，', ',', $data['rule']['pick_up_point']['keyword']);
        $data['rule']['pick_up_point']['relation'] = strtoupper($data['rule']['pick_up_point']['relation']);
        $data['rule']['deliver'] = str_replace('，', ',', $data['rule']['deliver']);
        $data['rule']['received'] = str_replace('，', ',', $data['rule']['received']);
        $data['ids'] = array_filter(explode(',', str_replace('，', ',', $data['ids'])));
        if (empty($data['ids'])) $this->error_json('请求ID参数错误');

        try {
            $this->Logistics_state_config_model->rule_batch_edit($data);
            $this->success_json([], null, '编辑成功');
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }

    /**
     * 获取已到提货点配置规则下拉列表数据
     */
    public function get_drop_down_list(){
        $data = $this->Logistics_state_config_model->get_drop_down_list();
        $this->success_json($data);
    }
}