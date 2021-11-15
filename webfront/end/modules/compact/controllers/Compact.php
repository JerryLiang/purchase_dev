<?php

require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 合同控制器
 * User: Jaxton
 * Date: 2019/01/08 10:00
 */

class Compact extends MY_ApiBaseController{
	public function __construct(){
        parent::__construct();
        //$this->load->model('Compact_list_model','compact_model');
    }


    /**
    * 获取合同列表
    * /compact/compact/get_compact
	* @author Jaxton 2018/01/08
    */
    public function get_compact(){
    	$params=[
    		'compact_number' => $this->input->get_post('compact_number'),//合同号
    		'create_user_id' => $this->input->get_post('create_user_id'),//创建人
    		'supplier_id' => $this->input->get_post('supplier_id'),//供应商
    		'create_time_start' => $this->input->get_post('create_time_start'),//创建时间开始
    		'create_time_end' => $this->input->get_post('create_time_end') //创建时间截止
    	];

    	$page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        if(empty($limit) or $limit < 0 ) $limit = 50;
        $offset        = ($page - 1) * $limit;

        $curlRequest = CurlRequest::getInstance();
        $result = $curlRequest->cloud_post('http://192.168.71.170:85/compact/compact/get_compact',$params);
        print_r($result);
        //$result = $this->_curl_request->cloud_post('/compact/compact/get_compact',$params);
	    // $result=$this->compact_model->get_compact_list($params,$offset,$limit);   
     //    $result['data_list']['value']=$this->compact_model->formart_compact_list($result['data_list']['value']);
	    // $return_data = response_format(1,$result['data_list'],'',$result['page_data']);      
     //    http_response($return_data);
    }

    /**
    * 获取合同详情
    * /compact/compact/get_compact_detail
    * @author Jaxton 2018/01/08
    */
    public function get_compact_detail(){
    	$compact_number=$this->input->get_post('compact_number');
    	if(empty($compact_number)){
    		http_response(response_format(0,[],'缺少合同编号'));
    	}
    	$result=$this->compact_model->get_compact_detail($compact_number);
    	$return_data = response_format(1,$result,'');  
        http_response($return_data);
    }

    /**
    *测试
    */
    public function test(){
        // $a=$this->compact_model->get_supplier_down_box();
        // print_r($a);
        //print_r($this->compact_model->get_sku_cancel_number('PO00000023','CW00009')) ;
    	echo UPLOAD_DOMAIN;die;
    	//echo SUPR_DAL_API_HOST;die;
    	//echo "<input type='file'>";
  //   	echo '<form action="/index.php/compact/compact/upload_compact_file" method="post" enctype="multipart/form-data">
		//      <input type="file" class="compact_file" name="compact_file" />
		//      <input type="hidden" name="pop_id" value="1">
		//      <input type="hidden" name="pc_id" value="1">
		//      <button type="submit" class="but1">上传</button>
		// </form>';
    }

    /**
    * 上传合同扫描件
    * @author Jaxton 2018/01/09
    * /compact/compact/upload_compact_file
    */
    public function upload_compact_file(){
    	//print_r($_FILES);die;
    	$pop_id=$this->input->get_post('pop_id');//请款单ID
    	$pc_id=$this->input->get_post('pc_id');//合同ID
    	if(empty($pop_id) || empty($pc_id)){
    		http_response(response_format(0,[],'缺少ID'));
    	}
    	if(empty($_FILES['compact_file']['name'])){
    		
    		http_response(response_format(0,[],'请选择文件上传'));
    	}else{
    		$upload_file=$_FILES['compact_file'];
    	}
    	$result=$this->compact_model->upload_compact_file($pop_id,$pc_id,$upload_file);
    	//print_r($result);
    	if($result['success']){
    		$error_code=1;
    		$error_msg='操作成功';
    	}else{
    		$error_code=0;
    		$error_msg='操作失败';
    	}
    	http_response(response_format($error_code,[],$error_msg));
    	
    }

    /**
    * 查看文件
    * @author Jaxton 2018/01/10
    */
    public function see_compact_file(){
    	$pop_id=$this->input->get_post('pop_id');
    	$pc_id=$this->input->get_post('pc_id');
    	if(empty($pop_id) || empty($pc_id)){
    		http_response(response_format(0,[],'缺少ID'));
    	}
        $result    = $this->compact_model->see_compact_file($pop_id, $pc_id);
        $file_data = [];
        $error_msg = '';
    	if($result){
    		$error_code=1;
    		$file_data['file_path']=$result['file_path'];
    	}else{
    		$error_code=0;
    		$error_msg='获取文件失败';
    	}
    	http_response(response_format($error_code,$file_data,$error_msg));
    }

    /**
    * 下载文件
    * @author Jaxton 2018/01/10
    * /compact/compact/download_compact_file
    */
    public function download_compact_file(){
    	$this->load->library('file_operation');
    	$pop_id=$this->input->get_post('pop_id');
    	$pc_id=$this->input->get_post('pc_id');
    	if(empty($pop_id) || empty($pc_id)){
    		http_response(response_format(0,[],'缺少ID'));
    	}
    	$result=$this->compact_model->see_compact_file($pop_id,$pc_id);
    	if($result){

    		$file=UPLOAD_DOMAIN.$result['file_path'];
			if(!$this->file_operation->download_file($file)){
				http_response(response_format(0,[],'获取文件失败'));
			};
    	}else{
    		http_response(response_format(0,[],'此文件不存在'));
    	}
    	

    }

    /**
    *pdf——test
    */
    public function pdf_test(){
        $this->load->model('print_pdf_model');
        $this->print_pdf_model->print_pdf('<h1>test</h1>');
    }


}