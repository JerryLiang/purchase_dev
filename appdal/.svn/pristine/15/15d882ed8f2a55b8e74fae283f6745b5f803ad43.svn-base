<?php if(!defined('BASEPATH')) exit('No direct script access allowed');
include_once APPPATH."core/MY_API_Controller.php";

/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-05-14
 * Time: 14:00
 */
class Supplier_balance_api extends MY_API_Controller {

    private $_trading_detail_update_time = '0000-00-00 00:00:00';
    private $_balance_day_update_time    = '0000-00-00 00:00:00';
    private $_balance_month_update_time  = '0000-00-00 00:00:00';
    private $_balance_year_update_time   = '0000-00-00 00:00:00';
    private $_trading_detail_max_id      = 1;
    private $_balance_day_max_id         = 1;
    private $_balance_month_max_id       = 1;
    private $_balance_year_max_id        = 1;

    private $_now_time = null;

    public function __construct(){
        parent::__construct();
        $this->load->model('statement/Supplier_balance_model');
        $this->load->model('statement/Supplier_balance_order_model');
        $this->load->model('statement/Supplier_trading_detail_model');
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');

        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $this->_now_time = date('Y-m-d H:i:s');

    }
    

    //region  数据初始化 - 非计划任务，上线初始化之后不能再执行
    /**
     * 1、初始化删除 所有统计的数据
     * @link supplier_balance_api/init_statistic_scope_reset?reset=reset
     */
    public function init_statistic_scope_reset(){
        $reset = $this->input->get_post('reset');

        if($reset == 'reset'){
            $this->rediss->deleteData('init_statistic_scope_completed');
            $this->rediss->deleteData('init_statistic_scope_list');

            for($i = 0;$i < 10; $i ++){// 删除数据，每次删除 1 万条
                $this->db->delete('pur_supplier_trading_detail','1=1',10000);
                $this->db->delete('pur_supplier_accounts_payable_balance','1=1',10000);
                $this->db->delete('pur_supplier_accounts_payable_balance_month','1=1',10000);
                $this->db->delete('pur_supplier_accounts_payable_balance_year','1=1',10000);
            }

            echo '数据删除成功';exit;
        }else{
            echo '请求参数异常';exit;
        }
    }

    /**
     * 2、初始化数据 核算日期范围
     * @link supplier_balance_api/init_statistic_scope_list
     */
    public function init_statistic_scope_list(){
        $init_statistic_scope_completed = $this->rediss->getData('init_statistic_scope_completed');
        if($init_statistic_scope_completed){
            echo  "核算日期范围添加成功-重复添加";exit;
        }

        for($date = '2019-01-01';$date < date('Y-m-d'); $date = date('Y-m-d',strtotime($date) + 86400)){
            $this->rediss->lpushData('init_statistic_scope_list',$date);
        }

        $this->rediss->setData('init_statistic_scope_completed',1);

        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_init_statistic_scope_completed');
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_init_statistic_scope_list');

        echo "核算日期范围添加成功";exit;
    }

    /**
     * 3、核算数据初始化
     * @link supplier_balance_api/init_batch_to_trading_detail
     */
    public function init_batch_to_trading_detail(){
        $statistic_date = $this->rediss->rpopData('init_statistic_scope_list');
        if(empty($statistic_date)){
            echo '数据计算完成';exit;
        }

        echo $statistic_date;
        print_r("<br/><br/>\n\n");
        print_r("<br/><br/>\n\n");

        // 不分日期执行
        $result = $this->Supplier_trading_detail_model->instock_to_trading_detail();
        print_r("入库明细表 - 入库商品额<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");


        $result = $this->Supplier_trading_detail_model->pay_to_trading_detail();
        print_r("应付款管理 - 其他费用&付款<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");


        // 分日期执行
        $result = $this->Supplier_trading_detail_model->loss_to_trading_detail($statistic_date);
        print_r("报损信息管理 - 报损商品额<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");


        $result = $this->Supplier_trading_detail_model->refund_to_trading_detail($statistic_date);
        print_r("应收款管理-普通收款 - 普通退款<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");


        $result = $this->Supplier_trading_detail_model->instock_refund_to_trading_detail($statistic_date);
        print_r("应收款管理-入库退货 - 退货退款&入库退货<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");


        $result = $this->Supplier_trading_detail_model->supplier_balance_order_to_trading_detail($statistic_date);
        print_r("余额调整单 - 调整<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");


        print_r("<br/><br/>\n\n");
        echo 'sss';exit;
    }

