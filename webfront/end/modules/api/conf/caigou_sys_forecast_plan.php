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

$config['api_caigou_sys_forecast_plan'] = array(
    '_baseUrl' => '/purchase_suggest/forecast_plan',
    '_listUrl' => '/get_forecast_list',//获取列表数据
    '_addUrl' => '/add_feedback',//反馈
    '_feedback_listUrl' => '/get_feedback_list',//获取反馈记录
    // '_lookUrl' => '/look_abnormal',//查看
    // '_provinceUrl' => '/get_province',//获取省列表
    // '_city_countyUrl' => '/get_city_county',//获取市(区)
);
