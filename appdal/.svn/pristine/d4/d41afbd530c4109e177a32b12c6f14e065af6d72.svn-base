<?php
/**
 * 入库后退货
 */
class Purchase_return_goods_model extends Purchase_model
{
    protected $sock = 'stock';
    protected $product = 'product';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 验证退货sku,并返回可退货的sku数据
     */
    public function verify_return_sku($sku)
    {
        $data = $this->purchase_db->from($this->product)
            ->select('sku,product_name,product_line_id')
            ->where('sku = ', $sku)
            ->get()
            ->row_array();
        return $data && count($data) > 0? $data:[];
    }

    /**
     * 提交入库退货申请
     */
    public function save_return_data_submit($list)
    {
        $res = [
            "code"  => 0,
            "msg"   => ''
        ];
        try{
            $this->purchase_db->trans_begin();
            $uid = getActiveUserId();
            $user = getActiveUserName();
            $date = date('Y-m-d H:i:s');
            $this->load->model('Prefix_number_model'); // 数据表前缀
            $this->load->model('return/Return_after_storage_main_model', 'm_return_main');

            $sku_map = $this->m_return_main->get_import_sku_info(array_column($list, 'sku'));
            $return_data = [];
            $main_all = [];
            foreach ($list as $val){

                $new_main_number = $this->Prefix_number_model->get_prefix_new_number('TH'.date('Ymd'),1,4);
                if (!$new_main_number){
                    throw new \RuntimeException('新的申请ID,生成失败',500);
                }

                $main = [];
                $main['main_number'] = $new_main_number;
                $main['sample_packing_weight'] = $sku_map[$val['sku']]['sample_packing_weight']??'';
                $main['product_name'] = $sku_map[$val['sku']]['product_name']??'';
                $main['unit_price_without_tax'] = $sku_map[$val['sku']]['unit_price_without_tax']??'';
                $main['buyer_id'] = $sku_map[$val['sku']]['buyer_id']??'';
                $main['buyer_name'] = $sku_map[$val['sku']]['buyer_name']??'';
                $main['create_time'] = $date;
                $main['create_user'] = $user;
                $main['proposer'] = $user;
                $main['return_reason'] = $val['cause'];
                $main['return_qty'] = $val['number'];
                $main['return_warehouse_code'] = $val['warehouse_code'];
                $main['sku'] = $val['sku'];
                $main_all[] = $main;
            }
            $this->purchase_db->insert_batch("return_after_storage_main", $main_all);
            //$this->return_sku_push_new_wms($return_data);
            if($this->purchase_db->trans_status()){
                $this->purchase_db->trans_commit();

            }else{
                throw new Exception('事务提交出错');
            }
            $res['code'] = 1;
            $res['msg'] = '申请成功。';
        }catch (Exception $e){
            $res['msg'] = $e->getMessage();
        }
        return $res;
    }

