<?php
/**
 * Created by PhpStorm.
 * 权均交期模型
 * User: Jaden
 * Date: 2019/01/16 
 */
class Delivery_model extends Api_base_model {

    protected $table_name   = 'sku_avg_delivery_time';

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }


    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function tableName() {
        return 'sku_avg_delivery_time';
    }


    /**
     * 权均交期列表
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-16
     */
    public function get_delivery_list($params) {
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
     * 权均交期列表导出
     * @author Jaden
     * 2019-1-16
     */
    public function delivery_export_list($params){
        $url = $this->_baseUrl . $this->_exportUrl;
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
     * 获取权限交期的日志数据
     * @params :无
     * @MTHODS :GET
     * @AUTHOR:LUXU
     * @time: 2020/6/15
     **/
    public function getDeliveryLogs($params){

        $url = $this->_baseUrl . $this->_getDeliveryLogs;
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