<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliBaseApi.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliAccount.php';
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliProductApi.php';

/**
 * Class AliOrderApi
 * 阿里巴巴 订单相关信息
 * @author:Jolon
 * @date 2019-03-18
 */
class AliOrderApi extends AliBaseApi {
    protected $_flow = 'general';// 固定(general.创建大市场订单 saleproxy.创建分销订单)

    public $tradeTypeList = [ // 交易方式类型说明(没有一种交易方式是全局通用的，所以当前下单可使用的交易方式必须通过下单预览接口的tradeModeNameList获取)
        'fxassure'  => '担保交易',// 1688默认交易
        'alipay'    => '大市场通用的支付宝担保交易',
        'period'    => '账期交易',
        'assure'    => '大买家企业采购询报价下单时需要使用的担保交易流程',
        'creditBuy' => '诚E赊',
        'bank'      => '银行转账',
        '631staged' => '631分阶段付款',
        '37staged'  => '37分阶段'
    ];

    public $orderRefundStatus = [// 1688订单退款状态
        'waitselleragree'   => '等待卖家同意',
        'refundsuccess'     => '退款成功',
        'refundclose'       => '退款关闭',
        'waitbuyermodify'   => '待买家修改',
        'waitbuyersend'     => '等待买家退货',
        'waitsellerreceive' => '等待卖家确认收货'
    ];

    /**
     * 获取 1688下单流程
     * @return string
     */
    public function getFlow(){
        return $this->_flow;
    }

    /**
     * 设置 1688下单流程
     * @param $_flow
     */
    public function setFlow($_flow){
        $this->_flow = $_flow;
    }

