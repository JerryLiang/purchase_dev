<?php

/**
 * [function 省市、省份、区县]
 * @author Jackson
 * @param
 * @DateTime 2019/1/25
 */
class Supplier_address_model extends Api_base_model
{

    protected $_baseUrl; // 统一地址前缀
    protected $_listUrl; // 列表的路径
    protected $_editUrl; // 编辑的路径
    protected $_addUrl;  // 添加
    protected $_dropUrl; // 删除
    protected $_allUrl;  // 所有
    protected $_detailUrl; // 详情
    protected $_AddressUrl;//城市
//    protected $_actionLogUrl; // 操作日志

    protected $_tableHeader = array(
        '序号', '供应商', '结算方式', '支付方式', '产品线',
        '供应商等级', '近三个月合作金额', '采购员', '创建人', 'sku数量', '审核状态', '操作'
    );

    // 字段-名称对应关系
    protected $_fieldTitleMap = array();

    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     * @desc 获取省份、城市、区县 根据 类型及父ID
     * @author jackson
     * @parames array $params 请求参数
     * @Date 2019-01-25 15:26:00
     * @return array()
     */
    public function get_address($params = array())
    {
        
        // 2.调用接口
        $result = $this->_curlRequestInterface($this->_listUrl, $params, 'POST');

        //3.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        $data = $result['data'];
        $records = $data['list'];

        if(isset($records['province'])){
            $records =  $records['province'];
        }elseif($records['city']){
            $records =  $records['city'];
        }else{
            $records =  $records['area'];
        }
        $records = create_key_and_name($records,'region_code','region_name');

        return array(
            'data_list' => array(
                'key' => ['ID', '名称'],
                'value' => $records,
                'drop_down_box' => null
            ),
        );
    }

    /**
     * @return 获取省份、城市、区县 根据 类型及父ID
     */
    public function get_address_list($params = array()){
        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_AddressUrl, $params, 'GET');
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }

        return $result;
    }


    /**
     * @return 获取省份、城市、区县 根据 类型及父ID
     */
    public function get_drop_down_list($params = array()){
        $url = $this->_baseUrl . $this->_getDropDownListUrl;
        return $this->request_http($params, $url, 'GET', false);
    }
}