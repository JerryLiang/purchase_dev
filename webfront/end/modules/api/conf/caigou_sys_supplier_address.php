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

$config['api_caigou_sys_supplier_address'] = array(
    '_baseUrl' => '/supplier/Supplier_address',
    '_listUrl' => '/get_address',
    '_AddressUrl' => '/get_address_list',
    '_getDropDownListUrl' => '/get_drop_down_list',//下拉
//    '_detailUrl' => '/detail',
//    '_editUrl' => '/addOrEdit',
//    '_addUrl' => '/addOrEdit',
//    '_dropUrl' => '/deleteById',
);
