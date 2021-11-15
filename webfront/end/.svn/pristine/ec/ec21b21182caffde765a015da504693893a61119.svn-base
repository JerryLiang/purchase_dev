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
require_once APPPATH . "third_party/CurlRequest.php";

class MY_Controller extends MX_Controller
{

    /**
     * 用户信息
     * @var array
     */
    protected $_user_data = array();


    /**
     * 返回前端的数据
     * @Array
     */
    public $data;
    /**
     * 分页数据
     * @var array
     */
    public $paging_data = array();
    /**
     * 当前使用的语言
     * @var string
     */
    public $main_lan = '';

    /**
     * 错误码
     * @var int
     */
    public $error_code = 0;

    public function __construct()
    {

        parent::__construct();

        /**
         * 接口请求初始状态值，默认为0
         * 成功时需赋值1返回前端
         */
        $this->data['status'] = 0;
        $this->paging_data = $this->config->item('paging_data');


        if(false && $this->_urlEnableCrossOrigin()){
            // 在需要 支持跨域的方法前 调用即可实现跨域访问
            header('Content-Type: text/html;charset=utf-8');
            header('Access-Control-Allow-Origin:*'); // *代表允许任何网址请求
            header('Access-Control-Allow-Methods:POST,GET'); // 允许请求的类型
            header('Access-Control-Allow-Credentials: true'); // 设置是否允许发送 cookies
            header('Access-Control-Allow-Headers: Content-Type,Content-Length,Accept-Encoding,X-Requested-with, Origin'); // 设置允许自定义请求头的字段
        }

        /**
         * 检测用户是否登录
         */
        if(! $this->_checkLogin()) {
            if($this->input->get_post('request_type') || $this->input->get_post('initData')) {
                http_response(array(
                    'status' => 0,
                    'errorMess' => '登录失效！',
                    'errorCode' => $this->error_code,
                    'path' => SECURITY_PATH,
                    'http_status_code' => 401
                ),401);
            } else {
                if($this->error_code == 402) {
                    http_response(array(
                        'status' => 0,
                        'errorMess' => '当前用户已在其他地方登录！',
                        'errorCode' => $this->error_code,
                        'path' => SECURITY_PATH,
                        'http_status_code' => $this->error_code
                    ),$this->error_code);
                } else {
                    http_response(array(
                        'status' => 0,
                        'errorMess' => '登录失效',
                        'errorCode' => $this->error_code,
                        'path' => SECURITY_PATH,
                        'http_status_code' => 401
                    ),401);
                }
//                    exit;
            }
        }

    }


    /**
     * 格式化渲染数据
     * $paging_data For example:
     * $paging_data = [
     *    'total'=>100, //总条数
     *    'offset'=>1, //当前页码
     *    'limit'=>20, //每页条数
     * ];
     * $has_paging: 1存在分页 0不存在分页，默认0
     *
     * $http_response 1.http请求响应 0.页面首次加载赋值
     *
     * return json
     */
    public function render($data, $module_name, $api_name, $paging_data = array(), $select_list = array(), $http_response = 0)
    {
        if (empty($module_name) || empty($api_name)) return false;

        $has_paging = 0;
        if (isset($paging_data) && !empty($paging_data)) {
            if (!isset($paging_data['total']) || !is_numeric($paging_data['total'])) {
                return false;
            }
            if (!isset($paging_data['offset']) || !is_numeric($paging_data['offset'])) {
                $paging_data['offset'] = $this->paging_data['offset'];
            }
            if (!isset($paging_data['limit']) || !is_numeric($paging_data['limit'])) {
                $paging_data['limit'] = $this->paging_data['page_size'];
            }
            $has_paging = $paging_data['total'] <= $paging_data['limit'] ? 0 : 1;
        }

        $result = array(
            'version_codes' => getVersion(),
            'module_name' => $module_name,
            'api_name' => $api_name,
            'viewUrl' => getViewPath(),
            'baseUrl' => base_url(),
            'has_paging' => $has_paging,
            'select_list' => $select_list,
            'paging_data' => $paging_data,
            'list' => $data,
        );
        if ($http_response) {
            http_response($result);
        }
        return json_encode($result);
    }

