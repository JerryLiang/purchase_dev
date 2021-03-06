<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Product_similar_api extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('product/Product_similar_model');
    }

    /**
     * 计划任务——根据货源变更记录推送同款货源推荐列表数据
     * 计划任务——同步同款货源数据到门户系统
     * Product_similar_api/push_similar_to_smc
     */
    public function push_similar_to_smc(){
        echo "开始执行计划任务<br/>\n";


        echo "执行同款货源-->数据生成逻辑<br/>\n";
        $similar_config = $this->Product_similar_model->get_similar_config();

        $execute_times = 0;
        if(!empty($similar_config)){
            foreach($similar_config as $item_config){
                if($item_config['is_enable'] == 2) continue;
                $execute_times ++;

                $sales_type         = $item_config['sales_type'];
                $days_sales_start   = $item_config['days_sales_start'];
                $days_sales_end     = $item_config['days_sales_end'];
                $priority           = $item_config['priority'];
                $create_time        = $item_config['create_time'];

                $this->Product_similar_model->plan_create_similar_record($sales_type,$days_sales_start,$days_sales_end,$priority,$create_time);


                echo "任务：".$sales_type .' --> '.$days_sales_start.' ~ '.$days_sales_end.' --> '.$create_time."<br/>\n";
            }
        }

        if($execute_times == 0) echo "同款货源-->数据生成逻辑->配置项：缺失 或 未启用<br/>\n";
        echo "执行同款货源-->数据生成逻辑-->结束<br/>\n";


        echo "同款货源-->数据开始执行同步门户系统<br/>\n";
        $this->Product_similar_model->push_similar_to_smc();
        echo "同款货源-->数据开始执行同步门户系统-->结束<br/>\n";

        echo "计划任务执行完毕";
        exit;
    }

    /**
     * 接口——接收门户系统推送的同款货源数据
     * Product_similar_api/receive_same_product_detail
     */
    public function receive_same_product_detail(){
        $sameJson = file_get_contents('php://input');

        if(!is_json($sameJson)){
            $this->error_json('参数不是JSON格式');
        }

        $sameJson = json_decode($sameJson,true);
        if(empty($sameJson) or !is_array($sameJson) or !isset($sameJson['items']) or empty($sameJson['items'])){
            $this->error_json('参数数据为空,items缺失');
        }

        $success_list = $error_list = null;
        foreach($sameJson['items'] as $item_value){
            if(!isset($item_value['same_referral_code']) or empty($item_value['same_referral_code'])){
                $error_list[] = 'same_referral_code 字段缺失';
                continue;
            }

            if(!isset($item_value['apply_number']) or empty($item_value['apply_number'])){
                $error_list[$item_value['same_referral_code']] = '中的 apply_number 字段缺失';
                continue;
            }

            $supplier_name = isset($item_value['supplier_name'])?$item_value['supplier_name']:PURCHASE_NUMBER_ZFSTATUS;
            $submit_time = isset($item_value['submit_time'])?$item_value['submit_time']:date('Y-m-d H:i:s');

            $same_referral_code = $item_value['same_referral_code'];
            $apply_number = $item_value['apply_number'];


            $similar_info = $this->Product_similar_model->get_similar_detail($apply_number);
            if(empty($similar_info)){
                $error_list[$same_referral_code] = '对应的记录不存在';
                continue;
            }

            if($similar_info['status'] != 10 and $similar_info['status'] != 35){
                $error_list[$same_referral_code] = '记录不是待提交或审核驳回状态，不允许重新提交';
                continue;
            }

            $update_arr = [];
            isset($item_value['same_referral_code']) and $update_arr['smc_similar_code'] = $item_value['same_referral_code'];
            isset($item_value['product_link']) and $update_arr['smc_product_link'] = $item_value['product_link'];
            isset($item_value['supplier_name']) and $update_arr['smc_supplier_name'] = $item_value['supplier_name'];
            isset($item_value['supplier_code']) and $update_arr['smc_supplier_code'] = $item_value['supplier_code'];
            isset($item_value['product_name']) and $update_arr['smc_product_name'] = $item_value['product_name'];
            isset($item_value['dev_image']) and $update_arr['smc_dev_image'] = $item_value['dev_image'];
            isset($item_value['goods_params']) and $update_arr['smc_goods_params'] = $item_value['goods_params'];
            isset($item_value['product_cost']) and $update_arr['smc_product_cost'] = $item_value['product_cost'];
            isset($item_value['product_length']) and $update_arr['smc_product_length'] = $item_value['product_length'];
            isset($item_value['product_height']) and $update_arr['smc_product_height'] = $item_value['product_height'];
            isset($item_value['product_width']) and $update_arr['smc_product_width'] = $item_value['product_width'];
            isset($item_value['rought_weight']) and $update_arr['smc_rought_weight'] = $item_value['rought_weight'];
            isset($item_value['product_brand']) and $update_arr['smc_product_brand'] = $item_value['product_brand'];
            isset($item_value['product_model']) and $update_arr['smc_product_model'] = $item_value['product_model'];
            isset($item_value['sample_type']) and $update_arr['smc_sample_type'] = $item_value['sample_type'];
            isset($item_value['product_material']) and $update_arr['smc_product_material'] = $item_value['product_material'];
            isset($item_value['pack_list']) and $update_arr['smc_pack_list'] = $item_value['pack_list'];
            isset($item_value['remark']) and $update_arr['smc_remark'] = $item_value['remark'];
            isset($item_value['submit_time']) and $update_arr['smc_submit_time'] = $item_value['submit_time'];

            if(empty($update_arr)){
                $error_list[$same_referral_code] = '记录数据均不是允许更新的数据';
                continue;
            }
            if($similar_info['status'] == 10){// 10.创建
                $new_status = 20;// 待分配
            }else{
                $new_status = 30;// 待审核
            }

            $result = $this->Product_similar_model->save_smc_same_product_detail($apply_number,$new_status,$update_arr);

            $this->Product_similar_model->add_similar_log($apply_number,'供应商提交同款产品信息','供应商提交同款',0,$supplier_name,$submit_time);
            if($result['code']){
                $success_list[$same_referral_code] = true;
            }else{
                $error_list[$same_referral_code] = $result['message'];
            }
        }

        $result_list = ['success_list' => $success_list,'error_list' => $error_list];
        $this->success_json($result_list);
    }

}
