<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * 1688 订单操作控制器
 * User: Jolon
 * Date: 2019/08/30 10:00
 */
class Ali_product_api extends MY_API_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->library('alibaba/AliProductApi');
        $this->load->model('ali/Ali_product_model');
    }

    /**
     * 计划任务 自动刷新 1688产品信息
     * @url http://pms.yibainetwork.com:81/ali_product_api/auto_update_ali_product?last_date=2019-09-10  更新时间小于指定日期
     */
    public function auto_update_ali_product(){
        $last_date  = $this->input->get_post('last_date',date('Y-m-d',strtotime('-1 days')));//1688订单号
        $sku        = $this->input->get_post('sku');//1688订单号
        $this->Ali_product_model->autoUpdateAliProduct($last_date,$sku);
        echo 'Success';exit;
    }

    /**
     * 计划任务 刷新 供应商名称、供应商ID不一致的问题
     * @url http://pms.yibainetwork.com:81/ali_product_api/verify_supplier_equal?sku=DEADDAD
     */
    public function verify_supplier_equal(){
        $sku        = $this->input->get_post('sku');
        $result     = $this->Ali_product_model->verify_supplier_equal($sku);
        if($result){
            echo 'Success';exit;
        }else{
            echo 'Failed';exit;
        }
    }

}