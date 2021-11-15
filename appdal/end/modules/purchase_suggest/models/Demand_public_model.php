<?php
class Demand_public_model extends Purchase_model
{
    /**
     * 状态列表
     * @author yefanli
     */
    public function status_list($status_type = null,$get_all = null){
        $this->load->helper('status_order');
        $this->load->helper('status_finance');

        if($get_all){
            $status_list = ['is_thousand','payment_method_source','is_drawback','order_status','suggest_status','purchase_type','order_status_wait_reject',
                'is_expedited','user_all_list','transit_warehouse','user_list','product_line_list','is_create_order',
                'is_new','is_left_stock','destination_warehouse','logistics_type','warehouse_code','supply_status',
                'is_boutique','state_type','is_scree','entities_lock_status','cancel_reason_category', 'is_fumigation',
                'connect_order_cancel','ticketed_point','is_overseas_first_order','pertain_wms_list','shipment_type','transformation','is_purchasing',
                'combination_status', 'demand_type_id', 'demand_status', 'demand_type', 'is_distribution'
                ];

        }else{
            $status_list = [$status_type];
        }

        $data_list_all = [];
        foreach($status_list as $status_type){
            switch(strtolower($status_type)){
                case 'demand_status':// 需求单完结状态
                    $data_list = getDemandStatus();;
                    break;
                case 'combination_status':// 合单状态
                    $data_list = [
                        "0" => "正常",
                        "1" => "已合单",
                    ];
                    break;
                case 'demand_type_id':// 需求类型
                    $data_list = getDemandType();
                    break;
                case 'is_include_tax':// 是否含税
                    $data_list        = getIsIncludeTax();
                    break;
                case 'is_drawback':// 是否退税
                    $data_list        = getIsDrawback();
                    break;
                case 'order_status':// 采购单状态
                    $data_list        = getPurchaseStatus();
                    break;
                case 'order_status_wait_reject':// 采购单状态（待驳回需求）
                    $data_list        = getPurOrderStatusForWaitReject();
                    break;
                case 'suggest_status':// 需求状态
                    $data_list        = getSuggestStatus();
                    break;

                case 'purchase_type':// 业务线
                case 'demand_type':// 业务线
                    $data_list = getPurchaseType();
                    break;

                case 'is_create_order': // 是否生成采购单
                    $data_list = getIsCreateOrder();
                    break;

                case 'is_expedited': // 是否加急
                    $data_list = getIsExpedited();
                    break;
                case 'is_overseas_first_order': // 是否海外仓首单
                    $data_list =is_overseas_first_order();
                    break;


                case 'user_list':// 采购员
                    $this->load->model('user/purchase_user_model');
                    $data_list = $this->purchase_user_model->get_list();
                    $data_list = array_column($data_list,'name','id');
                    $data_list = ['0' => '空'] + $data_list;
                    break;

                case 'user_all_list':// 系统内部用户
//                    $this->load->model('user/purchase_user_model');
//                    $data_list = $this->purchase_user_model->get_user_all_list();
//                    $data_list = array_column($data_list,'name','id');
//                    $data_list = ['0' => '空'] + $data_list;
                    break;

                case 'product_line_list':// 产品线列表
                    $this->load->model('product/product_line_model');
                    $data_list = $this->product_line_model->get_product_line_list_first();
                    $data_list = array_column($data_list,'linelist_cn_name','product_line_id');
                    break;

                case 'is_new': // 是否新品
                    $data_list = getProductIsNew();
                    break;

                case 'is_left_stock':// 是否欠货
                    $data_list = getIsLeftStock();
                    break;

                case 'is_purchasing': // 是否代采
                    $data_list =[1=>"否",2=>"是"];
                    break;
                case 'is_fumigation': // 是否代采
                    $data_list =[1=>"熏蒸",2=>"不熏蒸"];
                    break;

                case 'destination_warehouse': // 目的仓
                    $this->load->model('warehouse/Warehouse_model');
                    $warehouse_list = $this->Warehouse_model->get_warehouse_list();
                    $data_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
                    break;

                case 'pertain_wms_list': // 公共仓
                    $this->load->model('warehouse/Warehouse_model');
                    $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
                    // $data_list = array_column($pertain_wms_list,'pertain_wms_name','pertain_wms_code');
                    break;

                case 'logistics_type': // 物流类型
                    $this->load->model('warehouse/Logistics_type_model');
                    $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
                    $data_list = array_column($logistics_type_list,'type_name','type_code');
                    break;

                case 'warehouse_code': //采购仓库
                    $data_list = getWarehouse();
                    break;

                case 'supply_status': //货源状态
                    $data_list = getProductsupplystatus();
                    break;

                case 'is_boutique': //是否精品
                    $data_list = getISBOUTIQUE();
                    break;
                case 'state_type'://开发类型
                    $data_list = getProductStateType();
                    break;
                case 'is_scree': // SKU 屏蔽申请中
                    $data_list =[1=>"是",2=>"否"];
                    break;
                case 'entities_lock_status'://是否实单锁单
                    $data_list = getEntitiesLockStatus();
                    break;
                case 'cancel_reason_category'://作废原因分类
                    $this->load->model('system/Reason_config_model');
                    $param['status'] = 1;//启用的
                    $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
                    $data_list = array_column($cancel_reason_category_list['values'],'reason_name','id');
                    break;
                case 'connect_order_cancel': //关联采购单是否已作废（1.否;2.是）
                    $data_list =[1=>"否",2=>"是"];
                    break;
                case 'ticketed_point': // 开票点
                    $data_list= getTicketedPoint();
                    break;
                case 'shipment_type': // 发运类型
                    $data_list=[1=>"工厂发运",2=>"中转仓发运"];
                    break;
                case 'transformation': // 国内转海外
                    $data_list=[1=>"否",6=>"是"];
                    break;

                case 'payment_method_source': // 采购来源
                    $data_list=[1=>"合同",2=>"网采"];
                    break;
                case 'is_thousand':
                    $data_list=[1=>"否",2=>"是"];
                    break;
                case 'is_distribution':
                    $data_list=[1=>"是",2=>"否"];
                    break;

                default :// 未知的状态类型
                    $data_list        = null;
            }

            if($get_all){// 返回所有
                $data_list_all[$status_type] = $data_list;
            }else{// 只返回查询的
                $data_list_all = $data_list;
            }
        }

        return $data_list_all;
    }

