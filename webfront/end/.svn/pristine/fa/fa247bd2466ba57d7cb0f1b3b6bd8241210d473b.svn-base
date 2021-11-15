<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Purchase_user_model extends Api_base_model {

    public function __construct() {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     * 采购系统用户列表
     * @param $params
     * @return array|mixed|null
     * @author Justin
     * @date 2020-06-19
     */
    public function user_all_drop_down_box($params)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_userListApi;
        return $this->request_http($params, $url, 'GET', false);
    }

}
