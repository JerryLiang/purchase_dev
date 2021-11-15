<?php

/**
 * Created by PhpStorm.
 * 富有请款单记录
 * User: Jackson
 * Date: 2019/02/15
 */
class Purchase_order_pay_ufxfuiou_detail_model extends Purchase_model
{

    protected $table_name = 'ufxfuiou_pay_detail';

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @desc 保存交易流水号和付款申请单号的绑定关系(详情)
     * @author Jackson
     * @parames array $fuiouPayDatas 更新数据
     * @parames string $fuiouPay 查询字段
     * @Date 2019-02-12 18:01:00
     * @return array()
     **/
    public function save_pay_detail($fuiouPayDatas, $fuiouPay)
    {

        //查询条件
        $condition = [];

        if (isset($fuiouPay['tran']) && isset($fuiouPay['responseBody']['fuiouTransNo'])) {
            $condition['pur_tran_num'] = $fuiouPay['tran'];
        }

        $fuiouPayDatas = $fuiouPayDatas['Fuiou'];
        //组合数据
        $data = (object)array();
        $data->pur_tran_num = isset($fuiouPay['tran']) ? $fuiouPay['tran'] : '';
        $data->ufxfuiou_tran_num = isset($fuiouPay['responseBody']['fuiouTransNo']) ? is_array($fuiouPay['responseBody']['fuiouTransNo']) ? implode(',', $fuiouPay['responseBody']['fuiouTransNo']) : $fuiouPay['responseBody']['fuiouTransNo'] : '';
        $data->pay_status = FU_IOU_STATUS_STATUS_ACCEPTED;//默认状态已受理
        $data->tran_money = isset($fuiouPayDatas['amt']) ? $fuiouPayDatas['amt'] * 100 : 0;
        $data->payee_card_number = isset($fuiouPayDatas['bankCardNo']) ? $fuiouPayDatas['bankCardNo'] : '';
        $data->payee_user_name = isset($fuiouPayDatas['oppositeName']) ? $fuiouPayDatas['oppositeName'] : '';
        $data->payee_id_number = isset($fuiouPayDatas['oppositeIdNo']) ? $fuiouPayDatas['oppositeIdNo'] : '';
        $data->payee_phone_number = isset($fuiouPayDatas['oppositeMobile']) ? $fuiouPayDatas['oppositeMobile'] : '';
        $data->bank_code = isset($fuiouPayDatas['bankNo']) ? $fuiouPayDatas['bankNo'] : '';
        $data->city_code = isset($fuiouPayDatas['cityNo']) ? $fuiouPayDatas['cityNo'] : '';
        $data->remark = isset($fuiouPayDatas['remark']) ? $fuiouPayDatas['remark'] : '';
        $data->is_need_review = isset($fuiouPayDatas['is_need_review']) ? $fuiouPayDatas['is_need_review'] : '02';
        $data->branch_bank = isset($fuiouPayDatas['bankId']) ? $fuiouPayDatas['bankId'] : '';
        $data->is_notify = isset($fuiouPayDatas['isNotify']) ? $fuiouPayDatas['isNotify'] : '02';
        $data->charge = isset($fuiouPayDatas['charge']) ? $fuiouPayDatas['charge'] : '02';
        $data->ufxfuiou_account = isset($fuiouPayDatas['PayAccount']) ? $fuiouPayDatas['PayAccount'] : '';
        $data->status = PAY_UFXFUIOU_REVIEW;

        //查询数据是否存在
        $checkData = $this->checkDataExsit($condition);
        if (!empty($checkData)) {

            $data->modify_time = date('Y-m-d H:i:s', time());
            $data->modify_user_name = getActiveUserName();
            return $this->update((array)$data, $condition);

        } else {

            $data->create_time = date('Y-m-d H:i:s', time());
            $data->create_user_name = getActiveUserName();
            $data->create_ip = getActiveUserIp();
            return $this->insert((array)$data);

        }

    }

