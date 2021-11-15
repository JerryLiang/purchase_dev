<?php
/**
 * 报损和取消未到货转换控制器
 * User: Jolon
 * Date: 2020/03/03 10:00
 */

class Report_loss_unarrived_advance extends MY_Controller{

	public function __construct(){
        parent::__construct();
        $this->load->model('Report_loss_model','Report_loss_model');
        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('purchase/Purchase_order_items_model');
        $this->load->model('purchase/Purchase_order_determine_model');
        $this->load->model('purchase/Purchase_order_unarrived_model');
        $this->load->model('warehouse/Warehouse_storage_record_model');
        $this->load->helper('status_order');
        $this->load->helper('abnormal');
        $this->load->helper('common');
    }


    /**
     * 获取取消未到货转报损数据
     * @author Jolon 2020/03/03
     */
    public function get_unarrived_to_loss(){
        // 获取取消未到货数据
        $cancel_id = $this->input->post_get('cancel_id'); //勾选数据
        //获取采购单明细表id
        $audit_status = $this->Purchase_order_determine_model->purchase_db->select('audit_status')->where('id', $cancel_id)->get('purchase_order_cancel')->row_array();

        if($audit_status['audit_status'] != CANCEL_AUDIT_STATUS_CGBH and $audit_status['audit_status'] != CANCEL_AUDIT_STATUS_CFBH){
            $this->error_json("在“取消未到货状态=采购驳回/财务驳回”时才能操作");
        }

        try {
            $data_list = $this->Purchase_order_determine_model->get_cancel_unarrived_goods_examine_list($cancel_id);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }

        $total_report_loss_amount = $data_list['data_total']['total_report_loss_amount']??0;

        $order_list = $data_list['order_list'];
        $supplier   = $data_list['supplier'];
        $values     = [];

        // 转换成 固定格式的 报损数据
        foreach($order_list as $order_value){
            $order_info = $order_value['order_info'];

            foreach($order_info as $item_value){
                $purchase_number    = $order_value['purchase_number'];
                $sku                = $item_value['sku'];
                $order_cancel_list  = $this->Purchase_order_determine_model->get_order_cancel_list($purchase_number,$sku);
                $cancel_ctq         = isset($order_cancel_list[$purchase_number.'-'.$sku])?$order_cancel_list[$purchase_number.'-'.$sku]:0; //已取消数量

                $report_status_unfinished = $this->Report_loss_model->unfinished_loss_status($purchase_number,$item_value['demand_number']);
                if($report_status_unfinished){
                    $this->error_json("备货单 ".$item_value['demand_number']." 处于报损中，请在报损列表中完结报损后再申请");
                }

                $value = [
                    'supplier_code'       => $supplier['supplier_code'],
                    'supplier_name'       => $supplier['supplier_name'],
                    'purchase_number'     => $order_value['purchase_number'],
                    'demand_number'       => $item_value['demand_number'],
                    'product_img_url'     => erp_sku_img_sku($item_value['product_img_url']),
                    'sku'                 => $item_value['sku'],
                    'product_name'        => $item_value['product_name'],
                    'purchase_unit_price' => $item_value['purchase_unit_price'],
                    'purchase_amount'     => $item_value['confirm_amount'],
                    'confirm_amount'      => $item_value['upselft_amount'],
                    'upselft_amount'      => $item_value['upselft_amount'],
                    'freight'             => $item_value['freight'],
                    'process_cost'        => $item_value['process_cost'],
                    'cancel_ctq'          => $cancel_ctq,

                    // 报损数量信息（和取消数据保持一致，不可变）
                    'loss_amount'         => $item_value['application_cancel_ctq'],
                    'loss_process_cost'   => $item_value['process_cost'],
                    'loss_freight'        => $item_value['freight'],
                    'loss_totalprice'     => $item_value['change_to_report_loss'],
                ];

                $values[] = $value;
            }
        }

        if ($values) {
            $lossResponsibleParty = getReportlossResponsibleParty();
            $data = ['values' => $values, 'down_box_list' => ['lossResponsibleParty' => $lossResponsibleParty],'total_report_loss_amount'=>$total_report_loss_amount];
            $this->success_json($data);
        } else {
            $this->error_json('获取报损数据失败');
        }

    }

