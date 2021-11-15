<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 */

class Be_dismissed_model extends Purchase_model {

    /**
     * 获取驳回选择原因
     * @author harvin
     * @date <2019-1-4>
     * * */
    public function get_dismissed() {
        $this->load->helper('status_order');
        $data=  getPurchaseOrderRejectReason();
        return $data;
    }

    /**
     * 更改需求列表状态
     * @param array $ids <参数id>
     * @param string $reject_remark <驳回备注>
     * @param string $reiect_dismissed <驳回原因>
     * @author harvin Date <2019-1-4>
     * * */
    public function get_disnissed_statu($ids, $reject_remark, $reiect_dismissed) {
        $this->load->model('Reject_note_model');
        $query_builder = $this->purchase_db;
        //开启事物
        $query_builder->trans_begin();
        foreach ($ids as $key => $val) {
            $suggest = $query_builder->select('demand_number')->where('id', $val)->from('purchase_suggest')->get()->row();
            $data_pa = [
                'reject_remark' => $reject_remark,
                'reiect_dimsissed' => $reiect_dismissed,
                'reject_type_id' => 1,
                'link_id' => $val,
                'link_code' => $suggest->demand_number
            ];
            //记录驳回信息表
            $this->Reject_note_model->insert_one_note($data_pa);
            //更改需求列表状态
            $data = array('suggest_order_status' => SUGGEST_ORDER_STATUS_ALL_WAITING_AUDIT);
            $where = "id=" . $val;
            $query_builder->where($where)->update('purchase_suggest', $data);
            //记录操作日志
            $res = [
                'record_number' => $suggest->demand_number,
                'record_type' => '需求驳回',
                'content' => $reiect_dismissed,
                'content_detail' => $reject_remark
            ];
            $this->Reject_note_model->get_insert_log($res);
        }
        if ($query_builder->trans_status() === false) {
            //事物回滚
            $query_builder->trans_rollback();
            return false;
        } else {
            //提交事物
            $query_builder->trans_commit();
            return true;
        }
    }

    /**
     * 审核不通过操作
     * @param array $ids <参数id>
     * @param string $reject_remark <驳回备注>
     * @author harvin Date <2019-1-4>
     * * */
    public function get_audit_failed($ids, $reject_remark) {
        $this->load->model('Reject_note_model');
        $query_builder = $this->purchase_db;
        //开启事物
        $query_builder->trans_begin();
        try {
            //记录操作日志
            foreach ($ids as $key => $val) {
                $suggest = $query_builder->select('demand_number')->where('id', $val)->from('purchase_suggest')->get()->row();
                if(empty($suggest)){
                    throw new Exception('参数id 不存在');
                }
                $data = [];
                //更新需求表
                $query_builder->where('id', $val)->update('purchase_suggest', $data);
                //记录操作日志
                $res = [
                    'record_number' => $suggest->demand_number,
                    'record_type' => '需求审核',
                    'content' => '需求驳回审核不通过',
                    'content_detail' => $reject_remark
                ];
                $this->Reject_note_model->get_insert_log($res);
                $data_pa = [
                    'reject_remark' => $reject_remark,
                    'reiect_dimsissed' => '审核不通过',
                    'reject_type_id' => 1,
                    'link_id' => $val,
                    'link_code' => $suggest->demand_number
                ];
                //记录驳回信息表
                $suggest_id = $this->Reject_note_model->insert_one_note($data_pa);
            }
            if ($query_builder->trans_status() === false) {
                //事物回滚
                $query_builder->trans_rollback();
                return ['msg'=>'驳回失败','bool'=>FALSE];
             
            } else {
                //提交事物
                $query_builder->trans_commit();
                return ['msg'=>'驳回成功','bool'=>true];      
            }
        } catch (Exception $exc) {
           $query_builder->trans_rollback();
           return ['msg'=> $exc->getMessage(),'bool'=>true]; 
        }
    }

    /**
     * 审核通过操作
     * @param array $ids <参数id>
     * @param string $reject_remark <驳回备注>
     * @author harvin Date <2019-1-4>
     * * */
    public function get_audit_pass($ids, $reject_remark) {
        $this->load->model('Reject_note_model');
        $query_builder = $this->purchase_db;
        //开启事物
         $query_builder->trans_begin();
        try { 
            foreach ($ids as $val) {
                $suggest = $query_builder->select('demand_number')->where('id', $val)->from('purchase_suggest')->get()->row();
                if(empty($suggest)) throw new Exception ('参数id不存');
                    $data = [];
                //更新需求表
                $query_builder->where('id', $val)->update('purchase_suggest', $data);
                //记录操作日志
                $res = [
                    'record_number' => $suggest->demand_number,
                    'record_type' => '需求审核',
                    'content' => '需求驳回审核通过',
                    'content_detail' => !empty($reject_remark)?$reject_remark:'需求审核'
                ];
                $this->Reject_note_model->get_insert_log($res);
                $data_pa = [
                    'reject_remark' => $reject_remark,
                    'reiect_dimsissed' => '审核通过',
                    'reject_type_id' => 3,
                    'link_id' => $val,
                    'link_code' => $suggest->demand_number
                ];
                //记录驳回信息表
               $this->Reject_note_model->insert_one_note($data_pa);
            }
            if ($query_builder->trans_status() === false) {
                //事物回滚
                $query_builder->trans_rollback();
                 return ['msg'=>'驳回失败','bool'=>FALSE];
            } else {
                //提交事物
                $query_builder->trans_commit();
                return ['msg'=>'驳回成功','bool'=>true];    
            }
        } catch (Exception $exc) {
             $query_builder->trans_rollback();
            return ['msg'=> $exc->getMessage(),'bool'=>true];    
        }
    }

    /**
     * 获取需求单号
     * @param array $ids <参数id>
     * @author harvin  <2019-1-5>
     * @return array $res 返回数组
     * * */
    public function get_demand_number($ids) {
        $res = [];
        foreach ($ids as $val) {
            $suggest = $this->purchase_db->select('demand_number')
                            ->where('id', $val)->from('purchase_suggest')
                            ->get()->row();

            $res[] = $suggest->demand_number;
        }
        return $res;
    }
    /**
     * 判断需求单的状态
     * @param array $ids 数组id
     * @param array  $type 状态数组
     * @return boolean
     * @throws Exception
     **/
    public function get_demand_status($ids, $type){
        if (count(array_unique(array_filter($ids))) != count($ids)) {
           throw new Exception("有重复或非法id");
        }
        $rows = $this->purchase_db->select("id,suggest_status")->where_in("id", $ids)->get("purchase_suggest")->result_array();
        if (count($rows) != count($ids)) {
            throw new Exception("有重复或非法id");
        }
        foreach ($rows as $row) {
            if (!in_array($row['suggest_status'], $type)) {
                return true;
            }
        }
        return false;
        /*$temp=false;
        foreach ($ids as $id) {
            $suggest=$this->purchase_db->select('suggest_status')->where('id',$id)->get('purchase_suggest')->row_array();
            if(!in_array($suggest->suggest_status, $type)){
                $temp=true;
                break;
            }
        }
        return $temp;*/
    }
}
