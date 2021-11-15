<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/12/24
 * Time: 15:45
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_ali_order_advanced'] = array(
    '_baseUrl'                   => '/ali/ali_order_advanced',
    '_oneKeyCreateOrder'         => '/one_key_create_order',
    '_advancedOneKeyCreateOrder' => '/advanced_one_key_create_order',
    '_advancedOneKeyPayout'      => '/advanced_one_key_payout',
);
