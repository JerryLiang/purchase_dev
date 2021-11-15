<?php
/**
 * 供应商余额总表-交易明细
 * User: Jolon
 * Date: 2020-09-10
 * Time: 9:41
 */

class Supplier_trading_detail_model extends Purchase_model {

    public $table_name       = 'pur_supplier_trading_detail';

    public $table_name_day   = "pur_supplier_accounts_payable_balance";
    public $table_name_month = "pur_supplier_accounts_payable_balance_month";
    public $table_name_year  = "pur_supplier_accounts_payable_balance_year";

    public $trading_type_list = [// 交易类型
        '1' => '入库商品额',
        '2' => '报损商品额',
        '3' => '其他费用',
        '4' => '付款',
        '5' => '普通退款',
        '6' => '退货退款',
        '7' => '入库退货',
        '8' => '调整',
        '9' => '线下退款',
    ];

    public function __construct(){
        parent::__construct();
    }

    /**
     * 供应商余额汇总表 - 交易明细
     * @param $params
     * @param $offsets
     * @param $limit
     * @param $page
     * @return array
     */
    public function get_trading_detail_list($params, $offsets, $limit, $page){
        if(BALANCE_SWITCH_ENGINE == 1){
            $this->load->library('mongo_db');

            if(isset($params['ids']) and $params['ids'] != ''){
                $ids_arr = array_map('intval',explode(',',$params['ids']));// 要转成和mongodb数据类型一致
                $this->mongo_db->where_in('id', $ids_arr);
            }
            if(isset($params['order_no']) and $params['order_no'] != ''){
                $this->mongo_db->where(['order_no' => $params['order_no']]);
            }
            if(isset($params['relative_trading_num']) and $params['relative_trading_num'] != ''){
                $this->mongo_db->where(['relative_trading_num' =>  $params['relative_trading_num']]);
            }
            if(isset($params['supplier_code']) and $params['supplier_code'] != ''){
                $this->mongo_db->where(['supplier_code' => $params['supplier_code']]);
            }
            if(isset($params['purchase_name']) and $params['purchase_name'] != ''){
                $this->mongo_db->where(['purchase_name' => $params['purchase_name'] != 'none'?$params['purchase_name']:'']);
            }
            if(isset($params['trading_type']) and $params['trading_type'] != ''){
                $this->mongo_db->where(['trading_type' => $params['trading_type']]);
            }
            if(isset($params['start_trading_time']) and isset($params['start_trading_time'])
                and !empty($params['end_trading_time']) and !empty($params['end_trading_time'])
            ){
                $start_time = date('Y-m-d 00:00:00', strtotime($params['start_trading_time']));
                $end_time   = date('Y-m-d 23:59:59', strtotime($params['end_trading_time']));
                $this->mongo_db->where_between("trading_time",$start_time,$end_time);
            }

            if(isset($params['statistic'])){// 页面汇总
                $count_qb = clone $this->mongo_db;
                $count_row   = $count_qb->aggregate('PurSupplierTradingDetail','sum',['total_trading_money' => 'trading_money']);
                $total_trading_money = isset($count_row['total_trading_money']) ? $count_row['total_trading_money'] : 0;
            }else{
                $count_qb = clone $this->mongo_db;
                $this->mongo_db->order_by(['trading_time' => 'asc'])->limit($limit)->offset($page);
                $results = $this->mongo_db->get('PurSupplierTradingDetail');
                if(!empty($results)){
                    foreach($results as $key => $val){
                        $val_tmp = json_decode(json_encode($val), true);
                        unset($val_tmp['_id']);
                        $results[$key]                  = $val_tmp;
                        $results[$key]['purchase_name'] = get_purchase_agent($val_tmp['purchase_name']);
                        $results[$key]['trading_type']  = isset($this->trading_type_list[$val_tmp['trading_type']]) ? $this->trading_type_list[$val_tmp['trading_type']] : '';
                    }
                }

                $count_row   = $count_qb->count('PurSupplierTradingDetail');
                $total_count = $count_row ? $count_row : 0;
            }

        }else{
            $slaveDb = $this->load->database('slave',TRUE);// 从库读取数据

            $slaveDb->from($this->table_name);
            if(isset($params['ids']) and $params['ids'] != ''){
                $ids_arr = explode(',',$params['ids']);
                $slaveDb->where_in('id', $ids_arr);
            }
            if(isset($params['order_no']) and $params['order_no'] != ''){
                $slaveDb->where('order_no', $params['order_no']);
            }
            if(isset($params['relative_trading_num']) and $params['relative_trading_num'] != ''){
                $slaveDb->where('relative_trading_num', $params['relative_trading_num']);
            }
            if(isset($params['supplier_code']) and $params['supplier_code'] != ''){
                $slaveDb->where('supplier_code', $params['supplier_code']);
            }
            if(isset($params['purchase_name']) and $params['purchase_name'] != ''){
                $slaveDb->where('purchase_name', $params['purchase_name'] != 'none'?$params['purchase_name']:'');
            }
            if(isset($params['trading_type']) and $params['trading_type'] != ''){
                $slaveDb->where('trading_type', $params['trading_type']);
            }
            if(isset($params['start_trading_time']) and isset($params['start_trading_time'])
                and !empty($params['end_trading_time']) and !empty($params['end_trading_time'])
            ){
                $start_time = date('Y-m-d 00:00:00', strtotime($params['start_trading_time']));
                $end_time   = date('Y-m-d 23:59:59', strtotime($params['end_trading_time']));
                $slaveDb->where("trading_time between '{$start_time}' and '{$end_time}' ");
            }

            if(isset($params['statistic'])){// 页面汇总
                $count_qb            = clone  $slaveDb;
                $count_row           = $count_qb->select('count(id) as num,sum(trading_money) as total_trading_money')->get()->row_array();
                $total_trading_money = isset($count_row['total_trading_money']) ? $count_row['total_trading_money'] : 0;

            }else{
                $count_qb = clone  $slaveDb;
                $slaveDb->select('*')->limit($limit, $offsets);
                $results  = $slaveDb->order_by('trading_time ASC')->get()->result_array();

                if(!empty($results)){
                    foreach($results as $key => $val){
                        $results[$key]['purchase_name'] = get_purchase_agent($val['purchase_name']);
                        $results[$key]['trading_type'] = isset($this->trading_type_list[$val['trading_type']])?$this->trading_type_list[$val['trading_type']]:'';
                    }
                }

                $count_row   = $count_qb->select('count(id) as num')->get()->row_array();
                $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
            }
        }

        if(isset($params['statistic'])){
            $return_data = [
                'statistic_list' => [
                    'total_trading_money' => $total_trading_money
                ]
            ];
            return $return_data;

        }else{
            $drop_down_list['purchase_name']     = get_purchase_agent(null,true);//公司主体
            $drop_down_list['trading_type_list'] = $this->trading_type_list;
            $return_data = [
                'key'           => ['交易ID','关联单据号','供应商代码','供应商名称','采购主体','交易时间','交易类型','交易描述','交易金额'],
                'values'        => $results,
                'drop_down_box' => $drop_down_list,
                'paging_data'   => [
                    'total'  => $total_count,
                    'offset' => $page,
                    'limit'  => $limit,
                    'pages'  => ceil($total_count / $limit),
                ]
            ];
            return $return_data;
        }
    }


