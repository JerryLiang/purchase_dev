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
        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9521);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //??????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "????????????????????????\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            // echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/suggest_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/demander_export_data ' . $data['id']);
//            $this->download_order_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
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
            $heads = ['???????????????','??????????????????','????????????',
                'sku','????????????','????????????','????????????','????????????','???????????????',
                '????????????','???????????????','????????????','????????????','?????????','?????????', '?????????','????????????','????????????','????????????',
                '????????????', '????????????','??????????????????','????????????','????????????','?????????????????????','?????????????????????','????????????','????????????','????????????', '????????????', '????????????',
                '??????????????????', '????????????', '??????','??????','????????????','????????????','????????????'];

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //??????????????????????????????
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

                        $heads = ['???????????????','??????????????????','????????????',
                            'sku','????????????','????????????','????????????','????????????','???????????????',
                            '????????????','???????????????','????????????','????????????','?????????','?????????', '?????????','????????????','????????????','????????????',
                            '????????????', '????????????','??????????????????','????????????','????????????','?????????????????????','?????????????????????','????????????','????????????','????????????', '????????????', '????????????',
                            '??????????????????', '????????????', '??????','??????','????????????'];

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
                            $value_tmp['sales_note']               = iconv("UTF-8", "GBK//IGNORE",$value['sales_note']); //????????????
                            //????????????(???)
                            $value_tmp['tovoid_reason']             = iconv("UTF-8", "GBK//IGNORE",$value['tovoid_reason']);

                            $value_tmp['demand_name_ch']        = iconv("UTF-8", "GBK//IGNORE",$value['demand_name_ch']);
                            $value_tmp['demand_lock_ch']             = iconv("UTF-8", "GBK//IGNORE",$value['demand_lock_ch']);;
                            $value_tmp['create_time']        = iconv("UTF-8", "GBK//IGNORE",$value['create_time']);
                            $value_tmp['estime_time']              = iconv("UTF-8", "GBK//IGNORE",$value['estime_time']);
                            $value_tmp['over_lock_time']           = iconv("UTF-8", "GBK//IGNORE",$value['over_lock_time']);
                            $value_tmp['is_drawback_ch']      = iconv("UTF-8", "GBK//IGNORE",$value['is_drawback_ch']);
                            $value_tmp['is_overseas_first_order_ch']     = iconv("UTF-8", "GBK//IGNORE",$value['is_overseas_first_order_ch']);
                            $value_tmp['transformation_ch']                 = iconv("UTF-8", "GBK//IGNORE",$value['transformation_ch']);; //??????
                            $value_tmp['shipment_type_ch']            = iconv("UTF-8", "GBK//IGNORE",$value['shipment_type_ch']);
                            $value_tmp['logistics_type_ch']          = iconv("UTF-8", "GBK//IGNORE",$value['logistics_type_ch']);
                            $value_tmp['development_type_ch'] = SetAndNotEmpty($value, 'development_type_ch') ? iconv("UTF-8", "GBK//IGNORE",$value['development_type_ch']) : '--';
                            $value_tmp['sales_name']           = SetAndNotEmpty($value, 'sales_name') ? iconv("UTF-8", "GBK//IGNORE",$value['sales_name']) : '--';
                            $value_tmp['sales_group']    = SetAndNotEmpty($value, 'sales_group') ? iconv("UTF-8", "GBK//IGNORE",$value['sales_group']) : '--';   //?????????
                            $value_tmp['is_overseas_boutique']    = SetAndNotEmpty($value, 'is_overseas_boutique') ? iconv("UTF-8", "GBK//IGNORE",$value['is_overseas_boutique']): '--';   //????????????

                            $value_tmp['extra_handle_ch']           = SetAndNotEmpty($value, 'extra_handle_ch') ? iconv("UTF-8", "GBK//IGNORE",$value['extra_handle_ch']) : '--';
                          //  $value_tmp['extra_handle_ch']         = iconv("UTF-8", "GBK//IGNORE",$value['create_user_name']);   //?????????
                            $value_tmp['platform']              = SetAndNotEmpty($value, 'platform') ? iconv("UTF-8", "GBK//IGNORE",$value['platform']): '--';   //????????????
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * ??????????????????SWOOLE ??????
     * @param ???
     * @author:luxu
     * @time:2021???1???16???
     **/
    public function suggest_export(){
        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9520);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "????????????????????????\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            // echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/suggest_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/suggest_export_data ' . $data['id']);
