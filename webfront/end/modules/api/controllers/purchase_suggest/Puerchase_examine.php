<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 9:10
 */
class Puerchase_examine extends MY_ApiBaseController
{
    /** @var Puerchase_examine_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_suggest/Puerchase_examine_model');
        $this->_modelObj = $this->Puerchase_examine_model;
    }

    /**
     * 需求审核不通过-----袁学文
     * @url /api/purchase_suggest/puerchase_examine/audit_failed
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method POST
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=716
     */
    public function audit_failed()
    {
        try {
            $data = $this->_modelObj->audit_failed($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 审核通过接口-----袁学文
     * @url /api/purchase_suggest/puerchase_examine/audit_pass
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method POST
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=717
     */
    public function audit_pass()
    {
        try {
            $data = $this->_modelObj->audit_pass($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}