    /**
     * 获取 已经生成的交易明细的 关联单据编号
     * @param $relative_trading_num_list
     * @return array
     */
    public function get_these_trading_details($relative_trading_num_list){
        $list = $this->purchase_db->select('relative_trading_num')
            ->where_in('relative_trading_num',$relative_trading_num_list)
            ->get($this->table_name)
            ->result_array();

        return !empty($list)?array_column($list,'relative_trading_num','relative_trading_num'):[];
    }

    /**
     * 插入 交易明细 记录
     * @param array   $data_list    数据源
     * @param string  $trading_type 交易类型
     * @param boolean $auto_filter  是否自动过滤，去重（true.是，false.否）
     * @return array
     * @example $data_list = array(
     *      0 => array(
     *          'relative_trading_num'  => 'PP12333',
     *          'supplier_code'         => 'QS00001',
     *          'supplier_name'         => '供应商名称',
     *          'purchase_name'         => 'SZYB',
     *          'trading_time'          => '2020-09-10 17:12:15',
     *          'trading_note'          => '1011200333811入库',
     *          'trading_origin_money'  => '12.345'
     *      ),
     *      1 => array(
     *          'relative_trading_num'  => 'PP12444',
     *          'supplier_code'         => 'QS00001',
     *          'supplier_name'         => '供应商名称',
     *          'purchase_name'         => 'SZYB',
     *          'trading_time'          => '2020-09-10 17:12:15',
     *          'trading_note'          => '1011200333811入库',
     *          'trading_origin_money'  => '12.345'
     *      )
     * )
     */
    public function add_trading_details($data_list,$trading_type,$auto_filter = true){
        $return = ['code' => false,'message' => '操作失败'];

        if(empty($data_list)){
            $return['message'] = '目标数据缺失';
            return $return;
        }

        if(!is_null($trading_type) and !isset($this->trading_type_list[$trading_type])){
            $return['message'] = '不合法的交易类型';
            return $return;
        }

        if($auto_filter === true){
            $relative_trading_num_list = array_column($data_list,'relative_trading_num');
            $get_trading_details = $this->get_these_trading_details($relative_trading_num_list);
        }

        $trading_time = date('Y-m-d H:i:s');// 同一批次
        foreach($data_list as $key => &$value){
            $relative_trading_num = $value['relative_trading_num'];

            // 跳过已经存在的数据
            if($auto_filter === true and isset($get_trading_details[$relative_trading_num])){
                unset($data_list[$key]);
                continue;
            }

            $value['order_no']      = get_prefix_new_number('JY'.date('Ymd'), 1, 7);// 生成每日从1开始计数长度为7的交易编号
            if(!isset($value['trading_money']) or $value['trading_money'] === '' or $value['trading_money'] === false){
                $value['trading_money'] = format_two_point_price($value['trading_origin_money']);
            }
            $value['create_time']   = $trading_time;
            !is_null($trading_type) and $value['trading_type']  = $trading_type;
        }

        if(empty($data_list)){
            $return['code'] = true;
            $return['message'] = '目标数据过滤后为空';
            return $return;
        }

        $res = $this->purchase_db->insert_batch($this->table_name, $data_list);
        if(empty($res)){
            $return['message'] = '数据插入失败';
            return $return;
        }else{
            $return['code'] = true;
            $return['message'] = '数据插入成功';
            return $return;
        }
    }


