<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/12/12
 * Time: 15:09
 */

class Offline_reason_model extends Api_base_model {
    
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取 线下退款原因配置 数据列表
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function get_offline_reason($params){

        $url = $this->_baseUrl . $this->_getOfflineReasonUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 获取 退款类型 下拉框选择
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function get_refund_type_list($params){

        $url = $this->_baseUrl . $this->_getRefundTypeListUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 新增退款类型
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function create_refund_type($params){

        $url = $this->_baseUrl . $this->_createRefundTypeUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 新增/编辑 退款原因
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function update_refund_reason($params){

        $url = $this->_baseUrl . $this->_updateRefundReasonUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 更新 退款原因 禁用启用状态
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function update_refund_reason_status($params){

        $url = $this->_baseUrl . $this->_updateRefundReasonStatusUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

}