<?php

/**
 * Created by PhpStorm.
 * User: Dean
 * Date: 2019/12/5
 * Time: 11:01
 */
class Purchase_menu_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }


    /**
     * 获取栏目页面
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function get_menu_list($params){
        $url = $this->_baseUrl . $this->_menuListUrl;

        return $this->request_http($params,$url);

    }


    /**
     * 获取栏目页面
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function opr_menu($params){
        $url = $this->_baseUrl . $this->_oprMenuUrl;
        return $this->request_http($params,$url);

    }

    public function get_menu_news_num($params){
        $url = $this->_baseUrl . $this->_MenuNewsNumUrl;
        return $this->request_http($params,$url);

    }

    public function del_menu($params){
        $url = $this->_baseUrl . $this->_delMenuUrl;
        return $this->request_http($params,$url);

    }


    /**
     * 保存目录排序
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function save_menu_sort($params)
    {
        $url = $this->_baseUrl . $this->_saveMenuSortUrl;
        //return $this->request_http($params, $url,'POST');
        return  $this->_curlReadHandleApi($url, $params, 'POST');

    }





}