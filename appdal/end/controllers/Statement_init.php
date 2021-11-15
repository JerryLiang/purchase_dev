<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * 对账单功能数据初始化操作
 * User: Jolon
 * Date: 2020/05/06
 */

class Statement_init extends MY_API_Controller{

    private $operator_key = null;

    public function __construct(){
        parent::__construct();

        $this->load->model('statement/Charge_against_records_model','chargeARModel');
        $this->load->model('statement/Charge_against_surplus_model','chargeASModel');
        $this->load->model('statement/Purchase_inventory_items_model','purchaseIIModel');

    }

    private function getAvailablePurchase($purchase_numbers){

        $purchase_numbers = $this->chargeARModel->purchase_db->select('purchase_number')
            ->where_in('purchase_number',$purchase_numbers)
            ->where_not_in('purchase_order_status',[// 以下状态不需要重新计算
                PURCHASE_ORDER_STATUS_WAITING_QUOTE,
                PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT,
                PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER,
                PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND
            ])
            ->get('purchase_order')
            ->result_array();

        return $purchase_numbers;
    }

    /**
     * 入库记录 刷新冲销剩余、自动冲销（ <<< 从入库明细表同步数据到核销入库明细表）
     * @link statement_init/refresh_charge_surplus
     */
    public function refresh_charge_surplus(){
        set_time_limit(0);
        $start_time = time();

        $is_init = $this->input->get_post('is_init');

        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();//创建消息队列对象
        $mq->setQueueName('STATEMENT_WARE_RECORD_REFRESH');//设置参数
        $mq->setExchangeName('STATEMENT_ORDER');
        $mq->setRouteKey('SO_REFRESH_FOR_001');
        $mq->setType(AMQP_EX_TYPE_DIRECT);

        $queue_obj = $mq->getQueue();
        $row_count = $queue_obj->declareQueue();// 获得总消息数
        $row_count = ($row_count <= 1)? $row_count : 1;// 每次最多执行 100个

        $data = NULL;
        $po_count = 0;
        $skip_count = 0;// 读取数据异常次数控制器

        // 任务一、任务二、任务三 是根据时间窗口设计的简单的限流算法
        // 任务一：分配时间 30 秒
        for($i = 0; true ;$i ++){
            $envelope = $queue_obj->get();// 从队列中检索下一条消息

            if ($envelope){
                $data = $envelope->getBody();
                if(empty($data)){
                    $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除

                    $skip_count ++;
                    if($skip_count > 3){
                        break;
                    }else{
                        continue;
                    }
                }

                $data = json_decode($data,true);
                sleep(5);// 事务：数据存入 MQ 后还没来得及 提交，就读取入库记录失败

                if($data){
                    $purchase_number_list = array_unique(array_column($data,'purchase_number'));
                    $purchase_number_list = array_chunk($purchase_number_list,50);
                    foreach($purchase_number_list as $purchase_numbers){
                        $purchase_numbers = $this->getAvailablePurchase($purchase_numbers);
                        if(empty($purchase_numbers)) continue;

                        $purchase_numbers = array_column($purchase_numbers,'purchase_number');

                        $this->chargeASModel->recalculate_surplus($purchase_numbers,1);// 刷新采购单冲销汇总

                        $po_count += count($purchase_numbers);
                    }

                    foreach($data as $value_item){
                        $instock_batch   = $value_item['instock_batch'];
                        $this->chargeASModel->recalculate_inventory_item_surplus($instock_batch);
                    }

                    if(isset($is_init) and $is_init == 'yes'){// 数据同步初始化时 自动冲销
                        foreach($data as $value_item){
                            $purchase_number = $value_item['purchase_number'];
                            $instock_batch   = $value_item['instock_batch'];

                            $this->chargeARModel->check_inventory_item_charge_against([0 => $purchase_number.'_'.$instock_batch]);// 自动冲销
                        }
                    }
                }

                $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除
            }else{

                $skip_count ++;
                if($skip_count > 3){
                    break;
                }else{
                    continue;
                }
            }

            if(time() - $start_time >= 30){// 本次任务最多执行 30 秒
                break;
            }
        }

        $mq->disconnect();

        if($po_count == 0 or $row_count == 0){
            echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_001 >>> 没有需要操作的数据<br/>\t\n";
        }else{
            echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_001 >>> 本次执行：".$po_count."个<br/>\t\n";
        }

        // 任务二：分配时间 15 秒
        $start_time = time();
        while(1){
            $result2 =  $this->refresh_charge_surplus_two();
            if($result2 === false or time() - $start_time > 15){
                break;
            }
        }

        // 任务三：分配时间 15 秒
        $start_time = time();
        while(1){
            $result3 = $this->refresh_charge_surplus_third();
            if($result3 === false or time() - $start_time > 15){
                break;
            }
        }

        $this->refresh_charge_surplus_fourthly();

        // 设置付款备注-不想添加计划任务 就写这里哈
        $this->chargeARModel->purchase_db->query("UPDATE pur_purchase_order_pay SET finance_report_remark=payment_notice WHERE pay_status=51 AND finance_report_remark='' AND payment_notice <>'' ");


        // 合理安排执行时间
        $this->refresh_calc_pay_time_paid_service();

        echo 'All success!!!!';
        exit;
    }


