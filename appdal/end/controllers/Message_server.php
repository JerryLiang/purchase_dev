<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
use PHPUnit\Framework\TestCase;


class Message_server extends MY_API_Controller
{

    public function __construct()
    {
        parent::__construct();
    }



    public function Message()
    {
        //tcp服务器
        echo "fsfdsfsf";
        $server = new \Swoole\Server(SWOOLE_SERVER, 2026);
        //开启4个进程执行
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

            $this->pullData($data);
//            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
//            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
//            echo '开启下一个任务';
//            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
      * 数据落户单MONGODB和MYSQL 中
     **/
    public function pullData($data=''){

        if(!empty($data)){

            $datas = json_decode($data,True);
            $ci = get_instance();

            //获取redis配置
            $ci->load->config('mongodb');
            $host = $ci->config->item('mongo_host');
            $port = $ci->config->item('mongo_port');
            $user = $ci->config->item('mongo_user');
            $password = $ci->config->item('mongo_pass');
            $author_db = $ci->config->item('mongo_db');
            $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
            $author_db = $author_db;

            foreach($datas as $key=>$value){

                $bulk = new MongoDB\Driver\BulkWrite();
                $value['is_read'] = 1;
                $mongodb_result = $bulk->insert($value);
                try {

                    $result = $mongodb->executeBulkWrite("{$author_db}.message", $bulk);
                    usleep(2000);
                } catch (Exception $exp) {
                    echo $exp->getMessage();
                }
            }
        }
    }

}