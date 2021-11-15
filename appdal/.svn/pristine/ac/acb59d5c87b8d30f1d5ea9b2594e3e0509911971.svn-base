<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * 对接计划系统
 * Class Plan_system
 */
class Plan_system extends MY_API_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('others/Plan_system_model');
    }

    /**
     * 推送采购单状态到 计划系统
     * @url http://pms.yibainetwork.com:81/plan_system/push_purchase_order_info_to_plan
     */
    public function push_purchase_order_info_to_plan(){
        $result = $this->Plan_system_model->push_purchase_order_info_to_plan();
        $this->success_json($result);
    }

    /**
     * 计划系统变更目的仓,发运数等信息
     * @url http://pms.yibainetwork.com:81/plan_system/change_shipment_info
     * @author Manson
     */
    public function change_shipment_info()
    {
        try{
            //接收参数
            $params = $this->compatible('post');

            if (!isset($params['data']) || empty($params['data'])){
                throw new Exception('未接收到数据');
            }

            $params_data = json_decode($params['data'],true);

            if (count($params_data)>400){
                throw new Exception('数据不能超过400条');
            }
            //更新
            $this->load->model('Shipment_track_list_model','',false,'purchase_shipment');
            $this->data = $this->Shipment_track_list_model->change_shipment_info($params_data);
            $code = 200;

        }catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    /**
     * 推送采购单状态到 计划系统
     * @url http://pms.yibainetwork.com:81/plan_system/sys_push_waiting_arrival_info_to_plan
     */
    public function sys_push_waiting_arrival_info_to_plan(){
        $result = $this->Plan_system_model->sys_push_waiting_arrival_info_to_plan();
        $this->success_json($result);
    }

    public function new_sys_push_waiting_arrival_info_to_plan(){

        $ci = get_instance();
        $ci->load->config('mongodb');
        $host = $ci->config->item('mongo_host');
        $port = $ci->config->item('mongo_port');
        $user = $ci->config->item('mongo_user');
        $password = $ci->config->item('mongo_pass');
        $author_db = $ci->config->item('mongo_db');
        $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $author_db = $author_db;

        //$query = $this->db->from("purchase_order AS orders")->join("")->where("is_push_plan",1);
        $query = $this->db->from('purchase_order_items oi')
            ->join('purchase_suggest s', 's.demand_number=oi.demand_number AND  oi.sku=s.sku', 'left')
            ->join('purchase_order od', 'od.purchase_number=oi.purchase_number', 'left')
            ->where('s.source_from', 1)//数据来源于计划系统
            ->where('od.is_push_plan',1)->where_not_in('od.purchase_order_status',[PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT]);

        $count = $query->count_all_results();

        $limit =  100;

        $page = ceil($count/$limit);
        $return         = ['code' => true,'success_list' => [],'error_list' => []];
        for( $i=1;$i<=$page;++$i){

            // $sql = " SELECT purchase_number FROM pur_purchase_order WHERE  is_push_plan=1 LIMIT ".($i-1)*$limit.",".$limit;

            $sql = " SELECT distinct  od.purchase_number FROM pur_purchase_order_items AS oi LEFT JOIN pur_purchase_suggest AS s ";
            $sql .= " ON s.demand_number=oi.demand_number AND  oi.sku=s.sku ";
            $sql .= " LEFT JOIN pur_purchase_order as od ON od.purchase_number=oi.purchase_number";
            $sql .= " WHERE s.source_from=1 AND od.is_push_plan=1  AND od.purchase_order_status NOT IN (2,15) order by od.id DESC LIMIT ".($i-1)*$limit.",".$limit;
            $result = $this->db->query($sql)->result_array();
            if(empty($result)){

                break;
            }

            if(!empty($result)){

                $purchase_lists =  array_column($result,'purchase_number');

                $purchase_items = $this->Plan_system_model->get_push_data($purchase_lists,3);
                if(!empty($purchase_items)){
                    foreach($purchase_items as $purchase_item_key=>$purchase_item_value){

                        if(empty($purchase_item_value['demand_number'])){
                            unset($purchase_items[$purchase_item_key]);
                        }
                    }
                    $result = $this->Plan_system_model->java_push_purchase_order($purchase_items,4);
                    $logs['data'] = json_encode($purchase_items);
                    $logs['return'] = json_encode($result);
                    $bulk = new MongoDB\Driver\BulkWrite();
                    $mongodb_result = $bulk->insert($logs);
                    $mongodb->executeBulkWrite("{$author_db}.pushPlan", $bulk);

                    if( !empty($result) && isset($result['success_list'])){

                        $purchaseKeys = array_keys($result['success_list']);

                        if(!empty($purchaseKeys)){

                            $this->db->where_in("purchase_number",$purchaseKeys)->update("purchase_order",['is_push_plan'=>2]);
                        }
                    }
                    $return['success_list'] = array_merge($return['success_list'],$result['success_list']??[]);
                    $return['error_list'] = array_merge($return['error_list'],$result['error_list']??[]);
                    $this->success_json($result);
                }
            }
        }
    }


}