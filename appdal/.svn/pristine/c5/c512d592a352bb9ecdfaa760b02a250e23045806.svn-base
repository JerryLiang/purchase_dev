<?php

class Handle_track_model extends Purchase_model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Logistics_info_model');
    }


    /**
     * 更新物流单状态
     */
    public function update_track_status($express_no, $status)
    {
        $res = ["code" => 0, "msg" => '更新失败'];
        if($this->purchase_db->where(["id" => $express_no])->update("purchase_logistics_info", ["status" => $status])){
            $res['code'] = 1;
            $res['msg'] = '更新成功！';
        };
        return $res;
    }


    public function get_list($day=0)
    {
        $sql = "SELECT
            l.express_no,
            l.carrier_code,
            m.id	
        FROM
            pur_purchase_logistics_info AS l
            LEFT JOIN pur_warehouse_results_main AS m ON l.purchase_number = m.purchase_number AND l.sku = m.sku 
        WHERE
            l.`status` = 0 
            AND l.express_no != '' 
            AND l.carrier_code != '' 
            AND l.create_time BETWEEN '{$day} 00:00:00' 
            AND '{$day} 23:59:59' 
            AND m.id IS NULL 
        GROUP BY
            l.express_no;";
        return $this->purchase_db->query($sql)->result_array();
    }
}