<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-06-04
 * Time: 9:42
 */

class Supplier_balance_order extends MY_Controller {

    public function __construct(){
        parent::__construct();
        $this->load->model('Supplier_balance_order_model', 'balance_order');
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->helper('user');
    }

    public function get_search_params(){
        $params = [];
        $params['create_user_id']        = $this->input->get_post('create_user_id');
        $params['purchase_name']         = $this->input->get_post('purchase_name'); //主体
        $params['supplier_code']         = $this->input->get_post('supplier_code'); //供应商编码
        $params['start_create_time']     = $this->input->get_post('start_date'); //申请时间
        $params['end_create_time']       = $this->input->get_post('end_date'); //申请时间
        $params['start_occurrence_time'] = $this->input->get_post('start_occurrence_date'); //发生时间
        $params['end_occurrence_time']   = $this->input->get_post('end_occurrence_date'); //发生时间
        $params['order_no']              = $this->input->get_post('order_no');// 申请ID
        $params['compare']               = $this->input->get_post('compare');
        $params['audit_status']          = $this->input->get_post('audit_status');
        $params['id']                    = $this->input->get_post('id');
        $params['state']                 = $this->input->get_post('state');
        $params['offset']                = $this->input->get_post('offset');
        $params['limit']                 = $this->input->get_post('limit');

        return $params;
    }

    /**
     * 余额调整单列表
     */
    public function balance_order_list(){
        $params          = $this->get_search_params();
        $page            = $params['offset'];
        $limit           = $params['limit'];
        if(empty($page) or $page < 0){
            $page = 1;
        }
        $limit     = query_limit_range($limit);
        $offsets   = ($page - 1) * $limit;
        $data_list = $this->balance_order->balance_order_list($params, $offsets, $limit, $page);

        $this->success_json($data_list);
    }

    /**
     * 调整单的导入
     * @author Jolon
     */
    public function imp_balance_order(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        $data = $data['data'];
        if($data){
            $result = $this->balance_order->imp_balance_order($data);
            $this->balance_order->reset_applicant_user_list();
            if($result['code']){
                $this->success_json();
            }else{
                $this->error_data_json($result['data'], $result['message']);
            }
        }else{
            $this->error_json('数据缺失');
        }
    }

    /**
     * 审核、作废
     */
    public function update_balance_order_status(){
        $id   = $this->input->get_post('id');
        $type = $this->input->get_post('type');
        $note = $this->input->get_post('note');
        if(empty($id)){
            $this->error_json('请勾选要操作的数据!');
        }
        if(!is_array($id)){
            $id = explode(',', $id);
        }
        if(empty($note)){
            $this->error_json('备注不允许为空!');
        }
        $type_all = [2, 3];//审核与作废
        if(!in_array($type, $type_all)){
            $this->error_json('请传入正确的操作方式!');
        }

        $is_status = $this->balance_order->get_balance_order_status($id, 1);
        if($is_status){
            $this->error_json('调整单不是待财务经理审核的不允许审核或驳回!');
        }

        $result = $this->balance_order->update_balance_order_status($id, $type, $note);
        if($result){
            $this->success_json('操作成功!');
        }else{
            $this->error_json('操作失败!');
        }
    }

    /**
     * 导出
     */
    public function export_detail_list(){
        set_time_limit(0);
        $params = $this->get_search_params();
        file_put_contents(get_export_path('test_log').'log_sbo_20201214.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
        $page            = $params['offset'];
        $limit           = $params['limit'];
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $total = $this->balance_order->balance_order_list($params, $offsets, $limit,$page,'sum');

        $this->load->model('system/Data_control_config_model');
        $ext = 'csv';
        try {
            file_put_contents(get_export_path('test_log').'log_sbo_20201214.txt','END '.json_encode($params).PHP_EOL,FILE_APPEND);
            $result = $this->Data_control_config_model->insertDownData($params, 'SUPPLIER_BALANCE_ORDER', '供应商余额调整单下载', getActiveUserName(), $ext, $total);
        } catch (Exception $exp) {
            file_put_contents(get_export_path('test_log').'log_sbo_20201214.txt','END '.$exp->getMessage().PHP_EOL,FILE_APPEND);
            $this->error_json($exp->getMessage());
        }
        file_put_contents(get_export_path('test_log').'log_sbo_20201214.txt','END '.json_encode($result).PHP_EOL,FILE_APPEND);
        if ($result) {
            $this->success_json([], '',"已添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }
    }
}