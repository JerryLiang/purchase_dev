<?php
/**
 * 生成1688订单和拍单
 * User: 叶凡立
 * Date: 2020/11/03
 */
class Ali_order_advanced_load_model extends Purchase_model
{
    /**
     * 应付款时间计算配置
     */
    protected $calc_base = false;

    /**
     * Ali_order_advanced_load constructor.
     */
    public function __construct(){
        parent::__construct();
        $this->load->model('ali/Ali_order_advanced_new_model', 'advanced_new_model');
        $this->load->model('ali/Ali_order_advanced_model', 'advanced_model');
        $this->load->model('finance/Purchase_auto_payout_model');
        $this->load->model('purchase/Purchase_auto_audit_model');
        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('ali/Ali_order_model');
        $this->load->library('rediss');
        $this->load->model('calc_pay_time_model');
        $this->calc_base = $this->calc_pay_time_model->getSetParamData('PURCHASE_ORDER_PAY_TIME_SET');
    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 调用处理异步任务
     */
    public function handle_sync_task($id=null)
    {
        $data = null;
        try{
            $handle_data = $this->purchase_db->from("handle_create_order_log")->where("id=", $id)->get()->row_array();
            $data = $handle_data['handle_query'];
        }catch (Exception $e){}
        if(empty($data))return;
        $log = [
            "action_name"   => "one_key_create_order_task",
            "create_at"     => $this->get_microtime(),
            "request_data"  => $data,
        ];
        $data = json_decode($data, true);
        $data['id'] = $id;
        $id = isset($data['id'])?$data['id']:false;
        $ids = isset($data['list'])?$data['list']:false;
        $uid = isset($data['uid'])?$data['uid']: 0;
        $user = isset($data['user'])?$data['user']: "system";
        $action = $data['action'];
        $userInfo = [
            "uid"       => $uid,
            "username"  => $user
        ];
        $date = date('Y-m-d H:i:s');
        $end_msg = '';
        $is_false = false;
        try{
            if(!$id || !$ids)throw new Exception("数据不正确！");
            // 创建任务提示
            $all_len = count($ids);
            $handle_status = [
                "handle_msg" => '处理中...',
                "handle_status" => 1,
                "handle_at"     => $date,
            ];
            $this->advanced_new_model->callback_create_error($handle_status, ['id' => $id]);

            // 1.生成排他队列，剔除他人生成的部分
            $handle_ids = $this->suggest_order_exclude($ids);
            if(empty($handle_ids)){
                $is_false = true;
                throw new Exception("这些备货单已有其他任务在处理");
            }
            $ids = $handle_ids;

            // 2.验证备货单是否符合生成条件
            $verify = $this->advanced_new_model->verify_purchase_suggest($ids, $action);
            if(isset($verify['error']) && count($verify['error']) > 0){
                foreach ($verify['error'] as $key => $val){
                    $this->advanced_new_model->callback_create_error_items([
                        "demand_number" => $key,
                        "handle_id" => $id,
                        "handle_msg" => $val,
                        "handle_status" => 0
                    ]);
                }
            }
            if(count($verify) == 0 || !isset($verify['success']) || count($verify['success']) == 0){
                $this->unlock_suggest($ids);
                throw new Exception("没有符合1688一键下单的备货单");
            }

            // 3.组合成可生成的备货单组合，并生成采购单
            $grouping = $this->advanced_new_model->grouping_suggest_and_create(array_values($verify['success']), $userInfo);
            // 无论生成失败与否全部解锁
            $this->unlock_suggest($ids);
            /*
             * ---------------------
             * 如果是一键生成采购单，则终止
             * ---------------------
             */
            if($action == 3){
                $ali_succ = count($grouping['success']);
                $tips = "一键生成采购单";
                $tips = $tips.($ali_succ == 0?"失败":"成功");
                throw new Exception($tips);
            }

            // 聚合所有可生成的备货单组合,向1688下单
            $create_aliorder = $this->send_order_to_ali($grouping['success'], $id);
            if(empty($create_aliorder)){
                throw new Exception('生成采购单成功，1688下单失败！');
            }

            /*
             * ---------------------
             * 如果是1688一键拍单，则终止
             * ---------------------
             */
            if($action == 2){
                $ali_succ = count($create_aliorder);
                $tips = "1688一键拍单";
                $tips = $tips.($ali_succ == 0?"失败":"成功");
                throw new Exception($tips);
            }

            // 1688自助下单确认
            $order_confirm = $this->create_ali_order_confirm($create_aliorder, $userInfo, $id);
            if(empty($order_confirm)){
                throw new Exception('生成采购单成功，1688下单成功，自助下单确认失败！');
            }

            // 按照采购单自动审核规则将能审核通过的进行审核通过，其他的数据停留在采购单页面进行人工审核
            $order_examine = $this->create_ali_order_examine($order_confirm, $userInfo, $id);
            if(empty($order_examine)){
                throw new Exception('生成采购单成功，1688下单成功，自助下单确认成功，自动审核失败！');
            }

            //按照采购单自动请款规则进行自动请款
            $order_payment = $this->create_ali_order_payment($order_examine, $userInfo, $id);
            $order_payment_msg = '自动请款'.(empty($order_payment) ? "失败！": "成功！");
            throw new Exception('生成采购单成功，1688下单成功，自助下单确认成功，自动审核成功，'.$order_payment_msg);
        }catch (Exception $e){
            $log['context_err'] = $e->getMessage();
            $end_msg = $e->getMessage();
        }
        // 结束时必须完成,并依据错误日志判断失败条数
        $err_num = $this->purchase_db->from('handle_create_order_items')->select("count(DISTINCT demand_number) all_num")->where('handle_id = ', $id)->get()->row_array();

        $sql = $this->purchase_db->last_query();
        $sql .= "##########".json_encode($err_num);
        $all_num = 0;
        if(isset($err_num['all_num']) && $err_num['all_num'] != 0)$all_num = $err_num['all_num'];
        $update_end = [
            "handle_status" => $is_false?3:2,
            "handle_msg"    => $end_msg,
            "end_at"        => date('Y-m-d H:i:s'),
            "error_num"     => $is_false?count($ids):$all_num,
            "success_num"   => $is_false?0:(count($ids) - $all_num),
        ];
        $error_msg = json_encode($update_end);
        $update_end['error_msg'] = $error_msg."#######".$sql;

        $this->advanced_new_model->callback_create_error($update_end, ['id' => $id]);
    }

    /**
     * 生成备货单排他处理
     */
    public function suggest_order_exclude($ids = [])
    {
        $handle_ids = [];
        $hash_doc = 'PUR_HANDLE_ONE_KEY_CREATE_ORDER';
        $this_unix = time();
        $time_out = 300; // 生成不成功的过期时间
        foreach ($ids as $v){
            $has_is = $this->rediss->getHashData($hash_doc, $v);
            try{
                $has_is = json_decode($has_is, true);
                $has_is = (int)$has_is[0];
            }catch (Exception $e){}
            if(!$has_is || empty($has_is) || !is_numeric($has_is))$has_is = 0;
            if(($this_unix - $has_is) > $time_out){
                $handle_ids[] = $v;
                $this->rediss->addHashData($hash_doc, $v, $this_unix + $time_out);
            }
        }
        return $handle_ids;
    }

    /**
     * 批量向1688下单
     */
    private function send_order_to_ali($data, $id=false)
    {
        $res = [];
        $list = [];
        foreach ($data as $k=>$v){
            if(!in_array($v, array_keys($list)))$list[$v] = [];
            $list[$v][] = $k;
        }
        foreach ($list as $pur_number => $demand_number){
            // 验证 是否被占用
            $session_key = 'one_key_order_' . $pur_number;
            if (!$this->rediss->getData($session_key)) {
                $this->rediss->setData($session_key, '1', 600); //设置缓存和有效时间
            }else{
                // 多方下单导致下单失败
                if($id){
                    $this->advanced_new_model->callback_create_error([
                        "handle_msg" => '多方下单导致下单失败',
                        "handle_status" => 3,
                        "end_at"     => date('Y-m-d H:i:s'),
                    ], ['id' => $id]);
                }
                continue;
            }

            // 创建订单信息
            $result = $this->Ali_order_model->one_key_order_not_preview($pur_number);
            $res_str = "失败!";
            if($result['code']){
                $res[] = $pur_number;
                apiRequestLogInsert(
                    [
                        'record_number'    => $pur_number,
                        'record_type'      => '1688一键拍单',
                        'post_content'     => '第三步：自助拍单：成功',
                        'response_content' => '',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
                $res_str = "成功!";
            }elseif(isset($result['message']) && !empty($result['message'])){
                $res_str .= $result['message'];
            }
            $this->rediss->deleteData($session_key);
            if(count($demand_number) == 0)continue;
            foreach ($demand_number as $val){
                $create_pur_err = [
                    "demand_number" => $val,
                    "handle_id" => $id,
                    "handle_msg" => "生成采购单成功，{$pur_number}向1688下单{$res_str}",
                    "handle_status" => 0
                ];
                $this->advanced_new_model->callback_create_error_items($create_pur_err);
            }
        }
        return $res;
    }

    /**
     * 根据失败的采购单，更新所有失败的备货单
     */
    public function update_error_purchase_items($purchase='', $id=0, $msg='')
    {
        if(empty($purchase) || $id == 0 || empty($msg))return;
        $suggest = $this->advanced_new_model->get_purchase_all_suggest($purchase);
        if(!$suggest || count($suggest) == 0)return;
        foreach ($suggest as $vi){
            $this->advanced_new_model->callback_create_error_items([
                "demand_number" => $vi,
                "handle_id" => $id,
                "handle_msg" => $msg,
                "handle_status" => 0
            ]);
        }
    }

    /**
     * 1688自助下单确认，如成功则推送门户系统
     */
    private function create_ali_order_confirm($data, $userInfo=[], $id=0)
    {
        $res = [];
        $purchasing_order_audit = 2;
        $success_push_gateways = [];
        foreach ($data as $val){
            $purchaseOrder = $this->Purchase_order_model->get_one($val);
            if ($purchaseOrder['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_QUOTE){
                $res[$val] = $val;// 采购单非待采购询价状态，无需再次提交确认
                continue;
            }
            $localAliOrder = $this->Ali_order_model->check_have_order($val);
            // 分摊 1688 平台的运费优惠额到采购系统中来
            $order_discount     = $localAliOrder['discount'];//优惠金额
            $order_freight      = $localAliOrder['post_fee'];//运费
            $orderDistribution  = $this->Ali_order_model->get_order_sku_infos($val, $order_freight, $order_discount);

            $msg = '';
            $is_err = true;
            if(!empty($orderDistribution)){
                $freight_sku    = array_column($orderDistribution,'freight','sku');
                $discount_sku   = array_column($orderDistribution,'discount','sku');
                $data = [
                    'purchase_number'          => $val,
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

                $result = $this->Ali_order_model->one_key_order_submit($data, false);
                if($result['code']){
                    $res[$val] = $val;
                    $success_push_gateways[]=$val;
                    $msg = '1688自助下单确认：成功';
                    $is_err = false;
                }else{
                    $msg = isset($result['message']) ? $result['message']: '下单失败，无返回原因。';
                }
            }else{
                $msg = $val.' 采购单运费或优惠额分摊失败';
            }
            apiRequestLogInsert(
                [
                    'record_number'    => $val,
                    'record_type'      => '1688一键下单',
                    'post_content'     => '第四步：'.$msg,
                    'response_content' => '',
                    'status'           => '1',
                ],
                'api_request_ali_log'
            );
            if($is_err)$this->update_error_purchase_items($val, $id, $msg."下单确认失败。");
        }

        if(!empty($success_push_gateways)){
            $purchaseGateWays = $this->purchase_order_model->getPurchaseGateWays($success_push_gateways);
            if (!empty($purchaseGateWays)) {
                $this->load->model('sync_supplier_model');
                $gatewaysData = $this->sync_supplier_model->set_push_data($purchaseGateWays, 'ali_order_confirm');
                $gatewaysData = is_array($gatewaysData) ? json_encode($gatewaysData):$gatewaysData;
                $purchaseGateWays = is_array($purchaseGateWays) ? json_encode($purchaseGateWays):$purchaseGateWays;
                apiRequestLogInsert(
                    [
                        'record_number'    => "request: {$purchaseGateWays}......response:{$gatewaysData}",
                        'record_type'      => '1688一键下单推送到门户系统',
                        'post_content'     => '1688一键下单推送到门户系统',
                        'response_content' => '消息入队列',
                        'status'           => '1',
                    ],
                    'api_request_ali_log'
                );
            }
        }
        return $res;
    }

    /**
     * 按照采购单自动审核规则将能审核通过的进行审核通过，其他的数据停留在采购单页面进行人工审核
     */
    private function create_ali_order_examine($data, $userInfo=[], $id=0)
    {
        $res = [];
        $err = [];
        $purchasing_order_audit = 2;
        foreach ($data as $val){
            $purchaseOrder  = $this->Purchase_order_model->get_one($val);
            $is_err = true;
            if($purchaseOrder['purchase_order_status'] == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL){
                $res[] = $val;
                continue;
            }
            if($purchaseOrder['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT){
                $err[$val] = $val.' 采购单非待采购经理审核状态';
                continue;
            }
            $autoAuditCheck = $this->Purchase_auto_audit_model->checkPurchaseOrderAutomaticAudit($purchaseOrder);

            /* ----------------------- SKU降价限制审核通过 --------------------------- */
            $sku_reduce = false;
            if(SetAndNotEmpty($purchaseOrder, 'items_list')){
                $sku_pl = $product_list = [];
                $reduce_sku = array_unique(array_column($purchaseOrder['items_list'], 'sku'));
                if(!empty($reduce_sku)){
                    $upd_logs = $this->Purchase_order_model->purchase_db
                        ->from("product_update_log")
                        ->select("new_supplier_price,sku")
                        ->where_in("sku",$reduce_sku)
                        ->where_not_in("audit_status", [3,4])
                        ->get()->result_array();
                    if($upd_logs && count($upd_logs) > 0){
                        foreach ($upd_logs as $ul_val){
                            $sku_pl[$ul_val['sku']] = $ul_val['new_supplier_price'];
                        }
                    }

                    $product_data = $this->Purchase_order_model->purchase_db
                        ->from("product")
                        ->select("purchase_price,sku")
                        ->where_in("sku",$reduce_sku)
                        ->get()->result_array();
                    if($product_data && count($product_data) > 0){
                        foreach ($product_data as $pd_val){
                            $product_list[$pd_val['sku']] = $pd_val['purchase_price'];
                        }
                    }
                }
                foreach ($purchaseOrder['items_list'] as $i_val){
                    if($autoAuditCheck['code'] && isset($sku_pl[$i_val['sku']]) && $sku_pl[$i_val['sku']] < $i_val['purchase_unit_price']){
                        $sku_reduce = true;
                    }
                    if($autoAuditCheck['code'] && isset($product_list[$i_val['sku']]) && $product_list[$i_val['sku']] < $i_val['product_base_price']){
                        $sku_reduce = true;
                    }
                }
            }
            if($sku_reduce === true){
                $err[$val] = $val.'中含有降价审核中或已降价SKU，请驳回！';
                continue;
            }
            /* ----------------------- SKU降价限制审核通过 --------------------------- */

            $msg = '';
            if($autoAuditCheck['code']){
                $this->Purchase_order_model->change_status($val,PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);
                if($purchasing_order_audit == PUSHING_BLUE_LING)$this->Purchase_order_model->pushing_blue_ling($val);
                $res[] = $val;
                $msg = '自动审核：成功';
                $is_err = false;

                /* --------------- 计算应付款时间 start ----------------- */
                $account_type = $purchaseOrder['account_type'];
                $source = $purchaseOrder['source'];
                $calc_date = $this->calc_pay_time_model->calc_pay_time_audit_service($this->calc_base, $account_type, $source, date("Y-m-d H:i:s"), '');
                if(SetAndNotEmpty($calc_date, 'code') && $calc_date['code'] === true){
                    $this->purchase_db->where(['purchase_number' => $val])->update('purchase_order_pay_type', ['accout_period_time' => $calc_date['data']]);
                    $this->purchase_db->where(['purchase_number' => $val])->update('purchase_order_items', ['need_pay_time' => $calc_date['data']]);
                }
                /* --------------- 计算应付款时间 end ----------------- */
            }else{
                $msg = $autoAuditCheck['message'];
            }
            apiRequestLogInsert(
                [
                    'record_number'    => $val,
                    'record_type'      => '1688一键下单',
                    'post_content'     => '第五步：'.$msg,
                    'response_content' => '',
                    'status'           => '1',
                ],
                'api_request_ali_log'
            );
            if($is_err)$this->update_error_purchase_items($val, $id, $msg."自动审核失败。");
        }
        return $res;
    }

    /**
     * 按照采购单自动请款规则进行自动请款
     */
    private function create_ali_order_payment($data, $userInfo=[], $id=0)
    {
        $res = [];
        $create_notice = '【1688 自动请款】';
        foreach ($data as $val){
            $is_err = true;
            $auto_payout_result = $this->Purchase_auto_payout_model->do_auto_payout($val,$create_notice);
            $msg = '';
            if($auto_payout_result['code']){
                $res[] = $val;
                $msg = '自动请款：成功';
                $is_err = false;
            }else{
                $msg = $auto_payout_result['message'];
            }
            if($is_err)$this->update_error_purchase_items($val, $id, $msg);
            apiRequestLogInsert(
                [
                    'record_number'    => $val,
                    'record_type'      => '1688一键下单',
                    'post_content'     => '第六步：'.$msg,
                    'response_content' => '',
                    'status'           => '1',
                ],
                'api_request_ali_log'
            );
        }
        return $res;
    }

    /**
     * 解锁全部
     */
    public function unlock_suggest($ids){
        $hash_doc = 'PUR_HANDLE_ONE_KEY_CREATE_ORDER';
        foreach ($ids as $v){
            $this->rediss->delHashData($hash_doc, $v);
        }
    }
}