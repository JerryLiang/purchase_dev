<?php
require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/31
 * Time: 10:49
 */
class Payment_order_pay extends MY_ApiBaseController
{
    /** @var Payment_order_pay_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Payment_order_pay_model');
        $this->_modelObj = $this->Payment_order_pay_model;
    }

    /**
     * 应付款单列表页---袁学文
     * @url /api/finance/payment_order_pay/get_list
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1045
     */
    public function get_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 刷新1688最新的应付账款时间，并更新状态
     * @author yefanli
     * @date 2020/07/08 15:10
     * @method GET
     * @return mixed
     * @throws Exception
     * @url  /api/finance/payment_order_pay/refresh_ali_payable
     */
    public function refresh_ali_payable()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->refresh_ali_payable($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 应付款单列表页 -- 导出
     * @author liwuxue
     * @date 2019/2/12 15:05
     * @param
     * @return mixed
     * @throws Exception
     */
    public function export_list()
    {
        set_time_limit(0);
        try {
            $this->_init_request_param("GET");
           $data= $this->_modelObj->export_list($this->_requestParams);
           $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage()."导出出现异常!");
        }
    }

    /**
     * 财务付款收款方下拉框联动---袁学文
     * @url /api/finance/payment_order_pay/payment_linkage
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1056
     */
    public function payment_linkage()
    {
        try {
           
           // $this->_init_request_param("GET");
            $data = $this->_modelObj->get_payment_linkage($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 财务付款我司付款账号（下拉框联动）---袁学文
     * @url /api/finance/payment_order_pay/payment_bank
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1057
     */
    public function payment_bank()
    {
        try {
         //   $this->_init_request_param("GET");
            $data = $this->_modelObj->get_payment_bank($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 合同单待财务审核页面显示---袁学文
     * @url /api/finance/payment_order_pay/get_contract_order_info
     * @author xulp
     * @date 2019/02/12 20:28
     * @method GET
     * @param
     * @doc http://192.168.71.156/web/#/84?page_id=1053
     */
    public function payment_contract_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_payment_contract_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 合同单待财务审核页面显示---袁学文
     * @url /api/finance/payment_order_pay/get_contract_order_info
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1046
     */
    public function get_contract_order_info()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_contract_order_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 财务审核操作（通过及驳回）---袁学文
     * @url /api/finance/payment_order_pay/contract_order_save
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method POST
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1049
     */
    public function contract_order_save()
    {
        try {
            $data = $this->_modelObj->contract_order_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 网菜单详情付款----袁学文
     * @url /api/finance/payment_order_pay/get_net_order_info
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1094
     */
    public function get_net_order_info()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_net_order_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 财务审核操作（通过及驳回）---袁学文
     * @url /api/finance/payment_order_pay/order_net_pay
     * @author harvin
     * @date 2019/5/9 
     * @method POST
     * @param
     */
    public function order_net_pay()
    {
        try {
            $data = $this->_modelObj->order_net_pay($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 应付款 - - 合同备注 - - 显示
     * @url /api/finance/payment_order_pay/show_note
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1320
     */
    public function show_note()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->show_note($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 应付款 - - 添加合同备注
     * @url /api/finance/payment_order_pay/contract_order_save
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method POST
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1321
     */
    public function add_note()
    {
        try {
            $data = $this->_modelObj->add_note($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
  /**
     * 保存财务付款(合同)
     * @author harvin 
     * http://www.caigouapi.com/api/finance/payment_order_pay/payment_contract_save
     * */
    public function payment_contract_save() {
        
        try {
            $data = $this->_modelObj->add_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }  
    }
    
      /**
    * 财务付款驳回（合同）
    *@author harvin 
    * @url api/finance/payment_order_pay/payment_contract_reject
    **/
  public function payment_contract_reject(){
      try {
            $this->_init_request_param("GET");
            $data=$this->_modelObj->payment_contract_reject($this->_requestParams);
             $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
  }
    /**
    * 网采单（线下支付显示）
    *@author harvin 
    * @url api/finance/payment_order_pay/net_offline_payment
    */
  public function net_offline_payment(){
       try {
            $this->_init_request_param("GET");
            $data=$this->_modelObj->get_net_offline_payment($this->_requestParams);
             $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }   
  }
  
   /**
     * 保存财务付款(合同)
     * @author harvin 
     * api/finance/payment_order_pay/net_offline_payment_save
     * */
    public function net_offline_payment_save() {
        
        try {
            $data = $this->_modelObj->get_net_offline_payment_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }  
    }


    /**
     * 模糊查询主体账号
     */
    public function get_account_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_account_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 付款申请单添加备注 wangliang
     *
     */
    public function order_pay_remark(){
        try {
            $data = $this->_modelObj->order_pay_remark($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取付款申请单备注 wangliang
     *
     */
    public function get_remark_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_remark_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
  
    /**
     * 添加备注及显示备注
     * @author harvin
     * @date 2019-06-28
     */
    public function get_remark_log_list(){
          try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->remark_log_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }       
    }
    
     /**
     * 添加请款单日志
     * @author harvin
     * @date 2019-06-28
     */
    public function add_remark_log(){
         try {
            $data = $this->_modelObj->add_remark_log($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    
}