<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */
class Purchase_shipping_package_list extends MY_ApiBaseController {
    private $_modelObj;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_shipment/Purchase_shipping_package_model');
        $this->_modelObj = $this->Purchase_shipping_package_model;
    }
    /**
     * 获取整柜列表
     * @author Jaxton 2019/01/17
     */
    public function get_cabinet_list()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_cabinet_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
    /**
     * 导出
     * /abnormal/report_loss/export_report_loss
     * @author Jaxton 2019/01/18
     */
    public function get_export_cabinet_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_export_cabinet_list($params);

    }


    public function get_box_list()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_box_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function get_export_box_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_export_box_list($params);

    }

    /*
 * desc 导入 装箱明细
 * Dean
 */
    public function import_package_list(){


        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        if(!isset($params['file_path']) or empty($params['file_path'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['file_path'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;

        try {
            $data = $this->_modelObj->import_package_list($params);
            if($data['code']){
                $this->_code = 0;
                $this->_msg = $data['message'];
                $this->sendData();
            }else{
                $this->_code = 500;
                $this->_msg = $data['message'];
                $this->sendData(['error_file_path' => $data['data']]);
            }
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    //获取装箱明细
    public function get_package_box_list() {

        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_package_box_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    //获取装箱明细
    public function get_container_log_list() {

        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_container_log_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 打印装箱明细
     * @author liukai 2020-06-30
     * * */
    public function printing_box_detail() {

        $params = $this->_requestParams;
        if(!isset($params['ids'])){
            $data=['status'=>0,'errorMess'=>'请勾选数据'];
            $this->sendData($data);
        }
        $data=$this->_modelObj->printing_box_detail($params);
        $this->sendData($data);
    }


    /**
     * 返回装箱明细模板
     * @author dean
     * http://www.cgapi.com/api/purchase_shipment/purchase_shipping_package_list/print_menu
     * **/
    public function print_menu(){
        try {
            $this->_init_request_param("POST");
            $data=$this->_modelObj->get_print_menu($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $exc) {
            $this->sendError(-1, $exc->getMessage());
        }
    }




}