<?php

/**
 * 采购单运输相关操作.
 * User: Jolon
 * Date: 2019/12/30
 */
class Purchase_order_transport_model extends Purchase_model
{

    public function __construct()
    {
        parent::__construct();

        $this->load->model('purchase/purchase_order_model');
        $this->load->model('warehouse/warehouse_model');
    }

    /**
     * 获取计算的采购单运费---动态即时计算
     * @param $orderInfo
     * @example $orderInfo = array(
     *              'purchase_number'  => '',
     *              'warehouse_code'   => '',
     *              'ship_province'    => '',
     *              'order_items' = > array(
     *                  array(
     *                      'sku'            => '',
     *                      'confirm_amount' => '',
     *                  ),
     *                  array(
     *                      'sku'            => '',
     *                      'confirm_amount' => '',
     *                  ),
     *                  ...
     *              )
     *          )
     * @return array
     */
    public function get_calculate_order_reference_freight($orderInfo){
        $return = ['code' => false,'data' => '','message' => ''];

        $purchase_number = $orderInfo['purchase_number'];
        $order_items     = isset($orderInfo['order_items'])?array_column($orderInfo['order_items'],'confirm_amount','sku'):[];

        //获取采购单主表信息
        $query = $this->purchase_db;
        $query->select('A.purchase_number,A.supplier_name,A.supplier_code,A.warehouse_code,C.ship_province,D.sku,D.confirm_amount,P.product_weight');
        $query->from('purchase_order as A');
        $query->join('purchase_order_pay_type as B', 'A.purchase_number=B.purchase_number','left');
        $query->join('supplier as C', 'A.supplier_code=C.supplier_code','left');
        $query->join('purchase_order_items D','D.purchase_number=A.purchase_number','left');
        $query->join('product P','P.sku=D.sku','left');
        $query->where('A.purchase_number', $purchase_number);
        $order_list = $query->get()->result_array();

        if(empty($order_list)){
            $return['message'] = '采购单信息异常';
            return $return;
        }

        $total_product_weight = 0;// 总重量
        foreach($order_list as &$value){
            if(isset($orderInfo['warehouse_code'])) $value['warehouse_code'] = $orderInfo['warehouse_code'];
            if(isset($order_items[$value['sku']]))  $value['confirm_amount'] = $order_items[$value['sku']];
            if(isset($orderInfo['supplier_code']))  $value['supplier_code']  = $orderInfo['supplier_code'];

            $value['product_weight_sku'] = intval($value['product_weight'])/1000 * $value['confirm_amount'];// sku的重量 - 千克
            $total_product_weight       += $value['product_weight_sku'];
        }
        if(!isset($order_list[0]['warehouse_code']) or empty($order_list[0]['warehouse_code'])){
            $return['message'] = '采购单仓库缺失';
            return $return;
        }

        if(!isset($order_list[0]['ship_province']) or empty($order_list[0]['ship_province'])){
            $return['message'] = '采购单供应商发货地址-省缺失';
            return $return;
        }

        $reference_freight = $this->warehouse_model->get_reference_freight($order_list[0]['warehouse_code'],$order_list[0]['ship_province'],$total_product_weight);

        if($reference_freight === false){
            if(empty($order_list)){
                $return['message'] = '仓库运费规则未配置';
                return $return;
            }
        }
        $return['code'] = true;
        $return['data'] = $reference_freight;
        $return['data_attach'] = [
            'total_product_weight' => $total_product_weight,
            'reference_freight' => $reference_freight,
        ];
        $return['message'] = '参考运费计算成功';
        return $return;

    }

    /**
     * 获取参考运费
     * @author yefanli
     */
    public function get_reference_freight($warehouse_code, $province, $total_product_weight)
    {
        $res = [
            "code"      => false,
            "data"      => 0,
            "message"   => "参考运费计算失败"
        ];
        try{
            $freight = $this->warehouse_model->get_reference_freight($warehouse_code, $province, $total_product_weight);
            if($freight >= 0){
                $res['code'] = true;
                $res['data'] = $freight;
                $res['message'] = '参考运费计算成功';
            }
        }catch (Exception $e){}
        return $res;
    }

    /**
     * 计算采购单对应的sku重量
     * @author yefanli
     */
    public function calculation_purchase_number_weight($pur_list, $product)
    {
        $res = [];
        try{
            $Tlist = [];
            foreach ($pur_list as $val){
                $pur = $val['purchase_number'];
                if(!in_array($pur, array_keys($Tlist)))$Tlist[$pur] = 0;
                foreach ($product as $v){
                    if($val['sku'] == $v['sku']){
                        $Tlist[$pur] += $val['confirm_amount'] * $v['product_weight'];  // 采购数量 * 重量
                    }
                }
            }
            foreach ($Tlist as $k=>$v){
                $res[$k] = $v/1000;
            }
        }catch (Exception $e){}
        return $res;
    }
}