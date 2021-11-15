<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/17
 * Time: 16:35
 */

class Purchase_inventory_items_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType();
    }

    /**
     * 核销-入库明细列表
     * @param $params
     * @return mixed
     * @throws Exception
     * @author Justin
     * @date 2020/04/17
     */
    public function get_data_list($params)
    {
        $url = $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 核销-入库明细添加备注
     * @param $params
     * @return mixed
     * @throws Exception
     * @author Justin
     * @date 2020/04/17
     */
    public function add_remark($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_addRemarkUrl;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 核销-入库明细列表导出
     * @param $params
     * @return array|mixed
     */
    public function data_list_export($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_exportUrl;
        return $this->request_appdal($params, $url, 'POST');
    }
}