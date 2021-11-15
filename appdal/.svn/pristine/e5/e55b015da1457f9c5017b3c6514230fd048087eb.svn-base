<?php
/**
 * Created by PhpStorm.
 * 备选供应商控制器
 * User: 鲁旭
 * Date: 2020/4/22 0027 11:17
 */

class Alternative_suppliers_model extends Purchase_model {

    public function __construct(){
        parent::__construct();
        $this->load->model('product_line_model','product_line',false,'product');
        $this->load->helper(['user', 'status_product','status_supplier','status_alternative']);
        $this->load->model('supplier_buyer_model','supplier_buyer_model',false,'supplier');
        $this->load->model('product_model','product',false,'product');
        $this->load->model('Supplier_model','Supplier_model',false,'supplier');
        $this->load->model('Message_model');

    }

    /**
     * SKU+供应商,从历史采购记录获取最近一次采购的采购数量,没有数据就留空
     * @params : $sku  string   SKU
     *           $supplier_code  string  供应商CODE
     * @author:luxu
     * @time:2021年4月23号
     **/

    private function get_sku_supplier_orders($sku,$supplier_code){

      $result = $this->purchase_db->from("purchase_order_items AS items")->join("purchase_order as orders","items.purchase_number=orders.purchase_number","LEFT")
          ->where("items.sku",$sku)->where("orders.supplier_code",$supplier_code)
          ->where_in("orders.purchase_order_status",[PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION
          ,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
              PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND,PURCHASE_ORDER_STATUS_CANCELED,
              PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT ])
          ->select("items.confirm_amount,orders.audit_time")
          ->order_by("orders.audit_time DESC")->limit(1)->get()->row_array();
      return $result;

    }

    /**
     * 获取供应商信息
     * @param $supplierCods    array   供应商CODE
     * @author:luxu
     * @time:2021年4月23号
     **/

    private function alternative_supplier($supplierCods){

        $result = $this->purchase_db->from("supplier as supp")->join("supplier_settlement as settle","supp.supplier_settlement=settle.settlement_code","LEFT")
            ->where_in("supp.supplier_code",$supplierCods)->get()->result_array();
        if(!empty($result)){

           return array_column($result,NULL,'supplier_code');
       }

       return NULL;
    }

    /**
     * 添加SKU 备选供应商日志数据
     * @params $datas   array   日志数据
     * @author:luxu
     * @time:2021年4月27号
     **/

    private function add_alternative_logs($datas){

        if(empty($datas)){

            return NULL;
        }

        $this->purchase_db->insert('alternative_operation',$datas);
    }

