<?php

/**
 * 设置采购单状态
 * Created by PhpStorm.
 * User: 袁学文
 * Date: 2019/2/20
 * Time: 14:15
 */
class Status_set extends MY_Controller
{
  	
    /**
     * 设置采购单状态
     * @author 袁学文
     * @date 2019/2/20 14:15
     * @param
     * @url /system/status_set/get_se
     */
    public function get_set(){
        try {
            $this->load->model("Status_set_model");
            $this->Status_set_model->get_set_redis();
            $this->success_json([]); 
        } catch (Exception $exc) {
             $this->error_json($exc->getMessage());
        }
            
        
        
        
    }
    
}
