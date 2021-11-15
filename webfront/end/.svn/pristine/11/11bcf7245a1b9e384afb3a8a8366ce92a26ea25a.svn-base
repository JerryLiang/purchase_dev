<?php
/**
 * 异常列表模型类
 * User: Jaxton
 * Date: 2019/01/16 10:06
 */

class Abnormal_quality_model extends Api_base_model
{

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');

    }

    public function get_Abnormal_list_data($params){

        $url = $this->_baseUrl . $this->_get_Abnormal_list_data;
        return $this->request_http($params,$url);
    }

    public function add_Abnoral_list_data($params){

        $url = $this->_baseUrl . $this->_add_Abnoral_list_data;


        return $this->httpRequest($url, $params, 'POST');
    }

    public function handler_Abnoral_list_data($params){

        $url = $this->_baseUrl . $this->_handler_Abnoral_list_data;


        return $this->httpRequest($url, $params, 'POST');
    }

    public function Abnoral_log($params){

        $url = $this->_baseUrl . $this->_Abnoral_log;
        return $this->request_http($params,$url);
    }

    public function import_Abnormal_list_data($params){

        $url = $this->_baseUrl . $this->_import_Abnormal_list_data;


        return $this->httpRequest($url, $params, 'POST');
    }

    public function push_import_Abnormal_list_data($post){
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $url = $this->_baseUrl . $this->_push_import_Abnormal_list_data;//调用服务层api
        $file_path = $post['file_path'];
        $fileExp   = explode('.', $file_path);
        $fileExp   = strtolower($fileExp[count($fileExp) - 1]);//文件后缀
        include APPPATH.'third_party/PHPExcel/IOFactory.php';
        if ($fileExp == 'xls') $PHPReader = new \PHPExcel_Reader_Excel5();
        if ($fileExp == 'xlsx') $PHPReader = new \PHPExcel_Reader_Excel2007();
        if(!isset($PHPReader)){
            $return['code']    = false;
            $return['message'] = "只能导入 xls 或 xlsx 文件 ";
            $return['data']    = '';
            return $return;
        }
        $PHPReader      = $PHPReader->load($file_path);
        $currentSheet   = $PHPReader->getSheet(0);
        $sheetData      = $currentSheet->toArray(null,true,true,true);
        $error_list = [];
        $uid          = $post['uid'];
        $post['data'] = $sheetData;
        $data_string  = json_encode($post);
        $result = getCurlData($url.'?uid='.$uid,$data_string,'POST',array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ),false,array('time_out' => 900,'conn_out' => 0));

        $result = json_decode($result,true);

        if(empty($result['status'])){
            $error_list    = $result['data_list'];
            if(empty($error_list)){// 程序错误
                $return['code'] = false;
                $return['message'] = isset($result['errorMess'])?$result['errorMess']:'程序发生错误';
                $return['data'] = '';
                return $return;
            } else {
                $msg ='';
                foreach ($error_list as $container_sn => $info) {
                    $str = implode(',',$info);
                    $msg.=$container_sn.":".$str."<br/>";

                }
                $return['code'] = false;
                $return['message'] = $msg;
                $return['data'] = '';
                return $return;

            }


        }else{
            $return['code'] = true;
            $return['message'] = '导入成功'.$result['errorMess'];
            $return['data'] = '';
            return $return;
        }
    }
}