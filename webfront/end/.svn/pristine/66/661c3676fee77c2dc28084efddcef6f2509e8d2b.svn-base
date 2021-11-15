<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 预测计划单控制器
 * User: Jaxton
 * Date: 2019/01/03 17:20
 */

class Forecast_plan extends MY_ApiBaseController{

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase_suggest/Forecast_plan_model','forecast');
        //$this->load->model('Forecast_feedback_model','feedback');
        $this->_modelObj = $this->forecast;
    }

    /**
    * 获取预测单列表
    * @author Jaxton 2019-1-4
    * /purchase_suggest/forecast_plan/get_forecast_list
    */
    public function get_forecast_list(){
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        
        $data = $this->_modelObj->get_forecast_list_all($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
    * 反馈操作
    * @author Jaxton 2019-1-4
    */
    public function add_feedback(){
        $params = $this->_requestParams;
        
        $data = $this->_modelObj->add_feedback($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
    * 获取反馈记录
    * @author Jaxton 2019-1-4
    */
    public function get_feedback_list(){
        $params = $this->_requestParams;
        
        $data = $this->_modelObj->get_feedback_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

}