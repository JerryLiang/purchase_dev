<?php

/**
 * Created by PhpStorm.
 * 供应商数据变更审核记录
 * User: Jackson
 * Date: 2019/01/30 0029 11:50
 */
class Supplier_update_log_model extends Purchase_model
{
    protected $table_name = 'supplier_update_log';// 数据表名称
    protected $table_cont='supplier_contact';//供应商联系方式表
    protected $payment_log_table ='supplier_payment_log';
    public function __construct(){
        parent::__construct();

        $this->load->model('supplier_model');
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('Message_model');//消息模块


    }


    /**
     * 获取 供应商最新一条更新记录
     * @param string $supplier_code 供应商
     * @param array $to_audit 审核中状态
     * @return array|null
     */
    public function get_latest_audit_result($supplier_code,$to_audit = false){
        $this->purchase_db->where('supplier_code',$supplier_code);
        if($to_audit === true){
            // 查询审核中记录
            $this->purchase_db->where_in('audit_status',[
                SUPPLIER_WAITING_PURCHASE_REVIEW_LEVEL_GRADE,
                SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE,
                SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE
            ]);
        }
        $record = $this->purchase_db->order_by('id  desc')
            ->get($this->tableName())
            ->row_array();

        return $record?$record:null;
    }

    /**
     * 插入一条 供应商更新记录
     * @param $insert_data
     * @return bool
     */
    public function insert_one($insert_data,$insert_payment_log=[]){
        $supplier_code = $insert_data['supplier_code'];
        $audit_status = $insert_data['audit_status'];

        if (isset($insert_data['status'])){
            $status = $insert_data['status'];

            //状态变更时通知产品系统
            $this->supplier_model->send_supplier_status($supplier_code,$status);

            $this->purchase_db->where('supplier_code',$supplier_code)->update($this->supplier_model->tableName(),['audit_status' => $audit_status,'status'=>$status]);
            unset($insert_data['status']);
        }else{
            $this->purchase_db->where('supplier_code',$supplier_code)->update($this->supplier_model->tableName(),['audit_status' => $audit_status]);
        }

        //是否修改基本信息、是否修改关联供应商、是否修改联系方式、是否修改财务结算、是否修改证明资料

        $message = json_decode($insert_data['message'],true);
        if (!empty($message)) {

            if(!empty($message['change_data']['basis_data'])) $insert_data['is_basic_change'] = 1;
            if(!empty($message['change_data_log']['supplier_relation'])) $insert_data['is_relation_change'] = 1;
            if(!empty($message['change_data_log']['contact'])||!empty($message['insert_data']['contact_data'])) $insert_data['is_contact_change'] = 1;
            //if(!empty($message['change_data_log']['payment_data'])||!empty($message['insert_data']['payment_data'])) $insert_data['is_payment_change'] = 1;
            if(!empty($message['change_data_log']['images'])||!empty($message['insert_data']['images_data'])) $insert_data['is_proof_change'] = 1;
            if (!empty($message['change_data_log']['payment_data'])) {
                foreach ($message['change_data_log']['payment_data'] as $payment_info) {
                    foreach ($payment_info as $info) {
                        foreach ($info as $pay_key=>$pay_value) {
                            if (!in_array($pay_key,['settlement_change_res','settlement_change_remark'])) {
                                $insert_data['is_payment_change'] = 1;

                            }

                        }

                    }

                }

            }

        }

        $this->purchase_db->insert($this->tableName(),$insert_data);
        $insert_log_id  = $this->purchase_db->insert_id();
        //结算方式修改
        if ($insert_payment_log &&$insert_log_id) {
            foreach ($insert_payment_log as &$insert_data) {
                $insert_data['audit_log_id'] = $insert_log_id;

            }
            $this->purchase_db->insert_batch($this->payment_log_table,$insert_payment_log);

        }


        return $insert_log_id;
    }


