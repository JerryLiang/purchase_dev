<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

return $config['api_caigou_sys_warehouse'] = [
    "_baseUrl" => "/warehouse/Warehouse",
    '_getwarehousedata' => '/get_warehouse_data', // 刷新仓库数据
    '_get_warehouse_list'=>'/get_warehouse_list', // 获取仓库信息
    '_set_warehouse_address' => '/set_warehouse_address', // 设置仓库地址
    '_get_warehouse_log' => '/get_warehouse_log',
    '_get_fright_rule' => '/get_fright_rule',//获取仓库参考运费配置
    'create_fright_rule' => '/create_fright_rule',//设置仓库参考运费配置
    'set_fright_rule_batch' => '/set_fright_rule_batch',//批量设置仓库参考运费配置
];
