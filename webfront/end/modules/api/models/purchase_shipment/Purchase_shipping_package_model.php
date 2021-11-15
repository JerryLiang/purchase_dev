<?php

/**
 * Created by PhpStorm.
 * User: Dean
 * Date: 2019/12/5
 * Time: 11:01
 */
class Purchase_shipping_package_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }


    /**
     * 获取整柜列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function get_cabinet_list($params){
        $url = $this->_baseUrl . $this->_cabinetListUrl;
        return $this->request_http($params,$url);

    }



    public  function get_export_cabinet_list($params){

        $url= $this->_baseUrl . $this->_exportCabinetListUrl;
        $result= $this->request_http($params,$url,'GET',false);
        require_once APPPATH . 'third_party/PHPExcel.php';
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
        header("Content-Type:text/html;Charset=utf-8");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', '虚拟柜号')
            ->setCellValue('C1', '发运类型')
            ->setCellValue('D1', '发运单号')
            ->setCellValue('E1', '采购单号')
            ->setCellValue('F1', '新备货单号')
            ->setCellValue('G1', 'sku')
            ->setCellValue('H1', '图片')
            ->setCellValue('I1', '产品名称')
            ->setCellValue('J1', '备货单状态')
            ->setCellValue('K1', '订单状态')
            ->setCellValue('L1', '供应商名称')
            ->setCellValue('M1', '采购员')
            ->setCellValue('N1', '是否退税')
            ->setCellValue('O1', '总数')
            ->setCellValue('P1', '净重')
            ->setCellValue('Q1', '总净重')
            ->setCellValue('R1', '毛重')
            ->setCellValue('S1', '总毛重')
            ->setCellValue('T1', '单个体积')
            ->setCellValue('U1', '总体积')
            ->setCellValue('V1', '采购单价')
            ->setCellValue('W1', '总金额')
            ->setCellValue('X1', '柜型')
            ->setCellValue('Y1', '物流类型')
            ->setCellValue('Z1', '采购仓库')
            ->setCellValue('AA1', '目的仓')
            ->setCellValue('AB1', '合同号')
            ->setCellValue('AC1', '产品型号')
            ->setCellValue('AD1', '产品品牌')
            ->setCellValue('AE1', '创建时间')
            ->setCellValue('AF1', 'ID最新更新时间')
            ->setCellValue('AG1', '预计发货日期')
            ->setCellValue('AH1', 'ID是否有效')
            ->setCellValue('AI1', '是否已生成装箱明细')
        ;
        if($result['status'] && !empty($result['data_list'])){
            $data_list=$result['data_list'];
            $i=2;

            foreach($data_list as $key => $val){

                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $val['container_sn'])
                    ->setCellValue('B'.$i, $val['virtual_container_sn'])
                    ->setCellValue('C'.$i, $val['shipment_type'])
                    ->setCellValue('D'.$i, $val['shipment_sn'])
                    ->setCellValue('E'.$i, $val['purchase_number'])
                    ->setCellValue('F'.$i, $val['new_demand_number'])
                    ->setCellValue('G'.$i, $val['sku'])
                    ->setCellValue('H'.$i, $val['product_img_url'])
                    ->setCellValue('I'.$i, $val['product_name'])
                    ->setCellValue('J'.$i, $val['demand_status'])
                    ->setCellValue('K'.$i, $val['purchase_order_status'])
                    ->setCellValue('L'.$i, $val['supplier_name'])
                    ->setCellValue('M'.$i, $val['buyer_name'])
                    ->setCellValue('N'.$i, $val['is_drawback'])
                    ->setCellValue('O'.$i, $val['total_qty'])
                    ->setCellValue('P'.$i, $val['net_weight'])
                    ->setCellValue('Q'.$i, $val['net_weight_total'])
                    ->setCellValue('R'.$i, $val['rought_weight'])
                    ->setCellValue('S'.$i, $val['rought_weight_total'])
                    ->setCellValue('T'.$i, $val['product_volume'])
                    ->setCellValue('U'.$i, $val['product_volume_total'])
                    ->setCellValue('V'.$i, $val['purchase_unit_price'])
                    ->setCellValue('W'.$i, $val['purchase_price_total'])
                    ->setCellValue('X'.$i, $val['cabinet_type_id'])
                    ->setCellValue('Y'.$i, $val['logistics_type'])
                    ->setCellValue('Z'.$i, $val['warehouse_code'])
                    ->setCellValue('AA'.$i, $val['destination_warehouse_code'])
                    ->setCellValue('AB'.$i, $val['compact_number'])
                    ->setCellValue('AC'.$i, $val['product_model'])
                    ->setCellValue('AD'.$i, $val['product_brand'])
                    ->setCellValue('AE'.$i, $val['create_time'])
                    ->setCellValue('AF'.$i, $val['update_time'])
                    ->setCellValue('AG'.$i, $val['estimate_date'])
                    ->setCellValue('AH'.$i, $val['enable'])
                    ->setCellValue('AI'.$i, $val['is_package_box'])

                ;
                $i++;
            }
        }

        // 设置第一个sheet为工作的sheet
        $objPHPExcel->getActiveSheet()->setTitle('CONTAINER_LIST');
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


    /**
     * @desc 导入 优化需求单 文件(计划部修改采购数量和仓库用)
     * @author Jeff
     * @param $post
     * @return array
     * @throws Exception
     * @return
     */
    public function import_package_list($post)
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $url = $this->_baseUrl . $this->_importPackageListUrl;//调用服务层api

        $file_path = $post['file_path'];


        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀



        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'xls') $PHPReader = new \PHPExcel_Reader_Excel5();
        if ($fileExp == 'xlsx') $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 xls 或 xlsx 文件 ";
            $return['data']    = '';
            return $return;
        }



        $PHPReader      = $PHPReader->load($file_path);
        $currentSheet   = $PHPReader->getSheet(0);
        $sheetData      = $currentSheet->toArray(null,true,true,true);


        $error_list = [];
        if($sheetData){
            foreach($sheetData as $key => $value){
                if($key <= 1) continue;

                $purchase_sku = trim($value['A']);

                if (empty($purchase_sku)){
                    $error_list[$key] = "ID必填";
                }



            }
        }
