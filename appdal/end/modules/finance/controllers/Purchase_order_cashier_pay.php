<?php

/**
 * Created by PhpStorm.
 * 付款控制模块
 * User: jackson
 * Date: 2019/02/14
 */
class Purchase_order_cashier_pay extends MY_Controller
{


    public function __construct()
    {
        self::$_check_login = false;// 是否验证用户登录 true.验证
        parent::__construct();
        $this->load->model('purchase_order_pay_model'); // 请款单
        $this->load->model('Purchase_order_pay_type_model', 'orderPayType');
        $this->load->model('supplier_model','',false,'supplier');     
        $this->load->model('finance/Baofoo_fopay_model');
        $this->load->model('Payment_order_pay_model'); // 请款单

    }

    /**
     * @desc 1688在线付款(获取1688订单数据，访问1688收银台进行付款-每次只能批量支付同一个申请人的单)
     * @author Jackson
     * @Date 2019-02-12 17:01:00
     * @return array()
     **/
    public function online_payment()
    {

        //加载阿里1688账号管理MODEL
        $this->load->model('Alibaba_account_model', 'alibabaaccount');
        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('finance/Purchase_order_cancel_to_receipt_model', 'cancelReceiptModel');
        //如果post时提交一条数据
        if (IS_POST) {

            $post = gp();
            try {
                if (!isset($post['applicant']) || !is_numeric($post['applicant'])) {
                    throw new Exception('请求参数不能为空: applicant');
                }
                if (!isset($post['purchase_account']) || empty($post['purchase_account'])) {
                    throw new Exception('请求参数不能为空: purchase_account');
                }

                //获取1688账号信息(根据绑定账户)
                $_aliWhere = ['user_id' => $post['applicant'], 'account' => $post['purchase_account']];
                $accountData = $this->alibabaaccount->findOnes($_aliWhere);
                if (empty($accountData)) {
                    throw new Exception('申请人没有绑定开发者账户，请绑定后重试');
                }
                $accountData = (object)$accountData;
                $orderIdList = [];
                foreach ($post['payment'] as $val) {
                    $orderIdList[] = $val['order_number'];
                }
                $long = implode(',', $orderIdList);
                if (!$long) {
                    throw new Exception('没有可支付的单号');
                }

                //请求数据组合
                $param = array();
                $this->alibabaaccount->assemblyParameter($param, $long, $accountData, true);
                $apiInfo = $param['_apiInfo'];
                unset($param['_apiInfo']);

                //请求接口
                $response = $this->alibabaaccount->executeApi($apiInfo, $param);

                //返回API请求结果(返回前端页面-确认付款)
                if (isset($response['payUrl'])) {
                    $this->send_data($response, '1688在线付款POST接口(单个)', true);
                } else {
                    $messg = isset($response['erroMsg']) ? $response['erroMsg'] : $response['error_message'];
                    throw new Exception($messg);
                }
            } catch (Exception $e) {
                $this->send_data(null, $e->getMessage(), false);
            }

        } else {
            $get = gp();
            try {

                $ids = $get['ids'];
                if (!$ids) {
                    throw new Exception('参数错误');
                }
                $reg = "/^[0-9,]+$/";
                if (!preg_match($reg, $ids)) {
                    throw new Exception('参数格式错误');
                }

                $ids = explode(',', $ids);
                $newIds = [];
                if (isset($_COOKIE['not-pay'])) {
                    $notPay = $_COOKIE['not-pay'];
                    $not = explode(',', $notPay);
                    foreach ($ids as $id) {
                        if (!in_array($id, $not)) {
                            $newIds[] = $id;
                        }
                    }
                } else {
                    $newIds = $ids;
                }

                //根据选择的ID获取所有付款记录
                $orderPayDatas = $this->purchase_order_pay_model->findRecordeByIds($newIds);

                if (empty($orderPayDatas)) {
                    throw new Exception('采购单请款单表-没有找到数据');
                }
                $applicants = [];
                $purchaseAccount = [];
                $list = [];
                // 拍单号验证
                $reg_order = "/^[0-9]{10,25}$/";

                // 组建本地数据
                foreach ($orderPayDatas as $m) {
                    $m = (object)$m;
                    $applicants[] = $m->applicant;
                    $purchaseAccount[] = $m->purchase_account;
                    $subData = (array)$m;

                    //判断是否为网采单是,进行支付，之后提示不支持(合同来源（1合同 2网络【默认】 3账期采购）)
                    if ($m->source != SOURCE_NETWORK_ORDER) {
                        throw new Exception('此单不是网采不支持付款，请查检');
                    }
                    //判断支付状态
                    if ($m->pay_status != PAY_WAITING_FINANCE_PAID) {
                        throw new Exception('此单不是可支付状态，请查检');
                    }

                    //拍单号(优先从采购单请款单表获取)
                    $order_number = $m->pai_number;
                    if (!$order_number) {
                        /**@Table:pur_purchase_order_pay_type 中的 platform_order_number* */
                        $payTypeDatas = $this->orderPayType->get_one($m->pur_number);
                        if (empty($payTypeDatas)) {
                            throw new Exception('没有找到对应的拍单号【' . $m->pur_number . '】，请查检');
                        }
                        $payTypeDatas = (object)$payTypeDatas;
                        $order_number = $payTypeDatas->pai_number;
                    }

                    //网采账号通过接口获取
                    $orderAccount = trim($m->purchase_account);// 订单网采账号
                    if ($orderAccount) {
                        $subData['buyer_account'] = $orderAccount;
                    } else {
                        $subData['buyer_account'] = '未设置';
                    }

                    // 拍单号存在空格
                    $order_number = trim($order_number);
                    // 踢出拍单号异常的数据
                    if (!preg_match($reg_order, $order_number)) {
                        continue;
                    }

                    $subData['order_number'] = $order_number;

                    /**@Table:pur_purchase_order_pay 中的 freight,discount * */
                    // 修改 运费、优惠计算 方法 @author Jolon @date 2018-10-13 13:32
                    $freight = !empty($m->freight) ? $m->freight : 0;
                    $discount = !empty($m->discount) ? $m->discount : 0;
                    $subData['order_freight'] = $freight;
                    $subData['order_discount'] = $discount;

                    /**@Table:pur_supplier 中的 supplier_name  以 supplier_code 供应商code关联* */
                    $this->load->model('supplier/supplier_model', 'supplierModel');
                    $suppliers = $this->supplierModel->get_supplier_name_bycode($m->supplier_code, 'supplier_name');
                    $subData['supplier_name'] = isset($suppliers['supplier_name']) ? $suppliers['supplier_name'] : '';// 供应商信息
                    //判断验货验厂

                     $errer = $this->Payment_order_pay_model->is_check_status($m->requisition_number);

                     if(!empty($errer)){
                         throw new Exception($errer);
                     }

                    $list[] = $subData;
                }

                //判断批量支付是否为同一个申请人(支付时申请人只能是同一个申请人)
                if (empty($list)) {
                throw new Exception('没有可支付的单，可能是这些单的拍单号异常');
            }

                // 拉取1688数据
                $applicant = $applicants[0]; // 申请人
                $_aliWhere = ['user_id' => $applicant, 'account' => $purchaseAccount[0]];
                $accountData = $this->alibabaaccount->findOnes($_aliWhere);
//
                if (empty($accountData)) {
                    throw new Exception('申请人没有绑定开发者账户，请绑定后重试');
                }
                $accountData = (object)$accountData;
                //请求java接口，获取订单详情
                $order_num_arr = array_column($list, 'order_number');//需支付订单集合
                $result_order_item = $this->aliorderapi->getListOrderDetail(null,$order_num_arr);

                // 退款状态对照
                $refund_status = [
                    "waitselleragree" => '等待卖家同意',
                    "refundsuccess" => '退款成功',
                    "refundclose" => '退款关闭',
                    "waitbuyermodify" => '待买家修改',
//                    "waitbuyersend" => '等待买家退货',
                    "waitsellerreceive" => '等待卖家确认收货',
                ];

                $val['alibaba']['result']['aliRefundPrice'] = '';
                $val['alibaba']['result']['aliRefundStatus']   = '';
                $val['alibaba']['result']['aliRefundStatusCn'] = '';
                foreach ($list as &$val) {
                    $val['purchase_account'] = isset($accountData->account)?$accountData->account:$val['purchase_account'];// 显示主账号
                    $val['buyer_account'] = isset($accountData->account)?$accountData->account:$val['buyer_account'];// 显示主账号

                    $order_number_info = isset($result_order_item[$val['order_number']])?$result_order_item[$val['order_number']]:[];
                    if (isset($order_number_info['data']) && $order_number_info['code']==200) {
                        $baseInfo = $order_number_info['data']['baseInfo'];
                        $val['alibaba']['result'] = $baseInfo;
                    } else {
                        throw new Exception(isset($order_number_info['msg'])?$order_number_info['msg']:'获取1688采购单信息失败');
                    }

                    // 1688 退款信息
                    $aRPrice = $this->aliorderapi->getListOrderRefund($val['order_number'], true);
                    if(isset($aRPrice['code']) && $aRPrice['code'] == 200 && isset($aRPrice['data']) && is_array($aRPrice['data'])){
                        $r_status = '';
                        $r_status_cn = [];
                        $r_price = 0;
                        foreach ($aRPrice['data'] as $r_val){
                            $rf_status = $r_val['status'];
                            $refundPayment = (int)$r_val['refundPayment'];
                            $refundCarriage = (int)$r_val['refundCarriage'];
                            if($rf_status == 'refundsuccess'){
                                $r_price += $refundPayment + $refundCarriage;
                                $r_status = $rf_status;
                            }
                            $rw_status = isset($refund_status[$rf_status]) ? $refund_status[$rf_status].":".round($r_price/100, 3): "";
                            if($rw_status != '')$r_status_cn[] = $rw_status;
                        }
                        $val['alibaba']['result']['aliRefundPrice'] = round($r_price/ 100, 3);
                        $val['alibaba']['result']['aliRefundStatus']   = $r_status;
                        $val['alibaba']['result']['aliRefundStatusCn'] = implode(',', $r_status_cn);
                    }

                    // 采购系统内采购单取消未到货退款金额
                    $orderRefund = $this->cancelReceiptModel->get_receipt_price_by_order($val['pur_number']);
                    if($orderRefund){
                        $val['orderRefundPrice']    = array_sum(array_column($orderRefund,'pay_price'));
                        $val['orderRefundStatus']   = end($orderRefund)['pay_status'];
                        $val['orderRefundStatusCn'] = getReceivePayStatus($val['orderRefundStatus']);
                    }
                }
                //获取支付链接
                $param = array();
                $long = implode(',', $order_num_arr);
                //超级管理账号数据
                $account_data = $this->alibabaaccount->get_alibaba_account_by_account('yibaisuperbuyers');
                $account_data = (object)$account_data;
                $this->alibabaaccount->assemblyParameter($param, $long, $account_data, true);
                $apiInfo = $param['_apiInfo'];
                unset($param['_apiInfo']);
                $response_pay = $this->alibabaaccount->executeApi($apiInfo, $param);
                $pay_url = isset($response_pay['payUrl'])?$response_pay['payUrl']:'';
                if(empty($pay_url)){
                    throw new Exception(isset($response_pay['erroMsg'])?$response_pay['erroMsg']:'获取收银台支付链接异常');
                }
                //申请人名称
                $applicantName = getUserNameById($applicant); 
                //返回批量支付数据((返回前端页面-确认付款))
                $this->send_data(['list' => $list, 'applicant' => $applicant,'pay_url'=>$pay_url, 'applicantName' => $applicantName], '1688在线批量付款指接口', true);
            } catch (Exception $e) {
                $this->send_data(null, $e->getMessage(), false);
            }
        }

    }
    /**
     * 更新请款单状态
     * @throws Exception
     */
    public function online_paymeny_update(){
        $this->load->helper('status_order');
        $get = gp();
        $ids = $get['ids'];
        try {
            if (!$ids) {
                throw new Exception('参数错误');
            }
            $reg = "/^[0-9,]+$/";
            if (!preg_match($reg, $ids)) {
                throw new Exception('参数格式错误');
            }

            $ids = explode(',', $ids);
            $newIds = [];
            if (isset($_COOKIE['not-pay'])) {
                $notPay = $_COOKIE['not-pay'];
                $not = explode(',', $notPay);
                foreach ($ids as $id) {
                    if (!in_array($id, $not)) {
                        $newIds[] = $id;
                    }
                }
            } else {
                $newIds = $ids;
            }

            //根据选择的ID获取所有付款记录
            $orderPayDatas = $this->purchase_order_pay_model->findRecordeByIds($newIds);
            if (empty($orderPayDatas)) {
                throw new Exception('采购单请款单表-没有找到数据');
            }
            $list=[];
            foreach ($orderPayDatas as $key => $value) {
             $list[]=[
                 'id'=>$value['id'],
                 'status'=>getPayStatus($value['pay_status']),
             ];
            }
            $this->success_json($list);
        } catch (Exception $exc) {
           
            $this->error_json($exc->getMessage());
        }   
    }

