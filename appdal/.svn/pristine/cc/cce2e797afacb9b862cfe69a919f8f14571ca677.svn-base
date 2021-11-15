<?php
/**
 * Created by PhpStorm.
 * 产品基础信息表
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Product_model extends Purchase_model {
    protected $table_name   = 'product';// 数据表名称

    public function __construct(){
        parent::__construct();

        $this->load->model('product_line_model','',false,'product');
        $this->load->model('purchase/purchase_order_sum_model');
        $this->load->model('user/User_group_model');
        $this->load->model('product_update_log_model','product_update_log',false,'product');
       // $this->load->model('Product_mod_audit_model','product_mod_audit_other',false,'product');
    }

    /**
     * 货源状态发生变化，推入消息队列()
     * @param string $sku sku
     * @param int $supply_status_old 变化前的货源状态
     * @param int $supply_status_new 变化后的货源状态
     */
    public function _push_rabbitmq_data($sku, $supply_status_old, $supply_status_new)
    {
        //推入消息队列
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数zjo
        $mq->setExchangeName('PRODUCT_SUPPLY_STATUS_CHANGE_EX_NAME');
        $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
        //构造存入数据
        $push_data = [
            'sku' => $sku,
            'supply_status_old' => $supply_status_old,
            'supply_status_new' => $supply_status_new,
            'update_time' => time()
        ];
        //存入消息队列
        $mq->sendMessage($push_data);
    }


    /**
     * 获取产品的基本信息
     * @author Jolon
     * @param string $sku SKU
     * @param string $field 查询字段
     * @return array|bool
     */
    public function get_product_info($sku, $field = '*')
    {
        if (empty($sku)) return false;
        $where = ['sku' => strval($sku)];

        if(!is_array($sku)) {
            return $this->purchase_db->select($field)->where($where)->get($this->table_name)->row_array();
        }else{
            return $this->purchase_db->select($field)->where_in($where)->get($this->table_name)->result_array();
        }
    }

    public function set_invalid( $sku ) {

        return $this->purchase_db->where("sku",$sku)->update('pur_product',['is_invalid'=>0]);
    }

    /**
     * function:获取SKU 是否定制
     * @param:$sku string SKU
     * @author:luxu
     * @time:2020/6/19
     **/
    public function isCustomized($sku){

        return $this->purchase_db->from("product")->where("sku",$sku)->select("is_customized")->get()->row_array();
    }

    /**
     * 获取 产品的 包装类型
     * @param $purchase_packaging
     * @return mixed
     */
    public function getPurchasePackaging($purchase_packaging){
        if( !empty($purchase_packaging) ) {
            $search_start_index = strpos($purchase_packaging,"[");
            $packaging_string   = substr_replace($purchase_packaging," ",$search_start_index);
            $purchase_packaging = $packaging_string;
        }
        return $purchase_packaging;
    }

    /**
     * 标记产品是否 1688 关联
     * @user Jolon
     * @param $sku
     * @param $relate_status  0.未关联，1已关联
     * @return bool
     */
    public function set_is_relate_ali($sku,$relate_status){
        if($relate_status != 1) $relate_status = 0;// 只有0 或  1
        return $this->purchase_db->where("sku",$sku)->update('pur_product',['is_relate_ali' => $relate_status]);
    }

    /**
     * 获取产品的基本信息集合
     * @author harvin
     * return array
     */
    public function get_product_info_list(array $sku_list){
        if(empty($sku_list)) return [];
         $product_info = $this->purchase_db
                 ->select('*')
                 ->where_in('sku',$sku_list)
                 ->get($this->table_name)
                 ->result_array();
      return array_column(isset($sku_list)?$product_info:[], 'supply_status','sku');

    }

    /**
     * 获取 SKU销量（同步自 产品中心）
     * @author Jolon
     * @param  string $sku SKU
     * @param int     $day 指定天数
     * @return array|int|mixed
     */
    public function get_sku_days_sales($sku,$day = null){
        $product_info = $this->get_product_info($sku);
        if($product_info){
            //销量列表
            $days_sales_list['days_sales_7']  = isset($product_info['days_sales_7'])?$product_info['days_sales_7']:0;
            $days_sales_list['days_sales_15'] = isset($product_info['days_sales_15'])?$product_info['days_sales_15']:0;
            $days_sales_list['days_sales_30'] = isset($product_info['days_sales_30'])?$product_info['days_sales_30']:0;
            $days_sales_list['days_sales_60'] = isset($product_info['days_sales_60'])?$product_info['days_sales_60']:0;
            $days_sales_list['days_sales_90'] = isset($product_info['days_sales_90'])?$product_info['days_sales_90']:0;

            if(is_null($day)){
                switch($day){
                    case 7:
                        $day_key = 'days_sales_7';
                        break;
                    case 15:
                        $day_key = 'days_sales_15';
                        break;
                    case 30:
                        $day_key = 'days_sales_30';
                        break;
                    case 60:
                        $day_key = 'days_sales_60';
                        break;
                    case 90:
                        $day_key = 'days_sales_90';
                        break;
                    default :
                        $day_key = 'days_sales_';
                }
                return isset($product_info[$day_key]) ? $product_info[$day_key] : false;
            }else{

                return $days_sales_list;
            }
        }else{
            return false;
        }
    }

    /**
     * 更改 SKU的默认供应商
     * @author Jolon
     * @param  string $sku            SKU
     * @param string  $supplier_code  目的供应商代码
     * @param float   $supplier_price 新采购价（供应商报价）
     * @return array
     */
    public function change_supplier($sku,$supplier_code = null,$supplier_price = null){
        $return = ['code' => false,'data' => '','msg' => ''];

        $product_info = $this->get_product_info($sku);
        if(empty($product_info)){
            $return['msg'] = '产品不存在';
            return $return;
        }

        if(empty($product_info)){
            $return['msg'] = '供应商不存在';
            return $return;
        }

        $detail = '';
        $update_data = [];

        $update_log = [// 产品更新日志
            'sku'                => $sku,
            'product_name'       => $product_info['product_name'],
            'old_supplier_code'  => $product_info['supplier_code'],
            'old_supplier_name'  => $product_info['supplier_name'],
            'old_supplier_price' => $product_info['purchase_price'],
            'create_remark'      => 'SKU屏蔽申请列表替换供应商',
            'audit_status'       => PRODUCT_UPDATE_LIST_QUALITY_AUDIT,
            'create_user_id'     => getActiveUserId(),
            'create_user_name'   => getActiveUserName(),
            'create_time'        => date('Y-m-d H:i:s'),
        ];
        if($supplier_code and $supplier_code != $product_info['supplier_code']){// 更新产品 供应商
            $this->load->model('supplier_model','',false,'supplier');
            $supplier_info = $this->supplier_model->get_supplier_info($supplier_code);

            if(empty($supplier_info)){
                $return['msg'] = '供应商不存在';
                return $return;
            }
            $update_data['supplier_code'] = $supplier_code;
            $update_data['supplier_name'] = $supplier_info['supplier_name'];

            $update_log['new_supplier_code'] = $supplier_code;
            $update_log['new_supplier_name'] = $supplier_info['supplier_name'];
            $detail .= '变更供应商，从【'.$product_info['supplier_code'].'】改为【'.$supplier_code.'】 ';
        }
        if($supplier_price and $supplier_price != $product_info['purchase_price']){// 最新采购价（供应商报价）
            $update_data['purchase_price'] = $supplier_price;

            $update_log['new_supplier_price'] = $supplier_price;
            $detail .= '变更采购价，从【'.$product_info['purchase_price'].'】改为【'.$supplier_price.'】 ';
        }
        if(empty($update_data)){
            $return['msg'] = '数据未发生改变';
            return $return;
        }

        $result = $this->purchase_db->where('sku',$sku)->update($this->table_name,$update_data);
        if($result){
            $this->purchase_db->insert('product_update_log',$update_log);
            operatorLogInsert(
                ['id'      => $sku,
                 'type'    => $this->table_name,
                 'content' => '默认供应商与采购价',
                 'detail'  => $detail
                ]);

            $return['code'] = true;
        }else{
            $return['msg'] = '修改SKU供应商数据时出错';
        }

        return $return;
    }

    /**
      * 统计商品查询总数
     **/

    public function get_product_sum( $params ) {

        $this->purchase_db->from('product as p');
        if (!empty($params['sku'])) {
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $this->purchase_db->like('p.sku', $params['sku'], 'both');
            } else {
                $this->purchase_db->where_in('p.sku', $sku);
            }
        }

        if( isset($params['is_productismulti']) && !empty($params['is_productismulti']) ) {

            if( !is_array($params['is_productismulti'])) {
                $params['is_productismulti'] = explode(",", $params['is_productismulti']);
            }
            $this->purchase_db->where("( p.id>0 ");
            $flag = 0;
            if (in_array(1, $params['is_productismulti'])) {

                $this->purchase_db->where_in("p.productismulti", [2]);
                ++$flag;
            }

            if (in_array(2, $params['is_productismulti'])) {

                if( $flag == 0) {

                    $this->purchase_db->where("p.producttype", 2);
                }else {
                    $this->purchase_db->or_where("p.producttype", 2);
                }

                ++$flag;
            }

            if (in_array(3, $params['is_productismulti'])) {

                if( $flag ==0 ){

                    $this->purchase_db->where_in("(p.productismulti", [0,1])->where('p.producttype=1)');
                }else {
                    $this->purchase_db->or_where_in("(p.productismulti", [0,1])->where('p.producttype=1)');
                }




            }

            $this->purchase_db->where("1=1)");
        }

        if( isset($params['create_time_start']) && !empty($params['create_time_start'])){

            $this->purchase_db->where("p.create_time>=",$params['create_time_start']);
        }

        if( isset($params['create_time_end']) && !empty($params['create_time_end'])){

            $this->purchase_db->where("p.create_time<=",$params['create_time_end']);
        }



        if( isset($params['is_overseas_first_order']) && $params['is_overseas_first_order'] != NULL && $params['is_overseas_first_order'] == 0  ){
            $this->purchase_db->where("is_overseas_first_order",0);
        }

        if( isset($params['is_overseas_first_order']) && $params['is_overseas_first_order'] == 1 ){
            $this->purchase_db->where("is_overseas_first_order",1);
        }


        if( isset($params['transformation']) && !empty($params['transformation'])){

            if($params['transformation'] == 1){

                //$params['transformation'] = 0;
                $this->purchase_db->where("p.sku_state_type!=",6);
            }else {

                $this->purchase_db->where("p.sku_state_type", $params['transformation']);
            }
        }


        if (isset($params['develop_code']) && !empty($params['develop_code'])) {
            $develop_codes = [];
            foreach ($params['develop_code'] as $develop_key => $dvelop_value) {
                if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($dvelop_value), $arr)) {

                    $develop_codes[] = $arr[0];
                }
            }

            if (!empty($develop_codes)) {
                $this->purchase_db->where_in('p.create_user_name', $develop_codes);
            }
        }

        if( isset($params['is_new']) && !empty($params['is_new'])){

            $this->purchase_db->where("p.is_new",$params['is_new']);
        }

        //is_purchaseing

        if( isset($params['is_purchaseing']) && !empty($params['is_purchaseing']))
        {
            $this->purchase_db->where('p.is_purchasing',$params['is_purchaseing']);
        }

        if (!empty($params['buyer_code'])) {
            $this->purchase_db->join('supplier_buyer as supplierbuyer', 'p.supplier_code=supplierbuyer.supplier_code', 'left');
            $this->purchase_db->where_in("supplierbuyer.buyer_id", $params['buyer_code']);
            $this->purchase_db->where("supplierbuyer.status", 1);
        }

        if (!empty($params['is_inspection'])) {

            $this->purchase_db->where('p.is_inspection', $params['is_inspection']);
        }
        if (!empty($params['ids'])) {
            if( is_array($params['ids']) ) {

                $this->purchase_db->where_in('p.id',  $params['ids']);
            }else {
                $this->purchase_db->where_in('p.id', explode(',', $params['ids']));
            }
        }
        if (!empty($params['supplier_code'])) {
            if (is_array($params['supplier_code'])) {

                // $supplier_codes = implode(",",$params['supplier_code']);
                $this->purchase_db->where_in('p.supplier_code', $params['supplier_code']);
            } else {
                $this->purchase_db->where('p.supplier_code', $params['supplier_code']);
            }
        }

        if ( (isset($params['is_cross_border']) and $params['is_cross_border'] !== '') || !empty($params['supplier_source']) || !empty($params['status'])) {

            $this->purchase_db->join('supplier as s', 'p.supplier_code=s.supplier_code');
        }

        if( !empty($params['product_name']) )
        {
            $this->purchase_db->join(" (SELECT id FROM pur_product WHERE product_name LIKE '%{$params['product_name']}%') AS product_like","product_like.id=p.id","INNER");
        }

        if (!empty($params['supplier_source'])) {

            $this->purchase_db->select('s.supplier_source')->where('s.supplier_source=' . $params['supplier_source']);
        }

        if( !empty($params['is_invaild'])) {

            if( $params['is_invaild'] == 1) {

                $this->purchase_db->where("p.is_invalid",1);
            }

            if( $params['is_invaild'] == 2) {

                $this->purchase_db->where("p.is_invalid",0);
            }
        }


        if(!empty($params['status'])){
            if($params['status'] == 1){
                $this->purchase_db->select('s.status')->where_in('s.status',[1,4,5]);
            }else{
                $this->purchase_db->select('s.status')->where('s.status',$params['status']);
            }
        }

        if (!empty($params['product_line_id'])) {
            $this->purchase_db->where_in('p.product_line_id', explode(',', $params['product_line_id']));
        }
        if (isset($params['product_status']) && !empty($params['product_status'])) {

            if( is_string($params['product_status']) && strpos($params['product_status'],",")>0) {

                $params['product_status'] = explode(",",$params['product_status']);
            }
            $this->purchase_db->where_in('p.product_status', $params['product_status']);
        }


        if (!empty($params['supply_status'])) {
            $this->purchase_db->where('p.supply_status', $params['supply_status']);
        }
        if (!empty($params['audit_status'])) {
            $this->purchase_db->where('p.audit_status', $params['audit_status']);
        }
        if (isset($params['is_cross_border']) and $params['is_cross_border'] !== '') {
            $this->purchase_db->where('s.is_cross_border', $params['is_cross_border']);
        }
        if (isset($params['is_relate_ali']) and $params['is_relate_ali'] !== '') {
            $this->purchase_db->where('p.is_relate_ali', $params['is_relate_ali']);
        }
        if (isset($params['starting_qty_start']) and intval($params['starting_qty_start'])>0) {
            $this->purchase_db->where('p.starting_qty >=', intval($params['starting_qty_start']));
        }
        if (isset($params['starting_qty_end']) and intval($params['starting_qty_end'])>0) {
            $this->purchase_db->where('p.starting_qty <=', intval($params['starting_qty_end']));
        }
        if (isset($params['equal_sup_id']) and $params['equal_sup_id']!='') {
            $this->purchase_db->where('p.is_equal_sup_id=', intval($params['equal_sup_id']));
        }
        if (isset($params['equal_sup_name']) and $params['equal_sup_name']!='') {
            $this->purchase_db->where('p.is_equal_sup_name=', intval($params['equal_sup_name']));
        }
        $total = $this->purchase_db->count_all_results();
        return $total;
    }

    /**
      * function:设置SKU 审核状态
     **/
    public function set_sku_status($sku,$status)
    {
        return $this->purchase_db->where("sku",$sku)->update('product',['audit_status_log'=>$status]);
    }

    /**
       获取单个商品信息
      **/
    public function get_product_one($parms)
    {
        $query = $this->purchase_db->from("product");
        if( isset($parms['sku']) && !empty($parms['sku']))
        {
            $query->where("sku",$parms['sku']);
        }

        $result = $query->get()->row_array();
        return $result;
    }


    /**
     * 获取供应商信息
     * @param $new_supplier_code  string   新供应商CODE
     **/
    public function get_supplier_data($new_supplier_name,$where = array()){

        $query = $this->purchase_db->from("supplier")->where("supplier_name",$new_supplier_name);

        if(!empty($where)){

            if(isset($where['supplier_source']) && !empty($where['supplier_source'])){

                $query->where_in("supplier_source",$where['supplier_source']);
            }elseif(isset($where['status']) && !empty($where['status'])){
                $query->where_in("status",$where['status']);

            }else {
                $query->where($where);
            }
        }
        $result = $query->get()->row_array();
        return $result;

    }

    /**
     * 根据 SKU 从mongdb里面模糊查找 SKU LIST
     * @param $sku
     * @param $product_name
     * @return array|bool
     * @author Jolon
     */
    public function get_search_sku_list_from_mongodb($sku = '',$product_name = ''){
        $sku_list = [];
        try{
            // 利用 MongoDB进行查询优化
            $this->_ci     = get_instance();
            $this->_ci->load->config('mongodb');
            $host          = $this->_ci->config->item('mongo_host');
            $port          = $this->_ci->config->item('mongo_port');
            $user          = $this->_ci->config->item('mongo_user');
            $password      = $this->_ci->config->item('mongo_pass');
            $author_db     = $this->_ci->config->item('mongo_db');
            $mongdb_object = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");

            $filter = [];
            if($sku){
                $filter['p_sku'] = ['$in' => [new MongoDB\BSON\Regex($sku, 'i')]];
            }
            if($product_name){
                $filter['p_product_name'] = ['$in' => [new MongoDB\BSON\Regex($product_name, 'i')]];
            }

            $query         = new MongoDB\Driver\Query($filter);
            $cursor        = $mongdb_object->executeQuery("{$author_db}.productListToMongoDbForSearch", $query);

            foreach($cursor as $document){
                $sku_list[] = $document->p_sku;
            }
        }catch(\MongoDB\Driver\Exception\Exception $exception){
            return false;
        }
        return $sku_list;
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
    public function get_product_list($params, $offset, $limit,$field='*',$export=false)
    {

            $category_all_ids = [];
            if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {
                $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);
            }
            $this->purchase_db->from('product as p');

            // SKU 产品类型为普通

            $this->purchase_db->where('p.product_type',1)->where('p.is_multi!=2');

            if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {

                $children_id = $category_all_ids;
                $children_ids = explode(",", $children_id);
                $children_ids = array_filter($children_ids);
                $this->purchase_db->where_in('p.product_line_id', $children_ids);

                unset($params['product_line_id']);
            }

            if( isset($params['overseas_supplier_code'])){

                if(count($params['overseas_supplier_code'])>2000){

                    $overseas_supplier_code = array_chunk($params['overseas_supplier_code'],2000);
                    $this->purchase_db->where("( 1=1")->where_in('p.supplier_code',$overseas_supplier_code[0]);
                    foreach($overseas_supplier_code as $overseas_supplier_code_value){

                        $this->purchase_db->or_where_in('p.supplier_code',$overseas_supplier_code_value);
                    }
                    $this->purchase_db->where(" 1=1)");
                }else {

                    $this->purchase_db->where_in('p.supplier_code', $params['overseas_supplier_code']);
                }
            }

            if( isset($params['create_time_start']) && !empty($params['create_time_start'])){

                    $this->purchase_db->where("p.create_time>=",$params['create_time_start']);
            }

            if( isset($params['create_time_end']) && !empty($params['create_time_end'])){

                $this->purchase_db->where("p.create_time<=",$params['create_time_end']);
            }

            if( isset($params['price_start']) && !empty($params['price_start'])){

                $this->purchase_db->where('p.purchase_price>=',$params['price_start']);
            }

            if( isset($params['price_end']) && !empty($params['price_end'])){

                $this->purchase_db->where('p.purchase_price<=',$params['price_end']);
            }


            if( isset($params['domestic_supplier_code']) ){

                if(count($params['domestic_supplier_code'])>2000){

                    $domestic_supplier_code = array_chunk($params['domestic_supplier_code'],2000);
                    $this->purchase_db->where("( 1=1")->where_in('p.supplier_code',$domestic_supplier_code[0]);
                    foreach($domestic_supplier_code as $domestic_supplier_code_value){

                        $this->purchase_db->or_where_in('p.supplier_code',$domestic_supplier_code_value);
                    }
                    $this->purchase_db->where(" 1=1)");
                }else {

                    $this->purchase_db->where_in('p.supplier_code', $params['domestic_supplier_code']);
                }
            }

            if( isset($params['is_shipping']) && !empty($params['is_shipping'])){

                $this->purchase_db->where("p.is_shipping",$params['is_shipping']);
            }

            if( isset($params['settlement_method']) && !empty($params['settlement_method'])){

                $this->purchase_db->join('supplier_payment_info as info','info.supplier_code=p.supplier_code','LEFT')
                    ->where_in("info.supplier_settlement",$params['settlement_method']);
            }

            if( isset($params['is_overseas_first_order']) && $params['is_overseas_first_order'] != NULL && $params['is_overseas_first_order'] == 0  ){
                $this->purchase_db->where("is_overseas_first_order",0);
            }

            if( isset($params['is_overseas_first_order']) && $params['is_overseas_first_order'] == 1 ){
                $this->purchase_db->where("is_overseas_first_order",1);
            }

            if( isset($params['transformation']) && !empty($params['transformation'])){

                if($params['transformation'] == 1){

                    //$params['transformation'] = 0;
                    $this->purchase_db->where("p.sku_state_type!=",6);
                }else {

                    $this->purchase_db->where("p.sku_state_type", $params['transformation']);
                }
            }


        if( isset($params['is_productismulti']) && !empty($params['is_productismulti']) ) {

            if( !is_array($params['is_productismulti'])) {
                  $params['is_productismulti'] = explode(",",$params['is_productismulti']);
            }
            $this->purchase_db->where("(p.id>0");
            $flag = 0;
            if (in_array(1, $params['is_productismulti'])) {

                $this->purchase_db->where_in("p.is_multi", [2]);
                ++$flag;
            }

            if (in_array(2, $params['is_productismulti'])) {

                if( $flag == 0) {

                    $this->purchase_db->where("p.product_type", 2);
                }else {
                    $this->purchase_db->or_where("p.product_type", 2);
                }

                ++$flag;
            }

            if (in_array(3, $params['is_productismulti'])) {

                if( $flag ==0 ){

                    $this->purchase_db->where_in("(p.is_multi", [0,1])->where('p.producttype=1)');
                }else {
                    $this->purchase_db->or_where_in("(p.is_multi", [0,1])->where('p.producttype=1)');
                }
            }

            $this->purchase_db->where("1=1)");


        }
            if( isset($params['is_new']) && $params['is_new'] != NULL){

                $this->purchase_db->where("p.is_new",$params['is_new']);
            }
            if ( (isset($params['sku']) && !empty(trim($params['sku']))) or (isset($params['product_name']) && !empty($params['product_name']))) {
                $params_sku = isset($params['sku'])?trim($params['sku']):'';
                $params_product_name = isset($params['product_name'])?trim($params['product_name']):'';
                $sku = query_string_to_array($params_sku);

                $mongodb_search = [
                    'product_name' => '',
                    'sku'          => ''
                ];
                if(!empty($params_product_name)){
                    $mongodb_search['product_name'] = $params_product_name;
                }
                if (count($sku) == 1) {  //单个sku时使用模糊搜索
                    $mongodb_search['sku'] = $params_sku;
                } else {
                    $this->purchase_db->where_in('p.sku', $sku);
                }
                if(!empty($mongodb_search['sku']) or !empty($mongodb_search['product_name'])){
                    $sku_list = $this->get_search_sku_list_from_mongodb($mongodb_search['sku'],$mongodb_search['product_name']);
                    if($sku_list !== false){
                        if($sku_list){

                            if(count($sku_list)>2000){

                                $skuListChunk = array_chunk($sku_list,10);
                                $this->purchase_db->group_start();
                                foreach($skuListChunk as $chunkSkus) {
                                    $sku_list_str = "'" . implode("','", $chunkSkus) . "'";
                                    $this->purchase_db->or_where("p.sku IN({$sku_list_str})");
                                }
                                $this->purchase_db->group_end();

                            }else {
                                $sku_list_str = "'" . implode("','", $sku_list) . "'";
                                $this->purchase_db->where("p.sku IN({$sku_list_str})");
                            }
                        }else{
                            $this->purchase_db->where('1=0');
                        }
                    }else{// Mongodb加载失败时使用 MySQL模糊查询
                        $this->purchase_db->like('p.sku', $params_sku, 'both');
                        $this->purchase_db->join(" (SELECT id FROM pur_product WHERE product_name LIKE '%{$params_product_name}%') AS product_like", "product_like.id=p.id", "INNER");
                    }
                }
            }

            if (isset($params['develop_code']) && !empty($params['develop_code'])) {
                $develop_codes = [];
                foreach ($params['develop_code'] as $develop_key => $dvelop_value) {
                    if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($dvelop_value), $arr)) {

                    $develop_codes[] =$arr[0];
                }
            }

                if (!empty($develop_codes)) {
                    $this->purchase_db->where_in('create_user_name', $develop_codes);
                }
            }

            if (isset($params['is_purchaseing']) && !empty($params['is_purchaseing'])) {
                $this->purchase_db->where('p.is_purchasing', $params['is_purchaseing']);
            }

        if (!empty($params['buyer_code'])) {
            $this->purchase_db->join('supplier_buyer as supplierbuyer', 'p.supplier_code=supplierbuyer.supplier_code', 'left');
            $this->purchase_db->where_in("supplierbuyer.buyer_id", $params['buyer_code']);
            $this->purchase_db->where("supplierbuyer.status", 1);
        }

        if (!empty($params['is_inspection'])) {

            $this->purchase_db->where('p.is_inspection', $params['is_inspection']);
        }
        if (!empty($params['ids'])) {
            if( is_array($params['ids']) ) {

                $this->purchase_db->where_in('p.id', $params['ids']);
            }else {
                $this->purchase_db->where_in('p.id', explode(',', $params['ids']));
            }
        }
        if (!empty($params['supplier_code'])) {
            if (is_array($params['supplier_code'])) {

                // $supplier_codes = implode(",",$params['supplier_code']);
                $this->purchase_db->where_in('p.supplier_code', $params['supplier_code']);
            } else {
                $this->purchase_db->where('p.supplier_code', $params['supplier_code']);
            }
        }

        if ( (isset($params['is_cross_border']) and $params['is_cross_border'] !== '') || !empty($params['supplier_source']) || !empty($params['status'])) {

            $this->purchase_db->join('supplier as s', 'p.supplier_code=s.supplier_code');
        }

        if (!empty($params['supplier_source'])) {

            $this->purchase_db->select('s.supplier_source')->where('s.supplier_source=' . $params['supplier_source']);
        }

        if(!empty($params['status'])){
            if($params['status'] == 1){
                $this->purchase_db->select('s.status')->where_in('s.status',[1,4,5]);
            }else{
                $this->purchase_db->select('s.status')->where('s.status',$params['status']);
            }
        }

            if( isset($params['is_customized']) && !empty($params['is_customized'])){

                $this->purchase_db->where("p.is_customized",$params['is_customized']);
            }


            if (!empty($params['is_invaild'])) {

            if( $params['is_invaild'] == 1) {

                $this->purchase_db->where("p.is_invalid",1);
            }

            if( $params['is_invaild'] == 2) {

                $this->purchase_db->where("p.is_invalid",0);
            }
        }



        if (!empty($params['product_line_id'])) {
            $this->purchase_db->where_in('p.product_line_id', explode(',', $params['product_line_id']));
        }
        if (isset($params['product_status']) && !empty($params['product_status'])) {
            if( is_string($params['product_status']) && strpos($params['product_status'],",")) {

                $params['product_status'] = explode(",",$params['product_status']);
            }
            $this->purchase_db->where_in('p.product_status', $params['product_status']);
        }
        //是否异常
        if (isset($params['is_abnormal']) && !empty($params['is_abnormal'])) {
            $this->purchase_db->where_in('p.is_abnormal', $params['is_abnormal']);
        }

        if( isset($params['is_audit_type']) && !empty($params['is_audit_type'])) {

            $this->purchase_db->where_in('p.audit_status_log',$params['is_audit_type']);
        }

            if (!empty($params['supply_status'])) {
                $this->purchase_db->where('p.supply_status', $params['supply_status']);
            }
            if (!empty($params['audit_status'])) {
                $this->purchase_db->where_in('p.audit_status_log', $params['audit_status']);
            }
            if (isset($params['is_cross_border']) and $params['is_cross_border'] !== '') {
                $this->purchase_db->where('s.is_cross_border', $params['is_cross_border']);
            }
            if (isset($params['is_relate_ali']) and $params['is_relate_ali'] !== '') {
                $this->purchase_db->where('p.is_relate_ali', $params['is_relate_ali']);
            }
            if (isset($params['starting_qty_start']) and intval($params['starting_qty_start']) > 0) {
                $this->purchase_db->where('p.starting_qty >=', intval($params['starting_qty_start']));
            }
            if (isset($params['starting_qty_end']) and intval($params['starting_qty_end']) > 0) {
                $this->purchase_db->where('p.starting_qty <=', intval($params['starting_qty_end']));
            }
            if (isset($params['equal_sup_id']) and $params['equal_sup_id']!='') {
                $this->purchase_db->where('p.is_equal_sup_id=',intval($params['equal_sup_id']));
            }
            if (isset($params['equal_sup_name']) and $params['equal_sup_name']!='') {
                $this->purchase_db->where('p.is_equal_sup_name=', intval($params['equal_sup_name']));
            }

            if( isset($params['is_overseas']) && !empty($params['is_overseas'])) {
                $this->purchase_db->join("prod_sku_select_attr as attr", "p.sku=attr.sku")->where("attr.attr_type_id", 1)
                    ->where("is_del", 0);

                $this->purchase_db->join("product_attributes as children", "children.id=attr.attr_value_id", "LEFT")->join("product_attributes as parent", "children.parent_id=parent.id", "LEFT")
                    ->where_in("parent.id", [PRODUCT_ORDER_ATTR])
                    ->select("group_concat(distinct children.id) AS childrenid,count(children.id) as totalchildren");

                //当海外属性中,只有一个,为:"国内仓"时,是否海外仓SKU=否;
                if( $params['is_overseas'] == 1){

                    $this->purchase_db->having("totalchildren",1)->having("childrenid",67);
                }
                //当海外属性为多个,包含有"国内仓"时,是否海外仓SKU=共用;     例如"海外属性"为:"国内仓""澳洲仓";则是否海外仓sku=共用,
                if(  $params['is_overseas'] == 2 ){

                    $this->purchase_db->having("totalchildren>",1)->having("childrenid like '%67%'");

                }
                // 当海外属性为其他的,没有"国内仓"时,是否海外仓SKU=是;
                if(  $params['is_overseas'] == 3 ){

                    $this->purchase_db->having("totalchildren>=",1)->having("childrenid NOT LIKE '%67%'");

                }
            }

            $this->purchase_db->group_by("p.sku");
            $keys = $this->purchase_order_sum_model->get_key($params, "product");
            $total_sum = $this->purchase_order_sum_model->get_sum_cache($keys);
            //统计总数
            $clone_db = clone($this->purchase_db);
            $params['new'] =1;
            if (empty($total_sum) || (isset($params['new']) && !empty($params['new']))) {

                // 效率优化：只查询 product 表没有 join 时不用 group by p.sku
                $sum_sql = $clone_db->select('p.sku')->get_compiled_select();
                if(stripos($sum_sql,'join') === false){
                    $sum_sql = str_replace('GROUP BY `p`.`sku`','',$sum_sql);
                }
                $total_count = $clone_db->query("SELECT COUNT(1) AS numrows FROM (". $sum_sql .") AS tmp")->row_array();
                $total_count = isset($total_count['numrows'])?$total_count['numrows']:0;

                $this->purchase_order_sum_model->set_sum_cache($keys, $total_count);
            } else {
                $total_count = $total_sum;
            }


        $this->purchase_db->select($field);

        if ($export) {//导出不需要分页查询

            $results = $this->purchase_db->get()->result_array();

        } else {//列表查询
            $results = $this->purchase_db->limit($limit, $offset)->get()->result_array();


        }
        if( !empty($results)) {
            if (!empty($params['supplier_source']) || !empty($results)) {
                $supplier_code = array_column(array_filter($results), "supplier_code");
                $supplier_source = [];
                if (!empty($supplier_code)) {
                    $supplier_source = $this->purchase_db->from('supplier')->select('supplier_code,supplier_source,status')->where_in('supplier_code', $supplier_code)->get()->result_array();
                    if (!empty($supplier_source)) {

                        $supplier_source = array_column($supplier_source, NULL, "supplier_code");
                        $supplier_status_arr = array_column($supplier_source, 'status','supplier_code');
                    }
                }
            }

            foreach ($results as $key => &$value) {
                $category_all = $this->product_line_model->get_all_parent_category($value['product_line_id']);
                if (empty($params['supplier_source']) || isset($supplier_source)) {

                    $value['supplier_source'] = isset($supplier_source[$value['supplier_code']]['supplier_source']) ? $supplier_source[$value['supplier_code']]['supplier_source'] : "未知";
                }

                    $buyerCode = $this->purchase_db->from("supplier_buyer")->where("supplier_code",$value['supplier_code'])->select("buyer_id")
                        ->get()->row_array();
                    $groupName = '';
                    if(!empty($buyerCode)){
                        $groupName = $this->User_group_model->getBuyerGroupMessage(["'".$buyerCode['buyer_id']."'"]);
                        if(!empty($groupName)) {
                            $groupName = implode(",",array_column($groupName, 'group_name'));
                        }

                    }
                    $value['groupName'] = $groupName;

                    $audit_status = [
                        1 => "待采购审核",
                        2 => "待品控审核",
                        3 => "审核通过",
                        4 => "驳回",
                        5 => "待财务审核",
                        6 => "待供应商管理部审核",
                        7 => "待采购主管审核",
                        8 => "待采购副经理审核",
                        9 => "待采购经理审核",
                        10 => "待开发经理审核",
                        11 => "待供应链总监审核",
                    ];

                    $value['audit_status_log_cn'] = isset($value['audit_status_log']) && isset($audit_status[$value['audit_status_log']]) ? $audit_status[$value['audit_status_log']] : "未知";
                    $value['sku_state_type_ch'] = $value['sku_state_type'] != 6 ? '否' : '是';
                    $value['is_overseas_first_order_ch'] = $value['is_overseas_first_order'] == 1 ? "是": "否";
                    $supplier_source = [
                        1 => "常规",
                        2 => "海外",
                        3 => "临时",
                    ];
                    $value['supplier_source_ch'] = isset($value['supplier_source']) && isset($supplier_source[$value['supplier_source']]) ? $supplier_source[$value['supplier_source']] : "未知";

                    if( $value['is_new'] == 1){
                        $value['is_new_ch'] = "是";
                    }
                    if( $value['is_new'] == 0) {
                        $value['is_new_ch'] = "否";
                    }
                $value['category_line_data'] = $category_all;
                $value['product_img_url'] = erp_sku_img_sku($value['product_img_url']);
                $value['supplier_source'] = $value['supplier_source_ch'];
                $value['status'] = isset($supplier_status_arr[$value['supplier_code']]) ? $supplier_status_arr[$value['supplier_code']] : "";//供应商是否禁用
            }
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


    /**
     * 更新 产品信息
     * @author Jolon
     * @param $sku
     * @param $update_data
     * @return bool
     */
    public function update_one($sku,$update_data){

        // 使用代理方法 统一更新货源状态
        if(isset($update_data['supply_status'])){
            $this->update_product_supply_status($sku,$update_data['supply_status']);
            unset($update_data['supply_status']);
        }

        $result = $this->purchase_db->where('sku',strval($sku))->update($this->table_name,$update_data);

        return $result?true:false;
    }


    /**
     * 更新 插入数据
     * @author Jolon
     * @param $insert_data
     * @return bool
     */
    public function insert_one($insert_data){

        $result = $this->purchase_db->insert($this->table_name,$insert_data);
        return $result?true:false;
    }
    /**
     * 根据SKU取数据
     * @author Jaden
     * @param array  $skus
     * @return bool
     */
    public function get_list_by_sku($skus,$field='*'){
      if(empty($skus) || !is_array($skus)){
        return [];
      }
//      $this->purchase_db->select($field);
//      $this->purchase_db->from($this->table_name);
//      $sku_arr = format_query_string($skus);
//      $this->purchase_db->where('sku in ('. $sku_arr.')');
//      $results = $this->purchase_db->get()->result_array();
      //避免使用因数组长度过大where_in报正则匹配错误
      $query = $this->purchase_db->query("select ".$field." from pur_product where sku in (".format_query_string($skus).")");
      $results = $query->result_array();
      $sku_data = array();
      $field_list = explode(',',$field);
      foreach ($field_list as  $field_name) {
         foreach ($results as $key => $value) {
            $sku_data[$value['sku']][$field_name] = $value[$field_name];
         }
      }
      return $sku_data;

    }


    /**
     * 返回 是否可退税 退税率-税点≥1% ? '可退税' : '不可'
     * @param float $tax_rate 出口退税率
     * @param float $pur_ticketed_point 税点
     * @return number
     */
    public function getProductIsBackTax($tax_rate, $pur_ticketed_point) {
        if (empty($tax_rate) ||$pur_ticketed_point==0 || $tax_rate==0 || empty($pur_ticketed_point)) {
            return 0;
        }
        return $tax_rate - $pur_ticketed_point >= 1 ? 1 : 0;
    }

    public function getProductIsBackTaxNew($supplier_code,$tax_rate, $pur_ticketed_point){

        $supplierResult = $this->purchase_db->from('supplier')->where("supplier_code",$supplier_code)
            ->select('invoice')->get()->row_array();
        if($supplierResult['invoice'] == 1){

            return 0;
        }else{

           return $this->getProductIsBackTax($tax_rate, $pur_ticketed_point);
        }
    }

    /**
     * 推送最新采购价，平均运费成本， 加权平均价到产品系统
     * @author Jolon
     * @param $sku
     * @return array
     */
    public function plan_push_update_sku_price($sku){
        $return = ['code' => false,'data' => '','message' => ''];

        $product             = $this->get_product_info($sku);
        $params = [
            [
                'sku'      => $product['sku'],
                'newPrice' => $product['purchase_price'],// 产品最新采购价
                'shipCost' => 0,// 加权运费
                'avgPrice' => $product['purchase_price'],// 平均采购成本(含运费)
            ]
        ];

        $header = array('Content-Type: application/json');

        $request_url    = getConfigItemByName('api_config','product_system','yibaiProdSku-updateSkuPrice');
        $access_token   = getOASystemAccessToken();
        $request_url    = $request_url.'?access_token='.$access_token;
        $results        = getCurlData($request_url,json_encode($params),'post',$header);
        $results        = json_decode($results,true);

        if(isset($results['code'])){
            if($results['code'] == 200){
                $return['code'] = true;
                $status = 1;
            }else{
                $return['message'] = $results['msg'];
                $status = 0;
            }
            apiRequestLogInsert(
                [
                    'record_number'    => $sku,
                    'record_type'      => 'plan_push_update_sku_price',
                    'api_url'          => $request_url,
                    'post_content'     => $params,
                    'response_content' => $results,
                    'status'           => $status
                ]);
        }else{
            $return['message'] = isset($results['message'])?$results['message']:'执行出错';
        }

        return $return;
    }
     /**
      * 获取待编码产品信息
      * @author harvin
      * @date 2019-06-04
      * @param array $sku_product
      * @return array
      */
    public function get_product_update_list(array $sku_product){
        if(empty($sku_product)){
            return ['status'=>0,'errormasg'=>'sku不存在','data'=>[]];
        }
       $product=  $this->purchase_db->select('id,sku,product_name,product_img_url,'
                . 'ticketed_point,purchase_price,is_sample,supplier_code,'
                . 'supplier_name,product_cn_link,supply_status,'
                . 'starting_qty,starting_qty_unit')
                ->where_in('sku',$sku_product)->get($this->table_name)->result_array();
        if(empty($product)){
            return ['status'=>0,'errormasg'=>'产品表不存在','data'=>[]];
        }
        return ['status'=>1,'errormasg'=>'请求成功','data'=>$product];

    }

    public function getdata(){

        $res = $this->purchase_db->query("SELECT * FROM datas")->result_array();
        return $res;
    }

    public function getproduct( $sku ) {

        $res = $this->purchase_db->query(" SELECT * FROM pur_product WHERE sku='".$sku."'")->row_array();
        return $res;
    }

    public function getproductlog( $sku,$newmoney,$status ) {

        $res = $this->purchase_db->query(" SELECT * FROM pur_product_update_log WHERE sku='".$sku."' AND audit_status={$status}" )->row_array();
        return $res;
    }

    public function getProductSupplier($code) {
        $result = $this->purchase_db->query( " SELECT supplier_name FROM pur_supplier WHERE supplier_code='".$code."'")->row_array();
        return $result;
    }

    public function test() {


        $sql = $this->purchase_db->query(" SELECT * FROM supplier_test ")->result_array();
        return $sql;

    }

    public function get_sku_list($sku)
    {
        if (empty($sku)) return[];
        $sku_list = $this->purchase_db->select('sku')->like('sku',$sku,'after')->get($this->table_name)->result_array();

        if (empty($sku_list)){
            return[];
        }
        $return = array_column($sku_list,'sku');
        return $return;

    }

    public function send_http( $url, $data = NULL ) {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!$data){
            return 'data is null';
        }
        if(is_array($data))
        {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
    }

    /** 同步更新老采购系统货源状态
     * @param $sku sku
     * @param $supply_status 货源状态1正常2,停产3,断货
     * @return array
     */
    public function update_old_purchase_supplier_status($sku,$supply_status){

        $url    = getConfigItemByName('api_config','old_purchase','update-product-supply-status');
        $params = [
            'sku'               => $sku,
            'sourcing_status'   => $supply_status,
            'update_user_name'  => getActiveUserName(),
        ];
        $result = getCurlData($url,$params,'post');
        $res = json_decode($result,true);
        apiRequestLogInsert(
            [
                'record_number'    => $sku,
                'record_type'      => '新系统更新产品货源状态同步数据到老系统',
                'api_url'          => $url,
                'post_content'     => json_encode($params),
                'response_content' => $result,
                'status'           => $res['code'] == 200 ? 1: 0
            ]
        );
        return $res;
    }

    public function get_last_logs_id($sku)
    {
        return $this->purchase_db->from("product_update_log")->where("sku",$sku)->where("audit_status",3)->select("id,new_supplier_price")->order_by("id DESC")->get()->row_array();
    }
    /**
     * 同步新采购系统货源状态到ERP
     * @param string $sku    string    产品SKU
     * @param string $supply_status  string  货源状态
     * @return mixed
     */
    public function update_erp_purchase_supplier_status($sku,$supply_status)
    {
        $url    = getConfigItemByName('api_config','java_system_service','supply_status');
        $params = [
            'sku'               => $sku,
            'providerStatus'   => $supply_status,
        ];
        $url.="?access_token=". getOASystemAccessToken();
        $result =  $this->send_http($url,[$params]);
        $res = json_decode($result,true);
        $scree_log = array(

            'username' =>getActiveUserName(),
            'operation_time' => date('Y-m-d H:i:s',time()),
            'operation_type' => '修改货源状态',
            'operation_content' => "审核通过",
            'scree_id' => 0,
            'remark'   => "",
            'sku'      => $sku

        );

        $this->load->helper('status_product');
        $supplyStatusList = getProductsupplystatus();
        $scree_log['supply_status_ch'] = isset($supplyStatusList[$supply_status])?$supplyStatusList[$supply_status]:'正常';

        $this->purchase_db->insert('pur_product_scree_log',$scree_log);
        return $res;
    }


    /**
     * 更新 产品的最新起订量
     * @param string $sku                   SKU
     * @param int    $new_starting_qty      新最小清起订量
     * @param string $new_starting_qty_unit 新最小清起订量单位
     * @param int    $old_starting_qty      原最小清起订量
     * @param string $old_starting_qty_unit 原最小清起订量
     * @param string $remark                备注
     * @return bool
     */
    public function change_min_order_qty($sku,$new_starting_qty,$new_starting_qty_unit,$old_starting_qty,$old_starting_qty_unit,$remark = '修改产品信息'){
        $min_order_qty_content = '';
        if($old_starting_qty != $new_starting_qty) $min_order_qty_content .= "最新起订量从{$old_starting_qty}变成{$new_starting_qty}，";
        if($old_starting_qty_unit != $new_starting_qty_unit) $min_order_qty_content .= "最新起订量单位从{$old_starting_qty_unit}变成{$new_starting_qty_unit}，";
        if($min_order_qty_content){
            // 最小起订量（提交后，即自动变更）
            $this->update_one($sku,array('starting_qty' => $new_starting_qty,'starting_qty_unit' => $new_starting_qty_unit));
            if($min_order_qty_content){
                operatorLogInsert(// 最新起订量变更日志
                    [
                        'id'      => $sku,
                        'type'    => 'MIN_ORDER_QTY',
                        'content' => trim($min_order_qty_content,'，'),
                        'detail'  => $remark
                    ],
                    'product_operator_log'
                );
            }
        }

        return true;
    }

    /**
     * function: 获取老采购系统的SKU 下单记录
     * @param: $skus     string      商品SKU 信息
     * @return  array
     * @author:luxu
     **/

    public function old_purchase_order($skus)
    {
        if( !empty($this->config->item('old_purchase'))) {

            $items = $this->config->item('old_purchase');

            $header = array('Content-Type: application/json');

            $request_url    = getConfigItemByName('api_config','old_purchase','sku-purchase-order');
            $access_token   = getOASystemAccessToken();
            $request_url    = $items['sku-purchase-order'].'?access_token='.$access_token;
            $results        = getCurlData($request_url,json_encode(['sku'=>$skus]),'post',$header);
            $result         = json_decode( $results,True);
            if( isset($result['code']) && $result['code'] == 200 )
            {
                return $result['data'];
            }

            return NULL;
        }
        return NULL;
    }

    /**
     * function:获取新采购系统的SKU 下单记录
     * @param: $skus     string      商品SKU 信息
     * @return  array
     * @author:luxu
     **/
    public function new_purchase_order($skus)
    {
        if( !empty($skus) )
        {
            $query = $this->purchase_db->from("purchase_order_items AS orders")->join("purchase_order AS pur","orders.purchase_number=pur.purchase_number","left")->where("sku",$skus)->where_in('pur.purchase_order_status', [PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE]);
            $result = $query->select(" pur.source,orders.confirm_amount,
            pur.is_drawback,
            orders.product_base_price,
            orders.sku,pur.source,pur.audit_time AS createTime,pur.buyer_name AS buyer,pur.supplier_name AS supplierName,pur.supplier_code AS supplierCode,orders.purchase_number AS purNumber,purchase_unit_price AS price,orders.confirm_amount AS number")->get()->result_array();
            if( !empty($result) )
            {
                $compact_number= array_map( function($value){

                    if( !empty($value) && $value['source'] == 1)
                    {
                        return $value;
                    }
                },$result);

                $purchase_number = array_column( $compact_number,"purNumber");
                $purchase_sku = array_column( $compact_number,"sku");
                $compact_number = array_filter( $purchase_number );
                $compact_message = $compact_sku= [];
                if( !empty($compact_number) ) {

                    $compact_message = $this->purchase_db->from("warehouse_results")->where_in("sku",$purchase_sku)->where_in("purchase_number",$compact_number)->select("arrival_date,purchase_number,sku")->get()->result_array();
                    if( !empty($compact_message) )
                    {
                        $compact_sku = [];
                        foreach( $compact_message as $key=>$value)
                        {
                            $compact_sku[ $value['purchase_number'].'-'.$value['sku']] = $value['arrival_date'];
                        }

                    }
                }
                foreach( $result as $key=>&$value )
                {
                    if( $value['source'] == 1) {

                       $value['createTime'] = isset( $compact_sku[$value['purNumber']."-".$value['sku']])? $compact_sku[$value['purNumber']."-".$value['sku']]:'';
                    }
                    $instock_qty = $this->purchase_db->from("warehouse_results_main")->where("purchase_number",$value['purNumber'])->where("sku",$value['sku'])->select(" SUM(instock_qty) AS instock_qty")->get()->row_array();
                    if( empty($instock_qty['instock_qty']))
                    {
                        $value['number'] = 0;
                    }else {
                        $value['number'] = $instock_qty['instock_qty'];
                    }
                    //1合同 2网络【默认】 3账期采购
                    if($value['source'] == 1){

                        $value['source_ch'] = '合同';
                    }

                    if($value['source'] == 2){

                        $value['source_ch'] = '网采';
                    }

                    if($value['source'] == 3){

                        $value['source_ch'] = '账期采购';
                    }

                    $value['is_drawback_ch'] = getIsDrawbackShow($value['is_drawback']);
                    /*if($value['is_drawback']==1){

                        $value['is_drawback_ch'] = "否";
                    }else{
                        $value['is_drawback_ch'] = "是";
                    }*/

                    // 获取拍单号

                    $paiNumber = $this->purchase_db->from("purchase_order_pay_type")->where("purchase_number",$value['purNumber'])
                        ->select("pai_number")->get()->row_array();
                    $value['painumber_data'] = (isset($paiNumber['pai_number']) && !empty($paiNumber['pai_number']))?$paiNumber['pai_number']:'';
                    unset($value['sku']);
                    unset($value['source']);
                }

                return $result;
            }
        }

        return NULL;
    }

    public function get_tongtu_data($skus)
    {
        if( !empty($this->config->item('old_purchase'))) {

            $items = $this->config->item('old_purchase');

            $header = array('Content-Type: application/json');
            $erp_system = $this->config->item('old_purchase');
            $request_url    = isset($erp_system['tongtu'])?$erp_system['tongtu']:'';
            $access_token   = getOASystemAccessToken();
            $request_url    = $request_url.'?access_token='.$access_token;
            $results        = getCurlData($request_url,json_encode(['sku'=>$skus]),'post',$header);
            $api_log = [
                'record_number' => 10,
                'api_url' => '/product/accept_sku_price_log',
                'record_type' => '获取通途数据',
                'post_content' => $request_url,
                'response_content' => $results,
                'create_time' => date('Y-m-d H:i:s')
            ];
            $this->purchase_db->insert('api_request_log', $api_log);
            $result         = json_decode( $results,True);
            if( isset($result['code']) && $result['code'] == 200 )
            {
                return $result['data'];
            }

            return NULL;
        }
        return NULL;
    }

    /**
     * 获取开发单价SKU 修改历史记录
     * param: $sku    string    产品SKU
     * @author:luxu
     **/
    public function get_price_log($skus,$offset,$limit)
    {
        if( !empty($skus) )
        {
            $list_query = $this->purchase_db->from("product_price_log");
            $total_query = clone $list_query;
            $result = $list_query->where("sku",$skus)->limit($limit,$offset)->get()->result_array();
            $total = $total_query->where("sku",$skus)->count_all_results();
            return array(
                'list' => $result,
                'total' =>$total
            );
        }
        return NULL;
    }

    /**
     * 获取字段列表
     * @author:luxu
     **/

    public function get_product_config()
    {
        return $this->purchase_db->from("product_field")->where("status",0)->get()->result_array();
    }

    /**
     * 获取SKU 商品的品牌信息
     * @param:$sku string  商品SKU 信息
     * @author:luxu
     **/
    public function get_product_brand($sku){

        $result = $this->purchase_db->from("product")->where("sku",$sku)->select("product_brand,product_model,is_customized,original_devliy")->get()
            ->row_array();
        return $result;
    }

    /**
     * 获取原因
     * @author:luxu
     **/

    public function get_product_reason()
    {
        return $this->purchase_db->from("purchase_reason")->where("pid",0)->order_by("sort ASC")->get()->result_array();
    }

    /**
     * 添加原因
     * @author:luxu
     **/

    public function add_product_reason( $reaons)
    {
        if( !empty($reaons) )
        {
            foreach( $reaons as $key=>$value )
            {
                if( isset($value['id'])) {
                    $result = $this->purchase_db->from("purchase_reason")->where("id", $value['id'])->select("id")->get()->row_array();
                    if (!empty($result)) {
                        $update = array(

                            'reason' => $value['reason'],
                            'status' => $value['status'],
                            'sort' => $value['sort']
                        );

                        $this->purchase_db->where("id", $value['id'])->update("purchase_reason", $update);
                    } else {

                        $this->purchase_db->insert('purchase_reason', $value);
                    }
                }else{
                    $this->purchase_db->insert('purchase_reason', $value);
                }
            }

            return True;
        }
    }

    /**
     * 添加字段修改原因
     * @author:luxu
     **/
    public function add_reason( $reason )
    {
        try{

            if( !empty($reason) )
            {
                foreach( $reason as $key=>$value )
                {
                    $pids = $this->purchase_db->from("purchase_reason")->where("reason",$value['reason_ch'])->select("id")->get()->row_array();
                    $update = array(

                        'reason' => $value['reason_ch'],
                        'status' => $value['status'],
                        'feild' => $value['field'],
                        'pid'   => $pids['id']
                    );
                    if( isset($value['reason']))
                    {
                        $result = $this->purchase_db->from("purchase_reason")->where("id", $value['reason'])->select("id")->get()->row_array();
                        if (!empty($result)) {

                            $this->purchase_db->where("id", $value['reason'])->update("purchase_reason", $update);
                        }else{

                            $this->purchase_db->insert('purchase_reason', $update);
                        }
                    }else{

                        $this->purchase_db->insert('purchase_reason', $update);
                    }
                }
                return True;
            }
        }catch ( Exception $exp )
        {
            throw new Exception( $exp->getMessage());
        }
    }

    /**
     * 产品原因列表
     * @params: $field_name   string   产品字段名称
     * @author:luxu
     **/
    public function get_reason_list( $field_name = NULL,$is_product = NULL )
    {
        $query = $this->purchase_db->from("purchase_reason")->where("pid>",0);
        if( NULL != $field_name )
        {
            $field_names = explode(",",$field_name);
            $query->where_in("feild",$field_names);
        }

        if( NULL != $is_product )
        {
            $query->where("status",0);
        }

        $result = $query->group_by("reason")->get()->result_array();
        return $result;
    }

    /**
      * 获取一级分类
     **/
    public function get_parent_data()
    {
        $products = $this->purchase_db->from("product_attributes")->where("parent_id",0)->select("attribute_name,id")->get()->result_array();
        $logistics = $this->purchase_db->from("product_attributes")->where("parent_id",0)->select("attribute_name,id")->get()->result_array();
        if(!empty($products))
        {
            foreach($products as $key=>&$value)
            {
                if( $value['attribute_name'] == "订单属性"){

                    $products[$key]['attribute_name'] = "海外属性";
                }
            }
        }
        $productsData = array_column( $products,NULL,"attribute_name");
        if( !empty($logistics))
        {
            foreach($logistics as $key=>$value)
            {
                if($value['attribute_name'] == '订单属性'){
                    unset($logistics[$key]);
                }
            }
        }
        return array(
            'products' => $productsData,
            'logistices' => array_column( $logistics,NULL,"attribute_name")
        );
    }

    /**
     * c获取产品 SKU 商品属性和物流属性
     * @param: $sku  string 商品SKU
     * @author:luxu
     **/
    public function get_logistis_attribute($sku)
    {
       $logistis = $this->purchase_db->from("prod_logistics_audit_attr")->where("sku",$sku)->where("audit_attr_status",2)->select("new_product_attributes_value,country_code")->get()->result_array();

       $return_data = [];
       if(!empty($logistis)){

          $country_data = array_column( $logistis,NULL,"country_code");
          foreach($country_data as $key=>$value) {

              if(!empty($value['new_product_attributes_value'])) {
                  $product_attributes = json_decode($value['new_product_attributes_value'], True);

                  $attributes = [];
                  $attributes = array_map(function ($attri) {
                      if (!is_array($attri)) {
                          return $attri;
                      }
                  }, $product_attributes);
                  if(!empty($attributes)) {
                      $product_attribute_query = $this->purchase_db->from("product_attributes AS attributes")->join("product_attributes as parent", "parent.id=attributes.parent_id", "LEFT");
                      $product_attribute_result = $product_attribute_query->where_in("attributes.id", $attributes)->select("attributes.id,attributes.parent_id,attributes.attribute_name,parent.id AS pids,parent.attribute_name AS parent_name")->get()->result_array();

                      $attribute_level = [];
                      if (!empty($product_attribute_result)) {
                          foreach ($product_attribute_result as $attri_key => $attri_value) {
                              if (!isset($attribute_level[$attri_value['parent_name']])) {
                                  $attribute_level[$attri_value['parent_name']] = [];
                              }

                              $attribute_level[$attri_value['parent_name']][] = $attri_value['attribute_name'];
                          }
                          $return_data[$key] = $attribute_level;
                      }
                  }
              }
          }
       }
       return $return_data;
    }

    /**
     * 获取SKU 产品属性
     * @params   $sku   string  产品SKU
     * @author   luxu
     **/
    public function get_product_attribute_data($sku)
    {
        // 根据SKU 获取 商品的所有属性
        $attribute_data = $this->purchase_db->from("prod_sku_select_attr")->where("sku",$sku)->where("attr_type_id",1)->where("is_del",0)->select("attr_value_id,remark")->get()->result_array();
        $return_data = [];
        if( !empty($attribute_data) )
        {
            $attribute_ids = array_column( $attribute_data,"attr_value_id");
            $product_attributes_query = $this->purchase_db->from("product_attributes as children")->join("product_attributes as parent","children.parent_id=parent.id","LEFT")->where_in("children.id",$attribute_ids)->where("parent.parent_id",0);
            $product_attributes_result  = $product_attributes_query->where_in("parent.id",[PRODUCT_ATTR,PRODUCT_FROM_ATTR,PRODUCT_SPECIAL_ATTR,PRODUCT_BATTERY_ATTR,PRODUCT_ORDER_ATTR])->select("parent.attribute_name AS parent_name,children.attribute_name AS children_name,parent.parent_id AS parentid,children.parent_id AS children_parent_id,children.id AS cid,children.attr_type")->get()->result_array();

            if( !empty($product_attributes_result) )
            {

                $attributes = array_column( $attribute_data,NULL,"attr_value_id");
                foreach( $product_attributes_result as $key=>$value) {

                    if( !isset($return_data[$value['parent_name']]))
                    {
                        $return_data[ $value['parent_name']] = array();
                    }

                    if( $value['attr_type'] == 'text')
                    {
                        $return_data[$value['parent_name']][] = isset($attributes[$value['cid']]['remark'])?$attributes[$value['cid']]['remark']:NULL;
                    }
                    if($value['parent_name'] == "订单属性"){
                        $return_data['海外属性'][] = $value['children_name'];
                    }else {
                        $return_data[$value['parent_name']][] = $value['children_name'];
                    }

                }

            }
        }
        return $return_data;
    }

    /**
     * 获取产品系统说明书链接
     * @param  产品SKU
     **/
    public function get_product_link($sku)
    {
        $result = $this->purchase_db->from("prod_sku_link")->where("sku",$sku)->where("type",1)->where("is_del",0)->select("link,name")->get()->result_array();
        if( !empty($result))
        {
            $return_data = [];
            foreach($result as $key=>$value){
                $return_data[] = array(
                    'link'=>$value['link'],
                    'name' => $value['name']
                );
            }
            return $return_data;
        }
        return NULL;
    }

    /**
     * 获取产品系统的附件和说明信息
     * @param:$sku   string   商品SKU
     * @author:LUXU
     * 2020/2/13
     **/
    public function get_product_edit_common($sku)
    {
        $result = $this->purchase_db->from("prod_sku_edit_common")->where("sku",$sku)->where("is_del",0)->get()->result_array();
        $return_data = array(

            'typeData' => array(),
            'bookData' => array()
        );
        if(!empty($result)){


            foreach( $result as $key=>$value)
            {
                if( $value['type'] == 1)
                {
                    $return_data['typeData'][] = $value['remark'];
                }else{
                    $return_data['bookData'][] = $value['remark'];
                }
            }
            return $return_data;

        }
        return NULL;
    }

    /**
     * 获取国家的名称
     * @param $en_abbr array  国家标识
     * @author:luxu
     **/

    public function get_country($en_abbr){

        if(empty($en_abbr))
        {
            return NULL;
        }
        $result = $this->purchase_db->from("country")->where_in("en_abbr",$en_abbr)->select("en_abbr,cn_name")->get()->result_array();
        if( !empty($result))
        {
            $result = array_column( $result,NULL,"en_abbr");
        }
        return $result;
    }


    /**
     * 查询出所有的物流属性关系
     * @author Manson
     * @return array
     */
    public function product_attributes_map(){
        $map = [];
        $title = [
            '产品基础属性',
            '产品特殊属性',
            '产品电池属性',
            '产品违禁属性',
            '产品形态属性',
        ];

        $result = $this->purchase_db->select('attribute_name,id')
            ->where_in('attribute_name',$title)->get('pur_product_attributes')->result_array();
        $title = array_column($result,'id','attribute_name');

        foreach ($title as $attribute_name => $parent_id){
            $map[$parent_id] = $attribute_name;
            $sql = "SELECT id FROM pur_product_attributes WHERE parent_id = $parent_id";
            $result = $this->purchase_db->query($sql)->result_array();
            if (!empty($result)){
                foreach ($result as $item){
                    $map[$item['id']] = $attribute_name;
                }
            }

            while(true){
                $sql = "SELECT * FROM pur_product_attributes WHERE parent_id in (
SELECT id FROM pur_product_attributes WHERE parent_id in ($parent_id))";
                $result = $this->purchase_db->query($sql)->result_array();
                if (!empty($result)){
                    foreach ($result as $item){
                        $map[$item['id']] = $attribute_name;
                    }
                    $parent_id_arr[] = $item['id'];
                    $parent_id = implode(',',$parent_id_arr);

                }else{
                    break;
                }
            }
        }
        return $map;
    }

    /**
     * 查询sku的物流属性是否审核通过
     * @param: $sku string  产品SKU
     **/
    public function check_sku_logistics($sku,$country_code)
    {
        if (empty($sku) || empty($country_code)){
            throw new Exception('数据异常');
        }
        $result = $this->purchase_db->select('new_product_attributes_value')
            ->from('pur_prod_logistics_audit_attr')
            ->where('sku',$sku)
            ->where('country_code',$country_code)
            ->where('audit_attr_status',2)//审核通过
            ->get()->row_array();
        if (!empty($result)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 获取审核流程数据
     * @author:luxu
     * @time:2020/3/5
     **/

    public function getAuditProccess(){

        $result = $this->purchase_db->from("product_audit_process")->where("status",1)->get()->result_array();
        if(!empty($result))
        {
            $resultData = [];
            foreach( $result as $key=>$value)
            {
                if( !isset($resultData[$value['subject_en']])){
                    $resultData[$value['subject_en']] = [];
                }
                $resultData[$value['subject_en']][] = $value;
            }
        }
        return $resultData;
    }

    function arraySort($arr,$key,$type='asc'){
        $keyArr = []; // 初始化存放数组将要排序的字段值
        foreach ($arr as $k=>$v){
            $keyArr[$k] = $v[$key]; // 循环获取到将要排序的字段值
        }
        if($type == 'asc'){
            asort($keyArr); // 排序方式,将一维数组进行相应排序
        }else{
            arsort($keyArr);
        }
        foreach ($keyArr as $k=>$v){
            $newArray[$k] = $arr[$k]; // 循环将配置的值放入响应的下标下
        }
        $newArray = array_merge($newArray); // 重置下标
        return $newArray; // 数据返回
    }

    /**
     * SKU 审核顺序
     * @param $all_sku_proccess   array   SKU 的审核流程
     *        $contents_proccess  array   审核流程配置
     * @author:luxu
     * @time:2020/03/09
     **/
    public function sku_audit_proccess($all_sku_proccess,$contents_process){
        $contents_process = array_column( $contents_process,NULL,"nameflag");
        if(!empty($all_sku_proccess)){
            $return_data = array();
            foreach( $all_sku_proccess as $key=>$value){

                $return_data[$value] = isset($contents_process[$value])?$contents_process[$value]:NULL;
            }
            $return_data = $this->arraySort($return_data,'sort','asc');
           // $return_data = array_keys($return_data);
            return $return_data;
        }

        return NULL;
    }

    /**
     * 获取SKU 修改未税单价的流程
     * @param   $price_ratio   float   未税单价修改比例
     *          $proccess      array   未税单价审核流程
     * @author:luxu
     * @time:2020/3/5
     **/
    public function getSkuProccess($price_ratio,$proccess) {

        /**
           如果审核流程为空，或者降价比例为空 或者0。 表示不用审核
         **/
        if( empty($proccess) || empty($price_ratio) || $price_ratio == 0.0000){

            return NULL;
        }
        $sku_proccess = NULL;

        if($price_ratio <0 ){

            $sku_proccess = $proccess[0];
        }
        foreach( $proccess as $proccess_key=>$proccess_value){

            if( $price_ratio>=$proccess_value['audit_start'] && $price_ratio<=$proccess_value['audit_end']){

                $sku_proccess = $proccess[$proccess_key];
                break;
            }
        }

        if(empty($sku_proccess)){
           $endProccess = array_slice($proccess,-1,1);
           $sku_proccess = $endProccess[0];
        }

        $proccess_data = json_decode( $sku_proccess['audit_process'],True);
        $return_data = array_map(function($data){

            if( $data['purchase'] == 1){
                return $data['flag'];
            }
        },$proccess_data);
        // 去除重复返回
        return  array_filter($return_data);
    }

    /**
     * 审核流程获取
     * @param   $proccess    array  审核完整流程
     * @author:luxu
     * @time:2020/3/5
     **/
    public function getSkuOther($proccess){

        /**如果审核流程为空，就表示不用审核**/
        if( empty($proccess) || NULL == $proccess ){

            return NULL;
        }
        $return_data =[];
        foreach($proccess as $proccess_key=>$proccess_value){

            $values = json_decode($proccess_value['audit_process'],True);
            if(!empty($values)){

                foreach($values as $key=>$value){

                    if( $value['purchase'] == 1){
                       $return_data[] = $value['flag'];
                    }
                }
            }
        }

        return  array_filter($return_data);
    }

    /**
     * SKU 对应采购单变为等待到货事修改SKU 商品是否新品为否
     * @param  $skus  array 商品SKU
     * */
    public function updateProductNew($skus){

         $this->purchase_db->where_in("sku",$skus)->update("product",['is_new'=>0]);


    }

    public function updateProductOverson($skus){

        $this->purchase_db->where_in("sku",$skus)->update("product",['is_overseas_first_order'=>0]);

    }

    /**
     * 获取新品的SKU信息
     * @params  $sku  string  商品SKU
     * @author:luxu
     * @time:2020/3/16
     * @return BOOL TRUE 表示是新品 FALSE 表示不是新品
     **/
    public function getProductNew($skus){

        $result = $this->purchase_db->from("product")->where_in("sku",$skus)->where("is_new",PRODUCT_SKU_IS_NEW)->get()->result_array();
        if(!empty($result)){
            return True;
        }
        return False;
    }

    /**
     * 判断SKU 是否为海外仓首单
     * @param   $sku  string   商品SKU
     * @author:luxu
     * @time:2020/3/20
     **/
    public function is_overseas_first_order($skus){

        $result = $this->purchase_db->from("product")->where_in("sku",$skus)->where("is_overseas_first_order",1)->get()->row_array();
        if(!empty($result)){
            return True;
        }
        return False;
    }

    /**
     * 获取外箱配置信息
     * @author:luxu
     * @time:2020/4/13
     **/
    public function getProductSize($limit = NULL,$offset = NULL){

        $search_query = $this->purchase_db->from("product_size");
        $sum_query = clone $search_query;
        if( NULL !== $limit && NULL !== $offset) {
            $result = $search_query->limit($limit, $offset)->get()->result_array();
        }else{
            $result = $search_query->get()->result_array();
        }
        $total = $sum_query->count_all_results();

        return  array(
            'list' => $result,
            'page' =>['total'=>$total,'limit'=>!empty($limit)?$limit:0,'offset'=>!empty($offset)?$offset:1]
        );
    }

    /**
     * 修改外箱配置信息
     * @author:luxu
     * @time:2020/4/13
     **/
    public function updateProductSize($data){

        if(!empty($data)){

            // 如果数据包含ID  更新， 不包含就插入
            $insertData = $updateData = [];

            foreach( $data as $key=>&$value){
                if(isset($value['id']) && !empty($value['id'])){

                    $value['update_user'] = getActiveUserName();
                    $value['create_user'] = getActiveUserName();
                    $value['update_time'] = date("Y-m-d H:i:s",time());
                    $updateData[] = $value;
                }else{
                    $value['create_time'] = date("Y-m-d H:i:s",time());
                    $value['create_user'] = getActiveUserName();
                    $value['update_user'] = getActiveUserName();
                    $insertData[] = $value;
                }
            }
            try{

                $this->purchase_db->trans_begin();
                $updateResult = $insertResult = True;
                if( !empty($insertData)){

                    $insertResult = $this->purchase_db->insert_batch('product_size',$insertData);
                }

                if( !empty($updateData)){

                    $updateResult = $this->purchase_db->update_batch('product_size', $updateData,'id');
                }

                if( $updateResult != True || $insertResult != True){

                    throw new Exception("数据操作失败");
                }
                $this->purchase_db->trans_commit();
                return True;
            }catch ( Exception $exp ){
                $this->purchase_db->trans_rollback();
                return False;
            }

        }
    }



    /**
     * 获取 历史供应商sku
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function get_product_list_history($params, $offset, $limit,$field='*',$export=false)
    {
        $query_builder = $this->purchase_db;

        //排序
        $outsideWhere = ' where 1=1';
        $insideSql = ' and lo.id in (SELECT t.id
 FROM (SELECT * FROM pur_product_update_log ORDER BY audit_time DESC LIMIT 200000 ) t
GROUP BY t.sku,t.new_supplier_code ORDER BY audit_time DESC  )';
        $outsideSql = '';

        $category_all_ids = [];
        if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {
            $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);


        }


        /*  $this->purchase_db->select($field);
          $this->purchase_db->from('product as p');*/

        // SKU 产品类型为普通
        //$this->purchase_db->where('p.product_type',1)->where('p.is_multi!=2');
        if (!empty($params['product_line_id'])||!empty($params['product_name'])) {

            if (!empty($params['product_line_id'])) {
                $children_id = $category_all_ids;
                $children_ids = explode(",", $children_id);
                $children_ids = array_filter($children_ids);
                $children_ids_str = implode(',',$children_ids);

                $outsideWhere.= " and p.product_line_id in ({$children_ids_str})";


                unset($params['product_line_id']);

            }

            if (!empty($params['product_name'])) {
                $outsideWhere.= " and p.product_name like '%".$params['product_name']."%'";

            }


        }



        if (!empty($params['product_status'])) {
            $outsideWhere.= " and p.product_status={$params['product_status']} ";

        }

        if (!empty($params['is_purchasing'])) {
            $outsideWhere .=' and lo.old_is_purchasing='.$params['is_purchaseing'];

        }

        if (isset($params['sku']) && !empty(trim($params['sku']))) {
            $params['sku'] = trim($params['sku']);
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $outsideWhere.=" and lo.sku like '%".$params['sku']."%'";
            } else {
                $sku_str='';

                foreach ($sku as $skuinfo) {
                    $sku_str.="'".$skuinfo."',";
                }
                $sku_str = trim($sku_str,',');
                $outsideWhere.=" and lo.sku in ({$sku_str})";

            }
        }

        if (!empty($params['ids'])) {
            $ids_str = implode(',',$params['ids']);
            $outsideWhere.=" and lo.id in ({$ids_str})";

        }
        if (!empty($params['supplier_code'])) {
            if (is_array($params['supplier_code'])) {

                $supplier_str='';

                foreach ($params['supplier_code'] as $supplier) {
                    $supplier_str.="'".$supplier."',";
                }
                $supplier_str = trim($supplier_str,',');
                $outsideWhere.=" and lo.old_supplier_code in ({$supplier_str})";


            } else {
                $outsideWhere.=" and lo.old_supplier_code ='{$params['supplier_code']}'";
            }
        }


        if (!empty($params['status'])||!empty($params['supplier_source'])) {

            if (!empty($params['status'])) {
                if ($params['status']==1) {//供应商已禁用
                    $outsideWhere .=" and ps.status =2";

                } else {
                    $outsideWhere .=" and ps.status !=2";

                }

            }
            if (!empty($params['supplier_source'])){
                $outsideWhere .=" and ps.supplier_source ={$params['supplier_source']}";

            }

        }


        if( isset($params['reason']) && !empty($params['reason']))
        {

            $reasons = array_map(function($reason){

                return sprintf("'%s'",$reason);
            },$params['reason']);
            $reasons = implode(",",$reasons);

            $outsideSql.= " left join  pur_log_reason on pur_log_reason.log_id=lo.id  ";

            $outsideWhere.=" and pur_log_reason.reason in ({$reasons})";

        }

        if( isset($params['create_time_start']) && !empty($params['create_time_start']))
        {
            $outsideWhere.=" and audit_time>='{$params['create_time_start']}'";


        }

        if( isset($params['create_time_end']) && !empty($params['create_time_end']))
        {
            $outsideWhere.=" and audit_time<='{$params['create_time_end']}'";

        }

        $sql="select".$field." from pur_product_update_log lo";
        $outsideSql.=' left join pur_product p on p.sku=lo.sku ';
        $outsideSql.=' left join pur_supplier ps on ps.supplier_code=lo.old_supplier_code ';
        $outsideWhere.=' AND new_supplier_code !=old_supplier_code AND lo.audit_status=3 ';


        $sql.=$outsideSql.$outsideWhere.$insideSql;


        //统计总数
        $clone_db = clone($query_builder);
        $count_sql = "select count(lo.sku) as num from pur_product_update_log lo";
        $count_sql.= $outsideSql.$outsideWhere.$insideSql;

        $all_number = $clone_db->query($count_sql)->row_array();
        $total_count = $all_number['num'];


        if ($export) {//导出不需要分页查询

            $results = $query_builder->query($sql)->result_array();

        } else {//列表查询

            $insidelimit = " LIMIT {$offset},{$limit}";

            $sql.=$insidelimit;
            $results = $query_builder->query($sql)->result_array();


        }
        if (!empty($results)) {

            foreach ($results as $key => &$value) {

                $category_all = $this->product_line_model->get_all_parent_category($value['product_line_id']);
                $value['category_line_data'] = $category_all;

            }
        }

        $return_data = [
            'value' => $results,
            'page_data' => [
                'total' => $total_count,
                'limit' => $limit,
            ]
        ];
        return $return_data;
    }




    /**
     * 统计历史供应商sku个数
     **/

    public function get_history_product_sum( $params ) {

        $query_builder = $this->purchase_db;

        //排序
        $outsideWhere = ' where 1=1';
        $insideSql = ' and lo.id in (SELECT t.id
 FROM (SELECT * FROM pur_product_update_log ORDER BY audit_time DESC LIMIT 200000 ) t
GROUP BY t.sku,t.new_supplier_code ORDER BY audit_time DESC  )';
        $outsideSql = '';

        $category_all_ids = [];
        if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {
            $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);


        }
        /*  $this->purchase_db->select($field);
          $this->purchase_db->from('product as p');*/

        // SKU 产品类型为普通
        //$this->purchase_db->where('p.product_type',1)->where('p.is_multi!=2');
        if (!empty($params['product_line_id'])||!empty($params['product_name'])) {

            if (!empty($params['product_line_id'])) {
                $children_id = $category_all_ids;
                $children_ids = explode(",", $children_id);
                $children_ids = array_filter($children_ids);
                $children_ids_str = implode(',',$children_ids);
                $outsideWhere.= " and p.product_line_id in ({$children_ids_str})";

                unset($params['product_line_id']);


            }


            if (!empty($params['product_status'])) {
                $outsideWhere.= " and p.product_status={$params['product_status']} ";

            }


        }

        if (!empty($params['product_name'])) {
            $outsideWhere.= " and p.product_name like '%".$params['product_name']."%'";

        }

        if (!empty($params['is_purchasing'])) {
            $outsideWhere .=' and lo.old_is_purchasing='.$params['is_purchaseing'];

        }


        if (isset($params['sku']) && !empty(trim($params['sku']))) {
            $params['sku'] = trim($params['sku']);
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $outsideWhere.=" and lo.sku like '%".$params['sku']."%'";
            } else {
                $sku_str='';

                foreach ($sku as $skuinfo) {
                    $sku_str.="'".$skuinfo."',";
                }
                $sku_str = trim($sku_str,',');
                $outsideWhere.=" and lo.sku in ({$sku_str})";

            }
        }


        /*    if (!empty($params['ids'])) {
                if (is_array($params['ids'])) {

                    $this->purchase_db->where_in('p.id', $params['ids']);
                } else {
                    $this->purchase_db->where_in('p.id', explode(',', $params['ids']));
                }
            }*/
        if (!empty($params['supplier_code'])) {
            if (is_array($params['supplier_code'])) {

                $supplier_str='';

                foreach ($params['supplier_code'] as $supplier) {
                    $supplier_str.="'".$supplier."',";
                }
                $supplier_str = trim($supplier_str,',');
                $outsideWhere.=" and lo.old_supplier_code in ({$supplier_str})";


            } else {
                $outsideWhere.=" and lo.old_supplier_code ='{$params['supplier_code']}'";
            }
        }




        if (!empty($params['status'])||!empty($params['supplier_source'])) {

            if (!empty($params['status'])) {
                if ($params['status']==1) {//供应商已禁用
                    $outsideWhere .=" and ps.status =2";

                } else {
                    $outsideWhere .=" and ps.status !=2";

                }

            }
            if (!empty($params['supplier_source'])){
                $outsideWhere .=" and ps.supplier_source ={$params['supplier_source']}";

            }

        }


        if( isset($params['reason']) && !empty($params['reason']))
        {

            $reasons = array_map(function($reason){

                return sprintf("'%s'",$reason);
            },$params['reason']);
            $reasons = implode(",",$reasons);
            $outsideSql.= " left join ( SELECT log_id FROM pur_log_reason WHERE reason IN ({$reasons}) GROUP BY log_id  ) AS pur_log_reason  ";

            $outsideWhere.=" and pur_log_reason.log_id=lo.id";

        }


        if( isset($params['create_time_start']) && !empty($params['create_time_start']))
        {
            $outsideWhere.=" and lo.audit_time>='{$params['create_time_start']}'";


        }

        if( isset($params['create_time_end']) && !empty($params['create_time_end']))
        {
            $outsideWhere.=" and lo.audit_time<='{$params['create_time_end']}'";

        }

        //统计总数
        $clone_db = clone($query_builder);
        $count_sql = "select count(lo.sku) as num from pur_product_update_log lo";

        $outsideSql.=' left join pur_product p on p.sku=lo.sku ';
        $outsideSql.=' left join pur_supplier ps on ps.supplier_code=lo.old_supplier_code ';
        $outsideWhere.=' AND new_supplier_code !=old_supplier_code AND lo.audit_status=3 ';

        $count_sql.= $outsideSql.$outsideWhere.$insideSql;
        $all_number = $clone_db->query($count_sql)->row_array();
        $total_count = $all_number['num'];
        return $total_count;
    }

    /**
     * sku详情页，增加商品参数，需从新产品系统获取
     * @params $skus  string  商品SKU
     **/
    public function get_selectbysku($skus){
        $params = [
            'skus'               => [$skus],
            'languageCode'   => 'CN',
        ];
        $header = array('Content-Type: application/json');

        $request_url    = getConfigItemByName('api_config','java_system_service','get_selectbysku');
        $access_token   = getOASystemAccessToken();
        $request_url    = $request_url.'?access_token='.$access_token;
        $res        = getCurlData($request_url,json_encode($params),'post',$header);
        $res = json_decode($res,True);
        if(!empty($res) && $res['code'] == 200 && isset($res['data'][0]['goodsParams'])){

            return $res['data'][0]['goodsParams'];
        }

        return NULL;

    }

    /**
     * 获取SKU 是否为退税
     * @param $skus   array   SKU
     * @author:luxu
     * @time:2020/5/21
     **/
    public function getSkusIsdrackback($skus){

        try{

            $result = $this->purchase_db->from("product")->where_in("sku",$skus)->where("is_drawback",0)->select("sku")->get()->result_array();
            return $result;
        }catch ( Exception $exp ){

            throw new Exception( $exp->getMessage());
        }
    }

    /**
     * @function:商品管理SKU 退税率，修改日志。
     * 采购系统并不修改退税率，数据来自物流系统
     * @author:luxu
     * @time:2020年7月14号
     **/

    public function rateLogs($skus){

        return $this->purchase_db->from("drawback_log")->where("sku",$skus)->get()->result_array();
    }


    /**
     * @function:商品管理SKU 开票点修改人在
     * 采购系统是可以修改开票点
     * @author:luxu
     * @time:2020年7月14号
     **/

    public function ticketedPoint($skus){

        return $this->purchase_db->from("product_update_log")->where("sku",$skus)
            ->where("old_ticketed_point!=new_ticketed_point")->where("audit_status=3")
            ->select("old_ticketed_point,new_ticketed_point,audit_time,create_remark AS audit_remark,audit_user_name")
            ->get()->result_array();
    }

    /**
     * 设置产品管理头部信息
     * @author:luxu
     * @time:2021年3月29号
     **/
    public function save_table_list($data, $type, $p_uid = null){

        $res = [
            'code'  => 0,
            "msg"   => ''
        ];
        $user_id = getActiveUserId();
        //$this->purchase_db->trans_begin();
        try{

           $searchData = [];
           $searchData = array_map(function($data_value){

               if( $data_value['status'] ==1){

                   return $data_value;
               }
           },$data);
           $searchData = array_filter($searchData);

            $sort = array(
                'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'index',       //排序字段
            );
            $arrSort = array();
            $searchData = array_values($searchData);
            $sorrkey = array_column($searchData,'index');
            array_multisort($sorrkey, SORT_ASC, $searchData);

            $where = [

                'user_id' => $p_uid,
                'list_type' => $type
            ];

            $keyDatas = [];
            foreach( $data as $data_key=>$data_value){
                if( !isset($keyDatas[$data_value['name']])){

                    $keyDatas[$data_value['name']] = [];
                }
                $data_value['key'] = $data_key;
                $data_value['feild'] = $data_value['val'];
                unset($data_value['val']);
                $keyDatas[$data_value['name']] = $data_value;
            }

            foreach( $searchData as $search_key=>&$search_value){

                if(isset($keyDatas[$search_value['name']])){

                    $search_value['key'] = $keyDatas[$search_value['name']]['key'];
                    $search_value['feild'] = $keyDatas[$search_value['name']]['feild'];
                    unset($search_value['val']);
                }
            }
            $datas = [

                'user_id' => $p_uid,
                'list_type' =>$type,
                'header_content' => json_encode($searchData),
                'create_time' =>date("Y-m-d H:i:s",time())
            ];
            $result = $this->purchase_db->from("product_header")->where($where)->select("id")->get()->row_array();
            if(empty($result)){

                $res = $this->purchase_db->insert('product_header',$datas);
            }else{
                $res = $this->purchase_db->where($where)->update('product_header',$datas);
            }

            if($res){

                return True;
            }

            return False;

        }catch(Exception $e){
           return False;
        }
    }

    /**
     * 获取用户选择的字段信息
     * @param:$uid   int  用户ID
     * @author:luxu
     * @time:2021年3月30号
     **/
    public function get_user_search($uid,$type='product_list'){

        $results = $this->purchase_db->from("product_header")->where("user_id",$uid)
            ->where("list_type",$type)->get()->row_array();
        if(empty($results)){

            return NULL;
        }

        $searchData = json_decode($results['header_content'],true);
        return $searchData;
    }

    /**
     * 获取用户设置的头部信息
     **/
    public function get_user_header($uid=1671,$headertype='product_list',$header){
        $result = $this->get_user_search($uid,$headertype);

        if(!empty($result)){

            return $result;
        }
        $headerData = [];
        foreach($header as $key=>$value){
            if( !isset($headerData[$value['name']])) {

                $headerData[$value['name']] = [];
            }
            $value['key'] = $key;
            $value['field'] = $value['val'];
            unset($value['val']);
            $headerData[$value['name']] = $value;
        }
        $result = array_values($headerData);
        return $result;
    }

    /**
     * 在产品管理pur_product 表中获取SKU 指定的相关信息
     * @param $sku     string|array  SKU
     *        $feild   string|array        查询字段
     * @author:luxu
     * @time:2021年4月24号
     **/

    public function get_product_data($sku,$feild='*'){

       $query = $this->purchase_db->from("product");
       // 如果传入的参数 为字符串
       if(is_string($sku) && !empty($sku)){
           $query->where("sku",$sku);
       }
       // 如果传入的参数为数据，使用IN
       if( is_array($sku) && !empty($sku)){
           $query->where_in("sku",$sku);
       }
       // 如果传入的feild  就转换为字符串
       if(is_array($feild)){
           $feild = explode(",",$feild);
       }
       $result = $query->select($feild)->get()->result_array();
       return $result;
    }

    /**
     * 保存修改数据
     * @author harvin
     * @date 2019-0604
     */
    public function update_product_save($data_json = array(),$import = False,$username = NULL,$product_mod_audit_other = NULL,$usernameid=NULL){

        $this->load->model('supplier/Supplier_model','supplier_model',false,'supplier');
        if(empty($data_json)){
            return NULL;
        }
        $data_arr                = json_decode($data_json, true);
        // $this->_bulk = new MongoDB\Driver\BulkWrite();
        // $this->_bulk->insert($data_arr);
        // $result = $this->_mongodb->executeBulkWrite("{$this->_author_db}.product_audit", $this->_bulk);
        $sku_list                = $data_arr['sku_list'];
        $new_supplier_price      = $data_arr['new_supplier_price']; //未税单价
        $new_ticketed_point      = $data_arr['new_ticketed_point']; //开票税点
        $new_starting_qty        = $data_arr['new_starting_qty']; //最小起订量
        $new_starting_qty_unit   = $data_arr['new_starting_qty_unit']; //最小起订量单位
        $new_ali_ratio_own       = $data_arr['new_ali_ratio_own']; //单位对应关系（内部）
        $new_ali_ratio_out       = $data_arr['new_ali_ratio_out']; //单位对应关系（外部）
        $new_supplier_code       = $data_arr['new_supplier_code']; //供应商编码
        $new_supplier_name       = $data_arr['new_supplier_name']; //供应商名称
        $new_product_link        = $data_arr['new_product_link']; //采购链接
        $new_product_coupon_rate = $data_arr['coupon_rate']; //票面税
        $is_sample               = $data_arr['is_sample']; //是否拿样
        $supply_status           = $data_arr['supply_status']; //货源状态
        $create_remark           = $data_arr['create_remark'];//申请备注
        $is_customized           = $data_arr['is_customized']; // 是否定制
        $is_purchasing           = isset($data_arr['is_purchasing']) ? $data_arr['is_purchasing'] : null; // 是否待购
        $is_force_submit         = isset($data_arr['is_force_submit']) ? $data_arr['is_force_submit'] : null; // 是否强制提交,1.是,其他.否
        $reason                  = isset($data_arr['reason'])?$data_arr['reason']:NULL;
        $inside_number           = isset($data_arr['inside_number'])?$data_arr['inside_number']:NULL; // 箱内个数
        $box_size                = isset($data_arr['box_size'])?$data_arr['box_size']:''; // 箱内尺寸
        $outer_box_volume        = isset($data_arr['outer_box_volume'])?$data_arr['outer_box_volume']:''; //外箱体积
        $product_volume          = isset($data_arr['product_volume'])?$data_arr['product_volume']:''; //产品体积
        $devliy                  = $data_arr['devliy']; // 交期
        $long_devliy             = isset($data_arr['long_devliy'])?$data_arr['long_devliy']:'';
        $is_shipping             = isset($data_arr['is_shipping'])?$data_arr['is_shipping']:'';
        //转化数组
        $sku_product = explode(',', $sku_list);
        if (empty($sku_product) && !is_array($sku_product)) {
            throw new Exception("输入参数不合法");
        }
        $this->load->model('supplier/supplier_model');
        $this->load->library('alibaba/AliProductApi');
        $this->load->model('ali/ali_product_model');
        $data_sku     = $data_error = [];
        $audit_status = $audit_level = '';

        $error_list = [];
        $is_supplier_abnormal = false;
        // 验证数据是否满足要求（有任何一个不满足要求即退出）
        foreach ($sku_product as $sku){
            if($new_supplier_price[$sku] === ''){
                $error_list[$sku] = '单价不能为空';
                continue;
            }
            if(empty($new_supplier_code[$sku])){
                $error_list[$sku] = '供应商代码不能为空';
                continue;
            }
            if(strpos($new_supplier_name[$sku],'(正常)') !== false){

                $new_supplier_name[$sku] = str_replace('(正常)','',$new_supplier_name[$sku]);
            }

            if(strpos($new_supplier_name[$sku],'()') !== false){

                $new_supplier_name[$sku] = str_replace('()','',$new_supplier_name[$sku]);
            }

            if(empty($new_supplier_name[$sku])){
                $error_list[$sku] = '供应商名称不能为空';
                continue;
            }
            if(empty($new_product_link[$sku])){
                $error_list[$sku] = '采购链接不能为空';
                continue;
            }

            if(empty($is_shipping[$sku])){

                $error_list[$sku] = '是否包邮不能为空';
                continue;
            }

            /*
            if ($new_ticketed_point[$sku] == '')
                $this->error_json('税点不能为空');
            */
            if($is_sample[$sku] == ''){
                $error_list[$sku] = '是否拿样不能为空';
                continue;
            }

            if($supply_status[$sku] == ''){
                $error_list[$sku] = '货源状态不能为空';
                continue;
            }
            if($new_product_coupon_rate[$sku] == ''){
                $error_list[$sku] = '票面税率不能为空';
                continue;
            }

            if(!is_two_decimal($new_supplier_price[$sku])){
                $error_list[$sku] = '单价小数最多只能为两位';
                continue;
            }

            if(is_numeric($new_ticketed_point[$sku]) && !is_two_decimal($new_ticketed_point[$sku])){
                $error_list[$sku] = '开票点小数最多只能为两位';
                continue;
            }

            // 判断SKU 绑定的供应商是否支持开票
            $isSupplierInvoice  = $this->supplier_model->isSupplierInvoice($new_supplier_code[$sku]);
            // 如果供应商支持开票,判断开票点是否为0或者空
            if( True == $isSupplierInvoice  && ($new_ticketed_point[$sku] == 0.00 || empty($new_ticketed_point[$sku])) )
            {
                $error_list[$sku] = 'sku:'.$sku."关联供应商支持开票点，开票点必填";
                continue;
            }



            $product_log_info = $this->product_update_log->get_product_log_info($sku);

            if (in_array($product_log_info['audit_status'], [PRODUCT_UPDATE_LIST_AUDITED, PRODUCT_UPDATE_LIST_QUALITY_AUDIT, PRODUCT_UPDATE_LIST_FINANCE])) {

                $error_list[$sku] = "SKU:".$sku.",存在待审核记录";
                continue;
            }




            //根据SKU查找产品数据
            $product_info = $this->get_product_info($sku);
            if(empty($product_info)){
                $error_list[$sku] = '找不到该SKU:'.$sku.'数据';
                continue;
            }

            if( ($product_info['ticketed_point'] != $new_ticketed_point[$sku]

                    || $product_info['purchase_price'] != $new_supplier_price[$sku]
                    || $product_info['starting_qty'] != $new_starting_qty[$sku]
                    || $product_info['starting_qty_unit'] != $new_starting_qty_unit[$sku]
                    || $product_info['supplier_code'] != $new_supplier_code[$sku]
                    || $product_info['is_purchasing'] != $is_purchasing[$sku]
                    || $product_info['product_cn_link'] != $new_product_link[$sku]
                ) && empty($reason[$sku]))
            {
                $error_list[$sku] = $sku." 请填写修改原因";
                continue;
            }

            $reason_key = NULL;
            if( $product_info['ticketed_point'] != $new_ticketed_point[$sku] ){

                $reason_key .= "ticketed_point,";
            }

            if(  $product_info['purchase_price'] != $new_supplier_price[$sku] )
            {
                $reason_key .="purchase_price,";
            }

            if( $product_info['starting_qty'] != $new_starting_qty[$sku] || $product_info['starting_qty_unit'] != $new_starting_qty_unit[$sku] )
            {
                $reason_key.="starting_qty,";
            }

            if( $product_info['supplier_code'] != $new_supplier_code[$sku] )
            {
                $reason_key.="supplier_code,";
            }
            if( $product_info['product_cn_link'] != $new_product_link[$sku] )
            {
                $reason_key.="product_cn_link,";
            }

            if( $product_info['is_purchasing'] != $is_purchasing[$sku] )
            {
                $reason_key.="is_purchasing,";
            }

            if( $product_info['supply_status'] != $supply_status[$sku]){

                $reason_key .= "source_status";
            }

            if( NULL != $reason_key )
            {
                $reasons = $this->get_reason_list($reason_key,1);
                $reasons_message = array_column( $reasons,"reason");
                $reason_flag = False;
                if( !is_array($reason[$sku]))
                {
                    $reason[$sku] = explode(",",$reason[$sku]);
                }
                foreach( $reason[$sku] as $reason_value)
                {
                    if( !in_array($reason_value,$reasons_message))
                    {
                        $reason_flag = True;
                        continue;
                    }
                }

               /* if( $reason_flag == True )
                {
                    $error_list[$sku] = $sku." 修改原因填写错误";
                    continue;
                }*/
            }

            // 验证供应商是否存在
            $supplier_info = $this->supplier_model->get_supplier_info($new_supplier_code[$sku], false);

            $supplier_message = $this->get_supplier_data($new_supplier_name[$sku],['status'=>[1,6]]);
            if(empty($supplier_message)){
                $error_list[$sku] = '找不到该供应商:'.$new_supplier_code[$sku].'数据,只允许导入启用状态的常规供应商';
                continue;
            }

            /*
               需求：33110 产品管理的导出增加"一级产品线",增加验证SKU与供应商的是否包邮一致 #2
               SKU信息修改变更供应商时,验证:SKU的是否包邮与修改后供应商的是否包邮一致,否则不允许提交修改,
               报错:"SKU****与供应商的是否包邮不一致,请重新确认"
               前端传值 1 表示 包邮  2表示不包邮， 供应商表 1 是,  2 否' ,
            */
            if(trim($new_supplier_code[$sku]) != trim($product_info['supplier_code'])){

//                if($supplier_info['sku_shipping_num']>0){
//                    $supplier_info['is_postage'] =1;
//                }else{
//                    $supplier_info['is_postage'] =2;
//                }

                if($supplier_info['is_postage'] == 3){

                    $supplier_info['is_postage'] = 2;
                }
                if($supplier_info['is_postage'] != $is_shipping[$sku]){

                    $error_list[$sku] = $sku."与供应商:".$new_supplier_name[$sku].",是否包邮不一致";
                    continue;
                }
            }

            // 验证供应商CODE 和供应商名称是否一致

            if( trim($supplier_info['supplier_name']) != trim($new_supplier_name[$sku])){

                $error_list[$sku] = $sku.'供应商名称和供应商CODE不一致';
                continue;
            }

            /*     if($supplier_info['status']==PRE_DISABLE){
                     $error_list[$sku] = '供应商:'.$new_supplier_code[$sku].'预禁用';
                     continue;
                 }*/
            $oldsupplier_info = $this->supplier_model->get_supplier_info($product_info['supplier_code'], false);

            if($oldsupplier_info['supplier_code'] != $new_supplier_code[$sku]){
                // 来源为常规
                if($supplier_info['supplier_source'] == 3 && $oldsupplier_info['supplier_source'] == 1 && $product_info['supply_status'] == PRODUCT_SCREE_APPLY_REASON_SUCCESS){
                    $error_list[$sku] = '原供应商为常规并且SKU 货源状态为正常:'.$new_supplier_code[$sku].'不允许变更供应商';
                    continue;
                }
            }

            // 验证供应商CODE 和名字是否一致

            $newsupplier_info = $this->supplier_model->get_supplier_info($new_supplier_code[$sku], false);

            //根据SKU查找产品修改记录
            $product_log_info = $this->product_update_log->get_product_log_info($sku);
            if(in_array($product_log_info['audit_status'], [PRODUCT_UPDATE_LIST_AUDITED,
                PRODUCT_UPDATE_LIST_QUALITY_AUDIT,
                PRODUCT_UPDATE_LIST_FINANCE,
                PRODUCT_UPDATE_LIST_PURCHASE,
                PRODUCT_EXECUTIVE_DIRECTOR,
                PRODUCT_DEPUTY_MANAGER,
                PRODUCT_DEVELOPMENT,
                PRODUCT_PURCHASING_MANAGER,
                PRODUCT_SUPPLIER_DIRECTOR])){
                $error_list[$sku] = '存在待审核的记录';
                continue;
            }

            // 修改了采购链接或供应商时验证供应商ID是否一致
            if(false and (!isset($is_force_submit) or $is_force_submit != 1)
                and ($new_product_link[$sku] != $product_info['product_cn_link']
                    or $product_info['supplier_code'] != $new_supplier_code[$sku])){// 屏蔽供应商ID不一致
                $productId = $this->aliproductapi->parseProductIdByLink($new_product_link[$sku]);
                if($productId['code'] === false){
                    $error_list[$sku] = $productId['errorMsg'];
                    $is_supplier_abnormal = true;
                    continue;
                }
                $supplierInfo = $this->aliproductapi->getSupplierByProductId($productId['data']);
                if($supplierInfo['code'] === false){
                    $error_list[$sku] = $supplierInfo['errorMsg'];
                    $is_supplier_abnormal = true;
                    continue;
                }else{
                    $loginId = isset($supplierInfo['data']['loginId'])?$supplierInfo['data']['loginId']:'';
                    $supplierName = isset($supplierInfo['data']['supplierName'])?$supplierInfo['data']['supplierName']:'';
                    if(trim($loginId) != trim($supplier_info['shop_id'])){
                        $error_list[$sku] = '供应商店铺ID不一致';
                        $is_supplier_abnormal = true;
                        continue;
                    }
                    if(trim($supplierName) != trim($supplier_info['supplier_name'])){
                        $error_list[$sku] = '供应商名称与店铺名称不一致';
                        $is_supplier_abnormal = true;
                        continue;
                    }
                }
            }
        }

        // 获取SKU 数据修改审核流程

        if(!empty($error_list)){

            $error_list_keys = array_keys($error_list);
            foreach($sku_product as $new_sku_product_key=>$new_sku_product){

                if(in_array($new_sku_product,$error_list_keys)){

                    unset($sku_product[$new_sku_product_key]);
                }
            }
        }
        if(!empty($sku_product)) {
            $sku_audit_proccess = $this->product_model->getAuditProccess();
            $proccess_orders = json_decode(PRODUCT_ALL_CONTENT_PROCCESS, True);
            foreach ($sku_product as $sku) {
                $all_audit_proccess = [];
                $audit_flag = false;
                $product_info = $this->product_model->get_product_info($sku);

                // 如果业务方修改了未税单价，英文标识 productprice
                if ($product_info['purchase_price'] != $new_supplier_price[$sku]) {

                    // 计算修改比例
                    $price_ratio = ($new_supplier_price[$sku] - $product_info['purchase_price']) / $product_info['purchase_price'];

                    $price_ratio = round(($price_ratio * 100), 3);
                    $sku_audit_proccess_now = isset($sku_audit_proccess['productprice']) ? $sku_audit_proccess['productprice'] : NULL;
                    $audit_proccess = $this->getSkuProccess($price_ratio, $sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['productprice'] = $audit_proccess;
                    }
                }

                // 修改供应商

                if ($product_info['supplier_code'] != $new_supplier_code[$sku]) {
                    $sku_audit_proccess_now = isset($sku_audit_proccess['suppliername']) ? $sku_audit_proccess['suppliername'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['suppliername'] = $audit_proccess;
                    }
                }

                // 是否定制修改

                if ($product_info['is_customized'] != $is_customized[$sku]) {

                    $sku_audit_proccess_now = isset($sku_audit_proccess['is_customized']) ? $sku_audit_proccess['is_customized'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['is_customized'] = $audit_proccess;
                    }
                }

                // 交期

                if ($product_info['devliy'] != $devliy[$sku]) {

                    $sku_audit_proccess_now = isset($sku_audit_proccess['devliy']) ? $sku_audit_proccess['devliy'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['devliy'] = $audit_proccess;
                    }

                }

                // 是否代采修改
                if ($product_info['is_purchasing'] != $is_purchasing[$sku]) {
                    $sku_audit_proccess_now = isset($sku_audit_proccess['substitute']) ? $sku_audit_proccess['substitute'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['substitute'] = $audit_proccess;
                    }

                }
                // 起订量修改
                if ($product_info['starting_qty'] != $new_starting_qty[$sku]) {
                    $sku_audit_proccess_now = isset($sku_audit_proccess['minimum']) ? $sku_audit_proccess['minimum'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['minimum'] = $audit_proccess;
                    }
                }

                // 超长交期 long_devliy
                if ($product_info['long_delivery'] != $long_devliy[$sku]) {

                    $sku_audit_proccess_now = isset($sku_audit_proccess['long_devliy']) ? $sku_audit_proccess['long_devliy'] : NULL;

                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['long_delivery'] = $audit_proccess;
                    }
                }

                // 修改开票点
                if ($product_info['ticketed_point'] != $new_ticketed_point[$sku]) {
                    $sku_audit_proccess_now = isset($sku_audit_proccess['unitprice']) ? $sku_audit_proccess['unitprice'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['unitprice'] = $audit_proccess;
                    }
                }

                // 修改票面
                if ($product_info['coupon_rate'] != $new_product_coupon_rate[$sku]) {
                    $sku_audit_proccess_now = isset($sku_audit_proccess['par']) ? $sku_audit_proccess['par'] : NULL;

                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['par'] = $audit_proccess;
                    }
                }

                // 修改货源状态
                if ($product_info['supply_status'] != $supply_status[$sku]) {

                    $sku_audit_proccess_now = isset($sku_audit_proccess['supply_status']) ? $sku_audit_proccess['supply_status'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['supply_status'] = $audit_proccess;
                    }
                }
                // 修改1688 对应关系
                if ($product_info['ali_ratio_own'] != $new_ali_ratio_own[$sku] || $product_info['ali_ratio_out'] != $new_ali_ratio_out[$sku]) {
                    $sku_audit_proccess_now = isset($sku_audit_proccess['corresponding']) ? $sku_audit_proccess['corresponding'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_audit_proccess_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['corresponding'] = $audit_proccess;
                    }
                }

                // 是否包邮审核
                if ($product_info['is_shipping'] != $is_shipping[$sku]) {
                    $sku_is_shipping_now = isset($sku_audit_proccess['is_shipping']) ? $sku_audit_proccess['is_shipping'] : NULL;
                    $audit_proccess = $this->getSkuOther($sku_is_shipping_now);
                    if (!empty($audit_proccess)) {
                        $all_audit_proccess['is_shipping'] = $audit_proccess;
                    }
                }
                // 判断是否拿样变更 $product_info['is_sample']!=$is_sample[$sku]
                if (($product_info['supplier_code'] != $new_supplier_code[$sku] && $is_sample[$sku] == 1)
                    || ($product_info['is_sample'] == 0 && $is_sample[$sku] == 1)) {

                    $all_audit_proccess_key = array_column($proccess_orders, NULL, "nameflag");
                    $all_audit_proccess['quality'] = ["quality"];
                }
                $sku_now_proccess = [];
                $sku_audit_role = NULL;
                if (!empty($all_audit_proccess)) {

                    foreach ($all_audit_proccess as $all_audit_proccess_key => $all_audit_proccess_value) {

                        foreach ($all_audit_proccess_value as $all_key => $all_value) {

                            if (!in_array($all_value, $sku_now_proccess)) {
                                $sku_now_proccess[] = $all_value;
                            }
                        }
                    }
                    $sku_audit_role = $this->sku_audit_proccess($sku_now_proccess, $proccess_orders);
                }


                if ($product_info['product_cn_link'] != $new_product_link[$sku]) {
                    $this->set_invalid($sku);
                }

                if ($import == True) {
                    $new_ticketed_point[$sku] = str_replace("%", "", $new_ticketed_point[$sku]);
                }


                if (NULL == $sku_audit_role) {

                    $audit_status = PRODUCT_UPDATE_LIST_AUDIT_PASS;
                } else {
                    $headProcess_flag = $sku_audit_role;
                    $headProcess = array_shift($headProcess_flag);
                    $audit_status = $headProcess['audit_flag'];
                }

                $params = [
                    'sku' => $sku,
                    'product_name' => $product_info['product_name'],
                    'product_line_name' => $this->product_line->get_product_line_name($product_info['product_line_id']),
                    'old_supplier_price' => $product_info['purchase_price'],
                    'new_supplier_price' => $new_supplier_price[$sku],
                    'old_supplier_code' => $product_info['supplier_code'],
                    'new_supplier_code' => $new_supplier_code[$sku],
                    'old_supplier_name' => $product_info['supplier_name'],
                    'new_supplier_name' => $new_supplier_name[$sku],
                    'old_ticketed_point' => $product_info['ticketed_point'],
                    'new_ticketed_point' => $new_ticketed_point[$sku],
                    'old_product_link' => $product_info['product_cn_link'],
                    'new_product_link' => $new_product_link[$sku],
                    'old_starting_qty' => $product_info['starting_qty'],
                    'old_starting_qty_unit' => $product_info['starting_qty_unit'],
                    'new_starting_qty' => intval($new_starting_qty[$sku]),
                    'new_starting_qty_unit' => $new_starting_qty_unit[$sku],
                    'old_ali_ratio_own' => $product_info['ali_ratio_own'],
                    'old_ali_ratio_out' => $product_info['ali_ratio_out'],
                    'new_ali_ratio_own' => intval($new_ali_ratio_own[$sku]),
                    'new_ali_ratio_out' => intval($new_ali_ratio_out[$sku]),
                    'create_user_id' => $usernameid,
                    'create_user_name' => $username,
                    'audit_status' => !empty($audit_status) ? $audit_status : PRODUCT_UPDATE_LIST_AUDIT_PASS,
                    'create_remark' => $create_remark[$sku],
                    'is_sample' => $is_sample[$sku],
                    'audit_level' => json_encode($sku_audit_role),
                    'old_coupon_rate' => $product_info['coupon_rate'],
                    'new_coupon_rate' => $new_product_coupon_rate[$sku],
                    'old_is_purchasing' => $product_info['is_purchasing'],
                    'new_is_purchasing' => $is_purchasing[$sku],
                    'reason' => is_array($reason[$sku]) ? implode(",", $reason[$sku]) : $reason[$sku],
                    'all_sku_proccess' => json_encode($sku_now_proccess),
                    'new_inside_number' => $inside_number[$sku],
                    'old_inside_number' => $product_info['inside_number'],
                    'new_box_size' => $box_size[$sku],
                    'old_box_size' => $product_info['box_size'],
                    'outer_box_volume' => $outer_box_volume[$sku],
                    'product_volume' => $product_volume[$sku],
                    'new_is_customized' => $is_customized[$sku],
                    'old_is_customized' => $product_info['is_customized'],
                    'new_devliy' => $devliy[$sku],
                    'old_devliy' => $product_info['devliy'],
                    'new_long_delivery' => $long_devliy[$sku],
                    'old_long_delivery' => $product_info['long_delivery'],
                    'is_new_shipping' => $is_shipping[$sku],
                    'is_old_shipping' => $product_info['is_shipping'],
                    'old_supply_status' => $product_info['supply_status'],
                    'new_supply_status' => $supply_status[$sku]
                ];
                $audit_level = '';

                if ($product_info['maintain_ticketed_point'] == 0) {
                    $params['old_ticketed_point'] = NULL;
                }

                if ($new_ticketed_point[$sku] !== 0) {
                    $params['maintain_ticketed_point'] = 1;
                } else {
                    $params['maintain_ticketed_point'] = 0;
                }
                $audit_arr = [
                    'audit_status' => $audit_status,
                ];

                if ($params['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS) {

                    $params['audit_time'] = date('Y-m-d H:i:s');
                }
                if ($product_info['product_cn_link'] == $new_product_link[$sku]
                    && bccomp($product_info['ticketed_point'], $new_ticketed_point[$sku], 3) == 0
                    && bccomp($product_info['purchase_price'], $new_supplier_price[$sku], 3) == 0
                    && $product_info['supplier_code'] == $new_supplier_code[$sku]
                ) {
                    $params['push_erp_status'] = 3;
                    $params['push_old_purchase_status'] = 3;
                }

                // 涨价时，相关图片必填，至少上传一张图片，最多允许上传5张图片--多个图片链接之间以逗号隔开 ---- start ----
                $total_pics = 0;
                $related_pictures = '';
                if (isset($data_arr['related_pictures'][$sku]) && !empty($data_arr['related_pictures'][$sku])) {

                    // 获取传过来的图片数量
                    $related_pictures = $data_arr['related_pictures'][$sku];
                    $related_pictures_arr = explode(',', $related_pictures);
                    $total_pics = count($related_pictures_arr);
                }

                $params['related_pictures'] = '';
                $params['related_thumbnails'] = '';
                // 涨价时，相关图片必填，至少上传一张图片，最多允许上传5张图片--多个图片链接之间以逗号隔开 ----- end -----

                if ($audit_status == PRODUCT_UPDATE_LIST_AUDIT_PASS and $product_info['product_cn_link'] != $new_product_link[$sku]) {
                    $this->ali_product_model->remove_relate_ali_sku(null, $sku);// 链接变更后，是否关联1688自动变成否，系统自动取消关联1688
                    $this->update_one($sku, array('product_cn_link' => $new_product_link[$sku]));// 不需审核的直接更新采购链接
                }

                if (empty($product_log_info) or $product_log_info['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS or $product_log_info['audit_status'] == PRODUCT_UPDATE_LIST_REJECT) {
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $result = $this->product_update_log->save_product_log_info($params);
                    if ($new_ticketed_point[$sku] === '' || $new_ticketed_point[$sku] == '') {
                        $audit_arr['maintain_ticketed_point'] = 1;
                    } else {
                        $audit_arr['maintain_ticketed_point'] = 0;
                    }

                    if ($product_info['supply_status'] == $supply_status[$sku]) {
                        $this->update_one($sku, $audit_arr);
                    } else {
                        $this->update_one($sku, $audit_arr);
                    }
                    $this->purchase_suggest_model->change_purchase_ticketed_type($sku, $audit_arr['maintain_ticketed_point']);
                } else {
                    $result = $this->product_update_log->update_product_log_one($sku, $params);
                }
                if ($result) {
                    if ($params['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS) {// 更新最新起订量，单位对应关系
                        $this->change_min_order_qty($sku, $new_starting_qty[$sku], $new_starting_qty_unit[$sku], $product_info['starting_qty'], $product_info['starting_qty_unit']);
                        $updateData = ['ali_ratio_own' => intval($new_ali_ratio_own[$sku]), 'ali_ratio_out' => intval($new_ali_ratio_out[$sku])];
                        if ($product_info['product_cn_link'] != $new_product_link[$sku]) {

                            $updateData['product_cn_link'] = $new_product_link[$sku];
                        }
                        $this->update_one($sku, $updateData);
                    }
                    $data_sku[] = $sku;
                } else {
                    $error_list[] = $sku . "保存失败";
                }
                if ($audit_status == PRODUCT_UPDATE_LIST_AUDIT_PASS) {
                    $params['create_time'] = date('Y-m-d H:i:s');
                    $logs_message = $this->get_last_logs_id($sku);
                    // 没有配置审核角色
                    if (!empty($logs_message)) {
                        if ((empty($sku_audit_role)) || ($logs_message['new_supplier_price'] == $new_supplier_price[$sku] || $logs_message['new_ticketed_point'] == $new_ticketed_point[$sku])) {
                            $res = $product_mod_audit_other->product_audit($logs_message['id'], 1, '自动审核通过', TRUE);
                        }
                    }
                }
                $this->set_sku_status($sku, $audit_status);
                $this->ali_product_model->verify_supplier_equal($sku);// 刷新供应商是否一致
            }
        }


        $msg = '';
        if(!empty($data_sku)){
            $msg .= implode('-', $data_sku).'保存成功--';
        }
        if(!empty($data_error)){
            $msg .= implode('-', $data_error).'保存失败';
        }

        return [

            "success_total" => isset($data_sku)?count($data_sku):0,
            'error_total' => isset($data_error)?count($data_error)+count($error_list):0,
            "success_sku" => isset($data_sku)?$data_sku:'',
            "error_sku" =>isset($error_list)?$error_list:''
        ];
    }


    public function get_product_import_data($id){

        $productDatas = $this->purchase_db->from("product_import_data")->where("id",$id)
            ->get()->row_array();
        return $productDatas;
    }

    public function update_product_data($id,$logs){

        $this->purchase_db->where('id',$id)->update("product_import_data",$logs);

    }

    public function get_supplier_avg_day($supplier_code){

        $new_supplierAvg = $this->purchase_db->from("supplier_avg_day")->where("supplier_code",$supplier_code)
            //->where("statis_month",$nowMonth)
            ->order_by("statis_month DESC")
            ->get()->result_array();
        if(!empty($new_supplierAvg)){

            return $new_supplierAvg[0];
        }
        return [];
    }


    /**
     * 统一 更新SKU货源状态入口（便于做业务拓展）
     * @author Jolon
     * @param string $sku SKU
     * @param string $new_supply_status 新货源状态
     * @param string $old_supply_status 原货源状态
     * @return bool
     */
    public function update_product_supply_status($sku,$new_supply_status,$old_supply_status = null){

        $this->purchase_db->where('sku',$sku)->update($this->table_name,['supply_status' => $new_supply_status]);

        // 添加日志
        $this->add_product_supply_status_change_log($sku,$new_supply_status,'变更SKU货源状态',$old_supply_status);

        return true;
    }

    /**
     * 统一 更新SKU货源状态入口（便于做业务拓展）
     * @author Jolon
     * @param string $sku SKU
     * @param string $new_supply_status 新货源状态
     * @param string $old_supply_status 原货源状态
     * @param string $content           操作类型备注（记录是哪里修改的）
     * @return bool
     */
    public function add_product_supply_status_change_log($sku,$new_supply_status,$content,$old_supply_status = null){
        $add_status_change = [
            'sku'               => $sku,
            'old_supply_status' => intval($old_supply_status),
            'new_supply_status' => $new_supply_status,
            'content'           => $content ? $content : uri_string(),
            'operate_time'      => date('y-m-d H:i:s')
        ];

        $this->purchase_db->insert('operator_supply_status_log',$add_status_change);
        return true;
    }


}