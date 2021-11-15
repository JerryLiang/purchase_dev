<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/10/30
 * Time: 21:35
 */
class Logistics_carrier_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'logistics_carrier';
    }

    public function getLogisticsCompany()
    {
        $company = $this->db->select('carrier_name,carrier_code')
            ->get($this->table_name)
            ->result_array();
        if (!empty($company)){
            $company = array_column($company,'carrier_name','carrier_code');
        }
        return $company;
    }

    public function get_express_info()
    {
        $result = $this->db->select('carrier_name,carrier_code')->get($this->table_name)->result_array();
        if (!empty($result)){
            $result = array_column($result,'carrier_code','carrier_name');
        }
        return $result;
    }
}