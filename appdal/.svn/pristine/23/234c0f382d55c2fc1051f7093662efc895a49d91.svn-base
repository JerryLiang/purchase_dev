<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliBaseApi.php';

/**
 * Class AliProductApi
 * 阿里巴巴 账号相关信息
 * @author:Jolon
 * @date 2019-03-18
 */
class AliAccountApi extends AliBaseApi {

    /**
     * 获取主账号下 的子账号列表
     * @param $ali_account
     * @return array
     */
    public function getListAccount($ali_account){
        $post_data = $this->getFixedParams($ali_account);

        $quota_url = getConfigItemByName('api_config','alibaba','auth-listAccount');
        $result = $this->curlPost($quota_url,$post_data);
        $result = json_decode($result,true);
        if(isset($result['code']) and $result['code'] == 200){
            $subAccountList = $result['data']['subAccountList'];
            $this->_errorMsg = null;
            return $this->returnData($subAccountList);
        }else{
            $this->_errorMsg = $result['msg'];
            return $this->returnData();
        }
    }

    /**
     * 批量添加子账号授权
     * @param $ali_account
     * @param string $sub_ali_account  要授权的子账号
     * @return array
     */
    public function setAccountAuthAdd($ali_account,$sub_ali_account){
        $post_data = $this->getFixedParams($ali_account);
        $post_data['account'] = json_encode([$sub_ali_account]);// 数组：支持批量

//        print_r($post_data);exit;
        $quota_url = getConfigItemByName('api_config','alibaba','auth-authAdd');
        $result = $this->curlPost($quota_url,$post_data);
        $result = json_decode($result,true);
//        print_r($quota_url);
//        print_r($post_data);
//        print_r($result);exit;
        if(isset($result['error']) and !empty($result['error'])){
            $this->_errorMsg = $result['error'];
            return $this->returnData();
        }
        if(isset($result['code']) and $result['code'] == 200){
            $returnValue = $result['data']['returnValue'];
            $this->_errorMsg = null;
            return $this->returnData($returnValue);
        }else{
            $this->_errorMsg = $result['msg'];
            return $this->returnData();
        }
    }


}