<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
return $config['api_caigou_sys_lakala_pay'] = array(
    '_baseUrl' => '/finance/Lakala_pay',
    '_get_list' => '/LaKaLa_list', //数据列表
    '_submission' => '/lakala_submission', //提交
    '_audit_list' => '/audit_list', //批量审核显示
    '_reject' => '/lakala_reject', //驳回
    '_registry' => '/batchPay_registry', //驳回
    '_lakala_export' => '/lakala_export', //导出
    '_refresh_pay_status' => '/refresh_pay_status', //查询交易状态
);
