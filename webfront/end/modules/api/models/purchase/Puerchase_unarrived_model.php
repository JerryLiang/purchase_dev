<?php

/**
 * [function desc]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Puerchase_unarrived_model extends Api_base_model {

    const MODULE_NAME = 'CAIGOU_SYS'; // 合同模块

    public function __construct() {
        parent::__construct();
        $this->init();
        $this->setContentType('');
        $this->load->helper('export_csv');
        $this->load->helper('status_supplier');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');
    }

    /**
     * 取消未到货导出
     * @author:luxu
     **/
    function cancel_unarrived_goods_examine_down($params){

        $url = $this->_baseUrl . $this->_cancel_unarrived_goods_examine_down;
        $result = $this->httrequest($params, $url);
        return $result;
    }
   /**
     * 查询取消未到货的数据
     * @param srting $id 参数id
     * @author harvin 2019-1-8
     * * */
    public function get_cancel_unarrived_goods($params) {
         $url = $this->_baseUrl . $this->_unarrivedUrl;
         $result = $this->httrequest($params, $url);
         return $result;
    }
    /**
     * 保存数据
     * @author harvin 2019-1-11
     * @param string $purchase_number 采购单号
     * @param int  $purchase_type_id 采购类型 
     * @param decimal $freight 取消的运费
     * @param decimal $discount  取消的优惠额
     * @param decimal $total_price 取消的总金额
     * @param sring $create_note 备注 
     * @param array $cancel_ctq  取消的数量
     * @param srting $id 采购明细表ID
     * @author harvin 2019-1-11
     * * */
    public function get_cancel_unarrived_goods_save($params){
         $url = $this->_baseUrl . $this->_savelistUrl;
         $result = $this->httrequest($params, $url, "POST");
         return $result;
    }

    protected $_cancelAfloatGoodsListApi = "";
    protected $_cancelAfloatGoodsApi = "";
    protected $_changePurchaserListApi = "";
    protected $_changePurchaserApi = "";

    /**
     * 取消未到货的订单审核操作显示--袁学文
     * @author liwuxue
     * @date 2019/1/31 9:42
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function cancel_unarrived_goods_examine_list($params)
    {
         $url = $this->_baseUrl . $this->_cancelAfloatGoodsListApi;
         $result = $this->httrequest($params, $url);
         return $result;
    }
   /**
     * 取消未到货的再编辑--袁学文
     * @author liwuxue
     * @date 2019/1/31 9:42
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function cancel_unarrived_goods_edit($params)
    {
         $url = $this->_baseUrl . $this->_cancelAfloatGoodseditListApi;
         $result = $this->httrequest($params, $url);
         return $result;
    }
    
    /**
     * 取消未到货审核通过及审核驳回操作--袁学文
     * @author liwuxue
     * @date 2019/1/31 9:42
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function cancel_unarrived_goods_examine($post)
    {
        $url = $this->_baseUrl . $this->_cancelAfloatGoodsApi;
        $res = $this->_curlWriteHandleApi($url, $post, "POST");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }
    /**
     * 取消未到货列表
     * @author harvin 2019-3-9
     * @param $get 
     * **/
   public function get_cencel_lits($get){
        $url = $this->_baseUrl . $this->_cancelApi . "?" . http_build_query($get);
        $res = $this->_curlWriteHandleApi($url, "", "GET");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        $res['page_data']=$res['data_list']['page_data'];
        return $res;
   }
   /**
    * 取消未到货显示详情
    * @author harvin 2019-3-9
    * @param $get 
    */
   public function get_cancel_unarrived_info($get){
        $url = $this->_baseUrl . $this->_unarrived_infoApi . "?" . http_build_query($get);
        $res = $this->_curlWriteHandleApi($url, "", "GET");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        $res['page_data']=$res['data_list']['page_data'];
        return $res;
       
       
       
       
   }

   /**
     * 取消未到货 上传截图
     * @param harvin  2019-3-14
     * @param $post 
     */
    public function get_cancel_upload_screenshots($post){
          $url = $this->_baseUrl . $this->screenshotApi;
          $res = $this->_curlWriteHandleApi($url, $post, "POST");
          $res['status'] = 1;
          $res['errorMess'] = $this->_errorMsg;
          return $res;
    }
     /**
     * 获取取消未到货日志
     * @author liwuxue
     * @date 2019/6/26 9:42
     * @param $get
     * @return mixed
     * @throws Exception
     */
    public function get_cancel_log_info($get){
        $url = $this->_baseUrl . $this->_changePurchaserinfoListApi . "?" . http_build_query($get);
        $res = $this->_curlWriteHandleApi($url, "", "GET");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res; 
    }
    /**
     * 显示变更采购员操作经理权限显示--袁学文
     * @author liwuxue
     * @date 2019/1/31 9:42
     * @param $get
     * @return mixed
     * @throws Exception
     */
    public function change_purchaser_list($get)
    {
        $url = $this->_baseUrl . $this->_changePurchaserListApi . "?" . http_build_query($get);
        $res = $this->_curlWriteHandleApi($url, "", "GET");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 变更采购员操作经理权限保存--袁学文--袁学文
     * @author liwuxue
     * @date 2019/1/31 9:42
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function change_purchaser($post)
    {
        $url = $this->_baseUrl . $this->_changePurchaserApi;
        $res = $this->_curlWriteHandleApi($url, $post, "POST");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 根据收款信息拉取1688退款信息
     * @author harvin 2019-3-9
     * @param $get
     * **/
    public function refresh_ali_refund($get){
        $url = $this->_baseUrl . $this->_refreshAliRefundApi . "?" . http_build_query($get);
        $res = $this->_curlWriteHandleApi($url, "", "GET");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 取消未到货 上传截图预览
     * @author jeff 2019-0925
     * @param $get
     * **/
    public function cancel_upload_screenshots_preview($get){
        $url = $this->_baseUrl . $this->_cancelUploadScreenshotsPreviewApi . "?" . http_build_query($get);
        $res = $this->_curlWriteHandleApi($url, "", "GET");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 取消未到货 上传截图
     * @param jeff  2019-0925
     * @param $post
     */
    public function get_cancel_upload_screenshots_v2($post){
        $url = $this->_baseUrl . $this->screenshotv2Api;
        $res = $this->_curlWriteHandleApi($url, $post, "POST");
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 取消未到货列表计算信息
     * @author harvin 2019-3-9
     * @param $get
     * **/
    public function get_cancel_lists_sum($get){
        $url = $this->_baseUrl.$this->_cancelListsSumApi."?".http_build_query($get);
        try{
            $res = $this->_curlWriteHandleApi($url, '', "GET");
            $res['page_data'] = $res['data_list']['page_data'];
            return $res;
        }catch (Exception $e){
            return ["status" => 1, "errorMess" => $e->getMessage()];
        }
    }

    /**
     * 取消未到货批量上传图片
     */
    public function get_cancel_upload_data($get)
    {
        $url = $this->_baseUrl.$this->_getCancelUploadData."?".http_build_query($get);
        try{
            return $this->_curlWriteHandleApi($url, '', "GET");
        }catch (Exception $e){
            return ["status" => 1, "errorMess" => $e->getMessage().'系统错误！'];
        }
    }

    /**
     * 取消未到货批量上传图片 保存数据
     */
    public function save_cancel_upload_data($get)
    {
        $url = $this->_baseUrl.$this->_saveCancelUploadData;
        try{
            return $this->_curlWriteHandleApi($url, $get, "post");
        }catch (Exception $e){
            return ["status" => 1, "errorMess" => $e->getMessage().'系统错误！'];
        }
    }
 
}
