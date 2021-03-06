<?php
/**
 * Created by PhpStorm.
 * User: Jaxton
 * Date: 2019/1/29
 * Time: 17:15
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_report_loss'] = array(
    '_baseUrl' => '/abnormal/report_loss',
    '_listUrl' => '/get_report_loss_list',//获取列表数据
    '_approvalUrl' => '/approval',//弹出审核页面
    '_approval_handleUrl' => '/approval_handle',//审核提交
    '_exportUrl' => '/get_export_report_loss_data',//导出
    '_previewEditDataUrl' => '/preview_edit_data',//编辑报损数据预览
    '_editReportLossUrl' => '/edit_report_loss',//编辑报损
    // '_provinceUrl' => '/get_province',//获取省列表
    // '_city_countyUrl' => '/get_city_county',//获取市(区)
    '_listSumUrl' => '/get_report_loss_list_sum',
    '_batchApprovalHandleUrl'=>'/batch_approval_handle'

);
