<?php
/**
 * 报损信息模型类
 * User: Jaxton
 * Date: 2019/01/17 10:06
 */

class Report_loss_model extends Purchase_model {
	protected $table_name = 'purchase_order_reportloss';//报损表

	private $success=false;
	private $error_msg='';
	private $success_msg='';

    public function __construct(){
        parent::__construct();
        $this->load->helper(['user','abnormal']);
        $this->load->model('user/User_group_model');

        $this->load->model('Message_model');
    }

    /**
    * 获取一条数据
    * @param $id
    * @return array   
    * @author Jaxton 2019/01/17
    */
    public function get_one_report_loss($id){
    	$row=$this->purchase_db->select('*')
    	->from($this->table_name)
    	->where('id',$id)
    	->get()->row_array();
    	if($row){
    		return $row;
    	}else{
    		return false;
    	}
    }

    /**
    * 获取审核界面数据
    * @param $id
    * @return array   
    * @author Jaxton 2019/01/17
    */
    public function get_approval_page($id){
        $row=$this->purchase_db->select('a.*,b.product_img_url')
        ->from($this->table_name.' a')
        ->join('purchase_order_items b','a.sku=b.sku and a.pur_number=b.purchase_number','left')
        ->where('a.id',$id)
        ->get()->row_array();

        //查出一个po中的所有备货单号
        if(!empty($row)){
            $list=$this->purchase_db->select(
                'a.purchase_number,
                a.sku,
                a.demand_number,
                b.product_name,
                b.confirm_amount,
                d.supplier_code,
                d.supplier_name,
                b.purchase_unit_price as price,
                c.instock_qty,
                b.product_img_url,
                rl.loss_amount,
                rl.loss_totalprice,
                rl.loss_freight,
                rl.loss_process_cost,
                rl.remark,
                rl.is_abnormal,
                rl.responsible_user,
                rl.responsible_user_number,
                rl.responsible_party'
            )
                ->from('purchase_suggest_map a')
                ->join('purchase_order_items b','a.sku=b.sku and a.purchase_number=b.purchase_number','left')
                ->join('purchase_order d','a.purchase_number=d.purchase_number','left')
                ->join($this->table_name.' rl','rl.pur_number=a.purchase_number and rl.demand_number=a.demand_number','left')
                ->join('warehouse_results_main c','c.sku=b.sku and c.purchase_number=b.purchase_number','left')
                ->where('a.purchase_number',$row['pur_number'])
                ->where('a.sku',$row['sku'])
                ->where('rl.id',$id)
                ->order_by('rl.id','desc')
                ->get()->result_array();

            foreach ($list as &$value){
                if (empty($value['instock_qty'])) $value['instock_qty']=0;
            }

            return $list;
        }else{
            return;
        }
    }

    /**
    * 格式化审核界面数据
    * @param $data
    * @return array | bool  
    * @author Jaxton 2019/01/24
    */
    public function formart_approval_page($data){
        
        if(!empty($data)){
            $result = [];
            $total_freight = 0;//报损运费总计
            $total_process_cost = 0;//报损加工费总计
            $total_price = 0;//报损总金额
            $remark = "";
            $responsible_user         = '';
            $responsible_user_number  = '';
            $responsible_party        = '';
            foreach ($data as &$value){
                $total_freight += $value['loss_freight'];
                $total_process_cost += $value['loss_process_cost'];
                $total_price += $value['loss_totalprice'];
                $remark .= $value['remark'].' ';
                unset($value['remark']);

                $value['loss_amount'] = $value['loss_amount']?$value['loss_amount']:0;
                $value['loss_totalprice'] = $value['loss_totalprice']?$value['loss_totalprice']:0;
                $value['loss_freight'] = $value['loss_freight']?$value['loss_freight']:0;
                $value['loss_process_cost'] = $value['loss_process_cost']?$value['loss_process_cost']:0;

                $responsible_user        = $value['responsible_user']?$value['responsible_user']:'';
                $responsible_user_number = $value['responsible_user_number']?$value['responsible_user_number']:'';
                $responsible_party       = $value['responsible_party']?$value['responsible_party']:'';

                unset($value['responsible_user'],$value['responsible_user_number'],$value['responsible_party']);
            }

            $result['items_list'] = $data;
            $result['remark'] = $remark;
            $result['total_price'] = sprintf("%.3f",$total_price);
            $result['total_freight'] = sprintf("%.3f",$total_freight);
            $result['total_process_cost'] = sprintf("%.3f",$total_process_cost);
            $result['responsible_user'] = $responsible_user;
            $result['responsible_user_number'] = $responsible_user_number;
            $result['responsible_party'] = $responsible_party;

            return $result;
        }else{
            return false;
        }
    }

    /**
    * 获取报损数据列表
    * @param $params
    * @param $offset
    * @param $limit
    * @return array   
    * @author Jaxton 2019/01/17
    */
    public function get_report_loss_list($params,$offset=null,$limit=null,$page=1){
    	$field='l.apply_person,o.buyer_id,l.id,l.bs_number,l.pur_number,l.sku,demand_number,l.product_name,l.confirm_amount,l.price,l.loss_amount,
    	l.loss_totalprice,l.loss_freight,l.loss_process_cost,l.apply_person,l.apply_time,l.responsible_user,
    	l.responsible_user_number,l.responsible_party,l.relative_superior_number,
        l.audit_person,l.audit_time,l.approval_person,l.approval_time,
        l.status,l.is_abnormal,rm.instock_qty,o.supplier_code,o.supplier_name';
    	$this->purchase_db->select($field)
            ->from($this->table_name.' l')
            ->join('warehouse_results_main rm','rm.purchase_number=l.pur_number and rm.sku=l.sku','left')
            ->join('purchase_order o','o.purchase_number=l.pur_number','left');

    	if(isset($params['sku']) && !empty($params['sku'])){//批量，单个
            $search_sku=query_string_to_array($params['sku']);
    		$this->purchase_db->where_in('l.sku',$search_sku);
    	}

    	if( isset($params['group_ids']) && !empty($params['group_ids']))
        {
            $this->purchase_db->where_in("l.apply_person",$params['groupdatas']);
        }


        if(isset($params['pur_number']) && !empty($params['pur_number'])){
            if(is_array($params['pur_number'])){
                $this->purchase_db->where_in('l.pur_number',$params['pur_number']);
            }else{
                $pur_number_list = explode(' ', $params['pur_number']);
                $this->purchase_db->where_in('l.pur_number',$pur_number_list);
            }
        }

        if(isset($params['bs_number']) && !empty($params['bs_number'])){
            $this->purchase_db->where('l.bs_number',$params['bs_number']);
        }

    	if(isset($params['apply_person']) && !empty($params['apply_person'])){

    	    if(is_array($params['apply_person'])) {
                $this->purchase_db->where_in('l.apply_person', $params['apply_person']);
            }else{
                $this->purchase_db->where('l.apply_person', $params['apply_person']);
            }
    	}

        if(isset($params['responsible_party']) && !empty($params['responsible_party'])){
            $this->purchase_db->where('l.responsible_party',$params['responsible_party']);
        }

    	if(isset($params['status']) && is_numeric($params['status'])){
    		$this->purchase_db->where('l.status',$params['status']);
    	}

    	if(isset($params['apply_time_start']) && !empty($params['apply_time_start'])){
    		$this->purchase_db->where('l.apply_time>=',$params['apply_time_start']);
    	}

    	if(isset($params['apply_time_end']) && !empty($params['apply_time_end'])){
    		$this->purchase_db->where('l.apply_time<=',$params['apply_time_end']);
    	}

    	if(isset($params['id']) && !empty($params['id'])){
    		$this->purchase_db->where_in('l.id',$params['id']);
    	}

        if(isset($params['demand_number']) && !empty($params['demand_number'])){

            $this->purchase_db->where_in('l.demand_number',explode(" ",$params['demand_number']));
        }

        if(isset($params['is_abnormal']) && !empty($params['is_abnormal'])){
            $this->purchase_db->where('l.is_abnormal',$params['is_abnormal']);
        }

        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('o.supplier_code',$params['supplier_code']);
        }

