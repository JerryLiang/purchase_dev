<?php

/**
 * Created by PhpStorm.
 * 含税订单控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_order_tax_api extends MY_Controller {


    protected $table_name = 'purchase_invoice_list';// 数据表名称
    protected $declare_customs_table = 'declare_customs';
    protected $table_invoice_detail = 'purchase_items_invoice_info';
    protected $table_invoice_item = 'purchase_invoice_item';
    protected $table_purchase_order = 'purchase_order';
    protected $table_purchase_order_items = 'purchase_order_items';
    protected $table_product = 'product';
    public function __construct() {
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('purchase/purchase_order_tax_model');
        $this->load->model('purchase/declare_customs_model');
        $this->load->model('purchase/purchase_invoice_model');
        $this->load->model('prefix_number_model'); // 数据表前缀
        $this->load->helper('status_order');
        $this->load->model('purchase/purchase_invoice_list_model','m_invoice_list',false,'purchase');
        $this->load->model('system/invoice_information_model','information',false,'system');
        $this->load->model('product/product_model','product_model',false,'product');
        $this->load->model('supplier/supplier_model','supplier_model',false,'supplier');
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_order_items_model');
        $this->load->model('purchase/purchase_user_model','purchase_user_model',false,'user');
        $this->load->model('warehouse/warehouse_results_model','warehouse_results',false,'warehouse');
        $this->load->model('purchase/purchase_invoice_model','m_invoice',false,'purchase');
        $this->load->model('compact/Print_pdf_model');
        $this->load->library('Upload_image');
        
        
    }

    /**
     * 生成发票清单操作(海外仓)
     * /Purchase_order_tax_api/generate_overseas_invoice_list
     * @author Jaden 2019-1-10
     */
    public function generate_overseas_invoice_list(){
        $params = [
                'purchase_type_id' => PURCHASE_TYPE_OVERSEA // 业务线(1国内2海外3FBA)
               
        ]; 
        $invoice_data = array();
        $group='ppoi.sku,ppoi.purchase_number';
        $product_incomplete_list_arr = $this->purchase_order_tax_model->add_invoice($params,$group);
        $tax_data_list = $product_incomplete_list_arr['data_list'];
        $invoice_arr = array();
        $fail_list = '';
        $success_list = '';
        $oversea_list = '';

        //待优化
        foreach ($tax_data_list as $key=>$value)
        {   
            if($value['is_end']==IS_END_TRUE){
               continue; 
           }
            //根据采购单号查找报关数据
            $invoice_where = 'purchase_number="'.$value['purchase_number'].'" AND sku="'.$value['sku'].'" AND is_invoice=0 AND is_clear=2';
            $declare_info = $this->declare_customs_model->getInvoiceByWhere($invoice_where);
            //找不到报关信息
            if(empty($declare_info)){
                $fail_list.=$value['purchase_number'].'【SKU:'.$value['sku'].'】'.',';
                continue;
            }
            //未报关
            if($value['customs_status']==UNCUSTOMED){
                $fail_list.=$value['purchase_number'].'【SKU:'.$value['sku'].'】'.',';
                continue;
            }
            //海外仓订单需要满足“SKU+PO”的入库数量=报关数量才能生成发票清单
            if(in_array($value['purchase_type_id'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])){
                //查报关数量
                $de_info = $this->declare_customs_model->getInvoiceByWherelist(array($value['purchase_number']));
                $customs_quantity_num = $de_info[$value['purchase_number'].'_'.$value['sku']];
                if($customs_quantity_num!=$value['upselft_amount']){
                    $oversea_list.=$value['purchase_number'].'【SKU:'.$value['sku'].'】'.',';
                    continue;
                }
            }
            $order_key = $value['purchase_type_id'].'-'.$value['purchase_name'].'-'.$value['supplier_code'];
            $invoice_arr[$order_key][] = $value;
        }
        if(!empty($fail_list)){
            $fail_list.='找不到报关数据,无法生成发票清单';
        }
        if(!empty($oversea_list)){
            $fail_list.='海外仓需入库数量等于报关数量，才能生成发票清单';
        }
        $purchase_number = array();
        foreach ($invoice_arr as $k => $val) {
            $total_price = 0.000;
            $purchase_name = '';
            $supplier_code = '';
            $supplier_name = '';
            $buyer_name    = '';
            $buyer_id    = '';
            foreach ($val as $kp => $vp) {
                $success_list.=$vp['purchase_number'].'【SKU:'.$vp['sku'].'】'.',';
                $total_price        += format_two_point_price($vp['purchase_amount']*$vp['purchase_unit_price']);
                $supplier_name      = $vp['supplier_name'];
                $purchase_number[]  = $vp['purchase_number'];
                $purchase_name      = $vp['purchase_name'];
                $supplier_code      = $vp['supplier_code'];
                $buyer_name         = $vp['buyer_name'];
                $buyer_id           = $vp['buyer_id'];
                //把需求单号查入报关详情数据表
                if(!empty($vp['demand_number']) && isset($vp['demand_number'])){
                    $where_data['purchase_number'] = $vp['purchase_number'];
                    $where_data['sku'] = $vp['sku'];
                    $this->declare_customs_model->update_invoice_code($where_data,array('demand_number'=>$vp['demand_number']));
                }
            }
            //生成一个新的发票清单号
            $new_fp_number = $this->prefix_number_model->get_prefix_new_number('FP',1,6);
            //发票清单数据
            $invoice_data['purchase_name'] = $purchase_name;
            $invoice_data['invoice_number'] = $new_fp_number;
            $invoice_data['supplier_code'] = $supplier_code;
            $invoice_data['supplier_name'] = $supplier_name ;
            $invoice_data['invoice_amount'] = $total_price;
            $invoice_data['create_user'] = getActiveUserName();
            $invoice_data['create_time'] = date('Y-m-d H:i:s');
            $invoice_data['states'] = 1;
            $invoice_data['purchase_user_name'] = $buyer_name;
            $invoice_data['purchase_user_id'] = $buyer_id;
            //生成发票清单
            $invoice_result = $this->purchase_invoice_model->save_purchase_invoice($invoice_data);
            //根据采购单号更新报关详情表的发票清单号
            if($invoice_result){
                $this->declare_customs_model->save_invoice_number($purchase_number,$new_fp_number);    
            }
        }
        if(!empty($success_list)){
            $success_list.='发票清单生成成功';
        }
        if(empty($success_list)){
            $this->error_json($fail_list); 
        }elseif(!empty($fail_list) || !empty($success_list)){
            $this->success_json($success_list.'<br>'.$fail_list);
        }else{
            $this->error_json('数据有误');
        }        
    }




    /**
     * 生成发票清单操作(FBA)
     * /Purchase_order_tax_api/generate_fba_invoice_list
     * @author Jaden 2019-1-10
     */
    public function generate_fba_invoice_list(){
        $params = [
                'purchase_type_id' => PURCHASE_TYPE_FBA // 业务线(1国内2海外3FBA)
               
        ]; 
        $invoice_data = array();
        $group='ppoi.sku,ppoi.purchase_number';
        $product_incomplete_list_arr = $this->purchase_order_tax_model->add_invoice($params,$group);
        $tax_data_list = $product_incomplete_list_arr['data_list'];
        $invoice_arr = array();
        $fail_list = '';
        $success_list = '';
        //待优化
        foreach ($tax_data_list as $key=>$value)
        {   
            if($value['is_end']==IS_END_TRUE){
               continue; 
           }
            //根据采购单号查找报关数据
            $invoice_where = 'purchase_number="'.$value['purchase_number'].'" AND sku="'.$value['sku'].'" AND is_invoice=0 AND is_clear=2';
            $declare_info = $this->declare_customs_model->getInvoiceByWhere($invoice_where);
            //找不到报关信息
            if(empty($declare_info)){
                $fail_list.=$value['purchase_number'].'【SKU:'.$value['sku'].'】'.',';
                continue;
            }
            //未报关
            if($value['customs_status']==UNCUSTOMED){
                $fail_list.=$value['purchase_number'].'【SKU:'.$value['sku'].'】'.',';
                continue;
            }

            $order_key = $value['purchase_type_id'].'-'.$value['purchase_name'].'-'.$value['supplier_code'];
            $invoice_arr[$order_key][] = $value;
        }
        if(!empty($fail_list)){
            $fail_list.='找不到报关数据,无法生成发票清单';
        }

        $purchase_number = array();
        foreach ($invoice_arr as $k => $val) {
            $total_price = 0.000;
            $purchase_name = '';
            $supplier_code = '';
            $supplier_name = '';
            $buyer_name    = '';
            $buyer_id    = '';
            foreach ($val as $kp => $vp) {
                $success_list.=$vp['purchase_number'].'【SKU:'.$vp['sku'].'】'.',';
                $total_price        += format_two_point_price($vp['purchase_amount']*$vp['purchase_unit_price']);
                $supplier_name      = $vp['supplier_name'];
                $purchase_number[]  = $vp['purchase_number'];
                $purchase_name      = $vp['purchase_name'];
                $supplier_code      = $vp['supplier_code'];
                $buyer_name         = $vp['buyer_name'];
                $buyer_id           = $vp['buyer_id'];
                //把需求单号查入报关详情数据表
                if(!empty($vp['demand_number']) && isset($vp['demand_number'])){
                    $where_data['purchase_number'] = $vp['purchase_number'];
                    $where_data['sku'] = $vp['sku'];
                    $this->declare_customs_model->update_invoice_code($where_data,array('demand_number'=>$vp['demand_number']));
                }
            }
            //生成一个新的发票清单号
            $new_fp_number = $this->prefix_number_model->get_prefix_new_number('FP',1,6);
            //发票清单数据
            $invoice_data['purchase_name'] = $purchase_name;
            $invoice_data['invoice_number'] = $new_fp_number;
            $invoice_data['supplier_code'] = $supplier_code;
            $invoice_data['supplier_name'] = $supplier_name ;
            $invoice_data['invoice_amount'] = $total_price;
            $invoice_data['create_user'] = getActiveUserName();
            $invoice_data['create_time'] = date('Y-m-d H:i:s');
            $invoice_data['states'] = 1;
            $invoice_data['purchase_user_name'] = $buyer_name;
            $invoice_data['purchase_user_id'] = $buyer_id;
            //生成发票清单
            $invoice_result = $this->purchase_invoice_model->save_purchase_invoice($invoice_data);
            //根据采购单号更新报关详情表的发票清单号
            if($invoice_result){
                $this->declare_customs_model->save_invoice_number($purchase_number,$new_fp_number);    
            }
        }
        if(!empty($success_list)){
            $success_list.='发票清单生成成功';
        }
        if(empty($success_list)){
            $this->error_json($fail_list); 
        }elseif(!empty($fail_list) || !empty($success_list)){
            $this->success_json($success_list.'<br>'.$fail_list);
        }else{
            $this->error_json('数据有误');
        }        
    }

    /**
     * 接受门户系统推送的审核数据

     **/
    public function acceptGateWaysData(){

        try{
            $gatewaysData = json_decode(file_get_contents('php://input'), true);
            $insertData =[
                'pushdata' => json_encode($gatewaysData, JSON_UNESCAPED_UNICODE),
                'type' => 'pull'
            ];
            $this->db->insert('invoice_data_log',$insertData);

            if(empty($gatewaysData)){

                throw new Exception("请传入数据");
            }
            $success = $error = [];
            $keydata =0;

            $skuDatas = array_column($gatewaysData,'sku');
            $couponRateDatas = [];
            $couponRates = $this->db->from("product")->where_in("sku",$skuDatas)->select("sku,coupon_rate")->get()->result_array();
            if(!empty($couponRates)){

                $couponRateDatas = array_column( $couponRates,NULL,'sku');
            }
            foreach($gatewaysData as $key=>$value){
                ++$keydata;
//                $where['invoice_number'] = $value['invoice_number']; //发票清单号
//                $where['sku'] = $value['sku']; // SKU
//                $where['purchase_number'] = $value['purchase_number']; // 采购单号
                $demandNumbers = $this->db->from("purchase_invoice_item")->where("invoice_number",$value['invoice_number'])
                    ->where("sku",$value['sku'])->where("purchase_number",$value['purchase_number'])->select("id,demand_number")
                    ->get()->row_array();
                $update = [

                    //'taxes' => $value['taxes'], // 税金 发票金额*票面税率
                    //'taxes' => bcmul($value['invoice_value'],$value['invoice_coupon_rate'],2),
                    'invoiced_qty' =>$value['invoiced_qty'], // 开票数量
                    //'invoice_coupon_rate' => $value['invoice_coupon_rate'], // 票面税率
                    'invoice_coupon_rate' => isset($couponRateDatas[$value['sku']])?$couponRateDatas[$value['sku']]['coupon_rate']:0,
                    //'invoice_value' => $value['invoice_value'], // 开票金额 invoice_value
                    'invoice_code_left' => $value['invoice_code_left'], // 发票代码（左）
                    'invoice_code_right' => $value['invoice_code_right'], // 发票号码（右）
                    'invoice_image' => $value['invoice_image'],
                    //'invoiced_amount' => $value['invoiced_amount'], //开票金额
                    'audit_status' => 6, // 待采购审核状态
                    'sku' => $value['sku'],
                    'purchase_number' => $value['purchase_number'],
                    'invoice_number' => $value['invoice_number'],

                    'demand_number' => isset($demandNumbers['demand_number'])?$demandNumbers['demand_number']:'',
                    'children_invoice_number' => $value['invoiceNumberSub']

                ];


                /**
                //发票金额 = 开票数量 * 含税单价/(1+票面税率)
                $value['invoice_value'] = round(($value['invoiced_qty']*$value['unit_price'])/(1+$value['invoice_coupon_rate']),2);
                //开票金额 = 开票数量 * 含税单价
                $value['invoiced_amount'] = $value['invoiced_qty']*$value['unit_price'];
                //税金 = 开票金额 - 发票金额
                $value['taxes'] = round($value['invoiced_amount'] - $value['invoice_value'],2);
                 **/

                $purchaseUnitPrice = $this->db->from("purchase_order_items")->where("purchase_number",$update['purchase_number'])
                    ->where("sku",$update['sku'])->select("purchase_unit_price")->get()->row_array();

                $unitprice = (isset($purchaseUnitPrice['purchase_unit_price']) && !empty($purchaseUnitPrice['purchase_unit_price']))?$purchaseUnitPrice['purchase_unit_price']:0;

                $invoicevalue = bcmul($update['invoiced_qty'],$unitprice,3);
                $tax_1 = bcadd(1,$update['invoice_coupon_rate'],3);
                $update['invoiced_amount'] = $value['invoiced_qty']*$purchaseUnitPrice['purchase_unit_price'];
                $update['invoice_value'] = bcdiv($invoicevalue,$tax_1,2);

                /*
                 * 税金公式
                  税金 = 开票金额 - 发票金额
                 * */

                $update['taxes'] = round($update['invoiced_amount'] -$update['invoice_value'],2);
                $where['children_invoice_number'] = $value['invoiceNumberSub'];
                $result = $this->db->from("purchase_items_invoice_info")->where($where)->select("id")->order_by("id DESC")->get()->row_array();
                if(empty($result)){

                    $result = $this->db->insert_batch("purchase_items_invoice_info",[$update]);
                }else{

                    $result = $this->db->where($where)->update("purchase_items_invoice_info",$update);
                }
                $listWhere['invoice_number'] = $value['invoice_number'];
                $this->db->where($listWhere)->update("purchase_invoice_list",['audit_status'=>3]);

                if($result){
                    $success[] =  $value['invoice_number'];
                }else{
                    $error[] =  $value['invoice_number'];
                }
                $insertData =[
                    'pushdata' => json_encode($success),
                    'returndata' => json_encode($error),
                    'type' => 'pull'
                ];
                $this->db->insert('invoice_data_log',$insertData);
            }
            $this->success_json(['success'=>$success,'error'=>$error]);
        }catch ( Exception $exp ){
            $insertData =[
                'pushdata' => json_encode($gatewaysData, JSON_UNESCAPED_UNICODE),
                'returndata' => $exp->getMessage(),
                'type' => 'pull'
            ];
            $this->db->insert('invoice_data_log',$insertData);
            $this->error_json($exp->getMessage());

        }
    }

    public function download_view($invoice_number){
        $print_billingcontract = getConfigItemByName('api_config','cg_system','webfornt','print_billingcontract');
        if(empty($invoice_number)){
            throw new Exception('发票清单号不能为空！');
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
                        throw new Exception('未查询到该发票清单号');
                    }
                    $information_info = $this->information->getInformationByKey($invoice_info['purchase_name']);
                    if (empty($invoice_info)){
                       throw new Exception('采购主体信息查询为空');
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
                        throw new Exception('找不到供应商信息');
                    }
                    $key = "billing_compact";//缓存键
                    $data=json_encode($return_data);
                    $this->rediss->setData($key, $data);
                    $html = file_get_contents($print_billingcontract);
                    return $html;
                }else{
                    return NULL;
                }
            }catch(Exception $e){
               throw new Exception($e->getMessage());
            }
        }
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
        $java_result = $this->upload_image->doUploadFastDfs('file', $filepath);
        if ($java_result['code'] == 200) {
           $return['code'] = $java_result['code'];
           $return['filepath'] = $java_result['data'];
        } else {
            $return['code'] = 500;
            $return['msg'] = '文件上传数据库失败';
        }
        return $return;
    }


    /**
     * 采购系统推送发票合同下载链接
     * @接口文档:http://dp.yibai-it.com:33344/web/#/118?page_id=12703
     * @author:luxu
     * @time:2020/6/17
     **/

    public function pushCompactUrl(){

        try{

           $query = $this->db->from($this->table_name)->where("is_push_gateway",1);
           $totalQuery =$query;
           $total = $totalQuery->count_all_results();
           $limit = 5;
           $pages = ceil($total/$limit);
            $pushData = $errors = [];
           for($i=0;$i<=$pages;++$i){

               $result = $this->db->from($this->table_name)->where("is_push_gateway",1)->where("is_push_gateway_compact",0)
                   ->select("id,invoice_number")->limit($limit,$i*$limit)->get()->result_array();
               if(!empty($result)){

                   foreach($result as $key=>$value){

                       $htmlsData = $this->download_view($value['invoice_number']);
                       if(!empty($htmlsData)) {
                           $fileFile = $this->Print_pdf_model->new_print_pdf($htmlsData);
                           if(file_exists($fileFile)) {
                               $uplodata = $this->upload_file($fileFile);
                               if ($uplodata['code'] == 200) {

                               // $url = "http://rest.dev.java.yibainetworklocal.com/provider/yibaiSupplierInvoiceList/pushCompactUrl?access_token=".getOASystemAccessToken();
                               $pushData[] = [

                                   'invoiceNumber' => $value['invoice_number'],
                                   'compactUrl' => $uplodata['filepath'],
                                   'id' => $value['id']
                               ];
                           } else {

                                   $errors[] = $uplodata;
                               }
                           }
                       }
                   }
               }
           }

           if(!empty($pushData)){

               $header        = array('Content-Type: application/json');
               $access_taken  = getOASystemAccessToken();
               $url           = getConfigItemByName('api_config','charge_against','pushCompactUrl');
               $url           = $url."?access_token=".$access_taken;
               $idsPushData = $pushData;
               foreach($pushData as $key=>&$value){

                   unset($value['id']);
               }
               $result        = getCurlData($url,json_encode(['item'=>$pushData],JSON_UNESCAPED_UNICODE),'post',$header);

               $insertData =[
                   'pushdata' => json_encode($pushData, JSON_UNESCAPED_UNICODE),
                   'returndata' => $result,
                   'type' => 'pushGateWaysUrl'
               ];
               $this->db->insert('invoice_data_log',$insertData);

               $gateWaysData = json_decode($result,True);
               if($gateWaysData['code'] == 200){

                   $ids = array_column($idsPushData,"id");
                   $this->db->where_in("id",$ids)->update($this->table_name,['is_push_gateway_compact'=>1]);
               }

           }


           if(!empty($errors)){

               $insertData =[
                   'pushdata' => json_encode($errors, JSON_UNESCAPED_UNICODE),

                   'type' => 'pushGateWaysUrlEerror'
               ];
               $this->db->insert('invoice_data_log',$insertData);
           }

        }catch ( Exception $exp ){

            $insertData =[
                'pushdata' => $exp->getMessage(),

                'type' => 'pushGateWaysUrlEerror'
            ];
            $this->db->insert('invoice_data_log',$insertData);
        }
    }


}
