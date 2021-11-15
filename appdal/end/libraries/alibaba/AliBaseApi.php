<?php
include_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AliAccount.php';
/**
 * Class AliBaseApi
 * 阿里巴巴 1688批发平台 对接基本API
 * @author:Jolon
 * @date 2019-03-18
 */
class AliBaseApi {
    protected $_errorMsg = null;

    public $aliAccountObj = null;
    public $_java_access_taken = null;

    public function __construct(){
        $this->aliAccountObj = new AliAccount();
        $this->aliAccountObj->setAccountInfo();

        $this->_java_access_taken = getOASystemAccessToken();
    }

    /**
     * 获取 固定的头部信息
     * @return array
     */
    public function getHeaders(){
        $header = ['Content-Type: application/json'];

        return $header;
    }

    /**
     * 获取固定参数
     * @param string $account
     * @return mixed
     */
    public function getFixedParams($account = null){
        if(!is_null($account)) $this->aliAccountObj->setAccount($account);

        $aliAccountInfo = $this->aliAccountObj->getAccountInfo();

        $post_data['appKey']      = $aliAccountInfo['app_key'];
        $post_data['secKey']      = $aliAccountInfo['secret_key'];
        $post_data['accessToken'] = $aliAccountInfo['access_token'];

        return $post_data;

    }

    /**
     * POST 方式发送 CURL 请求
     * @param string $url 请求链接
     * @param array $post_data  发送的数据
     * @return mixed|string
     */
    public function curlPost($url,$post_data = array(), $use_header=false){
        $header = $this->getHeaders();
        if($use_header && count($use_header) > 0)$header = $use_header;
        if(stripos($url,'access_token') === false ) $url .= "?access_token=".$this->_java_access_taken;
        $result = getCurlData($url,json_encode($post_data),'post',$header);
        apiRequestLogInsert(
            [
                'record_number' => 'AliBaseApi',
                'record_type' => $url,
                'post_content' => json_encode($post_data),
                'response_content' => $result
            ],
            'api_request_ali_log'
        );
        return $result;
    }

    /**
     * 解析 JAVA 接口返回的错误结果信息
     * @param $result
     * @return bool|string
     */
    public function analysisResult($result){
        if(is_json($result)) $result = json_decode($result,true);

        // JAVA层错误
        if(isset($result['status']) and $result['status'] != 200){
            $_error   = isset($result['error']) ? $result['error'] : '';
            $_message = isset($result['message']) ? $result['message'] : '';
            $_path    = isset($result['path']) ? $result['path'] : '';
            return $_error.$_message.$_path;
        }
        if(isset($result['code']) and $result['code'] != 200){
            $_message = isset($result['msg']) ? $result['msg'] : '';
            return $_message;
        }

        if(isset($result['error']) and $result['error'] == 'invalid_token'){
            return 'Access token expired';
        }
        if(isset($result['error_description'])){
            return $result['error_description'];
        }

        return false;
    }

    /**
     * 组装返回的数据
     * @param null $data
     * @return array
     */
    public function returnData($data = null){
        if(is_null($data) and $this->_errorMsg){
            return [
                'code'     => false,
                'errorMsg' => $this->_errorMsg
            ];
        }else{
            return [
                'code'     => true,
                'errorMsg' => $this->_errorMsg,
                'data'     => $data
            ];
        }
    }

}