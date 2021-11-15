<?php
/**
 * Created by PhpStorm.
 * User: Jackson
 * Date: 2019/1/21
 * Time: 14:19
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_purchase_invoice'] = array(
    '_baseUrl' => '/purchase/Purchase_invoice_list',
    '_listUrl' => '/get_invoice_listing_list',//获取列表数据
    '_submitDetailUrl' => '/submit_detail',//发票清单列表提交弹出列表
    '_financialUrl' => '/submit_financial_audit_invoice_list',//发票清单列表财务审核弹出列表
    '_submitUrl' => '/submit_invoice',//发票清单提交
    '_batchSubmitUrl' => '/batch_submit',//发票清单批量提交
    '_revokeUrl' => '/revoke_invoice',//发票清单撤销
    '_downloadexportUrl' => '/download_export',//下载发票明细
    '_downloadviewUrl' => '/download_view',//下载开票合同页面
    '_downloadimportUrl' => '/download_import',//导入
    '_downloadimportmodelUrl' => '/download_import_model',//下载开票合同页面(下载模板)
    '_invoiceDetailUrl' => '/get_batch_invoice_detail',//批量开票详情页
    '_batchInvoiceSubmitUrl' => '/batch_invoice_submit',//批量开票提交
    '_importInvoiceInfoUrl' => '/import_invoice_info',//上传开票信息
    '_downloadInvoiceExcelUrl' => '/download_invoice_excel',//上传开票信息
    '_exportListUrl' => '/export_list',//导出
);
