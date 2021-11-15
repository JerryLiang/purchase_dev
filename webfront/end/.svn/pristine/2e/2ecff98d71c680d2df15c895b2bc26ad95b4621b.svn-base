<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 报损信息控制器
 * User: Jolon
 * Date: 2020/03/03 10:00
 */

class Report_loss_unarrived_advance extends MY_ApiBaseController{

	public function __construct(){
        parent::__construct();
        $this->load->model('abnormal/Report_loss_unarrived_advance_model','Report_loss_unarrived_advance_model');
        $this->_modelObj = $this->Report_loss_unarrived_advance_model;
    }

    /**
    * 获取取消未到货转报损数据
    * @author Jolon 2020/03/03
    */
    public function get_unarrived_to_loss(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_unarrived_to_loss($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 保存取消未到货转报损数据
     * @author Jolon 2020/03/03
     */
    public function set_unarrived_to_loss(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->set_unarrived_to_loss($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 获取报损转取消未到货数据
     * @author Jolon 2020/03/03
     */
    public function get_loss_to_unarrived(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_loss_to_unarrived($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 保存报损转取消未到货数据
     * @author Jolon 2020/03/03
     */
    public function set_loss_to_unarrived(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->set_loss_to_unarrived($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
}