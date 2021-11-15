<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
//D:\appdal\end\modules\user\controllers\Merchandiser_user_group.phpp.php
class Merchandiser_user_group_model extends Api_base_model {

    public function __construct() {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取小组列表数据
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date 2020-06-19
     */
    public function get_group_list($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_group_listApi;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 修改采购组
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date 2020-06-19
     */
    public function group_edit($params)
    {
        //调用服务层api

        $url = $this->_baseUrl . $this->_group_editApi;




        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 删除小组
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date 2020-06-19
     */
    public function group_del($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_group_delApi;
        return $this->request_appdal($params, $url, 'POST');
    }


    /**
     * 获取人员列表数据
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date 2020-06-19
     */
    public function get_user_list($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_user_listApi;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 添加用户
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date 2020-06-19
     */
    public function user_add($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_user_addApi;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 输入user_id获取用户显示下拉框
     * @param $params
     * @return array|mixed
     * @throws Exception
     */
    public function get_user_info($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_user_infoApi;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 添加用户显示下拉框
     * @author harvin
     * @param $params
     * @date 2019-3-20
     */
    public function get_1688_account($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_get_1688_accountApi;
        return $this->request_http($params, $url, 'GET', false);
    }

    public function user_edit_view($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_user_edit_viewApi;
        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 保存用户编辑
     * @param $params
     * @return array|mixed|null
     */
    public function get_user_edit_save($params) {
        //调用服务层api
        $url = $this->_baseUrl . $this->_user_edit_saveApi;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 删除用户
     * @param $params
     * @return array|mixed|null
     */
    public function user_del($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_user_delApi;
        return $this->request_appdal($params, $url, 'POST');
    }

    /**
     * 交接人
     * @param $params
     * @return array|mixed|null
     */
    public function get_handover_person($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_handover_personApi;
        return $this->request_appdal($params, $url, 'POST');
    }

    public function get_company_dep($params){
        $url = $this->_baseUrl . $this->_get_company_dep."?uid=".$params['uid'];
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 修改采购组
     * @param $get
     * @author harvin
     * @date 2019-3-19
     */
    public function group_edit_batch($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_group_editApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 用户的启用禁用
     * @param $params
     * @return array|mixed|null
     */
    public function change_enable_status($params){
        //调用服务层api
        $url = $this->_baseUrl . $this->_change_enable_statusApi;
        return $this->request_appdal($params, $url, 'POST');
    }

    public function getGrupData($params){

        //调用服务层api
        $url = $this->_baseUrl . $this->_getGrupData;
        return $this->request_appdal($params, $url, 'POST');
    }

}
