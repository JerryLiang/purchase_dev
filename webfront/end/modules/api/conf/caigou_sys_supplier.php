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
$config['api_caigou_sys_supplier'] = array(
    '_baseUrl' => '/supplier/Supplier',
    '_listUrl' => '/index',
    '_supplierListUrl' => '/get_supplier_list',
    '_updateUrl' => '/update_supplier',
    '_updateBuyerUrl' => '/update_supplier_buyer',//批量修改采购员
    '_detailUrl' => '/get_details',//详情
    '_reviewUrl' => '/supplier_review',//审核
    '_reviewDetailUrl' => '/supplier_review_detail',//审核数据预览
    '_disableUrl' => '/supplier_disable',//禁用
    '_enableUrl' => '/supplier_enable',//启用
    '_delPaymentAccountUrl' => '/delete_payment_account',//删除供应商支付帐号表记录
    '_supplierListApi' => '/supplier_list',//
    '_createNameApi' => '/get_create_user_information',//创建人信息
    '_supplierQuotaApi' => '/get_supplier_quota',//供应商账期信息
    '_opLogUrl' => '/get_op_log_list',//操作日志
    '_opLogPrettyUrl' => '/get_op_log_pretty_list',//供应商管理操作记录优化
    '_openingBankApi'=>'/supplier_opening_bank',
    '_validateSupplier'=>'/validate_supplier_name',
    '_delSupplierImageImageUrl' => '/del_Supplier_Image', // 删除供应商图片信息
    '_getSupplierImage' => '/get_Supplier_image', // 供应商图片下载
    '_getheavenApi' => '/heaven_suppler', //天眼验证供应商
    '_listSupplierData' => '/supplier_data',
    '_getCrossApi' => '/get_cross',//跨境宝批量修改验证
    '_updateCrossApi'=>'/update_supplier_cross',//跨境宝批量修改
	'_getShopId'=>'/get_shop_id',//根据链接获取店铺id
    '_heavenRefreshSupplier'=>'/heaven_refresh_supplier',//对接天眼，刷新供应商信息
     '_historySupplierListApi'=>'/history_supplier_list',//获取sku历史供应商信息
     '_saveSupplierInfo'=>'/save_trans_info',//保存供应商信息
     '_showSupplierInfo'=>'/show_trans_info',//保存供应商信息
    '_refreshCrossBorderUrl' => '/refresh_cross_border',//手动刷新，1688是否支持跨境宝
    '_preDisableUrl' => '/pre_disable',//预禁用
    '_updateSupplierLevelUrl' => '/update_supplier_level',//更新供应商等级
    '_supplierRelationUrl'=>'/get_relation_supplier_info',//获取关联供应商信息
    '_getHistoryPaymentInfoUrl'=>'/get_history_payment_info',
    '_blackListUrl' => '/black_list',
    '_supplierOprBlackListUrl'=>'/supplier_opr_black_list',
    '_blackListDetail'=>'/black_list_detail',
    '_modifyRelationSupplierUrl'=>'/modify_relation_supplier',
    '_showAllRelationSupplierUrl'=>'/show_all_relation_supplier',
    '_updateSupplierProductLineUrl'=>'/update_supplier_product_line',
    '_add_supplier_users' => '/add_supplier_users',
    '_del_supplier_users' => '/del_supplier_users',
    '_show_supplier_users' => '/show_supplier_users',
    '_updateSupplierProductLineUrl'=>'/update_supplier_product_line',
    '_auditSupplierListUrl'        =>'/audit_supplier_list',
    '_getConfirmSkuInfoUrl'        =>'/get_confirm_sku_info',
    '_auditSupplierLevelGradeListUrl'  =>'/audit_supplier_level_grade_list',
     '_auditLevelGradeLogUrl'          =>'/get_audit_level_grade_log',
    '_levelGradeReviewUrl'             =>'/level_grade_review',
    '_modifySupplierLevelGradeUrl'             =>'/modify_supplier_level_grade',
    '_getSettlementChangeUrl'           =>'/get_settlement_change',
    '_visitListUrl' => '/supplier_visit_list',
    '_getVisitDetailInfo'=>'/get_visit_detail_info',
    '_applyVisitUrl'=>'/apply_visit',
    '_visitSupplierAuditListUrl'=>'/visit_supplier_audit_list',
    '_auditVisitSupplierUrl'=>'/audit_visit_supplier',
    '_uploadVisitReportUrl'=>'/upload_visit_report',
    '_getVisitOpLogListUrl'=>'/get_visit_op_log_list',
    '_downloadVisitReportUrl'=>'/download_visit_report',
    '_supplierVisitListCsvUrl'=>'/supplier_visit_list_csv',
    '_upd_supplier_users' => '/upd_supplier_users'




);
