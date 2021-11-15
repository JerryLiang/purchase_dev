<?php

/**
 * @function:采购单订单追踪MODEL
 * @author:luxu
 **/
class Purchase_order_progress_model extends Purchase_model
{

    /**
     * 备货单表
     **/
    protected $table_suggest_name = 'purchase_suggest';

    /**
     * 物流信息表
     */
    protected $table_logistics_info_name = 'purchase_logistics_info';

    /**
     * 快递公司信息表
     */
    protected $table_carrier_info_name = 'logistics_carrier';

    /**
     * 异常采购单退货记录表
     */
    protected $exception_return_info_table = 'excep_return_info';
    /**
     * 采购订单明细表
     */
    protected $table_order_items_name = 'purchase_order_items';
    /**
     * 采购订单-取消未到货主表
     */
    protected $table_order_cancel_name = 'purchase_order_cancel';
    /**
     * 采购订单-取消未到货明细表
     */
    protected $table_order_cancel_detail_name = 'purchase_order_cancel_detail';
    /**
     * 采购订单-报损表
     */
    protected $table_order_reportloss_name = 'purchase_order_reportloss';
    /**
     * 订单跟踪表
     */
    protected $table_purchase_progress_name ='purchase_progress';


    /**
     * 获取采购单对应的备货单信息（私有）
     * @param $demand_number     string|array    备货单
     *        $sku               string|array    SKU
     * @return   array  备货单信息
     * @author:luxu
     **/


    private function progress_suggest($demand_number, $skus, $search = "*")
    {

        try {

            $query_builder = $this->purchase_db->from($this->table_suggest_name)->select($search);
            if (is_string($demand_number)) {

                $query_builder->where('demand_number', $demand_number);
            } else if (is_array($demand_number)) {

                $query_builder->where_in("demand_number", $demand_number);
            }

            if (is_string($skus)) {

                $query_builder->where('sku', $skus);
            } else {
                $query_builder->where_in('sku', $skus);
            }

            $result = $query_builder->get()->result_array();

            return $result;
        } catch (Exception $exp) {
            return NULL;
        }
    }

    /**
     * 获取仓库信息 私有
     * @param  $warehours_code   string   仓库标识
     * @author:luxu
     * @return array   仓库信息
     **/
    private function warhouse($warehours_code, $search = "*")
    {
        $this->purchase_db->reset_query();
        $query_builder = $this->purchase_db->from("warehouse");
        if (is_array($warehours_code)) {

            $query_builder->where_in("warehouse_code", $warehours_code);
        }

        if (is_string($warehours_code)) {
            $query_builder->where("warehouse_code='" . $warehours_code . "'");
        }

        $result = $query_builder->select($search)->get()->result_array();

        return $result;
    }

    /**
     * function:查询备货单是否在订单追踪表(私有)
     * @param $demand_number   string   备货单号
     * @author : luxu
     * @return array  返回备货单ID
     **/
    public function progress_message($demand_number, $search = "*")
    {

        $query_builder = $this->purchase_db->from("purchase_progress");
        if (is_array($demand_number)) {

            $query_builder->where_in(" demand_number", $demand_number);
        } else {

            $query_builder->where("demand_number='" . $demand_number . "'");
        }

        return $query_builder->select($search)->get()->row_array();
    }

    /**
     * function:查询备货单是否在订单追踪表（公开）
     * @param $demand_number   string   备货单号
     * @author : luxu
     * @return array  返回备货单ID
     **/
    public function get_progress($demand_number, $search = "*")
    {

        return $this->progress_message($demand_number, $search);
    }


    /**
     * 获取仓库信息 公开
     * @param  $warehours_code   string   仓库标识
     * @author:luxu
     * @return array   仓库信息
     **/

    public function get_warhouse($warehours_code, $search = "*", $return_key = "sku")
    {

        $warehours = $this->warhouse($warehours_code, $search);
        if (!empty($warehours)) {
            return array_column($warehours, NULL, $return_key);
        }

        return NULL;

    }

    /**
     * 获取采购单对应的备货单信息(公开)
     * @param $demand_number     string|array    备货单
     *        $sku               string|array    SKU
     * @return   array  备货单信息
     * @author:luxu
     **/
    public function get_progress_suggest($demand_number, $skus, $search = "*")
    {

        // 获取对应的信息
        if (is_array($search)) {

            $search = implode(",", $search);
        }
        $result = $this->progress_suggest($demand_number, $skus, $search);
        if (NULL != $result || !empty($result)) {

            $result_arr = [];
            foreach ($result as $key => $value) {

                $keys = $value['demand_number'] . "-" . $value['sku'];
                if (!isset($result_arr[$keys])) {

                    $result_arr[$keys] = [];
                }

                $result_arr[$keys] = $value;
            }

            return $result_arr;
        }

        return [];
    }

    public function get_purchase_sku_instock_qty($purchase_number, $sku)
    {

        return $this->purchase_db->from("warehouse_results")->select(" sum(instock_qty) AS instock_qty")->where("purchase_number", $purchase_number)->where("sku", $sku)->get()->row_array();
    }

