<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/10/31
 * Time: 17:53
 */
class Purchase_order_tracking_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    public function get_logistics_trace_list($params)
    {
        $url =  $this->_baseUrl .$this->_logisticsTraceUrl;
        return $this->request_http($params, $url, 'GET', false);
    }


    public function get_express_url($params)
    {
        $url =  $this->_baseUrl .$this->_getExpressUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    public function get_logistics_track_detail($params)
    {
        $url =  $this->_baseUrl .$this->_logisticsTrackDetailUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    public function refresh_logistics_state($params)
    {
        $url =  $this->_baseUrl .$this->_logisticsTrackRefreshUrl;
        return $this->request_http($params, $url, 'GET', false);
    }
}