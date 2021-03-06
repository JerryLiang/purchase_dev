<?php

/**
 * 是否含税
 * @param string $type
 * @return array|mixed
 */
function is_include_tax($type = null){
    $types = _getStatusList('PUR_PURCHASE_IS_CLUDE_TAX');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}
/**
 * 采购单是否退税（选择下拉框）
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getIsDrawback($type = null){
    $types = _getStatusList('PUR_PURCHASE_IS_DRAWBACK');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 采购单是否退税（合同显示）
 * @author Jolon
 * @param string $type 类型
 * @return array|mixed
 */
function getIsDrawbackShow($type = null){
    $types = _getStatusList('PUR_PURCHASE_IS_DRAWBACK');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 采购类型 列表
 * @author Jolon
 * @param string $type
 * @return array
 */
if(!function_exists('getPurchaseType')){
    function getPurchaseType($type = null){
        $types = _getStatusList('PUR_PURCHASE_TYPE');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type] : '';
        }

        return $types;
    }
}

/**
 * 获取需求类型
 */
if(!function_exists('getDemandType')){
    function getDemandType($type = null){
        $types = _getStatusList('PUR_DEMAND_TYPE');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 产品是否是新品
 * @author Jolon
 * @param string $type
 * @return array
 */
function getProductIsNew($type = null){
    $types = _getStatusList('PUR_PURCHASE_PRODUCT_IS_NEW');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/****************************采购需求 Start**************************/

/**
 * 采购需求-需求状态
 * @author Jolon
 * @param null $type
 * @return array|mixed|string
 */
function getSuggestStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUGGEST_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 是否生成计划单
 * @author Jolon
 * @param string $type
 * @return array
 */
function getIsPlanOrder($type = null){
    $types = _getStatusList('PUR_PURCHASE_IS_PLAN_ORDER');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 采购需求界面 采购单状态
 * @author Jolon
 * @param string $type
 * @return array
 */
function getPurOrderStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_PUR_ORDER_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 待驳回界面 采购单状态
 * @author Jolon
 * @param string $type
 * @return array
 */
function getPurOrderStatusForWaitReject($type = null){
    $types = _getStatusList('PUR_PURCHASE_PUR_ORDER_STATUS_FOR_WAITREJECT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 获取支付方式
 * @param string $type
 * @author harvin
 * @return array|string
 * **/
function getPayType($type = null){
    $types = _getStatusList('PUR_PURCHASE_PAY_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 获取 请款方式
 * @param string $type
 * @author Jolon *
 * @return array|mixed
 */
function getRequisitionMethod($type = null){
    $types = _getStatusList('PUR_PURCHASE_REQUISITION_METHOD');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 获取计算方式
 * @author harvin 2019-1-8
 * @param string $type
 * @return array
 * **/
function freight_formula_mode($type = null){
    $types = _getStatusList('PUR_PURCHASE_FREIGHT_FORMULA_MODE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}


/**
 * 运费方式
 * @author Jolon
 * @param string $type
 * @return array
 */
function getFreightPayment($type = null){
    $types = _getStatusList('PUR_PURCHASE_FREIGHT_PAYMENT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 结算比例
 * @author Jolon
 * @param string $type
 * @return array
 */
function getSettlementRatio($type = null){
    $types = _getStatusList('PUR_PURCHASE_SETTLEMENT_RATIO');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 采购需求列表-产品信息不全-拦截原因
 * @author Jolon
 * @param string $type
 * @return array
 */
function getInterceptReason($type = null){
    $types = _getStatusList('PUR_PURCHASE_INTERCEPT_REASON');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/****************************采购需求 End**************************/


/****************************采购单 Start**************************/
/**
 * 采购单 加急状态
 * @param string $type
 * @return array|mixed
 */
function getIsExpedited($type = null){
    $types = _getStatusList('PUR_PURCHASE_IS_EXPEDITED');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 采购单状态列表
 * @author Jolon
 * @param string $type
 * @return array|mixed|string
 */
function getPurchaseStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_ORDER_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    if (isset($types[5])) {
        unset($types[5]);
    }
    return $types;
}


/**
 * 获取请款方式
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getRequestPayoutType($type = null){
    $types = _getStatusList('PUR_PURCHASE_REQUEST_PAYOUT_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 获取请款单状态
 * @param string $type
 * @return array
 */
function getPayStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_PAY_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 获取采购单是否付款完结状态
 * @param string $type
 * @return array
 */
function getPayFinishStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_PAY_FINISH_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 采购单 采购来源类型
 * @author Jolon
 * @return array
 */
function getPurchaseSource($type = null){
    $types = _getStatusList('PUR_PURCHASE_SOURCE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;


}

/**
 * 获取采购单是否核销
 * @author harvin 2019-1-8
 * @param string $type
 * @return mixed
 */
function getDestroy($type = null){
    $types = _getStatusList('PUR_PURCHASE_ORDER_DESTROY');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 合同主状态 列表
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getCompactStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_REQUEST_PAYOUT_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}


/****************************采购单 End**************************/

/**
 * 海外仓采购需求运输方式（物流类型） 类型列表
 * @author Jolon
 * @param string $type
 * @return array
 */
function getTransportStyle($type = null){
    $types = _getStatusList('PUR_PURCHASE_IRANSPORT_STYLE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 财务管理 付款状态只显示待财务审核、待财务付款、已付款、财务驳回 下拉框
 * @author harvin 2019-1-19=6
 * @param string $type
 * @return mixed
 * **/
function payment_status($type = null){
    $types = _getStatusList('PUR_PURCHASE_PAYMENT_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商等级
 * @author jackson 2019-1-22
 * @param string $type
 * @return mixed
 * **/
function supplier_level($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_LEVEL');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 申请状态
 * @author jackson 2019-1-22
 * @param string $type
 * @return mixed
 * **/
function show_status($type = null){
    $types = _getStatusList('PUR_PURCHASE_SHOW_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商类型
 * @author jackson 2019-2-15
 * @param string $type
 * @return mixed
 * **/
function supplier_type($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商（采购员所属于部门）
 * @author jackson 2019-2-15
 * @param string $type
 * @return mixed
 * **/
function buyer_department($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_DEPARTMENT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 开票
 * @author jackson 2019-2-15
 * @param string $type
 * @return mixed
 * **/
function supplier_ticket($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_TICKET');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 开票税点
 * @author jackson 2019-2-15
 * @param string $type
 * @return mixed
 * **/
function supplier_invoice_tax_rate($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_TICKET_TAX_RATE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 跨境宝供应商 状态列表
 * @author Jolon
 * @param string $type
 * @return array
 */
function getCrossBorder($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUUPLIER_CROSSB_ORDER');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 启用或禁用状态
 * @author jackson 2019-1-22
 * @param string $type
 * @return mixed
 * **/
function enable_status($type = null){
    $types = _getStatusList('PUR_PURCHASE_ENABLE_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商支付方式
 * @author jackson 2019-2-16
 * @param string $type
 * @return mixed
 * **/
function supplier_method($type = null){
    $types = _getStatusList('PUR_PURCHASE_PAY_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商支付平台
 * @author jackson 2019-2-16
 * @param string $type
 * @return mixed
 * **/
function supplier_platform($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_PLATFORM');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}


/**
 * 供应商验货验厂(下拉)
 * @author jackson 2019-1-23
 * @param string $type
 * @return mixed
 * **/
function supplier_check_type($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_CHECK_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商验货验厂(下拉)
 * @author jackson 2019-1-23
 * @param string $type
 * @return mixed
 * **/
function supplier_check_result($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_CHECK_RESULT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商验货验厂(资料类型)
 * @author jackson 2019-1-24
 * @param string $type
 * @return mixed
 * **/
function supplier_material_type($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_MATERIAL_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商验货验厂(验厂申请状态)
 * @author jackson 2019-1-24
 * @param string $type
 * @return mixed
 * **/
function supplier_check_status($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_CHECK_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商验货验厂(再次验货原因)
 * @author jackson 2019-1-24
 * @param string $type
 * @return mixed
 * **/
function supplier_check_review_reason($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_CHECK_REVIEW_REASON');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 获取请款单状态
 * @author jackson 2019-2-17
 * @param string $type
 * @return mixed
 * **/
function pay_status_type($type = null){
    $types = _getStatusList('PUR_PURCHASE_PAY_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 阿里巴巴开放平台订单支付状态
 * @author jackson 2019-2-17
 * @param string $type
 * @return mixed
 * **/
function alibaba_pay_status($type = null){
    $types = _getStatusList('PUR_PURCHASE_ALIBABA_PAY_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 采购单界面-驳回原因
 * @author Jolon
 * @param string $type
 * @return mixed
 */
function getPurchaseOrderRejectReason($type = null){
    $types = _getStatusList('PUR_PURCHASE_ORDER_REJECT_REASON');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商验货(是否为跨境)
 * @author jackson 2019-1-24
 * @param string $type
 * @return mixed
 * **/
function supplier_cross_border($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUUPLIER_CROSSB_ORDER');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商验货(是否加急)
 * @author jackson 2019-1-24
 * @param string $type
 * @return mixed
 * **/
function supplier_is_urgent($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_IS_URGENT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 合同查看页面
 * @author jaxton 2019-2-28
 * @param string $type
 * @return mixed
 * **/
function see_page_file_type($type = null){
    $types = _getStatusList('PUR_PURCHASE_COMPACT_SEE_PAGE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}


/**
 * 获取物流公司名称
 * 获取第三方接口物流系统
 * 暂时写死
 * @return mixed
 * **/
function logistics_company(){
    $types = [
        '顺丰公司' => '顺丰公司',
        '圆通公司' => '圆通公司',
        '中通公司' => '中通公司',
    ];

    return $types;

}

/**
 * 报损审核状态
 * @author Jaxton
 * @param string $type
 * @return array|string
 */
if(!function_exists('getReportlossApprovalStatus')){
    function getReportlossApprovalStatus($type = null){
        $types = _getStatusList('PUR_PURCHASE_ABNORMAL_REPORTLOSS_APPROVAL_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type] : '';
        }

        return $types;
    }
}
/**
 * 取消未到货状态
 * @author 2019-3-11
 */
if(!function_exists('get_cancel_status')){
    function get_cancel_status($type = null){
        $types = _getStatusList('PUR_PURCHASE_CANCEL');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type] : '';
        }

        return $types;
    }
}

/**
 * 获取付款提醒状态
 * @author jeff
 * @param string $type
 * @return array|string
 */
function getPayNotice_Status($type = null)
{
    $types= _getStatusList('PUR_PAY_NOTICE_STATUS');
    if ($type === 0) {
        return 0;
    }

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}


/**
 * 是否 1688 下单
 * @param null $type
 * @return mixed|string
 */
function getIsAliOrder($type = null){
    $types= _getStatusList('PUR_PURCHASE_ORDER_RELATE_ALI');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}
/**
 * 是否 1688 订单金额异常
 * @param null $type
 * @return mixed|string
 */
function getIsAliPriceAbnormal($type = null){
    $types= _getStatusList('PUR_PURCHASE_IS_ALI_PRICE_ABNORMAL');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}
/**
 * 仓库下拉框
 * @param type $type
 * @return type
 */
function getWarehouse($type = null){
    $types= _getStatusList('PUR_PURCHASE_WAREHOUSE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 *  产品状态
 * @param type $type
 * @return type

 **/
function getProdcutStatus($type = NULL) {

    $types= _getStatusList('PUR_PURCHASE_PRODUCT_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 *  1688订单异常状态
 * @param type $type
 * @return type

 **/
function getIsAliAbnormalStatus($type = NULL) {

    $types= _getStatusList('PUR_PURCHASE_ORDER_ABNORMAL_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}


//采购单审核区间
function get_Audit_interval()
{
  return [

      "headman" => "组长审核",
      "director" => "主管审核",
      "deputy_manager_start"=>"副经理审核",
      "manager"  => "经理审核",
      "majordomo" => "总监审核"
  ];
}

/**
 * 票点是否为空
 **/

function getTicketedPoint()
{
    return [1=>'空',2=>'非空'];
}

/**
 * 是否选项
 **/
function getOption()
{
    return [1=>'是',2=>'否'];
}

function getOptionMessage()
{
    return [['status'=>1,'message'=>'是'],['status'=>2,'message'=>'否']];
}

/**
 * SKU 屏蔽，屏蔽原因选项
 **/

function getSkuScreeMessage()
{
    return [['status'=>1,'message'=>'停产'],['status'=>3,'message'=>'断货'],['status'=>99,'message'=>'需要起订量']];
}

function getOptionSupplierSource()
{
    return [['supplier_soruce'=>1,'message'=>'常规'],
        ['supplier_soruce'=>2,'message'=>'海外'],
        ['supplier_soruce'=>3,'message'=>'临时']
    ];
}

/**
 *  是否逾期
 * @param type $type
 * @return type

 **/
function getIsOverdue($type = NULL) {

    $types= _getStatusList('PUR_PURCHASE_ORDER_IS_OVERDUE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/** 供应商资料是否齐全
 * @param null $type
 * @return mixed|string
 */
function getComplete($type = NULL){
    $types= _getStatusList('PUR_SUPPLIER_IS_COMPLETE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }

    return $types;
}

/**
 *  发运类型
 * @param type $type
 * @return type

 **/
function getShipmentType($type = NULL) {

    $types= _getStatusList('PUR_PURCHASE_SHIPMENT_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}


/** 供应商是否是代理商
 * @param null $type
 * @return mixed|string
 */
function getIsAgent($type = NULL){
    $types= _getStatusList('PUR_SUPPLIER_IS_AGENT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }

    return $types;
}

/**
 * 产品开发类型
 * @author wangliang
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductStateType')){
    function getProductStateType($type=null){
        $status= _getStatusList('PUR_PRODUCT_STATE_TYPE');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}

/**
 * 采购需求-是否实单锁单
 * @author Jolon
 * @param null $type
 * @return array|mixed|string
 */
function getEntitiesLockStatus($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_SUGGEST_IS_ENTITIES_LOCK');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 采购单是否欠货（选择下拉框）
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getIsLeftStock($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_IS_LEFT_STOCK');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/** 供应商是否包邮 1 是  2 否
 * @param null $type
 * @return mixed|string
 */
function getIsPostage($type = NULL){
    $types= _getStatusList('PUR_SUPPLIER_IS_POSTAGE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }

    return $types;
}

/**
 * 采购单状态列表
 * @author Jolon
 * @param string $type
 * @return array|mixed|string
 */
function getCancelReasons($type = null){
    $types = _getStatusList('PUR_PURCHASE_CANCEL_REASON');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 物流轨迹状态选择下拉框
 * @author Justin
 * @param string $type
 * @return array|mixed|string
 */
function getTrackStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_TRACK_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 快递单号是否为空选择下拉框
 * @author Jaxton
 * @param string $type
 * @return array|string
 */
if( !function_exists('getEmptyStatus')){
    function getEmptyStatus($type=null){
        $types = _getStatusList('PUR_PURCHASE_EXPRESS_NO_VALUE');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 异常类型
 * @author Jaxton
 * @param string $type
 * @return array|string
 */
if( !function_exists('getWarehouseAbnormalType')){
    function getWarehouseAbnormalType($type=null){
        $types = _getStatusList('PUR_PURCHASE_ABNORMAL_WAREHOUSE_ABNORMAL_TYPE');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 是否在途异常
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getOnWayAbnormalStatus')){
    function getOnWayAbnormalStatus($type=null){
        $types = _getStatusList('PUR_PURCHASE_ON_WAY_ABNORMAL_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 交期确认状态
 * @author Jerry
 * @param string $type
 * @return array|string
 */
if( !function_exists('getIsPredictStatus')){
    function getIsPredictStatus($type=null){
        $types = _getStatusList('PUR_PURCHASE_PREDICT_TIME_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}


// 采购单门户系统状态  1.未确认、2.待采购确认、3.订单已确认、4.部分发货、5.全部发货、6.部分发货不等待剩余、7.已驳回、8.已作废
if( !function_exists('getGateWaysStatus')){

    function getGateWaysStatus($type=NULL){

        return [

            1=>'未确认',
            2=>'待采购确认',
            3=>'订单已确认',
            4=>'部分发货',
            5=>'全部发货',
            6=>'部分发货不等待剩余',
            7=>'已驳回',
            8=>'已作废'
        ];
    }

}

/**
 * 采购单页面验货状态
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getCheckStatus')){
    function getCheckStatus($type=null){
        $types = _getStatusList('PUR_PURCHASE_CHECK_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}



/** 关联供应商类型
 * @param null $type
 * @return mixed|string
 */
function getSupplierRelationType($type = NULL){
    $types= _getStatusList('PUR_SUPPLIER_RELATION_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }

    return $types;
}


/** 供应商是否包邮 1 是  2 否
 * @param null $type
 * @return mixed|string
 */
function getSupplierRelationReason($type = NULL){
    $types= _getStatusList('PUR_SUPPLIER_RELATION_REASON');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }

    return $types;
}

