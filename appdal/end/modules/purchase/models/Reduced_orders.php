<?php
/**
 * Created by PhpStorm.
 * sku 降本新版方法封装 获取采购单信息
 * User: luxu
 * Date: 2019/09/25
 */
class Reduced_orders extends Purchase_model
{

    protected  $_purchase_table = 'purchase_order'; // 采购单表
    protected  $_purchase_orders_items_table = 'purchase_order_items'; // 采购单明细表
    /**
     * 获取SKU 降本采购单信息
     * @param: $sku     string   商品SKU
     *         $begin_start  array  时间段
     **/
    public function get_reduced_purchase_orders( $sku, $begin_start,$change_price,$product_base_price )
    {
        if( empty($sku) || empty($begin_start) )
        {
            return NULL;
        }
        $result = [];
        foreach( $begin_start as $key=>$begin)
        {
            foreach($begin as $k=>$v) {
                $query = $this->purchase_db->from($this->_purchase_table . " AS orders")->join($this->_purchase_orders_items_table . " AS items", 'orders.purchase_number=items.purchase_number');
                $query->where("sku", $sku)->where_in('orders.purchase_order_status', [7, 8, 9, 10, 11]);
                $query->where('(items.product_base_price<="' . $change_price . '" OR items.product_base_price="' . $product_base_price . '")');
                $res = $query->where("audit_time>=", $v['start'])->where("audit_time<=", $v['end'])->get()->result_array();
                if( !empty($res) )
                {
                    foreach( $res as $re_key=>$re_value)
                    {
                        $result[$k][] = $re_value;
                    }
                }

            }
        }
        return $result;
    }
    /**
       获取降本配置文件新
     **/
    public function is_reduced_config()
    {
        $result = $this->purchase_db->from("sku_reduced_config")->where("create_time<=",date("Y-m-d H:i:s",time()))->get()->row_array();
        if( empty($result) )
        {
            return array(

                'domestic_days' => 40,
                'fba_days' => 40,
                'overseas_days' => 60
            );
        }
        return $result;
    }

    /**
     * 获取采购数量
     * @param  $purchase_number    string   采购单号
     *         $change_price       array    金额变化
     * @return array
     **/

    public function get_reduced_purchase_number($price,$ticketed_point,$sku,$start_time,$end_time,$purchase_number = NULL)
    {
        $result = $this->get_confirm_amount_one($sku,$start_time,NULL,NULL,$end_time," SUM(ppoi.confirm_amount) as confirm_amount,ppo.purchase_number ",$purchase_number);
        return $result['confirm_amount'];
    }

    /**
     * 获取PO采购信息
     * @param  $purchase_order    string   PO 单号
     **/
    public function get_purchase_number_confirm_amount($purchase_order,$demand_number,$sku)
    {
        $this->db->select(' SUM(ppoi.confirm_amount) AS confirm_amount');
        $this->db->from('purchase_order_items as ppoi');
        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->db->join('purchase_suggest_map AS map','map.purchase_number=ppoi.purchase_number','left');
        $this->db->where('map.demand_number',$demand_number);
        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
        $this->db->where("ppo.purchase_number",$purchase_order);
        $this->db->where("ppoi.sku",$sku);
        $results = $this->db->order_by('ppo.audit_time asc')->limit(1)->get()->row_array();
        return $results['confirm_amount'];
    }

    /**
     * 价格改变后数据是否有变化
    @param:  $SKU     string
     *         $start_time   string  价格变化时间
     *         $change_price  string  价格变化后时间
     *         $product_base_price   string   票点计算后价格
     **/
    public function get_confirm_amount_one($sku, $start_time, $change_price=NULL, $product_base_price=NULL, $end_time,$select ="ppoi.id", $purchase_number=NULL

    ){
        if( empty($sku) || empty($start_time) ){
            return [];
        }

        $this->db->select($select);
        $this->db->from('purchase_order_items as ppoi');
        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->db->where('ppoi.sku', $sku);

        $this->db->where('ppo.audit_time >=', $start_time);
        $this->db->where("ppo.audit_time<=", $end_time);

        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
        if( NULL != $change_price && NULL !=$product_base_price) {
            $this->db->where('(ppoi.product_base_price="' . $change_price . '" OR ppoi.product_base_price="' . $product_base_price . '")');
        }
        if( NULL != $purchase_number )
        {
            $this->db->where("ppo.purchase_number",$purchase_number);
        }
        $results = $this->db->order_by('ppo.audit_time asc')->limit(1)->get()->row_array();
        return $results;
        // return 1000;
    }

