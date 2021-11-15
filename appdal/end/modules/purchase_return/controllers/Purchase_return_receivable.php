<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020/3/14
 * Time: 14:25
 */

class Purchase_return_receivable extends MY_Controller{


    public function __construct(){
        parent::__construct();
        $this->load->model('Purchase_return_receivable_model','receivable');
        $this->load->helper('status_order');
    }
    /**
     * 退货跟踪应收列表
     */
    public function get_list(){

        $return_status = $this->input->get_post('return_status');
        $supplier_code = $this->input->get_post('supplier_code');
        $upload_time_start = $this->input->get_post('upload_time_start');
        $upload_time_end = $this->input->get_post('upload_time_end');
        $freight_payment_type = $this->input->get_post('freight_payment_type');
        $refund_status = $this->input->get_post('refund_status');
        $return_number = $this->input->get_post('return_number');
        $upload_screenshot_user = $this->input->get_post('upload_screenshot_user');
        $colletion_user = $this->input->get_post('colletion_user');
        $refund_number = $this->input->get_post('refund_number');
        $colletion_time_start = $this->input->get_post('colletion_time_start');
        $colletion_time_end = $this->input->get_post('colletion_time_end');
        $refund_time_start  = $this->input->get_post('refund_time_start');
        $refund_time_end  = $this->input->get_post('refund_time_end');





        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');

        if(empty($page)  or $page < 0 )  $page  = 1;
        if(empty($limit) or $limit < 0 ) $limit = 20;
        $offset        = ($page - 1) * $limit;

        $params = array(
            'return_status'=> $return_status,
            'supplier_code'=> $supplier_code,
            'freight_payment_type'=> $freight_payment_type,
            'upload_time_start'=> $upload_time_start,
            'upload_time_end'=> $upload_time_end,
            'refund_status' => $refund_status,
            'return_number' => $return_number,
            'upload_screenshot_user' => $upload_screenshot_user,
            'colletion_user' => $colletion_user,
            'refund_number' => $refund_number,
            'colletion_time_start'=>$colletion_time_start,
            'colletion_time_end'=>$colletion_time_end,
            'refund_time_start'=>$refund_time_start,
            'refund_time_end'=>$refund_time_end,



        );
        $result = $this->receivable->get_return_receivable_list($params,$offset,$limit,$page);
        $this->success_json($result['data_list'], $result['page_data']);
    }
    /**
     * 获取详情
     */
    public function get_return_receivable_items(){
        $return_number = $this->input->get_post('return_number');
        if(empty($return_number)) {
            $this->error_json('退货单号必填!');
        }
        $this->load->model('Purchase_return_tracking_model','purchase_return');
        $this->load->model('finance/payment_order_pay_model');
        $this->data['refund_list'] = $this->purchase_return->get_refund_flow_info($return_number);
        $this->data['storage_collection'] = $this->purchase_return->get_storage_collection_info($return_number);
        $this->data['account_short'] = $this->payment_order_pay_model->get_bank(); //收款账号

        //支付账号
        $this->success_json($this->data);
    }

    /**
     * 待财务收款
     */
    public function click_receivables(){
        $return_number = $this->input->get_post('return_number');
        $type =  $this->input->get_post('type'); //1  收款 2 驳回
        $colletion_remark = $this->input->get_post('colletion_remark');

          $our_branch_short = $this->input->get_post('our_branch_short');
          if (!empty($our_branch_short)) {
              $account_info = [
                  'our_branch_short'=>$our_branch_short,
                  'our_branch'=>$this->input->get_post('our_branch'),
                  'our_account'=>$this->input->get_post('our_account'),
                  'our_account_holder'=>$this->input->get_post('our_account_holder'),
              ];
          } else {
              $account_info = [];
          }
          if(empty($return_number)) {
            $this->error_json('退货单号必填!');
          }
          if(empty($colletion_remark)) {
                $this->error_json('收款备注必填!');
          }
          if(empty($type)){
                $this->error_json('参数错误!');
          }
        //判断状态是否是待财务付款
        if ($type == 1&&empty($our_branch_short)) {
            $this->error_json('账号简称必填');

        }


//       if(empty($type) or empty($colletion_remark) or empty($return_number)){ $this->error_json('参数错误!');}
        $result = $this->receivable->click_receivables($return_number,$colletion_remark,$type,$account_info);
        if($result['success']){
            $this->success_json([], null, '操作成功');
        }else{
            $this->error_json('操作失败');
        }
    }

