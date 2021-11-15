<?php

/**
 * SKU退款率
 * Class Supplier_check_model
 */
class Supplier_check_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     * @desc 获取列表
     */
    public function get_list($params = [])
    {
        $url = $this->_baseUrl.$this->_supplierGetList;
        return $this->httrequest($params, $url, 'POST');
    }

    /**
     * @desc 导出列表
     */
    public function export_list($params = [])
    {
        $url = $this->_baseUrl.$this->_supplierExportList;
        return $this->httrequest($params, $url, 'POST');
    }

    /**
     * @desc 导入列表
     */
    public function import_list($post = [])
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $file_path = $post['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀

        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'xls') $PHPReader = new \PHPExcel_Reader_Excel5();
        if ($fileExp == 'xlsx') $PHPReader = new \PHPExcel_Reader_Excel2007();
        if ($fileExp == 'csv') $PHPReader = new \PHPExcel_Reader_CSV();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 xls、csv 或 xlsx 文件 ";
            $return['data']    = '';
            return $return;
        }
        $PHPReader      = $PHPReader->load($file_path);
        $currentSheet   = $PHPReader->getSheet(0);
        $sheetData      = $currentSheet->toArray(null,true,true,true);

        $error_list = [];
        $post_data = [];
        if($sheetData){
            foreach($sheetData as $key => $value){
                if($key <= 1) continue;
                if(empty($value['A']) || empty($value['B'])){
                    $error_list[] = $value['A'];
                    continue;
                }
                $post_data[] = [
                    "sku" => $value['A'],
                    "rate" => $value['B'],
                ];
            }
        }
        if(!empty($error_list)){// 验证数据 出现错误
            $return['status'] = 0;
            $return['errorMess'] = "共有 ".count($error_list)." 条数据导入失败: ".implode(",", $error_list). "SKU或退款率必填";
            return $return;
        }

        $params['org_code'] = 'org_00001';
        $params['uid'] = $post['uid'];
        $params['data'] = $post_data;

        $url = $this->_baseUrl . $this->_supplierImportList;//调用服务层api

        $ress = $this->httrequest($params, $url, 'POST');
        return $ress;
    }

    /**
     * @desc 获取列表
     */
    public function refund_rate_edit($params = [])
    {
        $url = $this->_baseUrl.$this->_supplierRefundRateEdit;
        return $this->httrequest($params, $url, 'POST');
    }

    /**
     * @desc 根据备货单获取相应的验货信息
     */
    public function get_order_by_suggest($params = [])
    {
        $url = $this->_baseUrl.$this->_getOrderBySuggest;
        return $this->httrequest($params, $url, 'POST');
    }

    /**
     * @desc 获取批量确认数据
     */
    public function get_check_confirm($params = [])
    {
        $url = $this->_baseUrl.$this->_getCheckConfirm;
        return $this->httrequest($params, $url, 'POST');
    }

    /**
     * @desc 新增/保存批量确认数据
     */
    public function save_check_confirm($params = [])
    {
        $url = $this->_baseUrl.$this->_saveCheckConfirm;
        return $this->httrequest($params, $url, 'POST');
    }

    /**
     * @desc 新增验货申请
     */
    public function create_check_save($params = [])
    {
        $url = $this->_baseUrl.$this->_createCheckSave;
        return $this->httrequest($params, $url, 'POST');
    }
}