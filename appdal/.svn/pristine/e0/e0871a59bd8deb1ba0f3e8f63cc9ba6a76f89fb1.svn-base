<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliBaseApi.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliAccount.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliProductApi.php';

/**
 * Class AliOrderApi
 * 阿里巴巴 订单退款退货
 * @author 叶凡立
 * @date 2020-12-08
 */
class AliOrderRefundApi extends AliBaseApi
{
    /**
     * 获取固定参数
     * @param string $account
     * @return mixed
     */
    public function getFixedParams($account = null){
        if(!is_null($account)) $this->aliAccountObj->setAccount($account);
        $data = $this->aliAccountObj->getAccountInfo();
        return [
            'appKey'        => $data['app_key'],
            'secKey'        => $data['secret_key'],
            'accessToken'   => $data['access_token']
        ];
    }

    /**
     * 获取退货相关信息
     */
    public function getAliOrderRefundGoods()
    {}

    /**
     * 获取1688退款退货相关信息
     */
    public function getAliOrderRefundPrice()
    {}

    /**
     * 上传退款截图
     */
    public function uploadRefundImage()
    {}
}