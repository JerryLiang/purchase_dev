<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/1/4
 * Time: 15:03
 */

class Check_product_model extends Purchase_model
{
    //采购单主表
    protected $table_purchase_order = 'purchase_order';
    //采购单明细表
    protected $table_purchase_order_items = 'purchase_order_items';
    //产品信息表
    protected $table_product = 'product';
    //验货规则配置表
    protected $table_check_rule = 'supplier_check_rule';
    //验货规则配置-产品线关系表
    protected $table_check_rule_items = 'supplier_check_rule_items';
    //采购单与需求单号关系表
    protected $table_purchase_suggest_map = 'purchase_suggest_map';
    //供应商联系表
    protected $table_supplier_contact = 'supplier_contact';
    //供应商表
    protected $table_supplier = 'supplier';
    //验货主表
    protected $table_supplier_check = 'supplier_check';
    //验货记录表
    protected $table_supplier_check_record = 'supplier_check_record';
    //验货sku表
    protected $table_supplier_check_sku = 'supplier_check_sku';
    //仓库信息表
    protected $table_warehouse = 'warehouse';
    //验货操作日志表
    protected $table_check_log = 'supplier_check_log';
    //文件资料表
    protected $table_check_upload = 'supplier_check_upload';
    //订单跟踪表
    protected $table_purchase_progress = 'purchase_progress';
    //采购订单-取消未到货主表
    protected $table_order_cancel = 'purchase_order_cancel';
    //采购订单-取消未到货明细表
    protected $table_order_cancel_detail = 'purchase_order_cancel_detail';
    //采购订单-报损表
    protected $table_order_reportloss = 'purchase_order_reportloss';
    //缺货表
    protected $table_stock_owes = 'stock_owes';
    //采购单请款单明细表
    protected $table_purchase_order_pay_detail = 'purchase_order_pay_detail';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
        $this->lang->load('common_lang');
        $this->load->helper('user');
        $this->load->model('product_line_model', 'product_line', false, 'product');
        $this->load->helper('common');
    }

    /**
     * 获取批量确认数据
     * /supplier/check/check_product/get_check_batch_confirm_data
     * 32107
     */
    public function get_check_batch_confirm_data($ids)
    {
        $res = ["code" => 0, "msg" => "默认操作失败！"];

        $data = $this->purchase_db
            ->select("sc.*")
            ->from($this->table_supplier_check." as sc")
            ->where_in("sc.id", $ids)
            ->get()->result_array();
        if(!$data || count($data) == 0){
            $res["msg"] = "没有要确认的验货记录！";
            return $res;
        }
        $supplier = [];
        foreach ($data as $val){
            if(!in_array($val['supplier_code'], $supplier))$supplier[] = $val['supplier_code'];
        }
        if(count($supplier) != 1){
            $res["msg"] = "供应商不一致,请重新选择！";
            return $res;
        }

        return $res;
    }

    /**
     * 系统自动创建验货单
     * @param string $purchase_number 采购单号
     * @return array
     */
    public function auto_create_inspection($purchase_number = '')
    {
        if (empty($purchase_number)) return ['msg' => '验货PO不能为空', 'flag' => false];

        $inspection_sku_data = array(); //需要生成验货单的数据
        $total_price_data = array();    //符合单个sku采购单价，采购金额总和数据
        $current_time = time();         //当前时间

        //1、获取待验证数据
        $result = $this->_get_check_purchase_data($purchase_number);
        if (empty($result)) return ['msg' => '获取采购单[' . $purchase_number . ']数据为空', 'flag' => false];

        //校验PO+SKU是否已经已生成验货单
        $sku_array = array_column($result, 'sku');
        $verify_result = $this->_po_sku_is_exist($purchase_number, $sku_array);
        //PO+SKU已经已生成验货单
        if ($verify_result['status']) return ['msg' => $verify_result['msg'], 'flag' => false];

        $check_rule = $this->get_check_rule(); // 获取验货规则

        $sku_list = array_unique(array_column($result, 'sku'));
        $sku_refund = $this->get_sku_refund_rate($sku_list); // 获取SKU退款率

        //2、验证采购单价是否符合验货规则
        $this_time = date("Y-m-d H:i:s");
        $logs = "";
        foreach ($result as $item) {
            foreach ($check_rule as $ru_val){
                if($this_time < $ru_val['effective_time'])continue; // 如果未生效

                $item_price = (int)$item['purchase_unit_price'] * (int)$item['confirm_amount'];
                $row_sku = $item['sku'];
                $has_rate = isset($sku_refund[$row_sku]) && $sku_refund[$row_sku] > 0 ? (int)$sku_refund[$row_sku]:0;

                // 条件
                $is_boutique = $item['is_overseas_boutique'] == $ru_val['is_boutique'] ? true:false;
                $price = $this->value_between($item_price, (int)$ru_val['min_price'], (int)$ru_val['max_price']);
                $refund = $this->value_between($has_rate, (int)$ru_val['min_refund_rate'], (int)$ru_val['max_refund_rate']);
                $logs = "is_boutique:{$is_boutique},price:{$price},refund:{$refund}";
                if($is_boutique && $price && $refund){ // 如果检验通过，则跳出
                    $inspection_sku_data[] = $row_sku;
                    break;
                }
            }
        }

        if (empty($inspection_sku_data)) return ['msg' => '提交的SKU均不满足验货条件'.json_encode($result)."#########".json_encode($check_rule)."#####".$logs, 'flag' => false];

        $res = $this->get_assembly_check_order_data($purchase_number, ['sku' => $inspection_sku_data]);
        $result = ['msg' => '操作失败', 'flag' => false];
        if($res['code'] == 1){
            $result['flag'] = true;
            $result['msg'] = '操作成功！';
        }
        return $result;
        /*
        //组织创建验货单数据
        $insert_data = $this->_assembly_check_order_data($purchase_number, ['sku' => $inspection_sku_data], 'system');
        if (empty($insert_data['master_data']) || empty($insert_data['sku_data'])){
            return ['msg' => '未获取到要创建验货单的相关数据', 'flag' => false];
        }
        //写入验货主表和验货sku表
        return $this->_insert_check_data($insert_data['master_data'], $insert_data['sku_data'], $insert_data['record_data']);
        */
    }

    /**
     * 获取验货规则
     */
    private function get_check_rule()
    {
        $data = $this->purchase_db->from("supplier_check_rule")->get()->result_array();
        return $data && count($data) >0 ? $data : [];
    }

    /**
     * 获取sku退款率
     */
    private function get_sku_refund_rate($sku=[])
    {
        if(count($sku) == 0)return [];
        $data = $this->purchase_db->from("product_statistics")
            ->select("sku,refund_rate")
            ->where_in("sku", $sku)
            ->where("statistics_type = ", 1)
            ->get()
            ->result_array();
        if(!$data || count($data) == 0)return [];
        $temp = [];
        foreach ($data as $val){
            $temp[$val['sku']] = !empty($val['refund_rate']) ? (int)$val['refund_rate'] : 0;
        }
        return $temp;
    }

    /**
     * 判断某个值是否在一个特定的区间内
     * @param int  $check
     * @param int  $min
     * @param int  $max
     * @return  bool
     */
    private function value_between($check=0, $min=0, $max=0)
    {
        if($check <= 0)return false;
        if($min > 0 && $max > 0 && $check >= $min && $check <= $max){
            return true;
        }
        if($min == 0 && $max > 0 && $check <= $max){
            return true;
        }
        if($min > 0 && $max == 0 && $check >= $min){
            return true;
        }
        return false;
    }

    /**
     * 获取所有验货规则
     */
    private function get_check_all_rule()
    {
        $data = $this->purchase_db->from("supplier_check_rule")->get()->result_array();
    }

    /**
     * 手工创建验货单
     * @param string $purchase_number 采购单号
     * @param array $params
     * @return array
     */
    public function create_inspection($purchase_number = '', $params = array())
    {
        if (empty($purchase_number)) return ['msg' => '验货PO不能为空', 'flag' => false];

        //验证采购单是否已作废（有效采购数量等于0）
        $order_info = $this->purchase_db->select('purchase_order_status')
            ->where('purchase_number', $purchase_number)
            ->get($this->table_purchase_order)->row_array();

        if (empty($order_info)) {
            return ['msg' => '采购单[' . $purchase_number . ']不存在', 'flag' => false];
        } elseif (PURCHASE_ORDER_STATUS_CANCELED == $order_info['purchase_order_status']) {
            return ['msg' => '采购单已作废，不允许提交', 'flag' => false];
        }

        //验证sku是否属于该po
        $sku_info = $this->_get_sku_data_by_po($purchase_number);
        $sku_input = array_combine($params['sku'], $params['sku']);
        foreach ($sku_info as $item) {
            unset($sku_input[$item['sku']]);
        }
        unset($sku_info);
        if (count($sku_input)) return ['msg' => 'SKU[' . implode(',', $sku_input) . ']不属于采购单[' . $purchase_number . ']', 'flag' => false];

        //校验PO+SKU是否已经已生成验货单
        $verify_result = $this->_po_sku_is_exist($purchase_number, $params['sku']);

        //PO+SKU已经已生成验货单
        if ($verify_result['status']) {
            return ['msg' => $verify_result['msg'], 'flag' => false];
        }

        //组织创建验货单数据
        $insert_data = $this->_assembly_check_order_data($purchase_number, $params, 'manually');
        if (empty($insert_data['master_data']) OR empty($insert_data['sku_data'])){
            return ['msg' => '未获取到要创建验货单的相关数据', 'flag' => false];
        }

        //写入验货主表和验货sku表
        return $this->_insert_check_data($insert_data['master_data'], $insert_data['sku_data'], $insert_data['record_data']);
    }

    /**
     * 获取备货单数据
     */
    public function get_check_suggest_data($suggest)
    {
        $data = $this->purchase_db->from("purchase_order_items")
            ->where_in("suggest_number", $suggest)
            ->get()
            ->result_array();
    }

    /**
     * 获取验货列表数据
     * @param $params
     * @param $offsets
     * @param $limit
     * @param $page
     * @param $export
     * @return array
     */
    public function get_list_data($params = array(), $offsets = 1, $limit = 20, $page = 1, $export = false)
    {
        $query = $this->purchase_db;
        $query->from("{$this->table_supplier_check_sku} AS c");
        $query->join("{$this->table_supplier_check} AS a", 'a.id=c.check_id AND c.batch_no=a.check_times', 'left');
        $query->join("{$this->table_supplier_check_record} AS b", 'a.id=b.check_id AND b.batch_no=a.check_times');

        if (!empty($params['sku']) || !empty($params['demand_number']) || !empty($params['product_line_id'])) {
            //按sku查询
            if (!empty($params['sku'])) {
                $query->where_in('c.sku', array_unique(array_filter(explode(' ', $params['sku']))));
            }
            //按备货单号查询
            if (!empty($params['demand_number'])) {
                $query->where_in('c.demand_number', array_unique(array_filter(explode(' ', $params['demand_number']))));
            }
            //一级产品线
            if (!empty($params['product_line_id'])) {
                $query->where('c.product_line_id_top', $params['product_line_id'], false);
            }
        }

        if (!empty($params['supplier_code'])) {
            $query->where('a.supplier_code', $params['supplier_code']);
        }
        //按申请编码查询
        if (!empty($params['check_code'])) {
            $query->where('a.check_code', $params['check_code']);
        }
        //按采购单查询
        if (!empty($params['purchase_number'])) {
            $query->where_in('a.purchase_number', array_unique(array_filter(explode(' ', $params['purchase_number']))));
        }
        //按申请人查询
        if (!empty($params['apply_user_id'])) {
            $query->where_in('a.apply_user_id', $params['apply_user_id']);
        }
        //按申请人查询
        if (!empty($params['supplier'])) {
            $query->where_in('a.supplier_code', $params['supplier']);
        }
        //按是否异常查询
        if (isset($params['is_abnormal']) && is_numeric($params['is_abnormal'])) {
            $query->where('a.is_abnormal', $params['is_abnormal']);
        }

        // 组别
        if(isset($params['groupdatas']) && !empty($params['groupdatas'])){
            $query->where_in('a.buyer_id', $params['groupdatas']);
        }
        //按采购员查询
        if (!empty($params['buyer_id']) && is_array($params['buyer_id'])) {
            $query->where_in('a.buyer_id', $params['buyer_id']);
        }
        //按提交人查询
        if (!empty($params['confirm_user_id'])) {
            $query->where_in('b.confirm_user_id', $params['confirm_user_id']);
        }
        //按是否加急查询
        if (isset($params['is_urgent']) && is_numeric($params['is_urgent'])) {
            $query->where('a.is_urgent', $params['is_urgent']);
        }
        //按业务线查询
        if (!empty($params['purchase_type_id'])) {
            if(is_array($params['purchase_type_id'])){
                $query->where_in('a.purchase_type_id', $params['purchase_type_id']);
            }else{
                $query->where('a.purchase_type_id', $params['purchase_type_id']);
            }
        }
        //按检验状态查询
        if (!empty($params['status'])) {
            $query->where_in('a.status', $params['status']);
        }
        //按申请时间查询
        if (!empty($params['create_time_start']) && !empty($params['create_time_end'])) {
            $query->where('a.create_time >=', $params['create_time_start'] . ' 00:00:00');
            $query->where('a.create_time <=', $params['create_time_end'] . ' 23:59:59');
        }
        //按期望验货时间查询
        if (!empty($params['check_expect_time_start']) && !empty($params['check_expect_time_end'])) {
            $query->where('b.check_expect_time >=', $params['check_expect_time_start'] . ' 00:00:00');
            $query->where('b.check_expect_time <=', $params['check_expect_time_end'] . ' 23:59:59');
        }
        //按期实际验货时间查询
        if (!empty($params['check_time_start']) && !empty($params['check_time_end'])) {
            $query->where('b.check_time >=', $params['check_time_start'] . ' 00:00:00');
            $query->where('b.check_time <=', $params['check_time_end'] . ' 23:59:59');
        }
        //按特批出货查询
        if (isset($params['is_special']) && is_numeric($params['is_special'])) {
            $query->where('a.is_special', $params['is_special'], false);
        }
        //按检验次数查询
        if (isset($params['check_times']) && in_array($params['check_times'], [1, 2])) {
            if (1 == $params['check_times']) {
                $query->where('a.check_times = 1');
            } else {
                $query->where('a.check_times > 1');
            }
        }
        //按选择记录导出
        if ($export && !empty($params['ids'])) {
            $query->where_in('c.id', $params['ids']);
        }

        $count_qb = clone $query;
        $count_qb_tab = clone $query;
        //统计总数要加上前面筛选的条件
        $select = "c.id,
            a.check_code,
            a.id as check_id,
            a.complete_address,
            a.supplier_check_times,
            a.supplier_name,
            a.apply_user_name,
            a.apply_remark,
            a.create_time,
            a.buyer_name,
            a.check_times,
            a.status,
            a.order_type,
            a.purchase_number,
            a.is_abnormal,
            a.approval_user_name,
            a.approval_time,
            a.approval_remark,
            a.is_special,
            a.is_urgent,
            a.contact_province,
            a.contact_city,
            a.contact_area,
            a.contact_address,
            b.contact_person,
            b.phone_number,
            b.check_expect_time,
            b.check_time,
            b.confirm_user_name as confirm_user,
            b.confirm_time,
            b.confirm_remark,
            c.sku,
            c.demand_number,
            c.purchase_num,
            c.check_num,
            a.is_urgent,
            c.judgment_result,
            c.check_number as check_qty";
        $query->select($select)->group_by("c.id");
        $total_count = $count_qb->get()->num_rows();

        $tab_data = $count_qb_tab->select("a.status, count(a.status) as new_status")->group_by("a.status")->get()->result_array();
        $tab_list = [];
        foreach ($tab_data as $val){
            $tab_list[$val['status']] = $val['new_status'];
        }

        //查询数据
        if ($export) {//导出不排序
            $result = $query->limit($limit, $offsets)->get()->result_array();
        } else {//列表查询
            $result = $query->order_by('c.id', 'DESC')->limit($limit, $offsets)->get()->result_array();
        }

        //转换数据
        $region_temp = $this->purchase_db->select('region_name as title,region_code as id')
            ->from('pur_region')
            ->where_in("region_type", [1,2,3])
            ->get()->result_array();
        $region = [];
        foreach ($region_temp as $val){
            if(!empty($val['id']) && $val['id'] > 0)$region[$val['id']] = $val['title'];
        }

        foreach ($result as &$item) {
            //验货单类型
            $item['order_type_cn'] = !empty($item['order_type']) ? getCheckOrderType($item['order_type']) : '';
            //验货单状态
            $item['status_cn'] = !empty($item['status']) ? getCheckOrderStatus($item['status']) : '';
            //特批出货
            $item['is_special_cn'] = !is_null($item['is_special']) ? getCheckOrderSpecialStatus($item['is_special']) : '';
            //是否異常
            $item['is_abnormal_cn'] = !is_null($item['is_abnormal']) ? getCheckOrderAbnormalStatus($item['is_abnormal']) : '';
            //审核时间
            $item['approval_time'] = ('0000-00-00 00:00:00' == $item['approval_time']) ? '' : $item['approval_time'];
            //期望验货时间
            $item['check_expect_time'] = ('0000-00-00' == $item['check_expect_time']) ? '' : $item['check_expect_time'];
            //提交时间
            $item['confirm_time'] = ('0000-00-00 00:00:00' == $item['confirm_time']) ? '' : $item['confirm_time'];
            //实际验货时间
            $item['check_time'] = ('0000-00-00' == $item['check_time']) ? '' : $item['check_time'];
            $item['is_urgent'] = $item['is_urgent'] == 1 ? "是": "否";
            // 1-免检驳回，2-免检，3-合格，4-不合格，5-转IQC
            $judgment_result = [
                1 => '免检驳回',
                2 => '免检',
                3 => '合格',
                4 => '不合格',
                5 => '转IQC',
            ];
            $item['judgment_result'] = isset($judgment_result[$item['judgment_result']]) ? $judgment_result[$item['judgment_result']]: "--";
            $cpr = $item['contact_province'];
            $cci = $item['contact_city'];
            $car = $item['contact_area'];
            $item['complete_address'] = (isset($region[$cpr]) ? $region[$cpr] : "").
                (isset($region[$cci]) ? $region[$cci] : "").
                (isset($region[$car]) ? $region[$car] : "").$item['contact_address'];
        }

        //表头字段
        $key_table = array(
            $this->lang->myline('check_code'),
            $this->lang->myline('apply_info'),
            $this->lang->myline('buyer'),
            $this->lang->myline('supplier_info'),
            $this->lang->myline('po_number'),
            $this->lang->myline('check_times'),
            $this->lang->myline('check_status'),
            $this->lang->myline('confirm_info'),
            $this->lang->myline('approval_info'),
            $this->lang->myline('check_time'),
            $this->lang->myline('is_special_title'),
        );

        if ($export) {
            $return_data = [
                'values' => $result,
                'total' => $total_count
            ];
        } else {
            $this->load->model('product_line_model', 'product_line_model', false, 'product');
            $this->load->model('user/purchase_user_model');
            $this->load->model('user/user_group_model');
            $user_group = $this->user_group_model->getUserGroup();
            $user_group = isset($user_group['alias'])?$user_group['alias']:[];
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
                    'buyer_dropdown' => getBuyerDropdown(),             //申请人下拉,采购员下拉
                    'confirm_user_dropdown' => getAllUserDropDown(),    //提交人下拉
                    'is_abnormal' => getCheckOrderAbnormalStatus(),     //是否异常下拉
                    'is_urgent' => getCheckOrderUrgentStatus(),         //是否加急下拉
                    'check_suggest' => ["1" => "验货", "2" => "免检"],         //是否加急下拉
                    'check_order_status' => getCheckOrderStatus(),      //检验状态下拉
                    'is_special' => getCheckOrderSpecialStatus(),       //是否特批出货下拉
                    'check_times' => getCheckOrderCheckTimes(),         //检验次数下拉
                    'business_line' => getBusinessLine(),               //业务线下拉
                    'user_group' => $user_group,               // 用户组别
                    'product_line' => $this->product_line_model->get_product_line_list(0),//一级产品线下拉
                    'tab' => $tab_list,
                ]
            ];
        }

        return $return_data;
    }

    /**
     * 获取PO详情
     * @param $purchase_number
     * @param $id
     * @return array
     */
    public function get_po_detail($purchase_number, $id)
    {
        //验货sku表数据
        $sku_table = "(SELECT s1.* FROM pur_supplier_check_sku s1" .
            " JOIN (SELECT sku,substring_index(group_concat(batch_no ORDER BY batch_no DESC),',',1) AS batch_no FROM pur_supplier_check_sku WHERE check_id={$id} GROUP BY sku) s2" .
            " ON s1.sku=s2.sku AND s1.batch_no=s2.batch_no)";
        $query = $this->purchase_db;
        $query->select('a.purchase_number,b.demand_number,b.sku,b.purchase_num,b.warehouse_code,b.invalid_num');
        $query->select('c.warehouse_name,d.pay_status,d.purchase_order_status,b.judgment_result');
        $query->from("{$this->table_supplier_check} AS a");
        $query->join("{$sku_table} b", 'a.id=b.check_id');
        $query->join("{$this->table_warehouse} AS c", 'b.warehouse_code=c.warehouse_code', 'LEFT');
        $query->join("{$this->table_purchase_order} AS d", 'd.purchase_number=a.purchase_number', 'LEFT');
        $query->where('a.purchase_number', $purchase_number);
        $query->where('a.id', $id);
        $result = $query->get()->result_array();
        foreach ($result as &$item) {
            //采购订单状态
            $item['purchase_order_status_cn'] = isset($item['purchase_order_status']) ? getPurchaseStatus($item['purchase_order_status']) : '';
            //采购订单付款状态
            $item['pay_status_cn'] = isset($item['pay_status']) ? getPayStatus($item['pay_status']) : '';
            //验货单状态
            $item['judgment_result_cn'] = !empty($item['judgment_result']) ? getCheckOrderResult($item['judgment_result']) : '';
        }
        //表头字段
        $key_table = array(
            $this->lang->myline('po_number'),
            $this->lang->myline('demand_number'),
            $this->lang->myline('sku'),
            $this->lang->myline('purchase_num'),
            $this->lang->myline('pur_warehouse'),
            $this->lang->myline('check_result'),
            $this->lang->myline('invalid_num'),
            $this->lang->myline('order_status'),
            $this->lang->myline('pay_status'),
        );
        $return_data = [
            'key' => $key_table,
            'values' => $result,
        ];
        return $return_data;
    }

    /**
     * 手工创建验货单-验证验货PO是否属于等待到货及之后的状态
     * 并返回sku和供应商信息
     * @param $purchase_number
     * @return array
     *  status（1-验证通过，0-未通过验证）
     *  data['sku']（sku信息）
     *  data['supplier']（供应商信息）
     *  msg（验证未通过提示消息）
     */
    public function check_po_status($purchase_number)
    {
        $query = $this->purchase_db;
        $query->select('a.purchase_number,a.purchase_order_status,b.sku,a.supplier_code,a.supplier_name');
        $query->from("{$this->table_purchase_order} AS a");
        $query->join("{$this->table_purchase_order_items} AS b", 'a.purchase_number = b.purchase_number');
        $query->where('a.purchase_number', $purchase_number);
        $result = $query->get()->result_array();
        //该po在系统中不存在
        if (empty($result)) {
            return ['status' => 0, 'data' => [], 'msg' => '采购订单[' . $purchase_number . ']不存在'];
        }
        //验证订单状态是否属于等待到货及之后的状态(除已作废订单)
        $order_status = isset($result[0]['purchase_order_status']) ? $result[0]['purchase_order_status'] : '';
        if (!in_array($order_status, [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
            PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION, PURCHASE_ORDER_STATUS_ALL_ARRIVED,
            PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
            PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT, PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND, PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT
        ])) {
            $order_status_cn = !empty($order_status) ? '[' . getPurchaseStatus($order_status) . ']' : '';
            return ['status' => 0, 'data' => [], 'msg' => '采购订单状态' . $order_status_cn . '不符合验货条件'];
        }

        //获取供应商信息
        $supplier_code = isset($result[0]['supplier_code']) ? $result[0]['supplier_code'] : '';
        $supplier = $this->_get_supplier_info($supplier_code);
        $supplier_data = array(
            'contact_province' => isset($supplier['ship_province']) ? $supplier['ship_province'] : 0,//联系地址-省份
            'contact_city' => isset($supplier['ship_city']) ? $supplier['ship_city'] : 0,//联系地址-城市
            'contact_area' => isset($supplier['ship_area']) ? $supplier['ship_area'] : 0,//联系地址-地区
            'contact_address' => isset($supplier['ship_address']) ? $supplier['ship_address'] : '',//联系地址-详细地址
            'contact_person' => isset($supplier['contact_person']) ? $supplier['contact_person'] : '',//联系人
            'phone_number' => isset($supplier['contact_number']) ? $supplier['contact_number'] : '',//联系电话
            'supplier_check_times' => isset($supplier['check_times']) ? $supplier['check_times'] : 0,
            'supplier_code' => $supplier_code,
            'supplier_name' => isset($result[0]['supplier_name']) ? $result[0]['supplier_name'] : '',
            'check_times' => 0,//验货编码维度的验货次数（创建的时候永远为0次）
        );
        //sku信息（需要排除已生成验货单的sku）
        $order_sku = array_column($result, 'sku');//PO单全部sku
        $order_sku = array_combine($order_sku, $order_sku);

        $exist_sku = $query->select('b.sku')
            ->from("{$this->table_supplier_check} AS a")
            ->join("{$this->table_supplier_check_sku} AS b", 'a.id = b.check_id')
            ->where('a.purchase_number', $purchase_number)
            ->group_by('b.sku')->get()->result_array();

        //排除已生成验货单的sku
        if (!empty($exist_sku)) {
            $exist_sku = array_column($exist_sku, 'sku');//已生成验货单的sku
            foreach ($exist_sku as $idx) {
                unset($order_sku[$idx]);
            }
        }
        //PO单全部sku已被生成验货单
        if (empty($order_sku)) {
            return ['status' => 0, 'data' => [], 'msg' => '采购订单[' . $purchase_number . ']所有SKU均已生成过验货单'];
        }

        $return_data = array(
            'status' => 1,
            'data' => array(
                'supplier' => $supplier_data,
                'sku' => $order_sku,
            ),
            'msg' => ''
        );
        return $return_data;
    }

    /**
     * 根据ID获取验货单详情
     * @param int $id
     * @param int $type 1-采购确认页面，2-编辑页面
     * @return array
     */
    public function get_order_detail($id, $type)
    {
        $query = $this->purchase_db;

        //验货状态为‘免检驳回’时，只获取验货结果为免检驳回的数据
        //获取编辑页面详情时，验货状态为‘不合格待确认’或‘验货不合格’时，只获取最新批次号，验货结果为不合格的sku数据
        if (2 == $type) {
            //获取验货单信息
            $order_info = $this->_get_order_info($id);
            if (CHECK_ORDER_STATUS_EXEMPTION_REJECT == $order_info['status']) {
                $query->where('b.judgment_result', CHECK_ORDER_RESULT_EXEMPTION_REJECT);
            } elseif (CHECK_ORDER_STATUS_UNQUALIFIED == $order_info['status']
                OR CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM == $order_info['status']) {
                $query->where('b.judgment_result', CHECK_ORDER_RESULT_UNQUALIFIED);
            }
            //排除特批出货的数据
            $query->where('b.is_special', 0);
        }

        $query->select('a.purchase_number,a.check_code,c.contact_person,c.phone_number,a.is_urgent');
        $query->select('a.contact_province,a.contact_city,a.contact_area,a.contact_address,a.check_times');
        $query->select('a.order_type,a.check_suggest,c.check_expect_time,a.apply_remark,a.supplier_code,a.supplier_name,GROUP_CONCAT(b.sku) AS sku');
        $query->from("{$this->table_supplier_check} AS a");
        $query->join("{$this->table_supplier_check_sku} AS b", 'a.id=b.check_id AND b.batch_no=a.check_times');
        $query->join("{$this->table_supplier_check_record} AS c", 'a.id=c.check_id AND c.batch_no=a.check_times');
        $query->where('a.id', $id);

        $result = $query->get()->row_array();

        if (!empty($result['sku'])) {
            $result['sku'] = explode(',', $result['sku']);
            $flag = true;
        } else {
            $result = array();
            $flag = false;
        }
        return ['flag' => $flag, 'data' => $result];
    }

    /**
     * 采购确认验货单
     * @param $id
     * @param array $params
     * @return array
     */
    public function order_confirm($id, $params = array())
    {
        //验证订单是否为待采购确认状态
        $order_info = $this->_get_order_info($id);
        $status = isset($order_info['status']) ? $order_info['status'] : 0;
        if (CHECK_ORDER_STATUS_WAITING_PURCHASER_CONFIRM != $status) {
            return ['msg' => '验货单不是‘待采购确认’状态', 'flag' => false];
        }

        //系统创建的验货单，需要验证是否存在异常sku（重新计算sku的采购金额是否仍然满足验货条件，不满足即为异常），异常不允许提交
        //手工创建的验货单，验证是否所有sku有效采购数量=0，异常不允许提交
        //系统创建或手工创建，验货单中部分sku有效采购数量=0，标识其中sku异常，验货单异常，提示部分sku异常
        $res = $this->check_is_abnormal($order_info['purchase_number'], 'order_confirm');
        if (!$res['flag']) {
            //发生错误
            return ['msg' => $res['msg'], 'flag' => false];
        } elseif (!empty($res['abnormal_data']) && ($res['abnormal_data']['is_high_level'] OR $res['abnormal_data']['is_all_abnormal'])) {
            //不满足系统验货条件导致异常，或者验货单全部sku有效采购数量=0导致异常
            return ['msg' => '验货单为‘异常’状态，不允许提交', 'flag' => false];
        } elseif (!empty($res['abnormal_data']) && !$res['abnormal_data']['is_all_abnormal'] && !empty($res['abnormal_data']['sku'])) {
            //验货单其中某个sku有效采购数量=0导致异常
            if (!$params['is_force_submit']) {
                return ['msg' => '验货单部分SKU有效采购数量=0异常，是否确定继续提交？', 'flag' => false, 'is_warning' => $res['abnormal_data']['sku']];
            }
        }

        //保存确认数据
        $time = date('Y-m-d H:i:s');
        $query = $this->purchase_db;
        $query->trans_begin();
        try {
            //获取当前登录用户信息
            $info = getActiveUserInfo();
            //验货单状态
            $status = (2 == $params['is_check']) ? CHECK_ORDER_STATUS_WAITING_EXEMPTION_AUDIT : CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM;

            //获取验货单po+sku数据
            $purchase_data = $query->select('a.purchase_number,b.sku')->from("{$this->table_supplier_check} a")
                ->join("{$this->table_supplier_check_sku} b", 'a.id=b.check_id')
                ->where('a.id', $id)->get()->result_array();
            if (empty($purchase_data)) {
                throw new Exception('获取验货单数据失败');
            }
            $po = $purchase_data[0]['purchase_number'];
            $sku = array_column($purchase_data, 'sku');

            /**更新采购单明细表和采购单请款单明细表验货状态**/
            $this->_update_order_status($po, $sku, ['status' => $status]);

            /**更新验货主表**/
            $master_update_data = array(
                'is_urgent' => $params['is_urgent'],
                'contact_province' => $params['contact_province'],
                'contact_city' => $params['contact_city'],
                'contact_area' => $params['contact_area'],
                'contact_address' => $params['contact_address'],
                'complete_address' => $params['complete_address'],
                'order_type' => $params['order_type'],
                'check_suggest' => $params['is_check'],
                'status' => $status,
                'modify_user_name' => $info['user_name'],
                'is_push' => 1,
                'push_time' => $time
            );
            $query->update($this->table_supplier_check, $master_update_data, ['id' => $id]);

            /**更新验货记录表**/
            $record_update_data = array(
                'contact_person' => $params['contact_person'],
                'phone_number' => $params['phone_number'],
                'confirm_user_id' => $info['user_id'],
                'confirm_user_number' => $info['staff_code'],
                'confirm_user_name' => $info['user_name'],
                'confirm_remark' => $params['remark'],
                'confirm_time' => $time
            );
            //是否验货为‘验货’时才有验货时间
            if (!empty($params['check_expect_time'])) {
                $record_update_data['check_expect_time'] = date('Y-m-d', strtotime($params['check_expect_time']));
            }
            $query->update($this->table_supplier_check_record, $record_update_data, ['check_id' => $id]);

            /**写入操作日志表**/
            $this->_insert_log([
                'id' => $id,
                'type' => '采购确认',
                'content' => $params['remark']
            ]);

            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                $result = ['msg' => '操作失败', 'flag' => false];
            } else {
                //获取推送数据
                $push_data = $this->_get_push_inspection_data($id);
                if (!$push_data['flag']) {
                    throw new Exception($push_data['msg']);
                }
                //获取推送url
                $url = getConfigItemByName('api_config', 'check_product', 'push_inspect_order');
                //推送到新产品系统
                $push_result = $this->_push_product_sys($push_data['data'], $url);
                if (!$push_result['flag']) {
                    throw new Exception($push_result['msg']);
                } else {
                    //推送成功，写入日志
                    operatorLogInsert(
                        [
                            'type' => 'INSPECT_ORDER_PUSH',
                            'content' => '采购确认验货单推送产品系统',
                            'detail' => json_encode($push_data['data']),
                            'user' => getActiveUserName(),
                        ]);
                }
                $query->trans_commit();
                $result = ['msg' => '', 'flag' => true];
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $result = ['msg' => $e->getMessage(), 'flag' => false];
            //保存系统日志
            operatorLogInsert([
                'id' => $id,
                'type' => 'INSPECT_ORDER_PUSH',
                'content' => '采购确认失败',
                'detail' => $e->getMessage(),
                'user' => getActiveUserName(),
            ]);
        }
        return $result;
    }

    /**
     * 编辑验货单(重验申请)
     * @param $id
     * @param array $params
     * @return array
     */
    public function order_edit($id, $params = array())
    {
        //验证验货结果是否为'免检驳回'或‘不合格’状态
        $order_info = $this->_get_order_info($id, 'order_edit');
        //验货结果
        $judgment_result = isset($order_info['judgment_result']) ? $order_info['judgment_result'] : 0;
        if (!in_array($judgment_result, [CHECK_ORDER_RESULT_EXEMPTION_REJECT, CHECK_ORDER_RESULT_UNQUALIFIED])) {
            return ['msg' => '验货结果为‘免检驳回’或‘不合格’状态，才允许申请重验', 'flag' => false];
        }
        //批次号
        $batch_no = isset($order_info['check_times']) ? $order_info['check_times'] : 0;
        //供应商编码
        $supplier_code = $order_info['supplier_code'];
        //采购单号
        $purchase_number = $order_info['purchase_number'];

        $query = $this->purchase_db;
        $query->trans_begin();
        try {
            //获取当前登录用户信息
            $info = getActiveUserInfo();
            $time = date('Y-m-d H:i:s');
            //获取‘免检驳回’或‘不合格’状态，并且非特批出货的sku数据
            $sku_data_tmp = $query->select('product_line_id,product_line_id_top,demand_number,sku,unit_price,purchase_num,warehouse_code')
                ->from("{$this->table_supplier_check_sku}")
                ->where('check_id', $id)
                ->where('batch_no', $batch_no)
                ->where('is_special', 0)
                ->where_in('judgment_result', [CHECK_ORDER_RESULT_EXEMPTION_REJECT,CHECK_ORDER_RESULT_UNQUALIFIED])
                ->get()->result_array();
            ////验货sku表新增记录 验货记录表新增记录
            foreach ($sku_data_tmp as $item) {
                $sku_insert_data = array(
                    'check_id' => $id,
                    'batch_no' => $batch_no + 1,
                    'product_line_id' => $item['product_line_id'],
                    'product_line_id_top' => $item['product_line_id_top'],
                    'demand_number' => $item['demand_number'],
                    'sku' => $item['sku'],
                    'unit_price' => $item['unit_price'],
                    'purchase_num' => $item['purchase_num'],
                    'warehouse_code' => $item['warehouse_code'],
                    'create_time' => $time,
                );
                $query->insert($this->table_supplier_check_sku, $sku_insert_data);
            }
            unset($sku_data_tmp);

            //验证异常
            $res = $this->check_is_abnormal($purchase_number, 'order_edit');
            //返回失败，或者系统生成验货单异常不能提交
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }

            //更新验货主表,检验次数/总次数各增加一次
            $query->set('check_times', 'check_times+1', FALSE);
            $query->set('supplier_check_times', 'supplier_check_times+1', FALSE);
            $query->set('status', CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM, FALSE);
            $query->set('modify_user_name', $info['user_name']);
            $query->where('id', $id);
            $query->update($this->table_supplier_check);

            //更新供应商表取字段check_times，增加一次
            $query->set('check_times', 'check_times+1', FALSE);
            $query->where('supplier_code', $supplier_code);
            $query->update($this->table_supplier);

            $record_insert_data = array(
                'check_id' => $id,
                'batch_no' => $batch_no + 1,
                'contact_person' => $params['contact_person'],
                'phone_number' => $params['phone_number'],
                'confirm_user_id' => $info['user_id'],
                'confirm_user_number' => $info['staff_code'],
                'confirm_user_name' => $info['user_name'],
                'confirm_remark' => $params['remark'],
                'confirm_time' => $time,
                'check_time' => $time,
                'check_expect_time' => $params['check_expect_time'],
                'inspector' => $order_info['inspector'],
                'inspector_number' => $order_info['inspector_number'],
            );
            $query->insert($this->table_supplier_check_record, $record_insert_data);

            //写入操作日志表
            $this->_insert_log([
                'id' => $id,
                'type' => '编辑',
                'content' => $params['remark']
            ]);

            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                $result = ['msg' => '操作失败', 'flag' => false];
            } else {
                //获取推送数据
                $push_data = $this->_get_push_inspection_data($id, 'order_edit');
                if (!$push_data['flag']) {
                    throw new Exception($push_data['msg']);
                }
                //获取推送url
                $url = getConfigItemByName('api_config', 'check_product', 'push_inspect_order');
                //推送到新产品系统
                $push_result = $this->_push_product_sys($push_data['data'], $url);
                if (!$push_result['flag']) {
                    throw new Exception($push_result['msg']);
                } else {
                    //推送成功，写入日志
                    operatorLogInsert(
                        [
                            'type' => 'INSPECT_ORDER_PUSH',
                            'content' => '编辑验货单推送产品系统',
                            'detail' => json_encode($push_data['data']),
                            'user' => getActiveUserName(),
                        ]);
                }
                $query->trans_commit();
                $result = ['msg' => '', 'flag' => true];
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $result = ['msg' => $e->getMessage(), 'flag' => false];
            //保存系统日志
            operatorLogInsert([
                'id' => $id,
                'type' => 'INSPECT_ORDER_PUSH',
                'content' => '重验提交失败',
                'detail' => $e->getMessage(),
                'user' => getActiveUserName(),
            ]);
        }
        return $result;
    }

    /**
     * 转合格申请
     * @param $id
     * @param array $params
     * @return array
     */
    public function qualify_for_apply($id, $params = array())
    {
        $query = $this->purchase_db;
        $query->trans_begin();
        try {
            //验证订单是否为'不合格待确认'状态，第一次验货产品系统验货结果为不合格时，即为'不合格待确认'状态
            //(转合格申请，只能申请一次，申请过一次后，系统不能再次申请)
            $order_info = $this->_get_order_info($id);
            $status = isset($order_info['status']) ? $order_info['status'] : 0;
            if (!in_array($status, [CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM])) {
                throw new Exception('‘不合格待确认’状态，才允许申请');
            }

            //获取验货结果为‘不合格’的sku数据
            $push_data = $this->_get_unqualified_sku_data($id, $params);
            if (empty($push_data)) {
                throw new Exception('没有可转合格申请的SKU');
            }

            //修改主表验货状态
            $query->update($this->table_supplier_check, ['status' => CHECK_ORDER_STATUS_QUALIFIED_APPLYING], ['id' => $id]);

            //修改sku表验货状态
            $query->where('id', $id);
            $query->where('batch_no', $push_data['batch']);
            $query->where_in('sku', array_column($push_data['data'], 'sku'));
            $query->update($this->table_supplier_check_sku, ['status' => CHECK_ORDER_STATUS_QUALIFIED_APPLYING]);

            //保存转合格申请，相关证明凭证
            $insert_data = array();
            foreach ($params['img_url'] as $item) {
                $insert_data[] = [
                    'record_id' => $id,
                    'batch_no' => $order_info['check_times'],
                    'file_type' => 2,
                    'url' => $item,
                ];
            }
            $query->insert_batch($this->table_check_upload, $insert_data);
            //写入操作日志表
            $this->_insert_log([
                'id' => $id,
                'type' => '转合格申请',
                'content' => $params['reason']
            ]);
            /*
                        //验货结果：1-验货通过（免检、合格、转IQC） ，2-验货不通过（不合格）
                        $judgment_result_tmp = 0;
                        $judgment_result = 1;
                        if (in_array($judgment_result, [CHECK_ORDER_RESULT_EXEMPTION, CHECK_ORDER_RESULT_QUALIFIED, CHECK_ORDER_RESULT_IQC])) {
                            $judgment_result_tmp = 1;
                        } elseif (CHECK_ORDER_RESULT_UNQUALIFIED == $judgment_result) {
                            $judgment_result_tmp = 2;
                        }
                        //根据备货单号更新验货结果
                        $query = $this->purchase_db->where_in('sku', array_column($push_data['data'], 'sku'))->update('pur_shipment_track_list', ["push_to_plan"=>0, "judgment_result" => $judgment_result_tmp]);
            */
            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                $result = ['msg' => '操作失败', 'flag' => false];
            } else {
                //获取推送url
                $url = getConfigItemByName('api_config', 'check_product', 'qualifies_apply');
                //推送新产品系统
                $push_result = $this->_push_product_sys($push_data, $url);
                if (!$push_result['flag']) {
                    throw new Exception($push_result['msg']);
                } else {
                    //推送成功，写入日志
                    operatorLogInsert(
                        [
                            'type' => 'INSPECT_ORDER_PUSH',
                            'content' => '转合格申请推送产品系统',
                            'detail' => json_encode($push_data),
                            'user' => getActiveUserName(),
                        ]);
                }
                $query->trans_commit();
                $result = ['msg' => '', 'flag' => true];
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $result = ['msg' => $e->getMessage(), 'flag' => false];
            //保存系统日志
            operatorLogInsert([
                'id' => $id,
                'type' => 'INSPECT_ORDER_PUSH',
                'content' => '转合格申请提交失败',
                'detail' => $e->getMessage(),
                'user' => getActiveUserName(),
            ]);
        }
        return $result;
    }

    /**
     * 获取验货结果为‘不合格’的sku数据
     * @param int $id 验货记录ID
     * @param array $params 提交转合格申请的相关数据
     * @return array
     */
    private function _get_unqualified_sku_data($id, $params)
    {
        $query = $this->purchase_db;
        $data = $query->select('a.id,a.check_code,a.purchase_number,a.check_times,c.id AS sku_id,c.sku,b.confirm_user_number,b.confirm_user_name,b.confirm_time,b.confirm_remark,c.status')
            ->from("{$this->table_supplier_check} a")
            ->join("{$this->table_supplier_check_record} b", "a.id=b.check_id AND b.batch_no=a.check_times")
            ->join("{$this->table_supplier_check_sku} c", "a.id=c.check_id AND c.batch_no=a.check_times")
            ->where(['a.id' => $id, 'c.judgment_result' => CHECK_ORDER_RESULT_UNQUALIFIED, 'c.is_special' => 0])
            ->get()->result_array();

        if (empty($data)) return array();
        //获取当前登录用户信息
        $info = getActiveUserInfo();
        //推送：申请编码、批次号、转合格申请提交人、提交备注、采购单号、需要转合格的sku、相关证明凭证、申请备注
        $push_data = array();
        $push_data['applyNo'] = $data[0]['check_code'];
        $push_data['batch'] = $data[0]['check_times'];
        $push_data['applicant'] = $info['staff_name'];
        $push_data['applicantCode'] = $info['staff_code'];
        foreach ($data as $key => $item) {
            $push_data['data'][$key]['po'] = $item['purchase_number'];
            $push_data['data'][$key]['sku'] = $item['sku'];
            $push_data['data'][$key]['proof'] = '["' . implode('","', $params['img_url']) . '"]';
            $push_data['data'][$key]['explain'] = $params['reason'];
        }
        return $push_data;
    }

    /**
     * 作废验货单
     * @param array $ids
     * @return array
     */
    public function make_order_invalid($ids = array())
    {
        $query = $this->purchase_db;
        $query->trans_begin();
        try {
            //验证订单是否符合作废条件
            $ids_tmp = array();//符合作废条件的数据
            $ids_main = [];
            $nonconforming_data = array();//不符合条件的数据
            $cancel_list = [];//不符合条件的数据
            foreach ($ids as $id) {
                $order_info = $this->_get_order_info($id, 'cancel');
                $status = isset($order_info['status']) ? $order_info['status'] : 0;
                $ids_main[] = $order_info['main_id'];
                $check_times = isset($order_info['check_times']) ? $order_info['check_times'] : 0;
                $is_abnormal = isset($order_info['is_abnormal']) ? $order_info['is_abnormal'] : 0;

                // 检验状态=待采购确认/待品控确认/免检待审核/免检驳回/品控验货中/不合格待确认/转合格申请中/装IQC验货/验货不合格，是否异常=是时，可以进行作废
                // 否则报错：申请编码XXX是已完结的验货数据，用户不能作废。
                $status_list = [
                    CHECK_ORDER_STATUS_WAITING_PURCHASER_CONFIRM,
                    CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM,
                    CHECK_ORDER_STATUS_WAITING_EXEMPTION_AUDIT,
                    CHECK_ORDER_STATUS_WAITING_EXEMPTION_AUDIT,
                    CHECK_ORDER_STATUS_EXEMPTION_REJECT,
                    CHECK_ORDER_STATUS_QUALITY_CHECKING,
                    CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM,
                    CHECK_ORDER_STATUS_QUALIFIED_APPLYING,
                    CHECK_ORDER_STATUS_IQC,
                    CHECK_ORDER_STATUS_UNQUALIFIED,
                ];
                if(in_array($status, $status_list) && $is_abnormal == 1){
                    if(!in_array($id, $ids_tmp))$ids_tmp[] = $id;
                }else{
                    $cancel_list[] = $order_info['check_code'];
                    continue;
                }

                //是否异常=是，且验货状态=待采购确认，检验次数=1，则允许作废
                /*
                if ($is_abnormal && CHECK_ORDER_STATUS_WAITING_PURCHASER_CONFIRM == $status && 1 == $check_times) {
                    if(!in_array($id, $ids_tmp))$ids_tmp[] = $id;
                } else {
                    $nonconforming_data[] = $order_info['check_code'];
                }
                */
            }

            if(count($cancel_list) > 0){
                $cancel_list = implode(",", $cancel_list);
                throw new Exception("申请编码: {$cancel_list}是已完结的验货数据，用户不能作废");
            }


            //提交全部id都不符条件
            if (empty($ids_main)) {
                throw new Exception('验货单[' . implode(',', $nonconforming_data) . ']不满足作废条件');
            }

            //操作人
            $modify_user_name = getActiveUserName();
            //更新是否作废
            $update_data = array(
                'status' => CHECK_ORDER_STATUS_INVALID,
                'modify_user_name' => $modify_user_name,
            );
            $query->where_in('id', $ids_main);
            $query->update($this->table_supplier_check, $update_data);

            //获取sku数据
            $sku_data = $query->select('a.purchase_number,b.id,b.sku')->from("{$this->table_supplier_check} a")
                ->join("{$this->table_supplier_check_sku} b", 'a.id=b.check_id AND a.check_times=b.batch_no')
                ->where_in('b.id', $ids_tmp)
                ->get()->result_array();
            foreach ($sku_data as $item){
                //更新sku表验货状态
                $query->update($this->table_supplier_check_sku, $update_data,['id'=>$item['id']]);
                //更新采购单明细表和采购单请款单明细表验货状态
                $this->_update_order_status($item['purchase_number'], $item['sku'], ['status' => 0]);
            }
            //写入操作日志表
            foreach ($ids_main as $id) {
                $this->_insert_log([
                    'id' => $id,
                    'type' => '作废',
                    'content' => '作废验货单'
                ]);
            }

            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                $result = ['msg' => '操作失败', 'flag' => false];
            } else {
                $query->trans_commit();
                $result = ['msg' => '', 'flag' => true];
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $result = ['msg' => $e->getMessage(), 'flag' => false];
        }
        return $result;
    }

    /**
     * 检测验货单是否异常，如有异常则更新表信息
     * 符合条件将推送到新产品系统
     * @param string|array $purchase_number
     * @param string $type
     * @return array
     */
    public function check_is_abnormal($purchase_number, $type = 'system')
    {
        if (empty($purchase_number)) return ['status' => false, 'msg' => '采购单号不能为空'];
        if (!is_array($purchase_number)) $purchase_number = [$purchase_number];

        //取消，报损，采购数量发生变化时，根据采购单号获取验货单数据（只获取未产生验货结果的sku数据）
        $select_field = 'a.id,a.check_code,a.purchase_number,a.order_type,a.purchase_type_id,a.is_push,a.check_times,
        a.is_system_created,b.product_line_id_top,b.purchase_num,b.unit_price,b.sku,b.invalid_num,b.is_abnormal,
        b.judgment_result,b.batch_no,b.check_num,b.check_level,b.inspect_grade';
        $check_order_data = $this->purchase_db->select($select_field)
            ->from("{$this->table_supplier_check} a")
            ->join("{$this->table_supplier_check_sku} b", "a.id=b.check_id")
            ->where_in('a.purchase_number', $purchase_number)
            ->where('b.judgment_result', '0')
            ->get()->result_array();

        if (empty($check_order_data)) {
            if ('system' == $type) {
                return ['flag' => true, 'msg' => '采购单未生成验货单或未获取到符合条件的数据'];
            } else {
                return ['flag' => false, 'msg' => '未获取到验货单数据'];
            }
        }

        /**1、获取有效和无效采购数量**/
        $purchase_data_tmp = array();
        foreach ($check_order_data as $item) {
            $purchase_data_tmp[] = array(
                'purchase_number' => $item['purchase_number'],
                'sku' => $item['sku'],
                'purchase_num' => $item['purchase_num']
            );
        }
        $purchase_qty_data = $this->_get_purchase_qty_data($purchase_data_tmp);

        /**获取缺货sku数据**/
        $sku_arr = array_unique(array_column($purchase_data_tmp, 'sku'));
        $stock_owes_data = $this->_get_stock_owes($sku_arr);
        unset($purchase_data_tmp);

        $result_data = array();      //结果数据
        foreach ($check_order_data as &$item) {
            //采购单号+下划线+sku作为唯一下标
            $idx = $item['purchase_number'] . '_' . $item['sku'];
            //结果数据key
            $key = $item['id'] . '-' . $item['batch_no'];
            $pur_valid_num = $purchase_qty_data[$idx]['pur_valid_num'];     //有效采购数量
            $item['pur_valid_num'] = $pur_valid_num;
            $pur_invalid_num = $purchase_qty_data[$idx]['pur_invalid_num']; //无效采购数量
            $item['pur_invalid_num'] = $pur_invalid_num;

            $result_data[$key][$item['sku']]['pur_invalid_num'] = $pur_invalid_num;             //无效采购数量
            $result_data[$key][$item['sku']]['pur_valid_num'] = $pur_valid_num;                 //有效采购数量
            $result_data[$key][$item['sku']]['is_push'] = $item['is_push'];                     //是否推送到产品系统
            $result_data[$key][$item['sku']]['purchase_number'] = $item['purchase_number'];     //采购单号
            $result_data[$key][$item['sku']]['check_code'] = $item['check_code'];               //验货编码
            $result_data[$key][$item['sku']]['purchase_type_id'] = $item['purchase_type_id'];   //业务线
            $result_data[$key][$item['sku']]['order_type'] = $item['order_type'];               //验货类型
            $result_data[$key][$item['sku']]['unit_price'] = $item['unit_price'];               //单价
            $result_data[$key][$item['sku']]['judgment_result'] = $item['judgment_result'];     //验货结果
            $result_data[$key][$item['sku']]['check_times'] = $item['check_times'];             //验货次数
            $result_data[$key][$item['sku']]['is_system_created'] = $item['is_system_created']; //是否系统自动创建
            $result_data[$key][$item['sku']]['check_num_old'] = $item['check_num'];             //旧的抽检数量
            $result_data[$key][$item['sku']]['check_level_old'] = $item['check_level'];         //旧的抽检等级
            $result_data[$key][$item['sku']]['inspect_grade_old'] = $item['inspect_grade'];     //旧的验货等级
            $result_data[$key][$item['sku']]['owes_qty'] = isset($stock_owes_data[$item['sku']]) ? $stock_owes_data[$item['sku']] : 0;//缺货数量
        }

        // 需要重新修改  叶凡立  20210611
        /**2、验证采购单价是否符合验货规则
         * 统计同一业务线下同一产品线的采购金额总和（验货单维度）
         */
        $total_price_data = array();//验货单采购金额总数据
        $current_time = time();     //当前时间
        foreach ($check_order_data as $val) {
            //工厂发运的不用再次验证，手工创建验货单无需验证规则
            if (3 == $val['order_type'] OR !$val['is_system_created']) {
                continue;
            }

            //获取验货规则
            $rule_info = $this->_get_check_rule($val['purchase_type_id'], $val['product_line_id_top']);

            //未获取到生效规则，或者规则未生效，或者sku采购单价小于验货规则采购单价，则验货异常
            if (empty($rule_info)
                OR ($current_time - strtotime($rule_info['effective_time']) < 0)
                OR ((float)$val['unit_price'] < (float)$rule_info['purchase_unit_price'])) {
                //验货单异常(sku维度)
                $result_data[$val['id'] . '-' . $val['batch_no']][$val['sku']]['is_abnormal'] = 1;
                continue;
            }

            //统计同一业务线下同一产品线的采购金额总和（验货单维度）
            $total_price = (float)bcmul($val['unit_price'], $val['pur_valid_num'], 2);

            $idx = $val['id'] . '-' . $val['batch_no'] . '_' . $val['purchase_type_id'] . '_' . $val['product_line_id_top'];
            if (!isset($total_price_data[$idx]['total_price'])) {
                $total_price_data[$idx]['total_price'] = $total_price;
                $total_price_data[$idx]['sku'][] = $val['sku'];
            } else {
                $total_price_data[$idx]['total_price'] += $total_price;
                $total_price_data[$idx]['sku'][] = $val['sku'];
            }
        }
        unset($check_order_data);

        /**3、验证采购金额总和是否符合验货规则**/
        foreach ($total_price_data as $key => $item) {
            //验货单主表ID，业务线ID，产品线ID
            list($idx, $purchase_type_id, $product_line_id) = explode('_', $key);
            //获取验货规则
            $rule_info = $this->_get_check_rule($purchase_type_id, $product_line_id);
            if (empty($rule_info)) {
                //验货单异常
                $result_data[$idx][$item['sku']]['is_abnormal'] = 1;
                continue;
            }
            //总金额小于验货规则总金额，则为验货单异常
            if ((float)$item['total_price'] < (float)$rule_info['purchase_amount']) {
                foreach ($item['sku'] as $sku) {
                    $result_data[$idx][$sku]['is_abnormal'] = 1;
                }
            }
        }
        unset($total_price_data);

        $master_update_data = array();  //更新验货主表的数据
        $subtable_update_data = array();//更新验货sku表的数据
        $push_data_tmp = array();       //推送到新产品系统的数据

        $abnormal_sku = array();        //有效采购数量=0的sku
        $is_all_abnormal = 1;           //假设验货单下所有sku有效采购数量=0(该变量仅用于确认采购单的判断)
        $is_high_level_abnormal = 0;    //发生此异常值为1时不允许提交(该变量仅用于确认采购单的判断)

        //循环处理验货单
        foreach ($result_data as $key => $item) {
            list($check_id, $batch_no) = explode('-', $key);

            $is_master_abnormal = 0;    //假设验货单不为异常
            $is_all_abnormal_tmp = 1;   //假设验货单下所有sku有效采购数量=0
            //验证是否所有sku有效采购数量=0
            foreach ($item as $sku => $value) {
                if ($value['pur_valid_num'] > 0) {
                    $is_all_abnormal_tmp = 0;
                    $is_all_abnormal = 0;
                } else {
                    $abnormal_sku[$check_id][$sku] = $sku; //有效采购数量=0的sku
                }
            }

            //循环处理验货单的所有sku
            foreach ($item as $sku => $value) {
                //抽检等级
                $check_level = $this->_get_check_level($value['purchase_type_id'], $value['order_type'], $value['unit_price']);
                //抽检数量
                $check_qty = $this->_get_check_qty($check_level, $value['pur_valid_num']);
                //获取验货等级
                $inspect_grade = $this->_get_inspect_grade($check_qty, $value['order_type'], $value['check_times'], $value['purchase_type_id']);

                //系统自动创建单不满足验货条件异常，或（系统自动创建单、手工创建单）所有sku有效采购数量=0异常
                if (!empty($value['is_abnormal']) OR $is_all_abnormal_tmp) {
                    $abnormal_type = 1;
                    $is_high_level_abnormal = 1;//发生此异常不允许提交，或推送到产品系统时要把整个验货单sku标识为异常
                } elseif (!empty($abnormal_sku[$check_id][$sku])) {
                    //（系统自动创建单、手工创建单）有效采购数量等于0异常，推送到产品系统时按照实际sku异常标识即可
                    $abnormal_type = 2;
                } else {
                    $abnormal_type = 0;
                }
                //只要有一个sku为异常，验货单则为异常
                if($abnormal_type){
                    $is_master_abnormal = 1;
                }

                //异常要推送；
                //抽检等级，抽检数量，获取验货等级发生变化时也要推送
                if ($abnormal_type
                    OR ($check_level != $value['check_level_old'] OR $check_qty != $value['check_num_old'] OR $check_level != $value['check_level_old'])
                ) {
                    //异常并且已推送新产品系统，并且未生成验货结果，则把发生采购数量变更的验货单数据，推送给新产品系统
                    if (1 == $value['is_push'] && 0 == $value['judgment_result']) {
                        $is_abnormal = $abnormal_type ? 1 : 0;
                        //申请编码，PO，sku，有效采购数量，抽检等级，抽检数量，验货等级，验货批次，是否异常
                        $push_data_tmp[$key][] = array(
                            'purchase_number' => $value['purchase_number'],
                            'check_code' => $value['check_code'],
                            'sku' => $sku,
                            'purchase_num' => $value['pur_valid_num'],
                            'check_level' => $check_level,
                            'check_qty' => $check_qty,
                            'inspect_grade' => $inspect_grade,
                            'batch_no' => $batch_no,
                            'is_abnormal' => $is_abnormal,
                            'is_abnormal_sku' => $is_abnormal,
                            'is_all_abnormal' => 1 == $abnormal_type ? 1 : 0,//值为1时，产品系统将要把整个验货单sku都标识为异常
                        );
                    }
                }

                //更新验货sku表，无效数量,是否异常
                $subtable_update_data[] = array(
                    'where' => array(
                        'check_id' => $check_id,
                        'batch_no' => $batch_no,
                        'sku' => $sku
                    ),
                    'set' => array(
                        'check_level' => $check_level,
                        'check_num' => $check_qty,
                        'inspect_grade' => $inspect_grade,
                        'invalid_num' => $value['pur_invalid_num'],
                        'owes_qty' => $value['owes_qty'],
                        'is_abnormal' => $abnormal_type ? 1 : 0,
                    )
                );
            }
            //设置主表验货单是否为异常
            $master_update_data[$check_id] = array('id' => $check_id, 'is_abnormal' => $is_master_abnormal ? 1 : 0);
        }

        $this->purchase_db->trans_begin();
        try {
            if (!empty($master_update_data)) {
                //更新验货主表，是否异常
                $this->purchase_db->update_batch($this->table_supplier_check, $master_update_data, 'id');
            }

            //更新验货sku表，无效数量,是否异常
            foreach ($subtable_update_data as $item) {
                $this->purchase_db->update($this->table_supplier_check_sku, $item['set'], $item['where']);
            }

            if ($this->purchase_db->trans_status() === FALSE) {
                throw new Exception('更新异常，事务回滚');
            } else {
                //推送新产品系统(重验发生异常或相关数量变动时，不用单独推送，数据和重验数据一起推送)
                if (!empty($push_data_tmp) && 'order_edit' != $type) {
                    $push_data = array();
                    foreach ($push_data_tmp as $key => $item) {
                        foreach ($item as $k => $val) {
                            $push_data[$key]['applyNo'] = $val['check_code'];
                            $push_data[$key]['po'] = $val['purchase_number'];
                            $push_data[$key]['batch'] = $val['batch_no'];
                            $push_data[$key]['isAbnormal'] = $val['is_abnormal'];
                            $push_data[$key]['isAllAbnormal'] = $val['is_all_abnormal'];
                            $push_data[$key]['data'][$k]['sku'] = $val['sku'];
                            $push_data[$key]['data'][$k]['realPurchaseNum'] = $val['purchase_num'];
                            $push_data[$key]['data'][$k]['spotGrade'] = $val['check_level'];
                            $push_data[$key]['data'][$k]['inspectNum'] = $val['check_qty'];
                            $push_data[$key]['data'][$k]['inspectGrade'] = $val['inspect_grade'];
                            $push_data[$key]['data'][$k]['isAbnormal'] = $val['is_abnormal_sku'];
                        }
                    }
                    unset($push_data_tmp);
                    //获取推送url
                    $url = getConfigItemByName('api_config', 'check_product', 'inspect_order_changed');
                    $push_result = $this->_push_product_sys(['item' => array_values($push_data)], $url);
                    if (!$push_result['flag']) {
                        throw new Exception($push_result['msg']);
                    } else {
                        //推送成功，写入日志
                        operatorLogInsert(
                            [
                                'type' => 'INSPECT_ORDER_PUSH',
                                'content' => '验货单异常或有效采购数量变化推送产品系统',
                                'detail' => json_encode($push_data),
                                'user' => getActiveUserName(),
                            ]);
                    }
                }
                $this->purchase_db->trans_commit();
                //$abnormal_data该变量仅用于返回给确认采购单作判断（计划任务检测异常和重验无效）
                $abnormal_data = array(
                    'is_high_level' => $is_high_level_abnormal,
                    'is_all_abnormal' => $is_all_abnormal,
                    'sku' => $abnormal_sku
                );
                $result = ['msg' => '处理成功', 'flag' => true, 'abnormal_data' => $abnormal_data];
            }
        } catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            $result = ['msg' => $e->getMessage(), 'flag' => false, 'abnormal_data' => array()];
            //保存系统日志
            operatorLogInsert([
                'type' => 'INSPECT_ORDER_PUSH',
                'content' => '检查是否验货单异常失败',
                'detail' => '[' . implode(',', $purchase_number) . ']' . $e->getMessage(),
                'user' => getActiveUserName(),
            ]);
        }
        return $result;
    }

    /**
     * 获取操作日志
     * @param $id
     * @return array
     */
    public function get_log($id)
    {
        $query = $this->purchase_db;
        $query->select('record_id AS check_id,create_time,create_user,operation_type,operation_content');
        $query->from("{$this->table_check_log}");
        $query->where('record_id', $id);
        $result = $query->get()->result_array();

        foreach ($result as &$item) {
            $item['attachment'] = array();//附件
            $item['is_show_report'] = 0;//是否显示验货报告

            //处理转合格申请的附件
            if ('转合格申请' == $item['operation_type']) {
                //获取转合格申请的附件
                $attachment = $query->select('url')->where(['record_id' => $id, 'file_type' => 2])->get($this->table_check_upload)->result_array();
                $item['attachment'] = !empty($attachment) ? array_column($attachment, 'url') : array();
            } elseif ('验货结果' == $item['operation_type']) {
                $item['is_show_report'] = 1;
            }
        }

        //表头字段
        $key_table = array(
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_type'),
            $this->lang->myline('operation_content'),
            $this->lang->myline('other_file'),
        );
        $return_data = [
            'key' => $key_table,
            'values' => $result,
        ];
        return $return_data;
    }

    /**
     * 获取验货报告
     * @param $id
     * @return array
     */
    public function get_report($id)
    {
        //获取主要信息
        $query = $this->purchase_db;
        $query->select('purchase_number,check_code,supplier_code,supplier_name,complete_address,buyer_name,order_type,check_times');
        $query->from("{$this->table_supplier_check}");
        $query->where('id', $id);
        $master_data = $query->get()->row_array();

        //获取验货提交记录
        $query->select('check_id,batch_no,contact_person,phone_number,confirm_user_name,confirm_time,inspector,check_time');
        $query->from("{$this->table_supplier_check_record}");
        $query->where('check_id', $id);
        $record_data = $query->get()->result_array();

        // 33109 box_all 总箱数, box_psc 箱内数, box_size 外箱尺寸(cm), box_weight 外箱毛重(Kg), box_tail_size 尾箱尺寸(cm),
        // box_tail_weight 尾箱毛重(Kg), box_tail_psc 尾数, box_lock_number 封柜锁编号
        //获取验货sku
        $query->select('id,batch_no,sku,purchase_num,invalid_num,check_num,owes_qty,unit_price,defective_qty,
        received_qty,judgment_result,defective_type,defective_reason,improvement_measure,responsible_department,
        box_all, box_psc, box_size,box_weight,box_tail_size,box_tail_weight,box_tail_psc,box_lock_number,
        responsible_person,inspector_remark');
        $query->from("{$this->table_supplier_check_sku}");
        $query->where('check_id', $id);
        $sku_data = $query->get()->result_array();
        //根据采购单号和sku获取产品名称和图片
        $product_info = $this->_get_product_info($master_data['purchase_number'], array_column($sku_data, 'sku'));
        //获取验货报告的附件
        $attachment_data_tmp = $query->select('b.record_id AS sku_id,b.batch_no,b.url')
            ->from("{$this->table_supplier_check_sku} a")
            ->join("{$this->table_check_upload} b", "a.id=b.record_id AND a.batch_no=b.batch_no")
            ->where(['a.check_id' => $id, 'b.file_type' => 1])->get()->result_array();
        $attachment_data = array();
        foreach ($attachment_data_tmp as $item) {
            $idx = $item['sku_id'] . '_' . $item['batch_no'];
            $attachment_data[$idx][] = $item['url'];
        }
        unset($attachment_data_tmp);

        $sku_data_tmp = array();
        foreach ($sku_data as &$item) {
            $item['purchase_number'] = $master_data['purchase_number'];
            $item['defective_rate'] = (int)$item['check_num'] ? bcmul(bcdiv($item['defective_qty'], $item['check_num'], 2), 100) . '%' : '0.00%';//不良率=不良数/抽检数
            $item['judgment_result_cn'] = !empty($item['judgment_result']) ? getCheckOrderResult($item['judgment_result']) : '';
            $item['product_name'] = isset($product_info[$item['sku']]['product_name']) ? $product_info[$item['sku']]['product_name'] : '';
            $item['product_img_url'] = isset($product_info[$item['sku']]['product_img_url']) ? erp_sku_img_sku($product_info[$item['sku']]['product_img_url']) : '';
            $item['product_img_url_thumbnails'] = isset($product_info[$item['sku']]['product_thumb_url']) ? $product_info[$item['sku']]['product_thumb_url'] : erp_sku_img_sku($item['product_img_url']);
            $item['attachment'] = isset($attachment_data[$item['id'] . '_' . $item['batch_no']]) ? $attachment_data[$item['id'] . '_' . $item['batch_no']] : array();

            $sku_data_tmp[$item['batch_no']][] = $item;
        }
        unset($sku_data);
        unset($attachment_data);

        //结果数据
        $result = array();
        foreach ($record_data as $key => $item) {
            $result[$key]['master_data']['check_code'] = $master_data['check_code'];
            $result[$key]['master_data']['supplier_code'] = $master_data['supplier_code'];
            $result[$key]['master_data']['confirm_user_name'] = $item['confirm_user_name'];
            $result[$key]['master_data']['confirm_time'] = '0000-00-00 00:00:00' == $item['confirm_time'] ? '' : date('Y-m-d', strtotime($item['confirm_time']));
            $result[$key]['master_data']['buyer_name'] = $master_data['buyer_name'];
            $result[$key]['master_data']['order_type_cn'] = !empty($master_data['order_type']) ? getCheckOrderType($master_data['order_type']) : '';
            $result[$key]['master_data']['supplier_name'] = $master_data['supplier_name'];
            $result[$key]['master_data']['apply_department'] = '采购部';
            $result[$key]['master_data']['inspector'] = $item['inspector'];
            $result[$key]['master_data']['check_time'] = '0000-00-00' == $item['check_time'] ? '' : $item['check_time'];
            $result[$key]['master_data']['address'] = $master_data['complete_address'] . ' ' . $item['contact_person'] . ' ' . $item['phone_number'];
            $result[$key]['suk_data'] = $sku_data_tmp[$item['batch_no']];
        }

        //表头字段
        $key_table = array(
            $this->lang->myline('sequence'),
            $this->lang->myline('pur_number'),
            $this->lang->myline('sku'),
            $this->lang->myline('product_img'),
            $this->lang->myline('product_name'),
            $this->lang->myline('pur_valid_qty'),
            $this->lang->myline('check_qty'),
            $this->lang->myline('owes_qty'),
            $this->lang->myline('purchase_unit_price'),
            $this->lang->myline('defective_qty'),
            $this->lang->myline('defective_rate'),
            $this->lang->myline('received_qty'),
            $this->lang->myline('judgment_result'),
            // 33109
            "总箱数",
            "箱内数",
            "外箱尺寸(cm)",
            "外箱毛重(Kg)",
            "尾箱尺寸(cm)",
            "尾箱毛重(Kg)",
            "尾数",
            "封柜锁编号",
            $this->lang->myline('defective_type'),
            $this->lang->myline('defective_reason'),
            $this->lang->myline('improvement_measure'),
            $this->lang->myline('responsible_department'),
            $this->lang->myline('responsible_person'),
            $this->lang->myline('inspector_remark'),
            $this->lang->myline('accessory'),
        );
        $return_data = [
            'key' => $key_table,
            'values' => $result,
        ];
        return $return_data;
    }

    /**
     * 根据推送数据类型更改数据
     * @param int $type 1、免检审核， 2、排期分配(重新分配)，3、转合格申请审核，4、特批出货，5、验货结果
     * @param $data
     * @return array
     */
    public function update_order($type, $data)
    {
        $log_data = array();

        $query = $this->purchase_db;
        $query->trans_begin();
        $demand_list = [];
        try {
            //获取验货单po+sku数据
            $purchase_data = $query->select('
                    a.id,
                    a.purchase_number,
                    b.sku,
                    c.check_expect_time,
                    b.demand_number,
                    a.apply_user_id,
                    a.apply_user_name
                ')
                ->from("{$this->table_supplier_check} a")
                ->join("{$this->table_supplier_check_sku} b", 'a.id=b.check_id AND b.batch_no=a.check_times')
                ->join("{$this->table_supplier_check_record} c", 'a.id=c.check_id AND c.batch_no=a.check_times')
                ->where(['a.check_code' => $data['check_code']])
                ->get()->result_array();
            if (empty($purchase_data)) {
                throw new Exception('验货单[' . $data['check_code'] . ']不存在');
            }
            $demand_number = array_unique(array_column($purchase_data, "demand_number"));
            $check_id = $purchase_data[0]['id'];                        //验货数据id
            $po = $purchase_data[0]['purchase_number'];                 //采购单号
            $order_sku = array_column($purchase_data, 'sku');           //sku
            $check_expect_time = $purchase_data[0]['check_expect_time'];//期望验货日期

            // 37871
            $this->notice_apply_user($data['check_code'], $po, $data['status'], $purchase_data[0]['apply_user_id'], $purchase_data[0]['apply_user_name']);

            switch ($type) {
                case 1:
                    /**处理产品系统免检审核操作**/
                    //日志数据
                    $log_data['user_id'] = $data['approval_user_id'];
                    $log_data['user'] = $data['approval_user_name'];
                    $log_data['type'] = '免检审核';
                    $log_data['content'] = $data['approval_remark'];

                    /**更新验货主表**/
                    $set = array(
                        'status' => $data['status'],
                        'judgment_result' => $data['judgment_result'],
                        'approval_user_id' => $data['approval_user_id'],
                        'approval_user_name' => $data['approval_user_name'],
                        'approval_time' => $data['approval_time'],
                        'approval_remark' => $data['approval_remark'],
                        'assigner_code' => $data['assigner_code'],
                        'assigner_name' => $data['assigner_name']
                    );
                    $query->update($this->table_supplier_check, $set, ['check_code' => $data['check_code']]);

                    /**更新验货sku表**/
                    $set = array(
                        'status' => $data['status'],
                        'judgment_result' => $data['judgment_result'],
                    );
                    $query->update($this->table_supplier_check_sku, $set, ['check_id' => $check_id, 'batch_no' => $data['batch_no']]);

                    /**更新采购单明细表和采购单请款单明细表验货状态**/
                    $this->_update_order_status($po, $order_sku, ['status' => $data['status']]);
                    /**更新发运跟踪表验货结果**/
                    $this->_update_shipment_track($po, $order_sku, $data['judgment_result'], $check_expect_time);

                    //写入操作日志表
                    $this->_insert_log([
                        'id' => $check_id,
                        'type' => $log_data['type'],
                        'content' => $log_data['content'],
                        'user_id' => $log_data['user_id'],
                        'user' => $log_data['user']
                    ]);
                    break;
                case 2:
                    /**处理产品系统排期分配任务，或者重新分配任务操作**/
                    //日志数据
                    $log_data['user_id'] = $data['operator_id'];
                    $log_data['user'] = $data['operator'];
                    $log_data['type'] = '品控确认';
                    $log_data['content'] = '验货员：' . $data['inspector'] . '，计划验货时间：' . $data['schedule_inspect_time'];

                    /**更新验货主表**/
                    $set = array(
                        'status' => $data['status'],
                        'assigner_code' => $data['operator_code'],
                        'assigner_name' => str_replace($data['operator_code'], '', $data['operator'])
                    );
                    $query->update($this->table_supplier_check, $set, ['check_code' => $data['check_code']]);

                    /**更新验货sku表**/
                    $set = array('status' => $data['status']);
                    $query->update($this->table_supplier_check_sku, $set, ['check_id' => $check_id, 'batch_no' => $data['batch_no']]);

                    /**更新验货记录表**/
                    $set = array('inspector_number' => $data['inspector_number'], 'inspector' => $data['inspector']);
                    $query->update($this->table_supplier_check_record, $set, ['check_id' => $check_id, 'batch_no' => $data['batch_no']]);

                    /**更新采购单明细表和采购单请款单明细表验货状态**/
                    $this->_update_order_status($po, $order_sku, ['status' => $data['status']]);

                    //写入操作日志表
                    $this->_insert_log([
                        'id' => $check_id,
                        'type' => $log_data['type'],
                        'content' => $log_data['content'],
                        'user_id' => $log_data['user_id'],
                        'user' => $log_data['user'],
                    ]);
                    break;
                case 3:
                    /**处理产品系统转合格操作**/
                    //日志数据
                    $log_data['user_id'] = $data['approver_id'];
                    $log_data['user'] = $data['approver'];
                    $log_data['type'] = '转合格申请审批';
                    $log_data['content'] = CHECK_ORDER_RESULT_QUALIFIED == $data['judgment_result'] ? '同意合格' : '不同意合格';

                    /**更新验货主表**/
                    $set = array(
                        'status' => $data['status'],
                        'judgment_result' => $data['judgment_result'],
                        'approval_user_id' => $data['approver_id'],
                        'approval_user_name' => $data['approver'],
                        'approval_time' => $data['approval_time'],
                        'approval_remark' => $data['approval_remark'],
                        'assigner_code' => $data['assigner_code'],
                        'assigner_name' => $data['assigner_name']
                    );
                    $query->update($this->table_supplier_check, $set, ['check_code' => $data['check_code']]);

                    foreach ($data['items'] as $item) {
                        $set = array(
                            'status' => $item['status'],
                            'judgment_result' => $item['judgment_result'],
                            'approval_user_id' => $data['approver_id'],
                            'approval_user_name' => $data['approver'],
                            'approval_time' => $data['approval_time'],
                            'approval_remark' => $item['approval_remark'],
                        );
                        /**更新验货sku表**/
                        $query->update($this->table_supplier_check_sku, $set, ['check_id' => $check_id, 'batch_no' => $data['batch_no'], 'sku' => $item['sku']]);
                        /**更新采购单明细表和采购单请款单明细表验货状态**/
                        $this->_update_order_status($po, $item['sku'], ['status' => $item['status']]);
                        /**更新发运跟踪表验货结果**/
                        $this->_update_shipment_track($po, $item['sku'], $item['judgment_result'], $check_expect_time);
                    }

                    //写入操作日志表
                    $this->_insert_log([
                        'id' => $check_id,
                        'type' => $log_data['type'],
                        'content' => $log_data['content'],
                        'user_id' => $log_data['user_id'],
                        'user' => $log_data['user'],
                    ]);
                    break;
                case 4:
                    /**处理产品系统特批出货操作**/
                    //日志数据
                    $log_data['user_id'] = $data['approval_user_id'];
                    $log_data['user'] = $data['approval_user_name'];
                    $log_data['type'] = '特批出货';
                    $log_data['content'] = $data['approval_remark'];

                    /**更新验货主表**/
                    $set = array(
                        'is_special' => $data['is_special'],
                        'approval_user_id' => $data['approval_user_id'],
                        'approval_user_name' => $data['approval_user_name'],
                        'approval_time' => $data['approval_time'],
                        'approval_remark' => $data['approval_remark'],
                    );
                    $query->update($this->table_supplier_check, $set, ['check_code' => $data['check_code']]);

                    /**更新验货sku表**/
                    $query->update($this->table_supplier_check_sku, $set, ['check_id' => $check_id, 'batch_no' => $data['batch_no'], 'sku' => $data['sku']]);

                    /**更新采购单明细表和采购单请款单明细表验货状态**/
                    $this->_update_order_status($po, $order_sku, ['is_special' => 1]);

                    //保存附件
                    $record = $query->select('b.id')
                        ->from("{$this->table_supplier_check} a")
                        ->join("{$this->table_supplier_check_sku} b", 'a.id=b.check_id')
                        ->where(['a.check_code' => $data['check_code'], 'b.batch_no' => $data['batch_no'], 'b.sku' => $data['sku']])
                        ->get()->row_array();
                    if(empty($record)){
                        throw new Exception('获取验货SKU信息失败');
                    }

                    $insert_data = array();
                    foreach ($data['attachment'] as $item) {
                        $insert_data[] = [
                            'record_id' => $record['id'],
                            'batch_no' => $data['batch_no'],
                            'file_type' => 3,
                            'url' => $item,
                        ];
                    }
                    $query->insert_batch($this->table_check_upload, $insert_data);
                    //写入操作日志表
                    $this->_insert_log([
                        'id' => $check_id,
                        'type' => $log_data['type'],
                        'content' => $log_data['content'],
                        'user_id' => $log_data['user_id'],
                        'user' => $log_data['user'],
                    ]);
                    break;
                case 5:
                    /**处理产品系统推送验货结果操作**/
                    //日志数据
                    $log_data['user_id'] = $data['approval_user_id'];
                    $log_data['user'] = $data['approval_user_name'];
                    $log_data['type'] = '验货结果';
                    $log_data['content'] = $data['approval_remark'];
                    //根据验货编码获取主表数据ID
                    $record = $query->select('id,purchase_number')->where('check_code', $data['check_code'])->get($this->table_supplier_check)->row_array();
                    //更新主表
                    $query->set('status', $data['status']);
                    $query->set('judgment_result', $data['judgment_result']);
                    $query->set('approval_user_id', $data['approval_user_id']);
                    $query->set('approval_user_name', $data['approval_user_name']);
                    $query->set('approval_remark', $data['approval_remark']);
                    $query->set('approval_time', $data['approval_time']);
                    $query->where('check_code', $data['check_code']);
                    $query->update($this->table_supplier_check);
                    //更新记录表
                    $query->set('inspector', $data['approval_user_name']);
                    $query->set('inspector_number', $data['inspector_code']);
                    $query->set('check_time', $data['approval_time']);
                    $query->where(['check_id' => $record['id'], 'batch_no' => $data['batch_no']]);
                    $query->update($this->table_supplier_check_record);
                    //更新sku表
                    foreach ($data['sku'] as $item) {
                        $query->set('status', $item['status']);
                        $query->set('judgment_result', $item['judgment_result']);
                        $query->set('received_qty', $item['received_qty']);
                        $query->set('inspector_remark', $item['inspector_remark']);
                        $query->set('defective_type', $item['defective_type']);
                        $query->set('defective_qty', $item['defective_qty']);
                        $query->set('defective_reason', $item['defective_reason']);
                        $query->set('improvement_measure', $item['improvement_measure']);
                        $query->set('responsible_person', $item['responsible_person']);
                        $query->set('responsible_department', $item['responsible_department']);

                        // 33109
                        $query->set('box_all', $item['box_all']); // 总箱数
                        $query->set('box_psc', $item['box_psc']); // 箱内数
                        $query->set('box_size', $item['box_size']); // 外箱尺寸(cm)
                        $query->set('box_weight', $item['box_weight']); // 外箱毛重(Kg)
                        $query->set('box_tail_size', $item['box_tail_size']); // 尾箱尺寸(cm)
                        $query->set('box_tail_weight', $item['box_tail_weight']); // 尾箱毛重(Kg)
                        $query->set('box_tail_psc', $item['box_tail_psc']); // 尾数
                        $query->set('box_lock_number', $item['box_lock_number']); // 封柜锁编号

                        $query->where(['check_id' => $record['id'], 'batch_no' => $data['batch_no'], 'sku' => $item['sku']]);
                        $query->update($this->table_supplier_check_sku);

                        /**更新采购单明细表和采购单请款单明细表验货状态**/
                        $this->_update_order_status($record['purchase_number'], $item['sku'], ['status' => $item['status']]);
                        /**更新发运跟踪表验货结果**/
                        $this->_update_shipment_track($record['purchase_number'], $item['sku'], $data['judgment_result'], $check_expect_time);
                    }
                    //保存验货报告附件
                    $sku_arr = array_column($data['sku'], 'sku');
                    $record = $query->select('id,sku')
                        ->where(['check_id' => $record['id'], 'batch_no' => $data['batch_no']])
                        ->where_in('sku', $sku_arr)
                        ->get($this->table_supplier_check_sku)->result_array();
                    $sku_id_arr = array_column($record, 'id', 'sku');
                    $insert_data = array();
                    foreach ($data['attachment'] as $sku => $item) {
                        if (!isset($sku_id_arr[$sku])) continue;
                        foreach ($item as $url) {
                            $insert_data[] = [
                                'record_id' => $sku_id_arr[$sku],
                                'batch_no' => $data['batch_no'],
                                'file_type' => 1,
                                'url' => $url,
                            ];
                        }
                    }
                    if (!empty($data['attachment']) && empty($insert_data)) {
                        throw new Exception('获取SKU信息失败');
                    }
                    if(!empty($insert_data)){
                        $query->insert_batch($this->table_check_upload, $insert_data);
                    }

                    //写入操作日志表
                    $this->_insert_log([
                        'id' => $check_id,
                        'type' => $log_data['type'],
                        'content' => $log_data['content'],
                        'user_id' => $log_data['user_id'],
                        'user' => $log_data['user'],
                    ]);
                    break;
            }
            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                $result = ['msg' => '操作失败', 'flag' => false];
            } else {
                $query->trans_commit();
                $result = ['msg' => '', 'flag' => true];
                if(count($demand_number) > 0){
                    $demand = $this->purchase_db->from("purchase_suggest")
                        ->select("demand_number")
                        ->where(["shipment_type" => 1])
                        ->where_in("demand_number", $demand_number)
                        ->get()->result_array();
                    if($demand && count($demand) > 0)$demand_list = array_unique(array_column($demand, "demand_number"));
                }
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $result = ['msg' => $e->getMessage(), 'flag' => false];
            //保存系统日志
            operatorLogInsert([
                'id' => $data['check_code'],
                'type' => 'INSPECT_RESULT_RECEIVE',
                'content' => isset($log_data['type']) ? $log_data['type'] : '异常',
                'detail' => $e->getMessage(),
            ]);
        }

        // 接收产品系统验货结果后，推送计划系统
        if(count($demand_list) > 0 && $type != 4)$this->push_to_plan_by_result($demand_list);

        return $result;
    }

    /**
     * 免检驳回、验货不合格时信息提醒申请人
     */
    public function notice_apply_user($code='', $po='', $status=0, $apply_id='', $apply_user='')
    {
        if(!in_array($status, [4, 11]))return false;
        $status_cn = [
            4 => "申请编码:{$code}的验货申请单，申请免检被驳回，请及时处理",
            11 => "申请编码:{$code}的验货申请单验货不合格，请及时处理",
        ];
        $send_data = [
            'data' => [$po],
            'message' => $status_cn[$status],
            'user' => getActiveUserName(),
            'type' => '验货管理',
            'apply_id' => $apply_id,
            'recv_name' => $apply_user,
            'purchase_number' => $code,
        ];
        $this->load->model('Message_model');
        $this->Message_model->AcceptMessage('check_product', $send_data);
        return true;
    }

    /**
     * 推送数据到产品系统
     * @param array $push_data
     * @param string $url
     * @return array
     */
    private function _push_product_sys($push_data, $url)
    {
        $access_taken = getOASystemAccessToken();

        if (empty($url)) return ['flag' => false, 'msg' => 'API不存在'];
        if (empty($access_taken)) return ['flag' => false, 'msg' => '获取access_token值失败'];

        $url_api = $url . "?access_token=" . $access_taken;
        $_result = getCurlData($url_api, json_encode($push_data), 'POST', ['Content-Type: application/json']);
        operatorLogInsert([
            'id' => '2021',
            'type' => 'INSPECT_ORDER_PUSH_PRODUCT',
            'content' => '推送数据到产品系统',
            'detail' => "request:".json_encode($push_data).".....response:{$_result}",
        ]);
        $_result = json_decode($_result, true);

        if (isset($_result['code']) && $_result['code'] == 200 && !empty($_result['data'])) {
            $result = ['flag' => true, 'msg' => ''];
        } else {
            $msg = isset($_result['msg']) ? $_result['msg'] : '';
            $result = ['flag' => false, 'msg' => '接口推送数据失败' . '[' . $msg . ']'];
        }
        return $result;
    }

    /**
     * 根据数据id获取订单信息
     * @param int $id
     * @param string $type |重验时关联记录表获取前一次验货员信息，回传给采购系统
     * @return array
     */
    private function _get_order_info($id,$type='')
    {
        $query = $this->purchase_db;
        $query->select('a.id as main_id,a.status,a.supplier_code,a.is_abnormal,a.check_times,a.purchase_type_id,a.purchase_number,a.is_abnormal,a.is_system_created,a.judgment_result,a.check_code');
        $query->from("{$this->table_supplier_check} a");
        if('order_edit' == $type){
            $query->select('b.inspector,b.inspector_number');
            $query->join("{$this->table_supplier_check_record} b", 'a.id=b.check_id AND a.check_times=b.batch_no');
        }
        if($type == 'cancel'){
            $query->join("pur_supplier_check_sku as c", 'a.id=c.check_id AND a.check_times=c.batch_no');
            $query->where('c.id', $id);
        }else{
            $query->where('a.id', $id);
        }
        $result = $query->get()->row_array();
        return $result;
    }

    /**
     * 根据PO获取所属的sku，用于验证手工创建验货单sku是否属于该采购单
     * @param $purchase_number
     * @return array
     */
    private function _get_sku_data_by_po($purchase_number)
    {
        $query = $this->purchase_db;
        $query->select('b.sku');
        $query->from("{$this->table_purchase_order} a");
        $query->join("{$this->table_purchase_order_items} b", 'a.purchase_number=b.purchase_number');
        $query->where('a.purchase_number', $purchase_number);
        return $query->get()->result_array();
    }

    /**
     * 根据采购单号和sku获取产品名称和图片url
     * @param $purchase_number
     * @param array $sku
     * @return array
     */
    private function _get_product_info($purchase_number, $sku = array())
    {
        $result = $this->purchase_db->select('A.sku,A.product_name,A.product_img_url,B.product_thumb_url')
            ->from($this->table_purchase_order_items.' AS A')
            ->join($this->table_product.' AS B','A.sku=B.sku','LEFT')
            ->where('A.purchase_number', $purchase_number)
            ->where_in('A.sku', $sku)
            ->get()->result_array();
        $result_tmp = array();
        foreach ($result as $item) {
            $result_tmp[$item['sku']] = $item;
        }
        unset($result);
        return $result_tmp;
    }

    /**
     * 获取待验证采购单数据
     * @param string $purchase_number
     * @param array $sku
     * @return array
     */
    private function _get_check_purchase_data($purchase_number, $sku = array())
    {
        //1、获取待验证数据
        $query = $this->purchase_db;
        $query->select('a.shipment_type,a.purchase_number,a.purchase_type_id,b.sku,b.purchase_unit_price,b.confirm_amount,
        c.product_line_id,s.is_overseas_boutique,');
        $query->from("{$this->table_purchase_order_items} b");
        $query->join("{$this->table_purchase_order} a", 'a.purchase_number=b.purchase_number');
        $query->join("pur_purchase_suggest as s", 'b.demand_number=s.demand_number', "left");
        $query->join("{$this->table_product} c", 'c.sku=b.sku');

        $query->where('a.purchase_number', $purchase_number);
        if (!empty($sku) && is_array($sku)) {
            $query->where_in('c.sku', $sku);
        }
        $result = $query->get()->result_array();
        //获取一级产品线ID
        foreach ($result as &$item){
            $product_line_data = $this->_get_top_product_line($item['product_line_id']);
            if(!$product_line_data['flag']){
                return array();
            }
            $item['product_line_id'] = $product_line_data['product_line_id'];
        }

        return $result;
    }

    /**
     * 获取验货规则
     * @param int $purchase_type_id 业务线
     * @param int $product_line_id 产品线
     * @return array|bool|mixed
     */
    private function _get_check_rule($purchase_type_id, $product_line_id)
    {
        if (empty($purchase_type_id) OR empty($product_line_id)) return false;
        $idx = $purchase_type_id . '_' . $product_line_id;
        $result = $this->rediss->getData('CHECK_RULE_INFO_' . $idx);
        $result = json_decode($result, true);

        if (empty($result) OR !is_array($result)) {
            $query = $this->purchase_db;
            $query->select('a.id,a.purchase_type_id,a.purchase_unit_price,a.purchase_amount,a.effective_time,b.product_line_id');
            $query->from("{$this->table_check_rule} AS a");
            $query->join("{$this->table_check_rule_items} AS b", 'a.id=b.rule_id');
            $query->where('a.status', 1);
            $query->where('a.purchase_type_id', $purchase_type_id);
            $query->where('b.product_line_id', $product_line_id);
            //查询数据
            $result = $query->get()->row_array();
            //缓存规则
            $this->rediss->setData('CHECK_RULE_INFO_' . $idx, json_encode($result));
        }
        return $result;
    }

    /**
     * 更新采购单明细表和采购单请款单明细表验货状态
     * @param $po
     * @param $sku
     * @param array $data
     */
    private function _update_order_status($po, $sku, $data)
    {
        if (!is_array($sku)) $sku = array($sku);
        if (isset($data['status'])) {
            $this->purchase_db->where('purchase_number', $po)->where_in('sku', $sku)->update($this->table_purchase_order_items, ['check_status' => $data['status']]);
        }
        $ha_pd = $this->purchase_db->from($this->table_purchase_order_pay_detail)->where('purchase_number', $po)->where_in('sku', $sku)->get()->result_array();
        if (isset($data['is_special']) && $ha_pd && count($ha_pd) > 0) {
            $this->purchase_db->where('purchase_number', $po)->where_in('sku', $sku)->update($this->table_purchase_order_pay_detail, ['is_special' => $data['is_special']]);
        }
    }

    /**
     * 更新发运跟踪表验货结果
     * @param string $purchase_number
     * @param array|string $sku
     * @param int $judgment_result
     * @param string $check_expect_time
     * @return bool
     */
    private function _update_shipment_track($purchase_number, $sku, $judgment_result, $check_expect_time)
    {
        if (empty($purchase_number) || empty($sku) || empty($judgment_result)) return false;
        if (!is_array($sku)) $sku = array($sku);

        //验货结果：1-验货通过（免检、合格、转IQC） ，2-验货不通过（不合格）
        $judgment_result_tmp = 0;
        if (in_array($judgment_result, [CHECK_ORDER_RESULT_EXEMPTION, CHECK_ORDER_RESULT_QUALIFIED, CHECK_ORDER_RESULT_IQC])) {
            $judgment_result_tmp = 1;
        } elseif (CHECK_ORDER_RESULT_UNQUALIFIED == $judgment_result) {
            $judgment_result_tmp = 2;
        }
        if (empty($judgment_result_tmp)) {
            return false;
        }

        //根据备货单号更新验货结果
        $query = $this->purchase_db;
        $query->set('judgment_result', $judgment_result_tmp);
        $query->set('push_to_plan', 0);
        if (!empty($check_expect_time)) {
            $query->set('check_expect_time', $check_expect_time);
        }
        $query->where('purchase_number', $purchase_number);
        $query->where_in('sku', $sku);
        return $query->update('pur_shipment_track_list');
    }

    /**
     *
     * 33108	验货列表数据改为备货单维度展示  yefanli
     * 自动审核获取数据
     */
    public function get_assembly_check_order_data($purchase_number='', $params=[])
    {
        $base = $this->purchase_db
            ->select('a.supplier_code,a.supplier_name,a.buyer_id,a.buyer_name,a.purchase_number,a.purchase_type_id,
            a.warehouse_code,a.shipment_type,b.sku,b.purchase_unit_price,b.confirm_amount,c.product_line_id,b.demand_number')
            ->from("{$this->table_purchase_order_items} as b")
            ->join("{$this->table_purchase_order} as a", 'a.purchase_number=b.purchase_number')
            ->join("{$this->table_product} as c", 'c.sku=b.sku')
            ->where('a.purchase_number', $purchase_number)
            ->where_in('b.sku', $params['sku'])
            ->group_by('b.sku')
            ->get()->result_array();
        $supplier = [];
        $use_data = [];
        if($base && count($base) > 0){
            foreach ($base as $val){
                $pur = $val['purchase_number'];
                $sup_code = $val['supplier_code'];
                if(!isset($use_data[$pur]))$use_data[$pur] = [];
                if(!isset($supplier[$sup_code])){
                    $supplier[$sup_code] = $this->_get_supplier_info($sup_code);;
                }
                $sup_row = $supplier[$sup_code];

                $use_data[$pur][] = [
                    "purchase_number"   => $val['purchase_number'],
                    "demand_number"     => $val['demand_number'],
                    "sku"               => $val['sku'],
                    "check_num"         => $val['confirm_amount'],
                    "contacts"          => isset($sup_row['contact_person']) ? $sup_row['contact_person'] : '',
                    "phone"             => isset($sup_row['contact_number']) ? $sup_row['contact_number'] : '',
                    "province"          => isset($sup_row['ship_province']) ? $sup_row['ship_province'] : '',
                    "city"              => isset($sup_row['ship_city']) ? $sup_row['ship_city'] : '',
                    "area"              => isset($sup_row['ship_area']) ? $sup_row['ship_area'] : '',
                    "address"           => isset($sup_row['ship_address']) ? $sup_row['ship_address'] : '',
                    "is_urgent"         => '',
                    "check_suggest"     => '',
                    "expect"            => '',
                    "order_type"        => '',
                ];
            }
        }
        return $this->create_check_save($use_data);
    }

    /**
     * 33108	验货列表数据改为备货单维度展示  yefanli
     * 新增验货申请
     */
    public function create_check_save($param=[])
    {
        $res = ["code" => 0, "msg" => ''];

        // 获取采购单数据
        $order = $this->purchase_db->from("pur_purchase_order_items as it")
            ->join("purchase_order as o", "o.purchase_number=it.purchase_number", "inner")
            ->join("pur_purchase_suggest as su", "su.demand_number=it.demand_number", "inner")
            ->select("o.purchase_number,o.supplier_code,o.supplier_name,o.buyer_id,o.buyer_name,o.purchase_type_id,su.shipment_type,
            o.warehouse_code,it.purchase_unit_price,it.confirm_amount,it.demand_number")
            ->where_in("it.purchase_number", array_keys($param))
            ->get()
            ->result_array();

        $this->purchase_db->trans_begin();
        $push_check = [];
        try{
            foreach ($param as $key => $value){
                // 验货中验证
                $cehck_status = [
                    CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM,
                    CHECK_ORDER_STATUS_QUALITY_CHECKING,
                    CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM,
                    CHECK_ORDER_STATUS_QUALIFIED_APPLYING,
                    CHECK_ORDER_STATUS_IQC,
                ];
                $dn_list = array_unique(array_column($value, 'demand_number'));
                $has_list = $this->purchase_db->from("supplier_check_sku as cs")
                    ->select("demand_number")
                    ->where_in("status", $cehck_status)
                    ->where_in("demand_number", $dn_list)
                    ->get()->result_array();
                if($has_list && count($has_list) > 0){
                    $has_list_temp = array_unique(array_column($has_list, 'demand_number'));
                    throw new Exception("备货单 ".implode(',', $has_list_temp)." 验货中，不能再次申请！");
                }

                foreach ($value as $val){
                    $order_temp = false;
                    foreach ($order as $o_val){
                        if($key == $o_val['purchase_number'] && $val['demand_number'] == $o_val['demand_number']){
                            $order_temp = $o_val;
                            break;
                        }
                    }
                    if(!$order_temp)continue;

                    $uid = $val['handle_type'] != 'manually'? 0 :getActiveUserId();
                    $username = $val['handle_type'] != 'manually'? 'system' :getActiveUserName();
                    $is_system = $val['handle_type'] == 'manually' ? false : true;
                    $status = $val['handle_type'] == 'manually' ? CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM : CHECK_ORDER_STATUS_WAITING_PURCHASER_CONFIRM;
                    $check_times = $val['handle_type'] == 'manually' ? 1 : 0;

                    // 抽检等级
                    $check_level = $this->_get_check_level($order_temp['purchase_type_id'], $val['order_type'], $order_temp['purchase_unit_price']);
                    // 抽检数量
                    $check_qty = $this->_get_check_qty($check_level, $order_temp['confirm_amount']);
                    // 获取验货等级
                    $inspect_grade = $this->_get_inspect_grade($check_qty, $val['order_type'], 1, $order_temp['purchase_type_id']);

                    $area = $val["area"]?? "";
                    $master_data = [
                        "check_code"        => $this->_generate_check_code(),//验货申请编码,
                        "purchase_number"   => $key,
                        "supplier_code"     => $order_temp["supplier_code"]?? "",
                        "supplier_name"     => $order_temp["supplier_name"]?? "",
                        "buyer_id"          => $order_temp["buyer_id"]?? "",
                        "buyer_name"        => $order_temp["buyer_name"]?? "",
                        "buyer_user_number" => empty($order_temp["buyer_id"]) ? getUserNumberById($order_temp["buyer_id"]) : '',
                        "purchase_type_id"  => $order_temp["purchase_type_id"]?? "",

                        "contact_province"  => $val["province"]?? "",
                        "contact_city"      => $val["city"]?? "",
                        "contact_area"      => $area,
                        "contact_address"   => $val["address"]?? "",
                        "complete_address"  => $val["province"] . $val["city"] . $area . $val["address"],

                        "apply_user_id"     => $uid,
                        "apply_user_name"   => $username,
                        "status"            => $status,
                        "is_system_created" => $is_system?1:0,

                        "check_suggest"     => (int)$val["check_suggest"],
                        "order_type"        => (int)$val["order_type"],
                        "is_urgent"         => $val["is_urgent"],
                        "apply_remark"      => $val["remark"]??"系统自动生成",
                        "check_times"       => $check_times,
                        "create_user_name"  => $username,
                        "create_time"       => date("Y-m-d H:i:s"),
                    ];
                    if($val['handle_type'] != 'manually'){
                        // 供应商验货信息
                        $supplier = $this->_get_supplier_info($order_temp["supplier_code"]);
                        $master_data["supplier_check_times"]  = isset($supplier['check_times']) ? (int)$supplier['check_times'] + 1 : 1;
                        unset($master_data['check_suggest']);
                        unset($master_data['is_urgent']);
                        unset($master_data['order_type']);
                    }
                    $this->purchase_db->insert("supplier_check", $master_data);
                    $check_id = $this->purchase_db->insert_id();

                    $check_sku = [
                        "check_id"          => $check_id,
                        "demand_number"     => $val['demand_number'],
                        "batch_no"          => $check_times,
                        "sku"               => $val['sku'],
                        "brand"             => $val['brand']??"",
                        "check_number"      => $val['check_num'], // 验货数量
                        "check_level"       => $check_level, // 抽检等级
                        "check_num"         => $check_qty, // 抽检数量
                        "inspect_grade"     => $inspect_grade, // 获取验货等级
                        "status"            => $status,
                        "purchase_num"      => $order_temp['confirm_amount'], // 采购数量
                        "unit_price"        => $order_temp['purchase_unit_price'], // 采购单价
                        "create_user_name"  => $username, // 添加人
                        "warehouse_code"    => $order_temp['warehouse_code'], // 仓库编码
                        "is_urgent"         => $val['is_urgent']??0, // 是否加急
                    ];
                    if($val['handle_type'] != 'manually'){
                        unset($check_sku['check_number']);
                        unset($check_sku['is_urgent']);
                        $check_sku['batch_no'] = 0;
                    }
                    $this->purchase_db->insert("supplier_check_sku", $check_sku);

                    $supplier = $this->_get_supplier_info($order_temp["supplier_code"]);
                    $record_data = [
                        "check_id"          => $check_id, // 验货记录ID
                        "batch_no"          => $check_times, // 验货批次号
                        "contact_person"    => isset($supplier['contact_person']) ? $supplier['contact_person'] : '', // 验货联系人
                        "phone_number"      => isset($supplier['contact_number']) ? $supplier['contact_number'] : '', // 联系电话
                        "check_expect_time" => $val['expect'], // 期望验货时间
                        "create_time"       => date("Y-m-d H:i:s"), // 添加时间
                    ];
                    if($val['handle_type'] != 'manually'){
                        unset($record_data['check_expect_time']);
                    }else{
                        $record_data['contact_person'] = $val['contacts'];
                        $record_data['phone_number'] = $val['phone'];
                    }
                    $this->purchase_db->insert("supplier_check_record", $record_data);

                    if($val['handle_type'] == 'manually'){
                        $push_check[] = [
                            'demand_number' => $val['demand_number'],
                            'check_id'      => $check_id,
                            'pur'           => $key,
                            'sku'           => $val['sku'],
                            'status'        => CHECK_ORDER_RESULT_EXEMPTION,
                            'remark'        => '导入生成后推送',
                            'expect'        => $val['expect'],
                        ];
                    }
                }
            }

            if($this->purchase_db->trans_status() === FALSE){
                $this->purchase_db->trans_rollback();
                throw new Exception("提交事务失败！");
            }
            $this->purchase_db->trans_commit();
            $res['code'] = 1;
            $res['msg'] = '新增验货成功';
        }catch (Exception $e){
            $this->purchase_db->trans_rollback();
            $res['msg'] = $e->getMessage();
        }catch (Error $e){
            $this->purchase_db->trans_rollback();
            $res['msg'] = $e->getMessage();
        }
        try{
            if(count($push_check) > 0){
                foreach ($push_check as $val){
                    $this->push_data_for_ps($val['check_id'], $val['pur'], $val['sku'], $val['status'], $val['remark'], 'manually');
                }
            }
        }catch (Exception $e){}

        return $res;
    }

    /**
     * 33108	验货列表数据改为备货单维度展示  yefanli
     * 审核验货结果
     */
    public function save_check_confirm($param=[])
    {
        $res = ["code" => 0, "msg" => ''];
        $this->purchase_db->trans_begin();
        try{
            $uid = getActiveUserId();
            $user = getActiveUserName();
            $check_times_list = [];
            foreach ($param as $key => $value){
                foreach ($value as $val){
                    $check_id = $val["check_id"];
                    $status = (2 == $val['is_check']) ? CHECK_ORDER_STATUS_WAITING_EXEMPTION_AUDIT : CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM;
                    $area = $val["area"]?? "";

                    $order_temp = $this->purchase_db->from("supplier_check")->where("id =", $check_id)->get()->row_array();
                    // 如果是 不合格待确认
                    $check_status = isset($order_temp['status']) ? $order_temp['status'] : false;
                    if($check_status == CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM){
                        $status = CHECK_ORDER_STATUS_WAITING_QUALITY_CONFIRM;
                    }

                    // 兼容历史数据
                    if(isset($order_temp['check_times']) && !isset($check_times_list[$check_id])){
                        $check_times_list[$check_id] = (int)$order_temp['check_times'] + 1;
                    }
                    $check_times = isset($check_times_list[$check_id]) ? $check_times_list[$check_id] : 1;

                    // 供应商验货信息
                    $supplier = $this->_get_supplier_info($order_temp["supplier_code"]);

                    $master_data = [
                        "contact_province"  => $val["province"]?? "",
                        "contact_city"      => $val["city"]?? "",
                        "contact_area"      => $area,
                        "contact_address"   => $val["address"]?? "",
                        "complete_address"  => $val["province"] . $val["city"] . $area . $val["address"],

                        "status"                => $status,
                        "check_suggest"         => $val["check_suggest"],
                        "order_type"            => $val["order_type"],
                        "is_urgent"             => $val["is_urgent"],
                        'modify_user_name'      => $val['user_name'],
                        'is_push'               => 0,
                        'push_time'             => date("Y-m-d H:i:s"),
                        "check_times"           => $check_times,
                        "supplier_check_times"  => isset($supplier['check_times']) ? (int)$supplier['check_times'] + 1 : 1,
                    ];

                    $this->purchase_db->where(["id" => $check_id])->update("supplier_check", $master_data);

                    $check_sku = [
                        "check_number"      => $val['check_num'], // 验货数量
                        "brand"             => $val['brand'], // 品牌
                        "status"            => $status,
                        "batch_no"          => $check_times,
                        "is_urgent"         => $val['is_urgent'], // 是否加急
                    ];
                    if($check_status == CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM) { // 如果是不合格待确认，则新增一条验货记录
                        $new_sku = $this->purchase_db->from('supplier_check_sku')->where(["check_id" => $check_id])->order_by("id", "desc")->get()->result_array();
                        $new_sku = $new_sku[0];

                        $check_sku['check_id']          = $check_id;
                        $check_sku['create_time']       = date("Y-m-d H:i:s");
                        $check_sku['demand_number']     = $new_sku['demand_number'];
                        $check_sku['check_level']       = $new_sku['check_level'];
                        $check_sku['check_num']         = $new_sku['check_num'];
                        $check_sku['inspect_grade']     = $new_sku['inspect_grade'];
                        $check_sku['warehouse_code']    = $new_sku['warehouse_code'];
                        $check_sku['purchase_num']      = $new_sku['purchase_num'];
                        $check_sku['unit_price']        = $new_sku['unit_price'];
                        $check_sku['sku']               = $new_sku['sku'];
                        $check_sku['box_all']           = $new_sku['box_all'];
                        $check_sku['box_psc']           = $new_sku['box_psc'];
                        $check_sku['box_size']          = $new_sku['box_size'];
                        $check_sku['box_weight']        = $new_sku['box_weight'];
                        $check_sku['box_tail_size']     = $new_sku['box_tail_size'];
                        $check_sku['box_tail_weight']   = $new_sku['box_tail_weight'];
                        $check_sku['box_tail_psc']      = $new_sku['box_tail_psc'];
                        $check_sku['box_lock_number']   = $new_sku['box_lock_number'];
                        $check_sku['create_user_name']  = $user;

                        $this->purchase_db->insert("supplier_check_sku", $check_sku);
                    }else{
//                        $check_sku['batch_no'] = $check_sku['batch_no'] == 1 ? 1 : $check_sku['batch_no'] - 1;
                        $this->purchase_db->where(["check_id" => $check_id])->update("supplier_check_sku", $check_sku);
                    }

                    $record_data = [
                        "contact_person"        => isset($val['contacts']) ? $val['contacts'] : '', // 验货联系人
                        "phone_number"          => isset($val['phone']) ? $val['phone'] : '', // 联系电话
                        "check_expect_time"     => $val['expect'], // 期望验货时间
                        'confirm_user_id'       => $uid,
                        'confirm_user_number'   => getUserNumberById($uid),
                        'confirm_user_name'     => $user,
                        "batch_no"              => $check_times,
                        'confirm_remark'        => isset($val['remark'])? $val['remark'] :"",
                        'confirm_time'          => date("Y-m-d H:i:s")
                    ];
                    if($check_status == CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM){ // 如果是不合格待确认，则新增一条验货记录
                        $record_data['check_id']        = $check_id;
                        $record_data['check_time']      = date("Y-m-d H:i:s");
                        $record_data['inspector']       = $uid;
                        $record_data['inspector_number']= getUserNumberById($uid);
                        $this->purchase_db->insert("supplier_check_record", $record_data);
                    }else{
//                        $record_data['batch_no'] = $record_data['batch_no'] == 1 ? 1 : $record_data['batch_no'] - 1;
                        $this->purchase_db->where(["check_id" => $check_id])->update("supplier_check_record", $record_data);
                    }

                    $this->push_data_for_ps($check_id, $key, $val['sku'], $status, $val['remark']);
                }
            }

            if($this->purchase_db->trans_status() === FALSE){
                $this->purchase_db->trans_rollback();
                throw new Exception("提交事务失败！");
            }
            $this->purchase_db->trans_commit();
            $res['code'] = 1;
            $res['msg'] = '确认验货成功！';
        }catch (Exception $e){
            $this->purchase_db->trans_rollback();
            $res['msg'] = $e->getMessage();
        }catch (Error $e){
            $this->purchase_db->trans_rollback();
            $res['msg'] = $e->getMessage();
        }

        return $res;
    }

    /**
     * 推送数据到产品系统
     */
    public function push_data_for_ps($check_id, $purchase_number, $sku, $status, $remark, $type='')
    {
        // 写入操作日志表
        $this->_insert_log([
            'id' => $check_id,
            'type' => '采购确认',
            'content' => $remark
        ]);

        //获取推送数据
        $push_data = $this->_get_push_inspection_data($check_id, $type);
        //获取推送url
        $url = getConfigItemByName('api_config', 'check_product', 'push_inspect_order');

        //推送到新产品系统
        $push_result = $this->_push_product_sys($push_data['data'], $url);
        if(!$push_result['flag']){
            operatorLogInsert([
                'id' => $check_id,
                'type' => 'INSPECT_ORDER_PUSH',
                'content' => '采购确认失败',
                'detail' => $push_result['msg'],
                'user' => getActiveUserName(),
            ]);
        }else{
            //推送成功，写入日志
            operatorLogInsert([
                'type' => 'INSPECT_ORDER_PUSH',
                'content' => '采购确认验货单推送产品系统',
                'detail' => json_encode($push_data['data']),
                'user' => getActiveUserName(),
            ]);

            // 更新采购单明细表和采购单请款单明细表验货状态
            $this->_update_order_status($purchase_number, [$sku], ['status' => $status]);
        }
    }

    /**
     * 根据验货结果，推送到计划系统
     */
    private function push_to_plan_by_result($data=[])
    {
        $res = ["code" => 0, "msg" => "默认处理失败"];
        $this->load->model('purchase_shipment/Purchase_shipping_management_model', 'shipment_model');
        $len = count($data) < 500 ? 500 : count($data);
        $data_list = $this->shipment_model->getPushToPlanData($len, $data);
        if(!$data_list || count($data_list) == 0){
            $res['msg'] = '没有要处理的数据!';
            return $res;
        }

        //请求Java接口，推送数据
        $post_data = [];
        foreach ($data_list as $item) {
            $post_data[] = [
                'demand_number'     => $item['new_demand_number'],                                      // 备货单号
                'inspection_result' => $item['judgment_result'],                                        // 验货结果
                'send_date'         => $item['judgment_result'] == 2 ? $item['check_expect_time'] : '', // 期望验货日期（验货不合格时传值）
            ];
        }
        $post_data = json_encode($post_data);

        $request_url = getConfigItemByName('api_config', 'shipping_management', 'push_inspect_result');
        $access_token = getOASystemAccessToken();
        $response = getCurlData($request_url . '?access_token=' . $access_token, $post_data, 'POST', ['Content-Type: application/json']);

        // 验货和确认推送计划系统
        operatorLogInsert([
            'id' => date("202106"),
            'type' => 'PUSH_TO_PLAN_BY_RESULT',
            'content' => '自动验货或批量确认后推送计划系统',
            'detail' => "request: {$post_data}......response: {$response}",
            'user' => getActiveUserName(),
        ]);
        $_result = json_decode($response, true);

        //推送成功的数据
        if (!empty($_result['success_list'])) {
            foreach ($_result['success_list'] as $demand_number) {
                $update_res = $this->shipment_model->updatePushState($demand_number);
                if($update_res){
                    echo '备货单推送成功:' . $demand_number . '<br>';
                }
            }
        }
        //推送失败的处理
        if (!empty($_result['fail_list'])) {
            foreach ($_result['fail_list'] as $demand_number => $error_msg) {
                operatorLogInsert([
                    'id' => $demand_number,
                    'type' => 'SHIPMENT_TRACK_PUSH_TO_PLAN',
                    'content' => '备货单推送失败',
                    'detail' => $error_msg,
                    'user' => '计划任务',
                ]);
            }
        }
        return $res;
    }

    /**
     * 获取验货单数据
     * @param string $purchase_number
     * @param array $params
     * @param string $type
     * @return array
     */
    private function _assembly_check_order_data($purchase_number = '', $params = array(), $type = 'system')
    {
        $query = $this->purchase_db;
        $query->select('a.supplier_code,a.supplier_name,a.buyer_id,a.buyer_name,a.purchase_number,a.purchase_type_id,a.warehouse_code,a.shipment_type');
        $query->select('b.sku,b.purchase_unit_price,b.confirm_amount,c.product_line_id,b.demand_number');
        $query->from("{$this->table_purchase_order} a");
        $query->join("{$this->table_purchase_order_items} b", 'a.purchase_number=b.purchase_number');
        $query->join("{$this->table_product} c", 'c.sku=b.sku');
        $query->where('a.purchase_number', $purchase_number);
        $query->where_in('b.sku', $params['sku']);
        $query->group_by('b.sku');
        $result = $query->get()->result_array();

        $master_data = array();//主表数据
        $chk_sku_data = array();//详情表数据
        $record_data = array();//记录表数据
        $time = date('Y-m-d H:i:s');
        foreach ($result as $item) {
            //主表数据只获取一次
            if (empty($master_data)) {
                $master_data['supplier_code'] = $item['supplier_code'];
                $master_data['supplier_name'] = $item['supplier_name'];
                $master_data['buyer_id'] = $item['buyer_id'];
                $master_data['buyer_name'] = $item['buyer_name'];
                $user_number = !empty($item['buyer_id']) ? getUserNumberById($item['buyer_id']) : '';
                $master_data['buyer_user_number'] = !empty($user_number) ? $user_number : '';
                //PO是工厂直发时,设置为工厂直发，否则默认为1-常规或者手工选择值
                $master_data['order_type'] = (1 == $item['shipment_type']) ? 3 : (!empty($params['order_type']) ? $params['order_type'] : 1);
                $master_data['purchase_type_id'] = $item['purchase_type_id'];
            }
            //获取一级产品线ID
            $product_line_data = $this->_get_top_product_line($item['product_line_id']);
            if(!$product_line_data['flag']){
                return array('master_data' => array(), 'sku_data' => array(), 'record_data' => array());
            }
            //验货sku表数据
            $chk_sku_data[] = array(
                'product_line_id' => $item['product_line_id'],
                'product_line_id_top' => $product_line_data['product_line_id'],
                'demand_number' => $item['demand_number'],
                'sku' => $item['sku'],
                'unit_price' => $item['purchase_unit_price'],
                'purchase_num' => $item['confirm_amount'],
                'warehouse_code' => $item['warehouse_code'],
                'create_time' => $time,
            );
        }

        if(empty($master_data) || empty($chk_sku_data)){
            return array('master_data' => array(), 'sku_data' => array(), 'record_data' => array());
        }

        //获取供应商信息
        $supplier_code = isset($master_data['supplier_code']) ? $master_data['supplier_code'] : '';
        $supplier = $this->_get_supplier_info($supplier_code);

        //手工创建
        if ('manually' == $type) {
            $master_data['contact_province'] = isset($params['contact_province']) ? $params['contact_province'] : 0;
            $master_data['contact_city'] = isset($params['contact_city']) ? $params['contact_city'] : 0;
            $master_data['contact_area'] = isset($params['contact_area']) ? $params['contact_area'] : 0;
            $master_data['contact_address'] = isset($params['contact_address']) ? $params['contact_address'] : '';
            $master_data['complete_address'] = isset($params['complete_address']) ? $params['complete_address'] : '';
            $master_data['apply_user_id'] = getActiveUserId();
            $master_data['apply_user_name'] = getActiveUserName();
            $master_data['apply_remark'] = isset($params['remark']) ? $params['remark'] : '';
            $master_data['is_system_created'] = 0;
            //记录表数据
            $record_data['contact_person'] = isset($params['contact_person']) ? $params['contact_person'] : '';
            $record_data['phone_number'] = isset($params['phone_number']) ? $params['phone_number'] : '';
        } else {
            //系统自动创建
            $master_data['contact_province'] = isset($supplier['ship_province']) ? $supplier['ship_province'] : 0;
            $master_data['contact_city'] = isset($supplier['ship_city']) ? $supplier['ship_city'] : 0;
            $master_data['contact_area'] = isset($supplier['ship_area']) ? $supplier['ship_area'] : 0;
            $master_data['contact_address'] = isset($supplier['ship_address']) ? $supplier['ship_address'] : '';
            $master_data['complete_address'] = isset($supplier['complete_address']) ? $supplier['complete_address'] : '';
            $master_data['apply_user_id'] = 1;
            $master_data['apply_user_name'] = 'admin';
            $master_data['apply_remark'] = '系统生成';
            $master_data['is_system_created'] = 1;
            //记录表数据
            $record_data['contact_person'] = isset($supplier['contact_person']) ? $supplier['contact_person'] : '';
            $record_data['phone_number'] = isset($supplier['contact_number']) ? $supplier['contact_number'] : '';
        }
        $master_data['check_code'] = $this->_generate_check_code();//验货申请编码
        $master_data['supplier_check_times'] = isset($supplier['check_times']) ? (int)$supplier['check_times'] + 1 : 1;//供应商检验次数（从供应商表取）
        $master_data['create_time'] = $time;
        $master_data['purchase_number'] = $purchase_number;
        $master_data['check_times'] = 1;//验货次数，同一检验编码的次数
        //记录表数据
        $record_data['create_time'] = $time;
        return array('master_data' => $master_data, 'sku_data' => $chk_sku_data, 'record_data' => $record_data);
    }

    /**
     * 根据供应商编码获取供应商信息(多条联系方式时取最原始的)
     * @param $supplier_code
     * @return array
     */
    private function _get_supplier_info($supplier_code)
    {
        $this->load->model('Supplier/Supplier_address_model', 'addressModel');

        $query = $this->purchase_db;
        $query->select('a.ship_province,a.ship_city,a.ship_area,a.ship_address,a.check_times,b.contact_person,b.contact_number');
        $query->from("{$this->table_supplier} a");
        $query->join("{$this->table_supplier_contact} b", 'a.supplier_code=b.supplier_code');
        $query->where('a.supplier_code', $supplier_code);
        $query->order_by('b.id', 'ASC');
        $supplier = $query->get()->row_array();
        if (!empty($supplier)) {
            $ship_province_cn = $this->addressModel->get_address_name_by_id($supplier['ship_province']);
            $ship_city_cn = $this->addressModel->get_address_name_by_id($supplier['ship_city']);
            $ship_area_cn = $this->addressModel->get_address_name_by_id($supplier['ship_area']);
            $supplier['complete_address'] = $ship_province_cn . $ship_city_cn . $ship_area_cn . $supplier['ship_address'];
        }
        return $supplier;
    }

    /**
     * 生成验货申请编码(YH-年月日+5位数序号)
     * @return mixed
     */
    private function _generate_check_code()
    {
        $sql = "SELECT CONCAT('YH-',DATE_FORMAT(NOW(),'%Y%m%d'),
        LPAD((CONVERT (SUBSTRING(IFNULL((SELECT check_code FROM pur_supplier_check ORDER BY id DESC LIMIT 1),0),-5),SIGNED)+1),5,'0')
        ) AS check_code";
        $res = $this->purchase_db->query($sql)->row_array();
        return $res['check_code'];
    }

    /**
     * 写入验货主表和验货sku表
     * @param array $master_data
     * @param array $sku_data
     * @param array $record_data
     * @return array
     */
    private function _insert_check_data($master_data = array(), $sku_data = array(), $record_data = array())
    {
        if (empty($master_data) || empty($sku_data) || empty($record_data)) return ['msg' => '参数不能为空', 'flag' => false];

        $query = $this->purchase_db;
        $query->trans_begin();
        try {
            //写入验货主表，并返回id
            $query->insert($this->table_supplier_check, $master_data);
            $id = $query->insert_id();
            if ($id) {
                //写入验货sku表
                foreach ($sku_data as &$item) {
                    $item['check_id'] = $id;
                    $query->insert($this->table_supplier_check_sku, $item);
                    //更新采购单明细表和采购单请款单明细表验货状态
                    $this->_update_order_status($master_data['purchase_number'], $item['sku'], ['status' => CHECK_ORDER_STATUS_WAITING_PURCHASER_CONFIRM]);
                }
                //写入验货记录表
                $record_data['check_id'] = $id;
                $query->insert($this->table_supplier_check_record, $record_data);
                //写入操作日志表
                $this->_insert_log([
                    'id' => $id,
                    'type' => '创建',
                    'content' => $master_data['apply_remark']
                ]);
            }

            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                $result = ['msg' => '操作失败', 'flag' => false];
            } else {
                $query->trans_commit();
                $result = ['msg' => '创建成功', 'flag' => true];
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $result = ['msg' => $e->getMessage(), 'flag' => false];
        }
        return $result;
    }

    /**
     * 校验PO+SKU是否已经已生成验货单
     * @param string $purchase_number
     * @param array $sku
     * @return array
     */
    private function _po_sku_is_exist($purchase_number = '', $sku = array())
    {
        $query = $this->purchase_db;
        $query->select('a.check_code,b.sku');
        $query->from("{$this->table_supplier_check} AS a");
        $query->join("{$this->table_supplier_check_sku} AS b", 'a.id=b.check_id');
        $query->where('a.purchase_number', $purchase_number);
        $query->where_in('b.sku', $sku);
        $query->group_by('b.sku');
        $result = $query->get()->result_array();

        if (empty($result)) {
            return ['status' => false, 'msg' => ''];
        } else {
            $check_code = $result[0]['check_code'];                 //验货申请编码
            $result_sku = array_column($result, 'sku');             //结果sku
            $sku_array = array_combine($sku, $sku);                 //传入sku
            foreach ($result_sku as $item) {
                //该po已经生成验货单的sku
                if (isset($sku_array[$item])) {
                    $exist_sku[] = $item;
                }
            }
            if (empty($exist_sku)) {
                return ['status' => false, 'msg' => ''];
            } else {
                return ['status' => true, 'msg' => '采购单[' . $purchase_number . '],SKU[' . implode(',', $exist_sku) . ']' . '已经生成验货单[' . $check_code . ']'];
            }
        }
    }

    /**
     * 验货操作日志写入
     * @param $data
     * @return bool
     */
    private function _insert_log($data)
    {
        //用户名称的处理
        $user = !empty($data['user']) ? $data['user'] : getActiveUserName();
        if (empty($user)) $user = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'admin';
        //用户ID的处理
        $user_id = !empty($data['user_id']) ? $data['user_id'] : getActiveUserId();
        if (empty($user_id)) $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1;


        $insert_data = [
            'record_id' => !empty($data['id']) ? $data['id'] : 0,
            'create_id' => $user_id,
            'create_user' => $user,
            'operation_type' => !empty($data['type']) ? $data['type'] : '',
            'operation_content' => !empty($data['content']) ? $data['content'] : '',
            'create_time' => !empty($data['time']) ? $data['time'] : date('Y-m-d H:i:s'),
        ];

        return $this->purchase_db->insert($this->table_check_log, $insert_data);
    }

    /**
     * 创建和重验，获取待推送新产品系统的验货数据
     * @param int $id
     * @param string $type 创建-''，重验-order_edit
     * @return array
     */
    private function _get_push_inspection_data($id, $type = '')
    {
        /**#1、获取待推送验货单数据#**/
        $query = $this->purchase_db;
        $field = 'a.id,a.check_code,a.purchase_number,a.check_times,a.is_urgent,a.is_abnormal,a.order_type' .
            ',a.contact_province,a.contact_city,a.contact_area,a.contact_address,a.complete_address,a.buyer_id,a.buyer_user_number,a.buyer_name,a.create_time' .
            ',a.purchase_type_id,a.supplier_code,a.supplier_name,a.check_suggest,a.assigner_code,a.assigner_name' .
            ',b.check_expect_time,b.confirm_user_number,b.confirm_user_name,b.contact_person,b.phone_number,b.inspector_number,b.inspector,b.confirm_time,b.confirm_remark' .
            ',c.id AS sku_id,c.sku,c.inspect_grade,c.check_level,c.is_abnormal AS sku_is_abnormal,c.check_num,c.purchase_num,c.invalid_num,c.owes_qty,c.unit_price';

        $query->select("{$field}");
        $query->from("{$this->table_supplier_check} a");
        $query->join("{$this->table_supplier_check_record} b", "a.id=b.check_id AND b.batch_no=a.check_times");
        $query->join("{$this->table_supplier_check_sku} c", "a.id=c.check_id AND c.batch_no=a.check_times");
        $query->where('a.id', $id);
        $result = $query->get()->result_array();
        if (!$result) {
            return ['flag' => false, 'msg' => '获取验货单数据失败'];
        }

        //转换数据
        $region_temp = $this->purchase_db->select('region_name as title,region_code as id')
            ->from('pur_region')
            ->where_in("region_type", [1,2,3])
            ->get()->result_array();
        $region = [];
        foreach ($region_temp as $val){
            if(!empty($val['id']) && $val['id'] > 0)$region[$val['id']] = $val['title'];
        }
        $push_data = array();
        $sku_data = array();
        $uid = getActiveUserId();
        $username = getActiveUserName();
        foreach ($result as $key => $item) {
            if (empty($push_data)) {
                //类型转换成产品系统对应值 1-常规->3，2-首次->2，3-直发->4，4-客诉->1
                switch ($item['order_type']) {
                    case 1:
                        $order_type = 3;
                        break;
                    case 2:
                        $order_type = 2;
                        break;
                    case 3:
                        $order_type = 4;
                        break;
                    case 4:
                        $order_type = 1;
                        break;
                    default:
                        $order_type = $item['order_type'];
                        break;
                }
                $push_data['applyNo'] = $item['check_code'];
                $push_data['po'] = $item['purchase_number'];
                $push_data['batch'] = $item['check_times'];
                $push_data['isUrgent'] = $item['is_urgent'];
                $push_data['isAbnormal'] = $item['is_abnormal'];
                $push_data['inspectType'] = $order_type;
                $push_data['inspectAreaId'] = $item['contact_province'] . '->' . $item['contact_city'] . '->' . $item['contact_area'];//验货区域id字符串 格式(111->1222->1234)
//                $push_data['inspectArea'] = mb_substr($item['complete_address'], 0, mb_strlen($item['complete_address']) - mb_strlen($item['contact_address']));//省市区

                $cpr = $item['contact_province'];
                $cci = $item['contact_city'];
                $car = $item['contact_area'];
                $push_data['inspectArea'] = (isset($region[$cpr]) ? $region[$cpr] : "").
                    (isset($region[$cci]) ? $region[$cci] : "").
                    (isset($region[$car]) ? $region[$car] : "");

                $push_data['inspectDetailAddress'] = $push_data['inspectArea'].' '.$item['contact_address'];
                $push_data['procureInspectTime'] = '0000-00-00' == $item['check_expect_time'] ? '' : $item['check_expect_time'];
                $push_data['buyerName'] = str_replace($item['buyer_user_number'], '', $item['buyer_name']);
                $push_data['buyerCode'] = $item['buyer_user_number'];
                $ap_uid = $type != 'manually' ? str_replace($item['confirm_user_number'], '', $item['confirm_user_name']) : $username;
                $applyCode = $type != 'manually' ? $item['confirm_user_number'] : getUserNumberById($uid);
                $push_data['applyUser'] = $ap_uid;
                $push_data['applyCode'] = $applyCode;
                $push_data['belongCode'] = $item['inspector_number'];
                $push_data['belongName'] = str_replace($item['inspector_number'], '', $item['inspector']);
                $push_data['applyDept'] = '采购部';
                $push_data['purchaseApplyTime'] = $item['create_time'];
                $push_data['contact'] = $item['contact_person'];
                $push_data['contactPhone'] = $item['phone_number'];
                $push_data['supplierCode'] = $item['supplier_code'];
                $push_data['supplierName'] = $item['supplier_name'];
                $push_data['applyInspectRemark'] = $item['confirm_remark'];
                $push_data['createUser'] = '采购系统';
                //区分是否为重验（重验时，产品系统初始状态值为1），或采购确认（采购确认时，按照是否验货和免检值传递）
                $push_data['applyStatus'] = 'order_edit' == $type ? 1 : $item['check_suggest'];
                $push_data['assignCode'] = $item['assigner_code'];
                $push_data['assignName'] = $item['assigner_name'];
                $push_data['createTime'] = $type != 'manually' ? $item['confirm_time'] : $item['create_time'];
            }
            $sku_data[$key]['sku'] = $item['sku'];
            $sku_data[$key]['inspectGrade'] = $item['inspect_grade'];
            $sku_data[$key]['spotGrade'] = $item['check_level'];
            $sku_data[$key]['isAbnormal'] = $item['sku_is_abnormal'];
            $sku_data[$key]['inspectNum'] = $item['check_num'];
            $sku_data[$key]['productCost'] = (float)$item['unit_price'];
            $sku_data[$key]['realPurchaseNum'] = (int)$item['purchase_num'] - (int)$item['invalid_num'];
            $sku_data[$key]['shortSupplyNum'] = $item['owes_qty'];
            $sku_data[$key]['createTime'] = $type != 'manually' ? $item['confirm_time'] : $item['create_time'];

            if($item['order_type'] == 3){

                $demandNumbers = $this->purchase_db->from("purchase_order_items")->where("purchase_number",$item['purchase_number'])
                    ->where("sku",$item['sku'])->select("demand_number")->get()->row_array();
                if(!empty($demandNumbers)){

                    $virtual_cabinet = $this->purchase_db->from("purchase_suggest")->where("demand_number",$demandNumbers['demand_number'])
                        ->select("temp_container")->get()->row_array();
                    $sku_data[$key]['virtual_cabinet'] = $virtual_cabinet['temp_container'];
                }
            }
        }
        $push_data['items'] = $sku_data;
        return ['flag' => true, 'data' => $push_data];
    }

    /**
     * 获取验货等级
     * @param int $owes_qty 缺货数量
     * @param int $order_type 单据类型
     * @param int $check_times 验货次数
     * @param int $purchase_type_id 业务线
     * @return string
     */
    private function _get_inspect_grade($owes_qty, $order_type, $check_times, $purchase_type_id)
    {
        //order_type单据类型（1-常规，2-首次，3-直发，4-客诉）
        if ($owes_qty > 0 OR 3 == $order_type) {
            //是否缺货=是，或验货类是否=直发
            $inspect_grade = 'S';
        } elseif (4 == $order_type OR $check_times > 1 OR (1 == $purchase_type_id OR 3 == $purchase_type_id)) {
            //验货类型是否=客诉，或为二验，或业务线=国内或FBA
            $inspect_grade = 'A';
        } else {
            $inspect_grade = 'B';
        }
        return $inspect_grade;
    }

    /**
     * 获取验货等级
     * @param int $purchase_type_id |1-国内仓,2-海外仓,3-FBA
     * @param int $order_type |1-常规，2-首次，3-直发，4-客诉
     * @param float $unit_price
     * @return string
     */
    private function _get_check_level($purchase_type_id, $order_type, $unit_price)
    {
        //FBA单,或者验货类型为‘首次’,并且单价大于等于350,抽检等级等于“S-4”,否则抽检等级等于“Ⅱ”
        if ((3 == $purchase_type_id OR 2 == $order_type) && (float)$unit_price >= 350) {
            $check_level = 'S-4';
        } else {
            $check_level = 'Ⅱ';
        }
        return $check_level;
    }

    /**
     * 根据sku获取缺货数量
     * @param array $sku
     * @return array
     */
    private function _get_stock_owes($sku = array())
    {
        $data = $this->purchase_db->select('sku,think_lack_qty')
            ->from("{$this->table_stock_owes}")
            ->where_in('sku', $sku)
            ->get()->result_array();
        $stock_owes_data = array();
        foreach ($data as $item) {
            $stock_owes_data[$item['sku']] = $item['think_lack_qty'];
        }
        return $stock_owes_data;
    }

    /**
     * 根据抽检等级和有效采购数量，获取抽检数量
     * @param int $check_level 抽检等级
     * @param int $pur_valid_num 有效采购数量
     * @return int
     */
    private function _get_check_qty($check_level, $pur_valid_num)
    {
        //检验标准对应的样本代码数据
        $sample_code_data = array(
            'S-4' => array('2to8' => 'A', '9to15' => 'A', '16to25' => 'B', '26to50' => 'C', '51to90' => 'C', '91to150' => 'D',
                '151to280' => 'E', '281to500' => 'E', '501to1200' => 'F', '1201to3200' => 'G', '3201to10000' => 'G', '10001to35000' => 'H'),
            'Ⅱ' => array('2to8' => 'A', '9to15' => 'B', '16to25' => 'C', '26to50' => 'D', '51to90' => 'E', '91to150' => 'F',
                '151to280' => 'G', '281to500' => 'H', '501to1200' => 'J', '1201to3200' => 'K', '3201to10000' => 'L', '10001to35000' => 'M')
        );
        //样本代码对应的抽检数量数据
        $check_qty_data = array('A' => 2, 'B' => 3, 'C' => 5, 'D' => 8, 'E' => 13, 'F' => 20, 'G' => 32, 'H' => 50, 'J' => 80, 'K' => 125, 'L' => 200, 'M' => 315);
        //判断有效采购数量
        if ($pur_valid_num >= 2 && $pur_valid_num <= 8) {
            $pur_valid_num_code = '2to8';
        } elseif ($pur_valid_num >= 9 && $pur_valid_num <= 15) {
            $pur_valid_num_code = '9to15';
        } elseif ($pur_valid_num >= 16 && $pur_valid_num <= 25) {
            $pur_valid_num_code = '16to25';
        } elseif ($pur_valid_num >= 26 && $pur_valid_num <= 50) {
            $pur_valid_num_code = '26to50';
        } elseif ($pur_valid_num >= 51 && $pur_valid_num <= 90) {
            $pur_valid_num_code = '51to90';
        } elseif ($pur_valid_num >= 91 && $pur_valid_num <= 150) {
            $pur_valid_num_code = '91to150';
        } elseif ($pur_valid_num >= 151 && $pur_valid_num <= 280) {
            $pur_valid_num_code = '151to280';
        } elseif ($pur_valid_num >= 281 && $pur_valid_num <= 500) {
            $pur_valid_num_code = '281to500';
        } elseif ($pur_valid_num >= 501 && $pur_valid_num <= 1200) {
            $pur_valid_num_code = '501to1200';
        } elseif ($pur_valid_num >= 1201 && $pur_valid_num <= 3200) {
            $pur_valid_num_code = '1201to3200';
        } elseif ($pur_valid_num >= 3201 && $pur_valid_num <= 10000) {
            $pur_valid_num_code = '3201to10000';
        } elseif ($pur_valid_num >= 10001 && $pur_valid_num <= 35000) {
            $pur_valid_num_code = '10001to35000';
        } else {
            $pur_valid_num_code = '';
        }
        //根据有效采购数量判断样本代码,根据样本代码得出抽检数量
        $sample_code_key = isset($sample_code_data[$check_level][$pur_valid_num_code]) ? $sample_code_data[$check_level][$pur_valid_num_code] : '';
        return isset($check_qty_data[$sample_code_key]) ? $check_qty_data[$sample_code_key] : 0;
    }

    /**
     * 获取有效和无效采购数量
     * @param array $data
     * Array
     * (
     *  [0] => Array
     *   (
     *      [purchase_number] => ABD192116
     *      [sku] => US-QC17611
     *      [purchase_num] => 100
     *   )
     * )
     * @return array
     */
    private function _get_purchase_qty_data($data = array())
    {
        $purchase_number = array_unique(array_column($data, 'purchase_number'));
        $sku = array_unique(array_column($data, 'sku'));

        //获取取消数量
        $purchase_order_cancel = $this->_get_cancel_qty($purchase_number, $sku);
        //获取已报损数量
        $purchase_order_loss = $this->_get_loss_amount($purchase_number, $sku);

        $result_data = array();      //结果数据
        foreach ($data as $item) {
            //采购单号+下划线+sku作为唯一下标
            $idx = $item['purchase_number'] . '_' . $item['sku'];
            //取消数量
            $cancel_ctq = 0;
            if (isset($purchase_order_cancel[$idx])) {
                $cancel_ctq = (int)$purchase_order_cancel[$idx]['cancel_ctq'];
            }
            //报损数量
            $loss_amount = 0;
            if (isset($purchase_order_loss[$idx])) {
                $loss_amount = (int)$purchase_order_loss[$idx]['loss_amount'];
            }

            //无效采购数量=取消数量+报损数量
            $pur_invalid_num = $cancel_ctq + $loss_amount;
            //有效采购数量=采购数量-无效采购数量
            $pur_valid_num = (int)$item['purchase_num'] - $pur_invalid_num;

            $result_data[$idx]['pur_invalid_num'] = $pur_invalid_num;
            $result_data[$idx]['pur_valid_num'] = $pur_valid_num;
        }
        return $result_data;
    }

    /**
     * 获取取消数量
     * @param array $purchase_number
     * @param array $sku
     * @return array
     */
    private function _get_cancel_qty($purchase_number = array(), $sku = array())
    {
        $query = $this->purchase_db;
        $query->select('a.purchase_number,a.sku,SUM(a.cancel_ctq) AS cancel_ctq');
        $query->from("{$this->table_order_cancel_detail} a");
        $query->join("{$this->table_order_cancel} b", 'a.cancel_id=b.id');
        $query->where_in('a.purchase_number', $purchase_number);
        $query->where_in('a.sku', $sku);
        $query->group_by('a.purchase_number,a.sku');
        $purchase_order_cancel_tmp = $query->get()->result_array();
        $purchase_order_cancel = array();
        foreach ($purchase_order_cancel_tmp as $item) {
            $purchase_order_cancel[$item['purchase_number'] . '_' . $item['sku']] = $item;
        }
        unset($purchase_order_cancel_tmp);
        return $purchase_order_cancel;
    }

    /**
     * 获取已报损数量
     * @param array $purchase_number
     * @param array $sku
     * @return array
     */
    private function _get_loss_amount($purchase_number = array(), $sku = array())
    {
        $query = $this->purchase_db;
        $query->select('pur_number AS purchase_number,sku,SUM(loss_amount) AS loss_amount');
        $query->where_in('pur_number', $purchase_number);
        $query->where_in('sku', $sku);
        $query->group_by('pur_number,sku');
        $purchase_order_loss_tmp = $query->get($this->table_order_reportloss)->result_array();
        $purchase_order_loss = array();
        foreach ($purchase_order_loss_tmp as $item) {
            $purchase_order_loss[$item['purchase_number'] . '_' . $item['sku']] = $item;
        }
        unset($purchase_order_loss_tmp);
        return $purchase_order_loss;
    }

    /**
     * 获取一级产品线ID
     * @param $product_line_id
     * @return array
     */
    private function _get_top_product_line($product_line_id)
    {
        $product_line_data = $this->product_line->get_product_top_line_data($product_line_id);
        if (empty($product_line_data) OR empty($product_line_data['product_line_id'])) {
            return ['flag' => false, 'product_line_id' => 0];
        } else {
            return ['flag' => true, 'product_line_id' => $product_line_data['product_line_id']];
        }
    }

}