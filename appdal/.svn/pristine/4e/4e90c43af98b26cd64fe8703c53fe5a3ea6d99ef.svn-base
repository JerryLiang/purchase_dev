<?php
/**
 * 1688 订单操作控制器
 * User: Jolon
 * Date: 2019/03/20 10:00
 */
class Ali_order extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->helper('status_1688');

        $this->load->library('alibaba/AliProductApi');
        $this->load->library('alibaba/AliOrderApi');
        $this->load->library('alibaba/AliSupplierApi');
        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('Ali_product_model');
        $this->load->model('Ali_order_model');
        $this->load->model('purchase_suggest/Purchase_suggest_model');
        $this->load->model('product/Product_model');
        $this->load->model('supplier_joint_model');
    }

    /**
     * 1688下单-预览数据（单个）
     */
    public function one_key_order_preview(){
        $purchase_number     = $this->input->get_post('purchase_number');
        $ali_sku_amount      = $this->input->get_post('ali_sku_amount');// 根据此字段的值 判断是否是重新预下单
        if(empty($purchase_number)) $this->error_json("采购单号缺失");
        $isDrawbacks = $this->Purchase_order_model->getPurchaseIsdrawback([$purchase_number]);
        if(NULL != $isDrawbacks ){

            $this->error_json("采购单:".$isDrawbacks.",是退税，不可点击");
        }

        if($ali_sku_amount and is_json($ali_sku_amount)) $ali_sku_amount = json_decode($ali_sku_amount,true);// 预下单失败的时候，可以修改数量重新预下单

        $return = $this->Ali_order_model->order_preview($purchase_number,$ali_sku_amount);

        if($return['code']){
            $this->success_json($return['data']);
        }else{
            $this->error_json($return['message']);
        }
    }

    /**
     * 1688自助下单-预览（单个和批量，如果预览成功则会自动提交下单）
     */
    public function batch_one_key_order_preview(){
        set_time_limit(0);
        $purchase_numbers     = $this->input->get_post('purchase_numbers');
        $ali_sku_amount_list  = $this->input->get_post('ali_sku_amount');// 根据此字段的值 判断是否是重新预下单
        if(empty($purchase_numbers) or !is_array($purchase_numbers)) $this->error_json("采购单号缺失");
        if(!empty($ali_sku_amount_list) and !is_json($ali_sku_amount_list)) $this->error_json("预下单数量缺失");

        $purchase_numbers = array_unique($purchase_numbers);
        if(count($purchase_numbers) > 50) $this->error_json('请勿超过50个采购单');
        if($ali_sku_amount_list and is_json($ali_sku_amount_list)) $ali_sku_amount_list = json_decode($ali_sku_amount_list,true);// 预下单失败的时候，可以修改数量重新预下单

//        // 判断新品,采购单维度, 获取采购单下的备货单
//        $demandNumbers =  $this->Purchase_suggest_model->getSuggestDemand($purchase_numbers,NULL);
//        $demandNumbers = array_column( $demandNumbers,"demand_number");
//        $demandLocks = $this->Purchase_suggest_model->getDemandLock($demandNumbers);
//        if(!empty($demandLocks)){
//
//            $skusLock = implode(",",array_column($demandLocks,"demand_number"));
//            $this->error_data_json("备货单:".$skusLock." 锁单中");
//        }

        $isDrawbacks = $this->Purchase_order_model->getPurchaseIsdrawback($purchase_numbers);
        if(NULL != $isDrawbacks ){

            $this->error_json("采购单:".$isDrawbacks.",是退税，不可点击");
        }

        // 缓存有效期，过长导致占用很久才能释放（1个备货单 +1 秒锁定时间，最长锁定180秒）
        $ttl_session_key          = 2 + count($purchase_numbers) * 1;
        if($ttl_session_key > 180){ $ttl_session_key = 180; }

        // 设置缓存
        foreach($purchase_numbers as $key => $number){
            $session_key = 'one_key_order_' . $number;
            if (!$this->rediss->getData($session_key)) {
                $this->rediss->setData($session_key, '1', $ttl_session_key); //设置缓存和有效时间
            } else {
                unset($purchase_numbers[$key]);// 占用的订单不能预览，避免重复
            }
        }
        if(empty($purchase_numbers)) $this->error_json('您选中的订单已全部被占用，无法生成预览数据，请稍后处理...');

        $success_list   = null;
        $error_list     = null;
        $order_list     = null;
        foreach($purchase_numbers as $purchase_number){
            if($this->Ali_order_model->checkPurOrderExecuted($purchase_number)){
                $success_list[] = "采购单[$purchase_number]已经生成了1688订单，请勿重复下单";
                continue;
            }

            $ali_sku_amount = isset($ali_sku_amount_list[$purchase_number])?$ali_sku_amount_list[$purchase_number]:[];
            $return         = $this->Ali_order_model->order_preview($purchase_number,$ali_sku_amount);

            if($return['code'] === false){
                $error_list[] = $purchase_number."：".$return['message'];
            }else{
                if(isset($return['data']['ali_order_data']['passed_preview']) and $return['data']['ali_order_data']['passed_preview'] == 1){
                    // 预览成功的直接下单到1688，下单失败则重新预览
                    $result = $this->Ali_order_model->one_key_order_not_preview($purchase_number);
                    if($result['code']){

                        // 下单成功

                        $skuData = $this->Purchase_suggest_model->getSuggestDemand($purchase_number);
                        $skuDatas = array_column( $skuData,"sku");
                        $this->Product_model->updateProductNew($skuDatas);

                        $success_list[] = $purchase_number.' 1688下单成功';
                    }else{
                        $order_list[] = $return['data'];// 预览失败的，渲染到页面用户操作
                        $error_list[] = $purchase_number."：".$result['message'];
                    }
                }else{
                    $order_list[] = $return['data'];// 预览失败的，渲染到页面用户操作
                    $error_list[] = $purchase_number."：".$return['message'];
                }
            }
        }

        // 释放缓存
        foreach($purchase_numbers as $number){
            $session_key = 'one_key_order_' . $number;
            $this->rediss->deleteData($session_key);
        }

        $data = [
            'success_list'  => $success_list,
            'error_list'    => $error_list,
            'order_list'    => $order_list,
        ];
        $this->success_json($data);

    }

    /**
     * 1688下单-确认提交到1688下单
     */
    public function do_one_key_order(){
        $purchase_number  = $this->input->get_post('purchase_number');//采购单号
        $sku_amount       = $this->input->get_post('sku_amount');//采购数量
        $ali_sku_amount   = $this->input->get_post('ali_sku_amount');//采购数量
        $ali_ratio_list   = $this->input->get_post('ali_ratio_list');//单位对应关系
        $order_discount   = $this->input->get_post('order_discount');//优惠金额
        $order_freight    = $this->input->get_post('order_freight');//运费
        $order_process_cost = $this->input->get_post('process_cost');//加工费
        $buyer_note       = $this->input->get_post('buyer_note');//买家留言
        $trader_method    = $this->input->get_post('trader_method');//交易方式
        $purchase_account = $this->input->get_post('purchase_account');//采购账号
        $address_id       = $this->input->get_post('address_id');
        $modify_remark    = $this->input->get_post('modify_remark');
        $freight_note     = $this->input->get_post('freight_note');//运费说明
        $account_type     = $this->input->get_post('account_type');//结算方式
        $is_force_submit  = $this->input->get_post('is_force_submit');//是否强制提交
        //20190702
        $full_name    = $this->input->get_post('contacts');   //收货人姓名
        $mobile_phone = $this->input->get_post('contact_number');   //手机
        $phone        = $this->input->get_post('phone');   //电话
        $area         = $this->input->get_post('area'); //区
        $address      = $this->input->get_post('address'); //街道地址
        $province     = $this->input->get_post('province_text');//省
        $town         = $this->input->get_post('town_text');//镇
        $city         = $this->input->get_post('city_text');//城市
        $post_code    = $this->input->get_post('post_code');//邮政编码
        $note         = $this->input->get_post('note');//备注
        $address      .= $note;//拼接收货地址

        if(empty($purchase_number)) $this->error_json("采购单号缺失");
        if(empty($sku_amount) or !is_json($sku_amount)) $this->error_json("采购数量缺失或不是JSON");
        if(empty($ali_sku_amount) or !is_json($ali_sku_amount)) $this->error_json("下单数量缺失或不是JSON");
        if(!empty($ali_ratio_list) and !is_json($ali_ratio_list)) $this->error_json("单位对应关系数据不是JSON");// 可以为空
        if($trader_method == '') $this->error_json("交易方式缺失");
        if(empty($purchase_account)) $this->error_json("采购账号缺失");
        if(empty($address_id)) $address_id=0;
        if(empty($modify_remark)) $this->error_json("备注信息缺失");
        if(empty($full_name)) $this->error_json("收件货人缺失");
        if(empty($mobile_phone)) $this->error_json("收货联系电话为空");
        if(!empty($account_type) && $account_type==20){
            if($trader_method !='period'){
                $this->error_json($purchase_number.":的供应商是1688账期交易，交易方式必须选择“账期交易”");
            }
        }

        $check_result = $this->check_enable_order($is_force_submit,$purchase_number,$trader_method);// 验证订单是否可以1688下单
        if(empty($check_result['code'])){
            $this->error_data_json($check_result['data'],$check_result['message']);
        }

        $data = [
            'purchase_number'  => $purchase_number,
            'order_discount'   => floatval($order_discount),
            'order_freight'    => floatval($order_freight),
            'order_process_cost' => floatval($order_process_cost),
            'sku_amount'       => json_decode($sku_amount,true),
            'ali_sku_amount'   => json_decode($ali_sku_amount,true),
            'ali_ratio_list'   => json_decode($ali_ratio_list,true),
            'buyer_note'       => $buyer_note,
            'trader_method'    => $trader_method,
            'purchase_account' => $purchase_account,
            'address_id'       => $address_id,
            'modify_remark'    => json_decode($modify_remark,true),
            'freight_note'     => !empty($freight_note)?$freight_note:'',
            'full_name'=> $full_name, //收货人姓名
            'mobile'=> $mobile_phone, //手机
            'phone'=> $phone, //电话
            'city'=> $city,
            'area'=> $area,
            'province'=> $province,
            'town' => $town,
            'address'=> $address, //街道地址
            'post_code'=> $post_code,
        ];

        // 创建订单信息
        $result = $this->Ali_order_model->do_one_key_order($data);
        if($result['code']){
//            // 下单成功
//
//            $skuData = $this->Purchase_suggest_model->getSuggestDemand($purchase_number);
//            $skuDatas = array_column( $skuData,"sku");
//            $this->Product_model->updateProductNew($skuDatas);

            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }

    }

    /**
     * 批量检测1688商品是否有效
     * 需求号：28939
     * @author yefanli
     */
    public function verify_ali_product_effective()
    {
        $sku = $this->input->get_post('sku'); // sku
        $spid = $this->input->get_post('product_id'); // 阿里产品id
        if(empty($sku))$this->error_json("SKU不能为空!");
        $product_id = [];
        $sku_data = [];
        $res = [];
        if(!empty($sku) && is_array($sku)){
            $sku_data = $this->Ali_order_model->get_verify_ali_product($sku);
            if(count($sku_data) > 0){
                foreach ($sku_data as $val){
                    $product_id[] = $val['product_id'];
                }
            }
        }elseif (!empty($spid) && is_array($spid)){
            $product_id = $spid;
        }

        if(empty($product_id) || empty($sku_data))$this->error_json("没有相应的产品ID!");
        $send = $this->aliproductapi->checkProductWhetherOnline($product_id);
        if(isset($send['online_product']) && !empty($send['online_product'])){
            $online = array_keys($send['online_product']);
            foreach ($sku_data as $val){
                if(in_array($val['product_id'], $online))$res[] = "SKU:".$val['sku']."产品已下架或链接失效。";
            }
            if(count($res) == 0)$this->error_json("验证已完成!");
            $this->success_json($res);
        }
        $this->error_json("验证已完成!");
    }


    /**
     * 1688下单-确认提交到1688下单-下单（单个和批量）
     */
    public function batch_do_one_key_order(){
        set_time_limit(0);
        $error_list           = [];
        $success_list         = [];
        $purchase_number_list = [];
        $post_order_data_list = $this->input->post('order_data_list');//采购单数据

        if(empty($post_order_data_list) or !is_json($post_order_data_list)) $this->error_json("采购单下单数据缺失或者数据格式错误");

        $post_order_data_list = json_decode($post_order_data_list,true);
        foreach($post_order_data_list as $order_value){
            $purchase_number  = isset($order_value['purchase_number'])?$order_value['purchase_number']:'';//采购单号
            if(empty($purchase_number)) $this->error_json('采购单号缺失');
            $purchase_number_list[] = $purchase_number;
        }

        // 设置缓存
        foreach($purchase_number_list as $key => $number){
            $session_key = 'one_key_order_' . $number;
            if (!$this->rediss->getData($session_key)) {
                $this->rediss->setData($session_key, '1', 600); //设置缓存和有效时间
            } else {
                $this->error_json($number.'订单已占用，可能多方同时下单，请稍后再操作');
            }
        }

        $this->load->library('alibaba/AliAccount');
        // 获取仓库地址信息
        $warehouse_address_list = $this->warehouse_model->get_warehouse_address();
        $warehouse_address_list = arrayKeyToColumn($warehouse_address_list,'warehouse_code');

        foreach($post_order_data_list as $order_value){
            $purchase_number  = isset($order_value['purchase_number'])?$order_value['purchase_number']:'';//采购单号
            $purchase_order_info = $this->Purchase_order_model->get_one($purchase_number);
            $userInfo            = $this->Purchase_order_model->get_access_purchaser_information($purchase_number);
            $user_ali_account    = $this->aliaccount->getSubAccountOneByUserId($purchase_order_info['buyer_id']);

            try{
                if(empty($purchase_order_info) || empty($purchase_order_info['items_list'])){
                    throw new Exception('采购单或采购单明细缺失');
                }
                if(empty($user_ali_account)){
                    throw new Exception('采购员未配置采购账号');
                }
                $items_list       = $purchase_order_info['items_list'];

                // 用户提交的参数
                $ali_sku_amount   = isset($order_value['ali_sku_amount'])?$order_value['ali_sku_amount']:'';//采购数量
                $ali_ratio_list   = isset($order_value['ali_ratio_list'])?$order_value['ali_ratio_list']:'';//单位对应关系
                $order_discount   = isset($order_value['order_discount'])?$order_value['order_discount']:'';//优惠金额
                $order_freight    = isset($order_value['order_freight'])?$order_value['order_freight']:'';//运费
                $order_process_cost = isset($order_value['order_process_cost'])?$order_value['order_process_cost']:'';//加工费
                $buyer_note       = isset($order_value['buyer_note'])?$order_value['buyer_note']:'';//买家留言
                $trader_method    = isset($order_value['trader_method'])?$order_value['trader_method']:'';//交易方式
                $freight_note     = isset($order_value['freight_note'])?$order_value['freight_note']:'';//运费说明
                $is_force_submit  = isset($order_value['is_force_submit'])?$order_value['is_force_submit']:'';//是否强制提交
                //20190702
                $full_name    = $userInfo['user_name'].' '.(isset($order_value['contacts'])?$order_value['contacts']:'');   //收货人姓名
                $mobile_phone = isset($order_value['contact_number'])?$order_value['contact_number']:'';//手机

                if(!isset($warehouse_address_list[$purchase_order_info['warehouse_code']]) or empty($warehouse_address_list[$purchase_order_info['warehouse_code']])){
                    throw new Exception('仓库收货地址错误');
                }

                // 收货地址信息
                $warehouse_address = $warehouse_address_list[$purchase_order_info['warehouse_code']];

                $province     = isset($warehouse_address['province_text'])?$warehouse_address['province_text']:'';//省
                $city         = isset($warehouse_address['city_text'])?$warehouse_address['city_text']:'';//城市
                $area         = isset($warehouse_address['area'])?$warehouse_address['area']:'';//区
                $town         = isset($warehouse_address['town_text'])?$warehouse_address['town_text']:'';//镇
                $address      = $purchase_number.' '.(isset($warehouse_address['address'])?$warehouse_address['address']:'');//街道地址
                $post_code    = isset($warehouse_address['post_code'])?$warehouse_address['post_code']:'';//邮政编码

                $note         = isset($order_value['note'])?$order_value['note']:'';//收货地址备注
                $address      .= $note;//拼接收货地址

                // 系统级参数
                $sku_amount       = json_encode(array_column($items_list,'confirm_amount','sku'));;//采购数量
                $purchase_account = isset($user_ali_account['account'])?$user_ali_account['account']:'';//采购账号
                $account_type     = $purchase_order_info['account_type'];//结算方式

                $error_msg = '';
                if(empty($purchase_number)) $error_msg = '采购单号缺失';
                if(empty($sku_amount) or !is_json($sku_amount)) $error_msg ='采购数量缺失或不是JSON';
                if(empty($ali_sku_amount) or !is_json($ali_sku_amount)) $error_msg ='下单数量缺失或不是JSON';
                if(!empty($ali_ratio_list) and !is_json($ali_ratio_list)) $error_msg ='单位对应关系数据不是JSON';
                if($trader_method == '') $error_msg ='交易方式缺失';
                if(empty($purchase_account)) $error_msg ='采购账号缺失';
                if(empty($full_name)) $error_msg ='收件货人缺失';
                if(empty($mobile_phone))  $error_msg ='收货联系电话为空';
                if(!empty($account_type) && $account_type == SUPPLIER_SETTLEMENT_CODE_TAP_DATE){
                    if($trader_method !='period'){
                        $error_msg ="：供应商是1688账期交易，交易方式必须选择“账期交易”";
                    }
                }
                if($error_msg){
                    throw new Exception($error_msg);
                }

                $check_result = $this->check_enable_order($is_force_submit,$purchase_number,$trader_method);// 验证订单是否可以1688下单
                if(empty($check_result['code'])){
                    throw new Exception($check_result['message']);
                }

                $data = [
                    'purchase_number'  => $purchase_number,
                    'order_discount'   => floatval($order_discount),
                    'order_freight'    => floatval($order_freight),
                    'order_process_cost' => floatval($order_process_cost),
                    'sku_amount'       => json_decode($sku_amount, true),
                    'ali_sku_amount'   => json_decode($ali_sku_amount, true),
                    'ali_ratio_list'   => json_decode($ali_ratio_list, true),
                    'buyer_note'       => $buyer_note,
                    'trader_method'    => $trader_method,
                    'purchase_account' => $purchase_account,
                    'address_id'       => 0,// 不传该值
                    'modify_remark'    => '',
                    'freight_note'     => !empty($freight_note) ? $freight_note : '',
                    'full_name'        => $full_name, //收货人姓名
                    'mobile'           => $mobile_phone, //手机
                    'phone'            => '', //电话
                    'city'             => $city,
                    'area'             => $area,
                    'province'         => $province,
                    'town'             => $town,
                    'address'          => $address, //街道地址
                    'post_code'        => $post_code,
                ];

                // 创建订单信息
                $result = $this->Ali_order_model->do_one_key_order($data);
                if(empty($result['code'])){
                    throw new Exception($result['message']);
                }

                $success_list[] = $purchase_number;

            }catch(Exception $exception){
                $error_list[] = $purchase_number.'：'.$exception->getMessage();
            }

        }

        $data = [
            'success_list' => $success_list,
            'error_list'   => $error_list
        ];

        // 释放缓存
        foreach($purchase_number_list as $number){
            $session_key = 'one_key_order_' . $number;
            $this->rediss->deleteData($session_key);
        }

        $total   = count($purchase_number_list);
        $success = count($success_list);
        $error   = count($error_list);
        $message = "总个数：{$total}，成功个数：{$success}，失败个数：$error";

        $this->success_json($data,null,$message);
    }


    /**
     * 验证采购单是否可以 1688 下单
     * @param string $is_force_submit  是否强制提交（=1 为强制提交，跳过某些数据异常的判断）
     * @param string $purchase_number  采购单号
     * @param string $trader_method    交易方式
     * @return array
     */
    public function check_enable_order($is_force_submit,$purchase_number,$trader_method){
        $return = ['code' => false,'message' => '','data' => []];

        // 拦截非法订单状态
        $purchase_order_info = $this->Purchase_order_model->get_one($purchase_number);

        if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])){
            $return['message'] = '采购单或采购单明细缺失';
            return $return;
        }

        if($purchase_order_info['is_drawback'] == PURCHASE_IS_DRAWBACK_Y){
            $return['message'] = '采购单是否退税=是，不允许进行1688下单';
            return $return;
        }
        $demand  = $this->Ali_order_model->select_demand_is_drawback($purchase_order_info['purchase_number']);
        if(!empty($demand)){
            $return['message'] = '备货单号'.$demand.'，是退税，不允许进行1688下单';
            return $return;
        }
        if($purchase_order_info['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){
            if ($purchase_order_info['is_ali_abnormal'] != 1 ){
                $return['message'] = '只有【等待采购询价】状态才能【1688】下单';
                return $return;
            }
            if ($purchase_order_info['is_ali_abnormal'] == 1 && !in_array($purchase_order_info['purchase_order_status'],[PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT])){
                $return['message'] = '1688异常订单,只有【等待到货】【信息修改驳回】状态才能【1688】下单';
                return $return;
            }

            if ($purchase_order_info['is_ali_abnormal'] == 1 && !in_array($purchase_order_info['pay_status'],[PAY_UNPAID_STATUS,PAY_MANAGER_REJECT,PAY_FINANCE_REJECT])){
                $return['message'] = '1688异常订单,只有付款状态为【未申请付款】【经理驳回】【财务驳回】状态才能【1688】下单';
                return $return;
            }
        }
        $this->load->model('supplier/Supplier_payment_info_model');
        $supplier_payment_info = $this->Supplier_payment_info_model->check_payment_info($purchase_order_info['supplier_code'],$purchase_order_info['is_drawback'],$purchase_order_info['purchase_type_id']);

        if((empty($is_force_submit) or $is_force_submit != 1) and $trader_method == 'fxassure'){// 担保交易时验证是否存在账期的结算方式（强制提交时无需验证）
            if($supplier_payment_info['supplier_settlement'] == SUPPLIER_SETTLEMENT_CODE_TAP_DATE){

                $return['data']['is_ali_trader_method_abnormal'] = 1;
                $return['message'] = "供应商 {$purchase_order_info['supplier_code']} 的结算方式含有1688账期，\n您选择的是非账期交易，请确认是否提交？";
                return $return;
            }
        }

        if($supplier_payment_info['payment_method'] != PURCHASE_PAY_TYPE_ALIPAY){
            $return['message'] = '采购单号:'.$purchase_order_info['purchase_number'].'供应商无支付宝支付方式，不支持1688下单';
            return $return;
        }

        $return['code'] = true;
        return $return;
    }

    /**
     * 查询 1688订单的最新 总价
     */
    public function get_ali_order_newest_price(){
        $purchase_number     = $this->input->get_post('purchase_number');
        $result              = $this->Ali_order_model->get_ali_order_newest_price($purchase_number);
        if($result['code']){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 1688下单确认-预览数据
     */
    public function one_key_order_confirm(){
        $purchase_number     = $this->input->get_post('purchase_number');
        $isDrawbacks = $this->Purchase_order_model->getPurchaseIsdrawback([$purchase_number]);
        if(empty($purchase_number)) $this->error_json("采购单号缺失");

        $local_order = $this->Ali_order_model->check_have_order($purchase_number);
        if(empty($local_order)){
            $this->error_json('该采购单还未1688下单，请先【1688下单】');
        }
        $result = $this->Ali_order_model->get_preview_order_info($purchase_number,true);
        if($result['code']){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['message']);
        }

    }

    /**
     * 1688下单确认-确认提交采购经理审核
     */
    public function one_key_order_submit(){
        $post_data = $_REQUEST;
        $purchase_number = $this->input->get_post('purchase_number');//采购单号
        $order_discount  = $this->input->get_post('order_discount');//优惠金额
        $order_freight   = $this->input->get_post('order_freight');//运费
        $order_process_cost = $this->input->get_post('order_process_cost');//加工费
        $pay_type        = $this->input->get_post('pay_type');//支付方式
        $purchasing_order_audit    = $this->input->get_post('purchasing_order_audit');//是否退送蓝凌
        $plan_product_arrive_time  = $this->input->get_post('plan_product_arrive_time');//预计到货时间
        $freight_note = $this->input->get_post('freight_note');//运费说明
        $freight_sku      = isset($post_data['freight_sku'])?$post_data['freight_sku']:[];// 每个sku的运费 数组
        $discount_sku     = isset($post_data['discount_sku'])?$post_data['discount_sku']:[];// 每个sku的优惠 数组
        $process_cost_sku     = isset($post_data['process_cost_sku'])?$post_data['process_cost_sku']:[];// 每个sku的加工费 数组

        // 1688 下单判断是否为新品
        // 判断采购单对应的备货单是否在锁单状态
        // 判断新品,采购单维度, 获取采购单下的备货单
        $demandNumbers =  $this->Purchase_suggest_model->getSuggestDemand($purchase_number,NULL);
        $demandNumbers = array_column( $demandNumbers,"demand_number");
        $demandLocks = $this->Purchase_suggest_model->getDemandLock($demandNumbers);

        if(!empty($demandLocks)){

            $skusLock = implode(",",array_column($demandLocks,"demand_number"));
            $errroMsg = "采购单".$purchase_number."，对应的备货单号:".$skusLock."，处于锁单中。请到备货单（实单-锁单）模块，解锁!";
            $this->error_json($errroMsg);
        }

        if(empty($purchase_number)) $this->error_json("采购单号缺失");
       // if(empty($plan_product_arrive_time) or $plan_product_arrive_time == '0000-00-00 00:00:00') $this->error_json("预计到货时间缺失");
        if (empty($freight_sku)) $this->error_json('备货单运费必填');
        if (empty($discount_sku)) $this->error_json('备货单优惠必填');
        if (empty($process_cost_sku)) $this->error_json('备货单加工费必填');

        if (bccomp($order_discount,array_sum($discount_sku),3) !=0 ) $this->error_json('总优惠金额与备货单分摊优惠总和不一致');
        if (bccomp($order_freight,array_sum($freight_sku),3) !=0 ) $this->error_json('总运费金额与备货单分摊运费总和不一致');
        if (bccomp($order_process_cost,array_sum($process_cost_sku),3) !=0 ) $this->error_json('总加工费金额与备货单分摊加工费总和不一致');

        if (!is_two_decimal($order_discount) || !is_two_decimal($order_freight) || !is_two_decimal($order_process_cost)){
            $this->error_json("总运费,总优惠,总加工费小数最多只能为两位");
        }

        foreach ($freight_sku as $sku => $value){
            if (!is_two_decimal($value) || !is_two_decimal($discount_sku[$sku]) || !is_two_decimal($process_cost_sku[$sku])){
                $this->error_json("采购单明细[$purchase_number - $sku]运费,优惠,加工费小数最多只能为两位");
            }
        }

        // 拦截非法订单状态
        $purchase_order_info = $this->Purchase_order_model->get_one($purchase_number);
        if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])){
            $this->error_json('采购单或采购单明细缺失');
        }
        if($purchase_order_info['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){
            if ($purchase_order_info['is_ali_abnormal'] != 1 ){
                $this->error_json('只有【等待采购询价】状态才能【1688】下单');
            }
            if ($purchase_order_info['is_ali_abnormal'] == 1 && !in_array($purchase_order_info['purchase_order_status'],[PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT])){
                $this->error_json('1688异常订单,只有【等待到货】【信息修改驳回】状态才能【1688】下单');
            }
            if ($purchase_order_info['is_ali_abnormal'] == 1){
                if(($check_res2 = $this->Purchase_order_model->check_pay_status_able_change($purchase_order_info['pay_status'])) !== true){
                    $this->error_json($check_res2);
                }
            }
        }

        $data = [
            'purchase_number'  => $purchase_number,
            'order_discount'   => floatval($order_discount),
            'order_freight'    => floatval($order_freight),
            'order_process_cost' => floatval($order_process_cost),
            'purchasing_order_audit' => $purchasing_order_audit,
            'pay_type' => !empty($pay_type)?$pay_type:PURCHASE_PAY_TYPE_ALIPAY,
            'freight_note' => $freight_note,
            'freight_sku' => $freight_sku,
            'discount_sku' => $discount_sku,
            'process_cost_sku' => $process_cost_sku,
            'is_freight' => PURCHASE_FREIGHT_PAYMENT_A,// 1688下单默认甲方支付
        ];

        $result = $this->Ali_order_model->one_key_order_submit($data, true, true);
        if($result['code']){

            $this->success_json($result['data']);
        }else{
            $this->error_json($result['message']);
        }
    }


    /**
     * 1688自助下单-自动下单（不会预览 直接下单到1688平台）
     */
    public function auto_one_key_order_submit(){
        set_time_limit(0);
        $purchase_numbers = $this->input->get_post('purchase_numbers');//采购单号
        if(empty($purchase_numbers)) $this->error_json("采购单号缺失");

        $error_list = [];
        $success_list = [];

        $purchase_numbers = array_unique($purchase_numbers);
        if(count($purchase_numbers) > 50) $this->success_json(['success_list' => [],'error_list' => ['请勿超过50个采购单']],null,'请勿超过50个采购单');

        foreach($purchase_numbers as $purchase_number){
            // 验证 是否被占用
            $session_key = 'one_key_order_' . $purchase_number;
            if (!$this->rediss->getData($session_key)) {
                $this->rediss->setData($session_key, '1', 600); //设置缓存和有效时间
            } else {
                $error_list[] = $purchase_number.' 可能多方同时下单';
                continue;
            }

            // 创建订单信息
            $result = $this->Ali_order_model->one_key_order_not_preview($purchase_number);
            if($result['code']){
                $success_lis[] = $purchase_number;
            }else{
                $error_list[] = $result['message'];
            }
        }

        $data = [
            'success_list' => $success_list,
            'error_list'   => $error_list
        ];

        // 释放缓存
        foreach($purchase_numbers as $number){
            $session_key = 'one_key_order_' . $number;
            $this->rediss->deleteData($session_key);
        }

        $total   = count($purchase_numbers);
        $success = count($success_list);
        $error   = count($error_list);
        $message = "总个数：{$total}，成功个数：{$success}，失败个数：$error";
        
        $this->success_json($data,null,$message);
    }

    /***
     * 1688 下单时收货地址
     */
    public function update_ali_receiving_address(){

        $warehouse_code = $this->input->get_post('warehouse_code');//仓库编码
        $contacts = $this->input->get_post('contacts');//收货人
        $contact_number = $this->input->get_post('contact_number');//收货联系人

        if(empty($warehouse_code)) $this->error_json("仓库编码为空不允许编辑");
        if(empty($contacts)) $this->error_json("收货人为空不允许编辑");
        if(empty($contact_number)) $this->error_json("收货联系人为空不允许编辑");

        $result = $this->Warehouse_model->updateWarehouseAddress($warehouse_code,$contacts,$contact_number);
        if($result){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 查询仓库收货信息
     */
    public function get_ali_receiving_address(){
        $warehouse_code = $this->input->get_post('warehouse_code');//仓库编码
        if(empty($warehouse_code)) $this->error_json("仓库编码不允许为空");
        $result = $this->Warehouse_model->get_warehouse_address($warehouse_code);
        if(!empty($result)){
            $this->success_json($result);
        }else{
            $this->error_json($result['message']);
        }
    }

    /***
     * 取消1688订单
     */
    public function get_cancel_ali_order(){
        $purchase_number    = $this->input->get_post('purchase_number');
        $pai_number         = $this->input->get_post('pai_number');
        $note               = $this->input->get_post('note');
        if(empty($purchase_number)) $this->error_json("采购订单号不允许为空");
        if(empty($pai_number))      $this->error_json("拍单号不允许为空");
        if(empty($note))            $this->error_json("取消订单备注不为空");
        $data = array(
            'purchase_number' => $purchase_number,
            'note'            => $note,
            'pai_number'      => $pai_number,
        );
        $purchase_order_info = $this->Purchase_order_model->get_one($purchase_number);

        if(empty($purchase_order_info) or empty($purchase_order_info['items_list'])){
            $this->error_json('采购单或采购单明细缺失');
        }
        if($purchase_order_info['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE) {
            if ($purchase_order_info['is_ali_abnormal'] != 1) {
                $return['message'] = '只有【等待采购询价】状态才能【1688】取消';
                return $return;
            }
        }
        $result = $this->Ali_order_model->cancel_ali_order($data);
        if($result['code']){
            $this->success_json($result);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 1688 刷新订单金额（支持 指定采购单号 和 采购单页面查询条件）
     * @author Jolon
     */
    public function refresh_order_price(){
        set_time_limit(0);
        $purchase_number_list    = $this->input->get_post('purchase_numbers');
        if($purchase_number_list){
            if(!is_array($purchase_number_list))
                $purchase_number_list = explode(',',$purchase_number_list);

        }else{
            // 读取缓存的查询SQL
            $new_get_list_querySql = $this->rediss->getData(md5(getActiveUserId().'-new_get_list'));
            if(empty($new_get_list_querySql)) $this->error_json("缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $new_get_list_querySql = base64_decode($new_get_list_querySql);

            // 截取第一个FROM 和 最后一个GROUP BY 之间的字符串
            $new_get_list_querySql = preg_replace("/(LIMIT)[\w\W]+(\))/",')',$new_get_list_querySql);
            // 在付款状态=未申请付款、驳回（经理驳回、供应链总监驳回、财务驳回、财务主管驳回、财务总监驳回、总经办驳回）这些付款状态下可以点击
            $new_get_list_querySql = "SELECT `ppo`.`purchase_number`
                 FROM pur_purchase_order AS ppo
                 LEFT JOIN pur_purchase_order_items AS poi ON poi.purchase_number=ppo.purchase_number
                 WHERE poi.id IN (".$new_get_list_querySql." ) 
                 AND ppo.pay_status IN(10,21,26,31,64,65,66,67) AND ppo.is_ali_order=1 
                 GROUP BY `ppo`.`purchase_number`";

            $purchase_number_list  = $this->Ali_order_model->purchase_db->query($new_get_list_querySql)->result_array();
            $purchase_number_list  = array_column($purchase_number_list,'purchase_number');

        }
        if(empty($purchase_number_list)) $this->error_json('没有获取到待【1688刷新金额】的数据，请确认操作');

        $result = $this->Ali_order_model->refresh_order_price($purchase_number_list);
        if($result['code']){
            $total_count    = count($purchase_number_list);
            $success_count  = count($result['data']['success_list']);
            $error_count    = count($result['data']['error_list']);
            $error_list     = $result['data']['error_list'];

            $message = "刷新操作总数：{$total_count} ，成功个数：{$success_count}，失败个数：{$error_count}";

            $this->success_json($error_list,null,$message);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 批量 1688批量下单确认 - 获取预览数据
     * @author Jolon
     */
    public function ali_batch_edit_order(){
        $purchase_numbers = $this->input->post_get('purchase_numbers'); //勾选数据
        if(empty($purchase_numbers)){
            $this->error_json('请勾选数据');
        }
        if(!is_array($purchase_numbers)){
            $this->error_json('数据格式不合法[必须是数组]');
        }

        $purchase_numbers = array_unique($purchase_numbers);
        $this->load->model('purchase/Purchase_order_transport_model');
        $this->load->model('finance/Purchase_order_pay_type_model');
        $this->load->model('supplier/Supplier_model');
        $this->load->model('supplier/Supplier_payment_info_model');
        $this->load->model("supplier/Supplier_settlement_model");
        $this->Ali_order_model->refresh_order_price($purchase_numbers);

        $freightPaymentList    = getFreightPayment();
        $purchaseOrderInfoList = [];
        foreach($purchase_numbers as $purchase_number){
            $orderInfo          = $this->Purchase_order_model->get_one($purchase_number);
            $orderPayTypeInfo   = $this->Purchase_order_pay_type_model->get_one($purchase_number);
            $localAli           = $this->Ali_order_model->get_local_ali_order($purchase_number);
            if(empty($localAli)){
                $this->error_json($purchase_number.' 还没有下单，请先1688下单或1688自助下单');
            }
            if(empty($orderPayTypeInfo)){
                $this->error_json($purchase_number.' 采购单确单信息缺失');
            }
            if($orderInfo['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){
                $this->error_json($purchase_number.' 只能待采购询价状态下才需要提交审核，请勿重复操作');
            }
            if($orderInfo['is_ali_order'] == 0){
                $this->error_json($purchase_number.' 采购单非【1688下单】订单，请确认该订单是否取消');
            }

            $settlement_type_list = $this->Supplier_payment_info_model->get_settlement_name($orderInfo['supplier_code'],$orderInfo['is_drawback'],$orderInfo['purchase_type_id']);

            $items_list     = $orderInfo['items_list'];
            $total_price    = 0;
            $weight = 0;
            $demand_number = [];
            foreach($items_list as $value){
                $total_price += $value['purchase_unit_price'] * $value['confirm_amount'];
                $demand_number[] = $value['demand_number'];
                // 重量
                $w = $this->Product_model->get_product_info($value['sku'], 'product_weight');
                $product_weight = isset($w['product_weight'])?$w['product_weight'] * $value['confirm_amount']:0;
                $weight += $product_weight;
            }

            $selected_account_type = $this->Supplier_settlement_model->get_settlement_one($orderInfo['account_type']);

            $editOrderInfo = [
                'purchase_number'          => $purchase_number,
                'demand_number'            => $demand_number,
                'weight'                   => sprintf("%.3f", $weight /1000),
                'supplier_code'            => $orderInfo['supplier_code'],
                'supplier_name'            => $orderInfo['supplier_name'],
                'product_money'            => format_price($total_price),
                'freight'                  => $orderPayTypeInfo['freight'],
                'discount'                 => $orderPayTypeInfo['discount'],
                'process_cost'             => $orderPayTypeInfo['process_cost'],
                'process_cost_list'             => $orderPayTypeInfo['process_cost'],
                'total_money'              => 0,
                'account_type_list'        => $settlement_type_list,
                'selected_account_type'    => $selected_account_type['settlement_name']??'',
                'plan_product_arrive_time' => $orderInfo['plan_product_arrive_time'],
                'is_freight'               => $orderPayTypeInfo['is_freight'] == 0 ? 1: $orderPayTypeInfo['is_freight'],
                'pai_number'               => $orderPayTypeInfo['pai_number'],
                'ali_product_money'        => $localAli['total_success_amount'],
                'ali_freight'              => $localAli['post_fee'],
                'ali_discount'             => abs($localAli['discount']),
                'address'                  => $localAli['provinceText'].' '
                                                .$localAli['cityText'].' '
                                                .$localAli['areaText'].' '
                                                .$localAli['townText'].' '
                                                .$localAli['address'],
                'buyer_note'               => $localAli['message'],
                'post_fee'                 => $localAli['post_fee'],
            ];

            $editOrderInfo['total_money'] = format_price($editOrderInfo['product_money'] + $editOrderInfo['freight'] - $editOrderInfo['discount'] + $editOrderInfo['process_cost']);

            $reference_freight = $this->Purchase_order_transport_model->get_calculate_order_reference_freight(['purchase_number' => $purchase_number]);

            $supplier_info = $this->supplier_model->get_supplier_info($orderInfo['supplier_code']);
            if ($supplier_info['supplier_source'] == 3) {//临时供应商且下单量>0


                $order_num = $this->Purchase_order_model->set_temporary_supplier_order_number($orderInfo['supplier_code']);
                if ($order_num>0)  $editOrderInfo['order_num']=$order_num;

            }
            $editOrderInfo['is_postage'] = isset($supplier_info['is_postage']) && $supplier_info['is_postage'] == 1 ? true:false;
            if($reference_freight['code'] === false){
                $editOrderInfo['reference_freight']     = null;
                $editOrderInfo['reference_freight_msg'] = $reference_freight['message'];
            }else{
                $editOrderInfo['reference_freight']     = format_two_point_price($reference_freight['data']);
                $editOrderInfo['reference_freight_msg'] = $reference_freight['message'];
            }

            $purchaseOrderInfoList[] = $editOrderInfo;
        }

        $data_list = [
            'values'        => $purchaseOrderInfoList,
            'drop_down_box' => [
                'freightPaymentList' => $freightPaymentList
            ],
        ];

        $this->success_json($data_list);
    }

    /**
     * 批量 1688批量下单确认  - 确认提交经理审核
     * @author Jolon
     */
    public function ali_batch_submit_order(){
        $data_json              = $this->input->get_post('data');
        $purchasing_order_audit = $this->input->get_post('purchasing_order_audit');// 系统级参数：是否退送蓝凌
        if(empty($data_json)){ $this->error_json('数据不能为空'); }

        $data_arr                      = json_decode($data_json, true);
        $purchase_numbers              = isset($data_arr['purchase_numbers'])?$data_arr['purchase_numbers']:'';// 采购单号
        $freight_list                  = isset($data_arr['freight_list'])?$data_arr['freight_list']:[]; // 运费
        $discount_list                 = isset($data_arr['discount_list'])?$data_arr['discount_list']:[]; // 优惠额
        $process_cost_list             = isset($data_arr['process_cost_list'])?$data_arr['process_cost_list']:[]; // 加工费
        $account_type_list             = isset($data_arr['account_type_list'])?$data_arr['account_type_list']:[]; // 结算方式
        $plan_product_arrive_time_list = isset($data_arr['plan_product_arrive_time_list'])?$data_arr['plan_product_arrive_time_list']:[]; // 预计到货时间
        $is_freight_list               = isset($data_arr['is_freight_list'])?$data_arr['is_freight_list']:[]; // 运费支付
        $freight_note_list             = isset($data_arr['freight_note_list'])?$data_arr['freight_note_list']:[]; // 运费说明
        $freight_sku_list              = isset($data_arr['freight_sku_list'])?$data_arr['freight_sku_list']:[]; // 备货单运费
        $discount_sku_list             = isset($data_arr['discount_sku_list'])?$data_arr['discount_sku_list']:[]; // 备货单优惠
        $process_cost_sku_list         = isset($data_arr['process_cost_sku_list'])?$data_arr['process_cost_sku_list']:[]; // 备货单加工费

        $purchase_numbers = explode(',',$purchase_numbers);

        // 解析 采购单数据
        $purchaseOrderList = [];
        $error_list = [];
        $success_list = [];
        foreach($purchase_numbers as $purchase_number){

            // 1688 下单判断是否为新品
            // 判断采购单对应的备货单是否在锁单状态
            // 判断新品,采购单维度, 获取采购单下的备货单
            $demandNumbers =  $this->Purchase_suggest_model->getSuggestDemand($purchase_numbers,NULL);
            $demandNumbers = array_column( $demandNumbers,"demand_number");
            $demandLocks = $this->Purchase_suggest_model->getDemandLock($demandNumbers);
            $skusLock = [];
            if(!empty($demandLocks)){
                $skusLock = implode(",",array_column($demandLocks,"demand_number"));
                $error_list[] = $purchase_number."：采购单对应的备货单号:".$skusLock."，处于锁单中。请到备货单（实单-锁单）模块，解锁!";
                continue;
            }

            if(!isset($freight_list[$purchase_number])){ $error_list[] = $purchase_number.' 运费不能为空';continue; }
            if(!isset($discount_list[$purchase_number])){ $error_list[] = $purchase_number.' 优惠额不能为空';continue; }
            if(!isset($process_cost_list[$purchase_number])){ $error_list[] = $purchase_number.' 加工费不能为空';continue; }
            if(!isset($account_type_list[$purchase_number])){ $error_list[] = $purchase_number.' 结算方式不能为空';continue; }
//            if(!isset($plan_product_arrive_time_list[$purchase_number]) or $plan_product_arrive_time_list[$purchase_number] == '0000-00-00 00:00:00'){
//                $error_list[] = $purchase_number.' 预计到货时间不能为空';continue;
//            }
            if(!isset($is_freight_list[$purchase_number]) or empty(getFreightPayment($is_freight_list[$purchase_number]))){ $error_list[] = $purchase_number.' 运费支付不能为空或不是支持的方式';continue; }
//            if(!isset($freight_sku_list[$purchase_number])){ $error_list[] = $purchase_number.' 备货单运费不能为空';continue; }
//            if(!isset($discount_sku_list[$purchase_number])){ $error_list[] = $purchase_number.' 备货单优惠不能为空';continue; }
//            if(!isset($process_cost_sku_list[$purchase_number])){ $error_list[] = $purchase_number.' 备货单加工费不能为空';continue; }

            if (!is_two_decimal($freight_list[$purchase_number]) || !is_two_decimal($discount_list[$purchase_number]) || !is_two_decimal($process_cost_list[$purchase_number])){
                $error_list[] = $purchase_number.'：总运费,总优惠,加工费小数最多只能为两位';
                continue;
            }

            if(isset($is_freight_list[$purchase_number]) and $is_freight_list[$purchase_number] == PURCHASE_FREIGHT_PAYMENT_B AND $freight_list[$purchase_number] != 0){
                $error_list[] = $purchase_number.'：乙方支付不允许填写运费';
                continue;
            }
/*
            if(bccomp($freight_list[$purchase_number],array_sum($freight_sku_list[$purchase_number]),2)){
                $error_list[] = $purchase_number.'：总运费金额与备货单分摊运费总和不一致';
                continue;
            }

            if(bccomp($discount_list[$purchase_number],array_sum($discount_sku_list[$purchase_number]),2)){
                $error_list[] = $purchase_number.'：总优惠金额与备货单分摊优惠总和不一致';
                continue;
            }

            if(bccomp($process_cost_list[$purchase_number],array_sum($process_cost_sku_list[$purchase_number]),2)){
                $error_list[] = $purchase_number.'：总加工费与备货单分摊加工费总和不一致';
                continue;
            }
*/
            $err_sku = [];
            foreach ($freight_sku_list[$purchase_number] as $sku => $value){
                if (!is_two_decimal($value) || !is_two_decimal($discount_sku_list[$purchase_number][$sku]) || !is_two_decimal($process_cost_sku_list[$purchase_number][$sku])){
                    $err_sku[] = $purchase_number."：采购单SKU[$sku]运费,优惠,加工费小数最多只能为两位";
                }
            }
            if(count($err_sku) > 0){
                $error_list[] = $err_sku;
                continue;
            }

//            $plan_product_arrive_time = date('Y-m-d 12:00:00',strtotime($plan_product_arrive_time_list[$purchase_number]));

            $orderInfo          = $this->Purchase_order_model->get_one($purchase_number);
            if($orderInfo['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){
                $error_list[] = $purchase_number.'：只能待采购经理审核状态下才需要提交审核，请勿重复操作';
                continue;
            }
            if($orderInfo['is_ali_order'] == 0){
                $error_list[] = $purchase_number.'：采购单非【1688下单】订单，请确认该订单是否取消';
                continue;
            }

            $scree_result = $this->Purchase_order_model->check_order_is_scree_with_sku($purchase_number);
            if($scree_result !== false) {
                $error_list[] = $purchase_number.'：中 存在SKU：'.$scree_result.' 属于屏蔽申请中';
                continue;
            }

//            $purchaseOrderList[] = [
            $order_data = [
                'purchasing_order_audit'   => $purchasing_order_audit,// 系统级参数：是否退送蓝凌
                'purchase_number'          => $purchase_number,
                'order_freight'            => $freight_list[$purchase_number],
                'order_discount'           => $discount_list[$purchase_number],
                'order_process_cost'       => $process_cost_list[$purchase_number],
//                'plan_product_arrive_time' => $plan_product_arrive_time,
                'freight_note'             => isset($freight_note_list[$purchase_number])?$freight_note_list[$purchase_number]:'',
                'pay_type'                 => PURCHASE_PAY_TYPE_ALIPAY,

                'account_type'             => $account_type_list[$purchase_number],
                'is_freight'               => $is_freight_list[$purchase_number],
                'freight_sku'              => $freight_sku_list[$purchase_number],
                'discount_sku'             => $discount_sku_list[$purchase_number],
                'process_cost_sku'         => $process_cost_sku_list[$purchase_number],
            ];

            $result = $this->Ali_order_model->one_key_order_submit($order_data, true, true);
            if($result['code']){
                $success_list[] = $order_data['purchase_number'].' 提交经理审核成功';
            }else{
                $error_list[] = $order_data['purchase_number'].' '.$result['message'];
            }
        }
/*
        if(isset($error_list) and $error_list){
            $data = [
                'success_list' => [],
                'error_list'   => $error_list
            ];
            $this->error_data_json($data,'操作失败');
        }
        if(empty($purchaseOrderList) || count($purchaseOrderList) == 0){ $this->error_json('没有需要处理的数据'); }
        if(count($purchaseOrderList) > 0){
            foreach($purchaseOrderList as $order_data){
                $result = $this->Ali_order_model->one_key_order_submit($order_data);
                if($result['code']){
                    $success_list[] = $order_data['purchase_number'].' 提交经理审核成功';
                }else{
                    $error_list[] = $order_data['purchase_number'].' '.$result['message'];
                }
            }
        }
*/

        $data = [
            'success_list' => $success_list,
            'error_list' => $error_list
        ];

        $this->success_json($data);
    }

    /**
     * @desc 获取1688下单确认/1688批量下单确认 获取编辑数据
     * @author Jeff
     * @Date 2019/11/1 17:17
     * @return
     */
    public function get_order_sku_infos()
    {
        $purchase_number  = $this->input->get_post('purchase_number'); //采购单号
        $total_freight  = $this->input->get_post('total_freight'); //总运费
        $total_discount  = $this->input->get_post('total_discount'); //总优惠
        $total_process_cost = $this->input->get_post('total_process_cost'); //总加工费
        if (empty($purchase_number)) $this->error_json('采购单缺失');
        if ($total_freight=='') $this->error_json('总运费缺失');
        if ($total_discount=='') $this->error_json('总优惠缺失');
        if ($total_process_cost=='') $this->error_json('总加工费缺失');
        $result = $this->Ali_order_model->get_order_sku_infos($purchase_number, $total_freight, $total_discount,$total_process_cost);
        if (!empty($result)){
            $this->success_json($result);
        }else{
            $this->error_json('采购单或采购单明细缺失');
        }
    }

    /**
     * 刷新1688信息
     */
    public function refresh_ali_order_data()
    {
        $res = [
            "price" => [
                "list" => [],
                "total" => 0
            ],
            "order_status" => [
                "list" => [],
                "total" => 0
            ],
            "periodTime" => [
                "list" => [],
                "total" => 0
            ],
            "logistics" => [
                "list" => [],
                "total" => 0
            ],
            "status" => 0,
            "errorMess" => ''
        ];
        $pur_number = $this->input->get_post('purchase_numbers');
        if (!empty($pur_number) && !is_array($pur_number)) {
            $pur_number = explode(',', $pur_number);
        }

        if (count($pur_number) == 0) {
            $res['errorMess'] = '没有要处理的数据';
            $this->error_json($res);
        }
        $res = $this->Ali_order_model->refresh_ali_order_data_all($pur_number);
        if ($res['status'] == 0) {
            $this->error_json($res);
        }
        $res['status'] = 1;
        $res['errorMess'] = '处理完成';
        $this->success_json($res);
    }

    /**
     * 实时获取1688订单信息
     */
    public function get_ali_order_just_in_time()
    {
        $pai_number    = $this->input->get_post('pai_number');
        if(!$pai_number || empty($pai_number) || !is_array($pai_number))$this->error_json("拍单号不能为空");
        $list = [];
        foreach ($pai_number as $val){
            try {
                $val = json_decode($val, true);
                if(!$val || count($val) == 0)continue;
                foreach ($val as $k=>$v){
                    $list[$k] = $v;
                }
            }catch (Exception $e){}
        }
        if(count($list) == 0)$this->error_json("传值错误！");

        // 24686 通过接口验证与1688结算方式
        $this->load->model('purchase/Purchase_order_ali_verify_model');
        $verify_ali = $this->Purchase_order_ali_verify_model->verify_ali_order_settlement($list);
        if($verify_ali && $verify_ali['code'] == 1){
            $this->success_json($verify_ali['msg'], '', '校验完成。');
        }else{
            $this->error_json(isset($verify_ali['msg'])?$verify_ali['msg']: "校验失败。");
        }
    }


}