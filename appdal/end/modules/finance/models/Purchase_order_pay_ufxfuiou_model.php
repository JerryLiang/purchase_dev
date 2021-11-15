<?php

/**
 * Created by PhpStorm.
 * 富有请款单记录
 * User: Jackson
 * Date: 2019/02/15
 */
class Purchase_order_pay_ufxfuiou_model extends Purchase_model
{

    protected $table_name = 'purchase_order_pay_ufxfuiou';
    protected $table_expand = 'pay_ufxfuiou_expand';
    protected $table_detail_expand = 'pay_ufxfuiou_detail_expand';

    public function __construct()
    {
        $this->load->model('finance/Payment_order_pay_model');
        parent::__construct();

    }

    /**
     * @desc 保存交易流水号和付款申请单号的绑定关系
     * @author Jackson
     * @parames array $payModel 更新数据
     * @parames string $payModel 查询字段
     * @Date 2019-02-12 18:01:00
     * @return array()
     **/
    public function save_bind_information($payData, $tranNo = '')
    {

        //查询条件
        $condition = [];
        $data = [];

        if (!empty($payData)) {
            $condition['requisition_number'] = $payData['requisition_number'];
            $condition['pur_tran_num'] = $tranNo;
        }

        //判断是否存在此数据
        $checkData = $this->checkDataExsit($condition);
        $data = $condition;
        if (!empty($checkData)) {
            if (isset($payData['status']) && $payData['status']) {
                $data['status'] = $payData['status'];
            }
            $data['modify_time'] = date('Y-m-d H:i:s', time());
            $data['modify_user_name'] = getActiveUserName();
            return $this->update($data, $condition);

        } else {
            $data['create_time'] = date('Y-m-d H:i:s', time());
            $data['create_user_name'] = getActiveUserName();
            return $this->insert($data);

        }

    }

