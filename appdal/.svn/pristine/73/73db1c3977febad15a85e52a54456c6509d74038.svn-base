<?php

include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliAccountApi.php';

/**
 * Class AliAccount
 * 阿里巴巴1688批发采购平台 账号
 * @author:Jolon
 * @date 2019-03-18
 */
class AliAccount extends Purchase_model {

    public $account          = 'yibaisuperbuyers';
    public $accountInfo      = null;
    public $appKey           = null;
    public $appSecret        = null;
    public $accessToken      = null;


    /**
     * 获得当前激活的账号名称
     * @return null
     */
    public function getActiveAccount(){
        return $this->account;
    }

    /**
     * 设置当前操作的目标账号
     * @param $account
     */
    public function setAccount($account){
        $this->account = $account;
        $this->setAccountInfo();
    }

    /**
     * 获得当前激活的账号名称
     * @return null
     */
    public function getAccountInfo(){
        return $this->accountInfo;
    }

    /**
     * 设置当前激活的账号的用户基本信息
     * @return bool
     */
    public function setAccountInfo(){
        $accountInfo = $this->purchase_db
            ->select('access_token,app_key,secret_key')
            ->where(['account'=>$this->account])
            ->from('alibaba_account')
            ->get()
            ->row_array();

        if($accountInfo){
            $this->appKey      = $accountInfo['app_key'];
            $this->appSecret   = $accountInfo['secret_key'];
            $this->accessToken = $accountInfo['access_token'];
            $this->accountInfo = $accountInfo;
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取 主账号下 所有子账号列表
     * @param $ali_account
     * @return array
     */
    public function getListAccount($ali_account){
        $aliAccountApi = new AliAccountApi();
        $listAccount = $aliAccountApi->getListAccount($ali_account);
        return $listAccount;
    }

    /**
     * 获取 子账号的 token
     * @param $ali_account
     * @param $sub_ali_account
     * @return array
     */
    public function getSubAccountToken($ali_account,$sub_ali_account){
        $sub_access_token = $this->rediss->getData($sub_ali_account);
        if(empty($sub_access_token)){
            $aliAccountApi = new AliAccountApi();
            $accessTokenList = $aliAccountApi->setAccountAuthAdd($ali_account,$sub_ali_account);// 子账号加入授权
//            print_r($accessTokenList);exit;
            if($accessTokenList['code']){
                $accessTokenList = $accessTokenList['data'];
                if(isset($accessTokenList[$sub_ali_account])){
                    $accessToken = $accessTokenList[$sub_ali_account]['accessToken'];
                    $accessTokenTimeout = $accessTokenList[$sub_ali_account]['accessTokenTimeout'];
                    $this->rediss->setData($sub_ali_account,$accessToken,$accessTokenTimeout);

                    $return = ['code' => true,'access_token' => $accessToken,'errorMsg' => ''];
                }else{
                    $return = ['code' => false,'errorMsg' => "授权失败请求成功，可能账号[$sub_ali_account]错误"];
                }
            }else{
                $return = ['code' => false,'errorMsg' => $accessTokenList['errorMsg']];
            }
        }else{
            $return = ['code' => true,'access_token' => $sub_access_token,'errorMsg' => ''];
        }

        return $return;
    }

    /**
     * 获取 采购员 或 指定子账号的记录
     * @param null $user_id
     * @param null $sub_ali_account
     * @return array
     */
    public function getSubAccountOneByUserId($user_id = null,$sub_ali_account = null){
        $this->purchase_db->select('user_id,p_account,account,pay_user_id,level');

        if($user_id){
            $this->purchase_db->where('user_id',$user_id);
        }
        if($sub_ali_account){
            $this->purchase_db->where('account',$sub_ali_account);
        }
        $list = $this->purchase_db->get('alibaba_sub',1)
            ->row_array();

        if($list) return $list;
        return [];
    }

    /**
     * 获取 采购员 所有子账号的记录
     * @param $user_id
     * @return array
     */
    public function getSubAccountListByUserId($user_id){
        $list = $this->purchase_db->select('user_id,p_account,account')
            ->where('user_id',$user_id)
            ->get('alibaba_sub')
            ->result_array();

        return $list;
    }





}