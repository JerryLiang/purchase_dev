<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
return $config['api_caigou_sys_purchase_return_receivable_list'] = [
    '_baseUrl' => '/purchase_return/purchase_return_receivable',
    '_list' => '/get_list',//货跟踪list
    '_items' => '/get_return_receivable_items',//详情
    '_click_receivables' => '/click_receivables',//财务收款
    '_export_receivables' => '/export_receivables_list', //导出
    '_modify_receiving_account' => '/modify_receiving_account' //修改账号弹窗

];
