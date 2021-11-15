<?php
/**
 * 全局配置文件
 * @author:凌云
 * @since: 20180921
 */
// 用户自定义系统级配置项

//定义环境的变量 test 测试 ,   prod：生产环境, dev : 开发者环境
defined('CG_ENV') OR define('CG_ENV', 'dev');//test, prod,dev



//defined('UPLOAD_DOMAIN')    OR define('UPLOAD_DOMAIN','http://www.cgdown.net');// 公共资源文件夹域名 @author Jolon
defined('UEB_STOCK_KEYID')  OR define('UEB_STOCK_KEYID','yibai');//作用于签名 @author Jaden


if(CG_ENV == 'dev'){//开发环境

    defined('OA_PURCHASE_PERSON_ID')    OR define('OA_PURCHASE_PERSON_ID', 1079230); // 开发环境OA 供应链团队部门ID
    defined('CG_SYSTEM_WEB_FRONT_IP')   OR define('CG_SYSTEM_WEB_FRONT_IP', 'http://192.168.71.170:86/');// 采购系统前端IP
    //defined('CG_SYSTEM_WEB_FRONT_IP')   OR define('CG_SYSTEM_WEB_FRONT_IP', 'http://www.webfront.com/');// 采购系统前端IP

    defined('CG_SYSTEM_APP_DAL_IP')     OR define('CG_SYSTEM_APP_DAL_IP', 'http://192.168.71.170:85/');// 采购系统后端IP
    defined('OA_SYSTEM_IP')             OR define('OA_SYSTEM_IP', 'http://rest.dev.java.yibainetworklocal.com/oa/');// OA系统IP
//    defined('OA_ACCESS_TOKEN_IP')       OR define('OA_ACCESS_TOKEN_IP', 'http://oauth.dev.java.yibainetworklocal.com/');// OA 获取access_token
    defined('PRODUCT_SYSTEM_IP')        OR define('PRODUCT_SYSTEM_IP', 'http://rest.dev.java.yibainetworklocal.com/');// 产品系统系统IP  测试环境 http://192.168.71.245:9090/
    defined('JAVA_SYSTEM_ERP')          OR define('JAVA_SYSTEM_ERP', 'http://rest.dev.java.yibainetworklocal.com/erp/');// JAVA接口ERP
    defined('JAVA_SYSTEM_CHANG_SKU')    OR define('JAVA_SYSTEM_CHANG_SKU', 'http://192.168.31.147:9095/');// 同步新系统修改的SKU信息
    defined('ERP_DOMAIN')               OR define('ERP_DOMAIN', 'http://192.168.71.210:30080');// ERP IP
    defined('LAN_LING_IP')              OR define('LAN_LING_IP', 'http://192.168.71.158:8080/');// 蓝凌 IP
//    defined('SERVICE_DATA_IP')          OR define('SERVICE_DATA_IP', 'http://192.168.71.152:81/');// 数据中心 IP(开发环境)
    defined('SERVICE_DATA_IP')          OR define('SERVICE_DATA_IP', 'http://192.168.71.216/');// 数据中心 IP(测试环境)
    defined('WAREHOUSE_IP')             OR define('WAREHOUSE_IP', 'http://192.168.31.136');// 仓库 IP
    defined('LOGISTICS_IP')             OR define('LOGISTICS_IP', 'http://dp.yibai-it.com:5000');// 包裹物流加急IP
    defined('UPLOAD_DOMAIN')            OR define('UPLOAD_DOMAIN','http://192.168.71.170:88');// 公共资源文件夹域名 @author Jolon
    //defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://rest.dev.java.yibainetworklocal.com');// 1688 接口ip //java测试环境
//    defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://rest.dev.java.yibainetworklocal.com');// 1688 接口ip //java测试环境
    defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://rest.dev.java.yibainetworklocal.com');// 1688 接口ip //java测试环境
    defined('OA_ACCESS_TOKEN_IP')       OR define('OA_ACCESS_TOKEN_IP', 'http://oauth.java.yibainetwork.com/');// OA 获取access_token
    defined('OA_ACCESS_TOKEN_USERPWD')  OR define('OA_ACCESS_TOKEN_USERPWD', "service:service");// OA 获取access_token账号密码
//    defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://rest.dev.java.yibainetworklocal.com');// 1688 接口ip //java开发环境
    defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://rest.dev.java.yibainetworklocal.com');// 1688 接口ip //java测试环境
//    defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://java.yibainetwork.com:5000');// 1688 接口ip //java线上
    defined('ALI_IMAGES_DOMAIN')        OR define('ALI_IMAGES_DOMAIN','https://cbu01.alicdn.com/');// 1688主图显示域名
    defined('ALIBABA_ACCOUNT_PERIOD_IP')OR define('ALIBABA_ACCOUNT_PERIOD_IP','http://47.106.254.16:9092');// JAVA 供应商所有账期授信
    defined('PLAN_SYSTEM_APP_DAL_IP')   OR define('PLAN_SYSTEM_APP_DAL_IP','http://192.168.71.170:84');// 计划系统后端IP
    defined('LAN_WEB_IP')               OR define('LAN_WEB_IP','http://lan.yibainetwork.com/');//蓝凌WEB IP
    defined('ERP_SKU_IMG_URL')          OR define('ERP_SKU_IMG_URL', 'http://images.yibainetwork.com/services/api/system/index/method/getimage/sku/');
    defined('ERP_SKU_IMG_URL_THUMBNAILS') OR define('ERP_SKU_IMG_URL_THUMBNAILS', 'http://images.yibainetwork.com/upload/image/Thumbnails/');
    defined('WMS_DOMAIN')               OR define('WMS_DOMAIN', 'http://192.168.71.217');// WMS IP
    defined('OLD_PURCHASE')             OR define('OLD_PURCHASE', 'http://192.168.71.210:30087');// 老采购系统IP
    defined('WANG_IP')                  OR define('WANG_IP', 'https://amos.alicdn.com/msg.aw?v=2&uid=');// 旺旺聊天软件
    defined('WANG_IP_IMG')              OR define('WANG_IP_IMG', 'http://amos.im.alisoft.com/online.aw?v=2&uid=');// 旺旺聊天软件图标
    defined('WAREHOUSE_INFO_IP')        OR define('WAREHOUSE_INFO_IP', 'http://rest.dev.java.yibainetworklocal.com');// 匹配中转仓规则IP
    defined('WAREHOUSE_ACCESS_TOKEN_IP')  OR define('WAREHOUSE_ACCESS_TOKEN_IP', 'http://rest.dev.java.yibainetworklocal.com');// WAREHOUSE_CODE 获取access_token
//    defined('LOGISTICS_RULE_IP')        OR define('LOGISTICS_RULE_IP', 'http://192.168.71.141:92');// 物流系统ip(开发环境)
    defined('LO    GISTICS_RULE_IP')        OR define('LOGISTICS_RULE_IP', 'http://dp.yibai-it.com:10195');// 物流系统ip(测试环境)
    defined('FINANCE_IP')               OR define('FINANCE_IP', 'http://192.168.71.128:86');// 财务系统IP
    defined('SETTLEMENT_IP')           OR define('SETTLEMENT_IP', 'http://192.168.71.132:84');// 推送结汇系统ip
    defined('COPURREJECTORDER') OR define('COPURREJECTORDER','http://192.168.71.128:83');
    defined('ACCOUNT_IP')               OR define('ACCOUNT_IP', 'https://gw.open.1688.com/openapi/http/1/system.oauth2/getToken/');// 1688主账号授权
    defined('ACCOUNT_IP_TYPE')          OR define('ACCOUNT_IP_TYPE', '?grant_type=authorization_code&need_refresh_token=true&client_id=');// 1688主账号授权
    defined('EYE_IP_HEAVEN')            OR define('EYE_IP_HEAVEN', 'http://open.api.tianyancha.com/services/open/ic/baseinfo/normal?keyword=');// 天眼查
    defined('JAVA_SYSTEM_PLAN')          OR define('JAVA_SYSTEM_PLAN', 'http://rest.dev.java.yibainetworklocal.com/mrp/');// JAVA接口计划系统
    defined('AGENT_ID')   OR define('AGENT_ID','193670347');//钉钉应用ID
    defined('DD_HOST')   OR define('DD_HOST','http://dingtalk.yibainetwork.com');//钉钉应用host
    defined('WAREHOUSE_URL')   OR define('WAREHOUSE_URL','http://rest.dev.java.yibainetworklocal.com'); // 物流系统获取仓库新HOST
    defined('OA_URL')   OR define('OA_URL','http://rest.dev.java.yibainetworklocal.com/oa'); // 物流系统获取仓库新HOST
    defined('PRODUCT_OA') OR define('PRODUCT_OA','http://rest.dev.java.yibainetworklocal.com/erp');
    defined('PRODUCT_ALIBABASUB')  OR define('PRODUCT_ALIBABASUB','http://rest.dev.java.yibainetworklocal.com/product'); // 推送1688子账号信息给JAVA
    defined('GET_JAVA_DATA')  OR define('GET_JAVA_DATA','http://rest.dev.java.yibainetworklocal.com'); // 获取JAVA 的数据URL
    defined('JAVA_API_URL') OR define('JAVA_API_URL','http://rest.dev.java.yibainetworklocal.com');//对接JAVA接口
    defined('SMC_JAVA_API_URL') OR define('SMC_JAVA_API_URL','http://rest.dev.java.yibainetworklocal.com');//对接供应商们门户网站java接口
    defined('BI_THINK_TANK_URL') OR define('BI_THINK_TANK_URL','http://120.76.117.210:8801');//对接BI智库系统
    defined('KD_BIRD_API_URL') OR define('KD_BIRD_API_URL','https://api.kdniao.com');//对接快递鸟接口
    defined('SWOOLE_SERVER') OR define('SWOOLE_SERVER','127.0.0.1');//swoole本地环境
    defined('PRODUCT_SYSTEM_IMAGE') OR define('PRODUCT_SYSTEM_IMAGE','http://192.168.71.170:91');//产品系统图片服务器
    defined('NEW_JAVA_WMS_API_URL') OR define('NEW_JAVA_WMS_API_URL','http://rest.dev.java.yibainetworklocal.com'); // JAVA 对接新仓库系统
    defined('PLAN_TO_DATA')    OR define('PLAN_TO_DATA','http://dp.yibai-it.com:10203');//  计划系统

    define('SECURITY_PUR_BIG_FILE_PATH','http://dp.yibai-it.com:10086');
    defined('PRODUCT_SKU_IMG_URL')          OR define('PRODUCT_SKU_IMG_URL', 'http://product.yibainetwork.com');
    defined('PRODUCT_SKU_IMG_URL_THUMBNAILS') OR define('PRODUCT_SKU_IMG_URL_THUMBNAILS', 'http://product.yibainetwork.com');

}elseif(CG_ENV == 'prod'){  //生产环境
    defined('OA_PURCHASE_PERSON_ID')    OR define('OA_PURCHASE_PERSON_ID', 54518313);  // 开发环境OA 供应链团队部门ID
    defined('CG_SYSTEM_WEB_FRONT_IP')   OR define('CG_SYSTEM_WEB_FRONT_IP', 'http://pms.yibainetwork.com');// 采购系统前端IP
    defined('CG_SYSTEM_APP_DAL_IP')     OR define('CG_SYSTEM_APP_DAL_IP', 'http://pms.yibainetwork.com:81');// 采购系统后端IP
    defined('OA_SYSTEM_IP')             OR define('OA_SYSTEM_IP', 'http://java.yibainetwork.com:5000');// OA系统IP
    defined('PRODUCT_SYSTEM_IP')        OR define('PRODUCT_SYSTEM_IP', 'http://192.168.10.99:9090/');// 产品系统系统IP
    defined('ERP_DOMAIN')               OR define('ERP_DOMAIN', 'http://120.78.243.154');// ERP IP
    defined('LAN_LING_IP')              OR define('LAN_LING_IP', 'http://lan.yibainetwork.com/');// 蓝凌 IP
    defined('LAN_WEB_IP')                   OR define('LAN_WEB_IP','http://lan.yibainetwork.com/');//蓝凌WEB IP
//    defined('SERVICE_DATA_IP')          OR define('SERVICE_DATA_IP', 'http://datacenter.yibainetwork.com/');// 数据中心 IP
//    defined('WAREHOUSE_IP')             OR define('WAREHOUSE_IP', 'http://wms.yibainetwork.com');// 仓库 IP
    defined('SERVICE_DATA_IP')          OR define('SERVICE_DATA_IP', 'http://192.168.71.216/');// 数据中心 IP
    defined('WAREHOUSE_IP')             OR define('WAREHOUSE_IP', 'http://1z8580573g.51mypc.cn:33335/');// 仓库 IP
    defined('UPLOAD_DOMAIN')            OR define('UPLOAD_DOMAIN','http://pms.yibainetwork.com:82');// 公共资源文件夹域名 @author Jolon
    defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://java.yibainetwork.com:5000');// 1688 接口ip //java测试环境
    defined('ALI_IMAGES_DOMAIN')        OR define('ALI_IMAGES_DOMAIN','https://cbu01.alicdn.com/');// 1688主图显示域名
    defined('OA_ACCESS_TOKEN_IP')       OR define('OA_ACCESS_TOKEN_IP', 'http://java.yibainetwork.com:9403/');// OA 获取access_token
    defined('OA_ACCESS_TOKEN_USERPWD')  OR define('OA_ACCESS_TOKEN_USERPWD', "prod_procurement:GGP4CZz2EueJzxH");// OA 获取access_token账号密码
    defined('PLAN_SYSTEM_APP_DAL_IP')   OR define('PLAN_SYSTEM_APP_DAL_IP','http://plan.yibainetwork.com:81');// 计划系统后端IP
    defined('ERP_SKU_IMG_URL')          OR define('ERP_SKU_IMG_URL', 'http://120.78.243.154/services/api/system/index/method/getimage/sku/');
    defined('WMS_DOMAIN')               OR define('WMS_DOMAIN', 'http://wms.yibainetwork.com');// WMS IP
    defined('OLD_PURCHASE')             OR define('OLD_PURCHASE', 'http://caigou.yibainetwork.com');// 老采购系统IP
    defined('WAREHOUSE_INFO_IP')        OR define('WAREHOUSE_INFO_IP', 'http://java.yibainetwork.com:5000');// 匹配中转仓规则IP
    defined('WAREHOUSE_ACCESS_TOKEN_IP')       OR define('WAREHOUSE_ACCESS_TOKEN_IP', 'http://java.yibainetwork.com');// WAREHOUSE_CODE 获取access_token
    defined('LOGISTICS_RULE_IP')        OR define('LOGISTICS_RULE_IP', 'http://192.168.71.141:91');// 物流系统ip
    defined('AGENT_ID')   OR define('AGENT_ID','193670347');//钉钉应用ID
    defined('DD_HOST')   OR define('DD_HOST','http://dingtalk.yibainetwork.com');//钉钉应用host
    defined('WAREHOUSE_URL')   OR define('WAREHOUSE_URL','http://rest.test.java.yibainetworklocal.com'); // 物流系统获取仓库新HOST
    defined('JAVA_API_URL') OR define('JAVA_API_URL','http://rest.java.yibainetwork.com');//对接JAVA接口
    defined('NEW_JAVA_WMS_API_URL') OR define('NEW_JAVA_WMS_API_URL','http://rest.wms.yibainetwork.com'); // JAVA 对接新仓库系统
}else{  //测试环境
//    defined('CG_SYSTEM_WEB_FRONT_IP')   OR define('CG_SYSTEM_WEB_FRONT_IP', 'http://192.168.71.173:86/');// 采购系统前端IP
//    defined('CG_SYSTEM_APP_DAL_IP')     OR define('CG_SYSTEM_APP_DAL_IP', 'http://192.168.71.173:85/');// 采购系统后端IP
    defined('CG_SYSTEM_WEB_FRONT_IP')   OR define('CG_SYSTEM_WEB_FRONT_IP', 'http://192.168.71.170:86/');// 采购系统前端IP
    //http://192.168.71.170:85/
    defined('CG_SYSTEM_APP_DAL_IP')     OR define('CG_SYSTEM_APP_DAL_IP', 'http://192.168.71.170:85/');// 采购系统后端IP
    defined('OA_SYSTEM_IP')             OR define('OA_SYSTEM_IP', 'http://rest.test.java.yibainetworklocal.com/oa/');// OA系统IP(开发)
    defined('OA_ACCESS_TOKEN_IP')       OR define('OA_ACCESS_TOKEN_IP', 'http://oauth.java.yibainetwork.com/');// OA 获取access_token
    defined('OA_ACCESS_TOKEN_USERPWD')  OR define('OA_ACCESS_TOKEN_USERPWD', "service:service");// OA 获取access_token账号密码
    defined('PRODUCT_SYSTEM_IP')        OR define('PRODUCT_SYSTEM_IP', 'http://rest.test.java.yibainetworklocal.com/');// 产品系统系统IP
    defined('JAVA_SYSTEM_ERP')          OR define('JAVA_SYSTEM_ERP', 'http://rest.test.java.yibainetworklocal.com/erp/');// JAVA接口ERP
    defined('ERP_DOMAIN')               OR define('ERP_DOMAIN', 'http://192.168.71.210:30080');// ERP IP
    defined('LAN_LING_IP')              OR define('LAN_LING_IP', 'http://192.168.71.158:8080/');// 蓝凌 IP
    defined('LAN_WEB_IP')               OR define('LAN_WEB_IP','http://lan.yibainetwork.com/');//蓝凌WEB IP
    defined('SERVICE_DATA_IP')          OR define('SERVICE_DATA_IP', 'http://192.168.71.216/');// 数据中心 IP
    defined('WAREHOUSE_IP')             OR define('WAREHOUSE_IP', 'http://192.168.71.216');// 仓库 IP
    defined('UPLOAD_DOMAIN')            OR define('UPLOAD_DOMAIN','http://192.168.71.173:88');// 公共资源文件夹域名 @author Jolon
//    defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://rest.java.yibainetwork.com');// 1688 接口ip
    defined('ALI_IMAGES_DOMAIN')        OR define('ALI_IMAGES_DOMAIN','https://cbu01.alicdn.com/');// 1688主图显示域名
    defined('ALIBABA_ACCOUNT_PERIOD_IP')OR define('ALIBABA_ACCOUNT_PERIOD_IP','http://47.106.254.16:9092');// JAVA 供应商所有账期授信
    defined('LOGISTICS_IP')             OR define('LOGISTICS_IP', 'http://dp.yibai-it.com:33335');// 包裹物流加急IP
    defined('ERP_SKU_IMG_URL')          OR define('ERP_SKU_IMG_URL', 'http://images.yibainetwork.com/services/api/system/index/method/getimage/sku/');
    defined('PLAN_SYSTEM_APP_DAL_IP')   OR define('PLAN_SYSTEM_APP_DAL_IP','http://192.168.71.173:84');// 计划系统后端IP
    defined('WMS_DOMAIN')               OR define('WMS_DOMAIN', 'http://dp.yibai-it.com:33335');// WMS IP  暂时推到仓库的测试地址
    defined('OLD_PURCHASE')             OR define('OLD_PURCHASE', 'http://192.168.71.210:30087');// 老采购系统IP
    //defined('WANG_IP')                  OR define('WANG_IP', 'http://www.taobao.com/webww/ww.php?ver=3&touid=');// 旺旺聊天软件
    //defined('WANG_IP_IMG')              OR define('WANG_IP_IMG', 'http://amos.alicdn.com/realonline.aw?v=2&uid=');// 旺旺聊天软件图标
    //defined('WANG_IP')                  OR define('WANG_IP', 'http://amos.im.alisoft.com/msg.aw?v=2&uid=');// 旺旺聊天软件
    defined('WANG_IP')    OR  define('WANG_IP', 'https://amos.alicdn.com/msg.aw?v=2&uid=');// 旺旺聊天软件
    defined('WANG_IP_IMG')              OR define('WANG_IP_IMG', 'http://amos.im.alisoft.com/online.aw?v=2&uid=');// 旺旺聊天软件图标
    defined('WAREHOUSE_INFO_IP')        OR define('WAREHOUSE_INFO_IP', 'http://rest.test.java.yibainetworklocal.com');// 匹配中转仓规则IP
    defined('WAREHOUSE_ACCESS_TOKEN_IP')       OR define('WAREHOUSE_ACCESS_TOKEN_IP', 'http://rest.test.java.yibainetworklocal.com');// WAREHOUSE_CODE 获取access_token
    defined('LOGISTICS_RULE_IP')        OR define('LOGISTICS_RULE_IP', 'http://dp.yibai-it.com:10195');// 物流系统ip(测试环境)
    defined('FINANCE_IP')               OR define('FINANCE_IP', 'http://192.168.71.134:86');// 财务系统IP
    defined('ACCOUNT_IP')               OR define('ACCOUNT_IP', 'https://gw.open.1688.com/openapi/http/1/system.oauth2/getToken/');// 1688主账号授权
    defined('ACCOUNT_IP_TYPE')          OR define('ACCOUNT_IP_TYPE', '?grant_type=authorization_code&need_refresh_token=true&client_id=');// 1688主账号授权
    defined('EYE_IP_HEAVEN')            OR define('EYE_IP_HEAVEN', 'http://open.api.tianyancha.com/services/open/ic/baseinfo/normal?keyword=');// 天眼查
    defined('JAVA_SYSTEM_PLAN')         OR define('JAVA_SYSTEM_PLAN', 'http://rest.test.java.yibainetworklocal.com/mrp/');// JAVA接口计划系统(测试环境)
    defined('SETTLEMENT_IP')            OR define('SETTLEMENT_IP', 'http://192.168.71.135:84');// 推送结汇系统ip
    defined('AGENT_ID')                 OR define('AGENT_ID','193670347');//钉钉应用ID
    defined('DD_HOST')                  OR define('DD_HOST','http://dingtalk.yibainetwork.com');//钉钉应用host
    defined('OA_PURCHASE_PERSON_ID')    OR define('OA_PURCHASE_PERSON_ID', 1079230); // OA
    defined('WAREHOUSE_URL')            OR define('WAREHOUSE_URL','http://rest.test.java.yibainetworklocal.com'); // 物流系统获取仓库新HOST
    defined('PRODUCT_OA')               OR define('PRODUCT_OA','http://rest.test.java.yibainetworklocal.com/erp');
    defined('PRODUCT_ALIBABASUB')       OR define('PRODUCT_ALIBABASUB','http://rest.java.yibainetworklocal.com/product'); // 推送1688子账号信息给JAVA
    defined('OA_URL')                   OR define('OA_URL','http://rest.test.java.yibainetworklocal.com/oa');
    defined('GET_JAVA_DATA')            OR define('GET_JAVA_DATA','http://rest.dev.java.yibainetworklocal.com'); // 获取JAVA 的数据URL
    defined('ERP_SKU_IMG_URL_THUMBNAILS') OR define('ERP_SKU_IMG_URL_THUMBNAILS', 'http://images.yibainetwork.com/upload/image/Thumbnails/');
    defined('JAVA_API_URL') OR define('JAVA_API_URL','http://rest.test.java.yibainetworklocal.com');//对接JAVA接口
    defined('JAVA_SYSTEM_CHANG_SKU')    OR define('JAVA_SYSTEM_CHANG_SKU', 'http://192.168.31.147:9095/');// 同步新系统修改的SKU信息
    defined('ALIBABA_ACCOUNT_IP')       OR define('ALIBABA_ACCOUNT_IP','http://rest.test.java.yibainetworklocal.com');// 1688 接口ip //java测试环境
    defined('SWOOLE_SERVER') OR define('SWOOLE_SERVER','192.168.71.170');//swoole本地环境

    defined('NEW_JAVA_WMS_API_URL') OR define('NEW_JAVA_WMS_API_URL','http://rest.test.java.yibainetworklocal.com'); // JAVA 对接新仓库系统
}


