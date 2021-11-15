<?php

/**
 * Class Bank_info_model
 * 银行列表
 * User: Jolon
 */
class Bank_info_model extends Purchase_model {

    protected $table_name = 'bank_info';// 数据表名称


    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取银行名称
     * @param $bank_code
     * @return array
     */
    public function get_one_bank_info($bank_code) {
        $query = $this->purchase_db->select('master_bank_name')
            ->where('bank_code',$bank_code)
            ->get($this->table_name)
            ->row_array();
        return empty($query)?[]:$query;
    }

    /**
     * 获取银行信息
     * @param string $branch_bank_name  支行名称
     * @return array
     */
    public function get_one_bank_info_by_branch_bank_name($branch_bank_name){
        $query = $this->purchase_db->select('*')
            ->where('branch_bank_name',$branch_bank_name)
            ->get($this->table_name)
            ->row_array();
        return empty($query)?[]:$query;
    }


}
