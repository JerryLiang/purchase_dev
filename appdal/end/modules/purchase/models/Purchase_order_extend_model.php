<?php
/**
 * 采购单信息相关
 */
class Purchase_order_extend_model extends Purchase_model
{
    protected $purchase_table = 'purchase_order';
    protected $purchase_items_table = 'pur_purchase_order_items';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 批量获取sku质检备注
     * @param $param
     */
    public function get_sku_info_by_erp($param)
    {
        $res = [];
        try{
            $header        = array('Content-Type: application/json');
            $access_taken  = getOASystemAccessToken();
            $url           = getConfigItemByName('api_config','product_system','getSkuInfoList');
            $url           = $url."?access_token=".$access_taken;
            $params = ['skus' => $param];
            $result        = getCurlData($url,json_encode($params,JSON_UNESCAPED_UNICODE),'post',$header);

            $result = json_decode($result, true);
            if(isset($result['code']) && $result['code'] == 200 && isset($result['data'])){
                foreach ($result['data'] as $key => $val){
                    $res[$val['sku']] = $val['qcRemark'];
                    unset($result['data'][$key]);
                }
            }
        }catch (Exception $e){}
        return $res;
    }

    /**
     * 获取sku重量，发货省份等，并计算重量和参考运费
     */
    public function handle_weight_and_ship($sku, $order)
    {
        $res = [
            "ship"  => 0,
            "weight"=> 0,
        ];
        try{
            $supplier_code = $order['supplier_code'];
            $weight = 0;
            $weight_list = [];
            $product = $this->purchase_db->from("product")->select('sku,product_weight')->where_in('sku', $sku)->get()->result_array();
            if($product && count($product) > 0){
                foreach ($product as $val){
                    $weight_list[$val['sku']] = $val['product_weight'];
                }
            }
            foreach ($order['items_list'] as $val){
                $sku_w = isset($weight_list[$val['sku']])?$weight_list[$val['sku']]:0;
                $weight = $weight + ($val['confirm_amount'] * $sku_w);
            }
            $weight = $weight / 1000; // 转换为千克
            $res['weight'] = $weight;

            // 获取供应商地址
            $supplier = $this->purchase_db->from('supplier')->select('ship_province')->where('supplier_code =', $supplier_code)->get()->row_array();
            $province = $supplier && isset($supplier['ship_province'])?$supplier['ship_province']: false;
            if(!$province || $weight == 0 || !isset($order['warehouse_code']))throw new Exception('错误的数据');

            $this->load->model('warehouse/Warehouse_model');
            $ship = $this->Warehouse_model->get_reference_freight($order['warehouse_code'], $province, $weight);
            if($ship)$res['ship'] = $ship;
        }catch (Exception $e){}
        return $res;
    }

