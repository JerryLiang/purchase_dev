<?php
/**
 * Created by PhpStorm.
 * User: Jaxton
 * Date: 2019/1/21
 * Time: 14:19
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_compact_list'] = array(
    '_baseUrl' => '/compact/Compact',
    '_getCompactByIdUrl' => '/get_compact_by_id',//根据合同ID获取合同信息
    '_oneKeyCompactCreateUrl' => '/one_key_compact_create',//一键生成进货单（支持勾选和查询条件）
    '_listUrl' => '/get_compact',//获取列表数据
    '_detailUrl' => '/get_compact_detail',//获取详情
    '_downloadUrl' => '/get_download_compact_file',//下载
    '_uploadUrl' => '/upload_compact_file_conserve',//保存上传的文件路径
    '_uploadCompactOriginalScanFileUrl' => '/upload_compact_original_scan_file',//保存上传的文件路径
    '_batchUploadCompactOriginalScanFileUrl' => '/batch_upload_compact_original_scan_file',//保存上传的文件路径
    '_requisitionUrl' => '/get_pay_requisition',//获取付款申请书数据
    '_receiptUrl' => '/get_pay_receipt',//获取付款回单
    '_printCompactUrl' => '/print_compact',//获取打印合同数据
    '_printCompactTmpUrl' =>'/print_compact_tmp',//获取打印合同模板
    '_downloadCompactUrl' => '/download_compact',//下载合同
    '_requisition_seeApi' => '/pay_requisition_see',//查看付款申请书
    '_detail_file_seeApi' => '/get_compact_detail_file',//根据合同单号获取合同的付款申请书与合同扫描件
    '_downloadExcelCompactUrl' => '/download_compact_html', // 下载EXCEL合同
    '_compactAuditLogUrl' => '/get_web_info_log', //供应商门户-合同审核日志
    '_auditCompactStatusUrl' => '/audit_compact_status', // 供应商门户-合同审核状态
    '_getCompactExportUrl' => '/get_compact_export', // 合同列表导出
    '_batchDownloadCompact' => '/batch_download_compact', // 批量下载合同
);
