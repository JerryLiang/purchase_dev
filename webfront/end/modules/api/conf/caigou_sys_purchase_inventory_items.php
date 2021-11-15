<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/17
 * Time: 16:44
 */

$config['api_caigou_sys_purchase_inventory_items'] = [
    '_baseUrl' => '/statement/Purchase_inventory_items/',
    '_listUrl' => 'get_data_list',                                //列表数据接口
    '_addRemarkUrl' => 'add_remark',                              //添加备注接口
    '_exportUrl' => 'data_list_export',                           //导出接口
];