    /**
     * 采购单状态变更
     * @link statement_init/refresh_charge_surplus_two
     */
    public function refresh_charge_surplus_two(){
        set_time_limit(0);

        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();//创建消息队列对象
        $mq->setQueueName('STATEMENT_CHANGE_STATUS_REFRESH');//设置参数
        $mq->setExchangeName('STATEMENT_CHANGE_STATUS');
        $mq->setRouteKey('SO_REFRESH_FOR_002');
        $mq->setType(AMQP_EX_TYPE_DIRECT);

        $queue_obj = $mq->getQueue();
        $row_count = $queue_obj->declareQueue();// 获得总消息数
        $row_count = ($row_count <= 50)? $row_count : 50;// 每次最多执行 100个
        if($row_count <= 0){
            echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_002 >>> 没有待处理的数据<br/>\t\n";
            return false;
        }

        $data           = NULL;
        $skip_count     = 0;// 读取数据异常次数控制器
        $success_count  = 0;// 操作成功个数

        for($i = 0; $i < $row_count ;$i ++){
            $envelope = $queue_obj->get();// 从队列中检索下一条消息

            if ($envelope){
                $data = $envelope->getBody();
                if(empty($data)){
                    $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除

                    $skip_count ++;
                    if($skip_count > 3){
                        break;
                    }else{
                        continue;
                    }
                }

                $data = json_decode($data,true);

                // 事务：数据存入 MQ 后还没来得及 提交，就读取入库记录失败
                // 没有设置时间 或设置了时间且超过了 5 秒钟 则刷新
                if( !isset($data['add_time']) or (isset($data['add_time']) and time() > $data['add_time'] + 5) ){
                    if(isset($data['purchase_number'])){
                        if(is_string($data['purchase_number'])){
                            $purchase_numbers = [$data['purchase_number']];
                        }else{
                            $purchase_numbers = $data['purchase_number'];
                        }
                        $purchase_numbers = $this->getAvailablePurchase($purchase_numbers);

                        if(!empty($purchase_numbers)){// 当前状态不需要刷新的跳过，删除消息
                            $purchase_numbers = array_column($purchase_numbers,'purchase_number');
                            $this->chargeASModel->recalculate_surplus($purchase_numbers,2);// 刷新采购单冲销汇总
                        }
                        $success_count ++;
                    }
                    $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除
                }
            }else{
                $skip_count ++;
                if($skip_count > 3){
                    break;
                }else{
                    continue;
                }
            }
        }

        $mq->disconnect();

        echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_002 >>> 本次执行：".$row_count."个，成功：".$success_count."个，跳过：".$skip_count."个<br/>\t\n";
        return true;
    }


