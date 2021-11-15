<?php
/**
 * 拉卡拉支付接口
 * Created by PhpStorm.
 * User: totoro Jolon
 * Date: 2020-06-02
 * Time: 17:03
 */

class Lakala_pay_model extends Purchase_model {

    protected $table_name       = "pur_pay_lakala";
    protected $table_name_entry = "pur_pay_lakala_detail";

    public function __construct(){
        parent::__construct();
        $this->load->model('finance/purchase_order_pay_model');
        $this->load->model('finance/Payment_order_pay_model');
        $this->load->model('system/Bank_card_model', 'bankCart');
    }

    /**
     * 生成流水号
     * @param string $platform_type 支付平台类型
     * @return string
     * @throws Exception
     */
    protected function get_trans_no($platform_type){
        $platform_type = strtolower($platform_type);

        if($platform_type == 'lakala'){
            $this->load->library('Lakala');
            $lakala = new Lakala();
            return $lakala->get_trans_no();

        }elseif($platform_type == 'cebbank'){
            $this->load->library('CebBank');
            $cebBank = new CebBank();
            return $cebBank->createBatchId();
        }else{
            throw new Exception('支付平台类型错误');
        }
    }

    /**
     * 生成拓展单据编号 order_number_1
     * @param string $platform_type 支付平台类型
     * @return string
     */
    protected function get_order_number_1($platform_type){
        $platform_type = strtolower($platform_type);
        try{
            if($platform_type == 'cebbank'){
                $this->load->library('CebBank');
                $cebBank = new CebBank();
                return $cebBank->createClientPatchID();// order_number_1 = ClientPatchID
            }else{
                return '';
            }
        }catch( Exception $exception){
            return $exception->getMessage();
        }
    }

