<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */


class Purchase_order extends MY_ApiBaseController {

    /** @var Purchase_order_model */
    private $_modelObj;

    public function __construct() {      
        parent::__construct();
        $this->load->model('purchase/Purchase_order_model');
        $this->_modelObj = $this->Purchase_order_model;
        $this->config->load('url_img', FALSE, TRUE);
    }

    /**
     * 采购单列表搜索部分下拉框
     * @author harvin
     * http://www.caigouapi.com/api/purchase/purchase_order/get_status_lists
     */
    public function get_status_lists() {

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_status_lists($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
       
        $this->sendData($data);
    }

    /**
     * 获取作废原因下拉框
     * @author jeff
     * http://www.caigouapi.com/api/purchase/purchase_order/get_status_lists
     */
    public function get_cancel_reasons() {

        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_cancel_reasons($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 根据查询条件获取采购单列表
     * http://www.caigouapi.com/api/purchase/purchase_order/get_order_list
     * @author harvin 2019-1-8
     */
    public function get_order_list() {
        $params = $this->_requestParams;

        if(isset($params['demand_number']) and count($params['demand_number']) > 3000){
            $data=['status'=>0,'errorMess'=>'请不要超过3000个备货单'];
            $this->_msg = '请不要超过3000个备货单';
            $this->sendData($data);
        }

        $data = $this->_modelObj->get_list($params);
        if (empty($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 查询get_buyer_name列表
     * http://www.caigouapi.com/api/purchase/purchase_order/get_buyer_name
     * @author harvin 2019-1-8
     */
    public function get_buyer_name() {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_buyer_name($params);
        if (empty($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData(['buyer_list' => $data]);
    }

    public function get_sum() {
        $params = $this->_requestParams;

        if(isset($params['demand_number']) and count($params['demand_number']) > 3000){
            $data=['status'=>0,'errorMess'=>'请不要超过3000个备货单'];
            $this->_msg = '请不要超过3000个备货单';
            $this->sendData($data);
        }

        $data = $this->_modelObj->get_list_sum($params);
        if (empty($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }


    /**
     * 获取采购单页面汇总数据接口
     * http://www.caigouapi.com/api/purchase/purchase_order/get_order_sum
     * @author django 2019-7-8
     **/
    public function get_order_sum() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->get_order_sum($params);
    }
     /**
     * 导出
     * @author harvin 
     * http://www.caigouapi.com/api/purchase/purchase_order/export
     * **/ 
   public function export(){
       try {
           $this->_init_request_param("POST");
           $params = $this->_requestParams;

           if(isset($params['demand_number']) and count($params['demand_number']) > 3000){
               $data=['status'=>0,'errorMess'=>'请不要超过3000个备货单'];
               $this->_msg = '请不要超过3000个备货单';
               $this->sendData($data);
           }

           if (empty($params)) {
               $data = ['status' => 0, 'errorMess' => '参数请求错误'];
               $this->sendData($data);
           }
           $params['is_csv'] =1;
           $data = $this->_modelObj->get_list_export($params);
           $this->sendData($data);
       }catch ( Exception $exp ) {

           $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));
       }
   }

   public function get_purchase_progress() {

       $params = $_GET;
       $data = $this->_modelObj->get_purchase_progress($params);
       if (empty($data)) {
           $this->_code = $this->getServerErrorCode();
           $this->_msg = $this->_modelObj->getErrorMsg();
       }
       $data['data_list']['keys'] = ['序号','图片','备货单号','在途异常','订单状态',
           '备货单状态','供应商','采购单号','SKU','产品名称','采购数量','入库数量','采购在途',
       '未到货数量','跟进进度','异常类型','产品状态','采购员','跟单员','审核时间','预计到货日期',
           '到货日期',' 入库日期','入库时效（h）','逾期天数','物流公司','快递单号','发货批次号','拍单号','采购仓库','跟进日期','备注说明','请款时间',
           '付款时间','请款时效(h)','采购来源','产品线','近7天销量','缺货数量','1688异常'
       ];
       $this->sendData($data);
   }
    public function get_purchase_progress_total() {

        $params = $_GET;
        $data = $this->_modelObj->get_purchase_progress_total($params);
        if (empty($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }



   //get_list_progress_export_excel

    /**
     * 导出
     * @author luxu 2019-08-1
     */
    public function progressexport_excel(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $this->_modelObj->get_list_progress_export_excel($params);
    }

    /**
     * 导出
     * @author sinder 2019-05-24
     * http://www.caigouapi.com/api/purchase/purchase_order/export_excel
     */
    public function export_excel(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        if(isset($params['demand_number']) and count($params['demand_number']) > 100){
            exit('请不要超过100个备货单');
        }

        if(empty($params)){
            exit('参数请求错误');
        }
        $this->_modelObj->get_list_export_excel($params);
    }

    public function import_progress(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        if(!isset($params['packag_file']) or empty($params['packag_file'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['packag_file'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;

        try {
            $data = $this->_modelObj->import_progress($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function progress_export()
    {

        try {
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $result = $this->_modelObj->get_progress_export($params);

            $this->sendData($result);
        }catch ( Exception $exp ) {

            $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));
        }
    }




    /**
     * 获取历史采购信息
     * @author harvin 2019-1-8
     * http://www.caigouapi.com/api/purchase/purchase_order/get_order_history
     ***/
     public function get_order_history() {
        $params = $this->_requestParams;
        if(!isset($params['sku'])){
             $data=['status'=>0,'errorMess'=>'参数请求错误'];
             $this->sendData($data);
        }
        $data=$this->_modelObj->order_history($params);   
          if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
         $this->sendData($data);
     }

     /**
      * 获取订单追踪历史记录
      * @author： luxu
      **/
     public function get_progess_history() {

         $params = $this->_requestParams;
         $data=$this->_modelObj->get_progess_history($params);
         if (is_null($data)) {
             $this->_code = $this->getServerErrorCode();
             $this->_msg = $this->_modelObj->getErrorMsg();
         }
         $this->sendData($data);

     }

    /**
     * @return string
     */
    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 批量编辑采购单
     */
    public function get_batch_edit_order(){
        $time_list = [];
        $time_list['start_handle'] = $this->get_microtime();
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_batch_edit_order($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        // 增加“实际总额”字段。实际总额=订单总额+总运费-总优惠额   --- start ---

        $total_data = [];      // 返回给前端的统计数据

        $order_total         = 0.00;     // 订单总额
        $total_item_freight  = 0.00;     // 总运费
        $total_item_discount = 0.00;     // 总优惠额

        $order_list = isset($data['data_list']['data'])?$data['data_list']['data']:[];
        $pertain_wms_list = isset($data['data_list']['pertain_wms_list'])?$data['data_list']['pertain_wms_list']:[];
        if (!empty($order_list)) {
            foreach ($order_list as $item) {
                if(empty($item['items_list']))continue;
                foreach ($item['items_list'] as $l) {
                    $order_total         += $l['confirm_amount'] * $l['purchase_unit_price'];  // 以含税单价计算
                    $total_item_freight  += $l['item_freight'];
                    $total_item_discount += $l['item_discount'];
                }
            }
        }

        // 实际总额=订单总额+总运费-总优惠额
        $actual_total = $order_total + $total_item_freight - $total_item_discount;

        $total_data['order_total']         = number_format($order_total, 3, '.', '');
        $total_data['total_item_freight']  = number_format($total_item_freight, 3, '.', '');
        $total_data['total_item_discount'] = number_format($total_item_discount, 3, '.', '');
        $total_data['actual_total']        = number_format($actual_total, 3, '.', '');

        $result['data_list']['data']       = $data['data_list'];
        $result['data_list']['total_data'] = $total_data;
        $result['data_list']['drop_down_box'] = [
            'pertain_wms_list' => $pertain_wms_list,
            'is_drawback'=>['1'=>'是','2'=>'否'],
            'shipment_type'=>['1'=>'工厂发运','2'=>'中转仓发运']
        ];
        $result['status']                  = $data['status'];
        $result['errorMess']               = $data['errorMess'];
        $time_list['end_handle'] = $this->get_microtime();
        $result['data_list']['time'] = $time_list;

        // 增加“实际总额”字段。实际总额=订单总额+总运费-总优惠额   --- end ---

        $this->sendData($result);
    }

    /**
     * 批量编辑采购单-保存
     */
    public function save_batch_edit_order(){
        $uid            = isset($_GET['uid'])?$_GET['uid']:'';
        if (empty($uid)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = 'UID缺失';
            $this->sendData();
        }

        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        if(!isset($params['data']) or empty($params['data']) or !is_json($params['data'])){
            $this->_code = $this->getServerErrorCode();
            $this->_msg = '数据缺失或者非JSON格式';
            $this->sendData();
        }
        $data           = json_decode($params['data'],true);
        $params['data'] = $data;
        $params['uid']  = $uid;

        //是否推送蓝凌系统
        if($this->config->item('purchasing_order_audit')===TRUE){
            $params['purchasing_order_audit']=1;
        }else{
            $params['purchasing_order_audit']=2;
        }
        $data = $this->_modelObj->save_batch_edit_order($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }
    /**
     * 批量审核采购单--显示
     */
    public function batch_audit_order_list(){
        try {
            $start = $this->get_microtime();
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_batch_audit_order_list($this->_requestParams);
            if(is_array($data)){
                $data["request"] = [
                    "start" => $start,
                    "end"   => $this->get_microtime(),
                ];
            }
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
     /**
     * 批量审核采购单--保存
     */
    public function batch_audit_order_save(){
        try {
            $start = $this->get_microtime();
            $data = $this->_modelObj->batch_audit_order_save($this->_requestParams);
            if(is_array($data)){
                $data["request"] = [
                    "start" => $start,
                    "end"   => $this->get_microtime(),
                ];
            }
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /*
     * 无需付款
     */

    public function no_payment_save(){
        try {
            $data = $this->_modelObj->no_payment_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 确认提交信息
     * http://www.caigouapi.com/api/purchase/purchase_order/get_confirmation_information
     * @author harvin 2019-1-9
     * * */
    public function get_confirmation_information(){
         $params = $this->_requestParams;
         if(!isset($params['purchase_numbers'])){
              $data=['status'=>0,'errorMess'=>'请勾选数据'];
              $this->sendData($data);
         }
         //是否推送蓝凌系统
         if($this->config->item('purchasing_order_audit')===TRUE){
             $params['purchasing_order_audit']=1;
         }else{
             $params['purchasing_order_audit']=2;
         }
        $data=$this->_modelObj->save_purchase($params);
        if(empty($data)){
             $data=['status'=>0,'errorMess'=>'操作失败'];
            $this->sendData($data);
        }else{
            $this->sendData($data);
        }
        
    }
     /**
     * 撤销提交的信息
     * @author harvin
     * 2019-1-10
     * http://www.caigouapi.com/api/purchase/purchase_order/get_revoke_order
     * * */
    public function get_revoke_order() {
        $params = $this->_requestParams;
        if(!isset($params['ids'])){
            $data=['status'=>0,'errorMess'=>'请勾选数据'];
            $this->sendData($data);
         }
        $data=$this->_modelObj->order_status_save($params);
        $this->sendData($data);
    }
     /**
     * 编辑显示采购单列表字段显示
     * @author harvin 2019-1-8
     * http://www.caigouapi.com/api/purchase/purchase_order/get_table_boy
     * * */
    public function get_table_boy() {
        $data = $this->_modelObj->table_columns();
        $data['data_list']['product_status'] = "产品状态";
        $this->sendData($data);
    }
    /**
     * 保存编辑显示采购单列表字段显示
     * @author harvin
     * http://www.caigouapi.com/api/purchase/purchase_order/get_table_save
     * * */
    public function get_table_save(){
        $params = $this->_requestParams;
        $data=$this->_modelObj->table_save($params);
        $this->sendData($data);    
    }

    /**
     * 保存编辑显示采购单列表字段显示
     * @author jolon
     * http://www.caigouapi.com/api/purchase/purchase_order/get_table_search_header
     * * */
    public function get_table_search_header(){
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data=$this->_modelObj->get_table_search_header($params);
        $this->sendData($data);
    }

    /**
     * 获取采购单搜索头部的结果集
     * @author yefanli
     */
    public function get_search_select_data()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data=$this->_modelObj->get_search_data_by_one($params);
        $this->sendData($data);
    }

    /**
     * 采购单列表搜索框配置-查看
     * @author jolon
     * http://www.caigouapi.com/api/purchase/purchase_order/save_table_search_header
     * * */
    public function save_table_search_header(){
        $params = $this->_requestParams;
        $data=$this->_modelObj->save_table_search_header($params);
        $this->sendData($data);
    }
    /**
     * 采购单列表搜索框配置-保存
     * @author jolon
     * http://www.caigouapi.com/api/purchase/purchase_order/order_binding_logistics
     * * */
     public function order_binding_logistics() {
          $params = $this->_requestParams;
          if(!isset($params['ids'])){
            $data=['status'=>0,'errorMess'=>'请勾选数据'];
            $this->sendData($data);
         }
          $data=$this->_modelObj->get_order_binding_logistics($params);      
          $this->sendData($data);    
     }
    /**
     * 保存相物流单号关信息
     * http://www.caigouapi.com/api/purchase/purchase_order/order_binding_logistics_save
     * @author harvin 2019-1-8
     * * */
     public function order_binding_logistics_save() {
         $params = $this->_requestParams;
         if(isset($params['order_info'])){
             foreach ($params['order_info'] as $key => $item){
                 if (!isset($item['purchase_number']) || empty($item['purchase_number'])){
                     $data=['status'=>0,'errorMess'=>'采购单号不能为空'];
                     $this->sendData($data);
                 }
                 if (!isset($item['sku']) || empty($item['sku'])){
                     $data=['status'=>0,'errorMess'=>'SKU不能为空'];
                     $this->sendData($data);
                 }
             }
         }else{
             $data=['status'=>0,'errorMess'=>'参数错误,订单信息不能为空'];
             $this->sendData($data);
         }

         if (isset($params['logistics_info'])){
             foreach ($params['logistics_info'] as $key => $item){
                 if (!isset($item['express_no']) || empty($item['express_no'])){
                     $data=['status'=>0,'errorMess'=>'物流单号/快递单号必须同时填写'];
                     $this->sendData($data);
                 }
                 if (!isset($item['cargo_company_id']) || empty($item['cargo_company_id'])){
                     $data=['status'=>0,'errorMess'=>'物流单号/快递单号必须同时填写'];
                     $this->sendData($data);
                 }
             }
         }

          $data=$this->_modelObj->get_order_binding_logistics_save($params); 
          $this->sendData($data);
     }
     
     /**
     * 打印采购单
     * @author harvin 2019-1-10
     * http://www.caigouapi.com/api/purchase/purchase_order/printing_purchase_order
     * * */
      public function printing_purchase_order() {
         $params = $this->_requestParams;
         if(!isset($params['ids'])){
            $data=['status'=>0,'errorMess'=>'请勾选数据'];
            $this->sendData($data);
         }
          $data=$this->_modelObj->get_printing_purchase_order($params); 
          $this->sendData($data);      
      }
      /**
       * 返回打印采购单数据
       * @author harvin
       * http://www.cgapi.com/api/purchase/purchase_order/print_menu
       * **/
     public function print_menu(){
         try {
            $this->_init_request_param("POST");
            $data=$this->_modelObj->get_print_menu($this->_requestParams); 
            $this->sendData($data);       
         } catch (Exception $exc) {
             $this->sendError(-1, $exc->getMessage());
         }     
     }

    /**
     * 下载送货单
     * @author 叶凡立  20200730
     * @return  mixed
     */
    public function download_purchase_delivery_note()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->download_purchase_delivery_note($params);

        if(isset($params['behavior']) && $params['behavior'] == 1)$this->sendData($data);

        if(isset($params['behavior']) && $params['behavior'] == 2){
            try{
                if(!empty($data['data_list'])){
                    $this->load->model('compact/Print_pdf_model');
                    $this->Print_pdf_model->writePdfOnDom($data['data_list'], '', '', 'D', '');
                }else{
                    echo '未获取到下载数据';exit;
                }
            }catch(Exception $e){
                echo $e->getMessage();exit;
            }
        }
    }

    /**
     * 下载PDF采购单
     * @author Jaxton 2018/02/26
     * /compact/compact/download_compact
     */
    public function download_purchase_order(){
        if($_SERVER["REQUEST_METHOD"] == 'GET'){
            $this->_init_request_param("GET");
        }else{
            $this->_init_request_param("POST");
        }
        $params = $this->_requestParams;
        $data = $this->_modelObj->download_purchase_order($params);
        try{
            if(!empty($data['data_list'])){
                $this->load->model('compact/Print_pdf_model');
                $html = $data['data_list']['html'];
                $file_name = 'PURCHASE-'.date('Ymdhis');
                /*
                $css_file_name = "DownloadPurchase.css";
                $this->Print_pdf_model->writePdf($html,'',$file_name,'D',$css_file_name);
                */
                $this->Print_pdf_model->writePdfOnDom($html, '', $file_name, 'D', '');
            }else{
                echo '未获取到下载数据';exit;
            }

        }catch(Exception $e){
            echo $e->getMessage();exit;
        }

    }



          /**
     * 根据指定的 采购单编号 预览采购单合同数据
     *      生成进货单第一步 - 合同采购确认
     * @author Jolon
     */
    public function compact_confirm_purchase() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->compact_confirm_purchase($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /*
     * 根据指定的 采购单编号 创建对账单
     *      生成进货单第一步 - 合同采购确认
     * @author Jaden
    purchase/purchase_order/create_statement_confirm
     */
    public function create_statement_confirm() {
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_create_statement_confirm($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }




    /**
     * 根据指定的 采购单编号 预览采购单合同数据
     *      生成进货单第一步 - 合同采购确认
     * @author Jolon
     */
    public function compact_confirm_template() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->compact_confirm_template($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
    /**
     * 根据指定的 采购单编号 预览采购单合同数据
     *      生成进货单第一步 - 合同采购确认
     * @author Jolon
     */
    public function compact_create() {

        $params = $this->_requestParams;
        $data = $this->_modelObj->compact_create($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 采购单驳回
     * @author liwuxue
     * @date 2019/1/30 10:43
     * @param
     * @method POST
     * @url http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=885
     */
    public function reject_order()
    {
        try {
            $data = $this->_modelObj->reject_order($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 采购单作废
     * @author liwuxue
     * @date 2019/1/30 10:43
     * @param
     * @method POST
     */
    public function cancel_order()
    {
        try {
            $data = $this->_modelObj->cancel_order($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 采购单审核
     * @author liwuxue
     * @method POST
     * @date 2019/1/30 10:43
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=886
     *
     */
    public function audit_order()
    {
        try {
            $data = $this->_modelObj->audit_order($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }


   /**
    * 采购蓝凌单审核
    *@author harvin 2019-1-30 
    * http://www.caigouapi.com/api/purchase/purchase_order/audit_orders
    **/
  public function audit_orders(){
      if($this->config->item('purchasing_order_audit')===TRUE){
       $params = $this->_requestParams;
       if(empty($params['id'])){
            $data=['status'=>0,'errorMess'=>'请勾选数据'];
            $this->sendData($data);
       }
       $data_list = $this->_modelObj->audit_orders($params);
       if(empty($data_list['processid'])){
            $data=['status'=>0,'errorMess'=>'待推送蓝凌系统'];
            $this->sendData($data);
       }else{
         
            $this->sendData($data_list);
       }
      }else{
            $this->sendData(['processid'=>FALSE]);
      }
    
  }

  /**
 * 获取采购单操作日志
 *      2019-02-01
 * @author Jaxton
 * /purchase/purchase_order/get_purchase_operator_log
 */
    public function get_purchase_operator_log(){
        $params = $this->_requestParams;
        //print_r($params);die;
        $data = $this->_modelObj->get_purchase_operator_log($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
  * 获取报损界面sku数据
  * 2019-02-01
  * @author Jaxton
  * /purchase/purchase_order/get_reportloss_sku_data
  */
  public function get_reportloss_sku_data(){
      $params = $this->_requestParams;
        //print_r($params);die;
        $data = $this->_modelObj->get_reportloss_sku_data($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
  }

  /**
  * 申请报损确认
  * 2019-02-01
  * @author Jaxton
  * /purchase/purchase_order/reportloss_submit
  */
  public function reportloss_submit(){
      $uid            = isset($_GET['uid'])?$_GET['uid']:'';
      if (empty($uid)) {
          $this->_code = $this->getServerErrorCode();
          $this->_msg = 'UID缺失';
          $this->sendData();
      }

      $this->_init_request_param('POST');
      $params = $this->_requestParams;
      if(!isset($params['data']) or empty($params['data']) or !is_json($params['data'])){
          $this->_code = $this->getServerErrorCode();
          $this->_msg = '数据缺失或者非JSON格式';
          $this->sendData();
      }
      $data           = [];
      $data['data']   = json_decode($params['data'],true);
      $data['remark'] = $params['remark'];
      $data['uid']    = $uid;
      $data = $this->_modelObj->reportloss_submit($data);

    if (is_null($data)) {
        $this->_code = $this->getServerErrorCode();
        $this->_msg = $this->_modelObj->getErrorMsg();
    }

    $this->sendData($data);
  }

  /**
  * 入库日志
  * 2019-02-19
  * @author Jaxton
  * /purchase/purchase_order/get_storage_record
  */
  public function get_storage_record(){
    $params = $this->_requestParams;
        //print_r($params);die;
        $data = $this->_modelObj->get_storage_record($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
  }

    /**
     * 获取交易订单的物流跟踪信息
     * /purchase/purchase_order/get_logistics_trace_info
     */
    public function get_logistics_trace_info(){
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_logistics_trace_info($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 获取交易订单的物流跟踪信息
     * /purchase/purchase_order/get_logistics_trace_info
     */
    public function get_set_table_header(){
        try {
            $params = $this->_requestParams;
            $data = $this->_modelObj->get_set_table_header_info($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * @desc 编辑
     * @author Jeff
     * @Date 2019/6/25 16:54
     * @return
     */
    public function save_table_list(){
        try {
            $params = $this->_requestParams;
            $data = $this->_modelObj->save_table_list_info($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 获取各种备注
     * /purchase/purchase_order/get_note_list
     */
    public function get_note_list(){
        try {
            $params = $this->_requestParams;
            $data = $this->_modelObj->get_note_list_info($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 手动刷新1688订单是否异常
     * /purchase/purchase_order/get_note_list
     */
    public function fresh_ali_order_abnormal(){
        try {
            $params = $this->_requestParams;
            $data = $this->_modelObj->fresh_ali_order_abnormal($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData();
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 批量审核信息修改待审核状态的采购单--显示
     */
    public function batch_audit_data_change_order(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->batch_audit_data_change_order($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 批量审核信息修改待审核状态的采购单--保存
     */
    public function batch_audit_data_change_save(){
        try {
            $data = $this->_modelObj->batch_audit_data_change_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 采购单信息修改
     */
    public function get_change_order_preview()
    {
        $this->_init_request_param("GET");
        $data = $this->_modelObj->get_change_order_preview($this->_requestParams);
        $this->sendData($data);
    }

    /**
     * 保存采购单信息修改
     */
    public function save_change_order_preview()
    {
        try {
            $data = $this->_modelObj->save_change_order_preview($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 非1688下单订单信息修改-预览
     */
    public function change_order_data_preview(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_change_order_data_preview($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 非1688下单订单信息修改-保存
     */
    public function change_order_data_save(){
        try {
            $this->_init_request_param("REQUEST");
            $data = $this->_modelObj->change_order_data_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 采购单作废,以备货单维度
     * @author liwuxue
     * @date 2019/1/30 10:43
     * @param
     * @method POST
     */
    public function cancel_order_by_demand_number()
    {
        try {
            $data = $this->_modelObj->cancel_order_by_demand_number($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 1688下单订单信息修改-预览
     */
    public function edit_order_data_preview(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_edit_order_data_preview($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
      * 变更采购员
     **/
    public function update_purchase_data() {

        try {

            $this->_init_request_param("POST");

            $data = $this->_modelObj->update_purchase_data($this->_requestParams);
            $this->sendData($data);
        } catch ( Exception $e ) {

            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    // 获取OA 供应链人员信息
    public function get_purchase_oa() {

        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_purchase_oa($this->_requestParams);
            $this->sendData($data);

        }catch ( Exception $e ) {

            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 1688下单订单信息修改-预览
     *
     */
    public function edit_ali_order_data_save(){
        try {
            $this->_init_request_param("REQUEST");
            $data = $this->_modelObj->get_edit_ali_order_data_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
      * function:获取SKU 信息
     **/

    public function get_sku_message()
    {

        try
        {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_sku_message($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }



    /**
     * 批量添加备注
     * @author Manson
     */
    public function batch_add_remark(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->batch_add_remark($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 计算采购单的参考运费
     * @author Jolon
     */
    public function get_calculate_order_freight(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_calculate_order_freight($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 供应商门户-预计到货时间审核
     * @author Jerry
     */
    public function audit_arrive_time_status() {

        $params = $this->_requestParams;
        try {
        $data = $this->_modelObj->audit_arrive_time_status($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
        }
        catch ( Exception $exp )
            {
                $this->sendError($exp->getCode(), $exp->getMessage());
            }
    }

    /**
     *供应商门户-预计到货时间审核日志
     * @author Jerry
     */
    public function get_audit_arrive_log() {

        $this->_init_request_param("POST");
        try {

            $data = $this->_modelObj->audit_arrive_time_log($this->_requestParams);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
        }
        catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     *供应商门户-预计到货时间审核日志
     * @author Jerry
     */
    public function get_audit_info() {

        $this->_init_request_param("POST");
        try {
            $data = $this->_modelObj->audit_arrive_info($this->_requestParams);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData($data);
        }
        catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     * 下载文件
     * @author dean
     * @param $url string
     */
    public function download_label_file(){
        $url = $this->input->get_post('url');
        $this->load->library('file_operation');
        try{
            if(empty($url)){
                throw new Exception(
                     "链接地址不存在",
                    -1
                );

            }
            if(!$this->file_operation->download_file($url,2)){
                throw new Exception(
                    "下载失败",
                    -1
                );
            };
        }catch ( Exception $exp){

            $this->sendError($exp->getCode(), $exp->getMessage());

        }
    }

    /**
     * 采购单预览功能接口
     * @METHOD:GET
     * @author:luxu
     * @time:2020/年6月3号
     **/
    public function getViewData(){

        try{
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $result = $this->_modelObj->getViewData($params);
            $this->sendData($result);
        }catch ( Exception $exp ){
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     * 采购单预览功能接口(下载)
     * @METHOD:GET
     * @author:luxu
     * @time:2020/年6月3号
     **/
    public function downViewData(){

        try{
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $result = $this->_modelObj->downViewData($params);
            $this->sendData($result);
        }catch ( Exception $exp ){
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     * 获取PO 回货日志数据
     * 数据中心接口文档：http://dp.yibai-it.com:33344/web/#/118?page_id=15758
     * @author:luxu
     * @time:2020/7/3
     **/

    public function getShipmentsQty(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $result = $this->_modelObj->getShipmentsQty($params);
            $this->sendData($result);
        }catch ( Exception $exp ){
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     * 从1688获取数据，批量刷新PO运费、优惠金额、总金额
     * @author 叶凡立
     * @time 20200807
     */
    public function ali_order_refresh_purchase_SDP(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->ali_order_refresh_purchase_SDP($params);
        $this->sendData($data);
    }

    /**
     * 从（批量）编辑采购单-切换采购单是否退税数据
     * @author 叶凡立
     * @time 20201012
     */
    public function get_order_payment_pay(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->get_order_payment_pay($params);
        $this->sendData($data);
    }

    /**
     * 获取多货调拨
     * @author 叶凡立
     * @time  20201014
     */
    public function get_order_sku_allocation_info()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->get_order_sku_allocation_info($params);
        $this->sendData($data);
    }

    /**
     * 保存多货调拨数据
     * @author 叶凡立
     * @time  20201014
     */
    public function save_order_sku_allocation()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->save_order_sku_allocation($params);
        $this->sendData($data);
    }

    /**
     * 采购单催发货
     * @author 叶凡立
     * @time  20201123
     */
    public function urge_send_order()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->urge_send_order($params);
        $this->sendData($data);
    }

    /**
     * 采购单催改价
     * @author 叶凡立
     * @time  20201123
     */
    public function urge_change_order_price()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->urge_change_order_price($params);
        $this->sendData($data);
    }

    /**
     * 获取虚拟入库
     * @author 叶凡立
     * @time  20201125
     */
    public function get_imitate_purchase_instock()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->get_imitate_purchase_instock($params);
        $this->sendData($data);
    }

    /**
     * 保存虚拟入库
     * @author 叶凡立
     * @time  20201125
     */
    public function save_imitate_purchase_instock()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data=$this->_modelObj->save_imitate_purchase_instock($params);
        $this->sendData($data);
    }

}
