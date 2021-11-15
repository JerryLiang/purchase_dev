<?php

/**
 * 1688 交易方式列表
 * @param null $type
 * @return array|mixed|string
 */
function getTradeType($type = null){
    $types = _getStatusList('PUR_ALI_ORDER_TRADE_TYPE');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 数组 元素值设置为元素键名
 * @param $tradeModeNameList
 * @return array
 */
function arrayValueToKey($tradeModeNameList){
    $tradeModeNameListTmp = [];
    foreach($tradeModeNameList as $value){
        $tradeModeNameListTmp[$value] = $value;
    }
    return $tradeModeNameListTmp;
}

/**
 * 1688 订单状态列表
 * @param null $type
 * @return mixed|string
 */
function aliOrderStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_ALIBABA_PAY_STATUS');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '其他状态';
    }
    return $types;
}

/**
 * 1688 订单物流单状态
 * @param null $type
 * @return mixed|string
 */

if(!function_exists('aliOrderLogisticsStatus')) {
    function aliOrderLogisticsStatus($type = null)
    {
        $types = _getStatusList('PUR_ALI_ORDER_LOGISTICS_STATUS');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '其他状态';
        }
        return $types;
    }
}

/**
 * 1688 订单中子订单的货物状态
 * @param null $type
 * @return mixed|string
 */
if(!function_exists('aliOrderGoodsStatus')) {
    function aliOrderGoodsStatus($type=null){
        $types = _getStatusList('PUR_ALI_ORDER_GOODS_STATUS');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '其他状态';
        }
        return $types;
    }
}

/**
 * 1688订单退款退货状态
 * @param null $type
 * @return mixed|string
 */
if(!function_exists('getAliOrderRefundStatus')) {
    function getAliOrderRefundStatus($type = null)
    {
        $types = _getStatusList('PUR_ALI_ORDER_REFUND_STATUS');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '其他状态';
        }
        return $types;
    }
}

/**
 * 1688 子订单状态
 * @param null $type
 * @return mixed|string
 */
if(!function_exists('getAliOrderSubStatus')) {
    function getAliOrderSubStatus($type=null){
        $types = _getStatusList('PUR_ALI_ORDER_SUB_STATUS');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '其他状态';
        }
        return $types;
    }
}

/**
 * 1688退款原因
 * @param null $type
 * @return mixed|string
 */
if(!function_exists('getAliOrderRefundReason')) {
    function getAliOrderRefundReason($type = null)
    {
        $types = _getStatusList('PUR_ALI_ORDER_REFUND_REASON');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '其他状态';
        }
        return $types;
    }
}

/**
 * 卖家拒绝退货原因
 * @param null $type
 * @return mixed|string
 */
if(!function_exists('getSaleRefuseReason')){
    function getSaleRefuseReason($type=null){
        $types = _getStatusList('PUR_ALI_ORDER_SALE_REFUND_REASON');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '其他状态';
        }
        return $types;
    }
}

/**
 * 退款退货类型
 * @param null $type
 * @return mixed|string
 */
if(!function_exists('getRefundType')){
    function getRefundType($type=null){
        $types = _getStatusList('PUR_ALI_ORDER_REFUND_TYPE');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '其他状态';
        }
        return $types;
    }
}