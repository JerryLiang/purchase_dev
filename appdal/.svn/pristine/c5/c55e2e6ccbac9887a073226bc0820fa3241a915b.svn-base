<?php
/**
 * Created by PhpStorm.
 * 接收仓库异常数据
 * User: Jaden
 * Date: 2019/02/12 
 */
class Purchase_abnomal_model extends Purchase_model {

    protected $table_name   = 'purchase_abnomal';

    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function tableName() {
        return 'purchase_abnomal';
    }


   /**
     * 根据条件获取数据
     * @author Jaden
     *  @param string $where  
     * @return bool
     * 2019-1-16
     */
    public function getBywhere($where){
        if(empty($where)) {
            return false;
        }    
        $this->purchase_db->where($where);
        $results_data = $this->purchase_db->get($this->table_name)->row_array();
        return $results_data;
    }


    /**
     * 根据条件更新数据
     * @author Jaden
     * @param string $where  
     * @param array $update_datas 
     * @return bool
     * 2019-1-16
     */
    public function updateBywhere($where,$update_datas){
        if(empty($where) || empty($update_datas)) {
            return false;
        }    
        $this->purchase_db->where($where);
        $status = $this->purchase_db->update($this->table_name, $update_datas);
        return $status;
    }



    /**
     * 插入数据
     * @author Jaden
     *  @param array $insert_datas  
     * @return bool
     * 2019-1-16
     */
    public function insert_data($insert_datas){
        if(empty($insert_datas)) {
            return false;
        }
        $status =$this->purchase_db->insert($this->table_name, $insert_datas);
        return $status;
    }



}