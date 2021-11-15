<?php

/**
 * Class: Eye_check
 * Desc: 天眼查接口
 * User: Jolon
 * Date: 2019/3/12 0012 11:25
 */
class Eye_check {

    public function __construct($params = []){
        $ci = get_instance();
        // 加载 URL 配置项
        $ci->load->config('api_config', FALSE, TRUE);
        $access_taken = getOASystemAccessToken();
        if (!empty($ci->config->item('product_system'))) {
            $oa_system = $ci->config->item('product_system');
            foreach($oa_system as $key => $value){
                $this->$key = $value."?access_token=".$access_taken;;
            }
        }
    }

    protected $_corporate_type = array( 1 => '个人',2 => '公司');

    protected $_tableComapanyHeader = array(
        '公司名称','别称', '英文名', '法人代表', '法人类型','公司类型', '注册资金', '注册地址', '组织机构代码', '纳税人识别号',
        '信用代码', '经营状态', '成立日期', '经营结束日期', '行业','行业分数','企业评分','数据来源','人数范围','数据刷新时间'
    );

    /**
     * 调用 OA 系统接口  获取供应商天眼查信息（已缓存好的数据）
     * @param $credit_code
     * @return mixed
     */
    public function get_supplier_eye_check_info($credit_code){
        $param['creditCode'] = $credit_code;
        $header = array('Content-Type: application/json');
        $result = getCurlData($this->getProdCompanyInfoBySupplierCode,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);

        $eye_company_data = [];
        if(is_json($result)){
            $result           = json_decode($result, true);
            if(isset($result['code']) and $result['code'] == 200){
                if(isset($result['data'][0]) and !empty($result['data'][0])){
                    $eye_company_data = $result['data'][0];
                }
            }
        }

        // 没有查询到信息 默认都为空
        $company_data = array(
            'code'         => 200,
            'company_list' => array(
                'key'   => $this->_tableComapanyHeader,
                'value' => array(
                    "company_name"              => isset($eye_company_data['name']) ? $eye_company_data['name'] : '',
                    "nickname"                  => isset($eye_company_data['alias']) ? $eye_company_data['alias'] : '',
                    "us_company_name"           => isset($eye_company_data['property3']) ? $eye_company_data['property3'] : '',
                    "corporate"                 => isset($eye_company_data['legalPersonName']) ? $eye_company_data['legalPersonName'] : '',
                    "corporate_type"            => isset($eye_company_data['type']) ? (isset($this->_corporate_type[$eye_company_data['type']]) ? $this->_corporate_type[$eye_company_data['type']] : '') : '',
                    "company_type"              => isset($eye_company_data['companyOrgType']) ? $eye_company_data['companyOrgType'] : '',
                    "registered_amount"         => isset($eye_company_data['regCapital']) ? $eye_company_data['regCapital'] : '',
                    "registered_address"        => isset($eye_company_data['regLocation']) ? $eye_company_data['regLocation'] : '',
                    "organization_code"         => isset($eye_company_data['orgNumber']) ? $eye_company_data['orgNumber'] : '',
                    "tax_identification_number" => isset($eye_company_data['taxNumber']) ? $eye_company_data['taxNumber'] : '',
                    "credit_code"               => isset($eye_company_data['creditCode']) ? $eye_company_data['creditCode'] : '',
                    "business_status"           => isset($eye_company_data['regStatus']) ? $eye_company_data['regStatus'] : '',
                    "establishment_date"        => isset($eye_company_data['estiblishTime']) ? $eye_company_data['estiblishTime'] : '',
                    "business_end_date"         => isset($eye_company_data['toTime']) ? $eye_company_data['toTime'] : '',
                    "industry"                  => isset($eye_company_data['industry']) ? $eye_company_data['industry'] : '',
                    "industry_score"            => isset($eye_company_data['categoryScore']) ? $eye_company_data['categoryScore'] : '',
                    "corporate_rating"          => isset($eye_company_data['percentileScore']) ? $eye_company_data['percentileScore'] : '',
                    "data_sources"              => isset($eye_company_data['sourceFlag']) ? $eye_company_data['sourceFlag'] : '',
                    "people_range"              => isset($eye_company_data['staffNumRange']) ? $eye_company_data['staffNumRange'] : '',
                    "refresh_time"              => isset($eye_company_data['refreshTime']) ? $eye_company_data['refreshTime'] : '',
                ),
                'errorMess' => isset($result['code'])?$result['code']:''
            )
        );

        return $company_data;
    }


}