    /**
     * @desc 1688超级卖家在线付款(获取1688订单数据，访问1688收银台进行付款:  可以同时支付所有子账号的单)
     * @author Jackson
     * @Date 2019-02-12 17:01:00
     * @return array()
     **/
    public function super_online_payment()
    {

        //加载阿里1688账号管理MODEL
        $this->load->model('Alibaba_account_model', 'alibabaAccount');

        try {
            //获取1688账号信息(根据绑定账户)
            $_aliWhere = ['account' => 'yibaisuperbuyers'];
            $accountData = $this->alibabaAccount->findOnes($_aliWhere);
            if (empty($accountData)) {
                throw new Exception('超级买家账号，不存在');
            }
            $accountData = (object)$accountData;
            //POST提交
            if (IS_POST) {
                $post = gp();
                $orderIdList = [];
                foreach ($post['payment'] as $val) {
                    $orderIdList[] = $val['order_number'];
                }
                $long = implode(',', $orderIdList);
                if (!$long) {
                    throw new Exception('没有可支付的单号');
                }

                //请求数据组合
                $param = array();
                $this->alibabaAccount->assemblyParameter($param, $long, $accountData, true);
                $apiInfo = $param['_apiInfo'];
                unset($param['_apiInfo']);

                //请求接口
                $response = $this->alibabaAccount->executeApi($apiInfo, $param);

                //返回API请求结果(返回前端页面-确认付款)
                if (isset($response['payUrl'])) {
                    $this->send_data($response, '1688在线付款POST接口(超级卖家)', true);
                } else {
                    $messg = isset($response['erroMsg']) ? $response['erroMsg'] : $response['error_message'];
                    $this->send_data(null, $messg, false);
                }


            } else {

                //获取操作用户是否是出纳人员
                $user_info = getActiveUserInfo();
                $uid = $user_info['user_id'];

                /*
                $qx = \app\models\AlibabaZzh::find()->where(['user' => $uid, 'level' => 0])->one();
                if(empty($qx)) {
                    $this->send_data(null, '你不是出纳，请联系出纳负责人开通', false);
                }

                //支付权限
                $payable_users = \app\models\AlibabaZzh::getPayableIds($qx->id);
                if(!$payable_users) {
                    $this->send_data(null, '你没有支付权限，请联系出纳负责人开通', false);
                }
                */
                //测试数据
                $payable_users = [$uid];
                $applicantID = '';

                $get = gp();
                $ids = $get['ids'];
                if (!$ids) {
                    throw new Exception('参数错误');
                }
                $reg = "/^[0-9,]+$/";
                if (!preg_match($reg, $ids)) {
                    throw new Exception('参数格式错误');
                }
                $ids = explode(',', $ids);
                $newIds = [];
                if (isset($_COOKIE['not-pay'])) {
                    $notPay = $_COOKIE['not-pay'];
                    $not = explode(',', $notPay);
                    foreach ($ids as $id) {
                        if (!in_array($id, $not)) {
                            $newIds[] = $id;
                        }
                    }
                } else {
                    $newIds = $ids;
                }

                //根据选择的ID获取所有付款记录
                $orderPayDatas = $this->purchase_order_pay_model->findRecordeByIds($newIds);
                if (empty($orderPayDatas)) {
                    throw new Exception('采购单请款单表-没有找到数据');
                }

                $list = [];
                // 拍单号验证
                $reg_order = "/^[0-9]{10,25}$/";
                // 组建本地数据
                foreach ($orderPayDatas as $m) {

                    $m = (object)$m;
                    // 踢出不在流中的数据
                    if (!in_array($m->applicant, $payable_users)) {
                        continue;
                    }
                    $applicantID = $m->applicant;

                    $subData = (array)$m;
                    //这里平台订单号没有
                    $this->load->model('Purchase_order_pay_type_model', 'orderPayType');
                    $payTypeDatas = (object)$this->orderPayType->get_one($m->pur_number);
                    $order_number = isset($payTypeDatas->pai_number) ? $payTypeDatas->pai_number : '';
                    $order_account = isset($payTypeDatas->purchase_acccount) ? $payTypeDatas->purchase_acccount : '';

                    // 踢出非超级买家下的拍单
                    if ($order_account !== 'yibaisuperbuyers') {
                        continue;
                    }
                    $subData['buyer_account'] = $order_account;

                    // 拍单号存在空格
                    $order_number = trim($order_number);

                    // 踢出拍单号异常的数据
                    if (!preg_match($reg_order, $order_number)) {
                        continue;
                    }
                    $subData['order_number'] = $order_number;

                    // 修改 运费、优惠计算 方法 @author Jolon @date 2018-11-05 11:32
                    $price_list = $this->payment_order_pay_model->getPrice($m, false, SOURCE_NETWORK_ORDER, true);// 直接返回显示的金额的值
                    $subData['order_freight'] = $price_list['freight'];
                    $subData['order_discount'] = $price_list['discount'];

                    // 供应商信息
                    /**@Table:pur_supplier 中的 supplier_name  以 supplier_code 供应商code关联* */
                    $this->load->model('supplier/supplier_model', 'supplierModel');
                    $suppliers = $this->supplierModel->get_supplier_name_bycode($m->supplier_code, 'supplier_name');
                    $subData['supplier_name'] = isset($suppliers['supplier_name']) ? $suppliers['supplier_name'] : '';
                    $list[] = $subData;

                }

                if (empty($list)) {
                    throw new Exception('没有可支付的单，可能是这些单的拍单号异常');
                }

                foreach ($list as &$val) {

                    //请求数据组合
                    $param = array();
                    $this->alibabaAccount->assemblyParameter($param, $val['order_number'], $accountData);
                    $apiInfo = $param['_apiInfo'];
                    unset($param['_apiInfo']);

                    //请求接口
                    $response = $this->alibabaAccount->executeApi($apiInfo, $param);

                    //处理返回数据
                    if (isset($response['result']) && isset($response['result']['baseInfo'])) {
                        $val['alibaba']['result'] = $response['result']['baseInfo'];
                    } else {
                        $val['alibaba'] = '错误：' . json_encode($response);
                    }
                }
                //申请人名称
                $applicantName = getUserNameById($applicantID);
                //返回批量支付数据(返回前端页面-确认付款)
                $this->send_data(['list' => $list, 'applicant' => $applicantID, 'applicantName' => $applicantName], '1688在线批量付款指接口(超级卖家)', true);

            }
        } catch (Exception $e) {
            $this->send_data(null, $e->getMessage(), false);
        }

    }

