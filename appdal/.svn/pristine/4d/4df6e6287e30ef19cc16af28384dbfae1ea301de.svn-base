<?php
/**
 * Created by PhpStorm.
 * 采购员控制器
 * User: Jolon
 * Date: 2019/01/06 0027 11:17
 */
class Purchase_user extends MY_Controller{

    private $_pUserModel = null;

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase_user_model');
        $this->_pUserModel = $this->purchase_user_model;

    }


    /**
     * 获取采购员列表
     * @author Jolon
     */
    public function get_list(){
        $list = $this->_pUserModel->get_list();
        $this->success_json($list);
    }

    /**
     * 产品资料开发员 下拉框列表
     * @author Jolon
     */
    public function user_developer_drop_down_box(){

        $developer_list = $this->_pUserModel->get_user_developer_list();

        if($developer_list){
            $developer_list_tmp = [];
            foreach($developer_list as $developer){
                $developer_tmp['id']    = $developer['id'];
                $developer_tmp['name']  = $developer['name'];

                $developer_list_tmp[] = $developer_tmp;
            }
            $developer_list = $developer_list_tmp;
        }

        if($developer_list){
            $this->success_json(['key'=>['开发员ID','开发员名称'],'value' => $developer_list]);
        }else{
            $this->error_json('未获取到开发员数据');
        }
    }


    /**
     * 采购系统所有用户 下拉框列表（采购系统内部用户）
     * @author Jolon
     */
    public function user_all_drop_down_box(){

        $developer_list = $this->_pUserModel->get_user_all_list();

        if($developer_list){
            $developer_list_tmp = [];
            foreach($developer_list as $developer){
                $developer_tmp['id']    = $developer['id'];
                $developer_tmp['name']  = $developer['name'];

                $developer_list_tmp[] = $developer_tmp;
            }
            $developer_list = $developer_list_tmp;
        }

        if($developer_list){
            $this->success_json(['key'=>['用户ID','用户名称'],'value' => $developer_list]);
        }else{
            $this->error_json('未获取到用户数据');
        }
    }


}