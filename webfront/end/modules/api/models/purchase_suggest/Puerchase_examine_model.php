<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 9:12
 */
class Puerchase_examine_model extends Api_base_model
{

    public function __construct() {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    protected $_auditFailedApi = "";
    protected $_auditPassApi = "";

    /**
     * 需求审核不通过-----袁学文
     * @author liwuxue
     * @date 2019/2/1 9:17
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function audit_failed($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_auditFailedApi;
        $rs = $this->_curlWriteHandleApi($url, $post, "POST");
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

    /**
     * 需求审核通过-----袁学文
     * @author liwuxue
     * @date 2019/2/1 9:17
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function audit_pass($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_auditPassApi;
        $rs = $this->_curlWriteHandleApi($url, $post, "POST");
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

}