    /**
     * 获取虚拟入库
     * @author 叶凡立
     * @time  20201125
     */
    public function get_imitate_purchase_instock($purchase)
    {
        $res = [];
        $main_data = $this->purchase_db->from($this->purchase_table.' as o')
            ->join('pur_purchase_order_pay_type as t', 'o.purchase_number=t.purchase_number', 'inner')
            ->select('o.purchase_number,o.purchase_order_status,o.warehouse_code,o.supplier_code,
            o.supplier_name,o.buyer_name,o.pay_type,o.account_type,o.pay_status,o.pay_time,o.audit_time,
            o.audit_name,t.product_money,t.freight,t.discount,t.process_cost,t.settlement_ratio as settlement_percent,t.purchase_acccount,
            t.pai_number,t.express_no')
            ->where(['o.purchase_number' => $purchase, 'o.warehouse_code' => 'CG-YPC', 'o.purchase_order_status' => PURCHASE_ORDER_STATUS_WAITING_ARRIVAL])
            ->get()
            ->row_array();
        if(!$main_data && count($main_data) == 0)return '只能选择采购单状态为“等待到货”，采购仓库为“采购样品仓”的采购单进行操作！';
        $res = $main_data;
        $res['purchase_order_status'] = getPurchaseStatus($res['purchase_order_status']);
        $res['warehouse'] = getWarehouse($res['warehouse_code']);
        $res['pay_type'] = getPayType($res['pay_type']);
        $res['pay_status'] = getPayStatus($res['pay_status']);
        $settlement = $this->get_ratio_by_type();
        if(empty($settlement))$settlement=[];
        $res['account_type'] = isset($settlement[$res['account_type']]['name'])?$settlement[$res['account_type']]['name']:$res['account_type'];
//        $res['settlement_percent'] = isset($settlement[$res['account_type']]['percent'])?$settlement[$res['account_type']]['percent']:0;

        $res['items'] = $this->get_imitate_purchase_instock_items($purchase);

        return $res;
    }

    /**
     * 获取虚拟入库信息子项
     */
    private function get_imitate_purchase_instock_items($purchase)
    {
        $data = $this->purchase_db->from($this->purchase_items_table." as i")
            ->join('pur_purchase_suggest_map as m', 'i.purchase_number=m.purchase_number and i.sku=m.sku', 'inner')
            ->select('i.id,m.demand_number,i.sku,i.product_name,i.purchase_amount as purchase_amount_all,i.purchase_unit_price,
            i.confirm_amount as purchase_amount,i.product_img_url,i.upselft_amount as instock_qty')
            ->where(['i.purchase_number' => $purchase])
            ->get()
            ->result_array();
        $sku = array_column($data, 'sku');
        $res = [];
        if($data && count($data) > 0 && count($sku) > 0){
            $cancel = $this->purchase_db->from('purchase_order_cancel_detail as d')
//                ->join('pur_purchase_order_cancel as c', "d.cancel_id=c.id")
                ->select("d.sku,d.cancel_ctq")
                ->where(['d.purchase_number' => $purchase])
                ->where_in('d.sku', $sku)
//                ->where_in('c.audit_status', [60, 70])
                ->get()
                ->result_array();
            $cancel_list = [];
            if($cancel && count($cancel) > 0){
                foreach ($cancel as $val){
                    if(empty($val['sku']))continue;
                    if(!in_array($val['sku'], array_keys($cancel_list)))$cancel_list[$val['sku']] = 0;
                    $cancel_list[$val['sku']] += (int)$val['cancel_ctq'];
                }
            }

            $loss = $this->purchase_db->from('purchase_order_reportloss')
                ->select("sku,loss_amount")
                ->where(['pur_number' => $purchase, 'status' => REPORT_LOSS_STATUS_FINANCE_PASS])
                ->where_in('sku', $sku)
                ->get()
                ->result_array();
            $loss_list = [];
            if($loss && count($loss) > 0){
                foreach ($loss as $val){
                    if(empty($val['sku']))continue;
                    if(!in_array($val['sku'], array_keys($loss_list)))$loss_list[$val['sku']] = 0;
                    $loss_list[$val['sku']] += (int)$val['loss_amount'];
                }
            }
            foreach ($data as $val){
                $val['cancel_qty'] = 0;
                $val['loss_qty'] = 0;
                $val['purchase_amount'] = (int)$val['purchase_amount'];
                $val['purchase_amount_all'] = (int)$val['purchase_amount_all'];
                if(isset($cancel_list[$val['sku']]))$val['cancel_qty'] = (int)$cancel_list[$val['sku']];
                if(isset($loss_list[$val['sku']]))$val['loss_qty'] = (int)$loss_list[$val['sku']];
                $val['instock_qty'] = $val['purchase_amount'] - $val['cancel_qty'] - $val['loss_qty'];
                $res[] = $val;
            }
        }
        return $res;
    }

    /**
     * 获取结算信息
     */
    private function get_ratio_by_type()
    {
        $data = $this->purchase_db->from('supplier_settlement')->get()->result_array();
        $account = [];
        foreach ($data as $val){
            $account[$val['settlement_code']] = [
                "name"      => $val['settlement_name'],
                "code"      => $val['settlement_code'],
                "percent"   => $val['settlement_percent'],
            ];
        }
        return $account;
    }

    /**
     * 保存虚拟入库
     * @author 叶凡立
     * @time  20201125
     */
    public function save_imitate_purchase_instock($purchase_number, $list)
    {
        $res = [
            'code'      => 0,
            'msg'       => ''
        ];

        // 限制重复入库
        $is_instock = $this->purchase_db->from('virtual_storage')
            ->where(['purchase_number' => $purchase_number])
            ->where_in("audit_status", [1, 2])
            ->get()->result_array();
        if($is_instock && count($is_instock) > 0){
            $res['msg'] = "采购单：{$purchase_number}正在入库或者已入库，不能重复入库！";
            return $res;
        }

        $verify = $this->save_imitate_purchase_instock_verify($purchase_number, $list);

        if($verify && is_string($verify)){
            $res['msg'] = $verify;
            return $res;
        }

        if(!isset($verify['items']) || count($verify['items']) == 0){
            $res['msg'] = '没有可入库数据！';
            return $res;
        }

        try{
            $this->purchase_db->trans_begin();

            $date = date('Y-m-d H:i:s');
            $instock_batch = 'XR'.date('ymd').$this->get_last_imitate_instock_id();//time().rand(1000, 9999);
            $uid = getActiveUserId();
            $user = getActiveUserName();
            $main = [
                "storage_number"                => $instock_batch,
                "audit_status"                  => 1,
                "create_time"                   => $date,
                "apply_id"                      => $uid,
                "apply_name"                    => $user,
                "purchase_number"               => $purchase_number,
            ];
            $this->purchase_db->replace('virtual_storage', $main);

            foreach ($verify['items'] as $val){
                $buy_name = !empty($val['buyer_name'])?$val['buyer_name']: '';
                $instock_qty = !empty($val['instock_qty'])?$val['instock_qty']:0;

                // 入库详情表
                $items = [
                    "storage_number"                => $instock_batch,
                    "quantity"                      => $instock_qty,
                    "create_time"                   => $date,
                    "purchase_number"               => $purchase_number,
                    'sku'                           => $val['sku'],
                    'demand_number'                 => $val['demand_number'],
                ];

                $this->purchase_db->insert('virtual_storage_detail', $items);
            }

            // 入库日志
            $this->purchase_db->insert('virtual_storage_log', [
                "opr_id"        => $uid,
                "opr_user"      => $user,
                "opr_time"      => $date,
                "detail"        => "发起申请",
                "storage_number"=> $instock_batch,
            ]);

            if($this->purchase_db->trans_status()){
                $this->purchase_db->trans_commit();
                $res['code'] = 1;
                $res['msg'] = '入库成功';
            }else{
                throw new Exception('事务提交出错');
            }
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $res['msg'] = $e->getMessage();
        }
        return $res;
    }

    /**
     * 获取最新一个入库记录
     */
    private function get_last_imitate_instock_id()
    {
        $res = '0001';
        try{
            $data = $this->purchase_db->from('virtual_storage')
                ->select('storage_number')
                ->order_by('id', 'desc')
                ->limit(1)
                ->get()->row_array();
            if(isset($data['storage_number']) && !empty($data['storage_number'])){
                $str = $data['storage_number'];
                $this_data = date('ymd');
                if(substr($str,2, 6) != $this_data){
                    return $res;
                }
                $res = substr($str,-4);
                $res = $res + 1;
                $res = sprintf("%04d", $res);
            }
        }catch (Exception $e){}
        return $res;
    }

    /**
     * 保存虚拟入库 验证
     */
    private function save_imitate_purchase_instock_verify($purchase_number, $list)
    {
        $data = $this->get_imitate_purchase_instock($purchase_number);
        if(!$data || count($data) == 0 || !isset($data['items']) || empty($data['items']))return '验证失败，获取数据失败。';
        $items = [];
        $err = [];
        foreach ($data['items'] as $val){
            $val['this_instock'] = 0;
            $x = $val['purchase_amount'] - $val['cancel_qty'] - $val['loss_qty'] - $val['instock_qty'];
            foreach ($list as $v){
                if($val['demand_number'] == $v['demand_number']){
                    // 计算数量
                    if($x != 0 && $x < $v['instock_qty']){
                        $err[] = $v['demand_number'];
                        break;
                    }
                    $val['this_instock'] = $v['instock_qty'];
                    break;
                }
            }
            if($val['this_instock'] > 0)$items[] = $val;
        }
        if(count($err) != 0)return '备货单：'.implode(',', $err).'入库失败，入库数量 > 采购数量 - 取消数量 - 报损数量 - 已入库数量';
        $data['items'] = $items;
        return $data;
    }

    /**
     * 获取 采购单的入库日志
     * @param $purchase_number
     * @param $sku
     * @return array|bool
     */
    public function get_storage_record($purchase_number,$sku){
        $base = $this->get_purchase_info($purchase_number,$sku);
        $list = $this->purchase_db
            ->select('B.*,C.product_name')
            ->from('warehouse_results_main AS  A')
            ->join('warehouse_results AS B', 'A.items_id=B.items_id','left')
            ->join('product AS C','C.sku=A.sku','left')
            ->where('B.purchase_number', $purchase_number)
            ->where('B.sku', $sku)
            ->get()
            ->result_array();
        if($list && count($list) > 0){
            $instock_type = [ // 入库、2新版入库、3调拨入库、4入库合并流程； 5采购多货调拨
                "1" => "入库",
                "2" => "新版入库",
                "3" => "调拨入库",
                "4" => "入库合并流程",
                "5" => "采购多货调拨",
                "7" => "样品虚拟入库",
            ];
            $delivery_note = [
                1   => '与送货单相符',
                2   => '与送货单不符',
                3   => '无送货单',
            ];
            $res = [];
            $avg_len = count($list);
            $handle_time = 0;
            $all_time = 0;
            $all_len = 0;
            foreach ($list as $val){
                $val['count_efficiency'] = $this->date_is_true($val, 'count_time', 'arrival_date'); // 点数时效
                $val['quality_efficiency'] = $this->date_is_true($val, 'quality_time', 'count_time'); // 质检时效
                $val['paste_code_efficiency'] = $this->date_is_true($val, 'paste_code_time', 'quality_time'); // 贴码时效
                $val['instock_efficiency'] = $this->date_is_true($val, 'instock_date', 'paste_code_time'); // 上架时效
                $val['instock_type'] = isset($instock_type[$val['instock_type']])?$instock_type[$val['instock_type']]: "入库";
                $handle_time += $this->date_is_true($val, 'arrival_date', 'instock_date'); // 仓库处理时效
                $val['delivery_note_ch'] = isset($val['delivery_note']) && in_array($val['delivery_note'], array_keys($delivery_note))?$delivery_note[$val['delivery_note']]:'';
                $res[] = $val;
                $all_time += $val['count_efficiency'] + $val['quality_efficiency'] + $val['paste_code_efficiency'] + $val['instock_efficiency'];

                $all_len = $val['count_efficiency'] > 0? $all_len + 1: 0;
                $all_len = $val['quality_efficiency'] > 0? $all_len + 1: 0;
                $all_len = $val['paste_code_efficiency'] > 0? $all_len + 1: 0;
                $all_len = $val['instock_efficiency'] > 0? $all_len + 1: 0;
            }
            $base['items'] = $res;
            $avg = $all_len >= 1 && $all_time > 0 ? $all_time/$all_len : 0;
            $base['avg_handle_time'] = round($avg, 2);
        }else{
            $base['items'] = [];
        }
        return $base;
    }

    /**
     * 有效时间验证
     */
    private function date_is_true($data=[], $f1='', $f2='')
    {
        if(isset($data[$f1]) && isset($data[$f2]) && !empty($data[$f1]) && !empty($data[$f2]) &&
            $data[$f1] != '0000-00-00 00:00:00' && $data[$f2] != '0000-00-00 00:00:00'){
            try{
                return $this->get_timediff(strtotime($data[$f2]), strtotime($data[$f1]));
            }catch (Exception $e){
                return 0;
            }
        }
        return 0;
    }

    /**
     * 获取时效的小时数
     */
    private function get_timediff($begin_time,$end_time)
    {
        if($begin_time < $end_time){
            $starttime = $begin_time;
            $endtime = $end_time;
        }else{
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        //计算天数
        $timediff = $endtime-$starttime;
        $days = intval($timediff/86400);
        //计算小时数
        $remain = $timediff%86400;
        $hours = intval($remain/3600);
        //计算分钟数
        $remain = $remain%3600;
        $mins = intval($remain/60);
        //计算秒数
        $secs = $remain%60;
        $res = array("day" => $days,"hour" => $hours,"min" => $mins,"sec" => $secs);
        $res = 0;
        $res += $days != 0?$days*24:0;
        $res += $hours;
        $res += round($mins/60, 1);
        return $res;
    }

    /**
     * 获取采购单和报损信息
     */
    private function get_purchase_info($pur_number, $sku)
    {
        $data = $this->purchase_db->from('purchase_suggest_map as map')
            ->join('pur_purchase_order as o', "map.purchase_number=o.purchase_number", "inner")
            ->join('pur_purchase_order_items as it', "map.purchase_number=it.purchase_number and map.sku=it.sku", "inner")
            ->select('map.purchase_number,map.demand_number,map.sku, o.supplier_name,o.warehouse_code,
            it.confirm_amount as purchase_amount,it.product_name,o.supplier_code')
            ->where(['map.purchase_number'=>$pur_number, "map.sku" => $sku])
            ->get()
            ->row_array();
        if($data && count($data) > 0){
            $data['avg_handle_time'] = 0;
            $data['warehouse_name'] = getWarehouse($data['warehouse_code']);
            $cancel = $this->purchase_db->from('purchase_order_cancel_detail as d')
                ->select("d.sku,d.cancel_ctq")
                ->where(['d.purchase_number' => $pur_number])
                ->where_in('d.sku', $sku)
                ->get()
                ->result_array();
            $cancel_qty = 0;
            if($cancel && count($cancel) > 0){
                foreach ($cancel as $val){
                    $cancel_qty += (int)$val['cancel_ctq'];
                }
            }
            $data['cancel_ctq'] = $cancel_qty;

            $loss = $this->purchase_db->from('purchase_order_reportloss')
                ->select("sku,loss_amount")
                ->where(['pur_number' => $pur_number, 'status' => REPORT_LOSS_STATUS_FINANCE_PASS])
                ->where_in('sku', $sku)
                ->get()
                ->result_array();
            $loss_qty = 0;
            if($loss && count($loss) > 0){
                foreach ($loss as $val){
                    $loss_qty += (int)$val['loss_amount'];
                }
            }
            $data['loss_ctq'] = $loss_qty;
            return $data;
        }
        return [];
    }


    /**
     * 获取备货单信息
     */
    public function get_suggest_info($purchase=[], $sku=[])
    {
        if(count($purchase) == 0)return [];
        $query = $this->purchase_db->from("purchase_suggest as s")
            ->select("s.sku,m.purchase_number,s.transport_style,s.extra_handle")
            ->join("pur_purchase_suggest_map as m", "s.demand_number=m.demand_number", "inner")
            ->where_in("m.purchase_number", $purchase);
        if(count($sku) > 0)$query->where_in("s.sku", $sku);
        $data = $query->get()->result_array();
        if(!$data || empty($data))return [];
        $res = [];
        foreach ($data as $val){
            $res[$val['purchase_number']."_".$val['sku']] = $val;
        }
        return $res;
    }

    /**
     * 验证海外业务线   28329，29676
     */
    public function verify_overseas_warehouse_bak($data, $is_has=false, $is_arr=false)
    {
        $res = ["code"=>0, "msg"=>""];
        try{
            $ware = ["AML", "MBB_BL", '4PX-JND', '4PX-JP', 'GC_IT', 'GC-AZ'];
            $transport = ['KJP1'];
            if($is_has || $is_arr){
                $base_query = $this->purchase_db->from('purchase_suggest_map as map')
                    ->select("map.purchase_number,s.transport_style,s.extra_handle,s.destination_warehouse")
                    ->join("pur_purchase_suggest as s", "map.demand_number=s.demand_number", "inner");

                if($is_has){
                    $data = [$data];
                }
                $sug_data = $base_query->where_in("map.purchase_number", $data)
                    ->get()
                    ->result_array();
                if($sug_data && count($sug_data) > 0){
                    $has_oc = [];
                    foreach ($sug_data as $val){
                        if($val['destination_warehouse'] == "GC-FR")continue; // 36162
                        if($val['transport_style'] == 'KJP1' || $val['extra_handle'] == 1 || in_array($val['destination_warehouse'], $ware)){
                            $has_oc[$val['purchase_number']] = 'AFN';
                        }
                        if($val['destination_warehouse'] == 'AML' && $val['transport_style'] == 'LYSH'){
                            $has_oc[$val['purchase_number']] = 'AFN';
                        }
                    }

                    // 如果是慈溪仓

                    if($is_arr && !empty($has_oc)){
                        $res['msg'] = $has_oc;
                        $res['code'] = 1;
                        return $res;
                    }
                    if($is_has && !empty($has_oc)){
                        $res['msg'] = 'AFN';
                        $res['code'] = 1;
                        return $res;
                    }
                }
                return $res;
            }
            $x = 0;
            // 限制仓库
            if(in_array($data['destination_warehouse'], $ware)){
                $res['code'] = 1;
                $x += 1;
            }
            // 物流类型 == 空运
            if(isset($data['transport_style']) && in_array($data['transport_style'], $transport)){
                $res['code'] = 1;
                $x += 1;
            }
            // 是否熏蒸
            if(isset($data['extra_handle']) && $data['extra_handle'] == 1){
                $res['code'] = 1;
                $x += 1;
            }
            if($x == 3)$res['code'] = 3;
        }catch (Exception $e){}
        if($res['code'] >= 1)$res['msg'] = 'AFN';
        return $res;
    }

    /**
     * 根据配置验证海外业务线
     */
    public function verify_overseas_warehouse($data, $is_has=false, $is_arr=false)
    {
        $res = ["code"=>0, "msg"=>""];
        $query = $this->purchase_db->from("param_sets")
            ->where(["pType" => "PURCHASE_ORDER_PERTAIN_SET", "pSort" => 1])
            ->get()->result_array();
        if(!$query || !is_array($query) || count($query) < 1) return $res;

        if($is_has || $is_arr) {
            $base_query = $this->purchase_db->from('purchase_suggest_map as map')
                ->select("map.purchase_number,s.transport_style,s.extra_handle,s.destination_warehouse,s.purchase_type_id")
                ->join("pur_purchase_suggest as s", "map.demand_number=s.demand_number", "inner");
            if ($is_has) {
                $data = [$data];
            }
            $data = $base_query->where_in("map.purchase_number", $data)
                ->get()
                ->result_array();
        }else{
            $data = [$data];
        }

        $res_arr = [];
        foreach ($data as &$value){
            foreach ($query as &$val){
                $params = $val['pValue'] != "" ? json_decode($val['pValue'], true) : [];
                if(!$params || count($params) == 0)continue;
                if($params["purchase_type_id"] != $value['purchase_type_id'])continue;

                // 当是配置的项目时，只要满足其中一个条件
                if(
                    (SetAndNotEmpty($val, 'logistics_type') && $val['logistics_type'] == $value['transport_style']) ||
                    (SetAndNotEmpty($val, 'destination_warehouse') && $val['destination_warehouse'] == $value['destination_warehouse']) ||
                    (SetAndNotEmpty($val, 'is_fumigation', 'n') && $val['is_fumigation'] == $value['extra_handle'])
                ){
                    if($is_has || $is_arr){
                        if($is_has){ // 如果仅是传递采购单号
                            $res['msg'] = $val["pertain_wms"];
                            $res['code'] = 1;
                            break;
                        }
                        if($is_arr){ // 需要返回数据
                            $res_arr[$value['purchase_number']] = $val["pertain_wms"];
                        }
                    }else{ // 传入所有验证参数，不需要额外查询
                        $res['msg'] = $val["pertain_wms"];
                        $res['code'] = 1;
                        break;
                    }
                }
            }

            if($res['code'] == 1)break;
        }
        if(count($res_arr) > 0){
            $res['msg'] = $res_arr;
            $res['code'] = 1;
        }
        return $res;
    }

    /**
     * 获取仓库
     */
    public function get_warehouse_code($code, $type_id, $drawback)
    {
        if(empty($code) || empty($type_id) || ($drawback !=0 || $drawback != 1))return false;
        $data = $this->purchase_db->from('warehouse')
            ->select("warehouse_code")
            ->where(['pertain_wms' => $code, "purchase_type_id" => $type_id, "is_drawback" => $drawback])
            ->get()
            ->row_array();
        if(!isset($data["warehouse_code"]) && !empty($data["warehouse_code"]))return $data["warehouse_code"];
        return false;
    }

    /**
     * 采购数量变更，推送计划系统
     */
    public function amount_change_push_to_plan($data=[])
    {
        $res = ["code" => 0, "msg" => "默认推送失败！"];
        try{
            if(empty($data)){
                $res["msg"] = '没有要推送的数据！';
                return $res;
            }

            $url = getConfigItemByName('api_config', 'java_system_plan', 'push_cancel_qty');
            $access_taken = getOASystemAccessToken();

            $url_api = $url."?access_token=".$access_taken;
            $push_data = json_encode($data);
            $results = getCurlData($url_api, $push_data, 'post', ['Content-Type: application/json']);
            operatorLogInsert(
                [
                    'id'      => "202101",
                    'type'    => 'amount_change_push_to_plan',
                    'content' => '采购数量变更推送计划',
                    'detail'  => "url:{$url_api}......request:{$push_data}......response:{$results}"
                ]
            );
            $results = json_decode($results, true);

            $res['msg'] = '调用采购数量变更推送计划失败';
            if (isset($results['code']) && $results['code'] == 200){//接口调用成功
                $res['code'] = 1;
                $res['msg'] = $results['msg'];
            }
        }catch (Exception $e){}
        return $res;
    }

    /**
     * 获取需求类型枚举值
     */
    public function get_demand_type_list()
    {
//        $res = [];
//        $data = $this->purchase_db->from("demand_type")->get()->result_array();
//        if($data && !empty($data)){
//            foreach ($data as $val){
//                $res[$val['demand_type_id']] = $val['demand_type_name'];
//            }
//        }
//        return $res;
    }

    /**
     * 获取产品的供应商和对应的采购员
     * @params  string|bool     $sku   sku
     * @params  string|bool     $purchase_type_id   业务线
     */
    public function get_sku_supplier($sku=false, $purchase_type_id=false)
    {
        if(!$sku || !$purchase_type_id)return [];
        $sku_info = $this->purchase_db
            ->select('a.sku_state_type,a.product_status,a.product_img_url,a.product_name,a.product_line_id,a.supplier_code,a.supplier_name,
            a.purchase_price,a.ticketed_point,a.create_id,a.create_user_name,a.is_drawback,b.is_cross_border,b.supplier_settlement,c.buyer_id,
            c.buyer_name,d.linelist_cn_name,a.productismulti,a.producttype,a.product_type')
            ->from('product a')
            ->join('pur_supplier b', 'a.supplier_code=b.supplier_code', 'left')
            ->join('pur_supplier_buyer c', 'b.supplier_code=c.supplier_code and c.status=1 and c.buyer_type='.$purchase_type_id, 'left')
            ->join('pur_product_line d', 'a.product_line_id=d.product_line_id', 'left')
            ->where(['a.sku' => $sku])
            ->get()
            ->result_array();
        if($sku_info && count($sku_info) > 0)return $sku_info[0];
        return [];
    }

    /**
     * 验证订单供应商是否一致
     * @param string|array $purchase_number 采购单号
     * @param string $msg_type 返回的数据类型
     * @param string $msg 错误提示语
     * @return bool|string|array true.表示信息一致，string.表示不一致的信息的字符串形式，array.表示不一致的信息的数组形式
     */
    public function verify_sku_supplier($purchase_number,$msg_type = 'string',$msg = "供应商发生变更,请重新确认。如下信息 ")
    {
        if(empty($purchase_number)) return true;// 为空时认为一致

        $data = $this->purchase_db
            ->select('o.purchase_number,GROUP_CONCAT(it.sku) AS sku,o.supplier_code AS o_code,su.supplier_code AS su_code,p.supplier_code AS p_code')
            ->from("purchase_order_items as it")
            ->join('pur_purchase_order as o', 'it.purchase_number=o.purchase_number', 'inner')
            ->join('pur_purchase_suggest as su', 'it.demand_number=su.demand_number', 'inner')
            ->join('pur_product as p', 'it.sku=p.sku', 'left')
            ->where_in('o.purchase_number', $purchase_number)
            ->group_by('o.purchase_number,o.supplier_code,su.supplier_code,p.supplier_code')
            ->get()
            ->result_array();

        $poMessage = [];
        if($data && !empty($data)){
            $po_data = arrayKeyToColumnMulti($data,'purchase_number');
            foreach ($po_data as $po_number => $po_val_list){

                $message = [];
                foreach($po_val_list as $val){
                    if($val['o_code'] != $val['su_code'] || $val['o_code'] != $val['p_code'] || $val['su_code'] != $val['p_code']){
                        $val['sku'] = str_replace(',',' ',$val['sku']);
                        $message[] = 'SKU：'.$val['sku']." 的最新供应商与 ".$po_number." 的供应商不一致，请检查";
                    }
                }

                if($message){
                    $poMessage[$po_number] = implode(" ",$message);
                }
            }
        }
        if(count($poMessage) > 0){
            if($msg_type == 'array'){
                return $poMessage;
            }else{
                return $msg.implode(" ", $poMessage);
            }
        }

        return true;
    }

    /**
     * 人工提交和定时任务排他处理 **
     * 人工审核则判断机器中是否有添加，机器反之
     * @params      string|array      $pur        采购单
     * @params      int         $type       类型：1人工提交，2系统自动
     * @params      int         $handle     操作类型：1验证，2删除验证
     */
    public function verify_task_handle($pur='', $type=1, $handle=1)
    {
        if(!in_array($handle, [1, 2]))return '错误！！';
        $this->load->library('rediss');
        $person = 'PURCHASE_EXAMINE_EXCLUDE_ARTIFICIAL_';
        $machine = 'PURCHASE_EXAMINE_EXCLUDE_MACHINE_';
        $res = [];
        if(is_string($pur))$pur = [$pur];
        // 检测有锁一方
        $k1 = $type == 1?$machine:$person;
        // 如没有，则加锁在另一方
        $k2 = $type == 1?$person:$machine;

        if($handle == 1){
            if(empty($pur))return $res;
            foreach ($pur as $v){
                $getOne = $this->rediss->getData($k1.$v);
                if(empty($getOne)){
                    $this->rediss->setData($k2.$v, 1);
                    $res[] = $v;
                }
            }
            return $res;
        }

        if($handle == 2){
            foreach ($pur as $v){
                $this->rediss->deleteData($k2.$v);
            }
        }
    }

    /**
     * 获取物流类型
     */
    public function get_logistics_type()
    {
        $data = $this->purchase_db->from('logistics_logistics_type')
            ->select("type_code as key, type_name as value")
            ->get()
            ->result_array();
        if($data && count($data) > 0)return $data;
        return [];
    }

    /**
     * 获取仓库信息
     * @return array
     */
    public function get_warehouse(){
        return $this->purchase_db->from("warehouse")->select("warehouse_name,warehouse_code,pertain_wms")->get()->result_array();
    }

}