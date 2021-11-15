<?php
/**
 * Purchase_order_new_model
 * User: yefanli
 * Date: 20200729
 */
class Purchase_order_new_model extends Purchase_model
{
    protected $ali_order = 'ali_order';
//    protected $table_name = 'purchase_order';
//    protected $item_table_name = 'purchase_order_items';
//    protected $ali_order_items = 'ali_order_items';

    /**
     * Purchase_order_new_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->library('alibaba/AliOrderApi');
        $this->load->library('Search_header_data');
    }

    /**
     * 批量下单确认  批量刷新所有PO运费(SHIP)、优惠(Discount)、总金额(PRICE)，从1688重新获取
     * @author yefanli  20200807
     * @param array $pur_number
     * @return mixed
     */
    public function get_ali_order_refresh_purchase_SDP($pur_number)
    {
        $res = [
            'code'      => 0,
            "msg"       => '默认操作失败！'
        ];
        // 取出PO单号对应的拍单号
        $order = $this->purchase_db->from($this->ali_order)
            ->select('purchase_number, order_id')
            ->where_in('purchase_number', $pur_number)
            ->get()
            ->result_array();
        if(!$order || count($order) == 0){
            $res['msg'] = '没有对应的拍单号。';
            return $res;
        }
        $param = [];
        foreach ($order as $val){
            if(!empty($val['order_id']))$param[$val['purchase_number']] = $val['order_id'];
        }
        if(count($param) == 0){
            $res['msg'] = '没有对应的拍单号。';
            return $res;
        }

        $data = $this->aliorderapi->getListOrderDetail(null,array_values($param));
        if(!$data || isset($data['code'])){
            $res['msg'] = isset($data['errorMsg']) ? $data['errorMsg'] : '在1688平台没有查询到相应数据。';
            return $res;
        }

        if($data && count($data) > 0 && is_array($data)){
            $res['data'] = [];
            $param = array_flip($param);
            foreach ($data as $key => $val){
                if(!isset($val['data']['baseInfo']))continue;
                $base_info = $val['data']['baseInfo'];
                $items = [];
                // shipping 运费, refund 退款, totalAmount 总金额, sumProductPayment 产品金额合计，discount 优惠
                $items['shipping']      = isset($base_info['shippingFee'])        && !empty($base_info['shippingFee'])          ? $base_info['shippingFee']       : 0;
                $items['refund']        = isset($base_info['refund'])             && !empty($base_info['refund'])               ? $base_info['refund']            : 0;
                $items['total_amount']  = isset($base_info['totalAmount'])        && !empty($base_info['totalAmount'])          ? $base_info['totalAmount']       : 0;
                $items['amount']        = isset($base_info['sumProductPayment'])  && !empty($base_info['sumProductPayment'])    ? $base_info['sumProductPayment'] : 0;
                $items['discount']      = isset($base_info['discount'])           && !empty($base_info['discount'])             ? $base_info['discount']          : 0;
                if(in_array($key, array_keys($param)))$res['data'][$param[$key]] = $items;
            }
            if(count($res['data']) > 0){
                $res['code'] = 1;
                $res['msg'] = '获取成功';
            }
        }
        return $res;
    }

    /**
     * 采购单页面标题头数据转换
     * @param   array   $base   基准数据
     * @param   array   $param   转换数据
     * @param   int   $type   转换类型：1.转化为前端搜索，2.存储结构转化前端搜索结构
     * @return  mixed
     */
    public function get_header_data_group($base=[], $param = [], $type=1)
    {
        $res = ['code'  => 0, 'msg'   => '', 'data'  => []];
        $middle = $this->search_header_data->get_header_data_group_data();
        $data = [];
        $is_has = false;

        switch ($type){
            case 1:// 1.转化为前端搜索
                foreach ($middle as $k => $v){
                    foreach ($param as $k_p => $v_p){
                        if(in_array($k_p, $v['val']) && $v_p['status'] == 1){
                            $v['status'] = 1;
                            break;
                        }
                    }
                    $val['uid'] = getActiveUserId();
                    unset($v['val']);
                    $data[$k] = $v;
                }
                if(count($data) > 0)$is_has = true;
                break;
            case 2: // 2.转化为存储结构
                $index = 0;  // 基本排序
                foreach ($base as $k => $v){
                    foreach ($param as $k_p => $v_p){
                        if(!in_array($k_p, array_keys($middle)))break;
                        if(in_array($k, $middle[$k_p]['val']) && $v_p["status"] == 1){
                            $data[$k] = [
                                "index" => $index,
                                "status" => $v_p['status'],
                                "name" => $v
                            ];
                            $index ++;
                        }
                    }
                }
                if(count($data) > 0)$is_has = true;
                break;
            case 3: // 3.显示结构
                foreach ($param as $k=>$v){
                    if($v['status'] != 1 || !in_array($k, array_keys($middle)))continue;
                    $data[$v['index']] = [
                        "key"   => $k,
                        "name"  => $v['name'],
                        "field" => $middle[$k]['val']
                    ];
                }
                if(count($data) > 0)$is_has = true;
                ksort($data);
                $data = array_values($data);
                break;
            case 4: // 转化role 一维结构
                foreach ($param as $k=>$v){
//                    if($v['status'] != 1 || !in_array($k, array_keys($middle)) || !isset($middle[$k]['val']) || !is_array($middle[$k]['val']))continue;
                    foreach ($middle[$k]['val'] as $val){
                        $data[$v['index']] = $val;
                    }
                }
                ksort($data);
                if(count($data) > 0)$is_has = true;
                break;
            default:
                break;
        }
        if($is_has){
            $res['code'] = 1;
            $res['data'] = $data;
        }
        return $res;
    }

