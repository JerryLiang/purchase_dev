<?php
/**
 * 合同数据库模型类
 * User: Jaxton
 * Date: 2019/01/08 10:23
 */

class Compact_list_model extends Purchase_model {
	protected $compact_table = 'purchase_compact';//合同表
	protected $compact_file_table = 'purchase_compact_file';//合同扫描件上传表

    public function __construct(){
        parent::__construct();

        $this->load->helper('user');
        $this->load->helper('status_supplier');
        $this->config->load('key_name', FALSE, TRUE);
    }

    /**
     * 根据合同ID获取合同信息
     * @param  string $compact_id 合同号
     * @param string  $field 显示的字段
     * @return array
     */
    public function get_compact_by_id($compact_id, $field = '*'){
        $compactInfo = $this->purchase_db
            ->select($field)
            ->where('id', intval($compact_id))
            ->get($this->compact_table)
            ->row_array();

        return $compactInfo ? $compactInfo : [];
    }

    /**
    * 获取合同列表
    * @param $params
    * @param $offset
    * @param $limit
     * @param $action
    * @return array|int
    * @author Jaxton 2019/01/08
    */
    public function get_compact_list($params,$offset,$limit,$page=1,$action = null){
        $this->load->helper('status_order');

        $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
        $settlement_list = $this->settlementModel->get_settlement();//下拉列表供应商结算方式
        $settlement_list = $settlement_list['list']?array_column($settlement_list['list'],'settlement_name','settlement_code'):[];

        $query_builder = $this->purchase_db;

        $this->load->model('compact/Compact_file_model');

    	$field='c.id,c.compact_number,c.product_money,c.freight,c.discount,c.process_cost,c.real_money,c.compact_status,c.is_drawback,c.is_file_uploaded,
    	c.supplier_id,c.supplier_name,c.create_user_id,c.create_user_name,c.create_time,c.audit_user_id,c.audit_user_name,
    	c.audit_time,c.audit_note,c.modify_user_id,c.modify_user_name,c.modify_time,c.payment_status,si.img_url as si_url,si.compact_audit_status as si_status,si.file_name as si_name,
    	c.settlement_method,c.file_upload_time,sp.is_postage as free_shipping,
    	
        # 已付款和付款中的请款单的个数
        (SELECT COUNT(1) FROM pur_purchase_order_pay AS pay WHERE pay.pur_number=c.compact_number AND pay.pay_status IN(30,40,50,51,13,14,60,61,62,63) ) AS payed_count';

        $this->purchase_db->from($this->compact_table.' as c')
            ->join('pur_supplier as sp', 'c.supplier_code=sp.supplier_code', 'left')
            ->join('purchase_compact_items as ci', 'ci.compact_number=c.compact_number', 'left')
            ->join('pur_supplier_web_info as si', 'si.compact_num=c.compact_number', 'left')
            ->join('purchase_order as od', 'ci.purchase_number=od.purchase_number', 'left');
    	if(isset($params['compact_number']) && !empty($params['compact_number'])){
            $compact_number = explode(' ', trim($params['compact_number']));
            $this->purchase_db->where_in('c.compact_number', array_filter($compact_number));
    	}

        if(SetAndNotEmpty($params, 'free_shipping')){
            $this->purchase_db->where('sp.is_postage=', $params['free_shipping']);
        }

    	if(isset($params['create_user_id']) && !empty($params['create_user_id'])){

    	    if(is_array($params['create_user_id'])) {
                $this->purchase_db->where_in('c.create_user_id', $params['create_user_id']);
            }else{
                $this->purchase_db->where('c.create_user_id', $params['create_user_id']);
            }
    	}

        if(isset($params['group_ids']) && !empty($params['group_ids'])){

            $query_builder->where_in('od.buyer_id',$params['groupdatas']);
        }
        if(isset($params['payment_status']) && !empty($params['payment_status'])){

            if( is_array($params['payment_status'])) {
                $this->purchase_db->where_in('c.payment_status', $params['payment_status']);
            }else{
                $this->purchase_db->where('c.payment_status', $params['payment_status']);
            }
    	}
    	if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
    		$this->purchase_db->where('c.supplier_code',$params['supplier_code']);
    	}

    	if(!empty($params['purchase_type']) && is_array($params['purchase_type'])){
            $this->purchase_db->where_in('c.source', $params['purchase_type']);
        }

        if(!empty($params['settlement_method'])){
            $this->purchase_db->where('c.settlement_method', $params['settlement_method']);
        }

    	if(isset($params['is_drawback']) && !empty($params['is_drawback'])){
    	    $drawback = $params['is_drawback'] != 1 ? 0: 1;
            $this->purchase_db->where('c.is_drawback', $drawback);
        }

        if(isset($params["is_gateway"]) && is_numeric($params["is_gateway"])){
    	    $gateway = $params["is_gateway"] != 1 ? 0 : 1;
            $this->purchase_db->where('od.is_gateway', $gateway);
        }

        if(isset($params['file_upload_time_start']) && !empty($params['file_upload_time_start'])){
            $this->purchase_db->where('c.file_upload_time>=',$params['file_upload_time_start']);
        }

        if(isset($params['file_upload_time_end']) && !empty($params['file_upload_time_end'])){
            $this->purchase_db->where('c.file_upload_time<=',$params['file_upload_time_end']);
        }

    	if(isset($params['create_time_start']) && !empty($params['create_time_start'])){
    		$this->purchase_db->where('c.create_time>=',$params['create_time_start']);
    	}

    	if(isset($params['create_time_end']) && !empty($params['create_time_end'])){
    		$this->purchase_db->where('c.create_time<=',$params['create_time_end']);
    	}

