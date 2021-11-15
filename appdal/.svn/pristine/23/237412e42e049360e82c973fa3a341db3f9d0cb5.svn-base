<?php

/*********************** 1688支付 配置 ***********************/
if(CG_ENV == 'dev')  //开发环境
{
    $config['ali']['buyerView']    = 'param2/1/com.alibaba.trade/alibaba.trade.get.buyerView/';//获取订单详情（买家视角） @author Jackson
    $config['ali']['urlGet']       = 'param2/1/com.alibaba.trade/alibaba.alipay.url.get/';//获取支付宝支付链接（买家视角 @author Jackson
    $config['ali']['gateway']      = 'http://gw.open.1688.com/openapi/';//阿里巴巴开放平台网关 @author Jackson
    $config['ali']['refreshToken'] = 'param2/1/system.oauth2/getToken';

}elseif(CG_ENV == 'prod'){  //生产环境

    $config['ali']['buyerView']    = 'param2/1/com.alibaba.trade/alibaba.trade.get.buyerView/';//获取订单详情（买家视角） @author Jackson
    $config['ali']['urlGet']       = 'param2/1/com.alibaba.trade/alibaba.alipay.url.get/';//获取支付宝支付链接（买家视角 @author Jackson
    $config['ali']['gateway']      = 'http://gw.open.1688.com/openapi/';//阿里巴巴开放平台网关 @author Jackson
    $config['ali']['refreshToken'] = 'param2/1/system.oauth2/getToken';

}else{  //测试环境

    $config['ali']['buyerView']    = 'param2/1/com.alibaba.trade/alibaba.trade.get.buyerView/';//获取订单详情（买家视角） @author Jackson
    $config['ali']['urlGet']       = 'param2/1/com.alibaba.trade/alibaba.alipay.url.get/';//获取支付宝支付链接（买家视角 @author Jackson
    $config['ali']['gateway']      = 'http://gw.open.1688.com/openapi/';//阿里巴巴开放平台网关 @author Jackson
    $config['ali']['refreshToken'] = 'param2/1/system.oauth2/getToken';

}
/*********************** 1688支付 配置 ***********************/