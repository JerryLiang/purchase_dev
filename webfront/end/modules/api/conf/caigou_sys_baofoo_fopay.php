<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
return $config['api_caigou_sys_baofoo_fopay'] = array(
    '_baseUrl' => '/finance/Baofoo_fopay',
    '_getBaofoapylistApi' => '/baofoo_list', //数据列表
    '_getBaosubApi' => '/baofoo_submission', //提交
    '_batch_infoApi' => '/baofoo_batch_info', //批量显示
    '_baofoo_fopayApi' => '/baofoo_fopay', //宝付在线支付
    '_baofoo_rejectfopayApi'=>'/baofoo_fopay_reject',//宝付驳回
    '_getBaofoapylistexportApi'=>'/boofoo_fopay_export',//宝付导出
    '_update_pay'=>'/update_pay_baofoo_status',//宝付导出
);