        if(isset($params['statement_number']) && !empty($params['statement_number'])){
            $statement_numbers = query_string_to_array($params['statement_number']);
            $statement_numbers = "'".implode("','",$statement_numbers)."'";
            $this->purchase_db->where("c.compact_number IN(SELECT compact_number FROM pur_purchase_statement_items WHERE statement_number IN($statement_numbers))");
        }

        if(isset($params['is_file_uploaded']) && !empty($params['is_file_uploaded'])){
            $this->purchase_db->where_in('c.is_file_uploaded',$params['is_file_uploaded']);
        }
        //付款时间
        if(isset($params['payment_date_end'])&& isset($params['payment_date_start']) && $params['payment_date_start'] && $params['payment_date_end'] ){ //付款时间结束
            $pur_number = $query_builder->query("select pur_number from yibai_purchase.pur_purchase_order_pay 
where pay_status=51 and source=1 and payer_time>='".$params['payment_date_start']."' and payer_time<='".$params['payment_date_end']."'")->result_array();
            $number_list = !empty($pur_number)?array_column($pur_number,'pur_number'):[0];
            $this->purchase_db->where_in('c.compact_number',$number_list);
            $this->purchase_db->where('c.payment_status',51);

        }

        $clone_db  = clone($this->purchase_db);
        // 根据 cancel_id 维度计算记录个数
        $count_sql = $clone_db->select('c.id')->group_by('c.id')->get_compiled_select();
        $count_row = $clone_db->query("SELECT count(cc.id) as num FROM ($count_sql) AS cc")->row_array();
        $total = isset($count_row['num']) ? (int) $count_row['num'] : 0;
        if($action == 'sum'){
            return $total;// 只是获取总数
        }

        $result = $this->purchase_db->select($field)
            ->order_by('c.id', 'DESC')
            ->group_by('c.id')
            ->limit($limit, $offset)
            ->get()
            ->result_array();
        if($result){
            foreach($result as &$value){
                # enable 允许上传，disable 禁止上传
                # 未请款 且 合同状态为 未上传、采购驳回、已上传 才允许上传
                if($value['payed_count'] <=0 && in_array($value['is_file_uploaded'],[SRM_COMPACT_READY_STATUS,SRM_COMPACT_REFUSE_STATUS,SRM_COMPACT_ACCESS_STATUS])){
                    $value['is_allow_upload_file'] = 'enable';
                }else{
                    $value['is_allow_upload_file'] = 'disable';
                }
                $value['is_file_uploaded']  = getCompactIsFileUploaded($value['is_file_uploaded']);

                if(!in_array($value['si_status'],[SRM_COMPACT_WAIT_STATUS,SRM_COMPACT_REFUSE_STATUS])){// 非门户系统上传处理中
                    // 获取合同扫描件
                    $fileInfo = $this->Compact_file_model->see_compact_scanning_file($value['id']);
                    $value['file_name'] = isset($fileInfo['file_name'])?$fileInfo['file_name']:'';
                    $value['file_path'] = isset($fileInfo['file_path'])?$fileInfo['file_path']:'';
                }else{
                    $value['file_name'] = isset($value['si_name'])?$value['si_name']:'';
                    $value['file_path'] = isset($value['si_url'])?$value['si_url']:'';
                }
                $free_ship = getIsPostage();
                $val_ship = $value['free_shipping'];
                $value['free_shipping'] = isset($free_ship[$val_ship])?$free_ship[$val_ship]:"--";

                // 获取对账单号
                $statement_number_list = $this->purchase_db->select('statement_number')
                    ->where('compact_number',$value['compact_number'])
                    ->group_by('statement_number')
                    ->get('purchase_statement_items')
                    ->result_array();
                $value['statement_number'] = !empty($statement_number_list)?array_column($statement_number_list,'statement_number'):[];
            }
        }
        $return_data = [
            'data_list' => [
                'value'         => $result,
                'key'           => ['ID', '合同号', '金额', '状态', '是否退税', '供应商', '操作人', '操作时间', '操作', '是否包邮'],
                'drop_down_box' => [
                    //'supplier_list'=>$this->get_supplier_down_box(),
                    'user_list'         => getBuyerDropdown(),
                    'payment_status'    => getPayStatus(),
                    'is_file_uploaded'  => getCompactIsFileUploaded(),
                    'purchase_type'     => getPurchaseType(),//[1 => '国内仓', 2 => '海外', 3 => 'FBA', 4 => 'PFB', 5 => '平台头程']
                    'is_gateway' => [1 => '是', 2 => '否'],
                    'is_drawback' => ["1" => '是', "2" => '否'],
                    'free_shipping' => getIsPostage(),
                    'settlement_type' => $settlement_list,
                ]
            ],
            'page_data' => [
                'total'  => $total,
                'offset' => $page,
                'limit'  => $limit,
                'pages'  => ceil($total / $limit)
            ]
        ];
        return $return_data;

    }

    /**
    * 获取供应商下拉
    * @return array
    * @author Jaxton 2019/01/21
    */
    public function get_supplier_down_box(){
        $list=$this->purchase_db->select('supplier_code,id,supplier_name')
        ->from('supplier')
        ->get()->result_array();
        $new_data=[];
        if($list){
            foreach($list as $k => $v){
                $new_data[$v['id']]=$v['supplier_name'];
            }
        }
        return $new_data;
    }

