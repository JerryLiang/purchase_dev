<?php
/**
 * Created by PhpStorm.
 * 采购单自动请款配置
 * User: Jolon
 * Date: 2019/12/12
 */
class Purchase_auto_payout_model extends Purchase_model {

    public function __construct(){
        parent::__construct();
        $this->load->library('alibaba/AliOrderApi');

        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('warehouse/Warehouse_storage_record_model');
        $this->load->model('finance/Purchase_order_cancel_to_receipt_model', 'cancelReceiptModel');
        $this->load->model('purchase/Purchase_order_cancel_model');
        $this->load->model('finance/purchase_order_pay_type_model');
        $this->load->model('finance/purchase_order_pay_model');

    }

    /**
     * 刷新采购单的总运费、总优惠额、总金额
     * @param $purchase_number
     * @return bool
     */
    public function refresh_order_price($purchase_number){

        $have_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);

        // 获取采购单的总金额
        $purchaseOrder = $this->Purchase_order_model->get_one($purchase_number);
        $itemsList      = $purchaseOrder['items_list'];

        $totalFreight = isset($have_pay_type['freight'])?$have_pay_type['freight']:0;//array_sum(array_column($have_pay_type,'freight'));
        $totalDiscount = isset($have_pay_type['discount'])?$have_pay_type['discount']:0;//array_sum(array_column($have_pay_type,'discount'));
        $orderTotalPrice = 0;
        foreach($itemsList as $item_value){
            $orderTotalPrice += $item_value['purchase_unit_price']*$item_value['confirm_amount'];
        }
        $orderTotalPrice = format_price($orderTotalPrice);
        $totalFreight    = format_price($totalFreight);
        $totalDiscount   = format_price($totalDiscount);
        $orderTotalRealPrice = format_price($orderTotalPrice + $totalFreight - $totalDiscount);

        $save_pay_type_info = [
            'real_price' => $orderTotalRealPrice,
            'freight'    => $totalFreight,
            'discount'   => $totalDiscount
        ];
        $this->purchase_order_pay_type_model->update_one($have_pay_type['id'],$save_pay_type_info);

