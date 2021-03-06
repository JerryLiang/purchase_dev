<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * MY_Controller 控制器核心类
 *
 * @author:    凌云
 * @since: 2018-09-21
 *
 */


require APPPATH . "third_party/MX/Controller.php";


class MY_Controller extends MX_Controller
{
    protected static $_check_login = false;// 是否验证用户登录 true.验证
    protected static $_check_log_error = null;

    /**
     * 返回前端的数据
     * @Array
     */
    public $data;

    public function __construct()
    {
        parent::__construct();

        if(self::$_check_login){// 验证用户登录 Jolon
            if( ! $this->_check_login()){
                $this->error_json(self::$_check_log_error);
            }
        }

        // 开启记录用户访问日志
        $LOG_API_URL = $this->rediss->getData("LOG_API_URL");
        if(!empty($LOG_API_URL)){
            define("LOG_API_URL",$LOG_API_URL);
            $this->record_user_access_log();
        }else{
            define("LOG_API_URL",null);
        }

        /**
         * 接口请求初始状态值，默认为0
         * 成功时需赋值1返回前端
         */
        $this->data['status'] = 0;
        //校验请求token是否合法
        if(defined('VALID_REQUEST') && VALID_REQUEST === true) {
            $params = $this->_get_params();

            $params_validate_data = strtolower(json_encode($params));

            $select_intercept = (strrpos($params_validate_data,'select') && strrpos($params_validate_data,'from')) ;
            $insert_intercept = (strrpos($params_validate_data,'insert') && strrpos($params_validate_data,'into'));
            //$update_intercept = (strrpos($params_validate_data,'update') && strrpos($params_validate_data,'set'));
            $delete_intercept = (strrpos($params_validate_data,'delete') && strrpos($params_validate_data,'from'));
            $show_intercept = (strrpos($params_validate_data,'show') && strrpos($params_validate_data,'tables')) ||
                (strrpos($params_validate_data,'databases') && strrpos($params_validate_data,'databases'))
            ;

            if ($select_intercept || $insert_intercept || $delete_intercept || $show_intercept){
                $this->data['errorCode'] = 4008;
                http_response($this->data);
            }

            $this->client_ip = get_client_ip();
            $data=array('params'=>$params,'ip'=>$this->client_ip);
            $this->load->library('Valid_access',$data,'valid');
    
            $this->valid->check_access();
        }
    }

    /**
     * 验证用户是否登录
     * @author Jolon
     * @return bool
     */
    protected function _check_login(){//判断登录的方法
        // 从 redis 中获取登录的用户信息
        $this->load->model('user/user_model');
        $result = $this->user_model->_init(getActiveUID());
        if($result === true){
            return true;
        }else{
            self::$_check_log_error = $this->user_model->getCheckLoginError();
            return false;
        }
    }

    //获取http请求参数
    private function _get_params() {
        if($_SERVER['REQUEST_METHOD'] == 'GET') {
            return get();
        } else {
            return  post();
        }
    }

    /**
     * 返回响应统一方法
     * @param $data
     * @param $msg
     * @param $status
     */
    protected function send_data($data, $msg, $status)
    {
        // 获取列表字段的功能统一在这处理
        if (($fieldList = gp('__withList')) && is_array($data)) {
            $this->load->model('services/Metadata_model');
            $dropdown_box = $this->Metadata_model->batch_get_list($fieldList);
            $data['drop_down_box'] = $dropdown_box;
        }
        // End

        self::convert_to_json($data, $msg, $status);
    }


    /**
     * 返回响应统一方法
     * @param $data
     * @param $msg
     * @param $status
     */
    protected function send_data_v2($data, $msg, $status)
    {
        // 获取列表字段的功能统一在这处理
        if (($fieldList = gp('__withList')) && is_array($data)) {
            $this->load->model('services/Metadata_model');
            $dropdown_box = $this->Metadata_model->batch_get_list($fieldList);
            $data['drop_down_box'] = $dropdown_box;
        }
        // End

        self::convert_to_json_v2($data, $msg, $status);
    }