    /**
     *判断SKU 价格是否有下单记录
     *@param  SKU 价格数据
     **/
    private function get_purchase_order_history($purchase_price,$sku,$audit_time)
    {
        $orders_flag = True;
        $this->load->model('Purchase/Reduced_model','reduced');
        // 价格审核30天
        $prev_change_price = $this->reduced->get_change_price_six_time($audit_time,30,'prev');
        $now_prev_price_query = $this->purchase_db->from("product_update_log")->where("audit_time<",$audit_time)->where("audit_time>",$prev_change_price)->where("audit_time<",$audit_time)->where("new_supplier_price!=old_supplier_price")->where("new_supplier_price!=",$purchase_price)->where("audit_status",3)->where("sku",$sku);
        $prev_price = $now_prev_price_query->select("new_supplier_price,new_ticketed_point,audit_time,old_supplier_price")->order_by("audit_time DESC")->get()->row_array();
        if( !empty($prev_price) ) {
        // SKU 审核通过最新价格
        $change_price = $prev_price['new_supplier_price'];
        // SKU 审核通过计算单价
        $product_base_price = $prev_price['new_supplier_price'] * (1 + ($prev_price['new_ticketed_point'] / 100));
        $end_time = $this->reduced->get_change_price_six_time($prev_price['audit_time'], 30, 'next');
        $result = $this->get_confirm_amount_one($sku, $prev_price['audit_time'], $change_price, $product_base_price, $end_time);
            if (empty($result)) {
            $orders_flag = False;
        }
        }


        return array(
            'flag' =>$orders_flag,
            'prev_price' => $prev_price['new_supplier_price'],
            'old_prev_price' => $prev_price['old_supplier_price']
        );
    }

    /**
     * 判断PO是否叠加
     * @param    purchase_price     decimal    PO 单的价格
     *           optimization_price decimal    优化价格
     *           change_price       array      价格变化幅度
     * @return   int
     **/
    public function is_superposition($purchase_price,$optimization_price,$change_price,$purchase_number=NULL)
    {
        if( $purchase_price == $optimization_price || $purchase_price>$optimization_price )
        {
            return 3;
        }else{
            return 2;
        }
    }

    public function get_product_update_time($sku,$audit_time,$new_price)
    {
        $query = $this->purchase_db->from("product_update_log")->where("sku",$sku)->where("new_supplier_price",$new_price)->where("audit_time>",$audit_time)->where("old_supplier_price!=new_supplier_price");
        $result = $query->where("audit_status",3)->select("audit_time")->get()->row_array();
        return $result['audit_time'];
    }

    /**
     * 判断是否叠加
     * @param:  $purchase_price        string   当前PO价格
     *          $purchase_pirce    array    价格变化幅度
     *          $sku_price         decimal  优化价格
     **/
    public function old_is_superposition($purchase_price,$sku,$old_supplier_price,$audit_time )
    {
        $orders_flags =  $this->get_purchase_order_history($purchase_price,$sku,$audit_time);
        // 当前优化价格没有下单记录，并且当前PO 的价格小于优化价格。表示叠加无PO
        if( !$orders_flags['flag']  && $purchase_price<=$orders_flags['prev_price'] && $purchase_price < $old_supplier_price )
        {
            return 1;
        }
        // 当前有下单记录，并且当前PO 的价格小于优化价格。表示叠加
        if( $purchase_price < $old_supplier_price &&  $orders_flags['flag'] == True && $purchase_price < $old_supplier_price)
        {
            return 2;
        }

        // 如果当前PO的价格大于优化的价格，表示非叠加
        if( ( $purchase_price > $old_supplier_price ) || ($purchase_price>$orders_flags['prev_price']) || ($purchase_price == $orders_flags['prev_price']))
        {
            return 3;
        }
    }

