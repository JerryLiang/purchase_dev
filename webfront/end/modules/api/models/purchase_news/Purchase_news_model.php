<?php

/**
 * Created by PhpStorm.
 * User: Dean
 * Date: 2019/12/5
 * Time: 11:01
 */
class Purchase_news_model extends Api_base_model
{
    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }





    /**
     * 获取栏目页面
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function opr_news($params)
    {
        $url = $this->_baseUrl . $this->_oprNewsUrl;
        //return $this->request_http($params, $url,'POST');
       return  $this->_curlReadHandleApi($url, $params, 'POST');

    }

    public function show_news($params)
    {
        $url = $this->_baseUrl . $this->_showNewsUrl;
        return $this->request_http($params, $url);

    }


    public function get_menu_news_num($params)
    {
        $url = $this->_baseUrl . $this->_getMenuNewsNumUrl;
        return $this->request_http($params, $url);

    }


    public function set_top($params)
    {
        $url = $this->_baseUrl . $this->_setTopUrl;
        return $this->request_http($params, $url);

    }

    public function thumb_up($params)
    {
        $url = $this->_baseUrl . $this->_thumbUpUrl;
        return $this->request_http($params, $url);

    }

    public function get_show_news($params)
    {
        $url = $this->_baseUrl . $this->_getShowNewsUrl;
        return $this->request_http($params, $url);

    }

    public function get_edit_news($params)
    {
        $url = $this->_baseUrl . $this->_getEditNewsUrl;
        return $this->request_http($params, $url);

    }

    public function del_news($params)
    {
        $url = $this->_baseUrl . $this->_delNewsUrl;
        return $this->request_http($params, $url);

    }

    public function get_user_no_read_nums($params)
    {
        $url = $this->_baseUrl . $this->_getUserNoReadNumsUrl;
        return $this->request_http($params, $url);

    }

    //上传其他类型文件
    public function upload_file($params)
    {
        $return = ['code'=>false,'msg'=>'','file_info'=>[]];
        try{
            $this->load->library('file_operation');
            $upload_result=$this->file_operation->upload_file($params,'download_csv/news','NEWS');
            if ($upload_result['errorCode']) {
                $this->load->model('finance/Upload_receipt_model');

                //获取前缀
                $file_types = explode(".", $upload_result['file_name']);
                $ext  = $file_types [count($file_types) - 1];//后缀
                if (in_array($ext,['jpg','jpeg','png','gif'])){
                    $type = 'image';

                }else {
                    $type = 'file';


                }
                    $path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . $upload_result['file_info']['file_path'];
                    $result = $this->Upload_receipt_model->doUploadFastDfs($type,$path);
                    $result = json_decode($result, TRUE);
                    $result['data'] = $result['data'][0];
                    if ($result && isset($result['code']) && $result['code'] == 1000) {
                        //删除本地
                        @unlink($path);
                        $return['file_info'] = [
                            'file_path' => $result['data']['fullUrl'],
                            'file_type' => $result['data']['fileType'],
                        ];

                    } else {

                        throw new Exception('上传fastdfs文件系统失败');

                    }

            } else {
                throw new Exception($upload_result['errorMess']);
            }


        }catch (Exception $e){
            $return['msg'] = $e->getMessage();

        }


        return $return;









    }







}