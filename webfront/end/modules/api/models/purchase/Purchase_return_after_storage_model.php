<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/5
 * Time: 11:01
 */
class Purchase_return_after_storage_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }
// ****************** 申请明细start ******************
    public function apply_list($params){
        $url=$this->_baseUrl . $this->_applyListUrl;
        if(isset($params['proposer'])){
            $params['proposer'] = implode(',',$params['proposer']);
        }


        if(isset($params['buyer_id'])){
            $params['buyer_id'] = implode(',',$params['buyer_id']);
        }
        
        $result = $this->request_appdal($params,$url,'GET', 1);
        $result = $this->rsp_package($result);
        return $result;
    }

    public function apply_purchase_reject($params){
        $url=$this->_baseUrl . $this->_applyPurchaseRejectUrl;
        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }

    public function apply_export($params){
        $url=$this->_baseUrl . $this->_applyExportUrl;
        $result = $this->request_appdal($params,$url,'GET');
        return $result;
    }

    public function apply_export_excel($params){
        $url=$this->_baseUrl . $this->_applyExportExcelUrl;
        $result = $this->request_appdal($params,$url,'GET');
        if (isset($result['status']) && $result['status']==1){
            $data_list = $result['data_list'];
            $this->load->helper('export_excel');
            export_excel($data_list['heads'], $data_list['data_values'], $data_list['file_name'], $data_list['field_img_name'], $data_list['field_img_key']);
        }


        return $result;
    }

    public function download_import_template($params){
        $url=$this->_baseUrl . $this->_downloadImportTemplateUrl;
        $result = $this->request_appdal($params,$url,'GET');
        return $result;
    }

    public function apply_import($params)
    {
//        pr($params);exit;
        $mode                = 'insert';
        $insert_require_cols = [
            'sku'                   => 'SKU',
            'supplier_code'         => '供应商代码',
            'return_unit_price'     => '退货单价',
            'return_qty'            => '申请退货数量',
            'return_reason'         => '申请退货原因',
            'return_warehouse_code' => '退货仓库',
            'proposer'              => '申请人'
        ];
        //改功能暂无修改功能
        $update_require_cols = [];

        //中文转code
        //退货仓库映射
        $warehouse_map = [
            '小包仓_塘厦' => 'SZ_AA',
            '小包仓_慈溪' => 'CX',
            '小包仓_虎门' => 'HM_AA',
        ];

        //处理退货仓库映射
/*        $bind_required_cols_callback = [
            'return_warehouse_code' => function ($col, &$line, $actual_col_position) use ($warehouse_map) {
                $warehouse_name = $line[$actual_col_position[$col]] ?? '';

                $line[$actual_col_position[$col]] = $warehouse_map[$warehouse_name] ?? '';

                return true;
            },

        ];*/
/*        $parse_error_tips            = [
            'unknown'               => '无法识别内容，无法处理',
            'sku'                   => 'SKU为必填项',
            'return_qty'            => '申请退货数量为必填项,正整数',
            'return_reason'         => '申请退货原因只能填写滞销',
            'return_warehouse_code' => '退货仓库填写错误',
            'proposer'              => '申请人为必填项',
        ];*/

        $curl = &$this;
        $url  = $this->_baseUrl . $this->_applyImportUrl;
        $i = 0;
        $this->load->library('CsvReader');
        $this->csvreader->set_file_url($params['file_url']);
        $this->csvreader
//            ->bind_required_cols_callback($bind_required_cols_callback, null)
//            ->bind_parse_error_tips($parse_error_tips)
            ->check_mode(
                function () {
                    return 'insert';
                },
                //没有导入
                function ($csvReader) use ($insert_require_cols, $update_require_cols) {
                    $csvReader->set_rule($insert_require_cols, '', []);
                }
            )
            ->set_general_insert_id(
                function (){
                    return 'not_set_general_insert_id';
                }
            )
            ->set_request_handler(
                function ($post) use ($url, $curl,$params) {
                    $post['uid'] = $params['uid'];
                    return $curl->httpRequest($url, $post);
                })
            ->set_save_original_line(true)
            ->set_error_file_url();
        $this->csvreader->run();

        return $this->csvreader->get_report(true);
    }

    public function apply_purchase_confirm_detail($params){
        $url=$this->_baseUrl . $this->_applyPurchaseConfirmDetailUrl;
        $result = $this->request_appdal($params,$url,'GET');
        $result = $this->rsp_package($result);
        return $result;
    }

    public function apply_purchase_confirm($params){
        $url=$this->_baseUrl . $this->_applyPurchaseConfirmUrl;
        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }


    public function get_supplier_contact($params){
        $url=$this->_baseUrl . $this->_getSupplierContactUrl;

        $result = $this->request_appdal($params,$url,'GET');
        return $result;
    }



// ****************** 申请明细end ******************
// ****************** 采购确认明细start ******************
    public function confirm_list($params){
        $url=$this->_baseUrl . $this->_confirmListUrl;
        if (isset($params['proposer'])){
            $params['proposer'] = implode(',',$params['proposer']);
        }
        if (isset($params['buyer_id'])){
            $params['buyer_id'] = implode(',',$params['buyer_id']);
        }

        if (isset($params['return_status'])){
            $params['return_status'] = implode(',',$params['return_status']);
        }

        $result = $this->request_appdal($params,$url,'GET');
        $result = $this->rsp_package($result);
        return $result;
    }

    public function confirm_purchase_reject($params){
        $url=$this->_baseUrl . $this->_confirmPurchaseRejectUrl;

        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }

    public function confirm_purchasing_manager_audit($params){
        $url=$this->_baseUrl . $this->_confirmPurchasingManagerAuditUrl;

        $result = $this->request_appdal($params,$url,'POST');
        return $result;
    }

    public function get_log_list($params)
    {
        $url=$this->_baseUrl . $this->_getLogListUrl;

        $result = $this->request_appdal($params,$url,'GET');
        return $result;
    }

    public function confirm_export($params){
        $url=$this->_baseUrl . $this->_confirmExportUrl;
        $result = $this->request_appdal($params,$url,'GET');
        return $result;
    }


    public function confirm_export_excel($params){
        $url=$this->_baseUrl . $this->_confirmExportExcelUrl;
        $result = $this->request_appdal($params,$url,'GET');

        if (isset($result['status']) && $result['status']==1){
            $data_list = $result['data_list'];
            $this->load->helper('export_excel');
            export_excel($data_list['heads'], $data_list['data_values'], $data_list['file_name'], $data_list['field_img_name'], $data_list['field_img_key']);
        }

        return $result;
    }

// ****************** 采购确认明细end ******************
// ****************** 退货跟踪start ******************
// ****************** 退货跟踪end ******************
}