<?php
/**
 * 宝付在线支付
 */
class Baofoo_fopay_model extends Purchase_model {


    protected $request_url_01; //宝付支付ip
    protected $request_url_02; //宝付查询ip
    protected $request_url_bfa01;//代付凭证文件接口
    protected $member_id;
    protected $terminal_id;
    protected $data_type;
    protected $password;
    protected $table_name   = 'purchase_order_pay_baofppay';
    protected $table_detail = 'purchase_order_pay_baofo_detail';

    public function __construct(){
        parent::__construct();
        $this->load->library('baofoo/BaofooSdk');
        $this->load->library('baofoo/request/TransContent');
        $this->load->library('baofoo/request/TransReqData');
        $this->load->library('baofoo/request/TransReqDataBF0040001');
        $this->load->library('baofoo/request/TransReqDataBF0040002');
        $this->load->library('baofoo/request/TransDataUtils');
        $this->load->model('finance/purchase_order_pay_model');
        $this->load->model('finance/Payment_order_pay_model');
        // 加载 URL 配置项
        $this->load->config('baofoo_config', false, true);
        if(!empty($this->config->item('baofoo'))){
            $baofoo                  = $this->config->item('baofoo');
            $this->request_url_01    = $baofoo['BF0040001'];
            $this->request_url_02    = $baofoo['BF0040002'];
            $this->request_url_bfa01 = $baofoo['BFA0040001'];
            $this->member_id         = $baofoo['member_id'];
            $this->terminal_id       = $baofoo['terminal_id'];
            $this->data_type         = $baofoo['data_type'];
            $this->password          = $baofoo['password'];
        }

    }



    /**
     * 宝付数据列表
     * @author harvin
     * @param array $get
     */
    public function get_baofoo_list(array $get, $offsets, $limit, $page){
        $query = $this->purchase_db;
        $query->select('id,to_acc_name,to_bank_name,pur_tran_num,pur_tran_num,trans_orderid,java_voucher_address,'
                       .'to_acc_no,trans_card_id,trans_mobile,pay_price,'
                       .'audit_status,supplier_code,trans_summary,remark,'
                       .'applicant_time,applicant,drawee,drawee_time,pay_time');
        $query->from($this->table_name);
        if(isset($get['ids']) && $get['ids']){
            $ids = explode(',', trim($get['ids']));
            $query->where_in('id', $ids);
        }
        if(isset($get['pur_number']) && $get['pur_number']){ //合同号
            $pur_number        = explode(' ', trim($get['pur_number']));
            $pur_number_list   = array_map(function($array){
                return sprintf("'%s'", $array);
            }, $pur_number);
            $pur_number_list   = array_unique($pur_number_list);
            $pur_number_list   = implode(',', $pur_number_list);
            $pur_tran_num_list = $query->query("SELECT pur_tran_num FROM pur_purchase_order_pay_baofo_detail WHERE pur_number in (".$pur_number_list.")")->result_array();
            $pur_tran_num_arr  = !empty($pur_tran_num_list) ? array_column($pur_tran_num_list, 'pur_tran_num') : [PURCHASE_NUMBER_ZFSTATUS];
            $query->where_in('pur_tran_num', $pur_tran_num_arr);
        }
        if (isset($get['pur_tran_num']) && trim($get['pur_tran_num'])) {
            $pur_tran_num = explode(' ', trim($get['pur_tran_num']));
            $query->where_in('pur_tran_num', array_filter($pur_tran_num));
        }
        if (isset($get['trans_orderid']) && trim($get['trans_orderid'])) {
            $trans_orderid = explode(' ', trim($get['trans_orderid']));
            $query->where_in('trans_orderid', array_filter($trans_orderid));
        }
        if(isset($get['supplier_code']) && $get['supplier_code']){
            $query->where_in('supplier_code', $get['supplier_code']);
        }
        if(isset($get['audit_status']) && $get['audit_status']){
            $query->where_in('audit_status', $get['audit_status']);
        }
        if(isset($get['create_time_start']) and $get['create_time_start']){// 申请时间-开始
            $query->where('applicant_time>=', $get['create_time_start']);
        }
        if(isset($get['create_time_end']) and $get['create_time_end']){// 申请时间-结束
            $query->where('applicant_time<=', $get['create_time_end']);
        }
        if(isset($get['payer_time_start']) and $get['payer_time_start']){// 付款时间-开始
            $query->where('pay_time>=', $get['payer_time_start']);
        }
        if(isset($get['payer_time_end']) and $get['payer_time_end']){// 付款时间-结束
            $query->where('pay_time<=', $get['payer_time_end']);
        }
        $count_qb = clone $query;
        $query->limit($limit, $offsets);
        $query->order_by('id', 'desc');
        $query->group_by('pur_tran_num');
        $data               = $query->get()->result_array();
        $count_row          = $count_qb->select('count(id) as num')->get()->row_array();
        $total_count        = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        $pur_tran_num       = is_array($data) ? array_column($data, 'pur_tran_num') : [];
        $supplier_code_list = is_array($data) ? array_column($data, 'supplier_code') : [];
        $pur_number_list    = $this->get_pur_number($pur_tran_num);
        $supplier_list      = $this->Payment_order_pay_model->get_supplier_name($supplier_code_list);
        foreach($data as $key => $vv){
            $data[$key]['pur_number']    = isset($pur_number_list[$vv['pur_tran_num']]) ? $pur_number_list[$vv['pur_tran_num']] : '';
            $data[$key]['supplier_name'] = isset($supplier_list[$vv['supplier_code']]) ? $supplier_list[$vv['supplier_code']] : '';
            $data[$key]['audit_status']  = getBaofoostatus2($vv['audit_status']);
        }
        $data_list['audit_status'] = getBaofoostatus2();
        $key_table                 = ['审核状态', '合同号', '供应商名称', '转账金额', '收款名称', '开户名称', '收款账号', '收款人身份证', '收款人手机号', '审核人审/核时间', '提交人/提交时间', '备注'];
        $return_data               = [
            'drop_down_box' => $data_list,
            'key'           => $key_table,
            'values'        => $data,
            'paging_data'   => [
                'total'  => $total_count,
                'offset' => $page,
                'limit'  => $limit,
                'pages'  => ceil($total_count / $limit)
            ]
        ];
        return $return_data;
    }


