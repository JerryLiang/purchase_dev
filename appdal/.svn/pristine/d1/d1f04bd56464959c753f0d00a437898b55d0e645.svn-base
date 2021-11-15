<?php

require dirname(APPPATH) . DIRECTORY_SEPARATOR . "vendor/autoload.php";

use \Firebase\JWT\JWT;


/**
 * Created by PhpStorm.
 * Desc：Oauth 授权生成器类
 * User: Jolon
 * Date: 2021/5/13
 * Time: 上午9:22
 */
class API_JWT {

    public $_JWT_ISS = 'YB_CLOUD_PMS_JWT';// jwt的颁发者
    public $_JWT_AUD = null;// jwt的适用对象
    public $_JWT_IAT = null;// jwt的签发时间
    public $_JWT_NBF = null;// 表示jwt在这个时间后启用
    public $_JWT_EXP = null;// 过期时间

    private $_JWT_ALG = 'HS256';// 签名算法


    public function __construct(){}


    /**
     * Oauth 授权生成 access_token
     * @param string $audience      用户
     * @param string $audience_key  用户hash密码
     * @param int $exp_offset       token有效期（秒）
     * @return array
     */
    public function getToken($audience,$audience_key,$exp_offset = 1800){
        $this->_JWT_IAT = time();
        $this->_JWT_NBF = $this->_JWT_IAT;// 生效时间=签发时间
        $this->_JWT_AUD = $audience;
        $this->_JWT_EXP = $this->_JWT_IAT + $exp_offset;// 过期时间

        $payload = array(
            "iss" => $this->_JWT_ISS,
            "aud" => $this->_JWT_AUD,
            "iat" => $this->_JWT_IAT,
            "nbf" => $this->_JWT_NBF,
            "exp" => $this->_JWT_EXP,
        );

        // JWT插件生成令牌
        $access_token = JWT::encode($payload, $audience_key ,$this->_JWT_ALG);

        return [
            'token' => [
                'access_token'  => $access_token,
                'token_type'    => 'bearer',
                'expires_in'    => $exp_offset,// 有效期
                'scope'         => 'read',// 权限只读
            ],
            'payload' => $payload
        ];
    }


    /**
     * Oauth 解析令牌，验证是否过期
     * @param string $access_token  用户请求token
     * @param string $audience_key  用户hash密码
     * @return array
     */
    public function decodeToken($access_token,$audience_key){
        try{
            $decoded = JWT::decode($access_token, $audience_key, array( $this->_JWT_ALG));
            return [true,(array)$decoded];
        }catch (Exception $e){
            return [false,$e->getMessage()];
        }
    }


    /**
     * 检查 access_token 是否合法
     * @param $access_token
     * @return array
     */
    public function checkTokenUser($access_token){
        $split = explode('.',$access_token);

        if(!isset($split[1]) or empty($split[1])){
            // Token不合法
        }

        $payload = json_decode(base64_decode($split[1]),true);
        $iss = $payload['iss'];
        $aud = $payload['aud'];

        if($iss !== $this->_JWT_ISS){
            // Token不合法
        }

        // 验证 IP黑名单



        // 验证 用户是否合法
        if(1){
            // Token不合法
        }


        return [true,$payload];
    }
}