        if(isset($params['relative_superior_number']) && !empty($params['relative_superior_number'])){
            $this->purchase_db->where('l.relative_superior_number',$params['relative_superior_number']);
        }

        if( isset($params['loss_totalprice_min']) && !empty($params['loss_totalprice_min'])&&
            isset($params['loss_totalprice_max']) && !empty($params['loss_totalprice_max']) ){
            $this->purchase_db->where('l.loss_totalprice >',$params['loss_totalprice_min']);
            $this->purchase_db->where('l.loss_totalprice <',$params['loss_totalprice_max']);
        }

        if(isset($params['audit_time_start']) && !empty($params['audit_time_start'])){
            $this->purchase_db->where('l.audit_time>=',$params['audit_time_start']);
        }

        if(isset($params['audit_time_end']) && !empty($params['audit_time_end'])){
            $this->purchase_db->where('l.audit_time<=',$params['audit_time_end']);
        }


        if(isset($params['approval_time_start']) && !empty($params['approval_time_start'])){
               $this->purchase_db->where('l.approval_time>=',$params['approval_time_start']);
        }

        if(isset($params['approval_time_end']) && !empty($params['approval_time_end'])){
            $this->purchase_db->where('l.approval_time<=',$params['approval_time_end']);
        }




    	$clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        $this->purchase_db=$clone_db;
        $result=$this->purchase_db->order_by('apply_time','DESC')->limit($limit,$offset)->get()->result_array();
        if(!empty($result)) {
            $buyerIds = array_unique(array_column($result, "apply_person"));
            if(!empty($buyerIds)){

                $buyerIds = array_map(function($name){

                    return sprintf("'%s'",$name);
                },$buyerIds);

            }
            $buyerName = $this->User_group_model->getNameGroupMessage($buyerIds);

            $buyerName = array_column($buyerName, NULL, 'user_name');
        }else{
            $buyerName = [];
        }
        foreach ($result as $key=>$value){
             $instock_qty = $value['instock_qty'];
            if(empty($instock_qty)) $instock_qty =0;
            $result[$key]['loss_price'] = format_price($value['loss_totalprice']-$value['loss_freight']-$value['loss_process_cost']);//报损金额(含运费)-报损运费=报损金额
            $result[$key]['instock_qty'] = $instock_qty;
            $result[$key]['groupName']                = isset($buyerName[$value['apply_person']])?$buyerName[$value['apply_person']]['group_name']:'';
        }

        $return_data = [
            'data_list'   => [
                'value' => $result,
                'key' =>[
                    'ID','备货单号','采购单号','供应商名称','审核状态','商品信息','单价','采购数量','入库数量','报损数量','报损商品额','报损运费','报损加工费','报损金额','申请人/申请时间','审核人/审核时间','操作'
                ],
                'drop_down_box' => [
                    'status_list' => getReportlossApprovalStatus(),
                    'user_list' => getBuyerDropdown(),
                    'is_abnormal' => getReportLossIsAbnormal(),
                    'lossResponsibleParty' => getReportlossResponsibleParty(),
                ]
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }

    /**
    * 获取用户下拉
    */
    public function get_user_list(){
        $user_down_box=[];
        $this->load->model('user/Purchase_user_model');
        $user_list=$this->Purchase_user_model->get_user_all_list();
        if(!empty($user_list)){
            foreach($user_list as $k => $v){
                $user_down_box[$v['id']]=$v['name'];
            }
        }
        return $user_down_box;
    }

    /**
    * 数据格式化
    * @param $data_list
    * @return array   
    * @author Jaxton 2019/01/18
    */
    public function formart_report_loss_list($data_list){
    	if(!empty($data_list)){
    		foreach($data_list as $key => $val){
    			$data_list[$key]['status']=getReportlossApprovalStatus($val['status']);
                $data_list[$key]['responsible_party']=getReportlossResponsibleParty($val['responsible_party']);
    		}
    	}
    	return $data_list;
    }

    /**
     * 审核
     * @param $id
     * @param $approval_type
     * @param $remark
     * @param $responsible_user
     * @param $responsible_user_number
     * @param $responsible_party
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function approval_handle($id,$approval_type,$remark,$responsible_user,$responsible_user_number,$responsible_party){
    	$order_info=$this->get_one_report_loss($id);
    	if($order_info){

    	    if ($order_info['is_abnormal']==1){
                $this->error_msg.='为异常单,需联系IT进行处理';
                return [
                    'success'=>$this->success,
                    'error_msg'=>$this->error_msg
                ];
            }

    		$status=$order_info['status'];
    		if(in_array($status, [REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT])){
    			$this->purchase_db->trans_begin();
                try{
                    if($approval_type==1){//通过
                        if($status==REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT){//经理审核
                            if(empty($responsible_party)) throw new Exception('责任承担方式必填');
                            $edit_data=[
                                'status'=>REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT,
                                'audit_person'=>getActiveUserName(),
                                'audit_time'=>date('Y-m-d H:i:s'),
                                'audit_notice'=>$remark,
                                'responsible_user'=>$responsible_user,
                                'responsible_user_number'=>$responsible_user_number,
                                'responsible_party'=>$responsible_party,
                            ];
                            $log_data=[
                                'id'=>$id,
                                'type'=>$this->table_name,
                                'content'=>'经理审核',
                                'detail'=>'经理审核报损单'
                            ];
                        }elseif($status==REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT){//财务审核
                            $edit_data=[
                                'status'=>REPORT_LOSS_STATUS_FINANCE_PASS,
                                'approval_person'=>getActiveUserName(),
                                'approval_time'=>date('Y-m-d H:i:s'),
                                'approval_notice'=>$remark
                            ];
                            $log_data=[
                                'id'=>$id,
                                'type'=>$this->table_name,
                                'content'=>'财务审核',
                                'detail'=>'财务审核报损单'
                            ];
                            //修改订单相关信息
                            
                            $this->purchase_db->where('purchase_number',$order_info['pur_number'])->update('purchase_order',['is_destroy'=>1]);//核销(是)
                            $err_msg =  $this->update_purchase_order($order_info['pur_number'],$order_info['sku'],$order_info['loss_amount']);
                        }
                    }elseif($approval_type==2){//不通过

                        if($status==REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT){//经理审核
                            $edit_data=[
                                'status'=>REPORT_LOSS_STATUS_MANAGER_REJECTED,
                                'audit_person'=>getActiveUserName(),
                                'audit_time'=>date('Y-m-d H:i:s'),
                                'audit_notice'=>$remark
                            ];
                            $log_data=[
                                'id'=>$id,
                                'type'=>$this->table_name,
                                'content'=>'经理驳回',
                                'detail'=>'经理驳回报损单'
                            ];
                            $this->Message_model->AcceptMessage('report',['data'=>[$id],'message'=>$remark,'user'=>getActiveUserName(),'type'=>'经理审核']);
                        }elseif($status==REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT){//财务审核
                            $edit_data=[
                                'status'=>REPORT_LOSS_STATUS_FINANCE_REJECTED,
                                'approval_person'=>getActiveUserName(),
                                'approval_time'=>date('Y-m-d H:i:s'),
                                'approval_notice'=>$remark
                            ];
                            $log_data=[
                                'id'=>$id,
                                'type'=>$this->table_name,
                                'content'=>'财务驳回',
                                'detail'=>'财务驳回报损单'
                            ];


                            $this->Message_model->AcceptMessage('report',['data'=>[$id],'message'=>$remark,'user'=>getActiveUserName(),'type'=>'财务审核']);
                        }

                        $this->purchase_db->where('purchase_number',$order_info['pur_number'])->update('purchase_order',['is_destroy'=>0]);//核销(否)
//                        $this->push_reject_report_loss($id);
                  
                    }

                    $edit_result=$this->purchase_db->where('id',$id)->update($this->table_name,$edit_data);

                    if (empty($edit_result)) throw new Exception('报损记录更新失败');

                    $this->load->model('purchase/purchase_order_model');
                    $order_update = $this->purchase_order_model->change_status($order_info['pur_number']);
                    if (empty($order_update)) {
                        throw new Exception('采购单更新失败');
                    }

                    //报损审核通过,马上推送到仓库
                    /*if ($approval_type==1 && $edit_data['status']==REPORT_LOSS_STATUS_FINANCE_PASS){
                        $this->push_success_report_loss($id);
                    }*/
                    //报损审核通过,重新计算未开票数量
                    if ($approval_type==1 && $edit_data['status']==REPORT_LOSS_STATUS_FINANCE_PASS){
                        $recal_uninvoiced_qty_key = 'recal_uninvoiced_qty';//报损审核通过,重新计算未开票数量
                        $sku = '';
                        $this->rediss->set_sadd($recal_uninvoiced_qty_key,sprintf('%s$$%s',$order_info['pur_number'],$sku));
                        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$recal_uninvoiced_qty_key);
                    }