//            $this->download_order_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
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
            //??????????????????????????????
            fputcsv($fp, $title);

            $warehouse_list = $this->warehouse_model->get_code_name_list();
            $logistics_type_list = $this->warehouse_model->get_logistics_type();
            $this->load->model('system/Reason_config_model');
            $param['status'] = 1;//?????????
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
                    //????????????????????????
                    $lack_map = $this->Shortage_model->get_lack_info($sku_list);
                    unset($sku_list);

                    $demand_number_list = array_column( $purchase_tax_list_export,"demand_number");
                    //???????????????
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
                                $suggest_status = '???';
                            }else{
                                $suggest_status = getSuggestStatus($suggest_status);
                            }
                            $value_tmp['suggest_status']           = iconv("UTF-8", "GBK//IGNORE", $suggest_status);
                            $value_tmp['plan_product_arrive_time']            = iconv("UTF-8", "GBK//IGNORE",$value['plan_product_arrive_time']);
                            $value_tmp['es_shipment_time'] = $value['es_shipment_time'];
                            $shipment_type = $value['shipment_type'];
                            $value_tmp['shipment_type_ch']         = iconv("UTF-8", "GBK//IGNORE",($shipment_type == 1?"????????????": $shipment_type == 2?"???????????????":""));
                            $value_tmp['product_jump_url']         = iconv("UTF-8", "GBK//IGNORE",jump_url_product_base_info($value['sku']));
                            $purchase_type_id = getPurchaseType($value['purchase_type_id']);
                            $value_tmp['purchase_type_id']         = iconv("UTF-8", "GBK//IGNORE", (!empty($purchase_type_id) && !is_array($purchase_type_id) ? $purchase_type_id: ''));
                            $value_tmp['demand_number']            = iconv("UTF-8", "GBK//IGNORE",$value['demand_number']);
                            $value_tmp['sku']                      = iconv("UTF-8", "GBK//IGNORE",$value['sku']);
                            $value_tmp['is_overseas_first_order_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['is_overseas_first_order'] == 1?'???':'???');
                            $value_tmp['is_new']                   = iconv("UTF-8", "GBK//IGNORE",$value['is_new'] == 1 ? '???' : '???');
                            $value_tmp['product_line_name']        = iconv("UTF-8", "GBK//IGNORE",$value['product_line_name']);
                            $value_tmp['two_product_line_name']    = iconv("UTF-8", "GBK//IGNORE",$value['two_product_line_name']);
                            $value_tmp['product_name']             = iconv("UTF-8", "GBK//IGNORE",trim($value['product_name'],'"'));
                            $value_tmp['product_status']           = iconv("UTF-8", "GBK//IGNORE",$now_product_status);
                            $value_tmp['purchase_amount']          = iconv("UTF-8", "GBK//IGNORE",$list_type == 2 ?$value['purchase_amount']: $value['demand_data']);
                            $value_tmp['left_stock']               = iconv("UTF-8", "GBK//IGNORE",$value['left_stock']); //????????????
                            //????????????(???)
                            $value_tmp['new_lack_qty']             = iconv("UTF-8", "GBK//IGNORE",$lack_map[$value['sku']]['think_lack_qty']??0);
                            $value_tmp['left_stock_status']        = iconv("UTF-8", "GBK//IGNORE",intval($value_tmp['left_stock'])<0?'???':'???');
                            $value_tmp['starting_qty']             = iconv("UTF-8", "GBK//IGNORE",$value['starting_qty']);;
                            $value_tmp['starting_qty_unit']        = iconv("UTF-8", "GBK//IGNORE",$value['starting_qty_unit']);
                            $is_drawback = getIsDrawback($value['is_drawback']);
                            $value_tmp['is_drawback']              = iconv("UTF-8", "GBK//IGNORE", (!empty($is_drawback) && !is_array($is_drawback) ? $is_drawback: ''));
                            $value_tmp['ticketed_point']           = iconv("UTF-8", "GBK//IGNORE",$value['ticketed_point']);
                            $value_tmp['purchase_unit_price']      = iconv("UTF-8", "GBK//IGNORE",$value['purchase_unit_price']);
                            $value_tmp['purchase_total_price']     = iconv("UTF-8", "GBK//IGNORE",$value['purchase_total_price']);
                            $value_tmp['currency']                 = CURRENCY; //??????
                            $value_tmp['supplier_name']            = iconv("UTF-8", "GBK//IGNORE",$value['supplier_name']);
                            $value_tmp['is_cross_border']          = iconv("UTF-8", "GBK//IGNORE",$value['is_cross_border'] == 1 ? '???':'???');
                            $value_tmp['earliest_exhaust_date'] = iconv("UTF-8", "GBK//IGNORE",$value['earliest_exhaust_date']);
                            $value_tmp['warehouse_name']           = iconv("UTF-8", "GBK//IGNORE",$value['warehouse_name']);
                            $value_tmp['destination_warehouse']    = iconv("UTF-8", "GBK//IGNORE",isset($warehouse_list[$value['destination_warehouse']]) ? $warehouse_list[$value['destination_warehouse']] : '');   //?????????
                            $value_tmp['logistics_type']           = iconv("UTF-8", "GBK//IGNORE",isset($logistics_type_list[$value['logistics_type']]) ? $logistics_type_list[$value['logistics_type']] : '');   //????????????

                            $value_tmp['create_user_name']         = iconv("UTF-8", "GBK//IGNORE",$value['create_user_name']);   //?????????
                            $value_tmp['create_time']              = iconv("UTF-8", "GBK//IGNORE",$value['create_time']);   //????????????
                            $value_tmp['buyer_name']               = iconv("UTF-8", "GBK//IGNORE",$value['buyer_name']);
                            $value_tmp['expiration_time']          = iconv("UTF-8", "GBK//IGNORE",$value['expiration_time']);
                            $value_tmp['audit_time']               = iconv("UTF-8", "GBK//IGNORE",$value['audit_time']);
                            // ?????? ??????????????????????????????
                            $purchase_order_info = $purchase_order_info_map[$value['demand_number']]??[];

                            $value_tmp['purchase_number']       = iconv("UTF-8", "GBK//IGNORE", $purchase_order_info['purchase_number']??'');
                            $value_tmp['purchase_order_status'] = iconv("UTF-8", "GBK//IGNORE",$purchase_order_info['purchase_order_status']??'');
                            $purchase_order_status = getPurchaseStatus($value_tmp['purchase_order_status']);
                            $value_tmp['purchase_order_status'] = iconv("UTF-8", "GBK//IGNORE", (!empty($purchase_order_status) && !is_array($purchase_order_status) ? $purchase_order_status: ''));
                            $value_tmp['confirm_number']        = iconv("UTF-8", "GBK//IGNORE",$purchase_order_info['confirm_number']??'');


                            if ($value_tmp['confirm_number'] === ''){
                                $value_tmp['cancel_ctq'] = '';
                            }else{
                                $value_tmp['cancel_ctq']            = $value_tmp['purchase_amount'] - intval($value_tmp['confirm_number']);// ????????????+??????????????????=???????????????
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
                            $value_tmp['is_abnormal']           = iconv("UTF-8", "GBK//IGNORE",($value['is_abnormal'] == SUGGEST_ABNORMAL_TRUE) ? '???' : '???');
                            $value_tmp['platform']              = iconv("UTF-8", "GBK//IGNORE",$value['platform']);
                            $value_tmp['site']                  = iconv("UTF-8", "GBK//IGNORE",$value['site']);
                            $value_tmp['sales_group']           = iconv("UTF-8", "GBK//IGNORE",$value['sales_group']);
                            $value_tmp['sales_name']            = iconv("UTF-8", "GBK//IGNORE",$value['sales_name']);
                            $value_tmp['sales_account']         = iconv("UTF-8", "GBK//IGNORE",$value['sales_account']);
                            $value_tmp['sales_note2']            = iconv("UTF-8", "GBK//IGNORE",$value['sales_note2']);
                            $supply_status = getProductsupplystatus($value['supply_status']);
                            $value_tmp['supply_status']         = iconv("UTF-8", "GBK//IGNORE", !empty($supply_status) && !is_array($supply_status)?$supply_status: '');//????????????
                            $is_boutique = getISBOUTIQUE($value['is_boutique']);
                            $value_tmp['is_boutique']           = iconv("UTF-8", "GBK//IGNORE", (!empty($is_boutique) && !is_array($is_boutique)? $is_boutique :""));//????????????
                            $state_type = getProductStateType((int)$value['state_type']);
                            $value_tmp['state_type']            = iconv("UTF-8", "GBK//IGNORE", (!empty($state_type) && !is_array($state_type)? $state_type: ""));//????????????
                            $value_tmp['is_entities_lock']      = iconv("UTF-8", "GBK//IGNORE",($value['lock_type'] == LOCK_SUGGEST_ENTITIES) ? '?????????' : '?????????');
                            $value_tmp['tax_rate']              = iconv("UTF-8", "GBK//IGNORE",empty($value['tax_rate'])?0:$value['tax_rate']);//?????????
                            $declare_unit = deleteProductData($value['declare_unit']);
                            $value_tmp['issuing_office'] = iconv("UTF-8", "GBK//IGNORE", !empty($declare_unit) && !is_array($declare_unit) ? $declare_unit : "");//????????????
                            $value_tmp['groupName']                = iconv("UTF-8", "GBK//IGNORE",isset($buyerName[$value['buyer_id']])?$buyerName[$value['buyer_id']]['group_name']:'');

                            $value_tmp['is_purchasing_ch']                = iconv("UTF-8", "GBK//IGNORE",'');
                            $is_purchasing_ch = '';
                            if( isset($value['tis_purchasing'])){
                                $is_purchasing_ch = $value['tis_purchasing'] == 1 ? "???":"???";
                            }
                            $value_tmp['is_purchasing_ch']  = iconv("UTF-8", "GBK//IGNORE", $is_purchasing_ch);
                            $value_tmp['payment_method_source_ch'] = iconv("UTF-8", "GBK//IGNORE", ($value['source'] == 1 ? "??????":"??????"));
                            $value_tmp['delivery_time']       = iconv("UTF-8", "GBK//IGNORE", isset($value['delivery_time_estimate_time'])?$value['delivery_time_estimate_time']:'');

                            $value_tmp['demand_type']           = iconv("UTF-8", "GBK//IGNORE", $value['demand_name_id_cn']);
                            $value_tmp['is_merge']              = iconv("UTF-8", "GBK//IGNORE", ($value['is_merge'] == 1 ?"?????????":"??????"));
                            $value_tmp['suggest_demand']        = iconv("UTF-8", "GBK//IGNORE", $value['suggest_demand']);
                            $value_tmp['unsale_reason']         = iconv("UTF-8", "GBK//IGNORE", $value['unsale_reason']);

                            // '????????????','????????????','????????????','????????????'

                            // ???????????????
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }


    /**
     * ????????????????????? 2.????????? 3.?????????
     */
    private function suggest_export_data_head($type=2)
    {
        if($type==2){
            return ['???????????????','??????????????????','??????????????????','????????????','??????','??????????????????','????????????','SKU','??????????????????',
                '????????????','???????????????','???????????????','????????????','????????????','????????????', '????????????','????????????(???)','????????????','???????????????',
                '?????????????????????', '????????????','?????????','??????','?????????','??????','?????????','????????????','??????????????????','????????????', '?????????', '????????????',
                '?????????', '????????????', '?????????','????????????','????????????','??????????????????','???????????????','???????????????','????????????????????????','??????',
                '????????????','??????????????????','????????????','??????','??????','????????????','????????????',
                '????????????','????????????','????????????','????????????','????????????','???????????????','?????????','????????????','????????????','????????????','????????????','??????????????????',
                '????????????','????????????','????????????','????????????'
            ];
        }

        if($type==3){
            return ['???????????????','??????????????????','??????????????????','????????????','??????','??????????????????','????????????','SKU','??????????????????',
                '????????????','???????????????','???????????????','????????????','????????????','????????????', '????????????','????????????(???)','????????????','???????????????',
                '?????????????????????', '????????????','?????????','??????','?????????','??????','?????????','????????????','??????????????????','????????????', '?????????', '????????????',
                '?????????', '????????????', '?????????','??????????????????','???????????????','???????????????','????????????????????????','??????',
                '????????????','??????????????????','????????????','??????','??????','????????????','????????????',
                '????????????','????????????','????????????','????????????','????????????','???????????????','?????????','????????????','????????????','????????????','????????????','??????????????????',
                '????????????','????????????','????????????','????????????', '???????????????'
            ];
        }
        return false;
    }





    /**
     * ??????????????????SWOOLE ??????
     * @param ???
     * @author:luxu
     * @time:2021???1???16???
     **/
    public function refund_export(){
        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9518);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "????????????????????????\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/refund_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/refund_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_order_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * ??????????????????
     * @param ???
     * @author:luxu
     * @time:2021???1???16???
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
                '????????????','??????????????????','???????????????','???????????????','????????????','????????????','?????????'
                ,'????????????','?????????','???????????????','????????????','????????????','????????????','????????????','1688?????????'
                ,'????????????','????????????','???????????????','??????????????????','????????????','????????????','????????????','?????????','????????????'
            ];

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //??????????????????????????????
            fputcsv($fp, $title);

            $x = 0;
            if($total>=1) {

                for ($i = 1; $i <= $page; $i++) {
                    $export_offset      = ($i - 1) * $limit;
                    $orders_export_info = $this->Offline_refund_model->get_offline_refund(json_decode($params['condition'],true),$limit,$export_offset);

                    $purchase_tax_list_export = $orders_export_info['values'];
                    if ($purchase_tax_list_export) {

                        foreach ($purchase_tax_list_export as $value) {
                            $value_tmp['refund_status_cn']     = iconv("UTF-8", "GBK//IGNORE", $value['refund_status_cn']);//????????????
                            $value_tmp['refund_number']     = iconv("UTF-8", "GBK//IGNORE", $value['refund_number']);//????????????
                            $value_tmp['supplier_name']       = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);//???????????????
                            $value_tmp['supplier_code']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_code']);// ???????????????
                            $value_tmp['purchase_number_multi_ch']       = iconv("UTF-8", "GBK//IGNORE", $value['purchase_number_multi']);//????????????
                            $value_tmp['purchase_name_cn'] = iconv("UTF-8", "GBK//IGNORE", $value['purchase_name_cn']);//????????????
                            $value_tmp['compact_number_multi']         = iconv("UTF-8", "GBK//IGNORE", $value['compact_number_multi']);//????????????
                            $value_tmp['statement_number']                = iconv("UTF-8", "GBK//IGNORE", $value['statement_number']);
                            $value_tmp['apply_user_name']                = iconv("UTF-8", "GBK//IGNORE", $value['apply_user_name']);
                            $value_tmp['apply_notice']           = iconv("UTF-8", "GBK//IGNORE", $value['apply_notice']);//???????????????
                            $value_tmp['refund_type']  = iconv("UTF-8", "GBK//IGNORE", $value['refund_type']);//????????????
                            $value_tmp['refund_reason']      = iconv("UTF-8", "GBK//IGNORE", $value['refund_reason']);//????????????
                            $value_tmp['refund_channel_cn']      = iconv("UTF-8", "GBK//IGNORE", $value['refund_channel_cn']);//????????????
                            $value_tmp['refund_time']      = iconv("UTF-8", "GBK//IGNORE", $value['refund_time']);//????????????
                            $value_tmp['pai_number']      = iconv("UTF-8", "GBK//IGNORE", "'".$value['pai_number']);//1688?????????
                            $value_tmp['abnormal_number']       = iconv("UTF-8", "GBK//IGNORE", $value['abnormal_number']);//????????????
                            $value_tmp['refund_price']     = iconv("UTF-8", "GBK//IGNORE", $value['refund_price']);// ????????????
                            if(!empty($value['refund_water_number'])) {
                                $value_tmp['refund_water_number'] = iconv("UTF-8", "GBK//IGNORE", "'" . $value['refund_water_number']);//???????????????
                            }else{

                                $value_tmp['refund_water_number'] = iconv("UTF-8", "GBK//IGNORE", $value['refund_water_number']);//???????????????
                            }

                            if(!empty($value['refund_log_number'])) {
                                $value_tmp['refund_log_number'] = iconv("UTF-8", "GBK//IGNORE", "'" . $value['refund_log_number']);//??????????????????

                            }else{
                                $value_tmp['refund_log_number'] = iconv("UTF-8", "GBK//IGNORE", $value['refund_log_number']);//??????????????????
                            }
                            $value_tmp['status']   = iconv("UTF-8", "GBK//IGNORE", $value['status']);//????????????
                            $value_tmp['apply_time']        = iconv("UTF-8", "GBK//IGNORE", $value['apply_time']);//????????????
                            $value_tmp['receipt_time']    = iconv("UTF-8", "GBK//IGNORE", $value['receipt_time']); //????????????
                            $value_tmp['receipt_user_name']       = iconv("UTF-8", "GBK//IGNORE", $value['receipt_user_name']); //?????????
                            $value_tmp['receipt_notice']   = iconv("UTF-8", "GBK//IGNORE", isset($value['receipt_notice'])? $value['receipt_notice'] : ""); //????????????
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    public function abnormal()
    {
//tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 2024);
//??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "????????????????????????\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/abnormal_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/abnormal_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_order_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
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
            $heads = ['????????????','????????????','????????????','????????????','????????????','????????????','????????????','SKU','????????????','SKU??????','???????????????','???????????????','???????????????','????????????','????????????','????????????','?????????','?????????','?????????','????????????','????????????','??????????????????','??????????????????',
                '??????????????????','????????????','?????????????????????','?????????????????????','??????????????????','????????????(h)','??????','????????????','????????????'];

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //??????????????????????????????
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
                            $value_tmp['warehouse_name']     = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_name']);//????????????
                            $value_tmp['handle_warehouse']     = iconv("UTF-8", "GBK//IGNORE", $value['handle_warehouse_name']);//????????????
                            $value_tmp['defective_id']       = iconv("UTF-8", "GBK//IGNORE", $value['defective_id']."\t");//????????????
                            $value_tmp['demand_number']      = iconv("UTF-8", "GBK//IGNORE", $value['demand_number']."\t");
                            $value_tmp['express_code']       = iconv("UTF-8", "GBK//IGNORE", $value['express_code']."\t");//????????????
                            $value_tmp['exception_position'] = iconv("UTF-8", "GBK//IGNORE", $value['exception_position']);//????????????
                            $value_tmp['pur_number']         = iconv("UTF-8", "GBK//IGNORE", $value['pur_number']);//????????????
                            $value_tmp['sku']                = iconv("UTF-8", "GBK//IGNORE", $value['sku']);
                            $value_tmp['is_purchasing']                = iconv("UTF-8", "GBK//IGNORE", $value['is_purchasing']);
                            $value_tmp['quantity']           = iconv("UTF-8", "GBK//IGNORE", $value['quantity']);//SKU??????
                            $value_tmp['product_line_name']  = iconv("UTF-8", "GBK//IGNORE", $value['product_line_name']);//???????????????
                            $value_tmp['supplier_code']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_code']);//???????????????
                            $value_tmp['supplier_name']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);//???????????????
                            $value_tmp['abnormal_type']      = iconv("UTF-8", "GBK//IGNORE", $value['abnormal_type']);//????????????
                            $value_tmp['defective_type']      = iconv("UTF-8", "GBK//IGNORE", $value['defective_type']);//????????????
                            $value_tmp['handler_type2']       = iconv("UTF-8", "GBK//IGNORE", $value['handler_type']);//????????????
//                        $value_tmp['defective_type']     = iconv("UTF-8", "GBK//IGNORE", $value['defective_type']);//????????????

                            $value_tmp['buyer']              = iconv("UTF-8", "GBK//IGNORE", $value['buyer']);//?????????
                            $value_tmp['handler_person']     = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_person']) ? $value['handler_person']: "");//?????????
                            $value_tmp['create_user_name']   = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);//?????????
                            $value_tmp['create_time']        = iconv("UTF-8", "GBK//IGNORE", $value['create_time']);//????????????
                            $value_tmp['abnormal_depict']    = iconv("UTF-8", "GBK//IGNORE", $value['abnormal_depict']); //????????????
                            $value_tmp['handler_time']       = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_time'])? $value['handler_time'] : ""); //??????????????????
                            $value_tmp['handler_describe']   = iconv("UTF-8", "GBK//IGNORE", isset($value['handler_describe'])? $value['handler_describe'] : ""); //??????????????????
                            $value_tmp['express_no']         = iconv("UTF-8", "GBK//IGNORE", $value['return_express_no']."\t");//??????????????????
                            $value_tmp['track_status']     = iconv("UTF-8", "GBK//IGNORE", $value['track_status']);//????????????
                            $value_tmp['is_handler']         = iconv("UTF-8", "GBK//IGNORE", getAbnormalHandleResult($value['is_handler']));//????????????
                            $value_tmp['handler_type']       = iconv("UTF-8", "GBK//IGNORE", empty($value['handler_type'])?"?????????":getAbnormalHandleType($value['handler_type']));//?????????????????????
                            $value_tmp['warehouse_handler_result'] = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_handler_result']);//??????????????????
                            $value_tmp['deal_used']          = iconv("UTF-8", "GBK//IGNORE", $value['deal_used']);//????????????
                            $value_tmp['note']               = iconv("UTF-8", "GBK//IGNORE", $value['note'].' '.$value['add_note_person'].' '.$value['add_note_time']);//??????
                            $value_tmp['groupname']          = iconv("UTF-8", "GBK//IGNORE", $value['groupName']);
                            $value_tmp['department_china_ch']  = iconv("UTF-8", "GBK//IGNORE", $value['department_china_ch']);
                            $tax_list_tmp = $value_tmp;

                            $x ++;
//                        if($x > $total)break; // ????????????????????????????????????
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * ??????????????????CSV
     * @param
     * @author:luxu
     * @time:2020???1???10???
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
            $heads = ['????????????','??????????????????','????????????','????????????','sku','????????????','????????????','????????????','????????????'

                ,'??????????????????','?????????','???????????????','??????????????????','???????????????','???????????????','?????????????????????'

                ,'?????????','?????????CODE','????????????','????????????','????????????','????????????','????????????','????????????'];

            foreach($heads as $key => $item) {
                $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //??????????????????????????????
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * ??????????????????
     * @param
     * @author:luxu
     * @time:2020???1???10???
     **/

    public function lack_export_data($id){

        $par = $this->data_center_model->get_items("id = " . $id);
        $params = $par[0];
        return $this->lack_export_data_csv($id);
    }

    /**
     * ?????????????????? SWOOLE
     * @param
     * @author:luxu
     * @time:2020???1???10???
     **/

    public function lack_export()
    {
        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9517);
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'lack_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/lack_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/lack_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');

            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();

    }

    public function purchase_order_export()
    {
//tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9503);
//??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "????????????????????????\n";
//            var_dump($data);
//            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_order_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_order_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_order_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }
    public function cancel_unarrived_goods_examine_down()
    {
//tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 2023);
//??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            echo "????????????????????????\n";
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'order_' . date('Ymd') . '.txt';
            $data = json_decode($data, true);
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/cancel_unarrived_goods_examine_down_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/cancel_unarrived_goods_examine_down_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_order_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
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
            $heads = ['????????????','?????????????????????','????????????','sku','????????????','30???????????????',  '?????????????????????','?????????','????????????','????????????','????????????','????????????','???????????????','????????????'
                ,'?????????????????????','1688????????????','??????????????????','?????????','????????????','?????????','????????????','???????????????'
            ];
            foreach($heads as $key => $item) {
                $title[$key] = $item;
            }
            //??????????????????????????????
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }




    /**
     * ???????????????
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
                echo '??????????????????';
                return $result;
            }
            $params = $par[0];
            if (isset($params['user_id'])) {
                $export_user = [
                    'user_id' => explode(',', $params['user_id']) ?? [],
                    'data_role' => explode(',', $params['role']) ?? [],
                    'role_name' => explode(',', $params['role_name']) ?? [],
                    'export_user_id' => $params['user_id'],// ???????????????
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
            //????????????
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

                // ??????????????????
                $list_type = isset($param['list_type'])?$param['list_type']:1;
                $uid = $params['user_id'];
                $orders_key = $this->purchase_order_new_model->get_export_header($list_type, $uid);

                $table_columns_list = [];
                foreach ($orders_key as $k=>$v){
                    if($k == 'freight')$v = 'PO?????????';
                    if($k == 'discount')$v = 'PO????????????';
                    if($k == 'process_cost')$v = 'PO????????????';
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
                        'name' => '????????????'
                    ];

                    $keys_info[] =[

                        'key' => 'is_long_delivery_ch',
                        'name' => '??????????????????'
                    ];

                    $keys_info[] =[

                        'key' => 'new_devliy',
                        'name' => '????????????'
                    ];
                    $keys_info[] = [
                        'key'   => 'freight_pay',
                        'name'  => '????????????',
                    ];
                    $keys_info[] = [
                        'key'   => 'discount_pay',
                        'name'  => '???????????????',
                    ];
                    $keys_info[] = [
                        'key'   => 'process_cost_pay',
                        'name'  => '???????????????',
                    ];
                    $keys_info[] = [
                        'key'   => 'is_distribution',
                        'name'  => '????????????',
                    ];
                    $keys_info[] = [
                        'key'   => 'quantity',
                        'name'  => '???????????????',
                    ];
                    $keys_info[] = [
                        'key'   => 'freight',
                        'name'  => 'PO?????????',
                    ];
                    $keys_info[] = [
                        'key'   => 'discount',
                        'name'  => 'PO????????????',
                    ];
                    $keys_info[] = [
                        'key'   => 'process_cost',
                        'name'  => 'PO????????????',
                    ];
                    $keys_info[] = [
                        'key'   => 'order_remark',
                        'name'  => '??????',
                    ];
                    $keys_info[] = [
                        'key'   => 'quantity_time',
                        'name'  => '??????????????????',
                    ];
                    $keys = array();
                    foreach ($keys_info AS $key => $value) {

                        $keys[$value['key']] = $value['name'];
                        if ($value['key'] == 'sku') {
                            $keys['first_product_line'] = '???????????????';//?????????sku???,???????????????????????????
                            $keys['state_type'] = '????????????';
                            $keys['product_weight'] = '??????????????????';
//                            pr($keys);exit;
                        }
                        if ($value['key'] == 'pay_status') {
                            $keys['pay_finish_status'] = '??????????????????';//????????????????????????,??????????????????????????????
                        }
                        if ($value['key'] == 'amount_paid') {
                            $keys['ca_product_money'] = '???????????????';
                            $keys['ca_process_cost'] = '???????????????';
                        }
                        if($value['key'] == 'account_type' || $value['key'] == 'pay_type'){
                            $keys['settlement_ratio'] = '????????????';
                        }
                        if($value['key'] == 'supplier_name'){
                            $keys['supplier_code'] = '???????????????';
                        }
                    }
                    $keys_info[] =  [

                        'key' => 'groupname',
                        'name' => '????????????'
                    ];


                    //Start??????????????????????????????
                    if (!in_array('is_distribution', $keys)) $keys['is_distribution'] = '????????????';
                    if (!in_array('quantity', $keys)) $keys['quantity'] = '???????????????';
                    if (!in_array('quantity_time', $keys)) $keys['quantity_time'] = '??????????????????';
                    if (!in_array('purchase_type_id', $keys)) $keys['purchase_type_id'] = '?????????';
                    if (!in_array('is_oversea_boutique', $keys)) $keys['is_oversea_boutique'] = '??????????????????';
                    if (!in_array('logistics_trajectory', $keys)) $keys['logistics_trajectory'] = '????????????';
                    if (!in_array('is_entities_lock', $keys)) $keys['is_entities_lock'] = '????????????';
                    if (!in_array('supplier_status', $keys)) $keys['supplier_status'] = '????????????????????????';
                    if (!in_array('is_ali_price_abnormal', $keys)) $keys['is_ali_price_abnormal'] = '????????????';
                    if (!in_array('coupon_rate_price', $keys)) $keys['coupon_rate_price'] = '??????????????????';
                    if (!in_array('coupon_rate', $keys)) $keys['coupon_rate'] = '????????????';

                    if (!in_array('batch_note', $keys)) $keys['batch_note'] = '??????????????????';
                    if (!in_array('freight_note', $keys)) $keys['freight_note'] = '????????????';
                    if (!in_array('purchase_note', $keys)) $keys['purchase_note'] = '??????????????????????????????';
                    if (!in_array('message_note', $keys)) $keys['message_note'] = '????????????????????????';
                    if (!in_array('message_apply_note', $keys)) $keys['message_apply_note'] = '????????????????????????';
                    if (in_array('supplier_name', $keys)) {
                        if (!in_array('is_equal_sup_id', $keys)) $keys['is_equal_sup_id'] = '?????????ID????????????';
                        if (!in_array('is_equal_sup_name', $keys)) $keys['is_equal_sup_name'] = '???????????????????????????';
                    }
                    if (!in_array('audit_time_status', $keys)) $keys['audit_time_status'] = '??????????????????';
                    if ( !in_array('buyer_name',$keys)) $keys['groupname'] = '????????????';

                    if ( !in_array('track_status_ch',$keys)) $keys['track_status_ch'] = '????????????';
                    if (!in_array('order_remark',$keys)) $keys['order_remark'] = '??????';
                    if (!in_array('tap_date_str_sync',$keys)) $keys['tap_date_str_sync'] = '??????????????????';
//
//                    if ( !in_array('is_long_delivery_ch_ch',$keys)) $keys['is_long_delivery_ch_ch'] = '??????????????????';
//
//                    if ( !in_array('new_devliy_ch',$keys)) $keys['new_devliy_ch'] = '????????????';
                    //End??????????????????????????????

                    //if(!in_array('pay_category_numbermessage_note',$keys)) $keys['pay_category_number'] = '????????????????????????';

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
                            try{ // ?????????
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
                                }//?????????
                                if (!is_array($vvv) && $k != "purchase_price") {
                                    if (is_numeric($vvv) && strlen($vvv) > 9) $vv[$k] = $vvv . "\t";//??????????????????csv???????????????????????????
                                }
                            }

                            if ($is_head === false) {
                                $heads[] = "????????????";
//                                $heads[] = "??????????????????";
//                                $heads[]= "????????????";
//                                $heads[]= "????????????";
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
                        // ???????????????????????????pur_center_data ???
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
                //  ??????????????????????????????
                $file_data = $this->upload_file($product_file);
                if (!empty($file_data['code'] == 200)) {
                    // ???????????????pur_center_data ???
                    $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $template_file, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
                }
            }
        } else {
            echo '??????????????????';
        }
//        $down_host = CG_SYSTEM_WEB_FRONT_IP; //????????????
//        $down_file_url = $down_host . 'download_csv/' . $template_file;
//        $this->success_json($down_file_url);
        return $result;

    }

    /**
     * ????????????
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
     * ????????????????????????
     */
    public function purchase_progress_export()
    {

        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9504);
//??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_progress_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_progress_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * SKU ????????????????????????
     */
    public function deliveryData()
    {

        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 2021);
//??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/deliverydata_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/deliverydata_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
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
            $heads = ['SKU','????????????','?????????','?????????', '?????????????????????','??????????????????','????????????-??????','????????????','????????????','?????????'];
            foreach($heads as $key => $item) {
                $title[$key] = $item;
            }
            //??????????????????????????????
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
                    // '????????????','????????????','?????????','????????????'
                    //'SKU','????????????','?????????','?????????', '?????????????????????','??????????????????','????????????-??????','????????????','????????????','?????????'
                    if($value['is_customized'] == 1){

                        $v_value_tmp['is_customized_ch'] = '???';
                    }else{
                        $v_value_tmp['is_customized_ch'] = '???';
                    }

                    if($value['is_purchasing'] == 1){

                        $v_value_tmp['is_purch_ch'] = '???';
                    }else{
                        $v_value_tmp['is_purch_ch'] = '???';
                    }

                    if( $value['purchase_type_id'] == PURCHASE_TYPE_INLAND){
                        $v_value_tmp['business_line_chs'] = '??????';
                    }

                    if( $value['purchase_type_id'] == PURCHASE_TYPE_OVERSEA){
                        $v_value_tmp['business_line_chs'] = '??????';
                    }

                    if( $value['purchase_type_id'] == PURCHASE_TYPE_FBA_BIG){
                        $v_value_tmp['business_line_chs'] = 'FBA??????';
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }




    public function inventoryitems_export()
    {

        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9605);
//??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/inventoryitems_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/inventoryitems_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }




    /**
     * @function:??????????????????????????????
     * @param:???
     * @author:luxu
     * @time:2020/7/30
     **/
    public function downStatement_export(){


        //tcp?????????
        echo "hello,,,,";
        $server = new \Swoole\Server(SWOOLE_SERVER, 2020);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
        });

        // SWOOLE ???????????????????????????????????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });

        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {

            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            print_r($data['swoole_type']);
            // ?????????????????????
            if( isset($data['swoole_type']) && $data['swoole_type'] == 'INVENTORYITEMS') {

                $log_file = 'inventoryitems' . date('Ymd') . '.txt';
                echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/inventoryitems_export_data ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
                exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/inventoryitems_export_data ' . $data['id'] );
//            $this->download_progress_csv($data);
                $server->finish('???????????? OK');
            }

            // ????????????????????????

            if( isset($data['swoole_type']) && $data['swoole_type'] == 'BALANCE'){

                $log_file = 'balance_' . date('Ymd') . '.txt';
                print_r($data);
                //  echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/balance' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
                exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/balance ' . $data['id'] );
//            $this->download_progress_csv($data);
                $server->finish('???????????? OK');

            }

            if( isset($data['swoole_type']) && $data['swoole_type'] == 'CHARGE') {

                $log_file = 'balance_' . date('Ymd') . '.txt';
                echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/charge_against' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
                exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/charge_against ' . $data['id'] );
//            $this->download_progress_csv($data);
                $server->finish('???????????? OK');
            }

        });

        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();

    }


    /**
     * ??????????????????????????????
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
                'purchase_number' => '????????????', 'purchase_type_cn' => '?????????', 'source_cn' => '????????????', 'compact_number' => '?????????', 'statement_number' => '????????????', 'purchase_order_status_cn' => '????????????', 'pay_status_cn' => '????????????',
                'cancel_status_cn' => '????????????', 'report_loss_status_cn' => '????????????', 'supplier_name' => '???????????????', 'supplier_code' => '???????????????',
                'real_price' => '????????????-??????', 'product_money' => '????????????-????????????', 'freight' => '????????????-??????', 'process_cost' => '????????????-?????????', 'discount' => '????????????-?????????', 'total_instock_price' => '????????????',
                'paid_real_price' => '???????????????-??????', 'paid_product_money' => '???????????????-????????????', 'paid_freight' => '???????????????-??????', 'paid_process_cost' => '???????????????-?????????', 'paid_discount' => '???????????????-?????????',
                'cancel_real_price' => '????????????-??????', 'cancel_product_money' => '????????????-????????????', 'cancel_freight' => '????????????-??????', 'cancel_process_cost' => '????????????-?????????', 'cancel_discount' => '????????????-?????????',
                'real_refund_amount' => '????????????', 'loss_real_price' => '????????????-??????', 'loss_product_money' => '????????????-????????????', 'loss_freight' => '????????????-??????', 'loss_process_cost' => '????????????-?????????',
                'loss_discount' => '????????????-?????????', 'instock_price_after_charge_against' => '???????????????????????????', 'real_price_after_charge_against' => '???????????????????????????', 'buyer_name' => '?????????',
                'waiting_time' => '????????????', 'finished_time' => '??????????????????', 'finished_cn' => '????????????', 'groupName' => '????????????'
            );


            $fp = fopen($reduced_file, "a");

            //??????????????????????????????
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
                    // ????????????
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
            // ??????????????????????????????
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'])  && ($file_data['code']== 200 )){
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['progress' => $total,'file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
        exit;
    }


    /**
     * function ?????????????????????
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
                'sequence' => '??????', 'product_name' => '????????????', 'instock_batch' => '????????????',
                'deliery_batch' => '???????????????',
                'instock_date' => '????????????', 'audit_time' => '????????????', 'real_confirm_amount' => '????????????',
                'instock_price' => '????????????', 'instock_qty' => '????????????', 'instock_qty_more' => '????????????',
                'defective_num' => '????????????', 'warehouse_cn' => '????????????',
                'purchase_number' => '????????????', 'sku' => 'SKU', 'product_line_name' => '?????????', 'compact_number' => '?????????',
                'purchase_name' => '????????????', 'is_purchasing' => '????????????', 'is_drawback_cn' => '????????????', 'supplier_name' => '???????????????',
                'supplier_code' => '???????????????', 'purchase_unit_price' => '????????????',
                'currency_code' => '??????', 'coupon_rate' => '????????????', 'buyer_name' => '?????????', 'instock_user_name' => '???????????????',
                'purchase_type_cn' => '??????????????????',
                'pay_type_cn' => '????????????', 'settlement_type_cn' => '????????????', 'suggest_order_status_cn' => '???????????????',
                'purchase_order_status_cn' => '????????????',
                'is_paste_cn' => '??????????????????', 'paste_labeled_cn' => '??????????????????', 'instock_type'=>'????????????','statement_number' => '????????????',
                'pay_status_cn' => '?????????????????????',
                'charge_against_status_cn' => '????????????', 'surplus_charge_against_amount' => '?????????????????????',
                'po_surplus_aca_amount' => 'PO?????????????????????',
                'remark' => '??????','groupName' => '????????????','product_weight' => 'SKU??????(g)',
                'demand_purchase_type_cn' => '??????????????????',
                'demand_number' => '????????????',
                'is_oversea_boutique' => '??????????????????',
                'need_pay_time' => '???????????????',
                'instock_month' => '????????????',
                'create_time' => '????????????',
            );
            //?????????????????????????????????
            if(!isset($condition['source']))$condition['source'] = 2; // ???????????????????????????
            if (SOURCE_NETWORK_ORDER == $condition['source']) {
                unset($columns['compact_number']);
            }
            //?????????????????????
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
            //??????????????????????????????
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
            // ??????????????????????????????
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'])  && ($file_data['code']== 200 )){
                // ???????????????pur_center_data ???
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
                    $info =$this->balance->supplier_balance_export($condition,$offsets, $per_page,$page,$condition['date_type']);//????????????
                    if(!empty($info['values'])){
                        foreach ($info['values'] as $key =>$value) {
                            $row=[
                                $value['supplier_code'],
                                $value['supplier_name'],//???????????????
                                $value['purchase_name'],
                                $value['occurrence_date'],//????????????
                                $value['opening_balance'],//????????????
                                $value['payable_current'],//????????????
                                $value['payable_price'],//????????????
                                $value['refund_amount'],//????????????
                                $value['balance_price'],//??????????????????
                            ];

                            foreach ($row as $vvv) {
                                if(preg_match("/[\x7f-\xff]/",$vvv)){
                                    $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//????????????
                                }
                                if(is_numeric($vvv) && strlen($vvv) > 9){
                                    $vvv =  $vvv."\t";//??????????????????csv???????????????????????????
                                }
                                $row_list[]=$vvv;
                            }
                            if($is_head === false){
                                $heads =['?????????','???????????????','????????????','????????????','????????????','????????????','????????????','????????????','??????????????????'];
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
                            ob_flush();//??????????????????buffer???????????????????????????????????????
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
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //????????????
        $down_file_url =  $product_file;
        $file_data = $this->upload_file($down_file_url);
        if (!empty($file_data['code']) && $file_data['code'] == 200) {
            // ???????????????pur_center_data ???
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
            $heads = ['??????',
                '????????????',
                '????????????',
                '??????',
                '????????????',
                '????????????',
                '??????????????????',
                '????????????',
                '?????????',
                '?????????',
                '????????????',
                'SKU',
                '????????????',
                '????????????',
                '????????????',
                '??????????????????',
                '??????????????????',
                '???????????????',
                '????????????',
                '?????????',
                '?????????',
                '????????????',
                '??????????????????',
                '????????????',
                '????????????',
                '???????????????h???',
                '????????????',
                '?????????',
                '????????????',
                '????????????',
                '????????????',
                '????????????',
                '????????????',
                '????????????(h)',
                '????????????',
                '?????????',
                '????????????', '????????????(???)',
                '1688??????', '???7?????????',
                '????????????',
                '????????????',
                '????????????',
                '???????????????',
                '????????????',
                '????????????',
                '????????????',
                '????????????',
                '????????????',
                '?????????????????????'];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            fputcsv($fp, $title);
            $limit = 500;
            $page = ceil($total / $limit);
            $numi = 0;
            $i = 1;

            // ????????????????????????

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
                                $v_value['source_ch'] = "??????";
                                break;
                            case 2:
                                $v_value['source_ch'] = "??????";
                                break;
                            case 3:
                                $v_value['source_ch'] = "????????????";
                                break;
                            default:
                                $v_value['source_ch'] = "??????";
                                break;
                        }
                        $v_value_tmp['source_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['source_ch']);
                        $v_value_tmp['product_line_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['product_line_ch']);
                        $v_value['product_status_ch'] = $this->purchase_order_model->get_productStatus($v_value['product_status']);
                        $v_value_tmp['product_status_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['product_status_ch']);
                        $v_value_tmp['stock_owes'] = $v_value['stock_owes'];
                        if ($v_value['ali_order_status'] == 0) {

                            $v_value['ali_order_status_ch'] = "???";
                        } else {
                            $v_value['ali_order_status_ch'] = "???";
                        }
                        $v_value_tmp['ali_order_status_ch'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['ali_order_status_ch']);
                        if( isset($condition['is_sales']) && $condition['is_sales'] ==1){
                            $v_value_tmp['sevensale'] = iconv('UTF-8', 'GBK//IGNORE', "****");
                        }else{
                            $v_value_tmp['sevensale'] = iconv('UTF-8', 'GBK//IGNORE', $v_value['sevensale']);
                        }

                        //???????????? ???????????? ????????????
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

                        //????????????
                        $abnormal_type_arr = explode(',', $v_value['abnormal_type_cn']);
                        $abnormal_type_tmp = array();
                        foreach ($abnormal_type_arr as $item) {
                            $abnormal_type_tmp[] = iconv('UTF-8', 'GBK//IGNORE', $item);
                    }
                    $v_value_tmp['abnormal_type'] = implode(',', $abnormal_type_tmp);
                    //???????????? ???????????? $v_value
                    $v_value_tmp['groupName']                =   iconv('UTF-8', 'GBK//IGNORE', isset($v_value['groupName'])?$v_value['groupName']:'');
                        $v_value_tmp['dddemand_number'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['dddemand_number'])?$v_value['dddemand_number']:'');
                        $v_value_tmp['demand_types'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['demand_types'])?$v_value['demand_types']:'');
                        $v_value_tmp['is_merge'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['is_merge'])?$v_value['is_merge']:'');
                        $v_value_tmp['cancel_reason_ch'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['cancel_reason_ch'])?$v_value['cancel_reason_ch']:'');
                        $v_value_tmp['cancel_total'] = iconv('UTF-8', 'GBK//IGNORE', isset($v_value['cancel_total'])?$v_value['cancel_total']:'');
                    $tax_value_temp = $v_value_tmp;
                    fputcsv($fp, $tax_value_temp);
                    }

                    // ???????????????pur_center_data ???
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * SKU ??????????????????SWOOLE ??????
     * @author :luxu
     * @time:2020/5/23
     **/
    public function purchase_reduced_export(){

        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9510);
