<?php
/**
 * Created by PhpStorm.
 * 入库记录控制器
 * User: Jaxton
 * Date: 2019/02/19 0027 11:17
 */

class Warehouse_storage_record extends MY_Controller{

    public function __construct(){
    	self::$_check_login = false;
        parent::__construct();
        $this->load->model('Warehouse_storage_record_model','storage_model');
    }

    /**
    * 接收仓库返回的结果
    * /warehouse/warehouse_storage_record/receive_warehouse_results
    */
    public function receive_warehouse_results(){

    	$results = $this->input->get_post('results');
        
        if (isset($results) && !empty($results))
        {
            $results = json_decode($results,true);
        
            $find = $this->storage_model->findWarehouse($results);
            if($find['bool']){
                echo json_encode($find['data']);
            }else{
                 echo $find['data'];
            }

        } else {

            echo '没有任何的数据传输过来！';
        }
    }

    /**
     * 自动 运行采购单 采购状态判断逻辑
     * @author Jolon
     * /warehouse/warehouse_storage_record/update_order_status
     */
    public function update_order_status(){
        $this->storage_model->update_order_status();
        exit('sss');
    }

    /**
     * 判断入库是否异常
     * @author Jolon
     * /warehouse/warehouse_storage_record/update_is_storage_abnormal
     */
    public function update_is_storage_abnormal(){
        $purchase_numbers = $this->input->get_post('purchase_numbers');
        if(empty($purchase_numbers)) exit('Not found records!');

        if(stripos($purchase_numbers,',')){
            $purchase_numbers = explode(',',$purchase_numbers);
        }else{
            $purchase_numbers = [$purchase_numbers];
        }

        foreach($purchase_numbers as $purchase_number){
            $result = $this->storage_model->check_is_storage_abnormal($purchase_number);
            print_r($result);exit;
        }

        exit('Finished!');
    }

}