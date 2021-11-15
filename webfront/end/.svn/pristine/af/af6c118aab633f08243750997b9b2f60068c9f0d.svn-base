<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/01/14
 * Time: 10:23
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Check_product extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier_check/Check_product_model');
        $this->_modelObj = $this->Check_product_model;
    }

    /**
     * 验货规则-列表
     * @url /api/supplier_check/Check_product/get_rule_list
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=5622
     * @author Justin
     * @date 2020/01/14
     */
    public function get_rule_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_rule_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 验货规则-获取编辑数据
     * @url /api/supplier_check/Check_product/get_edit_data
     * @method GET
     * @author Justin
     * @date 2020/04/13
     */
    public function get_edit_data()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_edit_data($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 验货规则-批量编辑
     * @url /api/supplier_check/Check_product/rule_batch_edit
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=5640
     * @author Justin
     * @date 2020/01/14
     */
    public function rule_batch_edit() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->rule_batch_edit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 验货管理-列表
     * @url /api/supplier_check/Check_product/get_data_list
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=5713
     * @author Justin
     * @date 2020/01/14
     */
    public function get_data_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_data_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 验货管理-获取PO详情
     * @url /api/supplier_check/Check_product/get_po_detail
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=5759
     * @author Justin
     * @date 2020/01/14
     */
    public function get_po_detail()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_po_detail($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 验货管理-创建验货申请
     * @url /api/supplier_check/Check_product/create_inspection
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=5763
     * @author Justin
     * @date 2020/01/14
     */
    public function create_inspection() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->create_inspection($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 验货管理-根据验货ID获取数据（采购确认和编辑页面展示数据）
     * @url /api/supplier_check/Check_product/get_order_detail
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=5818
     * @author Justin
     * @date 2020/01/14
     */
    public function get_order_detail()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_order_detail($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 验货管理-手工创建验货单-验证验货PO是否属于等待到货及之后的状态
     * @url /api/supplier_check/Check_product/check_po_status
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=5764
     * @author Justin
     * @date 2020/01/14
     */
    public function check_po_status()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->check_po_status($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 验货管理-采购确认
     * @url /api/supplier_check/Check_product/order_confirm
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=5819
     * @author Justin
     * @date 2020/01/14
     */
    public function order_confirm() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->order_confirm($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 验货管理-列表导出
     * @url /api/supplier_check/Check_product/data_list_export
     * @method POST
     * @doc
     * @author Justin
     * @date 2020/03/14
     */
    public function data_list_export(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->data_list_export($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 验货管理-编辑
     * @url /api/supplier_check/Check_product/order_edit
     * @method POST
     * @doc
     * @author Justin
     * @date 2020/03/14
     */
    public function order_edit() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->order_edit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 验货管理-转合格申请
     * @url /api/supplier_check/Check_product/qualify_for_apply
     * @method POST
     * @doc
     * @author Justin
     * @date 2020/03/14
     */
    public function qualify_for_apply() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->qualify_for_apply($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 验货管理-作废验货单
     * @url /api/supplier_check/Check_product/make_order_invalid
     * @method POST
     * @doc
     * @author Justin
     * @date 2020/03/14
     */
    public function make_order_invalid() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->make_order_invalid($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 验货管理-操作日志
     * @url /api/supplier_check/Check_product/get_log
     * @method GET
     * @doc
     * @author Justin
     * @date 2020/03/14
     */
    public function get_log()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_log($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 验货管理-获取验货报告
     * @url /api/supplier_check/Check_product/get_report
     * @method GET
     * @doc
     * @author Justin
     * @date 2020/03/18
     */
    public function get_report()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_report($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
}