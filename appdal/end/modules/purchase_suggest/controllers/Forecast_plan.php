<?php
/**
 * 预测计划单控制器
 * User: Jaxton
 * Date: 2019/01/03 17:20
 */

class Forecast_plan extends MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('Forecast_plan_model','forecast');
        $this->load->model('Forecast_feedback_model','feedback');
    }

    /**
    * 获取预测单列表
    * @author Jaxton 2019-1-4
    * /purchase_suggest/forecast_plan/get_forecast_list
    */
    public function get_forecast_list(){
    	$params = [
    		'sku' => $this->input->get_post('sku'),
    		'buyer_id' => $this->input->get_post('buyer_id'),
    		'supplier_code' => $this->input->get_post('supplier_code'),
    		'product_line_id' => $this->input->get_post('product_line_id'),
    		'warehouse_code' => $this->input->get_post('warehouse_code'),
            'is_drawback' => $this->input->get_post('is_drawback'),
    	];
    	$page_data=$this->format_page_data();
    	$result=$this->forecast->get_forecast_list_all($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $result['data_list']['value']=$this->forecast->format_forecast_list($result['data_list']['value']);
        $this->success_json($result['data_list'],$result['paging_data']);
    }

    /**
    * 反馈操作
    * @author Jaxton 2019-1-4
    */
    public function add_feedback(){
    	$feedback_str=$this->input->get_post('feedback_str');//反馈内容
    	$suggest_id=$this->input->get_post('suggest_id');//预测单ID
    	if(empty($feedback_str) || empty($suggest_id)){
            $this->error_json('预测单ID和反馈内容不可缺少');
    	}
    	$result=$this->feedback->add_feedback($feedback_str,$suggest_id);
    	if($result){
    	    $this->success_json([],null,'操作成功');
    	}else{
    	    $this->error_json('操作失败');
    	}
    }

    /**
    * 获取反馈记录
    * @author Jaxton 2019-1-4
    */
    public function get_feedback_list(){
    	$suggest_id=$this->input->get_post('suggest_id');//预测单ID
    	if(empty($suggest_id)){
            $this->error_json('缺少ID');
    	}
    	$result=$this->feedback->get_feedback_list($suggest_id);
        $this->success_json($result);
    }

}