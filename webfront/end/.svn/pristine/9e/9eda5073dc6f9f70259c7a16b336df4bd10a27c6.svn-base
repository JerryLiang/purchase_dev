<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * SKU降本控制器
 * User: Jaden
 * Date: 2019/01/16 
 */

class Reduced_edition extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct(){
        parent::__construct();
        parent::__construct();
        $this->load->model('purchase/Reduced_edition_model');
        $this->_modelObj = $this->Reduced_edition_model;
        /*
        parent::__construct();
        $this->load->model('reduced_edition_model','reduced_edition');
        $this->load->model('purchase_order_items_model','order_items');
        $this->load->model('purchase_order_model','order');
        $this->load->model('product_line_model','product_line',false,'product');
        */
    }

    /**
     * SKU降本列表
     * /purchase/reduced_edition/sku_reduced_edition_list
     * @author Jaden 2019-1-17
    */
    public function sku_reduced_edition_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_reduced_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * SKU降本列表导出
     * /purchase/reduced_edition/reduced_export
     * @author Jaden 2019-1-17
    */
    public function reduced_export(){
        ini_set('memory_limit','3000M');
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->reduced_export_list($params);
        $tax_list_tmp = $data['data_list'];
        /*
        $heads = ['优化人','SKU','产品名称','供应商','价格变化时间','首次计算时间', '统计时间', '原价','现价','价格变化幅度','降本比例','采购数量','取消数量','有效采购数量','价格变化金额','采购单号','备货单号','采购员','下单时间','备注'];
        csv_export($heads,$tax_list_tmp,'SKU降本-'.date('YmdH_i_s'));
        */
        if(!empty($tax_list_tmp)){
            header('location:'.$tax_list_tmp);    
        }
        
    }


     /**
     * 获取SKU采购列表
     * /purchase/reduced_edition/get_sku_purchase_list
     * @author Jaden 2019-1-17
    */
    public function get_sku_purchase_list(){
        $params = $this->_requestParams;
        //$params['sku'] = 'CW00009';
        $data = $this->_modelObj->get_sku_history_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
    /**
      *SKU 降本优化记录接口
     **/

    public function get_reduced_data()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_reduced_data($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
      * function: SKU 降本配置信息
     **/
    public function get_reduced_config()
    {
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_reduced_config($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * function: SKU 降本配置信息修改
     **/
    public function update_reduced_config()
    {
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->update_reduced_config($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * function: SKU 降本配置信息修改日志
     **/
    public function get_reduced_log()
    {

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_reduced_log($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * SKU 降本信息
     * @author luxu
     * @time:2019-09-29
     **/
    public function get_reduced_list()
    {
        try
        {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_reduced_data_list($this->_requestParams);
            //1表示涨价，2表示降价
            $data['choice_change'] = array(
                "gain" => [ ['message'=>'涨价','value'=>1],['message'=>'降价','value'=>2]],
                "effective_purchase_quantity" => [['message'=>'≠0','value'=>2],['message'=>'=0','value'=>1]],
                "is_end" => [['message'=>'是','value'=>1],['message'=>'否','value'=>2]],
                //purchase_quantity
                "purchase_quantity" => [['message'=>'≠0','value'=>2],['message'=>'=0','value'=>1]],
            );
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     * SKU 降本明细
     **/
    public function get_reduced_detail()
    {
        try
        {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_reduced_detail_data($this->_requestParams);
            $data['choice_change'] = array(
                "gain" => [ ['message'=>'涨价','value'=>1],['message'=>'降价','value'=>2]],
                "is_end" => [['message'=>'是','value'=>1],['message'=>'否','value'=>2]],
                "purchase_num" => [['message'=>'≠0','value'=>2],['message'=>'=0','value'=>1]],
                "warehouse_number" => [['message'=>'≠0','value'=>2],['message'=>'=0','value'=>1]],
                "is_effect" => [['message'=>'有效','value'=>1],['message'=>'无效','value'=>2]],
                "is_superposition" => [['message'=>'叠加无PO','value'=>1],['message'=>'叠加','value'=>2],['message'=>'非叠加','value'=>3]],
                "is_new_data" => [['message'=>'新模块','value'=>1],['message'=>'老模块','value'=>2]],
                "is_purchasing" => [['message'=>'否','value'=>1],['message'=>'是','value'=>2]]

            );
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    public function reduced_export_data()
    {

        try{

            $this->_init_request_param("GET");
            $data = $this->_modelObj->reduced_export_data($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     * 获取SKU 降本新老模块数据操作日志
     * @param:  $params   array   HTTP 传入参数
     * @author: luxu
     **/

    public function get_set_reduced_data_log()
    {
        try{

            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_set_reduced_data_log($this->_requestParams);
            $this->sendData($data);

        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     * 设置SKU 降本新老模块数据
     * @param:  $params   array   HTTP 传入参数
     * @author: luxu
     **/

    public function set_reduced_data()
    {
        try{

            $this->_init_request_param("POST");
            $data = $this->_modelObj->set_reduced_data($this->_requestParams);
            $this->sendData($data);

        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

}