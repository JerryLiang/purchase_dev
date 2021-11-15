<?php

/**
 *
 * Class HttpClient
 * @author: 凌云
 * @Desc: 处理HTTP请求，接口授权认证
 * @since: 20180921
 *
 */

class HttpClient
{


    /**
     * @var string
     * $host主机域名或IP，$port端口，$path接口路径
     */
    protected $host, $port, $path;
    protected $method;
    protected $postdata = '';
    protected $cookies = array();
    protected $referer;
    protected $accept = 'text/xml,application/xml,application/xhtml+xml,text/html,text/plain,image/png,image/jpeg,image/gif,*/*';
    protected $accept_encoding = 'gzip';
    protected $accept_language = 'en-us';
    protected $user_agent = 'Incutio HttpClient v0.9b';


    /**
     * @var int
     * 定义接口超时时间
     */
    protected $timeout = 20;
    /**
     * @var bool
     * 是否启用压缩
     */
    protected $use_gzip = true;
    protected $persist_cookies = true;
    protected $persist_referers = true;
    protected $debug = HTTP_CLIENT_DEBUG;
    protected $handle_redirects = true;
    protected $max_redirects = 5;
    protected $headers_only = false;

    /**
     * @var $username 接口授权账号
     * @var $password 接口授权密码
     */
    protected $username, $password;

    protected $status;

    /**
     * @var array
     * 头部信息
     */
    protected $headers = array();
    protected $content = '';

    /**
     * @var $errormsg socket请求错误信息
     */
    protected $errormsg;

    protected $redirect_count = 0;

    /**
     * HttpClient constructor.
     * @param string $host 主机名或IP
     * @param int $port 端口
     */
    public function __construct($host = '', $port = 80)
    {

        $this->host = $host;
        $this->port = $port;

    }

    //设置端口
    public function setPort($port)
    {
        $this->port = $port;
    }

    public static function getInstance($host = '', $port = 80)
    {
        return Register::_set('HttpClient', new self($host, $port));
    }

    public function __destruct()
    {
        foreach ($this as $index => $value) unset($this->$index);
    }

    public function __toString()
    {
        return $this->getContent();
    }

    /**
     * Get方式请求
     * @param $path 接口请求地址，不包含IP与端口部分
     * @param null $data array 参数
     * @return bool|mixed
     */
    public function get($path, $data = null)
    {


        $this->path = $path;
        $this->method = 'GET';

        if ($data) $this->path .= '?' . createLinkstring($data);

        return $this->doRequest();

    }

    /**
     * POST方式请求
     * @param $path 接口请求地址，不包含IP与端口部分
     * @param null $data array 参数
     * @return bool|mixed
     */
    public function post($path, $data)
    {

        $this->path = $path;
        $this->method = 'POST';

        if (is_array($data)) {
            $this->postdata = stripslashes(decode_unicode(json_encode($data)));
        }

//         $this->postdata = createLinkstring($data);

        return $this->doRequest();
    }

    /**
     * PUT方式请求
     * @param $path 接口请求地址，不包含IP与端口部分
     * @param null $data array 参数
     * @return bool|mixed
     */
    public function put($path, $data)
    {

        $this->path = $path;
        $this->method = 'PUT';

        if (is_array($data)) {
            $this->postdata = json_encode($data);
        }

//         $this->postdata = createLinkstring($data);

        return $this->doRequest();

    }

    /**
     * DELETE方式请求
     * @param $path 接口请求地址，不包含IP与端口部分
     * @param null $data array 参数
     * @return bool|mixed
     */
    public function delete($path, $data)
    {

        $this->path = $path;
        $this->method = 'DELETE';
//         $this->postdata = createLinkstring($data);
        if ($data) $this->path .= '?' . createLinkstring($data);

        return $this->doRequest();

    }

    /**
     * 请求成功，响应http 200
     */
    public function ok()
    {
        return ($this->status == 200);
    }

    public function getContent()
    {
        return $this->content;
    }

