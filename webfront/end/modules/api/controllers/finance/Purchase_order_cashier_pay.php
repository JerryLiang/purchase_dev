<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * 付款控制模块
 * User: jackson
 * Date: 2019/02/16
 */
class Purchase_order_cashier_pay extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Purchase_order_cashier_pay_model', 'cashierPay');
        $this->_modelObj = $this->cashierPay;
    }

    /**
     * @desc 1688在线付款(获取1688订单数据，访问1688收银台进行付款-每次只能批量支付同一个申请人的单)
     * @author Jackson
     * @Date 2019-02-16 17:01:00
     * @return array()
     **/
    public function online_payment()
    {

        //判断请求方式
        $method = !empty($_POST) ? 'POST' : 'GET';

        //获取请求参数
        if ($method == 'GET') {
            $this->_init_request_param('GET');
        }
        $params = $this->_requestParams;
        $data = $this->_modelObj->online_payment($params, $method);

        //分析返回数据
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        //json数据返回
        $this->sendData($data);
    }

    public function online_paymeny_update(){
        //判断请求方式
        $method = !empty($_POST) ? 'POST' : 'GET';

        //获取请求参数
        if ($method == 'GET') {
            $this->_init_request_param('GET');
        }
        $params = $this->_requestParams;
        $data = $this->_modelObj->online_paymeny_update($params, $method);

        //分析返回数据
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        //json数据返回
        $this->sendData($data);
    }

    /**
     * 重新获取1688订单状态
     * @author Jolon
     */
    public function refresh_ali_order_status(){
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data = $this->_modelObj->refresh_ali_order_status($params);

        //分析返回数据
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        //json数据返回
        $this->sendData($data);
    }



    /**
     * @desc 跨境宝批量付款
     * @author Jaden
     * @Date 2019-02-16 17:01:00
     * @return array()
     **/
    public function online_cross_border_payment()
    {

        //判断请求方式
        $method = !empty($_POST) ? 'POST' : 'GET';

        //获取请求参数
        if ($method == 'GET') {
            $this->_init_request_param('GET');
        }
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_online_cross_border_payment($params, $method);

        //分析返回数据
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        //json数据返回
        $this->sendData($data);
    }





    /**
     * @desc 1688超级卖家在线付款(获取1688订单数据，访问1688收银台进行付款:  可以同时支付所有子账号的单)
     * @author Jackson
     * @Date 2019-02-16 17:01:00
     * @return array()
     **/
    public function super_online_payment()
    {
        //判断请求方式
        $method = !empty($_POST) ? 'POST' : 'GET';

        //获取请求参数
        if ($method == 'GET') {
            $this->_init_request_param('GET');
        }
        $params = $this->_requestParams;
        $data = $this->_modelObj->online_payment($params, $method);

        //分析返回数据
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        //json数据返回
        $this->sendData($data);

    }

    /**
     * @title 1688在线付款后的确认付款操作
     * @author jackosn
     * @date 2019-12-16 16:00
     */
    public function affirm_payment()
    {
        //获取参数
        $params = $this->_requestParams;

        //接收返回数据
        list($status, $msg) = $this->_modelObj->affirm_payment($params);
        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        //输出最终结果
        $this->sendData();
    }

    /**
     * @title 驳回请款单
     * @author jackosn
     * @date 2019-02-18 16:00
     */
    public function cashier_reject()
    {
        //获取参数
        $params = $this->_requestParams;

        //接收返回数据
        list($status, $msg) = $this->_modelObj->cashier_reject($params);
        $this->_code = $status ? 0 : $this->getServerErrorCode();
        $this->_msg = $msg;

        //输出最终结果
        $this->sendData();
    }

    /**
     * @desc 富友支付提交请求
     * @author jackosn
     * @date 2019-02-20 16:00
     */
    public function ufxfuiou_pay()
    {
        //判断请求方式
        $method = !empty($_POST) ? 'POST' : 'GET';

        //获取请求参数
        if ($method == 'GET') {
            $this->_init_request_param('GET');
        }
        $params = $this->_requestParams;
        $data = $this->_modelObj->ufxfuiou_pay($params, $method);

        //分析返回数据
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        //json数据返回
        $this->sendData($data);
    }

    /**
     * @desc 富友支付(手动获取支付状态信息)
     * @author jackosn
     * @date 2019-02-20 16:00
     */
    public function get_fuiou_pay_info()
    {
        //判断请求方式
        $method = !empty($_POST) ? 'POST' : 'GET';

        //获取请求参数
        if ($method == 'GET') {
            $this->_init_request_param('GET');
        }
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_fuiou_pay_info($params, $method);

        //分析返回数据
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        //json数据返回
        $this->sendData($data);
    }

}