    /**
    * 数据格式化
    * @param $data_list
    * @return array
    * @author Jaxton 2019/01/18
    */
    public function formart_compact_list($data_list){
        $this->load->helper('status_order');
        if(!empty($data_list)){
            $this->load->model("supplier/Supplier_settlement_model");
            $settlement_code_list = $this->Supplier_settlement_model->get_code_by_name_list();
            foreach($data_list as $key => $val){
                $compact_number = $val['compact_number'];
                //供应商门户是否有审核记录
                $this->purchase_db->select('compact_audit_status');
                $this->purchase_db->from('supplier_web_info');
                $this->purchase_db->where('compact_num',$val['compact_number']);
                $item = $this->purchase_db->get()->row_array();
                if(!empty($item)){
                    $data_list[$key]['compact_status']=$item['compact_audit_status'];
                }else{
                    $data_list[$key]['compact_status']=getCompactStatus($val['compact_status']);
                }
                $data_list[$key]['is_drawback']=getIsDrawbackShow($val['is_drawback']);
                $data_list[$key]['payment_status']=getPayStatus($val['payment_status']);
                $data_list[$key]['settlement_method'] = isset($settlement_code_list[$val['settlement_method']])?$settlement_code_list[$val['settlement_method']]:'';

                 // 判断合同下所有采购单是否有 未付款的有效状态的对账单
                $statement_number = $this->purchase_db->select('A.statement_number')
                    ->from('purchase_statement AS A')
                    ->join('purchase_statement_items AS B','A.statement_number=B.statement_number','INNER')
                    ->where("B.purchase_number IN( SELECT purchase_number FROM pur_purchase_compact_items WHERE compact_number='{$compact_number}' )")
                    ->where('A.statement_pay_status != 51')
                    ->where('A.status_valid=1')
                    ->get()
                    ->row_array();
                if($statement_number and isset($statement_number[0])){
                    $data_list[$key]['in_statement_payment'] = 1;
                }else{
                    $data_list[$key]['in_statement_payment'] = 0;
                }
                if($val['file_upload_time'] == '0000-00-00 00:00:00') $data_list[$key]['file_upload_time'] = '';
            }
        }
        return $data_list;
    }

    /**
     * 判断合同是否允许上传合同扫描件
     * @param $compact_number
     * @return bool
     */
    public function isAllowUploadFile($compact_number){
        $payed_count = $this->purchase_db
            ->select('count(1) as payed_count')
            ->from('purchase_order_pay')
            ->where('pur_number',$compact_number)
            ->get()
            ->row_array();

        $payed_count = isset($payed_count['payed_count'])?$payed_count['payed_count']:0;

        return $payed_count>0 ? false:true;
    }

    /**
    * 获取合详情
    * @param $compact_number
    * @return array
    * @author Jaxton 2019/01/08
    */
    public function get_compact_detail($compact_number){
        $this->load->helper(['status_order','status_supplier']);
        $this->load->model('supplier/Supplier_settlement_model');
        $this->load->model('finance/payment_order_pay_model');
        $this->load->model('purchase/Purchase_order_determine_model');
        //合同基础信息
        $field          = 'id,compact_number,product_money,freight,discount,process_cost,real_money,supplier_name,settlement_method,
        pay_type,settlement_ratio,is_drawback,create_user_name,create_time';
        $compact_detail = $this->purchase_db
            ->select($field)
            ->from($this->compact_table)
            ->where('compact_number', $compact_number)
            ->get()
            ->row_array();
        if(!empty($compact_detail)){
            $compact_detail['pay_type']          = getPayType($compact_detail['pay_type']);
            $compact_detail['is_drawback']       = getIsDrawbackShow($compact_detail['is_drawback']);
            $settlement_method                   = $this->payment_order_pay_model->get_settlement_method(); //结算方式
            $compact_detail['settlement_method'] = isset($settlement_method[$compact_detail['settlement_method']]) ? $settlement_method[$compact_detail['settlement_method']] : '';

            $is_allow_upload_file                   = $this->isAllowUploadFile($compact_number);
            $compact_detail['is_allow_upload_file'] = ($is_allow_upload_file) ? 'enable' : 'disable';# enable 允许上传，disable 禁止上传
        }
        //采购单信息
        $purchase_info_field='b.*,ROUND(c.product_weight * b.confirm_amount / 1000,2) as item_product_weight';
        $purchase_info=$this->purchase_db
        ->select($purchase_info_field)
        ->from('purchase_compact_items a')
        ->join('purchase_order_items b','a.purchase_number=b.purchase_number','left')
        ->join('product c','c.sku=b.sku','left')
        ->where('a.compact_number',$compact_number)
        ->get()
        ->result_array();
        if(!empty($purchase_info)){
            foreach($purchase_info as $k => $v){
                $order_cancel_list=$this->Purchase_order_determine_model->get_order_cancel_list($v['purchase_number'],$v['sku']);
                $purchase_info[$k]['cancel_number']=isset($order_cancel_list[$v['purchase_number'].'-'.$v['sku']])?$order_cancel_list[$v['purchase_number'].'-'.$v['sku']]:0; //取消数量;
                $purchase_info[$k]['purchase_price']= format_price($v['purchase_unit_price']*$v['confirm_amount']+$v['freight']-$v['discount']+$v['process_cost']);
                $purchase_info[$k]['pur_ticketed_point'] = ( $v['maintain_ticketed_point'] == 0 && empty($v['pur_ticketed_point']))?NULL:$v['pur_ticketed_point'];
            }

        }
        //付款记录
        $pay_record=$this->purchase_db
            ->select('id,js_ratio,pay_ratio,pur_number,pay_price,pay_status,payer_id,payer_time,payment_notice,requisition_number,requisition_method')
            ->from('purchase_order_pay')
            ->where('pur_number',$compact_number)
            ->order_by('id asc')
            ->get()
            ->result_array();
        $compact_first_paid_time = '';
        if(!empty($pay_record)){
            foreach($pay_record as $k => $v){
                $pay_record[$k]['pay_status']=getPayStatus($v['pay_status']);
                if(empty($v['payer_id'])){
                    $pay_record[$k]['payer_id'] ='';
                }else{
                    $pay_record[$k]['payer_id']=getUserNameById($v['payer_id']);
                }
                if($v['requisition_method']==4){//合同列表、合同详情中的调整
                    $pay_record[$k]['js_ratio']=0;
                }
                if($v['pay_status'] == PAY_PAID){
                    $compact_first_paid_time = $v['payer_time'];
                }
            }

        }
        if(!empty($pay_record)){
            foreach ($pay_record as & $item){
                $notice = json_decode($item['payment_notice'], true);
                if(is_array($notice)){
                    $notice = current($notice);
                    $item['payment_notice'] = isset($notice['note'])?$notice['note']:'';
                }
            }
        }
        $compact_detail['compact_first_paid_time'] = $compact_first_paid_time;
        //操作记录
        $operation_log=$this->purchase_db
        ->select('content_detail as content,operator,operate_time')
        ->from('operator_log')
        ->where('record_number',$compact_number)
        ->order_by('id','ASC')
        ->get()
        ->result_array();
        return [
            'compact_detail'=>[
                'value' => $compact_detail,
                'key'   => [
                    '合同编号','合同总商品金额','总运费','总优惠','实际金额','供应商','结算方式','支付方式','结算比例','是否退税','创建人','创建时间'
                ]
            ],
            'purchase_info'=>[
                'value' => $purchase_info,
                'key'   => [
                    '图片','采购单号','SKU','产品名称','开票点','采购数量','取消数量','入库数量','单价','含税单价','总重量(kg)','运费','优惠','加工费','采购金额'
                ]
            ],
            'pay_record'=>[
                'value' => $pay_record,
                'key'   => [
                    '请款单号','付款比例/金额','付款状态','付款人/付款时间','备注','操作'
                ]
            ],
            'operation_log'=>[
                'value' => $operation_log,
                'key'   => [
                    '操作人','操作时间','日志明细'
                ]
             ]
        ];


    }

