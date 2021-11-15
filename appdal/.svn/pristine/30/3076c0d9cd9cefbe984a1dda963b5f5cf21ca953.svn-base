<?php
/**
 * User: yefanli
 * Date: 2020/12/2
 * 入库后退货管理
 */
class Purchase_return_goods extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Purchase_return_goods_model', "return_goods");
    }

    /**
     * 验证退货sku,并返回可退货的sku数据
     */
    public function verify_return_sku()
    {
        $sku = $this->input->get_post('sku');
        if(empty($sku))$this->error_json("参数不能为空");

        $data = $this->return_goods->verify_return_sku($sku);
        if($data && !empty($data))$this->success_json($data);
        $this->error_json("SKU：{$sku} 验证不成功，申请失败。");
    }

    /**
     * 提交入库退货申请
     */
    public function save_return_data_submit()
    {
        $sku_list = $this->input->get_post('sku_list');
        if(empty($sku_list) || !is_array($sku_list))$this->error_json('参数错误!');
        $list = [];
        $warehouse = ['HM_AA', 'SZ_AA', 'CX'];
        $cause = [1, 2, 3];
        $err_list =  $err_warehouse =  $err_cause =  $err_number = [];
        foreach ($sku_list as $val){
            if(!is_array($val))$val = json_decode($val, true);
            $is_fail = false;
            if(!isset($val['sku']) || empty($val['sku'])){
                $err_list[] = "sku参数缺失";
                continue;
            }
            if(!isset($val['warehouse_code']) || !in_array($val['warehouse_code'], $warehouse)){
                $err_warehouse[] = $val['sku'];
                $is_fail = true;
            }
            if(!isset($val['cause']) || !in_array($val['cause'], $cause)){
                $err_cause[] = $val['sku'];
                $is_fail = true;
            }
            if(!isset($val['number']) || !is_numeric($val['number']) || $val['number'] <= 0){
                $err_number[] = $val['sku'];
                $is_fail = true;
            }
            if(isset($val['cause']) && $val['cause'] == 3)$val['cause'] = 5;
            if(!$is_fail)$list[] = $val;
        }
        if(count($err_warehouse) > 0)$err_list[] = implode(',', $err_warehouse)."，退货仓库只能为：小包仓_虎门、小包仓_塘厦、小包仓_慈溪";
        if(count($err_cause) > 0)$err_list[] = implode(',', $err_cause)."，退货原因只能为：滞销，库内异常退货";
        if(count($err_number) > 0)$err_list[] = implode(',', $err_number)."，退货数量不能小于0";
        if(count($err_list) > 0)$this->error_json(implode('。', $err_list));
        if(count($list) == 0)$this->error_json('入库后退货申请失败，提交的SKU不满足申请条件。');

        $data = $this->return_goods->save_return_data_submit($list);
        if(isset($data['code']) && $data['code'] == 1)$this->success_json("申请成功");
        if(isset($data['code']) && $data['code'] != 1 && isset($data['msg']))$this->error_json($data['msg']);
        $this->error_json('入库后退货申请失败。');
    }


}