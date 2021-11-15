<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-07-09
 * Time: 9:32
 */

class Offline_refund_model extends Api_base_model
{

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 线下退款列表
     * @methods GET
     * @author:luxu
     * @time:2021年1月12号
     **/

    public function get_offline_refund($params){

        $url = $this->_baseUrl . $this->_get_offline_refund;
        return $this->_curlReadHandleApi($url, $params, 'POST');
    }

    public function add_offline_refund($params){

        $url    = $this->_baseUrl . $this->_add_offline_refund.'?uid='.$params['uid'];
        $result = getCurlData($url,$params,'post');
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 线下退款列表 导出
     * @methods POST
     * @author:luxu
     * @time:2021年1月12号
     **/
    public function get_offline_refund_import($params){

        $url = $this->_baseUrl . $this->_get_offline_refund_import;
        return getCurlData($url,$params,'post');
    }

    /**
     * 作废申请
     * @author:luxu
     * @time:2021年1月14号
     **/

    public function cancel_refund($params){

        $url    = $this->_baseUrl . $this->_cancel_refund;
        $result = getCurlData($url,$params,'post');
        return $result;
    }
    /**
     * 获取退款日志接口
     * @params
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function get_refund_logs($params){

        $url = $this->_baseUrl . $this->_get_refund_logs."?uid=".$params['uid'];
        $result = getCurlData($url,$params,'post');
        $result = json_decode($result,true);
        return $result;
    }

    public function get_offline_reason($params){

        $url = $this->_baseUrl . $this->_get_offline_reason . "?uid=" . $params['uid'];

        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 获取1688拍单号
     * 1688拍单号根据填写的采购单号抓取"采购单"页面的同字段内容;采购单号无拍单号的.
     * 显示为空,采购单号为多个时,抓取第一个拍单号;若第一个为空,顺位抓取下一个拍单号;若全部为空,则显示为空
     * @methods  GET
     * @author:luxu
     * @time:2021年1月15号
     **/
    public function get_pai_number($params){

        $url = $this->_baseUrl . $this->_get_pai_number;
        $result = $this->_curlReadHandleApi($url, $params, 'POST');

        return $result;
    }
}