    /**
     * 备选供应商添加
     * @params $sku string  SKU
     *         $alternativeDatas  array 备货供应商数据
     * @author:luxu
     * @time:2021年4月28号
     **/
    public function update_alternative_supplier($alternativeDatas){

        try{
            $supplier_codes = array_unique(array_column($alternativeDatas, 'supplier_code')); // 备选供应商CODE
            $searchDatas = $this->purchase_db->from("alternative_suppliers")->where("sku", $alternativeDatas['sku'])
                ->where_in("supplier_code", $alternativeDatas['supplier_code'])
                ->where("id!=",$alternativeDatas['id'])->select("id,sku,supplier_name")->get()->result_array();
            if (!empty($searchDatas)) {
                $supplier_names = implode(",", array_column($searchDatas, 'supplier_name'));
                throw new  Exception("sku:".$alternativeDatas['sku'].",备选供应商:".$supplier_names.".已经存在，请重新选择");
            }

            // 判断SKU 备选供应商是否在申请中
            $logs = $this->purchase_db->from("alternative_supplier_log")->where("sku", $alternativeDatas['sku'])
                ->where_in("supplier_code", $alternativeDatas['supplier_code'])->where("audit_status",0)->select("id,sku,supplier_name")->get()->result_array();
            if(!empty($logs)){
                throw new  Exception("sku:".$alternativeDatas['sku'].",备选供应商:".$alternativeDatas['sku'].".已经在申请，请重新选择");
            }

            $alternativeLogs = $this->purchase_db->from("alternative_suppliers")->where("id",$alternativeDatas['id'])->get()
                ->row_array();

            // 写入申请日志
            $insertData =[
                'sku' => $alternativeDatas['sku'],
                'apply_user' => getActiveUserName(),
                'apply_time' => date("Y-m-d H:i:s",time()),
                'change_type' => 2, // 新增加
                'new_is_purchasing' => $alternativeDatas['is_purchasing'], // 是否包邮,修改后
                'old_is_purchasing' => $alternativeLogs['is_purchasing'], // 是否包邮，修改前
                'supplier_code' => $alternativeDatas['supplier_code'], // 供应商CODE
                'supplier_name' => $alternativeDatas['supplier_name'], // 供应商名称
                'new_product_price' => $alternativeDatas['purchase_price'], // 未税单价,修改后
                'old_product_price' => $alternativeLogs['purchase_price'], // 未税单价,修改前
                'new_starting_qty_unit' => $alternativeDatas['starting_qty_unit'], // 最小起订量
                'new_starting_qty_unit' => $alternativeLogs['starting_qty_unit'],
                'new_delivery' => $alternativeDatas['delivery'], // 交期
                'old_delivery' => $alternativeLogs['delivery'],
                'new_is_shipping' => $alternativeDatas['is_shipping'], // 是否包邮
                'old_is_shipping' => $alternativeLogs['is_shipping'],
                'apply_user_id' => getActiveUserId(), // 申请人ID
                'new_url' => $alternativeDatas['url'],
                'old_url' => $alternativeLogs['url']
            ];
            $result = $this->purchase_db->insert('alternative_supplier_log',$insertData);
            if($result){
                // 申请成功添加日志
                $logsData = [
                    'sku' => $alternativeDatas['sku'],
                    'supplier_code' => $alternativeDatas['supplier_code'], // 供应商CODE
                    'supplier_name' => $alternativeDatas['supplier_name'], // 供应商名称
                    'operation_user' => getActiveUserName(),
                    'operation_time' => date("Y-m-d H:i:s",time()),
                    'type' =>2,
                    'data_type'=>3,
                    'audit_type' =>1,
                    'remark' =>$alternativeDatas['remark']

                ];
                $this->add_alternative_logs($logsData);

                return True;
            }
            throw new Exception("sku:".$alternativeDatas['sku'].",修改备选供应商失败");


        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 备选供应商添加
     * @params $sku string  SKU
     *         $alternativeDatas  array 备货供应商数据
     * @author:luxu
     * @time:2021年4月22号
     **/
    public function add_alternative_supplier($alternativeDatas){

        try {
            $supplier_codes = array_unique(array_column($alternativeDatas, 'supplier_code')); // 备选供应商CODE
            $searchDatas = $this->purchase_db->from("alternative_suppliers")->where("sku", $alternativeDatas['sku'])
                ->where_in("supplier_code", $alternativeDatas['supplier_code'])->select("id,sku,supplier_name")->get()->result_array();
            if (!empty($searchDatas)) {
                $supplier_names = implode(",", array_column($searchDatas, 'supplier_name'));
                throw new  Exception("sku:".$alternativeDatas['sku'].",备选供应商:".$supplier_names.".已经存在，请重新选择");
            }

            // 判断SKU 备选供应商是否在申请中
            $logs = $this->purchase_db->from("alternative_supplier_log")->where("sku", $alternativeDatas['sku'])
                ->where_in("supplier_code", $alternativeDatas['supplier_code'])->where("audit_status",0)->select("id,sku,supplier_name")->get()->result_array();
            if(!empty($logs)){
                throw new  Exception("sku:".$alternativeDatas['sku'].",备选供应商:".$alternativeDatas['sku'].".已经在申请，请重新选择");
            }
            // 写入申请日志
            $insertData =[
                'sku' => $alternativeDatas['sku'],
                'apply_user' => getActiveUserName(),
                'apply_time' => date("Y-m-d H:i:s",time()),
                'change_type' => 1, // 新增加
                'new_is_purchasing' => $alternativeDatas['is_purchasing'], // 是否包邮
                'supplier_code' => $alternativeDatas['supplier_code'], // 供应商CODE
                'supplier_name' => $alternativeDatas['supplier_name'], // 供应商名称
                'new_product_price' => $alternativeDatas['purchase_price'], // 未税单价
                'new_starting_qty_unit' => $alternativeDatas['starting_qty_unit'], // 最小起订量
                'new_delivery' => $alternativeDatas['delivery'], // 交期
                'new_is_shipping' => $alternativeDatas['is_shipping'], // 是否包邮
                'apply_user_id' => getActiveUserId(), // 申请人ID
                'new_url' => $alternativeDatas['url']
            ];
            $result = $this->purchase_db->insert('alternative_supplier_log',$insertData);
            if($result){
                // 申请成功添加日志

                $logsData = [
                    'sku' => $alternativeDatas['sku'],
                    'supplier_code' => $alternativeDatas['supplier_code'], // 供应商CODE
                    'supplier_name' => $alternativeDatas['supplier_name'], // 供应商名称
                    'operation_user' => getActiveUserName(),
                    'operation_time' => date("Y-m-d H:i:s",time()),
                    'type' =>1,
                    'data_type'=>1,
                    'audit_type' =>1,
                    'remark' =>$alternativeDatas['remark']

                ];
                $this->add_alternative_logs($logsData);

                return True;
            }
            throw new Exception("sku:".$alternativeDatas['sku'].",添加备选供应商失败");
        }catch ( Exception $exp ){
            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 获取备选供应商接口
     * @params $clientDatas  array  查询字段接口
     * @author:luxu
     * @time:2021年4月22号
     **/
    public function get_alternative_supplier($clientDatas){
        $query = $this->purchase_db->from("product as product")
            ->join('pur_alternative_suppliers as alternative','product.sku=alternative.sku AND alternative.is_show!=2','LEFT');
        $query->join("supplier as supp","product.supplier_code=supp.supplier_code","LEFT");
        $query->join("supplier as supp1","alternative.supplier_code=supp1.supplier_code","LEFT");

        if(

        (isset($clientDatas['supplier_level']) && !empty($clientDatas['supplier_level']))
        ||
        (isset($clientDatas['cooper_status']) && !empty($clientDatas['cooper_status'][0]))
        ||
        (isset($clientDatas['supplier_source']) && !empty($clientDatas['supplier_source']))
        ||
        (isset($clientDatas['settlement_code']) && !empty($clientDatas['settlement_code']))
        ){


        }

        if( isset($clientDatas['settlement_code']) && !empty($clientDatas['settlement_code'])){

            $query->join('supplier_payment_info as info','info.supplier_code=supp.supplier_code','LEFT');
            $query->join('supplier_payment_info as info1','info1.supplier_code=supp1.supplier_code','LEFT');
        }

        // 查询SK

        if(isset($clientDatas['sku']) && !empty($clientDatas['sku'][0])){
            $query->where_in('product.sku',$clientDatas['sku']);
        }

        //查询供应商
        if( isset($clientDatas['supplier_code']) && !empty($clientDatas['supplier_code'][0]) ){
            $this->purchase_db->group_start();
            $query->where_in("alternative.supplier_code",$clientDatas['supplier_code']);
            $query->or_where_in("product.supplier_code",$clientDatas['supplier_code']);
            $this->purchase_db->group_end();
        }

        // 产品名称查询
        if( isset($clientDatas['product_name'])){
            $query->like('product.product_name', $clientDatas['product_name'], 'both');
        }
        // 获取是否包邮
        if( isset($clientDatas['is_shipping']) && !empty($clientDatas['is_shipping'])){
            $this->purchase_db->group_start();
            $query->where("(product.is_shipping='".$clientDatas['is_shipping']."')")
                ->or_where("(alternative.is_shipping='".$clientDatas['is_shipping']."' AND product.supplier_code!=alternative.supplier_code)");
            $this->purchase_db->group_end();
        }

        // 供应商来源
        if( isset($clientDatas['supplier_source']) && !empty($clientDatas['supplier_source'])){

            $this->purchase_db->group_start();

            if(is_array($clientDatas['supplier_source'])) {
                $query->where_in("supp1.supplier_source", $clientDatas['supplier_source']);
                $query->where_in("supp1.supplier_source", $clientDatas['supplier_source']);

                $query->or_where_in("(supp.supplier_source", $clientDatas['supplier_source']);
                $query->where_in("supp1.supplier_source", $clientDatas['supplier_source'])->where(" 1=1)");


            }else{
                $query->where("supp1.supplier_source", $clientDatas['supplier_source']);
                $query->where("supp1.supplier_source", $clientDatas['supplier_source']);


                $query->or_where("(supp.supplier_source", $clientDatas['supplier_source']);
                $query->where("supp1.supplier_source", $clientDatas['supplier_source'])->where(" 1=1) ");

            }

            $this->purchase_db->group_end();

        }
        // 产品状态
        if( isset($clientDatas['product_status']) && !empty($clientDatas['product_status'])){
            $query->where_in("product.product_status",$clientDatas['product_status']);
        }
        // 采购员
        if( isset($clientDatas['buyer_code']) && !empty($clientDatas['buyer_code'])){

            $this->purchase_db->join('supplier_buyer as supplierbuyer', 'product.supplier_code=supplierbuyer.supplier_code', 'left');
            $this->purchase_db->where_in("supplierbuyer.buyer_id", $clientDatas['buyer_code']);
            $this->purchase_db->where("supplierbuyer.status", 1);
        }
        // 是否代采
        if( isset($clientDatas['is_purchaseing']) && !empty($clientDatas['is_purchaseing'])){
            $this->purchase_db->group_start();

            $query->where("alternative.is_purchasing",$clientDatas['is_purchaseing']);
            $query->or_where("product.is_purchasing",$clientDatas['is_purchaseing']);
            $this->purchase_db->group_end();

        }
        // 开发人员
        if( isset($clientDatas['create_user_name']) && !empty($clientDatas['create_user_name'])){

            $query->where_in("product.create_user_name",$clientDatas['create_user_name']);
        }
        // 修改时间
        if( isset($clientDatas['update_time_start']) && !empty($clientDatas['update_time_start'])){
            $query->where("alternative.update_time>=",$clientDatas['update_time_start']);
        }
        if( isset($clientDatas['update_time_end']) && !empty($clientDatas['update_time_end'])){
            $query->where("alternative.update_time<=",$clientDatas['update_time_end']);
        }
        // 合作状态
        if( isset($clientDatas['cooper_status']) && !empty($clientDatas['cooper_status'][0])){
            $query->group_start();
            $query->where_in("supp1.status",$clientDatas['cooper_status']);
            $query->where_in("supp1.status",$clientDatas['cooper_status']);

            //$query->group_start();
            $query->or_where_in("(supp.status",$clientDatas['cooper_status']);
            $query->where_in("supp1.status",$clientDatas['cooper_status'])->where(" 1=1)");
           // $query->group_end();

            $query->group_end();



        }

        // 修改人
        if( isset($clientDatas['update_user']) && !empty($clientDatas['update_user'])){
            $query->where_in("alternative.update_user",$clientDatas['update_user']);
        }
        // 供应商结算方式
        if( isset($clientDatas['settlement_code']) && !empty($clientDatas['settlement_code'])){



            $query->group_start();
            $query->where_in("info1.supplier_settlement",$clientDatas['settlement_code'])->where("info1.is_del",0);
            $query->where_in("info1.supplier_settlement",$clientDatas['settlement_code'])->where("info1.is_del",0);

            $query->or_where_in("(info.supplier_settlement",$clientDatas['settlement_code'])->where("info.is_del",0);
            $query->where_in("info1.supplier_settlement",$clientDatas['settlement_code'])->where("info1.is_del",0)->where(" 1=1)");


            $query->group_end();
        }

        // 产品线

        if( isset($clientDatas['product_line_id']) && !empty($clientDatas['product_line_id'][0])){

            $category_all_ids = $this->product_line->get_all_category($clientDatas['product_line_id']);
            $children_ids = explode(",", $category_all_ids);
            $children_ids = array_filter($children_ids);
            $query->where_in("product.product_line_id",$children_ids);
        }

        if (!empty($clientDatas['buyer_code'])) {
            $this->purchase_db->join('supplier_buyer as supplierbuyer', 'product.supplier_code=supplierbuyer.supplier_code', 'left');
            $this->purchase_db->where_in("supplierbuyer.buyer_id", $clientDatas['buyer_code']);
            $this->purchase_db->where("supplierbuyer.status", 1);
        }


        if (isset($clientDatas['developer_code']) && !empty($clientDatas['developer_code'])) {
            $develop_codes = [];
            foreach ($clientDatas['developer_code'] as $develop_key => $dvelop_value) {
                if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($dvelop_value), $arr)) {

                    $develop_codes[] = $arr[0];
                }
            }

            if (!empty($develop_codes)) {
                $this->purchase_db->where_in('product.create_user_name', $develop_codes);
            }
        }
        // 供应商等级
        if( isset($clientDatas['supplier_level']) && !empty($clientDatas['supplier_level'])){
            $query->group_start();

            $query->where_in("supp1.supplier_level",$clientDatas['supplier_level']);
            $query->where_in("supp1.supplier_level",$clientDatas['supplier_level']);


            $query->or_where_in("(supp.supplier_level",$clientDatas['supplier_level']);
            $query->where_in("supp1.supplier_level",$clientDatas['supplier_level'])->where(" 1=1 )");

            $query->group_end();

        }
        $query->group_by("alternative.sku,alternative.supplier_code");
        $totalQuery = clone $query;

        $result = $query->select("
                    supp.supplier_source as supplier_source,
                    supp1.supplier_source as supplier_source1,
                    alternative.id,
                    product.sku,
                    product.product_name, 
                    product.product_status,
                    product.is_purchasing,
                    product.product_line_id,
                    product.supplier_name as psupplier_name,
                    product.supplier_code as psupplier_code,
                    alternative.supplier_code as asupplier_code,
                    alternative.supplier_name as asupplier_name,
                    product.purchase_price,
                    product.product_cn_link,
                    product.starting_qty,
                    alternative.starting_qty_unit as alstarting_qty,
                    product.devliy,
                    alternative.delivery as aldevliy,
                    product.is_shipping,
                    product.product_line_id,
                    alternative.is_shipping as alter_shipping,
                    alternative.update_time,
                    alternative.update_user,
                    alternative.purchase_price as alpurchase_price,alternative.source_from,
                    alternative.is_purchasing as alis_purchasing,
                    product.create_user_name,supp.supplier_level")->limit($clientDatas['limit'], $clientDatas['offset'])->get()->result_array();
        if(!empty($result)){

            $defaultSupplierCode = array_column($result,'psupplier_code','sku'); // 获取SKU 对应的默认供应商CODE
            $alternativeSupplierCodes = array_column($result,'asupplier_code'); // 获取SKU 备货选供应商
            // 合并默认供应商和备选供应商CODE数据
            $supplierCods = array_values(array_merge($defaultSupplierCode,$alternativeSupplierCodes));
            // 获取供应商信息
            $supplierMessage = $this->alternative_supplier($supplierCods);
            // 获取结算方式
            foreach($result as $key=>&$value){

                if(empty($value['asupplier_code']) && empty($value['asupplier_name'])){
                    $value['asupplier_code'] = $value['psupplier_code'];
                    $value['asupplier_name'] = $value['psupplier_name'];
                }
               // $value['product_line_datas'] = $this->product_line->get_product_line_name($value['product_line_id']);
                $category_all = $this->product_line_model->get_all_parent_category($value['product_line_id']);
                if(!empty($category_all) && !empty($category_all[0])){

                    $value['product_line_datas'] = $category_all[0]['product_line_name'];
                }
                $value['relationship'] = (isset($defaultSupplierCode[$value['sku']])
                    && !empty($defaultSupplierCode[$value['sku']])
                    && $defaultSupplierCode[$value['sku']] == $value['asupplier_code'])?"默认":"备选";
                if($value['psupplier_code'] == $value['asupplier_code']){
                    $oldpaymentData = $this->Supplier_model->get_supplier_payment($value['psupplier_code']);

                    $value['source'] = "产品列表";
                    $value['settlement_ch'] =$oldpaymentData;// 结算方式
                    $value['supplier_code'] = $value['psupplier_code'];
                    $value['supplier_name'] = $value['psupplier_name'];
                    $value['is_purchasing'] = $value['is_purchasing'];
                    $value['starting_qty'] = $value['starting_qty'];
                    // 如果数据来源为产品列表
                    $value['is_shipping'] = $value['is_shipping'];
                }else{
                    if($value['source_from'] == 1){
                        $value['source'] = "采购添加";
                        $value['is_shipping'] = $value['alter_shipping'];
                    }else if($value['source_from'] == 2){

                        $value['source'] = "开发添加";
                        $value['is_shipping'] = $value['alter_shipping'];
                    }else if($value['source_from'] == 3){
                        $value['source'] = '历史采购';
                        $value['is_shipping'] = NULL;
                    }else{
                        $value['source'] = 'sku换绑';
                        $value['is_shipping'] = $value['alter_shipping'];
                    }
                    $oldpaymentData = $this->Supplier_model->get_supplier_payment($value['asupplier_code']);
                    $value['settlement_ch'] = $oldpaymentData;// 结算方式
                    $value['supplier_code'] = $value['asupplier_code'];
                    $value['supplier_name'] = $value['asupplier_name'];
                    $value['purchase_price'] = $value['alpurchase_price'];
                    $value['is_purchasing'] = $value['alis_purchasing'];
                    $value['starting_qty'] = $value['alstarting_qty'];
                    $value['devliy'] = $value['aldevliy'];
                    $value['supplier_source'] = $value['supplier_source1'];
                }
                $value['product_status_ch'] = getProductStatus($value['product_status']);//产品状态

                $supplierMess = $this->purchase_db->from("supplier")->where("supplier_code",$value['supplier_code'])
                    ->get()->row_array();
                $value['cooper_status_ch'] = getCooperationStatus($supplierMess['status']);
                $value['supplier_source_ch'] = getSupplierSource($value['supplier_source']);
                if($value['supplier_source_ch'] == "备用"){
                    $value['supplier_source_ch'] = "临时";
                }
                $ordersDatas = $this->get_sku_supplier_orders($value['sku'],$value['supplier_code']);
                if(!empty($ordersDatas)){
                    $value['desc_audit_time'] = $ordersDatas['audit_time'];
                    $value['confirm_amount'] = $ordersDatas['confirm_amount'];
                    if($value['relationship'] =="备选") {
                        $value['source'] = '历史采购';
                    }
                }else{
                    $value['desc_audit_time'] = '';
                    $value['confirm_amount'] = '';
                }

                if($value['is_shipping'] == NULL){

                    $value['is_shipping_ch'] = '';
                }else{

                    $value['is_shipping_ch'] = ($value['is_shipping']==1)?"是":"否";
                }

                $value['is_purchasing_ch'] = get_enum($value['is_purchasing'],'PUR_IS_PURCHASING_DATA');
                // 获取采购员
                $supplier_buyer_list = $this->supplier_buyer_model->get_buyer_list($value['supplier_code']);
                $supplier_buyer_user = '';
                if(!empty($supplier_buyer_list)){
                    foreach ($supplier_buyer_list as $k => $val) {
                        if(PURCHASE_TYPE_INLAND == $val['buyer_type']){
                            $buyer_type_name = '国内仓';
                        }elseif(PURCHASE_TYPE_OVERSEA == $val['buyer_type']){
                            $buyer_type_name = '海外仓';
                        }elseif(PURCHASE_TYPE_FBA_BIG == $val['buyer_type']){
                            $buyer_type_name = 'FBA大货';
                        }elseif(PURCHASE_TYPE_FBA == $val['buyer_type']){
                            $buyer_type_name = 'FBA';
                        }else{
                            $buyer_type_name = '未知';
                        }
                        $supplier_buyer_user.= $buyer_type_name.':'.$val['buyer_name'].',';

                    }
                }
                $value['supplier_buyer_user'] = $supplier_buyer_user;
                $value['supplier_level_ch'] =getSupplierLevel($value['supplier_level']);

            }
        }
        return [
            'list'=>$result, // 查询结果
            'page' =>[
                'total' => $totalQuery->select("alternative.id")->count_all_results(),
                'limit' => $clientDatas['limit'],
                'offset' => $clientDatas['offset']+1
            ]
        ];
    }

    /**
     * 根据特定条件查询备货数据，不用备选接口因为改接口数据查询 表多
     * @params $params   array   更新SKU 备选供应商信息
     * @author:luxu
     * @time:2021年4月23号
     **/
    public function get_alternative_datas($params){
        try{
            foreach($params as $key=>$value){
                $result = $this->purchase_db->from("alternative_suppliers")->where("id!=",$value['id'])->where("sku",$value['sku'])
                    ->where("supplier_code",$value['supplier_code'])->get()->row_array();
                if(!empty($result)){
                    throw new Exception("sku:".$value['sku'].",供应商:".$value['supplier_name'].". 已经存在");
                }
            }
            return True;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 更新备货单信息
     * @params $params   array   更新SKU 备选供应商信息
     * @author:luxu
     * @time:2021年4月23号
     **/

    public function save_alternative_supplier($params){

        $result = $this->purchase_db->update_batch('alternative_suppliers',$params,'id');
        return $result;
    }

    /**
     * 获取备选供应商审核数据接口，33100 备选供应商列表新增子页面"备选供应商待审核"
     * @author:luxu
     * @time:2021年4月24号
     **/
    public function alternative_supplier_examine($params){
        try{

            $query = $this->purchase_db->from("alternative_supplier_log AS logs")
                ->join("pur_product as product","logs.sku=product.sku","LEFT")->where("logs.audit_status",0);
            $query->join("supplier as supp","logs.supplier_code=supp.supplier_code","LEFT");

            // SKU 查询
            if( isset($params['sku']) && !empty($params['sku'][0])){

                $query->where_in("logs.sku",$params['sku']);
            }
            // 申请人ID
            if( isset($params['apply_user_id']) && !empty($params['apply_user_id'][0])){

                $query->where_in("logs.apply_user_id",$params['apply_user_id']);
            }

            // 供应商来源

            if( isset($params['supplier_source']) && !empty($params['supplier_source'])){
                $query->where_in("supp.supplier_source", $params['supplier_source']);
            }

            if( isset($params['cooper_status']) && !empty($params['cooper_status'][0])){
                $query->where_in("supp.status",$params['cooper_status']);
            }

            // 一级产品线

            if( isset($params['product_line_id']) && !empty($params['product_line_id'][0])){
                $category_all_ids = $this->product_line->get_all_category($params['product_line_id']);
                $children_ids = explode(",", $category_all_ids);
                $children_ids = array_filter($children_ids);
                $query->where_in("product.product_line_id",$children_ids);
            }

            //申请时间

            if( isset($params['apply_time_start']) && !empty($params['apply_time_start'])){
                $query->where("logs.apply_time>=",$params['apply_time_start']);
            }

            if( isset($params['apply_time_end']) && !empty($params['apply_time_end'])){
                $query->where("logs.apply_time<=",$params['apply_time_end']);
            }

            //  供应商
            if( isset($params['supplier_code']) && !empty($params['supplier_code'])){
                $query->where_in("logs.supplier_code",$params['supplier_code']);
            }

            // 变更类型

            if( isset($params['change_type']) && !empty($params['change_type'])){

                $query->where_in("logs.change_type",$params['change_type']);
            }


            // 是否包邮

            if( isset($params['is_shipping']) && !empty($params['is_shipping'])){

                $query->where("logs.new_is_shipping",$params['is_shipping']);
            }

            // 是否代采
            if( isset($params['is_purchaseing']) && !empty($params['is_purchaseing'])){

                $query->where("logs.new_is_purchasing",$params['is_purchaseing']);
            }
            $totalquery = clone $query; // 复制链接用于统计
            $result =  $query->select("logs.*,product.product_line_id")->limit($params['limit'], $params['offset'])->get()->result_array();
            //echo $query->last_query();die();
            if(!empty($result)){

                // 统计备货供应商是新增加的SKU，在到产品管理表里面去查询，最小起订量，交期，是否包邮 等默认数据
               // $change_type_data = array_map(function($data){if($data['change_type'] == 1){return $data['sku'];}},$result);
                $change_type_data = array_column($result,'sku');
                // 如果数据不为空
                if(!empty($change_type_data)){

                    $productMessage = $this->product->get_product_data($change_type_data,"product_status,
                    purchase_price,
                    is_purchasing,
                    original_start_qty,
                    devliy,sku,product_line_id");
                    $skuDatas = array_column($productMessage,NULL,"sku");
                }
                $supplierCods = array_column($result,"supplier_code");
                $supplierMessage = $this->alternative_supplier($supplierCods);
                foreach($result as $key=>&$value){
                    // 如果是新增加
                    if( $value['change_type'] == 1){
                        // SKU 默认是否代采
                        $value['def_is_purchasing'] = isset($skuDatas[$value['sku']])?$skuDatas[$value['sku']]['is_purchasing']:0;
                        $value['def_is_purchasing_ch'] = get_enum($value['def_is_purchasing'],"PUR_IS_PURCHASING_DATA"); // 是否代采
                        // 默认起订量
                        $value['def_original_start_qty'] = isset($skuDatas[$value['sku']])?$skuDatas[$value['sku']]['original_start_qty']:0;
                        // 默认交期
                        $value['def_devliy'] = isset($skuDatas[$value['sku']])?$skuDatas[$value['sku']]['devliy']:0;
                        // 默认未税单价
                        $value['def_purchase_price'] =  isset($skuDatas[$value['sku']])?$skuDatas[$value['sku']]['purchase_price']:0;
                    }
                    $value['change_type_ch'] = get_enum($value['change_type']);
                    $value['product_status_ch'] = getProductStatus($skuDatas[$value['sku']]['product_status']);//产品状态
                    $value['is_purchasing_ch'] = get_enum($value['new_is_purchasing'],"PUR_IS_PURCHASING_DATA"); // 是否代采
                    $value['product_line_datas'] = $this->product_line->get_product_line_name($skuDatas[$value['sku']]['product_line_id']);
                    $value['relationship'] = "备选";
                    $value['settlement_ch'] = $supplierMessage[$value['supplier_code']]['settlement_name'];// 结算方式
                    $value['cooper_status_ch'] = getCooperationStatus($supplierMessage[$value['supplier_code']]['status']);
                    $value['supplier_source_ch'] = getSupplierSource($supplierMessage[$value['supplier_code']]['supplier_source']);
                    $value['audit_status_ch'] = get_enum($value['audit_status'],'PUR_ALTERNATIVE_AUDIT_DATA'); // 审核状态
                    // product_line_datas
                    $value['product_line_datas'] = $this->product_line->get_product_line_name($value['product_line_id']);
                    $value['new_is_shipping_ch'] = get_enum($value['new_is_shipping'],'PUR_IS_PURCHASING_DATA');
                    $value['old_is_shipping_ch'] = get_enum($value['old_is_shipping'],'PUR_IS_PURCHASING_DATA');




                }
            }

            return [
                'list' => $result,
                'page' =>[
                    'total' => $totalquery->count_all_results(),
                    'limit' => $params['limit'],
                    'offset' => $params['offset']+1
                ]
            ];
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 备选供应商审核
       3.审核通过时,备选供应商列表的数据同步更新
       4.审核驳回的,通过消息中心,弹窗通知申请人,弹窗信息:"备选供应商列表,SKU****供应商******审核被驳回,驳回原因 *******"
     * @author:luxu
     * @time:2021年4月24号
     **/

    public function audit_alternative_supplier($clientDatas){

        // 审核通过
        $result = $this->purchase_db->from("alternative_supplier_log")->where_in("id",$clientDatas['id'])->get()->result_array();
        if(empty($result)){
            //如果查询数据为空,抛出异常
            throw new Exception("数据不存在");
        }
        // 获取新增数据
        $new_insert_data = array_map(function($data){
            if($data['type'] == 0){
                return $data;
            }
        },$result);

        if($clientDatas['audit_status'] == 1){
            if(!empty($new_insert_data)){
                $inserDatas = $pushDatas= [];
                foreach($new_insert_data as $insert_key=>$insert_value){

                    if($insert_value['change_type'] == 1) {
                        $inserDatas[] = [
                            'sku' => $insert_value['sku'],
                            'supplier_name' => $insert_value['supplier_name'], //  供应商名称
                            'supplier_code' => $insert_value['supplier_code'], // 供应商CODE
                            'purchase_price' => $insert_value['new_product_price'], // 未税单价
                            'starting_qty_unit' => $insert_value['new_starting_qty_unit'], // 最小起订量
                            'is_shipping' => $insert_value['new_is_shipping'], // 是否包邮
                            'delivery' => $insert_value['new_delivery'], // 交期
                            'url' => $insert_value['new_url'], // 采购连接
                            'audit_status' => 1, // 审核通过
                            'source_from' => 1, //数据来源采购申请
                        ];
                    }

                    if($insert_value['change_type'] == 2){

                        $updateDatas[] = [
                            'sku' => $insert_value['sku'],
                            'supplier_name' => $insert_value['supplier_name'], //  供应商名称
                            'supplier_code' => $insert_value['supplier_code'], // 供应商CODE
                            'purchase_price' => $insert_value['new_product_price'], // 未税单价
                            'starting_qty_unit' => $insert_value['new_starting_qty_unit'], // 最小起订量
                            'is_shipping' => $insert_value['new_is_shipping'], // 是否包邮
                            'delivery' => $insert_value['new_delivery'], // 交期
                            'url' => $insert_value['new_url'], // 采购连接
                            'audit_status' => 1, // 审核通过
                            'source_from' => 1, //数据来源采购申请
                            'update_time' => date("Y-m-d H:i:s",time()),
                            'update_user' => $insert_value['apply_user']
                        ];
                    }

                    $logsData = [
                        'sku' => $insert_value['sku'],
                        'supplier_code' => $insert_value['supplier_code'], // 供应商CODE
                        'supplier_name' => $insert_value['supplier_name'], // 供应商名称
                        'operation_user' => getActiveUserName(),
                        'operation_time' => date("Y-m-d H:i:s",time()),
                        'type' =>$insert_value['type'],
                        'data_type'=>$insert_value['change_type'],
                        'audit_type' =>2,
                        'remark' =>$clientDatas['remark']
                    ];
                    $this->add_alternative_logs($logsData);

                }

                if(!empty($inserDatas) || !empty($updateDatas)){
                    if(!empty($inserDatas)) {
                        $result = $this->purchase_db->insert_batch('alternative_suppliers', $inserDatas);
                        foreach ($inserDatas as $inserDataValue) {
                            $alterdef = $this->purchase_db->from("alternative_suppliers")->where("sku", $inserDataValue['sku'])
                                ->where("source_from", 5)->select("id")->get()->row_array();
                            if (empty($alterdef)) {
                                $defProduct = $this->purchase_db->from("product")->where("sku", $inserDataValue['sku'])->select("sku,supplier_code,supplier_name")
                                    ->get()->row_array();
                                if (!empty($defProduct)) {
                                    $defDatas = [
                                        'sku' => $defProduct['sku'],
                                        'supplier_code' => $defProduct['supplier_code'],
                                        'supplier_name' => $defProduct['supplier_name'],
                                        'source_from' => 5
                                    ];
                                    $this->purchase_db->insert('alternative_suppliers', $defDatas);
                                }
                            }
                        }
                    }
                    if(!empty($updateDatas)){
                        foreach($updateDatas as $updateValue){
                            $result = $this->purchase_db->where("sku",$updateValue['sku'])->where("supplier_code",$updateValue['supplier_code'])
                                ->update("alternative_suppliers",$updateValue);}

                    }

                    if($result){
                        $update['audit_status'] =1;
                        if(is_array($clientDatas['id'])){
                            $this->purchase_db->where_in("id",$clientDatas['id'])->update("alternative_supplier_log",$update);
                        }else{
                            $this->purchase_db->where("id",$clientDatas['id'])->update("alternative_supplier_log",$update);
                        }
                    }
                }
            }
        }else{
            foreach($new_insert_data as $insert_key=>$insert_value){

                $logsData = [
                    'sku' => $insert_value['sku'],
                    'supplier_code' => $insert_value['supplier_code'], // 供应商CODE
                    'supplier_name' => $insert_value['supplier_name'], // 供应商名称
                    'operation_user' => getActiveUserName(),
                    'operation_time' => date("Y-m-d H:i:s",time()),
                    'type' =>$insert_value['type'],
                    'data_type'=>$insert_value['change_type'],
                    'audit_type' =>3,
                    'remark' =>$clientDatas['remark']
                ];
                $this->add_alternative_logs($logsData);
            }
                // 如果驳回操作
            $update['audit_status'] = 2;
            $result = $this->purchase_db->update('alternative_supplier_log',$update);


            $this->Message_model->AcceptMessage('alternative', ['data' => $clientDatas['id'], 'message' => $clientDatas['remark'], 'user' => getActiveUserName(), 'type' => '备选供应商审核']);

        }
        if($result){
            /*
             * 如果是审核通过，推送到产品系统
             * [{“sku”:”oxxx1”,”supplier_name”:”111”,”supplier_code”:”222”,”purchase_price”:12.3,”starting_qty_unit”:12,”delivery”:10,”url”:”xxx”}]
            */
            $this->load->library('Rabbitmq');
            if($clientDatas['audit_status'] == 1){

                if(empty($inserDatas)){

                    $inserDatas = $updateDatas;
                }
                foreach($inserDatas as $key=>&$value){
                    unset($value['audit_status']);
                    unset($value['is_shipping']);
                    if(isset($value['update_time'])){
                        unset($value['update_time']);
                    }

                    if(isset($value['update_user'])){
                        unset($value['update_user']);
                    }
                }

                //创建消息队列对象
                $mq = new Rabbitmq();
                //设置参数
                $mq->setQueueName('ALTERNATIVE_SUPPLIERS_TO_PRODUCT'); //  DEMAND_STOCKLIST_DATA
                $mq->setExchangeName('ALTERNATIVE');  //STOCKLIST
                $mq->setRouteKey('ALTERNATIVE_SUPPLIERS_KEY'); // DEMAND_STOCKLIST_DATA_KEY
                $mq->setType(AMQP_EX_TYPE_DIRECT);
                //存入消息队列
                $result = $mq->sendMessage($inserDatas);
            }
            return True;
        }
        return False;
    }

    public function pushMq($inserDatas){
        $this->load->library('Rabbitmq');

        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('ALTERNATIVE_SUPPLIERS_TO_PRODUCT'); //  DEMAND_STOCKLIST_DATA
        $mq->setExchangeName('ALTERNATIVE');  //STOCKLIST
        $mq->setRouteKey('ALTERNATIVE_SUPPLIERS_KEY'); // DEMAND_STOCKLIST_DATA_KEY
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        //存入消息队列
        $result = $mq->sendMessage($inserDatas);
    }

    public function product_audit_alternative_log($data,$type=NULL){

        $alterResult = $this->purchase_db->from("alternative_suppliers")->where("sku",$data['sku'])->where("is_show!=2")
            ->get()->row_array();
        if(NULL != $type && "product_audit" == $type){

            $data['source_from'] = 4;
        }

        $productMessage = $this->purchase_db->from("product")->where("sku",$data['sku'])->get()->row_array();
        $insertData = [];
        if (!empty($productMessage)) {
            $insertData[] = [
                'sku' => $productMessage['sku'],
                'supplier_name' => $productMessage['supplier_name'],
                'supplier_code' => $productMessage['supplier_code'],
                'purchase_price' => $productMessage['purchase_price'],
                'starting_qty_unit' => $productMessage['starting_qty'],
                'is_shipping' => $productMessage['is_shipping'],
                'delivery' => (!empty($productMessage['devliy']) && $productMessage['devliy'] !=NULL)?$productMessage['devliy']:0,
                'is_purchasing' => $productMessage['is_purchasing'],
                'url' => !empty($productMessage['product_link'])?$productMessage['product_link']:'',
                'source_from' => isset($data['source_from']) ? $data['source_from'] : 2,
                'audit_status' => 1,
                'is_show'=>1,
                'update_time' => date("Y-m-d H:i:s",time())
            ];
        }

        $insertData[] = [
            'sku' => $data['sku'],
            'supplier_name' => $data['old_supplier_name'],
            'supplier_code' => $data['old_supplier_code'],
            'purchase_price' => $data['old_supplier_price'],
            'starting_qty_unit' => $data['old_starting_qty'],
            'is_shipping' => isset($data['is_old_shipping']) ? $data['is_old_shipping'] : '',
            'delivery' => (!empty($data['old_devliy']) && $data['old_devliy']!=NULL)?$data['old_devliy']:0,
            'is_purchasing' => isset($data['old_is_purchasing']) ? $data['old_is_purchasing'] : '',
            'url' => !empty($data['old_product_link'])?$data['old_product_link']:'',
            'source_from' => isset($data['source_from']) ? $data['source_from'] : 2,
            'audit_status' => 1,
            'is_show'=>1,
            'update_time' => date("Y-m-d H:i:s",time())
        ];
        $this->purchase_db->insert_batch('alternative_suppliers',$insertData);



    }

    /**
     * 产品管理修改供应商写入到备选供应商表
     * @params   $data   array   备选供应商数据
     * @author:luxu
     * @time:2021年4月26号
     **/
    public function product_audit_alternative($data,$type=NULL){

        $alterResult = $this->purchase_db->from("alternative_suppliers")->where("sku",$data['sku'])->where("is_show!=2")
            ->get()->row_array();
        if(NULL != $type && "product_audit" == $type){

            $data['source_from'] = 4;
        }

        $productMessage = $this->purchase_db->from("product")->where("sku",$data['sku'])->get()->row_array();
        if(empty($productMessage)){
            $insertData[] = [
                'sku' => $data['sku'],
                'supplier_name' => $data['new_supplier_name'],
                'supplier_code' => $data['new_supplier_code'],
                'purchase_price' => $data['new_supplier_price'],
                'starting_qty_unit' => $data['new_starting_qty'],
                'is_shipping' => isset($data['is_new_shipping']) ? $data['is_new_shipping'] : '',
                'delivery' => !empty($data['new_devliy'])?$data['new_devliy']:0,
                'is_purchasing' => isset($data['new_is_purchasing']) ? $data['new_is_purchasing'] : '',
                'url' => $data['new_product_link'],
                'source_from' => isset($data['source_from']) ? $data['source_from'] : 2,
                'audit_status' => 1,
                'is_show'=>2,
                'update_time' => date("Y-m-d H:i:s",time()),
                'update_user' => getActiveUserName(),
            ];
        }else {
            if (empty($alterResult)) {
                if (!empty($productMessage)) {
                    $insertData[] = [
                        'sku' => $productMessage['sku'],
                        'supplier_name' => $productMessage['supplier_name'],
                        'supplier_code' => $productMessage['supplier_code'],
                        'purchase_price' => $productMessage['purchase_price'],
                        'starting_qty_unit' => $productMessage['starting_qty'],
                        'is_shipping' => $productMessage['is_shipping'],
                        'delivery' => !empty($productMessage['devliy'])?$productMessage['devliy']:0,
                        'is_purchasing' => $productMessage['is_purchasing'],
                        'url' => $productMessage['product_link'],
                        'source_from' => isset($data['source_from']) ? $data['source_from'] : 2,
                        'audit_status' => 1,
                        'is_show'=>1
                    ];
                }
            }


            $insertData[] = [
                'sku' => $data['sku'],
                'supplier_name' => $data['new_supplier_name'],
                'supplier_code' => $data['new_supplier_code'],
                'purchase_price' => $data['new_supplier_price'],
                'starting_qty_unit' => $data['new_starting_qty'],
                'is_shipping' => isset($data['is_new_shipping']) ? $data['is_new_shipping'] : '',
                'delivery' => !empty($data['new_devliy'])?$data['new_devliy']:0,
                'is_purchasing' => isset($data['new_is_purchasing']) ? $data['new_is_purchasing'] : '',
                'url' => $data['new_product_link'],
                'source_from' => isset($data['source_from']) ? $data['source_from'] : 2,
                'audit_status' => 1,
                'is_show'=>1,
            ];
        }

        $this->purchase_db->insert_batch('alternative_suppliers',$insertData);
    }

    /**
     * 备选供应商日志获取
     * @method:get
     * @author:luxu
     * @time:2021年4月27号
     **/
    public function get_alternative_log($skus){

        $alternativeLogs = $this->purchase_db->from("alternative_operation")->where("sku",$skus)
            ->get()->result_array();

        if(!empty($alternativeLogs)){

            foreach($alternativeLogs as $key=>&$value){
                $types = $this->purchase_db->from("product")->where("sku",$value['sku'])->select("supplier_code")->get()->row_array();
                if(empty($types) || $types['supplier_code']!=$value['supplier_code']){

                    $value['alternative_type'] = "备选";
                }else{
                    $value['alternative_type'] = "默认";
                }

                if($value['type'] == 1){

                    $value['type_ch'] = "新增";
                }else{
                    $value['type_ch'] = "修改";
                }

                if($value['data_type'] == 1){

                    $value['data_type_ch'] = "采购添加";
                }

                if($value['data_type'] == 2){

                    $value['data_type_ch'] = "sku换绑";
                }

                if($value['data_type'] == 3){

                    $value['data_type_ch'] = "开发添加";
                }

                if( $value['audit_type'] == 1){

                    $value['audit_type_ch'] = "待审核";
                }

                if( $value['audit_type'] == 2){

                    $value['audit_type_ch'] = "审核通过";
                }

                if( $value['audit_type'] == 3){

                    $value['audit_type_ch'] = "审核驳回";
                }
            }

            // SKU 换绑数据
            $product_logs = $this->purchase_db->from("product_update_log")->where("sku",$skus)->where("new_supplier_code!=old_supplier_code")
                ->where("audit_status",3)->select("sku,new_supplier_code as supplier_code,new_supplier_name AS supplier_name,
                audit_time AS operation_time,create_user_name as operation_user,audit_remark as remark")->get()->result_array();
            if(!empty($product_logs)){
                foreach($product_logs as $logs_key=>&$logs_value) {
                    $types = $this->purchase_db->from("product")->where("sku", $logs_value['sku'])->select("supplier_code")->get()->row_array();
                    if (empty($types) || $types['supplier_code'] != $logs_value['supplier_code']) {

                        $logs_value['alternative_type'] = "备选";
                    } else {
                        $logs_value['alternative_type'] = "默认";
                    }

                    $logs_value['audit_type_ch'] = $logs_value['type_ch'] = '';
                    $logs_value['data_type_ch'] = 'sku换绑';
                    $logs_value['audit_type_ch'] = "审核通过";
                }
            }

            $result = array_merge($product_logs,$alternativeLogs);
            return $result;
        }
    }
}