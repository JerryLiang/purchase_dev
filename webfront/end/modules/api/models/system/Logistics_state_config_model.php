<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/11/29
 * Time: 22:11
 */

class Logistics_state_config_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 列表显示
     * @author Justin
     * @date 2019/11/30
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function get_data_list($params)
    {
        $url = $this->_baseUrl . $this->_ruleListUrl;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 批量编辑物流轨迹状态匹配规则
     * @author Justin
     * @date 2019/11/30
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function rule_batch_edit($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_ruleEditUrl;
        return $this->_curlWriteHandleApi($url, $params, 'POST');
    }

    /**
     * 编辑页面下拉列表数据
     * @param $params
     * @return array|mixed|null
     */
    public function get_drop_down_list($params){
        $url = $this->_baseUrl . $this->_dropDownListUrl;
        return $this->request_http($params, $url, 'GET', false);
    }
}