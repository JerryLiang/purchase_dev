<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 产品修改审核控制器
 * User: Jaxton
 * Date: 2019/01/24 15:00
 */

class Product_mod_audit extends MY_ApiBaseController{

	public function __construct(){
        parent::__construct();
        $this->load->model('product/Product_mod_audit_model','product_mod');
        $this->_modelObj = $this->product_mod;
    }

    /**
    * 获取列表
    * /product/product_mod_audit/get_product_list
    * @author Jaxton 2019/01/21
    */
    public function get_product_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_product_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    }

    /**
    * 审核
    * /product/product_mod_audit/product_audit
    * @author Jaxton 2019/01/21
    */
    public function product_audit(){
    	// $type = $this->input->get_post('type');
    	// if(!in_array($type,[1,2])) http_response(response_format(0,[],'审核类型错误，请检查'));//1不拿样审核，2拿样审核
        $params = $this->_requestParams;

        $data = $this->_modelObj->product_audit($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);    	
    }

    
    /**
    * 品控审核(产品系统)
    * /product/product_mod_audit/product_control_audit
    * @author Jaxton 2019/01/28
    */
    public function product_control_audit(){
        $id = $this->input->get_post('id');//产品修改信息ID
        if(empty($id)) http_response(response_format(0,[],'缺少参数ID'));
        $audit_result = $this->input->get_post('audit_result');  //审核结果，1通过，2驳回
        if(empty($audit_result) || !in_array($audit_result,[1,2])) http_response(response_format(0,[],'审核结果错误，请检查'));
        $result=$this->product_mod->product_control_audit($id,$audit_result);
        if($result['success']){
            $return_data=response_format(1,[],'操作成功');
        }else{
            $return_data=response_format(0,[],$result['error_msg']);
        }
        http_response($return_data);
    }



    /**
     * 产品审核列表导出
     /product/product_mod_audit/product_audit_export
     * @author Jaden
     */
    public function product_audit_export(){
        ini_set('memory_limit','1024M');
        try{
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->load->helper('export_excel');
        if($_SERVER["REQUEST_METHOD"] == 'GET'){
            $this->_init_request_param("GET");
        }
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_product_audit_export($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $data_list = $data['data_list'];
        $heads = array('审核状态','审核人','审核时间','审核备注','申请人','申请时间','产品图片',
            'sku','产品名称','产品状态','产品线','开发员',
            '创建时间','修改前单价','修改后单价','修改前税点',
            '修改后税点','修改前供应商','修改后供应商','修改前箱内数',
            '修改后箱内数','修改前外箱尺寸','修改后外箱尺寸','修改前链接',
            '修改后链接','是否拿样','修改前代采','修改后代采','样品检验结果','确认人','确认时间','确认备注','申请备注','申请类型','修改原因'
        ,'所属小组','结算方式(修改前)','结算方式(修改后)','是否包邮(修改前)','是否包邮(修改后)','价格变化比例');
//        if(!empty($data_list['tax_list_tmp'])){
        if($params['export_mode'] == 1){
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
//            $tax_list_tmp = $data['data_list'];
//            header('location:'.$tax_list_tmp);
        }else{
            //product_img_url
            export_excel($heads, $data_list['tax_list_tmp'], '产品审核列表-'.date('YmdHis').'.xlsx', $data_list['field_img_name'], $data_list['field_img_key']);
        }
        }catch( Exception $exp ) {

                $this->sendData(array('status' => 0, 'errorMessage' => $exp->getMessage()));
            }

        /*}else{
            $this->sendData($data);
        }*/


//        $tax_list_tmp = $data['data_list'];
//        header('location:'.$tax_list_tmp);

    }

    /**
      * 产品价格修改日志
     **/
    public function get_product_log() {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_product_log($params);

        $this->sendData($data);

    }

    /**
     * 获取审核日志信息
     * @author :luxu
     * @time:2020/3/10
     **/
    public function getProductAuditLog(){

        try{

            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $reasons = $this->_modelObj->getProductAuditLog($params);
            $this->sendData($reasons);
        }catch ( Exception $exp )
        {
            $this->sendData($exp->getMessage());
        }
    }

    public function get_drop_box() {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_drop_box($params);

        $this->sendData($data);

    }

    public function get_supplier_avg(){

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_supplier_avg($params);

        $this->sendData($data);
    }

}