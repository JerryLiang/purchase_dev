<?php
class Rabbitmq{
    private $CI;
    private $config_file = 'rabbitmq';
    private $host = '';
    private $port = '';
    private $vhost = '/';
    private $user   = '';
    private $password = '';

    private $connection = null;
    private $channle_obj = null;
    private $exchange_obj = null;
    private $queue_obj    = null;

    private $queue_name = ''; //队列名称
    private $exchange_name = ''; //交换机名称
    private $route_key      ='';
    private $type = AMQP_EX_TYPE_DIRECT; //分发方式

    private $message = '';
    public function __construct($config=NULL)
    {
        if ( ! class_exists('AMQPConnection'))
        {
          //  show_error("The AMQP PECL extension has not been installed or enabled", 500);
        }
        $this->CI =& get_instance();
        $this->initParams($config);
        $this->connect();
    }

    public function setQueueName($queue_name = ''){
        $this->queue_name = $queue_name;
        return $this;
    }

    public function setExchangeName($exchangeName = ''){
        $this->exchange_name = $exchangeName;
        return $this;
    }

    public function setRouteKey($route_key = ''){
        $this->route_key = $route_key;
        return $this;
    }

    public function setType($type = ''){
        $this->type = $type;
        return $this;
    }




    //初始化
    private function initParams($config=NULL){

        if( NULL == $config) {
            $this->CI->load->config($this->config_file);
            $this->host = trim($this->CI->config->item('rabbitmq_host'));
            $this->port = trim($this->CI->config->item('rabbitmq_port'));
            $this->vhost = trim($this->CI->config->item('rabbitmq_vhost'));
            $this->user = trim($this->CI->config->item('rabbitmq_user'));
            $this->password = trim($this->CI->config->item('rabbitmq_password'));
        }else{
            $this->host = $config['rabbitmq_host'];
            $this->port = $config['rabbitmq_port'];
            $this->vhost = $config['rabbitmq_vhost'];
            $this->user = $config['rabbitmq_user'];
            $this->password = $config['rabbitmq_password'];

        }
    }


    //连接
    private function connect(){
        try {
            $this->connection = new AMQPConnection(array('host' => $this->host, 'port' => $this->port, 'vhost' => $this->vhost, 'login' => $this->user, 'password' => $this->password));
            $this->connection->connect() or show_error("Cannot connect to the broker!",500);

            $this->channle_obj = new AMQPChannel($this->connection);
            $this->exchange_obj = new AMQPExchange($this->channle_obj);
            return ($this);
        }catch (AMQPConnectionException $e){
            show_error($e->getMessage(),500);
        }
    }

    //设置交换机
    public function setExchange(){
        $this->exchange_obj->setName($this->exchange_name);
        $this->exchange_obj->setType($this->type);
        $this->exchange_obj->setFlags(AMQP_DURABLE);
        $this->exchange_obj->declareExchange();
        return ($this);
    }

    //设置队列
    public function setQueue(){
        $this->queue_obj =  new AMQPQueue($this->channle_obj);
        $this->queue_obj->setName($this->queue_name);
        $this->queue_obj->setFlags(AMQP_DURABLE);
        $this->queue_obj->declareQueue();
        if($this->type == AMQP_EX_TYPE_FANOUT) {
            $this->queue_obj->bind($this->exchange_name);
        }else{
            $this->queue_obj->bind($this->exchange_name, $this->route_key);
        }
        return ($this);
    }


    /**
     * 发送消息（只能单次发送请求）
     * @param array $message
     */
    public function sendMessage($message = []){
        if(!is_array($message)){
                $message = array($message);
        }
        if($this->type == AMQP_EX_TYPE_DIRECT){
            $this->setExchange()->setQueue();
        }else{
            $this->setExchange();
        }
        if($this->type == AMQP_EX_TYPE_FANOUT){
            $this->exchange_obj->publish(json_encode($message));
        }else{
            $this->exchange_obj->publish(json_encode($message),$this->route_key);
        }
        $this->disconnect();
    }


    //接收消息
    public function getQueue(){
        $this->setExchange()->setQueue();
        return $this->queue_obj;
    }


    //断开连接
    public function disconnect(){
        try{
            $this->connection->disconnect();
        }catch (AMQPConnectionException $e){
            print_r($e);
        }
    }


    public function ack_faount($message =[]){
        try {
            $connection = new AMQPConnection(array('host' => $this->host, 'port' => $this->port, 'vhost' => $this->vhost, 'login' => $this->user, 'password' => $this->password));
            $connection->connect() or show_error("Cannot connect to the broker!",500);
            $channle_obj = new AMQPChannel($connection);
            $exchange_obj = new AMQPExchange($channle_obj);

            $exchange_obj->setName($this->exchange_name);
            $exchange_obj->setType($this->type);
            $exchange_obj->setFlags(AMQP_DURABLE);
            $exchange_obj->declareExchange();


            $queue_obj =  new AMQPQueue($channle_obj);
            $queue_obj->setName($this->queue_name);
            $queue_obj->setFlags(AMQP_DURABLE);
            $queue_obj->declareQueue();
            $queue_obj->bind($this->exchange_name);
            return ['queue_obj' => $queue_obj,'connection' => $connection];

        }catch (AMQPConnectionException $e){
            show_error($e->getMessage(),500);
        }
    }



}