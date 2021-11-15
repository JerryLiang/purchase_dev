<?php

/**
 * 线下退款列表
 * Class Offline_receipt_model
 * @author Jolon
 * @date 2021-01-12 14:50:01
 */

class Offline_refund_model extends Purchase_model{

    public $receipt_model = 'offline_receipt'; // 线下申请退款数据表
    public $receipt_log_model = 'receipt_log'; // 日志表
    public function __construct() {
        parent::__construct();
        $this->load->model('finance/Offline_receipt_model');
        $this->load->model('system/Offline_reason_model');
    }

    /**
     * 统计当前当前页面数据
     * @param $listData   array   当前页面的数据
     * @author:luxu
     * @tim:2021年1月13号
     **/
    public function getPageData($listData){

        $return = [

            'current_sp_count' => 0, // 供应商数据
            'current_refund_price' => 0, // 申请金额
            'current_receipted_price' =>0 // 收款金额
        ];

        if(empty($listData)){

            return $return;
        }

        $current_sp_count = [];

        foreach($listData as $listData_key=>$listData_value){

            if($listData_value['refund_status']!=4){

                $current_sp_count[] = $listData_value['supplier_code'];
                $return['current_refund_price'] += $listData_value['refund_price'];
            }

            if($listData_value['refund_status'] == 2){

                $return['current_receipted_price'] +=$listData_value['receipted_price'];
            }
        }
        $return['current_sp_count'] = count(array_unique($current_sp_count));



//        $return['current_sp_count'] = count(array_unique(array_column($listData,"supplier_code")));
//        $return['current_refund_price'] = array_sum(array_column($listData,'refund_price')); // 应收款数据
//        $return['current_receipted_price'] = array_sum(array_column($listData,'receipted_price')); // 应收款数据
        return $return;
    }

    /**
     * 退款模块根据refund_log_number 退款物流单号查询
     * @param $refund_log_number   array|string  退款物流单号
     *        $feild               string            获取字段
     * @author:luxu
     * @time:2021年1月16号
     **/
    public function get_logistics_info($refund_log_number,$feild=NULL){

        $query = $this->purchase_db->from("purchase_logistics_info")->distinct(true);
        if( !is_array($refund_log_number)){
            $query->where("express_no",$refund_log_number);
        }

        if( is_array($refund_log_number)){
            $query->where_in("express_no",$refund_log_number);
        }

        $result = $query->select($feild)->get()->result_array();
        return $result;
    }

