<?php

/**
 *  Api access for HTTP REQUEST
 * @author 凌云
 * @since 20180921
 *
 * 调用示例
 *
 *      $curlRequest = CurlRequest::getInstance();
 *      $result = $curlRequest->cloud_get('material/material/viewmaterial',array('sku' => '90508.01', 'material_id' => '100632'));
 *
 * 生成缩略图调用示例：
 *      CurlRequest::img2thumb($src_img, $dst_img, $width = 100, $height = 100, $cut = 0, $proportion = 0);
 *
 */


class CurlRequest {

    /**
     * @var string api地址
     */
    public $api_server;
    /**
     * @var string api key
     */
    public $api_key;
    /**
     * @var string api密钥
     */
    public $api_secret;
    /**
     * @int
     */
    public $app_id;

    /**
     * @var string session_id
     */
    private $session_id;

    /**
     * @var object CurlRequest
     */
    private static $_instance;

    /**
     * api初始化
     */
    private function __construct() {
     
    }

    /**
     * 设置session_id
     */
    public function setSessionId($session_id){
        $this->session_id = $session_id;
    }

    /**
     * 实例访问入口，单例
     * @return CurlRequest实例
     */
    public static function getInstance() {
        if(!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 设置服务Host和秘钥
     */
    public function setServer($server = '',$api_secret = '',$app_id = ''){
        if (!empty($server)){
            $this->api_server = $server;
        }

        if (!empty($api_secret)){
            $this->api_secret = $api_secret;
        }

        if (!empty($app_id)){
            $this->app_id = $app_id;
        }
    }


    public function cloud_get($url='', $params=array(),$format_json=1) {
        $data = $this->request($url, 'GET', $params);

        if($format_json) return json_decode($data, true);

        return $data;
    }

    public function cloud_post($url='', $params=array(),$format_json=1) {
        $data = $this->request($url, 'POST', $params);
    
        if($format_json) return json_decode($data, true);

        return $data;
    }


    /**
     * @desc 生成缩略图
     * @param $src_img 源图绝对完整地址{带文件名及后缀名}
     * @param $dst_img 目标图绝对完整地址{带文件名及后缀名}
     * @param int $width 缩略图宽{0:此时目标高度不能为0，目标宽度为源图宽*(目标高度/源图高)}
     * @param int $height 缩略图高{0:此时目标宽度不能为0，目标高度为源图高*(目标宽度/源图宽)}
     * @param int $cut 是否裁切{宽,高必须非0}
     * @param int $proportion 缩放{0:不缩放, 0<this<1:缩放到相应比例(此时宽高限制和裁切均失效)}
     * @return bool
     */
    public static function img2thumb($src_img, $dst_img, $width = 100, $height = 100, $cut = 0, $proportion = 0) {
        if(!is_file($src_img)) {
            return false;
        }
        $ot = self::fileext($dst_img);
        $otfunc = 'image' . ($ot == 'jpg' ? 'jpeg' : $ot);
        $srcinfo = getimagesize($src_img);
        $src_w = $srcinfo[0];
        $src_h = $srcinfo[1];
        $type  = strtolower(substr(image_type_to_extension($srcinfo[2]), 1));
        $createfun = 'imagecreatefrom' . ($type == 'jpg' ? 'jpeg' : $type);

        $dst_h = $height;
        $dst_w = $width;
        $x = $y = 0;

        /**
         * 缩略图不超过源图尺寸（前提是宽或高只有一个）
         */
        if(($width> $src_w && $height> $src_h) || ($height> $src_h && $width == 0) || ($width> $src_w && $height == 0)) {
            $proportion = 1;
        }
        if($width> $src_w) {
            $dst_w = $width = $src_w;
        }
        if($height> $src_h) {
            $dst_h = $height = $src_h;
        }

        if(!$width && !$height && !$proportion) {
            return false;
        }
        if(!$proportion) {
            if($cut == 0) {
                if($dst_w && $dst_h) {
                    if($dst_w/$src_w> $dst_h/$src_h) {
                        $dst_w = $src_w * ($dst_h / $src_h);
                        $x = 0 - ($dst_w - $width) / 2;
                    } else {
                        $dst_h = $src_h * ($dst_w / $src_w);
                        $y = 0 - ($dst_h - $height) / 2;
                    }
                } else if($dst_w xor $dst_h) {
                    if($dst_w && !$dst_h) { //有宽无高
                        $propor = $dst_w / $src_w;
                        $height = $dst_h  = $src_h * $propor;
                    }
                    else if(!$dst_w && $dst_h) {  //有高无宽
                        $propor = $dst_h / $src_h;
                        $width  = $dst_w = $src_w * $propor;
                    }
                }
            } else {
                if(!$dst_h) { //裁剪时无高
                    $height = $dst_h = $dst_w;
                }
                if(!$dst_w) { //裁剪时无宽
                    $width = $dst_w = $dst_h;
                }
                $propor = min(max($dst_w / $src_w, $dst_h / $src_h), 1);
                $dst_w = (int)round($src_w * $propor);
                $dst_h = (int)round($src_h * $propor);
                $x = ($width - $dst_w) / 2;
                $y = ($height - $dst_h) / 2;
            }
        } else {
            $proportion = min($proportion, 1);
            $height = $dst_h = $src_h * $proportion;
            $width  = $dst_w = $src_w * $proportion;
        }

        $src = $createfun($src_img);
        $dst = imagecreatetruecolor($width ? $width : $dst_w, $height ? $height : $dst_h);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        if(function_exists('imagecopyresampled')) {
            imagecopyresampled($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        } else {
            imagecopyresized($dst, $src, $x, $y, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
        }
        $otfunc($dst, $dst_img);
        imagedestroy($dst);
        imagedestroy($src);
        return true;
    }

    /**
     * @desc 返回文件的拓展名
     * @param $file 文件绝对路径
     * @return string 文件拓展名
     */
    public function fileext($file) {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    private function request($url, $method, $params=array()) {
        if(empty($url))
            return '{"code":0, "msg":"url or params is null"}';


        $params['appid']       = $this->app_id;
        if (!empty($params)){
          $params['token'] = $this->token($params);
        }

        $headers['x-auth-token'] = $this->session_id;

        switch($method){
            case 'GET':
                $params_str = '';
                if (!empty($params)){
                    $params_str = $this->createLinkstring($params);
                }
                $url = $this->api_server . "/$url?" . $params_str;
                $response = $this->http($url, 'GET',array(),$headers);
                break;
            default:
                $url = $this->api_server . "/$url";

                $response = $this->http($url, 'POST', $params,$headers);
        }
        return $response;
    }


    private function createLinkstring($para)
    {

        $arg = "";

        foreach ($para as $key => $val) {
            //if(empty($val)) continue;
            if ($val === '' || $val === null) continue;
            if (is_array($val)) $val = json_encode($val);
            $arg .= $key . "=" . urlencode($val) . "&";
        }

        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }


    /**
     * token生成
     * md5("a=a&b=b&c=c".API密钥)
     */
    private function token($params) {
        unset($params['token']);
        unset($params['api_key']);

        $params = ascSort($params);
        $token = $this->createLinkstring($params) . $this->api_secret;

        return md5($token);
    }

    /**
     * @param $url API URL
     * @param null $data 请求参数
     * @param array $headers 请求头信息
     * @return mixed
     * @throws Exception
     */
    public function http_request($url, $data=null, $headers = array(),$method = 'POST'){
       
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }


        if (is_array($data) && 0 < count($data)){
            $postBodyString = "";
            $postMultipart = false;
            foreach ($data as $k => $v){
                if("@" != substr($v, 0, 1))//�ж��ǲ����ļ��ϴ�
                    $postBodyString .= "$k=" . urlencode($v) . "&";
                else//�ļ��ϴ���multipart/form-data��������www-form-urlencoded
                    $postMultipart = true;
            }
            unset($k, $v);

            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart)
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            else
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString,0,-1));

        }

        $reponse = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch),0);
        else{
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode)
                throw new Exception($reponse,$httpStatusCode);
        }

        curl_close($ch);

        return $reponse;
    }

    /**
     * @param $url api url
     * @param $method http请求方式，GET/POST
     * @param array $params 参数
     * @param array $headers 请求头
     * @return mixed
     * @throws Exception
     */
    private function http($url, $method, $params=array(),$headers=array()){

        if($method == 'POST') {
            $response = $this->http_request($url,$params,$headers);
        } else {
            $response = $this->http_request($url,array(),$headers);
        }

        return $response;
    }

    /**
     * 参数加密
     * @param $params
     * @return mixed
     */
    private function encryption($params){
        $this->ci =& get_instance();
        $this->ci->load->library('rsa');

        //设置加解密的秘钥
        $this->ci->rsa->setKey(APPDAL_PRIVATE_KEY,APPDAL_PUBLIC_KEY);
        $encrypt_params =  $this->ci->rsa->publicEncrypt(json_encode($params));

        return $encrypt_params;
    }

    /**
     * @param $url api url
     * @param $method http请求方式，GET/POST
     * @param array $filePath 参数
     * @param array $headers 请求头
     * @return mixed
     * @throws Exception
     */
    public  function uploadFile($url, $filePath, $postParam)
    {

        if (empty($url)){
            return '{"code":0, "msg":"url is null"}';
        }

        $url = DAL_API_HOST.$url;

        include_once APPPATH.'libraries/Java_rsa.php';

        $rsa = new Java_rsa();

        $headers[] = 'Authorization: '.DAL_API_AUTHORIZATION;

        if (version_compare(phpversion(), '5.5.0') >= 0 && class_exists('CURLFile')) {

            $file = new CURLFile(realpath($filePath));

        } else {

            $file = '@' . $filePath;

        }

        $data = ["file" => $file];


        $param = array_merge($postParam, $data);

        $ch    = curl_init($url);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_HEADER, 0);

        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        ob_start();
        curl_exec($ch);
        $reponse = ob_get_contents();
        ob_end_clean();

        if (curl_errno($ch) != 0)

            return curl_error($ch);

        curl_close($ch);

        if (!empty($reponse)){
            $reponse = $rsa->PublicDecrypt($reponse);
        }

        if(!empty($reponse)){
            $reponse = json_decode($reponse,true);
        }

        return $reponse;
    }

    public function curl_get($url='', $params=array(),$format_json=1) {
        $data = $this->curl_request($url, 'GET', $params);

        if($format_json) return json_decode($data, true);

        return $data;
    }

    public function curl_post($url='', $params=array(),$format_json=1) {
        $data = $this->curl_request($url, 'POST', $params);

        if($format_json) return json_decode($data, true);

        return $data;
    }

    private function curl_request($url, $method, $params=array()) {
        if(empty($url))
            return '{"code":0, "msg":"url or params is null"}';

        $headers[] = 'Authorization: '.DAL_API_AUTHORIZATION;
        $headers[] = 'Content-Type: application/json; charset=utf-8';

        switch($method){
            case 'GET':
                $params_str = '';
                if (!empty($params)){
                    $params_str = $this->createLinkstring($params);
                }
                $url = $this->api_server . "/$url?" . $params_str;
                $response = $this->curl_http($url,array(),$headers,'GET');
                break;
            default:
                $url = $this->api_server . "/$url";

                $response = $this->curl_http($url, $params,$headers,'POST');
        }
        return $response;
    }


    /**
     * @param $url API URL
     * @param null $data 请求参数
     * @param array $headers 请求头信息
     * @param string $method 请求头类型
     * @return mixed
     * @throws Exception
     */
    public function curl_http($url, $data=null, $headers = array(),$method = 'POST'){
        include_once APPPATH.'libraries/Java_rsa.php';

        $rsa = new Java_rsa();
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        curl_setopt($ch, CURLINFO_HEADER_OUT, true); //TRUE 时追踪句柄的请求字符串，从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header


        if ($method = 'POST'){
            curl_setopt($ch, CURLOPT_POST, 1);

            if (!empty($data)){
                //接口已非对称加密传输数据
                $data = $rsa->PublicEncrypt(json_encode($data));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                $headers[] = 'Content-Length:'.strlen($data);
            }else{
                $headers[] = 'Content-Length: 0';
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        ob_start();
        curl_exec($ch);
        $reponse = ob_get_contents();
        ob_end_clean();
    
        if (curl_errno($ch))
            throw new Exception(curl_error($ch),0);
        else{
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (200 !== $httpStatusCode)
                logger('error','日志错误',$reponse);
        }

        curl_close($ch);

        if (!empty($reponse)){
            $reponse = $rsa->PublicDecrypt($reponse);
        }

        return $reponse;
    }

}