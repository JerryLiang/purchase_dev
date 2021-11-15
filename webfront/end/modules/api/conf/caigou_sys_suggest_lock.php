<?php
/**
 * /api/purchase_suggest/purchase_suggest/xxx
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/29
 * Time: 14:22
 */
return $config['api_caigou_purchase_suggest'] = [
    "_baseUrl"           => "",
    '_getListApi'        => '/purchase_suggest/suggest_lock/get_list',//获取配置列表
    '_createLockApi' => '/purchase_suggest/suggest_lock/create_lock',//创建配置

];