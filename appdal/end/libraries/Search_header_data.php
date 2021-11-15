<?php
/**
 * User: yefanli
 * Date: 2020-08-13
 */
class Search_header_data {
    /**
     * 采购单搜索头分组 map
     */
    public function get_header_data_group_data()
    {
        // 待处理
        // review_remarks  备注
        // lock_order 锁单
        // pay_finish_status 付款完结状态
        // pertain_wms 公共仓
        return [
            "key1"  => [
                "name" => "图片",
                "val" => ["product_img_url"],
                "index" => 1,
                "status" => 0,
            ],
            "key2"  => [
                "name" => "采购员",
                "val" => ["buyer_name"],
                "index" => 2,
                "status" => 0,
            ],
            "key3"  => [
                "name" => "SKU/包装类型/产品线",
                "val" => ["sku", "purchase_packaging", "product_line_id"],
                "index" => 3,
                "status" => 0,
            ],
            "key4"  => [
                "name" => "产品名称/产品状态",
                "val" => [
                    "product_name",
                    "product_status"
                ],
                "index" => 4,
                "status" => 0,
            ],
            "key5"  => [
                "name" => "未税单价/含税单价/上次采购单价",
                "val" => [
                    "product_base_price",
                    "purchase_unit_price",
                    "last_purchase_price"
                ],
                "index" => 5,
                "status" => 0,
            ],
            "key6"  => [
                "name" => "采购单号/订单状态",
                "val" => [
                    "purchase_number",
                    "purchase_order_status",
                ],
                "index" => 6,
                "status" => 0,
            ],
            "key7"  => [
                "name" => "备货单号/备货单业务线/备货单状态",
                "val" => [
                    "demand_number",
                    "demand_purchase_type_id",
                    "suggest_order_status",
                ],
                "index" => 7,
                "status" => 0,
            ],
            "key8"  => [
                "name" => "合同号/对账单号/请款单号",
                "val" => [
                    "compact_number",
                    "statement_number",
                    "requisition_number",
                ],
                "index" => 8,
                "status" => 0,
            ],
            "key9"  => [
                "name" => "取消未到货次数/取消未到货状态/报损状态",
                "val" => [
                    "cancel_time",
                    "audit_status",
                    "loss_status",
                ],
                "index" => 9,
                "status" => 0,
            ],
            "key10"  => [
                "name" => "采购数量/最小起订量",
                "val" => [
                    "purchase_amount",
                    "starting_qty",
                ],
                "index" => 10,
                "status" => 0,
            ],
            "key11"  => [
                "name" => "到货数量/入库数量/多货数量",
                "val" => [
                    "arrival_qty",
                    "instock_qty",
                    "instock_qty_more",
                ],
                "index" => 11,
                "status" => 0,
            ],
            "key12"  => [
                "name" => "取消数量/取消金额/报损数量",
                "val" => [
                    "cancel_ctq",
                    "item_total_price",
                    "loss_amount",
                ],
                "index" => 12,
                "status" => 0,
            ],
            "key13"  => [
                "name" => "采购仓库/公共仓",
                "val" => [
                    "warehouse_code",
                    "pertain_wms",
                ],
                "index" => 13,
                "status" => 0,
            ],
            "key14"  => [
                "name" => "供应商/采购来源",
                "val" => [
                    "supplier_name",
                    "source",
                ],
                "index" => 14,
                "status" => 0,
            ],
            "key15"  => [
                "name" => "结算方式/支付方式/运费支付",
                "val" => [
                    "account_type",
                    "pay_type",
                    "is_freight",
                ],
                "index" => 15,
                "status" => 0,
            ],
            "key16"  => [
                "name" => "网拍账号/拍单号",
                "val" => [
                    "purchase_acccount",
                    "pai_number",
                ],
                "index" => 16,
                "status" => 0,
            ],
            "key17"  => [
                "name" => "1688订单状态/付款提醒状态/1688退款状态",
                "val" => [
                    "remark",
                    "pay_notice",
                    "refund_status"
                ],
                "index" => 17,
                "status" => 0,
            ],
            "key18"  => [
                "name" => "物流轨迹/轨迹状态",
                "val" => [
                    "logistics_trajectory",
                ],
                "index" => 18,
                "status" => 0,
            ],
            "key19"  => [
                "name" => "采购金额/入库金额/已付金额",
                "val" => [
                    "purchase_price",
                    "amount_storage",
                    "amount_paid",
                ],
                "index" => 19,
                "status" => 0,
            ],
            "key20"  => [
                "name" => "PO总运费/PO总加工费/PO总优惠额",
                "val" => [
                    "freight",
                    "process_cost",
                    "discount",
                ],
                "index" => 20,
                "status" => 0,
            ],
            "key21"  => [
                "name" => "付款状态/付款完结状态",
                "val" => [
                    "pay_status",
                    "pay_finish_status",
                ],
                "index" => 21,
                "status" => 0,
            ],
            "key22"  => [
                "name" => "是否退税/采购主体",
                "val" => [
                    "is_drawback",
                    "purchase_name",
                ],
                "index" => 22,
                "status" => 0,
            ],
            "key23"  => [
                "name" => "已开票数量/已开票金额",
                "val" => [
                    "invoices_issued",
                    "invoiced_amount",
                ],
                "index" => 23,
                "status" => 0,
            ],
            "key24"  => [
                "name" => "开票点/退税率",
                "val" => [
                    "pur_ticketed_point",
                    "export_tax_rebate_rate",
                ],
                "index" => 24,
                "status" => 0,
            ],
            "key25"  => [
                "name" => "开票票名/开票单位/出口海关编码",
                "val" => [
                    "invoice_name",
                    "issuing_office",
                    "customs_code",
                ],
                "index" => 25,
                "status" => 0,
            ],
            "key26"  => [
                "name" => "创建时间/审核日期/订单完结时间",
                "val" => [
                    "create_time",
                    "audit_time",
                    "completion_time",
                ],
                "index" => 26,
                "status" => 0,
            ],
            "key27"  => [
                "name" => "到货时间/入库日期",
                "val" => [
                    "arrival_date",
                    "instock_date",
                ],
                "index" => 27,
                "status" => 0,
            ],
            "key28"  => [
                "name" => "线上账期日期/应付款时间/付款时间",
                "val" => [
                    "tap_date_str",
                    "need_pay_time",
                    "pay_time",
                ],
                "index" => 28,
                "status" => 0,
            ],
            "key29"  => [
                "name" => "首次预计到货时间/预计到货日期/预计发货时间",
                "val" => [
                    "first_plan_arrive_time",
                    "plan_arrive_time",
                    "es_shipment_time",
                ],
                "index" => 29,
                "status" => 0,
            ],
            "key30"  => [
                "name" => "目的仓/物流类型/发运类型",
                "val" => [
                    "destination_warehouse",
                    "logistics_type",
                    "shipment_type",
                ],
                "index" => 30,
                "status" => 0,
            ],
            "key31"  => [
                "name" => "是否代采/是否定制/是否分销",
                "val" => [
                    "is_purchasing",
                    "is_customized",
                    "is_distribution",
                ],
                "index" => 31,
                "status" => 0,
            ],
            "key32"  => [
                "name" => "是否对接门户/门户订单状态",
                "val" => [
                    "is_gateway_ch",
                    "audit_time_status",
                ],
                "index" => 32,
                "status" => 0,
            ],
            "key33"  => [
                "name" => "是否逾期（权均）/逾期天数(权均)",
                "val" => [
                    "is_overdue",
                    "overdue_days",
                ],
                "index" => 33,
                "status" => 0,
            ],
            "key34"  => [
                "name" => "是否逾期(固定)/逾期天数（固定）",
                "val" => [
                    "devliery_status",
                    "devliery_days",
                ],
                "index" => 34,
                "status" => 0,
            ],
            "key35"  => [
                "name" => "是否逾期（预计)/逾期天数（预计)",
                "val" => [
                    "overdue_day",
                    "overdue_day_data",
                ],
                "index" => 35,
                "status" => 0,
            ],
            "key36"  => [
                "name" => "是否新品/是否海外首单/是否锁单",
                "val" => [
                    "is_new",
                    "is_overseas_first_order_ch",
                    "lock_order",
                ],
                "index" => 36,
                "status" => 0,
            ],
            "key37"  => [
                "name" => "备注",
                "val" => [
                    "review_remarks",
                ],
                "index" => 37,
                "status" => 0,
            ],
            "key38"  => [
                "name" => "其他信息",
                "val" => [
                    "modify_remark",
                ],
                "index" => 38,
                "status" => 0,
            ],
            "key39" => [
                "name" => "交期天数/是否超长交期",
                "val" =>[
                    "new_devliy"
                ],
                "index" => 39,
                "status" => 0,
            ],
            "key40" => [
                "name" => "虚拟柜号/需求类型/合单状态",
                "val" =>[
                    "temp_container",
                    "demand_type",
                    "is_merge",
                ],
                "index" => 40,
                "status" => 0,
            ],
            "key41"  => [
                "name" => "已付运费/已付加工费/已付优惠额",
                "val" => [
                    "freight_pay",
                    "process_cost_pay",
                    "discount_pay",
                ],
                "index" => 41,
                "status" => 0,
            ],

            "key42" => [
                "name" => "信账期日期",
                "val" => [
                    "tap_date_str_sync"
                ],
                "index" => 43,
                "status" => 0,
            ],
            "key42"  => [
                "name" => "备注图片",
                "val" => [
                    "remark_images",
                ],
                "index" => 42,
                "status" => 0,
            ],

            "key43"  => [
                "name" => "授信账期日期",
                "val" => [
                    "tap_date_str_sync",
                ],
                "index" => 42,
                "status" => 0,
            ],
            


        ];
    }


