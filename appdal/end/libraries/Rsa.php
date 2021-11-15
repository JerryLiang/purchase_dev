<?php
/**
 * RSA加密解密
 * Created by PhpStorm.
 */
class Rsa {

    private $private_key = '';
    private $public_key = '';

    function __construct($private_key = '', $public_key = '')
    {
        $this->private_key = $private_key;
        $this->public_key = $public_key;
    }

    /**
     * RSA私钥加密
     * @param string $private_key 私钥
     * @param string $data 要加密的字符串
     * @return string $encrypted 返回加密后的字符串
     */
    public function privateEncrypt($data){
        $encrypted = '';
        $pi_key =  openssl_pkey_get_private($this->private_key);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        //最大允许加密长度为117，得分段加密
        $plainData = str_split($data, 100);//生成密钥位数 1024 bit key
        foreach($plainData as $chunk){
            $partialEncrypted = '';
            $encryptionOk = openssl_private_encrypt($chunk,$partialEncrypted,$pi_key);//私钥加密
            if($encryptionOk === false){
                return false;
            }
            $encrypted .= $partialEncrypted;
        }

        $encrypted = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }

    /**
     * RSA公钥解密(私钥加密的内容通过公钥可以解密出来)
     * @param string $public_key 公钥
     * @param string $data 私钥加密后的字符串
     * @return string $decrypted 返回解密后的字符串
     */
    public function publicDecrypt($data){
        $decrypted = '';
        $pu_key = openssl_pkey_get_public($this->public_key);//这个函数可用来判断公钥是否是可用的
        $plainData = str_split(base64_decode($data), 128);//生成密钥位数 1024 bit key
        foreach($plainData as $chunk){
            $str = '';
            $decryptionOk = openssl_public_decrypt($chunk,$str,$pu_key);//公钥解密
            if($decryptionOk === false){
                return false;
            }
            $decrypted .= $str;
        }
        return $decrypted;
    }

    /**
     * RSA公钥加密
     * @param string $data 要加密的字符串
     * @return bool|string 返回加密后的字符串
     */
    public function publicEncrypt($data){
        $pu_key = openssl_pkey_get_public($this->public_key);
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $pu_key);
            $crypto .= $encryptData;
        }
        $encrypted = base64_encode($crypto);
        return $encrypted;
    }

    /**
     * RSA私钥解密
     * @param string $data 公钥加密后的字符串
     * @return bool|string 返回解密后的字符串
     */
    public function privateDecrypt($data){
        $decrypted = '';
        $pi_key = openssl_pkey_get_private($this->private_key);
        $plainData = str_split(base64_decode($data), 128);//生成密钥位数 1024 bit key
        foreach($plainData as $chunk){
            $str = '';
            $decryptionOk = openssl_private_decrypt($chunk,$str,$pi_key);//公钥解密
            if($decryptionOk === false){
                return false;
            }
            $decrypted .= $str;
        }
        return $decrypted;
    }



    // 数据发送方创建一套密钥对，把公钥给接收方，发送方用私钥签名数据，发给接收方。接收方将“解密后的明文、收到的签名、发送方生成的公钥”三个数据一起来验签，验证通过则表示发送方是真实有效的（签名就是为排除冒充发送方发信息的行为）
// 发送方的密钥对（这里为方便，用同一套密钥对）
    /**
    私钥签名
    @param $plain 要签名的明文
     */
    public function sign($plain) {
        // 用户私钥证书
        $pi_key = openssl_get_privatekey($this->private_key);
        if (! is_resource ( $pi_key )) {
            return FALSE;
        }
        openssl_sign($plain, $signature, $pi_key, OPENSSL_ALGO_MD5);
        openssl_free_key($pi_key);
        return base64_encode($signature);
    }


    /**
     * 公钥验签
     * @param $data
     * @param $sign
     * @return bool
     */
    public function _rsaCheckSign($data, $sign){
        $res = openssl_get_publickey($this->public_key);
        $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }
}