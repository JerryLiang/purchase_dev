<?php

/**
 * Created by PhpStorm.
 * 入库记录模型
 * User: Jaxton
 * Date: 2019/02/19 0029 11:50
 */
class Warehouse_storage_record_model extends Purchase_model {

    protected $table_name = 'warehouse_results';// 数据表名称(明细表)
    protected $main_table = 'warehouse_results_main';//(主表)
    protected $operator_key = 'REC_WARE_RECORDS';
    protected $data_abnormal_check_key = 'INVOICE_IS_ABNORMAL';//开票异常状态



    /**
     * 接收推送的仓库入库记录
     * @author 瑞文 韦尤
     * @param array $results
     * @return array
     */
    public function findWarehouse(array $results){
        $data = [];

        try{
            foreach($results as $key => $row){
                $purchase_number = $row['pur_number']; //采购单号
                $sku             = $row['sku']; //sku
                //判断仓库主表是否存在
                $bool = $this->get_warehouse_results_main($purchase_number, $sku);
                if(!$bool){ //存在更新
                    if($row['push_type'] == 1){ //质检入库
                        $res = $this->warehouse_results_update($row);
                        if(!$res){
                            $data['error_list'][$key]['pur_number'] = $row['pur_number'];
                            $data['error_list'][$key]['sku']        = $row['sku'];
                            $data['error_list'][$key]['express_no'] = $row['express_no'];
                            $data['error_list'][$key]['receipt_id'] = isset($row['receipt_id']) ? $row['receipt_id'] : '';
                            continue;
                        }
                    }
                    $results_status = $this->warehouse_results($row); //记录仓库明细表
                    if(!$results_status){
                        $data['error_list'][$key]['pur_number'] = $row['pur_number'];
                        $data['error_list'][$key]['sku']        = $row['sku'];
                        $data['error_list'][$key]['express_no'] = $row['express_no'];
                        $data['error_list'][$key]['receipt_id'] = isset($row['receipt_id']) ? $row['receipt_id'] : '';
                        continue;
                    }
                }else{ //不存在 插入
                    if($row['push_type'] == 1){ //质检入库
                        $relset = $this->warehouse_results_save($row);
                        if(!$relset){
                            $data['error_list'][$key]['pur_number'] = $row['pur_number'];
                            $data['error_list'][$key]['sku']        = $row['sku'];
                            $data['error_list'][$key]['express_no'] = $row['express_no'];
                            $data['error_list'][$key]['receipt_id'] = isset($row['receipt_id']) ? $row['receipt_id'] : '';
                            continue;
                        }
                        $items = $this->purchase_db
                            ->select('upselft_amount')
                            ->where('purchase_number', $purchase_number)
                            ->where('sku', $sku)
                            ->get('purchase_order_items')
                            ->row_array();
                        if(empty($items)){
                            $data['error_list'][$key]['pur_number'] = $row['pur_number'];
                            $data['error_list'][$key]['sku']        = $row['sku'];
                            $data['error_list'][$key]['express_no'] = $row['express_no'];
                            $data['error_list'][$key]['receipt_id'] = isset($row['receipt_id']) ? $row['receipt_id'] : '';
                            continue;
                            // throw new Exception('采购明细表不存在');
                        }

                        $this->purchase_db
                            ->where('purchase_number', $purchase_number)
                            ->where('sku', $sku)
                            ->update('purchase_order_items', ['upselft_amount' => $items['upselft_amount'] + $row['instock_qty']]);
                    }
                    $temps = $this->warehouse_results($row); //记录仓库明细表
                    if(!$temps){
                        $data['error_list'][$key]['pur_number'] = $row['pur_number'];
                        $data['error_list'][$key]['sku']        = $row['sku'];
                        $data['error_list'][$key]['express_no'] = $row['express_no'];
                        $data['error_list'][$key]['receipt_id'] = isset($row['receipt_id']) ? $row['receipt_id'] : '';
                        continue;
                    }
                }

                //更新采购单状态
                if($row['push_type'] == 1){
                    $this->rediss->set_sadd($this->operator_key,$row['pur_number']);// 把需要更新采购单状态、推送数据等操作放入 redis 队列中
                    $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$this->operator_key);
                }

                //更新开票是否异常状态
                $this->rediss->set_sadd($this->data_abnormal_check_key,sprintf('%s$$%s',$row['pur_number'],$row['sku']));
                $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$this->data_abnormal_check_key);

                $data['success_list'][$key]['pur_number'] = $row['pur_number'];
                $data['success_list'][$key]['sku']        = $row['sku'];
                $data['success_list'][$key]['express_no'] = $row['express_no'];
                $data['success_list'][$key]['receipt_id'] = isset($row['receipt_id']) ? $row['receipt_id'] : '';

            }