    /**
     * 通过合同请款上传扫描件
     * @author harvin 2019-8-2
     * @param string $compact_code
     * @param string $compact_url
     * @param string $requisition_number
     * @return array
     */
    public function compact_file_save($compact_code,$compact_url,$requisition_number){
        $this->load->model('compact/Compact_file_model');
        //获取请款单id
        $order_pay = $this->purchase_db->select('id')
            ->where('requisition_number', $requisition_number)
            ->get('purchase_order_pay')
            ->row_array();
        if (empty($order_pay)) {
            return ['code' => false, 'msg' => '请款单不存在'];
        }
        //转化数组url
        if (empty($compact_url)) {
            return ['code' => false, 'msg' => '合同扫描文件信息为空'];
        }
        //转化数组url
        if (!is_array($compact_url)) {
            return ['code' => false, 'msg' => '合同扫描文件数据格式错误'];
        }
        //获取合同号id
        $compact = $this->purchase_db
                        ->select('id')
                        ->where('compact_number', $compact_code)
                        ->get($this->compact_table)->row_array();
        if (empty($compact)) {
            return ['code' => false, 'msg' => '合同号不存在或是错误'];
        }
        $bool = false;
        foreach ($compact_url as $row) {
            $url_arr   = explode('.', $row['file_path']);
            $file_type = end($url_arr);
            $add_data  = [
                'file_path'        => $row['file_path'],
                'file_name'        => $row['file_name'],
                'pop_id'           => isset($order_pay['id']) ? $order_pay['id'] : '',
                'pc_id'            => isset($compact['id']) ? $compact['id'] : '',
                'upload_user_id'   => getActiveUserId(),
                'upload_user_name' => getActiveUserName(),
                'upload_time'      => date('Y-m-d H:i:s'),
                'file_type'        => $file_type,
            ];
            $insert    = $this->Compact_file_model->file_insert_one($add_data);
            if($insert){
                $log_data = [
                    'record_number'  => isset($order_pay['id']) ? $order_pay['id'] : '',
                    'record_type'    => '合同扫描件上传',
                    'content'        => '合同扫描件上传',
                    'content_detail' => $row['file_path'],
                ];
                $this->load->model('reject_note_model');
                $this->reject_note_model->get_insert_log($log_data);
                $bool = true;
            }else{
                $bool = false;
                break;
            }
            unset($add_data);
            unset($log_data);
            unset($url_arr);
        }

        if (!$bool) {
            return ['code' => false, 'msg' => '保存合同扫描件失败'];
        } else {
            return ['code' => false, 'msg' => '保存成功'];
        }
    }

    /**
    * 上传合同扫描件
    */
    public function upload_compact_file($pop_id,$pc_id,$upload_file){
        $success = false;
        $message = '';

        $this->load->library('file_operation');
        $this->load->model('compact/Compact_file_model');

    	$upload_result=$this->file_operation->upload_file($upload_file,'compact','HT');
    	if($upload_result['errorCode']){//上传成功
    		//print_r($upload_result);die;
    		//$user_info=getActiveUserInfo();
    		$add_data=[
    			'file_path'=>$upload_result['file_info']['file_path'],
    			'pop_id'=>$pop_id,
    			'pc_id'=>$pc_id,
    			'upload_user_id'=>getActiveUserId(),//$user_info['user_id'],
    			'upload_user_name'=>getActiveUserName(),//$user_info['user_name'],
    			'upload_time'=>date('Y-m-d H:i:s'),
                'file_type'=>$upload_result['file_info']['file_type']
    		];
    		$this->purchase_db->trans_begin();
            try{
                $add_result=$this->Compact_file_model->file_insert_one($add_data);
                if($add_result){
                    //存操作记录
                    //插入操作记录表
                    $this->load->model('reject_note_model');
                    $log_data=[
                        'record_number'=>$pop_id,
                        'record_type'=>'合同扫描件上传',
                        'content'=>'合同扫描件上传',
                        'content_detail'=>$upload_result['file_info']['file_path']
                    ];
                    $this->reject_note_model->get_insert_log($log_data);
                }

                $this->purchase_db->trans_commit();
                $success=true;
                $message='操作成功';
            }catch(Exception $e){
                $this->purchase_db->trans_rollback();
                $message=$e->getMessage();
            }


    	}else{//上传失败
    		$message=$upload_result['errorMess'];
    	}
    	return [
    		'success'=>$success,
    		'message'=>$message
    	];
    }

