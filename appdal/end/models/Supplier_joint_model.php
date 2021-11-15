<?php

/**
 * 供应商门户对接
 * User: Jerry
 * Date: 2019/12/11
 */
class Supplier_joint_model extends Purchase_model
{

    protected $db_name = 'default';// 默认数据库名
    protected $table_name = '';// 数据表名称
    protected $purchase_order_table = 'pur_purchase_order';
    protected $purchase_order_item_table = 'pur_purchase_order_items';
    protected $purchase_compact_table = 'pur_purchase_compact';
    protected $purchase_compact_item_table = 'pur_purchase_compact_items';
    protected $supplier_table = 'pur_purchase_compact_items';
    protected $supplier_conpact_file_table = 'pur_purchase_compact_file';
    protected $supplier_supplier_info_table = 'pur_supplier_web_info';//供应商门户和采购系统合同中间表
    protected $supplier_supplier_audit_table = 'pur_supplier_web_audit';//供应商门户和采购系统sku中间表
    protected $supplier_supplier_audit_log_table = 'pur_supplier_web_audit_log';
    protected $supplier_supplier_info_log_table = 'pur_supplier_web_info_log';
    protected $warehourse_result_table = 'pur_warehouse_results';
    protected $warehourse_table = 'pur_warehouse';
    protected $product_table = 'pur_product';
    protected $purchase_order_pay_type_table = 'pur_purchase_order_pay_type';
    protected $purchase_map_table = 'pur_purchase_suggest_map';

    protected $purchase_method = '/provider/txSendeKafkaMsg';//推送采购单
    protected $compact_status_method = '/provider/yibaiSupplierCompact/updateCompactStatus';//合同管理/合同状态更新
    protected $compact_method = '/provider/txSendeKafkaMsg';//推送合同信息
    protected $predict_time_method = '/provider/purPush/pushOrderAudis';//推送确认预计到货时间审核
//    protected $predict_time_method = '/purPush/pushOrderAudis';//推送确认预计到货时间审核
    protected $purchase_status_method = '/provider/purPush/pushOrderStatus';//推送订单状态为部分到货不等待剩余
//    protected $purchase_status_method = '/purPush/pushOrderStatus';//推送订单状态为部分到货不等待剩余
//    protected $warehourse_result_method = '/provider/purPush/pushLogisticsInfo';//推送快递单号入库情况
    protected $warehourse_result_method = '/purPush/pushLogisticsInfo';//推送快递单号入库情况
    protected $supplier_status_method = '/provider/yibaiSupplier/getSupplierListByEnabled';//查询供应商状态信息
    protected $purchase_cancel_method = '/provider/purPush/orderCancel';//推送订单作废
    protected $is_start = 'test'; //目前生产环境 proc 先屏蔽 test开启

//    protected $supplier_method = '/provider/yibaiPurSupplier/publishSupplierInfo';//推送供应商信息

    protected $errorTypeEnum = [
        'purchaseData'=>1, // 采购单信息标识为1
        'CompactData'=>2, // 合同数据
        'PurchaseStatus' => 4, // 推送订单部分到货不等待剩余
        'PurchaseCancel' =>7 ,// 推送订单作废
        'CompactStatusData' => 9, //合同管理/合同状态更新
        'PredictTimeStatus' => 3, // 推送确认时间
        'SendProviderLabel' =>5,//推送标签给门户
        'ProviderPromiseBarcode' =>6,//是否承诺贴码
        'SendCancelDetailToProvider' =>8,//取消数量推送门户




    ];

    function __construct()
    {
        parent::__construct();
//        SMC_JAVA_API_URL
        $this->load->helper('common');
    }

