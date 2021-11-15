<?php


class Work_desk_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType();
    }


    /**
     * 工作台-数据展示接口
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date   2020/07/06
     */
    public function get_data_list($params)
    {
        $url = $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params, $url, 'GET', FALSE);
    }

    /**
     * 获取小组类型
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date   2020/07/06
     */
    public function get_category_list($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getCategoryUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 根据小组类型获取小组
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date   2020/07/06
     */
    public function get_group_by_category($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getGroupUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 根据小组id获取采购员
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date   2020/07/06
     */
    public function get_buyer_by_group($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getBuyerUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 根据小组id获取采购员
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date   2020/07/11
     */
    public function refresh_data($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_refreshDataUrl;
        return $this->request_appdal($params, $url, 'POST');
    }
}