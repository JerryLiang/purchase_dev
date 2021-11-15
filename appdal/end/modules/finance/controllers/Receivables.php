<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 收款控制器
 */

class Receivables extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_order_pay_model');
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->model('receivables_model');
        $this->load->helper('user');
    }

    /**
     * 搜索列
     * @author harvin 
     * http://www.caigou.com/finance/receivables/list_search
     * */
    public function list_search() {
        $data_list['application_id'] = get_buyer_name(); //申请人
        $data_list['pay_status'] = getReceivePayStatus(); //收款状态
        $data_list['pay_type'] = getPayType(); //支付方式
        $data_list['is_cross_border'] = getCrossBorder(); //跨境宝供应商
        $this->success_json($data_list);
    }

    /**
     * 列表显示
     * @author harvin 2019-1-18
     * http://www.caigou.com/finance/receivables/rece_list
     * * */
    public function rece_list() {
        $params['supplier_code'] = $this->input->get_post('supplier_code'); //供应商编码
        $params['application_id'] = $this->input->get_post('application_id'); //申请人
        $params['pay_status'] = $this->input->get_post('pay_status'); //付款状态
        $params['pay_type'] = $this->input->get_post('pay_type'); //支付方式
        $params['is_cross_border'] = $this->input->get_post('is_cross_border'); //跨境宝供应商
        $params['cancel_number'] = $this->input->get_post('cancel_number'); //取消未到货编码
        $params['purchase_number'] = $this->input->get_post('purchase_number'); //采购单号
        $params['create_time_start'] = $this->input->get_post('create_time_start'); // 创建时间-开始
        $params['create_time_end'] = $this->input->get_post('create_time_end'); // 创建时间-结束
        $params['payer_time_start'] = $this->input->get_post('payer_time_start'); // 收款时间-开始
        $params['payer_time_end'] = $this->input->get_post('payer_time_end'); // 收款时间-结束
        $params['serial_number'] = $this->input->get_post('serial_number'); // 收款流水号
        $params['pai_number'] = $this->input->get_post('pai_number'); // 拍单号
        $params['cancel_order_type'] = $this->input->get_post('cancel_order_type'); // 取消订单类型
        $params['completed_time_start'] = $this->input->get_post('completed_time_start'); // 退款完成时间-开始
        $params['completed_time_end'] = $this->input->get_post('completed_time_end'); // 退款完成时间-开始
        $params['screenshot_time_start'] = $this->input->get_post('screenshot_time_start'); // 上传截图时间-开始
        $params['screenshot_time_end'] = $this->input->get_post('screenshot_time_end'); // 上传截图时间-结束
        $params['diff_search'] = $this->input->get_post('diff_search'); // 差异类型搜索
        $params['payer_user_id'] = $this->input->get_post('payer_user_id'); // 差异类型搜索
        $params['our_account'] = $this->input->get_post('our_account'); // 收款账号
        $params['group_ids'] = $this->input->get_post('group_ids'); // 采购组别

        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');

        if (empty($page) or $page < 0)
            $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->receivables_model->get_rece_list($params, $offsets, $limit,$page);
        $this->success_json($data_list);
    }

    /**
     * 待收款显示
     * http://www.caigou.com/finance/receivables/receivable
     * * */
    public function receivable() {
        try {
            $id = $this->input->get_post('id'); //参数id
            if (empty($id)) {
                $this->error_json('参数错误');
            }
            $data_list = $this->receivables_model->get_receivable($id);
            $this->success_json($data_list);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }

    /**
     * 保存待收款操作
     * @author harvin 2019-1-19 
     * http://www.caigou.com/finance/receivables/receivable_save
     * */
    public function receivable_save() {
        $id = $this->input->get_post('id'); //参数id
        $price = $this->input->get_post('price'); //金额（收款金额）
        $collection_time = $this->input->get_post('collection_time'); //收款时间
        $remarks = $this->input->get_post('remarks'); //备注
        $account_short = $this->input->get_post('account_short'); //我方支行简称
        $branch = $this->input->get_post('branch'); //我方支行
        $account_number = $this->input->get_post('account_number'); //我方银行卡号
        $account_holder = $this->input->get_post('account_holder'); //我方开户人
        $type = $this->input->get_post('type'); //$type 1 审核通过 2为驳回
        $cancel_order_type = $this->input->get_post('cancel_order_type');//1 普通采购 2样品采购

        if(empty($cancel_order_type)){
            $cancel_order_type = 1;
        }
        if (empty($id)) {
            $this->error_json('参数错误');
        }
        if (empty($price)) {
            $this->error_json('请填写收款金额');
        }
        if (empty($remarks)) {
            $this->error_json('请填写收款备注');
        }
        if ($type == 1) {
            if (empty($collection_time)) {
                $this->error_json('请填写收款时间');
            }
            if (empty($account_short)) {
                $this->error_json('请选付款我方支行简称');
            }
        }

        $receipt_order = $this->receivables_model->get_receivable_status($id);
        if(empty($receipt_order)){
            $this->error_json('收款单不存在');
        }
        if($receipt_order != RECEIPT_PAY_STATUS_WAITING_RECEIPT){
            $this->error_json('只有【待收款】才能收款操作');
        }

        $temp = $this->receivables_model->get_receivable_save($id, $price, $collection_time, $remarks,$account_short, $branch,$account_number, $account_holder, $type,$cancel_order_type);
        if ($temp['bool']) {

            if($type == 2){

                $this->load->model('Message_model');
                $this->Message_model->AcceptMessage('determine',['data'=>$id,'message'=>$remarks,'user'=>getActiveUserName(),'type'=>'财务收款']);
            }
            $this->success_json([],null,$temp['msg']);
        } else {
            $this->error_json($temp['msg']);
        }
    }

    /**
     * 查看收款详情
     * @author harvin 2019-1-19
     * http://www.caigou.com/finance/receivables/receivable_info
     * * */
    public function receivable_info() {
        try {
            $id = $this->input->get_post('id'); //参数id
            if (empty($id)) {
                $this->error_json('参数错误');
            }
            $data_list = $this->receivables_model->get_receivable_info($id);
            $this->success_json($data_list);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }

    /**
     * 根据收款信息拉取1688退款信息
     */
    public function get_ali_refund(){
        $limit = $this->input->get_post('limit');
        if(empty($limit)){
            $limit=10;
        }
        $this->receivables_model->getAliQueryOrderRefund($limit);
    }

    /**
     * 根据请款单获取应收款详细
     */

    public function get_receivable_info_item(){
        try {
            $cancel_number = $this->input->get_post('cancel_number');
            if (empty($cancel_number)) {
                $this->error_json('参数错误');
            }
            $data_list = $this->receivables_model->receivable_info_item($cancel_number);
            $this->success_json($data_list);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }

    /**
     * 财务导出
     * @author Justin 2019-09-27
     * /finance/receivables/export_list
     */
    public function export_list(){
        set_time_limit(0);
        ini_set('memory_limit', '3000M');
        $params     = gp();

        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');

        if (empty($page) or $page < 0)            $page = 1;
        $limit = query_limit_range($limit);
        $params['offset'] = $page;
        $params['limit'] = $limit;

        $page_data=$this->format_page_data();
        $total = $this->receivables_model->get_rece_list($params,0,$limit,$page);
        $total = isset($total['paging_data']['total'])?$total['paging_data']['total']:0;
        $template_file = 'receivables_'.date('YmdHis').mt_rand(1000,9999).'.csv';
        if($total>100000){//一次最多导出10W条
            $template_file = 'receivables.csv';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=get_export_path_replace_host(get_export_path('receivables'),$down_host).$template_file;
            $this->success_json($down_file_url);
        }
        $freight = [];
        $discount = [];

        $product_file = get_export_path('receivables').$template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $is_head = false;
        $fp = fopen($product_file, "a");
        if($total > 0){
            $page_limit = 1000;
            $total_page = ceil($total/$page_limit);
            for($i = 1;$i<= $total_page;$i++){
                $export_offset      = ($i - 1) * $page_limit;
                $data = $this->receivables_model->get_rece_list($params,$export_offset,$page_limit,$i);
                    //组装需求数据格式
                    foreach ($data['values'] as $key =>$value) {
                        $row=[
                            $value['pay_status'],
                            $value['cancel_number'],
                            $value['purchase_number'],
                            $value['pai_number'],
                            $value['supplier_name'],
                            $value['supplier_code'],
                            $value['pay_type'],
                            $value['settlement_method'],
                            $value['payment_platform'],
                            $value['pay_price'],
                            $value['real_refund'],
                            $value['diff_amount'],
                            $value['serial_number'],
                            $value['apply_notice'],
                            $value['apply_amount'],
                            $value['apply_user_name'],
                            $value['apply_time'],
                            $value['audit_user_name'],
                            $value['audit_time'],
                            $value['payer_user_name'],
                            $value['payer_time'],
                            $value['payer_notice'],
                            $value['completed_time'],
                            $value['our_account'],
                        ];
                        foreach ($row as $vvv) {
                            if(preg_match("/[\x7f-\xff]/",$vvv)){
                                $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//中文转码
                            }
                            if(is_numeric(trim($vvv)) && strlen(trim($vvv)) > 9){
                                $vvv =  $vvv."\t";//避免大数字在csv里以科学计数法显示
                            }
                            $row_list[]=$vvv;
                        }
                        if($is_head === false){
                            $heads = [
                                '收款状态',
                                '申请编号',
                                '采购单号',
                                '拍单号',
                                '供应商',
                                '供应商代码',
                                '支付方式',
                                '结算方式',
                                '支付平台',
                                '收款金额',
                                '实际退款金额',
                                '差额',
                                '收款流水号',
                                '退款类型',
                                '1688退款金额',
                                '申请人',
                                '申请时间',
                                '审核人',
                                '审核时间',
                                '收款人',
                                '收款时间',
                                '收款备注',
                                '实际退款时间',
                                '收款账号'
                            ];
                            foreach($heads as &$m){
                                $m = iconv('UTF-8','GBK//IGNORE',$m);
                            }
                            fputcsv($fp,$heads);
                            $is_head = true;
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
        $down_file_url= get_export_path_replace_host(get_export_path('receivables'),$down_host).$template_file;
        $this->success_json($down_file_url);
    }

    /**
     * totoro
     * 修改收款的备注
     */
    public function edit_receivable_note(){
        $id = $this->input->get_post('id');
        $update_params = [
            'payer_notice' => $this->input->get_post('payer_notice'),
            'account_short' => $this->input->get_post('account_short'), //我方支行简称
            'branch' => $this->input->get_post('branch'),//我方支行
            'account_number' => $this->input->get_post('account_number'),//我方银行卡号
            'account_holder' => $this->input->get_post('account_holder'),//我方开户人
        ];
        if (empty($id)) {
            $this->error_json('收款单ID必填!');
        }
        if (empty($update_params['payer_notice'])) {
            $this->error_json('修改的备注必填!');
        }
        if (empty($update_params['account_short'])) {
            $this->error_json('我方收款账户信息-账号简称-必填!');
        }
        $result = $this->receivables_model->edit_receivable_note($id,$update_params);
        if($result){
            $this->success_json($result);
        }else{
            $this->error_json('修改备注失败!');
        }
    }
}
