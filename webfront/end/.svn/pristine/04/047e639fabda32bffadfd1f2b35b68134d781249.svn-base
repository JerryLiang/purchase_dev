<?php
/**
 * Created by PhpStorm.
 * 同款货源控制器
 * User: Jolon
 * Date: 2021/11/09 0027 11:17
 */
class Product_similar_model extends Api_base_model {

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }


    public function get_similar_config($params){
        $url = $this->_baseUrl . $this->_getSimilarConfigUrl . "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    public function get_similar_list($params){
        $url = $this->_baseUrl . $this->_getSimilarListUrl . "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    public function get_similar_detail($params){
        $url = $this->_baseUrl . $this->_getSimilarDetailUrl . "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    public function similar_export($params){
        $url = $this->_baseUrl . $this->_similarExportUrl . "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    public function get_similar_logs($params){
        $url = $this->_baseUrl . $this->_getSimilarLogsUrl . "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    public function get_similar_history_detail($params){
        $url = $this->_baseUrl . $this->_getSimilarListoryDetailUrl . "?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }


    public function save_similar_config($params){
        $url = $this->_baseUrl . $this->_saveSimilarConfigUrl;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }

    public function delete_similar($params){
        $url = $this->_baseUrl . $this->_delete_similarUrl;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }

    public function allot_user_similar($params){
        $url = $this->_baseUrl . $this->_allotUserSimilarUrl;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }

    public function audit_similar($params){
        $url = $this->_baseUrl . $this->_auditSimilarUrl;
        $resp = $this->_curlWriteHandleApi($url, $params, 'POST');
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }



}