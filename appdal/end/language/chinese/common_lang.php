<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/10
 * Time: 17:14
 */


//含税订单跟踪列表
$lang['purchase_tax_order_tacking_list'] = [
    ['field'=> 'demand_number', 'key'=> 'demand_number', 'label' => '备货单号',],
    ['field'=> 'sku', 'key'=> 'sku', 'label' => 'SKU',],
    ['field'=> 'purchase_number', 'key'=> 'purchase_number', 'label' => '采购单号',],
    ['field'=> 'compact_number', 'key'=> 'compact_number', 'label' => '合同号',],
    ['field'=> 'purchase_order_status', 'key'=> 'purchase_order_status', 'label' => '采购单状态',],
    ['field'=> 'product_name', 'key'=> 'product_name', 'label' => '产品名称',],
    ['field'=> 'order_create_time', 'key'=> 'order_create_time', 'label' => '采购下单时间',],
    ['field'=> 'instock_date', 'key'=> 'instock_date', 'label' => '入库时间',],
    ['field'=> 'is_drawback', 'key'=> 'is_drawback', 'label' => '是否退税',],
    ['field'=> 'warehouse_code', 'key'=> 'warehouse_code', 'label' => '采购仓库',],
    ['field'=> 'buyer_name', 'key'=> 'buyer_name', 'label' => '采购员',],
    ['field'=> 'pur_ticketed_point', 'key'=> 'pur_ticketed_point', 'label' => '开票点%'],
    ['field'=> 'purchase_unit_price', 'key'=> 'purchase_unit_price', 'label' => '含税单价',],
    ['field'=> 'is_check_goods', 'key'=> 'is_check_goods', 'label' => '是否商检',],
    ['field'=> 'supplier_name', 'key'=> 'supplier_info', 'label' => '供货商名称',],
    ['field'=> 'supplier_code', 'key'=> 'supplier_info', 'label' => '供货商编码',],
    ['field'=> 'confirm_amount', 'key'=> 'confirm_amount', 'label' => '采购数量',],
    ['field'=> 'loss_quantity', 'key'=> 'loss_cancel_info', 'label' => '报损数量'],
    ['field'=> 'cancel_qty', 'key'=> 'loss_cancel_info', 'label' => '取消数量'],
    ['field'=> 'act_purchase_qty', 'key'=> 'act_purchase_qty', 'label' => '实际采购数量'],
    ['field'=> 'act_purchase_amount', 'key'=> 'act_purchase_amount', 'label' => '实际采购金额'],
    ['field'=> 'upselft_amount', 'key'=> 'upselft_amount', 'label' => '实际入库数量'],
    ['field'=> 'act_in_stock_amount', 'key'=> 'act_in_stock_amount', 'label' => '实际入库金额'],
    ['field'=> 'total_customs_quantity', 'key'=> 'customs_quantity', 'label' => '已报关数量',],
    ['field'=> 'invoice_count', 'key'=> 'invoice_count', 'label' => '已生成发票清单',],
    ['field'=> 'invoiced_qty', 'key'=> 'invoiced_qty', 'label' => '已开票数量',],
    ['field'=> 'uninvoiced_qty', 'key'=> 'uninvoiced_qty', 'label' => '未开票数量',],
    ['field'=> 'customs_status', 'key'=> 'customs_status', 'label' => '报关状态',],
    ['field'=> 'customs_time', 'key'=> 'customs_time', 'label' => '报关时间'],
    ['field'=> 'customs_number', 'key'=> 'customs_number', 'label' => '报关单号',],
    ['field'=> 'coupon_rate', 'key'=> 'coupon_rate', 'label' => '票面税率',],
    ['field'=> 'pay_status', 'key'=> 'pay_status', 'label' => '付款状态',],
    ['field'=> 'customs_code', 'key'=> 'customs_code', 'label' => '出口海关编码',],
    ['field'=> 'export_cname', 'key'=> 'export_cname', 'label' => '开票品名',],
    ['field'=> 'new_export_cname', 'key'=> 'new_export_cname', 'label' => '最新开票品名',],
    ['field'=> 'declare_unit', 'key'=> 'declare_unit', 'label' => '开票单位',],
    ['field'=> 'customs_name', 'key'=> 'customs_name', 'label' => '报关品名',],
    ['field'=> 'customs_unit', 'key'=> 'customs_unit', 'label' => '报关单位',],
    ['field'=> 'invoice_number', 'key'=> 'invoice_number_list', 'label' => '发票清单',],
    ['field'=> 'purchase_order_status', 'key'=> 'purchase_order_status', 'label' => '订单状态',],
    ['field'=> 'invoice_status', 'key'=> 'invoice_status', 'label' => '开票状态',],
    ['field'=> 'contract_invoicing_status', 'key'=> 'contract_invoicing_status', 'label' => '合同开票状态',],
    ['field'=> 'invoice_code_left_list', 'key'=> 'invoice_code_left_list', 'label' => '发票代码(左)',],
    ['field'=> 'invoice_code_right_list', 'key'=> 'invoice_code_right_list', 'label' => '发票代码(右)',]

];
//开票清单列表导出
$lang['purchase_invoice_listing_export_list'] = [
    ['field'=> 'invoice_number', 'key'=> 'invoice_number', 'label' => '发票清单号',],
    ['field'=> 'demand_number', 'key'=> 'demand_number', 'label' => '备货单号',],
    ['field'=> 'purchase_number', 'key'=> 'purchase_number', 'label' => '采购单号',],
    ['field'=> 'sku', 'key'=> 'sku', 'label' => 'SKU',],
    ['field'=> 'compact_number', 'key'=> 'compact_number', 'label' => '合同号',],
    ['field'=> 'product_name', 'key'=> 'product_name', 'label' => '产品名称',],
    ['field'=> 'customs_number', 'key'=> 'customs_code', 'label' => '报关单号',],
    ['field'=> 'purchase_user_name', 'key'=> 'buyer_name', 'label' => '采购员',],
    ['field'=> 'supplier_name', 'key'=> 'supplier_info', 'label' => '供货商名称',],
    ['field'=> 'supplier_code', 'key'=> 'supplier_info', 'label' => '供货商编码',],
    ['field'=> 'customs_code', 'key'=> 'customs_code', 'label' => '出口海关编码',],
    ['field'=> 'unit_price', 'key'=> 'unit_price', 'label' => '含税单价',],
    ['field'=> 'pur_ticketed_point', 'key'=> 'pur_ticketed_point', 'label' => '开票点%'],
    ['field'=> 'upselft_amount', 'key'=> 'upselft_amount', 'label' => '实际入库数量'],
    ['field'=> 'customs_time', 'key'=> 'customs_time', 'label' => '报关时间'],
    ['field'=> 'customs_quantity', 'key'=> 'customs_quantity', 'label' => '报关数量'],
    ['field'=> 'no_customs_quantity', 'key'=> 'no_customs_quantity', 'label' => '未报关数量'],
    ['field'=> 'customs_name', 'key'=> 'customs_name', 'label' => '报关品名',],
    ['field'=> 'export_cname', 'key'=> 'export_cname', 'label' => '开票品名',],
    ['field'=> 'customs_type', 'key'=> 'customs_type', 'label' => '报关型号',],
    ['field'=> 'customs_unit', 'key'=> 'customs_unit', 'label' => '报关单位',],
    ['field'=> 'export_cname', 'key'=> 'export_cname', 'label' => '开票品名',],
    ['field'=> 'new_export_cname', 'key'=> 'new_export_cname', 'label' => '最新开票品名',],
    ['field'=> 'invoice_coupon_rate', 'key'=> 'invoice_coupon_rate', 'label' => '票面税率',],
    ['field'=> 'app_invoice_qty', 'key'=> 'app_invoice_qty', 'label' => '可开票数量',],
    ['field'=> 'total_amount', 'key'=> 'invoice_value', 'label' => '总金额',],
    ['field'=> 'taxes', 'key'=> 'taxes', 'label' => '税金',],
];

