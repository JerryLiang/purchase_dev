<?php
/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Demand_suggest extends MY_Controller{

    public function __construct(){
    	self::$_check_login = false;
        parent::__construct();
        $this->load->model('purchase_suggest_model');

    }

    /**
    * 接收推送的备货单数据
    * purchase_suggest/demand_suggest/receive_demand_data
    */
    public function receive_demand_data(){
        $data=$this->input->get_post('data');

        if(empty($data)){
            $this->error_json('参数错误');
        }else{
            $data=json_decode($data,true);
        }
        $result = $this->purchase_suggest_model->receive_demand_data($data);
        $this->success_json($result);
    }
}