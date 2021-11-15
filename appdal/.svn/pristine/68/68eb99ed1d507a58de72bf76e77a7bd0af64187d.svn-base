<?php

/**
 * Created by PhpStorm.
 * 请款单控制器
 * User: Jolon
 * Date: 2019/01/10 0027 11:17
 */
class Purchase_order_pay extends MY_Controller {

    public function __construct() {
        parent::__construct();

        $this->load->model('purchase_order_pay_model'); // 请款单
        $this->load->model('purchase/Purchase_order_model'); //
        $this->load->model('Supplier_payment_info_model','Supplier_payment_info_model');
        $this->load->helper('status_supplier');
    }
    /**
     * 搜索列
     * @author harvin
     * http://www.caigou.com/finance/purchase_order_pay/search
     * **/ 
   public function search(){
        $data_list['applicant'] = get_buyer_name(); //申请人
        $data_list['pay_status'] = getPayStatus(); //付款状态
        $data_list['pay_type'] = getPayType(); //支付方式
        $data_list['settlement_method'] = $this->payment_order_pay_model->get_settlement_method(); //结算方式
        $data_list['is_cross_border'] = getCrossBorder(); //跨境宝供应商
       $this->success_json($data_list);
   }

   /**
     * 请款单列列表
     * @author harvin 2019-1-12
     * http://www.caigou.com/finance/purchase_order_pay/payment_list
     * * */
    public function payment_list() {
        $params = [
            'supplier_code' => $this->input->get_post('supplier_code'), // 供应商
            'pay_status' => $this->input->get_post('pay_status'), // 付款状态
            'requisition_number' => $this->input->get_post('requisition_number'), // 申请款单号
            'applicant' => $this->input->get_post('applicant'), // 申请人
            'pay_type' => $this->input->get_post('pay_type'), // 支付方式
            'pay_category' => $this->input->get_post('pay_category'), // 请款类型
            'settlement_method' => $this->input->get_post('settlement_method'), // 结算方式
            'is_cross_border' => $this->input->get_post('is_cross_border'), // 跨境宝供应商
            'create_time_start' => $this->input->get_post('create_time_start'), // 创建时间-开始
            'create_time_end' => $this->input->get_post('create_time_end'), // 创建时间-结束
            'pay_notice'=>$this->input->get_post('pay_notice'),//付款提醒状态
            'need_pay_time_start'=>$this->input->get_post('need_pay_time_start'),//应付款开始查询时间
            'need_pay_time_end'=>$this->input->get_post('need_pay_time_end'),//应付款结束查询时间
            'pur_number'=>$this->input->get_post('pur_number'),//合同号或订单号
            'statement_number'=>$this->input->get_post('statement_number'),//对账单号
            'purchase_source'=> $this->input->get_post('purchase_source'),//采购来源
            'purchase_type'=> $this->input->get_post('purchase_type'),//业务线
            'pur_tran_num' =>  $this->input->get_post('pur_tran_num'),//商户订单号
            'pay_time_start'=>$this->input->get_post('pay_time_start'),//付款开始查询时间
            'pay_time_end'=>$this->input->get_post('pay_time_end'),//付款结束查询时间
            'is_statement_pay'=>$this->input->get_post('is_statement_pay'),//是否对账单请款
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
            'is_auto'                   => $this->input->get_post('is_auto')
        ];

        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }



        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0)
            $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->purchase_order_pay_model->get_payment_list($params, $offsets, $limit,[],$page);
        $this->success_json_format($data_list);
    }

    /**
     * 审核通过及驳回
     * @author harvin 2019-1-12
     * http://www.cg.com/finance/purchase_order_pay/payment_examine
     * * */
    public function payment_examine() {
       
        $ids = $this->input->post_get('ids'); //勾选数据
        $type = $this->input->post_get('type'); //1 是审核通过  2是审核驳回
        $status = $this->input->post_get('status'); //20采购经理审核  25 供应链总监审核
        $review_notice = $this->input->post_get('review_notice'); //审核备注
        $skip_invoice_status = $this->input->get_post('skip_invoice_status');// 确认继续(跳过验证开票状态)


        if (empty($ids)) {
            $this->error_json('请勾选数据');
        }
        
        if (empty($review_notice) &&  $type=='2') {
            $this->error_json('请填写驳回备注');
        }
         
        $id = explode(',', $ids);
        //判断请款单的来源
       $temp= $this->purchase_order_pay_model->payment_order_pay_source($id);
       if(!empty($temp)){
           $error= implode(',', $temp);
           $this->error_json('请款单号'.$error.'不是合同单不能操作');
       }



       //增加判断请款状态
        $pay_status=$this->purchase_order_pay_model->pay_audit_lave($id,$status);
        if(!empty($pay_status)){
            $error= implode(',', $pay_status);
            $this->error_json('请款单号'.$error);
        }

        if ($type == '1' && in_array($status,[PAY_WAITING_SOA_REVIEW,PAY_WAITING_MANAGER_REVIEW,PAY_WAITING_MANAGER_SUPPLY]) && empty($skip_invoice_status)){//审核通过,待采购经理审核,待供应链总监审核,

            $this->load->model('Purchase_invoice_list_model','',false,'purchase');
            $this->load->model('Compact_model','',false,'finance');
            $compact_number_list = $this->purchase_order_pay_model->get_compact_number_by_id($id);//查询是尾款的

            foreach ($compact_number_list as $item){
                if (!$this->Purchase_invoice_list_model->check_compact_invoice_status($item['compact_number'])){
                    $this->success_json(['is_confirm'=>1,'confirm_msg'=>sprintf('合同单:%s,存在报关票据未开完,请问是否继续申请?',$item['compact_number'])]);
                }
            }
        }
        $bool = $this->purchase_order_pay_model->payment_examine_save($id, $review_notice, $type,$status);
        if($type==1){
            if ($bool['bool']) {
                $this->success_json([],null, '审核成功');
            } else {
                $this->error_json($bool['msg'].',审核失败');
           }  
        }else{
            if ($bool['bool']) {
                $this->success_json([],null, '驳回成功');
           } else {
                $this->error_json($bool['msg'].',驳回失败');
           }   
        }
      
    }

    /**
     * 获取请款明细
     * @author harvin 2019-1-12
     * http://www.caigou.com/finance/purchase_order_pay/payment_info
     */
    public function payment_info() {
        $requisition_number = $this->input->get_post('requisition_number'); //请款单号
        if (empty($requisition_number)) {
            $this->error_json('请款单号不存在');
        }
        $purchase_number = $this->purchase_order_pay_model->get_payment_info($requisition_number);
        if (empty($purchase_number)) {
            $this->error_json('采购单号不存在');
        }
        $purchase_number= implode(' ', $purchase_number);
        $result=[];
        $this->load->model('purchase/purchase_order_list_model');
        $data_list = $this->purchase_order_list_model->new_get_list(['purchase_number'=>$purchase_number], 0, MAX_PAGE_SIZE);
        if(!isset($data_list['value']) && empty($data_list['value'])){
              $this->error_json('采购单号不存在');
        } else{
               foreach ($data_list['value'] as $key => $value) {
                $data_list['value'][$key]['purchase_order_status']= getPurchaseStatus($value['purchase_order_status']);
                $data_list['value'][$key]['source']= getPurchaseSource($value['source']);
                $data_list['value'][$key]['pay_status']= getPayType($value['pay_status']); 
                if( $value['maintain_ticketed_point'] == 0 && empty($value['pur_ticketed_point']))
                {
                    $data_list['value'][$key]['pur_ticketed_point'] = NULL;
                }
                //获取对账单号
                $data_list['value'][$key]['statement_number']= '';
              }
            $this->load->library('Search_header_data');
            $data_list['key'] = $this->search_header_data->table_columns();
            $this->success_json($data_list,null, '获取数据成功');
        }  
        
    }
    /**
     * 获取合同单审核明细
     * @author harvin
     */
    public function payment_examine_info() {
        $this->load->model('compact/Compact_list_model');
        $requisition_number = $this->input->get_post('requisition_number'); //请款单号
        if (empty($requisition_number)) {
            $this->error_json('请款单号不存在');
        }
        $purchase_number = $this->purchase_order_pay_model->get_payment_info($requisition_number);
        if (empty($purchase_number)) {
            $this->error_json('采购单号不存在');
        }
        $purchase_number= implode(' ', $purchase_number);
        $result=[];
         $this->load->model('purchase/purchase_order_list_model');
         $data_list = $this->purchase_order_list_model->new_get_list(['purchase_number'=>$purchase_number], 0, MAX_PAGE_SIZE);
         if(!isset($data_list['value']) && empty($data_list['value'])){
              $this->error_json('采购单号不存在');
        } else{
               foreach ($data_list['value'] as $key => $value) {
                $data_list['value'][$key]['purchase_order_status']= getPurchaseStatus($value['purchase_order_status']);
                $data_list['value'][$key]['source']= getPurchaseSource($value['source']);
                $data_list['value'][$key]['pay_status']= getPayType($value['pay_status']); 
              }
             
             $source = $this->purchase_order_pay_model->order_pay_source($requisition_number); 
             if(isset($source['source']) && $source['source']==SOURCE_COMPACT_ORDER){
                  $result=$this->Compact_list_model->get_compact_detail($source['pur_number']);
                  $result['compact_url']= explode(',', $source['compact_url']);
                  $result['source']= $source['source'];
                  $result['freight_desc']= $source['freight_desc'];
                  $result['create_notice']= $source['create_notice'];

                  $result['pay_main_info']['product_money'] = $source['product_money'];
                  $result['pay_main_info']['freight'] = $source['freight'];
                  $result['pay_main_info']['discount'] = $source['discount'];
                  $result['pay_main_info']['process_cost'] = $source['process_cost'];
                  $result['pay_main_info']['pay_price'] = $source['pay_price'];
             }
              $result['order_info']=$data_list;
            $this->success_json($result,null, '获取数据成功');
        }  
        
    }
    
    /**
     * 请款单导出
     * @author harvin 2019-1-12
     * http://www.caigou.com/finance/purchase_order_pay/payment_export
     */
    public function payment_export() {
        set_time_limit(0);
        $this->load->helper('export_csv');
        $ids = $this->input->get_post('ids'); //勾选数据
        $params = [
            'supplier_code' => $this->input->get_post('supplier_code'), // 供应商
            'pay_status' => $this->input->get_post('pay_status'), // 付款状态
            'requisition_number' => $this->input->get_post('requisition_number'), // 申请款单号
            'applicant' => $this->input->get_post('applicant'), // 申请人
            'pay_type' => $this->input->get_post('pay_type'), // 支付方式
            'pay_category' => $this->input->get_post('pay_category'), // 请款类型
            'settlement_method' => $this->input->get_post('settlement_method'), // 结算方式
            'is_cross_border' => $this->input->get_post('is_cross_border'), // 跨境宝供应商
            'create_time_start' => $this->input->get_post('create_time_start'), // 创建时间-开始
            'create_time_end' => $this->input->get_post('create_time_end'), // 创建时间-结束
            'pay_notice'=>$this->input->get_post('pay_notice'),//付款提醒状态
            'need_pay_time_start'=>$this->input->get_post('need_pay_time_start'),//应付款开始查询时间
            'need_pay_time_end'=>$this->input->get_post('need_pay_time_end'),//应付款结束查询时间
            'pur_number'=>$this->input->get_post('pur_number'),//合同号或订单号
            'statement_number'=>$this->input->get_post('statement_number'),//对账单号
            'pur_tran_num' =>  $this->input->get_post('pur_tran_num'),//商户订单号
            'purchase_source' =>  $this->input->get_post('purchase_source'),//商户订单号
            'export_csv' => 1,
            'is_statement_pay'=>$this->input->get_post('is_statement_pay'),//是否对账单请款

            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
        ];

        if(empty($ids)){
             $id = [];   
        }else{
             $id = explode(',', $ids);   
        }
        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }


        $data_list = $this->purchase_order_pay_model->get_payment_list($params, 0, 2000, $id);
        $heads = ['请款单号', '合同号', '采购单号', '付款状态', '采购主体',
            '采购来源', '申请人', '申请时间',
            '审核 /(驳回）人', '审核 /(驳回)时间',
            '审核人备注', '供应商', '运费', '优惠额',
            '加工费', '申请金额', '已付金额','运费说明',
            '币种', '付款人', '付款时间',  '支付方式', '结算方式', '运费支付', '对账单号', '备注','付款回单编号','付款流水号',
            '是否对账单请款','所属小组'];
        $datalist[] = $heads;
        $template_file = 'finance_supply_'.date('YmdHis').mt_rand(1000,9999).'.csv';
        $webfront_path = dirname(dirname(APPPATH));
        $product_file = $webfront_path.'/webfront/download_csv/'.$template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        $fp = fopen($product_file, "a");
        foreach ($heads as &$v){
            $v = iconv('UTF-8','GBK//IGNORE',$v);//中文转码
        }
        fputcsv($fp,$heads);
        if(empty($data_list['values'])) {
            $this->success_json(CG_SYSTEM_WEB_FRONT_IP.'/download_csv/'.$template_file);
        }

        if($data_list['page_data']['total'] > 0){
            $counts = $data_list['page_data']['total'];
            $per_page = 2000;
            $total_page = ceil($counts/$per_page);
            for($i = 1;$i<= $total_page;$i++){
                $start = ($i - 1) * $per_page;
                $data_list = $this->purchase_order_pay_model->get_payment_list($params, $start, $per_page, $id);
                if(!empty($data_list['values'])){
                    foreach ($data_list['values'] as $key => $value) {
                        foreach ($value as &$m){
                            if(preg_match("/[\x7f-\xff]/", $m)) $m = iconv('UTF-8','GBK//IGNORE',$m);//中文转码
                            if(is_numeric($m) && strlen($m) > 9) $m = $m."\t";//避免大数字在csv里以科学计数法显示
                        }

                        $tmp = [
                            $value['requisition_number'],
                            $value['csv_compact_number'],
                            $value['csv_purchase_number'],
                            $value['pay_status'],
                            $value['is_drawback'],
                            $value['source'],
                            $value['applicant'], //$value['applicant'],申请人ID
                            $value['application_time'],
                            $value['auditor'], // $value['auditor'],id  查询数据库
                            $value['review_time'],
                            $value['processing_notice'],
                            $value['supplier_name'],
                            $value['freight_total'],
                            $value['discount_total'],
                            $value['process_cost_total'],
                            $value['pay_price'],
                            $value['real_pay_price'],
                            $value['freight_desc'],
                            CURRENCY, //币种
                            $value['payer_name'],
                            $value['payer_time'],
                            $value['pay_type'],
                            $value['settlement_method'], //结算方式
                            $value['is_freight'],
                            $value['statement_number'],
                            $value['note'],
                            $value['pur_tran_num'],
                            $value['trans_orderid'],
                            $value['is_statement_pay'],// 是否对账单请款
                            $value['groupName']
                        ];
                        fputcsv($fp,$tmp);
                    }
                }
                unset($data_list);
                ob_flush();
                flush();
                usleep(100);
            }
            fclose($fp);
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url= $down_host.'/download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }

    }

    /**
     * 请款单状态下拉列表
     * @author Jolon
     */
    public function get_status_list() {
        $status_type = $this->input->get_post('type');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');

        $status = 1;
        switch (strtolower($status_type)) {
            case 'pay_status':
                $status_type_name = '请款单状态';
                $data_list = getPayStatus();
                break;
            case 'pay_category':
                $status_type_name = '请款类型';
                $data_list = getPayCategory();
                break;

            default :
                $status = 0;
                $status_type_name = '未知的状态类型';
                $data_list = null;
        }

        if ($status) {
            $this->success_json($data_list);
        } else {
            $this->error_json($status_type_name);
        }
    }


    /**
     * 网采单-创建请款单-数据预览
     * @author Jolon
     * @desc array $_POST['purchase_numbers'] 要请款采购单号
     */
    public function network_pay_order_preview() {
        $purchase_number_list = $this->input->post('purchase_numbers'); // 采购单
        $is_force_submit      = $this->input->post('is_force_submit'); // 是否强制提交,1.是,其他.否
        if (empty($purchase_number_list) or ! is_array($purchase_number_list)) {
            $this->error_json('参数【purchase_numbers】缺失或不是数组');
        }
        $purchase_number_list = array_unique($purchase_number_list);

        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('purchase_order_model', '', false, 'purchase'); // 请款单 SKU 明细
        $this->load->model('purchase/Purchase_order_determine_model');  
        $this->load->model('purchase/Payment_order_pay_model');
        $this->load->model('warehouse/Warehouse_storage_record_model');
        $this->load->model('finance/purchase_order_pay_type_model');
        $this->load->model('statement/Charge_against_surplus_model');
        $this->load->model('supplier/Supplier_settlement_model');
        $this->load->model('purchase/Purchase_order_transport_model');

        // 获取采购单数据 并验证
        $purchase_order_list = [];
        $purchase_order_arr = [];
        $warning_list = [];
        foreach ($purchase_number_list as $purchase_number) {
            $purchase_order = $this->purchase_order_model->get_one_with_demand_number($purchase_number);
            if (empty($purchase_order)) {
                $this->error_json('采购单号：' . $purchase_number . '未找到');
            }
            if (!in_array($purchase_order['purchase_order_status'],
                          [
                              PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                              PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                              PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                              PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                              PURCHASE_ORDER_STATUS_CANCELED
                          ])) {
                $this->error_json('采购单号：' . $purchase_number . ' 请选择【等待到货/部分到货等待剩余/部分到货不等待剩余/全到货/已作废】状态的采购单请款');
            }

            if ($purchase_order['pay_status'] == PAY_NONEED_STATUS) {
                $this->error_json('采购单号：' . $purchase_number . '无需付款，请勿点击');
            }


            if (!in_array($purchase_order['pay_status'],[PAY_UNPAID_STATUS,PAY_MANAGER_REJECT,PAY_SOA_REJECT,PAY_FINANCE_REJECT,PAY_WAITING_MANAGER_REJECT,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT])) {
                $this->error_json('采购单号：' . $purchase_number . '该付款状态下的采购单不能请款');
            }
            $have_paid_data = $this->purchase_order_pay_model->get_pay_total_by_compact_number($purchase_number);
            if($have_paid_data and $have_paid_data['pay_price'] > 0){
                $this->error_json('采购单号：' . $purchase_number . ' 已经付过款了');
            }
            if ($purchase_order['source'] != SOURCE_NETWORK_ORDER) {
                $this->error_json('采购单号：' . $purchase_number . ' 请选择网采单请款');
            }

            if ($purchase_order['is_ali_abnormal'] == 1) {
                $this->error_json('采购单号：' . $purchase_number . ' 1688异常状态不能请款');
            }
            //验证验货状态是否满足请款条件
            $this->_check_inspect_status($purchase_order['items_list'],$warning_list);

            $purchase_order_list[$purchase_number] = $purchase_order;
            $purchase_order_arr[]=$purchase_number;
        }

        if (1 != $is_force_submit && $warning_list) {// 验货中状态的备货单，请款需要提示
            $warning_list[] = '请确定是否继续请款？';
            $warning_list_msg = implode("<br>", $warning_list);
            $this->error_data_json(['is_warning' => 1], $warning_list_msg);
        }

         //如果还有上一次取消未完结的存在，那么不允许进行该操作
        $cencel_status=$this->Purchase_order_determine_model->get_cancel_order_status($purchase_order_arr);
        if(!empty($cencel_status)){
            $errormess= '采购单号及sku'.implode(',', $cencel_status).'取消未到货状态未审核完毕，不允许再次申请';
            $this->error_json($errormess);
        }

        // 转换为 前台展示需要的数据
        $purchase_order_list_tmp = [];

        $settlementList = $this->Supplier_settlement_model->get_code_by_name_list();
        $purSurplusList = $this->Charge_against_surplus_model->get_surplus($purchase_order_arr);
        $purSurplusList = arrayKeyToColumn($purSurplusList,'purchase_number');


        foreach ($purchase_order_list as $purchase_number => $value) {
            $order_pay_type = $this->purchase_order_pay_type_model->get_one($purchase_number);
            if(empty($order_pay_type)) $this->error_json('采购单号['.$purchase_number.']确认信息未找到');

            $purSurplus = isset($purSurplusList[$purchase_number])?$purSurplusList[$purchase_number]:[];
            if(empty($purSurplus)){
                $this->error_json('采购单号['.$purchase_number.']冲销结余信息未找到');
            }


            $order = [];

            $order['purchase_number'] = $purchase_number;
            $order['pai_number'] = $order_pay_type['pai_number'];
            $order['supplier_code'] = $value['supplier_code'];
            $order['supplier_name'] = $value['supplier_name'];
            $order['account_type'] = $value['account_type'];
            $order['account_type_cn'] = isset($settlementList[$value['account_type']])?$settlementList[$value['account_type']]:'';
            $order['product_money'] = $order_pay_type['product_money'];
            $order['freight'] = $order_pay_type['freight'];
            $order['discount'] = $order_pay_type['discount'];
            $order['process_cost'] = $order_pay_type['process_cost'];
            $order['real_price'] = $order_pay_type['real_price'];


            # 获取 PO的取消运费、优惠额、加工费
            $order['cancel_product_money'] = $purSurplus['cancel_product_money'];
            $order['cancel_freight'] = $purSurplus['cancel_freight'];
            $order['cancel_discount'] = $purSurplus['cancel_discount'];
            $order['cancel_process_cost'] = $purSurplus['cancel_process_cost'];
            $order['cancel_real_price'] = $purSurplus['cancel_real_price'];


            $order['pay_total'] = $order['real_price'] - $order['cancel_real_price'];// 请款总额


            // 参考运费、重量
            $referenceData = $this->Purchase_order_transport_model->get_calculate_order_reference_freight($value);
            if($referenceData['code'] === true){
                $order['reference_freight'] = $referenceData['data_attach']['reference_freight'];
                $order['total_product_weight'] = $referenceData['data_attach']['total_product_weight'];// kg
            }else{
                $order['reference_freight'] = $referenceData['message'];
                $order['total_product_weight'] = $referenceData['message'];
            }

            $order['create_notice'] = '';// 申请备注（前端组件要求必传）

            // 应付总额=1688后台拍单号的总金额
            $aliOrderAllPrice = $this->aliorderapi->getAliOrderAllPrice($order_pay_type['pai_number']);
            $ali_pay_total = $aliOrderAllPrice['totalAmount'] - (isset($aliOrderAllPrice['applyTotalAmount'])?$aliOrderAllPrice['applyTotalAmount']:0);
            $order['ali_pay_total'] = format_price($ali_pay_total);

            $purchase_order_list_tmp[] = $order;

        }

        $data_list = [
            'key' => [],
            'value' => $purchase_order_list_tmp,
            'list_item_sum' => [],
        ];
        $this->success_json_format($data_list);
    }


    /**
     * 网采单-请款单
     * @author Jolon
     * http://www.caigou.com/finance/purchase_order_pay/network_pay_order_create
     * @desc array $_POST['purchase_numbers'] 要请款采购单号
     */
    public function network_pay_order_create() {
        $pay_data_list          = $this->input->post('pay_data_list'); // 采购单
        $is_force_submit        = $this->input->post('is_force_submit'); // 是否强制提交,1.是,其他.否

        if(!is_json($pay_data_list)){
            $this->error_json('参数【pay_data_list】数据不是JSON格式');
        }
        $pay_data_list = json_decode($pay_data_list,true);
        // 数据验证与处理
        if (empty($pay_data_list) or ! is_array($pay_data_list)) {
            $this->error_json('参数【pay_data_list】缺失或不是数组');
        }

        $po_pay_data_list = [];
        foreach($pay_data_list as $key => $value){
            if(!isset($value['purchase_number']) or empty($value['purchase_number'])){
                $this->error_json('参数【purchase_number】为空');
            }
            if(!empty($value['create_notice'])){
                $create_notice = make_semiangle($value['create_notice']);
            }else{
                $create_notice = '';
            }

            $po_pay_data_list[$value['purchase_number']] = [
                'purchase_number' => $value['purchase_number'],
                'create_notice' => $create_notice,
            ];
        }
        unset($pay_data_list);
        if (empty($po_pay_data_list)){
            $this->error_json('没有符合要求的数据');
        }

        $this->load->model('purchase_order_model', '', false, 'purchase');
        $this->load->model('purchase_order_pay_type_model', '', false, 'finance');
        $this->load->model('purchase_order_pay_model', '', false, 'finance');

        // 刷新1688金额异常
        $this->load->model('ali/Ali_order_model');
        $this->Ali_order_model->refresh_order_price(array_keys($po_pay_data_list));

        // 获取采购单数据 并验证
        $error_list = [];
        $purchase_order_list = [];
        foreach ($po_pay_data_list as $purchase_number => $pay_value) {
            $purchase_order = $this->purchase_order_model->get_one_with_demand_number($purchase_number);

            if (empty($purchase_order)) {
                $this->error_json('采购单号：' . $purchase_number . '未找到');
            }

            if (!in_array($purchase_order['purchase_order_status'],
                          [
                              PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                              PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                              PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                              PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                              PURCHASE_ORDER_STATUS_CANCELED
                          ])) {
                $this->error_json('采购单号：' . $purchase_number . ' 请选择【等待到货/部分到货等待剩余/部分到货不等待剩余/全到货/已作废】状态的采购单请款');
            }
            if (!in_array($purchase_order['pay_status'],[PAY_UNPAID_STATUS,PAY_SOA_REJECT,PAY_MANAGER_REJECT,PAY_FINANCE_REJECT,PAY_WAITING_MANAGER_REJECT,PAY_REJECT_SUPERVISOR,PAY_REJECT_MANAGER,PAY_REJECT_SUPPLY,PAY_GENERAL_MANAGER_REJECT])) {
                $this->error_json('采购单号：' . $purchase_number . '该付款状态下的采购单不能请款');
            }
            $have_paid_data = $this->purchase_order_pay_model->get_pay_total_by_compact_number($purchase_number);
            if($have_paid_data and $have_paid_data['pay_price'] > 0){
                $this->error_json('采购单号：' . $purchase_number . ' 已经付过款了');
            }
            if ($purchase_order['source'] != SOURCE_NETWORK_ORDER) {
                $this->error_json('采购单号：' . $purchase_number . ' 请选择网采单请款');
            }
            if($purchase_order['is_ali_order'] and $purchase_order['is_ali_price_abnormal']){
                $error_list[] = $purchase_number.' 的1688金额与系统不一致';
            }

            //验证验货状态是否满足请款条件
            $this->_check_inspect_status($purchase_order['items_list'],$error_list);

            $purchase_order_list[$purchase_number] = $purchase_order;
        }

        if((!isset($is_force_submit) or $is_force_submit != 1) and $error_list){// 金额异常或验货中状态的备货单，请款需要提示
            $error_list[]   = '请确定是否继续请款？';
            $error_list_msg = implode("<br>",$error_list);
            $this->error_data_json(['is_ali_price_abnormal' => 1],$error_list_msg);
        }

        // 创建请款单
        $result = $this->purchase_order_pay_model->network_pay_order_create($purchase_order_list,$po_pay_data_list);

        if ($result['code']) {
            $this->success_json($result['data']);
        } else {
            $this->error_json($result['msg']);
        }
    }


    /**
     * 合同单-创建请款单-数据预览
     * @author Jolon
     * @desc array $_POST['compact_number']         要请款合同号
     *              $_POST['requisition_payment']    为空：合同请款数据预览，不为空：预览 付款申请书
     */
    public function compact_pay_order_preview() {
        $this->load->helper('status_order');
        $this->load->helper('status_finance');

        $compact_number         = $this->input->post('compact_number');// 合同单号
        $requisition_payment    = $this->input->get_post('requisition_payment');// 付款申请书预览
        $is_force_submit        = $this->input->get_post('is_force_submit'); // 是否强制提交,1.是,其他.否
        $skip_invoice_status    = $this->input->get_post('skip_invoice_status');// 确认继续(跳过验证开票状态)

        if(empty($compact_number)) $this->error_json('无合同号不允许申请款');

        $this->load->model('purchase_order_model','',false,'purchase');
        $this->load->model('purchase_order_pay_model','',false,'finance');
        $this->load->model('purchase_order_pay_type_model','',false,'finance');
        $this->load->model('supplier_model','',false,'supplier');
        $this->load->model('compact_model', '', false, 'compact');
        $this->load->model('purchase/Purchase_order_determine_model');
        $this->load->model('Purchase_invoice_list_model','',false,'purchase');
        $this->load->model('warehouse/Warehouse_storage_record_model');
        $this->load->model('statement/Charge_against_records_model');
        $this->load->model('statement/Purchase_statement_items_model');
        $this->load->model('purchase/Purchase_order_transport_model');
        $this->load->model('statement/Charge_against_surplus_model');

        //$compact = $this->compact_model->get_compact_one($compact_number);
        $compact_pay_data = $this->compact_model->get_compact_pay_data($compact_number);
        if(empty($compact_pay_data['code'])) $this->error_json($compact_pay_data['msg']);
        $compact_pay_data = isset($compact_pay_data['data'])?$compact_pay_data['data']:[];

        if(empty($compact_pay_data) or empty($compact_pay_data['items_list'])) $this->error_json('合同号：'.$compact_number.' 合同或合同明细未找到');
        if($compact_pay_data['compact_status'] != COMPACT_STATUS_AUDIT_PASS) $this->error_json('合同号：'.$compact_number.' 合同非已审核状态');
        $result = $this->purchase_order_pay_model->verify_order_status_enable_pay($compact_number);
        if($result !== true) $this->error_json('合同号：'.$compact_number.' 合同存在未完结的请款单['.$result.']');

        $purchase_number_list = [];// 合同所含有的采购单号
        $pur_order_info_list = [];// 返回的采购单相关信息
        foreach($compact_pay_data['items_list'] as &$item){
            if( !array_key_exists($item['purchase_number'],$pur_order_info_list)){// 只检测一次

                $purchase_order = $this->purchase_order_model->get_one($item['purchase_number'],false);
                $pur_order_pay_type = $this->purchase_order_pay_type_model->get_one($item['purchase_number']);
                if (!in_array($purchase_order['purchase_order_status'],
                    [
                        PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                        PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                        PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                        PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                        PURCHASE_ORDER_STATUS_CANCELED
                    ])) {
                    $this->error_json('采购单号：' . $item['purchase_number'] . ' 请选择【等待到货/部分到货等待剩余/部分到货不等待剩余/全到货】状态的采购单请款');
                }
                $result_po = $this->purchase_order_pay_model->verify_order_status_enable_pay_by_po($item['purchase_number']);
                if($result_po !== true) $this->error_json('采购单号：'.$item['purchase_number'].' 存在未完结的请款单['.$result_po.']');

                $charge_against_record = $this->Charge_against_records_model->get_ca_audit_status($item['purchase_number'],2,[CHARGE_AGAINST_STATUE_WAITING_AUDIT,CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT]);
                if ($charge_against_record){
                    $this->error_json('采购单：' . $item['purchase_number'] . '存在退款冲销中的记录');
                }

                if ($purchase_order['pay_status'] == PAY_NONEED_STATUS) {
                    $this->error_json('合同号：' . $compact_number . '无需付款，请勿点击');
                }


                // 采购单信息
                $poInfo = [
                    'purchase_number' => $item['purchase_number'],
                    'purchase_order_status' => $purchase_order['purchase_order_status'],
                    'purchase_order_status_cn' => getPurchaseStatus($purchase_order['purchase_order_status']),
                    'po_product_money' => $pur_order_pay_type['product_money'],
                    'po_freight' => $pur_order_pay_type['freight'],
                    'po_discount' => $pur_order_pay_type['discount'],
                    'po_process_cost' => $pur_order_pay_type['process_cost'],
                    'po_real_price' => $pur_order_pay_type['real_price'],
                ];

                // 参考运费、重量
                $referenceData = $this->Purchase_order_transport_model->get_calculate_order_reference_freight($purchase_order);
                if($referenceData['code'] === true){
                    $poInfo['po_reference_freight'] = $referenceData['data_attach']['reference_freight'];
                    $poInfo['po_total_product_weight'] = $referenceData['data_attach']['total_product_weight'];// kg
                }else{
                    $poInfo['po_reference_freight'] = $referenceData['message'];
                    $poInfo['po_total_product_weight'] = $referenceData['message'];
                }

                $pur_order_info_list[$item['purchase_number']] = $poInfo;
            }

            $purchase_number_list[] = $item['purchase_number'];
            $item['suggest_order_status_cn'] = getPurchaseStatus(intval($item['suggest_order_status']));
        }

        //验证验货状态是否满足请款条件
        $purchase_number_list = array_unique($purchase_number_list);
        $warning_list = [];
        foreach ($purchase_number_list as $po) {
            $purchase_order = $this->purchase_order_model->get_one_with_demand_number($po);
            $this->_check_inspect_status($purchase_order['items_list'],$warning_list);

            $result2 = $this->Purchase_statement_items_model->verify_exists_statement($po);
            if($result2 !== true) $this->error_json('采购单：'.$po.' 关联对账单 '.$result2.' ,还未付款，付款后才可请款');
        }

        if( 1 != $is_force_submit  && $warning_list){// 验货中状态的备货单，请款需要提示
            $warning_list[]   = '请确定是否继续请款？';
            $warning_list_msg = implode("<br>",$warning_list);
            $this->error_data_json(['is_warning' => 1],$warning_list_msg);
        }

        //如果还有上一次取消未完结的存在，那么不允许进行该操作
        $cencel_status=$this->Purchase_order_determine_model->get_cancel_order_status($purchase_number_list);
        if(!empty($cencel_status)){
            $errormess= '采购单号及sku'.implode(',', $cencel_status).'取消未到货状态未审核完毕，不允许再次申请';
            $this->error_json($errormess);
        }

        if(!empty($requisition_payment)){// 付款申请书数据
            // 请款界面提交的数据
            $post_data['pay_type']           = $this->input->post('pay_type');// 支付方式
            $post_data['requisition_method'] = $this->input->post('requisition_method');// 请款方式
            $post_data['pay_ratio']          = $this->input->post('pay_ratio');// 请款比例
            $post_data['product_money']      = $this->input->post('product_money');// 商品金额
            $post_data['freight']            = $this->input->post('freight');// 运费
            $post_data['discount']           = $this->input->post('discount');// 优惠额
            $post_data['process_cost']       = $this->input->post('process_cost');// 加工费
            $post_data['pay_price']          = $this->input->post('pay_price');// 请款总金额
            $post_data['purchase_account']   = $this->input->post('purchase_account');// 请款总金额
            $post_data['pai_number']         = $this->input->post('pai_number');// 拍单号
            $post_data['create_notice']      = $this->input->post('create_notice');// 请款备注
            $post_data['is_drawback']        = $this->input->post('is_drawback');// 是否退税
            $post_data['freight_desc']       = $this->input->post('freight_desc');// 运费说明
            $post_data['item_data_list']     = $this->input->post('item_data_list');// 备货单维度运费、优惠额、请款总额
            $post_data['po_data_list']       = $this->input->post('po_data_list');// PO维度运费、优惠额、加工费、请款总额
            $compact_url                     = $this->input->post('compact_url');// 上传合同上传扫描件
            $post_data['compact_url']        = [];
            $post_data['product_money']      = floatval($post_data['product_money']);// 转成浮点数

            $error_msg = '';
            // 验证合同扫描文件名是否符合规则
            if($compact_url){
                if(!is_json($compact_url)){
                    $error_msg = '合同扫描件数据格式错误';
                }else{
                    $compact_url = json_decode($compact_url,true);
                    foreach($compact_url as $compact_value){
                        if(!isset($compact_value['file_name']) or empty($compact_value['file_name'])) $error_msg = '合同扫描件数据文件名错误';
                        if(!isset($compact_value['file_path']) or empty($compact_value['file_path'])) $error_msg = '合同扫描件数据文件路径错误';
                        if(stripos($compact_value['file_name'],$compact_number) !== 0) $error_msg = '上传的合同扫描件文件名必须以合同号开头';
                        $post_data['compact_url'][] = [
                            'file_name' => isset($compact_value['file_name'])?$compact_value['file_name']:'',
                            'file_path' => isset($compact_value['file_path'])?$compact_value['file_path']:'',
                        ];
                    }
                }
                if(!empty($error_msg)) $this->error_json($error_msg);
            }

            // Start:验证所有金额是否是两位小数
            if(is_two_decimal($post_data['freight']) === false) $error_msg = '运费必须是两位小数';
            if(is_two_decimal($post_data['discount']) === false) $error_msg = '优惠额必须是两位小数';
            if(is_two_decimal($post_data['process_cost']) === false) $error_msg = '加工费必须是两位小数';
            if(is_two_decimal($post_data['pay_price']) === false) $error_msg = '请款总金额必须是两位小数';
            if(empty($post_data['item_data_list']) or is_json($post_data['item_data_list']) === false){
                $error_msg = '备货单维度金额明细数据错误';
                $post_item_data_list = [];
            }else{
                $post_item_data_list = json_decode($post_data['item_data_list'],true);
            }
            if(empty($post_data['po_data_list']) or is_json($post_data['po_data_list']) === false){
                $error_msg = 'PO维度金额明细数据错误';
                $post_po_data_list = [];
            }else{
                $post_po_data_list = json_decode($post_data['po_data_list'],true);
            }
            if(is_two_decimal($post_data['product_money']) === false) $error_msg = '请款商品金额必须是两位小数';

            // 验证提交的备货单数据、备货单总金额与金额明细是否相等（后面不再验证了）
            if(count($post_item_data_list) != count($compact_pay_data['items_list'])) $error_msg = '备货单维度金额明细总数不等于合同SKU总数';
            if(count($post_po_data_list) != count($purchase_number_list)) $error_msg = 'PO维度金额明细总数不等于合同下PO总个数';

            $post_demand_number_list    = array_keys($post_item_data_list);// 提交的备货单
            $post_po_number_list        = array_keys($post_po_data_list);// 提交的备货单
            $compact_demand_number_list = array_column($compact_pay_data['items_list'],'demand_number');// 合同备货单明细
            if(array_diff($post_demand_number_list,$compact_demand_number_list) or array_diff($compact_demand_number_list,$post_demand_number_list)){
                $error_msg = '提交的备货单与合同备货单明细不一致';
            }
            if(array_diff($post_po_number_list,$purchase_number_list) or array_diff($purchase_number_list,$post_po_number_list)){
                $error_msg = '提交的PO与合同下PO明细不一致';
            }
            // 报损请款 验证请款金额
            if($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_REPORTLOSS){
                $calculate_data = $this->compact_model->calculate_compact_available_reportloss(null,$compact_pay_data['items_list']);
                if(empty($calculate_data) or empty($calculate_data['data'])){
                    $this->error_json('计算可申请报损金额错误');
                }
                $items_list = $calculate_data['data'];
                $items_list = arrayKeyToColumn($items_list,'demand_number');
            }

            // 备货单金额明细项汇总
            $item_total_product_money = 0;
            foreach($post_item_data_list as $demand_number => $demand_value){
                if(!isset($demand_value['item_product_money']) or is_two_decimal($demand_value['item_product_money']) === false) $error_msg = '备货单维度请款商品金额未设置或不是两位小数';
                $item_total_product_money   += isset($demand_value['item_product_money']) ? $demand_value['item_product_money'] : 0;
            }
            if(bccomp($item_total_product_money,$post_data['product_money'],3) != 0) $error_msg = '备货单维度商品金额之和不等于总商品金额';

            // PO单金额明细项汇总
            $total_product_money = $total_freight = $total_discount = $total_pay_price = $total_process_cost = 0;
            foreach($post_po_data_list as $po_number => $po_value){
                if(!isset($po_value['po_product_money']) or is_two_decimal($po_value['po_freight']) === false) $error_msg = 'PO维度运费未设置或不是两位小数';
                if(!isset($po_value['po_freight']) or is_two_decimal($po_value['po_freight']) === false) $error_msg = 'PO维度运费未设置或不是两位小数';
                if(!isset($po_value['po_discount']) or is_two_decimal($po_value['po_discount']) === false) $error_msg = 'PO维度优惠额未设置或不是两位小数';
                if(!isset($po_value['po_process_cost']) or is_two_decimal($po_value['po_process_cost']) === false) $error_msg = 'PO维度加工费未设置或不是两位小数';
                if(!isset($po_value['po_pay_price']) or is_two_decimal($po_value['po_pay_price']) === false) $error_msg = 'PO维度请款金额未设置或不是两位小数';

                $total_product_money   += isset($po_value['po_product_money']) ? $po_value['po_product_money'] : 0;
                $total_freight   += isset($po_value['po_freight']) ? $po_value['po_freight'] : 0;
                $total_discount  += isset($po_value['po_discount']) ? $po_value['po_discount'] : 0;
                $total_process_cost  += isset($po_value['po_process_cost']) ? $po_value['po_process_cost'] : 0;
                $total_pay_price += isset($po_value['po_pay_price']) ? $po_value['po_pay_price'] : 0;

            }
            if(bccomp($total_product_money,$post_data['product_money'],3) != 0) $error_msg = 'PO维度商品金额之和不等于总商品金额';
            if(bccomp($total_freight,$post_data['freight'],3) != 0) $error_msg = 'PO维度运费之和不等于总运费';
            if(bccomp($total_discount,$post_data['discount'],3) != 0) $error_msg = 'PO维度优惠额之和不等于总优惠额';
            if(bccomp($total_process_cost,$post_data['process_cost'],3) != 0) $error_msg = 'PO维度加工费之和不等于总加工费';
            if(bccomp($total_pay_price,$post_data['pay_price'],3) != 0) $error_msg = 'PO维度请款金额之和不等于总请款金额';
            if(bccomp($total_product_money + $total_freight - $total_discount + $total_process_cost,$total_pay_price,3) != 0){
                $error_msg = 'PO维度请款金额明细之和不等于请款总额';
            }
            if(!empty($error_msg)){
                $this->error_json($error_msg);
            }
            // End:验证所有金额是否是两位小数

            // 由于加工费的逻辑与运费的逻辑一致，所以把加工费累加到运费上方便统一处理（这里累加了 后面生成请款单就要减下来 #A0327）
            $post_data['freight'] = $post_data['freight'] + $post_data['process_cost'];

            // 验证 支付类型、请款类型、请款金额等数据
            if(empty($post_data['pay_type']))           $error_msg = '请选择支付方式';
            if(empty($post_data['requisition_method'])) $error_msg = '请选择请款方式';
            if(empty($post_data['pay_price']))          $error_msg = '请设置请款总金额';
            if($post_data['requisition_method'] != PURCHASE_REQUISITION_METHOD_PERCENT){
                $post_data['pay_ratio'] = '';// 只有 比例请款才有比例值
            }
            if($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_PERCENT){
                if(empty($post_data['pay_ratio'])) $error_msg = '比例请款-必须提交请款比例'; 
                if(bccomp(floatval($post_data['product_money']) + floatval($post_data['freight']) - floatval($post_data['discount']), floatval($post_data['pay_price']),3)!=0){
                    $error_msg = "本次请款总金额不等于请款明细汇总";
                }
            }
            elseif($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_IN_QUANTITY){
                if($post_data['product_money']){
                    if(bccomp(floatval($post_data['product_money']) + floatval($post_data['freight']) - floatval($post_data['discount']),floatval($post_data['pay_price']),3)!=0){
                        $error_msg = "本次请款总金额不等于请款明细汇总";
                    }
                }else{// 入库数量请款  反过来计算请款产品金额
                    $post_data['product_money'] = format_two_point_price(floatval($post_data['pay_price']) - floatval($post_data['freight']) + floatval($post_data['discount']));
                }
            }
            elseif($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_HAND){
                if(bccomp(floatval($post_data['product_money']) + floatval($post_data['freight']) - floatval($post_data['discount']),floatval($post_data['pay_price']),3)!=0){
                    $error_msg = "本次请款总金额不等于请款明细汇总";
                }
            }
            elseif($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_MANUAL){
                if(bccomp(floatval($post_data['product_money']) + floatval($post_data['freight']) - floatval($post_data['discount']),floatval($post_data['pay_price']),3)==1){
                    $error_msg = "本次请款总金额必须小于等于可申请金额";
                }
                //本次请款总金额必须大于0
                if(floatval($post_data['pay_price'])<=0){
                    $error_msg = "本次请款总金额必须大于0";
                }
            }elseif($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_REPORTLOSS){
                foreach($post_item_data_list as $demand_number => $demand_value){
                    // 验证报损请款 请款金额是否正确
                    if(isset($items_list[$demand_number]) and !empty($items_list[$demand_number])){
                        /*if(bccomp($demand_value['item_freight'],$items_list[$demand_number]['available_loss_freight'],3) > 0){
                            $error_msg = '报损请款：请款运费大于可申请报损运费';
                        }
                        if(bccomp($demand_value['item_process_cost'] , $items_list[$demand_number]['available_loss_process_cost'],3) > 0){
                            $error_msg = '报损请款：请款运费大于可申请报损加工费';
                        }*/
                        $loss_product_money = $demand_value['item_pay_price'] - $demand_value['item_freight'] - $demand_value['item_process_cost'] + $demand_value['item_discount'];
                        if(bccomp($loss_product_money , $items_list[$demand_number]['available_loss_product_money'],3) > 0){
                            $error_msg = '报损请款：请款运费大于可申请报损商品金额';
                        }
                    }
                }
            }else{
                $error_msg = "请款方式不存在";
            }
//            if(bccomp(floatval($post_data['freight']),floatval($compact_pay_data['freight']),3)>0){
//                   $error_msg = "本次请款运费不等于合同单运费";
//               }

            if(empty($error_msg)){
                $pay_category = $this->purchase_order_pay_model->compact_pay_category($compact_pay_data['is_drawback'],$compact_pay_data['settlement_method'],$post_data['pay_ratio'],$post_data['pay_price'],$post_data['freight'],$post_data['discount'],$post_data['requisition_method']);
                $pay_types = getPayType($post_data['pay_type']);
                if($pay_category != PURCHASE_PAY_CATEGORY_4 and $compact_pay_data['pay_type']!=$pay_types){
                    $error_msg = "请款类型≠采购运费的，那么支付方式必须=采购单的支付方式，不允许用户修改";

                }elseif($pay_category == PURCHASE_PAY_CATEGORY_4){

                    //针对单独请款请运费做判断
                    //不区分业务线   值传供应商编码支付方式也不区分是否退税
//                    $supplier_account_info = $this->Supplier_payment_info_model->check_payment_info($compact_pay_data['supplier_code'], PURCHASE_IS_DRAWBACK_N, $compact_pay_data['source'],$post_data['pay_type']); //支付方式:1.支付宝,2.对公支付，3.对私支付

                    $supplier_account_info = $this->Supplier_payment_info_model->check_payment_info_freight($compact_pay_data['supplier_code'],$post_data['pay_type']);
                    if(empty($supplier_account_info)){
                        $error_msg = "请款类型=采购运费的，那么支付方式必须=供应商管理界面的支付方式";
                    }
                }else{
                    $supplier_account_info = $this->Supplier_payment_info_model->check_payment_info($compact_pay_data['supplier_code'], $compact_pay_data['is_drawback'], $compact_pay_data['source']); //支付方式:1.支付宝,2.对公支付，3.对私支付
                }
                if(empty($error_msg)){
                    if (empty($supplier_account_info)) {
                        switch($post_data['pay_type']){
                            case PURCHASE_PAY_TYPE_ALIPAY:
                                $error_msg = '线上支付宝';
                                break;
                            case PURCHASE_PAY_TYPE_PRIVATE:
                                $error_msg = '线下境外';
                                break;
                            case PURCHASE_PAY_TYPE_PUBLIC:
                                $error_msg = '线下境内';
                                break;

                        }
                        $error_msg = $error_msg. '-收款账号失败';
                    }else{
                        $supplier_account_info['supplier_name'] = $this->supplier_model->get_supplier_name($compact_pay_data['supplier_code']);

                        if($post_data['pay_type'] == PURCHASE_PAY_TYPE_ALIPAY){  //如果是单独请运费且支付方式 为 线上支付宝
                            $supplier_account_info['supplier_name']           = '';
                            $supplier_account_info['account_name']            = '';
                            $supplier_account_info['payment_platform_bank']   = '';
                            $supplier_account_info['payment_platform_branch'] = '';
                            $supplier_account_info['account']                 = '';
                        }
                    }
                }
            }
            $company_info = compactCompanyInfo($compact_pay_data['purchase_name']);
            if(empty($company_info)) $error_msg = '获取【付款申请书】采购主体失败';
            if(empty($error_msg) and $post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_PERCENT and $post_data['pay_ratio']){// 比例请款
                if($this->purchase_order_pay_model->verify_compact_percent_paid($compact_number,$post_data['pay_ratio'])){
                    $error_msg = "合同号[$compact_number]当前请款比例[{$post_data['pay_ratio']}]已经请款，不能再请";
                }
            }

            if(empty($error_msg)){// 验证 退税、非退税合同运费与优惠额
                $available_product_money = floatval($compact_pay_data['available_product_money']);
                if($post_data['product_money'] - $available_product_money > IS_ALLOWABLE_ERROR ){
                    $error_msg = "本次请款：商品金额[{$post_data['product_money']}]大于可申请商品金额[{$available_product_money}]";
                }
                // 退税合同必须单独请运费
                if($compact_pay_data['is_drawback'] == PURCHASE_IS_DRAWBACK_Y and $post_data['freight'] > 0){// 验证退税合同是否单独请运费
                    if(bccomp(abs($post_data['freight']-$post_data['discount']),$post_data['pay_price'],3) != 0){
                        $error_msg = '退税合同必须单独请运费(加工费)及优惠额';
                    }elseif(abs($available_product_money) > IS_ALLOWABLE_ERROR){// 验证货款是否已全部请完
                        $error_msg = '退税合同必须在货款全部请完才能请运费且必须单独请运费(加工费)及优惠额';
                    }
                }elseif($compact_pay_data['is_drawback'] == PURCHASE_IS_DRAWBACK_N and bccomp(abs($post_data['freight']-$post_data['discount']),$post_data['pay_price'],3) == 0){
                    $error_msg = '非退税合同不能单独请运费(加工费)及优惠额';
                }
                if($compact_pay_data['is_drawback'] == PURCHASE_IS_DRAWBACK_N and $post_data['freight'] > 0 and abs($post_data['product_money'] - $available_product_money) > IS_ALLOWABLE_ERROR ){
                    $error_msg = '非退税合同请尾款时才能请运费(加工费)及优惠额';
                }
                if(empty($error_msg)){
                    // 判断是否请过运费(加工费)和优惠额
                    if(!empty($post_data['freight']) and (floatval($compact_pay_data['paid_total_freight']) or floatval($compact_pay_data['paid_total_process_cost']))){
                        $error_msg = '已经请过运费(加工费)了，不能再请';
                    }
                    if(!empty($post_data['discount']) and floatval($compact_pay_data['paid_total_discount'])) $error_msg = '已经请过优惠额了，不能再请';
                }

                //是退税,且是尾款
                if (empty($error_msg) && empty($skip_invoice_status) && $compact_pay_data['is_drawback'] == PURCHASE_IS_DRAWBACK_Y){
                    $is_end = false;
                    if ($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_PERCENT){//比例请款
                        $arr = explode('+',$compact_pay_data['settlement_ratio']);
                        $ding_amount = $arr[0]??'';//订金
                        $end_amount = $arr[1]??'';//尾款

                        if ($ding_amount == '100%' && $post_data['pay_ratio'] == '100%'){//100%的也属于尾款
                            $is_end = true;
                        }elseif ($end_amount == $post_data['pay_ratio']){
                            $is_end = true;
                        }else{
                            $is_end = false;
                        }

                    }elseif ($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_MANUAL){//手动请款
                        if (bcsub($compact_pay_data['available_product_money'],$post_data['pay_price']) < IS_ALLOWABLE_ERROR && bcsub($compact_pay_data['available_product_money'],$post_data['pay_price'] ) >= 0){//尾款
                            $is_end = true;
                        }
                    }

                    if ($is_end){
                        if(!$this->Purchase_invoice_list_model->check_compact_invoice_status($compact_number)){
                            $this->success_json(['is_confirm'=>1,'confirm_msg'=>sprintf('合同单:%s,存在报关票据未开完,请问是否继续申请?',$compact_number)]);
                        }
                    }
                }
            }


            if(empty($error_msg)){
                if(in_array($post_data['pay_type'],[PURCHASE_PAY_TYPE_PRIVATE])){// 线下境外
                    $receive_unit = $supplier_account_info['supplier_name']."(".$supplier_account_info['account_name'].")";
                }else{// 支付宝、 线下境内
                    $receive_unit = $supplier_account_info['supplier_name'];
                }
                $from_data = [
                    'compact_number'          => $compact_number,
                    'invoice_looked_up'       => $company_info['name'],// 抬头
                    'receive_unit'            => $receive_unit,// 收款单位
                    'payment_platform_branch' => $supplier_account_info['payment_platform_branch'],// 开户行
                    'account'                 => $supplier_account_info['account'],// 账号
                    'pay_date'                => date('Y年m月d日'),
                    'pay_price'               => $post_data['pay_price'],
                    'pay_price_cn'            => numberPriceToCname($post_data['pay_price']),
                    'check_department'        => '总经办',
                ];
                // 36253 采购单运费请款时，当支付方式=线下境外，请款方式=运费请款 时 公司主体=香港易佰
                if($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_HAND && $post_data['pay_type'] == PURCHASE_PAY_TYPE_PRIVATE){
                    $from_data['invoice_looked_up'] = 'YIBAI TECHNOLOGY LTD';
                }
                // 请款摘要
                $from_data['abstract_remark'] = abstractRemarkTemplate(SOURCE_SUBJECT_COMPACT_ORDER,$compact_pay_data['product_money'] - $compact_pay_data['cancel_total_product_money'],$post_data['product_money'],$compact_pay_data['paid_total_product_money']);

                #A0327
                $post_data['freight'] = $post_data['freight'] - $post_data['process_cost'];

                $this->rediss->setData($compact_number.'_post_data',$post_data);
                $this->rediss->setData($compact_number.'_from_data',$from_data);

                $this->success_json(['application_from_data' => $from_data]);
            }else{
                $this->error_json($error_msg);
            }
        }else{// 合同单请款数据预览
            // 已付金额（采购单）
            $purSurplusList = $this->Charge_against_surplus_model->get_surplus($purchase_number_list);
            $purSurplusList = arrayKeyToColumn($purSurplusList,'purchase_number');

            // PO维度已付款金额、已取消金额
            foreach($pur_order_info_list as $poInfo_number => $poInfo_value){
                $purSurplus = isset($purSurplusList[$poInfo_number])?$purSurplusList[$poInfo_number]:[];
                if($purSurplus){
                    $pur_order_info_list[$poInfo_number]['po_paid_product_money'] = $purSurplus['paid_product_money'];
                    $pur_order_info_list[$poInfo_number]['po_paid_freight'] = $purSurplus['paid_freight'];
                    $pur_order_info_list[$poInfo_number]['po_paid_discount'] = $purSurplus['paid_discount'];
                    $pur_order_info_list[$poInfo_number]['po_paid_process_cost'] = $purSurplus['paid_process_cost'];
                    $pur_order_info_list[$poInfo_number]['po_paid_real_price'] = $purSurplus['paid_real_price'];

                    $pur_order_info_list[$poInfo_number]['po_cancel_product_money'] = $purSurplus['cancel_product_money'];
                    $pur_order_info_list[$poInfo_number]['po_cancel_freight'] = $purSurplus['cancel_freight'];
                    $pur_order_info_list[$poInfo_number]['po_cancel_discount'] = $purSurplus['cancel_discount'];
                    $pur_order_info_list[$poInfo_number]['po_cancel_process_cost'] = $purSurplus['cancel_process_cost'];
                    $pur_order_info_list[$poInfo_number]['po_cancel_real_price'] = $purSurplus['cancel_real_price'];

                    // 剩余可请款总额
                    $pur_order_info_list[$poInfo_number]['po_available_pay_real_price'] = $pur_order_info_list[$poInfo_number]['po_real_price']
                        - $pur_order_info_list[$poInfo_number]['po_paid_real_price']
                        - $pur_order_info_list[$poInfo_number]['po_cancel_real_price'];
                }
            }

            if($compact_pay_data){
                $available_product_money = floatval($compact_pay_data['available_product_money']);
                $pay_types = getPayType();
                if(($compact_pay_data['is_drawback'] == PURCHASE_IS_DRAWBACK_Y)
                    || ($compact_pay_data['is_drawback'] == PURCHASE_IS_DRAWBACK_N and $compact_pay_data['product_money'] != $available_product_money ) ){
                }else{
                    unset($pay_types[PURCHASE_PAY_TYPE_ALIPAY]);// 去除支付宝
                }

                $requisition_methodsList = getRequisitionMethod();
                if(in_array($compact_pay_data['settlement_method'],[1,6,7,8,9,37,38])){// 结算方式为：线下账期、货到付款的只能 报损请款

                    $calculate_data = $this->compact_model->calculate_compact_available_reportloss(null,$compact_pay_data['items_list']);
                    if(empty($calculate_data) or empty($calculate_data['data'])){
                        $this->error_json('计算可申请报损金额错误');
                    }
                    $requisition_methods = $requisition_methodsList;
                    $compact_pay_data['items_list'] = $calculate_data['data'];

                }else{
                    //已经存在手动请款的后面必须都用手动请款
                    $requisition_method = $this->purchase_order_pay_model->get_order_requisition_method($compact_number);
                    if($requisition_method==PURCHASE_REQUISITION_METHOD_MANUAL){
                        $requisition_methods = array_diff($requisition_methodsList,["比例请款"]);
                    }else{
                        $requisition_methods = getRequisitionMethod();
                    }
                    $requisition_methods = array_diff($requisition_methods,["报损请款"]);
                }
                
                $requisition_methods = array_diff($requisition_methods,["入库数量"]);

                $ratio_money_list    = compactPaymentPlanByRatio($compact_pay_data['settlement_ratio'],$compact_pay_data['product_money']);
                $ratio_money_list    = calculateRealPayMoney($ratio_money_list,$compact_pay_data['cancel_total_product_money'] + $compact_pay_data['ca_total_product_money'] - $compact_pay_data['real_refund_product_money']);// 合同 取消未到货取消金额/退款冲销金额 从尾款里面扣除
                $purchase_account_list = getUserEnablePurchaseAccount(getActiveUserId());// 当前用户的采购账号

                // 剩余可申请商品金额全部为0 则按照 采购金额分摊
                $item_available_product_money_total = format_two_point_price(array_sum(array_column($compact_pay_data['items_list'],'item_available_product_money')));
                foreach($compact_pay_data['items_list'] as &$item_value){

                    if($item_available_product_money_total == 0){
                        $item_value['item_available_product_money'] = $item_value['purchase_unit_price'] * $item_value['order_amount'];
                        $item_value['item_distribute_price'] = $item_value['purchase_unit_price'] * $item_value['order_amount'];
                    }

                    // 组装数据给前端-合并 PO 信息
                    $pur_order_info = isset($pur_order_info_list[$item_value['purchase_number']])?$pur_order_info_list[$item_value['purchase_number']]:[];
                    if(!isset($pur_order_info['po_cancel_discount']))$item_value['po_cancel_discount'] = 0;
                    if(!isset($pur_order_info['po_cancel_freight']))$item_value['po_cancel_freight'] = 0;
                    if(!isset($pur_order_info['po_cancel_process_cost']))$item_value['po_cancel_process_cost'] = 0;
                    if(!isset($pur_order_info['po_cancel_product_money']))$item_value['po_cancel_product_money'] = 0;
                    if(!isset($pur_order_info['po_cancel_real_price']))$item_value['po_cancel_real_price'] = 0;
                    $item_value = array_merge($item_value,$pur_order_info);
                    $item_value['po_available_pay_real_price'] -= $item_value['ca_product_money'] + $item_value['ca_process_cost'];// 扣减抵扣商品金额和加工费
                    $item_value['po_distribute_price'] += $item_value['item_distribute_price'];// PO维度金额分摊占比
                }


                $data_list = [
                    'compact'             => $compact_pay_data,
                    'pay_types'           => $pay_types,
                    'requisition_methods' => $requisition_methods,
                    'ratio_money_list'    => $ratio_money_list,
                    'purchase_account'    => $purchase_account_list,
                ];
                $this->success_json_format($data_list);
            }else{
                $this->error_json('请求错误');
            }
        }
    }

    /**
     * 合同单-创建请款单-付款申请书预览
     *      在 compact_pay_order_preview 生成付款申请书之后调用
     * @author Jolon
     * @desc array $_POST[]  要请款合同号
     * http://www.caigou.com/finance/purchase_order_pay/compact_pay_order_create
     */
    public function compact_pay_order_create(){
        $compact_number =  $this->input->post('compact_number');// 合同单号
        $payment_reason =  $this->input->post('payment_reason');// 合同单号
        if(empty($compact_number) or empty($payment_reason)){
            $this->error_json('参数【compact_number或payment_reason】缺失');
        }
        $this->load->model('purchase_order_pay_model','',false,'finance');
        $this->load->model('compact_model','',false,'compact');
        $compact = $this->compact_model->get_compact_one($compact_number);
        if(empty($compact) or empty($compact['items_list'])) $this->error_json('合同号：'.$compact_number.' 合同或合同明细未找到');
        if ($compact['compact_status'] != COMPACT_STATUS_AUDIT_PASS) {// 合同状态 20.已审核
            $this->error_json('合同号：' . $compact_number . ' 合同非已审核状态');
        }
        $result = $this->purchase_order_pay_model->verify_order_status_enable_pay($compact_number);
        if($result !== true){ $this->error_json('合同号：'.$compact_number.' 合同存在未完结的请款单['.$result.']');}

        // 读取缓存的数据
        $post_data = $this->rediss->getData($compact_number.'_post_data');
        $from_data = $this->rediss->getData($compact_number.'_from_data');
        if(empty($post_data) or empty($from_data)){
            $this->error_json('请款信息或付款申请书信息缺失【数据缓存已过有效期（4800秒）】');
        }else{
            $this->rediss->deleteData($compact_number.'_post_data');
            $this->rediss->deleteData($compact_number.'_from_data');
        }

        $data = [];
        $data['post_data'] = $post_data;// 请款数据
        $data['from_data'] = $from_data;// 付款申请书数据
        $data['compact_number'] = $compact_number;
        $data['purchase_name'] = $compact['purchase_name'];
        $data['from_data']['payment_reason'] = $payment_reason;
        $result = $this->purchase_order_pay_model->compact_pay_order_create($data);

        if($result['code']){
            $this->success_json($result['data']);
        }else{
            $this->error_json($result['msg']);
        }

    }
    /**
     * http://www.caigou.com/finance/purchase_order_pay/payment_examines
     * **/
    public function payment_examines(){
        $ids =   $this->input->get_post('ids');// 勾选数据 
        $result = $this->purchase_order_pay_model->payment_examine_saves($ids);
        if(!empty($result)){
            $this->success_json($result);
        }else{
            $this->error_json('没有数据');
        }
    }

    /**
     * 验证验货状态是否满足请款条件
     * @param $sku_data
     * @param array $warning_list
     */
    private function _check_inspect_status($sku_data, &$warning_list)
    {
        foreach ($sku_data as $item) {
            if (in_array($item['check_status'], [CHECK_ORDER_STATUS_WAITING_PURCHASER_CONFIRM,
                    CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM, CHECK_ORDER_STATUS_QUALITY_CHECKING,
                    CHECK_ORDER_STATUS_WAITING_EXEMPTION_AUDIT, CHECK_ORDER_STATUS_EXEMPTION_REJECT]) && SUGGEST_STATUS_CANCEL != $item['suggest_status']) {
                //状态-验货中（待采购确认，待品控确认，品控确认中，免检待审核，免检驳回），排除已作废备货单
                $warning_list[] = '备货单：' . $item['demand_number'] . ' 验货状态为“' . getCheckStatus($item['check_status']) . '”';
                continue;
            } elseif ((in_array($item['check_status'], [CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM, CHECK_ORDER_STATUS_QUALIFIED_APPLYING])
                    OR (CHECK_ORDER_STATUS_UNQUALIFIED == $item['check_status'] && !$item['is_special']))
                && SUGGEST_STATUS_CANCEL != $item['suggest_status']) {
                //状态-验货不合格(不合格待确认，转合格申请中，验货不合格且特批出货=否),排除已作废备货单
                $this->error_json('备货单：' . $item['demand_number'] . ' 验货不合格，无法请款');
            }
        }
    }
}