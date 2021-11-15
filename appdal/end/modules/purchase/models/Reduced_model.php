<?php
/**
 * Created by PhpStorm.
 * sku 降本新版方法封装
 * User: luxu
 * Date: 2019/09/25
 */
class Reduced_model extends Purchase_model
{

    protected   $_purchase_order_data = 'purchase_order'; // 采购单表

    protected   $_product_data        = 'product'; // 商品表

    protected   $_product_update_log  = 'product_update_log'; // 商品修改日志表

    public function __construct()
    {
        parent::__construct();
        $this->load->model('product/Product_mod_audit_model','product_mod_audit_model');
    }

    public function get_change_price_six_time($change_price_time,$days= 180,$flag = 'prev')
    {
        if( $flag == 'prev') {
            return date('Y-m-d H:i:s', strtotime("$change_price_time -$days days"));
        }
        if( $flag == 'next'){

            return date('Y-m-d H:i:s', strtotime("$change_price_time +$days days"));
        }
    }

    public  function get_MonthNum( $date1, $date2, $tags='-' ){
        $date1 = explode($tags,$date1);
        $date2 = explode($tags,$date2);
        return abs($date1[0] - $date2[0]) * 12 + abs($date1[1] - $date2[1]);
    }

    function getNextMonthLastDay($date) {
        return date('Y-m-d 23:59:59', strtotime(date('Y-m-01', strtotime($date)) . ' +1 month -1 day'));
    }

    function getNextMonthFirstDay($date) {
        return date('Y-m-d 23:59:59', strtotime(date('Y-m-01', strtotime($date)) . ' +0 month'));
    }

    function get_stride_across( $diffNumber,$times)
    {
        if( $diffNumber >= 1)
        {
            $months[] = array(
                'first' => array(

                    'start' => $times['begin_time'],
                    'end'   => $this->reduced->getNextMonthLastDay($times['begin_time'])
                ),
                'two' => array(

                    'start' => $times['end_time'],
                    'end'   => $this->reduced->getNextMonthLastDay( $times['end_time'])
                ),
            );
        }else{

            // 没有夸月
            $months[] = array(
                'first' => array(
                    'start' => $times['begin_time'],
                    'end'   => $times['end_time']
                ),

            );
        }
        return $months;
    }

    /**
     * 获取SKU 价格变化前6个月最低价格
     * @param: $sku                  string   商品SKU
     *         $change_price_time    string   价格变化时间
     * @return  float
     **/
    public function get_six_minimum_price( $sku,$change_price_time )
    {

        if( empty($sku) || empty($change_price_time) )
        {
            return NULL;
        }
        $price_six_time = $this->get_change_price_six_time( $change_price_time,180,'prev');

        $sku_min_price = $this->purchase_db->from("product_update_log")->where("sku",$sku)->where("audit_time<",$change_price_time)->where("audit_time>=",$price_six_time)->where("audit_status",3)->select("MIN(new_supplier_price) AS price")->get()->row_array();
        return $sku_min_price['price'];
    }

    /**
     * 获取商品信息单价变化SKU ，并且审核通过
     * @param:  $sku        string         商品SKU
     *          $old_type   int            区分新老系统SKU  0表示新采购系统，1表示老采购系统。默认为新采购系统
     *          $start_time string         开始时间
     *          $end_time   string         结束时间
     *@return   array
     **/
    public function get_product_update_logs( $sku =  NULL,$old_type = 0,$start_time = NULL, $end_time = NULL,$flag = 'total',$field = '*' )
    {
        $this->purchase_db->reset_query();
        $query = $this->purchase_db->from("product_update_log");
        $query->where("old_supplier_price!=new_supplier_price")->where("audit_status=3");
        if( NULL != $sku )
        {
            $query->where("sku",$sku);
        }

        if( NULL != $start_time )
        {
            $query->where("audit_time>=",$start_time)->where("audit_time<=",$end_time);
        }

        if( 'total' == $flag ) {
            return $query->group_by("sku")->count_all_results();
        }
        if( 'select' == $flag )
        {
              return $query->select($field)->order_by("audit_time ASC ")->get()->result_array();

        }
    }

