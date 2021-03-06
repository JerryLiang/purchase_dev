<?php
/**
 * 多货列表模型类
 * User: Jaxton
 * Date: 2019/01/16 10:06
 */

class Multiple_goods_model extends Purchase_model
{
    protected $table_name = 'multiple_goods'; //多货表
    protected $return_goods = 'multiple_return_goods'; //多货退货表
    protected $transfer_goods = 'pur_multiple_transfer'; //多货调拨表
    protected $transfer_item = 'multiple_transfer_detail'; //多货调拨表

    protected $warehouse_result_method = '/provider/purPush/pushLogisticsInfo'; //推送快递单号入库情况

    protected $stock_center_url = '/stockcenter/ybStock/main/procurementAllocation'; //云仓接收地址

    public function __construct()
    {
        parent::__construct();
        $this->load->model('product/product_model');
        $this->load->model('product/product_line_model');
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_suggest_model');
        $this->load->model('purchase/purchase_order_items_model');
        $this->load->model('supplier/supplier_settlement_model');
        $this->load->model('purchase/purchase_order_determine_model');
        $this->load->helper('status_order');
        $this->load->helper('common');

    }
    /**
     * 获取多货数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function multiple_goods_list($params, $offset, $limit, $page = 1, $export = false)
    {
        $this->load->model('supplier/supplier_buyer_model', 'buyerModel');
        $data_list_temp = [];
        $getMultipleGoodsStatus = getMultipleGoodsStatus();
        $source_arr = getPurchaseSource();
        $line_arr = getBusinessLine();
        $settlement_list = $this->supplier_settlement_model->get_settlement();
        $settlement_list = array_column($settlement_list['list'], 'settlement_name', 'settlement_code');
        $pay_status_list = getPayStatus();

        $pay_list = getPayType();
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $field = '*,m.id as mid';
        $this->purchase_db->select($field)
            ->from($this->table_name . ' m')
            ->join('purchase_order order', 'order.purchase_number=m.purchase_number', 'left');

        if ($export && $params['ids']) {
            $ids_arr = explode(',', $params['ids']);
            $this->purchase_db->where_in('m.id', $ids_arr);

        } else {

            if (isset($params['multiple_number']) && !empty($params['multiple_number'])) {
                $search_pur = query_string_to_array($params['multiple_number']);
                $this->purchase_db->where_in('m.multiple_number', $search_pur);
            }

            if (isset($params['status']) && !empty($params['status'])) {
                $this->purchase_db->where('m.status', $params['status']);
            }

            if (isset($params['sku']) && !empty($params['sku'])) {
                $search_pur = query_string_to_array($params['sku']);
                $this->purchase_db->where_in('m.sku', $search_pur);
            }
            if (isset($params['instock_batch']) && !empty($params['instock_batch'])) {
                $search_pur = query_string_to_array($params['instock_batch']);
                $this->purchase_db->where_in('m.instock_batch', $search_pur);
            }

            if (isset($params['purchase_number']) && !empty($params['purchase_number'])) {
                $search_pur = query_string_to_array($params['purchase_number']);
                $this->purchase_db->where_in('m.purchase_number', $search_pur);
            }

            if (isset($params['demand_number']) && !empty($params['demand_number'])) {
                $search_pur = query_string_to_array($params['demand_number']);
                $this->purchase_db->where_in('m.demand_number', $search_pur);
            }

            if (isset($params['supplier_code']) && !empty($params['supplier_code'])) {
                $this->purchase_db->where_in('order.supplier_code', $params['supplier_code']);
            }

            if (isset($params['instock_date_start']) && !empty($params['instock_date_start'])) {

                $this->purchase_db->where('m.instock_date>=', $params['instock_date_start']);

            }

            if (isset($params['instock_date_end']) && !empty($params['instock_date_end'])) {

                $this->purchase_db->where('m.instock_date<=', $params['instock_date_end']);

            }

            if (isset($params['groupdatas']) && !empty($params['groupdatas'])) {

                $this->purchase_db->where_in('order.buyer_id', $params['groupdatas']);
            }

            if (isset($params['buyer_id']) and $params['buyer_id']) { // 采购员

                $this->purchase_db->where_in('order.buyer_id', $params['buyer_id']);

            }

        }

        $clone_db = clone ($this->purchase_db);
        $total = $clone_db->count_all_results(); //符合当前查询条件的总记录数

        if ($export) {
            $result = $this->purchase_db->order_by('m.instock_date desc ')->get()->result_array();

        } else {
            $result = $this->purchase_db->order_by('m.instock_date desc ')->limit($limit, $offset)->get()->result_array();

        }

        if (!empty($result)) {

            foreach ($result as $value) {
                $value_temp = [];
                $value_temp['id'] = $value['mid'];
                $product_info = $this->product_model->getproduct($value['sku']);
                $value_temp['image_url'] = $product_info['product_img_url'] ? erp_sku_img_sku($product_info['product_img_url']) : '';
                $value_temp['multiple_number'] = $value['multiple_number'];
                $value_temp['status'] = $getMultipleGoodsStatus[$value['status']];
                $value_temp['sku'] = $value['sku'];
                $category_all = $this->product_line_model->get_all_parent_category($product_info['product_line_id']);
                $value_temp['line_name'] = $category_all[0]['product_line_name'] ?? '';
                $value_temp['price'] = $product_info['purchase_price'] ?? '';
                $value_temp['product_name'] = $product_info['product_name'] ?? '';
                $value_temp['instock_batch'] = $value['instock_batch'];
                $value_temp['instock_date'] = $value['instock_date'];
                $value_temp['total_num'] = $value['total_num']; //多货数量
                $value_temp['transfer_num'] = $value['transfer_num']; //已转调拨数量
                $value_temp['return_num'] = $value['return_num']; //退货数量
                $value_temp['remain_num'] = $value['total_num'] - $value['transfer_num'] - $value['return_num']; //剩余数量

                if (!empty($value['purchase_number'])) {
                    $purchase_order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);
                    $value_temp['purchase_number'] = $value['purchase_number'];
                    $value_temp['source'] = $source_arr[$purchase_order_info['source']] ?? '';
                    $value_temp['demand_number'] = $value['demand_number'];
                    $value_temp['business_line'] = $line_arr[$purchase_order_info['purchase_type_id']] ?? '';
                    $value_temp['pay_status'] = $pay_status_list[$purchase_order_info['pay_status']] ?? '';
                    $value_temp['purchase_name'] = !empty($purchase_order_info['purchase_name']) ? get_purchase_agent($purchase_order_info['purchase_name']) : ''; //采购主体
                    $value_temp['supplier_code'] = $purchase_order_info['supplier_code'] ?? ''; //供应商编码
                    $value_temp['supplier_name'] = $purchase_order_info['supplier_name'] ?? ''; //供应商名称
                    $value_temp['settlement'] = $settlement_list[$purchase_order_info['account_type']] ?? ''; //结算方式
                    $value_temp['pay_method'] = $pay_list[$purchase_order_info['pay_type']] ?? ''; //支付方式
                    $value_temp['buyer_name'] = $purchase_order_info['buyer_name'] ?? ''; //采购员
                    $value_temp['warehouse_name'] = $warehouse_list[$purchase_order_info['warehouse_code']] ?? ''; //采购仓库
                } else {
                    $value_temp['purchase_number'] = '';
                    $value_temp['source'] = '';
                    $value_temp['demand_number'] = '';
                    $value_temp['business_line'] = '';
                    $value_temp['pay_status'] = '';
                    $value_temp['purchase_name'] = ''; //采购主体
                    $value_temp['supplier_code'] = ''; //供应商编码
                    $value_temp['settlement'] = ''; //结算方式
                    $value_temp['pay_method'] = ''; //支付方式
                    $value_temp['buyer_name'] = ''; //采购员
                    $value_temp['warehouse_name'] = ''; //采购仓库

                }

                $data_list_temp[] = $value_temp;

            }

        }
        $buyers = $this->buyerModel->get_buyers(); //采购员列表

        $return_data = [
            'data_list' => [
                'value' => $data_list_temp,
                'key' => ['序号', '图片', '多货编号', '多货状态', 'SKU', '产品线', '未税单价', '产品名称', '入库批次', '入库时间', '多货数量信息', '原采购单号', '采购来源', '原备货单号', '备货单业务线', '付款状态',
                    '采购主体', '供应商', '结算方式', '支付方式', '采购员', '采购仓库',
                ],
                'drop_down_box' => [
                    'status' => $getMultipleGoodsStatus,
                    'buyer_list' => $buyers['list'],
                    'group_data' => $this->getGroupData(),
                ],

            ],
            'paging_data' => [
                'total' => $total,
                'offset' => $page,
                'limit' => $limit,

            ],

        ];

        return $return_data;

    }

    //多货退货列表

    public function get_multiple_return_goods($params, $offset, $limit, $page = 1)
    {
        $data_list_temp = [];
        $this->load->model('supplier/supplier_buyer_model', 'buyerModel');

        $getMultipleGoodsStatus = getMultipleGoodsStatus();
        $source_arr = getPurchaseSource();
        $line_arr = getBusinessLine();
        $settlement_list = $this->supplier_settlement_model->get_settlement();
        $settlement_list = array_column($settlement_list['list'], 'settlement_name', 'settlement_code');
        $pay_list = getPayType();
        $pay_status_list = getPayStatus();
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $field = 'm.multiple_number,m.status,m.sku,m.instock_batch,m.instock_date,m.total_num,m.transfer_num,m.return_num,r.return_number,r.apply_name,r.create_time,r.quantity,r.express_no,r.carrier_name,r.carrier_code,r.status as track_status';
        $this->purchase_db->select($field)
            ->from($this->return_goods . ' r')
            ->join($this->table_name . ' m', 'm.multiple_number=r.multiple_number', 'left');

        if (isset($params['multiple_number']) && !empty($params['multiple_number'])) {
            $search_pur = query_string_to_array($params['multiple_number']);
            $this->purchase_db->where_in('m.multiple_number', $search_pur);
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $this->purchase_db->where('m.status', $params['status']);
        }

        if (isset($params['sku']) && !empty($params['sku'])) {
            $search_pur = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('m.sku', $search_pur);
        }

        if (isset($params['return_number']) && !empty($params['return_number'])) {
            $search_pur = query_string_to_array($params['return_number']);
            $this->purchase_db->where_in('r.return_number', $search_pur);
        }

        if (isset($params['apply_id']) && !empty($params['apply_id'])) {
            $this->purchase_db->where('r.apply_id', $params['apply_id']);
        }

        if (isset($params['instock_batch']) && !empty($params['instock_batch'])) {
            $search_pur = query_string_to_array($params['instock_batch']);
            $this->purchase_db->where_in('m.instock_batch', $search_pur);
        }

        if (isset($params['instock_date_start']) && !empty($params['instock_date_start'])) {

            $this->purchase_db->where('m.instock_date>=', $params['instock_date_start']);

        }

        if (isset($params['instock_date_end']) && !empty($params['instock_date_end'])) {

            $this->purchase_db->where('m.instock_date<=', $params['instock_date_end']);

        }

        if (isset($params['create_time_start']) && !empty($params['create_time_start'])) {

            $this->purchase_db->where('r.create_time>=', $params['create_time_start']);

        }

        if (isset($params['create_time_end']) && !empty($params['create_time_end'])) {

            $this->purchase_db->where('r.create_time<=', $params['create_time_end']);

        }
        if (isset($params['track_status']) && !empty($params['track_status'])) {
            if (is_array($params['track_status'])) {
                $this->purchase_db->where_in('r.status', $params['track_status'], false);
            } else {
                $this->purchase_db->where_in('r.status', explode(',', $params['track_status']), false);
            }
        }
        if (isset($params['express_no']) && !empty($params['express_no'])) {
            $this->purchase_db->like('r.express_no', $params['express_no']);
        }

        $clone_db = clone ($this->purchase_db);
        $total = $clone_db->count_all_results(); //符合当前查询条件的总记录数

        $result = $this->purchase_db->order_by('r.create_time desc ')->limit($limit, $offset)->get()->result_array();
        $track_status = getTrackStatus();
        unset($track_status[3]);

        if (!empty($result)) {

            foreach ($result as $value) {
                $value_temp = [];
                $product_info = $this->product_model->getproduct($value['sku']);
                $value_temp['image_url'] = $product_info['product_img_url'] ? erp_sku_img_sku($product_info['product_img_url']) : '';
                $value_temp['multiple_number'] = $value['multiple_number'];
                $value_temp['status'] = $getMultipleGoodsStatus[$value['status']];
                $value_temp['return_number'] = $value['return_number'];
                $value_temp['apply_name'] = $value['apply_name'];
                $value_temp['create_time'] = $value['create_time'];
                $value_temp['sku'] = $value['sku'];
                $category_all = $this->product_line_model->get_all_parent_category($product_info['product_line_id']);
                $value_temp['line_name'] = $category_all[0]['product_line_name'] ?? '';
                $value_temp['product_name'] = $product_info['product_name'] ?? '';
                $value_temp['instock_batch'] = $value['instock_batch'];
                $value_temp['instock_date'] = $value['instock_date'];
                $value_temp['total_num'] = $value['total_num']; //多货数量
                $value_temp['transfer_num'] = $value['transfer_num']; //已转调拨数量
                $value_temp['return_num'] = $value['return_num']; //多货退货数量
                $value_temp['remain_num'] = $value['total_num'] - $value['transfer_num'] - $value['return_num']; //剩余数量
                $value_temp['quantity'] = $value['quantity']; //退货数量
                $value_temp['actual_quantity'] = $value['actual_quantity']; //实际退货数量

                $logistics_info = [];

                if (!empty($value['carrier_code'])) {
                    $logistics_info['express_no'] = $value['express_no'];
                    $logistics_info['express_company'] = $value['carrier_name'];
                    $logistics_info['track_status'] = $value['track_status'];
                    $logistics_info['track_status_cn'] = !empty($value['track_status']) ? getTrackStatus($value['track_status']) : '';
                    $logistics_info['carrier_code'] = $value['carrier_code'];

                }

                $value_temp['express_no'] = $logistics_info;

                $data_list_temp[] = $value_temp;

            }

        }

        $buyers = $this->buyerModel->get_buyers();

        $return_data = [
            'data_list' => [
                'value' => $data_list_temp,
                'key' => ['序号', '图片', '多货编号', '多货状态', '退货编号', '申请人', '申请时间', 'SKU', '产品线', '产品名称', '入库批次', '入库时间', '多货数量信息', '退货数量',

                ],
                'drop_down_box' => [
                    'status' => $getMultipleGoodsStatus,
                    'buyers' => $buyers['list'],
                    'track_status' => $track_status,

                ],
            ],
            'paging_data' => [
                'total' => $total,
                'offset' => $page,
                'limit' => $limit,

            ],

        ];

        return $return_data;

    }

    //获取退货单基本信息
    public function get_basic_multiple_info($id = 0, $multiple_number = '')
    {
        $getMultipleGoodsStatus = getMultipleGoodsStatus();
        $source_arr = getPurchaseSource();
        $line_arr = getBusinessLine();
        $settlement_list = $this->supplier_settlement_model->get_settlement();
        $settlement_list = array_column($settlement_list['list'], 'settlement_name', 'settlement_code');
        $pay_list = getPayType();
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $pay_status_list = getPayStatus();

        if ($id) {
            $where['id'] = $id;

        } else {
            $where['multiple_number'] = $multiple_number;

        }

        $info = $this->purchase_db->select('*')->from($this->table_name)->where($where)->get()->row_array();
        if (!empty($info)) {
            $info['status'] = $getMultipleGoodsStatus[$info['status']];
            $product_info = $this->product_model->getproduct($info['sku']);
            $info['image_url'] = $product_info['product_img_url'] ? erp_sku_img_sku($product_info['product_img_url']) : '';
            $info['product_name'] = $product_info['product_name'] ?? '';
            $info['remain_num'] = $info['total_num'] - $info['transfer_num'] - $info['return_num']; //剩余数量
            if (!empty($info['purchase_number'])) {
                $purchase_order_info = $this->purchase_order_model->get_one($info['purchase_number'], false);
                $info['purchase_number'] = $info['purchase_number'];
                $info['source'] = $source_arr[$purchase_order_info['source']] ?? '';
                $info['demand_number'] = $info['demand_number'];
                $info['business_line'] = $line_arr[$purchase_order_info['purchase_type_id']] ?? '';
                $info['pay_status'] = $pay_status_list[$purchase_order_info['pay_status']] ?? '';
                $info['purchase_name'] = !empty($purchase_order_info['purchase_name']) ? get_purchase_agent($purchase_order_info['purchase_name']) : ''; //采购主体
                $info['supplier_code'] = $purchase_order_info['supplier_code'] ?? ''; //供应商编码
                $info['settlement'] = $settlement_list[$purchase_order_info['account_type']] ?? ''; //结算方式
                $info['pay_method'] = $pay_list[$purchase_order_info['pay_type']] ?? ''; //支付方式
                $info['buyer_name'] = $purchase_order_info['buyer_name'] ?? ''; //采购员
                $info['warehouse_name'] = $warehouse_list[$purchase_order_info['warehouse_code']] ?? ''; //采购仓库

            }

        }

        return $info;

    }

    //获取多货调拨信息

    public function get_transfer_info($id = 0, $transfer_number = '', $have_item = false)
    {

        if ($id) {
            $where['id'] = $id;

        } else {
            $where['transfer_number'] = $transfer_number;

        }

        $info = $this->purchase_db->select('*')->from($this->transfer_goods)->where($where)->get()->row_array();

        if (!empty($info) && $have_item) {
            $info['transfer_detail'] = $this->purchase_db->select('*')->from($this->transfer_item)->where('transfer_number', $info['transfer_number'])->get()->result_array();

        }

        return $info;

    }

    //多货退货信息保存
    public function multiple_return_save($params)
    {
        $return = ['code' => false, 'msg' => ''];
        $add_data = $params;
        unset($add_data['id'], $add_data['return_freight_payment_type']);
        //获取多货信息
        $basic_info = $this->get_basic_multiple_info($params['id']);
        try {
            $valid_result = $this->valid_multiple($basic_info);
            if (!empty($valid_result)) {
                throw new Exception('多货编号:' . $basic_info['multiple_number'] . implode(',', $valid_result));

            }

            if ($params['quantity'] > $basic_info['remain_num']) {
                throw new Exception('退货数量超出剩余数量');
            }

            if (!in_array($params['return_freight_payment_type'], array_keys(getReturnFreightPaymentType()))) {
                throw new Exception('非法的运费支付方式');
            }

            $this->purchase_db->trans_begin();

            $purchase_order_item_info = $this->purchase_order_items_model->get_item($basic_info['purchase_number'], $basic_info['sku'], true);
            $add_data['return_number'] = $this->get_prefix_new_number('th');

            //推送java接口

            $post_data = [
                "w" => $basic_info['warehouse_code'], // 仓库
                "demandOrder" => $add_data['return_number'], // 需求单号
                "sku" => $basic_info['sku'], // sku
                "returnType" => 3, //多货退货类型
                "volume" => $params['quantity'], // 退货数量
                "returnPrice" => $purchase_order_item_info['purchase_unit_price'], // 退货单价
                "purUser" => $basic_info['buyer_name'], // 采购人
                "supplierCode" => $basic_info['supplier_code'], // 退货供应商
                "receiveUser" => $add_data['contact_person'], // 收货人
                "receivePhone" => $add_data['return_phone'], // 收货电话
                "receiveProvince" => $this->get_province_mane($add_data['return_province']), // 收货省份
                "receiveAddress" => $add_data['return_address'], // 收货地址
                "createTime" => date('Y-m-d H:i:s'), // 创建时间
                "isInspection" => 0, // 是否验货1是 0否
                "returnSupplierCode" => '', // 可退货供应商
                "payment" => $params['return_freight_payment_type'], // 运费支付方式1.到付,2.寄付(现结),3.寄付(月结)
            ];

            $url = getConfigItemByName('api_config', 'wms_system', 'receiveDemand'); //获取推送url

            $access_taken = getOASystemAccessToken();
            if (empty($url)) {
                throw new Exception('api不存在');
            }
            if (empty($access_taken)) {
                throw new Exception('获取access_token值失败');
            }

            $url_api = $url . "?access_token=" . $access_taken;
            $results_json = getCurlData($url_api, json_encode($post_data), 'post', ['Content-Type: application/json', 'org:org_00001']);
            $results = json_decode($results_json, true);

            if (isset($results['code']) && $results['code'] == 200) { //接口调用成功

                //写入退货数据
                $add_data['multiple_number'] = $basic_info['multiple_number'];
                $add_data['sku'] = $basic_info['sku'];
                $add_data['apply_id'] = getActiveUserId();
                $add_data['apply_name'] = getActiveUserName();
                $add_data['create_time'] = date('Y-m-d H:i:s');
                $add_data['actual_quantity'] = $params['quantity'];

                $flag = $this->purchase_db->insert($this->return_goods, $add_data); //写入退货表
                if ($flag) { //更新多货列表
                    $update['status'] = $add_data['actual_quantity'] == $basic_info['remain_num'] ? 3 : 2;
                    $update['return_num'] = $basic_info['return_num'] + $add_data['actual_quantity'];
                    $is_update = $this->purchase_db->where(['id' => $params['id']])
                        ->update($this->table_name, $update);

                    if (!$is_update) {
                        throw new Exception('多货信息更新失败!');

                    }

                } else {
                    throw new Exception('退货表新增失败');

                }

            } else {
                throw new Exception('调用退货推送接口失败,详情:' . $results_json);
            }

            $return['code'] = true;
            $this->purchase_db->trans_commit();

        } catch (exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();

        }

        return $return;

    }

    /*
     * @desc 验证该多货信息是否有效
     * @int $basic_info 多货信息 $type 类型(1验证时间是否超出40天2验证状态是否有效)
     * @return string $error_list 错误信息
     */

