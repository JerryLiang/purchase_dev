<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/2/24
 * Time: 15:09
 */

class Kd100_api_model extends Purchase_model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function get_kd_order()
    {
        $result = $this->purchase_db->from("kd100_test")
            ->where(['is_push' => 0,
                    'company_code <>' => '',
                ]
            )
            ->limit(100)
            ->get()->result_array();
        return $result;
    }

    public function get_auto_query_order()
    {
        $result = $this->purchase_db->from("kd100_test")
            ->where(['is_push' => 1,
                    'status' => 0,
                ])
            ->where("query_time <= DATE_SUB(NOW(),INTERVAL 2 HOUR) ")
            ->limit(100)
            ->get()->result_array();
        return $result;
    }

    public function update_kd_order($where, $set_data)
    {
        return $this->purchase_db->update('kd100_test', $set_data, $where);
    }
}