<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-04-25
 * Time: 9:23
 */

class Supplier_balance_model extends Purchase_model {

    public $table_name       = 'pur_supplier_trading_detail';

    public $table_name_day   = "pur_supplier_accounts_payable_balance";
    public $table_name_month = "pur_supplier_accounts_payable_balance_month";
    public $table_name_year  = "pur_supplier_accounts_payable_balance_year";

    public $now_time;

    public $today;
    public $month;
    public $year;

    public $yesterday;
    public $last_month;
    public $last_year;

    public function __construct(){
        parent::__construct();
        $this->load->model('supplier/supplier_payment_info_model');

        $this->now_time  = date('Y-m-d H:i:s');

        $this->today     = date('Y-m-d');
        $this->month     = date('Y-m');
        $this->year      = date('Y');

        $this->yesterday  = date('Y-m-d', strtotime(" -1 days"));
        $this->last_month = date('Y-m',strtotime(date('Y-m-01')) - 86400 * 2);
        $this->last_year  = strval(intval(date('Y')) - 1);
    }

    /**
     * 允许排序的字段
     * @return array
     */
    public function getOrderBy(){
        return [
            'c_opening_balance',
            'c_instock_money',
            'c_other_money',
            'c_paid_money',
            'c_refunded_money',
            'c_loss_money',
            'c_adjust_money',
            'c_ending_balance'
        ];
    }

    /**
     * 允许的排序方式
     * @return array
     */
    public function getOrderByType(){
        return [
            'asc',
            'desc',
        ];
    }

    /**
     * 期末余额 选择条件
     * @return array
     */
    public function getEndingBalanceQuery(){
        return [
            '>' => '>0',
            '<' => '<0',
            '=' => '=0'
        ];
    }

    /**
     * 判断是不是闰年
     * @param $year
     * @return false  true.是闰年，false.不是闰年
     */
    public function isLeapYear($year){
        return date('L', $year);
    }

    /**
     * 获取 指定年份的 某月的最后一天的天数
     * @param $year
     * @param $month
     * @return int
     */
    public function getLastDay($year,$month) {
        if($month == 2){
            $lastday = $this->isLeapYear($year) ? 29 : 28;
        }elseif(in_array($month,[4,6,9,11])){
            $lastday = 30;
        }else{
            $lastday = 31;
        }

        return $lastday;
    }

    /**
     * 列表头
     * @return array
     */
    public function get_head_list(){
        $head = ['供应商代码','供应商', '采购主体','国内/FBA结算方式','海外仓结算方式', '生成时间','统计时间', '期初余额','入库应付','其他应付','本期已付','退款金额','报损金额','调整金额','期末余额'];
        return $head;
    }

    /**
     * 日报表
     * @param $params
     * @param $offsets
     * @param $limit
     * @param $page
     * @return array
     */
    public function day_supplier_balance_list($params, $offsets, $limit, $page){
        if(BALANCE_SWITCH_ENGINE == 1){
            $this->load->library('mongo_db');

            if(isset($params['ids']) and $params['ids'] != ''){
                $ids_arr = array_map('intval',explode(',',$params['ids']));// 要转成和mongodb数据类型一致
                $this->mongo_db->where_in('id', $ids_arr);
            }
            if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
                $this->mongo_db->where(['supplier_code' => $params['supplier_code']]);
            }
            if(isset($params['purchase_name']) and !empty($params['purchase_name'])){
                $this->mongo_db->where(['purchase_name' => $params['purchase_name'] != 'none'?$params['purchase_name']:'']);
            }
            if(isset($params['start_date']) and isset($params['end_date'])
                and !empty($params['end_date']) and !empty($params['start_date'])
            ){// 统计年份
                $start_time = date('Y-m-d', strtotime($params['start_date']));
                $end_time   = date('Y-m-d', strtotime($params['end_date']));
                $this->mongo_db->where_between("statistic_time",$start_time,$end_time);
            }

            if(isset($params['statistic'])){// 页面汇总
                $count_qb = clone $this->mongo_db;
                $count_row = $count_qb->aggregate(
                    'PurSupplierAccountsPayableBalance',
                    'sum',
                    [
                        'c_instock_money'     => 'c_instock_money',
                        'c_other_money'       => 'c_other_money',
                        'c_paid_money'        => 'c_paid_money',
                        'total_trading_money' => 'trading_money',
                        'c_refunded_money'    => 'c_refunded_money',
                        'c_loss_money'        => 'c_loss_money',
                        'c_adjust_money'      => 'c_adjust_money',
                        'c_ending_balance'    => 'c_ending_balance',
                    ]
                );

            }else{
                $count_qb = clone $this->mongo_db;
                $this->mongo_db->order_by(['statistic_time' => 'asc'])->limit($limit)->offset($page);
                $results = $this->mongo_db->get('PurSupplierAccountsPayableBalance');
                if(!empty($results)){
                    foreach($results as $key => $val){
                        $val_tmp = json_decode(json_encode($val), true);
                        unset($val_tmp['_id']);
                        $results[$key]                  = $val_tmp;
                        $results[$key]['purchase_name'] = get_purchase_agent($val_tmp['purchase_name']);
                    }
                }

                $count_row   = $count_qb->count('PurSupplierAccountsPayableBalance');
                $total_count = $count_row ? $count_row : 0;
            }

        }else{
            $slaveDb = $this->load->database('slave',TRUE);// 从库读取数据

            $slaveDb->from($this->table_name_day);
            if(isset($params['ids']) and $params['ids'] != ''){
                $ids_arr = explode(',',$params['ids']);
                $slaveDb->where_in('id', $ids_arr);
            }
            if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
                $slaveDb->where('supplier_code', $params['supplier_code']);
            }
            if(isset($params['purchase_name']) and !empty($params['purchase_name'])){
                $slaveDb->where('purchase_name', $params['purchase_name'] != 'none'?$params['purchase_name']:'');
            }
            if(isset($params['start_date']) and isset($params['end_date'])
                and !empty($params['end_date']) and !empty($params['start_date'])
            ){// 统计年份
                $start_time = date('Y-m-d', strtotime($params['start_date']));
                $end_time   = date('Y-m-d', strtotime($params['end_date']));
                $slaveDb->where("statistic_time between '{$start_time}' and '{$end_time}' ");
            }

