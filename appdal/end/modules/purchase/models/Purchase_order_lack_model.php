<?php
/**
 * 少数少款MODEL
 * User: luxu
 * Date: 2020/11/25
 */
class Purchase_order_lack_model extends Purchase_model
{

    private $defiveString = NULL;
    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['user','abnormal']);
        $this->load->model('Purchase_order_cancel_model', 'm_cancel', false, 'purchase');
        $this->load->model('Purchase_financial_audit_model', 'm_financial_audit', false, 'purchase');
        $this->load->model('purchase_order_model','order_model',false,'purchase');
        $this->load->model('warehouse/Warehouse_model');
        $this->defiveString = '{"1":"\u8d28\u91cf\u4e0d\u826f-\u529f\u80fd\u4e0d\u826f","2":"\u8d28\u91cf\u4e0d\u826f-\u914d\u4ef6\u4e0d\u9f50","3":"\u8d28\u91cf\u4e0d\u826f-\u5916\u89c2\u4e0d\u826f","4":"\u8d28\u91cf\u4e0d\u826f-\u53c2\u6570\u4e0d\u7b26","5":"\u8d28\u91cf\u4e0d\u826f-\u6750\u8d28\u4e0d\u826f","6":"\u8d27\u7269\u4e0d\u7b26","7":"\u56fe\u7269\u4e0d\u7b26","8":"\u5305\u88c5\u4e0d\u826f","9":"\u5206\u8d27\u9519\u8bef","101":"\u4ea7\u54c1\u4e0d\u826f-\u5916\u89c2\u4e0d\u826f","102":"\u4ea7\u54c1\u4e0d\u826f-\u529f\u80fd\u4e0d\u826f","103":"\u4ea7\u54c1\u4e0d\u826f-\u4ea7\u54c1\u9519\u8d27","104":"\u4ea7\u54c1\u4e0d\u826f-\u6750\u8d28\u4e0d\u826f","105":"\u4ea7\u54c1\u4e0d\u826f-\u4ea7\u54c1\u8fc7\u671f","106":"\u4ea7\u54c1\u4e0d\u826f-\u5176\u5b83\u4e0d\u826f","201":"\u5305\u88c5\u4e0d\u826f-\u9632\u62a4\u4e0d\u826f","202":"\u5305\u88c5\u4e0d\u826f-\u5305\u88c5\u4e0d\u4e00","203":"\u5305\u88c5\u4e0d\u826f-\u5305\u88c5\u7834\u635f","204":"\u5305\u88c5\u4e0d\u826f-\u53c2\u6570\u590d\u6838","301":"\u914d\u4ef6\u4e0d\u826f-\u914d\u4ef6\u4e0d\u7b26","302":"\u914d\u4ef6\u4e0d\u826f-\u914d\u4ef6\u7f3a\u5931","303":"\u914d\u4ef6\u4e0d\u826f-\u914d\u4ef6\u591a\u8d27","401":"\u8d44\u6599\u4e0d\u826f-\u56fe\u7247\u4e0d\u7b26","402":"\u8d44\u6599\u4e0d\u826f-\u63cf\u8ff0\u4e0d\u7b26","403":"\u8d44\u6599\u4e0d\u826f-\u54c1\u724c\u4e0d\u7b26"}';
    }

    /**
     * 少数少款规则保存
     * @author:luxu
     * @time:2020/11/25
     **/

    public function saveConfig($data = array()){

        if( NULL == $data || empty($data)){

            return False;
        }

        $saveData = [

            'number' => $data['number'], // 少数规则
            'style'  => $data['style'], //  少款规则
            'user'   => getActiveUserName() // 操作人
        ];

        $search = $this->purchase_db->from("purchase_lack")->select("id")->get()->row_array();
        if( !empty($search) ){

            $result = $this->purchase_db->where("id",$search['id'])->update('purchase_lack',$saveData);
        }else{
            $result = $this->purchase_db->insert('purchase_lack',$saveData);
        }
        return $result;
    }

    /**
     * 读取少数少款配置信息
     * @param  wu
     * @author:luxu
     * @time: 2020/11/26
     **/

    public function getConfig(){

        $result = $this->purchase_db->from("purchase_lack")->get()->row_array();
        $result['style'] = json_decode($result['style']);
        return $result;
    }

    /**
     * 获取采购单采购数量
     * @param $purchase_number  string  采购单号
     * @author:luxu
     * @time:2020/11/26
     **/

    private function getPurchaseConfirmAmount($purchase_number,$sku){

        $result = $this->db->from("purchase_order_items")->where("purchase_number",$purchase_number)->where('sku',$sku)
            ->select(" id,confirm_amount,sku")->get()->result_array();
        return $result;
    }

    /**
     * 获取采购单下所有备货单
     * @param $purchase_number  string  采购单号
     * @author:luxu
     * @time:2020/11/26
     **/

    public function getSuggestPurchaseData($purchase_number,$sku){

        $result = $this->db->from("purchase_order_items")->where("purchase_number",$purchase_number)->where("sku!=",$sku)
            ->select("purchase_number AS purchase_order_no,sku")->get()->result_array();
        return $result;
    }

    /**
     * 获取备货单下累加的点数
     * @param $purchase_number  string 采购单号
     * @author:luxu
     * @time:2020/11/28
     */
    private function getActualNum($purchase_number,$sku = NULL,$is_accumulate =NULL){

        $query = $this->purchase_db->from("purchase_lack_data")
            ->where("purchase_number",$purchase_number);
        if(NULL != $sku){

            $query->where("sku",$sku);
        }

        if( NULL != $is_accumulate){

            $query->where("is_accumulate",$is_accumulate);
        }
        $numbers = $query->select(" SUM(actual_num) as actual")
            ->get()->row_array();

        if(empty($numbers['actual'])){

            return 0;
        }

        return $numbers['actual'];
    }

    /**
     * 实际到货数量
     * @param  $purchase_number  string  采购单号
     *         $sku              string  SKU
     *@author:luxu
     **/
    protected  function actualConfirmData($purchase_number,$sku){

        $purchaseData = $this->getPurchaseConfirmAmount($purchase_number,$sku);
        $purchaseIds = array_column($purchaseData,'id');

            // 采购数量
            $confirmNumbers = array_sum( array_column($purchaseData,'confirm_amount'));
            // 获取取消数据
            $cancel_qty_map = $this->m_cancel->get_cancel_qty_by_item_id($purchaseIds);
            $cancelNumber = 0;
            if(!empty($cancel_qty_map)){

                $cancelNumber = array_sum(array_values($cancel_qty_map));
            }
            // 报损数量
            $qty_map = $this->m_financial_audit->get_qty_info($purchaseIds);
            $qty_number = 0;
            if( !empty($qty_map)){

                $qtyDatas = array_column($qty_map,'loss_qty');
                if(!empty($qtyDatas)) {
                    $qty_number = array_sum($qtyDatas);
                }
            }
        return [

            'cancelNumber' =>$cancelNumber,
            'qty_number' =>$qty_number
        ];
    }

    /**
      * 累计实际到货数量>=（采购数量-取消数量-报损数量），判断备货单少数少款类型为“全部到货”；
        0<累计实际到货数量<（采购数量-取消数量-报损数量），判断备货单少数少款类型为“少数”；
        累计实际到货数量=0，判断备货单少数少款类型为“少款”；
     * @param $purchase_number  string  采购单号
     *        $actual_number    int     到点数量
     * @author:luxu
     * @time:2020/11/26
     **/

    private function returnLackType($purchase_number,$actual_num,$is_accumulate,$sku = NULL){

        try{

            $actual_num_data = $this->getActualNum($purchase_number,$sku,1);
            //累计实际到货数量=0，判断备货单少数少款类型为“少款”；

            $suggestStatus = $this->getSuggestStatus($purchase_number,$sku);

            if( $suggestStatus == 9){

                return PURCHASE_ORDER_LACK_ALL;
            }
            if( $actual_num_data == 0 && $actual_num == 0){
                return PURCHASE_ORDER_LACK_STYLE;
            }
            if( $actual_num_data == 0){

                $actual_num_data = $actual_num;
            }else{
                if($is_accumulate ==1) {
                    $actual_num_data += $actual_num;
                }else{
                    $actual_num_data = $actual_num;
                }
            }
            // 获取采购单 下所有备货单的采购数量 和采购单ID
            $purchaseData = $this->getPurchaseConfirmAmount($purchase_number,$sku);
            if(empty($purchaseData)){

                return "EMPTY";
            }
            $purchaseIds = array_column($purchaseData,'id');

            // 采购数量
            $confirmNumbers = array_sum( array_column($purchaseData,'confirm_amount'));
            // 获取取消数据
            $cancel_qty_map = $this->m_cancel->get_cancel_qty_by_item_id($purchaseIds);
            $cancelNumber = 0;
            if(!empty($cancel_qty_map)){

                $cancelNumber = array_sum(array_values($cancel_qty_map));
            }
            // 报损数量

            $qty_map = $this->m_financial_audit->get_qty_info($purchaseIds);
            $qty_number = 0;
            if( !empty($qty_map)){

                $qtyDatas = array_column($qty_map,'loss_qty');
                if(!empty($qtyDatas)) {
                    $qty_number = array_sum($qtyDatas);
                }
            }
            // 累计实际到货数量>=（采购数量-取消数量-报损数量），判断备货单少数少款类型为“全部到货”；
            if( $actual_num_data >= ($confirmNumbers - $cancelNumber - $qty_number)){

                return PURCHASE_ORDER_LACK_ALL;
            }
            //  0<累计实际到货数量<（采购数量-取消数量-报损数量），判断备货单少数少款类型为“少数”；
            if( $actual_num_data >0 && ($confirmNumbers - $cancelNumber - $qty_number) > $actual_num_data){
                return PURCHASE_ORDER_LACK_QUE;
            }
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
      * 判断少数少款类型
     **/
    public function getPurchaseStype($purchaseNumbers){

        $result = $this->db->from("purchase_lack_data")->where("purchase_number",$purchaseNumbers)->select("type")->get()->result_array();
        if(empty($result)){

            return NULL;
        }

        $lackStyles=  array_unique(array_column($result,'type'));
        if( count($lackStyles) == 1){

            // 如果只有一种类型,并且全部到货
            if( in_array(PURCHASE_ORDER_LACK_ALL,$lackStyles)){

                return False;
            }
        }

        return True;
    }

    /**
     * 获取入库信息
     * @author:luxu
     * @time:2020/11/26
     **/

    protected  function getWarehouseData($purchaseNumber,$sku,$file = '*'){

        $where = [

            'sku' => $sku,
            'purchase_number' =>$purchaseNumber
        ];

        $result = $this->purchase_db->from("warehouse_results")->where($where)
            ->select($file)
            ->order_by("id DESC")->get()->row_array();
        return $result;
    }

    protected  function getWarehouseSum($purchaseNumber,$sku){

        $where = [

            'sku' => $sku,
            'purchase_number' =>$purchaseNumber
        ];
        //warehousemain.check_qty,warehousemain.instock_qty,warehousemain.bad_qty
        $result = $this->purchase_db->from("warehouse_results_main")->where($where)
            ->select(" SUM(check_qty) as check_qty,SUM(instock_qty) AS instock_qty,SUM(bad_qty) AS bad_qty")
            ->order_by("id DESC")->get()->row_array();
        return $result;
    }

    /**
      * 少款是否符合规则
     **/
    private function getProccessStatus($purchase_order_no,$configDatas,$type='update'){

        $totalSkus = $this->purchase_db->from("purchase_lack_data")->where("purchase_number", $purchase_order_no)
            ->where("type",1)
            ->select("confirm_amount,sku")->count_all_results();
        if ($totalSkus > 0 && $totalSkus <= 3) {
            $keys = 'Three';
        }

        if ($totalSkus > 3 && $totalSkus <= 10) {
            $keys = 'Thirty';
        }

        if ($totalSkus > 10 && $totalSkus <= 20) {
            $keys = 'Twenty';
        }

        if ($totalSkus > 20 && $totalSkus <= 30) {
            $keys = 'Ten';
        }

        if($type == 'insert'){

            $totalSkus = $totalSkus+1;
        }
        $styleConfig = json_decode($configDatas['style'], true);
        if ($styleConfig[$keys] < $totalSkus) {

            return True;
        }else{
            return false;
        }


    }

    /**
     * 获取少数少款列表数据
     * @param:HTTP 传值
     * @author:luxu
     * @time:2020/11/27
     **/
    public function getLackData($params,$offset='', $limit=''){
        $resultoffset = $offset;

        try{

            if (isset($params['account_type']) and $params['account_type']) {//结算方式

                $children_account_type = $this->purchase_db->from("supplier_settlement")->select("settlement_code")
                    ->where_in("parent_id",$params['account_type'])
                    ->or_where_in('settlement_code',$params['account_type'])->get()->result_array();
            }
            $query = $this->purchase_db->distinct(true)->from("purchase_lack_data as lack");
            $query->join('purchase_suggest_map as map','lack.purchase_number=map.purchase_number AND map.sku=lack.sku','LEFT');
            $query->join('purchase_order AS orders','lack.purchase_number=orders.purchase_number','LEFT');
            $query->join('purchase_order_items AS items','lack.purchase_number=items.purchase_number AND lack.sku=items.sku','LEFT');
            $query->join('purchase_warehouse_abnormal AS abnormal','lack.purchase_number=abnormal.pur_number AND lack.sku=abnormal.sku','LEFT');
            $query->join('warehouse_results_main as main','lack.purchase_number=main.purchase_number AND lack.sku=main.sku AND lack.actual_num=instock_qty','LEFT');
            $query->join('purchase_suggest as suggest','map.demand_number=suggest.demand_number AND suggest.sku=map.sku','LEFT');
            $query->join('warehouse as ware','ware.warehouse_code=orders.warehouse_code',"LEFT");
            // 根据采购单号查询
            if( isset($params['purchase_number']) && !empty($params['purchase_number'])){
                $query->where_in("lack.purchase_number",$params['purchase_number']);
            }

            if( isset($params['warehouse_code']) && !empty($params['warehouse_code'])){

                $query->where_in("ware.warehouse_code",$params['warehouse_code']);
            }

            if( isset($params['ids']) && !empty($params['ids'])){
                $query->where_in("lack.id",$params['ids']);
            }

            // 结算方式

            if (isset($params['account_type']) and $params['account_type']) {//结算方式
                //$query_builder->where('ppo.account_type', $params['account_type']);



                if( !empty($children_account_type)) {
                    $children_account_type = array_column($children_account_type, 'settlement_code');
                    $query->where_in("orders.account_type",$children_account_type);
                }
            }

            // 采购来源

            if( isset($params['source']) && !empty($params['source'])){

                 $query->where_in("orders.source",$params['source']);
            }

            // 是否对接门户
            if( isset($params['is_gateway']) && !empty($params['is_gateway'])){

                $query->where_in("orders.is_gateway",$params['is_gateway']);
            }

            //根据备货单号查询
            if( isset($params['demand_number']) && !empty($params['demand_number'])){
                $query->where_in("map.demand_number",$params['demand_number']);
            }

            //采购组别

            if( isset($params['groupdatas']) && !empty($params['groupdatas'])){

                $query->where_in('orders.buyer_id',$params['groupdatas']);
            }

            //根据SKU 查询
            if( isset($params['sku']) && !empty($params['sku'])){
                $query->where_in('lack.sku',$params['sku']);
            }
            // 采购员查询
            if( isset($params['buyer_id']) && !empty($params['buyer_id'])){
                $query->where_in('orders.buyer_id',$params['buyer_id']);
            }

            // 创建时间
            if( isset($params['create_time_start']) && !empty($params['create_time_start'])){

                $query->where("lack.add_time>=",$params['create_time_start']);
            }
            if( isset($params['create_time_end']) && !empty($params['create_time_end'])){

                $query->where("lack.add_time<=",$params['create_time_end']);
            }

            // 供应商
            if( isset($params['supplier_code']) && !empty($params['supplier_code'])){
                $query->where_in('orders.supplier_code',$params['supplier_code']);
            }

            // 处理状态

            if( isset($params['processing']) && !empty($params['processing'])){

                $tflag = true;
                $query->where("( 1=1");
                if( in_array(2,$params['processing'])){

                    $query->where_in('(lack.processing', $params['processing'])->where('suggest.suggest_order_status!=11')->where('suggest.suggest_order_status!=14')
                        ->where('suggest.suggest_order_status!=9)');
                    $tflag = false;
                }
                if( in_array(1,$params['processing']) || in_array(3,$params['processing'])) {

                    if($tflag == true) {
                        $query->where_in('lack.processing', $params['processing'])->where('suggest.suggest_order_status!=11')->where('suggest.suggest_order_status!=14');
                    }else{
                        $query->or_where_in('(lack.processing', $params['processing'])->where('suggest.suggest_order_status!=11')->where('suggest.suggest_order_status!=14)');
                    }
                    $tflag = false;

                }

                if(in_array(4,$params['processing'])){

                    if($tflag == true){

                        $query->where_in('(lack.processing', $params['processing'])->or_where('suggest.suggest_order_status=14')->or_where('suggest.suggest_order_status=11)');
                    }else{
                        $query->or_where_in('(lack.processing', $params['processing'])->or_where('suggest.suggest_order_status=14')->or_where('suggest.suggest_order_status=11)');
                    }
                    $tflag = false;
                }

                $query->where(" 1=1 )");


            }

            if( isset($params['type']) && !empty($params['type'])){
                $query->where_in("lack.type",$params['type']);
            }

            // 创建时间
            if( isset($params['new_update_start']) && !empty($params['new_update_start'])){

                $query->where("lack.update_time>=",$params['new_update_start']);
            }
            if( isset($params['new_update_end']) && !empty($params['new_update_end'])){

                $query->where("lack.update_time<=",$params['new_update_end']);
            }

            if( isset($params['update_time_start']) && !empty($params['update_time_start'])){

                $query->where("lack.person_time>=",$params['update_time_start']);
            }
            if( isset($params['update_time_end']) && !empty($params['update_time_end'])){

                $query->where("lack.person_time<=",$params['update_time_end']);
            }


            // 上架时间
            if( isset($params['up_time_start']) && !empty($params['up_time_start'])){

                $query->where("main.instock_date>=",$params['up_time_start']);
            }
            if( isset($params['up_time_end']) && !empty($params['up_time_end'])){

                $query->where("main.instock_date<=",$params['up_time_end']);
            }

            if( isset($params['warehouse_code']) && !empty($params['warehouse_code'])){

                $query->where_in("ware.warehouse_name",$params['warehouse_name']);
            }

            // 工位

            if( isset($params['works']) && !empty($params['works'])){

                $query->where('lack.station',$params['works']);
            }
            // 次品类型

            if( isset($params['errortype']) && !empty($params['errortype'])){

                $query->where('abnormal.defective_type',$params['errortype']);
            }

            $countQuery = clone $query;
            $result = $query->select("orders.account_type,orders.is_gateway,orders.source,ware.warehouse_name,main.instock_date AS upper_end_time,orders.purchase_order_status,items.id as itemsid,orders.purchase_order_status,lack.id,
                                       
                                        abnormal.defective_type,lack.*,
                                        orders.buyer_name,map.demand_number,items.confirm_amount,
            orders.supplier_code,orders.supplier_name,orders.supplier_code")->order_by("lack.id DESC")->limit($limit, $offset)->get()->result_array();

            if(!empty($result)){
                $demandNumbers = array_unique(array_column( $result,'demand_number'));

                $demandNumbers = $this->purchase_db->from("purchase_suggest")->where_in("demand_number",$demandNumbers)
                    ->select("demand_number,suggest_order_status")->get()->result_array();

                $demandNumbers = array_column($demandNumbers,NULL,'demand_number');

                $itemids = array_column( $result,'itemsid');
                $cancelDatas = $this->order_model->get_cancel_id($itemids);
                $file = "defective_position,instock_date,storage_position,instock_user_name,quality_level_num,instock_qty,quality_result,quality_time,quality_all,quality_username,paste_code_qty,paste_code_user,paste_code_time";
                $fileData = explode(",",$file);
                $sumfile = "check_qty,instock_qty,bad_qty";
                $sumfileData = explode(",",$sumfile);

                $itemsDatas = array_unique(array_column($result,"itemsid"));
                $cancelNumber = $this->m_cancel->get_cancel_qty_by_item_id($itemsDatas);
                $qty_map = $this->m_financial_audit->get_qty_info($itemsDatas);
                $configDatas = $this->db->from("purchase_lack")->select("number,style")->get()->row_array();


                // 获取供应商结算方式

                $settlementData = [];
                $settlement_code = array_unique(array_column($result,'account_type'));
                $settlement_db = $this->purchase_db->from('supplier_settlement')->select('settlement_code,settlement_name,settlement_percent')
                    ->where_in("settlement_code",$settlement_code)->get()->result_array();
                if($settlement_db && count($settlement_db) > 0){
                    foreach ($settlement_db as $val){
                        $settlementData[$val['settlement_code']] = $val;
                    }
                }
                foreach( $result as $key=>$value){
                    $result[$key]['cancel_number'] = isset($cancelNumber[$value['itemsid']]) ? $cancelNumber[$value['itemsid']] : 0;
                    $result[$key]['qty_number'] = isset($qty_map[$value['itemsid']]['loss_qty'])?$qty_map[$value['itemsid']]['loss_qty']:0;
                    $warehouseData = $this->getWarehouseData($value['purchase_number'],$value['sku'],$file);
                    foreach($fileData as $fileString){
                        $result[$key][$fileString] = isset($warehouseData[$fileString])?$warehouseData[$fileString]:'';
                    }

                    $result[$key]['imageurl'] = json_decode($value['imageurl'],true);

                    $result[$key]['person'] = $value['update_username'];
                    $summain = $this->getWarehouseSum($value['purchase_number'],$value['sku']);

                    foreach( $sumfileData as $manfile){
                        $result[$key][$manfile] = isset($summain[$manfile])?$summain[$manfile]:'';

                    }
                    $result[$key]['defective_ch'] = !empty($value['defective_type'])?getAbnormalDefectiveType($value['defective_type']):'';
                    $result[$key]['defective_ch'] = !empty($value['defective_type'])?getAbnormalDefectiveType($value['defective_type']):NULL;
                    if( !empty($value['quality_all'])){

                        if( $value['quality_all'] == 1){

                            $result[$key]['quality_all_ch'] = '否';
                        }else{
                            $result[$key]['quality_all_ch'] = '是';
                        }
                    }else{
                        $result[$key]['quality_all_ch'] = '';
                    }

                    $result[$key]['person_time'] = $value['update_time'];

                    $result[$key]['update_time'] = $value['person_time'];

                    if( !empty($value['quality_result'])){

                        if( $value['quality_result'] == 1){

                            $result[$key]['quality_result_ch'] = '合格';
                        }else{
                            $result[$key]['quality_result_ch'] = '不合格';
                        }
                    }else{
                        $result[$key]['quality_result_ch'] = '';
                    }
                    //处理状态 1表示无需处理,2表示未处理，3表示分批次到货，4订单已退款
                    if( $value['processing'] ==1 ){

                        $result[$key]['processing_ch'] = '无需处理';
                    }

                    if( $value['processing'] ==2 ){

                        $result[$key]['processing_ch'] = '未处理';
                    }

                    if( $value['processing'] ==3 ){

                        $result[$key]['processing_ch'] = '分批次到货';
                    }

                    if( $value['processing'] ==4 || $value['purchase_order_status'] == 14){

                        $result[$key]['processing_ch'] = '订单已退款';
                    }

                    $suggestStatus = $this->getSuggestStatus($value['purchase_number'],$value['sku']);

                    /**

                    采购单状态(1.等待采购询价,2.信息修改待审核,3.待采购审核,5.待销售审核,6.等待生成进货单,
                     * 7.等待到货,8.已到货待检测,9.全部到货,10.部分到货等待剩余到货,
                     * 11.部分到货不等待剩余到货,12.作废订单待审核,13.作废订单待退款,14.已作废订单,15.信息修改驳回)
                     **/
                    if( $suggestStatus == 14){

                        $result[$key]['processing_ch'] = '订单已退款';
                    }

                    if( $suggestStatus == 9){

                        $result[$key]['processing_ch'] = '无需处理';
                    }

//                    $fenpic = $this->getProccessStatus($value['purchase_number'],$configDatas);
//
//                    if( $fenpic == true && $value['type'] == 1){
//
//                        $result[$key]['processing_ch'] = '分批次到货';
//                    }

                    //少数少款类型 1 少款， 2 全部到货，3少数

                    if( $value['type'] == 1){

                        $result[$key]['type_ch'] = '少款';
                    }

                    if( $value['type'] == 2){

                        $result[$key]['type_ch'] = '全部到货';
                    }

                    if( $value['type'] == 3){

                        $result[$key]['type_ch'] = '少数';
                    }

                    // 结算方式
                    $result[$key]['settlement_ch'] = isset($settlementData[$value['account_type']]['settlement_name'])?$settlementData[$value['account_type']]['settlement_name']:'';
                    // 是否对接门户

                    if( $value['is_gateway'] == 0){

                        $result[$key]['is_gateway_ch'] = '否';
                    }else{
                        $result[$key]['is_gateway_ch'] = '是';
                    }

                    // source 1合同 2网络【默认】 3账期采购
                    $result[$key]['source_ch'] = isset($value['source'])?getPurchaseSource($value['source']):'';//采购单来源


                    //采购单状态 备货单状态 取消未到货状 报损状态

                    $result[$key]['purchase_status_ch'] = !empty($value['purchase_order_status'])?getPurchaseStatus($value['purchase_order_status']):NULL;
                    $result[$key]['demand_status_ch'] =  isset($demandNumbers[$value['demand_number']]['suggest_order_status'])?getPurchaseStatus($demandNumbers[$value['demand_number']]['suggest_order_status']):'';
                    $result[$key]['cancel_status_ch'] = isset($cancelDatas[$value['itemsid']])?get_cancel_status($cancelDatas[$value['itemsid']]):'未申请取消未到货'; //取消未到货状态

                    $lossDatas = $this->purchase_db->from("purchase_order_reportloss")->where("pur_number",$value['purchase_number'])
                        ->where("sku",$value['sku'])->select("status")->get()->row_array();

                    $result[$key]['loss_status_ch'] = !empty($lossDatas)?getReportlossApprovalStatus($lossDatas['status']):'未申请报损';
                    $pertain_wms = $this->Warehouse_model->get_warehouse_one($value['warehouse_code'], 'warehouse_name');// 获取公共仓
                    $result[$key]['pertain_wms'] = $pertain_wms;
                }

                $totalNumbers = $countQuery->select("lack.id")->count_all_results();
                //处理状态 1表示无需处理,2表示未处理，3表示分批次到货，4订单已退款
            }
            $this->load->model('user/Purchase_user_model');
            $data = $this->Purchase_user_model->get_list();
            $data = array_column(!empty($data)?$data:[], 'name','id');
            return [

                'list' => isset($result)?$result:[],
                'boxdata' => [

                    'defective' => !empty(getAbnormalDefectiveType())?getAbnormalDefectiveType():json_decode($this->defiveString,True),
                    'processing' => ['1'=>'无需处理','2'=>'未处理','3'=>'分批次到货','4'=>'订单已退款'],
                    //1 少款， 2 全部到货，3少数'
                    'type' => ['1'=>'少款','2'=>'全部到货','3'=>'少数'],
                    'buyer_name' => isset($data)?$data:[]

                ],
                'pages' =>[
                    'total_all' => isset($totalNumbers)?$totalNumbers:0,
                    'limit' => $limit,
                    'offset' =>  isset($params['offset'])?((int)$params['offset']-1):1
                ]
            ];
        }catch ( Exception $exp ){

            echo $exp->getMessage();
        }
    }

    /**
     * 备货单状态
     * @param $purchase_number  string  采购单号
     *        $sku              string  SKU
     **/
    public function getSuggestStatus($purchase_number,$sku = NULL){

        $result = $this->purchase_db->from("pur_purchase_suggest_map")->where("purchase_number",$purchase_number)
            ->where("sku",$sku)
            ->select("demand_number")
            ->get()->row_array();
        if(!empty($result)){

            $suggStatus = $this->purchase_db->from("purchase_suggest")->where("demand_number",$result['demand_number'])
                ->select("suggest_order_status")->get()->row_array();
            //备货单状态变为“部分到货不等待剩余”和“已作废订单时”，处理状态自动标记为“订单已退款”；
            //采购单状态(1.等待采购询价,2.信息修改待审核,3.待采购审核,5.待销售审核,6.等待生成进货单,
            //7.等待到货,8.已到货待检测,9.全部到货,10.部分到货等待剩余到货,
            //11.部分到货不等待剩余到货,12.作废订单待审核,13.作废订单待退款,14.已作废订单,15.信息修改驳回)
            return $suggStatus['suggest_order_status'];
        }

        return False;
    }

    /**
     * MQ 获取仓库推送的少数少款数据
     * @ 数据来源：仓库系统，MQ
     * @author:luxu
     * @time:2020/11/26
     **/

    public function pushLackData($mqString){


        if(!empty($mqString)){

            try {
                // 如果仓库系统推送过来数据
                $lackDatas = json_decode($mqString, True);
                // 获取配置数据
                $configDatas = $this->db->from("purchase_lack")->select("number,style")->get()->row_array();
                foreach($lackDatas as $lackData) {
                    // 仓库系统推送的基础数据标识，如果缺少 推送的数据不处理并且保存错误记录
                    if (isset($lackData['sku']) && isset($lackData['purchase_order_no'])) {

                    /**
                      若采购单里面有任何一个SKU到货点数了，则需要判断该采购单第几次到货，
                      是否已在在少数少款列表生成数据；
                     **/
                    //$purchaseLackData = $this->db->from("purchase_lack_data")->where("purchase_number", $lackData['purchase_order_no'])->get()->row_array();
                    // 少数少款类型
                        $lackType = $this->returnLackType($lackData['purchase_order_no'], $lackData['actual_num'],$lackData['is_accumulate'],$lackData['sku']);

                        if( "EMPTY" == $lackType){

                            continue;
                        }
                        $lackData['type'] = $lackType;
                        // 判断备货单是否存在

                        $where = [

                            'purchase_number' => $lackData['purchase_order_no'],
                            'sku'             => $lackData['sku']
                        ];

                        $lackResult = $this->db->from("purchase_lack_data")->where($where)->get()->result_array();
                        $purchaseLacks = $this->getPurchaseStype($lackData['purchase_order_no']);
                        $purchase_order_no = $lackData['purchase_number'] = $lackData['purchase_order_no'];
                        unset($lackData['purchase_order_no']);
                        unset($lackData['id']);
                        // 判断备货单采购数量
                        $purchaseConfirAmounts = $this->db->from("purchase_order_items")->where("purchase_number",$purchase_order_no)
                            ->where("sku",$lackData['sku'])
                            ->select("confirm_amount,sku")->get()->result_array();
                        $purchaseConfirAmounts['confirm_amount'] = array_sum(array_column($purchaseConfirAmounts, 'confirm_amount'));
                        if( empty($lackResult)){

                        //  如果备货单在少数少款没有生成数据
                        /**
                            若备货单未在少数少款列表生成数据，采购单下面所有的备货单少数少款类型全为“全部到货”，
                         *   则认为该采购单全部到货了，不需要进入该页面展示；
                         **/

                        // 判断备货单处理状态,处理状态 1表示无需处理,2表示未处理，3表示分批次到货，4订单已退款

                        if( $lackData['type'] == PURCHASE_ORDER_LACK_ALL){

                            $lackData['processing'] = 1;// 无需处理
                        }
                        // 数据判断为少数
                            $actualData = $this->actualConfirmData($purchase_order_no,$lackData['sku']);
                            // 实际到货数量

                            //$Actualhalf = ($purchaseConfirAmounts['confirm_amount'] -$actualData['cancelNumber'] - $actualData['qty_number'] );

                            $Actualhalf = $this->getWarehouseSum($lackData['purchase_number'],$lackData['sku']);
                            $Actualhalf = isset($Actualhalf['instock_qty'])?$Actualhalf['instock_qty']:0;
                            if ($lackData['type'] == PURCHASE_ORDER_LACK_QUE) {
                                // 如果采购数量小于配置数量，标记为未处理
                                if( $purchaseConfirAmounts['confirm_amount'] <$configDatas['number'] ){

                                    $lackData['processing'] = 2; // 未处理
                                }

                                // 采购数量大于等于配置数值，且实际到货数量大于等于（采购数量*50%），少数的处理状态为“未处理”；
                                if( $purchaseConfirAmounts['confirm_amount'] >= $configDatas['number'] &&
                                        $Actualhalf >= (int)($purchaseConfirAmounts['confirm_amount'] * 0.5 )) {

                                    $lackData['processing'] = 2; // 未处理
                                }

                                    // 采购数量大于等于配置数值，且实际到货数量小于（采购数量*50%），少数的处理状态为“分批次到货”；;
                                    if ($purchaseConfirAmounts['confirm_amount'] >= $configDatas['number'] &&
                                        (int)($purchaseConfirAmounts['confirm_amount'] * 0.5 )> $Actualhalf ) {

                                    $lackData['processing'] = 3; // 分批次到货
                                }
                            }


                        // 如果采购单少数少款数据为空就直接插入处理
                        if( NULL == $purchaseLacks){

                                $this->purchase_db->insert('purchase_lack_data',$lackData);
                            }else{
                                // 如果采购单少数少款数据不为空
                                if( True == $purchaseLacks){
                                    $this->purchase_db->insert('purchase_lack_data',$lackData);
                                }
                            }
                        }else{
                            if( $purchaseLacks != false) {
                                $Actualhalf = $this->getWarehouseSum($lackData['purchase_number'],$lackData['sku']);
                                $Actualhalf = isset($Actualhalf['instock_qty'])?$Actualhalf['instock_qty']:0;
                                $oldNumbers = $this->getActualNum($purchase_order_no,$lackData['sku'],NULL);
                                $lackData['actual_num'] = $oldNumbers + $lackData['actual_num'];
                                $lackData['person_time'] = date("Y-m-d H:i:s",time());
                                if( $lackData['type'] == PURCHASE_ORDER_LACK_ALL){

                                    $lackData['processing'] = 1; // 无需处理
                                }

                                if ($lackData['type'] == PURCHASE_ORDER_LACK_QUE) {
                                    // 如果采购数量小于配置数量，标记为未处理
                                    if( $purchaseConfirAmounts['confirm_amount'] <$configDatas['number'] ){

                                        $lackData['processing'] = 2; // 未处理
                                    }

                                    // 采购数量大于等于配置数值，且实际到货数量大于等于（采购数量*50%），少数的处理状态为“未处理”；
                                    if( $purchaseConfirAmounts['confirm_amount'] >= $configDatas['number'] &&
                                        $Actualhalf >= (int)($purchaseConfirAmounts['confirm_amount'] * 0.5 )) {

                                        $lackData['processing'] = 2; // 未处理
                                    }

                                    // 采购数量大于等于配置数值，且实际到货数量小于（采购数量*50%），少数的处理状态为“分批次到货”；;
                                    if ($purchaseConfirAmounts['confirm_amount'] >= $configDatas['number'] &&
                                        (int)($purchaseConfirAmounts['confirm_amount'] * 0.5 )> $Actualhalf ) {

                                        $lackData['processing'] = 3; // 分批次到货
                                    }
                                }

                                if( empty($lackData['type']) || $lackData['type'] ==0 || $lackData['type'] == PURCHASE_ORDER_LACK_STYLE){

                                    unset($lackData['type']);
                                }
                                $this->db->where($where)->update('purchase_lack_data', $lackData);
                            }
                        }
                    }
                }

                /**
                  * 修改处理状态
                 **/
                if(!empty($lackDatas)) {
                    //$purchaseLackDataNumber = array_unique(array_column($lackDatas, 'purchase_order_no'));
                    foreach ($lackDatas as $lackDataNumber) {
                        // 仓库系统推送的基础数据标识，如果缺少 推送的数据不处理并且保存错误记录
                        $fenpi = $this->getProccessStatus($lackDataNumber['purchase_order_no'], $configDatas);
                        $fenpiWhere['purchase_number'] = $lackDataNumber['purchase_order_no'];
                        $fenpiWhere['type'] = PURCHASE_ORDER_LACK_STYLE;
                        if ($fenpi == true) {
                            $fenpiUpdateData['processing'] = 3;
                        } else {
                            $fenpiUpdateData['processing'] = 2;
                        }
                        $fenpiUpdateData['add_time'] = $lackDataNumber['create_time'];
                        $this->purchase_db->where($fenpiWhere)->update("purchase_lack_data", $fenpiUpdateData);
                    }
                }

            }catch ( Exception $exp ){

                echo $exp->getMessage();
            }

        }
    }

    /**
     * 设置分批次：分批次到货：勾选数据，标记分批次到货，只有处理状态为“未处理”才允许进行该操作，若不满足，勾选时前端提示：
     *   “请选择处理状态为“未处理”的进行处理”，确认之后处理状态更新为：“分批次到货”，记录最新的处理人和处理时间
     * @param: ids  array
     * @author:luxu
     * @time:2020/11/28
     **/
    public function setBatches($ids){

        try {

                $result = $this->purchase_db->from("purchase_lack_data")->where_in("id", $ids)->get()->result_array();

            foreach ($result as $value) {
//                if ($value['processing'] != 2) {
//
//                    throw new Exception("请选择处理状态为'未处理'的进行处理");
//                }

            $updateData = [

                'processing' => 3,
                'update_time' => date('Y-m-d H:i:s',time()),
                'update_username' => getActiveUserName()
            ];

                    // 记录日志
                    $logs = [
                        'update_time' => date('Y-m-d H:i:s', time()),
                        'username' => getActiveUserName(),
                        'purchase_number' => $value['purchase_number'],
                        'sku' => $value['sku'],
                        'lackid' => $value['id'],
                        'logs' => json_encode(['old_processing' =>$value['processing'], 'new_processing' => 3])
                    ];
                    $result = $this->purchase_db->where("id", $value['id'])->update('purchase_lack_data', $updateData);
                    if (!$result) {
                        throw new Exception("处理失败");
                    }
                    $this->purchase_db->insert('lack_logs', $logs);
                }
            }catch (Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 订单已退款：勾选数据，标记少货已退款，只有处理状态为“未处理”、“分批次到货”才允许进行该操作，
     * 若不满足，勾选时前端提示：“请选择处理状态为“未处理”、“分批次到货”的进行处理”处理状态更新为：
     * “订单已退款”，记录最新的处理人和处理时间；
     * @param: ids  array
     * @author:luxu
     * @time:2020/11/28
     **/

    public function setMoney($ids){

        try{

            $result = $this->purchase_db->from("purchase_lack_data")->where_in("id",$ids)->get()->result_array();

            foreach($result as $value) {
//                if( $value['processing'] ==2 || $value['processing'] ==3) {


            $updateData = [

                'processing' => 4,
                'update_time' => date('Y-m-d H:i:s',time()),
                'update_username' => getActiveUserName()
            ];

                // 记录日志
                $logs = [
                    'update_time' => date('Y-m-d H:i:s', time()),
                    'username' => getActiveUserName(),
                    'purchase_number' => $value['purchase_number'],
                    'sku' => $value['sku'],
                    'lackid' => $value['id'],
                    'logs' => json_encode(['old_processing' => $value['processing'], 'new_processing' => 4])
                ];
                    $result = $this->purchase_db->where("id", $value['id'])->update('purchase_lack_data', $updateData);
                if (!$result) {
                    throw new Exception("处理失败");
                }
                $this->purchase_db->insert('lack_logs', $logs);
//                }else{
//                    throw new Exception("请选择处理状态为'未处理' 或者 分批次到货的订单进行处理");
//                }
            }
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
      * 读取仓库系统推送的MQ数据
     * 采购系统开发环境RMQ 信息：
            exchanges : PURCHASE_OUT_OF_STOCK_DATA

            key:PURCHASE_STOCK_KEY

            queues:PURCHASE_OUT_OF_STOCK
     **/
    public function getQueue(){

        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('PURCHASE_OUT_OF_STOCK');
        $mq->setExchangeName('PURCHASE_OUT_OF_STOCK_DATA');
        $mq->setRouteKey('PURCHASE_STOCK_KEY');
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        //构造存入数据
        //存入消息队列
        $queue_obj = $mq->getQueue();
        //处理生产者发送过来的数据
        $envelope = $queue_obj->get();
        $data = NULL;
        if($envelope) {
            $data = $envelope->getBody();

            $queue_obj->ack($envelope->getDeliveryTag());
        }
        $mq->disconnect();
        return $data;
    }

    /**
     * 获取日志信息
     * @param
     * @author:luxu
     * @time:2020/11/30
     **/

    public function getLogs($ids){

        $result = $this->purchase_db->from("lack_logs")->where("lackid",$ids)->get()->result_array();
        return $result;
    }

    /**
     * 添加少数少款备注功能
     * @param
     * @author:luxu
     * @time:2021年1月10号
     **/

    public function setLockMessage($ids,$message,$imageurl){

        try{

            $update =[

                'message' => $message,
                'imageurl' => json_encode($imageurl)
            ];
            $resultData = $this->purchase_db->from("purchase_lack_data")->where_in("id", $ids)->get()->result_array();

            $result = $this->purchase_db->where_in("id",$ids)->update('purchase_lack_data',$update);
            if($result) {
                foreach ($resultData as $value) {
                    // 记录日志
                    $logs = [
                        'update_time' => date('Y-m-d H:i:s', time()),
                        'username' => getActiveUserName(),
                        'purchase_number' => $value['purchase_number'],
                        'sku' => $value['sku'],
                        'lackid' => $value['id'],
                        'logs' =>  $message
                    ];

                    $this->purchase_db->insert('lack_logs', $logs);
                }
                return True;
            }

            throw new Exception("添加失败");
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }
}