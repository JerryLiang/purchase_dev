<?php

/**
 * 采购单相关任务配置
 * Class Purchase_setting
 */
class Purchase_setting extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/purchase_setting_model');
    }

    /**
     * 获取中转仓限定修改公共仓列表
     */
    public function pertain_set_list()
    {
        $id = $this->input->get_post('id');
        $data = $this->purchase_setting_model->pertain_set_list($id);

        $this->load->model('purchase/purchase_order_extend_model');
        $logistics_type = [];
        $logistics = $this->purchase_order_extend_model->get_logistics_type();
        foreach ($logistics as &$val){
            $logistics_type[$val['key']] = $val['value'];
        }

        $warehouse = $pertain = [];
        $ware = $this->purchase_order_extend_model->get_warehouse();
        $pertain_temp = array_unique(array_column($ware, 'pertain_wms'));
        foreach ($ware as &$val){
            $w_code = $val['warehouse_code'];
            $w_name = $val['warehouse_name'];
            $warehouse[$w_code] = $w_name;
            if(!empty($w_code) && in_array($w_code, $pertain_temp))$pertain[$w_code] = $w_name;
        }

        $data_temp = [];
        foreach ($data as &$val){
            $val['logistics_type_cn'] = isset($logistics_type[$val['logistics_type']]) ? $logistics_type[$val['logistics_type']]: '';
            $val['is_fumigation_cn'] = in_array($val['is_fumigation'], [1,2]) ? ($val['is_fumigation'] == 1 ? "熏蒸": "不熏蒸") : "";
            $val['destination_warehouse_cn'] = isset($warehouse[$val['destination_warehouse']]) ? $warehouse[$val['destination_warehouse']]: '';
            $val['pertain_wms_cn'] = isset($pertain[$val['pertain_wms']]) ? $pertain[$val['pertain_wms']]: '';
            $data_temp[] = $val;
        }

        $res = [
            "values" => $data_temp,
            "purchase_type_id" => getPurchaseType(),
            "logistics_type" => $logistics_type,
            "is_fumigation" => [1=>"熏蒸",2=>"不熏蒸"],
            "destination_warehouse" => $warehouse,
            "pertain_wms" => $pertain,
        ];
        if($data && count($data) > 0)$this->success_json($res, [], '获取成功！');
        $this->success_json($res, [], '暂无数据');
    }

    /**
     * 编辑/新增中转仓限定修改公共仓数据
     */
    public function pertain_set_edit()
    {
        $params = $this->input->get_post('data');
        try{
            $params = json_decode($params, true);
            if(!is_array($params) || count($params) == 0)$this->error_json("提交的数据不能为空！");
        }catch (Exception $e){}

        $x = 1;
        foreach ($params as $val){
            $row = "第 {$x} 行";
            if(!SetAndNotEmpty($val, 'purchase_type_id'))$this->error_json($row."业务线不能为空！");
            if(!SetAndNotEmpty($val, 'pertain_wms'))$this->error_json($row."公共仓不能为空");
            $x ++;
        }
        $data = $this->purchase_setting_model->pertain_set_edit($params);
        if($data['code'] == 1){
            $this->success_json([], [], '新增/修改成功！');
        }elseif(isset($data['msg'])){
            $this->error_json($data['msg']);
        }
        $this->error_json('新增/修改失败！');
    }

    /**
     * 获取自动取消配置列表
     */
    public function cancel_auto_list()
    {
        $id = $this->input->get_post('id');
        $data = $this->purchase_setting_model->cancel_auto_list($id);

        $source = [1 => "合同", 2 => "网采"];
        $track_status = [1 => "为空", 2 => "不为空"];
        $long_delivery = [1 => "是", 2 => "不是"];

        $data_temp = [];
        foreach ($data as &$val){
            $val['source_cn'] = $val['source'] == 1 ? "合同": "网采";
            $val['track_status_cn'] = $val['track_status'] == 1 ? "为空": "不为空";
            $val['long_delivery_cn'] = $val['long_delivery'] == 1 ? "是": "不是";
            $val['purchase_order_status_cn'] = getPurchaseStatus($val['purchase_order_status']);
            $val['suggest_order_status_cn'] = getPurchaseStatus($val['suggest_order_status']);
            $data_temp[] = $val;
        }

        $res = [
            "values" => $data_temp,
            "source" => $source,
            "track_status" => $track_status,
            "long_delivery" => $long_delivery,
            "purchase_order_status" => getPurchaseStatus(),
            "suggest_order_status" => getPurchaseStatus(),
        ];
        if($data && count($data) > 0)$this->success_json($res, [], '获取成功！');
        $this->success_json($res, [], '暂无数据');
    }

    /**
     * 编辑/新增自动取消配置
     */
    public function cancel_auto_edit()
    {
        $params = $this->input->get_post('data');
        try{
            $params = json_decode($params, true);
            if(!is_array($params) || count($params) == 0)$this->error_json("提交的数据不能为空！");
        }catch (Exception $e){}

        $x = 1;
        foreach ($params as $val){
            $row = "第 {$x} 行";
            if(!SetAndNotEmpty($val, 'source', 'n'))$this->error_json($row."采购来源不能为空！");
            if(!SetAndNotEmpty($val, 'track_status', 'n'))$this->error_json($row."轨迹状态不能为空！");
            if(!SetAndNotEmpty($val, 'long_delivery', 'n'))$this->error_json($row."是否超长交期不能为空！");
            $x ++;
        }
        $data = $this->purchase_setting_model->cancel_auto_edit($params);
        if($data['code'] == 1){
            $this->success_json([], [], '新增/修改成功！');
        }elseif(isset($data['msg'])){
            $this->error_json($data['msg']);
        }
        $this->error_json('新增/修改失败！');
    }

    /**
     * 应付款时间配置列表
     */
    public function need_pay_time_list()
    {
        $id = $this->input->get_post('id');
        $data = $this->purchase_setting_model->need_pay_time_list($id);

        $res = [
            "values" => $data,
            "source" => [1 => "合同", 2 => "网采"],
            "deposit" => [1 => "未付", 2 => "已付"],
            "pay_status" => getPayStatus(),
            "settlement" => $this->purchase_setting_model->get_settlement_list(),
        ];
        if($data && count($data) > 0)$this->success_json($res, [], '获取成功！');
        $this->success_json($res, [], '暂无数据');
    }

    /**
     * 编辑/新增应付款时间配置
     */
    public function need_pay_time_edit()
    {
        $params = $this->input->get_post('data');
        try{
            $params = json_decode($params, true);
            if(!is_array($params) || count($params) == 0)$this->error_json("提交的数据不能为空！");
        }catch (Exception $e){}

        $x = 1;
        foreach ($params as $val){
            $row = "第 {$x} 行";
            if(!SetAndNotEmpty($val, 'settlement', 'n'))$this->error_json($row."结算方式不能为空！");
            if(!SetAndNotEmpty($val, 'query', 'ar') || count($val['query']) < 1)$this->error_json($row."至少要有一个条件！");
            $x ++;
        }
        $data = $this->purchase_setting_model->need_pay_time_edit($params);
        if($data['code'] == 1){
            $this->success_json([], [], '新增/修改成功！');
        }elseif(isset($data['msg'])){
            $this->error_json($data['msg']);
        }
        $this->error_json('新增/修改失败！');
    }

    /**
     * 自动对账配置
     */
    public function record_auto_list()
    {
        $id = $this->input->get_post('id');
        $data = $this->purchase_setting_model->record_auto_list($id);

        $res = [
            "values" => $data,
            "pay_status" => getPayStatus(),
            "settlement" => $this->purchase_setting_model->get_settlement_list(),
        ];
        if($data && count($data) > 0)$this->success_json($res, [], '获取成功！');
        $this->success_json($res, [], '暂无数据');
    }

    /**
     * 自动对账配置
     */
    public function record_auto_edit()
    {
        $params = $this->input->get_post('data');
        try{
            $params = json_decode($params, true);
            if(!is_array($params) || count($params) == 0)$this->error_json("提交的数据不能为空！");
        }catch (Exception $e){}

        $x = 1;
        foreach ($params as $val){
            $row = "第 {$x} 行";
            if(!SetAndNotEmpty($val, 'settlement', 'n'))$this->error_json($row."结算方式不能为空！");
            if(!SetAndNotEmpty($val, 'query', 'ar') || count($val['query']) < 1)$this->error_json($row."至少要有一个条件！");
            $x ++;
        }
        $data = $this->purchase_setting_model->record_auto_edit($params);
        if($data['code'] == 1){
            $this->success_json([], [], '新增/修改成功！');
        }elseif(isset($data['msg'])){
            $this->error_json($data['msg']);
        }
        $this->error_json('新增/修改失败！');
    }
}