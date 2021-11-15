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

$config['api_caigou_sys_product_incomplete'] = array(
    '_baseUrl' => '/purchase_suggest/product_incomplete',
    '_listUrl' => '/product_incomplete_list',//获取列表数据
    '_exportUrl' => '/export_product_incomplete',//导出
    '_purUrl' => '/put_product_incomplete_list',//数据推送
    '_createremarksUrl' => '/create_remarks',//审核

);
