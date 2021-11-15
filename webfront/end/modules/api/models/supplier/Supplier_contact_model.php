<?php

/**
 * [function 供应商-联系方式]
 * @author Jackson
 * @param
 * @DateTime 2019/1/21
 */
class Supplier_contact_model extends Api_base_model
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
        '序号','供应商代码', '联系人', '联系电话', 'FAX','发货地址', '联系邮编', 'QQ', '微信',
        '旺旺', 'Skype', '性别', '邮箱', '法人代表','经营范围','最近采购时间','累计合作金额','最近采购时间'
    );

    protected $_tableComapanyHeader = array(
        '公司名称','别称', '英文名', '法人代表', '法人类型','公司类型', '注册资金', '注册地址', '组织机构代码', '纳税人识别号',
        '信用代码', '经营状态', '成立日期', '经营结束日期', '行业','行业分数','企业评分','数据来源','人数范围','数据刷新时间'
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
     * @parames array $params 请求参数
     * @Date 2019-01-21 15:26:00
     * @return array()
     */
    public function get_contact(array $params)
    {
        //1.调用接口
        $result = $this->_curlRequestInterface($this->_listUrl, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return array(false, $this->_errorMsg);
        }
        // End

        $data = $result['data'];
        $records = $data['list']['data'];
        $company_list = $data['list']['company_list'];


        if(empty($records)){
            return array(false, '供应商联系人缺失');
        }

        return array(
            'data_list' => array(
                'key' => $this->_tableHeader,
                'value' => $records,
                'drop_down_box' => null
            ),
            'company_list'=> $company_list
        );
    }


    /**
     * @desc 供应商-联系方式
     * @author jackson
     * @parames array $params 请求参数
     * @Date 2019-01-21 15:26:00
     * @return array()
     */
    public function translate_supplier_info(array $params)
    {
        $url = $this->_baseUrl . $this->_translateSupplierInfo;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;

    }
}