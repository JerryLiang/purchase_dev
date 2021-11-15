<?php

/**
 * Created by PhpStorm.
 * 发票清单控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_invoice_list extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('purchase_invoice_model','m_invoice',false,'purchase');
        $this->load->model('purchase_invoice_list_model','m_invoice_list',false,'purchase');
        $this->load->model('declare_customs_model','declare_customs');
        $this->load->model('invoice_information_model','information',false,'system');
        $this->load->model('product_model','product_model',false,'product');
        $this->load->model('supplier_model','supplier_model',false,'supplier');
        $this->load->model('purchase_order_model');
        $this->load->model('purchase_order_items_model');
        $this->load->model('purchase_order_tax_model');
        $this->load->model('purchase_user_model','purchase_user_model',false,'user');
        $this->load->model('warehouse_results_model','warehouse_results',false,'warehouse');
    }

    /**
     * 返回列表筛选的接收参数
     * @author Manson
     */
    public function get_select_params()
    {
        $params = [
            'invoice_number' => $this->input->get_post('invoice_number'), // 发票清单号
            'purchase_number' => $this->input->get_post('purchase_number'), //采购单号
            'purchase_user_id' => $this->input->get_post('purchase_user_id'), // 采购员
            'supplier_code' => $this->input->get_post('supplier_code'), //供应商名称
            'create_time_start' => $this->input->get_post('create_time_start'), //开始时间
            'create_time_end' => $this->input->get_post('create_time_end'), //结束时间
            'audit_status' => $this->input->get_post('audit_status'), //状态 1[待确认] 2[待采购开票] 3[待财务审核] 4[已审核] 5[财务驳回]
            'invoice_code_left' => $this->input->get_post('invoice_code_left'), //发票代码（左）
            'invoice_code_right' => $this->input->get_post('invoice_code_right'), //发票号码（右）
            'compact_number' => $this->input->get_post('compact_number'), //合同号
            'is_gateway'     => $this->input->get_post('is_gateway'), // 是否推送门户
            'purchase_type' => $this->input->get_post('purchase_type'), // 业务线
        ];

//        foreach ($params as &$val){
//            $val = trim($val);
//        }
        return $params;
    }

    /**
     * 发票清单列表
     * /purchase/purchase_invoice_list/get_invoice_listing_list
     * @author Manson 2019-11-29
     */
    public function get_invoice_listing_list() {
        $params = $this->get_select_params();

        $page_data=$this->format_page_data();
        $orders_info = $this->m_invoice_list->get_invoice_listing_list($params, $page_data['offset'], $page_data['limit'],$page_data['page']);

        $this->format_purchase_invoice_list($orders_info['data_list']['value']);

        $role_name=get_user_role();//当前登录角色
        $orders_info['data_list']['value'] = ShieldingData($orders_info['data_list']['value'],['supplier_name','supplier_code'],$role_name,NULL);

        $this->success_json($orders_info['data_list'],$orders_info['paging_data']);

    }

    /**
     * 组织列表数据
     * @author Manson
     */
    public function format_purchase_invoice_list(&$data)
    {
        //发票清单下的所有po
        $invoice_number_list = array_column($data,'invoice_number');
        $invoice_po_map = $this->m_invoice_list->get_invoice_po($invoice_number_list);

        foreach ($data as $key => &$item){
            $item['audit_status'] = invoice_number_status($item['audit_status']);
            $item['purchaser_number'] = array_unique($invoice_po_map[$item['invoice_number']]??[]);
        }
    }


    /**
     * 点击批量开票 弹出开票详情
     * 可勾选多个发票清单号
     * /purchase/purchase_invoice_list/get_batch_invoice_detail
     * @author Manson
     */
    public function get_batch_invoice_detail()
    {
        $ids = $this->input->get_post('ids');
       // $ids = explode(',',$ids);
        $params['ids'] = $ids;
        //分页
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        if(empty($limit)){

            $limit = 100;
        }

        $offset = ($page-1)*$limit;

        if(isset($params['ids']) && !empty($params['ids'])){
            $field='a.invoice_number,a.sku,a.demand_number,a.purchase_number,a.unit_price,a.invoice_coupon_rate,
            b.supplier_name,b.invoice_amount,a.app_invoice_qty,
            c.product_name,c.export_cname,c.customs_code';
            $main_info = $this->m_invoice_list->get_batch_invoice_detail($params,$offset, $limit,$field);
            if(empty($main_info['value'])) {
                $this->error_json('暂无相关数据');
            }
//pr($main_info);exit;
            //报关信息
            $purchase_number_list = array_unique(array_column($main_info['value'],'purchase_number'));
            $customs_clearance_map = $this->declare_customs->get_customs_clearance_details($purchase_number_list);
            foreach ($main_info['value'] as $item){
                $_tag = sprintf('%s_%s',$item['purchase_number'],$item['sku']);
                $item['customs_number'] = $customs_clearance_map[$_tag]['customs_number']??[];
                $item['customs_name'] = $customs_clearance_map[$_tag]['customs_name']??'';
                $item['customs_unit'] = $customs_clearance_map[$_tag]['customs_unit']??'';
                $item['customs_quantity'] = $customs_clearance_map[$_tag]['customs_quantity']??0;
                $item['customs_type'] = $customs_clearance_map[$_tag]['customs_type']??'';
                $item['total_amount'] = bcmul($item['unit_price'],$item['app_invoice_qty'],2);
                $item['ps_total_amount'] = bcmul($item['unit_price'],$item['app_invoice_qty'],2);//po+sku维度的总金额 可开票数量*含税单价
                $data_list[$item['invoice_number']][] = $item;
            }
            $main_info['value'] = $data_list;
//            pr($data_list);exit;
            $main_info['key'] = ['SKU','备货单号','产品名称', '开票品名', '采购单号', '供应商名称', '报关单号', '出口海关编码',
                '报关品名', '报关数量', '含税单价', '总金额', '报关型号', '报关单位', '可开票数量', '发票代码(左)', '发票代码(右)'];
            $main_info['page_data']['pages'] = ceil($main_info['page_data']['total']/$limit);
            $main_info['page_data']['offset'] = $page;

            $this->success_json($main_info);
        }else{
            $this->error_json('参数错误,ids为空');
        }
    }



    /**
     * 发票清单列表提交弹出列表
     * /purchase/purchase_invoice_list/submit_detail
     * @author Manson
     */
    public function submit_detail(){
        $invoice_number = $this->input->get_post('invoice_number'); //发票清单号

        if(empty($invoice_number)){
            $this->error_data_json('发票清单号不能为空！');
        }else{
            //根据开票清单号取数据
            $result = $this->m_invoice_list->get_submit_detail($invoice_number);
            $data_list['key'] = array('SKU','产品名称','开票品名','采购单号','采购员','采购下单时间','供应商名称','实际入库数量',
                '已开票数量','可开票数量', '报关单号','报关品名','含税单价','报关数量','报关总金额','报关单位','是否报关','报关时间');
            if(!empty($result)){
                $purchase_number_list = array_column($result,'purchase_number');
                //报关信息
                $this->load->model('declare_customs_model','m_declare_customs',false,'purchase');
                $customs_map = $this->m_declare_customs->get_customs_clearance_details($purchase_number_list);

                //入库
                $items_id_list = array_column($result,'items_id');
                $instock_field = 'items_id,instock_date';
                $instock_map = $this->warehouse_results->get_instock_list_by_items_ids($items_id_list,$instock_field);
//                pr($customs_map);
//                pr($result);exit;
                foreach ($result as $key => &$item) {
                    $ps_tag = sprintf('%s_%s',$item['purchase_number'],$item['sku']);
                    // ------ 报关start ------
                    $item['customs_number'] = $customs_map[$ps_tag]['customs_number']??[];
                    $item['customs_quantity'] = $customs_map[$ps_tag]['customs_quantity']??'';
                    $item['customs_name'] = $customs_map[$ps_tag]['customs_name']??'';
                    $item['customs_type'] = $customs_map[$ps_tag]['customs_type']??'';
                    $item['customs_time'] = $customs_map[$ps_tag]['customs_time']??'';
                    $customs_unit = $customs_map[$ps_tag]['customs_unit']??'';
                    $item['customs_unit'] = $this->purchase_order_tax_model->get_new_declare_unit($customs_unit);//
                    $item['customs_total_price'] = bcmul($item['unit_price'],$item['customs_quantity'],3);//报关总金额
                    $item['is_customs'] = $item['customs_quantity'] > 0?'是':'否';//是否报关
                    // ----- 报关end --------
                    // ----- 入库start ------
                    $item['instock_date'] = $instock_map[$item['items_id']]??'';//最新一次入库时间

                }
            }
            $data_list['value'] = $result;
//            $declare_customs_list_info['page_data']['pages'] = ceil($declare_customs_list_info['page_data']['total']/$limit);
//            $declare_customs_list_info['page_data']['offset'] = $page;
            if(!empty($data_list['value'])){
//                $invoice_info = $this->m_invoice->get_invoice_one($invoice_number);
//                $declare_customs_list_info['invoice_create_time'][$invoice_number] = $invoice_info['create_time'];
                $this->success_json($data_list);
            }else{
                $this->error_data_json('暂无相关数据');
            }
        }
    }


    /**
     * 发票清单列表财务审核弹出列表
     * /purchase/purchase_invoice_list/submit_financial_audit_invoice_list
     * @author Jaden 2019-1-10
     */
    public function submit_financial_audit_invoice_list(){
        $invoice_number = $this->input->get_post('invoice_number'); //发票清单号
        //分页
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page-1)*$limit;
        if(empty($invoice_number)){
            $this->error_data_json('发票清单号不能为空！');
        }else{
            //根据开票清单号取数据
            $filed='a.purchase_number,a.sku,a.customs_number,a.customs_name,a.customs_unit,a.customs_quantity,o.purchase_unit_price as unit_price,a.customs_type,a.customs_time,a.invoice_code_left,a.invoice_code_right';
            $declare_customs_list_info = $this->declare_customs->getByinvoice_number_list($invoice_number,$offset, $limit,$filed,true);
            $declare_customs_list_info['key'] = array('sku','品名','采购单号','供应商名称','报关单号','报关品名','报关数量','含税单价','总金额','报关型号','报关单位','发票代码(左)','发票号码(右)');
            $data_list = $declare_customs_list_info['value'];
            $return_data = array();
            foreach ($data_list as $key => $value) {
                $order_info = $this->purchase_order_model->get_one($value['purchase_number'],false);
                $order_items_info = $this->purchase_order_items_model->get_item($value['purchase_number'],$value['sku'],1);
                $declare_customs_list_info['value'][$key]['supplier_name'] = $order_info['supplier_name'];
                $declare_customs_list_info['value'][$key]['product_name'] = $order_items_info['product_name'];
                $declare_customs_list_info['value'][$key]['buyer_name'] = $order_info['buyer_name'];
                $declare_customs_list_info['value'][$key]['order_create_time'] = $order_info['create_time'];
                $declare_customs_list_info['value'][$key]['total_price'] = round($value['customs_quantity']*$value['unit_price'],3);
                //开票单位中含有2个或者以上单位时，生成的开票合同中的开票单位需要删除千克
                $declare_unit = isset($value['customs_unit']) ? $value['customs_unit'] : '';
                $new_declare_unit = $this->purchase_order_tax_model->get_new_declare_unit($declare_unit);
                $declare_customs_list_info['value'][$key]['customs_unit'] = $new_declare_unit;
            }
            if(!empty($data_list)){
                $declare_customs_list_info['invoice_number'] = $invoice_number;
                $declare_customs_list_info['page_data']['pages'] = ceil($declare_customs_list_info['page_data']['total']/$limit);
                $declare_customs_list_info['page_data']['offset'] = $page;
                $this->success_json($declare_customs_list_info);
            }else{
                $this->error_data_json('暂无相关数据');
            }
        }
    }



    /**
     * 发票清单列表提交操作
     * /purchase/purchase_invoice_list/submit_invoice
     * @author Jaden 2019-1-10
     */
    public function submit_invoice(){
        $invoice_number = $this->input->get_post('invoice_number'); //发票清单号
        if(empty($invoice_number)){
            $this->error_data_json('发票清单号不能为空！');
        }else{
            $result = $this->m_invoice_list->get_one($invoice_number);
            $supplier_code = $result['supplier_code'];
            if ( isset($result['audit_status']) && $result['audit_status'] == INVOICE_STATES_WAITING_CONFIRM){
                //改变发票清单状态为待采购开票
                $data['audit_status'] = INVOICE_STATES_WAITING_MAKE_INVOICE;
                $data['submit_user'] = getActiveUserName();
                $data['submit_time'] = date('Y-m-d H:i:s');
                $result = $this->m_invoice->submit_invoice($invoice_number,$data);
                // 判断发票清单是否推送到门户系统
                $gateWaysDatas = $this->m_invoice_list->getInvoiceGateWays(NULL,$invoice_number);
                if(!empty($gateWaysDatas)){
                    $ids = array_column( $gateWaysDatas,"id");
                    $this->m_invoice_list->pushGateWays($ids);
                }else{

                    // 如果不推送门户系统，判断发票清单对应的供应商
                    $supplierFlag = $this->supplier_model->getSupplierMessage([$supplier_code]);
                    if(!empty($supplierFlag)){

                        $gateWaysDatas = $this->m_invoice_list->getInvoiceMessage(NULL,$invoice_number);
                        $ids = array_column( $gateWaysDatas,"id");
                        $this->m_invoice_list->pushGateWays($ids);
                        $this->m_invoice_list->setInvoiceGateWays($ids);
                    }
                }
                if($result){
                    $this->success_json('操作成功！');
                }else{
                    $this->error_data_json('操作失败，请稍后再试！');
                }
            }else{
                $this->error_data_json('操作失败，该发票清单号状态不是待提交！');
            }

        }
    }


    /**
     * 发票清单列表撤销操作
     * /purchase/purchase_invoice_list/revoke_invoice
     * @author Manson 2019-11-30
     */
    public function revoke_invoice(){
        $invoice_number = $this->input->get_post('invoice_number'); //发票清单号
        if(empty($invoice_number)){
            $this->error_data_json('发票清单号不能为空！');
        }else{
            //点击撤销操作，删除该发票清单
            $result = $this->m_invoice->delete_invoice($invoice_number);
            if($result['code'] == 1){
                 $this->success_json('撤销成功！');
            }else{
                $this->error_data_json($result['msg']);  
            }
        }
    }

    /**
     * 下载发票明细(导出)
     * /purchase/purchase_invoice_list/download_export
     * @author Jaden 2019-1-11
     */
    public function download_export(){
        $invoice_number = $this->input->get_post('invoice_number'); //发票清单号
        if(empty($invoice_number)){
            $this->error_data_json('发票清单号不能为空！');
        }
        //转化数组
        $invoice_number= explode(',', $invoice_number);
        $invoice_number= array_unique($invoice_number);
        $limit = FALSE;
        $offset = FALSE;
        $filed='*';
        $invoice_list_arr = $this->declare_customs->getByinvoice_number_list($invoice_number,$offset, $limit,$filed,true);
//        pr($invoice_list_arr);exit;
        $invoice_list = $invoice_list_arr['value'];
        $tax_list_tmp = [];
        if(!empty($invoice_list)){
            $invoice_list_error_list = '';

            $skus = array_unique(array_column(isset($invoice_list)?$invoice_list:[], 'sku'));
            $product_field = 'sku,customs_code,declare_cname,declare_unit,export_cname';
            $sku_arr = $this->product_model->get_list_by_sku($skus,$product_field);

            foreach ($invoice_list as $key => $v_value) {

                //检测该发票清单号状态，待采购开票才可以下载
                $invoice_info = $this->m_invoice->get_invoice_one($v_value['invoice_number']);
                if(INVOICE_TO_BE_FINANCIAL_AUDIT!=$invoice_info['audit_status']){//待财务审核状态才能下载
                    $invoice_list_error_list.= $v_value['invoice_number'].',';
                }

                //开票单位中含有2个或者以上单位时，生成的开票合同中的开票单位需要删除千克
                $declare_unit = isset($v_value['customs_unit']) ? $v_value['customs_unit'] : '';
                $new_declare_unit = $this->purchase_order_tax_model->get_new_declare_unit($declare_unit);
  
                $product_info = $this->product_model->get_product_info($v_value['sku']);
                $v_value_tmp                       = [];
                $v_value_tmp['invoice_number']                = $v_value['invoice_number'];
                $v_value_tmp['demand_number']  = $v_value['demand_number'];//备货单号
                $v_value_tmp['sku']  = $v_value['sku'];//sku
                $v_value_tmp['product_name']  = !empty($product_info)?$product_info['product_name']:'';
                $v_value_tmp['export_cname']  = isset($sku_arr[$v_value['sku']])?$sku_arr[$v_value['sku']]['export_cname']:'';//开票品名
                $v_value_tmp['purchase_number']         = $v_value['purchase_number'];
                $v_value_tmp['supplier_name']         = $invoice_info['supplier_name'];
                $v_value_tmp['customs_number']         = iconv("UTF-8", "GBK//IGNORE",$v_value['customs_number'])."\t";
                $v_value_tmp['customs_code']  = isset($sku_arr[$v_value['sku']])?$sku_arr[$v_value['sku']]['customs_code']."\t":'';//出口海关编码
                $v_value_tmp['customs_quantity']       =  $v_value['customs_quantity'];
                $v_value_tmp['customs_name']       = $v_value['customs_name'];
                $v_value_tmp['unit_price']       = $v_value['purchase_unit_price'];
                
                $v_value_tmp['uumber_invoices']       = $v_value['uumber_invoices'];
                $v_value_tmp['vat_tax_rate']          = $v_value['vat_tax_rate'];
                $v_value_tmp['invoiced_amount']       = $v_value['invoiced_amount'];
                $v_value_tmp['invoice_amount']        = $v_value['invoice_amount'];
                $v_value_tmp['taxes']                 = $v_value['taxes'];
                
                $v_value_tmp['total_price']        = round($v_value['customs_quantity']*$v_value_tmp['unit_price'],3);
                $v_value_tmp['currency']           = CURRENCY; //币种
                $v_value_tmp['customs_unit']       = $new_declare_unit;
                $v_value_tmp['customs_type']       = $v_value['customs_type'];

                $invoice_code_left_list = explode(',', $v_value['invoice_code_left']);
                $invoice_code_right_list = explode(',', $v_value['invoice_code_right']);
                foreach ($invoice_code_left_list as $k => $val) {
                    $v_value_tmp['invoice_code_left'][$k] = $val."\t";
                    $v_value_tmp['invoice_code_right'][$k] = $invoice_code_right_list[$k]."\t";
                }
                $tax_list_tmp['invoice_list'][] = $v_value_tmp;
                $tax_list_tmp['heads_num'][] = count($invoice_code_left_list);
            }
            if(!empty($invoice_list_error_list)){
                $this->error_data_json(substr($invoice_list_error_list,0,-1).'发票清单号不是待财务审核状态，不能下载。！');
            }else{
                $this->success_json($tax_list_tmp);
            }
        }else{
            $this->error_data_json('找不到相关数据');
        }
        
    }

    /**
     * 下载开票合同页面
     * /purchase/purchase_invoice_list/download_view
     * @author Jaden 2019-1-11
     */
    public function download_view(){
        $print_billingcontract = getConfigItemByName('api_config','cg_system','webfornt','print_billingcontract');
        $invoice_number = $this->input->get_post('invoice_number'); //发票清单号
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        if(empty($invoice_number)){
            $this->error_data_json('发票清单号不能为空！');
        }else{
            //根据开票清单号取数据
            try{
                $data_list = $this->m_invoice_list->invoice_contract_detail($invoice_number);
//                pr($data_list);exit;
                $return_data = array();
                if(!empty($data_list)){

                    $skus = array_unique(array_column(isset($data_list)?$data_list:[], 'sku'));
                    $product_field = 'sku,customs_code,declare_cname,declare_unit,export_cname';
                    $sku_arr = $this->product_model->get_list_by_sku($skus,$product_field);
                    foreach ($data_list as $key => $value) {
                        $order_info = $this->purchase_order_model->get_one($value['purchase_number'],false);
                        $order_items_info = $this->purchase_order_items_model->get_item($value['purchase_number'],$value['sku'],1);
                        $data_list[$key]['supplier_name'] = $order_info['supplier_name'];
                        $data_list[$key]['product_name'] = $order_items_info['product_name'];
                        $data_list[$key]['buyer_name'] = $order_info['buyer_name'];
                        $data_list[$key]['order_create_time'] = $order_info['create_time'];
                        $data_list[$key]['total_price'] = bcmul($value['invoiced_qty'],$value['unit_price'],2);
                        $data_list[$key]['customs_code'] = isset($sku_arr[$value['sku']])?$sku_arr[$value['sku']]['customs_code']:'';
                        //开票单位中含有2个或者以上单位时，生成的开票合同中的开票单位需要删除千克
                        $declare_unit = isset($value['declare_unit']) ? $value['declare_unit'] : '';
                        $new_declare_unit = $this->purchase_order_tax_model->get_new_declare_unit($declare_unit);
                        $data_list[$key]['declare_unit'] = $new_declare_unit;

                    }
                    //获取开票资料表信息
                    $invoice_info = $this->m_invoice->get_invoice_one($invoice_number);
                    if (empty($invoice_info)){
                        $this->error_data_json('未查询到该发票清单号');
                    }
                    $information_info = $this->information->getInformationByKey($invoice_info['purchase_name']);
                    if (empty($invoice_info)){
                        $this->error_data_json('采购主体信息查询为空');
                    }
                    $information_info['invoice_number'] = $invoice_number;
                    $information_info['create_time'] = $invoice_info['create_time'];
                    //根据采购员ID取手机号
                    $user_info = $this->purchase_user_model->get_user_info_by_user_id($invoice_info['purchase_user_id']);
                    $information_info['iphone'] = !empty($user_info)?$user_info['phone_number']:'';
                    $information_info['buyer_name'] = $invoice_info['purchase_user_name'];

                    // 将开票地址，改为公司地址
                    $information_info['address'] = $information_info['company_address'];

                    unset($information_info['id']);
                    $return_data['invoice_list'] = $data_list;
                    $return_data['information_info'] = $information_info;
                    //根据供应商CODE取数据
                    $supplierinfo = $this->supplier_model->get_supplier_info($invoice_info['supplier_code']);
                    if(!empty($supplierinfo)){
                        $return_data['supplier_info'] = $supplierinfo;
                    }else{
                        $this->error_data_json('找不到供应商信息');
                    }
                    $key = "billing_compact";//缓存键
                    $data=json_encode($return_data);
                    $this->rediss->setData($key, $data);
                    $html = file_get_contents($print_billingcontract);
                    $this->success_json(['html' => $html,'key' => $key]);
                }else{
                    $this->error_data_json('暂无相关数据');
                }
            }catch(Exception $e){
                $this->error_json('-1',$e->getMessage());
            }
        }
    }


    /**
     * 下载开票合同批量上传开票信息
     * /purchase/purchase_invoice_list/download_import
     * @author Jaden 2019-1-11
     */
    public function download_import(){
        $import_json = $this->input->get_post('import_arr');
        $result_list = json_decode($import_json,true);
        $errorMsg = '';
        $invoice_list_arr = array();
        if(!empty($result_list)){
            foreach ($result_list as $key => $value) {
                if($key==0){
                    continue;
                }
                $where_data = array();
                $update_data = array();
                $invoice_number = $value[0];//发票清单号
                $sku = $value[2];//SKU
                $purchase_number = $value[4];//采购单号
                $invoice_code_left =$value[14];//发票代码（左1）
                $invoice_code_right = $value[15];//发票号码（右1）

                if(empty($invoice_number) || empty($sku) || empty($purchase_number)){
                    $errorMsg.='请检查第'.$key.'行发票清单号、SKU、采购单号是否为空';
                    break; 
                }
                //检测发票清单表是否有这条清单数据数据
                $invoice_info = $this->m_invoice->get_invoice_one($invoice_number);
                if(empty($invoice_info)){
                    $errorMsg.='第'.$key.'行发票清单号不存在';
                    break;
                }
                if(!empty($invoice_info)){
                   if($invoice_info['audit_status']!=INVOICE_STATES_WAITING_MAKE_INVOICE){
                        $errorMsg.='发票清单号'.$invoice_number.'不是待采购开票状态，不能导入';
                        break; 
                   }   
                }
                //检测发票代码是否为空
                if( (empty($invoice_code_left) or strlen($invoice_code_left)!=10) or (empty($invoice_code_right) or strlen($invoice_code_right)!=8) ){
                    $invoice_arr[]= $invoice_number;
                    $errorMsg.='第'.$key.'行发票代码(左1)或发票号码(右1)错误';
                    break;
                }
                $invoice_code_data = $this->check_invoice_code($value);
                if($invoice_code_data['code']==0){
                    $errorMsg.= '第'.$key.'行'.$invoice_code_data['errorMsg'].'代码错误';
                    break; 
                }else{
                    $invoice_code_left.= isset($invoice_code_data['invoice_code_left'])?$invoice_code_data['invoice_code_left']:'';
                    $invoice_code_right.= isset($invoice_code_data['invoice_code_right'])?$invoice_code_data['invoice_code_right']:'';
                }
               $invoice_list_arr[] = [
                    'invoice_number'=> $invoice_number,
                    'sku'=> $sku,
                    'purchase_number'=> $purchase_number,
                    'invoice_code_left'=> $invoice_code_left,
                    'invoice_code_right'=> $invoice_code_right

                ];
            }
            if(!empty($invoice_list_arr) && empty($errorMsg)){
                foreach ($invoice_list_arr as $k => $val) {
                    $where_data = array('invoice_number' => $val['invoice_number'], 'sku' => $val['sku'], 'purchase_number' => $val['purchase_number']);
                    $update_data = array('invoice_code_left' => $val['invoice_code_left'], 'invoice_code_right' => $val['invoice_code_right']);
                    $result = $this->declare_customs->update_invoice_code($where_data,$update_data);
                    //改变发票清单状态
                    if($result){
                        $audit_status = INVOICE_STATES_WAITING_FINANCE_AUDIT;
                        $this->m_invoice->change_states($val['invoice_number'],$audit_status);
                    }
                }
            }
        }else{
            $errorMsg = '没有数据';
        }
        if(empty($errorMsg)){
            $this->success_json('导入成功');
        }else{
            $this->error_data_json($errorMsg);
        }

    }

    /**
     * 批量上传发票清单(下模板)
     /purchase/purchase_invoice_list/download_import_model
     * @author Jaden 2019-4-09
     */
    public function download_import_model(){
        $template_file = 'invoice_list_num.csv';
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=$down_host.'download_csv/'.$template_file;
        $this->success_json($down_file_url);
    }


    /**
     * 验证导入的发票清单代码数据
     * @author Jaden 2019-4-09
     */
    public function check_invoice_code($data){
        $data_num = count($data);
        $vkey = $data_num-2;
        $errorMsg = '';
        $invoice_code_left_all = '';
        $invoice_code_right_all = '';
        for ($i=$vkey; $i >15 ; $i--) {
            if(isset($data[$i]) && !empty($data[$i])){
                $d = $i%2;//除2取余数，判断是是发票代码左 还是 发票代码右 余数等于0[发票代码左] 余数等于1[发票代码右]
                if($d==0){
                    $invoice_code_left=  $data[$i];
                    $invoice_code_right=  $data[$i+1];
                    if( (empty($invoice_code_left) or strlen($invoice_code_left)!=10) or (empty($invoice_code_right) or strlen($invoice_code_right)!=8) ){
                        //$errorMsg.='第'.($i+1).'行发票代码错误'.',';  
                        $errorMsg.= '发票代码(左'.(($i-12)/2).')'.'发票号码(右'.(($i-12)/2).')'.','; 
                    }
                    $invoice_code_left_all.= $data[$i].',';
                    $invoice_code_right_all.= $data[$i+1].',';   
                }
            }   
        }
        if(!empty($errorMsg)){
            $result_data['code'] = 0;
            $result_data['errorMsg'] = $errorMsg;
        }else{
            $result_data['code'] = 1;
            $result_data['invoice_code_left'] = join(',',array_reverse(explode(",", $invoice_code_left_all)));
            $result_data['invoice_code_right'] = join(',',array_reverse(explode(",", $invoice_code_right_all)));
        }
        return $result_data;
        
    }



    /**
     * 获取发票清单状态
     * /purchase/purchase_invoice_list/get_invoice_status
     * @author Jaxton 2019-1-21
     */
    public function get_invoice_status(){
        $this->load->helper('status_order');
        $list=invoice_number_status();
        $this->success_json($list);
    }

    /**
     * /purchase/Purchase_invoice_list/batch_invoice_submit
     * 批量开票提交
     * @author Manson 2019-1-11
     */
    public function batch_invoice_submit(){

        $invoice_code_data = json_decode($this->input->get_post('invoice_code_data'),true);
        if(empty($invoice_code_data)){
            $this->error_json('请填写信息');
        }


        foreach($invoice_code_data as $key=>&$data){

            foreach($data as $data_key=>&$value){
                //发票金额 = 开票数量 * 含税单价/(1+票面税率)
                $value['invoice_value'] = round(($value['invoiced_qty']*$value['unit_price'])/(1+$value['invoice_coupon_rate']),2);
                //开票金额 = 开票数量 * 含税单价
                $value['invoiced_amount'] = $value['invoiced_qty']*$value['unit_price'];
                //税金 = 开票金额 - 发票金额
                $value['taxes'] = round($value['invoiced_amount'] - $value['invoice_value'],2);
            }
        }

        $invoice_number_list = array_keys($invoice_code_data);
        $app_invoice_qty_info = $this->m_invoice_list->get_app_invoice_qty($invoice_number_list);//可开票数量
        $result = $this->m_invoice->validate_data_format($invoice_code_data,$app_invoice_qty_info);
        if ($result['code'] == 0){
            $this->error_json($result['error_msg']);
        }elseif ($result['code'] == 1 && !empty($result['success_data']) && !empty($result['update_status'])){
            $result=$this->m_invoice->batch_invoice_submit($result['success_data'],$result['update_status'],$invoice_number_list);
            if($result['success']){
                $pushData = [];
               foreach($invoice_code_data as $key=>$data_value){
                   foreach($data_value as $k=>$value) {

                       $where = [

                           'invoice_number' => $value['invoice_number'],
                           'sku' => $value['sku'],
                           'invoice_code_left' => $value['invoice_code_left'],
                           'invoice_code_right' => $value['invoice_code_right'],
                           'purchase_number' =>$value['purchase_number']
                       ];

                       $childrenNumber = $this->m_invoice->childrenNumber($where);
                       //$invoicePriceData = $this->m_invoice->invoicePrice($value['invoice_number']);
                       $pushData[] = [

                           'invoiceNumber' => $value['invoice_number'],
                           'sku' => $value['sku'],
                           'invoicedQty' => $value['invoiced_qty'],
                           'invoiceCouponRate' => $value['invoice_coupon_rate'],
                           'invoiceValue' => $value['invoice_value'],
                           'taxes' => $value['taxes'],
                           'invoicedAmount' =>  $value['invoiced_amount'],
                           'invoiceCodeLeft' => $value['invoice_code_left'],
                           'invoiceCodeRight' => $value['invoice_code_right'],
                           'invoiceImage' => '',
                           'purchaseNumber' =>$value['purchase_number'],
                           'invoiceNumberSub' => (isset($childrenNumber['children_invoice_number']))?$childrenNumber['children_invoice_number']:''
                       ];
                   }
               }
               if(!empty($pushData)){
                   $header        = array('Content-Type: application/json');
                   $access_taken  = getOASystemAccessToken();
                   $url           = getConfigItemByName('api_config','charge_against','pushInvoiceItem');
                   $url           = $url."?access_token=".$access_taken;
                   $result        = getCurlData($url,json_encode(['item'=>$pushData],JSON_UNESCAPED_UNICODE),'post',$header);

                   $insertData =[
                       'pushdata' => json_encode($pushData, JSON_UNESCAPED_UNICODE),
                       'returndata' => $result,
                       'type' => 'pushGateWaysUrl'
                   ];
                   $this->db->insert('invoice_data_log',$insertData);
               }

                $this->success_json('开票成功');
            }else{
                $this->error_json($result['error_msg']);
            }
        }else{
            $this->error_json('开票失败');
        }
    }


    /**
     * 获取导入的数据
     */
    public function get_import_data()
    {
        try {
            $params = $_GET;
            if (!isset($params['file_path']) or empty($params['file_path'])) {
                $this->error_json( "文件地址参数缺失");
            }
            $file_path = $params['file_path'];
            if (!file_exists($file_path)) {
                $this->error_json("文件不存在[{$file_path}]");
            }
            $file_name_arr = explode('.', $file_path);

            if (end($file_name_arr) != 'csv') {
                http_response(response_format(0, [], '只接受csv格式的文件'));
            }

            $handle = fopen($file_path, 'r');
            if ($handle === FALSE) {
                http_response(response_format(0, [], '打开文件资源失败'));
            }

            $title = fgetcsv($handle, 100000);
            foreach ($title as $val) {
                $_title[] = iconv('gbK', 'utf-8//IGNORE', $val);
            }
            $this->check_import_title($_title);

            $out          = [];
            $invoice_list = [];
            $n            = 2;
            while ($data = fgetcsv($handle, 100000)) {
                foreach ($data as $k => &$val) {
                    $val = trim($val);
                    $val = iconv('gbK', 'utf-8//IGNORE', $val);
                    if ($k == 0) {
                        if (empty($val)) {
                            throw new Exception(sprintf('第%s行,第%s列,发票清单号不能为空', $n, $k + 1));
                        } else {
                            $invoice_number = $val;
                        }
                    }
                    if ($k == 1) {
                        if (empty($val)) {
                            throw new Exception(sprintf('第%s行,第%s列,备货单号不能为空', $n, $k + 1));
                        } else {
                            $demand_number = $val;
                        }
                    }
                    if ($k == 2) {
                        if (empty($val)) {
                            throw new Exception(sprintf('第%s行,第%s列,SKU不能为空', $n, $k + 1));
                        } else {
                            $sku = $val;
                        }
                    }
                    if (empty($val)) {//后续的数据里不能存在空值, 发票清单号 备货单 sku为必填
                        continue;
                    }
                    if (($k - 3) % 4 == 0 && $k > 2) {
                        if (isset($invoice_list[$invoice_number])) {
                            $i = $invoice_list[$invoice_number] + 1;
                        } else {
                            $i = 0;
                        }
                        $out[$invoice_number][$i]['invoice_number']    = $invoice_number;
                        $out[$invoice_number][$i]['demand_number']     = $demand_number;
                        $out[$invoice_number][$i]['sku']               = $sku;
                        $out[$invoice_number][$i]['invoice_code_left'] = $val;
                        $invoice_list[$invoice_number]                 = $i;
                    }
                    if (($k - 4) % 4 == 0 && $k > 2) {
                        $out[$invoice_number][$i]['invoice_code_right'] = $val;
                    }
                    if (($k - 5) % 4 == 0 && $k > 2) {
                        $out[$invoice_number][$i]['invoice_coupon_rate'] = $val;
                    }
                    if (($k - 6) % 4 == 0 && $k > 2) {
                        $out[$invoice_number][$i]['invoiced_qty'] = $val;
                    }
                }
                $n++;
            }
            $total_invoice = count($out);
            if ($total_invoice > 10000) {
                throw new Exception('数据量过大,请分批导入');
            }
            return $out;
        }catch (Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 批量导入开票信息
     */
    public function import_invoice_info()
    {


        try{
//            $import_json = $this->input->get_post('import_arr');
////            $import_json = $params['import_arr'];
//            $result_list = json_decode($import_json,true);
//            pr($result_list);exit;
            $result_list = $this->get_import_data();

            if(!empty($result_list)){
                $invoice_number_list = array_keys($result_list);//所有的发票清单号
                //通过发票清单号查询 备货单号  确认含税单价和采购单号 可开票数量
                $map = $this->m_invoice_list->import_invoice_get_info($invoice_number_list);
                $purchase_info_map = $map['purchase_info_map']??[];
                foreach ($result_list as $key => &$item) {
                    //组织数据格式
                    foreach ($item as &$val){
                        if (!isset($val['invoice_code_left'])){
                            throw new Exception(sprintf('发票清单号为%s,发票代码(左)为空',$val['invoice_number']));
                        }
                        if (!isset($val['invoice_code_right'])){
                            throw new Exception(sprintf('发票清单号为%s,发票代码(右)为空',$val['invoice_number']));
                        }
                        if (!isset($val['invoice_coupon_rate'])){
                            throw new Exception(sprintf('发票清单号为%s,票面税率为空',$val['invoice_number']));
                        }
                        if (!isset($val['invoiced_qty'])){
                            throw new Exception(sprintf('发票清单号为%s,已开票数量为空',$val['invoice_number']));
                        }

                        $_tag = sprintf('%s%s',$val['invoice_number'],$val['demand_number']);
                        $val['purchase_number'] = $purchase_info_map[$_tag]['purchase_number']??'';//采购单号
                        $val['purchase_unit_price'] = $purchase_info_map[$_tag]['purchase_unit_price']??0;//含税单价

                        //计算数据
                        //发票金额=已开票数量*含税单价/（1+票面税率）   查:含税单价
                        $val['invoice_value'] = bcmul($val['invoiced_qty'], $val['purchase_unit_price'],3);
                        $tax_1 = bcadd(1,$val['invoice_coupon_rate'],3);
                        $val['invoice_value'] = bcdiv($val['invoice_value'], $tax_1,2);
                        //税金=发票金额*票面税率
                        $val['taxes'] = bcmul($val['invoice_value'],$val['invoice_coupon_rate'],2);
                        //已开票金额=已开票数量*含税单价
                        $val['invoiced_amount'] = bcmul($val['invoiced_qty'],$val['purchase_unit_price'],2);
                    }
                }
                if (!empty($result_list)){
                    $result = $this->m_invoice->validate_data_format($result_list,$purchase_info_map);
                    if ($result['code'] == 0){
                        $this->error_json($result['error_msg']);
                    }elseif ($result['code'] == 1 && !empty($result['success_data']) && !empty($result['update_status'])){
                        $result=$this->m_invoice->batch_invoice_submit($result['success_data'],$result['update_status'],$invoice_number_list);
                        if($result['success']){
                            $this->success_json('开票成功');
                        }else{
                            $this->error_json($result['error_msg']);
                        }
                    }else{
                        throw new Exception('开票失败');
                    }
                }

            }else{
                throw new Exception('没有数据');
            }

        }catch (Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 验证导入的文件标题
     * @author Manson
     * @param $title
     * @return array
     * @throws Exception
     */
    public function check_import_title($title){
        $title_rule = [
          0 => '发票清单号',
          1 => '备货单号',
          2 => 'SKU',
        ];
        $col_count = count($title);
        if ($col_count<7){//上传的文件必填项有7列
            throw new Exception(sprintf('上传的文件不符合模板的规范,温馨提示:还需额外添加发票代码的，请以此规则添加标题，并录入发票代码。发票清单号,sku,采购单号必填。'));
        }


        $col_count = $col_count - 3;
        foreach ($title_rule as $key => $name){//1.前3列按模板要求填
            if (isset($title[$key]) && $name != $title[$key]){
                throw new Exception(sprintf('上传的文件标题不符合规范:%s第1行第%s列',$name,$key+1));
            }else{
                unset($title[$key]);
            }
        }

        if ($col_count % 4 != 0){//2.发票代码(左) 发票代码(右) 票面税率 已开票数量  这四个字段是要一起的
            $error_title = '';
            $error_col = $col_count % 4;
            for ($i=0;$i<$error_col;$i++){
                $error_title .= array_pop($title).',';
            }
            throw new Exception(sprintf('%s,上传的文件不符合模板的规范,温馨提示:还需额外添加发票代码的，请以此规则添加标题，并录入发票代码。发票清单号,sku,采购单号必填。',$error_title));
        }


        $invoice_count = $col_count/4;
        for ($i=1;$i<=$invoice_count;$i++){
            $invoice_info_title[] = sprintf('发票代码(左%s)',$i);
            $invoice_info_title[] = sprintf('发票代码(右%s)',$i);
            $invoice_info_title[] = sprintf('票面税率%s',$i);
            $invoice_info_title[] = sprintf('已开票数量%s',$i);
        }

        $diff1 = array_diff($invoice_info_title,$title);
        $diff2 = array_diff($title,$invoice_info_title);

        if (!empty($diff1) || !empty($diff2)){
            throw new Exception(sprintf('上传的文件标题不符合规范:%s,请修改为正确的格式:%s',implode(',',$diff2),implode(' ',$invoice_info_title)));
        }
        $title = [
            'invoice_number',
            'demand_number',
            'sku',
            'invoice_code_left',
            'invoice_code_right',
            'invoice_coupon_rate',
            'invoiced_qty',
        ];
        return $title;
    }

    /**
     * 下载开票合同 excel
     */
    public function download_invoice_excel()
    {
        try{
        $html_file = getConfigItemByName('api_config','cg_system','webfornt','print_billing_contract_excel');
        $invoice_number = $this->input->get_post('invoice_number'); //发票清单号
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        if(empty($invoice_number)){
            throw new Exception('发票清单号不能为空');
        }else{
            //根据开票清单号取数据
                $data_list = $this->m_invoice_list->invoice_contract_detail($invoice_number);
                $return_data = array();
                if(!empty($data_list)){

                    $skus = array_unique(array_column(isset($data_list)?$data_list:[], 'sku'));
                    $product_field = 'sku,customs_code,declare_cname,declare_unit,export_cname';
                    $sku_arr = $this->product_model->get_list_by_sku($skus,$product_field);
                    foreach ($data_list as $key => $value) {
                        $order_info = $this->purchase_order_model->get_one($value['purchase_number'],false);
                        $order_items_info = $this->purchase_order_items_model->get_item($value['purchase_number'],$value['sku'],1);
                        $data_list[$key]['supplier_name'] = $order_info['supplier_name'];
                        $data_list[$key]['product_name'] = $order_items_info['product_name'];
                        $data_list[$key]['buyer_name'] = $order_info['buyer_name'];
                        $data_list[$key]['order_create_time'] = $order_info['create_time'];
                        $data_list[$key]['total_price'] = bcmul($value['invoiced_qty'],$value['unit_price'],2);
                        $data_list[$key]['export_cname'] = isset($sku_arr[$value['sku']])?$sku_arr[$value['sku']]['export_cname']:'';//开票品名
                        $data_list[$key]['customs_code'] = isset($sku_arr[$value['sku']])?$sku_arr[$value['sku']]['customs_code']:'';
                        //开票单位中含有2个或者以上单位时，生成的开票合同中的开票单位需要删除千克
                        $declare_unit = isset($value['declare_unit']) ? $value['declare_unit'] : '';
                        $new_declare_unit = $this->purchase_order_tax_model->get_new_declare_unit($declare_unit);
                        $data_list[$key]['declare_unit'] = $new_declare_unit;//开票单位

                    }
                    //获取开票资料表信息
                    $invoice_info = $this->m_invoice->get_invoice_one($invoice_number);
                    if (empty($invoice_info)){
                        $this->error_data_json('未查询到该发票清单号');
                    }
                    $information_info = $this->information->getInformationByKey($invoice_info['purchase_name']);
                    if (empty($invoice_info)){
                        $this->error_data_json('采购主体信息查询为空');
                    }
//                    pr($information_info);exit;
                    $information_info['invoice_number'] = $invoice_number;
                    $information_info['create_time'] = $invoice_info['create_time']??'';
                    //根据采购员ID取手机号
                    $user_info = $this->purchase_user_model->get_user_info_by_user_id($invoice_info['purchase_user_id']);
                    $information_info['iphone'] = !empty($user_info)?$user_info['phone_number']:'';
                    $information_info['buyer_name'] = $invoice_info['purchase_user_name']??'';

                    // 将开票地址，改为公司地址
                    $information_info['address'] = $information_info['company_address']??'';

                    unset($information_info['id']);
                    $return_data['invoice_list'] = $data_list;
                    $return_data['information_info'] = $information_info;
                    //根据供应商CODE取数据
                    $supplierinfo = $this->supplier_model->get_supplier_info($invoice_info['supplier_code']??'');
                    if(!empty($supplierinfo)){
                        $return_data['supplier_info'] = $supplierinfo;
                    }else{
                        throw new Exception('找不到供应商信息');
                    }
                    $key = "billing_compact_excel";//缓存键
                    $data=json_encode($return_data);
                    $this->rediss->setData($key, $data);
                    $html = file_get_contents($html_file);
                    $this->success_json(['html' => $html,'key' => $key]);
                }else{
                    throw new Exception('暂无相关数据');
                }
            }
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 开票清单列表-导出(po+sku维度) 便于上传开票信息
     * /purchase/purchase_invoice_list/export_list
     * @author Manson
     */
    public function export_list()
    {
        try{
            ini_set('memory_limit','1024M');
            set_time_limit(0);
            $this->load->helper('export_csv');
            $this->load->helper('status_product');
            $this->load->helper('status_order');
            $params['ids'] = $this->input->get_post('ids');//勾选
            if (empty($params['ids'])){
                $params = $this->get_select_params();//筛选项
            }

            $result = $this->m_invoice_list->export_list($params);
            $total = $result['total_count']??0;
            $quick_sql = $result['quick_sql']??'';

            $file_name = sprintf('发票清单列表_%s.csv',time());//文件名称
            $product_file = get_export_path().$file_name;//文件下载路径
            if (file_exists($product_file)) {
                unlink($product_file);
            }
            fopen($product_file,'w');
            $fp = fopen($product_file, "a");
            $this->load->classes('purchase/classes/InvoiceListingTemplate');
            $pick_cols = $this->InvoiceListingTemplate->get_default_template_cols();
            if(!isset($pick_cols['对接门户系统'])){

                $pick_cols['对接门户系统'] = [

                    'col' => 'is_gateway',
                    'width' => 18
                ];
            }
            foreach( $pick_cols as $key => $val) {

                $title[$val['col']] =iconv("UTF-8", "GBK//IGNORE",$key);
            }

            $pick_cols = array_column($pick_cols,'col');


            //将标题写到标准输出中
            fputcsv($fp, $title);
            if($total>=1) {
                $limit      = 1000;
                $total_page = ceil($total / $limit);
                $time_cols  = ['audit_time', 'submit_time','order_create_time','customs_time'];
                $too_long_cols = ['customs_code'];
                $special_cols = ['product_name'];
                for ($i = 1; $i <= $total_page; ++$i) {

                    $offset    = ($i - 1) * $limit;
                    $sql = sprintf('%s LIMIT %s, %s', $quick_sql, $offset, $limit);
                    $result    = $this->m_invoice_list->query_quick_sql($sql);

                    $this->format_list_data($result);

                    foreach ($result as $row) {
                        $new = [];
                        foreach ($pick_cols as $col) {
                            if(in_array($col, $special_cols)){
                                $row[$col] = str_replace(array("\r\n", "\r", "\n"), '', $row[$col]);//将换行
                                $row[$col] = str_replace(',',"，",$row[$col]);//将英文逗号转成中文逗号
                                $row[$col] = str_replace('"',"”",$row[$col]);//将英文引号转成中文引号
                            }
                            if (in_array($col, $time_cols)) {
                                $new[$col] = empty($row[$col]) || $row[$col] == '0000-00-00 00:00:00' ? '' : $row[$col] . "\t";

                            } elseif ($col == 'customs_number' && isset($row['customs_number'])) {
                                $new[$col] = implode(' ', $row['customs_number']). "\t";

                            } elseif ($col == 'invoice_number' && isset($row['invoice_number_list'])){
                                $new['invoice_number'] = implode(' ', $row['invoice_number_list']). "\t";

                            } elseif (in_array($col,$too_long_cols)){
                                $new[$col] = $row[$col]. "\t";

                            }  elseif($col=='is_gateway' && isset($row['is_gateway'])){
                                $new['is_gateway'] = $row['is_gateway']==1?'是' :'否' ;

                            }else{
                                $new[$col] = $row[$col];
                            }

                            if (!empty($new[$col])) {
                                $new[$col] = iconv("UTF-8", "GBK//IGNORE", $new[$col]);
                            } else {
                                $new[$col] = '';
                            }
                        }

                        fputcsv($fp, $new);
                    }
                    //刷新缓冲区
                    ob_flush();
                    flush();
                }
            }

            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$file_name;
            $this->success_json($down_file_url);
        }catch (Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 列表字段转换
     * @author Manson
     * @param $data_list
     */
    public function format_list_data(&$data_list)
    {
        $this->load->model('product_model','m_product',false,'product');
        $this->load->model('declare_customs_model','m_declare_customs',false,'purchase');
        //开票信息
        $invoice_number_list = array_unique(array_column($data_list,'invoice_number'));
        $field = 'a.id as invoice_detail_id, a.invoice_number, a.purchase_number, a.sku, a.invoice_code_left, a.invoice_code_right, 
         a.invoice_coupon_rate, a.invoiced_qty, a.invoice_value, a.taxes, a.audit_user, a.audit_time, a.audit_status, a.remark';
        $invoice_detail = $this->m_invoice_list->get_invoice_detail($invoice_number_list,$field);
        foreach ($invoice_detail as $key => $item){
            $_tag = sprintf('%s%s%s',$item['invoice_number'],$item['purchase_number'],$item['sku']);
            $invoice_map[$_tag][] = $item;
        }
        unset($invoice_number_list);
        unset($invoice_detail);
        //SKU集合 出口海关编码,
        $skus = array_unique(array_column($data_list, 'sku'));
        $product_field = 'sku,customs_code,declare_cname,declare_unit,export_cname,product_name';
        $sku_map = $this->m_product->get_list_by_sku($skus,$product_field);
        //报关信息
        $purchase_number_list = array_unique(array_column($data_list,'purchase_number'));
        $customs_clearance_map = $this->m_declare_customs->get_customs_clearance_details($purchase_number_list);

        foreach ($data_list as $key => &$item){
            $_tag = sprintf('%s%s%s',$item['invoice_number'],$item['purchase_number'],$item['sku']);
            $ps_tag = sprintf('%s_%s',$item['purchase_number'],$item['sku']);
            $item['invoice_info'] = $invoice_map[$_tag]??[];//开票信息


            $item['customs_code'] = $sku_map[$item['sku']]['customs_code']??'';//出口海关编码
            $item['new_export_cname'] = $sku_map[$item['sku']]['export_cname']??'';//开票品名
            $item['declare_unit'] = $sku_map[$item['sku']]['declare_unit']??'';//开票单位

            //报关信息
            $item['customs_number'] = $customs_clearance_map[$ps_tag]['customs_number']??[];
            $item['customs_name'] = $customs_clearance_map[$ps_tag]['customs_name']??'';//
            $item['customs_unit'] = $customs_clearance_map[$ps_tag]['customs_unit']??'';//报关单位
            $item['customs_quantity'] = $customs_clearance_map[$ps_tag]['customs_quantity']??0;//sum_报关数量
            $item['no_customs_quantity'] = $item['upselft_amount'] - $item['customs_quantity'];//未报关数量
            $item['customs_type'] = $customs_clearance_map[$ps_tag]['customs_type']??'';//报关型号
            $item['customs_time'] = $customs_clearance_map[$ps_tag]['customs_time']??'';//报关时间
            $item['total_amount'] = bcmul($item['unit_price']??0,$item['app_invoice_qty']??0,2);//总金额 含税单价*可开票数量
            $item['taxes'] = bcmul($item['total_amount']??0,$item['invoice_coupon_rate']??0,2);//税金 总金额*票面税率
            foreach ($item['invoice_info'] as &$invoice_info){
                $invoice_info['audit_status'] = invoice_number_status($invoice_info['audit_status']);
            }
            if (isset($item['audit_status'])){//导出
                $item['audit_status'] = invoice_number_status($item['audit_status']);
            }
        }
    }

    /**
     * 发票清单列表提交操作
     * /purchase/purchase_invoice_list/batch_submit
     * @author Manson 2020-3-27
     */
    public function batch_submit(){
        try{
            $ids = $this->input->get_post('ids'); //逗号隔开

            if(empty($ids)){
               throw new Exception('发票清单号不能为空！');
            }else{
                $ids = explode(',',$ids);
                if (count($ids) > 300){
                    throw new Exception('数据异常,超过300条');
                }
                $invoice_info = $this->m_invoice_list->get_audit_status_by_ids($ids);

                foreach ($ids  as $id){
                    if (!isset($invoice_info[$id])){
                        throw new Exception('未查询到相关数据,请重试');
                    }
                    $audit_status = $invoice_info[$id]['audit_status']??'';
                    if ($audit_status == INVOICE_STATES_WAITING_CONFIRM){
                        $update_data[] = [
                            'id' => $id,
                            'audit_status' => INVOICE_STATES_WAITING_MAKE_INVOICE,
                            'submit_user' =>   getActiveUserName(),
                            'submit_time' =>   date('Y-m-d H:i:s'),
                        ];
                    }else{
                        throw new Exception(sprintf('发票清单号%s不是待提交状态！',$invoice_info[$id]['invoice_number']??''));
                    }
                }

                if (!empty($update_data)){
                    $db = $this->m_invoice_list->getDatabase();
                    $db->trans_start();
                    $db->update_batch($this->m_invoice_list->table_name(),$update_data,'id');
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === false) {
                        throw new Exception("更新失败");
                    }
                }

                // 判断发票清单是否推送到门户系统
                $gateWaysDatas = $this->m_invoice_list->getInvoiceGateWays($ids);
                //print_r($gateWaysDatas);die();
                if(!empty($gateWaysDatas)){
                    $ids = array_column( $gateWaysDatas,"id");
                    $this->m_invoice_list->pushGateWays($ids);
                    $this->m_invoice_list->pushIsGateWays($ids);
                }



                $this->data['status'] = 1;
                $code = 200;

            }
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

}
