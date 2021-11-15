<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/5
 * Time: 11:01
 */
class Purchase_return_goods_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 验证退货sku,并返回可退货的sku数据
     * @param $params
     */
    public function verify_return_sku($params){
        $url=$this->_baseUrl.$this->_getVerifyReturnSku;
        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }

    /**
     * 提交入库退货申请
     * @param $params
     */
    public function save_return_data_submit($params){
        $url=$this->_baseUrl.$this->_getSaveReturnDataSubmit;
        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }
}