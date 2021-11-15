<?php

/**
 * Created by PhpStorm.
 * User: Dean
 * Date: 2019/12/5
 * Time: 11:01
 */
class Purchase_shipment_cancel_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }


    /**
     * 获取报损数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function get_cancel_list($params){
        $url = $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params,$url);

    }

    public  function export_cancel_list($params){

        $url= $this->_baseUrl . $this->_exportUrl;
        $result= $this->request_http($params,$url,'GET',false);
        require_once APPPATH . 'third_party/PHPExcel.php';
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
        header("Content-Type:text/html;Charset=utf-8");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '申请编码')
            ->setCellValue('B1', '发运类型')
            ->setCellValue('C1', '新备货单号')
            ->setCellValue('D1', 'sku')
            ->setCellValue('E1', '采购单号')
            ->setCellValue('F1', '供应商名称')
            ->setCellValue('G1', '是否退税')
            ->setCellValue('H1', '取消数量')
            ->setCellValue('I1', '申请备注')
            ->setCellValue('J1', '申请人')
            ->setCellValue('K1', '申请时间')
            ->setCellValue('L1', '取消审核状态')
        ;

        if($result['status'] && !empty($result['data_list'])){
            $data_list=$result['data_list'];
            $i=2;

            foreach($data_list as $key => $val){

                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $val['bs_number'])
                    ->setCellValue('B'.$i, $val['shipment_type'])
                    ->setCellValue('C'.$i, $val['new_demand_number'])
                    ->setCellValue('D'.$i, $val['sku'])
                    ->setCellValue('E'.$i, $val['purchase_number'])
                    ->setCellValue('F'.$i, $val['supplier_name'])
                    ->setCellValue('G'.$i, $val['is_drawback'])
                    ->setCellValue('H'.$i, $val['loss_amount'])
                    ->setCellValue('I'.$i, $val['remark'])
                    ->setCellValue('J'.$i, $val['apply_person'])
                    ->setCellValue('K'.$i, $val['apply_time'])
                    ->setCellValue('L'.$i, $val['audit_status'])

                ;
                $i++;
            }
        }

        // 设置第一个sheet为工作的sheet
        $objPHPExcel->getActiveSheet()->setTitle('CANCEL_LIST');
        $objPHPExcel->setActiveSheetIndex(0);

        //生成xlsx文件
        $filename = 'CANCEL_LIST_PLAN-'.date('YmdHis');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');

        // 保存Excel 2007格式文件
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }



}