    /**
     * 获取头部搜索列表
     */
    public function get_set_search_table_header($list_type,$table_columns=false)
    {
        $res=['code' => 0, 'msg' => ''];
        $userid = getActiveUserId();
        $header = $this->purchase_db->where('user_id', $userid)->where('list_type',$list_type)->get('list_header')->row_array();
        if($header && isset($header['header_content']) && !empty($header['header_content'])){
            try {
                $res['code'] = 1;
                $res['msg'] = json_decode($header['header_content'], true);
            }catch (Exception $e){}
        }
        return $res;
    }

    /**
     * 获取采购单标题头默认显示字段
     */
    public function get_purchase_list_head_title()
    {
        $middle = $this->search_header_data->get_header_data_group_data();
        $data = [];
        $x = 0;
        foreach ($middle as $k=>$v){
            $x ++;
            if($x == 16)break;
            $data[] = [
                "key"   => $k,
                "name"  => $v['name'],
                "field" => $v['val']
            ];
        }
        return $data;
    }

    /**
     * 未配置数据获取
     */
    public function get_purchase_list_def()
    {
        $middle = $this->search_header_data->get_header_data_group_data();
        $data = [];
        $x = 0;
        foreach ($middle as $k=>$v){
            $data[$k] = [
                "name"  => $v['name'],
                "index" => $x,
                "status"=> 0
            ];
            $x ++;
        }
        return $data;
    }

    public function get_export_header($type, $uid=false){
        $table_header = $this->get_table_one($type, $uid);
        $data = $this->get_purchase_list_head_title();
        $is_def = false;
        if(isset($table_header['header_content'])){
            $is_def = true;
            $data = json_decode($table_header['header_content'], true);
        }
        $middle = $this->search_header_data->get_header_data_group_data();
        $res = [];
        $handle = [];
        foreach ($data as $k=>$v){
            if($is_def && $v['status'] == 0)continue;
            $row = [
                "key"   => $k,
                "name"  => $v['name'],
                "field" => []
            ];
            foreach ($middle as $k_m=>$v_m){
                if($v_m['name'] == $v['name']){
                    $row["field"] = $v_m["val"];
                    break;
                }
            }
            $handle[] = $row;
        }
        $table_row = $this->search_header_data->table_columns();
        $table_row_keys = array_keys($table_row);
        foreach ($handle as $val){
            foreach ($val['field'] as $v){
                if(in_array($v, $table_row_keys))$res[$v] = $table_row[$v];
            }
        }
        return $res;
    }

    /**
     * @param int $type
     * @return array
     */
    private function get_table_one($type, $uid=false)
    {
        $user_id = $uid ? $uid : getActiveUserId();
        $data = $this->purchase_db
            ->from('list_header')
            ->where(['user_id' =>$user_id, 'list_type' =>$type])
            ->get()
            ->row_array();
        return $data && count($data) > 0?$data:[];
    }

    /**
     * 获取采购单列表头部显示字段
     * @author yefanli
     * @date    20200814
     */
    public function get_show_header($type)
    {
        $res = $this->get_set_table_header($type);
        if($res['code'] == 1){
            $data = $this->get_header_data_group([], $res['msg'], 3);
            $res['msg'] = $data && $data['code'] == 1 ? $data['data'] : [];
        }
        return $res;
    }

