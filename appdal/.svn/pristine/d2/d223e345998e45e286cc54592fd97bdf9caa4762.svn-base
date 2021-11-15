<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Cancal_model extends Purchase_model {
    protected $table_name   = 'purchase_order_cancel';
    protected $table_detail = 'purchase_order_cancel_detail';

    public function __construct(){
        parent::__construct();
        $this->load->model('Reject_note_model');
    }


    /**
     * 判断取消未到货主表是否存在
     * @author harvin
     * @date   2019-7-26
     * @param type $cancal_id
     * @return boolean
     */
    public function get_cancal_list($cancal_id){
        $cancal = $this->purchase_db
            ->select('cancel_number')
            ->where('id', $cancal_id)
            ->get($this->table_name)
            ->result_array();
        if(empty($cancal)){
            return false;
        }else{
            return true;
        }

    }


    public function cancal_save($id, array $data = []){
        if(empty($id)){
            return ['msg' => '参数id不存在', 'code' => '300'];
        }
        if(empty($data)){
            return ['msg' => '创建数据不存在', 'code' => '300'];
        }
        try{
            //开始事物
            $this->purchase_db->trans_begin();
            //记录主表信息
            $data_cancal = isset($data['cancel']) ? $data['cancel'] : [];
            if(empty($data_cancal)){
                throw new Exception('reids 获取主表信息失败');
            }
            $data_cancal['id'] = $id;

            $reslut = $this->purchase_db->insert($this->table_name, $data_cancal);
            if(empty($reslut)){
                throw new Exception('记录主表信息失败');
            }
            //保存明细表
            $cancel_detail = isset($data['cancel_detail']) ? $data['cancel_detail'] : [];
            if(empty($cancel_detail)){
                throw new Exception('reids 获取明细表信息失败');
            }
            foreach($cancel_detail as $row){
                $detail = $this->purchase_db->insert($this->table_detail, $row);
                if(empty($detail)){
                    throw new Exception('记录明细表信息失败');
                }
                $purchase_number[] = $row['purchase_number'];
            }
            $purchase_number = array_unique($purchase_number);
            $this->load->model('purchase/Purchase_order_model');
            $res = $this->Purchase_order_model->change_status($purchase_number);// 统一入口修改采购单状态
            if(empty($res)){
                throw new Exception('更新采购单状态失败');
            }
            if($this->purchase_db->trans_status() === false){
                $this->purchase_db->trans_rollback();

                return ['msg' => '操作失败', 'code' => '300'];
            }else{
                $this->purchase_db->trans_commit();

                return ['msg' => '操作成功', 'code' => '200'];
            }
        }catch(Exception $exc){
            $this->purchase_db->trans_rollback();

            return ['msg' => $exc->getMessage(), 'code' => '300'];
        }
    }

}