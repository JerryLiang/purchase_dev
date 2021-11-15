<?php
/**
 * Created by PhpStorm.
 * 开票资料表
 * User: Jaden
 * Date: 2019/01/12 0029 11:50
 */
class Invoice_information_model extends Purchase_model {

    protected $table_name   = 'invoice_information';// 数据表名称

    /**
     * 根据 ID获取 采购主体开票信息
     * @author Jaden
     * @param $id
     * @return array|bool
     */
    public function getInformationById($id){
        if(empty($id)){
            return false;
        }
        $row = $this->purchase_db->where('id',$id)->get($this->table_name)->row_array();
        return empty($row)?false:$row;

    }

    /**
     *
     * 根据 采购主体获取 采购主体开票信息
     * @author Jolon
     * @param $purchase_name
     * @return array|bool
     */
    public function getInformationByKey($purchase_name){
        if(empty($purchase_name)){
            return false;
        }
        $row = $this->purchase_db->where('purchase_name',$purchase_name)->get($this->table_name)->row_array();
        return empty($row)?false:$row;
    }


}