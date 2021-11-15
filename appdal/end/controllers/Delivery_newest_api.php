

<?php
/**
 * @function: 101053 是否定制、权均交期、是否逾期、逾期天数、首次预计到货时间的逻辑优化；增加是否逾期(交期)、逾期天数(交期)
 *   权限交期统计脚本
 * @author:luxu
 * @time:2020-06-12
 **/
if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

class Delivery_newest_api extends MY_API_Controller{

    protected  $warehouseResult = "warehouse_results"; // SKU 入库数据表
    protected  $purchaseOrders = "purchase_order"; // 采购单主表
    protected  $dvliveryLogs = "sku_avg_delivery_time_log"; // 交期日志表
    protected  $_limit = 2000;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('arrival_record_model', 'arrival_record_model', false, 'purchase');
        $this->load->model('purchase/delivery_log_model', 'delivery_log_model');
        $this->load->model('purchase/delivery_model', 'delivery_model');
    }

    /**
     * 获取权限交期日志数据
     * @author:luxu
     * @time:2020-06-12
     **/
    public function getDeliveryLogData(){

        try{
            /**
            获取备单首次入库数量
             **/
            $total = $this->getFirstPurchaseWarehouseQuery()->group_by("warehouseResult.sku,warehouseResult.purchase_number")->count_all_results();

            if($total > 0){
                // 分页数据
                $pages = ceil($total/$this->_limit);
                for( $i=0;$i<=$pages;++$i) {
                    // 获取备货单首次入库的数据
                    $result = $this->getFirstPurchaseWarehouseQuery()->group_by("warehouseResult.sku,warehouseResult.purchase_number")
                        ->limit($this->_limit,$i*$this->_limit)->get()->result_array();

                    if(!empty($result)){
                        $insertData_data = [];
                        foreach($result as $key=>$value){
                            // 计算入库记录是否有效，计算公式:首次入库时间-审核时间 如果大于1.5天 有效，反之 无效
                            $dieffDays = strtotime($value['instock_date']) - strtotime($value['audit_time']);
                            $insertData = [
                                'purchase_number' => $value['purchase_number'],
                                'sku'  => $value['sku'],
                                'supplier_name' => $value['supplier_name'],
                                'warehouse_code' => $value['warehouse_code'],
                                'purchase_type_id' => $value['purchase_type_id'],
                                'check_time' => $value['quality_time'],
                                'audit_time' => $value['audit_time'],
                                'create_time' => date("Y-m-d H:i:s",time()),
                                'system_at' =>1,
                                'first_warehouse_date' => $value['instock_date']
                            ];
                            if($dieffDays < 0){

                                // 如果时间差小于0 表示无效
                                $insertData['is_effect'] = 1;
                            }else{

                                $insertData['is_effect'] = (($dieffDays/86400) > 1.5)?2:1;
                            }
                            $insertData['deveily_days'] = round($dieffDays/(3600*24),2);
                            //$this->db->where("id",$value['warehouseId'])->update('warehouse_results', array('new_is_check_inland' => 1));
                            $this->db->where("id",$value['warehouseId'])->update('warehouse_results', array('new_is_check_inland' => 1));

                            $where = [
                                'purchase_number' => $value['purchase_number'],
                                'sku'  => $value['sku'],
                            ];

                            $datas = $this->db->from($this->dvliveryLogs)->where($where)->select("id")->get()->row_array();
                            if(empty($datas)){

                                $this->db->insert_batch($this->dvliveryLogs,[$insertData]);
                            }
                        }
                    }
                }
            }
        }catch (Exception $exp ){

            echo $exp->getMessage();
        }
    }

    public function deleteData(){

        $sql = " DELETE  FROM pur_sku_avg_delivery_time WHERE 1=1";
        $result = $this->db->query($sql);
    }

    public function repairData(){
        $limit =1000;
        $page = ceil(20000/$limit);
        for( $i=1;$i<=$page;++$i){

            $sql = "SELECT
                      a.id,
                        a.sku,
                        a.avg_delivery_time,
                        b.avg
                    FROM
                        pur_sku_avg_delivery_time AS a
                    LEFT JOIN (
                        SELECT
                            sku,
                            round(
                                sum(deveily_days) / count(*),
                                3
                            ) AS avg
                        FROM
                            pur_sku_avg_delivery_time_log
                        WHERE
                            is_effect = 2
                    
                        GROUP BY
                            sku
                        HAVING
                            COUNT(*) > 1 AND avg IS NOT NULL
                    ) AS b ON a.sku = b.sku
                    AND a.avg_delivery_time != b.avg
                    where b.avg!=''  ORDER BY a.id  DESC
            LIMIT ".($i-1)*$limit.",".$limit;
            $result = $this->db->query($sql)->result_array();
            if(!empty($result)){

                foreach($result as $key=>$value){

                    $dsql = " DELETE  FROM  pur_sku_avg_delivery_time WHERE sku='{$value['sku']}'";
                    $this->db->query($dsql);

                    $this->runDelivery($value['sku']);
                }
            }else{
                break;
            }
        }
    }

    function testa(){
        $sku = $_GET['sku'];
        $this->runDelivery($sku);
    }

    /**
     * 计算交期
     * @author:luxu
     * @time:2020/6/15
     **/
    public function runDelivery($sku = NULL){

        try{

            if($sku ==  NULL) {
                $total = $this->getEffectDataQuery()->group_by("sku,warehouse_code")->count_all_results();
            }else{
                $total = $this->getEffectDataQuery(NULL,NULL,$sku)->group_by("sku,warehouse_code")->count_all_results();
            }
            if( $total >0){

                $pages = ceil($total/$this->_limit);

                for( $i=0;$i<=$pages;++$i){

                    if($sku == NULL) {
                        $result = $this->getEffectDataQuery()->SELECT(" max(first_warehouse_date) as first_warehouse_date,
                        min(first_warehouse_date) as min_first_warehouse_date,
                    id,
                    sku,
                    purchase_type_id,
                    warehouse_code")->group_by("sku,warehouse_code")->limit($this->_limit, $i * $this->_limit)->get()->result_array();
                    }else{
                        $result = $this->getEffectDataQuery(NULL,NULL,$sku)->SELECT(" max(first_warehouse_date) as first_warehouse_date,
                        min(first_warehouse_date) as min_first_warehouse_date,
                    id,
                    sku,
                    purchase_type_id,
                    warehouse_code")->group_by("sku,warehouse_code")->limit($this->_limit, $i * $this->_limit)->get()->result_array();
                    }

                    if(!empty($result)){

                        foreach($result as $key=>$value){

                            // 如果当前时间 - 首次入库时间 大于 1年。有效记录变更为无效
                            if( time() - strtotime($value['min_first_warehouse_date']) >0){
                                $year = floor(abs(time()-strtotime($value['min_first_warehouse_date']))/31536000);
                                if( $year >=1){

                                    // 当前时间-首次入库时间大于1年，有效记录变更为无效
                                    $updateData = [

                                        'is_effect' => 1,
                                    ];
                                    $this->db->where("sku",$value['sku'])->where('warehouse_code',$value['warehouse_code'])->
                                        where('first_warehouse_date',$value['min_first_warehouse_date'])->update($this->dvliveryLogs,$updateData);
                                }

                            }

                            // 入库时间往前推3个月
                            $startTime = $this->to_sex_month(date("Y-m-d H:i:s",time()));

                            $timeEffectData = $this->getEffectDataQuery($startTime,date("Y-m-d H:i:s",time()),$value['sku'],$value['warehouse_code'])->get()->result_array();
                            $last = array_column($timeEffectData,'first_warehouse_date');
                            array_multisort($last,SORT_DESC,$timeEffectData);
                            if(count($timeEffectData) >= 3){

                                $timeEffectData = $timeEffectData;
                            }else{

                                $startTime = $this->to_sex_month(date("Y-m-d H:i:s",time()),'-12');
                                $timeEffectData = $this->getEffectDataQuery($startTime,date("Y-m-d H:i:s",time()),$value['sku'],$value['warehouse_code'])->get()->result_array();
                                $last = array_column($timeEffectData,'first_warehouse_date');
                                array_multisort($last,SORT_DESC,$timeEffectData);
                                if(count($timeEffectData) >= 3){
                                    $timeEffectData = array_slice($timeEffectData,0,3);
                                }
                            }
                            $add_arr['sku'] = $value['sku'];
                            $add_arr['warehouse_code'] = $value['warehouse_code'];
                            $add_arr['purchase_type_id'] = $value['purchase_type_id'];

                            if(!empty($timeEffectData)){

                                $avgTimeTotal = $this->getAvgDelivery($timeEffectData);

                                $add_arr['avg_delivery_time'] = $avgTimeTotal;
                            }else{
                                $add_arr['avg_delivery_time'] = 0;
                            }
                            $delivery_info = $this->delivery_model->get_delivery_info($value['warehouse_code'], $value['sku']);
                            if (empty($delivery_info)) {
                                $add_arr['statistics_date'] = date('Y-m-d H:i:s');

                                $result = $this->delivery_model->insert_delivery_info($add_arr);
                                if ($result) {
                                    //推入消息队列
                                    $this->_push_rabbitmq($add_arr, 'insert');
                                }
                            } else {
                                $result = $this->delivery_model->update_delivery_info($value['warehouse_code'], $value['sku'], $add_arr);
                                if ($result) {
                                    //推入消息队列
                                    $this->_push_rabbitmq($add_arr, 'update');
                                }
                            }
                            $this->db->where('warehouse_code="' . $value['warehouse_code'] . '" and sku="' . $value['sku'] . '"')->update('sku_avg_delivery_time_log', array('is_calculate' => 1));

                        }
                    }
                }
            }
        }catch ( Exception $exp ){

            echo $exp->getMessage();
        }
    }


    //二维数组根据某个值排序

    /**
     * sku新增了某个采购仓库的权均交期时,或sku+采购仓库维度的权均交期态发生变化时，推入消息队列
     * @param array $data 权均交期数据
     * @param string $type 类型（insert-新增，update-更新）
     */
    private function _push_rabbitmq($data, $type)
    {
        //推入消息队列
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setExchangeName('SKU_AVG_DELIVERY_TIME_EX_NAME');
        $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
        //构造存入数据
        $push_data = [
            'sku' => $data['sku'],
            'warehouse_code' => $data['warehouse_code'],
            'avg_delivery_time' => $data['avg_delivery_time'],
            'type' => $type,
            'push_time' => time()
        ];
        //存入消息队列
        $mq->sendMessage($push_data);
    }

    /**
     * 计算SKU 交期
     * @params $skuData   array  SKU 交期数据
     * @author:luxu
     * @time:2020-6-15
     **/

    private function getAvgDelivery($skusData = array()){

        if( !is_array($skusData) || !empty($skusData)){

            $avgTimeTotal = 0;
            foreach($skusData as $sku_key=>$sku_value){

                $delivery_time_log_items_arr['purchase_number'] = $sku_value['purchase_number'];
                $delivery_time_log_items_arr['sku'] = $sku_value['sku'];
                $delivery_time_log_items_arr['supplier_name'] = $sku_value['supplier_name'];
                $delivery_time_log_items_arr['warehouse_code'] = $sku_value['warehouse_code'];
                $delivery_time_log_items_arr['purchase_type_id'] = $sku_value['purchase_type_id'];
                $delivery_time_log_items_arr['check_time'] = $sku_value['check_time'];
                $delivery_time_log_items_arr['audit_time'] = $sku_value['audit_time'];
                $delivery_time_log_items_arr['create_time'] = date('Y-m-d H:i:s');
                //$this->db->insert('sku_avg_delivery_time_log_items', $delivery_time_log_items_arr);
                //$avgTimeTotal += strtotime($sku_value['first_warehouse_date']) - strtotime($sku_value['audit_time']);
                $avgTimeTotal += $sku_value['deveily_days'];

            }
            $avgTime = (float)$avgTimeTotal / count($skusData);
            return $avgTime;
        }
    }

    public  function to_sex_month($today,$days='-3'){
        $old_time = date("Y-m-d H:i:s",strtotime("{$days} month",strtotime($today)));
        return $old_time;
    }

    /**
     * 获取采购单入库数据
     * @param:$total        int      总共多少条数据
     *        $end_time     string   结束时间
     * @author:luxu
     * @time:2020-06-12
     **/
    protected  function getFirstPurchaseData($total,$page,$start_time = NULL ,$end_time = NULL ){


    }

    /**
     * 获取采购单入库数据SQL
     * @param:$start_time   string   开始时间
     *        $end_time     string   结束时间
     * @author:luxu
     * @time:2020-06-12
     **/
    private function getFirstPurchaseWarehouseQuery( $start_time = NULL ,$end_time = NULL,$sku=NULL){

        $query = $this->db->from($this->warehouseResult." AS warehouseResult")->join($this->purchaseOrders." AS purchaseOrders","warehouseResult.purchase_number=purchaseOrders.purchase_number","LEFT");
        $query->where("purchaseOrders.purchase_order_status>=6")->where("purchaseOrders.purchase_order_status!=14");
        $query->where("warehouseResult.new_is_check_inland=0");
        $query->where("warehouseResult.instock_user_name!='admin'");
        $query->where("warehouseResult.quality_time!='0000-00-00 00:00:00'");
        $result = $query->select(" MIN(warehouseResult.id) AS id,warehouseResult.purchase_number,warehouseResult.sku,
        purchaseOrders.supplier_name,purchaseOrders.supplier_code,purchaseOrders.warehouse_code,
        purchaseOrders.purchase_order_status,purchaseOrders.audit_time,purchaseOrders.waiting_time,warehouseResult.instock_date,
        purchaseOrders.purchase_type_id,warehouseResult.quality_time,warehouseResult.id AS warehouseId")
            ->order_by("warehouseResult.id DESC");
        return $result;
    }

    /**
     * 获取交期日志查询SQL
     * @param:无
     * @author:luxu
     * @time:2020/6/15
     **/
    private function getEffectDataQuery($startTime = NULL,$endTime = NULL ,$sku = NULL,$warehouseCode = NULL){

        $query = $this->db->from($this->dvliveryLogs." AS logs")->where("is_effect",2);
        if(NULL != $startTime && NULL != $endTime){
            $query->where("first_warehouse_date>=",$startTime)->where("first_warehouse_date<=",$endTime);
        }

        if( NULL != $sku){
            $query->where("sku",$sku);
        }

        if( NULL != $warehouseCode){
            $query->where("warehouse_code",$warehouseCode);
        }
        //  $query->group_by("sku");
        return $query;
    }

    /**
     * 统计采购单增加-是否逾期(交期)，逾期天数(交期)，需要进行历史数据的初始化。
     * 等待到货、信息修改待审核、信息修改驳回；作废待审核，且入库数量≠0
     * items.sku,items.purchase_number,items.is_customized,orders.audit_time
     * 采购单状态(1.等待采购询价,2.信息修改待审核,3.待采购审核,5.待销售审核,6.等待生成进货单,7.等待到货,8.已到货待检测,9.全部到货,
     * 10.部分到货等待剩余到货,11.部分到货不等待剩余到货,12.作废订单待审核,13.作废订单待退款,14.已作废订单,15.信息修改驳回)
     * @author:luxu
     * @time: 一天跑两次或者一次
     **/
    public function getDevlieryDay()
    {

        $sql = " SELECT
                    COUNT(*) as total
                FROM
                    pur_purchase_order AS orders
                LEFT JOIN pur_purchase_order_items AS items ON orders.purchase_number=items.purchase_number
                LEFT JOIN `pur_purchase_suggest_map` AS `map` ON `map`.`purchase_number` = `items`.`purchase_number`
                LEFT JOIN `pur_purchase_suggest` AS `sg` ON `map`.`demand_number` = `sg`.`demand_number` 
                WHERE
                    sg.suggest_order_status IN (7, 2, 15) 
                    OR (sg.suggest_order_status IN(12) AND items.upselft_amount>0) 
                ";

        $total = $this->db->query($sql)->row_array();
        $limit = 500;
        $page = ceil($total['total'] / $limit);
        for ($i = 1; $i <= $page; ++$i) {

            $sql = " SELECT
                   items.sku,items.purchase_number,items.is_customized,orders.audit_time,items.id AS tid,orders.purchase_type_id
                   ,items.first_plan_arrive_time
                FROM
                    pur_purchase_order AS orders
                LEFT JOIN pur_purchase_order_items AS items ON orders.purchase_number=items.purchase_number
                LEFT JOIN `pur_purchase_suggest_map` AS `map` ON `map`.`purchase_number` = `items`.`purchase_number`
                LEFT JOIN `pur_purchase_suggest` AS `sg` ON `map`.`demand_number` = `sg`.`demand_number` 
                WHERE
                     sg.suggest_order_status IN (7, 2, 15) 
                    OR (sg.suggest_order_status IN(12) AND items.upselft_amount>0)  ORDER BY tid DESC LIMIT " . ($i - 1) * $limit . "," . $limit;
            $result = $this->db->query($sql)->result_array();
            if (!empty($result)) {

                $purchaseSkusNumber = array_map(function ($data) {

                       return $data['purchase_number'];

                }, $result);
                $purchaseSkusDatas = array_map(function ($data) {

                    return $data['sku'];

                }, $result);
                $warehouseData = $this->db->from("warehouse_results")->where_in("purchase_number",$purchaseSkusNumber)
                    ->where_in("sku",$purchaseSkusDatas)->select("MIN(id) as id,instock_date,sku,purchase_number")->group_by("purchase_number,sku")
                    ->get()->result_array();
                $data = [];
                if (!empty($warehouseData)) {

                    foreach ($warehouseData as $wareKey => $wareValue) {

                        if (!isset($data[$wareValue['purchase_number'] . "." . $wareValue['sku']])) {

                            $data[$wareValue['purchase_number'] . "." . $wareValue['sku']] = NULL;
                        }
                        $data[$wareValue['purchase_number'] . "." . $wareValue['sku']] = $wareValue['instock_date'];
                    }
                }

                $skuDatas = array_map(function ($data) {

                    return $data['sku'];
                }, $result);

                $skuDevliery = $this->db->from("product")->where_in("sku", $skuDatas)->select("sku,devliy")->get()->result_array();
                foreach ($result as $key => $value) {

                    if ($value['is_customized'] == 2) {

                        // 非定制
                        if (!in_array($value['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {

                            $days = 10;
                        } else {
                            $days = 40;
                        }
                        if (isset($data[$value['purchase_number'] . "." . $value['sku']])) {
                            $skuInstockDate = date("Y-m-d", strtotime($data[$value['purchase_number'] . "." . $value['sku']]));
                            if ($skuInstockDate != date("Y-m-d", time())) {
                                $shouldArrive = strtotime($value['audit_time']) + 86400 * $days; // 应到时间
                                if (date("Y-m-d") <= date("Y-m-d", $shouldArrive)) {
                                    //当前时间≤应到时间的，那么逾期天数为负数，无需处理，前端页面一律显示=0，是否逾期=否；
                                    $update = [

                                        'devliery_days' => 0,
                                        'devliery_status' => 1, // 为逾期
                                    ];
                                }
                                if (date("Y-m-d") > date("Y-m-d", $shouldArrive)) {

                                    if (date("Y-m-d", $shouldArrive) >= $skuInstockDate) {

                                        $update = [

                                            'devliery_days' => 0,
                                            'devliery_status' => 1, // 未逾期
                                        ];
                                    } else {

                                        //当前时间＞应到时间的，那么逾期天数=当前时间-应到时间，是否逾期=是
                                        $update = [

                                            'devliery_days' => round((time() - $shouldArrive) / (3600 * 24), 3),
                                            'devliery_status' => 2, // 逾期
                                        ];

                                    }
                                }

                            }
                        }else{

                            // 没有入库过
                            $shouldArrive = strtotime($value['audit_time']) + 86400 * $days; // 应到时间
                            if (date("Y-m-d") > date("Y-m-d", $shouldArrive)) {

                                $update = [

                                    'devliery_days' => round((time() - $shouldArrive) / (3600 * 24), 3),
                                    'devliery_status' => 2, // 逾期
                                ];
                            }else{

                                $update = [

                                    'devliery_days' => 0,
                                    'devliery_status' => 1, // 未逾期
                                ];
                            }
                        }

                        if(!empty($update)) {
                            $this->db->where('purchase_number', $value['purchase_number'])->where('sku', $value['sku'])
                                ->update("purchase_order_items", $update);
                        }
                    } else {
                        if (empty($value['purchase_number'])) {
                            continue;
                        }
                        // 定制
                        if (isset($data[$value['purchase_number'] . "." . $value['sku']])) {
                        $skuInstockDate = date("Y-m-d", strtotime($data[$value['purchase_number'] . "." . $value['sku']]));

                        if ($skuInstockDate != date("Y-m-d", time())) {

                            $skuDe = isset($skuDevliery[$value['sku']]) ? $skuDevliery[$value['sku']] : 0;
                            $shouldArrive = time() - (strtotime($value['audit_time']) + 86400 * $skuDe);
                            if ($shouldArrive <= 0) {

                                $update = [

                                    'devliery_days' => 0,
                                    'devliery_status' => 1, // 未逾期
                                ];
                            } else {
                                $update = [

                                    'devliery_days' => round( $shouldArrive /(3600 * 24),4),
                                    'devliery_status' => 2, // 逾期
                                ];

                                }

                            }
                        }else{

                            if( date("Y-m-d H:i:s") > $value['first_plan_arrive_time']){

                                $update = [

                                    'devliery_days' => round( (time()-strtotime($value['first_plan_arrive_time'])) / (3600 * 24), 4),
                                    'devliery_status' => 2, // 逾期
                                ];
                            }else{

                                $update = [

                                    'devliery_days' => 0,
                                    'devliery_status' => 1, // 未逾期
                                ];
                            }
                        }
                        if(!empty($update)) {
                            $this->db->where('purchase_number', $value['purchase_number'])->where('sku', $value['sku'])
                                ->update("purchase_order_items", $update);
                        }
                    }
                }
            }
        }
    }

    /**
     * 刷新首次到货时间,数据
     * @author:luxu
     * @time:2020/7/8
     * {"1":"等待采购询价","2":"信息修改待审核","3":"待采购审核",
     * "5":"待销售审核","6":"等待生成进货单","7":"等待到货","8":"已到货待检测",
     * "9":"全部到货","10":"部分到货等待剩余到货","11":"部分到货不等待剩余到货",
     * "12":"作废订单待审核","13":"作废订单待退款","14":"已作废订单","15":"信息修改驳回"}
     **/
    public function estimated_delivery_time(){

        try{

            $sql = " 
                       SELECT
                         COUNT(*) AS total
                       FROM
                          pur_purchase_order AS orders
                       LEFT JOIN pur_purchase_order_items AS items ON orders.purchase_number=items.purchase_number
                       LEFT JOIN `pur_purchase_suggest_map` AS `map` ON `map`.`purchase_number` = `items`.`purchase_number`
                       LEFT JOIN `pur_purchase_suggest` AS `sg` ON `map`.`demand_number` = `sg`.`demand_number` 
                       WHERE
                       sg.suggest_order_status IN (2,7,15,12) 
                      ";

            $total = $this->db->query($sql)->row_array();
            $limit =1000;
            $page =  ceil($total['total']/$limit);
            for( $i=1;$i<=$page;++$i){

                $sql = " 
                       SELECT
                         items.purchase_number,items.sku,items.id AS tid,items.first_plan_arrive_time,orders.purchase_type_id,
                         orders.warehouse_code,orders.audit_time,items.first_plan_arrive_time,items.plan_arrive_time
                       FROM
                          pur_purchase_order AS orders
                       LEFT JOIN pur_purchase_order_items AS items ON orders.purchase_number=items.purchase_number
                       LEFT JOIN `pur_purchase_suggest_map` AS `map` ON `map`.`purchase_number` = `items`.`purchase_number`
                       LEFT JOIN `pur_purchase_suggest` AS `sg` ON `map`.`demand_number` = `sg`.`demand_number` 
                       WHERE
                       sg.suggest_order_status IN (2,7,15,12) 
                      LIMIT ".($i - 1) * $limit . "," . $limit;
                $result = $this->db->query($sql)->result_array();
                if(!empty($result)){

                    $devlieryData = $this->db->from("sku_avg_delivery_time")->where_in("sku",array_column($result,"sku"))
                        ->where_in("warehouse_code",array_column($result,"warehouse_code"))
                        ->get()->result_array();
                    $data = [];
                    if(!empty($devlieryData)){

                        foreach($devlieryData as $deKey=>$deValue){

                            $devKeys = $deValue['sku']."|".$deValue['warehouse_code'];
                            if( !isset($data[$devKeys])){
                                $data[$devKeys] = NULL;
                            }
                            $data[$devKeys] = $deValue['avg_delivery_time'];
                        }
                    }

                    $productData = $this->db->from("product")->where_in("sku",array_column($result,"sku"))
                        ->select("sku,devliy")->get()->result_array();
                    $productData = array_column($productData,NULL,"sku");
                    foreach($result as $resKey=>$resValue){

                        $resKeys = $resValue['sku']."|".$resValue['warehouse_code'];
                        if( isset($data[$resKeys]) && !empty($data[$resKeys]) && $data[$resKeys] != 0.000) {

                            //SKU+仓库权均交期不等于0，首次预计到货时间=审核时间+权均交期。
                            $devlieryTime = strtotime($resValue['audit_time']) + $data[$resKeys] *3600 * 24;
                        }else{

                            //SKU+仓库维度的权均交期等0 且 产品管理 SKU 交期等于0 ，国内仓/FAB/FPB 首次预计到货时间=审核时间+7天。
                            //海外仓首次预计到货时间 = 审核时间+40天。产品管理 SKU交期不等于0 首次预计到货时间=审核时间+SKU交期
                            if( empty($productData[$resValue['sku']]['devliy']) || $productData[$resValue['sku']]['devliy'] == 000.00){

                                if($resValue['purchase_type_id'] !=2 ){

                                    $devlieryTime = strtotime($resValue['audit_time']) + 7*3600 * 24;
                                }else{
                                    $devlieryTime = strtotime($resValue['audit_time']) + 40*3600 * 24;
                                }
                            }else{
                                $devlieryTime = strtotime($resValue['audit_time']) + $productData[$resValue['sku']]['devliy']*3600 * 24;
                            }

                        }

                        $update['first_plan_arrive_time'] = date("Y-m-d H:i:s",$devlieryTime);

                        $this->db->where("id",$resValue['tid'])->update("purchase_order_items",$update);

                    }
                }

            }
        }catch ( Exception $exp ){

            echo $exp->getMessage();
        }
    }

    /**
     * 是否逾期，逾期天数 脚本
     * @param:wu
     * @time:2020/7/8
     * @author:luxu
     **/
    public function estimated_delivery(){
        $sql = "
                       SELECT
                         COUNT(*) AS total
                       FROM
                          pur_purchase_order AS orders
                       LEFT JOIN pur_purchase_order_items AS items ON orders.purchase_number=items.purchase_number
                       LEFT JOIN `pur_purchase_suggest_map` AS `map` ON `map`.`purchase_number` = `items`.`purchase_number`
                       LEFT JOIN `pur_purchase_suggest` AS `sg` ON `map`.`demand_number` = `sg`.`demand_number`
                       WHERE
                       sg.suggest_order_status IN (2,7,15,12) AND items.is_warehouse=0 AND items.plan_arrive_time!='0000-00-00 00:00:00'
                ";
        $total = $this->db->query($sql)->row_array();
        $limit = 1000;
        $page = ceil($total['total']/$limit);

        for( $i=1;$i<=$page;++$i){
        
            $sql = " 
                       SELECT
                        items.purchase_number,items.sku,items.id AS tid,items.plan_arrive_time
                       FROM
                          pur_purchase_order AS orders
                       LEFT JOIN pur_purchase_order_items AS items ON orders.purchase_number=items.purchase_number
                       LEFT JOIN `pur_purchase_suggest_map` AS `map` ON `map`.`purchase_number` = `items`.`purchase_number`
                       LEFT JOIN `pur_purchase_suggest` AS `sg` ON `map`.`demand_number` = `sg`.`demand_number` 
                       WHERE
                       sg.suggest_order_status IN (2,7,15,12) AND items.is_warehouse=0 AND items.plan_arrive_time!='0000-00-00 00:00:00'
                       
                LIMIT ".($i - 1) * $limit . "," . $limit;
            $purchaseData = $this->db->query($sql)->result_array();
            if(!empty($purchaseData)){

                $purchaseNumbers = array_column( $purchaseData,"purchase_number");
                $skusData =  array_column( $purchaseData,"sku");
                $firstData = $this->db->from("warehouse_results")->where_in("purchase_number",$purchaseNumbers)
                    ->where_in("sku",$skusData)->select("MIN(instock_date) AS instock_date,purchase_number,sku")->group_by("purchase_number,sku")
                    ->get()->result_array();
                $firstWareData = [];
                foreach($firstData as $firstKey=>$firstValue){

                    $keys = $firstValue['purchase_number']."|".$firstValue['sku'];
                    if( !isset($firstWareData[$keys])){

                        $firstWareData[$keys] = $firstValue['instock_date'];
                    }
                }
                foreach($purchaseData as $key=>$value){

                    // 获取首次入库时间
                    $twokeys = $value['purchase_number']."|".$value['sku'];

                    $devliery_days = NULL;
                    $warehouseFlag = false;
                    if( !isset($firstWareData[$twokeys])){

                        if( time() > strtotime($value['plan_arrive_time'])) {
                            $devliery_days = time() - strtotime($value['plan_arrive_time']);
                            $devliery_status = 1;
                        }else{

                            $devliery_days = 0;
                            $devliery_status =0;
                        }

                    }else{
                        if($firstWareData[$twokeys] >= $value['plan_arrive_time']){
                            $devliery_days = strtotime($firstWareData[$twokeys]) - strtotime($value['plan_arrive_time']);
                            $devliery_status =1;

                        }else {
                            $devliery_days = 0;
                            $devliery_status = 0;
                        }

                        $warehouseFlag = True;
                    }
                    if( $devliery_days !=0 ) {
                        $devliery_days = round($devliery_days / (3600 * 24), 4);
                    }
                    $update = [

                        'is_overdue' => $devliery_status,
                        'overdue_days' => $devliery_days
                    ];
                    if( $warehouseFlag == True){
                        $update['is_warehouse'] = 1;
                    }
                    $this->db->where("id",$value['tid'])->update("purchase_order_items",$update);

                }

            }else{

                break;
            }
        }
    }

    /**
      * 111084 权均交期的计算，因为报损导致的入库，需要进行剔除，不需要参与到权均交期的计算中
     **/
    public function delAdminData(){

      try{

          $sql = "SELECT * FROM pur_warehouse_results WHERE instock_user_name='admin' GROUP BY sku";
          $result = $this->db->query($sql)->result_array();

          $skus = array_column( $result,'sku');
          $this->db->where_in("sku",$skus)->update('warehouse_results',['new_is_check_inland'=>0]);
          if(!empty($result)){

              foreach($result as $key=>$value){

                  $delLogSql = " DELETE FROM pur_sku_avg_delivery_time_log WHERE purchase_number='{$value['purchase_number']}' AND sku='{$value['sku']}'";
                  $logsData = $this->db->where('purchase_number',$value['purchase_number'])->where('sku',$value['sku'])->delete('sku_avg_delivery_time_log');

                  $skuData = $this->db->where('sku',$value['sku'])->delete('sku_avg_delivery_time');

              }
          }

      }catch ( Exception $exp ){


      }
    }

    public function runAdminData(){

        $sql = " SELECT * FROM datas WHERE purchase_type_id IS NULL";
        $result = $this->db->query($sql)->result_array();
        foreach($result as $key=>$value){

            $url = "http://pms.yibainetwork.com:81/Delivery_newest_api/testa?sku=".$value['sku'];

            $res = getCurlData($url,NULL,'get');
            $update=[

                'purchase_type_id' =>1
            ];
            $this->db->where("sku",$value['sku'])->update('datas_a',$update);
            echo $this->db->last_query();die();
        }
    }

    public function delData(){

        $data = '[{"sku":"TJ14281"},{"sku":"TJ12573"},{"sku":"JY13522-02"},{"sku":"CW03840-01"},{"sku":"AF01244"},{"sku":"US-ZP29165"},{"sku":"US-ZP29165"},{"sku":"US-ZP29137"},{"sku":"US-ZP29137"},{"sku":"US-ZP29131"},{"sku":"US-ZP21939"},{"sku":"US-ZP21921"},{"sku":"US-ZP21921"},{"sku":"US-ZP21909"},{"sku":"US-ZP21909"},{"sku":"US-ZP20822"},{"sku":"US-ZP20822"},{"sku":"US-ZP20360"},{"sku":"US-ZM03473"},{"sku":"US-ZM03473"},{"sku":"US-ZM03351"},{"sku":"US-ZM03297"},{"sku":"US-ZM03297"},{"sku":"US-ZM02758"},{"sku":"US-ZM02755"},{"sku":"US-ZM02755"},{"sku":"US-ZM02751"},{"sku":"US-ZM02751"},{"sku":"US-ZM02750"},{"sku":"US-ZM02750"},{"sku":"US-ZM02748-01"},{"sku":"US-ZM02748-01"},{"sku":"US-ZM02746"},{"sku":"US-ZM02746"},{"sku":"US-ZM02738"},{"sku":"US-ZM02738"},{"sku":"US-ZM02737"},{"sku":"US-ZM02737"},{"sku":"US-ZM02614"},{"sku":"US-ZM02614"},{"sku":"US-YQ03539"},{"sku":"US-YQ03539"},{"sku":"US-YQ03539"},{"sku":"US-YQ03539"},{"sku":"US-YQ03538"},{"sku":"US-YQ03538"},{"sku":"US-YQ03538"},{"sku":"US-YQ03538"},{"sku":"US-YQ03537"},{"sku":"US-YQ03537"},{"sku":"US-YQ03537"},{"sku":"US-YQ03537"},{"sku":"US-YQ03200"},{"sku":"US-YQ03199"},{"sku":"US-YQ03199"},{"sku":"US-YQ03198"},{"sku":"US-YB01802"},{"sku":"US-YB01802"},{"sku":"US-YB01798"},{"sku":"US-YB01798"},{"sku":"US-XD04755"},{"sku":"US-XD04755"},{"sku":"US-TJ19541"},{"sku":"US-TJ19486"},{"sku":"US-TJ19144"},{"sku":"US-TJ17852"},{"sku":"US-TJ17852"},{"sku":"US-TJ17851"},{"sku":"US-TJ17851"},{"sku":"US-TJ17728"},{"sku":"US-TJ17728"},{"sku":"US-TJ14508"},{"sku":"US-TJ14508"},{"sku":"US-TJ12383"},{"sku":"US-TJ12356"},{"sku":"US-TJ12348"},{"sku":"US-TJ07666"},{"sku":"US-TJ07666"},{"sku":"US-TJ06381"},{"sku":"US-TJ05169-02"},{"sku":"US-TJ05169-01"},{"sku":"US-TJ04391"},{"sku":"US-TJ04389"},{"sku":"US-TJ04389"},{"sku":"US-QC33880"},{"sku":"US-QC33866-02"},{"sku":"US-QC33866-01"},{"sku":"US-QC33135"},{"sku":"US-QC33135"},{"sku":"US-QC33134"},{"sku":"US-QC33134"},{"sku":"US-QC33128"},{"sku":"US-QC33128"},{"sku":"US-QC33126"},{"sku":"US-QC33126"},{"sku":"US-QC33124"},{"sku":"US-QC33124"},{"sku":"US-QC33120"},{"sku":"US-QC33120"},{"sku":"US-QC33118"},{"sku":"US-QC33118"},{"sku":"US-QC33115"},{"sku":"US-QC33115"},{"sku":"US-QC33110"},{"sku":"US-QC33110"},{"sku":"US-QC33108"},{"sku":"US-QC33108"},{"sku":"US-QC33106"},{"sku":"US-QC33106"},{"sku":"US-QC33104"},{"sku":"US-QC33104"},{"sku":"US-QC33101"},{"sku":"US-QC33101"},{"sku":"US-QC33098"},{"sku":"US-QC33098"},{"sku":"US-QC33094"},{"sku":"US-QC33094"},{"sku":"US-QC33092"},{"sku":"US-QC33092"},{"sku":"US-QC33088"},{"sku":"US-QC33088"},{"sku":"US-QC33086"},{"sku":"US-QC33086"},{"sku":"US-QC33084"},{"sku":"US-QC33084"},{"sku":"US-QC33083"},{"sku":"US-QC33083"},{"sku":"US-QC33082"},{"sku":"US-QC33082"},{"sku":"US-QC33076"},{"sku":"US-QC33076"},{"sku":"US-QC32852"},{"sku":"US-QC32852"},{"sku":"US-QC32847"},{"sku":"US-QC32839"},{"sku":"US-QC32832"},{"sku":"US-QC32832"},{"sku":"US-QC32809"},{"sku":"US-QC32809"},{"sku":"US-QC32804"},{"sku":"US-QC32804"},{"sku":"US-QC32798"},{"sku":"US-QC32793"},{"sku":"US-QC32789"},{"sku":"US-QC32789"},{"sku":"US-QC32784"},{"sku":"US-QC32677"},{"sku":"US-QC32671"},{"sku":"US-QC32640"},{"sku":"US-QC32636"},{"sku":"US-QC32628"},{"sku":"US-QC32628"},{"sku":"US-QC29499"},{"sku":"US-QC29383"},{"sku":"US-QC29383"},{"sku":"US-QC29380"},{"sku":"US-QC29380"},{"sku":"US-QC29376"},{"sku":"US-QC29376"},{"sku":"US-QC29374"},{"sku":"US-QC29374"},{"sku":"US-QC29372"},{"sku":"US-QC29372"},{"sku":"US-QC29370"},{"sku":"US-QC29370"},{"sku":"US-QC29369"},{"sku":"US-QC29369"},{"sku":"US-QC29367"},{"sku":"US-QC29367"},{"sku":"US-QC29364"},{"sku":"US-QC29364"},{"sku":"US-QC29359"},{"sku":"US-QC29359"},{"sku":"US-QC29356"},{"sku":"US-QC29356"},{"sku":"US-QC29354"},{"sku":"US-QC29354"},{"sku":"US-QC29352"},{"sku":"US-QC29352"},{"sku":"US-QC29348"},{"sku":"US-QC29348"},{"sku":"US-QC29346"},{"sku":"US-QC29346"},{"sku":"US-QC29344"},{"sku":"US-QC29344"},{"sku":"US-QC29343"},{"sku":"US-QC29343"},{"sku":"US-QC29340"},{"sku":"US-QC29340"},{"sku":"US-QC29339"},{"sku":"US-QC29339"},{"sku":"US-QC29251"},{"sku":"US-QC29251"},{"sku":"US-QC29249"},{"sku":"US-QC29249"},{"sku":"US-QC29247"},{"sku":"US-QC29247"},{"sku":"US-QC29246"},{"sku":"US-QC29246"},{"sku":"US-QC29237"},{"sku":"US-QC29237"},{"sku":"US-QC29236"},{"sku":"US-QC29236"},{"sku":"US-QC29234"},{"sku":"US-QC29234"},{"sku":"US-QC29233"},{"sku":"US-QC29233"},{"sku":"US-QC29232"},{"sku":"US-QC29232"},{"sku":"US-QC29231"},{"sku":"US-QC29231"},{"sku":"US-QC29205"},{"sku":"US-QC29205"},{"sku":"US-QC29203"},{"sku":"US-QC29203"},{"sku":"US-QC29193"},{"sku":"US-QC29193"},{"sku":"US-QC29190"},{"sku":"US-QC29190"},{"sku":"US-QC29187"},{"sku":"US-QC29187"},{"sku":"US-QC29182"},{"sku":"US-QC29182"},{"sku":"US-QC29177"},{"sku":"US-QC29177"},{"sku":"US-QC29171"},{"sku":"US-QC29171"},{"sku":"US-QC29170"},{"sku":"US-QC29170"},{"sku":"US-QC29169"},{"sku":"US-QC29169"},{"sku":"US-QC29160"},{"sku":"US-QC29160"},{"sku":"US-QC29158"},{"sku":"US-QC29158"},{"sku":"US-QC29154"},{"sku":"US-QC29154"},{"sku":"US-QC29151"},{"sku":"US-QC29151"},{"sku":"US-QC29144"},{"sku":"US-QC29144"},{"sku":"US-QC29135"},{"sku":"US-QC29135"},{"sku":"US-QC29122"},{"sku":"US-QC29122"},{"sku":"US-QC29118"},{"sku":"US-QC29118"},{"sku":"US-QC29000"},{"sku":"US-QC29000"},{"sku":"US-QC28994"},{"sku":"US-QC28994"},{"sku":"US-QC28989"},{"sku":"US-QC28989"},{"sku":"US-QC28984"},{"sku":"US-QC28984"},{"sku":"US-QC28978"},{"sku":"US-QC28978"},{"sku":"US-QC28971"},{"sku":"US-QC28971"},{"sku":"US-QC28969"},{"sku":"US-QC28969"},{"sku":"US-QC28960"},{"sku":"US-QC28960"},{"sku":"US-QC28956"},{"sku":"US-QC28956"},{"sku":"US-QC28945"},{"sku":"US-QC28945"},{"sku":"US-QC27305"},{"sku":"US-QC27051"},{"sku":"US-QC27051"},{"sku":"US-QC26926"},{"sku":"US-QC26922"},{"sku":"US-QC26916"},{"sku":"US-QC26908"},{"sku":"US-QC26905"},{"sku":"US-QC26902"},{"sku":"US-QC26886"},{"sku":"US-QC26886"},{"sku":"US-QC26885"},{"sku":"US-QC26885"},{"sku":"US-QC26884"},{"sku":"US-QC26883"},{"sku":"US-QC26883"},{"sku":"US-QC26881"},{"sku":"US-QC26881"},{"sku":"US-QC26880"},{"sku":"US-QC26880"},{"sku":"US-QC26879"},{"sku":"US-QC26879"},{"sku":"US-QC26878"},{"sku":"US-QC26878"},{"sku":"US-QC26877"},{"sku":"US-QC26877"},{"sku":"US-QC26855"},{"sku":"US-QC26656"},{"sku":"US-QC26656"},{"sku":"US-QC26655"},{"sku":"US-QC26655"},{"sku":"US-QC26642"},{"sku":"US-QC26642"},{"sku":"US-QC26638"},{"sku":"US-QC26638"},{"sku":"US-QC26635"},{"sku":"US-QC26635"},{"sku":"US-QC26632"},{"sku":"US-QC26632"},{"sku":"US-QC26621"},{"sku":"US-QC26621"},{"sku":"US-QC26619"},{"sku":"US-QC26619"},{"sku":"US-QC26618"},{"sku":"US-QC26618"},{"sku":"US-QC26615"},{"sku":"US-QC26615"},{"sku":"US-QC26614"},{"sku":"US-QC26614"},{"sku":"US-QC26560"},{"sku":"US-QC26560"},{"sku":"US-QC26558"},{"sku":"US-QC26558"},{"sku":"US-QC26557"},{"sku":"US-QC26557"},{"sku":"US-QC26556"},{"sku":"US-QC26556"},{"sku":"US-QC26553"},{"sku":"US-QC26553"},{"sku":"US-QC26552"},{"sku":"US-QC26552"},{"sku":"US-QC26548"},{"sku":"US-QC26548"},{"sku":"US-QC26542"},{"sku":"US-QC26542"},{"sku":"US-QC26536"},{"sku":"US-QC26536"},{"sku":"US-QC26531"},{"sku":"US-QC26531"},{"sku":"US-QC26529"},{"sku":"US-QC26529"},{"sku":"US-QC26527"},{"sku":"US-QC26527"},{"sku":"US-QC26525"},{"sku":"US-QC26525"},{"sku":"US-QC26524"},{"sku":"US-QC26524"},{"sku":"US-QC26517"},{"sku":"US-QC26517"},{"sku":"US-QC26515"},{"sku":"US-QC26515"},{"sku":"US-QC26513"},{"sku":"US-QC26513"},{"sku":"US-QC26512"},{"sku":"US-QC26512"},{"sku":"US-QC26511"},{"sku":"US-QC26511"},{"sku":"US-QC26492"},{"sku":"US-QC26492"},{"sku":"US-QC26491"},{"sku":"US-QC26491"},{"sku":"US-QC26488"},{"sku":"US-QC26488"},{"sku":"US-QC26484"},{"sku":"US-QC26484"},{"sku":"US-QC26482"},{"sku":"US-QC26482"},{"sku":"US-QC26467"},{"sku":"US-QC26467"},{"sku":"US-QC26466"},{"sku":"US-QC26466"},{"sku":"US-QC26461"},{"sku":"US-QC26461"},{"sku":"US-QC26434"},{"sku":"US-QC26434"},{"sku":"US-QC26425"},{"sku":"US-QC26425"},{"sku":"US-QC26415"},{"sku":"US-QC26415"},{"sku":"US-QC17804"},{"sku":"US-QC17804"},{"sku":"US-QC17795"},{"sku":"US-QC17795"},{"sku":"US-QC17779"},{"sku":"US-QC17779"},{"sku":"US-QC17617"},{"sku":"US-QC17617"},{"sku":"US-QC17611"},{"sku":"US-QC17611"},{"sku":"US-QC17587"},{"sku":"US-QC17587"},{"sku":"US-QC17580"},{"sku":"US-QC17580"},{"sku":"US-QC17505"},{"sku":"US-QC17505"},{"sku":"US-QC17485"},{"sku":"US-QC17485"},{"sku":"US-QC17477"},{"sku":"US-QC17477"},{"sku":"US-QC17468"},{"sku":"US-QC17468"},{"sku":"US-QC10395"},{"sku":"US-QC10384"},{"sku":"US-QC10384"},{"sku":"US-QC10353"},{"sku":"US-QC10352"},{"sku":"US-QC09011"},{"sku":"US-QC09011"},{"sku":"US-QC07516"},{"sku":"US-QC06436"},{"sku":"US-QC06296"},{"sku":"US-QC01334"},{"sku":"US-QC01225"},{"sku":"US-QC01225"},{"sku":"US-QC00629"},{"sku":"US-JYJJ5606"},{"sku":"US-JYJJ5606"},{"sku":"US-JYJJ5604-5"},{"sku":"US-JYJJ19505-6"},{"sku":"US-JYA02682"},{"sku":"US-JY39038"},{"sku":"US-JY39038"},{"sku":"US-JY38976"},{"sku":"US-JY38976"},{"sku":"US-JY36971"},{"sku":"US-JY36188"},{"sku":"US-JY36188"},{"sku":"US-JY36181"},{"sku":"US-JY36181"},{"sku":"US-JY36100-03"},{"sku":"US-JY36100-03"},{"sku":"US-JY35796"},{"sku":"US-JY35796"},{"sku":"US-JY34975-02"},{"sku":"US-JY34975-02"},{"sku":"US-JY34975-01"},{"sku":"US-JY34975-01"},{"sku":"US-JY31176"},{"sku":"US-JY30257"},{"sku":"US-JY30248"},{"sku":"US-JY27135"},{"sku":"US-JY27135"},{"sku":"US-JY27088"},{"sku":"US-JY27088"},{"sku":"US-JY23007-01"},{"sku":"US-JY23007-01"},{"sku":"US-JY18706"},{"sku":"US-JY12955"},{"sku":"US-JY12674"},{"sku":"US-JY12674"},{"sku":"US-JY12674"},{"sku":"US-JY11380"},{"sku":"US-JY10788"},{"sku":"US-JY08608"},{"sku":"US-JY08608"},{"sku":"US-JY08608"},{"sku":"US-JY05032"},{"sku":"US-JY03914"},{"sku":"US-JY03764-03"},{"sku":"US-JY02747"},{"sku":"US-JY02747"},{"sku":"US-JY02740"},{"sku":"US-JY02740"},{"sku":"US-JY00853-01"},{"sku":"US-JY00853-01"},{"sku":"US-JY00634-02"},{"sku":"US-JY00634-02"},{"sku":"US-JY00634-02"},{"sku":"US-JY00246"},{"sku":"US-JY00245"},{"sku":"US-JM24396"},{"sku":"US-JM24396"},{"sku":"US-JM24373"},{"sku":"US-JM24315"},{"sku":"US-JM24315"},{"sku":"US-JM24229"},{"sku":"US-JM24229"},{"sku":"US-JM23828"},{"sku":"US-JM23828"},{"sku":"US-JM12698"},{"sku":"US-JM12445"},{"sku":"US-JM12445"},{"sku":"US-JM12301"},{"sku":"US-JM12301"},{"sku":"US-JM11849-02"},{"sku":"US-JM11849-02"},{"sku":"US-JM11849-02"},{"sku":"US-JM11849-02"},{"sku":"US-JM11350"},{"sku":"US-JM11072"},{"sku":"US-JM08140"},{"sku":"US-JM04904"},{"sku":"US-JM01421"},{"sku":"US-GS22231"},{"sku":"US-GS22227"},{"sku":"US-GS21060"},{"sku":"US-GS21060"},{"sku":"US-GS21050"},{"sku":"US-GS21018"},{"sku":"US-GS21016"},{"sku":"US-GS21013"},{"sku":"US-GS07293"},{"sku":"US-GS07257"},{"sku":"US-GS07019"},{"sku":"US-GS07019"},{"sku":"US-GS06853"},{"sku":"US-GS06853"},{"sku":"US-GS06853"},{"sku":"US-GS06836"},{"sku":"US-GS06836"},{"sku":"US-GS04343"},{"sku":"US-GS04343"},{"sku":"US-GS02662-04"},{"sku":"US-GS02653"},{"sku":"US-GS02653"},{"sku":"US-GS01686"},{"sku":"US-GS01026"},{"sku":"US-GS00540"},{"sku":"US-GS00540"},{"sku":"US-CW05804"},{"sku":"US-CW05804"},{"sku":"US-CW01297"},{"sku":"US-CW01294"},{"sku":"US-AF04273"},{"sku":"US-AF04270"},{"sku":"US-AF04266"},{"sku":"IT-ZM03473"},{"sku":"IT-ZM02748-02"},{"sku":"IT-ZM02748-01"},{"sku":"GB-ZP29137"},{"sku":"GB-ZP29134"},{"sku":"GB-ZP29131"},{"sku":"GB-ZP20360"},{"sku":"GB-ZM02755"},{"sku":"GB-YQ03539"},{"sku":"GB-YQ03539"},{"sku":"GB-YQ03538"},{"sku":"GB-YQ03538"},{"sku":"GB-YQ03537"},{"sku":"GB-YQ03537"},{"sku":"GB-YQ03200"},{"sku":"GB-YQ03199"},{"sku":"GB-YQ03198"},{"sku":"GB-YB01802"},{"sku":"GB-YB01798"},{"sku":"GB-XDOT40600"},{"sku":"GB-XDOT37900"},{"sku":"GB-XD03751-02"},{"sku":"GB-XD03749-03"},{"sku":"GB-XD01594-02"},{"sku":"GB-XD01594-01"},{"sku":"GB-XD01594-01"},{"sku":"GB-TJ19541"},{"sku":"GB-TJ19486"},{"sku":"GB-TJ19483"},{"sku":"GB-TJ19050"},{"sku":"GB-TJ19049"},{"sku":"GB-TJ19047"},{"sku":"GB-TJ19046"},{"sku":"GB-TJ17852"},{"sku":"GB-TJ12356"},{"sku":"GB-TJ05354"},{"sku":"GB-QCMP0408XL"},{"sku":"GB-QC33875"},{"sku":"GB-QC33868"},{"sku":"GB-QC33866-02"},{"sku":"GB-QC33866-01"},{"sku":"GB-QC31884"},{"sku":"GB-QC31877"},{"sku":"GB-QC31868"},{"sku":"GB-QC31856"},{"sku":"GB-QC31850"},{"sku":"GB-QC29566"},{"sku":"GB-QC29562"},{"sku":"GB-QC29560"},{"sku":"GB-QC27305"},{"sku":"GB-QC10354"},{"sku":"GB-QC09688"},{"sku":"GB-QC07516"},{"sku":"GB-QC06559"},{"sku":"GB-QC06478"},{"sku":"GB-QC06437"},{"sku":"GB-QC04925"},{"sku":"GB-QC02157"},{"sku":"GB-QC01727"},{"sku":"GB-QC01334"},{"sku":"GB-QC00980"},{"sku":"GB-JYJJ142100"},{"sku":"GB-JYB00624"},{"sku":"GB-JYB00622"},{"sku":"GB-JYA02349-01"},{"sku":"GB-JYA02347-02"},{"sku":"GB-JYA02347-01"},{"sku":"GB-JYA02003"},{"sku":"GB-JYA02003"},{"sku":"GB-JYA02001"},{"sku":"GB-JYA02001"},{"sku":"GB-JYA02001"},{"sku":"GB-JYA01098"},{"sku":"GB-JY39229"},{"sku":"GB-JY38981"},{"sku":"GB-JY38976"},{"sku":"GB-JY38963"},{"sku":"GB-JY37843"},{"sku":"GB-JY37842"},{"sku":"GB-JY37837"},{"sku":"GB-JY37835"},{"sku":"GB-JY37659"},{"sku":"GB-JY37649"},{"sku":"GB-JY37647"},{"sku":"GB-JY37585"},{"sku":"GB-JY37213"},{"sku":"GB-JY36991-02"},{"sku":"GB-JY36991-01"},{"sku":"GB-JY36181"},{"sku":"GB-JY36100-03"},{"sku":"GB-JY35796"},{"sku":"GB-JY34975-02"},{"sku":"GB-JY34975-02"},{"sku":"GB-JY34975-01"},{"sku":"GB-JY34975-01"},{"sku":"GB-JY32656"},{"sku":"GB-JY32281"},{"sku":"GB-JY32148"},{"sku":"GB-JY27102"},{"sku":"GB-JY24508"},{"sku":"GB-JY24508"},{"sku":"GB-JY19340"},{"sku":"GB-JY19340"},{"sku":"GB-JY19338"},{"sku":"GB-JY19338"},{"sku":"GB-JY19101-02"},{"sku":"GB-JY19101-01"},{"sku":"GB-JY17767"},{"sku":"GB-JY15594"},{"sku":"GB-JY15594"},{"sku":"GB-JY14181"},{"sku":"GB-JY12955"},{"sku":"GB-JY11663"},{"sku":"GB-JY10318"},{"sku":"GB-JY08608"},{"sku":"GB-JY07961"},{"sku":"GB-JY07953"},{"sku":"GB-JY07807"},{"sku":"GB-JY07784"},{"sku":"GB-JY05866-01"},{"sku":"GB-JY04682"},{"sku":"GB-JY04081"},{"sku":"GB-JY04039"},{"sku":"GB-JY04039"},{"sku":"GB-JY01979"},{"sku":"GB-JY00634-02"},{"sku":"GB-JM23828"},{"sku":"GB-JM22284"},{"sku":"GB-JM22283"},{"sku":"GB-JM21769"},{"sku":"GB-JM15471-02"},{"sku":"GB-GSGJ3500-2"},{"sku":"GB-GS21050"},{"sku":"GB-GS21018"},{"sku":"GB-GS21016"},{"sku":"GB-GS15879"},{"sku":"GB-GS15875"},{"sku":"GB-GS15873"},{"sku":"GB-GS15174"},{"sku":"GB-GS15171"},{"sku":"GB-GS07293"},{"sku":"GB-GS07020"},{"sku":"GB-GS05551"},{"sku":"GB-GS05547"},{"sku":"GB-GS04818"},{"sku":"GB-GS04672"},{"sku":"GB-GS03396"},{"sku":"GB-GS01300"},{"sku":"GB-GS01300"},{"sku":"GB-CW05804"},{"sku":"GB-AF04273"},{"sku":"GB-AF04270"},{"sku":"GB-AF04267"},{"sku":"GB-AF04266"},{"sku":"GB-06ZH-EBE01"},{"sku":"FR-ZM03473"},{"sku":"FR-ZM03297"},{"sku":"FR-ZM02755"},{"sku":"FR-ZM02750"},{"sku":"FR-ZM02748-02"},{"sku":"FR-TJ17728"},{"sku":"FR-TJ12356"},{"sku":"FR-TJ12348"},{"sku":"FR-QT00510"},{"sku":"FR-QC10353"},{"sku":"FR-QC10352"},{"sku":"FR-QC07516"},{"sku":"FR-QC01727"},{"sku":"FR-JY34975-02"},{"sku":"FR-JY34975-01"},{"sku":"FR-JY32656"},{"sku":"FR-JY32281"},{"sku":"FR-JY32148"},{"sku":"FR-JY07953"},{"sku":"FR-JY07641"},{"sku":"FR-JM25192"},{"sku":"FR-JM25189"},{"sku":"FR-JM24370"},{"sku":"FR-JM21769"},{"sku":"ES-ZM02755"},{"sku":"ES-ZM02750"},{"sku":"ES-ZM02748-02"},{"sku":"ES-ZM02748-01"},{"sku":"ES-ZM02746"},{"sku":"ES-QCMP0408XXL"},{"sku":"ES-QCMP0407XXL"},{"sku":"ES-QCMP0400XL"},{"sku":"ES-QC04606-02"},{"sku":"ES-QC01726"},{"sku":"ES-JY07953"},{"sku":"ES-JM24229"},{"sku":"ES-JF28600"},{"sku":"ES-GS01027"},{"sku":"ES-GS00480-01"},{"sku":"DE-ZP29165"},{"sku":"DE-ZP29137"},{"sku":"DE-ZP29134"},{"sku":"DE-ZP20822"},{"sku":"DE-ZP20360"},{"sku":"DE-ZP14403-02"},{"sku":"DE-ZP14403-01"},{"sku":"DE-ZP13704-02"},{"sku":"DE-ZP13065"},{"sku":"DE-ZP13039-02"},{"sku":"DE-ZM02748-02"},{"sku":"DE-ZM02748-01"},{"sku":"DE-YQ03539"},{"sku":"DE-YQ03539"},{"sku":"DE-YQ03538"},{"sku":"DE-YQ03538"},{"sku":"DE-YQ03537"},{"sku":"DE-YQ03537"},{"sku":"DE-YQ03200"},{"sku":"DE-YQ03199"},{"sku":"DE-YB01798"},{"sku":"DE-YQ03198"},{"sku":"DE-XD03765-02"},{"sku":"DE-XDOT40400"},{"sku":"DE-XD03751-03"},{"sku":"DE-XD03755-02"},{"sku":"DE-TJ19541"},{"sku":"DE-TJA02798-02"},{"sku":"DE-XD03749-02"},{"sku":"DE-TJ19486"},{"sku":"DE-TJ19483"},{"sku":"DE-TJ19049"},{"sku":"DE-TJ19050"},{"sku":"DE-TJ19144"},{"sku":"DE-TJ19047"},{"sku":"DE-TJ12356"},{"sku":"DE-TJ07795"},{"sku":"DE-TJ12348"},{"sku":"DE-TJ04801"},{"sku":"DE-TJ07791"},{"sku":"DE-QCMP0408XXL"},{"sku":"DE-SJ01886"},{"sku":"DE-QC33875"},{"sku":"DE-QCMP0408LL"},{"sku":"DE-QC33868"},{"sku":"DE-QC33870"},{"sku":"DE-QC33866-01"},{"sku":"DE-QC33866-02"},{"sku":"DE-QC28277"},{"sku":"DE-QC28282"},{"sku":"DE-QC14466"},{"sku":"DE-QC14476"},{"sku":"DE-QC10384"},{"sku":"DE-QC10395"},{"sku":"DE-QC14045"},{"sku":"DE-QC10072"},{"sku":"DE-QC10352"},{"sku":"DE-QC10030-02"},{"sku":"DE-QC10030-02"},{"sku":"DE-QC10030-01"},{"sku":"DE-QC10030-01"},{"sku":"DE-QC09688"},{"sku":"DE-QC06161"},{"sku":"DE-QC06559"},{"sku":"DE-QC06947"},{"sku":"DE-QC09011"},{"sku":"DE-QC04090"},{"sku":"DE-QC04606-02"},{"sku":"DE-QC02157"},{"sku":"DE-QC03799"},{"sku":"DE-QC00627"},{"sku":"DE-QC01270"},{"sku":"DE-JYA02631"},{"sku":"DE-JY39427"},{"sku":"DE-JYA00923"},{"sku":"DE-JY39038"},{"sku":"DE-JY39425"},{"sku":"DE-JY37585"},{"sku":"DE-JY38963"},{"sku":"DE-JY36100-03"},{"sku":"DE-JY37213"},{"sku":"DE-JY34975-02"},{"sku":"DE-JY35796"},{"sku":"DE-JY32281"},{"sku":"DE-JY34975-01"},{"sku":"DE-JY28262"},{"sku":"DE-JY28317-03"},{"sku":"DE-JY24508"},{"sku":"DE-JY27135"},{"sku":"DE-JY23207-02"},{"sku":"DE-JY23207-02"},{"sku":"DE-JY23207-01"},{"sku":"DE-JY20695"},{"sku":"DE-JY23207-01"},{"sku":"DE-JY18128"},{"sku":"DE-JY19338"},{"sku":"DE-JY19340"},{"sku":"DE-JY14392"},{"sku":"DE-JY15594"},{"sku":"DE-JY16978"},{"sku":"DE-JY07961"},{"sku":"DE-JY05402"},{"sku":"DE-JY06443"},{"sku":"DE-JY07784"},{"sku":"DE-JY07953"},{"sku":"DE-JY05365"},{"sku":"DE-JY01841"},{"sku":"DE-JY04081"},{"sku":"DE-JY04081"},{"sku":"DE-JM24373"},{"sku":"DE-JM21769"},{"sku":"DE-JM23828"},{"sku":"DE-JF29000"},{"sku":"DE-GS06853"},{"sku":"DE-GS07293"},{"sku":"DE-GS08139"},{"sku":"DE-JF28900"},{"sku":"DE-GS02661"},{"sku":"DE-GS05409"},{"sku":"DE-GS06832-02"},{"sku":"DE-GS02652"},{"sku":"DE-GS01594"},{"sku":"DE-GS01595"},{"sku":"DE-GS01595"},{"sku":"DE-BB01887"},{"sku":"DE-CW05804"},{"sku":"DE-GS00245"},{"sku":"CA-ZM02746"},{"sku":"CA-JY03764-03"},{"sku":"CA-JYB00622"},{"sku":"CA-JYB00624"},{"sku":"CA-JY00634-01"},{"sku":"AU-ZM02748-01"},{"sku":"AU-ZM02750"},{"sku":"AU-ZM03297"},{"sku":"AU-ZP29134"},{"sku":"AU-ZP29137"},{"sku":"AU-ZP29165"},{"sku":"AU-YQ03539"},{"sku":"AU-YQ03539"},{"sku":"AU-YQ03538"},{"sku":"AU-YQ03538"},{"sku":"AU-YQ03537"},{"sku":"AU-YQ03537"},{"sku":"AU-YQ03200"},{"sku":"AU-YQ03198"},{"sku":"AU-YQ03199"},{"sku":"AU-YB01802"},{"sku":"AU-TJ06020"},{"sku":"AU-TJ06025"},{"sku":"AU-TJ19486"},{"sku":"AU-TJ19541"},{"sku":"AU-TJ03914"},{"sku":"AU-TJ05563"},{"sku":"AU-TJ05948"},{"sku":"AU-QCOT1208-10"},{"sku":"AU-QC29573"},{"sku":"AU-QC33866-01"},{"sku":"AU-QC33866-02"},{"sku":"AU-QC33868"},{"sku":"AU-QC06559"},{"sku":"AU-QC10352"},{"sku":"AU-QC27305"},{"sku":"AU-QC06478"},{"sku":"AU-QC05808"},{"sku":"AU-QC06161"},{"sku":"AU-QC06436"},{"sku":"AU-QC06437"},{"sku":"AU-QC02396"},{"sku":"AU-JYB00624"},{"sku":"AU-QC00980"},{"sku":"AU-QC00980"},{"sku":"AU-QC01727"},{"sku":"AU-JY38963"},{"sku":"AU-JY39425"},{"sku":"AU-JY37843"},{"sku":"AU-JY37213"},{"sku":"AU-JY37585"},{"sku":"AU-JY37842"},{"sku":"AU-JY36188"},{"sku":"AU-JY35796"},{"sku":"AU-JY36100-03"},{"sku":"AU-JY36181"},{"sku":"AU-JY35233"},{"sku":"AU-JY35268"},{"sku":"AU-JY23008-02"},{"sku":"AU-JY31176"},{"sku":"AU-JY18706"},{"sku":"AU-JY23008-01"},{"sku":"AU-JY08608"},{"sku":"AU-JY11663"},{"sku":"AU-JY08064"},{"sku":"AU-JY08067"},{"sku":"AU-JY04682"},{"sku":"AU-JY04682"},{"sku":"AU-JY02746"},{"sku":"AU-JY04081"},{"sku":"AU-JY04081"},{"sku":"AU-JM14516"},{"sku":"AU-JM24373"},{"sku":"AU-JM11337"},{"sku":"AU-GS05832"},{"sku":"AU-GS07293"},{"sku":"AU-JM11072"},{"sku":"AU-GS01595"},{"sku":"AU-GS05547"},{"sku":"AU-GS00089"},{"sku":"AU-GS01026"},{"sku":"AU-GS01594"},{"sku":"AU-AF04270"},{"sku":"AU-AF04273"},{"sku":"AU-CW05804"},{"sku":"AU-AF04267"},{"sku":"AU-06ZH-DTU20"}]';
        $datas = json_decode($data,true);
        $deleteData=[];
        foreach($datas as $key=>$value){
            $deleteData[] = $value['sku'];
        }
        $this->db->where_in("sku",$deleteData)->delete('sku_avg_delivery_time');
        echo $this->db->last_query();
    }
}