<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * 订单操作控制器
 * User: Jolon
 * Date: 2019/12/20 10:00
 */
class Purchase_order_api extends MY_API_Controller {

    public function __construct(){
        parent::__construct();

        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_new_model');
        $this->load->model('warehouse/Warehouse_model');

    }

    /**
     * 自动刷新 采购单完结时间
     * @url http://pms.yibainetwork.com:81/Purchase_order_api/auto_update_order_completion_time
     */
    public function auto_update_order_completion_time(){
        $purchase_number = $this->input->get_post('purchase_number');// 可以针对指定订单进行操作

        if(!empty($purchase_number)){
            $order_list = [ 0 => ['purchase_number' => $purchase_number]];
        }else{
            $order_list = $this->purchase_order_model
                ->purchase_db
                ->query(" SELECT purchase_number 
                     FROM pur_purchase_order 
                     WHERE (completion_time ='0000-00-00 00:00:00' or completion_time='2020-01-01 00:00:00') 
                     AND purchase_order_status IN(9,11,14) LIMIT 10000")
                ->result_array();
        }

        if(empty($order_list)) exit('没有需要处理的数据');

        foreach($order_list as $value){
            $purchase_number = $value['purchase_number'];

            $max_time = $this->purchase_order_model
                ->purchase_db
                ->query(" 
                        SELECT IFNULL(MAX(operate_time),'0000-00-00 00:00:00') as operate_time
                        FROM `pur_operator_log` 
                        WHERE pur_operator_log.`record_number`='{$purchase_number}'
                        AND pur_operator_log.record_type='PURCHASE_ORDER' 
                        AND pur_operator_log.content='变更采购单状态'
                        AND (
                            pur_operator_log.content_detail LIKE '%【全部到货】' 
                            OR pur_operator_log.content_detail LIKE '%【部分到货不等待剩余到货】' 
                            OR pur_operator_log.content_detail LIKE '%【已作废订单】'
                        )
	            ")
                ->row_array();

            // 从 取消未到货、报损、入库记录里面获取最后一条数据
            if(isset($max_time['operate_time']) and $max_time['operate_time'] == '0000-00-00 00:00:00'){
                // 最近一条取消未到货记录的审核时间
                $cancel_info = $this->purchase_order_model
                    ->purchase_db->select('max(poc.audit_time) as audit_time')
                    ->from('purchase_order_cancel as poc')
                    ->join('purchase_order_cancel_detail as pocd','poc.id=pocd.cancel_id','inner')
                    ->where_in('poc.audit_status',[CANCEL_AUDIT_STATUS_CFYSK,CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC]) // 50.审核通过
                    ->where('pocd.purchase_number',$purchase_number)
                    ->get()
                    ->row_array();
                $max_time_1 = isset($cancel_info['audit_time'])?strtotime($cancel_info['audit_time']):'0';

                $warehouse_results_info = $this->purchase_order_model
                    ->purchase_db->select('max(instock_date) as instock_date')
                    ->from('warehouse_results')
                    ->where('purchase_number',$purchase_number)
                    ->get()
                    ->row_array();
                $max_time_2 = isset($warehouse_results_info['instock_date'])?strtotime($warehouse_results_info['instock_date']):'0';

                $report_loss = $this->purchase_order_model
                    ->purchase_db->select('max(audit_time) as audit_time')
                    ->from('purchase_order_reportloss')
                    ->where('pur_number',$purchase_number)
                    ->where('status',REPORT_LOSS_STATUS_FINANCE_PASS)
                    ->get()
                    ->row_array();
                $max_time_3 = isset($report_loss['audit_time'])?strtotime($report_loss['audit_time']):'0';

                // 求最大时间
                $max_time = null;
                if($max_time_1 > $max_time_2 and $max_time_1 > $max_time_3){
                    $max_time = $max_time_1;
                }elseif($max_time_2 > $max_time_3){
                    $max_time = $max_time_2;
                }else{
                    $max_time = $max_time_3;
                }
                if(empty($max_time)){// 都没有则取当天时间的 零点
                    $max_time = strtotime(date('Y-m-d'));
                }

                $max_time = date('Y-m-d H:i:s',$max_time);
            }else{
                $max_time = $max_time['operate_time'];
            }

            if($max_time){
                $this->purchase_order_model
                    ->purchase_db
                    ->query("UPDATE pur_purchase_order SET completion_time='{$max_time}' where purchase_number='{$purchase_number}' LIMIT 1");
            }
        }

        exit('success');
    }

    /*
     *初始化备货单数据
     */
    public function set_label_order_info()
    {

        $s= $this->load->model('supplier_joint_model');

        //要写入标签信息的数据
        $purchase_items = $purchase_items=$this->purchase_order_model->purchase_db->select(
            'sp.demand_number,
            od.purchase_number,
            s.destination_warehouse,
               oi.sku,
                oi.confirm_amount,
                oi.purchase_unit_price,
                od.purchase_order_status,
                od.plan_product_arrive_time,
                od.shipment_type,od.supplier_code,od.supplier_name,od.shipment_type,od.audit_time,oi.label_pdf,oi.barcode_pdf'
        )
            ->from('purchase_order_items oi')
            ->join('purchase_suggest_map sp','sp.sku=oi.sku and sp.purchase_number=oi.purchase_number','left')
            ->join('purchase_suggest s','s.demand_number=sp.demand_number','left')
            ->join('purchase_order od','od.purchase_number=oi.purchase_number','left')
            ->where('s.purchase_type_id',2)
            ->where('od.audit_time>=','2020-02-03')
            ->where('od.audit_time<=','2020-03-09 23:59:59') ->get()->result_array();
    /*    `label_pdf` varchar(500) NOT NULL DEFAULT '' COMMENT -,
  `barcode_pdf` varchar(500) NOT NULL DEFAULT '' COMMENT '工厂直发码',*/
        if (!empty($purchase_items)) {
            foreach ($purchase_items as $item) {
                $demand_info = $this->purchase_order_model->purchase_db->select('*')->where('demand_number',$item['demand_number'])->get('purchase_label_info')->row_array();
                if (!empty($demand_info)) continue;
                $unvalid_supplier = $this->supplier_joint_model->isValidSupplier(array($item['purchase_number']));
                $unvalid_supplier = ($unvalid_supplier==true?1:2);//是否开启门户系统

                $insert = [
                    'purchase_number'=>$item['purchase_number'],
                    'sku'=>$item['sku'],
                    'demand_number'=>$item['demand_number'],
                    'supplier_code'=>$item['supplier_code'],
                    'supplier_name'=>$item['supplier_name'],
                    'order_time'=>$item['audit_time'],
                    'destination_warehouse'=>$item['destination_warehouse'],
                    'enable'=>$unvalid_supplier,
                    'shipment_type'=>$item['shipment_type'],
                    'label'=>$item['label_pdf'],
                    'barcode'=>$item['barcode_pdf']
                ];
                $re = $this->purchase_order_model->purchase_db->insert('purchase_label_info', $insert);


            }

        }

    }


    //将3月1号，审核通过的订单信息推送门户系统

    public function send_provider_info_list()
    {


        $order_list = $this->purchase_order_model->purchase_db->select('*')->where_not_in('purchase_order_status',[1,3,14])->where('audit_time>=','2020-03-01')->get('purchase_order')->result_array();

        if (count($order_list)>0) {
            $offset = 0;
            $query = array_slice($order_list, 0,200);
            while(!empty($query)){
                $this->send_provider_info($query);
                $offset +=200;
                $query = array_slice($order_list, $offset,200);
                usleep(500);

            }

        }
        echo 'done success';

    }

    public function send_provider_info($data_list)
    {
        if ($data_list) {
            $send_data = [];
            foreach ($data_list as $dataInfo ) {
                $temp = [];

                if (empty($dataInfo['purchase_number'])||empty($dataInfo['warehouse_code'])) continue;

                $userInfo  = $this->purchase_order_model->get_access_purchaser_information($dataInfo['purchase_number']);
                $warehouse_address = $this->warehouse_model->get_warehouse_address($dataInfo['warehouse_code']);


                 if ($warehouse_address) {
                     $warehouse_address_complete = $warehouse_address[0]['province_text'] . $warehouse_address[0]['city_text'] . $warehouse_address[0]['area_text'] . $warehouse_address[0]['town_text'] . $warehouse_address[0]['address'];
                     $contact_number = $userInfo['iphone'];
                     $contacts = $userInfo['user_name'];
                     // $val['warehouse_code'] =='shzz' or $val['warehouse_code']=='AFN'
                     if (!empty($warehouse_address[0]['contact_number'])) {
                         $contact_number = $warehouse_address[0]['contact_number'];
                     }
                     if (!empty($warehouse_address[0]['contacts'])) {
                         $contacts = $warehouse_address[0]['contacts'];
                     }
                     $temp['purchaseNumber'] = $dataInfo['purchase_number'];
                     $temp['address'] = $warehouse_address_complete ?? '';
                     $temp['contact_number'] = $contact_number ?? '';
                     $temp['contacts'] = $contacts ?? '';
                     $send_data[] = $temp;
                 }

            }

            $url = SMC_JAVA_API_URL . '/provider/purPush/pushAddress';
            $header = array('Content-Type: application/json');
            $access_taken = getOASystemAccessToken();
            $url = $url . "?access_token=" . $access_taken;
            $result = getCurlData($url, json_encode($send_data, JSON_UNESCAPED_UNICODE), 'post', $header);
            var_dump($result);



        }


    }

    /**
     * 崔供应商改价  每隔30分钟执行
     */
    public function urge_supplier_change_price()
    {
        $hour = [];
        $day = [];
        $start = date("YmdHis");
        try{
            $data = $this->purchase_order_new_model->get_one_hour_order_data();
            $hour = $data;
            if(!$data || count($data) == 0 || !is_array($data))exit;
            $data = count($data) > 100?array_chunk($data, 100): [$data];
            foreach ($data as $val){
                $this->purchase_order_new_model->send_official_order_data($val, 2);
            }
        }catch (Exception $e){}

        $mid = date("YmdHis");
        // 检测如果3天没有物流信息，则催发货。
        try{
            $push_time = date("Hi");
            $push_time = (int)$push_time;
            $is_open = $this->purchase_order_new_model->get_send_lock_key();
            if($is_open && ($push_time >= 859 && $push_time < 929) || ($push_time >= 1359 && $push_time < 1429)){
                $fh = $this->purchase_order_new_model->get_three_day_order_data();
                $day = $fh;
                if(count($fh) > 0){
                    $this->purchase_order_new_model->send_official_order_data($fh);
                }
            }
        }catch (Exception $e){}
        $end = date("YmdHis");

        operatorLogInsert(
            [
                'id'      => date("YmdHis"),
                'type'    => 'urge_supplier_change_price',
                'content' => '催发货催改价-定时任务',
                'detail'  => count($hour)."######".count($day)."......【time】start:{$start},middle:{$mid},end:{$end}",
            ]
        );
    }

    /**
     * 设置推送开关
     */
    public function set_send_lock_key()
    {
        $handle = $this->input->get_post('handle');
        $is_open = $this->purchase_order_new_model->set_send_lock_key($handle);
        echo json_encode($is_open);
    }

    /**
     * 更新门户回货数最新的时间
     */
    public function update_supplier_quantity_time()
    {
        $data = $this->Purchase_order_new_model->get_supplier_instock_data();
        if(!$data || count($data) == 0){
            echo "没有要处理的数据！";
        }
        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $url = getConfigItemByName('api_config','purchase','shipmentsqty');
        $url = $url."?access_token=".$access_taken;
        foreach ($data as $val){
            if(!SetAndNotEmpty($val, 'purchase_number') || !SetAndNotEmpty($val, 'sku'))continue;

            $post_data = ['purchaseNumber'=>$val['purchase_number'],'sku'=>$val['sku']];
            $result = getCurlData($url,json_encode($post_data,JSON_UNESCAPED_UNICODE),'post',$header);
            $result = json_decode($result,True);
            if(isset($result['code']) && $result['code'] == 200){
                $last = end($result['data']);
                if(SetAndNotEmpty($last, 'affirmShipmentsTime')){
                    $this->Purchase_order_new_model->update_supplier_instock_data($val['purchase_number'], $val['sku'], $last['affirmShipmentsTime']);
                }
            }
        }
    }

    /**
     * 门户回货更新
     */
    public function update_supplier_quantity()
    {
        $data = $this->input->get_post('data');
        $data_temp = [];
        try{
            $data_temp = json_decode($data, true);
        }catch (Exception $e){}
        if(empty($data_temp) || !is_array($data_temp)){
            echo json_encode([
                "status"  => 0,
                "errorMess"   => "提交数据为空或数据错误！",
                "request" => $data
            ]);
        }
        $res = $this->purchase_order_new_model->update_supplier_quantity($data_temp);
        echo json_encode($res);
    }


}