    /**
     * 获取采购单显示编辑列表字段
     * @author yefanli
     * @date    20200814
     */
    public function get_set_table_header($type)
    {
        $res = [
            'code'  => 0,
            "msg"   => ''
        ];
        $data = $this->get_table_one($type);
        if(count($data) == 0){
            $res['msg'] = '暂无数据';
            return $res;
        }

        $list_type = isset($data['header_content']) && !empty($data['header_content'])? json_decode($data['header_content'], true): [];
        $res['msg'] = '暂无数据';
        if(count($list_type) > 0){
            $res['code'] = 1;
            $list = [];
            $all = $this->search_header_data->get_header_data_group_data();
            foreach ($all as $k=>$v){
                foreach ($list_type as $key=>$val){
                    if($key == $k){
                        $v['status'] = $val['status'];
                        $v['index'] = $val['index'];
                    }
                }
                $list[$k] = $v;
            }
            $res['msg']  = $list;
        }
        return $res;
    }

    /**
     * 获取用户相应类型的权限
     */
    public function get_role_one($type)
    {
        $user_id = getActiveUserId();
        $data = $this->purchase_db
            ->from('purchase_user_role')
            ->where(['userid' =>$user_id])
            ->get()
            ->row_array();
        return $data && count($data) > 0? $data : [];
    }

    /**
     * 保存编辑的表头字段
     * @author yefanli
     * @date    20200814
     */
    public function save_table_list($data, $type, $p_uid = null)
    {
        $res = [
            'code'  => 0,
            "msg"   => ''
        ];
        $user_id = getActiveUserId();
        $this->purchase_db->trans_begin();
//        $this->load->library('Rediss');
        try{
            // 获取字段单一结构
            $id_has = $this->get_table_one($type);
            $field = $this->search_header_data->table_columns();
//            $hash_data = clone $data;
            if(isset($data) && is_array($data))$data = json_encode($data, true);
            if(count($id_has) > 0){
                $this->purchase_db->update('list_header', ['header_content' => $data, 'modify_time' => date('Y-m-d H:i:s')], ['user_id' =>$user_id, 'list_type' => $type,]);
            }else{
                $this->purchase_db->insert("list_header", [
                    'user_id'       => $user_id,
                    'header_content'=> $data,
                    'list_type'     => $type,
                    'create_time'   => date('Y-m-d H:i:s'),
                ]);
            }

            $has_role = $this->get_role_one($type);
            $field = json_encode(array_keys($field));
            if(count($has_role) > 0){
                $this->purchase_db->update('purchase_user_role', ['role' => $field], ['userid' => $user_id]);
            }else{
                $this->purchase_db->insert("purchase_user_role", [
                    'userid'        => $user_id,
                    'role'          => $field,
                ]);
            }
            $type_arr = [
                TIPS_SEARCH_ALL_ORDER,
                TIPS_SEARCH_WAITING_CONFIRM,
                TIPS_SEARCH_WAITING_ARRIVE,
                TIPS_SEARCH_ORDER_FINISH,
                TIPS_SEARCH_TODAY_WORK,
                TIPS_SEARCH_WAIT_CANCEL,
            ];
            if(in_array($type, $type_arr)){
                // 写入缓存  HEADER_SEARCH_DATA_  uid _ list_type       HEADER_SEARCH_DATA_DEFAULT
                $hash = "HEADER_SEARCH_DATA_LIST";
                $hash_field = "HEADER_SEARCH_DATA_".$p_uid."_".$type;
                $this->rediss->addHashData($hash, $hash_field, $data);
                // 默认数据设置
                $hash_def = "HEADER_SEARCH_DATA_DEFAULT";
                if(!$this->rediss->checkHashData($hash, $hash_def)){
                    $hash_def_data = [
                        "purchase_order_status"=> [
                            "index"=> 0,
                            "status"=> 1,
                            "name"=> "采购状态"
                        ],
                        "suggest_order_status"=> [
                            "index"=> 1,
                            "status"=> 1,
                            "name"=> "备货单状态"
                        ],
                        "purchase_number"=> [
                            "index"=> 2,
                            "status"=> 1,
                            "name"=> "采购单号"
                        ],
                        "create_time"=> [
                            "index"=> 3,
                            "status"=> 1,
                            "name"=> "创建时间"
                        ]
                    ];
                    $hash_def_data = json_encode($hash_def_data);
                    $this->rediss->addHashData($hash, $hash_def, $hash_def_data);
                }
            }

            $this->purchase_db->trans_commit();
            $res['code'] = 1;
            $res['msg'] = "保存成功";
        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $res['msg'] = $e->getMessage();
        }
        return $res;
    }

