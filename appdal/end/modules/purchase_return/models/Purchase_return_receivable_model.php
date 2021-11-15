<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020/3/14
 * Time: 14:26
 */

class Purchase_return_receivable_model extends Purchase_model {

    public function __construct(){
        parent::__construct();
        $this->table_name= 'return_after_storage_collection';
        $this->table_part = 'return_after_storage_part';
        $this->table_collection = 'return_after_storage_collection';
        $this->table_file ='return_refund_file';
        $this->table_express ='return_express_info';
        $this->table_main = 'return_after_storage_main';
        $this->load->model('Purchase_return_tracking_model','return_tracking');
    }

    /**
     * @param $params
     * @param $offset
     * @param $limit
     * @param int $page
     * @return array
     */
    public function get_return_receivable_list($params,$offset,$limit,$page=1){
        $query= $this->purchase_db;
        $query->select('c.return_number,c.supplier_code,c.supplier_name,c.refund_product_cost,c.freight_payment_type,
        c.return_status,c.act_refund_amount,c.is_confirm_receipt,c.is_confirm_time,c.wms_shipping_time,c.upload_screenshot_time,
        c.colletion_user_name,c.colletion_time,c.act_freight,c.refundable_amount,c.colletion_remark');
        $query->from($this->table_name.' as c');
        $query->where_in('c.return_status',[9,10,11]);

        //退货单号
        if(isset($params['return_number']) and !empty($params['return_number'])){
            $return_number = explode(' ', trim($params['return_number']));
            $query->where_in('c.return_number', array_filter($return_number));
        }

        //退货状态
        if(isset($params['return_status']) and !empty($params['return_status'])){
            $query->where('c.return_status',$params['return_status']);
        }

        //供应商编码
        if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
            $query->where('c.supplier_code',$params['supplier_code']);
        }

        //截图开始时间  upload_time_start  upload_time_end
        if(isset($params['upload_time_start']) and isset($params['upload_time_end']) and  !empty($params['upload_time_start'])){
            $start_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_end']));
            $query->where("c.upload_screenshot_time between '{$start_time}' and '{$end_time}' ");
        }
        //收款时间
        if(isset($params['colletion_time_start']) and isset($params['colletion_time_end']) and  !empty($params['colletion_time_start']) and  !empty($params['colletion_time_end'])){

            $query->where("c.colletion_time between '{$params['colletion_time_start']}' and '{$params['colletion_time_end']}' ");
        }

        //实际退款时间
        if(isset($params['refund_time_start']) and isset($params['refund_time_end']) and  !empty($params['refund_time_start']) and  !empty($params['refund_time_end'])){

            $query->where("c.return_number in (select distinct return_number from  pur_return_refund_flow_info WHERE refund_time between '{$params['refund_time_start']}' and '{$params['refund_time_end']}') ");
        }


        if(isset($params['freight_payment_type']) and !empty($params['upload_time_start'])){
            $query->where("c.freight_payment_type",$params['freight_payment_type']);
        }
        //退款情况
        if(isset($params['refund_status']) and !empty($params['refund_status'])){
            if($params['refund_status'] ==1){
                $query->where("c.act_refund_amount>c.refundable_amount");
            }
            if($params['refund_status'] ==2){
                $query->where("c.act_refund_amount<c.refundable_amount");
            }
            if($params['refund_status'] ==3){
                $query->where("c.act_refund_amount=c.refundable_amount");
            }
        }
        //提交人
        if(isset($params['upload_screenshot_user']) and !empty($params['upload_screenshot_user'])){
            $query->where("c.upload_screenshot_user",$params['upload_screenshot_user']);
        }

        //收款人
        if(isset($params['colletion_user']) and !empty($params['colletion_user'])){
            $query->where("c.colletion_user",$params['colletion_user']);
        }
        //退款流水号
        if(isset($params['refund_number']) and !empty($params['refund_number'])){
            $refund_number = $params['refund_number'];
            $refund_list = $query->query("SELECT return_number FROM pur_return_refund_flow_info WHERE refund_serial_number='{$refund_number}'")->result_array();
            if(!empty($refund_list)){
                $return_number = array_column($refund_list,'return_number');
                $query->where_in('c.return_number',$return_number);
            }else{
                $query->where('c.return_number',$refund_number);
            }
        }


        $clone_db          = clone($query);
        // 根据 return_number 维度计算记录个数
        $count_sql = $clone_db->select('count(c.return_number) as num')->group_by('c.return_number')->get_compiled_select();
        $count_row = $clone_db->query("SELECT count(cc.return_number) as num FROM ($count_sql) AS cc")->row_array();
        $total = isset($count_row['num']) ? (int) $count_row['num'] : 0;
        $data = $query->group_by('c.return_number')->limit($limit, $offset)->get()->result_array();


        if(!empty($data)){
            // 获取采购主体
            $agent = $this->purchase_db->from('param_sets')
                ->select('pValue')
                ->where('pKey = ', 'PUR_PURCHASE_AGENT')
                ->get()->row_array();
            $agent_list = [];
            $agent_def = 'YIBAI TECHNOLOGY LTD';
            if($agent && isset($agent['pValue'])){
                $agent_list = json_decode($agent['pValue'], true);
            }
            foreach ($data as $key => $val){
                $refund_serial_number = $this->return_tracking->get_refund_number($val['return_number']);
                if(empty($refund_serial_number)) $refund_serial_number ='';
                $freight_payment_type = '';
                $data[$key]['return_status'] = getReturnStatus($val['return_status']);
                $data[$key]['is_confirm_receipt'] = getReturnIsConfirmReceipt($val['is_confirm_receipt']);
                $main_data_list = $this->return_tracking->get_main_number($val['return_number']);
                $data[$key]['main_number'] = isset($main_data_list['main_number'])?$main_data_list['main_number']:'';
                $proposer_info = $this->get_proposer_info($val['return_number']);

                $proposer = $return_reason ='';
                if(!empty($proposer_info)){
                    //b.proposer,b.return_reason
                    $proposer = isset($proposer_info['proposer'])?$proposer_info['proposer']:'';
                    $return_reason = isset($proposer_info['return_reason'])?$proposer_info['return_reason']:'';
                    if(!empty($return_reason)){
                        $return_reason = getReturnSeason($return_reason);
                    }
                    $freight_payment_type = isset($proposer_info['freight_payment_type'])?$proposer_info['freight_payment_type']:'';
                    if(!empty($freight_payment_type)){
                        $freight_payment_type = getFreightPaymentType($freight_payment_type);
                    }
                }

                $data[$key]['freight_payment_type'] = $freight_payment_type;
                $data[$key]['refund_serial_number'] = $refund_serial_number;
                $data[$key]['proposer'] = $proposer;
                $data[$key]['return_reason'] = $return_reason;
                if($val['upload_screenshot_time'] =='0000-00-00 00:00:00'){
                    $data[$key]['upload_screenshot_time']  ='';
                }
                if($val['colletion_time'] =='0000-00-00 00:00:00'){
                    $data[$key]['colletion_time']  ='';
                }

                if($proposer_info && isset($proposer_info['sku'])){
                    // 获取最新入库记录对应的采购主体  pur_warehouse_results_main  purchase_number
                    $pur_name = $this->purchase_db->from('warehouse_results_main as m')
                        ->select('o.purchase_name')
                        ->join('purchase_order o', 'm.purchase_number=o.purchase_number', 'left')
                        ->where('m.sku = ', $proposer_info['sku'])
                        ->order_by('o.id','desc')
                        ->get()
                        ->row_array();
                    $data[$key]['purchase_name'] = $pur_name && $pur_name['purchase_name'] && in_array($pur_name['purchase_name'], array_keys($agent_list)) ? $agent_list[$pur_name['purchase_name']]:$agent_def;
                }else{
                    $data[$key]['purchase_name'] = $agent_def;
                }
                //差异金额
                $data[$key]['diff_amount'] = round($val['act_refund_amount']-$val['refundable_amount'],2);
            }
        }

        $this->load->model('user/purchase_user_model','purchase_user_model');
        $finance = $this->purchase_user_model->get_finance_list();
        $receivable_user= is_array($finance)?array_column($finance, 'name','id'):[];
        $user_list=$this->purchase_user_model->get_user_all_list();

        $return_data = [
            'data_list' => [
                'value'         => $data,
                'key'           => ['退货单号','供应商名称','退货产品成本','实际运费','应退款金额','实际退款金额','退款流水号','收款状态','申请信息','收款信息','提交截图时间'
                ],
                'drop_down_box' => [
                    'return_status'        => [
                        9=>getPurchaseReturnTrackingStatus(9),
                        10=>getPurchaseReturnTrackingStatus(10),
                        11=>getPurchaseReturnTrackingStatus(11),
                    ],
                    'is_confirm_receipt' => getReturnIsConfirmReceipt(),
                    'track_status' => getTrackStatus(),
                    'freight_payment_type' => getFreightPaymentType(),
                    'upload_screenshot_user' => $receivable_user,
                    'user_list' => $user_list,
                    'refund_status' =>array(
                        0=>'.',
                        1=>'超过',
                        2=>'小于',
                        3=>'等于',
                    )

                ]
            ],
            'page_data' => [
                'total'  => $count_row,
                'offset' => $page,
                'limit'  => $limit,
                'pages'  => ceil($total / $limit)
            ]
        ];
        return $return_data;
    }