    public function valid_multiple($basic_info)
    {
        $error_list = [];

        if ($basic_info['status'] == 3) {
            $error_list[] = '该多货单已处理';

        }

        $belong = strtotime($basic_info['instock_date']) + 24 * 3600 * 40;

        if ($belong < time()) { //超出40天
            $error_list[] = '该多货单已超出40天';

        }

        return $error_list;

    }

    //获取多货退货信息
    public function get_return_multiple_info($return_number)
    {
        $result = [];
        $info = $this->purchase_db->select('*')->from($this->return_goods)->where('return_number', $return_number)->get()->row_array();
        if (!empty($info)) {
            $result['return_info'] = $info;
            $result['multiple_info'] = $this->get_basic_multiple_info(0, $info['multiple_number']);

        }
        $result['province_list'] = $this->get_province();

        return $result;

    }

    //获取审核
    public function view_transfer_detail($transfer_number)
    {

        $settlement_list = $this->supplier_settlement_model->get_settlement();
        $settlement_list = array_column($settlement_list['list'], null, 'settlement_code');
        $pay_list = getPayType();
        $purchase_order_status_list = getPurchaseStatus();
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $pay_status_list = getPayStatus();

        $transfer_info = $this->get_transfer_info(0, $transfer_number, true);
        if (!empty($transfer_info)) {

            $purchase_number_list = [$transfer_info['purchase_number']];
            foreach ($purchase_number_list as $purchase_no) {
                $order_temp_info = $this->purchase_order_determine_model->get_order_info($purchase_no);
                $order_temp_info = array_column($order_temp_info, null, 'demand_number');
                $purchase_demand_number_info[$purchase_no] = $order_temp_info;

            }

            //备货单数量
            $order_num_info = $purchase_demand_number_info[$transfer_info['purchase_number']][$transfer_info['demand_number']] ?? '';

            $transfer_info['confirm_amount'] = $order_num_info['confirm_amount'];
            $transfer_info['upselft_amount'] = $order_num_info['instock_qty'];
            $transfer_info['cancel_num'] = $order_num_info['cancel_ctq'];
            $transfer_info['loss_num'] = $order_num_info['loss_amount'];
            $transfer_info['instock_qty_more'] = $order_num_info['confirm_amount'] - $order_num_info['instock_qty'] - $order_num_info['cancel_ctq'] - $order_num_info['loss_amount'];

            // $transfer_info['order_num_info']['need_transfer_num'] = $order_num_info['confirm_amount']-$order_num_info['instock_qty']-$order_num_info['cancel_ctq']-$order_num_info['loss_amount'];

            $purchase_order_info = $this->purchase_order_model->get_one($transfer_info['purchase_number'], false);
            $product_info = $this->product_model->getproduct($transfer_info['sku']);
            $transfer_info['sku_images'] = $product_info['product_img_url'] ? erp_sku_img_sku($product_info['product_img_url']) : '';
            $transfer_info['purchase_order_status'] = $purchase_order_status_list[$purchase_order_info['purchase_order_status']];
            $demand_info = $this->purchase_suggest_model->get_one(0, $transfer_info['demand_number']);
            $transfer_info['suggest_order_status'] = $purchase_order_status_list[$demand_info['suggest_order_status']];

            $transfer_info['pay_status'] = $pay_status_list[$purchase_order_info['pay_status']] ?? '';
            $transfer_info['purchase_name'] = !empty($purchase_order_info['purchase_name']) ? get_purchase_agent($purchase_order_info['purchase_name']) : ''; //采购主体

            $transfer_info['supplier_code'] = $purchase_order_info['supplier_code'] ?? ''; //供应商编码
            $transfer_info['supplier_name'] = $purchase_order_info['supplier_name'] ?? ''; //供应商名称

            $transfer_info['account_type'] = $settlement_list[$purchase_order_info['account_type']]['settlement_name'] ?? ''; //结算方式
            $transfer_info['account_ratio'] = $settlement_list[$purchase_order_info['account_type']]['settlement_percent'] ?? ''; //结算比例

            $transfer_info['pay_type'] = $pay_list[$purchase_order_info['pay_type']] ?? ''; //支付方式

            $transfer_info['buyer_name'] = $purchase_order_info['buyer_name'] ?? ''; //采购员
            $transfer_info['warehouse_code'] = $warehouse_list[$purchase_order_info['warehouse_code']] ?? ''; //采购仓库

            $allocation_list = [];
            //明细展示
            if (!empty($transfer_info['transfer_detail'])) {
                foreach ($transfer_info['transfer_detail'] as $key => $detail) {
                    $multiple_info_one = $this->get_basic_multiple_info(0, $detail['multiple_number']);
                    $multiple_info_one['surplus'] = $multiple_info_one['remain_num'];
                    $multiple_info_one['account_type'] = $multiple_info_one['settlement'];
                    $multiple_info_one['pay_type'] = $multiple_info_one['pay_method'];
                    $multiple_info_one['quantity'] = $detail['quantity'];
                    $allocation_list[] = $multiple_info_one;

                }

            }

            $transfer_info['allocation_list'] = $allocation_list;

        }

        return $transfer_info;

    }

