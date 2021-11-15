<?php
/**
 * 发票清单控制器
 * User: Jaxton
 * Date: 2019/01/10 18:00
 */

class Invoice_list extends MY_Controller{
	public function __construct(){
        parent::__construct();
        $this->load->model('Invoice_list_model','invoice_list_model');
        // $this->load->model('forecast_feedback_model','feedback');
    }

    /**
    * 批量开票弹出“发票维护”界面数据
    * /purchase/invoice_list/btach_invoice_list
    * @author Jaxton 2019-1-11
    */
    public function btach_invoice_list(){
        $invoice_number = $this->input->get_post('invoice_number');
        if(empty($invoice_number)){
            $this->error_json('发票清单号不能为空！');
        }

        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;
        $result = $this->invoice_list_model->get_invoice_detail_list($invoice_number,$limit,$offset);
        if($result['success']){
            $this->success_json($result['data'],$result['page_data'],$result['error_msg']);
    	}else{
            $this->error_data_json($result['data'],$result['error_msg']);
    	}
    }
    /**
    * /purchase/invoice_list/btach_invoice_submit
    * 批量开票提交
    * @author Jaxton 2019-1-11
    */
    public function btach_invoice_submit(){
    	$invoice_code_data = $this->input->get_post('invoice_code_data'); 
    	if(empty($invoice_code_data)){
            $this->error_json('请填写信息');
    	}
    	
    	$result=$this->invoice_list_model->btach_invoice_submit($invoice_code_data);

    	if($result['success']){
            $this->success_json([],null,'开票成功');
    	}else{
            $this->error_json($result['error_msg']);
    	}
    }

    /**
    * /purchase/invoice_list/invoice_finance_review
    * 财务审核
    * @author Jaxton 2019-1-12
    */
    public function invoice_finance_review(){
    	$invoice_number=$this->input->get_post('invoice_number');
    	$remark=$this->input->get_post('remark');
    	$review_result=$this->input->get_post('review_result');
    	if(empty($invoice_number)){
            $this->error_json('发票清单号不能为空！');
    	}
    	if(empty($review_result)){
            $this->error_json('审核结果不能为空！');
    	}
    	$result=$this->invoice_list_model->invoice_finance_review($invoice_number,$remark,$review_result);
    	if($result['success']){
            $this->success_json([],null,'审核成功');
    	}else{
            $this->error_json($result['error_msg']);
    	}
    }

    /**
    * /purchase/invoice_list/download_invoice_detail
    * 下载发票明细
    * @author Jaxton 2019-1-12
    */
    public function download_invoice_detail(){
    	$invoice_number=$this->input->get_post('invoice_number');
    	if(empty($invoice_number)){
            $this->error_json('发票清单号不能为空！');
    	}
    	$result=$this->invoice_list_model->get_download_invoice_detail($invoice_number);

    	if($result['success']){
            $this->success_json($result['data'],null,$result['error_msg']);
    	}else{
            $this->error_data_json($result['data'],$result['error_msg']);
    	}
    }

}