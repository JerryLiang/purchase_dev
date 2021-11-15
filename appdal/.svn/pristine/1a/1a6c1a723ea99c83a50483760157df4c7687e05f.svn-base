<?php

/**
 * 采购系统采购单文件管理MODEL
 * @author:luxu
 * @time:2020/2/21
 **/
class Purchase_file_down_model extends Purchase_model {

    protected $table_name   = 'purchase_label_info';//物流标签表

    protected $dir = NULL; // PDF 存放目录

    public function __construct(){
        parent::__construct();
        $this->dir = dirname(dirname(APPPATH)).'/webfront/download_csv/pdf/';

        //$this->dir = 'local.webfront.com/webfront/download_csv/pdf/';

    }

    /**
     * 获取采购单工厂直发码，标签
     * @param: $sku   string   产品SKU
     *         $purchase_number  string   采购单号
     * @author:luxu
     * @time:2020/2/21
     * @return  array
     **/

    private function get_barcode_pdf($sku,$purchase_number,$flag)
    {
        if( empty($sku) || empty($purchase_number) )
        {
            return NULL;
        }

        $query = $this->purchase_db->from($this->table_name)->where_in("sku",$sku)->where_in("purchase_number",$purchase_number);
        $result = $query->select("id,$flag,sku,purchase_number")->get()->result_array();
        $result_data = array();
        if( !empty($result) )
        {
            // 获取数据不为空情况下
            foreach( $result as $key=>$value )
            {
                $key_flag = $value['sku']."-".$value['purchase_number'];
                if( !isset($result_data[$key_flag]) ){

                    $result_data[$key_flag] = [];
                }
                $result_data[$key_flag] = array(

                    $flag =>  str_replace('192.168.1.34','wms.yibainetwork.com',$value[$flag]),//标签
                    'purchase_number'=>$value['purchase_number'],
                    'sku' => $value['sku']
                );
            }

            $update = [];
            if( $flag == 'barcode'){

                $update['purchase_barcode_down_time'] = date("Y-m-d H:i:s",time());
            }

            if( $flag == "label"){

                $update['purchase_label_down_time'] = date("Y-m-d H:i:s",time());
            }
            if(!empty($update)){
                $this->purchase_db->from($this->table_name)->where($flag.'!=','')->where_in("sku",$sku)->where_in("purchase_number",$purchase_number)->update($this->table_name,$update);
            }
        }

        return $result_data;
    }

    /**
     * 生成文件夹
     * @param : $demand_number  string 备货单号
     * @autho:luxu
     * @time:2020/2/21
     * @return array
     **/
    public function build_folder($dir_name,$lable_flag)
    {
        $pdf_path_data = $this->dir.$dir_name."/";
        $this->load->library('zip');
        $this->zip->clear_data();
        $this->zip->read_dir($pdf_path_data, FALSE);
        $this->zip->archive($pdf_path_data.$dir_name.'.zip');
        return $pdf_path_data.$dir_name.'.zip';

    }

    public function down_file_pdf( $pdf_image,$purchase_number,$mcrtime,$sku,$flag)
    {
        try {
            //$pdf_path = CG_SYSTEM_WEB_FRONT_IP.'download_csv/pdf/';
            $pdf_path = $this->dir .$mcrtime."/". $purchase_number;
            if( "barcode" == $flag ){
                $pdf_path.="_TM/";
            }else{
                $pdf_path.="_BQ/";
            }
            if (!is_dir($pdf_path)) {
                mkdir($pdf_path, 0777, True);
            }
            $pdf_path_data = [];

            foreach ($pdf_image as $key=>$pdf) {
                if (!empty($pdf)) {
                    $ch = curl_init($pdf);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
                    $rawdata = curl_exec($ch);
                    curl_close($ch);
                    $pdf_name = explode(".", basename($pdf));
                    $pdf_url = $pdf_path . $purchase_number."_".$sku;
                    if( $key >0){
                        $pdf_url.="_".$key;
                    }
                    if( "barcode" == $flag ){
                        $pdf_url.="_TM";
                    }else{
                        $pdf_url.="_BQ";
                    }

                    $pdf_url.="." . $pdf_name[1];
                    $fp = fopen($pdf_url, 'w');
                    fwrite($fp, $rawdata);
                    fclose($fp);
                    $pdf_path_data[] = $pdf_url;
                }
            }

            return $pdf_path;
        }catch ( Exception $exp )
        {
            throw new Exception( $exp->getMessage());
        }
    }

    /**
     * 获取采购单工厂直发码，标签（公开方法）
     * @param: $sku   string   产品SKU
     *         $purchase_number  string   采购单号
     *         $flag    string  返回数据字段
     * @author:luxu
     * @time:2020/2/21
     * @return  array
     **/
    public function get_barcode_pdf_data($sku,$purchase_number,$demand_number,$flag='barcode_pdf')
    {
        $result = $this->get_barcode_pdf($sku,$purchase_number,$flag);
        if( !empty($result))
        {
            foreach($demand_number as $key=>$value)
            {
                $demand_number[$key] = isset($result[$value])?$result[$value]:NULL;
            }
            return $demand_number;
        }
        return  NULL;
    }



}