<?php

/**
 * Created by PhpStorm.
 * 同款货源列表
 * User: Jolon
 * Date: 2021/11/08 0027 11:17
 */
class Product_similar extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('product/Product_similar_model');
    }

    /**
     * 获取 配置项
     */
    public function get_similar_config()
    {
        $result = $this->Product_similar_model->get_similar_config();
        if ($result) {
            $configList = [];
            foreach ($result as $value) {
                $configList[] = [
                    "sales_type" => $value['sales_type'],
                    "days_sales_start" => $value['days_sales_start'],
                    "days_sales_end" => $value['days_sales_end'],
                    "priority" => $value['priority'],
                    "is_enable" => $value['is_enable']
                ];
            }

            $drop_down_box['is_enable_list'] = $this->Product_similar_model->_is_enable_list;

            $this->success_json(['values' => $configList, 'drop_down_box' => $drop_down_box]);
        } else {
            $this->error_json('暂无任何配置项，请添加');
        }
    }

    /**
     * 修改 配置项
     */
    public function save_similar_config()
    {
        $config_data = $this->input->get_post('config_data');// JSON 格式数据
        if (empty($config_data) or !is_json($config_data)) {
            $this->error_json('参数config_data缺失或不是JSON格式');
        }

        $config_data = json_decode($config_data, true);
        $result = $this->Product_similar_model->save_similar_config($config_data);
        if ($result['code']) {
            $this->success_json();
        } else {
            $this->error_json($result['message']);
        }
    }

    /**
     * 获取请求参数
     * @return array
     */
    public function get_params()
    {
        $params = [
            'sku_list' => $this->input->get_post('sku_list'), // SKU
            'product_line_id' => $this->input->get_post('product_line_id'), // 产品线
            'supplier_code_list' => $this->input->get_post('supplier_code_list'), // 供应商
            'supply_status' => $this->input->get_post('supply_status'), // 货源状态
            'priority' => $this->input->get_post('priority'), // 优先级
            'smc_similar_code_list' => $this->input->get_post('smc_similar_code_list'), // 同款推荐码
            'apply_number_list' => $this->input->get_post('apply_number_list'),
        ];

        return $params;
    }

    /**
     * 获取同款货源列表数据（四合一页面）
     */
    public function get_similar_list()
    {
        $params = $this->get_params();

        $type = $this->input->get_post('type');
        $offset = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');


        $similar_info = $this->Product_similar_model->get_similar_list($params, $offset, $limit, $type);
        if (isset($similar_info['code']) and $similar_info['code'] === false) {
            $this->error_json($similar_info['message']);
        }
        $this->success_json($similar_info);

    }

    /**
     * 获取 同款货源数据详情
     */
    public function get_similar_detail()
    {
        $apply_number = $this->input->get_post('apply_number');

        $similar_info = $this->Product_similar_model->get_similar_detail($apply_number);
        if (empty($similar_info)) $this->error_json('参数指定的数据不存在');

        $returnInfo = [
            'id' => $similar_info['id'],
            'yb_info_detail' => [
                "apply_number" => $similar_info['apply_number'],
                "sku" => $similar_info['sku'],
                "priority" => $similar_info['priority'],
                "applied" => $similar_info['applied'],
                "is_delete" => $similar_info['is_delete'],
                "allot_user_id" => $similar_info['allot_user_id'],
                "allot_user_name" => $similar_info['allot_user_name'],
                "status" => $similar_info['status'],
                "create_time" => $similar_info['create_time'],
                "allot_time" => $similar_info['allot_time'],
                "supply_status" => $similar_info['supply_status'],
                "product_img_url" => $similar_info['product_img_url'],
                "product_name" => $similar_info['product_name'],
                "purchase_price" => $similar_info['purchase_price'],
                "product_line_id" => $similar_info['product_line_id'],
                "rought_weight" => $similar_info['rought_weight'],
                "sample_package_size" => $similar_info['sample_package_size'],
            ],
            'similar_info_detail' => [
                "smc_similar_code" => $similar_info['smc_similar_code'],
                "smc_product_link" => $similar_info['smc_product_link'],
                "smc_supplier_name" => $similar_info['smc_supplier_name'],
                "smc_supplier_code" => $similar_info['smc_supplier_code'],
                "smc_product_name" => $similar_info['smc_product_name'],
                "smc_dev_image" => explode(';',$similar_info['smc_dev_image']),
                "smc_goods_params" => $similar_info['smc_goods_params'],
                "smc_product_cost" => $similar_info['smc_product_cost'],
                "smc_product_length" => $similar_info['smc_product_length'],
                "smc_product_height" => $similar_info['smc_product_height'],
                "smc_product_width" => $similar_info['smc_product_width'],
                "smc_rought_weight" => $similar_info['smc_rought_weight'],
                "smc_product_brand" => $similar_info['smc_product_brand'],
                "smc_product_model" => $similar_info['smc_product_model'],
                "smc_sample_type" => $similar_info['smc_sample_type'],
                "smc_product_material" => $similar_info['smc_product_material'],
                "smc_pack_list" => $similar_info['smc_pack_list'],
                "smc_remark" => $similar_info['smc_remark'],
                "smc_push_time" => $similar_info['smc_push_time'],
                "smc_push_times" =>  explode(',',$similar_info['smc_push_times']),
                "smc_submit_time" => $similar_info['smc_submit_time'],
            ]

        ];

        $this->success_json($returnInfo);
    }


    /**
     * 移除 同款货源记录
     */
    public function delete_similar()
    {
        $apply_number_list = $this->input->get_post('apply_number_list');

        if (!is_array($apply_number_list)) $this->error_json('参数编号必须是数组');

        foreach ($apply_number_list as $apply_number) {

            $similar_info = $this->Product_similar_model->get_similar_detail($apply_number);
            if (empty($similar_info)) $this->error_json('参数指定的数据不存在');


            if ($similar_info['is_delete'] == 2) {// 2.已移除的，认为是成功的 跳过
                continue;
            }

            if ($similar_info['status'] != 10) {
                $this->error_json('参数指定的数据不允许移除，请核对');
            }

            $similarDelRes = $this->Product_similar_model->delete_similar($apply_number);
            if ($similarDelRes['code'] === false) {
                $this->error_json($similarDelRes['message']);
            }
        }

        $this->success_json();
    }

    /**
     * 分配人员 同款货源记录
     */
    public function allot_user_similar()
    {
        $apply_number_list = $this->input->get_post('apply_number_list');
        $allot_user_id = $this->input->get_post('allot_user_id');
        $allot_user_name = $this->input->get_post('allot_user_name');

        if (!is_array($apply_number_list)) $this->error_json('参数编号必须是数组');

        foreach ($apply_number_list as $apply_number) {

            $similar_info = $this->Product_similar_model->get_similar_detail($apply_number);
            if (empty($similar_info)) $this->error_json('参数指定的数据不存在');


            if ($similar_info['status'] == 30) {// 已分配的，认为是成功的 跳过
                continue;
            }

            if ($similar_info['status'] != 20) {
                $this->error_json('参数指定的数据不是待分配状态');
            }

            $similarAllotRes = $this->Product_similar_model->allot_user_similar($apply_number, $allot_user_id, $allot_user_name);
            if ($similarAllotRes['code'] === false) {
                $this->error_json($similarAllotRes['message']);
            }
        }

        $this->success_json();
    }

    /**
     * 审核 同款货源记录
     */
    public function audit_similar()
    {
        $apply_number_list = $this->input->get_post('apply_number_list');
        $audit_remark = $this->input->get_post('audit_remark');
        $audit_status = $this->input->get_post('audit_status');

        if($audit_status != 35 and $audit_status != 40) $this->error_json('审核状态有误');
        if (!is_array($apply_number_list)) $this->error_json('参数编号必须是数组');

        foreach ($apply_number_list as $apply_number) {
            $similar_info = $this->Product_similar_model->get_similar_detail($apply_number);
            if (empty($similar_info)) $this->error_json('参数指定的数据不存在');

            if ($similar_info['status'] == 35 or $similar_info['status'] == 40) {// 已审核的，认为是成功的 跳过
                continue;
            }

            if ($similar_info['status'] != 30) {
                $this->error_json('参数指定的数据不是待审核状态');
            }

            $similarAuditRes = $this->Product_similar_model->audit_similar($apply_number, $audit_status, $audit_remark);
            if ($similarAuditRes['code'] === false) {
                $this->error_json($similarAuditRes['message']);
            }
        }

        $this->success_json();
    }

    /**
     * 获取操作日志
     */
    public function get_similar_logs()
    {
        $apply_number = $this->input->get_post('apply_number');

        $logs = $this->Product_similar_model->get_similar_logs($apply_number);
        $logs = isset($logs['data'])?$logs['data']:[];
        sort($logs);

        $this->success_json(['logs' => $logs]);
    }


    /**
     * 获取 同款货源推荐详情
     */
    public function get_similar_history_detail(){
        $apply_number = $this->input->get_post('apply_number');

        $similar_info = $this->Product_similar_model->get_similar_detail($apply_number);
        if (empty($similar_info)) $this->error_json('参数指定的数据不存在');

        $sku = $similar_info['sku'];
        $history_detail = $this->Product_similar_model->get_similar_history_detail($sku);

        $this->success_json($history_detail);

    }

    /**
     * 导出数据
     */
    public function similar_export(){
        $this->load->helper('export_csv');

        set_time_limit(0);
        $params = $this->get_params();
        $type = $this->input->get_post('type');

        $total = $this->Product_similar_model->get_similar_list($params, 1, 1,$type, 'total');
        if($total <= 0) $this->error_json('没有获取到数据');


        if($type == 'initial'){
            $column_list = ['sku','商品名称','优先级','产品线','易佰未税单价', '供应商名称','供应商代码','最早推送时间','货源状态'];
        }elseif($type == 'wait_allot'){
            $column_list = ['sku','商品名称','优先级','产品线','易佰未税单价','同款未税单价','供应商名称','供应商代码','结算方式','推送时间','提交时间','货源状态','同款推荐码'];
        }elseif($type == 'wait_audit'){
            $column_list = ['sku','商品名称','优先级','产品线','易佰未税单价','同款未税单价','供应商名称','供应商代码','结算方式','推送时间','提交时间','货源状态','同款推荐码'];
        }elseif($type == 'passed'){
            $column_list = ['sku','商品名称','优先级','产品线','易佰未税单价','同款未税单价','供应商名称','供应商代码','结算方式','推送时间','提交时间','货源状态','同款推荐码','同款推荐数'];
        }else{
            $this->error_json('请求类型有误');
        }

        $fileName = $type.'-'.date('YmdHis').'.csv';
        $filePath = get_export_path('similar_export').$fileName;

        csv_export_file($column_list,[],$filePath);

        $flag = true;
        $page = 0;
        $limit = 1000;

        do{
            $page ++;
            $offsets = ($page - 1) * $limit;

            $data_list = $this->Product_similar_model->get_similar_list($params, $offsets, $limit,$type,'export');

            if(!empty($data_list)){
                $data_list_tmp = [];
                foreach($data_list as $value_item){
                    $data_tmp = [
                        'sku' => $value_item['sku'],
                        'product_name' => $value_item['product_name'],
                        'priority_cn' => $value_item['priority_cn'],
                        'product_line_id_top_cn' => $value_item['product_line_id_top_cn'],
                        'purchase_price' => $value_item['purchase_price'],
                    ];

                    if($type == 'initial'){
                        $data_tmp['supplier_code'] = $value_item['supplier_code'];
                        $data_tmp['supplier_name'] = $value_item['supplier_name'];
                        $data_tmp['smc_push_time'] = $value_item['smc_push_time'];
                        $data_tmp['supply_status_cn'] = $value_item['supply_status_cn'];

                    }elseif($type == 'wait_allot' or $type == 'wait_audit' or $type == 'passed'){

                        $data_tmp['smc_product_cost'] = $value_item['smc_product_cost'];
                        $data_tmp['smc_supplier_name'] = $value_item['smc_supplier_name'];
                        $data_tmp['smc_supplier_code'] = $value_item['smc_supplier_code'];
                        $data_tmp['supplier_settlement_cn'] = $value_item['supplier_settlement_cn'];
                        $data_tmp['smc_push_time'] = $value_item['smc_push_time'];
                        $data_tmp['smc_submit_time'] = $value_item['smc_submit_time'];
                        $data_tmp['supply_status_cn'] = $value_item['supply_status_cn'];
                        $data_tmp['smc_similar_code'] = $value_item['smc_similar_code'];
                    }

                    if($type == 'passed'){
                        $data_tmp['smc_similar_total'] = $value_item['smc_similar_total'];
                    }

                    $data_list_tmp[] = $data_tmp;

                }
                csv_export_file(array(),$data_list_tmp,$filePath);

                unset($data_list_tmp,$data_list);
            }else{
                $flag = false;
            }
        }while($flag);

        $filePath = get_export_path_replace_host(get_export_path('similar_export'),CG_SYSTEM_WEB_FRONT_IP).$fileName;
        $this->success_json(['file_path' => $filePath]);
    }


}