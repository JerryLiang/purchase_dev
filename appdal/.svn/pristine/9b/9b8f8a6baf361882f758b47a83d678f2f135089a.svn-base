<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2021/1/7
 * Time: 14:15
 */


/**
  * 计划系统推送备货单计算是否锁单服务
 **/

if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
use PHPUnit\Framework\TestCase;


class Suggest_server extends MY_API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_suggest/purchase_suggest_model');
    }

    /**
     * 接受计划系统推送备货 SWOOLE 服务
     * @params
     * @author:luxu
     * @time:2021年1月7号
     **/
    public function suggestData(){

        $server = new \Swoole\Server(SWOOLE_SERVER, 2126);

        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $log_dir = APPPATH . 'logs/suggest_mq/';
            $log_file = 'suggest_mq' . date('Ymd') . '.txt';
            echo $log_file;
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/suggest_mq');
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
        });
        $server->start();
    }

    /**
     * 计划系统推送数据锁单接口，推送计划系统
     * @author:luxu
     * @time:2021年1月16号
     **/
    public function suggest_mq(){

       $suggest =  $this->get_suggest_mq();
       // $suggest= '{"data":[26104941,26104940,26104939,26104938,26104937,26104936,26104935]}';
       if( !empty($suggest)){

           $suggestData = json_decode($suggest,True);
                   $idsData = $this->purchase_suggest_model->get_demand_number_ids($suggestData['data']);
                   if(!empty($idsData)) {

                       $ids = array_column($idsData, 'id');

                       $lockDemand = $this->purchase_suggest_model->plan_create_entities_lock_list($ids);

                       if ($lockDemand['code'] == 200) {

                           $this->purchase_suggest_model->update_plan_demand_data($ids);
                       }

                       if (!empty($idsData)) {

                           //推送计划系统审核后的需求单
                           $this->purchase_suggest_model->push_plan_audit($idsData);
                       }
                   }
           }
    }

    /**
     * 从MQ 中获取计划系统推送过来的备货单，并且是没有计算过是否锁单的数据，判断是否需要锁单
     * @param
     * @author:luxu
     * @time:2021年1月7号
     **/
    public function get_suggest_mq(){

        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('SUGGEST_LOCK');
        $mq->setExchangeName('SUGGEST');
        $mq->setRouteKey('SUGGEST_LOCK_KEY');
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        //构造存入数据
        //存入消息队列
        $queue_obj = $mq->getQueue();
        //处理生产者发送过来的数据
        $envelope = $queue_obj->get();
        $data = NULL;
        if ($envelope) {
            $data = $envelope->getBody();

            $queue_obj->ack($envelope->getDeliveryTag());

            $mq->disconnect();
        }
        return $data;
    }

    /**
     * 从MYSQL 中获取计划系统推送过来的备货单，并且是没有计算过是否锁单的数据，判断是否需要锁单
     * @param
     * @author:luxu
     * @time:2021年1月7号
     **/
    public function suggest(){

        try{

            $total = $this->purchase_suggest_model->planis_lock_demand_count();

            if($total == 0){
                return;
            }

            // 开始计算是否需要锁单
            $limit = 200;
            $page = ceil($total/$limit);

            for( $i=1;$i<=$page;++$i){

                $offset        = ($i - 1) * $limit;
                $results = $this->purchase_suggest_model->get_plan_demand_data($offset,$limit);
                if( !empty($results)){

                    $idsData = array_column($results,"id");
                    $lockDemand = $this->purchase_suggest_model->plan_create_entities_lock_list($idsData);
                    if( $lockDemand['code'] == 200){

                        $this->purchase_suggest_model->update_plan_demand_data($idsData);
                    }
                }
            }

        }catch ( Exception $exp ){


        }
    }

}