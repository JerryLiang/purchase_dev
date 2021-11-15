<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/5
 * Time: 10:58
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Purchase_return_after_storage extends MY_ApiBaseController
{

    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_return_after_storage_model');
        $this->_modelObj = $this->Purchase_return_after_storage_model;
    }
// ****************** 申请明细start ******************
    /**
     * 申请明细-列表
     * /api/purchase/Purchase_return_after_storage/apply_list
     * @author Manson
     */
    public function apply_list()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->apply_list($params);
        http_response($data);
    }

    /**
     * 申请明细-导入
     * /api/purchase/Purchase_return_after_storage/apply_import
     * @author Manson
     */
    public function apply_import()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->apply_import($params);
        if (!empty($data['errorMess'])){
            $res = ['status' => 0, 'data_list' => $data];
            if(isset($data['total']) && isset($data['total']) && isset($data['total'])){
                $res['data_list']['errorMess'] = $data['errorMess']."。本次总计上传{$data['total']}条数据，上传成功{$data['processed']}条，上传失败{$data['undisposed']}条。";
            }
            http_response($res);
        }else{
            $msg = '';
            if(count($data['error_line_message']) > 0){
                $msg = implode("。", $data['error_line_message'])."。";
            }
            $msg .= "本次总计上传{$data['total']}条数据，上传成功{$data['processed']}条，上传失败{$data['undisposed']}条。";
            $data['errorMess'] = $msg;
            http_response(['status' => 1, 'data_list' => $data, "errorMess" => $msg]);
        }
    }

    /**
     * 申请明细-采购确认详情页
     * /api/purchase/Purchase_return_after_storage/apply_purchase_confirm_detail
     * @author Manson
     */
    public function apply_purchase_confirm_detail()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->apply_purchase_confirm_detail($params);
        http_response($data);
    }


    /**
     * 申请明细-采购确认
     * /api/purchase/Purchase_return_after_storage/apply_purchase_confirm
     * @author Manson
     */
    public function apply_purchase_confirm()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->apply_purchase_confirm($params);
        http_response($data);
    }

    /**
     * 申请明细-采购驳回
     * /api/purchase/Purchase_return_after_storage/apply_purchase_reject
     * @author Manson
     */
    public function apply_purchase_reject()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->apply_purchase_reject($params);
        http_response($data);
    }

    /**
     * 申请明细-导出csv
     * /api/purchase/Purchase_return_after_storage/apply_export
     * @author Manson
     */
    public function apply_export()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->apply_export($params);
        http_response($data);
    }

    /**
     * 申请明细-获取供应商信息
     * /api/purchase/Purchase_return_after_storage/get_supplier_contact
     * @author Manson
     */
    public function get_supplier_contact()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->get_supplier_contact($params);
        http_response($data);
    }

    /**
     * 下载导入模板
     * /api/purchase/Purchase_return_after_storage/download_import_template
     * @author Manson
     */
    public function download_import_template()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->download_import_template($params);
        if (isset($data['file_url'])){
            $tax_list_tmp = $data['file_url'];
            header('location:'.$tax_list_tmp);
        }else{
            http_response(['status'=>0,'errorMess'=>'下载地址错误']);
        }

    }

    /**
     * 申请明细-导出excel
     * /api/purchase/Purchase_return_after_storage/apply_export_excel
     * @author Manson
     */
    public function apply_export_excel()
    {
        set_time_limit(0);
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $this->_modelObj->apply_export_excel($params);
    }


// ****************** 申请明细end ******************
// ****************** 采购确认明细start ******************

    /**
     * 采购确认明细-列表
     * /api/purchase/Purchase_return_after_storage/confirm_list
     * @author Manson
     */
    public function confirm_list()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->confirm_list($params);
        http_response($data);
    }

    /**
     * 采购确认明细-采购驳回
     * /api/purchase/Purchase_return_after_storage/confirm_purchase_reject
     * @author Manson
     */
    public function confirm_purchase_reject()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->confirm_purchase_reject($params);
        http_response($data);
    }

    /**
     * 采购确认明细-采购经理审核
     *  /api/purchase/Purchase_return_after_storage/confirm_purchasing_manager_audit
     * @author Manson
     */
    public function confirm_purchasing_manager_audit()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->confirm_purchasing_manager_audit($params);
        http_response($data);
    }


    /**
     * 采购确认明细-日志列表
     * /api/purchase/Purchase_return_after_storage/get_log_list
     * @author Manson
     */
    public function get_log_list()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->get_log_list($params);
        http_response($data);
    }

    /**
     * 采购确认明细-导出csv
     * /api/purchase/Purchase_return_after_storage/confirm_export
     * @author Manson
     */
    public function confirm_export()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->confirm_export($params);
        http_response($data);
    }

    /**
     * 采购确认明细-导出excel
     * /api/purchase/Purchase_return_after_storage/confirm_export_excel
     * @author Manson
     */
    public function confirm_export_excel()
    {
        $this->_init_request_param('GET');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->confirm_export_excel($params);
        http_response($data);
    }

// ****************** 采购确认明细end ******************
// ****************** 退货跟踪start ******************
// ****************** 退货跟踪end ******************
}