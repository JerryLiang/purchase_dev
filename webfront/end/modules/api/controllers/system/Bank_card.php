<?php

require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 14:21
 */
class Bank_card extends MY_ApiBaseController
{
    /** @var Bank_card_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Bank_card_model');
        $this->_modelObj = $this->Bank_card_model;
    }

    /**
     * 获取银行卡账号简称列表
     * @author Jolon
     */
    public function get_account_short_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_account_short_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取 指定的一个 银行卡信息
     * @author Jolon
     */
    public function get_card_one(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_card_one($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查询 银行卡列表
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=974
     */
    public function get_card_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_card_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 创建 SKU屏蔽 记录
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method post
     * @doc
     */
    public function bank_card_create()
    {
        try {
            $data = $this->_modelObj->bank_card_create($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}