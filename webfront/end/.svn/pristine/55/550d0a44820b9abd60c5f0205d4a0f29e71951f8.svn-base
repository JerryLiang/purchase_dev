<?php

/**
 * Class System_purchase_model
 */
class Purchase_setting_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取公共仓修改限制列表
     */
    public function pertain_set_list($params)
    {
        $url = $this->_baseUrl . $this->_PertainSetList;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 编辑/新增公共仓修改限制
     */
    public function pertain_set_edit($params)
    {
        $url = $this->_baseUrl . $this->_PertainSetEdit;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 获取自动取消列表
     */
    public function cancel_auto_list($params)
    {
        $url = $this->_baseUrl . $this->_CancelSetList;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 编辑/新增自动取消
     */
    public function cancel_auto_edit($params)
    {
        $url = $this->_baseUrl . $this->_CancelSetEdit;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 应付款时间配置列表
     */
    public function need_pay_time_list($params)
    {
        $url = $this->_baseUrl . $this->_need_pay_time_list;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 编辑/新增应付款时间配置
     */
    public function need_pay_time_edit($params)
    {
        $url = $this->_baseUrl . $this->_need_pay_time_edit;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 自动对账配置
     */
    public function record_auto_list($params)
    {
        $url = $this->_baseUrl . $this->_record_auto_list;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 编辑/新增自动对账配置
     */
    public function record_auto_edit($params)
    {
        $url = $this->_baseUrl . $this->_record_auto_edit;
        return $this->httrequest($params, $url, "POST");
    }

}