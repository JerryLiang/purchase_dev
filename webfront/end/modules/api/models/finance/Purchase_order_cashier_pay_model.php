<?php

/**
 * Created by PhpStorm.
 * User: Jackson
 * Date: 2019/1/31
 * Time: 10:51
 */
class Purchase_order_cashier_pay_model extends Api_base_model
{

    protected $_tableHeader = array(
        '编号ID', '订单号', '申请单号', '供应商名称', '账号', '拍单号', '支付状态', '应付金额', '本地金额',
        '优惠金额', '运费'
    );

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * @desc 1688在线支付
     * @author jackson
     * @parames array $params 请求参数
     * @parames array $method 请求方法
     * @Date 2019-02-16 15:26:00
     * @return array()
     */
    public function online_payment($params = array(), $method = 'POST')
    {

        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_listUrl, $params, $method);

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        if ($method == 'POST') {
            //返回付款地址
            $data = $result['data'];
            return array(
                'data_list' => array(
                    'redirect_url' => $data['payUrl']
                ),
            );
        }

        //GET时返回数据
        $data = $result['data'];
        $records = $data['list'];

        //解析前端需要字段信息
        $responseData = array();
        $this->data_analysis($responseData, $records, $data);
        $totalMoney = $responseData['totalMoney'];
        unset($responseData['totalMoney']);
        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $responseData,
                'total_money' => $totalMoney,
                'applicant' => $data['applicant'],
                'applicant_name' => $data['applicantName'],
                'pay_url' => $data['pay_url'],
                'drop_down_box' => null,
            ),
        );
    }
    public function online_paymeny_update($params = array(), $method = "POST"){
         // 1.调用接口
        $result = $this->_curlRequestInterface($this->_updateUrl, $params, $method);
        
       return $result;
        
    }

    /**
     * 重新获取1688订单状态
     * @author Jolon
     * @param array $params
     * @return array
     */
    public function refresh_ali_order_status($params = array()){
        // 1.调用接口
        $flag_update_to_paid = 'enable';
        $result = $this->_curlRequestInterface($this->_refreshAliOrderStatusUrl, $params, 'GET');
        if(isset($result['status']) and $result['status'] == 1){
            $data_list = [];
            foreach ($result['data_list'] as $order_item){
                $order_item['pay_status'] = alibaba_pay_status($order_item['status']);//阿里巴巴开放平台订单支付状态

                // 是否标记异常
                $order_item['flag_is_abnormal'] = '200';
                if($order_item['settlement_method'] == '1688账期' and $order_item['pay_status'] != '交易成功'){
                    $order_item['flag_is_abnormal'] = '500';
                }
                if($order_item['settlement_method'] == '款到发货' and $order_item['pay_status'] != '等待卖家发货'){
                    $order_item['flag_is_abnormal'] = '500';
                }

                if($order_item['flag_is_abnormal'] == '500') $flag_update_to_paid = 'disable';

                $data_list[$order_item['id']] = $order_item;
            }

            $result['data_list'] = [
                'values' => $data_list,
                'flag_update_to_paid' => $flag_update_to_paid
            ];

            return $result;
        }else{
            return $result;
        }
    }


    public function web_online_cross_border_payment($params = array(), $method = 'POST')
    {

        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_crossBorderUrl, $params, $method);

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        if ($method == 'POST') {
            //返回付款地址
            $data = $result['data'];
            return array(
                'data_list' => array(
                    'redirect_url' => $data['payUrl']
                ),
            );
        }

        //GET时返回数据
        $data = $result['data'];
        $records = $data['list'];

        //解析前端需要字段信息
        $responseData = array();
        $this->data_analysis($responseData, $records, $data);
        $totalMoney = $responseData['totalMoney'];
        unset($responseData['totalMoney']);
        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $responseData,
                'total_money' => $totalMoney,
                'applicant' => $data['applicant'],
                'applicant_name' => $data['applicantName'],
                'pay_url' => $data['pay_url'],
                'drop_down_box' => null,
            ),
        );
    }




    /**
     * @desc 1688在线支付批量支付(超级买家)
     * @author jackson
     * @parames array $params 请求参数
     * @Date 2019-02-16 15:26:00
     * @return array()
     */
    public function super_online_payment($params = array(), $method = "POST")
    {
        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_superUrl, $params, $method);

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        if ($method == 'POST') {
            //返回付款地址
            $data = $result['data'];
            return array(
                'data_list' => array(
                    'redirect_url' => $data['payUrl']
                ),
            );
        }

        //GET时返回数据
        $data = $result['data'];
        $records = $data['list'];

        //解析前端需要字段信息
        $responseData = array();
        $this->data_analysis($responseData, $records, $data);
        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $responseData,
                'applicant' => $data['applicant'],
                'applicant_name' => $data['applicantName'],
                'drop_down_box' => null,
            ),
        );
    }

    /**
     * @desc 1688在线付款后的确认付款操作
     * @author jackson
     * @parames array $params 请求参数
     * @Date 2019-02-16 15:26:00
     * @return array()
     */
    public function affirm_payment($params = array())
    {
        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_affirmPaymentUrl, $params);

        //2.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        return array($result['status'], $result['message']);

    }

    /**
     * @desc 驳回请求款单
     * @author jackson
     * @parames array $params 请求参数
     * @Date 2019-02-16 15:26:00
     * @return array()
     */
    public function cashier_reject($params = array())
    {
        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_cashierRejectUrl, $params);

        //2.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        return array($result['status'], $result['message']);

    }

    /**
     * @desc 富友在线支付
     * @author jackson
     * @parames array $params 请求参数
     * @parames array $method 请求方法
     * @Date 2019-02-20 15:26:00
     * @return array()
     */
    public function ufxfuiou_pay($params = array(), $method = 'POST')
    {

        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_ufxfuiouPayUrl, $params, $method);

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        //GET时返回数据
        $records = $result['data'];

        return array(
            'data_list' => array(
                'key' => [],//$this->_tableHeader,
                'value' => $records,
                'drop_down_box' => null,
            ),
        );
    }

    /**
     * @desc 富友在线支付(手动获取支付状态信息)
     * @author jackson
     * @parames array $params 请求参数
     * @parames array $method 请求方法
     * @Date 2019-02-20 15:26:00
     * @return array()
     */
    public function get_fuiou_pay_info($params = array(), $method = 'POST')
    {

        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_fuiouPayInfoUrl, $params, $method);

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        $records = $result['data'];
        $_messages = isset($records['data']['responseBody']['rspDesc']) ? $records['data']['responseBody']['rspDesc'] : '';

        return array(
            'data_list' => array(
                'key' => [],//$this->_tableHeader,
                'value' => $records,
                'message_notice' => $_messages,
                'drop_down_box' => null,
            ),
        );
    }

    /**
     * @desc 返回前端数据分析
     * @author jackson
     * @parames array $responseData 分析后的返回数据
     * @parames array $recorde 接口返回数据
     * @parames int $applicant 申请人
     * @Date 2019-02-16 15:26:00
     * @return array()
     */
    public function data_analysis(&$responseData = array(), $recorde = array(), $data = array())
    {
        if (!empty($recorde)) {
            $totalMoney = 0;
            $localData = array();
            $alibabaData = array();
            $noticeData = array();
            foreach ($recorde as $key => $item) {
                $discount = $item['order_discount'] ? $item['order_discount'] : 0;//优惠
                $shippfree = $item['order_freight'] ? $item['order_freight'] : 0;//运费
                /**@本地订单数据**/
                $localData['type'] = 'local';
                $localData['id'] = $item['id'];
                $localData['pur_number'] = $item['pur_number'];
                $localData['requisition_number'] = $item['requisition_number'];
                $localData['supplier_name'] = $item['supplier_name'];
                $localData['buyer_account'] = $item['buyer_account'];
                $localData['order_number'] = $item['order_number'];
                $localData['pay_status'] = pay_status_type($item['pay_status']);//获取请款单状态
                $localData['product_money'] = number_format($item['product_money'], 2);// 商品金额
                $localData['create_user_name'] =$item['create_user_name'];

                //计算本地金额及总金额
                $nativeTotalAmount = $item['pay_price'];
                //总金额
                $totalMoney += $nativeTotalAmount;
                $localData['native_total_amount'] = $nativeTotalAmount;
                $localData['discount'] = $discount;
                $localData['shippfree'] = $shippfree;
                // 取消未到货退款金额
                $localData['orderRefundPrice'] = isset($item['orderRefundPrice'])?$item['orderRefundPrice']:'';
                $localData['orderRefundStatus'] = isset($item['orderRefundStatus'])?$item['orderRefundStatus']:'';
                $localData['orderRefundStatusCn'] = isset($item['orderRefundStatusCn'])?$item['orderRefundStatusCn']:'';

                /**@1688返回结果**/
                if (isset($item['alibaba']['result']) && $baseInfo = $item['alibaba']['result']) {
                    $alibabaData['type'] = 'alibaba';
                    //公司名称
                    if (isset($baseInfo['sellerContact']) && isset($baseInfo['sellerContact']['companyName'])) {
                        $alibabaData['supplier_name'] = $item['supplier_name'];
                        //$baseInfo['sellerContact']['companyName'];
                    } else {
                        $alibabaData['supplier_name'] = '';
                    }
                    $alibabaData['buyer_account'] = isset($baseInfo['buyerLoginId']) ? $baseInfo['buyerLoginId'] : '';//账号
                    $alibabaData['order_number'] = isset($baseInfo['idOfStr']) ? $baseInfo['idOfStr'] : '';//拍单号
                    $alibabaData['pay_status'] = isset($baseInfo['status']) ? alibaba_pay_status($baseInfo['status']) : '';//阿里巴巴开放平台订单支付状态

                    $alibabaData['native_total_amount'] = isset($baseInfo['totalAmount']) ? $baseInfo['totalAmount'] : '';//应付金额
                    $alibabaData['discount'] = isset($baseInfo['discount']) ? number_format(($baseInfo['discount'] / 100), 2) : '';//优惠
                    $alibabaData['shippfree'] = isset($baseInfo['shippingFee']) ? number_format($baseInfo['shippingFee'], 2) : '';//运费
                    $alibabaData['shippingFee'] = $alibabaData['shippfree'];//运费
                    $alibabaData['product_money'] = isset($baseInfo['sumProductPayment']) ? number_format($baseInfo['sumProductPayment'], 2) : '';//商品金额  
                    // 1688退款金额
                    $alibabaData['aliRefundPrice']      = isset($baseInfo['aliRefundPrice']) ? $baseInfo['aliRefundPrice'] : '';//金额
                    $alibabaData['aliRefundStatus']     = isset($baseInfo['aliRefundStatus']) ? $baseInfo['aliRefundStatus'] : '';//状态
                    $alibabaData['aliRefundStatusCn']   = isset($baseInfo['aliRefundStatusCn']) ? $baseInfo['aliRefundStatusCn'] : '';//状态中文

                    $alibabaData_pay_price          = isset($baseInfo['totalAmount']) ? $baseInfo['totalAmount']: 0;//金额
                    $alibabaData_aliRefundPrice     = isset($baseInfo['aliRefundPrice']) ? $baseInfo['aliRefundPrice'] : 0;//金额
                    $alibabaData['pay_price']       = $alibabaData_pay_price - $alibabaData_aliRefundPrice;//金额
                    $alibabaData['pay_price']       = number_format($alibabaData['pay_price'],2);//应付金额-扣减退款后

                    /**@显示说明**/
                    $noticeData['type'] = 'notice';
                    $noticeData['review'] = true;
                    $noticeData['supplier_name_equal'] = trim($item['supplier_name']) == trim($alibabaData['supplier_name']) ? true : false;//供应商就是否相等
                    $noticeData['account_equal'] = trim($item['buyer_account']) == trim($alibabaData['buyer_account']) ? true : false;//显示说明(账号是否相等)
                    $noticeData['pai_number_equal'] = trim($item['order_number']) == trim($alibabaData['order_number']) ? true : false;//拍单号是否相等

                    //是否就待财务付款
                    if( $item['pay_status']==40 and $alibabaData['pay_status']=='等待买家付款'){
                        $noticeData['pay_status_equal'] = true;
                    }else{
                        $noticeData['pay_status_equal'] = false;
                    }

                    $item['pay_price'] = number_format($item['pay_price'], 2);// 保留2位小数
                    $localData['pay_price'] = $item['pay_price'];

                    //支付金额是否等1688返回金额及优惠=100、运费=10
                    $pay_price_equal = false;
                    if ($item['pay_price'] === $alibabaData['pay_price']) {// 都转成 两位小数的字符串比较
                        $pay_price_equal = true;
                    }
                    $noticeData['pay_price_equal'] = $pay_price_equal;

                    //比较本地金额与1688返回金额精度是否相等
                    $native_totalAmount_equal = false;
                    if (bccomp($nativeTotalAmount, $alibabaData['native_total_amount'], 2) == 0) {
                        $native_totalAmount_equal = true;
                    }
                    $noticeData['native_totalAmount_equal'] = $native_totalAmount_equal;

                } else {
                    $alibabaData['alibaba'] = $item['alibaba'];
                }

                $responseData[$key][0] = $localData;
                $responseData[$key][1] = $alibabaData;
                $responseData[$key][2] = $noticeData;
                $responseData['totalMoney'] = number_format($totalMoney, 2);
            }
        }
    }

}