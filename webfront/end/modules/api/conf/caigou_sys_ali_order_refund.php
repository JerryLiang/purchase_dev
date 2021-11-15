<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/12/24
 * Time: 15:45
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_ali_order_refund'] = array(
    '_baseUrl'                   => '/ali/Ali_order_refund',
    '_getOrderRefundData'        => '/get_order_refund_data',
    '_saveOrderRefundData'       => '/save_order_refund_data',
    '_saveOrderRefundReason'     => '/get_order_refund_reason',
    '_getOrderRefundList'        => '/get_order_refund_list',
);
