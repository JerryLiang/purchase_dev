<?php

require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 合同控制器
 * User: Jaxton
 * Date: 2019/01/08 10:00
 */

class Compact extends MY_ApiBaseController{

	private $_modelObj;
	private $_ip = NULL;

	public function __construct(){
        parent::__construct();
        $this->load->model('compact/Compact_list_model');
		$this->_modelObj = $this->Compact_list_model;
        $this->_ip = UPLOAD_DOMAIN_TEMPPATH;
    }


    /**
     * 根据合同ID获取合同信息
     * /compact/compact/get_compact_by_id
     * @author Jaxton 2018/01/08
     */
    public function get_compact_by_id(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_compact_by_id($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 一键生成进货单（支持勾选和查询条件）
     * @author Jolon 2019/11/20
     */
    public function one_key_compact_create(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;

        $data = $this->_modelObj->one_key_compact_create($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
    * 获取合同列表
    * /compact/compact/get_compact
	* @author Jaxton 2018/01/08
    */
    public function get_compact(){
        $this->_init_request_param("GET");
		$params = $this->_requestParams;

		$data = $this->_modelObj->get_compact_list($params);

		if (is_null($data)) {
			$this->_code = $this->getServerErrorCode();
			$this->_msg = $this->_modelObj->getErrorMsg();
		}

		$this->sendData($data);

    }

    /**
    * 获取合同详情
    * /compact/compact/get_compact_detail
    * @author Jaxton 2018/01/08
    */
    public function get_compact_detail(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_compact_detail($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

    	
    }

    /**
    *测试
    */
    public function test(){
        // $a=$this->compact_model->get_supplier_down_box();
        // print_r($a);
        //print_r($this->compact_model->get_sku_cancel_number('PO00000023','CW00009')) ;
    	//echo UPLOAD_DOMAIN;die;
    	//echo SUPR_DAL_API_HOST;die;
    	//echo "<input type='file'>";
    	echo '<form action="/index.php/api/compact/compact/upload_compact_file" method="post" enctype="multipart/form-data">
            <input type="hidden" name="pop_id" value="1">
             <input type="hidden" name="pc_id" value="1">
		     <input type="file" class="upload_file" name="upload_file" />
		     
		     <button type="submit" class="but1">上传</button>
		</form>';
    }

    /**
    * 上传合同扫描件
    * @author Jaxton 2018/01/09
    * /compact/compact/upload_compact_file
    */
    public function upload_compact_file(){
        $params = $this->_requestParams;

        if(empty($params['pc_id'])){
            $this->_msg = '缺少参数合同ID';
            $this->_code = 1;
            $this->sendData();
        }

        if(empty($params['pop_id'])){
            $this->_msg = '缺少参数请款单ID';
            $this->_code = 1;
            $this->sendData();
        }

        $params['upload_file']=$_FILES['upload_file'];
        if(empty($params['upload_file'])){
            $this->_msg = '请选择文件上传';
            $this->_code = 1;  
            $this->sendData(); 
        }

        $data = $this->_modelObj->upload_compact_file($params);
        if($data['errorCode']){
            $return_data=[
                'status'=>1,
                'errorMess'=>'上传成功',
                'file_info'=>$data['file_info']
            ];
            $this->_code = 0;
            $this->sendData($return_data);
        }else{
            $this->_code = 1;  
            $this->_msg = $data['errorMess'];
            $this->sendData(); 
        }
        
    }

    /**
     * 上传原始合同扫描件
     * @author Jolon 2018/01/09
     * /compact/compact/upload_compact_original_scan_file
     */
    public function upload_compact_original_scan_file(){
        $params = $this->_requestParams;

        if(empty($params['compact_number'])){
            $this->_msg = '缺少参数合同编号';
            $this->_code = 1;
            $this->sendData();
        }

        if(empty($params['file_name']) or empty($params['file_path'])){
            $this->_msg = '原始合同扫描件文件名或文件路径缺失';
            $this->_code = 1;
            $this->sendData();
        }

        $data = $this->_modelObj->upload_compact_original_scan_file($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);
    }

    /**
     * 批量上传原始合同扫描件
     * @author Jolon 2018/01/09
     * /compact/compact/batch_upload_compact_original_scan_file
     */
    public function batch_upload_compact_original_scan_file(){
        require APPPATH . 'libraries/File_operation.php';
        $DIR_PATH = dirname(APPPATH). '/download_csv/compact_original_cache';
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
            $data = $this->_modelObj->batch_upload_compact_original_scan_file($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData($data);
        }
    }

    /**
    * 查看文件
    * @author Jaxton 2018/01/10
    * /compact/compact/see_compact_file
    */
    public function see_compact_file(){
        try{
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            if(empty($params['type'])){
                $this->_msg = '缺少类型';
                $this->_code = 1;
                $this->sendData();
            }
            if($params['type']==4){
                $data = $this->_modelObj->see_compact_file($params);
                if (is_null($data)) {
                    $this->_code = $this->getServerErrorCode();
                    $this->_msg = $this->_modelObj->getErrorMsg();
                }

                $this->sendData($data);
            }elseif($params['type']==3){
                $data = $this->_modelObj->see_compact_file($params);
                if (is_null($data)) {
                    $this->_code = $this->getServerErrorCode();
                    $this->_msg = $this->_modelObj->getErrorMsg();
                }

                $this->sendData($data);
            }elseif($params['type']==1){
                $data = $this->get_pay_requisition('see');
            }elseif($params['type']==2){//付款回单
                $this->get_pay_receipt();
                //$data = $this->_modelObj->see_compact_file($params);
            }
        }catch(Exception $e){
            $this->_msg = $e->getMessage();
            $this->_code = 1;
            $this->sendData();
        }

    }

    /**
    * 弹出查看界面
    * @author Jaxton 2018/02/28
    * /compact/compact/see_page
    */
    public function see_page(){
        try{
            $this->load->helper('status_order');
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            $pop_id=!empty($params['pop_id'])?$params['pop_id']:0;
            $pc_id=!empty($params['pc_id'])?$params['pc_id']:0;
            $compact_number=!empty($params['compact_number'])?$params['compact_number']:0;
            $requisition_number=!empty($params['requisition_number'])?$params['requisition_number']:0;
            $return_params = [
                'pop_id' => $pop_id,
                'pc_id' => $pc_id,
                'compact_number' => $compact_number,
                'requisition_number' => $requisition_number
            ];
            $value=see_page_file_type();
            $return_data=[
                'status'=>1,
                'errorMess'=>'请求成功',
                'value'=>$value,
                'params'=>$return_params
            ];
            $this->_code = 0;
            $this->sendData($return_data);
        }catch(Exception $e){
            $this->_msg = $e->getMessage();
            $this->_code = 1;
            $this->sendData();
        }

    }

    /**
    * 下载文件
    * @author Jaxton 2018/01/10
    * /compact/compact/download_compact_file
    */
    public function download_compact_file(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        if(empty($params['type'])){
            $this->_msg = '缺少类型';
            $this->_code = 1;
            $this->sendData();
        }else{
            if($params['type']==4){//合同扫描件
                if(empty($params['compact_number'])){
                    $this->_msg = '缺少compact_number';
                    $this->_code = 1;
                    $this->sendData();
                }

                $data = $this->_modelObj->download_file($params);
                $file = isset($data['data_list']['file'])?$data['data_list']['file']:[];
                if(count($file) == 1){
                    $this->forceDownload(current($file));
                }else{
                    $this->download_compact_handle($data,$params['compact_number']);
                }

            }elseif($params['type']==3){//合同扫描件
                if(empty($params['pop_id']) || empty($params['pc_id'])){
                    $this->_msg = '缺少ID';
                    $this->_code = 1;
                    $this->sendData();
                }

                $data = $this->_modelObj->download_file($params);
                $file = isset($data['data_list']['file'])?$data['data_list']['file']:[];
                if(count($file) == 1){
                    $this->forceDownload(current($file));
                }else{
                    $this->download_compact_handle($data,$params['compact_number']);
                }

            }elseif($params['type']==1){
                $data = $this->get_pay_requisition('dwon');
            }elseif($params['type']==2){
                $paramsArr = array();
                if($params['type_data']==1){
                    $data = $this->_modelObj->get_pay_receipt($params);
                    if($data['status']==1){
                        $this->_msg = '';
                        $this->_code = 0;
                    }else{
                        $this->_msg = '付款回单为空';
                        $this->_code = 1;
                    }
                    $this->sendData();
                }elseif ($params['type_data']==2){
                    $data = $this->_modelObj->get_pay_receipt($params);
                    if($data['status']==1){
                        $paramsArr['status']=$data['status'];
                        $paramsArr['data_list']['file'][] = $data['data_list']['file'];
                        $this->download_compact_handle($paramsArr,$params['compact_number']);
                    }
                }
            }
        }
    }

    /**
     * 下载文件处理
     * @author Jaxton 2018/03/01
     * /compact/compact/download_compact_handle
     * @param  array $data
     * @param string $file_alias_name 文件别名
     * @throws Exception
     */
    public function download_compact_handle($data, $file_alias_name = null){
        $file = [];
        if($data['status'] == 1 && is_array($data['data_list']['file'])){
            $file      = $data['data_list']['file'];
            $file_name = isset($data['data_list']['file_name'])?$data['data_list']['file_name']:[];
        }
        if(empty($file)){
            $this->sendError(0, '文件不存在');
        }
        $this->load->library('php_zip');
        try{
            if(!empty($file_alias_name)){
                $filename = "compact_".$file_alias_name."_".date("YmdHis").".zip"; //下载的默认文件名
            }else{
                $filename = "compact_".date("YmdHis").".zip"; //下载的默认文件名
            }
            $webfront_path = dirname(dirname(APPPATH));
            $dfile = $webfront_path.'/webfront/download_csv/'.$filename;
            $image = $file;
            $file_name_tmp = [];// 文件对应的文件名
            foreach ($image as $key => $v) {
                $suffix = explode('.', basename($v));   //文件后缀
                if (empty($suffix)) continue;                    //获取后缀失败,跳过操作
                $suffix = '.' . array_pop($suffix);

                if(isset($file_name[$key]) and !empty($file_name[$key])){
                    $now_file_name = $file_name[$key].$suffix;
                    
                    // 重名文件修饰器
                    if(isset($file_name_tmp[$now_file_name])){
                        $file_name_tmp[$now_file_name] ++;

                        $embellish = '('.($file_name_tmp[$now_file_name]).')';// 修饰符，增加(1)字样
                        $now_file_name = $file_name[$key].$embellish.$suffix;

                    }else{
                        $file_name_tmp[$now_file_name] = 0;
                    }

                }else{
                    $now_file_name = '25'.$key.$suffix;
                }

                $this->php_zip->add_file(file_get_contents($v), '/compact/' . $now_file_name);
            }
            //----------------------
            $this->php_zip->output($dfile);
            // 下载文件
            ob_clean();
            header('Pragma: public');
            header('Last-Modified:'.gmdate('D, d M Y H:i:s') . 'GMT');
            header('Cache-Control:no-store, no-cache, must-revalidate');
            header('Cache-Control:pre-check=0, post-check=0, max-age=0');
            header('Content-Transfer-Encoding:binary');
            header('Content-Encoding:none');
            header('Content-type:multipart/form-data');
            header('Content-Disposition:attachment; filename="'.$filename.'"'); //设置下载的默认文件名
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
     * 强制下载 图片（JAVA文件服务系统 打开即是下载文件）
     * @param        $file_path
     */
    public function forceDownload($file_path){
        header('HTTP/1.1 301 Moved Permanently');//发出301头部
        header('Location:'.$file_path);//跳转网址
        exit;

        if($file_name){
            $url_arr   = explode('.', $file_path);
            $file_type = end($url_arr);
            $file_name = $file_name.'.'.$file_type;
        }else{
            $file_name = basename($file_path);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.$file_name.'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }

    /**
    * 打印合同
    * @author Jaxton 2018/01/30
    * /compact/compact/print_compact
    */
    public function print_compact(){
        $params = $this->_requestParams;
        //print_r($params);die;
        $data = $this->_modelObj->get_print_compact_data($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

       
    }
    /**
    * 打印合同(获取模板)
    * @author Jaxton 2018/02/25
    * /compact/compact/print_compact_tmp
    */
    public function print_compact_tmp(){
        try {  
            $this->_init_request_param("GET");
            $params = $this->_requestParams;
            //print_r($params);die;
            $data = $this->_modelObj->print_compact_tmp($params);

            if($data['status']){
                $html = $data['data_list']['html'];
                $key  = $data['data_list']['key'];
                if($key == 'tax_compact'){
                    $css_file_name = 'taxRefundTemplate.css';
                }else{
                    $css_file_name = 'nonRefundableTemplate.css';
                }

                $css1 = VIEWPATH_PRINT_TEMPLATE."print_template/mycss/".$css_file_name;
                $style1 = file_get_contents($css1);

                $html = str_replace('<body>','<style>'.$style1.'</style><body>',$html);
                $data_list=[
                    'data_list'=>[$html],
                ];
                $this->sendData($data_list); 
            }
         } catch (Exception $exc) {
             $this->sendError(-1, $exc->getMessage());
         }     
       
    }

    public function download_compact_excel(){

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->download_compact_html($params);
        try{
            if(!empty($data['data_list'])){
                $this->load->model('compact/Print_pdf_model');
                $html = $data['data_list']['html'];
                $key = $data['data_list']['key'];

                if($key == 'tax_compact'){
                    $css_file_name = 'taxRefundTemplate.css';
                }else{
                    $css_file_name = 'nonRefundableTemplate.css';
                }
                $file_name = isset($params['compact_number']) ? $params['compact_number'] :  date('Ymdhis');

                header("Cache-Control:public");
                header("Pragma:public");

                header( "Content-Type: application/vnd.ms-excel; name='excel'" );//表示输出的类型为excel文件类型
                header( "Content-type: application/octet-stream" );//表示二进制数据流，常用于文件下载
//
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
    * 下载合同
    * @author Jaxton 2018/02/26
    * /compact/compact/download_compact
    */
    public function download_compact(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->download_compact($params);
        try{
            if(!empty($data['data_list'])){
                $this->load->model('compact/Print_pdf_model');
                $html = $data['data_list']['html'];
                $key = $data['data_list']['key'];

                if($key == 'tax_compact'){
                    $css_file_name = 'taxRefundTemplate.css';
                }else{
                    $css_file_name = 'nonRefundableTemplate.css';
                }
                $file_name = isset($params['compact_number']) ? $params['compact_number'] :  date('Ymdhis');
                //设置PDF页脚内容
                $footer = "<p style='text-align:center'>第<span>{PAGENO}</span>页,共<span>{nb}</span>页</p>";
                $this->Print_pdf_model->writePdf($html,'',$file_name,'D',$css_file_name,'',$footer);
            }else{
                echo '未获取到打印数据';exit;
            }
            
        }catch(Exception $e){
            echo $e->getMessage();exit;
        }
        
    }

    /**
    *pdf——test
    */
    public function pdf_test(){
        $this->load->model('print_pdf_model');
        $this->print_pdf_model->print_pdf('<h1>test</h1>');
    }

    /**
    * 获取付款申请书数据
    * /compact/compact/get_pay_requisition
    * @author Jaxton 2019/02/21
    */
    public function get_pay_requisition($type){
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_pay_requisition($params);
        try{
            if(!empty($data['data_list'])){
                $handle_type=($type=='see')?'I':'D';
                $this->load->model('compact/Print_pdf_model');
             //   $data_list= str_replace('textarea', 'span', $data['data_list']);
                $this->Print_pdf_model->writePdf($data['data_list'],'',date('Ymdhis'),$handle_type);
            }else{
                if($type=='see'){
                    $this->sendError(-1, $data['errorMess']);
                }else{
                    $this->download_compact_handle($data['status']=0);
                }
            }
        }catch(Exception $e){
            $this->sendError(-1, $e->getMessage());
        }
        
    }

    /**
    * 获取付款回单
    * /compact/compact/get_pay_receipt
    * @author Jaxton 2019/02/21
    */
    public function get_pay_receipt(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_pay_receipt($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);        
    }

    /**
    * 生成pdf
    * /compact/compact/generate_pdf
    * @author Jaxton 2019/02/21
    */
    public function generate_pdf(){
        $params = $this->_requestParams;
        if(empty($params['html_str'])){
            $this->_msg = '缺少HTML内容';
            $this->_code = 1;  
            $this->sendData();  
        }else{
            $this->load->model('compact/Print_pdf_model');
            $this->Print_pdf_model->print_pdf($params['html_str']);
        }
    }
 
    /**
     * 查看付款申请书
     * @author harvin 2019-3-20
     */
    public function pay_requisition_see(){
         try {
            $params = $this->_requestParams;
            $data = $this->_modelObj->get_pay_requisition_see($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 批量下载
     */
    public function get_compact_detail_file(){
        $this->load->model('compact/Print_pdf_model');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_compact_detail_file($params);
        if(isset($params['type']) and  $params['type']==1){
            if(isset($data['status']) and !empty($data['data_list'])){
                $returnData = array(
                    'status' => 1
                );
                http_response($returnData);
            }
        }

        if(isset($data['status']) and !empty($data['data_list'])){
            $webfront_path = dirname(dirname(APPPATH));
            $filename = date('YmdHis');
            $dfilem_delete = $webfront_path.'/webfront/download_csv/compact/';
            $dfilem = $webfront_path.'/webfront/download_csv/compact/';
            $dfile = iconv("UTF-8", "GBK", $dfilem.$filename);
            if(!file_exists($dfile))mkdir($dfile);
            //付款申请书生成PDF 保存到目录
            $zipcreated = $dfilem."compact_".date("YmdHis").".zip";
            foreach ($data['data_list'] as $val){
                $dir = iconv("UTF-8", "GBK", $dfile.'/'.$val['compact_number']);
                if(!file_exists($dir))mkdir($dir);
                if(!isset($params['download_type'])){
                    if(!empty($val['requisition_list'])){
                        $this->Print_pdf_model->fPdf($val['requisition_list'],$dir,$val['requisition_number']);
                    }
                }
                if(!empty($val['compact_list'])){
                    //下载合同扫描件
                    foreach ($val['compact_list'] as $key => $value){
                        $this->GrabImage($value,$dir,$val['requisition_number'].'-'.($key+1));
                        }
                    }
                }
            //压缩目录,并下载压缩包（要压缩的目录$dfile,要生成的压缩包$zipcreated）
            $this->_createZip($dfile,$zipcreated);
            $this->deldir($dfilem_delete);
        }else{
            if (isset($params['download_type']) and  $params['download_type']==1){
                $this->sendError(-1,'导出失败[财务付款状态下的付款申请书、以及所有的合同扫描件为空]!');
            }else{
                $this->sendError(-1,'导出失败[待财务付款状态下的付款申请书、以及所有的合同扫描件为空]!');
            }

        }
    }
    /**
     * 下载图片
     * @param $url
     * @param string $dir
     * @param string $filename
     * @return bool|string
     */
    public function GrabImage($url, $dir='', $filename=''){
        if(empty($url)){
            return false;
        }
        $ext = strrchr($url, '.');
        if($ext != '.gif' && $ext != ".jpg" && $ext != ".bmp" && $ext != ".png"){
            echo "格式不支持！";
        }
        //为空就当前目录
        if(empty($dir))$dir = './';
        $dir = realpath($dir);
        //目录+文件
        if($ext == '.pdf'){
            $filename = $dir . (empty($filename) ? '/'.time().$ext : '/'.$filename.'.pdf');
        }else{
            $filename = $dir . (empty($filename) ? '/'.time().$ext : '/'.$filename.'.jpg');
        }
        //开始捕捉
        ob_start();
        readfile($url);
        $img = ob_get_contents();
        ob_end_clean();
        strlen($img);
        $fp2 = fopen($filename , "a");
        fwrite($fp2, $img);
        fclose($fp2);
        return $filename;
    }

    /**
     * 根据目录路径，生成压缩包，并下载
     * @param $dirPath|要压缩的目录（带路径）
     * @param $zipName|生成的压缩包名（带路径）
     */
    private function _createZip($dirPath,$zipName)
    {
        $zip = new ZipArchive();
        //ZipArchive::CREATE没有即是创建
        $zip->open($zipName, ZipArchive::CREATE);//打开压缩包
        $this->_zipDir($dirPath, '', $zip);
        $zip->close();//关闭压缩包
        if(file_exists($zipName)){
            //下载压缩包
            $this->_downloadZip($zipName);
        }

    }

    /**
     * 递归压缩目录
     * @param $real_path |要压缩的实际目录路径（绝对路径）
     * @param $zip_path |压缩过程中要添加的目录
     * @param $zip|ZipArchive对象
     */
    private function _zipDir($real_path, $zip_path, &$zip)
    {
        $sub_zip_path = empty($zip_path) ? '' : $zip_path . DIRECTORY_SEPARATOR;
        //读取目录
        if ($dh = opendir($real_path)) {
            while (($file = readdir($dh)) !== false) {
                if (in_array($file, array('.', '..', null))) continue; //无效文件，跳过
                if (is_dir($real_path . DIRECTORY_SEPARATOR . $file)) {//是目录
                    $zip->addEmptyDir($sub_zip_path . $file);
                    $this->_zipDir($real_path . DIRECTORY_SEPARATOR . $file, $sub_zip_path . $file, $zip);
                } else { //是文件
                    $zip->addFile($real_path . DIRECTORY_SEPARATOR . $file, $sub_zip_path . $file);
                }
            }
        }
    }

    /**
     * 下载压缩包，并删除服务器压缩包
     * @param $zipName|要下载的压缩包文件名
     */
    private function _downloadZip($zipName){
        //输出压缩文件提供下载
        $fp = fopen($zipName, "r");
        $file_size = filesize($zipName);//获取文件的字节
        //下载文件需要用到的头
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:" . $file_size);
        Header("Content-Disposition: attachment; filename=" . basename($zipName));
        $buffer = 1024; //设置一次读取的字节数，每读取一次，就输出数据（即返回给浏览器）
        $file_count = 0; //读取的总字节数
        //向浏览器返回数据 如果下载完成就停止输出，如果未下载完成就一直在输出。根据文件的字节大小判断是否下载完成
        while (!feof($fp) && $file_count < $file_size) {
            $file_con = fread($fp, $buffer);
            $file_count += $buffer;
            echo $file_con;
        }
        fclose($fp);
        //下载完成后删除压缩包，临时文件夹
        if ($file_count >= $file_size) {
            @unlink($zipName);
        }
    }

    private function deldir($path){
        //如果是目录则继续
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach($p as $val){
                //排除目录中的.和..
                if($val !="." && $val !=".."){
                    //如果是目录则递归子目录，继续操作
                    if(is_dir($path.$val)){
                        //子目录中操作删除文件夹和文件
                        $this->deldir($path.$val.'/');
                        //目录清空后删除空文件夹
                        @rmdir($path.$val.'/');
                    }else{
                        //如果是文件直接删除
                        unlink($path.$val);
                    }
                }
            }
        }
    }

    /**
     * 供应商门户-合同审核日志
     * /compact/compact/get_compact_audit_log
     */
    public function get_compact_audit_log(){
        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        try {
            $data = $this->_modelObj->get_compact_audit_log($params);

            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }

            $this->sendData($data);
        }
        catch ( Exception $exp )
            {
            $this->sendError($exp->getCode(), $exp->getMessage());
            }

        }

    /**
     * 供应商门户-合同审核状态
     * /compact/compact/get_audit_compact_status
     */
    public function get_audit_compact_status(){
        $this->_init_request_param("POST");
        try {
        $data = $this->_modelObj->get_audit_compact_status($this->_requestParams);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
        }
        catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     * 获取列表信息
     */
    public function get_compact_export(){
        set_time_limit(0);
        try {
            $this->_init_request_param("POST");
            file_put_contents(get_export_path('test_log').'log_20201127.txt','WEB '.json_encode($this->_requestParams).PHP_EOL,FILE_APPEND);
            $data= $this->_modelObj->get_compact_export($this->_requestParams);
            file_put_contents(get_export_path('test_log').'log_20201127.txt','WEB '.json_encode($data).PHP_EOL,FILE_APPEND);
            $this->sendData($data);
        } catch (Exception $e) {
            file_put_contents(get_export_path('test_log').'log_20201127.txt','WEB 导出出现异常'.PHP_EOL,FILE_APPEND);
            $this->sendError($e->getCode(), $e->getMessage()."导出出现异常!");
        }
    }


    /**
     * 批量下载合同
     */
    public function batch_download_compact(){
        set_time_limit(0);
        try {
            $this->_init_request_param("POST");
            $data= $this->_modelObj->batch_download_compact($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage()."导出出现异常!");
        }
    }
}