    /**
     * @desc 根据商户流水号，富友请款状态更新请款单号状态
     * @author Jackson
     * @parames array $tran_no 更新数据
     * @parames string $result 查询字段
     * @Date 2019-02-12 18:01:00
     * @return array()
     **/
    public function update_pay_status($tran_no, $result)
    {

        $requisition_numbers = $this->getDataByCondition(['pur_tran_num' => $tran_no, 'status' => 1], 'requisition_number');
        if (empty($requisition_numbers)) {
            return ['status' => 'error', 'message' => '无可更新的付款数据'];
        }

        //导入采购单请款单model
        $this->load->model('Purchase_order_pay_model', 'orderPay');
        $this->load->model("Purchase_order_pay_ufxfuiou_detail_model", "orderPayFuionDetails");
        //事务开始
        $this->db->trans_start();
        try {

            $_messge = '';
            //付款成功
            if (!empty($result['inOutSt']) && $result['inOutSt'] == FU_IOU_STATUS_DEDUCT_MONEY_SUCCESS) {

                /**
                 * 付款状态(10.未申请付款,20.待经理审核,21.经理驳回,30.待财务审核,31.财务驳回,40.待财务付款,
                 * 50.已部分付款,51.已付款,90.已取消)
                 **/
                //更新数据
                $updateDetails = [
                    'ufxfuiou_tran_num' => empty($result['fuiouTransNo']) ? '' : $result['fuiouTransNo'],
                    'tranfer_result_code' => empty($result['inOutSt']) ? '' : $result['inOutSt'],
                    'tranfer_result_reason' => empty($result['reason']) ? '' : $result['reason'],
                    'tranfer_result_money' => empty($result['amt']) ? '' : $result['amt'],
                    'status' => PAY_PAID,//51.已付款
                    'pay_status' => empty($result['inOutSt']) ? '' : $result['inOutSt'],
                ];

                //更新条件
                $condition = ['pur_tran_num' => $tran_no];
                $this->orderPayFuionDetails->change_designation_status($condition, $updateDetails, '付款成功富友详情更新');

                //更新请款单表
                $this->orderPay->update_success($requisition_numbers[0], $tran_no);
                $_messge = '付款成功状态更新成功';

            }
            //付款失败
            if (!empty($result['inOutSt']) && $result['inOutSt'] == FU_IOU_STATUS_DEDUCT_MONEY_FAILED_AND_RETURNED) {

                $where = ['pur_tran_num' => $tran_no];
                $updateDetails = [
                    'ufxfuiou_tran_num' => empty($result['fuiouTransNo']) ? '' : $result['fuiouTransNo'],
                    'tranfer_result_code' => empty($result['inOutSt']) ? '' : $result['inOutSt'],
                    'tranfer_result_reason' => empty($result['reason']) ? '' : $result['reason'],
                    'tranfer_result_money' => empty($result['amt']) ? '' : $result['amt'],
                    'status' => PAY_WAITING_FINANCE_PAID,//40.待财务付款
                    'pay_status' => empty($result['inOutSt']) ? '' : $result['inOutSt']
                ];
                $this->orderPayFuionDetails->change_designation_status($where, $updateDetails, '付款失败富友详情更新');

                $this->orderPay->update_fail($requisition_numbers[0], $tran_no);
                $_messge = '付款失败状态更新成功';

            }

            //判断是否保存成功
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                throw new Exception("付款失败状态更新失败!");
            }
            return ['status' => 'success', 'message' => $_messge];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * @desc 富友付款失败执行操作
     * @author Jackson
     * @param array $where 条件
     * @param int $new_status 目标状态
     * @return bool
     */
    public function change_designation_status($where = array(), $new_status = array(), $msg = '变更采购单状态')
    {

        //获取被更新字段
        $update_field = array_keys($new_status);
        $purchase_order = $this->findOne($where);

        $changList = array();
        //获取被修改的状态名称
        foreach ($update_field as $key => $field) {

            $oldText = '';
            $newText = '';
            switch ($field) {
                case 'pay_status':
                    $oldText = getPayStatus($purchase_order[$field]);//原状态名称
                    $newText = getPayStatus($new_status[$field]);//新状态名称
                    break;
                default:
                    break;
            }
            $changList['old_status_name'][$key] = $oldText;//原状态名称
            $changList['new_status_name'][$key] = $newText;//新状态名称

        }

        try {

            // 更新采购单状态
            $result = $this->update($new_status, $where);

            //记录日志
            if ($result) {
                $oldChangeText = implode(",", $changList['old_status_name']);//原状态名称
                $newChangeText = implode(",", $changList['new_status_name']);//新状态名称
                //获取ID
                $idKey = key($where);
                operatorLogInsert(
                    [
                        'id' => $idKey,
                        'type' => $this->table_name,
                        'content' => $msg,
                        'detail' => $msg . "，从【{$oldChangeText}】 改为【{$newChangeText}】"
                    ]);
            } else {
                throw new Exception("更新失败Table" . $this->table_name);
            }

        } catch (Eexception $e) {
            throw new Exception($msg . "失败: code：" . $e->getMessage());
        }
        return true;
    }

    /**
     * 获取富友列表
     */
    public function get_ufxfuiou_pay_list($get, $offsets, $limit, $page)
    {
        $query = $this->purchase_db;
        $query->select('id,to_acc_name,to_bank_name,ufxfuiou_number pur_tran_num,pur_tran_num as pur_number,trans_orderid,'
            . 'to_acc_no,trans_card_id,trans_mobile,pay_price,java_voucher_address,'
            . 'audit_status,supplier_code,trans_summary,remark,applicant,applicant_time,'
            . 'drawee,drawee_time,pay_time');
        $query->from($this->table_expand);
        if (isset($get['ids']) && $get['ids']) {
            $ids = explode(',', trim($get['ids']));
            $query->where_in('id', $ids);
        }
        if (isset($get['pur_number']) && $get['pur_number']) { //合同号
            $pur_number = explode(' ', trim($get['pur_number']));
            $pur_number_list = array_map(function ($array) {
                return sprintf("'%s'", $array);
            }, $pur_number);
            $pur_number_list = array_unique($pur_number_list);
            $pur_number_list = implode(',', $pur_number_list);
            $pur_tran_num_list = $query->query("SELECT pur_tran_num FROM pur_pay_ufxfuiou_detail_expand WHERE pur_number in (" . $pur_number_list . ")")->result_array();
            $pur_tran_num_arr = !empty($pur_tran_num_list) ? array_column($pur_tran_num_list, 'pur_tran_num') : [PURCHASE_NUMBER_ZFSTATUS];
            $query->where_in('pur_tran_num', $pur_tran_num_arr);
        }

        if (isset($get['pur_tran_num']) && trim($get['pur_tran_num'])) {
            $pur_tran_num = explode(' ', trim($get['pur_tran_num']));
            $query->where_in('ufxfuiou_number', array_filter($pur_tran_num));
        }

        if (isset($get['trans_orderid']) && trim($get['trans_orderid'])) {
            $trans_orderid = explode(' ', trim($get['trans_orderid']));
            $query->where_in('trans_orderid', array_filter($trans_orderid));
        }

        if (isset($get['supplier_code']) && $get['supplier_code']) {
            $query->where_in('supplier_code', $get['supplier_code']);
        }
        if (isset($get['audit_status']) && $get['audit_status']) {
            $query->where_in('audit_status', $get['audit_status']);
        }
        if (isset($get['create_time_start']) and $get['create_time_start']) {// 申请时间-开始
            $query->where('applicant_time>=', $get['create_time_start']);
        }
        if (isset($get['create_time_end']) and $get['create_time_end']) {// 申请时间-结束
            $query->where('applicant_time<=', $get['create_time_end']);
        }
        if (isset($get['payer_time_start']) and $get['payer_time_start']) {// 付款时间-开始
            $query->where('pay_time>=', $get['payer_time_start']);
        }
        if (isset($get['payer_time_end']) and $get['payer_time_end']) {// 付款时间-结束
            $query->where('pay_time<=', $get['payer_time_end']);
        }


        $count_qb = clone $query;
        $query->limit($limit, $offsets);
        $query->order_by('id', 'desc');
        $query->group_by('pur_tran_num');
        $data = $query->get()->result_array();
        $count_row = $count_qb->select('count(id) as num')->get()->row_array();

        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        $pur_tran_num = is_array($data) ? array_column($data, 'pur_number') : [];
        $supplier_code_list = is_array($data) ? array_column($data, 'supplier_code') : [];
        $pur_number_list = $this->get_pur_number($pur_tran_num);
        $supplier_list = $this->Payment_order_pay_model->get_supplier_name($supplier_code_list);

        foreach ($data as $key => $vv) {
            $data[$key]['pur_number'] = isset($pur_number_list[$vv['pur_number']]) ? $pur_number_list[$vv['pur_number']] : '';
            $data[$key]['supplier_name'] = isset($supplier_list[$vv['supplier_code']]) ? $supplier_list[$vv['supplier_code']] : '';
            $data[$key]['audit_status'] = getBaofoostatus($vv['audit_status']);

            if($vv['pay_time'] =='0000-00-00 00:00:00'){
                $data[$key]['pay_time'] ='';
            }
            if($vv['drawee'] =='0'){
                $data[$key]['drawee'] ='';
            }
        }
        $data_list['audit_status'] = getBaofoostatus();
        $key_table = ['审核状态', '合同号', '供应商名称', '转账金额', '收款名称', '开户名称', '收款账号', '收款人身份证', '收款人手机号', '审核人审/核时间', '提交人/提交时间', '备注'];
        $return_data = [
            'drop_down_box' => $data_list,
            'key' => $key_table,
            'values' => $data,
            'paging_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
                'pages' => ceil($total_count / $limit)
            ]
        ];
        return $return_data;
    }


