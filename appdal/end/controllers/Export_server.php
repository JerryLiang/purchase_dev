<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
use PHPUnit\Framework\TestCase;


class Export_server extends MY_API_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_new_model');
        $this->load->model('purchase/purchase_order_progress_model');
        $this->load->model('system/data_center_model');
        $this->load->model('product/product_model');
        $this->load->model('product/product_line_model', 'product_line', false, 'product');
        $this->load->model('product/product_update_log_model','product_update_log',false,'product');
         $this->load->model('Product_mod_audit_model','product_mod_audit_other',false,'product');

        $this->load->model('statement/Purchase_inventory_items_model');
        $this->load->library('Export');
        $this->load->model('statement/Charge_against_surplus_model');
        $this->load->model('statement/Supplier_balance_model','balance');
        $this->load->model('purchase/Reduced_edition_model');
        $this->load->model('user/User_group_model');
        $this->load->model('purchase/delivery_model','delivery');
        $this->load->model('finance/Offline_refund_model');
        $this->load->helper('status_product');

        $this->load->model('purchase/purchase_order_determine_model');
        $this->load->model('abnormal/Abnormal_list_model','abnormal_model');
        $this->load->model('purchase/purchase_order_lack_model');
        $this->load->model('product/Shortage_model');
        $this->load->model('warehouse/warehouse_model');
        $this->load->model('purchase_suggest/purchase_suggest_map_model');
        $this->load->model('purchase_suggest/purchase_demand_model');
        $this->load->library('Search_header_data');


        $this->load->library('Upload_image');
    }

    public function demander_export(){
        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9521);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //收到请求后触
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "处理异步任务中。\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            // echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/suggest_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/demander_export_data ' . $data['id']);
//            $this->download_order_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }


    public function demander_export_data($id=1313){

        echo "download----";
        echo "id=".$id;

        ini_set('max_execution_time','18000');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            $limit  = 1500;
            $conditiondatas = json_decode($params['condition'],true);
            $total = $conditiondatas['number'];
            $page = ceil($total/$limit);

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'demand' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $fp = fopen($reduced_file, "a+");
            $heads = ['需求单状态','需求单业务线','需求单号',
                'sku','是否精品','是否加急','产品状态','产品名称','一级产品线',
                '需求数量','最小起订量','缺货数量','采购仓库','公共仓','目的仓', '申请人','销售备注','作废原因','需求类型',
                '需求锁定', '创建日期','预计供货时间','解锁时间','是否退税','是否海外仓首单','是否国内转海外','发运类型','物流类型','开发类型', '销售名称', '销售分组',
                '是否海外精品', '是否熏蒸', '平台','站点','销售账号','变更类型','货源状态'];

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            $x = 0;
            if($total>=1) {
                for ($i = 1; $i <= $page; $i++) {

                    $conditiondatas['offset']      = ($i - 1) * $limit;
                    $conditiondatas['limit'] = $limit;
                    $orders_export_info = $this->purchase_suggest_model->get_demand_datas($conditiondatas);
                   // print_r($orders_export_info);die();
                    //print_r($orders_export_info);die();
                    $purchase_tax_list_export = (isset($orders_export_info['values']) && !empty($orders_export_info['values']))?$orders_export_info['values']:NULL;
                  //  print_r($purchase_tax_list_export);die();
                    if ($purchase_tax_list_export) {

                        $heads = ['需求单状态','需求单业务线','需求单号',
                            'sku','是否精品','是否加急','产品状态','产品名称','一级产品线',
                            '需求数量','最小起订量','缺货数量','采购仓库','公共仓','目的仓', '申请人','销售备注','作废原因','需求类型',
                            '需求锁定', '创建日期','预计供货时间','解锁时间','是否退税','是否海外仓首单','是否国内转海外','发运类型','物流类型','开发类型', '销售名称', '销售分组',
                            '是否海外精品', '是否熏蒸', '平台','站点','销售账号'];

                        foreach ($purchase_tax_list_export as $value) {
                            $value_tmp = [];
                            $value_tmp['demand_status_ch']           = iconv("UTF-8", "GBK//IGNORE",$value['demand_status_ch']);

                            $value_tmp['purchase_type_ch']            = iconv("UTF-8", "GBK//IGNORE",$value['purchase_type_ch']);

                            $value_tmp['demand_number'] = $value['demand_number'];

                            $value_tmp['sku'] = iconv("UTF-8", "GBK//IGNORE",$value['sku']);
                            $value_tmp['is_boutique_ch']         = iconv("UTF-8", "GBK//IGNORE",$value['is_boutique_ch']);
                            $value_tmp['is_expedited_ch']         = iconv("UTF-8", "GBK//IGNORE",$value['is_expedited_ch']);
                            $value_tmp['product_status_ch']            = iconv("UTF-8", "GBK//IGNORE",$value['product_status_ch']);
                            $value_tmp['product_name']                      = iconv("UTF-8", "GBK//IGNORE",trim($value['product_name'],'"'));
                            $value_tmp['product_line_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['product_line_ch']);
                            $value_tmp['demand_data']                   = iconv("UTF-8", "GBK//IGNORE",$value['demand_data']);
                            $value_tmp['starting_qty']        = iconv("UTF-8", "GBK//IGNORE",$value['starting_qty']);
                            $value_tmp['left_stock']    = iconv("UTF-8", "GBK//IGNORE",$value['left_stock']);
                            $value_tmp['warehouse_name']             = iconv("UTF-8", "GBK//IGNORE",$value['warehouse_name']);
                            $value_tmp['gwarehouse_code'] = iconv("UTF-8", "GBK//IGNORE",$value['gwarehouse_code']);
                            $value_tmp['destination_warehouse_ch']           = iconv("UTF-8", "GBK//IGNORE",$value['destination_warehouse_ch']);
                            $value_tmp['applicant']          = iconv("UTF-8", "GBK//IGNORE",$value['create_user_name']);
                            $value_tmp['sales_note']               = iconv("UTF-8", "GBK//IGNORE",$value['sales_note']); //销售备注
                            //缺货数量(新)
                            $value_tmp['tovoid_reason']             = iconv("UTF-8", "GBK//IGNORE",$value['tovoid_reason']);

                            $value_tmp['demand_name_ch']        = iconv("UTF-8", "GBK//IGNORE",$value['demand_name_ch']);
                            $value_tmp['demand_lock_ch']             = iconv("UTF-8", "GBK//IGNORE",$value['demand_lock_ch']);;
                            $value_tmp['create_time']        = iconv("UTF-8", "GBK//IGNORE",$value['create_time']);
                            $value_tmp['estime_time']              = iconv("UTF-8", "GBK//IGNORE",$value['estime_time']);
                            $value_tmp['over_lock_time']           = iconv("UTF-8", "GBK//IGNORE",$value['over_lock_time']);
                            $value_tmp['is_drawback_ch']      = iconv("UTF-8", "GBK//IGNORE",$value['is_drawback_ch']);
                            $value_tmp['is_overseas_first_order_ch']     = iconv("UTF-8", "GBK//IGNORE",$value['is_overseas_first_order_ch']);
                            $value_tmp['transformation_ch']                 = iconv("UTF-8", "GBK//IGNORE",$value['transformation_ch']);; //币种
                            $value_tmp['shipment_type_ch']            = iconv("UTF-8", "GBK//IGNORE",$value['shipment_type_ch']);
                            $value_tmp['logistics_type_ch']          = iconv("UTF-8", "GBK//IGNORE",$value['logistics_type_ch']);
                            $value_tmp['development_type_ch'] = SetAndNotEmpty($value, 'development_type_ch') ? iconv("UTF-8", "GBK//IGNORE",$value['development_type_ch']) : '--';
                            $value_tmp['sales_name']           = SetAndNotEmpty($value, 'sales_name') ? iconv("UTF-8", "GBK//IGNORE",$value['sales_name']) : '--';
                            $value_tmp['sales_group']    = SetAndNotEmpty($value, 'sales_group') ? iconv("UTF-8", "GBK//IGNORE",$value['sales_group']) : '--';   //销售组
                            $value_tmp['is_overseas_boutique']    = SetAndNotEmpty($value, 'is_overseas_boutique') ? iconv("UTF-8", "GBK//IGNORE",$value['is_overseas_boutique']): '--';   //海外精品

                            $value_tmp['extra_handle_ch']           = SetAndNotEmpty($value, 'extra_handle_ch') ? iconv("UTF-8", "GBK//IGNORE",$value['extra_handle_ch']) : '--';
                          //  $value_tmp['extra_handle_ch']         = iconv("UTF-8", "GBK//IGNORE",$value['create_user_name']);   //申请人
                            $value_tmp['platform']              = SetAndNotEmpty($value, 'platform') ? iconv("UTF-8", "GBK//IGNORE",$value['platform']): '--';   //申请时间
                            $value_tmp['site']               = SetAndNotEmpty($value, 'site')  ? iconv("UTF-8", "GBK//IGNORE",$value['site']) : '--';
                            $value_tmp['sales_account']          = SetAndNotEmpty($value, 'sales_account') ? iconv("UTF-8", "GBK//IGNORE",$value['sales_account']) : '--';
                            $value_tmp['sku_change_data'] =  SetAndNotEmpty($value, 'sku_change_data') ? iconv("UTF-8", "GBK//IGNORE",$value['sku_change_data']) : '--';
                            $value_tmp['supply_status_ch'] =  SetAndNotEmpty($value, 'supply_status_ch') ? iconv("UTF-8", "GBK//IGNORE",$value['supply_status_ch']) : '--';

                            $tax_list_tmp = $value_tmp;
                            fputcsv($fp, $tax_list_tmp);

                        }

                        if ($i * $limit < $total) {
                            $cur_num = $i * $limit;
                        } else {
                            $cur_num = $total;
                        }
                        $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
                    }
                }
            }

            $file_data = $this->upload_file($reduced_file);

            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * 退款数据导出SWOOLE 服务
     * @param 无
     * @author:luxu
     * @time:2021年1月16号
     **/
    public function suggest_export(){
        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9520);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "处理异步任务中。\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            // echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/suggest_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/suggest_export_data ' . $data['id']);
//            $this->download_order_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }


    public function suggest_export_data($id=1282){
        ini_set('max_execution_time','18000');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            $limit  = 500;
            $param = json_decode($params['condition'],true);
            $total = $param['number'];
            $page = ceil($total/$limit);

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = ($param['list_type'] == 2?'suggest_': "demand_") . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $fp = fopen($reduced_file, "a+");
            $list_type = isset($param['list_type']) && in_array($param['list_type'], [2, 3])?$param['list_type']:0;
            $heads = $this->suggest_export_data_head($list_type);

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);

            $warehouse_list = $this->warehouse_model->get_code_name_list();
            $logistics_type_list = $this->warehouse_model->get_logistics_type();
            $this->load->model('system/Reason_config_model');
            $param['status'] = 1;//启用的
            $cancel_reason_category_list = $this->Reason_config_model->get_cancel_reason_list($param);
            $category_list = array_column($cancel_reason_category_list['values'],'reason_name','id');
            $getProductStatus = getProductStatus();
            $x = 0;
            if($total>=1) {
                for ($i = 1; $i <= $page; $i++) {
                    $export_offset      = ($i - 1) * $limit;
                    $param['page'] = $i;
                    $param['offset'] = $export_offset;
                    $param['limit'] = $limit;
//                    $orders_export_info = $this->purchase_demand_model->get_demanding_list($param, false, false, false);
                    if($param['list_type'] == 2){
                        $orders_export_info = $this->purchase_demand_model->get_demanding_list($param);
                    }else{
                        $orders_export_info = $this->purchase_demand_model->get_all_demand($param);
                    }


                    $purchase_tax_list_export = $orders_export_info['data_list'];

                    $sku_list = array_column( $purchase_tax_list_export,"sku");
                    //获取缺货数量信息
                    $lack_map = $this->Shortage_model->get_lack_info($sku_list);
                    unset($sku_list);

                    $demand_number_list = array_column( $purchase_tax_list_export,"demand_number");
                    //采购单信息
                    $purchase_order_info_map=[];
                    if(!empty($demand_number_list)) {
                        $demand_number_list = array_unique($demand_number_list);
                        $purchase_order_info_map = $this->purchase_suggest_map_model->get_purchase_info_by_demand_number($demand_number_list);
                    }
                    unset($demand_number);

                    $tax_list_tmp = [];
                    $buyerIds =  array_unique(array_column($purchase_tax_list_export,"buyer_id"));
                    $buyerName = [];
                    if(!empty($buyerIds)) {
                        $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
                        $buyerName = array_column($buyerName, NULL, 'user_id');
                    }
                    if ($purchase_tax_list_export) {
                        $query = [
                            "getProductStatus"      => $getProductStatus,
                            "warehouse_list"        => $warehouse_list,
                            "logistics_type_list"   => $logistics_type_list,
                            "category_list"         => $category_list,
                            "buyerName"         => $buyerName,
                        ];
                        foreach ($purchase_tax_list_export as $value) {
                            $value_tmp = [];
                            $now_product_status                 = isset($getProductStatus[$value['product_status']])?$getProductStatus[$value['product_status']]:'';
                            $suggest_status = $value['suggest_status'];
                            if($suggest_status == ''){
                                $suggest_status = '无';
                            }else{
                                $suggest_status = getSuggestStatus($suggest_status);
                            }
                            $value_tmp['suggest_status']           = iconv("UTF-8", "GBK//IGNORE", $suggest_status);
                            $value_tmp['plan_product_arrive_time']            = iconv("UTF-8", "GBK//IGNORE",$value['plan_product_arrive_time']);
                            $value_tmp['es_shipment_time'] = $value['es_shipment_time'];
                            $shipment_type = $value['shipment_type'];
                            $value_tmp['shipment_type_ch']         = iconv("UTF-8", "GBK//IGNORE",($shipment_type == 1?"工厂发运": $shipment_type == 2?"中转仓发运":""));
                            $value_tmp['product_jump_url']         = iconv("UTF-8", "GBK//IGNORE",jump_url_product_base_info($value['sku']));
                            $purchase_type_id = getPurchaseType($value['purchase_type_id']);
                            $value_tmp['purchase_type_id']         = iconv("UTF-8", "GBK//IGNORE", (!empty($purchase_type_id) && !is_array($purchase_type_id) ? $purchase_type_id: ''));
                            $value_tmp['demand_number']            = iconv("UTF-8", "GBK//IGNORE",$value['demand_number']);
                            $value_tmp['sku']                      = iconv("UTF-8", "GBK//IGNORE",$value['sku']);
                            $value_tmp['is_overseas_first_order_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['is_overseas_first_order'] == 1?'是':'否');
                            $value_tmp['is_new']                   = iconv("UTF-8", "GBK//IGNORE",$value['is_new'] == 1 ? '是' : '否');
                            $value_tmp['product_line_name']        = iconv("UTF-8", "GBK//IGNORE",$value['product_line_name']);
                            $value_tmp['two_product_line_name']    = iconv("UTF-8", "GBK//IGNORE",$value['two_product_line_name']);
                            $value_tmp['product_name']             = iconv("UTF-8", "GBK//IGNORE",trim($value['product_name'],'"'));
                            $value_tmp['product_status']           = iconv("UTF-8", "GBK//IGNORE",$now_product_status);
                            $value_tmp['purchase_amount']          = iconv("UTF-8", "GBK//IGNORE",$list_type == 2 ?$value['purchase_amount']: $value['demand_data']);
                            $value_tmp['left_stock']               = iconv("UTF-8", "GBK//IGNORE",$value['left_stock']); //缺货数量
                            //缺货数量(新)
                            $value_tmp['new_lack_qty']             = iconv("UTF-8", "GBK//IGNORE",$lack_map[$value['sku']]['think_lack_qty']??0);
                            $value_tmp['left_stock_status']        = iconv("UTF-8", "GBK//IGNORE",intval($value_tmp['left_stock'])<0?'是':'否');
                            $value_tmp['starting_qty']             = iconv("UTF-8", "GBK//IGNORE",$value['starting_qty']);;
                            $value_tmp['starting_qty_unit']        = iconv("UTF-8", "GBK//IGNORE",$value['starting_qty_unit']);
                            $is_drawback = getIsDrawback($value['is_drawback']);
                            $value_tmp['is_drawback']              = iconv("UTF-8", "GBK//IGNORE", (!empty($is_drawback) && !is_array($is_drawback) ? $is_drawback: ''));
                            $value_tmp['ticketed_point']           = iconv("UTF-8", "GBK//IGNORE",$value['ticketed_point']);
                            $value_tmp['purchase_unit_price']      = iconv("UTF-8", "GBK//IGNORE",$value['purchase_unit_price']);
                            $value_tmp['purchase_total_price']     = iconv("UTF-8", "GBK//IGNORE",$value['purchase_total_price']);
                            $value_tmp['currency']                 = CURRENCY; //币种
                            $value_tmp['supplier_name']            = iconv("UTF-8", "GBK//IGNORE",$value['supplier_name']);
                            $value_tmp['is_cross_border']          = iconv("UTF-8", "GBK//IGNORE",$value['is_cross_border'] == 1 ? '是':'否');
                            $value_tmp['earliest_exhaust_date'] = iconv("UTF-8", "GBK//IGNORE",$value['earliest_exhaust_date']);
                            $value_tmp['warehouse_name']           = iconv("UTF-8", "GBK//IGNORE",$value['warehouse_name']);
                            $value_tmp['destination_warehouse']    = iconv("UTF-8", "GBK//IGNORE",isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '');   //目的仓
                            $value_tmp['logistics_type']           = iconv("UTF-8", "GBK//IGNORE",isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '');   //物流类型

                            $value_tmp['create_user_name']         = iconv("UTF-8", "GBK//IGNORE",$value['create_user_name']);   //申请人
                            $value_tmp['create_time']              = iconv("UTF-8", "GBK//IGNORE",$value['create_time']);   //申请时间
                            $value_tmp['buyer_name']               = iconv("UTF-8", "GBK//IGNORE",$value['buyer_name']);
                            $value_tmp['expiration_time']          = iconv("UTF-8", "GBK//IGNORE",$value['expiration_time']);
                            $value_tmp['audit_time']               = iconv("UTF-8", "GBK//IGNORE",$value['audit_time']);
                            // 获取 需求对应的采购单信息
                            $purchase_order_info = $purchase_order_info_map[$value['demand_number']]??[];

                            $value_tmp['purchase_number']       = iconv("UTF-8", "GBK//IGNORE", $purchase_order_info['purchase_number']??'');
                            $value_tmp['purchase_order_status'] = iconv("UTF-8", "GBK//IGNORE",$purchase_order_info['purchase_order_status']??'');
                            $purchase_order_status = getPurchaseStatus($value_tmp['purchase_order_status']);
                            $value_tmp['purchase_order_status'] = iconv("UTF-8", "GBK//IGNORE", (!empty($purchase_order_status) && !is_array($purchase_order_status) ? $purchase_order_status: ''));
                            $value_tmp['confirm_number']        = iconv("UTF-8", "GBK//IGNORE",$purchase_order_info['confirm_number']??'');


                            if ($value_tmp['confirm_number'] === ''){
                                $value_tmp['cancel_ctq'] = '';
                            }else{
                                $value_tmp['cancel_ctq']            = $value_tmp['purchase_amount'] - intval($value_tmp['confirm_number']);// 采购数量+未转在途数量=需求单数量
                                $value_tmp['cancel_ctq']            = $value_tmp['cancel_ctq']>0?$value_tmp['cancel_ctq']:0;
                            }

                            $sales_note = explode(' ',trim($value['sales_note']));
                            if (isset($sales_note[count($sales_note)-1]) && isset($sales_note[count($sales_note)-2]) && isset($sales_note[count($sales_note)-3])){
                                $sales_note_string = $sales_note[count($sales_note)-3].' '.$sales_note[count($sales_note)-2].' '.$sales_note[count($sales_note)-1];
                            }else{
                                $sales_note_string = isset($sales_note[0])?$sales_note[0]:'';
                            }

                            $value_tmp['sales_note']            = iconv("UTF-8", "GBK//IGNORE",$sales_note_string);

                            $crc = $value['cancel_reason_category'];
                            $value_tmp['cancel_reason']         = iconv("UTF-8", "GBK//IGNORE", $value['cancel_reason']);
                            $value_tmp['cancel_reason_category'] = iconv("UTF-8", "GBK//IGNORE",isset($category_list[$crc])?$category_list[$crc]:'');
                            $value_tmp['is_abnormal']           = iconv("UTF-8", "GBK//IGNORE",($value['is_abnormal'] == SUGGEST_ABNORMAL_TRUE) ? '是' : '否');
                            $value_tmp['platform']              = iconv("UTF-8", "GBK//IGNORE",$value['platform']);
                            $value_tmp['site']                  = iconv("UTF-8", "GBK//IGNORE",$value['site']);
                            $value_tmp['sales_group']           = iconv("UTF-8", "GBK//IGNORE",$value['sales_group']);
                            $value_tmp['sales_name']            = iconv("UTF-8", "GBK//IGNORE",$value['sales_name']);
                            $value_tmp['sales_account']         = iconv("UTF-8", "GBK//IGNORE",$value['sales_account']);
                            $value_tmp['sales_note2']            = iconv("UTF-8", "GBK//IGNORE",$value['sales_note2']);
                            $supply_status = getProductsupplystatus($value['supply_status']);
                            $value_tmp['supply_status']         = iconv("UTF-8", "GBK//IGNORE", !empty($supply_status) && !is_array($supply_status)?$supply_status: '');//货源状态
                            $is_boutique = getISBOUTIQUE($value['is_boutique']);
                            $value_tmp['is_boutique']           = iconv("UTF-8", "GBK//IGNORE", (!empty($is_boutique) && !is_array($is_boutique)? $is_boutique :""));//是否精品
                            $state_type = getProductStateType((int)$value['state_type']);
                            $value_tmp['state_type']            = iconv("UTF-8", "GBK//IGNORE", (!empty($state_type) && !is_array($state_type)? $state_type: ""));//开发类型
                            $value_tmp['is_entities_lock']      = iconv("UTF-8", "GBK//IGNORE",($value['lock_type'] == LOCK_SUGGEST_ENTITIES) ? '锁单中' : '未锁单');
                            $value_tmp['tax_rate']              = iconv("UTF-8", "GBK//IGNORE",empty($value['tax_rate'])?0:$value['tax_rate']);//退税率
                            $declare_unit = deleteProductData($value['declare_unit']);
                            $value_tmp['issuing_office'] = iconv("UTF-8", "GBK//IGNORE", !empty($declare_unit) && !is_array($declare_unit) ? $declare_unit : "");//开票单位
                            $value_tmp['groupName']                = iconv("UTF-8", "GBK//IGNORE",isset($buyerName[$value['buyer_id']])?$buyerName[$value['buyer_id']]['group_name']:'');

                            $value_tmp['is_purchasing_ch']                = iconv("UTF-8", "GBK//IGNORE",'');
                            $is_purchasing_ch = '';
                            if( isset($value['tis_purchasing'])){
                                $is_purchasing_ch = $value['tis_purchasing'] == 1 ? "否":"是";
                            }
                            $value_tmp['is_purchasing_ch']  = iconv("UTF-8", "GBK//IGNORE", $is_purchasing_ch);
                            $value_tmp['payment_method_source_ch'] = iconv("UTF-8", "GBK//IGNORE", ($value['source'] == 1 ? "合同":"网采"));
                            $value_tmp['delivery_time']       = iconv("UTF-8", "GBK//IGNORE", isset($value['delivery_time_estimate_time'])?$value['delivery_time_estimate_time']:'');

                            $value_tmp['demand_type']           = iconv("UTF-8", "GBK//IGNORE", $value['demand_name_id_cn']);
                            $value_tmp['is_merge']              = iconv("UTF-8", "GBK//IGNORE", ($value['is_merge'] == 1 ?"已合单":"正常"));
                            $value_tmp['suggest_demand']        = iconv("UTF-8", "GBK//IGNORE", $value['suggest_demand']);
                            $value_tmp['unsale_reason']         = iconv("UTF-8", "GBK//IGNORE", $value['unsale_reason']);

                            // '需求类型','合单状态','需求单号','停售原因'

                            // 需求单导出
                            if($list_type == 3){
                                unset($value_tmp['audit_time']);
                                unset($value_tmp['expiration_time']);
                                $de_demand_ty_id = getPurchaseType($value['de_demand_ty_id']);
                                $de_demand_ty_id = !is_string($de_demand_ty_id)?"":$de_demand_ty_id;
                                $value_tmp['demand_line_type']         = iconv("UTF-8", "GBK//IGNORE", $de_demand_ty_id);
                            }
                            fputcsv($fp, $value_tmp);

                        }
                        $cur_num = $i * $limit < $total ? $i * $limit: $total;
                        $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
                    }
                }
            }

            $file_data = $this->upload_file($reduced_file);

            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }


    /**
     * 备货单维度导出 2.备货单 3.需求单
     */
    private function suggest_export_data_head($type=2)
    {
        if($type==2){
            return ['备货单状态','预计到货时间','预计发货时间','发运类型','图片','备货单业务线','备货单号','SKU','是否海外首单',
                '是否新品','一级产品线','二级产品线','产品名称','产品状态','备货数量', '缺货数量','缺货数量(新)','是否缺货','最小起订量',
                '最小起订量单位', '是否退税','开票点','单价','总金额','币种','供应商','是否跨境','预计断货时间','采购仓库', '目的仓', '物流类型',
                '申请人', '创建日期', '采购员','过期时间','审核时间','关联采购单号','采购单状态','采购单数量','未转在途取消数量','备注',
                '作废原因','作废原因分类','是否异常','平台','站点','销售分组','销售名称',
                '销售账号','销售备注','货源状态','是否精品','开发类型','是否锁单中','退税率','开票单位','所属组别','是否代采','采购来源','预计供货时间',
                '需求类型','合单状态','需求单号','停售原因'
            ];
        }

        if($type==3){
            return ['备货单状态','预计到货时间','预计发货时间','发运类型','图片','备货单业务线','备货单号','SKU','是否海外首单',
                '是否新品','一级产品线','二级产品线','产品名称','产品状态','备货数量', '缺货数量','缺货数量(新)','是否缺货','最小起订量',
                '最小起订量单位', '是否退税','开票点','单价','总金额','币种','供应商','是否跨境','预计断货时间','采购仓库', '目的仓', '物流类型',
                '申请人', '创建日期', '采购员','关联采购单号','采购单状态','采购单数量','未转在途取消数量','备注',
                '作废原因','作废原因分类','是否异常','平台','站点','销售分组','销售名称',
                '销售账号','销售备注','货源状态','是否精品','开发类型','是否锁单中','退税率','开票单位','所属组别','是否代采','采购来源','预计供货时间',
                '需求类型','合单状态','需求单号','停售原因', '需求业务线'
            ];
        }
        return false;
    }





    /**
     * 退款数据导出SWOOLE 服务
     * @param 无
     * @author:luxu
     * @time:2021年1月16号
     **/
    public function refund_export(){
        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9518);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "处理异步任务中。\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/refund_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/refund_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_order_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * 退款数据导出
     * @param 无
     * @author:luxu
     * @time:2021年1月16号
     **/
    public function refund_export_data($id=1253){

        ini_set('max_execution_time','18000');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            $limit  = 2000;
            $total = $params['number'];
            $page = ceil($total/$limit);

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'refund' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $total = $par[0]['number'];
            $fp = fopen($reduced_file, "a+");
            $heads = [
                '退款状态','线下退款编号','供应商名称','供应商代码','采购单号','采购主体','合同号'
                ,'对账单号','申请人','申请人备注','退款类型','退款原因','退款渠道','退款时间','1688拍单号'
                ,'异常单号','退款金额','退款流水号','退货物流单号','轨迹状态','申请时间','收款时间','收款人','收款备注'
            ];

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);

            $x = 0;
            if($total>=1) {

                for ($i = 1; $i <= $page; $i++) {
                    $export_offset      = ($i - 1) * $limit;
                    $orders_export_info = $this->Offline_refund_model->get_offline_refund(json_decode($params['condition'],true),$limit,$export_offset);

                    $purchase_tax_list_export = $orders_export_info['values'];
                    if ($purchase_tax_list_export) {

                        foreach ($purchase_tax_list_export as $value) {
                            $value_tmp['refund_status_cn']     = iconv("UTF-8", "GBK//IGNORE", $value['refund_status_cn']);//退款状态
                            $value_tmp['refund_number']     = iconv("UTF-8", "GBK//IGNORE", $value['refund_number']);//退款编号
                            $value_tmp['supplier_name']       = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);//供应商中文
                            $value_tmp['supplier_code']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_code']);// 供应商编码
                            $value_tmp['purchase_number_multi_ch']       = iconv("UTF-8", "GBK//IGNORE", $value['purchase_number_multi']);//采购单号
                            $value_tmp['purchase_name_cn'] = iconv("UTF-8", "GBK//IGNORE", $value['purchase_name_cn']);//采购主体
                            $value_tmp['compact_number_multi']         = iconv("UTF-8", "GBK//IGNORE", $value['compact_number_multi']);//合同单号
                            $value_tmp['statement_number']                = iconv("UTF-8", "GBK//IGNORE", $value['statement_number']);
                            $value_tmp['apply_user_name']                = iconv("UTF-8", "GBK//IGNORE", $value['apply_user_name']);
                            $value_tmp['apply_notice']           = iconv("UTF-8", "GBK//IGNORE", $value['apply_notice']);//申请人备注
                            $value_tmp['refund_type']  = iconv("UTF-8", "GBK//IGNORE", $value['refund_type']);//退款类型
                            $value_tmp['refund_reason']      = iconv("UTF-8", "GBK//IGNORE", $value['refund_reason']);//退款原因
                            $value_tmp['refund_channel_cn']      = iconv("UTF-8", "GBK//IGNORE", $value['refund_channel_cn']);//退款渠道
                            $value_tmp['refund_time']      = iconv("UTF-8", "GBK//IGNORE", $value['refund_time']);//退款时间
                            $value_tmp['pai_number']      = iconv("UTF-8", "GBK//IGNORE", "'".$value['pai_number']);//1688拍单号
                            $value_tmp['abnormal_number']       = iconv("UTF-8", "GBK//IGNORE", $value['abnormal_number']);//异常单号
                            $value_tmp['refund_price']     = iconv("UTF-8", "GBK//IGNORE", $value['refund_price']);// 退款金额
                            if(!empty($value['refund_water_number'])) {
                                $value_tmp['refund_water_number'] = iconv("UTF-8", "GBK//IGNORE", "'" . $value['refund_water_number']);//退款流水号
                            }else{

                                $value_tmp['refund_water_number'] = iconv("UTF-8", "GBK//IGNORE", $value['refund_water_number']);//退款流水号
                            }

                            if(!empty($value['refund_log_number'])) {
                                $value_tmp['refund_log_number'] = iconv("UTF-8", "GBK//IGNORE", "'" . $value['refund_log_number']);//退货物流单号

                            }else{
                                $value_tmp['refund_log_number'] = iconv("UTF-8", "GBK//IGNORE", $value['refund_log_number']);//退货物流单号
                            }
                            $value_tmp['status']   = iconv("UTF-8", "GBK//IGNORE", $value['status']);//轨迹状态
                            $value_tmp['apply_time']        = iconv("UTF-8", "GBK//IGNORE", $value['apply_time']);//申请时间
                            $value_tmp['receipt_time']    = iconv("UTF-8", "GBK//IGNORE", $value['receipt_time']); //收款时间
                            $value_tmp['receipt_user_name']       = iconv("UTF-8", "GBK//IGNORE", $value['receipt_user_name']); //收款人
                            $value_tmp['receipt_notice']   = iconv("UTF-8", "GBK//IGNORE", isset($value['receipt_notice'])? $value['receipt_notice'] : ""); //收款备注
                            $tax_list_tmp = $value_tmp;

                            $x ++;
                            fputcsv($fp, $tax_list_tmp);
                        }

                        if ($i * $limit < $total) {
                            $cur_num = $i * $limit;
                        } else {
                            $cur_num = $total;
                        }
                        $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
                    }

                }
            }
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    public function abnormal()
    {
//tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 2024);
//开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "处理异步任务中。\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/abnormal_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/abnormal_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_order_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    public function abnormal_data($id = '1003'){

        ini_set('max_execution_time','18000');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            $page = 1;
            $limit  = 2000;
            $offsets = ($page - 1) * $limit;
            $this->load->model('system/Data_control_config_model');
            $data_list= $this->purchase_order_determine_model->get_cencel_list($params,0,1,1);
            $total = $data_list['page_data']['total'];

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'abnormal' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $total = $par[0]['number'];
            $fp = fopen($reduced_file, "a+");
            $heads = ['采购仓库','登记仓库','异常单号','备货单号','快递单号','异常货位','采购单号','SKU','是否代采','SKU数量','一级产品线','供应商代码','供应商名称','异常类型','次品类型','处理类型','采购员','处理人','创建人','创建时间','异常描述','采购处理时间','采购处理描述',
                '退货快递单号','轨迹状态','采购员是否处理','采购员处理结果','仓库处理结果','处理时效(h)','备注','所属组别','责任部门'];

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);

            $x = 0;
            if($total>=1) {
                $page_limit = $limit;

                for ($i = 1; $i <= ceil($total / $page_limit); $i++) {
                    $export_offset      = ($i - 1) * $page_limit;
                    $orders_export_info = $this->abnormal_model->get_abnormal_list_export(json_decode($params['condition'],true), $export_offset, $page_limit);
                    $purchase_tax_list_export = $orders_export_info['data_list']['value'];

                    if ($purchase_tax_list_export) {

                        foreach ($purchase_tax_list_export as $value) {
                            $value_tmp['warehouse_name']     = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_name']);//采购仓库
                            $value_tmp['handle_warehouse']     = iconv("UTF-8", "GBK//IGNORE", $value['handle_warehouse_name']);//登记仓库
                            $value_tmp['defective_id']       = iconv("UTF-8", "GBK//IGNORE", $value['defective_id']."\t");//异常单号
                            $value_tmp['demand_number']      = iconv("UTF-8", "GBK//IGNORE", $value['demand_number']."\t");
                            $value_tmp['express_code']       = iconv("UTF-8", "GBK//IGNORE", $value['express_code']."\t");//快递单号
                            $value_tmp['exception_position'] = iconv("UTF-8", "GBK//IGNORE", $value['exception_position']);//异常货位
                            $value_tmp['pur_number']         = iconv("UTF-8", "GBK//IGNORE", $value['pur_number']);//采购单号
                            $value_tmp['sku']                = iconv("UTF-8", "GBK//IGNORE", $value['sku']);
                            $value_tmp['is_purchasing']                = iconv("UTF-8", "GBK//IGNORE", $value['is_purchasing']);
                            $value_tmp['quantity']           = iconv("UTF-8", "GBK//IGNORE", $value['quantity']);//SKU数量
                            $value_tmp['product_line_name']  = iconv("UTF-8", "GBK//IGNORE", $value['product_line_name']);//一级产品线
                            $value_tmp['supplier_code']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_code']);//供应商名称
                            $value_tmp['supplier_name']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);//供应商名称
                            $value_tmp['abnormal_type']      = iconv("UTF-8", "GBK//IGNORE", $value['abnormal_type']);//异常类型
                            $value_tmp['defective_type']      = iconv("UTF-8", "GBK//IGNORE", $value['defective_type']);//次品类型
                            $value_tmp['handler_type2']       = iconv("UTF-8", "GBK//IGNORE", $value['handler_type']);//处理类型
//                        $value_tmp['defective_type']     = iconv("UTF-8", "GBK//IGNORE", $value['defective_type']);//次品类型

                            $value_tmp['buyer']              = iconv("UTF-8", "GBK//IGNORE", $value['buyer']);//采购员
                            $value_tmp['handler_person']     = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_person']) ? $value['handler_person']: "");//处理人
                            $value_tmp['create_user_name']   = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);//创建人
                            $value_tmp['create_time']        = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);//创建时间
                            $value_tmp['abnormal_depict']    = iconv("UTF-8", "GBK//IGNORE", $value['abnormal_depict']); //异常描述
                            $value_tmp['handler_time']       = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_time'])? $value['handler_time'] : ""); //采购处理时间
                            $value_tmp['handler_describe']   = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_describe'])? $value['handler_describe'] : ""); //采购处理描述
                            $value_tmp['express_no']         = iconv("UTF-8", "GBK//IGNORE", $value['return_express_no']."\t");//退货快递单号
                            $value_tmp['track_status']     = iconv("UTF-8", "GBK//IGNORE", $value['track_status']);//轨迹状态
                            $value_tmp['is_handler']         = iconv("UTF-8", "GBK//IGNORE", getAbnormalHandleResult($value['is_handler']));//是否处理
                            $value_tmp['handler_type']       = iconv("UTF-8", "GBK//IGNORE", empty($value['handler_type'])?"未处理":getAbnormalHandleType($value['handler_type']));//采购员处理类型
                            $value_tmp['warehouse_handler_result'] = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_handler_result']);//仓库处理结果
                            $value_tmp['deal_used']          = iconv("UTF-8", "GBK//IGNORE", $value['deal_used']);//处理时效
                            $value_tmp['note']               = iconv("UTF-8", "GBK//IGNORE", $value['note'].' '.$value['add_note_person'].' '.$value['add_note_time']);//备注
                            $value_tmp['groupname']          = iconv("UTF-8", "GBK//IGNORE", $value['groupName']);
                            $value_tmp['department_china_ch']  = iconv("UTF-8", "GBK//IGNORE", $value['department_china_ch']);
                            $tax_list_tmp = $value_tmp;

                            $x ++;
//                        if($x > $total)break; // 大于总查询量时，强行阻断
                            fputcsv($fp, $tax_list_tmp);
                        }

                        if ($i * $limit < $total) {
                            $cur_num = $i * $limit;
                        } else {
                            $cur_num = $total;
                        }
                        $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
                    }

                }
            }
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * 少数少款导出CSV
     * @param
     * @author:luxu
     * @time:2020年1月10号
     **/
    public function lack_export_data_csv($id=1243){

        ini_set('max_execution_time','18000');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);

            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'lack' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $total = $par[0]['number'];
            $fp = fopen($reduced_file, "a+");
            $heads = ['处理状态','少数少款类型','采购单号','备货单号','sku','采购数量','取消数量','报损数量','报损状态'

                ,'累计点数数量','采购员','最新处理人','最新处理时间','采购单状态','备货单状态','取消未到货状态'

                ,'供应商','供应商CODE','采购仓库','公共仓库','创建时间','更新时间','采购来源','结算方式'];

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);

            $x = 0;
            $limit = 200;
            if($total>=1) {
                $page_limit = $limit;

                for ($i = 1; $i <= ceil($total / $page_limit); $i++) {
                    $export_offset      = ($i - 1) * $page_limit;
                    $lackDatas = $this->purchase_order_lack_model->getLackData(json_decode($params['condition'],true),$export_offset,$limit);
                    $lackDatas_value = $lackDatas['list'];

                    if ($lackDatas_value) {
                        if( isset($condition['role_name']) && !empty($condition['role_name'])){
                            $lackDatas_value = ShieldingData($lackDatas_value,['supplier_name','supplier_code'],$condition['role_name'],NULL);
                        }
                        foreach ($lackDatas_value as $value) {

                            $v_value_tmp = [];

                            $v_value_tmp['processing_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['processing_ch']);
                            $v_value_tmp['type_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['type_ch']);
                            $v_value_tmp['purchase_number'] = iconv("UTF-8", "GBK//IGNORE",$value['purchase_number']);
                            $v_value_tmp['demand_number'] = iconv("UTF-8", "GBK//IGNORE",$value['demand_number']);
                            $v_value_tmp['sku'] = iconv("UTF-8", "GBK//IGNORE","'".$value['sku']);
                            $v_value_tmp['confirm_amount'] = iconv("UTF-8", "GBK//IGNORE",$value['confirm_amount']);
                            $v_value_tmp['cancel_number'] = iconv("UTF-8", "GBK//IGNORE",$value['cancel_number']);
                            $v_value_tmp['qty_number'] = iconv("UTF-8", "GBK//IGNORE",$value['qty_number']);
                            $v_value_tmp['loss_status_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['loss_status_ch']);
                            $v_value_tmp['actual_num'] = iconv("UTF-8", "GBK//IGNORE",$value['actual_num']);
                            $v_value_tmp['buyer_name'] = iconv("UTF-8", "GBK//IGNORE",$value['buyer_name']);
                            $v_value_tmp['person'] = iconv("UTF-8", "GBK//IGNORE",$value['person']);
                            $v_value_tmp['person_time'] = iconv("UTF-8", "GBK//IGNORE",$value['person_time']);
                            $v_value_tmp['purchase_status_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['purchase_status_ch']);
                            $v_value_tmp['demand_status_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['demand_status_ch']);
                            $v_value_tmp['cancel_status_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['cancel_status_ch']);
                            $v_value_tmp['supplier_name'] = iconv("UTF-8", "GBK//IGNORE",$value['supplier_name']);
                            $v_value_tmp['supplier_code'] = iconv("UTF-8", "GBK//IGNORE",$value['supplier_code']);
                            //warehouse_name
                            $v_value_tmp['warehouse_name'] = iconv("UTF-8", "GBK//IGNORE",$value['warehouse_name']);

                            $v_value_tmp['pertain_wms'] = iconv("UTF-8", "GBK//IGNORE",$value['pertain_wms']);
                            $v_value_tmp['add_time'] = iconv("UTF-8", "GBK//IGNORE",$value['add_time']);
                            $v_value_tmp['update_time'] = iconv("UTF-8", "GBK//IGNORE",$value['update_time']);
                            $v_value_tmp['source_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['source_ch']);
                            $v_value_tmp['settlement_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['settlement_ch']);
                            $tax_list_tmp = $v_value_tmp;
                            fputcsv($fp, $tax_list_tmp);
                        }

                        if ($i * $limit < $total) {
                            $cur_num = $i * $limit;
                        } else {
                            $cur_num = $total;
                        }
                        $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
                    }

                }
            }
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * 少数少款导出
     * @param
     * @author:luxu
     * @time:2020年1月10号
     **/

    public function lack_export_data($id){

        $par = $this->data_center_model->get_items("id = " . $id);
        $params = $par[0];
        return $this->lack_export_data_csv($id);
    }

    /**
     * 少数少款导出 SWOOLE
     * @param
     * @author:luxu
     * @time:2020年1月10号
     **/

    public function lack_export()
    {
        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9517);
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'lack_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/lack_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/lack_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');

            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();

    }

    public function purchase_order_export()
    {
//tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9503);
//开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "处理异步任务中。\n";
//            var_dump($data);
//            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_order_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_order_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_order_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }
    public function cancel_unarrived_goods_examine_down()
    {
//tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 2023);
//开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "处理异步任务中。\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/cancel_unarrived_goods_examine_down_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/cancel_unarrived_goods_examine_down_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_order_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }




    public function cancel_unarrived_goods_examine_down_data($id ='997'){
        ini_set('max_execution_time','18000');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            $page = 1;
            $limit  = 2000;
            $offsets = ($page - 1) * $limit;
            $this->load->model('system/Data_control_config_model');

            $data_list= $this->purchase_order_determine_model->get_cencel_list_server(json_decode($params['condition'],true),0,1,1);
            $total = $data_list['page_data']['total'];

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'unarrived' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $total = $par[0]['number'];
            $fp = fopen($reduced_file, "a+");
            $heads = ['申请编号','关联的报损编号','采购单号','sku','取消原因','30天取消次数',  '取消未到货状态','拍单号','取消数量','取消金额','取消运费','取消优惠','取消加工费','退款金额'
                ,'剩余需退款金额','1688退款金额','实际退款金额','申请人','申请时间','审核人','审核时间','退款流水号'
            ];
            foreach($heads as $key => $item) {
                $title[$key] = $item;
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);


            for($i=1;$i<=ceil($total/$limit);++$i){


                $condition['offset'] = $i;
                $condition['limit'] = $limit;
                $offset = ($i - 1) * $limit;
                $data_list= $this->purchase_order_determine_model->get_cencel_list_server(json_decode($params['condition'],true),$offset,$limit,$page);
                $tax_list_tmp = [];
                foreach ($data_list['values'] as $key => $value) {

                    $v_value_tmp = [];
                    $v_value_tmp['cancel_number'] = " ".$value['cancel_number'];
                    $v_value_tmp['relative_superior_number'] = $value['relative_superior_number'];
                    $v_value_tmp['purchase_numbers'] = $value['purchase_numbers'];
                    $v_value_tmp['sku'] = $value['sku'];
                    $v_value_tmp['cancel_reason'] = $value['cancel_reason'];
                    $v_value_tmp['cancel_times'] = $value['cancel_times'];
                    $v_value_tmp['audit_status'] = get_cancel_status($value['audit_status']);
                    $v_value_tmp['pai_numbers'] = $value['pai_numbers'];
                    $v_value_tmp['is_edit'] = $value['is_edit'];
                    $v_value_tmp['total_price'] = $value['total_price'];
                    $v_value_tmp['freight'] = $value['freight'];
                    $v_value_tmp['discount'] = $value['discount'];
                    $v_value_tmp['process_cost'] = $value['process_cost'];
                    $v_value_tmp['original_pay_price'] = $value['original_pay_price'];
                    $v_value_tmp['pay_price'] = $value['pay_price'];
                    $v_value_tmp['apply_amount'] = isset($value['apply_amount'])?$value['apply_amount']:'';
                    $v_value_tmp['real_refund_total'] = $value['real_refund_total'];
                    $v_value_tmp['create_user_name'] = $value['create_user_name'];
                    $v_value_tmp['create_time'] = $value['create_time'];
                    $v_value_tmp['audit_user_name'] = $value['audit_user_name'];
                    $v_value_tmp['audit_time'] = $value['audit_time'];
                    $v_value_tmp['serial_number'] = $value['serial_number'];
                    fputcsv($fp, $v_value_tmp);
                }


                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
            }
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }




    /**
     * 采购单导出
     * @param $id
     * @return mixed
     */
    public function download_order_csv($id)
    {
        ini_set('memory_limit', '1000M');
        //       $this->db->reconnect();
        $result = false;
//        $params = $this->data_center_model->getQueue();
        $use_mem = round(memory_get_usage() / 1024 / 1024, 2);
//        echo "\n id:" . $id . 'start_mem:' . $use_mem . "\n";
        $export_user = [];
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);

            if(!isset($par[0])){
                echo '没有数据处理';
                return $result;
            }
            $params = $par[0];
            if (isset($params['user_id'])) {
                $export_user = [
                    'user_id' => explode(',', $params['user_id']) ?? [],
                    'data_role' => explode(',', $params['role']) ?? [],
                    'role_name' => explode(',', $params['role_name']) ?? [],
                    'export_user_id' => $params['user_id'],// 导出操作人
                ];
            }
            $this->load->model('purchase/purchase_order_list_model');
            $param = json_decode($params['condition'], true);
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            $orders_info = $this->purchase_order_list_model->get_order_sum(json_decode($params['condition'], true), $offsets = 0, $limit = 2000, 1, True, True, $export_user);
            $use_mem = round(memory_get_usage() / 1024 / 1024, 2);
            echo 'start_sum_mem:' . $use_mem . "\n";
            $total = $orders_info['aggregate_data']['total_all'];
            echo 'total:' . $total . "\n";
            //前端路径
            $webfront_path = dirname(dirname(APPPATH));
            $template_file = 'purchase_order_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $product_file = $webfront_path . '/webfront/download_csv/' . $template_file;
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            $is_head = false;
            if ($total > 0) {
                $per_page = 500;
                //$total_page = ceil($orders_info['aggregate_data']['max_id']-$orders_info['aggregate_data']['min_id']/$per_page);
                $total_page = ceil($total / $per_page);
                $i = 0;

                // 获取配置表头
                $list_type = isset($param['list_type'])?$param['list_type']:1;
                $uid = $params['user_id'];
                $orders_key = $this->purchase_order_new_model->get_export_header($list_type, $uid);

                $table_columns_list = [];
                foreach ($orders_key as $k=>$v){
                    if($k == 'freight')$v = 'PO总运费';
                    if($k == 'discount')$v = 'PO总优惠额';
                    if($k == 'process_cost')$v = 'PO总加工费';
                    $table_columns_list[] = [
                        'key'       => $k,
                        'name'      => $v,
                    ];
                }
                do {
                    $redirectStdout = false;
                    $i++;
//            for ($i = 1; $i <= $total_page; ++$i) {
//                fopen($product_file, 'w');
                    $fp = fopen($product_file, "a+");
                    $offsets = ($i - 1) * $per_page;
                    $use_mem = round(memory_get_usage() / 1024 / 1024, 2);
                    echo 'start:' . $i * $per_page . ',mem_use:' . $use_mem . "\n";
                    $info = $this->purchase_order_list_model->new_get_list(json_decode($params['condition'], true), $offsets, $per_page, 1, True, false, $orders_info['aggregate_data']['min_id'], True, $export_user, $params['user_id']);
                    $info['key'] = $table_columns_list;
                    if(!empty($info['value'])){

                        foreach($info['value'] as $info_key=>$info_value){

                            foreach($info_value as $info_k=>$info_v){

                                if($info_k == 'is_overseas_first_order'){

                                    $info['value'][$info_key]['is_overseas_first_order_ch'] = $info_v;
                                }
                            }

                        }
                    }

                    if (count($info['value']) > 0) {
                        $redirectStdout = true;
                    }
                    if (!empty($info['value'])) {
                        foreach ($info['value'] as $key => $value) {
                            $info['value'][$key]['logistics_trajectory'] = '';
                            if (!empty($value['logistics_info'])) {
                                foreach ($value['logistics_info'] as $k => $v) {
                                    $info['value'][$key]['logistics_trajectory'] .= sprintf('%s-%s ', $v['cargo_company_id'] ?? '', $v['express_no'] ?? '');
                                }
                            }
                        }
                    }

                    $keys_info = $info['key'];
                    $keys_info[] =[

                        'key' => 'track_status_ch',
                        'name' => '轨迹状态'
                    ];

                    $keys_info[] =[

                        'key' => 'is_long_delivery_ch',
                        'name' => '是否超长交期'
                    ];

                    $keys_info[] =[

                        'key' => 'new_devliy',
                        'name' => '交期天数'
                    ];
                    $keys_info[] = [
                        'key'   => 'freight_pay',
                        'name'  => '已付运费',
                    ];
                    $keys_info[] = [
                        'key'   => 'discount_pay',
                        'name'  => '已付优惠额',
                    ];
                    $keys_info[] = [
                        'key'   => 'process_cost_pay',
                        'name'  => '已付加工费',
                    ];
                    $keys_info[] = [
                        'key'   => 'is_distribution',
                        'name'  => '是否分销',
                    ];
                    $keys_info[] = [
                        'key'   => 'quantity',
                        'name'  => '门户回货数',
                    ];
                    $keys_info[] = [
                        'key'   => 'freight',
                        'name'  => 'PO总运费',
                    ];
                    $keys_info[] = [
                        'key'   => 'discount',
                        'name'  => 'PO总优惠额',
                    ];
                    $keys_info[] = [
                        'key'   => 'process_cost',
                        'name'  => 'PO总加工费',
                    ];
                    $keys_info[] = [
                        'key'   => 'order_remark',
                        'name'  => '备注',
                    ];
                    $keys_info[] = [
                        'key'   => 'quantity_time',
                        'name'  => '门户回货时间',
                    ];
                    $keys = array();
                    foreach ($keys_info AS $key => $value) {

                        $keys[$value['key']] = $value['name'];
                        if ($value['key'] == 'sku') {
                            $keys['first_product_line'] = '一级产品线';//勾选了sku时,一级产品线也要导出
                            $keys['state_type'] = '开发类型';
                            $keys['product_weight'] = '样品包装重量';
//                            pr($keys);exit;
                        }
                        if ($value['key'] == 'pay_status') {
                            $keys['pay_finish_status'] = '付款完结状态';//勾选了付款状态时,付款完结状态也要导出
                        }
                        if ($value['key'] == 'amount_paid') {
                            $keys['ca_product_money'] = '抵扣商品额';
                            $keys['ca_process_cost'] = '抵扣加工费';
                        }
                        if($value['key'] == 'account_type' || $value['key'] == 'pay_type'){
                            $keys['settlement_ratio'] = '结算比例';
                        }
                        if($value['key'] == 'supplier_name'){
                            $keys['supplier_code'] = '供应商代码';
                        }
                    }
                    $keys_info[] =  [

                        'key' => 'groupname',
                        'name' => '采购小组'
                    ];


                    //Start：增加一些列数据导出
                    if (!in_array('is_distribution', $keys)) $keys['is_distribution'] = '是否分销';
                    if (!in_array('quantity', $keys)) $keys['quantity'] = '门户回货数';
                    if (!in_array('quantity_time', $keys)) $keys['quantity_time'] = '门户回货时间';
                    if (!in_array('purchase_type_id', $keys)) $keys['purchase_type_id'] = '业务线';
                    if (!in_array('is_oversea_boutique', $keys)) $keys['is_oversea_boutique'] = '是否海外精品';
                    if (!in_array('logistics_trajectory', $keys)) $keys['logistics_trajectory'] = '物流信息';
                    if (!in_array('is_entities_lock', $keys)) $keys['is_entities_lock'] = '是否锁单';
                    if (!in_array('supplier_status', $keys)) $keys['supplier_status'] = '供应商是否已禁用';
                    if (!in_array('is_ali_price_abnormal', $keys)) $keys['is_ali_price_abnormal'] = '金额异常';
                    if (!in_array('coupon_rate_price', $keys)) $keys['coupon_rate_price'] = '票面未税单价';
                    if (!in_array('coupon_rate', $keys)) $keys['coupon_rate'] = '票面税率';

                    if (!in_array('batch_note', $keys)) $keys['batch_note'] = '批量编辑备注';
                    if (!in_array('freight_note', $keys)) $keys['freight_note'] = '运费说明';
                    if (!in_array('purchase_note', $keys)) $keys['purchase_note'] = '采购经理审核驳回备注';
                    if (!in_array('message_note', $keys)) $keys['message_note'] = '信息修改审核备注';
                    if (!in_array('message_apply_note', $keys)) $keys['message_apply_note'] = '信息修改申请备注';
                    if (in_array('supplier_name', $keys)) {
                        if (!in_array('is_equal_sup_id', $keys)) $keys['is_equal_sup_id'] = '供应商ID是否一致';
                        if (!in_array('is_equal_sup_name', $keys)) $keys['is_equal_sup_name'] = '供应商名称是否一致';
                    }
                    if (!in_array('audit_time_status', $keys)) $keys['audit_time_status'] = '交期确认状态';
                    if ( !in_array('buyer_name',$keys)) $keys['groupname'] = '所属小组';

                    if ( !in_array('track_status_ch',$keys)) $keys['track_status_ch'] = '轨迹状态';
                    if (!in_array('order_remark',$keys)) $keys['order_remark'] = '备注';
                    if (!in_array('tap_date_str_sync',$keys)) $keys['tap_date_str_sync'] = '授信账期日期';
//
//                    if ( !in_array('is_long_delivery_ch_ch',$keys)) $keys['is_long_delivery_ch_ch'] = '是否超长交期';
//
//                    if ( !in_array('new_devliy_ch',$keys)) $keys['new_devliy_ch'] = '交期天数';
                    //End：增加一些列数据导出

                    //if(!in_array('pay_category_numbermessage_note',$keys)) $keys['pay_category_number'] = '请款运费请款单号';

                    $keyss = array_keys($keys);
                    $datalist = $data_values = $data_key = $heads = [];
                    $data = $info['value'];

                    if(!empty($data)) {
                        $buyerIds = array_unique(array_column($data, "buyer_name"));
                        if(!empty($buyerIds)){

                            $buyerIds = array_map(function($name){

                                return sprintf("'%s'",$name);
                            },$buyerIds);

                        }

                        $buyerName = $this->User_group_model->getNameGroupMessage($buyerIds);
                        $buyerName = array_column($buyerName, NULL, 'user_name');
                    }else{
                        $buyerName = [];
                    }


                    if ($data) {
                        foreach ($data as $row) {
                            try{ // 行容错
                                $row['purchase_type_id'] = getPurchaseType($row['purchase_type_id']);
                                $row['purchase_order_status'] = getPurchaseStatus($row['purchase_order_status']);
                                $row['suggest_order_status'] = getPurchaseStatus($row['suggest_order_status']);
                                $row['source'] = getPurchaseSource($row['source']);
                                $row['pay_status'] = $row['pay_status_name'];
                                $row['is_equal_sup_id'] = getEqualSupId($row['is_equal_sup_id']);
                                $row['is_equal_sup_name'] = getEqualSupName($row['is_equal_sup_name']);
                                $note_data = $this->purchase_order_model->get_note_list($row['purchase_number'], $row['sku']);

                                if (!empty($note_data)) {
                                    $row['batch_note'] = isset($note_data[0]) ? $note_data[0]['note'] : NULL;
                                    $row['freight_note'] = isset($note_data[1]) ? $note_data[1]['note'] : NULL;
                                    $row['purchase_note'] = isset($note_data[2]) ? $note_data[2]['note'] : NULL;
                                    $row['message_note'] = isset($note_data[3]) ? $note_data[3]['note'] : NULL;
                                    $row['message_apply_note'] = isset($note_data[4]) ? $note_data[4]['note'] : NULL;
                                } else {

                                    $row['batch_note'] = NULL;
                                    $row['freight_note'] = NULL;
                                    $row['purchase_note'] = NULL;
                                    $row['message_note'] = NULL;
                                    $row['message_apply_note'] = NULL;
                                }
                                foreach ($row as $key => $val) {
                                    if (in_array($key, $keyss)) {

                                        $data_key[$key] = $row[$key];
                                    }
                                    $data_key['is_long_delivery_ch'] = $row['is_long_delivery_ch'];
                                    $data_key['new_devliy'] = $row['new_devliy'];
                                    $data_key['track_status_ch'] = $row['track_status_ch'];
                                }
                                $datalist[] = $data_key;
                                unset($data_key);
                            }catch(Exception $e){}
                        }


                        foreach ($datalist as $key => $vv) {
                            $tagname = isset($vv['buyer_name'])?$vv['buyer_name']:'';

                            if (isset($vv['suggest_order_status']) && is_array($vv['suggest_order_status'])) {
                                $vv['suggest_order_status'] = NULL;
                            }

                            if (isset($vv['purchase_order_status']) && is_array($vv['purchase_order_status'])) {
                                $vv['purchase_order_status'] = NULL;
                            }

                            foreach ($vv as $k => $vvv) {

                                if ($key == 0 && !is_array($vvv)) {
                                    $heads[] = $keys[$k];
                                }


                                if (!is_array($vvv) && preg_match("/[\x7f-\xff]/", $vvv)) {
                                    $vv[$k] = stripslashes(iconv('UTF-8', 'GBK//IGNORE', $vvv));
                                }//中文转
                                if (!is_array($vvv) && $k != "purchase_price") {
                                    if (is_numeric($vvv) && strlen($vvv) > 9) $vv[$k] = $vvv . "\t";//避免大数字在csv里以科学计数法显示
                                }
                            }

                            if ($is_head === false) {
                                $heads[] = "采购小组";
//                                $heads[] = "是否超长交期";
//                                $heads[]= "交期天数";
//                                $heads[]= "物流轨迹";
                                foreach ($heads as &$m) {
                                    $m = iconv('UTF-8', 'GBK//IGNORE', $m);
                                }

                                fputcsv($fp, $heads);
                                $is_head = true;
                            }

                            $vv['groupname'] = iconv('UTF-8', 'GBK//IGNORE',isset($buyerName[$tagname])?$buyerName[$tagname]['group_name']:'');
                            //$vv['is_long_delivery_ch_ch']  =isset($vv['is_long_delivery_ch'])?$vv['is_long_delivery_ch']:'';
                            // $vv['new_devliy_ch']  = iconv('UTF-8', 'GBK//IGNORE',isset($vv['new_devliy'])?$vv['new_devliy']:'');
//                            $vv['track_status_ch_ch']  = isset($vv['track_status_ch'])?$vv['track_status_ch']:'';
                            fputcsv($fp, $vv);

                        }
//                    ob_flush();
//                    flush();
                        unset($datalist);
                        // 回写当前导出数据到pur_center_data 表
                        if ($i * $per_page < $total) {
                            $cur_num = $i * $per_page;
                        } else {
                            $cur_num = $total;
                        }
                        $result = $this->data_center_model->updateCenterData($params['id'], ['progress' => $cur_num]);
                        usleep(1000);

                    }
                    unset($fp);

                    unset($info);
                } while ($redirectStdout);
//            $zipFile ='purchase_order_'. date('YmdHi') . '.zip';
//            $zipRes = $this->zipFile($fileArr, $zipFile);
//            return $zipRes;
                //  上传文件到文件服务器
                $file_data = $this->upload_file($product_file);
                if (!empty($file_data['code'] == 200)) {
                    // 回写数据到pur_center_data 表
                    $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $template_file, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
                }
            }
        } else {
            echo '没有数据处理';
        }
//        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
//        $down_file_url = $down_host . 'download_csv/' . $template_file;
//        $this->success_json($down_file_url);
        return $result;

    }

    /**
     * 压缩文件
     * @param $sourceFile
     * @param $distFile
     * @return mixed
     */
//    public function zipFile($sourceFile, $distFile)
//    {
//        $zip = new \ZipArchive();
//        if ($zip->open($distFile, \ZipArchive::CREATE) !== true) {
//            return $sourceFile;
//        }
//
//        $zip->open($distFile, \ZipArchive::CREATE);
//        foreach ($sourceFile as $file) {
//            $fileContent = file_get_contents($file);
//            $file = iconv('utf-8', 'GBK', basename($file));
//            $zip->addFromString($file, $fileContent);
//        }
//        $zip->close();
//        return $distFile;
//    }
    /**
     * 订单跟踪导出脚本
     */
    public function purchase_progress_export()
    {

        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9504);
//开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_progress_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_progress_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * SKU 权均交期导出脚本
     */
    public function deliveryData()
    {

        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 2021);
//开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/deliverydata_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/deliverydata_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    public function deliverydata_csv($id = '905'){

        ini_set('memory_limit', '1200M');

        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            $page = 1;
            $limit  = 2000;
            $offsets = ($page - 1) * $limit;
            $field ='ware.purchase_type_id,p.product_status,p.is_customized,p.is_purchasing,d.id,d.sku,d.warehouse_code,d.avg_delivery_time,p.product_status,p.product_line_id,p.supplier_name';
            $total = $this->delivery->get_delivery_total($params, '', '',$field,true);

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'delivery' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $total = $par[0]['number'];
            $fp = fopen($reduced_file, "a+");
            $heads = ['SKU','产品状态','产品线','供应商', '权均交期（天）','目的中转仓库','权限交期-日志','是否定制','是否代采','业务线'];
            foreach($heads as $key => $item) {
                $title[$key] = $item;
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);

            $page = ceil($total/$limit);
            for($i=1;$i<=$page;++$i){


                $condition['offset'] = $i;
                $condition['limit'] = $limit;
                $offset = ($i - 1) * $limit;
                //  print_r($params['condition'e();]);di
                $orders_info = $this->delivery->get_delivery_list(json_decode($params['condition'],true), $offset, $limit,$field);


                $delivery_list = $orders_info['value'];

                $tax_list_tmp = [];
                $warehouse_code_arr = array_column($delivery_list, 'warehouse_code');
                $warehouse_list = $this->warehouse_model->get_code2name_list($warehouse_code_arr);

                if( isset($condition['role_name']) && !empty($condition['role_name'])){
                    $delivery_list = ShieldingData($delivery_list,['supplier_name','supplier_code'],$condition['role_name'],NULL);
                }
                foreach ($delivery_list as $key => $value) {

                    $linedata = $this->product_line_model->get_all_parent_category($value['product_line_id']);
                    $product_line_name = '';
                    if(isset($linedata[0])){
                        $product_line_name = $linedata[0]['product_line_name'];
                    }


                    $v_value_tmp = [];
                    $v_value_tmp['sku'] = " ".$value['sku'];
                    $v_value_tmp['product_status'] = !empty($value['product_status'])?getProductStatus($value['product_status']):'';
                    $v_value_tmp['product_line_id'] = $product_line_name;
                    $v_value_tmp['supplier_name'] = $value['supplier_name'];
                    $v_value_tmp['delivery_days'] = $value['avg_delivery_time'];
                    $v_value_tmp['warehouse_code'] = !empty($value['warehouse_code'])?$warehouse_list[$value['warehouse_code']]:'';
                    $EveryMonthData = $this->delivery->EveryMonth($value['sku'],$value['warehouse_code']);
                    $monthData = NULL;
                    if(!empty($EveryMonthData)){

                        foreach($EveryMonthData as $mKey=>$mValue ){

                            $monthData .= $mValue['month']." ".$mValue['deveily_days']."\r\n";
                        }
                    }
                    $v_value_tmp['monthData'] = $monthData;
                    // '是否定制','是否代采','业务线','产品状态'
                    //'SKU','产品状态','产品线','供应商', '权均交期（天）','目的中转仓库','权限交期-日志','是否定制','是否代采','业务线'
                    if($value['is_customized'] == 1){

                        $v_value_tmp['is_customized_ch'] = '是';
                    }else{
                        $v_value_tmp['is_customized_ch'] = '否';
                    }

                    if($value['is_purchasing'] == 1){

                        $v_value_tmp['is_purch_ch'] = '否';
                    }else{
                        $v_value_tmp['is_purch_ch'] = '是';
                    }

                    if( $value['purchase_type_id'] == PURCHASE_TYPE_INLAND){
                        $v_value_tmp['business_line_chs'] = '国内';
                    }

                    if( $value['purchase_type_id'] == PURCHASE_TYPE_OVERSEA){
                        $v_value_tmp['business_line_chs'] = '海外';
                    }

                    if( $value['purchase_type_id'] == PURCHASE_TYPE_FBA_BIG){
                        $v_value_tmp['business_line_chs'] = 'FBA大货';
                    }

                    if( $value['purchase_type_id'] == PURCHASE_TYPE_FBA){
                        $v_value_tmp['business_line_chs'] = 'FBA';
                    }

                    //getProductStatus
                    // $v_value_tmp['product_status'] =!empty($value['product_status'])?getProductStatus($value['product_status']):'';

                    fputcsv($fp, $v_value_tmp);
                }


                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
            }

            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }




    public function inventoryitems_export()
    {

        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9605);
//开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/inventoryitems_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/inventoryitems_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }




    /**
     * @function:对账冲账模块下载数据
     * @param:无
     * @author:luxu
     * @time:2020/7/30
     **/
    public function downStatement_export(){


        //tcp服务器
        echo "hello,,,,";
        $server = new \Swoole\Server(SWOOLE_SERVER, 2020);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        // SWOOLE 接受到客户端请求后，收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });

        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            print_r($data['swoole_type']);
            // 入库明细表导出
            if( isset($data['swoole_type']) && $data['swoole_type'] == 'INVENTORYITEMS') {

                $log_file = 'inventoryitems' . date('Ymd') . '.txt';
                echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/inventoryitems_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
                exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/inventoryitems_export_data ' . $data['id'] );
//            $this->download_progress_csv($data);
                $server->finish('完成处理 OK');
            }

            // 供应商余额表汇总

            if( isset($data['swoole_type']) && $data['swoole_type'] == 'BALANCE'){

                $log_file = 'balance_' . date('Ymd') . '.txt';
                print_r($data);
                //  echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/balance' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
                exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/balance ' . $data['id'] );
//            $this->download_progress_csv($data);
                $server->finish('完成处理 OK');

            }

            if( isset($data['swoole_type']) && $data['swoole_type'] == 'CHARGE') {

                $log_file = 'balance_' . date('Ymd') . '.txt';
                echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/charge_against' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
                exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/charge_against ' . $data['id'] );
//            $this->download_progress_csv($data);
                $server->finish('完成处理 OK');
            }

        });

        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();

    }


    /**
     * 采购单冲销汇总表导出
     **/
    public function charge_against($id = ''){

        ini_set('memory_limit', '1200M');

        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);

            $condition = json_decode($params['condition'], true);
            $condition['swoole'] = ['id' => $id];

            $total = $params['number'];

            $file_name = 'summary_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = get_export_path('Charge_against') . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }

            $columns = array(
                'purchase_number' => '采购单号', 'purchase_type_cn' => '业务线', 'source_cn' => '采购来源', 'compact_number' => '合同号', 'statement_number' => '对账单号', 'purchase_order_status_cn' => '订单状态', 'pay_status_cn' => '付款状态',
                'cancel_status_cn' => '取消状态', 'report_loss_status_cn' => '报损状态', 'supplier_name' => '供应商名称', 'supplier_code' => '供应商编码',
                'real_price' => '采购金额-总额', 'product_money' => '采购金额-商品总额', 'freight' => '采购金额-运费', 'process_cost' => '采购金额-加工费', 'discount' => '采购金额-优惠额', 'total_instock_price' => '入库金额',
                'paid_real_price' => '已付款金额-总额', 'paid_product_money' => '已付款金额-商品总额', 'paid_freight' => '已付款金额-运费', 'paid_process_cost' => '已付款金额-加工费', 'paid_discount' => '已付款金额-优惠额',
                'cancel_real_price' => '取消金额-总额', 'cancel_product_money' => '取消金额-商品总额', 'cancel_freight' => '取消金额-运费', 'cancel_process_cost' => '取消金额-加工费', 'cancel_discount' => '取消金额-优惠额',
                'real_refund_amount' => '退款金额', 'loss_real_price' => '报损金额-总额', 'loss_product_money' => '报损金额-商品总额', 'loss_freight' => '报损金额-运费', 'loss_process_cost' => '报损金额-加工费',
                'loss_discount' => '报损金额-优惠额', 'instock_price_after_charge_against' => '入库金额冲销后余额', 'real_price_after_charge_against' => '采购金额冲销后余额', 'buyer_name' => '采购员',
                'waiting_time' => '下单时间', 'finished_time' => '冲销完结时间', 'finished_cn' => '是否完结', 'groupName' => '所属组别'
            );


            $fp = fopen($reduced_file, "a");

            //将标题写到标准输出中
            fputcsv($fp, array_values($columns));

            $limit = 10000;
            $page = ceil($total / $limit);

            for ($i = 1; $i <= $page; ++$i) {

                $condition['offset'] = $i;
                $condition['limit'] = $limit;
                $offsets = ($i - 1) * $limit;

                $data = $this->Charge_against_surplus_model->get_summary_data_list($condition, $offsets, $limit, $page, true, 'get');
                $values = $data['values'];


                foreach ($values as $key => $value) {
                    // 批量注入
                    $v_value_tmp = [];
                    foreach ($columns as $col_key => $col_val) {
                        $v_value_tmp[$col_key] = isset($value[$col_key]) ? $value[$col_key] : '';
                        if(is_array($v_value_tmp[$col_key])){
                            $v_value_tmp[$col_key] = implode(',',$v_value_tmp[$col_key]);
                        }
                    }

                    fputcsv($fp, $v_value_tmp);
                }

                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);

            }
            // 上传文件到文件服务器
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'])  && ($file_data['code']== 200 )){
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['progress' => $total,'file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
        exit;
    }


    /**
     * function 入库明细表导出
     * @author:luxu
     * @time:2020/7/30
     **/
    public function inventoryitems_export_data($id = '23457'){
        ini_set('memory_limit', '1200M');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);

            $page = 1;
            $limit = 1;
            $offsets = ($page - 1) * $limit;
            $condition = json_decode($params['condition'], true);
            $condition['swoole'] = ['id'=>$id];

            //$data = $this->Purchase_inventory_items_model->get_data_list($condition, $offsets, $limit, $page, true, True);
            $total = $params['number'];


            $columns = array(
                'sequence' => '序号', 'product_name' => '产品名称', 'instock_batch' => '入库批次',
                'deliery_batch' => '发货批次号',
                'instock_date' => '入库日期', 'audit_time' => '审核日期', 'real_confirm_amount' => '下单数量',
                'instock_price' => '入库金额', 'instock_qty' => '入库数量', 'instock_qty_more' => '多货数量',
                'defective_num' => '次品数量', 'warehouse_cn' => '采购仓库',
                'purchase_number' => '采购订单', 'sku' => 'SKU', 'product_line_name' => '产品线', 'compact_number' => '合同号',
                'purchase_name' => '采购主体', 'is_purchasing' => '是否代采', 'is_drawback_cn' => '是否退税', 'supplier_name' => '供应商名称',
                'supplier_code' => '供应商代码', 'purchase_unit_price' => '采购单价',
                'currency_code' => '币种', 'coupon_rate' => '票面税率', 'buyer_name' => '采购员', 'instock_user_name' => '仓库操作人',
                'purchase_type_cn' => '采购单业务线',
                'pay_type_cn' => '支付方式', 'settlement_type_cn' => '结算方式', 'suggest_order_status_cn' => '备货单状态',
                'purchase_order_status_cn' => '订单状态',
                'is_paste_cn' => '是否承诺贴码', 'paste_labeled_cn' => '是否实际贴码', 'instock_type'=>'入库类型','statement_number' => '对账单号',
                'pay_status_cn' => '采购单付款状态',
                'charge_against_status_cn' => '冲销状态', 'surplus_charge_against_amount' => '剩余可冲销金额',
                'po_surplus_aca_amount' => 'PO剩余可冲销金额',
                'remark' => '备注','groupName' => '所属小组','product_weight' => 'SKU重量(g)',
                'demand_purchase_type_cn' => '备货单业务线',
                'demand_number' => '备货单号',
                'is_oversea_boutique' => '是否海外精品',
                'need_pay_time' => '应付款时间',
                'instock_month' => '入库月份',
                'create_time' => '推送时间',
            );
            //网采单，去除合同号字段
            if(!isset($condition['source']))$condition['source'] = 2; // 如不设置，默认网采
            if (SOURCE_NETWORK_ORDER == $condition['source']) {
                unset($columns['compact_number']);
            }
            //创建导出类对象
            $my_export = new Export();
            $file_name = 'HX_inventory_items_' . date('YmdHis'). mt_rand(1000, 9999) . '.csv';

            $heads= array_values($columns);

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'inventoryitems' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $fp = fopen($reduced_file, "a+");
            foreach($heads as $key => $item) {
                $title[$key] = $item;
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            $limit = 1000;
            $page = ceil($total/$limit);
            $tagi =1;
            for($i=1;$i<=$page;++$i){


                $condition['offset'] = $i;
                $condition['limit'] = $limit;
                $offset = ($i - 1) * $limit;
                $orders_info = $this->Purchase_inventory_items_model->get_data_list($condition,$offset,$limit);
                $delivery_list = $orders_info['values'];

                $tax_list_tmp = [];

                if( isset($condition['role_name']) && !empty($condition['role_name'])){
                    $delivery_list = ShieldingData($delivery_list,['supplier_name','supplier_code'],$condition['role_name'],NULL);
                }
                foreach ($delivery_list as $key => $value) {


                    $v_value_tmp = [];
                    $v_value_tmp['sequence'] = " ".$tagi++;
                    $v_value_tmp['product_name']  =  !empty($value['product_name'])?stripslashes($value['product_name']):'';
                    $v_value_tmp['instock_batch'] =  isset($value['instock_batch'])?$value['instock_batch']:'';
                    $v_value_tmp['deliery_batch'] =  isset($value['deliery_batch'])?$value['deliery_batch']:'';
                    $v_value_tmp['instock_date']  =  isset($value['instock_date'])?$value['instock_date']:'';
                    $v_value_tmp['audit_time']    =  isset($value['audit_time'])?$value['audit_time']:'';
                    $v_value_tmp['real_confirm_amount'] = isset($value['real_confirm_amount'])?$value['real_confirm_amount']:'';
                    $v_value_tmp['instock_price'] =  isset($value['instock_price'])?$value['instock_price']:'';
                    $v_value_tmp['instock_qty']   =  isset($value['instock_qty'])?$value['instock_qty']:'';
                    $v_value_tmp['instock_qty_more'] = isset($value['instock_qty_more'])?$value['instock_qty_more']:'';
                    $v_value_tmp['defective_num']  = isset($value['defective_num'])?$value['defective_num']:'';
                    $v_value_tmp['warehouse_cn'] = isset($value['warehouse_cn'])?$value['warehouse_cn']:'';
                    $v_value_tmp['purchase_number'] = isset($value['purchase_number'])?$value['purchase_number']:'';
                    $v_value_tmp['sku'] = isset($value['sku'])?$value['sku']:'';
                    $v_value_tmp['product_line_name'] = isset($value['product_line_name'])?$value['product_line_name']:'';
                    if (SOURCE_NETWORK_ORDER != $condition['source']) {
                        $v_value_tmp['compact_number'] = isset($value['compact_number'])?$value['compact_number']:'';
                    }

                    $v_value_tmp['purchase_name'] = isset($value['purchase_name'])?$value['purchase_name']:'';
                    $v_value_tmp['is_purchasing'] = isset($value['is_purchasing'])?$value['is_purchasing']:'';
                    $v_value_tmp['is_drawback_cn'] = isset($value['is_drawback_cn'])?$value['is_drawback_cn']:'';
                    $v_value_tmp['supplier_name'] = isset($value['supplier_name'])?$value['supplier_name']:'';
                    $v_value_tmp['supplier_code'] = isset($value['supplier_code'])?$value['supplier_code']:'';
                    $v_value_tmp['purchase_unit_price'] = isset($value['purchase_unit_price'])?$value['purchase_unit_price']:'';
                    $v_value_tmp['currency_code'] = isset($value['currency_code'])?$value['currency_code']:'';
                    $v_value_tmp['coupon_rate'] = isset($value['coupon_rate'])?$value['coupon_rate']:'';
                    $v_value_tmp['buyer_name'] = isset($value['buyer_name'])?$value['buyer_name']:'';
                    $v_value_tmp['instock_user_name'] = isset($value['instock_user_name'])?$value['instock_user_name']:'';
                    $v_value_tmp['purchase_type_cn'] = isset($value['purchase_type_cn'])?$value['purchase_type_cn']:'';
                    $v_value_tmp['pay_type_cn'] = isset($value['pay_type_cn'])?$value['pay_type_cn']:'';
                    $v_value_tmp['settlement_type_cn'] = isset($value['settlement_type_cn'])?$value['settlement_type_cn']:'';
                    $v_value_tmp['suggest_order_status_cn'] = isset($value['suggest_order_status_cn'])?$value['suggest_order_status_cn']:'';
                    $v_value_tmp['purchase_order_status_cn'] = isset($value['purchase_order_status_cn'])?$value['purchase_order_status_cn']:'';
                    $v_value_tmp['is_paste_cn'] = isset($value['is_paste_cn'])?$value['is_paste_cn']:'';
                    $v_value_tmp['paste_labeled_cn'] = isset($value['paste_labeled_cn'])?$value['paste_labeled_cn']:'';
                    $v_value_tmp['instock_type'] = isset($value['instock_type'])?$value['instock_type']:'';
                    $v_value_tmp['statement_number'] = isset($value['statement_number'])?$value['statement_number']:'';
                    $v_value_tmp['pay_status_cn'] = isset($value['pay_status_cn'])?$value['pay_status_cn']:'';
                    $v_value_tmp['charge_against_status_cn'] = isset($value['charge_against_status_cn'])?$value['charge_against_status_cn']:'';
                    $v_value_tmp['surplus_charge_against_amount'] = isset($value['surplus_charge_against_amount'])?$value['surplus_charge_against_amount']:'';
                    $v_value_tmp['po_surplus_aca_amount'] = isset($value['po_surplus_aca_amount'])?$value['po_surplus_aca_amount']:'';
                    $v_value_tmp['remark'] = isset($value['remark'])?implode(",",$value['remark']):'';
                    $v_value_tmp['groupName'] = isset($value['groupName'])?$value['groupName']:'';
                    $v_value_tmp['product_weight'] = isset($value['product_weight'])?$value['product_weight']:'';
                    $v_value_tmp['demand_purchase_type_cn'] = isset($value['demand_purchase_type_cn'])?$value['demand_purchase_type_cn']:'';
                    $v_value_tmp['demand_number'] = isset($value['demand_number'])?$value['demand_number']:'';
                    $v_value_tmp['is_oversea_boutique'] = isset($value['is_oversea_boutique'])?$value['is_oversea_boutique']:'';
                    $v_value_tmp['need_pay_time'] = isset($value['need_pay_time'])?$value['need_pay_time']:'';
                    $v_value_tmp['instock_month'] = isset($value['instock_month'])?$value['instock_month']:'';
                    $v_value_tmp['create_time'] = isset($value['create_time'])?$value['create_time']:'';
                    fputcsv($fp, $v_value_tmp);
                }
                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);

            }
            // $down_path = $my_export->ExportCsv($file_name, $total, $columns, $condition, $this->Purchase_inventory_items_model, 'get_data_list',$this->data_center_model);
            // 上传文件到文件服务器
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'])  && ($file_data['code']== 200 )){
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['progress' => $total,'file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }


        }
    }

    public function balance($id=''){

        ini_set('memory_limit', '1200M');

        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);

            $condition = json_decode($params['condition'], true);
            $page = 1;
            $limit = 1;
            $offsets = ($page - 1) * $limit;
            $data_list = $this->balance->supplier_balance_export($condition,$offsets, $limit,$page,$condition['date_type']);
            $template_file = 'supplier_balance'.date('YmdHis').mt_rand(1000,9999).'.csv';
            $product_file = get_export_path('supplier_balance').$template_file;
            if (file_exists($product_file)) {
                unlink($product_file);
            }

            fopen($product_file,'w');
            $is_head = false;
            $fp = fopen($product_file, "a");
            $total = $data_list['paging_data']['total'];
            if($total > 0){
                $per_page = 1000;
                $total_page = ceil($total/$per_page);
                for($i = 1;$i<= $total_page;$i++){
                    $offsets = ($i - 1) * $per_page;
                    $info =$this->balance->supplier_balance_export($condition,$offsets, $per_page,$page,$condition['date_type']);//导出文件
                    if(!empty($info['values'])){
                        foreach ($info['values'] as $key =>$value) {
                            $row=[
                                $value['supplier_code'],
                                $value['supplier_name'],//供应商名称
                                $value['purchase_name'],
                                $value['occurrence_date'],//发生时间
                                $value['opening_balance'],//期初余额
                                $value['payable_current'],//本期应付
                                $value['payable_price'],//本期已付
                                $value['refund_amount'],//退款金额
                                $value['balance_price'],//本期期末余额
                            ];

                            foreach ($row as $vvv) {
                                if(preg_match("/[\x7f-\xff]/",$vvv)){
                                    $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//中文转码
                                }
                                if(is_numeric($vvv) && strlen($vvv) > 9){
                                    $vvv =  $vvv."\t";//避免大数字在csv里以科学计数法显示
                                }
                                $row_list[]=$vvv;
                            }
                            if($is_head === false){
                                $heads =['供应商','供应商名称','采购主体','发生时间','期初余额','本期应付','本期已付','退款金额','本期期末余额'];
                                foreach($heads as &$m){
                                    $m = iconv('UTF-8','GBK//IGNORE',$m);
                                }
                                fputcsv($fp,$heads);
                                $is_head = true;
                            }
                            fputcsv($fp,$row_list);
                            unset($row_list);
                            unset($row);
                        }
                        if(ob_get_level()>0) {
                            ob_flush();//刷新一下输出buffer，防止由于数据过多造成问题
                            flush();
                        }
                        usleep(100);
                    }
                    $pagelimit =$i* $per_page;
                    if ( $i* $per_page < (int)$total) {
                        $cur_num = $i * $per_page;
                    } else {
                        $cur_num = $total;
                    }
                    $result = $this->data_center_model->updateCenterData($params['id'], ['progress' => $cur_num]);
                }
            }

        }
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url =  $product_file;
        $file_data = $this->upload_file($down_file_url);
        if (!empty($file_data['code']) && $file_data['code'] == 200) {
            // 回写数据到pur_center_data 表
            $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $template_file, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
        }

    }




    public function download_progress_csv($id = '16013')
    {
        ini_set('memory_limit', '1200M');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $condition = json_decode($params['condition'], true);
            echo "start";
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
            if (isset($params['user_id'])) {
                $export_user = [
                    'user_id' => explode(',', $params['user_id']) ?? [],
                    'data_role' => explode(',', $params['role']) ?? [],
                    'role_name' => explode(',', $params['role_name']) ?? [],
                    'export_user_id' => $params['user_id'],
                ];
            }
            $total = $this->purchase_order_model->get_purchase_total($condition, $export_user);
            echo "total:" . $total . "\n" ;
            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'purchase_progress_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $product_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($product_file)) {
                unlink($product_file);
            }
