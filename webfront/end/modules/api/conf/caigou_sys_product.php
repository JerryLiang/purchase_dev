<?php
/**
 * Created by PhpStorm.
 * User: Jackson
 * Date: 2019/1/21
 * Time: 14:19
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_product'] = array(
    '_baseUrl' => '/product/product',
    '_listUrl' => '/product_list',//获取列表数据
    '_exportUrl' => '/product_export',//导出csv
    '_exportExcelUrl' => '/product_export_excel',//导出excel
    '_updateproductUrl' => '/update_products',//修改数据
    '_updatesupplystatusUrl' => '/update_supply_status',//修改货源状态
    //'_examineUrl' => '/examine',//审核
//    '_confirmUrl' => '/confirm',//更新数据
//    '_meterialUrl' => '/get_meterial',//更新数据


    '_getSkuDetailPageUrl' => '/product_api/get_sku_detail_page',//修改货源状态
    '_updateProductApi'=>'/update_product_list',
    '_updateProducsavetApi'=>'/update_product_save',
    '_getSkuListApi'=>'/get_sku_list',//模糊查询获取sku
    '_getMinOrderQtyLog' => '/get_min_order_qty_log',//产品最小起订量变更的日志记录
    '_get_product_purchase' => '/get_product_purchase', // 获取SKU 相关信息
    '_get_product_price_log' => '/get_product_price_log', // 商品SKU 单价日志
    '_import_product'        => '/get_import_product',
    '_get_product_config'    => '/get_product_config',
    '_get_product_reason'    => '/get_product_reason',
    '_add_product_reason'    => '/add_product_reason',
    '_add_reason'            => '/add_reason',
    '_get_reason_list'       => '/get_reason_list',

    '_get_produt_attribute'  => '/get_attribute',
    '_getProductSize'        => '/getProductSize',
    '_updateProductSize'     => '/updateProductSize',
    '_getSize'               => '/getSize',

    '_get_produt_attribute'  => '/get_attribute',

    '_get_produt_attribute'  => '/get_attribute',
    '_historyListUrl' => '/history_product_list',//获取列表数据
    '_historyExportUrl' => '/history_product_export',//导出csv
    '_get_selectbysku'  => '/get_selectbysku', // sku详情页，增加商品参数，需从新产品系统获取
    '_get_drop_box'  => '/get_drop_box',
    '_rateLogs' => '/rateLogs',
    '_ticketedPoint' => '/ticketedPoint',
    '_get_set_table_header' => '/get_set_table_header',
    '_save_table_list' => '/save_table_list',
    '_get_headerlog' => '/get_headerlog',
    '_push_product_import'        => '/import_product_data',


);