    /**
     * 获取宝付流水集合
     * @author harvin
     * @param array $pur_tran_num
     * @return array
     */
    public function get_pur_number(array $pur_tran_num){
        if(empty($pur_tran_num)){
            return [];
        }
        $order_datail = $this->purchase_db
            ->select('pur_tran_num,pur_number')
            ->where_in('pur_tran_num', $pur_tran_num)
            ->get($this->table_detail)
            ->result_array();
        $data         = [];
        foreach($order_datail as $key => $value){
            $data[$value['pur_tran_num']][] = $value['pur_number'];
        }
        if(empty($data)){
            return [];
        }
        $data_tran = [];
        foreach($data as $key => $row){
            $data_tran[$key] = implode(' ', $row);
        }

        return $data_tran;
    }

    /**
     * 验证宝付可支付数据
     * @author harvin
     * @date   2019/8/10
     * @param array $ids
     * @return array
     */
    public function get_baofoopay_status(array $ids){
        $baofoo = $this->purchase_db
            ->select('audit_status')
            ->where_in('id', $ids)
            ->get($this->table_name)
            ->result_array();
        if(empty($baofoo)){
            return ['code' => false, 'msg' => '审核数据不存在'];
        }
        $audit_status =[BAOFOOPAYSTATUS_1,BAOFOOPAYSTATUS_5];
        foreach($baofoo as $row){
            if(!in_array($row['audit_status'],$audit_status)){
                return ['code' => false, 'msg' => '不是待审核或者收款失败的状态数据'];
            }
        }

        return ['code' => true, 'msg' => '数据验证通过'];

    }