//??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'product_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/purchase_reduced_export_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/purchase_reduced_export_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    public function get_mongdb_key($clientData)
    {
        $filter = [];
        // ?????????????????????
        if( isset($clientData['person_name']) && !empty($clientData['person_name']))
        {
            $filter['person_name'] ="{$clientData['person_name']}";
        }

        // ????????????ID

        if( isset($clientData['ids']) && !empty($clientData['ids']))
        {
            $filter['id'] = array('$in'=>$clientData['ids']);

        }

        // ???????????????

        if( isset($clientData['supplier_code']) && !empty($clientData['supplier_code']))
        {
            $filter['supplier_code'] = "{$clientData['supplier_code']}";
        }
        // ??????SKU
        if( isset( $clientData['sku']) && !empty($clientData['sku']) )
        {
            if (count($clientData['sku']) == 1 && !empty($clientData['sku'][0])) {  //??????sku?????????????????????
                $filter['sku'] = $clientData['sku'][0];
            } else if(count($clientData['sku']) > 1) {
                $filter['sku'] = array('$in'=>$clientData['sku']);
            }
        }
        // ?????????????????????????????????
        if( (isset($clientData['price_change_end_time']) && !empty($clientData['price_change_end_time'])) &&
            isset($clientData['price_change_start_time']) && !empty($clientData['price_change_start_time']) ){
            $filter['price_change_time'] = array('$gte'=>$clientData['price_change_start_time'],'$lte'=>$clientData['price_change_end_time']);

        }

        //????????????
        if( isset($clientData['audit_time_start'])  && !empty($clientData['audit_time_start'])

            && isset($clientData['audit_time_end']) && !empty($clientData['audit_time_end'])
        ){
            $filter['product_audit_time'] = array('$gte'=>$clientData['audit_time_start'],'$lte'=>$clientData['audit_time_end']);
        }

        //????????????
        //purchase_number
        if( isset($clientData['purchase_number']) && !empty($clientData['purchase_number']))
        {
            $filter['purchase_number'] = array('$in'=>$clientData['purchase_number']);
        }

        // ?????????
        if( isset($clientData['demand_number']) && !empty($clientData['demand_number']))
        {
            $filter['demand_number'] = array('$in'=>$clientData['demand_number']);
        }

        // ????????????
        if( isset($clientData['purchase_num']) && !empty($clientData['purchase_num']) )
        {
            // ???????????????0
            if( $clientData['purchase_num'] == 2 )
            {
                $filter['purchase_num_flag'] = 1;
            }else{
                // ??????0
                $filter['purchase_num_flag'] = 0;
            }
        }
        // ????????????
        if( isset($clientData['warehouse_start_time']) && !empty($clientData['warehouse_start_time'])
            && isset($clientData['warehouse_end_time']) && !empty($clientData['warehouse_end_time'])
        )
        {
            $filter['instock_date'] = array('$gte'=>$clientData['warehouse_start_time'],'$lte'=>$clientData['warehouse_end_time']);
        }

        // ????????????????????????

        if( isset($clientData['first_calculation_start_time']) && !empty($clientData['first_calculation_start_time']) &&
            isset($clientData['first_calculation_end_time']) && !empty($clientData['first_calculation_end_time']) )
        {
            $filter['first_calculation_time'] = array('$gte'=>$clientData['first_calculation_start_time'],'$lte'=>$clientData['first_calculation_end_time']);
        }

        //????????????
        if( isset($clientData['is_superposition']) && !empty($clientData['is_superposition']) )
        {
            $filter['is_superposition'] = "{$clientData['is_superposition']}";
        }

        // ??????????????????
        if( isset($clientData['gain']) && !empty($clientData['gain']) )
        {
            // ????????????
            if( $clientData['gain'] == 1 )
            {
                $filter['range_price'] = array('$gte'=>'0');
            }else{
                // ??????
                $filter['range_price'] = array('$lte'=>'0');
            }
        }
        // ??????????????????

        if( isset($clientData['is_effect']) && !empty($clientData['is_effect']))
        {
            $filter['is_effect'] = "{$clientData['is_effect']}";
        }

        // ??????????????????

        if( isset($clientData['is_end']) && !empty($clientData['is_end']) )
        {
            // ????????????
            if( $clientData['is_end'] == 1 )
            {
                $filter['purchase_order_status'] = array('$in'=>["9","11","14"]);
            }else{
                //?????????
                $filter['purchase_order_status'] = array('$nin'=>["9","11","14"]);
            }
        }

        //??????????????????stock_owes

        if( isset($clientData['completion_time_start']) && !empty($clientData['completion_time_start'])

            && isset($clientData['completion_time_end']) && !empty($clientData['completion_time_end']))
        {
            //11,9,14
            $filter['purchase_order_status'] = array('$in'=>["9","11","14"]);
            $filter['completion_time'] = array('$gte'=>$clientData['completion_time_start'],'$lte'=>$clientData['completion_time_end']);
        }

        // ?????????????????????

        if( isset($clientData['is_new_data']) && !empty($clientData['is_new_data']))
        {
            $filter['is_new_data'] = "{$clientData['is_new_data']}";
        }

        //????????????

        if( isset($clientData['is_purchasing']) && !empty($clientData['is_purchasing']))
        {
            $filter['is_purchasing'] = "{$clientData['is_purchasing']}";
        }
        return $filter;
    }

    /**
     * SKU ????????????????????????MYSQL ????????????????????????MONGDB?????????
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
                '?????????','??????','??????','??????','SKU','????????????','?????????','??????????????????','??????????????????',
                '???6???????????????','????????????','??????','??????','??????????????????','????????????','????????????','????????????',
                '??????????????????1','????????????','??????????????????','??????????????????2', '??????????????????','??????????????????','??????????????????',
                '????????????','????????????','?????????','????????????','??????????????????',  '????????????','??????????????????','?????????????????????','????????????','????????????'
            ];
            foreach($heads as $key => $item) {
                $title[$key] =iconv('UTF-8', 'GBK//IGNORE', $item);
            }
            //??????????????????????????????
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

                    $v_value_tmp['purchase_quantity'] = (isset($value['purchase_num']))?$value['purchase_num']:'';//????????????
                    $v_value_tmp['instock_qty'] = (isset($value['instock_qty']))?iconv('UTF-8', 'GBK//IGNORE', $value['instock_qty']):'';// ???????????????
                    // $v_value_tmp['breakage_number'] = (isset($value['breakage_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['breakage_number']):NULL;
                    //$v_value_tmp['actual_number'] =  (isset($value['actual_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['actual_number']):NULL; //??????????????????
                    //?????????????????????????????????*??????????????????
                    $v_value_tmp['price_change_total_1'] = (isset($value['price_change_1']))?$value['price_change_1']:'';
                    $v_value_tmp['cancel_ctq'] = (isset($value['cancel_ctq']))?$value['cancel_ctq']:'';//????????????
                    $v_value_tmp['effective_purchase_num'] = (isset($value['effective_purchase_num']))?$value['effective_purchase_num']:''; // ??????????????????
                    $v_value_tmp['price_change_total_2'] = (isset($value['price_change_2']))?$value['price_change_2']:'';
                    $v_value_tmp['end_time'] = (isset($value['end_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['end_time']):'';// ??????????????????
                    $v_value_tmp['is_end_name'] = (isset($value['is_end_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_end_name']):'';//??????????????????
                    $v_value_tmp['is_effect_name'] = (isset($value['is_effect_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_effect_name']):'';

                    $v_value_tmp['purchase_number'] =  (isset($value['purchase_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_number']):'';
                    $v_value_tmp['demand_number'] = (isset($value['demand_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['demand_number']):'';//????????????

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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }




        }
    }

    /**
     * SKU ??????????????????
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
                '?????????','??????','??????','??????','SKU','????????????','?????????','??????????????????','??????????????????',
                '???6???????????????','????????????','??????','??????','??????????????????','????????????','????????????','????????????',
                '??????????????????1','????????????','??????????????????','??????????????????2', '??????????????????','??????????????????','??????????????????',
                '????????????','????????????','?????????','????????????','??????????????????',  '????????????','??????????????????','?????????????????????','????????????'
            ];
            foreach($heads as $key => $item) {
                $title[$key] =iconv('UTF-8', 'GBK//IGNORE', $item);
            }
            //??????????????????????????????
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

                    $v_value_tmp['purchase_quantity'] = (isset($value['purchase_num']))?$value['purchase_num']:'';//????????????
                    $v_value_tmp['instock_qty'] = (isset($value['instock_qty']))?iconv('UTF-8', 'GBK//IGNORE', $value['instock_qty']):'';// ???????????????
                    // $v_value_tmp['breakage_number'] = (isset($value['breakage_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['breakage_number']):NULL;
                    //$v_value_tmp['actual_number'] =  (isset($value['actual_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['actual_number']):NULL; //??????????????????
                    //?????????????????????????????????*??????????????????
                    $v_value_tmp['price_change_total_1'] = (isset($value['price_change_1']))?$value['price_change_1']:'';
                    $v_value_tmp['cancel_ctq'] = (isset($value['cancel_ctq']))?$value['cancel_ctq']:'';//????????????
                    $v_value_tmp['effective_purchase_num'] = (isset($value['effective_purchase_num']))?$value['effective_purchase_num']:''; // ??????????????????
                    $v_value_tmp['price_change_total_2'] = (isset($value['price_change_2']))?$value['price_change_2']:'';
                    $v_value_tmp['end_time'] = (isset($value['end_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['end_time']):'';// ??????????????????
                    $v_value_tmp['is_end_name'] = (isset($value['is_end_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_end_name']):'';//??????????????????
                    $v_value_tmp['is_effect_name'] = (isset($value['is_effect_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_effect_name']):'';

                    $v_value_tmp['purchase_number'] =  (isset($value['purchase_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_number']):'';
                    $v_value_tmp['demand_number'] = (isset($value['demand_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['demand_number']):'';//????????????

                    $v_value_tmp['buyer_name'] = (isset($value['puchare_person']))?iconv('UTF-8', 'GBK//IGNORE', $value['puchare_person']):'';
                    $v_value_tmp['create_time'] = (isset($value['product_audit_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['product_audit_time']):'';
                    $v_value_tmp['purchase_status_mess'] = (isset($value['purchase_status_mess']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_status_mess']):'';

                    $v_value_tmp['is_superposition_name'] = (isset($value['is_superposition_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_superposition_name']):'';
                    $v_value_tmp['completion_time'] = ( isset($value['completion_time']))?iconv('UTF-8', 'GBK//IGNORE',$value['completion_time']):NULL;
                    $v_value_tmp['is_new_data_ch'] = ( isset($value['is_new_data_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_new_data_ch']):NULL;
                    $v_value_tmp['is_purchasing_ch'] = ( isset($value['is_purchasing_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_purchasing_ch']):NULL;
                    fputcsv($fp, $v_value_tmp);
                }
                //???1??????????????????????????????
//                ob_flush();
//                flush();

                // ???????????????pur_center_data ???
                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
            }
            $file_data = $this->upload_file($reduced_file);
            if (!empty($file_data['code'] == 200)) {
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        }
    }

    /**
     * ?????????????????????????????????
     */
    public function purchase_product_export()
    {

        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9505);
