<?php
/**
 * 自动取消相关
 */
class Purchase_cancel_model extends Purchase_model
{
    /**
     * Purchase_cancel_model constructor.
     */
    function __construct()
    {
        parent::__construct();
        $this->load->helper('common');
        $this->load->model('ali/ali_order_refund_model');
        $this->load->model('purchase/purchase_order_determine_model');
    }

    /**
     * 自动取消
     */
    public function auto_cancel($ali_order=false)
    {
        $res = ["code" => 0, "msg" => "默认执行失败"];
        $success = $error = [];
        $ptid_gn = [
            PURCHASE_TYPE_INLAND,
            PURCHASE_TYPE_FBA,
            PURCHASE_TYPE_PFB,
            PURCHASE_TYPE_PFH
        ];
        $ptid_hw = [
            PURCHASE_TYPE_OVERSEA,
            PURCHASE_TYPE_FBA_BIG
        ];
        $w_status = PURCHASE_ORDER_STATUS_WAITING_ARRIVAL;

        $setting = $this->purchase_db->from("param_sets")->where(['pType'=> 'PURCHASE_ORDER_CANCEL_SET', 'pSort'=>1])->get()->result_array();
        if(!$setting || count($setting) == 0){
            $res['msg'] = '未启动任何设置';
            return $res;
        }

        $select_query = [];
        foreach ($setting as $s_val){
            $pValue = json_decode($s_val['pValue'], true);
            $row = ' (pli.`status` < 1 or pli.`status` is null)';
            $row .= $pValue['source'] == 1 ? " and d.suggest_order_status={$w_status} " :" and a.purchase_order_status={$w_status} ";
            if(SetAndNotEmpty($pValue, 'source')){
                $row .= ' and a.source='.$pValue['source'];
            }

            if(SetAndNotEmpty($pValue, 'long_delivery', 'n')){ // 是否超长交期 1否，2是
                $row .= ' and b.is_long_delivery ='.$pValue['long_delivery'];
            }
            if(SetAndNotEmpty($pValue, 'plan_arrive_time', 'n') && $pValue['plan_arrive_time'] > 0){
                $row .= " and b.plan_arrive_time > '".date('Y-m-d 00:00:00',strtotime('+'.$pValue['plan_arrive_time'].' days'))."' ";
            }
            if(SetAndNotEmpty($pValue, 'overdue_day', 'n') && $pValue['overdue_day'] > 0){
                $row .= ' and b.devliery_days > '.$pValue['overdue_day'];
            }
            $select_query[] = " (".$row.") ";
        }

        $select_query = " and (".implode(" or ", $select_query).")";

        /*
        $d7 = date('Y-m-d 00:00:00',strtotime('+8 days'));
        $select_query = "and ((
            a.purchase_type_id in (".implode(',', $ptid_gn).")
            and d.suggest_order_status={$w_status}
            and a.audit_time < '".date('Y-m-d 00:00:00',strtotime('-4 days'))."'
            and (pli.status < 1 or pli.status is null)
        ) or (
            a.purchase_type_id in (".implode(',', $ptid_hw).")
            and d.suggest_order_status={$w_status}
            and (
                a.source=1 or
                (a.source=2 and (pli.status < 1 or pli.status is null))
            )
        )) and (
            (b.plan_arrive_time > '{$d7}' and b.is_long_delivery =1) or (b.devliery_days > 7 and b.is_long_delivery =2)
        ) ";
        */

        if($ali_order && $ali_order != ''){
            $select_query = " and b.id in ({$ali_order}) and d.suggest_order_status={$w_status} ";
        }
        $sql = "select 
            b.id,
            b.confirm_amount,
            b.purchase_unit_price,
            b.is_long_delivery,
            b.plan_arrive_time,
            b.devliery_days,
            a.purchase_type_id,
            a.source,
            a.purchase_number,
            a.audit_time,
            a.purchase_order_status,
            a.account_type,
            pt.freight,
            pt.discount,
            pt.process_cost,
            pt.pai_number
        from pur_purchase_order_items as b 
        inner join pur_purchase_order as a on a.purchase_number=b.purchase_number 
        inner join pur_purchase_suggest as d on b.demand_number=d.demand_number 
        left join pur_purchase_order_pay_type as pt on b.purchase_number=pt.purchase_number 
        left join pur_purchase_order_cancel_detail as cd on b.id=cd.items_id 
        left join pur_ali_order_refund as aor on b.purchase_number=aor.purchase_number 
        left join pur_purchase_logistics_info as pli on b.purchase_number=pli.purchase_number and pli.sku=b.sku 
        where cd.items_id is null and aor.id is null {$select_query}";
        $data = $this->purchase_db->query($sql)->result_array();
        exit($this->purchase_db->last_query());

        if(!$data || !is_array($data) || count($data) < 1){
            $res['msg'] = '没有要取消的数据';
            return $res;
        }

        $temp = [];
        foreach ($data as $val){
            $pur = $val['purchase_number'];
            if(!isset($temp[$pur]))$temp[$pur] = [];
            $temp[$pur][] = $val;
        }

        // 获取结算方式
        $account_data = $this->purchase_db->from("supplier_settlement")->get()->result_array();
        $account_yfk = [];
        $account_xxzq = [];
        foreach ($account_data as &$val){
            if($val['parent_id'] == 33)$account_yfk[] = $val['settlement_code'];
            if($val['parent_id'] == 35 || $val['parent_id'] == 36)$account_xxzq[] = $val['settlement_code'];
        }

        $errorMsg = [];
        foreach ($temp as $key=>$value){
            if(count($value) == 0)continue;
            $row = [
                "source"                => 0,
                "ids"                   => '',
                "total_cancelled"       => 0,
                "total_freight"         => 0,
                "total_process_cost"    => 0,
                "total_discount"        => 0,
                "total_price"           => 0,
                "cancel_ctq"            => [],
                "confirm_amount"        => [],
                "purchase_unit_price"   => [],
                "instock_qty"           => [],
                "loss_amount"           => [],
                "application_cancel_ctq"=> [],
                "freight"               => [],
                "create_note"           => [],
            ];
            $ids = [];
            $t_freight = $t_process_cost = $t_discount = 0;
            $pai_number = '';
            $source = false;
            $account_type = false;
            foreach ($value as $val){
                $p_number = $val['pai_number'];    // 拍单号
                $source = $val['source'];    // 采购来源
                $account_type = $val['account_type'];    // 结算方式
                $long_d = $val['is_long_delivery']; // 是否超长交期
                $pa_time = $val['plan_arrive_time']; // 预计到货时间
                $d_day = $val['devliery_days']; // 逾期天数
                $id = $val['id'];
                $confirm_qty = $val['confirm_amount']; // 采购数量
                $unit_price = $val['purchase_unit_price']; // 采购单价
                $row['source'] = $source;
                $d7 = date('Y-m-d 00:00:00',strtotime('+8 days'));
                if(($long_d == 1 && $pa_time > $d7) || ($long_d == 2 && $d_day > 7)){
                    $ids[]                              = $id;
                    $row['total_cancelled']             += $confirm_qty;
                    $row['total_price']                 += round($confirm_qty * $unit_price, 3);
                    $row['cancel_ctq'][$id]             = 0;
                    $row['confirm_amount'][$id]         = $confirm_qty;
                    $row['purchase_unit_price'][$id]    = $unit_price;
                    $row['instock_qty'][$id]            = 0;
                    $row['loss_amount'][$id]            = 0;
                    $row['application_cancel_ctq'][$id] = $confirm_qty;
                    $row['freight'][$id]                = 0;
                    $row['create_note'][$id]            = '系统自动发起';

                    $t_freight = $val['freight'];
                    $t_process_cost = $val['discount'];
                    $t_discount = $val['process_cost'];

                    if(empty($pai_number))$pai_number = $p_number;
                }
            }

            if(count($ids) == 0)continue;
            $row['ids'] = implode(",", $ids);
            // 取消未到货
            if($source == 1 || $ali_order){
                $cancel_all = $this->purchase_db->from("purchase_order_items as it")
                    ->select("it.id")
                    ->join('pur_purchase_order as o', "o.purchase_number=it.purchase_number", "inner")
                    ->where(['o.purchase_order_status'=> $w_status, "o.purchase_number" => $key])
                    ->get()
                    ->result_array();
                if($ali_order || ($cancel_all && count($cancel_all) > 0 && count($ids) == count($cancel_all))){
                    $row['total_freight']       = $t_freight;
                    $row['total_process_cost']  = $t_process_cost;
                    $row['total_discount']      = $t_discount;
                }

                $row['status'] = null;
                if($source == 1 && $account_type){
                    if(in_array($account_type, $account_yfk))$row['status'] = PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT;
                    if(in_array($account_type, $account_xxzq))$row['status'] = PURCHASE_ORDER_STATUS_CANCELED;
                }

                unset($row['source']);
                $res_pc = $this->purchase_order_determine_model->get_cancel_unarrived_goods_save($row);
                $errorMsg = $res_pc;
                if($res_pc['bool']){
                    $success[] = $key;
                }else{
                    $error[] = isset($res_pc['msg']) ? $res_pc['msg']: $key."取消失败";
                }
            }

            // 1688退款退货
            if($pai_number != '' && $source == 2 && !$ali_order){
                $res_ar = $this->auto_refund($pai_number);
                if($res_ar['code'] == 1){
                    $success[] = $key;
                }else{
                    $error[] = $key;
                }
            }
        }
        $res['code'] = 1;
        $res['msg'] = [
            "success"   => $success,
            "error"     => $error,
            "errorMsg"     => $errorMsg,
        ];
        return $res;
    }

