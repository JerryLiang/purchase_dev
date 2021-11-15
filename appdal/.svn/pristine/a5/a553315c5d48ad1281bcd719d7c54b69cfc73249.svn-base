<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */


class Purchase_file_down extends MY_Controller
{

    /** @var Purchase_order_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_file_down_model');
        $this->_modelObj = $this->Purchase_file_down_model;
    }

    public function get_barcode_pdf()
    {

        $clientData = $this->input->get_post("data");
        $data = json_decode( $clientData,True);
        if( !isset($data['data']) || !isset($data['flag']))
        {
            $this->error_json('传入参数错误');
        }
        $skus = array_column($data['data'],'sku');
        $purchase_numbers = array_column( $data['data'],'purchase_number');
        $demand_keys_data = [];
        $ids = NULL;
        foreach( $data['data'] as $key=>$value)
        {
            if( isset($demand_keys_data[$value['demand_number']]))
            {
                $demand_keys_data[$value['demand_number']] =[];
            }
            $demand_keys_data[$value['demand_number']] =$value['sku']."-".$value['purchase_number'];
            if( isset($value['id'])){
                $ids .=$value['id'];
            }
        }
        $lable_flag = NULL;
        if( $data['flag'] == 'barcode_pdf'){
            $lable_flag = "barcode";
        }elseif($data['flag']=='label_pdf'){
            $lable_flag = "label";
        } else {
            $lable_flag = "combine_label";

        }
        $result = $this->_modelObj->get_barcode_pdf_data( $skus,$purchase_numbers,$demand_keys_data,$lable_flag);

        if( NULL == $result )
        {
            $this->error_json('暂无下载文件');
        }
        list($msec, $sec) = explode(' ', microtime());
        //$dir_name = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000).rand(1,10000);
        $dir_name = date("YmdHis",time())."".$ids;
        if( "barcode" == $lable_flag ){
            $dir_name.="_TM";
        }else{
            $dir_name.="_BQ";
        }

        // 如果客户端传入的备货单号只有一个情况下
        $zip_dir = NULL;
        try {
            if (count($data['data']) == 1) {
                $pdfs = array_values(array_map('array_shift', $result))[0];

                $demand_number = array_keys($demand_keys_data);
                $skus = array_values($result);
                if (strpos($pdfs, ",")) {
                    $pdfs = explode(",", $pdfs);
                    $zip_dir = $this->_modelObj->down_file_pdf($pdfs,$demand_number[0],$dir_name,$skus[0]['sku'],$lable_flag);
                    $zip_dir = $this->_modelObj->build_folder($dir_name,$lable_flag);
                } else {
                    $zip_dir = $pdfs;
                    $this->success_json($zip_dir);
                    die();
                }
            } else {

                if( !empty($result) )
                {
                    $is_dir_name = False;
                    foreach( $result as $key=>$value)
                    {

                        if(isset($value[$lable_flag]) && !empty($value[$lable_flag])) {

                            $is_dir_name = True;
                            $pdfs = explode(",", $value[$lable_flag]);
                            $this->_modelObj->down_file_pdf($pdfs, $value['purchase_number'], $dir_name,$value['sku'],$lable_flag);
                        }
                    }
                    if(True == $is_dir_name) {
                        $zip_dir = $this->_modelObj->build_folder($dir_name,$lable_flag);
                    }
                }
            }
            if(empty($zip_dir)){
                $this->error_json('暂无下载文件');
            }
            $zip_dir = CG_SYSTEM_WEB_FRONT_IP.Ltrim(str_replace(dirname(dirname(APPPATH)),'',$zip_dir),'/');
            $zip_dir = str_replace("/webfront","",$zip_dir);

            $this->success_json($zip_dir);
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }
}