    /**
     * 财务收款成功失败
     * @param $return_number
     */
    public function click_receivables($return_number,$colletion_remark,$type,$account_info){
        $return_status = RETURN_STATUS_FINANCIAL_REJECT;//默认收款失败
        if($type==1){
            $return_status = RETURN_STATUS_FINANCIAL_RECEIVED;
        }
        $this->purchase_db->trans_begin();
        try{
            //收款成功 入库后退货_应收款
            $update_data['return_status'] = $return_status;
            $update_data['colletion_remark'] = $colletion_remark;
            $update_data['colletion_user'] = getActiveUserId();
            $update_data['colletion_time'] = date('Y-m-d H:i:s');
            $update_data['colletion_user_name'] = getActiveUserName();
            if ($type == 1) {
                $update_data['our_branch_short'] = $account_info['our_branch_short'];
                $update_data['our_branch'] = $account_info['our_branch'];
                $update_data['our_account'] = $account_info['our_account'];
                $update_data['our_account_holder'] = $account_info['our_account_holder'];

            }
            $false = $this->purchase_db->update($this->table_name, $update_data, ['return_number' => $return_number]);
            //收款成功 入库后退货子表
            if($false){
                unset($update_data);
                $update_data['return_status'] = $return_status;
                $false = $this->purchase_db->update($this->table_part,$update_data,['return_number'=>$return_number]);
            }
            if($false){
                $this->purchase_db->trans_commit();
                $success=true;
                $message='操作成功!';
            }else{
                $this->purchase_db->trans_rollback();
                $success=false;
                $message='操作失败!';
            }
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $message=$e->getMessage();
        }
        return [
            'success'=>$success,
            'message'=>$message
        ];
    }