//??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {
//            $this->db->reconnect();
//            $this->db->initialize();
        });
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            //??????????????????
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
//            var_dump($data);
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'product_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_product_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/download_product_csv ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
//            $this->download_progress_csv($data);
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
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
        } elseif ($params['module_ch_name'] == 'PRODUCTAUDITDATA') { //??????????????????
            return $this->download_product_audit_csv($id);
        }
    }

    /**
     * ??????????????????
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
                '?????????????????????',
                '????????????', '????????????',
                '????????????', '??????????????????',
                '???????????????', '????????????',
                '??????????????????', '????????????',
                '???????????????', '???????????????',
                '???????????????', '???????????????',
                '?????????', '?????????', '????????????',
                '???????????????', '?????????????????????',
                '???????????????', '?????????????????????',
                '????????????', '??????', '??????',
                '???????????????', '?????????', '????????????',
                '????????????', '???????????????', '????????????',
                '??????', '??????????????????', '????????????',
                '????????????', '????????????', '?????????ID????????????',
                '???????????????????????????',
                '????????????','????????????',
                '?????????????????????','???????????????','???????????????','????????????','????????????','???????????????sku','1688?????????','????????????(g)','????????????','??????','SKU????????????',

                '?????????','????????????','????????????','????????????','????????????','??????????????????'];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //??????????????????????????????
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
                                    $buyer_type_name = '?????????';
                                } elseif (PURCHASE_TYPE_OVERSEA == $val['buyer_type']) {
                                    $buyer_type_name = '?????????';
                                } elseif (PURCHASE_TYPE_FBA_BIG == $val['buyer_type']) {
                                    $buyer_type_name = 'FBA??????';
                                } elseif (PURCHASE_TYPE_FBA == $val['buyer_type']) {
                                    $buyer_type_name = 'FBA';
                                } else {
                                    $buyer_type_name = '??????';
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
                        $v_value_tmp['export_model'] = iconv("UTF-8", "GBK//IGNORE", $v_value['export_model']);//??????????????????
                        $v_value_tmp['product_status'] = iconv("UTF-8", "GBK//IGNORE", getProductStatus($v_value['product_status']));//????????????

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
                        $v_value_tmp['ticketed_point'] = iconv("UTF-8", "GBK//IGNORE", $v_value['ticketed_point']);//??????
                        $v_value_tmp['supplier_name'] = iconv("UTF-8", "GBK//IGNORE", $v_value['supplier_name']);
                        $v_value_tmp['supplier_buyer_user'] = iconv("UTF-8", "GBK//IGNORE", rtrim($supplier_buyer_user, ','));
                        $v_value_tmp['product_cn_link'] = iconv("UTF-8", "GBK//IGNORE", $v_value['product_cn_link']);
                        $v_value_tmp['supply_status'] = iconv("UTF-8", "GBK//IGNORE", !empty($v_value['supply_status']) ? getProductsupplystatus($v_value['supply_status']) : '');
                        $v_value_tmp['supplier_source'] = iconv("UTF-8", "GBK//IGNORE", $v_value['supplier_source_ch']);
                        $v_value_tmp['is_abnormal'] = iconv("UTF-8", "GBK//IGNORE", !empty($v_value['is_abnormal']) ? getProductAbnormal($v_value['is_abnormal']) : '');
                        $v_value_tmp['note'] = $v_value['note'];


                        if (empty($v_value['is_invalid']) || $v_value['is_invalid'] == 0) {
                            $v_value_tmp['is_invalid'] = iconv("UTF-8", "GBK//IGNORE", '??????');
                        } else if ($v_value['is_invalid'] == 1) {
                            $v_value_tmp['is_invalid'] = iconv("UTF-8", "GBK//IGNORE", '??????');
                        }

                        $v_value_tmp['t_product_type'] = NULL;
                        if ($v_value['productismulti'] == 2) {

                            $v_value_tmp['t_product_type'] .= iconv("UTF-8", "GBK//IGNORE", ' SPU');
                        }

                        if ($v_value['producttype'] == 2) {

                            $v_value_tmp['t_product_type'] .= iconv("UTF-8", "GBK//IGNORE", ' ????????????');
                        }
                        if (($v_value['productismulti'] == 0 || $v_value['productismulti'] == 1) && $v_value['producttype'] == 1) {

                            $v_value_tmp['t_product_type'] .= iconv("UTF-8", "GBK//IGNORE", ' ??????');
                        }

                        if ($v_value['is_purchasing'] == 1) {
                            $v_value_tmp['is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", '???');
                        }
                        if ($v_value['is_purchasing'] == 2) {
                            $v_value_tmp['is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", '???');
                        }


                        // iconv("UTF-8", "GBK//IGNORE",'?????????')
                        $v_value_tmp['is_inspection'] = ($v_value['is_inspection'] == 1) ? iconv("UTF-8", "GBK//IGNORE", '?????????') : iconv("UTF-8", "GBK//IGNORE", '??????');
                        $v_value_tmp['is_equal_sup_id'] = iconv("UTF-8", "GBK//IGNORE", getEqualSupId($v_value['is_equal_sup_id']));
                        $v_value_tmp['is_equal_sup_name'] = iconv("UTF-8", "GBK//IGNORE", getEqualSupName($v_value['is_equal_sup_name']));
                        $v_value_tmp['audit_status_log_cn'] = iconv("UTF-8", "GBK//IGNORE", $v_value['audit_status_log_cn']);
                        $v_value_tmp['sku_message'] =preg_replace("/(\s|\&nbsp\;|???|\xc2\xa0)/", " ", strip_tags($v_value['sku_message']));//???????????????&nbsp;?????????
                        $v_value_tmp['sku_message'] =  iconv("UTF-8", "GBK//IGNORE", $v_value_tmp['sku_message']);

                        if(isset($v_value['sku_state_type']) && $v_value['sku_state_type'] == 6 ){

                            $v_value_tmp['sku_state_type_ch'] =  iconv("UTF-8", "GBK//IGNORE", '???');
                        }else{
                            $v_value_tmp['sku_state_type_ch'] =  iconv("UTF-8", "GBK//IGNORE", '???');
                        }
                        //$v_value_tmp['sku_state_type_ch'] =  iconv("UTF-8", "GBK//IGNORE", $v_value_tmp['sku_state_type_ch']);
                        $v_value_tmp['overseas'] =iconv("UTF-8", "GBK//IGNORE",$overseas );
                        $v_value_tmp['domatic'] =iconv("UTF-8", "GBK//IGNORE", $domatic);
                        $paymentData = $this->Supplier_model->get_supplier_payment($v_value['supplier_code']);
                        $v_value_tmp['settlement_method'] = iconv("UTF-8", "GBK//IGNORE",$paymentData);

                        $product_attributes = $this->product_model->get_product_attribute_data($v_value['sku']);

                        $overseas_ch = '';
                        if($product_attributes['????????????'] && !empty($product_attributes['????????????'])){
                            if(count($product_attributes['????????????']) == 1) {
                                if (in_array('?????????', $product_attributes['????????????'])) {

                                    $overseas_ch = iconv("UTF-8", "GBK//IGNORE","???");
                                }else{
                                    $overseas_ch = iconv("UTF-8", "GBK//IGNORE","???");
                                }
                            }else{

                                if (in_array('?????????', $product_attributes['????????????'])) {
                                    $overseas_ch = iconv("UTF-8", "GBK//IGNORE","??????");
                                }else{
                                    $overseas_ch = iconv("UTF-8", "GBK//IGNORE","???");
                                }
                            }
                        }

                        if( isset($v_value['is_shipping']) && $v_value['is_shipping'] == 1){

                            $v_value_tmp['is_shipping_ch'] = iconv("UTF-8", "GBK//IGNORE","??????");
                        }

                        if( isset($v_value['is_shipping']) && $v_value['is_shipping'] == 2){

                            $v_value_tmp['is_shipping_ch'] = iconv("UTF-8", "GBK//IGNORE","?????????");
                        }

                        $v_value_tmp['overseas_ch'] = $overseas_ch;
                        $v_value_tmp['starting_qty_ch'] = iconv("UTF-8", "GBK//IGNORE",$v_value['is_relate_ali']);
                        $v_value_tmp['product_weight_number'] = iconv("UTF-8", "GBK//IGNORE",$v_value['product_weight']);
                        $v_value_tmp['sample_packaging_type_ch'] =iconv("UTF-8", "GBK//IGNORE",$v_value['sample_packaging_type']);
                        $v_value_tmp['devliy_ch'] = iconv("UTF-8", "GBK//IGNORE",$v_value['devliy']);
                        if(strstr($roleString,'??????') || strstr($roleString,'??????') || strstr($roleString,'??????') || strstr($roleString,'??????')){

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

                            $v_value_tmp['is_customized_ch'] = iconv("UTF-8", "GBK//IGNORE","???");
                        }else{
                            $v_value_tmp['is_customized_ch'] = iconv("UTF-8", "GBK//IGNORE","???");
                        }

                        if( $v_value['long_delivery'] == 2){

                            $v_value_tmp['long_delivery_ch'] = iconv("UTF-8", "GBK//IGNORE","???");

                        }else{
                            $v_value_tmp['long_delivery_ch'] = iconv("UTF-8", "GBK//IGNORE","???");


                        }

                        $new_supplierAvg = $this->product_model->get_supplier_avg_day($v_value['supplier_code']);
                        $v_value_tmp['new_ds_day_avg'] = iconv("UTF-8", "GBK//IGNORE",isset($new_supplierAvg['ds_day_avg'])?$new_supplierAvg['ds_day_avg']:'-');
                        $v_value_tmp['new_os_day_avg'] =  iconv("UTF-8", "GBK//IGNORE",isset($new_supplierAvg['os_day_avg'])?$new_supplierAvg['os_day_avg']:'-');
                        $v_value_tmp['nvew_ds_deliverrate'] =  iconv("UTF-8", "GBK//IGNORE",isset($new_supplierAvg['ds_deliverrate'])?round(($new_supplierAvg['ds_deliverrate']*100),4)."%":'-');
                        $v_value_tmp['new_os_deliverrate'] =  iconv("UTF-8", "GBK//IGNORE",isset($new_supplierAvg['os_deliverrate'])?round(($new_supplierAvg['os_deliverrate']*100),4)."%":'-');


                        fputcsv($fp, $v_value_tmp);

                    }
                }
                // ???????????????pur_center_data ???
                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);

            } while ($redirectStdout);
            //  ??????????????????????????????
            $file_data = $this->upload_file($product_file);
            if (!empty($file_data['code'] == 200)) {
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        } else {
            echo '??????????????????';
        }
//        $down_host = CG_SYSTEM_WEB_FRONT_IP; //????????????
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
            // ??????CSV??????
            if (file_exists($product_file)) {
                unlink($product_file);
            }
//            fopen($product_file, 'w');
            $fp = fopen($product_file, "a+");
            $heads = array('????????????', '?????????', '????????????',
                '????????????', '?????????', '????????????',
                '????????????', 'sku', '????????????',
                '????????????', '?????????', '?????????',
                '????????????', '???????????????',
                '???????????????', '???????????????',
                '???????????????', '??????????????????',
                '??????????????????', '???????????????',
                '???????????????', '????????????',
                '???????????????', '???????????????',
                '??????????????????', '?????????',
                '????????????', '????????????', '????????????',
                '????????????', '????????????','????????????','????????????(?????????)',
                '????????????(?????????)','????????????(?????????)','????????????(?????????)','??????????????????','???????????????????????????','????????????(?????????)','SKU????????????','SKU???????????????','??????????????????','????????????');
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //??????????????????????????????
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
                            $v_value_tmp['old_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "???");
                        } else {
                            $v_value_tmp['old_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "???");
                        }

                        if ($value['new_is_purchasing'] == 1) {
                            $v_value_tmp['new_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "???");
                        } else {
                            $v_value_tmp['new_is_purchasing'] = iconv("UTF-8", "GBK//IGNORE", "???");
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

                        //????????????(1.??????,2.??????,3.??????,10:???????????????)
                        $v_value_tmp['old_supply_status_ch'] = '';
                        if( $value['old_supply_status'] == 1){

                            $v_value_tmp['old_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","??????");
                        }
                        if( $value['old_supply_status'] == 2){

                            $v_value_tmp['old_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","??????");
                        }
                        if( $value['old_supply_status'] == 3){

                            $v_value_tmp['old_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","??????");
                        }
                        if( $value['old_supply_status'] == 10){

                            $v_value_tmp['old_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","???????????????");
                        }

                        $v_value_tmp['new_supply_status_ch'] = '';
                        if( $value['new_supply_status'] == 1){

                            $v_value_tmp['new_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","??????");
                        }
                        if( $value['new_supply_status'] == 2){

                            $v_value_tmp['new_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","??????");
                        }
                        if( $value['new_supply_status'] == 3){

                            $v_value_tmp['new_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","??????");
                        }
                        if( $value['new_supply_status'] == 10){

                            $v_value_tmp['new_supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE","???????????????");
                        }
                        $v_value_tmp['product_create_time'] = iconv("UTF-8", "GBK//IGNORE",$value['product_create_time']);
                        $one_line_data = (!empty($category_all) && isset($category_all[0]))?$category_all[0]['product_line_name']:'';
                        $v_value_tmp['one_line_data'] = iconv("UTF-8", "GBK//IGNORE",$one_line_data);

                        $v_value_tmp['scree_time_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['scree_time_ch']);
                        $v_value_tmp['supply_status_ch'] = iconv("UTF-8", "GBK//IGNORE",$value['supply_status_ch']);



                        fputcsv($fp, $v_value_tmp);

                    }
                }
                // ???????????????pur_center_data ???
                if ($i * $limit < $total) {
                    $cur_num = $i * $limit;
                } else {
                    $cur_num = $total;
                }
                $result = $this->data_center_model->updateCenterData($id, ['progress' => $cur_num]);
            } while ($redirectStdout);

            //  ??????????????????????????????
            $file_data = $this->upload_file($product_file);
            if (!empty($file_data['code'] == 200)) {
                // ???????????????pur_center_data ???
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
                            $v_value_tmp['old_is_purchasing'] = "???";
                        } else {
                            $v_value_tmp['old_is_purchasing'] = "???";
                        }

                        if ($value['new_is_purchasing'] == 1) {
                            $v_value_tmp['new_is_purchasing'] = "???";
                        } else {
                            $v_value_tmp['new_is_purchasing'] = "???";
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
                'field_img_name' => array('????????????'),
                'field_img_key' => array('product_img_url'),
            );

            $this->success_json($result);
        }
    }

    function page_array($count, $page, $array, $order)
    {
//        global $countpage; #???????????????
        $page = (empty($page)) ? '1' : $page; #?????????????????????????????? ????????????????????????????????????
        $start = ($page - 1) * $count; #?????????????????????????????????
        if ($order == 1) {
            $array = array_reverse($array);
        }
//        $totals = count($array);
//        self::$countpage = ceil($totals / $count); #??????????????????
        $pagedata = array();
        $pagedata = array_slice($array, $start, $count);
        return $pagedata;  #??????????????????
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
            $return['msg'] = '????????????';
        } else {
//            throw new Exception('');
            $return['msg'] = '???????????????????????????';
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
     * ?????????????????????
     * @author ?????????
     */
    public function export_payable_list_handle()
    {
        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9506);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/export_payable_list ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/export_payable_list ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * ?????????????????????
     * @author Jolon
     */
    public function export_supplier_order_handle()
    {
        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9512);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'progress_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server_handle/supplier_balance ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server_handle/supplier_balance ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * ?????????????????????
     * @author ?????????
     * @time 2020-09-22
     */
    public function export_payable_list($id = null)
    {
        ini_set('memory_limit', '1500M');
        $res = false;
        if (empty($id)){
            echo '??????ID????????????';
            return $res;
        }
        $this->load->model('finance/Payment_order_pay_model');
        $param_list = $this->data_center_model->get_items("id = " . $id);
        if(!isset($param_list[0]) || empty($param_list[0])){
            echo '??????????????????????????????';
            return $res;
        }
        $param_list = $param_list[0];
        $params = json_decode($param_list['condition'], true);
        $role_list = $param_list['role'];
        if($role_list)$role_list = explode(',', $role_list);
        if (!is_array($params) || count($params) == 0){
            echo '???????????????????????????????????????';
            return $res;
        }
        $type = $params['type'];
        $type_str = $type == 1? "??????": "??????";

        // ????????????
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
            1 => '??????',
            2 => '??????',
            3 => '?????????'
        ];

        $progress_all = isset($param_list['number']) && $param_list['number'] > 0?(int)$param_list['number']: 1; // ????????????
        $progress = 0; // ??????

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
            // ????????????
            if($need_header){
                $heads = [
                    '?????????','????????????','????????????','????????????','?????????','????????????','?????????','????????????','????????????','???????????????','sku',
                    '????????????','????????????','????????????','????????????','????????????','????????????','????????????','??????????????????','????????????','????????????','?????????',
                    '??????','?????????','?????????','????????????','???????????????','???????????????','????????????','??????','????????????','???????????????',
                    '????????????','????????????','????????????','????????????','??????1688??????','?????????','?????????','??????????????????','???????????????','?????????????????????','????????????'
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
                    $val['is_ali_order'] == 1?'???':'???',
                    $val['applicant'] && in_array($val['applicant'], array_keys($applicant))? $applicant[$val['applicant']]:'',
                    $val['payer_name']??'',
                    $val['pur_tran_num']??'',
                    $val['trans_orderid']??'',
                    $val['source_subject']!=3?"???":'???',
                    $val['account_number']??'',
                ];

                $row_list = [];
                foreach ($rows as $v) {
                    if(preg_match("/[\x7f-\xff]/",$v)){
                        $v = stripslashes(iconv('UTF-8','GBK//IGNORE', $v));//????????????
                    }
                    if(is_numeric($v) && strlen($v) > 9){
                        $v =  $v."\t";//??????????????????csv???????????????????????????
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

        echo '???????????????';
        return $res;
    }

    /**
     * ????????????
     */
    private function get_handle_status()
    {
        $res = [
            "order"         => getPurchaseStatus(),
            "warehouse"     => [],
            "pay_status"    => getPayStatus(),
            "pay_type"=> [
                1 => "???????????????",
                2 => "????????????",
                3 => "????????????",
                4 => "paypal",
                5 => "???????????????",
                6 => "p???"
            ],
            "check_status"  => [
                0 => "????????????",
                1=>"???????????????",
                2=>"???????????????",
                3=>"???????????????",
                4=>"????????????",
                5=>"???????????????",
                6=>"??????????????????",
                7=>"??????????????????",
                8=>"??????",
                9=>"???IQC??????",
                10=>"????????????",
                11=>"???????????????"
            ],
            "loss_status"   => [
                0 => "???????????????",
                1 => "????????????",
                2 => "???????????????",
                3 => "????????????",
                4 => "?????????"
            ],
            "settlement_method"=>[],
            "purchase_user"=>[],
        ];
        // ??????
        $this->load->model('finance/Payment_order_pay_model');
        $res['warehouse'] = $this->Payment_order_pay_model->get_warehouse_data();
        $res['settlement_method'] = $this->Payment_order_pay_model->get_settlement_method_data();
        $res['purchase_user'] = $this->Payment_order_pay_model->get_purchase_user_data();
        return $res;
    }

    /**
     * ?????????????????????????????????
     * @author ?????????
     */
    public function export_payment_list_handle()
    {
        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9507);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'export_payment_list_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/export_payment_list ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/export_payment_list ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * ?????????????????????
     * @author ?????????
     */
    public function export_payment_list($id=null)
    {
        ini_set('memory_limit', '1500M');
        $res = false;
        if (empty($id)){
            echo '??????ID????????????';
            return $res;
        }
        $this->load->model('finance/Payment_order_pay_model');
        $param_list = $this->data_center_model->get_items("id = " . $id);
        if(!isset($param_list[0]) || empty($param_list[0])){
            echo '??????????????????????????????';
            return $res;
        }
        $param_list = $param_list[0];
        $params = json_decode($param_list['condition'], true);

        if (!is_array($params) || count($params) == 0){
            echo '???????????????????????????????????????';
            return $res;
        }

        // ????????????
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

        $progress = 0; // ??????

        do {
            // ????????????
            if($need_header){
                $heads =['????????????','????????????','????????????','???????????????','???????????????','????????????','?????????','????????????',
                    '??????','??????????????????','???????????????','??????????????????','k3??????','??????????????????','?????????????????????','??????????????????',
                    '?????????','????????????','????????????','?????????','?????????','????????????','????????????','??????????????????','????????????','?????????','??????','?????????','?????????',
                    '????????????','????????????','?????????','??????'];
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
                // ????????????=????????????,??????????????????????????????
                if($val['purchase_name'] == 'HKYB' AND stripos($val['pay_branch_bank'],"????????????") === false){
                    $receive_unit = $this->convertReceiveUnit($receive_unit);
                }

                try {
                    $row_list = [];
                    $row=[
                        $val['payer_time'],
                        $val['source'],//????????????
                        $val['purchase_name'],
                        $val['supplier_code'],
                        $val['supplier_name'],
                        $val['is_drawback'],
                        $val['purchase_type_id'],//?????????
                        $val['pay_category'],
                        $abstract_remark,//??????
                        $val['pay_number'],//??????????????????
                        $val['pay_branch_bank'],//???????????????
                        $val['pay_account_number'],//??????????????????
                        $val['k3_bank_account']."\t",//k3??????
                        $receive_unit,//??????????????????
                        $val['payment_platform_branch'],//?????????????????????
                        $val['receive_account'],//??????????????????
                        $val['compact_number'],//?????????
                        $val['statement_number'],//????????????
                        $val['pur_number'],//?????????
                        $val['pai_number'],//?????????
                        $val['buyer_name'],//?????????
                        $val['settlement_method'],//????????????
                        $val['pay_type'],//????????????
                        $val['pur_tran_num'],//??????????????????
                        $val['requisition_number'],//????????????
                        $val['product_money'],//?????????
                        $val['freight'],//??????
                        $val['discount'],//?????????
                        $val['process_cost'],//?????????
                        $val['commission'],//????????????
                        $val['pay_price'],//????????????
                        $val['payer_name'],//?????????
                        $val['finance_report_remark'],// ??????
                    ];
                    foreach ($row as $vvv) {
                        if(preg_match("/[\x7f-\xff]/",$vvv)){
                            $vvv = stripslashes(iconv('UTF-8','GBK//IGNORE', $vvv));//????????????
                        }
                        if(is_numeric($vvv) && strlen($vvv) > 9){
                            $vvv =  $vvv."\t";//??????????????????csv???????????????????????????
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
        echo '???????????????';
        return $res;
    }

    /**
     * ?????? ??????????????????
     * @param $receive_unit
     * @return mixed|string
     * @exp ?????????????????????????????????????????????(??????)  =??? ??????(?????????????????????????????????????????????)
     */
    public function convertReceiveUnit($receive_unit){
        $receive_unit_new = str_replace(['???','???'],['(',')'],$receive_unit);
        // ?????????????????????????????????????????????
        if(strlen($receive_unit) != strlen($receive_unit_new)){
            $flag = true;
        }else{
            $flag = false;
        }

        preg_match('/((?<=[\(])\S+[^\)])/',$receive_unit_new,$matt);// ????????????????????????
        preg_match('/([^\(]+)/',$receive_unit_new,$matt2);//????????????????????????
        if(!empty($matt) and isset($matt[1]) and !empty($matt2) and isset($matt2[1])){
            $begin = $matt2[1];
            $end = $matt[1];

            $receive_unit = $end.'('.$begin.')';
        }else{
            // ????????????
        }

        if($flag){
            $receive_unit = str_replace(['(',')'],['???','???'],$receive_unit);
        }
        return $receive_unit;
    }

    /**
     * ????????? ??????????????????????????????????????????????????????web3????????????
     * @link export_server/compact_statement_download_handle
     * @author Jolon
     */
    public function compact_statement_download_handle()
    {
        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 9508);// ??????????????????web3?????????
        echo "start work\n";
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});
        echo "start work2\n";
        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });
        echo "start work3\n";
        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'compact_statement_download_execute_' . date('Ymd') . '.txt';
            echo 'php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server_handle/compact_statement_download_execute ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1';
            exec('/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server_handle/compact_statement_download_execute ' . $data['id'] . ' >> ' . $log_dir . $log_file . ' 2>&1');
            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }

    /**
     * ???????????????????????????
     * @author:luxu
     * @time:2021???4???29???
     **/
    public function alternative_download_file(){

        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 2029);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });

        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'alternative_download_file' . date('Ymd') . '.txt';
            exec('/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/alternative_download_file_data ' . $data['id']);
            echo '/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/alternative_download_file_data ' . $data['id'];


            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
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
                      '????????????',
                      '????????????',
                      '????????????',
                      '???????????????',
                      '????????????',
                      '????????????','???????????????',
                      '????????????','????????????'
                      ,'???????????????','????????????????????????'
                      ,'????????????','????????????','????????????',
                      '???????????????','??????','????????????','?????????','????????????','?????????',
                      '????????????'
                     ];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //??????????????????????????????
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
                        $value_tmp['product_name']     = iconv("UTF-8", "GBK//IGNORE", $value['product_name']);//????????????
                        $value_tmp['product_status']       = iconv("UTF-8", "GBK//IGNORE", $value['product_status_ch']);//????????????
                        $value_tmp['is_purchasing_ch']      = iconv("UTF-8", "GBK//IGNORE", $value['is_purchasing_ch']);// ????????????
                        $value_tmp['product_line_datas']       = iconv("UTF-8", "GBK//IGNORE", $value['product_line_datas']);//????????????
                        $value_tmp['relationship'] = iconv("UTF-8", "GBK//IGNORE", $value['relationship']);//????????????
                        $value_tmp['source']         = iconv("UTF-8", "GBK//IGNORE", $value['source']);//????????????
                        $value_tmp['supplier_name']                = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);
                        $value_tmp['settlement_ch']                = iconv("UTF-8", "GBK//IGNORE", $value['settlement_ch']);
                        $value_tmp['cooper_status_ch']           = iconv("UTF-8", "GBK//IGNORE", $value['cooper_status_ch']);//???????????????
                        $value_tmp['supplier_source_ch']  = iconv("UTF-8", "GBK//IGNORE", $value['supplier_source_ch']);//????????????
                        $value_tmp['desc_audit_time']      = iconv("UTF-8", "GBK//IGNORE", $value['desc_audit_time']);//????????????
                        $value_tmp['purchase_price']      = iconv("UTF-8", "GBK//IGNORE", $value['purchase_price']);//????????????
                        $value_tmp['confirm_amount']      = iconv("UTF-8", "GBK//IGNORE", $value['confirm_amount']);//????????????
                        $value_tmp['product_cn_link']      = iconv("UTF-8", "GBK//IGNORE", "'".$value['product_cn_link']);//1688?????????
                        $value_tmp['starting_qty']       = iconv("UTF-8", "GBK//IGNORE", $value['starting_qty']);//????????????
                        $value_tmp['devliy']     = iconv("UTF-8", "GBK//IGNORE", $value['devliy']);// ????????????
                        $value_tmp['is_shipping_ch'] = iconv("UTF-8", "GBK//IGNORE", $value['is_shipping_ch']);//???????????????
                        $value_tmp['supplier_buyer_user'] = iconv("UTF-8", "GBK//IGNORE", "'" . $value['supplier_buyer_user']);//??????????????????

                        $value_tmp['create_user_name']   = iconv("UTF-8", "GBK//IGNORE", $value['create_user_name']);//????????????
                        $value_tmp['update_user']        = iconv("UTF-8", "GBK//IGNORE", $value['update_user']);//????????????
                        $value_tmp['update_time']    = iconv("UTF-8", "GBK//IGNORE", $value['update_time']); //????????????
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }
        } else {
            echo '??????????????????';
        }
