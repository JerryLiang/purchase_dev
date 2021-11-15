<?php
/**
 * Created by PhpStorm.
 * 采购单自动审核配置
 * User: Jolon
 * Date: 2019/12/12
 */
class Purchase_auto_audit_model extends Purchase_model {

    public function __construct(){
        parent::__construct();
        $this->load->model('warehouse/Warehouse_storage_record_model');
        $this->load->model('system/Data_control_config_model');
        $this->load->model('finance/Purchase_order_pay_type_model');
    }

    /**
     * 获取 采购单的参考运费
     * @param string $purchase_number      采购单号
     * @param string $warehouse_code       仓库代码
     * @param string $ship_province        供应商发货省代码
     * @param string $total_product_weight 采购单商品总重量(单位 千克)
     * @return float|int
     */
    public function get_reference_freight($purchase_number = null,$warehouse_code = null,$ship_province = null,$total_product_weight = null){
        $this->load->model('warehouse/Warehouse_model');

        // 数据不全则自动获取 信息
        if(is_null($warehouse_code) or is_null($ship_province) or is_null($total_product_weight)){
            $query = $this->purchase_db;
            $query->select('A.purchase_number,A.source,A.purchase_order_status,A.supplier_name,  A.buyer_name,B.pai_number,
                A.is_ali_order,A.change_data_apply_note,A.warehouse_code,C.ship_province,B.freight_note,
                D.sku,D.freight,D.discount,D.confirm_amount,D.purchase_unit_price,D.modify_remark,D.purchase_amount,
                (P.product_weight * D.confirm_amount) AS total_product_weight');
            $query->from('purchase_order as A');
            $query->join('purchase_order_pay_type as B', 'A.purchase_number=B.purchase_number');
            $query->join('supplier as C', 'A.supplier_code=C.supplier_code');
            $query->join('purchase_order_items D', 'A.purchase_number=D.purchase_number');
            $query->join('product P','P.sku=D.sku','left');
            $query ->where('A.purchase_number',$purchase_number);
            $order = $query->get()->result_array();

            if(empty($order)) return false;

            $total_product_weight = array_sum(array_column($order, 'total_product_weight')) / 1000;// 商品总重量（转成 千克）
            $warehouse_code       = $order[0]['warehouse_code'];
            $ship_province        = $order[0]['ship_province'];
        }

        // 获取采购单的参考运费配置
        $freight_rule = $this->Warehouse_model->get_fright_rule_by_warehouse_code($warehouse_code,$ship_province);
        if (empty($freight_rule)){
            $temp_reference_freight = 0;
        }else{
            $temp_reference_freight = $freight_rule['first_weight_cost']+(ceil(format_two_point_price($total_product_weight))-1)*$freight_rule['additional_weight_cost'];
        }

        return $temp_reference_freight;
    }

    /**
     * 验证采购单是否可以自动审核通过
     * @param $purchaseOrderInfo
     * @param bool $autoCreteLog 是否自动创建审核日志
     * @return array
     * @example $purchaseOrderInfo = array(
     *              'purchase_number'   => 'ABD123456',
     *              'source'            => '2',
     *              'is_drawback'       => '0',
     *              'supplier_code'     => 'QS12D14442',
     *              'purchase_number'   => 'ABD123456',
     *              'items_list'    => array(
     *                 'sku'                    => 'DEAP0001',
     *                  'purchase_unit_price'   => '12.3'
     *              )
     *          )
     */
    public function checkPurchaseOrderAutomaticAudit($purchaseOrderInfo,$autoCreteLog = true){
        $return = ['code' => false,'message' => '','data' => ''];

        $config      = $this->Data_control_config_model->get_control_config('PURCHASE_AUTO_AUDIT');
        if(empty($config)){
            $return['message'] = '数据未配置无法自动审核';
            return $return;
        }
        $config      = isset($config['config_values'])?$config['config_values']:'';

        if(is_json($config)) $config = json_decode($config,true);
        $data_config = [
            'purchase_total_price_min'   => isset($config['purchase_total_price_min']) ? $config['purchase_total_price_min'] : '',
            'purchase_total_price_max'   => isset($config['purchase_total_price_max']) ? $config['purchase_total_price_max'] : '',
            'purchase_total_freight_max' => isset($config['purchase_total_freight_max']) ? $config['purchase_total_freight_max'] : '',
        ];

        $purchase_number = $purchaseOrderInfo['purchase_number'];
        $items_list      = $purchaseOrderInfo['items_list'];

        // 1.合同单不能自动审核
        if($purchaseOrderInfo['source'] == SOURCE_COMPACT_ORDER){
            $return['message'] = '合同单不能自动审核';
            return $return;
        }
        // 7.退税的单不能自动审核
        if($purchaseOrderInfo['is_drawback'] == PURCHASE_IS_DRAWBACK_Y){
            $return['message'] = '退税的单不能自动审核';
            return $return;
        }

        $this->Purchase_order_pay_type_model->refresh_order_price($purchase_number);

        // 5.采购数量未发生变更
        $sku_related_amount_list = $this->Warehouse_storage_record_model->get_purchase_order_and_suggest($purchase_number);
        foreach($sku_related_amount_list as $sku_related_amount){
            if($sku_related_amount['confirm_amount'] != $sku_related_amount['purchase_amount']
                || $sku_related_amount['confirm_amount'] != $sku_related_amount['map_confirm_number']){
                $return['message'] = '采购单数据发生变更';
                return $return;
            }
        }

        // 3.po的总采购金额：=sum所有备货单的（采购数量*采购单价）
        //   po的总采购运费：
        //   参考运费：根据供应商的发货地、易佰的收货仓库，以及po的总重量的不同，而有不同的参考运费。参考运费的逻辑之前已经算出，直接取值即可。
        $payTypeInfo            = $this->Purchase_order_pay_type_model->get_one($purchase_number);
        $purchase_total_price   = $payTypeInfo['real_price'];
        $purchase_total_freight = $payTypeInfo['freight'];
        $purchase_reference_freight = $this->get_reference_freight($purchase_number);
        if($purchase_reference_freight === false){// 数据有误
            $return['message'] = '参考运费计算失败';
            return $return;
        }

        if($data_config['purchase_total_price_min'] !== '' and $data_config['purchase_total_price_min'] > $purchase_total_price){// 不满足条件（设定的最小值 < PO的总采购金额 < 设定的最大值）
            $return['message'] = '不满足金额限制，采购单总金额：'.$purchase_total_price;
            return $return;
        }
        if($data_config['purchase_total_price_max'] !== '' and $data_config['purchase_total_price_max'] < $purchase_total_price){// 不满足条件（设定的最小值 < PO的总采购金额 < 设定的最大值）
            $return['message'] = '不满足金额限制，采购单总金额：'.$purchase_total_price;
            return $return;
        }
        // 不满足条件（PO的总采购运费 < 参考运费），不满足条件（PO的总采购运费 < 运费设定的最大值）
        if(($purchase_total_freight > $purchase_reference_freight) || ($data_config['purchase_total_freight_max'] !== '' and $data_config['purchase_total_freight_max'] < $purchase_total_freight) ){
            $return['message'] = "不满足 考运费限制，采购单总运费[{$purchase_total_freight}]，参考运费[{$purchase_reference_freight}] 或者 运费最大值限制，采购单总运费:".$purchase_total_freight;
            return $return;
        }
//        if($data_config['purchase_total_freight_max'] !== '' and $data_config['purchase_total_freight_max'] < $purchase_total_freight){// 不满足条件（PO的总采购运费 < 运费设定的最大值）
//            $return['message'] = '不满足运费最大值限制，采购单总运费：'.$purchase_total_freight;
//            return $return;
//        }

        // 验证供应商、未税单价是否发生变更
        foreach($items_list as $item_value){
            $sku = $item_value['sku'];

            // 查找 最近一次审核通过的有该SKU的网采单 的记录
            $latestPurchaseOrder = $this->purchase_db
                ->select("PO.purchase_number,PO.supplier_code,POI.purchase_unit_price")
                ->from("purchase_order AS PO")
                ->join("purchase_order_items AS POI","PO.purchase_number=POI.purchase_number","left")
                ->where("POI.sku",$sku)
                ->where_not_in("PO.purchase_order_status",[
                    PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                    PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                    PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,
                    PURCHASE_ORDER_STATUS_CANCELED])
                ->order_by("PO.audit_time DESC")
                ->get()
                ->row_array();

            if(empty($latestPurchaseOrder)) continue;

            // 6.供应商没有发生变更
            if($latestPurchaseOrder['supplier_code'] != $purchaseOrderInfo['supplier_code']) {
                $return['message'] = '供应商已经发生变更';
                return $return;
            }
            // 4.未税单价未发生变更（非退税订单 验证含税单价=未税单价）
            if($latestPurchaseOrder['purchase_unit_price'] != $item_value['purchase_unit_price']){
                $return['message'] = '未税单价已经发生变更';
                return $return;
            }
        }

        // 记录计算日志 便于排查问题
        $this->load->library('mongo_db');
        $insert_data = [];
        $insert_data['purchase_number']       = $purchase_number;
        $insert_data['create_time']           = date('Y-m-d H:i:s');
        $insert_data['operating_elements']    = json_encode(get_defined_vars());// 保存计算时的操作元素
        $this->mongo_db->insert('checkPurchaseOrderAutomaticAuditLog', $insert_data);

        $return['code']     = true;
        $return['message']  = '允许自动审核';

        if($autoCreteLog === true){
            $old_status_name = getPurchaseStatus($purchaseOrderInfo['purchase_order_status']);
            $new_status_name = getPurchaseStatus(PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT);

            // 变更状态，用于基础方法change_status自动获取最新状态来生成日志
            $this->purchase_db->where('purchase_number',$purchase_number)->update('purchase_order',['purchase_order_status' => PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT]);

            // 记录操作日志
            operatorLogInsert(
                [   'id'        => $purchase_number,
                    'type'      => 'PURCHASE_ORDER',
                    'content'   => '变更采购单状态',
                    'detail'    => "修改采购单状态，从【{$old_status_name}】 改为【{$new_status_name}】",
                    'user'      => 'admin',
                    'user_id'   => 1
                ]);

        }
        if($autoCreteLog === false){
            $return['code'] = false;
        }

        return $return;
    }



}