    /**
     * 24580  采购单列表当采购状态为“等待采购询价”和“等待到货”的时候才可以进行申请无需付款
     * @author yefanli
     */
    public function verify_submit_query($purchase_numbers=false)
    {
        $res = ['code'=> 0, "msg" => []];
        // 验证备货单状态
        $suggest = $this->purchase_db->from('purchase_suggest as s')
            ->select('s.demand_number, s.suggest_order_status')
            ->join('purchase_suggest_map as m', 's.demand_number=m.demand_number', 'left')
            ->where_in('m.purchase_number', $purchase_numbers)
            ->get()
            ->result_array();
        if(!$suggest || count($suggest) == 0){
            $res['msg'][] = '没有相应的备货单';
        }else{
            $suggest_status = [1, 6, 7];
            foreach ($suggest as $val){
                if(!in_array($val['suggest_order_status'], $suggest_status))$res['msg'][] = '备货单：'.$val['demand_number'].'状态验证未通过';
            }
        }

        // 验证采购单状态和合同
        $order = $this->purchase_db->from('purchase_order')
            ->select('purchase_number,is_generate,pay_status')
            ->where_in('purchase_number', $purchase_numbers)
            ->get()
            ->result_array();
        if(!$order || count($order) == 0){
            $res['msg'][] = '没有相应的采购单';
        }else{
            $order_status = [10, 21, 26, 31, 64, 65, 66, 67];
            foreach ($order as $val){
                if(!in_array($val['pay_status'], $order_status))$res['msg'][] = '采购单：'.$val['purchase_number'].'状态验证未通过';
                if($val['is_generate'] == 2)$res['msg'][] = '采购单：'.$val['purchase_number'].'已生成合同';
            }
        }

        // 验证采购单“未生成对账单”
        $statement = $this->purchase_db->from('purchase_statement_items')
            ->select('purchase_number')
            ->where_in('purchase_number', $purchase_numbers)
            ->get()
            ->result_array();
        if($statement && count($statement) > 0){
            foreach ($statement as $val){
                $res['msg'][] = '采购单：'.$val['purchase_number'].'已生对账单';
            }
        }
        if(count($res['msg']) > 0)return $res;
        $res['code'] = 1;
        return $res;
    }

    /**
     * 获取采购单对应的结算方式、支付方式
     */
    public function get_order_payment_pay($purchase_number=false, $is_drawback=false, $supplier_code=false, $purchase_type_id=false)
    {
        $res = ["code" => 0, "msg" => ""];
        $data = [];
        if(!empty($purchase_number)){
            $data = $this->purchase_db->from('purchase_order')
                ->select('purchase_number,supplier_code,purchase_type_id,is_drawback')
                ->where('purchase_number=', $purchase_number)
                ->get()
                ->row_array();
            if($data['is_drawback'] != '')$is_drawback = $data['is_drawback'];
            if($data['purchase_type_id'] != '' && empty($purchase_type_id))$purchase_type_id = $data['purchase_type_id'];
            if($data['supplier_code'] != '' && empty($supplier_code))$supplier_code = $data['supplier_code'];
        }
        if(!$is_drawback && !$supplier_code && !$purchase_type_id){
            $res['msg'] = '没有相应的数据。';
            return $res;
        }
        $this->load->model('supplier/supplier_model'); // 供应商
        $settlement_data = [];
        $settlement_db = $this->purchase_db->from('supplier_settlement')->select('settlement_code,settlement_name,settlement_percent')->get()->result_array();
        if($settlement_db && count($settlement_db) > 0){
            foreach ($settlement_db as $val){
                $settlement_data[$val['settlement_code']] = $val;
            }
        }
        $status = [
            "pay_type"          => [],
            "account_type"      => [],
            "settlement_ratio"  => ""
        ];
        $err = [];

        $pay_type = $this->supplier_model->new_get_suplier_payment_method($supplier_code, $is_drawback, $purchase_type_id);
        $settlement_type = $this->supplier_model->new_get_settlement_data($supplier_code, $is_drawback, $purchase_type_id);
        $settlement_null = ["settlement_code"=>$settlement_type,"settlement_name"=>"","settlement_percent"=>""];
        if(isset($settlement_data[$settlement_type])){
            $status['account_type'] = $settlement_data[$settlement_type];
        }else{
            $status['account_type'] = $settlement_null;
            $err[] = "结算方式";
        }

        $pay_type_box = [];
        if (!empty($pay_type)) {
            $pay_type_box[$pay_type] = getPayType($pay_type);
        }else{
            $err[] = "支付方式";
        }

        if(count($err) > 0){
            $is_d = [
                0 => "不退税",
                1 => "退税"
            ];
            $qu = in_array($is_drawback, array_keys($is_d))?$is_d[$is_drawback]: "此是否退税的";
            $res['msg'] = "采购单:".$purchase_number." 在 ".$qu." 情况下 ".implode(",", $err)." 为空";
            return $res;
        }
        $status['pay_type'] = $pay_type_box;   // pay_type  支付方式
        $status['settlement_ratio'] = isset($settlement_data[$settlement_type])?$settlement_data[$settlement_type]['settlement_percent']:''; // settlement_ratio 结算比例

        $supplier = $this->purchase_db->from("supplier")->where(["supplier_code" => $supplier_code])->get()->row_array();
//        $status['is_freight'] = ["1"=> "甲方支付", "2"=>"乙方支付"];
        $status['is_postage'] = isset($supplier['is_postage']) && $supplier['is_postage'] == 1 ? true : false;
        $status['is_freight'] = $status['is_postage'] ? "1" : "";

        $res['code'] = 1;
        $res['msg'] = $status;
        return $res;
    }

