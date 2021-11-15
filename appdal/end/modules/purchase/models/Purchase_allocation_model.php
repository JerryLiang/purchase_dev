<?php
/**
 * Created by PhpStorm.
 * 多货入库调拨
 * @author yefanli
 */
class Purchase_allocation_model extends Purchase_model
{
    protected $order_table = 'purchase_order';
    protected $suggest_table = 'purchase_suggest';
    protected $results_table = 'warehouse_results';


    /**
     * 获取多货调拨显示数据
     */
    public function get_order_sku_allocation_base($demand_number, $purchase_number, $sku)
    {
        $res = ['code' =>0, "msg" =>''];
        if(empty($purchase_number)){
            $res['msg'] = "采购单信息不能为空!";
            return $res;
        }
        if(empty($demand_number) || empty($sku) || is_array($demand_number)){
            $res['msg'] = "备货单不能为空，且只能选择一条备货单进行操作！";
            return $res;
        }

        $multiple = $this->purchase_db->from('multiple_transfer')->select('id')
            ->where(['purchase_number' => $purchase_number, 'demand_number' => $demand_number])
            ->where_in('audit_status', [1, 2])
            ->get()->result_array();
        if($multiple && count($multiple) > 0){
            $res['msg'] = "该备货单已申请调拨，请勿重复申请！";
            return $res;
        }

        // 获取对应的主体信息
        $purchase = $this->get_allocation_purchase_info($purchase_number, $sku);
        if(!$purchase){
            $res['msg'] = "没有对应的采购单信息！";
            return $res;
        }

        // 备货单信息验证
        $demand = $this->get_demand_info_by_one($demand_number, $sku);
        if(!$demand || !isset($demand['sku'])){
            $res['msg'] = "没有该备货单信息！";
            return $res;
        }
        if(!isset($demand['purchase_type_id']) || in_array($demand['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){
            $res['msg'] = "请选择非海外仓的备货单进行操作！";
            return $res;
        }
        if(!isset($demand['suggest_order_status']) || !in_array($demand['suggest_order_status'], [7, 10])){
            $res['msg'] = "请选择备货单状态为“等待到货”或“部分到货等待剩余到货”的备货单进行操作！";
            return $res;
        }

        // 结算信息
        $account_type = $this->get_ratio_by_type();
        $account = [];
        foreach ($account_type as $val){
            if(isset($val['settlement_code']) && $purchase['account_type'] == $val['settlement_code'])$account = $val;
        }

        // 仓库信息
        $warehouse = $this->get_warehouse_info($purchase['warehouse_code']);
        $ret = [];
        $ret["purchase_number"]         = $purchase_number;     // 采购单号
        $ret["demand_number"]           = $demand_number;   // 备货单号
        $ret["sku"]                     = $sku;     // SKU
        $ret["sku_images"]               = $this->get_product_image($sku);     // SKU
        $ret["supplier_name"]           = isset($demand['supplier_name'])?$demand['supplier_name']: '';   // 供应商名称
        $ret["supplier_code"]           = isset($demand['supplier_code'])?$demand['supplier_code']: '';   // 供应商code
        $ret["suggest_order_status"]    = isset($demand['suggest_order_status'])?getPurchaseStatus($demand['suggest_order_status']): '';   // 备货单状态
        $ret["buyer_name"]              = isset($purchase['buyer_name'])?$purchase['buyer_name']: '';   // 采购员
        $ret["account_type"]            = isset($purchase['account_type']) && isset($account['settlement_name'])?$account['settlement_name']: '';   // 结算方式
        $ret["pay_type"]                = isset($purchase['pay_type'])?getPayType($purchase['pay_type']): '';   // 支付方式
        $ret["warehouse_code"]          = $warehouse && isset($warehouse['warehouse_name'])?$warehouse['warehouse_name']: '';   // 采购仓库
        $ret["confirm_amount"]          = isset($purchase['confirm_amount'])?(int)$purchase['confirm_amount']: 0;   // 采购数量
        $ret["upselft_amount"]          = isset($purchase['upselft_amount'])?(int)$purchase['upselft_amount']: 0;   // 入库数量
        $ret["pay_status"]              = isset($purchase['pay_status'])?getPayStatus($purchase['pay_status']): '';   // 付款状态
        $ret["audit_time"]              = isset($purchase['audit_time'])?$purchase['audit_time']: '';   // 审核时间

        // 获取取消/报损数量
        $ret["cancel_num"]              = $this->get_purchase_order_cancel($purchase_number, $sku);   // 取消数量
        $ret["loss_num"]                = $this->get_purchase_order_loss($purchase_number, $sku);   // 报损数量
        $ret["account_ratio"]           = isset($purchase['account_type']) && isset($account['settlement_percent']) && !empty($account['settlement_percent'])?$account['settlement_percent']: 0;   // 结算比例
        $ret["instock_qty_more"]        = $ret["confirm_amount"] - $ret["upselft_amount"] - $ret["cancel_num"] - $ret["loss_num"];   // 需调拨数量
        $ret["allocation_list"]         = [];

        // 获取可调拨多货数量信息
        $order_info = $this->get_allocation_order_num($sku);
        if(!$order_info || count($order_info) == 0){
            $res['msg'] = "SKU ".$demand['sku']." 最近40天入库没有可供调拨的多货！";
            return $res;
        }
        $x = 1;
        $multiple_status = [
            1 => '未处理',
            2 => '部分已处理',
            3 => '全部已处理',
        ];
        foreach ($order_info as $val){
            if($val['purchase_number'] == $purchase_number)continue;
            $val['status'] = isset($val['status']) && in_array($val['status'], array_keys($multiple_status))?$multiple_status[$val['status']]: $multiple_status[1];
            $val['pay_type'] = isset($val['pay_type'])? getPayType($val['pay_type']): '';
            $val['account_type'] = '';
            foreach ($account_type as $v){
                if(isset($v['settlement_code']) && isset($val['account_type']) && $v['settlement_code'] == $val['account_type'])$val['account_type'] = $v['settlement_name'];
            }
            $val['pay_status'] = isset($val['pay_status'])? getPayStatus($val['pay_status']): '';

            $all = isset($val['total_num']) && !empty($val['total_num']) ? $val['total_num'] : 0;
            $transfer = isset($val['transfer_num']) && !empty($val['transfer_num']) ? $val['transfer_num'] : 0;
            $return = isset($val['return_num']) && !empty($val['return_num']) ? $val['return_num'] : 0;
            $val['surplus'] = 0; // 剩余调拨数量
            if($all > 0 && ($all - $transfer - $return) > 0)$val['surplus'] = $all - $transfer - $return;

            $val['id'] = $x;
            unset($val['create_time']);
            $ret["allocation_list"][] = $val;
            $x ++;
        }

        if(count($ret["allocation_list"]) == 0){
            $res['msg'] = "没有可供调出的多货！";
            return $res;
        }
        $res['code'] = 1;
        $res['msg'] = $ret;
        return $res;
    }

    /**
     * 保存调拨数据
     */
    public function save_order_sku_allocation($params)
    {
        $res = ["code" => 0,"msg" => ""];
        $this->load->model('abnormal/Multiple_goods_model');
        try {
            $this->purchase_db->trans_begin();
            $transfer_number = $this->Multiple_goods_model->get_prefix_new_number('db');
            if(!$transfer_number || empty($transfer_number))throw new Exception('调拨失败，生成调拨编号失败。');

            $now = date("Y-m-d H:i:s");
            $main_query = [
                'transfer_number'   => $transfer_number,
                'transfer_quantity' => $params['surplus'],
                'purchase_number'   => $params['purchase_number'],
                'demand_number'     => $params['demand_number'],
                'sku'               => $params['sku'],
                'audit_status'      => 1,
                'create_time'       => $now,
                'apply_id'          => getActiveUserId(),
                'apply_name'        => getActiveUserName(),
            ];
            $this->purchase_db->insert('multiple_transfer', $main_query);
            foreach ($params['out_surplus'] as $key=>$val){
                $multiple_number = '';
                $quantity = 0;
                if(gettype($val) == 'string')$val = json_decode($val, true);
                foreach ($val as $k=>$v){
                    $multiple_number = $k;
                    $quantity = $v;
                }
                if($quantity <1 || empty($multiple_number))continue;
                $multiple = $this->purchase_db->from('multiple_goods')->where('multiple_number = ', $multiple_number)->get()->row_array();
                $our_pur = isset($multiple['purchase_number'])?$multiple['purchase_number']: ' '; // 调出采购单号
                $sub_query = [
                    'multiple_number'   => $multiple_number,
                    'quantity'          => $quantity,
                    'purchase_number'   => $our_pur,
                    'transfer_number'   => $transfer_number,
                    'create_time'       => $now,
                ];

                $this->purchase_db->insert('multiple_transfer_detail', $sub_query);
            }
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                throw new Exception('调拨失败，保存数据失败。');
            }else{
                $this->purchase_db->trans_commit();
                $res["code"] = 1;
                $res["msg"] = "调拨申请成功！";
            }
        }catch (Exception $e){
            $this->purchase_db->trans_rollback();
            $res["msg"] = "调拨失败，原因：".$e->getMessage();
        }
        return $res;
    }

    /**
     * 校验调拨数据
     */
    public function verify_allocation_info($params)
    {
        $res = ["code"=>0, "msg"=>''];
        $cancel_all = 0;
        $multiple_list = [];
        foreach ($params['out_surplus'] as $val){
            if(gettype($val) == 'string')$val = json_decode($val, true);
            foreach ($val as $k=>$v){
                $cancel_all += $v;
                $multiple_list[] = $k;
            }
        }
        if($params['surplus'] != $cancel_all){
            $res['msg'] = '调入数量必须等于调出数量。';
            return $res;
        }
        if(count($multiple_list) == 0){
            $res['msg'] = '调出单信息不能为空。';
            return $res;
        }

        // 验证基本数据
        $allocation = $this->get_order_sku_allocation_base($params['demand_number'],$params['purchase_number'],$params['sku']);
        if(!$allocation || !isset($allocation['code']) || $allocation['code'] == 0)return $allocation;
        $allocation = $allocation['msg'];

        // 验证调拨数量
        if(!isset($params['surplus']) || empty($params['surplus']) || !isset($allocation['instock_qty_more']) || empty($allocation['instock_qty_more'])){
            $res['msg'] = '调入数量必须大于0';
            return $res;
        }

        $can_use = 0; // 最大允许调拨数量
        $allocation_list = [];
        foreach ($allocation['allocation_list'] as $val){
            $can_use = $can_use + ($val['total_num'] - $val['transfer_num'] - $val['return_num']);
            $val['sku'] = $allocation['sku'];
            $allocation_list[] = $val;
        }
        if($params['surplus'] > $can_use){
            $res['msg'] = '调入数量大于允许调出数量之和。';
            return $res;
        }
        if($params['surplus'] != $allocation['instock_qty_more']){
            $res['msg'] = '入库、报损、取消未到货在调拨在操作期间发生变化，请刷新后重新调拨。';
            return $res;
        }

        $qty_more = $this->get_oup_surplus_info($multiple_list);
        if($qty_more <= 0){
            $res['msg'] = '没有相应的多货入库供调拨。';
            return $res;
        }
        if($qty_more < $params['surplus'] || $qty_more < $cancel_all){
            $res['msg'] = '可调出数量发生了变化，刷新后重新调拨。';
            return $res;
        }
        // 需调拨数量 =采购数量-取消数量-入库数量-报损数量
        /*
        if($qty_more != $allocation['instock_qty_more']){
            $res['msg'] = '需调拨数量，应等于采购数量-取消数量-入库数量-报损数量。';
            return $res;
        }
        // 多个多货单的总调出数量 =采购数量-取消数量-入库数量-报损数量
        if($qty_more != $params['surplus']){
            $res['msg'] = '多个多货单的总调出数量，应等于调出的备货单采购数量-取消数量-入库数量-报损数量。';
            return $res;
        }
        */

        // 验证通过
        $res['code'] = 1;
        return $res;
    }

    /**
     * 获取调出单的相应信息
     */
    public function get_oup_surplus_info($multiple_list)
    {
        $res = 0;
        try {
            $data = $this->purchase_db->from('multiple_goods')->select('total_num')
                ->where_in('multiple_number', $multiple_list)
                ->get()->result_array();
            if($data && count($data) > 0){
                foreach ($data as $val){
                    $res = $res + (int)$val['total_num'];
                }
            }
            /*
            // 采购数量
            $purchase = $this->purchase_db->from('purchase_order_items')->select('confirm_amount')
                ->where(['purchase_number' => $purchase_number, 'sku' => $sku])
                ->get()->row_array();
            $purchase_qty = isset($purchase['confirm_amount']) && $purchase['confirm_amount'] >0?$purchase['confirm_amount']:0;

            // 入库数量
            $instock = $this->purchase_db->from('warehouse_results_main')->select('instock_qty')
                ->where(['purchase_number' => $purchase_number, 'sku' => $sku])
                ->get()->row_array();
            $instock_qty = isset($instock['instock']) && $instock['instock']>0?$instock['instock']: 0;

            // 报损数量
            $breakage = $this->purchase_db->from('purchase_order_reportloss')
                ->select('sum(loss_amount) as loss_amount')
                ->where(['pur_number' => $purchase_number, 'sku'=>$sku, 'status' => 4])
                ->get()->row_array();
            $breakage_qty = isset($breakage['loss_amount']) && $breakage['loss_amount'] > 0? $breakage['loss_amount']:0;

            // 取消数量
            $cancel = $this->purchase_db->from('purchase_order_cancel_detail as d')
                ->join('pur_purchase_order_cancel c', 'c.id=d.cancel_id', 'inner')
                ->select('sum(d.cancel_ctq) as cancel_ctq')
                ->where(['d.purchase_number' => $purchase_number, 'd.sku' => $sku])
                ->where_in('c.audit_status', [60, 70])
                ->get()->row_array();
            $cancel_qty = isset($cancel['cancel_ctq']) && !empty($cancel['cancel_ctq'])?(int)$cancel['cancel_ctq']:0;
            $res = $purchase_qty - $instock_qty - $breakage_qty - $cancel_qty;
            */
        }catch (Exception $e){}

        return $res;
    }

    /**
     * 获取备货单信息
     */
    public function get_demand_info_by_one($demand_number, $sku)
    {
        $res = $this->purchase_db->from($this->suggest_table)
            ->where(['demand_number'=> $demand_number, 'sku'=>$sku])
            ->get()
            ->row_array();
        if($res)return $res;
        return false;
    }

    /**
     * 获取多货信息
     */
    public function get_allocation_order_num($sku)
    {
        try {
            $filed = 'm.*,o.supplier_code,o.supplier_name,o.pay_status,o.account_type,o.pay_type,o.buyer_name';
            $daytime= date("Y-m-d H:i:s", strtotime("-40 day"));
            $instock = $this->purchase_db->from("multiple_goods as m")
                ->join('pur_purchase_order as o', 'm.purchase_number=o.purchase_number', "inner")
                ->select($filed)
                ->where('m.sku =', $sku)
                ->where("m.instock_date >", $daytime)
                ->where("m.total_num >", 0)
                ->get()
                ->result_array();
            if($instock && count($instock) > 0)return $instock;
        }catch (Exception $e){}
        return false;
    }

    /**
     * 获取多货对应的采购单信息
     */
    public function get_allocation_purchase_info($purchase_number, $sku)
    {
        $purchase = $this->purchase_db->from($this->order_table.' as o')
            ->join('pur_purchase_order_items as it', 'o.purchase_number=it.purchase_number', 'left')
            ->where(["o.purchase_number"=>$purchase_number, 'it.sku'=>$sku])
            ->get()
            ->row_array();
        if($purchase)return $purchase;
        return false;
    }

    /**
     * 获取采购单+sku的取消数量
     */
    public function get_purchase_order_cancel($purchase_number, $sku)
    {
        try {
            $data = $this->purchase_db->from('purchase_order_cancel_detail as d')
                ->join('pur_purchase_order_cancel as c', 'd.cancel_id=c.id', 'inner')
                ->select('sum(d.cancel_ctq) as cancel_ctq')
                ->where(['d.purchase_number'=> $purchase_number, 'd.sku'=>$sku])
                ->where_in('c.audit_status', [CANCEL_AUDIT_STATUS_CFYSK, CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
                ->group_by('d.sku')
                ->get()
                ->row_array();
            return isset($data['cancel_ctq']) && $data['cancel_ctq'] > 0? $data['cancel_ctq']:0;
        }catch (Exception $e){}
        return 0;
    }

    /**
     * 获取采购单+sku的报损数量
     */
    public function get_purchase_order_loss($purchase_number, $sku)
    {
        try {
            $data = $this->purchase_db->from('purchase_order_reportloss')
                ->select('sum(loss_amount) as loss_amount')
                ->where(['pur_number' => $purchase_number, 'sku' => $sku, 'status' => REPORT_LOSS_STATUS_FINANCE_PASS])
                ->group_by('sku')
                ->get()
                ->row_array();
            return isset($data['loss_amount']) && $data['loss_amount'] > 0?$data['loss_amount']:0;
        }catch (Exception $e){}
        return 0;
    }

    /**
     * 根据结算方式获取结算比例
     */
    public function get_ratio_by_type()
    {
        $data = $this->purchase_db->from('supplier_settlement')->get()->result_array();
        return $data?$data:false;
    }

    /**
     * 获取仓库信息
     */
    public function get_warehouse_info($code)
    {
        try {
            $data = $this->purchase_db->from('warehouse')
                ->where("warehouse_code =", $code)
                ->get()
                ->row_array();
            return $data && isset($data['warehouse_name'])? $data: false;
        }catch (Exception $e){}
        return false;
    }

    /**
     * 获取产品主图
     */
    public function get_product_image($sku)
    {
        if(empty($sku))return '';
        $data = $this->purchase_db->from('product')
            ->select('product_img_url')
            ->where('sku=', $sku)
            ->get()
            ->row_array();
        return isset($data['product_img_url'])?erp_sku_img_sku($data['product_img_url']): '';
    }
}