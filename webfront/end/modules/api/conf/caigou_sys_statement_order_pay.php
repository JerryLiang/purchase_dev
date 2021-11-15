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

$config['api_caigou_sys_statement_order_pay'] = array(
    '_baseUrl'                                    => '/finance/statement_order_pay',
    '_statementPayOrderPreview'                   => '/statement_pay_order_preview',
    '_statementPayOrderPreviewRequisitionPayment' => '/statement_pay_order_preview_requisition_payment',
    '_statementPayOrderCreate'                    => '/statement_pay_order_create',
    '_payStatementDetail'                         => '/pay_statement_detail',

);