//财务审核列表
$lang['purchase_financial_audit_list'] = [
    ['field'=> 'invoice_number', 'key'=> 'invoice_number', 'label' => '发票清单号',],
    ['field'=> 'demand_number', 'key'=> 'demand_number', 'label' => '备货单号',],
    ['field'=> 'purchase_number', 'key'=> 'purchase_number', 'label' => '采购单号',],
    ['field'=> 'sku', 'key'=> 'sku', 'label' => 'SKU',],
    ['field'=> 'compact_number', 'key'=> 'compact_number', 'label' => '合同号',],
    ['field'=> 'product_name', 'key'=> 'product_name', 'label' => '产品名称',],
    ['field'=> 'customs_number', 'key'=> 'customs_code', 'label' => '报关单号',],
    ['field'=> 'purchase_user_name', 'key'=> 'buyer_name', 'label' => '采购员',],
    ['field'=> 'supplier_name', 'key'=> 'supplier_info', 'label' => '供货商名称',],
    ['field'=> 'supplier_code', 'key'=> 'supplier_info', 'label' => '供货商编码',],
    ['field'=> 'customs_code', 'key'=> 'customs_code', 'label' => '出口海关编码',],
    ['field'=> 'unit_price', 'key'=> 'unit_price', 'label' => '含税单价',],
    ['field'=> 'pur_ticketed_point', 'key'=> 'pur_ticketed_point', 'label' => '开票点%'],
    ['field'=> 'total_amount', 'key'=> 'total_amount', 'label' => '总金额'],
    ['field'=> 'upselft_amount', 'key'=> 'upselft_amount', 'label' => '实际入库数量'],
    ['field'=> 'customs_quantity', 'key'=> 'customs_quantity', 'label' => '报关数量'],
    ['field'=> 'no_customs_quantity', 'key'=> 'no_customs_quantity', 'label' => '未报关数量'],
    ['field'=> 'customs_name', 'key'=> 'customs_name', 'label' => '报关品名',],
    ['field'=> 'export_cname', 'key'=> 'export_cname', 'label' => '开票品名',],
    ['field'=> 'customs_type', 'key'=> 'customs_type', 'label' => '报关型号',],
    ['field'=> 'customs_unit', 'key'=> 'customs_unit', 'label' => '报关单位',],
    ['field'=> 'invoice_code_left', 'key'=> 'invoice_code_left', 'label' => '发票代码(左)',],
    ['field'=> 'invoice_code_right', 'key'=> 'invoice_code_right', 'label' => '发票代码(右)',],
    ['field'=> 'invoice_coupon_rate', 'key'=> 'invoice_coupon_rate', 'label' => '票面税率',],
    ['field'=> 'invoiced_qty', 'key'=> 'invoiced_qty', 'label' => '已开票数量',],
    ['field'=> 'invoice_value', 'key'=> 'invoice_value', 'label' => '发票金额',],
    ['field'=> 'taxes', 'key'=> 'taxes', 'label' => '税金',],
    ['field'=> 'submit_user', 'key'=> 'submit_user', 'label' => '提交人',],
    ['field'=> 'submit_time', 'key'=> 'submit_time', 'label' => '提交时间',],
    ['field'=> 'audit_user', 'key'=> 'audit_user', 'label' => '审核人',],
    ['field'=> 'audit_time', 'key'=> 'audit_time', 'label' => '审核时间',],
    ['field'=> 'audit_status', 'key'=> 'audit_status', 'label' => '审核状态',],
    ['field'=> 'remark', 'key'=> 'remark', 'label' => '审核备注',],
];