    /**
     * 生成 指定前缀的 最新编号（自动更新编号记录）
     * @author Dean
     * @param string    $order_prefix   前缀
     * @param int       $add_number     增量（默认 1）
     * @param int       $fixed_length   编号长度（默认 4，用来填充）
     * @return bool|string
     */
    public function get_prefix_new_number($order_prefix, $add_number = 1, $fixed_length = 4)
    {
        $table = '';
        $now_day = date('Ymd');
        $operator_key = strtoupper('get_prefix_multiple_' . $order_prefix . $now_day);
        $existsKeyNumber = $this->rediss->getData($operator_key); // 命令用于获取指定 key 的值。如果 key 不存在，返回 nil 。如果key 储存的值不是字符串类型，返回一个错误
        switch ($order_prefix) {
            case 'th':$table = $this->return_goods;
                break;
            case 'db':$table = $this->transfer_goods;
                break;
            default:;
        }

        if (empty($existsKeyNumber)) {
            $num_info = $this->purchase_db->select('count(*) as num')->from($table)->where('create_time>=', date('Y-m-d'))->get()->row_array();
            $num = $num_info['num'] + $add_number;
            $number = $this->joinNumberStr($order_prefix, $num, $fixed_length, $now_day); // 编号不足长度 左边自动补0
            $this->rediss->setData($operator_key, $num); // 只是存储数字

        } else {
            $this->rediss->incrByData($operator_key, $add_number); // 命令将 key 中储存的数字加上指定的增量值
            $number_int_value = $this->rediss->getData($operator_key);
            $number = $this->joinNumberStr($order_prefix, $number_int_value, $fixed_length, $now_day); // 编号不足长度 左边自动补0

        }

        return $number;
    }