//        $down_host = CG_SYSTEM_WEB_FRONT_IP; //????????????
        return $result;
    }


    public function product_import_data_swoole(){

        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 20612);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });

        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'alternative_download_file' . date('Ymd') . '.txt';
            exec('/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/product_import_data ' . $data['id']);
            echo '/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/product_import_data ' . $data['id'];


            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
            $this->get_queue_data($data);
        });
        $server->start();
    }


    /**
     * ??????????????????????????????
     * @author:luxu
     * @time:2021???6???10???
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
                    ->setInputEncoding('GBK') //???????????????????????????????????????boolean(false)?????????
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
                        'handle_action' => "????????????-SKU????????????",
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






                        if((empty($product_supplier) || !in_array($product_supplier,['??????','??????','??????','????????????'])) )
                        {

                            if( $encode == 'ASCII' && $product_supplier=='u505cu4ea7u627eu8d27'){

                                $supply_status_flag = 10;
                            }else {
                                $value[23] = "????????????????????????";
                                $error_data[] = $value;
                                continue;
                            }
                        }else{

                            if( $product_supplier == "??????")
                            {
                                $supply_status_flag = 1;
                            }

                            if( $product_supplier == "??????")
                            {
                                $supply_status_flag = 2;
                            }

                            if( $product_supplier == "??????")
                            {
                                $supply_status_flag =3;
                            }

                            if( $product_supplier == "????????????"){

                                $supply_status_flag = 10;
                            }
                        }

                        $is_purchasing = $value[10];
                        $is_purchasing_flag = NULL;
                        if( empty($is_purchasing) || !in_array($is_purchasing,['???','???']))
                        {
                            $value[23] = "????????????????????????";
                            $error_data[] = $value;
                            continue;
                        }else{

                            if( $is_purchasing == "???")
                            {
                                $is_purchasing_flag =2;
                            }

                            if( $is_purchasing == "???")
                            {
                                $is_purchasing_flag =1;
                            }
                        }

                        $is_customizedData = $value[20];
                        if(empty($is_customizedData)){

                            if($skuMessages['is_customized'] == 1){

                                $is_customizedData = "???";
                            }else{
                                $is_customizedData = "???";
                            }
                        }
                        if( $is_customizedData == "???")
                        {
                            $is_customizedData_ch =1;
                        }else{
                            $is_customizedData_ch =2;
                        }

                        $is_long_delivery = $value[21];
                        if(empty($is_long_delivery)){

                            if($skuMessages['long_delivery'] == 1){

                                $is_long_delivery = "???";
                            }else{
                                $is_long_delivery = "???";
                            }
                        }

                        if( $is_long_delivery == "???"){

                            $is_long_delivery_ch = 1;
                        }else{
                            $is_long_delivery_ch =2;
                        }

                        $is_shipping = $value[22];

                        if( $is_shipping == "???" || $is_shipping == "??????"){

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
                                $value[23] = "??????????????????,?????????????????????????????????????????????";
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
                    $messvalue['pushMessage'] = "????????????:".count($out)."?????????,"."??????:"
                        .$result['success_total'].",?????????".$result['error_total'];
                    if($result['error_total']>0){
                        $messvalue['pushMessage'].=",????????????:".implode(",",array_values($result['error_sku']));

                        foreach($result['error_sku'] as $error_key=>$error_val){
                            
                            $messvalue['pushMessage'].= " SKU:".$error_key."?????????:".$error_val.".";
                        }
                    }
                    $messvalue['type'] = 'SKU????????????';
                    $messvalue['module'] = 'product_import_data';
                    $messvalue['create_time'] = date("Y-m-d H:i:s",time());
                    $messvalue['param'] = '';
                    $messvalue['recv_name'] = $productDatas['uid'];
                    $messvalue['apply_id'] = '';

                    $ci = get_instance();
                    //??????redis??????
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
                        'handle_action' => "????????????-SKU????????????",
                        'handle_msg' =>'????????????',
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

        //tcp?????????
        $server = new \Swoole\Server(SWOOLE_SERVER, 2025);
        //??????4???????????????
        $server->set(array('task_worker_num' => 4));
        $server->on('workerstart', function (\Swoole\Server $server, $workerId) {});

        //?????????????????????
        $server->on('receive', function (\Swoole\Server $server, $fd, $from_id, $data) {
            $task_id = $server->task($data);
            echo "????????????????????????: id=$task_id\n";
            $server->send($fd, "???????????????????????????...");
        });

        $server->on('task', function (\Swoole\Server $server, $fd, $from_id, $data) {
            echo "????????????????????????\n";
            $data = json_decode($data, true);
            $log_dir = APPPATH . 'logs/export_logs/';
            $log_file = 'alternative_download_file' . date('Ymd') . '.txt';
            exec('/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/import_abnormal_data ' . $data['id']);
            echo '/usr/local/php/bin/php ' . dirname(dirname(dirname(__FILE__))) . '/index.php  export_server/import_abnormal_data ' . $data['id'];


            $server->finish('???????????? OK');
        });
        $server->on('finish', function (\Swoole\Server $server, $task_id, $data) {
            echo "????????????[$task_id] ????????????: $data" . PHP_EOL;
            echo '?????????????????????';
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
                '????????????',
                '?????????',
                '????????????',
                '???????????????',
                '?????????CODE',
                '????????????',
                '???????????????',
                '??????SKU??????',
                '????????????',
                 '????????????'
                ,'????????????',
                '?????????'
                ,'????????????',
                '?????????','????????????',
                '????????????'
            ];
            foreach ($heads as $key => $item) {
                $title[$key] = iconv("UTF-8", "GBK//IGNORE", $item);
            }
            //??????????????????????????????
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
                        $value_tmp['create_buyer_name']     = iconv("UTF-8", "GBK//IGNORE", $value['create_buyer_name']);//????????????
                        $value_tmp['warehouse_name']       = iconv("UTF-8", "GBK//IGNORE", $value['warehouse_name']);//????????????
                        $value_tmp['supplier_name']      = iconv("UTF-8", "GBK//IGNORE", $value['supplier_name']);// ????????????
                        $value_tmp['supplier_code']       = iconv("UTF-8", "GBK//IGNORE", $value['supplier_code']);//????????????
                        $value_tmp['problem_name'] = iconv("UTF-8", "GBK//IGNORE", $value['problem_name']);//????????????
                        $value_tmp['supplier_reply']         = iconv("UTF-8", "GBK//IGNORE", $value['supplier_reply']);//????????????
                        $value_tmp['exception_number']                = iconv("UTF-8", "GBK//IGNORE", $value['exception_number']);
                        $value_tmp['instock_qty']                = iconv("UTF-8", "GBK//IGNORE", $value['instock_qty']);
                        $value_tmp['proportion']           = iconv("UTF-8", "GBK//IGNORE", $value['proportion']);//???????????????

                        $value_tmp['improve_ch']  = iconv("UTF-8", "GBK//IGNORE", $value['improve_ch']);//????????????
                        $value_tmp['update_buyer_name']      = iconv("UTF-8", "GBK//IGNORE", $value['update_buyer_name']);//????????????
                        $value_tmp['update_time']      = iconv("UTF-8", "GBK//IGNORE", $value['update_time']);//????????????
                        $value_tmp['buyer_name']      = iconv("UTF-8", "GBK//IGNORE", $value['buyer_name']);//????????????
                        $value_tmp['group_name']      = iconv("UTF-8", "GBK//IGNORE", "'".$value['group_name']);//1688?????????
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
                // ???????????????pur_center_data ???
                $result = $this->data_center_model->updateCenterData($params['id'], ['file_name' => $file_name, 'down_url' => $file_data['filepath'], 'data_status' => 3]);
            }



        }


    }

}