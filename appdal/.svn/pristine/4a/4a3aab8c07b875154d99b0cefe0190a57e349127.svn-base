<?php
/**
 * Created by PhpStorm.
 * 采购单取消未到货记录表
 * User: Jolon
 * Date: 2019/01/11 0027 11:23
 */

class Purchase_order_cancel_model extends Purchase_model {

    protected $table_name = 'purchase_order_cancel';
    protected $table_cancel_detail = 'purchase_order_cancel_detail';

    public function __construct(){
        parent::__construct();
    }


    /**
     * 获取 采购单 - SKU 已取消数量和总金额
     *      （取消的）商品金额、运费、优惠额、实际金额
     * @author Jolon
     * @param string $purchase_number   采购单号
     * @param string $sku               SKU
     * @return mixed
     */
    public function get_cancel_total_by_sku($purchase_number,$sku = null){
        $audit_status_list = [CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC];
        // 采购单 已取消总金额
        $cancel_info = $this->purchase_db->select('pocd.purchase_number,'
            .'sum(pocd.item_total_price) as cancel_product_money'
        )
            ->from('purchase_order_cancel as poc')
            ->join('purchase_order_cancel_detail as pocd','poc.id=pocd.cancel_id','inner')
            ->where_in('poc.audit_status',$audit_status_list) // 50.审核通过
            ->where('pocd.purchase_number',$purchase_number)
            ->get()
            ->row_array();

        if($cancel_info){
            $audit_status_list_str = implode(",",$audit_status_list);
            // 采购单 已取消总金额
            $po_cancel_sql = "SELECT
                        SUM(po_detail.freight) AS freight,
                        SUM(po_detail.discount) AS discount,
                        SUM(po_detail.process_cost) AS process_cost
                    FROM (
                    
                            SELECT  pocd.purchase_number,
                            pocd.freight,
                            pocd.discount,
                            pocd.process_cost
                            
                            FROM pur_purchase_order_cancel AS poc
                            INNER JOIN pur_purchase_order_cancel_detail AS pocd ON poc.id=pocd.cancel_id
                            WHERE poc.audit_status IN($audit_status_list_str)
                            AND pocd.purchase_number='$purchase_number'
                            GROUP BY pocd.cancel_id,pocd.purchase_number
                    
                    ) AS po_detail";
            $po_cancel_list = $this->purchase_db->query($po_cancel_sql)->row_array();

            $cancel_info['cancel_freight']      = $po_cancel_list['freight'];
            $cancel_info['cancel_discount']     = $po_cancel_list['discount'];
            $cancel_info['cancel_process_cost'] = $po_cancel_list['process_cost'];
            $cancel_info['cancel_total_price']  = $cancel_info['cancel_product_money']
                + $cancel_info['cancel_freight']
                - $cancel_info['cancel_discount']
                + $cancel_info['cancel_process_cost'];

        }


        if($sku !== null){
            // SKU 已付款总金额(pay_total 采购单 SKU 已付款金额)
            $cancel_info2 = $this->purchase_db->select('pocd.purchase_number,'
                .'pocd.sku,'
                .'sum(pocd.cancel_ctq) as cancel_ctq,'
                .'sum(pocd.item_total_price) as cancel_total_price'
            )
                ->from('purchase_order_cancel as poc')
                ->join('purchase_order_cancel_detail as pocd','poc.id=pocd.cancel_id','inner')
                ->where_in('poc.audit_status',$audit_status_list) // 50.审核通过
                ->where('pocd.purchase_number',$purchase_number)
                ->where('pocd.sku',$sku)
                ->get()->row_array();
            if($cancel_info2){
                $cancel_info['sku']                = $cancel_info2['sku'];
                $cancel_info['cancel_ctq']         = $cancel_info2['cancel_ctq'];
                $cancel_info['cancel_total_price'] = $cancel_info2['cancel_total_price'];
            }
        }

        return $cancel_info;
    }


    /**
     * 根据SKU和时间段统计审核通过的取消数量
     * @author Jaden
     * @date 2019/5/28 
     * @param string $sku
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public function get_cancel_ctq_by_sku($sku,$start_time,$end_time){
        if(empty($sku) || empty($start_time) || empty($end_time)){
            return [];
        }
        $this->purchase_db->select('sum(ci.cancel_ctq) as cancel_ctq,c.audit_time');
        $this->purchase_db->from('purchase_order_cancel_detail as ci');
        $this->purchase_db->join('purchase_order_cancel as c', 'ci.cancel_id=c.id', 'left');
        $this->purchase_db->where('ci.sku', $sku);
        $this->purchase_db->where_in('c.audit_status', [CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC]);
        $this->purchase_db->where('c.audit_time >=', $start_time);
        $this->purchase_db->where('c.audit_time <=', $end_time);
        $results = $this->purchase_db->order_by('c.audit_time asc')->get()->row_array();
        return $results;
      
    }

    
    /**
     * 根据SKU和采购单获取取消数量
     * @author Jaden
     * @date 2019/5/28 
     * @param string $sku
     * @param string $purchase_number
     * @return array
     */
    public function get_cancel_ctq($sku,$purchase_number){
        if(empty($sku) || empty($purchase_number)){
            return 0;
        }
        $this->purchase_db->select('sum(cancel_ctq) as cancel_ctq');
        $this->purchase_db->from('purchase_order_cancel_detail');
        $this->purchase_db->where('sku', $sku);
        $this->purchase_db->where('purchase_number', $purchase_number);
        $cancel_ctq_info = $this->purchase_db->get()->row_array();
       return !empty($cancel_ctq_info['cancel_ctq']) ? $cancel_ctq_info['cancel_ctq'] : 0;

    }

    /**
     * 根据SKU和采购单获取取消数量
     * @author luxu
     * @date 2019/11/20
     * @param string $sku
     * @param string $purchase_number
     * @return array
     */
    public function get_cancel_ctq_new($sku,$purchase_number){
        if(empty($sku) || empty($purchase_number)){
            return 0;
        }
        $sql = "SELECT sum(detail.cancel_ctq) as cancel_ctq from pur_purchase_order_cancel_detail AS detail LEFT JOIN pur_purchase_order_cancel AS cancel ON cancel.id=detail.cancel_id WHERE cancel.audit_status NOT IN(30,40)";
        $sql .= " AND sku='{$sku}' AND purchase_number='{$purchase_number}'";
        $result = $this->purchase_db->query($sql)->row_array();
        return $result['cancel_ctq'];
    }

    /**
     * 根据采购单items_id获取取消数量
     * 财务已付款,自动审核通过
     * @param $purchase_number_arr
     * @return array
     * @author Manson
     * @return array
     */
    public function get_cancel_qty_by_item_id($items_id_list){
        if(empty($items_id_list) || !is_array($items_id_list)){
            return [];
        }
        $map = [];
        $result = $this->purchase_db->select('a.items_id, a.cancel_ctq as cancel_qty')
            ->from($this->table_cancel_detail. ' a')
            ->join($this->table_name. ' b','a.cancel_id = b.id', 'left')
            ->where_in('b.audit_status',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
            ->where_in('a.items_id',$items_id_list)
            ->get()->result_array();
        if (!empty($result)){
            foreach ($result as $key => &$item){
                if (isset($map[$item['items_id']])){
                    $map[$item['items_id']] += $item['cancel_qty'];//相同items_id的取消数量进行相加
                }else{
                    $map[$item['items_id']] = $item['cancel_qty'];
                }
                unset($result[$key]);
            }
        }
        return $map;
    }

}