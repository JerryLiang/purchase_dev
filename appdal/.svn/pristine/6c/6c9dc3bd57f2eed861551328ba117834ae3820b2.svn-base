<?php
/**
 * 操作日志控制器
 * User: Jaxton
 * Date: 2019/01/23
 */

class Operator_log extends MY_Controller{
	public function __construct(){
        parent::__construct();
        $this->load->model('operator_log_model');
    }

    /**
    * 日志概况
    * /system/Operator_log/get_compact
	* @author Jaxton 2018/01/23
    */
	public function get_operator_log_survey(){
		$result=$this->operator_log_model->get_operator_log_survey();
	}

	/**
    * 日志查询
    * /system/Operator_log/get_query_log
	* @author Jaxton 2018/01/23
    */
    public function get_query_log(){
    	$params=[
    		'record_type' => $this->input->get_post('record_type'),//操作类型
    		'operator' => $this->input->get_post('operator'),//操作用户
    		'operate_time_start' => $this->input->get_post('operate_time_start'),//开始时间
    		'operate_time_end' => $this->input->get_post('operate_time_end'),//截止时间
    		'operate_ip' => $this->input->get_post('operate_ip'),//操作人IP
    		'content' => $this->input->get_post('content')
    	];

    	$page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        if(empty($limit) or $limit < 0 ) $limit = 300;
        $offset        = ($page - 1) * $limit;

        $result=$this->operator_log_model->get_operator_log_survey($params,$limit,$offset);
        print_r($result);
    }

    /**
    * 日志删除
    * /system/Operator_log/delete_log
	* @author Jaxton 2018/01/23
    */
    public function delete_log(){
    	$id=$this->input->get_post('id');
    	if(empty($id)){
            $this->error_json('缺少ID,请选择');
    	}
    	$id_arr=explode(',', $id);
    	$result=$this->operator_log_model->delete_log($id_arr);
    	if($result){
    		$return_data=response_format(1,[],'删除成功');
    	}else{
    		$return_data=response_format(0,[],'删除失败');
    	}
    	return $return_data;
    }

    /**
    * 日志清空
    * /system/Operator_log/empty_log
	* @author Jaxton 2018/01/23
    */
    public function empty_log(){
    	$result=$this->operator_log_model->empty_log();
    	if($result){
    		$return_data=response_format(1,[],'操作成功');
    	}else{
    		$return_data=response_format(0,[],'操作失败');
    	}
    	return $return_data;
    }

}