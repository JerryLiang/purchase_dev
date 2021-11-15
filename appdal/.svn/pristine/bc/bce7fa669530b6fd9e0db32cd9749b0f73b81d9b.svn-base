<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/11/04
 * Time: 16:51
 */
class Baofoo_fopay_api extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper('status_product');
        $this->load->model('finance/Baofoo_fopay_model');
    }

    /**
     * 宝付获取 付款回执信息
     * /baofoo_fopay_api/get_pay_baofoo_voucher?pur_tran_num=100000859_7313510
     */
    public function get_pay_baofoo_voucher(){
        set_time_limit(0);
        $pur_tran_num = $this->input->get_post('pur_tran_num');
        $debug = $this->input->get_post('debug');
        $return = $this->Baofoo_fopay_model->pay_baofoo_voucher($pur_tran_num,$debug);// 100000859_7313510
        if($debug){
            print_r($return);exit;
        }else{
            $this->success_json($return);
        }
    }

    /**
     * 代付交易状态查证接口
     */
    public function get_pay_baofoo_voucher_status(){
        $pur_tran_num  = $this->input->get_post('pur_tran_num');
        $debug = $this->input->get_post('debug');
        $return = $this->Baofoo_fopay_model->pay_baofoo_voucher_status($pur_tran_num,$debug);
        if($debug){
            pr($return);exit;
        }else{
            $this->success_json($return);
        }
    }
}