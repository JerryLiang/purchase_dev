<?php
/**
 * @Author:
 * @Date:   2018-12-13 13:39:45
 * @Last Modified by:   anchen
 * @Last Modified time: 2019-01-05 16:00:16
 */

//定义环境的变量 test 测试 ,   prod：生产环境, dev : 开发者环境
defined('CG_ENV') OR define('CG_ENV', 'dev');//test, production,dev

/************************************* 接口主机名定义 *************************************/
if( CG_ENV == 'dev' )  //开发环境
{
    defined('CG_API_HOST_CAIGOU_SYS')  OR define('CG_API_HOST_CAIGOU_SYS', 'http://192.168.71.170:85');          // 开发环境物流系统后台管理服务地址(http://192.168.71.170:85)

} elseif (CG_ENV == 'prod') {  //生产环境

    defined('CG_API_HOST_CAIGOU_SYS')  OR define('CG_API_HOST_CAIGOU_SYS', 'http://www.cg2.com');          // 生产环境物流系统后台管理服务地址

} else {  //测试环境
    defined('CG_API_HOST_CAIGOU_SYS')  OR define('CG_API_HOST_CAIGOU_SYS', 'http://192.168.71.173:85');          // 测试环境物流系统后台管理服务地址

}

$config['__host_config__'] = ''; // 添加该行代码，以防止在autoload.php中自动加载该文件时报错。