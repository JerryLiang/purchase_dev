<?php
/**
 * 1688 额度付款
 * User: yefnali
 * Date: 2019/03/20 10:00
 */

class Ali_quota_payment extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ali/Ali_quota_payment_model', 'quota');
    }

    /**
     * 1688 额度付款
     */
    public function ali_quota_payment()
    {
        $pur_number = $this->input->get_post('purchase_number');

        if(empty($pur_number) || !is_array($pur_number))$this->error_json("采购单号不能为空");

        $aliOrder = $this->quota->get_ali_order_data($pur_number);
        if($aliOrder['code'] == 0)$this->error_json('请选择结算方式为“1688账期”，付款状态为“未申请付款”，一次最多操作30个以内采购单！');

        $sendOrder = $this->quota->send_order_to_alibaba(['orderIdList' => $aliOrder['order_id']]);
        if($sendOrder['code'] == 1)$this->success_json($sendOrder["msg"]);
        $this->error_json($sendOrder["msg"]);
    }



}