                    $this->load->helper('common');
                    operatorLogInsert($log_data);

                    $this->purchase_db->trans_commit();
                    $this->success=true;
                }catch(Exception $e){
                    $this->purchase_db->trans_rollback();
                    $this->error_msg.=$e->getMessage();  
                }
    				    		
    		}else{
    			$this->error_msg.='当前状态不可审核';
    		}
    		
    	}else{
    		$this->error_msg.='审核错误，此条报损数据不存在';
    	}
    	return [
    		'success'=>$this->success,
    		'error_msg'=>$this->error_msg
    	];
    }

    /**
    * 修改采购单信息
    */
    public function update_purchase_order($purchase_number,$sku,$loss_qty){
        try{
            $purchase_info=$this->purchase_db->select('*')->from('purchase_order')
            ->where(['purchase_number'=>$purchase_number])->get()->row_array();

            if($purchase_info){
                //判断是否已全部报损

                $item_info=$this->purchase_db->select(
                'b.*,
                rl.loss_amount'
                )
                    ->from('purchase_suggest_map a')
                    ->join('purchase_order_items b','a.sku=b.sku and a.purchase_number=b.purchase_number','left')
                    ->join($this->table_name.' rl','rl.pur_number=a.purchase_number and rl.demand_number=a.demand_number','left')
                    ->where('a.purchase_number',$purchase_number)
                    ->order_by('rl.id','desc')
                    ->get()->result_array();

                $flag=0;
                $flag_not_waiting_leave = false;
                if($item_info){
                    foreach($item_info as $key => $val){
                        $purchase_amount=$val['purchase_amount'];//需求数量
                        $upselft_amount=$val['upselft_amount'];//上架数量(入库数量)
                        //取消未到货数量
                        $cancel=$this->purchase_db->select('SUM(a.cancel_ctq) sum_cancle_qty')->from('purchase_order_cancel_detail a')
                        ->join('purchase_order_cancel b','a.cancel_id=b.id')
                        ->where(['a.purchase_number'=>$val['purchase_number'],'a.sku'=>$val['sku']])
                        ->where_in('b.audit_status',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])
                        ->get()->row_array();

                        $total_cancel_qty = isset($cancel['sum_cancle_qty'])?$cancel['sum_cancle_qty']:0;
                        if(($purchase_amount-$upselft_amount-$total_cancel_qty)!=$val['loss_amount']){
                            $flag++;
                        }
                        //当入库数量和取消未到货数量都不为0时
                        if($upselft_amount && $total_cancel_qty){
                            $flag_not_waiting_leave = true;
                        }
                    }
                }
                if($flag>0){//未全部报损
                    if($purchase_info['purchase_order_status']==PURCHASE_ORDER_STATUS_WAITING_ARRIVAL){//等待到货
                        //$edit_data=['purchase_order_status'=>];
                    }elseif($purchase_info['purchase_order_status']==PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION){//已到货待检测
                        $edit_data=['purchase_order_status'=>PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE];
                    }elseif($purchase_info['purchase_order_status']==PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE){//部分到货等待剩余
                        //$edit_data=['purchase_order_status'=>PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE];
                    }

                }else{//未全部报损
                    if($purchase_info['purchase_order_status']==PURCHASE_ORDER_STATUS_WAITING_ARRIVAL){//等待到货
                        $edit_data=['purchase_order_status'=>PURCHASE_ORDER_STATUS_CANCELED];
                    }elseif($purchase_info['purchase_order_status']==PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION){//已到货待检测
                        $edit_data=['purchase_order_status'=>PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE];
                    }elseif($purchase_info['purchase_order_status']==PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE){//部分到货等待剩余

                        //取入库数量不为0,取消未到货不为0,此时订单改为部分到货不等待剩余
                        if($flag_not_waiting_leave){
                            $edit_data=['purchase_order_status'=>PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE];
                        }else{
                            $edit_data=['purchase_order_status'=>PURCHASE_ORDER_STATUS_ALL_ARRIVED];
                        }

                    }

                }
                if(!empty($edit_data) and isset($edit_data['purchase_order_status'])){
                    $this->load->model('purchase/Purchase_order_model');
                    $this->Purchase_order_model->change_status($purchase_number,$edit_data['purchase_order_status']);// 统一入口修改采购单状态
                }
                
            }
        }catch(Exception $e){
             return  $e->getMessage();
        }
        
    }

    function export_report_loss($params){
    	require_once APPPATH . 'third_party/PHPExcel/PHPExcel.php';
    	set_time_limit(3600);
        ini_set('memory_limit', '1024M');		        
        header("Content-Type:text/html;Charset=utf-8");
    	$objPHPExcel = new PHPExcel();
    	$objPHPExcel->setActiveSheetIndex(0)
		    ->setCellValue('A1', '采购单号')
		    ->setCellValue('B1', '审核状态')
		    ->setCellValue('C1', 'sku')
		    ->setCellValue('D1', '产品名称')
		    ->setCellValue('E1', '单价')
		    ->setCellValue('F1', '采购数量')
		    ->setCellValue('G1', '入库数量')
		    ->setCellValue('H1', '报损数量')
		    ->setCellValue('I1', '报损金额')
		    ->setCellValue('J1', '申请人')
		    ->setCellValue('K1', '申请时间')
		    ->setCellValue('L1', '经理审核人')
		    ->setCellValue('M1', '经理审核时间')
		    ->setCellValue('N1', '财务审核人')
		    ->setCellValue('O1', '财务审核时间')
            ->setCellValue('T1', '关联的取消编码')
		    ;

		$list=$this->get_report_loss_list($params);
    	if(!empty($list['data_list']['value'])){
    		$data_list=$list['data_list']['value'];
    		$i=2;
    		foreach($data_list as $key => $val){
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $val['pur_number'])
                    ->setCellValue('B'.$i, $val['supplier_code'])
                    ->setCellValue('C'.$i, $val['supplier_name'])
                    ->setCellValue('D'.$i, getReportlossApprovalStatus($val['status']))
                    ->setCellValue('E'.$i, $val['sku'])
                    ->setCellValue('F'.$i, $val['product_name'])
                    ->setCellValue('G'.$i, $val['price'])
                    ->setCellValue('H'.$i, $val['confirm_amount'])
                    ->setCellValue('I'.$i, $val['instock_qty'])
                    ->setCellValue('J'.$i, $val['loss_amount'])
                    ->setCellValue('K'.$i, $val['loss_freight'])
                    ->setCellValue('L'.$i, $val['loss_price'])
                    ->setCellValue('M'.$i, $val['loss_totalprice'])
                    ->setCellValue('N'.$i, $val['apply_person'])
                    ->setCellValue('O'.$i, $val['apply_time'])
                    ->setCellValue('P'.$i, $val['audit_person'])
                    ->setCellValue('Q'.$i, $val['audit_time'])
                    ->setCellValue('R'.$i, $val['approval_person'])
                    ->setCellValue('S'.$i, $val['approval_time'])
                    ->setCellValue('T'.$i, $val['relative_superior_number'])
                ;
			    $i++;
    		}
    	}

		// 设置第一个sheet为工作的sheet
		$objPHPExcel->getActiveSheet()->setTitle('REPORT_LOSS');
		$objPHPExcel->setActiveSheetIndex(0);

		//生成xlsx文件
		$filename = 'REPORT_LOSS-'.date('YmdHis');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');

		// 保存Excel 2007格式文件
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;

    }


    /**
    * 根据采购单获取报损数量以及状态
    * @param $purchase_number_arr
    * @return array   
    * @author Jaden 2019/03/09
    * @return array
    */
    public function get_loss_amount_by_purchase_number($purchase_number_arr){
        if(empty($purchase_number_arr) || !is_array($purchase_number_arr)){
            return false;
        }
        $field='pur_number,sku,loss_amount';
        $this->purchase_db->select($field)->from($this->table_name);
        $this->purchase_db->where_in('pur_number',$purchase_number_arr);
        $this->purchase_db->where('status',REPORT_LOSS_STATUS_FINANCE_PASS);
        $result_list=$this->purchase_db->get()->result_array();
        $loss_arr = array();
        if(!empty($result_list)){
            foreach ($result_list as $key => $value) {
                $loss_arr[$value['pur_number'].'_'.$value['sku']]['loss_amount'] = $value['loss_amount'];
            }
        }
        return $loss_arr;

    }

    /**
     * 获取 已审核通过的报损金额（PO维度）
     * @param $purchase_numbers
     * @return array
     */
    public function get_loss_money_by_purchase_number($purchase_numbers){
        // 计算报损商品金额
        $loss_pm_list = $this->purchase_db
            ->select('pur_number,sum(loss_totalprice - loss_freight - loss_process_cost) as loss_product_money')
            ->where_in('pur_number',$purchase_numbers)
            ->where('status', REPORT_LOSS_STATUS_FINANCE_PASS)
            ->group_by('pur_number')
            ->get($this->table_name)
            ->result_array();
        if(empty($loss_pm_list)) return [];

        // 按 采购单号+报损批次号 取第一条记录的ID
        $min_ids_list = $this->purchase_db
            ->select('min(id) as id')
            ->where_in('pur_number',$purchase_numbers)
            ->where('status', REPORT_LOSS_STATUS_FINANCE_PASS)
            ->group_by('pur_number,stamp_number')
            ->get($this->table_name)
            ->result_array();
        if(empty($min_ids_list)) return [];


        // 已审核通过的报损金额(计算运费、加工费)
        $loss_price_list = $this->purchase_db
            ->select(
                'pur_number,'
                .'sum(loss_freight) as loss_freight,'
                .'sum(loss_process_cost) as loss_process_cost'
            )
            ->where_in('pur_number',$purchase_numbers)
            ->where('status', REPORT_LOSS_STATUS_FINANCE_PASS)
            ->where_in('id',array_column($min_ids_list,'id')) // 只取一条
            ->group_by('pur_number')
            ->get($this->table_name)
            ->result_array();

        // 组装报损总金额
        $loss_pm_list = array_column($loss_pm_list,'loss_product_money','pur_number');
        foreach($loss_price_list as $key => $value){
            $loss_product_money = isset($loss_pm_list[$value['pur_number']])?$loss_pm_list[$value['pur_number']]:0;
            $loss_price_list[$key]['loss_product_money'] = $loss_product_money;
            $loss_price_list[$key]['loss_totalprice'] = format_price($loss_product_money + $value['loss_freight'] + $value['loss_process_cost']);
        }

        return $loss_price_list;
    }

    /**
     * 获取 已审核通过的报损金额（PO+SKU维度）
     * @param $purchase_numbers
     * @return array
     */
    public function get_loss_money_by_purchase_number_sku($purchase_numbers){
        // 已审核通过的报损金额
        $loss_price_list = $this->purchase_db
            ->select(
                'pur_number,sku,'
                .'sum(loss_amount) as loss_amount,'
                .'sum(loss_amount*price) as loss_product_money'
            )
            ->where_in('pur_number',$purchase_numbers)
            ->where('status', REPORT_LOSS_STATUS_FINANCE_PASS)
            ->group_by('pur_number,sku')
            ->get($this->table_name)
            ->result_array();

        return $loss_price_list;
    }

    /**
     * 获取 已付款报损金额（PO维度）
     * @param $purchase_numbers
     * @return array
     */
    public function get_paid_loss_money_by_purchase_number($purchase_numbers){
        // 报损请款方式 PO的已付款金额明细
        $po_paid_price_list = $this->purchase_db
            ->select(
                'poppd.purchase_number,'
                .'sum(poppd.product_money) as paid_product_money,'
                .'sum(poppd.freight) as paid_freight,'
                .'sum(poppd.discount) as paid_discount,'
                .'sum(poppd.process_cost) as paid_process_cost,'
                .'sum(poppd.pay_total) as paid_real_price'
            )
            ->from('purchase_order_pay as poy')
            ->join('purchase_order_pay_po_detail as poppd', 'poy.requisition_number=poppd.requisition_number', 'INNER')
            ->where('poy.pay_status',PAY_PAID)
            ->where('poy.requisition_method',PURCHASE_REQUISITION_METHOD_REPORTLOSS)
            ->where_in('poppd.purchase_number', $purchase_numbers)
            ->group_by('poppd.purchase_number')
            ->get()
            ->result_array();
        return $po_paid_price_list;
    }

    /**
     * 获取 已付款报损金额（PO+SKU维度）
     * @param $purchase_numbers
     * @return array
     */
    public function get_paid_loss_money_by_purchase_number_sku($purchase_numbers){
        // 报损请款方式 SKU的已付款金额
        $paid_price_list = $this->purchase_db
            ->select(
                'poyd.purchase_number,'
                .'poyd.sku,'
                .'sum(poyd.product_money) as paid_product_money'
            )
            ->from('purchase_order_pay as poy')
            ->join('purchase_order_pay_detail as poyd', 'poy.requisition_number=poyd.requisition_number', 'INNER')
            ->where('poy.pay_status',PAY_PAID)
            ->where('poy.requisition_method',PURCHASE_REQUISITION_METHOD_REPORTLOSS)
            ->where_in('poyd.purchase_number', $purchase_numbers)
            ->group_by('poyd.purchase_number,poyd.sku')
            ->get()
            ->result_array();
        return $paid_price_list;
    }

    /**
     * 根据采购单获取未完结报损数据
     * @param $purchase_number
     * @param $demand_number
     * @author Jaden 2019/03/09
     * @return array
     */
    public function unfinished_loss_status($purchase_number,$demand_number = null){
        if(empty($purchase_number)){
            return false;
        }
        $field='pur_number,sku,loss_amount,status';
        $this->purchase_db->select($field)->from($this->table_name);
        $this->purchase_db->where('pur_number',$purchase_number)
            ->where_in('status',[
                REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,
                REPORT_LOSS_STATUS_MANAGER_REJECTED,
                REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT,
                REPORT_LOSS_STATUS_FINANCE_REJECTED]);
        if(!is_null($demand_number)){
            $this->purchase_db->where('demand_number',$demand_number);
        }
        $result_list=$this->purchase_db->get()->result_array();
        if(!empty($result_list)){
            return true;
        }
        return false;

    }

    public function add_data(){
        $add_data=[];
        //$this->purchase_db->insert('');
        $list=$this->purchase_db->select('*')->from('pur_purchase_suggest')
        ->where(['demand_number'=>''])->get()->result_array();
        if($list){
            foreach($list as $k => $v){
                $str='RD000300'.$k;
                $this->purchase_db->where(['id'=>$v['id']])->update('pur_purchase_suggest',['demand_number'=>$str]);
            }
        }
        
    }


    //获取未推送的采购单报损记录
    public function get_un_push_report_loss_data($ids)
    {
        $data_list = [];
        $temp = [];
        $report_loss=$this->purchase_db->select('a.*,c.warehouse_code,b.transfer_warehouse,b.purchase_order_status,c.purchase_type_id')
            ->from($this->table_name.' a')
            ->join('purchase_order b','a.pur_number=b.purchase_number','left')
            ->join('purchase_suggest c','a.demand_number=b.demand_number','left')
            ->where('a.refund_status',NO_REFUND_STATUS)
            ->where('a.status',REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT)
            ->where('a.is_lock',1)//锁仓成功
            ->where('a.is_reduce',0)//未扣减在途
            ->where_in('a.id',$ids)//锁仓成功id
            ->get()->result_array();
        if(empty($report_loss)){
            throw new Exception('没有相关数据推送给仓库');
        }

        foreach ($report_loss as $row) {
            $temp['id'] = $row['id'];
            $temp['cancel_id'] = $row['id'];
            $temp['pur_number'] = $row['pur_number'];
            $temp['warehouse_code'] = $row['warehouse_code'];
            $temp['transit_warehouse'] = $row['transfer_warehouse'];
            $temp['sku'] = $row['sku'];
            $temp['ctq'] = $row['loss_amount'];
            $temp['cancel_operator'] = $row['create_user_name'];
            $temp['type'] = REPORT_LOSS_PUSH_TYPE;//取消类型 1：退款 2：报损  3取消剩余等待与中转仓同步
            $temp['check_operator'] = $row['modify_user_name'];
            $temp['status'] = $row['purchase_order_status'];//采购单状态
            $temp['demand_number'] = !empty($row['demand_number'])?$row['demand_number']:NULL;
            $temp['purchase_type'] = $row['purchase_type_id'];//业务线id
            $temp['is_new'] = 1;//区别新老系统
            $data_list[]=$temp;
        }
        return $data_list;
    }

    //获取需要推送仓库锁仓的报损数据
    public function get_report_loss_data($param)
    {
        $report_loss_list = $this->db->select('
                    lo.id,
                    lo.sku,
                    lo.create_user_name as cancel_operator,
                    lo.demand_number,
                    lo.create_time,
                    lo.remark as message,
                    lo.pur_number as purchase_order_no,
                    lo.loss_amount as cancel_num,
                    s.warehouse_code as transit_warehouse,
                    s.warehouse_code,
                    s.destination_warehouse,
                    s.purchase_type_id as purchase_type,
                    s.source_from
                    ')
            ->from('purchase_order_reportloss as lo')
            ->join('purchase_suggest s', 's.demand_number=lo.demand_number', 'left')
            ->join('purchase_order o', 'o.purchase_number=lo.pur_number', 'left')
            ->where('lo.refund_status',NO_REFUND_STATUS)
            ->where('lo.status',REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT)
            ->where('lo.is_lock',$param['is_lock'])
            ->where_in('lo.id',$param['ids'])
            ->order_by('lo.id DESC')
            ->get()->result_array();

        if(empty($report_loss_list)){
            throw new Exception('没有相关数据推送给仓库');
        }
        foreach ($report_loss_list as $key => &$value) {

            if(in_array($value['purchase_type'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){//海外仓
                $value['warehouse_code'] = $value['destination_warehouse'];
            }else{
                //海外仓以外,中专仓位空,warehouse_code为数据库本身值
                $value['warehouse_code'] = $value['transit_warehouse'];
                $value['transit_warehouse'] = '';

            }
            unset($value['destination_warehouse']);
            $value['type'] = 2;//类型:2是报损
        }

        return $report_loss_list;
    }

    //推送报损数据到数据中心
    public function push_report_loss_data_to_service($ids)
    {
        //获取待推送的数据
        $loss_data = $this->get_un_push_report_loss_data($ids);
        if (empty($loss_data)) {
            throw new Exception('没有相关数据推送数据中心');
        }

        //推送仓库
        $url = getConfigItemByName('api_config', 'service_data', 'push_report_loss_up');

        //单条推送仓库扣减在途
        foreach ($loss_data as $key => $data){
            $count = 0;
            $flag = true;

            $post[] = $data;
            $purchase = json_encode($post);
            $data_list = [
                'purchase' => $purchase,
            ];

            //推送仓库失败,推3次,3次之后还是失败,则改报损单状态为异常
            do{
                if ($count==3){
                    break;
                }

                $res = getCurlData($url, $data_list, 'POST');

                $api_log=[
                    'api_url'=>$url,
                    'record_type'=>'推送减在途数据到数据中心',
                    'post_content'=>$purchase,
                    'response_content'=>$res,
                    'create_time'=>date('Y-m-d H:i:s')
                ];
                apiRequestLogInsert($api_log);

                if (!is_json($res)){
                    $count++;
                    continue;
                }

                $result = json_decode($res, TRUE);

                if ($result['result'] != 'SUCCESS'){
                    $count++;
                    continue;
                }

                if (empty($result['data']['success_list'])){
                    $count++;
                    continue;
                }

                $flag = false;

            }while($flag);

            if ($flag){
                //减在途失败,更新报损单异常状态
                $this->purchase_db->where('id',$data['id'])->update('purchase_order_reportloss',['is_reduce'=>2,'is_abnormal'=>1,'modify_time'=>date('Y-m-d H:i:s')]);
            }else{
                //减在途成功,更新报损单状态
                $this->purchase_db->where('id',$data['id'])->update('purchase_order_reportloss',['is_reduce'=>1,'modify_time'=>date('Y-m-d H:i:s')]);
            }

        }

    }

    //更新推送状态
    public function update_report_loss_push($data,$type)
    {
        if(empty($data)){
            throw new Exception('仓库返回信息，错误');
        }
        foreach ($data as $val) {
            $update=$this->purchase_db
                ->where('id',$val['id'])
                ->update('purchase_order_reportloss',['refund_status'=>$type,'modify_time'=>date('Y-m-d H:i:s')]);
            if(empty($update)){
                throw new Exception('报损--采购单报损表,更新失败');
            }
        }
    }

    //获取已申请报损的记录及备货单数据
    public function get_apply_push_report_loss($id)
    {
        $data_list = [];
        $temp = [];
        $report_loss=$this->purchase_db->select('
            a.*,
            sg.warehouse_code,
            b.transfer_warehouse,
            b.purchase_order_status,
            sg.purchase_type_id,
            sg.product_category_id,
            sg.purchase_amount as suggest_purchase_amount,
            sg.id as suggest_id,
            sg.create_time as suggest_create_time,
            sg.sales_note,
            sg.buyer_id,
            ')->from($this->table_name.' a')
            ->join('purchase_order b','a.pur_number=b.purchase_number','left')
            ->join('purchase_suggest sg','sg.demand_number=a.demand_number and sg.sku=a.sku','left')
            ->where('a.refund_status',APPLY_REFUND_STATUS)
            ->where('a.id',$id)
            ->where_in('a.status',[REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT])
            ->get()->result_array();
        if(empty($report_loss)){
            throw new Exception('没有相关数据推送给仓库');
        }

        foreach ($report_loss as $row) {
            $temp['id'] = $row['id'];
            $temp['pur_number'] = $row['pur_number'];
            $temp['demand_number'] = !empty($row['demand_number'])?$row['demand_number']:NULL;
            $temp['warehouse_code'] = $row['warehouse_code'];
            $temp['transit_warehouse'] = $row['transfer_warehouse'];//中转仓
            $temp['sku'] = $row['sku'];
            $temp['create_time'] = $row['create_time'];
            $temp['ctq'] = $row['loss_amount'];
            $temp['cancel_operator'] = $row['create_user_name'];
            $temp['type'] = REPORT_LOSS_PUSH_TYPE;//取消类型 1：退款 2：报损  3取消剩余等待与中转仓同步
            $temp['check_operator'] = $row['modify_user_name'];
            $temp['status'] = $row['purchase_order_status'];//采购单状态
            $temp['purchase_type'] = $row['purchase_type_id'];//业务线id
            $temp['demand_info']['sku'] = $row['sku'];//需求单sku
            $temp['demand_info']['pur_number'] = $row['pur_number'];//需求单sku
            $temp['demand_info']['purchase_quantity'] = $row['suggest_purchase_amount'];//备货数量
            $temp['demand_info']['platform_number'] = '';//平台号
            $temp['demand_info']['platform_id'] = $row['suggest_id'];//备货单id
            $temp['demand_info']['transport_style'] = '';//运输方式
            $temp['demand_info']['transit_number'] = $row['purchase_amount'];//中转数量
            $temp['demand_info']['create_time'] = $row['suggest_create_time'];//需求生成时间
            $temp['demand_info']['sales_note'] = $row['sales_note'];//销售备注
            $temp['demand_info']['level_audit_status'] = '';//
            $temp['demand_info']['is_purchase'] = '';//
            $temp['demand_info']['transit_warehouse'] = $row['transfer_warehouse'];//中转仓
            $temp['demand_info']['product_category'] = $row['product_category_id'];//产品类别ID
            $temp['demand_info']['demand_number'] = $row['demand_number'];//产品类别ID
            $temp['demand_info']['purchase_warehouse'] = $row['warehouse_code'];//采购目的仓
            $temp['demand_info']['ship_code'] = '';//物流方式
            $temp['demand_info']['create_id'] = $row['buyer_id'];//物流方式
            $data_list[]=$temp;
        }
        return $data_list;
    }

    //推送驳回的报损数据
    public function push_reject_report_loss($id)
    {
        //获取已申请报损的记录及备货单数据
        $data = $this->get_apply_push_report_loss($id);
        if (empty($data)) {
            throw new Exception('没有相关数据推送仓库');
        }

        $purchase = json_encode($data);
        $data_list = [
            'purchase' => $purchase,
        ];

        //推送数据中心
        $url = getConfigItemByName('api_config', 'service_data', 'push_reject_report_loss');
        $res = getCurlData($url, $data_list, 'POST');
        $reslut = json_decode($res, TRUE);

        if (!isset($reslut['success_list'])){
            throw new Exception('数据中心返回信息，错误');
        }

        if ($reslut['error'] == '1') {
            $this->update_report_loss_push($reslut['success_list'],REJECT_REFUND_STATUS);
        } else {
            throw new Exception('推送数据中心失败');
        }
    }

    public function get_preview_edit_data($id)
    {
        $return = ['code'=>0,'data'=>[],'msg'=>''];
        $report_loss=$this->purchase_db->select('
            a.id,
            a.pur_number,
            a.demand_number,
            a.sku,
            a.product_name,
            a.price,
            a.confirm_amount,
            a.loss_amount,
            a.loss_freight,
            a.loss_process_cost,
            a.loss_totalprice,
            a.remark,
            a.responsible_user,
            a.responsible_user_number,
            a.responsible_party,
            ware.instock_qty,
            p.product_img_url,
            o.purchase_order_status,
            o.supplier_code,
            o.supplier_name
            ')->from($this->table_name.' a')
            ->join('warehouse_results_main as ware', 'ware.purchase_number=a.pur_number AND ware.sku=a.sku', 'left')
            ->join('product p','p.sku=a.sku','left')
            ->join('purchase_order o','o.purchase_number=a.pur_number','left')
            ->where('a.id',$id)
            ->get()->row_array();
        if(empty($report_loss)){
            $return['msg'] = '数据不存在';
            return $return;
        }


        $this->load->model("purchase/Purchase_order_determine_model");
        $this->load->model('finance/Purchase_order_pay_model');

        $purchase_number = $report_loss['pur_number'];
        $sku             = $report_loss['sku'];

        $report_loss['purchase_total_price'] = format_two_point_price($report_loss['confirm_amount'] * $report_loss['price']);
        $order_cancel_list                   = $this->Purchase_order_determine_model->get_order_cancel_list($purchase_number, $sku);
        $report_loss['cancel_ctq']           = isset($order_cancel_list[$purchase_number.'-'.$sku]) ? $order_cancel_list[$purchase_number.'-'.$sku] : 0; //已取消数量
        $report_loss['cancel_total_price']   = format_two_point_price($report_loss['cancel_ctq'] * $report_loss['price']);

        // 已付金额
        $order_paid                        = $this->Purchase_order_pay_model->get_pay_total_by_compact_number($purchase_number);
        $report_loss['paid_pay_price']     = format_two_point_price($order_paid['pay_price']);
        $report_loss['paid_product_money'] = format_two_point_price($order_paid['product_money']);
        $report_loss['paid_freight']       = format_two_point_price($order_paid['freight']);
        $report_loss['paid_discount']      = format_two_point_price($order_paid['discount']);
        $report_loss['paid_process_cost']  = format_two_point_price($order_paid['process_cost']);


        if (in_array($report_loss['purchase_order_status'],[PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT])){
            $return['msg'] = 'PO:'.$report_loss['pur_number'].'状态为“信息修改驳回”或者“信息修改待审核”的，不允许编辑';
            return $return;
        }


        if (empty($report_loss['instock_qty'])) $report_loss['instock_qty']=0;


        $this->load->helper('abnormal');
        $lossResponsibleParty = getReportlossResponsibleParty();
        $down_box_list['lossResponsibleParty'] = $lossResponsibleParty;

        $return['code'] = 1;
        $return['data']['value'] = $report_loss;
        $return['data']['key'] = ['订单号','备货单号','图片','SKU','产品名称','单价','采购数量','入库数量','报损数量','报损运费','报损加工费','报损金额'];
        $return['data']['down_box_list'] = $down_box_list;
        return $return;

    }

    public function update_reportloss_data($id, $loss_freight=0,$loss_process_cost=0, $loss_price=0, $remark='',$responsible_user,$responsible_user_number,$responsible_party)
    {
        $return = ['code'=>0,'msg'=>''];
        $report_loss=$this->purchase_db->select('a.*')
            ->from($this->table_name.' a')
            ->where('a.id',$id)
            ->get()->row_array();
        if(empty($report_loss)){
            $return['msg'] = '数据不存在';
            return $return;
        }

        if (!in_array($report_loss['status'],[REPORT_LOSS_STATUS_MANAGER_REJECTED,REPORT_LOSS_STATUS_FINANCE_REJECTED])){
            $return['msg'] = '只有状态为经理驳回或财务驳回的才能编辑';
            return $return;
        }

        $this->load->model('purchase/Purchase_order_model');
        $items_info = $this->Purchase_order_model->max_report_loss_number($report_loss['pur_number'],$report_loss['sku']);
        if(bccomp($loss_price - $loss_freight - $loss_process_cost,$report_loss['price'] * $items_info['max_number']) > 0 ){
            $return['msg'] = '报损商品额必须小于 可申请报损数量*单价';
            return $return;
        }

        $where['id'] = $id;

        $update['loss_freight'] = $loss_freight;
        $update['loss_process_cost'] = $loss_process_cost;
        $update['loss_totalprice'] = $loss_price;
        $update['status'] = REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT;//待经理审核
        $update['modify_time'] = date("Y-m-d H:i:s");
        $update['modify_user_name'] = getActiveUserName();
        $update['remark'] = !empty($remark)?$remark:$report_loss['remark'];
        $update['responsible_user'] = $responsible_user;
        $update['responsible_user_number'] = $responsible_user_number;
        $update['responsible_party'] = $responsible_party;
        $update['audit_time'] = '0000-00-00 00:00:00';
        $update['audit_person'] = '';
        $update['audit_notice'] = '';
        $update['approval_time'] = '0000-00-00 00:00:00';
        $update['approval_person'] = '';
        $update['approval_notice'] = '';

        $res =  $this->purchase_db->update($this->table_name,$update,$where);

        $this->Purchase_order_model->change_status($report_loss['pur_number']);
        if (!$res) {
            $return['msg'] = '编辑失败';
            return $return;
        }

        operatorLogInsert(
            [
               'id' => $id,
               'type' => 'purchase_order_reportloss',
               'content' => '编辑报损信息',
               'detail' => '运费更改为【' . $loss_freight . '】,报损总金额(含运费)更改为【'.$loss_price.'】'
            ]
        );

        $return['code'] = 1;
        return $return;

    }

    //推送报损锁仓数据到仓库
    public function push_wms_to_lock_warehouse($ids)
    {
        $return = ['msg'=>'','success_ids'=>[]];
        $msg = '';
        $param['is_lock'] = 0;
        $param['ids'] = $ids;
        //获取待推送的数据
        $data = $this->get_report_loss_data($param);
        if (empty($data)) {
            throw new Exception('没有相关数据推送数据中心');
        }

        $post_data['data'] = json_encode($data);
        $post_data['token'] = json_encode(stockAuth());

        //推送仓库
        $url = getConfigItemByName('api_config', 'wms_system', 'push_report_list_to_lock');
        $res = getCurlData($url, $post_data, 'POST');

        $api_log=[
            'api_url'=>$url,
            'record_type'=>'报损锁仓信息推送到仓库',
            'post_content'=>$post_data['data'],
            'response_content'=>$res,
            'create_time'=>date('Y-m-d H:i:s')
        ];
        apiRequestLogInsert($api_log);

        if (!is_json($res)){
            throw new Exception('推送仓库失败: '.$res);
        }

        $result = json_decode($res, TRUE);

        if ($result['code'] != 1) throw new Exception('推送仓库失败:'.$result['message'].$result['msg']);

        //更新数据库
        if (isset($result['data']['success_list']) && !empty($result['data']['success_list'])) {
            //更新锁仓成功的单
            $update = [];
            $update_date = $result['data']['success_list'];

            foreach ($update_date as $k => $v){
                $update[$k]['id'] = $v['id'];
                $update[$k]['is_lock'] = 1;//锁仓成功
            }

            $this->purchase_db->update_batch('purchase_order_reportloss',$update,'id');

        }else{
            throw new Exception('锁仓失败,报损失败');
        }

        if (isset($result['data']['fail_list']) && !empty($result['data']['fail_list'])) {
            //更新推送失败的单
//            $update = [];
            $update_date = $result['data']['fail_list'];

            foreach ($update_date as $k => $v){
//                $update[$k]['id'] = $v['id'];
//                $update[$k]['is_lock'] = 2;//锁仓失败
                $msg.='采购单['.$v['purchase_order_no'].']-sku['.$v['sku'].']锁仓失败,申请报损失败;';
                $where['id'] = $v['id'];
                $this->purchase_db->delete('purchase_order_reportloss',$where);//锁仓失败,删除申请报损记录
            }

//            $this->purchase_db->update_batch('purchase_order_reportloss',$update,'id');

        }

        $return['msg'] = $msg;
        $return['success_ids'] = array_column($update,'id');
        return $return;

    }

    /*
     * 报损审核采购后马上推送到仓库
     */
    public function push_success_report_loss($id)
    {
        $where='lo.status=4 AND lo.is_push_wms in(0,2) AND lo.is_lock=1 AND lo.is_reduce=1';
        if(!empty($id)){
            $where.=' AND lo.id='.$id;
        }else{
            throw new Exception('报损主键id缺失');
        }

        //读取配置文件参数，获取仓库接收快递信息接口地址
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('wms_system'))) {
            $wms_info = $this->config->item('wms_system');
            $_url_report_loss_success = isset($wms_info['report_loss_success_list'])?$wms_info['report_loss_success_list']:'';
            if(empty($_url_report_loss_success)){
                throw new Exception('推送地址缺失');
            }
            $push_url = $_url_report_loss_success;
        }else{
            throw new Exception('推送地址缺失');
        }

        try {

            $report_loss_list = $this->db->select('
            lo.id,
            lo.sku,
            lo.create_user_name as cancel_operator,
            lo.demand_number,
            lo.create_time,
            lo.remark as message,
            lo.pur_number as purchase_order_no,
            lo.loss_amount as cancel_num,
            s.warehouse_code as transit_warehouse,
            s.warehouse_code,
            s.destination_warehouse,
            s.purchase_type_id as purchase_type,
            s.source_from
            ')
                ->from('purchase_order_reportloss as lo')
                ->join('purchase_suggest s', 's.demand_number=lo.demand_number', 'left')
                ->join('purchase_order o', 'o.purchase_number=lo.pur_number', 'left')
                ->where($where)
                ->order_by('lo.id DESC')
                ->get()->result_array();

            if (empty($report_loss_list)) throw new Exception('待推送报损审核数据不存在');

            foreach ($report_loss_list as $key => &$value) {

                if(in_array($value['purchase_type'],[PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){//海外仓

                    $value['warehouse_code'] = $value['destination_warehouse'];

                }else{
                    //海外仓以外,中专仓位空,warehouse_code为数据库本身值
                    $value['warehouse_code'] = $value['transit_warehouse'];
                    $value['transit_warehouse'] = '';

                }
                unset($value['destination_warehouse']);
                $value['type'] = 2;//类型:2是报损
            }

//                    var_dump($report_loss_list);die;

            $post_data['data'] = json_encode($report_loss_list);
            $post_data['token'] = json_encode(stockAuth());
//                    print_r($post_data);die;
            $result = getCurlData($push_url,$post_data);

            if(is_json($result)){
                $result = json_decode($result,true);

                if ($result['code'] != 1){
                    $api_log=[
                        'api_url'=>$push_url,
                        'record_type'=>'报损成功信息推送到仓库',
                        'post_content'=>$post_data['data'],
                        'response_content'=>$result,
                        'create_time'=>date('Y-m-d H:i:s')
                    ];
                    apiRequestLogInsert($api_log);
                    throw new Exception($result['message'].$result['msg']);
                }

                //更新数据库
                if (isset($result['data']['success_list']) && !empty($result['data']['success_list'])) {
                    //更新推送成功的单
                    $update = [];
                    $update_date = $result['data']['success_list'];

                    foreach ($update_date as $k => $v){
                        $update[$k]['id'] = $v['id'];
                        $update[$k]['is_push_wms'] = 1;//成功
                    }

                    $this->db->update_batch('purchase_order_reportloss',$update,'id');

                }

                if (isset($result['data']['fail_list']) && !empty($result['data']['fail_list'])) {
                    //更新推送失败的单
                    $update = [];
                    $update_date = $result['data']['fail_list'];

                    foreach ($update_date as $k => $v){
                        $update[$k]['id'] = $v['id'];
                        $update[$k]['is_push_wms'] = 2;//失败
                    }

                    $this->db->update_batch('purchase_order_reportloss',$update,'id');

                }
            }else{
                $api_log=[
                    'api_url'=>$push_url,
                    'record_type'=>'报损成功信息推送到仓库',
                    'post_content'=>$post_data['data'],
                    'response_content'=>$result,
                    'create_time'=>date('Y-m-d H:i:s')
                ];
                apiRequestLogInsert($api_log);
                throw new Exception($result);
            }

        }catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }


    /*
     * 新增报损记录数据（采购系统内部转换才需要用到，一般是JAVA生成记录）
     * @author Jolon
     */
    public function save_report_loss($add_datas){

        $this->purchase_db->trans_begin();
        try{
            foreach($add_datas as $add_data){
                $add_result = $this->purchase_db->insert('purchase_order_reportloss', $add_data);
                if(empty($add_result))
                    throw new Exception('新增报损记录失败');
            }

            $this->purchase_db->trans_commit();

            return true;
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            return $e->getMessage();
        }
    }


    /**
     * 获取采购单审核通过的报损记录
     * @param $purchase_number
     * @param $sku
     * @return array
     */
    public function get_reportloss_info($purchase_number,$sku = null){
        $where = ['pur_number' => $purchase_number];

        if(!is_null($sku)) $where['sku'] = $sku;

        $row = $this->purchase_db->select('*')
            ->from('purchase_order_reportloss')
            ->where($where)
            ->where_in('status', [REPORT_LOSS_STATUS_FINANCE_PASS])
            ->get()
            ->result_array();

        return $row?$row:[];
    }


    /**
     * 获取报损汇总信息
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function get_report_loss_list_sum($params,$offset=null,$limit=null,$page=1){

        $field='count(distinct l.bs_number) as apply_num, count(distinct l.sku) as sku_num,count(distinct l.demand_number) as demand_num,count(distinct l.pur_number) as purchase_num,count(distinct o.supplier_code) as supplier_num,
       IFNULL(SUM(l.loss_totalprice),0) as total_loss_price_sum,IFNULL(SUM(l.loss_amount),0) as loss_amount_sum';
        $this->purchase_db->select($field)
            ->from($this->table_name.' l')
            ->join('warehouse_results_main rm','rm.purchase_number=l.pur_number and rm.sku=l.sku','left')
            ->join('purchase_order o','o.purchase_number=l.pur_number','left');

        if(isset($params['sku']) && !empty($params['sku'])){//批量，单个
            $search_sku=query_string_to_array($params['sku']);
            $this->purchase_db->where_in('l.sku',$search_sku);
        }

        if( isset($params['group_ids']) && !empty($params['group_ids']))
        {
            $this->purchase_db->where_in("l.apply_person",$params['groupdatas']);
        }

        if(isset($params['pur_number']) && !empty($params['pur_number'])){
            if(is_array($params['pur_number'])){
                $this->purchase_db->where_in('l.pur_number',$params['pur_number']);
            }else{
                $pur_number_list = explode(' ', $params['pur_number']);
                $this->purchase_db->where_in('l.pur_number',$pur_number_list);
            }
        }

        if(isset($params['bs_number']) && !empty($params['bs_number'])){
            $this->purchase_db->where('l.bs_number',$params['bs_number']);
        }

        if(isset($params['apply_person']) && !empty($params['apply_person'])){
            $this->purchase_db->where_in('l.apply_person',$params['apply_person']);
        }

        if(isset($params['responsible_party']) && !empty($params['responsible_party'])){
            $this->purchase_db->where('l.responsible_party',$params['responsible_party']);
        }

        if(isset($params['status']) && is_numeric($params['status'])){
            $this->purchase_db->where('l.status',$params['status']);
        }

        if(isset($params['apply_time_start']) && !empty($params['apply_time_start'])){
            $this->purchase_db->where('l.apply_time>=',$params['apply_time_start']);
        }

        if(isset($params['apply_time_end']) && !empty($params['apply_time_end'])){
            $this->purchase_db->where('l.apply_time<=',$params['apply_time_end']);
        }

        if(isset($params['id']) && !empty($params['id'])){
            $this->purchase_db->where_in('l.id',$params['id']);
        }

        if(isset($params['demand_number']) && !empty($params['demand_number'])){
            $this->purchase_db->where('l.demand_number',explode(" ",$params['demand_number']));
        }

        if(isset($params['is_abnormal']) && !empty($params['is_abnormal'])){
            $this->purchase_db->where('l.is_abnormal',$params['is_abnormal']);
        }

        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('o.supplier_code',$params['supplier_code']);
        }

        if(isset($params['relative_superior_number']) && !empty($params['relative_superior_number'])){
            $this->purchase_db->where('l.relative_superior_number',$params['relative_superior_number']);
        }

        if( isset($params['loss_totalprice_min']) && !empty($params['loss_totalprice_min'])&&
            isset($params['loss_totalprice_max']) && !empty($params['loss_totalprice_max']) ){
            $this->purchase_db->where('l.loss_totalprice >',$params['loss_totalprice_min']);
            $this->purchase_db->where('l.loss_totalprice <',$params['loss_totalprice_max']);
        }


        if(isset($params['audit_time_start']) && !empty($params['audit_time_start'])){
            $this->purchase_db->where('l.audit_time>=',$params['audit_time_start']);
        }

        if(isset($params['audit_time_end']) && !empty($params['audit_time_end'])){
            $this->purchase_db->where('l.audit_time<=',$params['audit_time_end']);
        }


        if(isset($params['approval_time_start']) && !empty($params['approval_time_start'])){
            $this->purchase_db->where('l.approval_time>=',$params['approval_time_start']);
        }

        if(isset($params['approval_time_end']) && !empty($params['approval_time_end'])){
            $this->purchase_db->where('l.approval_time<=',$params['approval_time_end']);
        }



        $result=$this->purchase_db->get()->row_array();


        return $result;
    }


}