            return ['data' => $data, 'bool' => true];
        }catch(Exception $exc){
            return ['data' => $exc->getMessage(), 'bool' => false];

        }
    }

    /**
     * 更新采购单状态（仓库推送了入库记录 后异步变更采购单状态）
     * @author Jolon
     */
    public function update_order_status(){
        $len = $this->rediss->set_scard($this->operator_key);// 获取集合元素的个数

        if($len){
            $count = ($len > 100)?100:$len;
            $this->load->model('purchase/Purchase_order_model');
            $_SESSION['user_name'] = '系统';// 设置默认用户，getActiveUsername会用到

            for($i = 0;$i < $count;$i ++){
                $purchase_number = $this->rediss->set_spop($this->operator_key);
                $purchase_number = current($purchase_number);
                try{
                    $purchase_order = $this->Purchase_order_model->get_one($purchase_number, false);
                    if(!in_array($purchase_order['purchase_order_status'], [
                        //PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                        //PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND,
                        //PURCHASE_ORDER_STATUS_CANCELED,
                        PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT])){// 作废处理中、信息修改待审核的都不改采购单状态

                        $this->Purchase_order_model->change_status($purchase_number); // 统一入口修改采购单状态
                    }

                }catch(Exception $e){
                    $this->rediss->set_sadd($this->operator_key,$purchase_number);// 执行失败 下次继续执行
                    echo $e->getMessage();
                }
            }

            exit("执行成功");
        }else{
            exit("没有需要操作的数据");
        }
    }

    /**
     * 判断采购单状态 - 根据 采购单确认数量、取消数量、入库数量 判断状态
     *      如果 $new_status 状态 为 等待采购询价，信息修改待审核状态，待采购审核，等待生成进货单，信息修改驳回 则原样返回采购单和备货单状态
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param string $new_status      目标采购状态
     * @return boolean|array
     * @exp 返回结果示例
     *      Array
     *      (
     *          [purchase_order_status] => 10
     *          [suggest_order_status] => Array
     *          (
     *              [RD60449026] => 10
     *              [RD60449027] => 10
     *          )
     *      )
     */
    public function get_order_status_by_instock_qty($purchase_number,$new_status = null){
        $status_list = [
            'purchase_order_status' => null,// 采购单状态
            'suggest_order_status'  => [// 备货单状态

            ]
        ];
        $purchase_order_status  = null;// 采购单状态
        $suggest_order_status   = [];// 备货单状态列表

        $sku_amount_list        = $this->get_purchase_order_info($purchase_number); //获取 采购单+SKU 的采购数量（含需求单号）
        $demand_number_list     = array_column($sku_amount_list,'demand_number','combine_key');// 备货单号列表
        if(empty($sku_amount_list)) return false;

        // 这些状态不用 调用公共方法，直接设置（原样返回采购单和备货单状态）
        if(!is_null($new_status) and in_array($new_status,
                         [
                             PURCHASE_ORDER_STATUS_WAITING_QUOTE,// 等待采购询价
                             PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,// 信息修改待审核状态
                             PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,// 待采购审核
                             PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,// 待销售审核
                             PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER,// 等待生成进货单
                             PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,// 信息修改驳回
                             PURCHASE_ORDER_STATUS_CANCELED,// 已作废订单
                         ])){// 指定状态 不变，原样返回（备货单和采购单状态保持一致）
            $purchase_order_status = $new_status;
            foreach($demand_number_list as $combine_key => $demand_number){
                $suggest_order_status[$demand_number] = $new_status;
            }
            $status_list['purchase_order_status'] = $purchase_order_status;
            $status_list['suggest_order_status']  = $suggest_order_status;
            return $status_list;
        }

        $sku_cancel_list  = $this->get_cancel_info($purchase_number);//获取 采购单+SKU 的已取消数量
        $sku_instock_list = $this->get_warehouse_results_instock_qty($purchase_number);//获取 采购单+SKU 的已入库数量
        $sku_loss_list    = $this->get_reportloss_info($purchase_number);// 获取 采购单+SKU 的报损数量

        if($sku_cancel_list){// 判断是否有 作废订单待审核，作废订单待退款的记录
            foreach($sku_cancel_list as $cancel_key => $cancel_value){
                $demand_number  = $demand_number_list[$cancel_value['combine_key']];// PO+SKU对应的备货单号
                if(in_array($cancel_value['audit_status'],[CANCEL_AUDIT_STATUS_CG])){// 待采购经理审核
                    $suggest_order_status[$demand_number] = PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT;// 作废订单待审核
                }elseif(in_array($cancel_value['audit_status'],[CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT])){
                    $suggest_order_status[$demand_number] = PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND;// 作废订单待退款
                }
            }
        }
        if($sku_loss_list){// 判断是否有 作废订单待审核
            foreach($sku_loss_list as $loss_key => $loss_value){
                $demand_number  = $demand_number_list[$loss_value['combine_key']];// PO+SKU对应的备货单号
                if(in_array($loss_value['status'],[REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT])){// 待采购经理审核,待财务经理审核
                    $suggest_order_status[$demand_number] = PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT;// 作废订单待审核
                }
            }
        }

        if(empty($sku_amount_list)) return false;

        // 组合数据
        $sku_amount_list  = arrayKeyToColumn($sku_amount_list,'combine_key');// 唯一记录
        $sku_instock_list = arrayKeyToColumn($sku_instock_list,'combine_key');// 唯一记录
        if($sku_cancel_list){// 组合数据，取有用数据（可能存在多条记录）
            $sku_cancel_list_tmp = [];
            foreach($sku_cancel_list as $cancel_key => $cancel_value){
                $combine_key = $cancel_value['combine_key'];
                if(!in_array($cancel_value['audit_status'],[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])){
                    continue;// 财务已收款,系统自动通过 的才算取消数量
                }
                if(isset($sku_cancel_list_tmp[$combine_key])){
                    $sku_cancel_list_tmp[$combine_key]['cancel_ctq'] += $cancel_value['cancel_ctq'];
                }else{
                    $sku_cancel_list_tmp[$combine_key] = $cancel_value;
                }
            }
            $sku_cancel_list = $sku_cancel_list_tmp;
            unset($sku_cancel_list_tmp);
        }
        if($sku_loss_list){// 组合数据，取有用数据（可能存在多条记录）
            $sku_loss_list_tmp = [];
            foreach($sku_loss_list as $loss_key => $loss_value){
                $combine_key = $loss_value['combine_key'];
                if(in_array($loss_value['status'],[REPORT_LOSS_STATUS_FINANCE_PASS])){// 报损已通过 的才算报损数量
                    if(isset($sku_cancel_list_tmp[$combine_key])){
                        $sku_loss_list_tmp[$combine_key]['loss_amount'] += $loss_value['loss_amount'];
                    }else{
                        $sku_loss_list_tmp[$combine_key] = $loss_value;
                    }
                }
            }
            $sku_loss_list = $sku_loss_list_tmp;
            unset($sku_loss_list_tmp);
        }


        // 以下是自动计算的状态
        // 根据  采购数量、入库数量、取消数量、报损数量  判断每个 备货单 状态
        foreach($sku_amount_list as $combine_key => $sku_value){
            $demand_number  = $demand_number_list[$combine_key];// PO+SKU对应的备货单号
            $confirm_amount = $sku_value['confirm_amount'];
            $instock_qty    = isset($sku_instock_list[$combine_key]) ? $sku_instock_list[$combine_key]['instock_qty'] : 0;
            $cancel_qty     = isset($sku_cancel_list[$combine_key]) ? $sku_cancel_list[$combine_key]['cancel_ctq'] : 0;
            $loss_qty       = isset($sku_loss_list[$combine_key]) ? $sku_loss_list[$combine_key]['loss_amount'] : 0;

            $combine_key_status = null;
            $cancel_qty         = $cancel_qty + $loss_qty;// 报损数量累加到 取消数量上
            if($cancel_qty > 0){
                if($instock_qty == 0){
                    if($cancel_qty >= $confirm_amount){
                        $combine_key_status = PURCHASE_ORDER_STATUS_CANCELED;// 已作废订单：全部取消
                    }else{
                        $combine_key_status = PURCHASE_ORDER_STATUS_WAITING_ARRIVAL;// 等待到货：部分取消且没入库数量
                    }
                }else{
                    if($instock_qty + $cancel_qty >= $confirm_amount){
                        $combine_key_status = PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE;// 部分到货不等待剩余到货：有入库数量且 没有剩余
                    }else{
                        $combine_key_status = PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE;// 部分到货等待剩余到货：有入库数量且 还有剩余
                    }
                }
            }else{
                if($instock_qty == 0){
                    $combine_key_status = PURCHASE_ORDER_STATUS_WAITING_ARRIVAL;// 等待到货：没取消且没入库
                }else{
                    if($instock_qty >= $confirm_amount){
                        $combine_key_status = PURCHASE_ORDER_STATUS_ALL_ARRIVED;// 全部到货：全部入库
                    }else{
                        $combine_key_status = PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE;// 部分到货等待剩余到货：有入库数量且还有剩余
                    }
                }
            }

            if(!isset($suggest_order_status[$demand_number])){// 作废订单待审核，作废订单待退款状态的优先级最高
                $suggest_order_status[$demand_number] = $combine_key_status;
            }
        }

        // 可能有的状态：作废订单待审核，作废订单待退款，已作废订单，等待到货，部分到货等待剩余到货，部分到货不等待剩余到货，全部到货
        $suggest_order_status_unique = array_unique($suggest_order_status);

        // 根据备货单状态 计算 采购单状态
        if(count($suggest_order_status_unique) == 1){
            $purchase_order_status = current($suggest_order_status_unique);                                             // 采购单状态 与 备货单状态相同
        }else{
            $suggest_order_status_unique = array_diff($suggest_order_status_unique,[PURCHASE_ORDER_STATUS_CANCELED]);   // 去除已作废订单状态

            if(count($suggest_order_status_unique) == 1){
                $purchase_order_status = current($suggest_order_status_unique);                                         // 采购单状态 与 备货单状态相同（等待到货、全部到货）
                if($purchase_order_status == PURCHASE_ORDER_STATUS_ALL_ARRIVED and in_array(PURCHASE_ORDER_STATUS_CANCELED,$suggest_order_status)){// 有且仅有 全部到货和已作废订单 两种状态的 为部分到货不等待剩余
                    $purchase_order_status = PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE;
                }
            }else{
                if(in_array(PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT, $suggest_order_status)){
                    $purchase_order_status = PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT;                                // 作废订单待审核：至少1个备货单状态为作废订单待审核
                }elseif(in_array(PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND, $suggest_order_status)){
                    $purchase_order_status = PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND;                               // 作废订单待收款：至少1个备货单的取消未到货状态为作废订单待收款
                }elseif(array_intersect([PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE],$suggest_order_status_unique)){
                    $purchase_order_status = PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE;                          // 部分到货等待剩余：含有 等待到货 或 部分到货等待剩余 状态的都为 部分到货等待剩余
                }else{
                    $purchase_order_status = PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE;                      // 部分到货不等待剩余：其他情况
                }
            }
        }

        $status_list['purchase_order_status'] = $purchase_order_status;
        $status_list['suggest_order_status']  = $suggest_order_status;
        return $status_list;
    }

    /**
     * 判断入库是否异常 - 根据 采购单确认数量、取消数量、入库数量 判断
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @param null $sku
     * @param bool $is_update
     * @return array|bool
     */
    public function check_is_storage_abnormal($purchase_number,$sku = null,$is_update = true){
        $sku_amount_list  = $this->get_purchase_order_info($purchase_number,$sku); //获取 采购单+SKU 的采购数量（含需求单号）
        $sku_cancel_list  = $this->get_cancel_info($purchase_number,$sku);//获取 采购单+SKU 的已取消数量
        $sku_instock_list = $this->get_warehouse_results_instock_qty($purchase_number,$sku);//获取 采购单+SKU 的已入库数量
        $sku_loss_list    = $this->get_reportloss_info($purchase_number,$sku);// 获取 采购单+SKU 的报损数量

        // 过滤掉无效数据
        if($sku_cancel_list){// 保留已取消/取消中的 取消数量
            foreach($sku_cancel_list as $cancel_key => $cancel_value){
                if(!in_array($cancel_value['audit_status'],[CANCEL_AUDIT_STATUS_CG,CANCEL_AUDIT_STATUS_CF,CANCEL_AUDIT_STATUS_SCJT,CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])){
                    unset($sku_cancel_list[$cancel_key]);
                }
            }
        }
        if($sku_loss_list){// 保留已报损/报损中数量
            foreach($sku_loss_list as $loss_key => $loss_value){
                if(!in_array($loss_value['status'],[REPORT_LOSS_STATUS_WAITING_MANAGER_AUDIT,REPORT_LOSS_STATUS_WAITING_FINANCE_AUDIT,REPORT_LOSS_STATUS_FINANCE_PASS])){
                    unset($sku_loss_list[$loss_key]);
                }
                if($loss_value['audit_time'] < '2019-10-20 23:59:59'){// 排除异常中已经存在的400多个报损成功的数据（2019-10-21 号上线报损不转入库数量的已审核通过的408条数据）
                    unset($sku_loss_list[$loss_key]);
                }
            }
        }

        // 组合数据
        $sku_amount_list  = arrayKeyToColumn($sku_amount_list,'combine_key');// 唯一记录
        $sku_instock_list = arrayKeyToColumn($sku_instock_list,'combine_key');// 唯一记录
        if($sku_cancel_list){// 组合数据，取有用数据（可能存在多条记录）
            $sku_cancel_list_tmp = [];
            foreach($sku_cancel_list as $cancel_key => $cancel_value){
                $combine_key = $cancel_value['combine_key'];
                if(isset($sku_cancel_list_tmp[$combine_key])){
                    $sku_cancel_list_tmp[$combine_key]['cancel_ctq'] += $cancel_value['cancel_ctq'];
                }else{
                    $sku_cancel_list_tmp[$combine_key] = $cancel_value;
                }
            }
            $sku_cancel_list = $sku_cancel_list_tmp;
            unset($sku_cancel_list_tmp);
        }
        if($sku_loss_list){// 组合数据，取有用数据（可能存在多条记录）
            $sku_loss_list_tmp = [];
            foreach($sku_loss_list as $loss_key => $loss_value){
                $combine_key = $loss_value['combine_key'];
                if(isset($sku_cancel_list_tmp[$combine_key])){
                    $sku_loss_list_tmp[$combine_key]['loss_amount'] += $loss_value['loss_amount'];
                }else{
                    $sku_loss_list_tmp[$combine_key] = $loss_value;
                }
            }
            $sku_loss_list = $sku_loss_list_tmp;
            unset($sku_loss_list_tmp);
        }

        // 满足正常的条件：入库数量+sum取消数量+申请取消数量+已报损/报损中数量=采购采购数量 且 入库日志中的总入库数量=采购单列表中的入库数量
        // 入库是否异常(1.是,2.否)
        $abnormal_demand_list = [];// 异常列表
        $normal_demand_list   = [];// 正常列表

        if(empty($sku_amount_list) or !is_array($sku_amount_list)) return false;

        foreach($sku_amount_list as $combine_key => $sku_amount_value){
            $detail_instock_qty = $this->purchase_db->select('sum(instock_qty) as instock_qty')
                ->where('purchase_number', $sku_amount_value['purchase_number'])
                ->where('sku', $sku_amount_value['sku'])
                ->get($this->table_name)
                ->row_array();

            $now_sku_amount     = $sku_amount_value['confirm_amount'];
            $now_sku_cancel     = isset($sku_cancel_list[$combine_key])?$sku_cancel_list[$combine_key]['cancel_ctq']:0;
            $now_sku_loss       = isset($sku_loss_list[$combine_key])?$sku_loss_list[$combine_key]['loss_amount']:0;
            $total_instock_qty  = isset($sku_instock_list[$combine_key])?$sku_instock_list[$combine_key]['instock_qty']:0;

            // 满足正常的条件
            if((intval($detail_instock_qty) == intval($total_instock_qty)) and
                (intval($total_instock_qty) + intval($now_sku_cancel) + intval($now_sku_loss) == intval($now_sku_amount))){
                $normal_demand_list[$sku_amount_value['demand_number']] = $sku_amount_value['demand_number'];
            }else{
                $abnormal_demand_list[$sku_amount_value['demand_number']] = $sku_amount_value['demand_number'];
            }
        }

        if($is_update === true){
            if($normal_demand_list) $this->purchase_db->where_in('demand_number',$normal_demand_list)->update('purchase_suggest',['is_storage_abnormal' => 2]);// 2.正常
            if($abnormal_demand_list) $this->purchase_db->where_in('demand_number',$abnormal_demand_list)->update('purchase_suggest',['is_storage_abnormal' => 1]);// 1.异常
        }

        return ['normal_demand_list' => $normal_demand_list,'abnormal_demand_list' => $abnormal_demand_list];

    }

    /**
     * 获取已入库数量
     * @param string $purchase_number
     * @param $sku
     * @return array
     */
    public function get_warehouse_results_instock_qty($purchase_number,$sku = null){
        $this->purchase_db
            ->select('concat(purchase_number,sku) as combine_key,purchase_number,sku,instock_qty')
            ->where('purchase_number', $purchase_number);

        if($sku) $this->purchase_db->where('sku',$sku);

        $list = $this->purchase_db->get('warehouse_results_main')->result_array();

        return !empty($list) ? $list : [];
    }

    /**
     * 获取采购单已取消数据
     * @author  Jaxton
     * @param $purchase_number
     * @param $sku
     * @return mixed
     */
    public function get_cancel_info($purchase_number,$sku = null){
        $this->purchase_db->select('concat(a.purchase_number,a.sku) as combine_key,a.purchase_number,a.sku,a.cancel_ctq,b.audit_status,b.is_edit')
            ->from('purchase_order_cancel_detail a')
            ->join('purchase_order_cancel b', 'a.cancel_id=b.id', 'left')
            ->where(['a.purchase_number' => $purchase_number]);
            //->where_in('b.audit_status', [CANCEL_AUDIT_STATUS_CFYSK, CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC])

        if($sku) $this->purchase_db->where('a.sku',$sku);

        $list = $this->purchase_db->get()->result_array();

        return !empty($list)?$list:[];
    }


    /**
     * 获取采购单已报损的数量
     * @param $purchase_number
     * @param $sku
     * @return array
     */
    public function get_reportloss_info($purchase_number,$sku = null){
        $this->purchase_db->select('concat(a.pur_number,a.sku) as combine_key,a.pur_number,a.sku,SUM(a.loss_amount) AS loss_amount,a.status,a.audit_time')
            ->from('purchase_order_reportloss a')
            ->where(['a.pur_number' => $purchase_number]);

        if($sku) $this->purchase_db->where('a.sku',$sku);

        $list = $this->purchase_db->group_by('a.status,a.sku')->get()->result_array();

        return !empty($list)?$list:[];
    }

    /**
     * 判断仓库主表是否存在
     * @author harvin
     * @date   2019-4-11
     * @param type $purchase_number 采购单号
     * @param type $sku             sku
     * @return boolean]
     */
    public function get_warehouse_results_main($purchase_number, $sku){
        $main = $this->purchase_db
            ->select('*')
            ->where('purchase_number', $purchase_number)
            ->where('sku', $sku)->get($this->main_table)
            ->row_array();

        if(empty($main)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更新仓库主表记录
     * @author harvin
     * @date   2019-4-11
     * @param array $row
     * @return boolean
     * @throws Exception
     */
    public function warehouse_results_update(array $row){
        if(!is_array($row)){
            throw new Exception('不是数组格式,');
        }
        $main = $this->purchase_db
            ->select('*')
            ->where('purchase_number', $row['pur_number'])
            ->where('sku', $row['sku'])->get($this->main_table)
            ->row_array();
        if(empty($main)){
            return false;
            // throw new Exception('采购单号' . $row['pur_number'] . 'sku:' . $row['sku'] . '不存在');
        }
        //计算明细表入库数量
        $warehouse_results = $this->purchase_db->select('*')
            ->where('purchase_number', $row['pur_number'])
            ->where('receipt_id', $row['receipt_id'])
            ->where('sku', $row['sku'])
            ->get($this->table_name)
            ->result_array();
        if(empty($warehouse_results)){ //防止重复入库
            $instock_qty             = $main['instock_qty'] + $row['instock_qty'];
            $data['items_id']        = $this->get_order_items_id($row['pur_number'], $row['sku']);
            $data['purchase_number'] = $row['pur_number']; //采购单号
            $data['sku']             = $row['sku']; //sku
            $data['purchase_qty']    = $row['purchase_quantity']; //采购数量
            $data['arrival_qty']     = $row['arrival_quantity']; //到货数量
            $data['arrival_date']    = $row['delivery_date']; //到货时间
            $data['bad_qty']         = $row['nogoods']; //不良品数量
            $data['instock_qty']     = $instock_qty; //入库数量
            $data['instock_date']    = $row['instock_date']; //入库时间
            $data['check_qty']       = $row['check_qty']; //检查数量
            $data['update_time']     = date('Y-m-d H:i:s'); //更新时间
            $update                  = $this->purchase_db
                ->where('purchase_number', $row['pur_number'])
                ->where('sku', $row['sku'])
                ->update($this->main_table, $data);
            if(empty($update)){
                return false;
                //  throw new Exception('更新仓库主表失败');
            }
            $items = $this->purchase_db
                ->select('upselft_amount')
                ->where('purchase_number', $row['pur_number'])
                ->where('sku', $row['sku'])
                ->get('purchase_order_items')
                ->row_array();
            if(empty($items)){
                return false;
                //  throw new Exception('采购明细表不存在');
            }
            $res = $this->purchase_db
                ->where('purchase_number', $row['pur_number'])
                ->where('sku', $row['sku'])
                ->update('purchase_order_items', ['upselft_amount' => $items['upselft_amount'] + $row['instock_qty']]);
            if($res){
                return true;
            }else{
                return false;
            }
        }

        return true;
    }

    /**
     * 记录仓库主表信息
     * @author harvin
     * @param array $row
     * @throws Exception
     */
    public function warehouse_results_save(array $row){
        $data['items_id']        = $this->get_order_items_id($row['pur_number'], $row['sku']);
        $data['purchase_number'] = $row['pur_number']; //采购单号
        $data['sku']             = $row['sku']; //sku
        $data['purchase_qty']    = $row['purchase_quantity']; //采购数量
        $data['arrival_qty']     = $row['arrival_quantity']; //到货数量
        $data['arrival_date']    = $row['delivery_date']; //到货时间
        $data['bad_qty']         = $row['nogoods']; //不良品数量
        $data['instock_qty']     = $row['instock_qty']; //上架数量
        $data['instock_date']    = $row['instock_date']; //入库时间
        $data['check_qty']       = $row['check_qty']; //检查数量
        $data['create_time']     = date('Y-m-d H:i:s');//创建时间
        $insert                  = $this->purchase_db->insert($this->main_table, $data);
        if(empty($insert)){
            return false;
            //  throw new Exception('记录仓库主表失败');
        }

        return true;
    }


    /**
     * 获取明细id
     * @author harvin
     * @date   2019-4-11
     * @param type $purchase_number
     * @param type $sku
     * @return type
     * @throws Exception
     */
    public function get_order_items_id($purchase_number, $sku){
        $items = $this->purchase_db
            ->select('id')
            ->where('purchase_number', $purchase_number)
            ->where('sku', $sku)
            ->get('purchase_order_items')
            ->row_array();
        if(empty($items)){
            return false;
            // throw new Exception('采购单号'.$purchase_number.'sku:'.$sku.'明细表不存在');
        }

        return $items['id'];
    }

    /**
     * 记录或更新仓库明细表
     * @return harvin
     * @param array $row
     */
    public function warehouse_results(array $row){
        if($row['push_type'] == 1){ //质检入库
            $items_id = $this->get_order_items_id($row['pur_number'], $row['sku']);
            if($items_id == false){
                return false;
            }
            $data['items_id']           = $items_id;
            $data['purchase_number']    = $row['pur_number'];          //采购单号
            $data['sku']                = $row['sku'];                //sku
            $data['express_no']         = $row['express_no'];  //快递单号
            $data['purchase_qty']       = $row['purchase_quantity']; //采购数量
            $data['arrival_qty']        = $row['arrival_quantity'];         //到货数量
            $data['qurchase_num']       = $row['able_delevery_num'];   //应到数量
            $data['quality_level_num']  = $row['quality_level_num'];   //抽检数量
            $data['quality_level']      = $row['quality_level'];          //质检级别
            $data['defective_num']      = $row['defective_num'];          //次品数量
            $data['defective_type']     = $row['defective_type'];          //次品类型
            $data['storage_position']   = $row['storage_position'];     //入库货位
            $data['defective_position'] = $row['defective_position'];  //次品货位
            $data['quality_username']   = $row['quality_username'];      //质检人
            $data['quality_all']        = $row['quality_all'];                //是否全检，1：否，2：是
            $data['quality_time']       = $row['quality_time'];              //质检时间
            $data['quality_result']     = $row['quality_result'];       //质检结果 1合格，2不合格',
            $data['quality_all_time']   = $row['quality_all_time'];       //全检时间
            $data['upper_end_time']     = $row['upper_end_time'];          //上架完成时间
            $data['delevery_time_long'] = $row['delevery_time_long'];  //时效开始时间到上架完成时间的时间间隔
            $data['arrival_date']       = $row['delivery_date'];  //到货时间
            $data['delivery_user']      = $row['delivery_user'];  // 收货人
            $data['bad_qty']            = $row['nogoods'];              //不良品数量
            $data['instock_qty']        = $row['instock_qty'];                   //入库数量
            $data['receipt_number']     = $row['receipt_number'];    //入库单号
            $data['instock_user_name']  = $row['instock_user'];             // 入库人
            $data['instock_date']       = $row['instock_date'];             //入库时间
            $data['check_qty']          = $row['check_qty'];                   //品检数量
            $data['check_type']         = $row['check_type'];                 //品检类型
            $data['receipt_id']         = $row['receipt_id'];                //仓库的receipt  id 类型不为整形的原因是有多个仓库
            $data['instock_total']      = $row['instock_platform'];  //总入库平台项目
            $data['create_time']        = date('Y-m-d H:i:s'); //创建时间
            //判断是否存在相同记录
            $warehouse_results_info = $this->purchase_db->select('*')->where('receipt_id', $row['receipt_id'])
                ->where('purchase_number', $row['pur_number'])
                ->where('sku', $row['sku'])
                ->get($this->table_name)
                ->row_array();
            if(!empty($warehouse_results_info)){
                return false;
                // throw new Exception('质检入库重复入库,请检查'); 
            }
            $insert = $this->purchase_db->insert($this->table_name, $data);
            if(empty($insert)){
                return false;
                // throw new Exception('记录仓库明细失败');
            }

            return true;
        }elseif($row['push_type'] == 2){
            //上架完成推送
            $items_id = $this->get_order_items_id($row['pur_number'], $row['sku']);
            if($items_id == false){
                return false;
            }
            $data['items_id']           = $items_id;
            $data['purchase_number']    = $row['pur_number'];          //采购单号
            $data['sku']                = $row['sku'];                //sku
            $data['express_no']         = $row['express_no'];  //快递单号
            $data['purchase_qty']       = $row['purchase_quantity']; //采购数量
            $data['arrival_qty']        = $row['arrival_quantity'];         //到货数量
            $data['qurchase_num']       = $row['able_delevery_num'];   //应到数量
            $data['quality_level_num']  = $row['quality_level_num'];   //抽检数量
            $data['quality_level']      = $row['quality_level'];          //质检级别
            $data['defective_num']      = $row['defective_num'];          //次品数量
            $data['defective_type']     = $row['defective_type'];          //次品类型
            $data['storage_position']   = $row['storage_position'];     //入库货位
            $data['defective_position'] = $row['defective_position'];  //次品货位
            $data['quality_username']   = $row['quality_username'];      //质检人
            $data['quality_all']        = $row['quality_all'];                //是否全检，1：否，2：是
            $data['quality_time']       = $row['quality_time'];              //质检时间
            $data['quality_result']     = $row['quality_result'];       //质检结果 1合格，2不合格',
            $data['quality_all_time']   = $row['quality_all_time'];       //全检时间
            $data['upper_end_time']     = $row['upper_end_time'];          //上架完成时间
            $data['delevery_time_long'] = $row['delevery_time_long'];  //时效开始时间到上架完成时间的时间间隔
            $data['arrival_date']       = $row['delivery_date'];  //到货时间
            $data['delivery_user']      = $row['delivery_user'];  // 收货人
            $data['bad_qty']            = $row['nogoods'];              //不良品数量
            $data['instock_qty']        = $row['instock_qty'];           //入库数量
            $data['receipt_number']     = $row['receipt_number'];    //入库单号
            $data['instock_user_name']  = $row['instock_user'];             // 入库人
            $data['instock_date']       = $row['instock_date'];             //入库时间
            $data['check_qty']          = $row['check_qty'];                   //品检数量
            $data['check_type']         = $row['check_type'];                 //品检类型：1抽检；2全检；3免检
            $data['receipt_id']         = $row['receipt_id'];                //仓库的receipt  id 类型不为整形的原因是有多个仓库
            $data['instock_total']      = $row['instock_platform'];  //总入库平台项目
            $data['update_time']        = date('Y-m-d H:i:s'); //创建时间
            $update                     = $this->purchase_db
                ->where('purchase_number', $row['pur_number'])
                ->where('sku', $row['sku'])
                ->where('receipt_id', $row['receipt_id'])
                ->update($this->table_name, $data);
            if(empty($update)){
                return false;
                // throw new Exception('更新仓库明细失败');
            }

            return true;
        }else{
            return false;
            //  throw new Exception('仓库推送所属阶段,不清楚');
        }


    }

    /**
     * 获取 PO 采购数量
     * @param $purchase_number
     * @param $sku
     * @return array
     */
    public function get_purchase_order_info($purchase_number,$sku = null){
        $this->purchase_db->select('poi.id as item_id,concat(poi.purchase_number,poi.sku) as combine_key,poi.purchase_number,poi.sku,poi.confirm_amount,map.demand_number')
            ->from('purchase_order_items as poi')
            ->join('purchase_suggest_map as map','poi.purchase_number=map.purchase_number and poi.sku=map.sku','left')
            ->where('poi.purchase_number',$purchase_number);

        if($sku){
            $this->purchase_db->where('poi.sku',$sku);
        }

        $list = $this->purchase_db->get()->result_array();

        return !empty($list)?$list:[];
    }

    /**
     * 获取 PO 采购单信息
     * @param $purchase_number
     * @return array
     */
    public function get_purchase_order($purchase_number){
        $list = $this->purchase_db->select('purchase_number,purchase_order_status')
            ->from('purchase_order')
            ->where('purchase_number',$purchase_number)
            ->get()
            ->result_array();
        return !empty($list)?$list:[];
    }


    /**
     * 获取 PO 采购数量
     * @param $purchase_number
     * @return array
     */
    public function get_purchase_order_and_suggest($purchase_number){
        $list = $this->purchase_db->select('po.purchase_number,po.purchase_order_status,poi.sku,sg.demand_number,sg.suggest_order_status,sg.is_storage_abnormal,poi.confirm_amount,sg.purchase_amount,map.confirm_number as map_confirm_number')
            ->from('purchase_order as po')
            ->join('purchase_order_items as poi','po.purchase_number=poi.purchase_number','left')
            ->join('purchase_suggest_map as map','poi.purchase_number=map.purchase_number and poi.sku=map.sku','left')
            ->join('purchase_suggest as sg','sg.demand_number=map.demand_number and sg.sku=map.sku','left')
            ->where('poi.purchase_number',$purchase_number)
            ->get()
            ->result_array();

        return !empty($list)?$list:[];
    }


    /**
     * 获取采购单已报损数据
     * @author  Jaxton
     * @param $purchase_number
     * @param $sku
     * @return mixed
     */
    public function get_loss_info($purchase_number, $sku){
        $list = $this->purchase_db
            ->select('loss_amount')
            ->from('purchase_order_reportloss')
            ->where(['pur_number' => $purchase_number, 'sku' => $sku, 'status' => REPORT_LOSS_STATUS_FINANCE_PASS])
            ->get()
            ->row_array();
        if($list && isset($list['loss_amount'])){
            return $list['loss_amount'];
        }else{
            return 0;
        }
    }

    //报损状态(0.待经理审核,1.经理驳回,2.待财务审核,3.财务驳回,4.已通过)
    public function get_loss_info_new($purchase_number,$sku){

        $list = $this->purchase_db
            ->select('loss_amount')
            ->from('purchase_order_reportloss')
            ->where(['pur_number' => $purchase_number, 'sku' => $sku])
            ->where_in(['status'=>[0,2,4]])
            ->get()
            ->row_array();
        if($list && isset($list['loss_amount'])){
            return $list['loss_amount'];
        }else{
            return 0;
        }
    }
}