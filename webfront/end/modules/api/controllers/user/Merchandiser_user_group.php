<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Merchandiser_user_group extends MY_ApiBaseController
{
    private $_modelObj;
    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Merchandiser_user_group_model');
        $this->_modelObj = $this->Merchandiser_user_group_model;
    }

    /**
     * 采购组列表
     * @author Justin
     *  /api/user/user_group/group_list
     */
    public function get_group_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_group_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 组的编辑
     * @author Justin
     * /user/user_group/group_edit
     */
    public function group_edit(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->group_edit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 删除小组
     * @author Justin
     * /user/user_group/group_del
     */
    public function group_del(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->group_del($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }


    /**
     * 用户列表
     * @author Justin
     *  /api/user/user_group/get_user_list
     */
    public function get_user_list(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_user_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 保存添加用户
     * @author Justin
     * /api/user/user_group/user_add
     */
    public function user_add(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->user_add($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 编辑用户显示
     * /api/user/user_group/user_edit_view
     */
    public function user_edit_view(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->user_edit_view($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 输入user_id获取用户显示下拉框
     * @author harvin
     * @date 2019-3-19
     * /api/user/user_group/user_info
     */
    public function user_info() {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_user_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 添加用户显示下拉框
     * @author Justin
     * /api/user/user_group/get_1688_account
     */
    public function get_1688_account(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_1688_account($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 保存用户编辑
     * /api/user/user_group/user_edit_save
     */
    public function user_edit_save()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_user_edit_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 删除用户（只删除超级数据组用户）
     * @author harvin
     * @date 2019-3-20
     */
    public function user_del(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->user_del($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 交款人
     * /api/user/user_group/handover_person
     */
    public function handover_person(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_handover_person($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 获取公司二级部门
     **/
    public function get_company_dep()
    {
        try{
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_company_dep($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e ) {

            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 用户的启用禁用
     * /api/user/user_group/change_enable_status
     */
    public function change_enable_status(){
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->change_enable_status($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 获取采购系统组别
     * @param GET
     * @author:luxu
     * @time:2020/9/8 11 19
     **/

    public function getGrupData(){

        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->getGrupData($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
}