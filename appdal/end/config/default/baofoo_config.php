<?php

/**
 * 宝付支付接口配置文件
 * @author:Jackson.Liu
 * @since: 2019-01-01
 */

// CG_ENV 在 config/conf.php中定义
/*********************** 宝付支付 配置 ***********************/
if(CG_ENV == 'dev')  //开发环境
{

    $config['baofoo']['member_id']        = 100000178;//会员号
    $config['baofoo']['terminal_id']      = 100000859;//终端号
    $config['baofoo']['password']         = '123456';//证书密码
    $config['baofoo']['data_type']        = 'json';//数据格式
    $config['baofoo']['BF0040001']        = 'https://paytest.baofoo.com/baofoo-fopay/pay/BF0040001.do';//支付接口
    $config['baofoo']['BF0040002']        = 'https://paytest.baofoo.com/baofoo-fopay/pay/BF0040002.do';//查询接口
    $config['baofoo']['BFA0040001']       = 'http://120.132.102.234:21102/baofoo-fopay-assistant/pay/BFA0040001.do';//代付凭证文件接口(BFA0040001)
}elseif(CG_ENV == 'prod'){  //生产环境

    $config['baofoo']['member_id']        =100000178;//会员号
    $config['baofoo']['terminal_id']      = 100000859;//终端号
    $config['baofoo']['password']         = '123456';//证书密码
    $config['baofoo']['data_type']        = 'json';//数据格式
      $config['baofoo']['BF0040001']      = 'https://public.baofoo.com/baofoo-fopay/pay/BF0040001.do';//接口IP
    $config['baofoo']['BF0040002']        = 'https://paytest.baofoo.com/baofoo-fopay/pay/BF0040002.do';//查询接口
    $config['baofoo']['BFA0040001']       = 'http://120.132.102.234:21102/baofoo-fopay-assistant/pay/BFA0040001.do';//代付凭证文件接口(BFA0040001)
}else{  //测试环境

    $config['baofoo']['member_id']        = 100000178;//会员号
    $config['baofoo']['terminal_id']      = 100000859;//终端号
    $config['baofoo']['password']         = '123456';//证书密码
    $config['baofoo']['data_type']        = 'json';//数据格式
    $config['baofoo']['BF0040001']        = 'https://paytest.baofoo.com/baofoo-fopay/pay/BF0040001.do';//接口IP
    $config['baofoo']['BF0040002']        = 'https://paytest.baofoo.com/baofoo-fopay/pay/BF0040002.do';//查询接口
    $config['baofoo']['BFA0040001']       = 'https://api-assistant.baofoo.com/baofoo-fopay-assistant/pay/BFA0040001.do';//代付凭证文件接口(BFA0040001)
}
/*********************** 宝付支付 配置 ***********************/