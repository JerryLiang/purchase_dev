<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */

class Purchase_news extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_news/Purchase_news_model');
        $this->_modelObj = $this->Purchase_news_model;
    }

    /**
     * 获取取消列表
     * @author Jaxton 2019/01/17
     */
    public function opr_news()
    {

        try {
            $this->_init_request_param("POST");
            $params = $this->_requestParams;
            $data = $this->_modelObj->opr_news($params);
            $this->sendData($data);
        } catch (Exception $e) {


            $this->sendError($e->getCode(), $e->getMessage());
        }



    }

    /**
     * 获取取消列表
     * @author Jaxton 2019/01/17
     */
    public function show_news()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->show_news($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }



    /**
     * 获取取消列表
     * @author Jaxton 2019/01/17
     */
    public function get_menu_news_num()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_menu_news_num($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 置顶
     * @author Jaxton 2019/01/17
     */
    public function set_top()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->set_top($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 点赞
     * @author Jaxton 2019/01/17
     */
    public function thumb_up()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->thumb_up($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 点赞
     * @author Jaxton 2019/01/17
     */
    public function get_show_news()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_show_news($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 点赞
     * @author Jaxton 2019/01/17
     */
    public function get_edit_news()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_edit_news($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 点赞
     * @author Jaxton 2019/01/17
     */
    public function del_news()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->del_news($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }


    /**
     * 点赞
     * @author Jaxton 2019/01/17
     */
    public function get_user_no_read_nums()
    {
        $this->_init_request_param();
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_user_no_read_nums($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }

    /**
     * 上传文件(支持多种格式)
     * @author Dean 2019/01/17
     */
    public function upload_file()
    {

        $news_file=$_FILES['news_file'];

        if(empty($news_file['name'])){

            $this->error_json('请选择文件上传');
        }


       $result = $this->_modelObj->upload_file($news_file);

        if($result['errorCode']){
            $return_data=[
                'status'=>1,
                'errorMess'=>'上传成功',
                'file_info'=>$result['file_info']
            ];
            $this->_code = 0;
            $this->sendData($return_data);
        }else{
            $this->_code = 1;
            $this->_msg = $result['msg'];
            $this->sendData();
        }






    }


    /**
     * 上传文件(支持多种格式)
     * @author Dean 2019/01/17
     */
    public function download_file()
    {

        $this->_init_request_param();
        $params = $this->_requestParams;
        if(empty($params['file_path'])||empty($params['file_type'])){

            $this->_code = 1;
            $this->_msg = '参数有误';
            $this->sendData();
        }
        $this->load->library('file_operation');
        $type = strtolower($params['file_type'])=='pdf'?1:2;
        $result = $this->file_operation->download_file($params['file_path'],$type);




    }












}