    /**
     * 订单跟踪导入功能
     * @author Manson
     * @param $update_data
     * @param $insert_data
     * @param $delete_data
     * @return bool
     */
    public function import_data($update_data, $insert_data, $delete_data='')
    {
        $this->load->model('Reject_note_model');
        $this->load->model('Purchase_order_progress_model','m_progress',false,'purchase');
        $this->purchase_db->trans_start();
        $this->purchase_db->update_batch('purchase_progress', $update_data, 'demand_number');//更新订单跟踪表的数据

        if (!empty($insert_data)){
            foreach ($insert_data as $key => $item) {
                $result = $this->purchase_db->select('*')
                    ->where('purchase_number',$item['purchase_number'])
                    ->where('sku',$item['sku'])
                    ->where('cargo_company_id',$item['cargo_company_id'])
                    ->where('express_no',$item['express_no'])
                    ->get('purchase_logistics_info')
                    ->row_array();
                if (!empty($result)){//已经存在的跳过
                    continue;
                }
                $this->purchase_db->insert('purchase_logistics_info',$item);

                $log = [
                    'record_number' => $item['purchase_number'],
                    'record_type' => '采购单',
                    'content' => '订单跟踪导入物流单号,物流公司',
                    'content_detail' => sprintf('采购单:%s,SKU:%s,录入物流单号:%s,物流公司:%s',$item['purchase_number'],$item['sku'],$item['express_no'],$item['cargo_company_id'])
                ];
                $this->Reject_note_model->get_insert_log($log);
                $this->update_parcel_urgent($item['purchase_number']);
                //推送物流单号,快递公司到WMS
                //采购单维度推送
                $pushData[$item["purchase_number"]] = 1;
            }
        }
        //记录操作日志
        $this->purchase_db->trans_complete();
        if ($this->purchase_db->trans_status() === false) {
            return false;
        }
        if (isset($pushData)&&!empty($pushData)){
            $pushNewsWms = $pushData = array_keys($pushData);
            //查询要推送的数据
            $pushData = $this->m_progress->get_push_list($pushData);

            if(!$this->m_progress->push_express_info_to_wms($pushData)){
                return false;
            }

            if(!$this->m_progress->push_receive_bind_express($pushNewsWms)){
                return false;
            }
        }
        return true;
    }

    /**
     *         //查询是否存在加急包裹
     * @author Manson
     * @param $purchase_number
     */
    public function update_parcel_urgent($purchase_number)
    {
        $this->load->model('warehouse/parcel_urgent_model');
        //查询是否存在加急包裹
        $have_parcel = $this->parcel_urgent_model->get_one($purchase_number);
        if($have_parcel){
            // 更新采购单包裹加急的信息
            $updateOrderData = [
                'update_time'   => date('Y-m-d H:i:s'),
                'push_status'   => 0,//改为未推送
                'push_res'      => '未推送',//改为未推送
            ];
            $this->parcel_urgent_model->update_logistics(['purchase_order_num' => $purchase_number], $updateOrderData);
        }
    }


    /**
     * 根据拍单号获取物流状态
     * http://192.168.71.145:8080/xwiki/bin/view/java%E6%8E%A5%E5%8F%A3/service-alibaba-order/%E6%A0%B9%E6%8D%AE%E8%AE%A2%E5%8D%95%E5%8F%B7%E8%8E%B7%E5%8F%96%E7%89%A9%E6%B5%81%E7%8A%B6%E6%80%81/
     * @author Manson
     * @param $orderId
     * @return array
     */
    public function get_logistics_status($orderId)
    {
//        pr($orderId)
        if (empty($orderId) || !is_array($orderId)){
            return [];
        }
        $orderId = array_filter($orderId);
        $orderId = array_values($orderId);
        $params  = [
            'appKey'  => '8192050',
            'secKey'  => 'YyrrBl6Nmh',
            'orderId' => $orderId
        ];
//pr(json_encode($params,JSON_UNESCAPED_UNICODE));exit;
        $header       = ['Content-Type: application/json'];
        $request_url  = getConfigItemByName('api_config', 'alibaba', 'get_logistics_status');
        $access_token = getOASystemAccessToken();
        $request_url  = $request_url . '?access_token=' . $access_token;
        $results      = getCurlData($request_url, json_encode($params), 'post', $header);
        $results      = json_decode($results, true);

        if (isset($results['code']) && $results['code'] == 200 && isset($results['data'])) {
            return $results['data'];
        } else {
            log_message('error', sprintf('调取物流状态异常,入参:%s,结果:%s', json_encode($params), json_encode($results)));

            return [];
        }
    }