    /**
     * 拼接指定格式的 前缀
     * @param $prefix
     * @param $number
     * @param $fixed_length
     * @param $date_str
     * @return string
     */
    public function joinNumberStr($prefix, $number, $fixed_length, $date_str)
    {
        return strtoupper($prefix) . $date_str . str_pad($number, $fixed_length, "0", STR_PAD_LEFT); // 编号不足长度 左边自动补0
    }

    /*
     * 查询出近40天的处理中和未处理的待调拨数据
     */

    public function get_need_transfer_list()
    {
        $data_list_temp = [];
        $getMultipleGoodsStatus = getMultipleGoodsStatus();
        $source_arr = getPurchaseSource();
        $line_arr = getBusinessLine();
        $settlement_list = $this->supplier_settlement_model->get_settlement();
        $settlement_list = array_column($settlement_list['list'], 'settlement_name', 'settlement_code');
        $pay_list = getPayType();
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $pay_status_list = getPayStatus();

        $sql = 'select * from pur_multiple_goods where DATE_SUB(CURDATE(), INTERVAL 40 DAY) <= instock_date  and status in (1,2) order by instock_date desc';
        $result = $this->purchase_db->query($sql)->result_array();
        if (!empty($result)) {

            foreach ($result as $value) {
                $value_temp = [];
                $product_info = $this->product_model->getproduct($value['sku']);
                $value_temp['image_url'] = $product_info['product_img_url'] ? erp_sku_img_sku($product_info['product_img_url']) : '';
                $value_temp['multiple_number'] = $value['multiple_number'];
                $value_temp['status'] = $getMultipleGoodsStatus[$value['status']];
                $value_temp['price'] = $product_info['purchase_price'] ?? '';
                $value_temp['product_name'] = $product_info['product_name'] ?? '';
                $value_temp['instock_batch'] = $value['instock_batch'];
                $value_temp['instock_date'] = $value['instock_date'];
                $value_temp['total_num'] = $value['total_num']; //多货数量
                $value_temp['transfer_num'] = $value['transfer_num']; //已转调拨数量
                $value_temp['return_num'] = $value['return_num']; //退货数量
                $value_temp['remain_num'] = $value['total_num'] - $value['transfer_num'] - $value['return_num']; //剩余数量

                if (!empty($value['purchase_number'])) {
                    $purchase_order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);
                    $value_temp['purchase_number'] = $value['purchase_number'];
                    $value_temp['source'] = $source_arr[$purchase_order_info['source']] ?? '';
                    $value_temp['demand_number'] = $value['demand_number'];
                    $value_temp['business_line'] = $line_arr[$purchase_order_info['purchase_type_id']] ?? '';
                    $value_temp['pay_status'] = $pay_status_list[$purchase_order_info['pay_status']];
                    $value_temp['purchase_name'] = !empty($purchase_order_info['purchase_name']) ? get_purchase_agent($purchase_order_info['purchase_name']) : ''; //采购主体
                    $value_temp['supplier_code'] = $purchase_order_info['supplier_code'] ?? ''; //供应商编码
                    $value_temp['settlement'] = $settlement_list[$purchase_order_info['account_type']] ?? ''; //结算方式
                    $value_temp['pay_method'] = $pay_list[$purchase_order_info['pay_type']] ?? ''; //支付方式
                    $value_temp['buyer_name'] = $purchase_order_info['buyer_name'] ?? ''; //采购员
                    $value_temp['warehouse_name'] = $warehouse_list[$purchase_order_info['warehouse_code']] ?? ''; //采购仓库
                } else {
                    $value_temp['purchase_number'] = '';
                    $value_temp['source'] = '';
                    $value_temp['demand_number'] = '';
                    $value_temp['business_line'] = '';
                    $value_temp['pay_status'] = '';
                    $value_temp['purchase_name'] = ''; //采购主体
                    $value_temp['supplier_code'] = ''; //供应商编码
                    $value_temp['settlement'] = ''; //结算方式
                    $value_temp['pay_method'] = ''; //支付方式
                    $value_temp['buyer_name'] = ''; //采购员
                    $value_temp['warehouse_name'] = ''; //采购仓库

                }

                $data_list_temp[] = $value_temp;

            }

        }