//        fopen($product_file, 'w');
            $fp = fopen($product_file, "a+");
            $heads = ['序号',
                '是否新品',
                '发货省份',
                '图片',
                '备货单号',
                '在途异常',
                '备货单业务线',
                '订单状态',
                '业务线',
                '供应商',
                '采购单号',
                'SKU',
                '产品名称',
                '采购数量',
                '入库数量',
                '内部采购在途',
                '仓库采购在途',
                '未到货数量',
                '跟进进度',
                '采购员',
                '跟单员',
                '审核时间',
                '预计到货日期',
                '到货日期',
                '入库日期',
                '入库时效（h）',
                '逾期天数',
                '拍单号',
                '采购仓库',
                '跟进日期',
                '备注说明',
                '请款时间',
                '付款时间',
                '付款时效(h)',
                '采购来源',
                '产品线',
                '产品状态', '缺货数量(新)',
                '1688异常', '近7天销量',
                '物流公司',
                '快递单号',
                '轨迹状态',
                '发货批次号',
                '异常类型',
                '所属小组',
                '需求类型',
                '合单状态',
                '取消原因',
                '取消未到货次数'];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            fputcsv($fp, $title);
            $limit = 500;
            $page = ceil($total / $limit);
            $numi = 0;
            $i = 1;

            // 订单追踪导出修改

            for ($i = 1; $i <= $page; $i++) {
                $offsets = ($i - 1) * $page;
                $condition['limit'] = $limit;
                $condition['page'] = $offsets;
                $condition['swoole'] = true;
                $result = $this->purchase_order_model->get_purchase_progress($condition, False, []);

                if (isset($result['list']) && !empty($result['list'])) {

                    if( isset($condition['role_name']) && !empty($condition['role_name'])){
                        $result['list'] = $results = ShieldingData($result['list'],['supplier_name','supplier_code'],$condition['role_name'],NULL);
                    }

                    if( isset($condition['role_name']) && !empty($condition['role_name'])){
                        $result['list'] = ShieldingData($result['list'],['supplier_name','supplier_code'],$condition['role_name'],NULL);
                    }
                    foreach ($result['list'] as $key => $v_value) {
                        $v_value_tmp = [];
                        $v_value_tmp['id'] = ++$numi;
                        $v_value_tmp['is_new_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['is_new_ch']);
                        $v_value_tmp['provinces'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['provinces']);

                        $v_value_tmp['product_img'] = isset($v_value['product_img']) ? $v_value['product_img'] : NULL;
                        $v_value_tmp['demand_number'] = $v_value['demand_number'];
                        $v_value_tmp['on_way_abnormal_cn'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['on_way_abnormal_cn']);
                        $v_value_tmp['demand_purchase_type_id'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['demand_purchase_type_id']?getPurchaseType($v_value['demand_purchase_type_id']):'');
                        $v_value_tmp['purchase_status_ch'] = iconv('UTF-8', 'GBK//IGNORE', getPurchaseStatus($v_value['purchase_status']));
                        $v_value_tmp['purchase_type'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['purchase_type']?getPurchaseType($v_value['purchase_type']):'');
                        $v_value_tmp['supplier_name'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['supplier_name']);
                        $v_value_tmp['purchase_number'] = $v_value['purchase_number'];
                        $v_value_tmp['sku'] = $v_value['sku'];
                        $v_value_tmp['product_name'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['product_name']);
                        $v_value_tmp['purchase_num'] = $v_value['purchase_num'];
                        $instock_qty = $this->Purchase_order_progress_model->get_purchase_sku_instock_qty($v_value['purchase_number'], $v_value['sku']);
                        $v_value_tmp['instock_number'] = $instock_qty['instock_qty'];
                        $v_value_tmp['purchase_on_way_num'] = $v_value['purchase_on_way_num'];
                        $v_value_tmp['warehouse_on_way_num'] = $v_value['warehouse_on_way_num'];
                        $v_value_tmp['no_instock_date'] = $v_value['no_instock_date'];
                        $v_value_tmp['progres'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['progres']);
                        $v_value_tmp['buyer_name'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['buyer_name']);
                        $v_value_tmp['documentary'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['documentary_name']);
                        $v_value_tmp['create_time'] = $v_value['create_time'];
                        $v_value_tmp['estimate_time'] = $v_value['estimate_time'];
                        $v_value_tmp['arrival_date'] = $v_value['arrival_date'];
                        $v_value_tmp['instock_date'] = $v_value['instock_date'];
                        if (empty($v_value['instock_date']) || empty($v_value['arrival_date'])) {

                            $v_value['storage'] = NULL;
                        } else {
                            $storage = $this->purchase_order_model->timediff(strtotime($v_value['instock_date']), strtotime($v_value['arrival_date']));
                            $v_value['storage'] = $storage['day'] * 24 + $storage['hour'];
                        }
                        $v_value_tmp['storage'] = $v_value['storage'];

                        $v_value_tmp['storageday'] = $v_value['storageday'];
                        $v_value_tmp['pai_number'] = $v_value['pai_number'] . "\t";
                        $v_value['warehourse'] = isset($warehouseResult[$v_value['warehouse_code']]) ? $warehouseResult[$v_value['warehouse_code']]['warehouse_name'] : NULL;
                        $v_value_tmp['warehourse'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['warehourse']);
                        $v_value_tmp['documentary_time'] = $v_value['documentary_time'];
                        $v_value_tmp['remark'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['remark']);
                        $v_value_tmp['application_time'] = $v_value['application_time'];
                        $v_value_tmp['payer_time'] = $v_value['payer_time'];
                        if (empty($v_value['payer_time']) || $v_value['payer_time'] == "0000-00-00 00:00:00") {

                            $v_value['payer_h'] = NULL;
                        } else {
                            $hours = $this->purchase_order_model->timediff(strtotime($v_value['payer_time']), strtotime($v_value['application_time']));
                            $v_value['payer_h'] = $hours['day'] * 24 + $hours['hour'];
                        }
                        $v_value_tmp['payer_h'] = $v_value['payer_h'];
                        switch ($v_value['source']) {

                            case 1:
                                $v_value['source_ch'] = "合同";
                                break;
                            case 2:
                                $v_value['source_ch'] = "网采";
                                break;
                            case 3:
                                $v_value['source_ch'] = "账期采购";
                                break;
                            default:
                                $v_value['source_ch'] = "未知";
                                break;
                        }
                        $v_value_tmp['source_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['source_ch']);
                        $v_value_tmp['product_line_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['product_line_ch']);
                        $v_value['product_status_ch'] = $this->purchase_order_model->get_productStatus($v_value['product_status']);
                        $v_value_tmp['product_status_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['product_status_ch']);
                        $v_value_tmp['stock_owes'] = $v_value['stock_owes'];
                        if ($v_value['ali_order_status'] == 0) {

                            $v_value['ali_order_status_ch'] = "否";
                        } else {
                            $v_value['ali_order_status_ch'] = "是";
                        }
                        $v_value_tmp['ali_order_status_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['ali_order_status_ch']);
                        if( isset($condition['is_sales']) && $condition['is_sales'] ==1){
                            $v_value_tmp['sevensale'] = iconv('UTF-8', 'GBK//IGNORE', "****");
                        }else{
                            $v_value_tmp['sevensale'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['sevensale']);
                        }

                        //快递公司 快递单号 轨迹状态
                        if (isset($v_value['logistics_info']) && is_array($v_value['logistics_info']) && !empty($v_value['logistics_info'])) {
                            $cargo_company_id = array_column($v_value['logistics_info'], 'cargo_company_id');
                            $express_no = array_column($v_value['logistics_info'], 'express_no');
                            $logistics_status = array_column($v_value['logistics_info'], 'logistics_status_cn');
                            $batch_no = array_column($v_value['logistics_info'],'batch_no');
                            $cargo_company_id = implode(' ', $cargo_company_id);
                            $express_no = implode(' ', $express_no);
                            $logistics_status = implode(' ', $logistics_status);
                            $batch_no = empty($batch_no) ? '' : implode(' ',$batch_no);
                            $v_value_tmp['logistics_company'] = iconv('UTF-8', 'GBK//IGNORE', $cargo_company_id);
                            $v_value_tmp['courier_number'] = $express_no . "\t";
                            $v_value_tmp['logistics_status'] = iconv('UTF-8', 'GBK//IGNORE', $logistics_status);
                            $v_value_tmp['batch_no'] = $batch_no."\t";
                        } else {
                            $v_value_tmp['logistics_company'] = '';
                            $v_value_tmp['courier_number'] = '';
                            $v_value_tmp['logistics_status'] = '';
                            $v_value_tmp['batch_no'] = '';
                        }

                        //异常类型
                        $abnormal_type_arr = explode(',', $v_value['abnormal_type_cn']);
                        $abnormal_type_tmp = array();
                        foreach ($abnormal_type_arr as $item) {
                            $abnormal_type_tmp[] = iconv('UTF-8', 'GBK//IGNORE', $item);
                    }
                    $v_value_tmp['abnormal_type'] = implode(',', $abnormal_type_tmp);
                    //快递公司 快递单号 $v_value
                    $v_value_tmp['groupName']                =   iconv('UTF-8', 'GBK//IGNORE', isset($v_value['groupName'])?$v_value['groupName']:'');
                        $v_value_tmp['dddemand_number'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['dddemand_number'])?$v_value['dddemand_number']:'');
                        $v_value_tmp['demand_types'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['demand_types'])?$v_value['demand_types']:'');
                        $v_value_tmp['is_merge'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['is_merge'])?$v_value['is_merge']:'');
                        $v_value_tmp['cancel_reason_ch'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['cancel_reason_ch'])?$v_value['cancel_reason_ch']:'');
                        $v_value_tmp['cancel_total'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['cancel_total'])?$v_value['cancel_total']:'');
                    $tax_value_temp = $v_value_tmp;
                    fputcsv($fp, $tax_value_temp);
                    }

                    // 回写数据到pur_center_data 表
                    if ($i * $limit < $total) {
                        $cur_num = $i * $limit;
                    } else {
                        $cur_num = $total;
                    }
                    $result = $this->data_center_model->updateCenterData($params['id'], ['progress' => $cur_num]);
                }else{
                    break;
                }
            }

            $file_data = $this->upload_file($product_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * SKU 降本数据导出SWOOLE 服务
     * @author :luxu
     * @time:2020/5/23
     **/
    public function purchase_reduced_export(){

        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9510);
//开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'product_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/purchase_reduced_export_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/purchase_reduced_export_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    public function get_mongdb_key($clientData)
    {
        $filter = [];
        // 如果传入优化人
        if( isset($clientData['person_name']) && !empty($clientData['person_name']))
        {
            $filter['person_name'] ="{$clientData['person_name']}";
        }

        // 如果传入ID

        if( isset($clientData['ids']) && !empty($clientData['ids']))
        {
            $filter['id'] = array('$in'=>$clientData['ids']);

        }

        // 传入供应商

        if( isset($clientData['supplier_code']) && !empty($clientData['supplier_code']))
        {
            $filter['supplier_code'] = "{$clientData['supplier_code']}";
        }
        // 传入SKU
        if( isset( $clientData['sku']) && !empty($clientData['sku']) )
        {
            if (count($clientData['sku']) == 1 && !empty($clientData['sku'][0])) {  //单个sku时使用模糊搜索
                $filter['sku'] = $clientData['sku'][0];
            } else if(count($clientData['sku']) > 1) {
                $filter['sku'] = array('$in'=>$clientData['sku']);
            }
        }
        // 传入价格变化的开始时间
        if( (isset($clientData['price_change_end_time']) && !empty($clientData['price_change_end_time'])) &&
            isset($clientData['price_change_start_time']) && !empty($clientData['price_change_start_time']) ){
            $filter['price_change_time'] = array('$gte'=>$clientData['price_change_start_time'],'$lte'=>$clientData['price_change_end_time']);

        }

        //下单时间
        if( isset($clientData['audit_time_start'])  && !empty($clientData['audit_time_start'])

            && isset($clientData['audit_time_end']) && !empty($clientData['audit_time_end'])
        ){
            $filter['product_audit_time'] = array('$gte'=>$clientData['audit_time_start'],'$lte'=>$clientData['audit_time_end']);
        }

        //采购单号
        //purchase_number
        if( isset($clientData['purchase_number']) && !empty($clientData['purchase_number']))
        {
            $filter['purchase_number'] = array('$in'=>$clientData['purchase_number']);
        }

        // 备货单
        if( isset($clientData['demand_number']) && !empty($clientData['demand_number']))
        {
            $filter['demand_number'] = array('$in'=>$clientData['demand_number']);
        }

        // 采购数量
        if( isset($clientData['purchase_num']) && !empty($clientData['purchase_num']) )
        {
            // 表示不等于0
            if( $clientData['purchase_num'] == 2 )
            {
                $filter['purchase_num_flag'] = 1;
            }else{
                // 等于0
                $filter['purchase_num_flag'] = 0;
            }
        }
        // 入库时间
        if( isset($clientData['warehouse_start_time']) && !empty($clientData['warehouse_start_time'])
            && isset($clientData['warehouse_end_time']) && !empty($clientData['warehouse_end_time'])
        )
        {
            $filter['instock_date'] = array('$gte'=>$clientData['warehouse_start_time'],'$lte'=>$clientData['warehouse_end_time']);
        }

        // 首次计算开始时间

        if( isset($clientData['first_calculation_start_time']) && !empty($clientData['first_calculation_start_time']) &&
            isset($clientData['first_calculation_end_time']) && !empty($clientData['first_calculation_end_time']) )
        {
            $filter['first_calculation_time'] = array('$gte'=>$clientData['first_calculation_start_time'],'$lte'=>$clientData['first_calculation_end_time']);
        }

        //是否叠加
        if( isset($clientData['is_superposition']) && !empty($clientData['is_superposition']) )
        {
            $filter['is_superposition'] = "{$clientData['is_superposition']}";
        }

        // 价格变化趋势
        if( isset($clientData['gain']) && !empty($clientData['gain']) )
        {
            // 表示涨价
            if( $clientData['gain'] == 1 )
            {
                $filter['range_price'] = array('$gte'=>'0');
            }else{
                // 降价
                $filter['range_price'] = array('$lte'=>'0');
            }
        }
        // 订单是否有效

        if( isset($clientData['is_effect']) && !empty($clientData['is_effect']))
        {
            $filter['is_effect'] = "{$clientData['is_effect']}";
        }

        // 是否结束统计

        if( isset($clientData['is_end']) && !empty($clientData['is_end']) )
        {
            // 表示结束
            if( $clientData['is_end'] == 1 )
            {
                $filter['purchase_order_status'] = array('$in'=>["9","11","14"]);
            }else{
                //未结束
                $filter['purchase_order_status'] = array('$nin'=>["9","11","14"]);
            }
        }

        //订单完结时间stock_owes

        if( isset($clientData['completion_time_start']) && !empty($clientData['completion_time_start'])

            && isset($clientData['completion_time_end']) && !empty($clientData['completion_time_end']))
        {
            //11,9,14
            $filter['purchase_order_status'] = array('$in'=>["9","11","14"]);
            $filter['completion_time'] = array('$gte'=>$clientData['completion_time_start'],'$lte'=>$clientData['completion_time_end']);
        }

        // 是否新模块数据

        if( isset($clientData['is_new_data']) && !empty($clientData['is_new_data']))
        {
            $filter['is_new_data'] = "{$clientData['is_new_data']}";
        }

        //是否代采

        if( isset($clientData['is_purchasing']) && !empty($clientData['is_purchasing']))
        {
            $filter['is_purchasing'] = "{$clientData['is_purchasing']}";
        }
        return $filter;
    }

    /**
     * SKU 降本导出数据，从MYSQL 中读取，之前是从MONGDB中读取
     * @author:luxu
     * @time:2020/8/13
     **/
    public function purchase_reduced_export_csv($id=''){

        ini_set('max_execution_time','18000');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $condition = json_decode($params['condition'], true);

            // $this->data_center_model->updateCenterData($id, ['data_status' => 1]);
//            $this->load->helper('export_csv');
//            $this->load->helper('status_product');
//            $this->load->helper('status_order');

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'reduced_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            $total = $par[0]['number'];
            $limit  = 2000;
            $page = ceil($total/$limit);
            $fp = fopen($reduced_file, "a+");
            $heads = [
                '优化人','部门','职位','工号','SKU','产品名称','供应商','价格变化时间','首次计算时间',
                '近6个月最低价','入库时间','原价','现价','价格变化幅度','降本比例','采购数量','入库数量',
                '价格变化金额1','取消数量','有效采购数量','价格变化金额2', '结束统计时间','是否结束统计','订单是否有效',
                '采购单号','备货单号','采购员','下单时间','采购订单状态',  '是否叠加','订单完结时间','是否新模块数据','是否代采','所属小组'
            ];
            foreach($heads as $key => $item) {
                $title[$key] =iconv('UTF-8', 'GBK//IGNORE', $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            for($i=1;$i<=$page;++$i){

                $condition['offset'] = $i;
                $condition['limit'] = $limit;
                $condition['is_swoole'] = true;
                $result = $this->Reduced_edition_model->get_reduced_detail( $condition );
                foreach($result['list'] as $value) {
                    $v_value_tmp = [];
                    $v_value_tmp['optimizing_user'] = iconv('UTF-8', 'GBK//IGNORE', $value['optimizing_user']);
                    $v_value_tmp['department'] = (isset($value['department']))?iconv('UTF-8', 'GBK//IGNORE', $value['department']):'';
                    $v_value_tmp['position'] = ( isset( $value['position']))?iconv('UTF-8', 'GBK//IGNORE', $value['position']):'';
                    $v_value_tmp['caff_code'] = (isset($value['job_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['job_number']):'';
                    $v_value_tmp['sku'] = iconv('UTF-8', 'GBK//IGNORE', $value['sku']);
                    $v_value_tmp['product_name'] = iconv('UTF-8', 'GBK//IGNORE', $value['product_name']);
                    $v_value_tmp['supplier_name'] = iconv('UTF-8', 'GBK//IGNORE', $value['supplier_name']);
                    $v_value_tmp['price_change_time'] = (isset($value['price_change_time']))?$value['price_change_time']:'';
                    $v_value_tmp['first_calculation_time'] = iconv('UTF-8', 'GBK//IGNORE',$value['first_calculation_time']);
                    $v_value_tmp['six_minimum_price'] =  (isset($value['six_minimum_price']))?$value['six_minimum_price']:0;
                    $v_value_tmp['instock_date'] = (isset($value['instock_date']))?$value['instock_date']:'';
                    $v_value_tmp['original_price'] = $value['old_price'];
                    $v_value_tmp['present_price'] = $value['new_price'];
                    $v_value_tmp['price_change'] = $value['range_price'];
                    $reduced_proportion = $value['old_price'] != 0 ? (round(($value['new_price'] - $value['old_price']) / $value['old_price'], 4) * 100) : 0;
                    $reduced_proportion = (round($reduced_proportion, 2) . '%');
                    $v_value_tmp['reduced_proportion'] = $reduced_proportion;

                    $v_value_tmp['purchase_quantity'] = (isset($value['purchase_num']))?$value['purchase_num']:'';//采购数量
                    $v_value_tmp['instock_qty'] = (isset($value['instock_qty']))?iconv('UTF-8', 'GBK//IGNORE', $value['instock_qty']):'';// 总入库数量
                    // $v_value_tmp['breakage_number'] = (isset($value['breakage_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['breakage_number']):NULL;
                    //$v_value_tmp['actual_number'] =  (isset($value['actual_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['actual_number']):NULL; //实际入库数量
                    //价格变化金额：采购数量*价格变化幅度
                    $v_value_tmp['price_change_total_1'] = (isset($value['price_change_1']))?$value['price_change_1']:'';
                    $v_value_tmp['cancel_ctq'] = (isset($value['cancel_ctq']))?$value['cancel_ctq']:'';//取消数量
                    $v_value_tmp['effective_purchase_num'] = (isset($value['effective_purchase_num']))?$value['effective_purchase_num']:''; // 有效采购数量
                    $v_value_tmp['price_change_total_2'] = (isset($value['price_change_2']))?$value['price_change_2']:'';
                    $v_value_tmp['end_time'] = (isset($value['end_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['end_time']):'';// 结束统计时间
                    $v_value_tmp['is_end_name'] = (isset($value['is_end_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_end_name']):'';//是否结束统计
                    $v_value_tmp['is_effect_name'] = (isset($value['is_effect_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_effect_name']):'';

                    $v_value_tmp['purchase_number'] =  (isset($value['purchase_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_number']):'';
                    $v_value_tmp['demand_number'] = (isset($value['demand_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['demand_number']):'';//备货单号

                    $v_value_tmp['buyer_name'] = (isset($value['puchare_person']))?iconv('UTF-8', 'GBK//IGNORE', $value['puchare_person']):'';
                    $v_value_tmp['create_time'] = (isset($value['product_audit_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['product_audit_time']):'';
                    $v_value_tmp['purchase_status_mess'] = (isset($value['purchase_status_mess']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_status_mess']):'';

                    $v_value_tmp['is_superposition_name'] = (isset($value['is_superposition_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_superposition_name']):'';
                    $v_value_tmp['completion_time'] = ( isset($value['completion_time']))?iconv('UTF-8', 'GBK//IGNORE',$value['completion_time']):NULL;
                    $v_value_tmp['is_new_data_ch'] = ( isset($value['is_new_data_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_new_data_ch']):NULL;
                    $v_value_tmp['is_purchasing_ch'] = ( isset($value['is_purchasing_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_purchasing_ch']):NULL;
                    $v_value_tmp['groupName']  =  ( isset($value['groupName']))?iconv('UTF-8', 'GBK//IGNORE',$value['groupName']):NULL;
                    fputcsv($fp, $v_value_tmp);
                }

                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
            }
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }




        }
    }

    /**
     * SKU 降本导出数据
     * @author:luxu
     * @time:2020/5/23
     **/
    public function purchase_reduced_export_csv_1($id=''){

        ini_set('memory_limit', '1500M');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $condition = json_decode($params['condition'], true);
            echo "start";
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);

            $this->load->helper('export_csv');
            $this->load->helper('status_product');
            $this->load->helper('status_order');

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'reduced_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $reduced_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }

            $this->_ci = get_instance();
            $this->_ci->load->config('mongodb');
            $host = $this->_ci->config->item('mongo_host');
            $port = $this->_ci->config->item('mongo_port');
            $user = $this->_ci->config->item('mongo_user');
            $password = $this->_ci->config->item('mongo_pass');
            $author_db = $this->_ci->config->item('mongo_db');
            $mongdb_object = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
            $filter = $this->get_mongdb_key($condition);
            $command =  new MongoDB\Driver\Command(['count' => 'reduced_detail','query'=>$filter]);
            $result = $mongdb_object->executeCommand($author_db,$command)->toArray();
            $total =$result[0]->n;
            $limit  = 2000;
            $page = ceil($total/$limit);
            $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
            //$file_name = 'reduced_edition.csv';
            $fp = fopen($reduced_file, "a+");
            $heads = [
                '优化人','部门','职位','工号','SKU','产品名称','供应商','价格变化时间','首次计算时间',
                '近6个月最低价','入库时间','原价','现价','价格变化幅度','降本比例','采购数量','入库数量',
                '价格变化金额1','取消数量','有效采购数量','价格变化金额2', '结束统计时间','是否结束统计','订单是否有效',
                '采购单号','备货单号','采购员','下单时间','采购订单状态',  '是否叠加','订单完结时间','是否新模块数据','是否代采'
            ];
            foreach($heads as $key => $item) {
                $title[$key] =iconv('UTF-8', 'GBK//IGNORE', $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            for( $i=1;$i<=$page;++$i) {
                $options['skip'] = ($i-1) * $limit;
                $options['limit'] = $limit;
                $query = new MongoDB\Driver\Query($filter,$options);
                $mongodb_result =$mongdb_object->executeQuery("{$author_db}.reduced_detail", $query)->toArray();
                $result = $this->reduced_edition->get_reduced_mongodb_data($mongodb_result);
                foreach($result as $value) {
                    $v_value_tmp = [];
                    $v_value_tmp['optimizing_user'] = iconv('UTF-8', 'GBK//IGNORE', $value['optimizing_user']);
                    $v_value_tmp['department'] = (isset($value['department']))?iconv('UTF-8', 'GBK//IGNORE', $value['department']):'';
                    $v_value_tmp['position'] = ( isset( $value['position']))?iconv('UTF-8', 'GBK//IGNORE', $value['position']):'';
                    $v_value_tmp['caff_code'] = (isset($value['job_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['job_number']):'';
                    $v_value_tmp['sku'] = iconv('UTF-8', 'GBK//IGNORE', $value['sku']);
                    $v_value_tmp['product_name'] = iconv('UTF-8', 'GBK//IGNORE', $value['product_name']);
                    $v_value_tmp['supplier_name'] = iconv('UTF-8', 'GBK//IGNORE', $value['supplier_name']);
                    $v_value_tmp['price_change_time'] = (isset($value['price_change_time']))?$value['price_change_time']:'';
                    $v_value_tmp['first_calculation_time'] = iconv('UTF-8', 'GBK//IGNORE',$value['first_calculation_time']);
                    $v_value_tmp['six_minimum_price'] =  (isset($value['six_minimum_price']))?$value['six_minimum_price']:0;
                    $v_value_tmp['instock_date'] = (isset($value['instock_date']))?$value['instock_date']:'';
                    $v_value_tmp['original_price'] = $value['old_price'];
                    $v_value_tmp['present_price'] = $value['new_price'];
                    $v_value_tmp['price_change'] = $value['range_price'];
                    $reduced_proportion = $value['old_price'] != 0 ? (round(($value['new_price'] - $value['old_price']) / $value['old_price'], 4) * 100) : 0;
                    $reduced_proportion = (round($reduced_proportion, 2) . '%');
                    $v_value_tmp['reduced_proportion'] = $reduced_proportion;

                    $v_value_tmp['purchase_quantity'] = (isset($value['purchase_num']))?$value['purchase_num']:'';//采购数量
                    $v_value_tmp['instock_qty'] = (isset($value['instock_qty']))?iconv('UTF-8', 'GBK//IGNORE', $value['instock_qty']):'';// 总入库数量
                    // $v_value_tmp['breakage_number'] = (isset($value['breakage_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['breakage_number']):NULL;
                    //$v_value_tmp['actual_number'] =  (isset($value['actual_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['actual_number']):NULL; //实际入库数量
                    //价格变化金额：采购数量*价格变化幅度
                    $v_value_tmp['price_change_total_1'] = (isset($value['price_change_1']))?$value['price_change_1']:'';
                    $v_value_tmp['cancel_ctq'] = (isset($value['cancel_ctq']))?$value['cancel_ctq']:'';//取消数量
                    $v_value_tmp['effective_purchase_num'] = (isset($value['effective_purchase_num']))?$value['effective_purchase_num']:''; // 有效采购数量
                    $v_value_tmp['price_change_total_2'] = (isset($value['price_change_2']))?$value['price_change_2']:'';
                    $v_value_tmp['end_time'] = (isset($value['end_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['end_time']):'';// 结束统计时间
                    $v_value_tmp['is_end_name'] = (isset($value['is_end_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_end_name']):'';//是否结束统计
                    $v_value_tmp['is_effect_name'] = (isset($value['is_effect_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_effect_name']):'';

                    $v_value_tmp['purchase_number'] =  (isset($value['purchase_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_number']):'';
                    $v_value_tmp['demand_number'] = (isset($value['demand_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['demand_number']):'';//备货单号

                    $v_value_tmp['buyer_name'] = (isset($value['puchare_person']))?iconv('UTF-8', 'GBK//IGNORE', $value['puchare_person']):'';
                    $v_value_tmp['create_time'] = (isset($value['product_audit_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['product_audit_time']):'';
                    $v_value_tmp['purchase_status_mess'] = (isset($value['purchase_status_mess']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_status_mess']):'';

                    $v_value_tmp['is_superposition_name'] = (isset($value['is_superposition_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_superposition_name']):'';
                    $v_value_tmp['completion_time'] = ( isset($value['completion_time']))?iconv('UTF-8', 'GBK//IGNORE',$value['completion_time']):NULL;
                    $v_value_tmp['is_new_data_ch'] = ( isset($value['is_new_data_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_new_data_ch']):NULL;
                    $v_value_tmp['is_purchasing_ch'] = ( isset($value['is_purchasing_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_purchasing_ch']):NULL;
                    fputcsv($fp, $v_value_tmp);
                }
                //每1万条数据就刷新缓冲区
//                ob_flush();
//                flush();

                // 回写数据到pur_center_data 表
                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
            }
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * 产品管理导出服务器脚本
     */
    public function purchase_product_export()
    {

        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9505);
//开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //投递异步任务
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'product_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_product_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_product_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    public function download_product_csv($id = 0)
    {
        $par = $this->data_center_model->get_items("id = " . $id);
        $params = $par[0];
        if ($params['module_ch_name'] == 'PRODUCTDATA') {
            return $this->download_product_item_csv($id);
        } elseif ($params['module_ch_name'] == 'PRODUCTAUDITDATA') { //产品信息审核
            return $this->download_product_audit_csv($id);
        }
    }

    /**
     * 产品管理导出
     * @param int $id
     * @return bool
     */
    public function download_product_item_csv($id = 1232)
    {
        ini_set('memory_limit', '1500M');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $condition = json_decode($params['condition'], true);
            echo "start";
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);

            $this->load->helper('export_csv');
            $this->load->helper('status_product');
            $this->load->helper('status_order');

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'product_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $product_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($product_file)) {
                unlink($product_file);
            }

            $field = 'p.long_delivery,p.is_customized,p.devliy,p.sample_packaging_type,p.product_weight,p.is_relate_ali,p.is_shipping,p.sku_state_type,p.sku_message,p.outer_box_volume,p.product_volume,p.inside_number,p.box_size,p.is_new,p.is_overseas_first_order,p.audit_status_log,p.is_purchasing,p.is_inspection,p.coupon_rate,p.productismulti,p.producttype,p.is_invalid,p.id,p.sku,p.supplier_code,p.product_img_url,p.product_thumb_url,p.product_name,p.declare_cname,p.tax_rate,export_cname,p.declare_unit,p.export_model,p.product_status,p.product_line_id,p.create_user_name,p.create_time,p.original_start_qty,p.original_start_qty_unit,p.starting_qty,p.starting_qty_unit,p.is_drawback,p.purchase_price,p.ticketed_point,p.supplier_name,p.product_cn_link,p.supply_status,p.note,p.is_abnormal,p.is_equal_sup_id,p.is_equal_sup_name';

            //$orders_info = $this->product_model->get_product_list($params, $offset, $limit,$field,True);

            $total = $params['number'];

            echo "total:" . $total . "\n" .
                $fp = fopen($product_file, "a+");
            $heads = [
                'SKU',
                '是否海外仓首单',
                '是否新品', '产品名称',
                '票面税率', '出口退税税率',
                '申报中文名', '申报单位',
                '出口申报型号', '产品状态',
                '一级产品线', '二级产品线',
                '三级产品线', '四级产品线',
                '产品线', '开发员', '创建时间',
                '原始起订量', '原始起订量单位',
                '最新起订量', '最新起订量单位',
                '是否退税', '单价', '税点',
                '供应商名称', '采购员', '采购链接',
                '货源状态', '供应商来源', '是否异常',
                '备注', '连接是否失效', '产品类型',
                '是否代采', '是否商检', '供应商ID是否一致',
                '供应商名称是否一致',
                '审核状态','商品参数',
                '是否国内转海外','海外仓小组','国内仓小组','结算方式','是否包邮','是否海外仓sku','1688起订量','样品重量(g)','包装类型','交期','SKU创建时间',

                '箱内数','外箱尺寸','外箱体积','产品体积','是否定制','是否超长交期'];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            $limit = 500;
//            $page = ceil($total / $limit);

//        $flag = false;
            $i = 1;
            do {
                $redirectStdout = false;
                $export_offset = ($i++ - 1)* $limit;
                echo "cur_num:" . $i * $limit . ',mem_start:' . round(memory_get_usage() / 1024 / 1024, 2) . "\n";
                $orders_export_info = $this->product_model->get_product_list($condition, $export_offset, $limit, $field);
                $product_list = $orders_export_info['value'];
                if( isset($condition['role_name']) && !empty($condition['role_name'])){
                    // $product_list = $results = ShieldingData($product_list,['supplier_name','supplier_code'],$condition['role_name'],NULL);
                    $roleString = implode(",",$condition['role_name']);

                }
                echo "count:" . count($product_list);
                if (count($product_list) > 0) {
                    $redirectStdout = true;
                }
                $tax_list_tmp = [];
                if ($product_list) {
                    foreach ($product_list as $key => $v_value) {

                        $supplier_buyer_list = $this->supplier_buyer_model->get_buyer_list($v_value['supplier_code']);
                        $buyerName = [];
                        if(!empty($supplier_buyer_list)) {
                            $buyerIds = array_unique(array_column($supplier_buyer_list, 'buyer_id'));
                            $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
                        }
                        $overseas = $domatic = '';
                        if(!empty($buyerName)) {
                            foreach ($buyerName as $buyerValue) {

                                if ($buyerValue['category_id'] == 1) {

                                    $domatic .= $buyerValue['group_name'];
                                }

                                if ($buyerValue['category_id'] == 2) {

                                    $overseas .= $buyerValue['group_name'];
                                }
                            }
                        }

                        $supplier_buyer_user = '';
                        if (!empty($supplier_buyer_list)) {
                            foreach ($supplier_buyer_list as $k => $val) {
                                if (PURCHASE_TYPE_INLAND == $val['buyer_type']) {
                                    $buyer_type_name = '国内仓';
                                } elseif (PURCHASE_TYPE_OVERSEA == $val['buyer_type']) {
                                    $buyer_type_name = '海外仓';
                                } elseif (PURCHASE_TYPE_FBA_BIG == $val['buyer_type']) {
                                    $buyer_type_name = 'FBA大货';
                                } elseif (PURCHASE_TYPE_FBA == $val['buyer_type']) {
                                    $buyer_type_name = 'FBA';
                                } else {
                                    $buyer_type_name = '未知';
                                }
                                $supplier_buyer_user .= $buyer_type_name . ':' . $val['buyer_name'] . ',';
                            }
                        }
                        $v_value_tmp = [];
                        $v_value_tmp['sku'] = iconv("UTF-8", "GBK//IGNORE", "'".$v_value['sku']);
                        $v_value_tmp['is_overseas_first_order_ch'] = iconv("UTF-8", "GBK//IGNORE", $v_value['is_overseas_first_order_ch']);
                        $v_value_tmp['is_new_ch'] = iconv("UTF-8", "GBK//IGNORE", $v_value['is_new_ch']);
                        $v_value_tmp['product_name'] = iconv("UTF-8", "GBK//IGNORE", $v_value['product_name']);
                        $v_value_tmp['coupon_rate'] = iconv("UTF-8", "GBK//IGNORE", $v_value['coupon_rate']);
                        $v_value_tmp['tax_rate'] = iconv("UTF-8", "GBK//IGNORE", $v_value['tax_rate']);
                        $v_value_tmp['declare_unit'] = iconv("UTF-8", "GBK//IGNORE", $v_value['declare_unit']);
                        $v_value_tmp['declare_cname'] = iconv("UTF-8", "GBK//IGNORE", $v_value['declare_cname']);
                        $v_value_tmp['export_model'] = iconv("UTF-8", "GBK//IGNORE", $v_value['export_model']);//出口申报型号
                        $v_value_tmp['product_status'] = iconv("UTF-8", "GBK//IGNORE", getProductStatus($v_value['product_status']));//产品状态

                        if (isset($v_value['category_line_data'][0]) && !empty($v_value['category_line_data'][0])) {
                            $v_value_tmp['one_line_name'] = iconv("UTF-8", "GBK//IGNORE", $v_value['category_line_data'][0]['product_line_name']);
                        } else {
                            $v_value_tmp['one_line_name'] = NULL;
                        }
                        if (isset($v_value['category_line_data'][1]) && !empty($v_value['category_line_data'][1])) {
                            $v_value_tmp['two_line_name'] = iconv("UTF-8", "GBK//IGNORE", $v_value['category_line_data'][1]['product_line_name']);
                        } else {
                            $v_value_tmp['two_line_name'] = NULL;
                        }
                        if (isset($v_value['category_line_data'][2]) && !empty($v_value['category_line_data'][2])) {
                            $v_value_tmp['three_line_name'] = iconv("UTF-8", "GBK//IGNORE", $v_value['category_line_data'][2]['product_line_name']);
                        } else {
                            $v_value_tmp['three_line_name'] = NULL;
                        }
                        if (isset($v_value['category_line_data'][3]) && !empty($v_value['category_line_data'][3])) {
                            $v_value_tmp['four_line_name'] = iconv("UTF-8", "GBK//IGNORE", $v_value['category_line_data'][3]['product_line_name']);
                        } else {
                            $v_value_tmp['four_line_name'] = NULL;
                        }

                        $v_value_tmp['product_line_id'] = iconv("UTF-8", "GBK//IGNORE", $this->product_line->get_product_line_name($v_value['product_line_id']));
                        $v_value_tmp['create_user_name'] = iconv("UTF-8", "GBK//IGNORE", $v_value['create_user_name']);
                        $v_value_tmp['create_time'] = iconv("UTF-8", "GBK//IGNORE", $v_value['create_time']);
                        $v_value_tmp['original_start_qty'] = iconv("UTF-8", "GBK//IGNORE", $v_value['original_start_qty']);
                        $v_value_tmp['original_start_qty_unit'] = iconv("UTF-8", "GBK//IGNORE", $v_value['original_start_qty_unit']);
                        $v_value_tmp['starting_qty'] = iconv("UTF-8", "GBK//IGNORE", $v_value['starting_qty']);
                        $v_value_tmp['starting_qty_unit'] = iconv("UTF-8", "GBK//IGNORE", $v_value['starting_qty_unit']);
                        $v_value_tmp['is_drawback'] = iconv("UTF-8", "GBK//IGNORE", getIsDrawback($v_value['is_drawback']));
                        $v_value_tmp['purchase_price'] = iconv("UTF-8", "GBK//IGNORE", $v_value['purchase_price']);
                        $v_value_tmp['ticketed_point'] = iconv("UTF-8", "GBK//IGNORE", $v_value['ticketed_point']);//税点
                        $v_value_tmp['supplier_name'] = iconv("UTF-8", "GBK//IGNORE", $v_value['supplier_name']);
                        $v_value_tmp['supplier_buyer_user'] = iconv("UTF-8", "GBK//IGNORE", rtrim($supplier_buyer_user, ','));
                        $v_value_tmp['product_cn_link'] = iconv("UTF-8", "GBK//IGNORE", $v_value['product_cn_link']);
                        $v_value_tmp['supply_status'] = iconv("UTF-8", "GBK//IGNORE", !empty($v_value['supply_status']) ? getProductsupplystatus($v_value['supply_status']) : '');
                        $v_value_tmp['supplier_source'] = iconv("UTF-8", "GBK//IGNORE", $v_value['supplier_source_ch']);
                        $v_value_tmp['is_abnormal'] = iconv("UTF-8", "GBK//IGNORE", !empty($v_value['is_abnormal']) ? getProductAbnormal($v_value['is_abnormal']) : '');
                        $v_value_tmp['note'] = $v_value['note'];


                        if (empty($v_value['is_invalid']) || $v_value['is_invalid'] == 0) {
                            $v_value_tmp['is_invalid'] = iconv("UTF-8", "GBK//IGNORE", '正常');
                        } else if ($v_value['is_invalid'] == 1) {
                            $v_value_tmp['is_invalid'] = iconv("UTF-8", "GBK//IGNORE", '失效');
                        }

                        $v_value_tmp['t_product_type'] = NULL;
                        if ($v_value['productismulti'] == 2) {

                            $v_value_tmp['t_product_type'] .= iconv("UTF-8", "GBK//IGNORE", ' SPU');
                        }

                        if ($v_value['producttype'] == 2) {

                            $v_value_tmp['t_product_type'] .= iconv("UTF-8", "GBK//IGNORE", ' 捆绑销售');
                        }
                        if (($v_value['productismulti'] == 0 || $v_value['productismulti'] == 1) && $v_value['producttype'] == 1) {

                            $v_value_tmp['t_product_type'] .= iconv("UTF-8", "GBK//IGNORE", ' 普通');
                        }

                        if ($v_value['is_purchasing'] == 1) {
                            $v_value_tmp['is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", '否');
                        }
                        if ($v_value['is_purchasing'] == 2) {
                            $v_value_tmp['is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", '是');
                        }


                        // iconv("UTF-8", "GBK//IGNORE",'不商检')
                        $v_value_tmp['is_inspection'] = ($v_value['is_inspection'] == 1) ? iconv("UTF-8", "GBK//IGNORE", '不商检') : iconv("UTF-8", "GBK//IGNORE", '商检');
                        $v_value_tmp['is_equal_sup_id'] = iconv("UTF-8", "GBK//IGNORE", getEqualSupId($v_value['is_equal_sup_id']));
                        $v_value_tmp['is_equal_sup_name'] = iconv("UTF-8", "GBK//IGNORE", getEqualSupName($v_value['is_equal_sup_name']));
                        $v_value_tmp['audit_status_log_cn'] = iconv("UTF-8", "GBK//IGNORE", $v_value['audit_status_log_cn']);
                        $v_value_tmp['sku_message'] =preg_replace("/(\s|\&nbsp\;|　|\xc2\xa0)/", " ", strip_tags($v_value['sku_message']));//去除换行，&nbsp;等字符
                        $v_value_tmp['sku_message'] =  iconv("UTF-8", "GBK//IGNORE", $v_value_tmp['sku_message']);

                        if(isset($v_value['sku_state_type']) && $v_value['sku_state_type'] == 6 ){

                            $v_value_tmp['sku_state_type_ch'] =  iconv("UTF-8", "GBK//IGNORE", '是');
                        }else{
                            $v_value_tmp['sku_state_type_ch'] =  iconv("UTF-8", "GBK//IGNORE", '否');
                        }
                        //$v_value_tmp['sku_state_type_ch'] =  iconv("UTF-8", "GBK//IGNORE", $v_value_tmp['sku_state_type_ch']);
                        $v_value_tmp['overseas'] =iconv("UTF-8", "GBK//IGNORE",$overseas );
                        $v_value_tmp['domatic'] =iconv("UTF-8", "GBK//IGNORE", $domatic);
                        $paymentData = $this->Supplier_model->get_supplier_payment($v_value['supplier_code']);
                        $v_value_tmp['settlement_method'] = iconv("UTF-8", "GBK//IGNORE",$paymentData);

                        $product_attributes = $this->product_model->get_product_attribute_data($v_value['sku']);

                        $overseas_ch = '';
                        if($product_attributes['海外属性'] && !empty($product_attributes['海外属性'])){
                            if(count($product_attributes['海外属性']) == 1) {
                                if (in_array('国内仓', $product_attributes['海外属性'])) {

                                    $overseas_ch = iconv("UTF-8", "GBK//IGNORE","否");
                                }else{
                                    $overseas_ch = iconv("UTF-8", "GBK//IGNORE","是");
                                }
                            }else{

                                if (in_array('国内仓', $product_attributes['海外属性'])) {
                                    $overseas_ch = iconv("UTF-8", "GBK//IGNORE","共用");
                                }else{
                                    $overseas_ch = iconv("UTF-8", "GBK//IGNORE","是");
                                }
                            }
                        }

                        if( isset($v_value['is_shipping']) && $v_value['is_shipping'] == 1){

                            $v_value_tmp['is_shipping_ch'] = iconv("UTF-8", "GBK//IGNORE","包邮");
                        }

                        if( isset($v_value['is_shipping']) && $v_value['is_shipping'] == 2){

                            $v_value_tmp['is_shipping_ch'] = iconv("UTF-8", "GBK//IGNORE","不包邮");
                        }

                        $v_value_tmp['overseas_ch'] = $overseas_ch;
                        $v_value_tmp['starting_qty_ch'] = iconv("UTF-8", "GBK//IGNORE",$v_value['is_relate_ali']);
                        $v_value_tmp['product_weight_number'] = iconv("UTF-8", "GBK//IGNORE",$v_value['product_weight']);
                        $v_value_tmp['sample_packaging_type_ch'] =iconv("UTF-8", "GBK//IGNORE",$v_value['sample_packaging_type']);
                        $v_value_tmp['devliy_ch'] = iconv("UTF-8", "GBK//IGNORE",$v_value['devliy']);
                        if(strstr($roleString,'采购') || strstr($roleString,'财务') || strstr($roleString,'供应') || strstr($roleString,'品控')){

                            $v_value_tmp['supplier_name'] = iconv("UTF-8", "GBK//IGNORE",$v_value['supplier_name']);
                        }else{
                            $v_value_tmp['supplier_name'] = "*";
                        }
                        $v_value_tmp['create_time_sku'] = iconv("UTF-8", "GBK//IGNORE",$v_value['create_time']);
                        $v_value_tmp['inside_number'] = iconv("UTF-8", "GBK//IGNORE",$v_value['inside_number']);
                        $v_value_tmp['box_size'] = iconv("UTF-8", "GBK//IGNORE",$v_value['box_size']);
                        $v_value_tmp['outer_box_volume'] = iconv("UTF-8", "GBK//IGNORE",$v_value['outer_box_volume']);
                        $v_value_tmp['product_volume'] = iconv("UTF-8", "GBK//IGNORE",$v_value['product_volume']);
                        if($v_value['is_customized'] == 2){

                            $v_value_tmp['is_customized_ch'] = iconv("UTF-8", "GBK//IGNORE","否");
                        }else{
                            $v_value_tmp['is_customized_ch'] = iconv("UTF-8", "GBK//IGNORE","是");
                        }

                        if( $v_value['long_delivery'] == 2){

                            $v_value_tmp['long_delivery_ch'] = iconv("UTF-8", "GBK//IGNORE","是");

                        }else{
                            $v_value_tmp['long_delivery_ch'] = iconv("UTF-8", "GBK//IGNORE","否");


                        }

                        $new_supplierAvg = $this->product_model->get_supplier_avg_day($v_value['supplier_code']);
                        $v_value_tmp['new_ds_day_avg'] = iconv("UTF-8", "GBK//IGNORE",isset($new_supplierAvg['ds_day_avg'])?$new_supplierAvg['ds_day_avg']:'-');
                        $v_value_tmp['new_os_day_avg'] =  iconv("UTF-8", "GBK//IGNORE",isset($new_supplierAvg['os_day_avg'])?$new_supplierAvg['os_day_avg']:'-');
                        $v_value_tmp['nvew_ds_deliverrate'] =  iconv("UTF-8", "GBK//IGNORE",isset($new_supplierAvg['ds_deliverrate'])?round(($new_supplierAvg['ds_deliverrate']*100),4)."%":'-');
                        $v_value_tmp['new_os_deliverrate'] =  iconv("UTF-8", "GBK//IGNORE",isset($new_supplierAvg['os_deliverrate'])?round(($new_supplierAvg['os_deliverrate']*100),4)."%":'-');


                        fputcsv($fp, $v_value_tmp);

                    }
                }
                // 回写数据到pur_center_data 表
                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);

            } while ($redirectStdout);
            //  上传文件到文件服务器
            $file_data = $this->upload_file($product_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        } else {
            echo '没有数据处理';
        }
//        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        return $result;
    }


    public function download_product_audit_csv($id = 0)
    {
        $this->load->model('product/Product_mod_audit_model', 'product_mod');
        $export_mode = 1;
        ini_set('memory_limit', '1500M');
//        if (!empty($id)) {
        $par = $this->data_center_model->get_items("id = " . $id);
        $params = $par[0];
        $condition = json_decode($params['condition'], true);
        echo "start\n";
        $this->data_center_model->updateCenterData($id, ['data_status' => 1]);

        $this->load->helper('export_csv');
        $this->load->helper('status_product');
        $this->load->helper('status_order');

        $webfront_path = dirname(dirname(APPPATH));
        $file_name = 'product_audit' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
        $product_file = $webfront_path . '/webfront/download_csv/' . $file_name;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        $field = 'b.product_line_id,b.supply_status,b.create_time AS product_create_time,a.new_supply_status,a.old_supply_status,a.*,b.product_img_url,b.product_thumb_url,b.create_user_name as develop_user_name,b.create_time as develop_time,b.product_status';
        $total_data = $this->product_mod->get_product_list($condition, 1, '', $field, '', true);
        $total = $total_data['paging_data']['total'];
        if ($export_mode == 1) {
            // 表示CSV格式
            if (file_exists($product_file)) {
                unlink($product_file);
            }
//            fopen($product_file, 'w');
            $fp = fopen($product_file, "a+");
            $heads = array('审核状态', '审核人', '审核时间',
                '审核备注', '申请人', '申请时间',
                '产品图片', 'sku', '产品名称',
                '产品状态', '产品线', '开发员',
                '创建时间', '修改前单价',
                '修改后单价', '修改前税点',
                '修改后税点', '修改前供应商',
                '修改后供应商', '修改前链接',
                '修改后链接', '是否拿样',
                '修改前代采', '修改后代采',
                '样品检验结果', '确认人',
                '确认时间', '确认备注', '申请备注',
                '申请类型', '修改原因','所属小组','结算方式(修改前)',
                '结算方式(修改后)','是否包邮(修改后)','是否包邮(修改前)','价格变化比例','货源状态（修改前）','货源状态(修改后)','SKU创建时间','SKU一级产品线','预计供货时间','货源状态');
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            $limit = 500;
//            $page = ceil($total / $limit);
            $i = 1;
            $this->load->model('product_line_model','',false,'product');
            do{
                $redirectStdout = false;
                $offset = ($i++ - 1) * $limit;
                echo "cur_num:" . $i * $limit . ',mem_start:' . round(memory_get_usage() / 1024 / 1024, 2) . "\n";
                $result = $this->product_mod->get_product_list($condition, $limit, $offset, $field, '', false);
                if (count($result['data_list']) > 0) {
                    $redirectStdout = true;
                }
                $formart_data = $this->product_mod->formart_product_list($result['data_list']);

                if (!empty($formart_data)) {

                    if( isset($condition['role_name']) && !empty($condition['role_name'])){
                        $formart_data = $results = ShieldingData($formart_data,['new_supplier_name','new_supplier_code','old_supplier_name','old_supplier_code'],$condition['role_name'],NULL);
                    }

                    foreach ($formart_data as $key => $value) {
                        $v_value_tmp = [];
                        $category_all = $this->product_line_model->get_all_parent_category($value['product_line_id']);
                        $v_value_tmp['audit_status'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_status']);
                        $v_value_tmp['audit_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_user_name']);
                        $v_value_tmp['audit_time'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_time']);
                        $v_value_tmp['audit_remark'] = iconv("UTF-8", "GBK//IGNORE", $value['audit_remark']);
                        $v_value_tmp['create_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);
                        $v_value_tmp['create_time'] = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);
                        $v_value_tmp['product_img_url'] = iconv("UTF-8", "GBK//IGNORE", $value['product_img_url']);
                        $v_value_tmp['sku'] = iconv("UTF-8", "GBK//IGNORE", "'".$value['sku']);
                        $v_value_tmp['product_name'] = iconv("UTF-8", "GBK//IGNORE", $value['product_name']);
                        $v_value_tmp['product_status'] = iconv("UTF-8", "GBK//IGNORE", $value['product_status']);
                        $v_value_tmp['product_line_name'] = iconv("UTF-8", "GBK//IGNORE", ($value['product_line_name']));
                        $v_value_tmp['develop_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['develop_user_name']);
                        $v_value_tmp['develop_time'] = iconv("UTF-8", "GBK//IGNORE", $value['product_create_time']);
                        $v_value_tmp['old_supplier_price'] = iconv("UTF-8", "GBK//IGNORE", $value['old_supplier_price']);
                        $v_value_tmp['new_supplier_price'] = iconv("UTF-8", "GBK//IGNORE", $value['new_supplier_price']);
                        $v_value_tmp['old_ticketed_point'] = iconv("UTF-8", "GBK//IGNORE", $value['old_ticketed_point']);
                        $v_value_tmp['new_ticketed_point'] = iconv("UTF-8", "GBK//IGNORE", $value['new_ticketed_point']);
                        $v_value_tmp['old_supplier_name'] = iconv("UTF-8", "GBK//IGNORE", $value['old_supplier_name']);
                        $v_value_tmp['new_supplier_name'] = iconv("UTF-8", "GBK//IGNORE", $value['new_supplier_name']);
                        $v_value_tmp['old_product_link'] = iconv("UTF-8", "GBK//IGNORE", $value['old_product_link']);
                        $v_value_tmp['new_product_link'] = iconv("UTF-8", "GBK//IGNORE", $value['new_product_link']);
                        $v_value_tmp['is_sample'] = iconv("UTF-8", "GBK//IGNORE", $value['is_sample']);
                        if ($value['old_is_purchasing'] == 1) {
                            $v_value_tmp['old_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "否");
                        } else {
                            $v_value_tmp['old_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "是");
                        }

                        if ($value['new_is_purchasing'] == 1) {
                            $v_value_tmp['new_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "否");
                        } else {
                            $v_value_tmp['new_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "是");
                        }

                        $v_value_tmp['sample_check_result'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_check_result']);
                        $v_value_tmp['sample_user_name'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_user_name']);
                        $v_value_tmp['sample_time'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_time']);
                        $v_value_tmp['sample_remark'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_remark']);
                        $v_value_tmp['sample_remark'] = iconv("UTF-8", "GBK//IGNORE", $value['sample_remark']);
                        $v_value_tmp['create_remark'] = iconv("UTF-8", "GBK//IGNORE", $value['create_remark']);
                        if (!empty($value['update_content'])) {
                            $content_string = '';
                            foreach ($value['update_content'] as $content_key => $content_value) {
                                $content_string .= $content_value . ",";
                                $v_value_tmp['update_content'] = iconv("UTF-8", "GBK//IGNORE", $content_string);
                            }

                        } else {
                            $v_value_tmp['update_content'] = '';
                        }
                        $v_value_tmp['reason'] = iconv("UTF-8", "GBK//IGNORE", $value['reason']);
                        $v_value_tmp['groupName'] =iconv("UTF-8", "GBK//IGNORE", $value['groupName']);
                        $oldpaymentData = $this->Supplier_model->get_supplier_payment($value['old_supplier_code']);
                        $v_value_tmp['settlement_method_old'] =  iconv("UTF-8", "GBK//IGNORE",$oldpaymentData);

                        $newpaymentData = $this->Supplier_model->get_supplier_payment($value['new_supplier_code']);
                        $v_value_tmp['settlement_method_new'] =  iconv("UTF-8", "GBK//IGNORE",$newpaymentData);



                        $v_value_tmp['new_is_shipping_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['is_new_shipping_ch']);

                        $v_value_tmp['old_is_shipping_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['is_old_shipping_ch']);


                        $v_value_tmp['cuttheprice_ch'] = $value['cuttheprice'];

                        //货源状态(1.正常,2.停产,3.断货,10:停产找货中)
                        $v_value_tmp['old_supply_status_ch'] = '';
                        if( $value['old_supply_status'] == 1){

                            $v_value_tmp['old_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","正常");
                        }
                        if( $value['old_supply_status'] == 2){

                            $v_value_tmp['old_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","停产");
                        }
                        if( $value['old_supply_status'] == 3){

                            $v_value_tmp['old_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","断货");
                        }
                        if( $value['old_supply_status'] == 10){

                            $v_value_tmp['old_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","停产找货中");
                        }

                        $v_value_tmp['new_supply_status_ch'] = '';
                        if( $value['new_supply_status'] == 1){

                            $v_value_tmp['new_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","正常");
                        }
                        if( $value['new_supply_status'] == 2){

                            $v_value_tmp['new_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","停产");
                        }
                        if( $value['new_supply_status'] == 3){

                            $v_value_tmp['new_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","断货");
                        }
                        if( $value['new_supply_status'] == 10){

                            $v_value_tmp['new_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","停产找货中");
                        }
                        $v_value_tmp['product_create_time'] = iconv("UTF-8", "GBK//IGNORE",$value['product_create_time']);
                        $one_line_data = (!empty($category_all) && isset($category_all[0]))?$category_all[0]['product_line_name']:'';
                        $v_value_tmp['one_line_data'] = iconv("UTF-8", "GBK//IGNORE",$one_line_data);

                        $v_value_tmp['scree_time_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['scree_time_ch']);
                        $v_value_tmp['supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['supply_status_ch']);



                        fputcsv($fp, $v_value_tmp);

                    }
                }
                // 回写数据到pur_center_data 表
                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
            } while ($redirectStdout);

            //  上传文件到文件服务器
            $file_data = $this->upload_file($product_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        } else {
            $tax_list_tmp = [];
            for ($i = 1; $i <= $page; ++$i) {
                $offset = ($i - 1) * $limit;
                $result = $this->product_mod->get_product_list($params, $limit, $offset, $field, '', false);
                $formart_data = $this->product_mod->formart_product_list($result['data_list']);

                if (!empty($formart_data)) {

                    foreach ($formart_data as $key => $value) {
                        $v_value_tmp = [];
                        $v_value_tmp['audit_status'] = $value['audit_status'];
                        $v_value_tmp['audit_user_name'] = $value['audit_user_name'];
                        $v_value_tmp['audit_time'] = $value['audit_time'];
                        $v_value_tmp['audit_remark'] = $value['audit_remark'];
                        $v_value_tmp['create_user_name'] = $value['create_user_name'];
                        $v_value_tmp['create_time'] = $value['create_time'];
                        $v_value_tmp['product_img_url'] = $value['product_img_url'];
                        $v_value_tmp['sku'] = $value['sku'];
                        $v_value_tmp['product_name'] = $value['product_name'];
                        $v_value_tmp['product_status'] = $value['product_status'];
                        $v_value_tmp['product_line_name'] = $value['product_line_name'];
                        $v_value_tmp['develop_user_name'] = $value['develop_user_name'];
                        $v_value_tmp['develop_time'] = $value['develop_time'];
                        $v_value_tmp['old_supplier_price'] = $value['old_supplier_price'];
                        $v_value_tmp['new_supplier_price'] = $value['new_supplier_price'];
                        $v_value_tmp['old_ticketed_point'] = $value['old_ticketed_point'];
                        $v_value_tmp['new_ticketed_point'] = $value['new_ticketed_point'];
                        $v_value_tmp['old_supplier_name'] = $value['old_supplier_name'];
                        $v_value_tmp['new_supplier_name'] = $value['new_supplier_name'];
                        $v_value_tmp['old_product_link'] = $value['old_product_link'];
                        $v_value_tmp['new_product_link'] = $value['new_product_link'];
                        $v_value_tmp['is_sample'] = $value['is_sample'];
                        if ($value['old_is_purchasing'] == 1) {
                            $v_value_tmp['old_is_purchasing'] = "否";
                        } else {
                            $v_value_tmp['old_is_purchasing'] = "是";
                        }

                        if ($value['new_is_purchasing'] == 1) {
                            $v_value_tmp['new_is_purchasing'] = "否";
                        } else {
                            $v_value_tmp['new_is_purchasing'] = "是";
                        }
                        $v_value_tmp['sample_check_result'] = $value['sample_check_result'];
                        $v_value_tmp['sample_user_name'] = $value['sample_user_name'];
                        $v_value_tmp['sample_time'] = $value['sample_time'];
                        $v_value_tmp['sample_remark'] = $value['sample_remark'];
                        $v_value_tmp['sample_remark'] = $value['sample_remark'];
                        $v_value_tmp['create_remark'] = $value['create_remark'];
                        if (!empty($value['update_content'])) {
                            $content_string = '';
                            foreach ($value['update_content'] as $content_key => $content_value) {
                                $content_string .= $content_value . ",";
                                $v_value_tmp['update_content'] = $content_string;
                            }

                        } else {
                            $v_value_tmp['update_content'] = '';
                        }
                        $v_value_tmp['reason'] = $value['reason'];
                        $tax_list_tmp[] = $v_value_tmp;
                    }


                }

            }
            $result = array(
                'tax_list_tmp' => $tax_list_tmp,
                'export_mode' => $export_mode,
                'field_img_name' => array('产品图片'),
                'field_img_key' => array('product_img_url'),
            );

            $this->success_json($result);
        }
    }

    function page_array($count, $page, $array, $order)
    {
//        global $countpage; #定全局变量
        $page = (empty($page)) ? '1' : $page; #判断当前页面是否为空 如果为空就表示为第一页面
        $start = ($page - 1) * $count; #计算每次分页的开始位置
        if ($order == 1) {
            $array = array_reverse($array);
        }
//        $totals = count($array);
//        self::$countpage = ceil($totals / $count); #计算总页面数
        $pagedata = array();
        $pagedata = array_slice($array, $start, $count);
        return $pagedata;  #返回查询数据
    }

    public
    function upload_file($filepath)
    {
        $java_url_list = '';
        $return = [
            'code' => 0,
            'filepath' => '',
            'msg' => '',
        ];
        $java_result = $this->upload_image->doUploadFastDfs('file', $filepath, false);
        if ($java_result['code'] == 200) {
            $java_url_list = $java_result['data'];
            $return['code'] = 200;
            $return['filepath'] = $java_url_list;
            $return['msg'] = '上传成功';
        } else {
//            throw new Exception('');
            $return['msg'] = '文件上传数据库失败';
        }
        return $return;
    }

    public
    function get_queue_data($param)
    {
        $data = '';
//        $data = '{"data":{"id":"212","module_cn_name":"\u91c7\u8d2d\u5355","module_ch_name":"ORDERTACKING","file_name":"http:\/\/192.168.1.34\/uploads\/transit\/pdf\/barcodelabel\/ABD226417_DE-JYOT17300-200_1582686606.pdf","number":"9","progress":null,"data_status":"1","examine_status":"1","add_time":"2020-02-27 14:47:09","add_user_name":"\u9c81\u65ed14592","examine_user_name":"\u9c81\u65ed14592","examine_time":"2020-02-27 18:00:17","condition":"{\"uid\":\"1671\",\"purchase_status\":[\"9\",\"11\"]}","ext":"csv","down_url":null,"remark":" "}}';
//        $data = '{"data":{"id":"233","module_cn_name":"\u91c7\u8d2d\u5355","module_ch_name":"PURCHASEORDER","file_name":"http:\/\/192.168.1.34\/uploads\/transit\/pdf\/barcodelabel\/ABD226417_DE-JYOT17300-200_1582686606.pdf","number":"9","progress":null,"data_status":"2","examine_status":"1","add_time":"2020-02-27 14:47:09","add_user_name":"\u9c81\u65ed14592","examine_user_name":"\u9c81\u65ed14592","examine_time":"2020-02-27 18:00:17","condition":"{\"first_product_line\":null,\"purchase_order_status\":null,\"suggest_order_status\":null,\"sku\":\"\",\"compact_number\":null,\"demand_number\":null,\"buyer_id\":null,\"supplier_code\":null,\"is_drawback\":null,\"is_ali_order\":null,\"product_name\":null,\"is_cross_border\":null,\"pay_status\":null,\"source\":null,\"is_destroy\":null,\"product_is_new\":null,\"purchase_number\":\"\",\"purchase_type_id\":null,\"create_time_start\":\"\",\"create_time_end\":\"\",\"loss_status\":null,\"audit_status\":null,\"need_pay_time_start\":null,\"need_pay_time_end\":null,\"audit_time_start\":null,\"audit_time_end\":null,\"ids\":null,\"is_csv\":\"1\",\"product_status\":null,\"pai_number\":null,\"is_inspection\":null,\"is_ali_abnormal\":null,\"is_ali_price_abnormal\":null,\"warehouse_code\":null,\"account_type\":null,\"is_overdue\":null,\"supplier_source\":null,\"statement_number\":null,\"new\":1,\"search\":\"1671\",\"is_expedited\":null,\"state_type\":null,\"pay_notice\":null,\"entities_lock_status\":null,\"is_invaild\":null,\"lack_quantity_status\":null,\"is_forbidden\":null,\"order_by\":\"\",\"order\":\"\",\"ticketed_point\":null,\"is_relate_ali\":null,\"is_generate\":null,\"is_purchasing\":null,\"barcode_pdf\":null,\"label_pdf\":null,\"is_equal_sup_id\":null,\"is_equal_sup_name\":null,\"is_arrive_time_audit\":null}","ext":"csv","down_url":null,"remark":" "}}';

        return $this->data_center_model->handle_quene_data($data);
    }

    /**
     * 应付款导出任务
     * @author 叶凡立
     */
    public function export_payable_list_handle()
    {
        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9506);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/export_payable_list ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/export_payable_list ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * 供应商余额导出
     * @author Jolon
     */
    public function export_supplier_order_handle()
    {
        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9512);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server_handle/supplier_balance ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server_handle/supplier_balance ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * 应付款管理导出
     * @author 叶凡立
     * @time 2020-09-22
     */
    public function export_payable_list($id = null)
    {
        ini_set('memory_limit', '1500M');
        $res = false;
        if (empty($id)){
            echo '处理ID不能为空';
            return $res;
        }
        $this->load->model('finance/Payment_order_pay_model');
        $param_list = $this->data_center_model->get_items("id = " . $id);
        if(!isset($param_list[0]) || empty($param_list[0])){
            echo '查询不到要处理的数据';
            return $res;
        }
        $param_list = $param_list[0];
        $params = json_decode($param_list['condition'], true);
        $role_list = $param_list['role'];
        if($role_list)$role_list = explode(',', $role_list);
        if (!is_array($params) || count($params) == 0){
            echo '参数不正确，请联系技术人员';
            return $res;
        }
        $type = $params['type'];
        $type_str = $type == 1? "合同": "网采";

        // 检查文件
        $webfront_path = dirname(dirname(APPPATH));
        $template_file = 'cwdc_'.date('YmdHis').mt_rand(1000, 9999) . '.csv';
        $product_file = $webfront_path . '/webfront/download_csv/' . $template_file;
//        $product_file = $webfront_path . '/otest01/end/logs/' . $template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }

        $per_page = 500;
        $i = 1;
        $need_header = true;
        fopen($product_file, 'w');
        $fp = fopen($product_file, "a");
        $source_subject = [
            1 => '合同',
            2 => '网采',
            3 => '对账单'
        ];

        $progress_all = isset($param_list['number']) && $param_list['number'] > 0?(int)$param_list['number']: 1; // 数据总长
        $progress = 0; // 进度

        $status_list = $this->get_handle_status();
        $order_status   = $status_list['order'];
        $warehouse      = $status_list['warehouse'];
        $pay_status     = $status_list['pay_status'];
        $pay_type       = $status_list['pay_type'];
        $check_status   = $status_list['check_status'];
        $loss_status    = $status_list['loss_status'];
        $settlement_method= $status_list['settlement_method'];
        $applicant      = $status_list['purchase_user'];

        $is_has = [];

        do {
            $is_last=true;
            $offsets = ($i - 1) * $per_page;
            $result = $this->Payment_order_pay_model->new_get_pay_list($params, $type, $offsets, $per_page, '', 0, 'export', $role_list);
            if(!$result || count($result) == 0){
                $is_last=false;
                break;
            }
            // 添加头部
            if($need_header){
                $heads = [
                    '合同号','请款单号','采购单号','网拍账号','拍单号','采购仓库','采购员','申请日期','付款日期','一级产品线','sku',
                    '产品名称','采购单价','采购数量','到货数量','入库数量','报损数量','报损状态','实际入库数量','次品数量','多货数量','商品额',
                    '运费','优惠额','加工费','请款金额','供应商编码','供应商名称','验货状态','备注','付款状态','采购单状态',
                    '支付方式','支付平台','结算方式','对账单号','是否1688下单','请款人','付款人','付款回单编号','付款流水号','是否对账单请款','支付账号'
                ];
                $need_header = false;
                foreach($heads as &$v){
                    $v = iconv('UTF-8','GBK//IGNORE',$v);
                }
                fputcsv($fp,$heads);
                unset($heads);
            }

            foreach ($result as $val){
                $product_money = $freight = $discount = $process_cost = $pay_price = 0;
                if(!in_array($val['requisition_number'],$is_has)){
                    $product_money = $val['product_money'];
                    $freight = $val['freight'];
                    $discount = $val['discount'];
                    $process_cost = $val['process_cost'];
                    $pay_price = $val['pay_price'];
                    $is_has[] = $val['requisition_number'];
                }

                $rows=[
                    $val['source'] == 1 ? $val['pur_number'] :'',
                    $val['requisition_number']??'',
                    $val['purchase_number']??'',
                    $val['purchase_account']??'',
                    $val['pai_number']?$val['pai_number']."\t":'',
                    $val['warehouse_code'] && in_array($val['warehouse_code'], array_keys($warehouse))?$warehouse[$val['warehouse_code']]:'',
                    $val['buyer_name']??'',
                    $val['application_time']??'',
                    $val['payer_time']??'',
                    $val['product_line_name']??'',
                    $val['sku']?$val['sku']."\t":'',
                    $val['product_name']??'',
                    $val['purchase_unit_price']??'',
                    $val['purchase_amount']??'',
                    $val['receive_amount']??'',
                    $val['upselft_amount']??'',
                    $val['loss_amount']??'',
                    $val['loss_status'] && in_array($val['loss_status'], array_keys($loss_status))?$loss_status[$val['loss_status']]:'',
                    $val['upselft_amount_sj']??'',
                    $val['defective_num'] ??'',
                    $val['instock_more_qty'] ??'',
                    $product_money,
                    $freight,
                    $discount,
                    $process_cost,
                    $pay_price,
                    $val['supplier_code']??'',
                    $val['supplier_name']??'',
                    in_array($val['check_status'], array_keys($check_status))?$check_status[$val['check_status']]:'',
                    $val['payment_notice']??'',
                    $val['pay_status'] && in_array($val['pay_status'], array_keys($pay_status))?$pay_status[$val['pay_status']]:'',
                    $val['purchase_order_status'] && in_array($val['purchase_order_status'], array_keys($order_status))?$order_status[$val['purchase_order_status']]:'',
                    $val['pay_type'] && in_array($val['pay_type'], array_keys($pay_type))?$pay_type[$val['pay_type']]:'',
                    $val['payment_platform']??'',
                    $val['settlement_method'] && in_array($val['settlement_method'], array_keys($settlement_method))?$settlement_method[$val['settlement_method']]:'',
                    $val['subject_statement']??'',
                    $val['is_ali_order'] == 1?'是':'否',
                    $val['applicant'] && in_array($val['applicant'], array_keys($applicant))? $applicant[$val['applicant']]:'',
                    $val['payer_name']??'',
                    $val['pur_tran_num']??'',
                    $val['trans_orderid']??'',
                    $val['source_subject']!=3?"否":'是',
                    $val['account_number']??'',
                ];

                $row_list = [];
                foreach ($rows as $v) {
                    if(preg_match("/[\x7f-\xff]/",$v)){
                        $v = stripslashes(iconv('UTF-8','GBK//IGNORE', $v));//中文转码
                    }
                    if(is_numeric($v) && strlen($v) > 9){
                        $v =  $v."\t";//避免大数字在csv里以科学计数法显示
                    }
                    $row_list[]=$v;
                }
                fputcsv($fp,$row_list);
                unset($row_list);
            }

            if($per_page * $i < $progress_all){
                $progress = $per_page * $i;
            }else{
                $progress = $progress_all;
            }
            $this->data_center_model->updateCenterData($id, ['progress' => $progress]);
            $i++;
        } while ($is_last);
        $this->data_center_model->updateCenterData($id, ['progress' => $progress_all]);

        $file_data = $this->upload_file($product_file);
        if (!empty($file_data['code'] == 200)) {
            $res = $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
        }

        echo '完结，撒花';
        return $res;
    }

    /**
     * 获取状态
     */
    private function get_handle_status()
    {
        $res = [
            "order"         => getPurchaseStatus(),
            "warehouse"     => [],
            "pay_status"    => getPayStatus(),
            "pay_type"=> [
                1 => "线上支付宝",
                2 => "线下境内",
                3 => "线下境外",
                4 => "paypal",
                5 => "银行公对公",
                6 => "p卡"
            ],
            "check_status"  => [
                0 => "无需验货",
                1=>"待采购确认",
                2=>"待品控确认",
                3=>"免检待审核",
                4=>"免检驳回",
                5=>"品控验货中",
                6=>"不合格待确认",
                7=>"转合格申请中",
                8=>"免检",
                9=>"转IQC验货",
                10=>"验货合格",
                11=>"验货不合格"
            ],
            "loss_status"   => [
                0 => "待经理审核",
                1 => "经理驳回",
                2 => "待财务审核",
                3 => "财务驳回",
                4 => "已通过"
            ],
            "settlement_method"=>[],
            "purchase_user"=>[],
        ];
        // 仓库
        $this->load->model('finance/Payment_order_pay_model');
        $res['warehouse'] = $this->Payment_order_pay_model->get_warehouse_data();
        $res['settlement_method'] = $this->Payment_order_pay_model->get_settlement_method_data();
        $res['purchase_user'] = $this->Payment_order_pay_model->get_purchase_user_data();
        return $res;
    }

    /**
     * 财务付款统计表导出任务
     * @author 叶凡立
     */
    public function export_payment_list_handle()
    {
        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9507);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'export_payment_list_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/export_payment_list ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/export_payment_list ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * 财务付款统计表
     * @author 叶凡立
     */
    public function export_payment_list($id=null)
    {
        ini_set('memory_limit', '1500M');
        $res = false;
        if (empty($id)){
            echo '处理ID不能为空';
            return $res;
        }
        $this->load->model('finance/Payment_order_pay_model');
        $param_list = $this->data_center_model->get_items("id = " . $id);
        if(!isset($param_list[0]) || empty($param_list[0])){
            echo '查询不到要处理的数据';
            return $res;
        }
        $param_list = $param_list[0];
        $params = json_decode($param_list['condition'], true);

        if (!is_array($params) || count($params) == 0){
            echo '参数不正确，请联系技术人员';
            return $res;
        }

        // 检查文件
        $webfront_path = dirname(dirname(APPPATH));
        $template_file = 'payment_list_'.date('YmdHis').mt_rand(1000, 9999) . '.csv';
        $product_file = $webfront_path . '/webfront/download_csv/' . $template_file;
//        $product_file = '/mnt/c/www/otest01/end/logs/' . $template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }

        $per_page = 1000;
        $i = 1;
        $need_header = true;
        fopen($product_file, 'w');
        $fp = fopen($product_file, "a");
        $this->load->model('financial_statements/Payment_order_report_model','report');

        $progress = 0; // 进度

        do {
            // 添加头部
            if($need_header){
                $heads =['付款时间','采购类型','采购主体','供应商编码','供应商名称','是否退税','业务线','请款类型',
                    '摘要','我司交易名称','我司开户行','我司交易账号','k3账户','交易对方户名','交易对方开户行','交易对方账号',
                    '合同号','对账单号','采购单号','拍单号','采购员','结算方式','支付方式','付款回单编号','请款单号','商品额','运费','优惠额','加工费',
                    '代采佣金','请款总额','付款人','备注'];
                $need_header = false;
                foreach($heads as &$v){
                    $v = iconv('UTF-8','GBK//IGNORE',$v);
                }
                fputcsv($fp,$heads);
                unset($heads);
            }
            $is_last=true;
            $offsets = ($i - 1) * $per_page;
            $result = $this->report->get_pay_order_report_new($params, $offsets, $per_page, 1, 'export');
            if(!$result || !isset($result['values']) || count($result['values']) == 0){
                $is_last=false;
                break;
            }

            $x = 0;
            foreach ($result['values'] as $val){
                $abstract_remark = $val['abstract_remark'];

                $receive_unit = $val['receive_unit'];
                // 交易名称=香港易佰,我司开户行≠光大银行
                if($val['purchase_name'] == 'HKYB' AND stripos($val['pay_branch_bank'],"光大银行") === false){
                    $receive_unit = $this->convertReceiveUnit($receive_unit);
                }

                try {
                    $row_list = [];
                    $row=[
                        $val['payer_time'],
                        $val['source'],//采购来源
                        $val['purchase_name'],
                        $val['supplier_code'],
                        $val['supplier_name'],
                        $val['is_drawback'],
                        $val['purchase_type_id'],//业务线
                        $val['pay_category'],
                        $abstract_remark,//摘要
                        $val['pay_number'],//我司交易名称
                        $val['pay_branch_bank'],//我司开户行
                        $val['pay_account_number'],//我司交易账号
                        $val['k3_bank_account']."\t",//k3账户
                        $receive_unit,//交易对方户名
                        $val['payment_platform_branch'],//交易对方开户行
                        $val['receive_account'],//交易对方账号
                        $val['compact_number'],//合同号
                        $val['statement_number'],//对账单号
                        $val['pur_number'],//订单号
                        $val['pai_number'],//拍单号
                        $val['buyer_name'],//采购员
                        $val['settlement_method'],//结算方式
                        $val['pay_type'],//支付方式
                        $val['pur_tran_num'],//付款回单编号
                        $val['requisition_number'],//请款单号
                        $val['product_money'],//商品额
                        $val['freight'],//运费
                        $val['discount'],//优惠额
                        $val['process_cost'],//加工费
                        $val['commission'],//代采佣金
                        $val['pay_price'],//请款总额
                        $val['payer_name'],//付款人
                        $val['finance_report_remark'],// 备注
                    ];
                    foreach ($row as $vvv) {
                        if(preg_match("/[\x7f-\xff]/",$vvv)){
                            $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//中文转码
                        }
                        if(is_numeric($vvv) && strlen($vvv) > 9){
                            $vvv =  $vvv."\t";//避免大数字在csv里以科学计数法显示
                        }
                        $row_list[]=$vvv;
                    }
                    fputcsv($fp,$row_list);
                    unset($row_list);
                    unset($row);
                    $x ++;
                }catch (Exception $e){}
            }

            $i++;
            $progress = $progress + $x;
            $this->data_center_model->updateCenterData($id, ['progress' => $progress]);
        } while ($is_last);

        $file_data = $this->upload_file($product_file);
        if (!empty($file_data['code'] == 200)) {
            $res = $this->data_center_model->updateCenterData($id, ['file_name' => $template_file, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
        }
        echo '完结，撒花';
        return $res;
    }

    /**
     * 转换 交易对方户名
     * @param $receive_unit
     * @return mixed|string
     * @exp 黄骅市瑞恒科教仪器制造有限公司(王称)  =》 王称(黄骅市瑞恒科教仪器制造有限公司)
     */
    public function convertReceiveUnit($receive_unit){
        $receive_unit_new = str_replace(['（','）'],['(',')'],$receive_unit);
        // 标记是否转换过，需要再转换回去
        if(strlen($receive_unit) != strlen($receive_unit_new)){
            $flag = true;
        }else{
            $flag = false;
        }

        preg_match('/((?<=[\(])\S+[^\)])/',$receive_unit_new,$matt);// 取的括号中的内容
        preg_match('/([^\(]+)/',$receive_unit_new,$matt2);//取得括号前的内容
        if(!empty($matt) and isset($matt[1]) and !empty($matt2) and isset($matt2[1])){
            $begin = $matt2[1];
            $end = $matt[1];

            $receive_unit = $end.'('.$begin.')';
        }else{
            // 转换失败
        }

        if($flag){
            $receive_unit = str_replace(['(',')'],['（','）'],$receive_unit);
        }
        return $receive_unit;
    }

    /**
     * 对账单 合同单异步任务处理逻辑（此服务部署在web3上面的）
     * @link export_server/compact_statement_download_handle
     * @author Jolon
     */
    public function compact_statement_download_handle()
    {
        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 9508);// 此服务部署在web3上面的
        echo "start work\n";
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});
        echo "start work2\n";
        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });
        echo "start work3\n";
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'compact_statement_download_execute_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server_handle/compact_statement_download_execute ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server_handle/compact_statement_download_execute ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * 备选供应商导出信息
     * @author:luxu
     * @time:2021年4月29号
     **/
    public function alternative_download_file(){

        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 2029);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });

        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'alternative_download_file' . date('Ymd') . '.txt';
            exec('/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/alternative_download_file_data ' . $data['id']);
            echo '/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/alternative_download_file_data ' . $data['id'];


            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    public function alternative_download_file_data($id=45184){
        echo "start........................";
        ini_set('memory_limit', '1500M');
        $this->load->model('product/Alternative_suppliers_model');
        $export_user = [];
        $result = false;
        if (!empty($id)) {
            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $condition = json_decode($params['condition'], true);
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);

            $this->load->helper('export_csv');
            $this->load->helper('status_product');
            $this->load->helper('status_order');

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'alternative_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $product_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($product_file)) {
                unlink($product_file);
            }

            $total = $params['number'];
            $fp = fopen($product_file, "a+");
            $heads = [
                      'sku',
                      '产品名称',
                      '产品状态',
                      '是否代采',
                      '一级产品线',
                      '供货关系',
                      '数据来源','供应商名称',
                      '结算方式','合作状态'
                      ,'供应商来源','最近一次采购日期'
                      ,'未税单价','采购数量','采购连接',
                      '最小起订量','交期','是否包邮','采购员','开发人员','修改人',
                      '修改时间'
                     ];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            $limit = 500;
            $page = ceil($total/$limit);
            for ($i = 1; $i <= $page; $i++) {
                $condition['limit'] = $limit;
                $condition['offset'] = 1;
                $condition['offset'] = ($condition['offset'] - 1) * $condition['limit'];
                $result = $this->Alternative_suppliers_model->get_alternative_supplier($condition);
                $purchase_tax_list_export = $result['list'];
                if ($purchase_tax_list_export) {

                    foreach ($purchase_tax_list_export as $value) {
                        $value_tmp['sku']     = iconv("UTF-8", "GBK//IGNORE", $value['sku']);//sku
                        $value_tmp['product_name']     = iconv("UTF-8", "GBK//IGNORE", $value['product_name']);//产品名称
                        $value_tmp['product_status']       = iconv("UTF-8", "GBK//IGNORE", $value['product_status_ch']);//产品状态
                        $value_tmp['is_purchasing_ch']      = iconv("UTF-8", "GBK//IGNORE", $value['is_purchasing_ch']);// 是否代采
                        $value_tmp['product_line_datas']       = iconv("UTF-8", "GBK//IGNORE", $value['product_line_datas']);//采购单号
                        $value_tmp['relationship'] = iconv("UTF-8", "GBK//IGNORE", $value['relationship']);//采购主体
                        $value_tmp['source']         = iconv("UTF-8", "GBK//IGNORE", $value['source']);//合同单号
                        $value_tmp['supplier_name']                = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);
                        $value_tmp['settlement_ch']                = iconv("UTF-8", "GBK//IGNORE", $value['settlement_ch']);
                        $value_tmp['cooper_status_ch']           = iconv("UTF-8", "GBK//IGNORE", $value['cooper_status_ch']);//申请人备注
                        $value_tmp['supplier_source_ch']  = iconv("UTF-8", "GBK//IGNORE", $value['supplier_source_ch']);//退款类型
                        $value_tmp['desc_audit_time']      = iconv("UTF-8", "GBK//IGNORE", $value['desc_audit_time']);//退款原因
                        $value_tmp['purchase_price']      = iconv("UTF-8", "GBK//IGNORE", $value['purchase_price']);//退款渠道
                        $value_tmp['confirm_amount']      = iconv("UTF-8", "GBK//IGNORE", $value['confirm_amount']);//退款时间
                        $value_tmp['product_cn_link']      = iconv("UTF-8", "GBK//IGNORE", "'".$value['product_cn_link']);//1688拍单号
                        $value_tmp['starting_qty']       = iconv("UTF-8", "GBK//IGNORE", $value['starting_qty']);//异常单号
                        $value_tmp['devliy']     = iconv("UTF-8", "GBK//IGNORE", $value['devliy']);// 退款金额
                        $value_tmp['is_shipping_ch'] = iconv("UTF-8", "GBK//IGNORE", $value['is_shipping_ch']);//退款流水号
                        $value_tmp['supplier_buyer_user'] = iconv("UTF-8", "GBK//IGNORE", "'" . $value['supplier_buyer_user']);//退货物流单号

                        $value_tmp['create_user_name']   = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);//轨迹状态
                        $value_tmp['update_user']        = iconv("UTF-8", "GBK//IGNORE", $value['update_user']);//申请时间
                        $value_tmp['update_time']    = iconv("UTF-8", "GBK//IGNORE", $value['update_time']); //收款时间
                        $tax_list_tmp = $value_tmp;
                        fputcsv($fp, $tax_list_tmp);
                    }

                    if ($i * $limit < $total) {
                        $cur_num = $i * $limit;
                    } else {
                        $cur_num = $total;
                    }
                    $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
                }

            }

            $file_data = $this->upload_file($product_file);
            print_r($file_data);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        } else {
            echo '没有数据处理';
        }
//        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        return $result;
    }


    public function product_import_data_swoole(){

        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 20612);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });

        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'alternative_download_file' . date('Ymd') . '.txt';
            exec('/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/product_import_data ' . $data['id']);
            echo '/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/product_import_data ' . $data['id'];


            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }


    /**
     * 产品管理导入数据脚本
     * @author:luxu
     * @time:2021年6月10号
     **/
    public function product_import_data($id=NULL){

        if( $id == NULL ){

            $id = isset($_GET['id'])?$_GET['id']:NULL;
        }
        if(!empty($id)){

            $productDatas = $this->product_model->get_product_import_data($id);
            if(!empty($productDatas)){

                ini_set('memory_limit','1024M');
                include APPPATH.'third_party/PHPExcel/PHPExcel/IOFactory.php';
                //echo APPPATH.'third_party/PHPExcel/PHPExcel/IOFactory.php';die();
                $file_path = $productDatas['file_path'];
                $PHPReader = PHPExcel_IOFactory::createReader('CSV')
                    ->setDelimiter(',')
                    ->setInputEncoding('GBK') //不设置将导致中文列内容返回boolean(false)或乱码
                    ->setEnclosure('"')
                    ->setSheetIndex(0);
                $PHPReader      = $PHPReader->load($file_path);

                $currentSheet   = $PHPReader->getSheet();
                $sheetData      = $currentSheet->toArray(null,true,true,true);
                $out = array ();
                $n = 0;
                foreach($sheetData as $data){

                    $num = count($data);
                    $i =0;
                    foreach($data as $data_key=>$data_value){
                        $out[$n][$i] = trim($data_value);
                        ++$i;
                    }
                    $n++;
                }

                if(!empty($out)){

                    $order_logs =[

                        'handle_status' => 1,
                        'handle_action' => "产品管理-SKU修改导入",
                        'handle_msg' =>'',
                        'handle_all' => count($out),
                        'error_num' => 0,
                        'success_num' => 0,
                        'handle_at' => date("Y-m-d H:i:s",time()),
                        'create_at' => date("Y-m-d H:i:s",time()),
                        'user_name' => $productDatas['uid']
                    ];
                    $this->db->insert("handle_create_order_log",$order_logs);

                    $logsIds = $this->db->insert_id("handle_create_order_log");
                    unset($out[0]);
                    foreach($out as $key=>$value){

                        $product_supplier = $value[11];
                        $supply_status_flag = NULL;
                        $encode = mb_detect_encoding($product_supplier, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));
                        $skuMessages = $this->db->from("product")->where("sku",$value[0])->get()->row_array();

                        if(empty($value[1])){

                            $value[1] = $skuMessages['ticketed_point'];
                        }

                        if( empty($value[3])){

                            $value[3] = $skuMessages['coupon_rate'];
                        }

                        if( empty($value[6])){

                            $value[6] = $skuMessages['ali_ratio_own'];
                        }

                        if( empty($value[7])){

                            $value[7] = $skuMessages['ali_ratio_out'];
                        }

                        if( empty($value[15])){

                            $value[15] = $skuMessages['inside_number'];
                        }

                        if( empty($value[16])){

                            $value[16] = $skuMessages['box_size'];
                        }

                        if( empty($value[17])){
                            $value[17] = $skuMessages['outer_box_volume'];
                        }

                        if( empty($value[18])){
                            $value[18] = $skuMessages['product_volume'];
                        }

                        if( empty($value[19])){
                            $value[19] = $skuMessages['devliy'];
                        }






                        if((empty($product_supplier) || !in_array($product_supplier,['正常','停产','断货','停产找货'])) )
                        {

                            if( $encode == 'ASCII' && $product_supplier=='u505cu4ea7u627eu8d27'){

                                $supply_status_flag = 10;
                            }else {
                                $value[23] = "货源状态填写错误";
                                $error_data[] = $value;
                                continue;
                            }
                        }else{

                            if( $product_supplier == "正常")
                            {
                                $supply_status_flag = 1;
                            }

                            if( $product_supplier == "停产")
                            {
                                $supply_status_flag = 2;
                            }

                            if( $product_supplier == "断货")
                            {
                                $supply_status_flag =3;
                            }

                            if( $product_supplier == "停产找货"){

                                $supply_status_flag = 10;
                            }
                        }

                        $is_purchasing = $value[10];
                        $is_purchasing_flag = NULL;
                        if( empty($is_purchasing) || !in_array($is_purchasing,['是','否']))
                        {
                            $value[23] = "是否代采填写错误";
                            $error_data[] = $value;
                            continue;
                        }else{

                            if( $is_purchasing == "是")
                            {
                                $is_purchasing_flag =2;
                            }

                            if( $is_purchasing == "否")
                            {
                                $is_purchasing_flag =1;
                            }
                        }

                        $is_customizedData = $value[20];
                        if(empty($is_customizedData)){

                            if($skuMessages['is_customized'] == 1){

                                $is_customizedData = "是";
                            }else{
                                $is_customizedData = "否";
                            }
                        }
                        if( $is_customizedData == "是")
                        {
                            $is_customizedData_ch =1;
                        }else{
                            $is_customizedData_ch =2;
                        }

                        $is_long_delivery = $value[21];
                        if(empty($is_long_delivery)){

                            if($skuMessages['long_delivery'] == 1){

                                $is_long_delivery = "否";
                            }else{
                                $is_long_delivery = "是";
                            }
                        }

                        if( $is_long_delivery == "否"){

                            $is_long_delivery_ch = 1;
                        }else{
                            $is_long_delivery_ch =2;
                        }

                        $is_shipping = $value[22];

                        if( $is_shipping == "是" || $is_shipping == "包邮"){

                            $is_shipping_value =1;
                        }else{
                            $is_shipping_value =2;
                        }


                        $verify_success_data[] = $value;
                        $sku_lists[] = trim($value[0]);
                        $supplier_price[trim($value[0])] = $value[2];
                        $ticketed_point[trim($value[0])] = $value[1];
                        $new_product_link[trim($value[0])] = $value[9];
                        $new_is_sample[trim($value[0])] = $value[8];
                        $supply_status[trim($value[0])] = $supply_status_flag;
                        $create_remark[trim($value[0])] = $value[13];
                        $supplier_name[trim($value[0])] = $value[12];
                        $reason[trim($value[0])] = $value[14];
                        $inside_number[trim($value[0])] = $value[15];
                        $box_size[trim($value[0])] = $value[16];
                        $outer_box_volume[trim($value[0])] = $value[17];
                        $product_volume[trim($value[0])] = $value[18];
                        $new_starting_qty[trim($value[0])] = $value[4];
                        $new_starting_qty_unit[trim($value[0])] = $value[5];
                        $new_ali_ratio_own[trim($value[0])] = (int)$value[6];
                        $new_ali_ratio_out[trim($value[0])] = (int)$value[7];
                        $new_coupon_rate[trim($value[0])] = ($value[3])/100;
                        $new_is_purchasing[trim($value[0])] = $is_purchasing_flag;
                        $new_devliy[trim($value[0])] = $value[19];
                        $new_iscustomized[trim($value[0])] = $is_customizedData_ch;
                        $new_long_delivery_ch[trim($value[0])] = $is_long_delivery_ch;
                        $new_is_shipping[trim($value[0])] = $is_shipping_value;
                        $value[0] = trim($value[0]);
                        $product_message = $this->product_model->get_product_one(['sku'=>$value[0]]);

                        $supplier_message = $this->product_model->get_supplier_data($value[12],['status'=>[1,6],'supplier_source'=>[1]]);
                        if( !empty($value[12]) && $value[12] != $product_message['supplier_name'])
                        {

                            if(empty($supplier_message))
                            {
                                $value[23] = "供应商不存在,只允许导入启用状态的常规供应商";
                                $error_data[] = $value;
                                continue;
                            }
                            $new_supplier_code[$value[0]] = $supplier_message['supplier_code'];
                            $new_supplier_name[$value[0]] = $supplier_message['supplier_name'];
                        }else {
                            $new_supplier_code[$value[0]] = isset($product_message['supplier_code']) ? $product_message['supplier_code'] : NULL;
                            $new_supplier_name[$value[0]] = isset($product_message['supplier_name']) ? $product_message['supplier_name'] : NULL;
                        }
                    }

                    $product_send_data = array(
                        'sku_list' => implode(",",$sku_lists),
                        'new_supplier_price' => $supplier_price,
                        'new_ticketed_point' => $ticketed_point,
                        'new_product_link' => $new_product_link,
                        'is_sample' => $new_is_sample,
                        'create_remark' =>$create_remark,
                        'new_starting_qty' =>$new_starting_qty,
                        'new_starting_qty_unit' => $new_starting_qty_unit,
                        'new_ali_ratio_own' =>$new_ali_ratio_own,
                        'new_ali_ratio_out' => $new_ali_ratio_out,
                        'coupon_rate' => $new_coupon_rate,
                        'new_supplier_name' => $new_supplier_name,
                        'new_supplier_code' => $new_supplier_code,
                        'supply_status' => $supply_status,
                        'is_purchasing'=>$new_is_purchasing,
                        'is_force_submit'=>1,
                        'reason' =>$reason,
                        'inside_number' => $inside_number,
                        'box_size'      => $box_size,
                        'outer_box_volume' => $outer_box_volume,
                        'product_volume' =>$product_volume,
                        'devliy' => $new_devliy,
                        'is_customized' =>$new_iscustomized,
                        'long_devliy' => $new_long_delivery_ch,
                        'is_shipping' =>$new_is_shipping
                    );
                    $result = $this->product_model->update_product_save(json_encode($product_send_data),True,$productDatas['uid'],$this->product_mod_audit_other,$productDatas['user_id']);
                    //print_r($result);die();
                    $logs['return_logs'] = json_encode($result);
                    $logs['is_read'] =1;
                    $this->product_model->update_product_data($id,$logs);
                    $messvalue['pushMessage'] = "总共导入:".count($out)."条数据,"."成功:"
                        .$result['success_total'].",失败：".$result['error_total'];
                    if($result['error_total']>0){
                        $messvalue['pushMessage'].=",失败原因:".implode(",",array_values($result['error_sku']));

                        foreach($result['error_sku'] as $error_key=>$error_val){
                            
                            $messvalue['pushMessage'].= " SKU:".$error_key."，原因:".$error_val.".";
                        }
                    }
                    $messvalue['type'] = 'SKU修改导入';
                    $messvalue['module'] = 'product_import_data';
                    $messvalue['create_time'] = date("Y-m-d H:i:s",time());
                    $messvalue['param'] = '';
                    $messvalue['recv_name'] = $productDatas['uid'];
                    $messvalue['apply_id'] = '';

                    $ci = get_instance();
                    //获取redis配置
                    $ci->load->config('mongodb');
                    $host = $ci->config->item('mongo_host');
                    $port = $ci->config->item('mongo_port');
                    $user = $ci->config->item('mongo_user');
                    $password = $ci->config->item('mongo_pass');
                    $author_db = $ci->config->item('mongo_db');
                    $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
                    $author_db = $author_db;

                    $bulk = new MongoDB\Driver\BulkWrite();
                    $messvalue['is_read'] = 1;
                    $mongodb_result = $bulk->insert($messvalue);
                    $mongodb->executeBulkWrite("{$author_db}.message", $bulk);

                    $order_logs =[

                        'handle_status' => 2,
                        'handle_action' => "产品管理-SKU修改导入",
                        'handle_msg' =>'处理完成',
                        'handle_all' => count($out),
                        'error_num' => $result['error_total'],
                        'success_num' => $result['success_total'],
                        'end_at' => date("Y-m-d H:i:s",time()),

                    ];

                    $this->db->where("id",$logsIds)->update("handle_create_order_log",$order_logs);




                }

            }
        }
    }

    public function import_abnormal_data_server(){

        //tcp服务器
        $server = new \Swoole\Server(SWOOLE_SERVER, 2025);
        //开启4个进程执行
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});

        //收到请求后触发
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "异步任务投递成功: id=$task_id\n";
            $server->send($fd, "数据已接收，处理中...");
        });

        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "处理异步任务中。\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'alternative_download_file' . date('Ymd') . '.txt';
            exec('/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/import_abnormal_data ' . $data['id']);
            echo '/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/import_abnormal_data ' . $data['id'];


            $server->finish('完成处理 OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "异步任务[$task_id] 处理完成: $data" . PHP_EOL;
            echo '开启下一个任务';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    public function import_abnormal_data($id=NULL){

        if( $id == NULL ){

            $id = isset($_GET['id'])?$_GET['id']:NULL;
        }

        if( NULL != $id){

            $par = $this->data_center_model->get_items("id = " . $id);
            $params = $par[0];
            $condition = json_decode($params['condition'], true);
            $this->data_center_model->updateCenterData($id, ['data_status' => 1]);

            $this->load->helper('export_csv');
            $this->load->helper('status_product');
            $this->load->helper('status_order');

            $webfront_path = dirname(dirname(APPPATH));
            $file_name = 'imporve_abnormal_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
            $product_file = $webfront_path . '/webfront/download_csv/' . $file_name;
            if (file_exists($product_file)) {
                unlink($product_file);
            }

            $total = $params['number'];
            $fp = fopen($product_file, "a+");
            $heads = [
                '创建时间',
                '创建人',
                '登记仓库',
                '供应商名称',
                '供应商CODE',
                '问题类型',
                '供应商回复',
                '异常SKU数量',
                '到货数量',
                 '异常占比'
                ,'改善结果',
                '更新人'
                ,'更新时间',
                '采购员','采购组别',
                '组产品线'
            ];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            $limit = 500;
            $page = ceil($total/$limit);
            $this->load->model('abnormal/Abnormal_quality_model','Abnormal_quality_model');

            for ($i = 1; $i <= $page; $i++) {
                $condition['limit'] = $limit;
                $condition['offset'] = 1;
                $condition['offset'] = ($condition['offset'] - 1) * $condition['limit'];

                $result = $this->Abnormal_quality_model->get_Abnormal_list_data($condition);

                $purchase_tax_list_export = $result['list'];
                if ($purchase_tax_list_export) {
                    foreach ($purchase_tax_list_export as $value) {
                        $value_tmp['create_time']     = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);//sku
                        $value_tmp['create_buyer_name']     = iconv("UTF-8", "GBK//IGNORE", $value['create_buyer_name']);//产品名称
                        $value_tmp['warehouse_name']       = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_name']);//产品状态
                        $value_tmp['supplier_name']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);// 是否代采
                        $value_tmp['supplier_code']       = iconv("UTF-8", "GBK//IGNORE", $value['supplier_code']);//采购单号
                        $value_tmp['problem_name'] = iconv("UTF-8", "GBK//IGNORE", $value['problem_name']);//采购主体
                        $value_tmp['supplier_reply']         = iconv("UTF-8", "GBK//IGNORE", $value['supplier_reply']);//合同单号
                        $value_tmp['exception_number']                = iconv("UTF-8", "GBK//IGNORE", $value['exception_number']);
                        $value_tmp['instock_qty']                = iconv("UTF-8", "GBK//IGNORE", $value['instock_qty']);
                        $value_tmp['proportion']           = iconv("UTF-8", "GBK//IGNORE", $value['proportion']);//申请人备注

                        $value_tmp['improve_ch']  = iconv("UTF-8", "GBK//IGNORE", $value['improve_ch']);//退款类型
                        $value_tmp['update_buyer_name']      = iconv("UTF-8", "GBK//IGNORE", $value['update_buyer_name']);//退款原因
                        $value_tmp['update_time']      = iconv("UTF-8", "GBK//IGNORE", $value['update_time']);//退款渠道
                        $value_tmp['buyer_name']      = iconv("UTF-8", "GBK//IGNORE", $value['buyer_name']);//退款时间
                        $value_tmp['group_name']      = iconv("UTF-8", "GBK//IGNORE", "'".$value['group_name']);//1688拍单号
                        $value_tmp['first_product_line']       = iconv("UTF-8", "GBK//IGNORE", $value['first_product_line']);
                        $tax_list_tmp = $value_tmp;
                        fputcsv($fp, $tax_list_tmp);
                    }

                    if ($i * $limit < $total) {
                        $cur_num = $i * $limit;
                    } else {
                        $cur_num = $total;
                    }
                    $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
                }

            }
            $file_data = $this->upload_file($product_file);
            if (!empty($file_data['code'] == 200)) {
                // 回写数据到pur_center_data 表
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }



        }


    }

}