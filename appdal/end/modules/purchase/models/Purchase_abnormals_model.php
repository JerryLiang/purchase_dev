<?php
/**
 * Created by PhpStorm.
 * 接收仓库异常数据
 * User: Jaden
 * Date: 2019/02/12 
 */
class Purchase_abnormals_model extends Purchase_model {

    protected $table_name   = 'purchase_abnormals';

    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function tableName() {
        return 'purchase_abnormals';
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