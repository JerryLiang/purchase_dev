<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 权均交期控制器
 * User: Jaden
 * Date: 2019/01/17 
 */

class Delivery extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct(){
        parent::__construct();
        parent::__construct();
        $this->load->model('purchase/Delivery_model');
        $this->_modelObj = $this->Delivery_model;

        // $this->load->model('delivery_model','delivery');
        // $this->load->model('purchase_user_model','product_user',false,'user');
        // $this->load->model('product_line_model','product_line',false,'product');
        // $this->load->model('product_model','product',false,'product');
    }

    /**
     * 权均交期列表
     * /purchase/delivery/delivery_list
     * @author Jaden 2019-1-17
    */
    public function delivery_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_delivery_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 权均交期列表导出
     * /purchase/delivery/delivery_export
     * @author Jaden 2019-1-17
    */
    public function delivery_export(){

        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->delivery_export_list($params);
        $this->sendData($data);
//        $tax_list_tmp = $data['data_list'];
//        if(!empty($tax_list_tmp)){
//            foreach($tax_list_tmp as $key=>$value){
//
//                $monthData = NULL;
//                if(!empty($value['monthData'])){
//
//                    foreach($value['monthData'] as $mKey=>$mValue){
//
//                        $monthData .= $mValue['month']." ".$mValue['deveily_days']."\r\n";
//                    }
//
//                }
//                $tax_list_tmp[$key]['monthData'] = $monthData;
//            }
//        }
//
//
//
//
//        $heads = ['SKU','产品状态','产品线','供应商', '权均交期（天）','目的中转仓库','权限交期-日志'];
//        csv_export($heads,$tax_list_tmp,'均权交期-'.date('YmdH_i_s'));
    }

    /**
     * 获取权限交期的日志数据
     * @params :无
     * @MTHODS :GET
     * @AUTHOR:LUXU
     * @time: 2020/6/15
     **/

    public function getDeliveryLogs(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->getDeliveryLogs($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

}