<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";


/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Cancel extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('Cancal_model');
    }
    
    
    /**
     * 取消未到货接收数据中心返回参数因超时不能记录数据
     * @author harvin
     * @date 2019-7-26
     * /cancel/cancal_ail
     */
    public function cancal_ail(){   
      $len= $this->rediss-> llenData('CANCEL');
      if($len<=0){
          exit('没有相关数据');
      }
      for($i=1;$i<=$len;$i++){
          //取消列队尾部数据第一个
         $cancal_id= $this->rediss->rpopData('CANCEL');
         //判断该记录是否在取消主表里存在  如不存在 要系统自动创建这条记录
         $temp=$this->Cancal_model->get_cancal_list($cancal_id);
         if(!$temp){ //不存在  则要创建
          $data= $this->rediss->getData($cancal_id);
           //保存主表及明细表数据
         $reslut= $this->Cancal_model->cancal_save($cancal_id,$data);
         if(isset($reslut['code']) && $reslut['code']=='200'){
              echo $reslut['msg'];
         }else{ //记录失败重新写入队列
            $this->rediss->lpushData('CANCEL',$cancal_id);    
         }   
       }
  
      } 
    }
}