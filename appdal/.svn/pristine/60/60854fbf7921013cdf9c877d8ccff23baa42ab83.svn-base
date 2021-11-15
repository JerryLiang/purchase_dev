<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Home extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index() {
        $this->load->view('index.html');
    }

    /**
     * 获取Redis指定Key的数据
     * @url /home/get_redis_data_by_key?key=STATUS
     */
    public function get_redis_data_by_key(){
        $key = $this->input->get('key');
        $data = $this->rediss->getData($key);
        print_r($data);
        exit;
    }

    /**
     * 清理缓存——删除Redis指定Key的数据
     * @url /home/delete_redis_key?key=STATUS
     */
    public function delete_redis_key(){
        $key = $this->input->get('key');
        $this->rediss->deleteData($key);
        echo "删除Redis数据==KEY：$key==成功==";
        exit;
    }

    /**
     * 设置指定 API 地址来记录客户端调用该接口传入的参数
     * @exp string $log_api_url  API地址（后台关键字模糊匹配）
     * @exp string  $log_watch_time 时间（秒，默认300）
     * @url 记录指定API /home/begin_debug_info_record?log_api_url=purchase/purchase_order/get_order_list
     * @url 记录所有API /home/begin_debug_info_record?log_api_url=/
     */
    public function begin_debug_info_record(){
        $log_api_url = $this->input->get('log_api_url');
        $log_watch_time = $this->input->get('log_watch_time');
        if(empty($log_api_url) or !is_string($log_api_url)){
            echo 'log_api_url参数缺失或不是数组';
            exit;
        }
        if(empty($log_watch_time)) $log_watch_time = 300;// 默认监控5分钟

        if($log_watch_time == -1){// 设置永久有效
            $this->rediss->setData("LOG_API_URL",$log_api_url);
            $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_LOG_API_URL');
        }else{
            $this->rediss->setData("LOG_API_URL",$log_api_url,intval($log_watch_time));
        }

        echo "监控API路由==开启成功==";
        echo "<br/>LOG_API_URL => $log_api_url";
        echo "<br/>LOG_WATCH_TIME => $log_watch_time";
        exit;
    }

    /**
     * 关闭 begin_debug_info_record 接口的配置
     * @url /home/begin_debug_info_record
     */
    public function close_begin_debug_info_record(){
        $this->rediss->deleteData("LOG_API_URL");
        echo "监控API路由==关闭成功==";
        exit;
    }


    /**
     * 查看文件夹下所有文件
     * @author Jolon
     * @url home/get_cache_files?dir=monolog
     */
    public function get_cache_files(){
        $this->load->helper('file_remote');
        $base_dir = APPPATH . DIRECTORY_SEPARATOR . "logs/";
        $dir = $this->input->get_post('dir');
        $dir = $base_dir . trim($dir, '/');
        $isRec = $this->input->get_post('isRec');
        $files = readAllFile($dir, '', $isRec);

        if(empty($files)) exit("文件夹为空");

        foreach ($files as $value){
            $value = substr($value,stripos($value,'logs'));// 隐藏项目目录
            echo "<br/>".$value;
        }
        exit;
    }

    /**
     * 查看线上文件
     * @url home/download_file?file_full_path=libraries\Monolog.php
     */
    public function download_file(){
        $file_full_path = $this->input->get_post('file_full_path');
        $file_full_path = APPPATH . $file_full_path;
        if(!file_exists($file_full_path)){
            exit("文件不存在");
        }

        $file_contents = file_get_contents($file_full_path);
        echo $file_contents;exit;
    }
    
    public function php_info(){
        phpinfo();
        exit;
    }

}
/* End of file Home.php */
/* Location: ./application/modules/home/controllers/Home.php */