<?php
/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Purchase_suggest extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_suggest_model');
        $this->load->model('purchase_suggest_map_model');
        $this->load->model('product/product_model');
        $this->load->model('abnormal/Product_scree_model');
        $this->load->model('user/User_group_model');
        $this->load->helper('status_product');
        $this->load->model('supplier/supplier_model');
        $this->load->model('Purchase_demand_lock');
        $this->load->model('purchase_demand_model');
        $this->load->model('demand_public_model');
        $this->load->helper('common');
    }

    /**
     * 获取需求单下拉框值
     * @author:luxu
     * @time:2021年3月1号
     **/
    private function get_select_data()
    {

        $this->load->model('product/product_line_model');
        $data_list = $this->product_line_model->get_product_line_list_first();
        $product_lines = array_column($data_list, 'linelist_cn_name', 'product_line_id');

        $this->load->model('warehouse/Logistics_type_model');
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $logistics_type = array_column($logistics_type_list, 'type_name', 'type_code');

        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');

        $this->load->model('user/purchase_user_model');
        $data_list = $this->purchase_user_model->get_list();
        $applicant = array_column($data_list, 'name', 'id');

        $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
        $gwarehouse_code = array_column($pertain_wms_list, 'pertain_wms_name', 'pertain_wms_code');

        $this->load->model('system/Reason_config_model');
        $param['status'] = 1;//启用的
        $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
        $cancel_reason_category_list = array_column($cancel_reason_category_list['values'], 'reason_name', 'id');
        $demand_type = $this->purchase_suggest_model->get_demand_type();

        // 筛选项 采购员
        $this->load->model('user/purchase_user_model');
        $data_list = $this->purchase_user_model->get_list();
        $data_list = array_column($data_list, 'name', 'id');
        $user_list = ['0' => '空'] + $data_list;

        return [

            'is_drawback' => [1 => '是', 2 => '否'], // 是否退税
            'product_line_id' => $product_lines, // 产品线
            'is_new' => [1 => '是', 2 => '否'], // 是否新品
            'purchase_type' => [1 => "国内", 2 => "海外", 3 => "FBA", 4 => "PFB", 5 => "平台头程", 6 => "FBA大货"], //备货单业务线
            'is_expedited' => [1 => '否', 2 => '是'], // 是否加急
            'applicant' => $applicant, // 申请人
            'logistics_type' => $logistics_type, //物流类型
            'warehouse_lit' => $warehouse_list, // 目的仓
            'product_status' => getProductStatus(), // 产品状态
            'warehouse_code' => getWarehouse(), // 采购仓库
            'gwarehouse_code' => $gwarehouse_code, // 公共仓
            'is_boutique' => [1 => '否', 2 => '是'], // 是否精品
            'state_type' => getProductStateType(), // 开发类型
            'cancel_reason_category_list' => $cancel_reason_category_list, // 作废原因
            'suggeststatus' => getSuggestStatus(), // 备货单类型

            /*
             * DEMAND_STATUS_NOT_FINISH：为完结
DEMAND_TO_SUGGEST：需求单已生成备货单
DEMAND_SKU_STATUS_CONFIR： 需求状态单重新确认标识
DEMAND_STATUS_FINISHED：已经完结
DEMAND_STATUS_CANCEL：已经作废
             * */
            'demand_status' => [DEMAND_STATUS_NOT_FINISH => '未完结', DEMAND_TO_SUGGEST => '已生成备货单', DEMAND_SKU_STATUS_CONFIR => '重新确认'], // 需求单完结状态
            'is_overseas_first_order' => [1 => '是', 2 => '否'], // 是否海外仓首单
            'shipment_type' => [1 => '工厂发运', 2 => '中转仓发运'], // 发运类型
            'transformation' => [6 => '是', 1 => '否'],
            'demand_type' => $demand_type,
            'demand_lock' => [1 => '是', 2 => '否'], // 需求锁定
            'demand_repeat' => [1 => '是', 2 => '否'], // SKU 是否重复
            'is_distribution' => [1 => '是', 2 => '否'], // 是否 分销
            'supply_status' => [1 => '正常', 2 => '停产', 3 => '断货', 10 => '停产找货中'], //货源状态(1.正常,2.停产,3.断货,10:停产找货中)
            'user_list'=>$user_list

        ];
    }


    /**
     * 导出需求单信息接口 需求：30695 一键合单(1)(需求单)备货单审核页面重构
     * @params   array  查询条件
     * @author:luxu
     * @time:2021年3月1号
     **/
    public function demand_export_csv()
    {

        $clientDatas = []; // 前端查询数据条件缓存
        // 一次性获取前端传递数据
        foreach ($_GET as $clientkey => $clientvalue) {

            $values = $this->input->get_post($clientkey);
            if (is_array($values) && isset($values[0]) && empty($values[0]) && $clientkey != 'product_status') {

                continue;
            }
            $clientDatas[$clientkey] = $this->input->get_post($clientkey);
        }

        if (isset($clientDatas['group_ids']) && !empty($clientDatas['group_ids'])) {

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($clientDatas['group_ids']);
            $groupdatas = [];
            if (!empty($groupids)) {
                $groupdatas = array_column($groupids, 'value');
            }

            $clientDatas['groupdatas'] = $groupdatas;
        }

        $page = isset($clientDatas['offset']) ? $clientDatas['offset'] : 1; // 默认为第一页数据
        $clientDatas['limit'] = isset($clientDatas['limit']) ? $clientDatas['limit'] : 1; // 默认为20条数据
        $clientDatas['offset'] = ($page - 1) * $clientDatas['limit'];
        $result = $this->purchase_suggest_model->get_demand_datas($clientDatas);
        $total = $result['page']['total']; // 总条数
        try {
            $this->load->model('system/Data_control_config_model');
            $ext = 'csv';
            if ($total >= 150000) {

                $this->error_json('单次导出限制，请分批导出');
            }

            $clientDatas['role_name'] = get_user_role();
            $clientDatas['number'] = $total;

            $userid = jurisdiction(); //当前登录用户ID
            $role = get_user_role();//当前登录角色
            $data_role = getRole();//数据权限配置
            $res_arr = array_intersect($role, $data_role);


            if (!(!empty($res_arr) or $userid === true)) {
                $clientDatas['buyer_id_flag'] = true;

                if (empty($clientDatas['buyer_id'])) {
                    $clientDatas['buyer_id'] = $userid;
                }

            } else {
                $clientDatas['buyer_id_flag'] = 0;
                if (empty($clientDatas['buyer_id'])) {
                    $clientDatas['buyer_id'] = [];
                }
            }
            // print_r($clientDatas);die();

            $result = $this->Data_control_config_model->insertDownData($clientDatas, 'DEMANDER', '需求单导出', getActiveUserName(), $ext, $total);
        } catch (Exception $exp) {
            $this->error_json($exp->getMessage());
        }
        if ($result) {
            $this->success_json("添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }

    }

    /**
     * 获取需求单信息接口 需求：30695 一键合单(1)(需求单)备货单审核页面重构
     * @params   array  查询条件
     * @author:luxu
     * @time:2021年2月27号
     **/

    public function get_demand_datas()
    {

        $clientDatas = []; // 前端查询数据条件缓存
        // 一次性获取前端传递数据
        foreach ($_GET as $clientkey => $clientvalue) {

            $values = $this->input->get_post($clientkey);
            if (is_array($values) && isset($values[0]) && empty($values[0]) && $clientkey != 'product_status') {

                continue;
            }

            $clientDatas[$clientkey] = $this->input->get_post($clientkey);
        }
        if (isset($clientDatas['group_ids']) && !empty($clientDatas['group_ids'])) {

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($clientDatas['group_ids']);
            $groupdatas = [];
            if (!empty($groupids)) {
                $groupdatas = array_column($groupids, 'value');
            }

            $clientDatas['groupdatas'] = $groupdatas;
        }

        $page = isset($clientDatas['offset']) ? $clientDatas['offset'] : 1; // 默认为第一页数据
        $clientDatas['limit'] = isset($clientDatas['limit']) ? $clientDatas['limit'] : 20; // 默认为20条数据
        $clientDatas['nowoffset'] = $page;

        $clientDatas['offset'] = ($page - 1) * $clientDatas['limit'];
        $result = $this->purchase_suggest_model->get_demand_datas($clientDatas);

        foreach ($result['values'] as $key => &$value) {

            foreach ($value as $k => $v) {
                if (empty($v)) {
                    $value[$k] = "-";
                }
            }
        }
        $result['drop_down_box'] = $this->get_select_data();
        $this->success_json($result);


    }

    /**
     * 状态列表
     * @param string $status_type 状态类型
     * @param string $get_all 返回所有状态
     * @return array|mixed|null|string
     * @author Jolon
     */
    public function status_list($status_type = null, $get_all = null)
    {
        return $this->demand_public_model->status_list($status_type, $get_all);
    }

    /**
     * 采购需求 相关状态 下拉列表
     * @author Jolon
     */
    public function get_status_list()
    {
        $status_type = $this->input->get_post('type');
        $get_all = $this->input->get_post('get_all');

        $data_list_all = $this->status_list($status_type, $get_all);
        if ($data_list_all) {
            $this->success_json($data_list_all);
        } else {
            $this->error_json('未知的状态类型');
        }
    }


    /**
     * 根据 ID 或 备货单号 获取一个需求信息
     * @author Jolon
     */
    public function get_one()
    {
        $id = $this->input->get_post('id');
        $demand_number = $this->input->get_post('demand_number');

        // 参数错误
        if (empty($id) and empty($demand_number)) $this->error_json('参数缺失');

        $demand_info = $this->purchase_suggest_model->get_one($id, $demand_number);

        if (!empty($demand_info)) {
            $this->success_json($demand_info);
        } else {
            $this->error_json('未找到对应的需求信息');
        }
    }

    /**
     * 根据用户组，获取对应的用户id
     */
    private function get_user_by_group($group)
    {
        $this->load->model('user/User_group_model', 'User_group_model');
        $groupids = $this->User_group_model->getGroupPersonData($group);
        $groupdatas = [];
        if (!empty($groupids)) {
            $groupdatas = array_column($groupids, 'value');
        }
        return $groupdatas;
    }

    /**
     * 获取备货单查询条件
     * @return array
     */
    private function get_suggest_params()
    {
        // 多处使用，统一维护，请勿在此额外添加
        $param = $this->demand_public_model->get_search_params();
        $res = [];
        foreach ($param as $val) {
            $res[$val] = $this->input->get_post($val);
        }
        if ($res['list_type'] == 2 && empty($res['suggest_status'])) {
            $res['suggest_status'] = [SUGGEST_STATUS_NOT_FINISH, SUGGEST_STATUS_REBORN];
        }

        // $res['demand_type_id'] = PURCHASE_DEMAND_TYPE_PLAN;// 需求类型(计划单)
        return $res;
    }

    /**
     * 作废到需求单
     */
    public function cancel_suggest_to_demand()
    {
        $params = $this->get_suggest_params();
        $ids = [];
        $temp_param = $params;
        if (!isset($temp_param['cancel_reason_category'])) {

            $params['cancel_reason_category'] = $this->input->get_post('cancel_reason_category');
        }
        $cancel_reasons = $params['cancel_reason_category'];
        $cancel_reasons_c = $params['cancel_reason'];
        $temp_param['cancel_reason'] = $cancel_reasons;
        $temp_param['cancel_reason_category'] = $cancel_reasons_c;
        if (SetAndNotEmpty($params, 'ids')) {
            $ids = explode(",", $params['ids']);
        } else {
            $data = $this->purchase_demand_model->get_demanding_list($temp_param, false, false, true);
            if (isset($data['ids']) && !empty($data['ids'])) $ids = $data['ids'];
        }

        if (!empty($ids)) {
            $lockDemands = $this->purchase_suggest_model->get_demand_data($ids, 2);
            $lockMsg = "";
            if (!empty($lockDemands)) {
                $lockMsg = "备货单号:" . implode(",", array_column($lockDemands, "demand_number")) . ".处于锁单中，请先解锁。在作废。";
            }
            $ids = array_diff($ids, array_column($lockDemands, "demand_number"));
            $res = $this->purchase_demand_model->cancel_suggest_to_demand($ids, $temp_param['handle_type'], $temp_param['cancel_reason']);
            if (isset($res['code']) && $res['code'] == 1) {
                $lockMsg .= "备货单号:" . implode(",", $ids) . ",作废成功";
                $this->success_json([], [], $lockMsg);
                return;
            }
            if (isset($res['msg'])) $this->error_json($res['msg']);
        }
        $this->error_json("没有要取消的记录");
    }

    /**
     * 查询 采购需求列表
     * @author Jolon
     */
    public function get_list()
    {
        $params = $this->get_suggest_params();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $params['page'] = $page;
        $params['limit'] = query_limit_range($limit);
        $params['offset'] = ($page - 1) * $limit;

        if (isset($params['group_ids']) && !empty($params['group_ids'])) {
            $params['groupdatas'] = $this->get_user_by_group($params['group_ids']);
        }
        if ($params['list_type'] == 2) {
            $demand_info = $this->purchase_demand_model->get_demanding_list($params);
        } else {
            $demand_info = $this->purchase_demand_model->get_all_demand($params);
        }
        //$demand_info = $this->purchase_suggest_model->get_list($params,$offset,$limit,$page);

        $key_arr = $this->demand_public_model->demand_head_list();
        $drop_down_box = $this->status_list(null, true);
        $drop_down_box['product_status'] = getProductStatus();
        if ($params['list_type'] == 2 && isset($drop_down_box['suggest_status'])) {
            if (isset($drop_down_box['suggest_status'][4])) unset($drop_down_box['suggest_status'][4]);
            if (isset($drop_down_box['suggest_status'][2])) unset($drop_down_box['suggest_status'][2]);
        }

        $data_list = $demand_info['data_list'];
        $skus_num = $purchase_amount_num = $purchase_total_price_all = 0;
        if ($data_list) {
            $data_list_tmp = [];

            //增加汇总信息
            $skus = array_column($data_list, 'sku');
            $skus_num = count(array_unique($skus));//当前页SKU数量
            $purchase_amount_num = 0;//当前页PCS数(采购数量)
            $purchase_total_price_all = 0.00;//当前页订单总金额

            // 获取备货单的采购单信息
            $demand_number = array_column($data_list, 'demand_number');
            $purchase = $this->purchase_demand_model->get_purchase_list($demand_number);

            //产品状态
            $product_field = 'sku,product_status,state_type';
            $sku_arr = $this->product_model->get_list_by_sku(array_unique($skus), $product_field);
            $this->load->model('purchase/Purchase_order_model');
            $this->load->model('warehouse/Logistics_type_model');
            $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
            $logistics_type_list = array_column($logistics_type_list, 'type_name', 'type_code');

            $this->load->model('warehouse/Warehouse_model');
            $warehouse_list = $this->Warehouse_model->get_warehouse_list();
            $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
            $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
            $pertain_wms_list = array_column($pertain_wms_list, 'pertain_wms_name', 'warehouse_code');

            //缺货数量
            $skus = array_column($data_list, "sku");
            $scree_skus = $this->Product_scree_model->get_product_sku_scree_array($skus);
            $this->load->model('system/Reason_config_model');
            $param['status'] = 1;//启用的
            $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
            $category_list = array_column($cancel_reason_category_list['values'], 'reason_name', 'id');

            //获取缺货数量信息
            $this->load->model('product/Shortage_model');
            $lack_map = $this->Shortage_model->get_lack_info($skus);
            $demand_type_datas = $this->purchase_suggest_model->get_demand_type();
            foreach ($data_list as $value) {
                $value_tmp = [];
                $value_tmp['operator'] = '';
                $value_tmp['id'] = $value['id'];
                $value_tmp['is_overseas_first_order'] = $value['is_overseas_first_order'];
                $value_tmp['suggest_status'] = SetAndNotEmpty($value, 'suggest_status') ? getSuggestStatus($value['suggest_status']) : '';
                $value_tmp['estimate_time'] = $value['create_time'] > $value['estimate_time'] ? NULL : $value['estimate_time'];
                $value_tmp['delivery_time'] = $value['delivery_time_estimate_time'];
                $value_tmp['product_img_url'] = erp_sku_img_sku($value['product_img_url']);
                $value_tmp['product_jump_url'] = jump_url_product_base_info($value['sku']);
                $value_tmp['purchase_type_id'] = getPurchaseType($value['purchase_type_id']);
                $value_tmp['demand_number'] = $value['demand_number'];
                $value_tmp['sku'] = $value['sku'];
                $value_tmp['is_new'] = $value['is_new'] == 1 ? '是' : '否';
                $value_tmp['product_line_name'] = $value['product_line_name'];
                $value_tmp['product_name'] = $value['product_name'];
                $value_tmp['purchase_amount'] = $value['purchase_amount'];
                $value_tmp['starting_qty'] = $value['starting_qty'];
                $value_tmp['starting_qty_unit'] = $value['starting_qty_unit'];
                //缺货数量
                $value_tmp['lack_quantity'] = $value['left_stock'] != 0 ? $value['left_stock'] : '';
                $value_tmp['new_lack_qty'] = $lack_map[$value['sku']]['think_lack_qty'] ?? NULL;
                $value_tmp['lack_quantity_status'] = intval($value_tmp['lack_quantity']) < 0 ? '欠货' : '未欠货';
                $value_tmp['is_drawback'] = getIsDrawback($value['is_drawback']);
                $value_tmp['product_status'] = isset($sku_arr[$value['sku']]) ? getProductStatus($sku_arr[$value['sku']]['product_status']) : '';
                $value_tmp['purchase_unit_price'] = $value['purchase_unit_price'];
                $value_tmp['purchase_total_price'] = $value['purchase_total_price'];
                $value_tmp['supplier_code'] = $value['supplier_code'];
                $value_tmp['supplier_name'] = $value['supplier_name'];
                $value_tmp['is_cross_border'] = $value['is_cross_border'];
                $value_tmp['plan_product_arrive_time'] = $value['plan_product_arrive_time'];
                $value_tmp['warehouse_code'] = $value['warehouse_code'];
                $value_tmp['warehouse_name'] = $value['warehouse_name'];
                $value_tmp['pertain_wms_name'] = isset($pertain_wms_list[$value['warehouse_code']]) ? $pertain_wms_list[$value['warehouse_code']] : '';
                $value_tmp['buyer_id'] = $value['buyer_id'];
                $value_tmp['buyer_name'] = $value['buyer_name'];
                $value_tmp['expiration_time'] = $value['expiration_time'];
                $value_tmp['create_time'] = $value['create_time'];
                $value_tmp['audit_time'] = $value['audit_time'];
                $value_tmp['two_product_line_name'] = $value['two_product_line_name'];
                $value_tmp['es_shipment_time'] = $value['es_shipment_time'];
                $value_tmp['plan_product_arrive_time'] = $value['plan_product_arrive_time'];
                $s_type = $value['shipment_type'];

                $value_tmp['shipment_type_ch'] = "";

                if ($s_type == 1) {

                    $value_tmp['shipment_type_ch'] = "工厂发运";
                }

                if ($s_type == 2) {

                    $value_tmp['shipment_type_ch'] = "中转仓发运";
                }
                $value_tmp['ticketed_point'] = empty($value['maintain_ticketed_point']) || $value['maintain_ticketed_point'] == 0 ? NULL : $value['ticketed_point'];
                $value_tmp['sku_state_type_ch'] = $value['sku_state_type'] != 6 ? "否" : "是";

                $value_tmp['payment_method_source_ch'] = $value['source'] == 1 ? "合同" : "网采";

                $value_tmp['supply_status'] = empty($value['supply_status']) ? "" : getProductsupplystatus($value['supply_status']);//货源状态
                $value_tmp['currency'] = CURRENCY;//币种
                // 获取 需求对应的采购单信息

                if ($value_tmp['confirm_number'] === '') {
                    $value_tmp['cancel_ctq'] = '';
                } else {
                    $value_tmp['cancel_ctq'] = $value_tmp['purchase_amount'] - intval($value_tmp['confirm_number']);// 采购数量+未转在途数量=需求单数量
                    $value_tmp['cancel_ctq'] = $value_tmp['cancel_ctq'] > 0 ? $value_tmp['cancel_ctq'] : 0;
                }

                $value_tmp['purchase_order_status'] = SetAndNotEmpty($value_tmp, 'purchase_order_status') ? getPurchaseStatus($value_tmp['purchase_order_status']) : "";

                $sales_note = explode(' ', trim($value['sales_note']));
                if (isset($sales_note[count($sales_note) - 1]) && isset($sales_note[count($sales_note) - 2]) && isset($sales_note[count($sales_note) - 3])) {
                    $sales_note_string = $sales_note[count($sales_note) - 3] . ' ' . $sales_note[count($sales_note) - 2] . ' ' . $sales_note[count($sales_note) - 1];
                } else {
                    $sales_note_string = isset($sales_note[0]) ? $sales_note[0] : '';
                }

                $value_tmp['sales_name'] = $value['sales_name'];
                $value_tmp['sales_group'] = $value['sales_group'];
                $value_tmp['sales_account'] = $value['sales_account'];
                $value_tmp['platform'] = $value['platform'];
                $value_tmp['site'] = $value['site'];
                $value_tmp['site_name'] = $value['site_name'];
                $value_tmp['is_fumigation'] = '';
                if ($value['extra_handle'] == 1) {
                    $value_tmp['is_fumigation'] = "熏蒸";
                } elseif ($value['extra_handle'] == 2) {
                    $value_tmp['is_fumigation'] = "不熏蒸";
                }//是否熏蒸

                // 29775 是否海外精品
                $value_tmp['is_oversea_boutique'] = '否';
                if (in_array($value['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]) && $value['is_boutique'] == 1) $value_tmp['is_oversea_boutique'] = '是';

                $value_tmp['sales_note'] = $sales_note_string;
                $value_tmp['audit_note'] = $value['audit_note'];//审核备注
                $value_tmp['sales_note2'] = $value['sales_note2'];//销售备注2

                $cancel_reason = explode(' ', trim($value['cancel_reason']));
                if (isset($cancel_reason[count($cancel_reason) - 1]) && isset($cancel_reason[count($cancel_reason) - 2])) {
                    $cancel_reason_string = $cancel_reason[count($cancel_reason) - 2] . ' ' . $cancel_reason[count($cancel_reason) - 1];
                } else {
                    $cancel_reason_string = $value['cancel_reason'];
                }
                $value_tmp['cancel_reason'] = $cancel_reason_string;
                $value_tmp['cancel_reason_category'] = isset($category_list[$value['cancel_reason_category']]) ? $category_list[$value['cancel_reason_category']] : '';//作废原因类别;

                //如果需求状态为未完结，并且备货单创建时间超过7天,就自动清空

                if ($value['suggest_status'] == SUGGEST_STATUS_NOT_FINISH && $value['cancel_reason']) {
                    $cancel_sign = $this->purchase_suggest_model->get_noexpired_reason(null, $value['cancel_reason']);
                    if (empty($cancel_sign)) {
                        $value_tmp['cancel_reason'] = '';
                        $value_tmp['cancel_reason_category'] = '';
                    }
                }

                // 获取 到货数量、入库数量、取消数量、报损数量
                $value_tmp['unlock_time'] = $value['unlock_time'];
                $value_tmp['suggest_demand'] = $value['suggest_demand'];
                $value_tmp['de_demand_ty_id'] = $value['de_demand_ty_id'];
                $value_tmp['demand_qty'] = $value['demand_qty'];
                $value_tmp['suggest_amount'] = $value['suggest_amount'];
                $value_tmp['purchase_amount'] = $value['purchase_amount'];
                $value_tmp['unbuy_amount'] = $value['unbuy_amount'] ?? 0;
                $value_tmp['combination_status'] = $value['combination_status'] == 0 ? "正常" : "已合单";
                $value_tmp['demand_type_id'] = isset($demand_type_datas[$value['demand_type_id']]) ? $demand_type_datas[$value['demand_type_id']] : '';
                if ($value['demand_status'] == 5) {
                    $value['demand_status'] = 7;
                }
                $value_tmp['demand_status'] = SetAndNotEmpty($value, 'demand_status', 'n') ? getDemandStatus($value['demand_status']) : '未知状态';
                $value_tmp['demand_type'] = $value['demand_name_id_cn'];

                //缺货数量(新)
                $value['new_lack_qty'] = $lack_map[$value['sku']]['think_lack_qty'] ?? NULL;

                // 需求单导入-临时方案
                $value_tmp['is_expedited'] = ($value['is_expedited'] == PURCHASE_IS_EXPEDITED_Y) ? '是' : '否';
                $value_tmp['destination_warehouse'] = isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '';
                $value_tmp['create_user_name'] = $value['create_user_name'];
                $value_tmp['logistics_type'] = isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '';
                $value_tmp['is_abnormal'] = ($value['is_abnormal'] == SUGGEST_ABNORMAL_TRUE) ? '是' : '否';
                $value_tmp['is_boutique'] = getISBOUTIQUE($value['is_boutique']);
                $value_tmp['source_from'] = $value['source_from'];
                $value_tmp['state_type'] = isset($value['state_type']) ? getProductStateType((int)$value['state_type']) : '';
                $value_tmp['is_scree'] = empty($scree_skus) || !in_array($value['sku'], $scree_skus) ? 0 : 1;
                //    $value_tmp['is_scree']              = isset($value['is_scree']) && !empty($value['is_scree']) ? 1:0; //$this->get_product_scree($scree_skus,$value['sku']);
                $value_tmp['is_entities_lock'] = ($value['lock_type'] == LOCK_SUGGEST_ENTITIES) ? '锁单中' : '未锁单';
                $value_tmp['tax_rate'] = empty($value['tax_rate']) ? NULL : $value['tax_rate'];//退税率
                $value_tmp['issuing_office'] = deleteProductData($value['declare_unit']);//开票单位
                $value_tmp['product_img_url_thumbnails'] = $value['product_thumb_url'] ? erp_sku_img_sku_thumbnail($value['product_thumb_url']) : erp_sku_img_sku($value['product_img_url']);
                $value_tmp['connect_order_cancel'] = ($value['connect_order_cancel'] == 2) ? '是' : '否';
                if (isset($value['tis_purchasing'])) $value_tmp['is_purchasing_ch'] = $value['tis_purchasing'] == 1 ? '否' : '是';
                $value_tmp['is_distribution'] = $value['is_distribution'] == 0 ? '--' : $value['is_distribution'] == 1 ? '是' : '否';

                // 采购单
                $value_tmp['purchase_number'] = '';
                $value_tmp['purchase_order_status'] = '';
                if ($params['list_type'] == 3 && isset($purchase[$value['demand_number']])) {
                    $pur_val = $purchase[$value['demand_number']];
                    $value_tmp['purchase_number'] = isset($pur_val['purchase_number']) ? $pur_val['purchase_number'] : "";
                    $value_tmp['purchase_order_status'] = SetAndNotEmpty($pur_val, 'purchase_order_status') ? getPurchaseStatus($pur_val['purchase_order_status']) : "";
                }

                $data_list_tmp[] = $value_tmp;

                $purchase_amount_num += $value['purchase_amount'];
                $purchase_total_price_all += $value['purchase_total_price'];
            }

            $data_list = $data_list_tmp;
            unset($data_list_tmp);
        }
        $role = get_user_role();//当前登录角色
        $data_role = getRolexiao();
        $res_xiao = array_intersect($role, $data_role);
        if ($res_xiao) {
            foreach ($data_list as $key => $row) {
                $data_list[$key]['purchase_unit_price'] = "***";
                $data_list[$key]['purchase_total_price'] = "***";
                $data_list[$key]['supplier_name'] = '******';

            }
        }
        //汇总数据
        $aggregate_data = $demand_info['aggregate_data'];
        $aggregate_data['total_all'] = $demand_info['page_data']['total'];
        $aggregate_data['page_limit'] = count($data_list);
        $aggregate_data['page_sku'] = $skus_num;
        $aggregate_data['page_purchase_amount'] = $purchase_amount_num;
        $aggregate_data['page_purchase_total_price'] = sprintf("%.3f", $purchase_total_price_all);
        $this->success_json(['key' => $key_arr, 'values' => $data_list, 'drop_down_box' => $drop_down_box, 'aggregate_data' => $aggregate_data], $demand_info['page_data']);
    }

    /**
     * 查询 采购需求列表
     * @author Jolon
     */
    public function get_list_sum()
    {
        $params = $this->get_suggest_params();
        if (isset($params['group_ids']) && !empty($params['group_ids'])) {
            $params['groupdatas'] = $this->get_user_by_group($params['group_ids']);
        }

        $new = $this->input->get_post('new');
        if (!empty($new)) $params['new'] = 1;

        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) || $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $params['page'] = $page;
        $params['limit'] = query_limit_range($limit);
        $params['offset'] = ($page - 1) * $limit;
        if ($params['list_type'] == 2) {
            $demand_info = $this->purchase_demand_model->get_demanding_list($params, false, true);
        } else {
            $demand_info = $this->purchase_demand_model->get_all_demand($params, false, true);
        }
//        $demand_info = $this->purchase_suggest_model->get_list_sum($params,$offset,$limit,$page);
        //汇总数据
        $aggregate_data = $demand_info['aggregate_data'];
        $aggregate_data['total_all'] = $demand_info['page_data']['total'];
        $this->success_json(['aggregate_data' => $aggregate_data]);
    }


    /**
     * 采购需求单列表导出
     * @author Jaden
     * purchase_suggest/purchase_suggest/purchase_export
     */
    public function purchase_export()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3000M');
        $this->load->helper('export_csv');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $ids = $this->input->get_post('id');
        $list_type = $this->input->get_post('list_type');

        $params = $this->get_suggest_params();
        $params['id'] = $ids;
        $params['list_type'] = $list_type;

        if (isset($params['group_ids']) && !empty($params['group_ids'])) {
            $params['groupdatas'] = $this->get_user_by_group($params['group_ids']);
        }


        //读取配置信息
        $this->load->model('system/Reason_config_model');
        $param['status'] = 1;//启用的

        //查询记录总数
        $params['new'] = 1;
//        $demand_info = $this->purchase_suggest_model->get_list_sum($params);
        if ($params['list_type'] == 2) {
            $demand_info = $this->purchase_demand_model->get_demanding_list($params, false, true);
        } else {
            $demand_info = $this->purchase_demand_model->get_all_demand($params, false, true);
        }

        $total = $demand_info['page_data']['total'];
        unset($demand_info);

        try {
            $this->load->model('system/Data_control_config_model');
            $ext = 'csv';
            if ($total >= 150000) $this->error_json('单次导出限制，请分批导出');

            $params['role_name'] = get_user_role();
            $params['number'] = $total;

            $userid = jurisdiction(); //当前登录用户ID
            $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
            $role = get_user_role();//当前登录角色
            $data_role = getRole();//数据权限配置
            $res_arr = array_intersect($role, $data_role);

            if (!(!empty($res_arr) || $userid === true)) {
                $params['buyer_id_flag'] = true;
                if (empty($params['buyer_id'])) $params['buyer_id'] = $userid;
            } else {
                $params['buyer_id_flag'] = 0;
                if (empty($params['buyer_id'])) $params['buyer_id'] = [];
            }
            $params['user_groups_types'] = $user_groups_types;

            $result = $this->Data_control_config_model->insertDownData($params, 'SUGGESTDATA', '备货单导出', getActiveUserName(), $ext, $total);
        } catch (Exception $exp) {
            $this->error_json($exp->getMessage());
        }
        if ($result) {
            $this->success_json("添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }
    }


    /**
     * 采购需求添加备注
     * @author Jolon
     */
    public function add_sales_note()
    {
        $id = $this->input->get_post('id');
        $remark = $this->input->get_post('remark');

        // 参数错误
        if (empty($id) or empty($remark)) {
            $this->error_json('参数错误');
        }

        $result = $this->purchase_suggest_model->add_sales_note($id, $remark);
        if ($result) {
            $this->success_json();
        } else {
            $this->error_json('采购需求添加备注失败');
        }
    }

    private function verify_product_url($skus)
    {
        $this->load->library('alibaba/AliProductApi');
        if (!empty($skus)) {

            $result = $this->db->from("pur_product")->select("product_cn_link,sku")->where_in("sku", $skus)->get()->result_array();
            if (!empty($result)) {
                $error_data = [];
                foreach ($result as $key => $value) {
                    $message = $this->aliproductapi->parseProductIdByLink($value['product_cn_link']);
                    if (isset($message['code']) && $message['code'] == 1) {
                        $mess = $this->aliproductapi->getProductInfo($message['data']);
                        if ($mess['code'] != 200) {

                            $error_data[] = $value['sku'];
                        }
                    } else {
                        $error_data[] = $value['sku'];
                    }
                }

                if (!empty($error_data)) {

                    $flag = $this->db->where_in("sku", $error_data)->update('pur_product', ['is_invalid' => 1]);
                    if ($flag) {

                        // 执行推送数据
                        operatorLogInsert(
                            [
                                'type' => 'pr_product',
                                'content' => '设置连接失效',
                                'detail' => json_encode($error_data)
                            ]);

                    }
                }
            }
        }
    }

    /**
     * 根据 需求 ID 创建采购单
     *      创建采购单 国内仓 | 海外仓 | FBA
     * @author Jolon
     */
    public function create_purchase_order()
    {
        $ids = $this->input->get_post('ids');
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase_suggest/suggest_lock_model');
        $is_lock_res = $this->suggest_lock_model->validate_is_lock_time();
        if ($is_lock_res['code'] == 500) $this->error_json($is_lock_res['message']);
        if ($is_lock_res['code'] == 200 && !empty($is_lock_res['message'])) $this->error_json('锁单时间内,不能生成采购单');

        // 参数错误
        if (empty($ids)) {
            $this->error_json('参数错误');
        } else {
            $ids = query_string_to_array($ids);
        }
        $this->load->model('ali/Ali_order_advanced_load_model', 'advanced_load_model');
        $res = '默认失败';
        try {

            // 获取符合要求的 采购需求
            // 未生成采购计划 或 已生成采购计划且被采购驳回
            //$sub_query     = " ( suggest_status=1 OR (suggest_status=4 AND purchase_order_status NOT IN(1)) )";
            $query_builder = $this->db->where_in('id', $ids);
            $query_builder = $query_builder->from('pur_purchase_suggest');
            //$query_builder = $query_builder->where($sub_query);

            $suggest_list = $query_builder->get()->result_array();

            if (empty($suggest_list)) {
                throw new ErrorException('未获取到符合要求的数据');
            }

            $skus = array_column($suggest_list, "sku");
            $this->verify_product_url($skus);
            $validate_create_purchase_order = $this->purchase_suggest_model->validate_create_purchase_order($suggest_list);
            if (!$validate_create_purchase_order) {
                throw new ErrorException('存在已生成采购单，请刷新后重新选择');
            }
            $check_supplier_code = array_unique(array_column($suggest_list, 'supplier_code'));
            if (count($check_supplier_code) != 1) {
                throw new ErrorException('请选择同一供应商');
            }

            $supplier_code = current($check_supplier_code);
            $reject_info = $this->purchase_order_model->temporary_supplier_order_number($supplier_code);
            if ($reject_info) {
                throw new ErrorException(implode(',', $reject_info));
            }

            $purchase_type_id = array_unique(array_column($suggest_list, 'purchase_type_id'));
            if (count($purchase_type_id) == 1) {
                // 什么也不做
            } elseif (count($purchase_type_id) >= 2 and count($purchase_type_id) <= 4) {
                if (!empty(array_diff($purchase_type_id, [PURCHASE_TYPE_INLAND, PURCHASE_TYPE_FBA, PURCHASE_TYPE_PFB, PURCHASE_TYPE_PFH]))) {
                    throw new ErrorException('请选择同一备货单业务线的备货单或国内和FBA的备货单');
                }
            } else {
                throw new ErrorException('请选择同一备货单业务线的备货单或国内和FBA的备货单');
            }
            $check_is_include_tax = array_unique(array_column($suggest_list, 'is_include_tax'));
            if (count($check_is_include_tax) != 1) {
                throw new ErrorException('需要全部都为退税或不退税');
            }
            if (in_array($purchase_type_id[0], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) { //如果是海外线  就要判断过去时间
                $validate_suggest_status = $this->purchase_suggest_model->validate_suggest_status($suggest_list);
                if (!empty($validate_suggest_status)) {
                    throw new ErrorException('备货单号[' . $validate_suggest_status['demand_number'] . ']已过期');
                }
                $check_expiration_time = $this->purchase_suggest_model->validate_expiration_time($suggest_list);
                if (!$check_expiration_time) {
                    throw new ErrorException('过期时间需要一致');
                }
                $check_warehouse_code = array_unique(array_column($suggest_list, 'destination_warehouse'));
                $check_source_from = array_unique(array_column($suggest_list, 'source_from'));
                //            if (count($check_warehouse_code) != 1) {
                //                throw new ErrorException('目的仓不一致');
                //            }

                if (count($check_source_from) != 1) {
                    throw new ErrorException('需求单来源不一致');
                }

                //            if ($check_source_from[0]!=1 && empty($check_warehouse_code[0])) {//计划系统推送的需求单目的仓可以为空
                //               throw new ErrorException('海外目的仓不能为空');
                //            }
                //            $check_logistics_type = array_unique(array_column($suggest_list, 'logistics_type'));
                //            if (count($check_logistics_type) != 1) {
                //                throw new ErrorException('海外物流类型不一致');
                //            }

                $tar_rate_verify_data = array_map(function ($data) {
                    if (isset($data['purchase_type_id']) && !empty($data['purchase_type_id']) && in_array($data['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {
                        return $data;
                    }
                }, $suggest_list);
                if (!empty($tar_rate_verify_data)) {
                    $verify_skus = array_column($tar_rate_verify_data, "sku");
                    $sku_message = $this->product_model->get_list_by_sku($verify_skus, 'sku,maintain_ticketed_point,supplier_code');

                    $sku_message = array_map(function ($skus) {
                        if ($skus['maintain_ticketed_point'] == 0) {
                            return $skus;
                        }
                    }, $sku_message);
                }
                if (!empty($sku_message)) {
                    $sku_message_supplier = array_column($sku_message, "supplier_code");
                }

                foreach ($suggest_list as $key => $suggest_value) {
                    // 如果备货单不是海外仓，验证开票点是否为0
                    if (isset($suggest_value['purchase_type_id']) && in_array($suggest_value['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {
                        if (!empty($sku_message) && isset($sku_message[$suggest_value['sku']]) && ($sku_message[$suggest_value['sku']]['maintain_ticketed_point'] == 0)) {
                            $tax_rate_error[] = $suggest_value['sku'];
                            break;
                        }

                        if (in_array($suggest_value['supplier_code'], $sku_message_supplier)) {
                            $tax_rate_error[] = $suggest_value['sku'];
                            break;
                        }

                    }
                }

                if (!empty($tax_rate_error)) {
                    $tax_rate_error_str = implode(",", array_unique($tax_rate_error));
                    throw new ErrorException($tax_rate_error_str . "开票点为空，请维护后再点击");
                }

            }

            foreach ($suggest_list as $key => $suggest_value) {
                //获取有相同sku的备货单的sku
                $same_sku[$suggest_value['sku']][$key]['id'] = $suggest_value['id'];
                $same_sku[$suggest_value['sku']][$key]['purchase_amount'] = $suggest_value['purchase_amount'];
            }

            //将相同sku的备货单的备货数量按从高到低排序
            foreach ($same_sku as $key => &$value) {
                $purchase_amounts = [];
                foreach ($value as $kkk => $row) {
                    $purchase_amounts[$kkk] = $row['purchase_amount'];

                }
                array_multisort($purchase_amounts, SORT_DESC, $value);

                foreach ($value as $k => $v) {
                    if ($k == 0) {
                        continue;
                    }
                    foreach ($suggest_list as $kk => $suggest) {
                        //去除备货数量低的
                        if ($suggest['id'] == $v['id']) {
                            unset($suggest_list[$kk]);
                        }
                    }
                }
            }

            if (count($suggest_list) > 30) throw new ErrorException('一个 PO 限定不能超过 30 个SKU，请重新确认');

            // 创建采购单
            $this->load->model('purchase_order_model', '', true, 'purchase');
            $response = $this->purchase_order_model->create_purchase_order($suggest_list);//,$purchase_type_ids[0]

            if ($response['code']) {
                $this->success_json($response['data']);
            }
            throw new ErrorException($response['msg']);
        } catch (ErrorException $e) {
            $res = $e->getMessage();
        }
        $this->error_json($res);
    }

    /**
     * 一键生成采购单-根据查询条件
     *      国内仓 | 海外仓 | FBA 一键创建采购单（根据查询条件获取可创建采购单的需求）
     * @author Jolon
     */
    public function create_purchase_order_onekey()
    {
        $this->error_json('接口已禁用-请使用新功能');

        $params = [
            'id' => $this->input->get_post('id'), //勾选数据
            'sku' => $this->input->get_post('sku'),// SKU
            'buyer_id' => $this->input->get_post('buyer_id'),
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'is_drawback' => $this->input->get_post('is_drawback'),// 是否退税
            'product_line_id' => $this->input->get_post('product_line_id'),// 产品线
            'product_status' => $this->input->get_post('product_status'),//产品状态
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'is_new' => $this->input->get_post('is_new'),// 是否新品
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),// 业务线(1.国内,2.海外,3.FBA)
            'plan_product_arrive_time_start' => $this->input->get_post('plan_product_arrive_time_start'),// 预计断货时间-开始
            'plan_product_arrive_time_end' => $this->input->get_post('plan_product_arrive_time_end'),// 预计断货时间-结束
            'create_time_start' => $this->input->get_post('create_time_start'),// 创建时间-开始（需求时间）
            'create_time_end' => $this->input->get_post('create_time_end'),// 创建时间-结束（需求时间）
            'destination_warehouse' => $this->input->get_post('destination_warehouse'),//目的仓
            'logistics_type' => $this->input->get_post('logistics_type'),// 物流类型
            'is_expedited' => $this->input->get_post('is_expedited'),// 是否加急
            'create_user_id' => $this->input->get_post('create_user_id'),// 申请人
            'demand_type_id' => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
            'suggest_status' => SUGGEST_STATUS_NOT_FINISH,// 需求状态
            'is_create_order' => SUGGEST_ORDER_STATUS_N,// 是否生成采购单
            'entities_lock_status' => $this->input->get_post('entities_lock_status'),// 是否实单锁单
            'is_left_stock' => $this->input->get_post('is_left_stock'),// 是否欠货 1.是，2.否
            'state_type' => $this->input->get_post('state_type'),// 开发类型
            'is_scree' => $this->input->get_post('is_scree'), // 是否在屏蔽中
            'supply_status' => $this->input->get_post('supply_status'),// 货源状态
            'cancel_reason' => $this->input->get_post('cancel_reason'), // 作废原因
            'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓
        ];
        $this->load->model('purchase_suggest/suggest_lock_model');
        $is_lock_res = $this->suggest_lock_model->validate_is_lock_time();
        if ($is_lock_res['code'] == 500) $this->error_json($is_lock_res['message']);
        if ($is_lock_res['code'] == 200 && !empty($is_lock_res['message'])) $this->error_json('锁单时间内,不能生成采购单');

        // 获取采购建议列表
        $suggest_list = $this->purchase_suggest_model->get_list($params, null, null);

        if (!isset($suggest_list['data_list']) or empty($suggest_list['data_list'])) {
            $this->error_json('没有符合条件的需求单数据');
        }

        $skus = array_column($suggest_list, "sku");
        $this->verify_product_url($skus);
        $suggest_list = $suggest_list['data_list'];
        $tax_rate_error = [];
        $tax_rate_supplier_error = [];
        $sku_message = $sku_message_supplier = [];
        if ($suggest_list) {
            $tar_rate_verify_data = array_map(function ($data) {
                if (isset($data['purchase_type_id']) && !empty($data['purchase_type_id']) && in_array($data['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {
                    return $data;
                }
            }, $suggest_list);
            if (!empty($tar_rate_verify_data)) {
                $verify_skus = array_column($tar_rate_verify_data, "sku");
                $sku_message = $this->product_model->get_list_by_sku($verify_skus, 'sku,maintain_ticketed_point,supplier_code');

                $sku_message = array_map(function ($skus) {
                    if ($skus['maintain_ticketed_point'] == 0) {
                        return $skus;
                    }
                }, $sku_message);
            }
            if (!empty($sku_message)) {
                $sku_message_supplier = array_column($sku_message, "supplier_code");
            }
            foreach ($suggest_list as $key => $suggest_value) {
                // 如果备货单不是海外仓，验证开票点是否为0
                if (isset($suggest_value['purchase_type_id']) && in_array($suggest_value['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {
                    if (!empty($sku_message) && isset($sku_message[$suggest_value['sku']]) && ($sku_message[$suggest_value['sku']]['maintain_ticketed_point'] == 0)) {
                        $tax_rate_error[] = $suggest_value['sku'];
                        continue;
                    }

                    if (in_array($suggest_value['supplier_code'], $sku_message_supplier)) {
                        $tax_rate_error[] = $suggest_value['sku'];
                        continue;
                    }

                }
                $validate_create_purchase_order = $this->purchase_suggest_model->validate_create_purchase_order([$suggest_value]);
                if (!$validate_create_purchase_order) {
                    unset($suggest_list[$key]);// 存在已生成采购单 跳过
                }
                $validate_suggest_status = $this->purchase_suggest_model->validate_suggest_status([$suggest_value]);
                if (!empty($validate_suggest_status)) {
                    unset($suggest_list[$key]);// 备货单号已过期 跳过;
                }

                //获取有相同sku的备货单的sku
                $same_sku[$suggest_value['sku']][$key]['id'] = $suggest_value['id'];
                $same_sku[$suggest_value['sku']][$key]['purchase_amount'] = $suggest_value['purchase_amount'];
            }
            if (!empty($tax_rate_error)) {
                $tax_rate_error_str = implode(",", array_unique($tax_rate_error));
                $this->error_json($tax_rate_error_str . "开票点为空，请维护后再点击");
            }
        }

//        if(!empty($same_sku)) {
//            //将相同sku的备货单的备货数量按从高到低排序
//            foreach ($same_sku as $key => $sku_info) {
//                $purchase_amounts = [];
//                foreach ($sku_info as $kkk => $row) {
//                    $purchase_amounts[$kkk] = $row['purchase_amount'];
//                }
//                array_multisort($purchase_amounts, SORT_DESC, $sku_info);
//                foreach ($sku_info as $k => $v) {
//                    if ($k == 0) {
//                        continue;
//                    }
//                    foreach ($suggest_list as $kk => $suggest) {
//                        //去除备货数量低的
//                        if ($suggest['id'] == $v['id']) {
//                            unset($suggest_list[$kk]);
//                        }
//                    }
//                }
//            }
//        }

        // 创建采购单
        $this->load->model('purchase_order_model', '', true, 'purchase');
        $response = $this->purchase_order_model->create_purchase_order($suggest_list);
        if ($response['code']) {
            $this->success_json($response['data']);
        } else {
            $this->error_json($response['msg']);
        }
    }

    /**
     * 接收推送的备货单数据
     * purchase_suggest/purchase_suggest/receive_demand_data
     */
    public function receive_demand_data()
    {
        $data = $this->input->get_post('data');

        if (empty($data)) {
            $this->error_json('参数错误');
        } else {
            $data = json_decode($data, true);
        }
        $result = $this->purchase_suggest_model->receive_demand_data($data);
        echo $result;
    }

    /**
     * @desc 生成采购单预览确认页
     * @return
     * @author Jeff
     * @Date 2019/4/3 22:20
     */
    public function preview_create_purchase_order()
    {
        $ids = $this->input->get_post('ids');

        // 参数错误
        if (empty($ids)) {
            $this->error_json('参数错误');
        } else {
            $ids = query_string_to_array($ids);
        }

        $result = $this->purchase_suggest_model->get_preview_suggest_data($ids);

        if (!$result['data']) {
            $this->error_json($result['msg']);
        }

        $this->success_json($result['data']);

    }

    /**
     * @desc 需求单作废
     * @return
     * @author Jeff
     * @Date 2019/4/18 11:39
     */
    public function cancel_demand_order()
    {
        $ids = $this->input->get_post('ids');
        $cancel_reason = $this->input->get_post('cancel_reason');//作废原因
        $cancel_reason_category = $this->input->get_post('cancel_reason_category');//作废原因类别 pur_reason_config 数据

        // 参数错误
        if (empty($ids)) {
            $this->error_json('参数错误');
        } else {
            $ids = query_string_to_array($ids);
        }

//        if(empty($cancel_reason)) $this->error_json('作废原因必填');
        if (empty($cancel_reason_category)) $this->error_json('作废原因类别必选');

        $query_builder = $this->db->where_in('id', $ids);
        $query_builder = $query_builder->from('pur_purchase_suggest');

        $suggest_list = $query_builder->get()->result_array();

        //取出sku
        $skuList = array_unique(array_column($suggest_list, 'sku'));


        if (empty($suggest_list)) {
            $this->error_json('未获取到符合作废要求的数据');
        }

        $validate_create_purchase_order = $this->purchase_suggest_model->validate_create_purchase_order($suggest_list);
        if (!$validate_create_purchase_order) {
            $this->error_json('存在已生成采购单，请刷新后重新选择');
        }

        $validate_expiration = $this->purchase_suggest_model->validate_suggest_status($suggest_list);
        if (!empty($validate_expiration)) {
            $this->error_json('备货单号[' . $validate_expiration['demand_number'] . ']已过期');
        }

        $validate_cancel = $this->purchase_suggest_model->validate_cancel($suggest_list);
        if (!$validate_cancel) {
            $this->error_json('存在已作废需求单');
        }

        $result = $this->purchase_suggest_model->demand_order_cancel($ids, $cancel_reason, $suggest_list, $cancel_reason_category, $skuList);

        if (!$result['code']) {
            $this->error_json($result['msg']);
        }

        $this->success_json();
    }

    private function get_product_scree($skus, $sku)
    {

        if (empty($skus) || !in_array($sku, $skus)) {

            return 0;
        }

        return 1;
    }

    /**
     * 获取未审核需求单搜索条件
     */
    private function get_audit_list_params()
    {
        return [
            'sku' => $this->input->get_post('sku'),// SKU
            'is_drawback' => $this->input->get_post('is_drawback'),// 是否退税
            'product_line_id' => $this->input->get_post('product_line_id'),// 产品线
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'is_new' => $this->input->get_post('is_new'),// 是否新品
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),// 业务线(1.国内,2.海外,3.FBA)
            'order_by' => $this->input->get_post('order_by'),// 排序字段(1.国内,2.海外,3.FBA)
            'order' => $this->input->get_post('order'),// 排序顺序(desc,asc)
            'earliest_exhaust_date_start' => $this->input->get_post('earliest_exhaust_date_start'),// 预计断货时间-开始
            'earliest_exhaust_date_end' => $this->input->get_post('earliest_exhaust_date_end'),// 预计断货时间-结束
            'create_time_start' => $this->input->get_post('create_time_start'),// 创建时间-开始（需求时间）
            'create_time_end' => $this->input->get_post('create_time_end'),// 创建时间-结束（需求时间）
            'is_expedited' => $this->input->get_post('is_expedited'),// 是否加急
            'create_user_id' => $this->input->get_post('create_user_id'),// 申请人
            'destination_warehouse' => $this->input->get_post('destination_warehouse'),// 目的仓
            'logistics_type' => $this->input->get_post('logistics_type'),// 物流类型
            'product_status' => $this->input->get_post('product_status'),// 产品状态
            'warehouse_code' => $this->input->get_post('warehouse_code'),// 采购仓库
            'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓
            'suggest_status' => $this->input->get_post('suggest_status'),// 需求状态
            'is_boutique' => $this->input->get_post('is_boutique'),// 是否精品
            'demand_type_id' => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
            'state_type' => $this->input->get_post('state_type'),// 开发类型
            'cancel_reason' => $this->input->get_post('cancel_reason'), // 作废原因
            'estimate_time_start' => $this->input->get_post('estimate_time_start'), // 预计到货开始时间
            'estimate_time_end' => $this->input->get_post('estimate_time_end'), // 预计到货开始时间
            'is_overseas_first_order' => $this->input->get_post('is_overseas_first_order'), // 是否为海外仓首单
            'shipment_type' => $this->input->get_post('shipment_type'), // 发运类型
            'transformation' => $this->input->get_post('transformation'), // 是否国内转海外
            'group_ids' => $this->input->get_post('group_ids'), // 组别ID

        ];
    }

    /**
     * 获取未审核的需求单 aggregate_data
     */
    public function get_un_audit_list()
    {
        $params = $this->get_audit_list_params();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;

        if (isset($params['group_ids']) && !empty($params['group_ids'])) {

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if (!empty($groupids)) {
                $groupdatas = array_column($groupids, 'value');
            }

            $params['groupdatas'] = $groupdatas;
        }


        $demand_info = $this->purchase_suggest_model->get_un_audit_list($params, $offset, $limit, $page);

        $key_arr = ['备货单状态', '是否海外首单', '发运类型', '预计时间', '预计到货时间', '图片', '备货单业务线', '备货单号', 'SKU', '是否新品', '一级产品线', '二级产品线', '产品名称', '备货数量', '最小起订量', '缺货数量', '产品状态',

            '是否退税', '预计断货时间', '采购仓库', '目的仓', '创建时间', '物流类型', '是否加急', '申请人', '销售备注', '备注', '作废原因', '是否精品', '开发类型', '是否国内转海外', '操作',];
        $drop_down_box = $this->status_list(null, true);
        $drop_down_box['product_status'] = getProductStatus();

        $data_list = $demand_info['data_list'];

        $this->load->model('warehouse/Logistics_type_model');
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $logistics_type_list = array_column($logistics_type_list, 'type_name', 'type_code');

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
        $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
        $pertain_wms_list = array_column($pertain_wms_list, 'pertain_wms_name', 'warehouse_code');

        $skus = array_column($data_list, 'sku');
        //产品状态
        $product_field = 'sku,product_status';
        $sku_arr = $this->product_model->get_list_by_sku(array_unique($skus), $product_field);
        //缺货数量
        //$outofstock_sku_arr = $this->Sku_outofstock_statisitics_model->get_outofstock_total_quantity(array_unique($skus));

        $skus_num = $purchase_amount_num = 0;
        $aggregate_data = ['page_limit' => 0, 'page_sku' => 0, 'page_purchase_amount' => 0];

        if ($data_list) {
            $data_list_tmp = [];

            //增加汇总信息
            $skus = array_column($data_list, 'sku');
            $skus_num = count(array_unique($skus));//当前页SKU数量
            $skus = array_column($data_list, "sku");
            $scree_skus = $this->Product_scree_model->get_product_sku_scree($skus);

            $this->load->model('system/Reason_config_model');
            $param['status'] = 1;//启用的
            $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
            $category_list = array_column($cancel_reason_category_list['values'], 'reason_name', 'id');

            foreach ($data_list as $value) {
                $value_tmp = [];

                $value_tmp['id'] = $value['id'];
                $value_tmp['suggest_status'] = $value['suggest_status'];
                $value_tmp['is_overseas_first_order'] = $value['is_overseas_first_order'];
                if ($value['create_time'] > $value['estimate_time']) {
                    $value_tmp['estimate_time'] = NULL;
                } else {
                    $value_tmp['estimate_time'] = $value['estimate_time'];
                }
                $value_tmp['product_img_url'] = erp_sku_img_sku($value['product_img_url']);
                $value_tmp['product_jump_url'] = jump_url_product_base_info($value['sku']);
                $value_tmp['purchase_type_id'] = getPurchaseType($value['purchase_type_id']);
                $value_tmp['demand_number'] = $value['demand_number'];
                $value_tmp['sku'] = $value['sku'];
                $value_tmp['is_new'] = $value['is_new'] == 1 ? '是' : '否';
                $value_tmp['product_line_name'] = $value['product_line_name'];
                $value_tmp['two_product_line_name'] = $value['two_product_line_name'];
                $value_tmp['product_name'] = $value['product_name'];
                $value_tmp['purchase_amount'] = $value['purchase_amount'];
                $value_tmp['starting_qty'] = $value['starting_qty'];
                $value_tmp['starting_qty_unit'] = $value['starting_qty_unit'];
                //缺货数量
                $value_tmp['lack_quantity'] = $value['left_stock'] != 0 ? $value['left_stock'] : '';
                //产品状态
                $value_tmp['product_status'] = isset($sku_arr[$value['sku']]) ? getProductStatus($sku_arr[$value['sku']]['product_status']) : '';
                $value_tmp['is_drawback'] = getIsDrawback($value['is_drawback']);
                $value_tmp['earliest_exhaust_date'] = $value['earliest_exhaust_date'];
                $value_tmp['warehouse_code'] = $value['warehouse_code'];
                $value_tmp['warehouse_name'] = $value['warehouse_name'];
                $value_tmp['pertain_wms_name'] = isset($pertain_wms_list[$value['warehouse_code']]) ? $pertain_wms_list[$value['warehouse_code']] : '';
                $value_tmp['destination_warehouse'] = isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '';
                $value_tmp['create_time'] = $value['create_time'];
                $value_tmp['logistics_type'] = isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '';
                $value_tmp['is_expedited'] = getIsExpedited($value['is_expedited']);
                $value_tmp['create_user_name'] = $value['create_user_name'];//申请人

                $sales_note = explode(' ', trim($value['sales_note']));
                if (isset($sales_note[count($sales_note) - 1]) && isset($sales_note[count($sales_note) - 2]) && isset($sales_note[count($sales_note) - 3])) {
                    $sales_note_string = $sales_note[count($sales_note) - 3] . ' ' . $sales_note[count($sales_note) - 2] . ' ' . $sales_note[count($sales_note) - 1];
                } else {
                    $sales_note_string = isset($sales_note[0]) ? $sales_note[0] : '';
                }

                $value_tmp['sales_name'] = $value['sales_name'];
                $value_tmp['sales_group'] = $value['sales_group'];
                $value_tmp['sales_account'] = $value['sales_account'];
                $value_tmp['platform'] = $value['platform'];
                $value_tmp['site'] = $value['site'];
                $value_tmp['site_name'] = $value['site_name'];
                $value_tmp['sales_note'] = $sales_note_string;//销售备注
                $value_tmp['sales_note2'] = $value['sales_note2'];//销售备注2
                $value_tmp['audit_note'] = $value['audit_note'];//审核备注
                $value_tmp['is_fumigation'] = '';
                if ($value['extra_handle'] == 1) {
                    $value_tmp['is_fumigation'] = "熏蒸";
                } elseif ($value['extra_handle'] == 2) {
                    $value_tmp['is_fumigation'] = "不熏蒸";
                }//是否熏蒸

                // 29775 是否海外精品
                $value_tmp['is_oversea_boutique'] = '否';
                if (!in_array($value['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]) && $value['is_boutique'] == 1) $value_tmp['is_oversea_boutique'] = '是';

                $cancel_reason = explode(' ', trim($value['cancel_reason']));
                if (isset($cancel_reason[count($cancel_reason) - 1]) && isset($cancel_reason[count($cancel_reason) - 2])) {
                    $cancel_reason_string = $cancel_reason[count($cancel_reason) - 2] . ' ' . $cancel_reason[count($cancel_reason) - 1];
                } else {
                    $cancel_reason_string = '';
                }

                if ($value['shipment_type'] == 2) {

                    $value_tmp['shipment_type_ch'] = "中转仓发运";
                }

                if ($value['shipment_type'] == 1) {

                    $value_tmp['shipment_type_ch'] = "工厂发运";
                }

                if (empty($value['shipment_type'])) {

                    $value_tmp['shipment_type_ch'] = "";
                }

                if ($value['sku_state_type'] != 6) {

                    $value_tmp['sku_state_type_ch'] = "否";
                } else {
                    $value_tmp['sku_state_type_ch'] = "是";
                }

                $value_tmp['es_shipment_time'] = $value['es_shipment_time'];
                $value_tmp['plan_product_arrive_time'] = $value['plan_product_arrive_time'];
                $value_tmp['cancel_reason'] = $cancel_reason_string;//作废备注
                $value_tmp['cancel_reason_category'] = isset($category_list[$value['cancel_reason_category']]) ? $category_list[$value['cancel_reason_category']] : '';//作废原因类别;

                //如果需求状态为未完结，并且备货单创建时间超过7天,就自动清空

                if ($value['suggest_status'] == SUGGEST_STATUS_NOT_FINISH && $value['cancel_reason']) {
                    $cancel_sign = $this->purchase_suggest_model->get_noexpired_reason(null, $value['cancel_reason']);
                    if (empty($cancel_sign)) {
                        $value_tmp['cancel_reason'] = '';
                        $value_tmp['cancel_reason_category'] = '';

                    }
                }


                $value_tmp['is_boutique'] = getISBOUTIQUE($value['is_boutique']);
                $value_tmp['state_type'] = getProductStateType((int)$value['state_type']);
                $value_tmp['is_scree'] = $this->get_product_scree($scree_skus, $value['sku']);
                $value_tmp['product_img_url_thumbnails'] = $value['product_thumb_url'] ? erp_sku_img_sku_thumbnail($value['product_thumb_url']) : erp_sku_img_sku($value['product_img_url']);

                $purchase_amount_num += $value['purchase_amount'];

                $data_list_tmp[] = $value_tmp;

            }
            $data_list = $data_list_tmp;
            unset($data_list_tmp);

            //汇总数据
//            $aggregate_data = $demand_info['aggregate_data'];
            $aggregate_data['page_limit'] = count($data_list);
            $aggregate_data['page_sku'] = $skus_num;
            $aggregate_data['page_purchase_amount'] = $purchase_amount_num;
        }
        //汇总数据
        $this->success_json(['key' => $key_arr, 'values' => $data_list, 'drop_down_box' => $drop_down_box, 'aggregate_data' => $aggregate_data], $demand_info['page_data']);
    }

    /**
     * 未审核采购需求单列表导出
     * @author Jeff
     */
    public function un_audit_suggest_export()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->load->helper('export_csv');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $ids = $this->input->get_post('id');
        $ids_arr = explode(',', $ids);

        if (!empty($ids)) {
            $params['id'] = $ids_arr;
        } else {
            $params = $this->get_audit_list_params();
        }

        if (isset($params['group_ids']) && !empty($params['group_ids'])) {

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if (!empty($groupids)) {
                $groupdatas = array_column($groupids, 'value');
            }

            $params['groupdatas'] = $groupdatas;
        }


        $demand_info = $this->purchase_suggest_model->get_un_audit_list($params, 0, 19000, 1, true);
        $purchase_tax_list_export = $demand_info['data_list'];

        $this->load->model('warehouse/Logistics_type_model');
        $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
        $logistics_type_list = array_column($logistics_type_list, 'type_name', 'type_code');

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');

        $this->load->model('system/Reason_config_model');
        $param['status'] = 1;//启用的
        $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
        $category_list = array_column($cancel_reason_category_list['values'], 'reason_name', 'id');
        $skus = array_column($demand_info['data_list'], "sku");
        $tax_list_tmp = [];
        if ($purchase_tax_list_export) {
            $getProductStatus = getProductStatus();

            $buyerIds = array_unique(array_column($purchase_tax_list_export, "buyer_id"));
            $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
            $buyerName = array_column($buyerName, NULL, 'user_id');
            foreach ($purchase_tax_list_export as $value) {
                $now_product_info = $this->product_model->get_product_info($value['sku']);
                $now_product_status = isset($now_product_info['product_status']) ? $now_product_info['product_status'] : '';
                $now_product_status = isset($getProductStatus[$now_product_status]) ? $getProductStatus[$now_product_status] : '';

                $value_tmp['suggest_status'] = getSuggestStatus($value['suggest_status']);

                $value_tmp['plan_product_arrive_time'] = $value['plan_product_arrive_time'];

                $value_tmp['es_shipment_time'] = $value['es_shipment_time'];
                if ($value['shipment_type'] == 1) {

                    $value_tmp['shipment_type_ch'] = "工厂发运";
                }

                if ($value['shipment_type'] == 2) {

                    $value_tmp['shipment_type_ch'] = "中转仓发运";
                }
                if (empty($value['shipment_type'])) {

                    $value_tmp['shipment_type_ch'] = "";
                }

                $value_tmp['product_jump_url'] = jump_url_product_base_info($value['sku']);
                $value_tmp['purchase_type_id'] = getPurchaseType($value['purchase_type_id']);
                $value_tmp['demand_number'] = $value['demand_number'];
                $value_tmp['sku'] = $value['sku'];
                $value_tmp['is_overseas_first_order'] = ($value['is_overseas_first_order'] == 1) ? '是' : '否';
                $value_tmp['is_new'] = $value['is_new'] == 1 ? '是' : '否';
                $value_tmp['product_line_name'] = $value['product_line_name'];
                $value_tmp['two_product_line_name'] = $value['two_product_line_name'];
                $value_tmp['product_name'] = $value['product_name'];
                $value_tmp['product_status'] = $now_product_status;
                $value_tmp['purchase_amount'] = $value['purchase_amount'];
                $value_tmp['left_stock'] = $value['left_stock'];   //缺货数量
                $value_tmp['starting_qty'] = $value['starting_qty'];
                $value_tmp['starting_qty_unit'] = $value['starting_qty_unit'];
                $value_tmp['is_drawback'] = getIsDrawback($value['is_drawback']);
                $value_tmp['earliest_exhaust_date'] = $value['earliest_exhaust_date'];
//                $value_tmp['warehouse_code']           = $value['warehouse_code'];
                $value_tmp['warehouse_name'] = $value['warehouse_name'];
                $value_tmp['destination_warehouse'] = isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '';   //目的仓
                $value_tmp['logistics_type'] = isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '';   //物流类型
                $value_tmp['create_user_name'] = $value['create_user_name'];   //申请人
                $value_tmp['platform'] = $value['platform'];      //销售平台
                $value_tmp['site'] = $value['site'];            //站点
                $value_tmp['sales_group'] = $value['sales_group'];    //销售分组
                $value_tmp['sales_name'] = $value['sales_name'];      //销售名称
                $value_tmp['sales_account'] = $value['sales_account'];  //销售账号

                $sales_note = explode(' ', trim($value['sales_note']));
                if (isset($sales_note[count($sales_note) - 1]) && isset($sales_note[count($sales_note) - 2]) && isset($sales_note[count($sales_note) - 3])) {
                    $sales_note_string = $sales_note[count($sales_note) - 3] . ' ' . $sales_note[count($sales_note) - 2] . ' ' . $sales_note[count($sales_note) - 1];
                } else {
                    $sales_note_string = isset($sales_note[0]) ? $sales_note[0] : '';
                }
                $value_tmp['sales_note'] = $sales_note_string;
                $value_tmp['create_time'] = $value['create_time'];

                $cancel_reason = explode(' ', trim($value['cancel_reason']));
                if (isset($cancel_reason[count($cancel_reason) - 1]) && isset($cancel_reason[count($cancel_reason) - 2])) {
                    $cancel_reason_string = $cancel_reason[count($cancel_reason) - 2] . ' ' . $cancel_reason[count($cancel_reason) - 1];
                } else {
                    $cancel_reason_string = '';
                }

                $value_tmp['cancel_reason'] = $cancel_reason_string;
                $value_tmp['cancel_reason_category'] = isset($category_list[$value['cancel_reason_category']]) ? $category_list[$value['cancel_reason_category']] : '';//作废原因类别;);
                $value_tmp['is_boutique'] = getISBOUTIQUE($value['is_boutique']);//是否精品
                $value_tmp['state_type'] = getProductStateType((int)$value['state_type']);//开发类型
                $value_tmp['groupName'] = isset($buyerName[$value['buyer_id']]) ? $buyerName[$value['buyer_id']]['group_name'] : '';
                $tax_list_tmp[] = $value_tmp;
            }
        }
        $this->success_json($tax_list_tmp);
    }

    /**
     * @desc 审核需求单
     * @return
     * @author Jeff
     * @Date 2019/4/18 11:39
     */
    public function audit_suggest()
    {
        $ids = $this->input->get_post('ids');
        $type = $this->input->get_post('type');//审核是否通过 1.通过 1.不通过
        $audit_note = $this->input->get_post('audit_note');//审核不通过原因

        if (empty($ids)) {
            set_time_limit(0);
            ini_set('memory_limit', '1024M');
            $this->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->searchexportcacheservice->setScene($this->searchexportcacheservice::PURCHASE_SUGGEST_AUDIT_LIST_SEARCH_EXPORT)->get();

            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);

            if (empty($quick_sql)) {
                $this->error_json('请重新查询后再点击审核');
            }

            $quick_sql .= " ORDER BY  ps.id DESC LIMIT 0,6000";
//            if ($total>10000){
//                $this->error_json('审核异常,审核数据量超过10000,请筛选后再审核');
//            }
            $result = $this->purchase_suggest_model->query_quick_sql($quick_sql);
            if (!empty($result)) {
                $ids = array_column($result, 'id');
            }
        } else {
            $ids = query_string_to_array($ids);

        }

        // 参数错误
        if (empty($ids)) {
            $this->error_json('参数错误');
        }
        if (!in_array($type, [SUGGEST_AUDITED_PASS, SUGGEST_AUDITED_UN_PASS])) $this->error_json('审核状态错误');

        if ($type == SUGGEST_AUDITED_UN_PASS && empty($audit_note)) $this->error_json('审核原因必填');

        $chunk_ids = array_chunk($ids, 500);
        foreach ($chunk_ids as $ids) {
            $query_builder = $this->db->where_in('id', $ids)->where('audit_status', SUGGEST_UN_AUDIT);
            $query_builder = $query_builder->from('pur_purchase_suggest');

            $suggest_list = $query_builder->order_by('id desc')->get()->result_array();

            if (empty($suggest_list)) {
                $this->error_json('未获取到符合审核要求的数据');
            }

            $validate_audit = $this->purchase_suggest_model->validate_audit($suggest_list);
            if (!$validate_audit) {
                $this->error_json('存在已审核需求单');
            }

            $result = $this->purchase_suggest_model->audit_suggest($ids, $type, $suggest_list, $audit_note);

            if (!$result['code']) {
                $this->error_json($result['msg']);
            }

            // 审核成功的备货单ID
            $lockResult = $this->purchase_suggest_model->plan_create_entities_lock_list($ids);

            if ($lockResult['code'] == 500) {

                $this->error_json($result['message']);
            }

        }

        $this->success_json();
    }

    /**
     * 需求单导入
     * @author Jolon
     */
    public function import_suggest()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        $sku_status_type = isset($data['sku_status_type']) ? $data['sku_status_type'] : 1;
        $data = $data['data'];

        if ($data) {
            $result = $this->purchase_suggest_model->import_suggest($data, $sku_status_type);
            if ($result['code']) {
                $this->success_json();
            } else {
                $this->error_data_json($result['data'], $result['message']);
            }
        } else {
            $this->error_json('数据缺失');
        }
    }

    /**
     * 需求单变更采购员 wangliang
     */
    public function change_purchaser()
    {
        $params['id'] = $this->input->get_post('id');
        $params['buyer_id'] = $this->input->get_post('buyer_id');

        if (empty($params['id']) || !is_array($params['id'])) $this->error_json('需求单编号有误');

        if (empty($params['buyer_id'])) $this->error_json('变更后采购员不可为空');

        $result = $this->purchase_suggest_model->change_purchaser($params);

        if ($result['bool']) {
            $this->success_json([], null, '变更成功');
        } else {
            $this->error_json($result['msg']);
        }
    }

    /**
     * @desc 解锁需求单
     * @return
     * @author Jeff
     * @Date 2019/4/18 11:39
     */
    public function unlock_suggest()
    {
        $ids = $this->input->get_post('ids');

        // 参数错误
        if (empty($ids)) {
            $this->error_json('请勾选数据');
        } else {
            if ($ids != 'all') {
                $ids = query_string_to_array($ids);
            }
        }

        if (is_array($ids)) {
            $query_builder = $this->db->where_in('id', $ids)->where_in('lock_type', [LOCK_SUGGEST_ENTITIES, LOCK_SUGGEST_NOT_ENTITIES]);
        } else {
            $query_builder = $this->db->where_in('lock_type', [LOCK_SUGGEST_ENTITIES, LOCK_SUGGEST_NOT_ENTITIES]);
        }
        $query_builder = $query_builder->from('pur_purchase_suggest');

        $suggest_list = $query_builder->order_by('id desc')->get()->result_array();

        if (empty($suggest_list)) {
            $this->error_json('未获取到符合解锁要求的数据');
        }

        if ($ids == 'all') {

            if (count($suggest_list) > 2000) {

                $suggest_list_chunk = array_chunk($suggest_list, 5);

                foreach ($suggest_list_chunk as $key => $value) {
                    $idsData = array_column($value, 'id');
                    $result = $this->purchase_suggest_model->unlock_suggest($idsData, $value);
                    if (!$result['code']) {
                        $this->error_json($result['msg']);
                    }
                }
            } else {
                $idsData = array_column($suggest_list, 'id');
                $result = $this->purchase_suggest_model->unlock_suggest($idsData, $suggest_list);
                if (!$result['code']) {
                    $this->error_json($result['msg']);
                }

            }
        } else {


            $result = $this->purchase_suggest_model->unlock_suggest($ids, $suggest_list);

            if (!$result['code']) {
                $this->error_json($result['msg']);
            }
        }

        $this->success_json();
    }


    /**
     * 查询 采购需求非实体锁单列表
     * @author Jolon
     */
    public function get_not_entities_lock_list()
    {
        $params = [
            'sku' => $this->input->get_post('sku'),// SKU
            'buyer_id' => $this->input->get_post('buyer_id'),// 采购员
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'is_drawback' => $this->input->get_post('is_drawback'),// 是否退税
            'product_line_id' => $this->input->get_post('product_line_id'),// 产品线
            'purchase_order_status' => $this->input->get_post('purchase_order_status'),// 采购单状态
            'suggest_status' => $this->input->get_post('suggest_status'),// 需求状态
            'is_create_order' => $this->input->get_post('is_create_order'),// 是否生成采购单
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'is_new' => $this->input->get_post('is_new'),// 是否新品
            'is_left_stock' => $this->input->get_post('is_left_stock'),// 是否欠货 1.是，2.否
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),// 业务线(1.国内,2.海外,3.FBA)
            'order_by' => $this->input->get_post('order_by'),// 排序字段(1.国内,2.海外,3.FBA)
            'order' => $this->input->get_post('order'),// 排序顺序(desc,asc)
            'plan_product_arrive_time_start' => $this->input->get_post('plan_product_arrive_time_start'),// 预计到货时间-开始
            'plan_product_arrive_time_end' => $this->input->get_post('plan_product_arrive_time_end'),// 预计到货时间-结束
            'is_expedited' => $this->input->get_post('is_expedited'),// 是否加急
            'create_user_id' => $this->input->get_post('create_user_id'),// 创建人
            'destination_warehouse' => $this->input->get_post('destination_warehouse'),// 目的仓
            'logistics_type' => $this->input->get_post('logistics_type'),// 物流类型
            'product_status' => $this->input->get_post('product_status'),// 产品状态
            'warehouse_code' => $this->input->get_post('warehouse_code'),// 采购仓库
            'left_stock_order' => $this->input->get_post('left_stock_order'),// 缺货数量排序
            'supplier_order' => $this->input->get_post('supplier_order'),// 供应商排序
            'supply_status' => $this->input->get_post('supply_status'),// 货源状态
            'is_boutique' => $this->input->get_post('is_boutique'),// 是否精品

            // 采购需求列表
            'create_time_start' => $this->input->get_post('create_time_start'),// 创建时间-开始（需求时间）
            'create_time_end' => $this->input->get_post('create_time_end'),// 创建时间-结束（需求时间）

            'demand_type_id' => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
            'lock_type' => LOCK_SUGGEST_NOT_ENTITIES,// 非实体锁单
            'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓
        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $demand_info = $this->purchase_suggest_model->get_list($params, $offset, $limit, $page);
        $key_arr = ['操作', '备货单状态', '图片', '备货单业务线', '备货单号', 'SKU', '是否新品', '一级产品线', '二级产品线', '产品名称', '备货数量', '缺货数量', '是否缺货',
            '是否退税', '产品状态', '开票点', '单价', '总金额', '币种', '供应商', '预计断货时间', '采购仓库', '采购员', '过期时间', '创建时间',
            '关联采购单号', '采购单状态', '采购单数量', '未转在途取消数量', '备注', '到货数量', '入库数量', '取消数量', '报损数量', '作废原因', '是否加急', '目的仓', '申请人', '物流类型', '是否异常'];
        $drop_down_box = $this->status_list(null, true);
        $drop_down_box['product_status'] = getProductStatus();

        $transit_warehouse = $drop_down_box['transit_warehouse'];

        $data_list = $demand_info['data_list'];
        $skus_num = $purchase_amount_num = $purchase_total_price_all = 0;
        if ($data_list) {
            $data_list_tmp = [];

            //增加汇总信息
            $skus = array_column($data_list, 'sku');
            $skus_num = count(array_unique($skus));//当前页SKU数量
            $purchase_amount_num = 0;//当前页PCS数(采购数量)
            $purchase_total_price_all = 0.00;//当前页订单总金额

            //产品状态
            $product_field = 'sku,product_status';
            $sku_arr = $this->product_model->get_list_by_sku(array_unique($skus), $product_field);

            $this->load->model('purchase/Purchase_order_model');
            $this->load->model('warehouse/Logistics_type_model');
            $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
            $logistics_type_list = array_column($logistics_type_list, 'type_name', 'type_code');

            $this->load->model('warehouse/Warehouse_model');
            $warehouse_list = $this->Warehouse_model->get_warehouse_list();
            $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
            //缺货数量
            //$outofstock_sku_arr = $this->Sku_outofstock_statisitics_model->get_outofstock_total_quantity(array_unique($skus));
            foreach ($data_list as $value) {
                $value_tmp = [];

                $value_tmp['operator'] = '';
                $value_tmp['id'] = $value['id'];
                $value_tmp['suggest_status'] = getSuggestStatus($value['suggest_status']);
                $value_tmp['product_img_url'] = $value['product_img_url'];
                $value_tmp['product_jump_url'] = jump_url_product_base_info($value['sku']);
                $value_tmp['purchase_type_id'] = getPurchaseType($value['purchase_type_id']);
                $value_tmp['demand_number'] = $value['demand_number'];
                $value_tmp['sku'] = $value['sku'];
                $value_tmp['is_new'] = $value['sale_state'] == SKU_STATE_IS_NEW ? '是' : '否';
                $value_tmp['product_line_name'] = $value['product_line_name'];
                $value_tmp['product_name'] = $value['product_name'];
                $value_tmp['purchase_amount'] = $value['purchase_amount'];
                //缺货数量
                $value_tmp['lack_quantity'] = isset($value['left_stock']) ? $value['left_stock'] : '';
                $value_tmp['lack_quantity_status'] = intval($value_tmp['lack_quantity']) < 0 ? '欠货' : '未欠货';
                $value_tmp['is_drawback'] = getIsDrawback($value['is_drawback']);
                $value_tmp['product_status'] = isset($sku_arr[$value['sku']]) ? getProductStatus($sku_arr[$value['sku']]['product_status']) : '';
                $value_tmp['purchase_unit_price'] = $value['purchase_unit_price'];
                $value_tmp['purchase_total_price'] = $value['purchase_total_price'];
                $value_tmp['supplier_code'] = $value['supplier_code'];
                $value_tmp['supplier_name'] = $value['supplier_name'];
                $value_tmp['is_cross_border'] = $value['is_cross_border'];
                $value_tmp['plan_product_arrive_time'] = $value['plan_product_arrive_time'];
                $value_tmp['warehouse_code'] = $value['warehouse_code'];
                $value_tmp['warehouse_name'] = $value['warehouse_name'];
                $value_tmp['buyer_id'] = $value['buyer_id'];
                $value_tmp['buyer_name'] = $value['buyer_name'];
                $value_tmp['expiration_time'] = $value['expiration_time'];
                $value_tmp['create_time'] = $value['create_time'];
                $value_tmp['audit_time'] = $value['audit_time'];
                $value_tmp['two_product_line_name'] = $value['two_product_line_name'];
                $value_tmp['ticketed_point'] = $value['ticketed_point'];
                $value_tmp['supply_status'] = getProductsupplystatus($value['supply_status']);//货源状态
                $value_tmp['currency'] = CURRENCY;//币种
                // 获取 需求对应的采购单信息
                $demand_purchase_order = $this->purchase_suggest_map_model->get_purchase_order_info($value['demand_number']);
                $value_tmp['purchase_number'] = isset($demand_purchase_order['map']['purchase_number']) ? $demand_purchase_order['map']['purchase_number'] : '';
                $value_tmp['purchase_order_status'] = isset($demand_purchase_order['purchase_order']['purchase_order_status']) ? $demand_purchase_order['purchase_order']['purchase_order_status'] : '';
                $value_tmp['confirm_number'] = isset($demand_purchase_order['map']['confirm_number']) ? $demand_purchase_order['map']['confirm_number'] : '';

                if ($value_tmp['confirm_number'] === '') {
                    $value_tmp['cancel_ctq'] = '';
                } else {
                    $value_tmp['cancel_ctq'] = $value_tmp['purchase_amount'] - intval($value_tmp['confirm_number']);// 采购数量+未转在途数量=需求单数量
                    $value_tmp['cancel_ctq'] = $value_tmp['cancel_ctq'] > 0 ? $value_tmp['cancel_ctq'] : 0;
                }

                $value_tmp['purchase_order_status'] = getPurchaseStatus($value_tmp['purchase_order_status']);
                $value_tmp['sales_note'] = $value['sales_note'];
                $value_tmp['audit_note'] = $value['audit_note'];//审核备注
                $value_tmp['sales_note2'] = $value['sales_note2'];//销售备注2
                $value_tmp['cancel_reason'] = $value['cancel_reason'];

                // 获取 到货数量、入库数量、取消数量、报损数量
                $demand_map = $this->purchase_suggest_map_model->get_one(null, $value['demand_number'], true);
                if ($demand_map) {
                    $relate_qty = $this->Purchase_order_model->calculate_sku_related_quantity($demand_map['purchase_number'], $demand_map['sku']);
                }
                $value_tmp['receive_amount'] = isset($relate_qty['receive_amount']) ? $relate_qty['receive_amount'] : 0;
                $value_tmp['upselft_amount'] = isset($relate_qty['upselft_amount']) ? $relate_qty['upselft_amount'] : 0;
                $value_tmp['cancel_amount'] = isset($relate_qty['cancel_amount']) ? $relate_qty['cancel_amount'] : 0;
                $value_tmp['loss_amount'] = isset($relate_qty['loss_amount']) ? $relate_qty['loss_amount'] : 0;


                // 需求单导入-临时方案
                $value_tmp['is_expedited'] = ($value['is_expedited'] == PURCHASE_IS_EXPEDITED_Y) ? '是' : '否';
                $value_tmp['destination_warehouse'] = isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '';
                $value_tmp['create_user_name'] = $value['create_user_name'];
                $value_tmp['logistics_type'] = isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '';
                $value_tmp['is_abnormal'] = ($value['is_abnormal'] == SUGGEST_ABNORMAL_TRUE) ? '是' : '否';
                $value_tmp['is_boutique'] = getISBOUTIQUE($value['is_boutique']);
                $value_tmp['source_from'] = $value['source_from'];

                $data_list_tmp[] = $value_tmp;

                $purchase_amount_num += $value['purchase_amount'];
                $purchase_total_price_all += $value['purchase_total_price'];
            }

            $data_list = $data_list_tmp;
            unset($data_list_tmp);
        }
        $role = get_user_role();//当前登录角色
        if (in_array(SALE, $role)) {
            foreach ($data_list as $key => $row) {
                $data_list[$key]['purchase_unit_price'] = "***";
                $data_list[$key]['purchase_total_price'] = "***";
                $data_list[$key]['supplier_name'] = '******';

            }
        }
        //汇总数据
        $aggregate_data = $demand_info['aggregate_data'];
        $aggregate_data['total_all'] = $demand_info['page_data']['total'];
        $aggregate_data['page_limit'] = count($data_list);
        $aggregate_data['page_sku'] = $skus_num;
        $aggregate_data['page_purchase_amount'] = $purchase_amount_num;
        $aggregate_data['page_purchase_total_price'] = sprintf("%.3f", $purchase_total_price_all);
        $this->success_json(['key' => $key_arr, 'values' => $data_list, 'drop_down_box' => $drop_down_box, 'aggregate_data' => $aggregate_data], $demand_info['page_data']);
    }

    /**
     * 查询 采购需求非实体锁单列表
     * @author Jolon
     */
    public function get_not_entities_lock_list_sum()
    {
        $params = [
            'sku' => $this->input->get_post('sku'),// SKU
            'buyer_id' => $this->input->get_post('buyer_id'),// 采购员
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'is_drawback' => $this->input->get_post('is_drawback'),// 是否退税
            'product_line_id' => $this->input->get_post('product_line_id'),// 产品线
            'purchase_order_status' => $this->input->get_post('purchase_order_status'),// 采购单状态
            'suggest_status' => $this->input->get_post('suggest_status'),// 需求状态
            'is_create_order' => $this->input->get_post('is_create_order'),// 是否生成采购单
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'is_new' => $this->input->get_post('is_new'),// 是否新品
            'is_left_stock' => $this->input->get_post('is_left_stock'),// 是否欠货 1.是，2.否
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),// 业务线(1.国内,2.海外,3.FBA)
            'order_by' => $this->input->get_post('order_by'),// 排序字段(1.国内,2.海外,3.FBA)
            'order' => $this->input->get_post('order'),// 排序顺序(desc,asc)
            'plan_product_arrive_time_start' => $this->input->get_post('plan_product_arrive_time_start'),// 预计到货时间-开始
            'plan_product_arrive_time_end' => $this->input->get_post('plan_product_arrive_time_end'),// 预计到货时间-结束
            'is_expedited' => $this->input->get_post('is_expedited'),// 是否加急
            'create_user_id' => $this->input->get_post('create_user_id'),// 创建人
            'destination_warehouse' => $this->input->get_post('destination_warehouse'),// 目的仓
            'logistics_type' => $this->input->get_post('logistics_type'),// 物流类型
            'product_status' => $this->input->get_post('product_status'),// 产品状态
            'warehouse_code' => $this->input->get_post('warehouse_code'),// 采购仓库
            'left_stock_order' => $this->input->get_post('left_stock_order'),// 缺货数量排序
            'supplier_order' => $this->input->get_post('supplier_order'),// 供应商排序
            'supply_status' => $this->input->get_post('supply_status'),// 货源状态
            'is_boutique' => $this->input->get_post('is_boutique'),// 是否精品

            // 采购需求列表
            'create_time_start' => $this->input->get_post('create_time_start'),// 创建时间-开始（需求时间）
            'create_time_end' => $this->input->get_post('create_time_end'),// 创建时间-结束（需求时间）

            'demand_type_id' => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
            'seachuid' => $this->input->get_post('uid'),
            'lock_type' => LOCK_SUGGEST_NOT_ENTITIES,// 非实体锁单
            'new' => $this->input->get_post('new'),// 是否清除缓存
            'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓
        ];

        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $demand_info = $this->purchase_suggest_model->get_list_sum($params, $offset, $limit, $page);
        //汇总数据
        $aggregate_data = $demand_info['aggregate_data'];
        $aggregate_data['total_all'] = $demand_info['page_data']['total'];
        $this->success_json(['aggregate_data' => $aggregate_data]);
    }

    /**
     * 非实体锁单采购需求列表导出
     * @author Jaden
     * purchase_suggest/purchase_suggest/purchase_export
     */
    public function not_entities_lock_export()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3000M');
        $this->load->helper('export_csv');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $ids = $this->input->get_post('id');

        if (!empty($ids)) {
            $params['id'] = $ids;
            $params['lock_type'] = LOCK_SUGGEST_NOT_ENTITIES;// 非实体锁单

        } else {
            $params = [
                'sku' => $this->input->get_post('sku'),// SKU
                'buyer_id' => $this->input->get_post('buyer_id'),// 采购员
                'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
                'is_drawback' => $this->input->get_post('is_drawback'),// 是否退税
                'product_line_id' => $this->input->get_post('product_line_id'),// 产品线
                'purchase_order_status' => $this->input->get_post('purchase_order_status'),// 采购单状态
                'suggest_status' => $this->input->get_post('suggest_status'),// 需求状态
                'is_create_order' => $this->input->get_post('is_create_order'),// 是否生成采购单
                'demand_number' => $this->input->get_post('demand_number'),// 备货单号
                'is_new' => $this->input->get_post('is_new'),// 是否新品
                'is_left_stock' => $this->input->get_post('is_left_stock'),// 是否欠货 1.是，2.否
                'purchase_type_id' => $this->input->get_post('purchase_type_id'),// 业务线(1.国内,2.海外,3.FBA)
                'order_by' => $this->input->get_post('order_by'),// 排序字段(1.国内,2.海外,3.FBA)
                'order' => $this->input->get_post('order'),// 排序顺序(desc,asc)
                'plan_product_arrive_time_start' => $this->input->get_post('plan_product_arrive_time_start'),// 预计到货时间-开始
                'plan_product_arrive_time_end' => $this->input->get_post('plan_product_arrive_time_end'),// 预计到货时间-结束
                'product_status' => $this->input->get_post('product_status'),// 产品状态
                // 采购需求列表
                'create_time_start' => $this->input->get_post('create_time_start'),// 创建时间-开始（需求时间）
                'create_time_end' => $this->input->get_post('create_time_end'),// 创建时间-结束（需求时间）
                'create_user_id' => $this->input->get_post('create_user_id'),// 申请人
                'is_expedited' => $this->input->get_post('is_expedited'),// 是否加急
                'logistics_type' => $this->input->get_post('logistics_type'),// 物流类型
                'destination_warehouse' => $this->input->get_post('destination_warehouse'),// 目的仓
                'warehouse_code' => $this->input->get_post('warehouse_code'),// 采购仓库
                'supply_status' => $this->input->get_post('supply_status'),// 货源状态
                'is_boutique' => $this->input->get_post('is_boutique'),// 是否精品

                'demand_type_id' => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
                'lock_type' => LOCK_SUGGEST_NOT_ENTITIES,// 非实体锁单
                'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓
                'group_ids' => $this->input->get_post('group_ids'), // 组别ID
            ];
        }
        $demand_info = $this->purchase_suggest_model->get_list_export($params, 0, 1000, 1, true);
        if (isset($params['group_ids']) && !empty($params['group_ids'])) {

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if (!empty($groupids)) {
                $groupdatas = array_column($groupids, 'value');
            }

            $params['groupdatas'] = $groupdatas;
        }
        $total = $demand_info['page_data']['total'];
        $template_file = 'not_entities-' . date('YmdH_i_s') . '.csv';
        if ($total > 100000) {//单次导出限制
            $template_file = 'product.xlsx';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url = $down_host . 'download_csv/' . $template_file;
            $this->success_json($down_file_url);
        }

        //前端路径
        $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $product_file = $webfront_path . '/webfront/download_csv/' . $template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file, 'w');
        $fp = fopen($product_file, "a");
        $heads = ['备货单状态', '图片', '备货单业务线', '备货单号', 'SKU', '是否新品', '一级产品线', '二级产品线', '产品名称', '产品状态', '备货数量',
            '缺货数量', '是否缺货', '是否退税', '开票点', '单价', '总金额', '币种', '供应商', '是否跨境', '预计断货时间', '采购仓库', '目的仓', '物流类型',
            '申请人', '申请时间', '采购员', '过期时间', '审核时间',
            '关联采购单号', '采购单状态', '采购单数量', '未转在途取消数量', '备注', '作废原因', '是否异常', '平台', '站点', '销售分组', '销售名称',
            '销售账号', '销售备注', '货源状态', '是否精品', '所属小组'];

        foreach ($heads as $key => $item) {
            $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);

        if ($total >= 1) {
            $page_limit = 10000;
            $getProductStatus = getProductStatus();
            for ($i = 1; $i <= ceil($total / $page_limit); $i++) {
                $export_offset = ($i - 1) * $page_limit;
                $orders_export_info = $this->purchase_suggest_model->get_list($params, $export_offset, $page_limit, 1);
                $purchase_tax_list_export = $orders_export_info['data_list'];

                $this->load->model('warehouse/Logistics_type_model');
                $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
                $logistics_type_list = array_column($logistics_type_list, 'type_name', 'type_code');

                $this->load->model('warehouse/Warehouse_model');
                $warehouse_list = $this->Warehouse_model->get_warehouse_list();
                $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');

                $tax_list_tmp = [];
                if ($purchase_tax_list_export) {
                    $buyerIds = array_unique(array_column($purchase_tax_list_export, "buyer_id"));
                    $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
                    $buyerName = array_column($buyerName, NULL, 'user_id');
                    foreach ($purchase_tax_list_export as $value) {
                        $now_product_info = $this->product_model->get_product_info($value['sku']);
                        $now_product_status = isset($now_product_info['product_status']) ? $now_product_info['product_status'] : '';
                        $now_product_status = isset($getProductStatus[$now_product_status]) ? $getProductStatus[$now_product_status] : '';


                        $value_tmp['suggest_status'] = iconv("UTF-8", "GBK//IGNORE", getSuggestStatus($value['suggest_status']));
                        $value_tmp['product_jump_url'] = iconv("UTF-8", "GBK//IGNORE", jump_url_product_base_info($value['sku']));
                        $value_tmp['purchase_type_id'] = iconv("UTF-8", "GBK//IGNORE", getPurchaseType($value['purchase_type_id']));
                        $value_tmp['demand_number'] = iconv("UTF-8", "GBK//IGNORE", $value['demand_number']);
                        $value_tmp['sku'] = iconv("UTF-8", "GBK//IGNORE", $value['sku']);
                        $value_tmp['is_new'] = iconv("UTF-8", "GBK//IGNORE", $value['sale_state'] == SKU_STATE_IS_NEW ? '是' : '否');
                        $value_tmp['product_line_name'] = iconv("UTF-8", "GBK//IGNORE", $value['product_line_name']);
                        $value_tmp['two_product_line_name'] = iconv("UTF-8", "GBK//IGNORE", $value['two_product_line_name']);
                        $value_tmp['product_name'] = iconv("UTF-8", "GBK//IGNORE", $value['product_name']);
                        $value_tmp['product_status'] = iconv("UTF-8", "GBK//IGNORE", $now_product_status);
                        $value_tmp['purchase_amount'] = iconv("UTF-8", "GBK//IGNORE", $value['purchase_amount']);
                        $value_tmp['left_stock'] = iconv("UTF-8", "GBK//IGNORE", $value['left_stock']); //缺货数量
                        $value_tmp['left_stock_status'] = iconv("UTF-8", "GBK//IGNORE", intval($value_tmp['left_stock']) < 0 ? '是' : '否');
                        $value_tmp['is_drawback'] = iconv("UTF-8", "GBK//IGNORE", getIsDrawback($value['is_drawback']));
                        $value_tmp['ticketed_point'] = iconv("UTF-8", "GBK//IGNORE", $value['ticketed_point']);
                        $value_tmp['purchase_unit_price'] = iconv("UTF-8", "GBK//IGNORE", $value['purchase_unit_price']);
                        $value_tmp['purchase_total_price'] = iconv("UTF-8", "GBK//IGNORE", $value['purchase_total_price']);
                        $value_tmp['currency'] = CURRENCY; //币种
                        $value_tmp['supplier_name'] = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);
                        $value_tmp['is_cross_border'] = iconv("UTF-8", "GBK//IGNORE", $value['is_cross_border'] == 1 ? '是' : '否');
                        $value_tmp['plan_product_arrive_time'] = iconv("UTF-8", "GBK//IGNORE", $value['plan_product_arrive_time']);
                        $value_tmp['warehouse_name'] = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_name']);
                        $value_tmp['destination_warehouse'] = iconv("UTF-8", "GBK//IGNORE", isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '');   //目的仓
                        $value_tmp['logistics_type'] = iconv("UTF-8", "GBK//IGNORE", isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '');   //物流类型
                        $value_tmp['create_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);   //申请人
                        $value_tmp['create_time'] = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);   //申请时间
                        $value_tmp['buyer_name'] = iconv("UTF-8", "GBK//IGNORE", $value['buyer_name']);
                        $value_tmp['expiration_time'] = iconv("UTF-8", "GBK//IGNORE", $value['expiration_time']);
                        $value_tmp['audit_time'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_time']);

                        // 获取 需求对应的采购单信息
                        $demand_purchase_order = $this->purchase_suggest_map_model->get_purchase_order_info($value['demand_number']);
                        $value_tmp['purchase_number'] = iconv("UTF-8", "GBK//IGNORE", isset($demand_purchase_order['map']['purchase_number']) ? $demand_purchase_order['map']['purchase_number'] : '');
                        $value_tmp['purchase_order_status'] = iconv("UTF-8", "GBK//IGNORE", isset($demand_purchase_order['purchase_order']['purchase_order_status']) ? $demand_purchase_order['purchase_order']['purchase_order_status'] : '');
                        $value_tmp['purchase_order_status'] = iconv("UTF-8", "GBK//IGNORE", getPurchaseStatus($value_tmp['purchase_order_status']));
                        $value_tmp['confirm_number'] = iconv("UTF-8", "GBK//IGNORE", isset($demand_purchase_order['map']['confirm_number']) ? $demand_purchase_order['map']['confirm_number'] : '');

                        if ($value_tmp['confirm_number'] === '') {
                            $value_tmp['cancel_ctq'] = '';
                        } else {
                            $value_tmp['cancel_ctq'] = $value_tmp['purchase_amount'] - intval($value_tmp['confirm_number']);// 采购数量+未转在途数量=需求单数量
                            $value_tmp['cancel_ctq'] = $value_tmp['cancel_ctq'] > 0 ? $value_tmp['cancel_ctq'] : 0;
                        }

                        $value_tmp['sales_note'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_note']);
                        $value_tmp['cancel_reason'] = iconv("UTF-8", "GBK//IGNORE", $value['cancel_reason']);
                        $value_tmp['is_abnormal'] = iconv("UTF-8", "GBK//IGNORE", ($value['is_abnormal'] == SUGGEST_ABNORMAL_TRUE) ? '是' : '否');
                        $value_tmp['platform'] = iconv("UTF-8", "GBK//IGNORE", $value['platform']);
                        $value_tmp['site'] = iconv("UTF-8", "GBK//IGNORE", $value['site']);
                        $value_tmp['sales_group'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_group']);
                        $value_tmp['sales_name'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_name']);
                        $value_tmp['sales_account'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_account']);
                        $value_tmp['sales_note2'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_note2']);
                        $value_tmp['supply_status'] = iconv("UTF-8", "GBK//IGNORE", empty($value['supply_status']) ? "" : getProductsupplystatus($value['supply_status']));//货源状态
                        $value_tmp['is_boutique'] = iconv("UTF-8", "GBK//IGNORE", getISBOUTIQUE($value['is_boutique']));//是否精品
                        $value_tmp['groupName'] = isset($buyerName[$value['buyer_id']]) ? $buyerName[$value['buyer_id']]['group_name'] : '';
                        $tax_list_tmp = $value_tmp;

                        fputcsv($fp, $tax_list_tmp);

                    }
                }
                //每1万条数据就刷新缓冲区
                ob_flush();
                flush();
            }
        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url = $down_host . 'download_csv/' . $template_file;
        $this->success_json($down_file_url);

    }


    /**
     * 查询 采购需求实体锁单列表
     * @author Jolon
     */
    public function get_entities_lock_list()
    {
        $params = [
            'sku' => $this->input->get_post('sku'),// SKU
            'buyer_id' => $this->input->get_post('buyer_id'),// 采购员
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'is_drawback' => $this->input->get_post('is_drawback'),// 是否退税
            'product_line_id' => $this->input->get_post('product_line_id'),// 产品线
            'purchase_order_status' => $this->input->get_post('purchase_order_status'),// 采购单状态
            'suggest_status' => $this->input->get_post('suggest_status'),// 需求状态
            'is_create_order' => $this->input->get_post('is_create_order'),// 是否生成采购单
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'is_new' => $this->input->get_post('is_new'),// 是否新品
            'is_left_stock' => $this->input->get_post('is_left_stock'),// 是否欠货 1.是，2.否
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),// 业务线(1.国内,2.海外,3.FBA)
            'order_by' => $this->input->get_post('order_by'),// 排序字段(1.国内,2.海外,3.FBA)
            'order' => $this->input->get_post('order'),// 排序顺序(desc,asc)
            'plan_product_arrive_time_start' => $this->input->get_post('plan_product_arrive_time_start'),// 预计到货时间-开始
            'plan_product_arrive_time_end' => $this->input->get_post('plan_product_arrive_time_end'),// 预计到货时间-结束
            'is_expedited' => $this->input->get_post('is_expedited'),// 是否加急
            'create_user_id' => $this->input->get_post('create_user_id'),// 创建人
            'destination_warehouse' => $this->input->get_post('destination_warehouse'),// 目的仓
            'logistics_type' => $this->input->get_post('logistics_type'),// 物流类型
            'product_status' => $this->input->get_post('product_status'),// 产品状态
            'warehouse_code' => $this->input->get_post('warehouse_code'),// 采购仓库
            'left_stock_order' => $this->input->get_post('left_stock_order'),// 缺货数量排序
            'supplier_order' => $this->input->get_post('supplier_order'),// 供应商排序
            'supply_status' => $this->input->get_post('supply_status'),// 货源状态
            'is_boutique' => $this->input->get_post('is_boutique'),// 是否精品
            'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓


            // 采购需求列表
            'create_time_start' => $this->input->get_post('create_time_start'),// 创建时间-开始（需求时间）
            'create_time_end' => $this->input->get_post('create_time_end'),// 创建时间-结束（需求时间）

            // 'demand_type_id'        => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
            'lock_type' => LOCK_SUGGEST_ENTITIES,// 实体锁单
            'is_ticketed_point' => $this->input->get_post('ticketed_point'), // 开票点是否为空
            'is_overseas_first_order' => $this->input->get_post('is_overseas_first_order'), // 是否为海外仓首单
            'transformation' => $this->input->get_post('transformation'), // 国内转海外
            'group_ids' => $this->input->get_post('group_ids'),
            'purchase_number' => $this->input->get_post('purchase_number'),
            'is_merge' => $this->input->get_post('is_merge'), //  合单状态
            'demand_type' => $this->input->get_post('demand_type'), // 需求单类型
        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;

        if (isset($params['group_ids']) && !empty($params['group_ids'])) {

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if (!empty($groupids)) {
                $groupdatas = array_column($groupids, 'value');
            }

            $params['groupdatas'] = $groupdatas;
        }

        //$demand_info = $this->purchase_suggest_model->get_list($params,$offset,$limit,$page);
        $demand_info = $this->purchase_suggest_model->get_entities_lock_list($params, $offset, $limit, $page);

        $key_arr = ['操作', '海外仓首单', '是否新品', '备货单状态', '图片', '备货单业务线', '备货单号', 'SKU', '是否新品', '一级产品线', '二级产品线', '产品名称', '备货数量', '缺货数量', '是否缺货',

            '是否退税', '产品状态', '开票点', '单价', '总金额', '币种', '供应商', '预计断货时间', '采购仓库', '采购员', '过期时间', '创建时间',
            '关联采购单号', '采购单状态', '采购单数量', '未转在途取消数量', '备注', '到货数量', '入库数量', '取消数量', '报损数量', '作废原因', '是否加急', '目的仓', '申请人', '物流类型', '是否异常'];
        $drop_down_box = $this->status_list(null, true);
        $drop_down_box['product_status'] = getProductStatus();
        $drop_down_box['demand_types'] = $this->purchase_suggest_model->get_demand_type();
        $drop_down_box['merge'] = [1 => '正常', 2 => '合单'];

        $transit_warehouse = $drop_down_box['transit_warehouse'];

        $data_list = $demand_info['data_list'];
        $skus_num = $purchase_amount_num = $purchase_total_price_all = 0;
        if ($data_list) {
            $data_list_tmp = [];

            //增加汇总信息
            $skus = array_column($data_list, 'sku');
            $skus_num = count(array_unique($skus));//当前页SKU数量
            $purchase_amount_num = 0;//当前页PCS数(采购数量)
            $purchase_total_price_all = 0.00;//当前页订单总金额

            //产品状态
            $product_field = 'sku,product_status';
            $sku_arr = $this->product_model->get_list_by_sku(array_unique($skus), $product_field);

            $this->load->model('purchase/Purchase_order_model');
            $this->load->model('warehouse/Logistics_type_model');
            $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
            $logistics_type_list = array_column($logistics_type_list, 'type_name', 'type_code');

            $this->load->model('warehouse/Warehouse_model');
            $warehouse_list = $this->Warehouse_model->get_warehouse_list();
            $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');
            $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
            $pertain_wms_list = array_column($pertain_wms_list, 'pertain_wms_name', 'warehouse_code');

            // 新缺货数量
//            $this->load->model('product/Shortage_model');
//            $lack_map = $this->Shortage_model->get_lack_info($skus);

            //缺货数量
            //$outofstock_sku_arr = $this->Sku_outofstock_statisitics_model->get_outofstock_total_quantity(array_unique($skus));
            foreach ($data_list as $value) {
                $value_tmp = [];

                $value_tmp['operator'] = '';
                $value_tmp['id'] = $value['id'];
                $value_tmp['is_overseas_first_order'] = $value['is_overseas_first_order'];
                $value_tmp['is_new_ch'] = $value['is_new'] == 1 ? '是' : '否';
                $value_tmp['suggest_status'] = getSuggestStatus($value['suggest_status']);
                $value_tmp['product_img_url'] = $value['product_img_url'];
                $value_tmp['product_jump_url'] = jump_url_product_base_info($value['sku']);
                $value_tmp['purchase_type_id'] = getPurchaseType($value['purchase_type_id']);
                $value_tmp['demand_number'] = $value['demand_number'];
                $value_tmp['sku'] = $value['sku'];
//                $value_tmp['is_new']                   = $value['sale_state']==SKU_STATE_IS_NEW?'是':'否';
                $value_tmp['is_new'] = $value['is_new'] == 1 ? '是' : '否';
                $value_tmp['product_line_name'] = $value['product_line_name'];
                $value_tmp['product_name'] = $value['product_name'];
                $value_tmp['purchase_amount'] = $value['purchase_amount'];
                //缺货数量
                $value_tmp['lack_quantity'] = isset($value['left_stock']) ? $value['left_stock'] : '';
                // 新缺货数量
//                $value_tmp['new_lack_qty']             = in_array($value['sku'], array_keys($skus_list))?$skus_list[$value['sku']]:0;
                $value_tmp['lack_quantity_status'] = intval($value_tmp['lack_quantity']) < 0 ? '欠货' : '未欠货';
                $value_tmp['is_drawback'] = getIsDrawback($value['is_drawback']);
                $value_tmp['product_status'] = isset($sku_arr[$value['sku']]) ? getProductStatus($sku_arr[$value['sku']]['product_status']) : '';
                $value_tmp['purchase_unit_price'] = $value['purchase_unit_price'];
                $value_tmp['purchase_total_price'] = $value['purchase_total_price'];
                $value_tmp['supplier_code'] = $value['supplier_code'];
                $value_tmp['earliest_exhaust_date'] = $value['earliest_exhaust_date']; // 预计缺货时间
                $value_tmp['es_shipment_time'] = $value['es_shipment_time']; // 预计发货时间
                $value_tmp['supplier_name'] = $value['supplier_name'];
                $value_tmp['is_cross_border'] = $value['is_cross_border'];
                $value_tmp['plan_product_arrive_time'] = $value['plan_product_arrive_time'];
                $value_tmp['warehouse_code'] = $value['warehouse_code'];
                $value_tmp['warehouse_name'] = $value['warehouse_name'];
                $value_tmp['pertain_wms_name'] = isset($pertain_wms_list[$value['warehouse_code']]) ? $pertain_wms_list[$value['warehouse_code']] : '';
                $value_tmp['buyer_id'] = $value['buyer_id'];
                $value_tmp['buyer_name'] = $value['buyer_name'];
                $value_tmp['expiration_time'] = $value['expiration_time'];
                $value_tmp['create_time'] = $value['create_time'];
                $value_tmp['audit_time'] = $value['audit_time'];
                $value_tmp['two_product_line_name'] = $value['two_product_line_name'];
                $value_tmp['ticketed_point'] = $value['ticketed_point'];
                $value_tmp['supply_status'] = getProductsupplystatus($value['supply_status']);//货源状态
                $value_tmp['currency'] = CURRENCY;//币种
                // 获取 需求对应的采购单信息
                $demand_purchase_order = $this->purchase_suggest_map_model->get_purchase_order_info($value['demand_number']);
                $value_tmp['purchase_number'] = isset($demand_purchase_order['map']['purchase_number']) ? $demand_purchase_order['map']['purchase_number'] : '';
                $value_tmp['purchase_order_status'] = isset($demand_purchase_order['purchase_order']['purchase_order_status']) ? $demand_purchase_order['purchase_order']['purchase_order_status'] : '';
                $value_tmp['confirm_number'] = isset($demand_purchase_order['map']['confirm_number']) ? $demand_purchase_order['map']['confirm_number'] : '';

                if (empty($value['maintain_ticketed_point']) || $value['maintain_ticketed_point'] == 0) {
                    $value_tmp['ticketed_point'] = NULL;
                } else {
                    $value_tmp['ticketed_point'] = $value['ticketed_point'];
                }

                if ($value_tmp['confirm_number'] === '') {
                    $value_tmp['cancel_ctq'] = '';
                } else {
                    $value_tmp['cancel_ctq'] = $value_tmp['purchase_amount'] - intval($value_tmp['confirm_number']);// 采购数量+未转在途数量=需求单数量
                    $value_tmp['cancel_ctq'] = $value_tmp['cancel_ctq'] > 0 ? $value_tmp['cancel_ctq'] : 0;
                }

                $value_tmp['purchase_order_status'] = getPurchaseStatus($value_tmp['purchase_order_status']);
                $value_tmp['sales_note'] = $value['sales_note'];
                $value_tmp['sales_group'] = $value['sales_group'];
                $value_tmp['sales_account'] = $value['sales_account'];
                $value_tmp['platform'] = $value['platform'];
                $value_tmp['site'] = $value['site'];
                $value_tmp['site_name'] = $value['site_name'];
                $value_tmp['audit_note'] = $value['audit_note'];//审核备注
                $value_tmp['sales_note2'] = $value['sales_note2'];//销售备注2
                $value_tmp['cancel_reason'] = $value['cancel_reason'];

                // 获取 到货数量、入库数量、取消数量、报损数量
                $demand_map = $this->purchase_suggest_map_model->get_one(null, $value['demand_number'], true);
                if ($demand_map) {
                    $relate_qty = $this->Purchase_order_model->calculate_sku_related_quantity($demand_map['purchase_number'], $demand_map['sku']);
                }
                $value_tmp['receive_amount'] = isset($relate_qty['receive_amount']) ? $relate_qty['receive_amount'] : 0;
                $value_tmp['upselft_amount'] = isset($relate_qty['upselft_amount']) ? $relate_qty['upselft_amount'] : 0;
                $value_tmp['cancel_amount'] = isset($relate_qty['cancel_amount']) ? $relate_qty['cancel_amount'] : 0;
                $value_tmp['loss_amount'] = isset($relate_qty['loss_amount']) ? $relate_qty['loss_amount'] : 0;


                // 需求单导入-临时方案
                $value_tmp['is_expedited'] = ($value['is_expedited'] == PURCHASE_IS_EXPEDITED_Y) ? '是' : '否';
                $value_tmp['destination_warehouse'] = isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '';
                $value_tmp['create_user_name'] = $value['create_user_name'];
                $value_tmp['logistics_type'] = isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '';
                $value_tmp['is_abnormal'] = ($value['is_abnormal'] == SUGGEST_ABNORMAL_TRUE) ? '是' : '否';
                $value_tmp['is_boutique'] = getISBOUTIQUE($value['is_boutique']);
                $value_tmp['source_from'] = $value['source_from'];
                $value_tmp['product_img_url_thumbnails'] = $value['product_thumb_url'] ? erp_sku_img_sku_thumbnail($value['product_thumb_url']) : erp_sku_img_sku($value['product_img_url']);
                if ($value['sku_state_type'] != 6) {

                    $value_tmp['sku_state_type_ch'] = '否';
                } else {
                    $value_tmp['sku_state_type_ch'] = '是';
                }
                $value_tmp['demand_name'] = $value['demand_name'];
                $value_tmp['is_merge'] = $value['is_merge'];
                $data_list_tmp[] = $value_tmp;

                $purchase_amount_num += $value['purchase_amount'];
                $purchase_total_price_all += $value['purchase_total_price'];
            }

            $data_list = $data_list_tmp;
            unset($data_list_tmp);
        }
        $role = get_user_role();//当前登录角色
        if (in_array(SALE, $role)) {
            foreach ($data_list as $key => $row) {
                $data_list[$key]['purchase_unit_price'] = "***";
                $data_list[$key]['purchase_total_price'] = "***";
                $data_list[$key]['supplier_name'] = '******';

            }
        }
        //汇总数据
        $aggregate_data = $demand_info['aggregate_data'];
        $aggregate_data['total_all'] = $demand_info['page_data']['total'];
        $aggregate_data['page_limit'] = count($data_list);
        $aggregate_data['page_sku'] = $skus_num;
        $aggregate_data['page_purchase_amount'] = $purchase_amount_num;
        $aggregate_data['page_purchase_total_price'] = sprintf("%.3f", $purchase_total_price_all);
        $this->success_json(['key' => $key_arr, 'values' => $data_list, 'drop_down_box' => $drop_down_box, 'aggregate_data' => $aggregate_data], $demand_info['page_data']);
    }

    /**
     * 查询 采购需求实体锁单列表
     * @author Jolon
     */
    public function get_entities_lock_list_sum()
    {
        $params = [
            'sku' => $this->input->get_post('sku'),// SKU
            'buyer_id' => $this->input->get_post('buyer_id'),// 采购员
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
            'is_drawback' => $this->input->get_post('is_drawback'),// 是否退税
            'product_line_id' => $this->input->get_post('product_line_id'),// 产品线
            'purchase_order_status' => $this->input->get_post('purchase_order_status'),// 采购单状态
            'suggest_status' => $this->input->get_post('suggest_status'),// 需求状态
            'is_create_order' => $this->input->get_post('is_create_order'),// 是否生成采购单
            'demand_number' => $this->input->get_post('demand_number'),// 备货单号
            'is_new' => $this->input->get_post('is_new'),// 是否新品
            'is_left_stock' => $this->input->get_post('is_left_stock'),// 是否欠货 1.是，2.否
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),// 业务线(1.国内,2.海外,3.FBA)
            'order_by' => $this->input->get_post('order_by'),// 排序字段(1.国内,2.海外,3.FBA)
            'order' => $this->input->get_post('order'),// 排序顺序(desc,asc)
            'plan_product_arrive_time_start' => $this->input->get_post('plan_product_arrive_time_start'),// 预计到货时间-开始
            'plan_product_arrive_time_end' => $this->input->get_post('plan_product_arrive_time_end'),// 预计到货时间-结束
            'is_expedited' => $this->input->get_post('is_expedited'),// 是否加急
            'create_user_id' => $this->input->get_post('create_user_id'),// 创建人
            'destination_warehouse' => $this->input->get_post('destination_warehouse'),// 目的仓
            'logistics_type' => $this->input->get_post('logistics_type'),// 物流类型
            'product_status' => $this->input->get_post('product_status'),// 产品状态
            'warehouse_code' => $this->input->get_post('warehouse_code'),// 采购仓库
            'left_stock_order' => $this->input->get_post('left_stock_order'),// 缺货数量排序
            'supplier_order' => $this->input->get_post('supplier_order'),// 供应商排序
            'supply_status' => $this->input->get_post('supply_status'),// 货源状态
            'is_boutique' => $this->input->get_post('is_boutique'),// 是否精品

            // 采购需求列表
            'create_time_start' => $this->input->get_post('create_time_start'),// 创建时间-开始（需求时间）
            'create_time_end' => $this->input->get_post('create_time_end'),// 创建时间-结束（需求时间）

            // 'demand_type_id'        => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
            'seachuid' => $this->input->get_post('uid'),
            'lock_type' => LOCK_SUGGEST_ENTITIES,// 实体锁单
            'new' => $this->input->get_post('new'),// 是否清除缓存
            'is_ticketed_point' => $this->input->get_post('ticketed_point'), // 开票点是否为空

            'is_overseas_first_order' => $this->input->get_post('is_overseas_first_order'), // 是否海外仓首单

            'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓
            'transformation' => $this->input->get_post('transformation'), // 国内转海外
            'group_ids' => $this->input->get_post('group_ids'),
            'is_thousand' => $this->input->get_post('is_thousand'),
            'payment_method_source' => $this->input->get_post('payment_method_source')

        ];

        if (isset($params['group_ids']) && !empty($params['group_ids'])) {

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if (!empty($groupids)) {
                $groupdatas = array_column($groupids, 'value');
            }

            $params['groupdatas'] = $groupdatas;
        }


        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $demand_info = $this->purchase_suggest_model->get_list_sum($params, $offset, $limit, $page);
        //汇总数据
        $aggregate_data = $demand_info['aggregate_data'];
        $aggregate_data['total_all'] = $demand_info['page_data']['total'];
        $this->success_json(['aggregate_data' => $aggregate_data]);
    }

    /**
     * 实体锁单采购需求列表导出
     * @author Jaden
     * purchase_suggest/purchase_suggest/purchase_export
     */
    public function entities_lock_export()
    {
        set_time_limit(0);
        ini_set('memory_limit', '3000M');
        $this->load->helper('export_csv');
        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $ids = $this->input->get_post('id');

        if (!empty($ids)) {
            $params['id'] = $ids;
            $params['lock_type'] = LOCK_SUGGEST_ENTITIES;//实体锁单
        } else {
            $params = [
                'sku' => $this->input->get_post('sku'),// SKU
                'buyer_id' => $this->input->get_post('buyer_id'),// 采购员
                'supplier_code' => $this->input->get_post('supplier_code'),// 供应商
                'is_drawback' => $this->input->get_post('is_drawback'),// 是否退税
                'product_line_id' => $this->input->get_post('product_line_id'),// 产品线
                'purchase_order_status' => $this->input->get_post('purchase_order_status'),// 采购单状态
                'suggest_status' => $this->input->get_post('suggest_status'),// 需求状态
                'is_create_order' => $this->input->get_post('is_create_order'),// 是否生成采购单
                'demand_number' => $this->input->get_post('demand_number'),// 备货单号
                'is_new' => $this->input->get_post('is_new'),// 是否新品
                'is_left_stock' => $this->input->get_post('is_left_stock'),// 是否欠货 1.是，2.否
                'purchase_type_id' => $this->input->get_post('purchase_type_id'),// 业务线(1.国内,2.海外,3.FBA)
                'order_by' => $this->input->get_post('order_by'),// 排序字段(1.国内,2.海外,3.FBA)
                'order' => $this->input->get_post('order'),// 排序顺序(desc,asc)
                'plan_product_arrive_time_start' => $this->input->get_post('plan_product_arrive_time_start'),// 预计到货时间-开始
                'plan_product_arrive_time_end' => $this->input->get_post('plan_product_arrive_time_end'),// 预计到货时间-结束
                'product_status' => $this->input->get_post('product_status'),// 产品状态
                // 采购需求列表
                'create_time_start' => $this->input->get_post('create_time_start'),// 创建时间-开始（需求时间）
                'create_time_end' => $this->input->get_post('create_time_end'),// 创建时间-结束（需求时间）
                'create_user_id' => $this->input->get_post('create_user_id'),// 申请人
                'is_expedited' => $this->input->get_post('is_expedited'),// 是否加急
                'logistics_type' => $this->input->get_post('logistics_type'),// 物流类型
                'destination_warehouse' => $this->input->get_post('destination_warehouse'),// 目的仓
                'warehouse_code' => $this->input->get_post('warehouse_code'),// 采购仓库
                'supply_status' => $this->input->get_post('supply_status'),// 货源状态
                'is_boutique' => $this->input->get_post('is_boutique'),// 是否精品

                //'demand_type_id'        => PURCHASE_DEMAND_TYPE_PLAN,// 需求类型(计划单)
                'lock_type' => LOCK_SUGGEST_ENTITIES,// 实体锁单

                'is_overseas_first_order' => $this->input->get_post('is_overseas_first_order'),

                'pertain_wms' => $this->input->get_post('pertain_wms'),// 公共仓
            ];
        }
        $demand_info = $this->purchase_suggest_model->get_list_export($params, 0, 1000, 1, true);

        $total = $demand_info['page_data']['total'];
        $template_file = 'entities-' . date('YmdH_i_s') . '.csv';
        if ($total > 100000) {//单次导出限制
            $template_file = 'product.xlsx';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url = $down_host . 'download_csv/' . $template_file;
            $this->success_json($down_file_url);
        }

        //前端路径
        $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $product_file = $webfront_path . '/webfront/download_csv/' . $template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file, 'w');
        $fp = fopen($product_file, "a");

        $heads = ['备货单状态', '图片', '备货单业务线', '备货单号', 'SKU', '是否海外首单', '是否新品', '一级产品线', '二级产品线', '产品名称', '产品状态', '备货数量',

            '缺货数量', '是否缺货', '是否退税', '开票点', '单价', '总金额', '币种', '供应商', '是否跨境', '预计断货时间', '采购仓库', '目的仓', '物流类型',
            '申请人', '申请时间', '采购员', '过期时间', '审核时间',
            '关联采购单号', '采购单状态', '采购单数量', '未转在途取消数量', '备注', '作废原因', '是否异常', '平台', '站点', '销售分组', '销售名称', '销售账号', '销售备注', '货源状态', '是否精品'];

        foreach ($heads as $key => $item) {
            $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);

        if ($total >= 1) {
            $page_limit = 10000;
            $getProductStatus = getProductStatus();
            for ($i = 1; $i <= ceil($total / $page_limit); $i++) {
                $export_offset = ($i - 1) * $page_limit;
                $orders_export_info = $this->purchase_suggest_model->get_list($params, $export_offset, $page_limit, 1);
                $purchase_tax_list_export = $orders_export_info['data_list'];

                $this->load->model('warehouse/Logistics_type_model');
                $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
                $logistics_type_list = array_column($logistics_type_list, 'type_name', 'type_code');

                $this->load->model('warehouse/Warehouse_model');
                $warehouse_list = $this->Warehouse_model->get_warehouse_list();
                $warehouse_list = array_column($warehouse_list, 'warehouse_name', 'warehouse_code');

                $tax_list_tmp = [];
                if ($purchase_tax_list_export) {
                    foreach ($purchase_tax_list_export as $value) {
                        $now_product_info = $this->product_model->get_product_info($value['sku']);
                        $now_product_status = isset($now_product_info['product_status']) ? $now_product_info['product_status'] : '';
                        $now_product_status = isset($getProductStatus[$now_product_status]) ? $getProductStatus[$now_product_status] : '';


                        $value_tmp['suggest_status'] = iconv("UTF-8", "GBK//IGNORE", getSuggestStatus($value['suggest_status']));
                        $value_tmp['product_jump_url'] = iconv("UTF-8", "GBK//IGNORE", jump_url_product_base_info($value['sku']));
                        $value_tmp['purchase_type_id'] = iconv("UTF-8", "GBK//IGNORE", getPurchaseType($value['purchase_type_id']));
                        $value_tmp['demand_number'] = iconv("UTF-8", "GBK//IGNORE", $value['demand_number']);
                        $value_tmp['sku'] = iconv("UTF-8", "GBK//IGNORE", $value['sku']);
                        $value_tmp['is_overseas_first_order'] = iconv("UTF-8", "GBK//IGNORE", $value['is_overseas_first_order'] == 1 ? '是' : '否');
                        $value_tmp['is_new'] = iconv("UTF-8", "GBK//IGNORE", $value['is_new'] == PRODUCT_SKU_IS_NEW ? '是' : '否');
                        $value_tmp['product_line_name'] = iconv("UTF-8", "GBK//IGNORE", $value['product_line_name']);
                        $value_tmp['two_product_line_name'] = iconv("UTF-8", "GBK//IGNORE", $value['two_product_line_name']);
                        $value_tmp['product_name'] = iconv("UTF-8", "GBK//IGNORE", $value['product_name']);
                        $value_tmp['product_status'] = iconv("UTF-8", "GBK//IGNORE", $now_product_status);
                        $value_tmp['purchase_amount'] = iconv("UTF-8", "GBK//IGNORE", $value['purchase_amount']);
                        $value_tmp['left_stock'] = iconv("UTF-8", "GBK//IGNORE", $value['left_stock']); //缺货数量
                        $value_tmp['left_stock_status'] = iconv("UTF-8", "GBK//IGNORE", intval($value_tmp['left_stock']) < 0 ? '是' : '否');
                        $value_tmp['is_drawback'] = iconv("UTF-8", "GBK//IGNORE", getIsDrawback($value['is_drawback']));
                        $value_tmp['ticketed_point'] = iconv("UTF-8", "GBK//IGNORE", $value['ticketed_point']);
                        $value_tmp['purchase_unit_price'] = iconv("UTF-8", "GBK//IGNORE", $value['purchase_unit_price']);
                        $value_tmp['purchase_total_price'] = iconv("UTF-8", "GBK//IGNORE", $value['purchase_total_price']);
                        $value_tmp['currency'] = CURRENCY; //币种
                        $value_tmp['supplier_name'] = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);
                        $value_tmp['is_cross_border'] = iconv("UTF-8", "GBK//IGNORE", $value['is_cross_border'] == 1 ? '是' : '否');
                        $value_tmp['plan_product_arrive_time'] = iconv("UTF-8", "GBK//IGNORE", $value['plan_product_arrive_time']);
                        $value_tmp['warehouse_name'] = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_name']);
                        $value_tmp['destination_warehouse'] = iconv("UTF-8", "GBK//IGNORE", isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '');   //目的仓
                        $value_tmp['logistics_type'] = iconv("UTF-8", "GBK//IGNORE", isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '');   //物流类型
                        $value_tmp['create_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);   //申请人
                        $value_tmp['create_time'] = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);   //申请时间
                        $value_tmp['buyer_name'] = iconv("UTF-8", "GBK//IGNORE", $value['buyer_name']);
                        $value_tmp['expiration_time'] = iconv("UTF-8", "GBK//IGNORE", $value['expiration_time']);
                        $value_tmp['audit_time'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_time']);

                        // 获取 需求对应的采购单信息
                        $demand_purchase_order = $this->purchase_suggest_map_model->get_purchase_order_info($value['demand_number']);
                        $value_tmp['purchase_number'] = iconv("UTF-8", "GBK//IGNORE", isset($demand_purchase_order['map']['purchase_number']) ? $demand_purchase_order['map']['purchase_number'] : '');
                        $value_tmp['purchase_order_status'] = iconv("UTF-8", "GBK//IGNORE", isset($demand_purchase_order['purchase_order']['purchase_order_status']) ? $demand_purchase_order['purchase_order']['purchase_order_status'] : '');
                        $value_tmp['purchase_order_status'] = iconv("UTF-8", "GBK//IGNORE", getPurchaseStatus($value_tmp['purchase_order_status']));
                        $value_tmp['confirm_number'] = iconv("UTF-8", "GBK//IGNORE", isset($demand_purchase_order['map']['confirm_number']) ? $demand_purchase_order['map']['confirm_number'] : '');

                        if ($value_tmp['confirm_number'] === '') {
                            $value_tmp['cancel_ctq'] = '';
                        } else {
                            $value_tmp['cancel_ctq'] = $value_tmp['purchase_amount'] - intval($value_tmp['confirm_number']);// 采购数量+未转在途数量=需求单数量
                            $value_tmp['cancel_ctq'] = $value_tmp['cancel_ctq'] > 0 ? $value_tmp['cancel_ctq'] : 0;
                        }

                        $value_tmp['sales_note'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_note']);
                        $value_tmp['cancel_reason'] = iconv("UTF-8", "GBK//IGNORE", $value['cancel_reason']);
                        $value_tmp['is_abnormal'] = iconv("UTF-8", "GBK//IGNORE", ($value['is_abnormal'] == SUGGEST_ABNORMAL_TRUE) ? '是' : '否');
                        $value_tmp['platform'] = iconv("UTF-8", "GBK//IGNORE", $value['platform']);
                        $value_tmp['site'] = iconv("UTF-8", "GBK//IGNORE", $value['site']);
                        $value_tmp['sales_group'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_group']);
                        $value_tmp['sales_name'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_name']);
                        $value_tmp['sales_account'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_account']);
                        $value_tmp['sales_note2'] = iconv("UTF-8", "GBK//IGNORE", $value['sales_note2']);
                        $value_tmp['supply_status'] = iconv("UTF-8", "GBK//IGNORE", empty($value['supply_status']) ? "" : getProductsupplystatus($value['supply_status']));//货源状态
                        $value_tmp['is_boutique'] = iconv("UTF-8", "GBK//IGNORE", getISBOUTIQUE($value['is_boutique']));//是否精品
                        $tax_list_tmp = $value_tmp;

                        fputcsv($fp, $tax_list_tmp);

                    }
                }
                //每1万条数据就刷新缓冲区
                ob_flush();
                flush();
            }
        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url = $down_host . 'download_csv/' . $template_file;
        $this->success_json($down_file_url);

    }

    /**
     * 需求单导入
     * @author Jolon
     */
    public function import_change_suggest()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);
        $data = $data['data'];

        if ($data) {
            $result = $this->purchase_suggest_model->import_change_suggest($data);
            if ($result['code']) {
                $this->success_json([], null, $result['message']);
            } else {
                $this->error_data_json($result['data'], $result['message']);
            }
        } else {
            $this->error_json('数据缺失');
        }
    }

    /**
     * @desc 删除备注或作废原因
     * @return
     * @author Jeff
     * @Date 2019/8/30 13:57
     */
    public function delete_sales_note()
    {
        $id = $this->input->get_post('id');
        $note_type = $this->input->get_post('note_type');//1.删除备注,2.作废原因

        if (empty($id)) $this->error_json('id缺失');
        if (empty($note_type)) $this->error_json('类型缺失');

        $result = $this->purchase_suggest_model->delete_sales_note($id, $note_type);
        if ($result) {
            $this->success_json();
        } else {
            $this->error_json('删除失败');
        }
    }

    /**
     * @desc 需求单作废确认
     * @return
     * @author Jeff
     * @Date 2019/4/18 11:39
     */
    public function demand_order_cancel_confirm()
    {
        $ids = $this->input->get_post('ids');

        // 参数错误
        if (empty($ids)) {
            $this->error_json('参数错误');
        } else {
            $ids = query_string_to_array($ids);
        }

        $query_builder = $this->db->where_in('id', $ids);
        $query_builder = $query_builder->from('pur_purchase_suggest');

        $suggest_list = $query_builder->get()->result_array();

        if (empty($suggest_list)) {
            $this->error_json('未获取到符合作废要求的数据');
        }

        $validate_create_purchase_order = $this->purchase_suggest_model->validate_create_purchase_order($suggest_list);
        if (!$validate_create_purchase_order) {
            $this->error_json('存在已生成采购单，请刷新后重新选择');
        }

        $validate_expiration = $this->purchase_suggest_model->validate_suggest_status($suggest_list);
        if (!empty($validate_expiration)) {
            $this->error_json('备货单号[' . $validate_expiration['demand_number'] . ']已过期');
        }

        $validate_cancel = $this->purchase_suggest_model->validate_cancel($suggest_list);
        if (!$validate_cancel) {
            $this->error_json('存在已作废需求单');
        }

        $validate_order_cancel = $this->purchase_suggest_model->validate_order_cancel($suggest_list);
        if (!empty($validate_order_cancel)) {
            $this->error_json('备货单号[' . $validate_order_cancel['demand_number'] . ']关联的po未作废，无法进行二次确认，请重新选择');
        }

        $result = $this->purchase_suggest_model->demand_order_cancel_confirm($ids, $suggest_list);

        if (!$result['code']) {
            $this->error_json($result['msg']);
        }

        $this->success_json();
    }


    //海外仓金额显示
    public function oversea_refund_list()
    {
        $data_info = $this->purchase_suggest_model->purchase_db->select('amount')->get('oversea_refund_rule')->row_array();
        $amount = empty($data_info['amount']) ? '' : $data_info['amount'];
        $this->success_json($amount);


    }

    //海外仓金额设置
    public function oversea_refund_set()
    {
        $msg = '';
        $flag = null;//是否新增成功
        $amount = (int)$this->input->get_post('amount');
        if (!is_numeric($amount)) $this->error_json('请输入数字');
        if ($amount <= 0) $this->error_json('请输入正整数');

        //查询是否有记录，没有则新增
        $data_info = $this->purchase_suggest_model->purchase_db->select('*')->get('oversea_refund_rule')->row_array();

        if (!empty($data_info)) {
            $msg = '更新';
            $data = array('amount' => $amount, 'opr_user' => getActiveUserName(),
                'opr_time' => date("Y-m-d H:i:s", time()));
            $flag = $this->purchase_suggest_model->purchase_db->update('oversea_refund_rule', $data);

        } else {
            $msg = '新增';
            $data = array('amount' => $amount, 'create_user' => getActiveUserName(), 'create_time' => date("Y-m-d H:i:s", time()));
            $flag = $this->purchase_suggest_model->purchase_db->insert('oversea_refund_rule', $data);

        }

        if ($flag) {
            $this->success_json($msg . '成功');


        } else {
            $this->error_json($msg . '失败');


        }

    }

    /**
     * 30696 一键合单(2)需求单页面新增功能"一键转为备货单","一键合单"
     * 1.一键转为备货单
     * 1).无勾选时,根据条件搜索结果进行操作;有勾选时,只对勾选项进行操作,需要弹出确认窗口
     * 2).点击"确认"后,需求单变为备货单维度进入"备货单"页面,生成新的备货单号,新的备货单号生成规则=需求单号+2位顺序码,例:RD79028600
     * ,备货单业务线=需求单业务线,备货数量=需求数量;页面其他字段都按现有逻辑不变,备货单的"合单状态"变为"正常"
     * 3).同时"全部需求单"页面,需求单状态变为"已生成备货单",
     * 4).进度加入"消息-数据处理进度"页面展示,
     * @author:luxu
     * @time:2021年3月3号
     **/
    public function transferToStandbyOrder()
    {

        try {

            $clientDatas = []; // 前端查询数据条件缓存
            // 如果前端只传递ID，就只处理ID 需求单数据
            if (isset($_POST['ids']) && !empty($_POST['ids'])) {

                $clientDatas['ids'] = $this->input->get_post('ids');
            } else {
                // 一次性获取前端传递数据
                foreach ($_POST as $clientkey => $clientvalue) {

                    $values = $this->input->get_post($clientkey);
                    if (is_array($values) && isset($values[0]) && empty($values[0])) {

                        continue;
                    }
                    $clientDatas[$clientkey] = $this->input->get_post($clientkey);
                }
                if (isset($clientDatas['group_ids']) && !empty($clientDatas['group_ids'])) {

                    $this->load->model('user/User_group_model', 'User_group_model');
                    $groupids = $this->User_group_model->getGroupPersonData($clientDatas['group_ids']);
                    $groupdatas = [];
                    if (!empty($groupids)) {
                        $groupdatas = array_column($groupids, 'value');
                    }

                    $clientDatas['groupdatas'] = $groupdatas;

                }
            }
            $clientDatas['demand_lock'] = 2; //查询未锁单数据
            $result = $this->purchase_suggest_model->get_demand_datas($clientDatas);
            if (!empty($result['values'])) {
                $suggestResult = $this->purchase_suggest_model->transferToStandbyOrder($result['values']);
                if ($suggestResult == true) {

                    // 同时"全部需求单"页面,需求单状态变为"已生成备货单",
                    $demandIds = array_column($result['values'], 'id');
                    $this->purchase_suggest_model->updateDemandStatus($demandIds, $status = ['demand_status' => 3]);
                    $this->success_json();
                }
            }

            throw new Exception("未查询到数据");
        } catch (Exception $exp) {

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取前端传入的HTTP参数数据
     * @author:luxu
     * @time:2021年3月6号
     **/

    private function get_client_data()
    {
        $clientData = array_merge($_POST, $_GET);
        if (!empty($clientData)) {
            $clientDatas = []; // 前端查询数据条件缓存
            // 如果前端只传递ID，就只处理ID 需求单数据
            if (isset($_POST['ids']) && !empty($_POST['ids'])) {

                $clientDatas['ids'] = $this->input->get_post('ids');
            } else {
                // 一次性获取前端传递数据
                foreach ($_POST as $clientkey => $clientvalue) {
                    $values = $this->input->get_post($clientkey);
                    if (is_array($values) && isset($values[0]) && empty($values[0])) {

                        continue;
                    }
                    $clientDatas[$clientkey] = $this->input->get_post($clientkey);
                }
                if (isset($clientDatas['group_ids']) && !empty($clientDatas['group_ids'])) {
                    $this->load->model('user/User_group_model', 'User_group_model');
                    $groupids = $this->User_group_model->getGroupPersonData($clientDatas['group_ids']);
                    $groupdatas = [];
                    if (!empty($groupids)) {
                        $groupdatas = array_column($groupids, 'value');
                    }
                    $clientDatas['groupdatas'] = $groupdatas;
                }
            }
            return $clientDatas;
        }
        return [];
    }

    /**
     * 30696 一键合单(2)需求单页面新增功能"一键转为备货单","一键合单"
     *  1).需求单业务线=国内/FBA/PFB/平台头程,需求单状态=未完结,待重新确认
     * 2).SKU是否重复=重复,公共仓一致,发运类型一致,是否退税一致
     * 3).无勾选时,根据条件搜索结果进行操作;有勾选时,只对勾选项进行操作
     * @author:luxu
     * @time:2021年3月3号
     **/
    public function mergetransferToStandbyOrder()
    {
        try {
            // 统一获取HTTP 传入参数
            $clientDatas = $this->get_client_data();
            if (empty($clientDatas)) {
                throw new Exception("请传入查询数据");
            }
            /**
             * 业务方是否需求把需求单合单和转换为备货单一起操作标识,如果需求flag 不为空并且等于TRUE
             **/
            $clientFlag = $this->input->get_post('flag');
            $clientDatas['demand_lock'] = DEMAND_SKU_STATUS_NO_LOCK; //查询未锁单数据
            $clientDatas['demand_status'] = [SUGGEST_STATUS_NOT_FINISH, DEMAND_SKU_STATUS_CONFIR]; // 获取需求单未完结并且重新确认
            // 查询需求单数据
            $result = $this->purchase_suggest_model->get_demand_datas($clientDatas);
            if (empty($result['values'])) {
                $this->success_json("未查询到需求单信息");
            }

            /**
             * 判断是否符合合单条件
             * 1).需求单业务线=国内/FBA/PFB/平台头程,需求单状态=未完结,待重新确认
             * 2).SKU是否重复=重复,公共仓一致,发运类型一致,是否退税一致
             * 3).无勾选时,根据条件搜索结果进行操作;有勾选时,只对勾选项进行操作
             **/
            $nodata = [];   // 验证不通过数据
            foreach ($result['values'] as $valuesKey => $valueData) {
                // 需求单业务线=国内/FBA/PFB/平台头程,需求单状态=未完结,待重新确认
                if (!in_array($valueData['purchase_type_id'],
                        [PURCHASE_TYPE_INLAND, PURCHASE_TYPE_FBA, PURCHASE_TYPE_PFB, PURCHASE_TYPE_PFH])
                    && !in_array($valueData['demand_status'], [SUGGEST_STATUS_NOT_FINISH, DEMAND_SKU_STATUS_CONFIR])
                ) {
                    $nodata[] = $valueData['demand_number'];
                }

                if (!in_array($valueData['purchase_type_id'],
                    [PURCHASE_TYPE_INLAND, PURCHASE_TYPE_FBA, PURCHASE_TYPE_PFB, PURCHASE_TYPE_PFH])) {
                    throw new Exception("业务线为海外仓库的需求单不能参与合单");
                }
                // 在做一次过滤，方便测试使用
//                if(!in_array($valueData['demand_status'],[SUGGEST_STATUS_NOT_FINISH,DEMAND_SKU_STATUS_CONFIR])){
//                    $nodata[] = $valueData['demand_number'];
//                }
                //SKU是否重复=重复,公共仓一致,发运类型一致,是否退税一致
                if ($valueData['demand_repeat'] == DEMAND_SKU_NO_REPEAT && count($result['values']) > 1) {
                    $nodata[] = $valueData['demand_number'];
                } else {
                    // 如果SKU 重复，判断公共仓一致,发运类型一致,是否退税一致
//                    $skuDemands = $this->purchase_suggest_model->get_sku_demand([$valueData['sku']],"sku,warehouse_code,shipment_type,is_drawback",
//                        [   'demand_repeat'=>1,
//                            'demand_status'=>[SUGGEST_STATUS_NOT_FINISH, DEMAND_SKU_STATUS_CONFIR],
//                             'demand_lock'=>DEMAND_SKU_STATUS_NO_LOCK,
//                            'id'=>(isset($clientDatas['ids']) && !empty($clientDatas['ids']))?$clientDatas['ids']:NULL]);
//
//
//
//                    $warehouse_codes = array_unique(array_column($skuDemands,'warehouse_code')); // 获取仓库信息
//                    $shipment_type = array_unique(array_column($skuDemands,'shipment_type')); // 获取发运类型
//                    $is_drawback = array_unique(array_column($skuDemands,'is_drawback')); // 获取是否退税
//
//                    if(count($warehouse_codes) >1 || count($shipment_type)>1 || count($is_drawback)>1){
//                        $nodata[] = $valueData['demand_number'];
//                    }
                }
            }
            if (empty($clientFlag)) {
                if (!empty($nodata)) {
                    $returnnoddata = "共有:" . count($nodata) . " 条需求单,不满足合单条件:需求单业务线=国内/FBA/PFB/平台头程,需求单状态=未完结,待重新确认,SKU是否重复=重复,采购仓库一致,公共仓一致,采购主体一致,发运类型一致;是否继续合单?且将上述需求单自动转为备货单?";
                    throw new Exception($returnnoddata);
                }
            }
            //合单逻辑
            $mereDatas = $nosuggestdata = [];
            foreach ($result['values'] as $merekey => $merevalue) {
                if (!in_array($merevalue['demand_number'], $nodata)) {
                    $mereDatas[] = $merevalue;
                } else {
                    $nosuggestdata[] = $merevalue;
                }
            }
            // 如果有符合合并条件的需求单开始合并
            if (!empty($mereDatas)) {
                // 开启REDIS进程事务锁
                $lock_flag = $this->Purchase_demand_lock->pull_set_lock(); // 获取到REDIS进程锁
                if (False == $lock_flag) {
                    $this->error_json("后台进程正在合并需求单,请稍后在重试");
                }
                // 开启事务
                $this->purchase_suggest_model->purchase_db->trans_begin();
                $results = True;
                if (!empty($mereDatas)) {
                    $results = $this->purchase_suggest_model->mereSuggest($mereDatas);
                }
                $suggestresultflag = True;
                // 当用户选择了确定，并且有转备货单数据时
                if (NULL != $clientFlag && $clientFlag == true && !empty($nodata)) {
                    $clientDatas = [];
                    $clientDatas['demand_number'] = $nodata;
                    $clientData['demand_repeat'] = 2; // 不重复

                    $suggestresult = $this->purchase_suggest_model->get_demand_datas($clientDatas);
                    if (!empty($suggestresult['values'])) {
                        $suggestresultflag = $this->purchase_suggest_model->transferToStandbyOrder($suggestresult['values']);
                    }
                }
                //echo "results=".$results."--- suggestresultflag=".$suggestresultflag;die();
                if (True == $results && True == $suggestresultflag) {
                    // 如果合单成功就更新状态
                    $mereDatasIds = !empty($mereDatas) ? array_column($mereDatas, 'id') : [];
                    if (NULL != $clientFlag && $clientFlag == true && !empty($nodata)) {
                        if (isset($suggestresult) && !empty($suggestresult['values'])) {
                            $nodataid = array_column($suggestresult['values'], "id");
                            $mereDatasIds = array_merge($mereDatasIds, $nodataid);
                        }
                    }
                    $this->purchase_suggest_model->update_demand(['id' => $mereDatasIds], ['demand_status' => 3], true);
                    // 合单成功。解锁
                    $this->purchase_suggest_model->purchase_db->trans_commit();
                    $this->Purchase_demand_lock->unlock();
                    $this->success_json("需求单合单成功");
                } else {
                    throw new Exception("需求合单失败", 300);
                }
            }
        } catch (Exception $exp) {
            if ($exp->getCode() == 300) {
                $this->purchase_suggest_model->trans_rollback();
            }
            $this->Purchase_demand_lock->unlock();
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 需求单锁定 30696 一键合单(2)需求单页面新增功能"一键转为备货单","一键合单" #2
     * 需求锁定:需求锁定的前提条件:业务线=海外
     * 1).点击"需求锁定"后, 弹出窗口由用户选择解锁日期,日期只能选择当前时间+365天内的日期
     * 2).可以勾选多个需求单同时操作,
     * 3).点击确认时,验证:业务线=海外,解锁时间大于当前日期,否则报错:"不满足锁定条件:业务线=海外,解锁时间大于当前日期,请重新确认!"
     * 4).点击确认验证通过后,需求单的"需求锁定"变为"是",未到达解锁日期时,该需求单无法生成备货单,报错提示:"需求单******未达到解锁时间,请重新确认"
     * 5).锁定状态下,到达解锁日期时,自动解锁,可以正常生成备货单
     * @author:luxu
     * @time:2021年3月4号
     **/
    public function demand_lock()
    {

        try {

            $clientDatas = $this->input->get_post('datas');
            if (empty($clientDatas)) {

                throw new Exception("请正确传入参数");
            }

            $clientDatas = json_decode($clientDatas, True);
            $ids = array_column($clientDatas, "id");
            // 开始验证需求单是否满足锁单条件
            //print_r($ids);die();
            $clientDatas['ids'] = $ids;
            $result = $this->purchase_suggest_model->get_demand_datas($clientDatas);
            if (empty($result['values'])) {
                throw new Exception("未查询到锁单数据");
            }

            $errorDatas = $errorlock = [];
            $end_lock = date('Y-m-d', strtotime('+365day'));

            foreach ($clientDatas as $clientKey => $clientValue) {

                if ($clientValue['lock_time'] > $end_lock) {

                    $errorlock[] = $clientValue['demand_number'];
                }
            }

            if (!empty($errorlock)) {

                throw new Exception("需求单:" . implode(",", $errorlock) . ",日期只能选择当前时间+365天内的日期");
            }
            //print_r($result['values']);die();
            foreach ($result['values'] as $key => $value) {

                //需求锁定的前提条件:业务线=海外
                if ($value['purchase_type_id'] != PURCHASE_TYPE_OVERSEA) {

                    $errorDatas[] = $value['demand_number'];
                }
            }

            if (!empty($errorDatas)) {

                throw new Exception("需求单:" . implode(",", $errorDatas) . ",不满足锁定条件:业务线=海外");
            }

            $updateerror = [];
            foreach ($clientDatas as $clientData_key => $clientData_value) {

                $result = $this->purchase_suggest_model->update_demand(['id' => $clientData_value['id']], ['over_lock_time' => $clientData_value['lock_time'], 'demand_lock' => 1]);
                if (!$result) {
                    $updateerror[] = $clientData_value['demand_number'];
                }
            }
            // 更新需求单解锁时间和是否锁单

            if (!empty($updateerror)) {

                throw new Exception("需求单:" . implode(",", $updateerror) . "，锁单失败");
            }
            $this->success_json();
        } catch (Exception $exp) {

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 需求：30696 一键合单(2)需求单页面新增功能"一键转为备货单","一键合单" #2
     * 4.解锁需求:
     * 1).点击"解锁需求"后,解除需求单锁定状态,需求单的"需求锁定"变为否,可以正常生成备货单,无论有没有到达解锁日期,
     * 2).未勾选时,针对搜索结果操作,有勾选时,针对勾选结果操作
     * @author：luxu
     * @time:2021年3月4号
     **/

    public function deadline_lock()
    {

        try {
            $clientDatas = []; // 前端查询数据条件缓存
            // 一次性获取前端传递数据
            if (!isset($_POST['id']) && !empty($_POST['id'])) {
                foreach ($_POST as $clientkey => $clientvalue) {

                    $values = $this->input->get_post($clientkey);
                    if (is_array($values) && isset($values[0]) && empty($values[0]) && $clientkey != 'product_status') {

                        continue;
                    }

                    $clientDatas[$clientkey] = $this->input->get_post($clientkey);
                }
            } else {

                $clientDatas['id'] = $this->input->get_post('id');
            }
            $clientDatas['demand_lock'] = 1; // 需求单锁单
            $result = $this->purchase_suggest_model->get_demand_datas($clientDatas);
            if (isset($result['values']) && empty($result['values'])) {

                $this->error_json("未查询到需要解锁数据");
            }

            $demandIds = array_column($result['values'], "id");

            $result = $this->purchase_suggest_model->update_demand(['id' => $demandIds], ['demand_lock' => 2], True);

            if (!$result) {

                throw new Exception("解锁失败");
            }
            $this->success_json("解锁成功");
        } catch (Exception $exp) {

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取需求单配置信息 30708 一键合单(9)基础配置增加备货单自动生成
     * @param
     * @author:luxu
     **/
    public function get_demand_config()
    {

        $result = $this->purchase_suggest_model->get_demand_config();
        if (!empty($result)) {

            foreach ($result as $key => &$value) {

                if ($value['status'] == 0) {

                    $value['status_ch'] = "禁用";
                } else {
                    $value['status_ch'] = "启用";
                }
            }
        }
        $this->success_json($result);
    }

    public function save_demand_config()
    {

        $ids = $this->input->get_post('id');
        $status = $this->input->get_post('status');

        $result = $this->purchase_suggest_model->save_demand_cofig($ids, $status);
        if ($result) {
            $this->success_json("修改成功");
        }
        $this->error_json("修改失败");
    }

    /**
     * 作废需求单
     * @author:luxu
     * @time:2021年9月3号
     **/

    public function del_purchase_demand()
    {

        try {
            $ids = $this->input->get_post('id');
            $status = $this->input->get_post('status');
            $flag = $this->input->get_post('flag');
            $cancel_reason = $this->input->get_post('cancel_reason');
            $cancel_reason_category = $this->input->get_post('cancel_reason_category');
            if (empty($cancel_reason_category)) {

                throw new Exception("请选择作废原因");
            }

            $demandDatas = $this->purchase_suggest_model->del_purchase_demand($ids, ['demand_lock' => 1]);
            if (!empty($demandDatas)) {
                $demandNumbers = array_column($demandDatas, "demand_number");
                $msg = "需求单号:" . implode(",", $demandNumbers) . ",为锁定状态。请确认是否需要作废";
                $lockIds = array_column($demandDatas, "id");
                $ids = array_diff($ids, $lockIds);
                if (empty($ids)) {
                    throw new Exception($msg);
                }
            }
            // 作废需求单逻辑
            $demandDatas = $this->purchase_suggest_model->del_purchase_demand($ids, ['demand_lock' => 2]);
            if (empty($demandDatas)) {
                throw new Exception("暂无数据");
            }

            $suggestDatas = [];
            $reasonString = $this->purchase_suggest_model->get_cancel_string($cancel_reason_category);
            foreach ($demandDatas as $key => $value) {
                if ($value['erp_id'] == 0) {
                    $suggestDatas[] = [
                        "pur_sn" => $value['demand_number'],
                        "state" => SUGGEST_STATUS_CANCEL,
                        "business_line" => $value['purchase_type_id'],
                        "cancel_reason" => $reasonString['reason_name']
                    ];
                }
            }
            $ids = array_column($demandDatas, "id");
            $updateResult = $this->purchase_suggest_model->update_purchase_demand($ids, $cancel_reason_category);
            if ($updateResult && !empty($suggestDatas)) {
                $this->load->model('approval_model');
                $push_plan = $this->approval_model->push_plan_expiration($suggestDatas);//推送计划系统作废备货单
                if ($push_plan !== true) {
                    throw new Exception('推送计划系统作废失败！');
                }
            }
            if (isset($msg)) {
                $this->success_json($msg);
            } else {
                $this->success_json();
            }
        } catch (Exception $exp) {

            $this->error_json($exp->getMessage());

        }
    }


}