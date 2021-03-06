<?php
/**
 * Created by PhpStorm.
 * User: Jaxton
 * Date: 2019/1/29
 * Time: 15:45
 *
 * 采购系统公司的接口URL配置文件
 * 主机名的配置在 config/host_confnig.php 中
 */

$config['api_caigou_sys_abnormal_list'] = array(
    '_baseUrl' => '/abnormal/abnormal_list',
    '_listUrl' => '/get_abnormal_list',//获取列表数据
    '_buyer_handleUrl' => '/buyer_handle',//采购员处理提交
    '_rejectUrl' => '/abnormal_reject',//驳回
    '_lookUrl' => '/look_abnormal',//查看
    '_provinceUrl' => '/get_province',//获取省列表
    '_city_countyUrl' => '/get_city_county',//获取市(区)
    '_abnormal_exportUrl' => '/abnormal_export',//导出异常列表
    '_getoperator_logUrl' => '/get_abnormal_operator_log',//异常单操作日志
    '_get_sum_dataUrl' => '/get_sum_data',//异常单统计数据
    '_addAbnormalNoteUrl' => '/add_abnormal_note',//驳回
    '_isOrderExistUrl' => '/is_order_exist',//判断采购单是否存在

    '_get_headerlog' => '/get_headerlog',
    '_save_table_list' => '/save_table_list',

    '_batchBuyerHandleUrl' => '/batch_buyer_handle',//采购员批量处理提交
    '_analysisReturnAddress'=>'/analysis_return_address'
);
