<?php
/**
 * 短信验证码类
 * User: Hermit(朱涛)
 * Date: 2018/9/22
 * Time: 10:11
 */
class Sms_validate_code{
    /**
     * @var string 数字生成编码
     */
    private static $code_1 = '0123456789';
    
    /**
     * 生成纯数字的验证码
     * @param int $str_len 长度
     * @return string   生成后的随机数
     */
    public function numberValidateCode($str_len = 6){
            $key='';

            for($i=0;$i<$str_len;++$i) {
                $key .= self::$code_1{mt_rand(0,9)};    // 生成php随机数
            }
            return $key;
    }

}