    /**
     * 催发货验证
     * @author yefanli
     * @date    20201123
     */
    public function handle_purchase_urge_data($pur_number)
    {
        $res = [
            "success"   => [],
            "error"     => [],
        ];
        $data = $this->purchase_db->from('purchase_order as o')
            ->join('pur_supplier as s', 'o.supplier_code=s.supplier_code', 'inner')
            ->join('pur_urge_send_order as u', 'o.supplier_code=u.handle_supplier_code and o.purchase_number=u.handle_purchase', 'left')
            ->select('o.purchase_number,o.buyer_name,o.purchase_order_status,o.supplier_name,o.supplier_code,s.use_wechat_official,u.handle_time,u.handle_action')
            ->where_in('o.purchase_number ', $pur_number)
            ->group_by('o.purchase_number')
            ->order_by('u.handle_time', 'desc')
            ->get()
            ->result_array();
        if(!$data || count($data) == 0)return $res;
        $this_time = time();
        $status = [
            PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER,
            PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
            PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE
        ];
        $status_msg =  $wechat =  $time =  $success = [];
        foreach($data as $val){
            if(!in_array($val['purchase_order_status'], $status)){
                $status_msg[] = $val['purchase_number'];
                continue;
            }
            if(empty($val['use_wechat_official']) || $val['use_wechat_official'] != 2){
                $wechat[] = $val['purchase_number'];
                continue;
            }
            if($val['handle_action'] == 1 && !empty($val['handle_time']) && $val['handle_time'] != '0000-00-00 00:00:00' && ($this_time - strtotime($val['handle_time'])) <= 1800){
                $time[] = $val['purchase_number'];
                continue;
            }
            $val['buyer_name'] = preg_replace("/\\d+/", '', $val['buyer_name']);
            $res['success'][] = $val;
        }

        if(count($status_msg) > 0)$res['status'] = '采购单：'.implode('，', $status_msg).'，状态不是等待生成进货单、等待到货、部分到货等待剩余到货，请选择采购单状态为等待到货的，进行催发货操作！';
        if(count($wechat) > 0)$res['wechat'] = '采购单：'.implode('，', $wechat).'，没有启用的公众号用户，无法进行催发货！';
        if(count($time) > 0)$res['time'] = '采购单：'.implode('，', $time).'，30分钟内已催过订单，请稍后再来进行催发货！';
        return $res;
    }

