<?php
/**
 * 退货跟踪模块.
 * User: totoro
 * Date: 2020/3/2
 * Time: 16:11
 */

class Purchase_return_tracking extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Purchase_return_tracking_model', 'purchase_return');
        $this->load->model('product/product_model');
        $this->load->helper('status_order');
        $this->load->helper('common');
    }

    /**
     * 货跟踪list
     */
    public function get_storage_list()
    {
        $return_status = $this->input->get_post('return_status');//退货状态
        $is_confirm_receipt = $this->input->get_post('is_confirm_receipt');//是否签收
        $track_status = $this->input->get_post('track_status');//物流状态
        $refuse_track_status = $this->input->get_post('refuse_track_status');//物流状态
        $supplier_code = $this->input->get_post('supplier_code');//供应商编码
        $upload_time_start = $this->input->get_post('upload_time_start');//截图开始时间
        $upload_time_end = $this->input->get_post('upload_time_end');//截图结束时间
        $return_number = $this->input->get_post('return_number');//退货单号
        $main_number = $this->input->get_post('main_number');//申请ID
        $express_number = $this->input->get_post('express_number');//快递单号
        $refund_serial_number = $this->input->get_post('refund_serial_number');//退款流水号
        $buyer_id = $this->input->get_post('buyer_id');//采购员
        $part_number = $this->input->get_post('part_number');//申请子id



        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        if (empty($limit) or $limit < 0) $limit = 20;
        $offset = ($page - 1) * $limit;
        $params = array(
            'return_status' => $return_status,          //退货状态
            'is_confirm_receipt' => $is_confirm_receipt,     //是否签收
            'track_status' => $track_status,           //物流状态
            'refuse_track_status' => $refuse_track_status,    //物流状态
            'supplier_code' => $supplier_code,          //供应商编码
            'upload_time_start' => $upload_time_start,      //截图开始时间
            'upload_time_end' => $upload_time_end,        //截图结束时间
            'return_number' => $return_number,          //退货单号
            'main_number' => $main_number,            //申请ID
            'express_number' => $express_number,         //快递单号
            'refund_serial_number' => $refund_serial_number,    //退款流水号
            'buyer_id' => $buyer_id,
            'part_number'=>$part_number
        );
        $result = $this->purchase_return->get_storage_collection_list($params, $offset, $limit, $page);
        $this->success_json($result['data_list'], $result['page_data']);
    }

    /**
     * 供应商签收  状态变成待上传截图
     */
    public function check_receipt_confirmation()
    {
        $return_number = $this->input->get_post('return_number');
        $is_confirm_receipt = $this->input->get_post('is_confirm_receipt');
        $confirm_receipt_remark = $this->input->get_post('confirm_receipt_remark');
        if (empty($return_number)) {
            $this->error_json('退货单必填！');
        }
        if($is_confirm_receipt == 2){
            if (empty($confirm_receipt_remark)) {
                $this->error_json('签收备注必填！');
            }
        }
        //判断状态是不是待供应商签收状态
        $fase = $this->purchase_return->is_return_status($return_number);
        if ($fase) {
            $this->error_json($return_number.":退货单状态不是待供应商签收状态");
        }
        $result = $this->purchase_return->receipt_confirmation($return_number, $is_confirm_receipt, $confirm_receipt_remark);
        if ($result['success']) {
            $this->success_json([], null, '操作成功');
        } else {
            $this->error_json('操作失败');
        }
    }

    /**
     * 保存上传截图
     */
    public function save_upload_return_file()
    {
//        $return = ' {"return_number":"QTH-1912170009","act_refund_amount":"204.4","remark":"收款","return_item":[{"refund_serial_number":"THLS001010101","refund_time":"2020-03-07","refund_amount":"102.2","file_path":"htpp:010,http:020"},
//{"refund_serial_number":"THLS001010101","refund_time":"2020-03-07","refund_amount":"102.2","file_path":"htpp:010,http:020"}]
//}';
        $return = $this->input->post_get('return_data'); //数组格式
        if (!is_json($return)) $this->error_json('请求参数错误');
        $data = json_decode($return, true);
        if (empty($data)) $this->error_json('请求参数错误');

        $result = $this->purchase_return->save_upload_return_file($data);

        if ($result['success']) {
            $this->success_json([], null, '操作成功');
        } else {
            $this->error_json('操作失败');
        }
    }

    /**
     * 获取物流商信息
     */
    public function get_express_info()
    {
        $this->load->model('Logistics_carrier_model', 'm_logistics', false, 'purchase');
        $return_number = $this->input->post_get('return_number'); //勾选数据
        if (empty($return_number)) {
            $this->error_json('请勾选数据');
        }
        //判断是否已经录入
        $is_express = $this->purchase_return->is_express_info($return_number);
        if ($is_express) {
            $this->error_json('退货单对应的物流信息已经录入!');
        }
        $this->data['drop_down_box']['cargo_company'] = $this->m_logistics->getLogisticsCompany();
        if (empty($this->data)) {
            $this->error_json('获取信息失败');
        } else {
            $this->success_json($this->data);
        }
    }

    /**
     * 录入快递单号
     */
    public function save_express_info()
    {
        $contact_person = $this->input->post_get('contact_person'); //联系人
        $contact_number = $this->input->post_get('contact_number'); //联系电话
        $contact_addr = $this->input->post_get('contact_addr'); //联系地址
        $return_number = $this->input->post_get('return_number'); //退货单号
        $express_items = $this->input->post_get('express_items'); // 录入快递单号明细
//        $express_items = '[{"express_company_name":"河北建华","express_company_code":"HBJH","express_number":"HJ0805405410400"},{"express_company_name":"河北建华","express_company_code":"HBJH","express_number":"HJ0805405410400"}]';


        $fase = $this->purchase_return->is_return_status($return_number, RETURN_STATUS_SUPPLIER_RECEIPT_FAIL);
        if ($fase) {
            $this->error_json($return_number.'退货单状态非供应商签收失败状态！');
        }

        if (empty($return_number)) {
            $this->error_json('退货单号必填!');
        }
        if (empty($contact_person) or empty($contact_number) or empty($contact_addr)) {
            $this->error_json('收件人信息有误!');
        }
        $data = json_decode($express_items, true);
        if (empty($express_items) OR !is_json($express_items) or empty($data)) $this->error_json('快递单号明细有误');

        $result = $this->purchase_return->save_express_info($return_number, $contact_person, $contact_number, $contact_addr, $data);
        if ($result['success']) {
            $this->success_json([], null, '操作成功');
        } else {
            $this->error_json($result['message'].'操作失败');
        }
    }

    /**
     * 获取详情信息
     */
    public function get_storage_item_list()
    {
        $return_number = $this->input->post_get('return_number'); //退货单号
        if (empty($return_number)) {
            $this->error_json('退货单号必填!');
        }
        //获取
        $this->data['item_list'] = $this->item_list($return_number);
        $this->data['refund_list'] = $this->purchase_return->get_refund_flow_info($return_number);
        $this->data['storage_collection'] = $this->purchase_return->get_storage_collection_info($return_number);
        $this->success_json($this->data);
    }

    /**
     * 详情信息
     */
    public function item_list($return_number)
    {
        if (empty($return_number)) return [];
        $result = $this->purchase_return->get_storage_item_list($return_number);
        $detail = array();
        if (!empty($result)) {
            $sku_list = array_column($result, 'sku');
            $this->load->model('Warehouse_model', 'm_warehouse', false, 'warehouse');
            $purchase_on_way_map = $this->m_warehouse->get_total_purchase_on_way($sku_list);
            foreach ($result as $item) {
                $productInfo = $this->product_model->get_product_info($item['sku'],'product_img_url,product_thumb_url');

                if($productInfo){
                   $product_img_url = $productInfo['product_thumb_url']?erp_sku_img_sku_thumbnail($productInfo['product_thumb_url']):erp_sku_img_sku($productInfo['product_img_url']);//缩略图地址
                }else{
                    $product_img_url =[];
                }

                $detail[] = [
                    'main_number' => $item['main_number'] ?? '',//申请主ID
                    'part_number' => $item['part_number'] ?? '',//申请子ID
                    'sku' => $item['sku'] ?? '',//sku
                    'sample_packing_weight' => $item['sample_packing_weight'] ?? '',//样品包装重量
                    'product_name' => $item['product_name'] ?? '',//产品名称
                    'supplier_name' => $item['supplier_name'] ?? '',//供应商名称
                    'can_match_inventory' => 0,//可配库库存
                    'purchase_on_way' => $purchase_on_way_map[$item['sku']] ?? 0,//采购在途
                    'unit_price_without_tax' => $item['unit_price_without_tax'] ?? '',//未税单价
                    'return_qty' => $item['return_qty'] ?? '',//退货数量(申请)
                    'pur_return_qty' => $item['pur_return_qty'] ?? '',//退货数量(采购确认)
                    'return_cost' => $item['return_cost'] ?? '',//退货产品成本
                    'return_unit_price' => $item['return_unit_price'] ?? '',//退货单价
                    'return_amount' => $item['return_amount'] ?? '',//退货金额
                    'freight' => $item['freight'] ?? '',//运费
                    'freight_payment_type' => $item['freight_payment_type'] ?? '',//运费类型
                    'return_reason' => $item['return_reason'] ?? '',//退货原因
                    'restricted_supplier' => empty($item['restricted_supplier']) ? '' : json_decode($item['restricted_supplier'], true),//需限制的供应商
                    'contact_person' => $item['contact_person'] ?? '',//退货联系人
                    'contact_number' => $item['contact_number'] ?? '',//退货联系方式
                    'contact_province' => $item['contact_province'] ?? '',//收货地址(省)
                    'contact_addr' => $item['contact_addr'] ?? '',//收货地址(详细地址)
                    'remark' => $item['remark'] ?? '',//采购备注
                    'return_warehouse_code' => $item['return_warehouse_code'] ?? '',//申请退货仓库
                    'product_img_url ' => $product_img_url //图片
                ];
            }
        }
        return $detail;
    }


    /**
     * 物流轨迹查询
     */
    public function get_logistics_trajectory(){
        $express_no = $this->input->get_post('express_no');
        $order_type = $this->input->get_post('order_type');
        if (empty($express_no)) {
            $this->error_json('快递单号不能为空');
        } elseif (empty($order_type)) {
            $this->error_json('参数order_type不能为空');
        }
        //返回数据
        $data_list = array();
        //2.合同单和退货单从快递鸟接口获取物流轨迹数据
        $_result = $this->purchase_return->get_Logistics_Trajectory($express_no, $order_type);
        $_result = json_decode($_result, true);
        if (empty($_result['Traces'])) {
            $this->error_json('查询成功，轨迹详情数据为空。');
        }

        //轨迹详情
        foreach ($_result['Traces'] as $key => $content) {
            //组织返回前端数据
            $occur_time = $content['AcceptTime'];
            $occur_date = date('Y-m-d', strtotime($occur_time));

            $data_list[$occur_date][] = array(
                'track_content' => $content['AcceptStation'],
                'occur_date' => $occur_time,
            );
        }
        $this->success_json($data_list, null, '查询成功');
    }


    /**
     * 导出数据
     */

    public function export_storage_data(){
        $return_status = $this->input->get_post('return_status');//退货状态
        $is_confirm_receipt = $this->input->get_post('is_confirm_receipt');//是否签收
        $track_status = $this->input->get_post('track_status');//物流状态
        $refuse_track_status = $this->input->get_post('refuse_track_status');//物流状态
        $supplier_code = $this->input->get_post('supplier_code');//供应商编码
        $upload_time_start = $this->input->get_post('upload_time_start');//截图开始时间
        $upload_time_end = $this->input->get_post('upload_time_end');//截图结束时间
        $return_number = $this->input->get_post('return_number');//退货单号
        $main_number = $this->input->get_post('main_number');//申请ID
        $express_number = $this->input->get_post('express_number');//快递单号
        $refund_serial_number = $this->input->get_post('refund_serial_number');//退款流水号
        $buyer_id = $this->input->get_post('buyer_id');//采购单号

        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        if (empty($limit) or $limit < 0) $limit = 20;
        $offset = ($page - 1) * $limit;
        $params = array(
            'return_status' => $return_status,          //退货状态
            'is_confirm_receipt' => $is_confirm_receipt,     //是否签收
            'track_status' => $track_status,           //物流状态
            'refuse_track_status' => $refuse_track_status,    //物流状态
            'supplier_code' => $supplier_code,          //供应商编码
            'upload_time_start' => $upload_time_start,      //截图开始时间
            'upload_time_end' => $upload_time_end,        //截图结束时间
            'return_number' => $return_number,          //退货单号
            'main_number' => $main_number,            //申请ID
            'express_number' => $express_number,         //快递单号
            'refund_serial_number' => $refund_serial_number,    //退款流水号
            'buyer_id' => $buyer_id
        );

        $total = $this->purchase_return->export_sum($params);

        $template_file = 'return_storage_'.date('YmdHis').mt_rand(1000,9999).'.csv';
        if($total>100000){//一次最多导出10W条
            $template_file = 'return_storage.csv';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }
        //前端路径
        $webfront_path = dirname(dirname(APPPATH));
        $product_file = $webfront_path.'/webfront/download_csv/'.$template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $is_head = false;
        $fp = fopen($product_file, "a");
        if($total>0){
            $per_page = 20;
            $total_page = ceil($total/$per_page);
            for($i = 1;$i<= $total_page;$i++){
                $offset = ($i - 1) * $per_page;
                $data = $this->purchase_return->export_storage_list($params,$offset,$per_page);
                if(!empty($data['value'])){
                    foreach ($data['value'] as $key => $value){
                        $row =[
                            $value['return_number'],
                            $value['main_number'],
                            $value['supplier_code'],
                            $value['supplier_name'],
                            $value['contact_person'],
                            $value['contact_number'],
                            $value['contact_province'].$value['contact_addr'],
                            $value['refund_product_cost'],
                            $value['refundable_amount'],
                            $value['act_freight'],
                            $value['act_refund_amount'],
                            $value['wms_shipping_time'],
                            '',
                            $value['is_confirm_time'],
                            $value['is_confirm_receipt'],
                            '',//采购员
                            '',//拒绝物流跟踪号
                            $value['upload_screenshot_time'],
                            $value['refund_serial_number'],
                            $value['colletion_user_name'],
                            $value['colletion_time'],
                            $value['colletion_remark'],
                            $value['return_status']
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
                            $heads=['退货单号','申请ID','供应商编码','供应商','联系人','联系方式','退货地址','退货产品成本','退货金额'
                                ,'实际运费','实际退款金额','仓库发货时间','物流轨迹','确认签收时间','是否确认签收','采购员',
                                '拒收后的快递单号','上传截图时间','退款流水号','财务审核人','审核时间','财务备注','状态'];

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
                }
            }
        }
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url= $down_host.'download_csv/'.$template_file;
        $this->success_json($down_file_url);
    }

}