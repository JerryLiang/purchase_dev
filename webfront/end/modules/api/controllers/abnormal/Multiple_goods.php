<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 多货列表控制器
 * User: Dean
 * Date: 2019/01/16 10:00
 */

class Multiple_goods extends MY_ApiBaseController{
    public function __construct(){
        parent::__construct();
        $this->load->model('abnormal/Multiple_goods_model','Multiple_goods_model');
        $this->_modelObj = $this->Multiple_goods_model;
    }

    /**
     * 多货列表
     * @author Dean 2019/01/17
     */
    public function multiple_goods_list()
    {

        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->multiple_goods_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 多货退货列表
     * @author Dean 2019/01/17
     */
    public function get_multiple_return_goods()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_multiple_return_goods($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 多货调拨列表
     * @author Dean 2019/01/17
     */
    public function get_transfer_multiple_list()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_transfer_multiple_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 获取退货信息
     * @author Jaxton 2019/01/17
     */
    public function multiple_return_show()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->multiple_return_show($params);

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
    public function multiple_return_save()
    {

        try {
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->multiple_return_save($params);


            $this->sendData($data);
        } catch (Exception $e) {


            $this->sendError($e->getCode(), $e->getMessage());
        }



    }


    /**
     * 获取退货信息
     * @author Dean 2019/01/17
     */
    public function get_return_multiple_info()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_return_multiple_info($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 多货调拨列表
     * @author Dean 2019/01/17
     */
    public function audit_transfer_show()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->audit_transfer_show($params);

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
    public function audit_transfer_order()
    {

        try {
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->audit_transfer_order($params);
            $this->sendData($data);
        } catch (Exception $e) {


            $this->sendError($e->getCode(), $e->getMessage());
        }



    }



    /**
     * 退货信息保存
     * @author Jaxton 2019/01/17
     */
    public function batch_audit_transfer_order()
    {

        try {
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->batch_audit_transfer_order($params);
            $this->sendData($data);
        } catch (Exception $e) {


            $this->sendError($e->getCode(), $e->getMessage());
        }



    }


    /**
     * 查看调拨列表
     * @author Jaxton 2019/01/17
     */
    public function view_transfer_detail()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->view_transfer_detail($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    public function multiple_list_amount_total()
    {

        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->multiple_list_amount_total($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 导出CSV-
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method get
     * @doc
     */
    public function multiple_export_csv()
    {
        try {
            $this->_init_request_param("GET");
            $this->_modelObj->multiple_export_csv($this->_requestParams);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }








}