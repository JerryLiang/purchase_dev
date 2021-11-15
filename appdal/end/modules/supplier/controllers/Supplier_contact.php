<?php

/**
 * Created by PhpStorm.
 * 获取供应商联系方式
 * User: Jackson
 * Date: 2018/12/27 0027 11:17
 */
class Supplier_contact extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Supplier_contact_model');
        $this->_modelObj = $this->Supplier_contact_model;
    }

    /**
     * @desc 获取供应商联系方式
     * @author Jackson
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_contact()
    {
        $params = gp();
        $data = $this->_modelObj->get_supplier_contact($params);
        // 获取供应商信息
        $this->load->model('supplier_model');
        $this->load->model('supplier_address_model');
        $this->load->library('eye_check');
        $supplier = $this->supplier_model->get_supplier_info($params['supplier_code'],false);

        if(isset($data['list']['data'][0])){
            if($supplier){
                $ship_province  = $this->supplier_address_model->get_address_name_by_id($supplier['ship_province']);
                $ship_city      = $this->supplier_address_model->get_address_name_by_id($supplier['ship_city']);
                $ship_area      = $this->supplier_address_model->get_address_name_by_id($supplier['ship_area']);
                $data['list']['data'][0]['ship_address'] = $ship_province.$ship_city.$ship_area.$supplier['ship_address'];
            }else{
                $data['list']['data'][0]['ship_address'] = '';
            }
        }
        $data['list']['company_list'] = [];
        if($supplier){
            $company_list = $this->eye_check->get_supplier_eye_check_info($supplier['credit_code']);
            $data['list']['company_list'] = $company_list['company_list'];
        }

        $this->send_data($data, '供应商联系方式', true);
    }

//翻译供应商信息
    public function translate_supplier_info()
    {

        //

        $params = gp();

         if(empty($params['supplier_name'])) $this->error_json('供应商名称不能为空');
         if(empty($params['address'])) $this->error_json('地址不能为空');
        if(empty($params['person'])) $this->error_json('联系人不能为空');


        $translate_data = array('supplier_name'=>array('text'=>$params['supplier_name'],'language'=>'en'),
           'address'=>array('text'=>$params['address'],'language'=>'en'),'person'=>array('text'=>$params['person'],'language'=>'en')

        );

        $trans_url = SMC_JAVA_API_URL.'/util/translate/comboTranslate';

        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $trans_url = $trans_url . "?access_token=" . $access_taken;

        $trans_res = getCurlData($trans_url,json_encode($translate_data, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果

        $trans_res = json_decode($trans_res,true);


        if (!empty($trans_res)) {

            $this->success_json($trans_res);


        } else {
            $this->error_json('未响应');


        }


    }





}