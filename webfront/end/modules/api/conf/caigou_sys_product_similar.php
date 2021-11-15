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

$config['api_caigou_sys_product_similar'] = array(
    '_baseUrl' => '/product/product_similar',
    '_getSimilarConfigUrl' => '/get_similar_config',
    '_saveSimilarConfigUrl' => '/save_similar_config',
    '_getSimilarListUrl' => '/get_similar_list',
    '_getSimilarDetailUrl' => '/get_similar_detail',
    '_delete_similarUrl' => '/delete_similar',
    '_allotUserSimilarUrl' => '/allot_user_similar',
    '_auditSimilarUrl' => '/audit_similar',
    '_getSimilarLogsUrl' => '/get_similar_logs',
    '_getSimilarListoryDetailUrl' => '/get_similar_history_detail',
    '_similarExportUrl' => '/similar_export',
);