    /**
     * 拉卡拉提交
     * @param array $PayDatas 拉卡拉支付数据
     * @param float $procedure_party 运费承担方
     * @param float $procedure_fee 运费金额
     * @param float $account_short 支付账号简称
     * @return array
     */
    public function pay_lakala($PayDatas,$procedure_party,$procedure_fee,$account_short){
        $to_acc_name   = isset($PayDatas['to_acc_name']) ? $PayDatas['to_acc_name'] : '';
        $to_bank_name_main  = isset($PayDatas['to_bank_name_main']) ? $PayDatas['to_bank_name_main'] : '';
        $to_bank_name  = isset($PayDatas['to_bank_name']) ? $PayDatas['to_bank_name'] : '';
        $to_acc_no     = isset($PayDatas['to_acc_no']) ? $PayDatas['to_acc_no'] : '';
        $remark        = isset($PayDatas['remark']) ? $PayDatas['remark'] : '';
        $trans_money   = isset($PayDatas['trans_money']) ? $PayDatas['trans_money'] : 0;
        $trans_card_id = isset($PayDatas['trans_card_id']) ? $PayDatas['trans_card_id'] : 0;
        $trans_mobile  = isset($PayDatas['trans_mobile']) ? $PayDatas['trans_mobile'] : 0;
        $ids           = isset($PayDatas['ids']) ? $PayDatas['ids'] : 0;
        $bank_code     = isset($PayDatas['bank_code'])?$PayDatas['bank_code']:'';
        $platform_type = isset($PayDatas['platform_type'])?$PayDatas['platform_type']:'';
        if(empty($to_acc_name)){
            return ['code' => false, 'msg' => '收款人姓名必填'];
        }
        if(empty($to_bank_name) or empty($to_bank_name_main)){
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
        if(empty($platform_type)){
            return ['code' => false, 'msg' => '支付平台类型必填'];
        }

        try{

            $bank = $this->bankCart->get_payment_account_by_platform($platform_type);//获取银行卡信息
            if(empty($bank)){
                throw new Exception('支付账号信息配置缺失');
            }

            switch ($platform_type){
                case "lakala":// 拉卡拉平台需要自己生产批次号
                    $this->load->library('Lakala');
                    $lakala = new Lakala();
                    $file_batch_no = $lakala->get_file_batch_no();
                    break;
                default :
                    $file_batch_no = '';
                    break;
            }

            $param  = [
                'cust_order_no'  => $this->get_trans_no($platform_type),//流水号
                'order_number_1' => $this->get_order_number_1($platform_type),
                'file_batch_no'  => $file_batch_no,
                'acc_name'       => $to_acc_name,//收款方名称
                'bank_name_main' => $to_bank_name_main,//收款方名称-主行
                'bank_name'      => $to_bank_name,//开户行名称-支行
                'bank_code'      => $bank_code,//开户行支行code
                'acc_no'         => $to_acc_no,//收款账号
                'cert_no'        => $trans_card_id,//收款方证件号
                'phone_no'       => $trans_mobile,//收款方手机号
                'amount'         => $trans_money,//付款金额
                'payer_acc_name' => $bank['account_holder'],
                'payer_acc_no'   => $bank['account_number'],
                'applicant_id'   => getActiveUserId(),
                'applicant'      => getActiveUserName(),
                'applicant_time' => date("Y-m-d H:i:s"),
                'remark'         => $remark,
                'platform_type'  => $platform_type,
            ];
            $result = $this->lakala_save($ids, $param,$procedure_party,$procedure_fee,$account_short);

            return $result;
        }catch(Exception $exception){
            return ['code' => false, 'msg' => $exception->getMessage()];
        }
    }

    /**
     *  保存拉卡拉数据
     * @param $ids
     * @param $reslut
     * @param float $procedure_party 运费承担方
     * @param float $procedure_fee 运费金额
     * @param float $account_short 支付账号简称
     * @return array
     */
    public function lakala_save($ids,$reslut,$procedure_party,$procedure_fee,$account_short){
        $this->load->model('Reject_note_model');
        $this->load->model('compact/Compact_model');
        try {
            if (empty($ids)) {
                throw new Exception('参数不存在');
            }
            $platform_type = $reslut['platform_type'];
            $bankInfo = $this->bankCart->findOne(['account_short' => $account_short, 'status' => 1]);
            if(empty($bankInfo)){
                throw new Exception('支付账号获取失败');
            }
            $ids = explode(',', $ids);
            //开启事物
            $this->purchase_db->trans_begin();
            $order_pay=$this->purchase_db->select('pur_number,requisition_number,supplier_code,pay_status')
                ->where_in('id', $ids)
                ->get('purchase_order_pay')
                ->result_array();
            if(empty($order_pay)){
                throw new Exception('请款单不存在');
            }

            $pay_update_date = [
                'pay_status'       => PAY_LAKALA,
                'payment_platform' => $platform_type,
                'procedure_party'  => $procedure_party,
                'procedure_fee'    => $procedure_fee,
                'pay_account'      => $bankInfo['account_short'],
                'pay_number'       => $bankInfo['account_holder'],
                'k3_account'       => $bankInfo['k3_bank_account'],
                'pay_branch_bank'  => $bankInfo['branch'],
            ];
            //记录请款单
            $this->purchase_db->where_in('id', $ids)->update('purchase_order_pay', $pay_update_date);

            foreach ($order_pay as  $row) {
                if($row['pay_status'] != PAY_WAITING_FINANCE_PAID){
                    throw new Exception("请款单[".$row['requisition_number']."]不是待财务付款状态");
                }
                $result = $this->Compact_model->change_compact_pay_status($row['pur_number'], PAY_LAKALA);
                if (!$result['code']) {
                    throw new Exception($result['msg']);
                }
                $supplier_code = $row['supplier_code'];
                $lakala_detail = [
                    'cust_order_no' => $reslut['cust_order_no'],
                    'pur_number' => $row['pur_number'],
                    'requisition_number' => $row['requisition_number'],
                    'create_time' => date('Y-m-d H:i:s'),
                ];

                $res_inset= $this->purchase_db->insert('pay_lakala_detail',$lakala_detail);
                if(empty($res_inset)) {
                    throw new Exception('记录付款流水明细失败');
                }
                //记录操作日志
                $log = [
                    'record_number'  => isset($row['requisition_number']) ? $row['requisition_number'] : '',
                    'record_type'    => 'PUR_PURCHASE_ORDER_PAY',
                    'content'        => $platform_type.' 支付提交成功',
                    'content_detail' => (isset($row['requisition_number']) ? $row['requisition_number'] : '').'已提 '.$platform_type.' 支付,待 '.$platform_type.' 支付审核'
                ];
                $this->Reject_note_model->get_insert_log($log);
                unset($lakala_detail);
            }
            $reslut['supplier_code']= $supplier_code;

            $re_inset= $this->purchase_db->insert($this->table_name,$reslut);
            if(empty($re_inset)) {
                throw new Exception('记录付款流水失败');
            }
            if ($this->purchase_db->trans_status() === FALSE)
            {
                $this->purchase_db->trans_rollback();
                return ['code'=>false,'msg'=>'支付请求提交失败'];
            }
            else
            {
                $this->purchase_db->trans_commit();
                return ['code'=>TRUE,'msg'=>'支付请求提交成功,等待审核'];
            }
        } catch (Exception $exc) {
            return ['code' => false, 'msg' => $exc->getMessage()];
        }
    }

    /**
     * head
     * @return array
     */
    public function get_head_list(){
        return [
            "审核状态",
            "合同号",
            "供应商名称",
            "转账金额",
            "收款名称",
            "开户名称",
            "收款账号",
            "收款人身份证",
            "收款人手机号",
            "审核人审/核时间",
            "提交人/提交时间",
            "备注"
        ];
    }

    /**
     * 拉卡拉数据量列表
     * @param array $get 查询参数
     * @param int $offsets 偏移量
     * @param int $limit 每页条数
     * @param int $page 当前页码
     * @return array
     */
    public function LaKaLa_list($get,$offsets, $limit,$page){
        $query = $this->purchase_db;
        $query->select("id,cust_order_no pur_tran_num,acc_type,acc_no,acc_name,file_batch_no,phone_no,supplier_code,audit_status,amount,cert_no,bank_code,bank_name,cnaps_code,summary,payer_acc_name,payer_acc_no,remark,applicant_time,drawee,drawee_time,applicant,voucherId,note,submit_date,java_voucher_address,lakala_state")
            ->from($this->table_name);
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
            $cust_order_no_list = $query->query("SELECT cust_order_no FROM pur_pay_lakala_detail WHERE pur_number in (".$pur_number_list.")")->result_array();
            $cust_order_no_arr  = !empty($cust_order_no_list) ? array_column($cust_order_no_list, 'cust_order_no') : [PURCHASE_NUMBER_ZFSTATUS];
            $query->where_in('cust_order_no', $cust_order_no_arr);
        }
        if (isset($get['pur_tran_num']) && trim($get['pur_tran_num'])) {
            $pur_tran_num = explode(' ', trim($get['pur_tran_num']));
            $query->where_in('cust_order_no', array_filter($pur_tran_num));
        }
        if (isset($get['trans_orderid']) && trim($get['trans_orderid'])) {
            $trans_orderid = explode(' ', trim($get['trans_orderid']));
            $query->where_in('file_batch_no', array_filter($trans_orderid));
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
        if(isset($get['platform_type']) and $get['platform_type']){// 平台类型
            $query->where('platform_type', $get['platform_type']);
        }

        $count_qb = clone  $query;
        $query->limit($limit,$offsets);
        $data = $query->order_by('id desc')->get()->result_array();
        $count_row = $count_qb->select('count(id) as num')->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;

        $pur_tran_num       = is_array($data) ? array_column($data, 'pur_tran_num') : [];
        $supplier_code_list = is_array($data) ? array_column($data, 'supplier_code') : [];
        $pur_number_list    = $this->get_lakala_pur_number($pur_tran_num);

        $supplier_list      = $this->Payment_order_pay_model->get_supplier_name($supplier_code_list);


        $this->load->library('Lakala');
        $lakala = new Lakala();
        $lakala_state_list = $lakala->lakala_state;

        foreach($data as $key => $vv){
            $data[$key]['pur_number']    = isset($pur_number_list[$vv['pur_tran_num']]) ? $pur_number_list[$vv['pur_tran_num']] : '';
            $data[$key]['supplier_name'] = isset($supplier_list[$vv['supplier_code']]) ? $supplier_list[$vv['supplier_code']] : '';
            $data[$key]['audit_status']  = getBaofoostatus($vv['audit_status']);
            $data[$key]['drawee'] = empty($vv['drawee'])?'':$vv['drawee'];
            $lakala_state_note = isset($lakala_state_list[$vv['lakala_state']])?$lakala_state_list[$vv['lakala_state']]:'';
            $remark = [$vv['remark'],$vv['note'],$lakala_state_note];
            $data[$key]['remark'] = implode(',',array_filter($remark));// 组合显示备注
        }

        $drop_down_list['audit_status'] = getBaofoostatus();
        $return_data = [
            'key'=> $this->get_head_list(),
            'values'=> $data,
            'drop_down_box' => $drop_down_list,
            'paging_data'=>[
                'total' => $total_count,
                'offset'=>$page,
                'limit'=>$limit,
                'pages'=> ceil($total_count/$limit),
            ]
        ];
        return $return_data;
    }

    /**
     * 获取宝付流水集合
     * @param array $pur_tran_num
     * @return array
     */
    public function get_lakala_pur_number(array $pur_tran_num){
        if(empty($pur_tran_num)){
            return [];
        }
        $order_datail = $this->purchase_db
            ->select('cust_order_no pur_tran_num,pur_number')
            ->where_in('cust_order_no', $pur_tran_num)
            ->get($this->table_name_entry)
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
     * 拉卡拉状态
     * @param $ids
     * @return array
     */
    public function lakala_status($ids){
        $lakala = $this->purchase_db
            ->select('audit_status')
            ->where_in('id', $ids)
            ->get($this->table_name)
            ->result_array();

        if (empty($lakala)) {
            return ['code' => false, 'msg' => '审核数据不存在'];
        }
        $audit_status = [BAOFOOPAYSTATUS_1];
        foreach ($lakala as $row) {
            if (!in_array($row['audit_status'], $audit_status)) {
                return ['code' => false, 'msg' => '不是待审核或者收款失败的状态数据'];
            }
        }
        return ['code' => true, 'msg' => '数据验证通过'];
    }

    /**
     * 支付审核与驳回列表
     */
    public function pay_Audit_list($ids){
        $lakalatotal = $this->purchase_db
            ->select('A.id,A.amount pay_price,A.cust_order_no,B.pur_number,B.requisition_number,C.pay_price as pay_price_detail,A.supplier_code')
            ->from($this->table_name.' AS A')
            ->join($this->table_name_entry.' AS B','A.cust_order_no=B.cust_order_no','LEFT')
            ->join('purchase_order_pay AS C','C.requisition_number=B.requisition_number')
            ->where_in('A.id', $ids)
            ->get()
            ->result_array();
        $supplier_code_list = array_unique(array_column($lakalatotal,'supplier_code'));
        if(count($supplier_code_list)>20){
            return ['code' => false, 'msg' => '不一致的供应商每次提交不可以超过20个！'];
        }
        $total       = 0;
        $data_list   = [];
        if(!empty($lakalatotal)){
            foreach($lakalatotal as $val){
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
     * 拉卡拉驳回
     */
    public function pay_lakala_reject($ids, $trans_summary){
        try{
            //开启事物
            $this->purchase_db->trans_begin();
            $lakala = $this->purchase_db
                ->select('cust_order_no,audit_status')
                ->where_in('id', $ids)
                ->get($this->table_name)
                ->result_array();
            if(empty($lakala)){
                return ['code' => false, 'message' => '付款流水不存在'];
            }
            $pur_tran_num = array_column($lakala,'cust_order_no');
            $audit_status_list = array_unique(array_column($lakala,'audit_status'));
            $lakala_detail = $this->purchase_db
                ->select('pur_number,requisition_number')
                ->where_in('cust_order_no', $pur_tran_num)
                ->get($this->table_name_entry)
                ->result_array();
            if(empty($lakala_detail)){
                return ['code' => false, 'message' => '流水表未绑定合同'];
            }
            if(count($audit_status_list) > 1 or current($audit_status_list) != BAOFOOPAYSTATUS_1){
                return ['code' => false, 'message' => '存在非待审核状态下的支付单'];
            }
            $requisition_number_list = is_array($lakala_detail) ? array_column($lakala_detail, 'requisition_number') : [];
            $pur_number_list         = is_array($lakala_detail) ? array_column($lakala_detail, 'pur_number') : [];
            //更新拉卡拉付款流水
            $data_lakala = [
                'audit_status'  => BAOFOOPAYSTATUS_3,
                'note'          => $trans_summary,
                'drawee'        => !empty(getActiveUserName()) ? getActiveUserName() : "system",
                'drawee_id'     => getActiveUserId(),
                'pay_time'      => date('Y-m-d H:i:s'),
                'drawee_time'   => date('Y-m-d H:i:s')
            ];
            $this->purchase_db->where_in('id', $ids)->update($this->table_name, $data_lakala);
            unset($data_lakala);
            //获取请款单号
            $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', ['pay_status' => PAY_WAITING_FINANCE_PAID, 'payment_notice' => '宝付审核驳回'.$trans_summary]);
            $this->load->model('compact/Compact_model');
            foreach($pur_number_list as $pur_number){
                $result = $this->Compact_model->change_compact_pay_status($pur_number, PAY_WAITING_FINANCE_PAID);
                if(!$result['code']){
                    throw new Exception($result['msg']);
                }
            }
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                return ['code' => false, 'message' => '驳回失败'];
            }else{
                $this->purchase_db->trans_commit();
                return ['code' => true, 'message' => '驳回成功'];
            }
        }catch(Exception $exc){
            return ['code' => false, 'message' => $exc->getMessage()];
        }
    }

    /**
     * 获取支付的数据
     * @param $ids
     * @return array
     */
    public function get_pay_info($ids){
        $result = $this->purchase_db
            ->select('*')
            ->from($this->table_name)
            ->where_in('id',$ids)
            ->get()
            ->result_array();

        if($result){// 组装数据
            foreach($result as &$value){
                $payInfo = $this->purchase_db->select('B.pay_type')
                    ->from($this->table_name_entry .' AS A')
                    ->join('pur_purchase_order_pay AS B','A.requisition_number=B.requisition_number','INNER')
                    ->where('A.cust_order_no',$value['cust_order_no'])
                    ->get()
                    ->row_array();

                $value['pay_type'] = $payInfo['pay_type'];
            }
        }


        return $result;
    }

    /**
     * 更新支付状态
     */
    public function update_pay_audit_status($cust_order_no,$data){
        try {
            $this->purchase_db->trans_begin();

            $finishTime = date('Y-m-d H:i:s');

            $update_arr = [
                'audit_status'  => $data['audit_status'],
                'submit_date'   => $data['submit_date'],
                'note'          => $data['note'],
                'lakala_state'  => 2,
                'pay_time'      => $finishTime,
                'file_batch_no' => isset($data['file_batch_no'])?$data['file_batch_no']:'',
                'drawee'        => getActiveUserName(),
                'drawee_id'     => getActiveUserId(),
                'drawee_time'   => $data['drawee_time'],
            ];
            //拉卡拉境内批量代付交易表
            $this->purchase_db->where('cust_order_no', $cust_order_no)->update('pay_lakala', $update_arr);

            //获取绑定的请款单号
            $lakala_main = $this->purchase_db
                ->select('applicant,applicant_id,pay_time,file_batch_no')
                ->where('cust_order_no', $cust_order_no)
                ->get('pur_pay_lakala')
                ->row_array();
            $lakala_detail = $this->purchase_db
                ->select('requisition_number')
                ->where('cust_order_no', $cust_order_no)
                ->get('pay_lakala_detail')
                ->result_array();
            if(empty($lakala_detail)) {
                throw new Exception('支付平台流水未绑定请款单号');
            }

            $fileBatchNo                = $lakala_main['file_batch_no'];
            $requisition_number_list    = is_array($lakala_detail) ? array_column($lakala_detail, 'requisition_number') : [];
            $pur_number_list            = is_array($lakala_detail) ? array_column($lakala_detail, 'pur_number') : [];
            $requisition_number_list    = array_unique($requisition_number_list);
            $pur_number_list            = array_unique($pur_number_list);

            if($data['audit_status'] == 2){
                //审核通过，只更新请款记录
                $data_pay = [
                    'pur_tran_num'  => $cust_order_no,
                    'trans_orderid' => $fileBatchNo,
                    'payer_id'      => $lakala_main['applicant_id'],
                    'payer_name'    => $lakala_main['applicant'],
                    'payer_time'    => $finishTime
                ];
                $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', $data_pay);
            }else{
                //审核不通过，更新 付款表、合同单、采购表的支付状态
                $data_pay = [
                    'pay_status' => PAY_WAITING_FINANCE_PAID,
                    'payment_notice' => '支付平台审核失败'
                ];
                $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', $data_pay);
                $this->load->model('compact/Compact_model');
                foreach ($pur_number_list as $pur_number) {
                    $result = $this->Compact_model->change_compact_pay_status($pur_number, PAY_WAITING_FINANCE_PAID);
                    if (!$result['code']) {
                        throw new Exception($result['msg']);
                    }
                }
            }
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                return ['code' => false, 'message' => '支付平台审核事务提交失败'];
            }else{
                $this->purchase_db->trans_commit();
                return ['code' => true, 'message' => '支付平台审核事务提交成功'];
            }
        }catch (Exception $exception){
            $this->purchase_db->trans_rollback();
            return ['code' => false, 'message' => '支付平台审核数据更新失败'];
        }
    }

    /**
     *
     * @param $get
     * @param $offsets
     * @param $limit
     * @param $page
     * @return array
     */
    public function LaKaLa_export_sum($get,$limit=1,$page=1){
        $query = $this->purchase_db;
        $query->select("pay_time,id,cust_order_no pur_tran_num,acc_type,acc_no,acc_name,file_batch_no,phone_no,supplier_code,audit_status,amount,cert_no,bank_code,bank_name,cnaps_code,summary,payer_acc_name,payer_acc_no,remark,applicant_time,drawee,applicant,voucherId,note")
            ->from($this->table_name);
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
            $pur_tran_num_list = $query->query("SELECT pur_tran_num FROM pur_pay_lakala_detail WHERE pur_number in (".$pur_number_list.")")->result_array();
            $pur_tran_num_arr  = !empty($pur_tran_num_list) ? array_column($pur_tran_num_list, 'pur_tran_num') : [PURCHASE_NUMBER_ZFSTATUS];
            $query->where_in('pur_tran_num', $pur_tran_num_arr);
        }
        if (isset($get['pur_tran_num']) && trim($get['pur_tran_num'])) {
            $pur_tran_num = explode(' ', trim($get['cust_order_no']));
            $query->where_in('cust_order_no', array_filter($pur_tran_num));
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
        if(isset($get['platform_type']) and $get['platform_type']){// 付款时间-结束
            $query->where('platform_type', $get['platform_type']);
        }
        //   对 1000
        //
        $query->limit($limit);
        $count_row = $query->select('count(id) as num')->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        $return_data = [
            'paging_data'=>[
                'total' => $total_count,
                'offset'=>$page,
                'limit'=>$limit,
                'pages'=> ceil($total_count/$limit),
            ]
        ];
        return $return_data;
    }

    /**
     * 拉卡拉数据量列表导出
     */
    public function LaKaLa_export($get,$offsets, $limit,$page){

        $this->purchase_db->select("id,cust_order_no pur_tran_num,acc_type,acc_no,acc_name,phone_no,supplier_code,audit_status,amount,file_batch_no,cert_no,bank_code,bank_name,cnaps_code,summary,payer_acc_name,payer_acc_no,remark,applicant_time,drawee,drawee_time,applicant,voucherId,pay_time,note");
        if(isset($get['ids']) && $get['ids']){
            $ids = explode(',', trim($get['ids']));
            $this->purchase_db->where_in('id', $ids);
        }
        if(isset($get['pur_number']) && $get['pur_number']){ //合同号
            $pur_number        = explode(' ', trim($get['pur_number']));
            $pur_number_list   = array_map(function($array){
                return sprintf("'%s'", $array);
            }, $pur_number);
            $pur_number_list   = array_unique($pur_number_list);
            $pur_number_list   = implode(',', $pur_number_list);
            $pur_tran_num_list = $this->purchase_db->query("SELECT pur_tran_num FROM pur_pay_lakala_detail WHERE pur_number in (".$pur_number_list.")")->result_array();
            $pur_tran_num_arr  = !empty($pur_tran_num_list) ? array_column($pur_tran_num_list, 'pur_tran_num') : [PURCHASE_NUMBER_ZFSTATUS];
            $this->purchase_db->where_in('pur_tran_num', $pur_tran_num_arr);
        }
        if (isset($get['pur_tran_num']) && trim($get['pur_tran_num'])) {
            $pur_tran_num = explode(' ', trim($get['cust_order_no']));
            $this->purchase_db->where_in('cust_order_no', array_filter($pur_tran_num));
        }
        if (isset($get['trans_orderid']) && trim($get['trans_orderid'])) {
            $trans_orderid = explode(' ', trim($get['trans_orderid']));
            $this->purchase_db->where_in('trans_orderid', array_filter($trans_orderid));
        }

        if(isset($get['supplier_code']) && $get['supplier_code']){
            $this->purchase_db->where_in('supplier_code', $get['supplier_code']);
        }

        if(isset($get['audit_status']) && $get['audit_status']){
            $this->purchase_db->where_in('audit_status', $get['audit_status']);
        }
        if(isset($get['create_time_start']) and $get['create_time_start']){// 申请时间-开始
            $this->purchase_db->where('applicant_time>=', $get['create_time_start']);
        }

        if(isset($get['create_time_end']) and $get['create_time_end']){// 申请时间-结束
            $this->purchase_db->where('applicant_time<=', $get['create_time_end']);
        }

        if(isset($get['payer_time_start']) and $get['payer_time_start']){// 付款时间-开始
            $this->purchase_db->where('pay_time>=', $get['payer_time_start']);
        }
        if(isset($get['payer_time_end']) and $get['payer_time_end']){// 付款时间-结束
            $this->purchase_db->where('pay_time<=', $get['payer_time_end']);
        }
        if(isset($get['platform_type']) and $get['platform_type']){// 付款时间-结束
            $this->purchase_db->where('platform_type', $get['platform_type']);
        }
        $this->purchase_db->limit($limit,$offsets);
        $data = $this->purchase_db->get($this->table_name)->result_array();

        $pur_tran_num       = is_array($data) ? array_column($data, 'pur_tran_num') : [];
        $supplier_code_list = is_array($data) ? array_column($data, 'supplier_code') : [];
        $pur_number_list    = $this->get_lakala_pur_number($pur_tran_num);
        $supplier_list      = $this->Payment_order_pay_model->get_supplier_name($supplier_code_list);
        foreach($data as $key => $vv){
            $data[$key]['pur_number']    = isset($pur_number_list[$vv['pur_tran_num']]) ? $pur_number_list[$vv['pur_tran_num']] : '';
            $data[$key]['supplier_name'] = isset($supplier_list[$vv['supplier_code']]) ? $supplier_list[$vv['supplier_code']] : '';
            $data[$key]['audit_status']  = getBaofoostatus($vv['audit_status']);
        }
        $return_data = [
            'values'=> $data
        ];
        return $return_data;
    }

    /**
     * 获取查询的数据
     */
    public function getSelectInfo($params){
        $this->purchase_db->select('cust_order_no,file_batch_no,submit_date')
            ->from($this->table_name);
        foreach($params as $key => $value){
            if(is_array($value)){
                $this->purchase_db->where_in($key,$value);
            }else{
                $this->purchase_db->where($key,$value);
            }
        }
        $result = $this->purchase_db->get()->result_array();
        return $result;
    }

    /***
     * 更新拉卡拉支付成功
     */
    public function update_lakala_pay_success($cust_order_no,$fileBatchNo,$finishTime){
        try{
            $this->purchase_db->trans_begin();
            //拉卡拉境内批量代付交易表
            $this->purchase_db->where('cust_order_no', $cust_order_no)->update('pay_lakala',['lakala_state'=>1,'audit_status'=>4,'pay_time' => $finishTime]);
            //获取绑定的请款单号
            $lakala_main = $this->purchase_db
                    ->select('applicant,applicant_id,pay_time')
                ->where('cust_order_no', $cust_order_no)
                ->get('pur_pay_lakala')
                ->row_array();
            $lakala_detail= $this->purchase_db
                ->select('requisition_number')
                ->where('cust_order_no', $cust_order_no)
                ->get('pay_lakala_detail')
                ->result_array();
            $requisition_number_list = is_array($lakala_detail) ? array_column($lakala_detail, 'requisition_number') : [];
            if(empty($requisition_number_list)){
                throw new Exception('支付平台流水未绑定请款单号');
            }
            $requisition_number_list = array_unique($requisition_number_list);
            //更新 付款表 采购表的支付状态
            $this->purchase_order_pay_model->update_order_pay_compact($requisition_number_list,$fileBatchNo,$finishTime);
            //更新请款记录
            $data_pay = [
                'pur_tran_num'  => $cust_order_no,
                'trans_orderid' => $fileBatchNo,
                'payer_id'      => $lakala_main['applicant_id'],
                'payer_name'    => $lakala_main['applicant'],
                'payer_time'    => $finishTime
            ];
            $this->purchase_db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', $data_pay);
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                return ['code' => false, 'message' => '支付平台支付成功事务提交失败'];
            }else{
                $this->purchase_db->trans_commit();
                return ['code' => true, 'message' => '支付平台支付成功事务提交成功'];
            }
        }catch (Exception $exception){
            $this->purchase_db->trans_rollback();
            return ['code' => false, 'message' => '支付平台支付成功事务提交失败'];
        }
    }

    /**
     * 更新拉卡拉支付失败
     */
    public function update_lakala_pay_fail($cust_order_no,$fileBatchNo,$lakala_status){
        try {
            $this->purchase_db->trans_begin();
            //拉卡拉境内批量代付交易表
            $this->purchase_db->where('cust_order_no', $cust_order_no)->update('pay_lakala', ['lakala_state' => $lakala_status, 'audit_status' => 5]);
            //获取绑定的请款单号
            $lakala_main = $this->purchase_db
                ->select('applicant,applicant_id,pay_time')
                ->where('cust_order_no', $cust_order_no)
                ->get('pur_pay_lakala')
                ->row_array();
            $lakala_detail = $this->purchase_db
                ->select('pur_number,requisition_number')
                ->where('cust_order_no', $cust_order_no)
                ->get('pay_lakala_detail')
                ->result_array();
            if (empty($lakala_detail)) {
                throw new Exception('支付平台流水未绑定请款单号');
            }
            $requisition_number_list = is_array($lakala_detail) ? array_column($lakala_detail, 'requisition_number') : [];
            $pur_number_list = is_array($lakala_detail) ? array_column($lakala_detail, 'pur_number') : [];
            $requisition_number_list = array_unique($requisition_number_list);
            $pur_number_list = array_unique($pur_number_list);
            //更新 付款表 采购表的支付状态
            $this->purchase_db->where_in('requisition_number', $requisition_number_list)
                ->update('purchase_order_pay', ['pay_status' => PAY_WAITING_FINANCE_PAID, 'payment_notice' => '支付平台审核失败']);
            $this->load->model('compact/Compact_model');
            foreach($pur_number_list as $pur_number){
                $result = $this->Compact_model->change_compact_pay_status($pur_number, PAY_WAITING_FINANCE_PAID);
                if(!$result['code']){
                    throw new Exception($result['msg']);
                }
            }
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();
                return ['code' => false, 'message' => '支付平台支付失败事务提交失败'];
            }else{
                $this->purchase_db->trans_commit();
                return ['code' => true, 'message' => '支付平台支付失败事务提交成功'];
            }
        }catch (Exception $exception){
            $this->purchase_db->trans_rollback();
            return ['code' => false, 'message' => '支付平台支付失败事务提交失败'];
        }


    }

    /**
     * 批量代付电子回单文件下载接口
     * @param $file_batch_no
     * @return array
     */
    public function downloadReceipt($file_batch_no){
        if($file_batch_no){
            $list_order = $this->getSelectInfo(['file_batch_no' => $file_batch_no,'platform_type' => 'lakala']);
        }else{
            $list_order  = $this->getSelectInfo(['lakala_state' => [0,1,3,4,6,7],'voucherId' => '','platform_type' => 'lakala']);
        }

        if($list_order){
            $this->load->library('Lakala');
            $this->load->library('Filedirdeal');
            $this->load->library('Upload_image');

            $lakala = new Lakala();
            $update_result = [];
            foreach ($list_order as $val) {
                try{
                    $cust_order_no = $val['cust_order_no'];
                    $requisition_number_list = $this->purchase_db->select('requisition_number')
                        ->where('cust_order_no', $cust_order_no)
                        ->get('pay_lakala_detail')
                        ->result_array();
                    $requisition_number_list = array_column($requisition_number_list,'requisition_number');
                    if(empty($requisition_number_list)){
                        throw new Exception('获取请款单失败');
                    }
                    $result = $lakala->queryDownloadReceipt($val['file_batch_no'],$val['cust_order_no'],$val['submit_date']);

                    if(isset($result['downloadFile'])){
                        $downloadFile = $result['downloadFile'];
                        $filePath = get_export_path('lakala').$val['file_batch_no'].'.pdf';
                        file_put_contents($filePath,$downloadFile);

                        if(!file_exists($filePath)) throw new Exception('拉卡拉回执单文件流保存失败');

                        $java_result = $this->upload_image->doUploadFastDfs('image', $filePath);

                        if($java_result['code'] == 200){
                            $update_arr = [
                                'voucherId' => 1,
                                'java_voucher_address' => $java_result['data']
                            ];
                            $this->purchase_db->where('cust_order_no',$cust_order_no)->update($this->table_name,$update_arr);

                            // 请款单-回执地址
                            $this->purchase_db->where_in('requisition_number',$requisition_number_list)->update('purchase_order_pay',['voucher_address' => $java_result['data']]);
                        }else{
                            throw new Exception('拉卡拉回执单文件上传JAVA服务器失败');
                        }

                        $update_result[$cust_order_no] = '回单拉取并更新成功';
                    }else{
                        $errorMsg = isset($result['message'])?$result['message']:'查询无结果!';
                        throw new Exception($errorMsg);
                    }
                }catch(Exception $exception){
                    $update_result[$cust_order_no] = $exception->getMessage();
                }
            }

            return $update_result;
        }else{
            return [];
        }
    }

    /**
     * 刷新交易状态
     * @param $cust_order_no
     * @return string
     * @throws Exception
     */
    public function refresh_pay_status($cust_order_no){
        $payOrderInfo = $this->purchase_db->where('cust_order_no',$cust_order_no)
            ->get($this->table_name)
            ->row_array();
        if(empty($payOrderInfo)){
            return '支付平台请款单不存在';
        }
        if($payOrderInfo['audit_status'] != 2){
            return '该状态无需刷新';
        }

        # lakala_status:拉卡拉审核状态：1: "待审核"，2: "审核通过"，3: "审核不通过"，4: "收款成功"，5: "收款失败"

        $cebBankApi = new CebBank();
        $result_resp = [];
        $result_resp['flag']     = '500';// 200.收款成功,300.收款失败，500.请求失败（查询请求未发送成功）
        $result_resp['errorMsg'] = '响应结果未设置';
        switch ($payOrderInfo['platform_type']){
            case 'cebbank':
                $result = $cebBankApi->single_order_b2e004003($payOrderInfo['cust_order_no'],$payOrderInfo['order_number_1']);
                if(empty($result['code'])){// 请求未发送成功
                    $result_resp['flag'] = '500';
                    $result_resp['errorMsg'] = $result['errorMsg'];
                }else{
                    $response = $cebBankApi->curlPostXml($result['data']['url'],$result['data']['RequestXmlStr']);
                    if(isset($response['TransContent']['ReturnCode']) and $response['TransContent']['ReturnCode'] == '0000'){
                        $result_resp['flag'] = '200';// 收款成功
                        $finishTime = $response['TransHead']['JnlTime'];
                        $finishTime = date('Y-m-d H:i:s',strtotime($finishTime));
                    }elseif(isset($response['TransContent']['ReturnMsg'])){
                        $result_resp['flag'] = '300';// 收款失败
                        $result_resp['errorMsg'] = $response['TransContent']['ReturnMsg'];
                    }else{
                        $result_resp['flag'] = '500';
                        $result_resp['errorMsg'] = '数据请求发送光大银行响应失败';
                    }
                }
                break;

            default :// 操作类型有误
                $result_resp['flag'] = '500';
                $result_resp['errorMsg'] = '操作类型有误';
                break;
        }

        if($result_resp['flag'] === '200'){// 1.批次处理完成 >>> 收款成功
            $result = $this->update_lakala_pay_success($payOrderInfo['cust_order_no'],$payOrderInfo['file_batch_no'],$finishTime);
            if($result['code']){
                $result_message = true;
            }else{
                $result_message = $result['message'];
            }
        }elseif($result_resp['flag'] === '300'){// 批次处理失败 >>> 收款失败
            $result = $this->update_lakala_pay_fail($payOrderInfo['cust_order_no'],$payOrderInfo['file_batch_no'],5);// $lakala_status=5 收款失败
            if($result['code']){
                $result_message = true;
            }else{
                $result_message = $result['message'];
            }
        }elseif($result_resp['flag'] === '500'){// 请求未发送成功的 不改变系统单据状态
            $result_message = $result_resp['errorMsg'];

        }else{
            $result_message = '未知的请求错误提示信息';
        }

        return $result_message;
    }
}