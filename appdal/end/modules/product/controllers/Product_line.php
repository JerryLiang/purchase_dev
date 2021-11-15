<?php
/**
 * Created by PhpStorm.
 * 产品线控制器
 * User: Jolon
 * Date: 2019/01/18 0027 11:17
 */

class Product_line extends MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('product_model');
        $this->load->model('product_line_model');
    }

    /**
     * 查询 产品线列表
     * @author Jolon
     */
    public function get_product_line_list(){
        $linelist_parent_id = $this->input->get_post('is_top_product_line');
        $product_line_name  = $this->input->get_post('product_line_name');
        $linelist_parent_id = ($linelist_parent_id == 1)?0:null;

        $product_line_list  = $this->product_line_model->get_product_line_list($linelist_parent_id,$product_line_name);

        $product_line_list = array_column($product_line_list,'linelist_cn_name','product_line_id');
        $this->success_json(['value' => $product_line_list]);
    }

    /**
     * 获取 指定的产品线信息
     * @author Jolon
     */
    public function get_product_line_one(){
        $product_line_id = $this->input->get_post('product_line_id');
        $product_line_info  = $this->product_line_model->get_product_line_one($product_line_id);

        if($product_line_info){
            $this->success_json(['value' => $product_line_info]);
        }else{
            $this->error_json('未到找目标数据');
        }

    }


    /**
     * 获取 指定的产品线的名称
     * @author Jolon
     */
    public function get_product_line_name(){
        $product_line_id = $this->input->get_post('product_line_id');
        $product_line_name  = $this->product_line_model->get_product_line_name($product_line_id);

        if($product_line_name){
            $this->success_json(['value' => $product_line_name]);
        }else{
            $this->error_json('未到找目标数据');
        }
    }

}