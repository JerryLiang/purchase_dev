<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-06-05
 * Time: 14:02
 */


require APPPATH.'core/MY_ApiBaseController.php';

class Supplier_balance_order extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('statement/Supplier_balance_order_model');
        $this->_modelObj = $this->Supplier_balance_order_model;
    }

    /**
     * 调整单的导入
     */
    public function imp_balance_order(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        if(!isset($params['file_path']) or empty($params['file_path'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['file_path'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;
        try{
            $data = $this->_modelObj->imp_balance_order($params);
            if($data['code']){
                $this->_code = 0;
                $this->_msg  = "数据导入成功";
                $this->sendData(['data_list' => []]);
            }else{
                $this->_code = 500;
                $this->_msg  = $data['message'];
                $this->sendData(['data_list' => ['error_file_path' => $data['data']]]);
            }
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 余额调整单列表
     */
    public function balance_order_list(){
        $this->_init_request_param("GET");
        $data = $this->_modelObj->balance_order_list($this->_requestParams);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 审核、作废
     */
    public function update_balance_order_status(){
        try{
            $this->_init_request_param("GET");
            $data = $this->_modelObj->update_balance_order_status($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $exp){
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }


    /**
     * 供应商余额调整单 - 导出
     */
    public function export_detail_list(){
        set_time_limit(0);
        $this->_init_request_param("POST");
        $data = $this->_modelObj->export_detail_list($this->_requestParams);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

}