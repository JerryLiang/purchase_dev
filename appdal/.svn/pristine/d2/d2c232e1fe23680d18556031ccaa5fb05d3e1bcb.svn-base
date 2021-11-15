<?php

/**
 * 合同 按比例请款 请款金额按比例拆分（最高3段）
 * @author Jolon
 * @param string $ratio        合同的结算比例 ，如：30%+70% 或 30%+30%+40%
 * @param float  $productMoney 合同的总产品额
 * @param bool   $is_string    是否拼接比例与金额
 * @return array
 */
if( !function_exists('compactPaymentPlanByRatio')){
    function compactPaymentPlanByRatio($ratio, $productMoney, $is_string = false){
        $CI = &get_instance();
        $CI->load->library('purchase_service');

        $payment_list = $CI->purchase_service->compactPaymentPlanByRatio($ratio, $productMoney, $is_string);

        return $payment_list;
    }
}

/**
 * 采购金额（运费或优惠额） 按 SKU 数量比重分摊到每个 采购单和SKU 上
 * @author Jolon
 * @param float $total_amount           总金额
 * @param array $purchase_sku_qty_list  采购单和SKU
 * @return array|bool
 * @example
 *    参数  $purchase_sku_qty_list
 *                = array(
 *                  'purchase_number1' => array(
 *                       'sku_1' => '数量_1',
 *                       'sku_2' => '数量_2',
 *                   ),
 *                  'purchase_number2' => array(
 *                       'sku_1' => '数量_1',
 *                       'sku_2' => '数量_2',
 *                   )
 *               )
 *  分摊结果 $average_distribute_result =
 *                = array(
 *                  'purchase_number1' => array(
 *                       'sku_1' => '10.423',
 *                       'sku_2' => '23.577',
 *                   ),
 *                  'purchase_number2' => array(
 *                       'sku_1' => '10.423',
 *                       'sku_2' => '23.577',
 *                   )
 *               )
 */
if( !function_exists('amountAverageDistribute')){
    function amountAverageDistribute($total_amount,$purchase_sku_qty_list){
        $CI = &get_instance();
        $CI->load->library('purchase_service');

        $average_distribute_result = $CI->purchase_service->amountAverageDistribute($total_amount,$purchase_sku_qty_list);

        return $average_distribute_result;
    }
}

/**
 * 数字的金额转成中文字符串
 * @author Jolon
 * @param  float $num 金额（只支持3位小数，最大 9999999.999）
 * @return string|bool
 */
if( !function_exists('numberPriceToCname')){
    function numberPriceToCname($num){
        $CI = &get_instance();
        $CI->load->library('purchase_service');

        $price_cname = $CI->purchase_service->numberPriceToCname($num);

        return $price_cname;
    }
}

/**
 * 采购合同 采购主体公司信息
 * @author Jolon
 * @param string $purchase_name  采购主体
 * @param string $field  指定字段
 * @return array|string
 */
function compactCompanyInfo($purchase_name,$field = ''){
    $company_info_list = _getStatusList('PUR_PURCHASE_FINANCE_COMPACT_COMPANY_INFO');
    $company_info_list = isset($company_info_list[$purchase_name])?$company_info_list[$purchase_name]:[];
    $company_info = [];
    if($company_info_list){
        $company_info['name'] = isset($company_info_list['name'])?$company_info_list['name']:'';
        $company_info['address'] = isset($company_info_list['address'])?$company_info_list['address']:'';
    }

    if($field) return isset($company_info[$field])?$company_info[$field]:'';

    return $company_info;

}

/**
 * 合同 取消未到货取消金额从尾款里面扣除（尾款不够的话 会往上一个比例 继续扣减
 * @author Jolon
 * @param array $ratio_money_list  合同请款比例与金额
 * @param float $cancel_total_product_money  总取消金额
 * @return array
 */
