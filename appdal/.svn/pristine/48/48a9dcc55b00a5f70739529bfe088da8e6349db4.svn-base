<?php

/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2018/12/27 0027 11:23
 */
class Export_mongo_model extends Purchase_model
{

    public function get_purchase_number($company_number)
    {
        $compact_number=trim($company_number);
        $pur_numbers = $this->purchase_db->query("SELECT purchase_number FROM pur_purchase_compact_items WHERE bind=1 and compact_number='{$compact_number}'")->result_array();
        $pur_numbers = !empty($pur_numbers)?array_column($pur_numbers,'purchase_number'):[PURCHASE_NUMBER_ZFSTATUS];
        return $pur_numbers;
    }
}