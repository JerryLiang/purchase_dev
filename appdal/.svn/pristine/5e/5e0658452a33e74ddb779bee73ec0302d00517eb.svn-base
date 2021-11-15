<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/12/26
 * Time: 14:32
 */

class Rule_config extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('check/Rule_config_model', 'rule_config_model');
    }

    /**
     * 获取规则列表数据
     * /supplier/check/Rule_config/get_data_list?uid=1528&purchase_type_id=&product_line_id=&enable_status=&sort_by=&offset=1&limit=20
     */
    public function get_data_list()
    {
        $params = [
            'purchase_type_id' => $this->input->get_post('purchase_type_id'), // 业务线
            'product_line_id' => $this->input->get_post('product_line_id'), // 一级产品线
            'enable_status' => $this->input->get_post('enable_status'), // 是否启用
            'is_boutique' => $this->input->get_post('is_boutique'), // 是否熏蒸
            'min_price' => $this->input->get_post('min_price'), // 最小金额
            'max_price' => $this->input->get_post('max_price'), // 最大金额
            'min_refund_rate' => $this->input->get_post('min_refund_rate'), // 最小退款率
            'max_refund_rate' => $this->input->get_post('max_refund_rate'), // 最大退款率
            'sort_by' => $this->input->get_post('sort_by'),//排序类型（asc-升序，desc-降序）
        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) OR $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data = $this->rule_config_model->get_list_data($params, $offsets, $limit, $page);
        $this->success_json($data);
    }

    /**
     * 获取编辑规则数据
     * /supplier/check/Rule_config/get_edit_data?uid=1528
     */
    public function get_edit_data()
    {
        $data = $this->rule_config_model->get_edit_data();
        $this->success_json($data);
    }

    /**
     * 批量编辑规则
     * /supplier/check/Rule_config/rule_batch_edit
     * @author Justin
     * @date 2019/11/30
     */
    public function rule_batch_edit()
    {
        $data = $this->input->post_get('data_initial'); //数组格式
        if (empty($data) OR !is_json($data)) $this->error_json('请求参数错误');

        //验证必填字段
        $data = json_decode($data, true);
        if (empty($data["effective_time"])) {
            $this->error_json('生效时间不能为空');
        } elseif (empty($data['rule']) || !is_array($data['rule'])) {
            $this->error_json('请求参数不全');
        }
        $error_msg = [];

        $has = [];
        $k = 1;
        $data_temp = [];
        foreach ($data['rule'] as $val) {
            if($val['is_boutique'] == '否')$val['is_boutique'] = 0;
            if($val['is_boutique'] == '是')$val['is_boutique'] = 1;
            if(!in_array($val['is_boutique'], [0, 1]) && empty($val['min_price']) && empty($val['max_price']))continue;
            $row = $val['is_boutique']."_".$val['min_price']."_".$val['max_price'];
            if(!$val['id'] && in_array($row, $has)){
                $error_msg[] = "第{$k}行的数据已存在，请检查后再提交";
            }
            $has[] = $row;
            //是否启用
            if (!SetAndNotEmpty($val, 'status', 'n')){
                $error_msg[] = '启用状态不能为空';
            }
            $data_temp[] = $val;
            $k ++;
        }

        if(!empty($error_msg)){
            $this->error_json(implode(',', $error_msg));
        }

        $result = $this->rule_config_model->rule_batch_edit($data_temp, $data["effective_time"]);
        if ($result['flag']) {
            $this->success_json($result, null, '修改成功');
        } else {
            $this->error_json($result['msg']);
        }
    }
}