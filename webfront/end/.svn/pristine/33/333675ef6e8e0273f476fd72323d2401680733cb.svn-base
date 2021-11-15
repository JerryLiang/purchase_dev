<?php
/**
 * 报损信息模型类
 * User: Jaxton
 * Date: 2019/01/17 10:06
 */

class Report_loss_model extends Api_base_model {
	//protected $table_name = 'purchase_order_reportloss';//报损表

	private $success=false;
	private $error_msg='';
	private $success_msg='';

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
        //$this->load->helper(['user','abnormal']);
    }

    

    /**
    * 获取报损数据列表
    * @param $params
    * @param $offset
    * @param $limit
    * @return array   
    * @author Jaxton 2019/01/17
    */
    public function get_report_loss_list($params){
        $url = $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params,$url);
    	
    }

    /**
    * 获取审核界面数据
    * @param $id
    * @return array   
    * @author Jaxton 2019/01/17
    */
    public function get_approval_page($params){
        if(empty($params['id'])){
            $this->_errorMsg = 'ID必须';return;
        }
        // 2.调用接口
        $url = $this->_baseUrl . $this->_approvalUrl;
        return $this->request_http($params,$url);
    }

    /**
    * 审核
    * @param $id
    * @return array   
    * @author Jaxton 2019/01/17
    */
    public function approval_handle($params){
    	if(empty($params['id'])){
            $this->_errorMsg = 'ID必须';return;
        } 
        if(empty($params['approval_type']) || !in_array($params['approval_type'], [1,2])){
            $this->_errorMsg = '审核类型错误';return;
        }
        if($params['approval_type']==2 && empty($params['remark'])){
            $this->_errorMsg = '驳回请填写原因';return;
        } 
        // 2.调用接口
        $url = $this->_baseUrl . $this->_approval_handleUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    function export_report_loss($params){

    	$url= $this->_baseUrl . $this->_exportUrl;
        $result= $this->request_http($params,$url,'GET',false);
        require_once APPPATH . 'third_party/PHPExcel.php';
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
        header("Content-Type:text/html;Charset=utf-8");
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '采购单号')
            ->setCellValue('B1', '供应商编码')
            ->setCellValue('C1', '供应商名称')
            ->setCellValue('D1', '审核状态')
            ->setCellValue('E1', 'sku')
            ->setCellValue('F1', '产品名称')
            ->setCellValue('G1', '单价')
            ->setCellValue('H1', '采购数量')
            ->setCellValue('I1', '入库数量')
            ->setCellValue('J1', '报损数量')
            ->setCellValue('K1', '报损运费')
            ->setCellValue('L1', '报损商品额')
            ->setCellValue('M1', '报损金额')
            ->setCellValue('N1', '申请人')
            ->setCellValue('O1', '申请时间')
            ->setCellValue('P1', '经理审核人')
            ->setCellValue('Q1', '经理审核时间')
            ->setCellValue('R1', '财务审核人')
            ->setCellValue('S1', '财务审核时间')
            ->setCellValue('T1', '责任人')
            ->setCellValue('U1', '责任人工号')
            ->setCellValue('V1', '承担方式')
            ->setCellValue('W1', '关联的取消编码')
            ->setCellValue('X1', '所属组别')
            ;

        if($result['status'] && !empty($result['data_list'])){
            $data_list=$result['data_list'];
            $i=2;

            foreach($data_list as $key => $val){
                
                $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValue('A'.$i, $val['pur_number'])
                    ->setCellValue('B'.$i, $val['supplier_code'])
                    ->setCellValue('C'.$i, $val['supplier_name'])
                    ->setCellValue('D'.$i, is_numeric($val['status'])?getReportlossApprovalStatus($val['status']):$val['status'])
                    ->setCellValue('E'.$i, $val['sku'])
                    ->setCellValue('F'.$i, $val['product_name'])
                    ->setCellValue('G'.$i, $val['price'])
                    ->setCellValue('H'.$i, $val['confirm_amount'])
                    ->setCellValue('I'.$i, $val['instock_qty'])
                    ->setCellValue('J'.$i, $val['loss_amount'])
                    ->setCellValue('K'.$i, $val['loss_freight'])
                    ->setCellValue('L'.$i, $val['loss_price'])
                    ->setCellValue('M'.$i, $val['loss_totalprice'])
                    ->setCellValue('N'.$i, $val['apply_person'])
                    ->setCellValue('O'.$i, $val['apply_time'])
                    ->setCellValue('P'.$i, $val['audit_person'])
                    ->setCellValue('Q'.$i, $val['audit_time'])
                    ->setCellValue('R'.$i, $val['approval_person'])
                    ->setCellValue('S'.$i, $val['approval_time'])
                    ->setCellValue('T'.$i, $val['responsible_user'])
                    ->setCellValue('U'.$i, $val['responsible_user_number'])
                    ->setCellValue('V'.$i, $val['responsible_party'])
                    ->setCellValue('W'.$i, $val['relative_superior_number'])
                    ->setCellValue('X'.$i, $val['groupName'])
                ;
                $i++;
            }
        }

        // 设置第一个sheet为工作的sheet
        $objPHPExcel->getActiveSheet()->setTitle('REPORT_LOSS');
        $objPHPExcel->setActiveSheetIndex(0);

        //生成xlsx文件
        $filename = 'REPORT_LOSS-'.date('YmdHis');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');

        // 保存Excel 2007格式文件
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * @desc 编辑报损数据预览
     * @author Jeff
     * @Date 2019/6/25 19:42
     * @return
     */
    public function get_preview_edit_data($params)
    {
        $url = $this->_baseUrl . $this->_previewEditDataUrl;
        $result = $this->httpRequest($url, $params, 'POST');

        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:" . json_encode($result, 256),
                -1
            );
        }
    }

    /**
     * @desc 编辑报损
     * @author Jeff
     * @Date 2019/6/26 9:51
     * @return
     */
    public function edit_report_loss($params)
    {
        $url = $this->_baseUrl . $this->_editReportLossUrl;
        $result = $this->httpRequest($url, $params, 'POST');

        if (isset($result['status']) && $result['status'] === 1) {
            return ['data_list' => isset($result['data_list']) ? $result['data_list'] : [],];
        } else {
            throw new Exception(
                isset($result['errorMess']) ? $result['errorMess'] : "api返回异常,resp:" . json_encode($result, 256),
                -1
            );
        }
    }


    /**
     * 获取报损数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function get_report_loss_list_sum($params){
        $url = $this->_baseUrl . $this->_listSumUrl;
        return $this->request_http($params,$url);

    }

    /**
     * 审核
     * @param $id
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function batch_approval_handle($params){
        if(empty($params['ids'])){
            $this->_errorMsg = 'ID必须';return;
        }
        if(empty($params['approval_type']) || !in_array($params['approval_type'], [1,2])){
            $this->_errorMsg = '审核类型错误';return;
        }
        if($params['approval_type']==2 && empty($params['remark'])){
            $this->_errorMsg = '驳回请填写原因';return;
        }
        // 2.调用接口
        $url = $this->_baseUrl . $this->_batchApprovalHandleUrl;
        return $this->request_http($params,$url,'GET',false);
    }
}