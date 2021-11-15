<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * 同步数据到门户系统
 */
class Sync_supplier extends MY_API_Controller
{
    /**
     * Sync_supplier constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $this->load->model('supplier_joint_model');
        $this->load->model('sync_supplier_model');
    }

    /**
     * 监听队列
     * sync_supplier/listening_mq_supplier
     */
    public function listening_mq_supplier()
    {
        set_time_limit(0);
        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();
        $mq->setQueueName('SYNC_DATA_TO_SUPPLIER');//设置参数
        $mq->setExchangeName('SYNC_DATA_TO_SUPPLIER_NAME');
        $mq->setRouteKey('SYNC_DATA_TO_SUPPLIER_KEY');
        $mq->setType(AMQP_EX_TYPE_DIRECT);

        $queue = $mq->getQueue();
        $row_count = $queue->declareQueue();// 获得总消息数
        $row_count = ($row_count <= 1)? $row_count : 1;// 每次最多执行 100个

        $success_count = 0;
        $reset = 0;
        $end = 0;
        for($i = 0; true ;$i ++){
            $envelope = $queue->get();
            if(!$envelope)continue;

            $base = $envelope->getBody();
            $data = json_decode($base, true);

            $queue->ack($envelope->getDeliveryTag());// 消息删除

            // 处理次数
            if(!isset($data['handle_time']) || !is_numeric($data['handle_time']))$data['handle_time'] = 0;
            $data['handle_time'] += 1;

            $res = $this->handle_message($data);

            // 本条处理完结
            $push_error = false;
            if(isset($res['code']) && $res['code'] == 1){
                $success_count ++;
                continue;
            }elseif(isset($res['code']) && $res['code'] == 0 && isset($res['msg']) && $res['msg'] !== false){
                $push_error = $res['msg'];
            }
            if(isset($res['code']) && $res['code'] == 3){
                continue; // 空数据，不处理
            }

            // 超过3次，将不再处理
            if($data['handle_time'] >= 3){
                // 写入异常
                $end ++;
                $error = $push_error === false ? "未知或默认的错误！" : $push_error;
                $this->sync_supplier_model->insert_error_table($error);
                continue;
            }

            // 消息重新入列
            $reset ++;
            $mq->sendMessage($data);
        }

        $mq->disconnect();
        echo date('Y-m-d H:i:s')." listening_mq_supplier >>> 本次执行：".$row_count."，成功：".$success_count."，失败重新入列：".$reset."，异常：".$end."<br/>\t\n";
        return true;
    }

    /**
     * 分配消息处理
     * @param $data
     * @return array
     */
    private function handle_message($data)
    {
        $res = ["code" => 0, "msg" => false];
        if(!SetAndNotEmpty($data, 'handle_type')){
            $res['msg'] = '处理类型不能为空！';
            return $res;
        }
        if(!SetAndNotEmpty($data, 'push_data')){
            $res['msg'] = '推送数据不能为空！';
            return $res;
        }

        switch ($data['handle_type']){
            case "ali_order_confirm":
                $res = $this->supplier_joint_model->pushSmcPurchaseData($data['push_data']);
                break;
            case "suggest_create_purchase":
                $push_data = !is_array($data['push_data']) ? json_decode($data['push_data'], true): $data['push_data'];
                $source = SetAndNotEmpty($data, 'source') ? $data['source']:0;
                $res = $this->purchase_suggest_model->create_order_by_suggest($push_data, $source);
                break;
        }
        return $res;
    }

    /**
     * 手动推送采购单信息到门户系统
     * sync_supplier/push_data?data=po00001&type=ali_order_confirm
     */
    public function push_data()
    {
        $data = $this->input->get_post('data');
        $type = $this->input->get_post('type');
        if(empty($data) || empty($type)){
            echo '推送失败！';
        }
        $this->sync_supplier_model->set_push_data($data, $type);
        echo '推送了！';
    }
}