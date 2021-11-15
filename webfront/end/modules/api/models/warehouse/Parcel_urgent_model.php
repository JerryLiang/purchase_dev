<?php
/**
 * Created by PhpStorm.
 * 包裹加急
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Parcel_urgent_model extends Api_base_model {
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }




    /**
     * 获取 包裹加急
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function web_get_logistics_urgent_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_listUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        // 3.确认返回的数据是否与预期一样
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
     * 包裹加急导入
     * @author Jaden
     * 2019-1-16
     */
    public function web_import_logistics($import_arr){

        $url = $this->_baseUrl . $this->_importUrl;
        $url .= '?' . http_build_query($import_arr);
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
     * 删除
     * @author Jaden 2019-1-10
     * @param array $params  
     */
    public function web_delete_logistics($params){

        $url = $this->_baseUrl . $this->_deleteUrl;
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
     * 包裹加急手动推送
     * @author Jaden
     * 2019-1-16
     */
    public function web_push_logistics_list_list($params){
        $url = $this->_baseUrl . $this->_pustUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['errorMess'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }

    /**
     * 包裹加急导出
     * @author jeff
     * 2019-1-16
     */
    public function web_logistics_urgent_export($params){
        $url = $this->_baseUrl . $this->_exportUrl;
        $url .= '?' . http_build_query($params);
        $data = getCurlData($url, "","POST", "",false,array('time_out'=>600,'conn_out'=>600));
        $result = json_decode($data,true);
        return $result;
    }



}