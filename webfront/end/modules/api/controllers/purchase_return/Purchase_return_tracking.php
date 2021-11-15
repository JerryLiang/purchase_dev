<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020/3/10
 * Time: 14:53
 */

require APPPATH . 'core/MY_ApiBaseController.php';

class Purchase_return_tracking extends MY_ApiBaseController{

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase_return/Purchase_return_tracking_model');
        $this->_modelObj = $this->Purchase_return_tracking_model;
    }

    /**
     *  列表
     */
    public function get_storage_collection_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_storage_collection_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 供应商签收api/purchase_return/Purchase_return_tracking/check_receipt
     */
    public function  check_receipt(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->check_receipt($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 保存上传截图api/purchase_return/Purchase_return_tracking/save_upload
     */
    public function  save_upload(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->save_upload($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取物流商信息api/purchase_return/Purchase_return_tracking/express_info
     */
    public function  express_info(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->express_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 录入快递单号
     */
    public function  save_express(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->save_express($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取详情信息
     */
    public function get_storage_item_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->item_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 入库退货跟踪轨迹
     */
    public function get_logistics_trajectory(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_logistics_trajectory($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 导出
     */
    public function export_storage_data(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->export_storage_data($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}