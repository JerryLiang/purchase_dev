<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/1/29
 * Time: 15:45
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_ali_order'] = array(
    '_baseUrl'                   => '/ali/ali_order',
    '_oneKeyOrderPreviewUrl'     => '/one_key_order_preview',
    '_doOneKeyOrderUrl'          => '/do_one_key_order',
    '_getAliOrderNewestPriceUrl' => '/get_ali_order_newest_price',
    '_oneKeyOrderConfirmUrl'     => '/one_key_order_confirm',
    '_oneKeyOrderSubmitUrl'      => '/one_key_order_submit',
    '_updateAliReceivingAddressUrl'      => '/update_ali_receiving_address',
    '_getAliReceivingAddressUrl'      => '/get_ali_receiving_address',
    '_getCancelAliOrderUrl'      => '/get_cancel_ali_order',
    '_refreshOrderPrice'         => '/refresh_order_price',
    '_autoOneKeyOrderSubmit'     => '/auto_one_key_order_submit',
    '_aliBatchEditOrder'         => '/ali_batch_edit_order',
    '_aliBatchSubmitOrder'       => '/ali_batch_submit_order',
    '_getOrderSkuInfos'          => '/get_order_sku_infos',
    '_batchOneKeyOrderPreview'    => '/batch_one_key_order_preview',
    '_batchDoOneKeyOrder'         => '/batch_do_one_key_order',
    '_getRefreshAliOrderDataUrl' => '/refresh_ali_order_data',
    '_getAliOrderJustInTime'      => '/get_ali_order_just_in_time',
    '_verifyAliProductEffective' => '/verify_ali_product_effective',
);
