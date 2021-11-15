<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/1/29
 * Time: 15:45
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_alternative_suppliers'] = array(
    '_baseUrl' => '/product/alternative_suppliers',
    '_add_alternative_supplier' => '/add_alternative_supplier', // 添加备选供应商
    '_get_alternative_supplier' => '/get_alternative_supplier', // 查询备选供应商信息
    '_save_alternative_supplier' => '/save_alternative_supplier', // 修改备选供应商信息
    '_alternative_supplier_examine' => '/alternative_supplier_examine', // 备选供应商审核列表
    '_audit_alternative_supplier' => '/audit_alternative_supplier', // 备选供应商审核接口
    '_get_alternative_log' =>'/get_alternative_log', // 备选供应商日志接口
    '_alternative_import' => '/alternative_import', // 备选供应商导出
    '_get_alternative_boxdata' => '/get_alternative_boxdata'
);
