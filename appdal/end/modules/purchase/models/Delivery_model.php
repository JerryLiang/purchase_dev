<?php
/**
 * Created by PhpStorm.
 * 权均交期模型
 * User: Jaden
 * Date: 2019/01/16 
 */
class Delivery_model extends Purchase_model {

    protected $table_name   = 'sku_avg_delivery_time';

    public function __construct(){
        parent::__construct();

        $this->load->model('product_line_model','',false,'product');
    }

    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function tableName() {
        return 'sku_avg_delivery_time';
    }

    /**
     * 获取仓库的信息
     * @PARAM
     * @AUTHOR:LUXU
     **/
    public function get_warehouseCode(){

        $result = $this->purchase_db->from("warehouse")->select("warehouse_name,warehouse_code")->get()->result_array();
        return $result;
    }


    /**
     * 权均交统计
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return arrainsert_delivery_info
     * 2019-1-16
     **/
    public function get_delivery_total($params, $offset, $limit,$field='*',$export=false){

        if(!empty($params['product_line_id'])){

            $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);
            $children_id = $category_all_ids;
            $children_ids = explode(",", $children_id);
            $children_ids = array_filter($children_ids);
        }

        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name.' as d');
        $this->purchase_db->join('product as p', 'd.sku=p.sku', 'left');
        $this->purchase_db->join('warehouse as ware','d.warehouse_code=ware.warehouse_code','LEFT');

        if(!empty($params['ids'])){
            $this->purchase_db->where_in('d.id', explode(',', $params['ids']));
        }

        if (isset($params['sku']) and $params['sku']) {// SKU
            $sku = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('d.sku', $sku);
        }

        if( isset($params['business_line']) && !empty($params['business_line'])){

            $this->purchase_db->where_in("ware.purchase_type_id",$params['business_line']);
        }


        if(!empty($params['supplier_code'])){
            $this->purchase_db->where('p.supplier_code', $params['supplier_code']);
        }

        if(!empty($params['product_line_id'])){

            $this->purchase_db->where_in('p.product_line_id', $children_ids);
        }

        return $this->purchase_db->count_all_results();
    }
    /**
     * 权均交期列表
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return arrainsert_delivery_info
     * 2019-1-16
     */
    public function get_delivery_list($params, $offset, $limit,$field='*',$export=false) {

        if(!empty($params['product_line_id'])){

            $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);
            $children_id = $category_all_ids;
            $children_ids = explode(",", $children_id);
            $children_ids = array_filter($children_ids);
        }

        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name.' as d');
        $this->purchase_db->join('product as p', 'd.sku=p.sku', 'left');
        $this->purchase_db->join('warehouse as ware','d.warehouse_code=ware.warehouse_code','LEFT');

        if(!empty($params['ids'])){
            $this->purchase_db->where_in('d.id', explode(',', $params['ids']));
        }


        if (isset($params['sku']) and $params['sku']) {// SKU
            $sku = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('d.sku', $sku);
        }

        if(!empty($params['supplier_code'])){
            $this->purchase_db->where('p.supplier_code', $params['supplier_code']);
        }

        if(!empty($params['product_line_id'])){

            $this->purchase_db->where_in('p.product_line_id', $children_ids);
        }

        /**
         * HTTP 传入SKU 产品状态
         **/
        if( isset($params['product_status']) && !empty($params['product_status'])){

            $this->purchase_db->where_in('p.product_status',$params['product_status']);
        }

        // 如果HTTP 传入是否定制

        if( isset($params['is_customized']) && !empty($params['is_customized'])){
            $this->purchase_db->where('p.is_customized',$params['is_customized']);
        }

        // 如果HTTP 传入业务线

        if( isset($params['business_line']) && !empty($params['business_line'])){

            $this->purchase_db->where_in("ware.purchase_type_id",$params['business_line']);
        }

        //如果HTTP 传入仓库

        if( isset($params['warehouse_code']) && !empty($params['warehouse_code'])){

            $this->purchase_db->where_in("d.warehouse_code",$params['warehouse_code']);
        }

        //SKU 权均交期天数

        if( isset($params['start']) && !empty($params['start'])){

            $this->purchase_db->where("d.avg_delivery_time>=",$params['start']);
        }

        if( isset($params['end']) && !empty($params['end'])){

            $this->purchase_db->where("d.avg_delivery_time<=",$params['end']);
        }

        // 是否代采is_purchasing

        if( isset($params['is_purch']) && !empty($params['is_purch'])){
            $this->purchase_db->where('p.is_purchasing',$params['is_purch']);
        }



        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$clone_db->count_all_results();//符合当前查询条件的总记录数
        //echo $this->purchase_db->last_query();exit;
        if($export){//导出不需要分页查询
            $results = $this->purchase_db->get()->result_array(); 
        }else{//列表查询
            $results = $this->purchase_db->order_by('d.statistics_date','desc')->limit($limit, $offset)->get()->result_array();     
        }
        $role_name=get_user_role();//当前登录角色
        $data_role= getRolexiao();
        $results = ShieldingData($results,['supplier_name','supplier_code'],$role_name);
        
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }


    public function EveryMonth($skus,$warehouse_code=NULL){

        $sql = "SELECT
                  id,
                  MAX(first_warehouse_date) AS create_time,
                    (
                        date_format(MAX(first_warehouse_date), '%Y-%m')
                    ) AS month,
                    sku,
                    first_warehouse_date,
                    deveily_days
                FROM
                    pur_sku_avg_delivery_time_log
                WHERE
                    sku = '".$skus."' AND is_effect=2 and warehouse_code='".$warehouse_code."'
                    
                GROUP BY
                    date_format(first_warehouse_date, '%Y-%m')
                ORDER BY first_warehouse_date DESC
              
               ";
        $result = $this->purchase_db->query($sql)->result_array();
        return $result;
    }



    /**
     * 根据 仓库CODE和SKU查数据
     * @author Jaden
     * @param string       $warehouse_code 仓库Code
     * @param string|array $sku            SKU（如果是数组 则 根据 order_by 参数返回一条记录）
     * @param string       $order_by       排序方式（枚举类型：avg_delivery_time DESC  或 avg_delivery_time ASC ）
     * @return array
     * @date   2019-03-15
     */
    public function get_delivery_info($warehouse_code=NULL,$sku,$order_by = 'avg_delivery_time DESC'){
//        if(empty($warehouse_code) || empty($sku)){
//            return false;
//        }

        if($warehouse_code != NULL ) {
            $this->purchase_db->where('warehouse_code', $warehouse_code);
        }
        if(is_array($sku)){
            $this->purchase_db->where_in('sku',$sku);
            $delivery_log_info = $this->purchase_db->order_by($order_by)->get($this->table_name)->row_array();
        }else{
            $this->purchase_db->where('sku',$sku);
            $delivery_log_info = $this->purchase_db->get($this->table_name)->row_array();
        }
        return $delivery_log_info;
    }


    /**
     * 根据 仓库CODE和SKU修改数据
     * @author Jaden
     * @param $warehouse_code
     * @param $sku
     * @param $update_data
     * @return array
     * 2019-03-15
     */
    public function update_delivery_info($warehouse_code,$sku,$update_data){
        if(empty($warehouse_code) || empty($sku)){
            return false;
        }
        $this->purchase_db->where('warehouse_code',$warehouse_code);
        $this->purchase_db->where('sku',$sku);
        $result = $this->purchase_db->update($this->table_name,$update_data);
        return $result?true:false;
    }


    /**
     * 数据入库
     * @author Jaden
     * @param $purchase_number
     * @param $sku
     * @return array
     * 2019-03-15
     */
    public function insert_delivery_info($insert_data){
        if(empty($insert_data)){
            return false;
        }
        $result = $this->purchase_db->insert($this->table_name,$insert_data);
        return $result?true:false;
    }

    /**
     * 获取权限交期日志数据
     * @param $skus  string|array SKU
     * @author:luxu
     * @time:2020/6/15
     **/

    public function getDeliveryLogs($skus,$offset,$limit,$warehouse_code = NULL){

        try{

              $query = $this->purchase_db->from("sku_avg_delivery_time_log");
              if(is_array($skus)){
                  $query->where_in("sku",$skus);
              }else{
                  $query->where("sku",$skus);
              }

              if( NULL != $warehouse_code){
                  $query->where("sku_avg_delivery_time_log.warehouse_code",$warehouse_code);
              }

              $totalQuery = clone $query;
              $total = $totalQuery->count_all_results();
              $result = $query->join("warehouse AS ware","sku_avg_delivery_time_log.warehouse_code=ware.warehouse_code")
                  ->select("sku_avg_delivery_time_log.*,ware.warehouse_name")->limit($limit, $offset)->order_by("sku_avg_delivery_time_log.first_warehouse_date DESC")->get()->result_array();
            $role_name=get_user_role();//当前登录角色
            $data_role= getRolexiao();
            $result = ShieldingData($result,['supplier_name','supplier_code'],$role_name);
              return [

                  'page' => ['total'=>$total,'offset'=>$offset+1,'limit'=>$limit],
                  'data' => $result
              ];

        }catch ( Exception $exp ){

              throw new Exception( $exp->getMessage());
        }
    }


}