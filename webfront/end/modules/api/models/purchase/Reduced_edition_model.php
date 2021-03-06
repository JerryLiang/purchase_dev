<?php
/**
 * Created by PhpStorm.
 * sku降本模型
 * User: Jaden
 * Date: 2019/01/16 
 */
class Reduced_edition_model extends Api_base_model {

    protected $table_name   = 'sku_reduced_edition';

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
        return 'sku_reduced_edition';
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
    public function get_reduced_list($params) {
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
     * sku降本列表导出
     * @author Jaden
     * 2019-1-16
     */
    public function reduced_export_list($params){
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

    public function reduced_export_data( $params )
    {
        $url = $this->_baseUrl . $this->_reduced_export_data;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }


    /**
     * sku采购记录
     * @author Jaden
     * 2019-1-16
     */
    public function get_sku_history_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_purchaseskuUrl;
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
     * 价格变化趋势
     * @author Jaden 2019-1-10
     * @param int $status  1涨价,2降价
     */
    public function get_price_trend_status($status=''){
            $status_arr = [
                ''=>'全部',
                '1'  => '涨价',
                '2' => '降价',
               
            ];
    return !empty($status)?$status_arr[$status]:$status_arr;
    }

    /**
      * SKU 降本优化记录
     **/
    public function get_reduced_data( $params )
    {
        $url = $this->_baseUrl . $this->_get_reduced_data;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }

        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }

    /**
      * function:SKU 降本配置信息
     **/
    public function get_reduced_config($params)
    {
        $url = $this->_baseUrl . $this->_get_reduced_config;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }

        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }

    /**
     * function:SKU 修改降本配置信息
     **/
    public function update_reduced_config($params)
    {
        $url = $this->_baseUrl . $this->_update_reduced_config;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     * function:SKU 修改降本配置信息日志
     **/
    public function get_reduced_log($params)
    {
        $url = $this->_baseUrl . $this->_get_reduced_log;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     * SKU 降本信息
     * @author luxu
     * @time:2019-09-29
     **/
    public function get_reduced_data_list( $params )
    {
        $url = $this->_baseUrl . $this->_get_reduced_list;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        return $result;
    }

    /**
     * 获取SKU 降本明细
     * @param  $params    string   HTTP 传入信息
     **/
    public function get_reduced_detail_data( $params )
    {
        $url = $this->_baseUrl . $this->_get_reduced_detail;

        $result = $this->httrequest($params, $url);

        return $result;
    }

    /**
     * 获取SKU 降本新老模块数据操作日志
     * @param:  $params   array   HTTP 传入参数
     * @author: luxu
     **/
    public function get_set_reduced_data_log( $params )
    {
        $url = $this->_baseUrl . $this->_get_set_reduced_data_log;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }

    /**
     * 设置SKU 降本新老模块数据
     * @param:  $params   array   HTTP 传入参数
     * @author: luxu
     **/
    public function set_reduced_data( $params )
    {
        $url = $this->_baseUrl . $this->_set_reduced_data;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }



}