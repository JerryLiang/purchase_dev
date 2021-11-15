<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 发票清单控制器
 * User: Jaxton
 * Date: 2019/03/14 15:00
 */

class Audit_amount extends MY_ApiBaseController{
	public function __construct(){
        parent::__construct();
        $this->load->model('purchase/Audit_amount_model');
        $this->_modelObj = $this->Audit_amount_model;
    }

    /**
    * 获取列表
    * /purchase/audit_amount/index
	* @author Jaxton 2019/03/13
    */
    public function index(){
    	$this->_init_request_param("GET");
    	$params = $this->_requestParams;
        
        $data = $this->_modelObj->get_audit_amount_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);        
    }

    /**
    * 修改金额
    * /purchase/audit_amount/update_amount
	* @author Jaxton 2019/03/13
    */
    public function update_amount(){
    	$params = $this->_requestParams;
        $data = $this->_modelObj->update_amount($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);        
    }
}

