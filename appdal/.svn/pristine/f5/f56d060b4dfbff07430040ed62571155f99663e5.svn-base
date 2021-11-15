<?php

/**
 * Class Sync_supplier_model
 */
class Sync_supplier_model extends Purchase_model
{
    /**
     * Sync_supplier_model constructor.
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * 写入错误列表
     */
    public function insert_error_table($data)
    {
        try{
            $this->purchase_db->insert("system_error_log", [
                "operator_user" => "system",
                "operate_ip" => "0.0.0.0",
                "operate_route" => "sync_supplier/listening_mq_supplier",
                "message" => (is_array($data) ? json_encode($data): $data),
            ]);
        }catch (Exception $e){}
    }

    /**
     * 推送数据设置
     */
    public function set_push_data($data, $type='')
    {
        if(!is_array($data))$data = [$data];
        $this->set_data([
            'push_data' => $data,
            "handle_type" => "ali_order_confirm",
            "handle_time" => 0,
        ]);
    }

    /**
     * 设置待生成的采购单数据
     */
    public function set_create_suggest($data, $keys=0)
    {
        if(!is_array($data))$data = [$data];
        $this->set_data([
            'push_data' => $data,
            "handle_type" => "suggest_create_purchase",
            "source" => $keys,
            "handle_time" => 0,
        ]);
    }

    /**
     * 设置推送数据
     */
    private function set_data($data)
    {
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('SYNC_DATA_TO_SUPPLIER');//设置参数
        $mq->setExchangeName('SYNC_DATA_TO_SUPPLIER_NAME');
        $mq->setRouteKey('SYNC_DATA_TO_SUPPLIER_KEY');
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        $mq->sendMessage($data);
    }
}