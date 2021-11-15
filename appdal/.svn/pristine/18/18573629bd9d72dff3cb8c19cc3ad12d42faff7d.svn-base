<?php

/**
 * 线下退款原因配置
 * Class Offline_reason
 * @author Jolon
 * @date 2021-01-12 14:50:01
 */
class Offline_reason extends MY_Controller
{

   public function __construct(){
        parent::__construct();
        $this->load->model('Offline_reason_model');
    }


    /**
     * 获取 线下退款原因配置 数据列表
     */
    public function get_offline_reason(){
        $list = $this->Offline_reason_model->get_offline_reason();

        $status = ['1' => '可用','2' => '禁用'];
        $number_need_status = ['1' => '是','2' => '否'];
        foreach($list as &$value){

            $value['status_cn'] = isset($status[$value['status']])?$status[$value['status']]:'-';
            $value['purchase_number_need_cn'] = isset($number_need_status[$value['purchase_number_need']])?$number_need_status[$value['purchase_number_need']]:'-';
            $value['logistics_number_need_cn'] = isset($number_need_status[$value['logistics_number_need']])?$number_need_status[$value['logistics_number_need']]:'-';
        }

        $this->success_json($list);

    }

    /**
     * 获取 退款类型 下拉框选择
     */
    public function get_refund_type_list(){
        $list = $this->Offline_reason_model->get_refund_type_list();
        $this->success_json(['refund_type_list' => $list]);
    }

    /**
     * 获取 退款类型 下拉框选择
     */
    public function get_refund_reason_list(){
        $list = $this->Offline_reason_model->get_refund_reason_list();
        $this->success_json(['refund_reason_list' => $list]);
    }

    /**
     * 新增退款类型
     */
    public function create_refund_type(){
        $refund_type = $this->input->get_post('refund_type');

        if(empty($refund_type)){
            $this->error_json('参数 退款类型 必填');
        }

        $result = $this->Offline_reason_model->create_refund_type($refund_type);
        if($result){
            $this->success_json([],null,'新增退款类型成功');
        }else{
            $this->error_json('新增退款类型失败');
        }
    }

    /**
     * 新增/编辑 退款原因
     */
    public function update_refund_reason(){
        $params = [
            'id' => $this->input->get_post('id'),
            'refund_type' => $this->input->get_post('refund_type'),
            'refund_reason' => $this->input->get_post('refund_reason'),
            'purchase_number_need' => $this->input->get_post('purchase_number_need'),
            'logistics_number_need' => $this->input->get_post('logistics_number_need'),
            'reason_remark' => $this->input->get_post('reason_remark'),
        ];

        if(!empty($params['id'])){
            unset($params['refund_reason']);// 更新数据 不修改 退款原因
        }else{
            if(empty($params['refund_reason'])) $this->error_json(' 参数 退款原因 必填');
        }
        if(empty($params['refund_type'])) $this->error_json(' 参数 退款类型 必填');
        if(empty($params['purchase_number_need'])) $this->error_json(' 参数 采购单号是否必填 必填');
        if(empty($params['logistics_number_need'])) $this->error_json(' 参数 物流单号是否必填 必填');
        if(empty($params['reason_remark'])) $this->error_json(' 参数 应用场景与说明 必填');

        if(!in_array($params['purchase_number_need'],[1,2])){
            $this->error_json('参数 采购单号是否必填 非法');
        }
        if(!in_array($params['logistics_number_need'],[1,2])){
            $this->error_json('参数 物流单号是否必填 非法');
        }


        $result = $this->Offline_reason_model->create_refund_reason($params);

        if($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 更新 退款原因 禁用启用状态
     */
    public function update_refund_reason_status(){
        $id = $this->input->get_post('id');
        $status = $this->input->get_post('status');


        $result = $this->Offline_reason_model->update_refund_reason_status($id,$status);
        if($result['code']){
            $this->success_json();
        }else{
            $this->error_json($result['message']);
        }
    }
}    