    /**
     * 获取线下退款列表数据
     * @param $params  array  查询参数
     * @author:luxu
     * @time:2021年1月12号
     **/
    public function get_offline_refund($params,$limit,$offset,$httppage=NULL){

        try{

            $search_user_ids = [];
            if( isset($params['groupname']) && !empty($params['groupname'])){
                $this->load->model('user/User_group_model', 'User_group_model');

                if(is_array($params['groupname'])){
                    foreach($params['groupname'] as $groupname){
                        $groupids = $this->User_group_model->getGroupPersonData($groupname);
                        if(!empty($groupids)){
                            $search_user_ids = array_merge($search_user_ids,array_column($groupids,'value'));
                        }
                    }
                }else{
                    $groupids = $this->User_group_model->getGroupPersonData($params['groupname']);
                    if(!empty($groupids)){
                        $search_user_ids = array_column($groupids,'value');
                    }
                }
            }

            $query = $this->purchase_db->from($this->receipt_model.' AS offline');

            // 线下退款编号
            if( isset($params['refund_number']) && !empty($params['refund_number'])){

                $query->where("offline.refund_number",$params['refund_number']);
            }

            if( isset($params['groupname']) && !empty($params['groupname'])){
                $this->purchase_db->where_in('offline.apply_user_id',$search_user_ids);
            }

            // 申请人查询

            if( isset($params['apply_user_id']) && !empty($params['apply_user_id'])){

                $this->purchase_db->where_in("offline.apply_user_id",$params['apply_user_id']);
            }

            // 物流单号
            if( isset($params['refund_log_number']) && !empty($params['refund_log_number'])){

                $query->where("offline.refund_log_number",$params['refund_log_number']);
            }
            // 采购单号
            if( isset($params['purchase_number']) && !empty($params['purchase_number'])){
                $query->like("offline.purchase_number_multi",$params['purchase_number']);
            }

            // 申请人
            if( isset($params['apply_user_name']) && !empty($params['apply_user_name'])){

                $query->where_in("offline.apply_user_name",$params['apply_user_name']);
            }

            // 申请时间

            if( isset($params['apply_time_start']) && !empty($params['apply_time_start'])){
                $query->where("offline.apply_time>=",$params['apply_time_start']);
            }
            if( isset($params['apply_time_end']) && !empty($params['apply_time_end'])){
                $query->where("offline.apply_time<=",$params['apply_time_end']);
            }

            // 退款状态
            if( isset($params['refund_status']) && !empty($params['refund_status'])){
                $query->where_in("offline.refund_status",$params['refund_status']);
            }

            // 退款流水号
            if( isset($params['refund_water_number']) && !empty($params['refund_water_number'])){
                $refund_water_numbers = array_filter(explode(' ',$params['refund_water_number']));// 多个拆分后模糊查询
                $this->purchase_db->group_start();
                foreach ($refund_water_numbers as $refund_water_number){
                    $this->purchase_db->or_like('offline.refund_water_number',$refund_water_number);
                }
                $this->purchase_db->group_end();

            }
            // 退款渠道
            if( isset($params['refund_channel']) && !empty($params['refund_channel'])){
                $query->where("offline.refund_channel",$params['refund_channel']);
            }
            // 退款原因
            if( isset($params['refund_reason']) && !empty($params['refund_reason'])){
                $query->where_in("offline.refund_reason",$params['refund_reason']);
            }
            // 供应商
            if( isset($params['supplier_code']) && !empty($params['supplier_code'])){
                $query->where_in("offline.supplier_code",$params['supplier_code']);
            }
            // 退款金额
            if( isset($params['refund_price_start']) && !empty($params['refund_price_start'])){
                $query->where("offline.refund_price>=",$params['refund_price_start']);
            }

            if( isset($params['refund_price_end']) && !empty($params['refund_price_end'])){
                $query->where("offline.refund_price<=",$params['refund_price_end']);
            }

            // 合同号
            if( isset($params['compact_number']) && !empty($params['compact_number'])){

                $compact_number_list = array_filter(explode(' ',$params['compact_number']));// 多个拆分后模糊查询
                $this->purchase_db->group_start();
                foreach ($compact_number_list as $compact_number_val){
                    $this->purchase_db->or_like('compact_number_multi',$compact_number_val);
                }
                $this->purchase_db->group_end();
            }

            // 对账单号
            if( isset($params['statement_number']) && !empty($params['statement_number'])){

                $statement_numbers = array_filter(explode(' ',$params['statement_number']));// 多个拆分后模糊查询
                $this->purchase_db->group_start();
                foreach ($statement_numbers as $statement_number){
                    $this->purchase_db->or_like('offline.statement_number',$statement_number);
                }
                $this->purchase_db->group_end();
            }
            // 收款时间
            if( isset($params['receipt_time_start']) && !empty($params['receipt_time_start'])){
                $query->where("offline.receipt_time>=",$params['receipt_time_start']);
            }

            if( isset($params['receipt_time_end']) && !empty($params['receipt_time_end'])){
                $query->where("offline.receipt_time<=",$params['receipt_time_end']);
            }
            $avgData =  clone $query; // 统计数据资源
            $totalData = clone $query; // 统计数据总条数
            $current_receipted = clone $query; // 收款金额
            $listResult = $query->limit($limit,$offset)->order_by("offline.id DESC")->get()->result_array();
            if(!empty($listResult)){

                $refund_log_number = array_column($listResult,'refund_log_number');
                $logistics_info = $this->get_logistics_info($refund_log_number,'cargo_company_id,status,express_no');

                $logistics_info_data = [];

                if(!empty($logistics_info)){

                    $logistics_info_data = array_column($logistics_info,NULL,"express_no");
                }
                foreach($listResult as &$datavalue){

                    if($datavalue['apply_time'] != '0000-00-00 00:00:00'){

                        $datavalue['apply_time'] = date("Y-m-d",strtotime($datavalue['apply_time']));
                    }

                    if($datavalue['refund_time'] != '0000-00-00 00:00:00'){

                        $datavalue['refund_time'] = date("Y-m-d",strtotime($datavalue['refund_time']));
                    }

                    if($datavalue['receipt_time'] != '0000-00-00 00:00:00'){

                        $datavalue['receipt_time'] = date("Y-m-d",strtotime($datavalue['receipt_time']));
                    }

                    $purchase_name_cn = NULL;
                    if($datavalue['purchase_name'] == 'HKYB') {
                        $purchase_name_cn = 'YIBAI TECHNOLOGY LTD';
                    }

                    if($datavalue['purchase_name'] == 'SZYB') {
                        $purchase_name_cn = '深圳市易佰网络科技有限公司';
                    }

                    if($datavalue['purchase_name'] == 'QHYB') {
                        $purchase_name_cn = '深圳前海新佰辰科技有限公司';
                    }

                    $datavalue['purchase_name_cn'] = $purchase_name_cn;
                    $datavalue['cargo_company_name'] = $logistics_info_data[$datavalue['refund_log_number']]['cargo_company_id'];
                    $datavalue['status'] = isset($logistics_info_data[$datavalue['refund_log_number']])?getTrackStatus($logistics_info_data[$datavalue['refund_log_number']]['status']):'-';
                    $datavalue['refund_status_cn'] = '-';
                    //退款状态(1.待财务收款;2.已收款;3.财务驳回;4.已作废
                    if( $datavalue['refund_status'] == 1){

                        $datavalue['refund_status_cn'] = '待财务收款';
                    }

                    if( $datavalue['refund_status'] == 2){

                        $datavalue['refund_status_cn'] = '已收款';
                    }

                    if( $datavalue['refund_status'] == 3){

                        $datavalue['refund_status_cn'] = '财务驳回';
                    }

                    if( $datavalue['refund_status'] == 4){

                        $datavalue['refund_status_cn'] = '已作废';
                    }

                    //refund_channel_cn

                    if($datavalue['refund_channel'] == 5){

                        $datavalue['refund_channel_cn'] = '网银转账';
                    }else{
                        $datavalue['refund_channel_cn'] = '支付宝';
                    }


                }
            }
            $count_list = $avgData
                ->select('COUNT(1) AS total,COUNT(DISTINCT offline.supplier_code) AS supplier_code_count,
                SUM(offline.refund_price) AS refund_price_total,SUM(offline.receipted_price) AS receipted_price_total')
                ->where("refund_status!=",4)
                ->get()->row_array();

            // 收款金额

            $current_receipted_price = $current_receipted->select('SUM(offline.receipted_price) AS receipted_price_total')
                ->where("refund_status=",2)
                ->get()->row_array();

            // 计算数据总条数

            $pageCount = $totalData->select("count(id) as total")->get()->row_array();


            $datas =  $this->getPageData($listResult);


            return [

                'values'=>$listResult,
                'aggregate_data' => [

                    'current_sp_count' => $datas['current_sp_count'], // 供应商数据
                    'current_refund_price' => round($datas['current_refund_price'],2), //申请金额
                    'current_receipted_price' => round($datas['current_receipted_price'],2), //收款金额
                    'all_sp_count' =>$count_list['supplier_code_count'],//总供应商个数
                    'all_refund_price' => round($count_list['refund_price_total'],2),
                    'all_receipted_price' =>round($current_receipted_price['receipted_price_total'],2),
                    'page_total' => count($listResult),
                    'total' => $pageCount['total']
                ],
                'drop_down_box' => [
                    'refund_status' => $this->Offline_receipt_model->refund_status_list,
                    'refund_reason_list' =>$this->Offline_reason_model->get_refund_reason_list()
                ],
                'paging_data' =>[

                    'total' =>$pageCount['total'],
                    'offset' => $httppage,
                    'limit' => $limit,
                    'page_total' => count($listResult)
                ]
            ];
        }catch ( Exception $exp ){


        }
    }

    /**
     * 获取供应商名称
     * @param type $supplier_code
     * @return string
     * @author luxu
     */
    public function get_supplier_name($supplier_code)
    {
        $supplier = $this->purchase_db
            ->select('supplier_name,supplier_code')
            ->where_in('supplier_code', $supplier_code)
            ->get('supplier')
            ->result_array();
        if (empty($supplier)) {
            return '';
        }
        return $supplier;
    }

    /**
     * 获取采购单相关信息方法
     * @param $purchaseNumber  string  采购单号
     *        $fileds    string   获取字段
     * @author:luxu
     * @time:2021年1月15号
     **/
    public function getOrdersMessage($purchaseNumber,$fileds='*'){

        $result = $this->purchase_db->from("purchase_order")->where_in("purchase_number",$purchaseNumber)->select($fileds)
            ->get()->result_array();

        if(!empty($result)){
            return array_column($result,NULL,"purchase_number");
        }
        return NULL;
    }

    /**
     * 审查请款单号
     * @param $data  array  申请请款数据
     * @author:luxu
     * @time:2021年1月15号
     **/

    public function get_refund_number($data){

        $returnData =[];

        foreach($data as $key=>$dataValue){

            $keys = $dataValue['supplier_code']."-".$dataValue['refund_water_number']."-".$dataValue['refund_price'];
            if(!isset($returnData[$keys])){

                $returnData[$keys] = "TK".date("YmdHis").rand(0,1000).$key;
            }
        }

        return $returnData;
    }

    /**
     * 校验HTTP 传入参数是否重复
     * @params $clientdata   array HTTP 传入参数
     * @author:luxu
     * @time:2021年1月16号
     **/

    public function verifyData($clientdata){

        try {
            /*
             * 申请提交时校验:多行的"供应商+退款流水号+退款金额"全部一致时,
               提示:当前提交的数据重复,请确认后在次提交
             */

            $verifyData = []; // 定义缓存数据区

            foreach ($clientdata as $key => $value) {

                $verifyKeys = $value['supplier_code'] . "-" . $value['refund_water_number'] . "-" . $value['refund_price'];
                if (isset($verifyData[$verifyKeys])) {

                    throw new Exception("当前提交的数据重复,请确认后在次提交");
                } else {
                    $verifyData[$verifyKeys] = True;
                }

                $refund_water_number = explode(" ",$value['refund_water_number']);
                if(count($refund_water_number)>1){

                    throw new Exception("退款流水号只能填写一个,请勿加空格");
                }

                $statement_number = explode(' ',$value['statement_number']);

                if(count($statement_number)>1){
                    throw new Exception("对账单号只能填写一个,请勿加空格");
                }

                $abnormal_number = explode(' ',$value['abnormal_number']);

                if(count($abnormal_number)>1){
                    throw new Exception('异常单号只能填写一个,请勿加空格');
                }

                $refund_water_number = explode(' ',$value['refund_water_number']);
                if(count($refund_water_number)>1){
                    throw new Exception('退货物流单号只能填写一个,请勿加空格');
                }

                $pai_number = explode(' ',$value['pai_number']);
                if(count($pai_number)>1){
                    throw new Exception('拍单号只能填写一个,请勿加空格');
                }


            }
        }catch ( Exception $exp){

            throw new Exception($exp->getMessage());
        }

    }



    /**
     * 添加退款数据
     * @param
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function add_offline_refund($addDatas){

        if(empty($addDatas)){
            return NULL;
        }
        try{
            $this->load->model('system/Offline_reason_model');
            // 验证HTTP 传入的参数是否重复
            $this->verifyData($addDatas);
            $reasonData = $this->Offline_reason_model->get_offline_select_reason(1);
            if(!empty($reasonData)){
                $reasonData = array_column($reasonData,NULL,"id");
            }
            // 统一获取传入的供应商CODE，进行批量获取供应商名称
            $supplierCodes = array_column($addDatas,"supplier_code");
            $supplierNames = $this->get_supplier_name($supplierCodes);
            $supplierNames = array_column($supplierNames,NULL,"supplier_code");
            $purchaseOrdersPrev = array_map(function($orders){

                if(!empty($orders)){

                    $mulitOrders = explode(" ",$orders);
                    return $mulitOrders[0];
                }
            },array_column($addDatas,"purchase_number"));

            $orderMess= $this->getOrdersMessage($purchaseOrdersPrev,'purchase_name,purchase_number');
            $refund_numberDatas = $this->get_refund_number($addDatas);

            foreach($addDatas as $key=>$value){

                // 验证采购单号是否存在、供应商是否一致
                if(!empty($value['purchase_number'])){
                    $purchase_number_arr = array_unique(array_filter(explode(' ',$value['purchase_number'])));
                    $poInfo = $this->purchase_db->select('purchase_number,supplier_code')
                        ->from('pur_purchase_order')
                        ->where_in('purchase_number',$purchase_number_arr)
                        ->get()
                        ->result_array();
                    if(count($poInfo) != count($purchase_number_arr)){
                        throw new Exception("采购单号不存在：".implode(' ',array_diff($purchase_number_arr,array_column($poInfo,'purchase_number'))));
                    }

                    $now_supplier_code = array_unique(array_column($poInfo,'supplier_code'));
                    if(count($now_supplier_code) > 1){
                        throw new Exception("采购单不是同一个供应商,请确认后在次提交");
                    }

                    foreach($poInfo as $poInfoValue){
                        if($value['supplier_code'] != $poInfoValue['supplier_code']){
                            throw new Exception($poInfoValue['purchase_number']."的供应商与申请退款的供应商不一致,请检查");
                        }
                    }
                }

                // 验证合同单号是否存在、供应商是否一致
                if(!empty($value['compact_number_multi'])){
                    $compact_number_arr = array_unique(array_filter(explode(' ',$value['compact_number_multi'])));
                    $compactInfo = $this->purchase_db->select('compact_number,supplier_code')
                        ->from('pur_purchase_compact')
                        ->where_in('compact_number',$compact_number_arr)
                        ->get()
                        ->result_array();
                    if(count($compactInfo) != count($compact_number_arr)){
                        throw new Exception("合同单号不存在：".implode(' ',array_diff($compact_number_arr,array_column($compactInfo,'compact_number'))));
                    }

                    $now_supplier_code = array_unique(array_column($compactInfo,'supplier_code'));
                    if(count($now_supplier_code) > 1){
                        throw new Exception("合同单不是同一个供应商,请确认后在次提交");
                    }

                    foreach($compactInfo as $compactInfoValue){
                        if($value['supplier_code'] != $compactInfoValue['supplier_code']){
                            throw new Exception($compactInfoValue['compact_number']."的供应商与申请退款的供应商不一致,请检查");
                        }
                    }
                }

                // 验证对账单号是否存在、供应商是否一致
                if(!empty($value['statement_number'])){
                    $statement_number_arr = array_unique(array_filter(explode(' ',$value['statement_number'])));
                    $stInfo = $this->purchase_db->select('statement_number,supplier_code')
                        ->from('purchase_statement')
                        ->where_in('statement_number',$statement_number_arr)
                        ->get()
                        ->result_array();
                    if(count($stInfo) != count($statement_number_arr)){
                        throw new Exception("对账单号不存在：".implode(' ',array_diff($statement_number_arr,array_column($stInfo,'statement_number'))));
                    }

                    $now_supplier_code = array_unique(array_column($stInfo,'supplier_code'));
                    if(count($now_supplier_code) > 1){
                        throw new Exception("对账单不是同一个供应商,请确认后在次提交");
                    }

                    foreach($stInfo as $stInfoValue){
                        if($value['supplier_code'] != $stInfoValue['supplier_code']){
                            throw new Exception($stInfoValue['statement_number']."的供应商与申请退款的供应商不一致,请检查");
                        }
                    }
                }

                $verifyWhere = [

                    'supplier_code' => $value['supplier_code'],
                    'refund_water_number' => $value['refund_water_number'],
                    'refund_price' => $value['refund_price']
                ];
                if(!isset($value['refund_reason_id']) && !empty($value['refund_reason_id'])) {

                    $verifyData = $this->purchase_db->from($this->receipt_model)->where($verifyWhere)->select("id")->get()->row_array();
                    if(!empty($verifyData)){

                        throw new Exception("当前提交的数据重复,请确认后在次提交");
                    }
                }

                if( !isset($value['refund_reason_id']) || empty($value['refund_reason_id'])){

                    throw new Exception("请传入退款原因ID");
                }

                if( isset($reasonData[$value['refund_reason_id']]) && !empty($reasonData[$value['refund_reason_id']])){

                    $reasonDataValue = $reasonData[$value['refund_reason_id']];
                    if( $reasonDataValue['purchase_number_need'] == 1 && empty($value['purchase_number'])){

                        throw new Exception("请填写采购单号");
                    }

                    if( $reasonDataValue['logistics_number_need'] == 1 && empty($value['refund_log_number'])){

                        throw new Exception("请填写退款物流单号");
                    }
                }else{
                    throw new Exception("申请原因填写错误");
                }

                $addDatas[$key]['purchase_number_multi'] = $value['purchase_number'];
                $refund_numbe_key = $value['supplier_code']."-".$value['refund_water_number']."-".$value['refund_price'];
                if(!isset($value['refund_id']) && empty($value['refund_id'])) {
                    $addDatas[$key]['refund_number'] = isset($refund_numberDatas[$refund_numbe_key]) ? $refund_numberDatas[$refund_numbe_key] : "TK" . date("YmdHis") . rand(0, 1000) . $key;
                }
                $addDatas[$key]['supplier_name'] = isset($supplierNames[$value['supplier_code']])?$supplierNames[$value['supplier_code']]['supplier_name']:'';
                unset($addDatas[$key]['purchase_number']);
                // 获取采购主体
                $addDatas[$key]['purchase_name'] = '';
                if(!empty($addDatas[$key]['purchase_number_multi'])) {
                    $multi = explode(" ", $addDatas[$key]['purchase_number_multi']);
                    $addDatas[$key]['purchase_name'] = !empty($orderMess[$multi[0]]['purchase_name'])?$orderMess[$multi[0]]['purchase_name']:'';
                }
                $addDatas[$key]['apply_user_name'] = getActiveUserName();
                $addDatas[$key]['apply_user_id']  = getActiveUserId();
                $addDatas[$key]['apply_time'] = date("Y-m-d H:i:s");
                $addDatas[$key]['refund_type'] = $reasonData[$value['refund_reason_id']]['refund_type'];
                $addDatas[$key]['refund_reason'] =  $reasonData[$value['refund_reason_id']]['refund_reason'];
            }

            $idsData = array_column($addDatas,'refund_id');
            // 如果HTTP 传值不包含ID 表示添加
            if(empty($idsData)) {
                foreach($addDatas as $updatas) {
                    unset($updatas['reason_remark']);
                    $result = $this->purchase_db->insert($this->receipt_model, $updatas);
                    $previds = $this->purchase_db->insert_id();
                    $this->add_log_data($updatas['apply_notice'],"待财务收款",$previds);

                }
            }else{
                foreach($addDatas as $inser_updatas){
                    $updataid = $inser_updatas['refund_id'];
                    $log_id = $inser_updatas['refund_id'];
                    $inser_updatas['refund_status'] = 1;
                    unset($inser_updatas['refund_id']);
                    unset($inser_updatas['reason_remark']);
                    $this->purchase_db->where("id",$updataid)->update($this->receipt_model,$inser_updatas);
                    $this->add_log_data($inser_updatas['apply_notice'],"重新编辑",$log_id);
                }
            }
            return $result;

        }catch ( Exception $exp ){

                throw new Exception($exp->getMessage());
        }


    }

    /**
     * 添加日志信息
     * @params $message  string  备注信息
     *         $type     string  日志类型
     *         $refund_id int    修改退款申请表offline_receipt 数据ID
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function add_log_data($message = '',$type='',$refund_id=NULL){

        $logs = [

            'username' => getActiveUserName(), // 申请人
            'addtime'  => date("Y-m-d H:i:s"), //操作时间
            'type' => $type,
            'message' => $message,
            'refund_id' =>$refund_id
        ];
        $this->purchase_db->insert($this->receipt_log_model,$logs);

    }

    /**
     * 作废申请
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function cancel_refund($refund_id,$message){

        $update = [

            'refund_status' => 4, // 作废标识
            'cancel_message' => $message
        ];
        $result = $this->purchase_db->where("id",$refund_id)->update($this->receipt_model,$update);
        if($result){
            $this->add_log_data($message,"已作废",$refund_id);
            return True;
        }

        return False;
    }

    /**
     * 获取退款列表日志
     * @author:luxu
     * @time:2021年1月14号
     **/
    public function get_refund_logs($refund_id){

        $result = $this->purchase_db->from($this->receipt_log_model)->where("refund_id",$refund_id)->get()->result_array();
        return $result;
    }

    /**
     * 获取1688拍单号
     * 1688拍单号根据填写的采购单号抓取"采购单"页面的同字段内容;采购单号无拍单号的.
     * 显示为空,采购单号为多个时,抓取第一个拍单号;若第一个为空,顺位抓取下一个拍单号;若全部为空,则显示为空
     * @methods  GET
     * @author:luxu
     * @time:2021年1月15号
     **/
    public function get_pai_number($purchase_number){

        $return = [];
        foreach($purchase_number as $number){
            $purchase_numbers = explode(' ',$number);
            $paiNumber = $this->purchase_db->from("purchase_order_pay_type")->where_in("purchase_number",$purchase_numbers)->select("purchase_number,pai_number")
                ->get()->result_array();
            if(empty($paiNumber)){

                $return[$number] = '';
                continue;
            }
            $paiNumber = array_column($paiNumber,NULL,"purchase_number");
            foreach($purchase_numbers as $purchaseNumber){

                if( isset($paiNumber[$purchaseNumber]) && !empty($paiNumber[$purchaseNumber]['pai_number'])){

                    $return[$number] = $paiNumber[$purchaseNumber]['pai_number'];
                    continue;
                }else{
                    $return[$number] = NULL;
                }
            }

        }
        return $return;
    }
}