    /**
     * @title 1688在线付款后的确认付款操作
     * @author jackosn
     * @date 2019-12-16 16:00
     */
    public function affirm_payment()
    {


        $ids = gp('ids');
        if (!$ids) {
            $this->send_data(null, '参数错误', false);
        }
        $ids = explode(",", $ids);

        //根据选择的ID获取所有付款记录
        $orderPayDatas = $this->purchase_order_pay_model->findRecordeByIds($ids);
        if (empty($orderPayDatas)) {
            $this->send_data(null, '采购单请款单表-没有找到数据', false);
        }

        //事务开始
        $this->db->trans_start();
        try {
            $this->load->library('alibaba/AliOrderApi');
            $this->load->model('system/Bank_card_model', 'bankCart');
            foreach ($orderPayDatas as $mod) {
                $mod = (object)$mod;
                /**付款状态(10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,50.已部分付款,51.已付款,90.已取消)**/
                if (in_array($mod->pay_status, [PAY_PAID, PAY_FINANCE_REJECT, PAY_CANCEL])) {
                    continue;
                }

                $ali_order_data = $this->aliorderapi->getListOrderDetail(null,$mod->pai_number);
                if(isset($ali_order_data[$mod->pai_number]['data'])){
                    $payChannelList = $ali_order_data[$mod->pai_number]['data']['baseInfo']['payChannelList'];
                    foreach($payChannelList as $channel_value){
                        if(stripos($channel_value,'支付宝') !== false){
                            $channle_account = '支付宝';
                            break;
                        }elseif(stripos($channel_value,'跨境宝') !== false){
                            $channle_account = '跨境宝';
                            break;
                        }
                    }
                }
                if(isset($channle_account) and !empty($channle_account)){
                    $bank = $this->bankCart->get_payment_account_by_platform('1688',$channle_account);//获取银行卡信息
                }else{
                    $bank = [];
                }

                //更新采购单请款单表数据
                $updateData['pay_status'] = PAY_PAID;//网采是全额付款
                $updateData['real_pay_price'] = $mod->pay_price;//网采实际付款金额
                $updateData['payer_id'] = getActiveUserId();//获取当前用户ID
                $updateData['payer_name'] = getActiveUserName();//获取当前用户名称
                $updateData['payer_time'] = date('Y-m-d H:i:s', time());
                $updateData['payment_notice'] = '1688批量在线支付3.0';
                $updateData['pay_account'] = isset($bank['account_short']) ? $bank['account_short'] : '';
                $updateData['pay_number'] = isset($bank['account_holder']) ? $bank['account_holder'] : '';
                $updateData['pay_branch_bank'] = isset($bank['branch']) ? $bank['branch'] : '';
                $updateData['k3_account'] = isset($bank['k3_bank_account']) ? $bank['k3_bank_account'] : '';

                //获取采购账号
                $order_account = '';
                $payTypeData = $this->orderPayType->findOne(['purchase_number' => $mod->pur_number], 'id,purchase_acccount');
                if (!empty($payTypeData)) {
                    $payTypeData = (object)$payTypeData;
                    $order_account = !empty($payTypeData->purchase_acccount) ? $payTypeData->purchase_acccount : '';
                }

                //更新采购单信息表支付状态
                /**@Table pur_purchase_order 支付状态：pay_status 根据采购单号* */
                $this->load->model("purchase/Purchase_order_model", "purchaseOrders");
                $this->purchaseOrders->change_designation_status($mod->pur_number, ['pay_status' => PAY_PAID,'pay_time'=>date('Y-m-d H:i;s')]);

                //记录1688支付付款流水
                $payWater = [
                    'pur_number' => $mod->pur_number,
                    'supplier_code' => $mod->supplier_code,
                    'pay_price' => $mod->pay_price,
                    'currency' => $mod->currency_code,
                    'account_abbreviation' => trim($order_account)
                ];
                $this->load->model("Purchase_order_pay_water_model", "orderPayWater");
                $this->orderPayWater->save_one_forali($payWater);

                //记录操作日志
                operatorLogInsert([
                    'id' => $mod->pur_number,
                    'type' => "1688批量在线支付确认",
                    'content' => '1688批量在线支付3.0>' . $mod->id,
                ]);

                //采购单请款单状态更新
                $_update = $this->purchase_order_pay_model->update($updateData, ['id' => $mod->id]);

                $this->purchase_order_pay_model->push_purchase_order_pay_status($mod->pur_number);// 推送采购单付款状态
                
            }

            //更新付款结果
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                throw new Exception("对不起，出错了!");
            }

            $this->send_data(null, '恭喜你，付款成功', true);

        } catch (Exception $e) {
            $this->send_data(null, $e->getMessage(), false);
        }

    }

    /**
     * @desc 财务驳回请款单
     * @author Jackson
     * @Date 2019-02-18 14:57:00
     * @return array()
     **/
    public function cashier_reject()
    {

        $id = gp('id');

        if (!$id) {
            $this->send_data(null, '驳回请款单id不能为空', true);
        }

        //事务开始
        $this->db->trans_start();
        try {

            //采购单请款单数据根据id
            $payData = $this->purchase_order_pay_model->getDataByCondition(['id' => $id]);
            if (!empty($payData)) {
                $payData = (object)$payData[0];
            } else {
                throw new Exception("不存在此请款单记录");
            }
            if(!in_array($payData->pay_status,[PAY_WAITING_MANAGER_REVIEW,PAY_WAITING_FINANCE_REVIEW,PAY_WAITING_FINANCE_PAID,PAY_UFXFUIOU_REVIEW])){
                throw new Exception("该状态下不支持驳回操作");
            }

            //更新采购单信息表驳回状态
            /**@Table pur_purchase_order 支付状态：pay_status 根据采购单号
             * 付款状态:付款状态(10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,50.已部分付款,51.已付款,90.已取消)
             * */
            $this->load->model("purchase/Purchase_order_model", "purchaseOrders");
            $this->purchaseOrders->change_designation_status($payData->pur_number, ['pay_status' => PAY_FINANCE_REJECT,]);

            //更新采购单请款单支付状态
            $this->purchase_order_pay_model->change_designation_status(['id' => $id], ['pay_status' => PAY_FINANCE_REJECT, 'payment_notice' => gp('payment_notice')
                ,'processing_notice'=>gp('payment_notice'),'processing_time'=>date("Y-m-d H:i:s"),'approver'=>getActiveUserId()]);

            $this->purchase_order_pay_model->push_purchase_order_pay_status($payData->pur_number,gp('payment_notice'));// 推送采购单付款状态

            //更新付款结果
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                throw new Exception("对不起，驳回失败!");
            }

            $this->send_data(null, '驳回操作成功', true);

        } catch (Exception $e) {
            $this->send_data(null, $e->getMessage(), false);
        }

    }

    /**
     * @desc 富友在线支付
     * @author Jackson
     * @Date 2019-02-14 17:01:00
     * @return array()
     **/
    public function ufxfuiou_pay()
    {

        $this->load->model('system/Bank_card_model', 'bankCart');

        if (IS_GET) {

            $ids = gp('ids');
            //检验批量付款的数据是否能一起付款
            try {
                $checkInfo = $this->purchase_order_pay_model->check_pay_apply_datas($ids);
            } catch (Exception $e) {
                $this->send_data(null, $e->getMessage(), false);
            }

            //采购单请款单数据根据勾选ids
            $payData = $this->purchase_order_pay_model->getDataByCondition(['where_in' => ['id' => explode(",", $ids)]]);

            //获取银行卡信息(这里需要设置：170)
            $bank = $this->bankCart->findOne(['id' => 170, 'status' => 1]);
            if (empty($bank)) {
                $bank = $this->bankCart->findOne(['status' => 1]);
            }

            //区分网采单、合同单
            $type = '';
            if ($checkInfo['source'] == SOURCE_NETWORK_ORDER) {
                $type = 'purchaseOrder';//网采单
            } elseif ($checkInfo['source'] == SOURCE_COMPACT_ORDER) {
                $type = 'purchaseCompact';//合同单
            } else {
                $this->send_data(null, '请款类型异常', false);
            }

            //返回批量支付数据((返回前端页面-确认付款))模板
            $_datas = ['type' => $type, 'ids' => $ids, 'is_drawback' => $checkInfo['is_drawback'], 'model' => $payData, 'bank' => $bank];
            $this->send_data($_datas, '富友在线支付接口', true);

        }

        //POST提交情况下
        if (IS_POST) {
            $post = gp();
            $fuiouPayDatas['ids']= $post['ids'];
            $fuiouPayDatas['Fuiou']['charge']=$post['charge']==0?"00":'01';      //是否手续费
            $total_pay_price = $post['total_pay_price'];
            if($post['charge']==1){// 我方承担手续费
                $total_pay_price = $total_pay_price-1;
                $procedure_party = PAY_PROCEDURE_PARTY_A;// 甲方
                $procedure_fee   = 1;
            }else{// 非我方承担手续费
                $procedure_party = PAY_PROCEDURE_PARTY_B;// 乙方
                $procedure_fee   = 1;
            }

            $account_short                = $post['account_short'];
            $fuiouPayDatas['Fuiou']['PayAccount']='pay_001';           //富友账号
            $fuiouPayDatas['Fuiou']['PayInfo']=$post['account_short']; //付款账号简称
            $fuiouPayDatas['Fuiou']['bankCardTp']='01'; //卡属性：01对私（默认）、02对公
            $fuiouPayDatas['Fuiou']['oppositeName']=$post['account_name']; //收款方名称
            $fuiouPayDatas['Fuiou']['oppositeIdNo']=$post['id_number']; //收款方证件号
            $fuiouPayDatas['Fuiou']['bankId']=$post['payment_platform_branch']; //支行号(或支行名称)
            $fuiouPayDatas['Fuiou']['bankCardNo']=$post['account']; //收款账号
            $fuiouPayDatas['Fuiou']['amt']=$total_pay_price; //转账金额
            $fuiouPayDatas['Fuiou']['isNotify']='01'; //是否需要到账短信通知  01：需要 02：不需要（默认）
            $fuiouPayDatas['Fuiou']['oppositeMobile']=$post['phone_number']; //收款方手机号
            $fuiouPayDatas['Fuiou']['remark']= make_semiangle($post['remark']); //付款备注
            $pur_tran_num = 'FY00'.date("YmdHis").rand(100, 999);
            $fuiouPayDatas['Fuiou']['pur_tran_num'] = $pur_tran_num;
            $this->load->library('Ufxfuiou');
            try {
//                $fuiouPay = Ufxfuiou::bankCardPay($fuiouPayDatas);
                $fuiouPay = Ufxfuiou::data_verification($fuiouPayDatas);
            } catch (Exception $e) {
                $this->send_data(null, $e->getMessage(), false);
            }

            $reslut = $this->purchase_order_pay_model->save_ufxfuiou($fuiouPayDatas,$procedure_party,$procedure_fee,$account_short);
            if($reslut['code']){
                $this->success_json([], null, $reslut['message']);
            }else{
                $this->error_json($reslut['message']);
            }

            //返回成功处理
//            if (isset($fuiouPay['status']) && $fuiouPay['status'] == 'success') {
//                try {
//                    $result = $this->purchase_order_pay_model->save_fuiou_pay_result($fuiouPayDatas, $fuiouPay);
//                } catch (Exception $e) {
//                    $this->send_data(null, $e->getMessage() . " 返回请求地址", false);
//                }
//                $this->send_data(null, $result['message'], TRUE);
//
//            } else {
//                $message = isset($fuiouPay['message']) ? $fuiouPay['message'] : '付款异常，请联系技术部解决';
//                $this->send_data(null, $message . " 返回请求地址", false);
//            }
        }
    }

    /**
     * @desc 获取富友支付信息
     * @author Jackson
     * @Date 2019-02-15 17:01:00
     * @return array()
     **/
    public function get_fuiou_pay_info()
    {

        $this->load->model("Purchase_order_pay_ufxfuiou_model", "orderPayfuiou");
        //测试时是!IS_AJAX 正式是 IS_AJAX
        if (IS_GET) {

            $requisition_number = gp('requisition_number');
            if (empty($requisition_number)) {
                $this->send_data(null, "确省必要参数", false);
            }

            //富有请款单记录
            $pur_tran_no = $this->orderPayfuiou->findOne(['requisition_number' => $requisition_number, 'status' => 1], 'id,pur_tran_num');
            if (empty($pur_tran_no)) {
                $this->send_data(null, "当前付款申请不是富友付款", false);
            }

            //根据流水号查询- 申请单号
            $requisition_numbers = $this->orderPayfuiou->getDataByCondition(['pur_tran_num' => $pur_tran_no['pur_tran_num'], 'status' => 1], 'requisition_number');
            if (empty($requisition_numbers)) {
                $this->send_data(null, "富友绑定关系异常", false);
            }

            $requisition_numbers = array_column($requisition_numbers, 'requisition_number');
            //查询采购单请款单信息根据 申请单号
            $wherecondition = [
                'where_in' => [
                    'requisition_number' => $requisition_numbers
                ]
            ];

            //查询字段
            $orderPayfileds = 'requisition_number,pur_number,pay_status';
            $pay_info = $this->purchase_order_pay_model->getDataByCondition($wherecondition, $orderPayfileds);
            if (empty($pay_info)) {
                $this->send_data(null, "付款信息异常", false);
            }

            $data = UfxFuiou::getTransferResult($pur_tran_no['pur_tran_num']);
            if (empty($data['status']) || $data['status'] == 'error') {
                $this->send_data($data['response'], "接口请求失败", false);
            } else {
                //todo 跳转到支付页面
                $_data = ['data' => $data, 'pay_info' => $pay_info, 'tran' => $pur_tran_no];
                $this->send_data($_data, "OK", true);
            }
        }

        //ajax请求及post提交
        if (IS_POST) {

            $refreshTranNo = gp('tran_no');
            if (empty($refreshTranNo)) {
                $this->send_data(null, "关键参数为空", false);
            }

            $response = UfxFuiou::getTransferResult($refreshTranNo);
            //请求日志
            try {
                $this->load->model("Purchase_order_pay_ufxfuiou_request_log_model", "payfuiouRequestLog");
                $this->payfuiouRequestLog->payfuiou_request_log($response, $refreshTranNo, '手动获取富友付款状态更新请款单状态', 3);
            } catch (Exception $e) {
                $this->send_data(null, $e->getMessage(), false);
            }

            if (empty($response['status']) || $response['status'] == 'error') {
                $this->send_data(null, '接口请求失败: ' . $response['response'], false);
            } else {
                if (empty($response['responseBody']) || empty($response['responseBody']['rspCode']) ||
                    $response['responseBody']['rspCode'] != '0000'
                ) {

                    $message = empty($response['responseBody']['rspDesc']) ? '接口返回异常' : $response['responseBody']['rspDesc'];
                    $this->send_data(null, '接口请求失败: ' . $message, false);

                } else {
                    if (!empty($response['responseBody']['resultSet']['result']['inOutSt']) &&
                        in_array($response['responseBody']['resultSet']['result']['inOutSt'], ['5005', '5007'])
                    ) {

                        $update = $this->orderPayfuiou->update_pay_status($refreshTranNo, $response['responseBody']['resultSet']['result']);
                        $this->send_data(null, $update['message'], false);

                    } else {
                        $msgs = '富友付款申请未完结不能手动更新系统付款状态,' . $response['responseBody']['resultSet']['result']['inOutSt'];
                        $this->send_data(null, $msgs, false);
                    }
                }

            }
        }
    }

    /**
     * @desc 富友支付通知地址
     * @author Jackson
     * @Date 2019-02-19 15:01:00
     * @return array()
     **/
    public function notify_url()
    {

        set_time_limit(0);
        $reponseStr = gp('reqStr');
        //判断返回数据中包含有特殊字符(&lt;、&gt;)时需要转换
        $reponseStr = htmlspecialchars_decode($reponseStr);
        $_responseData = ['response' => $reponseStr];
        //记录富友支付通知日志
        $this->load->model("Purchase_order_pay_ufxfuiou_request_log_model", 'ufxfuiouRequestLog');
        if (empty($reponseStr)) {
            $this->ufxfuiouRequestLog->payfuiou_request_log($_responseData, '无交易流水号', '接受回调数据', 2);
            exit('请求参数不能为空');
        }

        $pur_tran_no = '';
        try {
            $this->load->library('Ufxfuiou');
            $ufxiou = new UfxFuiou(); 
            if ($ufxiou->checkResponse($reponseStr)) {
                $responseBody = UfxFuiou::getXmlStrByTag($reponseStr, 'body', 0);
                $responseBodyArray = $ufxiou->xml_unserialize($responseBody);
                //通知类型04：转账到银行卡，划款结果通知；08失败
                $notifyType = isset($responseBodyArray['notifyType']) ? $responseBodyArray['notifyType'] : '04';
                $pur_tran_no = isset($responseBodyArray['notify' . $notifyType]['eicSsn']) && !empty($responseBodyArray['notify' . $notifyType]['eicSsn']) ? $responseBodyArray['notify' . $notifyType]['eicSsn'] : '无交易流水号';
                $this->ufxfuiouRequestLog->payfuiou_request_log($_responseData, $pur_tran_no, '接受回调数据', 2);

                //保存富友支付回调数据并变更请款单状态
                $this->purchase_order_pay_model->save_pay_notify_result($responseBodyArray);

                try{// 空异常-无需处理
                    file_get_contents(CG_SYSTEM_APP_DAL_IP.'ufxfuiou_api/get_pay_ufxfuiou_voucher?pur_tran_num='.$pur_tran_no);// 自动运行抓取回执信息
                }catch(Exception $e){

                }
            } else {
                $pur_tran_no = '回调数据没有通过验证';
                $this->ufxfuiouRequestLog->payfuiou_request_log($_responseData, $pur_tran_no, '接受回调数据', 2);
            }

        } catch (Exception $e) {
            $this->ufxfuiouRequestLog->payfuiou_request_log($_responseData, $pur_tran_no, '接受回调数据', 2);
            exit('数据验证失败: ' . $e->getMessage());
        }
        exit("更新结果: " . $pur_tran_no);
    }


    /**
     * @desc   跨境宝支付
     * @author Jaden
     * @Date   2019-03-19
     * @return array()
     **/
    public function cross_border_payment(){
        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('Alibaba_account_model', 'alibabaaccount');
        $this->load->model('finance/Purchase_order_cancel_to_receipt_model', 'cancelReceiptModel');

        $get = gp();
        try{
            $ids = $get['ids'];
            if(!$ids){
                throw new Exception('参数错误');
            }
            $reg = "/^[0-9,]+$/";
            if(!preg_match($reg, $ids)){
                throw new Exception('参数格式错误');
            }

            $ids = explode(',', $ids);
            //每次最多批量30个订单
            if(count($ids) > 30){
                throw new Exception('每次最多只能批量支付30个订单');
            }


            $newIds = [];
            if(isset($_COOKIE['not-pay'])){
                $notPay = $_COOKIE['not-pay'];
                $not    = explode(',', $notPay);
                foreach($ids as $id){
                    if(!in_array($id, $not)){
                        $newIds[] = $id;
                    }
                }
            }else{
                $newIds = $ids;
            }

            //根据选择的ID获取所有付款记录
            $orderPayDatas = $this->purchase_order_pay_model->findRecordeByIds($newIds);
            if(empty($orderPayDatas)){
                throw new Exception('采购单请款单表-没有找到数据');
            }

            $applicants      = [];
            $purchaseAccount = [];
            $list            = [];
            // 拍单号验证
            $reg_order = "/^[0-9]{10,25}$/";
            foreach($orderPayDatas as $m){
                $m = (object)$m;

                if($m->purchase_account != 'yibaisuperbuyers' and stripos($m->purchase_account, ':') !== false){// 根据子账号 获取主账号 @user Jolon 2019/05/17
                    $p_account           = explode(':', $m->purchase_account);
                    $m->purchase_account = isset($p_account[0]) ? $p_account[0] : '';
                }

                $applicants[]      = $m->applicant;
                $purchaseAccount[] = $m->purchase_account;
                $subData           = (array)$m;

                /*
                $supplier_name = $this->supplier_model->getSupplierNameBySupplierCode($m->supplier_code);
                //调用JAVA接口判断是否是跨境宝供应商
                if(!empty($supplier_name)){
                    
                    $is_cross_border_arr =$this->supplier_model->get_supplier_quota_inifo(array('seller_login_id'=>$supplier_name)); 
                    //JAVA接口返回数据是否正常
                    if(isset($is_cross_border_arr[0]) && empty($is_cross_border_arr[0])){
                        throw new Exception($is_cross_border_arr[1].'检查该供应商是否是否支持跨境支付');    
                    }
                    if(!isset($is_cross_border_arr['tap_date_str'])){
                        throw new Exception($m->requisition_number.'不是跨境宝供应商');
                    }
                }else{
                    throw new Exception($m->requisition_number.'找不到供应商');    
                }
                */
                //判断是跨境宝
                $is_cross_border = $this->supplier_model->is_cross_border($m->supplier_code);
                if(!$is_cross_border){
                    throw new Exception($m->requisition_number.'不是跨境宝供应商');
                }

                //判断是否为网采单是,进行支付，之后提示不支持(合同来源（1合同 2网络【默认】 3账期采购）)
                if($m->source != SOURCE_NETWORK_ORDER){
                    throw new Exception($m->requisition_number.'此单不是网采不支持付款，请查检');
                }
                //判断支付状态
                if($m->pay_status != PAY_WAITING_FINANCE_PAID){
                    throw new Exception($m->requisition_number.'此单不是可支付状态，请查检');
                }
                //判断请款类型
                if($m->pay_category == PURCHASE_PAY_CATEGORY_6 or $m->pay_category == PURCHASE_PAY_CATEGORY_5){
                    throw new Exception($m->pur_number.'采购单没有跨境交易，不允许使用跨境宝付款');
                }
                //判断验货验厂
                $errer = $this->Payment_order_pay_model->is_check_status($m->requisition_number);
                if(!empty($errer)){
                    throw new Exception($errer);
                }
                //拍单号(优先从采购单请款单表获取)
                $order_number = $m->pai_number;
                if(!$order_number){
                    /**@Table:pur_purchase_order_pay_type 中的 platform_order_number* */
                    $payTypeDatas = $this->orderPayType->get_one($m->pur_number);
                    if(empty($payTypeDatas)){
                        throw new Exception('没有找到对应的拍单号【'.$m->pur_number.'】，请查检');
                    }
                    $payTypeDatas = (object)$payTypeDatas;
                    $order_number = $payTypeDatas->pai_number;
                }

                //网采账号通过接口获取
                $orderAccount = trim($m->purchase_account);// 订单网采账号
                if($orderAccount){
                    $subData['buyer_account'] = $orderAccount;
                }else{
                    $subData['buyer_account'] = '未设置';
                }

                // 拍单号存在空格
                $order_number = trim($order_number);
                // 踢出拍单号异常的数据
                if(!preg_match($reg_order, $order_number)){
                    continue;
                }

                $subData['order_number'] = $order_number;
                /**@Table:pur_purchase_order_pay 中的 freight,discount * */
                // 修改 运费、优惠计算 方法 @author Jolon @date 2018-10-13 13:32
                $freight                   = !empty($m->freight) ? $m->freight : 0;
                $discount                  = !empty($m->discount) ? $m->discount : 0;
                $subData['order_freight']  = $freight;
                $subData['order_discount'] = $discount;

                /**@Table:pur_supplier 中的 supplier_name  以 supplier_code 供应商code关联* */
                $this->load->model('supplier/supplier_model', 'supplierModel');
                $suppliers                = $this->supplierModel->get_supplier_name_bycode($m->supplier_code, 'supplier_name');
                $subData['supplier_name'] = isset($suppliers['supplier_name']) ? $suppliers['supplier_name'] : '';// 供应商信息
                $subData['create_user_name'] = $m->create_user_name;
                $list[] = $subData;
            }

            //判断批量支付是否为同一个申请人(支付时申请人只能是同一个申请人)
            /*
            $res = is_same_data($applicants);
            if (!$res) {
                throw new Exception('只能批量支付同一个申请人的请款数据');
            }
            */
            if(empty($list)){
                throw new Exception('没有可支付的单，可能是这些单的拍单号异常');
            }
            /*
            if (count(array_unique($purchaseAccount)) > 1) {
                throw new Exception('只能批量支付同一个采购单号的请款数据');
            }
            */
            // 拉取1688数据
            $applicant   = $applicants[0]; // 申请人
            $_aliWhere   = ['user_id' => $applicant, 'account' => $purchaseAccount[0]];
            $accountData = $this->alibabaaccount->findOnes($_aliWhere);
            if(empty($accountData)){
                throw new Exception('申请人没有绑定开发者账户，请绑定后重试');
            }
            $accountData = (object)$accountData;

            //请求java接口，获取订单详情
            $order_num_arr     = array_column($list, 'order_number');//需支付订单集合
            $result_order_item = $this->aliorderapi->getListOrderDetail(null, $order_num_arr);

            $refund_status = [
                "waitselleragree" => '等待卖家同意',
                "refundsuccess" => '退款成功',
                "refundclose" => '退款关闭',
                "waitbuyermodify" => '待买家修改',
//                    "waitbuyersend" => '等待买家退货',
                "waitsellerreceive" => '等待卖家确认收货',
            ];

            $val['alibaba']['result']['aliRefundPrice'] = '';
            $val['alibaba']['result']['aliRefundStatus'] = '';
            $val['alibaba']['result']['aliRefundStatusCn'] = '';
            foreach($list as &$val){
                //请求数据组合
                $order_number_info = $result_order_item[$val['order_number']];

                if(isset($order_number_info['data']) && $order_number_info['code'] == 200){
                    $val['alibaba']['result'] = $order_number_info['data']['baseInfo'];
                }else{
                    $val['alibaba'] = '错误：'.json_encode($order_number_info['data']['msg']);
                    continue;
                }
                // 1688 退款信息
                $aRPrice = $this->aliorderapi->getListOrderRefund($val['order_number'], true);
                if(isset($aRPrice['code']) && $aRPrice['code'] == 200 && isset($aRPrice['data']) && is_array($aRPrice['data'])){
                    $r_status = '';
                    $r_status_cn = [];
                    $r_price = 0;
                    foreach ($aRPrice['data'] as $r_val){
                        $rf_status = $r_val['status'];
                        $refundPayment = (int)$r_val['refundPayment'];
                        $refundCarriage = (int)$r_val['refundCarriage'];
                        if($rf_status == 'refundsuccess'){
                            $r_price += $refundPayment + $refundCarriage;
                            $r_status = $rf_status;
                        }
                        $rw_status = isset($refund_status[$rf_status]) ? $refund_status[$rf_status].":".round($r_price/100, 3): "";
                        if($rw_status != '')$r_status_cn[] = $rw_status;
                    }
                    $val['alibaba']['result']['aliRefundPrice'] = round($r_price/ 100, 3);
                    $val['alibaba']['result']['aliRefundStatus']   = $r_status;
                    $val['alibaba']['result']['aliRefundStatusCn'] = implode(',', $r_status_cn);
                }

                // 采购系统内采购单取消未到货退款金额
                $orderRefund = $this->cancelReceiptModel->get_receipt_price_by_order($val['pur_number']);
                if($orderRefund){
                    $val['orderRefundPrice']    = array_sum(array_column($orderRefund, 'pay_price'));
                    $val['orderRefundStatus']   = end($orderRefund)['pay_status'];
                    $val['orderRefundStatusCn'] = getReceivePayStatus($val['orderRefundStatus']);
                }else{
                    $val['orderRefundPrice']    = null;
                    $val['orderRefundStatus']   = null;
                    $val['orderRefundStatusCn'] = null;
                }
            }
            //获取支付链接
            $pay_url_json = $this->aliorderapi->getOrderPayUrl(null, $order_num_arr);
            $pay_url_arr  = json_decode($pay_url_json, true);
            $pay_url      = isset($pay_url_arr['data']['payUrl']) ? $pay_url_arr['data']['payUrl'] : '';
            if(empty($pay_url)){
                throw new Exception(isset($pay_url_arr['msg']) ? $pay_url_arr['msg'] : '获取收银台支付链接异常');
            }
            //申请人名称
            $applicantName = getUserNameById($applicant);
            //返回批量支付数据((返回前端页面-确认付款))
            $this->send_data(['list' => $list, 'applicant' => $applicant, 'pay_url' => $pay_url, 'applicantName' => $applicantName], '1688在线批量付款指接口', true);
        }catch(Exception $e){
            $this->send_data(null, $e->getMessage(), false);
        }
    }
    
    /**
     * 宝付支付成功后出现网络超时不能更新采购数据定时任务跑
     * 每分钟执行一次
     * @author harvin
     * @date 2019/8/7
     * /Purchase_order_cashier_pay/baofoo_query
     */
    public function baofoo_query(){
        //获取redis 队列中数据
         $len= $this->rediss-> llenData('BAOFOOPAY');
        if($len<=0){
            exit('没有相关数据');
        }
        for ($i = 1; $i <= $len; $i++) {
            //取消列队尾部数据第一个
            $id = $this->rediss->rpopData('BAOFOOPAY');
            //判断有是否有待更新数据
            $temp = $this->Baofoo_fopay_model->get_baofoo_query($id);
            if ($temp['code']) { //有
                $key_ids="BAOFOOPAY".$id;
                $data = $this->rediss->getData($key_ids);
                //更新
                $reslut = $this->Baofoo_fopay_model->get_baofoo_query_list($data,$id);
                if (isset($reslut['code']) && $reslut['code'] == '200') {
                    echo $reslut['message'];
                } else { //记录失败重新写入队列
                    $this->rediss->lpushData('BAOFOOPAY', $id);
                }
            }else{
                echo $temp['msg'];
            }
        } 
    }


    /**
     * 重新获取1688订单状态
     * @author Jolon
     */
    public function refresh_ali_order_status(){
        $this->load->library('alibaba/AliOrderApi');
        $get = gp();
        $ids = $get['ids'];
        $ids = explode(',', $ids);
        if(empty($get['ids']) or empty($ids) or !is_array($ids)){
            $this->error_json("请求参数错误");
        }

        //根据选择的ID获取所有付款记录
        $orderPayDatas = $this->purchase_order_pay_model->findRecordeByIds($ids);


        if (empty($orderPayDatas)) {
            $this->error_json('采购单请款单表-没有找到数据');
        }
        if (count($orderPayDatas) != count($ids)) {
            $this->error_json('查找到的数据与请求数据不匹配');
        }


        $list = [];
        // 拍单号验证
        $reg_order = "/^[0-9]{10,25}$/";

        // 组建本地数据
        foreach ($orderPayDatas as $m) {
            //判断是否为网采单是,进行支付，之后提示不支持(合同来源（1合同 2网络【默认】 3账期采购）)
            if ($m['source'] != SOURCE_NETWORK_ORDER) {
                $this->error_json($m['pur_number'] .'此单不是网采不支持付款，请查检');
            }

            //拍单号(优先从采购单请款单表获取)
            $order_number = $m['pai_number'];
            if (!$order_number) {
                /**@Table:pur_purchase_order_pay_type 中的 platform_order_number* */
                $payTypeDatas = $this->orderPayType->get_one($m['pur_number']);
                if (empty($payTypeDatas)) {
                    $this->error_json('没有找到对应的拍单号【' . $m['pur_number'] . '】，请查检');
                }
                $order_number = $payTypeDatas['pai_number'];
            }

            // 拍单号存在空格
            $order_number = trim($order_number);
            // 踢出拍单号异常的数据
            if (!preg_match($reg_order, $order_number)) {
                $this->error_json('拍单号【' . $order_number . '】非常规拍单号，请查检');
            }

            $subData['id'] = $m['id'];
            $subData['pur_number'] = $m['pur_number'];
            $subData['order_number'] = $order_number;
            if($m['settlement_method'] == SUPPLIER_SETTLEMENT_CODE_TAP_DATE){
                $subData['settlement_method'] = '1688账期';
            }elseif($m['settlement_method'] == 10){
                $subData['settlement_method'] = '款到发货';
            }else{
                $this->error_json($m['pur_number'] .'结算方式错误，请查检');
            }

            $list[] = $subData;
        }

        if(empty($list)) $this->error_json('没有找到待处理的数据');


        $order_num_arr = array_column($list, 'order_number');//需支付订单集合
        $result_order_item = $this->aliorderapi->getListOrderDetail(null,$order_num_arr);

        foreach ($list as &$val) {
            $order_number_info = isset($result_order_item[$val['order_number']])?$result_order_item[$val['order_number']]:[];
            if (isset($order_number_info['data']) && $order_number_info['code']==200) {
                $val['status'] = $order_number_info['data']['baseInfo']['status'];
            } else {
                $this->error_json($order_number_info['msg']);
            }
        }

        $this->success_json($list);
    }

}