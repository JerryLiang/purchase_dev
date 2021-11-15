<?php
/**
 * Created by PhpStorm.
 * 付款申请书
 * User: Jolon
 * Date: 2019/01/10 0027 11:23
 */
class Purchase_pay_requisition_model extends Purchase_model {

    protected $table_name = 'purchase_pay_requisition';

    public function __construct(){
        parent::__construct();

    }

    /**
     * 创建付款申请书
     * @author Jolon
     * @param array $pay_data 付款申请书数据
     * @return bool
     * @example Array
        (
            [compact_number] => PO-HT000028
            [requisition_number] => PP000099
            [invoice_looked_up] => YIBAI TECHNOLOGY LTD
            [receive_unit] => 许二刚
            [receive_account] => 6212261208010786992
            [payment_platform_branch] => 中国工商银行浙江省义乌市支行
            [pay_price] => 12.323
            [pay_price_cn] => 壹拾贰元叁角贰分叁厘
            [payment_reason] =>
        )
     */
    public function create($pay_data){
        if(empty($pay_data['requisition_number']) or empty($pay_data['compact_number'])) return false;

        $pay_requisition = [];

        isset($pay_data['compact_number']) and $pay_requisition['compact_number'] = $pay_data['compact_number'];
        isset($pay_data['requisition_number']) and $pay_requisition['requisition_number'] = $pay_data['requisition_number'];
        isset($pay_data['invoice_looked_up']) and $pay_requisition['invoice_looked_up'] = $pay_data['invoice_looked_up'];
        isset($pay_data['receive_unit']) and $pay_requisition['receive_unit'] = $pay_data['receive_unit'];
        isset($pay_data['receive_account']) and $pay_requisition['receive_account'] = $pay_data['receive_account'];
        isset($pay_data['payment_platform_branch']) and $pay_requisition['payment_platform_branch'] = $pay_data['payment_platform_branch'];
        isset($pay_data['pay_price']) and $pay_requisition['pay_price'] = $pay_data['pay_price'];
        isset($pay_data['pay_price_cn']) and $pay_requisition['pay_price_cn'] = $pay_data['pay_price_cn'];
        isset($pay_data['payment_reason']) and $pay_requisition['payment_reason'] = $pay_data['payment_reason']?$pay_data['payment_reason']:$pay_data['compact_number'];

        // 判断请款单号的付款申请书是否存在
        $exists = $this->purchase_db->where(['requisition_number' => $pay_data['requisition_number']])->get($this->table_name)->row_array();

        if($exists and $exists['id']){// 更新
            $result = $this->purchase_db->set($pay_data)->where('id',$exists['id'])->update($this->table_name,$pay_requisition);

        }else{// 新增数据
            $pay_requisition['create_user_id']   = getActiveUserId();
            $pay_requisition['create_user_name'] = getActiveUserName();
            $pay_requisition['create_time']      = date('Y-m-d H:i:s');
            $pay_requisition['status']           = 1;// 状态(1.启用,2.作废)

            $result = $this->purchase_db->insert($this->table_name,$pay_requisition);
        }

        return $result;
    }

}