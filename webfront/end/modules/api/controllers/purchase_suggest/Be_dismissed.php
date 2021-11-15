<?php

require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 10:22
 */
class Be_dismissed extends MY_ApiBaseController
{
    /** @var Be_dismissed_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_suggest/Be_dismissed_model');
        $this->_modelObj = $this->Be_dismissed_model;
    }

    /**
     * 驳回下拉框原因----袁学文
     * @url /api/purchase_suggest/be_dismissed/get_dismissed_reason
     * @author liwuxue
     * @date 2019/1/29 14:19
     * @method GET
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=707
     */
    public function get_dismissed_reason()
    {
        try {
            $data = $this->_modelObj->get_dismissed_reason($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 驳回操作接口----袁学文
     * @url /api/purchase_suggest/be_dismissed/get_dismissed_operation
     * @author liwuxue
     * @date 2019/1/29 14:19
     * @method POST
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=708
     */
    public function get_dismissed_operation()
    {
        try {
            $data = $this->_modelObj->get_dismissed_operation($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

}