    /**
     * 入库后退货申请推送新wms
     */
    public function return_sku_push_new_wms($ids = [], $type="id")
    {
        if(!is_array($ids) || empty($ids))return;
        $p = array_keys($ids);
        $res_data = [];
        try{
            $query = $this->purchase_db->from("return_after_storage_main as m")
                ->select("m.return_warehouse_code,m.sku,p.part_number,m.pur_return_qty,m.unit_price_without_tax,ui.user_name,p.id,p.restricted_supplier,
                p.supplier_code,p.contact_person,p.contact_number,pr.region_name as contact_province,p.contact_addr,p.create_time,p.freight_payment_type,
                m.return_reason,p.pur_return_qty as return_qty")
                ->join("return_after_storage_part as p", "m.main_number=p.main_number", "left")
                ->join("pur_purchase_user_info as ui", "m.buyer_id=ui.user_id", "inner")
                ->join("pur_region as pr", "p.contact_province=pr.region_code", "left");

            if($type == "id")$query->where_in("p.id", $p);
            if($type == "main")$query->where_in("m.main_number", $p);
            if($type == "part")$query->where_in("p.part_number", $p);

            $data = $query->get()->result_array();
            if(empty($data))throw new Exception("没有要处理的数据");
            $request_url = getConfigItemByName('api_config', 'wms_system', 'receiveDemand');
            if (empty($request_url)) exit('请求URL不存在');
            $access_token = getOASystemAccessToken();
            if (empty($access_token)) exit('获取access_token值失败');
            $request_url = $request_url . '?access_token=' . $access_token;
            $header = ['Content-Type: application/json', 'org:org_00001'];

            $err_list = [];
            $d_list = array_column($data, "sku");
            $sku_list = [];
            $supplier = [];
            if(!empty($d_list)){
                $sku_temp = $this->purchase_db->from("purchase_order_items as i")->select("i.sku,i.check_status,o.supplier_code")
                    ->join("pur_purchase_order as o", "i.purchase_number=o.purchase_number", "inner")
                    ->where_in("i.sku", $d_list)
                    ->order_by("i.id desc")
                    ->get()->result_array();
                if($sku_temp && !empty($sku_temp)){
                    foreach ($sku_temp as $val){
                        $sku = $val['sku'];
                        if(!in_array($sku, array_keys($sku_list)))$sku_list[$sku] = $val['check_status'];
                        if(!in_array($sku, array_keys($supplier)))$supplier[$sku] = [];
                        if(!in_array($val['supplier_code'], $supplier[$sku]))$supplier[$sku][] = $val['supplier_code'];
                    }
                }
            }

            foreach ($data as $val){
                $vSku = $val['sku'];
                $row_sku = isset($sku_list[$val['sku']])?$sku_list[$val['sku']]:0;
                $row_number = isset($ids[$val['id']])?$ids[$val['id']]:0;
                $canUse = '';
                try{
                    if(!empty($val['restricted_supplier']) && in_array($vSku, array_keys($supplier)) && !empty($supplier[$vSku])){
                        $useSupplier = [];
                        $re_su = json_decode($val['restricted_supplier'], true);
                        $re_su = !empty($re_su) ? array_keys($re_su): [];
                        foreach ($supplier[$vSku] as $suV){
                            if(!in_array($suV, $re_su))$useSupplier[] = $suV;
                        }
                        if(!empty($useSupplier))$canUse = implode(",", $useSupplier);
                    }
                }catch (Exception $e){}

                $returnType = 2;
                if($val['return_reason'] == 1){ // 滞销
                    $returnType = 4;
                }elseif($val['return_reason'] == 2){ // 入库后有次品/入库异常
                    $returnType = 1;
                }

                $post_data = [
                    "w"                     => $val['return_warehouse_code'],       // 仓库
                    "demandOrder"           => $val['part_number'],                 // 需求单号
                    "sku"                   => $vSku,                               // sku
                    "volume"                => $val['return_qty'],                  // 确认退货数量
                    "returnPrice"           => $val['unit_price_without_tax'],      // 退货单价
                    "returnType"            => $returnType,                         // 货类型 ,1:入库异常 2:库内异常 3:多货退货 4:滞销退货
                    "purUser"               => $val['user_name'],                   // 采购人
                    "supplierCode"          => $val['supplier_code'],               // 退货供应商
                    "receiveUser"           => $val['contact_person'],              // 收货人
                    "receivePhone"          => $val['contact_number'],              // 收货电话
                    "receiveProvince"       => $val['contact_province'],            // 收货省份
                    "receiveAddress"        => $val['contact_addr'],                // 收货地址
                    "createTime"            => $val['create_time'],                 // 创建时间
                    "isInspection"          => $row_sku,                            // 是否验货1是 0否
                    "returnSupplierCode"    => $canUse,                             // 可退货供应商
                    "payment"               => $val['freight_payment_type'],        // 运费支付方式1.到付,2.寄付(现结),3.寄付(月结)
                ];
                $res = getCurlData($request_url, json_encode($post_data), 'POST', $header);
                $push_data = json_encode($post_data);
                operatorLogInsert(
                    [
                        'id'      => "202106",
                        'type'    => 'return_sku_push_new_wms',
                        'content' => '入库后退货申请推送新wms',
                        'detail'  => "request:{$push_data}......response:{$res}"
                    ]
                );
                $res = json_decode($res, true);
                if(isset($res['code']) && $res['code'] == 200){
                    //$this->purchase_db->where('part_number=', $val['part_number'])->update("return_after_storage_part",["push_to_new_wms" => 1]);
                    continue;
                }
                $res_data[] = $val['sku'];
            }
        }catch (Exception $e){
            $res_data= $e->getMessage();
        }
        return $res_data;
    }
}