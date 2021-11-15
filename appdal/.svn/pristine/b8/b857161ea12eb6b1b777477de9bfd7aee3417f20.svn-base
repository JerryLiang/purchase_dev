<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * SKU采购单导出定时同步脚本
 * User: luxu
 * Date: 2019/11/13
 */

class Purchase_import_api extends MY_API_Controller{

    public $_mongodb = NULL;
    public $_bulk = NULL;
    public $_author_db = NULL;

    public function __construct()
    {
        parent::__construct();
        $this->_ci = get_instance();
        //获取redis配置
        $this->_ci->load->config('mongodb');
        $host = $this->_ci->config->item('mongo_host');
        $port = $this->_ci->config->item('mongo_port');
        $user = $this->_ci->config->item('mongo_user');
        $password = $this->_ci->config->item('mongo_pass');
        $author_db = $this->_ci->config->item('mongo_db');
        $this->_mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $this->_author_db = $author_db;
        $this->load->model('purchase/Purchase_order_model','purchase_order_model');
        $this->load->model('purchase/reduced_edition_model','reduced_edition');
    }

    public function delete()
    {

        $bulk           = new MongoDB\Driver\BulkWrite;
        $bulk->delete([]);
        $res = $this->_mongodb->executeBulkWrite("{$this->_author_db}.product", $bulk);

    }

    // 获取采购单触发器数据
    public function get_purchase_trigger(){

        $sql = " SELECT COUNT(id) AS total FROM pur_purchase_import_trigger";
        $trigger_purchases = $this->db->query($sql)->row_array();
        if( !empty($trigger_purchases) )
        {
            $total = $trigger_purchases['total'];
            $limit = 2000;
            $page = ceil( $total/$limit);
            for( $i=1; $i<=$page;++$i)
            {
                $skus_sql = " SELECT * FROM pur_purchase_import_trigger LIMIT ".($i - 1) * $limit .",".$limit;
                $skus_data = $this->db->query($skus_sql)->result_array();
                $skus = array_column( $skus_data,"sku");
                $skus = array_map( function($sku){

                    return sprintf("'%s'",$sku);
                },$skus);

                $this->get_purchase_list($skus);
            }
        }

    }

    public function reduced_data()
    {
        ob_start();
        $bulk           = new MongoDB\Driver\BulkWrite;
        $bulk->delete([]);
        $slaveDb=$this->load->database('slave',TRUE);
        $this->_mongodb->executeBulkWrite("{$this->_author_db}.reduced_detail", $bulk);
        $sql = " SELECT COUNT(*) AS total
                            FROM `pur_purchase_reduced_detail` AS `detail`
                            LEFT JOIN `pur_warehouse_results_main` AS `main` ON `main`.`purchase_number`=`detail`.`purchase_number` AND
                            `main`.`sku`=`detail`.`sku`
                            LEFT JOIN `pur_purchase_order_reportloss` AS `loss` ON `detail`.`purchase_number`=`loss`.`pur_number` AND
                            `detail`.`sku`=`loss`.`sku`
                            LEFT JOIN `pur_purchase_order` AS `purorders` ON `detail`.`purchase_number`=`purorders`.`purchase_number`
                          
                            ORDER BY `detail`.`id` DESC
                 ";
        $result = $slaveDb->query($sql)->row_array();
        $total = $result['total'];
        $limit = 2000;
        $page =  ceil($total/$limit);
        $page = $page+1;
        for( $i=1;$i<=$page;++$i) {

            $sql = "SELECT `detail`.*,loss.status AS lossstatus,main.instock_qty AS maininstock_qty,loss.loss_amount AS loss_lossamount,
                            detail.purchase_num AS detail_purchase_num,cancal.cancel_ctq AS cancel_ctq,purorders.purchase_order_status,
                            main.instock_date,purorders.completion_time,prod.is_purchasing
                            FROM `pur_purchase_reduced_detail` AS `detail`
                            LEFT JOIN `pur_warehouse_results_main` AS `main` ON `main`.`purchase_number`=`detail`.`purchase_number` AND
                            `main`.`sku`=`detail`.`sku`
                            LEFT JOIN `pur_purchase_order_reportloss` AS `loss` ON `detail`.`purchase_number`=`loss`.`pur_number` AND
                            `detail`.`sku`=`loss`.`sku`
                            LEFT JOIN `pur_purchase_order` AS `purorders` ON `detail`.`purchase_number`=`purorders`.`purchase_number`
                            LEFT JOIN `pur_product` AS `prod` ON `detail`.`sku` = `prod`.`sku`
                            LEFT JOIN (SELECT
                            sum(cancel_ctq) AS cancel_ctq,
                            sku,
                            purchase_number
                            FROM
                            pur_purchase_order_cancel_detail
                            GROUP BY
                            sku,
                            purchase_number) AS cancal ON `detail`.`sku`=`cancal`.`sku` AND `detail`.`purchase_number`=`cancal`.`purchase_number`
                            
                            ORDER BY `detail`.`id` DESC LIMIT " .($i - 1) * $limit .",".$limit;
            $purchase_list_data = $slaveDb->query($sql)->result_array();
            if( !empty($purchase_list_data))
            {

                foreach ($purchase_list_data as $key => $value) {

                        if( $value['detail_purchase_num']>$value['cancel_ctq'] || empty($value['cancel_ctq']))
                        {
                            $value['purchase_num_flag'] = 1;
                        }else{
                            $value['purchase_num_flag'] = 0;
                        }
                        $this->_bulk = new MongoDB\Driver\BulkWrite();
                        $mongodb_result = $this->_bulk->insert($value);
                        try {

                            $result = $this->_mongodb->executeBulkWrite("{$this->_author_db}.reduced_detail", $this->_bulk);
                            usleep(2000);
                        } catch (Exception $exp) {
                            echo $exp->getMessage();
                        }

                    }
            }


        }


    }