    /**
     * 获取采购单和SKU 对应的备货信息
     * @param  $purchase_number      string     采购单号
     *         $sku                  string     商品SKU
     *@return  array
     **/
    public function get_purchase_demand_number( $purchase_number,$sku)
    {
        if( empty($purchase_number) || empty($sku) )
        {
            return NULL;
        }

        $result = $this->purchase_db->from("purchase_suggest_map")->where("purchase_number",$purchase_number)->where("map_status",1)->where("sku",$sku)->select("demand_number")->get()->row_array();
        return $result['demand_number'];
    }

    /**
       * 获取SKU 降本明细相关数据
     **/
    public function get_reduced_purchase_order_details( $sku,$start_time,$end_time,$order_price,$order_ticketed,$flag = false,$change_price=NULL)
    {
        if( empty($sku) ||  empty($start_time) || empty($end_time) )
        {
            return NULL;
        }

        $query = $this->purchase_db->from('purchase_order_items as ppoi')->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $query->join('purchase_suggest as suggest','suggest.demand_number=ppoi.demand_number  AND suggest.sku=ppoi.sku','LEFT');
        $query->where("ppoi.sku",$sku)->where_in('ppo.purchase_order_status', [7,8,9,10,11])->where("suggest.is_overseas_boutique",0);
        if( false == $flag) {
            $query->where("ppoi.product_base_price<=", $order_price);
        }

        if( NULL != $change_price)
        {
            $query->where("ppoi.product_base_price", $change_price)->where("ppo.audit_time>=",$start_time)->where("ppo.audit_time<=",$end_time);
        }else{
            $query->where("ppo.audit_time>=",$start_time)->where("ppo.audit_time<=",$end_time);
        }
        $query->select("ppo.purchase_type_id AS type_id,ppo.purchase_order_status AS product_status,ppoi.sku,ppoi.confirm_amount,ppo.purchase_type_id,ppoi.pur_ticketed_point,ppo.create_time,ppo.audit_time,ppoi.sku,ppoi.product_base_price,ppo.purchase_number,ppo.buyer_name AS purchase_person,ppo.supplier_name,ppo.supplier_code,ppoi.sku,ppoi.purchase_unit_price");
        $result = $query->get()->result_array();
        return $result;
    }

    /**
     * 获取SKU 降本明细相关叠加数据
     **/
    public function get_reduced_superposition( $sku,$start_time,$end_time,$order_price,$order_ticketed,$flag = false)
    {
        if( empty($sku) ||  empty($start_time) || empty($end_time) )
        {
            return NULL;
        }

        $query = $this->purchase_db->from('purchase_order_items as ppoi')
            ->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $query->join('purchase_suggest as suggest','suggest.demand_number=ppoi.demand_number AND suggest.sku=ppoi.sku','left');
        $query->where("ppoi.sku",$sku)->where('suggest.is_overseas_boutique',0)->where_in('ppo.purchase_order_status', [7,8,9,10,11])->where("ppo.audit_time>=",$start_time)->where("ppo.audit_time<=",$end_time);
        if( false == $flag) {
            $query->where("ppoi.product_base_price<", $order_price);
        }
        $query->select("ppo.purchase_type_id AS type_id,ppo.purchase_order_status AS product_status,ppoi.sku,ppoi.confirm_amount,ppo.purchase_type_id,ppoi.pur_ticketed_point,ppo.create_time,ppo.audit_time,ppoi.sku,ppoi.product_base_price,ppo.purchase_number,ppo.buyer_name AS purchase_person,ppo.supplier_name,ppo.supplier_code,ppoi.sku,ppoi.purchase_unit_price");
        $result = $query->get()->result_array();
        return $result;
    }


