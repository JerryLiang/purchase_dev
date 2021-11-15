<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * Created by PhpStorm.
 * 推送备货单数据到门户系统
 * User: luxu
 * Date: 2020/9/2 09 36
 */
class Purchase_gateways_api extends MY_API_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 推送订单实际状态全部到货
     *  部分到货不等待剩余和订单完结时间 到门户系统
     * @author:luxu
     * @time:2020/9/2
     * 每10分钟跑一次
     * 门户系统接口地址：http://dp.yibai-it.com:33344/web/#/118?page_id=17573
     **/
    public function pushPurchaseToGateways(){

        try{

            $flag =  isset($_GET['tag'])?$_GET['tag']:NULL;

            if(NULL == $flag) {

                $totalQuery = " SELECT orders.id as ordersId FROM pur_purchase_order AS orders 
                            WHERE orders.is_gateway=1 AND orders.purchase_order_status IN (9,11,14)
                         AND purchase_status_push_gateway=0 ";
            }else if($flag = 'test'){
                $totalQuery = " SELECT orders.id as ordersId FROM pur_purchase_order AS orders 
                            WHERE  orders.purchase_order_status IN (9,11,14)
                         AND purchase_status_push_gateway=0 AND purchase_number='".$_GET['purchase_number']."'";

            }
            $ordersIds = $this->db->query($totalQuery)->result_array();
            $limit  = 100;
            $result = array_chunk($ordersIds,$limit);
            $access_taken  = getOASystemAccessToken();
            $url           = getConfigItemByName('api_config','purchase','pushActualStatus');

            $url .='?access_token='.$access_taken;

            $header        = array('Content-Type: application/json');
            foreach($result as $result_key=>$result_value){

                $ids = array_column($result_value,"ordersId");
                $datas = $this->db->from("purchase_order")->where_in("id",$ids)->select("purchase_number AS purchaseNumber,completion_time AS endTime,purchase_order_status AS purchaseOrderActualStatus")
                    ->get()->result_array();
                $send_data = [];
                foreach ($datas as &$val){
                    if($val['endTime'] == '0000-00-00 00:00:00')continue;
                    $send_data[] = $val;
                }
                $result        = getCurlData($url,json_encode($send_data,JSON_UNESCAPED_UNICODE),'post',$header);
                $result = json_decode($result,True);
                if($result['code'] == 200){

                    $updateData['purchase_status_push_gateway'] = 1;

                    $this->db->where_in("id",$ids)->update("purchase_order",$updateData);
                }
            }





        }catch ( Exception $exp){

            echo $exp->getMessage();
        }
    }


}