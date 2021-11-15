<?php
/**
 * 全局配置文件
 * @author:凌云
 * @since: 20180921
 *
 *
 */
//define('UPLOAD_DOMAIN','');// 公共资源文件夹域名 @author Jolon
defined('UPLOAD_DOMAIN')    OR define('UPLOAD_DOMAIN','http://192.168.71.170:88');// 公共资源文件夹域名 @author Jolon

define('RSA_VALIDATE',false);
defined('CG_ENV') OR define('CG_ENV', 'dev');//test, prod,dev

//权限路径
define('SECURITY_PATH','http://192.168.71.128:83');

//权限API路径
define('SECURITY_API_HOST','http://192.168.71.128');

// 上传大文件服务器
define('SECURITY_PUR_BIG_FILE_PATH','http://192.168.71.173:86');

//权限加密串
define('SECURITY_API_SECRET','123456');

//权限APPID
define('SECURITY_APP_ID',15);

//登录IP验证
define('IP_VALIDATE',false);
//是开启登录拦截
define('LOGIN_VALIDATE',false);

if(CG_ENV=='dev') {

    defined('UPLOAD_DOMAIN_TEMPPATH')    OR define('UPLOAD_DOMAIN_TEMPPATH','http://192.168.71.170:86');// 模板文件  LUXU
}else if(CG_ENV=='prod') {

    defined('UPLOAD_DOMAIN_TEMPPATH')    OR define('UPLOAD_DOMAIN_TEMPPATH','http://pms.yibainetwork.com');//  模板文件  LUXU
}else{

    defined('UPLOAD_DOMAIN_TEMPPATH')    OR define('UPLOAD_DOMAIN_TEMPPATH','http://192.168.71.173:86');// 模板文件  LUXU
}