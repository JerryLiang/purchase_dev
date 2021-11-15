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

$config['api_caigou_sys_purchase_order_tax'] = array(
    '_baseUrl' => '/purchase/purchase_order_tax',
    '_listUrl' => '/tax_order_tacking_list',//获取列表数据
    '_exportUrl' => '/drawback_order_export',//导出
    '_updateproductUrl' => '/update_products',//修改数据
    '_warehousingUrl' => '/warehousing_list',//入库列表
    '_declareUrl' => '/declare_customs',//点击已报关数量弹出的列表
    '_invoicedlUrl' => '/invoiced_list',//点击已开票弹出的列表
    '_libraryUrl' => '/library_age_list',//点击库龄弹出列表
    '_generateinvoicedlUrl' => '/generate_invoice_list',//点击库龄弹出列表
    '_createinvoicelistingUrl' => '/batch_create_invoice_listing',//生成发票清单
    '_exportListUrl' => '/export_list',//导出
    '_get_purchase_review' => '/purchase_review',
    '_reject' => '/reject',
    '_uplodeImage' => '/uplodeImage',
    '_getImage' => '/getImage',
    '_getLogs' => '/getLogs',
    '_tax_order_tacking_sum' =>'/tax_order_tacking_sum'
);
