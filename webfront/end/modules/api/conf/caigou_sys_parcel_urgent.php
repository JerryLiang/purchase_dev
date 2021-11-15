<?php
/**
 * Created by PhpStorm.
 * User: Jaden
 * Date: 2019/1/21
 * Time: 14:19
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_parcel_urgent'] = array(
    '_baseUrl' => '/warehouse/parcel_urgent',
    '_listUrl' => '/logistics_urgent_list',//获取列表数据
    '_importUrl' => '/logistics_urgent_import',//导入
    '_deleteUrl' => '/logistics_delete',//删除
    '_pustUrl' => '/push_logistics_data',//手动推送
    '_exportUrl' => '/logistics_urgent_export',//导出
);