    public function super_replace_data($tables,$datas)
    {
        if( !empty($datas))
        {
            $flag = $this->purchase_db->from($tables)->where("sku", $datas['sku'])->where("is_superposition",1)->where("price_change_time",$datas['price_change_time'])->get()->row_array();
            if( !empty($flag) )
            {
                $this->purchase_db->where("id",$flag['id'])->where("sku", $datas['sku'])->where("is_superposition",1)->where("price_change_time",$datas['price_change_time'])->update($tables, $datas);
            }else{

                $this->purchase_db->insert($tables, $datas);
            }
        }
    }

    public function replace_data_data( $tables,$datas,$flag_data = NULL )
    {
        if( !empty($datas)){

            foreach($datas as $key=>$value)
            {
                if( $tables == "purchase_reduced_detail_other") {
                    if(!empty($value['purchase_number']) ) {
                        $flag = $this->purchase_db->from($tables)->where("app_id",$value['app_id'])->where("sku", $value['sku'])->where("purchase_number", $value['purchase_number'])->get()->row_array();
                        if ($flag) {
                            $this->purchase_db->where("id",$flag['id'])->where("app_id",$value['app_id'])->where("sku", $value['sku'])->where("purchase_number", $value['purchase_number'])->update($tables, $value);
                        } else {
                            $this->purchase_db->insert($tables, $value);
                        }
                    }
                }
                if( $tables == "sku_reduced_edition_new_other") {

                    $flag = $this->purchase_db->from($tables)->where("sku", $value['sku'])->where("app_id",$value['app_id'])->get()->row_array();
                    if ($flag) {
                        $this->purchase_db->where("id",$flag['id'])->where("sku", $value['sku'])->where("app_id",$value['app_id'])->update($tables, $value);
                    } else {
                        $this->purchase_db->insert($tables, $value);
                    }
                }
            }
        }
    }

    public function replace_data( $tables,$datas,$flag_data = NULL )
    {
       if( !empty($datas)) {
           foreach($datas as $key=>$value)
           {
               if( $tables == "purchase_reduced_detail_other") {
                   if(!empty($value['purchase_number']) ) {
                       $flag = $this->purchase_db->from($tables)->where("app_id",$value['app_id'])->where("sku", $value['sku'])->where("purchase_number", $value['purchase_number'])->get()->row_array();
                       if ($flag) {

                           $this->purchase_db->where("app_id",$value['app_id'])->where("sku", $value['sku'])->where("purchase_number", $value['purchase_number'])->update($tables, $value);
                       } else {

                           $this->purchase_db->insert($tables, $value);
                       }
                   }
               }
               if( $tables == "sku_reduced_edition_new_other") {

                   $flag = $this->purchase_db->from($tables)->where("sku", $value['sku'])->where("app_id",$value['app_id'])->get()->row_array();
                   if ($flag) {
                       $this->purchase_db->where("id",$flag['id'])->where("sku", $value['sku'])->where("app_id",$value['app_id'])->update($tables, $value);
                   } else {
                       $this->purchase_db->insert($tables, $value);
                   }


               }
           }
       }
    }

