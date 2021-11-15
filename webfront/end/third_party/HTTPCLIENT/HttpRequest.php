<?php

require APPPATH . "third_party/HTTPCLIENT/HttpClient.php";

/**
 *
 * Class HttpRequest
 *
 * HTTP请求类，主要处理：
 *
 *  1.GET、POST、PUT、DELETE等方式请求
 *  2.接口签名生成
 *
 * @author: 凌云
 * @since: 20180921
 *
 *
 */
class HttpRequest
{

    /**
     * @var string
     * 定义接口返回格式类型，目前支持json、XML
     */
    public $format = 'json';
    /**
     * @var string
     * 接口请求token
     */
    private $_token = '';


    /**
     * @var string
     * 接口请求地址
     */

    private $_host = '';

    /**
     * http请求状态码
     * @var int $_status
     */
    private $_status = 200;

    /**
     * 接口请求的SESSION ID
     * @var string
     */
    private $_session_id = '';


    public function __construct() {

    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function setHost($host){
        $this->_host = $host;
    }

    /**
     * 设置接口认证信息
     * @param string $authData
     */
    public function setToken($debug=0,$host = '')
    {
            if($debug) pr('[setToken] refresh token , Try to login and set new token...');

            $params =array(
                'loginName' => AUTH_USER,
                'loginPasswd' => AUTH_PWD,
                'wId' => 1,
                'sysKey' => SYS_KEY
            );

            $this->setHost($host);
            $result = $this->post(API_SERURITY_URL.'login',$params,WMS_SERURITY_PORT);
            if (HTTP_CLIENT_DEBUG)pr($result);
            if(isset($result['status']) && $result['status'] && isset($result['result']) && $result['result']){

//                //将用户认证信息保存到redis
//                $ci->rediss->setData('auth_data_'.$host,array(
//                    'token' => $result['result']['token'],
//                    'session_id' => $result['result']['sessionId']
//                ));
                $this->_session_id = $result['result']['sessionId'];
                $this->_token = $result['result']['token'];
                $result = null;
//            } else {
//                logger($logLever = 'debug','Login','Error:Try to get access_token fail!');
//            }

        }
    }

    /**
     * GET请求
     * @param string $url 接口地址，除去主机名与端口部分，如'api/wms/v1/warehouse'
     * @param array $params 接口参数，以数组形式传递
     * @param string $method 接口请求方式，GET或POST
     * @param string $format 返回的数据类型，支持JSON与XML
     * @return array|mixed|string
     */
    public function get($url = '', $params = array(), $port = WMS_API_PORT, $method = 'GET', $format = 'json')
    {
        $data = $this->request($url, $method, $params, $port);

        if ($format == 'json') {
            return json_decode($data, true);
        } else if ($format == 'xml') {
            return (array)simplexml_load_string($data);
        }

        return $data;
    }

    /**
     * POST请求
     * @param string $url 接口地址，除去主机名与端口部分，如'api/wms/v1/warehouse'
     * @param array $params 接口参数，以数组形式传递
     * @param string $method 接口请求方式，GET或POST
     * @param string $format 返回的数据类型，支持JSON与XML
     * @return array|mixed|string
     */
    public function post($url = '', $params = array(), $port = WMS_API_PORT, $method = 'POST', $format = 'json')
    {

        $data = $this->request($url, $method, $params, $port);

        if ($format == 'json') {
            return json_decode($data, true);
        } else if ($format == 'xml') {
            return (array)simplexml_load_string($data);
        }

        return $data;
    }

    /**
     * PUT请求
     * @param string $url 接口地址，除去主机名与端口部分，如'api/wms/v1/warehouse'
     * @param array $params 接口参数，以数组形式传递
     * @param string $method 接口请求方式，GET或POST或PUT或DELETE
     * @param string $format 返回的数据类型，支持JSON与XML
     * @return array|mixed|string
     */
    public function put($url = '', $params = array(), $port = WMS_API_PORT, $method = 'PUT', $format = 'json')
    {

        $data = $this->request($url, $method, $params, $port);

        if ($format == 'json') {
            return json_decode($data, true);
        } else if ($format == 'xml') {
            return (array)simplexml_load_string($data);
        }

        return $data;
    }

    /**
     * DELETE请求
     * @param string $url 接口地址，除去主机名与端口部分，如'api/wms/v1/warehouse'
     * @param array $params 接口参数，以数组形式传递
     * @param string $method 接口请求方式，GET或POST或PUT或DELETE
     * @param string $format 返回的数据类型，支持JSON与XML
     * @return array|mixed|string
     */
    public function delete($url = '', $params = array(), $port = WMS_API_PORT, $method = 'DELETE', $format = 'json')
    {

        $data = $this->request($url, $method, $params, $port);

        if ($format == 'json') {
            return json_decode($data, true);
        } else if ($format == 'xml') {
            return (array)simplexml_load_string($data);
        }

        return $data;
    }

    /**
     * 设置接品请求SESSION_ID
     * @param string $sessionId
     * @return bool|string
     */
    public function setSeesionID($sessionId = '')
    {
        if (empty($sessionId)) return false;
        return $this->_session_id = $sessionId;
    }

    /**
     * @param $url
     * @param string $method
     * @param array $params
     * @param $port
     * @return string
     */
    private function request($url, $method = 'POST', $params = array(), $port)
    {

        if (empty($url))
            return '{"code":0, "msg":"url is null"}';

        /**
         * 生成签名
         */
        $signData = $this->getSignature($params, $url);

        if (!empty($signData)) {
            $header['signature'] = $signData;
            $header['x-auth-token'] = $this->_session_id;
            $header['platform'] = PLATFORM;
//            $header['token'] = $this->_token;
        } else {
            return '{"code":0, "msg":"Signatura is null"}';
        }

        if (empty($this->_host)){
            $this->_host = WMS_API_HOST;
        }

        //过滤空值和null
        if(!empty($params)) {
            foreach ($params as $k => $v) {
                if ($v === '' || $v === null) unset($params[$k]);
            }
        }

        $HttpClient = HttpClient::getInstance($this->_host, $port);
        switch ($method) {
            case 'GET':
                if(!empty($params)) {
                    $url = "/$url?" . createLinkstring($params);
                } else {
                    $url = "/$url";
                }
                $response = $HttpClient->quickGet($url, null, $header);
                break;
            case 'POST':
                $url = "/$url";

                $HttpClient->setPort($port);
                $response = $HttpClient->quickPost($url, $params, $header);
                break;
            case 'PUT':
                $url = "/$url";

                $response = $HttpClient->quickPut($url, $params, $header);
                break;
            case 'DELETE':
                $url = "/$url";

                $response = $HttpClient->quickDelete($url, $params, $header);
                break;
            default:
                return '{"code":0, "msg":"illegal method"}';
                break;
        }
        $this->_status = $HttpClient->getStatus();

        $content = $response->getContent();

        if ($this->_checkHttpRequest($content)) {
            return $content;
        } else {
            global $http_status_code;
            $http_status_code = $this->_status;
            return json_encode(array(
                'status' => 0,
                'errorMess' => 'Invalid connection, session is out of date.',
                'http_status_code' => $this->_status
            ));
        }
    }

    /**
     * 生成签名
     *首先, 对(非空)参数按名称升序进行拼接(如参数为a=1&b=2&c=3)，则为'a1b2c3'
     *然后, 在拼接的字符串前边拼接上接口路径，待签名串为'api/wms/v1/warehousea1b2c3'，然后再将待签名串hash_hmac加密，bin2hex后转为大写，得到最终签名
     * @param $params Array 接口请求参数
     * @param $url String 接口路径
     * @return String 返回签名
     */
    private function getSignature($params = array(), $url = '')
    {

        if (empty($url)) return false;

        $signatureStr = '';

        if (!empty($params)) {
            ksort($params);
            foreach ($params as $k => $v) {
                if ($v === '' || $v === null) continue;

                if (gettype($v) == 'array') {
                    $v = stripslashes(decode_unicode(json_encode($v)));
                }
                $signatureStr .= $k . $v;
            }
        }

        $signatureStr = $url . $signatureStr;
//        pr($signatureStr);
        return strtoupper(bin2hex(hash_hmac('sha1', $signatureStr, $this->_token, true)));

    }

    /**
     * 检验请求状态
     * @return bool
     */
    protected function _checkHttpRequest($data='')
    {

        if(!empty($data)) {
            $data = json_decode($data,true);

            if (isset($data['errorcode']) && $data['errorcode'] == '0012') {
                $ci = &get_instance();

                $ci->rediss->deleteData('auth_data');
                $this->setToken();
                return false;
            }
        }

        return true;
    }

    public function timestamp()
    {
        list($usec, $sec) = explode(" ", microtime());
        return $sec . ((int)($this->microtime_float() * 1000));
    }

    public function __destruct()
    {
        foreach ($this as $index => $value) {
            unset($this->$index);
        }
    }

}


?>