    /**
     * 获取导出的总条数
     */
    public function export_sum($params){
        $query= $this->purchase_db;
        $query->select('c.return_number,c.supplier_code,c.supplier_name,c.refund_product_cost,c.freight_payment_type,c.return_status,c.act_refund_amount,c.is_confirm_receipt,c.is_confirm_time,c.wms_shipping_time,c.upload_screenshot_time,c.colletion_user_name,c.colletion_time,c.act_freight,c.refundable_amount,c.upload_screenshot_user_name,c.colletion_remark');
        $query->from($this->table_name.' as c');
        $query->where_in('c.return_status',[9,10,11]);
        //退货单号
        if(isset($params['return_number']) and !empty($params['return_number'])){
            $return_number = explode(' ', trim($params['return_number']));
            $query->where_in('c.return_number', array_filter($return_number));
        }
        //退货状态
        if(isset($params['return_status']) and !empty($params['return_status'])){
            $query->where('c.return_status',$params['return_status']);
        }
        //供应商编码
        if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
            $query->where('c.supplier_code',$params['supplier_code']);
        }
        //截图开始时间  upload_time_start  upload_time_end
        if(isset($params['upload_time_start']) and isset($params['upload_time_end']) and  !empty($params['upload_time_start'])){
            $start_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_end']));
            $query->where("c.upload_screenshot_time between '{$start_time}' and '{$end_time}' ");
        }
        if(isset($params['freight_payment_type']) and !empty($params['freight_payment_type'])){
            $query->where("c.freight_payment_type",$params['freight_payment_type']);
        }
        //退款情况
        if(isset($params['refund_status']) and !empty($params['refund_status'])){
            if($params['refund_status'] ==1){
                $query->where("c.act_refund_amount>c.refundable_amount");
            }
            if($params['refund_status'] ==2){
                $query->where("c.act_refund_amount<c.refundable_amount");
            }
            if($params['refund_status'] ==3){
                $query->where("c.act_refund_amount=c.refundable_amount");
            }
        }