        return true;
    }

    /**
     * 采购单自动请款
     * @param $purchase_number
     * @param $create_notice
     * @return array
     */
    public function do_auto_payout($purchase_number,$create_notice,$handle_user=false){
        $return = ['code' => false,'message' => '','data' => ''];

        try{
            $this->refresh_order_price($purchase_number);// 刷新采购单总金额

            // 基本验证请款限制
            $purchase_order = $this->purchase_order_model->get_one($purchase_number);
            if (empty($purchase_order)) { throw new Exception('采购单号未找到');}

            $a_type = $purchase_order['account_type'];
            $p_o_status = $purchase_order['purchase_order_status'];
            if(($a_type == 10 && $p_o_status == PURCHASE_ORDER_STATUS_WAITING_ARRIVAL) ||
                ($a_type == 20 && in_array($p_o_status, [PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,]))){}else{
                throw new Exception('结算方式≠款到发货，订单状态≠等待到货状态。或 结算方式≠1688账期，订单状态≠部分到货不等待剩余  全部到货');
            }

            if (empty($purchase_order['is_ali_order'])){
                //throw new Exception('是否1688下单≠是');
            }
            if (!in_array($purchase_order['pay_status'],[PAY_UNPAID_STATUS,PAY_SOA_REJECT,PAY_MANAGER_REJECT,PAY_FINANCE_REJECT,PAY_WAITING_MANAGER_REJECT,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT])) {
                throw new Exception('付款状态≠未申请付款');
            }
            $have_paid_data = $this->purchase_order_pay_model->get_pay_total_by_compact_number($purchase_number);
            if($have_paid_data and $have_paid_data['pay_price'] > 0){
                throw new Exception('付款状态=已付过款了');
            }
            if ($purchase_order['source'] != SOURCE_NETWORK_ORDER) {
                throw new Exception('采购来源≠网采');
            }
            if ($purchase_order['is_drawback'] != PURCHASE_IS_DRAWBACK_N) {
                throw new Exception('是否退税≠否');
            }
            if($purchase_order['is_ali_order'] and $purchase_order['is_ali_price_abnormal']){
                throw new Exception('采购金额=1688金额异常');
            }
            $order_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
            if(empty($order_pay_type['purchase_acccount']) or stripos($order_pay_type['purchase_acccount'],'yibaisuperbuyers') === false){
                throw new Exception('网拍账号≠1688账号');
            }

            // 功能级验证请款限制
            // 1、1688订单交易未关闭、未在退款中
            $aliOrderAllPrice   = $this->aliorderapi->getAliOrderAllPrice($order_pay_type['pai_number']);
            if(empty($aliOrderAllPrice['status'])){ throw new Exception('拍单号错误：1688订单不存在');}
            $aliOrderListRefund = $this->aliorderapi->getListOrderRefund($order_pay_type['pai_number']);
            if($aliOrderAllPrice['status'] == 'cancel' or $aliOrderAllPrice['status'] == 'terminated' ){
                throw new Exception('1688订单状态≠交易未关闭');
            }
            if($aliOrderListRefund['code'] and $aliOrderListRefund['data']){
                $refund_status_list = array_column($aliOrderListRefund['data'],'applyStatus');
                if($refund_status_list and array_merge($refund_status_list,['waitselleragree','waitbuyermodify','waitbuyersend','waitsellerreceive'])){
                    throw new Exception('1688订单状态≠未在退款中');
                }
            }
            // 2、采购系统：取消未到货 非取消中/驳回状态
            $sku_cancel_list  = $this->Warehouse_storage_record_model->get_cancel_info($purchase_number);//获取 采购单+SKU 的已取消数量
            if($sku_cancel_list){// 保留已取消/取消中的 取消数量
                foreach($sku_cancel_list as $cancel_key => $cancel_value){
                    if(in_array($cancel_value['audit_status'],[CANCEL_AUDIT_STATUS_CG,CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT])
                        or $cancel_value['is_edit'] == 1 ){
                        throw new Exception('取消未到货=取消中/驳回');
                    }
                }
            }
            // 3、采购系统：报损 非报损中/驳回状态
            $sku_loss_list    = $this->Warehouse_storage_record_model->get_reportloss_info($purchase_number);// 获取 采购单+SKU 的报损数量
            if($sku_loss_list){// 保留已报损/报损中数量
                foreach($sku_loss_list as $loss_key => $loss_value){
                    if(in_array($loss_value['status'],[REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,REPORT_LOSS_STATUS_MANAGER_REJECTED,REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT,REPORT_LOSS_STATUS_FINANCE_REJECTED])){
                        unset($sku_loss_list[$loss_key]);
                        throw new Exception('报损=报损中/驳回');
                    }
                }
            }
            // 4、1688总金额=系统PO的总采购金额
            // 1688总金额=1688商品额+1688运费-1688优惠额-1688退款成功金额
            // PO的总采购金额=PO的总商品金额+PO的运费-PO的优惠额-PO取消金额
            // 如果 1688总金额 = PO的总采购金额 则允许自动请款，否则，不允许
            $cancel_info            = $this->Purchase_order_cancel_model->get_cancel_total_by_sku($purchase_number);
            $cancel_total_price     = isset($cancel_info['cancel_total_price'])?$cancel_info['cancel_total_price']:0;// 已取消请款金额
            $aliRefundPrice         = $aliOrderListRefund['code']?array_sum(array_column($aliOrderListRefund['data'],'applyTotalAmount')):0;

            $orderRealPayPrice      = format_price($order_pay_type['real_price'] - $cancel_total_price);// 采购系统应付金额
            $aliOrderRealPayPrice   = format_price($aliOrderAllPrice['totalAmount'] - $aliRefundPrice);// 1688应付总金额

            if(bccomp($aliOrderRealPayPrice,$orderRealPayPrice,3) != 0 ){
                throw new Exception('总采购金额≠1688总金额');
            }

            // 验证通过  以下开始执行请款操作
            $purchase_order_list[$purchase_number] = $purchase_order;
            // 组装数据
            $po_pay_data_list[$purchase_number] = [
                'purchase_number' => $purchase_number,
                'create_notice' => $create_notice
            ];

            // 创建请款单
            $result = $this->purchase_order_pay_model->network_pay_order_create($purchase_order_list,$po_pay_data_list, $handle_user,$is_auto=1);
            if (empty($result['code'])) { throw new Exception($result['msg']);}

            // 请款成功
            $return['code'] = true;
        }catch(Exception $e){
            $return['message'] = $e->getMessage();
        }
        return $return;
    }



}