    /**
     * 付款状态变更
     * @link statement_init/refresh_charge_surplus_third
     */
    public function refresh_charge_surplus_third(){
        set_time_limit(0);

        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();//创建消息队列对象
        $mq->setQueueName('STATEMENT_CHANGE_PAY_STATUS_REFRESH');//设置参数
        $mq->setExchangeName('STATEMENT_CHANGE_PAY_STATUS');
        $mq->setRouteKey('SO_REFRESH_FOR_003');
        $mq->setType(AMQP_EX_TYPE_DIRECT);

        $queue_obj = $mq->getQueue();
        $row_count = $queue_obj->declareQueue();// 获得总消息数
        $row_count = ($row_count <= 50)? $row_count : 50;// 每次最多执行 100个
        if($row_count <= 0){
            echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_003 >>> 没有待处理的数据<br/>\t\n";
            return false;
        }

        $data           = NULL;
        $skip_count     = 0;// 读取数据异常次数控制器
        $success_count  = 0;// 操作成功个数

        for($i = 0; $i < $row_count ;$i ++){
            $envelope = $queue_obj->get();// 从队列中检索下一条消息

            if ($envelope){
                $data = $envelope->getBody();
                if(empty($data)){
                    $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除

                    $skip_count ++;
                    if($skip_count > 3){
                        break;
                    }else{
                        continue;
                    }
                }

                $data = json_decode($data,true);

                // 事务：数据存入 MQ 后还没来得及 提交，就读取入库记录失败
                // 没有设置时间 或设置了时间且超过了 5 秒钟 则刷新
                if( !isset($data['add_time']) or (isset($data['add_time']) and time() > $data['add_time'] + 5) ){
                    if(isset($data['purchase_number'])){
                        if(is_string($data['purchase_number'])){
                            $purchase_numbers = [$data['purchase_number']];
                        }else{
                            $purchase_numbers = $data['purchase_number'];
                        }
                        $purchase_numbers = $this->getAvailablePurchase($purchase_numbers);

                        if(!empty($purchase_numbers)){// 当前状态不需要刷新的跳过，删除消息
                            $purchase_numbers = array_column($purchase_numbers,'purchase_number');
                            $this->chargeASModel->recalculate_surplus($purchase_numbers,2);// 刷新采购单冲销汇总
                        }
                        $success_count ++;
                    }
                    $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除
                }
            }else{
                $skip_count ++;
                if($skip_count > 3){
                    break;
                }else{
                    continue;
                }
            }
        }

        $mq->disconnect();

        echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_003 >>> 本次执行：".$row_count."个，成功：".$success_count."个，跳过：".$skip_count."个<br/>\t\n";
        return true;



        $this->operator_key = 'SO_REFRESH_FOR_003';
        $row_count = $this->rediss->set_scard($this->operator_key);// 获取集合元素的个数
        $row_count = ($row_count <= 200)? $row_count : 200;// 每次最多执行 200个

        if($row_count <= 0){
            echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_003 >>> 没有需要操作的数据<br/>\t\n";
            return false;
        }

        $data = NULL;

        $purchase_number_list = [];
        for($i = 0; $i < $row_count; $i++){
            $purchase_number        = $this->rediss->set_spop($this->operator_key);
            $purchase_number        = current($purchase_number);
            $purchase_number_list[] = $purchase_number;
        }

        if(empty($purchase_number_list)){
            echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_003 >>> 没有需要操作的数据<br/>\t\n";
            return false;
        }

        sleep(5);// 事务：数据存入 Redis 后还没来得及 提交，就读取已付款单失败

        $purchase_number_list = array_chunk(array_unique($purchase_number_list),50);

        $this->load->model('finance/purchase_order_pay_model');
        $this->load->model('finance/purchase_order_pay_type_model');
        foreach($purchase_number_list as $purchase_numbers){
            $purchase_numbers = $this->getAvailablePurchase($purchase_numbers);
            if(empty($purchase_numbers)) continue;

            $purchase_numbers = array_column($purchase_numbers,'purchase_number');

            $this->chargeASModel->recalculate_surplus($purchase_numbers,3);// 刷新采购单冲销汇总


//            // 刷新采购单已付金额
//            foreach($purchase_numbers as $purchase_number){
//                $paid_detail_data = $this->purchase_order_pay_model->get_pay_total_by_purchase_number_group_by_sku($purchase_number);
//
//                // 用有效的付款记录 去更新采购单明细 运费、优惠额、加工费
//                foreach($paid_detail_data as $paid_value){
//                    $update_data = [];
//                    if($paid_value['freight'] > 0){
//                        $update_data['freight'] = $paid_value['freight'];
//                    }
//                    if($paid_value['discount'] > 0){
//                        $update_data['discount'] = $paid_value['discount'];
//                    }
//                    if($paid_value['process_cost'] > 0){
//                        $update_data['process_cost'] = $paid_value['process_cost'];
//                    }
//                    if($paid_value['commission'] > 0){
//                        $update_data['commission'] = $paid_value['commission'];
//                    }
//                    if(!empty($update_data)){// 更新采购单明细 运费、优惠额、加工费
//                        $this->chargeARModel->purchase_db->where('purchase_number',$paid_value['purchase_number'])
//                            ->where('sku',$paid_value['sku'])
//                            ->update('purchase_order_items',$update_data,NULL,1);
//                    }
//                }
//            }
//
//            // 更新采购单金额总计
//            foreach($purchase_numbers as $purchase_number){
//                $this->purchase_order_pay_type_model->refresh_order_price($purchase_number);
//            }
        }

        echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_003 >>> 本次执行：".$row_count."个<br/>\t\n";
        return true;
    }