    public function get_purchase_list($skus = array()){
        $total = $this->get_purchase_data_sum($skus);
        $limit  = 1000;
        $page = ceil( $total['num']/$limit);
        $this->delete();
        $slaveDb=$this->load->database('slave',TRUE);
        for( $i=1;$i<=$page;++$i) {

//
            $sql = "SELECT
                `ppoi`.`sku`,
                `ppoi`.`purchase_number`,
                `map`.`demand_number`,
                 `ppo`.`purchase_order_status`,
                `ppo`.`supplier_name`,
                `ppo`.`supplier_code`,
                 `ppoi`.`confirm_amount`,
                  `ppo`.`audit_time`,
                   `ppo`.`purchase_name`,
                `ppoi`.`maintain_ticketed_point`,
                `ppoi`.`coupon_rate`,
                `ppoi`.`coupon_rate_price`,
               
                `ppoi`.`purchase_unit_price`,
                `ppoi`.`freight`,
                `ppoi`.`process_cost`,
                `ppoi`.`discount`,
                `ppoi`.`create_time`,
               
              
                `ppoi`.`id`,
                `ppoi`.`product_img_url`,
               
                `ppo`.`purchase_type_id`,
                `ppo`.`purchase_type_id` AS `purchase_type`,
                `ppoi`.`is_new` AS `product_is_new`,
                `ppoi`.`product_name`,
                `ppo`.`buyer_name`,
                `sg`.`buyer_name` as `sg_buyer_name`,
                `sg`.`suggest_order_status`,
                
                `sg`.`supplier_name` as `sg_supplier_name`,
                `sg`.`supplier_code` as `sg_supplier_code`,
                `ppoi`.`confirm_amount` AS `purchase_amount`,
                `ppoi`.`confirm_amount`,
                `ppoi`.`purchase_unit_price`,
                `ppoi`.`is_new`,
                `ppoi`.`discount`,
                `ppoi`.`tax_rate`,
                `ppoi`.`is_overdue` as item_is_overdue,
                `ppoi`.`is_overdue`,
                `ppo`.`is_drawback`,
                `sg`.`is_drawback` as `sg_is_drawback`,
               
                `sg`.`purchase_name` as `sg_purchase_name`,
                `ppoi`.`pur_ticketed_point`,
                `ppoi`.`product_base_price`,
                `ppoi`.`pur_ticketed_point`,
                `ppoi`.`modify_remark`,
                `ice`.`export_tax_rebate_rate` AS `export_tax_rebate_rate_ice`,
                `ice`.`invoice_name` AS `invoice_name_ice`,
                `ice`.`issuing_office` AS `issuing_office_ice`,
                `ppo`.`warehouse_code`,
                `ppo`.`is_expedited`,
                `ppo`.`account_type`,
                `sg`.`account_type` as `sg_account_type`,
                `ppo`.`pay_type`,
                `sg`.`pay_type` as `sg_pay_type`,
                `ppo`.`currency_code`,
                `ppy`.`settlement_ratio`,
                `ppo`.`shipping_method_id`,
                `ppo`.`create_time`,
               
                `ppo`.`plan_product_arrive_time`,
                `ppo`.`first_plan_product_arrive_time`,
                `ppo`.`source`,
                `ppo`.`is_ali_order`,
                `ppo`.`is_ali_abnormal`,
                `ppo`.`ali_order_amount`,
                `ppo`.`is_ali_price_abnormal`,
                `ppo`.`ali_order_status`,
                `ppy`.`is_freight`,
                `ppy`.`freight_formula_mode`,
                `ppy`.`purchase_acccount`,
                `ppy`.`pai_number`,
                `ppy`.`accout_period_time`,
                `ppy`.`accout_period_time` AS `need_pay_time`,
                `ppoi`.`pur_ticketed_point` AS `ticketed_point`,
                `ppy`.`cargo_company_id`,
                `ppy`.`express_no`,
                `ware`.`arrival_date`,
                `ware`.`instock_date`,
                `ware`.`arrival_qty`,
                `ware`.`instock_qty`,
                `ware`.`breakage_qty`,
                `ppo`.`is_destroy`,
                `ppo`.`pay_status`,
                `ppo`.`pay_time`,
                `ppo`.`shipment_type`,
                `loss`.`loss_amount`,
                `loss`.`status` AS `loss_status`,
                `loss`.`status`,
                `sp`.`surplus_quota`,
                 `sp`.`status` AS `supplier_status`,
                `sp`.`tap_date_str`,
                `pro`.`is_relate_ali`,
                `pro`.`product_status`,
                `pro`.`starting_qty`,
                `pro`.`starting_qty_unit`,
               
                `pop`.`payment_platform`,
                `sg`.`destination_warehouse`,
                `sg`.`logistics_type` ,
                `pro`.`is_inspection`,
                `pro`.`is_invalid`,
                `sg`.`supplier_source`,
                `sg`.`lock_type`,
                `sta`.`statement_number`,
                `pro`.`state_type`,
                `ppo`.`is_expedited`,
                `pro`.`purchase_packaging`,
                `ppo`.`lack_quantity_status`,
                `pro`.`is_invalid`,
                `pro`.`product_cn_link`,
                `ppy`.`real_price`,
                `ppo`.`buyer_id`,
            
                `ppo`.`is_cross_border`,
                `sp`.`status` AS `supplier_status`,
                `sg`.`lock_type`,
                `sg`.`is_storage_abnormal`,
                `ppoi`.`invoiced_qty` AS  `invoices_issued`,
                  `pro`.`is_equal_sup_id`,
              `pro`.`is_equal_sup_name`
             
                FROM
                `pur_purchase_order_items` AS `ppoi`
                LEFT JOIN `pur_purchase_order` AS `ppo` ON `ppoi`.`purchase_number` = `ppo`.`purchase_number`
                LEFT JOIN `pur_purchase_order_pay_type` AS `ppy` ON `ppy`.`purchase_number` = `ppo`.`purchase_number`
                LEFT JOIN `pur_purchase_product_invoice` AS `ice` ON `ice`.`items_id` = `ppoi`.`id`
                LEFT JOIN `pur_warehouse_results_main` AS `ware` ON `ware`.`purchase_number` = `ppoi`.`purchase_number`
                AND `ware`.`sku` = `ppoi`.`sku`
                LEFT JOIN `pur_purchase_order_reportloss` AS `loss` ON `ppoi`.`purchase_number` = `loss`.`pur_number`
                AND `ppoi`.`sku` = `loss`.`sku`
                LEFT JOIN `pur_supplier` AS `sp` ON `sp`.`supplier_code` = `ppo`.`supplier_code`
                LEFT JOIN `pur_product` AS `pro` ON `pro`.`sku` = `ppoi`.`sku`
                LEFT JOIN `pur_purchase_suggest_map` AS `map` ON `map`.`purchase_number` = `ppoi`.`purchase_number`
                AND `map`.`sku` = `ppoi`.`sku`
                LEFT JOIN `pur_purchase_order_pay` AS `pop` ON `ppoi`.`purchase_number` = `pop`.`pur_number`
                LEFT JOIN `pur_purchase_suggest` AS `sg` ON `map`.`demand_number` = `sg`.`demand_number`
                LEFT JOIN `pur_purchase_statement_items` AS `sta` ON `sta`.`purchase_number` = `ppoi`.`purchase_number`
                AND `sta`.`sku` = `ppoi`.`sku`
                WHERE
                ppoi.id IN (
                SELECT
                orderdata.id
                FROM
                (
                SELECT
                distinct b.id,type.real_price
                FROM
                pur_purchase_order AS a
                LEFT JOIN pur_purchase_order_items AS b ON a.purchase_number = b.purchase_number
                LEFT JOIN pur_purchase_order_pay_type AS type ON a.purchase_number=type.purchase_number
                LEFT JOIN pur_purchase_suggest_map AS c ON c.purchase_number = b.purchase_number and c.sku=b.sku
                LEFT JOIN pur_purchase_suggest AS d ON d.demand_number = c.demand_number
                LEFT JOIN `pur_purchase_order` AS `e` ON `e`.`purchase_number` = `a`.`purchase_number`
                LEFT JOIN `pur_supplier` AS `f` ON `f`.`supplier_code` = `e`.`supplier_code`
               WHERE b.purchase_number NOT LIKE 'YPO%' AND a.purchase_order_status <>14 ";
            if(!empty($skus))
            {
                $sql .= " AND b.sku IN (".implode(",",$skus).")";
            }

            $sql .= " GROUP BY b.id
                    ORDER BY
                    b.id DESC
                    LIMIT
                    ".($i - 1) * $limit .",".$limit.") AS orderdata )
                    GROUP BY `ppoi`.`id`
                    ORDER BY
                    `ppoi`.`id` DESC";
            $purchase_list_data = $slaveDb->query($sql)->result_array();
            if( empty($purchase_list_data) )
            {
                break;
            }
            if( !empty($purchase_list_data))
            {
                foreach($purchase_list_data as $purchase_data) {
                    $this->_bulk = new MongoDB\Driver\BulkWrite();
                    $this->_bulk->insert($purchase_data);
                    try {

                        $result = $this->_mongodb->executeBulkWrite("{$this->_author_db}.product", $this->_bulk);
                        var_dump($result->getInsertedCount());
                    } catch (Exception $exp) {
                        echo $exp->getMessage();
                    }
                }
            }


        }

    }

