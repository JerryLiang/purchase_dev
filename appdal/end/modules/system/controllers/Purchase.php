<?php

/**
 * 采购单今日任务显示
 * Class Purchase
 */
class Purchase extends MY_Controller
{
    public function __construct(){
        parent::__construct();
        $this->load->model('system/purchase_system_model');
    }

    /**
     * 获取列表
     */
    public function get_list()
    {
        $id = $this->input->get_post('id');
        $data = $this->purchase_system_model->get_list($id);

        if($data && count($data) > 0)$this->success_json(["values" => $data], [], '获取成功！');
        $this->success_json(["values" => []], [], '暂无数据');
    }

    /**
     * 编辑/新增数据
     */
    public function save_edit_setting()
    {
        $params = $this->input->get_post('data');
        try{
            $params = json_decode($params, true);
            if(!is_array($params) || count($params) == 0)$this->error_json("提交的数据不能为空！");
        }catch (Exception $e){}

        $x = 1;
        foreach ($params as $val){
            $row = "第 {$x} 行";
            if(!SetAndNotEmpty($val, 'suggest_status'))$this->error_json($row."备货单状态不能为空！");
            if(!SetAndNotEmpty($val, 'track_log'))$this->error_json($row."物流轨迹是否为空缺失");
            if(!SetAndNotEmpty($val, 'add_remark'))$this->error_json($row."是否添加备注缺失");
            if(!SetAndNotEmpty($val, 'examine_tips') || !SetAndNotEmpty($val, 'examine_date'))$this->error_json($row."审核天数缺失");
            $x ++;
        }
        $data = $this->purchase_system_model->save_edit_setting($params);
        if($data['code'] == 1){
            $this->success_json([], [], '新增/修改成功！');
        }elseif(isset($data['msg'])){
            $this->error_json($data['msg']);
        }
        $this->error_json('新增/修改失败！');
    }

    /**
     * 启用/禁用数据
     */
    public function on_off_setting()
    {
        $id = $this->input->get_post('id');
        $status = $this->input->get_post('status');
        if(empty($id) || !is_numeric($status) || !in_array($status, [0, 1]))$this->error_json('提交参数错误！');

        $data = $this->purchase_system_model->on_off_setting($id, $status);
        if($data['code'] == 1){
            $this->success_json([], [], '修改成功！');
        }elseif(isset($data['msg'])){
            $this->error_json($data['msg']);
        }
        $this->error_json('修改失败！');
    }
}