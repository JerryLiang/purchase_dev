<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 审核控制器
 */

class Puerchase_examine extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('be_dismissed_model');
    }

    /**
     * 审核不通过
     * /purchase_suggest/puerchase_examine/audit_failed
     * @author harvin  <2019-1-4>
     * */
    public function audit_failed() {
        try {
            //请求的参数
            $id = $this->input->post_get('ids'); //获取勾选的数据
            $reject_remark = $this->input->post_get('reject_remark'); //驳回原因
            if (empty($id)) {
                $this->error_json('请勾选数据');
            }
            if (empty($reject_remark)) {
                $this->error_json('请填写驳回原因');
            }
            //判断需求单的状态 2.驳回待审核
            $ids = explode(',', $id);
            $type = ['2'];
            $demand_status = $this->be_dismissed_model->get_demand_status($ids, $type);
            if ($demand_status) {
                $this->error_json('需求单状态只能是驳回待审核,可操作');
            }
            $temp = $this->be_dismissed_model->get_audit_failed($ids, $reject_remark);
           if ($temp['bool']) {
                $this->success_json([],null,$temp['msg']);
            } else {
               $this->error_json($temp['msg']);
            }
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 审核通过
     * /purchase_suggest/puerchase_examine/audit_pass
     * @author harvin  <2019-1-4>
     * */
    public function audit_pass() {
        try {
            //请求的参数
            $id = $this->input->post_get('ids'); //获取勾选的数据
            $reject_remark = $this->input->post_get('reject_remark'); //驳回原因
            if (empty($id)) {
                $this->error_json('请勾选数据');
            }
            $ids = explode(',', $id);
            //判断需求单的状态 2.驳回待审核
            $type = ['2'];
            $demand_status = $this->be_dismissed_model->get_demand_status($ids, $type);
            if ($demand_status) {
                $this->error_json('需求单状态只能是驳回待审核,可操作');
            }
            //调用第三方api
            $res = $this->be_dismissed_model->get_demand_number($ids);
            $data = [
                'demand_number' => implode(',', $res),
                'reject_remark' => !empty($reject_remark)?$reject_remark:'采购需求驳回', //反馈信息
                'operation_time' => date('Y-m-d H:i:s')
            ];
            //更新数据
            $temp = $this->be_dismissed_model->get_audit_pass($ids, $reject_remark);
            if ($temp['bool']) {
                $this->success_json([],null,$temp['msg']);
            } else {
                $this->error_json($temp['msg']);
            }
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

}
