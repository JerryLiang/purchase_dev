<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */

class System_news extends MX_Controller {

    private $_modelObj;
    // 请求参数
    protected $_requestParams;

    const DEFAULT_CODE = 0; // 默认返回的错误码
    const STATUS = 1; // 默认返回状态码
    const SERVER_ERROR_CODE = 501; // 上游发生错误时的错误码
    const DEFAULT_MSG  = 'OK'; // 默认消息

    protected $_code; // 返回的错误码
    protected $_msg;  // 返回的错误信息

    // 不允许传递的参数
    protected $_rejectedFields = array(
        'is_delete', 'create_user'
    );


    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_news/System_news_model');
        $this->_modelObj = $this->System_news_model;
    }


    /**
     * 接收消息列表
     * @author Dean 2020/11/07
     */
    public function news_list()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->news_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    public function receive_news()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->receive_news($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }





    /**
     * 点赞
     * @author Jaxton 2019/01/17
     */
    public function get_user_no_read_nums()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_user_no_read_nums($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 返回 上游服务错误时的错误码
     * @return mixed
     */
    protected function getServerErrorCode()
    {
        return self::SERVER_ERROR_CODE;
    }


    /**
     * 初始化接口参数，构造方法里只支持post，不支持get
     * @author liwuxue
     * @date 2019/1/26 12:01
     * @param string $method    POST / GET
     *  参数值 = get_request_params() 支持的参数值
     *
     */
    protected function _init_request_param($method = "POST")
    {
        $this->_requestParams = null;
        $params = get_request_params($method);
        // 过滤非法字段
        foreach ($this->_rejectedFields as $field)
        {
            unset($params[$field]);
        }
        $this->_requestParams = $params;
    }


    /**
     * 发送数据给前端
     * $data 业务数据
     *
     * @return_param $code int 错误码 0 OK，其它 错误 【10x 权限验证错误 20x 参数传递错误 50x服务器错误】
     * @return_param $msg string 错误消息
     * @return_param $data null 业务数据
     */
    protected function sendData($data = null)
    {
        $returnData = array(
            'status' => $this->_code == 0 ? 1 : 0,
            'errorMess'  => $this->_msg
        );

        if (is_array($data))
        {
            $returnData = array_merge($returnData, $data);
        }
        http_response($returnData);
    }



    /**
     * 标记消息为已读
     * @author Jolon 2021/11/05
     */
    public function set_news_read()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->set_news_read($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }













}