    /**
     * 插入 交易明细 记录 - 装饰器
     * @param array             $query_data_list    参考 add_trading_details 的参数说明
     * @param string|integer    $trading_type       参考 add_trading_details 的参数说明
     * @param boolean           $auto_filter        参考 add_trading_details 的参数说明
     * @return mixed
     */
    public function do_format_trading_details($query_data_list,$trading_type,$auto_filter = true){
        $return = ['code' => false,'message' => '操作失败'];

        $query_data_list_arr = array_chunk($query_data_list,500);// 分批次插入

        $this->purchase_db->trans_begin();
        try{
            foreach($query_data_list_arr as $data_list_arr){
                $result = $this->add_trading_details($data_list_arr,$trading_type,$auto_filter);// 2.报损商品额
                if($result['code'] === false){
                    throw new Exception($result['message']);
                }
            }

            $this->purchase_db->trans_commit();
            $return['code']    = true;
            $return['message'] = '数据插入成功';

        }catch(Exception $exception){
            $return['message'] = $exception->getMessage();
            $this->purchase_db->trans_rollback();
        }

        return $return;
    }




    //region  生成各模块的交易明细记录
    /**
     * 入库明细表 - 入库商品额
     * 采购单在入库之后，只统计付款状态非“无需付款”的数据，以入库批次号的维度生成一条交易记录
     *
     * @return array
     * @link supplier_balance_api/instock_to_trading_detail
     */
    public function instock_to_trading_detail(){
        $return = ['code' => false,'message' => '操作失败'];

        // 字段 与 交易明细表字段对应
        $query_sql = "SELECT 
            W.instock_batch AS relative_trading_num,
            IFNULL(PO.supplier_code,'') AS supplier_code,
            IFNULL(PO.supplier_name,'') AS supplier_name,
            CASE PO.is_drawback WHEN 1 THEN 'SZYB' ELSE 'HKYB' END AS purchase_name,
            W.instock_date AS trading_time,
            W.instock_qty*POI.purchase_unit_price AS trading_origin_money,
            concat(W.sku,'入库') AS trading_note,
            IFNULL(PO.pay_status,'') AS pay_status

        FROM pur_warehouse_results AS W
        LEFT JOIN pur_purchase_order AS PO ON PO.purchase_number=W.purchase_number
        LEFT JOIN pur_purchase_order_items AS POI ON POI.id=W.items_id
        WHERE W.is_cal_balance=0 AND W.instock_node=100
        ORDER BY W.id ASC
        LIMIT 1000";

//        WHERE W.instock_date >='{$start_time}' AND W.instock_date<='{$end_time}'";

        $query_data_list = $this->purchase_db->query($query_sql)->result_array();

        if(empty($query_data_list)){
            $return['message'] = "未获取到数据";
            return $return;
        }

        foreach($query_data_list as $key => &$value){
            if(stripos($value['relative_trading_num'],'XR') !== false){
                // 36468：关联单据号=XR开头的单据时（即采购样品仓入库）
                $value['trading_money'] = 0;
            }else{
                if(!empty($value['pay_status']) and $value['pay_status'] == PAY_NONEED_STATUS){
                    $value['trading_money'] = 0;// 无需付款的设置为0
                }else{
                    $value['trading_money'] = format_two_point_price($value['trading_origin_money']);
                }
            }

            unset($value['pay_status']);// 保证数据字段与表字段对应
        }

        $return = $this->do_format_trading_details($query_data_list,1);
        if($return['code']){
            $return['message'] = "本次生成数据：".count($query_data_list)." 条";

            $instock_batch_list = array_column($query_data_list,'relative_trading_num');
            $instock_batch_list = implode("','",$instock_batch_list);
            $this->purchase_db->query("UPDATE pur_warehouse_results SET is_cal_balance=1 WHERE instock_batch IN('".$instock_batch_list."')");
        }

        return $return;
    }

