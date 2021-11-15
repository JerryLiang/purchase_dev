<?php

/**
 * Created by PhpStorm.
 * 银行卡管理表模块
 * User: Jackson
 * Date: 2019/02/14
 */
class Bank_cart_manager_model extends Purchase_model
{

    protected $table_name = 'bank_card';

    public function __construct()
    {
        parent::__construct();

    }
    
}