    /**
     * 处理 操作异常的数据
     * @link statement_init/refresh_charge_surplus_fourthly
     * @return bool
     */
    public function refresh_charge_surplus_fourthly(){
        // 七天前到半小时前的数据
        $start_date = date('Y-m-d H:i:s',strtotime('-7 days'));
        $end_date = date('Y-m-d H:i:s',time() - 1800);

        $already_running = $this->rediss->getData('refresh_charge_surplus_fourthly');
        if(empty($already_running)){
            // 每次只处理100个
            $sql = "SELECT A.`purchase_number`,C.total_instock_price,SUM(A.`instock_price`) AS instock_price
                FROM `pur_statement_warehouse_results` AS A
                LEFT JOIN `pur_purchase_order_charge_against_surplus` AS C ON C.purchase_number=A.purchase_number
                WHERE A.`purchase_number` IN(
                    SELECT purchase_number
                    FROM pur_statement_warehouse_results AS tmp 
                    WHERE tmp.`instock_date`>'{$start_date}' and tmp.`instock_date`<'{$end_date}'
                )
                GROUP BY A.`purchase_number`
                HAVING total_instock_price <> instock_price
                LIMIT 10000";
            $purchase_number_list = $this->chargeARModel->purchase_db->query($sql)->result_array();
            if($purchase_number_list){
                foreach($purchase_number_list as $purchase_numbers_value){
                    $purchase_number = $purchase_numbers_value['purchase_number'];

                    if(empty($purchase_number)) continue;
                    $this->chargeASModel->recalculate_surplus($purchase_number,2);// 刷新采购单冲销汇总
                }
                echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_004 >>> 本次执行：".count($purchase_number_list)."个<br/>\t\n";
            }else{
                $this->rediss->setData('refresh_charge_surplus_fourthly',3600*3);
                echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_004 >>> 已经全部处理完毕-1<br/>\t\n";
            }
        }else{
            echo date('Y-m-d H:i:s') . ' ' . "SO_REFRESH_FOR_004 >>> 已经全部处理完毕-2<br/>\t\n";
        }

        return true;
    }