        return $data_list_temp;

    }

    public function get_combine_info($demand_number, $purchase_number)
    {
        $source_arr = getPurchaseSource();
        $line_arr = getBusinessLine();
        $settlement_list = $this->supplier_settlement_model->get_settlement();
        $settlement_list = array_column($settlement_list['list'], 'settlement_name', 'settlement_code');
        $pay_list = getPayType();
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $pay_status_list = getPayStatus();

        $info = [];
        $return = ['code' => false, 'msg' => '', 'data' => []];
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $multiple_info = $this->get_need_transfer_list();
        //获取备货单信息
        $demand_info = $this->purchase_suggest_model->get_one(0, $demand_number);
        $purchase_order_info = $this->purchase_order_model->get_one($purchase_number, false);

        try {
            if (empty($demand_info)) {
                throw new Exception('备货单信息不存在');

            }
            if (empty($purchase_order_info)) {
                throw new Exception('采购单信息不存在');

            }

            $info['purchase_number'] = $purchase_number;
            $info['source'] = $source_arr[$purchase_order_info['source']] ?? '';
            $info['demand_number'] = $demand_number;
            $info['business_line'] = $line_arr[$purchase_order_info['purchase_type_id']] ?? '';
            $info['pay_status'] = $pay_status_list[$purchase_order_info['pay_status']] ?? '';
            $info['purchase_name'] = !empty($purchase_order_info['purchase_name']) ? get_purchase_agent($purchase_order_info['purchase_name']) : ''; //采购主体
            $info['supplier_code'] = $purchase_order_info['supplier_code'] ?? ''; //供应商编码.
            $info['supplier_name'] = $purchase_order_info['supplier_name'] ?? ''; //供应商名称
            $info['settlement'] = $settlement_list[$purchase_order_info['account_type']] ?? ''; //结算方式
            $info['pay_method'] = $pay_list[$purchase_order_info['pay_type']] ?? ''; //支付方式
            $info['buyer_name'] = $purchase_order_info['buyer_name'] ?? ''; //采购员
            $info['warehouse_name'] = $warehouse_list[$purchase_order_info['warehouse_code']] ?? ''; //采购仓库

            $return['code'] = true;
            $return['data'] = $info;

        } catch (Exception $e) {
            $return['msg'] = $e->getMessage();

        }

        return $return;

    }

    //多货退货列表

    public function get_transfer_multiple_list($params, $offset, $limit, $page = 1)
    {

        $this->load->model('supplier/supplier_buyer_model', 'buyerModel');

        $data_list_temp = [];
        $getMultipleGoodsStatus = getMultipleGoodsStatus();
        $source_arr = getPurchaseSource();
        $line_arr = getBusinessLine();
        $audit_status_arr = [1 => '待审核', 2 => '审核通过', 3 => '审核驳回'];
        $settlement_list = $this->supplier_settlement_model->get_settlement();
        $settlement_list = array_column($settlement_list['list'], 'settlement_name', 'settlement_code');
        $pay_list = getPayType();
        $pay_status_list = getPayStatus();
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $field = 'm.sku,m.multiple_number,m.instock_batch,tran.transfer_quantity,tran.purchase_number,tran.demand_number,tran.transfer_number,tran.audit_status,tran.apply_name,tran.create_time,tran.audit_name,tran.audit_time,';
        $this->purchase_db->select($field)
            ->from($this->transfer_goods . ' tran')
            ->join($this->transfer_item . ' item', 'item.transfer_number=tran.transfer_number', 'left')
            ->join($this->table_name . ' m', 'm.multiple_number=item.multiple_number', 'left');

        if (isset($params['multiple_number']) && !empty($params['multiple_number'])) {
            $search_pur = query_string_to_array($params['multiple_number']);
            $this->purchase_db->where_in('m.multiple_number', $search_pur);
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $this->purchase_db->where('m.status', $params['status']);
        }

        if (isset($params['audit_status']) && !empty($params['audit_status'])) {
            $this->purchase_db->where('tran.audit_status', $params['audit_status']);
        }

        if (isset($params['sku']) && !empty($params['sku'])) {
            $search_pur = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('m.sku', $search_pur);
        }

        if (isset($params['transfer_number']) && !empty($params['transfer_number'])) {
            $search_pur = query_string_to_array($params['transfer_number']);
            $this->purchase_db->where_in('tran.transfer_number', $search_pur);
        }

        if (isset($params['purchase_number']) && !empty($params['purchase_number'])) {
            $search_pur = query_string_to_array($params['purchase_number']);
            $this->purchase_db->where_in('tran.purchase_number', $search_pur);
        }

        if (isset($params['demand_number']) && !empty($params['demand_number'])) {
            $search_pur = query_string_to_array($params['demand_number']);
            $this->purchase_db->where_in('tran.demand_number', $search_pur);
        }

        if (isset($params['apply_id']) && !empty($params['apply_id'])) {
            $this->purchase_db->where('tran.apply_id', $params['apply_id']);
        }

        if (isset($params['instock_batch']) && !empty($params['instock_batch'])) {
            $search_pur = query_string_to_array($params['instock_batch']);
            $this->purchase_db->where_in('m.instock_batch', $search_pur);
        }

        if (isset($params['instock_date_start']) && !empty($params['instock_date_start'])) {

            $this->purchase_db->where('m.instock_date>=', $params['instock_date_start']);

        }

        if (isset($params['instock_date_end']) && !empty($params['instock_date_end'])) {

            $this->purchase_db->where('m.instock_date<=', $params['instock_date_end']);

        }

        if (isset($params['create_time_start']) && !empty($params['create_time_start'])) {

            $this->purchase_db->where('tran.create_time>=', $params['create_time_start']);

        }

        if (isset($params['create_time_end']) && !empty($params['create_time_end'])) {

            $this->purchase_db->where('tran.create_time<=', $params['create_time_end']);

        }

        if (isset($params['audit_id']) && !empty($params['audit_id'])) {
            $this->purchase_db->where('tran.audit_id', $params['audit_id']);
        }

        if (isset($params['audit_time_start']) && !empty($params['audit_time_start'])) {

            $this->purchase_db->where('tran.audit_time>=', $params['audit_time_start']);

        }

        if (isset($params['audit_time_end']) && !empty($params['audit_time_end'])) {

            $this->purchase_db->where('tran.audit_time<=', $params['audit_time_end']);

        }

        if (isset($params['purchase_order_status']) && !empty($params['purchase_order_status'])) {
            $this->purchase_db->join('purchase_order order', 'order.purchase_number=tran.purchase_number', 'left');

            $this->purchase_db->where('order.purchase_order_status', $params['purchase_order_status']);

        }

        if (isset($params['demand_status']) && !empty($params['demand_status'])) {
            $this->purchase_db->join('purchase_suggest sug', 'sug.demand_number=tran.demand_number', 'left');

            $this->purchase_db->where('sug.suggest_order_status', $params['demand_status']);

        }

        $clone_db = clone ($this->purchase_db);
        $total = $clone_db->count_all_results(); //符合当前查询条件的总记录数

        $result = $this->purchase_db->order_by('tran.create_time desc ')->limit($limit, $offset)->get()->result_array();

        if (!empty($result)) {
            $purchase_demand_number_info = []; //数量信息

            $purchase_number_list = array_unique(array_column($result, 'purchase_number'));
            foreach ($purchase_number_list as $purchase_no) {
                $order_temp_info = $this->purchase_order_determine_model->get_order_info($purchase_no);
                $order_temp_info = array_column($order_temp_info, null, 'demand_number');
                $purchase_demand_number_info[$purchase_no] = $order_temp_info;

            }

            foreach ($result as $value) {
                $value_temp = [];
                $product_info = $this->product_model->getproduct($value['sku']);
                $value_temp['image_url'] = $product_info['product_img_url'] ? erp_sku_img_sku($product_info['product_img_url']) : '';
                $value_temp['transfer_number'] = $value['transfer_number'];
                $value_temp['transfer_quantity'] = $value['transfer_quantity'];
                $value_temp['audit_status'] = $audit_status_arr[$value['audit_status']];
                $value_temp['apply_name'] = $value['apply_name'];
                $value_temp['create_time'] = $value['create_time'];
                $value_temp['audit_name'] = $value['audit_name'];
                $value_temp['audit_time'] = $value['audit_time'];
                $value_temp['sku'] = $value['sku'];
                $category_all = $this->product_line_model->get_all_parent_category($product_info['product_line_id']);
                $value_temp['line_name'] = $category_all[0]['product_line_name'] ?? '';
                $value_temp['product_name'] = $product_info['product_name'] ?? '';
                $value_temp['multiple_number'] = $value['multiple_number'] ?? '';
                $value_temp['instock_batch'] = $value['instock_batch'];

                //备货单数量
                $order_num_info = $purchase_demand_number_info[$value['purchase_number']][$value['demand_number']] ?? '';
                $value_temp['order_num_info'] = $order_num_info;

                $purchase_order_info = $this->purchase_order_model->get_one($value['purchase_number'], false);
                $value_temp['purchase_number'] = $value['purchase_number'];
                $value_temp['purchase_order_status'] = getPurchaseStatus($purchase_order_info['purchase_order_status']);
                $demand_info = $this->purchase_suggest_model->get_one(0, $value['demand_number']);
                $value_temp['demand_number'] = $value['demand_number'];
                $value_temp['demand_status'] = getPurchaseStatus($demand_info['suggest_order_status']);

                $value_temp['pay_status'] = $pay_status_list[$purchase_order_info['pay_status']] ?? '';
                $value_temp['purchase_name'] = !empty($purchase_order_info['purchase_name']) ? get_purchase_agent($purchase_order_info['purchase_name']) : ''; //采购主体

                $value_temp['supplier_code'] = $purchase_order_info['supplier_code'] ?? ''; //供应商编码
                $value_temp['supplier_code'] = $purchase_order_info['supplier_name'] ?? ''; //供应商名称

                $value_temp['settlement'] = $settlement_list[$purchase_order_info['account_type']] ?? ''; //结算方式
                $value_temp['pay_method'] = $pay_list[$purchase_order_info['pay_type']] ?? ''; //支付方式

                $value_temp['buyer_name'] = $purchase_order_info['buyer_name'] ?? ''; //采购员
                $value_temp['warehouse_name'] = $warehouse_list[$purchase_order_info['warehouse_code']] ?? ''; //采购仓库

                $purchase_order_item_info = $this->purchase_order_items_model->get_item($value['purchase_number'], $value['sku'], true);

                $value_temp['tax_price'] = $purchase_order_item_info['purchase_unit_price'] ?? 0;
                $value_temp['no_tax_price'] = $purchase_order_item_info['product_base_price'] ?? 0;

                $data_list_temp[] = $value_temp;

            }

        }

        //下拉列表采购员
        $buyers = $this->buyerModel->get_buyers();

        $return_data = [
            'data_list' => [
                'value' => $data_list_temp,
                'key' => ['序号', '图片', '多货编号', '多货状态', '退货编号', '申请人', '申请时间', 'SKU', '产品线', '产品名称', '入库批次', '入库时间', '多货数量信息', '退货数量',

                ],
                'drop_down_box' => [
                    'audit_status' => $audit_status_arr,
                    'buyers' => $buyers['list'],
                    'status' => $getMultipleGoodsStatus,
                    'demand_status' => getPurchaseStatus(),
                    'purchase_order_status' => getPurchaseStatus(),

                ],
            ],
            'paging_data' => [
                'total' => $total,
                'offset' => $page,
                'limit' => $limit,

            ],

        ];

        return $return_data;

    }

    public function audit_transfer_order($audit_result, $transfer_number, $remark)
    {
        $transfer_info = $this->get_transfer_info(0, $transfer_number, true);

        $result = ['code' => false, 'msg' => ''];
        $post_trans = []; //推送给java的调拨明细
        //审核通过

        if ($transfer_info['audit_status'] != 1) {
            throw new Exception('该调拨单非待审核状态!');

        }
        $this->purchase_db->trans_begin();

        try {

            if ($audit_result == 1) { //审核通过

                $purchase_number_list = [$transfer_info['purchase_number']];
                foreach ($purchase_number_list as $purchase_no) {
                    $order_temp_info = $this->purchase_order_determine_model->get_order_info($purchase_no);
                    $order_temp_info = array_column($order_temp_info, null, 'demand_number');
                    $purchase_demand_number_info[$purchase_no] = $order_temp_info;

                }

                //备货单数量
                $order_num_info = $purchase_demand_number_info[$transfer_info['purchase_number']][$transfer_info['demand_number']] ?? '';
                $need_transfer_num = $order_num_info['confirm_amount'] - $order_num_info['loss_amount'] - $order_num_info['cancel_ctq'] - $order_num_info['instock_qty'];

                if ($need_transfer_num != $transfer_info['transfer_quantity']) {

                    throw new Exception('调入数量过多，请审核不通过，重新提交申请!');

                }
                if (empty($transfer_info['transfer_detail'])) {
                    throw new Exception('调拨明细不存在!');

                }

                foreach ($transfer_info['transfer_detail'] as $trans_detail) {
                    $multiple_info = $this->get_basic_multiple_info(0, $trans_detail['multiple_number']);

                    $valid_multiple = $this->valid_multiple($multiple_info);
                    if (!empty($valid_multiple)) {
                        throw new Exception(implode(',', $valid_multiple));

                    }

                    if ($multiple_info['remain_num'] < $trans_detail['quantity']) {

                        throw new Exception('调入数量过多，请审核不通过，重新提交申请!');
                    }

                    if (($multiple_info['transfer_num'] + $trans_detail['quantity'] + $multiple_info['return_num']) == $multiple_info['total_num']) { //全部已处理就转为已处理
                        $status = 3;

                    } else {
                        $status = 2;
                    }

                    $flag = $this->purchase_db->where('multiple_number', $trans_detail['multiple_number'])->update($this->table_name, ['transfer_num' => $multiple_info['transfer_num'] + $trans_detail['quantity'], 'status' => $status]);
                    if (!$flag) {
                        throw new Exception('更新多货单信息失败');

                    }
                    $post_trans[] = ['sourcePurchaseOrderNo' => $trans_detail['purchase_number'], 'allotNum' => $trans_detail['quantity']];

                }

                $update = ['remark' => $remark, 'audit_status' => 2, 'audit_time' => date('Y-m-d H:i:s'), 'audit_id' => getActiveUserId(), 'audit_name' => getActiveUserName()];
                $flag = $this->purchase_db->where('transfer_number', $transfer_number)->update($this->transfer_goods, $update);

                //推送java接口
                $purchase_order_item_info = $this->purchase_order_items_model->get_item($transfer_info['purchase_number'], $transfer_info['sku'], true);

                $post_data = array(
                    'itemsId' => $purchase_order_item_info['id'],
                    'purNumber' => $transfer_info['purchase_number'],
                    'sku' => $transfer_info['sku'],
                    'instockBatch' => $transfer_info['transfer_number'],
                    'deliveryQty' => $transfer_info['transfer_quantity'],
                    'purchaseQty' => $purchase_order_item_info['confirm_amount'],
                    'deliveryDate' => date('Y-m-d H:i:s'),
                    'detailList' => $post_trans,

                );

                $report_loss_service_url = getConfigItemByName('api_config', 'java_system_service', 'transfer'); //获取java取消未到货接口地址
                $access_taken = getOASystemAccessToken(); //访问java token
                $url_api = $report_loss_service_url . "?access_token=" . $access_taken;

                $result_json = getCancelCurlData($url_api, json_encode($post_data), 'post', array('Content-Type: application/json'));
                $result_json_s = json_decode($result_json, true);

                if (isset($result_json_s['code'])) { //接口调用成功

                    if ($result_json_s['code'] != 200) {
                        throw new Exception($result_json_s['msg']);

                    } else {

                        $this->purchase_order_model->push_purchase_order_info_to_plan($transfer_info['purchase_number']);
                        //将信息推送门户系统
                        //采购单信息
                        $purchase_order_info = $this->purchase_order_model->get_one($transfer_info['purchase_number'], false);

                        $post_info = [
                            'purchaseNumber' => $transfer_info['purchase_number'],
                            'sku' => $transfer_info['sku'],
                            'instockDate' => date('Y-m-d H:i:s'),
                            'instockQty' => $transfer_info['transfer_quantity'],

                        ];

                        $post_url = GET_JAVA_DATA . $this->warehouse_result_method . "?access_token=" . $access_taken;
                        $post_result = getCancelCurlData($post_url, json_encode($post_info), 'post', array('Content-Type: application/json'));

                        //推给云仓
                        if (!empty($purchase_order_info['warehouse_code'])) {

                            $post_stock_info = [];

                            $post_stock_info['items'] = [
                                [
                                    "cargoOwnerId" => 8,
                                    "orderNo" => $transfer_info['purchase_number'],
                                    "warehouseCode" => $purchase_order_info['warehouse_code'],
                                    "sku" => $transfer_info['sku'],
                                    "number" => $transfer_info['transfer_quantity'],
                                ],

                            ];

                            $stock_url = GET_JAVA_DATA . $this->stock_center_url . "?access_token=" . $access_taken;
                            $stock_result = getCancelCurlData($stock_url, json_encode($post_stock_info), 'post', array('Content-Type: application/json'));

                            apiRequestLogInsert(
                                [
                                    'record_number' => $transfer_info['purchase_number'],
                                    'record_type' => 'multiple_goods_transfer',
                                    'api_url' => $stock_url,
                                    'post_content' => json_encode($post_stock_info),
                                    'response_content' => $stock_result,
                                ]);

                        }

                    }

                } else {
                    throw new Exception($result_json);
                }

            } elseif ($audit_result == 2) {
                $update = ['remark' => $remark, 'audit_status' => 3, 'audit_time' => date('Y-m-d H:i:s'), 'audit_id' => getActiveUserId(), 'audit_name' => getActiveUserName()];

                $flag = $this->purchase_db->where('transfer_number', $transfer_number)->update($this->transfer_goods, $update);

            }

            if (!$flag) {

                throw new Exception('审核失败');

            }

            if ($this->purchase_db->trans_status() === false) {
                $this->purchase_db->trans_rollback();
                throw new Exception('审核调拨单失败');
            } else {
                $result['code'] = true;
                $this->purchase_db->trans_commit();

            }

        } catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            $result['msg'] = $e->getMessage();

        }
