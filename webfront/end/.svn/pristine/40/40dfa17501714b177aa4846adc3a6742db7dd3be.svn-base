<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/31
 * Time: 18:08
 */

class Journal_log_model extends Api_base_model
{

    //api conf  /modules/api/conf/caigou_sys_payment_order_pay.php
    protected $_logListApi = "";
    protected $_operateLogListApi = "";

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 驳回信息表操作日志接口
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_log_list($get)
    {
        $url = $this->_baseUrl . $this->_logListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 系统操作日志接口
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_operator_log($get)
    {
        $url = $this->_baseUrl . $this->_operateLogListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

}