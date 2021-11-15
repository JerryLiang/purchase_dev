<?php
/**
 * 采购系统对接计划系统
 * User: Jolon
 * Date: 2019/10/20 10:00
 */
class Plan_system_model extends Purchase_model {

    public function __construct(){
        parent::__construct();
    }

    /**
     * 推送采购单信息到计划系统
     * @return array|bool
     */
    public function push_purchase_order_info_to_plan(){
        if(PUSH_PLAN_SWITCH == false) return true;

        $return         = ['code' => true,'success_list' => [],'error_list' => []];
        $operator_key   = 'push_purchase_order_info_to_plan';
        $len            = $this->rediss->llenData($operator_key);
        if($len <= 0) return $return;

        // 分页处理器
        $page_size  = 30;// 不要超过 50个
        $page_total = ceil($len / $page_size);
        $page_now   = ($page_total >= 50)?50:$page_total;

        for($page = 0;$page < $page_now;$page ++){
            $purchase_number_list = [];
            for($i = 0;$i < $page_size;$i ++){
                $purchase_number = $this->rediss->rpopData($operator_key);
                if(empty($purchase_number)) break;
                $purchase_number_list[] = $purchase_number;
            }

            if(empty($purchase_number_list)) break;

            //查询推送的数据 备货单维度
            $purchase_items = $this->get_push_data($purchase_number_list);

            if(!empty($purchase_items)){
                $result = $this->java_push_purchase_order($purchase_items,1);
                $return['success_list'] = array_merge($return['success_list'],$result['success_list']??[]);
                $return['error_list'] = array_merge($return['error_list'],$result['error_list']??[]);
            }
        }

        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$operator_key);

