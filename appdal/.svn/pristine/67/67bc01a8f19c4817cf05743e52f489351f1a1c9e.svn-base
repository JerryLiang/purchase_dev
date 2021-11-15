<?php if(!defined('BASEPATH'))
    exit('No direct script access allowed');

include_once APPPATH."core/MY_API_Controller.php";

/**
 * 核销功能计划任务
 * User: Jolon
 * Date: 2020/04/20 10:00
 */
class Charge_against_api extends MY_API_Controller {

    public function __construct(){
        parent::__construct();

        $this->load->model('statement/Purchase_statement_model');
        $this->load->model('statement/Purchase_inventory_items_model');
        $this->load->model('statement/Charge_against_surplus_model');
    }

    /**
     * 刷新 采购单 等待到货时间
     * @link charge_against_api/repair_order_waiting_time?purchase_numbers=ABD000535
     */
    public function repair_order_waiting_time(){
        $purchase_numbers = $this->input->get_post('purchase_numbers');
        if(empty($purchase_numbers)){
            $this->error_json('数据缺少');
        }
        $purchase_numbers = explode(",",$purchase_numbers);

        $sql = "UPDATE pur_purchase_order SET waiting_time=(
                    SELECT MIN(operate_time) 
                    FROM pur_operator_log AS A 
                    WHERE A.record_number=pur_purchase_order.`purchase_number` 
                    AND A.`content`='审核采购单'
                    AND content_detail='审核通过'
                )
                WHERE purchase_number IN('".implode("','",$purchase_numbers)."')";

        print_r($sql);

        $this->db->query($sql);