    /**
     * 报损信息管理 - 报损商品额
     * 报损申请为“已通过”的时候，以报损申请编码的维度生成一条交易记录
     *
     * @param string $statistic_date
     * @return array
     * @link supplier_balance_api/loss_to_trading_detail?statistic_date=2020-09-10
     */
    public function loss_to_trading_detail($statistic_date){
        $return = ['code' => false,'message' => '操作失败'];

        if(empty($statistic_date)){
            $return['message'] = '未设定日期';
            return $return;
        }

        $start_time = date('Y-m-d 00:00:00', strtotime($statistic_date));
        $end_time   = date('Y-m-d 23:59:59', strtotime($statistic_date));

        // 字段 与 交易明细表字段对应
        $query_sql = "SELECT 
            R.bs_number AS relative_trading_num,
            IFNULL(PO.supplier_code,'') AS supplier_code,
            IFNULL(PO.supplier_name,'') AS supplier_name,
            CASE PO.is_drawback WHEN 1 THEN 'SZYB' ELSE 'HKYB' END AS purchase_name,
            R.approval_time AS trading_time,
            R.loss_totalprice - R.loss_freight - R.loss_process_cost as trading_origin_money,
            concat(R.sku,'报损') AS trading_note
            
        FROM pur_purchase_order_reportloss AS R
        LEFT JOIN pur_purchase_order AS PO ON PO.purchase_number=R.pur_number
        WHERE R.status='".REPORT_LOSS_STATUS_FINANCE_PASS."' AND R.approval_time >='{$start_time}' AND R.approval_time<='{$end_time}'";

        $query_data_list = $this->purchase_db->query($query_sql)->result_array();

        if(empty($query_data_list)){
            $return['message'] = "未获取到数据：$start_time ~ $end_time";
            return $return;
        }

        $return = $this->do_format_trading_details($query_data_list,2);
        if($return['code']) $return['message'] = "本次生成数据：".count($query_data_list)." 条";

        return $return;
    }

