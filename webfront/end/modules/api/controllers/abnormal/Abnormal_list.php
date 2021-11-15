<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 异常列表控制器
 * User: Jaxton
 * Date: 2019/01/16 10:00
 */

class Abnormal_list extends MY_ApiBaseController{
	public function __construct(){
        parent::__construct();
        $this->load->model('abnormal/Abnormal_list_model','abnormal_model');
        $this->_modelObj = $this->abnormal_model;
    }

    /**
    * 获取异常数据列表
    * /abnormal/abnormal_list/get_abnormal_list
    * @author Jaxton 2019/01/16
    */
    public function get_abnormal_list(){

        $params = $this->_requestParams;

        $data = $this->_modelObj->get_abnormal_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);   	
    }

    /**
    * 采购员处理
    * /abnormal/abnormal_list/buyer_handle
    * @author Jaxton 2019/01/16
    */
    public function buyer_handle(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->buyer_handle_submit($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);     
    }

    /**
    * 驳回
    * /abnormal/abnormal_list/abnormal_reject
    * @author Jaxton 2019/01/16
    */
    public function abnormal_reject(){

        $params = $this->_requestParams;

        $data = $this->_modelObj->abnormal_reject($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);     
    }

    /**
    * 查看
    * /abnormal/abnormal_list/look_abnormal
    * @author Jaxton 2019/01/16
    */
    public function look_abnormal(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_look_abnormal($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);     
    }

    
    /**
    * 获取省
    * /abnormal/abnormal_list/get_province
    * @author Jaxton 2019/01/21
    */
    public function get_province(){

        $data = $this->_modelObj->get_province();

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);     
    }

    /**
    * 获取市
    * /abnormal/abnormal_list/get_city_county
    * @author Jaxton 2019/01/21
    */
    public function get_city_county(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_city_county($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);     
    }

    /**
    * 添加异常数据(仓库系统调取)
    * /abnormal/abnormal_list/add_abnormal_data
    * @author Jaxton 2019/01/28
    */
    public function add_abnormal_data(){
        $params=[
            'sku' => $this->input->get_post('sku'),
            'quantity' => $this->input->get_post('quantity'),//数量
            'exception_position' => $this->input->get_post('exception_position'),//异常货位
            'defective_type' => $this->input->get_post('defective_type'),//次品类型
            'pur_number' => $this->input->get_post('pur_number'),//采购单号
            'express_code' => $this->input->get_post('express_code'),//快递单号
            'abnormal_type' => $this->input->get_post('abnormal_type'),//异常类型
            'abnormal_depict' => $this->input->get_post('abnormal_depict'),//异常原因
            'img_path_data' => $this->input->get_post('img_path_data'),//图片地址
            'buyer' => $this->input->get_post('buyer'),//采购员名称
            'add_username' => $this->input->get_post('add_username'),//异常信息创建人
            'create_user_name' => $this->input->get_post('create_user_name'),//异常信息创建人
            'create_time' => $this->input->get_post('create_time'),//创建时间

        ];
        // $params=[
        //     'sku' => 'JY04547',
        //     'quantity' => 55,//数量
        //     'exception_position' => 'CP0120',//异常货位
        //     'defective_type' => 3,//次品类型
        //     'pur_number' => 'PO353999',//采购单号
        //     'express_code' => '71310340389536',//快递单号
        //     'abnormal_type' => 1,//异常类型
        //     'abnormal_depict' => '混料很严重.... 数量不够，无法入库',//异常原因
        //     'img_path_data' => 'xxx',//图片地址
        //     'buyer' => '姜龙华',//采购员名称
        //     'add_username' => '刘楚雯',//异常信息创建人

        // ];
        //验证字段是否
        $validate_result=$this->abnormal_model->validate_abnormal($params);
        if($validate_result['success']){
            $insert_result=$this->abnormal_model->add_abnormal_data($params);
            if($insert_result['success']){
                $return_data=response_format(0,[],'操作成功');
            }else{
                $return_data=response_format(0,[],$validate_result['error_msg']);
            }
        }else{
            $return_data=response_format(0,[],$validate_result['error_msg']);
        }
        echo json_encode($return_data,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);die;
    }

    /**
     * 采购需求导出
    abnormal_list/abnormal_list/abnormal_export
     * @author jeff
     */
    public function abnormal_export(){
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_abnormal_export_list($params);
        $this->sendData($data);
//        if (is_null($data)) {
//            $this->_code = $this->getServerErrorCode();
//            $this->_msg = $this->_modelObj->getErrorMsg();
//            $this->sendData($data);
//        }
//
//        $tax_list_tmp = $data['data_list'];
//        header('location:'.$tax_list_tmp);
    }

    /**
     * 获取采购单操作日志
     *      2019-02-01
     * @author Jaxton
     * abnormal_list/abnormal_list/get_abnormal_operator_log
     */
    public function get_abnormal_operator_log(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_abnormal_operator_log($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 获取采购单统计数据
     *      2019-02-01
     * @author jeff
     * abnormal_list/abnormal_list/get_sum_data
     */
    public function get_sum_data(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_sum_data($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 添加异常备注
     * /abnormal/abnormal_list/abnormal_reject
     * @author Jaxton 2019/01/16
     */
    public function add_abnormal_note(){

        $params = $this->_requestParams;

        $data = $this->_modelObj->add_abnormal_note($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 判断采购单是否存在
     * /abnormal/abnormal_list/is_order_exist
     * @author jeff 2019/10/28
     */
    public function is_order_exist(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->is_order_exist($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    public function get_headerlog(){

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_headerlog($params);
        $this->sendData($data);
    }

    public function save_table_list(){

        try{
            $this->_init_request_param("POST");
            $clientData = $this->_requestParams;
            $data = $this->_modelObj->save_table_list($clientData);
            $data = json_decode($data,True);
            $this->sendData($data);

        }catch ( Exception $e ) {

            $this->sendError($e->getCode(), $e->getMessage());
        }

    }




    /**
     * 退货信息保存
     * @author Jaxton 2019/01/17
     */
    public function batch_buyer_handle()
    {

        try {
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->batch_buyer_handle($params);
            $this->sendData($data);
        } catch (Exception $e) {


            $this->sendError($e->getCode(), $e->getMessage());
        }



    }

    /**
     * 智能解析
     * /abnormal/abnormal_list/analysis_return_address
     * @author jeff 2019/10/28
     */
    public function analysis_return_address()
    {

        try {
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->analysis_return_address($params);
            $this->sendData($data);
        } catch (Exception $e) {


            $this->sendError($e->getCode(), $e->getMessage());
        }



    }
}