    /**
     * 更新 供应商更新记录的状态（同步到供应商上面）
     * @author Jolon
     * @param $supplier_code
     * @param $new_audit_status
     * @param $status
     * @param $remark
     * @param $before_check_status 更新/启用 前的状态 用以判断审核完成供应商状态是禁用还是审核不通过
     * @param $cancel_disabled 是否是产品系统发起的"启用"流程
     * @return array
     */
    public function update_status($id,$new_audit_status,$status,$remark = '',$before_check_status = '',$cancel_disabled = 0){
        $return =  ['code' => false,'data' => '','message' => ''];
        $record = $this->update_log_detail($id);
        $supplier_code = $record['supplier_code'];
        if($record){
            $activeUserId = getActiveUserId();
            $activeUserName = getActiveUserName();

            $nowTime = date('Y-m-d H:i:s');
            if($new_audit_status == SUPPLIER_REVIEW_PASSED){
                //如果是关联供应商审核，状态不改变
                if ($record['apply_type'] == 4) {

                    $update = ['status' => $status,'audit_status' => $new_audit_status];// 审核通过，供应商状态不变

                } else {
                    $update = ['status' => 1,'audit_status' => $new_audit_status,'search_status'=>1];// 审核通过，供应商状态变为正常
                    if ($record['apply_type'] == 3) $update['restart_date'] = date('Y-m-d H:i:s');//最新启用时间


                }
                if($record['audit_status'] == 1){
                    $audit_update = [
                        'audit_status'   => $new_audit_status,
                        'purchase_audit' => $activeUserId,
                        'purchase_time'  => $nowTime,
                        'audit_user_id'  =>$activeUserId,
                        'audit_time'  =>$nowTime,
                        'audit_user_name'  =>$activeUserName


                    ];
                }elseif($record['audit_status'] == 3){
                    $audit_update = [
                        'audit_status'       => $new_audit_status,
                        'supply_chain_audit' => $activeUserId,
                        'supply_chain_time'  => $nowTime,
                        'audit_user_id'  =>$activeUserId,
                        'audit_time'  =>$nowTime,
                        'audit_user_name'  =>$activeUserName
                    ];
                }else{
                    $audit_update = [
                        'audit_status'  => $new_audit_status,
                        'finance_audit' => $activeUserId,
                        'finance_time'  => $nowTime,
                        'audit_user_id'  =>$activeUserId,
                        'audit_time'  =>$nowTime,
                        'audit_user_name'  =>$activeUserName
                    ];
                }
            }
            elseif($new_audit_status == SUPPLIER_PURCHASE_REJECT){//采购审核-驳回
                if($status==4){//只有当供应商状态为待审时,财务审核不通过，供应商状态变为审核不通过  禁用的供应商在启用后 如果被驳回  状态依旧为禁用 2019-08-13
                    //$update = ['status' => $before_check_status == IS_DISABLE ? IS_DISABLE : 5,'audit_status' => $new_audit_status];
                    $final_status = $record['apply_type'] == 5?$before_check_status:5;//如果是产品系统启用供应商流程，驳回恢复原来状态
                    $update = ['status' => in_array($before_check_status,[IS_DISABLE,PRE_DISABLE])? $before_check_status : $final_status,'audit_status' => $new_audit_status];


                }else{
                    $update = ['audit_status' => $new_audit_status];
                }
                $audit_update = [
                    'audit_status'   => $new_audit_status,
                    'purchase_audit' => $activeUserId,
                    'purchase_note'  => $remark,
                    'purchase_time'  => $nowTime,
                    'audit_user_id'  =>$activeUserId,
                    'audit_time'  =>$nowTime,
                    'audit_user_name'  =>$activeUserName
                ];
            }
            elseif($new_audit_status == SUPPLIER_SUPPLIER_REJECT){
                if($status==4){//只有当供应商状态为待审时,供应链审核不通过，供应商状态变为审核不通过
                    $final_status = $record['apply_type'] == 5?$before_check_status:5;//如果是产品系统启用供应商流程，驳回恢复原来状态
                    $update = ['status' => in_array($before_check_status,[IS_DISABLE,PRE_DISABLE])? $before_check_status : $final_status,'audit_status' => $new_audit_status];
                }else{
                    $update = ['audit_status' => $new_audit_status];
                }
                $audit_update = [
                    'audit_status'       => $new_audit_status,
                    'supply_chain_audit' => $activeUserId,
                    'supply_chain_note'  => $remark,
                    'supply_chain_time'  => $nowTime,
                    'audit_user_id'  =>$activeUserId,
                    'audit_time'  =>$nowTime,
                    'audit_user_name'  =>$activeUserName
                ];
            }
            elseif($new_audit_status == SUPPLIER_FINANCE_REJECT){//财务审核-驳回
                if($status==4){//只有当供应商状态为待审时,财务审核不通过，供应商状态变为审核不通过
                    $final_status = $record['apply_type'] == 5?$before_check_status:5;//如果是产品系统启用供应商流程，驳回恢复原来状态
                    $update = ['status' => in_array($before_check_status,[IS_DISABLE,PRE_DISABLE])? $before_check_status : $final_status,'audit_status' => $new_audit_status];
                }else{
                    $update = ['audit_status' => $new_audit_status];
                }
                $audit_update = [
                    'audit_status'  => $new_audit_status,
                    'finance_audit' => $activeUserId,
                    'finance_note'  => $remark,
                    'finance_time'  => $nowTime,
                    'audit_user_id'  =>$activeUserId,
                    'audit_time'  =>$nowTime,
                    'audit_user_name'  =>$activeUserName
                ];
            }
            elseif($new_audit_status == SUPPLIER_WAITING_SUPPLIER_REVIEW){//待供应链审核
                $update = ['audit_status' => $new_audit_status];
                $audit_update = [
                    'audit_status'   => $new_audit_status,
                    'purchase_audit' => $activeUserId,
                    'purchase_note'  => $remark,
                    'purchase_time'  => $nowTime,
                    'audit_user_id'  =>$activeUserId,
                    'audit_time'  =>$nowTime,
                    'audit_user_name'  =>$activeUserName
                ];
            }
            elseif($new_audit_status == SUPPLIER_FINANCE_REVIEW){//待财务审核
                $update = ['audit_status' => $new_audit_status];
                $audit_update = [
                    'audit_status'   => $new_audit_status,
                    'supply_chain_audit' => $activeUserId,
                    'supply_chain_note'  => $remark,
                    'supply_chain_time'  => $nowTime,
                    'audit_user_id'  =>$activeUserId,
                    'audit_time'  =>$nowTime,
                    'audit_user_name'  =>$activeUserName
                ];
            }
            else{
                $return['message'] = '供应商审核状态变更异常';
                return $return;
            }

            $res1 = $this->purchase_db->where('supplier_code',$supplier_code)
                ->update($this->supplier_model->tableName(),$update);

            $res2 = $this->purchase_db->where('id',$record['id'])
                ->update($this->table_name,$audit_update);
            if($res1 and $res2){
                $return['code'] = true;
            }else{
                $return['message'] = '更新记录状态或供应商状态失败';
            }
        }else{
            $return['message'] = '不存在待审核的记录';
        }
        /*if($status == 4){// 供应商状态是 待审核：把审核状态推送给 产品系统（审核之后的就不需要管了） 即时推送审核日志到产品系统
            $this->rediss->lpushData('push_sup_status_to_product',$supplier_code);// 推送供应商审核状态道产品系统
            $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_push_sup_status_to_product');
        }*/

        if($return['code'] === true){
            if(in_array($new_audit_status,[SUPPLIER_PURCHASE_REJECT,SUPPLIER_SUPPLIER_REJECT,SUPPLIER_FINANCE_REJECT])){// 驳回
                switch($new_audit_status){
                    case SUPPLIER_PURCHASE_REJECT:
                        $hint = '采购审核';
                        break;
                    case SUPPLIER_SUPPLIER_REJECT:
                        $hint = '供应链审核';
                        break;
                    case SUPPLIER_FINANCE_REJECT:
                        $hint = '财务审核';
                        break;
                    default :
                        $hint = '';
                }
                operatorLogInsert(
                    [
                        'id'      => $supplier_code,
                        'type'    => 'supplier_update_log',
                        'content' => "{$hint}-审核不通过",
                        'detail'  => '驳回原因：'.$remark,
                        'operate_type' => SUPPLIER_NORMAL_AUDIT
                    ]
                );

                $this->insert_payment_log($id,SUPPLIER_NORMAL_AUDIT,'驳回原因：'.$remark);

                //驳回写入消息
                $this->Message_model->AcceptMessage('supplier',['data'=>[$id],'message'=>$remark,'user'=>getActiveUserName(),'type'=>'待'.$hint]);


            }else{// 审核通过
                switch($new_audit_status){
                    case SUPPLIER_WAITING_SUPPLIER_REVIEW:
                        $hint = '采购审核';
                        break;
                    case SUPPLIER_FINANCE_REVIEW:
                        $hint = '供应链审核';
                        break;
                    case SUPPLIER_REVIEW_PASSED:
                        if($record['audit_status'] == SUPPLIER_WAITING_PURCHASE_REVIEW){// 当前为[待采购审核]时审核通过，为采购审核
                            $hint = '采购审核';
                        }elseif($record['audit_status'] == SUPPLIER_WAITING_SUPPLIER_REVIEW){// 当前为[待供应链审核]时审核通过，为供应链审核
                            $hint = '供应链审核';
                        }else{
                            $hint = '财务审核';
                        }
                        break;
                    default :
                        $hint = '';
                }
                operatorLogInsert(
                    [
                        'id'      => $supplier_code,
                        'type'    => 'supplier_update_log',
                        'content' => "{$hint}-审核通过",
                        'detail'  => '审核通过'.$remark,
                        'operate_type' => SUPPLIER_NORMAL_AUDIT
                    ]
                );

                $this->insert_payment_log($id,SUPPLIER_NORMAL_AUDIT,'审核通过'.$remark);

                if($before_check_status == IS_DISABLE && isset($update['status']) && $update['status'] == 1){//禁用的供应商重新启用时新增审启用日志
                    operatorLogInsert(
                        [
                            'id'      => $supplier_code,
                            'type'    => 'supplier_update_log',
                            'content' => "供应商启用成功",
                            'detail'  => "供应商启用成功(审核通过自动启用)",
                            'operate_type'  => SUPPLIER_RESTART_FROM_DISABLED,
                        ]
                    );
                }
            }
        }
        if($status == 4){//即时推送审核日志到产品系统
            if ($new_audit_status == SUPPLIER_REVIEW_PASSED) {
                $set_status = 1;
                $set_remark = isset($hint) ? $hint . '-审核通过' . $remark : '';
            } elseif (in_array($new_audit_status, [SUPPLIER_PURCHASE_REJECT, SUPPLIER_SUPPLIER_REJECT, SUPPLIER_FINANCE_REJECT])) {

               if (in_array($before_check_status,[IS_DISABLE,PRE_DISABLE])) {
                   $set_status  = ($before_check_status == IS_DISABLE)?7:12;

               } else {
                   $set_status = 5;

               }

                $set_remark = isset($hint) ? $hint . '审核不通过' . $remark : '';
            } else {
                $set_status = 2;
                $set_remark = isset($hint) ? $hint . '-审核通过' . $remark : '';
            }

            $this->supplier_model->plan_update_supplier_status($supplier_code, $set_status, $set_remark,$cancel_disabled,$record['apply_type']);
        }
        return $return;
    }