    /**
     * 采购单请款单(动态指定更新状态)
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
     * 富友 获取订单回执信息
     * @author Jolon
     * @param string $pur_tran_num
     * @param int $debug
     * @return array
     */
    public function pay_ufxfuiou_voucher($pur_tran_num = null,$debug = 0){
        $return = ['code' => false, 'data' => '', 'message' => ''];
        if(empty($pur_tran_num)){
            $trans_order_list = $this->purchase_db->select('*')
                ->where('pay_status', 51)
                ->where('ufxfuiou_tran_num !=','')
                ->where('is_get_back','0')
                ->get($this->table_name,50)
                ->result_array();
        }else{
            $trans_order_list = $this->purchase_db->select('*')
                ->where('pur_tran_num', $pur_tran_num)
                ->where('ufxfuiou_tran_num !=','')
                ->get($this->table_name)
                ->result_array();
        }
        if(empty($trans_order_list)){
            $return['msg'] = '没有找到对应的记录';
            return $return;
        }

        $this->load->helper('file_remote');
        $this->load->library('Ufxfuiou');
        $this->load->library('Filedirdeal');
        $this->load->library('Upload_image');

        $success_list = [];
        $error_list = [];

        foreach($trans_order_list as $trans_order){
            $pur_tran_num = $trans_order['pur_tran_num'];
            $ufxfuiou_tran_num = $trans_order['ufxfuiou_tran_num'];
            try {
                $requisition_number_list = $this->purchase_db->select('requisition_number')
                    ->where('pur_tran_num', $pur_tran_num)
                    ->get('purchase_order_pay_ufxfuiou')
                    ->result_array();
                $requisition_number_list = array_column($requisition_number_list,'requisition_number');

                $response = Ufxfuiou::getTransferVoucher($trans_order['ufxfuiou_tran_num']);
                $result = xmlParser($response);// 成功的返回文件流，否则返回XML报文
                if(empty($result)){// 解析xml失败 则为文件流
                    // 文件名
                    $fileName    = ($ufxfuiou_tran_num.'_'.date('YmdHis'));
                    $ap_fileName = END_UPLOAD.'ufxfvoucher/'.$fileName.'.pdf';// PDF 格式
                    isDirAndCreate(dirname($ap_fileName));// 判断文件夹是否存在
                    $save_res = file_put_contents($ap_fileName, $response);// 保存文件

                    if(empty($save_res)) throw new Exception('富友回执单文件流保存失败');

                    $java_result = $this->upload_image->doUploadFastDfs('image', $ap_fileName);
                    if($java_result['code'] == 200){
                        $update_arr = [
                            'is_get_back' => 1,
                            'java_voucher_address' => $java_result['data']
                        ];
                        $this->purchase_db->where('ufxfuiou_tran_num',$ufxfuiou_tran_num)->update($this->table_name,$update_arr);

                        $update_arr = [
                            'java_voucher_address' => $java_result['data']
                        ];
                        $this->purchase_db->where('ufxfuiou_number',$pur_tran_num)->update('pay_ufxfuiou_expand',$update_arr);

                        // 请款单-回执地址
                        $this->purchase_db->where_in('requisition_number',$requisition_number_list)->update('purchase_order_pay',['voucher_address' => $java_result['data']]);
                    }else{
                        throw new Exception('富友回执单文件上传JAVA服务器失败');
                    }
                }else{
                    $rspDesc = isset($result['body']['rspDesc'])?$result['body']['rspDesc']:'富友回执单文件获取失败';
                    throw new Exception($rspDesc);
                }

                $success_list[$ufxfuiou_tran_num] = 1;
            }catch(Exception $e){
                $error_list[$ufxfuiou_tran_num] = $e->getMessage();
            }
        }

        $data['success_list'] = $success_list;
        $data['error_list'] = $error_list;

        $return['data'] = $data;
        return $return;
    }

}