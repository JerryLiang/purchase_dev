<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Account_api extends MY_API_Controller {
    
     public function __construct() {
        parent::__construct();
         $this->load->model('system/Account_ail_model');
    }

    /**
     * 根据账号 获取 assess_token
     */
    public function get_assess_token(){
        $account = $this->input->get('account');
        $this->load->model('finance/Alibaba_account_model');

        $accountInfo = $this->Alibaba_account_model->get_alibaba_account_by_account($account);
        if($accountInfo){
            $this->success_json($accountInfo);
        }else{
            $this->error_json('查询失败');
        }
    }

    /**
     * 1688平台授权
     */
    public function update(){
        $params['id'] = $this->input->get('id');
        $params['code'] = $this->input->get('code');
        try {
            if(!isset($params['id']) || empty($params['id'])){
                echo '参数不能为空ID';die;
            }
            if(!isset($params['code']) || empty($params['code'])){
                echo '参数不能为空cdoe';die;
            }
            $resluts = $this->Account_ail_model->get_update_code($params);
            if ($resluts) {
                $reslut=$this->Account_ail_model->get_list($params['id']);
                $appkey = $reslut['app_key'];
                $secret_key = $reslut['secret_key'];
                $redirect_uri = $reslut['redirect_uri'];
                $_url = ACCOUNT_IP . $appkey . ACCOUNT_IP_TYPE . $appkey . "&client_secret={$secret_key}&redirect_uri={$redirect_uri}&code={$code}";
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $_url);
                curl_setopt($curl, CURLOPT_HEADER, 0);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
                $s = curl_exec($curl);
                curl_close($curl);
                $responses = json_decode($s, 1);
                if ($responses) {
                    $model['access_token'] = $responses['access_token'];
                    $model['refresh_token'] = $responses['refresh_token'];
                    $model['expires_in'] = $responses['expires_in'];
                    $model['refresh_token_timeout'] = $responses['refresh_token_timeout'];
                    $model['id'] =  $params['id'];
                    $resluts = $this->Account_ail_model->get_update_code($model);
                } else {
                    echo '1688授权失败';
                }
            }
        } catch (Exception $exc) {
            echo $exc->getMessage();
        }
    }

}