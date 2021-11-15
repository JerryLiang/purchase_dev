<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Journal_log_model extends Purchase_model {
      protected $table_reject   = 'reject_note';// 驳回信息表
      protected $table_log   = 'operator_log';// 系统操作日志表
    
    /**
     * 获取需求驳回日志表
     * @param $demand_number string <需求单号> 
     * @param $limit int <每页显示数量>
     * @param $offset inin <每页开始下标>
     * @author harvin
     * @date <2019-1-4>
     * **/
   public function log_list($demand_number,$limit,$offset){
       $query_builder = $this->purchase_db;
       $data=[];
       $data_log= $query_builder->where('link_code', $demand_number)->order_by('id','desc')
                ->get($this->table_reject, $limit, $offset)
                ->result_array();
       //统计总数
       $rems=$query_builder->where('link_code', $demand_number)->get($this->table_reject)->result_array();
       $count= count($rems);
       if(empty($data_log)){
           $data=[];
       }else{
           $data=[
               'data_list'=>$data_log,
               'total'=>$count
           ];
       }
       
       return $data;    
   }
   
    /**
     * 获取系统操作日志表
     *  @param $demand_number string <需求单号> 
     * @param $limit int <每页显示数量>
     * @param $offset inin <每页开始下标>
     * @author harvin
     * @date <2019-1-5>
     **/
   public function operator_log($demand_number,$limit,$offset,$page){
       $query_builder = $this->purchase_db;
       $data=[];
       $data_log= $query_builder->where('record_number', $demand_number)->order_by('id','desc')
                ->get($this->table_log, $limit, $offset)
                ->result_array();
       //统计总数
       $rems=$query_builder->where('record_number', $demand_number)->get($this->table_log)->result_array();
       $count= count($rems);
       if(empty($data_log)){
           $data=[];
       }else{
          $data = [
            
            'values'=>$data_log,
             'paging_data'=>[
                'total'=>$count,
                'offset'=>$page,
                'limit'=>$limit,
                'pages'=> ceil($count/$limit)
            ]
        ];
       }
       return $data; 
   }
    
    
}