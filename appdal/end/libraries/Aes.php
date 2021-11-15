<?php
/**
 * Created by PhpStorm.
 * User: dqs
 * Date: 2018/6/4
 * Time: 下午4:22
 */
class Aes {

    public $localIV = "*****";//密钥偏移量IV

    public $encryptKey = "*****";//AESkey

    /**
     * 加密  7.1版本之前加密,之后会废弃
     * @param $encryptStr
     * @return string
     */
    public function encrypt($encryptStr)
    {
        //压缩文本内容
        $encryptStr = bzcompress($encryptStr,9);
        $blockSize 	= mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        //填充方式 pkcs5
        $input = $this->pkcs5_pad($encryptStr, $blockSize);
        //获取key
        $key = openssl_random_pseudo_bytes($blockSize);

        //获取偏移量
        $ivSize = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $ivValue = mcrypt_create_iv($ivSize, MCRYPT_RAND);

        //aes加密
        $data = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key,
            $input, MCRYPT_MODE_CBC, $ivValue);

        $this->encryptKey = base64_encode($key);
        $this->localIV = base64_encode($ivValue);
        return $data;
    }


    /**
     * 填充方式 pkcs5
     * @param String text 		 原始字符串
     * @param String blocksize   加密长度
     * @return String
     */
    private function pkcs5_pad($data, $blocksize)
    {
        $pad = $blocksize - (strlen($data) % $blocksize);
        return $data . str_repeat(chr($pad), $pad);
    }


    /**
     * 解密  7.1版本之前解密,之后会废弃
     * @param $encryptStr
     * @return string
     */
    public function decrypt($encryptStr)
    {
        $encryptKey = base64_decode($this->encryptKey);
        $localIV = base64_decode($this->localIV);

        //根据key和iv进行解密
        $encryptedData = mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $encryptKey,
            base64_decode($encryptStr),
            MCRYPT_MODE_CBC,
            $localIV
        );
        //解压缩
        $encryptedData = bzdecompress($encryptedData);
        return $encryptedData;
    }

    /**
     * 加密 兼容7.0版本,优先选择
     * @param $input
     * @return string
     */
    public function shopenEncrypt($fileContent)
    {
        //压缩文本内容
        $fileContent = bzcompress($fileContent,9);
        //生成随机16位的key
        $key = substr(bin2hex(openssl_random_pseudo_bytes(16)),0,16);
        //生成随机16位的iv
        $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
        $ivValue = openssl_random_pseudo_bytes($ivlen);
        //aes加密
        $data = openssl_encrypt($fileContent, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $ivValue);

        //将生成的key转为base64
        $this->encryptKey = base64_encode($key);
        //将生成的iv转为base64
        $this->localIV = base64_encode($ivValue);
        return $data;
    }

    /**
     * 解密 兼容7.0版本,优先选择
     * @param $input
     * @return string
     */
    public function shopenDecrypt($input)
    {
        $encryptKey = base64_decode($this->encryptKey);
        $localIV = base64_decode($this->localIV);

        //aes 解密
        $decrypted = openssl_decrypt(base64_decode($input), 'AES-128-CBC',  $encryptKey, OPENSSL_RAW_DATA, $localIV);
        //解密后的内容,解压缩
        $decrypted = bzdecompress($decrypted);
        return $decrypted;
    }


    /**
     * 转换一个string字符串为byte数组
     * @param $string 需要转换的字符串
     * @param $bytes 目标byte数组
     */
    function getbytes($string)
    {
        $bytes = array();
        for($i = 0; $i < strlen($string); $i++){    //遍历每一个字符 用ord函数把它们拼接成一个php数组
            $bytes[] = ord($string[$i]);
        }
        return $bytes;
    }
}