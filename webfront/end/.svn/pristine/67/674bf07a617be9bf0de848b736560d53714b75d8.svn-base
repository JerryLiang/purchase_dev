<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/24
 * Time: 14:58
 */

class Statement_order_pay_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType();
    }


    /**
     * 对账单-创建请款单-数据预览（第一步）
     * @author Jolon
     * @desc array $_POST['statement_number']         要请款对账单号
     */
    public function statement_pay_order_preview($params)
    {
        $url = $this->_baseUrl . $this->_statementPayOrderPreview;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 对账单-创建请款单-付款申请书预览（第二步）
     * @author Jolon
     */
    public function statement_pay_order_preview_requisition_payment($params)
    {
        $url = $this->_baseUrl . $this->_statementPayOrderPreviewRequisitionPayment;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 对账单-创建请款单（第三步）
     * @author Jolon
     */
    public function statement_pay_order_create($params)
    {
        $url = $this->_baseUrl . $this->_statementPayOrderCreate;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 请款单、请款审核 查看对账单详情
     * @author Jolon
     */
    public function pay_statement_detail($params)
    {
        $url = $this->_baseUrl . $this->_payStatementDetail;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }
}