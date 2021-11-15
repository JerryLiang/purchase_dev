<?php
/**
 * Created by PhpStorm.
 * Token
 * User: Jolon
 * Date: 2019/01/16 0029 11:50
 */
class Token_model extends Purchase_model {
    protected $table_name   = 'token';// 数据表名称


    public function __construct(){
        parent::__construct();
    }

    /**
     * 获取Token
     * @author Jaden
     * @return array
     */
    public function get_token(){
        $token_info = $this->purchase_db->order_by('id desc')->get($this->table_name)->row_array();
        return $token_info;

    }

    
}