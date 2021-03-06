<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/29
 * Time: 14:14
 */
class Purchase_order_lack_model extends Api_base_model
{

    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
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
    public function saveConfigData($params){

        $url = $this->_baseUrl . $this->_saveConfigData;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     * 读取少数少款配置信息
     * @param  wu
     * @author:luxu
     * @time: 2020/11/26
     **/
    public function getConfigData($params){

        $url = $this->_baseUrl . $this->_getConfigData;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     * 少数少款列表数据
     * @param HTTP 传递参数
     * @author:luxu
     * @time:2020/11/27
     **/

    public function getLackData($params){

        $url = $this->_baseUrl . $this->_getLackData. "?" . http_build_query($params);
        $result = $this->_curlWriteHandleApi($url, "", "GET");
        return $result;
    }

    public function setBatches($params){
        $url = $this->_baseUrl . $this->_setBatches;

        $result = $this->request_appdal($params,$url,'POST');
        return $result;

    }

    public function setMoney($params){
        $url = $this->_baseUrl . $this->_setMoney;

        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }

    public function getLogs($params){

        $url = $this->_baseUrl . $this->_getLogs;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     * 添加少数少款备注功能
     * @param
     * @author:luxu
     * @time:2021年1月10号
     **/
    public function setLockMessage($params){

        $url = $this->_baseUrl . $this->_setLockMessage;
        $result = $this->request_appdal($params,$url,'POST');

        return $result;
    }

    /**
     * 添加少数少款导出CSV格式
     * @param
     * @author:luxu
     * @time:2021年1月10号
     **/
    public function exportData_csv($params){

        $url = $this->_baseUrl . $this->_exportData_csv. "?" . http_build_query($params);
        $result = $this->_curlWriteHandleApi($url, "", "GET");
        return $result;
    }
}