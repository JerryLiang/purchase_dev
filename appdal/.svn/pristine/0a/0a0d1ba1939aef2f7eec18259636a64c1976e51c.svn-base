<?php
/**
 * 1688 订单消息控制器
 * User: yefanli
 */
class Ali_order_message_model extends Purchase_model
{
    /**
     * Ali_order_message constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_1688');
        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('Message_model');
    }

    /**
     * 订阅1688改价信息，通知业务并自动确认
     */
    public function subscribe_change_price($data)
    {
        if(!isset($data['orderId']) || !isset($data['currentStatus']) || $data['currentStatus'] != 'waitbuyerpay')return false;
        $oid = $data['orderId'];

        // 获取1688订单信息
        $order = $this->aliorderapi->getListOrderDetail(null, $oid);

        $ali_price = false;

        if (isset($order[$oid]) && isset($order[$oid]['code']) && $order[$oid]['code'] == 200 && isset($order[$oid]['data']['baseInfo'])) {
            $b_info = $order[$oid]['data']['baseInfo'];
            $totalAmount = isset($b_info['totalAmount']) ? $b_info['totalAmount'] : 0;
            $discount = isset($b_info['discount']) ? $b_info['discount'] / 100 : 0;
            $ali_price = $totalAmount - $discount;
        }

        if(!$ali_price){
            return false;
        }

        // 获取订单和采购单信息
        $data = $this->purchase_db->from('ali_order')
            ->select("purchase_number,total_success_amount")
            ->where("order_id=", $oid)
            ->get()->row_array();
        if(isset($data['total_success_amount']) && $data['total_success_amount'] != $ali_price){ // 价格不相等 则发消息提示
            $pur = $data['purchase_number'];
            // 更新订单总价
            $this->purchase_db->where("order_id =", $oid.'')->update("pur_ali_order", ['total_success_amount' => $ali_price]);

            // 发送消息
            $this->Message_model->AcceptMessage('ali_order',['data'=> $pur,'message'=> "采购单 {$pur} 卖家已修改订单价格,请尽快确认",'user'=> "system",'type'=>'采购单价格确认']);
        }

        return true;
    }


}