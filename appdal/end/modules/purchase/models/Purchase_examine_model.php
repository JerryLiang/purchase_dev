<?php

/**
 * User: yefanli
 * Date: 2021/02/22
 */
class Purchase_examine_model extends Purchase_model
{
    /**
     * Purchase_examine_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取审核信息
     */
    public function get_examine_data($purchase=[], $ids=[], $check_status=0)
    {
        $res = ["success" => [], "error" => ""];

        $base = $this->purchase_db->from("pur_purchase_order_items as it")
            ->join("pur_purchase_order as o", "o.purchase_number=it.purchase_number", "inner")
            ->join('pur_product as pb', 'it.sku=pb.sku', 'inner');
        $select = 'o.*,it.sku,it.purchase_unit_price,it.product_base_price,pb.purchase_price,pb.is_drawback as pd_is_drawback,pb.supplier_code as pd_supplier_code';
        if(!empty($purchase)){
            $base->where_in("o.purchase_number", $purchase);
        }
        if(!empty($ids)){
            $base->where_in("it.id", $ids);
        }
        $data = $base->select($select)->get()->result_array();
        $supplier_code = array_column($data, "supplier_code");
        $sku = array_unique(array_column($data, "sku"));
        $supplier = [];
        $err_pur = [];
        if(!empty($supplier_code)){
            $supplier_temp = $this->purchase_db->from("pur_supplier")
                ->select("supplier_code,status,supplier_source")
                ->where_in("supplier_code", $supplier_code)
                ->get()->result_array();
            if($supplier_temp && !empty($supplier_temp)){
                foreach ($supplier_temp as $val){
                    $supplier[$val['supplier_code']] = $val;
                }
            }
        }
        if($data && !empty($data)){
            // sku 降价则不允许验证通过
            $update_log = $this->purchase_db->from("product_update_log")
                ->select("new_supplier_price,sku")
                ->where_in("sku",$sku)
                ->where_not_in("audit_status", [3,4])
                ->get()->result_array();
            $sku_ul = [];
            $sku_pl = [];
            if(count($update_log) > 0){
                $sku_ul = array_unique(array_column($update_log, 'sku'));
                foreach ($update_log as $val){
                    $sku_pl[$val['sku']] = $val['new_supplier_price'];
                }
            }

            $temp = [];
            $order_supplier = [];
            $sku_supplier = [];
            $pur_down = [];
            foreach ($data as $val){
                $pur = $val['purchase_number'];
                $sup_code = $val['supplier_code'];
                if(!in_array($pur, array_keys($order_supplier)))$order_supplier[$pur] = [];
                if(!in_array($sup_code, $order_supplier[$pur]))$order_supplier[$pur][] = $sup_code;

                if(!in_array($pur, array_keys($sku_supplier)))$sku_supplier[$pur] = [];
                if(!in_array($val['pd_supplier_code'], $sku_supplier[$pur]))$sku_supplier[$pur][] = $val['pd_supplier_code'];

                if(!in_array($pur, $temp))$temp[] = $pur;

                $err_str = [];
                if($val['purchase_order_status'] != PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT)$err_str[] = '只有待经理审核状态才需要审核';
                if($check_status == 1 && !in_array($sup_code, array_keys($supplier)))$err_str[] = '找不到绑定的供应商';
                if(in_array($sup_code, array_keys($supplier)) && in_array($supplier[$sup_code]['status'], [IS_BLACKLIST, IS_DISABLE]) && $check_status != 2)$err_str[] = '供应商已经禁用或者加入黑名单，无法审核通过';
                if(in_array($sup_code, array_keys($supplier)) && $supplier[$sup_code]['supplier_source'] == 3){
                    $supplier_source = $this->purchase_order_model->temporary_supplier_order_number($sup_code, $val['source'], false);
                    if(!empty($supplier_source))$err_str[] = implode("，", $supplier_source);
                }

                $sku = $val['sku'];
                if($check_status == 1 && isset($sku_pl[$sku]) && $sku_pl[$sku] < $val['purchase_unit_price']){
                    $err_str[] = "中 SKU：{$sku}在降价审核中，请驳回采购单：{$pur}";
                }
                if($check_status == 1 && $val['purchase_price'] < $val['product_base_price']){
                    $err_str[] = "中 SKU：{$sku}已降价，请驳回采购单：{$pur}";
                }

                if(!empty($err_str)){
                    $res['error'] = $pur.implode("，", $err_str);
                    $err_pur[] = $pur;
                }
            }

            $suggest = $this->purchase_db->select('su.is_drawback,map.purchase_number,su.sku')
                ->from('purchase_suggest as su')
                ->join('purchase_suggest_map as map', 'map.demand_number=su.demand_number', 'inner')
                ->where('map.purchase_number in ("'.implode('","', $temp).'")')
                ->get()
                ->result_array();
            $suggest_temp = [];
            if($suggest && !empty($suggest)){
                foreach ($suggest as $val){
                    $pur_s = $val['purchase_number'];
                    $id_d = $val['is_drawback'];
                    if(!in_array($pur_s, array_keys($suggest_temp)))$suggest_temp[$pur_s] = [];
                    if(!in_array($id_d, $suggest_temp[$pur_s]))$suggest_temp[$pur_s][] = $id_d;
                }
            }

            if(!empty($suggest_temp)){
                foreach ($suggest_temp as $k=>$v){
                    if(count($v) <= 1 || $check_status == 2)continue;
                    if(!empty($res['error'])){
                        $res['error'] = $res['error']."存在SKU是否退税不一致情况";
                    }else{
                        $res['error'] = '采购单号'.$k."存在SKU是否退税不一致情况";
                    }
                    $err_pur[] = $k;
                }
            }

            if(!empty($order_supplier)){
                foreach ($order_supplier as $k=>$v){
                    if(count($v) <= 1 || $check_status == 2)continue;
                    if(!empty($res['error'])){
                        $res['error'] = $res['error']."采购单号存在SKU供应商不一致情况";
                    }else{
                        $res['error'] = '采购单号'.$k."存在SKU供应商不一致情况";
                    }
                    $err_pur[] = $k;
                }
            }


            if(!empty($sku_supplier)){
                foreach ($sku_supplier as $k=>$v){
                    if(count($v) <= 1 || $check_status == 2)continue;
                    if(!empty($res['error'])){
                        $res['error'] = $res['error']."采购单与备货单供应商不一致";
                    }else{
                        $res['error'] = '采购单号'.$k."采购单与备货单供应商不一致";
                    }
                    $err_pur[] = $k;
                }
            }

            // 返回可审核的采购单
            $has_list = [];
            foreach ($data as $val){
                $temp_pur = $val['purchase_number'];
                if(!in_array($temp_pur, $err_pur) && !in_array($temp_pur, $has_list)){
                    $has_list[] = $temp_pur;
                    $res['success'][] = $val;
                }
            }
        }
        return $res;
    }

    /**
     * 批量获取采购单数据
     */
    public function get_order($purchase=[], $use_items = true)
    {
        $res = [];
        if(empty($purchase))return $res;
        $data = $this->purchase_db->from('purchase_order')->where_in("purchase_number", $purchase)->get()->result_array();
        if(!$data || empty($data))return $res;
        $items = [];
        if($use_items){
            $items = $this->purchase_db->from('purchase_order_items')->where_in("purchase_number", $purchase)->get()->result_array();
        }

        foreach ($data as $val){
            $pur = $val['purchase_number'];
            $val["items_list"] = [];
            if($use_items && !empty($items)){
                foreach ($items as $i_val){
                    if($i_val['purchase_number'] == $pur)$val["items_list"][] = $i_val;
                }
            }
            $res[$pur] = $val;
        }

        return $res;
    }
}