<?php
/**
 * Created by PhpStorm.
 * User: Jeff
 * Date: 2019/5/5
 * Time: 14:14
 */
class Suggest_expiration_set_model extends Api_base_model
{
    protected $_baseUrl = "";
    protected $_getListApi = "";
    protected $_editExpirationApi = "";

    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     *
     * @author liwuxue
     * @date 2019/1/29 14:43
     * @param $url
     * @param $param
     * @param $method
     * @throws Exception
     * @return mixed|array
     */
    public function httpRequest($url, $param = '', $method = "POST",$curlOption = [])
    {
        $result = parent::httpRequest($url, $param, $method);
        $this->verifyApiResp($result);
        return $result;
    }

    /**
     * 处理服务层返回结果
     * @author liwuxue
     * @date 2019/1/29 14:21
     * @param $api_resp
     * @throws Exception
     */
    private function verifyApiResp($api_resp)
    {
        if (!isset($api_resp['status']) || $api_resp['status'] !== 1) {
            if(isset($api_resp['errorMess']) and $api_resp['errorMess']){
                throw new Exception($api_resp['errorMess'], -1);
            }else{
                throw new Exception(json_encode($api_resp, JSON_UNESCAPED_UNICODE), -1);
            }
        }
    }

    public function get_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getListApi . "?" . http_build_query($req);;
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
//            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }

    public function edit_expiration($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_editExpirationApi;
        $result = parent::httpRequest($url, $post, 'POST');
        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:".json_encode($result,256),
                -1
            );
        }
    }
}