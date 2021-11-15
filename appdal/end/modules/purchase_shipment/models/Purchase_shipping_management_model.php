<?php
/**
 * 发运管理
 * @time:2020/4/27
 * @author:luxu
 **/
class Purchase_shipping_management_model extends Purchase_model
{

    private $shippingData = "shipment_track_list";// 发运管理主表
    private $shippingWhere = NULL;
    protected $table_shipment_track = 'shipment_track_list';//发运跟踪列表
    protected $newDemandNumber = NULL; // 发运跟踪表新备货单维度查询数据缓存

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('others/Plan_system_model');

        // 发运管理表查询条件

        $this->shippingWhere = [
            'main'=>
                [  'databasetype' => $this->shippingData,
                    'as' => 'shipping.',
                    'where'=>
                        [
                            ['search'=>'demand_number','flag'=>'in'], //查询备货单号
                            ['search'=>'new_demand_number','flag'=>'in'], //查询备货单号
                            ['search'=>'purchase_number','flag'=>'in'], // 查询采购单号
                            ['search' => 'supplier_code','flag'=>'in'], // 查询供应商
                            ['search' => 'audit_status','flag'=>'in'] , //发运类型审核状态
                            ['search' => 'deliver_status'] , // 二验交期是否已经确认
                            ['search' => 'destination_warehouse_code','flag'=>'in'], // 目的仓
                        ],
                    'searchData' => [
                        'id','new_demand_number','demand_number','purchase_number',
                        'supplier_name','supplier_code','sku','station_code',
                        'destination_warehouse_name'
                        ,'deliver_status,plan_qty,quantity_warehousing,es_arrival_time,is_can_change_time',
                        'create_time','update_time','audit_status','es_shipment_time','station_name','rerun_batch'
                    ], // 查询数据
                ],
        ];


    }

    /**
     * 组装MYSQL where 查询条件
     * @param  $MysqlQuery   Object  mysql 实例化对象
     *         $where        array   WHERE 条件
     * @author: luxu
     * @time:2020/4/17
     * @return Object
     **/

    private function getShippingManageWhere($MysqlQuery,$where){

        if(empty($where)){

            return $MysqlQuery;
        }

        if (isset($where['new_demand_number'])){
            $new_list = $old_list = [];
            foreach ($where['new_demand_number'] as $number){
                if (preg_match("/.*?(-1|-2)$/", $number, $matches)){
                    $new_list[] = $number;
                }else{
                    $old_list[] = $number;
                }
            }
            $where['new_demand_number'] = [];
            if (!empty($new_list)){
                $where['new_demand_number'] = $new_list;
            }
            if (!empty($old_list)){
                $where['demand_number'] = $old_list;

            }
        }

        $SearchMain = $this->shippingWhere['main']['where'];
        $searchData = array_map(function($search){

            return $this->shippingWhere['main']['as'].$search;
        },$this->shippingWhere['main']['searchData']);
        $searchData = implode(",",$searchData);
        $MysqlQuery->select($searchData);
        if(!empty($SearchMain)){
            foreach( $SearchMain as $searchkey=>$searchValue){
                if( isset( $where[ $searchValue['search'] ]) && !empty( $where[ $searchValue['search'] ])) {
                    if(isset($searchValue['flag'])  && $searchValue['flag'] == 'in' ){

                        $MysqlQuery->where_in($this->shippingWhere['main']['as'].$searchValue['search'],$where[ $searchValue['search'] ]);
                    }else{

                        $MysqlQuery->where($this->shippingWhere['main']['as'].$searchValue['search'],$where[ $searchValue['search'] ]);
                    }
                }else{

                }
            }
        }

        // 合同号

        if( isset( $where['compactnumbers']) && !empty($where['compactnumbers']) )
        {
            $MysqlQuery->join("purchase_compact_items AS compactitems","compactitems.purchase_number=shipping.purchase_number","LEFT")
                ->where_in("compactitems.compact_number",$where['compactnumbers']);
        }

        // 订单状态

        if( isset($where['orderstatus']) && !empty($where['orderstatus']) ){

            $MysqlQuery->where_in("orders.purchase_order_status",$where['orderstatus']);
        }

        // 是否退税

        if( isset($where['is_drawback']) && $where['is_drawback']!=NULL){
            if( $where['is_drawback'] == 2){

                $isdrawback = 0;
            }else{
                $isdrawback =1;
            }
            $MysqlQuery->where("orders.is_drawback",$isdrawback);
        }
        // 采购员

        if( isset($where['buyer_id']) && !empty($where['buyer_id'])){

            $MysqlQuery->where_in("orders.buyer_id",$where['buyer_id']);
        }

        // 审核时间

        if( isset($where['audit_start_time']) && isset($where['audit_end_time']) && $where['audit_end_time'] != NULL

             && $where['audit_start_time'] != NULL
         ){

            $MysqlQuery->where("orders.audit_time>=",$where['audit_start_time'])->where("orders.audit_time<=",$where['audit_end_time']);
        }

        // 发运类型审核状态

        if( isset($where['shipping_status_ch']) && $where['shipping_status_ch'] != NULL){

            $MysqlQuery->where("shipping.audit_status",$where['shipping_status_ch']);
        }
        // 预计到货时间
        if( isset($where['es_arrival_start_time']) && !empty($where['es_arrival_start_time'])){
            $MysqlQuery->where("shipping.es_arrival_time>=",$where['es_arrival_start_time']);
        }

        if( isset($where['es_arrival_end_time']) && !empty($where['es_arrival_end_time'])){
            $MysqlQuery->where("shipping.es_arrival_time<=",$where['es_arrival_end_time']);
        }

        // 预计发货时间
        if( isset($where['es_shipment_start_time']) && !empty($where['es_shipment_start_time'])){
            $MysqlQuery->where("shipping.es_shipment_time>=",$where['es_shipment_start_time']);
        }

        if( isset($where['es_shipment_end_time']) && !empty($where['es_shipment_end_time'])){
            $MysqlQuery->where("shipping.es_shipment_time<=",$where['es_shipment_end_time']);
        }

        //验货结果

        if( isset($where['check_status']) && !empty($where['check_status']) && $where['check_status'] != NULL) {

            if( $where['check_status'] == 3){

                $MysqlQuery->where("shipping.judgment_result",1);
            }


            if( $where['check_status'] ==4){

                $MysqlQuery->where("shipping.judgment_result",2);
            }

        }
        //计划数量

        if( isset($where['plan_qty']) && !empty($where['plan_qty'])){

            if( $where['plan_qty'] == 1){
                $MysqlQuery->where("shipping.plan_qty>",0);
            }else{
                $MysqlQuery->where("shipping.plan_qty",0);
            }
        }
        // 发运类型
        if( isset($where['shipment_type']) && !empty($where['shipment_type'])){

            $MysqlQuery->where("shipping.shipment_type",$where['shipment_type']);
        }
        // 备货单状态
        if( isset($where['suggest_status']) && $where['suggest_status'] !=NULL ){
            $MysqlQuery->where_in("suggest.suggest_order_status",$where['suggest_status']);
        }
        // 采购仓库
        if( isset($where['warehouse_code']) && !empty($where['warehouse_code'])){

            $MysqlQuery->where_in("orders.warehouse_code",$where['warehouse_code']);
        }

        // 验货时间

        if( isset($where['verify_start_time']) && !empty($where['verify_start_time'])){

            $MysqlQuery->where("shipping.check_time>=",$where['verify_start_time']);
        }

        if( isset($where['verify_end_time']) && !empty($where['verify_end_time'])){

            $MysqlQuery->where("shipping.check_time<=",$where['verify_end_time']);
        }

        // 更新时间
        if( isset($where['update_start_time']) && !empty($where['update_start_time'])){

            $MysqlQuery->where("shipping.update_start_time>=",$where['update_start_time']);
        }

        if( isset($where['update_end_time']) && !empty($where['update_end_time'])){

            $MysqlQuery->where("shipping.update_end_time<=",$where['update_end_time']);
        }

        //交期是否可以修改

        if( isset($where['is_can_change_time']) && !empty($where['is_can_change_time'])){
            $MysqlQuery->where("shipping.is_can_change_time",$where['is_can_change_time']);
        }


        // 物流类型

        if( isset($where['logisitic_type']) && !empty($where['logisitic_type'])){

            $MysqlQuery->where_in("logistics_type",$where['logisitic_type']);
        }

        $MysqlQuery->where("is_del",2);
        // 分页

        return $MysqlQuery;
    }

    public function getDemandPurchase($demandNumber){

        $result = $this->purchase_db->from("purchase_suggest_map as map")
            ->where_in("map.demand_number",array_unique($demandNumber))->select("map.purchase_number,map.demand_number")->get()->result_array();
        if(!empty($result)){
            $resultData = [];
            foreach( $result as $key=>$value){

                $resultData[$value['demand_number']][] = $value['purchase_number'];
            }

            return $resultData;
        }
        return $result;
    }


    /**
    获取备货单维度的入库数量
     **/
    public function getWareHouseData($demandNumbers){

        $demandPurchase = $this->getDemandPurchase($demandNumbers); // 获取备货对应的采购单信息
        if(!empty($demandPurchase)) {

            $purchaseNumbers = [];
            foreach( $demandPurchase as $key=>$value){
                if(!empty($value)){

                    foreach($value as $vKey=>$vValue){

                        $purchaseNumbers[] = $vValue;
                    }
                }
            }

            $warehouseData = $this->purchase_db->from("warehouse_results")->where_in("purchase_number", $purchaseNumbers)
                ->select(" SUM(instock_qty) AS instock_qty,purchase_number")->get()->result_array();
            if(!empty($warehouseData)){

                $warehouseData = array_column( $warehouseData,NULL,"purchase_number");

                foreach($warehouseData as $wareKey => $wareValue ){

                    foreach( $demandPurchase as $demandKey=>$demandValue){

                        if( in_array($wareKey,$demandValue) ){

                            $demandPurchase[$demandKey]['warehouseData'][] =$wareValue['instock_qty'];
                        }
                    }
                }

                $warehouseReturn = [];
                foreach( $demandPurchase as $key=>$value){

                    $warehouseReturn[$key] = isset($value['warehouseData'])?array_sum($value['warehouseData']):0;
                }

                return $warehouseReturn;
            }
        }

        return NULL;
    }

    /**
     * 获取SKU 商品详情体积信息
     * @param $skus   array    商品SKU
     * @author:luxu
     * @time:2020/4/18
     * @return array
     **/

    public function getProductVolume($skus){

        /**
         * 如果SKU 为空，就返回NULL
         **/
        if( empty($skus)){

            return NULL;
        }

        $result = $this->purchase_db->from("product")->where_in("sku",$skus)->select("sku,sample_package_length,sample_package_width,sample_package_heigth")
            ->get()->result_array();
        if(!empty($result))
        {
            $result  = array_column( $result,NULL,"sku");
            $resultData = [];
            foreach($result as $key=>$value){

                $resultData[$key] = number_format(($value['sample_package_length']*$value['sample_package_width']*$value['sample_package_heigth']),3,".","");
            }
            return $resultData;
        }

        return NULL;
    }

    /**
     * 获取备货单验货日期和验货结果
     * @param $demandNumbers   array   备货单信息
     * @author:luxu
     * @time:2020/4/29
     **/

    public function getCheckData($demandNumbers){

        $result = $this->purchase_db->from("supplier_check_sku  AS checksku")->join("supplier_check_record AS record","checksku.check_id=record.check_id")
            ->where_in("checksku.demand_number",$demandNumbers)->select("checksku.judgment_result AS checkstatus,record.check_time")
            ->group_by("checksku.demand_number")->get()->result_array();
        if(empty($result)){

            print_r($result);die();
        }

        return NULL;
    }

    /**
     * 获取备货单验货结果
     * @param: $demandNumber   array|string   备货单号
     * @author:luxu
     * @time:2020/7/7
     **/
    public function checkResultData($demandNumber){

        if( !empty($demandNumber) && is_array($demandNumber)){

            $demandNumber = array_map(function($data){

                return sprintf("'%s'",$data);
            },$demandNumber);
        }

        $demandNumber = implode(",",$demandNumber);
        $sql = "
             SELECT s1.* FROM pur_supplier_check_sku s1 JOIN (SELECT sku,substring_index(group_concat(batch_no ORDER BY
            batch_no DESC),',',1) AS batch_no FROM pur_supplier_check_sku WHERE demand_number IN (".$demandNumber.") GROUP BY sku) s2 ON s1.sku=s2.sku AND
            s1.batch_no=s2.batch_no
            ";

        $result  = $this->purchase_db->query($sql)->result_array();
        if(!empty($result)){

            $result = array_column($result,NULL,"demand_number");
        }

        return $result;
    }

    /**
     * 批量查下采购单SKU 的入库数量
     * @params : $purchaseNumber   string|array   采购单
     *           $sku              string|array   sku
     * @author:luxu
     * @time:2020/7/7
     **/
    public function getPurchaseWarehouseData($purchaseNumber = NULL ,$skus = NULL ){
        $query = $this->purchase_db->from("warehouse_results_main");
        if(!empty($purchaseNumber) && is_array($purchaseNumber)){

            $query->where_in("purchase_number",$purchaseNumber);
        }else{
            $query->where("purchase_number",$purchaseNumber);
        }

        if(!empty($skus) && is_array($skus)){

            $query->where_in("sku",$skus);
        }else{

            $query->where("sku",$skus);
        }

        $result = $query->select("SUM(instock_qty) AS instock_qty,SUM(breakage_qty) AS breakage_qty,purchase_number,sku")->group_by("purchase_number,sku")->get()->result_array();

        $resultData = [];
        if(!empty($result)){

            foreach($result as $key=>$value){

                $keys = $value['purchase_number']."-".$value['sku'];
                if( !isset($resultData[$keys])){

                    $resultData[$keys] = [];
                }

                $resultData[$keys] = [

                    'instock_qty' =>$value['instock_qty'],
                    'breakage_qty' => $value['breakage_qty']
                ];
            }
        }
        return $resultData;
    }


    /**
     * 获取发运管理数据
     * @param  $where   array   参数
     * @author: luxu
     * @time:2020/4/17
     **/
    public function getShippingManagementData($where,$import=False){

        $SearchQuery = $this->purchase_db->from($this->shippingWhere['main']['databasetype']." as shipping")->select("shipping.audit_status AS shipping_status,
        shipping.judgment_result AS checkstatus,
        shipping.check_time
        ,shipping.upselft_number");
        $where['limit'] = isset($where['limit'])?$where['limit']:20;
        $where['page']  = isset($where['offset'])?$where['offset']:1;
        $where['offsets'] = (!isset($where['offsets']))?($where['page'] - 1) *  $where['limit']:$where['offsets'];

        $SearchQuery->join("purchase_order AS orders","orders.purchase_number=shipping.purchase_number","LEFT")
            ->select("orders.is_drawback,purchase_order_status,
            orders.warehouse_code,
            orders.buyer_name,shipping.shipment_type,
            orders.buyer_name AS buyername,orders.audit_time,shipping.plan_update_time");
        // 从采购单明细表中获取采购数量，SKU 图片，入库数量
        $SearchQuery->join("purchase_order_items AS items","items.purchase_number=shipping.purchase_number AND items.sku=shipping.sku","LEFT")
            ->select("items.confirm_amount,items.product_img_url,items.upselft_amount");
        $SearchQuery->join("logistics_logistics_type AS logistics","shipping.logistics_type=logistics.type_code","LEFT")->select("logistics.type_name");
        $SearchQuery->join(" purchase_suggest AS suggest","suggest.demand_number=shipping.demand_number")->select("suggest.suggest_order_status,
        suggest.logistics_type
        ");

        $SearchQuery  = $this->getShippingManageWhere($SearchQuery,$where);
        $sumNumber = clone $SearchQuery;
        $SearchQuery->limit($where['limit'],$where['offsets']);
        $SearchResult = $SearchQuery->select("shipping.create_time")->order_by('orders.audit_time','DESC')->get()->result_array();

        if(!empty($SearchResult)){
            $demandNumbers = array_column( $SearchResult,"demand_number");
            $purchaseNumbers = array_column( $SearchResult,"purchase_number");
            $skus = array_column( $SearchResult,"sku");
            $skuVolumes = $this->getProductVolume($skus);
            $WarehouseNumbers = $this->getWareHouseData($demandNumbers);
            $compactNumbers = $this->Purchase_order_model->get_compact_number_list($purchaseNumbers);
            $purchaseNumbersData = array_column($SearchResult,"purchase_number");
            $skusData  = array_column($SearchResult,"sku");
            $warehouseData = $this->getPurchaseWarehouseData($purchaseNumbersData,$skusData);
            $demandNumbersData = array_column($SearchResult,"demand_number");
            $checkData = $this->checkResultData($demandNumbersData);
            foreach($SearchResult as $key=>$value){

                // 备货单维度入库数量
                $SearchResult[$key]['warehouse_numbers'] = isset( $WarehouseNumbers[$value['demand_number']])?$WarehouseNumbers[$value['demand_number']]:'';
                // 是否退税
                if( $value['is_drawback'] == 0){

                    $SearchResult[$key]['is_drawback_ch'] = '否';
                }else{
                    $SearchResult[$key]['is_drawback_ch'] = '是';
                }
                // 验货结果 1-免检驳回，2-免检，3-合格，4-不合格，5-转IQC


                if($value['checkstatus'] == 1){
                    $SearchResult[$key]['checkstatus_ch'] = '合格';
                }else if($value['checkstatus'] == 2){
                    $SearchResult[$key]['checkstatus_ch'] = '不合格';
                }else{
                    $SearchResult[$key]['checkstatus_ch'] = '';
                }

                if( $value['plan_update_time'] == '0000-00-00 00:00:00' || empty($value['plan_update_time'])){

                    $SearchResult[$key]['update_time'] = '';
                }else{
                    $SearchResult[$key]['update_time'] = $value['plan_update_time'];
                }

                if( $value['check_time'] == '0000-00-00 00:00:00' || empty($value['check_time'])){

                    $SearchResult[$key]['check_time'] = '';
                }

                if( $value['es_arrival_time'] == '0000-00-00 00:00:00' || empty($value['es_arrival_time'])){

                    $SearchResult[$key]['es_arrival_time'] = '';
                }

                if( $value['es_shipment_time'] == '0000-00-00 00:00:00' || empty($value['es_shipment_time'])){

                    $SearchResult[$key]['es_shipment_time'] = '';
                }

                if( $value['audit_time'] == '0000-00-00 00:00:00' || empty($value['audit_time'])){

                    $value['audit_time'] = '';
                }else{
                    $value['audit_time'] = $value['audit_time'];
                }

                // 二验交期是否已经确认
                if( $value['deliver_status'] == 1){

                    $SearchResult[$key]['deliver_status_ch'] = '';
                }else{
                    $SearchResult[$key]['deliver_status_ch'] = '已确认';
                }

                //单个体积
                $SearchResult[$key]['singlevolume'] = isset($skuVolumes[$value['sku']])?$skuVolumes[$value['sku']]:0;
                // 总体积
                $SearchResult[$key]['sumvolume'] = $value['confirm_amount'] * $SearchResult[$key]['singlevolume'];
                // 合同号
                $SearchResult[$key]['compactnumbers'] = isset($compactNumbers[$value['purchase_number']])?$compactNumbers[$value['purchase_number']]:'';
                // 权限交期是否可以修改
                if( $value['is_can_change_time'] == 1){
                    $SearchResult[$key]['is_can_change_time_ch'] = '是';
                }else{
                    $SearchResult[$key]['is_can_change_time_ch'] = '否';
                }

                if(!empty($value['es_shipment_time'])) {

                    $prevTime = strtotime($value['es_shipment_time']) - 3600 * 24 * 11;
                    if (date("Y-m-d") == date("Y-m-d", $prevTime) || date("Y-m-d") >= date("Y-m-d", $prevTime)) {

                        $SearchResult[$key]['is_can_change_time_ch'] = '否';
                    }
                }


                // 订单状态
                $SearchResult[$key]['order_status_ch'] = !empty($value['purchase_order_status'])?getPurchaseStatus($value['purchase_order_status']):'';
                // 采购仓库名称
                $warehousename = $this->warehouse_model->get_warehouse_one($value['warehouse_code'], 'warehouse_name');
                $SearchResult[$key]['warehousename'] = $warehousename;
                // 发运类型

                if($value['shipment_type'] == 1){
                    $SearchResult[$key]['shipment_type_ch'] = "工厂发运";

                }

                if($value['shipment_type'] == 2){
                    $SearchResult[$key]['shipment_type_ch'] = "中转仓发运";

                }

                if($value['shipment_type'] == 3){
                    $SearchResult[$key]['shipment_type_ch'] = "作废";

                }
                //$SearchResult[$key]['shipment_type_ch'] = ( isset($value['shipment_type']) && !empty($value['shipment_type']) )? getShipmentType($value['shipment_type']):'';
                // 备货单状态
                $SearchResult[$key]['suggest_status_ch'] = !empty($value['suggest_order_status'])?getPurchaseStatus($value['suggest_order_status']):'';
                // 取消数量
                $query = $this->purchase_db->from("purchase_order_cancel_detail")->select(" sum(cancel_ctq) AS number,sku,purchase_number")->where_in("sku", $value['sku'])->where_in("purchase_number", $value['purchase_number']);
                $cancal_result = $query->where("is_push=1")->group_by("sku,purchase_number")->get()->row_array();
                $SearchResult[$key]['cancel_amount'] = $cancal_result['number'];
                $SearchResult[$key]['t_plan_qty'] = isset($warehouseData[$value['purchase_number']."-".$value['sku']])?$warehouseData[$value['purchase_number']."-".$value['sku']]['instock_qty']:0;
                // 审核状态
                if($value['audit_status'] == 1){
                    $SearchResult[$key]['shipping_status_ch'] = "待采购确认";
                }

                if($value['audit_status'] == 2){
                    $SearchResult[$key]['shipping_status_ch'] = "采购驳回";
                }

                if($value['audit_status'] == 3){
                    $SearchResult[$key]['shipping_status_ch'] = "采购审核通过";
                }

                if($value['audit_status'] == 4){
                    $SearchResult[$key]['shipping_status_ch'] = "作废";
                }

                if( $value['audit_status'] == 5){
                    $SearchResult[$key]['shipping_status_ch'] = "自动审核通过";

                }

                if( $value['audit_status'] == 0 || empty($value['audit_status'])){
                    $SearchResult[$key]['shipping_status_ch'] = "";

                }

                if(empty($value['audit_status'])){

                    $SearchResult[$key]['shipping_status_ch'] = "";
                }
                // 采购员
                $SearchResult[$key]['buyer_name'] = $value['buyername'];
                // 发运类型审核状态
                $SearchResult[$key]['audit_time'] = $value['audit_time'];
                $SearchResult[$key]['logis_type'] = $value['type_name'];
                $SearchResult[$key]['station_name'] = $value['station_name'];
            }

        }
        $totalNumbers = $sumNumber->count_all_results();
        return array(

            'list' => isset($SearchResult)?$SearchResult:[],
            'page' => ['total'=>isset($totalNumbers)?$totalNumbers:0,'limit'=>$where['limit'],'offset'=>($where['offsets'] != 0)?$where['offsets']:1]
        );
    }

    /**
     * 修改预计到货和发货时间
     * @param $datas    array   参数
     * @author:luxu
     * @time:2020/4/18
     **/

    public function updateShipmentTime($datas = array()){

        try{
            // 验证数据是否满足 验货不合格并且备货单状态不等已作废

            if( isset($datas['ids']) && !empty($datas['ids'])) {

                $verifyData  = $this->purchase_db->from($this->shippingData." as shipping ")
                    ->where_in("id",$datas['ids'])->where("deliver_status",2)->get()->result_array();
                if(!empty($verifyData)){

//                    $newDemandNumbers = array_column($verifyData,"new_demand_number");
//                    throw new Exception("新配合单号:".implode(",",$newDemandNumbers).",已经确认了二验交期");
                }

                $SearchResult =  $this->purchase_db->from($this->shippingData." as shipping ")
                    ->join("supplier_check_sku AS check","shipping.demand_number=check.demand_number AND shipping.sku=check.sku","LEFT")
                    ->join(" purchase_suggest AS suggest","suggest.demand_number=shipping.demand_number")
                    ->select("shipping.judgment_result  AS checkstatus,shipping.check_time")
                    ->where("shipping.judgment_result",2)
                    ->where_in("shipping.id",$datas['ids'])
                    ->where("suggest.suggest_order_status!=",14)->select("COUNT(shipping.id) AS total")->get()->row_array();
                if( $SearchResult['total']!=count($datas['ids'])){

                    throw new Exception("验货不合格，且备货单状态≠已作废的，才可点击【二验交期确认】按钮");
                }

                $flag = $this->purchase_db->from("shipment_track_list")->where_in("id",$datas['ids'])
                    ->select("id AS shipping_id,new_demand_number,is_delivery,id,is_can_change_time,es_shipment_time,es_arrival_time")
                    ->get()->result_array();

                foreach($flag as $flag_key=>$flag_value){

                    $prevTime = strtotime($flag_value['es_shipment_time']) - 3600*24*11;
                    if( date("Y-m-d") == date("Y-m-d",$prevTime) || date("Y-m-d") >= date("Y-m-d",$prevTime)){

                        throw new Exception("备货单号:".$flag_value['new_demand_number'].",时间在 ".date("Y-m-d",$prevTime)."之后交期无法修改");
                    }
                }

                $update = array(
                    'deliver_status' => 2,
                    'es_shipment_time' => $datas['es_shipment_time'],
                    'es_arrival_time'  => $datas['es_arrival_time'],
                    'is_can_change_time' => 2
                );

                $this->purchase_db->trans_begin();
                $result = $this->purchase_db->where_in("id",$datas['ids'])->update($this->shippingData,$update);
                if($result){
                    //以新备货单号推送给计划系统
                    $demand_number_list = $this->purchase_db->select('new_demand_number')->from($this->shippingData." as shipping")
                        ->where_in("id",$datas['ids'])->get()->result_array();
                    $demand_number_list = array_column($demand_number_list,'new_demand_number');
                    if (empty($demand_number_list)){
                        $this->purchase_db->trans_rollback();
                        throw new Exception("数据异常,操作失败");
                    }
                    $this->load->model('others/Plan_system_model');
                    $time_map = [
                        'es_shipment_time' => $datas['es_shipment_time'],
                        'es_arrival_time'  => $datas['es_arrival_time']
                    ];
                    $res = $this->Plan_system_model->java_push_delivery_confirmation($demand_number_list,$time_map);

                    if ($res){//推送成功
                        $this->purchase_db->trans_commit();
                    }else{//推送失败
                        $this->purchase_db->trans_rollback();
                        throw new Exception("操作失败");
                    }
                }else{
                    throw new Exception("操作失败");
                }
            }else{

                throw new Exception("参数传入错误");
            }
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 获取修改日志
     * @author:luxu
     * @time:2020/4/20
     **/

    public function getUpdateLog($data,$typeData){

        try{

            if( !isset($data['id']) || empty($data['id'])){

                throw new Exception("缺少参数ID");
            }

            $logQuery = $this->purchase_db->from("shipment_log")->where("shipping_id",$data['id'])->select("id,update_time,update_user");
            if( "shipment" == $typeData){
                $logQuery->where("new_shipment_time!=old_shipment_time")->select("new_shipment_time AS old_shipment_time,old_shipment_time AS new_shipment_time,shipment_remark");
            }else if("arrival" == $typeData){
                $logQuery->where("new_arrival_time!=old_arrival_time")->select("new_arrival_time as old_arrival_time,old_arrival_time AS new_arrival_time,arrival_remark");
            }
            $result = $logQuery->get()->result_array();
            return $result;

        }catch ( Exception $exp ){

            throw new Exception( $exp->getMessage());
        }
    }

    /**
     * 获取计划系统修改日志信息接口
     * @Mthod GET
     * @author:luxu
     * @time:2020/4/20
     **/

    public function getChangeLog($data){

        try{

            if( !isset($data['demand_number']) || empty($data['demand_number'])){

                throw new Exception("请传入备货单号");
            }
            $where['new_demand_number'] = $data['demand_number'];
            if( isset($data['type']) ){
                $where['type'] = "change_plan_qty";
            }
            $result = $this->purchase_db->from("shipment_demand_info_update_log")->where($where)->get()->result_array();
            return $result;
        }catch ( Exception $exp ){
            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 获取发运跟踪列表数据
     * @param: $childrenDemandData    array|string   新备货单号
     * @author:luxu
     * @time: 2002/7/9
     **/
    public function getNewDemandData($childrenDemandData){

        $query = $this->purchase_db->from("shipment_track_list");
        if( is_array($childrenDemandData)){

            $query->where_in("new_demand_number",$childrenDemandData);
        }

        if( is_string($childrenDemandData)){

            $query->where("new_demand_number",$childrenDemandData);
        }
        $result = $query->get()->result_array();
        $this->newDemandNumber = $result;
        return $this;
    }

    /**
     * 清洗发运跟踪列表数据
     * @author:luxu
     * @time:2020/7/9
     **/
    public function showDewDemandData(){

        if( NULL == $this->newDemandNumber){

            return NULL;
        }

        return array_column( $this->newDemandNumber,NULL,"new_demand_number");
    }

    /**
     * 推送审核结果到计划系统
     *
     **/
    public function pushAuditResult($sendData,$rerun_batch){

        $url = getConfigItemByName('api_config','shipping_management','procurement_audit');
        $url.="?access_token=". getOASystemAccessToken();

        $header = array('Content-Type: application/json');
        $result = getCurlData($url, json_encode($sendData, JSON_UNESCAPED_UNICODE), 'post', $header);
        operatorLogInsert(
            [   'id' => $rerun_batch,
                'type' => "shippingment",
                'user'=>'发运类型',
                'content' => json_encode($sendData, JSON_UNESCAPED_UNICODE),
                'detail' => json_encode($result),
            ]);
        return $result;
    }
    /**
     * 审核发运类型
     * @author:luxu
     * @time:2020/4/21
     **/
    public function toExamineShipping($params,$status,$rerun_batch){

        try {
            $this->load->model('Shipment_track_list_model','',false,'purchase_shipment');
            $new_data = $old_data = $new_shipment_type = $old_shipment_type = $old_data_map = $insert_log_data = $push_data = $push_time_data = [];
            //发运类型变更审核接口修改
            if (!isset($params) || empty($params)) {

                throw new Exception($rerun_batch."传入参数错误");
            }

            if (empty($rerun_batch)){

                throw new Exception('数据异常,重跑批次不能为空');
            }
            $rerun_batch_data = $this->getDataByRerunBatch($rerun_batch);
            if (empty($rerun_batch_data)){
                throw new Exception($rerun_batch.'查询不到该重跑批次号');
            }

            //注:审核,是要将原单下的所有单都一起审核
            foreach ($rerun_batch_data as $key => $item){
                if ($item['is_del'] == 1){
                    $new_data[] = $item;
                    //新的发运类型
                    $new_shipment_type[] = getShipmentType($item['shipment_type']);
                }
                if ($item['is_del'] == 2){
                    $old_data[] = $item;
                    $old_data_map[$item['demand_number']][$item['shipment_type']] = $item;
                    //旧的发运类型
                    $old_shipment_type[] = getShipmentType($item['shipment_type']);
                }
            }
//pr($new_data);
//pr($params);exit;
            if (count($new_data) != count($params)){
                throw new Exception($rerun_batch.'数据异常,审核的数据和数据库的数据不一致');
            }
//            if (count($new_shipment_type) > 2 || count($old_shipment_type) > 2){
//                throw new Exception($rerun_batch.'数据异常,可能存在重复的数据');
//            }

            $new_data = array_column($new_data,NULL,'new_demand_number');
            $to_wms_datas = []; // 推送到仓库数据缓冲区
            // 审核通过
            $pushWmsStatus = $status;
            if ($status == 1) {
                foreach ($params as $itemKey => $itemValue) {

                    if (!isset($new_data[$itemValue['new_demand_number']])){
                        throw new Exception($itemValue['new_demand_number'].'数据异常,查询不到对应的新备货单号');
                    }
                    $new = $new_data[$itemValue['new_demand_number']];
                    $old = $old_data_map[$new['demand_number']][$new['shipment_type']]??[];

                    if ($new['audit_status'] != 1 || $new['is_del'] != 1){
                        throw new Exception($itemValue['new_demand_number'].'不是待采购确认状态');
                    }

                    //删除原单, 更新新单状态为审核通过
                    $update_shipment_track_data[] = [
                        'id' => $new['id'],
                        'plan_qty' => $new['temp_plan_qty'],
                        'audit_status' => 3,
                        'audit_time' => date("Y-m-d H:i:s", time()),
                        'es_shipment_time' => $itemValue['es_shipment_time'],
                        'es_arrival_time' => $itemValue['arrival_time'],
                        'is_del' => 2,
                    ];

                    $del_shipment_track_data = array_column($old_data,'id');

                    //记录日志
                    //数量变更
                    if ($new['plan_qty'] != $new['temp_plan_qty']){
                        $insert_log_data[] = [
                            'new_demand_number' => $new['new_demand_number'],
                            'type' => 'change_plan_qty',
                            'old_data' => $new['plan_qty'],
                            'new_data' => $new['temp_plan_qty'],
                            'operation_time' => date('Y-m-d H:i:s'),
                            'operator' => '计划系统',
                        ];
                    }
                    //发运类型变更
                    if (!empty(array_diff($new_shipment_type,$old_shipment_type)) || !empty(array_diff($old_shipment_type,$new_shipment_type))){
                        $insert_log_data[] = [
                            'new_demand_number' => $new['new_demand_number'],
                            'type' => 'change_shipment_type',
                            'old_data' => implode(' ',$old_shipment_type),
                            'new_data' => implode(' ',$new_shipment_type),
                            'operation_time' => date('Y-m-d H:i:s'),
                            'operator' => '计划系统',
                        ];
                    }

                    //修改物流类型
                    if ($new['logistics_type'] != ($old['logistics_type']??'')){
                        $insert_log_data[] = [
                            'new_demand_number' => $item['new_demand_number'],
                            'type' => 'change_logistics_type',
                            'old_data' => $old['logistics_type']??'',
                            'new_data' => $new['logistics_type'],
                            'operation_time' => date('Y-m-d H:i:s'),
                            'operator' => '计划系统',
                        ];
                    }

                    // 1.推送审核结果，
                    $push_data[] = [
                        'rerun_batch' => $rerun_batch,
                        'demand_number' => $itemValue['new_demand_number'],
                        'can_change_type' => $status,
                        'sku' => $itemValue['sku']
                    ];

                    // 2.推送时间
                    $push_time_data[] = [
                        'demand_number' => $itemValue['new_demand_number'],
                        'es_shipment_time' => $itemValue['es_shipment_time'],
                        'es_arrival_time'  => $itemValue['arrival_time']
                    ];
                }
            } else {

                // 驳回
                foreach ($params as $itemKey => $itemValue) {

                    if (!isset($new_data[$itemValue['new_demand_number']])){
                        throw new Exception($itemValue['new_demand_number'].'数据异常,查询不到对应的新备货单号');
                    }
                    $new = $new_data[$itemValue['new_demand_number']];
                    if ($new['audit_status'] != 1){
                        throw new Exception($itemValue['new_demand_number'].'不是待采购确认状态');
                    }


                    // 1.推送审核结果，
                    $push_data[] = [
                        'rerun_batch' => $rerun_batch,
                        'demand_number' => $itemValue['new_demand_number'],
                        'can_change_type' => $status,
                        'sku' => $itemValue['sku']
                    ];
                }

                //删除新单, 更新原单状态为审核驳回
                foreach ($old_data as $k => $v){
                    $update_shipment_track_data[] = [
                        'id' => $v['id'],
                        'audit_status' => 2,
                        'audit_time' => date("Y-m-d H:i:s", time()),
                    ];
                }


                $del_shipment_track_data = array_column($new_data,'id');

            }
            $shipmentDataOrders = [];
            if($pushWmsStatus == 1) {
                //36474 备货单在发运前重跑规则审核通过之后，
                //sku的“工厂直发发运”和“中转仓发运”采购数量发生变更的情况下，再次推送新的数量到仓库；
                $trackListDatas = $this->purchase_db->from("shipment_track_list")->where("rerun_batch", $rerun_batch)
                    ->where("is_del!=",2)
                    ->where("audit_status",1)
                    ->select("purchase_number,sku,shipment_type,temp_plan_qty")->get()->result_array();
                if (!empty($trackListDatas)) {

                    foreach ($trackListDatas as $trackListData_key => $trackListData_value) {
                        $to_wms_datas[] = [
                            'purchaseOrderNo' => $trackListData_value['purchase_number'],
                            'sku' => $trackListData_value['sku'],
                            'planQty' => $trackListData_value['temp_plan_qty'],
                            'shipmentType' => $trackListData_value['shipment_type']
                        ];

                        /**
                         * 需求：
                            37849 发运类型变更的逻辑补充
                            3．审核通过后，若采购数量全部由直发变为中转，那么采购单页面对应的发运类型也要变为中转仓发运；
                            4．审核通过后，若采购数量全部由直发变为中转，那么验货列表页面的验货类型也要变为常规；
                         **/

                        if($trackListData_value['shipment_type'] == 2){

                            // 如果审核的发运类型为中转仓，开始获取采购采购数量.
                            $purchase_amounts = $this->purchase_db->from("purchase_order_items")->where("purchase_number",$trackListData_value['purchase_number'])
                                ->where("sku",$trackListData_value['sku'])->select("purchase_amount")->get()->row_array();

                            if(!empty($purchase_amounts) && $purchase_amounts['purchase_amount'] == $trackListData_value['temp_plan_qty']){

                                $shipmentDataOrders[] = $trackListData_value['purchase_number'];
                            }
                        }
                    }
                }
                if (!empty($to_wms_datas)) {

                    $url = getConfigItemByName('api_config', 'wms_system', 'receivePurchaseNumber');
                    $url .= "?access_token=" . getOASystemAccessToken();

                    $header = array('Content-Type: application/json');
                    $result = getCurlData($url, json_encode($to_wms_datas, JSON_UNESCAPED_UNICODE), 'post', $header);
                    operatorLogInsert(
                        ['id' => $rerun_batch,
                            'type' => "receivePurchaseNumber",
                            'user' => 'sku的“工厂直发发运”和“中转仓发运”采购数量发生变更的情况下，再次推送新的数量到仓库',
                            'content' => json_encode($to_wms_datas, JSON_UNESCAPED_UNICODE),
                            'detail' => json_encode($result),
                        ]);

                }
            }

            //1.推送审核结果
            $audit = $this->pushAuditResult($push_data,$rerun_batch);
            $audit = json_decode($audit, True);
            if ($audit['status'] == 500 || $audit['status'] ==0) {

                throw new Exception("审核结果推送计划系统失败");
            }


            // 2.推送预计到货时间和发货时间到计划系统

            $push_plan_time_data = [];
            foreach ($push_time_data as $key => $item){
               /* $time_map = [
                    'es_shipment_time' => $item['es_shipment_time'],
                    'es_arrival_time'  => $item['es_arrival_time']
                ];
                $res = $this->Plan_system_model->java_push_delivery_confirmation([$item['demand_number']],$time_map);
                $res = json_decode($res,True);
                if(!$res){

                    throw new Exception("[预计发货时间][预计到货时间],推送计划系统失败");
                }*/

               $push_plan_time_data [] = [

                   'pur_sn' => $item['demand_number'],
                   'pms_to_data_plan' => $item['es_arrival_time']
               ];

            }




            $this->purchase_db->trans_begin();
            //数据库操作

            //发运跟踪列表 删除记录
            if (!empty($del_shipment_track_data)){
                $this->purchase_db->where_in('id',$del_shipment_track_data)->delete('pur_shipment_track_list');
            }

            //发运跟踪列表 更新
            if (!empty($update_shipment_track_data)){
                $this->purchase_db->update_batch('pur_shipment_track_list',$update_shipment_track_data,'id');
            }

            //日志表 新增记录
            if (!empty($insert_log_data)){
                $this->purchase_db->insert_batch('pur_shipment_demand_info_update_log',$insert_log_data);
            }

            //更新推送wms状态
            $this->Shipment_track_list_model->update_push_to_wms_status([$rerun_batch]);
            // 修改采购单发运类型
            if(isset($shipmentDataOrders) && !empty($shipmentDataOrders)){

                $this->purchase_db->where_in("purchase_number",$shipmentDataOrders)->update("purchase_order",['shipment_type'=>2]);
                $this->purchase_db->where_in("purchase_number",$shipmentDataOrders)->update("supplier_check",['order_type'=>1]);
            }

            $this->purchase_db->trans_commit();

            /*if($pushWmsStatus ==1 && !empty($push_plan_time_data)){

                $this->load->model('others/Plan_system_model');

                $this->Plan_system_model->pms_to_data_plan($push_plan_time_data);
            }*/

            return True;
        }catch ( Exception $exp ) {
            $this->purchase_db->trans_rollback();
            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 修改交期
     * @METHOD:POST
     * @author:luxu
     * @time:2020/4/21
     **/
    public function updateDelivery($params){

       try{
           // 判断HTTP 是否传入备货单号
           if( !isset($params['demand_number']) && empty($params['demand_number'])) {

               throw new Exception("请传入备货单号");
           }

           $flag = $this->purchase_db->from("shipment_track_list")->where_in("new_demand_number",$params['demand_number'])
               ->select("id AS shipping_id,new_demand_number,is_delivery,id,is_can_change_time,es_shipment_time,es_arrival_time")->get()->result_array();
           if(empty($flag)){
               throw new Exception("备货单不存在");
           }

            $noUpdateData = array_map(function($data){

                if($data['is_can_change_time'] == 2){

                    return $data['new_demand_number'];
                }
            },$flag);
            $noUpdateData = array_filter($noUpdateData);
            if(!empty($noUpdateData)) {
                $noUpdateData = array_filter(array_unique($noUpdateData));
                throw new Exception("备货单号：".implode(",",$noUpdateData)."，交期不可修改");
            }


           foreach($flag as $flag_key=>$flag_value){

               $prevTime = strtotime($flag_value['es_shipment_time']) - 3600*24;
               if( date("Y-m-d") == date("Y-m-d",$prevTime) || date("Y-m-d") >= date("Y-m-d",$prevTime)){

                   throw new Exception("备货单号:".$flag_value['new_demand_number'].",时间在 ".date("Y-m-d",$prevTime)."。交期无法修改");
               }
           }

           $updateData = array(
               'es_shipment_time' => $params['es_shipment_time'],
               'es_arrival_time'  => $params['es_arrival_time'],
               'is_can_change_time' => 2
           );

           $result = $this->purchase_db->where_in("new_demand_number",$params['demand_number'])->update('shipment_track_list',$updateData);
           $this->load->model('others/Plan_system_model');
           $time_map = [
               'es_shipment_time' =>  $params['es_shipment_time'],
               'es_arrival_time'  => $params['es_arrival_time']
           ];
           $demand_number_list = $this->purchase_db->select('new_demand_number')->from($this->shippingData." as shipping")
               ->where_in("new_demand_number",$params['demand_number'])->get()->result_array();
           $demand_number_list = array_column($demand_number_list,'new_demand_number');
           $res = $this->Plan_system_model->java_push_delivery_confirmation($demand_number_list,$time_map);
            if($result){


                $updateLogs = [];
                foreach($flag as $key=>$value){

                    $updateLogs[] = array(

                        'shipping_id' => $value['shipping_id'],
                        'new_shipment_time' => $params['es_shipment_time'],
                        'old_shipment_time' => $value['es_shipment_time'],
                        'new_arrival_time' => $params['es_arrival_time'],
                        'old_arrival_time' =>$value['es_arrival_time'],
                        'shipment_remark' => '',
                        'arrival_remark'  => '',
                        'update_time' => date("Y-m-d H:i:s",time()),
                        'update_user' =>getActiveUserName()
                    );
                }

                if(!empty($updateLogs)){

                    $this->purchase_db->insert_batch('shipment_log',$updateLogs);
                }
            }
            return $result;
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 获取发运跟踪表未推送计划系统的数据
     * @param int $limit
     * @param array $demand_number
     * @param array $new_demand_number
     * @return array
     */
    public function getPushToPlanData($limit=200, $demand_number=[], $new_demand_number=[])
    {
        $query = $this->purchase_db->select('new_demand_number,judgment_result,check_expect_time')
            ->from($this->table_shipment_track)
            ->where(['push_to_plan' => 0, "shipment_type" => 1])
            ->where_in('judgment_result', [1, 2]);
        if(count($demand_number) > 0){
            $query->where_in("demand_number", $demand_number);
        }
        if(count($new_demand_number) > 0){
            $query->where_in("new_demand_number", $new_demand_number);
        }
        $data = $query->limit($limit)->get()->result_array();
        return $data;
    }

    /**
     * 更新推送计划系统状态
     * @param $demand_number
     * @return bool
     */
    public function updatePushState($demand_number){
        if(empty($demand_number)) return false;
        $this->purchase_db->where('new_demand_number',$demand_number);
        $this->purchase_db->set('push_to_plan',1);
        $this->purchase_db->set('push_time', date('Y-m-d H:i:s'));
        return $this->purchase_db->update($this->table_shipment_track);
    }

    /**
     * 获取审核数据 //pur_shipment_track_list
     * @param $ids  array|string   主键id
     * @author:luxu
     * @time:2020/7/1
     **/
    public function showToExamineShipping($ids){

        try{
            //根据id查询重跑批次
            $result = $this->purchase_db->select('rerun_batch, new_demand_number, audit_status, is_del')->from($this->shippingData)
                ->where_in('id',$ids)->get()->result_array();
//            echo $this->purchase_db->last_query();exit;
            if (count(array_unique(array_filter(array_column($result,'rerun_batch')))) > 1){
                throw new Exception('请勾选同一重跑批次的备货单进行审核！');
            }
            foreach ($result as $key => $item){
                if($item['audit_status'] != 1){

                    throw new Exception("只有待采购确认状态，才能审核发运类型变更");
                }
            }
            $rerun_batch = $result[0]['rerun_batch'];
            if (empty($rerun_batch)){
                throw new Exception("重跑批次号不能为空");
            }

            $result = $this->purchase_db->select("id,sku,audit_status,new_demand_number,demand_number,shipment_type,es_shipment_time,es_arrival_time,plan_qty,temp_plan_qty, is_del, rerun_batch")
                ->from($this->shippingData)
                ->where('rerun_batch',$rerun_batch)
                ->order_by('demand_number')
                ->get()->result_array();

            if(!empty($result)){

                $skus = array_column( $result,"sku");
                $productData = $this->purchase_db->from("product")->where_in("sku",$skus)->select("product_name,sku")->get()->result_array();
                $productData = array_column($productData,NULL,"sku");
                foreach($result as $key=>$value){

                    if($value['is_del'] == 2 && $value['audit_status'] != 1){

                        throw new Exception("只有待采购确认状态，才能审核发运类型变更");
                    }

                    if ($value['is_del'] == 2){//原单不显示
                        unset($result[$key]);
                        continue;
                    }

                    if ($value['is_del'] == 1 && $value['audit_status'] != 1){
                        throw new Exception("数据异常,不是待采购确认状态");
                    }

                    if( isset($productData[$value['sku']])){

                        $result[$key]['product_name'] = $productData[$value['sku']]['product_name'];
                    }else{
                        $result[$key]['product_name'] = NULL;
                    }
                }
            }
            return $result;
        }catch ( Exception $exp ){

            throw new Exception( $exp->getMessage());
        }

    }


    /**
     * 审核时,根据原单查询数据
     * @author Manson
     * @param $demand_number_list
     * @return $this
     */
    public function getDataByRerunBatch($rerun_batch){
        $query = $this->purchase_db->from("shipment_track_list");


        $query->where("rerun_batch",$rerun_batch);

        $result = $query->get()->result_array();
        return $result;
    }
}