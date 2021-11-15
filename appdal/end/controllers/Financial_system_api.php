<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Financial_system_api extends MY_API_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('Financial_system_model');  
        $this->load->model('finance/Payment_order_pay_model'); 
        $this->load->model('payment_order_pay_model');
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->helper('user');
    }

    /**
     * 财务请求获取数据接口
     * Financial_system_api/Financial_inquiry
     */
    public function Financial_inquiry()
    {
        $data = $this->Financial_system_model->get_financial_inquiry([], SOURCE_NETWORK_ORDER);
        if (empty($data)) $this->error_json('没有数据了');

        foreach ($data as $key => $row) {
            $info = $this->payment_order_pay_model->net_order_info($row['requisition_number']);
            if(isset($info['storage_record'])) unset($info['storage_record']);

            $data[$key]['info'] = $info;
        }

        $this->success_json($data,null,'成功');
    }

    /**
     * 财务更新接口
     * Financial_system_api/Financial_update
     */
    public function Financial_update()
    {
        $requisition_number_list = file_get_contents("php://input");
        if(!is_json($requisition_number_list)){
            $this->error_json('请传递JSON格式数据');
        }
        $requisition_number_list = array_unique(json_decode($requisition_number_list, TRUE));

        if (empty($requisition_number_list) || !is_array($requisition_number_list)) {
            $this->error_json('请求参数错误');
        }elseif(count($requisition_number_list) > 500){
            $this->error_json('超过一次请求数量限制(最大数=500)');
        }else{
            $this->db->where_in('requisition_number', $requisition_number_list)->update('purchase_order_pay', ['is_financial' => 1]);
            $this->success_json('成功');
        }
    }

    /**
     * 只推送富友,并且是付款成功的
     * @author harvin
     * @date 2019/8/6
     * Financial_system_api/Push_ufxfuiou_system
     */
    public function Push_ufxfuiou_system(){
        //获取数据需求数据(富友数据)
       $ufxfuiou= $this->Financial_system_model->ufxfuiou_financial_list();
       if(!$ufxfuiou['code']){
           echo $ufxfuiou['msg'];die;
       } 
       $data_list= isset($ufxfuiou['data'])?$ufxfuiou['data']:[];
       if(empty($data_list)){
           echo "富友数据不存在";die;
       }
       //获取推送数据
     $reslut=  $this->Financial_system_model->get_order_list($data_list,'ufxfuiou');
     if(empty($reslut)){
         echo "请款单不存在";die;
     }
     //推送财务
     foreach ($reslut as $row) {
        $resluet= $this->Financial_system_model->to_financial_from($row,'ufxfuiou');
         $data_error_sucess[]=$resluet;
     }  
     echo "<pre>"; 
     print_r($data_error_sucess);die;
    }   
    
     /**
     * 只推送宝付,并且是付款成功的
     * @author harvin
     * @date 2019/8/6
     * Financial_system_api/Push_financial_system
     */
    public function Push_baofopay_system(){
       //获取数据需求数据(富友数据)
       $baofopay= $this->Financial_system_model->abofopay_financial_list();
       if(!$baofopay['code']){
           echo $baofopay['msg'];die;
       } 
       $data_list= isset($baofopay['data'])?$baofopay['data']:[];
       if(empty($data_list)){
           echo "宝付数据不存在";die;
       }  
        //获取推送数据
        $reslut=  $this->Financial_system_model->get_order_list($data_list,'baofopay');
        if(empty($reslut)){
            echo "请款单不存在";die;
        }
           //推送财务
        foreach ($reslut as $row) {
           $resluet= $this->Financial_system_model->to_financial_from($row,'baofopay');
           $data_error_sucess[]=$resluet;
        } 
          echo "<pre>"; 
          print_r($data_error_sucess);die;
        
        
        
    }

    /**
     * 只推送拉卡拉,并且是付款成功的
     * @author harvin jolon
     * @date   2019/8/6
     * Financial_system_api/Push_lakala_system
     */
    public function Push_lakala_system(){
        //获取数据需求数据(富友数据)
        $lakalapay = $this->Financial_system_model->lakalapay_financial_list();
        if(!$lakalapay['code']){
            echo $lakalapay['msg'];
            die;
        }
        $data_list = isset($lakalapay['data']) ? $lakalapay['data'] : [];
        if(empty($data_list)){
            echo "宝付数据不存在";
            die;
        }
        //获取推送数据
        $reslut = $this->Financial_system_model->get_order_list($data_list, 'lakala');
        if(empty($reslut)){
            echo "请款单不存在";
            die;
        }
        //推送财务
        foreach($reslut as $row){
            $resluet             = $this->Financial_system_model->to_financial_from($row, 'lakala');
            $data_error_sucess[] = $resluet;
        }
        echo "<pre>";
        print_r($data_error_sucess);
        die;
    }

    /**
     * 采购入库单接口
     * Financial_system_api/Push_warehouse_results?is_afresh_push_finance_error=1&force_push=1
     */
    public function Push_warehouse_results(){
        set_time_limit(0);

        $is_afresh_push_finance_error = $this->input->get_post('is_afresh_push_finance_error');// 用来重推 之前推送出错的记录
        $force_push = $this->input->get_post('force_push');
        for($i = 0;$i < 10;$i ++){
            if(!empty($is_afresh_push_finance_error) and $is_afresh_push_finance_error == 1){
                $is_push_finance = 3;// 重新推送 推送失败的
            }else{
                $is_push_finance = 1;// 默认推送
            }
            $res_count = $this->Financial_system_model->Push_warehouse_results($is_push_finance,$force_push);

            if($res_count == 0){
                echo "所有数据推送完成";exit;
            }

            echo "第 [".($i + 1)."] 次推送，成功：".$res_count."<br/>";
        }

        echo '本次推送成功';exit;
    }


    /**
     * 采购入库单接口
     * Financial_system_api/Push_warehouse_results_oversea?is_afresh_push_finance_error=1&force_push=1
     */
    public function Push_warehouse_results_oversea(){
        set_time_limit(0);

        $is_afresh_push_finance_error = $this->input->get_post('is_afresh_push_finance_error');// 用来重推 之前推送出错的记录
        $force_push = $this->input->get_post('force_push');
        for($i = 0;$i < 10;$i ++){
            if(!empty($is_afresh_push_finance_error) and $is_afresh_push_finance_error == 1){
                $is_push_finance = 3;// 重新推送 推送失败的
            }else{
                $is_push_finance = 1;// 默认推送
            }
            $res_count = $this->Financial_system_model->Push_warehouse_results_oversea($is_push_finance,$force_push);

            if($res_count == 0){
                echo "所有数据推送完成";exit;
            }

            echo "第 [".($i + 1)."] 次推送，成功：".$res_count."<br/>";
        }

        echo '本次推送成功';exit;
    }
}