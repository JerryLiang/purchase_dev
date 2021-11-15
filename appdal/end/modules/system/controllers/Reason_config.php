<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Reason_config extends MY_Controller
{
   public function __construct(){
        parent::__construct();
        $this->load->model('Reason_config_model');
    }
    /**
     * 原因配置列表
     * @author jeff
     * @date 2019-10-08
     */
    public function reason_type_list(){
//        $params     = gp();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0)
            $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $result= $this->Reason_config_model->reason_type_list($offsets, $limit, $page);
        $this->success_json(['key' => $result['key'],'values' => $result['values']],$result['page_data']);

    }
    /**
     * 原因列表
     * @author harvin
     * @date 2019-06-26
     */
    public function cancel_reason_list(){
        /*$reason_type=$this->input->get_post('reason_type');

        if(empty($reason_type)){
            $this->error_json('原因类型缺失');
        }*/
        $params     = gp();
        $result= $this->Reason_config_model->get_cancel_reason_list($params);
        $this->success_json(['key' => $result['key'],'values' => $result['values']]);
    }

    
    /**
     * 修改原因
     * @author harvin、
     * @date 2019-06-26
     */
    public function reason_edit(){
       $id=$this->input->get_post('id');
       $reason_name=$this->input->get_post('reason_name');
       if(empty($id) || !is_numeric($id)){
           $this->error_json('请求参数错误');
       }
       if (empty($reason_name)) $this->error_json('请输入原因名称');
       try {
        $this->Reason_config_model->reason_edit($id,$reason_name);
        $this->success_json([],null,'编辑成功');
       } catch (Exception $exc) {
           $this->error_json($exc->getMessage());
       }

    }

    /**
     * 修改原因启用状态
     * @author harvin、
     * @date 2019-06-26
     */
    public function reason_status_change(){
        $id=$this->input->get_post('id');
        $status=$this->input->get_post('status');
        if(empty($id) || !is_numeric($id)){
            $this->error_json('请求参数错误');
        }
        if (empty($status)||!in_array($status,[1,2])) $this->error_json('启用状态错误');
        try {
            $this->Reason_config_model->reason_status_change($id, $status);
            $this->success_json([],null,'修改成功');
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }

    }

    //添加原因
    public function reason_add()
    {
        $reason_type = $this->input->get_post('reason_type');//原因类型 1.作废原因
        $reason_name = $this->input->get_post('reason_name');//原因名称

        if(empty($reason_type)) $this->error_json('原因类型缺失');
        if(empty($reason_name)) $this->error_json('原因名称缺失');

        try {
            $this->Reason_config_model->reason_add($reason_type, $reason_name);
            $this->success_json([],null,'添加成功');
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }

    }

    /**
     * @desc 原因排序
     * @author Jeff
     * @Date 2019/10/10 10:03
     * @return
     */
    public function reason_sort()
    {
        $sort_type = $this->input->get_post('sort_type');//排序类型 1 上移, 2 下移
        $id=$this->input->get_post('id');

        if(empty($sort_type) || !in_array($sort_type,[1,2])) $this->error_json('排序类型错误');
        if(empty($id)) $this->error_json('id缺失');

        $res = $this->Reason_config_model->reason_sort($sort_type, $id);
        if ($res['code']){
            $this->success_json([],null,'排序成功');
        }else{
            $this->error_json($res['msg']);
        }
    }

    /**
     * @desc 编辑原因统一提交(包括新增)
     * @author Jeff
     * @Date 2019/10/10 10:03
     * @return
     */
    public function reason_edit_submit()
    {
        $edit_data = $this->input->get_post('edit_data');//编辑数据
        $add_data = $this->input->get_post('add_data');//新增数据

        if (empty($edit_data)) $this->error_json('参数错误');

        $res = $this->Reason_config_model->reason_edit_submit($edit_data, $add_data);
        if ($res['code']){
            $this->success_json([],null,'编辑成功');
        }else{
            $this->error_json($res['msg']);
        }
    }
}    
