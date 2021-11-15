<?php
/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Product extends MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('product_model');
        $this->load->model('purchase_user_model','product_user',false,'user');
        $this->load->model('product_line_model','product_line',false,'product');
        $this->load->model('supplier_buyer_model','supplier_buyer_model',false,'supplier');
        $this->load->model('product_update_log_model','product_update_log',false,'product');
        $this->load->model('Purchase_user_model','Purchase_user_model',false,'user');
        $this->load->model('product_mod_audit_model','product_mod_audit',false,'product_mod_audit');
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $this->load->model('user/User_group_model', 'User_group_model');
        $this->load->model('Supplier_model','Supplier_model',false,'supplier');
        $this->load->model('Supplier_settlement_model', 'settlementModel',false,'supplier');
        $this->load->helper('common');


        $this->_ci = get_instance();
        //获取redis配置
        $this->_ci->load->config('mongodb');
        $host = $this->_ci->config->item('mongo_host');
        $port = $this->_ci->config->item('mongo_port');
        $user = $this->_ci->config->item('mongo_user');
        $password = $this->_ci->config->item('mongo_pass');
        $author_db = $this->_ci->config->item('mongo_db');
        $this->_mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $this->_author_db = $author_db;
    }

    /**
     * 产品相关状态 下拉列表
     * @author Jolon
     */
    public function get_status_list(){
        $status_type  = $this->input->get_post('type');
        $this->load->helper('status_order');

        switch(strtolower($status_type)){
            default :
                $status_type_name = '未知的状态类型';
                $data_list        = null;
        }

        if($data_list){
            $this->success_json($data_list);
        }else{
            $this->error_json($status_type_name);
        }
    }
    /**
     * 获取产品列表修改原因
     **/
    public function get_reason_list()
    {
        try{

            $field_name = $this->input->get_post('field');
            $is_product =  $this->input->get_post('is_product');
            $reason_list = $this->product_model->get_reason_list($field_name,$is_product);
            $this->success_json($reason_list);
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 产品列表
    /product/product/product_list
     * @author Jaden
     */
    public function product_list(){
        $this->load->helper('status_product');
        $this->load->helper('status_supplier');
        $this->load->helper('status_order');

        $params = [
            'sku' => $this->input->get_post('sku'), // SKU
            'product_line_id' =>$this->input->get_post('product_line_id'),
            'supplier_code' => $this->input->get_post('supplier_code'),
            'product_status' => $this->input->get_post('product_status'),
            'supply_status' => $this->input->get_post('supply_status'),//货源状态(1.正常,2.停产,3.断货,4.停货)
            'audit_status' => $this->input->get_post('audit_status'),//审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]
            'is_cross_border' =>$this->input->get_post('is_cross_border'),//是否是跨境宝(默认0.否,1.跨境宝)
            'is_relate_ali' =>$this->input->get_post('is_relate_ali'),//是否是跨境宝(默认0.否,1.跨境宝)
            'buyer_code' => $this->input->get_post('buyer_code'),
            'is_inspection' => $this->input->get_post('is_inspection'),
            'supplier_source' => $this->input->get_post('supplier_source'),
            'is_abnormal' => $this->input->get_post('is_abnormal'),//是否异常 1[否] 2[是]
            'status' => $this->input->get_post('status'),//是否禁用 1[否] 2[是]
            'seachuid' =>$this->input->get_post('uid'),
            'is_invaild' => $this->input->get_post('is_invaild'),
            'starting_qty_start' => $this->input->get_post('starting_qty_start'),
            'starting_qty_end' => $this->input->get_post('starting_qty_end'),
            'product_name'     => $this->input->get_post('product_name'),
            'is_purchaseing'   => $this->input->get_post('is_purchaseing'), // 是否需要待采购
            'is_audit_type'   => $this->input->get_post('is_audit_type'), // 审核类型
            'develop_code'    => $this->input->get_post('developer_code'),
            'equal_sup_id'    => $this->input->get_post('equal_sup_id'),
            'equal_sup_name'    => $this->input->get_post('equal_sup_name'),
            'is_new'          => $this->input->get_post('is_new'), // 是否新品
            'is_overseas_first_order' => $this->input->get_post('is_overseas_first_order'), // 是否海外仓首单
            'transformation' => $this->input->get_post('transformation'), // 是否为国内传海外仓
            'is_customized'  => $this->input->get_post('is_customized'), // 是否定制
            'overseas_ids'   => $this->input->get_post('overseas_ids'),
            'domestic_ids'   => $this->input->get_post('domestic_ids'),
            'settlement_method' => $this->input->get_post('settlement_method'),
            'price_start'    => $this->input->get_post('price_start'),
            'price_end'      => $this->input->get_post('price_end'),
            'is_overseas'    => $this->input->get_post('is_overseas'),
            'is_shipping'    => $this->input->get_post('is_shipping'),
            'create_time_start' =>$this->input->get_post('create_time_start'),
            'create_time_end' => $this->input->get_post('create_time_end')
        ];

        if( isset($params['overseas_ids']) && !empty($params['overseas_ids'])){

            $params['overseas_supplier_code'] = ['xx2'];
            $groupids = $this->supplier_buyer_model->getUserData($params['overseas_ids'],2);
            if(!empty($groupids)){

                $params['overseas_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        if( isset($params['domestic_ids']) && !empty($params['domestic_ids'])){

            $params['domestic_supplier_code'] =['xx2'];
            $groupids = $this->supplier_buyer_model->getUserData($params['domestic_ids'],1);
            if(!empty($groupids)){

                $params['domestic_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        $is_productismulti = $this->input->get_post('is_productismulti');
        if( !empty($is_productismulti) ) {

            $params['is_productismulti'] = $is_productismulti;
        }
        $new =$this->input->get_post('new');
        if( !empty($new) ) {
            $params['new'] = $new;
        }
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        //p.is_shipping,p.is_consign
        $field = 'p.is_shipping,p.long_delivery,
p.is_customized,p.original_devliy,p.devliy,p.sku_state_type,
        p.sample_package_length,p.sample_package_width,p.sample_package_heigth,
        p.box_size,p.outer_box_volume,p.product_volume,p.inside_number,
        p.is_overseas_first_order,p.is_new,p.audit_status_log,p.is_purchasing,p.maintain_ticketed_point,
        p.is_multi AS productismulti,p.product_type AS producttype,'
            .'p.is_invalid,p.is_inspection,p.id,p.sku,p.supplier_code,p.product_img_url,p.product_thumb_url,
            p.product_name,p.coupon_rate,p.declare_cname,p.tax_rate,export_cname,'
            .'p.declare_unit,p.export_model,p.product_status,p.product_line_id,p.create_user_name,p.create_time,
            p.original_start_qty,p.original_start_qty_unit,'
            .'p.starting_qty,p.starting_qty_unit,p.is_drawback,p.is_sample,p.purchase_price,p.ticketed_point,
            p.supplier_name,p.product_cn_link,'
            .'p.supply_status,p.note,p.is_relate_ali,p.product_cost,p.is_abnormal,p.ali_ratio_own,
            p.ali_ratio_out,p.is_equal_sup_id,p.is_equal_sup_name';
        $orders_info = $this->product_model->get_product_list($params, $offset, $limit,$field);
        if(isset($orders_info['code']) and $orders_info['code'] == false){
            $this->error_json($orders_info['message']);
        }
        $product_list = $orders_info['value'];
        $role_name=get_user_role();//当前登录角色
        //$data_role= getRolexiao();
        //$product_list = ShieldingData($product_list,['supplier_name','supplier_code','product_name','abc'],$role_name,$data_role);
        $roleString = implode(",",$role_name);

        foreach ($product_list as $key => $value) {


            $product_attributes = $this->product_model->get_product_attribute_data($value['sku']);

            $overseas_ch = '';
            if($product_attributes['海外属性'] && !empty($product_attributes['海外属性'])){
                if(count($product_attributes['海外属性']) == 1) {
                    if (in_array('国内仓', $product_attributes['海外属性'])) {

                        $overseas_ch = "否";
                    }else{
                        $overseas_ch = "是";
                    }
                }else{

                    if (in_array('国内仓', $product_attributes['海外属性'])) {
                        $overseas_ch = "共用";
                    }else{
                        $overseas_ch = "是";
                    }
                }
            }
            if( $value['is_relate_ali'] == 0){

                $orders_info['value'][$key]['starting_qty_number'] = "-";
            }else{
                $orders_info['value'][$key]['starting_qty_number'] = $value['starting_qty'];
            }
            $orders_info['value'][$key]['overseas_ch'] = $overseas_ch;
            if($value['is_customized'] == 2){

                $orders_info['value'][$key]['is_customized_ch'] = "否";
            }else{
                $orders_info['value'][$key]['is_customized_ch'] = "是";
            }
            if( $value['is_overseas_first_order'] == 1){

                $orders_info['value'][$key]['is_overseas_first_order_ch'] = "是";
            }else{
                $orders_info['value'][$key]['is_overseas_first_order_ch'] = "否";
            }
            if( empty($value['devliy']) || $value['devliy'] == 0.00){

                $orders_info['value'][$key]['devliy'] = $value['original_devliy'];
            }

            if( $value['long_delivery'] == 1){

                $orders_info['value'][$key]['long_delivery_ch'] = '否';
            }else{
                $orders_info['value'][$key]['long_delivery_ch'] = '是';
            }


            $orders_info['value'][$key]['product_volume'] = $value['product_volume'];
            $orders_info['value'][$key]['sample_package_size'] = $value['sample_package_length'] * $value['sample_package_width'] * $value['sample_package_heigth'];
            if( empty($value['is_invalid']) || $value['is_invalid'] ==0 ) {

                $orders_info['value'][$key]['is_invalid'] = "正常";
            }else if( $value['is_invalid'] ==1 ) {

                $orders_info['value'][$key]['is_invalid'] = "失效";
            }
            if( $value['productismulti'] == 2) {

                $orders_info['value'][$key]['is_productismulti'] = 1;
            }else{
                $orders_info['value'][$key]['is_productismulti'] = 0;
            }

            if( $value['producttype'] == 2 ) {

                $orders_info['value'][$key]['is_producttype'] =1;
            }else{
                $orders_info['value'][$key]['is_producttype'] =0;
            }

            if( !empty($value['declare_unit']))
            {
                $value['declare_unit'] = deleteProductData($value['declare_unit']);
            }



            $supplier_buyer_list = $this->supplier_buyer_model->get_buyer_list($value['supplier_code']);
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
            if(!empty($supplier_buyer_list)){
                foreach ($supplier_buyer_list as $k => $val) {
                    if(PURCHASE_TYPE_INLAND == $val['buyer_type']){
                        $buyer_type_name = '国内仓';
                    }elseif(PURCHASE_TYPE_OVERSEA == $val['buyer_type']){
                        $buyer_type_name = '海外仓';
                    }elseif(PURCHASE_TYPE_FBA == $val['buyer_type']){
                        $buyer_type_name = 'FBA';
                    }else{
                        $buyer_type_name = '未知';
                    }

                    $supplier_buyer_user.= $buyer_type_name.':'.$val['buyer_name'].',';

                }
            }
            /*
            $sku_img_url = file_get_contents($value['product_img_url']);
            if(empty($sku_img_url)){
                $orders_info['value'][$key]['product_img_url'] = 'http://images.yibainetwork.com/services/api/system/index/method/getimage/sku/'.$value['sku'];
            }else{
                $orders_info['value'][$key]['product_img_url'] = $value['product_img_url'];
            }
            */
            $orders_info['value'][$key]['domatic'] = $domatic;
            $orders_info['value'][$key]['overseas'] = $overseas;
            $orders_info['value'][$key]['supplier_buyer_user'] = rtrim($supplier_buyer_user,',');
            $orders_info['value'][$key]['product_status'] = getProductStatus($value['product_status']);

            $orders_info['value'][$key]['is_drawback'] = getIsDrawback($this->product_model->getProductIsBackTaxNew($value['supplier_code'],$value['tax_rate'],$value['ticketed_point']));
            $orders_info['value'][$key]['product_line_id'] = $this->product_line->get_product_line_name($value['product_line_id']);
            $orders_info['value'][$key]['is_relate_ali'] = getRelateAli($value['is_relate_ali']);
            $orders_info['value'][$key]['is_inspection'] = ( $value['is_inspection'] ==1 )?"不商检":"商检";
            $orders_info['value'][$key]['product_img_url_thumbnails'] = $value['product_thumb_url']?erp_sku_img_sku_thumbnail($value['product_thumb_url']):erp_sku_img_sku($value['product_img_url']);
            $orders_info['value'][$key]['product_img_url'] = erp_sku_img_sku($value['product_img_url']);
            $orders_info['value'][$key]['is_equal_sup_id'] = getEqualSupId($value['is_equal_sup_id']);
            $orders_info['value'][$key]['is_equal_sup_name'] = getEqualSupName($value['is_equal_sup_name']);

            //同款标识 产品链接
            preg_match("/offer\/[\w]+\.html/",$value['product_cn_link'],$pdt_tongkuan);
            $orders_info['value'][$key]['pdt_tongkuan'] = !empty($pdt_tongkuan)?$value['product_cn_link']:'';


            if( empty($value['is_invalid']) || $value['is_invalid'] ==0 ) {

                $orders_info['value'][$key]['is_invalid'] = "正常";
            }else if( $value['is_invalid'] == 1 ) {

                $orders_info['value'][$key]['is_invalid'] = "失效";
            }
            if( $value['is_inspection'] ==0 ) {
                $orders_info['value'][$key]['is_inspection'] = "未知";

            }
            if($value['maintain_ticketed_point'] ==1  && $value['ticketed_point'] == 0.000)
            {
                $orders_info['value'][$key]['ticketed_point'] = NULL;
            }

            if( $value['is_purchasing'] && !empty($value['is_purchasing']))
            {
                $orders_info['value'][$key]['is_purchasing'] = ( $value['is_purchasing']==1)?"否":"是";
            }

            if( isset($value['is_shipping']) && $value['is_shipping'] == 1){

                $orders_info['value'][$key]['is_shipping_ch'] = '包邮';
            }

            if( isset($value['is_shipping']) && $value['is_shipping'] == 2){

                $orders_info['value'][$key]['is_shipping_ch'] = '不包邮';
            }

            if( isset($value['is_shipping']) && $value['is_shipping'] == 0){

                $orders_info['value'][$key]['is_shipping_ch'] = '-';
            }

            $paymentData = $this->Supplier_model->get_supplier_payment($value['supplier_code']);
            $orders_info['value'][$key]['settlement_method'] = $paymentData;

            if(strstr($roleString,'admin') || strstr($roleString,'采购') || strstr($roleString,'财务') ||
                strstr($roleString,'供应') || strstr($roleString,'品控')){

                $orders_info['value'][$key]['supplier_name'] = $value['supplier_name'];
                $orders_info['value'][$key]['supplier_code'] = $value['supplier_code'];
            }else{
                $orders_info['value'][$key]['supplier_name'] = "*";
                $orders_info['value'][$key]['supplier_code'] = "*";
            }

            $new_supplierAvg = $this->product_model->get_supplier_avg_day($value['supplier_code']);

            $orders_info['value'][$key]['ds_day_avg'] = isset($new_supplierAvg['ds_day_avg'])?$new_supplierAvg['ds_day_avg']:'-';
            $orders_info['value'][$key]['os_day_avg'] = isset($new_supplierAvg['os_day_avg'])?$new_supplierAvg['os_day_avg']:'-';
            $orders_info['value'][$key]['ds_deliverrate'] = isset($new_supplierAvg['ds_deliverrate'])?round(($new_supplierAvg['ds_deliverrate']*100),4)."%":'-';
            $orders_info['value'][$key]['os_deliverrate'] = isset($new_supplierAvg['os_deliverrate'])?round(($new_supplierAvg['os_deliverrate']*100),4)."%":'-';

        }

      //  $orders_info['key'] = array('ID','SKU','产品图片','产品名称','票面税率','产品信息','最小起订量','是否退税','税点','单价','开发单价','供应商名称','是否代采','开发人员/采购员','采购链接','是否1688关联','审核状态','是否包邮','操作');
        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        //getActiveUserId
        $uid = $this->input->get_post('uid');
        $searchData = $this->product_model->get_user_header($uid,'product_list',$this->header());
        $orders_info['key'] = $searchData;
        $this->success_json($orders_info);
    }

    /**
     * 下拉选择框
     */
    function get_drop_box(){
        $product_line_list = $this->product_line->get_product_line_list(0);
        $drop_down_box['product_line_id'] =array_column($product_line_list, 'linelist_cn_name','product_line_id');
        //供应商
        $user_list = $this->product_user->get_list();
        $drop_down_box['supplier_name'] = array_column($user_list, 'name','id');
        $drop_down_box['product_status'] = getProductStatus();//产品状态
        $drop_down_box['supply_status'] = getProductsupplystatus();//货源状态
        $drop_down_box['audit_status'] =getProductModStatus();//审核状态
        $drop_down_box['is_cross_border'] = getCrossBorder();//是否是跨境宝
        $drop_down_box['is_relate_ali'] = getRelateAli();//是否是跨境宝
        $drop_down_box['product_status_list'] = getProductStatus();//产品状态
        $drop_down_box['is_abnormal_list'] = getProductAbnormal();//是否异常
        $drop_down_box['down_disable'] = getSupplierDisable();//是否禁用
        $drop_down_box['developer_code'] = $this->purchase_user_model->get_all_user_by_dept_id(1079231,'development_person');
        $drop_down_box['buyer_message'] = $this->Purchase_user_model->get_list(); // 采购员
        $drop_down_box['supplier_source'] = [['source'=>1,'message'=>'常规'],['source'=>'2','message'=>'海外'],['source'=>3,'message'=>'临时']];
        $drop_down_box['is_invaild'] =  [['status'=>1,'message'=>'是'],['status'=>2,'message'=>'否']];
        $drop_down_box['is_inspection'] = [['is_inspection'=>1,'message'=>'不商检'],['is_inspection'=>2,'message'=>'商检']];
        $drop_down_box['is_purchasing'] =  [['status'=>2,'message'=>'是'],['status'=>1,'message'=>'否']];
        $drop_down_box['is_productismulti'] =[['productismulti'=>1,'message'=>'spu'],['productismulti'=>2,'message'=>'捆绑销售'],['productismulti'=>3,'message'=>'普通']];
        //$drop_down_box['is_audit_type'] = [['status'=>1,'message'=>'待采购经理审核'],['status'=>2,'message'=>'待品控审核'],['status'=>3,'message'=>'审核通过'],['status'=>4,'message'=>'驳回'],['status'=>5,'message'=>'审核通过']];
        $drop_down_box['is_audit_type'] = getProductModStatus();
        $drop_down_box['equal_sup_id'] =  getEqualSupId();
        $drop_down_box['equal_sup_name'] = getEqualSupName();
        $drop_down_box['is_new'] = [['status'=>1,'message'=>'是'],['status'=>0,'message'=>'否']];
        $drop_down_box['is_overseas_first_order'] = [['status'=>0,'message'=>'否'],['status'=>1,'message'=>'是']];
        $drop_down_box['transformation']=[['status'=>1,'message'=>'否'],['status'=>6,'message'=>'是']];

        $drop_down_box['is_customized'] = [['status'=>1,'message'=>'是'],['status'=>2,'message'=>'否']];

        $drop_down_box['is_shipping'] = [['status'=>1,'message'=>'包邮'],['status'=>2,'message'=>'不包邮']];

        $drop_down_box['is_overseas'] = [['status'=>1,'message'=>'否'],['status'=>2,'message'=>'共用'],['status'=>3,'message'=>'是']];
        $settlementData = [];

        $data = $this->settlementModel->get_settlement([]);
        foreach($data['list'] as $data_key=>$data_value){
            $settlementData[$data_value['settlement_code']] = $data_value['settlement_name'];
        }
        $drop_down_box['settlement_method'] =$settlementData;
        $orders_info['drop_down_box'] = $drop_down_box;


        $this->success_json($orders_info);

    }
    /**
     * 产品导出
    /product/product/product_export
     * @author Jaden
     */
    public function product_export(){
        ini_set('memory_limit','3000M');
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->load->helper('status_product');
        $this->load->helper('status_order');
        $ids = $this->input->get_post('ids');

        $product_line_id = $this->input->get_post('product_line_id');
        if(!empty($product_line_id)){
            $product_line_ids = $this->product_line_model->get_all_category($product_line_id);
            $product_line_ids = substr($product_line_ids, 0, -1);
        }else{
            $product_line_ids =  $product_line_id;
        }
        if(!empty($ids)){
            $params['ids']   = $ids;
        }else{
            $client_params = [];

            if( !empty($_GET) ) {
                $client_params['product_line_id'] = $product_line_ids;
                foreach( $_GET as $key=>$value ) {
                    if( $key != "product_line_id") {
                        $client_params[$key] = $this->input->get_post($key);
                    }
                }
            }
            $params = [
                'sku' => isset($client_params['sku'])?$client_params['sku']:NULL, // SKU
                'product_line_id' => $product_line_ids,
                'supplier_code' => isset($client_params['supplier_code'])?$client_params['supplier_code']:NULL,
                'product_status' =>  isset($client_params['product_status'])?$client_params['product_status']:NULL,
                'supply_status' => isset($client_params['supply_status'])?$client_params['supply_status']:NULL,//货源状态(1.正常,2.停产,3.断货,4.停货)
                'audit_status' =>isset($client_params['audit_status'])?$client_params['audit_status']:NULL,//审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]
                'is_cross_border' => isset($client_params['is_cross_border'])?$client_params['is_cross_border']:NULL,//是否是跨境宝(默认0.否,1.跨境宝)
                'is_abnormal' => isset($client_params['is_abnormal'])?$client_params['is_abnormal']:NULL,//是否异常 1[否] 2[是]
                'status' => isset($client_params['status'])?$client_params['status']:NULL,//是否禁用 1[否] 2[是]
                'is_relate_ali' => isset($client_params['is_relate_ali'])?$client_params['is_relate_ali']:NULL,
                'buyer_code' => isset($client_params['buyer_code'])?$client_params['buyer_code']:NULL,
                'is_inspection' =>isset($client_params['is_inspection'])?$client_params['is_inspection']:NULL, //is_inspection
                'is_invaild' => isset($client_params['is_invaild'])?$client_params['is_invaild']:NULL,
                'search' => isset($client_params['search'])?$client_params['search']:NULL,
                'supplier_source' => isset($client_params['supplier_source'])?$client_params['supplier_source']:NULL,
                'starting_qty_start' => isset($client_params['starting_qty_start'])?$client_params['starting_qty_start']:NULL,
                'starting_qty_end' => isset($client_params['starting_qty_end'])?$client_params['starting_qty_end']:NULL,
                'product_name' => isset($client_params['product_name'])?$client_params['product_name']:NULL,
                // 'is_purchaseing'   => $this->input->get_post('is_purchaseing'), // 是否需要待采购
                'is_purchaseing'   => isset($client_params['is_purchaseing'])?$client_params['is_purchaseing']:NULL, // 是否需要待采购
                'develop_code'   => isset($client_params['developer_code'])?$client_params['developer_code']:NULL, // 开发人员
                'product_name'   => isset($client_params['product_name'])?$client_params['product_name']:NULL, // 商品名称
                'equal_sup_id'   => isset($client_params['equal_sup_id'])?$client_params['equal_sup_id']:NULL, // 供应商ID是否一致
                'equal_sup_name'   => isset($client_params['equal_sup_name'])?$client_params['equal_sup_name']:NULL, // 供应商名称是否一致
                'is_new'  => isset($client_params['is_new'])?$client_params['is_new']:NULL,
                'is_overseas_first_order' => isset($client_params['is_overseas_first_order'])?$client_params['is_overseas_first_order']:NULL,
                'transformation' =>  isset($client_params['transformation'])?$client_params['transformation']:NULL,

                'overseas_ids'   => isset($client_params['overseas_ids'])?$client_params['overseas_ids']:NULL,
                'domestic_ids'   => isset($client_params['domestic_ids'])?$client_params['domestic_ids']:NULL,
                'settlement_method'   => isset($client_params['settlement_method'])?$client_params['settlement_method']:NULL,
                'is_overseas'  => isset($client_params['is_overseas'])?$client_params['is_overseas']:NULL,
                'is_shipping'  => isset($client_params['is_shipping'])?$client_params['is_shipping']:NULL,
                'create_time_start' => isset($client_params['create_time_start'])?$client_params['create_time_start']:NULL,
                'create_time_end' => isset($client_params['create_time_end'])?$client_params['create_time_end']:NULL
            ];
        }

        $is_productismulti = isset($client_params['is_productismulti'])?$client_params['is_productismulti']:NULL;
        if( !empty($is_productismulti)) {

            $params['is_productismulti'] = $is_productismulti;
        }
        $params['new']  = 1;

        if( isset($params['overseas_ids']) && !empty($params['overseas_ids'])){

            $params['overseas_supplier_code'] = ['xx2'];
            $groupids = $this->supplier_buyer_model->getUserData($params['overseas_ids'],2);
            if(!empty($groupids)){

                $params['overseas_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        if( isset($params['domestic_ids']) && !empty($params['domestic_ids'])){

            $params['domestic_supplier_code'] =['xx2'];
            $groupids = $this->supplier_buyer_model->getUserData($params['domestic_ids'],1);
            if(!empty($groupids)){

                $params['domestic_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        //转入下载中心--start
        $this->load->model('system/Data_control_config_model');
        $field = 'p.is_relate_ali,p.is_shipping,p.sku_state_type,p.sku_message,p.outer_box_volume,p.product_volume,p.inside_number,p.box_size,p.is_new,p.is_overseas_first_order,p.audit_status_log,p.is_purchasing,p.is_inspection,p.coupon_rate,p.productismulti,p.producttype,p.is_invalid,p.id,p.sku,p.supplier_code,p.product_img_url,p.product_thumb_url,p.product_name,p.declare_cname,p.tax_rate,export_cname,p.declare_unit,p.export_model,p.product_status,p.product_line_id,p.create_user_name,p.create_time,p.original_start_qty,p.original_start_qty_unit,p.starting_qty,p.starting_qty_unit,p.is_drawback,p.purchase_price,p.ticketed_point,p.supplier_name,p.product_cn_link,p.supply_status,p.note,p.is_abnormal,p.is_equal_sup_id,p.is_equal_sup_name';
        $list = $this->product_model->get_product_list($params,0,10,$field);

        $total = $list['page_data']['total'];
        try {
            $ext = 'csv';
            if($total >= 150000){

                $this->error_json('产品管理SKU最多只能导出15万条数据，请分批导出');
            }

            $params['role_name'] = get_user_role();
            $result = $this->Data_control_config_model->insertDownData($params, 'PRODUCTDATA', '产品管理', getActiveUserName(), $ext, $total);
        } catch (Exception $exp) {
            $this->error_json($exp->getMessage());
        }
        if ($result) {
            $this->success_json("添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }

        die();
        //转入下载中心--end

        $page = $this->input->get_post('offset');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = 1;
        $offset = ($page - 1) * $limit;
        $field = 'p.is_relate_ali,p.sku_message,p.is_overseas_first_order,p.is_new,audit_status_log,p.is_purchasing,p.is_inspection,p.coupon_rate,p.is_multi as productismulti,p.product_type AS producttype,p.is_invalid,p.id,p.sku,p.supplier_code,p.product_img_url,p.product_thumb_url,p.product_name,p.declare_cname,p.tax_rate,export_cname,p.declare_unit,p.export_model,p.product_status,p.product_line_id,p.create_user_name,p.create_time,p.original_start_qty,p.original_start_qty_unit,p.starting_qty,p.starting_qty_unit,p.is_drawback,p.purchase_price,p.ticketed_point,p.supplier_name,p.product_cn_link,p.supply_status,p.note,p.is_abnormal,p.is_equal_sup_id,p.is_equal_sup_name';
        // $orders_info = $this->product_model->get_product_list($params, $offset, $limit,$field,True);
        $total =  $this->product_model->get_product_sum($params);
        if($total>100000){//单次导出限制
            $template_file = 'product.xlsx';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }
        //$total = 1000;
        //前端路径
        $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $file_name = 'product.'.time().'.csv';
        $product_file = $webfront_path.'/webfront/download_csv/'.$file_name;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $fp = fopen($product_file, "a");
        $heads = ['SKU','是否海外首单','是否新品','产品名称','票面税率','出口退税税率','申报中文名','申报单位','出口申报型号','产品状态','一级产品线','二级产品线','三级产品线','四级产品线','产品线','开发员','创建时间','原始起订量','原始起订量单位','最新起订量','最新起订量单位','是否退税','单价','税点', '供应商名称','箱内数','外箱尺寸','外箱体积','产品体积','采购员','采购链接','货源状态','供应商来源','是否异常','备注','连接是否失效','产品类型','是否代采','是否商检','供应商ID是否一致','供应商名称是否一致','审核状态','商品参数'];
        foreach($heads as $key => $item) {
            $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);
        if($total>=1){
            $page_limit = 200;
            $pages =  ceil($total/$page_limit);
            for ($i=1; $i <= $pages ; ++$i) {
                $export_offset = ($i - 1) * $page_limit;
                $orders_export_info = $this->product_model->get_product_list($params, $export_offset, $page_limit,$field);
                $product_list = $orders_export_info['value'];
                $tax_list_tmp = [];
                if($product_list){
                    foreach($product_list as $key=>$v_value){

                        $supplier_buyer_list = $this->supplier_buyer_model->get_buyer_list($v_value['supplier_code']);
                        $supplier_buyer_user = '';
                        if(!empty($supplier_buyer_list)){
                            foreach ($supplier_buyer_list as $k => $val) {
                                if(PURCHASE_TYPE_INLAND == $val['buyer_type']){
                                    $buyer_type_name = '国内仓';
                                }elseif(PURCHASE_TYPE_OVERSEA == $val['buyer_type']){
                                    $buyer_type_name = '海外仓';
                                }elseif(PURCHASE_TYPE_FBA == $val['buyer_type']){
                                    $buyer_type_name = 'FBA';
                                }else{
                                    $buyer_type_name = '未知';
                                }
                                $supplier_buyer_user.= $buyer_type_name.':'.$val['buyer_name'].',';
                            }
                        }
                        $v_value_tmp                       = [];
                        $v_value_tmp['sku']                = iconv("UTF-8", "GBK//IGNORE",$v_value['sku']."\t");
                        $v_value_tmp['is_overseas_first_order_ch'] = iconv("UTF-8", "GBK//IGNORE",$v_value['is_overseas_first_order_ch']);
                        $v_value_tmp['is_new_ch'] = iconv("UTF-8", "GBK//IGNORE",$v_value['is_new_ch']);
                        $v_value_tmp['product_name']  = iconv("UTF-8", "GBK//IGNORE",$v_value['product_name']);
                        $v_value_tmp['coupon_rate']   = iconv("UTF-8", "GBK//IGNORE",$v_value['coupon_rate']);
                        $v_value_tmp['tax_rate']  = iconv("UTF-8", "GBK//IGNORE",$v_value['tax_rate']);
                        $v_value_tmp['declare_unit']         = iconv("UTF-8", "GBK//IGNORE",$v_value['declare_unit']);
                        $v_value_tmp['declare_cname']         = iconv("UTF-8", "GBK//IGNORE",$v_value['declare_cname']);
                        $v_value_tmp['export_model']         = iconv("UTF-8", "GBK//IGNORE",$v_value['export_model']);//出口申报型号
                        $v_value_tmp['product_status']       = iconv("UTF-8", "GBK//IGNORE",getProductStatus($v_value['product_status']));//产品状态

                        if( isset($v_value['category_line_data'][0]) && !empty($v_value['category_line_data'][0]))
                        {
                            $v_value_tmp['one_line_name'] = iconv("UTF-8", "GBK//IGNORE",$v_value['category_line_data'][0]['product_line_name']);
                        }else{
                            $v_value_tmp['one_line_name'] = NULL;
                        }
                        if( isset($v_value['category_line_data'][1]) && !empty($v_value['category_line_data'][1]))
                        {
                            $v_value_tmp['two_line_name'] = iconv("UTF-8", "GBK//IGNORE",$v_value['category_line_data'][1]['product_line_name']);
                        }else{
                            $v_value_tmp['two_line_name'] = NULL;
                        }
                        if( isset($v_value['category_line_data'][2]) && !empty($v_value['category_line_data'][2]))
                        {
                            $v_value_tmp['three_line_name'] = iconv("UTF-8", "GBK//IGNORE",$v_value['category_line_data'][2]['product_line_name']);
                        }else{
                            $v_value_tmp['three_line_name'] = NULL;
                        }
                        if( isset($v_value['category_line_data'][3]) && !empty($v_value['category_line_data'][3]))
                        {
                            $v_value_tmp['four_line_name'] = iconv("UTF-8", "GBK//IGNORE",$v_value['category_line_data'][3]['product_line_name']);
                        }else{
                            $v_value_tmp['four_line_name'] = NULL;
                        }

                        $v_value_tmp['product_line_id']       = iconv("UTF-8", "GBK//IGNORE",$this->product_line->get_product_line_name($v_value['product_line_id']));
                        $v_value_tmp['create_user_name']       = iconv("UTF-8", "GBK//IGNORE",$v_value['create_user_name']);
                        $v_value_tmp['create_time']       = iconv("UTF-8", "GBK//IGNORE",$v_value['create_time']);
                        $v_value_tmp['original_start_qty']          = iconv("UTF-8", "GBK//IGNORE",$v_value['original_start_qty']);
                        $v_value_tmp['original_start_qty_unit']     = iconv("UTF-8", "GBK//IGNORE",$v_value['original_start_qty_unit']);
                        $v_value_tmp['starting_qty']                = iconv("UTF-8", "GBK//IGNORE",$v_value['starting_qty']);
                        $v_value_tmp['starting_qty_unit']           = iconv("UTF-8", "GBK//IGNORE",$v_value['starting_qty_unit']);
                        $v_value_tmp['is_drawback']       = iconv("UTF-8", "GBK//IGNORE",getIsDrawback($v_value['is_drawback']));
                        $v_value_tmp['purchase_price']       = iconv("UTF-8", "GBK//IGNORE",$v_value['purchase_price']);
                        $v_value_tmp['ticketed_point']       = iconv("UTF-8", "GBK//IGNORE",$v_value['ticketed_point']);//税点
                        $v_value_tmp['supplier_name']       = iconv("UTF-8", "GBK//IGNORE",$v_value['supplier_name']);
                        $v_value_tmp['inside_number']       = iconv("UTF-8", "GBK//IGNORE",$v_value['inside_number']);
                        $v_value_tmp['box_size']            = iconv("UTF-8", "GBK//IGNORE",$v_value['box_size']);
                        $v_value_tmp['outer_box_volume']    = iconv("UTF-8", "GBK//IGNORE",$v_value['outer_box_volume']);
                        $v_value_tmp['product_volume']      = iconv("UTF-8", "GBK//IGNORE",$v_value['product_volume']);
                        $v_value_tmp['supplier_buyer_user']       = iconv("UTF-8", "GBK//IGNORE",rtrim($supplier_buyer_user,','));
                        $v_value_tmp['product_cn_link']       = iconv("UTF-8", "GBK//IGNORE",$v_value['product_cn_link']);
                        $v_value_tmp['supply_status']       = iconv("UTF-8", "GBK//IGNORE",!empty($v_value['supply_status'])?getProductsupplystatus($v_value['supply_status']):'');
                        $v_value_tmp['supplier_source']     =  iconv("UTF-8", "GBK//IGNORE",$v_value['supplier_source_ch']);
                        $v_value_tmp['is_abnormal']       = iconv("UTF-8", "GBK//IGNORE",!empty($v_value['is_abnormal'])?getProductAbnormal($v_value['is_abnormal']):'');
                        $v_value_tmp['note']       = $v_value['note'];


                        if( empty($v_value['is_invalid']) || $v_value['is_invalid'] ==0 ) {
                            $v_value_tmp['is_invalid'] = iconv("UTF-8", "GBK//IGNORE",'正常');
                        }else if( $v_value['is_invalid'] ==1 ) {
                            $v_value_tmp['is_invalid'] = iconv("UTF-8", "GBK//IGNORE",'失效');
                        }

                        $v_value_tmp['t_product_type'] = NULL;
                        if( $v_value['productismulti'] == 2 ) {

                            $v_value_tmp['t_product_type'].= iconv("UTF-8", "GBK//IGNORE",' SPU');
                        }

                        if( $v_value['producttype'] == 2 ) {

                            $v_value_tmp['t_product_type'].=iconv("UTF-8", "GBK//IGNORE",' 捆绑销售');
                        }
                        if( ( $v_value['productismulti'] == 0 || $v_value['productismulti'] == 1) && $v_value['producttype'] ==1 ){

                            $v_value_tmp['t_product_type'].=iconv("UTF-8", "GBK//IGNORE",' 普通');
                        }

                        if( $v_value['is_purchasing'] == 1){
                            $v_value_tmp['is_purchasing'] = iconv("UTF-8", "GBK//IGNORE",'否');
                        }
                        if( $v_value['is_purchasing'] == 2){
                            $v_value_tmp['is_purchasing'] = iconv("UTF-8", "GBK//IGNORE",'是');
                        }
                        // iconv("UTF-8", "GBK//IGNORE",'不商检')
                        $v_value_tmp['is_inspection'] =  ( $v_value['is_inspection'] ==1 )?iconv("UTF-8", "GBK//IGNORE",'不商检'):iconv("UTF-8", "GBK//IGNORE",'商检');
                        $v_value_tmp['is_equal_sup_id'] = iconv("UTF-8", "GBK//IGNORE",getEqualSupId($v_value['is_equal_sup_id']));
                        $v_value_tmp['is_equal_sup_name'] = iconv("UTF-8", "GBK//IGNORE",getEqualSupName($v_value['is_equal_sup_name']));
                        $v_value_tmp['audit_status_log_cn'] = iconv("UTF-8", "GBK//IGNORE",$v_value['audit_status_log_cn']);
                        $v_value_tmp['sku_message'] = iconv("UTF-8", "GBK//IGNORE",$v_value['sku_message']);
                        fputcsv($fp, $v_value_tmp);

                    }
                }
                //每1万条数据就刷新缓冲区
                ob_flush();
                flush();
            }
        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=$down_host.'download_csv/'.$file_name;
        $this->success_json($down_file_url);

    }

    /**
     * 产品管理列表导出excel
     * product/product/product_export_excel
     * @author Sinder
     * @date 2019-05-27
     */
    public function product_export_excel(){
        ini_set('memory_limit','3000M');
        set_time_limit(0);
        $this->load->helper('export_excel');
        $this->load->helper('status_product');
        $this->load->helper('status_order');
        $ids = $this->input->get_post('ids');

        $product_line_id = $this->input->get_post('product_line_id');
        if(!empty($product_line_id)){
            $product_line_ids = $this->product_line_model->get_all_category($product_line_id);
            $product_line_ids = substr($product_line_ids, 0, -1);
        }else{
            $product_line_ids =  $product_line_id;
        }
        if(!empty($ids)){
            $params['ids']   = $ids;
        }else{


            $client_params = [];

            if( !empty($_GET) ) {
                $client_params['product_line_id'] = $product_line_ids;
                foreach( $_GET as $key=>$value ) {
                    if( $key != "product_line_id") {
                        $client_params[$key] = $this->input->get_post($key);
                    }
                }
            }
            $params = [
                'sku' => isset($client_params['sku'])?$client_params['sku']:NULL, // SKU
                'product_line_id' => $product_line_ids,
                'supplier_code' => isset($client_params['supplier_code'])?$client_params['supplier_code']:NULL,
                'product_status' =>  isset($client_params['product_status'])?$client_params['product_status']:NULL,
                'supply_status' => isset($client_params['supply_status'])?$client_params['supply_status']:NULL,//货源状态(1.正常,2.停产,3.断货,4.停货)
                'audit_status' =>isset($client_params['audit_status'])?$client_params['audit_status']:NULL,//审核状态 1[待采购审核] 2[待品控审核] 3[审核通过] 4[驳回]
                'is_cross_border' => isset($client_params['is_cross_border'])?$client_params['is_cross_border']:NULL,//是否是跨境宝(默认0.否,1.跨境宝)
                'is_abnormal' => isset($client_params['is_abnormal'])?$client_params['is_abnormal']:NULL,//是否异常 1[否] 2[是]
                'status' => isset($client_params['status'])?$client_params['status']:NULL,//是否禁用 1[否] 2[是]
                'is_relate_ali' => isset($client_params['is_relate_ali'])?$client_params['is_relate_ali']:NULL,
                'buyer_code' => isset($client_params['buyer_code'])?$client_params['buyer_code']:NULL,
                'is_inspection' =>isset($client_params['is_inspection'])?$client_params['is_inspection']:NULL, //is_inspection
                'is_invaild' =>isset($client_params['is_invaild'])?$client_params['is_invaild']:NULL,
                'supplier_source' => isset($client_params['supplier_source'])?$client_params['supplier_source']:NULL,
                'starting_qty_start' => isset($client_params['starting_qty_start'])?$client_params['starting_qty_start']:NULL,
                'starting_qty_end' => isset($client_params['starting_qty_end'])?$client_params['starting_qty_end']:NULL,
                'is_purchaseing'   => isset($client_params['is_purchaseing'])?$client_params['is_purchaseing']:NULL, // 是否需要待采购
                'develop_code'   => isset($client_params['developer_code'])?$client_params['developer_code']:NULL, // 开发员查询
                'product_name'   => isset($client_params['product_name'])?$client_params['product_name']:NULL, // 商品名称
                'equal_sup_id'   => isset($client_params['equal_sup_id'])?$client_params['equal_sup_id']:NULL, // 供应商ID是否一致
                'equal_sup_name'   => isset($client_params['equal_sup_name'])?$client_params['equal_sup_name']:NULL, // 供应商名称是否一致
                'is_new'  => isset($client_params['is_new'])?$client_params['is_new']:NULL,
                'is_overseas_first_order' => isset($client_params['is_overseas_first_order'])?$client_params['is_overseas_first_order']:NULL,
                'is_gateway' => isset($client_params['is_gateway'])?$client_params['is_gateway']:NULL,
                'transformation' => isset($client_params['transformation'])?$client_params['transformation']:NULL,
                'overseas_ids'   => isset($client_params['overseas_ids'])?$client_params['overseas_ids']:NULL,
                'domestic_ids'   =>  isset($client_params['domestic_ids'])?$client_params['domestic_ids']:NULL,
                'is_shipping'    => isset($client_params['is_shipping'])?$client_params['is_shipping']:NULL,
                'is_overseas'    => isset($client_params['is_overseas'])?$client_params['is_overseas']:NULL,
                'create_time_start' =>  isset($client_params['create_time_start'])?$client_params['create_time_start']:NULL,
                'create_time_end' => isset($client_params['create_time_end'])?$client_params['create_time_end']:NULL
            ];
        }

        if( isset($params['overseas_ids']) && !empty($params['overseas_ids'])){

            $params['overseas_supplier_code'] = ['xx2'];
            $groupids = $this->supplier_buyer_model->getUserData($params['overseas_ids'],2);
            if(!empty($groupids)){

                $params['overseas_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        if( isset($params['domestic_ids']) && !empty($params['domestic_ids'])){

            $params['domestic_supplier_code'] =['xx2'];
            $groupids = $this->supplier_buyer_model->getUserData($params['domestic_ids'],1);
            if(!empty($groupids)){

                $params['domestic_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }
        $is_productismulti = isset($client_params['is_productismulti'])?$client_params['is_productismulti']:NULL;
        if( !empty($is_productismulti)) {

            $params['is_productismulti'] = $is_productismulti;
        }
        $params['new'] =1;
        $params['supplier_source'] = isset($client_params['supplier_source'])?$client_params['supplier_source']:NULL;
        $page = $this->input->get_post('offset');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $field = 'p.devliy,p.sample_packaging_type,p.product_weight,p.is_relate_ali,p.is_shipping,p.sku_state_type,p.sku_message,p.outer_box_volume,p.product_volume,p.inside_number,p.box_size,p.is_new,p.is_overseas_first_order,p.audit_status_log,p.is_purchasing,p.is_inspection,p.coupon_rate,p.productismulti,p.producttype,p.is_invalid,p.id,p.sku,p.supplier_code,p.product_img_url,p.product_thumb_url,p.product_name,p.declare_cname,p.tax_rate,export_cname,p.declare_unit,p.export_model,p.product_status,p.product_line_id,p.create_user_name,p.create_time,p.original_start_qty,p.original_start_qty_unit,p.starting_qty,p.starting_qty_unit,p.is_drawback,p.purchase_price,p.ticketed_point,p.supplier_name,p.product_cn_link,p.supply_status,p.note,p.is_abnormal,p.is_equal_sup_id,p.is_equal_sup_name';
        $total = $this->product_model->get_product_sum($params);
        if ($total > 100000) {//单次导出限制
            $template_file = 'product.xlsx';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url = $down_host . 'download_csv/' . $template_file;
            $this->error_json($down_file_url);
        }
        $file_name = 'product_' . date('YmdHis') . '.xlsx';
        $heads = ['SKU','是否海外首单','是否新品', '图片', '产品名称', '票面税率', '出口退税税率', '申报中文名',
            '申报单位', '出口申报型号', '产品状态', '一级产品线', '二级产品线',
            '三级产品线', '四级产品线', '产品线', '开发员', '创建时间',
            '原始起订量', '原始起订量单位', '最新起订量', '最新起订量单位',
            '是否退税', '单价', '税点', '供应商名称','箱内数','箱外尺寸',
            '箱外体积','产品体积','采购员', '采购链接', '货源状态', '供应商来源',
            '是否异常', '备注', '连接是否失效', '产品类型','是否代采','是否商检',
            '供应商ID是否一致','供应商名称是否一致','审核状态','商品参数','国内转海外','所属小组','结算方式','是否包邮','是否海外仓','1688起订量','样品重量(g)','包装类型','交期'];
        $data_values = array();
        if ($total > 0) {
            $page_limit = 200;
            for ($i = 1; $i <= ceil($total / $page_limit); ++$i) {

                $export_offset = ($i - 1) * $page_limit;
                $product_list = $this->product_model->get_product_list($params, $export_offset, $page_limit,$field);
                if( isset($product_list['value']) && !empty($product_list['value'])) {
                    $role_name=get_user_role();//当前登录角色
                    $roleString = implode(",",$role_name);
                 //  $product_list['value'] = ShieldingData($product_list['value'],['supplier_name','supplier_code'],$role_name,NULL);
                    foreach ($product_list['value'] as $key => $v_value) {
                        $supplier_buyer_list = $this->supplier_buyer_model->get_buyer_list($v_value['supplier_code']);
                        $supplier_buyer_user = '';

                        if (!empty($supplier_buyer_list)) {
                            foreach ($supplier_buyer_list as $k => $val) {
                                if (PURCHASE_TYPE_INLAND == $val['buyer_type']) {
                                    $buyer_type_name = '国内仓';
                                } elseif (PURCHASE_TYPE_OVERSEA == $val['buyer_type']) {
                                    $buyer_type_name = '海外仓';
                                } elseif (PURCHASE_TYPE_FBA == $val['buyer_type']) {
                                    $buyer_type_name = 'FBA';
                                } else {
                                    $buyer_type_name = '未知';
                                }
                                $supplier_buyer_user .= $buyer_type_name . ':' . $val['buyer_name'] . ',';
                            }
                        }
                        $v_value_tmp = [];
                        $v_value_tmp['sku'] = $v_value['sku'];
                        $v_value_tmp['is_overseas_first_order_ch'] = $v_value['is_overseas_first_order_ch'];
                        $v_value_tmp['is_new_ch'] = $v_value['is_new_ch'];
                        $v_value_tmp['product_img_url'] = $v_value['product_img_url'];
                        $v_value_tmp['product_name'] = $v_value['product_name'];
                        $v_value_tmp['coupon_rate']  = $v_value['coupon_rate'];
                        $v_value_tmp['tax_rate'] = $v_value['tax_rate'];
                        $v_value_tmp['declare_unit'] = $v_value['declare_unit'];
                        $v_value_tmp['declare_cname'] = $v_value['declare_cname'];
                        $v_value_tmp['export_model'] = $v_value['export_model'];//出口申报型号
                        $v_value_tmp['product_status'] = getProductStatus($v_value['product_status']);//产品状态
                        if (isset($v_value['category_line_data'][0]) && !empty($v_value['category_line_data'][0])) {
                            $v_value_tmp['one_line_name'] =$v_value['category_line_data'][0]['product_line_name'];
                        }else{
                            $v_value_tmp['one_line_name'] = NULL;
                        }
                        if (isset($v_value['category_line_data'][1]) && !empty($v_value['category_line_data'][1])) {
                            $v_value_tmp['two_line_name'] = $v_value['category_line_data'][1]['product_line_name'];
                        }else{
                            $v_value_tmp['two_line_name'] = NULL;
                        }
                        if (isset($v_value['category_line_data'][2]) && !empty($v_value['category_line_data'][2])) {
                            $v_value_tmp['three_line_name'] =$v_value['category_line_data'][2]['product_line_name'];
                        } else {
                            $v_value_tmp['three_line_name'] = NULL;
                        }
                        if (isset($v_value['category_line_data'][3]) && !empty($v_value['category_line_data'][3])) {
                            $v_value_tmp['four_line_name'] = $v_value['category_line_data'][3]['product_line_name'];
                        } else {
                            $v_value_tmp['four_line_name'] = NULL;
                        }

                        $v_value_tmp['product_line_id'] = $this->product_line->get_product_line_name($v_value['product_line_id']);
                        $v_value_tmp['create_user_name'] = $v_value['create_user_name'];
                        $v_value_tmp['create_time'] = $v_value['create_time'];
                        $v_value_tmp['original_start_qty'] = $v_value['original_start_qty'];
                        $v_value_tmp['original_start_qty_unit'] = $v_value['original_start_qty_unit'];
                        $v_value_tmp['starting_qty'] = $v_value['starting_qty'];
                        $v_value_tmp['starting_qty_unit'] = $v_value['starting_qty_unit'];
                        $v_value_tmp['is_drawback'] = getIsDrawback($v_value['is_drawback']);
                        $v_value_tmp['purchase_price'] = $v_value['purchase_price'];
                        $v_value_tmp['ticketed_point'] = $v_value['ticketed_point'];//税点
                        $v_value_tmp['supplier_name'] = $v_value['supplier_name'];
                        if(strstr($roleString,'采购') || strstr($roleString,'财务') || strstr($roleString,'供应') || strstr($roleString,'品控')){

                            $v_value_tmp['supplier_name']= $v_value['supplier_name'];
                        }else{
                            $v_value_tmp['supplier_name'] = "*";
                        }
                        $v_value_tmp['inside_number']       = $v_value['inside_number'];
                        $v_value_tmp['box_size']            = $v_value['box_size'];
                        $v_value_tmp['outer_box_volume']    = $v_value['outer_box_volume']; // 箱外体积
                        $v_value_tmp['product_volume']      = $v_value['product_volume']; // 产品体积
                        $v_value_tmp['supplier_buyer_user'] = rtrim($supplier_buyer_user, ',');
                        $v_value_tmp['product_cn_link'] = $v_value['product_cn_link'];
                        $v_value_tmp['supply_status'] = !empty($v_value['supply_status']) ? getProductsupplystatus($v_value['supply_status']) : '';
                        $v_value_tmp['supplier_source'] = $v_value['supplier_source_ch'];//supplier_source_ch
                        $v_value_tmp['is_abnormal'] = !empty($v_value['is_abnormal']) ? getProductAbnormal($v_value['is_abnormal']) : '';
                        $v_value_tmp['note'] = $v_value['note'];

                        if( empty($v_value['is_invalid']) || $v_value['is_invalid'] ==0 ) {

                            $product_list['value']['is_invalid'] = "正常";
                        } else if ($v_value['is_invalid'] == 1) {
                            $product_list['value']['is_invalid'] = "失效";
                        }
                        $v_value_tmp['is_invalid'] = $product_list['value']['is_invalid'];
                        $v_value_tmp['t_product_type'] = NULL;
                        if ($v_value['productismulti'] == 2) {

                            $v_value_tmp['t_product_type'].= 'SPU ';
                        }
                        if( $v_value['producttype'] == 2 ) {

                            $v_value_tmp['t_product_type'].=' 捆绑销售';
                        }
                        if(  ( $v_value['productismulti'] == 0 || $v_value['productismulti'] == 1) && $v_value['producttype'] ==1 ){

                            $v_value_tmp['t_product_type'] .= ' 普通';
                        }
                        //$orders_info['value'][$key]['is_inspection'] =
                        if( $v_value['is_purchasing'] == 1){
                            $v_value_tmp['is_purchasing'] ='否';
                        }
                        if( $v_value['is_purchasing'] == 2){
                            $v_value_tmp['is_purchasing'] ='是';
                        }
                        $v_value_tmp['is_inspection'] =  ( $v_value['is_inspection'] ==1 )?"不商检":"商检";
                        $v_value_tmp['is_equal_sup_id'] = getEqualSupId($v_value['is_equal_sup_id']);
                        $v_value_tmp['is_equal_sup_name'] = getEqualSupName($v_value['is_equal_sup_name']);
                        $v_value_tmp['audit_status_log_cn'] = $v_value['audit_status_log_cn'];
                        $v_value_tmp['sku_message'] =str_replace('&nbsp;','',strip_tags($v_value['sku_message']));
                        //$v_value_tmp['sku_message'] = $v_value['sku_message'];
                        $v_value_tmp['sku_state_type_ch'] = $v_value['sku_state_type_ch'];
                        $v_value_tmp['groupName'] = !empty($v_value['groupName'])?$v_value['groupName']:'';
                        $oldpaymentData = $this->Supplier_model->get_supplier_payment($v_value['supplier_code']);
                        $v_value_tmp['settlement_method'] =$oldpaymentData;

                        if($v_value['is_shipping'] == 1){

                            $v_value_tmp['shipping_ch'] = "包邮";
                        }else{
                            $v_value_tmp['shipping_ch'] = "不包邮";
                        }
                        $product_attributes = $this->product_model->get_product_attribute_data($v_value['sku']);
                        $v_value_tmp['overseas_ch'] = "";
                        if($product_attributes['海外属性'] && !empty($product_attributes['海外属性'])){

                            if(count($product_attributes['海外属性']) == 1) {
                                if (in_array('国内仓', $product_attributes['海外属性'])) {

                                    $v_value_tmp['overseas_ch'] = "否";
                                }else{
                                    $v_value_tmp['overseas_ch'] = "是";
                                }
                            }else{
                                if (in_array('国内仓', $product_attributes['海外属性'])) {
                                    $v_value_tmp['overseas_ch'] = "共用";
                                }else{
                                    $v_value_tmp['overseas_ch'] = "是";
                                }
                            }
                        }else{
                            $v_value_tmp['overseas_ch'] = '';
                        }
                        $v_value_tmp['is_relate_ali'] = $v_value['is_relate_ali'];

                        $v_value_tmp['product_weight_number'] = $v_value['product_weight'];
                        $v_value_tmp['sample_packaging_type_ch'] =$v_value['sample_packaging_type'];
                        $v_value_tmp['devliy_ch'] = $v_value['devliy'];

                        $data_values[] = $v_value_tmp;
                    }
                }
            }
        }
        $result = array(
            'heads' => $heads,
            'data_values' => $data_values,
            'file_name' => $file_name,
            'field_img_name' => array('图片'),
            'field_img_key' => array('product_img_url')
        );
        $this->success_json($result);
    }

    /**
     *function:商品CSV 文件导入
     **/
    public function get_import_product(){

        try{
           // $import_json = $this->input->get_post('import_arr');
            $import_json = file_get_contents('php://input');
            $result_list = json_decode($import_json,True);
            $result_list = $result_list['import_arr'];
            if(!empty($result_list))
            {
                $error_data = [];
                $verify_success_data = [];
                foreach($result_list as $key=>$value)
                {
                    if( $key == 0 || $value[0] == '必填')
                    {
                        continue;
                    }
                    $skus_data = $value[0]; // 导入数据的SKU
                    if(empty($skus_data))
                    {
                        $value[19] = "SKU 必填";
                        $error_data[] = $value;
                        continue;
                    }

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


                    $point_ticket = $value[1]; // 开票点
                    if( !empty($point_ticket))
                    {
                        $point_ticket = str_replace("%","",$point_ticket);

                        if( !($point_ticket>0) || $point_ticket>100)
                        {
                            $value[19] = "开票点必须大于0并且小于1%";
                            $error_data[] =$value;
                            continue;
                        }
                    }else if($value[1] === ''){
                        $value[1] = '';
                    }

                    if( $value[1] !== '')
                    {
                        $value[1] = $value[1];
                    }

                    //未税单价
                    $product_price = $value[2];
                    if( !empty($product_price) && $product_price !==0)
                    {
                        $product_price = (float)$product_price;
                        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $product_price)) {
                            $value[23] = "未税单价小时有效为只能为两位";
                            $error_data[] = $value;
                            continue;
                        }
                    }else if( empty($product_price)){
                        $value[23] = "未税单价必填并且不等于0";
                        $error_data[] = $value;
                        continue;
                    }
                    // 票面

                    $coupon_rate = ($value[3])/100;

                    if( !empty($coupon_rate) )
                    {
                        $coupon_rate = (float)$coupon_rate;
                        if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $coupon_rate)) {
                            $value[23] = "票面税率只能是2位小数";
                            $error_data[] = $value;
                            continue;
                        }

                        if( !($coupon_rate>=0 && $coupon_rate<=1))
                        {
                            $value[23] = "票面税率只能大于0 并且小于1%";
                            $error_data[] = $value;
                            continue;
                        }

                    }else if( $coupon_rate === '' || $coupon_rate === NULL){
                        $value[23] = "票面税率必填";
                        $error_data[] = $value;
                        continue;
                    }


                    //最小起订量
                    $min_product_number = $value[4];
                    if( $min_product_number<0)
                    {
                        $value[23] = "最小起订量填写错误";
                        $error_data[] = $value;
                        continue;
                    }else if( empty($min_product_number) ){

                        $value[23] = '最小起订量必填并且不等于0';
                        $error_data[] = $value;
                        continue;
                    }

                    // 最小起订量单位
                    $min_product_number_type = $value[5];
//                    if( empty($min_product_number_type) || !in_array($min_product_number_type,['个','件','套','台','把','PCS','只']))
//                    {
//                        if( empty($min_product_number_type)) {
//                            $value[15] = "最小起订量单位必填";
//                        }else {
//                            $value[15] = "最小起订量单位填写错误";
//                        }
//                        $error_data[] = $value;
//                        continue;
//                    }
                    // 系统与1688单位对应关系-系统
                    if(empty($value[6]))
                    {
                        $value[23] = "系统与1688单位对应关系-系统";
                        $error_data[] = $value;
                        continue;
                    }
                    $is_relate_ali = (int)$value[6];
                    if(  $is_relate_ali<0)
                    {
                        $value[23] = "系统与1688单位对应关系（1688）填写错误";
                        $error_data[] = $value;
                        continue;
                    }
                    if(empty($value[7]))
                    {
                        $value[23] = "系统与1688单位对应关系-采购系统";
                        $error_data[] = $value;
                        continue;
                    }

                    $is_relate_ali_puchase = (int)$value[7];

                    if(  $is_relate_ali_puchase<0)
                    {
                        $value[23] = "系统与1688单位对应关系（采购系统）填写错误";
                        $error_data[] = $value;
                        continue;
                    }

                    $is_sample = $value[8];
                    $is_sample_flag = NULL;
                    if( empty($is_sample) || !in_array($is_sample,['是','否']))
                    {
                        $value[23] = "是否拿样填写错误";
                        $error_data[] = $value;
                        continue;
                    }else{

                        if( $is_sample == "是")
                        {
                            $is_sample_flag =1;
                        }else{
                            $is_sample_flag ="0";
                        }
                    }
                    $product_link = $value[9];
                    if( empty($product_link))
                    {
                        $value[23] = "采购链接必填";
                        $error_data[] = $value;
                        continue;
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
                    $product_supplier = $value[11];
                    $supply_status_flag = NULL;
                    $encode = mb_detect_encoding($product_supplier, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));

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
                    $new_product_link[trim($value[0])] = $product_link;
                    $new_is_sample[trim($value[0])] = $is_sample_flag;
                    $supply_status[trim($value[0])] = $supply_status_flag;
                    $create_remark[trim($value[0])] = $value[13];
                    $supplier_name[trim($value[0])] = $value[12];
                    $reason[trim($value[0])] = $value[14];
                    $inside_number[trim($value[0])] = $value[15];
                    $box_size[trim($value[0])] = $value[16];
                    $outer_box_volume[trim($value[0])] = $value[17];
                    $product_volume[trim($value[0])] = $value[18];
                    $new_starting_qty[trim($value[0])] = $min_product_number;
                    $new_starting_qty_unit[trim($value[0])] = $min_product_number_type;
                    $new_ali_ratio_own[trim($value[0])] = $is_relate_ali;
                    $new_ali_ratio_out[trim($value[0])] = $is_relate_ali_puchase;
                    $new_coupon_rate[trim($value[0])] = $coupon_rate;
                    $new_is_purchasing[trim($value[0])] = $is_purchasing_flag;
                    $new_devliy[trim($value[0])] = $value[19];
                    $new_iscustomized[trim($value[0])] = $is_customizedData_ch;
                    $new_long_delivery_ch[trim($value[0])] = $is_long_delivery_ch;
                    $new_is_shipping[trim($value[0])] = $is_shipping_value;

                    // 获取商品信息
                    $value[0] = trim($value[0]);
                    $product_message = $this->product_model->get_product_one(['sku'=>$value[0]]);
                    if( $product_message['ticketed_point'] < $value[1] && empty($value[13]))
                    {
                        $value[23] = "SKU:".$value[0].",开票点上涨，请填写备注";
                        $error_data[] = $value;
                        continue;
                    }

                    if( $product_message['coupon_rate'] < $value[3] && empty($value[13]))
                    {
                        $value[23] = "SKU:".$value[0].",开票点上涨，请填写备注";
                        $error_data[] = $value;
                        continue;
                    }

                    $supplier_message = $this->product_model->get_supplier_data($value[12],['status'=>1,'supplier_source'=>[1,3]]);

                    if($supplier_message['status'] == 1){

                        // 如果供应商合作状$value[23] = "第:".$key."行,供应商不符要求，请修改后重新导入";
                        //                        $error_data[] = $value;
                        //                        continue;态等于启用时, 供应商来源：国内 == 常规,临时 == 备用
                        if( $supplier_message['supplier_source'] != 1 && $supplier_message['supplier_source'] != 3){

                            $value[23] = "第".$key."行,供应商不符要求，请修改后重新导入";
                            $error_data[] = $value;
                             continue;
                        }
                        if( $supplier_message['supplier_source'] == 3 && !in_array($product_message['supply_status'],[10,3])){

                            $value[23] = "第".$key."行,供应商不符要求，请修改后重新导入";
                            $error_data[] = $value;
                            continue;
                        }

                    }

                    // 如果供应商合作状态 等于 预禁用,并且供应商来源 等于常规
                    if($supplier_message['status'] == 6 && $supplier_message['supplier_source']!=1){

                        $value[23] = "第".$key."行,供应商不符要求，请修改后重新导入";
                        $error_data[] = $value;
                        continue;
                    }



                    if( !empty($value[12]) && $value[12] != $product_message['supplier_name'])
                    {

                        if(empty($supplier_message))
                        {
                            $value[23] = "供应商不存在";
                            $error_data[] = $value;
                            continue;
                        }
                        $new_supplier_code[$value[0]] = $supplier_message['supplier_code'];
                        $new_supplier_name[$value[0]] = $supplier_message['supplier_name'];
                    }else {
                        $new_supplier_code[$value[0]] = isset($product_message['supplier_code']) ? $product_message['supplier_code'] : NULL;
                        $new_supplier_name[$value[0]] = isset($product_message['supplier_name']) ? $product_message['supplier_name'] : NULL;
                    }



                    if( !empty($value[12]) && $value[12] != $product_message['supplier_name'] )
                    {

                        $oldsupplier_info = $this->supplier_model->get_supplier_info($product_message['supplier_code'],false);
                        if( $supplier_message['supplier_source'] == 3 &&  $oldsupplier_info['supplier_source'] == 1 && $product_message['supply_status'] == PRODUCT_SCREE_APPLY_REASON_SUCCESS)
                        {
                            $value[23] = "供应商不能变更，原因:原供应商为常规并且SKU 货源状态为正常";
                            $error_data[] = $value;
                            continue;
                        }
                    }

                    $product_log_info = $this->product_update_log->get_product_log_info($value[0]);

                    if (in_array($product_log_info['audit_status'], [PRODUCT_UPDATE_LIST_AUDITED, PRODUCT_UPDATE_LIST_QUALITY_AUDIT, PRODUCT_UPDATE_LIST_FINANCE])) {

                        $value[23] = "SKU:".$value[0].",存在待审核记录";
                        $error_data[] = $value;
                        continue;
                    }

                    if( $value[2] > $product_message['purchase_price'] && empty($value[13]))
                    {
                        $value[23] = "SKU:".$value[0].",未税单价上涨，请填写备注";
                        $error_data[] = $value;
                        continue;
                    }

                    if( !empty($value[13]) && mb_strlen($value[13],'UTF8')>50)
                    {
                        $value[23] = "SKU:".$value[0].",备注长度不能超过50个字";
                        $error_data[] = $value;
                        continue;
                    }
                }
                if( empty($error_data)) {
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
                    $result = $this->update_product_save(json_encode($product_send_data),True);
                    if(!is_array($result) && True === $result)
                    {
                        $this->success_json('保存成功');
                    }else{
                        foreach($result_list as $list_key=>&$list_value)
                        {
                            if( $list_key ==0 )
                            {
                                continue;
                            }
                            if( !isset($result['sku'])) {
                                $error_skus = isset($result[$list_value[0]]) ? $result[$list_value[0]] : NULL;
                                $list_value[19] = $error_skus;
                            }else{
                                $error_skus = $result['sku'];
                                if( $list_value[0] == $error_skus)
                                {
                                    $list_value[19] = $result['message'];
                                }
                            }
                        }
                        $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
                        $file_name = 'product'.time().'.csv';
                        $product_file = $webfront_path.'/webfront/download_csv/'.$file_name;
                        if (file_exists($product_file)) {
                            unlink($product_file);
                        }
                        fopen($product_file,'w');
                        $fp = fopen($product_file, "a");
                        $heads = $result_list[0];
                        array_push($heads,"错误提示");

                        foreach($heads as $key => $item) {
                            $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
                        }
                        //将标题写到标准输出中
                        fputcsv($fp, $title);
                        foreach($result_list as $error_key=>$error_value)
                        {
                            $result_list[1] = $result_list[1];

                            if( $error_key ==0){
                                continue;
                            }

                            foreach($error_value as $err_key=>$err_value)
                            {
                                if( $err_key == 1)
                                {
                                    $error_value[$err_key] = $err_value;
                                }
                                if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $err_value)) {
                                    $error_value[$err_key] = iconv("UTF-8", "GBK//IGNORE",$err_value );
                                }
                            }

                            fputcsv($fp, $error_value);
                        }
                        ob_flush();
                        flush();
                        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
                        $error_sku = $result;
                        $down_file_url=$down_host.'download_csv/'.$file_name;

                        $return_data = array(

                            'error_message' => "共有".count($error_sku)."条数据错误，请确认是否下载",
                            'error_list' =>$down_file_url
                        );
                        $this->error_json($return_data);

                    }
                }else{

                    $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
                    $file_name = 'product.'.time().'.csv';
                    $product_file = $webfront_path.'/webfront/download_csv/'.$file_name;
                    if (file_exists($product_file)) {
                        unlink($product_file);
                    }
                    fopen($product_file,'w');
                    $fp = fopen($product_file, "a");
                    $heads = $result_list[0];
                    array_push($heads,"错误提示");

                    foreach($heads as $key => $item) {
                        $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
                    }
                    //将标题写到标准输出中
                    fputcsv($fp, $title);
                    $datas = array_merge( $error_data,$verify_success_data);
                    $verify_data = [];
                    foreach($datas as $error_key=>$error_value)
                    {
                        $error_value[0] = trim($error_value[0]);
                        if( in_array($error_value[0],$verify_data))
                        {
                            continue;
                        }
                        foreach($error_value as $err_key=>$err_value)
                        {
                            if( $err_key == 1)
                            {
                                $error_value[$err_key] = $err_value;
                            }
                            if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $err_value)) {
                                $error_value[$err_key] = iconv("UTF-8", "GBK//IGNORE",$err_value );
                            }
                        }
                        fputcsv($fp,$error_value);
                        $verify_data[] = $error_value[0];

                    }
                    ob_flush();
                    flush();
                    $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
                    $down_file_url=$down_host.'download_csv/'.$file_name;
                    $return_data = array(

                        'error_message' => "共有".count($error_data)."条数据错误，请确认是否下载",
                        'error_list' =>$down_file_url
                    );
                    $this->error_json($return_data);
                }

            }
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }
    /**
     * 显示修改产品预览
     * @author harvin
     * @date 2019-06-04
     */
    public function update_product_list(){
        $sku_list = $this->input->get_post('sku_list');
        if (empty($sku_list)) {
            $this->error_json('请选择要修改数据');
        }
        //转化数组
        $sku_product = explode(',', $sku_list);
        if (empty($sku_product) && !is_array($sku_product)) {
            $this->error_json('传入参数不合法');
        }
        foreach ($sku_product as $sku_row) {
            //根据SKU查找产品数据
            $product_info = $this->product_model->get_product_info($sku_row);
            if (empty($product_info)) {
                $this->error_json('找不到该SKU:' . $sku_row . '数据');
            }
            if ($this->product_update_log->check_in_audit($sku_row)) {
                $this->error_json('该SKU:' . $sku_row . '存在待审核的记录');
            }
        }
        //获取显示数据
        $reslut = $this->product_model->get_product_update_list($sku_product);
        if($reslut['status']==1){
            $this->success_json($reslut['data'],null,$reslut['errormasg']);
        }else{
            $this->error_json($reslut['errormasg']);
        }
    }
    /**
     * 保存修改数据
     * @author harvin
     * @date 2019-0604
     */
    public function update_product_save($data_json = array(),$import = False){

        $this->load->model('supplier/Supplier_model','supplier_model',false,'supplier');
        if(empty($data_json)) {
            $data_json = $this->input->get_post('data');
        }
        if(empty($data_json)){
            $this->error_json('数据不能为空');
        }
        $data_arr                = json_decode($data_json, true);
       // $this->_bulk = new MongoDB\Driver\BulkWrite();
       // $this->_bulk->insert($data_arr);
       // $result = $this->_mongodb->executeBulkWrite("{$this->_author_db}.product_audit", $this->_bulk);
        $sku_list                = $data_arr['sku_list'];
        $new_supplier_price      = $data_arr['new_supplier_price']; //未税单价
        $new_ticketed_point      = $data_arr['new_ticketed_point']; //开票税点
        $new_starting_qty        = $data_arr['new_starting_qty']; //最小起订量
        $new_starting_qty_unit   = $data_arr['new_starting_qty_unit']; //最小起订量单位
        $new_ali_ratio_own       = $data_arr['new_ali_ratio_own']; //单位对应关系（内部）
        $new_ali_ratio_out       = $data_arr['new_ali_ratio_out']; //单位对应关系（外部）
        $new_supplier_code       = $data_arr['new_supplier_code']; //供应商编码
        $new_supplier_name       = $data_arr['new_supplier_name']; //供应商名称
        $new_product_link        = $data_arr['new_product_link']; //采购链接
        $new_product_coupon_rate = $data_arr['coupon_rate']; //票面税
        $is_sample               = $data_arr['is_sample']; //是否拿样
        $supply_status           = $data_arr['supply_status']; //货源状态
        $create_remark           = $data_arr['create_remark'];//申请备注
        $is_customized           = $data_arr['is_customized']; // 是否定制
        $is_purchasing           = isset($data_arr['is_purchasing']) ? $data_arr['is_purchasing'] : null; // 是否待购
        $is_force_submit         = isset($data_arr['is_force_submit']) ? $data_arr['is_force_submit'] : null; // 是否强制提交,1.是,其他.否
        $reason                  = isset($data_arr['reason'])?$data_arr['reason']:NULL;
        $inside_number           = isset($data_arr['inside_number'])?$data_arr['inside_number']:NULL; // 箱内个数
        $box_size                = isset($data_arr['box_size'])?$data_arr['box_size']:''; // 箱内尺寸
        $outer_box_volume        = isset($data_arr['outer_box_volume'])?$data_arr['outer_box_volume']:''; //外箱体积
        $product_volume          = isset($data_arr['product_volume'])?$data_arr['product_volume']:''; //产品体积
        $devliy                  = $data_arr['devliy']; // 交期
        $long_devliy             = isset($data_arr['long_devliy'])?$data_arr['long_devliy']:'';
        $is_shipping             = isset($data_arr['is_shipping'])?$data_arr['is_shipping']:'';
        //转化数组
        $sku_product = explode(',', $sku_list);
        if (empty($sku_product) && !is_array($sku_product)) {
            $this->error_json('传入参数不合法');
        }
        $this->load->model('supplier/supplier_model');
        $this->load->library('alibaba/AliProductApi');
        $this->load->model('ali/ali_product_model');
        $data_sku     = $data_error = [];
        $audit_status = $audit_level = '';

        $error_list = [];
        $is_supplier_abnormal = false;
        // 验证数据是否满足要求（有任何一个不满足要求即退出）
        foreach ($sku_product as $sku){
            if($new_supplier_price[$sku] === ''){
                $error_list[$sku] = '单价不能为空';
                continue;
            }
            if(empty($new_supplier_code[$sku])){
                $error_list[$sku] = '供应商代码不能为空';
                continue;
            }
            if(strpos($new_supplier_name[$sku],'(正常)') !== false){

                $new_supplier_name[$sku] = str_replace('(正常)','',$new_supplier_name[$sku]);
            }

            if(strpos($new_supplier_name[$sku],'()') !== false){

                $new_supplier_name[$sku] = str_replace('()','',$new_supplier_name[$sku]);
            }

            if(empty($new_supplier_name[$sku])){
                $error_list[$sku] = '供应商名称不能为空';
                continue;
            }
            if(empty($new_product_link[$sku])){
                $error_list[$sku] = '采购链接不能为空';
                continue;
            }

            if(empty($is_shipping[$sku])){

                $error_list[$sku] = '是否包邮不能为空';
                continue;
            }

            /*
            if ($new_ticketed_point[$sku] == '')
                $this->error_json('税点不能为空');
            */
            if($is_sample[$sku] == ''){
                $error_list[$sku] = '是否拿样不能为空';
                continue;
            }

            if($supply_status[$sku] == ''){
                $error_list[$sku] = '货源状态不能为空';
                continue;
            }
            if($new_product_coupon_rate[$sku] == ''){
                $error_list[$sku] = '票面税率不能为空';
                continue;
            }

            if(!is_two_decimal($new_supplier_price[$sku])){
                $error_list[$sku] = '单价小数最多只能为两位';
                continue;
            }

            if(is_numeric($new_ticketed_point[$sku]) && !is_two_decimal($new_ticketed_point[$sku])){
                $error_list[$sku] = '开票点小数最多只能为两位';
                continue;
            }

            // 判断SKU 绑定的供应商是否支持开票
            $isSupplierInvoice  = $this->supplier_model->isSupplierInvoice($new_supplier_code[$sku]);
            // 如果供应商支持开票,判断开票点是否为0或者空
            if( True == $isSupplierInvoice  && ($new_ticketed_point[$sku] == 0.00 || empty($new_ticketed_point[$sku])) )
            {
                $error_list[$sku] = 'sku:'.$sku."关联供应商支持开票点，开票点必填";
                continue;
            }

            //根据SKU查找产品数据
            $product_info = $this->product_model->get_product_info($sku);
            if(empty($product_info)){
                $error_list[$sku] = '找不到该SKU:'.$sku.'数据';
                continue;
            }

            if( ($product_info['ticketed_point'] != $new_ticketed_point[$sku]

                    || $product_info['purchase_price'] != $new_supplier_price[$sku]
                    || $product_info['starting_qty'] != $new_starting_qty[$sku]
                    || $product_info['starting_qty_unit'] != $new_starting_qty_unit[$sku]
                    || $product_info['supplier_code'] != $new_supplier_code[$sku]
                    || $product_info['is_purchasing'] != $is_purchasing[$sku]
                    || $product_info['product_cn_link'] != $new_product_link[$sku]
                ) && empty($reason[$sku]))
            {
                $error_list[$sku] = $sku." 请填写修改原因";
                continue;
            }

            $reason_key = NULL;
            if( $product_info['ticketed_point'] != $new_ticketed_point[$sku] ){

                $reason_key .= "ticketed_point,";
            }

            if(  $product_info['purchase_price'] != $new_supplier_price[$sku] )
            {
                $reason_key .="purchase_price,";
            }

            if( $product_info['starting_qty'] != $new_starting_qty[$sku] || $product_info['starting_qty_unit'] != $new_starting_qty_unit[$sku] )
            {
                $reason_key.="starting_qty,";
            }

            if( $product_info['supplier_code'] != $new_supplier_code[$sku] )
            {
                $reason_key.="supplier_code,";
            }
            if( $product_info['product_cn_link'] != $new_product_link[$sku] )
            {
                $reason_key.="product_cn_link,";
            }

            if( $product_info['is_purchasing'] != $is_purchasing[$sku] )
            {
                $reason_key.="is_purchasing,";
            }

            if( $product_info['supply_status'] != $supply_status[$sku]){

                $reason_key .= "source_status";
            }

            if( NULL != $reason_key )
            {
                $reasons = $this->product_model->get_reason_list($reason_key,1);
                $reasons_message = array_column( $reasons,"reason");
                $reason_flag = False;
                if( !is_array($reason[$sku]))
                {
                    $reason[$sku] = explode(",",$reason[$sku]);
                }
                foreach( $reason[$sku] as $reason_value)
                {
                    if( !in_array($reason_value,$reasons_message))
                    {
                        $reason_flag = True;
                        continue;
                    }
                }

                if( $reason_flag == True )
                {
                    $error_list[$sku] = $sku." 修改原因填写错误";
                    continue;
                }
            }

            // 验证供应商是否存在
            $supplier_info = $this->supplier_model->get_supplier_info($new_supplier_code[$sku], false);
            if(empty($supplier_info)){
                $error_list[$sku] = '找不到该供应商:'.$new_supplier_code[$sku].'数据';
                continue;
            }

            /*
               需求：33110 产品管理的导出增加"一级产品线",增加验证SKU与供应商的是否包邮一致 #2
               SKU信息修改变更供应商时,验证:SKU的是否包邮与修改后供应商的是否包邮一致,否则不允许提交修改,
               报错:"SKU****与供应商的是否包邮不一致,请重新确认"
               前端传值 1 表示 包邮  2表示不包邮， 供应商表 1 是,  2 否' ,
            */
            if(trim($new_supplier_code[$sku]) != trim($product_info['supplier_code'])){

                if($supplier_info['is_postage'] == 3){

                    $supplier_info['is_postage'] = 2;
                }
                if($supplier_info['is_postage'] != $is_shipping[$sku]){

                     $error_list[$sku] = $sku."与供应商:".$new_supplier_name[$sku].",是否包邮不一致";
                     continue;
                 }
            }

            // 验证供应商CODE 和供应商名称是否一致

            if( trim($supplier_info['supplier_name']) != trim($new_supplier_name[$sku])){

                $error_list[$sku] = $sku.'供应商名称和供应商CODE不一致';
                continue;
            }

       /*     if($supplier_info['status']==PRE_DISABLE){
                $error_list[$sku] = '供应商:'.$new_supplier_code[$sku].'预禁用';
                continue;
            }*/
            $oldsupplier_info = $this->supplier_model->get_supplier_info($product_info['supplier_code'], false);

            if($oldsupplier_info['supplier_code'] != $new_supplier_code[$sku]){
                // 来源为常规
                if($supplier_info['supplier_source'] == 3 && $oldsupplier_info['supplier_source'] == 1 && $product_info['supply_status'] == PRODUCT_SCREE_APPLY_REASON_SUCCESS){
                    $error_list[$sku] = '原供应商为常规并且SKU 货源状态为正常:'.$new_supplier_code[$sku].'不允许变更供应商';
                    continue;
                }
            }

            // 验证供应商CODE 和名字是否一致

            $newsupplier_info = $this->supplier_model->get_supplier_info($new_supplier_code[$sku], false);

            //根据SKU查找产品修改记录
            $product_log_info = $this->product_update_log->get_product_log_info($sku);
            if(in_array($product_log_info['audit_status'], [PRODUCT_UPDATE_LIST_AUDITED,
                PRODUCT_UPDATE_LIST_QUALITY_AUDIT,
                PRODUCT_UPDATE_LIST_FINANCE,
                PRODUCT_UPDATE_LIST_PURCHASE,
                PRODUCT_EXECUTIVE_DIRECTOR,
                PRODUCT_DEPUTY_MANAGER,
                PRODUCT_DEVELOPMENT,
                PRODUCT_PURCHASING_MANAGER,
                PRODUCT_SUPPLIER_DIRECTOR])){
                $error_list[$sku] = '存在待审核的记录';
                continue;
            }

            // 修改了采购链接或供应商时验证供应商ID是否一致
            if(false and (!isset($is_force_submit) or $is_force_submit != 1)
                and ($new_product_link[$sku] != $product_info['product_cn_link']
                    or $product_info['supplier_code'] != $new_supplier_code[$sku])){// 屏蔽供应商ID不一致
                $productId = $this->aliproductapi->parseProductIdByLink($new_product_link[$sku]);
                if($productId['code'] === false){
                    $error_list[$sku] = $productId['errorMsg'];
                    $is_supplier_abnormal = true;
                    continue;
                }
                $supplierInfo = $this->aliproductapi->getSupplierByProductId($productId['data']);
                if($supplierInfo['code'] === false){
                    $error_list[$sku] = $supplierInfo['errorMsg'];
                    $is_supplier_abnormal = true;
                    continue;
                }else{
                    $loginId = isset($supplierInfo['data']['loginId'])?$supplierInfo['data']['loginId']:'';
                    $supplierName = isset($supplierInfo['data']['supplierName'])?$supplierInfo['data']['supplierName']:'';
                    if(trim($loginId) != trim($supplier_info['shop_id'])){
                        $error_list[$sku] = '供应商店铺ID不一致';
                        $is_supplier_abnormal = true;
                        continue;
                    }
                    if(trim($supplierName) != trim($supplier_info['supplier_name'])){
                        $error_list[$sku] = '供应商名称与店铺名称不一致';
                        $is_supplier_abnormal = true;
                        continue;
                    }
                }
            }
        }

        // 存在错误信息不能提交
        if($error_list){
            if( $import == False) {
                if($is_supplier_abnormal === true){// 供应商异常
                    $this->error_data_json(['error_list' => $error_list,'is_supplier_abnormal' => 1],'供应商店铺ID不一致，请确定是否提交？');
                }else{

                    $result_error_message = "";
                    if( !empty($error_list) )
                    {
                        foreach($error_list as $error_key=>$error_value){

                            $result_error_message.= "sku:".$error_key.":".$error_value." ";
                        }
                    }
                    $this->error_data_json(['error_list' => $error_list],$result_error_message);
                }
            }else{
                return $error_list;
            }
        }

        // 执行更新操作

        // 获取SKU 数据修改审核流程
        $sku_audit_proccess = $this->product_model->getAuditProccess();
        $proccess_orders = json_decode(PRODUCT_ALL_CONTENT_PROCCESS,True);

        foreach ($sku_product as $sku) {
            $all_audit_proccess = [];
            $audit_flag = false;
            $product_info = $this->product_model->get_product_info($sku);

            // 如果业务方修改了未税单价，英文标识 productprice
            if($product_info['purchase_price']!=$new_supplier_price[$sku]){

                // 计算修改比例
                $price_ratio = ($new_supplier_price[$sku]-$product_info['purchase_price'])/$product_info['purchase_price'];

                $price_ratio = round(($price_ratio*100),3);
                $sku_audit_proccess_now = isset($sku_audit_proccess['productprice'])?$sku_audit_proccess['productprice']:NULL;
                $audit_proccess = $this->product_model->getSkuProccess($price_ratio,$sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['productprice'] = $audit_proccess;
                }
            }

            // 修改供应商

            if( $product_info['supplier_code'] !=$new_supplier_code[$sku] ){
                $sku_audit_proccess_now = isset($sku_audit_proccess['suppliername'])?$sku_audit_proccess['suppliername']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['suppliername'] = $audit_proccess;
                }
            }

            // 是否定制修改

            if( $product_info['is_customized'] != $is_customized[$sku]){

                $sku_audit_proccess_now = isset($sku_audit_proccess['is_customized'])?$sku_audit_proccess['is_customized']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['is_customized'] = $audit_proccess;
                }
            }

            // 交期

            if( $product_info['devliy'] != $devliy[$sku]){

                $sku_audit_proccess_now = isset($sku_audit_proccess['devliy'])?$sku_audit_proccess['devliy']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['devliy'] = $audit_proccess;
                }

            }

            // 是否代采修改
            if( $product_info['is_purchasing'] != $is_purchasing[$sku] ){
                $sku_audit_proccess_now = isset($sku_audit_proccess['substitute'])?$sku_audit_proccess['substitute']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['substitute'] = $audit_proccess;
                }

            }
            // 起订量修改
            if( $product_info['starting_qty'] != $new_starting_qty[$sku]) {
                $sku_audit_proccess_now = isset($sku_audit_proccess['minimum'])?$sku_audit_proccess['minimum']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['minimum'] = $audit_proccess;
                }
            }

            // 超长交期 long_devliy
            if( $product_info['long_delivery'] != $long_devliy[$sku]){

                $sku_audit_proccess_now = isset($sku_audit_proccess['long_devliy'])?$sku_audit_proccess['long_devliy']:NULL;
                
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['long_delivery'] = $audit_proccess;
                }
            }

            // 修改开票点
            if($product_info['ticketed_point']!=$new_ticketed_point[$sku]){
                $sku_audit_proccess_now = isset($sku_audit_proccess['unitprice'])?$sku_audit_proccess['unitprice']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['unitprice'] = $audit_proccess;
                }
            }

            // 修改票面
            if( $product_info['coupon_rate'] != $new_product_coupon_rate[$sku])
            {
                $sku_audit_proccess_now = isset($sku_audit_proccess['par'])?$sku_audit_proccess['par']:NULL;

                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['par'] = $audit_proccess;
                }
            }

            // 修改货源状态
            if($product_info['supply_status']!=$supply_status[$sku]) {

                $sku_audit_proccess_now = isset($sku_audit_proccess['supply_status'])?$sku_audit_proccess['supply_status']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['supply_status'] = $audit_proccess;
                }
            }
            // 修改1688 对应关系
            if( $product_info['ali_ratio_own'] !=  $new_ali_ratio_own[$sku] || $product_info['ali_ratio_out'] !=  $new_ali_ratio_out[$sku]){
                $sku_audit_proccess_now = isset($sku_audit_proccess['corresponding'])?$sku_audit_proccess['corresponding']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_audit_proccess_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['corresponding'] = $audit_proccess;
                }
            }

            // 是否包邮审核
            if( $product_info['is_shipping'] != $is_shipping[$sku]){
                $sku_is_shipping_now = isset($sku_audit_proccess['is_shipping'])?$sku_audit_proccess['is_shipping']:NULL;
                $audit_proccess = $this->product_model->getSkuOther($sku_is_shipping_now);
                if(!empty($audit_proccess)){
                    $all_audit_proccess['is_shipping'] = $audit_proccess;
                }
            }
            // 判断是否拿样变更 $product_info['is_sample']!=$is_sample[$sku]
            if( ($product_info['supplier_code'] != $new_supplier_code[$sku] &&  $is_sample[$sku] ==1)
                || ($product_info['is_sample'] == 0 && $is_sample[$sku] ==1) ){

                $all_audit_proccess_key = array_column($proccess_orders,NULL,"nameflag");
                $all_audit_proccess['quality'] = ["quality"];
            }
            $sku_now_proccess = [];
            $sku_audit_role = NULL;
            if(!empty($all_audit_proccess)){

                foreach( $all_audit_proccess as $all_audit_proccess_key=>$all_audit_proccess_value){

                    foreach($all_audit_proccess_value as $all_key=>$all_value){

                        if( !in_array($all_value,$sku_now_proccess)) {
                            $sku_now_proccess[] = $all_value;
                        }
                    }
                }
                $sku_audit_role = $this->product_model->sku_audit_proccess($sku_now_proccess,$proccess_orders);
            }


            if( $product_info['product_cn_link']!=$new_product_link[$sku] ) {
                $this->product_model->set_invalid($sku);
            }

            if( $import == True )
            {
                $new_ticketed_point[$sku] =str_replace("%","",$new_ticketed_point[$sku]);
            }


            if( NULL == $sku_audit_role){

                $audit_status = PRODUCT_UPDATE_LIST_AUDIT_PASS;
            }else{
                $headProcess_flag = $sku_audit_role;
                $headProcess = array_shift($headProcess_flag);
                $audit_status = $headProcess['audit_flag'];
            }
            $params = [
                'sku' => $sku,
                'product_name' => $product_info['product_name'],
                'product_line_name' => $this->product_line->get_product_line_name($product_info['product_line_id']),
                'old_supplier_price' => $product_info['purchase_price'],
                'new_supplier_price' => $new_supplier_price[$sku],
                'old_supplier_code' => $product_info['supplier_code'],
                'new_supplier_code' => $new_supplier_code[$sku],
                'old_supplier_name' => $product_info['supplier_name'],
                'new_supplier_name' => $new_supplier_name[$sku],
                'old_ticketed_point' => $product_info['ticketed_point'],
                'new_ticketed_point' => $new_ticketed_point[$sku],
                'old_product_link' => $product_info['product_cn_link'],
                'new_product_link' => $new_product_link[$sku],
                'old_starting_qty' => $product_info['starting_qty'],
                'old_starting_qty_unit' => $product_info['starting_qty_unit'],
                'new_starting_qty' => intval($new_starting_qty[$sku]),
                'new_starting_qty_unit' => $new_starting_qty_unit[$sku],
                'old_ali_ratio_own' => $product_info['ali_ratio_own'],
                'old_ali_ratio_out' => $product_info['ali_ratio_out'],
                'new_ali_ratio_own' => intval($new_ali_ratio_own[$sku]),
                'new_ali_ratio_out' => intval($new_ali_ratio_out[$sku]),
                'create_user_id' => !empty(getActiveUserId()) ? getActiveUserId() : '',
                'create_user_name' => !empty(getActiveUserName()) ? getActiveUserName() : '',
                'audit_status'=> !empty($audit_status)?$audit_status:PRODUCT_UPDATE_LIST_AUDIT_PASS,
                'create_remark' => $create_remark[$sku],
                'is_sample' => $is_sample[$sku],
                'audit_level'=>json_encode($sku_audit_role),
                'old_coupon_rate' =>$product_info['coupon_rate'],
                'new_coupon_rate' => $new_product_coupon_rate[$sku],
                'old_is_purchasing' => $product_info['is_purchasing'],
                'new_is_purchasing' => $is_purchasing[$sku],
                'reason'            => implode(",",$reason[$sku]),
                'all_sku_proccess'  => json_encode($sku_now_proccess),
                'new_inside_number' => $inside_number[$sku],
                'old_inside_number' => $product_info['inside_number'],
                'new_box_size'      => $box_size[$sku],
                'old_box_size'      => $product_info['box_size'],
                'outer_box_volume'  => $outer_box_volume[$sku],
                'product_volume'    =>$product_volume[$sku],
                'new_is_customized' => $is_customized[$sku],
                'old_is_customized' => $product_info['is_customized'],
                'new_devliy'        => $devliy[$sku],
                'old_devliy'        => $product_info['devliy'],
                'new_long_delivery' => $long_devliy[$sku],
                'old_long_delivery' => $product_info['long_delivery'],
                'is_new_shipping'   => $is_shipping[$sku],
                'is_old_shipping'   => $product_info['is_shipping'],
                'old_supply_status' => $product_info['supply_status'],
                'new_supply_status'  =>$supply_status[$sku]
            ];
            $audit_level = '';

            if( $product_info['maintain_ticketed_point'] ==0 )
            {
                $params['old_ticketed_point'] = NULL;
            }

            if( $new_ticketed_point[$sku] !== 0)
            {
                $params['maintain_ticketed_point'] = 1;
            }else{
                $params['maintain_ticketed_point'] = 0;
            }
            $audit_arr = [
                'audit_status'=>$audit_status,
            ];

            if( $params['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS ) {

                $params['audit_time'] =date('Y-m-d H:i:s');
            }
            if(  $product_info['product_cn_link']==$new_product_link[$sku]
                && bccomp($product_info['ticketed_point'], $new_ticketed_point[$sku], 3) ==0
                && bccomp($product_info['purchase_price'], $new_supplier_price[$sku], 3) ==0
                && $product_info['supplier_code']==$new_supplier_code[$sku]
            )
            {
                $params['push_erp_status'] = 3;
                $params['push_old_purchase_status'] = 3;
            }

            // 涨价时，相关图片必填，至少上传一张图片，最多允许上传5张图片--多个图片链接之间以逗号隔开 ---- start ----
            $total_pics       = 0;
            $related_pictures = '';
            if (isset($data_arr['related_pictures'][$sku]) && !empty($data_arr['related_pictures'][$sku])) {

                // 获取传过来的图片数量
                $related_pictures = $data_arr['related_pictures'][$sku];
                $related_pictures_arr = explode(',', $related_pictures);
                $total_pics           = count($related_pictures_arr);
            }

            if( $import == False) {
                if ($total_pics <= 5) {
                    if ($product_info['purchase_price'] < $new_supplier_price[$sku]) {   // 涨价时，相关图片必填
                        if ($total_pics == 0) {
                            $this->error_json('涨价时，至少上传一张图片');
                        }
                    }

                    $params['related_pictures'] = $related_pictures;
                } else {
                    $this->error_json('最多允许上传5张图片');
                }


                // 保存缩略图
                if (isset($data_arr['related_thumbnails'][$sku]) && !empty($data_arr['related_thumbnails'][$sku])) {
                    $params['related_thumbnails'] = $data_arr['related_thumbnails'][$sku];
                }
            }else{
                $params['related_pictures'] = '';
                $params['related_thumbnails'] = '';
            }
            // 涨价时，相关图片必填，至少上传一张图片，最多允许上传5张图片--多个图片链接之间以逗号隔开 ----- end -----

            if( $audit_status == PRODUCT_UPDATE_LIST_AUDIT_PASS and $product_info['product_cn_link']!=$new_product_link[$sku] ) {
                $this->ali_product_model->remove_relate_ali_sku(null,$sku);// 链接变更后，是否关联1688自动变成否，系统自动取消关联1688
                $this->product_model->update_one($sku,array('product_cn_link' => $new_product_link[$sku]));// 不需审核的直接更新采购链接
            }

            if (empty($product_log_info) or $product_log_info['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS or $product_log_info['audit_status'] == PRODUCT_UPDATE_LIST_REJECT) {
                $params['create_time'] = date('Y-m-d H:i:s');
                $result = $this->product_update_log->save_product_log_info($params);
                if($new_ticketed_point[$sku]==='' || $new_ticketed_point[$sku]=='')
                {
                    $audit_arr['maintain_ticketed_point'] =1;
                }else{
                    $audit_arr['maintain_ticketed_point'] =0;
                }

                if($product_info['supply_status']==$supply_status[$sku]){
                    $this->product_model->update_one($sku, $audit_arr);
                }else{
                    $this->product_model->update_one($sku, $audit_arr);
                }
                $this->purchase_suggest_model->change_purchase_ticketed_type($sku,$audit_arr['maintain_ticketed_point']);
            }else{
                $result = $this->product_update_log->update_product_log_one($sku, $params);
            }
            if ($result) {
                if($params['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS){// 更新最新起订量，单位对应关系
                    $this->product_model->change_min_order_qty($sku,$new_starting_qty[$sku],$new_starting_qty_unit[$sku],$product_info['starting_qty'],$product_info['starting_qty_unit']);
                    $updateData = ['ali_ratio_own' => intval($new_ali_ratio_own[$sku]),'ali_ratio_out' => intval($new_ali_ratio_out[$sku])];
                    if( $product_info['product_cn_link']!=$new_product_link[$sku] ) {

                        $updateData['product_cn_link'] = $new_product_link[$sku];
                    }
                    $this->product_model->update_one($sku, $updateData);
                }
                $data_sku[]=$sku;
            } else {
                $data_error[]=$sku;
                if($import == False) {
                    $this->error_json('保存失败，请稍后再试');
                }else{
                    return array('sku'=>$sku,'message'=>'保存失败，请稍后再试');
                }
            }
            if($audit_status == PRODUCT_UPDATE_LIST_AUDIT_PASS)
            {
                $params['create_time'] = date('Y-m-d H:i:s');
                $logs_message = $this->product_model->get_last_logs_id($sku);
                // 没有配置审核角色
                if( !empty($logs_message))
                {
                    if( (empty($sku_audit_role)) || ($logs_message['new_supplier_price'] == $new_supplier_price[$sku]  || $logs_message['new_ticketed_point'] == $new_ticketed_point[$sku] ) )
                    {
                        $res = $this->product_mod_audit->product_audit($logs_message['id'],1,'自动审核通过',TRUE);
                    }
                }
            }
            $this->product_model->set_sku_status($sku,$audit_status);
            $this->ali_product_model->verify_supplier_equal($sku);// 刷新供应商是否一致
        }


        $msg = '';
        if(!empty($data_sku)){
            $msg .= implode('-', $data_sku).'保存成功--';
        }
        if(!empty($data_error)){
            $msg .= implode('-', $data_error).'保存失败';
        }

        if( $import == False) {
            $this->success_json([], NULL, $msg);
        }else{
            if(empty($data_error))
            {
                return True;
            }
        }
    }





    /**
     * 修改
    /product/product/update_products
     * @author Jaden
     */
    public function update_products(){
        $this->load->helper('user');
        $sku = $this->input->get_post('sku');
        $new_supplier_price =  $this->input->get_post('new_supplier_price');
        $new_ticketed_point =  $this->input->get_post('new_ticketed_point');
        $new_starting_qty =  $this->input->get_post('new_starting_qty');
        $new_starting_qty_unit =  $this->input->get_post('new_starting_qty_unit');
        $new_ali_ratio_own =  $this->input->get_post('new_ali_ratio_own');
        $new_ali_ratio_out =  $this->input->get_post('new_ali_ratio_out');
        $new_supplier_code =  $this->input->get_post('new_supplier_code');
        $new_supplier_name =  $this->input->get_post('new_supplier_name');
        $new_product_link =  $this->input->get_post('new_product_link');
        $is_sample =   $this->input->get_post('is_sample');
        $create_remark = $this->input->get_post('create_remark');

        if(empty($sku))$this->error_json('sku不能为空');
        if(empty($new_supplier_price)) $this->error_json('单价不能为空');
        if(empty($new_supplier_code)) $this->error_json('供应商代码不能为空');
        if(empty($new_supplier_name)) $this->error_json('供应商名称不能为空');
        if(empty($new_product_link)) $this->error_json('采购链接不能为空');
        if($new_ticketed_point == '') $this->error_json('税点不能为空');
        if($is_sample == '') $this->error_json('是否拿样不能为空');

        //根据SKU查找产品数据
        $product_info = $this->product_model->get_product_info($sku);
        if(empty($product_info)){
            $this->error_json('找不到该SKU数据');
        }
        //根据SKU查找产品修改记录
        $product_log_info = $this->product_update_log->get_product_log_info($sku);

        $params = [
            'sku'=>$sku,
            'product_name'=>$product_info['product_name'],
            'product_line_name'=>$this->product_line->get_product_line_name($product_info['product_line_id']),
            'old_supplier_price'=>$product_info['purchase_price'],
            'new_supplier_price'=>$new_supplier_price,
            'old_supplier_code'=>$product_info['supplier_code'],
            'new_supplier_code'=>$new_supplier_code,
            'old_supplier_name'=>$product_info['supplier_name'],
            'new_supplier_name'=>$new_supplier_name,
            'old_ticketed_point'=>$product_info['ticketed_point'],
            'new_ticketed_point'=>$new_ticketed_point,
            'old_product_link'=>$product_info['product_cn_link'],
            'new_product_link'=>$new_product_link,
            'old_starting_qty' => $product_info['starting_qty'],
            'old_starting_qty_unit' => $product_info['starting_qty_unit'],
            'new_starting_qty' => intval($new_starting_qty),
            'new_starting_qty_unit' => $new_starting_qty_unit,
            'old_ali_ratio_own' => $product_info['ali_ratio_own'],
            'new_ali_ratio_own' => intval($new_ali_ratio_own),
            'old_ali_ratio_out' => $product_info['ali_ratio_out'],
            'new_ali_ratio_out' => intval($new_ali_ratio_out),
            'create_user_id'=>!empty(getActiveUserId())?getActiveUserId():'',
            'create_user_name'=>!empty(getActiveUserName())?getActiveUserName():'',
            'create_remark'=>$create_remark,
            'is_sample'=>$is_sample,
        ];
        if(in_array($product_log_info['audit_status'], [PRODUCT_UPDATE_LIST_AUDITED,PRODUCT_UPDATE_LIST_QUALITY_AUDIT,PRODUCT_UPDATE_LIST_FINANCE])){
            $this->error_json('该SKU存在待审核的记录');
        }

        if(empty($product_log_info) or $product_log_info['audit_status']==PRODUCT_UPDATE_LIST_AUDIT_PASS or $product_log_info['audit_status']==PRODUCT_UPDATE_LIST_REJECT){
            $params['create_time'] = date('Y-m-d H:i:s');
            $result = $this->product_update_log->save_product_log_info($params);

            if($product_log_info['audit_status']==PRODUCT_UPDATE_LIST_AUDIT_PASS){// 无需审核的 更新最新起订量
                $this->product_model->change_min_order_qty($sku,$new_starting_qty,$new_starting_qty_unit,$product_info['starting_qty'],$product_info['starting_qty_unit']);
                $this->product_model->update_one($sku, ['ali_ratio_own' => intval($new_ali_ratio_own),'ali_ratio_out' => intval($new_ali_ratio_out)]);
            }

            $this->product_model->update_one($sku,array('audit_status'=>PRODUCT_UPDATE_LIST_AUDITED));
        }else{
            $result = $this->product_update_log->update_product_log_one($sku,$params);
        }
        if($result){
            $this->success_json('保存成功！');
        }else{
            $this->error_json('保存失败，请稍后再试');
        }


    }

    /**
     * 修改货源状态
    /product/product/update_supply_status
     * @author Jaden
     */
    public function update_supply_status(){
        $supply_status =  $this->input->get_post('supply_status');
        $sku =   $this->input->get_post('sku');

        if(empty($sku) or empty($supply_status)){
            $this->error_data_json('参数不能为空');
        }
        //获取旧的货源状态
        $sku_info = $this->product_model->get_product_info($sku, 'supply_status');
        if (empty($sku_info)) {
            $this->error_data_json('sku[' . $sku . ']不存在');
        }

        $result = $this->product_model->update_product_supply_status($sku,$supply_status,$sku_info['supply_status']);
        if($result){
            $this->product_model->update_old_purchase_supplier_status($sku,$supply_status);
            $this->product_model->update_erp_purchase_supplier_status($sku,$supply_status);
            //货源状态发生变化，推入消息队列
            $this->_push_rabbitmq($sku, $sku_info['supply_status'], $supply_status);
            $this->success_json('保存成功！');
        }else{
            $this->error_data_json('保存失败！');
        }
    }

    /**
     * 货源状态发生变化，推入消息队列
     * @param string $sku sku
     * @param int $supply_status_old 变化前的货源状态
     * @param int $supply_status_new 变化后的货源状态
     */
    private function _push_rabbitmq($sku, $supply_status_old, $supply_status_new)
    {
        //推入消息队列
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setExchangeName('PRODUCT_SUPPLY_STATUS_CHANGE_EX_NAME');
        $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
        //构造存入数据
        $push_data = [
            'sku' => $sku,
            'supply_status_old' => $supply_status_old,
            'supply_status_new' => $supply_status_new,
            'update_time' => time()
        ];
        //存入消息队列
        $mq->sendMessage($push_data);
    }

    public function test() {


        $result = $this->product_model->test();
        foreach( $result AS $key=>$value ) {

            if( !empty($value['sku']) ) {

                $sql = " UPDATE pur_product SET supplier_code='".$value['supplier_code']."', supplier_name='".$value['supplier_name']."'";
                $sql .= " WHERE sku='".$value['sku']."' AND supplier_code='' AND supplier_name='';\n\r";
                echo $sql;
            }
        }

    }

    /**
     * @desc 获取sku列表(模糊查询)
     * @author Jeff
     * @Date 2019/7/30 10:59
     * @return
     */
    public function get_sku_list()
    {
        $sku =  $this->input->get_post('sku');

        $sku_list = $this->product_model->get_sku_list($sku);
        if(empty($sku_list)){
            $this->success_json();
        }else{
            $this->success_json($sku_list);
        }
    }

    /**
     * 获取 产品最小起订量变更的日志记录
     * @author Jolon
     * @link product/product/get_min_order_qty_log
     */
    public function get_min_order_qty_log(){
        $sku =  $this->input->get_post('sku');
        if(empty($sku)) $this->error_json('SKU 缺失');
        $results = operatorLogQuery(['id' => $sku,'type' => 'MIN_ORDER_QTY'],'record_number,content,content_detail,operator,operate_time','product_operator_log');
        if(empty($results)){
            $this->error_json('没有找到记录');
        }else{
            $this->success_json($results);
        }
    }

    /**
     * function:获取商品SKU 的下单记录
     **/
    public function get_product_purchase()
    {
        try
        {
            $sku =  $this->input->get_post('sku');
            if(empty($sku)){
                $this->error_json('SKU 缺失');
            }

            // 获取老采购系统的SKU 下单记录
            $result['old_data'] =  $this->product_model->old_purchase_order($sku);
            // 获取新采购系统的SKU 下单记录
            $result['new_data'] =   $this->product_model->new_purchase_order($sku);
            // 获取通途系统的SKU 采购单下单记录
            $result['tongtu_data'] = $this->product_model->get_tongtu_data($sku);
            $this->success_json($result);



        }catch ( Exception $exp )
        {
            $this->error_json('没有找到记录');
        }
    }

    public function get_selectbysku(){

        try{

            $skus = $this->input->get_post('sku');
            $message =  $this->product_model->get_selectbysku($skus);
            $returnData = array(

                'goodsParams' => str_replace("\r\n","<br>",$message)
            );

            $productBrand = $this->product_model->get_product_brand($skus);
            $returnData['productData'] = $productBrand;
            $this->success_json($returnData);

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取开发单价修改记录
     * @author:luxu
     **/
    public function get_product_price_log()
    {
        try{

            $skus = $this->input->get_post('sku');
            if( empty($skus) )
            {
                $this->error_json('缺少SKU参数');
            }

            $offset = $this->input->get_post('offset');
            if( empty($offset) )
            {
                $offset = 1;
            }

            $limit = $this->input->get_post('limit');
            if( empty($limit) )
            {
                $limit = 20;
            }

            $offset = ($offset - 1) * $limit;
            $result = $this->product_model->get_price_log($skus,$offset,$limit);
            $this->success_json($result);
        }catch ( Exception $exp )
        {
            $this->error_json('没有找到记录');
        }
    }

    /**
     * 获取字段列表
     * @author:luxu
     **/

    public function get_product_config()
    {
        try{

            $configs = $this->product_model->get_product_config();
            $this->success_json($configs);
        }catch ( Exception $exp)
        {
            $this->error_json('没有找到记录');
        }
    }

    /**
     * 获取原因列表
     * @author:luxu
     **/

    public function get_product_reason( $return = True)
    {
        try{

            $reasons = $this->product_model->get_product_reason();
            if( !empty($reasons) )
            {
                foreach( $reasons as $key=>$value)
                {
                    if( $value['id']<10)
                    {
                        $reasons[$key]['numbers'] = "0".$value['id'];
                    }else{
                        $reasons[$key]['numbers'] = $value['id'];
                    }
                }
            }
            if( $return == True ) {
                $this->success_json($reasons);
            }else{
                return $reasons;
            }
        }catch ( Exception $exp )
        {
            $this->error_json('没有找到记录');
        }
    }

    /**
     * 添加原因
     * @author:luxu
     **/
    public function add_product_reason()
    {
        try{

            $reasons = $this->input->get_post('reason');
            $reasons = json_decode( $reasons,True);
            if( empty($reasons) )
            {
                $this->error_json("缺少参数");
            }
            foreach( $reasons as $key=>$value)
            {
                $reasons[$key]['sort'] = $key+1;
            }

            $result = $this->product_model->add_product_reason($reasons);
            if( $result )
            {
                $this->success_json();
            }else{
                $this->error_json();
            }
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 添加字段原因
     * @author:luxu
     **/
    public function add_reason()
    {
        try{

            $reasons = $this->input->get_post('reason');
            $reasons = json_decode( $reasons,True);
            if( empty($reasons) )
            {
                $this->error_json("缺少参数");
            }
            $result = $this->product_model->add_reason($reasons);
            $this->success_json();
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取商品SKU的物流属性和商品属性
     * LUXU
     **/
    public function get_attribute()
    {
        try{

            $sku =  $this->input->get_post('sku');
            if( empty($sku))
            {
                $this->error_json("缺少参数");
            }
            // 获取SKU 产品系统的产品属性
            $product_attribute = $this->product_model->get_product_edit_common($sku);
            if( !empty($product_attribute))
            {
                foreach( $product_attribute as $key=>$value)
                {
                    if(!empty($value)) {
                        $product_attribute[$key] = implode(",",$value);
                    }else{
                        $product_attribute[$key] == '';
                    }
                }
            }

            $return_data['product_attribute'] = $product_attribute;
            $product_attributes = $this->product_model->get_product_attribute_data($sku);
            if( !empty($product_attributes))
            {
                foreach( $product_attributes as $key=>$value)
                {
                    $product_attributes[$key]  = implode(",",$value);
                }
            }
            $return_data = $this->product_model->get_parent_data();
            if( !empty($return_data))
            {
                foreach( $return_data['products'] as $key=>$value)
                {
                    if( isset($product_attributes[$key])){
                        $return_data['products'][$key] = $product_attributes[$key];
                    }else{
                        $return_data['products'][$key] = NULL;
                    }
                }
            }
            foreach($return_data['products'] as $product_key=>$product_value){
                if($product_key == "海外属性"){
                    $return_data['products']["订单属性"] = $product_value;
                    unset($return_data['products'][$product_key]);
                }
            }
            $return_data['products']['证书类型'] = $product_attribute['typeData'];
            $return_data['products']['说明书']  = $product_attribute['bookData'];
            $return_data['products']['说明书附件']  = $this->product_model->get_product_link($sku);

            // 获取物流属性
            $result = $this->product_model->get_logistis_attribute($sku);
            $en_abbr = array_keys($result);
            $country = $this->product_model->get_country($en_abbr);
            $logistis_attribute = [];
            if( !empty($result))
            {
                foreach( $result as $key=>$value)
                {
                    if(  isset($country[$key]))
                    {
                        foreach($value as $key_value=>$value_val){
                            $value[$key_value] = implode(",",$value_val);
                        }
                        foreach( $return_data['logistices'] as $log_key=>$log_value)
                        {
                            if( isset($value[$log_key]))
                            {
                                $return_data['logistices'][$log_key] = $value[$log_key];
                            }else{
                                $return_data['logistices'][$log_key]= NULL;
                            }
                        }
                        $logistis_attribute[$country[$key]['cn_name']] = $return_data['logistices'];
                    }
                }
            }

            $return_data['logistis_attribute'] = $logistis_attribute;
            if( isset($return_data['logistices']))
            {
                unset($return_data['logistices']);
            }

            // 获取SKU是否定制
            $isCustomized = $this->product_model->isCustomized($sku);
            if($isCustomized['is_customized'] == 1){

                $return_data['is_customized_ch'] ="是";
            }else{
                $return_data['is_customized_ch'] ="否";
            }
            $this->success_json($return_data);
        }catch ( Exception $exp)
        {
            $this->error_json($exp->getMessage());
        }
    }

    //历史供应商sku
    public function history_product_list()
    {

        $this->load->helper('status_product');
        $this->load->helper('status_supplier');
        $this->load->helper('status_order');

        $params = [
            'sku' => $this->input->get_post('sku'), // SKU
            'product_line_id' =>$this->input->get_post('product_line_id'),
            'supplier_code' => $this->input->get_post('supplier_code'),
            'product_status' => $this->input->get_post('product_status'),
            'supplier_source' => $this->input->get_post('supplier_source'),
            'status' => $this->input->get_post('status'),//是否禁用 1[否] 2[是]
            'product_name'     => $this->input->get_post('product_name'),
            'is_purchasing'   => $this->input->get_post('is_purchasing'), // 是否需要待采购
            'create_time_start'   => $this->input->get_post('create_time_start'), // 创建时间起始
            'create_time_end'   => $this->input->get_post('create_time_end'), // 创建时间起始
            'reason'=>$this->input->get_post('reason')

        ];


        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $field = ' p.product_status, p.product_name,p.product_line_id,p.create_user_name,p.create_time,lo.old_supplier_price,lo.old_starting_qty,lo.old_starting_qty_unit,lo.old_ticketed_point,ps.supplier_source,lo.old_supplier_name,lo.old_supplier_code,lo.old_is_purchasing,lo.reason,lo.audit_time,lo.old_product_link,lo.sku';
        $orders_info = $this->product_model->get_product_list_history($params, $offset, $limit,$field);

        $product_list = $orders_info['value'];


        $orders_info['key'] = array('SKU','产品名称','产品信息','供应商名称','供应商代码','未税单价','最小起订量','最小起订量单位','开票点','是否代采','供应商来源','修改原因','生成时间','采购链接');
        //下拉框
        //产品线
        $product_line_list = $this->product_line->get_product_line_list(0);
        $drop_down_box['product_line_id'] =array_column($product_line_list, 'linelist_cn_name','product_line_id');
        $drop_down_box['status'] = getSupplierDisable();//是否禁用
        $drop_down_box['supplier_source'] = [1=>'常规',2=>'海外',3=>'临时'];
        $drop_down_box['is_purchasing'] =  [1=>'否',2=>'是'];
        $reason_list = $this->product_model->get_product_reason();
        $drop_down_box['reason_list'] = $reason_list;
        $drop_down_box['product_status'] = getProductStatus();//产品状态
        $orders_info['drop_down_box'] = $drop_down_box;
        foreach ($product_list as $key => $value) {

            $orders_info['value'][$key]['supplier_source'] =!empty($drop_down_box['supplier_source'][$value['supplier_source']])?$drop_down_box['supplier_source'][$value['supplier_source']]:'';
            if( $value['old_is_purchasing'] && !empty($value['old_is_purchasing']))
            {
                $orders_info['value'][$key]['old_is_purchasing'] = ( $value['old_is_purchasing']==1)?"否":"是";
            }
            $orders_info['value'][$key]['product_status'] =!empty($drop_down_box['product_status'][$value['product_status']])?$drop_down_box['product_status'][$value['product_status']]:'';

        }

        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        $this->success_json($orders_info);
    }



    /**
     * 产品导出
    /product/product/product_export
     * @author Jaden
     */
    public function history_product_export(){
        ini_set('memory_limit','3000M');
        set_time_limit(0);
        $this->load->helper('export_csv');
        $this->load->helper('status_product');
        $this->load->helper('status_order');
        $ids = $this->input->get_post('ids');
        $supplier_source_arr = [1=>'常规',2=>'海外',3=>'临时'];
        $product_line_id = $this->input->get_post('product_line_id');
        if(!empty($product_line_id)){
            $product_line_ids = $this->product_line_model->get_all_category($product_line_id);
            $product_line_ids = substr($product_line_ids, 0, -1);
        }else{
            $product_line_ids =  $product_line_id;
        }
        if(!empty($ids)){
            $params['ids']   = $ids;
        }else{
            $client_params = [];
            if( !empty($_GET) ) {
                $client_params['product_line_id'] = $product_line_ids;
                foreach( $_GET as $key=>$value ) {
                    if( $key != "product_line_id") {
                        $client_params[$key] = $this->input->get_post($key);
                    }
                }
            }
            $params = [

                'sku' => isset($client_params['sku'])?$client_params['sku']:NULL, // SKU
                'product_line_id' => $product_line_ids,
                'supplier_code' => isset($client_params['supplier_code'])?$client_params['supplier_code']:NULL,
                'product_status' =>  isset($client_params['product_status'])?$client_params['product_status']:NULL,
                'status' => isset($client_params['status'])?$client_params['status']:NULL,//是否禁用 1[否] 2[是]
                'supplier_source' => isset($client_params['supplier_source'])?$client_params['supplier_source']:NULL,
                'product_name' => isset($client_params['product_name'])?$client_params['product_name']:NULL,
                'is_purchasing'   => isset($client_params['is_purchaseing'])?$client_params['is_purchaseing']:NULL, // 是否需要待采购
                'create_time_start'   => isset($client_params['create_time_start'])?$client_params['create_time_start']:NULL, // 创建时间起始
                'create_time_end'   => isset($client_params['create_time_end'])?$client_params['create_time_end']:NULL, // 创建时间结束
                'reason'   => isset($client_params['reason'])?$client_params['reason']:NULL // 创建时间结束





            ];

        }

        $page = $this->input->get_post('offset');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = 1;
        $offset = ($page - 1) * $limit;
        $field = ' p.product_status, p.product_name,p.product_line_id,p.create_user_name,p.create_time,lo.old_supplier_price,lo.old_starting_qty,lo.old_starting_qty_unit,lo.old_ticketed_point,ps.supplier_source,lo.old_supplier_name,lo.old_supplier_code,lo.old_is_purchasing,lo.reason,lo.audit_time,lo.old_product_link,lo.sku';
        // $orders_info = $this->product_model->get_product_list($params, $offset, $limit,$field,True);
        $total =  $this->product_model->get_history_product_sum($params);

        if($total>100000){//单次导出限制
            $template_file = 'product_log_update.xlsx';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$template_file;

            $this->success_json($down_file_url);

        }
        //$total = 1000;
        //前端路径
        $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        $file_name = 'product_log_update.'.time().'.csv';
        $product_file = $webfront_path.'/webfront/download_csv/'.$file_name;


        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $fp = fopen($product_file, "a");
        $heads = array('SKU','产品名称','产品信息','供应商名称','供应商代码','未税单价','最小起订量','最小起订量单位','开票点','是否代采','供应商来源','修改原因','生成时间','采购链接');

        foreach($heads as $key => $item) {
            $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);
        if($total>=1){

            $page_limit = 200;
            $pages =  ceil($total/$page_limit);

            for ($i=1; $i <= $pages ; ++$i) {
                $export_offset = ($i - 1) * $page_limit;
                $orders_export_info = $this->product_model->get_product_list_history($params, $export_offset, $page_limit,$field);
                $product_list = $orders_export_info['value'];




                if($product_list){

                    foreach($product_list as $key=>$v_value){
                        $v_value_tmp                       = [];
                        $v_value_tmp['sku']                = iconv("UTF-8", "GBK//IGNORE",$v_value['sku']);
                        $v_value_tmp['product_name']  = iconv("UTF-8", "GBK//IGNORE",$v_value['product_name']);

                        $product_info='';
                         if (!empty($v_value['category_line_data'][0])) {
                             $line_name_arr =array_column($v_value['category_line_data'],'product_line_name');
                             $line_name_str = implode(',',$line_name_arr);
                             $product_info.='产品线:'.$line_name_str.'\r\n';
                         }
                        $product_info.='开发员:'.$v_value['create_user_name'].'\r\n';
                        $product_info.='创建时间:'.$v_value['audit_time'];
                        $v_value_tmp['product_info']  = iconv("UTF-8", "GBK//IGNORE",$product_info);
                        $v_value_tmp['old_supplier_name']  = iconv("UTF-8", "GBK//IGNORE",$v_value['old_supplier_name']);
                        $v_value_tmp['old_supplier_code']  = iconv("UTF-8", "GBK//IGNORE",$v_value['old_supplier_code']);
                        $v_value_tmp['old_supplier_price']  = iconv("UTF-8", "GBK//IGNORE",$v_value['old_supplier_price']);
                        $v_value_tmp['old_starting_qty']  = iconv("UTF-8", "GBK//IGNORE",$v_value['old_starting_qty']);
                        $v_value_tmp['old_starting_qty_unit']  = iconv("UTF-8", "GBK//IGNORE",$v_value['old_starting_qty_unit']);
                        $v_value_tmp['old_ticketed_point']  = iconv("UTF-8", "GBK//IGNORE",$v_value['old_ticketed_point']);


                        if( !empty($v_value['old_is_purchasing']))
                        {

                            $v_value_tmp['old_is_purchasing']  = ( $v_value['old_is_purchasing']==1)?iconv("UTF-8", "GBK//IGNORE",'否'):iconv("UTF-8", "GBK//IGNORE",'是');

                        } else {

                            $v_value_tmp['old_is_purchasing']  =iconv("UTF-8", "GBK//IGNORE",'未知');

                        }

                        $v_value_tmp['supplier_source']  = empty($supplier_source_arr[$v_value['supplier_source']])?'':iconv("UTF-8", "GBK//IGNORE",$supplier_source_arr[$v_value['supplier_source']]);


                        $v_value_tmp['reason']  = empty($v_value['reason'])?'':iconv("UTF-8", "GBK//IGNORE",$v_value['reason']);
                        $v_value_tmp['audit_time']  = $v_value['audit_time'];
                        $v_value_tmp['old_product_link']  = iconv("UTF-8", "GBK//IGNORE",$v_value['old_product_link']);


                     

                        fputcsv($fp, $v_value_tmp);

                    }
                }

                //每1万条数据就刷新缓冲区
                ob_flush();
                flush();
            }
        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=$down_host.'download_csv/'.$file_name;
        $this->success_json($down_file_url);

    }

    /**
     * 产品列表获取外箱尺寸数据
     * @author:luxu
     * @time:2020/4/13
     **/

    public function getSize(){

        $clientData = $this->product_model->getProductSize();
        $returnData = [];
        if(!empty($clientData['list'])){

            foreach( $clientData['list'] as $key=>&$value){

                $returnData[] =array(
                    'outerbox' => $value['long']."*".$value['wide']."*".$value['high'],
                    'long' => $value['long'],
                    'wide' => $value['wide'],
                    'high' => $value['high']
                );

            }
        }

        $this->success_json($returnData);

    }

    /**
     * 获取外箱尺寸的配置信息
     * @author:luxu
     * @time:2020/4/13
     **/
    public function getProductSize(){

      try{

          $page = $this->input->get_post('offset');
          $limit = $this->input->get_post('limit');
          if (empty($page) or $page < 0){
              $page = 1;
          }
          $offset = ($page - 1) * $limit;
          $clientData = $this->product_model->getProductSize($limit,$offset);
          if(!empty($clientData['list'])){
              foreach($clientData['list'] as $key=>$value){

                  if( $value['status'] == 0){

                      $clientData['list'][$key]['status_ch'] = '是';
                  }else{
                      $clientData['list'][$key]['status_ch'] = '否';
                  }
              }
          }
          $clientData['drop_down_box']['is_status']=[0=>'是',1=>'否'];
          $this->success_json($clientData);
      }catch ( Exception $exp ){

          $this->error_json($exp->getMessage());
      }
    }

    /**
     * 修改外箱尺寸的配置信息
     * @author:luxu
     * @time:2020/4/13
     **/
    public function updateProductSize(){

        try{
            $productSizeData = $this->input->get_post('data');
            if(!empty($productSizeData)){
                $productSizeData = json_decode($productSizeData,True);
            }

            foreach($productSizeData as $key=>&$value){

                $productSizeData[$key]['status'] = isset($value['status_ch'])?$value['status_ch']:$value['status'];
                unset($value['status_ch']);
            }
            $result = $this->product_model->updateProductSize($productSizeData);
            if(True == $result){
                $this->success_json();
            }
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }

    }

    /**
     * @function:商品管理SKU 退税率，修改日志。
     * 采购系统并不修改退税率，数据来自物流系统
     * @author:luxu
     * @time:2020年7月14号
     **/

    public function rateLogs(){

        try{

            $skus = $this->input->get_post('sku');
            if( NULL == $skus || empty($skus)){

                throw new Exception("请传入SKU");
            }

            $result = $this->product_model->rateLogs($skus);
            $this->success_json($result);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * @function:商品管理SKU 开票点修改人在
     * 采购系统是可以修改开票点
     * @author:luxu
     * @time:2020年7月14号
     **/
    public function ticketedPoint(){

        try{

            $skus = $this->input->get_post('sku');
            if( NULL == $skus || empty($skus)){

                throw new Exception("请传入SKU");
            }

            $result = $this->product_model->ticketedPoint($skus);
            $this->success_json($result);

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    private function header(){

        $header = [
            'key1' => ['name'=>'SKU','status'=>0,'index'=>0,'val'=>['sku']], // SKU
            'key2' => ['name' => '产品图片','status'=>0,'index'=>1,'val'=>['product_thumb_url']], // 产品图片
            'key3' => ['name' => '产品名称','status'=>0,'index'=>2,'val'=>['product_name','tax_rate','export_cname','starting_qty_unit']], // 产品名称
            'key4' => ['name' => '票面税率','status'=>0,'index'=>3,'val'=>['coupon_rate']],
            'key5' => ['name' => '产品信息','status'=>0,'index'=>4,'val'=>['product_status','create_user_name','create_time','is_customized_ch']],
            'key6' => ['name'=>'产品线','status'=>0,'index'=>5,'val'=>['category_line_data']],
            'key7' => ['name'=>'最小起订量/1688起订量','status'=>0,'index'=>6,'val'=>['starting_qty','starting_qty_unit','original_start_qty_unit','original_start_qty','starting_qty_number']],

            'key8' => ['name'=>'是否海外仓SKU/是否包邮','status'=>0,'index'=>7,'val'=>['overseas_ch','is_shipping_ch']],
            'key9' => ['name' => '是否可退税','status'=>0,'index'=>8,'val'=>['is_drawback']],
            'key10' => ['name'=>'开发单价','status'=>0,'index'=>9,'val'=>['product_cost']],
            'key11' => ['name'=>'开票点(%)','status'=>0,'index'=>10,'val'=>['ticketed_point']],
            'key12' =>['name'=>'未税单价','status'=>0,'index'=>11,'val'=>['product_cost']],
            'key13' => ['name'=>'交期(天)','status'=>0,'index'=>12,'val'=>['original_devliy','devliy']],
            'key14' => ['name' => '是否超长交期','status'=>0,'index'=>13,'val'=>['long_delivery_ch']],
            'key15' => ['name' => '供应商名称','status'=>0,'index'=>14,'val'=>['supplier_name','supplier_code']],
            'key16' => ['name' => '结算方式','status'=>0,'index'=>15,'val'=>['settlement_method']],
            'key17' => ['name' => '箱内数','status'=>0,'index'=>16,'val'=>['inside_number']],
            'key18' => ['name' => '外箱尺寸(cm)','status'=>0,'index'=>17,'val'=>['box_size']],
            'key19' => ['name' => '体积(cm)','status'=>0,'index'=>18,'val'=>['outer_box_volume','product_volume']],
            'key20' => ['name' => '供应商来源','status'=>0,'index'=>19,'val'=>['supplier_source_ch']],
            'key21' => ['name' => '是否代采','status'=>0,'index'=>20,'val'=>['is_purchasing']],
            'key22' => ['name' => '开发人员/采购人员','status'=>0,'index'=>21,'val'=>['create_user_name','supplier_buyer_user']],
            'key23' => ['name' => '采购连接','status'=>0,'index'=>22,'val'=>['product_cn_link']],
            'key24' => ['name' => '是否1688关联','status'=>0,'index'=>23,'val'=>['is_relate_ali']],
            'key25' => ['name' => '供应商是否一致','status'=>0,'index'=>24,'val'=>['is_equal_sup_id,is_equal_sup_name']],
            'key26' => ['name' => '系统与1688对应关系','status'=>0,'index'=>25,'val'=>['ali_ratio_own','ali_ratio_out']],
            'key27' =>['name' => '审核状态','status'=>0,'index'=>26,'val'=>['audit_status_log_cn']],
            'key28' => ['name'=>'国内仓交付天数','status'=>0,'index'=>28,'val'=>['ds_day_avg']],
            'key29' => ['name'=>'海外仓交付天数','status'=>0,'index'=>29,'val'=>['os_day_avg']],
            'key30' => ['name'=>'10天交付率','status'=>0,'index'=>30,'val'=>['ds_deliverrate']],
            'key31' => ['name'=>'40天交付率','status'=>0,'index'=>31,'val'=>['os_deliverrate']],


        ];
        return $header;
    }

    private function headerlog(){

        $header = [
            'key4' => ['name'=>'SKU','status'=>0,'index'=>0,'val'=>['sku']], // SKU
            'key1' => ['name' => '审核状态','status'=>0,'index'=>1,'val'=>['audit_status','audit_user_name','audit_time','audit_remark']], // 产品图片
            'key2' => ['name' => '申请人/申请时间','status'=>0,'index'=>2,'val'=>['create_user_name','create_time']], // 产品名称
            'key3' => ['name' => '产品图片','status'=>0,'index'=>3,'val'=>['product_img_url','product_img_url_thumbnails','product_thumb_url']],
            'key5' => ['name' => '产品名称','status'=>0,'index'=>4,'val'=>['product_name']],
            'key6' => ['name' => '箱内数','status'=>0,'index'=>5,'val'=>['new_inside_number','old_inside_number']],
            'key7' => ['name'=>'外箱尺寸(cm)','status'=>0,'index'=>6,'val'=>['new_box_size','old_box_size']],
            'key8' => ['name'=>'产品信息','status'=>0,'index'=>7,'val'=>['product_status','product_line_name','create_time']],
            'key9' => ['name' => '系统与1688对应关系','status'=>0,'index'=>8,'val'=>['new_ali_ratio_out','old_ali_ratio_out','new_ali_ratio_own','old_ali_ratio_own']],
            'key10' => ['name'=>'最小起订量','status'=>0,'index'=>9,'val'=>['new_starting_qty','old_starting_qty','new_starting_qty_unit','old_starting_qty_unit']],
            'key11' => ['name'=>'未税单价/价格变化比例','status'=>0,'index'=>10,'val'=>['new_supplier_price','old_supplier_price','cuttheprice']],
            'key12' => ['name' => '是否包邮','status'=>0,'index'=>11,'val'=>['is_new_shipping_ch','is_old_shipping_ch']],
            'key13' => ['name' => '开票点','status'=>0,'index'=>12,'val'=>['new_ticketed_point','old_ticketed_point']],
            'key14' =>['name'=>'票面税率','status'=>0,'index'=>13,'val'=>['new_coupon_rate','old_coupon_rate']],
            'key15' => ['name'=>'供应商名称','status'=>0,'index'=>14,'val'=>['new_supplier_name','old_supplier_name']],
            'key16' => ['name'=>'结算方式','status'=>0,'index'=>15,'val'=>['new_settlement_method','old_settlement_method']],
            'key17' => ['name' =>'连接','status'=>0,'index'=>16,'val'=>['new_product_link','old_product_link']],
            'key18' => ['name' => '是否定制','status'=>0,'index'=>17,'val'=>['new_is_customized_ch','old_is_customized_ch']],
            'key19' => ['name' => '是否拿样','status'=>0,'index'=>18,'val'=>['is_sample']],
            'key20' => ['name' => '是否代采','status'=>0,'index'=>19,'val'=>['new_is_purchasing_ch','old_is_purchasing_ch']],
            'key21' => ['name' => '样品检验结果','status'=>0,'index'=>20,'val'=>['sample_check_result','sample_user_name','sample_time','sample_remark']],
            'key22' => ['name' => '交期(天)','status'=>0,'index'=>21,'val'=>['new_devliy','old_devliy']],
            'key23' => ['name' => '是否超长交期','status'=>0,'index'=>22,'val'=>['new_long_delivery','old_long_delivery']],
            'key24' => ['name' => '货源状态','status'=>0,'index'=>23,'val'=>['reason']],
            'key26' => ['name' => '申请备注','status'=>0,'index'=>24,'val'=>['create_remark']],
            'key25' => ['name'=>'修改原因','status'=>0,'index'=>25,'val'=>['reason']]
        ];
        return $header;
    }

    public function get_headerlog(){
        $uid = $this->input->post_get('uid');
        $flag = $this->input->post_get('flag');
        $this->load->model('Product_mod_audit_model','product_audit_mod');
        $headerData = $this->product_audit_mod->headerlog();
        $searchData = $this->product_model->get_user_search($uid,$flag);
        if(!empty($searchData)){
            $datas = array_column($searchData,'name');
            foreach($headerData as $key=>&$value){

                if(in_array($value['name'],$datas)){

                    $value['status'] =1;
                }
            }
        }

        $this->success_json($headerData);
    }

    /**
     * 產品管理列表頭部信息
     * @author:luxu
     * @time:2021年3月29號
     **/

    public function get_set_table_header(){

        $headerData = $this->header();
        $uid= $this->input->post_get('uid');
        $searchData = $this->product_model->get_user_search($uid);
        if(!empty($searchData)){
                $datas = array_column($searchData,'name');
            foreach($headerData as $key=>&$value){

                if(in_array($value['name'],$datas)){

                    $value['status'] =1;
                }
            }
        }
        $this->success_json($headerData);
    }

    /**
     * 设置产品管理头部信息
     * @author:luxu
     * @time:2021年3月29号
     **/
    public function save_table_list(){
        $req_data = $this->input->post_get('order_initial'); //数组格式
        $type = $this->input->post_get('flag'); //列表类型
        $uid = $this->input->post_get('uid');
        if (empty($type)) $this->error_json('列表类型缺失');
        if (empty($req_data)) $this->error_json('列表数据缺失');
        $req_data = json_decode($req_data,true);
        $res = $this->product_model->save_table_list($req_data, $type,$uid);
        if ($res) {
            $this->success_json([], null, '编辑成功');
        } else {
            $this->error_json('编辑失败');
        }
    }

    /**
     * 34694 在产品管理列表页面，导入优化
     * @author :luxu
     * @time:2021年6月9号
     **/
    public function import_product_data(){

        set_time_limit(0);
        ini_set('memory_limit','1024M');
        include APPPATH.'third_party/PHPExcel/PHPExcel/IOFactory.php';
        //echo APPPATH.'third_party/PHPExcel/PHPExcel/IOFactory.php';die();
        $file_path = $this->input->get_post('packag_file');
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

        $result_list = $out;

        if(!empty($out)){

            foreach($out as $key=>$value){


                if( $key == 0 || $value[0] == '必填')
                {
                    continue;
                }
                $skus_data = $value[0]; // 导入数据的SKU
                if(empty($skus_data))
                {
                    $value[19] = "SKU 必填";
                    $error_data[] = $value;
                    continue;
                }
                $point_ticket = $value[1]; // 开票点
                if( !empty($point_ticket))
                {
                    $point_ticket = str_replace("%","",$point_ticket);

                    if( !($point_ticket>0) || $point_ticket>100)
                    {
//                        $value[19] = "开票点必须大于0并且小于1%";
//                        $error_data[] =$value;
//                        continue;
                    }
                }else if($value[1] === ''){
                    $value[1] = '';
                }

                if( $value[1] !== '')
                {
                    $value[1] = $value[1];
                }

                //未税单价
                $product_price = $value[2];
                if( !empty($product_price) && $product_price !==0)
                {
                    $product_price = (float)$product_price;
                    if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $product_price)) {
                        $value[23] = "未税单价小时有效为只能为两位";
                        $error_data[] = $value;
                        continue;
                    }
                }else if( empty($product_price)){
                    $value[23] = "未税单价必填并且不等于0";
                    $error_data[] = $value;
                    continue;
                }
                // 票面

                $coupon_rate = ($value[3])/100;

                if( !empty($coupon_rate) )
                {
                    $coupon_rate = (float)$coupon_rate;
                    if (!preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $coupon_rate)) {
//                        $value[23] = "票面税率只能是2位小数";
//                        $error_data[] = $value;
//                        continue;
                    }

                    if( !($coupon_rate>=0 && $coupon_rate<=1))
                    {
//                        $value[23] = "票面税率只能大于0 并且小于1%";
//                        $error_data[] = $value;
//                        continue;
                    }

                }


                //最小起订量
                $min_product_number = $value[4];
                if( $min_product_number<0)
                {
                    $value[23] = "最小起订量填写错误";
                    $error_data[] = $value;
                    continue;
                }else if( empty($min_product_number) ){

                    $value[23] = '最小起订量必填并且不等于0';
                    $error_data[] = $value;
                    continue;
                }

                // 最小起订量单位
                $min_product_number_type = $value[5];
//                    if( empty($min_product_number_type) || !in_array($min_product_number_type,['个','件','套','台','把','PCS','只']))
//                    {
//                        if( empty($min_product_number_type)) {
//                            $value[15] = "最小起订量单位必填";
//                        }else {
//                            $value[15] = "最小起订量单位填写错误";
//                        }
//                        $error_data[] = $value;
//                        continue;
//                    }
                // 系统与1688单位对应关系-系统
                if(empty($value[6]))
                {
                   /* $value[23] = "系统与1688单位对应关系-系统";
                    $error_data[] = $value;
                    continue;*/
                }
                $is_relate_ali = (int)$value[6];
                if(  $is_relate_ali<0)
                {
//                    $value[23] = "系统与1688单位对应关系（1688）填写错误";
//                    $error_data[] = $value;
//                    continue;
                }
                if(empty($value[7]))
                {
                    /*$value[23] = "系统与1688单位对应关系-采购系统";
                    $error_data[] = $value;
                    continue;*/
                }

                $is_relate_ali_puchase = (int)$value[7];

                if(  $is_relate_ali_puchase<0)
                {
//                    $value[23] = "系统与1688单位对应关系（采购系统）填写错误";
//                    $error_data[] = $value;
//                    continue;
                }

                $is_sample = $value[8];
                $is_sample_flag = NULL;
                if( empty($is_sample) || !in_array($is_sample,['是','否']))
                {
                    $value[23] = "是否拿样填写错误";
                    $error_data[] = $value;
                    continue;
                }else{

                    if( $is_sample == "是")
                    {
                        $is_sample_flag =1;
                    }else{
                        $is_sample_flag ="0";
                    }
                }
                $product_link = $value[9];
                if( empty($product_link))
                {
                    $value[23] = "采购链接必填";
                    $error_data[] = $value;
                    continue;
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
                $product_supplier = $value[11];
                $supply_status_flag = NULL;
                $encode = mb_detect_encoding($product_supplier, array("ASCII",'UTF-8',"GB2312","GBK",'BIG5'));

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
                $is_customizedData = $value[20];
                if( $is_customizedData == "是")
                {
                    $is_customizedData_ch =1;
                }else{
                    $is_customizedData_ch =2;
                }

                $is_long_delivery = $value[21];

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
                $new_product_link[trim($value[0])] = $product_link;
                $new_is_sample[trim($value[0])] = $is_sample_flag;
                $supply_status[trim($value[0])] = $supply_status_flag;
                $create_remark[trim($value[0])] = $value[13];
                $supplier_name[trim($value[0])] = $value[12];
                $reason[trim($value[0])] = $value[14];
                $inside_number[trim($value[0])] = $value[15];
                $box_size[trim($value[0])] = $value[16];
                $outer_box_volume[trim($value[0])] = $value[17];
                $product_volume[trim($value[0])] = $value[18];
                $new_starting_qty[trim($value[0])] = $min_product_number;
                $new_starting_qty_unit[trim($value[0])] = $min_product_number_type;
                $new_ali_ratio_own[trim($value[0])] = $is_relate_ali;
                $new_ali_ratio_out[trim($value[0])] = $is_relate_ali_puchase;
                $new_coupon_rate[trim($value[0])] = $coupon_rate;
                $new_is_purchasing[trim($value[0])] = $is_purchasing_flag;
                $new_devliy[trim($value[0])] = $value[19];
                $new_iscustomized[trim($value[0])] = $is_customizedData_ch;
                $new_long_delivery_ch[trim($value[0])] = $is_long_delivery_ch;
                $new_is_shipping[trim($value[0])] = $is_shipping_value;

                // 获取商品信息
                $value[0] = trim($value[0]);
                $product_message = $this->product_model->get_product_one(['sku'=>$value[0]]);
                if( $product_message['ticketed_point'] < $value[1] && empty($value[13]))
                {

                }

                if( $product_message['coupon_rate'] < $value[3] && empty($value[13]))
                {

                }

                $supplier_message = $this->product_model->get_supplier_data($value[12],['status'=>[1,6],'supplier_source'=>[1,3]]);
                /**
                 *  需求：37184 产品管理列表SKU导入优化
                    产品管理列表批量导入，SKU状态为“停产找货中、断货”可批量导入供应商状态为备用供应商名下；预禁用供应商可批量导入

                    在产品管理列表页面，对“导入”、“导入（新）”的文件中供应商必填，验证供应商的合作状态，
                    当合作状态=启用,且供应商来源=常规,允许导入。
                    当合作状态=启用，供应商来源=备用，且SKU货源状态=停产找货中、断货，允许导入
                    当合作状态=预禁用，且供应商来源=常规，允许导入
                 * {"1":"正常","2":"停产","3":"断货","10":"停产找货中"}

                    不满足条件则不允许导入,且toast报错提示:第XXX行供应商不符要求，请修改后重新导入，XXX表示具体第几行数据
                 **/
                if($supplier_message['status'] == 1){

                    // 如果供应商合作状$value[23] = "第:".$key."行,供应商不符要求，请修改后重新导入";
                    //                        $error_data[] = $value;
                    //                        continue;态等于启用时, 供应商来源：国内 == 常规,临时 == 备用
                    if( $supplier_message['supplier_source'] != 1 && $supplier_message['supplier_source'] != 3){

                        $value[23] = "第".$key."行,供应商不符要求，请修改后重新导入";
                        $error_data[] = $value;
                        continue;
                    }
                    if( $supplier_message['supplier_source'] == 3 && !in_array($product_message['supply_status'],[10,3])){

                        $value[23] = "第".$key."行,供应商不符要求，请修改后重新导入";
                        $error_data[] = $value;
                        continue;
                    }

                }

                // 如果供应商合作状态 等于 预禁用,并且供应商来源 等于常规
                if($supplier_message['status'] == 6 && $supplier_message['supplier_source']!=1){

                    $value[23] = "第".$key."行,供应商不符要求，请修改后重新导入";
                    $error_data[] = $value;
                    continue;
                }

                if( !empty($value[12]) && $value[12] != $product_message['supplier_name'])
                {

                    if(empty($supplier_message))
                    {
//                        $value[23] = "只允许导入启用状态的常规供应商";
//                        $error_data[] = $value;
//                        continue;
                    }
                    $new_supplier_code[$value[0]] = $supplier_message['supplier_code'];
                    $new_supplier_name[$value[0]] = $supplier_message['supplier_name'];
                }else {
                    $new_supplier_code[$value[0]] = isset($product_message['supplier_code']) ? $product_message['supplier_code'] : NULL;
                    $new_supplier_name[$value[0]] = isset($product_message['supplier_name']) ? $product_message['supplier_name'] : NULL;
                }



                if( !empty($value[12]) && $value[12] != $product_message['supplier_name'] )
                {

                    $oldsupplier_info = $this->supplier_model->get_supplier_info($product_message['supplier_code'],false);
                    if( $supplier_message['supplier_source'] == 3 &&  $oldsupplier_info['supplier_source'] == 1 && $product_message['supply_status'] == PRODUCT_SCREE_APPLY_REASON_SUCCESS)
                    {
                        $value[23] = "供应商不能变更，原因:原供应商为常规并且SKU 货源状态为正常";
                        $error_data[] = $value;
                        continue;
                    }
                }

                $product_log_info = $this->product_update_log->get_product_log_info($value[0]);

                if (in_array($product_log_info['audit_status'], [PRODUCT_UPDATE_LIST_AUDITED, PRODUCT_UPDATE_LIST_QUALITY_AUDIT, PRODUCT_UPDATE_LIST_FINANCE])) {

                    $value[23] = "SKU:".$value[0].",存在待审核记录";
                    $error_data[] = $value;
                    continue;
                }

                if( $value[2] > $product_message['purchase_price'] && empty($value[13]))
                {
                    $value[23] = "SKU:".$value[0].",未税单价上涨，请填写备注";
                    $error_data[] = $value;
                    continue;
                }

                if( (!empty($value[13]) && mb_strlen($value[13],'UTF8')>50))
                {
                    $value[23] = "SKU:".$value[0].",备注长度不能超过50个字";
                    $error_data[] = $value;
                    continue;
                }

            }

            if(!empty($error_data)){


                $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
                $file_name = 'product.'.time().'.csv';
                $product_file = $webfront_path.'/webfront/download_csv/'.$file_name;
                if (file_exists($product_file)) {
                    unlink($product_file);
                }
                fopen($product_file,'w');
                $fp = fopen($product_file, "a");
                $heads = $result_list[0];
                array_push($heads,"错误提示");

                foreach($heads as $key => $item) {
                    $title[$key] =iconv("UTF-8", "GBK//IGNORE", $item);
                }
                //将标题写到标准输出中
                fputcsv($fp, $title);
                $datas = array_merge( $error_data,$verify_success_data);
                $verify_data = [];
                foreach($datas as $error_key=>$error_value)
                {
                    $error_value[0] = trim($error_value[0]);
                    if( in_array($error_value[0],$verify_data))
                    {
                        continue;
                    }
                    foreach($error_value as $err_key=>$err_value)
                    {
                        if( $err_key == 1)
                        {
                            $error_value[$err_key] = $err_value;
                        }
                        if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', $err_value)) {
                            $error_value[$err_key] = iconv("UTF-8", "GBK//IGNORE",$err_value );
                        }
                    }
                    fputcsv($fp,$error_value);
                    $verify_data[] = $error_value[0];

                }
                ob_flush();
                flush();
                $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
                $down_file_url=$down_host.'download_csv/'.$file_name;
                $return_data = array(

                    'error_message' => "共有".count($error_data)."条数据错误，请确认是否下载",
                    'error_list' =>$down_file_url
                );
                $this->error_json($return_data);

            }else{
                // 推送数据到SWOOLE，$file_path pur_product_import_data
                $add =[

                    'file_path' => $file_path,
                    'uid' =>getActiveUserName(),
                    'user_id' =>getActiveUserId()
                ];
                $insertDatas = $this->product_model->purchase_db->insert('product_import_data',$add);
                if($insertDatas){
                    $this->load->model('system/Data_center_model');
                    $lastId = $this->product_model->purchase_db->insert_id("product_import_data");
                    $this->Data_center_model->push_product_data(['id'=>$lastId]);
                    //->push_product_data()
                }

                $this->success_json();
            }
        }
    }

}