<?php
/**
 * 退货跟踪模块.
 * User: totoro
 * Date: 2020/3/2
 * Time: 16:11
 */


class Purchase_return_tracking_model extends Purchase_model {


    public function __construct(){
        parent::__construct();
        $this->table_name= 'return_after_storage_collection';
        $this->table_part = 'return_after_storage_part';
        $this->table_collection = 'return_after_storage_collection';
        $this->table_file ='return_refund_file';
        $this->table_express ='return_express_info';
        $this->table_main = 'return_after_storage_main';
        $this->table_logistics_track_detail  = 'purchase_logistics_track_detail';

    }

    /**
     * 根据退货单获取
     * 退货产品成本=sum（仓库确认数量*未税单价）
     * 退货金额 =sum（仓库确认数量*退货单价）
     * 实际运费 =sum()
     */
    public function get_storage_collection_list($params,$offset,$limit,$page=1){
//        $this->purchase_db->select('return_number,group_concat(part_number) part_number,sum(freight) freight,sum(return_amount) return_amount,sum(return_cost) return_cost,freight_payment_type,supplier_code,supplier_name,contact_person,contact_number,contact_province,contact_addr,wms_shipping_time,return_status')->from($this->table_name.' as c');
//      $this->purchase_db->where('return_status>=5');
        //添加筛选条件
        $query= $this->purchase_db;
        $query->select('c.return_number,c.supplier_code,c.supplier_name,c.refund_product_cost,c.freight_payment_type,c.return_status,c.act_refund_amount,c.is_confirm_receipt,c.is_confirm_time,c.wms_shipping_time,c.upload_screenshot_time,c.colletion_user,c.colletion_user_name,c.colletion_time,c.colletion_remark,c.act_freight,c.refundable_amount');
        $query->from($this->table_name.' as c');
        //退货单号
        if(isset($params['return_number']) and !empty($params['return_number'])){
            $return_number = explode(' ', trim($params['return_number']));
            $query->where_in('c.return_number', array_filter($return_number));
        }

        //退货状态
        if(isset($params['return_status']) and !empty($params['return_status'])){
            $query->where('c.return_status',$params['return_status']);
        }
        //是否签收
        if(isset($params['is_confirm_receipt']) and !empty($params['is_confirm_receipt'])){
            $query->where('c.is_confirm_receipt',$params['is_confirm_receipt']);
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

        //退货申请单
        if(isset($params['main_number']) and !empty($params['main_number'])){
            $query->join('return_after_storage_part as pr', 'pr.return_number=c.return_number');
            $main_number = explode(' ', trim($params['main_number']));
            $query->where_in('pr.main_number', array_filter($main_number));
        }


        //申请子id
        if(isset($params['part_number']) and !empty($params['part_number'])){
            $query->join('return_after_storage_part as pr', 'pr.return_number=c.return_number');
            $main_number = explode(' ', trim($params['part_number']));
            $query->where_in('pr.part_number', array_filter($main_number));
        }

        //物流状态 退货
        if(isset($params['track_status']) and !empty($params['track_status'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number and ex.is_manually=0');
            $query->where_in('ex.status',$params['track_status']);
        }

        //拒绝 物流状态
        if(isset($params['refuse_track_status']) and !empty($params['refuse_track_status'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number and ex.is_manually=1');
            $query->where_in('ex.status',$params['refuse_track_status']);
        }

        //快递单号
        if(isset($params['express_number']) and !empty($params['express_number'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number');
            $express_number = explode(' ', trim($params['express_number']));
            $query->where_in('ex.express_number', array_filter($express_number));
        }


        //退款流水号
        if(isset($params['refund_serial_number']) and !empty($params['refund_serial_number'])){
            $refund_number = $params['refund_serial_number'];
            $refund_serial_number = explode(' ', trim($params['refund_serial_number']));
            $refund_serial_list   = array_map(function($array){
                return sprintf("'%s'", $array);
            }, $refund_serial_number);
            $refund_serial_list   = array_unique($refund_serial_list);
            $refund_serial_list   = implode(',', $refund_serial_list);

            $refund_list = $query->query("SELECT return_number FROM pur_return_refund_flow_info WHERE refund_serial_number in (".$refund_serial_list.")")->result_array();
            if(!empty($refund_list)){
                $return_number = array_column($refund_list,'return_number');
                $query->where_in('c.return_number',$return_number);
            }else{
                $query->where('c.return_number',$refund_number);
            }
        }
        //采购员
        if(isset($params['buyer_id']) and !empty($params['buyer_id'])){
            if(is_array($params['buyer_id'])){
                $buyer_id = implode(',',$params['buyer_id']);
            }
            if(isset($buyer_id) and $buyer_id){
                $main_number = $query->query("SELECT main_number FROM pur_return_after_storage_main WHERE buyer_id in (".$buyer_id.")")->result_array();
                if(!empty($main_number)){
                    $main_number = array_column($main_number,'main_number');
                    $main_number = implode("','",$main_number);
                    $return_number = $query->query("SELECT return_number FROM pur_return_after_storage_part WHERE main_number in ('".$main_number."')")->result_array();
                    if(!empty($return_number)){
                        $return_number = array_column($return_number,'return_number');
                        $query->where_in('c.return_number',$return_number);
                    }else{
                        $query->where('c.return_number',PURCHASE_NUMBER_ZFSTATUS);
                    }
                }else{
                    $query->where('c.return_number',PURCHASE_NUMBER_ZFSTATUS);
                }
            }else{
                $query->where('c.return_number',PURCHASE_NUMBER_ZFSTATUS);
            }
        }
        $clone_db          = clone($query);
        // 根据 return_number 维度计算记录个数
        $count_sql = $clone_db->select('count(c.return_number) as num')->group_by('c.return_number')->get_compiled_select();
        $count_row = $clone_db->query("SELECT count(cc.return_number) as num FROM ($count_sql) AS cc")->row_array();
        $total = isset($count_row['num']) ? (int) $count_row['num'] : 0;
        $data = $query->group_by('c.return_number')->limit($limit, $offset)->get()->result_array();


//        table_maim;
        if(!empty($data)){
            foreach ($data as $key => $val){
                $return_status = getReturnStatus($val['return_status']);
                $track_list = $this->get_return_express_info($val['return_number'],0);//手工录入的

                if(empty($track_list))  $track_list = [];

                $refuse_track_list = [];
                if($return_status == '供应商签收失败' || $val['return_status'] == 7){
                    $refuse_track_list = $this->get_return_express_info($val['return_number'],1);
                }

                $refund_serial_number = $this->get_refund_number($val['return_number']);
                if(empty($refund_serial_number)) $refund_serial_number ='';
                $page_info = $this->get_storage_part_info($val['return_number']);

                $contact_person = $contact_number= $contact_addr= $contact_province ='';
                if(!empty($page_info)){
                    $contact_person = $page_info['contact_person'];
                    $contact_number= $page_info['contact_number'];
                    $contact_addr= $page_info['contact_addr'];
                    $contact_province = $page_info['contact_province'];
                }

                $data[$key]['freight_payment_type'] =getFreightPaymentType($val['freight_payment_type']);
                $data[$key]['return_status'] = $return_status;
                $data[$key]['is_confirm_receipt'] = getReturnIsConfirmReceipt($val['is_confirm_receipt']);
                $main_data_list = $this->get_main_number($val['return_number']);
                $data[$key]['main_number'] = isset($main_data_list['main_number'])?$main_data_list['main_number']:'';
                $data[$key]['buyer_name'] = isset($main_data_list['buyer_name'])?$main_data_list['buyer_name']:'';
                $data[$key]['track_list'] = $track_list;
                $data[$key]['refuse_track_list'] = $refuse_track_list;
                $data[$key]['refund_serial_number'] = $refund_serial_number;
                $data[$key]['contact_person'] = $contact_person;
                $data[$key]['contact_number']= $contact_number;
                $data[$key]['contact_addr']= $contact_addr;
                $data[$key]['contact_province'] = $contact_province;
                if($val['colletion_time'] =='0000-00-00 00:00:00'){
                    $data[$key]['colletion_time'] = '';
                }

                if($val['upload_screenshot_time'] =='0000-00-00 00:00:00'){
                    $data[$key]['upload_screenshot_time'] = '';
                }

            }
        }

        $this->load->model('user/Purchase_user_model');
        $user_list=$this->Purchase_user_model->get_user_all_list();
        $return_data = [
            'data_list' => [
                'value'         => $data,
                'key'           => ['退货单号', '申请ID', '供应商', '联系人', '联系方式', '退货地址', '退货产品成本', '退货金额', '实际运费','实际退款金额','仓库发货时间','物流轨迹','确认签收时间','是否确认签收','采购员','拒收后的快递单号','上传截图时间','退款流水号','财务收款人/时间','财务备注','状态'
                ],
                'drop_down_box' => [
                    'return_status'        => getPurchaseReturnTrackingStatus(),
                    'is_confirm_receipt' => getReturnIsConfirmReceipt(),
                    'track_status' => getTrackStatus(),
                    'refuse_track_status'=> getTrackStatus(),
                    'user_list' => $user_list
                ],
            ],
            'page_data' => [
                'total'  => $count_row,
                'offset' => $page,
                'limit'  => $limit,
                'pages'  => ceil($total / $limit)
            ],
        ];
        return $return_data;

    }

    /**
     * 供应商签收
     *  更新 pur_return_after_storage_collection
     *      pur_return_after_storage_part 表退货状态
     *      失败签收失败
     *      成功待上传截图
     * @param $return_number 退货单号
     * @param $is_confirm_receipt 是否签收  1是 2否
     * @param $confirm_receipt_remark 签收备注
     */
    public function receipt_confirmation($return_number,$is_confirm_receipt,$confirm_receipt_remark){
        $return_status = RETURN_STATUS_SUPPLIER_RECEIPT_FAIL;//默认签收失败
        if($is_confirm_receipt==RETURN_IS_CONFIRM_RECEIPT_TRUE){
            $return_status = RETURN_STATUS_WAITING_UPLOAD_SCREENSHOT;
        }
        $this->purchase_db->trans_begin();
        try{
            if($is_confirm_receipt==1){
                $update_data['is_confirm_receipt'] = 1;
                $update_data['is_confirm_time'] = date('Y-m-d H:i:s');
            }else{
                $update_data['is_confirm_receipt'] = 2;
            }
            //更新签收状态 入库退货_应收款
            $update_data['confirm_receipt_remark'] = $confirm_receipt_remark;
            $update_data['return_status'] = $return_status;
            $false = $this->purchase_db->update($this->table_name,$update_data,['return_number'=>$return_number]);
            unset($update_data);
            //更新签收状态 入库后退货子表
            if($false){
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
     * 获取申请ID
     * @param $return_number
     * @return array
     */
    public function get_main_number($return_number){
        $data = $this->purchase_db->select('group_concat(p.main_number)  main_number,group_concat( DISTINCT m.buyer_name)  buyer_name')
            ->from($this->table_part.' AS p')
            ->join($this->table_main.' AS m','p.main_number=m.main_number')
            ->where('p.return_number',$return_number)
            ->group_by('p.return_number')
            ->get()
            ->row_array();

        return !empty($data)?$data:[];
    }

    /**
     * 判断状态
     * 退货状态 ({"1":"待采购经理审核","2":"采购经理驳回","3":"待生成退货单","4":"待仓库发货","5":"仓库驳回","6":"待供应商签收","7":"供应商签收失败","8":"待上传截图","9":"待财务收款","10":"财务驳回","11":"财务已收款"})
     *
     */
    public function is_return_status($return_number,$return_status =RETURN_STATUS_WAITING_SUPPLIER_RECEIPT){
        $id = $this->purchase_db->select('id')->from($this->table_name)->where('return_number',$return_number)->where('return_status',$return_status)->get()->row_array();
        if(empty($id)){
            return true;
        }
        return false;
    }


    /**
     * 保存退款流水上传截图
     * 1.添加到退款流水信息表(pur_return_refund_flow_info)
     * 2.更新 入库后退货_应收款(pur_return_after_storage_collection)
     *              upload_screenshot_remark 上传备注
     *              upload_screenshot_user 上传用户
     *              upload_screenshot_time 上传时间
     *              act_refund_amount    实际退款
     * 3.入库后退货子表 (pur_return_after_storage_part)
     *              return_status 退货状态更新为待财务收款
     */
    public function save_upload_return_file($data){
        if(!empty($data)){
            $this->purchase_db->trans_begin();
            $fal = false;
            try{
                $false = $this->batch_save_flow_info($data['return_number'],$data);
                if($false){
                    $update_data['upload_screenshot_time'] = date('Y-m-d H:i:s');
                    $update_data['upload_screenshot_user'] = getActiveUserId();
                    $update_data['upload_screenshot_user_name'] = getActiveUserName();
                    $update_data['upload_screenshot_remark'] = $data['remark'];
                    $update_data['act_refund_amount'] = $data['act_refund_amount'];
                    $update_data['return_status'] = RETURN_STATUS_WAITING_FINANCIAL_RECEIVE;//更新为待财务收款

                    $false = $this->purchase_db->update($this->table_name,$update_data,['return_number'=>$data['return_number']]);
                    if($false){
                        unset($update_data);
                        $update_data['return_status'] = RETURN_STATUS_WAITING_FINANCIAL_RECEIVE;//更新为待财务收款
                        $false = $this->purchase_db->update($this->table_part,$update_data,['return_number'=>$data['return_number']]);
                        if($false){
                            $fal = true;
                        }
                    }
                }
                if($fal){
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
        }
        return [
            'success'=>$success,
            'message'=>$message
        ];
    }

    /**
     * 批量保存退款流水上传截图
     */
    public function batch_save_flow_info($return_number,$data){
        if(empty($data) or !isset($data['return_item'])) return false;
        $insert_data = [];
        $falses = false ;
        foreach ($data['return_item'] as $val){
            $insert_data[] = array(
                'return_number' => $return_number,
                'refund_serial_number' => $val['refund_serial_number'],
                'refund_amount' => $val['refund_amount'],
                'refund_time'   => $val['refund_time'],
                'update_time'   => date('Y-m-d H:i:s'),
                'update_user'   => getActiveUserId(),
                'update_user_name' => getActiveUserName(),
                'file_path'     => $val['file_path'],
            );
        }
        if(!empty($insert_data)){
            $falses = $this->purchase_db->insert_batch('return_refund_flow_info', $insert_data);
        }
        return $falses;
    }

    /**
     * 判断是否已经录入快递单号
     */
    public function is_express_info($return_number,$is_manually=1){
        $id = $this->purchase_db->select('id')->from($this->table_express)->where('return_number',$return_number)->where('is_manually',$is_manually)->get()->row_array();
        if(!empty($id)){
            return true;
        }
        return false;
    }

    /**
     * 保存手动录入的退货物流信息
     */
    public function save_express_info($return_number,$contact_person,$contact_number,$contact_addr,$express_items){
        $insert_data = [];
//        if(!is_array($express_items) or empty($express_items)) return false;
        $falses = false;
        foreach ($express_items as $val){
            $insert_data[] = array(
                'return_number' => $return_number,
                'contact_person' => $contact_person,
                'contact_number' => $contact_number,
                'contact_addr' => $contact_addr,
                'express_company_name' => $val['express_company_name'],
                'express_number' => $val['express_number'],
                'express_company_code' => $val['express_company_code'],
                'create_time'=>date('Y-m-d H:i:s'),
                'update_time'=>date('Y-m-d H:i:s'),
                'update_user'=>getActiveUserId(),
                'is_manually' => 1
            );
            $warehouse_code = $this->get_return_warehouse_code($return_number);
            $data[] = array(
                'order_id' => $return_number,
                'express_name' => $return_number,
                'express_no' => $val['express_number'],
                'receiver' => $contact_person,
                'contact_no' => $contact_number,
                'contact_addr' => $contact_addr,
                'warehouse_code' => $warehouse_code,
            );
        }
        if(!empty($insert_data)){
            if(!empty($data)){
                $url          = getConfigItemByName('api_config', 'wms_system', 'supplier_return_express_info'); //获取推送url
                $access_taken = getOASystemAccessToken();
                if (empty($url)) {
                    return ['code' => false, 'message' => 'api不存在'];
                }
                if (empty($access_taken)) {
                    return ['code' => false, 'message' => '获取access_token值失败'];
                }
                $url_api = $url . "?access_token=" . $access_taken;
                $results = getCurlData($url_api, json_encode($data), 'post', ['Content-Type: application/json']);
                $results = json_decode($results, true);
                if (isset($results['code']) and $results['code']==1) {
                    $falses = $this->purchase_db->insert_batch($this->table_express, $insert_data);
                    $message = '';
                }else{
                    $falses = false;
                    $message = $results['msg']??'推送WMS失败';
                }
            }
        }
        return [
            'success'=>$falses,
            'message'=>$message
        ];
    }

    /**
     * 获取物流轨迹
     */
    public function get_return_express_info($return_number,$is_manually=0){
        $expres_data = $this->purchase_db->select('express_company_name,express_number,express_company_code,status,contact_number')->from($this->table_express)->where('return_number',$return_number)->where('is_manually',$is_manually)->get()->result_array();
        if(!empty($expres_data)){
            foreach ($expres_data as $key=>$val){
                if(!empty($val['status'])){
                    $str_status = getTrackStatus($val['status']);
                }
                if(empty($str_status)) $str_status = '';
                $expres_data[$key]['status'] = $str_status;
            }
        }
        return $expres_data;
    }

    /**
     * 获取退款流水号
     */
    public function get_refund_number($return_number){
        $refund_number = $this->purchase_db->select('refund_serial_number')->from('return_refund_flow_info')->where('return_number',$return_number)->group_by('refund_serial_number')->get()->result_array();
        $str_number ='';
        if(!empty($refund_number)){
            $refund_number = array_column($refund_number,'refund_serial_number');
            $str_number = implode(' ',$refund_number);
        }
        return $str_number;
    }

    /**
     * 详情页
     * @author Manson
     * @param $ids
     * @return array
     */
    public function get_storage_item_list($return_number){
        if (empty($return_number)){
            return [];
        }
        $result = $this->purchase_db->select('a.main_number,a.part_number,a.supplier_code,a.supplier_name,a.pur_return_qty,a.wms_return_qty,wms_match_return_qty,a.return_cost,a.return_unit_price,a.contact_person,a.contact_number,a.contact_province,a.contact_addr,a.remark,a.freight_payment_type,a.restricted_supplier,b.sku, b.product_name, b.sample_packing_weight, b.return_qty, b.return_reason, b.return_warehouse_code, b.unit_price_without_tax')
            ->from($this->table_part. ' a')
            ->join($this->table_main. ' b','a.main_number = b.main_number', 'left')
            ->where_in('a.return_number',$return_number)
            ->get()->result_array();
        return $result;
    }

    /**
     * 根据退货单获收款取明细信息
     * @param $return_number
     */
    public function get_refund_flow_info($return_number){
        $flow_info = $this->purchase_db->select('a.refund_serial_number,a.refund_amount,a.refund_time,a.file_path')
            ->from('return_refund_flow_info  a')
            ->where('a.return_number',$return_number)
            ->get()->result_array();
        return $flow_info;
    }

    /**
     * 获取收款信息
     * @param $return_number
     * @return array
     */
    public function get_storage_collection_info($return_number){
        $storage_collectio = $this->purchase_db->select('b.colletion_time,b.colletion_user,b.colletion_remark,b.supplier_code,b.supplier_name,b.refundable_amount,b.upload_screenshot_remark,b.our_branch_short,b.our_branch,b.our_account,b.our_account_holder')
            ->from('return_after_storage_collection  b')
            ->where('b.return_number',$return_number)->get()->row_array();
        return $storage_collectio;
    }

    /**
     * 获取联系信息
     */
    public function get_storage_part_info($return_number){
        $result = $this->purchase_db->select('contact_person,contact_number,contact_addr,contact_province')->from($this->table_part)->where('return_number',$return_number)->get()->row_array();
     return $result;
    }

    /**
     * @param $express_no
     * @param $order_type
     * @return bool
     */
    public function get_Logistics_Trajectory($express_no, $order_type){
        $query = $this->purchase_db;
        $query->select('express_company_code carrier_code,status')->from($this->table_express)
            ->where('express_number',$express_no);

        if($order_type ==3){
            $query->where('is_manually',1);
        }else{
            $query->where('is_manually',0);
        }
        $res = $query->get()->row_array();

        if($res['carrier_code'] == 'JD' OR $res['carrier_code'] == 'other'){
            //京东快递直接不查询（暂时没有京东商家编码，无法查询）
            return false;
        }else{
            $customer_name = '';
        }
        if($res['status'] == RECEIVED_STATUS){
            $data = $query->select('track_detail')->from($this->table_logistics_track_detail)
                ->where('express_no',$express_no)->where('carrier_code',$res['carrier_code'])->get()->row_array();
            $result = $data['track_detail'];
        }else{
            $this->load->model('Logistics_info_model','logistics');
            $result = $this->logistics->get_track_by_kdbird($express_no,$res['carrier_code'],$customer_name);
        }
        return $result;
    }

    /**
     * 导出总的条数
     */
    public function export_sum($params){
//        $this->purchase_db->select('return_number,group_concat(part_number) part_number,sum(freight) freight,sum(return_amount) return_amount,sum(return_cost) return_cost,freight_payment_type,supplier_code,supplier_name,contact_person,contact_number,contact_province,contact_addr,wms_shipping_time,return_status')->from($this->table_name.' as c');
//      $this->purchase_db->where('return_status>=5');
        //添加筛选条件
        $query= $this->purchase_db;
        $query->select('c.return_number,c.supplier_code,c.supplier_name,c.refund_product_cost,c.freight_payment_type,c.return_status,c.act_refund_amount,c.is_confirm_receipt,c.is_confirm_time,c.wms_shipping_time,c.upload_screenshot_time,c.colletion_user,c.colletion_time,c.colletion_remark,');
        $query->from($this->table_name.' as c');
        //退货单号
        if(isset($params['return_number']) and !empty($params['return_number'])){
            $return_number = explode(' ', trim($params['return_number']));
            $query->where_in('c.return_number', array_filter($return_number));
        }

        //退货状态
        if(isset($params['return_status']) and !empty($params['return_status'])){
            $query->where('c.return_status',$params['return_status']);
        }
        //是否签收
        if(isset($params['is_confirm_receipt']) and !empty($params['is_confirm_receipt'])){
            $query->where('c.is_confirm_receipt',$params['is_confirm_receipt']);
        }
        //供应商编码
        if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
            $query->where('c.supplier_code',$params['supplier_code']);
        }

        //截图开始时间  upload_time_start  upload_time_end
        if(isset($params['upload_time_start']) and isset($params['upload_time_end']) and  !empty($params['upload_time_end'])){
            $start_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_end']));
            $query->where("c.upload_screenshot_time between '{$start_time}' and '{$end_time}' ");
        }

        //退货申请单
        if(isset($params['main_number']) and !empty($params['main_number'])){
            $query->join('return_after_storage_part as pr', 'pr.return_number=c.return_number');
            $main_number = explode(' ', trim($params['main_number']));
            $query->where_in('pr.main_number', array_filter($main_number));
        }

        //物流状态 退货
        if(isset($params['track_status']) and !empty($params['track_status'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number and ex.is_manually=0');
            $query->where_in('ex.status',$params['track_status']);
        }

        //拒绝 物流状态
        if(isset($params['refuse_track_status']) and !empty($params['refuse_track_status'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number and ex.is_manually=1');
            $query->where_in('ex.status',$params['refuse_track_status']);
        }

        //快递单号
        if(isset($params['express_number']) and !empty($params['express_number'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number');
            $express_number = explode(' ', trim($params['express_number']));
            $query->where_in('ex.express_number', array_filter($express_number));
        }


        //退款流水号
        if(isset($params['refund_serial_number']) and !empty($params['refund_serial_number'])){
            $refund_number = $params['refund_serial_number'];
            $refund_serial_number = explode(' ', trim($params['refund_serial_number']));
            $refund_serial_list   = array_map(function($array){
                return sprintf("'%s'", $array);
            }, $refund_serial_number);
            $refund_serial_list   = array_unique($refund_serial_list);
            $refund_serial_list   = implode(',', $refund_serial_list);

            $refund_list = $query->query("SELECT return_number FROM pur_return_refund_flow_info WHERE refund_serial_number in (".$refund_serial_list.")")->result_array();
            if(!empty($refund_list)){
                $return_number = array_column($refund_list,'return_number');
                $query->where_in('c.return_number',$return_number);
            }else{
                $query->where('c.return_number',$refund_number);
            }
        }

        //采购员
        if(isset($params['buyer_id']) and !empty($params['buyer_id'])){
            if(is_array($params['buyer_id'])){
                $buyer_id = implode(',',$params['buyer_id']);
            }
            $main_number = $query->query("SELECT main_number FROM pur_return_after_storage_main WHERE buyer_id in (".$buyer_id.")")->result_array();
            if(!empty($main_number)){
                $main_number = array_column($main_number,'main_number');
                $refund_number = $query->query("SELECT return_number FROM pur_return_after_storage_part WHERE main_number in (".$main_number.")")->result_array();
                if(!empty($refund_number)){
                    $return_number = array_column($refund_list,'return_number');
                    $query->where_in('c.return_number',$return_number);
                }else{
                    $query->where('c.return_number',1);
                }
            }else{
                $query->where('c.return_number',1);
            }
        }

//        $clone_db          = clone($query);
//        // 根据 return_number 维度计算记录个数
//        $count_sql = $clone_db->select('count(c.return_number) as num')->group_by('c.return_number')->get_compiled_select();
//        $count_row = $clone_db->query("SELECT count(cc.return_number) as num FROM ($count_sql) AS cc")->row_array();
//        $total = isset($count_row['num']) ? (int) $count_row['num'] : 0;

        $count_row = $query->select('count(c.return_number) as num')->get()->row_array();
        $total = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        return $total;
}

    /**
     * 导出退货跟踪号
     */
    public function export_storage_list($params,$offset,$limit){
        $query= $this->purchase_db;
        $query->select('c.return_number,c.supplier_code,c.supplier_name,c.refund_product_cost,c.freight_payment_type,c.return_status,c.act_refund_amount,c.is_confirm_receipt,c.is_confirm_time,c.wms_shipping_time,c.upload_screenshot_time,c.colletion_user,c.colletion_user_name,c.colletion_time,c.colletion_remark,c.act_freight,c.refundable_amount');
        $query->from('return_after_storage_collection as c');
        //退货单号
        if(isset($params['return_number']) and !empty($params['return_number'])){
            $return_number = explode(' ', trim($params['return_number']));
            $query->where_in('c.return_number', array_filter($return_number));
        }

        //退货状态
        if(isset($params['return_status']) and !empty($params['return_status'])){
            $query->where('c.return_status',$params['return_status']);
        }
        //是否签收
        if(isset($params['is_confirm_receipt']) and !empty($params['is_confirm_receipt'])){
            $query->where('c.is_confirm_receipt',$params['is_confirm_receipt']);
        }
        //供应商编码
        if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
            $query->where('c.supplier_code',$params['supplier_code']);
        }

        //截图开始时间  upload_time_start  upload_time_end
        if(isset($params['upload_time_start']) and isset($params['upload_time_end']) and  !empty($params['upload_time_end'])){
            $start_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_start']));
            $end_time = date('Y-m-d 00:00:00',strtotime($params['upload_time_end']));
            $query->where("c.upload_screenshot_time between '{$start_time}' and '{$end_time}' ");
        }

        //退货申请单
        if(isset($params['main_number']) and !empty($params['main_number'])){
            $query->join('return_after_storage_part as pr', 'pr.return_number=c.return_number');
            $main_number = explode(' ', trim($params['main_number']));
            $query->where_in('pr.main_number', array_filter($main_number));
        }

        //物流状态 退货
        if(isset($params['track_status']) and !empty($params['track_status'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number and ex.is_manually=0');
            $query->where_in('ex.status',$params['track_status']);
        }

        //拒绝 物流状态
        if(isset($params['refuse_track_status']) and !empty($params['refuse_track_status'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number and ex.is_manually=1');
            $query->where_in('ex.status',$params['refuse_track_status']);
        }

        //快递单号
        if(isset($params['express_number']) and !empty($params['express_number'])){
            $query->join('return_express_info as ex', 'ex.return_number=c.return_number');
            $express_number = explode(' ', trim($params['express_number']));
            $query->where_in('ex.express_number', array_filter($express_number));
        }


        //退款流水号
        if(isset($params['refund_serial_number']) and !empty($params['refund_serial_number'])){
            $refund_number = $params['refund_serial_number'];
            $refund_serial_number = explode(' ', trim($params['refund_serial_number']));
            $refund_serial_list   = array_map(function($array){
                return sprintf("'%s'", $array);
            }, $refund_serial_number);
            $refund_serial_list   = array_unique($refund_serial_list);
            $refund_serial_list   = implode(',', $refund_serial_list);

            $refund_list = $query->query("SELECT return_number FROM pur_return_refund_flow_info WHERE refund_serial_number in (".$refund_serial_list.")")->result_array();
            if(!empty($refund_list)){
                $return_number = array_column($refund_list,'return_number');
                $query->where_in('c.return_number',$return_number);
            }else{
                $query->where('c.return_number',$refund_number);
            }
        }

        //采购员
        if(isset($params['buyer_id']) and !empty($params['buyer_id'])){
            if(is_array($params['buyer_id'])){
                $buyer_id = implode(',',$params['buyer_id']);
            }
            $main_number = $query->query("SELECT main_number FROM pur_return_after_storage_main WHERE buyer_id in (".$buyer_id.")")->result_array();
            if(!empty($main_number)){
                $main_number = array_column($main_number,'main_number');
                $refund_number = $query->query("SELECT return_number FROM pur_return_after_storage_part WHERE main_number in (".$main_number.")")->result_array();
                if(!empty($refund_number)){
                    $return_number = array_column($refund_list,'return_number');
                    $query->where_in('c.return_number',$return_number);
                }else{
                    $query->where('c.return_number',1);
                }
            }else{
                $query->where('c.return_number',1);
            }
        }


        // 根据 return_number 维度计算记录个数
        $data = $query->group_by('c.return_number')->limit($limit, $offset)->get()->result_array();

        if(!empty($data)){
            foreach ($data as $key => $val){
                $track_list = $this->get_return_express_info($val['return_number'],1);
                if(empty($track_list))  $track_list = [];
                $refuse_track_list= $this->get_return_express_info($val['return_number']);
                if(empty($refuse_track_list))  $refuse_track_list = [];
                $refund_serial_number = $this->get_refund_number($val['return_number']);
                if(empty($refund_serial_number)) $refund_serial_number ='';
                $page_info = $this->get_storage_part_info($val['return_number']);
                $contact_person = $contact_number= $contact_addr= $contact_province ='';
                if(!empty($page_info)){
                    $contact_person = $page_info['contact_person'];
                    $contact_number= $page_info['contact_number'];
                    $contact_addr= $page_info['contact_addr'];
                    $contact_province = $page_info['contact_province'];
                }
                if(empty($val['freight_payment_type'])) {
                    $freight_payment_type = '';
                }else{
                    $freight_payment_type =  getFreightPaymentType($val['freight_payment_type']);
                }
                $data[$key]['freight_payment_type'] =$freight_payment_type;
                $data[$key]['return_status'] = getReturnStatus($val['return_status']);
                $data[$key]['is_confirm_receipt'] = getReturnIsConfirmReceipt($val['is_confirm_receipt']);
                $main_data_list = $this->get_main_number($val['return_number']);
                $data[$key]['main_number'] = isset($main_data_list['main_number'])?$main_data_list['main_number']:'';
                $data[$key]['track_list'] = $track_list;
                $data[$key]['refuse_track_list'] = $refuse_track_list;
                $data[$key]['refund_serial_number'] = $refund_serial_number;
                $data[$key]['contact_person'] = $contact_person;
                $data[$key]['contact_number']= $contact_number;
                $data[$key]['contact_addr']= $contact_addr;
                $data[$key]['contact_province'] = $contact_province;
                if($val['upload_screenshot_time'] =='0000-00-00 00:00:00'){
                    $data[$key]['upload_screenshot_time'] = '';
                }
            }
        }
        $return_data =['value'=> $data];
        return $return_data;
    }

    /**
     * 获取的退货仓库
     */
    public function get_return_warehouse_code($return_number){
        $warehouse_code ='';
        $result  = $this->purchase_db->select('return_warehouse_code')
            ->from($this->table_part)
            ->where('return_number',$return_number)
            ->get()->row_array();
        if(!empty($result)){
            $warehouse_code = $result['return_warehouse_code'];
        }else{
            $warehouse_code =  '';
        }
        return $warehouse_code;
    }

    /**
     * 采购-退货需求单合单配库
     */
    public function get_delivery_data_by_orderId($limit,$return_number){
        $url = getConfigItemByName('api_config', 'wms_system', 'get_delivery_data_by_orderId'); //获取推送url
        $access_taken = getOASystemAccessToken();
        if (empty($url)) {
            return ['code' => false, 'message' => 'api不存在'];
        }
        if (empty($access_taken)) {
            return ['code' => false, 'message' => '获取access_token值失败'];
        }

        //获取入库数据
        $this->purchase_db->select('return_number')->from($this->table_collection);

        if(!empty($return_number)){
            $this->purchase_db->where('return_number',$return_number);
        }else{
            $this->purchase_db->where('is_update', 0);
        }
        $return_number_list =  $this->purchase_db->limit($limit)->get()->result_array();
        if(!empty($return_number)){
            pr($return_number_list);
        }
        if (!empty($return_number_list)) {
            //获取
            $error = [];
            $error_str = '';
            foreach ($return_number_list as $value) {
                $data= array(
                    'orderId'=>$value['return_number']
                );
                $url_api = $url . "?access_token=" . $access_taken;
                $results = getCurlData($url_api, json_encode($data), 'post', ['Content-Type: application/json']);
                unset($data);
                $results = json_decode($results, true);
                if (isset($results['code']) and $results['code'] ==1 and isset($results['data'])) {//接口调用成功
                    if (!empty($results['data'])) {
                        $this->purchase_db->trans_begin();
                        $false = false;
                        try {
                            foreach ($results['data'] as $val) {
                                $update_data['wms_return_qty'] = $val['sku_num'];
                                $update_data['return_warehouse_code'] = $val['real_warehouse_code'];
                                $out_qty = $val['sku_num'];
                                //更新操作
                                $false = $this->purchase_db->update($this->table_part, $update_data, ['part_number' => $val['demand_order_id']]);
                                unset($update_data);
                                //获取数据
                                $main_part = $this->purchase_db->query("SELECT main_number FROM pur_return_after_storage_part WHERE part_number='" . $val['demand_order_id'] . "'")->row_array();
                                if (!empty($main_part)) {
                                    $main_number_list = $this->purchase_db->query("SELECT  processing_status,main_number,return_qty,sku,wms_return_qty,pur_return_qty,return_qty FROM pur_return_after_storage_main WHERE main_number='" . $main_part['main_number'] . "'")->row_array();
                                    if (!empty($main_number_list)) {
                                        $processing_status = '';
                                        $main_number = $main_number_list['main_number'];//申请ID
                                        $return_qty = $main_number_list['return_qty'];//申请数
                                        $wms_return_qty = isset($main_number_list['wms_return_qty']) ? $main_number_list['wms_return_qty'] : 0;//出库数
                                        $sum_out = $out_qty + $wms_return_qty;//本次出库加上次出库数量
                                        if ($return_qty == $sum_out) {
                                           $processing_status = RETURN_PROCESSING_STATUS_END;//已处理
                                        }
                                        if ($sum_out < $return_qty) {
                                            $processing_status = RETURN_PROCESSING_STATUS_PART;//部分已处理
                                        }
                                        if (empty($processing_status)) {
                                            $processing_status = $main_number_list['processing_status'];
                                        }
                                        $main_updaet['processing_status'] = $processing_status;//更新处理状态
                                        $main_updaet['wms_return_qty'] = $sum_out;//更新处理状态
                                        $main_updaet['end_time'] = $val['delivery_time'];
                                        $false = $this->purchase_db->update($this->table_main, $main_updaet, ['main_number' => $main_number]);
                                        unset($main_updaet);
                                    } else {
                                        $error_str.=$value['return_number'].'获取pur_return_after_storage_main数据为空';
                                        $false = false;
                                    }
                                } else {
                                    $error_str.=$value['return_number'].'获取pur_return_after_storage_part数据为空';
                                    $false = false;
                                }
                            }
                            if($false){
                                $update_collection['is_update'] = 1;
                                $false = $this->purchase_db->update($this->table_collection, $update_collection, ['return_number' =>$value['return_number']]);
                                unset($update_collection);
                                if($false){
                                    $this->purchase_db->trans_commit();
                                }else{
                                    $this->purchase_db->trans_rollback();
                                }
                            }else{
                                $this->purchase_db->trans_rollback();
                            }
                        }catch (Exception $e){
                            $this->purchase_db->trans_rollback();
                            $message=$e->getMessage();
                            $error_str.= $message;
                        }
                    }
                }else{
                    $error_str.=$value['return_number'].'获取wms数据为空';
                }
                $error[] = $error_str;
            }
        }else{
            return ['code' => false, 'message' => '获取数据为空'];
        }
        if(!empty($error)){
            return ['code' => false, 'message' => json_encode($error)];
        }else{
            return ['code' => true, 'message' =>'已经刷新'];
        }
    }
}