//入库后退货- 申请明细列表
$lang['return_apply_detail_list'] = [
    ['field'=> 'main_number', 'key'=> 'main_number', 'label' => '申请ID',],
    ['field'=> '', 'key'=> 'product_img_url_thumbnails', 'label' => '图片',],
    ['field'=> 'sku', 'key'=> 'sku', 'label' => 'SKU',],
    ['field'=> 'product_name', 'key'=> 'product_name', 'label' => '产品名称',],
    ['field'=> '', 'key'=> 'can_match_inventory', 'label' => '可配库库存',],
    ['field'=> 'purchase_on_way', 'key'=> 'purchase_on_way', 'label' => '采购在途',],
    ['field'=> 'return_qty', 'key'=> 'return_qty', 'label' => '申请退货数量',],
    ['field'=> 'pur_return_qty', 'key'=> 'pur_return_qty', 'label' => '采购确认数量',],
    ['field'=> 'wms_return_qty', 'key'=> 'wms_return_qty', 'label' => '仓库确认数量',],
    ['field'=> 'proposer', 'key'=> 'proposer', 'label' => '申请人',],
    ['field'=> 'create_time', 'key'=> 'create_time', 'label' => '申请时间',],
    ['field'=> 'return_reason', 'key'=> 'return_reason_text', 'label' => '申请退货原因',],
    ['field'=> 'buyer_name', 'key'=> 'buyer_name', 'label' => '采购员',],
    ['field'=> 'end_time', 'key'=> 'end_time', 'label' => '处理完结时间',],
    ['field'=> 'processing_time', 'key'=> 'processing_time', 'label' => '处理时间',],
    ['field'=> 'return_warehouse_code', 'key'=> 'return_warehouse_code_text', 'label' => '申请退货仓库',],
    ['field'=> 'processing_status', 'key'=> 'processing_status_text', 'label' => '处理状态',],
];

