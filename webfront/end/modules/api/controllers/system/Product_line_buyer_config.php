<?php
/**
 * User: Justin
 * Date: 2020/8/3
 * Time: 17:06
 **/
require APPPATH . 'core/MY_ApiBaseController.php';

class Product_line_buyer_config extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Product_line_buyer_config_model');
        $this->_modelObj = $this->Product_line_buyer_config_model;
    }

    /**
     * 列表显示
     * @url /api/system/Product_line_buyer_config/get_data_list
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=17288
     * @author Justin
     * @date 2020/8/3 17:06
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
     * 编辑数据
     * @url /api/system/Product_line_buyer_config/batch_edit
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=17289
     * @author Justin
     * @date 2020/8/3 17:06
     */
    public function batch_edit() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->batch_edit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}