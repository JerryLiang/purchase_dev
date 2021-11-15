<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */


class Purchase_order_lack extends MY_ApiBaseController
{

    /** @var Purchase_order_model */
    private $_modelObj;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_order_lack_model');
        $this->_modelObj = $this->Purchase_order_lack_model;
    }

    /**
     * 获取HTTP 客户端POST 参数
     **/
    protected  function get_post(){

        if(!empty($_POST)){

            $postData = [];

            foreach( $_POST as $key=>$value){

                $postData[$key] = $this->input->get_post($key);
            }

            return $postData;
        }

        return NULL;
    }

    /**
     * 需求:少数规则
        说明：少数少款类型为“少数”的，判断备货单中的SKU采购数量和累计实际到货数量；
        1、采购数量小于配置数值，少数的标记处理状态为“未处理”；
        2、采购数量大于等于配置数值，且实际到货数量大于等于（采购数量*50%），少数的处理状态为“未处理”；
        3、采购数量大于等于配置数值，且实际到货数量小于（采购数量*50%），少数的处理状态为“分批次到货”；
         * 说明：少数少款类型为“少款”的，判断采购单中少款的SKU数量；

        1、判断采购单中的SKU个数在左边数据的哪个区间内；
        2、判断采购单中少款的SKU个数大于右边配置的数量，该采购单中少款的备货单都标记为“分批次到货”；
        3、判断采购单中少款的SKU个数小于等于右边配置的数量，该采购单中少款的备货单都标记为“未处理”；
     * @author:luxu
     * @time: 2020/11/25
     **/

    public function saveConfigData(){

        $clientData = $this->get_post();
        $data = $this->_modelObj->saveConfigData($clientData);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 读取少数少款配置信息
     * @param  wu
     * @author:luxu
     * @time: 2020/11/26
     **/

    public function getConfigData(){

        $this->_init_request_param('GET');
        $clientData = $this->_requestParams;
        $data = $this->_modelObj->getConfigData($clientData);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 少数少款列表数据
     * @param HTTP 传递参数
     * @author:luxu
     * @time:2020/11/27
     **/
    public function getLackData(){

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data = $this->_modelObj->getLackData($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 设置分批次接口
     * @param
     * @author :luxu
     * @time: 2020/11/28
     **/
    public function setBatches(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->setBatches($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 设置分批次接口
     * @param
     * @author :luxu
     * @time: 2020/11/28
     **/
    public function setMoney(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->setMoney($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function getLogs(){

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data = $this->_modelObj->getLogs($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 添加少数少款备注功能
     * @param
     * @author:luxu
     * @time:2021年1月10号
     **/

    public function setLockMessage(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->setLockMessage($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 添加少数少款导出CSV格式
     * @param
     * @author:luxu
     * @time:2021年1月10号
     **/
    public function exportData_csv(){

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data = $this->_modelObj->exportData_csv($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
}