    /**
     * 宝付提交
     * @author harvin
     * @param array $faoPayDatas 宝付支付数据
     * @param float $procedure_party 运费承担方
     * @param float $procedure_fee 运费金额
     * @param float $account_short 支付账号简称
     * @return array
     */
    public function pay_faofoo_edit($faoPayDatas,$procedure_party,$procedure_fee,$account_short){
        $to_acc_name   = isset($faoPayDatas['to_acc_name']) ? $faoPayDatas['to_acc_name'] : '';
        $to_bank_name  = isset($faoPayDatas['to_bank_name']) ? $faoPayDatas['to_bank_name'] : '';
        $to_acc_no     = isset($faoPayDatas['to_acc_no']) ? $faoPayDatas['to_acc_no'] : '';
        $remark        = isset($faoPayDatas['remark']) ? $faoPayDatas['remark'] : '';
        $trans_money   = isset($faoPayDatas['trans_money']) ? $faoPayDatas['trans_money'] : 0;
        $trans_card_id = isset($faoPayDatas['trans_card_id']) ? $faoPayDatas['trans_card_id'] : 0;
        $trans_mobile  = isset($faoPayDatas['trans_mobile']) ? $faoPayDatas['trans_mobile'] : 0;
        $ids           = isset($faoPayDatas['ids']) ? $faoPayDatas['ids'] : 0;
        if(empty($to_acc_name)){
            return ['code' => false, 'msg' => '收款人姓名必填'];
        }
        if(empty($to_bank_name)){
            return ['code' => false, 'msg' => '收款人银行名称必填'];
        }
        if(empty($to_acc_no)){
            return ['code' => false, 'msg' => '收款人银行帐号必填'];
        }
        if(empty($trans_money)){
            return ['code' => false, 'msg' => '付款金额不能为0'];
        }
        if(empty($ids)){
            return ['code' => false, 'msg' => '参数ids 未勾选'];
        }
        $param  = [
            'pur_tran_num'   => $this->get_trans_no(),//流水号
            'to_acc_name'    => $to_acc_name,
            'to_bank_name'   => $to_bank_name,
            'to_acc_no'      => $to_acc_no,
            'trans_card_id'  => $trans_card_id,
            'trans_mobile'   => $trans_mobile,
            'pay_price'      => $trans_money,
            'applicant_id'   => getActiveUserId(),
            'applicant'      => getActiveUserName(),
            'applicant_time' => date("Y-m-d H:i:s"),
            'remark'         => $remark,
        ];
        $result = $this->purchase_order_pay_model->faofopay_save($ids, $param,$procedure_party,$procedure_fee,$account_short);

        return $result;
    }

    /**
     * 获取待审核页面金额
     * @author harvin
     * @param array $ids
     * @return array
     */
    public function get_baofoop_total(array $ids){
        $baofoototal = $this->purchase_db
            ->select('A.id,A.pay_price,A.pur_tran_num,B.pur_number,B.requisition_number,C.pay_price as pay_price_detail,A.supplier_code')
            ->from($this->table_name.' AS A')
            ->join($this->table_detail.' AS B','A.pur_tran_num=B.pur_tran_num','LEFT')
            ->join('purchase_order_pay AS C','C.requisition_number=B.requisition_number')
            ->where_in('A.id', $ids)
            ->get()
            ->result_array();
        $supplier_code_list = array_unique(array_column($baofoototal,'supplier_code'));
        if(count($supplier_code_list)>20){
            return ['code' => false, 'msg' => '不一致的供应商每次提交不可以超过20个！'];
        }
        $total       = 0;
        $data_list   = [];
        if(!empty($baofoototal)){
            foreach($baofoototal as $val){
                if(isset($data_list[$val['id']])){
                    $data_list[$val['id']][] = $val;
                }else{
                    $total += $val['pay_price'];
                    $data_list[$val['id']][] = $val;
                }
            }
        }

        return [
            'ids'   => implode(',', $ids),
            'total' => format_price($total),
            'data'  => $data_list
        ];
    }

