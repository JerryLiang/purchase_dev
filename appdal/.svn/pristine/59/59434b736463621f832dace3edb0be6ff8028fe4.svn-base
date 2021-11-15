<?php
/**
 * 产品状态列表
 * @param null $type
 * @return array
 */
function getProductStatus($type = null){
    $types= _getStatusList('PUR_PURCHASE_PRODUCT_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 产品是否已经关联1688
 * @param null $type
 * @return mixed|string
 */
function getRelateAli($type = null){
    $types= _getStatusList('PUR_PURCHASE_PRODUCT_RELATE_ALI');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 货源状态
 * @param null $supplystatus
 * @return array
 */
function getProductsupplystatus($supplystatus = null){
    $supplystatus_arr= _getStatusList('PUR_PURCHASE_PRODUCT_SUPPLYSTATUS');
    if(!is_null($supplystatus)){
        return isset($supplystatus_arr[$supplystatus]) ? $supplystatus_arr[$supplystatus]:'';
    }
    return $supplystatus_arr;
}

/**
 * 票点是否为空
 **/

function getTicketedPoint()
{
    return [1=>'空',2=>'非空'];
}



/**
 * 审核状态
 * @param null $type
 * @return array
 */
function getProductauditstatus($auditstatus = null){
    $auditstatus_arr= _getStatusList('PUR_PURCHASE_PRODUCT_AUDITSTATUS');
    if(!is_null($auditstatus)){
        return isset($auditstatus_arr[$auditstatus]) ? $auditstatus_arr[$auditstatus]:'';
    }
    return $auditstatus_arr;
}


/**
 * SKU 屏蔽记录状态列表
 * @param int $status 状态
 * @return mixed
 */
function getProductScreeStatus($status = null){
    $status_list= _getStatusList('PUR_PURCHASE_PRODUCT_SCREESTATUS');
    if(!is_null($status)){
        return isset($status_list[$status]) ? $status_list[$status]:'';
    }
    return $status_list;
}

/**
 * 获取 SKU屏蔽 申请原因列表
 * @param string $key
 * @return array|bool|mixed
 */
function getScreeApplyReason($key = null){
    $reason_list= _getStatusList('PUR_PURCHASE_PRODUCT_SCREE_APPLY_REASON');
    // 不准查询值为空的 Name
    if($key !== null AND empty($key)) return false;

    if(!is_null($key)){
        return isset($reason_list[$key])?$reason_list[$key]:'';
    }
    return $reason_list;
}

/**
 * 获取产品线 名称
 * @author Jolon
 * @param int $product_line_id 产品线ID
 * @return array|bool
 */
if( !function_exists('getProductLineName')){
    function getProductLineName($product_line_id){
        $CI = &get_instance();
        $CI->load->model('product_line_model','',false,'product');

        return $CI->product_line_model->get_product_line_name($product_line_id);
    }
}

/**
 * 仓库类型列表
 * @author Jolon
 * @param int $key
 * @return array|bool|mixed
 */
function getWarehouseType($key = null){
    $type_list= _getStatusList('PUR_PURCHASE_PRODUCT_WAREHOUSE_TYPE');
    // 不准查询值为空的 Name
    if($key !== null AND empty($key)) return false;

    if($key) return isset($type_list[$key])?$type_list[$key]:false;

    return $type_list;
}

/**
 * 获取产品信息修改审核状态
 * @author Jaxton
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductModStatus')){
    function getProductModStatus($type=null){
        $status= _getStatusList('PUR_PURCHASE_PRODUCT_MOD_STATUS');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}
/**
 * 获取产品信息修改 整合状态
 * @author Jaxton
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductModType')){
    function getProductModType($type = null){
        $status= _getStatusList('PUR_PURCHASE_PRODUCT_MOD_TYPE');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}

/**
 * 获取产品信息修改 整合状态
 * @author Jaxton
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductIntegrateStatus')){
    function getProductIntegrateStatus($type = null){
        $status= _getStatusList('PUR_PURCHASE_PRODUCT_MOD_INTEGRATE_STATUS');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}

/**
 * 获取产品信息修改是否拿样下拉
 * @author Jaxton
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductIsSampleDownBox')){
    function getProductIsSampleDownBox($type=null){
        $status= _getStatusList('PUR_PURCHASE_PRODUCT_IS_SAMPLE_DOWN_BOX');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}

/**
 * 获取产品信息修改是否拿样状态
 * @author Jaxton
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductIsSample')){
    function getProductIsSample($type=null){
        $status= _getStatusList('PUR_PURCHASE_PRODUCT_IS_SAMPLE_DOWN_BOXS');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}

/**
 * 获取产品信息修改是否拿样状态
 * @author Jaxton
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductPriceChange')){
    function getProductPriceChange($type=null){
        $status= _getStatusList('PUR_PURCHASE_PRICE_TREND_STATUS');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}

/**
 * 二次包装列表审核状态
 * @author Jaden
 * @param int type
 * @return array|bool
 */
if( !function_exists('getproductRepackageStatus')){
    function getproductRepackageStatus($type=null){
        $status= _getStatusList('PUR_PURCHASE_REPACKAGE_STATUS');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}


/**
 * SKU降本-价格变化趋势
 * @author Jaden
 * @param int type
 * @return array|bool
 */
if( !function_exists('getPriceTrendStatus')){
    function getPriceTrendStatus($type=null){
        $status= _getStatusList('PUR_PURCHASE_PRICE_TREND_STATUS');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}



/**
 * 获取产品信息修改样品检验结果
 * @author Jaxton
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductSampleCheckResult')){
    function getProductSampleCheckResult($type=null){
        $status= _getStatusList('PUR_PURCHASE_PRODUCT_SAMPLE_CAHECK_RESULT');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
}


/**
 * 包裹加急推送状态
 * @author Jaden
 * @param int type
 * @return array|bool
 */
if( !function_exists('getParcelUrgentState')){
    function getParcelUrgentState($type=null){
        $status= _getStatusList('PUR_PURCHASE_PARCEL_URGENT');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
    }
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
 * 产品列表SKU是否异常
 * @author Jaden
 * @param int type
 * @return array|bool
 */
if( !function_exists('getProductAbnormal')){
    function getProductAbnormal($type=null){
        $status= _getStatusList('PUR_PRODUCT_IS_ABNORMAL');
        if(!is_null($type)){
            return isset($status[$type]) ? $status[$type]:'';
        }
        return $status;
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

