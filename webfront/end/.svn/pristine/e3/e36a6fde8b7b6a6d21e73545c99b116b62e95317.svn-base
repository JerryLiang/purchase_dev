<?php

/**
 * 供应商交期
 * @author Jolon
 * @param
 * @DateTime 2021/06/11
 */
class Supplier_average_delivery_model extends Api_base_model
{
    protected $_baseUrl; // 统一地址前缀
    protected $_listUrl; // 列表的路径


    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     * @desc 获取供应商交期列表
     * @author Jolon
     * @parames array $params 请求参数
     * @DateTime 2021/06/11
     * @return array()
     */
    public function get_delivery_list($params = array())
    {
        $url = $this->_baseUrl . $this->_listUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;

    }

    /**
     * 供应商交期列表 - 导出
     * @author Jolon
     * @parames array $params 请求参数
     */
    public function export_delivery_list($params){
        $url = $this->_baseUrl . $this->_exportDeliveryListUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }
}