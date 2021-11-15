<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * User: Jeff
 * Date: 2019/5/5
 * Time: 14:41
 */
class Suggest_expiration_set extends MY_ApiBaseController
{
    /** @var Purchase_suggest_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_suggest/Suggest_expiration_set_model');
        $this->_modelObj = $this->Suggest_expiration_set_model;
    }

    public function get_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function edit_expiration()
    {
        try {
            $this->_init_request_param("POST");
            $this->_modelObj->edit_expiration($this->_requestParams);
            $this->sendData();
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}