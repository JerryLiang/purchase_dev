<?php

/**
 * Created by PhpStorm.
 * 请款单明细
 * User: Jeff
 * Date: 2019/09/26 0027 11:23
 */
class Purchase_order_cancel_to_receipt_model extends Purchase_model
{

    protected $table_name           = 'purchase_order_cancel_to_receipt';
    protected $table_name_receipt   = 'purchase_order_receipt';

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @desc 根据选择的取消ID获取应流向应收款表记录
     * @author jeff
     * @parames array $parames 查询条件
     * @parames string $field 返回字段
     * @Date 2019-02-12 18:01:00
     * @return array()
     **/
    public function findOnes($parames = array(), $field = '*')
    {

        //查询条件
        $condition = array();
        if (!empty($parames)) {
            foreach ($parames as $key => $value) {
                $condition[$key] = $value;
            }
        }

        //查询数据
        return $this->findOne($condition, $field);

    }

    /**
     * 获取 取消未到货的退款记录
     * @param $where
     * @return array
     */
    public function get_receipt_by_cancel_id($where)
    {
        $this->purchase_db->select('*');
        $this->purchase_db->from($this->table_name);
        $this->purchase_db->where($where);
        $receipt_record_arr = $this->purchase_db->get()->result_array();
        return $receipt_record_arr;
    }


    /**
     * 根据采购单 获取 退款中、已退款 的金额和状态
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @return mixed
     */
    public function get_receipt_price_by_order($purchase_number){

        $sql_query_refund = "
            SELECT A.id,B.`purchase_number`,C.`pay_price`,D.pay_status
            FROM `pur_purchase_order_cancel_detail` AS B 
            LEFT JOIN `pur_purchase_order_cancel` AS A ON A.id=B.`cancel_id`
            LEFT JOIN `pur_purchase_order_cancel_to_receipt` AS C ON C.`cancel_id`=A.id AND C.purchase_number=B.purchase_number
            LEFT JOIN `pur_purchase_order_receipt` AS D ON D.cancel_id=A.id AND D.purchase_number=B.purchase_number
            WHERE B.`purchase_number`='{$purchase_number}'
            AND A.`audit_status` IN("
                .CANCEL_AUDIT_STATUS_CG.","
                .CANCEL_AUDIT_STATUS_CF.","
                .CANCEL_AUDIT_STATUS_SCJT.","
                .CANCEL_AUDIT_STATUS_CFYSK.","
                .CANCEL_AUDIT_STATUS_SYSTEM.","
                .CANCEL_AUDIT_STATUS_YDC."
            )
            AND C.`pay_price` > 0
            ORDER BY A.create_time ASC";

        $refund_list = $this->purchase_db->query($sql_query_refund)->result_array();

        return $refund_list;
    }
}