function calculateRealPayMoney($ratio_money_list,$cancel_total_product_money){
    $ratio_money_list_tmp = array_reverse($ratio_money_list);// 结算比例 顺序反转
    $cancel_total_product_money_tmp = $cancel_total_product_money;

    if($ratio_money_list_tmp){
        foreach($ratio_money_list_tmp as $ratio => $price){
            if(in_array($ratio,['wk_t','real_money'])) continue;// 为了兼容 compactPaymentPlan | compactPaymentPlanByRatio 两种计算方式的结果
            if($cancel_total_product_money > 0){
                if($cancel_total_product_money >= $price){
                    $ratio_money_list[$ratio] = 0;// 当前比例的应付金额
                }else{
                    $ratio_money_list[$ratio] = format_two_point_price($price - $cancel_total_product_money);// 当前比例的应付金额
                }
                $cancel_total_product_money -= $price;// 剩余扣除金额
            }
        }

        if(isset($ratio_money_list['wk_t'])) $ratio_money_list['wk_t'] = format_two_point_price($ratio_money_list['wk']);
        if(isset($ratio_money_list['real_money'])) $ratio_money_list['real_money'] = format_two_point_price($ratio_money_list['real_money'] - $cancel_total_product_money_tmp);
    }

    return $ratio_money_list;
}

/**
 * 合同模板 相关要求条款数据
 * @author Jolon
 */
function compactRequireInfo(){
    $require_info = _getStatusList('PUR_PURCHASE_FINANCE_COMPACT_REQUIRE_INFO');
    return $require_info;
}

/**
 * 合同 按比例请款 请款金额拆分（最高3段）
 * @author Jolon
 * @param string $ratio        合同的结算比例 ，如：30%+70% 或 30%+30%+40%
 * @param float  $productMoney 合同的总产品额
 * @param float  $freight      合同的总运费
 * @param float  $discount     合同的总优惠额
 * @param int    $is_drawback  是否退税（1.退税,0.不退税）
 * @return bool|array          返回  订金、中款、尾款、尾款总额 信息
 */
if( !function_exists('compactPaymentPlan')){
    function compactPaymentPlan($ratio, $productMoney, $freight, $discount, $is_drawback = 0){
        $CI = &get_instance();
        $CI->load->library('purchase_service');

        $payment_list = $CI->purchase_service->compactPaymentPlan($ratio, $productMoney, $freight, $discount, $is_drawback);

        return $payment_list;
    }
}

/**
 * 是否需要中转
 * @author Jolon
 * @param string $type
 * @return array|mixed
 */
