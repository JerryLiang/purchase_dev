<?php
/**
 * 富有支付接口配置文件
 * @author:Jackson.Liu
 * @since: 2019-01-01
 */

// CG_ENV 在 config/conf.php中定义
/*********************** 富友支付 配置 ***********************/
if(CG_ENV == 'dev')  //开发环境
{

    $config['ufxfuiou']['domain']        = 'http://www.cg.com';//富友支付域名 @author Jackson
    $config['ufxfuiou']['partnerId']     = 'FY000001';//富友支付-账号ID @author Jackson
    $config['ufxfuiou']['md5Key']        = 'e112dc63a91d420ba23c4e70ab33351f';//富友支付-KEY @author Jackson
    $config['ufxfuiou']['fuiouUrl']      = 'http://www-1.fuiou.com:9015/gpayapi/eics/common.fuiou';//富友支付-KEY @author Jackson
    $config['ufxfuiou']['backNotifyUrl'] = '/finance/Purchase_order_cashier_pay/notify_url';//富友支付-通知地址 @author Jackson

}elseif(CG_ENV == 'prod'){  //生产环境

    $config['ufxfuiou']['domain']        = 'http://caigou.yibainetwork.com';//富友支付域名 @author Jackson
    $config['ufxfuiou']['partnerId']     = 'FL000049';//富友支付-账号ID @author Jackson
    $config['ufxfuiou']['md5Key']        = 'vXLfAYBXexpRKFJWmySrlEEZDZgZB6NX';//富友支付-KEY @author Jackson
    $config['ufxfuiou']['fuiouUrl']      = 'https://ufx.fuioupay.com/eics/common';//富友支付-KEY @author Jackson
    $config['ufxfuiou']['backNotifyUrl'] = '/finance/Purchase_order_cashier_pay/notify_url';//富友支付-通知地址 @author Jackson

}else{  //测试环境

    $config['ufxfuiou']['domain']        = 'http://www.cg.com';//富友支付域名 @author Jackson
    $config['ufxfuiou']['partnerId']     = 'FL000008';//富友支付-账号ID @author Jackson
    $config['ufxfuiou']['md5Key']        = 'XQQfxYEHn6lV9ipfE1cVkX3sC8CRKlov';//富友支付-KEY @author Jackson
    $config['ufxfuiou']['fuiouUrl']      = 'http://www-1.fuiou.com:19032/eics/common';//富友支付-KEY @author Jackson
    $config['ufxfuiou']['backNotifyUrl'] = '/finance/Purchase_order_cashier_pay/notify_url';//富友支付-通知地址 @author Jackson

}
/*********************** 富友支付 配置 ***********************/


