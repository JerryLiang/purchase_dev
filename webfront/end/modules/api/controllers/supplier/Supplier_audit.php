<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * 供应商管理 ==》 供应商审核列表 业务层
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/24
 * Time: 20:01
 */
class Supplier_audit extends MY_ApiBaseController
{

    /** @var Supplier_audit_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/Supplier_audit_model');
        $this->_modelObj = $this->Supplier_audit_model;
    }

    /**
     * 列表数据
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method get
     */
    public function get_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_page_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 导出excel
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method GET
     */
    public function export()
    {
        set_time_limit(0);
        try {
            $this->_init_request_param("GET");
            $this->_modelObj->export_excel($this->_requestParams);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}