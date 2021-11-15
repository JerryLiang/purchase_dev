<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/24
 * Time: 15:04
 */

$config['api_caigou_sys_charge_against'] = [
    '_baseUrl' => '/statement/Charge_against/',
    '_listUrl' => 'get_summary_data_list',                                                                 //列表数据接口
    '_exportUrl' => 'data_list_export',                                                                    //导出接口
    '_getChargeAgainstListUrl' => 'get_charge_against_list',                                               //其他冲销审核列表
    '_inventoryItemChargeAgainstUrl' => 'inventory_item_charge_against',                                   //采购单 与 入库批次进行冲销 - 自动冲销
    '_refundChargeAgainstViewUrl' => 'refund_charge_against_view',                                         //采购单 与 取消未到货退款 进行冲销 - 显示
    '_getAbleCaAmountUrl' => 'get_able_ca_amount',                                                         //采购单 与 取消未到货退款 进行冲销 - 显示 -获取采购单剩余可冲销商品金额
    '_refundChargeAgainstSaveUrl' => 'refund_charge_against_save',                                         //采购单 与 取消未到货退款 进行冲销 - 保存
    '_auditUrl' => 'charge_against_audit',                                                                 //采购经理审核 - 预览 & 提交,财务经理审核 - 预览 & 提交
    '_viewChargeAgainstUrl' => 'view_charge_against',                                                      //查看冲销 - （与 采购单冲销汇总表 数据 对应）
    '_viewChargeAgainstLogsUrl' => 'view_charge_against_logs',                                             //查看冲销操作日志
    '_caListExportUrl' => 'charge_against_list_export',                                                    //其他冲销审核列表导出接口
];