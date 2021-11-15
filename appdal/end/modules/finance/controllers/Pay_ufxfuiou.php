<?php
/**
 * Created by PhpStorm.
 * User: totoro
 * Date: 2020-04-16
 * Time: 14:05
 */

class Pay_ufxfuiou extends  MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('Purchase_order_pay_ufxfuiou_model','pay_ufxfuiou');
    }

    /**
     * 列表数据
     */
    public function ufxfuiou_list(){
        $get   = gp();
        $page  = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if(empty($page) or $page < 0)
            $page = 1;
        $limit   = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->pay_ufxfuiou->get_ufxfuiou_pay_list($get, $offsets, $limit, $page);
        $this->success_json($data_list);
    }

    /**
     *  富友列表导出
     */
    public function ufxfuiou_export(){
        $get           = gp();
        $data_list     = $this->pay_ufxfuiou->get_ufxfuiou_pay_list($get, 1, 1, 1);
        $total         = $data_list['paging_data']['total'];
        $template_file = 'fuyou_'.date('YmdHis').mt_rand(1000, 9999).'.csv';
        if($total > 100000){//一次最多导出10W条
            $template_file = 'fuyou_pay.csv';
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
                $data   = $this->pay_ufxfuiou->get_ufxfuiou_pay_list($get, $offset, $per_page, $i);
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
     * 获取富友审核数据
     */
    public function fuyou_batch_info(){
        $ids = $this->input->get_post('ids');//请求参数
        if(empty($ids)){
            $this->error_json('参数id 不能为空');
        }
        $ids = explode(',', $ids);
        if(count($ids) > 20){
            $this->error_json('批量支付不能超过20个');
        }
        //判断付款状态
        $res = $this->pay_ufxfuiou->fuyou_status($ids);
        if(!$res['code']){
            $this->error_json($res['msg']);
        }
        //返回数据
        $reslut = $this->pay_ufxfuiou->fuyou_batch_info($ids);
        $this->success_json($reslut);
    }
    /**
     * 富友支付
     */
    public function ufxfuiou_pay(){
        //POST提交情况下
        if(IS_POST){
            $ids = $this->input->get_post('ids');//请求参数
            $trans_summary = $this->input->get_post('remark');//请求参数
            if(empty($ids)){
                $this->error_json('参数id，不能为空');
            }
            $ids    = explode(',', $ids);
            $res = $this->pay_ufxfuiou->fuyou_status($ids);
            if(!$res['code']){
                $this->error_json($res['msg']);
            }
            $reslut = $this->pay_ufxfuiou->fuyou_pay($ids,$trans_summary);
            $this->success_json($reslut);
        }else{
            $this->error_json('请求方式为POST');
        }
    }

    /**
     * 富友驳回
     */
    public function ufxfuiou_pay_reject(){
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
            $reslut = $this->pay_ufxfuiou->ufxfuiou_pay_reject($ids, $trans_summary);
            $this->success_json($reslut);
        }else{
            $this->error_json('请求方式为POST');
        }
    }

}