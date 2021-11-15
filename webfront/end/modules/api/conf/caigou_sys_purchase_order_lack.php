<?php
/**
 * Created by PhpStorm.
 * User: Jackson
 * Date: 2019/1/21
 * Time: 14:19
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_purchase_order_lack'] = array(
    '_baseUrl' => '/purchase/Purchase_order_lack',
    '_saveConfigData' => '/saveConfigData',
    '_getConfigData'  => '/getConfigData',
    '_getLackData'    => '/getLackData',
    '_setBatches'     => '/setBatches',
    '_setMoney'       => '/setMoney',
    '_getLogs'        => '/getLogs',
    '_setLockMessage' => '/setLockMessage',
    '_exportData_csv' => '/exportData_csv'

);
