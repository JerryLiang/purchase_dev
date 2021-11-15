<?php

defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * 蓝凌接口api 键值映射关系
 * @author Harvin
 */
$config = [
    "net_mining_id" => "16884d774442603ea94324645cea2396", //網采單表單ID
    'net_mining' => [//網采單請款單
        'purchase_order_number' => 'fd_37028232f54738.fd_37028267129ebe', //采购单号
        'sku' => 'fd_37028232f54738.fd_370282679f0024', //SKU
        'product_name' => 'fd_37028232f54738.fd_3702826661aee6', //产品名称
        'purchase_unit_price' => 'fd_37028232f54738.fd_37028268588e68', //单价
        'purchase_amount' => 'fd_37028232f54738.fd_370282691e18d8', //数量
        'freight' => 'fd_37028232f54738.fd_37028269e0ecc8', //运费
        'discount'=> 'fd_37028232f54738.fd_3702826acd30c6', //优惠额
        'pay_total' => 'fd_37028232f54738.fd_3702826b93e064', //采购金额
        'product_money' => 'fd_3702b55815fbb6', //采购金额(总)
        'freight_total' => 'fd_3702b56130e42e', //运费(总)
        'discount_total'=>'fd_3702b55df7f714', //优惠额(总)
        'total'=>'fd_3702b56261063e', //应付总金额
        'requisition_number'=>'fd_3702957304996e', //请款单号
    ],
    'purchase_order_id' => "1687b2907cefdbbc4c951e94e5899d1b", //採購單表單ID
    'purchase_order' => [ //采购单
        'buyer_name' => 'fd_3706b323892b86', //申请人（采购员）
        'department'=>'fd_3706b324670a0a',//申请部门
        'modify_time'=>'fd_340f535fd78dc8',//申请日期
        'purchase_number_test' => 'fd_3706ae37d68372', //采购单号
        'product_img_url'=>'fd_37209da38fa362.fd_370fcb0777e3a0',//图片
        'sku'=>'fd_37209da38fa362.fd_37209e7dadf2d4',//sku
        'product_name'=>'fd_37209da38fa362.fd_37209e828c37ee',//产品名称
        'purchase_name'=>'fd_37209da38fa362.fd_37209e888e59e8',//采购主体
        'supplier_name'=>'fd_37209da38fa362.fd_37209e9c3d6390',//供应商
        'purchase_amount'=>'fd_37209da38fa362.fd_37209ea3a9f84e',//采购数量
        'purchase_money'=>'fd_37209da38fa362.fd_37209eaf31b7fe',//采购金额
        'is_new'=>'fd_37209da38fa362.fd_37209f11a59d5a',//是否新品
        'is_drawback'=>'fd_37209da38fa362.fd_37209f165b92a2',//是否退税
        'is_include_tax'=>'fd_37209da38fa362.fd_37209f1ecfc3f6',//是否含税
        'purchase_unit_price'=>'fd_37209da38fa362.fd_37209f26ec0966',//含税单价
        'warehouse_name'=>'fd_37209da38fa362.fd_37209f366e123a',//采购仓库
        'pay_type'=>'fd_37209da38fa362.fd_37209f3d6698e0',//支付方式
        'account_type'=>'fd_37209da38fa362.fd_37209ebd17dbda',//结算方式
        'settlement_ratio'=>'fd_37209da38fa362.fd_37209f456817fe',//结算比例
        'shipping_method_id'=>'fd_37209da38fa362.fd_37209f4dfc2166',//供应商运输
        'purchase_acccount'=>'fd_37209da38fa362.fd_37209f5f7766dc',//账号
        'source'=>'fd_37209da38fa362.fd_37209f5600347a',//采购来源
        'is_destroy'=>'fd_37209da38fa362.fd_37209f66c076de',//是否核销
        'purchase_type_id'=>'fd_37209da38fa362.fd_37209f6e5f3cb6',//采购类型  
        'purchase_order_status'=>'fd_37209da38fa362.fd_37209e775284c6',//采购状态
        'is_cross_border'=>'fd_37209da38fa362.fd_37209f77cedf30',//跨境宝供应商
        'plan_product_arrive_time'=>'fd_37209da38fa362.fd_37213a78d36c26',//预计到货时间
        'discount'=>'fd_37209da38fa362.fd_37209ee7ee9516',//优惠额
        'freight'=>'fd_37209da38fa362.fd_37209ef9e9ca86',//运费
        'pai_number'=>'fd_37209da38fa362.fd_37209f0bf50842',//拍单号
    ],
    'compact_number_id'=>'1687b97e744b13b4d8a49f64c2b91dd9',//合同单 表单id
    'compact_number'=>[ //合同请款表单
        'supplier_name'=>'fd_370339dc2f5baa',//供应商名称
        'settlement_method'=>'fd_370506ec1ea612',//结算方式
        'pay_type'=>'fd_3707333ff300cc',//支付方式
        'is_drawback'=>'fd_37050933f7aa74',//是否退税
        'js_ratio'=>'fd_37050b39f37542',//结算比例
        'is_freight'=>'fd_37050a37c9dbbe',//运费支付
        
        'purchase_number'=>'fd_3703391c54ef68.fd_37033950048816',//采购单号
        'sku'=>'fd_3703391c54ef68.fd_37033953ed7dd6',//SKU
        'product_img_url'=>'fd_3703391c54ef68.fd_3703395625098a',//图片
        'product_name'=>'fd_3703391c54ef68.fd_3703395e14357e',//产品名称
        'purchase_unit_price'=>'fd_3703391c54ef68.fd_3703395f56c3a0',//采购单价
        'purchase_amount'=>'fd_3703391c54ef68.fd_37033960635cee',//采购数量
        'freight'=>'fd_3703391c54ef68.fd_370339616725d0',//运费
        'discount'=>'fd_3703391c54ef68.fd_3703396248e764',//优惠额
        'pay_total'=>'fd_3703391c54ef68.fd_37033963ea7c70',//已请金额
        
        'pay_ratio'=>'fd_370339d0d14dac',//计算比例
        'requisition_method'=>'fd_370502b26f2ec4',//请款方式
        'discount_total'=>'fd_370339d1d90c3a',//总优惠
        'freight_total'=>'fd_370339d5f1f0da',//总运费
        'purchase_acccount'=>'fd_370339d2a26812',//账号
        'pay_price'=>'fd_370339d6c4ee54',//请款总金额
        'pai_number'=>'fd_370339d397fc9c',//拍单号
        'pur_number'=>'fd_3704d02df2c5e8',//合同号
    ],
];
