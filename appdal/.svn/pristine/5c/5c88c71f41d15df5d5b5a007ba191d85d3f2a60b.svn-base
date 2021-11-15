<?php
/**
 * Purchase_order_model
 * User: yefanli
 * Date: 20200729
 */
class Purchase_delivery_note_model extends Purchase_model
{
    protected $table_name = 'purchase_order';
    protected $item_table_name = 'purchase_order_items';
    protected $warehouse_table = 'warehouse';
    protected $suggest_map_table = 'purchase_suggest_map';
    protected $product_table = 'product';
    protected $user_table = 'purchase_user';

    /**
     * Purchase_order_new_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/invoice_information_model','information',false,'system');
        $this->load->model('warehouse/Warehouse_model','Warehouse_model');
        $this->load->helper('common');
    }

    /**
     * 获取采购单与采购单下的items
     * @author yefanli
     * @time    20200729
     */
    public function get_order_and_items($pur_number=[])
    {
        $res = [
            'code' => 0,
            "message" => ''
        ];
        if(count($pur_number) == 0){
            $res["message"] = '采购单不能为空';
            return $res;
        }

        $data = $this->purchase_db->from($this->table_name." as od")
            ->select("od.purchase_number, od.is_drawback, od.supplier_code, od.supplier_name, od.pertain_wms, od.purchase_name, od.warehouse_code, 
            it.sku,it.product_name,it.confirm_amount,it.product_img_url,od.buyer_id, od.buyer_name,DATE_FORMAT(od.audit_time, '%Y-%m-%d') as audit_time")
            ->join($this->item_table_name." as it", "on od.purchase_number=it.purchase_number", "left")
            ->where_in("od.purchase_number", $pur_number)
            ->order_by('od.purchase_number', 'desc')
            ->get()
            ->result_array();

        if(!$data || count($data) == 0){
            $res["message"] = '没有相应的采购单数据';
            return $res;
        }
        $res['code'] = 1;
        $res['data'] = $data;
        return $res;
    }

    /**
     * 校验：退税一致，且供应商一致，公共仓一致 DSW(drawback-supplier-warehouse)
     * @author yefanli
     * @time    20200729
     * @param array $arr
     * @return mixed
     */
    public function verify_DSW_uniformity($arr=[])
    {
        $res = false;
        $err = [];
        $d = [];
        $s = [];
        $w = [];
        foreach ($arr as $val){
            if(!in_array($val["is_drawback"], $d))$d[] = $val["is_drawback"];
            if(!in_array($val["supplier_code"], $s))$s[] = $val["supplier_code"];
            if(!in_array($val["warehouse_code"], $w))$w[] = $val["warehouse_code"];
        }
        if(count($d) > 1)$err[] = '退税不一致';
        if(count($s) > 1)$err[] = '供应商不一致';
        if(count($w) > 1)$err[] = '公共仓不一致';
        if(count($err) > 0)return '勾选采购单必须是退税一致，供应商一致，公共仓一致，当前：'.implode($err, ",");
        return $res;
    }

    /**
     * 组合前台数据
     * @author yefanli
     * @time    20200729
     * @param array $arr
     * @return array
     */
    public function combination_show_data($arr=[], $contacts=[])
    {
        $data = [
            "supplier"      => "",      // 供应商
            "purchaser"     => "",      // 采购主体
            "addressee"     => isset($contacts["contacts"]) ?$contacts["contacts"]: "",      // 收货人
            "address"       => "",      // 收货地址（公共仓）
            "phone"         => isset($contacts["phone"]) ?$contacts["phone"]: "",      // 收货电话
            "purchase_sum"  => 0,       // 采购单个数
            "sku_sum"       => 0,       // sku个数
            "pcs"           => 0,       // 总个数
            "value"         => []       // 列表数据
        ];
        $key = 1;
        $audit_time = [];
        foreach ($arr as $val){
            if($data["supplier"] == "")$data["supplier"] = $val['supplier_name'];
            if($data["address"] == ""){
                $ad = $this->get_pertain_wms($val['pertain_wms'], 1);
                $data["address"] = $ad && $ad["code"] == 1 ? $ad["data"] : "";
            }
            if($data["purchaser"] == ""){
                $information_info = $this->information->getInformationByKey($val['purchase_name']);
                if(!isset($information_info['unit_name']) || empty($information_info['unit_name'])){
                    $information_info = $this->get_purchase_agent($val['purchase_name']);
                }
                $data["purchaser"] = $information_info['unit_name'];
            }

            $data['pcs'] += (int)$val['confirm_amount'];
            $data['sku_sum'] += 1;

            // 列表数据
            $ws = $this->get_pertain_wms($val['warehouse_code'], 3);
            $pak = $this->get_sku_package($val['sku']);
            $packaging = '';
            if($pak){
                $packaging = strpos($pak,"[");
                $packaging = substr_replace($pak,"",$packaging);
            }
            $val_list = [
                "id"                    => $key,
                "purchase_number"       => $val['purchase_number'],
                "warehouse_code"        => $ws && $ws['code'] == 1 ? $ws['data'] : $val['warehouse_code'].'(无此编号)',      // 采购仓库
                "sku"                   => $val['sku'],
                "purchase_package"      => $packaging,
                "product_name"          => $val['product_name'],
                "audit_time"            => $val['audit_time'],
                "confirm_amount"        => $val['confirm_amount'],  // 实际采购数量
                "product_img_url"       => erp_sku_img_sku($val['product_img_url']),
            ];
            $audit_time[] = $val['audit_time'];
            if(!in_array($val['purchase_number'], array_keys($data["value"]))){
                $data['value'][$val['purchase_number']] = [];
                $data['purchase_sum'] += 1;
            }
            $data['value'][$val['purchase_number']][] = $val_list;
            $key ++;
        }
        sort($audit_time);
        $data['audit_time'] = isset($audit_time[0])?$audit_time[0]:"";
        return $data;
    }

    /**
     * 如pur_invoice_information中没有，则从pur_param_sets 获取相应的 PUR_PURCHASE_AGENT
     */
    public function get_purchase_agent($company=false)
    {
        $res = [
            'code'  => 0,
            'unit_name'  => ''
        ];
        $data = $this->purchase_db->from('param_sets')
            ->where('pKey =', 'PUR_PURCHASE_AGENT')
            ->select('pValue')
            ->get()
            ->row_array();
        if(!$data || !isset($data['pValue'])) return $res;
        $pValue =  !empty($data['pValue']) ? json_decode($data['pValue'], true) : false;
        if($pValue && is_array($pValue) && in_array($company, array_keys($pValue))){
            $res['code'] = 1;
            $res['unit_name'] = $pValue[$company];
        }
        return $res;
    }

    /**
     * 获取sku对应的包装
     */
    public function get_sku_package($sku=false)
    {
        if(!$sku)return '';
        $data = $this->purchase_db->from($this->product_table)->select('purchase_packaging')->where("sku=", $sku)->get()->result_array();
        if($data && count($data) > 0)return $data[0]['purchase_packaging'];
        return '';
    }

    /**
     * 获取公共仓地址  随机获取备货单对应的联系人
     */
    public function get_pertain_wms($code=false, $type=1)
    {
        $res = [
            "code"      => 1,
            "message"   => '',
            "data"      => []
        ];
        $query = $this->purchase_db->from($this->warehouse_table." as pw")
            ->select("pw.warehouse_name,pwa.country,pwa.province_text,pwa.city_text,pwa.area_text,pwa.town_text,pwa.address,pwa.contacts,pwa.contact_number")
            ->join("pur_warehouse_address as pwa", "on pw.warehouse_code=pwa.warehouse_code", "left");
        switch ($type){
            case 1: // 公共仓 取值
                $query->where("pw.pertain_wms=", $code);
                break;
            case 2: // 根据仓库随机取联系人
            case 3: // 获取仓库名称
                $query->where_in("pw.warehouse_code", $code);
                break;
            default:
                $query->where("pw.pertain_wms=", $code);
        }
        $data = $query->get()->result_array();
        if(!$data || count($data) == 0){
            $res["message"] = "没有仓库数据";
            return $res;
        }

        // 公共仓 地址 取值
        if($type == 1){
            $res["data"] = $data[0]['country'].' '.$data[0]['province_text'].$data[0]['city_text']
                .$data[0]['area_text'].$data[0]['town_text'].' '.$data[0]['address'];
            $res['code'] = 1;
            return $res;
        }
        // 根据仓库随机取联系人  获取采购员
        if($type == 2 && is_array($code)){
            $res['data']["contacts"] = $data[mt_rand(0, count($data) -1)]['contacts'];
            $res['data']["phone"] = $data[mt_rand(0, count($data) -1)]['contact_number'];
            $res['code'] = 1;
            return $res;
        }
        if($type == 3){
            $res["data"] = $data[0]['warehouse_name'];
            $res['code'] = 1;
            return $res;
        }
        return $res;
    }

    /**
     * 获取用户信息
     */
    public function get_contacts($uid=false)
    {
        $res = [
            'code'  => 0,
            "data"  => []
        ];
        $data = $this->purchase_db->from($this->user_table)
            ->select('user_name, iphone')
            ->where('user_id =', $uid)
            ->get()
            ->result_array();
        if(!$data || count($data) == 0)return $res;
        foreach ($data as $val){
            $res["data"]["contacts"]  = $val['user_name'];
            $res["data"]["phone"]  = $val['iphone'];
            $res['code'] = 1;
            break;
        }
        return $res;
    }

    /**
     * 获取采购单对应的备货单，以及对应的仓库
     */
    public function get_suggest_wms_contacts($pur_number=[])
    {
        $data = $this->purchase_db->from($this->suggest_map_table.' as psm')
            ->select('pps.warehouse_code')
            ->join('pur_purchase_suggest as pps', "on psm.demand_number=pps.demand_number", 'left')
            ->where_in('psm.purchase_number', $pur_number)
            ->get()
            ->result_array();
        if(!$data || count($data) == 0)return false;
        $res = [];
        foreach ($data as $val){
            if(!in_array($val["warehouse_code"], $res))$res[] = $val["warehouse_code"];
        }
        return $res;
    }
}