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

$config['api_caigou_sys_puerchase_unarrived'] = array(
    '_baseUrl' => '/purchase/Puerchase_unarrived',
    '_unarrivedUrl' => '/cancel_unarrived_goods',//获取采购单搜索部分
    '_savelistUrl'=>'/cancel_unarrived_goods_save',//保存取消未到货的操作
    '_cancelAfloatGoodsListApi'=>'/cancel_unarrived_goods_examine_list',//
    '_cancelAfloatGoodsApi'=>'/cancel_unarrived_goods_examine',//
    '_changePurchaserListApi'=>'/change_purchaser_list',//
    '_changePurchaserApi'=>'/change_purchaser',//
     '_cancelApi'=>'/cencel_lits',//
     'screenshotApi'=>'/cancel_upload_screenshots',//   
    '_unarrived_infoApi'=>'/cancel_unarrived_info',
    '_cancelAfloatGoodseditListApi'=>'/cancel_unarrived_goods_edit',
     '_changePurchaserinfoListApi'=>'/cancel_log_info',//
    '_refreshAliRefundApi'=>'/refresh_ali_refund',//根据收款信息拉取1688退款信息
    '_cancelUploadScreenshotsPreviewApi'=>'/cancel_upload_screenshots_preview',//取消未到货 上传截图预览
    'screenshotv2Api'=>'/cancel_upload_screenshots_v2',//上传截图新版20190926
    '_cancel_unarrived_goods_examine_down'=>'/cancel_unarrived_goods_examine_down', // 取消未到货导出
    '_cancelListsSumApi'=>'/get_cancel_lists_sum',//
    '_getCancelUploadData'=>'/get_cancel_upload_data',// 取消未到货批量上传 获取数据
    '_saveCancelUploadData'=>'/save_cancel_upload_data',// 取消未到货批量上传 保存数据

);
