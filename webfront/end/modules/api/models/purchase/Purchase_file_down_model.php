<?php
/**
 * Created by PhpStorm.
 * 文件下载
 * User: luxu
 * Date: 2020/02/21
 */
class Purchase_file_down_model extends Api_base_model
{

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 导入
     * @author Jaden 2019-1-10
     * @param array $import_arr  导入的文件
     */
    public function get_barcode_pdf($import_arr){

        try {
            $url = $this->_baseUrl . $this->_Purchase_file_down;
            $result = $this->_curlWriteHandleApi($url, $import_arr, "POST");

            return $result;


        }catch ( Exception $exp )
        {
            echo $exp->getMessage();die();
            return NULL;
        }

    }

}