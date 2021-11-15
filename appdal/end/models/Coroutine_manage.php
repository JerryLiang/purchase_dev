<?php

/**
 * 协程 - 异步任务管理器
 * Class Coroutine_manage
 * @package App\Server
 */
class Coroutine_manage extends Purchase_model
{
    /**
     * 缓存请求参数过期时间
     */
    protected  $timeout = 600;

    protected $res = [
        "status"    => 0,
        "message"   => "默认失败!",
        "dataList"  => [],
        "page"      => 1,
        "offset"    => 20,
    ];

    /**
     * 随机ID
     */
    private function randomCode($num = 12)
    {
        $codeSeeds = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        $len = strlen($codeSeeds);
        $code = "";
        for ($i = 0; $i < $num; $i++) {
            $rand = rand(0, $len - 1);
            $code .= $codeSeeds[$rand];
        }
        return $code;
    }

    /**
     * FPM 模式下调用swoole协程管理器，不需要启动端口监听
     */
    public function HandleWord($params=[])
    {
        $this->load->library('rediss');
        $res = $this->res;
        $id = $this->randomCode(32); // 生成随机ID

        // 缓存请求参数
        $params["uid"] = 123456;
        $params['function'] = ["coroutine_manage","fun_a"];
        $params['type'] = "fun_a";

        $this->rediss->setData($id, serialize($params), $this->timeout);
//        log::info($id);

        $out = [];
        $path = '/mnt/c/www/purchase_dev/appdal';
        exec("php {$path}/index.php CoroutineManage/cmd_test {$id}", $out, $status);

        // 获取返回后，销毁缓存的参数
//        if($this->rediss->existsData($id)){
//            $this->rediss->delete($id);
//        }

        if($status === 0){
            $temp = isset($out[0]) ? json_decode($out[count($out) -1], true) : [];
            if(SetAndNotEmpty($temp, 'status') && $temp['status'] == 1){
                return $temp;
            }
        }

        return $res;
    }


    /**
     * 分配处理
     */
    public function HandleCoroutine($id)
    {
//        echo ".....HandleCoroutine:{$id}...";exit;
        $res = $this->res;

        // id 只能是32位数字和大小写字母字符长度字符串
        if(strlen($id) == 32 && preg_match("/^[a-zA-Z0-9]*$/", $id) <= 0){
            $res['message'] = '未获取相应的数据！';
            echo json_encode($res);
            return false;
        }

        // 获取请求参数
        $params = [];
//        if($this->rediss->existsData($id)){
            $params = $this->rediss->getData($id);
            if(is_string($params))$params = unserialize($params);
//        }
        if(!is_array($params) || count($params) < 1){
            $res['message'] = '未获取相应的请求参数！';
            echo json_encode($res);
            return false;
        }

        // 分配方式2
        $type = isset($params['type']) ? $params['type'] : '';
        switch ($type){
            case 'fun_a':
                echo $this->fun_a($params);
                break;
        }

        return false;
    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 协程的使用
     * @param array $params
     */
    private function fun_a($params)
    {
        $this->load->model('purchase/Purchase_order_mode');
        $_this_db = $this->purchase_db;
        $log_file = APPPATH . 'logs/HandleCoroutine' . date('Ymd') . '.txt';
        file_put_contents($log_file, get_microtime()." fun_a.....run ..".json_encode($params)."....\n", FILE_APPEND);
        \Swoole\Coroutine::create(function ($params, $_this_db) {
            $res = $this->res;
            $start_at = get_microtime();
            $wg = new \Swoole\Coroutine\WaitGroup();
            $result = [];
            $time = microtime(true);

            // 1G = 1024 / 16 ()

            for($i=0; $i<100; $i++){

                $wg->add();//协程数量加1
                // 启动第一个协程
                $dbs = clone $_this_db;
                \Swoole\Coroutine::create(function () use ($wg, &$result, $i, $dbs) {
                    $log_file = APPPATH . 'logs/HandleCoroutine' . date('Ymd') . '.txt';
                    try{
                        // $con = ''; // 链接

                        $sleep = rand(1, 9);
                        \Swoole\Coroutine::sleep($sleep);
                        $result["data{$i}"] = "this is a {$i} sleep:{$sleep}..";
                        $wg->done();//本协程任务完成

                        // 如果错误，销毁该协程
                    }catch (\Exception $e){
                        file_put_contents($log_file, get_microtime()." HandleCoroutine.....Exception ..".$e->getMessage()."....\n", FILE_APPEND);
                    }catch (\Throwable $e){
                        file_put_contents($log_file, get_microtime()." HandleCoroutine.....Throwable ..".$e->getMessage()."....\n", FILE_APPEND);
                    }
                });

            }

            //挂起父协程，等待所有子协程任务完成后恢复
//            $wg->wait();
            \Swoole\Event::wait();

            $end_at = get_microtime();
            //这里 $result 包含了 2 个任务执行结果
            if(count($result) > 0){
                $res['message'] = '操作成功';
                $res['status'] = 1;
                $res['start_at'] = $start_at;
                $res['end_at'] = $end_at;
                $res['dataList'] = array_merge($result, $params);
            }else{
                $res['message'] = '未获取相应的数据！';
            }
            $log_file = APPPATH . 'logs/HandleCoroutine' . date('Ymd') . '.txt';
            file_put_contents($log_file, get_microtime()." HandleCoroutine.....run ..".json_encode($res)."....\n", FILE_APPEND);
            echo json_encode($res);
        }, $params, $_this_db);
    }
}
