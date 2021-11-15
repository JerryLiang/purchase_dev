<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliBaseApi.php';

/**
 * Class Ali1688BaseApi
 * 阿里巴巴 供应商相关信息
 * @author:Jolon
 * @date 2019-03-18
 */
class AliSupplierApi extends AliBaseApi {

    /**
     * 供应商账期信息
     * @url https://open.1688.com/api/apidocdetail.htm?id=com.alibaba.trade:alibaba.accountPeriod.list.buyerView-1
     * @param $sellerLoginId
     * @return mixed|string
     */
    public function getSupplierQuota($account = null,$sellerLoginId = '金华园林工具厂家'){
        if(empty($sellerLoginId)){
            $this->_errorMsg = '供应商登录Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams($account);
        $post_data['sellerLoginId'] = $sellerLoginId;
        $quota_url = getConfigItemByName('api_config','alibaba','list_buyer_view');
        $result = $this->curlPost($quota_url,$post_data);
        return $result;

    }

    /**
     * 供应商店铺信息
     * @url https://open.1688.com/api/apidocdetail.htm?id=com.alibaba.account:alibaba.account.agent.basic-1&aopApiCategory=member
     * @param $account
     * @param $shop_url
     * @return mixed|string
     */
    public function getSupplierShopInfo($account = null,$shop_url){
        $post_data = $this->getFixedParams($account);
        $post_data['url'] = $shop_url;
        $quota_url = getConfigItemByName('api_config','alibaba','get_supplier_shop_id');
        $result = $this->curlPost($quota_url,$post_data);
        return $result;
    }


}