    /**
     * 获取富友流水集合
     * @author harvin
     * @param array $pur_tran_num
     * @return array
     */
    public function get_pur_number(array $pur_tran_num)
    {
        if (empty($pur_tran_num)) {
            return [];
        }
        $order_datail = $this->purchase_db
            ->select('pur_tran_num,pur_number')
            ->where_in('pur_tran_num', $pur_tran_num)
            ->get($this->table_detail_expand)
            ->result_array();
        $data = [];
        foreach ($order_datail as $key => $value) {
            $data[$value['pur_tran_num']][] = $value['pur_number'];
        }
        if (empty($data)) {
            return [];
        }
        $data_tran = [];
        foreach ($data as $key => $row) {
            $data_tran[$key] = implode(' ', $row);
        }
        return $data_tran;
    }

    /**
     * 审核时查看信息接口
     */
    public function fuyou_batch_info($ids)
    {
        $fuyou = $this->purchase_db
            ->select('A.id,A.pay_price,A.supplier_code,B.pur_tran_num,B.ufxfuiou_number,B.requisition_number,C.pur_number pur_number,C.pay_price as pay_price_detail')
            ->from($this->table_expand . ' AS A')
            ->join($this->table_detail_expand . ' AS B', 'A.pur_tran_num=B.pur_tran_num')
            ->join('purchase_order_pay AS C','C.requisition_number=B.requisition_number')
            ->where_in('A.id', $ids)
            ->get()
            ->result_array();
        $supplier_code_list = array_unique(array_column($fuyou, 'supplier_code'));
        if (count($supplier_code_list) > 20) {
            return ['code' => false, 'msg' => '不一致的供应商每次提交不可以超过20个！'];
        }
        $total = 0;
        $data_list = [];
        if (!empty($fuyou)) {
            foreach ($fuyou as $val) {
                if (isset($data_list[$val['id']])) {
                    $data_list[$val['id']][] = $val;
                } else {
                    $total += $val['pay_price'];
                    $data_list[$val['id']][] = $val;
                }
            }
        }
        return [
            'ids' => implode(',', $ids),
            'total' => format_price($total),
            'data' => $data_list
        ];
    }

