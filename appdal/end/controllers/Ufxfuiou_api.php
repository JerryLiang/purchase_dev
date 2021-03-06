
<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/11/04
 * Time: 16:51
 */
class Ufxfuiou_api extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper('status_product');
        $this->load->model('finance/Purchase_order_pay_ufxfuiou_detail_model');
    }

    /**
     * 富友获取 付款回执信息
     * /ufxfuiou_api/get_pay_ufxfuiou_voucher?pur_tran_num=20190708180340YIBAIfd175285d14
     */
    public function get_pay_ufxfuiou_voucher(){
        $pur_tran_num = $this->input->get_post('pur_tran_num');
        $debug = $this->input->get_post('debug');
        $return = $this->Purchase_order_pay_ufxfuiou_detail_model->pay_ufxfuiou_voucher($pur_tran_num,$debug);// 100000859_7313510
        if($debug){
            print_r($return);exit;
        }else{
            $this->success_json($return);
        }
    }


}