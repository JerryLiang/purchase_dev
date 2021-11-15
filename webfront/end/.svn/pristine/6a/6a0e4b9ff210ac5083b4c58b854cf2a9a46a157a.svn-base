<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/24
 * Time: 14:58
 */

class Charge_against_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType();
    }

    /**
     * 其他冲销审核 列表
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function get_charge_against_list($params)
    {
        $url = $this->_baseUrl . $this->_getChargeAgainstListUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 【冲销入库明细】采购单 与 入库批次进行冲销 - 自动冲销
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function inventory_item_charge_against($params)
    {
        $url = $this->_baseUrl . $this->_inventoryItemChargeAgainstUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 冲销审核 - 预览 & 提交
     * @param $params
     * @return mixed
     * @author Justin
     */
    public function charge_against_audit($params)
    {
        $url = $this->_baseUrl . $this->_auditUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 查看冲销 - （与 采购单冲销汇总表 数据 对应）
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function view_charge_against($params)
    {
        $url = $this->_baseUrl . $this->_viewChargeAgainstUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 查看冲销操作日志
     * @param $params
     * @return mixed
     * @author Jolon
     */
    public function view_charge_against_logs($params)
    {
        $url = $this->_baseUrl . $this->_viewChargeAgainstLogsUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 核销-采购单冲销汇总表
     * @param $params
     * @return mixed
     * @throws Exception
     * @author Justin
     * @date 2020/04/24
     */
    public function get_summary_data_list($params)
    {
        $url = $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 核销-采购单冲销汇总表导出
     * @param $params
     * @return array|mixed
     * @author Justin
     * @date 2020/04/24
     */
    public function summary_data_list_export($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_exportUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 核销-采购单与取消未到货退款,进行冲销-显示
     * @param $params
     * @return array|mixed
     * @author Justin
     * @date 2020/05/09
     */
    public function refund_charge_against_view($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_refundChargeAgainstViewUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 核销-采购单与取消未到货退款,进行冲销-显示
     * @param $params
     * @return array|mixed
     * @author Justin
     * @date 2020/05/09
     */
    public function get_able_ca_amount($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getAbleCaAmountUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 核销-采购单与取消未到货退款,进行冲销-保存
     * @param $params
     * @return array|mixed
     * @author Justin
     * @date 2020/05/09
     */
    public function refund_charge_against_save($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_refundChargeAgainstSaveUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 核销-其他冲销审核列表导出
     * @param $params
     * @return array|mixed
     * @author Justin
     * @date 2020/04/24
     */
    public function charge_against_list_export($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_caListExportUrl;
        return $this->request_appdal($params, $url, 'POST');
    }
}