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

$config['api_Abnormal_quality_list'] = array(
    '_baseUrl' => '/abnormal/Abnormal_quality_list',
    '_get_Abnormal_list_data' =>'/get_Abnormal_list_data',
    '_add_Abnoral_list_data' =>'/add_Abnoral_list_data',
    '_handler_Abnoral_list_data' => '/handler_Abnoral_list_data',
    '_Abnoral_log' => '/Abnoral_log',
    '_import_Abnormal_list_data' => '/import_Abnormal_list_data',
    '_push_import_Abnormal_list_data' => '/push_import_Abnormal_list_data'


);