    /**
     * @author Jaxton
     * @desc 上传文件
     * @return array
     */
    public function upload_file($upload_file)
    {
    	$result_arr=[
    		'errorCode'=>false,
    		'errorMess'=>''
    	];

        if (!empty ($upload_file['name'])) {

            $file_type_arr = [
                'zip', 'rar', 'pdf', 'doc', 'docx', 'doc', 'pptx', 'ppt', 'xlsx','csv' ,'xls', 'gz', 'png', 'gif', 'jpg', 'jpeg'
            ];


            //单个附件处理
            $tmp_file  = $upload_file ['tmp_name'];
            $file_name = $upload_file ['name'];

            $file_types = explode(".", $file_name);
            $file_type  = $file_types [count($file_types) - 1];

            if (!in_array(strtolower($file_type), $file_type_arr)) {
                $result_arr['errorMess'] = '文件格式错误,提交失败！';

                return $result_arr;
            }
            /*设置上传路径*/
            $savePath = APP_UPLOAD . 'compact/';
            if (!file_exists($savePath)) {
                mkdir($savePath, 0755, true);
            }
            /*是否上传成功*/
            $new_file_name='HT_'.date('ymdHis').rand(10000,99999).'.'.$file_type;
            $upload_result=copy($tmp_file, $savePath . $new_file_name);
            if (!$upload_result) {
                $result_arr['errorMess'] = '上传失败！';

                return $result_arr;
            }else{
            	$result_arr['errorCode'] = true;
            	$result_arr['errorMess'] = '/compact/'.$new_file_name;
            	return $result_arr;
            }


        }

    }

    /**
    * 用合同号获取合同数据
    * @param $compact_number
    * @return array
    * @author Jaxton 2019/01/30
    */
    public function get_row_by_number($compact_number){
        return $row=$this->purchase_db->select('*')
        ->from($this->compact_table)
        ->where('compact_number',$compact_number)
        ->get()
        ->row_array();
    }