    /**
     * 根据 供应商更新记录  去修改供应商数据
     * @author Jolon
     * @param        $supplier_code
     * @param        $new_status
     * @param string $remarks
     * @return array
     */
    public function do_update_supplier($id,$new_status,$remarks = '',$is_complete = null){
        $return =  ['code' => false,'data' => '','message' => ''];
        $now_time = date('Y-m-d H:i:s');

        $update_log = $this->update_log_detail($id);
        if(empty($update_log) or $update_log['audit_status'] == SUPPLIER_REVIEW_PASSED){
            $return['message'] = "该供应商非待审核状态";
            return $return;
        }

        $supplier_code = $update_log['supplier_code'];

        $this->load->model('Supplier_model');
        $this->load->model('Supplier_payment_account_model', 'paymentAccountModel');
        $this->load->model('Supplier_contact_model', 'contactModel');
        $this->load->model('Supplier_images_model', 'imagesModel');
        $this->load->model('Supplier_audit_results_model', 'auditModel');
        $this->load->model('Supplier_product_line_model', 'productLineModel');
        $this->load->model('Supplier_buyer_model', 'buyerModel');
        $this->load->model('Supplier_address_model','addressModel');
        $this->load->model('Supplier_payment_info_model','Supplier_payment_info_model');
        $this->load->model('purchase/Purchase_order_model','purchase_order_model');

        // 供应商名称
        $supplierInfo = $this->Supplier_model->get_supplier_info($supplier_code,false);
        $supplier_name = $supplierInfo['supplier_name'];
        $new_supplier_name = null;
        $new_supplier_source = NULL; // 供应商来源
        //事务处理
        $this->purchase_db->trans_begin();// 必须调用 model->purchase_db
        try {

            if($new_status){
                $update_info = json_decode($update_log['message'],true);
                $before_check_status = isset($update_info['change_data']['before_check_status']) ? $update_info['change_data']['before_check_status'] : '';
                $cancel_disabled = isset($update_info['change_data']['cancel_disabled']) ? $update_info['change_data']['cancel_disabled'] : 0;
                $result = $this->update_status($id,$new_status,$supplierInfo['status'],$remarks,$before_check_status,$cancel_disabled);
                if(empty($result) or empty($result['code'])){
                    throw new Exception('该供应商更新数据审核失败');
                }

                if(is_numeric($is_complete) && $supplierInfo['is_complete'] != $is_complete){//审核时变更资料是否齐全
                    $update_complete = $this->purchase_db->where('supplier_code',$supplier_code)->update('pur_supplier',['is_complete'=>$is_complete]);
                    if(empty($update_complete)){
                        throw new Exception('供应商资料是否齐全变更失败');
                    }
                }

            }else{
                throw new Exception('供应商状态未发生变更');
            }
            if($new_status == SUPPLIER_REVIEW_PASSED){// 所有审核都通过 更细数据
                //$this->Supplier_model->supplier_disable($supplierInfo['id'],IS_ENABLE,'审核通过自动启用',false);
                $message = $update_log['message'];
                $message = json_decode($message, true);
                if(empty($message)){
                    throw new Exception('获取供应商变更数据失败');
                }
                $change_data = isset($message['change_data']) ? $message['change_data'] : null;
                $insert_data = isset($message['insert_data']) ? $message['insert_data'] : null;
                $delete_data = isset($message['delete_data']) ? $message['delete_data'] : null;




                if (isset($change_data['basis_data']['supplier_source'])&&$change_data['basis_data']['supplier_source'] == 1&&$supplierInfo['is_diversion_status'] == 2) {
                    $change_data['basis_data']['is_diversion_status'] = 3;//变成已转化


                }



                if($change_data){
                    if(isset($change_data['basis_data']) and $change_data['basis_data']){

                        $supplier_basic                  = $change_data['basis_data'];
                        $supplier_basic['supplier_code'] = $supplier_code;
                        $supplier_settlement= isset($supplier_basic['supplier_settlement']) ? $supplier_basic['supplier_settlement'] : '';
                        if(isset($supplier_basic['supplier_name'])){
                            $new_supplier_name = $supplier_basic['supplier_name'];
                        }
                        if( isset($supplier_basic['supplier_source'])) {
                            $new_supplier_source = $supplier_basic['supplier_source'];
                        }

                        if( isset($supplier_basic['is_postage'])) {
                            $new_supplier_is_postage = $supplier_basic['is_postage'];
                        }


                        list($basic_status, $msg) = $this->Supplier_model->update_supplier($supplier_basic, 0, true);
                        //sku关联的该默认供应商的名字也需同步改变。变成“等待到货”前的采购单的名字也发生变化，同时该采购单关联的备货单的名字也发生变化
                        if ($new_supplier_name) {
                            $re_supplier_name = $this->purchase_order_model->update_relate_supplier_name($supplier_code, $new_supplier_name);
                            if (!$re_supplier_name) {
                                throw new Exception('关联供应商名称更新失败');
                            }
                        }
                        if( $new_supplier_source != NULL ) {
                            $re_supplier_source = $this->purchase_order_model->update_relate_supplier_source($supplier_code,$new_supplier_source);
                            if( False == $re_supplier_source ) {

                                throw new Exception('修改采购单供应商来源失败');
                            }
                        }


                        if( isset($new_supplier_is_postage)&&$new_supplier_is_postage==1 ) {//该供应商绑定的所有SKU是否包邮自动变为:包邮
                            $re_supplier_source = $this->supplier_model->update_postage_supplier_sku($supplier_code);
                            if( False == $re_supplier_source ) {

                                throw new Exception('修改绑定供应商sku是否包邮失败');
                            }
                        }

                        /*if($supplier_settlement){ //更改采购单结算方式
                            $this->purchase_order_model->update_order_settlement($supplier_settlement,$supplier_code);
                            if(empty($basic_status)){
                                throw new Exception('供应商数据更新失败');
                            }
                        }*/

                    }
              
                    /*if(isset($change_data['supplier_product_line']) and $change_data['supplier_product_line']){
                        $supplier_product_line                  = $change_data['supplier_product_line'];
                        $supplier_product_line['supplier_code'] = $supplier_code;
                        if(isset($supplier_basic['supplier_name'])){
                            $supplier_product_line['supplier_name'] = $supplier_basic['supplier_name'];
                        }
                        //更新产品线
                        list($_productLineStatus, $proLineMsg) = $this->productLineModel->update_product_line($supplier_product_line);
                        if(empty($_productLineStatus)){
                            throw new Exception('供应商产品线数据更新失败');
                        }
                    }*/
                    if(isset($change_data['contact_data']) and $change_data['contact_data']){
                        // 更更新多少联系方式       
                        $supplier_contact_list                  = $change_data['contact_data'];
                        foreach($supplier_contact_list as $key => $supplier_contact){
                            $supplier_contact['id']            = $key;
                            $supplier_contact['supplier_code'] = $supplier_code;
                            list($_contactStatus, $contactMsg) = $this->contactModel->update_supplier_contact($supplier_contact, $supplier_code);
                            if(empty($_contactStatus)){
                                throw new Exception('供应商联系人据更新失败');
                            }
                        }
                     
                       // $contact_key                       = key($supplier_contact);
                      //  $supplier_contact                  = current($supplier_contact);
                     //    $supplier_contact['id']            = $contact_key;
                     //  $supplier_contact['supplier_code'] = $supplier_code;
                      
                      //  list($_contactStatus, $contactMsg) = $this->contactModel->update_supplier_contact($supplier_contact, $supplier_code);

                        
                    }
                    if(isset($change_data['buyer_data']) and $change_data['buyer_data']){
                        $supplier_buyer     = $change_data['buyer_data'];
                        $supplier_buyer_tmp = [];
                        foreach($supplier_buyer as $buyer_key => $buyer_value){
                            $supplier_buyer_tmp[] = [
                                'buyer_type' => $buyer_key,
                                'buyer_id'   => $buyer_value['buyer_id'],
                            ];
                        }
                        list($_buyerStatus, $buyerMsg) = $this->buyerModel->update_supplier_buyer($supplier_buyer_tmp, $supplier_code);
                        if(empty($_buyerStatus)){
                            throw new Exception('供应商采购员数据更新失败');
                        }
                    }

                    if(isset($change_data['payment_data']) and $change_data['payment_data']){
                        $data = [];
                        foreach ($change_data['payment_data'] as $item){
                            foreach ($item as $val){
                                $data[] = $val;
                            }
                        }
                        if (!empty($data)){
                            $this->Supplier_payment_info_model->update_payment_info($data);
                        }
                    }

                    if(isset($change_data['images_data']) and $change_data['images_data']){//更新附属图
                        $tempImage = $change_data['images_data'];
                        $typeField = array_keys($tempImage); //解析图片类型字段
                        $imageInfo = array();
                        if(!empty($typeField)){
                            foreach($typeField as $key => $field){
                                $imageInfo['supplier_code'] = $supplier_code;
                                $imageInfo['supplier_name'] = !empty($new_supplier_name) ? $new_supplier_name : $supplier_name;
                                $imageInfo['image_type']    = $field;
                                $image_url                  = !empty($tempImage[$field]) ? $tempImage[$field] : '';
                                $image_url                  = is_array($image_url) ? implode(';', $image_url) : $image_url;
                                $imageInfo['image_url']     = $image_url;
                                list($_imageStatus, $imageMsg) = $this->imagesModel->update_supplier_image($imageInfo, $supplier_code,false);
                                if(empty($_imageStatus)){
                                    throw new Exception('供应商图片数据更新失败');
                                }
                            }
                        }
                    }
                }
//               pr($insert_data);exit;
                if($insert_data){
                    /*if(isset($insert_data['supplier_product_line']) and $insert_data['supplier_product_line']){
                        $supplier_product_line                     = $insert_data['supplier_product_line'];
                        $supplier_product_line['supplier_code']    = $supplier_code;
                        $supplier_product_line['supplier_name']    = !empty($new_supplier_name) ? $new_supplier_name : $supplier_name;
                        $supplier_product_line['create_user_name'] = getActiveUserName();
                        $supplier_product_line['create_time']      = $now_time;

                        //更新产品线
                        list($_productLineStatus, $proLineMsg) = $this->productLineModel->insert_product_line($supplier_product_line);
                        if(empty($_productLineStatus)){
                            throw new Exception('供应商产品线数据添加失败');
                        }
                    }*/
                       
                    if(isset($insert_data['contact_data']) and $insert_data['contact_data']){
                        
                        // 只支持 单个联系人
                        $supplier_contact['id']            = '-1';
                        $supplier_contact                  = $insert_data['contact_data'];
                    
                        foreach($supplier_contact as $contact){
                            $contact['id'] = -1;
                            $contact['supplier_code'] = $supplier_code;
                            list($_contactStatus, $contactMsg) = $this->contactModel->update_supplier_contact($contact, $supplier_code);

                            if(empty($_contactStatus)){
                                throw new Exception('供应商联系人据添加失败');
                            }
                        }
                    }
                    if(isset($insert_data['buyer_data']) and $insert_data['buyer_data']){
                        $supplier_buyer     = $insert_data['buyer_data'];
                        $supplier_buyer_tmp = [];
                        foreach($supplier_buyer as $buyer_key => $buyer_value){
                            $supplier_buyer_tmp[$buyer_key]['buyer_id']   = $buyer_value['buyer_id'];
                            $supplier_buyer_tmp[$buyer_key]['buyer_type'] = $buyer_value['buyer_type'];
                        }
                        list($_buyerStatus, $buyerMsg) = $this->buyerModel->update_supplier_buyer($supplier_buyer_tmp, $supplier_code);

                        if(empty($_buyerStatus)){
                            throw new Exception('供应商采购员数据添加失败');
                        }
                    }

                    if(isset($insert_data['payment_data']) and $insert_data['payment_data']){
                        $data = [];
                        foreach ($insert_data['payment_data'] as $item){
                            foreach ($item as $val){
                                //验证是否存在
                                if (!empty($this->Supplier_payment_info_model->check_payment_info($val['supplier_code'],$val['is_tax'],$val['purchase_type_id']))){
                                    continue;
                                }else{
                                    $data[] = $val;

                                }
                            }
                        }
                        if (!empty($data)){
                            if(!$this->Supplier_payment_info_model->insert_payment_info($data)){
                                throw new Exception('供应商财务结算数据添加失败');
                            }
                        }

                    }
               
                    if(isset($insert_data['images_data']) and $insert_data['images_data']){//更新附属图
                        $tempImage = $insert_data['images_data'];
                        $tempImage = array_filter($tempImage); //过滤空值
                        $typeField = array_keys($tempImage); //解析图片类型字段
                        $imageInfo = array();
                       
                        if(!empty($typeField)){
                            foreach($typeField as $key => $field){   
                                $imageInfo['supplier_code'] = $supplier_code;
                                $imageInfo['supplier_name'] = !empty($new_supplier_name) ? $new_supplier_name : $supplier_name;
                                $imageInfo['image_type']    = $field;
                                $image_url                  = !empty($tempImage[$field]) ? $tempImage[$field] : '';
                                $image_url                  = is_array($image_url) ? implode(';', $image_url) : $image_url;
                                $imageInfo['image_url']     = $image_url;
                                $imageInfo['image_status']  = 1;// 图片状态（1.正常,2.历史图片）
                                list($_imageStatus, $imageMsg) = $this->imagesModel->update_supplier_image($imageInfo,$supplier_code);  
                                if(empty($_imageStatus)){
                                    throw new Exception('供应商图片数据添加失败');
                                }
                                 
                            }
                        }
                    }
                    //关联供应商
                    if (!empty($insert_data['relation_supplier'])) {
                        $this->supplier_model->update_relation_suppplier($supplier_code,$insert_data['relation_supplier'],1);

                    }
                }
  
                if($delete_data){
                    if(isset($delete_data['contact_data']) and $delete_data['contact_data']){
                        $contact_ids = $delete_data['contact_data'];
                        foreach($contact_ids as $contact_id){           
                            $this->purchase_db->delete($this->table_cont,['id' => $contact_id]);
                        }
                    }
                    if (!empty($delete_data['relation_supplier'])) {
                        $this->supplier_model->update_relation_suppplier($supplier_code,$delete_data['relation_supplier'],2);


                    }
                }

                // 审核通过-审核记录
                $this->auditModel->update_audit($supplier_code,SUPPLIER_AUDIT_RESULTS_STATUS_PASS,$remarks);

                if(isset($update_log['source']) and $update_log['source'] != 2){// 等于2的表示为产品系统抓取数据的更新，不再推送到产品系统
                    $this->rediss->lpushData('push_sup_info_to_product',$supplier_code);// 推送供应商信息
                    $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_push_sup_info_to_product');
                }

                //同步到产品系统
                $this->Supplier_model->send_supplier_status($supplier_code,$supplierInfo['status'],'',true);

                //如果有供应商店铺id同步更新线上账期
               /* if (!empty($change_data['basis_data']['shop_id'])||!empty($supplierInfo['shop_id'])) {
                    $shop_id = !empty($change_data['basis_data']['shop_id'])?$change_data['basis_data']['shop_id']:$supplierInfo['shop_id'];
                    $this->supplier_model->update_supplier_quota($shop_id,$supplier_code,$supplierInfo);

                }*/


            }elseif(in_array($new_status,[SUPPLIER_PURCHASE_REJECT,SUPPLIER_SUPPLIER_REJECT,SUPPLIER_FINANCE_REJECT])){
                // 审核驳回-审核记录
                $this->auditModel->update_audit($supplier_code,SUPPLIER_AUDIT_RESULTS_STATUS_REJECTED,$remarks);


                if ($supplierInfo['is_diversion_status'] == 2) {//临时转常规状态驳回标识就变为1
                    $update_temp['is_diversion_status'] = 1 ;
                    $this->purchase_db->where('supplier_code',$supplier_code)->update('pur_supplier',$update_temp);//临时转常规  供应商来源直接改为1

                }

            }

            //判断是否保存成功
            if ($this->purchase_db->trans_status() !== false) {
                $this->purchase_db->trans_commit();
            } else {
                $this->purchase_db->trans_rollback();
                throw new Exception('更新供应商事务提交失败');
            }

            $return['code'] = true;
            return $return;
        } catch (Exception $e) {
            $return['message'] = $e->getMessage();
            return $return;
        }
    }