        //提交人
        if(isset($params['upload_screenshot_user']) and !empty($params['upload_screenshot_user'])){
            $query->where("c.upload_screenshot_user",$params['upload_screenshot_user']);
        }

        //收款人
        if(isset($params['colletion_user']) and !empty($params['colletion_user'])){
            $query->where("c.colletion_user",$params['colletion_user']);
        }


        //退款流水号
        if(isset($params['refund_number']) and !empty($params['refund_number'])){
            $refund_number = $params['refund_number'];
            $refund_list = $query->query("SELECT return_number FROM pur_return_refund_flow_info WHERE refund_serial_number='{$refund_number}'")->result_array();
            if(!empty($refund_list)){
                $return_number = array_column($refund_list,'return_number');
                $query->where_in('c.return_number',$return_number);
            }else{
                $query->where('c.return_number',$refund_number);
            }
        }

        $count_row = $query->select('count(c.return_number) as num')->get()->row_array();
        $total = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        return $total;
//
//        $clone_db          = clone($query);
//        // 根据 return_number 维度计算记录个数
//        $count_sql = $clone_db->select('count(c.return_number) as num')->group_by('c.return_number')->get_compiled_select();
//        $count_row = $clone_db->query("SELECT count(cc.return_number) as num FROM ($count_sql) AS cc")->row_array();
//        $total = isset($count_row['num']) ? (int) $count_row['num'] : 0;
//        return $total;
    }


    public function export_receivables_list($params,$offset,$limit,$page=1){
        $this->load->model('Purchase_return_tracking_model','purchase_return');
        $query= $this->purchase_db;
        $query->select('c.return_number,c.supplier_code,c.supplier_name,c.refund_product_cost,c.freight_payment_type,c.return_status,c.act_refund_amount,c.is_confirm_receipt,c.is_confirm_time,c.wms_shipping_time,c.upload_screenshot_time,c.colletion_user_name,c.colletion_time,c.act_freight,c.refundable_amount,c.upload_screenshot_user_name,c.colletion_remark');
        $query->from('return_after_storage_collection as c');
        $query->where_in('c.return_status',[9,10,11]);
        //退货单号
        if(isset($params['return_number']) and !empty($params['return_number'])){
            $return_number = explode(' ', trim($params['return_number']));
            $query->where_in('c.return_number', array_filter($return_number));
        }
        //退货状态
        if(isset($params['return_status']) and !empty($params['return_status'])){
            $query->where('c.return_status',$params['return_status']);
        }

        //供应商编码
        if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
            $query->where('c.supplier_code',$params['supplier_code']);
        }
        //截图开始时间  upload_time_start  upload_time_end
        if(isset($params['upload_time_start']) and isset($params['upload_time_end']) and  !empty($params['upload_time_start'])){
            $start_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_end']));
            $query->where("c.upload_screenshot_time between '{$start_time}' and '{$end_time}' ");
        }

        if(isset($params['freight_payment_type']) and !empty($params['freight_payment_type'])){
            $query->where("c.freight_payment_type",$params['freight_payment_type']);
        }
        //退款情况
        if(isset($params['refund_status']) and !empty($params['refund_status'])){
            if($params['refund_status'] ==1){
                $query->where("c.act_refund_amount>c.refundable_amount");
            }
            if($params['refund_status'] ==2){
                $query->where("c.act_refund_amount<c.refundable_amount");
            }
            if($params['refund_status'] ==3){
                $query->where("c.act_refund_amount=c.refundable_amount");
            }
        }

        //提交人
        if(isset($params['upload_screenshot_user']) and !empty($params['upload_screenshot_user'])){
            $query->where("c.upload_screenshot_user",$params['upload_screenshot_user']);
        }

        //收款人
        if(isset($params['colletion_user']) and !empty($params['colletion_user'])){
            $query->where("c.colletion_user",$params['colletion_user']);
        }

        //退款流水号
        if(isset($params['refund_number']) and !empty($params['refund_number'])){
            $refund_number = $params['refund_number'];
            $refund_list = $query->query("SELECT return_number FROM pur_return_refund_flow_info WHERE refund_serial_number='{$refund_number}'")->result_array();
            if(!empty($refund_list)){
                $return_number = array_column($refund_list,'return_number');
                $query->where_in('c.return_number',$return_number);
            }else{
                $query->where('c.return_number',$refund_number);
            }
        }


        // 根据 return_number 维度计算记录个数
        $data = $query->group_by('c.return_number')->limit($limit, $offset)->get()->result_array();
        if(!empty($data)){
            foreach ($data as $key => $val){
                $refund_serial_number = $this->return_tracking->get_refund_number($val['return_number']);
                if(empty($refund_serial_number)) $refund_serial_number ='';
                $freight_payment_type = '';
                $data[$key]['return_status'] = getReturnStatus($val['return_status']);
                $data[$key]['is_confirm_receipt'] = getReturnIsConfirmReceipt($val['is_confirm_receipt']);
                $main_data_list = $this->return_tracking->get_main_number($val['return_number']);
                $data[$key]['main_number'] = isset($main_data_list['main_number'])?$main_data_list['main_number']:'';
                $proposer_info = $this->get_proposer_info($val['return_number']);

                $proposer = $return_reason ='';
                if(!empty($proposer_info)){
                    //b.proposer,b.return_reason
                    $proposer = isset($proposer_info['proposer'])?$proposer_info['proposer']:'';
                    $return_reason = isset($proposer_info['return_reason'])?$proposer_info['return_reason']:'';
                    if(!empty($return_reason)){
                        $return_reason = getReturnSeason($return_reason);
                    }
                    $freight_payment_type = isset($proposer_info['freight_payment_type'])?$proposer_info['freight_payment_type']:'';
                    if(!empty($freight_payment_type)){
                        $freight_payment_type = getFreightPaymentType($freight_payment_type);
                    }
                }

                $data[$key]['freight_payment_type'] = $freight_payment_type;
                $data[$key]['refund_serial_number'] = $refund_serial_number;
                $data[$key]['proposer'] = $proposer;
                $data[$key]['return_reason'] = $return_reason;
                if($val['upload_screenshot_time'] =='0000-00-00 00:00:00'){
                    $data[$key]['upload_screenshot_time']  ='';
                }
                if($val['colletion_time'] =='0000-00-00 00:00:00'){
                    $data[$key]['colletion_time']  ='';
                }
                //实际退款时间，如果有都记录下来
                $refund_list = array_column($this->purchase_return->get_refund_flow_info($return_number),'refund_time');
                $data[$key]['refund_time_list'] = !empty($refund_list)?implode(',',$refund_list):'';
                //差异金额
                $data[$key]['diff_amount'] = round($val['act_refund_amount']-$val['refundable_amount'],2);
            }
        }
        $return_data = [
            'value' => $data
        ];
        return $return_data;
    }

    /**
     * 获取申请人信息
     */
    public function get_proposer_info($return_number){
        $proposer_info = $this->purchase_db->select("b.proposer,b.return_reason,a.freight_payment_type,b.sku")
            ->from($this->table_part.' as a')
            ->join($this->table_main.' as b',' a.main_number=b.main_number')
            ->where('a.return_number',$return_number)
            ->get()->row_array();
        return $proposer_info;
    }

    //修改收款账号
    public function modify_receiving_account($params)
    {
        $return = [false,''];
        try{
            //查询出给退货信息
            $pay_info = $this->purchase_db->select('*')->from($this->table_name)->where('return_number',$params['return_number'])->get()->row_array();

            //检验是否提交账号简称
            if(empty($pay_info)) throw new Exception('应收款信息为空');
            if (empty($params['our_branch_short'])) throw new Exception('支行简称为空');

            if (($pay_info['our_branch_short']==$params['our_branch_short']) &&($pay_info['colletion_remark'] == $params['colletion_remark'] ) ) {
                throw new Exception('收款信息未修改，不允许提交');
            }
            if (($pay_info['return_status']!= 11 ) ) {
                throw new Exception('只有已收款状态下才可修改收款账号');
            }

            $update_data = $params;
            unset($update_data['return_number']);
            unset($update_data['uid']);
            $flag = $this->purchase_db->update($this->table_name, $update_data, ['return_number' => $params['return_number']]);
            if (!$flag) {
                throw new Exception('数据库更新失败');
            }
            $return[0] = true;

        }catch(Exception $e){
            $return[1] = $e->getMessage();

        }
        return $return;

    }

}