<?php
/**
 * Created by PhpStorm.
 * 产品基础信息表
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Product_update_log_model extends Purchase_model {
    protected $table_name   = 'product_update_log';// 数据表名称

    /**
     * 获取产品的基本信息
     * @author Jaden
     * @param string $sku SKU
     * @return array|bool
     */
    public function get_product_log_info($sku){
        if(empty($sku)) return false;

        $where = ['sku' => $sku];
        $product_info = $this->purchase_db->where($where)->order_by('create_time','desc')->get($this->table_name)->row_array();

        return $product_info;

    }

    /**
     * 获取 最新一条待审核的修改日志
     * @author Jaden
     * @param string $sku SKU
     * @return array|bool
     */
    public function get_latest_waiting_audit_log($sku){
        if(empty($sku)) return [];

        $product_info = $this->purchase_db->where('sku',$sku)
            ->where_in('audit_status',[3,4])
            ->order_by('create_time','desc')
            ->get($this->table_name)->row_array();

        return $product_info;
    }


    /**
     * 保存数据
     * @author Jaden
     * @param array $save_data 更新数据
     * @return array|bool
     */
    public function save_product_log_info($save_data){
        if(empty($save_data)) return false;

        // 审核通过的记录 设置默认审核人
        if(isset($save_data['audit_status']) and $save_data['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS){
            if(!isset($save_data['audit_user_id']) or empty($save_data['audit_user_id'])) $save_data['audit_user_id'] = 1;
            if(!isset($save_data['audit_user_name']) or empty($save_data['audit_user_name'])) $save_data['audit_user_name'] = 'admin';
            if(!isset($save_data['audit_time']) or empty($save_data['audit_time'])) $save_data['audit_time'] = date('Y-m-d H:i:s');
        }
        $result = $this->purchase_db->insert($this->table_name,$save_data);
        if( isset($save_data['reason'])) {
            $logid = $this->purchase_db->insert_id($this->table_name);
            $reason_message = explode(",", $save_data['reason']);
            foreach( $reason_message as $key=>$value )
            {
                $data = array(

                    'log_id' =>$logid,
                    'reason' => $value
                );

                $this->purchase_db->insert('log_reason',$data);
            }
        }


        return $result?true:false;

    }


    /**
     * 更新 产品信息
     * @author Jaden
     * @param $sku
     * @param $update_data
     * @return bool
     */
    public function update_product_log_one($sku,$update_data){

        if(empty($sku) or empty($update_data)){
            return false;
        }
        $product_log_info = $this->get_product_log_info($sku);
        $this->purchase_db->where('sku',$sku);
        $this->purchase_db->where('id',$product_log_info['id']);
        $result = $this->purchase_db->update($this->table_name,$update_data);
        return $result?true:false;
    }


    /**
     * 判断 SKU 是否存在 待审核的记录
     * @author Jolon
     * @param $sku
     * @return bool true.存在待审核的记录，false.不存在
     */
    public function check_in_audit($sku){
        if(empty($sku)) return false;

        $product_log_info = $this->get_product_log_info($sku);//根据SKU查找产品修改记录
        if(empty($product_log_info)) return false;

        if (in_array($product_log_info['audit_status'], [PRODUCT_UPDATE_LIST_AUDITED, PRODUCT_UPDATE_LIST_QUALITY_AUDIT, PRODUCT_UPDATE_LIST_FINANCE])) {
            return true;
        }else{
            return false;
        }
    }

}