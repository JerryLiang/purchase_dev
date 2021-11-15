<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/31
 * Time: 10:51
 */
class Payment_order_pay_model extends Api_base_model
{

    //api conf  /modules/api/conf/caigou_sys_payment_order_pay.php
    protected $_getListApi = "";
    protected $_getPaymentLinkageApi = "";
    protected $_getPaymentBankApi = "";
    protected $_getContractOrderInfoApi = "";
    protected $_contractOrderSaveApi = "";
    protected $_getNetOrderInfoApi = "";
    protected $_showNoteApi = "";
    protected $_addNoteApi = "";

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
        $this->load->helper('export_csv');
    }

    /**
     * 应付款单列表页
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_list($get)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_getListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 刷新1688最新的应付账款时间，并更新状态
     * @author yefanli
     * @date 2020/07/08 15:10
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function refresh_ali_payable($get)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_refreshAliPayable . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 导出列表
     * @author liwuxue
     * @date 2019/2/12 15:06
     * @param $get
     * @throws Exception
     */
    public function export_list($get)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_export_listApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 财务付款收款方下拉框联动
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_payment_linkage($get)
    {
        $url = $this->_baseUrl . $this->_getPaymentLinkageApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 财务付款我司付款账号
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_payment_bank($get)
    {
        $url = $this->_baseUrl . $this->_getPaymentBankApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    /**
     * 财务付款列表显示
     * @author xulp
     * @date 2019/02/12 20:29
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_payment_contract_list($get){
        $url = $this->_baseUrl . $this->_getPaymentContractListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
    /**
     * 合同单待财务审核页面显示---袁学文
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_contract_order_info($get)
    {
        $url = $this->_baseUrl . $this->_getContractOrderInfoApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 财务审核操作---袁学文
     * @author liwuxue
     * @date 2019/1/31 15:52
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function contract_order_save($post)
    {
        $url = $this->_baseUrl . $this->_contractOrderSaveApi;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

    /**
     * 网菜单详情付款----袁学文
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_net_order_info($get)
    {
        $url = $this->_baseUrl . $this->_getNetOrderInfoApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 应付款 - - 添加合同备注
     * @author liwuxue
     * @date 2019/1/31 15:52
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function add_note($post)
    {
        $url = $this->_baseUrl . $this->_addNoteApi;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

    /**
     * 应付款 - - 合同备注 - - 显示
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function show_note($get)
    {
        $url = $this->_baseUrl . $this->_showNoteApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }
     /**
     * 保存财务付款(合同)
     * @author yuanxuewen 
     * @date 2019/2/14 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function add_save($post){
        $url = $this->_baseUrl . $this->_addsaveNoteApi;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;   
    }
     /**
     * 网采单线下付款
     * @author harvin 
     * @date 2019-2-9
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function order_net_pay($post){
         $url = $this->_baseUrl . $this->_orderNetPayApi;
         $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
         $rs['status'] = 1;
         $rs['errorMess'] = $this->_errorMsg;
         return $rs;
    }
    /**
     *財務駁回
     * @author harvin 
     * @param $get
     **/
   public function payment_contract_reject($get){
        $url = $this->_baseUrl . $this->_contract_rejectApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", "GET");
   }
   /**
    * 网采单线下支付显示
    * @author harvin
    * @date 2019-4-9
    * @return array
    * @param $get
    */
   public function get_net_offline_payment($get){
        $url = $this->_baseUrl . $this->_offline_paymentApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", "GET");
   }
   /**
    * 网采单线下支付保存
    * @author harvin 2019-4-9
    * @date 2019-4-9
    * @param type $post
    * @return array
    */
   public function get_net_offline_payment_save($post){
         $url = $this->_baseUrl . $this->payment_saveApi;
        $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;      
   }

    /** 获取主账号列表
     * @author wangliang
     * @date 2019-6-5
     * @param $get
     * @return array|mixed
     */
   public function get_account_list($get){
       $url = $this->_baseUrl . $this->_accountListApi . "?" . http_build_query($get);
       return $this->_curlReadHandleApi($url, "", 'GET');
   }


    /** 应付款申请添加备注 wangliang
     * @return array|mixed
     */
   public function order_pay_remark($post){
       $url = $this->_baseUrl . $this->_pay_remarkApi;
       $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
       $rs['status'] = 1;
       $rs['errorMess'] = $this->_errorMsg;
       return $rs;
   }

    /** 获取付款申请单备注 wangliang
     * @param $get
     * @return array|mixed
     */
   public function get_remark_list($get){
       $url = $this->_baseUrl . $this->_get_remark_listApi. "?" . http_build_query($get);
       return $this->_curlReadHandleApi($url, "", 'GET');
   }
    /**
     * 添加备注及显示备注
     * @author harvin
     * @param  $get
     * @date 2019-06-28
     */
   public function remark_log_list($get){
       $url = $this->_baseUrl . $this->_get_remark_logtApi. "?" . http_build_query($get);
       return $this->_curlReadHandleApi($url, "", 'GET');
   }
   
     /**
     * 添加请款单日志
     * @author harvin
      * @param  $post
     * @date 2019-06-28
     */
   public function add_remark_log($post){
       $url = $this->_baseUrl . $this->_get_remark_addApi;
       $rs = $this->_curlWriteHandleApi($url, $post, 'POST');
       $rs['status'] = 1;
       $rs['errorMess'] = $this->_errorMsg;
       return $rs;
   }
}