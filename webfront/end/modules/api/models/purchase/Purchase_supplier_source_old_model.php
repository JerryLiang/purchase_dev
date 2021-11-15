<?php
/**
 * Created by PhpStorm.
 *
 * User: luxu
 * Date: 2019/07/29
 */
class Purchase_supplier_source_old_model extends Api_base_model {

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');

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

    public function get_supplier_source( $params ) {

        $url = $this->_baseUrl . $this->_set_supplier;
        $result = $this->httrequest($params, $url);
        return $result;
    }


}