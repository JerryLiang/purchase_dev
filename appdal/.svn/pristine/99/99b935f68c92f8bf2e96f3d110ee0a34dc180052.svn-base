<?php
/**
 * User: Justin
 * Date: 2020/08/03
 * Time: 15:20
 */

class Product_line_buyer_config extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Product_line_buyer_config_model');
    }

    /**
     * 查询列表数据接口
     * /system/Product_line_buyer_config/get_data_list
     */
    public function get_data_list()
    {
        $params = [];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data = $this->Product_line_buyer_config_model->get_list_data($params, $offsets, $limit, $page);
        $this->success_json($data);
    }

    /**
     * 根据数据id更新数据接口
     * /system/Product_line_buyer_config/batch_edit
     */
    public function batch_edit()
    {
        $params = [
            'non_oversea_buyer_id' => $this->input->get_post('non_oversea_buyer_id'),    //非海外仓采购员id
            'non_oversea_buyer_name' => $this->input->get_post('non_oversea_buyer_name'),//非海外仓采购员姓名
            'oversea_buyer_id' => $this->input->get_post('oversea_buyer_id'),            //海外仓采购员id
            'oversea_buyer_name' => $this->input->get_post('oversea_buyer_name'),        //海外仓采购员姓名
        ];
        $ids = $this->input->get_post('ids');                                        //记录主键id（数组）

        if (empty($ids) OR !is_array($ids)) {
            $this->error_json('参数id格式错误');
        }
        if (empty($params['non_oversea_buyer_id']) or empty($params['non_oversea_buyer_name'])) {
            $this->error_json('非海外仓采购员候补人不能为空');
        }
        if (empty($params['oversea_buyer_id']) or empty($params['oversea_buyer_name'])) {
            $this->error_json('海外仓采购员候补人不能为空');
        }
        try {
            $this->Product_line_buyer_config_model->batch_edit($ids, $params);
            $this->success_json([], NULL, '编辑成功');
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }

    }
}