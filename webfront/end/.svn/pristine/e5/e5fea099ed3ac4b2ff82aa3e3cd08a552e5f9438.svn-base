<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/15
 * Time: 15:09
 */
class Admin_log_model extends Api_base_model
{

    //api conf  /modules/api/conf/caigou_sys_admin_log.php
    protected $_getListApi = "";
    protected $_writeLogApi = "";

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 日志接口
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     */
    public function get_list(array $get)
    {
        $url = $this->_baseUrl . $this->_getListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 记录日志
     * @author liwuxue
     * @date 2019/2/15 16:32
     * @param $data
     *      "route" => $this->uri->uri_string,
     *      "user_name" => $username,
     *      "ip" => $client_ip,
     *      "description" => "描述",
     * @return mixed
     * @throws Exception
     */
    public function write_log(array $data)
    {
        try {
            $url = $this->_baseUrl . $this->_writeLogApi;
            $this->_curlWriteHandleApi($url, $data, 'POST');
        } catch (Exception $e) {

        }
        return true;
    }
}