<?php

/**
 * Created by PhpStorm.
 * 请款单
 * User: Jolon
 * Date: 2019/01/10 0027 11:23
 */
class Statement_order_pay_model extends Purchase_model
{

    protected $table_name = 'purchase_order_pay';

    public function __construct()
    {
        parent::__construct();

        $this->load->model('finance/purchase_order_pay_model'); // 请款单 SKU 明细

    }


    /**
     * 对账单请款审核流程
     * @param $requisition_number
     * @return string
     */
    public function audit_statement_pay($requisition_number){


        $next_pay_status = '';// 请款单审核后 下一个状态


        return $next_pay_status;
    }

    /**
     * 合同线上审核流程
     * @param $requisition_number
     * @return string
     */
    public function audit_compact_pay($requisition_number){

        $next_pay_status = '';// 请款单审核后 下一个状态


        return $next_pay_status;
    }


}