    /**
     * 应付款管理 - 其他费用&付款
     * 申请单的状态为“已付款”的时候，以请款单号的维度生成一条交易记录
     *
     * @return array
     * @link supplier_balance_api/pay_to_trading_detail
     */
    public function pay_to_trading_detail(){
        $return = ['code' => false,'message' => '操作失败'];

        // 字段 与 交易明细表字段对应
        $query_sql = "SELECT 
            P.requisition_number AS relative_trading_num,
            IFNULL(P.supplier_code,'') AS supplier_code,
            IFNULL(S.supplier_name,'') AS supplier_name,
            P.purchase_name AS purchase_name,
            P.payer_time AS trading_time,
            P.product_money,
            P.freight,
            P.discount,
            P.process_cost,
            P.commission,
            P.pay_price,
            P.pay_category

        FROM pur_purchase_order_pay AS P
        LEFT JOIN pur_supplier AS S ON P.supplier_code=S.supplier_code
        WHERE P.is_cal_balance=0 AND P.pay_status=51 
        ORDER BY P.id ASC
        LIMIT 1000";

//        AND P.payer_time >='{$start_time}' AND P.payer_time<='{$end_time}'";

        $query_data_list = $this->purchase_db->query($query_sql)->result_array();

        if(empty($query_data_list)){
            $return['message'] = "未获取到数据";
            return $return;
        }

        // 其他费用 & 付款
        $data_list_pay_price = [];
        foreach($query_data_list as $value){
            if($value['pay_category'] == PURCHASE_PAY_CATEGORY_5){
                $value['purchase_name'] = '';// 41288 请款类型=样品请款不展示采购主体
            }

            // 生成其他费用记录
            if(abs($value['freight']) > 0 or abs($value['discount']) > 0 or abs($value['process_cost']) > 0 or abs($value['commission']) > 0){
                $data_list_pay_price[] = [
                    'relative_trading_num' => $value['relative_trading_num'],
                    'supplier_code'        => $value['supplier_code'],
                    'supplier_name'        => $value['supplier_name'],
                    'purchase_name'        => $value['purchase_name'],
                    'trading_time'         => $value['trading_time'],
                    'trading_type'         => 3,// 其他费用
                    'trading_origin_money' => format_price($value['freight'] - $value['discount'] + $value['process_cost'] + $value['commission']),
                    'trading_note'         => '请款单其他费用入账',
                ];
            }

            // 付款记录
            $data_list_pay_price[] = [
                'relative_trading_num' => $value['relative_trading_num'],
                'supplier_code'        => $value['supplier_code'],
                'supplier_name'        => $value['supplier_name'],
                'purchase_name'        => $value['purchase_name'],
                'trading_time'         => $value['trading_time'],
                'trading_type'         => 4,// 付款
                'trading_origin_money' => - $value['pay_price'],// 取负值
                'trading_note'         => '请款单付款',
            ];
        }
        unset($query_data_list);

        if(empty($data_list_pay_price)){
            $return['message'] = "未获取到数据";
            return $return;
        }

        $return = $this->do_format_trading_details($data_list_pay_price,null,false);
        if($return['code']){
            $return['message'] = "本次生成数据：".count($data_list_pay_price)." 条";

            $requisition_number_list = array_column($data_list_pay_price,'relative_trading_num');
            $requisition_number_list = implode("','",$requisition_number_list);
            $this->purchase_db->query("UPDATE pur_purchase_order_pay SET is_cal_balance=1 WHERE requisition_number IN('".$requisition_number_list."')");
        }

        return $return;
    }

