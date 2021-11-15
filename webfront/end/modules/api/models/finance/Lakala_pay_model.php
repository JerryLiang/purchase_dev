<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-07-09
 * Time: 9:32
 */

class Lakala_pay_model extends Api_base_model{

    public function __construct() {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * '_baseUrl' => '/finance/Lakala_pay',
    '_get_list' => '/LaKaLa_list', //数据列表
    '_submission' => '/lakala_submission', //提交
    '_audit_list' => '/audit_list', //批量审核显示
     *
     */

    /**
     *  应付提交支付接口
     */
    public function lakala_submission($post){
        $url = $this->_baseUrl . $this->_submission;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

    /**
     * 界面列表
     */
    public function LaKaLa_list($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_get_list . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     *
     * 审核时显示的列表
     *
     */
    public function audit_list($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_audit_list . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 拉卡拉驳回接口
     */
    public function lakala_reject($post){
        $url = $this->_baseUrl . $this->_reject;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

    /**
     * 操作审核后将数据注册到拉卡拉
     * Totoro
     * 2020-07-09
     * 拉卡拉:批量代付注册接口
     * 易佰：拉卡拉界面审核
     * batchPay/registry
     */
    public function batchPay_registry($post){
        $url = $this->_baseUrl . $this->_registry;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

    /**
     * 拉卡拉导出
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function lakala_export($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_lakala_export . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 刷新交易状态
     * @param $post
     * @return array|mixed
     * @throws Exception
     */
    public function refresh_pay_status($post){
        //调用服务层api
        $url = $this->_baseUrl . $this->_refresh_pay_status;
        return $this->_curlReadHandleApi($url, $post, 'POST');
    }
}