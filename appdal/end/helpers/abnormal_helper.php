<?php
/**
* 采购异常辅助函数
* User: Jaxton
* Date: 2019/1/16  13:50
*/

/**
* 异常是否处理
* @author Jaxton
* @param string $type
* @return array|string
*/
if( !function_exists('getAbnormalHandleResult')){
	function getAbnormalHandleResult($type=null){

        $types = _getStatusList('PUR_PURCHASE_ABNORMAL_HANDLE_RESULT');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
	}
}

/**
* 仓库处理结果
* @author Jaxton
* @param string $type
* @return array|string
*/
if( !function_exists('getWarehouseHandleResult')){
	function getWarehouseHandleResult($type=null){
        $types = _getStatusList('PUR_PURCHASE_ABNORMAL_WAREHOUSE_HANDLE_RESULT');
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
 * 异常列表责任部门 department_ch PUR_PURCHASE_DEPARTMENT_DATA
 * @author:luxu
 * @time:2021年9月2号
 **/
if( !function_exists('getdepartmentData')){
    function getdepartmentData($type=null){
        $types = _getStatusList('PUR_PURCHASE_DEPARTMENT_DATA');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}






/**
* 处理类型
* @author Jaxton
* @param string $type
* @return array|string
*/
if( !function_exists('getAbnormalHandleType')){
	function getAbnormalHandleType($type=null){
        $types = _getStatusList('PUR_PURCHASE_ABNORMAL_ABNORMAL_HANDLE_TYPE');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
	}
}


/* 次品类型
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getAbnormalDefectiveType')){
    function getAbnormalDefectiveType($type=null){
       $types = _getStatusList('PUR_PURCHASE_ABNORMAL_ABNORMAL_DEFECTIVE_TYPE');
      if(!is_null($type)){
          return isset($types[$type]) ? $types[$type]:'';
       }
       return $types;
    }
}

/**
* 报损审核状态
* @author Jaxton
* @param string $type
* @return array|string
*/
if( !function_exists('getReportlossApprovalStatus')){
	function getReportlossApprovalStatus($type=null){
        $types = _getStatusList('PUR_PURCHASE_ABNORMAL_REPORTLOSS_APPROVAL_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
	}
}

/**
 * 报损责任承担方式
 * @author Jolon
 * @param string $type
 * @return array|mixed|string
 */
function getReportlossResponsibleParty($type = null){
    $types = _getStatusList('PUR_PURCHASE_REPORTLOSS_RESPONSIBLE_PARTY');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
* 报损审核状态
* @author Jaxton
* @param string $type
* @return array|string
*/
if(!function_exists('generateOrderNo')){
	function generateOrderNo(){
		$order_no=date('YmdHis').rand(10000,99999);
		return $order_no;
	}
}

/**
* 异常是否推送至仓库状态
* @author Jaxton
* @param string $type
* @return array|string
*/
if(!function_exists('getIsPushWarehouse')){
	function getIsPushWarehouse($type=null){
        $types = _getStatusList('PUR_PURCHASE_ABNORMAL_IS_PUSH_WAREHOUSE');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
	}
}

/**
 * 异常是否异常状态
 * @author Jaxton
 * @param string $type
 * @return array|string
 */
if(!function_exists('getReportLossIsAbnormal')){
    function getReportLossIsAbnormal($type=null){
        $types = _getStatusList('PUR_PURCHASE_REPORTLOSS_ABNORMAL_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 责任小组
 * @author Jaxton
 * @param string $type
 * @return array|string
 */
if(!function_exists('getDutyGroup')){
    function getDutyGroup($type=null){
        $types = _getStatusList('PUR_PURCHASE_ABNORMAL_DUTY_GROUP');
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
 * 多货类型
 * @author Dean
 * @param string $type
 * @return array|string
 */
if( !function_exists('getMultipleGoodsStatus')){
    function getMultipleGoodsStatus($type=null){
        $types = _getStatusList('PUR_MULTIPLE_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}


/**
 * 入库退货-运费支付方式
 * @author Jolon
 * @param string $type
 * @return array|string
 */
if( !function_exists('getReturnFreightPaymentType')){
    function getReturnFreightPaymentType($type=null){

        $types = [
            '1' => '到付',
            '2' => '寄付(现结)',
            '3' => '寄付(月结)'
        ];
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}
