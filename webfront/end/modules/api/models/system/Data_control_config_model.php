<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/12/12
 * Time: 15:09
 */

class Data_control_config_model extends Api_base_model {
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取 采购单自动审核配置
     * @author Jolon
     * @date   2019/12/11
     * @return mixed
     * @throws Exception
     */
    public function get_auto_audit_control_config($data){

        $url = $this->_baseUrl . $this->_getAutoAuditControlConfig;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 获取 采购单自动审核配置
     * @author Jolon
     * @date   2019/12/11
     * @return mixed
     * @throws Exception
     */
    public function set_auto_audit_control_config($data){
        $url = $this->_baseUrl . $this->_setAutoAuditControlConfig;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 获取 采购单自动请款设置
     * @author Jolon
     * @date   2019/12/11
     * @return mixed
     * @throws Exception
     */
    public function get_auto_payout_control_config($data){

        $url = $this->_baseUrl . $this->_getAutoPayoutControlConfig;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 获取 采购单自动请款设置
     * @author Jolon
     * @date   2019/12/11
     * @return mixed
     * @throws Exception
     */
    public function set_auto_payout_control_config($data){

        $url = $this->_baseUrl . $this->_setAutoPayoutControlConfig;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 获取 1688一键下单配置
     * @author Jolon
     * @date   2019/12/11
     * @return mixed
     * @throws Exception
     */
    public function get_ali_one_key_control_config($data){

        $url = $this->_baseUrl . $this->_getAliOneKeyControlConfig;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 获取 1688一键下单配置
     * @author Jolon
     * @date   2019/12/11
     * @return mixed
     * @throws Exception
     */
    public function set_ali_one_key_control_config($data){

        $url = $this->_baseUrl . $this->_setAliOneKeyControlConfig;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 获取审核配置信息
     **/
    public function getConfiguration($data){

        $url = $this->_baseUrl . $this->_getConfiguration;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

    /**
     * 下载中心日志修改记录
     * @author luxu
     * @date   2020-02-24
     */
    public function getConfiguration_log($data)
    {
        $url = $this->_baseUrl . $this->_getConfiguration_log;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

    public function getCenterData($data)
    {
        $url = $this->_baseUrl . $this->_getCenterData;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }
    /**
     * 修改配置信息
     * @author luxu
     * @date   2020-02-24
     */
    public function updateConfiguration($data)
    {
        $url = $this->_baseUrl . $this->_updateConfiguration;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

    public function getCenterBoxData($data)
    {
        $url = $this->_baseUrl . $this->_getCenterBoxData;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

    /**
     * 获取产品模块审核主体信息
     * @param: $data  array HTTP 传入参数
     * @author:luxu
     * @time: 2020/3/3
     **/
    public function productAuditSubjectList($data){

        $url = $this->_baseUrl . $this->_productAuditSubjectList;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

    /**
     * 获取审核信息
     * @author:luxu
     * @time:2020/3/3
     **/
    public function getAuditData($data){

        $url = $this->_baseUrl . $this->_getAuditData;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

    /**
     * 编辑审核流程
     * @author:luxu
     * @time:2020/3/3
     **/
    public function updateProcess($data){

        $url = $this->_baseUrl . $this->_updateProcess;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

    public function getRoleMessage($data){

        $url = $this->_baseUrl . $this->_getRoleMessage;
        $res = $this->_curlWriteHandleApi($url, $data, 'POST');
        return $res;
    }

}