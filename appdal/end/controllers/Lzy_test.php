<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

class  Lzy_test extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->monolog->setConfig(["channel" => 'test']);
    }

    public function write_log()
    {
        try {
            $this->monolog->debug('此程序有bug', ['date' => date("Y-m-d H:i:s"), 'module' => 'purchase_order']);
        } catch (Exception $exception) {
            echo $exception->getTraceAsString();
        }
    }


    public function read_log()
    {
        $monolog = new Monolog();
        $mon_log = 'monolog/';
        $file_name = $this->input->get('filename');
        $log_dir = APPPATH . 'logs/' . $mon_log;
        try {
            $monolog->debug('此程序有bug', ['date' => date("Y-m-d H:i:s"), 'module' => 'purchase_order']);
        } catch (Exception $exception) {
            echo $exception->getTraceAsString();
        }
    }


    public function list_dir()
    {
//        $monolog = new Monolog();
//        $mon_log = 'monolog/';
//        $log_dir = APPPATH . 'logs/' . $mon_log;
        $file_dir = "Test";
        $this->monolog->debug();
        $dir_name = $this->input->get('dirname');
        try {
            $dir_list = scandir($log_dir . $dir_name . "/");
            var_dump($dir_list);
        } catch (Exception $exception) {
            echo $exception->getTraceAsString();
        }
    }


    public function test_add()
    {
        $effect = ((144142 / 86400) > 1.5) ? 2 : 1;
        echo $effect;
    }


    function add_item(array $arr, callable $function)
    {
        foreach ($arr as $value) {
            if ($value % 10 == 0) {
                call_user_func($function,$value);
            }
        }
    }

    function action(){
        $this->add_item(range(1,1000),function ($i){
            echo "匿名函数符合条件处理数据:".$i;
        });
    }
}