    /**
     * 备货单列表头部
     */
    public function demand_head_list()
    {
        return [
            '操作','是否海外首单','发运类型','预计时间','备货单状态','图片','备货单业务线','备货单号','SKU','是否新品',
            '一级产品线','二级产品线','产品名称','备货数量','缺货数量','是否缺货',
            '是否退税','产品状态','开票点','单价','总金额','币种','供应商','预计断货时间','采购仓库','采购员','过期时间','创建时间',
            '关联采购单号','采购单状态','采购单数量','未转在途取消数量','备注','到货数量','入库数量','取消数量','报损数量','是否熏蒸',
            '作废原因','是否加急','目的仓','申请人','物流类型','是否异常','最小起订量','关联po已作废','国内转海外'
        ];
    }

    /**
     * 获取搜索条件
     */
    public function get_search_params()
    {
        return [
            'sku', 'buyer_id', 'supplier_code', 'is_drawback', 'product_line_id', 'purchase_order_status', 'suggest_status',
            'is_create_order', 'demand_number', 'is_new', 'is_left_stock', 'purchase_type_id', 'order_by', 'order', 'plan_product_arrive_time_start',
            'plan_product_arrive_time_end', 'is_expedited', 'create_user_id', 'destination_warehouse', 'logistics_type',
            'product_status', 'warehouse_code', 'left_stock_order', 'supplier_order', 'supply_status', 'is_boutique', 'state_type',
            'entities_lock_status', 'pertain_wms', 'create_time_start', 'create_time_end', 'is_scree', 'cancel_reason', 'connect_order_cancel',
            'is_ticketed_point', 'estimate_time_start', 'estimate_time_end', 'new_lack_qty_start', 'new_lack_qty_end', 'is_overseas_first_order',
            'shipment_type', 'transformation', 'group_ids', 'is_purchasing', 'payment_method_source', 'is_thousand', 'is_fumigation',
            'delivery_time_start', 'delivery_time_end', 'is_oversea_boutique', 'list_type', 'ids', 'combination_status', 'demand_type_id',
            'suggest_demand', 'handle_type', 'demand_type_name','temp_container', 'demand_status', "demand_type", 'is_distribution',
            'cancel_time_start','cancel_time_end',
        ];
    }
}