<?php
/**
 * Created by PhpStorm.
 * User: 鲁旭
 * Date: 2019/02/21
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Purchase_file_down extends MY_ApiBaseController
{

    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_file_down_model');
        $this->_modelObj = $this->Purchase_file_down_model;
    }

    /**
     * PDF 下载
     * purchase/Purchase_file_down/get_barcode_pdf
     * @author luxu 2020-02-21
     */
    public function get_barcode_pdf() {
        $this->_init_request_param("GET");
        $clientData = $this->_requestParams;
        $now = date("Ymdhis",time());
        $clientDataIds = json_decode($clientData['data'],True);
//        if( isset($clientDataIds['flag']) || empty($clientDataIds['flag'])){
//
//            $this->sendData(['code'=>403,'_msg'=>'请传入下载标识']);
//        }
        $flag = $clientDataIds['flag'];
        $ids = NULL;
        foreach( $clientDataIds['data'] as $key=>$value)
        {

            if( isset($value['id'])){
                $ids .=$value['id'];
            }
        }
        if(NULL !=$ids){
            $now.="".$ids;
        }
        $data = $this->_modelObj->get_barcode_pdf($clientData);
        if (is_null($data) || empty($data['data_list'])) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = "没有下载文件";
            $this->sendData($data);
        }
        $filename = explode(".",basename($data['data_list']));
        if( $filename[1] == 'pdf') {
            //header('Location:'.$data['data_list']);

            $dataName = $clientDataIds['data'];
            $name = $dataName[0]['purchase_number'].'_'.$dataName[0]['sku'];
            if( $flag == 'barcode_pdf'){
                $name.="_TM";
            }elseif($flag=='label_pdf'){
                $name.="_BQ";
            }
            header('Content-type: application/pdf');
            $down_name = $name.".pdf";
        }else{


            header("Content-Type: application/zip");

            if( $flag == 'barcode_pdf'){
                $down_name = $now."_TM.zip";
            }elseif($flag=='label_pdf'){
                $down_name = $now."_BQ.zip";
            } else {
                $down_name = $now.".zip";

            }
           
        }
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=".$down_name);

        $get_file=@file_get_contents($data['data_list']);//
        echo $get_file;


    }


}