        echo 'sss';exit;
    }


    /**
     * 刷新 采购单 等待到货时间
     * @link charge_against_api/repair_order_waiting_time2?purchase_number=ABD000535&waiting_time=2020-03-29 14:17:30
     */
    public function repair_order_waiting_time2(){
        $purchase_number = $this->input->get_post('purchase_number');
        $waiting_time = $this->input->get_post('waiting_time');
        if(empty($purchase_number) or empty($waiting_time)){
            $this->error_json('数据缺少');
        }

        $sql = "UPDATE pur_purchase_order SET waiting_time='$waiting_time' WHERE purchase_number IN('$purchase_number') LIMIT 1";

        print_r($sql);

        $this->db->query($sql);

        echo 'sss';exit;
    }


    /**
     * 自动创建冲销结余记录
     * @link charge_against_api/create_charge_against_surplus
     */
    public function create_charge_against_surplus(){
        $purchase_numbers = $this->input->get_post('purchase_numbers');

        $result = $this->Charge_against_surplus_model->insertBatch($purchase_numbers);

        $this->success_json($result);
    }

    /**
     * 重新计算 采购单冲销结余
     * @url /charge_against_api/recalculate_surplus
     */
    public function recalculate_surplus(){
        $purchase_numbers = $this->input->get_post('purchase_numbers');

        $result = $this->Charge_against_surplus_model->recalculate_surplus($purchase_numbers,2);

        if($result['code']){
            $this->success_json($result['message']);
        }else{
            $this->error_json($result['message']);
        }
    }

    /**
     * 初始化 采购单冲销结余（初始化临时方法）
     * @url /charge_against_api/init_charge_against_surplus
     */
    public function init_charge_against_surplus(){
        $limit = $this->input->get_post('limit');
        $limit = isset($limit)?intval($limit):50000;

        $sql = "INSERT INTO `pur_purchase_order_charge_against_surplus`(purchase_number,purchase_type_id,source,compact_number,product_money,charge_against_status,create_time)
        
                SELECT `PO`.`purchase_number`, `PO`.`purchase_type_id`, `PO`.`source`, IFNULL(PCI.compact_number, \"\") AS compact_number,
                IFNULL(SUM(POI.confirm_amount*purchase_unit_price) ,0) AS product_money, 0 AS `charge_against_status`,`PO`.waiting_time AS create_time
                FROM `pur_purchase_order` AS `PO`
                LEFT JOIN `pur_purchase_order_items` AS `POI` ON `PO`.`purchase_number`=`POI`.`purchase_number`
                LEFT JOIN `pur_purchase_order_pay_type` AS `POPT` ON `POPT`.`purchase_number`=`PO`.`purchase_number`
                LEFT JOIN `pur_purchase_compact_items` AS `PCI` ON `PCI`.`purchase_number`=`PO`.`purchase_number`
                WHERE PO.purchase_order_status NOT IN(1,3,5,6)
                AND (SELECT COUNT(1) FROM pur_purchase_order_charge_against_surplus AS tmp WHERE tmp.purchase_number=PO.purchase_number) < 1
                AND PO.purchase_number!=''
                GROUP BY `PO`.`purchase_number`
                LIMIT {$limit}";

        $this->db->query($sql);

        echo 'sss';exit;
    }

    /**
     * 刷新历史数据  入库批次号
     * 在 /Sync_warehouse_results_api/sync_warehouse_results_data 之前刷数据
     * @url /charge_against_api/init_warehouse_results_for_instock_batch
     */
    public function init_warehouse_results_for_instock_batch(){
        // 初始化入库批次号
        $count = $this->db->select('count(1) as count')->where('instock_batch','')->get('warehouse_results')->row_array();
        if(isset($count['count']) and $count['count'] > 0){// 2090412 条数据
            $this->db->query("UPDATE pur_warehouse_results SET instock_batch=id,deliery_batch=id WHERE instock_batch='' LIMIT 100000");
            echo date('Y-m-d H:i:s') . ' ' . "已更新";exit;
        }else{
            echo date('Y-m-d H:i:s') . ' ' . "无需更新";exit;
        }
    }

    /**
     * 刷新历史数据  请款单来源主体
     * @url /charge_against_api/init_purchase_order_pay_source_subject
     */
    public function init_purchase_order_pay_source_subject(){
        $count = $this->db->select('count(1) as count')->where("source_subject=2 AND pur_number LIKE '%HT%'")->get('purchase_order_pay')->row_array();

        if(isset($count['count']) and $count['count'] > 0){
            $res = $this->db->update('purchase_order_pay',['source_subject' => 1],"source_subject=2 AND pur_number  LIKE '%HT%' LIMIT 10000");
            echo date('Y-m-d H:i:s') . ' ' . "已更新";exit;
        }else{
            echo date('Y-m-d H:i:s') . ' ' . '无需刷新';exit;
        }
    }

    /**
     * 刷新 入库明细记录 冲销状态
     * @link charge_against_api/init_refresh_inventory_item_surplus?instock_batch=1956380
     */
    public function init_refresh_inventory_item_surplus(){
        $instock_batch = $this->input->get_post('instock_batch');
        if(empty($instock_batch)) exit('参数缺失');

        $result = $this->Charge_against_surplus_model->recalculate_inventory_item_surplus($instock_batch);

        $this->success_json($result);
    }



    /**
     * 刷新 是否隔离数据 冲销状态
     * @link charge_against_api/init_refresh_inventory_item_isolation?instock_batch=1956380&is_isolation=1
     */
    public function init_refresh_inventory_item_isolation(){
        $instock_batch = $this->input->get_post('instock_batch');
        $is_isolation  = $this->input->get_post('is_isolation');

        if($instock_batch and $is_isolation){
            $update_sql = "UPDATE pur_statement_warehouse_results SET is_isolation='{$is_isolation}' where instock_batch='{$instock_batch}' limit 1 ";
            $this->db->query($update_sql);

        }else{
            // 采购单审核日期=6月4日晚24点之前，且采购单对应的合同号，在6月4日晚24点之前存在至少一次已付款记录，付款时间≤6月4日晚24点
            $update_sql  = "UPDATE pur_statement_warehouse_results SET is_isolation=1
                        WHERE id IN(
                            SELECT id FROM (
                                SELECT a.id
                                FROM `pur_statement_warehouse_results` `a`
                                LEFT JOIN `pur_purchase_order` `c` ON `c`.`purchase_number`=`a`.`purchase_number`
                                WHERE `a`.`source` = '1'
                                AND a.`is_isolation`=2
                                AND `c`.`audit_time` <= '2020-06-04 23:59:59' 
                                AND  ( 
                                    SELECT COUNT(payd.id)
                                    FROM `pur_purchase_order_pay` AS pay
                                    LEFT JOIN `pur_purchase_order_pay_detail` AS payd ON pay.`requisition_number`=payd.`requisition_number`
                                    WHERE pay.`pay_status`=51
                                    AND payd.`purchase_number`=a.`purchase_number`
                                    LIMIT 1
                                )  >= 1
                                LIMIT 10000
                            ) AS tmp
                        );";
            $this->db->query($update_sql);
        }

        $this->success_json();
    }

    /**
     * 初始化 请款单PO金额明细
     * charge_against_api/init_pay_po_detail
     */
    public function init_pay_po_detail(){
        $init_pay_po_detail = $this->rediss->getData('init_pay_po_detail');
        if($init_pay_po_detail){
            echo  "初始化 请款单PO金额明细-执行中";exit;
        }


        $this->rediss->setData('init_pay_po_detail',1);

        for($i = 1;$i <= 10;$i ++){
            echo "开始第：{$i} 次处理<br/>";

            $query_requisition_number_sql = "select requisition_number 
                from pur_purchase_order_pay_detail as a
                where (select count(1) from pur_purchase_order_pay_po_detail as b where b.requisition_number=a.requisition_number)=0
                group by a.requisition_number
                limit 1000";

            $requisition_number_list = $this->db->query($query_requisition_number_sql)->result_array();
            if(empty($requisition_number_list)){
                echo '数据已全部处理完成';exit;
            }
            $requisition_number_list = array_column($requisition_number_list,'requisition_number');
            $requisition_number_list_str = "'".implode("','",$requisition_number_list)."'";


            echo "本次处理请款单号个数：".count($requisition_number_list)." >>> ";


            $sql = "
            INSERT INTO pur_purchase_order_pay_po_detail(requisition_number,purchase_number,product_money,freight,discount,process_cost,commission,pay_total)
            SELECT 
                requisition_number,
                purchase_number,
                sum(IFNULL(product_money,0)) as product_money,
                sum(IFNULL(freight,0)) as freight,
                sum(IFNULL(discount,0)) as discount,
                sum(IFNULL(process_cost,0)) as process_cost,
                sum(IFNULL(commission,0)) as commission,
                sum(IFNULL(pay_total,0)) as pay_total
            FROM pur_purchase_order_pay_detail
            WHERE requisition_number in({$requisition_number_list_str})
            group by requisition_number,purchase_number";

            $res = $this->db->query($sql);
            if($res){
                echo "执行成功<br/>";
            }else{
                echo "执行失败<br/>";
            }

            echo "<br/>";
            echo "<br/>";
        }


        $this->rediss->deleteData('init_pay_po_detail');

        echo '处理结束';exit;
    }

    // charge_against_api/init_need_pay_time
    public function init_need_pay_time(){

        $querySql = "SELECT A.pur_number
                FROM pur_purchase_order_pay AS A
                LEFT JOIN pur_purchase_order_pay_type AS B ON A.pur_number=B.purchase_number
                WHERE A.`need_pay_time` != LEFT(B.`accout_period_time`,10)
                AND A.`source`=2
                AND A.`pay_status`=40
                LIMIT 1000";
        $result = $this->db->query($querySql)->result_array();

        if($result){
            $pur_numbers_str = implode("','",array_unique(array_column($result,'pur_number')));

            $updateSql = "UPDATE `pur_purchase_order_pay`,`pur_purchase_order_pay_type` 
                    SET pur_purchase_order_pay.`need_pay_time`=LEFT(pur_purchase_order_pay_type.`accout_period_time`,10)
                    WHERE pur_purchase_order_pay.pur_number=pur_purchase_order_pay_type.purchase_number
                    AND pur_purchase_order_pay.`need_pay_time` != LEFT(pur_purchase_order_pay_type.`accout_period_time`,10)
                    AND pur_purchase_order_pay.`source`=2
                    AND pur_purchase_order_pay.`pay_status`=40
                    AND `pur_purchase_order_pay`.pur_number IN('{$pur_numbers_str}')";
            $this->db->query($updateSql);
        }else{
            echo '1.执行完毕<br>';
        }


        $querySql2 = "SELECT A.pur_number
                    FROM `pur_purchase_order_pay` AS A
                    LEFT JOIN `pur_purchase_order_pay_type`  AS B ON A.pur_number=B.purchase_number
                    WHERE A.`need_pay_time` != LEFT(B.`accout_period_time`,10)
                    AND A.`source`=2
                    LIMIT 2000";
        $result2 = $this->db->query($querySql2)->result_array();

        if($result2){
            $pur_numbers_str2 = implode("','",array_unique(array_column($result2,'pur_number')));

            $updateSql = "UPDATE `pur_purchase_order_pay`,`pur_purchase_order_pay_type` 
                    SET pur_purchase_order_pay.`need_pay_time`=LEFT(pur_purchase_order_pay_type.`accout_period_time`,10)
                    WHERE pur_purchase_order_pay.pur_number=pur_purchase_order_pay_type.purchase_number
                    AND pur_purchase_order_pay.`need_pay_time` != LEFT(pur_purchase_order_pay_type.`accout_period_time`,10)
                    AND pur_purchase_order_pay.`source`=2
                    AND `pur_purchase_order_pay`.pur_number IN('{$pur_numbers_str2}')";
            $this->db->query($updateSql);
        }else{
            echo '2.执行完毕<br>';
        }

        exit;

    }

}