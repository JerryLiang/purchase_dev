<?php

/**
 * [function 供应商-结算方式]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier_settlement_model extends Api_base_model
{
    protected $_baseUrl; // 统一地址前缀
    protected $_listUrl; // 列表的路径
    protected $_editUrl; // 编辑的路径
    protected $_addUrl;  // 添加
    protected $_dropUrl; // 删除
    protected $_allUrl;  // 所有
    protected $_detailUrl; // 详情
//    protected $_actionLogUrl; // 操作日志

    protected $_tableHeader = array(
        '结算方式名称', '结算方式编码', '结算方式状态'
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
     * @desc 供应商-联系方式
     * @author jackson
     * @Date 2019-01-21 15:26:00
     * @return array()
     */
    public function get_settlement()
    {

        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_listUrl, '', 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return array(false,$this->_errorMsg);
        }
        // End

        $data = $result['data'];
        $records = $data['list'];

        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $records,
                'drop_down_box' => null
            ),
        );
    }
}