    /**
     * 4、自动填充月维度数据
     *      month=2019-09 表示填充 在 2019-08 月份期末余额不为0，但不存在 2019-09 月维度记录的数据，会生成统计时间为 2019-09 月份的数据
     *      month=2019-10 表示填充 在 2019-09 月份期末余额不为0，但不存在 2019-10 月维度记录的数据，会生成统计时间为 2019-10 月份的数据
     *
     * @link supplier_balance_api/init_batch_to_auto_padding_month?month=2019-09
     */
    public function init_batch_to_auto_padding_month(){
        $month = $this->input->get_post('month');

        echo '自动填充月维度数据';
        print_r("<br/><br/>\n\n");
        print_r("<br/><br/>\n\n");

        if($month){
            echo $month;
            print_r("<br/>\n");

            $this->Supplier_balance_model->last_month = $month;
            $result = $this->Supplier_balance_model->auto_padding_balance_month();

            print_r("填充结果<br/>\n");
            print_r($result);
            print_r("<br/><br/>\n\n");

        }else{
            $list_month = [
                '2019-01',
                '2019-02',
                '2019-03',
                '2019-04',
                '2019-05',
                '2019-06',
                '2019-07',
                '2019-08',
                '2019-09',
                '2019-10',
                '2019-11',
                '2019-12',
                '2020-01',
                '2020-02',
                '2020-03',
                '2020-04',
                '2020-05',
                '2020-06',
                '2020-07',
                '2020-08',
//                '2020-09',
//                '2020-10',
//                '2020-11',
//                '2020-12',
            ];


            foreach($list_month as $month_value){

                echo $month_value;
                print_r("<br/>\n");

                $this->Supplier_balance_model->last_month = $month_value;
                $result = $this->Supplier_balance_model->auto_padding_balance_month();

                print_r("填充结果<br/>\n");
                print_r($result);
                print_r("<br/><br/>\n\n");
            }
        }

        print_r("<br/><br/>\n\n");
        echo 'sss';exit;
    }

    /**
     * 5、自动填充年维度数据
     *      year=2019 表示填充：在 2018 年期末余额不为0，但不存在 2019 年维度记录的数据，会生成统计时间为 2019 年的数据
     *      year=2020 表示填充：在 2019 年期末余额不为0，但不存在 2020 年维度记录的数据，会生成统计时间为 2020 年的数据
     *
     *  执行时间规定：
     *      假如当前年份是 2020，由于年维度只能计算前一年的数据，所以 year 必须小于 2020，否则则会生成错误数据
     *
     * @link supplier_balance_api/init_batch_to_auto_padding_year?year=2019
     */
    public function init_batch_to_auto_padding_year(){
        $year = $this->input->get_post('year');

        echo '自动填充年维度数据';
        print_r("<br/><br/>\n\n");
        print_r("<br/><br/>\n\n");

        if($year){
            echo $year;
            print_r("<br/>\n");

            $this->Supplier_balance_model->last_year = $year;
            $result = $this->Supplier_balance_model->auto_padding_balance_year();

            print_r("填充结果<br/>\n");
            print_r($result);
            print_r("<br/><br/>\n\n");

        }else{
            echo '参数缺失';
        }

        print_r("<br/><br/>\n\n");
        echo 'sss';exit;
    }


    //endregion




