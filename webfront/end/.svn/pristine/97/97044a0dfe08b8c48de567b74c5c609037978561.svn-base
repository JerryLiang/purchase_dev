<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/11
 * Time: 14:29
 */

/**
 * @author jackson
 * @description API对接基础类，尽可能将相似的调用封装在一块，子类只需编写格式化数据的代码就行。
 * 如有特殊情况，子类可重写该类的方法
 * 类名
 */
class Api_base_model extends Api_abstract_model
{

    const MODULE_NAME = 'CAIGOU_SYS'; //采购系统
    protected $_modelName = ''; // 模型名称，init()方法中根据这个来获取配置信息
    protected $_errorMsg = ''; // 错误信息

    protected $_baseUrl; // 统一地址前缀
    protected $_listUrl; // 列表的路径
    protected $_addUrl;  // 创建
    protected $_detailUrl; // 详情

    protected $_defaultPageSize = 20;  // 默认页数
    protected $_maxPageSize = 200; // 最大页数


    // 列表页面，给前端的表头
    protected $_tableHeader = array();
    // 操作日志 数据库字段名 与 显示给前端的名称 对照表
    protected $_fieldTitleMap = array();

    /**
     * 控制api返回的字段
     *
     * @var unknown
     */
    protected $_return_cols;


    const CONTENT_TYPE_JSON = 'application/json';
    const CONTENT_TYPE_NORMAL = 'application/x-www-form-urlencoded';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 初始化配置（接口URL），子类如有特殊情况，可以重写。该方法必须在子类中调用。
     */
    protected function init()
    {
        $modelName = $this->_modelName;

        // 1.根据调用的模块名，解析出对应的配置文件名称
        if (empty($modelName)) // 没有的话就根据类名获取，第一个 _ 之前的字符串
        {
            $realModelName = get_called_class();
            $pos = strrpos($realModelName, '_');

            if ($pos === false) {
                $modelName = $realModelName;
            } else {
                $modelName = substr($realModelName, 0, $pos);
            }

            $this->_modelName = $modelName;
        }

        $modelNameLower = strtolower($modelName);
        $configFile = strtolower(static::MODULE_NAME) . '_' . $modelNameLower;
        $configParams = array();
        $realConfigPath = APPPATH . 'modules/api/conf/' . $configFile . '.php';
        if (file_exists($realConfigPath)) {
            $arr = include_once($realConfigPath);        
            // 配置方式1: 返回数组形式的配置
            if (is_array($arr)) {
                $configParams = $arr;
            } else {
                // 配置方式2: $config['api_...']形式的配置，键名可以是任意名称
                $configKey = 'api_' . $configFile;
                $configParams = isset($config[$configKey]) ? $config[$configKey] : current($config);
            }
        }

        // 3.初始化配置参数
        $host = constant('CG_API_HOST_' . static::MODULE_NAME); // 对应模块的主机名
        if (!empty($configParams)) {
            $configParams['_baseUrl'] = $host . $configParams['_baseUrl'];
            foreach ($configParams as $k => $v) {
                $this->{$k} = $v;
            }
            
        } else {
            echo $realConfigPath, ' does not exist', '<br/>';
            exit();
            // 没有找到配置文件
            $this->_baseUrl = $host . $this->_baseUrl;
        }
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->_errorMsg;
    }

    /**
     * 设置请求的内容类型
     * @param string $contentType
     */
    public function setContentType($contentType = '')
    {
        if ($contentType == 'json') {
            $this->headerArr['Content-Type'] = self::CONTENT_TYPE_JSON;
        } else {
            $this->headerArr['Content-Type'] = self::CONTENT_TYPE_NORMAL;
        }

        // key-value格式的Header 转换成适应curl的 Header
        if (!is_numeric(key($this->headerArr))) // 防止重复处理
        {
            $newHeaderArr = array();
            foreach ($this->headerArr as $k => $v) {
                $newHeaderArr[] = "$k: $v";
            }

            $this->headerArr = $newHeaderArr;
        }
    }

    /**
     * 改写请求的主机名（处理特殊情况）
     */
    public function rewriteHost($newhost = '')
    {
        $host = $newhost ? $newhost : $this->_realHost;
        if (!empty($host)) {
            $slashPos = strpos($this->_baseUrl, '/', 9);
            $basePath = $slashPos === false ? '' : substr($this->_baseUrl, $slashPos);

            $this->_baseUrl = $host . $basePath;
        }
    }

