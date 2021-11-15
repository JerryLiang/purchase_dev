<?php
/**
 * 采购单与入库单冲销记录
 * User: Jolon
 * Date: 2020/04/14 10:00
 */

class Charge_against_records_model extends Purchase_model
{

    protected $table_name = 'purchase_order_charge_against_records';
    protected $table_purchase_statement = 'purchase_statement';                                        //对账单表
    protected $table_purchase_statement_item = 'purchase_statement_items';                             //对账单详情表
    protected $table_purchase_order = 'purchase_order';                                                //采购单信息表
    protected $table_purchase_order_items = 'purchase_order_items';                                    //采购单明细表
    protected $table_charge_against_surplus = 'purchase_order_charge_against_surplus';                 //采购单冲销结余
    protected $table_purchase_order_cancel = 'purchase_order_cancel';                                  //采购单-取消未到货数量-主表
    protected $table_cancel_to_receipt = 'purchase_order_cancel_to_receipt';                           //取消未到货-上传截图后流向应收款表



    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
        $this->lang->load('common_lang');
    }

    /**
     * 获取 冲销记录数据
     * @param $params
     * @return array
     */
    public function get_charge_against_records($params)
    {
        if (isset($params['id'])) {// 记录ID
            if (is_array($params['id'])) {
                $this->purchase_db->where_in('id', $params['id']);
            } else {
                $this->purchase_db->where('id', $params['id']);
            }
            unset($params['id']);
        }
        if (isset($params['record_number'])) {// 冲销操作编号
            if (is_array($params['record_number'])) {
                $this->purchase_db->where_in('record_number', $params['record_number']);
            } else {
                $this->purchase_db->where('record_number', $params['record_number']);
            }
            unset($params['record_number']);
        }
        if (isset($params['purchase_number'])) {// 采购单号
            if (is_array($params['purchase_number'])) {
                $this->purchase_db->where_in('purchase_number', $params['purchase_number']);
            } else {
                $this->purchase_db->where('purchase_number', $params['purchase_number']);
            }
            unset($params['purchase_number']);
        }

        $this->purchase_db->where($params);

        $records = $this->purchase_db->get($this->table_name)->result_array();
        return $records;
    }

    /**
     * 获取 冲销记录汇总数据
     * @param        $params
     * @param int    $record_type
     * @param string $group_by
     * @return array|bool
     */
    public function get_charge_against_records_gather($params,$record_type, $group_by = 'purchase_number')
    {
        if (empty($params)) return false;

        // 必须要指定单号，否则查询全表数据
        if( (!isset($params['id']) or empty($params['id']))
            and (!isset($params['record_number']) or empty($params['record_number']))
            and (!isset($params['purchase_number']) or empty($params['purchase_number']))
        ){
            return false;
        }

        $this->purchase_db->select(
            $group_by . ','
            . 'SUM(charge_against_amount) AS charge_against_amount,'
            . 'SUM(charge_against_product) AS charge_against_product,'
            . 'SUM(charge_against_freight) AS charge_against_freight,'
            . 'SUM(charge_against_discount) AS charge_against_discount,'
            . 'SUM(charge_against_process_cost) AS charge_against_process_cost'
        );

        if (isset($params['id'])) {// 记录ID
            if($params['id']){
                if (is_array($params['id'])) {
                    $this->purchase_db->where_in('id', $params['id']);
                } else {
                    $this->purchase_db->where('id', $params['id']);
                }
            }
            unset($params['id']);
        }
        if (isset($params['record_number'])) {// 冲销操作编号
            if($params['record_number']){
                if (is_array($params['record_number'])) {
                    $this->purchase_db->where_in('record_number', $params['record_number']);
                } else {
                    $this->purchase_db->where('record_number', $params['record_number']);
                }
            }
            unset($params['record_number']);
        }
        if (isset($params['purchase_number'])) {// c采购单号
            if($params['purchase_number']){
                if (is_array($params['purchase_number'])) {
                    $this->purchase_db->where_in('purchase_number', $params['purchase_number']);
                } else {
                    $this->purchase_db->where('purchase_number', $params['purchase_number']);
                }
            }
            unset($params['purchase_number']);
        }

        $this->purchase_db->where('record_type', $record_type);

        $records = $this->purchase_db->where($params)
            ->group_by($group_by)
            ->get($this->table_name)
            ->result_array();

        return $records;

    }

    /**
     * 其他冲销审核 列表
     * @param array $params
     * @param int $offset
     * @param int $limit
     * @param int $page
     * @param bool $export
     * @return array
     */
    public function get_charge_against_list($params = array(), $offset = 1, $limit = 20, $page = 1, $export = false)
    {
        $query = $this->purchase_db;
        $query->select('a.id,a.charge_against_number,a.purchase_number,a.record_type,a.charge_against_amount,a.charge_against_product,a.charge_against_process_cost,a.charge_against_status');
        $query->select('a.record_number,a.record_number_relate,a.create_user_name,a.create_time,a.create_notice,a.audit_user_name,a.audit_time,a.audit_remark,b.purchase_name,b.is_drawback,b.supplier_code,b.supplier_name,b.source');
        $query->from("{$this->table_name} a");
        $query->join("{$this->table_purchase_order} b", 'a.purchase_number=b.purchase_number', 'LEFT');
        //冲销类型（2.退款冲销）
        $query->where('a.record_type', 2);
        //供应商编码
        if (!empty($params['supplier_code'])) {
            $query->where('b.supplier_code', $params['supplier_code']);
        }
        //冲销编码
        if (!empty($params['charge_against_number'])) {
            $query->where('a.charge_against_number', $params['charge_against_number']);
        }
        //取消未到货编码
        if (!empty($params['record_number'])) {
            $query->where('a.record_number', $params['record_number']);
        }
        //关联的采购单号
        if (!empty($params['purchase_number_relate'])) {
            $query->where('a.record_number_relate', $params['purchase_number_relate']);
        }
        //(接收抵冲)采购单号
        if (!empty($params['purchase_number'])) {
            $query->where('a.purchase_number', $params['purchase_number']);
        }
        //申请人
        if (!empty($params['apply_user_id'])) {
            $query->where('a.create_user_id', $params['apply_user_id']);
        }
        //申请时间
        if (!empty($params['apply_date_start']) && !empty($params['apply_date_end'])) {
            $query->where('a.create_time >=', $params['apply_date_start'] . ' 00:00:00');
            $query->where('a.create_time <=', $params['apply_date_end'] . ' 23:59:59');
        }
        //审核状态
        if (isset($params['audit_status']) && !is_null($params['audit_status']) && is_numeric($params['audit_status'])) {
            $query->where('a.charge_against_status', $params['audit_status']);
        }
        //审核时间
        if (!empty($params['audit_date_start']) && !empty($params['audit_date_end'])) {
            $query->where('a.audit_time >=', $params['audit_time'] . ' 00:00:00');
            $query->where('a.audit_time <=', $params['apply_date_end'] . ' 23:59:59');
        }
        //采购来源（1-合同，2-网采）
        if (isset($params['source']) && !is_null($params['source']) && is_numeric($params['source'])) {
            $query->where('b.source', $params['source']);
        }


        $count_qb = clone $query;
        //统计总数要加上前面筛选的条件
        $total_count = $count_qb->get()->num_rows();

        //列表查询
        $result = $query->order_by('a.id', 'DESC')->limit($limit, $offset)->get()->result_array();

        //<editor-fold desc="数据转换">
        foreach ($result as $key => $item) {
            //冲销类型
            $result[$key]['record_type_cn'] = (2 == $item['record_type']) ? '退款冲销' : '';
            //冲销金额总额=抵冲的商品额+抵冲的加工费
            $result[$key]['charge_against_amount_total'] = $item['charge_against_amount'];
            //审核状态
            $result[$key]['charge_against_status_cn'] = !is_null($item['charge_against_status']) ? getRefundChargeAgainstStatus($item['charge_against_status']) : '';
            //是否退税
            $result[$key]['is_drawback_cn'] = !is_null($item['is_drawback']) ? getIsDrawback($item['is_drawback']) : '';
            //采购来源
            $result[$key]['source_cn'] = !is_null($item['source']) ? getPurchaseSource($item['source']) : '';
            //审核状态标识
            if (CHARGE_AGAINST_STATUE_WAITING_AUDIT == $item['charge_against_status']) {
                $result[$key]['audit_type'] = 'purchase';
            } elseif (CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT == $item['charge_against_status']) {
                $result[$key]['audit_type'] = 'finance';
            } else {
                $result[$key]['audit_type'] = '';
            }
        }
        //</editor-fold>

        //<editor-fold desc="表头字段">
        $key_table = array(
            $this->lang->myline('hx_apply_code'),
            $this->lang->myline('hx_ca_union_code'),
            $this->lang->myline('hx_purchase_number'),
            $this->lang->myline('hx_ca_type'),
            $this->lang->myline('hx_ca_amount'),
            $this->lang->myline('hx_ca_audit_status'),
            $this->lang->myline('hx_ca_purchase_name'),
            $this->lang->myline('hx_ca_supplier_name'),
            $this->lang->myline('hx_ca_source'),
            $this->lang->myline('hx_ca_apply_user'),
            $this->lang->myline('hx_ca_apply_remark'),
            $this->lang->myline('hx_ca_audit_user'),
            $this->lang->myline('hx_ca_audit_remark'),
        );
        //</editor-fold>

        if ($export) {
            $return_data = [
                'values' => $result,
                'total' => $total_count
            ];
        } else {
            $return_data = [
                'key' => $key_table,
                'values' => $result,
                'page_data' => [
                    'total' => $total_count,
                    'offset' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total_count / $limit),
                ],
                'drop_down_box' => [
                    'charge_against_status' => getRefundChargeAgainstStatus(),          //审核状态
                    'source' => getPurchaseSource(),                                    //采购来源
                    'apply_user' => getAllUserDropDown(),                               //申请人下拉
                ]
            ];
        }
        return $return_data;
    }

    /**
     * 【冲销入库明细】采购单 与 入库批次进行冲销 - 自动冲销
     * @param array $instock_batch_list 入库批次类型（PO_INSTOCKBATCH）
     * @return array
     */
    public function check_inventory_item_charge_against($instock_batch_list)
    {
        $return = ['code' => false, 'message' => '', 'data' => ''];

        $success_list = $error_list = [];

        $this->load->model('statement/Purchase_inventory_items_model');
        $this->load->model('statement/Charge_against_surplus_model');

        foreach ($instock_batch_list as $value) {
            if (stripos($value, '_') === false) {
                $error_list[] = $value . ' 参数错误，未匹配到入库批次号';
                continue;
            }
            list($purchase_number, $instock_batch) = explode('_', $value);

            if (empty($purchase_number) or empty($instock_batch)) {
                $error_list[] = $value . ' 参数错误，未匹配到入库批次号';
                continue;
            }

            // 冲销
            // 1.生成 采购单与入库单冲销记录
            $result = $this->do_inventory_item_charge_against($purchase_number, $instock_batch);

            if ($result['code']) {

                // 2.更新 采购单剩余可冲销金额
                $this->Charge_against_surplus_model->recalculate_surplus($purchase_number);
                // 3.更新 入库批次剩余可冲销金额
                $this->Charge_against_surplus_model->recalculate_inventory_item_surplus($instock_batch);

                $success_list[] = $value;
            } else {
                $error_list[] = $value . ' ' . $result['message'];
            }
        }


        $return['code'] = true;
        $return['message'] = "总共选中" . count($instock_batch_list) . "个数据，冲销成功" . count($success_list) . "个，失败" . count($error_list) . "个";
        $return['data'] = [
            'success_list' => $success_list,
            'error_list' => $error_list
        ];

        return $return;
    }

    /**
     * 执行冲销系统级操作
     * @param string $purchase_number
     * @param string $instock_batch
     * @return array
     */
    public function do_inventory_item_charge_against($purchase_number, $instock_batch)
    {
        $return = ['code' => false, 'message' => '', 'data' => ''];

        // 获取入库记录，入库批次剩余可冲销金额
        $inventory_item_list = $this->Purchase_inventory_items_model->get_inventory_items(['instock_batch' => $instock_batch]);
        if (empty($inventory_item_list) or !isset($inventory_item_list[0])) {
            $return['message'] = '参数错误，未匹配到入库明细记录';
            return $return;
        }

        // 获取采购单剩余可冲销金额
        $po_surplus_list = $this->Charge_against_surplus_model->get_surplus(['0' => $purchase_number]);
        if (empty($po_surplus_list) or !isset($po_surplus_list[0])) {
            $return['message'] = '参数错误，未匹配到采购单冲销结余记录';
            return $return;
        }

        // 采购单存在待审核的冲销记录
        $po_record = $this->get_ca_audit_status($purchase_number,2,[CHARGE_AGAINST_STATUE_WAITING_AUDIT,CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT]);
        if($po_record){
            $return['message'] = '采购单存在待审核的冲销记录';
            return $return;
        }

        // 入库明细只冲销商品金额
        $surplus_charge_against_amount = $inventory_item_list[0]['surplus_charge_against_amount'];    // 入库单剩余可冲销金额
        $surplus_able_charge_against_money = $po_surplus_list[0]['surplus_able_charge_against_money'];// 采购单剩余可冲销商品金额

        if ($surplus_charge_against_amount <= 0) {
            $return['message'] = '入库单剩余可冲销金额=0';
            return $return;
        }
        if ($surplus_able_charge_against_money <= 0) {
            $return['message'] = '采购单剩余可冲销商品金额=0';
            return $return;
        }
        if ($inventory_item_list[0]['charge_against_status'] == 0 or $po_surplus_list[0]['charge_against_status'] == 0) {
            $return['message'] = '采购单付款状态设置了无需付款，无需自动冲销';
            return $return;
        }

        // 本次冲销商品额（最大可冲销商品金额）
        $charge_against_product = min($surplus_charge_against_amount, $surplus_able_charge_against_money);
        $charge_against_amount = $charge_against_product;

        $result = $this->createInventoryItemChargeAgainstRecords($purchase_number, $instock_batch, $charge_against_product, $charge_against_product);
        if ($result) {
            $return['code'] = true;
            $return['message'] = "冲销成功($charge_against_amount)";
        } else {
            $return['message'] = '冲销保存失败';
        }

        return $return;
    }

    /**
     * 生成入库明细记录的冲销记录
     * @param string $purchase_number         采购单号
     * @param string $instock_batch           入库批次号
     * @param string $charge_against_product  冲销商品额
     * @param string $charge_against_amount   冲销金额
     * @return bool
     */
    public function createInventoryItemChargeAgainstRecords($purchase_number,$instock_batch,$charge_against_product,$charge_against_amount){
        $insert_data = [
            'purchase_number'             => $purchase_number,
            'record_number'               => $instock_batch,
            'record_type'                 => 1,// 冲销类型（1.入库与采购单冲销）
            'charge_against_status'       => CHARGE_AGAINST_STATUE_WAITING_PASS,// 入库单冲销自动通过
            'charge_against_source'       => 2,// 冲销来源（2.用户手动冲销）
            'charge_against_amount'       => $charge_against_amount,
            'charge_against_product'      => $charge_against_product,
            'charge_against_freight'      => 0,
            'charge_against_discount'     => 0,
            'charge_against_process_cost' => 0,
            'create_user_name'            => getActiveUserName(),
            'create_time'                 => date('Y-m-d H:i:s'),
            'create_notice'               => '',
        ];

        $result = $this->purchase_db->insert($this->table_name,$insert_data);
        if($result){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 【冲销退款】获取 退款冲销数据
     * @param $cancel_number
     * @param $purchase_number
     * @return array
     */
    public function get_cancel_charge_against_data($id)
    {
        try {
            $row = $this->purchase_db->select('record_number_relate,record_number,purchase_number,charge_against_product,charge_against_process_cost,create_notice')
                ->where('id',$id)
                ->get($this->table_name)->row_array();
            if (empty($row)) {
                throw new Exception('未找到对应的冲销记录，请核实');
            }

            $purchase_number = $row['purchase_number'];
            $purchase_number_relate = $row['record_number_relate'];
            $cancel_number = $row['record_number'];

            //未完结（1-未完结）并且取消未到货状态为‘财务驳回’或‘待上传截图’
            $this->load->model('statement/Charge_against_surplus_model');
            $cancel_info = $this->Charge_against_surplus_model->get_unfinish_cancel_info([$purchase_number_relate]);

            if (!isset($cancel_info[$purchase_number_relate]['audit_status'])
                OR !in_array($cancel_info[$purchase_number_relate]['audit_status'], [CANCEL_AUDIT_STATUS_CFBH, CANCEL_AUDIT_STATUS_SCJT])) {
                throw new Exception('没有可冲销的退款单，取消未到货状态必须是待上传截图/财务驳回');
            }
            //获取剩余需退款金额
            $amount_data = $this->_get_surplus_refund_amount($cancel_info[$purchase_number_relate]['cancel_id'], $purchase_number_relate);
            if (!$amount_data['status']) {
                throw new Exception('未获取到剩余需退款金额');
            }
            //可申请商品额
            $able_ca_amount_record = $this->Charge_against_surplus_model->get_able_ca_amount($purchase_number);
            if (empty($able_ca_amount_record)) {
                throw new Exception('未获取到可申请商品额，请重新确认');
            }

            $data = [
                'cancel_data' => [
                    'purchase_number' => $purchase_number_relate,//采购单号
                    'charge_against_type' => '冲销退款',         //冲销类型，对应 record_type
                    'cancel_number' => $cancel_number,           //关联的取消编码
                    'refund_amount' => $amount_data['amount'],             //剩余需退款金额
                    'apply_remark' => $row['create_notice']      //申请备注
                ],
                'receive_data' => [
                    'purchase_number' => $purchase_number,                                                 //接收抵冲的po
                    'surplus_able_ca_money' => $able_ca_amount_record['surplus_able_pay_product_money'],//可申请商品额
                    'charge_against_product' => $row['charge_against_product'],                            //抵冲商品额
                    'charge_against_process_cost' => $row['charge_against_process_cost']                   //抵冲加工费
                ]
            ];
            $return = ['flag' => true, 'data' => $data, 'msg' => ''];

        } catch (Exception $e) {
            $return = ['flag' => false, 'data' => [], 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 冲销退款申请单预览数据
     * @param $purchase_number
     * @return array
     */
    public function refund_charge_against_view($purchase_number)
    {
        try {
            //获取冲销汇总订单信息（采购单状态，采购单付款状态，完结状态）
            $ca_order_info = $this->_get_ca_order_info($purchase_number);
            if (empty($ca_order_info['purchase_number'])) {
                throw new Exception('采购单号[' . $purchase_number . ']不存在');
            }
            //存在有效对账单未请款的，不允许操作【冲销退款】
            $statement_data = $this->_get_statement_status($purchase_number);
            if (!empty($statement_data)) {
                foreach ($statement_data as $val) {
                    //对账单状态=正常，并且对账单付款状态≠已付款，不允许冲销退款操作
                    if (2 != $val['status_valid'] && PAY_PAID != $val['statement_pay_status']) {
                        throw new Exception('对账单请款结束后才允许操作');
                    }
                }
            }
            //退款冲销是否正在进行中（10.待采购经理审核,20.待财务经理审核）
            $charge_against_record = $this->get_ca_audit_status($purchase_number,2,[CHARGE_AGAINST_STATUE_WAITING_AUDIT, CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT],'record_number_relate');
            if (!empty($charge_against_record)) {
                throw new Exception('采购单号[' . $purchase_number . ']已经处于退款冲销中，请审核结束后再操作');
            }
            //采购单状态是否有效（作废订单待退款，为有效）
            if (!in_array($ca_order_info['purchase_order_status'], [PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND])) {
                throw new Exception('只有“作废订单待退款”状态才允许申请');
            }
            //付款状态未在请款中（只有未请款或者未在请款中，才允许进行申请）
            if (!in_array($ca_order_info['pay_status'], [PAY_UNPAID_STATUS,PAY_SOA_REJECT,PAY_MANAGER_REJECT, PAY_WAITING_MANAGER_REJECT,
                PAY_FINANCE_REJECT, PART_PAID, PAY_PAID, PAY_REJECT_SUPERVISOR, PAY_REJECT_MANAGER, PAY_REJECT_SUPPLY, PAY_GENERAL_MANAGER_REJECT])) {
                throw new Exception('只有未请款或者未在请款中，才允许申请');
            }
            //未完结（1-未完结）并且取消未到货状态为‘财务驳回’或‘待上传截图’
            $this->load->model('statement/Charge_against_surplus_model');
            $cancel_info = $this->Charge_against_surplus_model->get_unfinish_cancel_info([$ca_order_info['purchase_number']]);
            if (1 != $ca_order_info['finished']) {
                throw new Exception('未完结状态下，才允许冲销退款操作');
            } elseif (!isset($cancel_info[$purchase_number]['audit_status'])
                OR !in_array($cancel_info[$purchase_number]['audit_status'], [CANCEL_AUDIT_STATUS_CFBH, CANCEL_AUDIT_STATUS_SCJT])) {
                throw new Exception('没有可冲销的退款单，取消未到货状态必须是待上传截图/财务驳回');
            }
            //获取剩余需退款金额
            $amount_data = $this->_get_surplus_refund_amount($cancel_info[$purchase_number]['cancel_id'], $purchase_number);
            if (!$amount_data['status']) {
                throw new Exception('未获取到剩余需退款金额');
            }

            return [
                'status' => 1,
                'data' => [
                    'cancel_number' => $cancel_info[$purchase_number]['cancel_number'],
                    'surplus_refund_amount' => $amount_data['amount'],
                    'charge_against_type' => '冲销退款',// 对应 record_type
                ],
                'msg' => ''
            ];
        } catch (Exception $e) {
            return ['status' => 0, 'data' => [], 'msg' => $e->getMessage()];
        }
    }

    /**
     * 【冲销退款】采购单 与 取消未到货退款 进行冲销 - 保存
     * @param string $purchase_number_relate 关联的采购单号
     * @param string $cancel_number 取消未到货编码
     * @param string $purchase_number 接收抵冲的采购单号
     * @param array $apply_data 申请提交的数据
     * @return array
     */
    public function refund_charge_against_save($purchase_number_relate, $cancel_number, $purchase_number, $apply_data)
    {
        $query=$this->purchase_db;
        $query->trans_begin();
        try {
            //验证接收抵冲的po是否为合同单
            $source_data = $this->_get_purchase_source($purchase_number);
            if( SOURCE_COMPACT_ORDER != $source_data['source']){
                throw new Exception('接收抵冲的采购单必须为合同单');
            }
            //比较关联采购单号和接收抵冲采购单的供应商是相同
            $compare_res = $this->_compare_supplier_by_po($purchase_number,$purchase_number_relate);
            if(!$compare_res){
                throw new Exception('接收抵冲的采购单[' . $purchase_number . ']和采购单['.$purchase_number_relate.']必须为同一个供应商');
            }
            //验证数据
            $submit_data=[
                'purchase_number_relate'=>$purchase_number_relate,
                'cancel_number'=>$cancel_number,
                'purchase_number'=>$purchase_number,
                'charge_against_product'=>$apply_data['charge_against_product'],
                'charge_against_process_cost'=>$apply_data['charge_against_process_cost'],
            ];
            $check_res = $this->_check_submit_data($submit_data);
            if (!$check_res['flag']) {
                throw new Exception($check_res['msg']);
            }
            // 验证通过：创建 退款冲销记录，记录状态：待采购经理审核
            $insert_data = [
                'purchase_number' => $purchase_number,                                                   //接收抵冲的po
                'charge_against_number' => $this->_generate_charge_against_number(),                     //冲销编码
                'record_number_relate' => $purchase_number_relate,                                       //关联的采购单号
                'record_number' => $cancel_number,                                                       //取消未到货申请编号
                'record_type' => 2,                                                                      //冲销操作单据类型(2.退款冲销)
                'charge_against_status' => 10,                                                           //冲销状态(10.待采购经理审核)
                'charge_against_amount' => bcadd($apply_data['charge_against_product'], $apply_data['charge_against_process_cost'], 3),//冲销金额（实际冲销金额）
                'charge_against_product' => $apply_data['charge_against_product'],                                     //冲销商品额
                'charge_against_process_cost' => $apply_data['charge_against_process_cost'],                           //冲销加工费
                'charge_against_source' => 2,                                                            //冲销来源(2.用户手动冲销)
                'create_user_name' => getActiveUserName(),
                'create_time' => date('Y-m-d H:i:s'),
                'create_notice' => $apply_data['create_notice'],// 申请备注
                'create_user_id' => getActiveUserId(),          //创建人user_id
            ];
            $insert_status = $query->insert($this->table_name, $insert_data);
            $charge_against_id = $query->insert_id();
            if (!$insert_status OR !$charge_against_id) {
                throw new Exception('冲销记录保存失败');
            }

            //保存操作日志
            $this->load->model('Purchase_statement_note_model', 'note_model');
            $insert_log_res = $this->note_model->add_remark($charge_against_id, 3, $apply_data['create_notice'], '退款冲销');
            if(!$insert_log_res['flag']){
                throw new Exception('保存日志失败');
            }

            $return = ['status' => 1, 'msg' => '冲销记录保存成功'];
            $query->trans_commit();
        } catch (Exception $e) {
            $query->trans_rollback();
            $return = ['status' => 0, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 检查冲销退款提交，审核通过提交的数据
     * @param array $data
     *
     * @return array 验证通过时返回剩余需退款金额（surplus_refund_amount）
     */
    private function _check_submit_data(array $data){
        try{
            // 状态验证
            $result = $this->refund_charge_against_view($data['purchase_number_relate']);
            if (empty($result['status'])) {// 先调取 显示的验证
                throw new Exception($result['msg']);
            }
            if ($result['data']['cancel_number'] != $data['cancel_number']) {
                throw new Exception('关联的取消编码已发生变化，请重新确认');
            }
            //获取采购单剩余可冲销商品金额（可申请商品额）
            $this->load->model('statement/Charge_against_surplus_model');
            $res = $this->Charge_against_surplus_model->get_able_ca_amount($data['purchase_number']);
            if (empty($res)) {
                throw new Exception('未获取到可申请商品额');
            }

            $refund_amount = (float)$result['data']['surplus_refund_amount'];                                   //剩余需退款金额
            $able_ca_amount = (float)$res['surplus_able_pay_product_money'];                                 //可申请商品额
            $charge_against_product = (float)$data['charge_against_product'];                                   //冲销商品额
            $charge_against_process_cost = (float)$data['charge_against_process_cost'];                         //冲销加工费
            $charge_against_amount = (float)bcadd($charge_against_product, $charge_against_process_cost, 3);    //冲销金额（实际冲销金额）

            //抵冲商品额：0≤抵冲商品额≤可申请商品额，否则不允许填写
            if ($charge_against_product < 0 OR $charge_against_product > $able_ca_amount) {
                throw new Exception('抵冲商品额不能大于可申请商品额');
            }
            //抵冲加工费： 0≤抵冲加工费≤剩余需退款金额，否则不允许填写
            if ($charge_against_process_cost < 0 OR $charge_against_process_cost > $refund_amount) {
                throw new Exception('抵冲加工费不能大于剩余需退款金额');
            }
            // 金额验证：提交时需要校验：0＜抵冲商品额+抵冲加工费≤剩余需退款金额。否则报错提示：不满足“0＜抵冲商品额+抵冲加工费≤剩余需退款金额”，请重新确认
            if ($charge_against_amount <= 0 OR $charge_against_amount > $refund_amount) {
                throw new Exception('不满足“0＜抵冲商品额+抵冲加工费≤剩余需退款金额”，请重新确认');
            }
            $return = ['flag'=>true,'surplus_refund_amount'=>$refund_amount,'msg'=>''];
        }catch (Exception $e){
            $return = ['flag'=>false,'msg'=>$e->getMessage()];
        }
        return $return;
    }

    /**
     * 获取对账单状态和付款状态
     * @param $purchase_number
     * @return array
     */
    private function _get_statement_status($purchase_number)
    {
        return $this->purchase_db->select('a.status_valid,a.statement_pay_status')
            ->from("{$this->table_purchase_statement} a")
            ->join("$this->table_purchase_statement_item b", 'a.statement_number=b.statement_number')
            ->where('b.purchase_number', $purchase_number)
            ->group_by('a.statement_number')->get()->result_array();
    }

    /**
     * 获取退款冲销审核状态
     * @param string $purchase_number
     * @param int    $record_type  冲销操作单据类型（1.入库冲销,2.退款冲销）
     * @param array  $charge_against_status
     * @param string $field 查询条件字段（冲销操作单据关联的采购单号record_number_relate|采购单号purchase_number）
     * @return array
     */
    public function get_ca_audit_status($purchase_number, $record_type = 2, $charge_against_status = [], $field = 'purchase_number')
    {
        $this->purchase_db->select('charge_against_status,charge_against_number')
            ->from($this->table_name)
            ->where($field, $purchase_number)
            ->where('record_type', $record_type);
        if($charge_against_status){
            $this->purchase_db->where_in('charge_against_status',$charge_against_status);
        }

        return $this->purchase_db->order_by('id', 'DESC')
            ->get()->row_array();
    }

    /**
     * 获取退款冲销 冲销金额
     * @param string $purchase_number
     * @param int    $record_type  冲销操作单据类型（1.入库冲销,2.退款冲销）
     * @param array  $charge_against_status
     * @return array
     */
    public function get_ca_total_price_list($purchase_number,$record_type,$charge_against_status = [])
    {
        $this->purchase_db
            ->select(
                'sum(charge_against_product) as charge_against_product,'
                .'sum(charge_against_freight) as charge_against_freight,'
                .'sum(charge_against_discount) as charge_against_discount,'
                .'sum(charge_against_process_cost) as charge_against_process_cost,'
                .'sum(charge_against_amount) as charge_against_amount'
            )
            ->from($this->table_name)
            ->where('purchase_number', $purchase_number)
            ->where('record_type', $record_type);
        if($charge_against_status){
            $this->purchase_db->where_in('charge_against_status',$charge_against_status);
        }

        $price_list = $this->purchase_db->get()->row_array();

        $price_list = [
            'charge_against_product' => isset($price_list['charge_against_product'])?$price_list['charge_against_product']:0,
            'charge_against_freight' => isset($price_list['charge_against_freight'])?$price_list['charge_against_freight']:0,
            'charge_against_discount' => isset($price_list['charge_against_discount'])?$price_list['charge_against_discount']:0,
            'charge_against_process_cost' => isset($price_list['charge_against_process_cost'])?$price_list['charge_against_process_cost']:0,
            'charge_against_amount' => isset($price_list['charge_against_amount'])?$price_list['charge_against_amount']:0,
        ];

        return $price_list;
    }

    /**
     * 根据取消id和采购单号，获取剩余需退款金额
     * @param $cancel_id
     * @param $purchase_number
     * @return array
     */
    private function _get_surplus_refund_amount($cancel_id, $purchase_number)
    {
        if (empty($cancel_id) or empty($purchase_number)) return ['status' => false];

        $receipt = $this->purchase_db
            ->select('pay_price')
            ->where('cancel_id', $cancel_id)
            ->where('purchase_number', $purchase_number)
            ->get('purchase_order_cancel_to_receipt')
            ->result_array();

        if (empty($receipt)) {
            return ['status' => false];
        }
        return ['status' => true, 'amount' => array_sum(array_column($receipt, 'pay_price'))];
    }

    /**
     * 生成冲销编码(CX年月日+5位数序号)
     * @return mixed
     */
    private function _generate_charge_against_number()
    {
        $sql = "SELECT CONCAT('CX',DATE_FORMAT(NOW(),'%Y%m%d'),
        LPAD((CONVERT (SUBSTRING(IFNULL((SELECT charge_against_number FROM pur_purchase_order_charge_against_records ORDER BY id DESC LIMIT 1),0),-5),SIGNED)+1),5,'0')
        ) AS charge_against_number";
        $res = $this->purchase_db->query($sql)->row_array();
        return $res['charge_against_number'];
    }

    /**
     * 退款冲销审核
     * @param $params
     * @return array
     */
    public function charge_against_audit($params){
        $query = $this->purchase_db;
        $query->trans_begin();
        try{
            //修改审核状态
            $where = ['id' => $params['id']];
            $set_data = [
                'charge_against_status' => $params['charge_against_status'],//审核状态
                'audit_remark' => $params['audit_remark'],                  //审核备注
                'audit_user_name' => getActiveUserName(),                   //审核人
                'audit_time' => date('Y-m-d H:i:s'),                //审核时间
            ];
            $res1 = $query->update($this->table_name,$set_data,$where);
            if(!$res1){
                throw new Exception('修改审核状态失败');
            }
            //财务经理审核通过
            if($params['finance_agree']){
                //验证数据
                $submit_data=[
                    'purchase_number_relate'=>$params['record_number_relate'],
                    'cancel_number'=>$params['cancel_number'],
                    'purchase_number'=>$params['purchase_number'],
                    'charge_against_product'=>$params['charge_against_product'],
                    'charge_against_process_cost'=>$params['charge_against_process_cost'],
                ];
                $check_res = $this->_check_submit_data($submit_data);
                if (!$check_res['flag']) {
                    throw new Exception($check_res['msg']);
                }

                //扣减剩余需退款金额（取消未到货-上传截图后流向应收款表）
                $order_cancel =$query->select('b.cancel_id,b.id,b.pay_price')
                    ->from("{$this->table_purchase_order_cancel} a")
                    ->join("{$this->table_cancel_to_receipt} b",'a.id=b.cancel_id')
                    ->where(['a.cancel_number'=>$params['cancel_number'],'b.purchase_number'=>$params['record_number_relate']])
                    ->get()->row_array();
                if(empty($order_cancel['id'])){
                    throw new Exception('未获取到取消未到货应收款数据');
                }
                //已抵扣金额=抵扣商品额+抵扣加工费
                //最新的剩余需退款金额=剩余需退款金额-已抵扣金额
                $surplus_refund_amount = bcsub($order_cancel['pay_price'],bcadd($params['charge_against_product'],$params['charge_against_process_cost'],3),3);
                $query->set('pay_price',$surplus_refund_amount);
                $query->where('id',$order_cancel['id']);
                $update_result = $query->update($this->table_cancel_to_receipt);
                if(!$update_result){
                    throw new Exception('更新剩余需退款金额失败');
                }
                //剩余需退款金额等于0时，更新取消未到货状态=已抵冲，修改采购订单状态

                // 该取消未到货批次中 是否存在其他 审核中的退款记录
                $total_pay_price =$query->select('sum(b.pay_price) as pay_price')
                    ->from("{$this->table_purchase_order_cancel} a")
                    ->join("{$this->table_cancel_to_receipt} b",'a.id=b.cancel_id')
                    ->where("a.cancel_number",$params['cancel_number'])
                    ->get()->row_array();

                if ($total_pay_price and $total_pay_price['pay_price'] <= 0) {// 判断是否还有需退款金额
                    //修改取消未到货状态
                    $this->load->model('purchase_order_model', '', false, 'purchase');
                    $this->load->model('Purchase_order_unarrived_model', '', false, 'purchase');
                    $this->Purchase_order_unarrived_model->change_order_cancel(CANCEL_AUDIT_STATUS_YDC, $order_cancel['cancel_id']);

                    $purchase_list =$query->select('b.purchase_number')
                        ->from("{$this->table_purchase_order_cancel} a")
                        ->join("purchase_order_cancel_detail AS b",'a.id=b.cancel_id','left')
                        ->where("a.cancel_number",$params['cancel_number'])
                        ->group_by('b.purchase_number')
                        ->get()->result_array();
                    foreach($purchase_list as $purchase_value){
                        //修改采购订单状态
                        $order_update = $this->purchase_order_model->change_status($purchase_value['purchase_number']);
                        if (empty($order_update)) {
                            throw new Exception('采购单更新失败');
                        }
                    }
                }
                //按照采购金额占比将抵扣商品额、加工费分摊到已付金额和加工费中
                $update_order_item_result = $this->_update_order_item_data($params['purchase_number'],$params['charge_against_product'],$params['charge_against_process_cost']);
                if($update_order_item_result['code'] === false){
                    throw new Exception($update_order_item_result['message']);
                }
            }
            //保存审核日志
            $this->load->model('Purchase_statement_note_model', 'note_model');
            $insert_log_res = $this->note_model->add_remark($params['id'], 3, $params['audit_remark'], $params['operation_type']);
            if(!$insert_log_res['flag']){
                throw new Exception('保存日志失败');
            }
            if ($query->trans_status() === FALSE) {
                throw new Exception('添加失败');
            }
            $query->trans_commit();
            $result = ['msg' => '', 'flag' => true];
        }catch(Exception $e){
            $query->trans_rollback();
            $result = ['msg' => $e->getMessage(), 'flag' => false];
        }
        return $result;
    }

    /**
     * 更新退款冲销抵扣商品额和退款冲销抵扣加工费
     * @param string $purchase_number 采购单号
     * @param float $ca_product_money 退款冲销抵扣商品额
     * @param float $ca_process_cost 退款冲销抵扣加工费
     * @return array
     */
    private function _update_order_item_data($purchase_number,$ca_product_money,$ca_process_cost){
        $this->load->helper('status_finance');
        //根据获取采购单获取剩余可申请商品额
        $this->load->model('compact/Compact_model', 'Compact_model');
        $purchase_sku_data = $this->Compact_model->get_compact_pay_data_details($purchase_number);
        if(empty($purchase_sku_data)){
            return $this->res_data(false,'采购单SKU明细为空');
        }
        $distribute_sku_data=[];
        foreach ($purchase_sku_data as $item){
            if(empty($item['item_available_product_money']) or $item['item_available_product_money'] <= 0){
                continue;
            }
            $distribute_sku_data[$item['purchase_number']][$item['sku']] = $item['item_available_product_money'];
        }

        if($distribute_sku_data){
            return $this->res_data(false,'采购单可分摊金额为空[所有SKU已付款完结]');
        }
        //按照采购金额占比将抵扣商品额、加工费分摊到已付金额和加工费中
        $distribute_result_product_money = amountAverageDistribute($ca_product_money,$distribute_sku_data);
        $distribute_result_process_cost = amountAverageDistribute($ca_process_cost,$distribute_sku_data);

        //更新退款冲销抵扣商品额
        if(!empty($distribute_result_product_money)){
            foreach ($distribute_result_product_money as $po => $value1) {
                foreach ($value1 as $sku => $value2) {
                    $item_result1 = $this->purchase_db->update($this->table_purchase_order_items, ['ca_product_money' => $value2],['purchase_number' => $po,'sku' => $sku],1);
                    if(empty($item_result1)){
                        return $this->res_data(false,'采购单SKU明细更新分摊抵扣商品金额失败');
                    }
                }
            }
        }else{
            return $this->res_data(false,'采购单SKU明细分摊抵扣商品金额数据缺失');
        }

        //更新退款冲销抵扣加工费
        if(!empty($distribute_result_process_cost)){
            foreach ($distribute_result_process_cost as $po => $value1) {
                foreach ($value1 as $sku => $value2) {
                    $item_result1 = $this->purchase_db->update($this->table_purchase_order_items, ['ca_process_cost' => $value2],['purchase_number' => $po,'sku' => $sku],1);
                    if(empty($item_result1)){
                        return $this->res_data(false,'采购单SKU明细更新分摊抵扣加工费失败');
                    }
                }
            }
        }else{
            return $this->res_data(false,'采购单SKU明细分摊抵扣加工费数据缺失');
        }

        //采购单状态变更需要刷新冲销汇总
        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();//创建消息队列对象
        $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
        $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');//构造存入数据
        $mq->setRouteKey('SO_REFRESH_FOR_003');
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        $mq->sendMessage(['purchase_number' => $purchase_number,'add_time' => time()]);// 保持格式一致
        return $this->res_data(true,'操作成功');
    }

    /**
     * 验证接收抵冲的po，并返回可申请商品额
     * @param string $purchase_number 接收抵冲采的购单
     * @param string $purchase_number_relate 关联的采购单号
     * @return array
     */
    public function get_able_ca_amount_check($purchase_number,$purchase_number_relate)
    {
        try {
            //验证接收抵冲的po是否为合同单
            $source_data = $this->_get_purchase_source($purchase_number);
            if( SOURCE_COMPACT_ORDER != $source_data['source']){
                throw new Exception('接收抵冲的采购单必须为合同单');
            }
            //比较关联采购单号和接收抵冲采购单的供应商是相同
            $compare_res = $this->_compare_supplier_by_po($purchase_number,$purchase_number_relate);
            if(!$compare_res){
                throw new Exception('接收抵冲的采购单[' . $purchase_number . ']和采购单['.$purchase_number_relate.']必须为同一个供应商');
            }
            //退款冲销是否正在进行中（10.待采购经理审核,20.待财务经理审核）
            $charge_against_record = $this->get_ca_audit_status($purchase_number, 2, [CHARGE_AGAINST_STATUE_WAITING_AUDIT, CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT], 'purchase_number');
            if (!empty($charge_against_record)) {
                throw new Exception('采购单号[' . $purchase_number . ']已经处于退款冲销中，请审核结束后再操作');
            }
            //获取冲销汇总订单信息（采购单状态，采购单付款状态，完结状态）
            $ca_order_info = $this->_get_ca_order_info($purchase_number);
            if (empty($ca_order_info['purchase_number'])) {
                throw new Exception('采购单号[' . $purchase_number . ']不存在');
            }
            //采购单状态是否有效（等待到货/部分到货等待剩余/全部到货/部分到货不等待剩余，为有效）
            if (!in_array($ca_order_info['purchase_order_status'], [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL, PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                PURCHASE_ORDER_STATUS_ALL_ARRIVED, PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE])) {
                throw new Exception('只有“等待到货/部分到货等待剩余/全部到货/部分到货不等待剩余”状态才允许申请');
            }
            //付款状态未在请款中（只有未请款或者未在请款中，才允许进行申请）
            if (!in_array($ca_order_info['pay_status'], [PAY_UNPAID_STATUS, PART_PAID,PAY_SOA_REJECT,PAY_MANAGER_REJECT, PAY_WAITING_MANAGER_REJECT,
                PAY_FINANCE_REJECT, PAY_REJECT_SUPERVISOR, PAY_REJECT_MANAGER, PAY_REJECT_SUPPLY, PAY_GENERAL_MANAGER_REJECT])) {
                throw new Exception('只有未请款或者未在请款中，才允许申请');
            }

            $this->load->model('statement/Charge_against_surplus_model');
            $able_ca_amount_record = $this->Charge_against_surplus_model->get_able_ca_amount($purchase_number);
            if (empty($able_ca_amount_record)) {
                throw new Exception('未获取到可申请商品额，请重新确认');
            }
            return ['status' => 1, 'data' => ['surplus_able_ca_money' => $able_ca_amount_record['surplus_able_pay_product_money']], 'msg' => ''];
        } catch (Exception $e) {
            return ['status' => 0, 'data' => [], 'msg' => $e->getMessage()];
        }
    }

    /**
     * 通过采购单号比较是否为同一个供应商
     * @param string $purchase_number 采购单号
     * @param string $purchase_number_relate 采购单号
     * @return bool （true-比较结果相同，false-比较结果不相同）
     */
    private function _compare_supplier_by_po($purchase_number, $purchase_number_relate)
    {
        $query = $this->purchase_db;
        $query->select('supplier_code,purchase_number');
        $query->where_in('purchase_number', [$purchase_number, $purchase_number_relate]);
        $result = $query->get($this->table_purchase_order)->result_array();
        $result = array_column($result, 'supplier_code', 'purchase_number');
        if (!empty($result[$purchase_number]) && !empty($result[$purchase_number_relate]) && ($result[$purchase_number] == $result[$purchase_number_relate])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取冲销汇总订单信息（采购单状态，采购单付款状态，完结状态）
     * @param $purchase_number
     * @return array
     */
    private function _get_ca_order_info($purchase_number)
    {
        $query = $this->purchase_db;
        $query->select('a.id,a.purchase_number,a.finished,b.purchase_order_status,b.pay_status');
        $query->from("{$this->table_charge_against_surplus} a");
        $query->join("{$this->table_purchase_order} b", 'b.purchase_number=a.purchase_number', 'LEFT');
        $query->where('a.purchase_number', $purchase_number);
        return $query->get()->row_array();
    }

    /**
     * 获取采购来源
     * @param $purchase_number
     * @return array
     */
    private function _get_purchase_source($purchase_number)
    {
        $query = $this->purchase_db;
        $query->select('source,purchase_number');
        $query->where('purchase_number', $purchase_number);
        return $query->get($this->table_purchase_order)->row_array();
    }

}