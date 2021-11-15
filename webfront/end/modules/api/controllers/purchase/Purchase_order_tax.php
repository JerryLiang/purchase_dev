<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 含税订单控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_order_tax extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct() {
        parent::__construct();
        $this->load->model('purchase/Purchase_order_tax_model');
        $this->_modelObj = $this->Purchase_order_tax_model;  
    }

    /**
     * 根据查询条件获取含税订单列表
     * /purchase/purchase_order_tax/tax_order_tacking_list
     * @author Jaden 2019-1-8
     */
    public function tax_order_tacking_list() {
        $params = $this->_requestParams;

        $data = $this->_modelObj->tax_order_tacking_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function tax_order_tacking_sum() {
        $params = $this->_requestParams;

        $data = $this->_modelObj->tax_order_tacking_sum($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }




    /**
     * 退税订单导出
     * /purchase/purchase_order_tax/drawback_order_export
     * @author Jaden 2019-1-10
     */
    public function drawback_order_export(){
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->drawback_export_list($params);
        $tax_list_tmp = $data['data_list']['invoice_list'];
        $heads_max = max($data['data_list']['heads_num']);
        $heads = ['备货单号','是否异常','采购单号','SKU','产品名称','开票品名','开票单位','出口海关编码','采购员','下单时间','供货商名称','报关单号','报关品名','报关单位','开票型号','单价(含税)','币种','报关数量','总金额','报关时间', '发票清单号','发票清单时间','发票清单状态'];
        for ($i=1; $i <= $heads_max; $i++) { 
            array_push($heads,"发票代码(左".$i.")","发票号码(右".$i.")");
        }
        array_push($heads,"开票时间");
        csv_export($heads,$tax_list_tmp,'含税订单-'.date('YmdH_i_s'));
       //csv_export($heads,$tax_list_tmp,'含税订单-'.date('YmdH:i:s'));

    }



    /**
     * 生成发票清单操作
     * /purchase/purchase_order_tax/generate_invoice_list
     * @author Jaden 2019-1-10
     */
    public function generate_invoice_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->web_generate_invoice_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);    
            
        
    }

    /**
     * 含税订单跟踪页面-点击入库数量弹出的列表
     * /purchase/purchase_order_tax/warehousing_list
     * @author Jaden 2019-1-10
     */
    public function warehousing_list(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_warehousing_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 含税订单跟踪页面-点击已报关数量弹出的列表
     * /purchase/purchase_order_tax/declare_customs
     * @author Jaden 2019-1-10
     */
    public function declare_customs(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_declare_customs_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 含税订单跟踪页面-点击已开票弹出的列表
     * /purchase/purchase_order_tax/invoiced_list
     * @author Jaden 2019-1-10
     */
    public function invoiced_list(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_invoiced_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 含税订单跟踪页面-点击库龄弹出的列表
     * /purchase/purchase_order_tax/library_age_list
     * @author Jaden 2019-1-10
     */
    public function library_age_list(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_library_age_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);


    }


    /**
     * 生成发票清单操作
     * /purchase/purchase_order_tax/batch_create_invoice_listing
     * @author Manson
     */
    public function batch_create_invoice_listing(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->web_generate_invoice_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);


    }

    /**
     * 退税订单跟踪列表-导出
     * purchase/purchase_order_tax/export_list
     * @author Manson
     */
    public function export_list()
    {
        try {
            $this->_init_request_param('GET');
            $params = $this->_requestParams;
            $this->_modelObj->export_list($params);
        }catch ( Exception $exp ) {

            $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));
        }
    }


    /**
     * 获取待采购审核数据
     * @MTHOD GET
     * @author :luxu
     * @time:2020/5/18
     **/
    public function purchase_review(){

        try{
            // 发票清单号
            $this->_init_request_param('GET');
            $params = $this->_requestParams;
            $result = $this->_modelObj->get_purchase_review($params);
            $this->sendData($result);
        }catch ( Exception $exp ){

            $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));

        }
    }

    /**
     * 驳回接口
     * @MTHOD POST
     * @author:luxu
     * @time:2020/5/19
     **/
    public function reject(){

       try{
           $this->_init_request_param("POST");
           $params = $this->_requestParams;
           $result = $this->_modelObj->reject($params);
           $this->sendData($result);
       }catch ( Exception $exp ){

           $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));
       }
    }

    /**
     *  上传发票图片
     *  @METHODS  POST
     *  @author:luxu
     *  @time: 2020/5/19
     **/

    public function uplodeImage(){

        try{

            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $result = $this->_modelObj->uplodeImage($params);
            $this->sendData($result);
        }catch ( Exception $exp ){

            $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));
        }
    }

    /**
     * 获取发票图片地址
     * @METHODS GET
     * @author:luxu
     * @time:2020/5/19
     **/

    public function getImage(){

        try{
            // 发票清单号
            $this->_init_request_param('GET');
            $params = $this->_requestParams;
            $result = $this->_modelObj->getImage($params);
            $this->sendData($result);
        }catch ( Exception $exp ){

            $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));

        }
    }



}