    /**
    * 用合同号获取sku数据
    * @param $compact_number
    * @return array
    * @author Jaxton 2019/01/30
    */
    public function get_sku_data_by_number($compact_number){
        $sku_data = $this->purchase_db->select('d.purchase_order_status,a.sku_info,d.warehouse_code,e.warehouse_name,b.purchase_number,
        b.sku,b.product_name,b.purchase_amount,b.confirm_amount,b.purchase_unit_price,b.product_img_url,b.pur_ticketed_point,
        c.export_cname as pro_export_cname,c.declare_unit as pro_declare_unit,b.coupon_rate,b.export_cname,b.declare_unit,
        b.invoiced_qty')
            ->from('purchase_compact_items a')
            ->join('purchase_order d', 'd.purchase_number=a.purchase_number', 'left')
            ->join('purchase_order_items b', 'a.purchase_number=b.purchase_number')
            ->join('pur_product c', 'b.sku = c.sku', 'left')
            ->join('warehouse e','e.warehouse_code=d.warehouse_code')
            ->where('a.compact_number', $compact_number)
            ->get()
            ->result_array();
        return $sku_data;
    }

    /**
    * 获取打印合同数据
    * @param $compact_number
    * @return array
    * @author Jaxton 2019/01/30
    */
    public function get_print_compact_data($compact_number){
        $row=$this->get_row_by_number($compact_number);

        $this->load->helper('status_finance');
        $this->load->model('compact/Compact_model');
        $this->load->model('purchase/Purchase_order_determine_model');
         $this->load->model('purchase/purchase_order_cancel_model');
        if(!empty($row)){
            $compact_data=[
                'compact_number' => $row['compact_number'],
                'a_company_name' => $row['a_company_name'],
                'a_address' => $row['a_address'],
                'a_linkman' => $row['a_linkman'],
                'a_phone' => $row['a_phone'],
                'a_email' => $row['a_email'],
                'b_company_name' => $row['b_company_name'],
                'b_corporate' => $row['b_corporate'],
                'b_address' => $row['b_address'],
                'b_linkman' => $row['b_linkman'],
                'b_phone' => $row['b_phone'],
                'b_email' => $row['b_email'],
                'is_freight' => $row['is_freight'],//运费支付
                'ship_method' => $row['ship_method'],//送货方式
                'receive_address' => $row['receive_address'],
                'payment_explain' => json_decode($row['payment_explain']),
                'remit_information' => $row['remit_information'],
                'delivery_date' => $row['delivery_date'],
                'is_drawback' => $row['is_drawback'],
                'cooperate_require' => json_decode($row['cooperate_require']),
                'settlement_ratio' => explode("+",$row['settlement_ratio']),
                'product_money' => $row['product_money'],
                'freight' => $row['freight'],
                'discount' => $row['discount'],
                'process_cost' => $row['process_cost'],
                'total_price' => $row['real_money'],
            ];
            if(empty($compact_data['remit_information'])){
                $this->load->model('supplier/Supplier_model');
                $supplier_pay_info = $this->Supplier_model->get_supplier_remit_information($row['supplier_code'], $row['is_drawback'], $row['source']);//支付方式:1.支付宝,2.对公支付，3.对私支付
                $compact_data['remit_information'] = $supplier_pay_info;
            }
            if(strlen($row['delivery_date']) > 10){
                $compact_data['contract_require_attach'] = '货物损毁灭失的风险在货物到达甲方仓库前由乙方承担，到达甲方仓库后由甲方承担。';// 附加合约要求
            }else{
                $compact_data['contract_require_attach'] = '';
            }


            $compact = $this->Compact_model->get_compact_one($compact_number);
            $cancel_total_product_money=$cancel_total_real_money=0;
            foreach($compact['items_list'] as $compact_item){
                   $cancel_info = $this->purchase_order_cancel_model->get_cancel_total_by_sku($compact_item['purchase_number']);
                   if($cancel_info){
                       $cancel_total_product_money += isset($cancel_info['cancel_product_money'])?$cancel_info['cancel_product_money']:0;// 已取消商品金额
                       $cancel_total_real_money    += isset($cancel_info['cancel_total_price'])?$cancel_info['cancel_total_price']:0;// 已取消请款金额
                   }
               }

            $compact_data['cancel_total'] = $cancel_total_real_money;

            $price_list     = compactPaymentPlan($row['settlement_ratio'],$row['product_money'],$row['freight'] + $row['process_cost'],$row['discount'],$row['is_drawback']);
            $price_list     = calculateRealPayMoney($price_list,$cancel_total_product_money);// 合同 取消未到货取消金额从尾款里面扣除

            //获取sku数据
            $total_price=0;
            $total_invoiced_amount = 0;
            $sku_data = $this->get_sku_data_by_number($compact_number);
            if(!empty($sku_data)){

                foreach($sku_data as $key => $val){
                    $sku_info = $val['sku_info'];
                    if($sku_info) $sku_info = json_decode($sku_info,true);
                    //获取已取消数量和总金额 （取消的）商品金额、运费、优惠额、实际金额
                    $cancel_date  = $this->purchase_order_cancel_model->get_cancel_total_by_sku($val['purchase_number'],$val['sku']); //取消总金额

                    $cancel_total_price = isset($cancel_date['cancel_total_price'])?$cancel_date['cancel_total_price']:0; //取消总额
                    $cancel_ctq  = isset($cancel_date['cancel_ctq'])?$cancel_date['cancel_ctq']:0;//取消数量

                    //开票品名、开票单位
                    if(in_array($val['purchase_order_status'], [PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                        PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                        PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,
                        PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER])){
                        //未变成等待到货
                        $export_cname = $val['pro_export_cname'];
                        $declare_unit = $val['pro_declare_unit'];
                    }else{
                        //等待待货之后,取采购单表
                        $export_cname = $val['export_cname'];
                        $declare_unit = $val['declare_unit'];
                    }

                    $invoiced_amount = bcmul( $val['invoiced_qty'],$val['purchase_unit_price'],3);// 已开票数量*含税单价
                    $total_invoiced_amount = bcadd($total_invoiced_amount,$invoiced_amount,3);

                    $format_sku_data[$val['purchase_number']][]=[
                        'purchase_number' => $val['purchase_number'],
                        'sku' => $val['sku'],
                        'product_name' => $val['product_name'],
                        'purchase_amount' => $val['purchase_amount'],
                        'confirm_amount' => $val['confirm_amount'],
                        'cancel_ctq' => $cancel_ctq, //已取消数量,
                        'purchase_unit_price' => $val['purchase_unit_price'],
                        'sku_total_price' => format_price($val['confirm_amount']*$val['purchase_unit_price']),
                        'product_img_url' => $val['product_img_url'],
                        'sku_spec' => isset($sku_info[$val['sku']]['sku_spec'])?$sku_info[$val['sku']]['sku_spec']:'',
                        'cancel_total_price' => format_price($cancel_total_price),
                        'export_cname' => $export_cname,//开票品名
                        'declare_unit' => $declare_unit,//开票单位
                        'pur_ticketed_point' => $val['pur_ticketed_point'],//开票点
                        'coupon_rate' => $val['coupon_rate'],//票面税率
                        'warehouse_code' => $val['warehouse_code'],
                        'warehouse_name' => $val['warehouse_name'],
                        ];
                    unset($cancel_date);
                }

                return [
                    'compact_data' => $compact_data,
                    'sku_data' => $format_sku_data,
                    'price_list' => $price_list,
                    'cancel_total_real_money'=>$cancel_total_product_money,//更换取消商品金额
                    'total_invoiced_amount' => $total_invoiced_amount,//已开票金额合计
                ];

            }else{
                return false;
            }

        }else{
            return false;
        }
    }

    public function get_compact_tmp($data,$uid){
        $print_compact = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'print_compact');
        $url           = $print_compact."?uid=".$uid."&data=".$data;
        $html = file_get_contents($url);
        return $html;
    }

    /**
     * 保存上传合同扫描件路径
     * @author Jaxton Jolon
     * @param $pop_id
     * @param $pc_id
     * @param $file_info
     * @return array
     */
    public function upload_compact_file_conserve($pop_id, $pc_id, $file_info){
        $success = false;
        $message = '';

        $this->load->model('compact/Compact_file_model');
        try{
            $add_data = [
                'file_path'        => $file_info['file_path'],
                'file_name'        => $file_info['file_name'],
                'pop_id'           => $pop_id,
                'pc_id'            => $pc_id,
                'upload_user_id'   => getActiveUserId(),
                'upload_user_name' => getActiveUserName(),
                'upload_time'      => date('Y-m-d H:i:s'),
                'file_type'        => $file_info['file_type']
            ];
            $this->purchase_db->trans_begin();
            $add_result = $this->Compact_file_model->file_insert_one($add_data);
            if($add_result){
                //存操作记录
                //插入操作记录表
                $this->load->model('reject_note_model');
                $log_data = [
                    'record_number'  => $pop_id,
                    'record_type'    => '合同扫描件上传',
                    'content'        => '合同扫描件上传',
                    'content_detail' => $file_info['file_path']
                ];
                $this->reject_note_model->get_insert_log($log_data);
            }

            $this->purchase_db->trans_commit();
            $success = true;
            $message = '操作成功';

        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $message = $e->getMessage();
        }

        return [
            'success' => $success,
            'message' => $message
        ];
    }