//        print_r($error_list);exit;

       if($error_list){// 验证数据 出现错误

            $error_str = implode(',',array_keys($error_list));


            $return['code'] = false;
            $return['message'] = '第'.$error_str.'行ID必填';
            $return['data'] = [];
            return $return;
        }

        $uid          = $post['uid'];
        $post['data'] = $sheetData;
        $data_string  = json_encode($post);


        $result = getCurlData($url.'?uid='.$uid,$data_string,'POST',array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ),false,array('time_out' => 900,'conn_out' => 0));





        $result = json_decode($result,true);








        if(empty($result['status'])){
            $error_list    = $result['data_list'];
            if(empty($error_list)){// 程序错误
                $return['code'] = false;
                $return['message'] = isset($result['errorMess'])?$result['errorMess']:'程序发生错误';
                $return['data'] = '';
                return $return;
            } else {
                $msg ='';
              foreach ($error_list as $container_sn => $info) {
                  $str = implode(',',$info);
                  $msg.=$container_sn.":".$str."<br/>";

              }
                $return['code'] = false;
                $return['message'] = $msg;
                $return['data'] = '';
                return $return;

            }


        }else{
            $return['code'] = true;
            $return['message'] = '导入成功'.$result['errorMess'];
            $return['data'] = '';
            return $return;
        }
    }


    public function get_package_box_list($params)
    {
        $url = $this->_baseUrl . $this->_getPackageBoxDetailUrl;
        return $this->request_http($params, $url);
    }


    /**
     * 获取整柜列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function get_box_list($params){
        $url = $this->_baseUrl . $this->_boxListUrl;
        return $this->request_http($params,$url);

    }

    public  function get_export_box_list($params){

        $url= $this->_baseUrl . $this->_exportBoxListUrl;
        $result= $this->request_http($params,$url,'GET',false);
        require_once APPPATH . 'third_party/PHPExcel.php';
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
        header("Content-Type:text/html;Charset=utf-8");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', '虚拟柜号')
            ->setCellValue('C1', '发运类型')
            ->setCellValue('D1', '发运单号')
            ->setCellValue('E1', '采购单号')
            ->setCellValue('F1', 'sku')
            ->setCellValue('G1', '供应商名称')
            ->setCellValue('H1', '图片')
            ->setCellValue('I1', '产品名称')
            ->setCellValue('J1', '箱数')
            ->setCellValue('K1', '箱内数')
            ->setCellValue('L1', '总数')
            ->setCellValue('M1', '外箱尺寸cm')
            ->setCellValue('N1', '净重KG')
            ->setCellValue('O1', '净重KG-导入')
            ->setCellValue('P1', '毛重KG')
            ->setCellValue('Q1', '毛重KG-导入')
            ->setCellValue('R1', '总净重KG')
            ->setCellValue('S1', '总毛重KG')
            ->setCellValue('T1', '含税单价')
            ->setCellValue('U1', '总金额')
            ->setCellValue('V1', '是否退税')
            ->setCellValue('W1', '柜型')
            ->setCellValue('X1', '采购仓库')
            ->setCellValue('Y1', '目的仓')
        ;
        if($result['status'] && !empty($result['data_list'])){
            $data_list=$result['data_list'];
            $i=2;

            foreach($data_list as $key => $val){

                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $val['container_sn'])
                    ->setCellValue('B'.$i, $val['virtual_container_sn'])
                    ->setCellValue('C'.$i, $val['shipment_type'])
                    ->setCellValue('D'.$i, $val['shipment_sn'])
                    ->setCellValue('E'.$i, $val['purchase_number'])
                    ->setCellValue('F'.$i, $val['sku'])
                    ->setCellValue('G'.$i, $val['supplier_name'])
                    ->setCellValue('H'.$i, $val['product_img_url'])
                    ->setCellValue('I'.$i, $val['product_name'])
                    ->setCellValue('J'.$i, $val['case_qty'])
                    ->setCellValue('K'.$i, $val['in_case_qty'])
                    ->setCellValue('L'.$i, $val['total_num'])
                    ->setCellValue('M'.$i, $val['size'])
                    ->setCellValue('N'.$i, $val['net_weight'])
                    ->setCellValue('O'.$i, $val['import_net_weight'])
                    ->setCellValue('P'.$i, $val['rought_weight'])
                    ->setCellValue('Q'.$i, $val['import_rought_weight'])
                    ->setCellValue('R'.$i, $val['net_weight_total'])
                    ->setCellValue('S'.$i, $val['rought_weight_total'])
                    ->setCellValue('T'.$i, $val['purchase_unit_price'])
                    ->setCellValue('U'.$i, $val['purchase_price_total'])
                    ->setCellValue('V'.$i, $val['is_drawback'])
                    ->setCellValue('W'.$i, $val['cabinet_type_id'])
                    ->setCellValue('X'.$i, $val['warehouse_code'])
                    ->setCellValue('Y'.$i, $val['destination_warehouse_code'])
                ;
                $i++;
            }
        }

        // 设置第一个sheet为工作的sheet
        $objPHPExcel->getActiveSheet()->setTitle('BOX_LIST');
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

    public function get_container_log_list($params)
    {
        $url = $this->_baseUrl . $this->_getContainerLogUrl;
        return $this->request_http($params, $url);
    }


    /**
     * 打印装箱明细
     * * */
    public function printing_box_detail($params)
    {
        $url = $this->_baseUrl . $this->_printingUrl;
        $result = $this->httrequest($params, $url);

        return $result;
    }


    /**
     * 返回打印的装箱明细数据
     * @author Dean
     * **/
    public function  get_print_menu($post){
        $url = $this->_baseUrl . $this->_printMenuUrl;
        return $this->_curlReadHandleApi($url, $post, 'POST');
    }








}