<?php
/**
 * 跨境宝供应商 状态列表
 * @author Jolon
 * @param string $type
 * @return array
 */
function getCrossBorder($type = null)
{
    $types = _getStatusList('PUR_PURCHASE_SUUPLIER_CROSSB_ORDER');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 供应商 禁用启用列表
 * @author Jolon
 * @param string $type
 * @return array
 */
function getSupplierDisable($type = null)
{
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_DISABLE');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 获取结算方式 列表
 * @author harvin
 * @param string $type
 * @return array
 */
function getSettlement_method($type = null)
{
    $types = _getStatusList('PUR_PURCHASE_SUUPLIER_SETTLEMENT_METHOD');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}


/**
  * 供应商是否开启推送门户下拉框
 **/

function getSupplierGateWays(){

    return [

        1=>'是',
        2 => '否'
    ];
}
/**
 * 获取运输方式 列表
 * @author Jolon
 * @param string $type
 * @return array
 */
function getShippingMethod($type = null)
{
    $types = _getStatusList('PUR_PURCHASE_SHIPPING_METHOD');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 供应商支付方式
 * @author Jolon
 * @param string $type
 * @return array
 */
function getSupplierPayMethod($type = null)
{
    $types = _getStatusList('PUR_PURCHASE_PAY_TYPE');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 供应商审核记录 状态
 * @author Jolon
 * @param string $type
 * @return array
 */
function getSupplierAuditResultStatus($type = null)
{
    $types = _getStatusList('PUR_SUPPLIER_AUDIT_RESULTS_STATUS');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * @desc 判断当前审核到哪个阶段(测试时可以使用，生产线使用用户角色来判断是哪个阶段)
 * @author Jackson
 * @parame array $parames 更新数据
 * @parame array $updateBefore 更前的数据
 * @Date 2019-02-26 17:01:00
 * @return array()
 **/
function get_current_review_status(&$parames, $updateBefore)
{

    $_auditStatus = 0;
    if (!empty($updateBefore)) {

        //判断是否包含不同环节审核数据
        if (count(array_unique(array_column($updateBefore, 'audit_status'))) > 1) {
            return false;
        }

        foreach ($updateBefore as $key => $item) {

            $audit_status = $item['audit_status'];
            if ($parames['audit_status'] == 1) {
                //审核通过
                if (in_array($audit_status, array(0, 1))) {
                    $_auditStatus = 2;
                } elseif (in_array($audit_status, array(2, 3))) {
                    $_auditStatus = 4;
                } elseif (in_array($audit_status, array(4, 5))) {
                    $_auditStatus = 6;
                } else {
                    //todo
                }
            } else {
                //驳回
                if (in_array($audit_status, array(0, 1))) {
                    $_auditStatus = 1;
                } elseif (in_array($audit_status, array(2, 3))) {
                    $_auditStatus = 3;
                } elseif (in_array($audit_status, array(4, 5))) {
                    $_auditStatus = 5;
                } else {
                    //todo
                }
            }
            if ($audit_status == 6) {
                $_auditStatus = 6;
            }
            break;

        }
    }
    $parames['audit_status'] = $_auditStatus;
    //增加审核人及时间
    $parames['audit_user'] = getActiveUserName();
    $parames['audit_time'] = date('Y-m-d H:i:s', time());
    return true;

}


/**
 * 获取供应商可选支付平台
 * @author harvin
 */

function get_supplier_payment_platform($type = null){
     $types = _getStatusList('PUR_PURCHASE_SUPPLIER_PLATFORM');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}
/**
 * 获取供应商所以支付平台
 * @author harvin
 */

function get_supplier_payment_platform_all($type = null){
    $types = _getStatusList('PUR_PURCHASE_SUPPLIER_PAYMENT_PLATFORM');
    if (!is_null($type)) {
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}


if (!function_exists('supplierIsDisabled')) {
    /**
     * 判断供应商是否处于禁用状态
     * @param $supplier_code
     * @return mixed
     */
    function supplierIsDisabled($supplier_code)
    {
        $CI = &get_instance();
        $CI->load->model('supplier/Supplier_model');

        return $CI->Supplier_model->is_disabled($supplier_code);
    }
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
 * 供应商等级
 * @author harvin
 * @param string $type
 * @return mixed
 */
function getSupplierLevel($type = null){
    $types = _getStatusList('PUR_SUPPLER_LEVEL');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 供应商来源
 * @author harvin
 * @param string $type
 * @return mixed
 */
function getSupplierSource($type = null){
    $types = _getStatusList('PUR_SUPPLER_SOURCE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}


/**
 * 供应商临时转常规状态
 * @author harvin
 * @param string $type
 * @return mixed
 */
function getPurIsDiversionStatus($type = null){
    $types = _getStatusList('PUR_IS_DIVERSION_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
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

/** 供应商操作类型
 * @param null $type
 * @return mixed|string
 */
function getSupplierOperateType($type = NULL){
    $types= _getStatusList('PUR_SUPPLIER_OPERATE_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }

    return $types;
}

/** 供应商审核申请类型 1 创建（产品系统） 2 更新 3 启用  4 创建（erp）
 * @param null $type
 * @return mixed|string
 */
function getSupplierApplyType($type = NULL){
    $types= _getStatusList('PUR_SUPPLIER_APPLY_TYPE');
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
 * 申请状态
 * @author jackson 2019-1-22
 * @param string $type
 * @return mixed
 * **/
function show_status_level_grade($type = null){
    $types = _getStatusList('PUR_PURCHASE_LEVEL_GRADE_SHOW_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}

/**
 * 供应商拜访状态
 * @author jackson 2019-1-22
 * @param string $type
 * @return mixed
 * **/
function show_supplier_visit_status($type = null){
    $types = _getStatusList('PUR_SUPPLIER_VISIT_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}


/**
 * 拜访申请部门
 * @author jackson 2019-1-22
 * @param string $type
 * @return mixed
 * **/
function show_supplier_visit_depart($type = null){
    $types = _getStatusList('PUR_SUPPLIER_VISIT_DEPART');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}


/**
 * 拜访目的
 * @author jackson 2019-1-22
 * @param string $type
 * @return mixed
 * **/
function show_supplier_visit_aim($type = null){
    $types = _getStatusList('PUR_SUPPLIER_VISIT_AIM');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }

    return $types;
}