    /**
     * 获取订单最新总额
     * @param string $orderId
     * @return mixed|string
     */
    public function getOrderPrice($orderId){
        if(empty($orderId)){
            $this->_errorMsg = 'Order Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        $post_data['orderId'] = $orderId;

        $quota_url = getConfigItemByName('api_config','alibaba','purchasingSolution-getOrderPrice');
        $result = $this->curlPost($quota_url,$post_data);
        $result = json_decode($result,true);
        if(isset($result['code']) and $result['code'] == 200){
            $this->_errorMsg = null;
            return $this->returnData($result['data']);
        }else{
            $this->_errorMsg = $result['msg'];
            return $this->returnData();
        }
    }

    /**
     * 通过交易ID获取订单详情
     * @param string $orderId
     * @return mixed|string
     */
    public function getOrderDetail($orderId){
        if(empty($orderId)){
            $this->_errorMsg = 'Order Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        $post_data['orderId'] = $orderId;

        $quota_url = getConfigItemByName('api_config','alibaba','get_order_detail');
        $result = $this->curlPost($quota_url,$post_data);

        return $result;
    }


    /**
     * 获取使用跨境宝支付的支付链接
     * @link https://open.1688.com/api/apidocdetail.htm?id=com.alibaba.trade:alibaba.crossBorderPay.url.get-1
     * @param string   $account
     * @param string $orderId
     * @return mixed|string
     */
    public function getOrderPayUrl($account = null,$orderId){
        if(empty($orderId)){
            $this->_errorMsg = 'Order Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams($account);
        if(is_array($orderId)){
            $orderId = array_values(array_unique($orderId));
            $post_data['orderId'] = $orderId;
        }else{
            $post_data['orderId'] = [$orderId];
        }
        $quota_url = getConfigItemByName('api_config','alibaba','crossBorder-getPayUrl');
        $result = $this->curlPost($quota_url,$post_data);
        return $result;
    }


    /**
     * 获取交易订单的物流信息
     * @link https://open.1688.com/api/apidocdetail.htm?aopApiCategory=Logistics_NEW&id=com.alibaba.logistics%3Aalibaba.trade.getLogisticsInfos.sellerView-1
     * @param string $orderId
     * @return mixed|string
     */
    public function getLogisticsInfo($orderId){
        if(empty($orderId)){
            $this->_errorMsg = 'Order Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        $post_data['orderId'] = $orderId;

        $quota_url = getConfigItemByName('api_config','alibaba','purchasingSolution-getLogisticsInfo');
        $result    = $this->curlPost($quota_url,$post_data);
        $result    = json_decode($result,true);
        return $result;
    }

    /**
     * 批量获取交易订单的物流信息
     * @param string $orderId
     * @return mixed|string
     */
    public function listLogisticsInfo($orderId){
        if(empty($orderId)){
            $this->_errorMsg = 'Order Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        if(is_array($orderId)){
            $post_data['orderId'] = $orderId;
        }else{
            $post_data['orderId'] = [$orderId];
        }
        $quota_url = getConfigItemByName('api_config','alibaba','purchasingSolution-listLogisticsInfo');
        $result    = $this->curlPost($quota_url,$post_data);
        $result    = json_decode($result,true);
        return $result;
    }

    /**
     * 获取交易订单的订单详细信息
     * @param string   $account
     * @param string $orderId
     * @return mixed|string
     */
    public function getListOrderDetail($account = null,$orderId){
        if(empty($orderId)){
            $this->_errorMsg = 'Order Id不能为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams($account);

        if(is_array($orderId)){
            $post_data['orderId'] = $orderId;
        }else{
            $post_data['orderId'] = [$orderId];
        }
        $quota_url = getConfigItemByName('api_config','alibaba','purchasingSolution-listOrderDetail');
        $result    = $this->curlPost($quota_url,$post_data);
        $result    = json_decode($result,true);
        $result['requestUrl'] = $quota_url;
        return $result;
    }

    /**
     * @desc 创建订单前预览数据接口
     * @author Jeff
     * @Date 2019/03/20 14:22
     * @link https://open.1688.com/api/apidocdetail.htm?aopApiCategory=trade_new&id=com.alibaba.trade%3Aalibaba.createOrder.preview-1
     * @param $sub_ali_account
     * @param $orderData
     * @return mixed|string
     * @return
     */
    public function createOrderPreview($sub_ali_account,$orderData){
        if(empty($orderData) or empty($orderData['cargoParamList'])){
            $this->_errorMsg = 'Order data不能为空';
            return $this->returnData();
        }

        // START:验证商品是否存在（获取商品详情成功与否来验证）
        $offerIdList = array_column($orderData['cargoParamList'],'offerId');
        $checkProductOnline = (new AliProductApi())->checkProductWhetherOnline($offerIdList);
        if(empty($checkProductOnline['code'])){
            $this->_errorMsg = $checkProductOnline['errorMsg'];
            return $this->returnData();
        }
        if(count($checkProductOnline['data']['online_product']) != count($checkProductOnline['data']['check_results'])){
            $offLine = array_diff_key($checkProductOnline['data']['check_results'],$checkProductOnline['data']['online_product']);// 获取下架的商品ID
            $errorMsg = array_keys($offLine);
            $errorMsg = implode(" ",$errorMsg);
            $errorMsg = $errorMsg.' '.end($offLine);
            $errorMsg = str_replace("商品已经下架","offer no exist",$errorMsg);// 转换成 统一解析格式 Ali_order_model::convert_message_content
            $this->_errorMsg = $errorMsg;
            return $this->returnData();
        }
        // END:验证商品是否存在

        $aliAccount        = new AliAccount();
        $user_ali_account  = $aliAccount->getSubAccountOneByUserId(null,$sub_ali_account);

        $post_data         = $this->getFixedParams($user_ali_account['p_account']);
        $post_data         = $post_data + $orderData;

        $account_access_token = $aliAccount->getSubAccountToken($user_ali_account['p_account'],$user_ali_account['account']);
        if(empty($account_access_token['code'])){
            $this->_errorMsg = $account_access_token['errorMsg'];
            return $this->returnData();
        }

        $post_data['accessToken'] = $account_access_token['access_token'];// 用子账号的 token 下单 就可以在子账号里面创建订单了

        $quota_url = getConfigItemByName('api_config','alibaba','order-createOrderPreview');
        $result    = $this->curlPost($quota_url,$post_data);
        $result    = json_decode($result,true);
        return $result;
    }


    /**
     * @desc 创建订单接口
     * @author Jeff
     * @Date 2019/03/20 14:22
     * @link https://open.1688.com/api/apidocdetail.htm?id=com.alibaba.trade:alibaba.trade.createCrossOrder-1
     * @param $sub_ali_account
     * @param $orderData
     * @return mixed|string
     * @return
     */
    public function createCrossOrder($sub_ali_account,$orderData){
        if(empty($orderData) or empty($orderData['cargoParams'])){
            $this->_errorMsg = 'Order data不能为空';
            return $this->returnData();
        }

        // START:验证商品是否存在（获取商品详情成功与否来验证）
        $offerIdList = array_column($orderData['cargoParams'],'offerId');
        $checkProductOnline = (new AliProductApi())->checkProductWhetherOnline($offerIdList);
        if(empty($checkProductOnline['code'])){
            $this->_errorMsg = $checkProductOnline['errorMsg'];
            return $this->returnData();
        }
        if(count($checkProductOnline['data']['online_product']) != count($checkProductOnline['data']['check_results'])){
            $offLine = array_diff_key($checkProductOnline['data']['check_results'],$checkProductOnline['data']['online_product']);// 获取下架的商品ID
            $this->_errorMsg = implode("\r\n",$offLine);
            return $this->returnData();
        }
        // END:验证商品是否存在

        $aliAccount        = new AliAccount();
        $user_ali_account  = $aliAccount->getSubAccountOneByUserId(null,$sub_ali_account);

        $post_data         = $this->getFixedParams($user_ali_account['p_account']);
        $post_data['flow'] = $this->_flow;
        $post_data         = $post_data + $orderData;

        $account_access_token = $aliAccount->getSubAccountToken($user_ali_account['p_account'],$user_ali_account['account']);
        if(empty($account_access_token['code'])){
            $this->_errorMsg = $account_access_token['errorMsg'];
            return $this->returnData();
        }

        $post_data['accessToken'] = $account_access_token['access_token'];// 用子账号的 token 下单 就可以在子账号里面创建订单了

        $quota_url = getConfigItemByName('api_config','alibaba','order-createCrossOrder');
        $result    = $this->curlPost($quota_url,$post_data);
        $result     = json_decode($result,true);

        if(isset($result['error'])){
            $this->_errorMsg = $result['error'];
            return $this->returnData();
        }
        if(isset($result['code']) and $result['code'] == 200){
            $this->_errorMsg = null;
            return $this->returnData($result['data']['result']);
        }else{
            $this->_errorMsg = (isset($result['msg'])?$result['msg']:'').(isset($result['data']['message'])?$result['data']['message']:'');
            return $this->returnData();
        }
    }

    /**
     * 获取 收货地址信息
     * @param string $sub_ali_account 子主账号
     * @return array
     */
    public function getTradeByReceiveAddress($sub_ali_account){
        $aliAccount         = new AliAccount();
        $user_ali_account   = $aliAccount->getSubAccountOneByUserId(null,$sub_ali_account);

        $post_data         = $this->getFixedParams($user_ali_account['p_account']);

        $account_access_token = $aliAccount->getSubAccountToken($user_ali_account['p_account'],$user_ali_account['account']);
        if(empty($account_access_token['code'])){
            $this->_errorMsg = $account_access_token['errorMsg'];
            return $this->returnData();
        }
        $post_data['accessToken'] = $account_access_token['access_token'];// 用子账号的 token 下单 就可以在子账号里面创建订单了
        
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'tracde-getTradeByReceiveAddress');
        $result    = $this->curlPost($quota_url, $post_data);
        $result    = json_decode($result, true);

        if (empty($result)){
            $this->_errorMsg = '未知错误[getTradeByReceiveAddress]';
            return $this->returnData();
        }

        if(isset($result['error'])){
            $this->_errorMsg = $result['error'];
            return $this->returnData();
        }

        if (isset($result['code']) && $result['code'] != 200 ){
            $this->_errorMsg = $result['msg'];
            return $this->returnData();
        }

        if (isset($result['status']) && $result['status'] != 200){
            $this->_errorMsg = $result['error'].':'.$result['message'];
            return $this->returnData();
        }

        if(!isset($result['data']) or empty($result['data'])){
            $this->_errorMsg = '获取收货地址失败[地址为空]';
            return $this->returnData();
        }

        $this->_errorMsg = null;
        return $this->returnData($result['data']);
    }

    /**
     * 获取交易订单的物流跟踪信息
     * @param string $orderId  订单号
     * @return array
     */
    public function getLogisticsTraceInfo($orderId){
        $post_data = $this->getFixedParams();
      //  $post_data['logisticsId'] = 'LP00127152419790';
        $post_data['orderId'] = trim($orderId);
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'purchasingSolution-getLogisticsTraceInfo');
        $result    = $this->curlPost($quota_url, $post_data);
        $result    = json_decode($result,true,512,JSON_BIGINT_AS_STRING );
    
        if (empty($result)){
            $this->_errorMsg = '未知错误';
            return $this->returnData();
        }

        if (isset($result['code']) && $result['code'] != 200 ){
            $this->_errorMsg = $result['msg'];
            return $this->returnData();
        }

        if(isset($result['error'])){
            $this->_errorMsg = $result['error'];
            return $this->returnData();
        }

        if (!isset($result['data']['success']) or $result['data']['success'] != 1){
            $this->_errorMsg = $result['data']['errorCode'].':'.$result['data']['errorMessage'];
            return $this->returnData();
        }

        if(! isset($result['data']['logisticsTrace'][0]) or empty($result['data']['logisticsTrace'][0])){
            $this->_errorMsg = '物流信息为空';
            return $this->returnData();
        }

        $data            = $result['data']['logisticsTrace'][0];
        $logisticsId     = isset($data['logisticsId']) ? $data['logisticsId'] : '';
        $orderId         = isset($data['orderId']) ? strval($data['orderId']) : '';
        $logisticsBillNo = isset($data['logisticsBillNo']) ? trim($data['logisticsBillNo']) : '';
        $logisticsSteps  = isset($data['logisticsSteps']) ? $data['logisticsSteps'] : [];
//            echo "<pre>";
//        print_r($logisticsSteps);
//        die;
//        
        foreach ($logisticsSteps as $key => $value) {
            $data_tep[date('Y-m-d', strtotime($value['acceptTime']))][]=[
                'acceptTime'=>$value['acceptTime'],
                'remark'=>$value['remark'],
                'logisticsId'=>$logisticsId,
                'orderId'=>$orderId,
                'logisticsBillNo'=>$logisticsBillNo,
            ];
        }
       return $this->returnData($data_tep);
      
//        foreach($logisticsSteps as &$value){
//            $value['logisticsId']     = $logisticsId;
//            $value['orderId']         = $orderId;
//            $value['logisticsBillNo'] = $logisticsBillNo;
//        }
//
//        return $this->returnData($logisticsSteps);
    }

    /**
     * 取消1688订单
     * @param $orderId
     * @return array
     */
    public function getTradeCancel($orderId,$reason){
        if(empty($orderId)){
            $this->_errorMsg = '拍单号不允许为空为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        $post_data['orderId'] = trim($orderId);
        $post_data['reason'] = $reason;
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'get_trade_cancel');
        $result    = $this->curlPost($quota_url, $post_data);
        $result = json_decode($result,true);
        if(isset($result['code']) and $result['code'] == 200){
            $this->_errorMsg = null;
            return $this->returnData($result['data']);
        }else{
            $this->_errorMsg = $result['msg'];
            return $this->returnData();
        }
    }

    /**
     * 退款单-退款成功详情信息（退款成功的记录-JAVA已经处理过的数据）
     * @link https://open.1688.com/api/apidocdetail.htm?id=com.alibaba.trade:alibaba.trade.refund.buyer.queryOrderRefundList-1&aopApiCategory=trade_new
     * @param $orderId
     * @return array
     */
    public function getQueryOrderRefund($orderId, $not_handle = false){
        if(empty($orderId)){
            $this->_errorMsg = '拍单号不允许为空为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        $post_data['orderId'] = trim($orderId);
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'get_query_order_refund');
        $result    = $this->curlPost($quota_url, $post_data);
        $result = json_decode($result,true);
        if($not_handle)return $result;
        if(isset($result['code']) and $result['code'] == 200 and is_array($result['data']) and !empty($result['data'])){
            $this->_errorMsg = null;

            $result_data   = $result['data'];
            $applyCarriage = $applyPayment = 0;
            $createTime    = $completedTime = '0000-00-00 00:00:00';// 最近一次退款的开始与结束时间
            foreach($result_data as $value){
                $applyCarriage += $value['applyCarriage'];
                $applyPayment  += $value['applyPayment'];
                if(strtotime($value['createTime']) > strtotime($createTime)) $createTime = $value['createTime'];
                if(strtotime($value['completedTime']) > strtotime($completedTime)) $completedTime = $value['completedTime'];
            }

            // 组合退款信息数据
            $refund_data                     = [];
            $refund_data['applyCarriage']    = $applyCarriage;
            $refund_data['applyPayment']     = $applyPayment;
            $refund_data['applyTotalAmount'] = $applyCarriage + $applyPayment;
            $refund_data['createTime']       = $createTime;
            $refund_data['completedTime']    = $completedTime;
            $refund_data['applyReason']      = implode(";", array_column($result_data, 'applyReason'));// 所有退款记录的原因
            $refund_data['applyStatus']      = 'refunded';// JAVA接口已调整：只返回 退款成功的记录
            $refund_data['applyStatusCn']    = '已退款';
            $refund_data['originalData']     = $result_data;// 1688返回的原始数据

            // 计算退款状态
            /*$createTime     = $result['data']['createTime'];
            $completedTime  = $result['data']['completedTime'];
            if(empty($completedTime) or strtotime($completedTime) < strtotime($createTime)){
                $result['data']['applyStatus'] = 'waiting refund';
                $result['data']['applyStatusCn'] = '待退款';
            }else{
                $result['data']['applyStatus'] = 'refunded';
                $result['data']['applyStatusCn'] = '已退款';
            }*/

            return $this->returnData($refund_data);
        }else{
            $this->_errorMsg = $result['msg'];
            return $this->returnData();
        }
    }

    /**
     * 退款单-退款成功详情信息（所有记录）
     * @link http://192.168.71.145:8080/xwiki/bin/view/java接口/service-alibaba-order/根据订单号获取所有退款详情/
     * @link https://open.1688.com/api/apidocdetail.htm?id=com.alibaba.trade:alibaba.trade.refund.buyer.queryOrderRefundList-1&aopApiCategory=trade_new
     * @param $orderId
     * @return array
     */
    public function getListOrderRefund($orderId, $is_res=false){
        if(empty($orderId)){
            $this->_errorMsg = '拍单号不允许为空为空';
            return $this->returnData();
        }
        $post_data = $this->getFixedParams();
        $post_data['orderId'] = trim($orderId);
        $post_data['applyStartTime'] = date('YmdHis',strtotime(' -180 days'))."000+0800";
        $post_data['applyEndTime'] = date('YmdHis')."000+0800";
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'list_order_refund');
        $result    = $this->curlPost($quota_url, $post_data);
        $result    = json_decode($result,true);
        if($is_res)return $result;

        if(isset($result['code']) and $result['code'] == '200'){
            $refund_data = [];
            if($result['data'] and is_array($result['data'])){
                foreach($result['data'] as $d_value){
                    $data_tmp = [
                        'applyCarriage'  => isset($d_value['applyCarriage']) ? $d_value['applyCarriage'] : 0,// 申请退款运费
                        'applyPayment'   => isset($d_value['applyPayment']) ? $d_value['applyPayment'] : 0,// 申请退款金额
                        'refundCarriage' => isset($d_value['refundCarriage']) ? $d_value['refundCarriage'] : 0,// 实际退款运费
                        'refundPayment'  => isset($d_value['refundPayment']) ? $d_value['refundPayment'] : 0,// 实际退款金额
                        'applyReason'    => isset($d_value['applyReason']) ? $d_value['applyReason'] : '',// 申请退款原因
                        'createTime'     => isset($d_value['gmtCreate']) ? date('Y-m-d H:i:s',strtotime($d_value['gmtCreate'])) : '',// 退款创建时间
                        'completedTime'  => isset($d_value['gmtCompleted']) ? date('Y-m-d H:i:s',strtotime($d_value['gmtCompleted'])) : '',// 退款完成时间
                        'applyStatus'    => isset($d_value['status']) ? $d_value['status'] : '',// 退款处理状态
                    ];

                    $data_tmp['applyStatusCn'] = isset($this->orderRefundStatus[$data_tmp['applyStatus']])?$this->orderRefundStatus[$data_tmp['applyStatus']]:'';// 退款处理状态中文

                    // 单位是分，转成元
                    $data_tmp['applyCarriage']    = format_two_point_price($data_tmp['applyCarriage']/100);
                    $data_tmp['applyPayment']     = format_two_point_price($data_tmp['applyPayment']/100);
                    $data_tmp['refundCarriage']   = format_two_point_price($data_tmp['refundCarriage']/100);
                    $data_tmp['refundPayment']    = format_two_point_price($data_tmp['refundPayment']/100);
                    $data_tmp['applyTotalAmount'] = format_two_point_price($data_tmp['applyCarriage'] + $data_tmp['applyPayment']);// 申请退款总金额

                    $refund_data[] = $data_tmp;
                }

                return $this->returnData($refund_data);

            }else{
                return $this->returnData();
            }
        }else{
            $this->_errorMsg = isset($result['msg'])?$result['msg']:'JAVA返回数据异常';
            return $this->returnData();
        }
    }

    /**
     * 解析错误结果 转换成中文
     * @param $_errorMsg
     * @return bool|mixed|string
     */
    public function analysisResultToSimpleMessage($_errorMsg){
        if(is_string($_errorMsg)){
            // 账期余额不足 转换
            if(strpos($_errorMsg, 'availableQuota less than sumPayment') !== false){
                $_errorMsg = str_replace('availableQuota less than sumPayment','账期余额不足',$_errorMsg);
            }
        }

        return $_errorMsg;

    }

    /**
     * 获取 1688 订单金额、退款金额等
     * @param $pai_number
     * @return array
     */
    public function getAliOrderAllPrice($pai_number){
        $ali_price_list = [
            'status'            => null,// 1688订单状态
            'sumProductPayment' => 0,// 商品总金额
            'shippingFee'       => 0,// 运费
            'discount'          => 0,// 优惠额
            'totalAmount'       => 0,// 订单总金额（退款前的金额）
            'applyCarriage'     => 0,// 退款运费
            'applyPayment'      => 0,// 退款商品额
            'applyTotalAmount'  => 0,// 退款总金额
        ];
        $aliOrderInfoList = $this->getListOrderDetail(null,array_values([$pai_number]));

        if(isset($aliOrderInfoList[$pai_number]) and !empty($aliOrderInfoList[$pai_number]['data'])){
            // 更新 1688 订单金额信息
            $aliOrderData                        = $aliOrderInfoList[$pai_number]['data'];
            $baseInfo                            = $aliOrderData['baseInfo'];
            $ali_price_list['status']            = $baseInfo['status'];
            $ali_price_list['sumProductPayment'] = $baseInfo['sumProductPayment'];
            $ali_price_list['totalAmount']       = $baseInfo['totalAmount'];
            $ali_price_list['shippingFee']       = $baseInfo['shippingFee'];
            $ali_price_list['discount']          = $baseInfo['discount'];
        }

        // 退款金额
        $aliRefundPrice = $this->getQueryOrderRefund($pai_number);
        if(isset($aliRefundPrice['code']) and $aliRefundPrice['code']){
            $ali_price_list['applyCarriage']    = $aliRefundPrice['data']['applyCarriage'];
            $ali_price_list['applyCarriage']    = $aliRefundPrice['data']['applyCarriage'];
            $ali_price_list['applyTotalAmount'] = $aliRefundPrice['data']['applyTotalAmount'];
        }

        return $ali_price_list;
    }

    /**
     * 批量获取交易订单的物流跟踪信息(用于匹配轨迹状态)
     * @param array $orderId  订单号
     * @return array
     */
    public function listLogisticsTraceInfo($orderId = array())
    {
        $post_data = $this->getFixedParams();
        $orderId = array_filter($orderId);
        $orderId = array_values($orderId);

        $post_data['orderId'] = $orderId;
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'purchasingSolution-listLogisticsTraceInfo');

        $result = $this->curlPost($quota_url, $post_data);
        $result = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);

        if (empty($result)) {
            $this->_errorMsg = '未知错误';
            return $this->returnData();
        }

        if (isset($result['code']) && $result['code'] != 200) {
            $this->_errorMsg = $result['msg'];
            return $this->returnData();
        }

        if (isset($result['error'])) {
            $this->_errorMsg = $result['error'];
            return $this->returnData();
        }

        if (!isset($result['data']) OR !is_array($result['data']) OR empty($result['data'])) {
            $this->_errorMsg = '接口数据格式错误';
            return $this->returnData();
        }

        $return_data = array();
        foreach ($result['data'] as $key => $item) {
            if (is_array($item) && isset($item['success']) && $item['success'] == 1 && isset($item['logisticsTrace'][0]) && !empty($item['logisticsTrace'][0])) {
                $data = $item['logisticsTrace'][0];
                $express_no = trim($data['logisticsBillNo']);
                if (empty($express_no)) continue;
                $return_data[$express_no]['express_no'] = $express_no;
                $return_data[$express_no]['pai_number'] = $key;
                $logisticsSteps = isset($data['logisticsSteps']) ? $data['logisticsSteps'] : array();

                if (empty($logisticsSteps)) continue;

                //获取排序标识,最新记录在前面用于提高匹配效率
                $first = isset($logisticsSteps[0]['acceptTime']) ? $logisticsSteps[0]['acceptTime'] : '';
                $last = isset($logisticsSteps[count($logisticsSteps) - 1]['acceptTime']) ? $logisticsSteps[count($logisticsSteps) - 1]['acceptTime'] : '';
                $need_to_reverse = 0;
                if (!empty($first) && !empty($last)) {
                    //如果原始记录是时间较早的排在前面，则需要反转顺序
                    if ((int)strtotime($first) < (int)strtotime($last)) {
                        $need_to_reverse = 1;
                    }
                }
                //组织轨迹详情
                foreach ($logisticsSteps as $value) {
                    $return_data[$express_no]['remark'][] = isset($value['remark']) ? $value['remark'] : '';
                }
                //反转顺序，最新记录在前面！！！
                if ($need_to_reverse) {
                    $return_data[$express_no]['remark'] = array_reverse($return_data[$express_no]['remark']);
                }
            }
        }
        return $this->returnData($return_data);
    }

