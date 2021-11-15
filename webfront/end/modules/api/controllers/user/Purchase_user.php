<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Purchase_user extends MY_ApiBaseController
{
      private $_modelObj;

      public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Purchase_user_model');
        $this->_modelObj = $this->Purchase_user_model;
    }

    /**
     * 采购系统用户列表
     * @author Justin
     *  /api/user/Purchase_user/user_all_drop_down_box
     */
     public function user_all_drop_down_box(){
          try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->user_all_drop_down_box($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
     }
}