//入库后退货- 采购确认列表
$lang['return_purchase_confirm_list'] = [
    ['field'=> 'part_number', 'key'=> 'part_number', 'label' => '申请ID',],
    ['field'=> 'sku', 'key'=> 'product_img_url_thumbnails', 'label' => '图片',],
    ['field'=> 'sku', 'key'=> 'sku', 'label' => 'SKU',],
    ['field'=> 'sample_packing_weight', 'key'=> 'sample_packing_weight', 'label' => '样品包装重量',],
    ['field'=> 'product_name', 'key'=> 'product_name', 'label' => '产品名称',],
    ['field'=> 'supplier_name', 'key'=> 'supplier_name', 'label' => '供应商名称',],
    ['field'=> 'supplier_code', 'key'=> 'supplier_code', 'label' => '供应商编码',],
    ['field'=> 'unit_price_without_tax', 'key'=> 'unit_price_without_tax', 'label' => '未税单价',],
    ['field'=> 'return_qty', 'key'=> 'return_qty', 'label' => '申请数量',],
    ['field'=> 'pur_return_qty', 'key'=> 'pur_return_qty', 'label' => '采购确认数量',],
    ['field'=> 'wms_match_return_qty', 'key'=> 'wms_match_return_qty', 'label' => '成功配库数量',],
    ['field'=> 'wms_return_qty', 'key'=> 'wms_return_qty', 'label' => '仓库确认数量',],
    ['field'=> 'return_cost', 'key'=> 'return_cost', 'label' => '退货产品成本',],
    ['field'=> 'return_unit_price', 'key'=> 'return_unit_price', 'label' => '退货单价',],
    ['field'=> 'return_amount', 'key'=> 'return_amount', 'label' => '退货金额',],
    ['field'=> 'freight_payment_type', 'key'=> 'freight_payment_type_text', 'label' => '运费支付类型',],
    ['field'=> 'weight', 'key'=> 'weight', 'label' => '总重量(kg)',],
    ['field'=> 'freight', 'key'=> 'freight', 'label' => '实际运费',],
    ['field'=> 'return_reason', 'key'=> 'return_reason_text', 'label' => '申请退货原因',],
    ['field'=> 'audit_user', 'key'=> 'audit_user', 'label' => '审核人',],
    ['field'=> 'audit_time', 'key'=> 'audit_time', 'label' => '审核时间',],
    ['field'=> 'audit_remark', 'key'=> 'audit_remark', 'label' => '审核备注',],
    ['field'=> 'restricted_supplier', 'key'=> 'restricted_supplier', 'label' => '需要限制的供应商',],
    ['field'=> 'return_status', 'key'=> 'return_status_text', 'label' => '退货状态',],
    ['field'=> 'buyer_name', 'key'=> 'return_number', 'label' => '退货单号',],
    ['field'=> '', 'key'=> '', 'label' => '退货快递单号',],
    ['field'=> 'return_warehouse_code', 'key'=> 'return_warehouse_code_text', 'label' => '退货仓库',],
];

