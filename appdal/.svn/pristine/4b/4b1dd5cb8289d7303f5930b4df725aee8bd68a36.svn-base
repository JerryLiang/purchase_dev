<?php

/**
 * Created by PhpStorm.
 * 供应商权均交期
 * User: Jolon
 * Date: 2021/06/11 0029 11:50
 */
class Supplier_average_delivery_model extends Purchase_model
{

    protected $table_name = 'supplier_avg_day';


    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function table_nameName()
    {
        return $this->table_name;
    }

    /**
     * Supplier_model constructor.
     */
    public function __construct()
    {
        parent::__construct();

    }

    /**
     * 查询供应商权均交期列表
     * @param $params
     * @param $offsets
     * @param $limit
     * @param $page
     * @param string $action
     * @return array
     */
    public function get_delivery_list($params, $offsets, $limit, $page,$action = 'query'){
        $this->purchase_db->from($this->table_name . ' AS A')
            ->join('pur_supplier AS C', "A.supplier_code=C.supplier_code", 'LEFT')
            ->join('pur_supplier_analysis_product_line AS B', "A.supplier_code=B.supplier_code AND B.status=1", 'LEFT')
            ->join("pur_product_line AS E", "B.first_product_line=E.product_line_id", 'LEFT');


        if (isset($params['ids']) and !empty($params['ids'])) {
            $ids = explode(',',$params['ids']);
            if (!empty($ids)) {
                $this->purchase_db->where_in('A.id', $ids);
            } else {
                $this->purchase_db->where('A.id',PURCHASE_NUMBER_ZFSTATUS);
            }
        }
        if (isset($params['supplier_code']) && !empty($params['supplier_code'])) {
            if (is_array($params['supplier_code'])) {
                $this->purchase_db->where_in('A.supplier_code', $params['supplier_code']);
            } else {
                $this->purchase_db->where('A.supplier_code', $params['supplier_code']);
            }
        }
        if(isset($params['first_product_line']) and !empty($params['first_product_line'])){
            if(is_array($params['first_product_line'])) {
                $this->purchase_db->where_in('B.first_product_line', $params['first_product_line']);
            }else {
                $this->purchase_db->where('B.first_product_line', $params['first_product_line']);
            }
        }
        if (isset($params['cooperation_status']) and !empty($params['cooperation_status'])) {
            $this->purchase_db->where('C.status', $params['cooperation_status']);
        }

        if (isset($params['supplier_settlement']) and !empty($params['supplier_settlement'])) {
            if(is_array($params['supplier_settlement'])) {
                $supplier_settlement_str = implode(",",$params['supplier_settlement']);
            }else {
                $supplier_settlement_str = $params['supplier_settlement'];
            }
            $this->purchase_db->where("(SELECT COUNT(1) FROM pur_supplier_payment_info WHERE pur_supplier_payment_info.supplier_settlement IN($supplier_settlement_str) AND A.supplier_code=pur_supplier_payment_info.supplier_code AND pur_supplier_payment_info.is_del=0) > 0");
        }

        if(isset($params['statis_month_start']) and $params['statis_month_start'] != ''){
            $this->purchase_db->where('A.statis_month >=', $params['statis_month_start']);
        }
        if(isset($params['statis_month_end']) and $params['statis_month_end'] != ''){
            $this->purchase_db->where('A.statis_month <=', $params['statis_month_end']);
        }

        if(isset($params['ds_day_avg_start']) and $params['ds_day_avg_start'] != ''){
            $this->purchase_db->where('A.ds_day_avg >=', $params['ds_day_avg_start']);
        }
        if(isset($params['ds_day_avg_end']) and $params['ds_day_avg_end'] != ''){
            $this->purchase_db->where('A.ds_day_avg <', $params['ds_day_avg_end']);
        }

        if(isset($params['os_day_avg_start']) and $params['os_day_avg_start'] != ''){
            $this->purchase_db->where('A.os_day_avg >=', $params['os_day_avg_start']);
        }
        if(isset($params['os_day_avg_end']) and $params['os_day_avg_end'] != ''){
            $this->purchase_db->where('A.os_day_avg <', $params['os_day_avg_end']);
        }

        if(isset($params['ds_deliverrate_start']) and $params['ds_deliverrate_start'] != ''){
            $this->purchase_db->where('A.ds_deliverrate >=', $params['ds_deliverrate_start']);
        }
        if(isset($params['ds_deliverrate_end']) and $params['ds_deliverrate_end'] != ''){
            $this->purchase_db->where('A.ds_deliverrate <=', $params['ds_deliverrate_end']);
        }

        if(isset($params['os_deliverrate_start']) and $params['os_deliverrate_start'] != ''){
            $this->purchase_db->where('A.os_deliverrate >=', $params['os_deliverrate_start']);
        }
        if(isset($params['os_deliverrate_end']) and $params['os_deliverrate_end'] != ''){
            $this->purchase_db->where('A.os_deliverrate <=', $params['os_deliverrate_end']);
        }


        $count_qb = clone $this->purchase_db;
        $results = $this->purchase_db->select('A.id,A.supplier_code,C.supplier_name,B.first_product_line,E.linelist_cn_name,
                C.status,LEFT(A.statis_month,7) AS statis_month,A.ds_day_avg,A.os_day_avg,A.ds_deliverrate,A.os_deliverrate')
            ->limit($limit, $offsets)
            ->order_by('A.id ASC')
            ->get()
            ->result_array();


        $count_row                       = $count_qb->select('count(A.id) as num')->get()->row_array();
        $total_count                     = isset($count_row['num']) ? (int)$count_row['num'] : 0;
        if($action == 'sum'){
            return $total_count;
        }



        $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
        $this->load->model('product/Product_line_model', 'Product_line_model');
        $this->load->helper('status_supplier');

        if(!empty($results)){
            $supplier_codes = array_unique(array_column($results, 'supplier_code'));
            // 获取支付方式, 结算方式
            $payment_info = $this->purchase_db->select("supplier_code,group_concat(DISTINCT B.settlement_name) as supplier_settlement")
                ->from('pur_supplier_payment_info AS A')
                ->join('pur_supplier_settlement AS B','A.supplier_settlement=B.settlement_code')
                ->where_in('supplier_code', $supplier_codes)
                ->where('is_del', 0)
                ->group_by('supplier_code')
                ->get()
                ->result_array();
            $payment_info = array_column($payment_info, NULL, 'supplier_code');


            foreach($results as $key => $val){
                $now_supplier_settlement = isset($payment_info[$val['supplier_code']]) ? $payment_info[$val['supplier_code']]['supplier_settlement'] : '';
                $results[$key]['supplier_settlement'] = str_replace(',','/',$now_supplier_settlement);
                $results[$key]['status'] = getCooperationStatus($val['status']);
                $results[$key]['ds_day_avg'] = format_two_point_price($val['ds_day_avg']);
                $results[$key]['os_day_avg'] = format_two_point_price($val['os_day_avg']);
                $results[$key]['ds_deliverrate'] = format_price($val['ds_deliverrate'] * 100,1).'%';
                $results[$key]['os_deliverrate'] = format_price($val['os_deliverrate'] * 100,1).'%';
            }
        }



        $drop_down_list = [];
        $down_settlement = $this->settlementModel->get_settlement();
        $down_oneline = $this->Product_line_model->get_product_line_list_first();
        $drop_down_list['down_settlement'] = array_column($down_settlement['list'],'settlement_name','settlement_code');
        $drop_down_list['down_cooperation_status'] = getCooperationStatus();
        $drop_down_list['down_oneline'] = array_column($down_oneline,'linelist_cn_name','product_line_id');


        $return_data = [
            'key'           => ['序号','供应商代码','供应商','一级产品线','合作状态','结算方式','统计月份','国内仓交付天数','海外仓交付天数','国内仓10天交付率','海外仓10天交付率'],
            'values'        => $results,
            'drop_down_box' => $drop_down_list,
            'paging_data'   => [
                'total'  => $total_count,
                'offset' => $page,
                'limit'  => $limit,
                'pages'  => ceil($total_count / $limit),
            ]
        ];

        return $return_data;

    }

}