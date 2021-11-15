<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */

class Purchase_menu extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct() {
        parent::__construct();
       $this->load->model('purchase_news/Purchase_menu_model');
        $this->_modelObj = $this->Purchase_menu_model;
    }

    /**
     * 获取取消列表
     * @author Jaxton 2019/01/17
     */
    public function get_menu_list()
    {

        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_menu_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 获取取消列表
     * @author Jaxton 2019/01/17
     */
    public function opr_menu()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->opr_menu($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 获取数量
     * @author Jaxton 2019/01/17
     */
    public function get_menu_news_num()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_menu_news_num($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 获取数量
     * @author Jaxton 2019/01/17
     */
    public function del_menu()
    {
      

        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->del_menu($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }



//保存排序
    public function save_menu_sort()
    {

        try {
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->save_menu_sort($params);
            $this->sendData($data);
        } catch (Exception $e) {


            $this->sendError($e->getCode(), $e->getMessage());
        }



    }






}