    public function get_purchase_demand( $demand_number)
    {

       return $this->purchase_db->from("purchase_suggest")->where("demand_number",$demand_number)->where("suggest_status",2)->select("id")->get()->row_array();
    }
    /**
     * 是否有效
     * @param   $purchase_order_audit_time     string   当前PO 下单时间
     *          $config_time                   string   配置时间
     *          $purchase_type_id              int      当前PO类型
     * @return  Bool   如果是 返回True,不是返回False;
     **/
    public function is_reduced_orders_effective( $purchase_order_audit_time,$purchase_type_id,$config_time,$purchase_status,$purchase_number,$sku )
    {
        $end_time_days = NULL;
        if( $purchase_type_id == PURCHASE_TYPE_INLAND || $purchase_type_id == PURCHASE_TYPE_PFB) {

            $end_time_days = $config_time['domestic_days'];
        }

        if( in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {

            $end_time_days = $config_time['overseas_days'];
        }

        if( $purchase_type_id == PURCHASE_TYPE_FBA) {

            $end_time_days = $config_time['fba_days'];
        }
        $this->load->model('purchase/Reduced_model','reduced');
        $end_days = $this->reduced->get_change_price_six_time($purchase_order_audit_time,$end_time_days,'next');
        $result = $this->purchase_db->from("warehouse_results")->where("purchase_number",$purchase_number)->where("sku",$sku)->where("instock_date<=",$end_days)->select("id")->get()->row_array();

        if( in_array($purchase_status,[9,11,14]) && !empty($result))
        {
            return True;
        }
        return False;

        // 和产品确认
//        if( date("Y-m-d H:i:s") < $end_days && in_array($purchase_status,[7,8,9,10,11]) ) {
//            $suggest_status = $this->get_purchase_demand($demand_number);
//            if( !empty($suggest_status)) {
//                return True;
//            }
//        }
//        return False;
    }

    /**
     * 获取结束统计时间,（有效时间也通用）
     * @param   $purchase_order_audit_time     string   当前PO 下单时间
     *          $config_time                   string   配置时间
     *          $purchase_type_id              int      当前PO类型
     * @return  string
     **/
    public function get_effected_days( $purchase_type_id,$config_time,$purchase_order_audit_time)
    {
        $end_time_days = NULL;
        if( $purchase_type_id == 1 or $purchase_type_id == 4) {

            $end_time_days = $config_time['domestic_days'];
        }

        if( $purchase_type_id == 2) {

            $end_time_days = $config_time['overseas_days'];
        }

        if( $purchase_type_id == 3) {

            $end_time_days = $config_time['fba_days'];
        }
        $end_days = $this->reduced->get_change_price_six_time($purchase_order_audit_time,$end_time_days,'next');
        return $end_days;
    }

    /**
     * 是否结束统计
     * @param   $purchase_order_audit_time     string   当前PO 下单时间
     *          $config_time                   string   配置时间
     *          $purchase_type_id              int      当前PO类型
     * @return  Bool   如果是 返回True,不是返回False;
     **/
    public function is_reduced_orders_end( $purchase_number)
    {
       $result = $this->purchase_db->from("purchase_order")->where("purchase_number",$purchase_number)->where_in("purchase_order_status",[9,11,14])->get()->row_array();
       if( !empty($result))
       {
           return True;
       }
       return False;
    }


    /**
     * 是否结束统计
     * @author yefanli  2020-07-10
     * @param   $purchase_number    string
     * @return  bool
     */
    public function is_reduced_orders_end_count( $purchase_number)
    {
        $result = $this->purchase_db->from("purchase_order")->where("purchase_number",$purchase_number)->where_in("purchase_order_status",[9,11,14])->get()->row_array();
        if($result > 0)return True;
        return False;
    }

    /**
     * 获取采购单信息
     * @param $purchase_number    string   采购单号
     * @return   array
     **/
    public function get_purchase_message($purchase_number,$result_string = "ppo.purchase_order_status,ppo.purchase_number")
    {
        return $this->purchase_db->from("purchase_order AS ppo")->where_in("purchase_number",$purchase_number)->select($result_string)->get()->result_array();
    }

    public function get_purchase_detail( $app_id )
    {
        $query = $this->purchase_db->from("purchase_reduced_detail_other AS detail");
        $query->join("purchase_order AS orders","detail.purchase_number=orders.purchase_number")->where("detail.app_id",$app_id)->WHERE_IN("orders.purchase_order_status",[9,11,14]);
        $result = $query->get()->result_array();
        $total = $this->purchase_db->from("purchase_reduced_detail_other AS detail")->where("detail.app_id",$app_id)->count_all_results();
        if( count($result) == $total)
        {
            return True;
        }
        return False;


    }


}