    //region  生成各模块的交易明细记录
    /**
     * PLAN - 1、入库明细表 - 入库商品额
     *      采购单在入库之后，只统计付款状态非“无需付款”的数据，以入库批次号的维度生成一条交易记录
     *
     *      每日增量：10000 ~ 20000
     *      执行时间：每天执行一次
     *
     * @link supplier_balance_api/instock_to_trading_detail
     */
    public function instock_to_trading_detail(){
        $result = $this->Supplier_trading_detail_model->instock_to_trading_detail();
        print_r("PLAN - 1、入库明细表 - 入库商品额<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        echo 'sss';exit;
    }

    /**
     * PLAN - 2、应付款管理 - 其他费用&付款
     *      申请单的状态为“已付款”的时候，以请款单号的维度生成一条交易记录
     *
     *      每日增量：< 5000
     *      执行时间：每天执行一次
     *
     * @link supplier_balance_api/pay_to_trading_detail
     */
    public function pay_to_trading_detail(){
        $result = $this->Supplier_trading_detail_model->pay_to_trading_detail();
        print_r("PLAN - 2、应付款管理 - 其他费用&付款<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        echo 'sss';exit;
    }

    /**
     * PLAN - 3
     *      3.1 报损信息管理 - 报损商品额
     *      3.2 应收款管理-普通收款 - 普通退款
     *      3.3 应收款管理-入库退货 - 退货退款&入库退货
     *      3.4 余额调整单 - 调整
     *
     *      每日增量：< 1000
     *      执行时间：每天执行一次
     *      执行规则说明：statistic_date=2020-09-10，表示计算交易日期为 2020-09-10 的记录，用于处理跑漏的数据，自动过滤重复的数据，所以可以重复执行
     *
     * @link supplier_balance_api/other_to_trading_detail?statistic_date=2020-09-10
     */
    public function other_to_trading_detail(){
        $statistic_date = $this->input->get_post('statistic_date');

        if(empty($statistic_date)) $statistic_date = date('Y-m-d', strtotime(" -1 days"));// 默认前一天

        echo $statistic_date;
        print_r("<br/><br/>\n\n");
        print_r("<br/><br/>\n\n");

        /*
         * 入库明细表 - 入库商品额
         */
        for($i = 0; $i < 20;$i ++){
            $result = $this->Supplier_trading_detail_model->instock_to_trading_detail();
            if(empty($result['code']) or $result['message'] == '未获取到数据'){
                print_r("PLAN - 1、循环执行完毕<br/>\n");
                break;
            }
            print_r("PLAN - 1、第 {$i} 次运行，入库明细表 - 入库商品额<br/>\n");
            print_r($result);
            print_r("<br/>\n");
        }
        print_r("<br/><br/>\n\n");

        /*
         * 应付款管理 - 其他费用&付款
         */
        for($i = 0; $i < 20;$i ++){
            $result = $this->Supplier_trading_detail_model->pay_to_trading_detail();
            if(empty($result['code']) or $result['message'] == '未获取到数据'){
                print_r("PLAN - 2、循环执行完毕<br/>\n");
                break;
            }
            print_r("PLAN - 2、第 {$i} 次运行，应付款管理 - 其他费用&付款<br/>\n");
            print_r($result);
            print_r("<br/>\n");
        }
        print_r("<br/><br/>\n\n");

        /*
         * 报损信息管理 - 报损商品额
         * 报损申请为“已通过”的时候，以报损申请编码的维度生成一条交易记录
         */
        $result = $this->Supplier_trading_detail_model->loss_to_trading_detail($statistic_date);
        print_r("PLAN - 3.1、报损信息管理 - 报损商品额<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        /*
         * 应收款管理-普通收款 - 普通退款
         * 当收款状态变为“已收款”的时候，以申请编号为维度生成一条交易记录
         */
        $result = $this->Supplier_trading_detail_model->refund_to_trading_detail($statistic_date);
        print_r("PLAN - 3.2、应收款管理-普通收款 - 普通退款<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        /*
         * 应收款管理-入库退货 - 退货退款&入库退货
         * 当收款状态变为“财务已收款”的时候，以退货单号为维度生成一条交易记录
         */
        $result = $this->Supplier_trading_detail_model->instock_refund_to_trading_detail($statistic_date);
        print_r("PLAN - 3.3、应收款管理-入库退货 - 退货退款&入库退货<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        /*
         * 余额调整单 - 调整
         * 余额调整单的状态为“审核通过”之后，以余额申请单ID生成一条交易记录
         */
        $result = $this->Supplier_trading_detail_model->supplier_balance_order_to_trading_detail($statistic_date);
        print_r("PLAN - 3.4、余额调整单 - 调整<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");


        /*
         * 线下收款单 - 收款
         * 财务应收款页面,点击"确认收款"通过后,推送数据到"报表管理-供应商余额汇总表-交易明细"页面
         */
        $result = $this->Supplier_trading_detail_model->offline_receipt_to_trading_detail($statistic_date);
        print_r("PLAN - 3.5、线下收款单 - 收款<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        echo 'sss';exit;
    }
    //endregion



    //region 新增加 日/月/年 维度记录
    /**
     * PLAN - 4、新增的交易明细 汇总到 日/月/年 维度
     * @link supplier_balance_api/trading_detail_to_deal
     */
    public function trading_detail_to_deal(){

        for($i = 0; $i < 5;$i ++){
            $result = $this->Supplier_balance_model->trading_detail_to_deal();// 每 1000 条 耗时，6-10秒
            if(empty($result['code']) or $result['message'] == '交易明细未获取到数据'){
                print_r("PLAN - 4、循环执行完毕<br/>\n");
                break;
            }
            print_r("PLAN - 4、第 {$i} 次运行，新增的交易明细 汇总到 日/月/年 维度末<br/>\n");
            print_r($result);
            print_r("<br/>\n");

            sleep(2);
        }
        print_r("<br/><br/>\n\n");

        echo 'sss';exit;
    }

    /**
     * 计算
     *      新增的日维度 汇总到 月维度
     *      新增的月维度 汇总到 年维度
     *      日维度期初期末余额，余额维度期初期末余额，年维度期初期末余额
     *      月维度自动填充处理
     *      年维度自动填充处理
     * @link supplier_balance_api/calculate_current_balance
     */
    public function calculate_current_balance(){
        // 日维度 每天都计算一次
        for($i = 0; $i < 3;$i ++){
            $result = $this->Supplier_balance_model->calculate_balance_day_opening_and_ending_balance();
            if(empty($result['code']) or $result['message'] == '没有需要计算的数据'){
                print_r("PLAN - 5、循环执行完毕<br/>\n");
                break;
            }
            print_r("PLAN - 5、第 {$i} 次运行，根据 是否更新 重新计算 日维度期初期末<br/>\n");
            print_r($result);
            print_r("<br/><br/>\n\n");
        }

        $current_date_the_day = intval(date('d'));// 当前月份的第几天
        $current_date_the_month = intval(date('m'));// 当前月份

        // 月维度，每个月的 1,2,3 号运行
        if(in_array($current_date_the_day,[1,2,3])){
            for($i = 0; $i < 10;$i ++){
                $this->balance_day_to_balance_month();
            }
        }else{// 平常每天运行一次，1000条数据，处理的是查缺补漏的数据
            $this->balance_day_to_balance_month();
        }

        // 年维度，每年的第 1 月的 1,2,3 号运行
        if(in_array($current_date_the_day,[1,2,3]) and in_array($current_date_the_month,[1])){
            for($i = 0; $i < 10;$i ++){
                $this->balance_month_to_balance_year();
            }
        }else{
            $this->balance_month_to_balance_year();
        }

        echo 'sss';exit;
    }

    /**
     * PLAN - 5、根据 是否更新 重新计算 日维度期初期末
     * @link supplier_balance_api/calculate_balance_day_opening_and_ending_balance
     */
    public function calculate_balance_day_opening_and_ending_balance(){
        $result = $this->Supplier_balance_model->calculate_balance_day_opening_and_ending_balance();
        print_r("PLAN - 5、根据 是否更新 重新计算 日维度期初期末<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        echo 'sss';exit;
    }

    /**
     * PLAN - 6
     *      6.1 新增的日维度 汇总到 月维度
     *      6.2 根据 是否更新 重新计算 月维度期初期末
     *      6.3 月维度自动填充（生成当前时间的上个月份的数据）处理
     *
     * @link supplier_balance_api/balance_day_to_balance_month
     */
    public function balance_day_to_balance_month(){
        // 新增的日维度 汇总到 月维度
        $result = $this->Supplier_balance_model->balance_day_to_balance_month();
        print_r("PLAN - 6.1、新增的日维度 汇总到 月维度<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        // 根据 是否更新 重新计算 月维度期初期末
        $result = $this->Supplier_balance_model->calculate_balance_month_opening_and_ending_balance();
        print_r("PLAN - 6.2、根据 是否更新 重新计算 月维度期初期末<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        echo 'sss';
    }

    /**
     * PLAN - 7
     *      7.1 新增的月维度 汇总到 年维度
     *      7.2 根据 是否更新 重新计算 年维度期初期末
     *      7.3 年维度自动填充（生成当前年份的上个年份的数据）处理
     *
     * @link supplier_balance_api/balance_month_to_balance_year
     */
    public function balance_month_to_balance_year(){
        // 新增的月维度 汇总到 年维度
        $result = $this->Supplier_balance_model->balance_month_to_balance_year();
        print_r("PLAN - 7.1、新增的月维度 汇总到 年维度<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        // 根据 是否更新 重新计算 年维度期初期末
        $result = $this->Supplier_balance_model->calculate_balance_year_opening_and_ending_balance();
        print_r("PLAN - 7.2、根据 是否更新 重新计算 年维度期初期末<br/>\n");
        print_r($result);
        print_r("<br/><br/>\n\n");

        echo 'sss';
    }
    //endregion


    /**
     * 数据的存入MondoDB
     * supplier_balance_api/balance_to_mongodb_for_search
     * @author Jolon
     */
    public function balance_to_mongodb_for_search(){
        set_time_limit(0);
        $this->load->library('mongo_db');

        $update_time_list = $this->mongo_db->where(['pKey' => 'balanceToMongodbForSearch'])->get('keyRecordsList');
        if(!isset($update_time_list[0])){
            $insert_data                               = [];
            $insert_data['pKey']                       = 'balanceToMongodbForSearch';
            $insert_data['trading_detail_update_time'] = '0000-00-00 00:00:00';
            $insert_data['balance_day_update_time']    = '0000-00-00 00:00:00';
            $insert_data['balance_month_update_time']  = '0000-00-00 00:00:00';
            $insert_data['balance_year_update_time']   = '0000-00-00 00:00:00';
            $insert_data['trading_detail_max_id']      = 1;
            $insert_data['balance_day_max_id']         = 1;
            $insert_data['balance_month_max_id']       = 1;
            $insert_data['balance_year_max_id']        = 1;
            $this->mongo_db->insert('keyRecordsList', $insert_data);
        }

        if(isset($update_time_list[0]) and $update_time_list[0]){
            $this->_trading_detail_update_time = $update_time_list[0]->trading_detail_update_time;
            $this->_balance_day_update_time    = $update_time_list[0]->balance_day_update_time;
            $this->_balance_month_update_time  = $update_time_list[0]->balance_month_update_time;
            $this->_balance_year_update_time   = $update_time_list[0]->balance_year_update_time;
            $this->_trading_detail_max_id      = $update_time_list[0]->trading_detail_max_id;
            $this->_balance_day_max_id         = $update_time_list[0]->balance_day_max_id;
            $this->_balance_month_max_id       = $update_time_list[0]->balance_month_max_id;
            $this->_balance_year_max_id        = $update_time_list[0]->balance_year_max_id;
        }

        $this->to_mongodb('trading_detail','pur_supplier_trading_detail',$this->_trading_detail_max_id,$this->_trading_detail_update_time);
        $this->to_mongodb('balance_day','pur_supplier_accounts_payable_balance',$this->_balance_day_max_id,$this->_balance_day_update_time);
        $this->to_mongodb('balance_month','pur_supplier_accounts_payable_balance_month',$this->_balance_month_max_id,$this->_balance_month_update_time);
        $this->to_mongodb('balance_year','pur_supplier_accounts_payable_balance_year',$this->_balance_year_max_id,$this->_balance_year_update_time);

        echo 'sss';exit;
    }

    /**
     * 数据的存入MondoDB
     * @param $key_word
     * @param $table_name
     * @param $max_id
     * @param $last_update_time
     * @return bool
     */
    public function to_mongodb($key_word,$table_name,$max_id,$last_update_time){
        // 数据同步到 MongoDB
        $trading_detail_list = $this->db->select('*')
            ->where('id >',$max_id)
            ->where('update_time >=',$last_update_time)
            ->where('update_time <=',$this->_now_time)
            ->order_by('id asc')
            ->get($table_name,10000)
            ->result_array();

        print_r($this->db->last_query());
        print_r("<br/>\n");
        $mongodb_doc_name = str_replace(' ','',ucwords(str_replace('_',' ',$table_name)));

        print_r($table_name ." 操作结果<br/>\n");
        if(empty($trading_detail_list)){
            print_r(" 没有变更的数据<br/>\n");

            $update_arr = [
                $key_word.'_update_time' => $this->_now_time,
                $key_word.'_max_id' => 0,
            ];
            $res = $this->mongo_db->where(['pKey' => 'balanceToMongodbForSearch'])->update('keyRecordsList',$update_arr);
        }else{
            print_r(count($trading_detail_list)." 条数据<br/>\n");
            $count = 0;
            // 数据类型强制转换
            foreach($trading_detail_list as $key => &$value){
                foreach($value as $k => $v){
                    if($k == 'statistic_time') continue;
                    if(is_numeric($v)){// 小数或整数
                        if(stripos($v,'.') !== false){// 小数
                            $value[$k] = floatval($v);
                        }else{// 整数
                            $value[$k] = intval($v);
                        }
                    }
                }
            }
            $trading_detail_list_arr = array_chunk($trading_detail_list,500);// 分批插入，每次500条
            $ids_list = array_column($trading_detail_list,'id');
            $max_id = max($ids_list);

            foreach($trading_detail_list_arr as $trading_detail_list){
                $count += $this->mongo_db->insertBatch($mongodb_doc_name, $trading_detail_list);
            }
            $update_arr = [
                $key_word.'_max_id' => $max_id,
            ];
            $res = $this->mongo_db->where(['pKey' => 'balanceToMongodbForSearch'])->update('keyRecordsList',$update_arr);
        }
        print_r($update_arr);
        print_r("<br/><br/>\n\n");
        print_r("<br/><br/>\n\n");

        return true;
    }


    /**
     * 本地造测试数据的方法
     */
    public function add_list(){
        $query = "INSERT INTO pur_supplier_trading_detail(order_no,relative_trading_num,supplier_code,supplier_name,purchase_name,trading_time,trading_type,trading_note,trading_money,trading_origin_money,create_time,is_calculated,update_time)
SELECT order_no,relative_trading_num,supplier_code,supplier_name,purchase_name,trading_time,trading_type,trading_note,trading_money,trading_origin_money,create_time,is_calculated,update_time
FROM pur_supplier_trading_detail LIMIT 30000";
        $this->db->query($query);

        $query = "INSERT INTO pur_supplier_accounts_payable_balance(supplier_code,supplier_name,purchase_name,occurrence_time,update_time,statistic_time,c_opening_balance,c_instock_money,c_other_money,c_paid_money,c_refunded_money,c_loss_money,c_adjust_money,c_ending_balance,is_calculated,is_updated)
SELECT supplier_code,supplier_name,purchase_name,occurrence_time,update_time,statistic_time,c_opening_balance,c_instock_money,c_other_money,c_paid_money,c_refunded_money,c_loss_money,c_adjust_money,c_ending_balance,is_calculated,is_updated
FROM pur_supplier_accounts_payable_balance LIMIT 30000";
        $this->db->query($query);

        $query = "INSERT INTO pur_supplier_accounts_payable_balance_month(supplier_code,supplier_name,purchase_name,occurrence_time,update_time,statistic_time,c_opening_balance,c_instock_money,c_other_money,c_paid_money,c_refunded_money,c_loss_money,c_adjust_money,c_ending_balance,is_calculated,is_updated)
SELECT supplier_code,supplier_name,purchase_name,occurrence_time,update_time,statistic_time,c_opening_balance,c_instock_money,c_other_money,c_paid_money,c_refunded_money,c_loss_money,c_adjust_money,c_ending_balance,is_calculated,is_updated
FROM pur_supplier_accounts_payable_balance_month LIMIT 30000";
        $this->db->query($query);

        $query = "INSERT INTO pur_supplier_accounts_payable_balance_year(supplier_code,supplier_name,purchase_name,occurrence_time,update_time,statistic_time,c_opening_balance,c_instock_money,c_other_money,c_paid_money,c_refunded_money,c_loss_money,c_adjust_money,c_ending_balance,is_updated)
SELECT supplier_code,supplier_name,purchase_name,occurrence_time,update_time,statistic_time,c_opening_balance,c_instock_money,c_other_money,c_paid_money,c_refunded_money,c_loss_money,c_adjust_money,c_ending_balance,is_updated
FROM pur_supplier_accounts_payable_balance_year LIMIT 30000";
        $this->db->query($query);

        echo 'sss';exit;
    }
}
