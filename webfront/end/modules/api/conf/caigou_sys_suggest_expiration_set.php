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
    '_getListApi'        => '/purchase_suggest/suggest_expiration_set/get_list',//获取过期时间列表
    '_editExpirationApi' => '/purchase_suggest/suggest_expiration_set/edit_expiration',//编辑过期时间

];