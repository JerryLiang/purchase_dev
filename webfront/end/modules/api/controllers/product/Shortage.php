<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 缺货列表控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Shortage extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct(){
        parent::__construct();
        $this->load->model('product/Shortage_model');
        $this->_modelObj = $this->Shortage_model;
    }



    /**
     * 缺货列表
     /product/shortage/shortage_list
     * @author Jaden
     */
    public function shortage_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->web_get_shortage_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);


    }
    /**
     * 缺货列表导出
     /product/shortage/shortage_export
     * @author Jaden
     */
    public function shortage_export(){
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_shortage_export_list($params);
        $tax_list_tmp = $data['data_list'];
        $heads = ['图片URL','产品名称','默认供应商名称','SKU','货源状态','产品状态','在途库存','可用库存','缺货数量','开发员','采购员'];
        csv_export($heads,$tax_list_tmp,'缺货列表-'.date('YmdH_i_s'));
    }



}