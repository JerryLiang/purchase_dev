<?php
/**
 * 1688 订单操作控制器
 * User: Jolon
 * Date: 2019/12/20 10:00
 */
class Ali_order_advanced extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->helper('status_1688');

        $this->load->library('alibaba/AliProductApi');
        $this->load->library('alibaba/AliOrderApi');
        $this->load->library('alibaba/AliSupplierApi');
        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('purchase/Purchase_auto_audit_model');
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('ali/Ali_product_model');
        $this->load->model('ali/Ali_order_model');
        $this->load->model('ali/Ali_order_advanced_model');
        $this->load->model('finance/purchase_order_pay_type_model');
        $this->load->model('finance/Purchase_auto_payout_model');
    }

    /**
     * 1688一键拍单（自动建单、自动拍单）
     */
    public function one_key_create_order(){
        $this->error_json('接口已禁用-请使用新功能');
        
        set_time_limit(0);
        $suggest_ids        = $this->input->get_post('ids');// 备货单ID
        $is_force_submit    = $this->input->get_post('is_force_submit'); // 是否强制提交,1.是,其他.否
        if($suggest_ids){
            if(!is_array($suggest_ids)){
                $suggest_ids_list = explode(',',$suggest_ids);
            }

        }else{
            // 读取缓存的查询SQL
            $suggest_get_list_querySql = $this->rediss->getData(md5(getActiveUserId().'_purchase_suggest_get_list'));
            if(empty($suggest_get_list_querySql)) $this->error_json("缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $suggest_get_list_querySql = base64_decode($suggest_get_list_querySql);

            // 截取第一个FROM 和 最后一个GROUP BY 之间的字符串
            $from_index                = stripos($suggest_get_list_querySql, 'FROM');
            $length                    = strlen($suggest_get_list_querySql) - $from_index;
            $suggest_get_list_querySql = substr($suggest_get_list_querySql, $from_index, $length);
            $suggest_get_list_querySql = "SELECT ps.id,ps.demand_number ".$suggest_get_list_querySql;
            $suggest_ids_list          = $this->Ali_order_advanced_model->purchase_db->query($suggest_get_list_querySql)->result_array();
            $suggest_ids_list          = array_column($suggest_ids_list, 'id');

        }
        if(!isset($suggest_ids_list) or empty($suggest_ids_list)) $this->error_json('没有获取到可以【1688一键下单】的数据，请确认操作');
        if(isset($suggest_ids_list) and count($suggest_ids_list) > 100) $this->error_json('您选择的数据过多，请减少数据量，最大值100个PO');

        $message_list = [];
        $isDrawbacks = $this->purchase_suggest_model->getSuggestIsDrawback($suggest_ids);
        if( NULL != $isDrawbacks){
            $this->error_json("备货单号:".$isDrawbacks."是退税，不可点击");
        }
        // 步骤：系统验证：0，业务级：1/2/3/4/5/6
        // 0.根据目标备货单ID查找备货单
        // 1.第一步：【1688一键下单】先进行数据检验（验证备货单数据是否满足条件，返回提示，若设置 $is_force_submit = 1 则自动往下）
        $verify_result = $this->Ali_order_advanced_model->verify_purchase_suggest($suggest_ids_list);
        $success_list  = $verify_result['success_list'];
        $error_list    = $verify_result['error_list'];
        $errorMess     = "备货单：".count($success_list)."个成功，".count($error_list)."个不成功";
        if($is_force_submit != 1){
            $this->error_data_json($error_list,$errorMess);
        }elseif(count($success_list) == 0){
            $this->error_json('没有满足条件的备货单');
        }
        $message_list['verify_purchase_suggest']['message'] = $errorMess;
        $message_list['verify_purchase_suggest']['error_list'] = $error_list;


        // 2.第二步：满足上述所有条件的数据，按照合单规则，合并生成po号。
        // 获取满足条件的备货单
        $suggest_list = $this->Ali_order_advanced_model->purchase_db
            ->from('pur_purchase_suggest')
            ->where_in('id',array_keys($success_list))
            ->get()
            ->result_array();
        $verify_result          = $this->Ali_order_advanced_model->advanced_create_order($suggest_list);
        $success_pur_numbers    = $verify_result['success_pur_numbers'];
        $suggest_list_success   = $verify_result['suggest_list_success'];
        $errorMess              = "生成采购单：".count($success_pur_numbers)."个成功，成功生成采购单的备货单：".count(array_keys($suggest_list_success))."个数";
        $message_list['advanced_create_order']['message'] = $errorMess;
        foreach($success_pur_numbers as $purchase_number){
            apiRequestLogInsert(
                [
                    'record_number'    => $purchase_number,
                    'record_type'      => '1688一键下单',
                    'post_content'     => '第二步：自动简单：成功',
                    'response_content' => '',
                    'status'           => '1',
                ],
                'api_request_ali_log'
            );
        }


        // 3.第三步：1688自助拍单，按照（不满足起订量）不能自助拍单的规则进行自助拍单。
        $success_list_3 = $error_list_3 = [];
        foreach($success_pur_numbers as $purchase_number){
            // 验证 是否被占用
            $session_key = 'one_key_order_' . $purchase_number;
            if (!$this->rediss->getData($session_key)) {
                $this->rediss->setData($session_key, '1', 600); //设置缓存和有效时间
            } else {
                $error_list_3[] = $purchase_number.' 可能多方同时下单';
                continue;
            }

            // 创建订单信息
            $result = $this->Ali_order_model->one_key_order_not_preview($purchase_number);
            if($result['code']){
                $success_list_3[$purchase_number] = $purchase_number;

                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '1688一键拍单',
                        'post_content'     => '第三步：自助拍单：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }else{
                $error_list_3[$purchase_number] = $purchase_number.' '.$result['message'];
            }
            $this->rediss->deleteData($session_key);
        }
        $success   = count($success_list_3);
        $error     = count($error_list_3);
        $errorMess = "1688拍单报错：{$success} 个成功，{$error} 个不成功";
        $message_list['do_create_ali_order']['message'] = $errorMess;
        $message_list['do_create_ali_order']['error_list'] = $error_list_3;


        $this->success_json($message_list);
    }


    /**
     * 1688一键下单（自动建单、自动拍单、自动下单确认、自动审核、自动请款）
     */
    public function advanced_one_key_create_order(){
        $this->error_json('接口已禁用-请使用新功能');

        set_time_limit(0);
        $suggest_ids        = $this->input->get_post('ids');// 备货单ID
        $is_force_submit    = $this->input->get_post('is_force_submit'); // 是否强制提交,1.是,其他.否
        if($suggest_ids){
            if(!is_array($suggest_ids)){
                $suggest_ids_list = explode(',',$suggest_ids);
            }

        }else{
            // 读取缓存的查询SQL
            $suggest_get_list_querySql = $this->rediss->getData(md5(getActiveUserId().'_purchase_suggest_get_list'));
            if(empty($suggest_get_list_querySql)) $this->error_json("缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $suggest_get_list_querySql = base64_decode($suggest_get_list_querySql);

            // 截取第一个FROM 和 最后一个GROUP BY 之间的字符串
            $from_index                = stripos($suggest_get_list_querySql, 'FROM');
            $length                    = strlen($suggest_get_list_querySql) - $from_index;
            $suggest_get_list_querySql = substr($suggest_get_list_querySql, $from_index, $length);
            $suggest_get_list_querySql = "SELECT ps.id,ps.demand_number ".$suggest_get_list_querySql;
            $suggest_ids_list          = $this->Ali_order_advanced_model->purchase_db->query($suggest_get_list_querySql)->result_array();
            $suggest_ids_list          = array_column($suggest_ids_list, 'id');

        }

        $isDrawbacks = $this->purchase_suggest_model->getSuggestIsDrawback($suggest_ids);
        if( NULL != $isDrawbacks){
            $this->error_json("备货单号:".$isDrawbacks."是退税，不可点击");
        }
        if(!isset($suggest_ids_list) or empty($suggest_ids_list)) $this->error_json('没有获取到可以【1688一键下单】的数据，请确认操作');
        if(isset($suggest_ids_list) and count($suggest_ids_list) > 100) $this->error_json('您选择的数据过多，请减少数据量，最大值100个PO');

        /**
           * 判断 1688 下单的备货单是否在锁单中
         **/
        if(!empty($suggest_ids_list)){
            $suggestIdsDatas = $this->purchase_suggest_model->get_suggest_lock(NULL,$suggest_ids_list);

            if( !empty($suggestIdsDatas)) {
                $locksuggetsNumbers = array_column($suggestIdsDatas, "demand_number");
                if(!empty($locksuggetsNumbers)){
                    $errorMessage = "备货单号:".implode(",",$locksuggetsNumbers)."，处于锁单中。请到备货单（实单-锁单）模块，解锁!";
                    $this->error_json($errorMessage);
                }
            }
        }
        $message_list           = [];
        $purchasing_order_audit = 2;


        // 步骤：系统验证：0，业务级：1/2/3/4/5/6
        // 0.根据目标备货单ID查找备货单
        // 1.第一步：【1688一键下单】先进行数据检验（验证备货单数据是否满足条件，返回提示，若设置 $is_force_submit = 1 则自动往下）
        $verify_result   = $this->Ali_order_advanced_model->verify_purchase_suggest($suggest_ids_list);
        $success_list_1  = $verify_result['success_list'];
        $error_list_1    = $verify_result['error_list'];
        $errorMess       = "备货单：".count($success_list_1)."个成功，".count($error_list_1)."个不成功";
        if($is_force_submit != 1){
            $this->error_data_json($error_list_1,$errorMess);
        }elseif(count($success_list_1) == 0){
            $this->error_json('没有满足条件的备货单');
        }
        $message_list['verify_purchase_suggest']['message'] = $errorMess;
        $message_list['verify_purchase_suggest']['error_list'] = $error_list_1;


        // 2.第二步：满足上述所有条件的数据，按照合单规则，合并生成po号。
        // 获取满足条件的备货单
        $suggest_list = $this->Ali_order_advanced_model->purchase_db
            ->from('pur_purchase_suggest')
            ->where_in('id',array_keys($success_list_1))
            ->get()
            ->result_array();
        $verify_result          = $this->Ali_order_advanced_model->advanced_create_order($suggest_list);
        $success_pur_numbers    = $verify_result['success_pur_numbers'];
        $suggest_list_success   = $verify_result['suggest_list_success'];
        $errorMess              = "生成采购单：".count($success_pur_numbers)."个成功，成功生成采购单的备货单：".count(array_keys($suggest_list_success))."个数";
        $message_list['advanced_create_order']['message'] = $errorMess;
        foreach($success_pur_numbers as $purchase_number){
            apiRequestLogInsert(
                [
                    'record_number'    => $purchase_number,
                    'record_type'      => '1688一键下单',
                    'post_content'     => '第二步：自动建单：成功',
                    'response_content' => '',
                    'status'           => '1',
                ],
                'api_request_ali_log'
            );
        }


        // 3.第三步：1688自助拍单，按照（不满足起订量）不能自助拍单的规则进行自助拍单。
        $success_list_3 = $error_list_3 = [];
        foreach($success_pur_numbers as $purchase_number){
            // 验证 是否被占用
            $session_key = 'one_key_order_' . $purchase_number;
            if (!$this->rediss->getData($session_key)) {
                $this->rediss->setData($session_key, '1', 600); //设置缓存和有效时间
            } else {
                $error_list_3[] = $purchase_number.' 可能多方同时下单';
                continue;
            }

            // 创建订单信息
            $result = $this->Ali_order_model->one_key_order_not_preview($purchase_number);
            if($result['code']){
                $success_list_3[$purchase_number] = $purchase_number;

                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '1688一键下单',
                        'post_content'     => '第三步：1688自助拍单：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }else{
                $error_list_3[$purchase_number] = $purchase_number.' '.$result['message'];
            }
            $this->rediss->deleteData($session_key);
        }
        $success   = count($success_list_3);
        $error     = count($error_list_3);
        $errorMess = "1688拍单报错：{$success} 个成功，{$error} 个不成功";
        $message_list['do_create_ali_order']['message'] = $errorMess;
        $message_list['do_create_ali_order']['error_list'] = $error_list_3;


        // 4.第四步：1688自助下单确认。
        //      自助拍单成功后，po采购金额满足配置中的条件的，且po的1688总金额=系统中的采购总金额，则进行1688下单确认，不满足的，数据停留在采购单页面，让用户人工确认
        $success_list_4 = $error_list_4 =  [];
        foreach($success_list_3 as $purchase_number){
            $purchaseOrder = $this->Purchase_order_model->get_one($purchase_number);
            if ($purchaseOrder['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){
                $success_list_4[$purchase_number] = $purchase_number;// 采购单非待采购询价状态，无需再次提交确认
                continue;
            }
            $localAliOrder = $this->Ali_order_model->check_have_order($purchase_number);

            // 分摊 1688 平台的运费优惠额到采购系统中来
            $order_discount     = $localAliOrder['discount'];//优惠金额
            $order_freight      = $localAliOrder['post_fee'];//运费
            $orderDistribution  = $this->Ali_order_model->get_order_sku_infos($purchase_number, $order_freight, $order_discount);

            if(empty($orderDistribution)){
                $error_list_4[$purchase_number] = $purchase_number.' 采购单运费或优惠额分摊失败';
                continue;
            }
            $freight_sku    = array_column($orderDistribution,'freight','sku');
            $discount_sku   = array_column($orderDistribution,'discount','sku');

            $data = [
                'purchase_number'          => $purchase_number,
                'order_discount'           => floatval($order_discount),
                'order_freight'            => floatval($order_freight),
                'purchasing_order_audit'   => $purchasing_order_audit,// 是否退送蓝凌：不推送蓝凌
                'plan_product_arrive_time' => $purchaseOrder['plan_product_arrive_time'],//预计到货时间
                'pay_type'                 => PURCHASE_PAY_TYPE_ALIPAY,// 支付方式=支付宝
                'freight_note'             => '',
                'freight_sku'              => $freight_sku,
                'discount_sku'             => $discount_sku,
                'is_freight'               => PURCHASE_FREIGHT_PAYMENT_A,// 1688下单默认甲方支付
            ];

            $result = $this->Ali_order_model->one_key_order_submit($data);
            if($result['code']){
                $success_list_4[$purchase_number] = $purchase_number;
              //  $success_push_gateways[]=$purchase_number;

                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '1688一键下单',
                        'post_content'     => '第四步：1688自助下单确认：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }else{
                $error_list_4[$purchase_number] = $purchase_number.' '.$result['message'];
            }
        }
//
//        if(!empty($success_push_gateways)){
//
//            $purchaseGateWays = $this->purchase_order_model->getPurchaseGateWays($success_push_gateways);
//            if (NULL != $purchaseGateWays && !empty($purchaseGateWays)) {
//                $gatewaysData = $this->supplier_joint_model->pushSmcPurchaseData($purchaseGateWays);
//                apiRequestLogInsert(
//                    [
//                        'record_number'    => json_encode($success_push_gateways),
//                        'record_type'      => '1688一键下单推送到门户系统',
//                        'post_content'     => '1688一键下单推送到门户系统',
//                        'response_content' =>$gatewaysData,
//                        'status'           => '1',
//                    ],
//                    'api_request_ali_log'
//                );
//            }
//        }
        $success   = count($success_list_4);
        $error     = count($error_list_4);
        $errorMess = "1688自动确认报错：{$success} 个成功，{$error} 个不成功";
        $message_list['one_key_order_submit']['message'] = $errorMess;
        $message_list['one_key_order_submit']['error_list'] = $error_list_4;


        // 5.第五步：按照采购单自动审核规则将能审核通过的进行审核通过，其他的数据停留在采购单页面进行人工审核
        $success_list_5 = $error_list_5 = [];
        foreach($success_list_4 as $purchase_number){
            $purchaseOrder  = $this->Purchase_order_model->get_one($purchase_number);
            if($purchaseOrder['purchase_order_status'] == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL){
                $success_list_5[$purchase_number] = $purchase_number;
                continue;
            }
            if($purchaseOrder['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT){
                $error_list_5[$purchase_number] = $purchase_number.' 采购单非待采购经理审核状态';
                continue;
            }
            $autoAuditCheck = $this->Purchase_auto_audit_model->checkPurchaseOrderAutomaticAudit($purchaseOrder);

            if($autoAuditCheck['code']){
                $this->Purchase_order_model->change_status($purchase_number,PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);

                if ($purchasing_order_audit == PUSHING_BLUE_LING) {//推送蓝凌系统
                    $this->Purchase_order_model->pushing_blue_ling($purchase_number);
                }

                $success_list_5[$purchase_number] = $purchase_number;

                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '1688一键下单',
                        'post_content'     => '第五步：自动审核：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }else{
                $error_list_5[$purchase_number] = $purchase_number.' '.$autoAuditCheck['message'];
            }
        }
        $success   = count($success_list_5);
        $error     = count($error_list_5);
        $errorMess = "自动审核失败确认报错：{$success} 个成功，{$error} 个不成功";
        $message_list['auto_audit_result']['message'] = $errorMess;
        $message_list['auto_audit_result']['error_list'] = $error_list_5;


        // 6.第六步： 按照采购单自动请款规则进行自动请款
        $success_list_6  = $error_list_6 = [];
        $create_notice = '【1688 自动请款】';
        foreach($success_list_5 as $purchase_number){
            $auto_payout_result = $this->Purchase_auto_payout_model->do_auto_payout($purchase_number,$create_notice);
            if($auto_payout_result['code']){
                $success_list_6[$purchase_number] = $purchase_number;

                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '1688一键下单',
                        'post_content'     => '第六步：自动请款：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }else{
                $error_list_6[$purchase_number] = $purchase_number.' '.$auto_payout_result['message'];
            }
        }
        $success   = count($success_list_6);
        $error     = count($error_list_6);
        $errorMess = "自动请款失败：{$success} 个成功，{$error} 个不成功";
        $message_list['auto_payout']['message'] = $errorMess;
        $message_list['auto_payout']['error_list'] = $error_list_6;

        $this->success_json($message_list);
    }


    /**
     * 1688一键确认/请款（系统自动对所有“是否1688下单=是，待采购询价”下的订单进行操作）
     */
    public function advanced_one_key_payout(){
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
                 GROUP BY `ppo`.`purchase_number`";

            $purchase_number_list  = $this->Ali_order_model->purchase_db->query($new_get_list_querySql)->result_array();
            $purchase_number_list  = array_column($purchase_number_list,'purchase_number');

        }
        if(empty($purchase_number_list)) $this->error_json('没有获取到待【1688一键确认/请款】的数据，请确认操作');
        if(count($purchase_number_list) > 100) $this->error_json('您选择的数据过多，请减少数据量，最大值100个PO');

        $purchasing_order_audit = 2;

        // 4.第四步：1688自助下单确认。
        //      自助拍单成功后，po采购金额满足配置中的条件的，且po的1688总金额=系统中的采购总金额，则进行1688下单确认，不满足的，数据停留在采购单页面，让用户人工确认
        $success_list_4 = $error_list_4 = [];
        foreach($purchase_number_list as $purchase_number){
            $purchaseOrder = $this->Purchase_order_model->get_one($purchase_number);
            if ($purchaseOrder['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){
                $success_list_4[$purchase_number] = $purchase_number;// 采购单非待采购询价状态，无需再次提交确认
                continue;
            }
            $localAliOrder = $this->Ali_order_model->check_have_order($purchase_number);

            // 分摊 1688 平台的运费优惠额到采购系统中来
            $order_discount     = $localAliOrder['discount'];//优惠金额
            $order_freight      = $localAliOrder['post_fee'];//运费
            $orderDistribution  = $this->Ali_order_model->get_order_sku_infos($purchase_number, $order_freight, $order_discount);

            if(empty($orderDistribution)){
                $error_list_4[$purchase_number] = $purchase_number.' 采购单运费或优惠额分摊失败';
                continue;
            }
            $freight_sku    = array_column($orderDistribution,'freight','sku');
            $discount_sku   = array_column($orderDistribution,'discount','sku');

            $data = [
                'purchase_number'          => $purchase_number,
                'order_discount'           => floatval($order_discount),
                'order_freight'            => floatval($order_freight),
                'purchasing_order_audit'   => $purchasing_order_audit,// 是否退送蓝凌：不推送蓝凌
                'plan_product_arrive_time' => $purchaseOrder['plan_product_arrive_time'],//预计到货时间
                'pay_type'                 => PURCHASE_PAY_TYPE_ALIPAY,// 支付方式=支付宝
                'freight_note'             => '',
                'freight_sku'              => $freight_sku,
                'discount_sku'             => $discount_sku,
                'is_freight'               => PURCHASE_FREIGHT_PAYMENT_A,// 1688下单默认甲方支付
            ];

            $result = $this->Ali_order_model->one_key_order_submit($data);
            if($result['code']){
                $success_list_4[$purchase_number] = $purchase_number;

                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '1688一键确认/请款',
                        'post_content'     => '第四步：1688自助下单确认：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }else{
                $error_list_4[$purchase_number] = $purchase_number.' '.$result['message'];
            }
        }
        $success   = count($success_list_4);
        $error     = count($error_list_4);
        $errorMess = "1688自动确认报错：{$success} 个成功，{$error} 个不成功";
        $message_list['one_key_order_submit']['message'] = $errorMess;
        $message_list['one_key_order_submit']['error_list'] = $error_list_4;


        // 5.第五步：按照采购单自动审核规则将能审核通过的进行审核通过，其他的数据停留在采购单页面进行人工审核
        $success_list_5 = $error_list_5 = [];
        foreach($success_list_4 as $purchase_number){
            $purchaseOrder  = $this->Purchase_order_model->get_one($purchase_number);
            if($purchaseOrder['purchase_order_status'] == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL){
                $success_list_5[$purchase_number] = $purchase_number;
                continue;
            }
            if($purchaseOrder['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT){
                $error_list_5[$purchase_number] = $purchase_number.' 采购单非待采购经理审核状态';
                continue;
            }
            $autoAuditCheck = $this->Purchase_auto_audit_model->checkPurchaseOrderAutomaticAudit($purchaseOrder);

            if($autoAuditCheck['code']){
                $this->Purchase_order_model->change_status($purchase_number,PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);

                if ($purchasing_order_audit == PUSHING_BLUE_LING) {//推送蓝凌系统
                    $this->Purchase_order_model->pushing_blue_ling($purchase_number);
                }

                $success_list_5[$purchase_number] = $purchase_number;

                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '1688一键确认/请款',
                        'post_content'     => '第五步：自动审核：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }else{
                $error_list_5[$purchase_number] = $purchase_number.' '.$autoAuditCheck['message'];
            }
        }
        $success   = count($success_list_5);
        $error     = count($error_list_5);
        $errorMess = "自动审核失败确认报错：{$success} 个成功，{$error} 个不成功";
        $message_list['auto_audit_result']['message'] = $errorMess;
        $message_list['auto_audit_result']['error_list'] = $error_list_5;


        // 6.第六步： 按照采购单自动请款规则进行自动请款
        $success_list_6  = $error_list_6 = [];
        $create_notice = '【1688 自动请款】';
        foreach($success_list_5 as $purchase_number){
            $auto_payout_result = $this->Purchase_auto_payout_model->do_auto_payout($purchase_number,$create_notice);
            if($auto_payout_result['code']){
                $success_list_6[$purchase_number] = $purchase_number;

                apiRequestLogInsert(
                    [
                        'record_number'    => $purchase_number,
                        'record_type'      => '1688一键确认/请款',
                        'post_content'     => '第六步：自动请款：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }else{
                $error_list_6[$purchase_number] = $purchase_number.' '.$auto_payout_result['message'];
            }
        }
        $success   = count($success_list_6);
        $error     = count($error_list_6);
        $errorMess = "自动请款失败：{$success} 个成功，{$error} 个不成功";
        $message_list['auto_payout']['message'] = $errorMess;
        $message_list['auto_payout']['error_list'] = $error_list_6;

        $this->success_json($message_list);
    }

}