<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-04-25
 * Time: 9:23
 */

class Supplier_balance extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('statement/Supplier_balance_model', 'balance');
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->helper('user');
    }


    /**
     * 初始进入时调用该接口
     */
    public function get_purchase_agent_info(){
        $drop_down_list['purchase_name'] = get_purchase_agent();//公司主体
        $return_data                     = [
            'drop_down_box' => $drop_down_list,
        ];
        $this->success_json($return_data);
    }

    /**
     * 日报表
     */
    public function day_supplier_balance_list(){
        $params['supplier_code'] = $this->input->get_post('supplier_code');
        $params['purchase_name'] = $this->input->get_post('purchase_name');
        $params['start_date']    = $this->input->get_post('start_date');
        $params['end_date']      = $this->input->get_post('end_date');
        $page                    = $this->input->get_post('offset');
        $limit                   = $this->input->get_post('limit');
        if(empty($page) or $page < 0) $page = 1;
        $limit   = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $result  = $this->balance->day_supplier_balance_list($params, $offsets, $limit, $page);
        $result['values'] = format_price_multi_floatval($result['values'],false,true,2);
        $this->success_json($result);
    }

    /**
     * 月报表
     */
    public function month_supplier_balance_list(){
        $params['supplier_code'] = $this->input->get_post('supplier_code');
        $params['start_date']    = $this->input->get_post('start_date');
        $params['end_date']      = $this->input->get_post('end_date');
        $params['purchase_name'] = $this->input->get_post('purchase_name');
        $params['ending_balance'] = $this->input->get_post('ending_balance');
        $params['settlement_inland'] = $this->input->get_post('settlement_inland');
        $params['settlement_oversea'] = $this->input->get_post('settlement_oversea');
        $params['order_by']      = $this->input->get_post('order_by');
        $params['order_by_type'] = $this->input->get_post('order_by_type');
        $params['diff_type']     = $this->input->get_post('diff_type');
        $page                    = $this->input->get_post('offset');
        $limit                   = $this->input->get_post('limit');
        if(empty($page) or $page < 0) $page = 1;
        $limit   = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $result  = $this->balance->month_supplier_balance_list($params, $offsets, $limit, $page);
        $result['values'] = format_price_multi_floatval($result['values'],false,true,2);
        $this->success_json($result);
    }

    /**
     * 年度报表
     */
    public function year_supplier_balance_list(){
        $params['supplier_code'] = $this->input->get_post('supplier_code');
        $params['date']          = $this->input->get_post('date');
        $params['purchase_name'] = $this->input->get_post('purchase_name');
        $params['ending_balance'] = $this->input->get_post('ending_balance');
        $params['settlement_inland'] = $this->input->get_post('settlement_inland');
        $params['settlement_oversea'] = $this->input->get_post('settlement_oversea');
        $params['order_by']      = $this->input->get_post('order_by');
        $params['order_by_type'] = $this->input->get_post('order_by_type');
        $params['diff_type']     = $this->input->get_post('diff_type');
        $page                    = $this->input->get_post('offset');
        $limit                   = $this->input->get_post('limit');
        if(empty($page) or $page < 0) $page = 1;
        $limit   = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $result  = $this->balance->year_supplier_balance_list($params, $offsets, $limit, $page);
        $result['values'] = format_price_multi_floatval($result['values'],false,true,2);
        $this->success_json($result);
    }

    /**
     * 导出数据
     */
    public function balance_export(){
        set_time_limit(0);
        $params['ids']           = $this->input->get_post('ids');
        $params['supplier_code'] = $this->input->get_post('supplier_code');
        $params['purchase_name'] = $this->input->get_post('purchase_name');
        $params['start_date']    = $this->input->get_post('start_date');
        $params['end_date']      = $this->input->get_post('end_date');
        $params['date']          = $this->input->get_post('date');
        $params['ending_balance'] = $this->input->get_post('ending_balance');
        $params['settlement_inland'] = $this->input->get_post('settlement_inland');
        $params['settlement_oversea'] = $this->input->get_post('settlement_oversea');
        $params['diff_type']     = $this->input->get_post('diff_type');
        $date_type               = $this->input->get_post('date_type');
        if(!in_array($date_type, ['day','month','year'])){
            $this->error_json('导出的数据不在数据范围，请检查需要导出的类型!');
        }

        $page                           = 0;
        $limit                          = 10000;

        $fileName = $date_type.'-'.date('YmdHis').'.csv';
        $filePath = get_export_path('balance_export').$fileName;

        // 写入表头
        if($date_type == 'month' or $date_type == 'year'){
            $column_list   = ['供应商代码', '供应商', '采购主体','国内/FBA结算方式','海外仓结算方式', '生成时间', '统计时间', '期初余额', '入库应付', '其他应付', '本期已付', '退款金额', '报损额', '调整金额', '期末余额'];
        }else{
            $column_list   = ['供应商代码', '供应商', '采购主体', '生成时间', '统计时间', '期初余额', '入库应付', '其他应付', '本期已付', '退款金额', '报损额', '调整金额', '期末余额'];
        }
        csv_export_file($column_list,[],$filePath);

        $flag = true;
        $pages = null;

        do{
            $page ++;
            $offsets = ($page - 1) * $limit;

            if(!is_null($pages) and $page > $pages){
                break;
            }

            if($date_type == 'day'){
                $data_list = $this->balance->day_supplier_balance_list($params, $offsets, $limit, $page);
            }elseif($date_type == 'month'){
                $data_list = $this->balance->month_supplier_balance_list($params, $offsets, $limit, $page);
            }else{
                $data_list = $this->balance->year_supplier_balance_list($params, $offsets, $limit, $page);
            }

            is_null($pages) and $pages = $data_list['paging_data']['pages'];

            if(!empty($data_list['values'])){
                $data_list     = $data_list['values'];// 数据列表
                $data_list_tmp = [];
                foreach($data_list as $value_item){
                    if($date_type == 'month' or $date_type == 'year'){
                        $data_list_tmp[] = [
                            'supplier_code'     => $value_item['supplier_code'],
                            'supplier_name'     => $value_item['supplier_name'],
                            'purchase_name'     => $value_item['purchase_name'],
                            'settlement_inland_cn'  => $value_item['settlement_inland_cn'],
                            'settlement_oversea_cn' => $value_item['settlement_oversea_cn'],
                            'occurrence_time'   => $value_item['occurrence_time'],
                            'statistic_time'    => $value_item['statistic_time'],
                            'c_opening_balance' => $value_item['c_opening_balance'],
                            'c_instock_money'   => $value_item['c_instock_money'],
                            'c_other_money'     => $value_item['c_other_money'],
                            'c_paid_money'      => $value_item['c_paid_money'],
                            'c_refunded_money'  => $value_item['c_refunded_money'],
                            'c_loss_money'      => $value_item['c_loss_money'],
                            'c_adjust_money'    => $value_item['c_adjust_money'],
                            'c_ending_balance'  => $value_item['c_ending_balance'],
                        ];
                    }else{
                        $data_list_tmp[] = [
                            'supplier_code'     => $value_item['supplier_code'],
                            'supplier_name'     => $value_item['supplier_name'],
                            'purchase_name'     => $value_item['purchase_name'],
                            'occurrence_time'   => $value_item['occurrence_time'],
                            'statistic_time'    => $value_item['statistic_time'],
                            'c_opening_balance' => $value_item['c_opening_balance'],
                            'c_instock_money'   => $value_item['c_instock_money'],
                            'c_other_money'     => $value_item['c_other_money'],
                            'c_paid_money'      => $value_item['c_paid_money'],
                            'c_refunded_money'  => $value_item['c_refunded_money'],
                            'c_loss_money'      => $value_item['c_loss_money'],
                            'c_adjust_money'    => $value_item['c_adjust_money'],
                            'c_ending_balance'  => $value_item['c_ending_balance'],
                        ];
                    }
                }
                csv_export_file(array(),$data_list_tmp,$filePath);

                unset($data_list_tmp,$data_list);
            }else{
                $flag = false;
            }
        }while($flag);

        $filePath = get_export_path_replace_host(get_export_path('balance_export'),CG_SYSTEM_WEB_FRONT_IP).$fileName;
        $this->success_json(['file_path' => $filePath]);
    }

    /**
     * 页面汇总数据
     */
    public function get_statistic_list(){
        set_time_limit(0);
        $params['supplier_code'] = $this->input->get_post('supplier_code');
        $params['purchase_name'] = $this->input->get_post('purchase_name');
        $params['ending_balance'] = $this->input->get_post('ending_balance');
        $params['settlement_inland'] = $this->input->get_post('settlement_inland');
        $params['settlement_oversea'] = $this->input->get_post('settlement_oversea');
        $params['start_date']    = $this->input->get_post('start_date');
        $params['end_date']      = $this->input->get_post('end_date');
        $params['date']          = $this->input->get_post('date');
        $params['statistic']     = true;// 表示是统计数据

        $date_type               = $this->input->get_post('date_type');
        if(!in_array($date_type, ['day','month','year'])){
            $this->error_json('导出的数据不在数据范围，请检查需要导出的类型!');
        }

        if($date_type == 'day'){
            $data_list = $this->balance->day_supplier_balance_list($params, null, null, null);
        }elseif($date_type == 'month'){
            $data_list = $this->balance->month_supplier_balance_list($params, null, null, null);
        }else{
            $data_list = $this->balance->year_supplier_balance_list($params, null, null, null);
        }

        if(isset($data_list['statistic_list'])){
            // 数据根式化
            $data_list['statistic_list']['c_instock_money'] = format_two_point_price($data_list['statistic_list']['c_instock_money']);
            $data_list['statistic_list']['c_other_money'] = format_two_point_price($data_list['statistic_list']['c_other_money']);
            $data_list['statistic_list']['c_paid_money'] = format_two_point_price($data_list['statistic_list']['c_paid_money']);
            $data_list['statistic_list']['c_refunded_money'] = format_two_point_price($data_list['statistic_list']['c_refunded_money']);
            $data_list['statistic_list']['c_loss_money'] = format_two_point_price($data_list['statistic_list']['c_loss_money']);
            $data_list['statistic_list']['c_adjust_money'] = format_two_point_price($data_list['statistic_list']['c_adjust_money']);
            $data_list['statistic_list']['c_ending_balance'] = format_two_point_price($data_list['statistic_list']['c_ending_balance']);
        }

        $this->success_json($data_list);
    }
}