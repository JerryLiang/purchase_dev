<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "third_party/MX/Controller.php";

/**
 * MY_Controller 控制器核心类
 * @author:    Jolon
 * @since: 2021-06-21
 */
class MY_Oauth_Controller extends MX_Controller
{
    protected $_OK                  = 200;
    protected $_BadRequest          = 400;
    protected $_Unauthorized        = 401;
    protected $_Forbidden           = 403;
    protected $_MethodNotAllowed    = 405;

    protected $_status;
    protected $_error;
    protected $_errorDesc;
    protected $_pathUrl;
    protected $_remoteIp;
    protected $_audience;
    protected $_timestamp;
    protected $_requestData;
    protected $_responseData;

    public function __construct()
    {
        defined('OAUTH_ACCESS') or define('OAUTH_ACCESS',true);// 是否是 OAUTH 访问

        parent::__construct();

        // 设置请求数据
        $this->_pathUrl     = $_SERVER['REDIRECT_URL'];
        $this->_timestamp   = date('Y-m-d H:i:s');
        $this->_remoteIp    = $_SERVER['REMOTE_ADDR'];

        // 只允许 POST 请求方式
        if($_SERVER['REQUEST_METHOD'] != 'POST'){
            $this->error_json($this->_MethodNotAllowed,"Method Not Allowed","Request method 'GET' not supported");
        }

        $this->load->model('user/oauth_user_model');

        // 验证必填参数
        $access_token = $this->input->get_post('access_token');
        if(empty($access_token)){
            $this->error_json($this->_Unauthorized,"Full authentication is required to access this resource",'Access token missing');
        }

        // 验证 Token 是否合法
        $flagResult = $this->oauth_user_model->checkGrant($access_token);
        $this->_audience    = isset($flagResult['data']['audience'])?$flagResult['data']['audience']:null;

        if($flagResult['code'] === false){// 限制访问
            $this->error_json($this->_BadRequest,'Request failed',$flagResult['message']);
        }

        $this->_requestData = file_get_contents("php://input");// 请求数据
        if(!empty($this->_requestData)){
            if( ($msg = $this->jsonValidate($this->_requestData)) !== true){
                $this->error_json($this->_BadRequest,'Bad Request','JSON parse error: '.$msg);
            }
        }else{
            $this->_requestData = $_POST;
        }


        if(empty($this->_requestData)){
            //$this->error_json($this->_BadRequest,'Bad Request','Required request body is missing');
        }
    }

    /**
     * 日志系统-记录请求日志
     * @param string $logTable
     */
    protected function saveRequestLog($logTable = null){
        $this->load->library('mongo_db');

        $data = [];
        $data['pathUrl']        = $this->_pathUrl;
        $data['audience']       = $this->_audience;
        $data['remoteIp']       = $this->_remoteIp;
        $data['status']         = $this->_status;
        $data['error']          = $this->_error;
        $data['errorDesc']      = $this->_errorDesc;
        $data['timestamp']      = $this->_timestamp;
        $data['requestData']    = !is_string($this->_requestData)?json_encode($this->_requestData):$this->_requestData;
        $data['responseData']   = !is_string($this->_responseData)?json_encode($this->_responseData):$this->_responseData;

        if(empty($logTable) or !is_string($logTable)){
            $pathUrl  = str_replace('/','_',$data['pathUrl']);
            $logTable = 'OAUTH_LOG_'.trim($pathUrl,'_').'_'.date('Ym').'_'.$this->_audience;
            $logTable = trim($logTable,'_');
        }

        $this->mongo_db->insert($logTable, $data);
    }

    /**
     * 格式化响应返回的数据
     * @author Jolon
     * @param int $status 状态码（成功1，失败0）
     * @param array $data_list 数据（values,key,drop_down_box）
     * @param string $errorMess 操作结果状态提示标识
     * @param string $errorDescription 错误描述
     * @return array
     */
    private function oauthResponseFormat($status = 200, $data_list = [], $errorMess = null, $errorDescription = null)
    {
        $return_data = [];
        $return_data['status'] = $status;
        $return_data['data_list'] = $data_list;

        if($status != $this->_OK){
            $return_data['error'] = $errorMess;
            $return_data['error_description'] = $errorDescription;
        }
        return $return_data;
    }

    /**
     * 输出响应数据
     * @param mixed $data
     * @param int $response_code
     */
    protected function oauthHttpResponse($data = null, $response_code = 200)
    {
        !isset($data['timestamp']) and $data['timestamp'] = time();// 请求时间戳

        header('Content-Type: application/json;charset=utf-8');

        if (function_exists('set_status_header')) {
            set_status_header($response_code);
        }
        echo json_encode($data);
        exit;

    }

    /**
     * 错误提示
     * @param int $status
     * @param string $error
     * @param string $errorDescription
     * @param array $data_list
     */
    protected function error_json(int $status,string $error, string $errorDescription = null,$data_list = [])
    {
        $this->_status = $status;
        $this->_responseData = $data_list;
        $this->_error = $error;
        $this->_errorDesc = $errorDescription;
        $this->saveRequestLog();
        $this->oauthHttpResponse($this->oauthResponseFormat($status,$data_list,$error,$errorDescription?$errorDescription:$error),$status);
    }

    /**
     * 成功的json输出
     * @param int $status
     * @param string|array|object $data_list
     */
    protected function success_json(int $status = 200,$data_list = [])
    {
        $this->_status = $status;
        $this->_responseData = $data_list;
        $this->saveRequestLog();
        $this->oauthHttpResponse($this->oauthResponseFormat($status, $data_list),$status);
    }

    /**
     * 验证请求数据是否是合法的 JSON 格式
     * @param $string
     * @return bool|string
     */
    protected function jsonValidate($string)
    {
        if (is_string($string)) {
            @json_decode($string);
            if(json_last_error() === JSON_ERROR_NONE){
                return true;
            }else{
                return json_last_error_msg();
            }
        } else {
            return false;
        }
    }
}