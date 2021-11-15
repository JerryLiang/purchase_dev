<?php
/**
 * Created by PhpStorm.
 * 二次包装模型
 * User: Jaden
 * Date: 2019/01/16 
 */
class Product_repackage_model extends Purchase_model {

    protected $table_name   = 'product_repackage';

    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function tableName() {
        return 'product_repackage';
    }

    /**
     * 插入数据
     * @author Jaden
     *  @param array $insert_data  插入数据表的数据
     * @return bool
     * 2019-1-16
     */
    public function insert_batch_all($insert_data){
    	if(empty($insert_data)) {
            return false;
        }    
        $result = $this->purchase_db->insert_batch($this->table_name,$insert_data);
        return $result;
    }

    /**
     * 根据SKU获取数据
     * @author Jaden
     *  @param string $sku  
     * @return bool
     * 2019-1-16
     */
    public function get_one($sku){
        if(empty($sku)) {
            return false;
        }    
        $this->purchase_db->where('sku', $sku);
        $results_data = $this->purchase_db->get($this->table_name)->row_array();
        return $results_data;
    }

    /**
     * 二次包装列表
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-16
     */
    public function get_product_repackage_list($params, $offset, $limit,$field='*',$export=false) {
        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name);
        $this->purchase_db->where('status', 1);//是否删除(1.启用，2.已删除)

        if(!empty($params['audit_status'])){
            $this->purchase_db->where('audit_status', $params['audit_status']);
        }
        if (isset($params['sku']) and $params['sku']) {// SKU
            $sku = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('sku', $sku);
        }

        if(!empty($params['ids'])){
            $this->purchase_db->where_in('id', explode(',', $params['ids']));
        }

        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        //echo $this->purchase_db->last_query();exit;
        $this->purchase_db=$clone_db;
        if($export){//导出不需要分页查询
            $results = $this->purchase_db->order_by('create_time','desc')->get()->result_array();
        }else{
            $results = $this->purchase_db->limit($limit, $offset)->order_by('create_time','desc')->get()->result_array();   
        }
         
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }



}