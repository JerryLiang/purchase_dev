<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 虚拟入库控制器
 * User: Dean
 * Date: 2020/11/28 10:00
 */

class Virtual_storage extends MY_ApiBaseController{
    public function __construct(){
        parent::__construct();
        $this->load->model('abnormal/Virtual_storage_model','Virtual_storage_model');
        $this->_modelObj = $this->Virtual_storage_model;
    }

    /**
     * 多货列表
     * @author Dean 2019/01/17
     */
    public function get_storage_list()
    {

        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_storage_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }








    /**
     * 退货信息保存
     * @author Jaxton 2019/01/17
     */
    public function batch_audit_storage_order()
    {

        try {
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->batch_audit_storage_order($params);
            $this->sendData($data);
        } catch (Exception $e) {


            $this->sendError($e->getCode(), $e->getMessage());
        }



    }


    /**
     * 查看调拨列表
     * @author Jaxton 2019/01/17
     */
    public function view_storage_detail()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->view_storage_detail($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }



    /**
     * 查看日志
     * @author Jaxton 2019/01/17
     */
    public function view_log()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->view_log($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }











}