    /**
     * 获取采购单总数
     **/
    public function get_purchase_data_sum($sku = array())
    {
        $sql = "SELECT count(distinct b.id) as num
                FROM pur_purchase_order_items as b LEFT JOIN pur_purchase_order AS a ON b.purchase_number=a.purchase_number
                WHERE a.purchase_number NOT LIKE 'YPO%' AND a.purchase_order_status <> 14 ";
        if( !empty($sku))
        {
            $sql .= " AND b.sku IN (".implode(",",$sku).")";
        }
        return $this->db->query($sql)->row_array();
    }

    public function get(){
        $filter = array('id'=>['$in'=>['2083540']]);
        $options = array(
            'sort' => ['_id' => -1],
        );

        $query = new MongoDB\Driver\Query($filter,$options);
        $cursor = $this->_mongodb->executeQuery('db.product', $query)->toArray();
        print_r($cursor);
    }

    public function get_gropress_data1()
    {
        ini_set('max_execution_time','18000');
        ob_start();
        $bulk           = new MongoDB\Driver\BulkWrite;
        $bulk->delete([]);
        $res = $this->_mongodb->executeBulkWrite("{$this->_author_db}.progress_detail_1", $bulk);
        $total = 50000;
        $limit=  1000;
        $page = ceil($total/$limit);
        for($i=1;$i<=$page;++$i)
        {
            $sql ="SELECT `pli`.`express_no`, `progress`.`product_img`, `progress`.`estimate_time` as `pestimate_time`,
`warehouse`.`arrival_date` AS `tarrival_date`, `abn`.`abnormal_type`, `orders`.`purchase_order_status` AS
`orders_status`, `progress`.`product_line_ch` AS `progress_product_line`, `pli`.`express_no` AS `pliexpress_no`,
`pli`.`status` AS `plistatus`, `tproduct`.`days_sales_7`, `suggest`.`suggest_order_status`, `suggest`.`left_stock`,
`progress`.*, `line`.`linelist_cn_name` AS `product_line_ch`, `tproduct`.`product_status`, `tproduct`.`sku_sale7` AS
`sevensale`, CONCAT(progress.purchase_number, progress.sku) as purchase_num_sku
FROM `pur_purchase_progress` as `progress`
LEFT JOIN `pur_product_line` AS `line` ON `line`.`id`=`progress`.`product_line_ch`
LEFT JOIN `pur_product` AS `tproduct` ON `tproduct`.`sku`=`progress`.`sku`
LEFT JOIN `pur_purchase_suggest` AS `suggest` ON `suggest`.`sku`=`progress`.`sku` AND
`suggest`.`demand_number`=`progress`.`demand_number`
LEFT JOIN `pur_purchase_logistics_info` AS `pli` ON `pli`.`purchase_number`=`progress`.`purchase_number` AND `pli`.`sku`
= `progress`.`sku`
LEFT JOIN `pur_purchase_order` AS `orders` ON `orders`.`purchase_number`=`progress`.`purchase_number`
LEFT JOIN `pur_purchase_warehouse_abnormal` AS `abn` ON `progress`.`purchase_number` = `abn`.`pur_number` AND
(`abn`.`sku`=`progress`.`sku` OR `abn`.`sku`='') AND (`abn`.`is_handler`=0 OR `abn`.`is_handler`=2)
LEFT JOIN (SELECT id AS id,arrival_date,purchase_number,sku FROM pur_warehouse_results GROUP BY purchase_number,sku
ORDER BY id ASC ) AS warehouse ON `progress`.`purchase_number`=`warehouse`.`purchase_number` AND
`progress`.`sku`=`warehouse`.`sku`
WHERE `suggest`.`suggest_order_status` IN('7', '10')
AND `progress`.`create_time` >= '2019-12-01'
AND `progress`.`create_time` <= '2020-02-10'  GROUP BY
	`progress`.`demand_number` ORDER BY `progress`.`id` DESC LIMIT ".($i - 1) * $limit .",".$limit;
            $result = $this->db->query($sql)->result_array();
            if(!empty($result)){

                foreach ($result as $key => $purchase_data) {
                    if ((empty($purchase_data['tarrival_date']) && $purchase_data['pestimate_time'] < date("Y-m-d H:i:s")) || (!empty($purchase_data['tarrival_date']) && $purchase_data['tarrival_date'] > $purchase_data['pestimate_time'])) {

                        $purchase_data['is_overdue'] ='1';
                    }else if( ( !empty($purchase_data['tarrival_date']) && $purchase_data['tarrival_date'] < $purchase_data['pestimate_time']) || ($purchase_data['pestimate_time']>date("Y-m-d H:i:s")) ){
                        $purchase_data['is_overdue'] ='2';
                    }
                    $this->_bulk = new MongoDB\Driver\BulkWrite();
                    $this->_bulk->insert($purchase_data);
                    try {

                        $result = $this->_mongodb->executeBulkWrite("{$this->_author_db}.progress_detail_1", $this->_bulk);
//                    var_dump($result->getInsertedCount());
                    } catch (Exception $exp) {
                        echo $exp->getMessage();
                    }
                }
            }


        }
    }

