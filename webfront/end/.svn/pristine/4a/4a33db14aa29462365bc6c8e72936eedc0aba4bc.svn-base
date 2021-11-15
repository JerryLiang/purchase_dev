<?php

require APPPATH . 'core/MY_ApiBaseController.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */

class Purchase_auto_payout extends MY_ApiBaseController
{

    /** @var Purchase_auto_payout_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Purchase_auto_payout_model');
        $this->_modelObj = $this->Purchase_auto_payout_model;
    }
   
    /**
     * 网采单-创建请款单-自动请款
     * @author Jolon
     * @desc array $_POST['purchase_numbers'] 要请款采购单号
     */
    public function auto_payout()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->auto_payout($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }

}
