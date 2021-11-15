<?php

/**
 * 验货控制器
 * User: yefanli
 */
class Supplier_check extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier_check_model');
        $this->load->helper('common');
    }

    /**
     * sku退款率列表
     */
    public function get_list()
    {
        $params = $this->get_params();
        $baseData = $this->supplier_check_model->get_list($params);

        if(SetAndNotEmpty($baseData, 'value')){
            $this->success_json($baseData, [], "获取数据成功");
        }
        $this->success_json(["value"=>[]], [], "暂无数据");
    }

    /**
     * 列表公共数据
     */
    private function get_params()
    {
        return [
            "supplier_code"     => $this->input->get_post("supplier_code"),
            "id"                => $this->input->get_post("id"),
            "sku"               => $this->input->get_post("sku"),
            "title"             => $this->input->get_post("title"),
            "min_refund_rate"   => $this->input->get_post("min_refund_rate"),
            "max_refund_rate"   => $this->input->get_post("max_refund_rate"),
            "page"              => $this->input->get_post("offset"),
            "limit"             => $this->input->get_post("limit"),
            "export"            => $this->input->get_post("export"),
        ];
    }

    /**
     * sku退款率导出
     */
    public function export_list()
    {
        $params = $this->get_params();
        $sum = $this->supplier_check_model->get_list($params, true);
//        echo $sum;exit;
        $sum = isset($sum['page_data']['count']) && !empty($sum['page_data']['count']) ? $sum['page_data']['count'] : 0;
        if(!$sum && $sum == 0) $this->error_json("没有要导出的数据！");
        if($sum > 50000) $this->error_json("单次导出最大50000条！");

        set_time_limit(0);
        ini_set('memory_limit', '1000M');
        $this->load->helper('export_csv');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');

        $template_file = 'product_refund_rate-'.date('YmdHis').rand(1000, 9999).'.csv';

        //前端路径
        $product_file = get_export_path().$template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $fp = fopen($product_file, "a");
        $heads = ['sku','产品名称','供应商CODE','供应商名称','图片','退款率','最近一次修改'];

        foreach($heads as $key => $item) {
            $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);

        $page_limit = 1000;
        $sum_page = ($sum <= $page_limit) ? 1: ceil($sum/$page_limit);

        if($sum_page>=1) {
            for ($i = 1; $i <= $sum_page; $i++) {
                $params['page'] = $i;
                $params['limit'] = $page_limit;
                $export_info = $this->supplier_check_model->get_list($params, false, true);
                $export = isset($export_info['value']) && !empty($export_info['value']) ? $export_info['value'] : [];
                if(!$export || count($export) == 0) break;

                foreach ($export as $value) {
                    $row = [];
                    $row['sku']                   = iconv("UTF-8", "GBK//IGNORE", $value['sku']."\t");
                    $row['product_name']          = iconv("UTF-8", "GBK//IGNORE", $value['product_name']."\t");
                    $row['supplier_code']         = iconv("UTF-8", "GBK//IGNORE", $value['supplier_code']."\t");
                    $row['supplier_name']         = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']."\t");
                    $row['images']                = erp_sku_img_sku_thumbnail($value['product_thumb_url'])."\t";
//                    $row['images']                = iconv("UTF-8", "GBK//IGNORE", $value['product_thumb_url']."\t");
                    $row['refund_rate']           = iconv("UTF-8", "GBK//IGNORE", $value['refund_rate']."\t");
                    $row['last_change']           = iconv("UTF-8", "GBK//IGNORE", $value['last_change_info']."\t");
                    fputcsv($fp, $row);
                }
                //每1万条数据就刷新缓冲区
                ob_flush();
                flush();
            }
        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=get_export_path_replace_host(get_export_path(),$down_host).$template_file;
        $this->success_json($down_file_url);
    }

    /**
     * 导入SKU退款率
     */
    public function refund_rate_import()
    {
        set_time_limit(0);
        ini_set('memory_limit','2048M');
        $data = $this->input->get_post("data");
        if(!is_array($data) || count($data) == 0)$this->error_json('提交参数不能为空');
        $c_data = count($data);

        $res = $this->supplier_check_model->refund_rate_import($data);

        if($res['code'] == 1)$this->success_json([], ["count" => $c_data], "导入成功");
        $this->error_json("导入失败");
    }

    /**
     * 退款率编辑
     * sku[sku001] = 1
     */
    public function refund_rate_edit()
    {
        $sku = $this->input->get_post("sku");
        if(!is_array($sku) || count($sku) == 0)$this->error_json('提交参数不能为空');

        $res = $this->supplier_check_model->refund_rate_edit($sku);

        if($res['code'] == 1)$this->success_json([], [], "编辑成功");
        $this->error_json("编辑失败");
    }

    /**
     * 根据备货单获取相应的验货信息
     * /supplier/supplier_check/get_order_by_suggest
     */
    public function get_order_by_suggest()
    {
        $suggest = $this->input->get_post("suggest_number");
        if(empty($suggest) || !is_string($suggest))$this->error_json('提交参数不能为空!');
        $suggest = explode(" ", $suggest);

        $demand = $this->supplier_check_model->check_suggest_status($suggest);
        if(count($demand) > 0){
            $this->error_json("获取失败备货单：".implode(',', $demand)."，状态不是等待到货、部分到货等待剩余，不能申请验证");
        }

        $res = $this->supplier_check_model->get_order_by_suggest($suggest);

        if($res['code'] == 1 && count($res['data']) > 0){
            $this->success_json($res['data'], $this->get_select_data(), "获取成功");
        }
        $this->error_json("获取失败");
    }

    /**
     * 下拉选项
     */
    private function get_select_data()
    {
        return [
            "is_urgent"         => ["1" => "是", "0" => "否"],
            "check_suggest"     => [1 => "验货", 2 => "免检"],
            "order_type"        => [1 => "常规", 2 => "首次", 3 => "直发", 4 => "客诉"],
        ];
    }

    /**
     * 新增验货申请
     */
    public function create_check_save()
    {
        $data = $this->input->get_post("data");
        if(empty($data))$this->error_json('提交参数不能为空！');
        try{
            $data = json_decode($data, true);
        }catch (Exception $e){}
        if(!is_array($data) || count($data) == 0)$this->error_json('提交的参数错误！');

        $temp_data = [];
        $error = [];

        $demand_list = array_column($data, 'demand_number');
        $demand_check = $this->supplier_check_model->check_suggest_status($demand_list);
        if(count($demand_check) > 0){
            $res['msg'] = '备货单号 '.implode(",", $demand_check)."的备货单状态不是等待到货,不允许提交!";
            return $res;
        }

        foreach ($data as $val){
            $pur = $val['purchase_number']??false;
            $demand = $val['demand_number']??false;
            $sku = $val['sku']??false;
            $check_num = $val['check_num']??false;

            if(!$pur || !$demand || !$sku || !$check_num){
                $error[] = "备货单、采购单、SKU、验货数量不能为空！";
                break;
            }

            if(!SetAndNotEmpty($val, 'contacts') ||
                !SetAndNotEmpty($val, 'phone') ||
                !SetAndNotEmpty($val, 'province') ||
                !SetAndNotEmpty($val, 'city') ||
                !SetAndNotEmpty($val, 'area') ||
                !SetAndNotEmpty($val, 'address')){
                $error[] = $demand." 联系人、联系方式、详细地址不能为空。";
                break;
            }

            if(!SetAndNotEmpty($val, 'is_urgent', 'n') ||
                !SetAndNotEmpty($val, 'check_suggest', 'n') ||
                !SetAndNotEmpty($val, 'expect') ||
                !SetAndNotEmpty($val, 'order_type', 'n')){
                $error[] = $demand." 是否加急、是否验货、期望验货时间、验货类型不能为空。";
                break;
            }

            if(!isset($temp_data[$pur]))$temp_data[$pur] = [];
            $val['handle_type'] = "manually";
            $temp_data[$pur][] = $val;
        }

        if(count($error) > 0)$this->error_json(json_encode($error));

        if(!empty($temp_data)){
            $res = $this->supplier_check_model->create_check_save($temp_data);
            if($res['code'] == 1){
                $this->success_json([], [], $res['msg']);
                $this->success_json([], [], "申请提交成功");
            }elseif ($res['msg']){
                $this->error_json($res['msg']);
            }
        }

        $this->error_json("提交失败");
    }

    /**
     * 获取批量确认数据
     */
    public function get_check_confirm()
    {
        $id = $this->input->get_post("id");
        if(!is_array($id) || count($id) == 0)$this->error_json('提交参数不能为空！');

        $res = $this->supplier_check_model->get_check_confirm([], $id);

        if($res['code'] == 1 && count($res['data']) > 0){
            $suggest_temp = array_column($res['data'], 'demand_number');
            if(count($suggest_temp) > 0){
                $demand = $this->supplier_check_model->check_suggest_status($suggest_temp);
                if(count($demand) > 0){
                    $this->error_json("获取失败备货单：".implode(',', $demand)."，状态不是等待到货、部分到货等待剩余，不能申请验证");
                }
            }
            $this->success_json($res['data'], $this->get_select_data(), "获取成功");
        }
        $this->error_json("获取失败");
    }

    /**
     * 新增/保存批量确认数据
     */
    public function save_check_confirm()
    {
        $data = $this->input->get_post("data");
        if(empty($data))$this->error_json('提交参数不能为空！');
        try{
            $data = json_decode($data, true);
        }catch (Exception $e){}
        if(!is_array($data) || count($data) == 0)$this->error_json('提交的参数错误！');

        $temp_data = [];
        $error = [];
        foreach ($data as $val){
            $id = $val['check_id']??false;
            $pur = $val['purchase_number']??false;
            $demand = $val['demand_number']??false;
            $sku = $val['sku']??false;
            $check_num = $val['check_num']??false;

            if(!$pur || !$id || !$sku || !$check_num){
                $error[] = "验货ID、采购单、SKU、验货数量不能为空！";
                break;
            }

            if(!SetAndNotEmpty($val, 'contacts') ||
                !SetAndNotEmpty($val, 'phone') ||
                !SetAndNotEmpty($val, 'province') ||
                !SetAndNotEmpty($val, 'city') ||
                !SetAndNotEmpty($val, 'area') ||
                !SetAndNotEmpty($val, 'address')){
                $error[] = $demand." 联系人、联系方式、详细地址不能为空。";
                break;
            }

            if(!SetAndNotEmpty($val, 'is_urgent', 'n') ||
                !SetAndNotEmpty($val, 'check_suggest', 'n') ||
                !SetAndNotEmpty($val, 'expect') ||
                !SetAndNotEmpty($val, 'order_type', 'n')){
                $error[] = $demand." 是否加急、是否验货、期望验货时间、验货类型不能为空。";
                break;
            }

            if(!isset($temp_data[$pur]))$temp_data[$pur] = [];
            $val['handle_type'] = "manually";
            $temp_data[$pur][] = $val;
        }

        if(count($error) > 0)$this->error_json(implode(',', $error));

        if(!empty($temp_data)){
            $res = $this->supplier_check_model->save_check_confirm($temp_data);
            if($res['code'] == 1){
                $this->success_json([], [], "申请提交成功");
            }elseif ($res['msg']){
                $this->error_json($res['msg']);
            }
        }

        $this->error_json("提交失败");
    }

}