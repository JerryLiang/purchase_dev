<?php

/**
 * 是否含税
 * @param null $type
 * @return mixed
 */
function getIsIncludeTax($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_IS_CLUDE_TAX');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 采购单是否退税（选择下拉框）
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getIsDrawback($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_IS_DRAWBACK');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
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
    $types= _getStatusList('PUR_PURCHASE_IS_DRAWBACK');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}
/**
 * 数据权限角色
 * @author harvin
 * @param string $type 类型
 * @return array|mixed
 */
function getRole($type = null){
    $types= _getStatusList('PUR_PUCHASE_ROLE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}
/**
 * 数据权限角色(产品开发-财务)
 * @param type $type
 * @return type
 */
function  getRolefinance($type = null){
    $types= _getStatusList('PUR_PRODUCT_FINANCE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;   
}
/**
 * 数据权限角色(销售敏感字段屏蔽)
 * @param type $type
 * @return type
 */
function  getRolexiao($type = null){
    $types= _getStatusList('PUR_SUPPLIER_XIAO_SU');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;   
}


/**
 * 业务线 列表
 * @author Jolon
 * @param string $type
 * @return array
 */
function getPurchaseType($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 采购需求是否生成采购单
 * @author Jaxton
 * @param string $type
 * @return array
 */
function getIsCreateOrder($type = null){
    
    $types= _getStatusList('PUR_IS_CREATE_ORDER');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 产品是否是新品
 * @author Jolon
 * @return array
 */

function getProductIsNew($type = null){
    $types= _getStatusList('PUR_PURCHASE_PRODUCT_IS_NEW');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
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


/****************************采购需求 Start**************************/

/**
 * 采购需求-需求状态
 * @author Jolon
 * @param null $type
 * @return array|mixed|string
 */
function getSuggestStatus($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_SUGGEST_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 获取需求单完结状态
 */
if(!function_exists('getDemandStatus')){
    function getDemandStatus($type = null){
        $types= _getStatusList('PUR_PURCHASE_DEMAND_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 是否生成计划单
 * @author Jolon
 * @param string $type
 * @return array|string
 */
function getIsPlanOrder($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_IS_PLAN_ORDER');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 采购需求界面 采购单状态
 * @author Jolon
 * @param string $type
 * @return array|string
 */
function getPurOrderStatus($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_PUR_ORDER_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
  * 供应商启用原因
 **/

function getSupplierEnable($type = NULL){

    return [

        1=>'名下绑定SKU需要采购产品',
        2=>'失误操作，被禁用',
        3=>'款项未结清，需要修改银行信息付款',
        4=>'原账期不达标，后经谈判达标',
        5=>'原单价原因禁用，后经谈判符合现公司要求',
        6=>'原资料不齐，后补齐资料',
        7=>'原临时供应商，条件符合常规供应商',
        8=>'原公司经营异常，后改善正常',
        9=>'其他'
    ];
}

/**
 * 采购需求界面 采购单状态
 * @author Jolon
 * @param string $type
 * @return array|string
 */
function getPurOrderStatusAll($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_PUR_ORDER_STATUS_ALL');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 待驳回界面 采购单状态
 * @author Jolon
 * @param string $type
 * @return array
 */
function getPurOrderStatusForWaitReject($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_PUR_ORDER_STATUS_FOR_WAITREJECT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 获取支付方式
 * @author harvin
 * @param string $type
 * @return array|string
 */
function getPayType($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_PAY_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
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
 * 获取 请款方式
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getRequisitionMethod($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_REQUISITION_METHOD');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 获取计算方式
 * @author harvin 2019-1-8
 * @param string $type
 * @return array
 */

 function freight_formula_mode($type = null){
    $types= _getStatusList('PUR_PURCHASE_FREIGHT_FORMULA_MODE');
     if($type === 0){
         return 0;
     }
     if(!is_null($type)){
         return isset($types[$type]) ? $types[$type]:'';
     }
    return $types;
}



/**
 * 运费方式
 * @author Jolon
 * @param string $type
 * @return array
 */
function getFreightPayment($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_FREIGHT_PAYMENT');
    if($type === 0){
        return 0;
    }
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 结算比例
 * @author Jolon
 * @return array
 */
function getSettlementRatio()
{
    $types= _getStatusList('PUR_PURCHASE_SETTLEMENT_RATIO');
    return $types;
}

/**
 * 采购需求列表-产品信息不全-拦截原因
 * @author Jolon
 * @return array
 */
function getInterceptReason()
{
    $types= _getStatusList('PUR_PURCHASE_INTERCEPT_REASON');
    return $types;
}

/****************************采购需求 End**************************/


/****************************采购单 Start**************************/
/**
 * 获取采购员下拉框数据(临时函数 等第三接口)
 * @author harvin 2019-1-8
 * @param string $user_id  用户ID
 * @return array|string
 */
function get_buyer_name($user_id = null)
{
    $CI = &get_instance();
    $CI->load->model('user/Purchase_user_model');
    $data = $CI->Purchase_user_model->get_user_all_list();
    if($data and is_array($data)){
        $user_list = isset($data) ? array_column($data, 'name', 'id') : [];
        if (!is_null($user_id)) {
            if(isset($user_list[$user_id])){
                return $user_list[$user_id];
            }else{
                $data = $CI->Purchase_user_model->get_user_info_by_id($user_id);
                return isset($data['user_name'])?$data['user_name']:'';
            }
        }
        return $user_list;
    }else{
        return [];
    }
}

/**
 * 获取采购员采购账号（配置的 1688 子账号 ）
 * @author Jolon
 * @param int $buyer_id 采购员ID
 * @param bool $is_down_box 下拉框数组
 * @return array
 */
if (!function_exists('getPurchaseAccountList')) {
    function getPurchaseAccountList($buyer_id = null, $is_down_box = true)
    {
        $CI = &get_instance();
        $CI->load->model('finance/alibaba_account_model');

        $list = $CI->alibaba_account_model->get_purchase_account_list($buyer_id);
        if ($list and $is_down_box) {
            $list = array_column($list, 'account', 'account');
        }

        return $list;
    }
}
/**
 * 获取采购员采购账号（采购员所有可用的账号）
 * @author Jolon
 * @param int $buyer_id 采购员ID
 * @param bool $is_down_box 下拉框数组
 * @return array
 */
if (!function_exists('getUserEnablePurchaseAccount')) {
    function getUserEnablePurchaseAccount($buyer_id = null, $is_down_box = true)
    {
        $CI = &get_instance();
        $CI->load->model('finance/alibaba_account_model');

        $list = $CI->alibaba_account_model->get_purchase_acccount($buyer_id);
        return $list;
    }
}

/**
 * 采购单界面-驳回原因
 * @author Jolon
 * @return array
 */
function getPurchaseOrderRejectReason()
{
    $types= _getStatusList('PUR_PURCHASE_ORDER_REJECT_REASON');
    return $types;
}

/**
 * 采购单 加急状态
 * @param string $type
 * @return array|mixed
 */
function getIsExpedited($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_IS_EXPEDITED');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 备货单是否欠货（选择下拉框）
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

/**
 * SKU 是否为海外仓首单（下拉框）
 * @author:luxu
 * @time:2020/3/20
 * @return array|mixed
 **/
function is_overseas_first_order($type = NULL){

   return  [IS_OVERSEAS_FIRST_ORDER_YES=>'是',IS_OVERSEAS_FIRST_ORDER_NO=>'否'];


}
/**
 * 采购单状态列表
 * @author Jolon
 * @param string $type
 * @return array|mixed|string
 */
function getPurchaseStatus($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_ORDER_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    if (isset($types[PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT])) {
        unset($types[PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT]);
    }
    return $types;
}


function getContainer($type = null){
    $types= _getStatusList('PUR_CONTAINER_DATA');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
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
    $types= _getStatusList('PUR_PURCHASE_REQUEST_PAYOUT_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 获取请款单状态
 * @param string $type
 * @param int $show_type 显示下拉框类型（1.全部，2.请款单、应付款单）
 * @return array
 */
function getPayStatus($type = null,$show_type = 1)
{    
    $types= _getStatusList('PUR_PURCHASE_PAY_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    if($types and $show_type == 2) unset($types['10'],$types['50']);
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
 * 获取门户系统状态
 * @author:luxu
 * @time:2020/5/28
 **/
function getPullGateWayStatus($getGateWaysStatus=NULL,$show_type=1){


    $types= _getStatusList('PULL_GATEWAYS_STATUS');
    if(!is_null($getGateWaysStatus)){
        return isset($types[$getGateWaysStatus]) ? $types[$getGateWaysStatus]:'';
    }
    return NULL;

}

/**
 * 获取 对账单 请款状态
 * @param string $type
 * @param int $show_type 显示下拉框类型（1.全部，2.请款单、应付款单）
 * @return array
 */
function getStatementPayStatus($type = null,$show_type = 1)
{
    $types = getPayStatus();
    //$types['5'] = '已生成对账单';// 对账单付款状态比 之前的请款单状态多一个

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    if($types and $show_type == 2) unset($types['10'],$types['50']);
    if($types) ksort($types);// 根据 Key 升序
    return $types;
}

/**
 * 获取 对账单 是否上传扫描件
 * @param string $type
 * @return array
 */
function getStatementPdfStatus($type = null)
{
    $types= _getStatusList('PUR_STATEMENT_PDF_STATUE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 获取收款单收款状态
 * @author Jolon
 * @param string $type
 * @return array
 */
function getReceivePayStatus($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_RECEIVE_PAY_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 采购单 采购来源类型
 * @author Jolon
 * @return array
 */
function getPurchaseSource($type = null)
{
    $source= _getStatusList('PUR_PURCHASE_SOURCE');
    if(!is_null($type)){
        return isset($source[$type]) ? $source[$type]:'';
    }
    return $source;
}


/**
 * 返回结果屏蔽字段
 * @author:luxu
 * @param  $list          array   屏蔽数据
 *         $shield        array   屏蔽字段
 *         $role          array   角色
 *         $shieldrole    array|NULL 屏蔽角色
 * @time:2021年2月5号
 **/

function ShieldingData($list,$shield=[],$role=[],$shieldrole = []){



//    // 如果没有屏蔽角色，或者没有需要屏蔽的字段.数据按照原来的格式返回
    if(  empty($shield) || empty($list)){

        return $list;
    }

    // 如果传入角色信息包含ADMIN 就不需要屏蔽

    if( in_array('admin',$role) || in_array('供应链总监',$role)){

        return $list;
    }

    //采购部,品控部,财务部,供管部外,其他所有角色登录采购系统时,在系统任何页面都无法查看到供应商名称,供应商代码

    $roleString = implode(",",$role);
    if(strstr($roleString,'采购') || strstr($roleString,'财务') || strstr($roleString,'供应') || strstr($roleString,'品控')){

        return $list;
    }

//    // 判断屏蔽角色和角色是否有交集，如果没有交集不需要屏蔽字段
//
//    $role_verify = array_intersect($role, $shieldrole);
//    if(!$role_verify){
//
//        return $list;
//    }
//
    foreach( $list as $list_key=>&$list_value){
        $keys = array_keys($list_value);
        $interkeys = array_intersect($keys,$shield);
        if(!empty($interkeys)){
            $shieldData = [];
            foreach( $interkeys as $inter_key=>$inter_value){

                $shieldData[$inter_value] = '*';
            }
            $list[$list_key] = array_replace($list_value,$shieldData);
        }
    }
    return $list;
}

/**
 * 采购单 需求类型
 * @author Jolon
 * @return array
 */
function getPurchasedemandtype()
{
    $source= _getStatusList('PUR_PURCHASE_DEMAND_TYPE');
    return $source;
}


/**
 * 获取采购单是否核销
 * @author harvin 2019-1-8
 * @param string $type
 * @return array|string
 */

function getDestroy($type = null){
    $types= _getStatusList('PUR_PURCHASE_ORDER_DESTROY');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 合同主状态 列表
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getCompactStatus($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_REQUEST_PAYOUT_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 是否已上传扫描件：未上传、已上传
 * @param null $type
 * @return array|mixed|string
 */
function getCompactIsFileUploaded($type = null){
    $types = ['1' => '未上传','2' => '待采购确认','3'=>'采购驳回','4'=>'已上传','5' => '已作废','6' => '已完结'];
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
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
function getTransportStyle($type = null)
{
    $types= _getStatusList('PUR_PURCHASE_IRANSPORT_STYLE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 财务管理 付款状态只显示待财务审核、待财务付款、已付款、财务驳回 下拉框
 * @author harvin 2019-1-19=6
 * @param string $type
 * @return array
 */
function payment_status($type = null){
    $types= _getStatusList('PUR_PURCHASE_PAYMENT_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}



/**
 * 是否商检
 * @author Jaden 2019-1-19=6
 * @param string $type
 * @return array
 */
function check_goods($type = null){
    $types= _getStatusList('PUR_PURCHASE_IS_CHECK_GOODS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 库存是否异常
 * @author Jaden 2019-1-19=6
 * @param string $type
 * @return array
 */
function abnormal_status_type($type = null){
    $types= _getStatusList('PUR_PURCHASE_IS_ABNORMAL_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 是否完结
 * @author Jaden 2019-1-19=6
 * @param string $type
 * @return array
 */
function end_status($type = null){
    $types= _getStatusList('PUR_PURCHASE_IS_END_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 报关状态
 * @author Jaden 2019-1-19=6
 * @param string $type
 * @return array
 */
function customs_statu_type($type = null){
    $types= _getStatusList('PUR_PURCHASE_CUSTOMS_STATU');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 发票清单状态
 * @author Jaden 2019-1-19=6
 * @param string $type
 * @return array
 */
function invoice_number_status($type = null){
    $types= _getStatusList('PUR_PURCHASE_INVOICE_NUMBER_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}


/**
 * 采购主体
 * @author Jaden 2019-1-19=6
 * @param string $type
 * @param bool   $modifier  修饰选项
 * @return array
 */
function get_purchase_agent($type = null,$modifier = false){
    $types= _getStatusList('PUR_PURCHASE_AGENT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    if(isset($types['QHYB'])) unset($types['QHYB']);// 删除 QHYB

    if($modifier === true) $types = array_merge(['none' => '空'],$types);// 增加一个空的查询项
    
    return $types;
}

/**
 * 取消未到货状态
 * **/
 function get_cancel_status($type = null){
     $types= _getStatusList('PUR_PURCHASE_CANCEL');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
 }


/**
 * 供应商支付方式
 * **/
 function get_supplier_pay($type = null){
     $types= _getStatusList('PUR_PURCHASE_SUPPLIER_METHOD');
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
 * 1688账号状态
 * @param type $type
 * @return type
 */
function  getAccountstatus($type = null){
    $types= _getStatusList('PUR_PURCHASE_ACCOUNT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}
/**
 * 1688子账号状态 级别
 * @param type $type
 * @return type
 */
function  getAccountsSublevel($type = null){
    $types= _getStatusList('PUR_OURCHASE_ACCOUNT_SUB');
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

    $types= _getStatusList('PUR_PURCHASE_PRODUCT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 *  是否精品状态
 * @param type $type
 * @return type

 **/
function getISBOUTIQUE($type = NULL) {

    $types= _getStatusList('PUR_PURCHASE_IS_BOUTIQUE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
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


/**
 * 宝付支付状态
 */
if (!function_exists('getBaofoostatus')) {

    function getBaofoostatus($type = NULL) {
        $types = _getStatusList('PUR_PURCHASE_BAOFOOPAY');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }

}

/**
 * 宝付支付状态
 */
if (!function_exists('getBaofoostatus2')) {

    function getBaofoostatus2($type = NULL) {
        $types = _getStatusList('PUR_PURCHASE_BAOFOOPAY_2');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
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
 * 作废原因分类
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
 * 退税订单跟踪列表 - 开票状态
 * @author Manson
 * @param string $type
 * @return array|mixed|string
 */
function getInvoiceStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_INVOICE_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 退税订单跟踪列表 - 合同开票状态
 * @author Manson
 * @param string $type
 * @return array|mixed|string
 */
function getContractInvoiceStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_CONTRACT_INVOICING_STATUS');
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
 * 验货业务线选择下拉框
 * @author Justin
 * @param string $type
 * @return array|mixed|string
 */
function getBusinessLine($type = null){
    $types = _getStatusList('PUR_PURCHASE_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 验货是否启用选择下拉框
 * @author Justin
 * @param string $type
 * @return array|mixed|string
 */
function getEnableStatus($type = null){
    $types = _getStatusList('PUR_SUPPLER_CHECK_RULE_ENABLE_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 验货单状态
 * @author Justin
 */
if (!function_exists('getCheckOrderStatus')) {
    function getCheckOrderStatus($type = NULL) {
        $types = _getStatusList('PUR_SUPPLER_CHECK_ORDER_STATUS');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 验货单结果
 * @author Justin
 */
if (!function_exists('getCheckOrderResult')) {
    function getCheckOrderResult($type = NULL) {
        $types = _getStatusList('PUR_SUPPLER_CHECK_ORDER_RESULT');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 验货单类型
 * @author Justin
 */
if (!function_exists('getCheckOrderType')) {
    function getCheckOrderType($type = NULL) {
        $types = _getStatusList('PUR_SUPPLER_CHECK_ORDER_TYPE');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 验货是否异常下拉框
 * @author Justin
 */
if (!function_exists('getCheckOrderAbnormalStatus')) {
    function getCheckOrderAbnormalStatus($type = NULL) {
        $types = _getStatusList('PUR_SUPPLER_CHECK_IS_ABNORMAL');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 验货是否加急下拉框
 * @author Justin
 */
if (!function_exists('getCheckOrderUrgentStatus')) {
    function getCheckOrderUrgentStatus($type = NULL) {
        $types = _getStatusList('PUR_SUPPLER_CHECK_IS_URGENT');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 验货特批出货下拉框
 * @author Justin
 */
if (!function_exists('getCheckOrderSpecialStatus')) {
    function getCheckOrderSpecialStatus($type = NULL) {
        $types = _getStatusList('PUR_SUPPLER_CHECK_IS_SPECIAL');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 验货检验次数下拉框
 * @author Justin
 */
if (!function_exists('getCheckOrderCheckTimes')) {
    function getCheckOrderCheckTimes($type = NULL) {
        $types = _getStatusList('PUR_SUPPLER_CHECK_TIMES');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}


/**
 * 入库后退货-处理状态
 * @author Manson
 * @return array
 */
function getReturnProcessingStatus($type = null)
{
    $types= _getStatusList('PUR_RETURN_PROCESSING_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 入库后退货-退货仓库
 * @author Manson
 * @return array
 */
function getReturnWarehouse($type = null)
{
    //暂时写死
    $types = [
        'SZ_AA' => '小包仓_塘厦',
        'CX'    => '小包仓_慈溪',
        'HM_AA' => '小包仓_虎门',
    ];

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 入库后退货-退货状态
 * @author Manson
 * @return array
 */
function getReturnStatus($type = null)
{
    $types= _getStatusList('PUR_RETURN_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}


/**
 * 入库后退货-是否确认签收
 * @author Manson
 * @return array
 */
function getReturnIsConfirmReceipt($type = null)
{
    $types= _getStatusList('PUR_RETURN_IS_CONFIRM_RECEIPT');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}



/**
 * 物流公司
 * @author Manson
 * @return array
 */
function getLogisticsCompany($type = null)
{

    $CI = &get_instance();
    $CI->load->model('purchase/Logistics_carrier_model');
    $types = $CI->Logistics_carrier_model->getLogisticsCompany();
    if($types and is_array($types)){
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }else{
        return [];
    }
}

/**
 * 运费支付类型
 * @author Manson
 * @param null $type
 * @return mixed|string
 */
function getFreightPaymentType($type = null)
{
    $types= _getStatusList('PUR_RETURN_FREIGHT_PAYMENT_TYPE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 退货原因
 * @author Manson
 * @param null $type
 * @return mixed|string
 */
function getReturnSeason($type = null)
{
    $types= _getStatusList('PUR_RETURN_SEASON');

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}



/**
 * 省下拉
 * @author Manson
 * @return array
 */
function getProvince($type = null)
{

    $CI = &get_instance();
    $CI->load->model('supplier/Supplier_address_model');
    $types = $CI->Supplier_address_model->get_address_list(REGION_TYPE_PROVINCE,1);

    if($types and is_array($types)){
        foreach ($types as $key => $item){
            $map[$item['region_code']] = $item['region_name'];
        }

        if (!is_null($type)) {

            return isset($map[$type]) ? $map[$type] : '';
        }
        return $map;
    }else{
        return [];
    }
}


/**
 * 结算方式变更原因
 * @author Manson
 * @param null $type
 * @return mixed|string
 */
function getSettlementChangeRes($type = null)
{
    $types= _getStatusList('PUR_SUPPLIER_SETTLEMENT_CHANGE_RES');

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}


/**
 * -------------------入库后的退货
 */
function getPurchaseReturnTrackingStatus($type = null){
    $types= _getStatusList('PUR_RETURN_TRACKING_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

function getIsConfirmReceipt($type = null){
    $types= _getStatusList('PUR_SUPPLER_CHECK_RULE_ENABLE_STATUS');

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 创建部门
 * @author Manson
 * @param null $type
 * @return mixed|string
 */
function getSupplierDepartmentType($type = null){
    $types= _getStatusList('PUR_SUPPLIER_DEPARTMENT_TYPE');

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 供应商-类目属性
 * @author Manson
 * @param null $type
 * @return mixed|string
 */
function getCategoryType($type = null)
{
    $types= _getStatusList('PUR_SUPPLIER_CATEGORY_TYPE');

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;

}

/**
 * 近一个月预估开发数量类型
 * @author Manson
 * @param null $type
 * @return mixed|string
 */
function getOneMouthNumType($type = null)
{
    $types= _getStatusList('PUR_SUPPLIER_ONE_MOUTH_NUM_TYPE');

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}


/**
 * 近六个月预估开发数量类型
 * @author Manson
 * @param null $type
 * @return mixed|string
 */
function getSixMouthNumType($type = null)
{
    $types= _getStatusList('PUR_SUPPLIER_SIX_MOUTH_NUM_TYPE');

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
}

/**
 * 供应商定级
 * @author Manson
 * @param null $type
 * @return mixed|string
 */
function getProdSupplierLevel($type = null)
{
    $types= _getStatusList('PUR_PROD_SUPPLIER_LEVEL');

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type] : '';
    }
    return $types;
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

/**
 * 核销-是否承诺贴码状态
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getPasteLabelStatus')){
    function getPasteLabelStatus($type=null){
        $types = _getStatusList('PUR_PURCHASE_PASTE_LABEL_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 核销-冲销状态
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getChargeAgainstStatus')){
    function getChargeAgainstStatus($type=null){
        $types = _getStatusList('PUR_STATEMENT_CHARGE_AGAINST_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 核销-入库数量
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getInstockQty')){
    function getInstockQty($type=null){
        $types = _getStatusList('PUR_STATEMENT_INSTOCK_QTY');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}


/**
 * 发运管理-计划部取消-审核状态
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getShipmentPlanCancelAuditStatus')){
    function getShipmentPlanCancelAuditStatus($type=null){
        $types = _getStatusList('PUR_SHIPMENT_PLAN_CANCEL_AUDIT_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}



/**
 * 核销-冲销是否完结
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getChargeAgainstFinishStatus')){
    function getChargeAgainstFinishStatus($type=null){
        $types = _getStatusList('PUR_STATEMENT_CHARGE_AGAINST_FINISHED');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 核销-退款冲销状态
 * @author Justin
 * @param string $type
 * @return array|string
 */
if( !function_exists('getRefundChargeAgainstStatus')){
    function getRefundChargeAgainstStatus($type=null){
        $types = _getStatusList('PUR_STATEMENT_REFUND_CHARGE_AGAINST_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

if(!function_exists('getBalanceAdjustmentOrderStatus')){
    function getBalanceAdjustmentOrderStatus($type=null){
        $types = _getStatusList('PUR_BALANCE_ADJUSTMENT_ORDER_STATUS');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}

/**
 * 权限配置-人员职级
 * @author Justin
 */
if (!function_exists('getUserRank')) {
    function getUserRank($type = NULL) {
        $types = _getStatusList('PUR_PERMISSION_RANK');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}

/**
 * 权限配置-人员是否离职
 * @author Justin
 */
if (!function_exists('getUserLeaveStatus')) {
    function getUserLeaveStatus($type = NULL) {
        $types = _getStatusList('PUR_USER_IS_LEAVE');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return (object)$types;
    }
}

/**
 * 权限配置-交接人是否为空
 * @author Justin
 */
if (!function_exists('getHandoverUserStatus')) {
    function getHandoverUserStatus($type = NULL) {
        $types = _getStatusList('PUR_HANDOVER_USER_IS_EMPTY');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}


/**
 * 仓库入库类型
 * @author Dean
 */
if (!function_exists('getWarehouseInStockType')) {
    function getWarehouseInStockType($type = NULL) {
        $types = _getStatusList('PUR_PURCHASE_WAREHOUSE_IN_STOCK_TYPE');
        if (!is_null($type)) {
            return isset($types[$type]) ? $types[$type] : '';
        }
        return $types;
    }
}


/**
 * 损益值 按 比例分摊到 指定数组集上（最后一个做减法）
 * @author Jolon
 * @param float $change_amount          损益值
 * @param array $apportion_list         键名和键值
 * @param string $sort                  对 $apportion_list 的排序，ASC 升序，DESC 降序
 * @return array|bool
 * @example
 *    参数  $apportion_list
 *                = array(
 *                  'key1' => 10,
 *                  'key2' => 30
 *               )
 *  分摊结果 $apportion_list =
 *                = array(
 *                  'key1' => 11,
 *                  'key2' => 33
 *               )
 */
if( !function_exists('apportionChangeAmount')){
    function apportionChangeAmount($change_amount, $apportion_list, $sort = null){
        $CI = &get_instance();
        $CI->load->library('purchase_service');

        $apportion_list = $CI->purchase_service->apportion_change_amount($change_amount, $apportion_list, $sort);

        return $apportion_list;
    }
}

if(!function_exists('handleBehaviorLogs')){
    function handleBehaviorLogs($data){
        $CI = &get_instance();
        $CI->load->model("logs/Handle_behavior_logs", "logs_models");
        $CI->logs_models->generateBehaviorLogs($data);
    }
}

/**
 * 获取html中的文字
 */
if(!function_exists('HtmlStringToText')){
    function HtmlStringToText($string){
        if($string){
            $html_string = htmlspecialchars_decode($string);
            $content = str_replace(" ", "", $html_string);
            $contents = strip_tags($content);
            return mb_substr($contents, 0);
        }else{
            return $string;
        }
    }
}


/**
 * 转换 交货日期
 * @param $delivery_date
 * @return string
 */
function convert_delivery_date($delivery_date){
    $delivery_date = trim($delivery_date);
    $delivery_date_year = substr($delivery_date,0,4);
    $delivery_date_month = substr($delivery_date,5,2);
    $delivery_date_day = substr($delivery_date,8,2);
    return '乙方货物应于'.$delivery_date_year.'年'.$delivery_date_month.'月'.$delivery_date_day.'日前到达甲方仓库';
}

/**
 * 获取取消未到货原因
 * @author yefanli
 * @param string $type
 * @return array|string
 */
if( !function_exists('getOrderCancelReason')){
    function getOrderCancelReason($type=null){
        $types = _getStatusList('PUR_PURCHASE_ORDER_CANCEL_REASON');
        if(!is_null($type)){
            return isset($types[$type]) ? $types[$type]:'';
        }
        return $types;
    }
}