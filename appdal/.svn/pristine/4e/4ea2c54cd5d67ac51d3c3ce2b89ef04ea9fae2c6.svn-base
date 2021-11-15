<?php

/**
 * 线下退款原因配置
 * Class Reason_config_model
 * @author Jolon
 * @date 2021-01-12 14:50:01
 */

class Offline_reason_model extends Purchase_model {
    
      protected $table_name = 'offline_receipt_reason';


    public function __construct() {
        parent::__construct();
        $this->load->helper('status_order');
    }

    /**
     * 获取 线下退款原因配置 数据列表
     */
    public function get_offline_reason(){
        $list = $this->purchase_db->select('*')
            ->where('refund_reason !=""')
            ->order_by('FIELD(id, 18,5) DESC')
            ->get($this->table_name)
            ->result_array();

        return $list;
    }

    /**
     * 获取 线下退款原因配置 数据列表(获取可用的)
     */
    public function get_offline_select_reason($status){
        $list = $this->purchase_db->select('*')->where("status",$status)
            ->order_by('FIELD(id, 18,5) DESC')
            ->get($this->table_name)->result_array();

        return $list;
    }


    /**
     * 获取 退款类型 下拉框选择
     */
    public function get_refund_type_list(){
        $list = $this->purchase_db->select('refund_type')
            ->group_by('refund_type')
            ->get($this->table_name)
            ->result_array();

        $list = array_column($list,'refund_type','refund_type');

        return $list;
    }

    /**
     * 获取 退款原因 下拉框选择
     */
    public function get_refund_reason_list(){
        $list = $this->purchase_db->select('refund_reason')
            ->where('refund_reason !=""')
            ->where('status',1)
            ->group_by('refund_reason')
            ->get($this->table_name)
            ->result_array();

        $list = array_column($list,'refund_reason','refund_reason');

        return $list;
    }

    /**
     * 新增退款类型
     * @param string $refund_type 退款类型
     * @return bool true.成功，false.失败
     */
    public function create_refund_type($refund_type){
        $exists = $this->purchase_db->where('refund_type' ,$refund_type)
            ->get($this->table_name)
            ->row_array();

        if($exists){
            return true;
        }else{
            $new_reason = ['refund_type' => $refund_type];
            $result = $this->purchase_db->insert($this->table_name,$new_reason);
            if($result){
                return true;
            }else{
                return false;
            }
        }
    }

    /**
     * 新增、编辑 退款原因
     * @param array $params 退款原型
     * @return array
     */
    public function create_refund_reason($params){
        // 先验证退款类型是否存在
        $exists = $this->create_refund_type($params['refund_type']);
        if($exists === false){
            return $this->res_data(false,'退款类型不存在');
        }

        if(isset($params['id']) and !empty($params['id'])){// 存在ID则为更新记录
            $exists = $this->purchase_db->where('id',$params['id'])
                ->get($this->table_name)
                ->row_array();
            if(empty($exists)){
                return $this->res_data(false,'更新的目标数据[id='.$params['id'].']不存在');
            }

            $new_reason = [
                'refund_type' => $params['refund_type'],
                'purchase_number_need' => $params['purchase_number_need'],
                'logistics_number_need' => $params['logistics_number_need'],
                'reason_remark' => $params['reason_remark'],
                'update_time' => date('Y-m-d H:i:s'),
                'update_user_name' => getActiveUserName(),
            ];
            $result = $this->purchase_db->where('id',$exists['id'])->update($this->table_name,$new_reason);

        }else{// 新增记录
            $exists = $this->purchase_db->where('refund_reason',$params['refund_reason'])
                ->get($this->table_name)
                ->row_array();
            if($exists){
                return $this->res_data(false,'退款原因已经存在，不能重复添加');
            }
            $new_reason = [
                'refund_type' => $params['refund_type'],
                'refund_reason' => $params['refund_reason'],
                'purchase_number_need' => $params['purchase_number_need'],
                'logistics_number_need' => $params['logistics_number_need'],
                'reason_remark' => $params['reason_remark'],
                'create_time' => date('Y-m-d H:i:s'),
                'update_user_name' => getActiveUserName(),// 更新人等于创建人
            ];
            $result = $this->purchase_db->insert($this->table_name,$new_reason);
        }


        if($result){
            return $this->res_data(true,'数据保存成功');
        }else{
            return $this->res_data(false,'数据保存失败');
        }
    }


    /**
     * 更新 退款原因 禁用启用状态
     * @param int $id  目标ID
     * @param int $status 目标状态
     * @return array
     */
    public function update_refund_reason_status($id,$status){
        if($status != 1 and $status != 2){
            return $this->res_data(false,'状态非法，必须为禁用或启用');
        }

        $exists = $this->purchase_db->where('id',$id)
            ->get($this->table_name)
            ->row_array();
        if(empty($exists)){
            return $this->res_data(false,'更新的目标数据[id='.$id.']不存在');
        }


        $new_reason = [
            'status' => $status,
            'update_time' => date('Y-m-d H:i:s'),
            'update_user_name' => getActiveUserName(),// 更新人等于创建人
        ];
        $result = $this->purchase_db->where('id',$exists['id'])->update($this->table_name,$new_reason);

        if($result){
            return $this->res_data(true,'数据保存成功');
        }else{
            return $this->res_data(false,'数据保存失败');
        }

    }

}