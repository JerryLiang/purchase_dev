<?php

/**
 * 1688 额度付款
 * @author: yefanli
 * @Date: 2020/11/11
 */

class Ali_quota_payment_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * 1688 额度付款
     * @param $params
     * @return array|mixed|null
     */
    public function ali_quota_payment($params)
    {
        $url = $this->_baseUrl.$this->_aliQuotaPayment;
        $result = $this->httpRequest($url, $params);
        return $result;
    }
}