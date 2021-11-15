<?php

/**
 * 供应商权均交期报表
 * User: Jolon
 */
class Supplier_average_delivery extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_average_delivery_model');
    }

    /**
     * 列表公共数据
     */
    private function get_params()
    {
        $params = [
            "ids" => $this->input->get_post("ids"),
            "supplier_code" => $this->input->get_post("supplier_code"),
            "first_product_line" => $this->input->get_post("first_product_line"),
            "cooperation_status" => $this->input->get_post("cooperation_status"),
            "supplier_settlement" => $this->input->get_post("supplier_settlement"),
            "statis_month_start" => $this->input->get_post("statis_month_start"),
            "statis_month_end" => $this->input->get_post("statis_month_end"),
            "ds_day_avg_start" => $this->input->get_post("ds_day_avg_start"),
            "ds_day_avg_end" => $this->input->get_post("ds_day_avg_end"),
            "os_day_avg_start" => $this->input->get_post("os_day_avg_start"),
            "os_day_avg_end" => $this->input->get_post("os_day_avg_end"),
            "ds_deliverrate_start" => $this->input->get_post("ds_deliverrate_start"),
            "ds_deliverrate_end" => $this->input->get_post("ds_deliverrate_end"),
            "os_deliverrate_start" => $this->input->get_post("os_deliverrate_start"),
            "os_deliverrate_end" => $this->input->get_post("os_deliverrate_end"),
        ];

        // 百分比转小数
        $params['ds_deliverrate_start'] = floatval($params['ds_deliverrate_start'])/100;
        $params['ds_deliverrate_end'] = floatval($params['ds_deliverrate_end'])/100;
        $params['os_deliverrate_start'] = floatval($params['os_deliverrate_start'])/100;
        $params['os_deliverrate_end'] = floatval($params['os_deliverrate_end'])/100;

        return $params;
    }

    /**
     * 权均交期列表
     */
    public function get_delivery_list()
    {
        $params = $this->get_params();

        $page                    = $this->input->get_post('offset');
        $limit                   = $this->input->get_post('limit');

        if(empty($page) or $page < 0) $page = 1;
        $limit   = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $result  = $this->Supplier_average_delivery_model->get_delivery_list($params, $offsets, $limit, $page);
        $this->success_json($result);
    }


    /**
     * 权均交期列表 - 导出
     */
    public function export_delivery_list(){
        $params = $this->get_params();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;

        $this->load->model('system/Data_control_config_model');

        $total = $this->Supplier_average_delivery_model->get_delivery_list($params, $offsets, $limit, $page,'sum');

        try {
            $result = $this->Data_control_config_model->insertDownData($params, 'EXPORT_DELIVERY', '权均交期导出', getActiveUserName(), 'csv', $total);
        } catch (Exception $exp) {
            $this->error_json($exp->getMessage());
        }
        if ($result) {
            $this->success_json([], '',"已添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }


        set_time_limit(0);
        $this->load->helper('export_csv');

        $params                         = $this->get_params();
        $page                           = 0;
        $limit                          = 1000;

        $fileName = 'SupplierDeliveryExport-'.date('YmdHis').'.csv';
        $filePath = get_export_path('supplier_delivery_export').$fileName;

        // 写入表头
        $column_list = ['序号','供应商代码','供应商','一级产品线','合作状态','结算方式','统计月份','国内仓交付天数','海外仓交付天数','国内仓10天交付率','海外仓10天交付率'];
        csv_export_file($column_list,[],$filePath);

        $flag = true;
        $pages = null;

        do{
            // 分页按批次导出
            $page ++;
            $offsets = ($page - 1) * $limit;

            if(!is_null($pages) and $page > $pages){
                $flag = false;
                break;
            }

            $data_list = $this->Supplier_average_delivery_model->get_delivery_list($params, $offsets, $limit, $page);
            is_null($pages) and $pages = $data_list['paging_data']['pages'];

            if(!empty($data_list['values'])){
                $data_list = $data_list['values'];// 数据列表

                $data_list_tmp = [];
                foreach($data_list as $value_item){
                    $data_list_tmp[] = [
                        'id' => $value_item['id'],
                        'supplier_code' => $value_item['supplier_code'],
                        'supplier_name' => $value_item['supplier_name'],
                        'linelist_cn_name' => $value_item['linelist_cn_name'],
                        'status' => $value_item['status'],
                        'supplier_settlement' => $value_item['supplier_settlement'],
                        'statis_month' => $value_item['statis_month'],
                        'ds_day_avg' => $value_item['ds_day_avg'],
                        'os_day_avg' => $value_item['os_day_avg'],
                        'ds_deliverrate' => $value_item['ds_deliverrate'],
                        'os_deliverrate' => $value_item['os_deliverrate'],
                    ];
                }
                csv_export_file(array(),$data_list_tmp,$filePath);

                unset($data_list_tmp,$data_list);
            }else{
                $flag = false;
                break;
            }
        }while($flag);

        $filePath = get_export_path_replace_host(get_export_path('supplier_delivery_export'),CG_SYSTEM_WEB_FRONT_IP).$fileName;

        $this->success_json(['file_path' => $filePath]);
    }


}