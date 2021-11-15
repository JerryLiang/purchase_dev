<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/12/26
 * Time: 14:40
 */

class Rule_config_model extends Purchase_model
{
    //验货规则配置表
    protected $table_check_rule = 'supplier_check_rule';
    //验货规则配置-产品线关系表
    protected $table_check_rule_items = 'supplier_check_rule_items';

    public function __construct()
    {
        parent::__construct();
        $this->lang->load('common_lang');
        $this->load->model('product_line_model', 'product_line_model', false, 'product');
        $this->load->helper('status_order');
    }

    /**
     * @desc 获取验货规则列表数据
     * @author Justin
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function get_list_data($params, $offsets, $limit, $page)
    {
        $query = $this->purchase_db;
        $query->select('a.*');
        $query->from("{$this->table_check_rule} AS a");


        //按是否启用查询
        if (isset($params['enable_status']) && is_numeric($params['enable_status'])) {
            $query->where('a.status', $params['enable_status'], false);
        }

        if(SetAndNotEmpty($params, 'is_boutique', 'n')){
            $query->where('a.is_boutique', $params['is_boutique']);
        }
        if(SetAndNotEmpty($params, 'min_price')){
            $query->where('a.min_price >=', $params['min_price']);
        }
        if(SetAndNotEmpty($params, 'max_price')){
            $query->where('a.max_price <=', $params['max_price']);
        }
        if(SetAndNotEmpty($params, 'min_refund_rate')){
            $query->where('a.min_refund_rate >=', $params['min_refund_rate']);
        }
        if(SetAndNotEmpty($params, 'max_refund_rate')){
            $query->where('a.max_refund_rate <=', $params['max_refund_rate']);
        }

        //使用GROUP_CONCAT查询产品线字段，所以需按ID分组
        $query->group_by('a.id');

        $count_qb = clone $query;
        //统计总数要加上前面筛选的条件
        $total_count = $count_qb->get()->num_rows();

        //当传入自定义排序条件时，根据业务线排序，否则按照更新时间排序
        if (isset($params['sort_by']) && !empty($params['sort_by']) && in_array(strtoupper($params['sort_by']), ['ASC', 'DESC'])) {
            $this->purchase_db->order_by('purchase_type_id', $params['sort_by']);
        }

        //查询数据
        $result = $query->limit($limit, $offsets)->get()->result_array();

        //转换数据
        foreach ($result as &$item) {
            //转换状态对应中文
            $item['status_cn'] = getEnableStatus($item['status']);
            //最后一次修改人和修改时间
            $item['update_user'] = $item['update_user'] . ' ' . $item['update_time'];
            $item['is_boutique_id'] = $item['is_boutique'];
            $item['is_boutique'] = $item['is_boutique']==1 ? "是": "否";
            $item['price'] = ''; // is_boutique,a.,a.,a.refund_rate,
            $min_p = $item['min_price'];
            $max_p = $item['max_price'];
            if($min_p > 0 && $max_p > 0){
                $item['price'] = $min_p.'<= X <'.$max_p;
            }elseif ($min_p <= 0 && $max_p > 0){
                $item['price'] = 'X <'.$max_p;
            }elseif ($min_p > 0 && $max_p <= 0){
                $item['price'] = $min_p.'<= X';
        }

            $item['refund_rate'] = '';
            $is_boutique = ($item['is_boutique_id']==1 ? "": "非")."海外仓精品";
            $min_r = $item['min_refund_rate'];
            $max_r = $item['max_refund_rate'];
            if($min_r > 0 && $max_r > 0){
                $item['refund_rate'] = $is_boutique.$min_r."%".'<= X <'.$max_r."%";
            }elseif ($min_r <= 0 && $max_r > 0){
                $item['refund_rate'] = $is_boutique.$max_r."% <";
            }elseif ($min_r > 0 && $max_r <= 0){
                $item['refund_rate'] =$is_boutique. ">=".$max_r."%";
            }
        }

        //表头字段
        $key_table = array(
            $this->lang->myline('business_line'),
            $this->lang->myline('product_line_one'),
            $this->lang->myline('purchase_unit_price'),
            $this->lang->myline('purchase_amount'),
            $this->lang->myline('dimension'),
            $this->lang->myline('effective_time'),
            $this->lang->myline('enable_status'),
            $this->lang->myline('last_modified'),
            "是否熏蒸",
            "采购金额区间",
            "退款率"
        );

        $return_data = [
            'key' => $key_table,
            'values' => $result,
            'page_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
                'pages' => ceil($total_count / $limit)
            ],
            'drop_down_box' => [
                'business_line' => getBusinessLine(),
                'enable_status' => getEnableStatus(),
            ]
        ];

        return $return_data;
    }

    /**
     * @desc 获取编辑规则数据
     * @author Justin
     * @Date 2020/04/13
     * @return array
     */
    public function get_edit_data()
    {
        $query = $this->purchase_db;
        $query->select('a.id,a.purchase_type_id,a.purchase_unit_price,a.purchase_amount,a.type,a.effective_time,
        a.update_time,a.status,GROUP_CONCAT(b.product_line_id) AS product_line_id');
        $query->from("{$this->table_check_rule} AS a");
        $query->join("{$this->table_check_rule_items} AS b", 'a.id=b.rule_id');
        //使用GROUP_CONCAT查询产品线字段，所以需按ID分组
        $query->group_by('a.id');
        //查询数据
        $result = $query->get()->result_array();
        //转换数据
        foreach ($result as &$item) {
            $product_line_arr = explode(',', $item['product_line_id']);
            //一级产品线
            foreach ($product_line_arr as $line_id) {
                $product_line_cn = $this->product_line_model->get_product_top_line_data($line_id);
                $item['product_line_info'][$line_id] = isset($product_line_cn['linelist_cn_name']) ? $product_line_cn['linelist_cn_name'] : '';
            }

            //维度
            switch ($item['type']) {
                case 1:
                    $item['type_cn'] = '采购单';
                    break;
                default:
                    $item['type_cn'] = '';
                    break;
            }
            //业务线
            $item['business_line_cn'] = !empty($item['purchase_type_id']) ? getBusinessLine($item['purchase_type_id']) : '';
            //转换状态对应中文
            $item['status_cn'] = getEnableStatus($item['status']);
        }
        $return_data = [
            'values' => $result,
            'drop_down_box' => [
                'business_line' => getBusinessLine(),
                'enable_status' => getEnableStatus(),
                'product_line' => $this->product_line_model->get_product_line_list(0),
            ]
        ];

        return $return_data;
    }

    /**
     * @desc 批量编辑验货规则
     * @author Justin
     * @Date 2019/12/26
     * @param $data
     * @return array
     */
    public function rule_batch_edit($data, $effective_time = '0000-00-00 00:00:00')
    {
        $query = $this->purchase_db;
        //开启事务
        $query->trans_begin();
        try {
            $time = date('Y-m-d H:i:s');

            // 没有提交的ID全部删掉
            $has_id = [];
            foreach ($data as $key => $item) {
                if(isset($item['id']) && $item['id'] > 0)$has_id[] = (int)$item['id'];
            }
            if(count($has_id) > 0){
                $this->purchase_db->where_not_in('id', $has_id)->delete($this->table_check_rule);
            }

            foreach ($data as $key => $item) {
                $tmp = [];
                $tmp['id'] = (int)$item['id'];
                if(in_array($item['is_boutique'], ["0", "1"])){
                    $tmp['is_boutique'] = (int)$item['is_boutique'] == 1 ? 1: 0;
                }
                $tmp['min_price'] = (int)$item['min_price'];
                $tmp['max_price'] = (int)$item['max_price'];
                $tmp['min_refund_rate'] = (int)$item['min_refund_rate'];
                $tmp['max_refund_rate'] = (int)$item['max_refund_rate'];
                $tmp['effective_time'] = $effective_time;
                $tmp['update_time'] = $time;
                $tmp['update_user'] = getActiveUserName();
                $tmp['status'] = (int)$item['status'];
                $id = $tmp['id'];
                if (!empty($tmp['id'])) {
                    unset($tmp['id']);
                    $this->purchase_db->update($this->table_check_rule, $tmp, ["id" => $id]);
                } else {
                    unset($tmp['id']);
                    $tmp['type'] = 1;
                    $tmp['create_time'] = $time;
                    $this->purchase_db->insert($this->table_check_rule, $tmp);
                }
            }

            if ($query->trans_status() === false) {
                throw new Exception('编辑失败');
            } else {
                $query->trans_commit();
                $flag = true;
                $msg = '修改成功';
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $flag = false;
            $msg = $e->getMessage();
        }
        return ['flag' => $flag, 'msg' => $msg];
    }
}