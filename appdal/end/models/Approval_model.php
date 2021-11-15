<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Approval_model extends Purchase_model {

    protected $table_name = 'purchase_blue_process';
    protected $table_purchase_order_items_name = 'purchase_order_items';//采购单明细表

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase/purchase_order_model');
        $this->load->helper('status_order');
        $this->load->helper('common');
        $this->load->model('Reject_note_model');
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $this->load->model('abnormal/abnormal_list_model');
    }

    /**
     * 更新采购单状态
     * @param josn $temp 蓝凌返回的josn数据
     * @author 2019-1-14
     * * */
    public function update_blue($temp, $processid, $purchase_number) {
        $data = [
            'callback' => $temp['CallBack'],
            'custompara' => json_encode($temp['CustomPara']),
            'result' => $temp['Result'],
            'usercode' => $temp['UserCode'],
            'operatetime' => $temp['OperateTime'],
            //  'sku' => $sku,
            'purchase_number' => $purchase_number
        ];
        $this->purchase_db->where('processid', $processid)->where('purchase_number', $purchase_number)->update($this->table_name, $data);
        if ($temp['Result'] == 1) {
            $result = 1;
            $reject_order_note = '蓝灵审核通过';
            //更改采购单状态
            $result = $this->purchase_order_model->audit_order($purchase_number, $result, $reject_order_note,$temp['UserCode']);
            if (empty($result['code'])) {// 失败
                return true;
            } else {
                return false;
            }
        } elseif ($temp['Result'] == 0) {
            $result = 10;
            $reject_order_note = '蓝灵审核驳回';
            $result = $this->purchase_order_model->audit_order($purchase_number, $result, $reject_order_note,$temp['UserCode']);
            if (empty($result['code'])) {// 失败
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 蓝凌推送接口
     * @param json $formValues 表单数据
     * @param varchar $username 创建人
     * @param varchar $docsubject 表单说明
     * @param varchar $id 表单id
     * @return  Object 返回id
     * @author harvin 2019-1-24
     * * */
    public function orderlient($formValues, $username, $docsubject, $id) {
        $url = getConfigItemByName('api_config','lanling','lanling_url');
        $soap = new SoapClient($url);
        $result = $soap->__soapCall('addReview', [['arg0' => [
            'docCreator' => '{"LoginName":"' . $username . '"}',
            'docStatus' => 20,
            'docSubject' => $docsubject,
            'fdTemplateId' => $id,
            'formValues' => $formValues
        ]]]);
        return $result;
    }

    /**
     * 更新請款單數據
     * @param array $temp 返回數據
     * * */
    public function update_request($temp) {
        $requisition_number = $temp['CustomPara']['requisition_number']; //請款單號
        $data = [
            'callback' => $temp['CallBack'],
            'custompara' => json_encode($temp['CustomPara']),
            'result' => $temp['Result'],
            'usercode' => $temp['UserCode'],
            'operatetime' => $temp['OperateTime'],
            'requisition_number' => $requisition_number
        ];
        $this->purchase_db->where('processid', $temp['ProcessID'])->where('requisition_number', $requisition_number)->update('purchase_blue_pay', $data);

        if ($temp['Result'] == 1) {
            //審核通過
            $this->purchase_db->where('requisition_number', $requisition_number)->update('purchase_order_pay', ['pay_status' => 40, 'review_time' => $temp['OperateTime'], 'auditor' => '藍凌系統']);
            $purchase_number_order = $this->purchase_db->select('pur_number')->where('requisition_number', $requisition_number)->get('purchase_order_pay')->row_array();
            // 更新采购单请款状态
            $result_2 = $this->purchase_db->where('purchase_number', $purchase_number_order['pur_number'])->update('purchase_order', ['pay_status' => 40]);
            if (empty($result_2)) {
                throw new Exception('创建网采单-请款单-更新采购单请款状态失败');
            } else {
                operatorLogInsert(['id' => $purchase_number_order['pur_number'],
                    'type' => 'purchase_order',
                    'content' => '采购单请款状态',
                    'detail' => '更新采购单付款状态为【' . getPayStatus(40) . '】'
                ]);
            }
        } elseif ($temp['Result'] == 0) {
            // 更新采购单请款状态
            $result_2 = $this->purchase_db->where('purchase_number', $purchase_number_order['pur_number'])->update('purchase_order', ['pay_status' => 21]);
            operatorLogInsert(['id' => $purchase_number_order['pur_number'],
                'type' => 'purchase_order',
                'content' => '采购单请款状态',
                'detail' => '更新采购单付款状态为【' . getPayStatus(21) . '】'
            ]);
        }
    }

    /**
     *  更新請款單數據
     * @param array $temp 返回數據
     * * */
    public function update_compact($temp) {
        $requisition_number = $temp['CustomPara']['requisition_number']; //請款單號
        $data = [
            'callback' => $temp['CallBack'],
            'custompara' => json_encode($temp['CustomPara']),
            'result' => $temp['Result'],
            'usercode' => $temp['UserCode'],
            'operatetime' => $temp['OperateTime'],
            'requisition_number' => $requisition_number
        ];
        $this->purchase_db->trans_begin();
        try {
            $this->purchase_db->where('processid', $temp['ProcessID'])->where('requisition_number', $requisition_number)->update('purchase_blue_pay', $data);
            if ($temp['Result'] == 1) {
                //审核通过
                $res = [
                    'review_notice' => '蓝凌审核通过',
                    'auditor' => '',
                    'review_time' => $temp['OperateTime'],
                    'pay_status' => 30
                ];
                $log = [
                    'record_number' => $requisition_number,
                    'record_type' => '请款单',
                    'content' => '请款单审核过',
                    'content_detail' => '请款单号' . $requisition_number . '审核通过',
                ];
            } elseif ($temp['Result'] == 0) {
                //审核驳回
                $res = [
                    'review_notice' => '蓝凌审核驳回',
                    'auditor' => '',
                    'review_time' => $temp['OperateTime'],
                    'pay_status' => 21
                ];
                $log = [
                    'record_number' => $requisition_number,
                    'record_type' => '请款单',
                    'content' => '请款单审驳回',
                    'content_detail' => '请款单号' . $requisition_number . '审核驳回',
                ];
                $pay_status = ['pay_status' => 21];
            }
            //通过申请单号 获取合同单号
            $order_pay = $this->purchase_db->select('pur_number')->where('requisition_number', $requisition_number)->get('purchase_order_pay')->row_array();
            //通过合同号 去获取采购单号
            $order_compac = $this->purchase_db->select('purchase_number')->where('compact_number', $order_pay['pur_number'])->get('purchase_compact_items')->result_array();
            foreach ($order_compac as $order_number) {
                $this->purchase_db->where('purchase_number', $order_number)->update('purchase_order', $pay_status);
            }
            $this->purchase_db->where_in('id', $id)->update('purchase_order_pay', $res);
            $this->Reject_note_model->get_insert_log($log);
            if ($this->purchase_db->trans_status() === false) {
                $this->purchase_db->trans_rollback();
            } else {
                $this->purchase_db->trans_commit();
                return TRUE;
            }
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            return false;
        }
    }


    /**
     * @desc 获取已过期的需求单
     * @author Jeff
     * @Date 2019/03/23 16:35
     * @return array
     * @return
     */
    public function get_expired_suggest()
    {
        $now = date("Y-m-d H:i:s");
        $result = $this->purchase_db->select('id,demand_number,suggest_status,source_from')
            ->where("expiration_time<'{$now}'")
            ->where('suggest_status<>'.SUGGEST_STATUS_EXPIRED)
            ->where('suggest_status<>'.SUGGEST_STATUS_FINISHED)
//            ->where('purchase_type_id',PURCHASE_TYPE_INLAND)//1.1.1暂停接入计划系统后,只有国内仓有过期时间
            ->where_in('audit_status',[SUGGEST_AUDITED_PASS,SUGGEST_AUDITED_UN_PASS])
            ->get('purchase_suggest')
            ->result_array();
        return $result;
    }


    /**
     * 更新 采购需求信息
     * @param     $update_data
     * @param int $suggest_id
     * @return bool
     */
    public function update_suggest($expired_suggest='',$suggest_id = []){
        $return = ['code' => false,'message' => '','data' => ''];
        if(empty($suggest_id)) return false;

        $this->purchase_db->trans_begin();
        try{
            // 更新数据
            $temp_arr = [];

            // 保存操作记录
            foreach ($suggest_id as $key => $id) {

                //构造推送数据
                if ($expired_suggest[$key]['source_from']==1){//数据来源于计划系统才推送计划系统
                    $temp_arr[] = [
                        'pur_sn' => $expired_suggest[$key]['demand_number'],
                        'state' => SUGGEST_STATUS_EXPIRED,
                        'business_line' => $expired_suggest[$key]['purchase_type_id'],//业务线
                    ];
                }

                $update_data[$key]['id'] = $id;
                $update_data[$key]['suggest_status'] = SUGGEST_STATUS_EXPIRED;
            }

            $update_res = $this->purchase_db->update_batch('purchase_suggest', $update_data,'id');

            if(empty($update_res)){
                throw new Exception("需求单状态更新失败");
            }

            if (!empty($temp_arr)){
                //推送计划系统
                $push_plan = $this->push_plan_expiration($temp_arr);
                if($push_plan !== true){
                    throw new Exception('推送计划系统作废失败！');
                }
            }

            $this->purchase_db->trans_commit();

            $return['code'] = true;

        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $return['message'] = $e->getMessage();
        }

        if ($return['code']){
            echo '需求单id'.json_encode($suggest_id).'过期状态更新成功';
        }else{
            $messge = $return['message'].' 需求单id为: '.json_encode($suggest_id).PHP_EOL;
            //记录日志
            file_put_contents(date('Y-m-d').'推送计划系统过期需求单失败.txt',$messge,FILE_APPEND);

        }

    }

    /**
     * @desc 推送过期需求单到计划系统
     * @author Jeff
     * @Date 2019/4/2 14:49
     * @param $expiration_data
     * @throws Exception
     */
    public function push_plan_expiration($expiration_data)
    {
        if(PUSH_PLAN_SWITCH == false) return;
        $start = $this->get_microtime();

        $push_data['data_list'] = $expiration_data;
        $push_data = json_encode($push_data);
        $access_token = getOASystemAccessToken();
        //推送计划系统
        $url = getConfigItemByName('api_config', 'java_system_plan', 'push_expiration_suggest');
        $url    = $url.'?access_token='.$access_token;
        $header = ['Content-Type: application/json'];
        operatorLogInsert(
            [
                'id' => 'push_plan_expiration_request',
                'type' => 'push_plan_expiration_request',
                'content' => '推送过期需求单到计划系统',
                'detail' => $push_data,
            ]
        );
        $res = getCurlData($url, $push_data, 'POST',$header);
        $end = $this->get_microtime();
        operatorLogInsert(
            [
                'id' => 'push_plan_expiration_response',
                'type' => 'push_plan_expiration_response',
                'content' => '推送过期需求单到计划系统',
                'detail' => "response:{$res}...start:{$start}...end:{$end}",
            ]
        );

        if (!is_json($res)) throw new Exception('计划系统返回的不是json: '.$res);

        $result = json_decode($res, TRUE);

        if(isset($result['code']) && $result['code']==200){
            return true;
        }elseif(isset($result['code']) && $result['code']!=200){
            throw new Exception('推送计划返回信息, '.$result['msg']);
        }elseif(isset($result['error'])){
            throw new Exception('推送计划返回信息, '.isset($result['error_description'])?$result['error_description']:$result['message']);
        }
        return false;
    }

    //仓库驳回异常列表数据
    public function warehouse_abnormal_reject($data)
    {
        $result = ['status'=>0 , 'msg'=>'', 'data'=>[], 'fail'=>[]];
        $this->purchase_db->trans_begin();
        try {
            $defective_ids = [];
            $fail_ids      = [];

            foreach ($data as $k => $v) {

                $abnormal_data = $this->abnormal_list_model->get_one_abnormal($v['defective_id']);

                if (!empty($abnormal_data)) {
                    $update_data['is_handler']               = 0;// 是否处理 因为是驳回的数据  需要采购重新处理
                    $update_data['is_push_warehouse']        = 0;// 处理推送至仓库
                    $update_data['warehouse_handler_result'] = !empty($v['reburt_reson']) ? "驳回:" . $v['reburt_reson'] : '驳回';// 处理结果
                    $update_data['warehouse_handler_time']   = date('Y-m-d H:i:s');// 仓库处理时间
                    $update_data['deal_used']                = 0;// 处理时效重新计算
                    $update_data['pull_time']                = date('Y-m-d H:i:s');// 处理时效重新计算
                    $update_data['create_time']              = date('Y-m-d H:i:s');// 标红/标橙时间重新计算
                    $where['defective_id'] = $v['defective_id'];

                    //记录标记成功的异常单号
                    $update_res = $this->purchase_db->update('purchase_warehouse_abnormal', $update_data, $where);

                    //根據採購單號和sku更新採購單明細表，異常處理狀態
                    if (!empty($abnormal_data['pur_number']) && !empty($abnormal_data['sku'])) {
                        $this->purchase_db->where(['purchase_number' => $abnormal_data['pur_number'], 'sku' => $abnormal_data['sku']])
                            ->update($this->table_purchase_order_items_name, ['abnormal_flag' => 1]);
                    } elseif (!empty($abnormal_data['pur_number'])) {
                        $this->purchase_db->where(['purchase_number' => $abnormal_data['pur_number']])
                            ->update($this->table_purchase_order_items_name, ['abnormal_flag_no_sku' => 1]);
                    }

                    if ($update_res && !in_array($v['defective_id'], $defective_ids)) {
                        $defective_ids[] = $v['defective_id'];
                    } else {
                        if (!in_array($v['defective_id'], $fail_ids)) {
                            $fail_ids[] = $v['defective_id'];
                        }
                    }
                } else {
                    if (!in_array($v['defective_id'], $fail_ids)) {
                        $fail_ids[] = $v['defective_id'];
                    }
                }

                $insert_res = operatorLogInsert(
                    [
                        'id' => $v['defective_id'],
                        'type' => 'purchase_warehouse_abnormal',
                        'content' => '仓库驳回采购异常单',
                        'detail' => '异常单号'.$v['defective_id'].'仓库驳回',
                        'user' => '仓库',
                    ]
                );
                if(empty($insert_res)) throw new Exception($v['defective_id'].":异常单操作记录添加失败");

            }

            $result['status'] = 1;
            $result['data']   = $defective_ids;
            $result['fail']   = $fail_ids;
            $this->purchase_db->trans_commit();
        }catch(\Exception $e){
            $this->purchase_db->trans_rollback();

            $result['msg'] = $e->getMessage();
        }

        return $result;

    }

    //获取仓库异常单退货信息
    public function save_return_data($data)
    {
        //事务开启
        $this->purchase_db->trans_begin();
        try{
            $failList=[];
            $successList=[];
            $time = date('Y-m-d H:i:s');

            foreach ($data as $v){
                $record = $this->purchase_db->where(['data_id'=>$v['data_id']])->get('excep_return_info')->row_array();

                //若是重新推送过来的数据，先删除已存在数据，再按照新数据插入
                if(!empty($record)){
                    $_data = array(
                        'update_time' => $time,
                        'is_del' => 1,
                    );
                    $where = array(
                        'data_id' => $v['data_id'],
                        'is_del' => 0
                    );
                    $this->purchase_db->update('excep_return_info', $_data, $where);
                    $res = $this->purchase_db->affected_rows();
                    $insert_res = operatorLogInsert(
                        [
                            'id' => $v['data_id'],
                            'type' => 'pur_excep_return_info',
                            'content' => '仓库更新采购异常单退货记录信息',
                            'detail' => '数据编号为：'.$v['data_id'].' 的退货单被标识为删除共'. $res .'条。',
                            'user' => '仓库',
                        ]
                    );
                    if(empty($insert_res)) throw new Exception($v['express_no'].":异常单操作记录添加失败");
                }

                //验证数据
                $express_no = explode(' ', trim($v['express_no']));
                $delivery_company = explode(',', $v['delivery_company']);
                $carrier_code = explode(',', $v['carrier_code']);

                if (0 == count($express_no) OR count($express_no) != count($delivery_company) OR count($delivery_company) != count($carrier_code)) {
                    $failList[] = $v['data_id'];
                    continue;
                }

                //组织插入数据
                $save_data = array();
                foreach ($express_no as $key => $item) {
                    $save_data[$key]['express_no'] = $item;                         //退货快递单号
                    $save_data[$key]['express_company'] = $delivery_company[$key]; //退货快递商
                    $save_data[$key]['carrier_code'] = $carrier_code[$key];        //快递公司编码
                    $save_data[$key]['excep_number'] = $v['excep_number'];        //异常单号
                    $save_data[$key]['pur_number'] = $v['pur_number'];            //采购单号
                    $save_data[$key]['return_user'] = $v['return_user'];          //退货人
                    $save_data[$key]['return_time'] = $v['return_time'];          //退货时间
                    $save_data[$key]['return_status'] = $v['return_status'];      //退货状态
                    $save_data[$key]['data_id'] = $v['data_id'];                  //对应仓库数据的唯一ID
                    $save_data[$key]['create_time'] = $time;
                }
                if (empty($save_data)) throw new Exception('推送的退货快递单数据格式有误');

                $res = $this->purchase_db->insert_batch('excep_return_info', $save_data);
                $insert_res = operatorLogInsert(
                    [
                        'id' => $record['excep_number'],
                        'type' => 'pur_excep_return_info',
                        'content' => '仓库更新采购异常单退货记录信息',
                        'detail' => '退货物流单号:' . implode(',', array_column($save_data, 'express_no')),
                        'user' => '仓库',
                    ]
                );
                if (empty($insert_res)) throw new Exception($v['excep_number'] . ":异常单操作记录添加失败");

                //根据异常单号更新仓库处理结果
                $update_data['warehouse_handler_result'] = '仓库返回结果:退货';     // 处理结果
                $update_data['warehouse_handler_time'] = $time;                   // 仓库处理时间
                $where_update['defective_id'] = $v['excep_number'];
                $update_res = $this->purchase_db->update('purchase_warehouse_abnormal', $update_data, $where_update);
                if (empty($update_res)) throw new Exception($v['excep_number'] . ":异常单操作记录更新失败");

                if (!$res) {
                    $failList[] = $v['data_id'];
                } else {
                    $successList[] = $v['data_id'];
                }
            }

            if ($this->purchase_db->trans_status() === FALSE)
            {
                //事务回滚
                $this->purchase_db->trans_rollback();
            }else{
                //事务提交
                $this->purchase_db->trans_commit();
            }
        }catch (Exception $e){
            //事务回滚
            $this->purchase_db->trans_rollback();
            $successList =[];
            echo json_encode(['successList' => $successList, 'failList' => $failList, 'msg' => $e->getMessage()]);
            exit();
        }
        echo json_encode(['successList'=>$successList,'failList'=>$failList]);
        exit();
    }

    //获取满足条件的异常单
    public function get_abmomal_list_ids()
    {
        $ids = $this->purchase_db->select('defective_id')
            ->where_in('is_handler', [1])
            ->Where_in('is_push_warehouse', [1])
            ->Where('warehouse_handler_result', '')
            ->Where('handler_type <>', null)
            ->get('purchase_warehouse_abnormal',50)
            ->result_array();

        return $ids;
    }

    //根据defective_id获取异常当
    public function get_abmomal_list($defective_ids)
    {
        $list = $this->purchase_db->select('id,defective_id,warehouse_handler_result')
            ->where_in('defective_id', $defective_ids)
            ->where('warehouse_handler_result', '')
            ->get('purchase_warehouse_abnormal')
            ->result_array();

        return $list;
    }

}
