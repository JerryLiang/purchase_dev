<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2020-07-20
 * Time: 9:20
 */

class Lakala_api extends MY_API_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('finance/Lakala_pay_model');
    }

    /**
     * 批量代付文件批次查询接口
     * lakala_api/queryBatchFileStatus?cust_order_no=1213333
     */
    public function queryBatchFileStatus(){
        $cust_order_no = $this->input->get_post('cust_order_no');//批次
        $this->load->library('mongo_db');
        //获取待查询的数据
        if($cust_order_no){
            $order_list  = $this->Lakala_pay_model->getSelectInfo(['audit_status' => [2],'platform_type' => 'lakala','cust_order_no' => $cust_order_no]);// 只拉取 审核通过待抓取的
        }else{
            $order_list  = $this->Lakala_pay_model->getSelectInfo(['audit_status' => [2],'platform_type' => 'lakala']);// 只拉取 审核通过待抓取的
        }
        if($order_list){
            $this->load->library('Lakala');
            $lakala = new Lakala();
            $update_result = [];
            foreach ($order_list as $val) {
                //cust_order_no,file_batch_no,submit_date
                $result = $lakala->queryBatchFileStatus($val['file_batch_no'],$val['submit_date']);

                // 记录日志
                $insert_data = [];
                $insert_data['purchase_number']       = $val['file_batch_no'];
                $insert_data['create_time']           = date('Y-m-d H:i:s');
                $insert_data['operating_elements']    = json_encode($result);
                $this->mongo_db->insert('lakalaQueryBatchFileStatus', $insert_data);

                if(isset($result['encData']) and !empty($result['encData'])){
                    $data = $result['encData'];
                    if(!empty($data)){
                        $custNo      = $val['cust_order_no'];
                        $state       = $data['state'];
                        $fileBatchNo = $data['fileBatchNo'];
                        $finishTime  = '0000-00-00 00:00:00';
                        if((isset($data['finishDate']) and !empty($data['finishDate'])) and (isset($data['finishTime']) and !empty($data['finishTime']))){
                            $finishTime = $data['finishDate'].$data['finishTime'];
                            $finishTime = preg_replace('{^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(.*?)$}u', '$1-$2-$3 $4:$5:$6', $finishTime);
                        }
                        if($state == 1){// 1.批次处理完成 >>> 收款成功
                            $update_result[] = $this->Lakala_pay_model->update_lakala_pay_success($custNo,$fileBatchNo,$finishTime);
                        }elseif(in_array($state,[2,4,5,6])){// 批次处理失败 >>> 收款失败
                             $this->Lakala_pay_model->update_lakala_pay_fail($custNo,$fileBatchNo,$state);
                            $error_result[] = $custNo;
                        }
                    }
                }
            }
            echo '数据拉取成功!';
        }else{
            echo '数据为空!';
        }
    }

    /**
     * 批量代付解析回盘文件下载接口
     */
    public function downloadErrorBackFile(){
        $file_batch_no = $this->input->get_post('file_batch_no');//批次
        $submit_date = $this->input->get_post('submit_date');//提交时间
        if(empty($file_batch_no) or empty($submit_date)){
            $this->error_json('拉卡拉批次号与提交时间必填');
        }
        $this->load->library('Lakala');
        $lakala = new Lakala();
        $result = $lakala->downloadErrorBackFile($file_batch_no,$submit_date);
        $downloadFile = '';
        if(isset($result['downloadFile'])){
            $downloadFile = $result['downloadFile'];
        }else{
            $downloadFile = isset($result['message'])?$result['message']:'查询无结果!';
        }
        $this->success_json($downloadFile);
    }

    /**
     * 批量代付电子回单文件下载接口
     * lakala_api/downloadReceipt
     */
    public function downloadReceipt(){
        $file_batch_no = $this->input->get_post('file_batch_no');
        $return = $this->Lakala_pay_model->downloadReceipt($file_batch_no);// 100000859_7313510
        $this->success_json($return);
    }
}