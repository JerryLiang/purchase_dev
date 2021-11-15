<?php
require APPPATH . 'core/MY_ApiBaseController.php';
require APPPATH . 'libraries/File_operation.php';
/* * *
 * 图片上传控制器
 */
class Upload_receipt extends MY_ApiBaseController {
    /**
     * 上传多张文件 （一次一张）
     * $_FILES['userfile'] 上传参数
     *@author harvin 2019-2-13 
     * /api/finance/upload_receipt/do_upload
     **/
    public function do_upload() {
       $upload = new File_operation();
       $this->load->model('finance/Upload_receipt_model');
        //缓存本地
        $res = $upload->upload_file($_FILES['images'], 'receipt');
        if (isset($res['errorCode']) && $res['errorCode'] == 1) {
            $path =dirname(dirname(dirname(dirname(dirname(__FILE__))))).$res['file_info']['file_path'];
            $result= $this->Upload_receipt_model->upload_picture($path,$res['file_info']['file_name']);
            $result= json_decode($result, TRUE);
            if($result && isset($result['code']) && $result['code']==1000){
                //删除本地
                 @unlink($path);
                $this->sendData(['url_img'=>$result['data'][0]['fullUrl']]);
            }else{
                 $data = ['status' => 0, 'errorMess' =>"上传fastdfs文件系统失败"];
                 $this->sendData($data);
            }
        } else {
            $data = ['status' => 0, 'errorMess' => isset($res['errorMess']) ? $res['errorMess'] : '请求参数错误'];
           $this->sendData($data);
        }
    }


    /**
     * 上传多张文件  （支持一次多张）
     * @author Jolon
     * @param array $_FILES['userfile'] 上传参数
     * @url /api/finance/upload_receipt/do_upload_multi
     */
    public function do_upload_multi() {
        $upload = new File_operation();
        $this->load->model('finance/Upload_receipt_model');

        if(!isset($_FILES['images']) or empty($_FILES['images'])){
            $this->_code = '501';
            $this->_msg = '图片资源参数缺失';
            $this->sendData();
        }
        $images  = $_FILES['images'];
        $list_id = array_keys($images['name']);
        if(empty($list_id) or !is_array($list_id)){
            $this->_code = '501';
            $this->_msg  = '图片资源参数错误';
            $this->sendData();
        }

        $url_img_success_list = [];
        $url_img_error_list = [];
        foreach($list_id as $value){
            $image_now = [
                'name'     => $images['name'][$value],
                'type'     => $images['type'][$value],
                'tmp_name' => $images['tmp_name'][$value],
                'error'    => $images['error'][$value],
                'size'     => $images['size'][$value],
            ];

            //缓存本地
            $res = $upload->upload_file($image_now, 'product_scree');
            if (isset($res['errorCode']) && $res['errorCode'] == 1) {
                $path =dirname(dirname(dirname(dirname(dirname(__FILE__))))).$res['file_info']['file_path'];
                $result= $this->Upload_receipt_model->upload_picture($path);
                $result= json_decode($result, TRUE);
                if($result && isset($result['code']) && $result['code']==1000){
                    //删除本地
                    @unlink($path);
                    $url_img_success_list[] = $result['data']['fullUrl'];
                }else{
                    $url_img_error_list[] = '上传fastdfs文件系统失败';
                }
            } else {
                $url_img_error_list[] = isset($res['errorMess']) ? $res['errorMess'] : '请求参数错误';
            }
        }

        $this->sendData(['url_img_success' => $url_img_success_list, 'url_img_error' => $url_img_error_list]);

    }