//入库后退货- 退货跟踪列表
$lang['return_track_list'] = [
    ['field'=> 'return_number', 'key'=> 'return_number', 'label' => '退货单号',],
];

//验货条件配置表头字段
$lang['business_line'] = '业务线';
$lang['product_line_one'] = '一级产品线';
$lang['purchase_unit_price'] = '采购单价';
$lang['purchase_amount'] = '采购金额';
$lang['dimension'] = '维度';
$lang['effective_time'] = '生效时间';
$lang['enable_status'] = '启用状态';
$lang['last_modified'] = '最近一次修改';

//验货管理列表
$lang['check_code'] = '申请编码';
$lang['apply_info'] = '申请信息';
$lang['buyer'] = '采购员';
$lang['supplier_info'] = '供应商信息';
$lang['po_number'] = 'PO号';
$lang['check_times'] = '检验次数';
$lang['check_status'] = '验货状态/类型';
$lang['confirm_info'] = '提交人/提交时间';
$lang['approval_info'] = '审核信息';
$lang['check_time'] = '验货时间';
$lang['is_special_title'] = '特批出货';
//验货管理PO详情列表
$lang['demand_number'] = '备货单号';
$lang['sku'] = 'SKU';
$lang['purchase_num'] = '采购数量';
$lang['pur_warehouse'] = '采购仓库';
$lang['check_result'] = '验货结果';
$lang['invalid_num'] = '作废数量';
$lang['order_status'] = '订单状态';
$lang['pay_status'] = '付款状态';
//验货管理-日志列表
$lang['operation_time'] = '时间';
$lang['operator'] = '操作人';
$lang['operation_type'] = '操作类型';
$lang['operation_content'] = '操作内容';
$lang['other_file'] = '相关附件';
//验货报告sku列表
$lang['sequence'] = '序号';
$lang['pur_number'] = '采购单号';
$lang['product_img'] = '商品图片';
$lang['product_name'] = '商品名称';
$lang['pur_valid_qty'] = '有效采购数量';
$lang['check_qty'] = '抽检数';
$lang['owes_qty'] = '缺货数';
$lang['defective_qty'] = '不良数';
$lang['defective_rate'] = '不良率';
$lang['received_qty'] = '实收数';
$lang['judgment_result'] = '检验结果';
$lang['defective_type'] = '不良类型';
$lang['defective_reason'] = '不良原因';
$lang['improvement_measure'] = '改善措施';
$lang['responsible_department'] = '责任部门';
$lang['responsible_person'] = '责任人';
$lang['inspector_remark'] = '验货员备注';
$lang['accessory'] = '附件';

