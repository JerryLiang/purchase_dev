<?php

/**
 * Class System_purchase_model
 */
class System_purchase_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取列表
     */
    public function get_list($params)
    {
        $url = $this->_baseUrl . $this->_SetPurchaseList;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 编辑/新增
     */
    public function save_edit_setting($params)
    {
        $url = $this->_baseUrl . $this->_SetPurchaseEdit;
        return $this->httrequest($params, $url, "POST");
    }

    /**
     * 启用禁用
     */
    public function on_off_setting($params)
    {
        $url = $this->_baseUrl . $this->_SetPurchaseOnOff;
        return $this->httrequest($params, $url, "POST");
    }

}