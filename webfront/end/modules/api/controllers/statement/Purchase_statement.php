<?php

require APPPATH.'core/MY_ApiBaseController.php';

/**
 * 对账单控制器
 * User: Jolon
 * Date: 2020/05/04 10:00
 */
class Purchase_statement extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('statement/Statement_model');
        $this->_modelObj = $this->Statement_model;
    }

    /**
     * 创建对账单(第一步)
     * @author Jolon
     */
    public function create_statement_preview(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->create_statement_preview($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 入库批次 创建对账单（第二步）
     * @author Jolon
     */
    public function create_statement(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->create_statement($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 对账单管理列表
     * @author Jolon
     */
    public function get_statement_list(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_statement_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 【作废】设置 对账单 是否有效
     * @author Jolon
     */
    public function set_status_valid(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->set_status_valid($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 打印对账单
     * @author Jolon
     */
    public function print_statement(){
        try {
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->print_statement_tmp($params);
            if($data['status']){
                $html = $data['data_list']['html'];
                $key  = $data['data_list']['key'];

                $css_file_name = 'printStatementTemplateAuto.css';

                $css1   = VIEWPATH_PRINT_TEMPLATE."print_template/mycss/".$css_file_name;
                $style1 = file_get_contents($css1);
                $html   = str_replace('<body>','<style>'.$style1.'</style><body>',$html);

                $data_list=[
                    'data_list'=>[$html],
                ];
                $this->sendData($data_list);
            }
        } catch (Exception $exc) {
            $this->sendError(-1, $exc->getMessage());
        }
    }


    /**
     * 查看对账单详情
     * @author Jolon
     */
    public function preview_statement_detail(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->preview_statement_detail($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 上传扫描件
     * @author Jolon
     */
    public function upload_statement_pdf(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->upload_statement_pdf($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 批量上传扫描件
     * @author Jolon
     */
    public function batch_upload_statement_pdf(){
        require APPPATH . 'libraries/File_operation.php';
        $DIR_PATH = dirname(APPPATH). '/download_csv/statement_pdf_upload';
        $upload = new File_operation();
        $this->load->model('finance/Upload_receipt_model');

        if(!isset($_FILES['zip_file']) or empty($_FILES['zip_file'])){
            $this->_code = '501';
            $this->_msg = 'zip资源参数缺失';
            $this->sendData();
        }

        $params = $this->_requestParams;
        $zip_file  = $_FILES['zip_file'];
        $tmp_name = $zip_file['tmp_name'];
        $new_file_name = md5($tmp_name).'.zip';
        $new_file_path = $DIR_PATH .DIRECTORY_SEPARATOR. $new_file_name;

        $ext = explode('.',$zip_file['name']);
        $ext = end($ext);
        if(empty($ext) or $ext !== 'zip'){
            $this->_code = '501';
            $this->_msg  = '所传的压缩包必须是zip格式';
            $this->sendData();
        }

        if(!file_exists($DIR_PATH .DIRECTORY_SEPARATOR)){
            mkdir($DIR_PATH .DIRECTORY_SEPARATOR,0777,true);
        }
        if(file_exists($new_file_path)){// 文件已存在的删除
            unlink($new_file_path);
        }

        $upload_result = copy($tmp_name, $new_file_path);
        if (!$upload_result or !file_exists($new_file_path)) {
            $this->_code = '501';
            $this->_msg  = '文件上传保存失败';
            $this->sendData();
        }else{
            $params = array_merge($params,['zip_file_name' => $zip_file['name'],'zip_file_path' => $new_file_path]);
            $data = $this->_modelObj->batch_upload_statement_pdf($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData($data);
        }
    }


    /**
     * 下载对账单 PDF
     * /statement/purchase_statement/download_statement_pdf
     */
    public function download_statement_pdf(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->download_statement_pdf($params);
        try{
            if(!empty($data['data_list'])){
                $this->load->model('compact/Print_pdf_model');
                $html = $data['data_list']['html'];
                $key = $data['data_list']['key'];

                $css_file_name = 'printStatementTemplate.css';
                //设置PDF页脚内容
                $footer = "<p style='text-align:center'>第<span>{PAGENO}</span>页,共<span>{nb}</span>页</p>";

                $file_name = isset($params['statement_number']) ? $params['statement_number'] :  date('Ymdhis');
                $type = isset($params['type']) ? $params['type'] : 'D';
                if(!empty($params['file_path'])){
                    $file_path = realpath($params['file_path']) . DIRECTORY_SEPARATOR . $file_name;
                    $this->Print_pdf_model->writePdf($html, '', $file_path, $type, $css_file_name, '', $footer);
                }else{
                    $this->Print_pdf_model->writePdf($html, '', $file_name, $type, $css_file_name, '', $footer);
                }
            }else{
                echo '未获取到打印数据';exit;
            }

        }catch(Exception $e){
            echo $e->getMessage();exit;
        }

    }


    /**
     * 下载对账单 EXCEL
     * /statement/purchase_statement/download_statement_html
     */
    public function download_statement_html(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->download_statement_excel($params);
        try{
            if(!empty($data['data_list'])){
                $this->load->model('compact/Print_pdf_model');
                $html = $data['data_list']['html'];
                $key = $data['data_list']['key'];

                $file_name = isset($params['statement_number']) ? $params['statement_number'] :  date('Ymdhis');

                header("Cache-Control:public");
                header("Pragma:public");

                header( "Content-Type: application/vnd.ms-excel; name='excel'" );//表示输出的类型为excel文件类型
                header( "Content-type: application/octet-stream" );//表示二进制数据流，常用于文件下载
                header( "Content-Disposition: attachment; filename=".date('Y-m-d',time()).$file_name.".xls");//弹框下载文件
                //以下三行代码使浏览器每次打开这个页面的时候不会使用缓存从而下载上一个文件
                header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
                header( "Pragma: no-cache" );
                header( "Expires: 0" );
                //设置纸张大小为A4
                echo $html;
            }else{
                echo '未获取到打印数据';exit;
            }

        }catch(Exception $e){
            echo $e->getMessage();exit;
        }
    }

    /**
     * 下载对账单运费明细 EXCEL
     * /statement/purchase_statement/download_freight_details
     */
    public function download_freight_details(){
        try {
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $data = $this->_modelObj->download_freight_details($params);

            if(isset($data['data_list']['filePath'])){
                $this->sendData($data);
            }
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 采购确认扫描件
     * /api/statement/purchase_statement/confirm_statement_pdf
     * @author Justin
     */
    public function confirm_statement_pdf(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->confirm_statement_pdf($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查看上传pdf操作日志
     * @author Justin
     */
    public function get_operation_pdf_logs()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_operation_pdf_logs($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 下载付款申请书
     * @author Justin
     */
    public function get_statement_pay_requisition(){
        $this->_init_request_param("GET");
        try{
            $data_list = $this->_modelObj->get_statement_pay_requisition($this->_requestParams);
            if($data_list['status']){
                $data_list = $data_list['data_list'];
                $success_list = $data_list['success_list'];
                $error_list = $data_list['error_list'];

                if($success_list){
                    $this->load->model('compact/Print_pdf_model');
                    $this->load->model('finance/Upload_receipt_model');
                    if(count($success_list) == 1){
                        $html       = current($success_list);
                        $pur_number = key($success_list);
                        $fileName   = get_export_path('statement_pdf').$pur_number.'.pdf';
                        $this->Print_pdf_model->writePdf($html,'',$fileName,'F');

                        if(file_exists($fileName)){
                            $file_type = 'binary';// 二进制文件流
                            $file_name = $pur_number.'.pdf';

                            $result = $this->Upload_receipt_model->upload_picture($fileName,$pur_number.'.pdf');
                            $result = json_decode($result, TRUE);
                            if($result && isset($result['code']) && $result['code']==1000){
                                //删除本地
                                @unlink($fileName);
                                $down_file_url = $result['data'][0]['fullUrl'];
                            }else{
                                throw new Exception('上传fastdfs文件系统失败');
                            }

                        }else{
                            throw new Exception('下载文件生成失败');
                        }
                    }else{
                        // HTML 生成 PDF 文件
                        foreach($success_list as $pur_number => $html){
                            $fileName = get_export_path('statement_pdf').$pur_number.'.pdf';
                            $this->Print_pdf_model->writePdf($html,'',$fileName,'F');
                            $success_list[$pur_number] = $fileName;
                        }
                        if($error_list){
                            $fileName = get_export_path('statement_pdf').'errors'.'.pdf';
                            $this->Print_pdf_model->writePdf(implode("<br/>",$error_list),'',$fileName,'F');
                            $success_list['errors'] = $fileName;
                        }
                        $zip_file_name  = 'fukuanshenqing-'.date('Ymd').'-'.rand(1000,9999).'.zip';
                        $file_name      = $zip_file_name;
                        $file_type      = 'zip';// 压缩包
                        $down_file_url  = $this->download_compact_handle($success_list,$zip_file_name);

                        if(empty($down_file_url)){
                            throw new Exception('压缩文件生成失败');
                        }
                    }

                    $this->sendData(['data_list' => ['file_type' => $file_type,'file_name' => $file_name,'file_path' => $down_file_url]]);
                }else{
                    throw new Exception(implode("<br/>",$error_list));
                }
            }else{
                throw new Exception($data_list['errorMess']);
            }
        }catch(Exception $e){
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 下载文件处理
     * @param  array $file_list
     * @param string $file_alias_name
     * @throws Exception
     */
    public function download_compact_handle($file_list,$file_alias_name){
        if(empty($file_list)){
            $this->sendError(0, '文件不存在');
        }
        $this->load->library('php_zip');
        try{
            $dfile = get_export_path('statement_pdf').$file_alias_name;

            foreach ($file_list as $filename => $file_path) {
                $suffix = explode('.', basename($file_path));   //文件后缀
                if (empty($suffix)) continue;                    //获取后缀失败,跳过操作
                $suffix = '.' . array_pop($suffix);
                $filename = $filename.$suffix;

                $this->php_zip->add_file(file_get_contents($file_path), $filename);
            }
            //----------------------
            $this->php_zip->output($dfile);

            if(file_exists($dfile)){
                $down_file_url = get_export_path_replace_host(get_export_path('statement_pdf'),'').$file_alias_name;
                return 'http://'.$_SERVER['HTTP_HOST'].'/'.$down_file_url;
            }else{
                return false;
            }

            $file_alias_name = str_replace('payment','付款申请书',$file_alias_name);

            // 下载文件
            ob_clean();
            header('Pragma: public');
            header('Last-Modified:'.gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control:no-store, no-cache, must-revalidate');
            header('Cache-Control:pre-check=0, post-check=0, max-age=0');
            header('Content-Transfer-Encoding:binary');
            header('Content-Encoding:none');
            header('Content-type:multipart/form-data');
            header('Content-Disposition:attachment; filename="'.$file_alias_name.'"'); //设置下载的默认文件名
            header('Content-length:'. filesize($dfile));
            $fp = fopen($dfile, 'r');
            while(connection_status() == 0 && $buf = @fread($fp, 8192)){
                echo $buf;
            }
            fclose($fp);
            @unlink($dfile);
            @flush();
            @ob_flush();
            exit();
        }catch(Exception $e){
            throw new Exception($e->getMessage());
        }
    }


    /**
     * 批量下载对账单
     */
    public function batch_download_statement(){
        set_time_limit(0);
        try {
            //file_put_contents(get_export_path('test_log').'log_20201127.txt','WEB '.json_encode($this->_requestParams).PHP_EOL,FILE_APPEND);
            $this->_init_request_param("POST");
            $data= $this->_modelObj->batch_download_statement($this->_requestParams);
            //file_put_contents(get_export_path('test_log').'log_20201127.txt','WEB '.json_encode($data).PHP_EOL,FILE_APPEND);
            $this->sendData($data);
        } catch (Exception $e) {
            //file_put_contents(get_export_path('test_log').'log_20201127.txt','WEB 导出出现异常'.PHP_EOL,FILE_APPEND);
            $this->sendError($e->getCode(), $e->getMessage()."导出出现异常!");
        }
    }

    /**
     * 对账单CSV下载
     */
    public function statement_export_csv(){
        set_time_limit(0);
        try {
            //file_put_contents(get_export_path('test_log').'log_20210106.txt','WEB '.json_encode($this->_requestParams).PHP_EOL,FILE_APPEND);
            $this->_init_request_param("POST");
       /*     if(!isset($this->_requestParams['ids']) or empty($this->_requestParams['ids'])){
                throw new Exception('参数ids不能为空');
            }*/

            $search_params = ['ids','order_number','purchase_number','create_user_id','buyer_id','supplier_code','statement_pdf_status','supplier_is_gateway','is_drawback','purchase_name','demand_number',
                'status_valid','statement_pay_status','create_time_start','create_time_end','is_purchasing','group_ids','free_shipping','create_user_name','status','source_party','statement_user_id','statement_user_name',
                'instock_month','accout_period_time_start','accout_period_time_end'];

            $all_ok = false;
            foreach ($search_params as $param) {
                if( !empty($this->_requestParams[$param])){
                    $all_ok = true;
                }

            }
            if (!$all_ok) {
                throw new Exception('请至少选择一个条件查询');
            }

            $requestParams = $this->_requestParams;
            $requestParams['uid'] = $this->_requestParams['uid'];
            $requestParams['org_code'] = $this->_requestParams['org_code'];
            $data = $this->_modelObj->statement_export_csv($requestParams);
            //file_put_contents(get_export_path('test_log').'log_20210106.txt','WEB '.json_encode($data).PHP_EOL,FILE_APPEND);
            $this->sendData($data);
        } catch (Exception $e) {
            //file_put_contents(get_export_path('test_log').'log_20210106.txt','WEB 导出出现异常'.PHP_EOL,FILE_APPEND);
            $this->sendError(1, $e->getMessage()."导出出现异常!");
        }
    }



    /**
     * 甲方先盖章（甲方发起盖章）
     * @author Jolon
     */
    public function initiator_start_flow()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->initiator_start_flow($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 甲方对账人审核
     * @author Jolon
     */
    public function statement_audit()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->statement_audit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 甲方盖章
     * @author Jolon
     */
    public function signfields_flow()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->signfields_flow($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 甲方催办
     * @author Jolon
     */
    public function signflows_rushsign()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->signflows_rushsign($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 甲方撤销
     * @author Jolon
     */
    public function signflows_revoke()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->signflows_revoke($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }


    /**
     * 上传附属文件
     * @author Jolon
     */
    public function upload_attachment_pdf()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->upload_attachment_pdf($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}