        return $return;
    }

    /**
     * 查询推送的数据 采购单查询出对应的备货单数据
     * @author Manson
     * @param $purchase_number_list
     * @return array
     */
    public function get_push_data($number_list,$type=1){
        //以备货单为维度推送计划系统
        $select_field = "pd.demand_number,
                s.suggest_status,
                pd.demand_status,
                s.warehouse_code,
                pd.had_purchase_amount,
                oi.purchase_unit_price,
                oi.es_shipment_time,
                oi.plan_arrive_time,
                oi.sku,
                oi.purchase_number,
                rm.arrival_qty,
                od.shipment_type,
                od.purchase_order_status,
                od.purchase_type_id,
                od.create_time,
                od.is_drawback,
                od.pertain_wms,
                od.supplier_code,
                s.cancel_reason";

        $query = $this->purchase_db->from('purchase_order_items oi')
            ->join('purchase_suggest s', 's.demand_number=oi.demand_number', 'inner')
            ->join('purchase_order od', 'od.purchase_number=oi.purchase_number', 'inner')
            ->join('pur_purchase_demand pd', 'pd.suggest_demand=oi.demand_number', 'left')
            ->join('warehouse_results_main rm', 'rm.purchase_number=oi.purchase_number and rm.sku=oi.sku', 'left')
            ->where('s.source_from', 1);//数据来源于计划系统
        if ($type == 1){
            $query->where_in('oi.purchase_number', $number_list);

        }elseif ($type == 2){
            $select_field = $select_field.',stl.new_demand_number,stl.plan_qty,stl.shipment_type new_shipment_type';
            $query->join('shipment_track_list stl','s.demand_number = stl.demand_number','left');

            $query->where_in('stl.new_demand_number', $number_list);//根据新备货单号查询数据
        }elseif($type == 3){
            $select_field = $select_field.',oi.plan_arrive_time,oi.es_shipment_time';
            $query->where_in('oi.purchase_number', $number_list);

        }
        $query->select($select_field);
        $purchase_items = $query->get()->result_array();
        return $purchase_items;
    }


    /**
     * 采购单状态变更时推送
     * http://192.168.71.156/web/#/87?page_id=3466
     * @author Manson
     * @param $purchase_items
     * @param int $type 1失败写入队列 2推送采购单作废 3推送二验交期 4等待到货的
     * @return bool
     */
    public function java_push_purchase_order($purchase_items,$type = 1,$time_map=[])
    {
        $temp_arr = [];
        $push_data = [
            'data_list' => ''
        ];
        if ($type == 1){
            $queue_key = 'push_purchase_order_info_to_plan';
        }elseif ($type == 4){
            $queue_key = 'push_waiting_arrival_info_to_plan';
        }else{
            $queue_key = '';
        }

        $map_line = [
            1   => 1,
            3   => 5,
            4   => 1,
            6   => 2,
            7   => 4,
        ];

        foreach($purchase_items as $item){
            //采购单状态为信息修改待审核,信息修改驳回  不回传信息给计划系统
            if (in_array($item['purchase_order_status'],[PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT])){
                $return['error_list'][$item['purchase_number']] = '采购单状态为信息修改待审核或信息修改驳回,不回传信息给计划系统';
                continue;
            }
            $sug = isset($map_line[$item['demand_status']])?$map_line[$item['demand_status']]:$item['demand_status'];
            $s_status = $item['purchase_type_id'] == 2 ? $item['suggest_status'] : $sug;
            $arr = [
                'business_line'       => $item['purchase_type_id'],//业务线
                'pur_sn'              => $item['demand_number'],//备货单号
                'warehouse_code'      => $item['warehouse_code'],//采购仓库
                'po_qty'              => $item['had_purchase_amount'],//确认采购数量改为需求单实际下单数量
                'sku'                 => $item['sku'],//SKU
                'purchase_number'     => $item['purchase_number'],//采购单号
                'po_state'            => $item['purchase_order_status'],//采购单状态
                'suggest_status'      => $s_status,//备货单状态
                'demand_status'       => $item['demand_status'],//需求单状态
                'pertain_wms'         => $item['pertain_wms'],//公共仓
                'order_time'          => $item['create_time'],//下单时间
                'is_drawback'         => $item['is_drawback'],//是否退税
                'purchase_unit_price' => $item['purchase_unit_price'],//采购单价
                'arrival_qty'         => $item['arrival_qty'],//到货数量
                'supplier_code'       => $item['supplier_code'],//供应商编码
                'cancel_reason'       => isset($item['cancel_reason'])?$item['cancel_reason']:'', // 作废原因
                'expect_arrived_date' => $item['plan_arrive_time'], // 预计到货时间
                'es_shipment_time'    => $item['es_shipment_time'], // 预计发货时间
            ];
            // 获取备货单不良品数量,入库量，到货量
            $warehouseResults = $this->purchase_db->from("warehouse_results_main")->where("sku",$item['sku'])
                ->where("purchase_number",$item['purchase_number'])->select("SUM(bad_qty) AS bad_qty,SUM(instock_qty) AS instock_qty,
                SUM(arrival_qty) AS arrival_qty")
                ->get()->row_array();
            if(!empty($warehouseResults)){

                $arr['bad_qty'] = $warehouseResults['bad_qty'];
                $arr['instock_qty'] = $warehouseResults['instock_qty'];
                $arr['arrival_qty'] = $warehouseResults['arrival_qty'];
            }else{
                $arr['bad_qty'] = $arr['instock_qty'] = $arr['arrival_qty'] = 0;
            }


            if(isset($item['new_demand_number']) && !empty($item['new_demand_number'])) {
                $exists = $this->purchase_db->from("shipment_track_list")->where("new_demand_number", $item['new_demand_number'])->get()->row_array();

                if (!empty($exists)) {

                    $arr['po_qty'] = $exists['temp_plan_qty']; // 获取发运表里面的计划数量
                }
            }

            if ($type == 2){//采购单作废

                $arr['suggest_status'] = SUGGEST_STATUS_NOT_FINISH;
                $arr['po_state'] = PURCHASE_ORDER_STATUS_CANCELED;

            }
            elseif ($type == 3){//二验交期  以发运跟踪的数据进行推送

                $arr['expect_arrived_date'] = $time_map['es_arrival_time'];//预计到货时间
                $arr['es_shipment_time']    = $time_map['es_shipment_time'];//预计发货时间
                $arr['shipment_type']       = $item['new_shipment_type'];//发运类型
                $arr['pur_sn']              = $item['new_demand_number'];//备货单号

            }elseif ($type == 4){//等待到货
                $arr['shipment_type']    = $item['shipment_type'];//发运单类型
                $arr['expect_arrived_date']    = ($item['plan_arrive_time'] != '0000-00-00 00:00:00')?date("Y-m-d",strtotime($item['plan_arrive_time'])):'0000-00-00';//预计到货时间
                $arr['es_shipment_time']    =$arr['expect_arrived_date'];
                $shipmentTypes = $this->purchase_db->from("purchase_order")->where("purchase_number",$arr['purchase_number'])
                    ->select("shipment_type")->get()->row_array();
                if(isset($shipmentTypes['shipment_type']) && $shipmentTypes['shipment_type'] == 1){

                    $arr['es_shipment_time'] = $arr['expect_arrived_date'];
                }
            }

            array_push($temp_arr,$arr);
        }

        $push_data['data_list'] = json_encode($temp_arr);
        $push_data              = json_encode($push_data);
        $access_token           = getOASystemAccessToken();

        //推送计划系统
        $url    = getConfigItemByName('api_config', 'java_system_plan', 'push_purchase_order');
        $url    = $url.'?access_token='.$access_token;
        $header = ['Content-Type: application/json'];
        $res    = getCurlData($url, $push_data, 'POST', $header);
        $result = json_decode($res, true);

        $this->load->helper('common');
        operatorLogInsert(
            [
                'id'      => '',
                'type'    => 'PUSH_PURCHASE_INFO_TO_PLAN',
                'content' => '推送采购单信息到计划系统',
                'detail'  => sprintf('请求参数:%s 接口返回:%s', $push_data, $res)
            ]
        );

        if (!empty($queue_key)){

            $purchase_number_result_list = array_unique(array_column($purchase_items,'purchase_number'));
            foreach($purchase_number_result_list as $purchase_number){
                if (isset($result['code']) && $result['code'] == 200) {

                    $return['success_list'][$purchase_number] =  'OK';

                }else{//失败的存入队列
                    $this->rediss->lpushData($queue_key,$purchase_number);
                    $return['error_list'][$purchase_number] = '推送计划返回信息, '.$result['msg']??'接口未反应';
                }
            }
            return $return;
        }else{//直接返回成功失败

            // 执行结果解析-成功与失败
            if (isset($result['code']) && $result['code'] == 200) {

                return true;

            }else{

                return false;
            }
        }

    }


    /**
     * 采购系统进行[二验交期确认]后 推送预计发货日期,预计到货时间
     * @author Manson
     */
    public function java_push_delivery_confirmation($demand_number_list,$time_map)
    {
        //查询推送的数据 备货单维度
        $purchase_items = $this->get_push_data($demand_number_list,2);
        if(!empty($purchase_items)){
            return $this->java_push_purchase_order($purchase_items,3, $time_map);
        }else{
            return false;
        }
    }


    public function push_waiting_arrival_info_to_plan($purchase_number)
    {
        if (PUSH_PLAN_SWITCH == false) return true;
        //推送采购单信息至计划系统
        $this->rediss->select(0);
        $this->rediss->lpushData('push_waiting_arrival_info_to_plan', $purchase_number);
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_push_waiting_arrival_info_to_plan');
        return true;

    }


    /**
     * 推送采购单信息到计划系统(等待到货)
     * @return array|bool
     */
    public function sys_push_waiting_arrival_info_to_plan(){
        if(PUSH_PLAN_SWITCH == false) return true;

        $return         = ['code' => true,'success_list' => [],'error_list' => []];
        $operator_key   = 'push_waiting_arrival_info_to_plan';
        $len            = $this->rediss->llenData($operator_key);
        if($len <= 0) return $return;

        // 分页处理器
        $page_size  = 30;// 不要超过 50个
        $page_total = ceil($len / $page_size);
        $page_now   = ($page_total >= 50)?50:$page_total;

        for($page = 0;$page < $page_now;$page ++){
            $purchase_number_list = [];
            for($i = 0;$i < $page_size;$i ++){
                $purchase_number = $this->rediss->rpopData($operator_key);
                if(empty($purchase_number)) break;
                $purchase_number_list[] = $purchase_number;
            }

            if(empty($purchase_number_list)) break;

            //查询推送的数据 备货单维度
            $purchase_items = $this->get_push_data($purchase_number_list,3);

            if(!empty($purchase_items)){
                $result = $this->java_push_purchase_order($purchase_items,4);
                $return['success_list'] = array_merge($return['success_list'],$result['success_list']??[]);
                $return['error_list'] = array_merge($return['error_list'],$result['error_list']??[]);
            }
        }

        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$operator_key);

        return $return;
    }

    /**
     * 原有的推送采购单到计划系统的方式采用异步的模式，有缺陷。现在重新改造
     * @author:luxu
     * @time:2020/11/24
     **/
    public function new_push_waiting_arrival_info_to_plan($purchase_number = NULL){

        try{
           // if(PUSH_PLAN_SWITCH == false) return true;
            if( NULL != $purchase_number && !empty($purchase_number)){

                $updateQuery = $this->db;

                if( is_array($purchase_number)){

                    $updateQuery->where_in("purchase_number",$purchase_number);
                }else{
                    $updateQuery->where("purchase_number",$purchase_number);
                }

                $result = $updateQuery->update("purchase_order",['is_push_plan'=>1]);
            }
        }catch ( Exception $exp ){

            return False;
        }
    }

    /**
     *  需求：37849 发运类型变更的逻辑补充
     *  @author:luxu
     *  @time:2021年7月1号
     **/

    public function pms_to_data_plan($data){

        try{
            $token = getPlanSystemAccessToken();
            $result = createSignature($token);
            $result['token'] = $token;
            $result['username'] = PLAN_USER_NAME;
            $this->load->config('api_config', FALSE, TRUE);
            $erp_system = $this->config->item('plan_system');
            $url = $erp_system['push_data_url'];
            $pushData = [

                'token_info' => $result,
                'data' => $data
            ];
            //print_r($pushData);die();
            $header        = array('Content-Type: application/json');

            $result        = getCurlData($url,json_encode($pushData),'post',$header);
            $result = json_decode($result,True);
            if(isset($result['error_list']) && !empty($result['error_list'])){

                $errorMsg = '';
                foreach($result['error_list'] as $key=>$value){

                    $errorMsg .= $key.":".$value.",";
                }
                throw new Exception("预计到货时间推送计划系统失败:".$errorMsg);
            }

            return True;


        }catch ( Exception $exp ){
            echo $exp->getMessage();die();
            throw new Exception($exp->getMessage());
        }
    }

    /**
       需求：37936 提供接口给ＤＳＳ系统获取ＦＢＡ备货的ＳＫＵ
     * 需求背景：ＤＳＳ系统需要知道ＦＢＡ备货的有哪些ＳＫＵ，然后通知品控针对这些ＳＫＵ在入库之前，安排进行全检
       需求描述：计划系统推送到采购系统的需求单明细中，fba_purchase_qty 的数量＞0的，若这个需求单下单成功，采购单状态变为等待到货的时候，
     * @author:luxu
     * @time:2021年7月2号
     **/
    public function get_waiting_arrival_data($purchase_number){

        try{
            $query = $this->purchase_db->from("purchase_order_items");
            if(is_array($purchase_number)){

                $query->where_in("purchase_number",$purchase_number);
            }else{
                $query->where("purchase_number",$purchase_number);
            }

            $result = $query->select("sku")->get()->result_array();
            if(!empty($result)){
                $skus = array_column($result,'sku');
                $demand_datas = $this->purchase_db->from("purchase_demand")->where_in("sku",$skus)->where("fba_purchase_qty>",0)->select("sku,fba_purchase_qty")->get()->result_array();
                if(!empty($demand_datas)){

                    foreach($demand_datas as $key=>&$value){

                        $value['wating_arrval_time'] = date("Y-m-d H:i:s",time());
                    }

                    $this->purchase_db->insert_batch("purchase_sku_waiting_delivery",$demand_datas);
                }
            }


        }catch ( Exception $exp ){



        }
    }

}