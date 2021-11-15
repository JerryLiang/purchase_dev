<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 产品信息不全控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Product_incomplete extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct(){

        parent::__construct();
        $this->load->model('purchase_suggest/Product_incomplete_model');
        $this->_modelObj = $this->Product_incomplete_model;
    }

    /**
     * 产品信息不全列表
     * /purchase_suggest/product_incomplete/product_incomplete_list
     * @author Jaden 2019-1-8
     */

    public function product_incomplete_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_incomplete_list_all($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 产品信息不全页面推送
     * /purchase_suggest/product_incomplete/put_product_incomplete_list
     * @author Jaden 2019-1-8
     */
    public function put_product_incomplete_list(){

        $params = $this->_requestParams;
        $data = $this->_modelObj->push_product_incomplete_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);    	
    }


    /**
     * 产品信息不全页面导出
     * /purchase_suggest/product_incomplete/export_product_incomplete
     * @author Jaden 2019-1-8
     */
    public function export_product_incomplete(){
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->export_product_incomplete_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $tax_list_tmp = $data['data_list'];
        $heads = ['SKU','产品图片URL','产品线','产品名称', '单价', '开票点','退税率','供应商','拦截原因','创建时间','备注'];
        csv_export($heads,$tax_list_tmp,'产品信息不全-'.date('YmdH_i_s'));
    }


    /**
     * 产品信息不全页面添加备注
     * /purchase_suggest/product_incomplete/create_remarks
     * @author Jaden 2019-1-8
     */
    public function create_remarks(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_create_remarks($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }




}