    /**
     * 门户系统推送的采购单状态
     * @param $purchaseList  array  门户系统退税采购单数据
     * @author:luxu
     * @time:2020/5/28
     **/
    public function setPurchaseNumberStatus($purchaseList){

        try{

            $success = $errors = [];
            foreach($purchaseList as $key=>$value){

                $where = [

                    'purchase_number' => $value['purchase_number'],
                    'sku' => $value['sku']
                ];

                $result = $this->purchase_db->where($where)->update('purchase_order_items',['gateway_status'=>$value['status']]);
                if($result){
                    $success[] = ['sku'=>$value['sku'],'purchase_number'=>$value['purchase_number']];
                }else{
                    $errors[] = ['sku'=>$value['sku'],'purchase_number'=>$value['purchase_number']];
                }
            }
            return [

                'success' => $success,
                'errors' => $errors
            ];
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    /**
     * 记录错误日志到MYSQL
     * @param   $pushData    array    采购系统推送数据
     *          $msg         string   错误消息
     *          $modules     string   接口模块名称
     *          $params      array    采购单号
     * @return  暂无
     * @author: luxu
     * @time: 2020年4月10号
     **/
    public function RecordErrorData($pushData,$msg,$moduels,$params){


        $errorMessage = array(

            'pushData' => json_encode($pushData, JSON_UNESCAPED_UNICODE),
            'returnData' => $msg,
            'modules' =>$this->errorTypeEnum[$moduels],
            'purchase_number'=>json_encode($params),
            'status'=>0
        );

        $result = $this->purchase_db->insert('gateway_abnormaldata',$errorMessage);
    }
    /**
     * 记录采购系统推送到门户系统数据是否异常情况
     * @param $result   array  JAVA 门户系统返回数据
     *        $params   array  采购系统推送门户PO 号
     *        $pushData array  采购系统推送到门户系统的数据
     * @return  暂无
     * @author: luxu
     * @time: 2020年4月10号
     **/
    public function RecordGateWayPush($result,$params,$pushData,$moduels){

        try{

            if( isset($result['code'])&&$result['code'] != 200){

                $errorMsg = (isset($result['msg']) && !empty($result['msg']))?$result['msg']:'';
                throw new Exception($errorMsg);
            } elseif(!isset($result['code'])) {
                throw new Exception(json_encode($result));

            }
        }catch ( Exception $exp ){

                $this->RecordErrorData($pushData,$exp->getMessage(),$moduels,$params);
        }

    }

    /**
     * 批量推送采购单信息到供应商门户
     * @param array $param 二维数组 purchase_number
     * @return mixed
     */
    function pushSmcPurchaseData($param = [])
    {
        if ($this->is_start == 'proc') {
            return true;
        }

        $res = ["code" => 0, "msg" => false, "dataList" => $param];
        $url = SMC_JAVA_API_URL . $this->purchase_method;
        $data['data'] = $this->formatData('purchase', $param);
        if(!empty($data['data'])){
            foreach($data['data']['orderc'] as $data_key=>$data_value){
                if( isset($data_value['source']) && $data_value['source'] != SOURCE_COMPACT_ORDER){
                    unset($data['data']['orderc'][$data_key]);
                }
            }
        }else{
            $res['code'] = 3;
            return $res;
        }
        try {
            $header = array('Content-Type: application/json');
            $url = $url."?access_token=".getOASystemAccessToken();
            $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
            $res['msg'] = $result;
            $resultData = json_decode($result,True);
            apiRequestLogInsert([
                'record_number' => '',
                'record_type' => 'pushSmcPurchaseData',
                'api_url' => $url,
                'post_content' => json_encode($data, JSON_UNESCAPED_UNICODE).'params:'.(is_array($param) ? json_encode($param) : $param),
                'response_content' => $result,
                'status' => 1,
            ]);
            $new_status = 1;
            if(isset($resultData['code']) && $resultData['code'] == 200){
                $new_status = 2;
                $res['code'] = 1;
            }
            $this->purchase_db->where_in("purchase_number",$param)->update('pur_purchase_order',['push_gateway_success'=>$new_status]);
        }catch ( Exception $exp ){
            // 采购推送门户系统网络原因或者其他不可以预知的原因异常记录
            $this->purchase_db->where_in("purchase_number",$param)->update('pur_purchase_order',['push_gateway_success'=>2]);
            $this->RecordErrorData($data, $exp->getMessage(),"purchaseData",$param);
        }
        return $res;
    }

    /**
     * 推送合同数据到供应商门户
     * @param array $param
     */
    function pushSmcCompactData($param = [])
    {
        if ($this->is_start == 'proc') {
            return true;
        }

        try {
            $url = SMC_JAVA_API_URL . $this->compact_method;

            $data['data'] = $this->formatData('compact', $param);

            $header = array('Content-Type: application/json');
            $access_taken = getOASystemAccessToken();

            $url = $url . "?access_token=" . $access_taken;

            $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
            $this->RecordGateWayPush(json_decode($result,True),$param,$data,"CompactData");
            return $result;
        }catch ( Exception $exp){
            // 采购推送门户系统网络原因或者其他不可以预知的原因异常记录
            $this->RecordErrorData($data,$exp->getMessage(),"CompactData",$param);
            if( isset($result)) {
                return $result;
            }
            return NULL;
        }

    }

    /**
     * 推送合同状态到供应商门户
     * @param string|array $param
     * @param int $is_system 1 供应商门户合同审核，2采购系统上传合同
     * @return array|bool|mixed|null|string
     * @example
     *  $param string 'ABD-HT1500113'
     *  $$param array
     *      array(
     *          array(
     *              'compact_number' => 'ABD-HT1500113'
     *          ),
     *          array(
     *              'compact_number' => 'ABD-HT1500226'
     *          ),
     *      )
     */
    public function pushSmcCompactStatusData($param = [], $is_system = 1)
    {
        if ($this->is_start == 'proc') {
            return true;
        }
        try {
            $url = SMC_JAVA_API_URL . $this->compact_status_method;


            $query = $this->purchase_db->from("purchase_compact_items AS items")->join("purchase_order AS orders","items.purchase_number=orders.purchase_number","LEFT");
            $query->select("items.compact_number");
            if(is_array($param)){

                $compact_numberData = array_column($param,"compact_number");
                $query->where_in("items.compact_number",$compact_numberData);
            }else{
                $query->where("items.compact_number",$param);
            }

            $result = $query->where("orders.is_gateway", SUGGEST_IS_GATEWAY_YES)->get()->result_array();// 查询对接门户的PO

            if(!empty($result)){
                if ($is_system == 1) {

                    $compactNumbers = $result;
                }else {
                    $compactNumbers = array_column($result, "compact_number");
                }
                $param = $compactNumbers;
            }else{// 非门户的创建上传文件日志
                if(is_array($param)){
                    foreach($param as $param_val){
                        $img_url = $this->getCompactFile($param_val['compact_number']);
                        if(empty($img_url)){
                            $img_url = '';
                        }
                        $this->create_compact_scan_log($param_val['compact_number'],$img_url,'采购系统上传');
                    }

                }else{
                    $img_url = $this->getCompactFile($param);
                    if(empty($img_url)){
                        $img_url = '';
                    }
                    $this->create_compact_scan_log($param,$img_url,'采购系统上传');
                }

                return True;
            }

            if ($is_system == 1) {
                $data['data'] = $this->formatData('compact_status', $param);
            } elseif ($is_system == 2) {
                $param = $param[0];
                $img_url = $this->getCompactFile($param);
                if(empty($img_url)){

                    $img_url = '';
                }
                $this->create_compact_scan_log($param,$img_url,'采购系统上传');
                $data['data'][0] = ['ImgUrl' => $img_url, 'compactNum' => $param, 'compactAuditStatus' => SRM_COMPACT_ACCESS_STATUS];
            }

            $header = array('Content-Type: application/json');
            $access_taken = getOASystemAccessToken();
            $url = $url . "?access_token=" . $access_taken;
            $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
            apiRequestLogInsert(
                [
                    'record_number' => '',
                    'record_type' => 'pushSmcCompactStatusData',
                    'api_url' => $url,
                    'post_content' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    'response_content' => $result,
                    'status' => 1,
                ]);
            $this->RecordGateWayPush(json_decode($result,True),$param,$data,"CompactStatusData");
            return $result;
        }catch ( Exception $exp ){

            // 采购推送门户系统网络原因或者其他不可以预知的原因异常记录
            $this->RecordErrorData($data,$exp->getMessage(),"CompactStatusData",$param);
            if( isset($result)) {
                return $result;
            }
            return NULL;
        }

    }

    /**
     * 创建合同扫描件上传日志
     * @author Jolon
     * @param $compact_num
     * @param $img_url
     * @param $remark
     */
    public function create_compact_scan_log($compact_num,$img_url,$remark){
        $log_data = [
            'compact_num' => $compact_num,
            'compact_audit_status' => SRM_COMPACT_ACCESS_STATUS,
            'img_url' => $img_url,
            'created_user' => getActiveUserName(),
            'remark' => $remark,
        ];
        $this->purchase_db->insert($this->supplier_supplier_info_log_table, $log_data);
    }

    /**
     * 确认预计到货时间审核
     * @param array $param (purchase_number,purchaseType,remark)
     */
    public function pushPredictTimeStatus($param = [], $automatic=false)
    {
        if ($this->is_start == 'proc') {
            return true;
        }
        $url = SMC_JAVA_API_URL . $this->predict_time_method;
//        $data['data'] = $this->getData('predict_status', $param);
        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $url = $url . "?access_token=" . $access_taken;

        $data = ['data' => []];
        foreach ($param as $k => $val) {
            $data['data'][] = ['purchaseNumber' => $val['purchase_number'], '', 'purchaseType' => $val['purchase_type'], 'rejectRemark' => $val['remark']];
        }

        $this->purchase_db->trans_begin();
        try {
            $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
            $result = json_decode($result, true);
            if(!$result || !isset($result['code'])){
                throw new Exception("推送门户系统失败！");
            }elseif (isset($result['code']) && isset($result['msg']) && $result['code'] != 200){
                throw new Exception($result['msg']);
            }
            $this->RecordGateWayPush($result,$param,$data,"PredictTimeStatus");

            foreach ($param as $k => $val) {
                $data = ['audit_status' => $val['purchase_type']];
                $pur_items = $this->purchase_db->select('purchase_number,sku,plan_arrive_time,create_user_name')
                    ->from($this->purchase_order_item_table)
                    ->where('purchase_number', $val['purchase_number'])
                    ->get()->result_array();
                if(empty($pur_items) || !is_array($pur_items))continue;
                foreach ($pur_items as $v) {
                    $p_items = $this->purchase_db->from($this->supplier_supplier_audit_table)
                        ->where('purchase_number', $val['purchase_number'])
                        ->where('sku', $v['sku'])
                        ->where_not_in('audit_status', [SRM_TIME_ACCESS_STATUS])
                        ->get()->row_array();
                    if(empty($p_items))continue;
                    $this->purchase_db->where('purchase_number', $val['purchase_number'])
                        ->where('sku', $v['sku'])
                        ->update($this->supplier_supplier_audit_table, $data);
                    if ($val['purchase_type'] == SRM_TIME_ACCESS_STATUS) {
                        $this->purchase_db->where('purchase_number', $val['purchase_number'])
                            ->where('sku', $v['sku'])
                            ->update($this->purchase_order_item_table, ['plan_arrive_time' => $p_items['estimated_arrive_time']]);
                    }
                    //写入审核日志表
                    $this->purchase_db->insert($this->supplier_supplier_audit_log_table, [
                        'purchase_number' => $val['purchase_number'],
                        'sku' => $v['sku'],
                        'old_estimated_arrive_time' => $v['plan_arrive_time'],
                        'new_estimated_arrive_time' => $p_items['estimated_arrive_time'],
                        'audit_status' => $val['purchase_type'],
                        'remark' => $val['remark'],
                        'created_user' => getActiveUserName(),//审核操作人
                    ]);
                }
            }

            if ($this->purchase_db->trans_status() === false) {
                $this->purchase_db->trans_rollback();
            } else {
                $this->purchase_db->trans_commit();
            return $result;
            }
        } catch (Exception $exp) {
            // 26990 如果是自动推送，则失败后需要重新推送
            if($automatic){
                $this->handle_error_sku($param);
            }

            // 采购推送门户系统网络原因或者其他不可以预知的原因异常记录
            $this->RecordErrorData($data,$exp->getMessage(),"PredictTimeStatus",$param);
            $this->purchase_db->trans_rollback();
            return $exp->getMessage();
        }
        return $result;
    }

    /**
     * 推送采购单状态部分到货不等待剩余
     * @param array $param (purchase_number,status:PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE)
     */
    public function pushPurchaseStatus($param = [])
    {
        if ($this->is_start == 'proc') {
            return true;
        }
        try {
            $url = SMC_JAVA_API_URL . $this->purchase_status_method;
            $items = $this->formatData('purchase_status', $param);
            $data['purchaseNumber'] = array_column($items, 'purchase_number');
            $header = array('Content-Type: application/json');
            $access_taken = getOASystemAccessToken();
            $url = $url . "?access_token=" . $access_taken;
            $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
            $this->RecordGateWayPush(json_decode($result,True),$param,$data,"PurchaseStatus");
            return $result;
        }catch ( Exception $exp ){

//            // 采购推送门户系统网络原因或者其他不可以预知的原因异常记录
            $this->RecordErrorData($data,$exp->getMessage(),"PurchaseStatus",$param);
            if( isset($result)) {
                return $result;
            }
            return NULL;

        }
    }

    /**
     * 推供应商门户入库情况
     * @param array $param express_no(快递单号)
     */
    public function pushWarehourseStatus($param = [])
    {
        $url = SMC_JAVA_API_URL . $this->warehourse_result_method;
        $data['data'] = $this->formatData('warehourse_result', $param);
//        echo json_encode($data, JSON_UNESCAPED_UNICODE);exit;
        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $url = $url . "?access_token=" . $access_taken;
        $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
        return $result;
    }
    /**
     * 推供作废采购单
     * @param array $param purchase_num(采购单号)
     */
    public function pushPurchaseCancel($param = [])
    {
        try {
            $url = SMC_JAVA_API_URL . $this->purchase_cancel_method;
            $data['purchaseNumber'] = $param;
            $header = array('Content-Type: application/json');
            $access_taken = getOASystemAccessToken();
            $url = $url . "?access_token=" . $access_taken;
            $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
            $this->RecordGateWayPush(json_decode($result,True),$param,$data,"PurchaseCancel");
            return $result;
        }catch ( Exception $exp ){
            // 采购推送门户系统网络原因或者其他不可以预知的原因异常记录
            $this->RecordErrorData($data,$exp->getMessage(),"PurchaseCancel",$param);
            if( isset($result)) {
                return $result;
            }
            return NULL;

        }
    }
    /**
     * 查询门户系统是否有效供应商
     * @param array $purchase_number
     * @return array|bool 返回为true为门户正常状态供应商
     */
    public function isValidSupplier($purchase_number = [])
    {
        if ($this->is_start == 'proc') {
            return true;
        }
        $url = SMC_JAVA_API_URL . $this->supplier_status_method;
        $suppliers = $this->getPurchaseSupplier($purchase_number);
        $supplier_codes = array_column($suppliers, 'supplier_code');
        $supplier_codes_num = array_column($suppliers, 'supplier_code','purchase_number');
        $data['supplierCodes'] = $supplier_codes;
        $header = array('Content-Type: application/json');
        $access_taken = getOASystemAccessToken();
        $url = $url . "?access_token=" . $access_taken;
//        var_dump($data);exit;
        $return = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
        $result = json_decode($return, true);
        if (isset($result) && !empty($result['data'])) {
            $err_codes = [];
            //如果是禁用供应商或者门户没有供应商按采购系统目前流程处理
            foreach ($result['data'] as $v) {
//            if (!empty($v['enableStatus']) && $v['enableStatus'] == 2) {
//                $err_codes[] = $v['supplierCode'];
//            } else if () {
//                //
//                $this->getSupplierAuditByStatus();
//            }
                if (!empty($v['enableStatus']) && $v['enableStatus'] == 1) {
                    //门户开启的供应商，需判断订单状态
//                    $err_codes[] = $v['supplierCode'];
                    $err_codes[] = array_search($v['supplierCode'],$supplier_codes_num);
                }
            }
            if (!empty($err_codes)) {
                return $err_codes;
//            $this->getSupplierAuditByStatus();
            }
        }
        return true;
    }

    public function is_valid_status($purchase_numbers = [])
    {
        if ($this->is_start == 'proc') {
            return true;
        }
        $this->purchase_db->select('audit_status,purchase_number');
        $this->purchase_db->from($this->supplier_supplier_audit_table);
        $this->purchase_db->where('audit_status', SRM_TIME_ACCESS_STATUS);
        $this->purchase_db->where_in('purchase_number', $purchase_numbers);
        $items = $this->purchase_db->get()->result_array();
//        echo $this->purchase_db->last_query();exit;
//        var_dump($items);exit;
        $diff_num = $purchase_numbers;
        if (!empty($items)) {
            $purchase_num_item = array_column($items, 'purchase_number');
            $diff_num = array_diff($purchase_num_item,$purchase_numbers);
        }
//        var_dump($diff_num);exit;
        if(!empty($diff_num)){
//            var_dump($diff_num);exit;
            $supplier_codes = $this->isValidSupplier($diff_num);
            if (!empty($supplier_codes)) {
//                $supplier_codes_str = implode(',', $supplier_codes);
                return $supplier_codes;
            }
        }
        return true;
    }

    /**
     * 格式化数据接口
     * @param string $type
     * @param array $data
     * @return array
     */
    function formatData($type = '', $data = [])
    {
        $item = [];
        switch ($type) {
            case 'purchase':
                $item = $this->formatPurchaseData($data);
                break;
            case 'compact':
                $item = $this->formatCompactData($data);
                break;
            case 'compact_status':
                $item = $this->formatCompactStatus($data);
                break;
            case 'predict_status':
                $item = $this->formatPredictStatus($data);
                break;
            case 'purchase_status':
                $item = $this->formatPurchaseStatus($data);
                break;
            case 'warehourse_result':
                $item = $this->formatWarehourseResult($data);
                break;
            default:
                break;
        }
        return $item;
    }

    /**
     * 采购单数据格式化
     * @param array $data
     * @return array
     */
    public function formatPurchaseData($data = [])
    {
        //po单联系人，电话，地址
        $this->load->model('warehouse/Warehouse_model');
        $this->load->model('purchase/Purchase_order_model');
        $order = [];
        $order_title_name = ['source','purchase_number', 'compact_number', 'warehouse_code', 'buyer_name', 'account_type', 'currency_code', 'is_freight', 'warehouse_name', 'is_freight'];
        $item_title_name = ['sku', 'product_name', 'purchase_unit_price', 'confirm_amount', 'product_img_url', 'shipment_type', 'is_replace_purchase', 'first_plan_arrive_time', 'create_time', 'create_user_name', 'modify_user_name', 'freight'];
        //批量编辑采购单过来的
        $i = 0;
        foreach ($data as $num => $purchase_number) {
            $j = 0;
            //处理单条PO
            $pur_list = $this->getData('purchase', $purchase_number);
            foreach ($pur_list as $list) {
                foreach ($order_title_name as $tvalue) {
                    $order['orderc'][$i][$tvalue] = $list[$tvalue] ?? '';
                }
                $order['orderc'][$i]['supplier_code'] = $list['ps_code'];
                $order['orderc'][$i]['supplier_name'] = $list['ps_name'];
               // $order['orderc'][$i]['create_time'] = $list['a_time'];
                $order['orderc'][$i]['create_time'] = date("Y-m-d H:i:s",time());
                $order['orderc'][$i]['is_drawback'] = $list['a_dark'] ?? '';
                $order['orderc'][$i]['purchase_type_id'] = $list['pyd'] ?? '';

                $userInfo  = $this->purchase_order_model->get_access_purchaser_information($purchase_number);
                $warehouse_address = $this->warehouse_model->get_warehouse_address($list['warehouse_code']);
                $warehouse_address_complete = $warehouse_address[0]['province_text'].$warehouse_address[0]['city_text'].$warehouse_address[0]['area_text'].$warehouse_address[0]['town_text'].$warehouse_address[0]['address'];
                $order['orderc'][$i]['address'] =$warehouse_address_complete ?? '';
                $contact_number = $userInfo['iphone'];
                $contacts = $userInfo['user_name'];
                // $val['warehouse_code'] =='shzz' or $val['warehouse_code']=='AFN'
                if(!empty($warehouse_address[0]['contact_number']) ){
                    $contact_number = $warehouse_address[0]['contact_number'];
                }
                if(!empty($warehouse_address[0]['contacts']) ){
                    $contacts = $warehouse_address[0]['contacts'];
                }
                $order['orderc'][$i]['contact_number'] = $contact_number ?? '';
                $order['orderc'][$i]['contacts'] = $contacts ?? '';
                

//                foreach ($order_value['items_list'] as $item_value) {
                foreach ($item_title_name as $ivalue) {
                    $order['orderc'][$i]['items'][$j][$ivalue] = $list[$ivalue] ?? '';
                }
                $order['orderc'][$i]['items'][$j]['create_user'] = $list['create_user_name'] ?? '';
                $order['orderc'][$i]['items'][$j]['create_time'] = date("Y-m-d H:i:s",time());
                $order['orderc'][$i]['items'][$j]['update_time'] = $list['modify_time'] ?? '';
                $order['orderc'][$i]['items'][$j]['update_user'] = $list['modify_user_name'] ?? '';
                $order['orderc'][$i]['items'][$j]['modify_time'] = $list['mod_time'] ?? '';
                $order['orderc'][$i]['items'][$j]['estimated_overdue_time'] = $list['plan_arrive_time'] ?? '';
                $order['orderc'][$i]['items'][$j]['is_replace_purchase'] = $list['is_purchasing'] ?? '';
                $j++;
//                }
            }
            $i++;
        }
//        $format = [
//            'order'=>[
//               'purchase_number'=>,
//            ],
//        ];
        return $order;
    }

    /**
     * 合同数据格式化
     * @param array $data
     * @return array
     */
    public function formatCompactData($data = [])
    {
        $item = [];
        $compact_title_name = ['purchase_number', 'compact_number', 'compact_create_time', 'warehouse_code', 'warehouse_name', 'supplier_code', 'buyer', 'buyer_id', 'settlement_method', 'is_drawback', 'is_freight', 'order_amount', 'purchase_create_time', 'currency_code'];
//批量编辑采购单过来的
        $i = 0;
        foreach ($data as $num => $purchase_number) {
            $pur_list = $this->getData('compact', $purchase_number);
            foreach ($pur_list as $list) {
                foreach ($compact_title_name as $tvalue) {
                    $item['compactc'][$i][$tvalue] = $list[$tvalue] ?? '';
                }
            }
            $this->purchase_db->select('confirm_amount');
            $this->purchase_db->from($this->purchase_order_item_table);
            $this->purchase_db->where('purchase_number', $purchase_number);
//            $this->purchase_db->group_by('purchase_number');
            $items_data = $this->purchase_db->get()->result_array();
            $item['compactc'][$i]['sku_qty'] = count($items_data);
            foreach ($items_data as $amount) {
                if (isset($item['compactc'][$i]['confirm_amount'])) {
                    $item['compactc'][$i]['confirm_amount'] += $amount['confirm_amount'];
                } else {
                    $item['compactc'][$i]['confirm_amount'] = $amount['confirm_amount'];
                }
            }
            $i++;
        }
        return $item;
    }

    /**
     * 合同状态格式化
     * @param array $compact_num
     * @return array|void
     */
    public function formatCompactStatus($compact_num = [])
    {
//        $status_item = ['compactNum', 'compactAuditStatus', 'ImgUrl'];
        $item = $this->getData('compact_status', $compact_num);
        return $item;
    }

    /**
     * @param $param (purchase_number,sku)
     * @return array|void
     */
    public function formatPredictStatus($param)
    {
//        $status_item = ['purchaseNumber', 'purchaseType'];
        $item = $this->getData('predict_status', $param);
        return $item;
    }

    /**
     * @param $param ($purchase_number,$status)
     * @return array|void
     */
    public function formatPurchaseStatus($param)
    {
        $item = $this->getData('purchase_status', $param);
        return $item;
    }

    /**
     *
     * @param $param
     * @return array|void
     */
    public function formatWarehourseResult($param)
    {
        $items = $this->getData('warehourse_result', $param);
        foreach ($items as $k => $v) {
            $items[$k]['remark'] = '';
        }
        return $items;
    }


    /**
     * 数据获取
     * @param $type
     * @param string $param
     * @return array|void
     */
    function getData($type, $param = '')
    {
        $data = [];
        switch ($type) {
            case 'purchase':
                $data = $this->getPurchaseData($param);
                break;
            case 'compact':
                $data = $this->getCompactData($param);
                break;
            case 'compact_status':
                $data = $this->getCompactStatus($param);
                break;
            case 'predict_status':
                $data = $this->getPredictStatus($param);
                break;
            case 'purchase_status':
                $data = $this->getPurchaseStatus($param);
                break;
            case 'warehourse_result':
                $data = $this->getWarehourseResult($param);
                break;
            default:
                break;
        }
        return $data;
    }

    /**
     * 推送采购单数据
     * @param $purchase_num
     * @return array
     */
    public function getPurchaseData($purchase_num)
    {
        $this->purchase_db->select('*,b.plan_arrive_time,b.sku,a.supplier_code as ps_code,a.supplier_name as ps_name,a.audit_time as a_time,a.create_time as c_time,b.modify_time as mod_time,a.purchase_type_id as pyd,a.is_drawback as a_dark');
        $this->purchase_db->from($this->purchase_order_table . ' as a');
        $this->purchase_db->join($this->purchase_order_item_table . ' as b', 'a.purchase_number=b.purchase_number', 'left');
        $this->purchase_db->join($this->warehourse_table . ' as c', 'a.warehouse_code=c.warehouse_code', 'left');
        $this->purchase_db->join($this->product_table . ' as d', 'b.sku=d.sku', 'left');
        $this->purchase_db->join($this->purchase_order_pay_type_table . ' as e', 'a.purchase_number=e.purchase_number', 'left');
        $this->purchase_db->where('a.purchase_number', $purchase_num);
        $data = $this->purchase_db->get()->result_array();
//        echo $this->purchase_db->last_query();exit;
//        $result = [];
//        foreach($data as $v){
//            $result[$v['purchase_num']]['items_list'][]=[
//
//            ];
//        }
        return $data;
    }

    /**
     * 推送合同单数据
     * @param $purchase_num
     */
    public function getCompactData($purchase_num)
    {
        $this->purchase_db->select('a.currency_code,c.create_time as compact_create_time,a.create_time as purchase_create_time,c.a_linkman_id as buyer_id,c.a_linkman as buyer,c.real_money as order_amount,c.*,a.purchase_number');
        $this->purchase_db->from($this->purchase_order_table . ' as a');
        $this->purchase_db->join($this->purchase_compact_item_table . ' as b', 'a.purchase_number=b.purchase_number', 'left');
        $this->purchase_db->join($this->purchase_compact_table . ' as c', 'b.compact_number=c.compact_number', 'left');
        $this->purchase_db->where('a.purchase_number', $purchase_num);
        $data = $this->purchase_db->get()->result_array();
//        echo $this->purchase_db->last_query();exit;
        return $data;
    }

    /**
     * 推送合同状态
     * @param $compact_num string
     * @return array
     */
    public function getCompactStatus($compact_items)
    {
        $data = [];
        $compact_nums = array_column($compact_items, 'compact_number');
        $this->purchase_db->select('a.compact_number as compactNum,b.compact_audit_status as compactAuditStatus,b.img_url as ImgUrl');
        $this->purchase_db->from($this->purchase_compact_table . ' as a');
        $this->purchase_db->join($this->supplier_supplier_info_table . ' as b', 'a.compact_number=b.compact_num', 'left');
        $this->purchase_db->where_in('a.compact_number', $compact_nums);
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    /**
     *
     * @param $param
     * @return bool
     */
    public function updateCompactStatus($param = [])
    {
        if ($this->is_start == 'proc') {
            return true;
        }
        foreach ($param as $val) {
            $log_data = [
                'compact_num' => $val['compact_number'],
                'compact_audit_status' => $val['audit_status'],
                'remark' => $val['remark'],
            ];
            $this->purchase_db->trans_start();
            //采购员审核通过
            if ($val['audit_status'] == SRM_COMPACT_ACCESS_STATUS) {

                $this->purchase_db->select('*');
                $this->purchase_db->from($this->supplier_supplier_info_table);
                $this->purchase_db->where('compact_num', $val['compact_number']);
                $this->purchase_db->where_not_in('compact_audit_status', [SRM_COMPACT_ACCESS_STATUS]);
//            $this->purchase_db->where('compact_audit_status', SRM_COMPACT_ACCESS_STATUS);
                $info_data = $this->purchase_db->get()->row_array();
//            var_dump($info_data);
//            exit;
                if (!empty($info_data)) {
                    //更新审核状态，合同地址
                    $this->purchase_db->where('compact_num', $val['compact_number']);
                    $this->purchase_db->update($this->supplier_supplier_info_table, ['compact_audit_status' => SRM_COMPACT_ACCESS_STATUS]);
                    //
                    //合同主表更新上传文件状态
                    $this->purchase_db->where('compact_number', $val['compact_number']);
                    $this->purchase_db->update($this->purchase_compact_table, ['is_file_uploaded' => SRM_COMPACT_ACCESS_STATUS,'file_upload_time' => date('Y-m-d H:i:s')]);
                    $this->purchase_db->where('compact_number', $val['compact_number']);
                    $compact_data = $this->purchase_db->get($this->purchase_compact_table)->row_array();
//            $this->purchase_db->where('pc_id', $compact_data['id']);
                    $compact_file = [
                        'pc_id' => $compact_data['id'],
                        'pop_id' => -1,
                        'file_name' => $info_data['file_name'],
                        'file_path' => $info_data['img_url'],
                        'file_type' => $info_data['file_type'],
                        'upload_user_name' => getActiveUserName(),
                        'upload_user_id' => getActiveUserId(),
                    ];
                    //写入合同文件表
                    $this->purchase_db->insert($this->supplier_conpact_file_table, $compact_file);
                    $log_data['img_url'] = $info_data['img_url'];
                    $log_data['created_user'] = getActiveUserName();
                    $this->purchase_db->insert($this->supplier_supplier_info_log_table, $log_data);

                }

            }
            //采购员驳回
            if ($val['audit_status'] == SRM_COMPACT_REFUSE_STATUS) {
                $this->purchase_db->select('*');
                $this->purchase_db->from($this->supplier_supplier_info_table);
                $this->purchase_db->where('compact_num', $val['compact_number']);
                $this->purchase_db->where_not_in('compact_audit_status', [SRM_COMPACT_ACCESS_STATUS, SRM_COMPACT_REFUSE_STATUS]);
                $info_data = $this->purchase_db->get()->row_array();
                if (!empty($info_data)) {
                    //更新审核状态，合同地址
                    $this->purchase_db->where('compact_num', $val['compact_number']);
                    $this->purchase_db->update($this->supplier_supplier_info_table, ['compact_audit_status' => SRM_COMPACT_REFUSE_STATUS]);

                    //合同主表更新上传文件状态
                    $this->purchase_db->where('compact_number', $val['compact_number']);
                    $this->purchase_db->update($this->purchase_compact_table, ['is_file_uploaded' => SRM_COMPACT_REFUSE_STATUS]);

                    $log_data['img_url'] = $info_data['img_url'];
                    $log_data['created_user'] = getActiveUserName();
                    //写入合同审核日志表
                    $this->purchase_db->insert($this->supplier_supplier_info_log_table, $log_data);
                }
            }
            $this->purchase_db->trans_complete();

        }
        if ($this->db->trans_status() === false) {
            return false;
        }
        //推送门户系统合同审核信息
        $this->pushSmcCompactStatusData($param);
        return true;
    }

    /**
     * 预期到货时间审核状态
     * @param $param
     * @return string
     */
    public function getPredictStatus($param)
    {
//        $this->purchase_db->select('purchase_number as purchaseNumber,sku,audit_status as purchaseType');
//        $this->purchase_db->from($this->supplier_contact_audit_table);
//        $this->purchase_db->where('purchase_number', $param['purchase_num']);
//        $this->purchase_db->where('sku', $param['sku']);
//        $data = $this->purchase_db->get()->row_array();
        $return = [];
        foreach ($param as $k => $val) {
            $data = ['audit_status' => $val['purchase_type']];
            $this->purchase_db->select('purchase_number,sku,plan_arrive_time,create_user_name');
            $this->purchase_db->from($this->purchase_order_item_table);
            $this->purchase_db->where('purchase_number', $val['purchase_number']);
            $pur_items = $this->purchase_db->get()->result_array();
            if (!empty($pur_items) && is_array($pur_items)) {
                foreach ($pur_items as $v) {
                    $this->purchase_db->select('*');
                    $this->purchase_db->from($this->supplier_supplier_audit_table);
                    $this->purchase_db->where('purchase_number', $val['purchase_number']);
                    $this->purchase_db->where('sku', $v['sku']);
                    $this->purchase_db->where_not_in('audit_status', [SRM_TIME_ACCESS_STATUS]);
                    $p_items = $this->purchase_db->get()->row_array();
                    if (!empty($p_items)) {
                        $this->purchase_db->where('purchase_number', $val['purchase_number']);
                        $this->purchase_db->where('sku', $v['sku']);
                        $this->purchase_db->update($this->supplier_supplier_audit_table, $data);
                        if ($val['purchase_type'] == SRM_TIME_ACCESS_STATUS) {
                            $this->purchase_db->where('purchase_number', $val['purchase_number']);
                            $this->purchase_db->where('sku', $v['sku']);
                            $this->purchase_db->update($this->purchase_order_item_table, ['plan_arrive_time' => $p_items['estimated_arrive_time']]);
                        }
                        //写入审核日志表
                        $log_data = [
                            'purchase_number' => $val['purchase_number'],
                            'sku' => $v['sku'],
                            'old_estimated_arrive_time' => $v['plan_arrive_time'],
                            'new_estimated_arrive_time' => $p_items['estimated_arrive_time'],
                            'audit_status' => $val['purchase_type'],
                            'remark' => $val['remark'],
                            'created_user' => getActiveUserName(),//审核操作人
                        ];
                        $this->purchase_db->insert($this->supplier_supplier_audit_log_table, $log_data);
                    }
                }
            }
            $return[] = ['purchaseNumber' => $val['purchase_number'], '', 'purchaseType' => $val['purchase_type'],'rejectRemark'=>$val['remark']];
        }
        return $return;
    }

    /**
     * 指定采购单状态条件返回采购单号
     * @param $param
     * @return array
     */
    public function getPurchaseStatus($param)
    {
        $data = [];
        $this->purchase_db->select('purchase_number,purchase_order_status');
        $this->purchase_db->from($this->purchase_order_table);
        $this->purchase_db->where_in('purchase_number', $param['purchase_number']);
        $this->purchase_db->where('purchase_order_status', $param['purchase_status']);
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    /**
     * 入库状态推送
     * @param $param
     * @return array
     */
    public function getWarehourseResult($param)
    {
        $data = [];
        $this->purchase_db->select(',sku,express_no as logisticsNumber,quality_result as status,receipt_number as putStorage,instock_qty as quantity');
        $this->purchase_db->from($this->warehourse_result_table);
//        $this->purchase_db->where('purchase_number', $param['purchase_num']);
//        $this->purchase_db->where('sku', $param['sku']);
        $this->purchase_db->where_in('express_no', $param['express_no']);
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    /**
     * 获取供应商状态
     * @param $param
     * @return array
     */
    public function getSupplierAuditByStatus($param)
    {
        $data = [];
        $this->purchase_db->select('*');
        $this->purchase_db->from($this->supplier_contact_audit_table);
        $this->purchase_db->where('purchase_number', $param['purchase_num']);
        $this->purchase_db->where('audit_status', $param['sku']);
        if (!empty($param['audit_status'])) {
            $this->purchase_db->where('audit_status', $param['audit_status']);
        }
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    /**
     * 获取采购供应商
     * @param array $purchase_num
     * @return array
     */
    public function getPurchaseSupplier($purchase_num = [])
    {
        $this->purchase_db->select('supplier_code,purchase_number');
        $this->purchase_db->from($this->purchase_order_table);
        $this->purchase_db->where_in('purchase_number', $purchase_num);
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    /**
     * 获取采购预计到货时间审核日志
     * @param $purchase_num
     * @param $sku
     * @return array
     */
    public function getWebInfoLog($purchase_num, $sku)
    {
        $this->purchase_db->select('*');
        $this->purchase_db->from($this->supplier_supplier_audit_log_table);
        $this->purchase_db->where('purchase_number', $purchase_num);
        $this->purchase_db->where('sku', $sku);
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    /**
     * 获取采购预合同审核日志
     * @param $purchase_num
     * @param $sku
     * @return array
     */
    public function getInfoLog($compact_num)
    {
        $this->purchase_db->select('*');
        $this->purchase_db->from($this->supplier_supplier_info_log_table);
        $this->purchase_db->where('compact_num', $compact_num);
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    /**
     * 查询有审核记录采购单
     * @param $purchase_numbers
     * @return array
     */
    public function get_time_audit_info($purchase_numbers)
    {
        $fields = 'a.purchase_number,a.sku,a.product_img_url,b.demand_number,c.estimated_arrive_time,c.audit_status,c.remark,c.updated_user,DATE_FORMAT(a.plan_arrive_time,"%Y-%m-%d") as plan_arrive_time';
        $this->purchase_db->select($fields);
        $this->purchase_db->from($this->purchase_order_item_table . ' as a');
        $this->purchase_db->join($this->purchase_map_table . ' as b', 'a.purchase_number=b.purchase_number and a.sku = b.sku', 'left');
        $this->purchase_db->join($this->supplier_supplier_audit_table . ' as c', 'a.purchase_number=c.purchase_number and a.sku = c.sku', 'left');
        $this->purchase_db->where_in('a.purchase_number', $purchase_numbers);
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    /**
     * 查询有审核记录合同
     * @param $purchase_numbers
     * @return array
     */
    public function get_compact_status_info($compact_numbers)
    {
        $this->purchase_db->select('*');
        $this->purchase_db->from($this->supplier_supplier_info_table);
        $this->purchase_db->where_in('compact_num', $compact_numbers);
        $data = $this->purchase_db->get()->result_array();
        return $data;
    }

    public function getCompactFile($compact_number)
    {
        $this->purchase_db->select('id');
        $this->purchase_db->from($this->purchase_compact_table);
        $this->purchase_db->where('compact_number', $compact_number);
        $compact = $this->purchase_db->get()->row_array();
        $this->purchase_db->select('file_path');
        $this->purchase_db->from($this->supplier_conpact_file_table);
        $this->purchase_db->where('pc_id', $compact['id']);
        $data = $this->purchase_db->get()->row_array();
        return $data['file_path'];
    }

//    public function getSupplierData($supplier_code)
//    {
//        $this->purchase_db->select('*');
//        $this->purchase_db->from($this->purchase_order_table . ' as a');
//        $this->purchase_db->join($this->purchase_compact_item_table . ' as b', 'a.purchase_number=b.purchase_number', 'left');
//        $this->purchase_db->join($this->purchase_compact_table . ' as c', 'b.compact_number=c.compact_number', 'left');
//        $this->purchase_db->where('a.purchase_number', $purchase_num);
//        $data = $this->purchase_db->get()->result_array();
////        echo $this->purchase_db->last_query();exit;
//        return $data;
//    }

    /**
     * 定时任务自动审核 25947
     * @author yefanli
     * 定时脚本:/usr/bin/php /mnt/purchase/appdal/index.php /data_api/getDownData
     * 运行时间： 每隔2分钟跑一次
     */
    public function examine_estimated_arrive_time()
    {
        $thisTime = date('Y-m-d H:i:s',strtotime(' -1 days'));
        $fields = 'a.purchase_number,a.sku,a.product_img_url,c.estimated_arrive_time,c.audit_status,c.updated_user,a.plan_arrive_time';
        $data = $this->purchase_db->select($fields)
            ->from($this->purchase_order_item_table.' as a')
            ->join($this->supplier_supplier_audit_table.' as c', 'a.purchase_number=c.purchase_number and a.sku = c.sku', 'left')
            ->where('c.audit_status=', 1)
            ->where("c.create_time >", $thisTime)
            ->get()->result_array();
        if(!$data || count($data) == 0)exit;

        $query = [];
        $is_has = [];
        $del_list = [];
        foreach ($data as $val){
            if($val['estimated_arrive_time'] > $val['plan_arrive_time'])$del_list[] = $val['purchase_number'];
            if($val['estimated_arrive_time'] > $val['plan_arrive_time'] || in_array($val['purchase_number'], $is_has))continue;

            $row = [];
            $row['purchase_number'] = $val['purchase_number'];
            $row['remark'] = '系统自动审核通过！';
            $row['purchase_type'] = 2;
            $query[] = $row;
            $is_has[] = $val['purchase_number'];
        }

        foreach ($query as $k=>$v){
            if(in_array($v['purchase_number'], $del_list))unset($query[$k]);
        }

        // 26900 获取失败数据
        $this->load->library('rediss');
        $cacheData = $this->rediss->getData('HANDLE_ERROR_SKU');
        if($cacheData && is_array($cacheData) && !empty($cacheData)){
            $query = array_merge($query, $cacheData);
            $this->rediss->deleteData('HANDLE_ERROR_SKU');
        }

        if(count($query) == 0)exit;
        try {
            $return = $this->pushPredictTimeStatus($query, true);
//            $return = json_decode($return, true);
            $log = [
                'id'      => date("YmdHis"),
                'type'    => 'examine_estimated_arrive_time',
                'content' => '系统自动审核预计到货时间。',
                'detail'  => json_encode($is_has)
            ];
            if ($return['code'] == 200) {
                $log['content'] = $log['content'].'审核成功';
            }else if($return['code'] == 500) {
                $log['content'] = $log['content'].$return['msg'];
            }else{
                $log['content'] = $log['content'].'审核失败';
            }
            operatorLogInsert($log);
        }catch (Exception $e){}
    }

    /**
     * 26900 处理门户系统发版失败的sku
     */
    private function handle_error_sku($data)
    {
        try {
            $this->load->library('rediss');
            $this->rediss->setData('HANDLE_ERROR_SKU',$data, 1200);
        }catch (Exception $e){}
    }

}