    /**
     * 部分付款的 刷新采购单的应付款时间
     * @link /statement_init/refresh_calc_pay_time_paid_service?order_number=ABD9728470
     * @return bool
     */
    public function refresh_calc_pay_time_paid_service(){
        $this->load->model('calc_pay_time_model');
        $baseSet = $this->calc_pay_time_model->getSetParamData('PURCHASE_ORDER_PAY_TIME_SET');

        $len = $this->rediss->set_scard('calc_pay_time_paid_service');


        // 执行指定PO的更新
        $order_number = $this->input->get_post('order_number');
        if($order_number){
            $len = 1;
        }

        if (!$len) {
            echo date('Y-m-d H:i:s') . ' ' . "calc_pay_time_paid_service >>> 没有需要操作的数据<br/>\t\n";
            return true;
        }


        $num = $success_count = 0;

        //循环处理Redis数据
        while ($num < $len) {
            if($order_number){
                $purchase_number  = $order_number;
            }else{
                $purchase_number  = $this->rediss->set_spop('calc_pay_time_paid_service');
                if(empty($purchase_number)) break;
                $purchase_number  = isset($purchase_number[0])?$purchase_number[0]:'';
                if(empty($purchase_number)) continue;
            }

            $num ++;

            // 查询 满足订金请款的数据
            $querySql = "SELECT PO.purchase_number,POI.id as items_id,PO.account_type,PO.source,
                  MAX(IFNULL(WRM.instock_date,'0000-00-00 00:00:00')) AS instock_date,POI.plan_arrive_time
                FROM pur_purchase_order AS PO
                LEFT JOIN pur_purchase_order_items AS POI ON PO.purchase_number=POI.purchase_number
                LEFT JOIN pur_warehouse_results_main AS WRM ON POI.id=WRM.items_id
                WHERE PO.purchase_number='{$purchase_number}' 
                AND PO.account_type IN(30,31,32,17,18,19,40,39)
                GROUP BY POI.id;";

            $items_list = $this->db->query($querySql)->result_array();

            if($items_list){
                $accout_period_time = '0000-00-00';
                foreach($items_list as $item_value){
                    $newPayTime = $this->calc_pay_time_model->calc_pay_time_paid_service($baseSet, $item_value['account_type'],$item_value['source'],$item_value['instock_date'],$item_value['plan_arrive_time']);
                    if(isset($newPayTime['code']) && $newPayTime['code'] === true){
                        // 更新采购单明细的应付款时间（备货单维度）
                        $this->db->where('id',$item_value['items_id'])
                            ->update("purchase_order_items", ["need_pay_time" => $newPayTime['data']]);

                        if(strtotime($accout_period_time) < strtotime($newPayTime['data'])){
                            $accout_period_time = $newPayTime['data'];// 保留最大值
                        }
                    }else{
                        $this->db->where('id',$item_value['items_id'])
                            ->update("purchase_order_items", ["need_pay_time" => '0000-00-00']);
                    }
                }

                // 更新PO维度应付款时间
                $this->db->where('purchase_number',$purchase_number)
                    ->update("purchase_order_pay_type", ["accout_period_time" => $accout_period_time]);

                $success_count ++;
            }
        }

        echo date('Y-m-d H:i:s') . ' ' . "calc_pay_time_paid_service >>> 本次执行：".$num."个，成功：".$success_count."个<br/>\t\n";
        return true;
    }


    /**
     * 手动刷新采购单总金额
     * @link statement_init/refresh_order_price
     */
    public function refresh_order_price(){
        $purchase_numbers = $this->input->get_post('purchase_numbers');
        if(stripos($purchase_numbers,' ')){
            $purchase_numbers = explode(' ',$purchase_numbers);
        }

        $this->load->model('finance/Purchase_order_pay_type_model');

        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();//创建消息队列对象
        $mq->setQueueName('STATEMENT_CHANGE_STATUS_REFRESH');//设置参数
        $mq->setExchangeName('STATEMENT_CHANGE_STATUS');//构造存入数据
        $mq->setRouteKey('SO_REFRESH_FOR_002');
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        $mq->sendMessage(['purchase_number' => $purchase_numbers,'add_time' => time()]);// 二维数组，保持格式一致

        foreach($purchase_numbers as $purchase_number){
            $this->Purchase_order_pay_type_model->refresh_order_price($purchase_number);
        }

        echo '刷新成功';exit;
    }


