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

$config['api_caigou_sys_reduced_edition'] = array(
    '_baseUrl' => '/purchase/Reduced_edition',
    '_listUrl' => '/sku_reduced_edition_list',//获取列表数据
    '_exportUrl' => '/reduced_export',//导出
    '_purchaseskuUrl' => '/get_sku_purchase_list',//数据导出
    '_get_reduced_data' => '/get_reduced_data', // SKU 降本优化记录
    '_get_reduced_config' =>'/get_reduced_config', //SKU 配置信息
    '_update_reduced_config' => '/update_reduced_config', // 更新SKU 信息
    '_get_reduced_log' => '/get_reduced_log', // sku 降本日志读取
    '_get_reduced_list' =>'/get_reduced_list', // SKU降本新版
    '_get_reduced_detail' =>'/get_reduced_detail', // SKU 降本明细
    '_reduced_export_data' => '/reduced_export_data', // 导出
    '_get_set_reduced_data_log' => '/get_set_reduced_data_log', // SKU 降本新老模块数据操作日志
    '_set_reduced_data' => '/set_reduced_data', //设置SKU 降本新老模块数据操作
    //'_examineUrl' => '/examine',//审核
//    '_confirmUrl' => '/confirm',//更新数据
//    '_meterialUrl' => '/get_meterial',//更新数据
);