    /**
     * 重写父类的发送请求方法
     *
     * @param $requestUrl
     * @param string $params
     * @param string $methodType
     * @param array $curlOption   [curl请求配置]
     * @return array|mixed
     */
    public function httpRequest($requestUrl, $params = '', $methodType = 'post',$curlOption = [])
    {
        $this->addUserIdentity();
        //$requestUrl = str_replace('/ordersys/api/', '/services/admin_service/', $requestUrl);
        return parent::httpRequest($requestUrl, $params, $methodType,$curlOption);
    }

    /**
     * 添加用户信息
     */
    protected function addUserIdentity()
    {
        $userNumber = $this->session->username;
        $userId = $this->session->uid;

        if (empty($userNumber)) {
            $userNumber = 'devuser';
        }
        if (empty($userId)) {
            $userId = '9999';
        }

        $this->headerArr[] = "X-USER: $userNumber";
        $this->headerArr[] = "X-USERID: $userId";
    }

    public function httrequest($params = [], $url, $method = 'GET')
    {
        // 1.预处理请求参数
        $params['page_size'] = !isset($params['page_size']) || intval($params['page_size']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['page_size']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End
        // 2.调用接口
        if ($method == "GET") {
            $url .= '?' . http_build_query($params);
            $result = $this->httpRequest($url, '', $method);
        }else{

            $result = $this->httpRequest($url, $params, $method);
        }   
        if(isset($result['data']['list'])){
            return $result;
        }
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status']) || !isset($result['data_list'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        
        if (!empty($result['message'])) {
            $this->_errorMsg = $result['message'];
        }
        return $result;
    }

    /**
     * 调用服务层api
     *  get 读数据
     * @param $url
     * @param $param
     * @param string $method
     * @return mixed|array
     * @throws Exception
     */
    protected function _curlReadHandleApi($url, $param, $method = "GET")
    {
        $api_resp = $this->httpRequest($url, $param, $method);
        if (isset($api_resp['status']) && $api_resp['status'] === 1) {
            $return = [];
            $return['status']    = $api_resp['status'];
            $return['data_list'] = isset($api_resp['data_list']) ? $api_resp['data_list'] : [];
            $return['tatal_sum'] = isset($api_resp['data_list']['tatal_sum']) ? $api_resp['data_list']['tatal_sum'] : []; //增加统计字段
            $return['page_data'] = [];
           
            if (isset($api_resp['data_list']['paging_data'])) {
                $return['page_data'] = $api_resp['data_list']['paging_data'];
            } elseif (isset($api_resp['data_list']['page_data'])) { 
                $return['page_data'] = $api_resp['data_list']['page_data'];
            } elseif (isset($api_resp['data_list']['paging_data'])) {
                $return['page_data'] = $api_resp['data_list']['paging_data'];
            }elseif (isset($api_resp['page_data'])) {
                $return['page_data'] = $api_resp['page_data'];
            }else{
            }
            return $return;

        } else {
            $msg = isset($api_resp['errorMess']) ? $api_resp['errorMess'] :'';
            throw new Exception($msg, -1);
        }
    }


    /**
     * 调用服务层api
     *  post 写操作
     * @author liwuxue
     * @date 2019/1/30 16:51
     * @param $url
     * @param $param
     * @param $method
     * @return mixed
     * @throws Exception
     */
    public function _curlWriteHandleApi($url, $param, $method = "POST")
    {
        $api_resp = $this->httpRequest($url, $param, $method);
        if (isset($api_resp['status']) && $api_resp['status'] == 1) {
            //操作成功
            $this->_errorMsg = isset($api_resp['errorMess']) ? $api_resp['errorMess'] : "操作成功！！！";
            return ['status' => $api_resp['status'],"data_list" => isset($api_resp['data_list']) ? $api_resp['data_list'] : []];
        } else {
            //失败
            if(isset($api_resp['status']) and isset($api_resp['data_list']) and $api_resp['data_list']){
                $this->_errorMsg = isset($api_resp['errorMess']) ? $api_resp['errorMess'] : "操作失败！！！";
                return ['status' => $api_resp['status'],"data_list" => $api_resp['data_list']];
            }else{
                $msg = isset($api_resp['errorMess']) ? $api_resp['errorMess'] : "";
                throw new Exception($msg, -1);
            }
        }
    }

    public function request_http($params = [], $url, $method = 'GET', $type = true)
    {
        if ($type) {
            // 1.预处理请求参数
            $params['page_size'] = !isset($params['page_size']) || intval($params['page_size']) <= 0 ?
                $this->_defaultPageSize :
                min(intval($params['page_size']), $this->_maxPageSize);

            if (!isset($params['page']) || intval($params['page']) <= 0) {
                $params['page'] = 1;
            }
        }

        // End
        // 2.调用接口
        if ($method == "GET") {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        }
        $result = $this->httpRequest($url, '', $method);
        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (!empty($result['message'])) {
            $this->_errorMsg = $result['message'];
        }
        return $result;
    }

    /**
     * @desc 调用接口及结果分析
     * @author Jackson
     * @parames string $methodUrl 请求地址
     * @parames array $parames 请求参数
     * @parames string $method 请求方法(POST,GET)
     * @Date 2019-02-11 09:59:00
     * @return array()
     **/
    public function _curlRequestInterface($methodUrl = '', $parames = array(), $method = 'POST')
    {
        //请求参数(GET时带地址参数，POST时直接传递参数)
        $_requestParames = $parames;

        //1.请求地址
        $url = $this->_baseUrl . $methodUrl;
        //2.请求接口(GET地址带参数,POST直接传参数)
        if ($method == 'GET') {
            $url .= '?' . http_build_query($parames);
            $_requestParames = '';//GET时不直接传值
        }
        $result = $this->httpRequest($url, $_requestParames, $method);

        // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status'])) {
            if(isset($result) and isset($result['error']) and stripos($result['error'],'404 Page Not Found')){
                $this->_errorMsg = $methodUrl.'：404 Page Not Found';
            }else{
                $this->_errorMsg = "Request Error:" . json_encode($result);
            }
            return null;
        }
        //4. 返回结果不成功则返回错误信息
        if (!$result['status']) {
            $this->_errorMsg = isset($result['message']) ? $result['message'] : '';
            if (isset($result['errorMess'])) {
                $this->_errorMsg = $result['errorMess'];
            }
            if (isset($result['data_list']) and !empty($result['data_list'])) {// 错误的时候也可能返回数据
                return $result;
            }elseif (isset($result['data']) and !empty($result['data'])) {// 错误的时候也可能返回数据
                return $result;
            }else{
                return null;
            }
        }
        return $result;

    }