    /**
     * 合同单创建时间
     * @param  $sku  string 商品SKU 信息
     *         $purchase_number  string  采购单号
     * @return  string  合同创建时间
     **/
    public function get_computing( $sku,$purchase_number )
    {
        $result = $this->purchase_db->from("purchase_compact_items")->where("sku",$sku)->where("purchase_number",$purchase_number)->where("bind",1)->select("create_time")->order_by("create_time ASC")->get()->row_array();
        if( !empty($result) )
        {
            return $result['create_time'];
        }
        return NULL;
    }

    /**
     * 获取SKU 降本优化人
     * @param  $change_price    array   商品价格变化
     *         $sku             string  商品SKU
     **/
    public function get_product_sku_optimizing_user($username)
    {

            $create_user_name = $create_user_number = $create_user = NULL;
            if( is_string($username)) {
                if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($username), $arr)) {
                    $create_user_name = $arr[0];
                    $create_user_number = str_replace($arr[0], '', $username);

                }
                return array(
                    'name' => $create_user_name,
                    'number' => $create_user_number
                );
            }
        

    }

    /**
     * SKU 价格变化
     * @param  $sku          string    商品SKU
     *         $computing    string    审核时间
     **/
    public function get_product_sku_price_change( $sku, $computing,$new_supplier_price,$end_price_change_time )
    {
        // $end_price_change_time = $this->get_change_price_six_time( $computing,30,'next');
        $product_price_change = $this->get_product_update_logs( $sku,0,$computing,$end_price_change_time,'select','old_supplier_price,new_supplier_price,audit_time,new_ticketed_point,sku,create_user_name');
        /**
          * 如果为空表示价格没有变化过
         **/
        $prices_new = array_column( $product_price_change,"new_supplier_price");
        $prices_old = array_column( $product_price_change,"old_supplier_price");
        $prices = array_merge( $prices_old,$prices_new);
        $price_changes = array_values(array_unique( $prices ));

        //$price_changes = array_reverse($prices);// 调试代码，待删
        // 如果价格为一个元素说明价格没有变化过
        if( count($price_changes) == 1 )
        {
            return array(

                "price_change_flag" => 1, //PRICE_CHANGE_NO
                "start_time"        => $computing,
                "end_time"          => $end_price_change_time,
                "change_data"       => $product_price_change
            );
        }else{

            // 如果价格元素不等于一个元素，说明价格有变化

            $flag = []; // 定义标识位，标识价格连续下降
            $rise_price = NULL; // 上涨的价格
            foreach( $product_price_change as $key=>$prices)
            {
                if ( $prices['old_supplier_price'] !=  $prices['new_supplier_price'] && $prices['new_supplier_price'] > $prices['old_supplier_price']) {
                    $flag[] = 4; // 上涨
                    if( NULL === $rise_price ) {
                        $rise_price = $key;
                    }
                }else if( $prices['old_supplier_price'] !=  $prices['new_supplier_price'] && $prices['new_supplier_price'] < $prices['old_supplier_price'] ) {

                    $flag[] = 5; // 下降
                }

            }
            $flag = array_unique( $flag );
            /**
               价格连续下降
             **/
            if( !empty($flag) && count($flag) ==1 && $flag[0] == 5 )
            {
                return array(

                    "price_change_flag" => 2, //PRICE_CHANGE_DECLINE
                    "start_time"        => $computing,
                    "end_time"          => $end_price_change_time,
                    "change_data"       => $product_price_change
                );
            }

            /**
              价格连上涨
             **/
            if( !empty($flag) && count($flag) ==1 && $flag[0] == 4 )
            {
                return array(

                    "price_change_flag" => 4, //PRICE_CHANGE_RISE
                    "start_time"        => $computing,
                    "end_time"          => $end_price_change_time,
                    "change_data"       => $product_price_change,
                    "gain"              => $rise_price
                );
            }
            /**
              * 几个价格有涨有降
             **/
            if( !empty($flag) && count($flag) >=2 )
            {
                $price_columns = array_column( $product_price_change,NULL,'new_supplier_price');
                $end_price_change_time = $this->get_change_price_six_time($product_price_change[$rise_price]['audit_time'],30,'next');
                return array(

                    "price_change_flag" => 3, //PRICE_CHANGE_GAIN
                    "start_time"        => $product_price_change[$rise_price]['audit_time'],
                    "end_time"          => $end_price_change_time,
                    "change_data"       => $product_price_change,
                    "gain"              => $rise_price
                );
            }
        }

    }

    /**
     * 获取降本商品SKU信息
     * @param:  $params     array   传入参数
     *          $offset     int     每页多少条数据
     *          $page       int     第几页
     *          $field      string  获取字段信息
     * @return  array
     **/
    public function get_product_list( $params,$page,$offset,$field='a.*,b.sku as product_sku,b.product_thumb_url' )
    {
        //$params,$limit,$offset,$field='*',$page=1,$export=false,$reduced= False
        return $this->product_mod_audit_model->get_product_list($params, $offset,$page,$field,1,false,true,"desc");

    }

    /**
      * 获取SKU 降本明细信息
     **/
    public function get_reduced_detail($clientData,$flag = 'list')
    {
        $query = $this->purchase_db->from("purchase_reduced_detail_other AS detail");
        // 传入优化人姓名
        if( isset($clientData['person_name']) && !empty($clientData['person_name']) )
        {
            $query->where(" detail.person_name",$clientData['person_name']);
        }

        // 传入供应商

        if( isset($clientData['supplier_code']) && !empty($clientData['supplier_code']))
        {
            $query->where("detail.supplier_code",$clientData['supplier_code']);
        }

        // 传入商品的SKU

        if( isset( $clientData['sku']) && !empty($clientData['sku']) )
        {
            $query->where("detail.sku",$clientData['sku']);
        }

        // 传入价格变化的开始时间

        if( isset($clientData['price_change_start_time']) && !empty($clientData['price_change_start_time']))
        {
            $query->where("detail.price_change_time>=",$clientData['price_change_start_time']);
        }

        // 价格变化的结束时间

        if( isset($clientData['price_change_end_time']) && !empty($clientData['price_change_end_time']))
        {
            $query->where("detail.price_change_end_time<=",$clientData['price_change_end_time']);
        }

        // 采购数量

        if( isset($clientData['purchase_num']) && !empty($clientData['purchase_num']) )
        {
            // 表示不等于0
            if( $clientData['purchase_num'] == 1 )
            {
                $query->where("detail.purchase_num>",0);
            }else{
                // 等于0
                $query->where("detail.purchase_number=",0);
            }
        }

        // 实际入库
        if( isset($clientData['warehouse_number']) && !empty($clientData['warehouse_number']) )
        {
            // 表示不等于0
            if( $clientData['warehouse_number'] == 1 )
            {
                $query->where("detail.warehouse_number>",0);
            }else{
                // 等于0
                $query->where("detail.warehouse_number=",0);
            }
        }

        // 入库开始时间

        if( isset($clientData['warehouse_start_time']) && !empty($clientData['warehouse_start_time']))
        {
            $query->where("detail.warehouse_time>=",$clientData['warehouse_start_time']);
        }
        // 入库结束时间
        if( isset($clientData['warehouse_end_time']) && !empty($clientData['warehouse_end_time']))
        {
            $query->where("detail.warehouse_time<=",$clientData['warehouse_start_time']);
        }
        // 首次计算开始时间

        if( isset($clientData['first_calculation_start_time']) && !empty($clientData['first_calculation_start_time']) )
        {
            $query->where("detail.first_calculation_time>=",$clientData['first_calculation_start_time']);
        }
        // 首次计算结束时间

        if( isset($clientData['first_calculation_end_time']) && !empty($clientData['first_calculation_end_time']) )
        {
            $query->where("detail.first_calculation_time<=",$clientData['first_calculation_end_time']);
        }

        if( 'list' == $flag )
        {
            $result = $query->limit(($clientData['page']-1)*$clientData['offsest'],$clientData['offsest'])->get()->result();

        }
    }

    /**
      * 匹配SKU 的新老价格
     **/
    public function get_product_price_change( $sku,$now_price,$audti_time,$app_id )
    {

        $query = $this->purchase_db->from("product_update_log")->where("new_supplier_price!=old_supplier_price")->where("sku",$sku)->where("new_supplier_price",$now_price)->where("audit_time<",$audti_time)->where("audit_status",3);
        $result = $query->select("old_supplier_price")->order_by("id DESC")->get()->row_array();
        return $result['old_supplier_price'];

    }

    /**
     * function:获取SKU 商品信息
     * @param: $sku   string    商品SKU
     * @return array   商品信息
     **/

    public function get_product_sku_message( $sku )
    {



    }
}
