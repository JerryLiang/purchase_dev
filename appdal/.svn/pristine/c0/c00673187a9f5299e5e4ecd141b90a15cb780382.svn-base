<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 未到货控制器
 */
class Puerchase_unarrived extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_order_model');
        $this->load->model('purchase_order_items_model');
        $this->load->model('purchase_order_determine_model');
        $this->load->helper('status_finance');
    }

    /**
     * 取消未到货列表
     * @author luxu
     * /purchase/puerchase_unarrived/cancel_unarrived_goods_examine_down
     */
    public function cancel_unarrived_goods_examine_down(){

        $params=[
            'cancel_number'=>$this->input->get_post('cancel_number'),//申请编码
            'sku'=>$this->input->get_post('sku'),//申请编码
            'purchase_number'=>$this->input->get_post('purchase_number'),//采购单号
            'create_user_id'=>$this->input->get_post('create_user_id'),//申请人
            'audit_status'=>$this->input->get_post('audit_status'),//取消未到货状态
            'create_time_start'=>$this->input->get_post('create_time_start'),//申请时间开始
            'create_time_end'=>$this->input->get_post('create_time_end'),//申请时间结束
            'serial_number' =>$this->input->get_post('serial_number'),//收款流水号
            'purchase_type_id' =>$this->input->get_post('purchase_type_id'),//业务线
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'cancel_price_min' => $this->input->get_post('cancel_price_min'),// 取消金额最小值
            'cancel_price_max' => $this->input->get_post('cancel_price_max'),// 取消金额最大值
            'completed_time_start' => $this->input->get_post('completed_time_start'),// 退款完成时间开始
            'completed_time_end' => $this->input->get_post('completed_time_end'),// 退款完成时间结束
            'relative_superior_number' => $this->input->get_post('relative_superior_number'),// 关联的报损编号
            'pai_number' => $this->input->get_post('pai_number'),// 拍单号(1-≠0)
            'apply_amount' => $this->input->get_post('apply_amount'),//1688退款金额（1-≠0）
            'source' => $this->input->get_post('source'),//采购来源（1-合同，2-网采）
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
            'buyer_id' => $this->input->get_post('buyer_id'),// 采购员
        ];

        $ids = $this->input->get_post('ids');
        if(!empty($ids)){

            $params['ids'] =$ids;
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
        $this->load->model('system/Data_control_config_model');
        $data_list= $this->purchase_order_determine_model->get_cencel_list($params,0,1,1);
        $total = $data_list['page_data']['total'];
        try {
            $ext = 'csv';
            $result = $this->Data_control_config_model->insertDownData($params, 'UNARRIVED', '取消未到货管理', getActiveUserName(), $ext, $total);
        } catch (Exception $exp) {
            $this->error_json($exp->getMessage());
        }
        if ($result) {
            $this->success_json("添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }
    }
    /**
     * 取消未到货列表
     * @author harvin
     * /purchase/puerchase_unarrived/cencel_lits
     */
    public function cencel_lits(){
        $params=[
            'cancel_number'=>$this->input->get_post('cancel_number'),//申请编码
            'sku'=>$this->input->get_post('sku'),//申请编码
            'purchase_number'=>$this->input->get_post('purchase_number'),//采购单号
            'create_user_id'=>$this->input->get_post('create_user_id'),//申请人
            'audit_status'=>$this->input->get_post('audit_status'),//取消未到货状态
            'create_time_start'=>$this->input->get_post('create_time_start'),//申请时间开始
            'create_time_end'=>$this->input->get_post('create_time_end'),//申请时间结束
            'serial_number' =>$this->input->get_post('serial_number'),//收款流水号
            'purchase_type_id' =>$this->input->get_post('purchase_type_id'),//业务线
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'cancel_price_min' => $this->input->get_post('cancel_price_min'),// 取消金额最小值
            'cancel_price_max' => $this->input->get_post('cancel_price_max'),// 取消金额最大值
            'completed_time_start' => $this->input->get_post('completed_time_start'),// 退款完成时间开始
            'completed_time_end' => $this->input->get_post('completed_time_end'),// 退款完成时间结束
            'relative_superior_number' => $this->input->get_post('relative_superior_number'),// 关联的报损编号
            'pai_number' => $this->input->get_post('pai_number'),// 拍单号(1-≠0)
            'apply_amount' => $this->input->get_post('apply_amount'),//1688退款金额（1-≠0）
            'source' => $this->input->get_post('source'),//采购来源（1-合同，2-网采）
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
            'upload_files_time' => $this->input->get_post('upload_files_time'),// 上传截图时间
            'refund_price' => $this->input->get_post('refund_price'),// 退款金额
            'cancel_source' => $this->input->get_post('cancel_source'),// 取消操作类型
            'buyer_id' => $this->input->get_post('buyer_id'),// 采购员
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
        $data_list= $this->purchase_order_determine_model->get_cencel_list($params,$offsets,$limit,$page);
        $this->success_json($data_list);
        
    }
    /**
     * 取消未到货显示
     * @author harvin 2019-1-8
     * /purchase/puerchase_unarrived/cancel_unarrived_goods
     * * */
    public function cancel_unarrived_goods() {
        $ids = $this->input->post_get('ids'); //勾选数据
        $is_plan_cancel = $this->input->post_get('is_plan_cancel');//发运管理-计划申请取消的数据 1

        //转化数组
        $ids = explode(',', $ids);
        $ids = array_unique(array_filter($ids));
        if (empty($ids)) {
            $this->error_json('参数错误');
        }
        $plan_cancel_ids = '';
        if (isset($is_plan_cancel) && $is_plan_cancel == 1){
            $plan_cancel_ids = $ids;//勾选的计划申请取消的数据

            $this->load->model('Shipment_cancel_list_model', '', false, 'purchase_shipment');
            $result = $this->Shipment_cancel_list_model->get_order_items_ids($ids);

            if (empty($result)){
                $this->error_json('未查询到对应的数据');
            }
            //根据采order_items表id获取详情
            $ids = array_column($result,'id');
            if (empty($ids)) {
                $this->error_json('参数错误');
            }
        }


        //10.部分到货等待剩余到货 7.等待到货
        $status_array = [PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL];
        $change_status = [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT];//信息修改状态

        $is_change = $this->purchase_order_determine_model->is_order_change_status($ids, $change_status);
        if ($is_change) {
            $this->error_json("PO:{$is_change}状态是“信息修改驳回”或者“信息修改待审核”的，不允许点击，信息修改通过后再申请");
        }

        $bool = $this->purchase_order_determine_model->order_status($ids, $status_array);
        if (!$bool) {
            $this->error_json('只能是部分到货等待剩余到货及等待到货，才能操作');
        }
        //请款中的订单状态不可以申请(判断是否有在申请请款中)
         $pay_status=$this->purchase_order_determine_model->order_pay_get($ids);
        if($pay_status){
             $this->error_json('请款中的订单不可以申请取消未到货');
        }   
       
        //需验证入库数量>=采购数量，如果是，那么不允许操作
        $temp=$this->purchase_order_determine_model->warehouse_results_get($ids);
        if(!empty($temp)){
              $errormess= '采购单号及sku:'.implode(',', $temp).',请与仓库确定已入库数量后再取消未到货';
              $this->error_json($errormess);
        }
        
        //如果还有上一次取消未完结的存在，那么不允许进行该操作
        $cencel_status=$this->purchase_order_determine_model->get_cancel_status($ids);
        if(!empty($cencel_status)){
            $errormess= '采购单号及sku'.implode(',', $cencel_status).'取消未到货状态未审核完毕或有驳回单，不允许再次申请';
            $this->error_json($errormess);
        }
       
        //判断订单号的供应商代码是否一致
        $supp_code= $this->purchase_order_determine_model->get_supplier_sy($ids);
        if($supp_code){
           $this->error_json('请对同一供应商的订单进行批量取消');
        }

        //获取采购单号
        $order_items = $this->purchase_order_items_model->get_list_by_ids($ids,'purchase_number');
        $purchase_number= array_column(isset($order_items)?$order_items:[], 'purchase_number');
        if(empty($purchase_number)) $this->error_json('采购单主表记录不存在');
        $purchase_numbers= array_unique($purchase_number);

        //判断是否是同一个采购来源或同一个合同单
        $source= $this->purchase_order_determine_model->is_same_compact_or_source($purchase_numbers);
        if ($source==1){
            $this->error_json('请对同一采购来源进行批量取消');
        }elseif ($source==2){
            $this->error_json('请对同一合同单进行批量取消');
        }

        $this->load->model('abnormal/Report_loss_model');
        $this->load->model('statement/Charge_against_records_model');

        //如果有报损中的,那么不允许进行该操作
        foreach ($purchase_numbers as $purchase_number){
            $unfinished = $this->Report_loss_model->unfinished_loss_status($purchase_number);
            if ($unfinished){
                $this->error_json('采购单:'.$purchase_number.' 报损状态未完结');
            }

            $charge_against_record = $this->Charge_against_records_model->get_ca_audit_status($purchase_number,2,[CHARGE_AGAINST_STATUE_WAITING_AUDIT,CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT]);
            if ($charge_against_record){
                $this->error_json('采购单:'.$purchase_number.' 存在退款冲销中的记录');
            }
        }

        //判断是否已经生成
//        $purchase_order_data = $this->purchase_order_model->get_purchase_order_data($purchase_numbers,'source,purchase_number,is_generate');
//
//        if(!empty($purchase_order_data)) {
//            $source_arr = array_unique(array_column($purchase_order_data, 'source'));
//
//            if(count($source_arr)== 1 and $source_arr[0]==SOURCE_COMPACT_ORDER) {
//                $is_generate_arr = array_unique(array_column($purchase_order_data, 'is_generate'));
//                if(count($is_generate_arr)!=1 or $is_generate_arr[0]!=2){
//                    $this->error_json('无合同号不允许申请,请在列表中查询【是否生成合同单】');
//                }
//            }
//        }
        //取出所需要的数据
        $data_list = $this->purchase_order_determine_model->get_cancel_unarrived_goods($ids,$is_plan_cancel,$plan_cancel_ids);
        $this->success_json($data_list);
    }

    /**
     * 保存取消未到货的操作
     * @author harvin 2019-1-11
     * /purchase/puerchase_unarrived/cancel_unarrived_goods_save
     * * */
    public function cancel_unarrived_goods_save() {
        $params = [
            'total_cancelled' => $this->input->post_get('total_cancelled'), //取消总数数量
            'total_freight'   => $this->input->post_get('total_freight'), //取消的运费
            'total_discount'  => $this->input->post_get('total_discount'), //取消的优惠额
            'total_process_cost'  => $this->input->post_get('total_process_cost'), //取消的加工费
            'total_price'     => $this->input->post_get('total_price'), //取消的总金额
            'create_note'     => $this->input->post_get('create_note'), //申请备注
            'type'            => $this->input->post_get('type'), //再次编辑 1

            'cancel_ctq'             => $this->input->post_get('cancel_ctq'),//已取消的数量 数组
            'confirm_amount'         => $this->input->post_get('confirm_amount'),//采购数量
            'purchase_unit_price'    => $this->input->post_get('purchase_unit_price'),//采购单价
            'instock_qty'            => $this->input->post_get('instock_qty'),//入库数量
            'loss_amount'            => $this->input->post_get('loss_amount'),//报损数量
            'application_cancel_ctq' => $this->input->post_get('application_cancel_ctq'),//申请中取消的数量 数组
            'freight'                => $this->input->post_get('freight'), //取消的运费 数组
            'discount'               => $this->input->post_get('discount'), //取消的优惠额 数组
            'process_cost'           => $this->input->post_get('process_cost'), //取消的加工费 数组
            'ids'                    => $this->input->post_get('ids'), //采购单明细ids
            'cancel_id'              => $this->input->post_get('cancel_id'), //再编辑取消未到货 原id
            'is_plan_cancel'         => $this->input->post_get('is_plan_cancel'),//发运管理-计划申请取消的数据 1
            'plan_cancel_ids'        => $this->input->post_get('plan_cancel_ids'),//勾选的计划申请取消的数据
            'image_list'             => $this->input->post_get('image_list'),// 附件
            'is_submit'              => $this->input->post_get('is_submit'),// 是否新申请
            'cancel_reason'          => $this->input->post_get('cancel_reason'),// 取消原因
            'estimate_time'          => $this->input->post_get('estimate_time'),// 预计供货时间
            'cancel_reason_img'      => $this->input->post_get('cancel_reason_img'),// 取消聊天截图
        ];
       
        if(empty($params['create_note']) || empty($params['ids'])){
            $this->error_json('参数不能为空');
        }

        $err = [];
        $temp_param = $this->purchase_order_determine_model->get_cancel_order_items($params);
        foreach ($temp_param as $key=>$val){
            $val['ids'] = implode(",", $val['ids']);
            $val['type'] = $params['type'];
            $val['cancel_id'] = $params['cancel_id'];
            $val['total_price'] = $val['total_price'] + $val['total_freight'] + $val['total_process_cost'] - $val['total_discount'];
            $temps = $this->purchase_order_determine_model->get_cancel_unarrived_goods_save($val);
            if(!$temps['bool'])$err[] = $temps['msg'];
        }

        if(count($err) == 0){
            $this->success_json($err,null, '取消未到货成功。');
        }
        $this->error_json(implode('', $err));
    }
    /**
     * 取消未到货的订单审核操作显示
     * @author harvin 2019-1-11
     * /purchase/puerchase_unarrived/cancel_unarrived_goods_examine_list
     * * */
    public function cancel_unarrived_goods_examine_list() {
        try {
            $id = $this->input->post_get('id'); //勾选数据
            if (empty($id)) {
                $this->error_json('参数错误');
            }
            //取出所需要的数据
            $data_list = $this->purchase_order_determine_model->get_cancel_unarrived_goods_examine_list($id);
            $this->success_json($data_list);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }

    /**
     * 取消未到货批量上传图片 34130
     */
    public function get_cancel_upload_data()
    {
        $cancel_id = $this->input->post_get('cancel_id'); //勾选数据
        if (empty($cancel_id)) {
            $this->error_json('请勾选要取消的单据！');
        }

        $data_list = $this->purchase_order_determine_model->get_cancel_upload_data($cancel_id);
        if(count($data_list) > 0)$this->success_json($data_list, [], "获取数据成功");
        $this->error_json('获取数据失败！');
    }

    /**
     * 取消未到货批量上传图片 34130
     */
    public function save_cancel_upload_data()
    {
        $cancel = $this->input->post_get('data');
        $remark = $this->input->post_get('remark');
        if (empty($cancel) || !is_array($cancel)) {
            $this->error_json('上传的数据不能为空！');
        }

        $data = $this->purchase_order_determine_model->save_cancel_upload_data($cancel, $remark);
        if($data['code'] == 1){
            $this->success_json([], [], "上传成功！");
        }elseif (!empty($data['msg'])){
            $this->error_json($data['msg']);
        }
        $this->error_json('上传失败！');
    }

    /**
     * 取消未到货驳回再编辑显示
     * @author harvin
     * @
     */
     public function cancel_unarrived_goods_edit(){
          try {
            $id = $this->input->post_get('id'); //勾选数据
            if (empty($id)) {
                $this->error_json('参数错误');
            }
            //取出所需要的数据
            $data_list = $this->purchase_order_determine_model->get_cancel_unarrived_goods_examine_list($id);
            $this->success_json($data_list);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }        
     }
    /**
     * 取消未到货审核通过及审核驳回操作
     * @author harvin 2019-1-11
     * /purchase/puerchase_unarrived/cancel_unarrived_goods_examine
     * * */
    public function cancel_unarrived_goods_examine() {
        try {
            $id = $this->input->post_get('id'); //参数ID
            $audit_note = $this->input->post_get('audit_note'); //审核备注
            $type = $this->input->post_get('type'); //1 审核通过  2.审核驳回
            if ($type != 1 and empty($audit_note)) {
                $this->error_json('驳回备注不能为空');
            }
            if (empty($id)) {
                $this->error_json('参数错误');
            }

            $idData = explode(",",$id);

            foreach($idData as $id) {
                $audit_note_temp = isset($audit_note[$id])?$audit_note[$id]: is_array($audit_note)?json_encode($audit_note): '';
                $temps = $this->purchase_order_determine_model->cancel_unarrived_goods_examine_save($id, $audit_note_temp, $type);
                if (!$temps['bool']) {
                    $this->error_json($temps['msg']);
                }
            }
            $this->success_json([],null, $temps['msg']);
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }
    /**
     * 取消未到货--详情
     * @author harvin 2019-1-11
     * /purchase/puerchase_unarrived/cancel_unarrived_info
     * **/
   public function cancel_unarrived_info(){
         try {
            $id = $this->input->post_get('id'); //勾选数据
            if (empty($id)) {
                $this->error_json('参数错误');
            }
            //取出所需要的数据
            $data_list = $this->purchase_order_determine_model->get_cancel_unarrived_goods_examine_list($id);
            $this->success_json($data_list);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        } 
   }

   /**
     * 取消未到货 上传截图预览
     * @author harvin 2019-3-23
     * /purchase/puerchase_unarrived/cancel_upload_screenshots_preview
     */
    public function cancel_upload_screenshots_preview(){
        $id = $this->input->post_get('id');

        if (empty($id)) {
            $this->error_json('参数不合法');
        }
        try {
            //判断取消未到货单，是否存在正在进行中的退款冲销
            $this->_check_charge_against_status($id);

            $params['ids'] = $id;
            $params['ali_refund'] = 1;
            $page = $this->input->get_post('offset');
            $limit = $this->input->get_post('limit');
            if (empty($page) or $page < 0)
                $page = 1;
            $limit = query_limit_range($limit);
            $offsets = ($page - 1) * $limit;
            $data_list = $this->purchase_order_determine_model->get_cencel_list($params,$offsets,$limit,$page);
            if (!empty($data_list['values'])) {
                $this->purchase_order_determine_model->getAliQueryOrderRefund($data_list['values']);
            }

            $temp=$this->purchase_order_determine_model->get_upload_preview_data($id);
            if ($temp) {
                $this->success_json($temp);
            }else{
                $this->error_json('请重新选择数据');
            }
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }

    /**
     * 判断取消未到货单，是否存在正在进行中的退款冲销
     * @param $cancel_id
     * @throws Exception
     */
    private function _check_charge_against_status($cancel_id){
        $this->load->model('statement/Charge_against_records_model');

        //根据取消id 获取取消未到货编码
        $_data = $this->purchase_order_determine_model->get_cancel_number_by_id($cancel_id);

        //退款冲销是否正在进行中（10.待采购经理审核,20.待财务经理审核）
        $charge_against_record = $this->Charge_against_records_model->get_ca_audit_status($_data['cancel_number'],2,[CHARGE_AGAINST_STATUE_WAITING_AUDIT, CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT],'record_number');

        if (!empty($charge_against_record)) {
            throw new Exception('申请编号[' . $_data['cancel_number'] . ']正处于退款冲销中，请审核结束后再操作');
        }
    }


   /**
     * 取消未到货 上传截图
     * @author harvin 2019-3-23
     * /purchase/puerchase_unarrived/cancel_upload_screenshots
     */
    public function cancel_upload_screenshots(){
        $cancel_url = $this->input->post_get('cancel_url'); //上传流水截图
        $serial_number = $this->input->post_get('serial_number'); //上传流水号
        $id = $this->input->post_get('id');
        $upload_note = $this->input->post_get('upload_note'); //上传截图备注
        if (empty($cancel_url)) {
            $this->error_json('请上传流水截图');
        }
        if(empty($serial_number)){
            $this->error_json('请输入流水号');
        }
        if (empty($id)) {
            $this->error_json('参数不合法');
        }
        try {
            $temp=$this->purchase_order_determine_model->cancel_upload_screenshots_save($cancel_url,$id,$serial_number,$upload_note);
             if ($temp) {
                $this->success_json([],null,'上传成功');
            } 
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }

    /**
     * 取消未到货 上传截图最新版
     * @author jeff 2019-9-25
     * /purchase/puerchase_unarrived/cancel_upload_screenshots
     */
    public function cancel_upload_screenshots_v2(){
        $cancel_url = $this->input->post_get('cancel_url'); //数组 上传流水截图 以采购单号为键,多个图片用逗号分隔
        $real_refund = $this->input->post_get('real_refund'); //数组 以采购单号为键,实际退款金额
        $serial_number = $this->input->post_get('serial_number'); //数组 上传流水号 多个以空格隔开
        $upload_note = $this->input->post_get('upload_note'); //上传截图备注
        $completed_time = $this->input->post_get('completed_time'); //退款完成时间
        $id = $this->input->post_get('id');
        $is_submit = $this->input->post_get('is_submit');//是否提交
        if (empty($cancel_url)) {
            $this->error_json('请上传流水截图');
        }
        if(empty($serial_number)){
            $this->error_json('请输入流水号');
        }
        if(empty($real_refund)){
            $this->error_json('请输入实际退款金额');
        }
        if (empty($id)) {
            $this->error_json('参数不合法');
        }
        try {
            //判断取消未到货单，是否存在正在进行中的退款冲销
            $this->_check_charge_against_status($id);

            $return=$this->purchase_order_determine_model->cancel_upload_screenshots_save_v2($id,$is_submit,$upload_note,$cancel_url,$serial_number,$real_refund,$completed_time);
            if ($return['success']) {
                if ($is_submit){
                    $this->success_json([],null,'上传成功');
                }else{
                    $this->success_json([],null,'保存成功');
                }

            }else{
                $this->error_json($return['message']);
            }
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }
    
    /**
     * 获取取消未到货操作日志
     * @author harvin
     */
    public function cancel_log_info(){
          try {
            $id = $this->input->post_get('id'); //勾选数据
            if (empty($id)) {
                $this->error_json('参数错误');
            }
            //取出所需要的数据
            $data_list = $this->purchase_order_determine_model->get_cancel_log_info($id);
            $this->success_json($data_list);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }   
    }
    /**
   * 显示变更采购员操作经理权限---显示
   * @author harvin 2019-12
    * http://www.caigou.com/purchase/puerchase_unarrived/change_purchaser_list
   ***/  
    public function change_purchaser_list(){
        try {
            $ids = $this->input->post_get('id'); //勾选数据
            if (empty($ids)) {
                $this->error_json('请勾选数据');
            }
            $id = explode(',', $ids);
            if (count($id) != 1) {
                $this->error_json('只能选择一条数据');
            }
            $temps = $this->purchase_order_determine_model->get_change_purchaser_list($id);
            $this->success_json($temps, null, '操作成功');
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }
 /**
   * 变更采购员操作经理权限
   *@author harvin 2019-1-12 
   * http://www.caigou.com/purchase/puerchase_unarrived/change_purchaser
   **/
    public function change_purchaser(){
     $purchase_number = $this->input->post_get('purchase_number'); //采购单号
        $buyer_id = $this->input->post_get('buyer_id'); //采购员

        if (empty($buyer_id)) {
            $this->error_json('请先选择采购员');
        }
        if (empty($purchase_number)) {
            $this->error_json('请求参数错误');
        }
        //变更采购员
        $status = $this->purchase_order_determine_model->change_purchaser_save($purchase_number, $buyer_id);
        if ($status['bool']) {
            $this->success_json([], null, $status['msg']);
        } else {
            $this->error_json($status['msg']);
        }
    } 
    // http://www.cg.com/purchase/puerchase_unarrived/test
    public function test(){
         $id=171;
         $this->load->model('Purchase_order_unarrived_model','',false,'purchase');
         $this->Purchase_order_unarrived_model->preservation_warehouse_reject($id);
    }

    /**
     * 根据收款信息拉取1688退款信息
     */
    public function refresh_ali_refund(){
        $ids = $this->input->post_get('ids'); //勾选数据

        if ($ids){
            $params['ids'] = $ids;
            $params['ali_refund'] = 1;
        }else{
            $params=[
                'cancel_number'=>$this->input->get_post('cancel_number'),//申请编码
                'sku'=>$this->input->get_post('sku'),//申请编码
                'purchase_number'=>$this->input->get_post('purchase_number'),//采购单号
                'create_user_id'=>$this->input->get_post('create_user_id'),//申请人
                'audit_status'=>$this->input->get_post('audit_status'),//取消未到货状态
                'create_time_start'=>$this->input->get_post('create_time_start'),//申请时间开始
                'create_time_end'=>$this->input->get_post('create_time_end'),//申请时间结束
                'serial_number' =>$this->input->get_post('serial_number'),//收款流水号
                'purchase_type_id' =>$this->input->get_post('purchase_type_id'),//业务线
                'ali_refund' =>1,//1688刷新退款信息
            ];
        }

        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0)
            $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->purchase_order_determine_model->get_cencel_list($params,$offsets,$limit,$page);
        if (empty($data_list['values'])) $this->error_json('请重新选择刷新数据,1688刷新数据需为网采单,且取消未到货状态为待上传截图');
        $refresh_res = $this->purchase_order_determine_model->getAliQueryOrderRefund($data_list['values']);

        if ($refresh_res['success']){
            $this->success_json([],null,'刷新成功');
        }else{
            $this->error_json($refresh_res['error_msg']);
        }

    }

    /**
     * 取消未到货列表
     * @author harvin
     * /purchase/puerchase_unarrived/cencel_lits_sum
     */
    public function get_cancel_lists_sum(){

        $params=[
            'cancel_number'=>$this->input->get_post('cancel_number'),//申请编码
            'sku'=>$this->input->get_post('sku'),//申请编码
            'purchase_number'=>$this->input->get_post('purchase_number'),//采购单号
            'create_user_id'=>$this->input->get_post('create_user_id'),//申请人
            'audit_status'=>$this->input->get_post('audit_status'),//取消未到货状态
            'create_time_start'=>$this->input->get_post('create_time_start'),//申请时间开始
            'create_time_end'=>$this->input->get_post('create_time_end'),//申请时间结束
            'serial_number' =>$this->input->get_post('serial_number'),//收款流水号
            'purchase_type_id' =>$this->input->get_post('purchase_type_id'),//业务线
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'cancel_price_min' => $this->input->get_post('cancel_price_min'),// 取消金额最小值
            'cancel_price_max' => $this->input->get_post('cancel_price_max'),// 取消金额最大值
            'completed_time_start' => $this->input->get_post('completed_time_start'),// 退款完成时间开始
            'completed_time_end' => $this->input->get_post('completed_time_end'),// 退款完成时间结束
            'relative_superior_number' => $this->input->get_post('relative_superior_number'),// 关联的报损编号
            'pai_number' => $this->input->get_post('pai_number'),// 拍单号(1-≠0)
            'apply_amount' => $this->input->get_post('apply_amount'),//1688退款金额（1-≠0）
            'source' => $this->input->get_post('source'),//采购来源（1-合同，2-网采）
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
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

        $data_list= $this->purchase_order_determine_model->get_cancel_lists_sum($params);
        $this->success_json($data_list);

    }
    
}