//核销-入库明细列表
$lang['hx_product_img'] = '产品图片';
$lang['hx_product_name'] = '产品名称';
$lang['hx_instock_batch'] = '入库批次';
$lang['hx_deliery_batch'] = '发货批次号';
$lang['hx_instock_date'] = '入库日期';
$lang['hx_waiting_time'] = '下单日期';
$lang['hx_instock_price'] = '入库金额';
$lang['hx_instock_qty'] = '入库数量';
$lang['hx_instock_qty_more'] = '多货数量';
$lang['hx_defective_num'] = '次品数量';
$lang['hx_warehouse_code'] = '采购仓库';
$lang['hx_pur_order'] = '采购订单';
$lang['hx_sku'] = 'SKU';
$lang['hx_contract_number'] = '合同号';
$lang['hx_purchase_name'] = '采购主体';
$lang['hx_is_drawback'] = '是否退税';
$lang['hx_supplier_name'] = '供应商';
$lang['hx_purchase_unit_price'] = '采购单价';
$lang['hx_currency'] = '币种';
$lang['hx_coupon_rate'] = '票面税率';
$lang['hx_buyer'] = '采购员';
$lang['hx_instock_user_name'] = '仓库操作人';
$lang['hx_business_line'] = '业务线';
$lang['hx_pay_type'] = '支付方式';
$lang['hx_account_type'] = '结算方式';
$lang['hx_suggest_order_status'] = '备货单状态';
$lang['hx_pur_order_status'] = '订单状态';
$lang['hx_is_paste'] = '是否承诺贴码';
$lang['hx_is_pasted'] = '是否实际贴码';
$lang['hx_instock_type'] = '入库类型';
$lang['hx_statement_number'] = '对账单号';
$lang['hx_pur_order_pay_status'] = '采购单付款状态';
$lang['hx_status'] = '冲销状态';
$lang['hx_surplus_amount'] = '剩余可冲销金额';
$lang['hx_po_surplus_amount'] = 'PO剩余可冲销金额';
$lang['hx_remark'] = '备注';
//核销-采购单冲销汇总列表
$lang['hx_purchase_number'] = '采购单号';
$lang['hx_purchase_amount'] = '采购金额';
$lang['hx_order_pay_status'] = '付款状态';
$lang['hx_pay_amount'] = '已付款金额';
$lang['hx_cancel_amount'] = '取消金额';
$lang['hx_refund_amount'] = '退款金额';
$lang['hx_loss_amount'] = '报损金额';
$lang['hx_instock_price_after_ca'] = '入库金额冲销后余额';
$lang['hx_real_price_after_ca'] = '采购金额冲销后余额';
$lang['hx_orders_time'] = '下单时间';
$lang['hx_finsh_time'] = '冲账完结时间';
$lang['hx_is_finish'] = '是否完结';
//核销-其他冲销审核列表
$lang['hx_apply_code'] = '申请编码';
$lang['hx_ca_type'] = '冲销类型';
$lang['hx_ca_amount'] = '冲销金额';
$lang['hx_ca_audit_status'] = '审核状态';
$lang['hx_ca_purchase_name'] = '采购主体';
$lang['hx_ca_supplier_name'] = '供应商名称';
$lang['hx_ca_source'] = '采购来源';
$lang['hx_ca_apply_user'] = '申请人/申请时间';
$lang['hx_ca_apply_remark'] = '申请备注';
$lang['hx_ca_audit_user'] = '审核人/审核(驳回)时间';
$lang['hx_ca_audit_remark'] = '审核备注';
$lang['hx_ca_union_code'] = '关联的编码';



//发运管理- 计划部取消
$lang['shipment_plan_cancel_list'] = [
    ['field'=> 'plan_cancel_number', 'key'=> 'plan_cancel_number', 'label' => '申请ID',],
    ['field'=> 'shipment_type', 'key'=> 'shipment_type_text', 'label' => '发运类型',],
    ['field'=> 'new_demand_number', 'key'=> 'new_demand_number', 'label' => '新备货单号',],
    ['field'=> 'sku', 'key'=> 'sku', 'label' => 'SKU',],
    ['field'=> 'purchase_number', 'key'=> 'purchase_number', 'label' => '采购单号',],
    ['field'=> 'supplier_name', 'key'=> 'supplier_name', 'label' => '供应商名称',],
    ['field'=> 'is_drawback', 'key'=> 'is_drawback', 'label' => '是否退税',],
    ['field'=> 'apply_cancel_qty', 'key'=> 'apply_cancel_qty', 'label' => '申请数量',],
    ['field'=> 'act_cancel_qty', 'key'=> 'act_cancel_qty', 'label' => '实际同意数量',],
    ['field'=> 'buyer_name', 'key'=> 'buyer_name', 'label' => '采购员',],
    ['field'=> 'apply_time', 'key'=> 'apply_time', 'label' => '申请时间',],
    ['field'=> 'audit_user_name', 'key'=> 'audit_user_name', 'label' => '审核人',],
    ['field'=> 'audit_remark', 'key'=> 'audit_remark', 'label' => '审核备注',],
    ['field'=> 'suggest_order_status', 'key'=> 'suggest_order_status_text', 'label' => '备货单状态',],
    ['field'=> 'cancel_number', 'key'=> 'cancel_number', 'label' => '关联的申请编码',],
    ['field'=> 'audit_status', 'key'=> 'audit_status_text', 'label' => '审核状态',],
];

$lang['shipment_plan_change_info_type'] = [
    'change_shipment_type' => '发运类型变更',
    'change_destination_warehouse_code' => '目的仓',
    'change_logistics_type' => '物流类型',
    'change_plan_qty' => '计划数量',
];