    /**
     * 宝付在线支付
     * @author harvin
     * @date   2019-8-7
     * @return array
     */
    public function pay_faofoo_save(array $ids, $trans_summary){

        //获取需要推送宝付数据
        $baofoopay = $this->purchase_db->select("pur_tran_num,to_acc_name,pay_price,to_acc_no,trans_card_id,to_bank_name,trans_mobile,supplier_code")
            ->where_in('id', $ids)
            ->get($this->table_name)
            ->result_array();
        $success_list = $error_list = [];
        if(!empty($baofoopay)) {
            $datetype = $this->data_type;
            foreach ($baofoopay as $row) {
                $transReqDatas = new TransReqData();
                $Transdata = new TransReqDataBF0040001();
                $Transdata->_set("trans_no", $row['pur_tran_num']);
                $Transdata->_set("trans_money", $row['pay_price']);
                $Transdata->_set("to_acc_name", $row['to_acc_name']);
                $Transdata->_set("to_acc_no", $row['to_acc_no']);
                $Transdata->_set("to_bank_name", $row['to_bank_name']);
                $Transdata->_set("trans_card_id", $row['trans_card_id']);
                $Transdata->_set("trans_mobile", $row['trans_mobile']);
                $Transdata->_get('trans_summary', $trans_summary);
                $Transdata->_get('to_acc_dept');
                if ($datetype == "json") {
                    $transReqDatas->__array_json_push($Transdata->_getValues());
                } else {
                    $transReqDatas->__array_push($Transdata->_getValues());
                }
                $transReqDatas = $transReqDatas->__getTransReqDatas();
                $TransDataUtils = new TransDataUtils();
                if ($datetype == "json") {
                    $tmp = array();
                    array_push($tmp, array("trans_reqData" => $transReqDatas));
                    $trans_content1 = new TransContent();
                    $trans_content1->__set("trans_reqDatas", $tmp);

                    $trans_content = new TransContent();
                    $trans_content->__set("trans_content", $trans_content1->__getTransContent());

                    $data_content = $TransDataUtils->__array2Json($trans_content->__getTransContent());
                    $data_content = str_replace("\\\"", '"', $data_content);
                } else {
                    $trans_content = new TransContent();
                    $trans_content->__set("trans_reqDatas", $transReqDatas);
                    $data_content = $TransDataUtils->__array2Xml($trans_content->__getTransContent());
                }
                $request_url = $this->request_url_01;
                // 私钥加密
                $baofooSdk = new BaofooSdk();
                $baofooSdk->Verification($this->member_id, $this->terminal_id, $this->data_type, $this->password);
                $encrypted = $baofooSdk->encryptedByPrivateKey($data_content);
                $httpResult = $baofooSdk->post($encrypted, $request_url);
                if (count(explode("trans_content", $httpResult)) > 1) {  //返回错误
                    $reslut = json_decode($httpResult, true);
                    $return_msg = isset($reslut['trans_content']['trans_head']['return_msg']) ? $reslut['trans_content']['trans_head']['return_msg'] : "宝付支付失败";
                    $error_list[] = $row['pur_tran_num'] . ':' . $return_msg;//异常信息
                } else {
                    //业务逻辑信息处理
                    $decrypt = $baofooSdk->decryptByPublicKey($httpResult);
                    $reslut = json_decode($decrypt, true);
                    if (isset($reslut['trans_content']['trans_head']['return_code']) && $reslut['trans_content']['trans_head']['return_code'] == '0000') {
                        //交易成功
                        $results = $this->purchase_order_pay_model->faofopay_pay_update($ids, $reslut, $trans_summary);
                        if ($results['code']) {
                            $success_list[] = $row['pur_tran_num'] . ':' . $results['message'];
                        } else {
                            $error_list[] = $row['pur_tran_num'] . ':' . $results['message'];
                        }
                    } else { //交易失败
                        $return_msg = isset($reslut['trans_content']['trans_head']['return_msg']) ? $reslut['trans_content']['trans_head']['return_msg'] : "宝付支付失败";
                        $error_list[] = $row['pur_tran_num'] . ':' . $return_msg;
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
     * 宝付驳回
     * @author harvin
     * @param array $ids
     * @param type  $trans_summary
     * @return type
     * @throws Exception
     */
    public function pay_faofoo_reject(array $ids, $trans_summary){
        try{
            //开启事物
            $this->purchase_db->trans_begin();
            $baofoo = $this->purchase_db
                ->select('pur_tran_num')
                ->where_in('id', $ids)
                ->get($this->table_name)
                ->result_array();
            if(empty($baofoo)){
                return ['code' => false, 'message' => '宝付付款流水不存在'];
            }
            $pur_tran_num = array_column($baofoo, 'pur_tran_num');
            $baofo_detail = $this->purchase_db
                ->select('pur_number,requisition_number')
                ->where_in('pur_tran_num', $pur_tran_num)
                ->get($this->table_detail)
                ->result_array();
            if(empty($baofo_detail)){
                return ['code' => false, 'message' => '宝付流水表未绑定合同'];
            }
            $requisition_number_list = is_array($baofo_detail) ? array_column($baofo_detail, 'requisition_number') : [];
            $pur_number_list         = is_array($baofo_detail) ? array_column($baofo_detail, 'pur_number') : [];
            //更新宝付付款流水
            $data_baofoo = [
                'audit_status'  => BAOFOOPAYSTATUS_3,
                'trans_summary' => $trans_summary,
                'drawee'        => !empty(getActiveUserName()) ? getActiveUserName() : "system",
                'drawee_id'     => getActiveUserId(),
                'pay_time'      => date('Y-m-d H:i:s'),
                'drawee_time'   => date('Y-m-d H:i:s'),
            ];
            $this->purchase_db->where_in('id', $ids)->update('purchase_order_pay_baofppay', $data_baofoo);
            unset($data_baofoo);
            //获取请款单号
            $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', [
                'pay_status' => PAY_WAITING_FINANCE_PAID,
                'payment_notice' => '宝付审核驳回'.$trans_summary,
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

                return ['code' => false, 'message' => '宝付驳回失败'];
            }else{
                $this->purchase_db->trans_commit();

                return ['code' => true, 'message' => '宝付驳回成功'];
            }
        }catch(Exception $exc){
            return ['code' => false, 'message' => $exc->getMessage()];
        }
    }

    /**
     * 宝付 获取订单回执信息
     * @author Jolon
     * @param string $pur_tran_num
     * @param int $debug
     * @return array
     */
    public function pay_baofoo_voucher($pur_tran_num = null,$debug = 0){
        $return = ['code' => false, 'data' => '', 'msg' => ''];
        if(empty($pur_tran_num)){
            $trans_order_list = $this->purchase_db->select('*')
                ->where_in('audit_status', [2,4])
                ->where('voucherId','')
                ->get($this->table_name,50)
                ->result_array();

            if($trans_order_list){
                foreach($trans_order_list as $trans_order){
                    $pur_tran_num = $trans_order['pur_tran_num'];
                    try{// 空异常-无需处理
                        file_get_contents(CG_SYSTEM_APP_DAL_IP.'baofoo_fopay_api/get_pay_baofoo_voucher?pur_tran_num='.$pur_tran_num);// 自动运行抓取回执信息
                    }catch(Exception $e){

                    }
                }
                echo 'sss';exit;
            }
        }else{
            $trans_order_list = $this->purchase_db->select('*')
                ->where('pur_tran_num', $pur_tran_num)
                ->get($this->table_name)
                ->result_array();
        }
        if(empty($trans_order_list)){
            $return['msg'] = '没有找到对应的记录';
            return $return;
        }

        $this->load->helper('file_remote');
        $this->load->library('Filedirdeal');
        $this->load->library('Upload_image');

        $baofuBasePath = END_UPLOAD.'baofoovoucher/';
        $this->filedirdeal->removeDir($baofuBasePath);// 文件夹存在则执行删除（保留根目录）

        $success_list = [];
        $error_list = [];

        foreach($trans_order_list as $trans_order){
            $pur_tran_num = $trans_order['pur_tran_num'];
            try{
                // 组装 宝付需要的数据格式
                $transReqDatas     = new TransReqData();
                $Transdata         = new TransReqDataBF0040002();
                $Transdata->values = [
                    'trans_batchid' => '',// 必传但是为空
                    'trans_no'      => '',
                    'trans_orderid' => '',// 必传但是为空
                    'trans_date'    => ''
                ];
                $Transdata->_set("trans_no",$pur_tran_num);
                $Transdata->_set("trans_date", substr($trans_order['trans_date'], 0, 10));
                if($this->data_type == "json"){
                    $transReqDatas->__array_json_push($Transdata->_getValues());
                }else{
                    $transReqDatas->__array_push($Transdata->_getValues());
                }
                $transReqDatas = $transReqDatas->__getTransReqDatas();

                $TransDataUtils = new TransDataUtils();
                if($this->data_type == "json"){
                    $tmp = array();
                    array_push($tmp, array("trans_reqData" => $transReqDatas));
                    $trans_content1 = new TransContent();
                    $trans_content1->__set("trans_reqDatas", $tmp);

                    $trans_content = new TransContent();
                    $trans_content->__set("trans_content", $trans_content1->__getTransContent());

                    $data_content = $TransDataUtils->__array2Json($trans_content->__getTransContent());
                    $data_content = str_replace("\\\"", '"', $data_content);
                }else{
                    $trans_content = new TransContent();
                    $trans_content->__set("trans_reqDatas", $transReqDatas);
                    $data_content = $TransDataUtils->__array2Xml($trans_content->__getTransContent());
                }
                $request_url = $this->request_url_bfa01;


                // 私钥加密
                $baofooSdk = new BaofooSdk();
                $baofooSdk->Verification($this->member_id, $this->terminal_id, $this->data_type, $this->password);
                $encrypted  = $baofooSdk->encryptedByPrivateKey($data_content);
                $httpResult = $baofooSdk->post($encrypted, $request_url);
                if($debug) print_r($httpResult);
                if(count(explode("trans_content", $httpResult)) > 1){  //返回错误
                    $reslut     = json_decode($httpResult, true);
                    $return_msg = isset($reslut['trans_content']['trans_head']['return_msg']) ? $reslut['trans_content']['trans_head']['return_msg'] : "宝付支付回执获取失败";

                    throw new Exception($return_msg);
                }else{
                    //业务逻辑信息处理
                    $decrypt = $baofooSdk->decryptByPublicKey($httpResult);
                    $reslut  = json_decode($decrypt, true);
                    if($debug) print_r($reslut);
                    if(isset($reslut['trans_content']['trans_head']['return_code']) && $reslut['trans_content']['trans_head']['return_code'] == '0000'){
                        $data_list       = $reslut['trans_content']['trans_reqDatas'][0]['trans_reqData'];
                        $voucher_id      = $data_list['voucher_id'];
                        $voucher_address = $data_list['voucher_address'];
                        $voucher_address = base64_decode($voucher_address);

                        $java_url_list                         = [];
                        $updata_baofoo                         = [];
                        $updata_baofoo['voucher_address']      = $voucher_address;
                        $updata_baofoo['voucherId']            = $voucher_id;
                        $updata_baofoo['voucher_name']         = $data_list['voucher_name'];
                        $updata_baofoo['effective_time']       = $data_list['effective_time'];
                        $updata_baofoo['voucher_size']         = $data_list['voucher_size'];
                        $updata_baofoo['java_voucher_address'] = $data_list['voucher_name'];

                        $fileName  = ($voucher_id.'_'.date('YmdHis'));
                        $ap_fileName = $baofuBasePath.$fileName.'.zip';

                        // 下载远程ZIP文件到本地、解压文件、上传到JAVA服务器、更新系统文件地址
                        // 1.下载文件到本地
                        downFile($ap_fileName,$voucher_address);

                        if(!file_exists($ap_fileName)){// 文件下载失败
                            throw new Exception('宝付支付回执保存失败');
                        }else{
                            // 2.解压ZIP文件
                            $out_filePath = dirname($ap_fileName).'/'.$fileName;
                            $zip = new ZipArchive();
                            $openRes = $zip->open($ap_fileName);
                            if ($openRes === TRUE) {
                                $zip->extractTo($out_filePath);
                                $zip->close();
                                // 3.上传文件到 JAVA服务器
                                if(!file_exists($out_filePath)){
                                    throw new Exception('宝付支付回执保存成功但解压生成文件失败');
                                }else{
                                    $fileList = $this->filedirdeal->readAllFile($out_filePath);
                                    if($fileList){
                                        foreach($fileList as $value_path){
                                            $java_result = $this->upload_image->doUploadFastDfs('image',$value_path,false);
                                            if($java_result['code'] == 200){
                                                $java_url_list[] = $java_result['data'];
                                            }else{
                                                throw new Exception('宝付支付回执上传JAVA服务器失败');
                                            }
                                        }
                                        $updata_baofoo['java_voucher_address']    = implode(';',$java_url_list);
                                    }else{
                                        throw new Exception('宝付支付回执解压文件夹为空');
                                    }
                                }

                            }else{
                                throw new Exception('宝付支付回执保存成功但解压失败');
                            }
                        }
                        //交易成功
                        $results = $this->purchase_order_pay_model->faofopay_voucher_update($trans_order['id'], $updata_baofoo);
                        if(empty($results['code'])){
                            throw new Exception($results['message']);
                        }

                        // 请款单-回执地址
                        $requisition_number_list = $this->purchase_db->select('requisition_number')
                            ->where('pur_tran_num', $pur_tran_num)
                            ->get($this->table_detail)
                            ->result_array();
                        if($requisition_number_list){
                            // 请款单-回执地址
                            $requisition_number_list = array_column($requisition_number_list,'requisition_number');
                            $this->purchase_db->where_in('requisition_number',$requisition_number_list)->update('purchase_order_pay',['voucher_address' => $updata_baofoo['java_voucher_address']]);
                        }
                    }else{ //交易失败
                        $return_msg = isset($reslut['trans_content']['trans_head']['return_msg']) ? $reslut['trans_content']['trans_head']['return_msg'] : "宝付支付回执获取失败";
                        throw new Exception($return_msg);
                    }
                }

                $success_list[$trans_order['pur_tran_num']] = 1;
            }catch(Exception $e){

                $error_list[$trans_order['pur_tran_num']] = $e->getMessage();

                // 付款失败的记录标记为 不需要再抓取
                if(stripos($error_list[$trans_order['pur_tran_num']],'不存在成功订单') !== false){
                    $this->purchase_db->where('pur_tran_num',$pur_tran_num)->update($this->table_name,['voucherId' => '不存在成功订单']);
                }
            }

            sleep(3);// 等待释放文件夹
        }

        $data['success_list'] = $success_list;
        $data['error_list'] = $error_list;

        $return['data'] = $data;
        return $return;
    }

    /**
     * 生成流水号
     * @return string
     */
    protected function get_trans_no(){
        $member_id = $this->member_id;

        return $member_id.date("YmdHis").rand(100, 999);
    }

    /**
     * 根据redis 获取可用数据
     * @author harvin
     * @date   2019-8-7
     * @return array
     */
    public function get_baofoo_query($id){
        $baofppay = $this->purchase_db
            ->select('audit_status')
            ->where('id', $id)
            ->get($this->table_name)
            ->row_array();
        if(empty($baofppay)){
            return ['code' => false, 'msg' => '流水表数据不存在'];
        }
        if($baofppay['audit_status'] != BAOFOOPAYSTATUS_1){
            return ['code' => false, 'msg' => '审核状态不存在'];
        }

        return ['code' => true, 'msg' => '有数据'];
    }

    /**
     * 根据redis 获取可用数据付数据
     * @author harvin
     * @param array $data
     * @return array
     */
    public function get_baofoo_query_list(array $data, $id){
        $results = $this->purchase_order_pay_model->faofopay_pay_update([$id], $data, '宝付付款成功', false);

        return $results;
    }

    /**
     * 代付交易状态查证接口
     * @author totoro
     * @param null $pur_tran_num
     * @param int $debug
     */
    public function pay_baofoo_voucher_status($pur_tran_num = null,$debug = 0){
        $return = ['code' => false, 'data' => '', 'message' => ''];
        if(empty($pur_tran_num)){
            $trans_order_list = $this->purchase_db->select('*')
                ->where('audit_status', 2)
                ->where('trans_date <',date('Y-m-d H:i:s',time() - 5 * 60))  // 只查询 5分钟之前的数据，避免单据未推送到宝付
                ->get($this->table_name,50)
                ->result_array();
        }else{
            $trans_order_list = $this->purchase_db->select('*')
                ->where('pur_tran_num', $pur_tran_num)
                ->where('trans_date <',date('Y-m-d H:i:s',time() - 5 * 60))
                ->get($this->table_name)
                ->result_array();
        }
        if(empty($trans_order_list)){
           $return['message'] = '没有找到对应的记录';
            return $return;
        }
        $error_list = $success_list = [];
        foreach($trans_order_list as $trans_order){
            $pur_tran_num = $trans_order['pur_tran_num'];
            $trans_batchid = $trans_order['trans_batchid'];
            try{
                // 组装 宝付需要的数据格式
                $transReqDatas     = new TransReqData();
                $Transdata         = new TransReqDataBF0040002();
                $Transdata->_set("trans_no",$pur_tran_num);
                if (empty($trans_batchid)) $trans_batchid = '';
                $Transdata->_set("trans_batchid",$trans_batchid);
                if($this->data_type == "json"){
                    $transReqDatas->__array_json_push($Transdata->_getValues());
                }else{
                    $transReqDatas->__array_push($Transdata->_getValues());
                }
                $transReqDatas = $transReqDatas->__getTransReqDatas();

                $TransDataUtils = new TransDataUtils();
                if($this->data_type == "json"){
                    $tmp = array();
                    array_push($tmp, array("trans_reqData" => $transReqDatas));
                    $trans_content1 = new TransContent();
                    $trans_content1->__set("trans_reqDatas", $tmp);

                    $trans_content = new TransContent();
                    $trans_content->__set("trans_content", $trans_content1->__getTransContent());
                    $data_content = $TransDataUtils->__array2Json($trans_content->__getTransContent());
                    $data_content = str_replace("\\\"", '"', $data_content);
                }else{
                    $trans_content = new TransContent();
                    $trans_content->__set("trans_reqDatas", $transReqDatas);
                    $data_content = $TransDataUtils->__array2Xml($trans_content->__getTransContent());
                }
                $request_url = $this->request_url_02;
                // 私钥加密
                $baofooSdk = new BaofooSdk();
                $baofooSdk->Verification($this->member_id, $this->terminal_id, $this->data_type, $this->password);
                $encrypted  = $baofooSdk->encryptedByPrivateKey($data_content);
                $httpResult = $baofooSdk->post($encrypted, $request_url);
                if($debug) print_r($httpResult);
                if(count(explode("trans_content", $httpResult)) > 1){  //返回错误
                    $reslut     = json_decode($httpResult, true);
                    $return_msg = isset($reslut['trans_content']['trans_head']['return_msg']) ? $reslut['trans_content']['trans_head']['return_msg'] : "代付交易状态查证失败!";
                    throw new Exception($return_msg);
                }else{
                     //业务逻辑信息处理
                    $decrypt = $baofooSdk->decryptByPublicKey($httpResult);
                    $reslut  = json_decode($decrypt, true);
                    if($debug) pr($reslut);
                    $ids = $trans_order['id'];
                    if(isset($reslut['trans_content']['trans_head']['return_code']) && $reslut['trans_content']['trans_head']['return_code'] == '0000'){

                        if(isset($reslut['trans_content']['trans_reqDatas'][0]['trans_reqData']) and is_array($reslut['trans_content']['trans_reqDatas'][0]['trans_reqData'])){
                            $reqDatas = $reslut['trans_content']['trans_reqDatas'][0]['trans_reqData'];
                            $judge = false;
                            if($reqDatas['state']==1){
                                $data_baofoo = [
                                    'audit_status'  => BAOFOOPAYSTATUS_4
                                ];
                            }elseif ($reqDatas['state']==-1 or $reqDatas['state']==2){
                                $data_baofoo = [
                                    'audit_status'  => BAOFOOPAYSTATUS_5
                                ];
                                $judge = true;
                            }else{
                                $data_baofoo = [
                                    'audit_status'  => BAOFOOPAYSTATUS_2
                                ];
                            }
                            if ($judge){
                                $error_list[] = array(
                                    'pur_tran_num' => $pur_tran_num,
                                    'return_msg'=> $reqDatas['trans_remark'],
                                );
                            }else{
                                $success_list[] = array(
                                    'pur_tran_num' => $pur_tran_num,
                                );
                            }
                            $res = $this->purchase_db->where('id', $ids)->update('purchase_order_pay_baofppay', $data_baofoo);

                            if($res and $data_baofoo['audit_status'] == BAOFOOPAYSTATUS_4){
                                // 收款成功才更新付款状态为已付款
                                $baofo_detail = $this->purchase_db->select('requisition_number')->where('pur_tran_num', $pur_tran_num)->get('purchase_order_pay_baofo_detail')->result_array();
                                $requisition_number_list = is_array($baofo_detail) ? array_column($baofo_detail, 'requisition_number') : [];
                                if(empty($requisition_number_list)){
                                    throw new Exception('宝付流水未绑定请款单号');
                                }
                                $requisition_number_list = array_unique($requisition_number_list);
                                $this->purchase_order_pay_model->update_order_pay_compact($requisition_number_list, $trans_order['trans_summary'], $trans_order['pay_time']);

                                // 更新请款单记录
                                $data_pay = [
                                    'pur_tran_num'  => $trans_order['pur_tran_num'],
                                    'trans_orderid' => $trans_order['trans_orderid'],
                                    'payer_id'      => $trans_order['drawee_id'],
                                    'payer_name'    => $trans_order['drawee'],
                                ];
                                $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', $data_pay);
                            }
                        }
                    }else{ //交易失败
                        $return_msg = isset($reslut['trans_content']['trans_head']['return_msg']) ? $reslut['trans_content']['trans_head']['return_msg'] : "宝付支付失败-未获取到信息";
                        $error_list[] = array(
                            'pur_tran_num' => $pur_tran_num,
                            'return_msg'=> $return_msg,
                        );
                    }
                }
            }catch(Exception $e){
                continue;
            }
        }
        $data['success'] = $success_list;
        $data['error'] = $error_list;
        $return['data'] = $data;
        return  $return;
    }
}

