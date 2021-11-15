<?php
/**
 * 1688 额度付款
 * User: yefnali
 * Date: 2021/01/13
 */
class Ali_quota_payment_model extends Purchase_model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('alibaba/AliOrderApi');
    }

    /**
     * 1688 额度付款 信息验证
     */
    public function get_ali_order_data($query)
    {
        $res = ["code" => 0, "msg" => '', "order_id" => [], "pur_number" => []];
        $data = $this->purchase_db->from('purchase_order as o')
            ->select("o.purchase_number,ali.order_id")
            ->join("pur_ali_order as ali", "o.purchase_number=ali.purchase_number", "inner")
            ->where_in("o.purchase_number", $query)
            ->where(["o.account_type" => 20, "o.pay_status" => 10])
            ->get()->result_array();

        if(!$data || count($data) == 0)return $res;
        foreach ($data as $val){
            $res["order_id"][] = (integer)$val['order_id'];
        }
        if(count($res["order_id"]) > 0 && count($res["order_id"]) <=30)$res['code'] = 1;
        return $res;
    }

    /**
     * 1688 额度付款 获取收银台链接
     */
    public function send_order_to_alibaba($query)
    {
        $res = ["code" => 0, "msg" => "订单不存在或者订单不是待支付状态。"];
        try{
            $data = $this->aliorderapi->getAliCashier($query);
            if($data && $data["code"] == 1){
                // 标记订单和付款状态(暂时不需要)

                // 获取收银台链接成功
                $res['msg'] = $data['msg'];
                $res['code'] = 1;
            }
        }catch (Exception $e){
        }

        return $res;
    }



}