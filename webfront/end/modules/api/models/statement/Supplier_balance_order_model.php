<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-06-05
 * Time: 14:02
 */

class Supplier_balance_order_model extends Api_base_model {

    protected $_listUrl = "";

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 调整单的导入
     */
    public function imp_balance_order($params){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $url = $this->_baseUrl.$this->_imp_balance;//调用服务层api

        $file_path = $params['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀

        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if($fileExp == 'xls')
            $PHPReader = new \PHPExcel_Reader_Excel5();
        if($fileExp == 'xlsx')
            $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 xls 或 xlsx 文件 ";
            $return['data']    = '';

            return $return;
        }

        $PHPReader    = $PHPReader->load($file_path);
        $currentSheet = $PHPReader->getSheet(0);
        $sheetData    = $currentSheet->toArray(null, true, true, true);

        //判断数据源是否为空
        $error_list = [];
        if($sheetData){
            foreach($sheetData as $key => $value){
                if($key <= 1)
                    continue;
                if(empty($value['A'])){
                    $error_list[$key] = "采购主体";
                }
                if(empty($value['B'])){
                    $error_list[$key] = "供应商代码";
                }
                if(empty($value['C'])){
                    $error_list[$key] = "调整金额";
                }
                if(empty($value['D'])){
                    $error_list[$key] = "备注";
                }
            }
        }

        if($error_list){// 验证数据 出现错误
            $objPHPExcel = PHPExcel_IOFactory::load($file_path);
            foreach($error_list as $key => $errorMsg){
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$key, $errorMsg);
                $objPHPExcel->getActiveSheet()->getStyle("E{$key}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
            }
            $objWriter       = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $file_name       = 'Error-'.date('YmdHis').'.xls';
            $error_file_path = get_export_path('balance_order').$file_name;
            $objWriter->save($error_file_path);//文件保存路径
            $error_file_path   = 'http://'.$_SERVER['HTTP_HOST'].'/download_csv/balance_order/'.$file_name;
            $return['code']    = false;
            $return['message'] = "共有 ".count($error_list)." 条数据导入失败，是否下载报错结果";
            $return['data']    = $error_file_path;

            return $return;
        }

        $uid          = $params['uid'];
        $post['data'] = $sheetData;
        $data_string  = json_encode($post);
        $result       = getCurlData($url.'?uid='.$uid, $data_string, 'POST', array(
            'Content-Type: application/json',
            'Content-Length: '.strlen($data_string)
        ), false, array('time_out' => 900, 'conn_out' => 0));
        $result       = json_decode($result, true);
        if(empty($result['status'])){
            $error_list = $result['data_list'];
            if(empty($error_list)){// 程序错误
                $return['code']    = false;
                $return['message'] = isset($result['errorMess']) ? $result['errorMess'] : '程序发生错误';
                $return['data']    = [];

                return $return;
            }
            $objPHPExcel = PHPExcel_IOFactory::load($file_path);
            foreach($error_list as $key => $errorMsg){
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E'.$key, $errorMsg);
                $objPHPExcel->getActiveSheet()->getStyle("E{$key}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
            }
            $objWriter       = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $file_name       = 'Error-'.date('YmdHis').'.xls';
            $error_file_path = get_export_path('balance_order').$file_name;
            $objWriter->save($error_file_path);//文件保存路径
            $error_file_path   = 'http://'.$_SERVER['HTTP_HOST'].'/download_csv/balance_order/'.$file_name;
            $return['code']    = false;
            $return['message'] = "共有 ".count($error_list)." 条数据导入失败，是否下载报错结果";
            $return['data']    = $error_file_path;

            return $return;
        }else{
            $return['code']    = true;
            $return['message'] = '导入成功'.$result['errorMess'];
            $return['data']    = '';

            return $return;
        }
    }

    /**
     * 余额调整单列表
     */
    public function balance_order_list($params){
        $url = $this->_baseUrl.$this->_list;

        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 审核、作废
     */
    public function update_balance_order_status($params){
        $url = $this->_baseUrl.$this->_update_balance;

        return $this->request_http($params, $url, 'GET', false);
    }

    /**
     * 供应商余额调整表 - 导出
     */
    public function export_detail_list($params){
        $url = $this->_baseUrl . $this->_exportDetailList;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }
}