    /**
     * 自动发起1688退款退货
     */
    private function auto_refund($order_id=null, $purchase_number='', $ids='')
    {
        $res = ["code" => 0, "msg" => '默认处理失败!'];
        if(empty($order_id)){
            $res['msg'] = '退款拍单号不能为空';
            return $res;
        }
        $res = $this->Ali_order_refund_model->get_order_refund_data($order_id);
        $subItemId = [];
        $refund_total = 0;
        $goods_status = [];
        if(SetAndNotEmpty($res, 'ali_order') && count($res['ali_order']) > 0){
            foreach ($res['ali_order'] as $val){
                if(SetAndNotEmpty($val, 'goods_status'))$goods_status[] = $val['goods_status'];
                if(SetAndNotEmpty($val, 'sub_order_id'))$subItemId[] = [
                    'sub_order_id'  => $val['sub_order_id'],
                    'number'        => $val['quantity'],
                ];
                if(SetAndNotEmpty($val, 'pay_price'))$refund_total += $val['pay_price'];
            }
        }
        // 退款原因 20006 不想买了, 20002 卖家缺货, 20003 卖家不支持在线交易, 20004 未按约定时间发货, 20005 卖家调价
        $query = [
            "order_id"          => $order_id,
            "refund_type"       => 1,
            "refund_reason"     => 20002,
            "goods_status"      => 'refundWaitSellerSend',
            "refund_total"      => $refund_total,
            "refund_ship"       => isset($res['ship_price']) ? $res['ship_price'] : 0,
            "remarks"           => '卖家缺货',
            "images"            => [],
            "ali_order"         => $subItemId,
        ];

        $save = $this->Ali_order_refund_model->save_and_send_refund_data($query, true);
        $success = 'fail';
        $res['msg'] = isset($save['msg']) ? $save['msg']: $save;
        if($save['code'] == 1){
            $res['code'] = 1;
            $success = 'success';
        }
        if($purchase_number && $ids && $save['code'] == 1){
            $this->purchase_db->where(["pai_number"=> $order_id, "purchase_number"=> $purchase_number])->update("", ["wait_cancel_id" => $ids]);
        }
        return "order_id[{$order_id}] is {$success}";
    }

    /**
     * 如果订阅消息中，订单退款状态满足条件的，自动申请取消未到货
     */
    public function ali_order_auto_cancel()
    {
        $res = ["code" => 0, "msg" => '默认处理失败!'];
        $data = $this->purchase_db->from("ali_order_refund as ar")
            ->select("ar.*,o.account_type")
            ->join("pur_purchase_order as o", "o.purchase_number=ar.purchase_number", "inner")
            ->where(["ar.apply_source" => 1, "ar.apply_handle" => 0])
            ->where_in("ar.refund_status", ["refundsuccess", "refundclose"])
            ->get()
            ->row_array();
        if(!$data || !SetAndNotEmpty($data, 'wait_cancel_id')){
            $res['msg'] = '没有要处理的数据！';
            return $res;
        }

        // 缓存验证直接通过的数据
        if(SetAndNotEmpty($data, 'account_type') && in_array($data['account_type'], [20, 34])){
        }

        return $this->auto_cancel($data['wait_cancel_id']);
    }
}