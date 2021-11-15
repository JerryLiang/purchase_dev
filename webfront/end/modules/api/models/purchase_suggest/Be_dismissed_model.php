<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 10:26
 */
class Be_dismissed_model extends Api_base_model
{
    public function __construct() {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    protected $_getDismissedReasonApi = "";
    protected $_getDismissedOperationApi = "";

    /**
     * 驳回下拉框原因接口-----袁学文
     * @author liwuxue
     * @date 2019/2/1 9:17
     * @param
     * @return mixed
     * @throws Exception
     */
    public function get_dismissed_reason($post)
    {
        $url = $this->_baseUrl . $this->_getDismissedReasonApi;
        return $this->_curlWriteHandleApi($url, $post, "POST");
    }

    /**
     * 驳回操作接口----袁学文-----袁学文
     * @author liwuxue
     * @date 2019/2/1 9:17
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function get_dismissed_operation($post)
    {
        $url = $this->_baseUrl . $this->_getDismissedOperationApi;
        $rs = $this->_curlWriteHandleApi($url, $post, "POST");
        $rs['status'] = 1;
        $rs['errorMess'] = $this->_errorMsg;
        return $rs;
    }

}