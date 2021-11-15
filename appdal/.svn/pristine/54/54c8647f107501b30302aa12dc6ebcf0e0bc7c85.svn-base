<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 驳回控制器
 * 
 */

class Be_dismissed extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('be_dismissed_model');
    }

    /**
     * 获取选择原因
     * /purchase_suggest/be_dismissed/get_dismissed_reason
     * @author harvin 2019-1-4 
     **/
    public function get_dismissed_reason() {
        $dismissed = $this->be_dismissed_model->get_dismissed();

        $this->success_json($dismissed,null, '操作成功');
    }

    /**
     * 驳回操作
     * /purchase_suggest/be_dismissed/get_dismissed_operation
     * @author harvin 2019-1-4
     **/

    public function get_dismissed_operation() {
        //获取请求的参数
        $id = $this->input->post_get('ids'); //获取勾选的数据
        $reject_remark = $this->input->post_get('reject_remark'); //驳回备注
        $reiect_dismissed = $this->input->post_get('reiect_dismissed'); //驳回原因 
        if (empty($id)) {
            $this->error_json('请勾选数据');
        }
        if (empty($reject_remark)) {
            $this->error_json('请填写驳回备注');
        }
        if (empty($reiect_dismissed)) {
            $this->error_json('请选择驳回原因');
        }
        //转化数组
        $ids = explode(',', $id);
        $bool = $this->be_dismissed_model->get_disnissed_statu($ids, $reject_remark, $reiect_dismissed);
        if ($bool) {
            $this->success_json([],null, '操作成功');
        } else {
            $this->error_json('操作失败');
        }
    }

}
