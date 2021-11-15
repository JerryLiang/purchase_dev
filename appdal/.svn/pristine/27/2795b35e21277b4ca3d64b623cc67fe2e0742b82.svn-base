<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2020/4/14
 * Time: 16:22
 * 发运跟踪列表
 */

class Shipment_track_list_model extends Purchase_model
{
    protected $table_name = 'shipment_track_list';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
    }

    /**
     * 根据采购单号查询对应备货单号 写入发运跟踪列表
     * 1.计划系统的数据
     * 2.业务线:海外仓
     * 3.采购单状态:等待到货
     * @author Manson
     * @param $purchase_number
     * @return bool
     */
    public function add_shipment_data($purchase_number)
    {
        $result = $this->purchase_db->select('
        a.supplier_code, a.supplier_name, 
        b.product_img_url,
        c.purchase_number,c.sku,
        d.demand_number, d.plan_product_arrive_time, d.logistics_type, d.shipment_type,
        d.site, d.site_name, d.es_shipment_time, d.plan_product_arrive_time, d.destination_warehouse,b.confirm_amount')
            ->from('purchase_order a')
            ->join('purchase_order_items b','a.purchase_number = b.purchase_number','left')
            ->join('purchase_suggest_map c','b.purchase_number = c.purchase_number AND b.sku = c.sku AND c.map_status = 1','left')
            ->join('purchase_suggest d','c.demand_number = d.demand_number','left')
            ->where('a.purchase_number',$purchase_number)
            ->where('a.purchase_order_status',PURCHASE_ORDER_STATUS_WAITING_ARRIVAL)
            ->where('d.purchase_type_id',PURCHASE_TYPE_OVERSEA)//海外仓
            ->get()->result_array();

        if (!empty($result)){

            //仓库 code=>name
            $this->load->model('warehouse_model','',false,'warehouse');
            $warehouse_map = $this->warehouse_model->warehouse_code_to_name();

            foreach ($result as $key => $item){

                //是否存在
                $res = $this->purchase_db->select('*')
                    ->from($this->table_name)->where('demand_number',$item['demand_number'])
                    ->get()->result_array();
//                pr($res);exit;

                if (!empty($res)){
                    continue;
                }
                $insert_data[] = [
                    'new_demand_number' => $item['demand_number'],
                    'demand_number' => $item['demand_number'],
                    'purchase_number' => $item['purchase_number'],
                    'sku' => $item['sku'],
                    'shipment_type' => $item['shipment_type'],
                    'station_code' => $item['site'],
                    'station_name' => $item['site_name'],
                    'destination_warehouse_code' => $item['destination_warehouse'],
                    'destination_warehouse_name' => $warehouse_map[$item['destination_warehouse']]??'',
                    'es_shipment_time' => $item['es_shipment_time'],
                    'es_arrival_time' => $item['plan_product_arrive_time'],
                    'logistics_type' => $item['logistics_type'],
                    'supplier_code' => $item['supplier_code'],
                    'supplier_name' => $item['supplier_name'],
                    'product_image' => $item['product_img_url'],
                    'create_time' => date('Y-m-d H:i:s'),
                    'plan_qty' =>$item['confirm_amount'],
                    'push_to_wms' => 2,//待推送
                ];
            }

            if (!empty($insert_data)){
                $this->purchase_db->insert_batch($this->table_name,$insert_data);
            }
        }
    }



    /**
     * 备货单号+发运类型 确认一条记录
     * @author Manson
     * @param $demand_number_list
     * @return array
     */
    public function get_shipment_track_info($demand_number_list)
    {
        $data = [];
        $result = $this->purchase_db->select('id,demand_number,new_demand_number,
        shipment_type, destination_warehouse_code, plan_qty, logistics_type, 
        sku, purchase_number, station_code, station_name,
        destination_warehouse_code,
        destination_warehouse_name,
        es_shipment_time,
        es_arrival_time,
        supplier_code,
        supplier_name,
        product_image')
            ->from($this->table_name)
            ->where_in('demand_number',$demand_number_list)
            ->where('is_del',2)
            ->get()->result_array();
        foreach ($result as $key => $item){
            $data[$item['demand_number']][$item['shipment_type']] = $item;
        }

        return $data;
    }


    /**
     * 根据备货单号查询中转仓数量
     * @author Manson
     * @param $demand_number_list
     * @return array
     */
    public function get_transit_qty($demand_number_list)
    {
        $demand_number_list = implode("','",$demand_number_list);

        $sql = "SELECT SUM(plan_qty) transit_qty,demand_number FROM pur_shipment_track_list 
WHERE shipment_type = 2 AND is_del = 2 AND demand_number IN ('{$demand_number_list}') GROUP BY demand_number;";

        $result = $this->purchase_db->query($sql)->result_array();

        return array_column($result,'transit_qty','demand_number');
    }

    /**
     * 最新版本: 没有自动审核的逻辑, 接收到的数据都是待审核状态
     * @author Manson
     */
    public function change_shipment_info($params)
    {
        if (empty($params)){
            throw new Exception('数据为空');
        }

        //初始化
        $success_list = $push_data = $insert_shipment_track_data = $update_shipment_track_data = $update_is_del_data = $temp_update =  $insert_log_data = $return = $demand_number_map = $new_demand_number_map = $auto_reject_data = $del_shipment_track_data = $del_wait_audit_data = [];
        define('SHIPMENT_TYPE_FACTORY',1);//工厂直发
        define('SHIPMENT_TYPE_EXCHANGE',2);//中转仓发货
        //返回结果格式
        $return = [
            'status' => 0,
            'errorMess' => '',
            'success_list' => [],
            'fail_list' => [],
        ];
        //必填项
        $required_fields = [
            'id',
            'sku',
            'demand_number',
            'new_demand_number',
            'shipment_type',
            'destination_warehouse_code',
            'logistics_type',
            'plan_update_time',
            'split_status',
            'rerun_batch'
        ];
        //检验参数
        foreach ($params as $key => $item){
            $is_ok = 1;
            foreach ($item as $k => $val){
                if (in_array($k,$required_fields) && empty($val)){

                    $return['fail_list'][$item['id']] = $k.'参数不能为空';
                    $is_ok = 0;
                    break;
                }
            }

            //发运类型异常
            if (isset($item['shipment_type']) && !in_array($item['shipment_type'],[SHIPMENT_TYPE_FACTORY,SHIPMENT_TYPE_EXCHANGE])){
                $is_ok = 0;
                $return['fail_list'][$item['id']] = '发运类型异常';
                continue;
            }

            if ($is_ok == 0){
                unset($params[$key]);
            }

            $handle_data[$item['demand_number']][] = $item;
        }

        $demand_number_list = array_filter(array_column($params,'demand_number'));
        $new_demand_number_list = array_filter(array_column($params,'new_demand_number'));
        unset($params);
        //根据原始备货单查询旧信息
        if (!empty($demand_number_list)){
            $shipment_info_map = $this->get_shipment_track_info($demand_number_list);
            //根据原备货单号查询中转仓数量
//            $transit_qty_map = $this->get_transit_qty($demand_number_list);
        }

        $this->load->helper('status_order');

        //仓库 code=>name
        $this->load->model('warehouse_model','',false,'warehouse');
        $warehouse_map = $this->warehouse_model->warehouse_code_to_name();

        //物流类型 code=>name
        $this->load->model('warehouse/Logistics_type_model');
        $logistics_map = $this->Logistics_type_model->logistics_code_to_name();


        foreach ($handle_data as $demand_number => $row){

            //判断是否需要人工审核, 中转仓数量减少, 原单下所有的单都需要进行审核
            $transit_qty = 0;
            $error_message = '';
//            foreach ($row as $key => $item){
//                if ($item['shipment_type'] == 2){
//                    $transit_qty = $item['plan_qty'];
//                }
//            }
//
//
//            $old_transit_qty = $transit_qty_map[$demand_number]??0;
//
//            if ($old_transit_qty > $transit_qty){
//                $audit_status = 1;//待采购审核
//            }else{
//                $audit_status = 5;//自动审核通过
//            }
            $audit_status = 1;//待采购审核

            foreach ($row as $key => $item) {
                $is_update = $is_insert = false;//初始化

                //其中一条数据有误,原单下所有的单都要返回失败
                if (!empty($error_message)){
                    $return['fail_list'][$item['id']] = $error_message;
                    continue;
                }

                //如果是拆单的那么计划系统要将拆好的2条数据同时推过来
                if ($item['demand_number'] != $item['new_demand_number'] && count($row) != 2){
                    $error_message = '拆单的数据要一起推送过来';
                    $return['fail_list'][$item['id']] = $error_message;
                    continue;
                }

                if ($item['demand_number'] == $item['new_demand_number'] && count($row) != 1){
                    $error_message = '没拆单的只能有一条数据';
                    $return['fail_list'][$item['id']] = $error_message;
                    continue;
                }

                //根据原备货单的维度进行自动驳回
                if (isset($auto_reject_data['rerun_batch'])){
                    $auto_reject_data[$item['rerun_batch']][] = $item;
                    continue;
                }


                //不存在发运跟踪表的自动审核驳回
                if (!isset($shipment_info_map[$item['demand_number']])) {
                    $auto_reject_data[$item['rerun_batch']][] = $item;
                    continue;
                }

                //------------------------ 注意: 上面的要先判断,过滤异常数据 -----------------------

                //如果是已经存在的发运类型
                if (isset($shipment_info_map[$item['demand_number']][$item['shipment_type']])){
                    $old_data = $shipment_info_map[$item['demand_number']][$item['shipment_type']]??[];
                    $is_update = true;
                }else{
                    //获取当前存在的发运类型的数据
                    if (isset($shipment_info_map[$item['demand_number']][SHIPMENT_TYPE_FACTORY])){
                        $old_data = $shipment_info_map[$item['demand_number']][SHIPMENT_TYPE_FACTORY]??[];
                    }else{
                        $old_data = $shipment_info_map[$item['demand_number']][SHIPMENT_TYPE_EXCHANGE]??[];
                    }
                }

                if (empty($old_data)){
                    $error_message = '没查询到对应的备货单数据';
                    $return['fail_list'][$item['id']] = $error_message;
                    continue;
                }



                if ($audit_status == 5){//自动审核通过, 删除旧数据, 新增新数据
                    //新增数据
                    $is_insert = true;
                    $temp_plan_qty = $item['plan_qty'];
                    $plan_qty = $item['plan_qty'];
                    $is_del = 2;

                    //删除旧数据
                    $del_shipment_track_data[$item['demand_number']] = 1;

                    //回传计划系统审核结果
                    $push_data[] = [
                        'demand_number' => $item['new_demand_number'],
                        'sku' => $item['sku'],
                        'can_change_type' => 1,
                        'rerun_batch' => $item['rerun_batch']
                    ];
                }elseif ($audit_status ==  1){//待采购审核
                    //新增数据
                    $is_insert = true;
                    $temp_plan_qty = $item['plan_qty'];
                    if ($is_update){
                        $plan_qty = $old_data['plan_qty'];
                    }else{
                        $plan_qty = 0;
                    }
                    $is_del = 1;//待审核的不显示

                    //更新数据
                    $update_shipment_track_data[] = [
                        'id' => $old_data['id'],
                        'audit_status' => 1,
                        'rerun_batch' => $item['rerun_batch']
                    ];

                    //返回计划系统推送直发规则成功
                    $success_list[] = $item['id'];

                    $del_wait_audit_data[$item['demand_number']] = 1;
                }else{
                    $error_message = '数据异常,无法确认审核状态';
                    $return['fail_list'][$item['id']] = $error_message;
                    continue;
                }


                if($is_insert){
                    $insert_shipment_track_data[] = [
                        'demand_number' => $item['demand_number'],
                        'new_demand_number' => $item['new_demand_number'],
                        'shipment_type' => $item['shipment_type'],//发运类型
                        'plan_qty' => $plan_qty,
                        'temp_plan_qty' => $temp_plan_qty,
                        'logistics_type' => $item['logistics_type'],
                        'plan_update_time' => $item['plan_update_time'],
                        'purchase_number' => $old_data['purchase_number'],
                        'sku' => $old_data['sku'],
                        'station_code' => $old_data['station_code'],
                        'station_name' => $old_data['station_name'],
                        'destination_warehouse_code' => $old_data['destination_warehouse_code'],
                        'destination_warehouse_name' => $old_data['destination_warehouse_name'],
                        'es_shipment_time' => $old_data['es_shipment_time'],
                        'es_arrival_time' => $old_data['es_arrival_time'],
                        'supplier_code' => $old_data['supplier_code'],
                        'supplier_name' => $old_data['supplier_name'],
                        'product_image' => $old_data['product_image'],
                        'create_time' => date('Y-m-d H:i:s'),
                        'audit_status'=> $audit_status,
                        'is_del' => $is_del,
                        'rerun_batch' => $item['rerun_batch'],
                        'split_status' => $item['split_status'],
                    ];
                }

                if ($is_update){
                    //直接更新的字段,不需要审核,记录日志

                    //修改目的仓
                    if ($item['destination_warehouse_code'] != $old_data['destination_warehouse_code']){
                        $insert_log_data[] = [
                            'new_demand_number' => $item['new_demand_number'],
                            'type' => 'change_destination_warehouse_code',
                            'old_data' => $warehouse_map[$old_data['destination_warehouse_code']]??$old_data['destination_warehouse_code'],
                            'new_data' => $warehouse_map[$item['destination_warehouse_code']]??$item['destination_warehouse_code'],
                            'operation_time' => date('Y-m-d H:i:s'),
                            'operator' => '计划系统',
                        ];
                    }
                }
            }
        }

        //组织自动驳回的数据
        foreach ($auto_reject_data as $key => $item){
            foreach ($item as $k => $v){
                $push_data[] = [
                    'demand_number' => $v['new_demand_number'],
                    'sku' => $v['sku'],
                    'can_change_type' => 2,//不同意更改发运类型
                    'rerun_batch' => $key
                ];
            }
        }



//pr($return);
//pr($update_shipment_track_data);
//pr($insert_shipment_track_data);
//pr($insert_log_data);
//pr($push_data);exit;
        //数据库写入更新
        $this->purchase_db->trans_begin();
        if(!empty($del_shipment_track_data)){
            $del_shipment_track_data = array_keys($del_shipment_track_data);
            $this->purchase_db->where_in('demand_number',$del_shipment_track_data)->delete($this->table_name);
        }
        if(!empty($del_wait_audit_data)){
            $del_wait_audit_data = array_keys($del_wait_audit_data);
            $this->purchase_db->where_in('demand_number',$del_wait_audit_data)->where('is_del',1)->delete($this->table_name);
        }

        if (!empty($update_shipment_track_data)){
            $this->purchase_db->update_batch($this->table_name,$update_shipment_track_data,'id');
        }
        if (!empty($insert_shipment_track_data)){
            $this->purchase_db->insert_batch($this->table_name,$insert_shipment_track_data);
        }
        if (!empty($insert_log_data)){
            $this->purchase_db->insert_batch('shipment_demand_info_update_log',$insert_log_data);
        }

        //更新推送wms状态
//        $this->update_push_to_wms_status($demand_number_list);


        //推送审核结果到计划系统
        if (!empty($push_data)){
            $result = $this->push_audit_result_to_plan($push_data);
            $result = json_decode($result, True);

            if (isset($result['status']) && $result['status'] == 1) {

            }else{
                $this->purchase_db->trans_rollback();
                throw new Exception("审核结果同步计划系统失败");
            }
        }

        if($this->purchase_db->trans_status() === FALSE){
            $this->purchase_db->trans_rollback();
            throw new Exception('处理数据失败');
        }else{
            $this->purchase_db->trans_commit();
            $return['status'] = 1;
            $return['success_list'] = $success_list;
            $return['audit_push_data'] = $push_data;
        }
        return $return;
    }


    /**
     * 重跑批次下所有的单全部都是[审核通过]或[自动审核]通过的状态, 将推送状态改为[待推送]
     * @author Manson
     * @param $rerun_batch
     */
    public function update_push_to_wms_status($rerun_batch)
    {
        $result = $this->purchase_db->select('id, new_demand_number, demand_number, audit_status, rerun_batch')
            ->from($this->table_name)
            ->where_in('rerun_batch',$rerun_batch)
            ->get()->result_array();

        //原单下存在多条新备货单
        foreach ($result as $key => $item){
            $demand_number_info[$item['rerun_batch']][] = $item;
        }

        $update_data = [];

        foreach ($demand_number_info as $key => $item){
            $temp_update_data = [];
            foreach ($item as $k => $v){
                if (in_array($v['audit_status'],[3,5])){//审核通过 或 自动审核通过
                    $is_ok = true;
                    $temp_update_data[] = [
                        'id' => $v['id'],
                        'push_to_wms' => 2,//待推送
                    ];
                }else{
                    $is_ok = false;
                    break;
                }
            }

            if ($is_ok == true){
                $update_data = array_merge($update_data,$temp_update_data);
            }
        }

        if(!empty($update_data)){
            $this->purchase_db->update_batch('pur_shipment_track_list',$update_data,'id');
        }
    }

//通过原备货单号，找到所有计划系统备货单跟踪记录,如果是
    public function get_track_by_demand($demand_number=null,$new_demand_number=null)
    {
        if ($demand_number) {
            $result = $this->purchase_db->select('*')
                ->from($this->table_name)
                ->where('demand_number',$demand_number)
                ->where('is_del',2)
                ->get()->result_array();

        }elseif($new_demand_number){
            $result = $this->purchase_db->select('*')
                ->from($this->table_name)
                ->where('new_demand_number',$new_demand_number)
                ->where('is_del',2)
                ->get()->row_array();

        }

        return empty($result)?[]:$result;
    }

    /**
     * 推送审核结果给到计划
     * http://192.168.71.156/web/#/87?page_id=6655
     * @author Manson
     * @param $audit_status
     * @param $demand_number
     * @param $sku
     * @return mixed
     */
    public function push_audit_result_to_plan($data){

        $url = getConfigItemByName('api_config','shipping_management','procurement_audit');
        $url.="?access_token=". getOASystemAccessToken();
        $header = array('Content-Type: application/json');
        $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
        return $result;
    }
}