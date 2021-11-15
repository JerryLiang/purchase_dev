<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 发票清单控制器
 * User: Jaxton
 * Date: 2019/01/10 18:00
 */

class Invoice_list extends MY_ApiBaseController{
	public function __construct(){
        parent::__construct();
        $this->load->model('purchase/Invoice_list_model','invoice_list_model');
        $this->_modelObj = $this->invoice_list_model;
    }

    /**
    * 批量开票弹出“发票维护”界面数据
    * /purchase/invoice_list/btach_invoice_list
    * @author Jaxton 2019-1-11
    */
    public function btach_invoice_list(){
        $params = $this->_requestParams;
        
        $data = $this->_modelObj->get_invoice_detail_list($params);
        //print_r($data);die;
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);        
    }
    /**
    * /purchase/invoice_list/btach_invoice_submit
    * 批量开票提交
    * @author Jaxton 2019-1-11
    */
    public function btach_invoice_submit(){
        $params = $this->_requestParams;
        
        $data = $this->_modelObj->btach_invoice_submit($params);
        //print_r($data);die;
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);          	
    }

    /**
    * /purchase/invoice_list/invoice_finance_review
    * 财务审核
    * @author Jaxton 2019-1-12
    */
    public function invoice_finance_review(){
        $params = $this->_requestParams;
        
        $data = $this->_modelObj->invoice_finance_review($params);
        //print_r($data);die;
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);          
    }

    /**
    * /purchase/invoice_list/download_invoice_detail
    * 下载发票明细
    * @author Jaxton 2019-1-12
    */
    public function download_invoice_detail(){
        $params = $this->_requestParams;
        
        $data = $this->_modelObj->get_download_invoice_detail($params);
        //print_r($data);die;
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);          
        
    }

}