    /**
     * 应收款管理-普通收款 - 普通退款
     * 当收款状态变为“已收款”的时候，以申请编号为维度生成一条交易记录
     *
     * @param string $statistic_date
     * @return array
     * @link supplier_balance_api/refund_to_trading_detail?statistic_date=2020-09-10
     */
    public function refund_to_trading_detail($statistic_date){
        $this->load->model('statement/Charge_against_records_model');

        $return = ['code' => false,'message' => '操作失败'];

        if(empty($statistic_date)){
            $return['message'] = '未设定日期';
            return $return;
        }

        $start_time = date('Y-m-d 00:00:00', strtotime($statistic_date));
        $end_time   = date('Y-m-d 23:59:59', strtotime($statistic_date));

        // 字段 与 交易明细表字段对应
        $query_sql = "SELECT 
            cancel_number AS relative_trading_num,
            supplier_code,
            supplier_name,
            purchase_name,
            trading_time,
            SUM(trading_origin_money) AS trading_origin_money,
            SUM(receipt_price) AS receipt_price,
            cancel_order_type,
            original_pay_product_money
        FROM (
            SELECT 
                C.cancel_number AS cancel_number,
                IFNULL(PO.supplier_code,'') AS supplier_code,
                IFNULL(PO.supplier_name,'') AS supplier_name,
                CASE PO.is_drawback WHEN 1 THEN 'SZYB' ELSE 'HKYB' END AS purchase_name,
                R.payer_time AS trading_time,
                D.real_refund AS trading_origin_money,
                R.pay_price AS receipt_price,
                C.cancel_order_type,
                TR.original_pay_product_money
        
            FROM pur_purchase_order_receipt AS R
            LEFT JOIN pur_purchase_order_cancel_to_receipt AS TR ON TR.cancel_id=R.cancel_id
            LEFT JOIN pur_purchase_order_cancel AS C ON C.id=R.cancel_id
            LEFT JOIN pur_purchase_order_cancel_detail AS D ON D.cancel_id=C.id AND R.purchase_number=D.purchase_number
            LEFT JOIN pur_purchase_order AS PO ON PO.purchase_number=R.purchase_number
            WHERE R.pay_status=2 AND R.payer_time >='{$start_time}' AND R.payer_time<='{$end_time}'
            GROUP BY C.cancel_number,D.purchase_number
        ) AS tmp GROUP BY cancel_number";

        $query_data_list = $this->purchase_db->query($query_sql)->result_array();

        if(empty($query_data_list)){
            $return['message'] = "该时间段内未获取到数据：$start_time ~ $end_time";
            return $return;
        }

        $cancel_number_list = array_column($query_data_list,'relative_trading_num');
        $refund_cg_list = $this->Charge_against_records_model->get_charge_against_records_gather(['record_number' => $cancel_number_list,'charge_against_status' => CHARGE_AGAINST_STATUE_WAITING_PASS],2,'record_number');
        $refund_cg_list = arrayKeyToColumn($refund_cg_list,'record_number');


        foreach($query_data_list as $key => &$value){
            $value['trading_origin_money'] = format_price($value['trading_origin_money']);


            if($value['cancel_order_type'] == 2){
                $value['purchase_name'] = '';// 29385样品退款的采购主体取为空
            }else{
                // 冲销扣减商品金额
                if(isset($refund_cg_list[$value['cancel_number']])){
                    $refund_cg_product_money = $refund_cg_list[$value['cancel_number']]['charge_against_product'];
                }else{
                    $refund_cg_product_money = 0;
                }
                // 交易金额 = 收款商品金额 = 原始退款商品金额 - 冲销扣减商品金额
                $value['receipt_price'] = $value['original_pay_product_money'] - $refund_cg_product_money;
            }

            $trading_note =  '收款金额：'.$value['receipt_price'].'，实际退款金额：'.$value['trading_origin_money'];
            if($value['trading_origin_money'] > $value['receipt_price']){
                $trading_note .= '，多退';
            }elseif($value['trading_origin_money'] < $value['receipt_price']){
                $trading_note .= '，错误';
            }else{
                $trading_note .= '，正常';
            }

            $value['trading_note'] = $trading_note;

            unset($value['receipt_price'],$value['cancel_order_type'],$value['original_pay_product_money']);// 保证数据字段与表字段对应
        }

        if(empty($query_data_list)){
            $return['message'] = "该时间段内未获取到数据：$start_time ~ $end_time";
            return $return;
        }

        $return = $this->do_format_trading_details($query_data_list,5);
        if($return['code']) $return['message'] = "本次生成数据：".count($query_data_list)." 条";

        return $return;
    }

    /**
     * 应收款管理-入库退货 - 退货退款&入库退货
     * 当收款状态变为“财务已收款”的时候，以退货单号为维度生成一条交易记录
     *
     * @param string $statistic_date
     * @return array
     * @link supplier_balance_api/instock_refund_to_trading_detail?statistic_date=2020-09-10
     */
    public function instock_refund_to_trading_detail($statistic_date){
        $return = ['code' => false,'message' => '操作失败'];

        if(empty($statistic_date)){
            $return['message'] = '未设定日期';
            return $return;
        }

        $start_time = date('Y-m-d 00:00:00', strtotime($statistic_date));
        $end_time   = date('Y-m-d 23:59:59', strtotime($statistic_date));

        // 字段 与 交易明细表字段对应
        $query_sql = "SELECT 
            C.return_number AS relative_trading_num,
            C.supplier_code AS supplier_code,
            C.supplier_name AS supplier_name,
            'HKYB' AS purchase_name,
            C.colletion_time AS trading_time,
            C.refundable_amount AS trading_origin_money

        FROM pur_return_after_storage_collection AS C
        LEFT JOIN pur_return_after_storage_part AS RP ON RP.return_number=C.return_number
        LEFT JOIN pur_return_after_storage_main AS RM ON RM.main_number=RP.main_number
        WHERE C.return_status=11 AND C.colletion_time >='{$start_time}' AND C.colletion_time<='{$end_time}'";

        $query_data_list = $this->purchase_db->query($query_sql)->result_array();

        if(empty($query_data_list)){
            $return['message'] = "该时间段内未获取到数据：$start_time ~ $end_time";
            return $return;
        }

        // 退货退款 & 入库退货
        $data_list_refund_price = [];
        foreach($query_data_list as $value){
            // 生成其他费用记录
            $value['trading_note']    = '入库退货收款';
            $value['trading_type']    = 6;
            $data_list_refund_price[] = $value;

            // 付款记录
            $value['trading_note']         = '入库退货';
            $value['trading_type']         = 7;
            $value['trading_origin_money'] = -$value['trading_origin_money'];
            $data_list_refund_price[]      = $value;
        }
        unset($query_data_list);

        if(empty($data_list_refund_price)){
            $return['message'] = "该时间段内未获取到数据：$start_time ~ $end_time";
            return $return;
        }

        $return = $this->do_format_trading_details($data_list_refund_price,null);
        if($return['code']) $return['message'] = "本次生成数据：".count($data_list_refund_price)." 条";

        return $return;
    }

