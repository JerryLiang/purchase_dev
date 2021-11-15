<?php
/**
 * 产品信息不全数据库模型类
 * User: Jaden
 * Date: 2019/01/03 17:23
 */

class Product_incomplete_model extends Purchase_model {

    protected $table_name   = 'product_incomplete';// 数据表名称

    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取符合条件的产品信息
     * @author Jaden
     */
    public function get_incomplete_list_all($params,$offset,$limit,$export=false){
        $field='id,sku,product_img_url,product_line_name,product_name,unit_price,point,tax_rate,supplier_name,remarks,add_time,intercept_reason';
        $query_builder = clone $this->purchase_db;
        $query_builder->select($field);
        $query_builder->from($this->table_name);
        if(isset($params['id']) and is_array($params['id'])){
            $query_builder->where_in('id',$params['id']);
        }
        if(!empty($params['sku'])){
            //要批量查询
             $sku= explode(' ', $params['sku']);
             $sku= array_filter($sku);
             $query_builder->where_in('sku',$sku);
        }
        if(!empty($params['buyer_id'])){
            $this->load->model('supplier/supplier_buyer_model');
            $supplier_buyer_list = $this->supplier_buyer_model->get_list_by_buyer_id($params['buyer_id']);
            if(!empty($supplier_buyer_list)){
                $supplier_code_arr = array_column($supplier_buyer_list, 'supplier_code');
                $query_builder->where_in('supplier_code',$supplier_code_arr);
            }
        }
        if(!empty($params['product_line_id'])){
            $query_builder->where('product_line_id',$params['product_line_id']);
        }

        if(isset($params['start_add_time']) && !empty($params['start_add_time'])){
            $query_builder->where('add_time>=',$params['start_add_time']);
        }
        if(isset($params['end_add_time']) && !empty($params['end_add_time'])){
            $query_builder->where('add_time<=',$params['end_add_time']);
        }


        if(!empty($params['intercept_reason'])){
            $query_builder->like('intercept_reason',$params['intercept_reason']);
        }

        $clone_db = clone($query_builder);
        $total=$clone_db->count_all_results();//符合当前查询条件的总记录数 
        if($export){//导出不需要分页查询
            $result=$query_builder->order_by('add_time','DESC')->get()->result_array();
        }else{//列表查询
            $result=$query_builder->order_by('add_time','DESC')->limit($limit,$offset)->get()->result_array();    
        }
        
        //echo $query_builder->last_query();exit;
        $return_data = [
            'value'   => $result,
            'page_data' => [
                'total'     => $total,
                'offset'    => $offset,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }


    //添加备注
    public function incomplete_create_remarks($params){
        if(empty($params) || !is_array($params)){
            $return['msg'] = '数据有误';
            return $return;
        }
        if(empty($params['id']) || empty($params['remarks'])){
            $return['msg'] = '数据不能为空';
            return $return;  
        }
        $incomplete_info = $this->get_incomplete_info($params['id']);
        if(empty($incomplete_info)){
            $return['msg'] = 'ID不存在';
            return $return;
        }else{
            $update_data['remarks'] = $params['remarks'];
            $result = $this->purchase_db->where('id',$params['id'])->update($this->table_name,$update_data);
            if($result){
                $return['code'] = true;
            }else{
                $return['msg'] = '添加失败，请稍后再试';
            }
        }
        return $return;


    }


    /**
     * 获取基本信息
     * @author Jaden
     * @param string $sku SKU
     * @return array|bool
     */
    public function get_incomplete_info($id){
        if(empty($id)) return false;

        $where = ['id' => $id];
        $incomplete_info = $this->purchase_db->where($where)->get($this->table_name)->row_array();

        return $incomplete_info;

    }


}