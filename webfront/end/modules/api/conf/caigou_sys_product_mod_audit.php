<?php
/**
 * Created by PhpStorm.
 * User: Jaxton
 * Date: 2019/1/29
 * Time: 15:45
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_product_mod_audit'] = array(
    '_baseUrl' => '/product/product_mod_audit',
    '_listUrl' => '/get_product_list',//获取列表数据
    '_audit_handleUrl' => '/product_audit',//审核
    '_audit_export' => '/product_audit_export',//审核
    '_get_product_log' => '/get_product_log',//修改单价日志
    // '_rejectUrl' => '/abnormal_reject',//驳回
    // '_lookUrl' => '/look_abnormal',//查看
    // '_provinceUrl' => '/get_province',//获取省列表
    // '_city_countyUrl' => '/get_city_county',//获取市(区)
    '_getProductAuditLog' => '/getProductAuditLog',
    '_get_drop_box' => '/get_drop_box',
    '_get_supplier_avg' => '/get_supplier_avg'

);