    /**
     * 导出
     */
    public function export_receivables_list(){
        $return_status = $this->input->get_post('return_status');
        $supplier_code = $this->input->get_post('supplier_code');
        $upload_time_start = $this->input->get_post('upload_time_start');
        $upload_time_end = $this->input->get_post('upload_time_end');
        $freight_payment_type = $this->input->get_post('freight_payment_type');
        $refund_status = $this->input->get_post('refund_status');
        $return_number = $this->input->get_post('return_number');
        $upload_screenshot_user = $this->input->get_post('upload_screenshot_user');
        $colletion_user = $this->input->get_post('colletion_user');
        $refund_number =  $this->input->get_post('refund_number');

        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');


        if(empty($page)  or $page < 0 )  $page  = 1;
        if(empty($limit) or $limit < 0 ) $limit = 20;
        $offset        = ($page - 1) * $limit;
        $params = array(
            'return_status'=> $return_status,
            'supplier_code'=> $supplier_code,
            'freight_payment_type'=> $freight_payment_type,
            'upload_time_start'=> $upload_time_start,
            'upload_time_end'=> $upload_time_end,
            'refund_status' => $refund_status,
            'return_number' => $return_number,
            'upload_screenshot_user' => $upload_screenshot_user,
            'colletion_user' => $colletion_user,
            'refund_number' => $refund_number
        );

        $total = $this->receivable->export_sum($params);
        $template_file = 'return_receivable_'.date('YmdHis').mt_rand(1000,9999).'.csv';
        if($total>100000){//一次最多导出10W条
            $template_file = 'return_receivable.csv';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }
        //前端路径
        $webfront_path = dirname(dirname(APPPATH));
        $product_file = $webfront_path.'/webfront/download_csv/'.$template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $is_head = false;
        $fp = fopen($product_file, "a");
        if($total>0){
            $per_page = 20;
            $total_page = ceil($total/$per_page);
            for($i = 1;$i<= $total_page;$i++){
                $offset = ($i - 1) * $per_page;
                $data = $this->receivable->export_receivables_list($params,$offset,$per_page);

                if(!empty($data['value'])){
                    foreach ($data['value'] as $key => $value){
                        $row =[
                            $value['return_number'],
                            $value['main_number'],
                            $value['supplier_name'],
                            $value['supplier_code'],
                            $value['refund_product_cost'],
                            $value['act_freight'],
                            $value['freight_payment_type'],
                            $value['refundable_amount'],
                            $value['act_refund_amount'],
                            $value['refund_serial_number'],
                            $value['return_status'],
                            $value['upload_screenshot_user_name'],
                            $value['upload_screenshot_time'],
                            '',
                            $value['colletion_time'],
                            $value['colletion_user_name'],
                            $value['colletion_remark'],
                            $value['upload_screenshot_time'],
                            $value['refund_time_list'],
                            $value['diff_amount']
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
                        if($is_head === false){
                            $heads=['退货单号','申请ID','供应商名称','供应商代码','退货产品成本','实际运费','运费支付方','应退款金额','实际退款金额','退款流水号','收款状态'
                                ,'提交人','提交时间','申请备注','收款时间','收款人','收款备注','提交截图时间','实际退款时间','差异金额'];
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
                }
            }
        }
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url= $down_host.'download_csv/'.$template_file;
        $this->success_json($down_file_url);
    }
    //修改收款账号
     public function modify_receiving_account()
     {
         $params = gp();
         list($status,$msg) = $this->receivable->modify_receiving_account($params);
         if ($status) {
             $this->success_json('更新成功');

         } else {
             $this->error_json($msg);

         }

     }


}