    /**
     * 查询物流轨迹信息
     * @author Manson
     * @return array
     */
    public function get_logistics_trace_info($orderId, $logisticsId='')
    {
        $params = [
            'appKey'  => '8192050',
            'secKey'  => 'YyrrBl6Nmh',
            'orderId' => $orderId, //拍单号
//            'logisticsId' =>$logisticsId //物流单号 加上物流单号查询不到数据,暂时不使用物流单号
        ];

        $header       = ['Content-Type: application/json'];
        $request_url  = getConfigItemByName('api_config', 'alibaba', 'get_logistics_trace_info');
        $access_token = getOASystemAccessToken();
        $request_url  = $request_url . '?access_token=' . $access_token;
        $results      = getCurlData($request_url, json_encode($params), 'post', $header);
        $results      = json_decode($results, true);

        if (isset($results['code']) && $results['code'] == 200 && isset($results['data']['logisticsTrace'])) {
            if (!empty($results['data']['logisticsTrace'])) {
                $res = array_column($results['data']['logisticsTrace'], NULL, 'logisticsBillNo');
                $res = $res[$logisticsId]??[];

                return ['code' => 1, 'data' => $res, 'msg' => '查询成功'];
            } else {
                return ['code' => 1, 'data' => [], 'msg' => '查询成功'];
            }
        } else {
//            log_message('error',sprintf('调取物流状态异常,入参:%s,结果:%s',json_encode($params),json_encode($results)));
            return ['code' => 0, 'data' => [], 'msg' => isset($results['data']['errorMessage']) ? $results['data']['errorMessage'] : '请求错误' . json_encode($results)];
        }
    }