    /**
     * 保存上传合同扫描件路径
     * @author Jaxton Jolon
     * @param $compact_number
     * @param $file_name
     * @param $file_path
     * @return array
     */
    public function upload_compact_original_scan_file($compact_number,$file_name,$file_path){
        $success = false;

        $this->load->model('compact/Compact_file_model');
        $this->load->model('compact/Compact_model');
        try{
            $compactInfo = $this->Compact_model->get_compact_one($compact_number,false);
            if(empty($compactInfo)){
                throw new Exception('合同不存在');
            }

            $file_type   = explode('.', $file_path);
            $file_type   = end($file_type);

            $add_data = [
                'file_path'        => $file_path,
                'file_name'        => $file_name,
                'pc_id'            => $compactInfo['id'],
                'pop_id'           => '-1',// -1 表示原始合同扫描件，>0的为合同的请款单的合同扫描件
                'upload_user_id'   => getActiveUserId(),
                'upload_user_name' => getActiveUserName(),
                'upload_time'      => date('Y-m-d H:i:s'),
                'file_type'        => $file_type
            ];
            $this->purchase_db->trans_begin();

            $this->purchase_db->where('pc_id',$compactInfo['id'])->where('pop_id',-1)->delete('purchase_compact_file');// 删除历史
            $add_result = $this->Compact_file_model->file_insert_one($add_data);
            if($add_result){
                $this->purchase_db->where('compact_number',$compact_number)->update($this->compact_table,['is_file_uploaded' => SRM_COMPACT_ACCESS_STATUS,'file_upload_time' => date('Y-m-d H:i:s')]);// 2.标记为已上传
                $this->purchase_db->where('compact_num',$compact_number)->update('supplier_web_info',['compact_audit_status' => SRM_COMPACT_ACCESS_STATUS,'img_url' => $file_path,'file_name' => $file_name,'file_type' => $file_type,'update_time' => date('Y-m-d H:i:s')]);
                //存操作记录
                //插入操作记录表
                $this->load->model('reject_note_model');
                $log_data = [
                    'record_number'  => $compactInfo['id'],
                    'record_type'    => '合同扫描件上传',
                    'content'        => '合同扫描件上传',
                    'content_detail' => $file_path
                ];
                $this->reject_note_model->get_insert_log($log_data);
            }

            $this->purchase_db->trans_commit();
            $success = true;
            $message = '操作成功';

        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $message = $e->getMessage();
        }

        return [
            'success' => $success,
            'message' => $message
        ];
    }

    /**
    * 获取付款申请书数据
    * @param $compact_number
    * @param $requisition_number
    * @return array
    * @author Jaxton 2019/02/21
    */
    public function get_pay_requisition($compact_number,$requisition_number){
        $data=[];
        $row=$this->purchase_db->select('*')->from('purchase_pay_requisition')
        ->where(['compact_number'=>$compact_number,'requisition_number'=>$requisition_number])
        ->get()->row_array();

        if(!empty($row)){
            $row['payment_reason'] = make_semiangle($row['payment_reason']);
            $auditor = $this->get_pay_receipt_auditor($requisition_number);
            $data=array_merge($row,$auditor);
            $success=1;
        }else{
            $success=0;
        }
        return [
            'success'=>$success,
            'data'=>$data
        ];
    }

