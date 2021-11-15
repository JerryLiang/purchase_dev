<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Display Debug backtrace
|--------------------------------------------------------------------------
|
| If set to TRUE, a backtrace will be displayed along with php errors. If
| error_reporting is disabled, the backtrace will not display, regardless
| of this setting
|
*/
defined('SHOW_DEBUG_BACKTRACE') OR define('SHOW_DEBUG_BACKTRACE', TRUE);

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file sys.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments                                                                                                                                                                              (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
defined('FILE_READ_MODE') OR define('FILE_READ_MODE', 0644);
defined('FILE_WRITE_MODE') OR define('FILE_WRITE_MODE', 0666);
defined('DIR_READ_MODE') OR define('DIR_READ_MODE', 0755);
defined('DIR_WRITE_MODE') OR define('DIR_WRITE_MODE', 0755);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/
defined('FOPEN_READ') OR define('FOPEN_READ', 'rb');
defined('FOPEN_READ_WRITE') OR define('FOPEN_READ_WRITE', 'r+b');
defined('FOPEN_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_WRITE_CREATE_DESTRUCTIVE', 'wb'); // truncates existing file data, use with care
defined('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE') OR define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE', 'w+b'); // truncates existing file data, use with care
defined('FOPEN_WRITE_CREATE') OR define('FOPEN_WRITE_CREATE', 'ab');
defined('FOPEN_READ_WRITE_CREATE') OR define('FOPEN_READ_WRITE_CREATE', 'a+b');
defined('FOPEN_WRITE_CREATE_STRICT') OR define('FOPEN_WRITE_CREATE_STRICT', 'xb');
defined('FOPEN_READ_WRITE_CREATE_STRICT') OR define('FOPEN_READ_WRITE_CREATE_STRICT', 'x+b');

/*
|--------------------------------------------------------------------------
| Exit Status Codes
|--------------------------------------------------------------------------
|
| Used to indicate the conditions under which the script is exit()ing.
| While there is no universal standard for error codes, there are some
| broad conventions.  Three such conventions are mentioned below, for
| those who wish to make use of them.  The CodeIgniter defaults were
| chosen for the least overlap with these conventions, while still
| leaving room for others to be defined in future versions and user
| applications.
|
| The three main conventions used for determining exit status codes
| are as follows:
|
|    Standard C/C++ Library (stdlibc):
|       http://www.gnu.org/software/libc/manual/html_node/Exit-Status.html
|       (This link also contains other GNU-specific conventions)
|    BSD sysexits.h:
|       http://www.gsp.com/cgi-bin/man.cgi?section=3&topic=sysexits
|    Bash scripting:
|       http://tldp.org/LDP/abs/html/exitcodes.html
|
*/
defined('CURRENCY') OR define('CURRENCY', 'RMB'); // 币种
defined('EXIT_SUCCESS') OR define('EXIT_SUCCESS', 0); // no errors
defined('EXIT_ERROR') OR define('EXIT_ERROR', 1); // generic error
defined('EXIT_CONFIG') OR define('EXIT_CONFIG', 3); // configuration error
defined('EXIT_UNKNOWN_FILE') OR define('EXIT_UNKNOWN_FILE', 4); // file not found
defined('EXIT_UNKNOWN_CLASS') OR define('EXIT_UNKNOWN_CLASS', 5); // unknown class
defined('EXIT_UNKNOWN_METHOD') OR define('EXIT_UNKNOWN_METHOD', 6); // unknown class member
defined('EXIT_USER_INPUT') OR define('EXIT_USER_INPUT', 7); // invalid user input
defined('EXIT_DATABASE') OR define('EXIT_DATABASE', 8); // database error
defined('EXIT__AUTO_MIN') OR define('EXIT__AUTO_MIN', 9); // lowest automatically-assigned error code
defined('EXIT__AUTO_MAX') OR define('EXIT__AUTO_MAX', 125); // highest automatically-assigned error code

defined('DEFAULT_PAGE_SIZE') OR define('DEFAULT_PAGE_SIZE', 20); //默认每页10条
defined('MAX_PAGE_SIZE') OR define('MAX_PAGE_SIZE', 200); //最大每页50条

//定义请求数据的方法
defined('IS_POST') OR define('IS_POST', isset($_SERVER["REQUEST_METHOD"]) ? strtolower($_SERVER["REQUEST_METHOD"]) == 'post': false);//判断是否是post方法
defined('IS_GET') OR define('IS_GET', isset($_SERVER["REQUEST_METHOD"]) ? strtolower($_SERVER["REQUEST_METHOD"]) == 'get' : false);//判断是否是get方法
defined('IS_AJAX') OR define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');//判断是否是ajax请求

// 产品系统商品SKU 属性对应关系
defined('PRODUCT_ATTR') OR define('PRODUCT_ATTR', 1); //基础属性
defined('PRODUCT_FROM_ATTR') OR define('PRODUCT_FROM_ATTR', 40); //产品型太属性
defined('PRODUCT_SPECIAL_ATTR') OR define('PRODUCT_SPECIAL_ATTR', 2 ); //产品特殊属性
defined('PRODUCT_BATTERY_ATTR') OR define('PRODUCT_BATTERY_ATTR', 5); //产品电池属性
defined('PRODUCT_ORDER_ATTR') OR define('PRODUCT_ORDER_ATTR', 66); //订单属性

defined('PUSH_WMS_START_TIME')             OR define('PUSH_WMS_START_TIME', '2020-12-16'); // 推送仓库系统时间配置

// 少数少点配置

defined('PURCHASE_ORDER_LACK_STYLE')             OR define('PURCHASE_ORDER_LACK_STYLE', 1); // 少款
defined('PURCHASE_ORDER_LACK_ALL')             OR define('PURCHASE_ORDER_LACK_ALL', 2); // 全部到货
defined('PURCHASE_ORDER_LACK_QUE')             OR define('PURCHASE_ORDER_LACK_QUE', 3); // 少数
// PURCHASE_ORDER_LACK_ALL ,PURCHASE_ORDER_LACK_QUE

// 采购单取消未到货-取消单类型
defined('PURCHASE_CANCEL_ORDER_TYPE_PART')  OR define('PURCHASE_CANCEL_ORDER_TYPE_PART', 1); // 部分取消
defined('PURCHASE_CANCEL_ORDER_TYPE_ALL')   OR define('PURCHASE_CANCEL_ORDER_TYPE_ALL', 2); // 全部取消

//采购单-合同来源
defined('SOURCE_COMPACT_ORDER') OR define('SOURCE_COMPACT_ORDER', 1); //合同单
defined('SOURCE_NETWORK_ORDER') OR define('SOURCE_NETWORK_ORDER', 2); //网采单
defined('SOURCE_PERIOD_ORDER')  OR define('SOURCE_PERIOD_ORDER', 3); //账期采购单


//请款单-来源主体（1合同 2网采 3对账单）
defined('SOURCE_SUBJECT_COMPACT_ORDER')    OR define('SOURCE_SUBJECT_COMPACT_ORDER', 1); //合同单
defined('SOURCE_SUBJECT_NETWORK_ORDER')    OR define('SOURCE_SUBJECT_NETWORK_ORDER', 2); //网采单
defined('SOURCE_SUBJECT_STATEMENT_ORDER')  OR define('SOURCE_SUBJECT_STATEMENT_ORDER', 3); //对账单


// 合同单 合同状态
defined('COMPACT_STATUS_WAITING_DEAL')  OR define('COMPACT_STATUS_WAITING_DEAL', 10); // 待处理
defined('COMPACT_STATUS_AUDIT_PASS')    OR define('COMPACT_STATUS_AUDIT_PASS', 20); // 已审批
defined('COMPACT_STATUS_CANCEL')        OR define('COMPACT_STATUS_CANCEL', 30); // 已作废

// 商品SKU 是否为新品
defined('PRODUCT_SKU_IS_NEW')  OR define('PRODUCT_SKU_IS_NEW', 1); // 待表示是新品
defined('PRODUCT_SKU_IS_NOT_NEW')  OR define('PRODUCT_SKU_IS_NOT_NEW', 0); // 待表示不是新品

defined('DEMAND_SELLING_PRODUCTS_ID') 					 OR define('DEMAND_SELLING_PRODUCTS_ID',2); // 热销精品的ID
// 采购类型
defined('PURCHASE_TYPE_INLAND')     OR define('PURCHASE_TYPE_INLAND',1);// 国内仓
defined('PURCHASE_TYPE_OVERSEA')    OR define('PURCHASE_TYPE_OVERSEA',2);// 海外仓
defined('PURCHASE_TYPE_FBA')        OR define('PURCHASE_TYPE_FBA',3);// FBA
defined('PURCHASE_TYPE_PFB')        OR define('PURCHASE_TYPE_PFB',4);// PFB
defined('PURCHASE_TYPE_PFH')        OR define('PURCHASE_TYPE_PFH',5);// 平台头程
defined('PURCHASE_TYPE_FBA_BIG')    OR define('PURCHASE_TYPE_FBA_BIG',6);// FBA大货

// 需求单状态枚举
defined('DEMAND_SKU_STATUS_NO_LOCK')                      OR define('DEMAND_SKU_STATUS_NO_LOCK',2); // 未锁单标识
defined('DEMAND_SKU_NO_REPEAT')                           OR define('DEMAND_SKU_NO_REPEAT',2); // 需求单是否重复，未重复
defined('DEMAND_SKU_REPEAT')                              OR define('DEMAND_SKU_REPEAT',1); // SKU重复
defined('DEMAND_STATUS_NOT_FINISH')                 	     OR define('DEMAND_STATUS_NOT_FINISH', 1); // 未完结
defined('DEMAND_TO_SUGGEST')                              OR define('DEMAND_TO_SUGGEST',3); // 需求单已生成备货单
defined('DEMAND_SKU_STATUS_CONFIR')                       OR define('DEMAND_SKU_STATUS_CONFIR',4); // 待重新下单
defined('DEMAND_MERGE_SUGGEST')                           OR define('DEMAND_MERGE_SUGGEST',5); // 需求单已合单
defined('DEMAND_STATUS_FINISHED')                    	 OR define('DEMAND_STATUS_FINISHED', 6); // 已完结
defined('DEMAND_STATUS_CANCEL')                     	     OR define('DEMAND_STATUS_CANCEL', 7); // 已作废

// 是否对接门户系统
defined('SUGGEST_IS_GATEWAY_YES')        OR define('SUGGEST_IS_GATEWAY_YES',1);// 对接
defined('SUGGEST_IS_GATEWAY_NO')        OR define('SUGGEST_IS_GATEWAY_NO',0);// 不对接
// 采购订单是否含税
defined('PURCHASE_IS_INCLUDE_TAX_Y')    OR define('PURCHASE_IS_INCLUDE_TAX_Y',1);// 是
defined('PURCHASE_IS_INCLUDE_TAX_N')    OR define('PURCHASE_IS_INCLUDE_TAX_N',0);// 否

// 采购单是否退税
defined('PURCHASE_IS_DRAWBACK_Y')   OR define('PURCHASE_IS_DRAWBACK_Y',1);// 是
defined('PURCHASE_IS_DRAWBACK_N')   OR define('PURCHASE_IS_DRAWBACK_N',0);// 否

// 产品是否是新品
defined('PURCHASE_PRODUCT_IS_NEW_Y')    OR define('PURCHASE_PRODUCT_IS_NEW_Y',1);// 是
defined('PURCHASE_PRODUCT_IS_NEW_N')    OR define('PURCHASE_PRODUCT_IS_NEW_N',0);// 否

// 备货单状态
defined('SUGGEST_STATUS_NOT_FINISH')                OR define('SUGGEST_STATUS_NOT_FINISH', 1);// 未完结
defined('SUGGEST_STATUS_FINISHED')                  OR define('SUGGEST_STATUS_FINISHED', 2);// 已完结
defined('SUGGEST_STATUS_EXPIRED')                   OR define('SUGGEST_STATUS_EXPIRED', 3);// 过期
defined('SUGGEST_STATUS_CANCEL')                    OR define('SUGGEST_STATUS_CANCEL', 4);// 作废
defined('SUGGEST_STATUS_REBORN')                    OR define('SUGGEST_STATUS_REBORN', 5);// 待重新下单



// 采购需求 是否生成计划单
defined('PURCHASE_IS_PLAN_ORDER_Y') OR define('PURCHASE_IS_PLAN_ORDER_Y',1);// 是
defined('PURCHASE_IS_PLAN_ORDER_N') OR define('PURCHASE_IS_PLAN_ORDER_N',0);// 否

// 采购需求列表界面 采购单状态
defined('SUGGEST_ORDER_STATUS_N')   OR define('SUGGEST_ORDER_STATUS_N',0);// 未生成
defined('SUGGEST_ORDER_STATUS_Y')   OR define('SUGGEST_ORDER_STATUS_Y',1);// 已生成
// 采购需求-待驳回需求 列表 采购单状态
defined('SUGGEST_ORDER_STATUS_ALL_N')               OR define('SUGGEST_ORDER_STATUS_ALL_N',0);// FBA
defined('SUGGEST_ORDER_STATUS_ALL_Y')               OR define('SUGGEST_ORDER_STATUS_ALL_Y',1);// FBA
defined('SUGGEST_ORDER_STATUS_ALL_WAITING_AUDIT')   OR define('SUGGEST_ORDER_STATUS_ALL_WAITING_AUDIT',2);// 审核驳回
defined('SUGGEST_ORDER_STATUS_AUDIT_REJECT')        OR define('SUGGEST_ORDER_STATUS_AUDIT_REJECT',3);// 审核驳回

// 支付方式
defined('PURCHASE_PAY_TYPE_ALIPAY')     OR define('PURCHASE_PAY_TYPE_ALIPAY',1);// 支付宝
defined('PURCHASE_PAY_TYPE_PUBLIC')     OR define('PURCHASE_PAY_TYPE_PUBLIC',2);// 对公支付 - 线下境内
defined('PURCHASE_PAY_TYPE_PRIVATE')    OR define('PURCHASE_PAY_TYPE_PRIVATE',3);// 对私支付 - 线下境外

// 采购单-合同单 情况方式
defined('PURCHASE_REQUISITION_METHOD_PERCENT')      OR define('PURCHASE_REQUISITION_METHOD_PERCENT',1);// 比例请款
defined('PURCHASE_REQUISITION_METHOD_IN_QUANTITY')  OR define('PURCHASE_REQUISITION_METHOD_IN_QUANTITY',2);// 入库数量请款
defined('PURCHASE_REQUISITION_METHOD_HAND')         OR define('PURCHASE_REQUISITION_METHOD_HAND',3);// 运费请款
defined('PURCHASE_REQUISITION_METHOD_MANUAL')         OR define('PURCHASE_REQUISITION_METHOD_MANUAL',4);// 手动请款
defined('PURCHASE_REQUISITION_METHOD_REPORTLOSS')   OR define('PURCHASE_REQUISITION_METHOD_REPORTLOSS',5);// 报损请款


// 运费支付方式
defined('PURCHASE_FREIGHT_PAYMENT_A')        OR define('PURCHASE_FREIGHT_PAYMENT_A',1);// 甲方支付
defined('PURCHASE_FREIGHT_PAYMENT_B')        OR define('PURCHASE_FREIGHT_PAYMENT_B',2);// 乙方支付

// 采购单加急状态
defined('PURCHASE_IS_EXPEDITED_N')  OR define('PURCHASE_IS_EXPEDITED_N',1);// 否
defined('PURCHASE_IS_EXPEDITED_Y')  OR define('PURCHASE_IS_EXPEDITED_Y',2);// 是

// 发票单审核状态
defined('INVOICE_STATES_WAITING_CONFIRM')        OR define('INVOICE_STATES_WAITING_CONFIRM',1);// 待提交
defined('INVOICE_STATES_WAITING_MAKE_INVOICE')   OR define('INVOICE_STATES_WAITING_MAKE_INVOICE',2);// 待采购开票
defined('INVOICE_STATES_WAITING_FINANCE_AUDIT')  OR define('INVOICE_STATES_WAITING_FINANCE_AUDIT',3);// 待财务审核
defined('INVOICE_STATES_AUDIT')                  OR define('INVOICE_STATES_AUDIT',4);// 已审核
defined('INVOICE_STATES_FINANCE_REJECTED')       OR define('INVOICE_STATES_FINANCE_REJECTED',5);// 财务驳回

// 二次包装类表 审核状态
defined('PRODUCT_REPACKAGE_STATUS_NOT_AUDIT')       OR define('PRODUCT_REPACKAGE_STATUS_NOT_AUDIT',1);// 财务驳回
defined('PRODUCT_REPACKAGE_STATUS_AUDIT_PASS')      OR define('PRODUCT_REPACKAGE_STATUS_AUDIT_PASS',2);// 审核通过
defined('PRODUCT_REPACKAGE_STATUS_AUDIT_NO_PASS')   OR define('PRODUCT_REPACKAGE_STATUS_AUDIT_NO_PASS',3);// 审核不通过



// 采购需求 单据类型
defined('PURCHASE_DEMAND_TYPE_PLAN')        OR define('PURCHASE_DEMAND_TYPE_PLAN',1);// 计划单
defined('PURCHASE_DEMAND_TYPE_FORECAST')    OR define('PURCHASE_DEMAND_TYPE_FORECAST',2);// 预测单

//付款状态
defined('PAY_UNPAID_STATUS')            OR define('PAY_UNPAID_STATUS', 10); //未申请付款
defined('PAY_NONEED_STATUS')            OR define('PAY_NONEED_STATUS', 95); //无需付款
defined('PAY_WAITING_SOA_REVIEW')       OR define('PAY_WAITING_SOA_REVIEW', 18); //待对账主管审核
defined('PAY_SOA_REJECT')               OR define('PAY_SOA_REJECT', 19); //对账主管驳回
defined('PAY_WAITING_MANAGER_REVIEW')   OR define('PAY_WAITING_MANAGER_REVIEW', 20); //待经理审核
defined('PAY_MANAGER_REJECT')           OR define('PAY_MANAGER_REJECT', 21); //经理驳回

defined('PAY_WAITING_MANAGER_SUPPLY')   OR define('PAY_WAITING_MANAGER_SUPPLY', 25); //待供应链总监审核
defined('PAY_WAITING_MANAGER_REJECT')   OR define('PAY_WAITING_MANAGER_REJECT', 26); //供应链总监驳回


defined('PAY_WAITING_FINANCE_REVIEW')   OR define('PAY_WAITING_FINANCE_REVIEW', 30); //待财务审核
defined('PAY_FINANCE_REJECT')           OR define('PAY_FINANCE_REJECT', 31); //财务驳回
defined('PAY_WAITING_FINANCE_PAID')     OR define('PAY_WAITING_FINANCE_PAID', 40); //待财务付款
defined('PART_PAID')                    OR define('PART_PAID', 50); //已部分付款
defined('PAY_PAID')                     OR define('PAY_PAID', 51); //已付款
defined('PAY_CANCEL')                   OR define('PAY_CANCEL', 90); //已取消
defined('PAY_UFXFUIOU_REVIEW')          OR define('PAY_UFXFUIOU_REVIEW', 13); //富友支付审核
defined('PAY_UFXFUIOU_BAOFOPAY')        OR define('PAY_UFXFUIOU_BAOFOPAY', 14); //宝付支付审核
defined('PAY_LAKALA')                   OR define('PAY_LAKALA', 15); //待支付平台审核

defined('PAY_UFXFUIOU_SUPERVISOR')          OR define('PAY_UFXFUIOU_SUPERVISOR', 60); //待财务主管审核
defined('PAY_UFXFUIOU_MANAGER')             OR define('PAY_UFXFUIOU_MANAGER', 61); //待财务经理审核
defined('PAY_UFXFUIOU_SUPPLY')              OR define('PAY_UFXFUIOU_SUPPLY', 62); //待财务总监审核（改为待财务副总监审核）
defined('PAY_GENERAL_MANAGER')              OR define('PAY_GENERAL_MANAGER', 63); //待总经办审核
defined('PAY_REJECT_SUPERVISOR')            OR define('PAY_REJECT_SUPERVISOR', 64); //财务主管驳回
defined('PAY_REJECT_MANAGER')               OR define('PAY_REJECT_MANAGER', 65); //财务经理驳回
defined('PAY_REJECT_SUPPLY')                OR define('PAY_REJECT_SUPPLY', 66); //财务总监驳回(改为财务副总监驳回)
defined('PAY_GENERAL_MANAGER_REJECT')       OR define('PAY_GENERAL_MANAGER_REJECT', 67); //总经办驳回

// 请款单 请款类型
// 付款单付款种类明细
defined('PURCHASE_PAY_CATEGORY_1') OR define('PURCHASE_PAY_CATEGORY_1', 1); // 采购运费/优惠
defined('PURCHASE_PAY_CATEGORY_2') OR define('PURCHASE_PAY_CATEGORY_2', 2); // 采购货款
defined('PURCHASE_PAY_CATEGORY_3') OR define('PURCHASE_PAY_CATEGORY_3', 3); // 采购货款+运费/优惠
defined('PURCHASE_PAY_CATEGORY_4') OR define('PURCHASE_PAY_CATEGORY_4', 4); // 采购运费
defined('PURCHASE_PAY_CATEGORY_5') OR define('PURCHASE_PAY_CATEGORY_5', 5); // 样品请款
defined('PURCHASE_PAY_CATEGORY_6') OR define('PURCHASE_PAY_CATEGORY_6', 6); // 物料请款

// 应收款单 付款状态
defined('RECEIPT_PAY_STATUS_WAITING_RECEIPT') OR define('RECEIPT_PAY_STATUS_WAITING_RECEIPT', 1); // 待收款
defined('RECEIPT_PAY_STATUS_RECEIPTED')       OR define('RECEIPT_PAY_STATUS_RECEIPTED', 2); // 已收款
defined('RECEIPT_PAY_STATUS_REJECTED')        OR define('RECEIPT_PAY_STATUS_REJECTED', 3); // 已驳回

// 合同单请款金额容许的误差值
defined('IS_ALLOWABLE_ERROR')                 OR define('IS_ALLOWABLE_ERROR', 1);


//供应商审核状态
defined('SUPPLIER_WAITING_PURCHASE_REVIEW')  OR define('SUPPLIER_WAITING_PURCHASE_REVIEW', 1); //待采购审核
defined('SUPPLIER_PURCHASE_REJECT')          OR define('SUPPLIER_PURCHASE_REJECT', 2); //采购审核-驳回
defined('SUPPLIER_WAITING_SUPPLIER_REVIEW')  OR define('SUPPLIER_WAITING_SUPPLIER_REVIEW', 3); //待供应链审核
defined('SUPPLIER_SUPPLIER_REJECT')          OR define('SUPPLIER_SUPPLIER_REJECT', 4); //供应链审核-驳回
defined('SUPPLIER_FINANCE_REVIEW')           OR define('SUPPLIER_FINANCE_REVIEW', 5); //待财务审核
defined('SUPPLIER_FINANCE_REJECT')           OR define('SUPPLIER_FINANCE_REJECT', 6); //财务审核-驳回
defined('SUPPLIER_REVIEW_PASSED')            OR define('SUPPLIER_REVIEW_PASSED', 7); //审核通过




//供应商主表状态
defined('SUPPLIER_REVIEW_STATUS') OR define('SUPPLIER_REVIEW_STATUS', 4); //审核不通过
defined('SUPPLIER_NORMAL_STATUS') OR define('SUPPLIER_NORMAL_STATUS', 1); //显示正常


// 供应商审核记录 状态
defined('SUPPLIER_AUDIT_RESULTS_STATUS_WAITING_AUDIT') OR define('SUPPLIER_AUDIT_RESULTS_STATUS_WAITING_AUDIT', 0); // 待审核
defined('SUPPLIER_AUDIT_RESULTS_STATUS_PASS') OR define('SUPPLIER_AUDIT_RESULTS_STATUS_PASS', 10); // 通过
defined('SUPPLIER_AUDIT_RESULTS_STATUS_REJECTED') OR define('SUPPLIER_AUDIT_RESULTS_STATUS_REJECTED', 20); // 驳回
defined('SUPPLIER_AUDIT_RESULTS_STATUS_DISABLED') OR define('SUPPLIER_AUDIT_RESULTS_STATUS_DISABLED', 30); // 禁用
defined('SUPPLIER_AUDIT_RESULTS_STATUS_BLACKLIST') OR define('SUPPLIER_AUDIT_RESULTS_STATUS_BLACKLIST', 40); // 加入黑名单



//供应商整合状态列表
defined('SUPPLIER_INTEGRATE_STATUS_TO_CONFIRM')    OR define('SUPPLIER_INTEGRATE_STATUS_TO_CONFIRM',1);// 待确认
defined('SUPPLIER_INTEGRATE_STATUS_SUCCESS')       OR define('SUPPLIER_INTEGRATE_STATUS_SUCCESS',2);// 整合成功
defined('SUPPLIER_INTEGRATE_STATUS_FAILED')        OR define('SUPPLIER_INTEGRATE_STATUS_FAILED',3);// 整合失败


// 产品状态
defined('PRODUCT_STATUS_AUDIT_NO_PASS')             OR define('PRODUCT_STATUS_AUDIT_NO_PASS',0);// 审核不通过
defined('PRODUCT_STATUS_NEW_DEVELOPED')             OR define('PRODUCT_STATUS_NEW_DEVELOPED',1);// 刚开发
defined('PRODUCT_STATUS_EDITING')                   OR define('PRODUCT_STATUS_EDITING',2);// 编辑中
defined('PRODUCT_STATUS_ADVANCE_ONLINE')            OR define('PRODUCT_STATUS_ADVANCE_ONLINE',3);// 预上线
defined('PRODUCT_STATUS_IN_SALE')                   OR define('PRODUCT_STATUS_IN_SALE',4);// 在售中
defined('PRODUCT_STATUS_BEEN_SLOW')                 OR define('PRODUCT_STATUS_BEEN_SLOW',5);// 已滞销
defined('PRODUCT_STATUS_WAITING_CLEAR')             OR define('PRODUCT_STATUS_WAITING_CLEAR',6);// 待清仓
defined('PRODUCT_STATUS_BEEN_STOP_SALE')            OR define('PRODUCT_STATUS_BEEN_STOP_SALE',7);// 已停售
defined('PRODUCT_STATUS_NEW_BUY_SAMPLE')            OR define('PRODUCT_STATUS_NEW_BUY_SAMPLE',8);// 刚买样  待买样
defined('PRODUCT_STATUS_WAITING_QUALITY')           OR define('PRODUCT_STATUS_WAITING_QUALITY',9);// 待品检
defined('PRODUCT_STATUS_FILMING')                   OR define('PRODUCT_STATUS_FILMING',10);// 拍摄中
defined('PRODUCT_STATUS_INFO_WAITING_CONFIRM')      OR define('PRODUCT_STATUS_INFO_WAITING_CONFIRM',11);// 产品信息确认
defined('PRODUCT_STATUS_DOING_IMAGE')               OR define('PRODUCT_STATUS_DOING_IMAGE',12);// 修图中
defined('PRODUCT_STATUS_DESIGN_COPYWRITER')         OR define('PRODUCT_STATUS_DESIGN_COPYWRITER',14);// 设计审核中
defined('PRODUCT_STATUS_COPYWRITER_WAITING_AUDIT')  OR define('PRODUCT_STATUS_COPYWRITER_WAITING_AUDIT',15);// 文案审核中
defined('PRODUCT_STATUS_COPYWRITER_FINAL_AUDIT')    OR define('PRODUCT_STATUS_COPYWRITER_FINAL_AUDIT',16);// 文案主管终审中
defined('PRODUCT_STATUS_TRY_SELL_EDITOR')           OR define('PRODUCT_STATUS_TRY_SELL_EDITOR',17);// 试卖编辑中
defined('PRODUCT_STATUS_TRY_SELL_ON_SALE')          OR define('PRODUCT_STATUS_TRY_SELL_ON_SALE',18);// 试卖在售中
defined('PRODUCT_STATUS_TRY_SELL_COPYWRITER_FINAL_AUDIT') OR define('PRODUCT_STATUS_TRY_SELL_COPYWRITER_FINAL_AUDIT',19);// 试卖文案终审中
defined('PRODUCT_STATUS_ADVANCE_ONLINE_FILMING')    OR define('PRODUCT_STATUS_ADVANCE_ONLINE_FILMING',20);// 预上线拍摄中
defined('PRODUCT_STATUS_LOGISTICS_AUDIT')           OR define('PRODUCT_STATUS_LOGISTICS_AUDIT',21);// 物流审核中
defined('PRODUCT_STATUS_SOLD_OUT')                  OR define('PRODUCT_STATUS_SOLD_OUT',22);// 缺货中
defined('PRODUCT_STATUS_DOING_IMAGE_AUDIT')         OR define('PRODUCT_STATUS_DOING_IMAGE_AUDIT',27);// 作图审核中
defined('PRODUCT_STATUS_DOING_CUSTOMS_AUDIT')       OR define('PRODUCT_STATUS_DOING_CUSTOMS_AUDIT',28);// 关务审核中
defined('PRODUCT_STATUS_DEVELOP_CHECK')             OR define('PRODUCT_STATUS_DEVELOP_CHECK',29);// 开发检查中
defined('PRODUCT_STATUS_FILMING_AND_EDITOR')        OR define('PRODUCT_STATUS_FILMING_AND_EDITOR',31);// 编辑中拍摄中
defined('PRODUCT_STATUS_EDITED_AND_FILMING')        OR define('PRODUCT_STATUS_EDITED_AND_FILMING',32);// 已编辑拍摄中
defined('PRODUCT_STATUS_EDITOR_AND_FILMED')        OR define('PRODUCT_STATUS_EDITOR_AND_FILMED',33);//   编辑中已拍摄
defined('PRODUCT_STATUS_UNKNOWN')                   OR define('PRODUCT_STATUS_UNKNOWN',100);// 未知

//产品信息修改审核页面 审核状态
defined('PRODUCT_UPDATE_LIST_AUDITED')         OR define('PRODUCT_UPDATE_LIST_AUDITED',1);// 待采购审核
defined('PRODUCT_UPDATE_LIST_QUALITY_AUDIT')   OR define('PRODUCT_UPDATE_LIST_QUALITY_AUDIT',2);// 待品控审核
defined('PRODUCT_UPDATE_LIST_AUDIT_PASS')      OR define('PRODUCT_UPDATE_LIST_AUDIT_PASS',3);// 审核通过
defined('PRODUCT_UPDATE_LIST_REJECT')          OR define('PRODUCT_UPDATE_LIST_REJECT',4);// 驳回
defined('PRODUCT_UPDATE_LIST_FINANCE')          OR define('PRODUCT_UPDATE_LIST_FINANCE',5);// 待财务审核
defined('PRODUCT_UPDATE_LIST_PURCHASE')          OR define('PRODUCT_UPDATE_LIST_PURCHASE',6);// 待供应商审核
defined('PRODUCT_EXECUTIVE_DIRECTOR')          OR define('PRODUCT_EXECUTIVE_DIRECTOR',7);// 采购主管审核
defined('PRODUCT_DEPUTY_MANAGER')          OR define('PRODUCT_DEPUTY_MANAGER',8);// 采购副经理审核
defined('PRODUCT_PURCHASING_MANAGER')          OR define('PRODUCT_PURCHASING_MANAGER',9);// 采购经理审核
defined('PRODUCT_DEVELOPMENT')          OR define('PRODUCT_DEVELOPMENT',10);// 开发经理审核
defined('PRODUCT_SUPPLIER_DIRECTOR')          OR define('PRODUCT_SUPPLIER_DIRECTOR',11);// 供应链总监
// 审核全流程
defined('PRODUCT_ALL_CONTENT_PROCCESS')  OR define('PRODUCT_ALL_CONTENT_PROCCESS','[{"name":"\u91c7\u8d2d\u4e3b\u7ba1","nameflag":"executive_director","sort":1,"audit_flag":7},{"name":"\u91c7\u8d2d\u526f\u7ecf\u7406","nameflag":"deputy_manager","sort":2,"audit_flag":8},{"name":"\u91c7\u8d2d\u7ecf\u7406","nameflag":"purchasing_manager","sort":3,"audit_flag":9},{"name":"\u4f9b\u5e94\u5546\u7ba1\u7406\u90e8\u95e8","nameflag":"supplier","sort":4,"audit_flag":6},{"name":"\u5f00\u53d1\u7ecf\u7406","nameflag":"Development_Manager","sort":5,"audit_flag":10},{"name":"\u54c1\u63a7\u5ba1\u6838","nameflag":"quality","sort":6,"audit_flag":2},{"name":"\u4f9b\u5e94\u94fe\u603b\u76d1","nameflag":"supplier_director","sort":7,"audit_flag":11}]');
//supplier_director
//产品是否拿样
defined('PRODUCT_MOD_SAMPLE')         OR define('PRODUCT_MOD_SAMPLE',1);// 拿样
defined('PRODUCT_MOD_NO_SAMPLE')   OR define('PRODUCT_MOD_NO_SAMPLE',0);// 不拿样

// SKU屏蔽申请原因列表
defined('PRODUCT_SCREE_APPLY_REASON_STOP_PRODUCTION')   OR define('PRODUCT_SCREE_APPLY_REASON_STOP_PRODUCTION',2);// 停产
defined('PRODUCT_SCREE_APPLY_REASON_OUT_STOCK')         OR define('PRODUCT_SCREE_APPLY_REASON_OUT_STOCK',3);// 断货
defined('PRODUCT_SCREE_APPLY_REASON_STOP_GOODS')        OR define('PRODUCT_SCREE_APPLY_REASON_STOP_GOODS',4);// 停货
defined('PRODUCT_SCREE_APPLY_REASON_NEED_MINIMUM')      OR define('PRODUCT_SCREE_APPLY_REASON_NEED_MINIMUM',99);// 需要起订量
defined('PRODUCT_SCREE_APPLY_REASON_OTHER')             OR define('PRODUCT_SCREE_APPLY_REASON_OTHER',100);// 其他
defined('PRODUCT_SCREE_APPLY_REASON_SUCCESS')   OR define('PRODUCT_SCREE_APPLY_REASON_SUCCESS',1);// 正常

defined('PRODUCT_SCREE_APPLY_REASON_ZAOHUO')             OR define('PRODUCT_SCREE_APPLY_REASON_ZAOHUO',10);// 停产找货中
// SKU 屏蔽记录状态列表
defined('PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT')   OR define('PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT',10);// 待采购经理审核
defined('PRODUCT_SCREE_STATUS_PURCHASE_REJECTED')        OR define('PRODUCT_SCREE_STATUS_PURCHASE_REJECTED',11);// 采购经理驳回
defined('PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM')  OR define('PRODUCT_SCREE_STATUS_WAITING_DEVELOP_CONFIRM',20);// 待开发确认
defined('PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM') OR define('PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM',30);// 待采购确认
// defined('PRODUCT_SCREE_STATUS_WAITING_AUDIT') OR define('PRODUCT_SCREE_STATUS_WAITING_PURCHASE_CONFIRM',31);// 待采购确认
defined('PRODUCT_SCREE_STATUS_CHANGED')                  OR define('PRODUCT_SCREE_STATUS_CHANGED',40);// 已变更
defined('PRODUCT_SCREE_STATUS_END')                      OR define('PRODUCT_SCREE_STATUS_END',50);// 已结束
defined('PRODUCT_SCREE_STATUS_DEVELOP_REJECTED')         OR define('PRODUCT_SCREE_STATUS_DEVELOP_REJECTED',60);// 开发驳回
defined('PRODUCT_SCREE_STATUS_DEVELOP_CONFIRM')         OR define('PRODUCT_SCREE_STATUS_DEVELOP_CONFIRM',70);// 开发驳回


//发票清单列表 状态
defined('INVOICE_TO_BE_CONFIRMED')         OR define('INVOICE_TO_BE_CONFIRMED',1);// 待确认
defined('INVOICE_TO_BE_PURCHASE_INVOICE')  OR define('INVOICE_TO_BE_PURCHASE_INVOICE',2);// 待采购开票
defined('INVOICE_TO_BE_FINANCIAL_AUDIT')   OR define('INVOICE_TO_BE_FINANCIAL_AUDIT',3);// 待财务审核
defined('INVOICE_AUDITED')                 OR define('INVOICE_AUDITED',4);// 已审核
defined('INVOICE_FINANCIAL_REJECTION')     OR define('INVOICE_FINANCIAL_REJECTION',5);// 财务驳回
defined('INVOICE_FINANCIAL_TOVOID')     OR define('INVOICE_FINANCIAL_TOVOID',9);// 作废
//供应商发票类型
defined('INVOICE_TYPE_NONE')                OR define('INVOICE_TYPE_NONE',1);// 无
defined('INVOICE_TYPE_ADDED_VALUE_TAX')     OR define('INVOICE_TYPE_ADDED_VALUE_TAX',2);// 增值税发票
defined('INVOICE_TYPE_GENERAL_INVOICE')     OR define('INVOICE_TYPE_GENERAL_INVOICE',3);// 普票

//报关状态
defined('CUSTOMS_DECLARATION')  OR define('CUSTOMS_DECLARATION',1);// 已报关
defined('PARTIAL_DECLARATION')  OR define('PARTIAL_DECLARATION',2);// 部分报关
defined('UNCUSTOMED')        	OR define('UNCUSTOMED',3);// 未报关

//是否完结
defined('IS_END_TRUE')  OR define('IS_END_TRUE',1);// 是
defined('IS_END_FALSE')  OR define('IS_END_FALSE',0);// 否

//库存是否异常
defined('IS_ABNORMAL_TRUE')  OR define('IS_ABNORMAL_TRUE',1);// 是
defined('IS_ABNORMAL_FALSE')  OR define('IS_ABNORMAL_FALSE',2);// 否


//启用禁用
defined('IS_ENABLE')  OR define('IS_ENABLE',1);// 启用
defined('IS_DISABLE')  OR define('IS_DISABLE',2);// 禁用
defined('PRE_DISABLE')  OR define('PRE_DISABLE',6);// 预禁用
defined('IS_BLACKLIST')  OR define('IS_BLACKLIST',7);// 黑名单



// 国家地区表 地区类型
defined('REGION_TYPE_COUNTRY')  OR define('REGION_TYPE_COUNTRY',0);// 国家
defined('REGION_TYPE_PROVINCE') OR define('REGION_TYPE_PROVINCE',1);// 省份
defined('REGION_TYPE_CITY')     OR define('REGION_TYPE_CITY',2);// 城市
defined('REGION_TYPE_AREA')     OR define('REGION_TYPE_AREA',3);// 区县


// 采购单报损单状态
defined('REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT')     OR define('REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT',0);// 待经理审核
defined('REPORT_LOSS_STATUS_MANAGER_REJECTED')          OR define('REPORT_LOSS_STATUS_MANAGER_REJECTED',1);// 经理驳回
defined('REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT')     OR define('REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT',2);// 待财务审核
defined('REPORT_LOSS_STATUS_FINANCE_REJECTED')          OR define('REPORT_LOSS_STATUS_FINANCE_REJECTED',3);// 财务驳回
defined('REPORT_LOSS_STATUS_FINANCE_PASS')              OR define('REPORT_LOSS_STATUS_FINANCE_PASS',4);// 已通过
defined('REPORT_LOSS_STATUS_YZCANCEL')                  OR define('REPORT_LOSS_STATUS_YZCANCEL',5);// 已转取消


// 富有相关状态配置
defined('FU_IOU_STATUS_OTHER')                              OR define('FU_IOU_STATUS_OTHER','0000');// 复核拒绝会出现其他情况还不确定
defined('FU_IOU_STATUS_STATUS_ACCEPTED')                    OR define('FU_IOU_STATUS_STATUS_ACCEPTED','5002');// 状态已受理
defined('FU_IOU_STATUS_DEDUCT_MONEY_SUCCESS')               OR define('FU_IOU_STATUS_DEDUCT_MONEY_SUCCESS','5005');// 划款成功
defined('FU_IOU_STATUS_DEDUCT_MONEY_FAILED_AND_RETURNED')   OR define('FU_IOU_STATUS_DEDUCT_MONEY_FAILED_AND_RETURNED','5007');// 划款失败，资金已退回


//取消未到货状态
defined('CANCEL_AUDIT_STATUS_CG')                       OR define('CANCEL_AUDIT_STATUS_CG',10);// 待采购经理审核
defined('CANCEL_AUDIT_STATUS_CF')                       OR define('CANCEL_AUDIT_STATUS_CF',20);// 待财务收款
defined('CANCEL_AUDIT_STATUS_CGBH')                     OR define('CANCEL_AUDIT_STATUS_CGBH',30);// 采购驳回
defined('CANCEL_AUDIT_STATUS_CFBH')                     OR define('CANCEL_AUDIT_STATUS_CFBH',40);// 财务驳回
defined('CANCEL_AUDIT_STATUS_SCJT')                     OR define('CANCEL_AUDIT_STATUS_SCJT',50);// 待上传截图
defined('CANCEL_AUDIT_STATUS_CFYSK')                    OR define('CANCEL_AUDIT_STATUS_CFYSK',60);// 财务已收款
defined('CANCEL_AUDIT_STATUS_SYSTEM')                   OR define('CANCEL_AUDIT_STATUS_SYSTEM',70);//系统自动通过
defined('CANCEL_AUDIT_STATUS_YZBS')                     OR define('CANCEL_AUDIT_STATUS_YZBS',80);//已转报损
defined('CANCEL_AUDIT_STATUS_YDC')                     OR define('CANCEL_AUDIT_STATUS_YDC',90);//已抵冲


//采购主体
defined('HKYB')	OR define('HKYB','YIBAI TECHNOLOGY LTD');
defined('SZYB')	OR define('SZYB','深圳市易佰网络科技有限公司');
defined('QHYB')	OR define('QHYB','深圳前海新佰辰科技有限公司');

//取消未到货 推送仓库

defined('WAREHOUSE_TYPE')	OR define('WAREHOUSE_TYPE',3);

//设置特殊情况下状态
defined('PURCHASE_NUMBER_ZFSTATUS') OR define('PURCHASE_NUMBER_ZFSTATUS','xxxxxx');
defined('ORDER_CANCEL_ORSTATUS') OR define('ORDER_CANCEL_ORSTATUS','0');
defined('ORDER_CANCEL_SORSTATUS') OR define('ORDER_CANCEL_SORSTATUS','0');
//结算方式(100%订金   10) 为可创建对账合同
defined('RECONCIlIATION_COMPACT') OR define('RECONCIlIATION_COMPACT',10);
//供应商结算方式
defined('SUPPLIER_SETTLEMENT_CODE_TAP_DATE') OR define('SUPPLIER_SETTLEMENT_CODE_TAP_DATE',20);//线上账期

//提醒账期付款状态
defined('TAP_DATE_OVER_TIME') OR define('TAP_DATE_OVER_TIME',1);//表示 已超期
defined('TAP_DATE_COMING_SOON') OR define('TAP_DATE_COMING_SOON',2);//即将到期
defined('TAP_DATE_CAN_WAIT') OR define('TAP_DATE_CAN_WAIT',3);//可继续等待
defined('TAP_DATE_WITHOUT_BALANCE') OR define('TAP_DATE_WITHOUT_BALANCE',4);//额度已满，需紧急支付

//对账合同 是否作废
defined('RECONCILIATION_COMPACT_INVALID') OR define('RECONCILIATION_COMPACT_INVALID',1);//不作废 （也可以作为是否等于1判断）
defined('RECONCILIATION_COMPACT_INVALID_YES') OR define('RECONCILIATION_COMPACT_INVALID_YES',2);//作废



//数据权限 用户角色
defined('TEAM_MEMBER') OR define('TEAM_MEMBER','组员');//组员
defined('GROUP_LEADER') OR define('GROUP_LEADER','组长');//组长
defined('EXECUTIVE_DIRECTOR') OR define('EXECUTIVE_DIRECTOR','主管');//主管
defined('MANAGER') OR define('MANAGER','采购经理');//采购经理
defined('ADMIN') OR define('ADMIN','admin');//admin
defined('OPRIMIZATION') OR define('OPRIMIZATION','优化员');//优化员
defined('SALE') OR define('SALE','销售');//销售

// SKU 货源状态修改类型
defined('ESTIMATED_TIME') OR define('ESTIMATED_TIME','到达预计供货时间');//到达预计供货时间
defined('PURCHASEING_MANAGER') OR define('PURCHASEING_MANAGER','采购经理审核');//采购经理审核
defined('MODIFY_SOURCE') OR define('MODIFY_SOURCE','修改货源状态');//修改货源状态

//报损推送数据中心类型
defined('REPORT_LOSS_PUSH_TYPE') OR define('REPORT_LOSS_PUSH_TYPE',2);//取消类型 1：退款 2：报损  3取消剩余等待与中转仓同步

//报损记录的推送状态
defined('NO_REFUND_STATUS') OR define('NO_REFUND_STATUS',0);//未推送
defined('APPLY_REFUND_STATUS') OR define('APPLY_REFUND_STATUS',1);//申请报损已推送
defined('REJECT_REFUND_STATUS') OR define('REJECT_REFUND_STATUS',2);//驳回报损已推送
//是否推送蓝凌系统
defined('PUSHING_BLUE_LING') OR define('PUSHING_BLUE_LING',1);//表示 推送

//供应商支付平台
defined('SUPPLIER_PAY_PLATFORM_5') OR define('SUPPLIER_PAY_PLATFORM_5',5);//网银
defined('SUPPLIER_PAY_PLATFORM_6') OR define('SUPPLIER_PAY_PLATFORM_6',6);//富友支付

//计划系统推送sku状态1.在售品;2.下架品;3.清仓品;4.新产品
defined('SKU_STATE_ON_SALE') OR define('SKU_STATE_ON_SALE',1);//在售品
defined('SKU_STATE_UNDER_CARRIAGE') OR define('SKU_STATE_UNDER_CARRIAGE',2);//下架品
defined('SKU_STATE_CLEARANCE') OR define('SKU_STATE_CLEARANCE',3);//清仓
defined('SKU_STATE_IS_NEW') OR define('SKU_STATE_IS_NEW',4);//新品

// 需求单审核状态
defined('SUGGEST_UN_AUDIT') OR define('SUGGEST_UN_AUDIT', 0); // 待审核
defined('SUGGEST_AUDITED_PASS') OR define('SUGGEST_AUDITED_PASS', 1); // 审核通过
defined('SUGGEST_AUDITED_UN_PASS') OR define('SUGGEST_AUDITED_UN_PASS', 2); // 审核未通过

//推送计划系统开关
defined('PUSH_PLAN_SWITCH') OR define('PUSH_PLAN_SWITCH', true); //回写计划系统开关

//需求单是否异常
defined('SUGGEST_ABNORMAL_FALSE')  OR define('SUGGEST_ABNORMAL_FALSE',0);// 否
defined('SUGGEST_ABNORMAL_TRUE')  OR define('SUGGEST_ABNORMAL_TRUE',1);// 是

//对账单付款状态
defined('STATEMENT_UNPAID')  OR define('STATEMENT_UNPAID',1);// 未付款
defined('STATEMENT_PARTIAL_PAYMENT')  OR define('STATEMENT_PARTIAL_PAYMENT',2);// 部分付款
defined('STATEMENT_PAYMENT_MADE')  OR define('STATEMENT_PAYMENT_MADE',3);// 已付款

//对账单作废状态
defined('STATEMENT_IS_INVALID_FALSE')  OR define('STATEMENT_IS_INVALID_FALSE',1);// 否
defined('STATEMENT_IS_INVALID_TRUE')  OR define('STATEMENT_IS_INVALID_TRUE',2);// 是

//责任小组
defined('DUTY_GROUP_PURCHASE')  OR define('DUTY_GROUP_PURCHASE',1);// 采购跟单组
defined('DUTY_GROUP_STORAGE')  OR define('DUTY_GROUP_STORAGE',2);// 入库组
defined('DUTY_GROUP_IQC')  OR define('DUTY_GROUP_IQC',3);// IQC
defined('DUTY_GROUP_QUALITY_CONTROL')  OR define('DUTY_GROUP_QUALITY_CONTROL',4);// 质控组

//锁单类型
defined('LOCK_SUGGEST_NOT_ENTITIES')  OR define('LOCK_SUGGEST_NOT_ENTITIES',1);// 非实单锁单
defined('LOCK_SUGGEST_ENTITIES')  OR define('LOCK_SUGGEST_ENTITIES',2);// 实单锁单


//产品列表SKU是否异常
defined('PRODUCT_ABNORMAL_FALSE')  OR define('PRODUCT_ABNORMAL_FALSE',1);// 否
defined('PRODUCT_ABNORMAL_TRUE')  OR define('PRODUCT_ABNORMAL_TRUE',2);// 是

// 是否海外仓首单 IS_OVERSEAS_FIRST_ORDER_YES

defined('IS_OVERSEAS_FIRST_ORDER_YES')  OR define('IS_OVERSEAS_FIRST_ORDER_YES',1);// 是

defined('IS_OVERSEAS_FIRST_ORDER_NO')  OR define('IS_OVERSEAS_FIRST_ORDER_NO',0);// 是

//宝付支付审核状态
defined('BAOFOOPAYSTATUS_1')  OR define('BAOFOOPAYSTATUS_1',1);//待审核
defined('BAOFOOPAYSTATUS_2')  OR define('BAOFOOPAYSTATUS_2',2);//审核通过
defined('BAOFOOPAYSTATUS_3')  OR define('BAOFOOPAYSTATUS_3',3);//审核不通过
defined('BAOFOOPAYSTATUS_4')  OR define('BAOFOOPAYSTATUS_4',4);//收款成功
defined('BAOFOOPAYSTATUS_5')  OR define('BAOFOOPAYSTATUS_5',5);//收款失败

//非实单锁单限制金额金额
defined('NOT_ENTITIES_LIMIT_AMOUNT')  OR define('NOT_ENTITIES_LIMIT_AMOUNT',100000);//供应商近3个月合作金额大于改值,非实单锁单

//供应商操作日志类型
defined('SUPPLIER_CREATE_FROM_PRODUCT')  OR define('SUPPLIER_CREATE_FROM_PRODUCT',1);// 产品系统创建              创建
defined('SUPPLIER_CREATE_FROM_ERP')  OR define('SUPPLIER_CREATE_FROM_ERP',2);// ERP系统创建                      创建（erp）
defined('SUPPLIER_NORMAL_UPDATE')  OR define('SUPPLIER_NORMAL_UPDATE',3);// 供应商正常状态（status = 1）更新        更新
defined('SUPPLIER_RESTART_FROM_DISABLED')  OR define('SUPPLIER_RESTART_FROM_DISABLED',4);// 启用（原来状态为禁用）  启用
defined('SUPPLIER_RESTART_FROM_FAILED')  OR define('SUPPLIER_RESTART_FROM_FAILED',5);// 启用（原来状态为审核不通过） 启用
defined('SUPPLIER_NORMAL_AUDIT')  OR define('SUPPLIER_NORMAL_AUDIT',6);// 审核                                   审核
defined('SUPPLIER_NORMAL_DISABLE')  OR define('SUPPLIER_NORMAL_DISABLE',7);//                                   禁用
defined('SUPPLIER_UPDATE_FROM_PRODUCT')  OR define('SUPPLIER_UPDATE_FROM_PRODUCT',8);//                       更新（产品系统）
defined('SUPPLIER_RESTART_FROM_PRODUCT')  OR define('SUPPLIER_RESTART_FROM_PRODUCT',9);//                       启用（产品系统）
defined('SUPPLIER_PRE_DISABLE')  OR define('SUPPLIER_PRE_DISABLE',10);//                       预禁用
defined('SUPPLIER_IS_BLACKLIST')  OR define('SUPPLIER_IS_BLACKLIST',11);//                       加入黑名单
defined('SUPPLIER_LEVEL_GRADE_OPR')  OR define('SUPPLIER_LEVEL_GRADE_OPR',12);//                       供应商等级分数操作




defined('ALI_PREVIEW_MIN_ORDER_PASSED') OR define('ALI_PREVIEW_MIN_ORDER_PASSED','不满足,小于最小起订量');


//开票状态 invoice_status
defined('INVOICE_STATUS_NOT')  OR define('INVOICE_STATUS_NOT',1);//未开票
defined('INVOICE_STATUS_ING')  OR define('INVOICE_STATUS_ING',2);//开票中
defined('INVOICE_STATUS_PART')  OR define('INVOICE_STATUS_PART',3);//部分已开票
defined('INVOICE_STATUS_END')  OR define('INVOICE_STATUS_END',4);//已开票
defined('INVOICE_STATUS_PROHBIT')  OR define('INVOICE_STATUS_PROHBIT',5);//无法开票
//合同开票状态 contract_invoicing_status
defined('CONTRACT_INVOICING_STATUS_NOT')  OR define('CONTRACT_INVOICING_STATUS_NOT',1);//未完结
defined('CONTRACT_INVOICING_STATUS_END')  OR define('CONTRACT_INVOICING_STATUS_END',2);//已完结
//开票是否异常 is_abnormal
defined('INVOICE_IS_ABNORMAL_TRUE')  OR define('INVOICE_IS_ABNORMAL_TRUE',1);//是 异常
defined('INVOICE_IS_ABNORMAL_FALSE')  OR define('INVOICE_IS_ABNORMAL_FALSE',2);//否 正常

/**
 * csv导出格式数据可读格式
 *
 * @var unknown
 */
defined('EXPORT_VIEW_PRETTY')  OR define('EXPORT_VIEW_PRETTY',1);
defined('EXPORT_VIEW_NATIVE')  OR define('EXPORT_VIEW_NATIVE',2);

/**
 * csv输出方式
 * @var unknown
 */
defined('VIEW_BROWSER') OR define('VIEW_BROWSER',1);
defined('VIEW_FILE') OR define('VIEW_FILE',2);
defined('VIEW_AUTO') OR define('VIEW_AUTO',3);

//物流轨迹状态配置
defined('ENABLE_STATUS')  OR define('ENABLE_STATUS',1);//启用状态
defined('DISABLE_STATUS')  OR define('DISABLE_STATUS',0);//禁用状态
defined('FLAG_PROVINCE')  OR define('FLAG_PROVINCE','PROVINCE');//关键字标识“省”
defined('FLAG_CITY')  OR define('FLAG_CITY','CITY');//关键字标识“市”
defined('FLAG_AREA')  OR define('FLAG_AREA','AREA');//关键字标识“区”
defined('FLAG_TOWN')  OR define('FLAG_TOWN','TOWN');//关键字标识“镇”
defined('FLAG_STREET')  OR define('FLAG_STREET','STREET');//关键字标识“街道”
defined('FLAG_LESS_PROVINCE')  OR define('FLAG_LESS_PROVINCE','LESS_PROVINCE');//关键字标识“省（不含“省”字）”
defined('FLAG_LESS_CITY')  OR define('FLAG_LESS_CITY','LESS_CITY');//关键字标识“市（不含“市”字）”
defined('FLAG_LESS_AREA')  OR define('FLAG_LESS_AREA','LESS_AREA');//关键字标识“区（不含“区”字）”
defined('FLAG_LESS_TOWN')  OR define('FLAG_LESS_TOWN','LESS_TOWN');//关键字标识“区（不含“镇”字）”
defined('FLAG_LESS_STREET')  OR define('FLAG_LESS_STREET','LESS_STREET');//关键字标识“街道（不含“街道”字样）”
defined('KEYWORD_AND')  OR define('KEYWORD_AND','AND');//关键字标识“AND”
defined('KEYWORD_OR')  OR define('KEYWORD_OR','OR');//关键字标识“OR”
defined('COLLECT_STATUS')  OR define('COLLECT_STATUS',1);//已揽件
defined('SHIPPED_STATUS')  OR define('SHIPPED_STATUS',2);//已发货
defined('PICK_UP_POINT_STATUS')  OR define('PICK_UP_POINT_STATUS',3);//已到提货点
defined('DELIVER_STATUS')  OR define('DELIVER_STATUS',4);//派件中
defined('RECEIVED_STATUS')  OR define('RECEIVED_STATUS',5);//已签收

//供应商门户各种配置
defined('SRM_COMPACT_READY_STATUS')  OR define('SRM_COMPACT_READY_STATUS',1);//门户合同审核状态-未上传
defined('SRM_COMPACT_WAIT_STATUS')  OR define('SRM_COMPACT_WAIT_STATUS',2);//门户合同审核状态-待采购确认
defined('SRM_COMPACT_REFUSE_STATUS')  OR define('SRM_COMPACT_REFUSE_STATUS',3);//门户合同审核状态-采购驳回
defined('SRM_COMPACT_ACCESS_STATUS')  OR define('SRM_COMPACT_ACCESS_STATUS',4);//门户合同审核状态-已上传(审核通过)
defined('SRM_COMPACT_ACCESS_CANCELED')  OR define('SRM_COMPACT_ACCESS_CANCELED',5);//门户合同审核状态-已作废
defined('SRM_COMPACT_ACCESS_FINISHED')  OR define('SRM_COMPACT_ACCESS_FINISHED',6);//门户合同审核状态-已完结

defined('SRM_TIME_READY_STATUS')  OR define('SRM_TIME_READY_STATUS',1);//门户预计到货时间审核状态-未审核
defined('SRM_TIME_ACCESS_STATUS')  OR define('SRM_TIME_ACCESS_STATUS',2);//门户预计到货时间审核状态-已审核
defined('SRM_TIME_REFUSE_STATUS')  OR define('SRM_TIME_REFUSE_STATUS',3);//门户预计到货时间审核状态-已驳回

//验货单状态
//1-待采购确认,2-待品控确认,3-免检待审核,4-免检驳回,5-品控验货中,6-不合格待确认,7-转合格申请中,8-免检,9-转IQC验货,10-验货合格,11-验货不合格,12-已作废
defined('CHECK_ORDER_STATUS_WAITING_PURCHASER_CONFIRM') OR define('CHECK_ORDER_STATUS_WAITING_PURCHASER_CONFIRM',1);// 待采购确认
defined('CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM') OR define('CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM',2);// 待品控确认
defined('CHECK_ORDER_STATUS_WAITING_EXEMPTION_AUDIT') OR define('CHECK_ORDER_STATUS_WAITING_EXEMPTION_AUDIT',3);// 免检待审核
defined('CHECK_ORDER_STATUS_EXEMPTION_REJECT') OR define('CHECK_ORDER_STATUS_EXEMPTION_REJECT',4);// 免检驳回
defined('CHECK_ORDER_STATUS_QUALITY_CHECKING') OR define('CHECK_ORDER_STATUS_QUALITY_CHECKING',5);// 品控验货中
defined('CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM') OR define('CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM',6);// 不合格待确认
defined('CHECK_ORDER_STATUS_QUALIFIED_APPLYING') OR define('CHECK_ORDER_STATUS_QUALIFIED_APPLYING',7);// 转合格申请中
defined('CHECK_ORDER_STATUS_EXEMPTION') OR define('CHECK_ORDER_STATUS_EXEMPTION',8);// 免检
defined('CHECK_ORDER_STATUS_IQC') OR define('CHECK_ORDER_STATUS_IQC',9);// 转IQC验货
defined('CHECK_ORDER_STATUS_QUALIFIED') OR define('CHECK_ORDER_STATUS_QUALIFIED',10);// 验货合格
defined('CHECK_ORDER_STATUS_UNQUALIFIED') OR define('CHECK_ORDER_STATUS_UNQUALIFIED',11);// 验货不合格
defined('CHECK_ORDER_STATUS_INVALID') OR define('CHECK_ORDER_STATUS_INVALID',12);// 已作废
//验货结果
//1-免检驳回，2-免检，3-合格，4-不合格，5-转IQC
defined('CHECK_ORDER_RESULT_EXEMPTION_REJECT') OR define('CHECK_ORDER_RESULT_EXEMPTION_REJECT',1);// 免检驳回
defined('CHECK_ORDER_RESULT_EXEMPTION') OR define('CHECK_ORDER_RESULT_EXEMPTION',2);// 免检
defined('CHECK_ORDER_RESULT_QUALIFIED') OR define('CHECK_ORDER_RESULT_QUALIFIED',3);// 合格
defined('CHECK_ORDER_RESULT_UNQUALIFIED') OR define('CHECK_ORDER_RESULT_UNQUALIFIED',4);// 不合格
defined('CHECK_ORDER_RESULT_IQC') OR define('CHECK_ORDER_RESULT_IQC',5);// 转IQC

//电商ID
defined('EBusinessID') or define('EBusinessID', '1619320');
//电商加密私钥，快递鸟提供，注意保管，不要泄漏
defined('AppKey') or define('AppKey', '87819d04-0781-4d27-bcb9-daa15c4c5b4b');

//入库后退货-处理状态
defined('RETURN_PROCESSING_STATUS_NO')  OR define('RETURN_PROCESSING_STATUS_NO',1);//未处理
defined('RETURN_PROCESSING_STATUS_ING')  OR define('RETURN_PROCESSING_STATUS_ING',2);//处理中
defined('RETURN_PROCESSING_STATUS_PART')  OR define('RETURN_PROCESSING_STATUS_PART',3);//部分已处理
defined('RETURN_PROCESSING_STATUS_END')  OR define('RETURN_PROCESSING_STATUS_END',4);//已处理
defined('RETURN_PROCESSING_STATUS_REJECT')  OR define('RETURN_PROCESSING_STATUS_REJECT',5);//已驳回

//入库后退货-退货状态
defined('RETURN_STATUS_WAITING_AUDIT')  OR define('RETURN_STATUS_WAITING_AUDIT',1);//待采购经理审核
defined('RETURN_STATUS_PURCHASE_REJECT')  OR define('RETURN_STATUS_PURCHASE_REJECT',2);//采购驳回
defined('RETURN_STATUS_WAITING_RETURN_NUMBER')  OR define('RETURN_STATUS_WAITING_RETURN_NUMBER',3);//待生成退货单
defined('RETURN_STATUS_WAITING_SHIPMENT')  OR define('RETURN_STATUS_WAITING_SHIPMENT',4);//待仓库发货
defined('RETURN_STATUS_WAREHOUSE_REJECT')  OR define('RETURN_STATUS_WAREHOUSE_REJECT',5);//仓库驳回
defined('RETURN_STATUS_WAITING_SUPPLIER_RECEIPT')  OR define('RETURN_STATUS_WAITING_SUPPLIER_RECEIPT',6);//待供应商签收
defined('RETURN_STATUS_SUPPLIER_RECEIPT_FAIL')  OR define('RETURN_STATUS_SUPPLIER_RECEIPT_FAIL',7);//供应商签收失败
defined('RETURN_STATUS_WAITING_UPLOAD_SCREENSHOT')  OR define('RETURN_STATUS_WAITING_UPLOAD_SCREENSHOT',8);//待上传截图
defined('RETURN_STATUS_WAITING_FINANCIAL_RECEIVE')  OR define('RETURN_STATUS_WAITING_FINANCIAL_RECEIVE',9);//待财务收款
defined('RETURN_STATUS_FINANCIAL_REJECT')  OR define('RETURN_STATUS_FINANCIAL_REJECT',10);//财务驳回
defined('RETURN_STATUS_FINANCIAL_RECEIVED')  OR define('RETURN_STATUS_FINANCIAL_RECEIVED',11);//财务已收款
defined('RETURN_STATUS_PURCHASE_MANGER')  OR define('RETURN_STATUS_PURCHASE_MANGER',12);//采购经理驳回


//入库后退货-是否确认签收
defined('RETURN_IS_CONFIRM_RECEIPT_TRUE')  OR define('RETURN_IS_CONFIRM_RECEIPT_TRUE',1);//是
defined('RETURN_IS_CONFIRM_RECEIPT_FALSE')  OR define('RETURN_IS_CONFIRM_RECEIPT_FALSE',2);//否

//入库后退货-退货原因
defined('RETURN_SEASON_UNSALABLE')  OR define('RETURN_SEASON_UNSALABLE',1);//滞销


//入库后退货-运费付款类型
defined('RETURN_FREIGHT_PAYMENT_TYPE_A')  OR define('RETURN_FREIGHT_PAYMENT_TYPE_A',1);//甲方支付
defined('RETURN_FREIGHT_PAYMENT_TYPE_B')  OR define('RETURN_FREIGHT_PAYMENT_TYPE_B',2);//乙方支付

//发运管理-计划部取消
defined('SHIPMENT_PLAN_CANCEL_WAITING_AUDIT_STATUS')  OR define('SHIPMENT_PLAN_CANCEL_WAITING_AUDIT_STATUS',1);//待采购采购审核
defined('SHIPMENT_PLAN_CANCEL_AGREE_AUDIT_STATUS')  OR define('SHIPMENT_PLAN_CANCEL_AGREE_AUDIT_STATUS',2);//采购同意
defined('SHIPMENT_PLAN_CANCEL_REJECT_AUDIT_STATUS')  OR define('SHIPMENT_PLAN_CANCEL_REJECT_AUDIT_STATUS',3);//采购驳回


// 冲销状态（10.待采购经理审核,20.待财务经理审核,30.审核驳回,100.审核通过）
defined('CHARGE_AGAINST_STATUE_WAITING_AUDIT') OR define('CHARGE_AGAINST_STATUE_WAITING_AUDIT', 10);//待采购经理审核
defined('CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT') OR define('CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT', 20);//待财务经理审核
defined('CHARGE_AGAINST_STATUE_WAITING_AUDIT_REJECT') OR define('CHARGE_AGAINST_STATUE_WAITING_AUDIT_REJECT', 30);//审核驳回
defined('CHARGE_AGAINST_STATUE_WAITING_PASS') OR define('CHARGE_AGAINST_STATUE_WAITING_PASS', 100);//100.审核通过

//数据权限配置-小组类型
defined('DAC_GROUP_TYPE_NON_OVERSEA') or define('DAC_GROUP_TYPE_NON_OVERSEA', 1);//非海外仓组
defined('DAC_GROUP_TYPE_OVERSEA') or define('DAC_GROUP_TYPE_OVERSEA', 2);//海外仓组


//支付 - 手续费承担方
defined('PAY_PROCEDURE_PARTY_A') or define('PAY_PROCEDURE_PARTY_A', 1);//1.甲方-我司
defined('PAY_PROCEDURE_PARTY_B') or define('PAY_PROCEDURE_PARTY_B', 2);//2.乙方-供应商


defined('BALANCE_SWITCH_ENGINE') OR define('BALANCE_SWITCH_ENGINE', 2); //供应商余额表数据存储引擎，1.mongodb,2.slave


//供应商审核状态
defined('SUPPLIER_WAITING_PURCHASE_REVIEW_LEVEL_GRADE')  OR define('SUPPLIER_WAITING_PURCHASE_REVIEW_LEVEL_GRADE', 1); //待采购审核
defined('SUPPLIER_PURCHASE_REJECT_LEVEL_GRADE')          OR define('SUPPLIER_PURCHASE_REJECT_LEVEL_GRADE', 2); //采购审核-驳回
defined('SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE')  OR define('SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE', 3); //待供应链审核
defined('SUPPLIER_SUPPLIER_REJECT_LEVEL_GRADE')          OR define('SUPPLIER_SUPPLIER_REJECT_LEVEL_GRADE', 4); //供应链审核-驳回
defined('SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE')           OR define('SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE', 5); //待供应链负责人审核
defined('SUPPLIER_MANAGE_REJECT_LEVEL_GRADE')           OR define('SUPPLIER_MANAGE_REJECT_LEVEL_GRADE', 6); //供应链负责人审核驳回
defined('SUPPLIER_REVIEW_PASSED_LEVEL_GRADE')            OR define('SUPPLIER_REVIEW_PASSED_LEVEL_GRADE', 7); //审核通过




//拜访供应商状态
defined('SUPPLIER_VISIT_WAIT_AUDIT')  OR define('SUPPLIER_VISIT_WAIT_AUDIT', 1); //拜访供应商待审核
defined('SUPPLIER_VISIT_REJECT')          OR define('SUPPLIER_VISIT_REJECT', 2); //采购审核-驳回
defined('SUPPLIER_VISIT_AUDIT_PASS')           OR define('SUPPLIER_VISIT_AUDIT_PASS', 3); //审核通过
defined('SUPPLIER_VISIT_WAIT_VISITING')  OR define('SUPPLIER_VISIT_WAIT_VISITING', 4); //待拜访
defined('SUPPLIER_VISIT_IN_VISITING')          OR define('SUPPLIER_VISIT_IN_VISITING', 5); //拜访中
defined('SUPPLIER_VISIT_WAIT_REPORT')           OR define('SUPPLIER_VISIT_WAIT_REPORT', 6); //待上传报告
defined('SUPPLIER_VISIT_END')           OR define('SUPPLIER_VISIT_END', 7); //已完结

// 采购单页签
defined('TIPS_ALL_ORDER')               OR define('TIPS_ALL_ORDER', 1); // 全部
defined('TIPS_WAITING_CONFIRM')         OR define('TIPS_WAITING_CONFIRM', 3); // 待确认
defined('TIPS_WAITING_ARRIVE')          OR define('TIPS_WAITING_ARRIVE', 5); // 等待到货
defined('TIPS_ORDER_FINISH')            OR define('TIPS_ORDER_FINISH', 7); // 已完结
defined('TIPS_TODAY_WORK')              OR define('TIPS_TODAY_WORK', 9); // 今日任务
defined('TIPS_WAIT_CANCEL')             OR define('TIPS_WAIT_CANCEL', 11); // 待取消

// 采购单页签的搜索
defined('TIPS_SEARCH_ALL_ORDER')               OR define('TIPS_SEARCH_ALL_ORDER', 2); // 全部
defined('TIPS_SEARCH_WAITING_CONFIRM')         OR define('TIPS_SEARCH_WAITING_CONFIRM', 4); // 待确认
defined('TIPS_SEARCH_WAITING_ARRIVE')          OR define('TIPS_SEARCH_WAITING_ARRIVE', 6); // 等待到货
defined('TIPS_SEARCH_ORDER_FINISH')            OR define('TIPS_SEARCH_ORDER_FINISH', 8); // 已完结
defined('TIPS_SEARCH_TODAY_WORK')              OR define('TIPS_SEARCH_TODAY_WORK', 10); // 今日任务
defined('TIPS_SEARCH_WAIT_CANCEL')             OR define('TIPS_SEARCH_WAIT_CANCEL', 12); // 待取消

// 采购单状态
defined('PURCHASE_ORDER_STATUS_WAITING_QUOTE')                  OR define('PURCHASE_ORDER_STATUS_WAITING_QUOTE', 1); // 等待采购询价
defined('PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT')      OR define('PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT', 2); //信息修改待审核状态
defined('PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT')         OR define('PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT', 3); // 待采购审核
defined('PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT')             OR define('PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT', 5); // 待销售审核
defined('PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER')  OR define('PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER', 6); // 等待生成进货单
defined('PURCHASE_ORDER_STATUS_WAITING_ARRIVAL')                OR define('PURCHASE_ORDER_STATUS_WAITING_ARRIVAL', 7); // 等待到货
defined('PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION')     OR define('PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION', 8); // 已到货待检测
defined('PURCHASE_ORDER_STATUS_ALL_ARRIVED')                    OR define('PURCHASE_ORDER_STATUS_ALL_ARRIVED', 9); // 全部到货
defined('PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE')     OR define('PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE', 10); // 部分到货等待剩余到货
defined('PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE') OR define('PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE', 11); // 部分到货不等待剩余到货
defined('PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT')           OR define('PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT', 12); // 作废订单待审核
defined('PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND')          OR define('PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND', 13); // 作废订单待退款
defined('PURCHASE_ORDER_STATUS_CANCELED')                       OR define('PURCHASE_ORDER_STATUS_CANCELED', 14); // 已作废订单
defined('PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT')             OR define('PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT', 15); // 信息修改驳回

// 对账单状态
defined('STATEMENT_STATUS_IS_CREATE')                           OR define('STATEMENT_STATUS_IS_CREATE', 1); // 已生成对账单
defined('STATEMENT_STATUS_WAIT_PURCHASE_AUDIT')                 OR define('STATEMENT_STATUS_WAIT_PURCHASE_AUDIT', 5); // 待采购审核
defined('STATEMENT_STATUS_PURCHASE_REJECT')                     OR define('STATEMENT_STATUS_PURCHASE_REJECT', 10); // 采购驳回
defined('STATEMENT_STATUS_WAIT_PARTY_B_AUDIT')                  OR define('STATEMENT_STATUS_WAIT_PARTY_B_AUDIT', 15); // 待乙方审核
defined('STATEMENT_STATUS_PARTY_B_REJECT')                      OR define('STATEMENT_STATUS_PARTY_B_REJECT', 20); // 乙方驳回
defined('STATEMENT_STATUS_RECALL')                              OR define('STATEMENT_STATUS_RECALL', 25); // 已撤回
defined('STATEMENT_STATUS_EXPIRE')                              OR define('STATEMENT_STATUS_EXPIRE', 30); // 已过期
defined('STATEMENT_STATUS_SIGN_OFFLINE')                        OR define('STATEMENT_STATUS_SIGN_OFFLINE', 35); // 线上签署完成
defined('STATEMENT_STATUS_SIGN_ONLINE')                         OR define('STATEMENT_STATUS_SIGN_ONLINE', 40); // 线下签署完成