    /**
     * @author Manson
     */
    public function sync_express_info()
    {
        //查询出未推送到pur_purchase_logistics_info表的数据
        $starttime = explode(' ', microtime());
        while (true) {
            /**
             * 采购单列表
             */
            $this->purchase_db->trans_begin();
            $sql    = "SELECT a.sku,a.purchase_number,b.express_no,b.cargo_company_id FROM pur_purchase_order_items a
LEFT JOIN  pur_purchase_order_pay_type b ON a.purchase_number=b.purchase_number
LEFT JOIN pur_purchase_logistics_info c ON a.purchase_number = c.purchase_number AND a.sku = c.sku 
WHERE c.purchase_number is NULL LIMIT 200";
            $result = $this->purchase_db->query($sql)->result_array();
            if (!empty($result)) {
                $this->purchase_db->insert_batch('purchase_logistics_info', $result);
                if ($this->purchase_db->trans_status() === FALSE) {
                    $this->purchase_db->trans_rollback();
                } else {
                    $this->purchase_db->trans_commit();
                }
            } else {
                /**
                 * 订单跟踪列表
                 */
                $sql    = "SELECT a.sku,a.purchase_number,a.courier_number as express_no,a.logistics_company as cargo_company_id FROM pur_purchase_progress a
LEFT JOIN	pur_purchase_logistics_info b ON a.purchase_number = b.purchase_number AND a.sku = b.sku 
WHERE b.purchase_number is NULL LIMIT 200";
                $result = $this->purchase_db->query($sql)->result_array();
                if (!empty($result)) {
                    $this->purchase_db->insert_batch('purchase_logistics_info', $result);
                    if ($this->purchase_db->trans_status() === FALSE) {
                        $this->purchase_db->trans_rollback();
                    } else {
                        $this->purchase_db->trans_commit();
                    }
                }
            }

            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);

            if (empty($result) || $thistime > 300) {
                echo sprintf("执行成功! 耗时: %s", $thistime);
                exit;
            }
        }

    }

    /**
     * 推送采购单快递单号到新仓库系统 获取数据
     * JAVA 接口地址：http://192.168.71.156/web/#/127?page_id=19213
     * @params:$purchaseNumber    string   采购单号
     * @author:luxu
     * @time:2021/1/11
     **/
    public function get_receive_bind_express($purchaseNumber){

        $result = $this->purchase_db->select('express_no,cargo_company_id,purchase_number,carrier_code')
            ->from('purchase_logistics_info')
            ->where_in('purchase_number',$purchaseNumber)
            ->distinct()
            ->get()
            ->result_array();
        if (!empty($result)){
            foreach ($result as $key => $item){
                $pushData[] = [
                    'expressNo' => $item["express_no"],
                    'poNumber' => $item["purchase_number"],
                    'expressSupplier' => $item["cargo_company_id"],
                    'expressSupplierCode' => $item["carrier_code"],
                    'createName' =>getActiveUserName()
                ];
            }

        }else{
            $purchase_number = array_unique($purchaseNumber);
            foreach ($purchase_number as $val){
                $pushData[] = [
                    'expressNo' => '',
                    'poNumber' => $val,
                    'expressSupplier' => '',
                    'expressSupplierCode' => '',
                    'createName' =>getActiveUserName()
                ];
            }
        }

        return $pushData;

    }

    /**
     * 推送采购单快递单号到新仓库系统
     * JAVA 接口地址：http://192.168.71.156/web/#/127?page_id=19213
     * @params:$purchaseNumber    string   采购单号
     * @author:luxu
     * @time:2021/1/11
     **/
    public function push_receive_bind_express($purchaseNumber = NULL,$pushData = NULL){

        $url          = getConfigItemByName('api_config', 'wms_system', 'push_receive_bind_express'); //获取推送url
        $access_taken = getOASystemAccessToken();

        if (empty($url)) {
            return ['code' => false, 'message' => '新仓库系统api不存在'];
        }
        if (empty($access_taken)) {
            return ['code' => false, 'message' => '获取access_token值失败'];
        }

        $url_api = $url . "?access_token=" . $access_taken;
        if( NULL != $purchaseNumber && NULL == $pushData) {
            $pushNewWmsData = $this->get_receive_bind_express($purchaseNumber);
        }else{
            $pushNewWmsData = $pushData;
        }
        $header = array('Content-Type: application/json','w:SZ_AA','org:org_00001');

        $results = getCurlData($url_api, json_encode(['items'=>$pushNewWmsData]), 'post', $header);
        $insert_data[] = [
            'purchase_number' => '',
            'log_info'        => json_encode(['items'=>$pushNewWmsData]).$results,
            'log_type'        => '采购系统推送数据',
            'create_time'     => date('Y-m-d H:i:s'),
        ];

        $this->purchase_db->insert_batch('pms_push_wms_log', $insert_data);
        $results = json_decode($results, true);

        $result = SetAndNotEmpty($results, 'data') ? $results['data'] : [];
        // 36531
        if(isset($results['code']) && $results['code'] == 200){
            $success_list = isset($result['successList'])?$result['successList']:NULL;
            if($success_list){
                $update = [];
                foreach ($success_list as $val){
                    $this->purchase_db->where(["purchase_number"=>$val])
                        ->update('purchase_logistics_info', ["is_push_wms" => 1, "update_time" => date('Y-m-d H:i:s')]);
                }
//                $this->purchase_db->update_batch('purchase_logistics_info', $update, 'purchase_number');
            }
        }

        if (!isset($results['code']) || $results['code'] != 200) {
            $failerDatas = isset($result['failureList'])?$result['failureList']:NULL;

            if( NULL != $failerDatas){
                $insert_data = [];
                foreach($failerDatas as $dataValue){
                    $insert_data[] = [
                        'purchase_number' => $dataValue??'',
                        'log_info'        => $results['msg']??'推送失败',
                        'log_type'        => '采购单推送新仓库系统失败',
                        'create_time'     => date('Y-m-d H:i:s'),
                    ];
                }
                if (!empty($insert_data)){
                    $this->purchase_db->insert_batch('pms_push_wms_log', $insert_data);
                }
            }
        }


    }

    /**
     *
     * 推送快递单号,快递公司到wms
     * 接口文档:http://192.168.71.156/web/#/105?page_id=4606
     * @author Manson
     * @param $data
     * @return array|bool
     */
    public function push_express_info_to_wms($data)
    {
        $url          = getConfigItemByName('api_config', 'wms_system', 'push_express_info_to_wms'); //获取推送url
        $access_taken = getOASystemAccessToken();
        if (empty($url)) {
            return ['code' => false, 'message' => 'api不存在'];
        }
        if (empty($access_taken)) {
            return ['code' => false, 'message' => '获取access_token值失败'];
        }
        $url_api = $url . "?access_token=" . $access_taken;
        $results = getCurlData($url_api, json_encode($data), 'post', ['Content-Type: application/json']);
        $results = json_decode($results, true);

        if (!isset($results['code']) || $results['code'] != 200) {
            $fail_list = $results['data']['failList']??'';
            if (!empty($fail_list)) {
                foreach ($fail_list as $key => $item) {
                    //记录失败日志
                    $insert_data[] = [
                        'purchase_number' => $item['purchaseOrderNo']??'',
                        'log_info'        => $item['msg']??'推送失败',
                        'log_type'        => '采购单页面的录入快递单号,快递公司功能',
                        'create_time'     => date('Y-m-d H:i:s'),
                    ];
                }
            } else {
                foreach ($data as $key => $item) {
                    $insert_data[] = [
                        'purchase_number' => $item['purchaseOrderNo']??'',
                        'log_info'        => $results['msg']??'推送失败',
                        'log_type'        => '采购单页面的录入快递单号,快递公司功能',
                        'create_time'     => date('Y-m-d H:i:s'),
                    ];
                }

            }
            if (!empty($insert_data)){
                $this->purchase_db->insert_batch('pms_push_wms_log', $insert_data);
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * 根据采购单号获取推送数据
     * @author Manson
     * @param $purchase_number
     * @return array
     */
    public function get_push_list($purchase_number)
    {

        if (empty($purchase_number)){
            return [];
        }
        $pushData = [];
        $result = $this->purchase_db->select('express_no,cargo_company_id,purchase_number')
            ->from('purchase_logistics_info')
            ->where_in('purchase_number',$purchase_number)
            ->distinct()
            ->get()
            ->result_array();

        if (!empty($result)){
            foreach ($result as $key => $item){
                $pushData[] = [
                    'expressNo' => $item["express_no"],
                    'purchaseOrderNo' => $item["purchase_number"],
                    'cargoCompanyName' => $item["cargo_company_id"],
                ];
            }

        }else{
            $purchase_number = array_unique($purchase_number);
            foreach ($purchase_number as $val){
                $pushData[] = [
                    'expressNo' => '',
                    'purchaseOrderNo' => $val,
                    'cargoCompanyName' =>'',
                ];
            }
        }
        return $pushData;
    }

    /**
     * 验证数据是否存在
     * 不存在则返回不存在快递单号
     * 存在则返回快递单号和状态数据
     * @author Justin
     * @param int $order_type
     * @param array $express_no
     * @return array
     */
    public function express_is_exists($order_type, $express_no = array())
    {
        if (empty($express_no) OR !in_array($order_type, [1, 2])) array('flag' => false, 'data' => []);

        if (1 == $order_type) {
            $table_name = $this->table_logistics_info_name;
        } else {
            $table_name = $this->exception_return_info_table;
        }

        //验证数据是否存在
        $result_tmp = $this->purchase_db->select('status,express_no')->where_in('express_no', $express_no)->get($table_name)->result_array();
        $express_no_data = array_combine($express_no, $express_no);
        $result = array();
        foreach ($result_tmp as $item) {
            unset($express_no_data[$item['express_no']]);
            $result[$item['express_no']] = $item['status'];
        }
        if (count($express_no_data)) {
            return array('flag' => false, 'data' => $express_no_data);
        } else {
            return array('flag' => true, 'data' => $result);
        }
    }

    /**
     * 获取快递公司信息，并缓存Redis
     * @return array
     */
    public function get_carrier_info(){
        $result = $this->rediss->getData('CARRIER_INFO');
        if (empty($result) OR !is_array($result)) {
            $query = $this->purchase_db;
            $query->select('carrier_code,carrier_name');
            $query->from($this->table_carrier_info_name);
            $result_tmp = $query->get()->result_array();
            $result = array();
            foreach ($result_tmp as $item) {
                $result[$item['carrier_code']] = $item;
            }
            unset($result_tmp);

            //缓存快递公司信息
            $this->rediss->setData('CARRIER_INFO', $result);
        }
        return $result;
    }

    /**
     * 在途在途是否异常处理
     * @param array $purchase_number
     * @param int $debug
     * @return array
     */
    public function handle_on_way_abnormal($purchase_number = array(), $debug = 0)
    {
        if (empty($purchase_number)) return ['status' => false, 'msg' => '采购单号不能为空'];
        if (!is_array($purchase_number)) $purchase_number = [$purchase_number];

        $query = $this->purchase_db;
        $query->trans_begin();
        try {
            //获取订单跟踪表订单数据
            $query->select('purchase_number,sku,warehouse_on_way_num,is_compare');
            $query->where_in('purchase_number', $purchase_number);
            $query->group_by('purchase_number,sku');
            $progress_order_data = $query->get($this->table_purchase_progress_name)->result_array();
            if ($debug) pr($query->last_query());
            if (!$progress_order_data) {
                return ['status' => true, 'msg' => '采购单订单跟踪数据不存在'];//脏数据，返回true，删除消息队列数据
            }
            $purchase_number = array_column($progress_order_data, 'purchase_number');
            $sku = array_unique(array_column($progress_order_data, 'sku'));
            if ($debug) pr($progress_order_data);

            //获取采购数量和入库数量
            $purchase_order_data = $this->_get_confirm_upselft_amount($purchase_number, $sku);
            if ($debug) {
                pr($query->last_query());
                pr($purchase_order_data);
            }
            if (!$purchase_order_data) {
                return ['status' => true, 'msg' => '采购单数据不存在'];//脏数据，返回true，删除消息队列数据
            }

            //获取取消数量
            $purchase_order_cancel = $this->_get_cancel_qty($purchase_number, $sku);
            if ($debug) {
                pr($query->last_query());
                pr($purchase_order_cancel);
            }

            //获取已报损数量
            $purchase_order_loss = $this->_get_loss_amount($purchase_number, $sku);
            if ($debug) {
                pr($query->last_query());
                pr($purchase_order_loss);
            }

            //组织数据
            $update_data = array();
            $push_mq_data = array();
            foreach ($progress_order_data as $key => $item) {
                //采购数量和入库数量
                if (!isset($purchase_order_data[$item['purchase_number'] . $item['sku']])) {
                    if ($debug) pr('continue：' . $key);
                    continue;
                }
                $tmp = $purchase_order_data[$item['purchase_number'] . $item['sku']];
                $unlisted_qty = (int)$tmp['confirm_amount'] - (int)$tmp['upselft_amount'];
                //取消数量
                $cancel_ctq = 0;
                if (isset($purchase_order_cancel[$item['purchase_number'] . $item['sku']])) {
                    $cancel_ctq = (int)$purchase_order_cancel[$item['purchase_number'] . $item['sku']]['cancel_ctq'];
                }
                //报损数量
                $loss_amount = 0;
                if (isset($purchase_order_loss[$item['purchase_number'] . $item['sku']])) {
                    $loss_amount = (int)$purchase_order_loss[$item['purchase_number'] . $item['sku']]['loss_amount'];
                }

                //‘内部在途数量’数据与‘仓库在途数量’数据不同步，重新存入队列等待仓库推送数据后再计算
                if (1 == $item['is_compare']) {
                    $push_mq_data[] = $item['purchase_number'];
                } else {
                    //内部采购在途=采购数量-入库数量-取消数量-报损数量 warehouse_on_way_num
                    $purchase_on_way_num = $unlisted_qty - $cancel_ctq - $loss_amount;
                    $on_way_abnormal = ($purchase_on_way_num != (int)$item['warehouse_on_way_num']) ? 1 : 0;
                    $update_data[$key] = array(
                        'purchase_on_way_num' => $purchase_on_way_num,
                        'on_way_abnormal' => $on_way_abnormal,
                        'purchase_number' => $item['purchase_number'],
                        'sku' => $item['sku'],
                    );
                }
            }

            //更新在途异常标识
            $time = date('Y-m-d H:i:s');
            //成功返回提示
            $success_msg = array();
            foreach ($update_data as $item) {
                $set_data = array(
                    'purchase_on_way_num' => $item['purchase_on_way_num'],
                    'purchase_update_time' => $time,
                    'on_way_abnormal' => $item['on_way_abnormal'],
                    'is_compare' => 1,
                );
                $where = array(
                    'purchase_number' => $item['purchase_number'],
                    'sku' => $item['sku'],
                );
                $query->update($this->table_purchase_progress_name, $set_data, $where);

                //写入操作日志表
                operatorLogInsert(
                    array(
                        'id' => $item['purchase_number'],
                        'type' => 'HANDLE_ON_WAY_ABNORMAL',
                        'content' => '更新在途异常标识',
                        'detail' => '更新在途异常标识PO:' . $item['purchase_number'] . ',SKU:' . $item['sku'] . ',是否在途异常：' . $item['on_way_abnormal'],
                        'user' => '定时计划',
                    )
                );
                $success_msg [] = 'PO：' . $item['purchase_number'] . '，SKU：' . $item['sku'];
            }

            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                return ['status' => false, 'msg' => '更新异常，事务回滚'];
            } else {
                $query->trans_commit();
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            log_message('error', $e->getMessage());
            return ['status' => false, 'msg' => $e->getMessage()];
        }
        return ['status' => true, 'msg' => '处理成功' . count($success_msg) . '条<br>' . implode('<br>', $success_msg), 'push_mq_data' => $push_mq_data];
    }

    /**
     * 在途在途是否异常处理
     * 处理时间差造成不是同一次产生的数据的情况
     * @param $debug
     * @return array
     */
    public function handle_on_way_abnormal_td($debug)
    {
        $query = $this->purchase_db;
        //获取订单跟踪表订单数据
        $query->select('id,purchase_number,sku,purchase_on_way_num,warehouse_on_way_num,warehouse_update_time,purchase_update_time,is_compare');
        $query->where('is_compare', 0);
        $query->where('purchase_update_time <>', '0000-00-00 00:00:00');
        $query->where('warehouse_update_time >= purchase_update_time');
        $progress_order_data = $query->get($this->table_purchase_progress_name)->result_array();

        //成功返回提示
        $success_msg = array();
        foreach ($progress_order_data as $item) {
            $on_way_abnormal = ((int)$item['purchase_on_way_num'] != (int)$item['warehouse_on_way_num']) ? 1 : 0;
            $set_data = array(
                'on_way_abnormal' => $on_way_abnormal,
                'is_compare' => 1,
            );
            $query->update($this->table_purchase_progress_name, $set_data, ['id' => $item['id']]);
            if ($debug) pr($query->last_query());
            //写入操作日志表
            operatorLogInsert(
                array(
                    'id' => $item['purchase_number'],
                    'type' => 'HANDLE_ON_WAY_ABNORMAL',
                    'content' => '更新在途异常标识(时间差数据)',
                    'detail' => '更新在途异常标识PO:' . $item['purchase_number'] . ',SKU:' . $item['sku'] . ',是否在途异常：' . $on_way_abnormal,
                    'user' => '定时计划',
                )
            );
            $success_msg [] = 'PO：' . $item['purchase_number'] . '，SKU：' . $item['sku'];
        }
        return ['status' => true, 'msg' => '处理成功' . count($success_msg) . '条时间差数据<br>' . implode('<br>', $success_msg)];
    }

    /**
     * 获取采购数量和入库数量
     * @param array $purchase_number
     * @param array $sku
     * @return array
     */
    private function _get_confirm_upselft_amount($purchase_number = array(), $sku = array())
    {
        $query = $this->purchase_db;
        $query->select('purchase_number,sku,SUM(confirm_amount) AS confirm_amount,SUM(upselft_amount) AS upselft_amount');
        $query->where_in('purchase_number', $purchase_number);
        $query->where_in('sku', $sku);
        $query->group_by('purchase_number,sku');
        $purchase_order_data_tmp = $query->get($this->table_order_items_name)->result_array();

        $purchase_order_data = array();
        foreach ($purchase_order_data_tmp as $item) {
            $purchase_order_data[$item['purchase_number'] . $item['sku']] = $item;
        }
        unset($purchase_order_data_tmp);
        return $purchase_order_data;
    }

    /**
     * 获取取消数量
     * @param array $purchase_number
     * @param array $sku
     * @return array
     */
    private function _get_cancel_qty($purchase_number = array(), $sku = array())
    {
        $query = $this->purchase_db;
        $query->select('a.purchase_number,a.sku,SUM(a.cancel_ctq) AS cancel_ctq');
        $query->from("{$this->table_order_cancel_detail_name} a");
        $query->join("{$this->table_order_cancel_name} b", 'a.cancel_id=b.id');
        $query->where_in('a.purchase_number', $purchase_number);
        $query->where_in('a.sku', $sku);
        $query->where_in('b.audit_status', [CANCEL_AUDIT_STATUS_CFYSK, CANCEL_AUDIT_STATUS_SYSTEM,CANCEL_AUDIT_STATUS_YDC]);
        $query->group_by('a.purchase_number,a.sku');
        $purchase_order_cancel_tmp = $query->get()->result_array();
        $purchase_order_cancel = array();
        foreach ($purchase_order_cancel_tmp as $item) {
            $purchase_order_cancel[$item['purchase_number'] . $item['sku']] = $item;
        }
        unset($purchase_order_cancel_tmp);
        return $purchase_order_cancel;
    }

    /**
     * 获取已报损数量
     * @param array $purchase_number
     * @param array $sku
     * @return array
     */
    private function _get_loss_amount($purchase_number = array(), $sku = array())
    {
        $query = $this->purchase_db;
        $query->select('pur_number AS purchase_number,sku,SUM(loss_amount) AS loss_amount');
        $query->where('status', REPORT_LOSS_STATUS_FINANCE_PASS);
        $query->where_in('pur_number', $purchase_number);
        $query->where_in('sku', $sku);
        $query->group_by('pur_number,sku');
        $purchase_order_loss_tmp = $query->get($this->table_order_reportloss_name)->result_array();
        $purchase_order_loss = array();
        foreach ($purchase_order_loss_tmp as $item) {
            $purchase_order_loss[$item['purchase_number'] . $item['sku']] = $item;
        }
        unset($purchase_order_loss_tmp);
        return $purchase_order_loss;
    }

    /**
     * 在途在途是否异常处理(处理历史数据)
     * @param int $limit
     * @param array $po
     * @param int $debug
     * @return array
     */
    public function handle_on_way_abnormal_history($limit = 100, $po = array(), $debug = 0)
    {
        $query = $this->purchase_db;

        //获取订单跟踪表订单数据
        $query->select('purchase_number,sku');
        if (!empty($po)) {
            $query->where_in('purchase_number', $po);
        } else {
            $query->where('warehouse_update_time', '0000-00-00 00:00:00');
            $query->where('purchase_update_time', '0000-00-00 00:00:00');
            $query->limit($limit);
        }
        $data = $query->get($this->table_purchase_progress_name)->result_array();
        if (empty($data)) return ['status' => false, 'msg' => '没有可处理的历史数据'];

        $post_data = array();
        foreach ($data as $item) {
            $post_data[] = array('purchaseOrderNo' => $item['purchase_number'], 'sku' => $item['sku']);
        }
        $progress_order_data = array();
        $purchase_number = array();
        $sku = array();
        if (!empty($post_data)) {
            //调用java接口获取仓库在途数量
            $res = $this->_get_warehouse_on_way_num($post_data);
            if (!$res['status']) return $res;
            $progress_order_data = $res['data'];
            $purchase_number = array_unique(array_column($progress_order_data, 'purchase_number'));
            $sku = array_unique(array_column($progress_order_data, 'sku'));
        }

        //获取采购数量和入库数量
        $purchase_order_data = $this->_get_confirm_upselft_amount($purchase_number, $sku);
        if ($debug) {
            pr($query->last_query());
            pr($purchase_order_data);
        }
        if (!$purchase_order_data) {
            //异常数据
            //写入操作日志表
            operatorLogInsert(
                array(
                    'type' => 'HANDLE_ON_WAY_ABNORMAL_HISTORY_FAIL',
                    'content' => '采购订单明细表pur_purchase_order_items存在异常数据',
                    'detail' => '采购订单明细表pur_purchase_order_items存在异常数据' . date('Y-m-d H:i:s'),
                    'user' => '定时计划',
                )
            );
            return ['status' => false, 'msg' => '采购单数据不存在'];
        }

        //获取取消数量
        $purchase_order_cancel = $this->_get_cancel_qty($purchase_number, $sku);
        if ($debug) {
            pr($query->last_query());
            pr($purchase_order_cancel);
        }

        //获取已报损数量
        $purchase_order_loss = $this->_get_loss_amount($purchase_number, $sku);
        if ($debug) {
            pr($query->last_query());
            pr($purchase_order_loss);
        }

        //组织数据
        $update_data = array();
        foreach ($progress_order_data as $key => $item) {
            //采购数量和入库数量
            if (!isset($purchase_order_data[$item['purchase_number'] . $item['sku']])) {
                if ($debug) pr('continue：' . $key);
                continue;
            }
            $tmp = $purchase_order_data[$item['purchase_number'] . $item['sku']];
            $unlisted_qty = (int)$tmp['confirm_amount'] - (int)$tmp['upselft_amount'];
            //取消数量
            $cancel_ctq = 0;
            if (isset($purchase_order_cancel[$item['purchase_number'] . $item['sku']])) {
                $cancel_ctq = (int)$purchase_order_cancel[$item['purchase_number'] . $item['sku']]['cancel_ctq'];
            }
            //报损数量
            $loss_amount = 0;
            if (isset($purchase_order_loss[$item['purchase_number'] . $item['sku']])) {
                $loss_amount = (int)$purchase_order_loss[$item['purchase_number'] . $item['sku']]['loss_amount'];
            }
            //内部采购在途=采购数量-入库数量-取消数量-报损数量
            $purchase_on_way_num = $unlisted_qty - $cancel_ctq - $loss_amount;
            $warehouse_on_way_num = (int)$item['warehouse_on_way_num'];
            $on_way_abnormal = ($purchase_on_way_num != $warehouse_on_way_num) ? 1 : 0;
            $update_data[$key] = array(
                'purchase_on_way_num' => $purchase_on_way_num,
                'warehouse_on_way_num' => $warehouse_on_way_num,
                'on_way_abnormal' => $on_way_abnormal,
                'purchase_number' => $item['purchase_number'],
                'sku' => $item['sku'],
            );
        }

        $query->trans_begin();
        try {
            //更新在途异常标识
            $time = date('Y-m-d H:i:s');
            //成功返回提示
            $success_msg = array();
            foreach ($update_data as $item) {
                $set_data = array(
                    'purchase_on_way_num' => $item['purchase_on_way_num'],
                    'warehouse_on_way_num' => $item['warehouse_on_way_num'],
                    'purchase_update_time' => $time,
                    'warehouse_update_time' => $time,
                    'on_way_abnormal' => $item['on_way_abnormal'],
                    'is_compare' => 1,
                );
                $where = array(
                    'purchase_number' => $item['purchase_number'],
                    'sku' => $item['sku'],
                );
                $query->update($this->table_purchase_progress_name, $set_data, $where);

                //写入操作日志表
                operatorLogInsert(
                    array(
                        'id' => $item['purchase_number'],
                        'type' => 'HANDLE_ON_WAY_ABNORMAL',
                        'content' => '更新在途异常标识',
                        'detail' => '更新在途异常标识PO:' . $item['purchase_number'] . ',SKU:' . $item['sku'] . ',是否在途异常：' . $item['on_way_abnormal'],
                        'user' => '定时计划',
                    )
                );
                $success_msg [] = 'PO：' . $item['purchase_number'] . '，SKU：' . $item['sku'];
            }

            if ($query->trans_status() === FALSE) {
                $query->trans_rollback();
                return ['status' => false, 'msg' => '更新异常，事务回滚'];
            } else {
                $query->trans_commit();
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            log_message('error', $e->getMessage());
            return ['status' => false, 'msg' => $e->getMessage()];
        }
        return ['status' => true, 'msg' => '处理成功' . count($success_msg) . '条<br>' . implode('<br>', $success_msg)];
    }

    /**
     * 调用java接口获取仓库在途数量
     * @param array $post_data
     * @return array
     */
    private function _get_warehouse_on_way_num($post_data = array())
    {
        $url = getConfigItemByName('api_config', 'on_way_abnormal', 'get_history'); //获取推送url
        $access_taken = getOASystemAccessToken();

        if (empty($url)) return ['status' => false, 'msg' => 'API不存在'];
        if (empty($access_taken)) return ['status' => false, 'msg' => '获取access_token值失败'];

        $url_api = $url . "?access_token=" . $access_taken;

        $result = getCurlData($url_api, json_encode($post_data), 'POST', ['Content-Type: application/json']);
        $result = json_decode($result, true);

        if (!isset($result['code']) OR $result['code'] != 200) {
            return ['status' => false, 'msg' => '接口获取数据失败'];
        }
        if (empty($result['data']) OR !is_array($result['data'])) {
            return ['status' => false, 'msg' => '接口返回数据为空,请求PO:' . json_encode($post_data)];
        }

        $progress_order_data = array();
        foreach ($result['data'] as $item) {
            $progress_order_data[] = array(
                'purchase_number' => $item['purchaseOrderNo'],
                'sku' => $item['sku'],
                'warehouse_on_way_num' => $item['warehouseOnWayNum'],
            );
        }

        return ['status' => true, 'msg' => '', 'data' => $progress_order_data];
    }

    /**
     * 获取未推送wms的物流单数据（门户系统推送到采购系统的物流单数据）
     * @param int    $limit
     * @param string $express_no
     * @return array
     */
    public function get_gateway_express_order($limit = 200, $express_no = '')
    {
        $pushData = [];
        $this->purchase_db->select('express_no,cargo_company_id,purchase_number');
        $this->purchase_db->from('purchase_logistics_info');
        $this->purchase_db->where(['type' => 2, 'push_to_wms' => 0]);
        if (!empty($express_no)) {
            $this->purchase_db->where('express_no', $express_no);
        }
        $result = $this->purchase_db->distinct()->limit($limit)->get()->result_array();

        foreach ($result as $key => $item) {
            $pushData[] = [
                'expressNo' => $item["express_no"],
                'purchaseOrderNo' => $item["purchase_number"],
                'cargoCompanyName' => $item["cargo_company_id"],
            ];
        }
        return $pushData;
    }

    /**
     * 根据快递单号和采购单号更新推送到wms状态
     * @param string $express_no 快递单号
     * @param string $purchase_number 采购单号
     */
    public function update_push_status($express_no, $purchase_number)
    {
        $where = [
            'express_no' => $express_no,
            'purchase_number' => $purchase_number,
        ];
        $this->purchase_db->where($where);
        $this->purchase_db->set('push_to_wms', '1');
        $this->purchase_db->update('purchase_logistics_info');
    }
}