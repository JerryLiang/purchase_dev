<?php
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 14:34
 */
return $config['api_caigou_sys_bank_card'] = array(
    '_baseUrl'                  => '',
    '_getScreeListApi'          => '/abnormal/product_scree/get_scree_list',//
    '_screeExportCsvApi'        => '/abnormal/product_scree/scree_export_csv',//
    '_screeCreateApi'           => '/abnormal/product_scree/scree_create',//
    '_screeAuditApi'           => '/abnormal/product_scree/scree_audit',//
    '_affirmSupplierApi'           => '/abnormal/product_scree/affirm_supplier',//
    '_newscreeCreateApi'       =>'/abnormal/product_scree/set_scree_sku',
    '_get_logs'                => '/abnormal/product_scree/get_logs',
    '_update_estimate_time'    => '/abnormal/product_scree/update_estimate_time',
    '_get_scree_estimatetime'  => '/abnormal/product_scree/get_scree_estimatetime',
    '_getPrevData'             => '/abnormal/product_scree/getPrevData',
    '_scree_import_product'          => '/abnormal/product_scree/scree_import_product' // SKU ∆¡±Œµº»Î

);
