<?php
/**
 * 光大银行接口配置
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2020-10-16
 * Time: 9:49
 */

/*********************** 光大银行 配置 ***********************/

// 开发环境
$config['cebbank']['agencyHost']   = 'http://192.168.31.137:8000';
$config['cebbank']['usrID']        = '2450708155';// usrID为客户号
$config['cebbank']['userPassword'] = '123456';// userPassword为通讯密码，即企业客户端与安全代理csiiproxy通讯的密码
$config['cebbank']['Sigdata']      = 1;
$config['cebbank']['OPERUserID']   = '002';// UserID为操作员号
$config['cebbank']['OPERActNo']    = '35500188068586493';// ActNo为操作员号对应的账户
$config['cebbank']['b2e004001']    = "/ent/b2e004001.do";// 单笔转账
$config['cebbank']['b2e004003']    = "/ent/b2e004003.do";// 单笔转账查证
$config['cebbank']['b2e005023']    = "/ent/b2e005023.do";// 电子回单查询
$config['cebbank']['downloadFile'] = "/ent/downloadFile.do";// 电子回单下载



defined('CEBBANK_DOWNLOADS_FILEPATH') OR define('CEBBANK_DOWNLOADS_FILEPATH', "D:\\workspace\\cebbank\\downloads\\");

