<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/31
 * Time: 13:52
 */
class Receivables_model extends Api_base_model
{

    //api conf  /modules/api/conf/caigou_sys_receivables.php
    protected $_receListApi = "";
    protected $_receivableApi = "";
    protected $_receivableSaveApi = "";

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 应付款单列表页
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function rece_list($get)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_receListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 待收款显示
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function receivable($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_receivableApi;
        return $this->_curlWriteHandleApi($url, $post, 'POST');
    }

    /**
     * 保存待收款操作
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function receivable_save($post)
    {
        //调用服务层api
        $url = $this->_baseUrl . $this->_receivableSaveApi;
        $resp = $this->_curlWriteHandleApi($url, $post, 'POST');
        $resp['status'] = 1;
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }
     /**
     * 查看待收款显示
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function receivable_info($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_receivable_infoSaveApi. "?" . http_build_query($get);
        $resp= $this->_curlReadHandleApi($url, "", 'GET');
        $resp['status'] = 1;
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }
    /**
     * 根据申请单号获取详细信息
     */
    public function get_receivable_info_item($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_receivable_info_itemApi. "?" . http_build_query($get);
        $resp= $this->_curlReadHandleApi($url, "", 'GET');
        $resp['status'] = 1;
        $resp['errorMess'] = $this->_errorMsg;
        return $resp;
    }
    /**
     * 导出列表
     * @author justin
     * @date 2019/9/26 10:48
     * @param $get
     * @return array|mixed
     * @throws Exception
     */
    public function export_list($get)
    {
        set_time_limit(0);
        //调用服务层api
        $url = $this->_baseUrl . $this->_export_listApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 根据申请单号获取详细信息
     */
    public function edit_receivable_note($get){
        //调用服务层api
        $url = $this->_baseUrl . $this->_edit_receivable_noteApi;
        $result = $this->httpRequest($url, $get);
        return $result;
    }
}