    /**
     * 富友状态
     * @param $ids
     * @return array
     */
    public function fuyou_status($ids)
    {
        $baofoo = $this->purchase_db
            ->select('audit_status')
            ->where_in('id', $ids)
            ->get($this->table_expand)
            ->result_array();
        if (empty($baofoo)) {
            return ['code' => false, 'msg' => '审核数据不存在'];
        }
        $audit_status = [BAOFOOPAYSTATUS_1];
        foreach ($baofoo as $row) {
            if (!in_array($row['audit_status'], $audit_status)) {
                return ['code' => false, 'msg' => '不是待审核或者收款失败的状态数据'];
            }
        }
        return ['code' => true, 'msg' => '数据验证通过'];
    }

    /**
     * 富友审核
     */
    public function fuyou_pay($ids, $trans_summary){
        //获取数据
        $pay_Info = $this->purchase_db->select('id,pur_tran_num,to_acc_name,to_bank_name,to_acc_no,
         trans_card_id,trans_mobile,pay_price,supplier_code,account_short')->from($this->table_expand)->where_in('id', $ids)->get()->result_array();

        $success_list = $error_list =[];
        if (!empty($pay_Info)) {
            $this->load->library('Ufxfuiou');
            foreach ($pay_Info as $value) {
                $fuiouPayDatas['Fuiou']['PayAccount'] = 'pay_001';           //富友账号
                $fuiouPayDatas['Fuiou']['bankCardTp'] = '01'; //卡属性：01对私（默认）、02对公
                $fuiouPayDatas['Fuiou']['oppositeName'] = $value['to_acc_name']; //收款方名称
                $fuiouPayDatas['Fuiou']['oppositeIdNo'] = $value['trans_card_id']; //收款方证件号
                $fuiouPayDatas['Fuiou']['bankId'] = $value['to_bank_name']; //支行号(或支行名称)
                $fuiouPayDatas['Fuiou']['bankCardNo'] = $value['to_acc_no']; //收款账号
                $fuiouPayDatas['Fuiou']['amt'] = $value['pay_price']; //转账金额
                $fuiouPayDatas['Fuiou']['isNotify'] = '01'; //是否需要到账短信通知  01：需要 02：不需要（默认）
                $fuiouPayDatas['Fuiou']['oppositeMobile'] = $value['trans_mobile']; //收款方手机号
                $fuiouPayDatas['Fuiou']['remark'] = make_semiangle($trans_summary); //付款备注
                $fuiouPayDatas['Fuiou']['PayInfo'] = $value['account_short'];
                $fuiouPayDatas['pur_tran_num'] = $value['pur_tran_num'];

                $this->load->library('Ufxfuiou');
                $fuiouPay = Ufxfuiou::bankCardPay($fuiouPayDatas);
                $order_log=[
                    'record_number' => $value['id'],
                    'record_type' => 'PUR_PURCHASE_PAY',
                    'content' => '富友审核',
                    'content_detail' => json_encode($fuiouPay).'请款单号',
                    'is_show' => 2
                ];
                $this->Reject_note_model->get_insert_log($order_log);
//                error_log();
                //返回成功处理
                if (isset($fuiouPay['status']) && $fuiouPay['status'] == 'success' && isset($fuiouPay['responseBody']['rspCode'])) {
                    if($fuiouPay['responseBody']['rspCode'] == '0000'){
                        $results = $this->update_pay_info($fuiouPay, $fuiouPayDatas,$trans_summary);
                        if ($results['code']) {
                            $success_list[] =  $value['pur_tran_num'] . ':' . $results['message'];
                        } else {
                            $error_list[] =  $value['pur_tran_num']. ':' . $results['message'];
                        }
                    }else{
                        $err_msg = isset($fuiouPay['responseBody']['rspDesc'])?$fuiouPay['responseBody']['rspDesc']:"未知原因";
                        $error_list[] =  $value['pur_tran_num']. '：提交支付失败：' . $fuiouPay['responseBody']['rspCode']." -- ".$err_msg;
                    }
                    unset($fuiouPayDatas);
                } else {
                    try{
                        $this->purchase_db->trans_begin();
                        //更新拓展表
                        $data_expand = [
                            'audit_status' => 3,
                            'drawee_time' => date('Y-m-d H:i:s')
                        ];
                        $this->purchase_db->where('pur_tran_num', $value['pur_tran_num'])->update($this->table_expand, $data_expand);
                        //开启事物

                        $pay_Info =  $this->purchase_db->select('id,pur_tran_num')
                            ->from($this->table_expand)
                            ->where('pur_tran_num', $value['pur_tran_num'])
                            ->get()->result_array();
                        if(empty($pay_Info)){
                            throw new Exception('富友付款流水不存在');
                        }
                        $pur_tran_num = array_column($pay_Info, 'pur_tran_num');
                        $requisition_result = $this->get_Ufxfuiou_detail($pur_tran_num);
                        $requisition_number_list = array_column($requisition_result,'requisition_number');
                        $pur_number_list  = array_column($requisition_result,'pur_number');
                        $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', ['pay_status' => PAY_WAITING_FINANCE_PAID, 'payment_notice' => '富友审核失败自动驳回'.$trans_summary]);
                        $this->load->model('compact/Compact_model');
                        foreach($pur_number_list as $pur_number){
                            $result = $this->Compact_model->change_compact_pay_status($pur_number, PAY_WAITING_FINANCE_PAID);
                            if(!$result['code']){
                                throw new Exception($result['msg']);
                            }
                        }
                        if($this->purchase_db->trans_status() === false){
                            $this->purchase_db->trans_rollback();
                            $error_list[] = $value['pur_tran_num'].'：审核失败驳回失败';
                        }else{
                            $this->purchase_db->trans_commit();
                            $error_list[] = $value['pur_tran_num'].'：审核失败驳回成功';
                        }
                    }catch(Exception $exc){
                        $error_list[] = $value['pur_tran_num'].'：审核失败'. $exc->getMessage();
                    }
                }
            }
        }else{
            $error_list[] = '没有可用数据';
        }
        $data['success_list'] = $success_list;
        $data['error_list'] = $error_list;
        return $data;
    }
    /**
     * 获取明细
     */
    public function get_Ufxfuiou_detail($pur_tran_num){
        $this->purchase_db->select('requisition_number,pur_number')->from($this->table_detail_expand);
        if(is_array($pur_tran_num)){
            $this->purchase_db->where_in('pur_tran_num', $pur_tran_num);
        }else{
            $this->purchase_db->where('pur_tran_num', $pur_tran_num);
        }
        $result = $this->purchase_db->get()->result_array();
        return $result;
    }

    /**
     * 更新数据
     */
    public function update_pay_info($fuiouPay, $fuiouPayDatas,$trans_summary){
        try {
            //开启事物
            $this->purchase_db->trans_begin();
            $pur_tran_num = $fuiouPayDatas['pur_tran_num'];
            $requisition_number = $this->get_Ufxfuiou_detail($fuiouPayDatas['pur_tran_num']);
            $requisition_number_list = array_column($requisition_number,'requisition_number');
            $tranNo = isset($fuiouPay['tran']) ? $fuiouPay['tran'] : '';//交易号

            if (!empty($requisition_number_list)) {
                $fase =  false;

                $trans_orderid = '';
                if(isset($fuiouPay['responseBody']['fuiouTransNo'])){
                    if(is_array($fuiouPay['responseBody']['fuiouTransNo'])){
                       $trans_orderid  = implode(',', $fuiouPay['responseBody']['fuiouTransNo']);
                    }else{
                        $trans_orderid = $fuiouPay['responseBody']['fuiouTransNo'];
                    }
                }
                //更新拓展表
                $data_expand = [
                    'ufxfuiou_number'=> $tranNo,
                     'trans_orderid'=> $trans_orderid
                ];

                $fase = $this->purchase_db->where('pur_tran_num',$pur_tran_num )->update($this->table_expand, $data_expand);

                if($fase){
                    $this->load->model('system/Bank_card_model', 'bankCart');
                    $bankInfo = $this->bankCart->findOne(['account_short' => $fuiouPayDatas['Fuiou']['PayInfo'], 'status' => 1]);

                    //更新数据
                    $_datas['payer_id'] = getActiveUserId();//获取当前登录用户ID
                    $_datas['payer_name'] = getActiveUserName();//获取当前登录用户名
                    $_datas['payer_time'] = date('Y-m-d H:i:s', time());
                    $_datas['pay_account'] = !empty($bankInfo) && !empty($bankInfo['account_short']) ? $bankInfo['account_short'] : 0;
                    $_datas['pay_number'] = !empty($bankInfo) && !empty($bankInfo['account_holder']) ? $bankInfo['account_holder'] : '';
                    $_datas['k3_account'] = !empty($bankInfo) && !empty($bankInfo['k3_bank_account']) ? $bankInfo['k3_bank_account'] : '';
                    $_datas['pay_branch_bank'] = !empty($bankInfo) && !empty($bankInfo['branch']) ? $bankInfo['branch'] : '';
                    $_datas['trans_orderid'] = $trans_orderid;
                    $_datas['pur_tran_num'] = $tranNo;
                    $_datas['payment_notice'] = $trans_summary;
                    //更新应付单主表字段
                    $fase = $this->purchase_db->where_in('requisition_number',$requisition_number_list)->update('purchase_order_pay', $_datas);
                }
                if($fase){
                    //更新主表
                    $data_expand = [
                        'pay_time' => date('Y-m-d H:i:s', time()),
                        'drawee' => getActiveUserName(),
                        'drawee_id' =>getActiveUserId(),
                        'drawee_time' => date('Y-m-d H:i:s'),
                        'ufxfuiou_number' => $tranNo,//isset($fuiouPay['responseBody']['fuiouTransNo']) ? is_array($fuiouPay['responseBody']['fuiouTransNo']) ? implode(',', $fuiouPay['responseBody']['fuiouTransNo']) : $fuiouPay['responseBody']['fuiouTransNo'] : '',
                        'audit_status' => 2,
                        'trans_summary' => $trans_summary,
                        'trans_orderid' => $trans_orderid
                    ];
                    $fase = $this->purchase_db->where('pur_tran_num',$pur_tran_num)->update($this->table_expand, $data_expand);
                }
                if($fase){
                    //更新明细
                    $data_expand_entry = [
                        'ufxfuiou_number' => $tranNo
                    ];
                    $fase = $this->purchase_db->where('pur_tran_num',$pur_tran_num)->update($this->table_detail_expand, $data_expand_entry);;
                }
                if($fase){
                    $this->load->model("Purchase_order_pay_model", "Purchase_order_pay");
                    $updateDatas = $this->purchase_db->select('id,requisition_number')->from('purchase_order_pay')
                        ->where_in('requisition_number',$requisition_number_list)->get()->result_array();

                    if(!empty($updateDatas)){
                        foreach ($updateDatas as $data){
                            $fase = $this->save_bind_information($data, $tranNo);
                            if(!$fase){
                                $fase = false;
                            }
                        }
                    }
                }
                if ($fase){
                    $this->load->model("Purchase_order_pay_ufxfuiou_detail_model", "orderPayFuionDetails");
                    $fase = $this->orderPayFuionDetails->save_pay_detail($fuiouPayDatas, $fuiouPay);
                }
            if ($this->purchase_db->trans_status() === false and  $fase ==false) {
                $this->purchase_db->trans_rollback();
                return ['code' => false, 'message' => '富友支付失败!'];
            } else {
                $this->purchase_db->trans_commit();
                return ['code' => true, 'message' => '富友支付成功!'];
            }
            }
        } catch (Exception $exc) {
            return ['code' => false, 'message' => $exc->getMessage()];
        }
    }

    /**
     * 财务驳回
     */
    public function ufxfuiou_pay_reject($ids,$trans_summary){
        try{
            $success_list = $error_list =[];
            //开启事物
            $this->purchase_db->trans_begin();

            $pay_Info =  $this->purchase_db->select('id,pur_tran_num')
                ->from($this->table_expand)
                ->where_in('id', $ids)
                ->get()->result_array();
            if(empty($pay_Info)){
                return ['code' => false, 'message' => '富友付款流水不存在'];
            }
            $pur_tran_num = array_column($pay_Info, 'pur_tran_num');
            $requisition_result = $this->get_Ufxfuiou_detail($pur_tran_num);
            $requisition_number_list = array_column($requisition_result,'requisition_number');
            $pur_number_list  = array_column($requisition_result,'pur_number');

            $data_expand = [
                'pay_time' => date('Y-m-d H:i:s', time()),
                'drawee' => getActiveUserName(),
                'drawee_id' =>getActiveUserId(),
                'drawee_time' => date('Y-m-d H:i:s', time()),
                'audit_status' => 3,
                'trans_summary' => $trans_summary
            ];

             $this->purchase_db->where_in('pur_tran_num',$pur_tran_num)->update($this->table_expand, $data_expand);
            //获取请款单号
             $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', [
                 'pay_status' => PAY_WAITING_FINANCE_PAID,
                 'payment_notice' => '富友审核驳回'.$trans_summary,
                 "payer_id" => getActiveUserId(),
                 "payer_name" => getActiveUserName(),

             ]);

            $this->load->model('compact/Compact_model');
            foreach($pur_number_list as $pur_number){
                $result = $this->Compact_model->change_compact_pay_status($pur_number, PAY_WAITING_FINANCE_PAID);
                if(!$result['code']){
                    throw new Exception($result['msg']);
                }
            }
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                foreach($pur_tran_num as $tran_num){
                    $error_list[] =  $tran_num . ':富友驳回失败';
                }
            }else{
                $this->purchase_db->trans_commit();
                foreach($pur_tran_num as $tran_num){
                    $success_list[] =  $tran_num . ':富友驳回成功';
                }
            }
        }catch(Exception $exc){
            $error_list[] =  '操作失败:'.$exc->getMessage();
        }
        $data['success_list'] = $success_list;
        $data['error_list'] = $error_list;
        return $data;
    }
}