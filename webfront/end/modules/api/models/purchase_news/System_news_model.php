<?php

/**
 * Created by PhpStorm.
 * User: Dean
 * Date: 2019/12/5
 * Time: 11:01
 */
class System_news_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }







    public function news_list($params)
    {
        $url = $this->_baseUrl . $this->_newsListUrl;
        return $this->request_http($params, $url);

    }


    public function receive_news($params)
    {
        $url = $this->_baseUrl . $this->_receiveNewsUrl;
        return $this->request_http($params, $url);

    }



    public function get_user_no_read_nums($params)
    {
        $url = $this->_baseUrl . $this->_getUserNoReadNumsUrl;
        return $this->request_http($params, $url);

    }



    public function set_news_read($params)
    {
        $url = $this->_baseUrl . $this->_setNewsRead;
        return $this->request_http($params, $url);

    }





}