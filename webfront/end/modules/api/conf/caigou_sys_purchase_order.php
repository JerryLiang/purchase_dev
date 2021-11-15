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

$config['api_caigou_sys_purchase_order'] = array(
    '_baseUrl' => '/purchase/Purchase_order',
    '_searchUrl' => '/get_status_lists',//获取采购单搜索部分
    '_listUrl'=>'/get_order_list',//采购单列表
    '_exportUrl' => '/export',//导出
    '_historytUrl' => '/get_order_history',//历史数据
    '_informationdeUrl' => '/get_confirmation_information',//确认提交信息
    '_revokeUrl' => '/get_revoke_order',//撤销提交的信息
    '_tableUrl' => '/get_table_boy',//编辑显示采购单列表字段显示
    '_tablesaveUrl' => '/get_table_save',//编辑显示采购单列表字段显示
    '_getTableSearchHeader' => '/get_table_search_header',//采购单列表搜索框配置-查看
    '_saveTableSearchHeader' => '/save_table_search_header',//采购单列表搜索框配置-保存
     '_logisticlistsUrl'=>'/order_binding_logistics',//保存物流单号相关信息
    '_logisticsUrl'=>'/order_binding_logistics_save',//保存物流单号相关信息
    '_printingUrl'=>'/printing_purchase_order',//打印采购单
    '_print_menuUrl'=>'/print_menu',//打印采购单返回数据
    '_confirmPurchaseUrl' => '/compact_confirm_purchase',//合同单信息确认
    '_confirmTemplateUrl' => '/compact_confirm_template',//合同模板确认
    '_compactCreatUrl' => '/compact_create',//创建合同
    '_rejectOrderApi' => '/reject_order',//采购单驳回
    '_cancelOrderApi' => '/cancel_order',//采购单作废
    '_auditOrderApi' => '/audit_order',//采购单审核
    '_auditsOrderApi' => '/audit_orders',//审核蓝凌
    '_getoperator_logUrl' => '/get_purchase_operator_log',//获取操作日志
    '_get_reportloss_skuUrl' => '/get_reportloss_sku_data',//获取报损SKU数据
    '_reportloss_submitUrl' => '/reportloss_submit',//报损提交
    '_buyernameUrl'=>'/get_buyer', //获取采购员下拉
    '_getBatchEditOrderUrl' => '/get_batch_edit_order',// 批量编辑采购单
    '_saveBatchEditOrderUrl' => '/save_batch_edit_order',// 批量编辑采购单-保存
    '_getLogisticsTraceInfo' => '/get_logistics_trace_info',// 获取交易订单的物流跟踪信息
    '_batch_audit_orderApi' => '/batch_audit_order_list',// 采购批量审核显示
    '_batch_audit_order_saveApi' => '/batch_audit_order_save',// 采购批量审核保存
    '_no_payment_saveApi' => '/no_payment_save',// 无需付款操作
    '_getSetTableHeaderApi' => '/get_set_table_header',// 获取列表表头字段
    '_saveTableListApi' => '/save_table_list',// 保存排序列表表头字段
    '_getNoteListApi' => '/get_note_list',// 获取各种备注
    '_freshAliOrderAbnormalApi' => '/fresh_ali_order_abnormal',// 手动刷新1688订单是否异常
    '_batchAuditDataChangeOrderUrl' => '/batch_audit_data_change_order',// 批量编辑信息修改审核状态采购单-显示
    '_getChangeOrderPreview' => '/get_change_order_preview',// 批量编辑信息修改审核状态采购单-显示 新
    '_saveChangeOrderPreview' => '/save_change_order_preview',// 批量编辑信息修改审核状态采购单-显示 新
    '_batchAuditDataChangeSaveUrl' => '/batch_audit_data_change_save',// 批量编辑信息修改审核状态采购单-保存
    '_changeOrderDataPreviewUrl' => '/change_order_data_preview',// 非1688下单订单信息修改-预览
    '_changeOrderDataSaveUrl' => '/change_order_data_save',// 非1688下单订单信息修改-保存
    '_cancelOrderByDemandNumberApi' => '/cancel_order_by_demand_number',//作废采购单,以备货单维度
    '_sum' => '/get_order_sum',  // 采购单数据统计
    '_settlementListUrl' => '/get_settlement_list',  // 获取供应商结算方式下拉列表
    '_pertainWmsListUrl' => '/get_pertain_wms',  // 获取公共仓下拉列表
    '_editAliOrderDataPreviewUrl' => '/edit_ali_order_data_preview',// 1688下单订单信息修改-预览
    '_editAliOrderDataSaveUrl' => '/edit_ali_order_data_save',// 1688下单订单信息修改-保存
    '_get_purchase_progress'   => '/get_purchase_progress', // 订单状态跟踪
    '_import_progress'      => '/import_progress', // 订单追踪导入

    '_conStatementConfirmUrl' => '/create_statement_confirm',//创建对账单
    '_progressExport' => '/progress_export', // 订单追踪导出
    '_get_progess_history' => '/get_progess_history', // 订单追踪历史记录
    '_import_progess_excel' => '/progress_export_excel', // 订单追踪导出EXCEl
    '_purchase_buyer' => '/update_purchase_data',
    '_get_purchase_oa' => '/get_purchase_oa',
    '_downloadPurchaseOrderUrl' => '/download_purchase_order',//下载PDF采购单
    '_get_sku_message' => '/get_sku_message', // 获取SKU 信息
    '_batch_add_remark' => '/batch_add_remark',// 其他按钮-批量添加备注

    '_getFirstProductLineUrl' => '/get_first_product_line',//获取一级产品线下拉

    '_getCalculateOrderFreight' => '/get_calculate_order_freight',// 计算采购单的参考运费
    '_getAuditArriveStatus' => '/audit_arrive_time_status',//供应商门户-预计到货时间审核
    '_getAuditArriveLog' => '/get_audit_arrive_log',//供应商门户-预计到说时间审核日志
    '_getAuditInfo' => '/get_arrive_audit_info',//供应商门户-预计到说时间审核日志
    '_get_purchase_progress_total' => '/get_purchase_progress_total', //订单追踪统计
    '_getViewData' => '/getViewData', // 采购单预览数据
    '_getShipmentsQty' => '/getShipmentsQty',
    '_downloadPurchaseDeliveryNote' => '/download_purchase_delivery_note', // 下载发货单
    '_aliOrderRefreshPurchaseSDP' => '/ali_order_refresh_purchase_SDP', // 从1688获取数据，批量刷新PO运费、优惠金额、总金额

    '_getGroupName' => '/getGrupData',
    '_getMudeCode'  => '/getMudeCode',

    '_getGroupName' => '/getGrupData',
    '_getOrderPaymentPay' => "/get_order_payment_pay", // （批量）编辑采购单-切换采购单是否退税数据
    '_getOrderSkuAllocationInfo' => "/get_order_sku_allocation_info", // 获取多货调拨信息
    '_getSaveOrderSkuAllocation' => "/save_order_sku_allocation", // 获取多货调拨信息

    '_getUrgeSendOrder' => "/urge_send_order", // 催发货
    '_getUrgeChangeOrderPrice' => "/urge_change_order_price", // 催发货
    '_saveImitatePurchaseInstock' => "/save_imitate_purchase_instock", // 保存虚拟入库
    '_getImitatePurchaseInstock' => "/get_imitate_purchase_instock", // 获取虚拟入库
    '_getLogisticsType' => "/get_logistics_type", // 获取物流类型枚举值

);