//记录一下推送日志
        apiRequestLogInsert(
            [
                'record_type' => 'purchase_order_transfer',
                'record_number' => $transfer_number,
                'api_url' => $url_api,
                'post_content' => json_encode($post_data),
                'response_content' => $result_json,
                'create_time' => date('Y-m-d H:i:s'),
            ]);

        apiRequestLogInsert(
            [
                'record_type' => 'push_logistics_info',
                'record_number' => $transfer_number,
                'api_url' => $post_url ?? '',
                'post_content' => json_encode($post_info ?? ''),
                'response_content' => $post_result ?? '',
                'create_time' => date('Y-m-d H:i:s'),
            ]);

        return $result;

    }

    /**
     * 获取省份名称
     * @param $province
     * @return mixed
     */
    public function get_province_mane($province)
    {
        $name = '';
        $result = $this->purchase_db->select('region_name name')
            ->from('pur_region')
            ->where('region_code', $province)
            ->where('pid', 1)
            ->get()->row_array();
        if (!empty($result['name'])) {
            $name = $result['name'];
        }
        return $name;
    }

    /**
     * 获取省
     * @return array
     * @author Jaxton 2019/01/23
     */
    public function get_province()
    {
        $list = $this->purchase_db->select('id,region_name,region_code')->from('region')
            ->where('region_type', REGION_TYPE_PROVINCE)
            ->get()->result_array();
        $new_data = [];
        if (!empty($list)) {
            foreach ($list as $k => $v) {
                $new_data[$v['region_code']] = $v['region_name'];
            }
        }
        return $new_data;
    }

    /**
     * 获取多货金额总量
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Dean 2019/01/16
     */
    public function multiple_list_amount_total($params)
    {

        $field = 'SUM(m.total_num) as total_num,'
            . 'SUM(m.transfer_num) as total_transfer_num ,'
            . 'SUM(m.return_num) as total_return_num,'
            . 'm.sku,'
            . 'p.purchase_price,'
            . ' SUM(m.total_num - m.transfer_num - m.return_num) AS total_num_remain,'
            . ' SUM(m.total_num - m.transfer_num - m.return_num) * IFNULL(p.purchase_price,0) AS total_amount_remain,'
            . ' SUM(m.total_num) * IFNULL(p.purchase_price,0) AS total_amount';

        $this->purchase_db->select($field)
            ->from($this->table_name . ' m')
            ->join('purchase_order order', 'order.purchase_number=m.purchase_number', 'left')
            ->join('product p', 'p.sku=m.sku', 'left');

        if (isset($params['multiple_number']) && !empty($params['multiple_number'])) {
            $search_pur = query_string_to_array($params['multiple_number']);
            $this->purchase_db->where_in('m.multiple_number', $search_pur);
        }

        if (isset($params['status']) && !empty($params['status'])) {
            $this->purchase_db->where('m.status', $params['status']);
        }

        if (isset($params['sku']) && !empty($params['sku'])) {
            $search_pur = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('m.sku', $search_pur);
        }
        if (isset($params['instock_batch']) && !empty($params['instock_batch'])) {
            $search_pur = query_string_to_array($params['instock_batch']);
            $this->purchase_db->where_in('m.instock_batch', $search_pur);
        }

        if (isset($params['purchase_number']) && !empty($params['purchase_number'])) {
            $search_pur = query_string_to_array($params['purchase_number']);
            $this->purchase_db->where_in('m.purchase_number', $search_pur);
        }

        if (isset($params['demand_number']) && !empty($params['demand_number'])) {
            $search_pur = query_string_to_array($params['demand_number']);
            $this->purchase_db->where_in('m.demand_number', $search_pur);
        }

        if (isset($params['supplier_code']) && !empty($params['supplier_code'])) {
            $this->purchase_db->where_in('order.supplier_code', $params['supplier_code']);
        }

        if (isset($params['instock_date_start']) && !empty($params['instock_date_start'])) {

            $this->purchase_db->where('m.instock_date>=', $params['instock_date_start']);

        }

        if (isset($params['instock_date_end']) && !empty($params['instock_date_end'])) {

            $this->purchase_db->where('m.instock_date<=', $params['instock_date_end']);

        }

        if (isset($params['groupdatas']) && !empty($params['groupdatas'])) {

            $this->purchase_db->where_in('order.buyer_id', $params['groupdatas']);
        }

        if (isset($params['buyer_id']) and $params['buyer_id']) { // 采购员

            $this->purchase_db->where_in('order.buyer_id', $params['buyer_id']);

        }

        $result_sql_1 = $this->purchase_db->group_by('m.sku ')->get_compiled_select();

        // 汇总 退款金额
        $select_main1 = $this->purchase_db->query("SELECT SUM(total_num) AS total_num,
            SUM(total_transfer_num) AS total_transfer_num,
            SUM(total_return_num) AS total_return_num,
            SUM(total_num_remain) AS total_num_remain,
            SUM(total_amount_remain) AS total_amount_remain,
            SUM(total_amount) AS total_amount,
            count(1) AS total_sku_num
            FROM (
              $result_sql_1
            ) AS tmp ")->row_array();

//        $total_sku_num = 0;
        //        $total_num  = 0;//总多货数量
        //        $total_num_remain  = 0;//总剩余多货数量
        //        $total_amount = 0;//总金额
        //        $total_amount_remain = 0;//剩余多货总金额
        //
        //
        //
        //
        //
        //        if (!empty($result)) {
        //
        //            foreach ($result as $value) {
        //                $product_info =$this->product_model->getproduct($value['sku']);
        //                $remain_num = $value['total_num'] - $value['total_transfer_num'] - $value['total_return_num'];
        //                $price =  $product_info['purchase_price']??0;
        //                $total_amount_remain+= $remain_num*$price;
        //                $total_amount+=$value['total_num']*$price;
        //                $total_num+=$value['total_num'];
        //                $total_num_remain+=$remain_num;
        //
        //
        //            }
        //
        //            $total_sku_num = count(array_unique(array_column($result,'sku')));
        //
        //        }

        return array(
            'total_sku_num' => isset($select_main1['total_sku_num']) ? $select_main1['total_sku_num'] : 0,
            'total_num' => isset($select_main1['total_num']) ? $select_main1['total_num'] : 0,
            'total_num_remain' => isset($select_main1['total_num_remain']) ? $select_main1['total_num_remain'] : 0,
            'total_amount' => round(isset($select_main1['total_amount']) ? $select_main1['total_amount'] : 0, 2),
            'total_amount_remain' => round(isset($select_main1['total_amount_remain']) ? $select_main1['total_amount_remain'] : 0, 2),
        );
    }

    /**
     * 获取采购系统组别
     * @param GET
     * @author:luxu
     * @time:2020/9/8 11 19
     **/
    public function getGroupData()
    {
        $this->load->model('user/User_group_model', 'User_group_model');
        $result['alias'] = $this->User_group_model->getGroupList([1, 2]);
        $groupByData = $this->User_group_model->getGroupByData([1, 2]);

        $result['overseas'] = [];

        foreach ($groupByData as $key => $value) {

            if ($value['category_id'] == 2) {

                $result['overseas'][$value['value']] = $value['label'];
            }

            if ($value['category_id'] == 1) {

                $result['domestic'][$value['value']] = $value['label'];
            }
        }

        return $result;
    }

}
