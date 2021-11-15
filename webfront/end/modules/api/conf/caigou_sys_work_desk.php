<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/17
 * Time: 16:44
 */

$config['api_caigou_sys_purchase_inventory_items'] = [
    '_baseUrl' => '/work_desk/',
    '_listUrl' => 'get_data_list',                                           //列表数据接口
    '_getCategoryUrl' => 'get_category_list',                              //获取小组类型
    '_getGroupUrl' => 'get_group_by_category',                              //根据小组类型获取小组接口
    '_getBuyerUrl' => 'get_buyer_by_group',                                    //根据小组id获取采购员接口
    '_refreshDataUrl' => 'refresh_data',                                    //各个模块数据刷新接口
];