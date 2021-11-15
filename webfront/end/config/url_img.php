<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


if(CG_ENV == 'dev'){//开发环境
    $config['purchasing_order_audit'] = FALSE;//true 走蓝凌系统  FALSE 走采购系统
    $config['access_token']="http://oauth.dev.java.yibainetworklocal.com/oauth/token?grant_type=client_credentials"; //获取access_token
    $config['url_img']='http://rest.dev.java.yibainetworklocal.com/file/file/upload/batch';//图片服务地址
    $config['file_img']='http://rest.dev.java.yibainetworklocal.com/file/file/upload/batch';//图片服务地址

}elseif(CG_ENV == 'prod'){  //生产环境

    $config['purchasing_order_audit'] = FALSE;//true 走蓝凌系统  FALSE 走采购系统
    $config['access_token']="http://oauth.dev.java.yibainetworklocal.com/oauth/token?grant_type=client_credentials"; //获取access_token
    //$config['url_img']='http://java.yibainetwork.com:5000/file/file/upload/image';//图片服务地址
    $config['url_img']='http://rest.dev.java.yibainetworklocal.com/file/file/upload/batch';//图片服务地址
    $config['file_img']='http://rest.dev.java.yibainetworklocal.com/file/file/upload/batch';//文件服务地址


}else{  //测试环境
    $config['purchasing_order_audit'] = FALSE;//true 走蓝凌系统  FALSE 走采购系统
    $config['access_token']="http://oauth.dev.java.yibainetworklocal.com/oauth/token?grant_type=client_credentials"; //获取access_token
    $config['url_img']='http://rest.dev.java.yibainetworklocal.com/file/file/upload/image';//图片接口地址
    $config['file_img']='http://rest.dev.java.yibainetworklocal.com/file/file/upload/batch';//文件服务地址

}

