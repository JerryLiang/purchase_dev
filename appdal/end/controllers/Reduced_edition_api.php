<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * SKU降本定时任务
 * User: Jaden
 * Date: 2019/01/17 
 */

class Reduced_edition_api extends MY_API_Controller{

    protected   $_limit = 3000; // 新版SKU 降本 每次计算数量

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase/Reduced_edition_model','reduced_edition');
        $this->load->model('purchase/Purchase_order_items_model','purchase_order_items_model');
        $this->load->model('purchase/Purchase_order_cancel_model','purchase_order_cancel_model');
        $this->load->model('product/Product_mod_audit_model','product_mod_audit_model');
        $this->load->model('purchase/Reduced_model','reduced');
        $this->load->model('purchase/Reduced_orders','reduced_orders');
    }
    /**
      * 获取每月开始时间和结束时间存入REDIS
     **/
    public function get_year_month( $now_year )
    {
        $now = date("Y",time());
        if( NULL == $now_year ) {
            $year_month = 12;
        }else{

            if( $now == $now_year) {
                $year_month = Ltrim(date("m"), 0);
            }else{
                $year_month = 12;
            }
        }
        $month = [];
        for( $i=1;$i<=$year_month;++$i)
        {
            if( $i<10)
            {
                $mon = '0'.$i;
            }else{
                $mon = $i;
            }

            if( NULL == $now_year) {
                $month[] = array(

                    'start' => date("Y-" . $mon . "-01", strtotime("-1 year")),
                    'end' => date("Y-m-d 23:59:59", strtotime(date("Y-" . $mon . "-01", strtotime("-1 year"))) + 60 * 60 * 24 * 30)
                );
            }else{

                if( $now == $now_year) {
                    $month[] = array(

                        'start' => date("Y-" . $mon . "-01"),
                        'end' => date("Y-m-d 23:59:59", strtotime(date("Y-" . $mon . "-01")) + 60 * 60 * 24 * 30)
                    );
                }else{
                    $diff_year = date("Y")-$now_year;
                    $month[] = array(

                        'start' => date("Y-" . $mon . "-01", strtotime("-{$diff_year} year")),
                        'end' => date("Y-m-d 23:59:59", strtotime(date("Y-" . $mon . "-01", strtotime("-{$diff_year} year"))) + 60 * 60 * 24 * 30)
                    );
                }
            }
        }
        $expiretime = 3600*24*7;
        $flag = $this->rediss->setData('YEAR_MONTH_NEW',json_encode($month),$expiretime);
    }






    /**
      * 获取 SKU 降本修改日期，根据日期跑数据
     **/
    public function reduced_audit_time()
    {
        ini_set('max_execution_time','18000');
        $now_year = isset($_GET['year'])?$_GET['year']:NULL;
        $months = $this->rediss->getData('YEAR_MONTH_NEW');
        if( empty($months) )
        {
            $this->get_year_month( $now_year );
            $months = $this->rediss->getData('YEAR_MONTH_NEW');
        }
        $months = json_decode( $months,True);

        $run_months = array_slice( $months,0,3);

        try {
            foreach ($run_months as $key=>$month) {
                $this->Reduced_edition_api(NULL, $month['start'], $month['end']);

                // 第一个日期运行完成就释放
                foreach( $months as $mon_key=>$mon_value)
                {
                    if( $mon_value['start'] == $month['start'] && $mon_value['end'] == $month['end'])
                    {
                        unset($months[$mon_key]);
                    }
                }

                echo "2019年".$month['start']." 月数据统计完成\r\n";
            }

            if( empty($months))
            {
                $this->rediss->deleteData('YEAR_MONTH_NEW');
            }else {
                $expiretime = 3600*24*7;
                $this->rediss->setData('YEAR_MONTH_NEW', json_encode($months),$expiretime);
            }
        }catch ( Exception $exp )
        {
            echo json_encode( $month)."数据统计错误";
        }
    }
    /**
     *获取采购入库信息
     * @param  $purchase_number    string    采购单号
     *         $sku                string    商品SKU
     *         $end_time_days      string    统计结束时间
     **/
    private function get_warehouse_data($purchase_number,$sku,$end_time_days=NULL)
    {
        $query = $this->purchase_db->from("warehouse_results_main")->where("purchase_number",$purchase_number)->where("sku",$sku);
        if( NULL != $end_time_days )
        {
            $query->where("instock_date>=",$end_time_days);
        }
        $result = $query->select(" instock_qty,instock_date")->get()->row_array();
        return $result;
    }

    public function Reduced_edition_data_other()
    {
        ini_set('max_execution_time','18000');
        $sql = " SELECT
	 count(distinct items.sku) as total
FROM
	pur_purchase_order_items AS items
LEFT JOIN pur_purchase_order AS orders ON items.purchase_number=orders.purchase_number
WHERE
	orders.purchase_order_status NOT IN (11, 9, 14) AND items.create_time>='2020-03-01'";

        $total = $this->db->query($sql)->row_array();
        $limit =1000;
        $page = ceil( $total['total']/$limit);
        for( $i=1; $i<=$page;++$i)
        {
            $sql = "SELECT
	items.sku
FROM
	pur_purchase_order_items AS items

LEFT JOIN pur_purchase_order AS orders ON items.purchase_number=orders.purchase_number

WHERE
	orders.purchase_order_status NOT IN (11, 9, 14) AND orders.create_time>='2020-03-01' GROUP BY items.sku LIMIT ".($i-1)*$limit.",".$limit;
            $result = $this->db->query($sql)->result_array();
            foreach( $result as $key=>$value)
            {
                $this->Reduced_edition_api_data($value['sku']);
            }
        }

    }

    public function Reduced_edition_data()
    {
        ini_set('max_execution_time','18000');
        $sql = " SELECT
                    count(*) AS total
                FROM
                    pur_product_update_log
                WHERE
                    sku IN (
                        SELECT
                            items.sku
                        FROM
                            pur_purchase_order_items AS items
                    LEFT JOIN pur_purchase_order AS orders ON orders.purchase_number=items.purchase_number WHERE orders.purchase_order_status IN (7,8,9,10,11)
                    )
                AND sku NOT IN (
                    SELECT
                        sku
                    FROM
                        pur_sku_reduced_edition_new
                )
                AND audit_status = 3
                AND new_supplier_price != old_supplier_price";
        $limit =1000;
        $total = $this->db->query($sql)->row_array();
        $page = ceil( $total['total']/$limit);
        for( $i=1; $i<=$page;++$i)
        {
            $sql = " SELECT
                *
            FROM
                pur_product_update_log
            WHERE
                sku IN (
                    SELECT
                        items.sku
                    FROM
                        pur_purchase_order_items AS items
                LEFT JOIN pur_purchase_order AS orders ON orders.purchase_number=items.purchase_number WHERE orders.purchase_order_status IN (7,8,9,10,11)
                )
            AND sku NOT IN (
                SELECT
                    sku
                FROM
                    pur_sku_reduced_edition_new
            )
            AND audit_status = 3
            AND new_supplier_price != old_supplier_price LIMIT ".($i-1)*$limit.",".$limit;

            $result = $this->db->query($sql)->result_array();
            foreach( $result as $key=>$value)
            {
                $this->Reduced_edition_api($value['sku']);
            }
        }

    }


    /**
     * 新版本的SKU降本计划任务(临时脚本)
     **/
    public function Reduced_edition_api_data( $skus_data = NULL,$start=NULL,$end=NULL)
    {
        try
        {
            //获取价格变化并且审核通过的SKU总数量
            if( NULL != $start ) {

                $reduced_skus_total = $this->reduced->get_product_update_logs(NULL, 0, $start, $end);
            }else{
                if( isset($_GET['sku']) && !empty($_GET['sku']) ) {
                    $reduced_skus_total = $this->reduced->get_product_update_logs($_GET['sku']);
                }else {
                    if(NULL != $skus_data){
                        $reduced_skus_total = $this->reduced->get_product_update_logs($skus_data);
                    }else {
                        $reduced_skus_total = $this->reduced->get_product_update_logs();
                    }
                }
            }
            // 总共分多少页
            $page = ceil( $reduced_skus_total/$this->_limit);
            if( $page >=1 )
            {
                $params = [
                    'contrast_price'=>1,
                    'audit_status'=>3
                ];

                if( isset($_GET['sku']) && !empty($_GET['sku']) )
                {
                    $params['sku'] = $_GET['sku'];
                }

                if( NULL != $start)
                {
                    $params['audit_time_start'] = $start;
                }

                if( NULL != $end)
                {
                    $params['audit_time_end'] = $end;
                }

                if( $skus_data != NULL)
                {
                    $params['sku'] = $skus_data;
                }
                // 获取配置信息
                $reduced_config = $this->reduced_orders->is_reduced_config();

                for( $i=1;$i<=$page;++$i )
                {
                    $offset = ($i - 1) * $this->_limit;
                    $orders_info = $this->reduced->get_product_list($params, $offset,$this->_limit);

                    if( isset($orders_info['data_list']) && !empty($orders_info['data_list']))
                    {
                        $detail_value = $insert_data =[];
                        foreach( $orders_info['data_list'] as $key=>$products) {
                            // SKU 审核通过最新价格
                            $change_price = $products['new_supplier_price'];
                            // SKU 审核通过计算单价
                            $product_base_price = $products['new_supplier_price']*(1+($products['new_ticketed_point']/100));
                            // 定义标识表示是否有下单记录,默认为有
                            $is_first_sku_order = True;
                            $products_sku_orders_first = $this->get_confirm_amount_one($products['sku'], $products['audit_time'],$change_price,$product_base_price,NULL,True);
                            // 获取SKU 价格修改后第一下单的信息，采购单号，采购数量，审核时间
                            // 如果没有下单记录，价格变化时间+90天范围内下单记录

                            if( empty($products_sku_orders_first) )
                            {
                                $is_first_sku_order = False;
                                $products_sku_orders_first['audit_time'] = $products['audit_time'];
                            }
                            if( !empty($products_sku_orders_first) )
                            {
                                if( False == $is_first_sku_order)
                                {
                                    // 无首次计算时间 90天内时间
                                    $first_order_next_30 = $this->reduced->get_change_price_six_time($products_sku_orders_first['audit_time'], 180, 'next');
                                }else {
                                    // 首次计算时间 30天内时间
                                    $first_order_next_30 = $this->reduced->get_change_price_six_time($products_sku_orders_first['audit_time'], 30, 'next');
                                }
                                $days = $this->diffBetweenTwoDays(date("Y-m-d H:i:s"),$products['audit_time']);
                                if($days)
                                {
                                    //PO 单截止时间
                                    $deadline_time = '0000-00-00 00:00:00';
                                    //获取SKU 价格涨幅,从价格变化时间起算到计算截止时间
                                    $sku_price_change = $this->reduced->get_product_sku_price_change($products['sku'],$products['audit_time'],$products['new_supplier_price'],$first_order_next_30);
                                    // 表示SKU 是连续降价，price_change_flag=2
                                    // 价格涨幅的情况
                                    $price_prev_message =  $sku_price_change['change_data'];
                                    $change_flag = NULL;

                                    if( isset($sku_price_change['price_change_flag']) && ( $sku_price_change['price_change_flag'] ==3  ) )
                                    {
                                        // 涨价价格信息
                                        $gain_message = $sku_price_change['change_data'][$sku_price_change['gain']];
                                        // 涨价截止时间
                                        if( count($sku_price_change['change_data']) >1) {
                                            $first_order_next_30 = $gain_message['audit_time'];
                                        }
                                        // 如果当前就是涨价,直接统计SKU 价格的采购数量
                                        if( $products['new_supplier_price'] > $products['old_supplier_price'])
                                        {
                                            $change_flag = $products['new_supplier_price'];
                                        }
                                    }
                                    // 价格连续上涨的情况
                                    if( isset($sku_price_change['price_change_flag']) && ( $sku_price_change['price_change_flag'] ==4  ) )
                                    {
                                        // 涨价价格信息
                                        $gain_message = $sku_price_change['change_data'][$sku_price_change['gain']];
                                        // 涨价截止时间
                                        if( count($sku_price_change['change_data']) >1) {
                                            if( isset($sku_price_change['change_data'][$sku_price_change['gain']+1])){
                                                //$first_order_next_30 = $products_sku_orders_first['audit_time'];

                                                $first_order_next_30 =$sku_price_change['change_data'][$sku_price_change['gain']+1]['audit_time'];
                                            }

                                        }
                                        //$products_sku_orders_first['audit_time'] =  $gain_message['audit_time'];
                                        if( $products['new_supplier_price'] > $products['old_supplier_price'])
                                        {
                                            $change_flag = $products['new_supplier_price'];
                                        }
                                    }
                                    $order_next_price = $products['new_supplier_price'];
                                    // SKU 审核通过计算单价
                                    $order_next_ticketed = $products['new_supplier_price']*(1+($products['new_ticketed_point']/100));
                                    if(  $products_sku_orders_first['audit_time'] > $first_order_next_30){
                                        $middle_time = NULL;
                                        $middle_time = $products_sku_orders_first['audit_time'];
                                        $products_sku_orders_first['audit_time'] =$first_order_next_30;
                                        $first_order_next_30 =$middle_time;
                                    }
                                    $detail_message = $this->reduced_orders->get_reduced_purchase_order_details($products['sku'], $products_sku_orders_first['audit_time'],$first_order_next_30,$order_next_price,$order_next_ticketed,false,$change_flag);
                                    $purchase_orders_message = $this->get_confirm_amount_by_sku_new(
                                        $products['sku'],
                                        $products_sku_orders_first['audit_time'],
                                        $first_order_next_30,
//                                        $order_next_price
                                        $products['new_supplier_price']
                                    );
                                    $deadline_time = $first_order_next_30;
                                    if( !empty($detail_message) )
                                    {
                                        $deadline_time = $this->get_confirm_amount_one($products['sku'], $products['audit_time'],$change_price,$product_base_price,NULL,True,'desc');
                                        $deadline_time = $deadline_time['audit_time'];
                                    }

                                    // 如果是涨价获取该价格区间PO最后一次下单
                                    if( $products['new_supplier_price'] > $products['old_supplier_price'] )
                                    {
                                        $deadline_times =  $this->db->select('ppo.audit_time');
                                        $this->db->from('purchase_order_items as ppoi');
                                        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
                                        $this->db->where('ppoi.sku', $products['sku']);
                                        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
                                        $this->db->where('ppo.audit_time >=', $products['audit_time']);
                                        $this->db->where('ppoi.product_base_price=',$products['new_supplier_price']);
                                        $results = $this->db->order_by('ppo.audit_time DESC')->get()->row_array();
                                        $deadline_time = $results['audit_time'];
                                    }
                                    $sku_purchase_number = array_column( $purchase_orders_message,'purchase_number');
                                    $loss_amount_data = 0;
                                    if( !empty($sku_purchase_number)) {
                                        $loss_amount = $this->db
                                            ->select('SUM(loss_amount) as loss_amount')
                                            ->from('purchase_order_reportloss')
                                            ->where(['sku' => $products['sku']])
                                            ->where_in('pur_number',$sku_purchase_number)
                                            ->where_in('status',[0, 2, 4])
                                            ->get()
                                            ->row_array();
                                        $loss_amount_data = $loss_amount['loss_amount'];
                                    }
                                    $purchase_quantity1 = array_sum( array_column($purchase_orders_message, 'confirm_amount') );
                                    $cancel_ctq1 = array_sum( array_column($purchase_orders_message, 'cancel_ctq') );
                                    $quantity = array_sum( array_column($purchase_orders_message, 'instock_qty') );
                                    $breakage_num = $loss_amount_data;// 统计报损数量
                                    $purchase_number_string = NULL;
                                    if(!empty($purchase_orders_message))
                                    {
                                        $purchase_number_string = implode(",",array_column($purchase_orders_message, 'purchase_number'));
                                    }

                                    if( empty($detail_message) && $is_first_sku_order == False)
                                    {
                                        $deadline_time = '0000-00-00 00:00:00';
                                    }
                                    $get_six_minimum_price = $this->reduced->get_six_minimum_price($products['sku'], $products['audit_time']);
                                    $insert_data[] = array(
                                        'sku' => $products['sku'],
                                        'optimizing_user' => $products['create_user_name'],
                                        'product_name' => $products['product_name'],
                                        'supplier_code' => $products['new_supplier_code'],
                                        'supplier_name' => $products['new_supplier_name'],
                                        'first_calculation_time' => ($purchase_quantity1 >0)?$products_sku_orders_first['audit_time']:'0000-00-00 00:00:00',
                                        'statistical_time' => $products_sku_orders_first['audit_time'],
                                        'deadline_time' => $deadline_time,
                                        'price_change_time' => $products['audit_time'],
                                        'product_price' => $products['old_supplier_price'],
                                        'present_price' => $products['new_supplier_price'],
                                        'price_change'  => $products['new_supplier_price'] - $products['old_supplier_price'],
                                        'purchase_quantity'=> $purchase_quantity1, //采购数量
                                        'warehous_quantity' =>$quantity, // 总入库数量
                                        'app_id'=>$products['id'],
                                        'old_type'=>0,
                                        'cancel_number'=>$cancel_ctq1,
                                        'breakage_number' =>$breakage_num,
                                        'effective_purchase_quantity' => $purchase_quantity1-$cancel_ctq1, //有效采购数量
                                        'price_change_total' => round(($products['new_supplier_price']-$products['old_supplier_price'])*($purchase_quantity1-$cancel_ctq1),3), //价格变化金额
                                        'six_minimum_price' =>$get_six_minimum_price,
                                        'purchase_numbers'  => $purchase_number_string,
                                    );
                                    $total_purchase_number = 0;
                                    //SKU 明细数据

                                    if( !empty($detail_message) )
                                    {
                                        foreach( $detail_message as $detail_key=>&$detail_value )
                                        {
                                            $purchase_orders_message = $this->reduced_orders->get_reduced_superposition($products['sku'], $detail_value['audit_time'],$first_order_next_30,$order_next_price,$order_next_ticketed);
                                            $detail_value['product_name'] =$products['product_name'];
                                            $detail_value['person_name']  =$products['create_user_name'];
                                            $old_price = $this->reduced->get_product_price_change($detail_value['sku'],$detail_value['product_base_price'],$detail_value['audit_time'],$products['id']);
                                            if(empty($old_price))
                                            {
                                                $old_price = 0;
                                            }
                                            $detail_value['old_price'] = $old_price;
                                            $detail_value['new_price'] = $detail_value['product_base_price'];
                                            $detail_value['price_change_time'] = $this->reduced_orders->get_product_update_time($detail_value['sku'],$detail_value['audit_time'],$detail_value['product_base_price']);
                                            $detail_value['range_price'] = $detail_value['product_base_price']- $old_price;
                                            $detail_value['product_audit_time'] = $detail_value['audit_time'];
                                            $detail_value['purchase_type'] = $detail_value['purchase_type_id'];
                                            //叠加PO 单
                                            $purchase_number_string = implode(",",array_column($purchase_orders_message, 'purchase_number'));
                                            // 结束计算时间
                                            $detail_value['end_days'] = $this->reduced_orders->get_effected_days( $detail_value['purchase_type_id'],$reduced_config,$detail_value['audit_time']);
                                            // SKU 审核通过计算单价
                                            $now_purchase_number = NULL;
                                            $purchase_number = NULL;

                                            $total_purchase_number+= $purchase_number;
                                            $detail_value['app_id'] = $products['id'];
                                            // 是否叠加
                                            $detail_value['is_superposition'] = $this->reduced_orders->is_superposition($detail_value['new_price'],$products['new_supplier_price'],$sku_price_change,$detail_value['purchase_number']);
                                            // 如果是叠加
                                            if(  $detail_value['is_superposition'] == 2 )
                                            {
                                                $detail_value['old_price'] = $products['old_supplier_price'];
                                                $detail_value['new_price'] = $products['new_supplier_price'];
                                                $detail_value['range_price'] =  $products['new_supplier_price']- $products['old_supplier_price'] ;
                                            }

                                            // 获取优化人 $sku_price_change,$detail_value['sku'],$detail_value['product_base_price'],
                                            $users = $this->reduced->get_product_sku_optimizing_user( $detail_value['person_name']);
                                            // 首次计算时间
                                            $detail_value['first_calculation_time'] = $products_sku_orders_first['audit_time'];
                                            // 获取备货单号
                                            $demand_numbers = $this->reduced_orders->get_purchase_demand_number($detail_value['purchase_number'],$detail_value['sku']);
                                            $detail_value['demand_number'] = $demand_numbers;
                                            $purchase_number = $this->reduced_orders->get_purchase_number_confirm_amount($detail_value['purchase_number'],$demand_numbers,$detail_value['sku']);
                                            $detail_value['purchase_num'] = $purchase_number;
                                            $detail_value['optimizing_user'] = $users['name'];
                                            $detail_value['job_number'] = $users['number'];
                                            $detail_value['purchase_price'] = $detail_value['product_base_price'];
                                            $detail_value['price_change_time'] = $products['audit_time'];// 价格变化时间
                                            $detail_value['six_minimum_price'] = $get_six_minimum_price;

                                            // $is_effect = $this->reduced_model->is_reduced_orders_effective($value['product_audit_time'], $value['type_id'], $reduced_config, $value['product_status'], $value['purchase_number'],$value['sku']);

                                            $is_effect = $this->reduced_orders->is_reduced_orders_effective($detail_value['audit_time'], $detail_value['purchase_type_id'], $reduced_config,$detail_value['product_status'], $detail_value['purchase_number'],$detail_value['sku']);
                                            if( True == $is_effect )
                                            {
                                                $detail_value['is_effect'] =1;
                                            }else{
                                                $detail_value['is_effect'] =2;
                                            }
                                            if(False == $is_first_sku_order)
                                            {
                                                $users = $this->reduced->get_product_sku_optimizing_user( $products['create_user_name']);
                                                $detail_value['product_name'] =$products['product_name'];
                                                $detail_value['person_name']  =$products['create_user_name'];
                                                $detail_value['old_price'] = $products['old_supplier_price'];
                                                $detail_value['new_price'] = $products['new_supplier_price'];
                                                $detail_value['price_change_time'] = $products['audit_time'];
                                                $detail_value['optimizing_user'] = $products['create_user_name'];
                                                $detail_value['purchase_num'] = $purchase_number;
                                                $detail_value['sku'] = $products['sku'];
                                                $detail_value['optimizing_user'] = $users['name'].$users['number'];
                                                $detail_value['job_number'] = $users['number'];
                                                $detail_value['is_superposition'] = 1;
                                                $detail_value['supplier_name'] = $products['new_supplier_name'];
                                                $detail_value['supplier_code'] = $products['new_supplier_code'];
                                                $detail_value['six_minimum_price'] = $get_six_minimum_price;
                                                $detail_value['range_price'] = $products['new_supplier_price']-$products['old_supplier_price'];
                                            }


                                            unset($detail_value['create_time']);
                                            unset($detail_value['product_base_price']);
                                            unset($detail_value['pur_ticketed_point']);
                                            unset($detail_value['purchase_type_id']);
                                            unset($detail_value['purchase_unit_price']);
                                            unset($detail_value['confirm_amount']);
                                        }
                                        
                                        $this->reduced_orders->replace_data("purchase_reduced_detail_other",$detail_message);
                                    }

                                }else{
                                    $update_data['is_end'] = 1;
                                    $update_data['end_time'] = date("Y-m-d H:i:s",time());
                                    $this->db->where("app_id",$products['id'])->where("sku",$products['sku'])->update('pur_sku_reduced_edition_new',$update_data);
                                }
                            }
                        }


                        if( !empty($insert_data) )
                        {
                            $this->reduced_orders->replace_data("sku_reduced_edition_new_other",$insert_data);
                        }
                    }
                }
            }

        }catch ( Exception $exception )
        {
            echo $exception->getMessage();

        }
    }


    /**
     * 新版本的SKU降本计划任务
     **/
    public function Reduced_edition_api( $skus_data = NULL,$start=NULL,$end=NULL)
    {
        try
        {
            //获取价格变化并且审核通过的SKU总数量
            if( NULL != $start ) {

                $reduced_skus_total = $this->reduced->get_product_update_logs(NULL, 0, $start, $end);
            }else{
                if( isset($_GET['sku']) && !empty($_GET['sku']) ) {
                    $reduced_skus_total = $this->reduced->get_product_update_logs($_GET['sku']);
                }else {
                    $reduced_skus_total = $this->reduced->get_product_update_logs();
                }
            }
            // 总共分多少页
            $page = ceil( $reduced_skus_total/$this->_limit);
            if( $page >=1 )
            {
                $params = [
                    'contrast_price'=>1,
                    'audit_status'=>3
                ];

                if( isset($_GET['sku']) && !empty($_GET['sku']) )
                {
                    $params['sku'] = $_GET['sku'];
                }

                if( NULL != $start)
                {
                    $params['audit_time_start'] = $start;
                }

                if( NULL != $end)
                {
                    $params['audit_time_end'] = $end;
                }

                if( $skus_data != NULL)
                {
                    $params['sku'] = $skus_data;
                }
                // 获取配置信息
                $reduced_config = $this->reduced_orders->is_reduced_config();

                for( $i=1;$i<=$page;++$i )
                {
                    $offset = ($i - 1) * $this->_limit;
                    $orders_info = $this->reduced->get_product_list($params, $offset,$this->_limit);

                    if( isset($orders_info['data_list']) && !empty($orders_info['data_list']))
                    {
                        $detail_value = $insert_data =[];
                        foreach( $orders_info['data_list'] as $key=>$products) {
                            // SKU 审核通过最新价格
                            $change_price = $products['new_supplier_price'];
                            // SKU 审核通过计算单价
                            $product_base_price = $products['new_supplier_price']*(1+($products['new_ticketed_point']/100));
                            // 定义标识表示是否有下单记录,默认为有
                            $is_first_sku_order = True;
                            $products_sku_orders_first = $this->get_confirm_amount_one($products['sku'], $products['audit_time'],$change_price,$product_base_price,NULL,True);
                            // 获取SKU 价格修改后第一下单的信息，采购单号，采购数量，审核时间
                            // 如果没有下单记录，价格变化时间+90天范围内下单记录

                            if( empty($products_sku_orders_first) )
                            {
                                $is_first_sku_order = False;
                                $products_sku_orders_first['audit_time'] = $products['audit_time'];
                            }
                            if( !empty($products_sku_orders_first) )
                            {
                                if( False == $is_first_sku_order)
                                {
                                    // 无首次计算时间 90天内时间
                                    $first_order_next_30 = $this->reduced->get_change_price_six_time($products_sku_orders_first['audit_time'], 180, 'next');
                                }else {
                                    // 首次计算时间 30天内时间
                                    $first_order_next_30 = $this->reduced->get_change_price_six_time($products_sku_orders_first['audit_time'], 30, 'next');
                                }
                                $days = $this->diffBetweenTwoDays(date("Y-m-d H:i:s"),$products['audit_time']);
                                if($days)
                                {
                                    //PO 单截止时间
                                    $deadline_time = '0000-00-00 00:00:00';
                                    //获取SKU 价格涨幅,从价格变化时间起算到计算截止时间
                                    $sku_price_change = $this->reduced->get_product_sku_price_change($products['sku'],$products['audit_time'],$products['new_supplier_price'],$first_order_next_30);
                                    // 表示SKU 是连续降价，price_change_flag=2
                                    // 价格涨幅的情况
                                    $price_prev_message =  $sku_price_change['change_data'];
                                    $change_flag = NULL;

                                    if( isset($sku_price_change['price_change_flag']) && ( $sku_price_change['price_change_flag'] ==3  ) )
                                    {
                                        // 涨价价格信息
                                        $gain_message = $sku_price_change['change_data'][$sku_price_change['gain']];
                                        // 涨价截止时间
                                        if( count($sku_price_change['change_data']) >1) {
                                            $first_order_next_30 = $gain_message['audit_time'];
                                        }
                                        // 如果当前就是涨价,直接统计SKU 价格的采购数量
                                        if( $products['new_supplier_price'] > $products['old_supplier_price'])
                                        {
                                            $change_flag = $products['new_supplier_price'];
                                        }
                                    }
                                    // 价格连续上涨的情况
                                    if( isset($sku_price_change['price_change_flag']) && ( $sku_price_change['price_change_flag'] ==4  ) )
                                    {
                                        // 涨价价格信息
                                        $gain_message = $sku_price_change['change_data'][$sku_price_change['gain']];
                                        // 涨价截止时间
                                        if( count($sku_price_change['change_data']) >1) {
                                            if( isset($sku_price_change['change_data'][$sku_price_change['gain']+1])){
                                                //$first_order_next_30 = $products_sku_orders_first['audit_time'];

                                                $first_order_next_30 =$sku_price_change['change_data'][$sku_price_change['gain']+1]['audit_time'];
                                            }

                                        }
                                        //$products_sku_orders_first['audit_time'] =  $gain_message['audit_time'];
                                        if( $products['new_supplier_price'] > $products['old_supplier_price'])
                                        {
                                            $change_flag = $products['new_supplier_price'];
                                        }
                                    }
                                    $order_next_price = $products['new_supplier_price'];
                                    // SKU 审核通过计算单价
                                    $order_next_ticketed = $products['new_supplier_price']*(1+($products['new_ticketed_point']/100));
                                    if(  $products_sku_orders_first['audit_time'] > $first_order_next_30){
                                        $middle_time = NULL;
                                        $middle_time = $products_sku_orders_first['audit_time'];
                                        $products_sku_orders_first['audit_time'] =$first_order_next_30;
                                        $first_order_next_30 =$middle_time;
                                    }
                                    $detail_message = $this->reduced_orders->get_reduced_purchase_order_details($products['sku'], $products_sku_orders_first['audit_time'],$first_order_next_30,$order_next_price,$order_next_ticketed,false,$change_flag);
                                    $purchase_orders_message = $this->get_confirm_amount_by_sku_new(
                                        $products['sku'],
                                        $products_sku_orders_first['audit_time'],
                                        $first_order_next_30,
//                                        $order_next_price
                                        $products['new_supplier_price']
                                    );
                                    $deadline_time = $first_order_next_30;
                                    if( !empty($detail_message) )
                                    {
                                        $deadline_time = $this->get_confirm_amount_one($products['sku'], $products['audit_time'],$change_price,$product_base_price,NULL,True,'desc');
                                        $deadline_time = $deadline_time['audit_time'];
                                    }

                                    // 如果是涨价获取该价格区间PO最后一次下单
                                    if( $products['new_supplier_price'] > $products['old_supplier_price'] )
                                    {
                                        $deadline_times =  $this->db->select('ppo.audit_time');
                                        $this->db->from('purchase_order_items as ppoi');
                                        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
                                        $this->db->join('purchase_suggest as suggest','suggest.demand_number=ppoi.demand_number  AND suggest.sku=ppoi.sku','left');
                                        $this->db->where('ppoi.sku', $products['sku']);
                                        $this->db->where('suggest.is_overseas_boutique',0);
                                        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
                                        $this->db->where('ppo.audit_time >=', $products['audit_time']);
                                        $this->db->where('ppoi.product_base_price=',$products['new_supplier_price']);
                                        $results = $this->db->order_by('ppo.audit_time DESC')->get()->row_array();
                                        $deadline_time = $results['audit_time'];
                                    }
                                    $sku_purchase_number = array_column( $purchase_orders_message,'purchase_number');
                                    $loss_amount_data = 0;
                                    if( !empty($sku_purchase_number)) {
                                        $loss_amount = $this->db
                                            ->select('SUM(loss_amount) as loss_amount')
                                            ->from('purchase_order_reportloss')
                                            ->where(['sku' => $products['sku']])
                                            ->where_in('pur_number',$sku_purchase_number)
                                            ->where_in('status',[0, 2, 4])
                                            ->get()
                                            ->row_array();
                                        $loss_amount_data = $loss_amount['loss_amount'];
                                    }
                                    $purchase_quantity1 = array_sum( array_column($purchase_orders_message, 'confirm_amount') );
                                    $cancel_ctq1 = array_sum( array_column($purchase_orders_message, 'cancel_ctq') );
                                    $quantity = array_sum( array_column($purchase_orders_message, 'instock_qty') );
                                    $breakage_num = $loss_amount_data;// 统计报损数量
                                    $purchase_number_string = NULL;
                                    if(!empty($purchase_orders_message))
                                    {
                                        $purchase_number_string = implode(",",array_column($purchase_orders_message, 'purchase_number'));
                                    }

                                    if( empty($detail_message) && $is_first_sku_order == False)
                                    {
                                        $deadline_time = '0000-00-00 00:00:00';
                                    }
                                    $get_six_minimum_price = $this->reduced->get_six_minimum_price($products['sku'], $products['audit_time']);
                                    $insert_data[] = array(
                                        'sku' => $products['sku'],
                                        'optimizing_user' => $products['create_user_name'],
                                        'product_name' => $products['product_name'],
                                        'supplier_code' => $products['new_supplier_code'],
                                        'supplier_name' => $products['new_supplier_name'],
                                        'first_calculation_time' => ($purchase_quantity1 >0)?$products_sku_orders_first['audit_time']:'0000-00-00 00:00:00',
                                        'statistical_time' => $products_sku_orders_first['audit_time'],
                                        'deadline_time' => $deadline_time,
                                        'price_change_time' => $products['audit_time'],
                                        'product_price' => $products['old_supplier_price'],
                                        'present_price' => $products['new_supplier_price'],
                                        'price_change'  => $products['new_supplier_price'] - $products['old_supplier_price'],
                                        'purchase_quantity'=> $purchase_quantity1, //采购数量
                                        'warehous_quantity' =>$quantity, // 总入库数量
                                        'app_id'=>$products['id'],
                                        'old_type'=>0,
                                        'cancel_number'=>$cancel_ctq1,
                                        'breakage_number' =>$breakage_num,
                                        'effective_purchase_quantity' => $purchase_quantity1-$cancel_ctq1, //有效采购数量
                                        'price_change_total' => round(($products['new_supplier_price']-$products['old_supplier_price'])*($purchase_quantity1-$cancel_ctq1),3), //价格变化金额
                                        'six_minimum_price' =>$get_six_minimum_price,
                                        'purchase_numbers'  => $purchase_number_string,
                                    );
                                    $total_purchase_number = 0;
                                    //SKU 明细数据

                                    if( !empty($detail_message) )
                                    {
                                        if( False == $is_first_sku_order){
                                            // 如果SKU 在价格区间未下单
                                            $sortMessage = $detail_message;
                                            array_multisort(array_column($sortMessage, 'audit_time'), SORT_ASC, $sortMessage);
                                            // 找出最早下单PO 单审核时间
                                            $effective_time =  date('Y-m-d H:i:s', strtotime($sortMessage[0]['audit_time']." +30 days"));
                                        }
                                        foreach( $detail_message as $detail_key=>&$detail_value )
                                        {
                                            $purchase_orders_message = $this->reduced_orders->get_reduced_superposition($products['sku'], $detail_value['audit_time'],$first_order_next_30,$order_next_price,$order_next_ticketed);
                                            $detail_value['product_name'] =$products['product_name'];
                                            $detail_value['person_name']  =$products['create_user_name'];
                                            $old_price = $this->reduced->get_product_price_change($detail_value['sku'],$detail_value['product_base_price'],$detail_value['audit_time'],$products['id']);
                                            if(empty($old_price))
                                            {
                                                $old_price = 0;
                                            }
                                            $detail_value['old_price'] = $old_price;
                                            $detail_value['new_price'] = $detail_value['product_base_price'];
                                            $detail_value['price_change_time'] = $this->reduced_orders->get_product_update_time($detail_value['sku'],$detail_value['audit_time'],$detail_value['product_base_price']);
                                            $detail_value['range_price'] = $detail_value['product_base_price']- $old_price;
                                            $detail_value['product_audit_time'] = $detail_value['audit_time'];
                                            $detail_value['purchase_type'] = $detail_value['purchase_type_id'];
                                            //叠加PO 单
                                            $purchase_number_string = implode(",",array_column($purchase_orders_message, 'purchase_number'));
                                            // 结束计算时间
                                            $detail_value['end_days'] = $this->reduced_orders->get_effected_days( $detail_value['purchase_type_id'],$reduced_config,$detail_value['audit_time']);
                                            // SKU 审核通过计算单价
                                            $now_purchase_number = NULL;
                                            $purchase_number = NULL;

                                            $total_purchase_number+= $purchase_number;
                                            $detail_value['app_id'] = $products['id'];
                                            // 是否叠加
                                            $detail_value['is_superposition'] = $this->reduced_orders->is_superposition($detail_value['new_price'],$products['new_supplier_price'],$sku_price_change,$detail_value['purchase_number']);
                                            // 如果是叠加
                                            if(  $detail_value['is_superposition'] == 2 )
                                            {
                                                $detail_value['old_price'] = $products['old_supplier_price'];
                                                $detail_value['new_price'] = $products['new_supplier_price'];
                                                $detail_value['range_price'] =  $products['new_supplier_price']- $products['old_supplier_price'] ;
                                            }

                                            // 获取优化人 $sku_price_change,$detail_value['sku'],$detail_value['product_base_price'],
                                            $users = $this->reduced->get_product_sku_optimizing_user( $detail_value['person_name']);
                                            // 首次计算时间
                                            $detail_value['first_calculation_time'] = $products_sku_orders_first['audit_time'];
                                            // 获取备货单号
                                            $demand_numbers = $this->reduced_orders->get_purchase_demand_number($detail_value['purchase_number'],$detail_value['sku']);
                                            $detail_value['demand_number'] = $demand_numbers;
                                            $purchase_number = $this->reduced_orders->get_purchase_number_confirm_amount($detail_value['purchase_number'],$demand_numbers,$detail_value['sku']);
                                            $detail_value['purchase_num'] = $purchase_number;
                                            $detail_value['optimizing_user'] = $users['name'];
                                            $detail_value['job_number'] = $users['number'];
                                            $detail_value['purchase_price'] = $detail_value['product_base_price'];
                                            $detail_value['price_change_time'] = $products['audit_time'];// 价格变化时间
                                            $detail_value['six_minimum_price'] = $get_six_minimum_price;

                                            // $is_effect = $this->reduced_model->is_reduced_orders_effective($value['product_audit_time'], $value['type_id'], $reduced_config, $value['product_status'], $value['purchase_number'],$value['sku']);

                                            $is_effect = $this->reduced_orders->is_reduced_orders_effective($detail_value['audit_time'], $detail_value['purchase_type_id'], $reduced_config,$detail_value['product_status'], $detail_value['purchase_number'],$detail_value['sku']);
                                            $flag_detail_value = 0;
                                            if( True == $is_effect )
                                            {
                                                $flag_detail_value =1;
                                                $detail_value['is_effect'] =1;
                                            }else{
                                                $flag_detail_value =2;
                                                $detail_value['is_effect'] =2;
                                            }
                                            if(False == $is_first_sku_order)
                                            {

                                                //$detail_me = $this->reduced_orders->get_reduced_purchase_order_details($products['sku'], $products_sku_orders_first['audit_time'],$first_order_next_30,$order_next_price,$order_next_ticketed,True);
                                                //$purchase_quantity1 = array_sum( array_column($detail_me, 'confirm_amount') );
                                                $users = $this->reduced->get_product_sku_optimizing_user( $products['create_user_name']);
                                                $detail_value['product_name'] =$products['product_name'];
                                                $detail_value['person_name']  =$products['create_user_name'];
                                                $detail_value['old_price'] = $products['old_supplier_price'];
                                                $detail_value['new_price'] = $products['new_supplier_price'];
                                                $detail_value['price_change_time'] = $products['audit_time'];
                                                $detail_value['optimizing_user'] = $products['create_user_name'];
                                                $detail_value['purchase_num'] = $purchase_number;
                                                $detail_value['sku'] = $products['sku'];
                                                $detail_value['optimizing_user'] = $users['name'].$users['number'];
                                                $detail_value['job_number'] = $users['number'];
                                                $detail_value['is_superposition'] = 1;
                                                $detail_value['supplier_name'] = $products['new_supplier_name'];
                                                $detail_value['supplier_code'] = $products['new_supplier_code'];
                                                $detail_value['six_minimum_price'] = $get_six_minimum_price;
                                                $detail_value['range_price'] = $products['new_supplier_price']-$products['old_supplier_price'];
                                                $detail_value['first_calculation_time'] = $sortMessage[0]['audit_time'];

                                                if( $flag_detail_value ==1 &&  $detail_value['audit_time'] <= $effective_time){

                                                    $detail_value['is_effect'] =1;
                                                }else{
                                                   // echo "audit_time=".$detail_value['audit_time'];
                                                    //echo "effective_time=".$effective_time;
                                                    $detail_value['is_effect'] =2;
                                                }
                                               // echo "is_effect=".$detail_value['is_effect'];die();
                                                //.
                                            }
                                            unset($detail_value['create_time']);
                                            unset($detail_value['product_base_price']);
                                            unset($detail_value['pur_ticketed_point']);
                                            unset($detail_value['purchase_type_id']);
                                            unset($detail_value['purchase_unit_price']);
                                            unset($detail_value['confirm_amount']);
                                        }
                                        $this->reduced_orders->replace_data("purchase_reduced_detail_other",$detail_message);
                                    }

                                }else{
                                    $update_data['is_end'] = 1;
                                    $update_data['end_time'] = date("Y-m-d H:i:s",time());
                                    $this->db->where("app_id",$products['id'])->where("sku",$products['sku'])->update('sku_reduced_edition_new_other',$update_data);
                                }
                            }
                        }


                        if( !empty($insert_data) )
                        {
                            $this->reduced_orders->replace_data("sku_reduced_edition_new_other",$insert_data);
                        }
                    }
                }
            }

        }catch ( Exception $exception )
        {
            echo $exception->getMessage();

        }
    }

    /**
     * sku 降本是否结束统计
     * 采购单状态(1.等待采购询价,2.信息修改待审核,3.待采购审核,5.待销售审核,6.等待生成进货单,7.等待到货,8.已到货待检测,9.全部到货,10.部分到货等待剩余到货,
     * 11.部分到货不等待剩余到货,12.作废订单待审核,13.作废订单待退款,14.已作废订单,15.信息修改驳回)
     **/

    public function get_sku_reduced_status()
    {
        $sql = " SELECT app_id FROM pur_sku_reduced_edition_new_other WHERE (is_end IS NULL OR is_end=0)";

        $result = $this->db->query($sql)->result_array();
        if( !empty($result) )
        {
            foreach($result as $key=>$value)
            {
                $purchase_number = $this->db->query(" SELECT purchase_number FROM pur_purchase_reduced_detail_other WHERE app_id=".$value['app_id'])->result_array();
                if( !empty($purchase_number) )
                {
                    $numbers = array_column( $purchase_number,"purchase_number");
                    $purchase_status_number = $this->db->from("purchase_order")->select("purchase_number")->where_in("purchase_number",$numbers)->where_in("purchase_order_status",[9,11,14])->get()->result_array();
                    $purchase_status_number = array_column($purchase_status_number,"purchase_number");
                    if( count($numbers) == count($purchase_status_number) )
                    {
                        $this->db->where('app_id', $value['app_id'])->update("pur_sku_reduced_edition_new_other",['is_end'=>1]);
                    }else{

                        $this->db->where('app_id', $value['app_id'])->update("pur_sku_reduced_edition_new_other",['is_end'=>0]);
                    }
                }
            }
        }

    }
    
    /**
     * SKU降本计划任务
     * /reduced_edition_api/get_reduced_edition_sku_list
     * @author Jaden 2019-1-17
    */
    public function get_reduced_edition_sku_list(){
        set_time_limit(0);
        $start_time = $this->input->get_post('start_time');
        $end_time = $this->input->get_post('end_time');
        $now_time = date('Y-m-d H:i:s');

        if(!empty($start_time) AND !empty($end_time)){
            $params = [
                'audit_time_start' => $start_time, // 价格变化开始时间
                'audit_time_end' => $end_time, // 价格变化结束时间
                //'sku' => 'JY00020-04',
                'contrast_price'=>1,
                'audit_status'=>3,
                'old_type'=>0
            ];    
        }else{
            $params = [
                //'audit_time_start' => $starttime, // 价格变化开始时间
                //'audit_time_end' => $endtime, // 价格变化结束时间
                //'sku' => 'JY00020-04',
                'contrast_price'=>1,
                'audit_status'=>3,
                'old_type'=>0
            ];  
        }

        /**
          * 从商品审核日志中统计时间段内并且审核通过，数据来源为老采购系统的数据
         **/
        if(!empty($start_time) AND !empty($end_time)){
            $total = $this->db->select('sku')->from('product_update_log')->where('audit_time>="'.$start_time.'" AND audit_time<="'.$end_time.'" AND old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=0')->count_all_results();
        }else{
            $total = $this->db->select('sku')->from('product_update_log')->where('old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=0')->count_all_results();    
        }
        $limit = 500; 
        $num = ceil($total/$limit);
        $field ='a.*,b.sku as product_sku,b.product_thumb_url';
        if($total>1){
            //$this->reduced_edition->delete_reduced_edition($starttime,$endtime_st);
            for ($i=1; $i <= $num; $i++) { 
                $edition_arr =array();
                $offset = ($i - 1) * $limit;
                $orders_info = $this->product_mod_audit_model->get_product_list($params, $limit,$offset,$field);
                $reduced_edition_list = $orders_info['data_list'];
                if(!empty($reduced_edition_list)){
                    foreach ($reduced_edition_list as $key => $value) {
                        $confirm_amount1 = $cancel_ctq1 = 0;
                        $confirm_amount2 = $cancel_ctq2 = 0;
                        $where = '';
                        $app_id_arr1 = $app_id_arr2 =  array();
                        $confirm_amount_arr_one1 = $confirm_amount_arr_one2 = array();
                        $add_arr1 = $add_arr2 = array();
                        $price_change_time = $value['audit_time'];//价格改变时间
                        //echo $value['sku'].'-----'.$price_change_time.'<br>';
                        $endtime = date("Y-m-d H:i:s",strtotime("$price_change_time+30 day"));//价格改变后30天时间
                        //价格改变之后是否有下单
                        $change_price = $value['new_supplier_price'];
                        $product_base_price = $value['new_supplier_price']*(1+($value['new_ticketed_point']/100));
                        $confirm_amount_arr_one1 = $this->get_confirm_amount_one($value['sku'], $price_change_time,$change_price,$product_base_price);

                        if(empty($confirm_amount_arr_one1)){//改变价格之后没有数据,跳过计算
                            continue;
                        }else{
                            if(!empty($value['cost_begin_time'])){
                                $days = $this->diffBetweenTwoDays($now_time,$value['cost_begin_time']);
                                if($days>30 AND $value['cost_begin_time']!='NULL'){
                                    continue;
                                }else{
                                    $app_id_arr1[] = $value['id'];
                                    //更新pur_product_update_log 降本开始计算时间
                                    $this->db->where('id', $value['id'])->update('product_update_log',['cost_begin_time'=>$confirm_amount_arr_one1['audit_time']]);
                                    //获取分段计算时间
                                    $order_start_time1 = $confirm_amount_arr_one1['audit_time'];
                                    $countTime1 = self::getPurNumTime($order_start_time1);
                                    //echo '<pre>';
                                    //echo $value['sku'].'-----'.$order_start_time1.'<br>';
                                    //print_R($countTime1);
                                    $countTime2 = !empty($countTime1) ? self::getPurNumTime($countTime1['end_time'],$countTime1['limit']) : [];

                                    $dateArray=[];
                                    if(!empty($countTime1)){
                                        $dateArray[] = date('Y-m-01 00:00:00',strtotime($countTime1['begin_time']));
                                    }
                                    if(!empty($countTime2)){
                                        $dateArray[] = date('Y-m-01 00:00:00',strtotime($countTime2['begin_time']));
                                    }

                                    //判断是否降价之后涨价，如果是涨价，数量不累积
                                    $sku_is_rise1 = $this->reduced_edition->get_sku_is_rise($value['sku'],$price_change_time,$endtime);
                                    if(!empty($sku_is_rise1) AND ($value['new_supplier_price']<$value['old_supplier_price']) AND $sku_is_rise1['audit_time']<=$countTime1['end_time']){
                                        $countTime1['end_time'] = $sku_is_rise1['audit_time'];
                                    }else{
                                        $countTime1['end_time'] = $countTime1['end_time'];
                                    }


                                    //判断是否降价，如果是降价，数量不累积
                                    $sku_is_reduction1 = $this->reduced_edition->get_sku_is_reduction($value['sku'],$price_change_time,$endtime);
                                    if(!empty($sku_is_reduction1) AND ($value['new_supplier_price']>$value['old_supplier_price']) AND $sku_is_reduction1['audit_time']<=$countTime1['end_time']){
                                        $countTime1['end_time'] = $sku_is_reduction1['audit_time'];
                                    }else{
                                        $countTime1['end_time'] = $countTime1['end_time'];
                                    }

                                    //月开始时间
                                    $month_date = get_month_time($order_start_time1);
                                    $confirm_amount_arr1 = $this->get_confirm_amount_by_sku($value['sku'], $countTime1['begin_time'], $countTime1['end_time'],$change_price,$product_base_price);
                                    $confirm_amount1 = array_sum( array_column($confirm_amount_arr1, 'confirm_amount') );
                                    $cancel_ctq1 = array_sum( array_column($confirm_amount_arr1, 'cancel_ctq') );
                                    if(isset($confirm_amount_arr1[0]['confirm_amount']) AND !empty($confirm_amount_arr1[0]['confirm_amount'])){
                                        $purchase_quantity1 = $confirm_amount1;
                                    }else{
                                        $purchase_quantity1 = 0;
                                    }
                                    //获取取消数量
                                    /*
                                    $order_cancel_arr1 = $this->purchase_order_cancel_model->get_cancel_ctq_by_sku($value['sku'], $countTime1['begin_time'], $countTime1['end_time']);
                                    if(isset($order_cancel_arr1['cancel_ctq'])){
                                        $cancel_ctq1 = $order_cancel_arr1['cancel_ctq'];
                                    }else{
                                        $cancel_ctq1 = 0;
                                    }
                                    */

                                    $statistical_time1 = substr($order_start_time1,0,7);//统计时间

                                    $add_arr1['sku'] = $value['sku'];
                                    $add_arr1['optimizing_user'] = $value['create_user_name'];
                                    $add_arr1['product_name'] = $value['product_name'];
                                    $add_arr1['supplier_code'] = $value['new_supplier_code'];
                                    $add_arr1['sku'] = $value['sku'];
                                    $add_arr1['supplier_name'] = $value['new_supplier_name'];
                                    $add_arr1['price_change_time'] = $value['audit_time'];
                                    $add_arr1['first_calculation_time'] = isset($order_start_time1) ? $order_start_time1 : '还未下单';
                                    $add_arr1['statistical_time'] = $statistical_time1;
                                    $add_arr1['original_price'] = $value['old_supplier_price'];//原价
                                    $add_arr1['present_price'] = $value['new_supplier_price'];//现价
                                    $add_arr1['price_change'] = $value['new_supplier_price']-$value['old_supplier_price'];//价格变化幅度(现价-原价)
                                    $add_arr1['price_change_total'] =round(($value['new_supplier_price']-$value['old_supplier_price'])*($purchase_quantity1-$cancel_ctq1),3);//价格变化金额
                                    $add_arr1['purchase_quantity'] = $purchase_quantity1;//采购数量
                                    $add_arr1['cancel_ctq'] = $cancel_ctq1;//取消数量
                                    $add_arr1['effective_purchase_quantity'] = $purchase_quantity1-$cancel_ctq1;//有效采购数量
                                    $add_arr1['app_id'] = $value['id'];
                                    $add_arr1['old_type'] = 0;
                                    array_push($edition_arr,$add_arr1);
                                    //删除数数据，重新计算
                                    //$app_id_arr1 = array_column($reduced_edition_list, 'id');
                                    //$this->db->where('statistical_time="'.$statistical_time2.'" AND old_type=0')->where_in('app_id', $app_id_arr2)->delete($this->reduced_edition->tableName());
                                    $this->db->where('old_type=0')->where_in('app_id', $app_id_arr1)->delete($this->reduced_edition->tableName());

                                    //跨幅度两个月的情况
                                    if(isset($dateArray[1])){
                                        $app_id_arr2[] = $value['id'];
                                        //判断是否就涨价，如果是涨价，数量不累积
                                        $sku_is_rise2 = $this->reduced_edition->get_sku_is_rise($value['sku'],$price_change_time,$endtime);
                                        if(!empty($sku_is_rise2) AND ($value['new_supplier_price']<$value['old_supplier_price']) AND $sku_is_rise2['audit_time']<=$countTime2['end_time']){
                                            $countTime2['end_time'] = $sku_is_rise2['audit_time'];
                                        }else{
                                            $countTime2['end_time'] = $countTime2['end_time'];
                                        }

                                        //判断是否降价，如果是降价，数量不累积
                                        $sku_is_reduction2 = $this->reduced_edition->get_sku_is_reduction($value['sku'],$price_change_time,$endtime);
                                        if(!empty($sku_is_reduction2) AND ($value['new_supplier_price']>$value['old_supplier_price']) AND $sku_is_reduction2['audit_time']<=$countTime2['end_time']){
                                            $countTime2['end_time'] = $sku_is_reduction2['audit_time'];
                                        }else{
                                            $countTime2['end_time'] = $countTime2['end_time'];
                                        }

                                        $confirm_amount_arr2 = $this->get_confirm_amount_by_sku($value['sku'], $countTime2['begin_time'], $countTime2['end_time'],$change_price,$product_base_price);
                                        $confirm_amount2 = array_sum( array_column($confirm_amount_arr2, 'confirm_amount') );
                                        $cancel_ctq2 = array_sum( array_column($confirm_amount_arr2, 'cancel_ctq') );
                                        if(isset($confirm_amount_arr2[0]['confirm_amount']) AND !empty($confirm_amount_arr2[0]['confirm_amount'])){
                                            $purchase_quantity2 = $confirm_amount2;
                                        }else{
                                            $purchase_quantity2 = 0;
                                        }
                                        //获取取消数量
                                        /*
                                        $order_cancel_arr2 = $this->purchase_order_cancel_model->get_cancel_ctq_by_sku($value['sku'], $dateArray[1], $endtime);
                                        if(isset($order_cancel_arr2['cancel_ctq'])){
                                            $cancel_ctq2 = $order_cancel_arr2['cancel_ctq'];
                                        }else{
                                            $cancel_ctq2 = 0;
                                        }
                                        */

                                        $now_date = date('Y-m-d H:i:s');
                                        $statistical_time2 = substr($dateArray[1],0,7);//统计时间
                                        if(isset($purchase_quantity2) && isset($cancel_ctq2) && $now_date>$dateArray[1]){
                                            $add_arr2['sku'] = $value['sku'];
                                            $add_arr2['optimizing_user'] = $value['create_user_name'];
                                            $add_arr2['product_name'] = $value['product_name'];
                                            $add_arr2['supplier_code'] = $value['new_supplier_code'];
                                            $add_arr2['sku'] = $value['sku'];
                                            $add_arr2['supplier_name'] = $value['new_supplier_name'];
                                            $add_arr2['price_change_time'] = $value['audit_time'];
                                            $add_arr2['first_calculation_time'] = isset($order_start_time1) ? $order_start_time1 : '还未下单';
                                            $add_arr2['statistical_time'] = $statistical_time2;
                                            $add_arr2['original_price'] = $value['old_supplier_price'];//原价
                                            $add_arr2['present_price'] = $value['new_supplier_price'];//现价
                                            $add_arr2['price_change'] = $value['new_supplier_price']-$value['old_supplier_price'];//价格变化幅度(现价-原价)
                                            $add_arr2['price_change_total'] =round(($value['new_supplier_price']-$value['old_supplier_price'])*($purchase_quantity2-$cancel_ctq2),3);//价格变化金额
                                            $add_arr2['purchase_quantity'] = $purchase_quantity2;//采购数量
                                            $add_arr2['cancel_ctq'] = $cancel_ctq2;//取消数量
                                            $add_arr2['effective_purchase_quantity'] = $purchase_quantity2-$cancel_ctq2;//有效采购数量
                                            $add_arr2['app_id'] = $value['id'];
                                            $add_arr2['old_type'] = 0;
                                            array_push($edition_arr,$add_arr2);
                                                
                                        }
                                        //删除数数据，重新计算
                                    //$app_id_arr2 = array_column($reduced_edition_list, 'id');
                                    //$this->db->where('statistical_time="'.$statistical_time2.'" AND old_type=0')->where_in('app_id', $app_id_arr2)->delete($this->reduced_edition->tableName());
                                    $this->db->where('old_type=0')->where_in('app_id', $app_id_arr2)->delete($this->reduced_edition->tableName());
                                    }
                                }    
                            }else{
                                continue;
                            } 
                        }
                    }
                    
                    if(!empty($edition_arr)){
                        //echo '<pre>';
                        //print_r($edition_arr);exit;     
                        $this->reduced_edition->insert_reduced_batch_all($edition_arr);    
                    }
                    
                }
                usleep(100000);
                var_dump('OK');
            }
        }else{
            var_dump('查不到符合条件数据');
        }

       
            
    }

    /**
     * 老系统SKU降本计划任务
     * /reduced_edition_api/get_old_edition_sku_list
     * @author Jaden 2019-1-17
    */
    public function get_old_edition_sku_list(){
        set_time_limit(0);
        $now_time = date('Y-m-d H:i:s');
        $start_time = $this->input->get_post('start_time');
        $end_time = $this->input->get_post('end_time');
        $now_time = date('Y-m-d H:i:s');

        if(!empty($start_time) AND !empty($end_time)){
            $params = [
                'audit_time_start' => $start_time, // 价格变化开始时间
                'audit_time_end' => $end_time, // 价格变化结束时间
                //'sku' => 'JY00020-04',
                'contrast_price'=>1,
                'audit_status'=>3,
                'old_type'=>1
            ];    
        }else{
            $params = [
                //'audit_time_start' => $starttime, // 价格变化开始时间
                //'audit_time_end' => $endtime, // 价格变化结束时间
                //'sku' => 'JY00020-04',
                'contrast_price'=>1,
                'audit_status'=>3,
                'old_type'=>1
            ];  
        }
        //$total = $this->db->select('sku')->from('product_update_log')->where('audit_time>="'.$starttime.'" AND audit_time<="'.$endtime.'" AND old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=1')->count_all_results();
        if(!empty($start_time) AND !empty($end_time)){
            $total = $this->db->select('sku')->from('product_update_log')->where('audit_time>="'.$start_time.'" AND audit_time<="'.$end_time.'" AND old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=1')->count_all_results();
        }else{
            $total = $this->db->select('sku')->from('product_update_log')->where('old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=1')->count_all_results();    
        }
        $limit = 500; 
        $num = ceil($total/$limit);
        $field ='a.*,b.sku as product_sku,b.product_thumb_url';
        if($total>1){
            //$this->reduced_edition->delete_reduced_edition($starttime,$endtime);
            for ($i=1; $i <= $num; $i++) { 
                $edition_arr =array();
                $offset = ($i - 1) * $limit;
                $orders_info = $this->product_mod_audit_model->get_product_list($params, $limit,$offset,$field);
                $reduced_edition_list = $orders_info['data_list'];
                if(!empty($reduced_edition_list)){
                    foreach ($reduced_edition_list as $key => $value) {
                        $confirm_amount1 = $cancel_ctq1 = 0;
                        $confirm_amount2 = $cancel_ctq2 = 0;
                        $where = '';
                        $app_id_arr1 = $app_id_arr2 = array();
                        $confirm_amount_arr_one1 = $confirm_amount_arr_one2 = array();
                        $add_arr1 = $add_arr2 = array();
                        $price_change_time = $value['audit_time'];//价格改变时间
                        $endtime = date("Y-m-d H:i:s",strtotime("$price_change_time+30 day"));//价格改变后30天时间
                        //价格改变之后是否有下单
                        $change_price = $value['new_supplier_price'];
                        $product_base_price = $value['new_supplier_price']*(1+($value['new_ticketed_point']/100));
                        $confirm_amount_arr_one1 = $this->get_confirm_amount_one($value['sku'], $price_change_time,$change_price,$product_base_price);
                        if(empty($confirm_amount_arr_one1)){//改变价格之后没有数据,跳过计算
                            continue;
                        }else{
                            $days = $this->diffBetweenTwoDays($now_time,$value['cost_begin_time']);
                            if($days>30 AND !empty($value['cost_begin_time'])){
                                continue;
                            }else{
                                $app_id_arr1[] = $value['id'];
                                //更新pur_product_update_log 降本开始计算时间
                                $this->db->where('id', $value['id'])->update('product_update_log',['cost_begin_time'=>$confirm_amount_arr_one1['audit_time']]);
                                //获取分段计算时间
                                $order_start_time1 = $confirm_amount_arr_one1['audit_time'];
                                $countTime1 = self::getPurNumTime($order_start_time1);
                                $countTime2 = !empty($countTime1) ? self::getPurNumTime($countTime1['end_time'],$countTime1['limit']) : [];

                                $dateArray=[];
                                if(!empty($countTime1)){
                                    $dateArray[] = date('Y-m-01 00:00:00',strtotime($countTime1['begin_time']));
                                }
                                if(!empty($countTime2)){
                                    $dateArray[] = date('Y-m-01 00:00:00',strtotime($countTime2['begin_time']));
                                }

                                //判断是否就涨价，如果是涨价，数量不累积
                                $sku_is_rise1 = $this->reduced_edition->get_sku_is_rise($value['sku'],$price_change_time,$endtime);
                                if(!empty($sku_is_rise1) AND ($value['new_supplier_price']<$value['old_supplier_price']) AND $sku_is_rise1['audit_time']<=$countTime1['end_time']){
                                    $countTime1['end_time'] = $sku_is_rise1['audit_time'];
                                }else{
                                    $countTime1['end_time'] = $countTime1['end_time'];
                                }

                                //判断是否降价，如果是降价，数量不累积
                                $sku_is_reduction1 = $this->reduced_edition->get_sku_is_reduction($value['sku'],$price_change_time,$endtime);
                                if(!empty($sku_is_reduction1) AND ($value['new_supplier_price']>$value['old_supplier_price']) AND $sku_is_reduction1['audit_time']<=$countTime1['end_time']){
                                    $countTime1['end_time'] = $sku_is_reduction1['audit_time'];
                                }else{
                                    $countTime1['end_time'] = $countTime1['end_time'];
                                }


                                //月开始时间
                                $month_date = get_month_time($order_start_time1);
                                $confirm_amount_arr1 = $this->get_confirm_amount_by_sku($value['sku'], $countTime1['begin_time'], $countTime1['end_time'],$change_price,$product_base_price);
                                $confirm_amount1 = array_sum( array_column($confirm_amount_arr1, 'confirm_amount') );
                                $cancel_ctq1 = array_sum( array_column($confirm_amount_arr1, 'cancel_ctq') );
                                if(isset($confirm_amount_arr1[0]['confirm_amount']) AND !empty($confirm_amount_arr1[0]['confirm_amount'])){
                                    $purchase_quantity1 = $confirm_amount1;
                                }else{
                                    $purchase_quantity1 = 0;
                                }
                                //获取取消数量
                                /*
                                $order_cancel_arr1 = $this->purchase_order_cancel_model->get_cancel_ctq_by_sku($value['sku'], $countTime1['begin_time'], $countTime1['end_time']);
                                if(isset($order_cancel_arr1['cancel_ctq'])){
                                    $cancel_ctq1 = $order_cancel_arr1['cancel_ctq'];
                                }else{
                                    $cancel_ctq1 = 0;
                                }
                                */

                                $statistical_time1 = substr($order_start_time1,0,7);//统计时间

                                $add_arr1['sku'] = $value['sku'];
                                $add_arr1['optimizing_user'] = $value['create_user_name'];
                                $add_arr1['product_name'] = $value['product_name'];
                                $add_arr1['supplier_code'] = $value['new_supplier_code'];
                                $add_arr1['sku'] = $value['sku'];
                                $add_arr1['supplier_name'] = $value['new_supplier_name'];
                                $add_arr1['price_change_time'] = $value['audit_time'];
                                $add_arr1['first_calculation_time'] = isset($order_start_time1) ? $order_start_time1 : '还未下单';
                                $add_arr1['statistical_time'] = $statistical_time1;
                                $add_arr1['original_price'] = $value['old_supplier_price'];//原价
                                $add_arr1['present_price'] = $value['new_supplier_price'];//现价
                                $add_arr1['price_change'] = $value['new_supplier_price']-$value['old_supplier_price'];//价格变化幅度(现价-原价)
                                $add_arr1['price_change_total'] =round(($value['new_supplier_price']-$value['old_supplier_price'])*($purchase_quantity1-$cancel_ctq1),3);//价格变化金额
                                $add_arr1['purchase_quantity'] = $purchase_quantity1;//采购数量
                                $add_arr1['cancel_ctq'] = $cancel_ctq1;//取消数量
                                $add_arr1['effective_purchase_quantity'] = $purchase_quantity1-$cancel_ctq1;//有效采购数量
                                $add_arr1['app_id'] = $value['id'];
                                $add_arr1['old_type'] = 1;
                                array_push($edition_arr,$add_arr1);
                                //删除数数据，重新计算
                                //$app_id_arr1 = array_column($reduced_edition_list, 'id');
                                $this->db->where('old_type=1')->where_in('app_id', $app_id_arr1)->delete($this->reduced_edition->tableName());

                                //跨幅度两个月的情况
                                if(isset($dateArray[1])){
                                    $app_id_arr2[] = $value['id'];
                                    //判断是否就涨价，如果是涨价，数量不累积
                                    $sku_is_rise2 = $this->reduced_edition->get_sku_is_rise($value['sku'],$price_change_time,$endtime);
                                    if(!empty($sku_is_rise2) AND ($value['new_supplier_price']<$value['old_supplier_price']) AND $sku_is_rise2['audit_time']<=$countTime2['end_time']){
                                        $countTime2['end_time'] = $sku_is_rise2['audit_time'];
                                    }else{
                                        $countTime2['end_time'] = $countTime2['end_time'];
                                    }

                                    //判断是否降价，如果是降价，数量不累积
                                    $sku_is_reduction2 = $this->reduced_edition->get_sku_is_reduction($value['sku'],$price_change_time,$endtime);
                                    if(!empty($sku_is_reduction2) AND ($value['new_supplier_price']>$value['old_supplier_price']) AND $sku_is_reduction2['audit_time']<=$countTime2['end_time']){
                                        $countTime2['end_time'] = $sku_is_reduction2['audit_time'];
                                    }else{
                                        $countTime2['end_time'] = $countTime2['end_time'];
                                    }

                                    $confirm_amount_arr2 = $this->get_confirm_amount_by_sku($value['sku'], $countTime2['begin_time'], $countTime2['end_time'],$change_price,$product_base_price);
                                    $confirm_amount2 = array_sum( array_column($confirm_amount_arr2, 'confirm_amount') );
                                    $cancel_ctq2 = array_sum( array_column($confirm_amount_arr2, 'cancel_ctq') );
                                    if(isset($confirm_amount_arr2[0]['confirm_amount']) AND !empty($confirm_amount_arr2[0]['confirm_amount'])){
                                        $purchase_quantity2 = $confirm_amount2;
                                    }else{
                                        $purchase_quantity2 = 0;
                                    }
                                    //获取取消数量
                                    /*
                                    $order_cancel_arr2 = $this->purchase_order_cancel_model->get_cancel_ctq_by_sku($value['sku'], $dateArray[1], $endtime);
                                    if(isset($order_cancel_arr2['cancel_ctq'])){
                                        $cancel_ctq2 = $order_cancel_arr2['cancel_ctq'];
                                    }else{
                                        $cancel_ctq2 = 0;
                                    }
                                    */

                                    $now_date = date('Y-m-d H:i:s');
                                    $statistical_time2 = substr($dateArray[1],0,7);//统计时间
                                    if(isset($purchase_quantity2) && isset($cancel_ctq2) && $now_date>$dateArray[1]){
                                        $add_arr2['sku'] = $value['sku'];
                                        $add_arr2['optimizing_user'] = $value['create_user_name'];
                                        $add_arr2['product_name'] = $value['product_name'];
                                        $add_arr2['supplier_code'] = $value['new_supplier_code'];
                                        $add_arr2['sku'] = $value['sku'];
                                        $add_arr2['supplier_name'] = $value['new_supplier_name'];
                                        $add_arr2['price_change_time'] = $value['audit_time'];
                                        $add_arr2['first_calculation_time'] = isset($order_start_time1) ? $order_start_time1 : '还未下单';
                                        $add_arr2['statistical_time'] = $statistical_time2;
                                        $add_arr2['original_price'] = $value['old_supplier_price'];//原价
                                        $add_arr2['present_price'] = $value['new_supplier_price'];//现价
                                        $add_arr2['price_change'] = $value['new_supplier_price']-$value['old_supplier_price'];//价格变化幅度(现价-原价)
                                        $add_arr2['price_change_total'] =round(($value['new_supplier_price']-$value['old_supplier_price'])*($purchase_quantity2-$cancel_ctq2),3);//价格变化金额
                                        $add_arr2['purchase_quantity'] = $purchase_quantity2;//采购数量
                                        $add_arr2['cancel_ctq'] = $cancel_ctq2;//取消数量
                                        $add_arr2['effective_purchase_quantity'] = $purchase_quantity2-$cancel_ctq2;//有效采购数量
                                        $add_arr2['app_id'] = $value['id'];
                                        $add_arr2['old_type'] = 1;
                                        array_push($edition_arr,$add_arr2);
                                            
                                    }
                                    //删除数数据，重新计算
                                //$app_id_arr2 = array_column($reduced_edition_list, 'id');
                                $this->db->where('old_type=1')->where_in('app_id', $app_id_arr2)->delete($this->reduced_edition->tableName());
                                }
                            }    
                             
                        }
                    }
                    
                    if(!empty($edition_arr)){
                        //echo '<pre>';
                        //print_r($edition_arr);exit;     
                        $this->reduced_edition->insert_reduced_batch_all($edition_arr);    
                    }
                }
                usleep(100000);
                var_dump('OK');

            }
        }else{
            var_dump('查不到符合条件数据');
        }




    }

    /**
     * 根据SKU和时间段统计审核通过的采购数量
     * @author Jaden
     * @date 2019/3/14 17:21
     * @param string $sku
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public function get_confirm_amount_by_sku_new($sku,$start_time,$end_time,$change_flag = NULL){
        if(empty($sku) || empty($start_time) || empty($end_time)){
            return [];
        }
        $this->db->select('ppoi.confirm_amount as confirm_amount,ppoi.purchase_number,ppoi.sku,ppo.audit_time,ware.instock_qty,breakage_qty');
        $this->db->from('purchase_order_items as ppoi');
        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->db->join('warehouse_results_main as ware', 'ware.purchase_number=ppoi.purchase_number AND ware.sku=ppoi.sku', 'left');
        $this->db->join('purchase_suggest as suggest','suggest.demand_number=ppoi.demand_number AND suggest.sku=ppoi.sku','LEFT');
//        if( NULL != $change_flag)
//        {
//            $this->db->where('ppoi.product_base_price',$change_flag);
//        }else{
//            $this->db->where('ppo.audit_time >=', $start_time);
//            $this->db->where('ppo.audit_time <=', $end_time);
//        }
        $this->db->where('ppoi.product_base_price<=',$change_flag);
        $this->db->where('ppo.audit_time >=', $start_time);
        $this->db->where('ppo.audit_time <=', $end_time);
        $this->db->where('suggest.is_overseas_boutique',0);
        $this->db->where('ppoi.sku', $sku);
        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11,12]);
        $results = $this->db->order_by('ppo.audit_time asc')->get()->result_array();

        foreach ($results as $key => $value) {
            $cancel_ctq = $this->purchase_order_cancel_model->get_cancel_ctq_new($value['sku'],$value['purchase_number']);
            $results[$key]['cancel_ctq'] = $cancel_ctq;
        }
        return $results;

    }

    /**
     * 根据SKU和时间段统计审核通过的采购数量
     * @author Jaden
     * @date 2019/3/14 17:21
     * @param string $sku
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public function get_confirm_amount_by_sku($sku,$start_time,$end_time,$change_price,$product_base_price){
        if(empty($sku) || empty($start_time) || empty($end_time) || empty($change_price)){
            return [];
        }
        $this->db->select('ppoi.confirm_amount as confirm_amount,ppoi.purchase_number,ppoi.sku,ppo.audit_time,ware.instock_qty');
        $this->db->from('purchase_order_items as ppoi');
        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->db->join('warehouse_results_main as ware', 'ware.purchase_number=ppoi.purchase_number AND ware.sku=ppoi.sku', 'left');
        $this->db->where('ppoi.sku', $sku);
        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
        $this->db->where('ppo.audit_time >=', $start_time);
        $this->db->where('ppo.audit_time <=', $end_time);
        $this->db->where('(ppoi.product_base_price<="'.$change_price.'" OR ppoi.product_base_price="'.$product_base_price.'")');
        $results = $this->db->order_by('ppo.audit_time asc')->get()->result_array();
        foreach ($results as $key => $value) {
            $cancel_ctq = $this->purchase_order_cancel_model->get_cancel_ctq($value['sku'],$value['purchase_number']);
            $results[$key]['cancel_ctq'] = $cancel_ctq;
        }
        return $results;
      
    }

    /**
     * 价格改变后数据是否有变化
      @param:  $SKU     string
     *         $start_time   string  价格变化时间
     *         $change_price  string  价格变化后时间
     *         $product_base_price   string   票点计算后价格
     **/
    public function get_confirm_amount_one($sku,$start_time,$change_price,$product_base_price,$purchase_number = NULL,$Flase=False,$ASC='asc'){
        if( empty($sku) || empty($start_time) ){
            return [];
        }

        $search_sku_sql = " SELECT id,audit_time FROM pur_product_update_log WHERE new_supplier_price='{$change_price}' AND sku='{$sku}' AND new_supplier_price!=old_supplier_price AND audit_status=3 AND audit_time>'{$start_time}' and audit_time!='{$start_time}' ORDER BY id ASC ";
        $search_sku_audit_time = $this->db->query($search_sku_sql)->row_array();

        $this->db->select('ppo.buyer_id,ppoi.product_base_price,ppoi.confirm_amount as confirm_amount,ppoi.purchase_number,ppo.audit_time');
        $this->db->from('purchase_order_items as ppoi');
        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->db->join('purchase_suggest as suggest','ppoi.demand_number=suggest.demand_number AND ppoi.sku=suggest.sku','LEFT');
        $this->db->where('ppoi.sku', $sku);
        $this->db->where('ppo.audit_time >=', $start_time);
        $this->db->where('suggest.is_overseas_boutique',0);
        if( !empty($search_sku_audit_time))
        {
            $this->db->where('ppo.audit_time <=', $search_sku_audit_time['audit_time']);
        }
        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
        if( True == $Flase)
        {
            $this->db->where('(ppoi.product_base_price="' . $change_price . '" OR ppoi.product_base_price="' . $product_base_price . '")');
        }else {
            $this->db->where('(ppoi.product_base_price<="' . $change_price . '" OR ppoi.product_base_price="' . $product_base_price . '")');
        }
        if( NULL != $purchase_number )
        {
            $this->db->where("ppo.purchase_number",$purchase_number);
        }
        $results = $this->db->order_by('ppo.audit_time '.$ASC)->limit(1)->get()->row_array();
        return $results;
       // return 1000;
    }

    //计算两个时间相差天数
    function diffBetweenTwoDays ($day1, $day2)
    {
      $second1 = strtotime($day1);
      $second2 = strtotime($day2);
        
      if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
      }
      return ($second1 - $second2) / 86400;
    }



    //根据开始时间和天数间隔计算采购数据计算时间;
    public static function getPurNumTime($begin_time,$limit=30){
        if($limit<0){
            return [];
        }
        $month_big = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        //开始的月份
        $date_month_old = (int)date('m',strtotime($begin_time));
        //下个月的月份
        $year = $date_month_old==12 ? date('Y',strtotime($begin_time)) +1 : date('Y',strtotime($begin_time));
        $date_time_new = strtotime('1 '.$month_big[$date_month_old%12].' '.$year);
        //今天的时间戳
        $date_time_old = strtotime(date('d',strtotime($begin_time)).' '.$month_big[$date_month_old-1].' '.date('Y',strtotime($begin_time)));
        //距下月剩余时间
        //var_dump($limit);
        $time_new = ($date_time_new - $date_time_old)/24/60/60;
        $old_limit=$limit;
        $limit-=$time_new;
        if($limit>0){
            $array['begin_time'] = $begin_time;
            $array['end_time'] = date('Y-m-d H:i:s',$date_time_new);
            $array['limit']    = $limit;
        }else{
            $array['begin_time'] = $begin_time;
            $array['end_time'] = date('Y-m-d 00:00:00',strtotime("$begin_time + $old_limit day"));
            $array['limit']    = $limit;
        }
        return $array;
    }


    public function truncate()
    {
        $sku = isset($_GET['sku'])?$_GET['sku']:null;
        if( NULL != $sku ) {
            $this->db->where("sku",$sku)->delete("pur_purchase_reduced_detail");
        }
    }

}