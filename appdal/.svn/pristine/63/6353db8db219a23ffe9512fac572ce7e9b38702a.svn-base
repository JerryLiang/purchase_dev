<?php

/**
 * 包材入库
 * Class Package_instock_model
 */
class Package_instock_model extends Purchase_model
{


    /**
     * 包材信息入库
     */
    public function package_instock($params)
    {
        $res = ["code"  => 0, "success" => [], "error" => [], "msg" => ''];
        $success = [];
        // 获取item id
        $insert = [];
        foreach ($params as $val){
            $query = ["purchase_number" => $val['purchase_number'], "sku" => $val['sku']];
            $items = $this->purchase_db->select("id,confirm_amount")->from("pur_purchase_order_items")->where($query)->get()->row_array();
            $id = isset($items['id'])?$items['id']: '';
            $val['items_id'] = $id;
            $val['purchase_qty'] = isset($items['confirm_amount'])?$items['confirm_amount']: 0;
            $val['qurchase_num'] = $val['purchase_qty'];

            $is_false = true;
            $this->purchase_db->trans_begin();
            try{
                // 如果main没有数据，则新增
                $main = $this->purchase_db->from("pur_warehouse_results_main")->where($query)->get()->result_array();
                if(!$main || count($main) == 0){
                    // 入库主表信息
                    $this->purchase_db->insert("pur_warehouse_results_main", [
                        "items_id"          => $id,
                        "purchase_number"   => $val['purchase_number'],
                        "sku"               => $val['sku'],
                        "purchase_qty"      => isset($items['confirm_amount'])?$items['confirm_amount']:0,
                        "arrival_qty"       => 0,
                        "arrival_date"      => '0000-00-00 00:00:00',
                        "bad_qty"           => 0,
                        "breakage_qty"      => 0,
                        "instock_qty"       => 0,
                        "instock_date"      => '0000-00-00 00:00:00',
                        "check_qty"         => 0,
                        "create_time"       => date("Y-m-d H:i:s"),
                        "update_time"       => '0000-00-00 00:00:00',
                        "instock_qty_more"  => 0,
                    ]);
                }

                $this->purchase_db->trans_commit();
                if($this->purchase_db->trans_status() === false){
                    throw new Exception("提交事务失败！");
                }
            }catch (Exception $e){
                $is_false = false;
                $this->purchase_db->trans_rollback();
                $res['msg'] = $e->getMessage();
            }
            if($is_false){
                $success[] = $val;
                $res['success'][] = $val['instock_batch'];
            }else{
                $res['error'][] = $val['instock_batch'];
            }
            $val['create_time'] = date("Y-m-d H:i:s");
            $val['instock_node'] = 100;
            $insert[] = $val;
        }

        // 批量入库, 不分原本有没有，允许多次入库
        $this->purchase_db->insert_batch("pur_warehouse_results", $insert);

        $res['code'] = 1;
        $res['msg'] = "入库成功";

        // 更新入库信息主表和计算采购单状态
        if(count($success) > 0)$this->compute_purchase_status($insert);

        return $res;
    }

    /**
     * 入库完成后变更采购单状态
     */
    public function compute_purchase_status($params)
    {
        // 根据入库详情计算main表
        try{
            $this->purchase_db->trans_begin();
            foreach ($params as $val){
                $pur = $val['purchase_number'];
                $sku = $val['sku'];
                $sql = "select sum(arrival_qty) as arrival_qty, max(arrival_date) as arrival_date, sum(bad_qty) as bad_qty, 
                sum(breakage_qty) as breakage_qty, sum(instock_qty) as instock_qty, max(instock_date) as instock_date, 
                sum(check_qty) as check_qty from pur_warehouse_results where purchase_number = '".$val['purchase_number']."' 
                and sku = '".$val['sku']."' group by purchase_number,sku";
                $one = $this->purchase_db->query($sql)->row_array();

                $update_main = [
                    "arrival_qty"       => $one['arrival_qty'] ?? 0,
                    "arrival_date"      => $one['arrival_date'] ?? 0,
                    "bad_qty"           => $one['bad_qty'] ?? 0,
                    "breakage_qty"      => $one['breakage_qty'] ?? 0,
                    "instock_qty"       => $one['instock_qty'] ?? 0,
                    "instock_date"      => $one['instock_date'] ?? 0,
                    "check_qty"         => $one['check_qty'] ?? 0
                ];

                $update_query = ["purchase_number" => $pur, "sku" => $sku];
                $this->purchase_db->update("pur_warehouse_results_main", $update_main, $update_query);

                $order_items = [
                    "receive_amount" => $update_main['arrival_qty'],
                    "upselft_amount" => $update_main['instock_qty'],
                ];
                $this->purchase_db->update("pur_purchase_order_items", $order_items, $update_query);
            }

            $this->purchase_db->trans_commit();
        }catch (Exception $e){
            $this->purchase_db->trans_rollback();
        }

        // 根据入库详情，计算采购单和备货单状态
        try{
            $pur_number = array_column($params, 'purchase_number');
            $sql = "select it.purchase_number,it.demand_number,it.confirm_amount,m.instock_qty from pur_purchase_order_items as it 
                left join pur_warehouse_results_main as m on it.purchase_number=m.purchase_number and it.sku=m.sku 
                where it.purchase_number in ('".implode("','", $pur_number)."');";

            $list = $this->purchase_db->query($sql)->result_array();
            if(!$list || empty($list))throw new Exception('没有要更新的数据');
            $purchase = [];
            foreach ($list as $val){
                // 计算并更新备货单状态
                $suggest_status = 0;
                if($val['instock_qty'] >=  $val['confirm_amount'] && $val['instock_qty']  >0)$suggest_status = PURCHASE_ORDER_STATUS_ALL_ARRIVED;  // 全部到货
                if($val['instock_qty'] <  $val['confirm_amount'] && $val['instock_qty']  >0)$suggest_status = PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE;  // 部分到货等待剩余.
                if($suggest_status > 0)$this->purchase_db->update("purchase_suggest", ["suggest_order_status" => $suggest_status], ["demand_number" => $val['demand_number']]);

                $pur_a = $val['purchase_number'];
                $sku_a = $val['demand_number'];
                if(!isset($purchase[$pur_a]))$purchase[$pur_a] = ["all" => 0, "instock" => 0];
                $purchase[$pur_a]['all'] += $val['confirm_amount']??0;
                $purchase[$pur_a]['instock'] += $val['instock_qty']??0;
            }

            // 计算采购单状态，因为存在多次入库
            if(!empty($purchase)){
                foreach ($purchase as $key => $val){
                    $order_status = 0;
                    $all = $val['all'];
                    $instock = $val['instock'];
                    if($instock >= $all)$order_status = PURCHASE_ORDER_STATUS_ALL_ARRIVED;  // 全部到货
                    if($instock < $all)$order_status = PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE;  // 部分到货等待剩余
                    if($order_status > 0)$this->purchase_db->update("purchase_order", ["purchase_order_status" => $order_status], ["purchase_number" => $key]);
                }
            }

            $this->purchase_db->trans_commit();
        }catch (Exception $e){
            $this->purchase_db->trans_rollback();
        }
    }
}