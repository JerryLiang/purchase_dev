<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/11/29
 * Time: 22:28
 */

$config['api_caigou_sys_check_product'] = [
    '_baseUrl' => '/supplier/check',
    '_ruleListUrl' => '/rule_config/get_data_list',//验货规则列表数据接口
    '_ruleDataUrl' => '/rule_config/get_edit_data',//验货规则编辑数据接口
    '_ruleEditUrl' => '/rule_config/rule_batch_edit',//验货规则编辑接口
    '_listUrl' => '/check_product/get_data_list',//列表数据接口
    '_createInspectionUrl' => '/check_product/create_inspection',//创建验货单接口
    '_orderDetailUrl' => '/check_product/get_order_detail',//根据验货ID获取数据（采购确认和编辑页面展示数据）接口
    '_poDetailUrl' => '/check_product/get_po_detail',//获取PO详情
    '_checkPoStatusUrl' => '/check_product/check_po_status',//检查状态接口
    '_orderConfirmUrl' => '/check_product/order_confirm',//采购确认接口
    '_exportUrl' => '/check_product/data_list_export',//导出接口
    '_orderEditUrl' => '/check_product/order_edit',//编辑接口
    '_qualifyForApplyUrl' => '/check_product/qualify_for_apply',//转合格申请接口
    '_makeOrderInvalidUrl' => '/check_product/make_order_invalid',//作废验货单
    '_getLogUrl' => '/check_product/get_log',//操作日志
    '_getReportUrl' => '/check_product/get_report',//获取验货报告
];