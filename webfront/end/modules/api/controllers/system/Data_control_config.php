<?php
require APPPATH.'core/MY_ApiBaseController.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Data_control_config extends MY_ApiBaseController {

    private $_modelObj;

    public function __construct(){
        parent::__construct();
        $this->load->model('system/Data_control_config_model');
        $this->_modelObj = $this->Data_control_config_model;
    }

    /**
     * 获取 采购单自动审核配置
     * @author Jolon
     * @date   2019-12-11
     */
    public function get_auto_audit_control_config(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_auto_audit_control_config($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
    /**
     * 设置 采购单自动审核配置
     * @author Jolon
     * @date   2019-12-11
     */
    public function set_auto_audit_control_config(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->set_auto_audit_control_config($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 设置 采购单自动请款设置
     * @author Jolon
     * @date   2019-12-11
     */
    public function get_auto_payout_control_config(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_auto_payout_control_config($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 设置 采购单自动请款设置
     * @author Jolon
     * @date   2019-12-11
     */
    public function set_auto_payout_control_config(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->set_auto_payout_control_config($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 设置 1688一键下单配置
     * @author Jolon
     * @date   2019-12-11
     */
    public function get_ali_one_key_control_config(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_ali_one_key_control_config($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 设置 1688一键下单配置
     * @author Jolon
     * @date   2019-12-11
     */
    public function set_ali_one_key_control_config(){
        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->set_ali_one_key_control_config($this->_requestParams);
            $this->sendData($data);
        }catch(Exception $e){
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 下载中心配置获取
     * @author luxu
     * @date   2020-02-22
     */

    public function getConfiguration()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->getConfiguration($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 下载中心日志修改记录
     * @author luxu
     * @date   2020-02-24
     */
    public function getConfiguration_log()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->getConfiguration_log($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 修改配置信息
     * @author luxu
     * @date   2020-02-24
     */
    public function updateConfiguration()
    {
        try{

            $this->_init_request_param("POST");
            $data = $this->_modelObj->updateConfiguration($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e )
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    public function getCenterData()
    {
        try{

            $this->_init_request_param("GET");
            $data = $this->_modelObj->getCenterData($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e )
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取选项信息
     * @author luxu
     * @date   2020-02-24
     */
    public function getCenterBoxData()
    {
        try{

            $this->_init_request_param("GET");
            $data = $this->_modelObj->getCenterBoxData($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e )
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取产品模块审核主体信息
     * @author:luxu
     * @time: 2020/3/3
     **/

    public function productAuditSubjectList(){

        try{

            $this->_init_request_param("GET");
            $data = $this->_modelObj->productAuditSubjectList($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e )
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取审核信息
     * @author:luxu
     * @time:2020/3/3
     **/
    public function getAuditData(){

        try{

            $this->_init_request_param("GET");
            $data = $this->_modelObj->getAuditData($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e )
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 编辑审核流程
     * @author:luxu
     * @time:2020/3/3
     **/
    public function updateProcess(){

        try{

            $this->_init_request_param("POST");
            $data = $this->_modelObj->updateProcess($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $e )
        {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取角色信息
     * @author:luxu
     * @time:2020/3/5
     **/
    public function getRoleMessage(){

        try{
            $this->_init_request_param("POST");
            $data = $this->_modelObj->getRoleMessage($this->_requestParams);
            $this->sendData($data);
        }catch ( Exception $exception )
        {
            $this->sendError($exception->getCode(), $exception->getMessage());
        }

    }
}