    /**
     * 描述:转换成json对象
     * @author Jackson
     * @param string $data
     * @param string $message
     * @param string $status
     */
    protected static function convert_to_json($data='',$message='',$status=false)
    {
        header("Content-type:application/json; charset=utf-8");
        $jsonData = array(
            'status' => $status,
            'message' => $message,
            'data' => $data,
        );
        if (empty($data) && empty($message)) {
            $jsonData['message'] = '参数不能为空';
        }
        echo json_encode($jsonData,JSON_UNESCAPED_UNICODE);
        die;
    }


    protected static function convert_to_json_v2($data='',$message='',$status=false)
    {
        header("Content-type:application/json; charset=utf-8");
        $jsonData = array(
            'status' => $status,
            'message' => $message,
            'data' => $data,
        );
        if (empty($data) && empty($message)) {
            $jsonData['message'] = '参数不能为空';
        }
        echo json_encode($jsonData,JSON_FORCE_OBJECT);
        die;
    }

    /**
     * 错误提示
     * @author Jolon
     * @date 2019/2/27 9:54
     * @param mixed $data
     * @param string $error
     *      {"status":0,"errorMess":"$error"}
     */
    protected function error_data_json($data,$error = null)
    {
        http_response(response_format(0, $data, $error));
    }

    /**
     * 错误提示
     * @author liwuxue
     * @date 2019/2/13 9:54
     * @param string $error
     *      {"status":0,"errorMess":"$error"}
     */
    protected function error_json($error)
    {
        http_response(response_format(0, [], $error));
    }

    /**
     * 成功的json输出
     * @author liwuxue
     * @date 2019/2/13 9:56
     * @param array $data
     * @param array $page_data
     * @param string $error
     *  {"status":1,"errorMess":"", "data_list":{}, "paging_data":{}}
     */
    protected function success_json($data = [], $page_data = null, $error = "恭喜您,请求成功")
    {

        http_response(response_format(1, $data, $error, $page_data));
    }

    /**
     * 成功的json输出（格式化数据）
     *      同 success_json，唯一不同的是格式化数据
     * @author Jolon
     * @date 2021/04/09 9:56
     * @param array $data
     * @param array $page_data
     * @param string $error
     *  {"status":1,"errorMess":"", "data_list":{}, "paging_data":{}}
     */
    protected function success_json_format($data = [], $page_data = null, $error = "恭喜您,请求成功")
    {
        $data = format_price_multi_floatval($data);
        http_response(response_format(1, $data, $error, $page_data));
    }

    /**
     * 格式化分页参数
     *  @author Jaxton
     */
    protected function format_page_data(){
        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        if(empty($limit) or $limit < 0 ) $limit = 20;
        $offset        = ($page - 1) * $limit;
        return [
            'offset' => $offset,
            'limit' => $limit,
            'page' => $page
        ];
    }

    /**
     * 后端接口兼容restful(url采用/分割key/val和直接get参数?&拼接）
     */
    protected function compatible($method = 'get')
    {
        //额外鉴权参数
        $auth_col = ['token', 'appid', 'session_id', 'session_uid'];

        if ($method == 'get')
        {
            $get = $this->input->get();
            $url = $this->uri->uri_to_assoc(4);
            return array_diff_key(array_merge($get, $url), array_flip($auth_col));
        }
        elseif ($method == 'put')
        {
            $put = $this->input->input_stream();
            $post = $this->input->post();
            return array_diff_key(array_merge($put, $post), array_flip($auth_col));
        }
        else
        {
            return array_diff_key($this->input->post(), array_flip($auth_col));
        }
    }


    /**
     * @return string
     */
    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 记录用户访问请求参数
     */
    protected function record_user_access_log(){
        if(LOG_API_URL){
            $this->load->helper('url');
            $access_url = uri_string();
            if(stripos($access_url,LOG_API_URL) !== false){
                $this->load->library('monolog');
                $this->monolog->debug(getActiveUID() .'-'.getActiveUserId().'-'.getActiveUserName(),$_REQUEST);
            }
        }
    }

}

// END MY_Controller class

/* End of file MY_Controller.php */
/* Location: ./app/core/MY_Controller.php */