function getIsTransferWarehouse($type = null){
    $types = _getStatusList('PUR_PURCHASE_FINANCE_IS_TRANSFER_WAREHOUSE');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 采购合同 - 中转仓库信息列表
 * @author Jolon
 * @param string $warehouse_code  中转仓库代码
 * @param string $field
 * @param string $buyer_name
 * @param string $buyer_phone
 * @return mixed|string
 */
function getTransitWarehouseInfo($warehouse_code = null,$field = null,$buyer_name = '',$buyer_phone = ''){
    $warehouses = _getStatusList('PUR_PURCHASE_FINANCE_TRANSFER_WAREHOUSE_INFO');
    if(isset($warehouse_code)){
        if(isset($warehouses[$warehouse_code])){
            $warehouses_now = $warehouses[$warehouse_code];
            if($field){
                $warehouseAddress = $warehouses_now['receive_address'];
                $warehouseAddress = str_replace('{buyer}', $buyer_name, $warehouseAddress); 
                $warehouseAddress = str_replace('{buyer_phone}', $buyer_phone, $warehouseAddress);
                mb_internal_encoding("UTF-8");
                $encoding = mb_internal_encoding();
                $warehouseAddress=  mb_rtrim($warehouseAddress, "、",$encoding);
                return $warehouseAddress;
            }
            return $warehouses_now;
        }else{
            return '';
        }
    }else{
        return $warehouses;
    }
}
/**
 * 防止中文去掉特殊符号出现乱码
 * @author harvin
 * @param srting $string
 * @param type $trim
 * @param type $encoding
 * @return type
 */
function mb_rtrim($string, $trim, $encoding)
{
 
    $mask = [];
    $trimLength = mb_strlen($trim, $encoding);
    for ($i = 0; $i < $trimLength; $i++) {
        $item = mb_substr($trim, $i, 1, $encoding);
        $mask[] = $item;
    }
 
    $len = mb_strlen($string, $encoding);
    if ($len > 0) {
        $i = $len - 1;
        do {
            $item = mb_substr($string, $i, 1, $encoding);
            if (in_array($item, $mask)) {
                $len--;
            } else {
                break;
            }
        } while ($i-- != 0);
    }
    return mb_substr($string, 0, $len, $encoding);
}
 

/**
 * 请款单 请款类型
 * @author Jolon
 * @param string $type
 * @return array|mixed|string
 */
function getPayCategory($type = null){
    $types = _getStatusList('PUR_PURCHASE_FINANCE_PAY_CATEGORY');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 是否对账单请款
 * @author Jolon
 * @param string $type
 * @return array|mixed|string
 */
function getIsStatementPay($type = null){
    $types = ['3' => '是','1' => '否'];

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'否';
    }
    return $types;
}


/**
 * 银行卡管理 支付类型
 * @author Jolon
 * @param int $type  (1.银行卡,2.支付宝)
 * @return array|mixed|string
 */
function bankCardPaymentTypes($type = null){
    $types = _getStatusList('PUR_PURCHASE_FINANCE_BANK_CARD_PAYMENT_TYPES');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 银行卡管理 账号标志
 * @author Jolon
 * @param int $type  (1.对公帐号,2.对私帐号)
 * @return array|mixed|string
 */
function bankCardAccountSign($type = null){
    $types = _getStatusList('PUR_PURCHASE_FINANCE_BANK_CARD_ACCOUNT_SIGN');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}


/**
 * 银行卡管理 状态
 * @author Jolon
 * @param int $type  (1.可用,2.禁用)
 * @return array|mixed|string
 */
function bankCardStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_FINANCE_BANK_CARD_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}


/**
 * 对账单付款状态
 * @author Jaden
 * @return array|mixed|string
 */
function purchaseStatementPaymentStatus($type = null){
    $types = _getStatusList('PUR_PURCHASE_STATEMENT_PAYMENT_STATUS');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}


/**
 * 对账单付款状态
 * @author Jaden
 * @return array|mixed|string
 */
function purchaseStatementIsInvalid($type = null){
    $types = _getStatusList('PUR_PURCHASE_STATEMENT_IS_INVALID');
    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 账号类型：下拉选择，分为——淘宝、其他，2个选项
 * @author Jolon
 * @param null $type
 * @return array|mixed|string
 */
function purchaseAccountType($type = null){
    $types = [
        '1' => '淘宝',
        '100' => '其他'
    ];

    if(!is_null($type)){
        return isset($types[$type]) ? $types[$type]:'';
    }
    return $types;
}

/**
 * 判断 请款段位
 * @param float $total_product_money    总商品额
 * @param float $pay_product_money      本次请款商品额
 * @param float $paid_product_money     已付款商品额
 * @return string
 * @author Jolon
 */
function calculatePriceCase($total_product_money,$pay_product_money,$paid_product_money){
    if( abs($pay_product_money - $total_product_money) <= IS_ALLOWABLE_ERROR){// 全款
        return "全款".format_price($pay_product_money);
    }elseif( abs($paid_product_money + $pay_product_money - $total_product_money) <= IS_ALLOWABLE_ERROR){// 尾款
        $percent = ceil($pay_product_money * 100/ $total_product_money);// 尾款比例：向上取整
        return "{$percent}%%尾款".format_price($pay_product_money);
    }else{// 订金
        $percent = ceil($pay_product_money  * 100/ $total_product_money);// 订金比例：向上取整
        return "{$percent}%%订金".format_price($pay_product_money);
    }
}

/**
 * 生成 请款摘要备注信息
 * @param int   $source_subject         请款主体来源
 * @param float $total_product_money    总商品额
 * @param float $pay_product_money      本次请款商品额
 * @param float $paid_product_money     已付款商品额
 * @param float $freight                运费
 * @return string
 * @author Jolon
 */
function abstractRemarkTemplate($source_subject = 1,$total_product_money = 0.0,$pay_product_money = 0.0,$paid_product_money = 0.0,$freight = 0.0){
    if($pay_product_money > 0){
        $price_case = calculatePriceCase($total_product_money,$pay_product_money,$paid_product_money);
        $abstract_remark = "%s已用%s账户支付货款{$price_case}元%s%s";
    }elseif($freight > 0){
        $abstract_remark = "%s已用%s账户支付运费%s元";
    }else{
        $abstract_remark = '';
    }
    return $abstract_remark;
}

/**
 * 转换 请款摘要备注信息 为用户可见信息
 * @param string      $abstract_remark 摘要信息
 * @param string|null $payer_time      付款时间
 * @param string|null $pay_platform    支付平台
 * @param float|null  $procedure_fee   手续费
 * @param float|null  $freight         运费
 * @param float|null  $discount        优惠额
 * @param float|null  $process_cost    加工费
 * @param float|null  $commission      代采佣金
 * @return string
 * @author Jolon
 */
function convertAbstractRemark($abstract_remark,string $payer_time = null,string $pay_platform = null,float $procedure_fee = null,float $freight = null,float $discount = null,float $process_cost = null,float $commission = null){
    if(empty($abstract_remark)) return '';

    // 月份：1-12
    if(is_string($payer_time)){
        $payer_time = strtotime($payer_time);
        $payer_time = date('n月d日',$payer_time);
    }elseif(is_numeric($payer_time)){
        $payer_time = date('n月d日',$payer_time);
    }else{
        return '';
    }

    $pay_platform_type_list = [
        '1688'     => '1688',
        'ufxfuiou' => '富友',
        'lakala'   => '拉卡拉',
        'baofoo'   => '宝付',
    ];
    $pay_platform = isset($pay_platform_type_list[$pay_platform])?$pay_platform_type_list[$pay_platform]:$pay_platform;


    if($procedure_fee > 0){
        $procedure_fee = "（供应商承担{$procedure_fee}元手续费）";
    }else{
        $procedure_fee = '';
    }

    $append_to = [];// 运费、优惠额、加工费、代采佣金都追加上去

    if($freight > 0){
        $append_to[] = "运费{$freight}元";
        $freight = "（另外运费{$freight}元）";
    }
    if($discount > 0){
        $append_to[] = "优惠额{$discount}元";
    }
    if($process_cost > 0){
        $append_to[] = "加工费{$process_cost}元";
    }
    if($commission > 0){
        $append_to[] = "代采佣金{$commission}元";
    }

    if($append_to){
        $append_to_str = "（另外".implode('，',$append_to)."）";
    }else{
        $append_to_str = '';
    }

    if(stripos($abstract_remark,'支付运费') !== false){
        $abstract_remark = sprintf($abstract_remark,$payer_time,$pay_platform,$freight);
    }else{
        $abstract_remark = sprintf($abstract_remark,$payer_time,$pay_platform,$procedure_fee,$append_to_str);
    }

    return $abstract_remark;
}

/**
 * 根据支付平台 获取支付银行卡信息
 * @param string $platform 平台类型
 * @param string $account  平台对应的账号
 * @return int
 * @author Jolon
 */
function getPaymentIdByPlatform($platform,$account = null){
    $payCardId = 0;
    switch ($platform){
        case 'ufxfuiou':
            $payCardId = 99;
            break;
        case 'lakala':
            $payCardId = 98;
            break;
        case 'baofopay':
            $payCardId = 107;
            break;
        case 'cebbank':
            $payCardId = 108;
            break;
        case 'taobao':
            if($account == '琦LL114'){
                $payCardId = 12;
            }elseif($account == '琦LL115'){
                $payCardId = 13;
            }elseif($account == '琦LL213'){
                $payCardId = 109;
            }elseif($account == '琦LL214'){
                $payCardId = 110;
            }
            break;
        case '1688':
            if ($account == '支付宝') {
                $payCardId = 15;
            } elseif ($account == '跨境宝') {
                $payCardId = 111;
            }
            break;
    }

    return $payCardId;
}


/**
 * 对账单状态
 * @author Jolon
 * @param string $status  查找目标状态
 * @return array|string
 */
function getPurchaseStatementStatus($status = null){
    $status_list = _getStatusList('PURCHASE_STATEMENT_STATUS');
    if(!is_null($status)){
        return isset($status_list[$status]) ? $status_list[$status]:'';
    }
    return $status_list;
}