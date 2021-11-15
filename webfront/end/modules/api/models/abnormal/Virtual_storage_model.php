<?php
/**
 * 异常列表模型类
 * User: Dean
 * Date: 2019/01/16 10:06
 */

class Virtual_storage_model extends Api_base_model {


    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
        //$this->load->helper(['user','abnormal']);
    }

    /**
     * 获取多货数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Dean 2019/01/16
     */
    public function get_storage_list($params)
    {

        $url = $this->_baseUrl . $this->_getStorageListUrl;
        return $this->request_http($params, $url);

    }













    /**
     * 批量审核入库单
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Dean 2019/01/17
     */
    public function batch_audit_storage_order($params)
    {
        $url = $this->_baseUrl . $this->_batchAuditStorageOrderUrl;
        //return $this->request_http($params, $url,'POST');
        return  $this->_curlReadHandleApi($url, $params, 'POST');

    }



    /**
     * 查看调拨信息
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Dean 2019/01/16
     */
    public function view_storage_detail($params)
    {
        $url = $this->_baseUrl . $this->_viewStorageDetailUrl;
        return $this->request_http($params, $url);

    }


    /**
     * 查看日志
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Dean 2019/01/16
     */
    public function view_log($params)
    {
        $url = $this->_baseUrl . $this->_viewLogUrl;
        return $this->request_http($params, $url);

    }





























}