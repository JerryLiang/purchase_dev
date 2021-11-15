<?php

/**
 * 线下收款列表
 * Class Offline_receipt
 * @author Jolon
 * @date 2021-01-12 14:50:01
 */
class Offline_receipt extends MY_Controller
{

   public function __construct(){
        parent::__construct();
        $this->load->model('Offline_receipt_model');
    }

    /**
     * 获取查询参数
     * @return array
     */
    public function get_query_params(){
        return [
            'ids' => $this->input->get_post('ids'),
            'refund_number' => $this->input->get_post('refund_number'),
            'refund_log_number' => $this->input->get_post('refund_log_number'),
            'purchase_number' => $this->input->get_post('purchase_number'),
            'apply_user_id' => $this->input->get_post('apply_user_id'),
            'apply_time_start' => $this->input->get_post('apply_time_start'),
            'apply_time_end' => $this->input->get_post('apply_time_end'),
            'refund_status' => $this->input->get_post('refund_status'),
            'refund_water_number' => $this->input->get_post('refund_water_number'),
            'refund_reason' => $this->input->get_post('refund_reason'),
            'supplier_code' => $this->input->get_post('supplier_code'),
            'refund_price_start' => $this->input->get_post('refund_price_start'),
            'refund_price_end' => $this->input->get_post('refund_price_end'),
            'compact_number' => $this->input->get_post('compact_number'),
            'statement_number' => $this->input->get_post('statement_number'),
            'groupname' => $this->input->get_post('groupname'),// 采购组别,通采购单列表查询
            'refund_channel' => $this->input->get_post('refund_channel'),
            'receipt_time_start' => $this->input->get_post('receipt_time_start'),
            'receipt_time_end' => $this->input->get_post('receipt_time_end'),
            'receipt_account_short' => $this->input->get_post('receipt_account_short'),
            'receipt_account_number' => $this->input->get_post('receipt_account_number'),
            'receipt_user_id' => $this->input->get_post('receipt_user_id'),
            'refund_type' => $this->input->get_post('refund_type'),
            'refund_time_start' => $this->input->get_post('refund_time_start'),
            'refund_time_end' => $this->input->get_post('refund_time_end'),
        ];
    }

    /**
     * 获取 线下收款列表 数据列表
     */
    public function get_offline_receipt_list(){
        $params = $this->get_query_params();

        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;

        $list = $this->Offline_receipt_model->get_offline_receipt_list($params,$offsets, $limit,$page);

        $this->load->model('finance/payment_order_pay_model');
        $list['drop_down_box']['account_short_list'] = $this->payment_order_pay_model->get_bank();
        $list['drop_down_box']['applicant'] = get_buyer_name(); //申请人
        $list['drop_down_box']['refund_channel'] = [3=>'支付宝',5=>'网银转账']; //3.支付宝;5.网银转账
        $this->success_json($list);
    }


    /**
     * 确认待收款 界面
     */
    public function confirm_waiting_receipt(){
        $id = $this->input->get_post('id');

        $receiptInfo = $this->Offline_receipt_model->get_offline_receipt_one($id);
        if(empty($receiptInfo)) $this->error_json('目标ID对应的记录不存在');

        if($receiptInfo['refund_status'] != 1){
            $this->error_json('只有状态为:待财务收款状态时,才能操作待收款');
        }


        $purchase_name_list = get_purchase_agent();
        $payment_platform_all = get_supplier_payment_platform_all();

        if(strlen($receiptInfo['refund_water_append']) < 5){
            $receiptInfo['refund_water_append'] = '';
        }
        // 转换成中文
        $receiptInfo['purchase_name_cn'] = isset($purchase_name_list[$receiptInfo['purchase_name']])?$purchase_name_list[$receiptInfo['purchase_name']]:'未知主体';
        $receiptInfo['refund_channel_cn'] = isset($payment_platform_all[$receiptInfo['refund_channel']])?$payment_platform_all[$receiptInfo['refund_channel']]:'未知渠道';

        $this->success_json($receiptInfo);

    }


    /**
     * 确认待收款 驳回/确认到账
     */
    public function confirm_receipted(){
        $id = $this->input->get_post('id');
        $confirm_status = $this->input->get_post('confirm_status');// 2.确认到账，3.驳回
        $receipt_notice = $this->input->get_post('receipt_notice');
        $receipt_account_short = $this->input->get_post('receipt_account_short');

        if(empty($id)) $this->error_json(' 参数 ID 缺失 ');
        if(empty($confirm_status)) $this->error_json(' 参数 确认状态 缺失');
        if(empty($receipt_notice)) $this->error_json(' 参数 收款备注 必填');

        if($confirm_status == 2 and empty($receipt_account_short)){
            $this->error_json(' 参数 我方收款账号信息：账户简称 必填');
        }

        $result = $this->Offline_receipt_model->confirm_receipted($id,$confirm_status,$receipt_notice,$receipt_account_short);
        if($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }

    }

