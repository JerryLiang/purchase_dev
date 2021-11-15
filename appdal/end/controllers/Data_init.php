<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";


/**
 * 初始化数据
 */
class Data_init extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_suggest/Purchase_suggest_model');
        $this->load->model('purchase_suggest/Purchase_demand_lock');

        $this->_java_access_taken = getOASystemAccessToken();
    }

    /**
     * data_init/init_demand_had_purchase_amount
     */
    public function init_demand_had_purchase_amount(){
        $this->load->model('purchase_suggest/Purchase_demand_model');

        $max_id = $this->rediss->getData('init_demand_had_purchase_amount');
        $max_id = $max_id?$max_id:0;
        $max_id = intval($max_id);

        $sql = "SELECT A.id,A.suggest_demand,B.`demand_number`,B.`confirm_amount`,A.`demand_number` AS demand_number2
                FROM `pur_purchase_demand` AS A
                INNER JOIN `pur_purchase_order_items` AS B ON A.`suggest_demand`=B.demand_number
                WHERE A.`suggest_demand`<>'' 
                AND A.`suggest_demand` IS NOT NULL 
                AND A.id> $max_id 
                ORDER BY id ASC
                LIMIT 1000";

        echo $sql."<br/><br/>\n\n";

        $list = $this->db->query($sql)->result_array();
        if(empty($list)) exit('数据执行完毕');


        foreach($list as $value){

            $this->Purchase_demand_model->apportionPurchaseAmount($value['demand_number'],$value['confirm_amount']);
            $max_id = $value['id'];
        }


        $this->rediss->setData('init_demand_had_purchase_amount',$max_id);


        // 更新是否生成采购单
        $sql = "UPDATE pur_purchase_demand,pur_purchase_order_items
            SET pur_purchase_demand.`is_create_order`=1
            WHERE pur_purchase_demand.`is_create_order`=0 
            AND pur_purchase_demand.`suggest_demand`=pur_purchase_order_items.demand_number
            AND pur_purchase_demand.`suggest_demand` <> ''
            AND pur_purchase_demand.`suggest_demand` IS NOT NULL
            AND pur_purchase_demand.id<=$max_id";
        $this->db->query($sql);

        echo $sql."<br/><br/>\n\n";

        echo 'sss';exit;
    }

}