    /**
     * 上传 备货单 文件到本地
     * $_FILES['file'] 上传参数
     * @author harvin 2019-2-13
     */
    public function do_local_upload(){
        $file_path = dirname(APPPATH). '/download_csv/suggest';
        $file_path = str_replace("\\",'/',$file_path);
        $upload = new File_operation();
        $res    = $upload->upload_file2($_FILES['file'], $file_path);
        if(isset($res['errorCode']) && $res['errorCode'] == 1){
            $path = $res['file_info']['file_path'];
            $this->sendData(['status' => 1, 'file_path' => $path,'errorMess' => '']);
        }else{
            $data = ['status' => 0, 'errorMess' => isset($res['errorMess']) ? $res['errorMess'] : '文件上传失败'];
            $this->sendData($data);
        }
    }
    /**
     * 上传 备货单 文件到本地
     * $_FILES['file'] 上传参数
     * @author harvin 2019-2-13
     */
    public function do_package_upload(){
        $file_path = dirname(APPPATH). '/download_csv/package';
        $file_path = str_replace("\\",'/',$file_path);
        $upload = new File_operation();
        $res    = $upload->upload_file2($_FILES['file'], $file_path,'package');
        if(isset($res['errorCode']) && $res['errorCode'] == 1){
            $path = $res['file_info']['file_path'];
            $this->sendData(['status' => 1, 'file_path' => $path,'errorMess' => '']);
        }else{
            $data = ['status' => 0, 'errorMess' => isset($res['errorMess']) ? $res['errorMess'] : '文件上传失败'];
            $this->sendData($data);
        }
    }

    /**
     * 上传合同扫描件
     * @author Manson
     * /api/finance/upload_receipt/upload_compact
     */
    public function upload_compact() {
        $upload = new File_operation();
        $this->load->model('finance/Upload_receipt_model');
        //验证文件名称
        $img = $_FILES['images'];
        $compact_number = $this->input->get_post('compact_number');//合同单号
        $file_number = $this->input->get_post('file_number');//文件序号

        // 验证文件名是否符合规则
        if(stripos($img['name'],$compact_number) !== 0){
            $data = ['status' => 0, 'errorMess' => isset($res['errorMess']) ? $res['errorMess'] : '请检查上传的文件,文件名要以合同号开头'];
            $this->sendData($data);
        }

        $file_name = sprintf('%s-%s',$img['name'],$file_number);

        //缓存本地
        $res = $upload->upload_file($img, 'receipt',$file_name);
        if (isset($res['errorCode']) && $res['errorCode'] == 1) {
            $path =dirname(dirname(dirname(dirname(dirname(__FILE__))))).$res['file_info']['file_path'];
            $result= $this->Upload_receipt_model->upload_picture($path,$res['file_info']['file_name']);
            $result= json_decode($result, TRUE);
            if($result && isset($result['code']) && $result['code']==1000){
                //删除本地
                @unlink($path);
                $this->sendData(['url_img'=>$result['data'][0]['fullUrl']]);
            }else{
                $data = ['status' => 0, 'errorMess' =>"上传fastdfs文件系统失败"];
                $this->sendData($data);
            }
        } else {
            $data = ['status' => 0, 'errorMess' => isset($res['errorMess']) ? $res['errorMess'] : '请求参数错误'];
            $this->sendData($data);
        }
    }

    /**
     * 上传文件到文件服务器
     * /api/finance/upload_receipt/upload_file
     * @author Justin
     **/
    public function upload_file()
    {
        $file_path = $this->input->post('file_path');//上传文件的本地服务器路径+文件名称

        if (file_exists($file_path)) {
            $this->load->model('finance/Upload_receipt_model');
            $result = $this->Upload_receipt_model->upload_picture($file_path, basename($file_path));
            $result = json_decode($result, TRUE);
            if ($result && isset($result['code']) && $result['code'] == 1000) {
                //删除本地
                @unlink($file_path);
                $this->sendData(['url_img' => $result['data'][0]['fullUrl']]);
            } else {
                $data = ['status' => 0, 'errorMess' => "上传文件系统失败"];
                $this->sendData($data);
            }
        } else {
            $data = ['status' => 0, 'errorMess' => '要上传的文件不存在'];
            $this->sendData($data);
        }
    }


    /**
     * 上传 供应商余额表 文件到本地
     */
    public function upload_balance_order(){
        $file_path = dirname(APPPATH). '/download_csv/balance_order';
        $file_path = str_replace("\\",'/',$file_path);
        $upload = new File_operation();
        $res    = $upload->upload_file2($_FILES['file'], $file_path);
        if(isset($res['errorCode']) && $res['errorCode'] == 1){
            $path = $res['file_info']['file_path'];
            $this->sendData(['status' => 1, 'file_path' => $path,'errorMess' => '']);
        }else{
            $data = ['status' => 0, 'errorMess' => isset($res['errorMess']) ? $res['errorMess'] : '文件上传失败'];
            $this->sendData($data);
        }
    }
}