    /**
     *function:订单追踪数据导入MONGDB 缓存
     **/

    public function get_progress_data()
    {
        ob_start();
        $bulk           = new MongoDB\Driver\BulkWrite;
        $bulk->delete([]);
        $res = $this->_mongodb->executeBulkWrite("{$this->_author_db}.progress_detail", $bulk);

        $total_sql  = "
                
              SELECT COUNT(id) AS total
            FROM `pur_purchase_progress` as `progress`
          
        ";
        $total = $this->db->query($total_sql)->row_array();
        $limit = 1000;
        $page = ceil($total['total']/$limit);
        for( $i=1;$i<=$page;++$i) {
            $offset = ($i-1)*$limit;
            $query_builder = $this->db;
            $query_builder->from("purchase_progress as progress ");
            $query_builder->join("pur_product_line AS line", "line.id=progress.product_line_ch", "LEFT");
            $query_builder->join("pur_product AS tproduct", "tproduct.sku=progress.sku", "LEFT");
            $query_builder->join(" purchase_suggest AS suggest", "suggest.sku=progress.sku AND suggest.demand_number=progress.demand_number", "LEFT");
            $query_builder->join("purchase_logistics_info AS pli", "pli.purchase_number=progress.purchase_number AND pli.sku = progress.sku", 'LEFT');
            $query_builder->join("pur_purchase_order AS orders", "orders.purchase_number=progress.purchase_number", "LEFT");
            $query_builder->join("purchase_warehouse_abnormal AS abn",
                "progress.purchase_number = abn.pur_number AND (abn.sku=progress.sku OR abn.sku='') AND (abn.is_handler=0 OR abn.is_handler=2)",
                'LEFT');
            $query_builder->JOIN("(SELECT id AS id,arrival_date,purchase_number,sku FROM pur_warehouse_results GROUP BY purchase_number,sku ORDER BY id ASC )  AS  warehouse", "progress.purchase_number=warehouse.purchase_number  AND progress.sku=warehouse.sku ", "LEFT");
            $result = $query_builder->select(" pli.express_no,progress.product_img, progress.estimate_time as pestimate_time,warehouse.arrival_date AS tarrival_date,abn.abnormal_type,orders.purchase_order_status AS orders_status,progress.product_line_ch AS progress_product_line,pli.express_no AS pliexpress_no,pli.status AS plistatus,tproduct.days_sales_7, suggest.suggest_order_status,suggest.left_stock,progress.*,line.linelist_cn_name AS product_line_ch,tproduct.product_status,tproduct.sku_sale7 AS sevensale,CONCAT(progress.purchase_number,progress.sku) as purchase_num_sku")->group_by("progress.id")->limit($limit,$offset)->get()->result_array();
            if (!empty($result)) {
                foreach ($result as $key => $purchase_data) {
                    if ((empty($purchase_data['tarrival_date']) && $purchase_data['pestimate_time'] < date("Y-m-d H:i:s")) || (!empty($purchase_data['tarrival_date']) && $purchase_data['tarrival_date'] > $purchase_data['pestimate_time'])) {

                    $purchase_data['is_overdue'] ='1';
                }else if( ( !empty($purchase_data['tarrival_date']) && $purchase_data['tarrival_date'] < $purchase_data['pestimate_time']) || ($purchase_data['pestimate_time']>date("Y-m-d H:i:s")) ){
                    $purchase_data['is_overdue'] ='2';
                }
                $this->_bulk = new MongoDB\Driver\BulkWrite();
                $this->_bulk->insert($purchase_data);
                try {

                    $result = $this->_mongodb->executeBulkWrite("{$this->_author_db}.progress_detail", $this->_bulk);
//                    var_dump($result->getInsertedCount());
                } catch (Exception $exp) {
                    echo $exp->getMessage();
                    }
                }
            }
        }
    }

}