    /**
     * 采购单列表列名称
     */
    public function table_columns()
    {
        return [
            //    'id' => 'ID号',
            'product_img_url' => '图片',
            'purchase_order_status' => '订单状态',
            'suggest_order_status' => '备货单状态',
            'sku' => 'sku',
            'purchase_number' => '采购单号',
            'demand_number' => '备货单号',
            'product_name' => '产品名称',
            'compact_number' => '合同号',
            'buyer_name' => '采购员',
            'purchase_name' => '采购主体',
            'supplier_name' => '供应商',
            'purchase_amount' => '采购数量',
            'purchase_price' => '采购金额',
            'is_new' => '是否新品',
            'is_drawback' => '是否退税',
            'coupon_rate_message'        => '票面信息',
            'is_include_tax' => '是否含税',
            'purchase_unit_price' => '含税单价',
            'product_base_price' => '未税单价',
            'pur_ticketed_point' => '开票点',
            'export_tax_rebate_rate' => '退税率',
            'currency_code' => '币种',
            'invoice_name' => '开票票名',
            'issuing_office' => '开票单位',
            'invoices_issued' => '已开票数量',
            'invoiced_amount' => '已开票金额',
            'warehouse_code' => '采购仓库',
            'is_expedited' => '是否加急',
            'logistics_trajectory' => '物流轨迹',
            'account_type' => '结算方式',
            'pay_type' => '支付方式',
            'payment_platform' => '支付平台',
            'settlement_ratio' => '结算比例',
            'shipping_method_id' => '供应商运输',
            'create_time'=>'创建时间',
            'audit_time' => '审核日期',
            'plan_arrive_time' => '预计到货日期',
            'es_shipment_time' => '预计发货时间',
            'first_plan_arrive_time' => '首次预计到货时间',
            'source' => '采购来源',
            'freight' => '运费',
            'process_cost' => '加工费',
            'is_freight' => '运费支付',
            'discount' => '优惠额',
            'freight_formula_mode' => '运费计算方式',
            'purchase_acccount' => '网拍账号',
            'pai_number' => '拍单号',
            'arrival_date' => '到货时间',
            'arrival_qty' => '到货数量',
            'instock_qty_more' => '多货数量',
            'instock_date' => '入库日期',
            'instock_qty' => '入库数量',
            'logistics_type' => '物流类型',
            'amount_storage' => '入库金额',
            'amount_paid' => "已付金额",
            'overdue_days' => '逾期天数（权均）',
            'is_overdue' => '是否逾期（权均）',
            'is_destroy' => '是否核销',
            'cancel_ctq' => '取消数量',
            'item_total_price' => '取消金额',
            'loss_amount' => '报损数量',
            'loss_status'  => '报损状态',
            'customs_code'=>'出口海关编码',
            'pay_status' => '付款状态',
            'pay_time' => '付款时间',
            'requisition_number' => '请款单号',
            'audit_status' => '取消未到货状态',
            'tap_date_str' => '线上账期日期',
            'need_pay_time' => '应付款时间',
            'pay_notice' => '付款提醒状态',
            'is_ali_order' => '是否1688下单',
            'remark' => '1688订单状态',
            'modify_remark' => '其他备注',
            'destination_warehouse' => '目的仓',
            'product_status' => '产品状态',
            'last_purchase_price' => '上次采购单价',
            'is_inspection' => '是否商检',
            'shipment_type' => '发运类型',
            'supplier_source' => '供应商来源',
            'statement_number' => '对账单号',
            'state_type'     => '开发类型',
            'lack_quantity_status' => '是否欠货',
            'purchase_packaging' => '包装类型',
            'starting_qty' => '最小起订量',
            'starting_qty_unit' => '最小起订量单位',
            'is_invalid'        => '连接是否失效',
            'is_ali_price_abnormal' => '金额异常',
            'coupon_rate'       => '票面税率',
            'coupon_rate_price' => '票面未税单价',
            'completion_time'    => '订单完结时间',
            'is_purchasing' =>'是否代采',
            'audit_time_status' =>'门户订单状态',
            'barcode_pdf'=>'是否有商品条码',
            'label_pdf'=>'是否有物流标签',
            'is_new_ch'=>'是否新品',
            'is_overseas_first_order_ch'=>'是否海外首单',
            'is_gateway_ch'=>'是否对接门户',
            'check_status_cn'=>'验货状态',
            'demand_purchase_type_id' => '备货单业务线',
            'is_customized' => '是否定制',
            'devliery_days' => '逾期天数（固定）',
            'devliery_status' => '是否逾期(固定)',
            'pertain_wms' => '公共仓',
            'lock_order' => '是否锁单',
            'supplier_status' => '供应商是否禁用',
            'demand_type' => '需求类型',
            'is_merge' => '合单状态',
            'overdue_day' => '是否逾期（预计）',
            'overdue_day_data' => '逾期天数(预计)',
            'temp_container' => '虚拟柜号',
            'is_distribution' => '是否分销',
            'price_reduction'       => 'SKU降价中',

        ];
    }


