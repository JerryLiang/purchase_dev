<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 包裹加急控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Parcel_urgent extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct(){
        parent::__construct();
        $this->load->model('warehouse/Parcel_urgent_model');
        $this->_modelObj = $this->Parcel_urgent_model;
    }



    /**
     * 包裹加急
     /warehouse/parcel_urgent/web_logistics_urgent_list
     * @author Jaden
     */
    public function web_logistics_urgent_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->web_get_logistics_urgent_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);


    }

    /**
     * 包裹加急导入``
     * /warehouse/parcel_urgent/web_import_logistics_urgent
     * @author Jaden 2019-1-17
    */
    public function web_import_logistics_urgent(){
        $params = $this->_requestParams;
        $file  = $_FILES['logistics_file'];
        $file_name = $file['tmp_name'];
        $file_name_arr = explode('.', $file['name']);
        
        if($file_name == '')
        {
            http_response(response_format(0,[],'请选择文件再导入'));
        }
        if(end($file_name_arr)!='csv')
        {
            http_response(response_format(0,[], '只接受csv格式的文件'));
        }

        $handle = fopen($file_name, 'r');
        if($handle === FALSE) {
            http_response(response_format(0,[], '打开文件资源失败'));
        }

        $out = array ();
        $n = 0;
        while ($data = fgetcsv($handle, 100000)) {
            $num = count($data);
            for ($i = 0; $i < $num; $i++){
                $out[$n][$i] = iconv('gbK', 'utf-8//IGNORE',$data[$i]);
            }
            $n++;
        }
        $result_arr = $out;
        $params['import_arr'] = json_encode($result_arr);
        $data = $this->_modelObj->web_import_logistics($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }


    /**
     * 删除数据
     * /warehouse/parcel_urgent/logistics_delete
     * @author Jaden 2019-1-17
    */
    public function logistics_delete(){
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_delete_logistics($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 下载模板
     * /warehouse/parcel_urgent/download_template
     * @author Jaden 2019-1-17
    */
    public function download_template(){
        $this->load->helper('export_csv');
        $heads = ['物流单号','采购单号'];
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $tax_list_tmp = array(
            array('logistics_num'=>'804817739352253000'."\t",'purchase_order_num'=>'PO707900'),
            array('logistics_num'=>'804817739352253001'."\t",'purchase_order_num'=>'PO707901')
        );

        csv_export($heads,$tax_list_tmp,'包裹加急导入模板');
    }



    /**
     * 包裹加急手动推送
     * /warehouse/parcel_urgent/put_logistics_list
     * @author Jaden 2019-1-8
     */
    public function put_logistics_list(){

        $params = $this->_requestParams;
        $data = $this->_modelObj->web_push_logistics_list_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);     
    }

    /**
     * 包裹加急导出
    /warehouse/parcel_urgent/logistics_urgent_export
     * @author jeff
     */
    public function logistics_urgent_export(){
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->web_logistics_urgent_export($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        }

        $tax_list_tmp = $data['data_list'];
        header('location:'.$tax_list_tmp);
    }




    
    public function test(){
        echo '<form action="/index.php/api/warehouse/parcel_urgent/web_import_logistics_urgent" method="post" enctype="multipart/form-data">
             <input type="file" class="logistics_file" name="logistics_file" />
             <button type="submit" class="but1">上传</button>
        </form>';
    }
    



}