<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/26
 * Time: 15:12
 */

class Payment_order_report_model extends Api_base_model{

    protected $_listUrl = "";

    //api conf  /modules/api/conf/caigou_sys_payment_order_pay.php
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
        $this->load->helper('export_csv');
    }

    /**
     * 获取列表数据
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function get_pay_order_report($get){
        $url = $this->_baseUrl . $this->_listUrl . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 导出
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function export_report_list($get){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_export_listApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }


    /**
     * 添加、修改 财务付款统计表备注
     */
    public function add_finance_report_remark($params){
        $url = $this->_baseUrl . $this->_addFinanceReportRemarkApi;
        return $this->_curlReadHandleApi($url, $params, 'POST');
    }
}