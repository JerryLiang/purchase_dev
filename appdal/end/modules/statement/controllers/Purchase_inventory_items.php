<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/15
 * Time: 15:04
 */

class Purchase_inventory_items extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Purchase_inventory_items_model', 'inventory_items_model');
        $this->load->library('Export');
    }

    /**
     * 获取入库明细记录
     * /statement/Purchase_inventory_items/get_data_list?uid=1528
     */
    public function get_data_list()
    {
        $params = $this->_get_params();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) OR $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;

        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }

        $data = $this->inventory_items_model->get_data_list($params, $offsets, $limit, $page);
        $role_name=get_user_role();//当前登录角色
        $data['values'] = ShieldingData($data['values'],['supplier_name','supplier_code'],$role_name,NULL);

        $this->success_json_format($data);
    }

    /**
     * 添加备注
     * /statement/Purchase_inventory_items/add_remark?uid=1528&remark=备注信息
     */
    public function add_remark()
    {
        $id = $this->input->get_post('id');        //操作记录id(数组格式)
        $remark = $this->input->get_post('remark');//备注信息
        if (!is_array($id) OR empty(array_filter($id))) {
            $this->error_json('请求数据id缺失');
        } elseif (empty($remark)) {
            $this->error_json('备注信息不能为空');
        }
        $this->load->model('Purchase_statement_note_model', 'note_model');
        $data = $this->note_model->add_remark($id, 2, $remark,'添加备注');
        if ($data['flag']) {
            $this->success_json([], null, '添加成功');
        } else {
            $this->error_json($data['msg']);
        }
    }

    /**
     * 核销-入库明细列表导出
     * /statement/Purchase_inventory_items/data_list_export
     */
    public function data_list_export()
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $ids = $this->input->get_post('ids');                     //选择多条数据导出时，id用逗号分隔（1,2,3,4）
        $export_type = $this->input->get_post('export_type');     //导出类型（csv-csv格式,excel-excel格式）
        $source = $this->input->get_post('source');               //采购来源（1-合同，2-网采）

        $params = array();
        if (!empty($ids)) {
            $params['ids'] = array_filter(explode(',', $ids));
            $params['source'] = empty($source) ? SOURCE_COMPACT_ORDER : $source;//没有传值默认获取合同单的数据
        } else {
            $params = $this->_get_params();
        }

        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }


        //获取记录条数
        $page = 1;
        $limit = 1;
        $offsets = ($page - 1) * $limit;
        $data = $this->inventory_items_model->get_data_list($params, $offsets, $limit, $page, true);
        $total = $data['total'];

        //文件名
        $file_name = 'HX_inventory_items_' . date('YmdHis');
        if ('excel' == $export_type) {
            //表头字段
            $columns = array(
                'rows' => array(['序号', '产品图片', '产品名称', '入库批次', '发货批次号', '入库日期', '审核日期','下单数量', '入库金额', '入库数量', '多货数量',
                    '次品数量', '采购仓库', '采购订单', 'SKU', '合同号', '采购主体','是否代采', '是否退税', '供应商名称', '供应商代码', '采购单价', '币种',
                    '票面税率', '采购员', '仓库操作人', '业务线', '支付方式', '结算方式', '备货单状态', '订单状态', '是否承诺贴码', '是否实际贴码',
                    '对账单号', '采购单付款状态', '冲销状态', '剩余可冲销金额', 'PO剩余可冲销金额','是否隔离数据', '备注','SKU重量(g)','推送时间']
                ),
                'keys' => array(
                    'sequence', 'product_img_url', 'product_name', 'instock_batch', 'deliery_batch', 'instock_date', 'audit_time','real_confirm_amount', 'instock_price', 'instock_qty', 'instock_qty_more',
                    'defective_num', 'warehouse_cn', 'purchase_number', 'sku', 'compact_number', 'purchase_name','is_purchasing', 'is_drawback_cn', 'supplier_name', 'supplier_code', 'purchase_unit_price', 'currency_code',
                    'coupon_rate', 'buyer_name', 'instock_user_name', 'purchase_type_cn', 'pay_type_cn', 'settlement_type_cn', 'suggest_order_status_cn', 'purchase_order_status_cn', 'is_paste_cn', 'paste_labeled_cn',
                    'statement_number', 'pay_status_cn', 'charge_against_status_cn', 'surplus_charge_against_amount', 'po_surplus_aca_amount','is_isolation', 'remark','product_weight','create_time')
            );
            //网采单，去除合同号字段
            if (SOURCE_NETWORK_ORDER == $params['source']) {
                if (false !== arrayDelElementByVal($columns['rows'][0],'合同号')) {
                   arrayDelElementByVal($columns['keys'],'compact_number');
                }
            }
            //创建导出类对象
            $my_export = new Export();
            $down_path = $my_export->ExportExcel($file_name, $total, $columns, $params, $this->inventory_items_model, 'get_data_list',['product_img_url']);
        } else {


            $this->load->model('system/Data_control_config_model');
            try {
                $user_id = jurisdiction(); //当前登录用户ID
                $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
                $role_name = get_user_role();//当前登录角色
                $data_role = getRole();
                $res_arr = array_intersect($role_name, $data_role);
                $authorization_user = [];
                if (!(!empty($res_arr) or $user_id === true)) {
                    $params['swoole_userid'] = $user_id;//数据权限采购员id集合
                }
                $params['user_groups_types'] = $user_groups_types;
                $result = $this->Data_control_config_model->insertDownData($params, 'INVENTORYITEMS','入库明细下载', getActiveUserName(), 'csv', $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json("添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }
            die();
            //表头字段
            $columns = array(
                'sequence' => '序号', 'product_name' => '产品名称', 'instock_batch' => '入库批次', 'deliery_batch' => '发货批次号', 'instock_date' => '入库日期', 'audit_time' => '审核日期','real_confirm_amount' => '下单数量',
                'instock_price' => '入库金额', 'instock_qty' => '入库数量', 'instock_qty_more' => '多货数量', 'defective_num' => '次品数量', 'warehouse_cn' => '采购仓库',
                'purchase_number' => '采购订单', 'sku' => 'SKU', 'compact_number' => '合同号',
                'purchase_name' => '采购主体','is_purchasing' => '是否代采', 'is_drawback_cn' => '是否退税', 'supplier_name' => '供应商名称', 'supplier_code' => '供应商代码', 'purchase_unit_price' => '采购单价',
                'currency_code' => '币种', 'coupon_rate' => '票面税率', 'buyer_name' => '采购员', 'instock_user_name' => '仓库操作人', 'purchase_type_cn' => '业务线',
                'pay_type_cn' => '支付方式', 'settlement_type_cn' => '结算方式', 'suggest_order_status_cn' => '备货单状态', 'purchase_order_status_cn' => '订单状态',
                'is_paste_cn' => '是否承诺贴码', 'paste_labeled_cn' => '是否实际贴码', 'statement_number' => '对账单号', 'pay_status_cn' => '采购单付款状态',
                'charge_against_status_cn' => '冲销状态', 'surplus_charge_against_amount' => '剩余可冲销金额', 'po_surplus_aca_amount' => 'PO剩余可冲销金额','is_isolation' => '是否隔离数据', 'remark' => '备注'
            );
            //网采单，去除合同号字段
            if (SOURCE_NETWORK_ORDER == $params['source']) {
                unset($columns['compact_number']);
            }
            //创建导出类对象
            $my_export = new Export();
            $down_path = $my_export->ExportCsv($file_name, $total, $columns, $params, $this->inventory_items_model, 'get_data_list');
        }
        $this->success_json($down_path);
    }

    /**
     * 列表和导出查询参数
     * @return array|mixed|string
     */
    private function _get_params()
    {
        $params = [
            'supplier_code' => $this->input->get_post('supplier_code'),                 //供应商编码
            'buyer_id' => $this->input->get_post('buyer_id'),                           //采购员
            'pay_status' => $this->input->get_post('pay_status'),                       //付款状态
            'compact_number' => $this->input->get_post('compact_number'),               //合同号
            'purchase_number' => $this->input->get_post('purchase_number'),             //采购单号
            'instock_batch' => $this->input->get_post('instock_batch'),                 //入库批次号
            'settlement_type' => $this->input->get_post('settlement_type'),             //结算方式
            'pay_type' => $this->input->get_post('pay_type'),                           //支付方式
            'is_drawback' => $this->input->get_post('is_drawback'),                     //是否退税
            'instock_date_start' => $this->input->get_post('instock_date_start'),       //入库时间开始
            'instock_date_end' => $this->input->get_post('instock_date_end'),           //入库时间结束
            'pur_warehouse_code' => $this->input->get_post('pur_warehouse_code'),       //采购仓库
            'sku' => $this->input->get_post('sku'),                                     //sku
            'product_name' => $this->input->get_post('product_name'),                   //产品名称
            'statement_number' => $this->input->get_post('statement_number'),           //对账单号
            'suggest_order_status' => $this->input->get_post('suggest_order_status'),   //备货单状态
            'pur_order_status' => $this->input->get_post('pur_order_status'),           //订单状态
            'instock_qty' => $this->input->get_post('instock_qty'),                     //入库数量（1-入库数量≠0，2-入库数量=0）
            'charge_against_status' => $this->input->get_post('charge_against_status'), //入库批次冲销状态
            'source' => $this->input->get_post('source'),                               //采购来源（1-合同，2-网采）
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),           //业务线
            'purchase_agent' => $this->input->get_post('purchase_agent'),               //采购主体
            'is_purchasing' => $this->input->get_post('is_purchasing'),                 //是否代采
            'audit_time_start' => $this->input->get_post('audit_time_start'),           //审核时间开始
            'audit_time_end' => $this->input->get_post('audit_time_end'),               //审核时间结束
            'is_isolation' => $this->input->get_post('is_isolation'),                   //是否隔离数据
            'is_abnormal' => $this->input->get_post('is_abnormal'),                     //入库是否异常
            'group_ids'  => $this->input->get_post('group_ids'),                        // 组别ID
            'product_line_id' => $this->input->get_post('product_line_id'),             // 组别ID
            'has_statement_number' => $this->input->get_post('has_statement_number'),   // 是否生成对账单
            'upper_end_time_start' => $this->input->get_post('upper_end_time_start'),   // 上架时间-开始
            'upper_end_time_end' => $this->input->get_post('upper_end_time_end'),       // 上架时间-结束
            'demand_number' => $this->input->get_post('demand_number'),                 // 备货单号
            'is_oversea_boutique' => $this->input->get_post('is_oversea_boutique'),     // 是否海外精品
            'need_pay_time_start' => $this->input->get_post('need_pay_time_start'),     // 应付款时间-开始
            'need_pay_time_ned' => $this->input->get_post('need_pay_time_ned'),         // 应付款时间-结束
            'instock_month' => $this->input->get_post('instock_month'),                 // 入库月份
            'create_time_start' => $this->input->get_post('create_time_start'),         // 推送时间-开始
            'create_time_ned' => $this->input->get_post('create_time_ned'),             // 推送时间-结束
        ];
        $params['source'] = empty($params['source']) ? SOURCE_COMPACT_ORDER : $params['source'];//没有传值默认获取合同单的数据
        return $params;
    }
}