    /**
     * 将后端返回的格式，统一转换为前端格式
     *
     * @param unknown $rsp
     */
    protected function rsp_package($rsp)
    {
        if (empty($rsp))
        {
            $rsp['status'] = 0;
            $rsp['errorCode'] = 1002;
            log_message('ERROR', sprintf('request api %s return null', $this->uri->uri_string()));
        }
        if (!isset($rsp['status']) || intval($rsp['status']) !== 1)
        {
            $rsp['status'] = 0;
        }
        if (isset($rsp['data']) && is_array($rsp['data'])){
            $rsp['data_list'] = $rsp['data'];
            unset($rsp['data']);
        }

        if ($_ret_cols = ($this->_return_cols[$this->uri->uri_string()] ?? []))
        {
            $flip_ret_cols = array_flip($_ret_cols);
            //要求数据格式为data_list
            if (isset($rsp['data_list']['value']))
            {
                //过滤字段
                foreach ($rsp['data_list']['value'] as $ik => $val)
                {
                    $rsp['data_list']['value'][$ik] = array_intersect_key($val, $flip_ret_cols);
                }
            }

        }
        $this->data = $rsp;
        return $this->data;
    }

    public function request_appdal($params = [], $url, $method = 'GET', $type = false)
    {
        if ($type) {
            // 1.预处理请求参数
            $params['page_size'] = !isset($params['page_size']) || intval($params['page_size']) <= 0 ?
                $this->_defaultPageSize :
                min(intval($params['page_size']), $this->_maxPageSize);

            if (!isset($params['page']) || intval($params['page']) <= 0) {
                $params['page'] = 1;
            }
        }

        // End
        // 2.调用接口
        if ($method == "GET") {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            $result = $this->httpRequest($url, '', $method);

        }elseif ($method == "POST"){
            $result = $this->httpRequest($url, $params, $method);

        }else{
            return null;
        }


        if (isset($_GET['__debug']) && (strpos($url, CG_API_HOST_CAIGOU_SYS) !== false))
        {
            pr($result);
            ob_end_flush();
            exit;
        }
        return $result;
    }
}