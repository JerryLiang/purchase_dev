<?php

//abnormal_data_api.php
include_once APPPATH ."core/MY_Oauth_Controller.php";
#include_once APPPATH . "core/MY_API_Controller.php";

class abnormal_data_api extends MY_Oauth_Controller{

    public  function __construct()
    {

        parent::__construct();
    }

    /**
     * 42056 【异常列表管理】中仓库操作“手动完结”后不直接修改采购系统状态，改为采购系统接受结果后自己判断
     * @author:luxu
     * @time:2021年9月10号
     */

    public function abnormal_data_status(){

        $clientDatas = json_decode($this->_requestData,True);
        if(empty($clientDatas)){
            $this->error_json("数据为空");
        }
        $successDatas = $errorsDatas = []; // 成功和错误数据缓存数组
        foreach($clientDatas as $clientDatas_key=>$clientDatas_value){
            $abnormals = $this->db->from("purchase_warehouse_abnormal")->where("defective_id",$clientDatas_value['defective_id'])->select("id,is_handler")
                ->get()->row_array();
            if(empty($abnormals)){

                $errorsDatas[] = [
                    'defective_id' => $clientDatas_value['defective_id'],
                    'msg' => '异常单号不存在'
                ];
                continue;
            }
            $update['is_handler'] = $abnormals['is_handler'];
            $update['warehouse_handler_result'] = '';
            $update['warehouse_handler_time'] = date("Y-m-d H:i:s",time());
            // 仓库系统推送手动完结
            if($clientDatas_value['is_handler'] == 5){
                // 采购系统异常单号为"未处理" 就标记为手动完结
                if($abnormals['is_handler'] == 0){
                    $update['is_handler'] = 5;
                    $update['warehouse_handler_result'] = '仓库系统手动完结';
                }
                // 采购系统异常单号为"驳回",就标记为已处理
                if($abnormals['is_handler'] == 6){

                    $update['is_handler'] = 1;
                    $update['warehouse_handler_result'] = '仓库系统手动完结';
                }
            }else{
                // 仓库系统推送手动驳回
                $update['is_handler'] = 6;
                $update['warehouse_handler_result'] = '仓库系统手动驳回';

            }

            $result = $this->db->where("defective_id",$clientDatas_value['defective_id'])->update("purchase_warehouse_abnormal",$update);
            //添加日志
            $logs=[
                'record_number' => $clientDatas_value['defective_id'],
                'record_type' => 'PURCHASE_WAREHOUSE_ABNORMAL',
                'content' => '仓库处理:'.$update['warehouse_handler_result'].',处理人:'.$clientDatas_value['operator'],
                'content_detail' =>$clientDatas_value['content_detail'],
                'operator' => $clientDatas_value['operator'],
                'operate_time' => date("Y-m-d H:i:s",time()),
                'is_show' =>1
            ];

            $logs = $this->db->insert('operator_log',$logs);
            $successDatas[] = [

                'defective_id' => $clientDatas_value['defective_id'],
                'msg' => '采购系统处理成功'
            ];
        }

        $this->success_json($this->_OK,['success'=>$successDatas,'errors'=>$errorsDatas]);


    }

}