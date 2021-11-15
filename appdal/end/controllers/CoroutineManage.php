<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * php-FPM 模式下协程调用示例
 * Class CoroutineManage
 */
class CoroutineManage extends MY_API_Controller
{
    /**
     * CoroutineManage constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Coroutine_manage');
    }

    /**
     * FPM 模式下调用(url)
     */
    public function test()
    {
        $params = [];
        $data = $this->Coroutine_manage->HandleWord($params);
        echo "response：".json_encode($data).",form Coroutine";
    }

    /**
     * cli 模式下 cmd命令入口
     */
    public function cmd_test($id = '')
    {
        $this->load->model('Coroutine_manage');
        echo $this->Coroutine_manage->HandleCoroutine($id);
    }

}