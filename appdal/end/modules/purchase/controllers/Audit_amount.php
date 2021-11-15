<?php
/**
 * 审核金额配置
 * User: Jaxton
 * Date: 2019/03/13 16:20
 */

class Audit_amount extends MY_Controller{
	public function __construct(){
        parent::__construct();
        $this->load->model('Audit_amount_model');
    }

    /**
    * 获取列表
    * /purchase/audit_amount/index
	* @author Jaxton 2019/03/13
    */
    public function index(){
    	$page_data=$this->format_page_data();
    	$result=$this->Audit_amount_model->get_list($page_data['offset'],$page_data['limit'],$page_data['page']); 
    	$this->success_json($result['data_list'],$result['page_data']);
    }

    /**
    * 修改金额
    * /purchase/audit_amount/update_amount
	* @author Jaxton 2019/03/13
    */
    public function update_amount(){
    	$data=$this->input->get_post('data');
    	// $data='[{"id":"1","auth_name":"信息变更审核","headman_start":0,"headman_end":1000,"director_start":1000,"director_end":20000,"manager_start":20000,"manager_end":200000,"majordomo":200000},{"id":"2","auth_name":"采购单审核","headman_start":0,"headman_end":1000,"director_start":1000,"director_end":20000,"manager_start":20000,"manager_end":200000,"majordomo":200000},{"id":"3","auth_name":"请款页面审核","headman_start":0,"headman_end":1000,"director_start":1000,"director_end":20000,"manager_start":20000,"manager_end":200000,"majordomo":200000},{"id":"4","auth_name":"订单作废审核","headman_start":0,"headman_end":1000,"director_start":1000,"director_end":20000,"manager_start":20000,"manager_end":200000,"majordomo":200000}]';
    	//echo json_encode($data);die;
    	if(empty($data)){
            $this->error_json('缺少参数[data]');
    	}
    	$result=$this->Audit_amount_model->update_amount($data);
    	if($result['success']){
            $this->success_json([],null,'操作成功');
    	}else{
            $this->error_json($result['error_msg']);
    	}
    }

}