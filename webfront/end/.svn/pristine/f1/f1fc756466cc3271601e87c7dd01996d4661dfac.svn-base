<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/11/29
 * Time: 22:08
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Logistics_state_config extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Logistics_state_config_model');
        $this->_modelObj = $this->Logistics_state_config_model;
    }

    /**
     * 列表显示
     * @url /api/system/Logistics_state_config/get_data_list
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=4854
     * @author Justin
     * @date 2019/11/29 22:18
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
     * 批量编辑物流轨迹状态匹配规则
     * @url /api/system/Logistics_state_config/rule_batch_edit
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=4859
     * @author Justin
     * @date 2019/11/30
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
     * 编辑页面下拉列表数据
     * @url /api/system/Logistics_state_config/get_drop_down_list
     * @method GET
     */
    public function get_drop_down_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_drop_down_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}