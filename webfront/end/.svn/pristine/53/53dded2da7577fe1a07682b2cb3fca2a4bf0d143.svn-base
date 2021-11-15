<?php

/**
 * [function desc]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier_buyer_model extends Api_base_model
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
     * @desc 获取下拉采购员列表
     * @author jackson
     * @parames array $params 请求参数
     * @Date 2019-01-21 15:26:00
     * @return array()
     */
    public function get_buyer($params = array())
    {
        // 1.调用接口
        $result = $this->_curlRequestInterface($this->_listUrl, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        $data = $result['data'];
        $records = $data['list'];

        return array(
            'data_list' => array(
                'key' => ['采购员', '采购员ID'],
                'value' => $records,
                'drop_down_box' => null
            ),
        );
    }
}