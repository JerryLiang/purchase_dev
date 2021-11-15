<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 发票清单控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_invoice_list extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct() {
        parent::__construct();
        $this->load->model('purchase/Purchase_invoice_model');
        $this->_modelObj = $this->Purchase_invoice_model;
        $this->load->helper('status_order');
        
    }

    /**
     * 发票清单列表
     * /purchase/purchase_invoice_list/get_purchase_invoice
     * @author Jaden 2019-1-8
     */
    public function get_invoice_listing_list() {
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_invoice_listing_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    }


    /**
     * 发票清单列表提交弹出列表
     * /purchase/purchase_invoice_list/submit_detail
     * @author Jaden 2019-1-10
     */
    public function submit_detail(){
        $params = $this->_requestParams;
        //$params['invoice_number'] = 'FP000024';
        $data = $this->_modelObj->web_submit_detail($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 发票清单列表财务审核弹出列表
     * /purchase/purchase_invoice_list/submit_financial_audit_invoice_list
     * @author Jaden 2019-1-10
     */
    public function submit_financial_audit_invoice_list(){
        $params = $this->_requestParams;
        //$params['invoice_number'] = 'FP000114';
        $data = $this->_modelObj->web_submit_invoice_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }



    /**
     * 发票清单列表提交操作
     * /purchase/purchase_invoice_list/submit_invoice
     * @author Jaden 2019-1-10
     */
    public function submit_invoice(){
        $params = $this->_requestParams;
        //$params['invoice_number'] = 'FP000036';
        $data = $this->_modelObj->web_submit_invoice($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 发票清单列表撤销操作
     * /purchase/purchase_invoice_list/revoke_invoice
     * @author Jaden 2019-1-10
     */
    public function revoke_invoice(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_revoke_invoice($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 下载发票明细(导出)
     * /purchase/purchase_invoice_list/download_export
     * @author Jaden 2019-1-11
     */
    public function download_export(){
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_download_export($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $tax_list_tmp = $data['data_list']['invoice_list'];
       
        $heads_max = max($data['data_list']['heads_num']);
//        $heads = ['发票清单号','备货单号','SKU','产品名称','开票品名','采购单号','供应商名称',
//            '报关单号', '出口海关编码','报关数量','报关品名','含税单价','开票数量','增值税税率',
//            '已开票金额','发票金额','税金','总金额','币种','报关单位','报关型号'];
        $heads = [
            'invoice_number' => '发票清单号',
            'demand_number'  => '备货单号',
            'sku' => 'SKU',
            'product_name' => '产品名称',
            'export_cname' => '开票品名',
            'purchase_number' => '采购单号',
            'buyer_name' => '采购员',
            'order_time' => '下单时间',
            'supplier_name' => '供应商名称',
            'customs_number' => '报关单号',
            'customs_code' => '出口海关编码',
            'customs_name' => '报关品名',
            'unit_price' => '含税单价',
            'invoiced_amount' => '已开票金额',
            'customs_quantity' => '报关数量',
            'uumber_invoices' => '已开票数量',
            'total_price' => '总金额',
            'export_cname' => '报关型号',
            'invoice_amount' => '发票金额',
            'taxes' => '税金',
            'customs_unit' => '报关单位',
            'invoice_code_left' => '发票代码(左)',
            'invoice_code_right' => '发票号码(右)',
        ];
        foreach ($tax_list_tmp as $key => $item){
            foreach ($heads as $k => $row){
                if ($k == 'invoice_code_left' || $k == 'invoice_code_right'){//存在多条
                    $i = 1;
                    foreach ($item['invoice_code_left'] as $_k =>  $v){
                        $list[$key]['invoice_code_left'.$i] = $v;
                        $list[$key]['invoice_code_right'.$i] = $item['invoice_code_right'][$_k];
                        $i++;
                    }
                }else{
                    $list[$key][$k] = $item[$k]??'';
                }
            }
            unset($tax_list_tmp[$key]);
        }

        for ($i=1; $i <= $heads_max; $i++) {
            unset($heads['invoice_code_left']);
            unset($heads['invoice_code_right']);
            array_push($heads,"发票代码(左".$i.")","发票号码(右".$i.")");
        }
        csv_export($heads,$list,'发票清单号-'.date('YmdH:i:s'));
        
        
    }


    public function test(){
        echo '<form action="/index.php/api/purchase/purchase_invoice_list/download_import" method="post" enctype="multipart/form-data">
             <input type="file" class="invoice_file" name="invoice_file" />
             <input type="hidden" name="pop_id" value="1">
             <input type="hidden" name="pc_id" value="1">
             <button type="submit" class="but1">上传</button>
        </form>';
    }

    /**
     * 下载开票合同页面
     * /purchase/purchase_invoice_list/download_view
     * @author Jaden 2019-1-11
     */
    public function download_view(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_download_view($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }else{
            $this->load->model('compact/Print_pdf_model');
            $this->Print_pdf_model->writePdf($data['data_list']['html'],'',date('YmdHis'),'D','billingcontract.css');
        }
        $this->sendData();
    }


    /**
     * 下载开票合同批量上传开票信息
     * /purchase/purchase_invoice_list/download_import
     * @author Jaden 2019-1-11
     */
    public function download_import(){
        //$this->_init_request_param("GET");
        $params = $this->_requestParams;
        $file  = $_FILES['invoice_file'];
        $file_name = $file['tmp_name'];
        $file_name_arr = explode('.', $file['name']);
        
        if($file_name == '')
        {
            http_response(response_format(0,[],'请选择文件再导入'));
        }
        if(end($file_name_arr)!='csv')
        {
            http_response(response_format(0,[], '只接受csv格式的文件'));
        }

        $handle = fopen($file_name, 'r');
        if($handle === FALSE) {
            http_response(response_format(0,[], '打开文件资源失败'));
        }

        $out = array ();
        $n = 0;
        while ($data = fgetcsv($handle, 100000)) {
            $num = count($data);
            for ($i = 0; $i < $num; $i++){
                $out[$n][$i] = iconv('gbK', 'utf-8//IGNORE',$data[$i]);
                //$out[$n][$i] = $data[$i];
            }
            $n++;
        }
        $result_arr = $out;
        $params['import_arr'] = json_encode($result_arr);
        $data = $this->_modelObj->web_download_import($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    }

    /**
     * 批量上传开票信息(下载模板)
     * /purchase/purchase_invoice_list/download_import_model
     * @author Jaden 2019-1-11
     */
    public function download_import_model(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_download_import_model($params);
        $tax_list_tmp = $data['data_list'];
        header('location:'.$tax_list_tmp);
    }


    /**
     * 获取发票清单状态
     * /purchase/purchase_invoice_list/get_invoice_status
     * @author Jaxton 2019-1-21
     */
    public function get_invoice_status(){
        $list=customs_statu_type();
        http_response(response_format(1, $list));
    }


    /**
     * 批量开票详情
     * /purchase/purchase_invoice_list/get_batch_invoice_detail
     * @author Manson
     */
    public function get_batch_invoice_detail()
    {
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_get_batch_invoice_detail($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 批量开票弹出“发票维护”界面数据
     * /purchase/purchase_invoice_list/batch_invoice_submit
     * @author Manson
     */
    public function batch_invoice_submit(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->web_batch_invoice_submit($params);
        //print_r($data);die;
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 导入
     * /purchase/purchase_invoice_list/import_invoice_info
     * @author Manson
     */
    public function import_invoice_info()
    {
        try{
            $this->_init_request_param("POST");
            $params = $this->_requestParams;

            $data = $this->_modelObj->import_invoice_info($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData($data);
        }catch (Exception $e){
            $this->sendError('-1',$e->getMessage());
        }
    }


    public function check_import_title($title){
        $title_rule = [
            0 => '发票清单号',
            1 => '备货单号',
            2 => 'SKU',
        ];
        $col_count = count($title);
        if ($col_count<7){//上传的文件必填项有7列
            throw new Exception(sprintf('上传的文件不符合模板的规范,温馨提示:还需额外添加发票代码的，请以此规则添加标题，并录入发票代码。发票清单号,sku,采购单号必填。'));
        }
        $col_count = $col_count - 3;

        if ($col_count % 4 != 0){//发票代码(左) 发票代码(右) 票面税率 已开票数量  这四个字段是要一起的
            $error_title = '';
            $error_col = $col_count % 4;
            for ($i=0;$i<$error_col;$i++){
                $error_title .= array_pop($title).',';
            }
            throw new Exception(sprintf('%s,上传的文件不符合模板的规范,温馨提示:还需额外添加发票代码的，请以此规则添加标题，并录入发票代码。发票清单号,sku,采购单号必填。',$error_title));
        }

        foreach ($title_rule as $key => $name){//前3列按模板要求填
            if (isset($title[$key]) && $name != $title[$key]){
                throw new Exception(sprintf('上传的文件标题不符合规范:%s第1行第%s列',$name,$key+1));
            }else{
                unset($title[$key]);
            }
        }
        $invoice_count = $col_count/4;
        for ($i=1;$i<=$invoice_count;$i++){
            $invoice_info_title[] = sprintf('发票代码(左%s)',$i);
            $invoice_info_title[] = sprintf('发票代码(右%s)',$i);
            $invoice_info_title[] = sprintf('票面税率%s',$i);
            $invoice_info_title[] = sprintf('已开票数量%s',$i);
        }

        $diff1 = array_diff($invoice_info_title,$title);
        $diff2 = array_diff($title,$invoice_info_title);

        if (!empty($diff1) || !empty($diff2)){
            throw new Exception(sprintf('上传的文件标题不符合规范:%s,请修改为正确的格式:%s',implode(',',$diff2),implode(' ',$invoice_info_title)));
        }
        $title = [
            'invoice_number',
            'demand_number',
            'sku',
            'invoice_code_left',
            'invoice_code_right',
            'invoice_coupon_rate',
            'invoiced_qty',
        ];
        return $title;
    }

    /**
     * 下载开票合同 excel
     * /purchase/purchase_invoice_list/download_invoice_excel
     * @author Manson
     */
    public function download_invoice_excel(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->download_invoice_excel($params);
        if(!empty($data['data_list'])){
            $this->load->model('compact/Print_pdf_model');
            $html = $data['data_list']['html'];
            $key = $data['data_list']['key'];


            $css_file_name = 'billingcontract.css';

            $file_name = isset($params['invoice_number']) ? $params['invoice_number'] :  date('Ymdhis');

            header("Cache-Control:public");
            header("Pragma:public");

            header( "Content-Type: application/vnd.ms-excel; name='excel'" );//表示输出的类型为excel文件类型
            header( "Content-type: application/octet-stream" );//表示二进制数据流，常用于文件下载

            header( "Content-Disposition: attachment; filename=".date('Y-m-d',time()).$file_name.".xls");//弹框下载文件

            //以下三行代码使浏览器每次打开这个页面的时候不会使用缓存从而下载上一个文件
            header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
            header( "Pragma: no-cache" );
            header( "Expires: 0" );
            echo $html;
        }else{
            $this->sendError(-1,$data['errorMess']);
        }
    }

    /**
     * 导出
     * @author Manson
     */
    public function export_list()
    {
        try {
            $this->_init_request_param('GET');
            $params = $this->_requestParams;
            $this->_modelObj->export_list($params);
        }catch ( Exception $exp ) {

            $this->sendData(array('status'=>0,'errorMessage'=>$exp->getMessage()));
        }
    }

    /**
     * 批量提交
     * @author Manson
     */
    public function batch_submit()
    {
        $this->_init_request_param('POST');
        $params = $this->_requestParams;
        $data   = $this->_modelObj->batch_submit($params);
        http_response($data);
    }
}
