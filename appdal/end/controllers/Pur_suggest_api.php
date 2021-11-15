<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

use Swoole\Coroutine;
use function Swoole\Coroutine\run;
use Swoole\Coroutine\WaitGroup;
//use Swoole;

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Pur_suggest_api extends MY_API_Controller {
    public $_java_access_taken = null;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_suggest/Purchase_suggest_model');
        $this->load->model('purchase_suggest/Purchase_demand_lock');
        $this->load->library('Monolog');

        $this->_java_access_taken = getOASystemAccessToken();
    }

    /**
     * 获取需求单类型数据
     * @author:luxu
     * @time:2021年2月27号
     **/
    public function push_demand_type(){

        $data = file_get_contents('php://input');

        if(empty($data)){
            $this->error_json('未接收到数据');
        }
        $data = json_decode($data,true);

        $result = $this->Purchase_suggest_model->push_demand_type($data);
        if($result){
            $this->success_json();
        }else{
            $this->error_json('操作失败');
        }

    }

    /**
     * 获取 固定的头部信息
     * @return array
     */
    public function getHeaders(){
        $header = ['Content-Type: application/json'];
        return $header;
    }

    /**
     * 接收推送的备货单数据（From 老采购系统MRP）
     * @url pur_suggest_api/receive_demand_data_v2
     */
    public function receive_demand_data_v2(){
        $data = file_get_contents('php://input');
        apiRequestLogInsert(
            [
                'record_number' => 'receive_demand_data_v2',
                'record_type'   => 'receive_demand_data_v2',
                'post_content'  => $data
            ]
        );
        if(empty($data)){
            $this->error_json('未接收到数据');
        }
        $data = json_decode($data,true);
        isset($data['body']) and $data = $data['body'];

        if(!isset($data['suggest_list'])){
            $this->error_json('suggest_list数据缺失[body->suggest_list]');
        }
        if(!isset($data['suggest_list'])){
            $this->error_json('suggest_list数据错误');
        }
        $suggest_list = $data['suggest_list'];
        if(empty($suggest_list)){
            $this->error_json('未接收到suggest_list数据');
        }
        if(!is_array($suggest_list)){
            $this->error_json('suggest_list数据解析失败[未解析到数组]');
        }
        $result = $this->purchase_suggest_model->receive_demand_data_v2($suggest_list);
        if($result['code']){
            $return_data = [
                'status' => 200,
                'error_list' => $result['error_list'],
                'errorMess' => '操作成功'
            ];
        }else{
            $return_data = [
                'status' => 500,
                'error_list' => [],
                'errorMess' => $result['message']
            ];
        }

        http_response($return_data);
    }

    /**
     * 批量匹配中转仓规则更新 warehouse_code,warehouse_name
     */
    public function batch_get_warehouse_info()
    {
        die("此方法永远未执行过，没有满足条件的数据，失效");// @author:Jolon @date:2020-12-02

        $demand_number = $this->input->get_post('demand_number');
        $where = 's.is_get_warehouse=0 AND s.warehouse_code="" AND s.purchase_type_id=1';
        if(!empty($demand_number)){
            $where.=' AND s.demand_number="'.$demand_number.'"';
        }

        //查找总数
        $count_num = $this->db->select('a.id')
            ->from('purchase_suggest as s')
            ->where($where)
            ->order_by('s.id DESC')
            ->count_all_results();

        //读取配置文件参数，获取推送地址
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('yibaiLogistics'))) {
            $service_data_info = $this->config->item('yibaiLogistics');
            $_batch_get_warehouse_info = isset($service_data_info['batch_get_warehouse_info'])?$service_data_info['batch_get_warehouse_info']:'';
            if(empty($_batch_get_warehouse_info)){
                exit('获取匹配中转仓规则地址缺失');
            }
            $get_warehouse_info_url = $_batch_get_warehouse_info;
        }

        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 300;//每次推送300条
        }

        if($count_num>=1){
            $post_data = [];
            $update_data = [];

            //每分钟只能执行3000条数据,因此控制更新数量,避免定时任务重复执行
            if($count_num>2000){
                $count_num = 2000;
            }

            try {
                for ($i=1; $i <=ceil($count_num/$limit) ; $i++) {
                    $offset = ($i-1)*$limit;
                    $suggest_list = $this->db->select(
                        's.id,
                         s.sku,
                         s.purchase_type_id as serviceTypeId
                        '
                    )
                        ->from('purchase_suggest as s')
                        ->limit($limit,$offset)
                        ->where($where)
                        ->order_by('s.id DESC')
                        ->get()
                        ->result_array();

                    $clone_data = $suggest_list;

                    if(!empty($suggest_list)){
                        foreach ($suggest_list as &$v){
                            //转换业务线
                            if($v['serviceTypeId'] == PURCHASE_TYPE_INLAND){
                                $v['serviceTypeId'] = 3;
                            }
                            unset($v['id']);
                        }

                    }

                    $post_data['list'] = $suggest_list;
                    $header = $this->getHeaders();
                    if(stripos($get_warehouse_info_url,'access_token') === false ) $get_warehouse_info_url .= "?access_token=".$this->_java_access_taken;
                    $result = getCurlData($get_warehouse_info_url,json_encode($post_data),'post',$header);

                    $result = json_decode($result,true);
                    if ($result['code']!=0) {//调取失败
                        //默认值 易佰东莞仓库 SZ_AA
                        foreach ($clone_data as $value){
                            $update_data[] = [
                                'id' => $value['id'],
                                'warehouse_code' => 'SZ_AA',
                                'warehouse_name' => '易佰东莞仓库',
                                'is_get_warehouse' => 2,
                            ];
                        }
                    }else{
                        foreach ($result['data'] as $key => $value){
                            if (!empty($value['warehouseInfoList'])){
                                $update_data[] = [
                                    'id' => $clone_data[$key]['id'],
                                    'warehouse_code' => isset($value['warehouseInfoList'][0]['warehouseCode'])?$value['warehouseInfoList'][0]['warehouseCode']:'SZ_AA',
                                    'warehouse_name' => isset($value['warehouseInfoList'][0]['warehouseName'])?$value['warehouseInfoList'][0]['warehouseName']:'易佰东莞仓库',
                                    'is_get_warehouse' => 1,
                                ];
                            }else{
                                $update_data[] = [
                                    'id' => $clone_data[$key]['id'],
                                    'warehouse_code' => 'SZ_AA',
                                    'warehouse_name' => '易佰东莞仓库',
                                    'is_get_warehouse' => 1,
                                ];
                            }

                        }
                    }
                    $update_res = $this->db->update_batch('purchase_suggest', $update_data,'id');

                    if(empty($update_res)) throw new Exception("批量匹配中转仓规则更新失败");

                    apiRequestLogInsert([
                        'api_url'=>$get_warehouse_info_url,
                        'record_type'=>'更新匹配中转仓规则',
                        'post_content'=>json_encode($post_data),
                        'response_content' => $result,
                        'create_time'=>date('Y-m-d H:i:s')
                    ]);

                    echo 'ok';

                }
            }catch (Exception $e) {

                exit($e->getMessage());
            }
        }else{
            exit('没有需要更新的数据');
        }
    }

    //接收物流系统推送的需求单的采购仓库和目的仓
    public function receive_logistics_warehouse()
    {
        $data = file_get_contents('php://input');
        apiRequestLogInsert(
            [
                'record_number' => 'receive_logistics_warehouse',
                'record_type'   => '接收物流系统推送需求单采购仓库及目的仓',
                'post_content'  => $data
            ]
        );
        if(empty($data)){
            $this->error_json('未接收到数据');
        }
        $data = json_decode($data,true);
        if(!isset($data['suggest_list'])){
            $this->error_json('suggest_list数据缺失');
        }
        $suggest_list = $data['suggest_list'];
        if(!is_array($suggest_list)){
            $this->error_json('suggest_list数据解析失败[未解析到数组]');
        }

        $return = ['status' => 1,'error_list'=>[],'message' => ''];
        $error_list = [];

        foreach ($suggest_list as $key => &$value){
            $demand_number = isset($value['demand_number'])?$value['demand_number']:null;
            if(empty($demand_number)){
                $error_list[] = 'demand_number缺失';
                unset($suggest_list[$key]);
                continue;
            }

            //转换物流的业务线
            if($value['purchase_type_id'] == 3){//海外仓的目的仓不能空
                if (empty($value['destination_warehouse'])){
                    $error_list[$demand_number] = "目的仓缺失";
                    unset($suggest_list[$key]);
                    continue;
                }

                $value['purchase_type_id'] = 2;
            }elseif($value['purchase_type_id'] == 2){
                $value['purchase_type_id'] = 1;
            }elseif($value['purchase_type_id'] == 1){
                $value['purchase_type_id'] = 3;
            }

            if(empty($value['warehouse_code']) || empty($value['warehouse_name'])){
                $error_list[$demand_number] = "采购仓库缺失";
                unset($suggest_list[$key]);
                continue;
            }
        }
        if (!empty($suggest_list)){
            $update_res = $this->db->update_batch('purchase_suggest', $suggest_list,'demand_number');

            if ($update_res){
                $return['message'] = '更新成功';
                $return['error_list'] = $error_list;
            }else{
                $return['message'] = '更新失败';
                $return['status'] = 500;
            }
        }else{
            $return['message'] = '更新失败';
            $return['error_list'] = $error_list;
            $return['status'] = 0;
        }

        http_response($return);
    }

    /**
     * 接收ERP推送的备货单数据
     * @url pur_suggest_api/receive_suggest_data_from_erp
     * @author Sinder
     * @date 2019-05-29
     */
    public function receive_suggest_data_from_erp(){
        $log_conf = [
            'channel'=>'plan_manual_suggest_data',
            'level'=>'debug'
        ];
        //初始化日志插件
        $monolog = new Monolog($log_conf);
        $redisLocks = $this->Purchase_demand_lock->get_redis_lock_exists();
        if( True == $redisLocks){
            $this->error_json('采购系统正在合单中');
        }
        $data_json = file_get_contents('php://input');
        $monolog->info($data_json);//记录推送原始数据
        $data = json_decode($data_json,true);


        if($data){
            $result = $this->purchase_suggest_model->receive_suggest_data_from_erp($data);

            apiRequestLogInsert(
                [
                    'record_number'    => date('Y-m-d H:i:s'),
                    'record_type'      => 'receive_suggest_data_from_erp',
                    'api_url'          => 'services/purchase/Purchasesuggest/Purchase',
                    'post_content'     => $data_json,
                    'response_content' => json_encode($result['data']),
                    'status'           => $result['code']?1:0
                ]);

            if($result['code']){
                $this->success_json($result['data'], '', '执行成功');
            }else{
                $this->error_data_json($result['data'],$result['message']);
            }
        }else{
            $this->error_json('数据缺失');
        }
    }

    /**
     * 接收ERP同步请求（ERP 获取需求单在采购系统下单情况）
     * @url pur_suggest_api/sync_suggest_to_erp
     * @author Sinder
     * @date 2019-05-31
     */
    public function sync_suggest_to_erp(){
        $data_json = file_get_contents('php://input');
        $data = json_decode($data_json,true);
        if($data){
            $result = $this->purchase_suggest_model->sync_suggest_to_erp($data);

            apiRequestLogInsert(
                [
                    'record_number'    => date('Y-m-d H:i:s'),
                    'record_type'      => 'sync_suggest_to_erp',
                    'api_url'          => 'sync_purchase',
                    'post_content'     => $data_json,
                    'response_content' => json_encode($result),
                    'status'           => 1
                ]);
            $this->success_json($result, '', '执行成功');
        }else{
            $this->error_json('数据缺失');
        }
    }

    /**
     * 接收仓库推送的备货单数据
     * @url pur_suggest_api/receive_suggest_data_from_warehouse
     * @author Jeff
     * @date 2019-05-29
     */
    public function receive_suggest_data_from_warehouse(){

        $data_json = $this->input->get_post('stock');

        if (empty($data_json)) {
            $msg = ['code'=>'500','msg'=>'无数据'];
            echo json_encode($msg);
            exit;
        }

        $data = json_decode($data_json,true);

        header('Content-Type: application/json;charset=utf-8');
        if($data){

            apiRequestLogInsert(
                [
                    'record_type'      => '接收仓库推送过来的需求单',
                    'api_url'          => '',
                    'post_content'     => '',
                    'response_content' => $data_json,
                    'status'           => 1
                ]);

            if(!is_array($data)){
                $msg = ['code'=>'500','msg'=>'数据格式错误'];
                echo json_encode($msg);
                exit;
            }

            // 转换数据格式，三维数组转二维数组
            $data_list = [];
            foreach($data as $value_list){
                foreach($value_list as $value_item){
                    $data_list[] = $value_item;
                }
            }

            $result = $this->purchase_suggest_model->receive_suggest_data_from_warehouse($data_list);
            if($result['code']=='200'){
                $msg = ['code'=>$result['code'],'sku'=>$result['sku'],'msg'=>'success','error_list' => $result['error_list']];
                echo json_encode($msg);
                exit;
            }else{
                $msg = ['code'=>'500','msg'=>$result['message']];
                echo json_encode($msg);
                exit;
            }

        }else{
            $msg = ['code'=>'500','msg'=>'无数据'];
            echo json_encode($msg);
            exit;
        }
    }

    //实单锁单
    public function plan_create_entities_lock_list()
    {
        $result = $this->purchase_suggest_model->plan_create_entities_lock_list();

        if ($result['code']!=200){
            echo $result['message'];
            //记录日志
            file_put_contents(date('Y-m-d').'锁单失败.txt',$result['message'],FILE_APPEND);
        }else{
            echo $result['message'];
        }
    }

    /**
     * function: MQ 获取计划系统推送备货单信息
     * @params author:luxu
     * @time:2020/8/31
     **/
    public function pushReceive_demand_data(){

        ini_set('max_execution_time','18000');

        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('DEMAND_STOCKLIST_DATA'); //  DEMAND_STOCKLIST_DATA
        $mq->setExchangeName('STOCKLIST');  //STOCKLIST
        $mq->setRouteKey('DEMAND_STOCKLIST_DATA_KEY'); // DEMAND_STOCKLIST_DATA_KEY
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        //构造存入数据
        //存入消息队列
        $queue_obj = $mq->getQueue();
        //处理生产者发送过来的数据
        $envelope = $queue_obj->get();
        $data = NULL;
        if ($envelope) {
            $data = $envelope->getBody();

            $datas = json_decode($data,true);

            $result = $this->purchase_suggest_model->receive_demand_data([$datas]);
            print_r($result);
            $queue_obj->ack($envelope->getDeliveryTag());
            $mq->disconnect();
        }
    }


    private function return_demand_datas($datas){

    }




    /**
     * 需求：30696 一键合单(2)需求单页面新增功能"一键转为备货单","一键合单"
    定时任务:每次计划系统推送完一批需求单之后,10分钟内,无新的需求单进入时,即开始自动执行任务(执行一次):
    1.SKU是否重复=否的,按照"一键转为备货单"的规则,自动生成备货单
    2.SKU是否重复=是的,按照"一键合单"的规则,自动合单生成备货单,合单不成功的需求也自动转为备货单
    3.业务线=海外,FBA大货的,不参与定时任务
     * @author:luxu
     * @time:2021年3月6号
     **/

    public function meregeDemandToSuggest()
    {
        ini_set('max_execution_time','18000');
        ini_set('memory_limit','1024M');

        $start_time = time();
        $max_time   = 300 - 30;// 最大允许执行时间阀：时间与 pull_set_lock 方法保持一致

        $this->load->helper('status_product');
        self::saveMeregeDemandToSuggestSwooleLog('开始任务','old');

        // 定时任务未开启
        $demandConfig = $this->db->from("purchase_demand_config")
            ->where("status", 1)
            ->select("module_flag,status")
            ->get()
            ->result_array();
        if (empty($demandConfig)) return false;
        $demandConfig = array_column($demandConfig, NULL, "module_flag");

        // 获取需求单表计划系统推送的最后一条数据
        $lastDemandDatas = $this->db->select("id,create_time")
            ->from("purchase_demand")
            ->where("source_from", 1)
            ->where_in('demand_status',[SUGGEST_STATUS_NOT_FINISH,DEMAND_SKU_STATUS_CONFIR])
            ->where('demand_lock',DEMAND_SKU_STATUS_NO_LOCK)
            ->where('is_abnormal_lock',0)
            ->where('erp_id',0)
            ->order_by("id DESC")
            ->limit(1)
            ->get()
            ->row_array();

        // 如果获取数据不为空的情况下
        if (!empty($lastDemandDatas)) {
            self::saveMeregeDemandToSuggestSwooleLog('开始执行任务','old');

            //每次计划系统推送完一批需求单之后,2分钟内,无新的需求单进入时,即开始自动执行任务(执行一次)
            $minute = floor((strtotime(date("Y-m-d H:i:s", time())) - strtotime($lastDemandDatas['create_time'])) % 86400 / 60);
            if ($minute >= 1) {
                $limit = 2000;

                $lock_flag = $this->Purchase_demand_lock->pull_set_lock(); // 获取到REDIS进程锁
                if(False == $lock_flag){
                    self::saveMeregeDemandToSuggestSwooleLog('未获取到进程锁.....','old');

                    echo "未获取到进程锁.....";die();
                }

                // 一键转换为备货单
                // 开始获取数据，创建时间小于最大时间，并且SKU 是否重复=否。按照一键转换为备货单
                $clientDatas                    = [];// 初始化参数
                $clientDatas['demand_lock']     = DEMAND_SKU_STATUS_NO_LOCK; //查询未锁单数据
                $clientDatas['is_abnormal_lock'] = 0;
                $clientDatas['offset']          = 0;
                $clientDatas['limit']           = 1;
                $clientDatas['erp_id']          = false;

                //开始一键转换备货单的操作,如果需求单转换为备货单定时任务开启
                if (isset($demandConfig['suggest']) && ($demandConfig['suggest']['status'] == 1)) {
                    self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【需求单转备货单】开始','old');

                    $clientDatas['demand_repeat']   = 2; // 不重复
                    $clientDatas['is_mereges']      = true;
                    $total = $this->purchase_suggest_model->get_demand_datas($clientDatas,'sum');// sum查询总数

                    self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【需求单转备货单】开始，总共'.$total.'条数据','old');

                    if ($total > 0) {
                        $page = ceil($total / $limit); // 分页处理数据
                        for ($i = 1; $i <= $page; ++$i) {
                            if(time() - $start_time > $max_time){// 任务本次执行时间 超过 最大允许时间自动退出
                                self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【需求单转备货单】,进度【'.$i.'/'.$page.'】执行超过最大允许时间，自动退出','old');
                                break;
                            }

                            self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【需求单转备货单】,进度【'.$i.'/'.$page.'】开始','old');

                            $clientDatas['offset'] = 0;// 每次查询新的数据，不需要偏移量
                            $clientDatas['limit']  = $limit;
                            $result = $this->purchase_suggest_model->get_demand_datas($clientDatas,'system_merge');


                            if (!empty($result) && !empty($result['values'])) {// 需求单不为空的情况下
                                // 任务拆分，每个任务50条数据
                                $result_values_chunk = array_chunk($result['values'],200);

                                foreach($result_values_chunk as $chunk_item_key => $result_values_item){
                                    $process_log = ($chunk_item_key + 1).'-'.$i.'-'.$page;

                                    try {
                                        // 开启事务（事务尽可能小）
                                        //$this->db->trans_begin();

                                        $suggestResult = $this->purchase_suggest_model->transferToStandbyOrder($result_values_item);
                                        if (True == $suggestResult) {
                                            // 同时"全部需求单"页面,需求单状态变为"已生成备货单"
                                            $demandIds = array_column($result_values_item, 'id');
                                            $this->purchase_suggest_model->updateDemandStatus($demandIds, $status = ['demand_status' => 3]);
                                        } else {
                                            // 转换为备货单失败抛出异常
                                            throw new Exception("需求单转换为备货单失败");
                                        }

                                        //$this->db->trans_commit();// 数据合成完毕提交事务
                                        self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【需求单转备货单】,Chunk明细【'.$process_log.'】成功','old');

                                    } catch (Exception $exp) {

                                        //$this->db->trans_rollback();// 抛出异常数据回滚
                                        self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【需求单转备货单】,Chunk明细【'.$process_log.'】失败，[错误原因]'.$exp->getMessage(),'old');
                                    }

                                }

                            } else {
                                break;
                            }


                            self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【需求单转备货单】,进度【'.$i.'/'.$page.'】结束','old');
                        }
                    }

                }

                // 开始合单操作
                if (isset($demandConfig['merge']) && $demandConfig['merge']['status'] == 1) {
                    $limit = 50;
                    self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【合单】开始','old');

                    // 查询参数
                    $clientDatas                        = [];// 初始化参数
                    $clientDatas['demand_repeat']       = 1; //重复
                    $clientDatas['purchase_type_id']    = [PURCHASE_TYPE_INLAND, PURCHASE_TYPE_FBA, PURCHASE_TYPE_PFB, PURCHASE_TYPE_PFH];// 需求单业务线
                    $clientDatas['demand_status']       = [SUGGEST_STATUS_NOT_FINISH, DEMAND_SKU_STATUS_CONFIR]; // 需求单状态
                    $clientDatas['offset']              = 0;
                    $clientDatas['limit']               = 1;
                    $clientDatas['is_abnormal_lock']    = 0;

                    $clientDatas['backstage']           = 1; // 后台读取数据标识
                    $clientDatas['is_mereges']          = true;

                    // 查询结果，计算总页数
                    $totalMerge = $this->purchase_suggest_model->get_demand_datas($clientDatas,'sum');
                    $page = ceil($totalMerge / $limit);

                    self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【合单】开始，总共'.$totalMerge.'条数据','old');

                    for ($i = 1; $i <= $page; ++$i) {
                        if(time() - $start_time > $max_time){// 任务本次执行时间 超过 最大允许时间自动退出
                            self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【合单】,进度【'.$i.'/'.$page.'】执行超过最大允许时间，自动退出','old');
                            break;
                        }
                        self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【合单】,进度【'.$i.'/'.$page.'】开始','old');

                        // 重置查询条件Start
                        $clientDatas['offset']  = 0;// 每次查询新的数据，不需要偏移量
                        $clientDatas['limit']   = $limit;
                        $clientDatas['backstage'] = 1; // 后台读取数据标识
                        unset($clientDatas['sku']);
                        // 重置查询条件End

                        // 合单数据量不大，全部查询出来
                        //unset($clientDatas['offset'], $clientDatas['limit']);
                        $result = $this->purchase_suggest_model->get_demand_datas($clientDatas);

                        // 根据每页查询的SKU，重新到数据表里面查询一次
                        if (!empty($result['values'])) {
                            // 获取查询SKU 集合
                            $resultSkus = array_unique(array_column($result['values'], "sku"));
                            $clientDatas['sku'] = $resultSkus;

                            unset($clientDatas['backstage']);
                            unset($clientDatas['offset'], $clientDatas['limit']);
                            $resultSkuDatas = $this->purchase_suggest_model->get_demand_datas($clientDatas);

                            if (!empty($resultSkuDatas['values'])) {
                                $nodata = [];
                                foreach ($resultSkuDatas['values'] as $merge_key => $merge_value) {

                                    // 需求单业务线=国内/FBA/PFB/平台头程,需求单状态=未完结,待重新确认
                                    if (true == false && !in_array($merge_value['purchase_type_id'], [PURCHASE_TYPE_INLAND, PURCHASE_TYPE_FBA, PURCHASE_TYPE_PFB, PURCHASE_TYPE_PFH])
                                        && !in_array($merge_value['demand_status'], [SUGGEST_STATUS_NOT_FINISH, DEMAND_SKU_STATUS_CONFIR])
                                    ) {
                                        $nodata[] = $merge_value['demand_number'];
                                    }

                                    // 如果SKU 不重复
                                    if ($merge_value['demand_repeat'] == DEMAND_SKU_NO_REPEAT) {
                                        $nodata[] = $merge_value['demand_number'];
                                    }
                                }
                                $mereDatas = $nosuggestdata =[];
                                foreach($resultSkuDatas['values'] as $new_merekey=>$new_merevalue){
                                    if( !in_array($new_merevalue['demand_number'],$nodata)){
                                        $mereDatas[] = $new_merevalue;
                                    }else{
                                        $nosuggestdata[] = $new_merevalue;
                                    }
                                }

                                try {
                                    if (!empty($mereDatas)) {
                                        $this->db->trans_begin(); // 开启事务
                                        $results = $this->purchase_suggest_model->mereSuggest($mereDatas);
                                        $mereDatasIds = array_column($mereDatas, 'id');
                                        $updateDemands = $this->purchase_suggest_model->update_demand(['id' => $mereDatasIds], ['demand_status' => 3], true);

                                        if (True == $results && $updateDemands) {
                                            $this->db->trans_commit(); // 提交事务
                                        }
                                    }
                                }catch ( Exception $exp ){
                                    $this->db->trans_rollback();
                                    $this->Purchase_demand_lock->unlock();

                                    self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【合单】,进度【'.$i.'/'.$page.'】结束，[错误原因]'.$exp->getMessage(),'old');
                                }
                            }
                        }

                        self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【合单】,进度【'.$i.'/'.$page.'】结束','old');
                    }

                    self::saveMeregeDemandToSuggestSwooleLog('开始执行任务【合单】结束','old');
                }
                $this->Purchase_demand_lock->unlock();
            }
        }

        self::saveMeregeDemandToSuggestSwooleLog('开始结束','old');
        echo 'Success';exit;
    }
    
    /**
     * 记录日志
     * @param $msg
     * @param $id
     */
    private static function saveMeregeDemandToSuggestSwooleLog($msg,$id = 'swoole'){
        if($id == 'swoole'){
            $id = 'meregeDemandToSuggestSwoole';
        }else{
            $id = 'meregeDemandToSuggest';
        }
        operatorLogInsert(
            [
                'id'      => $id,
                'type'    => 'merge',
                'content' => $msg,
            ]
        );
    }


    /**
     * 需求单解锁流程
     * @author:luxu
     * @time:2021年3月6号
     **/
    public function demand_unlock(){

        $nowTime = date("Y-m-d H:i:s",time()); //获取当前时间
        $limit = 1000;
        $total = $this->db->from("purchase_demand")->where("over_lock_time<=",$nowTime)->where("demand_lock",1)->count_all_results();
        //echo "total=".$total;die();
        if($total>=1){

            $page = ceil($total/$limit);
            for($i=1;$i<=$page;++$i){
                $limit         = $limit;
                $offset        = ($i - 1) * $limit;

                $results = $this->db->from("purchase_demand")->where("over_lock_time<=",$nowTime)->where("demand_lock",1)
                    ->limit($limit,$offset)->select("id")->get()->result_array();
                //echo $this->db->last_query();die();
                if(!empty($results)){

                    $demandIds = array_column($results,"id");
                    $this->db->where_in("id",$demandIds)->update("purchase_demand",['demand_lock'=>2]);
                }

            }
        }
    }

    /**
     * 需求单转换为备货单后推送到计划系统
     * @author:luxu
     * @time:2021年3月9号
     **/

    public function pushDemandPlan(){

        $where = [

            'demand_status' => [DEMAND_TO_SUGGEST,DEMAND_MERGE_SUGGEST,6],
            'is_push_plan' => 0
        ];

        $listQuery = $this->db->from("purchase_demand")->where_in("demand_status",[DEMAND_TO_SUGGEST,DEMAND_MERGE_SUGGEST,6])
            ->where("is_push_plan",0);
        $limit = 100;
        $total = $listQuery->count_all_results();
        if( $total >0) {
            $page = ceil($total / $limit);
            for($i=1;$i<=$page;++$i){

                $offset = ($i-1)*$limit;
                $result = $this->db->from("purchase_demand")->where_in("demand_status",[DEMAND_TO_SUGGEST,DEMAND_MERGE_SUGGEST,6])
                    ->where("is_push_plan",0)->limit($limit,$offset)->get()->result_array();
                $push_data = [];
                if(!empty($result)){

                    foreach($result as $key=>$value) {
                        if($value['source_from'] == 1) {
                            $push_data[] = [

                                'id' => $value['id'],
                                'demand_number' => $value['demand_number'],//备货单号
                                'audit_status' => SUGGEST_AUDITED_PASS,//审核未通过
                                'audit_time' => date('Y-m-d H:i:s', time()),//审核时间
                                'business_line' => $value['purchase_type_id'],//业务线
                                'is_distribution' => $value['is_distribution'], //1表示“否”，2表示“是”

                            ];
                        }
                    }
                }

                if (!empty($push_data)) {
                    $update_push_data = $push_data;
                    $push_data['data_list'] = json_encode($push_data);
                    $push_data = json_encode($push_data);
                    $access_token = getOASystemAccessToken();

                    //推送计划系统
                    $url = getConfigItemByName('api_config', 'java_system_plan', 'push_audit_suggest');
                    $url    = $url.'?access_token='.$access_token;
                    $header = ['Content-Type: application/json'];
                    $result = getCurlData($url, $push_data, 'POST',$header);
                    $result = json_decode($result,True);
                    if (isset($result['code']) && $result['code']=200){
                        $demandDatas = array_column($update_push_data,"demand_number");
                        $this->db->where_in("demand_number",$demandDatas)->update("purchase_demand",['is_push_plan'=>1]);
                    }else{
                        echo '推送计划返回信息, '.isset($result['error_description'])?$result['error_description']:$result['message'];
                        //throw new Exception('推送计划返回信息, '.($result['error_description'])?$result['error_description']:$result['message']);
                    }
                }
            }
        }
    }

    public function demandDatas(){
        ini_set('max_execution_time','9000');
        $sql = "select * from pur_purchase_suggest WHERE suggest_status=1 AND audit_status=1 LIMIT 100";
        $result = $this->db->query($sql)->result_array();
        //$slaveDb=$this->load->database('slave',TRUE);


        foreach($result as $key=>$value){

            $insert =[

                'sku' => $value['sku'],
                'is_boutique' =>$value['is_boutique'],
                'is_expedited' => $value['is_expedited'],
                'demand_number' => $value['demand_number'],
                'warehouse_code' => $value['warehouse_code'],
                'warehouse_name' => $value['warehouse_name'],
                'sales_note' => $value['sales_note2'],
                'tovoid_reason' => '',
                'is_drawback' => $value['is_drawback'],
                'is_overseas_first_order' => $value['is_overseas_first_order'],
                'create_time' => $value['create_time'],
                'shipment_type' =>$value['shipment_type'],
                'logistics_type' =>$value['logistics_type'],
                'extra_handle' =>$value['extra_handle'],
                'sales_name' => $value['sales_name'],
                'sales_group' =>$value['sales_group'],
                'platform' =>$value['platform'],
                'site' =>$value['site'],
                'sales_account' => $value['sales_account'],
                'purchase_type_id' => $value['purchase_type_id'],
                'earliest_exhaust_date' => $value['earliest_exhaust_date'],
                'destination_warehouse' => $value['destination_warehouse'],
                'demand_status' =>1,
                'transformation' =>0,
                'is_new' =>$value['is_new'],
                'gid' =>$value['gid'],
                'plan_product_arrive_time' =>$value['plan_product_arrive_time'],
                'fba_purchase_qty' => $value['fba_purchase_qty'],
                'country' => $value['country'],
                'es_shipment_time' => $value['es_shipment_time'],
                'source_from' =>$value['source_from'],
                'inland_purchase_qty' => $value['inland_purchase_qty'],
                'pfh_purchase_qty' => $value['pfh_purchase_qty'],
                'product_img_url' => $value['product_img_url'],
                'product_name' =>$value['product_name'],
                'two_product_line_id' => $value['two_product_line_id'],
                'two_product_line_name' =>$value['two_product_line_name'],
                'product_line_id' => $value['product_line_id'],
                'product_line_name' =>$value['product_line_name'],
                'supplier_code' =>$value['supplier_code'],
                'supplier_name' =>$value['supplier_name'],
                'purchase_unit_price' =>$value['purchase_unit_price'],
                'purchase_total_price' =>$value['purchase_total_price'],
                'developer_id' =>$value['developer_id'],
                'developer_name' =>$value['developer_name'],
                'buyer_id' =>$value['buyer_id'],
                'buyer_name' =>$value['buyer_name'],
                'is_cross_border' =>$value['is_cross_border'],
                'cancel_reason' =>$value['cancel_reason'],
                'estimate_time' =>empty($value['estimate_time'])?'':$value['estimate_time'],
                'source' =>$value['source'],
                'create_user_name' =>$value['create_user_name'],
                'sales_note2' =>$value['sales_note2'],
                'erp_id' =>!empty($value['erp_id'])?$value['erp_id']:0,
                'left_stock' => $value['left_stock'],
                'account_type' =>$value['account_type'],
                'cancel_reason_category' =>$value['cancel_reason_category'],
                'create_user_id' => $value['create_user_id'],
                'is_erp' =>$value['is_erp'],
                'purchase_name' => $value['purchase_name'],
                'demand_data' => $value['demand_data'],
                'transfer_warehouse' => $value['transfer_warehouse'],
                'is_include_tax' => $value['is_include_tax'],
                'is_mrp' =>$value['is_mrp'],
                'site_name' =>$value['site_name'],
                'demand_data' => $value['purchase_amount']
            ];

            $demand_repeat = $this->judge_sku_repeat($value['sku'],$value['purchase_type_id'],$value['demand_number']);

            $sku_repeatflag = 1; // 标记为重复
            if($demand_repeat == "no_repetition"){
                $sku_repeatflag =2; // 标记为重复
            }

            $insert['demand_repeat'] = $sku_repeatflag;
            $re = $this->db->insert('pur_purchase_demand',$insert);
            if($re) {
                $this->db->where("demand_number", $value['demand_number'])->update("purchase_suggest", ['suggest_status' => 4]);
            }
        }
    }

    private function judge_sku_repeat($sku=NULL,$purchase_type_id=NULL,$demand_number=NULL){

        if( NULL == $sku || $purchase_type_id == NULL){

            return NULL;
        }
        //$slaveDb=$this->load->database('slave',TRUE);

        $demandDatas = $this->db->from("pur_purchase_demand")->where("sku",$sku)
            ->where_in("demand_status",[DEMAND_SKU_STATUS_CONFIR,SUGGEST_STATUS_NOT_FINISH])
            ->select("id,demand_number,sku,purchase_type_id")->get()
            ->result_array();

        // 如果SKU 没有查询到需求单记录
        if(empty($demandDatas)){

            return "no_repetition";
        }

        //print_r($demandDatas);die();

        // 判断SKU 需求单业务线类别
        $searchData = [];
        if( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])){

            $searchData = [PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH];
        }else if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $searchData = [PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG];
        }

        $qualified = [];
        foreach($demandDatas as $key=>$value){

            if(in_array($value['purchase_type_id'],$searchData) && in_array($purchase_type_id,$searchData)){

                $qualified[] = $value;
            }
        }
        $ids = array_column($qualified,"id");
        //  业务线一直所有需求单为重复
        if( !empty($qualified)){
            $updata =[

                'demand_repeat' =>1
            ];
            $this->db->where_in("id",$ids)->update("pur_purchase_demand",$updata);
            return "repetition";
        }

        /*$updata =[

            'demand_repeat' =>2
        ];
        $this->purchase_db->where("demand_number",$demand_number)->update("purchase_demand",$updata);
        */
        return "no_repetition";

    }



    /**
     * 备货单作废到需求单锁定解锁脚本
     * @author:luxu
     * @time:2021年7月17号
     **/

    public function demand_cancel_unlock(){

        $total = $this->db->from("purchase_demand")->where("is_lock",1)->count_all_results();

        $nowDays =  date("Y-m-d",time()); // 当前时间
        // 获取需求单最后一个订单的创建时间
        $endDatas = $this->db->from("purchase_demand")->where("erp_id",0)->order_by("id DESC")->select(" (id) as id,create_time")->get()->row_array();
        $endDatasTime = date("Y-m-d",strtotime($endDatas['create_time']));
        if($endDatasTime != $nowDays){
            echo "计划系统推送需求单后在执行";die();
        }
        $limit = 100;
        $page =  ceil($total/$limit);
        for($i=1;$i<=$page;++$i) {
            $limit = 100;
            $offset = 0;

            $results = $this->db->from("purchase_demand")->where("is_lock",1)
                ->limit($limit,$offset)->select("id,demand_cancel_time")->get()->result_array();

            if(!empty($results)){
                $unlocks = [];
                foreach($results as $key=>$value){
                    $demand_cancel_time = date("Y-m-d",strtotime($value['demand_cancel_time']));
                        if ($demand_cancel_time < $nowDays) {

                        $unlocks[] = $value['id'];
                    }
                }

                if(!empty($unlocks)){

                    $this->db->where_in("id",$unlocks)->update("purchase_demand",['is_lock'=>0]);
                }
            }


           // $results = $this->db->from("purchase_demand")->where("over_lock_time<=", $nowTime)->where("demand_lock", 1)
            //    ->limit($limit, $offset)->select("id")->get()->result_array();
        }
    }

    /**
     * 获取当前时间之前需求单未完结的数据,
     * 直接从redis取数据（1.需求单为锁单，2：历史需求单状态未完结，需求单业务线=国内/FBA/PFB/平台头程,需求单状态=未完结,待重新确认）
     * @author:luxu
     * @time:2021年8月17号
     **/

    public function get_demand_skus(){
        ini_set('max_execution_time','18000');
        $start = date("Y-m-d H:i:s",strtotime("-15 day"));
        $end = date("Y-m-d H:i:s",time());
        $count = $this->db->from("purchase_demand")
            ->where_in("purchase_type_id",[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_PFB,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFH])
            ->where_in("demand_status",[DEMAND_SKU_STATUS_CONFIR,SUGGEST_STATUS_NOT_FINISH])
            ->where("create_time>=",$start)->where("create_time<=",$end)
            ->count_all_results();
        $limit = 500;
        $page = ceil($count/$limit);
        $expiretime = 60*60*24;
        for($i=1;$i<=$page;$i++){
            $offset = ($i - 1) * $limit;
            $result = $this->db->from("purchase_demand")
                ->where_in("purchase_type_id",[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_PFB,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFH])
                ->where_in("demand_status",[DEMAND_SKU_STATUS_CONFIR,SUGGEST_STATUS_NOT_FINISH])
                ->where("create_time>=",$start)->where("create_time<=",$end)
                ->select("sku")
                ->limit($limit,$offset)->get()->result_array();
            if(!empty($result)){
                $skus = array_column($result,"sku");
                $demandSkus = $this->db->from("purchase_demand")
                    ->where_in("purchase_type_id",[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_PFB,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFH])
                    ->where_in("demand_status",[DEMAND_SKU_STATUS_CONFIR,SUGGEST_STATUS_NOT_FINISH])
                    ->where("create_time>=",$start)->where("create_time<=",$end)
                    ->where_in("sku",$skus)->select("sku,purchase_type_id,demand_number,id")->get()->result_array();
                $pushRedis = [];
                foreach($demandSkus as $demandSkus_key=>$demandSkus_value){
                    if(!isset($pushRedis[$demandSkus_value['sku']])){

                        $pushRedis[$demandSkus_value['sku']] = [];
                    }
                    $pushRedis[$demandSkus_value['sku']][] = $demandSkus_value;

                }
                if(!empty($pushRedis)){
                    foreach($pushRedis as $redis_key=>$redis_value){
                        $keys = "demand_number:".$redis_key;
                        $flag = $this->rediss->setData($keys,json_encode($redis_value),$expiretime);
                    }
                }
            }
        }
    }

    public function testdemand(){
        $sku = "06EGS90000";
        $purchase_type_id = 4;
        if( NULL == $sku || $purchase_type_id == NULL){

            return NULL;
        }
        $keys = "demand_number:".$sku;
        $history_demands = $this->rediss->getData($keys);
        if(empty($history_demands)){
            return "no_repetition";
        }

        $searchData = [];
        if( in_array($purchase_type_id,[PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH])){

            $searchData = [PURCHASE_TYPE_INLAND,PURCHASE_TYPE_FBA,PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH];
        }else if(in_array($purchase_type_id,[PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG])){
            $searchData = [PURCHASE_TYPE_OVERSEA,PURCHASE_TYPE_FBA_BIG];
        }

        $qualified = [];
        $demandDatas =  json_decode($history_demands,True);
        foreach($demandDatas as $key=>$value){
            if(in_array($value['purchase_type_id'],$searchData) && in_array($purchase_type_id,$searchData)){

                $qualified[] = $value;
            }
        }
        $ids = array_column($qualified,"id");
        if( !empty($qualified)){
            $updata =[

                'demand_repeat' =>1
            ];
            //$this->purchase_db->where_in("id",$ids)->update("purchase_demand",$updata);
            return "repetition";
        }

        /*$updata =[

            'demand_repeat' =>2
        ];
        $this->purchase_db->where("demand_number",$demand_number)->update("purchase_demand",$updata);
        */
        return "no_repetition";
    }


    /**
     * 计划系统获取 需求单在途数量接口
     * Pur_suggest_api/get_demand_pr_qty
     * @author Jolon
     */
    public function get_demand_pr_qty(){
        $page   = $this->input->get_post('page');
        $limit  = $this->input->get_post('limit');
        $page   = $page?$page:1;// 当前页
        $limit  = (empty($limit) or $limit > 5000) ? 1000: $limit;// 默认一千条数据

        // 生成数据
        $first  = $this->db->select('insert_time')->order_by('id ASC')->get('purchase_demand_pr')->row_array();
        if(empty($first) or empty($first['insert_time']) or time() - strtotime($first['insert_time']) > 3600 * 12){
            $this->db->where('1=1')->delete('purchase_demand_pr');

            $insert_time = date('Y-m-d H:i:s');
            $insertSql = "INSERT INTO pur_purchase_demand_pr(demand_number,pr_qty,sku,created_at,purchase_type_id,warehouse_code,is_drawback,insert_time)
                        SELECT 
                            C.demand_number AS demand_number,
                            CASE A.purchase_order_status
                            WHEN 1 THEN C.demand_data
                            WHEN 3 THEN C.had_purchase_amount 
                            END AS pr_qty,
                            C.sku AS sku,
                            C.create_time AS created_at,
                            C.purchase_type_id,
                            C.warehouse_code,
                            A.is_drawback AS is_drawback,
                            '{$insert_time}'
                        FROM pur_purchase_order AS A
                        INNER JOIN pur_purchase_order_items AS B ON A.purchase_number=B.purchase_number
                        INNER JOIN pur_purchase_demand AS C ON B.demand_number=C.suggest_demand
                        WHERE A.purchase_order_status IN(".PURCHASE_ORDER_STATUS_WAITING_QUOTE.",".PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT.")
                        AND B.demand_number <> ''
                        AND B.demand_number IS NOT NULL
                    
                        UNION ALL
                    
                        SELECT 
                            C.demand_number AS demand_number,
                            C.demand_data AS pr_qty,
                            C.sku AS sku,
                            C.create_time AS created_at,
                            C.purchase_type_id,
                            C.warehouse_code,
                            C.is_drawback AS is_drawback,
                            '{$insert_time}'
                        FROM pur_purchase_demand AS C
                        WHERE C.demand_status IN(".DEMAND_STATUS_NOT_FINISH.",".DEMAND_TO_SUGGEST.",".DEMAND_SKU_STATUS_CONFIR.",".DEMAND_STATUS_FINISHED.") 
                        AND C.is_create_order=0";

            $this->db->query($insertSql);
        }


        // 已生成采购单部分
        $offset = ($page - 1) * $limit;

        $list = $this->db->select('demand_number,pr_qty,SKU,purchase_type_id,is_drawback,warehouse_code,created_at')
            ->order_by('id ASC')
            ->get('pur_purchase_demand_pr',$limit,$offset)
            ->result_array();



        // 汇总信息
        $countTotal = $this->db->select('count(1) AS num')
            ->get('pur_purchase_demand_pr')
            ->row_array();

        $page_data = [
            'total' => isset($countTotal['num'])?$countTotal['num']:0,
            'offset' => $page,
            'limit' => $limit,
            'pages' => strval(ceil($countTotal['num']/$limit))
        ];

        $this->success_json($list,$page_data);

    }

}
