<?php
/**
 * User: Justin
 * Date: 2020/8/3
 * Time: 17:03
 **/

class Product_line_buyer_config_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 列表显示
     * @param $params
     * @return mixed
     * @throws Exception
     * @author Justin
     * @date   2020/8/3
     */
    public function get_data_list($params)
    {
        $url = $this->_baseUrl . $this->_dataListUrl;
        return $this->request_http($params, $url, 'GET', FALSE);
    }

    /**
     * 编辑数据
     * @param $params
     * @return mixed
     * @throws Exception
     * @author Justin
     * @date   2020/8/3
     */
    public function batch_edit($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_dataEditUrl;
        return $this->_curlWriteHandleApi($url, $params, 'POST');
    }
}