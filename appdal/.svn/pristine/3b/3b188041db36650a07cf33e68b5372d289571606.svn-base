<?php

/**
 * 线下退款列表
 * Class Offline_receipt
 * @author Jolon
 * @date 2021-01-12 14:50:01
 */
class Offline_refund extends MY_Controller
{

    public function __construct(){
        parent::__construct();
        $this->load->model('Offline_refund_model');
        $this->load->model('system/Offline_reason_model');
    }

    /**
     * 线下退款列表
     * @methods GET
     * @author:luxu
     * @time:2021年1月12号
     **/

    public function get_offline_refund(){

        try{

            $clientDatas = [];
            // 获取HTTP 客户端传入的数据
            if( !empty($_POST) ){

                foreach( $_POST as $key=>$data){
                    $clientDatas[$key] = $this->input->get_post($key);
                }
            }
            $page = !empty($clientDatas['offset'])?$clientDatas['offset']:1;
            $limit = !empty($clientDatas['limit'])?$clientDatas['limit']:20;
            $page = ($page - 1) * $limit;
            $result = $this->Offline_refund_model->get_offline_refund($clientDatas,$limit,$page,$clientDatas['offset']);

            if(!empty($result)){

                foreach($result['values'] as $key=>$datas){

                    $keys = array_keys($datas);
                    foreach($keys as $keydata){

                        if(empty($result['values'][$key][$keydata]) || $result['values'][$key][$keydata] == '0000-00-00 00:00:00'){

                            $result['values'][$key][$keydata] = "-";
                        }
                    }

                }
            }

            $result['drop_down_box']['applicant'] = get_buyer_name(); //申请人
            $result['drop_down_box']['refund_channel'] = [3=>'支付宝',5=>'网银转账']; //3.支付宝;5.网银转账
            $this->success_json($result);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 添加退款数据
     * @param
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function add_offline_refund(){

        try{

            $params = $this->input->get_post('data');
            $params = json_decode($params,true);
            $result = $this->Offline_refund_model->add_offline_refund($params);
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }

    }

    /**
     * 作废申请
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function cancel_refund(){

        try{
            $refund_id = $this->input->get_post("refund_id");
            $message = $this->input->get_post("message");
            if(empty($refund_id)){
                throw new Exception("确实id");
            }

            $result = $this->Offline_refund_model->cancel_refund($refund_id,$message);
            if($result){
                $this->success_json();
            }
            throw new Exception("作废成功");

        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());

        }
    }

    /**
     * 获取退款日志接口
     * @params
     * @author:luxu
     * @time:2021年1月14号
     **/

    public function get_refund_logs(){

      try{

          $refund_id = $this->input->get_post('refund_id'); // 获取退款列表ID
          if(empty($refund_id)){
              throw new Exception("缺少id");
          }

          $result = $this->Offline_refund_model->get_refund_logs($refund_id);
          $this->success_json($result);
      }catch ( Exception $exp ){
          $this->error_json($exp->getMessage());

      }
    }

    /**
     * 获取配置信息
     * @params
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function get_offline_reason(){

        try{

            $list = $this->Offline_reason_model->get_offline_select_reason(1);
            $status = ['1' => '可用','2' => '禁用'];
            foreach($list as &$value){

                $value['status'] = isset($status[$value['status']])?$status[$value['status']]:'未知状态';

                $value['purchase_number_need_cn'] = '';

                if($value['purchase_number_need'] == 1){

                    $value['purchase_number_need_cn'] = '是';
                }else{
                    $value['purchase_number_need_cn'] = '否';
                }

                $value['logistics_number_need_cn'] = '';

                if($value['logistics_number_need'] == 1){

                    $value['logistics_number_need_cn'] = '是';
                }else{
                    $value['logistics_number_need_cn'] = '否';
                }
            }
            $this->success_json($list);
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());

        }
    }

    /**
     * 获取1688拍单号
     * @methods  GET
     * @author:luxu
     * @time:2021年1月15号
     **/

    public function get_pai_number(){

          try{

             $purchaseNumbers= $this->input->get_post('pai_number');
             $result = $this->Offline_refund_model->get_pai_number($purchaseNumbers);
             $this->success_json($result);
          }catch ( Exception $exp ){
              $this->error_json($exp->getMessage());

          }
    }

    /**
     * 线下退款列表(导出)
     * @methods GET
     * @author:luxu
     * @time:2021年1月12号
     **/
    public function get_offline_refund_import(){

        $clientDatas = [];
        // 获取HTTP 客户端传入的数据
        if( !empty($_POST) ){

            foreach( $_POST as $key=>$data){
                $clientDatas[$key] = $this->input->get_post($key);
            }
        }
        $page = !empty($clientDatas['offset'])?$clientDatas['offset']:1;
        $limit = !empty($clientDatas['limit'])?$clientDatas['limit']:1;
        $result = $this->Offline_refund_model->get_offline_refund($clientDatas,$limit,$page-1);

        $total = $result['paging_data']['total']; // 总条数数据
        try {
            $this->load->model('system/Data_control_config_model');
            $result = $this->Data_control_config_model->insertDownData($clientDatas, 'REFUND', '退款列表导出', getActiveUserName(), 'csv', $total);
        } catch (Exception $exp) {
            $this->error_json($exp->getMessage());
        }
        if ($result) {
            $this->success_json("添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }

        die();
    }
}