<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 二次包装控制器
 * User: Jaden
 * Date: 2019/01/16 
 */

class Repackaging extends MY_ApiBaseController{
    private $_modelObj;
    public function __construct(){
        parent::__construct();
        $this->load->model('purchase/Product_repackage_model');
        $this->_modelObj = $this->Product_repackage_model;
    }

    /**
     * 二次包装列表
     * /purchase/repackaging/two_packaging_list
     * @author Jaden 2019-1-17
    */
    public function two_packaging_list(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_product_repackage_list($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 二次包装列表导入``
     * /purchase/repackaging/import_packaging
     * @author Jaden 2019-1-17
    */
    public function import_packaging(){
        $params = $this->_requestParams;
        $file  = $_FILES['packag_file'];
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
        $data = $this->_modelObj->import_packaging($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);

        
        

    }

    /**
     * 删除数据
     * /purchase/repackaging/delete_pack
     * @author Jaden 2019-1-17
    */
    public function delete_pack(){
        $params = $this->_requestParams;
        //$params['ids'] = '13,22';
        $data = $this->_modelObj->delete_pack($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 审核
     * /purchase/repackaging/examine
     * @author Jaden 2019-1-17
    */
    public function examine(){
        $params = $this->_requestParams;
        //$params['ids'] = '13,22';
       // $params['audit_status'] = 2;
        $data = $this->_modelObj->examine($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    
    /**
     * 获取审核状态
     * /purchase/repackaging/get_product_repackage_status
     * @author jaxton 2019-1-17
    */
    public function get_product_repackage_status(){
        $list=$this->repackage->get_product_repackage_status();
        http_response(response_format(1, $list));
    }

    //测试用
    public function test(){
        echo '<form action="/index.php/api/purchase/repackaging/import_packaging" method="post" enctype="multipart/form-data">
             <input type="file" class="packag_file" name="packag_file" />
             <button type="submit" class="but1">上传</button>
        </form>';
    }

}