            if(isset($params['statistic'])){// 页面汇总
                $count_qb  = clone $slaveDb;
                $count_row = $count_qb->select(
                    'count(id) as num,'
                    .'sum(c_instock_money) as c_instock_money,'
                    .'sum(c_other_money) as c_other_money,'
                    .'sum(c_paid_money) as c_paid_money,'
                    .'sum(c_refunded_money) as c_refunded_money,'
                    .'sum(c_loss_money) as c_loss_money,'
                    .'sum(c_adjust_money) as c_adjust_money,'
                    .'sum(c_ending_balance) as c_ending_balance'
                )->get()->row_array();

            }else{
                $count_qb = clone $slaveDb;
                $slaveDb->select('*')->limit($limit, $offsets);
                $results = $slaveDb->order_by('statistic_time ASC')->get()->result_array();

                if(!empty($results)){
                    foreach($results as $key => $val){
                        $results[$key]['purchase_name'] = get_purchase_agent($val['purchase_name']);
                    }
                }

                $count_row   = $count_qb->select('count(id) as num')->get()->row_array();
                $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
            }
        }

        // 返回数据
        if(isset($params['statistic'])){
            $return_data = [
                'statistic_list' => [
                    'c_instock_money'  => isset($count_row['c_instock_money']) ? $count_row['c_instock_money'] : 0,
                    'c_other_money'    => isset($count_row['c_other_money']) ? $count_row['c_other_money'] : 0,
                    'c_paid_money'     => isset($count_row['c_paid_money']) ? $count_row['c_paid_money'] : 0,
                    'c_refunded_money' => isset($count_row['c_refunded_money']) ? $count_row['c_refunded_money'] : 0,
                    'c_loss_money'     => isset($count_row['c_loss_money']) ? $count_row['c_loss_money'] : 0,
                    'c_adjust_money'   => isset($count_row['c_adjust_money']) ? $count_row['c_adjust_money'] : 0,
                    'c_ending_balance' => isset($count_row['c_ending_balance']) ? $count_row['c_ending_balance'] : 0,
                ]
            ];
            return $return_data;

        }else{
            $drop_down_list['purchase_name'] = get_purchase_agent(null,true);//公司主体
            $return_data = [
                'key'           => $this->get_head_list(),
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
     * 月报表
     * @param $params
     * @param $offsets
     * @param $limit
     * @param $page
     * @return array
     */
    public function month_supplier_balance_list($params, $offsets, $limit, $page){
        if(BALANCE_SWITCH_ENGINE == 1){
            $this->load->library('mongo_db');

            if(isset($params['ids']) and $params['ids'] != ''){
                $ids_arr = array_map('intval',explode(',',$params['ids']));// 要转成和mongodb数据类型一致
                $this->mongo_db->where_in('id', $ids_arr);
            }
            if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
                $this->mongo_db->where(['supplier_code' => $params['supplier_code']]);
            }
            if(isset($params['purchase_name']) and !empty($params['purchase_name'])){
                $this->mongo_db->where(['purchase_name' => $params['purchase_name'] != 'none'?$params['purchase_name']:'']);
            }
            if(isset($params['ending_balance']) and !empty($params['ending_balance'])){
                if(count($params['ending_balance']) == 1){
                    if(in_array('>',$params['ending_balance'])){
                        $this->mongo_db->where_gt('c_ending_balance',0);
                    }elseif(in_array('>',$params['ending_balance'])){
                        $this->mongo_db->where_lt('c_ending_balance',0);
                    }else{
                        $this->mongo_db->where(['c_ending_balance' => 0]);
                    }
                }elseif(count($params['ending_balance']) == 2){
                    if(!in_array('<',$params['ending_balance'])){// 大于等于0
                        $this->mongo_db->where_gte('c_ending_balance',0);
                    }elseif(!in_array('>',$params['ending_balance'])){// 小于等于0
                        $this->mongo_db->where_lte('c_ending_balance',0);
                    }else{// 不等于0
                        $this->mongo_db->where_ne('c_ending_balance',0);
                    }
                }else{// =3的不设置查询条件

                }
            }
            if(isset($params['settlement_inland']) and !empty($params['settlement_inland'])){
                if(is_array($params['settlement_inland'])){
                    $this->mongo_db->where_in('settlement_inland',$params['settlement_inland']);
                }else{
                    $this->mongo_db->where(['settlement_inland' => $params['settlement_inland']]);
                }
            }
            if(isset($params['settlement_oversea']) and !empty($params['settlement_oversea'])){
                if(is_array($params['settlement_oversea'])){
                    $this->mongo_db->where_in('settlement_oversea',$params['settlement_oversea']);
                }else{
                    $this->mongo_db->where(['settlement_oversea' => $params['settlement_oversea']]);
                }
            }
            if(isset($params['diff_type']) and !empty($params['diff_type'])){
                if($params['diff_type'] == 1){
                    $this->mongo_db->where('settlement_oversea=settlement_inland');
                }else{
                    $this->mongo_db->where('settlement_oversea<>settlement_inland');
                }
            }
            if(isset($params['start_date']) and isset($params['end_date'])
                and !empty($params['end_date']) and !empty($params['start_date'])
            ){// 统计年份
                $getLastDay = $this->getLastDay(date('Y', strtotime($params['end_date'])),date('m', strtotime($params['end_date'])));// 计算输入日期月对应的最后一天
                $start_time = date('Y-m', strtotime($params['start_date']));// 输入月份的第一天
                $end_time   = date('Y-m', strtotime($params['end_date'])).'-'.$getLastDay.' 23:59:59';
                $this->mongo_db->where_between("statistic_time",$start_time,$end_time);
            }

            if(isset($params['statistic'])){// 页面汇总
                $count_qb = clone $this->mongo_db;
                $count_row = $count_qb->aggregate(
                    'PurSupplierAccountsPayableBalanceMonth',
                    'sum',
                    [
                        'c_instock_money'     => 'c_instock_money',
                        'c_other_money'       => 'c_other_money',
                        'c_paid_money'        => 'c_paid_money',
                        'total_trading_money' => 'trading_money',
                        'c_refunded_money'    => 'c_refunded_money',
                        'c_loss_money'        => 'c_loss_money',
                        'c_adjust_money'      => 'c_adjust_money',
                        'c_ending_balance'    => 'c_ending_balance',
                    ]
                );

            }else{
                $count_qb = clone $this->mongo_db;
                if(isset($params['order_by']) and !empty($params['order_by']) and isset($params['order_by_type'])){
                    if(in_array($params['order_by'],$this->getOrderBy()) and in_array($params['order_by_type'],$this->getOrderByType())){
                        $this->mongo_db->order_by([$params['order_by'] => $params['order_by_type']]);
                    }
                }else{
                    $this->mongo_db->order_by(['statistic_time' => 'asc']);
                }
                $this->mongo_db->limit($limit)->offset($page);
                $results = $this->mongo_db->get('PurSupplierAccountsPayableBalanceMonth');
                if(!empty($results)){
                    foreach($results as $key => $val){
                        $val_tmp = json_decode(json_encode($val), true);
                        unset($val_tmp['_id']);
                        $results[$key]                  = $val_tmp;
                        $results[$key]['purchase_name'] = get_purchase_agent($val_tmp['purchase_name']);
                    }
                }

                $count_row   = $count_qb->count('PurSupplierAccountsPayableBalanceMonth');
                $total_count = $count_row ? $count_row : 0;
            }

        }else{
            $slaveDb = $this->load->database('slave',TRUE);// 从库读取数据

            $slaveDb->from($this->table_name_month);
            if(isset($params['ids']) and $params['ids'] != ''){
                $ids_arr = explode(',',$params['ids']);
                $slaveDb->where_in('id', $ids_arr);
            }
            if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
                $slaveDb->where('supplier_code', $params['supplier_code']);
            }
            if(isset($params['purchase_name']) and !empty($params['purchase_name'])){
                $slaveDb->where('purchase_name', $params['purchase_name'] != 'none'?$params['purchase_name']:'');
            }
            if(isset($params['ending_balance']) and !empty($params['ending_balance'])){
                if(count($params['ending_balance']) == 1){
                    if(in_array('>',$params['ending_balance'])){
                        $slaveDb->where('c_ending_balance >', 0);
                    }elseif(in_array('<',$params['ending_balance'])){
                        $slaveDb->where('c_ending_balance <', 0);
                    }else{
                        $slaveDb->where('c_ending_balance', 0);
                    }
                }elseif(count($params['ending_balance']) == 2){
                    if(!in_array('<',$params['ending_balance'])){// 大于等于0
                        $slaveDb->where('c_ending_balance >=', 0);
                    }elseif(!in_array('>',$params['ending_balance'])){// 小于等于0
                        $slaveDb->where('c_ending_balance <=', 0);
                    }else{// 不等于0
                        $slaveDb->where('c_ending_balance <>', 0);
                    }
                }else{// =3的不设置查询条件

                }
            }
            if(isset($params['settlement_inland']) and !empty($params['settlement_inland'])){
                if(is_array($params['settlement_inland'])){
                    $slaveDb->where_in('settlement_inland', $params['settlement_inland']);
                }else{
                    $slaveDb->where('settlement_inland', $params['settlement_inland']);
                }
            }
            if(isset($params['settlement_oversea']) and !empty($params['settlement_oversea'])){
                if(is_array($params['settlement_oversea'])){
                    $slaveDb->where_in('settlement_oversea', $params['settlement_oversea']);
                }else{
                    $slaveDb->where('settlement_oversea', $params['settlement_oversea']);
                }
            }
            if(isset($params['diff_type']) and !empty($params['diff_type'])){
                if($params['diff_type'] == 1){
                    $slaveDb->where('settlement_oversea=settlement_inland');
                }else{
                    $slaveDb->where('settlement_oversea<>settlement_inland');
                }
            }
            if(isset($params['start_date']) and isset($params['end_date'])
                and !empty($params['end_date']) and !empty($params['start_date'])
            ){// 统计年份
                $getLastDay = $this->getLastDay(date('Y', strtotime($params['end_date'])),date('m', strtotime($params['end_date'])));// 计算输入日期月对应的最后一天
                $start_time = date('Y-m', strtotime($params['start_date']));// 输入月份的第一天
                $end_time   = date('Y-m', strtotime($params['end_date'])).'-'.$getLastDay.' 23:59:59';
                $slaveDb->where("statistic_time between '{$start_time}' and '{$end_time}' ");
            }

            if(isset($params['statistic'])){// 页面汇总
                $count_qb  = clone $slaveDb;
                $count_row = $count_qb->select(
                    'count(id) as num,'
                    .'sum(c_instock_money) as c_instock_money,'
                    .'sum(c_other_money) as c_other_money,'
                    .'sum(c_paid_money) as c_paid_money,'
                    .'sum(c_refunded_money) as c_refunded_money,'
                    .'sum(c_loss_money) as c_loss_money,'
                    .'sum(c_adjust_money) as c_adjust_money,'
                    .'sum(c_ending_balance) as c_ending_balance'
                )->get()->row_array();

            }else{
                $count_qb = clone $slaveDb;
                $slaveDb->select('*')->limit($limit, $offsets);
                if(isset($params['order_by']) and !empty($params['order_by']) and isset($params['order_by_type'])){
                    if(in_array($params['order_by'],$this->getOrderBy()) and in_array($params['order_by_type'],$this->getOrderByType())){
                        $slaveDb->order_by($params['order_by'].' '.$params['order_by_type']);
                    }
                }else{
                    $slaveDb->order_by('statistic_time ASC');
                }
                $results = $slaveDb->get()->result_array();

                if(!empty($results)){
                    foreach($results as $key => $val){
                        $results[$key]['purchase_name'] = get_purchase_agent($val['purchase_name']);
                    }
                }

                $count_row   = $count_qb->select('count(id) as num')->get()->row_array();
                $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
            }
        }

        // 返回数据
        if(isset($params['statistic'])){
            $return_data = [
                'statistic_list' => [
                    'c_instock_money'  => isset($count_row['c_instock_money']) ? $count_row['c_instock_money'] : 0,
                    'c_other_money'    => isset($count_row['c_other_money']) ? $count_row['c_other_money'] : 0,
                    'c_paid_money'     => isset($count_row['c_paid_money']) ? $count_row['c_paid_money'] : 0,
                    'c_refunded_money' => isset($count_row['c_refunded_money']) ? $count_row['c_refunded_money'] : 0,
                    'c_loss_money'     => isset($count_row['c_loss_money']) ? $count_row['c_loss_money'] : 0,
                    'c_adjust_money'   => isset($count_row['c_adjust_money']) ? $count_row['c_adjust_money'] : 0,
                    'c_ending_balance' => isset($count_row['c_ending_balance']) ? $count_row['c_ending_balance'] : 0,
                ]
            ];
            return $return_data;

        }else{
            $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
            $down_settlement = $this->settlementModel->get_settlement();
            $drop_down_list['down_settlement'] = array_column($down_settlement['list'],'settlement_name','settlement_code');
            $drop_down_list['purchase_name'] = get_purchase_agent(null,true);//公司主体
            $drop_down_list['ending_balance_query'] = $this->getEndingBalanceQuery();//期末余额筛选
            $drop_down_list['diff_type_list'] = ['1' => '相同','2' => '不同'];// 结算方式差异
            $return_data = [
                'key'           => $this->get_head_list(),
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
     * 年度报表
     * @param $params
     * @param $offsets
     * @param $limit
     * @param $page
     * @return array
     */
    public function year_supplier_balance_list($params, $offsets, $limit, $page){
        if(BALANCE_SWITCH_ENGINE == 1){
            $this->load->library('mongo_db');

            if(isset($params['ids']) and $params['ids'] != ''){
                $ids_arr = array_map('intval',explode(',',$params['ids']));// 要转成和mongodb数据类型一致
                $this->mongo_db->where_in('id', $ids_arr);
            }
            if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
                $this->mongo_db->where(['supplier_code' => $params['supplier_code']]);
            }
            if(isset($params['purchase_name']) and !empty($params['purchase_name'])){
                $this->mongo_db->where(['purchase_name' => $params['purchase_name'] != 'none'?$params['purchase_name']:'']);
            }
            if(isset($params['ending_balance']) and !empty($params['ending_balance'])){
                if(count($params['ending_balance']) == 1){
                    if(in_array('>',$params['ending_balance'])){
                        $this->mongo_db->where_gt('c_ending_balance',0);
                    }elseif(in_array('<',$params['ending_balance'])){
                        $this->mongo_db->where_lt('c_ending_balance',0);
                    }else{
                        $this->mongo_db->where(['c_ending_balance' => 0]);
                    }
                }elseif(count($params['ending_balance']) == 2){
                    if(!in_array('<',$params['ending_balance'])){// 大于等于0
                        $this->mongo_db->where_gte('c_ending_balance',0);
                    }elseif(!in_array('>',$params['ending_balance'])){// 小于等于0
                        $this->mongo_db->where_lte('c_ending_balance',0);
                    }else{// 不等于0
                        $this->mongo_db->where_ne('c_ending_balance',0);
                    }
                }else{// =3的不设置查询条件

                }
            }
            if(isset($params['settlement_inland']) and !empty($params['settlement_inland'])){
                if(is_array($params['settlement_inland'])){
                    $this->mongo_db->where_in('settlement_inland',$params['settlement_inland']);
                }else{
                    $this->mongo_db->where(['settlement_inland' => $params['settlement_inland']]);
                }
            }
            if(isset($params['settlement_oversea']) and !empty($params['settlement_oversea'])){
                if(is_array($params['settlement_oversea'])){
                    $this->mongo_db->where_in('settlement_oversea',$params['settlement_oversea']);
                }else{
                    $this->mongo_db->where(['settlement_oversea' => $params['settlement_oversea']]);
                }
            }
            if(isset($params['diff_type']) and !empty($params['diff_type'])){
                if($params['diff_type'] == 1){
                    $this->mongo_db->where('settlement_oversea=settlement_inland');
                }else{
                    $this->mongo_db->where('settlement_oversea<>settlement_inland');
                }
            }
            if(isset($params['date']) and !empty($params['date'])){// 统计年份
                $this->mongo_db->where(["statistic_time" => $params['date']]);
            }

            if(isset($params['statistic'])){// 页面汇总
                $count_qb = clone $this->mongo_db;
                $count_row = $count_qb->aggregate(
                    'PurSupplierAccountsPayableBalanceYear',
                    'sum',
                    [
                        'c_instock_money'     => 'c_instock_money',
                        'c_other_money'       => 'c_other_money',
                        'c_paid_money'        => 'c_paid_money',
                        'total_trading_money' => 'trading_money',
                        'c_refunded_money'    => 'c_refunded_money',
                        'c_loss_money'        => 'c_loss_money',
                        'c_adjust_money'      => 'c_adjust_money',
                        'c_ending_balance'    => 'c_ending_balance',
                    ]
                );

            }else{
                $count_qb = clone $this->mongo_db;
                if(isset($params['order_by']) and !empty($params['order_by']) and isset($params['order_by_type'])){
                    if(in_array($params['order_by'],$this->getOrderBy()) and in_array($params['order_by_type'],$this->getOrderByType())){
                        $this->mongo_db->order_by([$params['order_by'] => $params['order_by_type']]);
                    }
                }else{
                    $this->mongo_db->order_by(['statistic_time' => 'asc']);
                }
                $this->mongo_db->limit($limit)->offset($page);
                $results = $this->mongo_db->get('PurSupplierAccountsPayableBalanceYear');
                if(!empty($results)){
                    foreach($results as $key => $val){
                        $val_tmp = json_decode(json_encode($val), true);
                        unset($val_tmp['_id']);
                        $results[$key]                  = $val_tmp;
                        $results[$key]['purchase_name'] = get_purchase_agent($val_tmp['purchase_name']);
                    }
                }

                $count_row   = $count_qb->count('PurSupplierAccountsPayableBalanceYear');
                $total_count = $count_row ? $count_row : 0;
            }

        }else{
            $slaveDb = $this->load->database('slave',TRUE);// 从库读取数据

            $slaveDb->from($this->table_name_year);
            if(isset($params['ids']) and $params['ids'] != ''){
                $ids_arr = explode(',',$params['ids']);
                $slaveDb->where_in('id', $ids_arr);
            }
            if(isset($params['supplier_code']) and !empty($params['supplier_code'])){
                $slaveDb->where('supplier_code', $params['supplier_code']);
            }
            if(isset($params['purchase_name']) and !empty($params['purchase_name'])){
                $slaveDb->where('purchase_name', $params['purchase_name'] != 'none'?$params['purchase_name']:'');
            }
            if(isset($params['ending_balance']) and !empty($params['ending_balance'])){
                if(count($params['ending_balance']) == 1){
                    if(in_array('>',$params['ending_balance'])){
                        $slaveDb->where('c_ending_balance >', 0);
                    }elseif(in_array('<',$params['ending_balance'])){
                        $slaveDb->where('c_ending_balance <', 0);
                    }else{
                        $slaveDb->where('c_ending_balance', 0);
                    }
                }elseif(count($params['ending_balance']) == 2){
                    if(!in_array('<',$params['ending_balance'])){// 大于等于0
                        $slaveDb->where('c_ending_balance >=', 0);
                    }elseif(!in_array('>',$params['ending_balance'])){// 小于等于0
                        $slaveDb->where('c_ending_balance <=', 0);
                    }else{// 不等于0
                        $slaveDb->where('c_ending_balance <>', 0);
                    }
                }else{// =3的不设置查询条件

                }
            }
            if(isset($params['settlement_inland']) and !empty($params['settlement_inland'])){
                if(is_array($params['settlement_inland'])){
                    $slaveDb->where_in('settlement_inland', $params['settlement_inland']);
                }else{
                    $slaveDb->where('settlement_inland', $params['settlement_inland']);
                }
            }
            if(isset($params['settlement_oversea']) and !empty($params['settlement_oversea'])){
                if(is_array($params['settlement_oversea'])){
                    $slaveDb->where_in('settlement_oversea', $params['settlement_oversea']);
                }else{
                    $slaveDb->where('settlement_oversea', $params['settlement_oversea']);
                }
            }
            if(isset($params['diff_type']) and !empty($params['diff_type'])){
                if($params['diff_type'] == 1){
                    $slaveDb->where('settlement_oversea=settlement_inland');
                }else{
                    $slaveDb->where('settlement_oversea<>settlement_inland');
                }
            }
            if(isset($params['date']) and !empty($params['date'])){// 统计年份
                $slaveDb->where("statistic_time",$params['date']);
            }

            if(isset($params['statistic'])){// 页面汇总
                $count_qb  = clone $slaveDb;
                $count_row = $count_qb->select(
                    'count(id) as num,'
                    .'sum(c_instock_money) as c_instock_money,'
                    .'sum(c_other_money) as c_other_money,'
                    .'sum(c_paid_money) as c_paid_money,'
                    .'sum(c_refunded_money) as c_refunded_money,'
                    .'sum(c_loss_money) as c_loss_money,'
                    .'sum(c_adjust_money) as c_adjust_money,'
                    .'sum(c_ending_balance) as c_ending_balance'
                )->get()->row_array();

            }else{
                $count_qb = clone $slaveDb;
                $slaveDb->select('*')->limit($limit, $offsets);
                if(isset($params['order_by']) and !empty($params['order_by']) and isset($params['order_by_type'])){
                    if(in_array($params['order_by'],$this->getOrderBy()) and in_array($params['order_by_type'],$this->getOrderByType())){
                        $slaveDb->order_by($params['order_by'].' '.$params['order_by_type']);
                    }
                }else{
                    $slaveDb->order_by('statistic_time ASC');
                }
                $results = $slaveDb->get()->result_array();

                if(!empty($results)){
                    foreach($results as $key => $val){
                        $results[$key]['purchase_name'] = get_purchase_agent($val['purchase_name']);
                    }
                }

                $count_row   = $count_qb->select('count(id) as num')->get()->row_array();
                $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;
            }
        }

        // 返回数据
        if(isset($params['statistic'])){
            $return_data = [
                'statistic_list' => [
                    'c_instock_money'  => isset($count_row['c_instock_money']) ? $count_row['c_instock_money'] : 0,
                    'c_other_money'    => isset($count_row['c_other_money']) ? $count_row['c_other_money'] : 0,
                    'c_paid_money'     => isset($count_row['c_paid_money']) ? $count_row['c_paid_money'] : 0,
                    'c_refunded_money' => isset($count_row['c_refunded_money']) ? $count_row['c_refunded_money'] : 0,
                    'c_loss_money'     => isset($count_row['c_loss_money']) ? $count_row['c_loss_money'] : 0,
                    'c_adjust_money'   => isset($count_row['c_adjust_money']) ? $count_row['c_adjust_money'] : 0,
                    'c_ending_balance' => isset($count_row['c_ending_balance']) ? $count_row['c_ending_balance'] : 0,
                ]
            ];
            return $return_data;

        }else{
            $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
            $down_settlement = $this->settlementModel->get_settlement();
            $drop_down_list['down_settlement'] = array_column($down_settlement['list'],'settlement_name','settlement_code');
            $drop_down_list['purchase_name'] = get_purchase_agent(null,true);//公司主体
            $drop_down_list['ending_balance_query'] = $this->getEndingBalanceQuery();//期末余额筛选
            $drop_down_list['diff_type_list'] = ['1' => '相同','2' => '不同'];// 结算方式差异
            $return_data = [
                'key'           => $this->get_head_list(),
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





    //region  新增加 日/月/年 维度记录
    /**
     * 转换金额类型
     * @param $trading_detail_item
     * @return string
     */
    public function convert_to_money($trading_detail_item){
        $money_type = '';
        switch($trading_detail_item['trading_type']){
            case 1:// 入库商品额,入库应付=入库商品额+入库退货
            case 7:// 入库退货,入库应付=入库商品额+入库退货
                $money_type = 'c_instock_money';
                break;

            case 3:// 其他应付=其他费用
                $money_type = 'c_other_money';
                break;

            case 4:// 本期已付=付款
                $money_type = 'c_paid_money';
                break;

            case 2:// 报损额=报损商品额
                $money_type = 'c_loss_money';
                break;

            case 8:// 调整金额=调整单金额
                $money_type = 'c_adjust_money';
                break;

            case 5:// 普通退款,退款金额=普通退款+退货退款
            case 6:// 退货退款,退款金额=普通退款+退货退款
            case 9:// 线下退款,退款金额=普通退款+退货退款+线下退款
                $money_type = 'c_refunded_money';
                break;

        }

        return $money_type;
    }

    /**
     * 查找已经存在的记录
     * @param $table_name
     * @param $supplier_code
     * @param $purchase_name
     * @param $statistic_time
     * @return array
     */
    public function have_record($table_name,$supplier_code,$purchase_name,$statistic_time){
        $exists =  $this->purchase_db->where('supplier_code',$supplier_code)
            ->where('purchase_name',$purchase_name)
            ->where('statistic_time',$statistic_time)
            ->get($table_name)
            ->row_array();

        return $exists?$exists:[];
    }

    /**
     * 新增的交易明细 汇总到 日/月/年 维度
     * @return array
     */
    public function trading_detail_to_deal(){
        $return = ['code' => false,'message' => '操作失败'];

        // 处理 今天之前的 待计算的 交易明细
        $trading_detail_query = "SELECT 
            -- GROUP_CONCAT(id) as ids,
            id as ids,
            order_no,
            relative_trading_num,
            supplier_code,
            supplier_name,
            purchase_name,
            trading_time,
            trading_type,
            -- SUM(trading_money) AS trading_money
            trading_money AS trading_money
            
            
            FROM {$this->table_name} 
            WHERE is_calculated=0 AND trading_time<='{$this->yesterday} 23:59:59'
            -- GROUP BY supplier_code,purchase_name,trading_type,LEFT(trading_time,10)
            LIMIT 1000";

        $trading_detail_list = $this->purchase_db->query($trading_detail_query)->result_array();

        if($trading_detail_list){
            try{
                foreach($trading_detail_list as $trading_detail_value){
                    $res_day   = $this->add_to_balance_day($trading_detail_value);// 更新或插入日维度
                    if($res_day['code'] === true){
                        if($res_day['is_calculated'] == 0){// is_calculated=1 表示日维度已经计算月维度
                            // 如果操作的日维度记录未被计算到月维度 则不汇总到 月维度（由 is_calculated=0 控制插入）

                        }else{// 如果操作的日维度记录已经计算到月维度了，则把新增明细汇总到月维度
                            $res_month = $this->add_to_balance_month($trading_detail_value);
                        }
                        if($res_day['is_calculated'] == 0){// 同月维度

                        }else{
                            $res_year  = $this->add_to_balance_year($trading_detail_value);
                        }

                        // 更新交易明细为已计算
                        $ids_str = $trading_detail_value['ids'];
                        $this->purchase_db->query("UPDATE {$this->table_name} SET is_calculated=1 WHERE id IN(".$ids_str.")");
                    }
                }

                $return['code']    = true;
                $return['message'] = '交易明细处理成功：'.count($trading_detail_list) .' 条';

            }catch(Exception $exception){
                $return['message'] = $exception->getMessage();
            }

        }else{
            $return['message'] = '交易明细未获取到数据';
        }

        return $return;
    }

    /**
     * 新增的日维度 汇总到 月维度
     */
    public function balance_day_to_balance_month(){
        $return = ['code' => false,'message' => '操作失败'];

        $balance_day_detail_query = "SELECT 
            GROUP_CONCAT(id) as ids,
            supplier_code,
            supplier_name,
            purchase_name,
            LEFT(statistic_time,7) AS statistic_time,
            SUM(c_opening_balance) AS c_opening_balance,
            SUM(c_ending_balance) AS c_ending_balance,
            SUM(c_instock_money) AS c_instock_money,
            SUM(c_other_money) AS c_other_money,
            SUM(c_paid_money) AS c_paid_money,
            SUM(c_refunded_money) AS c_refunded_money,
            SUM(c_loss_money) AS c_loss_money,
            SUM(c_adjust_money) AS c_adjust_money
            
            FROM {$this->table_name_day} 
            WHERE is_calculated=0 AND LEFT(statistic_time,7)<='{$this->last_month}'
            GROUP BY supplier_code,purchase_name,LEFT(statistic_time,7)
            LIMIT 1000";

        $balance_day_detail_list = $this->purchase_db->query($balance_day_detail_query)->result_array();

        if($balance_day_detail_list){
            try{
                foreach($balance_day_detail_list as $balance_day_detail_value){
                    $ids_str = $balance_day_detail_value['ids'];
                    unset($balance_day_detail_value['ids']);

                    $res_month = $this->add_to_balance_month_from_day($balance_day_detail_value);// 更新或插入月维度

                    if($res_month['code'] === true){
                        // 更新交易明细为已计算
                        $this->purchase_db->query("UPDATE {$this->table_name_day} SET is_calculated=1 WHERE id IN(".$ids_str.")");
                    }
                }

                $return['code']    = true;
                $return['message'] = '日维度处理成功：'.count($balance_day_detail_list) .' 条';

            }catch(Exception $exception){
                $return['message'] = $exception->getMessage();
            }

        }else{
            $return['message'] = '日维度未获取到数据';
        }

        return $return;
    }

    /**
     * 新增的月维度 汇总到 年维度
     */
    public function balance_month_to_balance_year(){
        $return = ['code' => false,'message' => '操作失败'];

        $balance_month_detail_query = "SELECT 
            GROUP_CONCAT(id) as ids,
            supplier_code,
            supplier_name,
            purchase_name,
            LEFT(statistic_time,4) AS statistic_time,
            SUM(c_opening_balance) AS c_opening_balance,
            SUM(c_ending_balance) AS c_ending_balance,
            SUM(c_instock_money) AS c_instock_money,
            SUM(c_other_money) AS c_other_money,
            SUM(c_paid_money) AS c_paid_money,
            SUM(c_refunded_money) AS c_refunded_money,
            SUM(c_loss_money) AS c_loss_money,
            SUM(c_adjust_money) AS c_adjust_money
            
            FROM {$this->table_name_month} 
            WHERE is_calculated=0 AND LEFT(statistic_time,4)<='{$this->last_year}'
            GROUP BY supplier_code,purchase_name,LEFT(statistic_time,4)
            LIMIT 1000";

        $balance_month_detail_list = $this->purchase_db->query($balance_month_detail_query)->result_array();

        if($balance_month_detail_list){
            try{
                foreach($balance_month_detail_list as $balance_month_detail_value){
                    $ids_str = $balance_month_detail_value['ids'];
                    unset($balance_month_detail_value['ids']);

                    $res_year = $this->add_to_balance_year_from_month($balance_month_detail_value);// 更新或插入年维度

                    if($res_year['code'] === true){
                        // 更新交易明细为已计算
                        $this->purchase_db->query("UPDATE {$this->table_name_month} SET is_calculated=1 WHERE id IN(".$ids_str.")");
                    }
                }

                $return['code']    = true;
                $return['message'] = '月维度处理成功：'.count($balance_month_detail_list) .' 条';

            }catch(Exception $exception){
                $return['message'] = $exception->getMessage();
            }

        }else{
            $return['message'] = '月维度未获取到数据';
        }

        return $return;
    }

    /**
     * 添加记录到 日维度
     * @param $trading_detail_item
     * @return array
     */
    public function add_to_balance_day($trading_detail_item){
        $return = ['code' => false,'message' => '操作失败','is_calculated' => 0];

        $statistic_time = substr($trading_detail_item['trading_time'],0,10);

        $money_type = $this->convert_to_money($trading_detail_item);
        if(empty($money_type)){
            $return['message'] = '金额类型错误：'.$money_type;
            return $return;
        }

        $have = $this->have_record($this->table_name_day,$trading_detail_item['supplier_code'],$trading_detail_item['purchase_name'],$statistic_time);

        if($have){
            $update_arr = [
                $money_type   => $trading_detail_item['trading_money'] + $have[$money_type],
                'is_updated'  => 1,// 1.已更新-用于计算本维度的期初期末
                'update_time' => $this->now_time,
            ];
            $result_day = $this->purchase_db->where('id', $have['id'])->update($this->table_name_day, $update_arr);

            $return['is_calculated'] = $have['is_calculated'];// 该记录是否计算过月维度
        }else{
            $update_arr = [
                'supplier_code'   => $trading_detail_item['supplier_code'],
                'supplier_name'   => $trading_detail_item['supplier_name'],
                'purchase_name'   => $trading_detail_item['purchase_name'],
                'occurrence_time' => $this->now_time,
                'statistic_time'  => $statistic_time,
                'update_time'     => $this->now_time,
                $money_type       => $trading_detail_item['trading_money'],
                'is_updated'      => 1,// 1.已更新-用于计算本维度的期初期末
            ];
            $result_day = $this->purchase_db->insert($this->table_name_day, $update_arr);

            $return['is_calculated'] = 0;// 该记录是否计算过月维度=否
        }

        if(!empty($result_day)){
            $return['code'] = true;
            $return['message'] = '操作成功';
        }

        return $return;
    }

    /**
     * 添加记录到 月维度
     * @param $trading_detail_item
     * @return array
     */
    public function add_to_balance_month($trading_detail_item){
        $return = ['code' => false,'message' => '操作失败'];

        $statistic_time = substr($trading_detail_item['trading_time'],0,7);
        if($statistic_time >= $this->month){// 交易记录月份 非本月才添加
            $return['message'] = '交易明细记录添加到月维度-还未到处理时间';
            return $return;
        }

        $money_type = $this->convert_to_money($trading_detail_item);
        if(empty($money_type)){
            $return['message'] = '金额类型错误：'.$money_type;
            return $return;
        }

        $have = $this->have_record($this->table_name_month,$trading_detail_item['supplier_code'],$trading_detail_item['purchase_name'],$statistic_time);

        if($have){
            $update_arr = [
                $money_type   => $trading_detail_item['trading_money'] + $have[$money_type],
                'is_updated'  => 1,// 1.已更新-用于计算本维度的期初期末
                'update_time' => $this->now_time,
            ];
            $result_day = $this->purchase_db->where('id', $have['id'])->update($this->table_name_month, $update_arr);
        }else{
            // 获取当前结算方式
            $supplierPaymentInfo = $this->supplier_payment_info_model->get_payment_info_combine($trading_detail_item['supplier_code']);
            $update_arr = [
                'supplier_code'   => $trading_detail_item['supplier_code'],
                'supplier_name'   => $trading_detail_item['supplier_name'],
                'purchase_name'   => $trading_detail_item['purchase_name'],
                'settlement_inland' => isset($supplierPaymentInfo['settlement_inland'])?$supplierPaymentInfo['settlement_inland']:'0',
                'settlement_inland_cn' => isset($supplierPaymentInfo['settlement_inland_cn'])?$supplierPaymentInfo['settlement_inland_cn']:'',
                'settlement_oversea' => isset($supplierPaymentInfo['settlement_oversea'])?$supplierPaymentInfo['settlement_oversea']:'0',
                'settlement_oversea_cn' => isset($supplierPaymentInfo['settlement_oversea_cn'])?$supplierPaymentInfo['settlement_oversea_cn']:'',
                'occurrence_time' => $this->now_time,
                'statistic_time'  => $statistic_time,
                'update_time'      => $this->now_time,
                $money_type       => $trading_detail_item['trading_money'],
                'is_updated'      => 1,// 1.已更新-用于计算本维度的期初期末
            ];
            $result_day = $this->purchase_db->insert($this->table_name_month, $update_arr);
        }

        if($result_day){
            $return['code'] = true;
            $return['message'] = '操作成功';
        }

        return $return;
    }

    /**
     * 添加记录到 月维度 - 从日维度汇总
     * 说明：与 add_to_balance_month 不同的是，add_to_balance_month 是用来补充历史数据的
     *      比如 当前是5月份，新增了一条 3月份的入库记录，3月份的日维度数据可能已经汇总到 月维度了，
     *      所以只能使用增量的方式 累加到 日维度/月维度/年维度
     * @param $trading_detail_item
     * @return array
     */
    public function add_to_balance_month_from_day($trading_detail_item){
        $return = ['code' => false,'message' => '操作失败'];

        $statistic_time = substr($trading_detail_item['statistic_time'],0,7);
        if($statistic_time >= $this->month){// 交易记录月份 非本月才添加
            $return['message'] = '日维度记录添加到月维度-还未到处理时间';
            return $return;
        }

        $have = $this->have_record($this->table_name_month,$trading_detail_item['supplier_code'],$trading_detail_item['purchase_name'],$statistic_time);

        if($have){
            $update_arr = [
                'update_time'      => $this->now_time,
                'c_instock_money'  => $trading_detail_item['c_instock_money'] + $have['c_instock_money'],
                'c_other_money'    => $trading_detail_item['c_other_money'] + $have['c_other_money'],
                'c_paid_money'     => $trading_detail_item['c_paid_money'] + $have['c_paid_money'],
                'c_refunded_money' => $trading_detail_item['c_refunded_money'] + $have['c_refunded_money'],
                'c_loss_money'     => $trading_detail_item['c_loss_money'] + $have['c_loss_money'],
                'c_adjust_money'   => $trading_detail_item['c_adjust_money'] + $have['c_adjust_money'],
                'is_updated'       => 1,// 1.已更新-用于计算本维度的期初期末
            ];
            $result_day = $this->purchase_db->where('id', $have['id'])->update($this->table_name_month, $update_arr);
        }else{
            // 获取当前结算方式
            $supplierPaymentInfo = $this->supplier_payment_info_model->get_payment_info_combine($trading_detail_item['supplier_code']);
            $update_arr = [
                'supplier_code'    => $trading_detail_item['supplier_code'],
                'supplier_name'    => $trading_detail_item['supplier_name'],
                'purchase_name'    => $trading_detail_item['purchase_name'],
                'settlement_inland' => isset($supplierPaymentInfo['settlement_inland'])?$supplierPaymentInfo['settlement_inland']:'0',
                'settlement_inland_cn' => isset($supplierPaymentInfo['settlement_inland_cn'])?$supplierPaymentInfo['settlement_inland_cn']:'',
                'settlement_oversea' => isset($supplierPaymentInfo['settlement_oversea'])?$supplierPaymentInfo['settlement_oversea']:'0',
                'settlement_oversea_cn' => isset($supplierPaymentInfo['settlement_oversea_cn'])?$supplierPaymentInfo['settlement_oversea_cn']:'',
                'occurrence_time'  => $this->now_time,
                'statistic_time'   => $statistic_time,
                'update_time'      => $this->now_time,
                'c_instock_money'  => $trading_detail_item['c_instock_money'],
                'c_other_money'    => $trading_detail_item['c_other_money'],
                'c_paid_money'     => $trading_detail_item['c_paid_money'],
                'c_refunded_money' => $trading_detail_item['c_refunded_money'],
                'c_loss_money'     => $trading_detail_item['c_loss_money'],
                'c_adjust_money'   => $trading_detail_item['c_adjust_money'],
                'is_updated'       => 1,// 1.已更新-用于计算本维度的期初期末
            ];
            $result_day = $this->purchase_db->insert($this->table_name_month, $update_arr);
        }

        if(!empty($result_day)){
            $return['code'] = true;
            $return['message'] = '操作成功';
        }

        return $return;
    }

    /**
     * 添加记录到 年维度
     * @param $trading_detail_item
     * @return array
     */
    public function add_to_balance_year($trading_detail_item){
        $return = ['code' => false,'message' => '操作失败'];

        $statistic_time = substr($trading_detail_item['trading_time'],0,4);
        if($statistic_time >= $this->year){// 交易记录月份 非本年才添加
            $return['message'] = '交易明细记录添加到年维度-还未到处理时间';
            return $return;
        }

        $money_type = $this->convert_to_money($trading_detail_item);
        if(empty($money_type)){
            $return['message'] = '金额类型错误：'.$money_type;
            return $return;
        }

        $have = $this->have_record($this->table_name_year,$trading_detail_item['supplier_code'],$trading_detail_item['purchase_name'],$statistic_time);

        if($have){
            $update_arr = [
                $money_type   => $trading_detail_item['trading_money'] + $have[$money_type],
                'is_updated'  => 1,// 1.已更新-用于计算本维度的期初期末
                'update_time' => $this->now_time,
            ];
            $result_day = $this->purchase_db->where('id', $have['id'])->update($this->table_name_year, $update_arr);
        }else{
            // 获取当前结算方式
            $supplierPaymentInfo = $this->supplier_payment_info_model->get_payment_info_combine($trading_detail_item['supplier_code']);
            $update_arr = [
                'supplier_code'   => $trading_detail_item['supplier_code'],
                'supplier_name'   => $trading_detail_item['supplier_name'],
                'purchase_name'   => $trading_detail_item['purchase_name'],
                'settlement_inland' => isset($supplierPaymentInfo['settlement_inland'])?$supplierPaymentInfo['settlement_inland']:'0',
                'settlement_inland_cn' => isset($supplierPaymentInfo['settlement_inland_cn'])?$supplierPaymentInfo['settlement_inland_cn']:'',
                'settlement_oversea' => isset($supplierPaymentInfo['settlement_oversea'])?$supplierPaymentInfo['settlement_oversea']:'0',
                'settlement_oversea_cn' => isset($supplierPaymentInfo['settlement_oversea_cn'])?$supplierPaymentInfo['settlement_oversea_cn']:'',
                'occurrence_time' => $this->now_time,
                'statistic_time'  => $statistic_time,
                'update_time'     => $this->now_time,
                $money_type       => $trading_detail_item['trading_money'],
                'is_updated'      => 1,// 1.已更新-用于计算本维度的期初期末
            ];
            $result_day = $this->purchase_db->insert($this->table_name_year, $update_arr);
        }

        if(!empty($result_day)){
            $return['code'] = true;
            $return['message'] = '操作成功';
        }

        return $return;
    }

    /**
     * 添加记录到 年维度
     * @param $trading_detail_item
     * @return array
     */
    public function add_to_balance_year_from_month($trading_detail_item){
        $return = ['code' => false,'message' => '操作失败'];

        $statistic_time = substr($trading_detail_item['statistic_time'],0,4);
        if($statistic_time >= $this->year){// 交易记录月份 非本年才添加
            $return['message'] = '月维度记录添加到年维度-还未到处理时间';
            return $return;
        }

        $have = $this->have_record($this->table_name_year,$trading_detail_item['supplier_code'],$trading_detail_item['purchase_name'],$statistic_time);

        if($have){
            $update_arr = [
                'update_time'      => $this->now_time,
                'c_instock_money'  => $trading_detail_item['c_instock_money'] + $have['c_instock_money'],
                'c_other_money'    => $trading_detail_item['c_other_money'] + $have['c_other_money'],
                'c_paid_money'     => $trading_detail_item['c_paid_money'] + $have['c_paid_money'],
                'c_refunded_money' => $trading_detail_item['c_refunded_money'] + $have['c_refunded_money'],
                'c_loss_money'     => $trading_detail_item['c_loss_money'] + $have['c_loss_money'],
                'c_adjust_money'   => $trading_detail_item['c_adjust_money'] + $have['c_adjust_money'],
                'is_updated'       => 1,// 1.已更新-用于计算本维度的期初期末
            ];
            $result_day = $this->purchase_db->where('id', $have['id'])->update($this->table_name_year, $update_arr);
        }else{
            // 获取当前结算方式
            $supplierPaymentInfo = $this->supplier_payment_info_model->get_payment_info_combine($trading_detail_item['supplier_code']);
            $update_arr = [
                'supplier_code'    => $trading_detail_item['supplier_code'],
                'supplier_name'    => $trading_detail_item['supplier_name'],
                'purchase_name'    => $trading_detail_item['purchase_name'],
                'settlement_inland' => isset($supplierPaymentInfo['settlement_inland'])?$supplierPaymentInfo['settlement_inland']:'0',
                'settlement_inland_cn' => isset($supplierPaymentInfo['settlement_inland_cn'])?$supplierPaymentInfo['settlement_inland_cn']:'',
                'settlement_oversea' => isset($supplierPaymentInfo['settlement_oversea'])?$supplierPaymentInfo['settlement_oversea']:'0',
                'settlement_oversea_cn' => isset($supplierPaymentInfo['settlement_oversea_cn'])?$supplierPaymentInfo['settlement_oversea_cn']:'',
                'occurrence_time'  => $this->now_time,
                'statistic_time'   => $statistic_time,
                'update_time'      => $this->now_time,
                'c_instock_money'  => $trading_detail_item['c_instock_money'],
                'c_other_money'    => $trading_detail_item['c_other_money'],
                'c_paid_money'     => $trading_detail_item['c_paid_money'],
                'c_refunded_money' => $trading_detail_item['c_refunded_money'],
                'c_loss_money'     => $trading_detail_item['c_loss_money'],
                'c_adjust_money'   => $trading_detail_item['c_adjust_money'],
                'is_updated'       => 1,// 1.已更新-用于计算本维度的期初期末
            ];
            $result_day = $this->purchase_db->insert($this->table_name_year, $update_arr);
        }

        if(!empty($result_day)){
            $return['code'] = true;
            $return['message'] = '操作成功';
        }

        return $return;
    }
    //endregion




    //region  更新 日/月/年 维度 期初期末
    /**
     * 根据 是否更新 重新计算 日维度期初期末
     * @return array
     */
    public function calculate_balance_day_opening_and_ending_balance(){
        $return = ['code' => false,'message' => '操作失败'];

        $balance_day_query = "SELECT 
            supplier_code,
            supplier_name,
            purchase_name,
            MIN(statistic_time) AS statistic_time        
            
            FROM {$this->table_name_day} 
            WHERE is_updated=1
            GROUP BY supplier_code,purchase_name
            LIMIT 1000";

        $balance_day_list = $this->purchase_db->query($balance_day_query)->result_array();

        if(empty($balance_day_list)){
            $return['message'] = '没有需要计算的数据';
            return $return;
        }

        foreach($balance_day_list as $balance_day_item){
            $min_statistic_time = $balance_day_item['statistic_time'];
            $this->update_balance_day_opening_and_ending_balance($balance_day_item['supplier_code'],$balance_day_item['purchase_name'],$min_statistic_time);
        }

        $return['code'] = true;
        $return['message'] = '操作成功，更新条数：'.count($balance_day_list);
        return $return;
    }

    /**
     * 根据 是否更新 重新计算 月维度期初期末
     * @return array
     */
    public function calculate_balance_month_opening_and_ending_balance(){
        $return = ['code' => false,'message' => '操作失败'];

        $balance_month_query = "SELECT 
            supplier_code,
            supplier_name,
            purchase_name,
            MIN(statistic_time) AS statistic_time        
            
            FROM {$this->table_name_month} 
            WHERE is_updated=1
            GROUP BY supplier_code,purchase_name
            LIMIT 1000";

        $balance_month_list = $this->purchase_db->query($balance_month_query)->result_array();

        if(empty($balance_month_list)){
            $result = $this->auto_padding_balance_month();
            print_r("PLAN - 6.3、月维度自动填充处理<br/>\n");
            print_r($result);
            print_r("<br/><br/>\n\n");

            $return['message'] = '没有需要计算的数据';
            return $return;
        }

        foreach($balance_month_list as $balance_month_item){
            $min_statistic_time = substr($balance_month_item['statistic_time'],0,7);
            $this->update_balance_month_opening_and_ending_balance($balance_month_item['supplier_code'],$balance_month_item['purchase_name'],$min_statistic_time);
        }

        $return['code'] = true;
        $return['message'] = '操作成功，更新条数：'.count($balance_month_list);
        return $return;
    }

    /**
     * 根据 是否更新 重新计算 年维度期初期末
     * @return array
     */
    public function calculate_balance_year_opening_and_ending_balance(){
        $return = ['code' => false,'message' => '操作失败'];

        $balance_day_query = "SELECT 
            supplier_code,
            supplier_name,
            purchase_name,
            MIN(statistic_time) AS statistic_time        
            
            FROM {$this->table_name_year}
            WHERE is_updated=1
            GROUP BY supplier_code,purchase_name
            LIMIT 1000";

        $balance_year_list = $this->purchase_db->query($balance_day_query)->result_array();

        if(empty($balance_year_list)){
            $result = $this->auto_padding_balance_year();
            print_r("PLAN - 7.3、年维度自动填充处理<br/>\n");
            print_r($result);
            print_r("<br/><br/>\n\n");

            $return['message'] = '没有需要计算的数据';
            return $return;
        }

        foreach($balance_year_list as $balance_year_item){
            $min_statistic_time = $balance_year_item['statistic_time'];
            $this->update_balance_year_opening_and_ending_balance($balance_year_item['supplier_code'],$balance_year_item['purchase_name'],$min_statistic_time);
        }

        $return['code'] = true;
        $return['message'] = '操作成功，更新条数：'.count($balance_year_list);
        return $return;
    }

    /**
     * 根据 是否更新 重新计算 日维度期初期末
     * @param $supplier_code
     * @param $purchase_name
     * @param $start_statistic_time
     * @return array
     */
    public function update_balance_day_opening_and_ending_balance($supplier_code,$purchase_name,$start_statistic_time){
        $return = ['code' => false,'message' => '操作失败'];

        // 待计算的数据
        $waiting_update_query = "SELECT *
        FROM {$this->table_name_day}
        WHERE supplier_code='{$supplier_code}' AND purchase_name='{$purchase_name}'
        AND statistic_time>='{$start_statistic_time}' AND statistic_time<='{$this->yesterday}'
        ORDER BY statistic_time ASC";
        $waiting_update_list = $this->purchase_db->query($waiting_update_query)->result_array();


        // 上期 期末
        $last_ending_balance_query = "SELECT *
        FROM {$this->table_name_day}
        WHERE supplier_code='{$supplier_code}' AND purchase_name='{$purchase_name}'
        AND statistic_time<'{$start_statistic_time}'
        ORDER BY statistic_time DESC LIMIT 1";
        $last_ending_balance_list = $this->purchase_db->query($last_ending_balance_query)->row_array();


        $last_ending_balance     = isset($last_ending_balance_list['c_ending_balance']) ? $last_ending_balance_list['c_ending_balance'] : 0;
        $current_opening_balance = null;
        $current_ending_balance  = null;
        // 更新期初、期末
        foreach($waiting_update_list as $waiting_update_item){
            // 本期期初 ：本期期初=上期期末
            $current_opening_balance = $last_ending_balance;


            // 期末余额=期初金额+入库应付+其他应付+本期已付+退款金额+报损额+调整金额
            $current_ending_balance = $current_opening_balance
                + $waiting_update_item['c_instock_money']
                + $waiting_update_item['c_other_money']
                + $waiting_update_item['c_paid_money']
                + $waiting_update_item['c_refunded_money']
                + $waiting_update_item['c_loss_money']
                + $waiting_update_item['c_adjust_money'];
            $current_ending_balance = format_price($current_ending_balance);

            $last_ending_balance = $current_ending_balance;// 本期期末 -> 下期期初

            $update_arr = [
                'c_opening_balance' => $current_opening_balance,// 本期期初
                'c_ending_balance'  => $current_ending_balance,// 本期期末
                'is_updated'        => 0,// 更新的数据 已经处理了，标记为未更新
                'update_time'       => $this->now_time,
            ];

            $this->purchase_db->where('id',$waiting_update_item['id'])->update($this->table_name_day,$update_arr);
        }

        $return['code'] = true;
        $return['message'] = '操作成功';
        return $return;
    }

    /**
     * 根据 是否更新 重新计算 月维度期初期末
     * @param $supplier_code
     * @param $purchase_name
     * @param $start_statistic_time
     * @return array
     */
    public function update_balance_month_opening_and_ending_balance($supplier_code,$purchase_name,$start_statistic_time){
        $return = ['code' => false,'message' => '操作失败'];

        // 待计算的数据
        $waiting_update_query = "SELECT *
        FROM {$this->table_name_month}
        WHERE supplier_code='{$supplier_code}' AND purchase_name='{$purchase_name}'
        AND statistic_time>='{$start_statistic_time}' AND statistic_time<='{$this->last_month}'
        ORDER BY statistic_time ASC";
        $waiting_update_list = $this->purchase_db->query($waiting_update_query)->result_array();


        // 上期 期末
        $last_ending_balance_query = "SELECT *
        FROM {$this->table_name_month}
        WHERE supplier_code='{$supplier_code}' AND purchase_name='{$purchase_name}'
        AND statistic_time<'{$start_statistic_time}'
        ORDER BY statistic_time DESC LIMIT 1";
        $last_ending_balance_list = $this->purchase_db->query($last_ending_balance_query)->row_array();


        $last_ending_balance     = isset($last_ending_balance_list['c_ending_balance']) ? $last_ending_balance_list['c_ending_balance'] : 0;
        $current_opening_balance = null;
        $current_ending_balance  = null;
        // 更新期初、期末
        foreach($waiting_update_list as $waiting_update_item){
            // 本期期初 ：本期期初=上期期末
            $current_opening_balance = $last_ending_balance;


            // 期末余额=期初金额+入库应付+其他应付+本期已付+退款金额+报损额+调整金额
            $current_ending_balance = $current_opening_balance
                + $waiting_update_item['c_instock_money']
                + $waiting_update_item['c_other_money']
                + $waiting_update_item['c_paid_money']
                + $waiting_update_item['c_refunded_money']
                + $waiting_update_item['c_loss_money']
                + $waiting_update_item['c_adjust_money'];
            $current_ending_balance = format_price($current_ending_balance);

            $last_ending_balance = $current_ending_balance;// 本期期末 -> 下期期初

            $update_arr = [
                'c_opening_balance' => $current_opening_balance,// 本期期初
                'c_ending_balance'  => $current_ending_balance,// 本期期末
                'is_updated'        => 0,// 更新的数据 已经处理了，标记为未更新
                'update_time'       => $this->now_time,
            ];

            $this->purchase_db->where('id',$waiting_update_item['id'])->update($this->table_name_month,$update_arr);
        }

        $return['code'] = true;
        $return['message'] = '操作成功';
        return $return;
    }

    /**
     * 根据 是否更新 重新计算 年维度期初期末
     * @param $supplier_code
     * @param $purchase_name
     * @param $start_statistic_time
     * @return array
     */
    public function update_balance_year_opening_and_ending_balance($supplier_code,$purchase_name,$start_statistic_time){
        $return = ['code' => false,'message' => '操作失败'];

        // 待计算的数据
        $waiting_update_query = "SELECT *
        FROM {$this->table_name_year}
        WHERE supplier_code='{$supplier_code}' AND purchase_name='{$purchase_name}'
        AND statistic_time>='{$start_statistic_time}' AND statistic_time<='{$this->year}'
        ORDER BY statistic_time ASC";
        $waiting_update_list = $this->purchase_db->query($waiting_update_query)->result_array();


        // 上期 期末
        $last_ending_balance_query = "SELECT *
        FROM {$this->table_name_year}
        WHERE supplier_code='{$supplier_code}' AND purchase_name='{$purchase_name}'
        AND statistic_time<'{$start_statistic_time}'
        ORDER BY statistic_time DESC LIMIT 1";
        $last_ending_balance_list = $this->purchase_db->query($last_ending_balance_query)->row_array();


        $last_ending_balance     = isset($last_ending_balance_list['c_ending_balance']) ? $last_ending_balance_list['c_ending_balance'] : 0;
        $current_opening_balance = null;
        $current_ending_balance  = null;
        // 更新期初、期末
        foreach($waiting_update_list as $waiting_update_item){
            // 本期期初 ：本期期初=上期期末
            $current_opening_balance = $last_ending_balance;


            // 期末余额=期初金额+入库应付+其他应付+本期已付+退款金额+报损额+调整金额
            $current_ending_balance = $current_opening_balance
                + $waiting_update_item['c_instock_money']
                + $waiting_update_item['c_other_money']
                + $waiting_update_item['c_paid_money']
                + $waiting_update_item['c_refunded_money']
                + $waiting_update_item['c_loss_money']
                + $waiting_update_item['c_adjust_money'];
            $current_ending_balance = format_price($current_ending_balance);

            $last_ending_balance = $current_ending_balance;// 本期期末 -> 下期期初

            $update_arr = [
                'c_opening_balance' => $current_opening_balance,// 本期期初
                'c_ending_balance'  => $current_ending_balance,// 本期期末
                'is_updated'        => 0,// 更新的数据 已经处理了，标记为未更新
                'update_time'       => $this->now_time,
            ];

            $this->purchase_db->where('id',$waiting_update_item['id'])->update($this->table_name_year,$update_arr);
        }

        $return['code'] = true;
        $return['message'] = '操作成功';
        return $return;
    }

    /**
     * 如果供应商+采购主体本月不发生任何交易记录，但是上一次统计过程中【期末余额】不为0，那么本月进行统计的时候，也要生成相对应的数据
     */
    public function auto_padding_balance_month(){
        $second_month_before = date('Y-m',strtotime($this->last_month.'-01') - 86400 * 2);// 上个月的上个月

        // 上个月的上个月里面存在期末余额不为0的供应商  如果本年没有则要填充到上个月
        $balance_month_query = "SELECT *
            FROM {$this->table_name_month} 
            WHERE statistic_time='{$second_month_before}' 
            AND CONCAT(supplier_code,'-',purchase_name) NOT IN(
                SELECT CONCAT(supplier_code,'-',purchase_name) AS gys  FROM {$this->table_name_month} WHERE statistic_time='{$this->last_month}'
            )
            AND c_ending_balance <> 0
            GROUP BY supplier_code,purchase_name";

        $balance_month_list = $this->purchase_db->query($balance_month_query)->result_array();

        if($balance_month_list){
            try{
                $balance_month_value_tmp_list = [];
                foreach($balance_month_list as $balance_month_value){
                    $balance_month_value_tmp_list[] = [
                        'supplier_code'     => $balance_month_value['supplier_code'],
                        'supplier_name'     => $balance_month_value['supplier_name'],
                        'purchase_name'     => $balance_month_value['purchase_name'],
                        'occurrence_time'   => $this->now_time,
                        'update_time'       => $this->now_time,
                        'statistic_time'    => $this->last_month,
                        'c_opening_balance' => $balance_month_value['c_ending_balance'],
                        'c_instock_money'   => 0,
                        'c_other_money'     => 0,
                        'c_paid_money'      => 0,
                        'c_refunded_money'  => 0,
                        'c_loss_money'      => 0,
                        'c_adjust_money'    => 0,
                        'c_ending_balance'  => $balance_month_value['c_ending_balance'],
                        'is_calculated'     => 0,
                        'is_updated'        => 0,
                    ];

                    if(count($balance_month_value_tmp_list) > 500){
                        $this->purchase_db->insert_batch($this->table_name_month,$balance_month_value_tmp_list);
                        $balance_month_value_tmp_list = [];// 重置
                    }
                }

                if(count($balance_month_value_tmp_list) > 0){
                    $this->purchase_db->insert_batch($this->table_name_month,$balance_month_value_tmp_list);
                }

                $return['code']    = true;
                $return['message'] = '月维度自动填充处理成功：'.count($balance_month_list) .' 条';

            }catch(Exception $exception){
                $return['message'] = $exception->getMessage();
            }
        }else{
            $return['message'] = '月维度自动填充无需处理';
        }

        return $return;
    }

    /**
     * 如果供应商+采购主体本年不发生任何交易记录，但是上一次统计过程中【期末余额】不为0，那么本年进行统计的时候，也要生成相对应的数据
     */
    public function auto_padding_balance_year(){
        $second_year_before = strval(intval(date('Y',strtotime($this->last_year.'-01-01'))) - 1);// 前年

        // 前年里面存在期末余额不为0的供应商  如果本年没有则要填充到去年
        $balance_year_query = "SELECT *
            FROM {$this->table_name_year} 
            WHERE statistic_time='{$second_year_before}' 
            AND CONCAT(supplier_code,'-',purchase_name) NOT IN(
                SELECT CONCAT(supplier_code,'-',purchase_name) AS gys  FROM {$this->table_name_year} WHERE statistic_time='{$this->last_year}'
            )
            AND c_ending_balance <> 0
            GROUP BY supplier_code,purchase_name";

        $balance_year_list = $this->purchase_db->query($balance_year_query)->result_array();

        if($balance_year_list){
            try{
                $balance_year_tmp_list = [];
                foreach($balance_year_list as $balance_year_value){
                    $balance_year_tmp_list[] = [
                        'supplier_code'     => $balance_year_value['supplier_code'],
                        'supplier_name'     => $balance_year_value['supplier_name'],
                        'purchase_name'     => $balance_year_value['purchase_name'],
                        'occurrence_time'   => $this->now_time,
                        'update_time'       => $this->now_time,
                        'statistic_time'    => $this->last_year,
                        'c_opening_balance' => $balance_year_value['c_opening_balance'],
                        'c_instock_money'   => $balance_year_value['c_instock_money'],
                        'c_other_money'     => $balance_year_value['c_other_money'],
                        'c_paid_money'      => $balance_year_value['c_paid_money'],
                        'c_refunded_money'  => $balance_year_value['c_refunded_money'],
                        'c_loss_money'      => $balance_year_value['c_loss_money'],
                        'c_adjust_money'    => $balance_year_value['c_adjust_money'],
                        'c_ending_balance'  => $balance_year_value['c_ending_balance'],
                        'is_updated'        => 0,
                    ];

                    if(count($balance_year_tmp_list) > 500){
                        $this->purchase_db->insert_batch($this->table_name_year,$balance_year_tmp_list);
                        $balance_year_tmp_list = [];// 重置
                    }
                }

                if(count($balance_year_tmp_list) > 0){
                    $this->purchase_db->insert_batch($this->table_name_year,$balance_year_tmp_list);
                }

                $return['code']    = true;
                $return['message'] = '年维度自动填充处理成功：'.count($balance_year_list) .' 条';

            }catch(Exception $exception){
                $return['message'] = $exception->getMessage();
            }
        }else{
            $return['message'] = '年维度自动填充无需处理';
        }
        return $return;
    }
    //endregion


}