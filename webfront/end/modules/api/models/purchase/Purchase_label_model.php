<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/29
 * Time: 14:14
 */
class Purchase_label_model extends Api_base_model
{



    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     * 标签列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_label_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_listLabelUrl . "?" . http_build_query($req);
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
            'drop_down_box'=>isset($result['drop_down_box']) ? $result['drop_down_box'] : [],

        ];


        return $result;
    }


    /**
     * 标签列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_barcode_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_listBarcodeUrl . "?" . http_build_query($req);
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
            'drop_down_box'=>isset($result['drop_down_box']) ? $result['drop_down_box'] : [],

        ];


        return $result;
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


    /**

     * @parame $params
     */
    public function provider_promise_barcode($params)
    {

        $url = $this->_baseUrl . $this->_providerPromiseBarcode;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     *
     * @parame $params
     */
    public function send_wms_label($params)
    {

        $url = $this->_baseUrl . $this->_sendWmsLabel;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     * 获取交易订单的物流跟踪信息
     * @author Jolon
     * @parame $params
     */
    public function send_provider_label($params)
    {

        $url = $this->_baseUrl . $this->_sendProviderLabel;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }


    /**
     * 
     * @导出物流标签
     * 
     */
    public function export_label($params){
        $url = $this->_baseUrl . $this->_exportLabelUrl;
        $url .= '?' . http_build_query($params);

        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }


    /**
     * 导出产品条码
     */
    public function export_barcode($params){
        $url = $this->_baseUrl . $this->_exportBarcodeUrl;
        $url .= '?' . http_build_query($params);

        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }


    /**
     * 标签列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    public function get_combine_list($req)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_listCombineUrl . "?" . http_build_query($req);
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
            'drop_down_box'=>isset($result['drop_down_box']) ? $result['drop_down_box'] : [],

        ];


        return $result;
    }

    /**
     * 导出产品条码
     */
    public function export_combine($params){
        $url = $this->_baseUrl . $this->_exportCombineUrl;
        $url .= '?' . http_build_query($params);

        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }

    /**
     *
     * @parame $params
     */
    public function send_wms_combine_label($params)
    {
        $url = $this->_baseUrl . $this->_sendWmsCombineLabel;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }



}