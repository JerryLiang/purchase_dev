<?php
/**
 * 预测计划单数据库模型类
 * User: Jaxton
 * Date: 2019/01/03 17:23
 */

class Forecast_plan_model extends Purchase_model {

    protected $table_name   = 'purchase_suggest';// 数据表名称

    public function __construct(){
        parent::__construct();

        $this->load->helper('status_order');
    }


    /**
    * 获取符合条件的预测单
    * @param $params
    * @param $offset
    * @param $limit
    * @return array   
    * @author Jaxton 2019-1-4
    */
    public function get_forecast_list_all($params,$offset,$limit,$page=1){
        $this->purchase_db->select('id,sku,product_name,product_img_url,product_line_name,supplier_name,demand_number,
            buyer_name,purchase_amount,purchase_unit_price,purchase_total_price,warehouse_name,is_drawback');
        $this->purchase_db->from($this->table_name);     
        $this->purchase_db->where('demand_type_id',PURCHASE_DEMAND_TYPE_FORECAST);
        if(isset($params['sku']) && !empty($params['sku'])){
            $sku_arr=query_string_to_array($params['sku']);
            $this->purchase_db->where_in('sku',$sku_arr);
        }
        if(isset($params['buyer_id']) && !empty($params['buyer_id'])){
            $this->purchase_db->where('buyer_id',$params['buyer_id']);
        }
        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('supplier_code',$params['supplier_code']);
        }
        if(isset($params['product_line_id']) && !empty($params['product_line_id'])){
            $this->purchase_db->where('product_line_id',$params['product_line_id']);
        }
        if(isset($params['warehouse_code']) && !empty($params['warehouse_code'])){
            $this->purchase_db->where('warehouse_code',$params['warehouse_code']);
        }
        if(isset($params['is_drawback'])  && is_numeric($params['is_drawback']) && $params['is_drawback']>=0){
            $this->purchase_db->where('is_drawback',$params['is_drawback']);
        }
        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        $this->purchase_db=$clone_db;
        $result=$this->purchase_db->order_by('id','DESC')->limit($limit,$offset)->get()->result_array();
        $return_data = [
            'data_list'   => [
                'value' => $result,
                'key'   => [
                    'ID','图片','产品信息','需求信息','采购数量','采购单价','采购金额','目的仓','是否退税','反馈'
                ],
                'drop_down_box' =>[
                    'supplier_list' => $this->get_supplier_down_box(),
                    'is_drawback'  => getIsDrawback(),
                    'warehouse_list' => $this->get_warehouse_down_box(),
                    'user_list'   => $this->get_user_down_box(),
                    'product_line_list' => $this->get_product_line_down_box()
                ]
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit
            ]
        ];
        return $return_data;
    }

    /**
    * 格式化数据
    * @return array   
    * @author Jaxton 2019/01/21
    */
    public function format_forecast_list($data_list){
        if(!empty($data_list)){
            foreach($data_list as $key => $val){
                $data_list[$key]['is_drawback']=getIsDrawback($val['is_drawback']);
                $data_list[$key]['product_jump_url'] = jump_url_product_base_info($val['sku']);
            }
        }
        return $data_list;
    }

    /**
    * 获取仓库下拉
    * @return array   
    * @author Jaxton 2019/01/21
    */
    public function get_warehouse_down_box(){
        return [
            'SZW' => '深圳仓',
            'DGW' => '东莞仓'
        ];
    }

    /**
    * 获取用户下拉
    * @return array   
    * @author Jaxton 2019/01/21
    */
    public function get_user_down_box(){

        $this->load->model('user/purchase_user_model');
        $data_list = $this->purchase_user_model->get_list();
        $data_list = array_column($data_list,'name','id');

        return $data_list;
    }

    /**
    * 获取供应商下拉
    * @return array   
    * @author Jaxton 2019/01/21
    */
    public function get_supplier_down_box(){
        $list=$this->purchase_db->select('supplier_code,id,supplier_name')
        ->from('supplier')
        ->get()->result_array();
        $new_data=[];
        if($list){
            foreach($list as $k => $v){
                $new_data[$v['id']]=$v['supplier_name'];
            }
        }
        return $new_data;
    }

    /**
    * 获取产品线下拉
    * @return array   
    * @author Jaxton 2019/01/21
    */
    public function get_product_line_down_box(){
        $this->load->model('product/product_line_model');

        $list = $this->product_line_model->get_product_line_list_first();
        $new_data=[];
        if($list){
            foreach($list as $k => $v){
                $new_data[$v['product_line_id']]=$v['linelist_cn_name'];
            }
        }
        return $new_data;
    }

}