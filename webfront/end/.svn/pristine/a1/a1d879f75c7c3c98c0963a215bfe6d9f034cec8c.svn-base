<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Httpclient extends Api_base_model{
    
    
    
    
    public function httrequest($params=[],$url,$method='GET'){
       // 1.预处理请求参数
        $params['page_size'] = !isset($params['page_size']) || intval($params['page_size']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['page_size']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End
        // 2.调用接口
        if($method=="GET"){
            $url .= '?' . http_build_query($params);
        }
        $result = $this->httpRequest($url, '', $method);
          // 3.确认返回的数据是否与预期一样
        if (empty($result) || !isset($result['status']) || !isset($result['data_list'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (!empty($result['message'])) {
            $this->_errorMsg = $result['message'];
        }
        if (!$result['status']) {
            return null;
        }
        return $result;
    }
}
