<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2020/05/04
 * Time: 11:06
 */

$config['api_caigou_sys_statement'] = array(
    '_baseUrl'                   => '/statement/purchase_statement',
    '_createStatementPreviewUrl' => '/create_statement_preview',//创建对账单(第一步)
    '_createStatementUrl'        => '/create_statement',//入库批次 创建对账单（第二步）
    '_getStatementListUrl'       => '/get_statement_list',//对账单管理列表
    '_setStatusValidUrl'         => '/set_status_valid',//设置 对账单 是否有效
    '_printStatementUrl'         => '/print_statement',//打印对账单
    '_previewStatementDetailUrl' => '/preview_statement_detail',//查看对账单详情
    '_uploadStatementPdfUrl'     => '/upload_statement_pdf',//上传扫描件
    '_batchUploadStatementPdfUrl'=> '/batch_upload_statement_pdf',//批量上传扫描件
    '_downloadStatementHtml'     => '/download_statement_html',// 获取下单文件的 HTML 内容
    '_downloadStatementExcel'     => '/download_statement_excel',// 获取下单文件的 HTML 内容
    '_confirmStatementPdfUrl'     => '/confirm_statement_pdf',// 采购确认扫描件
    '_downloadStatementHtmlCron'  => '/Statement_gateway_api/get_statement_pdf_html',// 获取下单文件的 HTML 内容
    '_getOperationPdfLogsUrl'     => '/get_operation_pdf_logs',// 查看上传pdf操作日志
    '_getStatementPayRequisition' => '/get_statement_pay_requisition',// 下载付款申请书
    '_batchDownloadStatement'     => '/batch_download_statement',// 批量下载对账单
    '_statementExportCsv'         => '/statement_export_csv',// 批量下载对账单csv
    '_download_freight_details'     => '/download_freight_details',// 批量下载对账单

    '_initiatorStartFlow'         => '/a_initiator_start_flow',// 甲方先盖章（甲方发起盖章）
    '_statementAudit'             => '/a_statement_audit',// 甲方对账人审核
    '_signfieldsFlow'             => '/a_signfields_flow',// 甲方盖章
    '_signflowsRushSign'          => '/a_signflows_rushsign',// 甲方催办
    '_signflowsRevoke'            => '/a_signflows_revoke',// 甲方撤销
    '_uploadAttachmentPdf'        => '/upload_attachment_pdf',// 上传附属文件
);
