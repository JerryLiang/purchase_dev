<?php

/**
 * 发运管理
 * @time:2020/4/27
 * @author:luxu
 **/
class Purchase_shipping_management extends MY_Controller{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Purchase_shipping_management_model');
        $this->load->model('Purchase_user_model','Purchase_user_model',false,'user');
    }

    /**
     * 验证HTTP 参数是否合法
     * @param $data    array   HTTP 传入参数
     * @author:luxu
     * @time:2020/4/17
     * @return  array
     **/
    private function verifyHttpData($data){

        $return =[];
        if( empty($data)){

            return $return;
        }

        foreach( $data as $http_key=>$http_value){

            $return[$http_key] = $this->input->get_post($http_key);
        }

        return $return;

    }

    /**
     * 发运管理列表
     * @METHOD  GET
     * @author:luxu
     * @time:2020/4/17
     **/

    public function getPurchaseShippingList(){

        try{

            $params = $this->verifyHttpData($_GET);

            $result = $this->Purchase_shipping_management_model->getShippingManagementData($params);
            $data = $result;
            $data['drop_down_box']['shipment_type'] = [1=>'工厂发运',2=>'中转仓发运'];
            $data['drop_down_box']['orders_status'] = [1=> "等待采购询价",
                                                        2=>"信息修改待审核",
                                                        3=> "待采购审核",
                                                        6=> "等待生成进货单",
                                                        7=>"等待到货",
                                                        8=>"已到货待检测",
                                                        9=>"全部到货",
                                                        10=> "部分到货等待剩余到货",
                                                        11=>"部分到货不等待剩余到货",
                                                        12=>"作废订单待审核",
                                                        13=>"作废订单待退款",
                                                        14=>"已作废订单",
                                                        15=>"信息修改驳回",
            ];
            $data['drop_down_box']['suggest_order_status']= [1=>"等待采购询价",
                    2=>"信息修改待审核",
                    3=>"待采购审核",
                    6=>"等待生成进货单",
                    7=>"等待到货",
                    8=>"已到货待检测",
                    9=>"全部到货",
                    10=> "部分到货等待剩余到货",
                    11=>"部分到货不等待剩余到货",
                    12=>"作废订单待审核",
                    13=>"作废订单待退款",
                    14=>"已作废订单",
                    15=>"信息修改驳回",];
            //是否退税(默认 0.否,1.退税)
            $data['drop_down_box']['is_drawback'] = [1=>'是',2=>'否'];
            $data['drop_down_box']['pertain_wms'] = getWarehouse();
            $data['drop_down_box']['buryer_name'] = $this->Purchase_user_model->get_list();
            $data['drop_down_box']['shipping_status'] = [0=>'空',1=>'待采购确认',3=>'采购审核通过',2=>'审核驳回',5=>'自动审核通过'];
            // 二验校验是否已经确认
            $data['drop_down_box']['deliver_status'] = [1=>'否',2=>'是'];
            // 验证货结果
            $data['drop_down_box']['check_status'] = [3=>'验货合格',4=>'验货不合格'];
            // 计划数量
            $data['drop_down_box']['plan_qty'] = [1=>'≠0',2=>'=0'];
            //物流类型
            $data['drop_down_box']['logis_type']=[];
            // 权限交期是否可以更改
            $data['drop_down_box']['is_can_change_time']=[1=>'是',2=>'否'];
            //目的仓
            $data['drop_down_box']['warehouse_code'] = getWarehouse();
            $this->load->model('warehouse/Logistics_type_model');
            $logistics_type_list = $this->Logistics_type_model->get_logistics_type_list();
            $data['drop_down_box']['logis_type'] = NULL;
            if(!empty($logistics_type_list)){

                $data['drop_down_box']['logis_type']  = array_column($logistics_type_list,"type_name","type_code");

            }
            $this->success_json($data);

        }catch ( Exception $exp ){

            $this->error_info($exp->getMessage());
        }
    }

    /**
     * 更新预计到货时间
     * POST
     * @author:luxu
     * @time:2020/4/18
     **/

    public function updateShipmentTime(){

        try{

            $params = $this->verifyHttpData($_POST);
            if( !isset($params['ids']) || empty($params['ids']) ){
                throw new Exception("请传入ID");
            }

            if( !isset($params['es_shipment_time']) || empty($params['es_shipment_time'])){
                throw new Exception("请传入预计发货时间");
            }

            if( !isset($params['es_arrival_time']) || empty($params['es_arrival_time'])){

                throw new Exception("请传入预计到货时间");
            }

            $result = $this->Purchase_shipping_management_model->updateShipmentTime($params);
            $this->success_json();
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取修改日志
     * GET
     * @author:luxu
     * @time:2020/4/20
     **/
    public function getUpdateLog(){

        try{
          $params =  $this->verifyHttpData($_GET);
          if( !isset($params['id']) || empty($params['id'])){

              throw new Exception("请传入ID");
          }
          // 获取数据的类型，如果HTTP 协议没有传入就默认为预计发货时间
          $getDataType = isset($params['datatype'])?$params['datatype']:'shipment';
          // 调用MODEL 方法获取日志数据
          $logs = $this->Purchase_shipping_management_model->getUpdateLog($params,$getDataType);
          $this->success_json($logs);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     *  获取变更日志信息
     *  @Mthod GET
     *  @author:luxu
     *  @time:2020/4/20
     **/
    public function getChangeLog(){

        try{
            $params =  $this->verifyHttpData($_GET);
            //  验证如果HTTP 客户端没有传入参数，或者没有传入备货单号抛出异常
            if(empty($params) || !isset($params['demand_number']) ||  empty($params['demand_number'])) {

                throw new Exception("请传入备货单号");
            }

            $logResult = $this->Purchase_shipping_management_model->getChangeLog($params);
            if(!empty($logResult)){

                foreach($logResult as $key=>&$value){

                    if( $value['type'] == 'change_shipment_type'){

                        $value['type'] = "发运类型变更";
                    }

                    if( $value['type'] == 'change_destination_warehouse_code'){

                        $value['type'] = "目的仓";
                    }

                    if( $value['type'] == 'change_logistics_type'){

                        $value['type'] = "物流类型";
                    }

                    if( $value['type'] == 'change_plan_qty'){

                        $value['type'] = "计划数量";
                    }


                }
            }
            $this->success_json($logResult);
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }


    /**
     *  获取计划系统变更日志信息
     *  @Mthod GET
     *  @author:luxu
     *  @time:2020/4/20
     **/
    public function getPlangChangeLog(){

        try{
            $params =  $this->verifyHttpData($_GET);
            //  验证如果HTTP 客户端没有传入参数，或者没有传入备货单号抛出异常
            if(empty($params) || !isset($params['demand_number']) ||  empty($params['demand_number'])) {

                throw new Exception("请传入备货单号");
            }
            $params['type'] = '计划数量';

            $logResult = $this->Purchase_shipping_management_model->getChangeLog($params);
            $this->success_json($logResult);
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 发运管理展示审核数据
     * @METHOD:GET
     * @AUTHOR:LUXU
     * @TIME：2020/7/1
     **/
    public function showToExamineShipping(){

        try{

            $params = $this->verifyHttpData($_GET);
            if(empty($params) || !isset($params['ids']) || empty($params['ids'])){

                throw new Exception("缺少参数");
            }

            $ids = explode(",",$params['ids']);

            $result = $this->Purchase_shipping_management_model->showToExamineShipping($ids);
            if(!empty($result)){

                foreach($result as $key=>$value){

                    if( $value['shipment_type'] == 1){
                        $result[$key]['shipment_type_ch'] = "工厂发运";
                    }

                    if( $value['shipment_type'] == 2){
                        $result[$key]['shipment_type_ch'] = "中转仓发运";
                    }
                }
            }
            $resultData = $data = [];
            if(!empty($result)){

                foreach( $result as $key=>$value){

                    if( !isset($resultData[$value['rerun_batch']])){

                        $resultData[$value['rerun_batch']]['itemList'] = [];
                    }
                    $resultData[$value['rerun_batch']]['itemList'][] = $value;

                }
            }

            if(!empty($resultData)){

                foreach($resultData as $key=>$value){

                    $data[] = [

                        'rerun_batch' =>$key,
                        'itemList' =>$value['itemList']
                    ];
                }
            }
            $this->success_json($data);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 审核发运类型
     * @METHOD:POST
     * @author:luxu
     * @time:2020/4/21
     *
     * is_status: 1
    remark:
    data: [{"demand_number":"HBH190418AO17606",
     * "items":[{"new_demand_number":"HBH200509AA09506","es_shipment_time":"2020-07-14 00:00:00",
     * "arrival_time":"2020-07-15 00:00:00"}]},{"demand_number":"HBH190418AO17606",
     * "items":[{"new_demand_number":"HBH200509AA09505",
     * "es_shipment_time":"0000-00-00 00:00:00","arrival_time":"2019-07-13 00:00:00"}]}]
    uid: 1973
     **/

    public function toExamineShipping(){

        try{

            $params =  $this->verifyHttpData($_POST);

            if( !isset($params['data']) || empty($params['data'])){

                throw new Exception("缺少参数");
            }

            $datas = json_decode($params['data'],True);

            //不想让前端调整了, 后端重组成需要的数据格式
            foreach($datas as $key => $item){
                $handle_data[$item['rerun_batch']][] = $item['items'][0];
            }
            foreach($handle_data as $rerun_batch => $value){
                 $result = $this->Purchase_shipping_management_model->toExamineShipping($value,$params['is_status'],$rerun_batch);
            }
           $this->success_json();
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 修改交期
     * @METHOD:POST
     * @author:luxu
     * @time:2020/4/21
     **/
    public function updateDelivery(){

        try{

            $params =  $this->verifyHttpData($_POST);
            if(empty($params) || !isset($params['demand_number']) || empty($params['demand_number'])){

                throw new Exception("缺少参数");
            }

            if( !isset($params['es_arrival_time']) || empty($params['es_arrival_time'])){

                throw new Exception("请传入预计到货时间");
            }

            if( !isset($params['es_shipment_time']) || empty($params['es_shipment_time'])){
                throw new Exception("请传入预计发货时间");
            }

//            if( !isset($params['is_status']) ){
//
//                throw new Exception("请传入审核结果");
//            }
//            if( $params['is_status'] == 2 && (!isset($params['remark']) || empty($params['remark']))){
//
//                throw new Exception("请填写备注");
//            }

            $result = $this->Purchase_shipping_management_model->updateDelivery($params);
            if($result){
                $this->success_json();
            }else{
                throw new Exception("数据修改失败");
            }


        }catch ( Exception $exception ){

            $this->error_json($exception->getMessage());
        }
    }

    /**
     *导出CSV 格式文件
     * @author:luxu
     * @time:2020/4/22
     **/

    public function getshippingcsv(){

        try {
            $params = $this->verifyHttpData($_GET);
            $params['limit'] =1;
            //获取数据总数
            $totalData = $this->Purchase_shipping_management_model->getShippingManagementData($params);
            $total = 0;
            if( isset($totalData['page']) && isset($totalData['page']['total'])){
                $total = $totalData['page']['total'];
            }


            $limit = 1000;
            $page = ceil($total/$limit);
            $webfront_path = dirname(dirname(APPPATH));

            $file_name = rand(1, 100) . '-progre.csv';
            $product_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file, 'w');
            $fp = fopen($product_file, "a");
            $heads = ['序号','新备货单号', '发运类型', '地域仓', '目的仓', 'sku', '图片', '采购单号', '供应商名称', '是否退税', '备货单采购数量',
                '备货单取消数量', '备货单入库数量', '二验交期已确认', '验货结果', '计划数量', '入库数量',
                '单个体积', '总体积', '合同号', '审核时间', '预计发货时间','预计到货时间', '交期是否可更改', '订单状态（h）', '备货单状态',
                '采购仓库', '物流类型', '采购员', '验货日期', '创建时间', '变更日期'];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            fputcsv($fp, $title);
            $numi = 0;
            for ($i = 1; $i <= $page; $i++) {
                $params['offsets'] = ($i - 1) * $limit;
                $params['limit']  = $limit;
                $resultData = $this->Purchase_shipping_management_model->getShippingManagementData($params);
                if(!empty($resultData) && !empty($resultData['list'])){

                     foreach($resultData['list'] as $key=>$v_value){

                         $v_value_tmp = [];
                         $v_value_tmp['id'] = ++$numi;
                         $v_value_tmp['new_demand_number'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['new_demand_number']);
                         $v_value_tmp['shipment_type_ch'] =  iconv('UTF-8', 'GBK//IGNORE', $v_value['shipment_type_ch']);
                         $v_value_tmp['station_name'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['station_name']);
                         $v_value_tmp['destination_warehouse_name'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['destination_warehouse_name']);
                         $v_value_tmp['sku'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['sku']);
                         $v_value_tmp['product_img_url'] = $v_value['product_img_url'];
                         $v_value_tmp['purchase_number'] = $v_value['purchase_number'];
                         $v_value_tmp['supplier_name'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['supplier_name']);
                         $v_value_tmp['is_drawback_ch'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['is_drawback_ch']);
                         $v_value_tmp['confirm_amount'] = $v_value['confirm_amount'];
                         $v_value_tmp['cancel_amount'] = $v_value['cancel_amount'];
                         $v_value_tmp['warehouse_numbers'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['warehouse_numbers']);
                         $v_value_tmp['deliver_status_ch'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['deliver_status_ch']);
                         $v_value_tmp['checkstatus_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['checkstatus_ch']);
                         $v_value_tmp['plan_qty'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['plan_qty']);
                         $v_value_tmp['t_plan_qty'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['t_plan_qty']);
                         $v_value_tmp['singlevolume'] = $v_value['singlevolume'];
                         $v_value_tmp['sumvolume'] = $v_value['sumvolume'];
                         $v_value_tmp['compactnumbers'] = $v_value['compactnumbers'];
                         $v_value_tmp['audit_time'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['audit_time']);
                         $v_value_tmp['es_arrival_time'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['es_arrival_time']);
                         $v_value_tmp['es_shipment_time'] =  iconv('UTF-8', 'GBK//IGNORE',$v_value['es_shipment_time']);
                         $v_value_tmp['is_can_change_time_ch'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['is_can_change_time_ch']);
                         $v_value_tmp['order_status_ch'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['order_status_ch']);
                         $v_value_tmp['suggest_status_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['suggest_status_ch']);
                         $v_value_tmp['warehousename'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['warehousename']);
                         $v_value_tmp['logis_type'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['logis_type']);
                         $v_value_tmp['buyer_name'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['buyer_name']);
                         //check_time
                         $v_value_tmp['check_time'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['check_time']);
                         $v_value_tmp['create_time'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['create_time']);
                         $v_value_tmp['update_time'] = iconv('UTF-8', 'GBK//IGNORE',$v_value['update_time']);
                         $tax_value_temp = $v_value_tmp;
                         fputcsv($fp, $tax_value_temp);

                     }
                    ob_flush();
                    flush();
                }

            }
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名

            $down_file_url = $down_host . 'download_csv/' . $file_name;
            $this->success_json($down_file_url);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 导出EXCEL 格式文件
     * @author:luxu
     * @time:2020/4/22
     **/

    public function getshippingexcel(){

        try {
            $params = $this->verifyHttpData($_GET);
            $params['limit'] = 1;
            //获取数据总数
            $totalData = $this->Purchase_shipping_management_model->getShippingManagementData($params);
            $total = 0;
            if (isset($totalData['page']) && isset($totalData['page']['total'])) {
                $total = $totalData['page']['total'];
            }
            $limit = 2000;
            $page = ceil($total / $limit);
            $heads = ['序号','新备货单号', '发运类型', '地域仓', '目的仓', 'sku', '图片', '采购单号', '供应商名称', '是否退税', '备货单采购数量',
                '备货单取消数量', '备货单入库数量', '二验交期已确认', '验货结果', '计划数量', '入库数量',
                '单个体积', '总体积', '合同号', '审核时间', '预计发货时间','预计到货时间', '交期是否可更改', '订单状态（h）', '备货单状态',
                '采购仓库', '物流类型', '采购员', '验货日期', '创建时间', '变更日期'];
            static $return = [];
            for ($i = 1; $i <= $page; $i++) {

                $params['offsets'] = ($i - 1) * $limit;
                $params['limit']  = $limit;
                $resultData = $this->Purchase_shipping_management_model->getShippingManagementData($params);
                if (!empty($resultData['list'])) {
                    $return = array_merge($return, $resultData['list']);

                }
                unset($resultData);
            }

            $returnList['list'] = $return;
            $returnList['header'] = $heads;
            $this->success_json($returnList);

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }
}