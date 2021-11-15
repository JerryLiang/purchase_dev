<?php

/**
 * Created by PhpStorm.
 * 请款单
 * User: Jolon
 * Date: 2019/01/10 0027 11:23
 */
class Purchase_auto_payout_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
    }

    /**
     * 网采单-创建请款单-自动请款
     * @author Jolon
     * @desc array $_POST['purchase_numbers'] 要请款采购单号
     */
    public function auto_payout($params = array())
    {
        $url    = $this->_baseUrl . $this->_autoPayout;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

}
