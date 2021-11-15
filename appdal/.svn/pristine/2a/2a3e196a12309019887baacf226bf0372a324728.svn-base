<?php
/**
 * 供应商余额总表-交易明细
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2020-09-10
 * Time: 9:42
 */

class Supplier_trading_detail extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('Supplier_trading_detail_model');
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->helper('user');
    }

    /**
     * 供应商余额汇总表 - 交易明细
     */
    public function get_trading_detail_list(){
        $params                         = $this->_get_params();
        $page                           = $this->input->get_post('offset');
        $limit                          = $this->input->get_post('limit');
        if(empty($page) or $page < 0) $page = 1;
        $limit     = query_limit_range($limit);
        $offsets   = ($page - 1) * $limit;
        $data_list = $this->Supplier_trading_detail_model->get_trading_detail_list($params, $offsets, $limit, $page);

        $data_list['values'] = format_price_multi_floatval($data_list['values'],false,true,2);
        $this->success_json($data_list);
    }

    /**
     * 供应商余额汇总表 - 交易明细 - 页面汇总
     */
    public function get_statistic_list(){
        $params                         = $this->_get_params();
        $params['statistic']            = true;// 表示是统计数据

        $data_list = $this->Supplier_trading_detail_model->get_trading_detail_list($params, null, null, null);

        if(isset($data_list['statistic_list'])){
            // 数据根式化
            $data_list['statistic_list']['total_trading_money'] = format_two_point_price($data_list['statistic_list']['total_trading_money']);
        }

        $this->success_json($data_list);
    }


    /**
     * 供应商余额汇总表 - 交易明细 - 导出
     */
    public function export_trading_detail_list(){
        set_time_limit(0);
        $this->load->helper('export_csv');

        $params                         = $this->_get_params();
        $page                           = 0;
        $limit                          = 10000;

        $fileName = 'TradingDetail-'.date('YmdHis').'.csv';
        $filePath = get_export_path('export_trading_detail').$fileName;

        // 写入表头
        $column_list = ['交易ID','关联单据号','供应商代码','供应商名称','采购主体','交易时间','交易类型','交易描述','交易金额'];
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

            $data_list = $this->Supplier_trading_detail_model->get_trading_detail_list($params, $offsets, $limit, $page);
            is_null($pages) and $pages = $data_list['paging_data']['pages'];

            if(!empty($data_list['values'])){
                $data_list = $data_list['values'];// 数据列表

                $data_list_tmp = [];
                foreach($data_list as $value_item){
                    $data_list_tmp[] = [
                        'order_no'             => $value_item['order_no'],
                        'relative_trading_num' => $value_item['relative_trading_num'],
                        'supplier_code'        => $value_item['supplier_code'],
                        'supplier_name'        => $value_item['supplier_name'],
                        'purchase_name'        => $value_item['purchase_name'],
                        'trading_time'         => $value_item['trading_time'],
                        'trading_type'         => $value_item['trading_type'],
                        'trading_note'         => $value_item['trading_note'],
                        'trading_money'        => $value_item['trading_money']
                    ];
                }
                csv_export_file(array(),$data_list_tmp,$filePath);

                unset($data_list_tmp,$data_list);
            }else{
                $flag = false;
                break;
            }
        }while($flag);

        $filePath = get_export_path_replace_host(get_export_path('export_trading_detail'),CG_SYSTEM_WEB_FRONT_IP).$fileName;

        $this->success_json(['file_path' => $filePath]);
    }

    private function _get_params(){
        $params['ids']                  = $this->input->get_post('ids');
        $params['order_no']             = $this->input->get_post('order_no');//关联单据号
        $params['relative_trading_num'] = $this->input->get_post('relative_trading_num'); //关联单据号
        $params['supplier_code']        = $this->input->get_post('supplier_code'); //供应商编码
        $params['purchase_name']        = $this->input->get_post('purchase_name'); //采购主体
        $params['start_trading_time']   = $this->input->get_post('start_trading_time'); //交易时间
        $params['end_trading_time']     = $this->input->get_post('end_trading_time'); //交易时间
        $params['trading_type']         = $this->input->get_post('trading_type');// 交易类型

        return $params;
    }




}