    /**
     * 计划任务自动冲销 满足冲销条件的数据
     * statement_init/plan_auto_charge_surplus?page_size=500&offset_page=1
     */
    public function plan_auto_charge_surplus(){
        $redis_key = md5(__METHOD__);
        $isExists = $this->rediss->getData($redis_key);// 同时只能运行一个任务
        if($isExists){
            $this->error_json('The task is running!');
        }else{
            $this->rediss->setData($redis_key,'start_task',300);
        }

        set_time_limit(300);

        $page_size      = $this->input->get_post('page_size');
        $offset_page    = $this->input->get_post('offset_page');
        if(empty($page_size)) $page_size = 200;
        if(empty($offset_page)) $offset_page = 1;

        $length = $this->rediss->set_scard('SET_'.$redis_key);
        if($length <= 0){
            // 查询效率低：一次查询大量数据存入 redis 集合，每次读取 10000 个
            // 1、查找满足需求条件的
            // 2、入库记录剩余冲销金额>0 且 采购单的剩余可冲销商品金额>0
            $list = $this->chargeARModel->purchase_db
                ->select('ware.purchase_number,ware.instock_batch')
                ->from('statement_warehouse_results as ware')
                ->join('purchase_order_items as poi','ware.items_id=poi.id')
                ->join('purchase_suggest_map as psm','psm.purchase_number=poi.purchase_number and psm.sku=poi.sku')
                ->join('purchase_suggest as ps','ps.demand_number=psm.demand_number')
                ->join('purchase_order as po','poi.purchase_number=po.purchase_number')
                ->join('purchase_order_charge_against_surplus as cg_sur','po.purchase_number=cg_sur.purchase_number')
                ->where('ware.surplus_charge_against_amount >',0)
                ->where_in('ware.charge_against_status',[1,2]) // 3、入库批次冲销=未冲销、部分冲销；
                ->where('po.pay_status',PAY_PAID) // 1、付款状态=已付款；
                ->where_in('ps.suggest_order_status',[PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                        PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
                        PURCHASE_ORDER_STATUS_CANCELED]
                ) // 2、备货单状态=全部到货、部分到货不等待剩余到货、已作废订单；
                ->where('ware.instock_qty >',0) // 4、入库数量≠0；
                ->where('cg_sur.surplus_able_charge_against_money > ',0)
                ->limit(10000)
                ->get()
                ->result_array();

            foreach($list as $value){
                $in_stock_batch_value = $value['purchase_number']."_".$value['instock_batch'];
                $this->rediss->set_sadd('SET_'.$redis_key,$in_stock_batch_value);
            }
        }

        $length = $this->rediss->set_scard('SET_'.$redis_key);
        if($length <= 0){
            $this->rediss->deleteData($redis_key);
            $this->error_json('Not Found Results!');
        }else{
            $in_stock_batch_list = [];
            for($i = 0; $i < $page_size ;$i ++){
                $in_stock_batch_value = $this->rediss->set_spop('SET_'.$redis_key);
                if(!isset($in_stock_batch_value[0]) or empty($in_stock_batch_value[0])){
                    continue;
                }

                $in_stock_batch_list[] = $in_stock_batch_value[0];
            }
            if(empty($in_stock_batch_list)){
                $this->rediss->deleteData($redis_key);
                $this->error_json('Not Found Results!!!');
            }
            $result = $this->chargeARModel->check_inventory_item_charge_against($in_stock_batch_list);

            sleep(2);// 防止其他进程运行重叠
            $this->rediss->deleteData($redis_key);
            $this->success_json($result);
        }
    }


    /**
     * 计划任务 自动生成对账单 PDF 文件
     * @link /statement_init/create_statement_order_pdf?statement_number=PFB-DZ000021&force=1
     */
    public function create_statement_order_pdf(){
        $statement_number = $this->input->get_post('statement_number');
        $force = $this->input->get_post('force');

        set_time_limit(0);
        ini_set('memory_limit', '1500M');

        $this->load->library('Upload_image');
        $this->load->library('Filedirdeal');
        $this->load->library('Print_pdf_deal');
        $this->load->model('statement/Purchase_statement_model');

        if($statement_number and $force){
            $list = $this->Purchase_statement_model->purchase_db->select('statement_number')
                ->where('statement_number',$statement_number)
                ->get('purchase_statement')
                ->result_array();
        }else{
            $list = $this->Purchase_statement_model->purchase_db->select('statement_number')
                ->where('create_time > "2021-09-01 00:00:00" ')
                ->where('filePath =""')
                ->limit(10)
                ->order_by('id ASC')
                ->get('purchase_statement')
                ->result_array();
        }

        if (empty($list)) {
            echo '数据均已处理完毕';
            exit;
        }

        $this->load->model('system/Data_center_model');
        foreach ($list as $value) {
            $statement_number = $value['statement_number'];
            echo $statement_number;

            $params = [];
            $params['id'] = $statement_number;

            try {
                $this->Data_center_model->compact_statement_download($params);
                echo 'success<br/>';
            } catch (Exception $exp) {
                echo $exp->getMessage().'<br/>';
            }
        }
    }


