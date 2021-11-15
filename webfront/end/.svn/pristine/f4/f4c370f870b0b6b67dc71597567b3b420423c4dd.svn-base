<?php
/**
 * 获取运输方式 列表
 * @author harvin
 * @param string $type
 * @return array
 */
function getSettlement_method($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUUPLIER_SETTLEMENT_METHOD');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 获取运输方式 列表
 * @author Jolon
 * @param string $type
 * @return array
 */
function getShippingMethod($type = null){
    $types = _getStatusList('PUR_PURCHASE_SHIPPING_METHOD');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 供应商支付方式
 * @author Jolon
 * @param string $type
 * @return array
 */
function getSupplierPayMethod($type = null){
    $types = _getStatusList('PUR_PURCHASE_PAY_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}


/**
 * 获取供应商合作状态
 * @author jeff
 */
if (!function_exists('getCooperationStatus')) {
    function getCooperationStatus($type = null){
        $types = _getStatusList('PUR_SUPPLIER_COOPERATION_STATUS');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 1688供应商ID是否一致
 * @author Jolon
 * @param int type
 * @return array|bool
 */
if( !function_exists('getEqualSupId')){
    function getEqualSupId($type = null){
        $status = [
            '1' => '是', '2' => '否','0' => '空'
        ];
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}

/**
 * 1688供应商名称是否一致
 * @author Jolon
 * @param int type
 * @return array|bool
 */
if( !function_exists('getEqualSupName')){
    function getEqualSupName($type=null){
        $status = [
            '1' => '是', '2' => '否','0' => '空'
        ];
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}


if (!function_exists('getPurIsDiversionStatus')) {
    function getPurIsDiversionStatus($type = null){
        $types = _getStatusList('PUR_IS_DIVERSION_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}