    /**
     * 催改价验证
     * @author yefanli
     * @date    20201123
     */
    public function handle_purchase_change_price_data($pur_number)
    {
        $res = [
            "success"   => [],
        ];
        $data = $this->purchase_db->from('purchase_order as o')
            ->join('pur_supplier as s', 'o.supplier_code=s.supplier_code', 'inner')
            ->join('pur_ali_order as a', 'o.purchase_number=a.purchase_number', 'left')
            ->join('pur_urge_send_order as u', 'o.supplier_code=u.handle_supplier_code and a.order_id=u.handle_purchase', 'left')
            ->select('o.purchase_number,o.purchase_order_status,o.source,o.buyer_name,o.supplier_code,o.supplier_name,
            s.use_wechat_official,a.order_id,u.handle_time,u.handle_action,a.purchase_account')
            ->where_in('o.purchase_number', $pur_number)
            ->group_by('o.purchase_number')
            ->order_by('u.handle_time', 'desc')
            ->get()
            ->result_array();
        if(!$data || count($data) == 0)return $res;
        $status_msg =  $wechat =  $time =  $success = $source = [];
        $status = [
            PURCHASE_ORDER_STATUS_WAITING_QUOTE
        ];
        $this_time = time();
        foreach ($data as $val){
            if($val['source'] != 2){
                $source[] = $val['purchase_number'];
                continue;
            }
            if(!in_array($val['purchase_order_status'], $status)){
                $status_msg[] = $val['purchase_number'];
                continue;
            }
            if(empty($val['use_wechat_official']) || $val['use_wechat_official'] != 2){
                $wechat[] = $val['purchase_number'];
                continue;
            }
            if($val['handle_action'] == 2 && !empty($val['handle_time']) && $val['handle_time'] != '0000-00-00 00:00:00' && ($this_time - strtotime($val['handle_time'])) <= 1800){
                $time[] = $val['handle_time'];
                continue;
            }
            $val['buyer_name'] = preg_replace("/\\d+/", '', $val['buyer_name']);
            $res['success'][] = $val;
        }

        if(count($source) > 0)$res['source'] = "采购单：".implode(",", $source)."，采购来源不是网采，请选择采购单来源为网采的，进行催改价操作！";
        if(count($status_msg) > 0)$res['status'] = "采购单：".implode(",", $status_msg)."，状态不是等待采购询价，请选择采购单状态为等待采购询价的，进行催改价操作！";
        if(count($wechat) > 0)$res['wechat'] = "采购单：".implode(",", $wechat)."，没有启用的公众号用户，无法进行催改价！";
        if(count($time) > 0)$res['time'] = "采购单：".implode(",", $time)."，30分钟内已催过订单，请稍后再来进行催改价！";
        return $res;
    }

    /**
     * 调用催发货/改价接口
     * @author yefanli
     * @date    20201123
     */
    public function send_official_order_data($data, $type=3)
    {
        $res = [
            "list"      => '',
            "status"    => 0,
        ];

        $type_str = "发货";
        $handle_action = 1;
        switch ($type){
            case 1:
                $type_str = '下单';
                $handle_action = 3;
                break;
            case 2:
                $type_str = '改价';
                $handle_action = 2;
                break;
            case 3:
                $type_str = '发货';
                $handle_action = 1;
                break;
            default:
                $handle_action = 1;
                $type_str = '发货';
        }

        if(!$data || count($data) == 0)return $res;

        $sendData = [];
        $list = [];
        foreach ($data as $val) {
            $list[] = $val['purchase_number'];
            $buyer = $handle_action == 1 ? $val['buyer_name'] : $val['purchase_account'];
            $pur_number = $handle_action == 1 ? $val['purchase_number'] : $val['order_id'];
            $supplier = $val['supplier_code'];
            if(!in_array($supplier,array_keys($sendData)))$sendData[$supplier] = [];
            if(empty($sendData[$supplier])){
                $sendData[$supplier] = [
                    "supplierCode"      => $supplier,
                    "buyer"             => $buyer,
                    "sendEvent"         => $type,
                    "purchaseNumbers"   => []
                ];
            }
            if(!empty($pur_number) && $pur_number != " "){
                $sendData[$supplier]['purchaseNumbers'][] = $pur_number;
                $list[] = $pur_number;
            }
        }

        $sendList = [];
        foreach ($sendData as $key=>$val){
            // 催发货，则合并
            if($type == 3){
                $val['purchaseNumbers'] = implode(',', $val['purchaseNumbers']);
                $sendList[] = $val;
            }
            // 如果是催改价，则展开
            if($type == 2 && count($val['purchaseNumbers']) > 0){
                foreach ($val['purchaseNumbers'] as $v){
                    $sendList[] = [
                        "supplierCode"      => $val['supplierCode'],
                        "buyer"             => $val['buyer'],
                        "sendEvent"         => $val['sendEvent'],
                        "purchaseNumbers"   => $v
                    ];
                }
            }
        }

        $url = getConfigItemByName('api_config', 'charge_against', 'officialAccountNotifica');
        $header = array('Content-Type: application/json');
        $access_token = getOASystemAccessToken();
        $url = $url."?access_token=" . $access_token;

        $success = $error = [];
        foreach ($sendList as $val){
            $post_data = json_encode($val, JSON_UNESCAPED_UNICODE);
            $result = getCurlData($url, $post_data, 'post', $header);
            operatorLogInsert(
                [
                    'id'      => "type:".$type,
                    'type'    => 'send_official_order_data',
                    'content' => '催发货催改价',
                    'detail'  => "request：{$post_data}...response：{$result}",
                ]
            );
            $result = json_decode($result,true);
            if(isset($result['code']) && $result['code'] == 200){
                $success[] = $val['purchaseNumbers'];
                $this->insert_urge_list_data($val, $handle_action);
            }else if(isset($result['code']) && $result['code'] == 500 && isset($result['msg'])){
                $error[] = $val['purchaseNumbers'].$result['msg'];
            }else{
                $error[] = $val['purchaseNumbers'];
            }
        }
        if(count($success) > 0){
            $res['status'] = 1;
            $res['list'] = "订单：".implode('，', $success)."，催{$type_str}成功";
        }
        if(count($error) > 0)$res['list'] .= "订单：".implode('，', $error)."，催{$type_str}失败";
        return $res;
    }

    /**
     * 写入催发货/改价成功的记录
     * @author yefanli
     * @date    20201123
     */
    public function insert_urge_list_data($data, $handle_action)
    {
        try{
            $date = date('Y-m-d H:i:s');
            $uid = getActiveUserId()?getActiveUserId():0;
            $user = getActiveUserName()?getActiveUserName():"admin";
            $succ_log = [
                'handle_action'         => $handle_action,
                'handle_time'           => $date,
                'handle_supplier_code'  => $data['supplierCode'],
                'handle_uid'            => $uid,
                'handle_user'           => $user,
                'handle_purchase'       => $data['purchaseNumbers'],
            ];

            if($handle_action == 2){
                $succ_log['handle_user'] = $data['buyer'];
            }
            $this->purchase_db->insert('urge_send_order', $succ_log);
        }catch (Exception $e){}
    }

    /**
     * 获取一个小时内需要自动发送的单
     * @author yefanli
     * @date    20201123
     */
    public function get_one_hour_order_data()
    {
        $this_hour = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $data = $this->purchase_db->from('purchase_order as o')
            ->join('pur_supplier as s', 'o.supplier_code=s.supplier_code', 'inner')
            ->join('pur_purchase_order_pay_type as t', 'o.purchase_number=t.purchase_number', 'left')
            ->join('pur_urge_send_order as u', 'o.supplier_code=u.handle_supplier_code and t.pai_number=u.handle_purchase and u.handle_action = 1', 'left')
            ->select('o.ali_order_amount,t.real_price,u.handle_purchase,o.buyer_name,
            o.purchase_number,t.pai_number as order_id,o.supplier_code,t.purchase_acccount as purchase_account')
            ->where(['o.purchase_order_status' => 1, 'o.source' => 2, 's.use_wechat_official' => 2])
            ->where('o.modify_time > ', $this_hour)
            ->group_by('o.purchase_number')
            ->order_by('u.handle_time')
            ->get()
            ->result_array();
        $res = [];
        if(!$data || count($data) == 0)return $res;
        foreach ($data as $val){
            if(empty($val['handle_purchase']) && $val['ali_order_amount'] != $val['real_price'])$res[] = $val;
        }
        return $res;
    }

    /**
     * 获取可催发货的供应商
     */
    public function get_three_day_order_data()
    {
        $res = $res_temp = [];
        try{
            $three = date('Y-m-d H:i:s', strtotime('- 3 days'));
            $data = $this->purchase_db
                ->select('o.purchase_number,o.ali_order_amount,t.real_price,o.buyer_name,li.express_no,
            t.pai_number as order_id,o.supplier_code,t.purchase_acccount as purchase_account')
                ->from("pur_purchase_suggest_map as map")
                ->join("pur_purchase_order as o", "o.purchase_number=map.purchase_number", "inner")
                ->join("pur_purchase_suggest as sg", "map.demand_number=sg.demand_number and map.sku=sg.sku and map.map_status=1", "inner")
                ->join('pur_purchase_order_pay_type as t', 'map.purchase_number=t.purchase_number', 'inner')
                ->join("pur_supplier as s", "o.supplier_code=s.supplier_code", "left")
                ->join("pur_purchase_logistics_info as li", "map.purchase_number=li.purchase_number", "left")
                ->where("o.audit_time <= ", $three)
                ->where("s.use_wechat_official = ", 2)
                ->where_not_in("sg.purchase_type_id", [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])
                ->where_in("o.purchase_order_status", [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL, PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE])
                ->where("sg.suggest_order_status = ", 7)
                ->group_by("o.purchase_number")
                ->get()
                ->result_array();
            if(!$data || count($data) == 0)throw new Exception();

            foreach ($data as $val){
                if(empty($val['express_no'])){
                    $res[] = $val;
                }else{
                    $res_temp[$val['purchase_number']] = $val;
                }
            }

            if(count($res_temp) > 0){
                // 获取物流信息
                $res_temp = count($res_temp) > 1000 ? array_chunk($res_temp, 1000): [$res_temp];
                foreach ($res_temp as $rt_v){
                    if(count($rt_v) == 0) continue;
                    $track = $this->purchase_db->select("io.purchase_number")
                        ->from('purchase_logistics_info as io')
                        ->join("pur_purchase_logistics_track_detail as td", "io.express_no=td.express_no", "left")
                        ->where_in("io.purchase_number", array_column($rt_v, 'purchase_number'))
                        ->where("td.express_no is null")
                        ->get()
                        ->result_array();
                    $not_track = [];
                    if($track && count($track) > 0){
                        $not_track = array_column($track, "purchase_number");
                    }
                    foreach ($rt_v as $key=>$val){
                        if(in_array($key, $not_track))$res[] = $val;
                    }
                }
            }
            $res_has = [];
            $has_list = [];
            if(count($res) > 0){
                $supplier = array_unique(array_column($res, 'supplier_code'));
                $supplier = count($supplier) > 1000 ? array_chunk($supplier, 1000): [$supplier];
                foreach ($supplier as $s_val){
                    $is_send = $this->purchase_db->from('urge_send_order')
                        ->select("handle_supplier_code,handle_purchase,handle_time")
                        ->where('handle_action =', 1)
                        ->where_in("handle_supplier_code", $s_val)

                        ->get()->result_array();
                    if($is_send && count($is_send) > 0){
                        foreach ($res as $key=>$val){
                            $has = true;
                            foreach ($is_send as $v){
                                if($val['supplier_code'] == $v['handle_supplier_code'] && strpos($v['handle_purchase'],$val['purchase_number']) !== false){
                                    if($v['handle_time'] <= $three && !in_array($val['purchase_number'], $has_list)){
                                        $has_list[] = $val['purchase_number'];
                                        $res_has[] = $val;
                                    }
                                    $has = false;
                                };
                            }
                            if($has && !in_array($val['purchase_number'], $has_list)){
                                $has_list[] = $val['purchase_number'];
                                $res_has[] = $val;
                            }
                        }
                    }
                }
                $res = $res_has;
            }
        }catch (Exception $e){}
        return $res;
    }

    /**
     * 获取门户回货时间
     */
    public function get_supplier_instock_data()
    {
        $date = date('Y-m-d H:i:s',strtotime('-90 days'));
        $sql = "select purchase_number,sku from pur_purchase_order_items where quantity > 0 and 
                (quantity_time ='' or quantity_time = '0000-00-00 00:00:00') and create_time > '{$date}'";
        $data = $this->purchase_db->query($sql)->result_array();
        if($data && count($data) > 0)return $data;
        return [];
    }

    /**
     * 更新门户回货
     */
    public function update_supplier_instock_data($pur, $sku, $time)
    {
        try{
            $this->purchase_db->where(["purchase_number" => $pur, "sku" => $sku])->update("purchase_order_items", ["quantity_time" => $time]);
        }catch (\Exception $e){}
    }

    /**
     * 获取推送开关
     */
    public function get_send_lock_key()
    {
        $data = $this->purchase_db->from("param_sets")->where("pKey =", "URGE_SUPPLIER_SEND_ORDER")->get()->row_array();
        return SetAndNotEmpty($data, 'pValue') && $data['pValue'] == 1? true : false;
    }

    /**
     * 设置推送开关
     */
    public function set_send_lock_key($key)
    {
        $res = ["code" => 0, "msg" => "更新值不能为空"];
        if(empty($key) || in_array($key, [0, 1])){
            return $res;
        }
        if(
            $this->purchase_db->where("pKey =", "URGE_SUPPLIER_SEND_ORDER")
            ->update("param_sets", ["pValue" => $key])
        ){
            $res['code'] = 1;
            $res['msg'] = 'success';
        }else{
            $res['msg'] = 'error';
        }
        return $res;
    }

    /**
     * 门户回货时间
     */
    public function update_supplier_quantity($data)
    {
        $res = ["status" => 0, "errorMess" => "默认处理失败！"];
        if(empty($data) || count($data) == 0){
            $res['msg'] = '提交数据不能为空！';
            return $res;
        }
        try{
            $this->purchase_db->trans_begin();
            foreach ($data as $val){
                if(!SetAndNotEmpty($val, "pur_number") || !SetAndNotEmpty($val, "sku") ||
                    !SetAndNotEmpty($val, "batch_number", 'n') || !SetAndNotEmpty($val, "batch_time")){
                    throw new Exception('采购单号、sku、回货数量或回货时间不能为空！');
                }
                $query = [];
                if(SetAndNotEmpty($val, "batch_number", 'n'))$query['quantity'] = $val['batch_number'];
                if(SetAndNotEmpty($val, "batch_time"))$query['quantity_time'] = $val['batch_time'];
                if(count($query) > 0)$this->purchase_db->where(["purchase_number" => $val['pur_number'], "sku" => $val['sku']])->update("pur_purchase_order_items", $query);
            }
            if($this->purchase_db->trans_status() === FALSE) {
                $this->purchase_db->trans_rollback();
                throw new Exception('操作失败');
            }
            $this->purchase_db->trans_commit();
            $res['errorMess'] = '更新成功！';
            $res['status'] = 1;
        }catch (Exception $e){
            $this->purchase_db->trans_rollback();
            $res['errorMess'] = $e->getMessage();
        }
        return $res;
    }


}