    /**
     * 一个页面渲染多个列表
     * @param unknown $data
     * @param unknown $module_name
     * @param unknown $api_name
     * @param unknown $paging_data
     * @param unknown $select_list
     * @param number $http_response
     * @return boolean
     */
    public function renderMulti($data, $dataChild, $module_name, $api_name, $paging_data = array(), $select_list = array(), $http_response = 0)
    {
        if (empty($module_name) || empty($api_name)) return false;

        $has_paging = 0;
        $has_paging_child = 0;

        if (isset($paging_data) && !empty($paging_data)) {
            if (!isset($paging_data['total']) || !is_numeric($paging_data['total'])) {
                return false;
            }
            if (!isset($paging_data['offset']) || !is_numeric($paging_data['offset'])) {
                $paging_data['offset'] = $this->paging_data['offset'];
            }
            if (!isset($paging_data['limit']) || !is_numeric($paging_data['limit'])) {
                $paging_data['limit'] = $this->paging_data['page_size'];
            }
            $has_paging = $paging_data['total'] <= $paging_data['limit'] ? 0 : 1;

//            if (!isset($paging_data['total_child']) || !is_numeric($paging_data['total_child'])) {
//                return false;
//            }
            if (!isset($paging_data['child_offset']) || !is_numeric($paging_data['child_offset'])) {
                $paging_data['child_offset'] = $this->paging_data['offset'];
            }
            if (!isset($paging_data['child_limit']) || !is_numeric($paging_data['child_limit'])) {
                $paging_data['child_limit'] = $this->paging_data['page_size'];
            }
            $has_paging_child = $paging_data['child_total'] <= $paging_data['child_limit'] ? 0 : 1;

        }

        $result = array(
            'version_codes' => getVersion(),
            'module_name' => $module_name,
            'api_name' => $api_name,
            'viewUrl' => getViewPath(),
            'baseUrl' => base_url(),
            'has_paging' => $has_paging,
            'has_paging_child' => $has_paging_child,
            'select_list' => $select_list,
            'paging_data' => $paging_data,
            'list' => $data,
            'listChild' => $dataChild,
        );
        if ($http_response) {
            http_response($result);
        }
        return json_encode($result);
    }

    public function renderChild($data,$paging_data = array()) {

        $has_paging = 0;
        if (!empty($paging_data)) {
            if (!isset($paging_data['child_total']) || !is_numeric($paging_data['child_total'])) {
                return false;
            }
            if (!isset($paging_data['child_offset']) || !is_numeric($paging_data['child_offset'])) {
                $paging_data['child_offset'] = $this->paging_data['offset'];
            }
            if (!isset($paging_data['child_limit']) || !is_numeric($paging_data['child_limit'])) {
                $paging_data['child_limit'] = $this->paging_data['page_size'];
            }
            $has_paging = $paging_data['child_total'] <= $paging_data['child_limit'] ? 0 : 1;
        }

        $result = array(
            'has_paging_child' => $has_paging,
            'paging_data' => $paging_data,
            'listChild' => $data,
        );
        http_response($result);

    }

    /**
     * 设置需要 支持跨域访问的 URL
     * @return string[]
     */
    protected function _urlEnableCrossOrigin(){
        return [
            '/api/statement/purchase_statement/download_statement_pdf',
            '/api/statement/purchase_statement/batch_upload_statement_pdf',
            '/api/statement/purchase_statement/batch_download_statement',
            '/api/compact/compact/batch_upload_compact_original_scan_file',
            '/api/compact/compact/batch_download_compact',
            '/api/compact/compact/get_compact_export',
        ];
    }

    /**
     * 设定 未受阻的 的方法
     * @return array
     */
    public function unimpededMethod(){
        $aliases = [
            '/api/statement/purchase_statement/download_statement_pdf',
            '/api/statement/purchase_statement/batch_upload_statement_pdf',
            '/api/statement/purchase_statement/batch_download_statement',
            '/api/compact/compact/batch_upload_compact_original_scan_file',
            '/api/compact/compact/batch_download_compact',
            '/api/compact/compact/get_compact_export',
            '/api/statement/purchase_statement/download_statement_pdf',

        ];

        return $aliases;
    }

    /**
     * 检测用户是否已登录
     * @return bool
     */
    protected function _checkLogin() {
        if (!LOGIN_VALIDATE){
            return true;
        }

        // 特定接口 无需验证Cookie信息
        if($index = stripos($_SERVER['REQUEST_URI'],'?')){
            $action_route = substr($_SERVER['REQUEST_URI'],0,$index);
        }else{
            $action_route = $_SERVER['REQUEST_URI'];
        }
        if( !empty($action_route) and in_array($action_route,$this->unimpededMethod()) ){
            return true;
        }

        $userData = $this->cookie->getData('userdata');

        if(isset($userData['uid']) && !empty($userData['uid'])) {

            $authData = $this->rediss->getData($userData['uid']);
            if(isset($authData['session_id']) && !empty($authData['session_id'])) {
                if($authData['session_id'] != $userData['session_id']) {
                    /**
                     * 用户在其他地方登录，当前登录失效
                     */
                    $this->cookie->deleteData('userdata');
                    $this->error_code = 402;
                    return false;
                }

                $this->_token = array(
//                    'token' => $authData['token'],
                    'session_id' => $authData['session_id']
                );

                $this->_user_data = $userData;

                $curlRequest = CurlRequest::getInstance();
                $curlRequest->setSessionId($authData['session_id']);
                $curlRequest->setServer(SECURITY_API_HOST,SECURITY_API_SECRET,SECURITY_APP_ID);
                $params =array(
                    'user_id'=> $userData['uid']
                );
                $curlRequest->cloud_post('login/login/updateStatus', $params);
                //更新用户状态，以免登录失效
                $this->rediss->setData($userData['uid'],$authData);
                $this->cookie->setData('userdata',$userData);
                return true;
            }
        }
        $this->error_code = 401;
        return false;
    }

}

// END MY_Controller class

/* End of file MY_Controller.php */
/* Location: ./app/core/MY_Controller.php */