    /**
    * 获取付款回单
    * @param $compact_number
    * @param $requisition_number
    * @return array
    * @author Jaxton 2019/02/21
    */
    public function get_pay_receipt($compact_number,$requisition_number){
        $data=[];
        $row=$this->purchase_db->select('images,voucher_address')->from('purchase_order_pay')
        ->where(['pur_number'=>$compact_number,'requisition_number'=>$requisition_number])
        ->get()->row_array();
        if(!empty($row['images']) or !empty($row['voucher_address'])){
            $data = $row['images'].';'.((string)$row['voucher_address']);
            $data = explode(';',trim($data,';'));
            $data = current($data);
            $success=1;
        }else{
            $success=0;
        }
        return [
            'success'=>$success,
            'data'=>$data
        ];
    }
    /**
     * 获取申请单的审核人
     * @param $compact_number
     * @param $requisition_number
     * @return array
     *  请款人、采购经理审核人、供应链总监审核人、财务审批人、财务主管审核人、财务经理审核人、财务总监审核人、总经办审核人
     */
    public function get_pay_receipt_auditor($requisition_number){
        $row_user = $this->purchase_db->select('applicant,auditor,waiting_id,approver,financial_supervisor_id,financial_manager_id,financial_officer_id,general_manager_id,payer_name,payer_id')->from('purchase_order_pay')
            ->where(['requisition_number' => $requisition_number])
            ->where_in('pay_status',array(PAY_WAITING_FINANCE_PAID,PART_PAID,PAY_PAID,PAY_UFXFUIOU_REVIEW,PAY_UFXFUIOU_BAOFOPAY))
            ->get()->row_array();
        $auditor = array();
        if (!empty($row_user)) {
            $this->load->model('user/Purchase_user_model');
            $applicant = '';
            $approver  ='';
            $payer_name = '';

            $error = $this->get_personal_signature_picture('error');
            //申请人
            $applicant_id_name = $this->Purchase_user_model->get_user_info($row_user['applicant'], null,'staff_name');
            if($applicant_id_name){
                $applicant =  $applicant_id_name;
            }
            //采购经理审核人
            $auditor_id_name = $this->Purchase_user_model->get_user_info($row_user['auditor'], null,'staff_code');
            $auditor_name =  $this->get_personal_signature_picture($auditor_id_name);
            if(empty($auditor_name))  $auditor_name = $error;

            //供应链审核
            $waiting_id_name = $this->Purchase_user_model->get_user_info($row_user['waiting_id'], null,'staff_code');
            $waiting_name = $this->get_personal_signature_picture($waiting_id_name);
            if(empty($waiting_name))  $waiting_name = $error;
            //财务审核
            $approver_id_name = $this->Purchase_user_model->get_user_info($row_user['approver'], null,'staff_name');
            if($approver_id_name){
                $approver =  $approver_id_name;
            }
            //财务主管
            $financial_supervisor_id = $this->Purchase_user_model->get_user_info($row_user['financial_supervisor_id'], null,'staff_code');
            $financial_supervisor =  $this->get_personal_signature_picture($financial_supervisor_id);
            if(empty($financial_supervisor))  $financial_supervisor = $error;

            //财务经理
            $financial_manager_id = $this->Purchase_user_model->get_user_info($row_user['financial_manager_id'], null,'staff_code');
            $financial_manager = $this->get_personal_signature_picture($financial_manager_id);
            if(empty($financial_manager))  $financial_manager = $error;

            //财务总监
            $financial_officer_id = $this->Purchase_user_model->get_user_info($row_user['financial_officer_id'], null,'staff_code');
            $financial_officer =  $this->get_personal_signature_picture($financial_officer_id);
            if(empty($financial_officer))  $financial_officer = $error;

            //总经办
            $general_manager_name_id = $this->Purchase_user_model->get_user_info($row_user['general_manager_id'], null,'staff_code');
            $general_manager_name = $this->get_personal_signature_picture($general_manager_name_id);
            if(empty($general_manager_name))  $general_manager_name = $error;

            //付款人
            $payer_name_id = $this->Purchase_user_model->get_user_info($row_user['payer_id'], null,'staff_name');
            if($payer_name_id){
                $payer_name =  $payer_name_id;
            }
            $auditor = array(
                'applicant_name' => $applicant,
                'soa_name' => '党小婷',
                'auditor_name' => $auditor_name,//采购经理
                'waiting_name' => $waiting_name,//供应链总监
                'approver_name' => $approver,//财务审核人
                'financial_supervisor_name' => $financial_supervisor,//财务主管
                'financial_manager_name' => $financial_manager,//财务经理
                'financial_officer_name' => $financial_officer,//财务总监
                'general_manager_name' => $general_manager_name,//总经办
                'payer_name' => $payer_name,
            );
        }else{
            //为空的时候
            $error =$this->get_personal_signature_picture('error');
            $auditor = array(
                'applicant_name' =>'',
                'soa_name' =>'',
                'auditor_name' => $error,
                'waiting_name' =>$error,
                'approver_name' =>'',
                'financial_supervisor_name' =>$error,
                'financial_manager_name' =>$error,
                'financial_officer_name' =>$error,
                'general_manager_name' =>$error,
                'payer_name' =>'');
        }
        return $auditor;
    }

    /**
     * 根据合同单号获取合同的付款申请书与合同扫描件
     * @param $compact_number
     * @return array
     */
    public function get_compact_detail_file($compact_number,$download_type=''){
        //获取付款状态为待财务放款  合同单的请款单号与合同单号
        $pay_status = PAY_WAITING_FINANCE_PAID;
        if(!empty($download_type)){
            $pay_status = PAY_PAID;
        }
        $pay_list = $this->purchase_db->select('ppo.pur_number,ppo.requisition_number,ppc.id pc_id,ppo.id pop_id')
            ->from('purchase_order_pay ppo')
            ->join('purchase_compact ppc','ppc.compact_number=ppo.pur_number','LEFT')
            ->where_in('ppo.pur_number',$compact_number)
            ->where(['ppo.pay_status'=>$pay_status,'ppo.source'=>1])
            ->get()->result_array();
        return $pay_list;
    }

    /**
     * @param $compact_number
     * @param $requisition_number
     * @return array
     */
    public function get_pay_requisition_file($compact_number,$requisition_number){
        $data=[];
        $row=$this->purchase_db->select('*')->from('purchase_pay_requisition')
            ->where(['compact_number'=>$compact_number,'requisition_number'=>$requisition_number])
            ->get()->row_array();
        if(!empty($row)){
            $auditor = $this->get_pay_receipt_auditor($requisition_number);
            $data=array_merge($row,$auditor);
        }
        return $data;
    }


    /**
     * 获取用户签名显示的图片
     *      图片生成规则：在白纸上手写签名->扫描签名（微信小程序，搜索全能扫描王）->生成的电子文件导出来
     *      IT处理图片规格（png，像素：75X29）上传到JAVA DFS 服务器，生成图片文件地址
     * @param string $user_number 用户工号
     * @return string
     */
    public function get_personal_signature_picture($user_number=''){
        $patch = '';
        $patch_url=$this->purchase_db->select('patch_url')
            ->from('personal_signature_picture')
            ->where(['user_number'=>$user_number])
            ->get()->row_array();
        if(!empty($patch_url)){
            $patch = $patch_url['patch_url'];
        }else{
           $this->get_personal_signature_picture('error');
        }
        return $patch;
    }
}