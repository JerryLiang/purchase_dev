<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Ware extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('arrival_record_model');
    }
    
   
    /**
     * 接收并保存采购到货记录
     * @author 2019-2-19
     * http://www.caigou.com/ware/get_arriva
     * **/
    public function get_arriva(){
        $post=$this->input->post('deliveryData');
         if(!empty($post)){
            $qc_id = [];
            foreach($post as $k=>$v){
                $data['purchase_order_no'] = $v['purchase_order_no'];
                $data['sku'] = $v['sku'];
                $data['delivery_qty'] = $v['delivery_qty'];
                $data['delivery_time'] = empty($v['delivery_time']) ? $v['delivery_time'] : $v['delivery_time'];
                $data['delivery_user'] = empty($v['delivery_user']) ? '系统操作' : $v['delivery_user'];
                $data['cdate'] = time();
                $data['express_no'] = $v['express_no'];
                $data['check_type'] = $v['check_type'];
                $data['bad_products_qty'] = $v['bad_products_qty'];
                $data['check_time'] = $v['check_time'];
                $data['check_user'] = $v['check_user'];
                $data['qc_id'] = $v['qc_id'];
                $data['note'] = $v['note'];
                $temp=$this->arrival_record_model->arrival_record_save($data);
                if($temp['bool']){
                  $qc_id[]=$v['qc_id'];
                }else{
                  exit(json_encode(['qc_id'=>$qc_id,'code'=>'202','msg'=>$temp['msg']]));
                }
            }
            exit(json_encode(['qc_id'=>$qc_id,'code'=>'200','msg'=>'success']));
        }else{
            exit(json_encode(['code'=>'500','msg'=>'null']));
        }
    }


    /**
     * 推送采购单状态到 仓库系统（JAVA 接口）
     *      数据来源 Warehouse_order_contact_model::push_send_order_status_queue方法
     * @author Jolon
     * @showDoc  http://192.168.71.156/web/#/105?page_id=4521
     */
    public function send_order_status_ware(){
        $operator_key = 'SEND_ORDER_STATUS_WARE';

        $len = $this->rediss->set_scard($operator_key);// 获取集合元素的个数

        if($len){
            $this->load->model('purchase/Purchase_order_model');
            $this->load->model('warehouse/Warehouse_storage_record_model','storage_model');

            $count = ($len > 100)? 100 : $len;
            $purchase_suggest_status_list = [];
            for($i = 0;$i < $count;$i ++){
                $purchase_number = $this->rediss->set_spop($operator_key);
                $purchase_number = current($purchase_number);
                if($purchase_number){
                    $purOrderInfo    = $this->storage_model->get_purchase_order_and_suggest($purchase_number);
                    // 无需推送的状态：信息修改待审核 信息修改驳回 作废订单待审核 作废订单待退款 待生成进货单 待采购审核 待采购询价
                    if(empty($purOrderInfo) or in_array($purOrderInfo[0]['purchase_order_status'], [
                        PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT,
                        PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                        PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND,
                        PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT,
                        PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER,
                        PURCHASE_ORDER_STATUS_WAITING_QUOTE]
                    )){
                        continue;
                    }else{
                        foreach($purOrderInfo as $status_value){
                            $purchase_suggest_status_list[] = [
                                'purchaseNumber'     => $status_value['purchase_number'],
                                'sku'                => $status_value['sku'],
                                'suggestOrderStatus' => $status_value['suggest_order_status']
                            ];
                        }
                    }
                }
            }

            if($purchase_suggest_status_list){
                $failList = $purchase_suggest_status_list;
                try{
                    $java_access_taken = getOASystemAccessToken();
                    $get_url           = getConfigItemByName('api_config', 'wms_system', 'updateWmsSuggestOrderStatus');
                    $get_url           .= "?access_token=".$java_access_taken;
                    $result            = getCurlData($get_url, json_encode($purchase_suggest_status_list), 'post', ['Content-Type: application/json']);
                    if(!is_json($result)){
                        throw new Exception('仓库返回的数据不是JSON');
                    }

                    $result      = json_decode($result, true);
                    $successList = isset($result['data']['successList'])?$result['data']['successList']:[];
                    if(isset($result['data']['failList']) and $result['data']['failList']){
                        $failList = $result['data']['failList'];
                        throw new Exception('部分数据推送失败');
                    }

                    echo 'SUCCESS ';
                }catch(Exception $e){
                    if($failList){
                        foreach($failList as $fail_value){
                            $this->rediss->set_sadd($operator_key,$fail_value['purchaseNumber']);// 推送失败 下次继续执行
                        }
                        print_r($failList);
                    }
                    echo $e->getMessage();
                }
            }
            exit("Finished");
        }else{
            exit("没有需要操作的数据");
        }
    }

}