    /**
     * 余额调整单 - 调整
     * 余额调整单的状态为“审核通过”之后，以余额申请单ID生成一条交易记录
     *
     * @param string $statistic_date
     * @return array
     * @link supplier_balance_api/supplier_balance_order_to_trading_detail?statistic_date=2020-09-10
     */
    public function supplier_balance_order_to_trading_detail($statistic_date){
        $return = ['code' => false,'message' => '操作失败'];

        if(empty($statistic_date)){
            $return['message'] = '未设定日期';
            return $return;
        }

        $start_time = date('Y-m-d 00:00:00', strtotime($statistic_date));
        $end_time   = date('Y-m-d 23:59:59', strtotime($statistic_date));

        // 字段 与 交易明细表字段对应
        $query_sql = "SELECT 
            W.order_no AS relative_trading_num,
            W.supplier_code AS supplier_code,
            W.supplier_name AS supplier_name,
            W.purchase_name AS purchase_name,
            W.audit_time AS trading_time,
            W.adjust_money AS trading_origin_money,
            '申请调整金额' AS trading_note

        FROM pur_supplier_balance_order AS W
        WHERE W.audit_status=2 AND W.audit_time >='{$start_time}' AND W.audit_time<='{$end_time}'";

        $query_data_list = $this->purchase_db->query($query_sql)->result_array();

        if(empty($query_data_list)){
            $return['message'] = "该时间段内未获取到数据：$start_time ~ $end_time";
            return $return;
        }

        $return = $this->do_format_trading_details($query_data_list,8);
        if($return['code']) $return['message'] = "本次生成数据：".count($query_data_list)." 条";

        return $return;
    }


    /**
     * 线下收款单 - 收款
     * 财务应收款页面,点击"确认收款"通过后,推送数据到"报表管理-供应商余额汇总表-交易明细"页面
     *
     * @param string $statistic_date
     * @return array
     * @link supplier_balance_api/offline_receipt_to_trading_detail?statistic_date=2020-09-10
     */
    public function offline_receipt_to_trading_detail($statistic_date){
        $return = ['code' => false,'message' => '操作失败'];

        if(empty($statistic_date)){
            $return['message'] = '未设定日期';
            return $return;
        }

        $start_time = date('Y-m-d 00:00:00', strtotime($statistic_date));
        $end_time   = date('Y-m-d 23:59:59', strtotime($statistic_date));

        // 字段 与 交易明细表字段对应
        // 需求32148：1688系统异常(财务专用)不计入
        // 需求36468：线下退款 显示为0
        $query_sql = "SELECT 
            W.refund_number AS relative_trading_num,
            W.supplier_code AS supplier_code,
            W.supplier_name AS supplier_name,
            W.purchase_name AS purchase_name,
            W.receipt_time AS trading_time,
            0 AS trading_money,
            W.refund_price AS trading_origin_money,
            W.refund_reason AS trading_note

        FROM pur_offline_receipt AS W
        WHERE W.refund_status=2 AND W.receipt_time >='{$start_time}' AND W.receipt_time<='{$end_time}'
        AND W.refund_reason NOT IN('1688系统异常(财务专用)')";

        $query_data_list = $this->purchase_db->query($query_sql)->result_array();

        if(empty($query_data_list)){
            $return['message'] = "该时间段内未获取到数据：$start_time ~ $end_time";
            return $return;
        }

        $return = $this->do_format_trading_details($query_data_list,9);
        if($return['code']) $return['message'] = "本次生成数据：".count($query_data_list)." 条";

        return $return;
    }
    //endregion

}