    /**
     * 获取接口请求http状态值
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 设置http头部值
     * @param string $key 头部键
     * @param string $value 头部键对应的值
     */
    public function setHeaders($key = '', $value = '')
    {
        if (!empty($key) && !empty($value)) {
            $this->headers[] = $key . ':' . $value;
        }
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader($header)
    {
        $header = strtolower($header);
        if (isset($this->headers[$header])) {
            return $this->headers[$header];
        } else {
            return false;
        }
    }

    /**
     * @return socket请求错误信息
     */
    public function getError()
    {
        return $this->errormsg;
    }

    public function getCookies($host = null)
    {

        return @$this->cookies[$host ? $host : $this->host];

    }

    /**
     * @return string 返回请求接口完整地址
     */
    public function getRequestURL()
    {
        $url = 'http://' . $this->host;
        if ($this->port != 80) {
            $url .= ':' . $this->port;
        }
        $url .= $this->path;
        return $url;
    }

    /**
     * @return string 返回请求接口路径
     */
    public function getPath()
    {
        return $this->path;
    }

    public function setUserAgent($string)
    {
        $this->user_agent = $string;
    }

    /**
     * 设置授权账号与密码
     * @param $username
     * @param $password
     */
    public function setAuthorization($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function setCookies($array, $replace = false)
    {

        if ($replace || !is_array(@$this->cookies[$this->host]))
            $this->cookies[$this->host] = array();

        $this->cookies[$this->host] = ($array + $this->cookies[$this->host]);

    }

    /**
     * @param $boolean 设置请求是否开启压缩 1开启 0不开启
     */
    public function useGzip($boolean)
    {
        $this->use_gzip = $boolean;
    }

    public function setPersistCookies($boolean)
    {

        $this->persist_cookies = $boolean;

    }

    public function setPersistReferers($boolean)
    {
        $this->persist_referers = $boolean;
    }

    public function setHandleRedirects($boolean)
    {
        $this->handle_redirects = $boolean;
    }

    public function setMaxRedirects($num)
    {
        $this->max_redirects = $num;
    }

    public function setHeadersOnly($boolean)
    {
        $this->headers_only = $boolean;
    }

    /**
     * 是否开启调试
     * @param $boolean 1.开启，将于浏览器输出调试信息 0不开启
     */
    public function setDebug($boolean)
    {
        $this->debug = $boolean;
    }

    /**
     * 设置接口请求路径
     * @param $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    public function setMethod($method)
    {
        if (!in_array($method, array("GET", "POST"))) trigger_error("HttpClient::setMethod() : '$method' is not a valid method", E_USER_ERROR);
        $this->method = $method;
    }

    /**
     * GET请求
     * @param $url 接口路径
     * @param null $data 参数
     * @param array $header 头信息
     * @return HttpClient
     */
    public function quickGet($url, $data = null, $header = array())
    {

        $client = $this->create($url, $header);
        $client->get($client->getPath(), $data);
        return $client;

    }

    /**
     * POST请求
     * @param $url 接口路径
     * @param null $data 参数
     * @param array $header 头信息
     * @return HttpClient
     */
    public function quickPost($url, $data, $header = array())
    {

        $client = $this->create($url, $header);

        $client->post($client->getPath(), $data);

        return $client;

    }

    /**
     * PUT请求
     * @param $url 接口路径
     * @param null $data 参数
     * @param array $header 头信息
     * @return HttpClient
     */
    public function quickPut($url, $data, $header = array())
    {

        $client = $this->create($url, $header);
        $client->put($client->getPath(), $data);
        return $client;

    }

    /**
     * DELETE请求
     * @param $url 接口路径
     * @param null $data 参数
     * @param array $header 头信息
     * @return HttpClient
     */
    public function quickDelete($url, $data, $header = array())
    {

        $client = $this->create($url, $header);
        $client->delete($client->getPath(), $data);
        return $client;

    }

    public function create($url, $headers = array())
    {

        $bits = parse_url($url);
        $path = isset($bits['path']) ? $bits['path'] : '/';

        if (isset($bits['query']))
            $path .= '?' . $bits['query'];
        if ($headers) {
            foreach ($headers as $k => $v) {
                $this->setHeaders($k, $v);
            }
        }

        $this->setPath($path);

        return $this;

    }

    protected function debug($msg, $object = false)
    {

        if ($this->debug) {

            pr('HttpClient Debug:' . $msg);
            if ($object) pr($object);

        }

    }

    public function doRequest()
    {

        if (!$fp = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout)) {

            switch ($errno) {
                case -3:
                    $this->errormsg = 'Socket creation failed (-3)';
                case -4:
                    $this->errormsg = 'DNS lookup failure (-4)';
                case -5:
                    $this->errormsg = 'Connection refused or timed out (-5)';
                default:
                    $this->errormsg = 'Connection failed (' . $errno . ')';
                    $this->errormsg .= ' ' . $errstr;
                    $this->debug($this->errormsg);
            }

            return false;
        }

        socket_set_timeout($fp, $this->timeout);

        $request = $this->buildRequest();
        $this->debug($request);

        fwrite($fp, $request);

        $this->headers = array();
        $this->content = '';
        $this->errormsg = '';

        $inHeaders = true;
        $atStart = true;

        while (!feof($fp)) {

            $line = fgets($fp, 4096);

            if ($atStart) {

                $atStart = false;

                if (!preg_match('/HTTP\/(\\d\\.\\d)\\s*(\\d+)\\s*(.*)/', $line, $m)) {
                    $this->errormsg = "Status code line invalid: " . htmlentities($line);
                    $this->debug($this->errormsg);
                    return false;
                }

                $http_version = $m[1];
                $this->status = $m[2];
                $status_string = $m[3];

                $this->debug(trim($line));

                continue;

            }

            if ($inHeaders) {

                if (trim($line) == '') {
                    $inHeaders = false;
                    $this->debug('Received Headers', $this->headers);
                    if ($this->headers_only) {
                        break;
                    }
                    continue;
                }

                if (!preg_match('/([^:]+):\\s*(.*)/', $line, $m)) {
                    continue;
                }

                $key = strtolower(trim($m[1]));
                $val = trim($m[2]);

                if (isset($this->headers[$key])) {
                    if (is_array($this->headers[$key])) {
                        $this->headers[$key][] = $val;
                    } else {
                        $this->headers[$key] = array($this->headers[$key], $val);
                    }
                } else {
                    $this->headers[$key] = $val;
                }

                continue;

            }

            $this->content .= $line;

        }

        fclose($fp);

        /**
         * 判断接口是否有开启压缩，有则解压
         */
        if (isset($this->headers['content-encoding']) && $this->headers['content-encoding'] == 'gzip') {
            $this->debug('Content is gzip encoded, unzipping it');
            $this->content = substr($this->content, 10);
            $this->content = gzinflate($this->content);
        }

        if ($this->persist_cookies && isset($this->headers['set-cookie'])) {

            $cookies = $this->headers['set-cookie'];

            if (!is_array($cookies))
                $cookies = array($cookies);

            if (!is_array(@$this->cookies[$this->host]))
                $this->cookies[$this->host] = array();

            foreach ($cookies as $cookie) {
                if (preg_match('/([^=]+)=([^;]+);/', $cookie, $m)) {
                    $this->cookies[$this->host][$m[1]] = $m[2];
                }
            }

        }

        if ($this->persist_referers) {
            $this->debug('Persisting referer: ' . $this->getRequestURL());
            $this->referer = $this->getRequestURL();
        }

        if ($this->handle_redirects) {

            if (++$this->redirect_count >= $this->max_redirects) {
                $this->errormsg = 'Number of redirects exceeded maximum (' . $this->max_redirects . ')';
                $this->debug($this->errormsg);
                $this->redirect_count = 0;
                return false;
            }

            $location = isset($this->headers['location']) ? $this->headers['location'] : '';
            $location .= isset($this->headers['uri']) ? $this->headers['uri'] : '';
            if ($location) {
                $this->debug("Following redirect to: $location" . (@$url['host'] ? ", host: " . $url['host'] : ''));
                $url = parse_url($location);
                if (@$url['host']) $this->host = $url['host'];
                return $this->get(($url['path']{0} == '/' ? '' : '/') . $url['path']);
            }

        }

        return true;

    }

    protected function buildRequest()
    {

        $headers = array();
        $headers[] = "{$this->method} {$this->path} HTTP/1.0";
        $headers[] = "Host: {$this->host}";
        $headers[] = "User-Agent: {$this->user_agent}";
        $headers[] = "Accept: {$this->accept}";

        if (!empty($this->headers)) {
            $headers = array_merge($headers, $this->headers);
        }

        if ($this->use_gzip)
            $headers[] = "Accept-encoding: {$this->accept_encoding}";

        $headers[] = "Accept-language: {$this->accept_language}";

        if ($this->referer)
            $headers[] = "Referer: {$this->referer}";

        if (@$this->cookies[$this->host]) {
            $cookie = 'Cookie: ';
            foreach ($this->cookies[$this->host] as $key => $value) {
                $cookie .= "$key=$value; ";
            }
            $headers[] = $cookie;
        }

        if ($this->username && $this->password)
            $headers[] = 'Authorization: BASIC ' . base64_encode($this->username . ':' . $this->password);

        if ($this->postdata) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($this->postdata);
        }

        $request = implode("\r\n", $headers) . "\r\n\r\n" . $this->postdata;

        return $request;

    }


}
?>