    /**根据供应商编码获取审核时效数据
     * @param $supplier_code
     * @param $begin_time
     * @param string $audit_time
     * @return array
     */
    public function get_audit_time_list($supplier_code,$begin_time,$audit_time = ''){

        $apply_time    = [
            'apply_user'    => '',
            'apply_time'    => ''
        ];
        $purchase_time  = [//采购审核时间
            'audit_user'  => '',
            'audit_time'  => '',
            'use_time'    => '',
        ];
        $supply_time    = [//供应链审核时间
            'audit_user'  => '',
            'audit_time'  => '',
            'use_time'    => '',
        ];
        $finance_time   = [//财务审核时间
            'audit_user'  => '',
            'audit_time'  => '',
            'use_time'    => '',
        ];

        //因历史数据更新时先生成变更记录再生成审核记录 根据2者生成的时间差来判定是否存在对应关系
        $begin_time_stamp = strtotime($begin_time);
        $update = $this->purchase_db->where('supplier_code',$supplier_code)
            ->where("ABS(UNIX_TIMESTAMP(`create_time`) - {$begin_time_stamp}) <=",30)
            ->order_by('id asc')
            ->get($this->tableName())
            ->row_array();
        if($update){
            //因历史数据更新时先生成变更记录再生成审核记录 根据2者生成的时间差来判定是否存在对应关系
                $apply_time = [
                    'apply_user'    => isset($update['create_user_name']) ? $update['create_user_name']: '',
                    'apply_time'    => isset($update['create_time'])      ? $update['create_time'] : '',
                ];

        }
        $content_list = ['采购审核-审核通过','供应链审核-审核通过','财务审核-审核通过','采购审核-审核不通过','供应链审核-审核不通过','财务审核-审核不通过'];
        $query_builder   = $this->purchase_db->where('record_number',$supplier_code);
        $query_builder->where('record_type','supplier_update_log');
        $query_builder->where_in('content',$content_list);
        $query_builder->where('operate_time >= ',$begin_time);
        if($audit_time != '0000-00-00 00:00:00'){
            $query_builder->where('operate_time <= ',$audit_time);//审核通过时间
        }
        $query_builder->order_by('id asc');
        $result = $query_builder->get('pur_operator_log')->result_array();

        if(empty($result)){
            $return = [
                'apply_time'        => $apply_time,
                'purchase_time'     => $purchase_time,
                'supply_time'       => $supply_time,
                'finance_time'      => $finance_time,
            ];
            return $return;
        }
        $this->load->model('supplier/supplier_audit_model');
        $time_list = ['purchase_time'=>'','supply_time'=>'','finance_time'=>''];
        foreach ($result as $key => $value){
                if(mb_strpos($value['content'],'采购审核') !== false){
                    $time_list['purchase_time'] = $value['operate_time'];
                    $purchase_use_time = strtotime($value['operate_time']) - strtotime($begin_time);
                    $purchase_time = [
                        'audit_user'    => $value['operator'],
                        'audit_time'    => $value['operate_time'],
                        'use_time'      => supplier_audit_model::get_audit_used_cn((int)$purchase_use_time),
                    ];
                }


                if(mb_strpos($value['content'],'供应链审核') !== false){
                    $time_list['supply_time'] = $value['operate_time'];
                    $supply_use_time = strtotime($value['operate_time']) - strtotime($time_list['purchase_time'] ? : $begin_time);
                    $supply_time = [
                        'audit_user'    => $value['operator'],
                        'audit_time'    => $value['operate_time'],
                        'use_time'      => supplier_audit_model::get_audit_used_cn((int)$supply_use_time),
                    ];
                }


                if(mb_strpos($value['content'],'财务审核') !== false){
                    $time_list['finance_time'] = $value['operate_time'];
                    $finance_use_time = strtotime($value['operate_time']) - strtotime($time_list['supply_time']);
                    $finance_time = [
                        'audit_user'    => $value['operator'],
                        'audit_time'    => $value['operate_time'],
                        'use_time'      => supplier_audit_model::get_audit_used_cn((int)$finance_use_time),
                    ];
                }
        }


        $return = [
            'apply_time'        => $apply_time,
            'purchase_time'     => $purchase_time,
            'supply_time'       => $supply_time,
            'finance_time'      => $finance_time,
        ];

        return $return;
    }

