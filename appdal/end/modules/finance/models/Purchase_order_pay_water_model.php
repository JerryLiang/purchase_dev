<?php

/**
 * Created by PhpStorm.
 * 付款流水表
 * User: Jackson
 * Date: 2019/2/15 0027 11:23
 */
class Purchase_order_pay_water_model extends Purchase_model
{

    protected $table_name = 'purchase_order_pay_water';

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @desc 保存富有支付流水(付款流水)
     * @author Jolon
     * @param object $payData 采购单支付数据
     * @param string $pur_tran_num 富友商户流水号
     * @return bool
     */
    public function save_pay_water($payData, $pur_tran_num)
    {

        //富有请款单详细模块
        $this->load->model("Purchase_order_pay_ufxfuiou_detail_model", "ufxfuiouDetail");
        $ufxPayDetail = $this->ufxfuiouDetail->findOne(['pur_tran_num' => $pur_tran_num]);
        if (empty($ufxPayDetail)) {
            //有请款单详细信息为空则返回
            return false;
        } else {
            $ufxPayDetail = (object)$ufxPayDetail;
        }

        try {
            //数组组合
            $waterModel = (object)array();
            $waterModel->pur_number = $payData->pur_number;
            $waterModel->supplier_code = $payData->supplier_code;
            $waterModel->billing_object_type = 1;// 结算对象类型
            $waterModel->transaction_number = 'PY' . date('YmdHis', time()) . mt_rand(10, 99);;
            $waterModel->is_bill = 2;// 是否指定账单（1是2否）
            $waterModel->price = $payData->pay_price;
            $waterModel->write_off_price = $payData->pay_price;
            $waterModel->original_price = $payData->pay_price;
            $waterModel->original_currency = 'RMB';
            $waterModel->write_off_sign = 2;// 核销完成标志(1是2否）
            $waterModel->monthly_checkout = 1;// 参与月结账(1是2否）
            $waterModel->internal_offset_sign = 2;// 内部抵销标志(1是2否）
            $waterModel->remarks = '富友支付成功';
            $waterModel->create_id = $payData->payer_id;
            $waterModel->create_time = date('Y-m-d H:i:s', time());
            $waterModel->beneficiary_payment_method = $payData->pay_type;
            $waterModel->beneficiary_branch = $ufxPayDetail->branch_bank;
            $waterModel->beneficiary_account = $ufxPayDetail->payee_card_number;
            $waterModel->beneficiary_account_name = $ufxPayDetail->payee_user_name;
            $waterModel->our_branch = '富友账号';
            $waterModel->our_account_abbreviation = '富友账号';
            $waterModel->our_account_holder = $ufxPayDetail->ufxfuiou_account;
            $waterModel->pay_time = date('Y-m-d H:i:s', time());
            $waterModel->is_push_to_k3cloud = 0;
            return $this->insert((array)$waterModel);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    /**
     * @desc 保存1688支付流水(付款流水)
     * @author Jackson
     * @param object $data 操作数据
     * @return bool
     */
    public function save_one_forali($data)
    {

        $water = (object)array();
        $water->pur_number = $data['pur_number'];
        $water->supplier_code = $data['supplier_code'];
        $water->billing_object_type = 1;// 结算对象类型
        $water->transaction_number = 'PY' . date('YmdHis', time()) . mt_rand(10, 99);
        $water->is_bill = 2;// 是否指定账单（1是2否）
        $water->price = $data['pay_price'];
        $water->original_price = $data['pay_price'];
        $water->original_currency = $data['currency'];
        $water->our_account_abbreviation = $data['account_abbreviation'];
        $water->write_off_sign = 2;// 核销完成标志(1是2否）
        $water->monthly_checkout = 1;// 参与月结账(1是2否）
        $water->internal_offset_sign = 2;// 内部抵销标志(1是2否）
        $water->remarks = '1688批量在线付款V3.0';
        $water->create_id = getActiveUserId();
        $water->pay_time = date('Y-m-d H:i:s', time());
        $water->create_time = date('Y-m-d H:i:s', time());
        return $this->insert((array)$water);

    }
}