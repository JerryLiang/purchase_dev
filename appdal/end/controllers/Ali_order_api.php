<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * 1688 订单操作控制器
 * User: Jolon
 * Date: 2019/03/20 10:00
 */
class Ali_order_api extends MY_API_Controller {

    public function __construct(){
        parent::__construct();
//        $this->load->helper('status_1688');
//
//        $this->load->library('alibaba/AliProductApi');
//        $this->load->library('alibaba/AliOrderApi');
//        $this->load->library('alibaba/AliSupplierApi');
//        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('ali/Ali_product_model');
        $this->load->model('ali/Ali_order_model');
        $this->load->model('ali/Ali_order_advanced_load_model', 'advanced_load_model');
    }

    /**
     * 自助下单
     */
    public function self_help_create_order($purchase_number='')
    {
        $this->_ci->load->library('rediss');
        $log_file = APPPATH . 'logs/one_key_order_' . date('Ymd') . '.txt';
        file_put_contents($log_file, get_microtime()."start...self_help_create_order ...{$purchase_number}...\n", FILE_APPEND);
        if(empty($purchase_number))return;
        file_put_contents($log_file, get_microtime()."...1...\n", FILE_APPEND);
        $base_data = $this->rediss->getData('one_key_create_order_params_'.$purchase_number);
        file_put_contents($log_file, get_microtime()."...2..{$base_data}.\n", FILE_APPEND);
        $session_key = 'one_key_order_res_'.$purchase_number;
        $ttl_key = 120;
        try{
            $base_data = json_decode($base_data);
        }catch (Exception $e){}catch (Error $e){}
        file_put_contents($log_file, get_microtime()."...3...\n", FILE_APPEND);
        if(!isset($base_data['ali_sku_amount_list'])){
            $this->rediss->setData($session_key, '预下单数量缺失', $ttl_key);
            return;
        }
        $ali_sku_amount_list = [];

        $is_create = $this->Ali_order_model->checkPurchaseCreateOrder([$purchase_number]);

        if(is_array($is_create) && !empty($purchase_number) && in_array($purchase_number, $is_create)){
            $this->rediss->setData($session_key, "采购单[$purchase_number]已经生成了1688订单，请勿重复下单", $ttl_key);
            return;
        }

        $ali_sku_amount = isset($ali_sku_amount_list[$purchase_number])?$ali_sku_amount_list[$purchase_number]:[];
        $return  = $this->Ali_order_model->order_preview($purchase_number,$ali_sku_amount);

        if($return['code'] === false){
            $this->rediss->setData($session_key, $purchase_number."：".$return['message'], $ttl_key);
            return;
        }

        $list_key = 'one_key_order_list_res_'.$purchase_number;
        if(isset($return['data']['ali_order_data']['passed_preview']) and $return['data']['ali_order_data']['passed_preview'] == 1){
            // 预览成功的直接下单到1688，下单失败则重新预览
            $result = $this->Ali_order_model->one_key_order_not_preview($purchase_number);
            if($result['code']){
                // 下单成功
                $skuData = $this->Purchase_suggest_model->getSuggestDemand($purchase_number);
                $skuDatas = array_column( $skuData,"sku");
                $this->Product_model->updateProductNew($skuDatas);

                $this->rediss->setData($session_key, $purchase_number."_success", $ttl_key);
            }else{
                if(SetAndNotEmpty($result, 'data'))$this->rediss->setData($list_key, json_encode($result['data']), $ttl_key);
                $this->rediss->setData($session_key, $purchase_number."：".$return['message'], $ttl_key);
            }
        }else{
            if(SetAndNotEmpty($return, 'data'))$this->rediss->setData($list_key, json_encode($return['data']), $ttl_key);
            $this->rediss->setData($session_key, $purchase_number."：".$return['message'], $ttl_key);
        }
    }

    /**
     * 计划任务 自动刷新 1688订单状态
     * @url http://pms.yibainetwork.com:81/ali_order_api/auto_update_order_status
     */
    public function auto_update_order_status(){
        $this->Ali_order_model->autoUpdateOrderStatus();
    }

    /**
     * 计划任务 自动获取 1688订单的物流单号
     * @url http://pms.yibainetwork.com:81/ali_order_api/auto_update_order_tracking_number
     */
    public function auto_update_order_tracking_number(){
        $this->Ali_order_model->autoUpdateOrderTrackingNumber();
    }

    /**
     * 获取 1688物流单号的物流轨迹
     * @url http://pms.yibainetwork.com:81/ali_order_api/get_logistics_tracking
     */
    public function get_logistics_tracking(){
        $order_id  = $this->input->get_post('order_id');//1688订单号
        $this->Ali_order_model->get_logistics_tracking($order_id);
    }

    /**
     * 接收 1688 平台消息 - 来自 JAVA 的中转数据
     * @author Jolon
     * @url http://pms.yibainetwork.com:81/ali_order_api/receive_ali_order_message?debug=1&limit=20
     * @link https://open.1688.com/doc/msgOverview.htm?id=ORDER
     */
    public function receive_ali_order_message(){
        $data  = file_get_contents("php://input");
        $debug = $this->input->get_post('debug');// 调试模式
        $limit = $this->input->get_post('limit');// 调试模式
        $limit = isset($limit)?$limit:10;

        if(empty($debug) and (empty($data) or !is_json($data)) ){
            $this->error_json('数据为空或不是JSON格式');
        }

        $data = json_decode($data,true,512,JSON_BIGINT_AS_STRING);

        $this->Ali_order_model->receive_ali_order_message($data,$debug,$limit);
        $this->success_json();
    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }


    /**
     * 异步处理1688一键下单任务
     */
    public function one_key_create_order_task()
    {
        $server = new \Swoole\Server(SWOOLE_SERVER, 9509);
        $server->set(array('task_worker_num' => 8));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});

        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $server->task($data);
            $server->send($fd, "handle...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'handle_one_key_create_order_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php Ali_order_api/handle_sync_task ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php Ali_order_api/handle_sync_task ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            exec('php index.php Ali_order_api/handle_sync_task ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {});
        $server->start();
    }

    public function handle_sync_task($id=null)
    {
        echo "public function handle_sync_task......\n";
        $this->advanced_load_model->handle_sync_task($id);
    }

    /**
     * 从推送的消息中获取最近的退款退货消息记录
     */
    public function get_refund_order_data()
    {
        $sync_all = $this->input->get_post('sync_all');// 是否同步全量数据
        $this->Ali_order_model->sync_refund_order_data($sync_all);
    }
}