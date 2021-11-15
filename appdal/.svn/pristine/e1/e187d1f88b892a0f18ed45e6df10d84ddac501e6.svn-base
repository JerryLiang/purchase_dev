<?php

/**
 * Purchase_order_ali_verify_model
 * User: yefanli
 * Date: 20200921
 */
class Purchase_order_ali_verify_model extends Purchase_model
{
    protected $ali_order = 'ali_order';
//    protected $table_name = 'purchase_order';
//    protected $item_table_name = 'purchase_order_items';
//    protected $ali_order_items = 'ali_order_items';

    /**
     * Purchase_order_new_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('alibaba/AliOrderApi');
    }

    /**
     * 验证采购单与阿里后台订单结算方式是否一致
     */
    public function verify_ali_order_settlement($pai_number = [])
    {
        $res = ['code' => 0, 'msg' => '验证通过!'];
        if(count($pai_number) == 0)return $res;

        // 获取拍单号

        $purchase_number = $this->get_ali_order_purchase(array_keys($pai_number));
        if(!$purchase_number || count($purchase_number) == 0){
            $res['msg'] = '采购单对应的结算方式缺失。';
            return $res;
        }
        $msg = [];

        $account = $this->purchase_db->from('supplier_settlement')->get()->result_array();
        $settlement = $this->get_supplier_settlement(0, $account);
        try{
            $pai_list = array_values($pai_number);
            if(!$pai_list || count($pai_list) == 0){
                $res['msg'] = '没有要处理的订单。';
                return $res;
            }
            $ali_order = $this->aliorderapi->getListOrderDetail(null, $pai_list);
            if(!$ali_order || empty($ali_order)){
                $res['msg'] = '从1688平台获取订单失败。';
                return $res;
            }

            foreach ($pai_number as $k=>$v){
                if(!isset($ali_order[$v]) ||
                    !isset($ali_order[$v]['data']) ||
                    (empty($ali_order[$v]['data']) && isset($ali_order[$v]['msg'])) ||
                    !isset($ali_order[$v]['data']['baseInfo'])
                ){
                    $msg[$k] = "从1688平台获取订单失败";
                    continue;
                }
                $p_status = isset($purchase_number[$k]) ?$purchase_number[$k]: 0; // 采购单结算方式
                if($p_status == 0){
                    $msg[$k] = "结算方式有误";
                    continue;
                }
                $base_info = $ali_order[$v]['data']['baseInfo'];
                $ali_status1 = $base_info['tradeTypeDesc'];
                $ali_status2 = $base_info['tradeType'];
                $apm = isset($base_info['flowTemplateCode']) && $base_info['flowTemplateCode'] != "accountPeriod30min"? true:false;

                // 账期
                if(in_array($p_status, $settlement[34]) && ($ali_status2 != 10 && !$apm))$msg[$k] = "与1688后台结算方式不一致";
                // 款到发货
                if(in_array($p_status, $settlement[36]) && ($ali_status1 != '担保交易' || $ali_status2 != 1))$msg[$k] = "与1688后台结算方式不一致";
            }
        }catch (Exception $e){}
        $res['code'] = 1;
        if(count($msg) > 0){
            $res['msg'] = $msg;
        }
        return $res;
    }

    /**
     * 获取结算方式
     */
    private function get_supplier_settlement($code, $account)
    {
        $res = [];
        foreach ($account as $val){
            if($code != $val['parent_id'])continue;
            if(!isset($res[$val['settlement_code']]) || !is_array($res[$val['settlement_code']]))$res[$val['settlement_code']] = [];
            $res[$val['settlement_code']][] = $val['settlement_code'];
            foreach ($account as $v){
                if($val['settlement_code'] == $v['parent_id'] && $v['parent_id'] != 0)$res[$val['settlement_code']][] = $v['settlement_code'];
            }
        }
        return $res;
    }

    /**
     * 获取阿里订单信息批量更新采购单信息
     */
    public function get_ali_order_purchase($purchase_number = [])
    {
        if(!$purchase_number || count($purchase_number) == 0) return [];
        $parchase = $this->purchase_db->from('purchase_order')
            ->select('purchase_number, account_type')
            ->where_in('purchase_number', $purchase_number)
            ->get()
            ->result_array();
        if(!$parchase || count($parchase) == 0)return [];
        $res = [];
        foreach ($parchase as $val){
            $res[$val['purchase_number']] = $val['account_type'];
        }
        return $res;
    }
}