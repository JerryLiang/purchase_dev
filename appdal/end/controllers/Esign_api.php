<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * 1688 订单操作控制器
 * User: Jolon
 * Date: 2019/03/20 10:00
 */
class Esign_api extends MY_API_Controller {

    public function __construct(){

        $this->load->library('mongo_db');
    }


    /**
     * E签宝回调地址（来自JAVA中转）
     * {HOST}/esign_api/callback
     */
    public function callback(){
        $data           = [];
        $data['post']   = $_POST;
        $data['get']    = $_GET;

        $contentType = (isset($_SERVER['CONTENT_TYPE']) and !empty($_SERVER['CONTENT_TYPE'])) ? $_SERVER['CONTENT_TYPE'] : '';
        if (strpos($contentType, 'json') > 0) {
            $params = file_get_contents('php://input');
            $data['input'] = $params;
        }else{
            $data['input'] = [];
        }

        $data['timestamp']      = time();
        $data['handing_status'] = 1;// 未处理

        $logTable = 'esign_callback_log';
        $this->mongo_db->insert($logTable, $data);

        echo json_encode(['code' => 200]);
        exit;
    }
}