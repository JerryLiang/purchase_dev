<?php
/**
 * Created by PhpStorm.
 * 缺货列表
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Shortage_model extends Purchase_model {
    protected $table_name   = 'stock_owes';// 缺货列表

    public function __construct(){
        parent::__construct();
    }




    /**
     * 获取 产品列表
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_shortage_list($params, $offset, $limit,$field='*',$export=false){
        $this->purchase_db->select($field);
        $this->purchase_db->from('stock_owes as s');
        $this->purchase_db->join('product as p', 's.sku=p.sku', 'left');

        if(!empty($params['ids'])){
            $this->purchase_db->where_in('s.id', explode(',', $params['ids']));
        }
        if(!empty($params['supplier_code'])){
            $this->purchase_db->where('p.supplier_code',$params['supplier_code']);
        }
        if(!empty($params['sku'])){
            $this->purchase_db->like('s.sku',$params['sku']);
        }
        if(isset($params['product_status']) and $params['product_status']!==''){

            if( is_array($params['product_status'])) {
                $this->purchase_db->where_in('p.product_status', $params['product_status']);
            }else{
                $this->purchase_db->where('p.product_status', $params['product_status']);
            }

        }
        if(!empty($params['create_id'])){
            $this->purchase_db->where('p.create_id',$params['create_id']);
        }
        if(!empty($params['time_node'])){
            $time_node_date = date('Y-m-d H:i:s',time()-60*60*$params['time_node']);
            $this->purchase_db->where('s.earlest_outofstock_date<=', $time_node_date);
        }
        if(!empty($params['buyer_name'])){
            $supplier_code = $this->_db->query("select DISTINCT(`supplier_code`) as supplier_code from pur_supplier_buyer where buyer_id IN (".implode(",",$params['buyer_name']).")")
                                        ->result_array();
            $supplier_code_arr = array_column($supplier_code,'supplier_code');
            if(!empty($supplier_code_arr)) {
                $this->purchase_db->where_in('p.supplier_code',$supplier_code_arr);
            }else{
                $this->purchase_db->where_in('p.supplier_code',['no']);
            }
        }

        //更新时间排序
        if (isset($params['lack_update_time_order']) && $params['lack_update_time_order'] && in_array($params['lack_update_time_order'],['asc','desc'])){
            $this->_db->order_by('think_statis_time',$params['lack_update_time_order']);
        }

        //缺货数量排序
        if (isset($params['think_lack_qty_order']) && $params['think_lack_qty_order'] && in_array($params['think_lack_qty_order'],['asc','desc'])){
            $this->_db->order_by('think_lack_qty',$params['think_lack_qty_order']);
        }

        //缺货数量
        if(isset($params['left_stock_start']) && $params['left_stock_start'] != ''){
            $this->purchase_db->where('s.think_lack_qty >=',$params['left_stock_start']);
        }
        if(isset($params['left_stock_end']) && $params['left_stock_end'] != ''){
            $this->purchase_db->where('s.think_lack_qty <=',$params['left_stock_end']);
        }

        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        
        $this->purchase_db=$clone_db;
        if($export){//导出不需要分页查询
            $results = $this->purchase_db->get()->result_array();
        }else{//列表查询
            $results = $this->purchase_db->order_by('s.id','desc')->limit($limit, $offset)->get()->result_array();    
        }
        //$this->purchase_db->last_query();
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }

    /**
     * 插入数据
     * @author Jaden
     *  @param array $insert_data  插入数据表的数据
     * @return bool
     * 2019-1-16
     */
    public function insert_shortage_batch_all($insert_data){
        if(empty($insert_data)) {
            return false;
        }    
        $result = $this->purchase_db->insert_batch($this->table_name,$insert_data);
        return $result;
    }


    //删除数据
    public function delete_shortage($skus){
        if(empty($skus)) {
            return false;
        }    
        $result = $this->purchase_db->where_in('sku',$skus)->delete($this->table_name);
        return $result;
    }


    public function get_old_data()
    {
        $result = $this->purchase_db->select('sku,think_lack_qty,think_platform_info')->from($this->table_name);
        return $result;
    }

    public function get_lack_info($sku_list)
    {
        if (empty($sku_list)){
            return [];
        }
        $result = $this->purchase_db->select('sku,think_lack_qty')
            ->from($this->table_name)
            ->where_in('sku',$sku_list)
            ->get()->result_array();
        return empty($result)?[]: array_column($result,NULL,'sku');


    }
}