    /**
     * 采购单列表搜索框配置
     */
    public function table_search_columns()
    {
        $key_value = [
            'purchase_order_status' => '采购状态',
            'suggest_order_status'  => '备货单状态',
            'demand_number'         => '备货单号',
            'purchase_number'       => '采购单号',
            'sku'                   => 'SKU',
            'create_time'           => '创建时间',
            'buyer_id'              => '采购员',
            'supplier_code'         => '供应商',
            'is_drawback'           => '是否退税',
            'product_name'          => '产品名称',
            'is_cross_border'       => '跨境宝供应商',
            'pay_status'            => '付款状态',
            'pay_notice'            => '付款提醒状态',
            'source'                => '采购来源',
            'is_destroy'            => '是否核销',
            'product_is_new'        => '是否新品',
            'purchase_type_id'      => '业务线',
            'compact_number'        => '合同号',
            'loss_status'           => '报损状态',
            'audit_status'          => '取消未到货状态',
            'is_ali_order'          => '是否1688下单',
            'express_no'            => '物流单号',
            'product_status'        => '商品状态',
            'is_ali_abnormal'       => '是否1688异常',
            'warehouse_code'        => '采购仓库',
            'pertain_wms'           => '公共仓',
            'pai_number'            => '拍单号',
            'account_type'          => '结算方式',
            'is_inspection'         => '是否商检',
            'is_overdue'            => '是否逾期（权均）',
            'supplier_source'       => '供应商来源',
            'statement_number'      => '对账单号',
            'need_pay_time'         => '应付款时间',
            'audit_time'            => '审核时间',
            'state_type'            => '开发类型',
            'is_expedited'          => '是否加急',
            'is_scree'              => '是否屏蔽申请中',
            'entities_lock_status'  => '是否锁单中',
            'lack_quantity_status'  => '是否欠货',
            'is_invaild'            => '链接是否失效',
            'is_forbidden'          => '供应商是否禁用',
            'is_ali_price_abnormal' => '金额异常',
            'level'                 => '审核级别',
            'is_relate_ali'         => '是否关联1688',
            'first_product_line'    => '一级产品线',
            'is_generate'           => '是否生成合同单',
            'is_purchasing'         => '是否代采',
            'is_arrive_time_audit'  => '交期确认状态',
            'order_num'             => '下单次数',
            'barcode_pdf'           => '是否有产品条码',
            'label_pdf'             => '是否有物流标签',
            'is_equal_sup_id'       => '供应商ID一致',
            'is_equal_sup_name'     => '供应商名称一致',
            'is_overseas_first_order' => '是否海外首单',
            'is_gateway'            =>'是否对接门户',
            'check_status'          =>'验货状态',
            'push_gateway_success'  => '是否推送成功',
            'gateway_status'        => '门户订单状态',
            'transformation'        => '国内转海外',
            'pay_finish_status'     => '付款完结状态',
            'ca_amount_search'      => '抵扣金额',
            'demand_purchase_type_id' => '备货单业务线',
            'is_customized'         => '是否定制',
            'devliery_days'         => '逾期天数(交期)',
            'devliery_status'       => '是否逾期(交期)',
            'quantity'              => '门户系统回货数',
            'shipment_type'         => '发运类型',
            'pay_type'              => '支付方式',
            'unfinished_overdue'    => '未完结天数',
            'pur_manager_audit_reject' => '采购经理驳回',
            'overdue_delivery_type' => '逾期天数类型',
            'ali_order_status'      => '1688订单状态',
            'ali_refund_amount'     => '1688退款金额',
            'instock_qty_gt_zero'   => '入库数量',
            'pay_time'              => '付款时间',
            'is_completion_order'   => '订单是否完结',
            'plan_arrive_time'      => '预计到货时间',
            'instock_date'          => '入库时间',
            'groupname'             => '采购组别',
            'tap_date_str'          => '线上账期日期',
            'demand_type'           => '需求类型',
            'is_merge'              => '合单状态',
            'temp_container'        => '虚拟柜号',
            'is_distribution'       => '是否分销',
        ];

        return $key_value;
    }
}