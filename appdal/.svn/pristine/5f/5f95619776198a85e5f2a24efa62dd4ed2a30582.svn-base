<?php
/**
 * Created by PhpStorm.
 * 包裹加急
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Parcel_urgent_model extends Purchase_model {
    protected $table_name   = 'logistics_import';// 包裹加急

    public function __construct(){
        parent::__construct();
    }

    public function tableName() {
        return $this->table_name;
    }
    /**
     * 获取 包裹加急
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_logistics_urgent_list($params, $offset, $limit,$field='*',$export=false,$pust_list=false){
        $this->purchase_db->select($field)
            ->from($this->table_name.' a');
        if(!empty($params['order_by_time'])){
            $order_by_time = $params['order_by_time'];    
        }else{
            $order_by_time = 'DESC';
        }
        $this->purchase_db->where('a.is_deleted', 1);//查询未删除的
        if(!empty($params['logistics_num'])){
            $this->purchase_db->join('purchase_logistics_info b','a.purchase_order_num = b.purchase_number','left');
            $this->purchase_db->where('b.express_no', $params['logistics_num']);
            $this->purchase_db->group_by('id');
        }

        if(!empty($params['create_id'])){
            $this->purchase_db->where_in('a.create_id', $params['create_id']);
        }

        if(isset($params['push_status']) and $params['push_status']!==''){
            $this->purchase_db->where('a.push_status', $params['push_status']);
        }
        if(isset($params['ids']) && $params['ids']!=""){

            $ids = explode(',', $params['ids']);

            $this->purchase_db->where_in('a.id',$ids);
        }
        if($pust_list){
            $this->purchase_db->where('a.push_status', 0);
        }

        $this->purchase_db->order_by('a.create_time', $order_by_time);
        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数

        $this->purchase_db=$clone_db;
        if($export){//导出不需要分页查询
            $result = $this->purchase_db->get()->result_array();
        }else{//列表查询
            $result = $this->purchase_db->limit($limit, $offset)->order_by('a.id desc')->get()->result_array();
        }

        if (!empty($result)){
            //根据采购单 获取物流信息
            $purchase_numbers =array_column($result,'purchase_order_num');
            if (!empty($purchase_numbers)){
                $express_info = $this->purchase_db->select('purchase_number,express_no,cargo_company_id')
                    ->from('purchase_logistics_info')
                    ->where_in('purchase_number',$purchase_numbers)
                    ->group_by('purchase_number,express_no')
                    ->get()->result_array();
            }

            if (isset($express_info)&&!empty($express_info)){
                foreach ($express_info as $item){
                    $express_map[$item['purchase_number']][] = [
                      'express_no' => $item['express_no']??'',
                      'cargo_company_id' => $item['cargo_company_id']??'',
                    ];
                }
                foreach ($result as $key => &$item){
                    $item['logistics_info'] = $express_map[$item['purchase_order_num']]??'';
                }
            }

        }
        $return_data = [
            'value'   => $result,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }


    /**
     * 根据 物流单号和采购单号查数据
     * @author Jaden
     * @param $logistics_num
     * @param $purchase_order_num
     * @return array
     * 2019-03-15
     */
    public function get_parcel_urgent_info($logistics_num,$purchase_order_num){
        if(empty($logistics_num) || empty($purchase_order_num)){
            return false;
        }
        $this->purchase_db->where('logistics_num',$logistics_num);
        $this->purchase_db->where('purchase_order_num',$purchase_order_num);
        $parcel_urgent_info = $this->purchase_db->get($this->table_name)->row_array();
        return $parcel_urgent_info;
    }

    /**
     * 插入数据
     * @author Jaden
     *  @param array $insert_data  插入数据表的数据
     * @return bool
     * 2019-1-16
     */
    public function insert_parcel_batch_all($insert_data){
        if(empty($insert_data)) {
            return false;
        }
        $result = $this->purchase_db->insert_batch($this->table_name,$insert_data);
        return $result;
    }


     /**
     * 修改数据
     * @author Jaden
     * @param strint $where  
     * @param array $update_data  需要改变数据
     * @return bool
     * 2019-1-16
     */

     public function update_logistics($where,$update_data){
        if(empty($where) || empty($update_data)) {
            return false;
        }
        $result = $this->purchase_db->update($this->table_name, $update_data,$where);
        return $result;
    }

    /**
     * 获取 获取推送包裹加急数据
     * @author jeff
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_push_logistics_urgent_list($ids){
        $field = 'a.id,a.purchase_order_num,b.express_no logistics_num';
        $this->purchase_db->select($field)
            ->from($this->table_name.' a')
            ->join('purchase_logistics_info b','a.purchase_order_num = b.purchase_number','left')
            ->distinct();
        $this->purchase_db->where('a.is_deleted', 1);//查询未删除的
        $this->purchase_db->where_in('a.id',$ids);
        $this->purchase_db->where_in('a.push_status', [0,3]);
        $results = $this->purchase_db->get()->result_array();

        return $results;
    }

    public function auto_add_parcel($purchase_number)
    {
        $query_builder = $this->purchase_db;
        //检查是否欠货
        $result = $query_builder->select('*')
            ->where('lack_quantity_status',1)
            ->where('purchase_number',$purchase_number)
            ->get('purchase_order')->row_array();
        if (empty($result)){
            return true;
        }

        //获取采购单采购员
        $purchase_info = $query_builder->select('buyer_id,buyer_name')->where('purchase_number', $purchase_number)
            ->get("purchase_order")
            ->row_array();

        $logistics_arr = [];
        $unique_key = [];
        //查询已经存在的包裹加急
        $import_info = $query_builder->select('CONCAT(logistics_num,carrier_name) as tag')->where('purchase_order_num', $purchase_number)
            ->where('is_deleted',1)
            ->get("logistics_import")
            ->result_array();

        if (!empty($import_info)){
            foreach ($import_info as $key=>$item){//快递单号+快递公司 同采购单号不能重复
                $unique_key[$item['tag']] = 1;
            }
        }
        unset($import_info);

        $logistics_arr[] = [
            'purchase_order_num' => $purchase_number,
            'create_id' => $purchase_info['buyer_id'],
            'create_name' => $purchase_info['buyer_name'],
            'create_time' => date('Y-m-d H:i:s'),
        ];

        $add_parcel_result = $this->insert_parcel_batch_all($logistics_arr);
        if (empty($add_parcel_result)) {
            return false;
        }
        return true;
    }

    /**
     * 获取 采购单包裹加急信息
     * @author Jolon
     * @param string $purchase_number 采购单号
     * @return mixed
     */
    public function get_one($purchase_number){

        $row = $this->purchase_db->where('purchase_order_num',$purchase_number)->get($this->table_name)->row_array();
        return empty($row)?false:$row;
    }
}