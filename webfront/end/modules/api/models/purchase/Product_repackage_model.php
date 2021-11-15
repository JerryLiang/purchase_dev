<?php
/**
 * Created by PhpStorm.
 * 二次包装模型
 * User: Jaden
 * Date: 2019/01/16 
 */
class Product_repackage_model extends Api_base_model {

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }


    /**
     * 二次包装列表
     * @author Jaden
     * @param $params  传参数
     * 2019-1-16
     */
    public function get_product_repackage_list($params) {
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
     * 导入
     * @author Jaden 2019-1-10
     * @param array $import_arr  导入的文件
     */
    public function import_packaging($import_arr){

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
     * @param array $import_arr 
     */
    public function delete_pack($params){

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
     * 审核
     * @author Jaden 2019-1-10
      * @param $params  传参数
     */
    public function examine($params){

        $url = $this->_baseUrl . $this->_examineUrl;
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




}