<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 产品修改审整合列表
 * User: Jolon
 * Date: 2019/03/16 21:00
 */

class Product_mod_integrate extends MY_ApiBaseController {

    public function __construct(){
        parent::__construct();
        $this->load->model('product/Product_mod_integrate_model', 'product_mod_integrate');
        $this->_modelObj = $this->product_mod_integrate;
    }

    /**
     * 获取供应商整合列表
     * /product/product_mod_integrate/get_integrate_list
     * @author Jolon
     */
    public function get_integrate_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data   = $this->_modelObj->get_integrate_list($params);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 整合成功 或 整合失败
     * /product/product_mod_integrate/integrate_result
     * @author Jolon
     */
    public function integrate_result(){
        $params = $this->_requestParams;
        $data   = $this->_modelObj->integrate_result($params);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 添加整合备注
     * /product/product_mod_integrate/integrate_note_add
     * @author Jolon
     */
    public function integrate_note_add(){
        $params = $this->_requestParams;
        $data   = $this->_modelObj->integrate_note_add($params);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 取消供应商整合
     * /product/product_mod_integrate/integrate_cancel
     * @author Jolon
     */
    public function integrate_cancel(){
        $params = $this->_requestParams;
        $data   = $this->_modelObj->integrate_cancel($params);
        if(is_null($data)){
            $this->_code = $this->getServerErrorCode();
            $this->_msg  = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 供应商整合导出
     * /product/product_mod_integrate/integrate_cancel
     * @author Jolon
     */
    public function integrate_export(){
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->integrate_export($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $values       = $data['data_list']['value'];
        $heads        = $data['data_list']['key'];
        if(!empty($values)){
            $tax_list_tmp = [];
            foreach($values as $value){

                $tax_tmp                           = [];
                $tax_tmp['id']                     = $value['id'];
                $tax_tmp['audit_status']           = $value['audit_status'];
                $tax_tmp['create_user_time']       = $value['create_user_name'].'/'.$value['create_time'];
                $tax_tmp['integrate_status']       = $value['integrate_status'];
                $tax_tmp['develop_time']           = $value['develop_time'];
                $tax_tmp['product_line_name']      = $value['product_line_name'];
                $tax_tmp['product_img_url']        = $value['product_img_url'];
                $tax_tmp['sku']                    = $value['sku'];
                $tax_tmp['product_name']           = $value['product_name'];
                $tax_tmp['old_supplier_price']     = $value['old_supplier_price'];
                $tax_tmp['new_supplier_price']     = $value['new_supplier_price'];
                $tax_tmp['old_supplier_name']      = $value['old_supplier_name'];
                $tax_tmp['new_supplier_name']      = $value['new_supplier_name'];
                $tax_tmp['is_sample']              = $value['is_sample'];
                $tax_tmp['integrate_check_result'] = $value['integrate_check_result'];
                $tax_tmp['integrate_note']         = $value['integrate_note'];

                $tax_list_tmp[] = $tax_tmp;
            }
            csv_export($heads,$tax_list_tmp,'供应商整合列表-'.date('YmdH:i:s'));
        }else{
            $result = [
                'status'    => 0,
                'errorMess' => '未找到目标记录',
                'data_list' => []
            ];
            $this->sendData($result);
        }
    }


}