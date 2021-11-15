<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Oauth extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('API_JWT');

        $this->load->model('user/Oauth_user_model');
    }

    /**
     * 用户授权获取 ACCESS_TOKEN
     */
    public function token(){
        // 验证 grant_type 合法性
        $grant_type = $this->input->get_post('grant_type');

        if(empty($grant_type)){
            echo json_encode(['error' => 'invalid_request','error_description' => 'Missing grant type']);
            exit;
        }

        if($grant_type != 'client_credentials'){
            echo json_encode(['error' => 'unsupported_grant_type','error_description' => 'Unsupported grant type: '.$grant_type]);
            exit;
        }

        // 验证用户密码信息
        if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])
            || empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
            echo json_encode(['error' => 'unauthorized','error_description' => 'Full authentication is required to access this resource']);
            exit;
        }

        // 验证用户密码是否正确
        if(!($userInfo = $this->Oauth_user_model->checkUser($_SERVER['PHP_AUTH_USER'],md5($_SERVER['PHP_AUTH_PW'])))){
            echo json_encode(['error' => 'unauthorized','error_description' => 'User information authentication failed']);
            exit;
        }

        if(strcmp($userInfo['audience_key'],md5($_SERVER['PHP_AUTH_PW'])) != 0){
            echo json_encode(['error' => 'unauthorized','error_description' => 'MD5 password error']);
            exit;
        }

        // 如果token存在且没有过期则返回原来的token
        if(empty($userInfo['access_token'])){
            $jwt_token_data = $this->api_jwt->getToken($_SERVER['PHP_AUTH_USER'],md5($_SERVER['PHP_AUTH_PW']),$userInfo['expires_in']);
        }else{
            // 解析 token,判断token是否过期
            list($jwt_token_result,$message) = $this->api_jwt->decodeToken($userInfo['access_token'],$userInfo['audience_key']);
            if($jwt_token_result === false){// token 已过期
                $jwt_token_data = $this->api_jwt->getToken($_SERVER['PHP_AUTH_USER'],md5($_SERVER['PHP_AUTH_PW']),$userInfo['expires_in']);
            }else{
                // token 存在且没有过期 则返回原 token
                $jwt_token_data = [
                    'token' => [
                        'access_token'  => $userInfo['access_token'],
                        'token_type'    => 'bearer',
                        'expires_in'    => $userInfo['token_exp'] - time(),// 有效期,剩余有效期，2秒偏移量
                        'scope'         => 'read',// 权限只读
                    ]
                ];
                $this->success_json($jwt_token_data['token']);
            }
        }

        // 更新数据库
        $this->Oauth_user_model->updateUser($_SERVER['PHP_AUTH_USER'],
            [
                'access_token'  => $jwt_token_data['token']['access_token'],
                'token_iat'     => $jwt_token_data['payload']['iat'],
                'token_nbf'     => $jwt_token_data['payload']['nbf'],
                'token_exp'     => $jwt_token_data['payload']['exp'],
                'update_time'   => date('Y-m-d H:i:s'),
            ]);


        $this->rediss->deleteData('OAUTH_USER_'.$_SERVER['PHP_AUTH_USER']);// 更新信息时主动清除缓存

        // 返回token
        $this->success_json($jwt_token_data['token']);
    }

    /**
     * 清除缓存
     */
    public function del_user_info(){
        $audience = $this->input->get_post('audience');
        $this->rediss->deleteData('OAUTH_USER_'.$audience);// 更新信息时主动清除缓存
        $this->success_json();
    }

}