    /**
     * 计划任务 处理回调消息，解析签署状态
     * @link /statement_init/plan_analyze_esign_message
     */
    public function plan_analyze_esign_message(){
        $this->load->library('mongo_db');

        $timestamp = $this->input->get_post('timestamp');

        $this->load->model('statement/Purchase_statement_model');

        if($timestamp){
            $list_data = $this->mongo_db->where(['timestamp' => intval($timestamp)])
                ->limit(100)
                ->get('esign_callback_log');
        }else{
            $list_data = $this->mongo_db->where(['handing_status' => 1])
                ->limit(100)
                ->get('esign_callback_log');
        }

        if(empty($list_data)){
            $this->success_json('Not found results');
        }

        $total = count($list_data);
        $success = $fail = 0;
        foreach($list_data as $list_item){
            $where = ['_id' => $list_item->_id];

            try{
                if(!isset($list_item->input) or empty($list_item->input)){
                    throw new Exception('Params [input] is null');
                }


                // 解析数据
                $input_data = $list_item->input;
                $input_data = json_decode($input_data);


                if(!isset($input_data->flowId) or empty($input_data->flowId)){
                    throw new Exception('Params [input.flowId] is null');
                }

                if(!isset($input_data->action) or empty($input_data->action)){
                    throw new Exception('Params [input.action] is null');
                }

                if($input_data->action == 'SIGN_FLOW_FINISH'){
                    if(!isset($input_data->flowStatus) or empty($input_data->flowStatus)){
                        throw new Exception('Params [input.flowStatus] is null');
                    }
                }elseif($input_data->action == 'SIGN_FLOW_UPDATE'){
                    if(!isset($input_data->signResult) or empty($input_data->signResult)){
                        throw new Exception('Params [input.signResult] is null');
                    }
                }


                // 操作时间：e签宝操作时间 -> 回调消息推送时间 -> 当前时间
                if(isset($input_data->timestamp)){
                    $opTime = $input_data->timestamp / 1000;// 毫秒转成秒
                }elseif(isset($list_item->timestamp)){
                    $opTime = $list_item->timestamp;
                }else{
                    $opTime = time();
                }

                $data = [
                    'flowId' => $input_data->flowId,
                    'action' => $input_data->action,
                    'flowStatus' => isset($input_data->flowStatus)?$input_data->flowStatus:null,// 签署任务状态
                    'signResult' => isset($input_data->signResult)?$input_data->signResult:null,// 签署结果
                    'order' => isset($input_data->order)?$input_data->order:null,// 签署人的签署顺序
                    'opTime' => date('Y-m-d H:i:s',$opTime)
                ];
                $result = $this->Purchase_statement_model->analyze_esign_message($data);
                if($result['code'] === false){
                    throw new Exception('Update failed ['.$result['message'].']');
                }

                $update_arr = [// 处理失败
                    'handing_status' => 2,
                    'handing_msg' => 'Success'
                ];
                $success ++;

            }catch (Exception $e){
                $update_arr = [// 处理失败
                    'handing_status' => 3,
                    'handing_msg' => $e->getMessage()
                ];
                $fail ++;
            }

            $this->mongo_db->where($where)->update('esign_callback_log', $update_arr);


            #tododuizhang

        }

        echo "本次执行总个数：$total 个，成功：$success 个，失败：$fail 个";
        exit;
    }
}