    /**
     * 查看 收款详情
     */
    public function get_receipt_details(){
        $id = $this->input->get_post('id');

        $receiptInfo = $this->Offline_receipt_model->get_offline_receipt_one($id);
        if(empty($receiptInfo)) $this->error_json('目标ID对应的记录不存在');


        $this->load->model('finance/payment_order_pay_model');
        $this->load->model('system/Bank_card_model', 'bankCart');
        $bankInfo = $this->bankCart->findOne(['account_short' => $receiptInfo['receipt_account_short']]);

        $refund_status_list = $this->Offline_receipt_model->refund_status_list;
        $purchase_name_list = get_purchase_agent();
        $payment_platform_all = get_supplier_payment_platform_all();

        if(strlen($receiptInfo['refund_water_append']) < 5){
            $receiptInfo['refund_water_append'] = '';
        }
        // 转换成中文
        $receiptInfo['purchase_name_cn'] = isset($purchase_name_list[$receiptInfo['purchase_name']])?$purchase_name_list[$receiptInfo['purchase_name']]:'未知主体';
        $receiptInfo['refund_channel_cn'] = isset($payment_platform_all[$receiptInfo['refund_channel']])?$payment_platform_all[$receiptInfo['refund_channel']]:'未知渠道';
        $receiptInfo['refund_status_cn'] = isset($refund_status_list[$receiptInfo['refund_status']])?$refund_status_list[$receiptInfo['refund_status']]:'未知状态';
        $receiptInfo['bank_branch'] = isset($bankInfo['branch'])?$bankInfo['branch']:'';
        $receiptInfo['bank_account_holder'] = isset($bankInfo['account_holder'])?$bankInfo['account_holder']:'';

        $historyLogs = $this->Offline_receipt_model->get_receipt_logs($id);
        $receiptData['receipt_info'] = $receiptInfo;
        $receiptData['history_logs'] = $historyLogs;
        $receiptData['account_short_list'] = $this->payment_order_pay_model->get_bank();

        if($receiptInfo){
            $this->success_json($receiptData);
        }else{
            $this->error_json("目标ID对应的记录不存在");
        }
    }

    /**
     * 更新 收款详情
     */
    public function update_receipt_details(){
        $id = $this->input->get_post('id');
        $receipt_notice = $this->input->get_post('receipt_notice');
        $receipt_account_short = $this->input->get_post('receipt_account_short');

        if(empty($id)) $this->error_json(' 参数 ID 缺失 ');
        if(empty($receipt_account_short)) $this->error_json(' 参数 我方收款账号信息：账户简称 必填');
        if(empty($receipt_notice)) $this->error_json(' 参数 收款备注 必填');

        $result = $this->Offline_receipt_model->update_receipt_details($id,$receipt_notice,$receipt_account_short);

        if($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 导出 收款列表
     */
    public function export_list_csv(){
        $params = $this->get_query_params();

        $template_file = 'offline_receipt_'.date('YmdHis').mt_rand(1000, 9999) . '.csv';
        $template_path = get_export_path('offline_receipt') . $template_file;
        if (file_exists($template_path)) {
            unlink($template_path);
        }

        fopen($template_path, 'w');
        $fp = fopen($template_path, "a");

        $heads =[
            '申请编号',
            '退款状态',
            '供应商名称',
            '供应商代码',
            '采购单号',
            '采购主体',
            '合同号',
            '对账单号',
            '申请人',
            '申请备注',
            '退款类型',
            '退款渠道',
            '退款原因',
            '退款时间',
            '退款金额',
            '退款流水号',
            '1688拍单号',
            '异常单号',
            '退货物流单号',
            '轨迹状态',
            '申请时间',
            '收款时间',
            '收款人',
            '收款备注',
            '收款账号简称',
            '收款账号'
        ];
        foreach($heads as &$v){
            $v = iconv('UTF-8','GBK//IGNORE',$v);
        }
        fputcsv($fp,$heads);

        $limit = 1000;
        $page = 1;

        do {
            $offsets = ($page - 1) * $limit;
            $is_last = true;

            $result  = $this->Offline_receipt_model->get_offline_receipt_list($params,$offsets, $limit,$page);
            if(!isset($result['values']) or count($result['values']) == 0){
                $is_last = false;
                break;
            }
            $values = $result['values'];

            foreach($values as $value){
                try {
                    $row_list = [];
                    $row = [
                        $value['refund_number'],
                        $value['refund_status_cn'],
                        $value['supplier_name'],
                        $value['supplier_code'],
                        $value['purchase_number_multi'],
                        $value['purchase_name_cn'],
                        $value['compact_number_multi'],
                        $value['statement_number'],
                        $value['apply_user_name'],
                        $value['apply_notice'],
                        $value['refund_type'],
                        $value['refund_channel_cn'],
                        $value['refund_reason'],
                        $value['refund_time'],
                        $value['refund_price'],
                        $value['refund_water_number'],
                        $value['pai_number'],
                        $value['abnormal_number'],
                        $value['refund_log_number'],
                        $value['status'],
                        $value['apply_time'],
                        $value['receipt_time'],
                        $value['receipt_user_name'],
                        $value['receipt_notice'],
                        $value['receipt_account_short'],
                        $value['receipt_account_number'],
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
                }catch (Exception $e){}
            }

            $page ++;

        } while ($is_last);


        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=get_export_path_replace_host(get_export_path('offline_receipt'),$down_host).$template_file;

        $this->success_json($down_file_url);

    }
}    