    /**
     * 获取1688退款原因
     * @param array $orderId  订单号
     * @return array
     */
    public function getOrderRefundReason($orderId=null, $subOrderId=[], $status = false)
    {
        $res = ['code' => 0, 'msg' => ''];
        $post_data = $this->getFixedParams();

        $post_data['orderId'] = $orderId;
        $post_data['orderEntryIds'] = $subOrderId;
        $post_data['goodsStatus'] = $status;
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'getOrderReasonReturn');

        $result = $this->curlPost($quota_url, $post_data);
        $result = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
        $result['url'] = json_encode($post_data);
        if(!$result || !isset($result['code']))$res['msg'] = '获取数据失败';
        if($result['code'] == 200 && isset($result['data'])){
            $res['msg'] = $result['data'];
            $res['code'] = 1;
        }else if(isset($result['msg'])){
            $res['msg'] = $result['msg'];
        }
        return $res;
    }

    /**
     * 上传退款图片接口
     */
    public function uploadRefundImages($img)
    {
        $post_data = $this->getFixedParams();
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'getUploadAttachment');

        $post_data['imageData'] = $img;
        $res = '';
        try{
            $result = $this->curlPost($quota_url, $post_data);
            $result = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
            if (isset($result['code']) && $result['code'] == 200 && isset($result['data']['imageDomain']) && isset($result['data']['imageRelativeUrl'])) {
                return $result['data']['imageDomain'].'/'.$result['data']['imageRelativeUrl'];
            }
            $res = $result;
        }catch (Exception $e){}
        return $res;

    }

    /**
     * 退款提交接口
     */
    public function sendOrderRefund($param)
    {
        $post_data = $this->getFixedParams();
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'sendOrderRefund');

        $post_data = array_merge($post_data, $param);
        $res = [];
        try{
            $result = $this->curlPost($quota_url, $post_data);
            $result = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
            return $result;
        }catch (Exception $e){
            $res[] = $post_data;
        }
        return $res;
    }

    /**
     * 获取1688收银台地址
     */
    public function getAliCashier($params)
    {
        $res = ["code" => 0, "msg" => false, "request" => false, "response"=>false];
        $post_data = $this->getFixedParams();
        $quota_url = getConfigItemByName('api_config', 'alibaba', 'getAliCashier');
        $post_data = array_merge($post_data, $params);
        $res['request'] = $post_data;
        try{
            $result = $this->curlPost($quota_url, $post_data);
            $result = json_decode($result, true, 512, JSON_BIGINT_AS_STRING);
            $res['response'] = $result;
            if(isset($result['code']) && $result['code'] == 200 && !empty($result['data'])){
                $res['msg'] = $result['data'];
                $res['code'] = 1;
            }
        }catch (Exception $e){
            $res['msg'] = $e->getMessage();
        }
        return $res;
    }


}