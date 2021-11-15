<?php if(!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 产品系统图片服务器对接接
 * @author        jolon
 * @since         2020-05-04
 */
class Product_system_image extends Purchase_model {

    // 请求参数
    public  $_sku_list    = null;// 逗号隔开的sku
    private $_token       = null;// 加密串
    private $_image_type  = null;// 1开发图 2实拍图 3实拍图无logo图 不填就是全部
    private $_is_complete = null;// 1带域名的URL
    private $_is_cache    = null;// 1不读缓存

    private $_api_secret  = null;


    public function __construct(){
        parent::__construct();

        $this->load->config('app_key', false, true);
        $this->_api_secret = PSI_API_SECRET;

        $this->_image_type  = 5;// 默认开发图
        $this->_is_complete = 1;// 带域名的URL
        $this->_is_cache    = 0;// 读缓存

    }

    /**
     * 根据参数 生成 token
     * @param $sku_list
     * @return array|string
     */
    public function create_access_token($sku_list){
        if(is_array($sku_list)){
            $sku_list = implode(',',$sku_list);// 逗号隔开的sku
        }
        $this->_sku_list = $sku_list;
        $params = $this->format_params();

        $params    = ascSort($params);
        $new_token = strtolower(md5($this->createLinkString($params).$this->_api_secret));

        $params['token'] = $new_token;

        return $params;
    }

    /**
     * 格式化参数
     * @return array
     */
    private function format_params(){

        $params = [
            'sku_list'    => $this->_sku_list,
            'image_type'  => $this->_image_type,
            'is_complete' => $this->_is_complete,
            'is_cache'    => $this->_is_cache
        ];

        return $params;
    }

    /**
     * 参数加密拼接方法
     * @param $para
     * @return bool|string
     */
    private function createLinkString($para){
        $arg = "";
        foreach($para as $key => $val){
            if($val === '' || $val === null)
                continue;
            if(is_array($val))
                $val = json_encode($val);
            $arg .= $key."=".urlencode($val)."&";
        }

        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){
            $arg = stripslashes($arg);
        }

        return $arg;
    }

}