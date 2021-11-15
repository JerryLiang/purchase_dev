<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * 自动取消相关
 */
class Purchase_cancel extends MY_API_Controller
{
    /**
     * Purchase_cancel constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('ali/ali_order_refund_model');
        $this->load->model('purchase_cancel_model');
    }

    /**
     * 测试
     */
    public function test()
    {
        $id = $this->input->get_post('id');
        $data = $this->ali_order_refund_model->get_order_refund_data($id);
        exit(json_encode($data));
    }

    /**
     * 自动取消
     * purchase_cancel/auto_cancel
     */
    public function auto_cancel()
    {
        $res = $this->purchase_cancel_model->auto_cancel();
        if(is_array($res))$res = json_encode($res);
        echo $res;
    }

    /**
     * 网菜单自动取消
     * purchase_cancel/auto_cancel_ali_order
     */
    public function auto_cancel_ali_order()
    {
        $res = $this->purchase_cancel_model->ali_order_auto_cancel();
        if(is_array($res))$res = json_encode($res);
        echo $res;
    }


}