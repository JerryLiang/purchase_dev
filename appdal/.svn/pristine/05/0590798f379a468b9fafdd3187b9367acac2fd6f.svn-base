<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * Created by PhpStorm.
 * User: zouhua
 * Date: 2019/3/25
 * Time: 14:23
 */

class Refresh extends MY_API_Controller {

    public function __construct() {
//        parent::__construct();
        $this->load->model('finance/Alibaba_account_model', 'alibaba_account_model');
    }

    public function refresh_ali_token() {
        $result = $this->alibaba_account_model->getAccountList();
        foreach ($result as $v) {
            $res = $this->alibaba_account_model->getRefreshToken($v['refresh_token'], $v['app_key'], $v['secret_key']);
            $data = json_decode($res, true);
            if (is_array($data) && !empty($data['access_token'])) {
                $row = $this->alibaba_account_model->updateAccessToken($v['app_key'], ['access_token' => $data['access_token']]);
                $state = $row ? '成功' : '失败';
                echo "更新{$v['account']}账号 access token {$state}。\n";
            } else {
                echo "获取access token 失败. {$res}。\n";
            }
        }
    }
}