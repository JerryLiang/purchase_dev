<?php
/**
 * 合同数据库模型类
 * User: Jaxton
 * Date: 2019/01/08 10:23
 */

class Compact_list_model extends Api_base_model {


    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
        
    }

    /**
     * 根据合同ID获取合同信息
     * @param array $params
     * @return array
     * @author Jaxton 2019/01/08
     */
    public function get_compact_by_id($params){

        $url = $this->_baseUrl . $this->_getCompactByIdUrl;
        return $this->request_http($params,$url);

    }

    /**
     * 一键生成进货单（支持勾选和查询条件）
     * @param array $params
     * @return array
     * @author Jaxton 2019/01/08
     */
    public function one_key_compact_create($params){

        $url = $this->_baseUrl . $this->_oneKeyCompactCreateUrl;
        return $this->request_http($params,$url);

    }

    /**
    * 获取合同列表
    * @param $params
    * @param $offset
    * @param $limit
    * @return array
    * @author Jaxton 2019/01/08
    */
    public function get_compact_list($params){

        $url = $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params,$url);
        
    }

    
    /**
    * 获取合详情
    * @param $compact_number
    * @return array
    * @author Jaxton 2019/01/08
    */
    public function get_compact_detail($params){
        if (empty($params['compact_number'])) {
            $this->_errorMsg = '缺少合同编号';
            return;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_detailUrl;
        return $this->request_http($params,$url,'GET',false);


    }

    

    /**
    * 查看文件
    */
    public function see_compact_file($params){
        if($params['type'] == 4){
            if (empty($params['compact_number'])) {
                $this->_errorMsg = '缺少compact_number';
                return;
            }
        }else{
            if (empty($params['pop_id']) || empty($params['pc_id'])) {
                $this->_errorMsg = '缺少ID';
                return;
            }
        }
        $url =$this->_baseUrl . $this->_downloadUrl ."?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

     /**
    * 获取打印合同数据
    * @param $compact_number
    * @return array   
    * @author Jaxton 2019/01/30
    */
    public function get_print_compact_data($params){
        $url = $this->_baseUrl . $this->_printCompactUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    public function print_compact_tmp($params){
        $url = $this->_baseUrl . $this->_printCompactTmpUrl ."?" . http_build_query($params);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
    * 上传合同扫描件
    */
    public function upload_compact_file($params){
        require APPPATH . 'libraries/File_operation.php';
        $this->load->model('finance/Upload_receipt_model');
        $upload_model = new File_operation();
        //文件上传
        $upload_result = $upload_model->upload_file($params['upload_file'], 'receipt');
        if ($upload_result['errorCode']) {//上传成功，保存文件路径
            $path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . $upload_result['file_info']['file_path'];
            $result = $this->Upload_receipt_model->doUploadFastDfs('file',$path);
            $result = json_decode($result, TRUE);
            $result['data'] = $result['data'][0];
            if ($result && isset($result['code']) && $result['code'] == 1000) {            
                //删除本地
                @unlink($path);
                $file_info = [
                    'file_path' => $result['data']['fullUrl'],
                    'file_type' => $result['data']['fileType'],
                ];
                $url       = $this->_baseUrl.$this->_uploadUrl;
                $post_data = [
                    'pop_id'           => $params['pop_id'],
                    'pc_id'            => $params['pc_id'],
                    'file_info'        => $file_info,
                    'uid'              => $params['uid']
                ];
                $add_file_result = $this->request_http($post_data, $url, 'GET', false);
                if (isset($add_file_result['status']) && $add_file_result['status']=1) {
                     return ['errorCode' => true, 'file_info' => $result['data']['fullUrl']];
                } else {
                    return [
                        'errorCode' => false,
                        'errorMess' => '上传成功，文件路径保存失败'
                    ];
                }
            } else {
                return ['errorCode' => false, 'errorMess' => "上传fastdfs文件系统失败"];
                  
            }
            //return $add_file_result;
        } else {//上传失败，返回错误信息
            return $upload_result;
        }
    }

    /**
     * 上传原始合同扫描件
     * @author Jolon 2018/01/09
     * @param $params
     * @return array|mixed|null
     */
    public function upload_compact_original_scan_file($params){
        $url = $this->_baseUrl . $this->_uploadCompactOriginalScanFileUrl;
        return $this->request_http($params,$url);
    }

    /**
     * 上传原始合同扫描件
     * @author Jolon 2018/01/09
     * @param $params
     * @return array|mixed|null
     */
    public function batch_upload_compact_original_scan_file($params){
        $url = $this->_baseUrl . $this->_batchUploadCompactOriginalScanFileUrl;
        return $this->request_http($params,$url);
    }

    /**
    * download_file
    */
    public function download_file($params){
        // print_r($params);die;
        // if(empty($params['pop_id']) || empty($params['pc_id'])){
        //     $this->_errorMsg = '缺少ID';return;           
        // }
        // die;
        $url=$this->_baseUrl . $this->_downloadUrl;
        return $this->request_http($params,$url,'GET',false);

    }

    /**
    * 获取付款申请书数据
    * @param $params
    * @return array   
    * @author Jaxton 2019/02/21
    */
    public function get_pay_requisition($params){
        $url=$this->_baseUrl . $this->_requisitionUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
    * 获取付款回单
    * @param $params
    * @return array   
    * @author Jaxton 2019/02/21
    */
    public function get_pay_receipt($params){
        $url=$this->_baseUrl . $this->_receiptUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
    * 下载合同
    * @param $params
    * @return array   
    * @author Jaxton 2019/02/26
    */
    public function download_compact($params){
        $url=$this->_baseUrl . $this->_downloadCompactUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    public function download_compact_html($params){
        $url=$this->_baseUrl . $this->_downloadExcelCompactUrl;
        return $this->request_http($params,$url,'GET',false);

    }
    
    /**
     * 查看付款申请书
     * @param  $params
     * **/
    
   public function get_pay_requisition_see($params){
      $url = $this->_baseUrl . $this->_requisition_seeApi;
      $re = $this->httrequest($params, $url);
      if($re['status']==1){
            $result['status']=1;
            $result['data_list']=$re['data_list']['data'];
      }else{
       $result['status']=0; 
       $result['errorMess']=$re['errorMess'];   
      }
      return $result;


   }

    /**
     *根据合同单号获取合同的付款申请书与合同扫描件
     * @param $params
     * @return array|mixed|null
     */
    public function get_compact_detail_file($params){
        $url=$this->_baseUrl . $this->_detail_file_seeApi;
        return $this->request_http($params,$url,'GET',false);
    }


    public function get_compact_audit_log($params){

        $url = $this->_baseUrl . $this->_compactAuditLogUrl;
//        return $this->request_http($params,$url);
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        $result['errorMess'] = $this->_errorMsg;
        return $result;

    }
    public function get_audit_compact_status($params){

        $url = $this->_baseUrl . $this->_auditCompactStatusUrl;
//        return $this->request_http($params,$url);
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        $result['errorMess'] = $this->_errorMsg;
        return $result;
    }


//  public  function zip($dir_path, $zipName)
//    {
//        $relationArr = array(
//            $dir_path => array(
//                'originName' => $dir_path,
//                'is_dir' => true,
//                'children' => array()
//            )
//        );
//
//        $key = array_keys($relationArr);
//        $val = array_values($relationArr);
//        $this->modifiyFileName($dir_path, $relationArr[$dir_path]['children']);
//        $zip = new ZipArchive();
//        //ZIPARCHIVE::CREATE没有即是创建
//        $zip->open($zipName, ZipArchive::CREATE);
//        $this->zipDir($key[0], '', $zip, $val[0]['children']);
//        $zip->close();
//        $this->restoreFileName($key[0], $val[0]['children']);
//        return true;
//    }
//
//    public function zipDir($real_path, $zip_path, &$zip, $relationArr){
//        $sub_zip_path = empty($zip_path) ? '' : $zip_path . '\\';
//        if (is_dir($real_path)) {
//            foreach ($relationArr as $k => $v) {
//                if ($v['is_dir']) {  //是文件夹
//                    $zip->addEmptyDir($sub_zip_path . $v['originName']);
//                    $this->zipDir($real_path . '\\' . $k, $sub_zip_path . $v['originName'], $zip, $v['children']);
//                } else { //不是文件夹
//                    $zip->addFile($real_path . '\\' . $k, $sub_zip_path . $k);
//                    $zip->deleteName($sub_zip_path . $v['originName']);
//                    $zip->renameName($sub_zip_path . $k, $sub_zip_path . $v['originName']);
//                }
//            }
//        }
//    }
//    private function modifiyFileName($path, &$relationArr){
//        if (!is_dir($path) || !is_array($relationArr)) {
//            return false;
//        }
//        if ($dh = opendir($path)) {
//            $count = 0;
//            while (($file = readdir($dh)) !== false) {
//                if(in_array($file,array('.', '..', null))) continue; //无效文件，重来
//                if (is_dir($path . '\\' . $file)) {
//                    $newName = md5(rand(0, 99999) . rand(0, 99999) . rand(0, 99999) . microtime() . 'dir' . $count);
//                    $relationArr[$newName] = array(
//                        'originName' => iconv('GBK', 'UTF-8', $file),
//                        'is_dir' => true,
//                        'children' => array()
//                    );
//                    rename($path . '\\' . $file, $path . '\\' . $newName);
//                    $this->modifiyFileName($path . '\\' . $newName, $relationArr[$newName]['children']);
//                    $count++;
//                } else {
//                    $extension = strchr($file, '.');
//                    $newName = md5(rand(0, 99999) . rand(0, 99999) . rand(0, 99999) . microtime() . 'file' . $count);
//                    $relationArr[$newName . $extension] = array(
//                        'originName' => iconv('GBK', 'UTF-8', $file),
//                        'is_dir' => false,
//                        'children' => array()
//                    );
//                    rename($path . '\\' . $file, $path . '\\' . $newName . $extension);
//                    $count++;
//                }
//            }
//        }
//    }
//    private  function restoreFileName($path, $relationArr){
//        foreach ($relationArr as $k => $v) {
//            if (!empty($v['children'])) {
//                $this->restoreFileName($path . '\\' . $k, $v['children']);
//                rename($path . '\\' . $k, iconv('UTF-8', 'GBK', $path . '\\' . $v['originName']));
//            } else {
//                rename($path . '\\' . $k, iconv('UTF-8', 'GBK', $path . '\\' . $v['originName']));
//            }
//        }
//    }



    /**
     * 获取列表数据
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function get_compact_export($get){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_getCompactExportUrl . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 批量下载合同
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function batch_download_compact($get){
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_batchDownloadCompact . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

}