    /**
     * 保存取消未到货转报损数据
     * @author Jolon 2020/03/03
     */
    public function set_unarrived_to_loss(){
        $this->load->helper('user');

        $cancel_id = $this->input->get_post('cancel_id');
        $data      = $this->input->get_post('data');
        $remark    = $this->input->get_post('remark');
        $data      = json_decode($data,true);
        $stamp_number = time();

        //获取采购单明细表id
        $audit_status = $this->Purchase_order_determine_model->purchase_db->select('audit_status')->where('id', $cancel_id)->get('purchase_order_cancel')->row_array();

        if($audit_status['audit_status'] != CANCEL_AUDIT_STATUS_CGBH and $audit_status['audit_status'] != CANCEL_AUDIT_STATUS_CFBH){
            $this->error_json("在“取消未到货状态=采购驳回/财务驳回”时才能操作");
        }

        try {
            $data_list = $this->Purchase_order_determine_model->get_cancel_unarrived_goods_examine_list($cancel_id);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
        $data_total = $data_list['data_total'];

        $error_msg = '';
        $add_datas = [];
        if(!empty($data)){
            $application_loss_fright       = [];//申请报损的运费,按采购单汇总
            $application_loss_process_cost = [];//申请报损的加工费,按采购单汇总
            $loss_cancel_fright            = [];//历史报损的运费,按采购单汇总
            $loss_cancel_process_cost      = [];//历史报损的加工费,按采购单汇总
            $purchase_number_fright        = [];//采购单总运费
            $purchase_number_process_cost  = [];//采购单总加工费
            $history_cancel_fright         = [];//历史取消的运费,按采购单汇总
            $history_cancel_process_cost   = [];//历史取消的加工费,按采购单汇总

            foreach($data as $val){
                $error_str = '采购单:'.$val['purchase_number'].' sku:'.$val['sku'];
                if(!is_numeric($val['loss_amount']) || $val['loss_amount']<=0){
                    continue;
                }else{
                    if(!isset($val['responsible_party']) or empty($val['responsible_party'])){
                        $error_msg.=$error_str.' 责任承担方式必填';
                        break;
                    }
                    if (!is_two_decimal(isset($val['loss_freight'])?$val['loss_freight']:0)){
                        $error_msg.=$error_str.' 报损运费小数最多只能为两位';
                        break;
                    }
                    if (!is_two_decimal(isset($val['loss_process_cost'])?$val['loss_process_cost']:0)){
                        $error_msg.=$error_str.' 报损加工费小数最多只能为两位';
                        break;
                    }
                    if (!is_two_decimal($val['loss_totalprice'])){
                        $error_msg.=$error_str.' 报损金额小数最多只能为两位';
                        break;
                    }

                    //将同一个采购单的申请的报损运费相加
                    if (!isset($application_loss_fright[$val['purchase_number']])){
                        $application_loss_fright[$val['purchase_number']]=isset($val['loss_freight'])?$val['loss_freight']:0;
                    }/*else{
                        $application_loss_fright[$val['purchase_number']]+=isset($val['loss_freight'])?$val['loss_freight']:0;
                    }*/
                    //将同一个采购单的申请的报损加工费相加
                    if (!isset($application_loss_process_cost[$val['purchase_number']])){
                        $application_loss_process_cost[$val['purchase_number']]=isset($val['loss_process_cost'])?$val['loss_process_cost']:0;
                    }/*else{
                        $application_loss_process_cost[$val['purchase_number']]+=isset($val['loss_process_cost'])?$val['loss_process_cost']:0;
                    }*/

                    //查询是否有报损运费
                    $purchase_order_reportloss_fright = $this->Purchase_order_determine_model->purchase_db->select('loss_freight,loss_process_cost')
                        ->where('pur_number', $val['purchase_number'])
                        ->group_by('pur_number,stamp_number')
                        ->get('purchase_order_reportloss')
                        ->result_array();

                    if (!empty($purchase_order_reportloss_fright)){
                        //将报损取消的运费放入数组
                        if (!isset($loss_cancel_fright[$val['purchase_number']])){
                            $loss_cancel_fright_total = array_column($purchase_order_reportloss_fright,'loss_freight');
                            $loss_cancel_fright_total = array_sum($loss_cancel_fright_total);
                            $loss_cancel_fright[$val['purchase_number']]=$loss_cancel_fright_total;
                        }
                        //将报损取消的加工费放入数组
                        if (!isset($loss_cancel_process_cost[$val['purchase_number']])){
                            $loss_cancel_process_cost_total = array_column($purchase_order_reportloss_fright,'loss_process_cost');
                            $loss_cancel_process_cost_total = array_sum($loss_cancel_process_cost_total);
                            $loss_cancel_process_cost[$val['purchase_number']]=$loss_cancel_process_cost_total;
                        }
                    }else{
                        $loss_cancel_fright[$val['purchase_number']]=0;
                        $loss_cancel_process_cost[$val['purchase_number']]=0;
                    }

                    $total_freight_discount = $this->Purchase_order_determine_model->get_total_freight_discount_true($val['purchase_number']);//查询总运费和总优惠

                    if (!isset($purchase_number_fright[$val['purchase_number']])){
                        $purchase_number_fright[$val['purchase_number']]=$total_freight_discount['total_freight'];
                        $purchase_number_discount[$val['purchase_number']]=$total_freight_discount['total_discount'];
                        $purchase_number_process_cost[$val['purchase_number']]=$total_freight_discount['total_process_cost'];
                    }

                    $items_info = $this->Purchase_order_items_model->get_item($val['purchase_number'],$val['sku'],true);
                    $freight_discount= $this->Purchase_order_determine_model->get_freight_discount($val['purchase_number']);  //历史取消的运费及优惠额
                    //将历史取消的运费优惠相加
                    if (!isset($history_cancel_fright[$val['purchase_number']])){
                        $history_cancel_fright[$val['purchase_number']]=$freight_discount['history_freight'];
                        $history_cancel_process_cost[$val['purchase_number']]=$freight_discount['history_process_cost'];
                    }/*else{
                        $history_cancel_fright[$val['purchase_number']]+=$freight_discount['history_freight'];
                        $history_cancel_process_cost[$val['purchase_number']]+=$freight_discount['history_process_cost'];
                    }*/


                    $add_datas[] = [
                        'bs_number'               => get_prefix_new_number('BS'.date('Ymd'),1,3),
                        'pur_number'              => $val['purchase_number'],
                        'sku'                     => $val['sku'],
                        'demand_number'           => $val['demand_number'],
                        'product_name'            => $val['product_name'],
                        'purchase_amount'         => $val['purchase_amount'],
                        'price'                   => $val['price'],
                        'confirm_amount'          => $val['confirm_amount'],
                        'loss_amount'             => $val['loss_amount'],
                        'loss_totalprice'         => isset($val['loss_totalprice']) ? $val['loss_totalprice'] : $val['loss_amount'] * $val['price'],
                        'loss_freight'            => isset($val['loss_freight']) ? $val['loss_freight'] : 0,
                        'loss_process_cost'       => isset($val['loss_process_cost']) ? $val['loss_process_cost'] : 0,
                        'responsible_user'        => isset($val['responsible_user']) ? $val['responsible_user'] : '',
                        'responsible_user_number' => isset($val['responsible_user_number']) ? $val['responsible_user_number'] : '',
                        'responsible_party'       => isset($val['responsible_party']) ? $val['responsible_party'] : '',
                        'apply_time'              => date('Y-m-d H:i:s'),
                        'apply_person'            => getActiveUserName(),
                        'remark'                  => $remark,
                        'relative_superior_number'=> $data_total['cancel_number'],
                        'is_reduce'               => 1,// 无需重复操作
                        'is_lock'                 => 1,// 无需重复操作
                        'is_push_wms'             => 1,// 无需重复操作
                        'refund_status'           => 1,// 无需重复操作
                        'stamp_number'            => $stamp_number

                    ];
                }
            }

            foreach ($application_loss_fright as $purchase_number => $value){
                //判断申请取消运费是否>0 或<=po的总优惠额-已取消的总优惠额
                if ($application_loss_fright[$purchase_number]<0 ||
                    $application_loss_fright[$purchase_number]>($purchase_number_fright[$purchase_number]-$history_cancel_fright[$purchase_number]-$loss_cancel_fright[$purchase_number]) ){
                    $error_msg.= $purchase_number . '报损的运费额总和已超过po的总运费额-已取消的总运费额-已取消的运费，请检查后在操作报损';
                    break;
                }

                //判断申请取消加工费是否>0 或<=po的总加工费-已取消的总加工费
                if ($application_loss_process_cost[$purchase_number]<0 ||
                    $application_loss_process_cost[$purchase_number]>($purchase_number_process_cost[$purchase_number]-$history_cancel_process_cost[$purchase_number]-$loss_cancel_process_cost[$purchase_number]) ){
                    $error_msg.= $purchase_number . '报损的加工费总和已超过po的总加工费-已取消的总加工费-已取消的加工费，请检查后在操作报损';
                    break;
                }
            }

            if($add_datas and empty($error_msg)){
                $add_result = $this->Report_loss_model->save_report_loss($add_datas);
                if($add_result !== true){
                    $error_msg = $add_result;
                    $this->error_json($error_msg);
                }else{
                    $order_numberr  = array_column($add_datas,'pur_number');
                    foreach($order_numberr as $order_number){
                        $this->Purchase_order_model->change_status($order_number, PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT);
                    }
                    $this->Purchase_order_unarrived_model->change_order_cancel(CANCEL_AUDIT_STATUS_YZBS, $cancel_id);

                    $this->success_json();
                }
                $this->success_json();
            }else{
                $this->error_json($error_msg);
            }
        }else{
            $error_msg .='传入参数有误';
            $this->success_json($error_msg);
        }

    }

    /**
     * 获取报损转取消未到货数据
     * @author Jolon 2020/03/03
     */
    public function get_loss_to_unarrived(){
        $loss_ids = $this->input->post_get('loss_ids'); //勾选数据

        $supplier = [];
        $cancel_data_list = [];
        $loss_data_list = [];
        foreach($loss_ids as $value_id){
            $loss_data = $this->Report_loss_model->get_one_report_loss($value_id);

            if(empty($loss_data)){
                $this->error_json('存在非法的报损单');
            }

            if($loss_data['status'] != REPORT_LOSS_STATUS_MANAGER_REJECTED and $loss_data['status'] != REPORT_LOSS_STATUS_FINANCE_REJECTED){
                $this->error_json('报损状态=采购经理已驳回、财务经理已驳回时才能转取消未到货');
            }

            $cancel_result = $this->Warehouse_storage_record_model->get_cancel_info($loss_data['pur_number'],$loss_data['sku']);
            foreach($cancel_result as $cancel_value){
                if(in_array($cancel_value['audit_status'],[CANCEL_AUDIT_STATUS_CG,CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT])
                or (in_array($cancel_value['audit_status'],[CANCEL_AUDIT_STATUS_CGBH,CANCEL_AUDIT_STATUS_CFBH]) and $cancel_value['is_edit'] == 2)){

                    $this->error_json("备货单".$loss_data['demand_number']."处于取消中，不允许申请");
                }
            }

            $order_info = $this->Purchase_order_model->get_one($loss_data['pur_number'],false);
            $supplier[$order_info['supplier_code']] = $order_info['supplier_name'];


            $total_order_amount = $this->Purchase_order_determine_model->get_total_order($loss_data['pur_number']);
            $total_confirm_amount = $this->Purchase_order_determine_model->get_total_order($loss_data['pur_number']);

            $total_freight_discount = $this->Purchase_order_determine_model->get_total_freight_discount_true($loss_data['pur_number']);//查询总运费和总优惠


            $loss_data_list[$loss_data['pur_number']][$loss_data['sku']] = [
                'loss_id'           => $loss_data['id'],
                'loss_amount'       => $loss_data['loss_amount'],
                'loss_freight'      => $loss_data['loss_freight'],
                'loss_process_cost' => $loss_data['loss_process_cost'],
                'loss_totalprice'   => $loss_data['loss_totalprice'],
            ];

            if(isset($cancel_data_list[$loss_data['pur_number']])) continue;

            $cancel_data_list[$loss_data['pur_number']] = [
                'purchase_number'      => $loss_data['pur_number'], //订单号
                'purchase_type_id'     => getPurchaseType($order_info['purchase_type_id']), //业务线
                'total_order_amount'   => isset($total_order_amount['total_order']) ? $total_order_amount['total_order'] : 0,//订单金额
                'total_freight'        => isset($total_freight_discount['total_freight']) ? $total_freight_discount['total_freight'] : 0,//订单运费
                'total_discount'       => isset($total_freight_discount['total_discount']) ? $total_freight_discount['total_discount'] : 0,//订单优惠额
                'total_process_cost'   => isset($total_freight_discount['total_process_cost']) ? $total_freight_discount['total_process_cost'] : 0,//订单加工费
                'total_confirm_amount' => isset($total_confirm_amount['total_confirm_amount']) ? $total_confirm_amount['total_confirm_amount'] : 0,//订单数量
                'order_info'           => $this->Purchase_order_determine_model->get_order_info($loss_data['pur_number']),
            ];

        }

        if(count($supplier) > 1){
            $this->error_json("必须为同一供应商");
        }

        foreach($cancel_data_list as $purchase_number => $order_value){
            foreach($order_value['order_info'] as $item_key => $item_value){
                if(isset($loss_data_list[$purchase_number][$item_value['sku']])){
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_loss_id'] = $loss_data_list[$purchase_number][$item_value['sku']]['loss_id'];
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_qty'] = $loss_data_list[$purchase_number][$item_value['sku']]['loss_amount'];
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_freight'] = $loss_data_list[$purchase_number][$item_value['sku']]['loss_freight'];
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_process_cost'] = $loss_data_list[$purchase_number][$item_value['sku']]['loss_process_cost'];
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_totalprice'] = $loss_data_list[$purchase_number][$item_value['sku']]['loss_totalprice'];
                }else{
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_loss_id'] = 0;
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_qty'] = 0;
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_freight'] = 0;
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_process_cost'] = 0;
                    $cancel_data_list[$purchase_number]['order_info'][$item_key]['application_cancel_totalprice'] = 0;
                }
            }
        }

        $data_list=[
            'supplier'=> ['supplier_code' => key($supplier),'supplier_name' => current($supplier)],
            'order_list'=>$cancel_data_list,
        ];

        $this->success_json($data_list);
    }

    /**
     * 保存报损转取消未到货数据
     * @author Jolon 2020/03/03
     */
    public function set_loss_to_unarrived(){
        $items_ids       = $this->input->post_get('ids'); //采购单明细ID
        $create_note     = $this->input->post_get('create_note');
        $total_cancelled = $this->input->post_get('total_cancelled');
        $total_freight   = $this->input->post_get('total_freight');
        $total_discount  = $this->input->post_get('total_discount');
        $total_process_cost  = $this->input->post_get('total_process_cost');
        $total_price     = $this->input->post_get('total_price');


        $param_cancel_loss_id  = $this->input->post_get('application_cancel_loss_id');
        $param_cancel_ctq      = $this->input->post_get('application_cancel_ctq');
        $param_cancel_freight  = $this->input->post_get('freight');
        $param_cancel_discount = $this->input->post_get('discount');
        $param_cancel_process_cost = $this->input->post_get('process_cost');

        if(  empty($total_cancelled) || empty($create_note) || empty($items_ids)){
            $this->error_json('部分参数缺失');
        }


        $this->load->helper('status_finance');
        $this->load->model('purchase_order_cancel_model', '', false, 'purchase');
        $this->load->model('Reject_note_model');
        try {

            $ids = explode(',', $items_ids);
            $ids = array_unique(array_filter($ids));
            if(empty($ids)){
                throw new Exception('参数id,不存在');
            }

            $data                        = [];
            $application_cancel_fright   = [];//申请取消的运费,按采购单汇总
            $application_cancel_discount = [];//申请取消的优惠,按采购单汇总
            $application_cancel_process_cost = [];//申请取消的加工费,按采购单汇总
            $history_cancel_fright       = [];//历史取消的运费,按采购单汇总
            $history_cancel_discount     = [];//历史取消的优惠,按采购单汇总
            $history_cancel_process_cost = [];//历史取消的加工费,按采购单汇总
            $loss_cancel_fright          = [];//报损取消的运费,按采购单汇总
            $loss_cancel_process_cost    = [];//报损取消的运费,按采购单汇总
            $purchase_number_fright      = [];//采购单总运费
            $purchase_number_discount    = [];//采购单总优惠
            $purchase_number_process_cost= [];//采购单总加工费

            foreach ($ids as $id) {
                if (! isset($param_cancel_loss_id[$id])){
                    throw new Exception('报损单不匹配');
                }else{
                    if(empty($param_cancel_loss_id[$id]) or $param_cancel_loss_id[$id] == 0){
                        continue;
                    }
                    $loss_data = $this->Report_loss_model->get_one_report_loss($param_cancel_loss_id[$id]);
                    if(empty($loss_data)){
                        throw new Exception('存在非法的报损单');
                    }
                    if($loss_data['status'] != REPORT_LOSS_STATUS_MANAGER_REJECTED and $loss_data['status'] != REPORT_LOSS_STATUS_FINANCE_REJECTED){
                        throw new Exception('报损状态=采购经理已驳回、财务经理已驳回时才能转取消未到货');
                    }
                }

                if (isset($param_cancel_freight[$id]) && !is_two_decimal($param_cancel_freight[$id])){
                    throw new Exception('取消运费小数最多只能为两位');
                }

                if (isset($param_cancel_discount[$id]) && !is_two_decimal($param_cancel_discount[$id])){
                    throw new Exception('取消优惠小数最多只能为两位');
                }

                if (isset($param_cancel_process_cost[$id]) && !is_two_decimal($param_cancel_process_cost[$id])){
                    throw new Exception('取消加工费小数最多只能为两位');
                }

                //获取对应的采购单号及sku
                $order_items = $this->Purchase_order_determine_model->purchase_db
                    ->select('purchase_number,purchase_unit_price,sku,freight,discount')
                    ->where('id', (int)$id)
                    ->get('purchase_order_items')
                    ->row_array();

                $total_freight_discount = $this->Purchase_order_determine_model->get_total_freight_discount_true($order_items['purchase_number']);//查询总运费和总优惠

                if (!isset($purchase_number_fright[$order_items['purchase_number']])){
                    $purchase_number_fright[$order_items['purchase_number']]=$total_freight_discount['total_freight'];
                    $purchase_number_discount[$order_items['purchase_number']]=$total_freight_discount['total_discount'];
                    $purchase_number_process_cost[$order_items['purchase_number']]=$total_freight_discount['total_process_cost'];
                }

                $freight_discount = $this->Purchase_order_determine_model->get_freight_discount($order_items['purchase_number']);  //历史取消的运费及优惠额
                $freight_ben      = isset($param_cancel_freight[$id]) ? $param_cancel_freight[$id] : 0; //本次取消的运费;
                $discount_ben     = isset($param_cancel_discount[$id]) ? $param_cancel_discount[$id] : 0; //本次取消的优惠额;
                $process_cost_ben = isset($param_cancel_process_cost[$id]) ? $param_cancel_process_cost[$id] : 0; //本次取消的加工费

                //将同一个采购单的申请的取消运费优惠相加
                if(!isset($application_cancel_fright[$order_items['purchase_number']])){
                    $application_cancel_fright[$order_items['purchase_number']]   = $freight_ben;
                    $application_cancel_discount[$order_items['purchase_number']] = $discount_ben;
                    $application_cancel_process_cost[$order_items['purchase_number']] = $process_cost_ben;
                }else{
                    $application_cancel_fright[$order_items['purchase_number']]   += $freight_ben;
                    $application_cancel_discount[$order_items['purchase_number']] += $discount_ben;
                    $application_cancel_process_cost[$order_items['purchase_number']] += $process_cost_ben;
                }

                //将历史取消的运费优惠相加
                if(!isset($history_cancel_fright[$order_items['purchase_number']])){
                    $history_cancel_fright[$order_items['purchase_number']]   = $freight_discount['history_freight'];
                    $history_cancel_discount[$order_items['purchase_number']] = $freight_discount['history_discount'];
                    $history_cancel_process_cost[$order_items['purchase_number']] = $freight_discount['history_process_cost'];
                }/*else{
                    $history_cancel_fright[$order_items['purchase_number']]   += $freight_discount['history_freight'];
                    $history_cancel_discount[$order_items['purchase_number']] += $freight_discount['history_discount'];
                    $history_cancel_process_cost[$order_items['purchase_number']] += $freight_discount['history_process_cost'];
                }*/

                //查询是否有报损运费
                $purchase_order_reportloss_fright = $this->Purchase_order_determine_model->purchase_db->select('loss_freight,loss_process_cost')
                    ->where('pur_number', $order_items['purchase_number'])
                    ->where('sku', $order_items['sku'])
                    ->where('id !=',intval($loss_data['id']))
                    ->get('purchase_order_reportloss')
                    ->row_array();

                if(!empty($purchase_order_reportloss_fright)){
                    //将报损的运费相加
                    if(!isset($loss_cancel_fright[$order_items['purchase_number']])){
                        $loss_cancel_fright[$order_items['purchase_number']] = $purchase_order_reportloss_fright['loss_freight'];
                    }/*else{
                        $loss_cancel_fright[$order_items['purchase_number']] += $purchase_order_reportloss_fright['loss_freight'];
                    }*/
                    //将报损的加工费相加
                    if(!isset($loss_cancel_process_cost[$order_items['purchase_number']])){
                        $loss_cancel_process_cost[$order_items['purchase_number']] = $purchase_order_reportloss_fright['loss_process_cost'];
                    }/*else{
                        $loss_cancel_process_cost[$order_items['purchase_number']] += $purchase_order_reportloss_fright['loss_process_cost'];
                    }*/
                }else{
                    $loss_cancel_fright[$order_items['purchase_number']] = 0;
                    $loss_cancel_process_cost[$order_items['purchase_number']] = 0;
                }
            }



            foreach ($application_cancel_discount as $purchase_number => $value){
                //判断申请取消优惠是否>0 或<=po的总优惠额-已取消的总优惠额
                if ($value<0 || $value>($purchase_number_discount[$purchase_number]-$history_cancel_discount[$purchase_number]) ){
                    $errormsg = '采购单:' . $purchase_number . '取消的优惠额总和已超过po的总优惠额-已取消的总优惠额，请检查后在操作取消未到货';
                    throw new Exception($errormsg);
                }

                //判断申请取消运费是否>0 或<=po的总运费-已取消的总运费
                if ($application_cancel_fright[$purchase_number]<0 ||
                    bccomp($application_cancel_fright[$purchase_number],($purchase_number_fright[$purchase_number]-$history_cancel_fright[$purchase_number]-$loss_cancel_fright[$purchase_number]),3) > 0  ){
                    $errormsg = '采购单:' . $purchase_number . '取消的运费额总和已超过po的总运费额-已取消的总运费额-已报损的运费，请检查后在操作取消未到货';
                    throw new Exception($errormsg);
                }

                //判断申请取消加工费是否>0 或<=po的总加工费-已取消的总加工费
                if ($application_cancel_process_cost[$purchase_number]<0 ||
                    bccomp($application_cancel_process_cost[$purchase_number],($purchase_number_process_cost[$purchase_number]-$history_cancel_process_cost[$purchase_number]-$loss_cancel_process_cost[$purchase_number]),3) > 0  ){
                    $errormsg = '采购单:' . $purchase_number . '取消的加工费总和已超过po的总加工费-已取消的总加工费-已报损的加工费，请检查后在操作取消未到货';
                    throw new Exception($errormsg);
                }
            }

            $this->Purchase_order_determine_model->purchase_db->trans_begin();
            //记录采购单-取消未到货数量-主表
            $cancel_number = $this->Prefix_number_model->get_prefix_new_number('QX', 1, 4);
            $cancel_number = "QX".date('Ymd').str_replace('QX', '', $cancel_number);
            $freights      = $total_freight; //取消的运费
            $discounts     = $total_discount; //取消的实际总金额
            $process_costs = $total_process_cost; //取消的实际总加工费
            $total_prices  = $total_price; //取消的实际总金额
            $order_cancel  = [
                'cancel_number'    => $cancel_number, //取消未到货编码
                'create_user_id'   => getActiveUserId(), //审请人ID
                'create_user_name' => getActiveUserName(), //申请人名称
                'create_note'      => $create_note, //申请备注
                'create_time'      => date('Y-m-d H:i:s'), //申请时间
                'cancel_ctq'       => $total_cancelled,//取消数量
                'freight'          => $freights, //取消的运费
                'discount'         => $discounts, //取消的优惠额
                'process_cost'     => $process_costs, //取消的加工费
                'total_price'      => $total_prices, //取消的实际总金额
                'product_money'    => ($total_prices - $freights + $discounts - $process_costs), //取消的商品总金额
            ];

            $cancel_inset = $this->Purchase_order_determine_model->purchase_db->insert('purchase_order_cancel', $order_cancel);
            if (empty($cancel_inset)) {
                throw new Exception('记录采购单-取消未到货数量-主表失败');
            }
            $cancel_id = $this->Purchase_order_determine_model->purchase_db->insert_id('purchase_order_cancel');


            //记录采购单-取消未到货数量-子表
            $purchase_number_list = $this->Purchase_order_determine_model->get_purchase_number_list($ids, 'purchase_number');
            $sku_list             = $this->Purchase_order_determine_model->get_purchase_number_list($ids, 'sku');
            $purchase_unit_price_list = $this->Purchase_order_determine_model->get_purchase_number_list($ids, 'purchase_unit_price');

            foreach ($ids as $val) {
                $application_cancel_ctq_s = isset($param_cancel_ctq[$val]) ? $param_cancel_ctq[$val] : 0;
                $loss_data = $this->Report_loss_model->get_one_report_loss($param_cancel_loss_id[$val]);
                if ($application_cancel_ctq_s > 0) {
                    //记录之前的采购单状态 已防驳回，返回原状态
                    $purchase_unit_price = isset($purchase_unit_price_list[$val]) ? $purchase_unit_price_list[$val] : 0;
                    $order_number_y      = isset($purchase_number_list[$val]) ? $purchase_number_list[$val] : '';
                    $order_status        = $this->Purchase_order_determine_model->purchase_db->select('purchase_order_status')->where('purchase_number', $order_number_y)->get('purchase_order')->row_array();
                    if(empty($order_status)){
                        throw new Exception('采购单,不存在');
                    }

                    $cancel_detail = [
                        'cancel_id'             => $cancel_id, //取消未到货主表的id
                        'items_id'              => $val, //采购单明细表id
                        'purchase_number'       => $order_number_y, //采购单号
                        'sku'                   => isset($sku_list[$val]) ? $sku_list[$val] : '', //sku
                        'cancel_ctq'            => $application_cancel_ctq_s, //取消数量
                        'freight'               => isset($param_cancel_freight[$val]) ? $param_cancel_freight[$val] : 0, //取消运费
                        'discount'              => isset($param_cancel_discount[$val]) ? $param_cancel_discount[$val] : 0, //取消优惠额
                        'process_cost'          => isset($param_cancel_process_cost[$val]) ? $param_cancel_process_cost[$val] : 0, //取消加工费
                        'item_total_price'      => $application_cancel_ctq_s * $purchase_unit_price, //取消金额(取消数量*单价)
                        'purchase_order_status' => isset($order_status['purchase_order_status']) ? $order_status['purchase_order_status'] : 0,
                        'relative_superior_number' => $loss_data['bs_number']
                    ];

                    $cancel_detail_insert = $this->Purchase_order_determine_model->purchase_db->insert('purchase_order_cancel_detail', $cancel_detail);
                    if (empty($cancel_detail_insert)) {
                        throw new Exception('采购单-取消未到货数量-子表记录失败');
                    }
                    $this->Purchase_order_model->change_status($order_number_y, PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT);

                    $purchase_number_order[] = $order_number_y;
                }

                $edit_data=[
                    'status'=>REPORT_LOSS_STATUS_YZCANCEL,
                ];
                $this->Purchase_order_determine_model->purchase_db->where('id',$param_cancel_loss_id[$val])->update('purchase_order_reportloss',$edit_data);
            }

            $this->Purchase_order_determine_model->purchase_db->trans_commit();

            $this->success_json('保存成功');

        } catch (Exception $exc) {
            
            $this->Purchase_order_determine_model->purchase_db->trans_rollback();
            $this->error_json($exc->getMessage());
        }

    }

}