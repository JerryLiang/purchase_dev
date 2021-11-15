<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Reason_config_model extends Api_base_model
{   
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

     /**
     * 列表页
     * @author harvin
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function reason_type_list($get){
         //调用服务层api
        $url = $this->_baseUrl . $this->_reasonTypeListApi . "?" . http_build_query($get);
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
            'page_data' => isset($result['page_data']) ? $result['page_data'] : [],
        ];
    }

    /**
     * 列表页
     * @author harvin
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_cancel_reason_list($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_cancelReasonListApi . "?" . http_build_query($get);
        $result = $this->httpRequest($url, "", 'GET');
        return [
            'data_list' => isset($result['data_list']) ? $result['data_list'] : [],
        ];
    }
 
    /**
     * 修改原因
     * @author jeff
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function reason_edit($post){
          //调用服务层api
        $url = $this->_baseUrl . $this->_reasonEditApi;
        return $this->_curlWriteHandleApi($url, $post, 'POST');
        
    }

    /**
     * 修改原因
     * @author jeff
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function reason_status_change($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_reasonStatusChangeApi;
        return $this->_curlWriteHandleApi($url, $post, 'POST');

    }

    /**
     * 添加原因
     * @author jeff
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function reason_add($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_reasonAddApi;
        return $this->_curlWriteHandleApi($url, $post, 'POST');

    }

    /**
     * 原因排序
     * @author jeff
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function reason_sort($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_reasonSortApi;
        return $this->_curlWriteHandleApi($url, $post, 'POST');

    }

    /**
     * 获取作废原因下拉框
     * @author jeff
     *
     */
    public function get_cancel_reasons($params = array())
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_cancelReasonListApi . "?" . http_build_query($params);
        $result = $this->httpRequest($url, "", 'GET');

        $return_data = [];
        if (isset($result['data_list']['values'])){
            foreach ($result['data_list']['values'] as $value){
                $return_data[] = ['reason_name'=>$value['reason_name'],'id'=>$value['id']];
            }
        }
        return [
            'data_list' => $return_data,
        ];
    }

    /**
     * 编辑原因统一提交(包括新增)
     * @author jeff
     * @date 2019/6/27 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function reason_edit_submit($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_reasonEditSubmitApi;
        return $this->_curlWriteHandleApi($url, $post, 'POST');

    }
}