    /**
     * 获取修改日志
     * @supplier_code  供应商CODE
     * @author:luxu
     * @time:2020/4/21
     **/

    public function getUpdateLogs($supplier_code){

        return $this->purchase_db->from($this->table_name)->where_in("supplier_code",$supplier_code)->get()->result_array();

    }


    /**
     * 获取 除了当前审核记录的其他审核状态记录
     * @param $supplier_code
     * @return array|null
     */
    public function get_other_complete_log($id,$supplier_code,$status_list){
        $record = $this->purchase_db->where('supplier_code',$supplier_code)
            ->where('id!=',$id)
            ->where_in('audit_status',$status_list)
            ->order_by('id  desc')
            ->get($this->tableName())
            ->result_array();
        return $record?$record:null;
    }


    /**
     * 获取 供应商最新一条更新记录
     * @param $supplier_code
     * @return array|null
     */
    public function update_log_detail($id){
        $record = $this->purchase_db->where('id',$id)
            ->order_by('id  desc')
            ->get($this->tableName())
            ->row_array();
        return $record?$record:null;
    }

    public function insert_payment_log($audit_log_id,$opr_type,$remark)
    {
        $opr_user = getActiveUserName();
        $opr_id   = getActiveUserId();
        $insert_batch = [];


        //查询是否有关联日志存在
        $relation_logs = $this->purchase_db->select('*')->from($this->payment_log_table)->where('audit_log_id',$audit_log_id)->get()->result_array();
        if (!empty($relation_logs)) {
            foreach ($relation_logs as $log) {
                $temp = $log ;
                unset($temp['id']);
                $temp['opr_type'] = $opr_type;
                $temp['remark'] =   $remark;
                $temp['opr_user'] = $opr_user;
                $temp['opr_user_id'] = $opr_id;
                $temp['opr_time']    = date('Y-m-d H:i:s');
                $insert_batch[] = $temp;

            }


        }
        if (!empty($insert_batch)) {
            $this->purchase_db->insert_batch($this->payment_log_table,$insert_batch);

        }

    }


}