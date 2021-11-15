<?php

$config['api_caigou_sys_supplier_balance'] = array(
// ****************** 供应商应付余额报表 ******************
    '_baseUrl' => '/statement/supplier_balance',
    '_day_list' =>'/day_supplier_balance_list',//日
    '_month_list' =>'/month_supplier_balance_list',//月
    '_quarter_list'=>'/quarter_supplier_balance_list',//季度
    '_year_list' =>'/year_supplier_balance_list',//年
    '_balance_export' =>'/balance_export',//导出
    '_purchase_agent'=>'/get_purchase_agent_info',//供应商主体
    '_getStatisticList' => '/get_statistic_list',

);
