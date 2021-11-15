<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 14:22
 */
class Bank_card_model extends Api_base_model
{

    //api conf  /modules/api/conf/api_caigou_sys_bank_card.php
    protected $_getCardListApi = "";
    protected $_bankCardCreateApi = "";

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取银行卡账号简称列表
     * @author Jolon
     * @date 2021/1/12 16:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     * @
     */
    public function get_account_short_list($get)
    {
        $url = $this->_baseUrl . $this->_getAccountShortListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 获取 指定的一个 银行卡信息
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $post
     * @return mixed|array
     * @throws Exception
     */
    public function get_card_one($post)
    {
        $url = $this->_baseUrl . $this->_getCardOneApi;
        $res = $this->_curlWriteHandleApi($url, $post, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 查询 银行卡列表 - 占伟龙
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @return mixed|array
     * @throws Exception
     * @
     */
    public function get_card_list($get)
    {
        $url = $this->_baseUrl . $this->_getCardListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", 'GET');
    }

    /**
     * 创建 SKU屏蔽 记录
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $post
     * @return mixed|array
     * @throws Exception
     * @
     */
    public function bank_card_create($post)
    {
        $url = $this->_baseUrl . $this->_bankCardCreateApi;
        $res = $this->_curlWriteHandleApi($url, $post, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

}