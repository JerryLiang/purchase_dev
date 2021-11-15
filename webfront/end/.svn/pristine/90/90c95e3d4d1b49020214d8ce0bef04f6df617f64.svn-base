<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/17
 * Time: 16:35
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Purchase_inventory_items extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('statement/Purchase_inventory_items_model');
        $this->_modelObj = $this->Purchase_inventory_items_model;
    }

    /**
     * 核销-入库明细列表
     * @url /api/statement/Purchase_inventory_items/get_data_list
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=6472
     * @author Justin
     * @date 2020/04/17
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
     * 核销-入库明细添加备注
     * @url /api/statement/Purchase_inventory_items/add_remark
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=6478
     * @author Justin
     * @date 2020/04/17
     */
    public function add_remark()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->add_remark($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 核销-入库明细列表导出
     * @url /api/statement/Purchase_inventory_items/data_list_export
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=6477
     * @author Justin
     * @date 2020/04/18
     */
    public function data_list_export(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->data_list_export($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}