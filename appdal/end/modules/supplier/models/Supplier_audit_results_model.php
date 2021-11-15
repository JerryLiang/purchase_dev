<?php

/**
 * Created by PhpStorm.
 * 二次包装模型
 * User: Jaden
 * Date: 2019/01/16
 */
class Supplier_audit_results_model extends Purchase_model
{

    protected $table_name = 'supplier_audit_results';

    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function table_nameName()
    {
        return 'supplier_audit_results';
    }

    /**
     * 找到一个待审核的 供应商审核记录
     * @author Jolon
     * @param $supplier_code
     * @return array
     */
    public function get_waiting_audit_record($supplier_code){

        $record = $this->purchase_db->where('supplier_code',$supplier_code)
            ->where('audit_status',SUPPLIER_AUDIT_RESULTS_STATUS_WAITING_AUDIT)
            ->order_by('id','desc')
            ->get($this->table_name)
            ->row_array();

        return $record;
    }

    /**
     * 增加 一个供应商审核记录
     * @author Jolon
     * @param  string   $supplier_code
     * @param int $audit_type
     * @return bool
     */
    public function create_record($supplier_code,$audit_type = 2){
        $this->load->model('supplier/supplier_model');
        $supplier_info = $this->supplier_model->get_supplier_info($supplier_code,false);
        if(empty($supplier_info)) return false;

        $record = [
            'supplier_code'  => $supplier_code,
            'supplier_name'  => $supplier_info['supplier_name'],
            'apply_time'     => date('Y-m-d H:i:s'),
            'audit_status'   => SUPPLIER_AUDIT_RESULTS_STATUS_WAITING_AUDIT,
            'audit_type'     => $audit_type,
            'is_show'        => 2,
            'create_user_id' => getActiveUserId(),
            'create_time'    => date('Y-m-d H:i:s')
        ];

        $res = $this->purchase_db->insert($this->table_name,$record);

        return $res?true:false;
    }

    /**
     * @desc 更新数据(审核)
     * @author Jolon
     * @param        $supplier_code
     * @param int    $audit_status
     * @param string $remarks
     * @return mixed
     */
    public function update_audit($supplier_code,$audit_status = SUPPLIER_AUDIT_RESULTS_STATUS_PASS,$remarks = '')
    {
        $record = $this->get_waiting_audit_record($supplier_code);
        if(empty($record)) return false;

        $audit_used = time() - strtotime($record['apply_time']);// 计算时效（秒）

        $update_record = [
            'audit_status' => $audit_status,
            'remarks'      => $remarks ? $remarks : '审核成功',
            'audit_user'   => getActiveUserName(),
            'audit_time'   => date('Y-m-d H:i:s'),
            'audit_used'   => $audit_used,
            'is_show'      => 1,
        ];

        $res = $this->purchase_db->where('id',$record['id'])
            ->update($this->table_name,$update_record);

        return $res?true:false;
    }

}