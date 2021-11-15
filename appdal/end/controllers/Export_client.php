<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

//$clientData 从MQ取
class Export_client extends MY_API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_progress_model');
        $this->load->model('system/data_center_model');
        $this->load->library('Upload_image');
    }

    function index()
    {
        $data = '';
//      需要判断当前正在导出任务数量
        $data = '{"data":{"id":"243","module_cn_name":"\u91c7\u8d2d\u5355","module_ch_name":"PURCHASEORDER","file_name":null,"number":"3425","progress":"0","data_status":"2","examine_status":"2","add_time":"2020-03-06 09:39:00","add_user_name":"\u9c81\u65ed14592","examine_user_name":"\u9c81\u65ed14592","examine_time":"2020-03-06 10:48:30","condition":{"first_product_line":null,"purchase_order_status":null,"suggest_order_status":null,"sku":"","compact_number":null,"demand_number":null,"buyer_id":null,"supplier_code":null,"is_drawback":null,"is_ali_order":null,"product_name":null,"is_cross_border":null,"pay_status":null,"source":null,"is_destroy":null,"product_is_new":null,"purchase_number":"","purchase_type_id":null,"create_time_start":"","create_time_end":"","loss_status":null,"audit_status":null,"need_pay_time_start":null,"need_pay_time_end":null,"audit_time_start":null,"audit_time_end":null,"ids":null,"is_csv":"1","product_status":null,"pai_number":null,"is_inspection":null,"is_ali_abnormal":null,"is_ali_price_abnormal":null,"warehouse_code":null,"account_type":null,"is_overdue":null,"supplier_source":null,"statement_number":null,"new":1,"search":"1671","is_expedited":null,"state_type":null,"pay_notice":null,"entities_lock_status":null,"is_invaild":null,"lack_quantity_status":null,"is_forbidden":null,"order_by":"","order":"","ticketed_point":null,"is_relate_ali":null,"is_generate":null,"is_purchasing":null,"barcode_pdf":null,"label_pdf":null,"is_equal_sup_id":null,"is_equal_sup_name":null,"is_arrive_time_audit":null},"ext":"csv","down_url":null,"remark":" ","update_time":"2020-03-06 10:51:12","role":"admin,\u91c7\u8d2d\u7ecf\u7406,\u7ecf\u7406,\u4f18\u5316\u5458,\u9500\u552e","user_id":"22579","role_name":"admin"}}';
        var_dump($this->data_center_model->handle_quene_data($data));
    }
}
