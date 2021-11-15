<?php
/**
 * @Author: King
 * @Date:   2018-12-06 09:22:06
 * @Last Modified by:   anchen
 * @Last Modified time: 2019-01-05 16:05:21
 */

class Api_abstract_model  extends MY_Model
{
    const ACCESS_KEY = 'TMS_ORDER_SYSTEM_666888';  //访问秘钥

    const ACCESS_USERNAEM = 'TMS_ADMIN_SERVICE_SYSTEM'; //请求唯一账号

    /**
     * @desc 身份是否通过验证
     * @var int
     */
    protected $timeStamp ;


    /**
     * [$AccessSign 请求签名]
     * @var null
     */
    protected $AccessSign=null;


    /**
     * @desc 请求头
     * @var array
     */
    protected $headerArr = [];


    protected $errorMsg;

    /**
     * [__construct 构造方法]
     */
    public function __construct()
    {
        parent::__construct();

        $this->timeStamp=time();

        //$this->headerArr['Accept']='text/json';
        $this->headerArr['Content-Type']='application/json';
        //$this->headerArr['Accept-Language']='zh-cn';
        $this->headerArr['Authorization']='Basic '.$this->createAccessSign();
    }


    /**
     * [sendHttpRequest 发送API请求]
     * @param  [type] $requestUrl [url]
     * @param  string $params     [请求数据]
     * @param  string $methodType [请求类型]
     * @param array $curlOption   [curl请求配置]
     * @return mixed
     */
    public function httpRequest($requestUrl, $params = '', $methodType = 'post',$curlOption = [])
    {
        //$responseData = $this->http_helper->sendRequest($requestUrl.'/timeStamp/'.$this->timeStamp, $params, $methodType, $this->headerArr);
        $responseData = $this->http_helper->sendRequest($requestUrl, $params, $methodType, $this->headerArr,$curlOption);
        //请求响应头信息 包含http请求状态码，一般的，请求状态码不是200，说明请求失败，比如遇到了400错误、500服务器错误，参考资料：https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Status#成功响应
        //$this->responseHeaders = $this->http_helper->getResponseHeaders();//通过请求响应header头信息，判断请求是否成功

        if ($responseData === false){
            $this->errorMsg = $this->http_helper->getErrorMessage();
            return $responseData=['error'=>$this->errorMsg ];
        }
        if(!is_json($responseData)){
            return $responseData = ['error' => $responseData];
        }

        return json_decode($responseData,TRUE);
    }


    /**
     * @desc 记录日志
     * @param unknown $filename
     * @param unknown $log
     * @return boolean
     */
    public function writeLog($log=[])
    {
        /**
         *
         */
    }


    /**
     * @param $randomStr 随机字符串
     * @return string 返回签名
     */
    protected function createAccessSign()
    {
        $arr['timeStamp']  =  $this->timeStamp;
        $arr['randomStr']  =  self::ACCESS_KEY.self::ACCESS_USERNAEM;
        $arr['access_key'] =  self::ACCESS_KEY;
        //按照首字母大小写顺序排序
        sort($arr,SORT_STRING);
        //拼接成字符串
        $str = implode($arr);
        //进行加密
        $AccessSign = md5($str);
        //转换成大写
        $AccessSign = strtoupper($AccessSign);
        return self::ACCESS_USERNAEM.'_'.$AccessSign;
    }

}