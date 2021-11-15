<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";


/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Ceb_bank extends MY_API_Controller {

    private $cebBankApi = null;

    public function __construct() {
        parent::__construct();
        $this->load->library('Filedirdeal');
        $this->load->library('Upload_image');
        $this->load->library('CebBank');
        $this->cebBankApi = new CebBank();

        $this->load->model('finance/Lakala_pay_model');
    }

    /**
     * 保存日志的文件
     * @return string
     */
    public function get_log_file(){
        return get_export_path('cebbank/logs/').date('Ymd').'.csv';
    }

    /**
     * 回单文件本地文件夹
     * @return string
     */
    public function get_file_dir(){
        return rtrim(get_export_path('cebbank/downloads'),'/');
    }

    /**
     * 回单文件本地文件夹 - 按天划分
     * @return string
     */
    public function get_file_day_dir(){
        return get_export_path('cebbank/downloads/'.date('Y-m-d'));
    }

    /**
     * 验证光大交易记录是否转账成功（测试工具接口）
     * ceb_bank/test_b2e004003?cust_order_no=24507081552020102215105636032&order_number_1=24507081552020102215105689284
     */
    public function test_b2e004003(){
        $cust_order_no = $this->input->get_post('cust_order_no');
        $order_number_1 = $this->input->get_post('order_number_1');

        $result = $this->cebBankApi->single_order_b2e004003($cust_order_no,$order_number_1);
        if(empty($result['code'])){
            $error_list[] = $cust_order_no.':'.$result['errorMsg'];
            print_r($error_list);exit;
        }

        $response = $this->cebBankApi->curlPostXml($result['data']['url'],$result['data']['RequestXmlStr']);
        print_r($response);exit;

    }

    /**
     * 交易成功的单 生成电子回单文件（已配置计划任务执行）
     * ceb_bank/test_b2e005023
     */
    public function test_b2e005023(){
        set_time_limit(0);

        $this->test_refresh_pay_status();

        // 重 光大银行支付记录上获取 需要抓取电子回单的日期范围
        $list = $this->Lakala_pay_model->purchase_db
            ->select('min(submit_date) as startDate,max(submit_date) as endDate')
            ->where('platform_type','cebbank')
            ->where_in('audit_status',[2,4])
            ->where('order_number_2','')
            ->get('pay_lakala')
            ->row_array();

        $message_log = [];
        $message_log[] = '2、开始生成电子回单文件 test_b2e005023';
        if(empty($list['startDate'])){
            $message_log[] = 'test_b2e005023 已全部获取 回单号成功，无需重复获取!';
        }else{
            $startDate  = $list['startDate'];
            $endDate    = $list['endDate'];

            $result       = $this->cebBankApi->query_b2e005023($startDate,$endDate);
            $success_list = $result['success_list'];
            $error_list   = $result['error_list'];

            if($success_list){
                foreach($success_list as $FlowNumber => $BillNumber){
                    $this->Lakala_pay_model->purchase_db->where('file_batch_no',$FlowNumber)->update('pay_lakala',['order_number_2' => $BillNumber]);
                }
                $message_log[] = 'test_b2e005023 数据更新成功：'.count($success_list).'个';
            }else{
                $message_log[] = 'test_b2e005023 没有获取成功的数据';
            }
            $resp_list     = ['success_list' => $success_list,'error_list' => $error_list];
            $message_log[] = 'test_b2e005023 响应结果';
            $message_log[] = json_encode($resp_list,JSON_UNESCAPED_UNICODE);
        }
        $message_log[] = 'test_b2e005023 结束';
        $message_log[] = '<br/><br/>';

        // 显示&保存日志
        foreach($message_log as $value_log){
            echo $value_log.'<br/>';
        }
        file_put_contents($this->get_log_file(),implode("\n",$message_log).PHP_EOL,FILE_APPEND);


        $this->test_downloadFile_b2e005023();
        $this->test_update_pay_file();

        exit;
    }

    /**
     * 交易成功的单 下载电子回单文件
     * ceb_bank/test_downloadFile_b2e005023
     */
    public function test_downloadFile_b2e005023(){
        set_time_limit(0);

        // 获取 已有回单编号 但是未抓取文件的数据
        $waiting_list = $this->Lakala_pay_model->purchase_db->select('id,cust_order_no,file_batch_no,order_number_2')
            ->where_in('audit_status',[2,4])// 2.审核通过，4.收款成功
            ->where('voucherId','') // 文件凭证为空
            ->where('file_batch_no !=','')// 已获取到 回单编号
            ->where('platform_type','cebbank')
            ->get('pay_lakala',6) // 每次查询 6 条（5*6=30 秒）
            ->result_array();

        $message_log = [];
        $message_log[] = '3、开始下载电子回单文件 test_downloadFile_b2e005023';
        if(empty($waiting_list)){
            $message_log[] = '已全部获取 回单号成功，无需重复获取!';
        }else{
            $success_list = $error_list = [];
            foreach($waiting_list as $value){
                sleep(5);// 3、下载时控制频率每5秒1次
                $order_number_2 = $value['order_number_2'];
                $cust_order_no = $value['cust_order_no'];

                $queryCallB2e005023ResXml     = $this->cebBankApi->call_b2e005023('','',1,50,1,$order_number_2);
                if(empty($queryCallB2e005023ResXml['code'])){
                    $error_list[$cust_order_no] = $queryCallB2e005023ResXml['errorMsg'];
                    continue;
                }
                $queryCallB2e005023Resp       = $this->cebBankApi->curlPostXml($queryCallB2e005023ResXml['data']['url'],$queryCallB2e005023ResXml['data']['RequestXmlStr']);
                if(isset($queryCallB2e005023Resp['TransContent']['RespData']['fileName'])
                    and !empty($queryCallB2e005023Resp['TransContent']['RespData']['fileName']) ){
                    $fileName = $queryCallB2e005023Resp['TransContent']['RespData']['fileName'];

                    $result = $this->cebBankApi->downloadFile_b2e005023($fileName);
                    if(empty($result['code'])){
                        $error_list[] = $fileName.':'.$result['errorMsg'];
                    }

                    $response = $this->cebBankApi->curlPostXml($result['data']['url'],'');
                    // 读取数据内容
                    if(isset($response['success']) and !empty($response['success'])){
                        // 文件保存在前置机上，与系统不是同一台服务器，所以异步上传JAVA服务器
                        $fileFullPath = CEBBANK_DOWNLOADS_FILEPATH.date('Y-m-d')."\\".$fileName;

                        $update_arr = [
                            'voucherId' => $fileFullPath
                        ];
                        $this->Lakala_pay_model->purchase_db->where('cust_order_no',$cust_order_no)
                            ->update('pay_lakala',$update_arr);

                        $success_list[$cust_order_no] = '回单拉取并更新成功，文件路径=>'.$fileFullPath;
                        continue;
                    }else{
                        $error_list[$cust_order_no] = isset($response['success'])?$response['success']:'文件查询出错';
                        continue;
                    }
                }elseif(isset($queryCallB2e005023Resp['ReturnCode'])){
                    $error_list[$cust_order_no] = isset($queryCallB2e005023Resp['ReturnMsg'])?$queryCallB2e005023Resp['ReturnMsg']:(isset($queryCallB2e005023Resp['error'])?$queryCallB2e005023Resp['error']:'查询出错未知原因');
                    continue;
                }else{
                    $error_list[$cust_order_no] = '查询出错未知原因[响应数据解析失败]';
                    continue;
                }
            }

            $resp_list = ['success_list' => $success_list,'error_list' => $error_list];

            $message_log[] = 'test_downloadFile_b2e005023 响应结果';
            $message_log[] = json_encode($resp_list,JSON_UNESCAPED_UNICODE);
        }
        $message_log[] = 'test_downloadFile_b2e005023 结束';
        $message_log[] = '<br/><br/>';

        // 显示&保存日志
        foreach($message_log as $value_log){
            echo $value_log.'<br/>';
        }
        file_put_contents($this->get_log_file(),implode("\n",$message_log).PHP_EOL,FILE_APPEND);

        return true;
    }

    /**
     * 光大银行 - 刷新收款状态
     * @return bool
     */
    public function test_refresh_pay_status(){
        $pay_list = $this->Lakala_pay_model->purchase_db
            ->select('cust_order_no')
            ->where('platform_type','cebbank')
            ->where('audit_status',2)
            ->get('pay_lakala')
            ->result_array();

        $message_log = [];
        $message_log[] = '1、上传文件,更新请款单文件路径 test_refresh_pay_status';
        if(empty($pay_list)){
            $message_log[] = '无需待刷新收款状态的支付单';
        }else{
            foreach($pay_list as $pay_value){
                $result_flag = $this->Lakala_pay_model->refresh_pay_status($pay_value['cust_order_no']);
                if($result_flag === true){
                    $message_log[] = $pay_value['cust_order_no'].'：查询成功';
                }else{
                    $message_log[] = $pay_value['cust_order_no'].'：'.$result_flag;
                }
            }
        }

        $message_log[] = 'test_refresh_pay_status 结束';
        $message_log[] = '<br/><br/>';

        // 显示&保存日志
        foreach($message_log as $value_log){
            echo $value_log.'<br/>';
        }
        file_put_contents($this->get_log_file(),implode("\n",$message_log).PHP_EOL,FILE_APPEND);

        return true;
    }

    /**
     * 上传文件,更新请款单文件路径（来自光大银行前置机）
     * ceb_bank/test_update_pay_file
     */
    public function test_update_pay_file(){
        $file_list = $this->filedirdeal->readAllFile($this->get_file_dir(),'pdf',true,true,true);

        $message_log = [];
        $message_log[] = '4、上传文件,更新请款单文件路径 test_update_pay_file';
        if(empty($file_list)){
            $message_log[] = '无需待上传的文件';
        }else{
            foreach ($file_list as $filePath){
                $file_name      = basename($filePath);
                $file_name_len  = strlen($file_name);

                $payInfo = $this->Lakala_pay_model->purchase_db->select('cust_order_no')
                    ->where("RIGHT(voucherId,$file_name_len)", $file_name)
                    ->get('pay_lakala')
                    ->row_array();

                if(empty($payInfo)){
                    $message_log[] = '保存失败：未找到文件所属请款单：'.$file_name;
                    continue;
                }
                $cust_order_no = $payInfo['cust_order_no'];
                $requisition_number_list = $this->Lakala_pay_model->purchase_db->select('requisition_number')
                    ->where('cust_order_no',$cust_order_no)
                    ->get('pay_lakala_detail')
                    ->result_array();
                $requisition_number_list = array_column($requisition_number_list,'requisition_number');
                if(empty($requisition_number_list)){
                    $message_log[] = '获取请款单失败:'.$file_name;
                    continue;
                }

                $java_result = $this->upload_image->doUploadFastDfs('image', $filePath);
                if(isset($java_result['code']) and $java_result['code'] == 200){
                    $update_arr = [
                        'java_voucher_address' => $java_result['data']
                    ];
                    $this->Lakala_pay_model->purchase_db->where('cust_order_no',$cust_order_no)
                        ->update('pay_lakala',$update_arr);

                    // 请款单-回执地址
                    $this->Lakala_pay_model->purchase_db->where_in('requisition_number',$requisition_number_list)
                        ->update('purchase_order_pay',['voucher_address' => $java_result['data']]);

                    $message_log[] = '光大回执单文件上传JAVA服务器成功：'.$file_name;
                }else{
                    $message_log[] = '光大回执单文件上传JAVA服务器失败：'.$file_name;
                }
            }
        }
        $message_log[] = 'test_update_pay_file 结束';
        $message_log[] = '<br/><br/>';

        // 显示&保存日志
        foreach($message_log as $value_log){
            echo $value_log.'<br/>';
        }
        file_put_contents($this->get_log_file(),implode("\n",$message_log).PHP_EOL,FILE_APPEND);

        return true;
    }

    /**
     * 接收 光大银企通 软件同步的文件
     * @return string
     */
    public function test_file_upload(){
        if(isset($_FILES) and $_FILES){
            $filename = $_FILES['file']['name'];
            $tmpname = $_FILES['file']['tmp_name'];
            if(move_uploaded_file($tmpname, $this->get_file_day_dir().$filename)) {
                echo json_encode(['status' => 200,'message' => '文件同步成功']);
            }
            else {
                echo json_encode(['status' => 500,'message' => '文件同步保存失败']);
            }
        }else{
            echo json_encode(['status' => 500,'message' => '文件传输数据缺失']);
        }
        exit;
    }
}