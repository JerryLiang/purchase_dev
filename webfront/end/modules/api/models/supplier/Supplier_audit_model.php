<?php

/**
 * 交互层--供应商列表 logic
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/25
 * Time: 8:53
 */
class Supplier_audit_model extends Api_base_model
{

    //api conf  /modules/api/conf/supplier_sys_supplier_audit.php
    protected $_baseUrl = "";
    protected $_conditionApi = "";
    protected $_listApi = "";


    public function __construct()
    {
        parent::__construct();

        $this->init();
        $this->setContentType('');
    }

    /**
     * 处理服务层返回结果
     * @author liwuxue
     * @date dt
     * @param $api_resp
     * @return mixed
     * @throws Exception
     */
    private function disposeApiResp($api_resp)
    {
        if (isset($api_resp['status']) && $api_resp['status'] === true && isset($api_resp['data'])) {
            return $api_resp['data'];
        } else {
            throw new Exception(json_encode($api_resp, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * 获取列表
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $req
     * @return mixed|array
     * @throws Exception
     */
    private function get_audit_list($req)
    {
        $api = $this->_baseUrl . $this->_listApi . "?" . http_build_query($req);
        //$api_res = $this->httpRequest($api, "", "GET");
        $api_res = getCurlData($api, "", "GET","",false,['time_out'=>300]);
        $result =  json_decode($api_res,true);
        return isset($result['data']) ? $result['data'] : [];
    }

    /**
     * 获取分页列表数据
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $param 客户端请求参数
     * @return mixed
     * @throws Exception
     */
    public function get_page_list($param = [])
    {
        return $this->get_audit_list($param);
    }

    /**
     * 导出列表数据为excel
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $param
     * @throws Exception
     */
    public function export_excel($param = [])
    {
        //导出不传分页参数,取全量数据
        unset($param['offset']);
        unset($param['limit']);
		$param['export'] = true;
        $data = $this->get_audit_list($param);
        $list = isset($data['data_list']['value']) && is_array($data['data_list']['value']) ? $data['data_list']['value'] : [];
        $title = ["供应商编码","供应商名称", "创建人", "申请人","申请时间", "采购审核人","采购审核时间","采购审核时效",
                                                      "供应链审核人","供应链审核时间","供应链审核时效",
                                                        "财务审核人","财务审核时间","财务审核时效","最终审核人","最终审核时间",
                                                        "审核状态", "审核类型", "审核时效(H)","备注"];
//        $fileName = "供应商审核列表".date("Y-m-d").".xlsx";
        $fileName = "供应商审核列表".date("Y-m-d").".csv";

        $data = [];
//        $data[] = $title;
        foreach ($list as $item) {
            $audit_time_list = $item['audit_time_list'];
            $data[] = [
                isset($item['supplier_code']) ? $item['supplier_code'] : '',
                isset($item['supplier_name']) ? $item['supplier_name'] : '',
                isset($item['create_user']) ? $item['create_user'] : '',//创建人
                $audit_time_list['apply_time']['apply_user'],//申请人
                $audit_time_list['apply_time']['apply_time'],//申请时间
                $audit_time_list['purchase_time']['audit_user'],
                $audit_time_list['purchase_time']['audit_time'],
                $audit_time_list['purchase_time']['use_time'],

                $audit_time_list['supply_time']['audit_user'],
                $audit_time_list['supply_time']['audit_time'],
                $audit_time_list['supply_time']['use_time'],

                $audit_time_list['finance_time']['audit_user'],
                $audit_time_list['finance_time']['audit_time'],
                $audit_time_list['finance_time']['use_time'],

                isset($item['audit_user']) ? $item['audit_user'] : '',
                isset($item['audit_time']) ? $item['audit_time'] : '',

                isset($item['audit_status']) ? $item['audit_status'] : '',
                isset($item['audit_type']) ? $item['audit_type'] : '',
                isset($item['audit_used']) ? $item['audit_used'] : '',
                isset($item['remarks']) ? $item['remarks'] : '',
            ];
        }

        $this->load->library("CommonHelper");
//        CommonHelper::array2excel($data, $fileName);
        CommonHelper::arrayToCsv($title, $data, $fileName);
    }

}