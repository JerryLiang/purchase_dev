<?php
/**
 * 应付单报表
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/25
 * Time: 9:48
 */

class Payment_order_report extends MY_Controller {


    public function __construct(){
        parent::__construct();
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->helper('user');
        $this->load->model('Payment_order_report_model','report');
    }

    /**
     * 获取搜索条件
     */
    public function get_search_params()
    {
        $params = [];
        $params['pay_category']     = $this->input->get_post('pay_category'); //请款类型
        $params['pay_time_start']   = $this->input->get_post('pay_time_start'); // 付款开始查询时间
        $params['pay_time_end']     = $this->input->get_post('pay_time_end'); // 付款结束查询时间
        $params['purchase_type_id'] = $this->input->get_post('purchase_type_id'); // 业务线类型
        $params['purchase_source']  = $this->input->get_post('purchase_source'); // 采购来源
        $params['pay_user_id']      = $this->input->get_post('pay_user_id'); // 付款人
        $params['purchase_name']      = $this->input->get_post('purchase_agent'); // 主体
        $params['supplier_code']      = $this->input->get_post('supplier'); // 主体
        $params['compact_number']     = $this->input->get_post('compact_number');//合同单
        $params['purchase_number']    = $this->input->get_post('purchase_number');//采购单
        $params['pay_type']           = $this->input->get_post('pay_type');//支付方式筛选
        $params['is_set_report_remark'] = $this->input->get_post('is_set_report_remark');//增加查询项
        $params['pay_number'] = $this->input->get_post('pay_number');//我司交易名称
        $params['pay_account_number'] = $this->input->get_post('pay_account_number');//我司交易账户
        $params['receive_unit'] = $this->input->get_post('receive_unit');//交易对方户名
        $params['receive_account'] = $this->input->get_post('receive_account');//交易对方账号
        $params['k3_bank_account'] = $this->input->get_post('k3_bank_account');//K3账户
        $params['requisition_number'] = $this->input->get_post('requisition_number');//请款单号
        $params['settlement_method'] = $this->input->get_post('settlement_method');//结算方式
        $params['ids']     = $this->input->get_post('ids'); //ID
        return $params;
    }

    /**
     * 获取应付单明细列表
     */
    public function get_pay_order_report(){
        $params = $this->get_search_params();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $reportArr = $this->report->get_pay_order_report_new($params, $offsets, $limit,$page);
        $this->success_json($reportArr);
    }

    /**
     * 导出
     */
    public function export_pay_order_report(){
        set_time_limit(0);
        ini_set('memory_limit', '3000M');
        $params = $this->get_search_params();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->report->get_pay_order_report_new($params, $offsets, $limit,$page, 'sum');
        if (true) {
            $this->load->model('system/Data_control_config_model');
            $total = $data_list ??0;
            $ext = 'csv';
            try {
                $result = $this->Data_control_config_model->insertDownData($params, 'FINANCE_PAYMENT_LIST', '财务付款统计表', getActiveUserName(), $ext, $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json([], '',"已添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }
            exit;
        }

        $total = $data_list;
//        $this->success_json($total);
        $template_file = 'order_pay_report'.date('YmdHis').mt_rand(1000,9999).'.csv';
        if($total>100000){//一次最多导出10W条
            $template_file = 'order_pay_report.csv';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=get_export_path_replace_host(get_export_path('order_pay_report'),$down_host).$template_file;
            $this->success_json($down_file_url);
        }
        $freight = [];
        $discount = [];
        //前端路径
        $product_file = get_export_path('order_pay_report').$template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }

        fopen($product_file,'w');
        $is_head = false;
        $fp = fopen($product_file, "a");
        if($total > 0){
            $per_page = 1000;
            $total_page = ceil($total/$per_page);
            for($i = 1;$i<= $total_page;$i++){
                // header
                if($is_head === false){
                    $heads =['付款时间','采购类型','采购主体','供应商编码','供应商名称','是否退税','业务线','请款类型',
                        '摘要','我司交易名称','我司开户行','我司交易账号','k3账户','交易对方户名','交易对方开户行','交易对方账号',
                        '合同号','采购单号','支付方式','付款回单编号','请款单号','商品额','运费','优惠额','加工费',
                        '代采佣金','请款总额','付款人','备注'];
                    foreach($heads as &$m){
                        $m = iconv('UTF-8','GBK//IGNORE',$m);
                    }
                    fputcsv($fp,$heads);
                    $is_head = true;
                }

                // get body data
                $offsets = ($i - 1) * $per_page;
                $info = $this->report->get_pay_order_report_new($params, $offsets, $per_page, $page, 'export');//导出文件
                if(!isset($info['values']) || empty($info['values']))continue;
                foreach ($info['values'] as $key =>$value) {
                    $row_list = [];
                    $row=[
                        $value['payer_time'],
                        $value['source'],//采购来源
                        $value['purchase_name'],
                        $value['supplier_code'],
                        $value['supplier_name'],
                        $value['is_drawback'],
                        $value['purchase_type_id'],//业务线
                        $value['pay_category'],
                        $value['abstract_remark'],//摘要
                        $value['pay_number'],//我司交易名称
                        $value['pay_branch_bank'],//我司开户行
                        $value['pay_account_number'],//我司交易账号
                        $value['pay_account_number'],//k3账户
                        $value['receive_unit'],//交易对方户名
                        $value['payment_platform_branch'],//交易对方开户行
                        $value['receive_account'],//交易对方账号
                        $value['compact_number'],//合同号
                        $value['pur_number'],//订单号
                        $value['settlement_method'],//结算方式
                        $value['pur_tran_num'],//付款回单编号
                        $value['requisition_number'],//请款单号
                        $value['product_money'],//商品额
                        $value['freight'],//运费
                        $value['discount'],//优惠额
                        $value['process_cost'],//加工费
                        $value['commission'],//代采佣金
                        $value['pay_price'],//请款总额
                        $value['payer_name'],//付款人
                        $value['finance_report_remark'],// 备注
                    ];
                    foreach ($row as $vvv) {
                        if(preg_match("/[\x7f-\xff]/",$vvv)){
                            $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//中文转码
                        }
                        if(is_numeric($vvv) && strlen($vvv) > 9){
                            $vvv =  $vvv."\t";//避免大数字在csv里以科学计数法显示
                        }
                        $row_list[]=$vvv;
                    }
                    fputcsv($fp,$row_list);
                    unset($row_list);
                    unset($row);
                }
                ob_flush();
                flush();
                usleep(100);
            }
        }
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url = get_export_path_replace_host(get_export_path('order_pay_report'),$down_host).$template_file;
        $this->success_json($down_file_url);
    }


    /**
     * 添加、修改 财务付款统计表备注
     * @author Jolon
     */
    public function add_finance_report_remark(){
        $ids  = $this->input->get_post('id'); //请款单ID
        $finance_report_remark  = $this->input->get_post('remark'); //备注

        $ids_arr = explode(',',$ids);
        if(empty($ids) or empty($ids_arr) or !is_array($ids_arr) or empty($finance_report_remark)){
            $this->error_json('参数错误');
        }

        $result = $this->report->add_finance_report_remark($ids_arr,$finance_report_remark);
        if($result){
            $this->success_json();
        }else{
            $this->error_json('数据变更失败');
        }

    }

}