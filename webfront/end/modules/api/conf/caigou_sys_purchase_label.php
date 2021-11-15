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

$config['api_caigou_sys_purchase_label'] = array(
    '_baseUrl' => '/purchase/Purchase_label',
    '_searchUrl' => '/get_status_lists',//获取采购单搜索部分
    '_listLabelUrl'=>'/get_label_list',//物流标签列表
    '_listBarcodeUrl'=>'/get_barcode_list',//产品条码标签部分
    '_exportLabelUrl' => '/export_label',//导出物流标签
    '_exportBarcodeUrl' => '/export_barcode',//导出条码标签
    '_providerPromiseBarcode'=>'/provider_promise_barcode',
     '_sendWmsCombineLabel'=>'/send_wms_combine_label',
     '_sendWmsLabel'=>'/send_wms_label',
    '_sendProviderLabel'=>'/send_provider_label',
    '_listCombineUrl'=>'/get_combine_list',
    '_exportCombineUrl'=>'/export_combine'//导出二合一标签

);
