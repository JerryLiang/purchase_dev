<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/5
 * Time: 11:06
 */

$config['api_caigou_sys_purchaser_return_after_storage'] = array(
// ****************** 申请明细start ******************
    '_baseUrl' => '/purchase/Purchase_return_after_storage',
    '_applyImportUrl' => '/apply_import',//申请明细-导入
    '_applyListUrl' => '/apply_list',//申请明细-列表
    '_applyPurchaseConfirmDetailUrl' => '/apply_purchase_confirm_detail',//申请明细-采购确认详情
    '_applyPurchaseConfirmUrl' => '/apply_purchase_confirm',//申请明细-采购确认
    '_applyPurchaseRejectUrl' => '/apply_purchase_reject',//申请明细-采购驳回
    '_applyExportUrl' => '/apply_export',//申请明细-导出
    '_getSupplierContactUrl' => '/get_supplier_contact',//供应商联系信息
    '_downloadImportTemplateUrl' => '/download_import_template',//申请明细-导入模板下载
    '_applyExportExcelUrl' => '/apply_export_excel',//申请明细-导出

// ****************** 申请明细end ******************
// ****************** 采购确认明细start ******************
    '_confirmListUrl' => '/confirm_list',//采购确认明细列表
    '_confirmPurchaseRejectUrl' => '/confirm_purchase_reject',//采购确认明细-采购驳回
    '_confirmPurchasingManagerAuditUrl' => '/confirm_purchasing_manager_audit',//采购确认明细-采购经理审核
    '_getLogListUrl' => '/get_log_list',//采购确认明细-日志列表
    '_confirmExportUrl' => '/confirm_export',//采购确认明细-导出
    '_confirmExportExcelUrl' => '/confirm_export_excel',//采购确认明细-导出

// ****************** 采购确认明细end ******************

// ****************** 退货跟踪start ******************
// ****************** 退货跟踪end ******************
);
