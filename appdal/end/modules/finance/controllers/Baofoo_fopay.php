<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Baofoo_fopay extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('finance/Baofoo_fopay_model');
        //   $this->load->helper('status_order');
    }


    /**
     * 待宝付审核列表
     * @author harvin
     * @date   2018/8/9
     * /Baofoo_fopay/baofoo_list
     */
    public function baofoo_list(){
        $get   = gp();
        $page  = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if(empty($page) or $page < 0)
            $page = 1;
        $limit   = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;

        $data_list = $this->Baofoo_fopay_model->get_baofoo_list($get, $offsets, $limit, $page);
        $this->success_json($data_list);
    }

    /**
     * 宝付提交
     * @author harvin
     * @date   2019/8/8
     * /Baofoo_fopay/baofoo_submission
     */
    public function baofoo_submission(){
        //POST提交情况下

        $lock_success_list = [];
        if(IS_POST){
            $post                         = gp();
            $account_short                = $post['account_short'];

            if (empty($post['ids'])) {
                $this->error_json('参数不存在');
            }
            $ids = explode(',', $post['ids']);
            if (empty($ids)) {
                $this->error_json('参数 ids 为空');
            }

            try{
                $this->load->model('finance/Payment_order_contract_pay_model');
                $this->Payment_order_contract_pay_model->get_supplier_ststus($ids, true);
            }catch(Exception $exc){
                $this->error_json($exc->getMessage());
            }

            foreach($ids as $id){
                $session_key = 'v924_order_pay_' . $id;
                if (!$this->rediss->getData($session_key)) {
                    $this->rediss->setData($session_key, '1', 60); //设置缓存和有效时间
                    $lock_success_list[] = $id;
                } else {
                    foreach($lock_success_list as $order_pay_id){
                        $session_key = 'v924_order_pay_' . $order_pay_id;
                        $this->rediss->deleteData($session_key);
                    }
                    $this->error_json('请款单号 id（' . $id . ' ）已在处理中，请稍后');
                }
            }


            $faoPayDatas['ids']           = $post['ids'];
            $faoPayDatas['charge']        = $post['charge'] == 0 ? "00" : '01';      //是否手续费
            $faoPayDatas['to_acc_name']   = $post['account_name']; //收款方名称
            $faoPayDatas['trans_card_id'] = $post['id_number']; //收款方证件号 
            $faoPayDatas['to_bank_name']  = $post['payment_platform_branch']; //支行号(或支行名称)
            $faoPayDatas['to_acc_no']     = $post['account']; //收款账号
            if($post['charge'] == 1){// 我方承担手续费
                $post['total_pay_price'] = $post['total_pay_price']-0.6;
                $procedure_party = PAY_PROCEDURE_PARTY_A;// 甲方
                $procedure_fee   = 0.6;
            }else{
                $procedure_party = PAY_PROCEDURE_PARTY_B;// 乙方
                $procedure_fee   = 0.6;
            }

            $faoPayDatas['trans_money']   = $post['total_pay_price']; //转账金额
            $faoPayDatas['trans_mobile']  = $post['phone_number']; //收款方手机号
            $faoPayDatas['remark']        = $post['remark']; //付款备注
            $reslut                       = $this->Baofoo_fopay_model->pay_faofoo_edit($faoPayDatas,$procedure_party,$procedure_fee,$account_short);
            if($reslut['code']){
                $this->success_json([], null, $reslut['message']);
            }else{
                $this->error_json($reslut['message']);
            }
        }else{
            $this->error_json(null, '请求方式为POST', false);
        }

    }

    /**
     * 宝付支付批量（显示）
     * @author harvin
     * @date   2019/8/9
     * /Baofoo_fopay/baofoo_batch_info
     */
    public function baofoo_batch_info(){
        $ids = $this->input->get_post('ids');//请求参数
        if(empty($ids)){
            $this->error_json('参数id 不能为空');
        }
        $ids = explode(',', $ids);
        if(count($ids) > 20){
            $this->error_json('批量支付不能超过20个');
        }
        //判断付款状态
        $res = $this->Baofoo_fopay_model->get_baofoopay_status($ids);
        if(!$res['code']){
            $this->error_json($res['msg']);
        }

        //返回数据
        $reslut = $this->Baofoo_fopay_model->get_baofoop_total($ids);
        $this->success_json($reslut);
    }

    /**
     * 宝付在线付款
     * @author harvin
     * @date   2019/8/6
     * /Baofoo_fopay/baofoo_fopay
     */
    public function baofoo_fopay(){
        //POST提交情况下
        if(IS_POST){
            $post          = gp();
            $ids           = isset($post['ids']) ? $post['ids'] : '';
            $trans_summary = isset($post['remark']) ? $post['remark'] : '';
            if(empty($ids)){
                $this->error_json('参数id，不能为空');
            }
            $ids    = explode(',', $ids);
            $reslut = $this->Baofoo_fopay_model->pay_faofoo_save($ids, $trans_summary);
            $this->success_json($reslut);
        }else{
            $this->error_json('请求方式为POST');
        }
    }

    /**
     * 宝付驳回操作
     * @author harvin
     * @date   2019/8/10
     * /Baofoo_fopay/baofoo_fopay_reject
     */
    public function baofoo_fopay_reject(){
        //POST提交情况下
        if(IS_POST){
            $post          = gp();
            $ids           = isset($post['ids']) ? $post['ids'] : '';
            $trans_summary = isset($post['remark']) ? $post['remark'] : '';
            if(empty($ids)){
                $this->error_json('参数id，不能为空');
            }
            if(empty($trans_summary)){
                $this->error_json('驳回请填写备注');
            }
            $ids    = explode(',', $ids);
            $reslut = $this->Baofoo_fopay_model->pay_faofoo_reject($ids, $trans_summary);
            if($reslut['code']){
                $this->success_json([], null, $reslut['message']);
            }else{
                $this->error_json($reslut['message']);
            }
        }else{
            $this->error_json('请求方式为POST');
        }
    }

    /**
     * 宝付导出
     * @author harvin
     * @date   2019/8/12
     */
    public function boofoo_fopay_export(){
        $get           = gp();
        $data_list     = $this->Baofoo_fopay_model->get_baofoo_list($get, 1, 1, 1);
        $total         = $data_list['paging_data']['total'];
        $template_file = 'baofo_'.date('YmdHis').mt_rand(1000, 9999).'.csv';
        if($total > 100000){//一次最多导出10W条
            $template_file = 'baofo_pay.csv';
            $down_host     = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url = $down_host.'download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }
        //前端路径
        $webfront_path = dirname(dirname(APPPATH));
        $product_file  = $webfront_path.'/webfront/download_csv/'.$template_file;
        if(file_exists($product_file)){
            unlink($product_file);
        }
        fopen($product_file, 'w');
        $is_head = false;
        $fp      = fopen($product_file, "a");
        if($total > 0){
            $per_page   = 20;
            $total_page = ceil($total / $per_page);
            for($i = 1; $i <= $total_page; $i++){
                $offset = ($i - 1) * $per_page;
                $data   = $this->Baofoo_fopay_model->get_baofoo_list($get, $offset, $per_page, $i);
                if(!empty($data['values'])){
                    //组装需求数据格式
                    foreach($data['values'] as $key => $value){
                        $row = [
                            $value['audit_status'], $value['pur_number'], $value['supplier_name'], $value['pay_price'], $value['to_acc_name']."\t",
                            $value['to_bank_name'], $value['to_acc_no'], $value['trans_card_id'], $value['trans_mobile'], $value['drawee'], $value['drawee_time'], $value['applicant'], $value['applicant_time'],
                            "申请备注：".$value['remark']."\t\n"."审核备注:".$value['trans_summary'],
                        ];
                        foreach($row as $vvv){
                            if(preg_match("/[\x7f-\xff]/", $vvv)){
                                $vvv = stripslashes(iconv('UTF-8', 'GBK//IGNORE', $vvv));//中文转码
                            }
                            if(is_numeric($vvv) && strlen($vvv) > 9){
                                $vvv = $vvv."\t";//避免大数字在csv里以科学计数法显示
                            }
                            $row_list[] = $vvv;
                        }
                        if($is_head === false){
                            $heads = [
                                '审核状态', '合同号', '供应商名称', '转账金额', '收款名称', '开户行名称', '收款账号', '收款人身份证', '收款人手机号', '审核人',
                                '审核时间', '提交人', '提交时间', '备注'
                            ];
                            foreach($heads as &$m){
                                $m = iconv('UTF-8', 'GBK//IGNORE', $m);
                            }
                            fputcsv($fp, $heads);
                            $is_head = true;
                        }
                        fputcsv($fp, $row_list);
                        unset($row_list);
                        unset($row);

                    }
                    ob_flush();
                    flush();
                    usleep(100);
                }
            }
        }
        $down_host     = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url = $down_host.'download_csv/'.$template_file;
        $this->success_json($down_file_url);
    }

    /**
     * 刷新宝付状态
     */
    public function update_pay_baofoo_status(){
        $pur_tran_num  = $this->input->get_post('pur_tran_num');
        if(empty($pur_tran_num)){
            $this->error_json('付款流水号不允许为空');
        }
        $debug = $this->input->get_post('debug');
        if (!is_array($pur_tran_num)) {
            $pur_tran_num = explode(',', $pur_tran_num);
        }
        if(!empty($pur_tran_num)){
            foreach ($pur_tran_num as $value){
                $this->Baofoo_fopay_model->pay_baofoo_voucher_status($value,$debug);
            }
           $this->success_json('刷新成功!');
        }
    }

}
