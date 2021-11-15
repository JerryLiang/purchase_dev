<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Product_api extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('arrival_record_model');
        $this->load->model('product/Product_model');
        $this->load->model('product/Product_line_model');
        $this->load->model('product/Product_mod_audit_model');
        $this->load->model('supplier_joint_model');
        $this->load->model('product_line_model','product_line',false,'product');
        $this->load->helper('common');
    }

    public function testa(){

        $this->supplier_joint_model->pushPredictTimeStatus();

    }
    
   
    /**
     * 根据sku获取相关信息（采购系统端）
     * @author Jolon
     */
    public function get_sku_detail_page(){

        $data_role = getRolexiao();
        $role_name = get_user_role();//当前登录角色

        $res_xiao = array_intersect($role_name, $data_role);
        $sku = $this->input->get_post('sku');
        $debug = $this->input->get_post('debug');

         if(!empty($sku)){
             $param['sku']  = $sku;
             $header        = array('Content-Type: application/json');
             $access_taken  = getOASystemAccessToken();
             $url           = getConfigItemByName('api_config','java_system_erp','yibaiProduct-getSkuInfoBySku');
             $url           = $url."?access_token=".$access_taken;
             //$newurl = 'http://rest.test.java.yibainetworklocal.com/product/yibaiProdSku/getSkuInfo?access_token=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJyZWFkIl0sImV4cCI6MTYxNzE1MzQwMywiYXV0aG9yaXRpZXMiOlsiMTAiXSwianRpIjoiNmVlZjcyYWYtN2I2Yy00NWI5LThhZGQtZGNhNmMzMmQyZDA2IiwiY2xpZW50X2lkIjoidGVzdCJ9.Uk9HeCQCRc30gWOZUhf0c-v0fxd0YAmuM12SSfJByyM';
             //yibaiProduct/getSkuInfoBySku
             $newurl =  getConfigItemByName('api_config','java_system_erp','new_yibaiProduct-getSkuInfoBySku');
             $newurl = $newurl."?access_token=".$access_taken;
             $result        = getCurlData($url,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
             $newresult = getCurlData($newurl,json_encode($param,JSON_UNESCAPED_UNICODE),'post',$header);
             if($debug){
                 print_r($result);exit;
             }
             if(is_json($result)){
                 $result = json_decode($result, true);
                 $newresult = json_decode($newresult,true);
                 $data   = ($result['code'] == 200)?$result['data']:[];
                 if(empty($data)){
                     exit(json_encode(['status' => 0, 'errorMess' => '未获取到产品详细信息']));
                 }

                 $appendix_materials          = '';
                 $product_development_picture = isset($data['uploadimgs']) ? $data['uploadimgs'] : '';
                 $product_development_picture = is_json($product_development_picture)?json_decode($product_development_picture,true):$product_development_picture;
                 //加上http:// 防止浏览器自动加上的其他的前缀
                 if(!empty($product_development_picture) && is_array($product_development_picture)) {
                     foreach ($product_development_picture as &$val) {
                         if (stripos($val, 'http') === false) {
                             $val = sprintf('http://%s', $val);
                         }
                     }
                 }
                 $sku_info          = $this->Product_model->get_product_info($data['sku']);
                 $product_line_list = $this->Product_line_model->get_all_parent_category(isset($data['productLinelistId'])?$data['productLinelistId']:0);
                 $product_line_list = array_column($product_line_list,'product_line_name','product_line_id');

                 $buySampleType = ['0' => '购买', '1' => '免费','2' => '借用'];

                 $data_tmp                                = [];

                 // 商品基础资料
                 $data_tmp['sku']                         = isset($data['sku']) ? $data['sku'] : '';// 商品sku
                 $data_tmp['product_name']                = isset($data['productName']) ? $data['productName'] : '';// 商品名称
                 $data_tmp['product_line_name_list']      = isset($data['pathName']) ? $data['pathName'] : '';// 产品线
                 $data_tmp['buyer_type']                  = isset($data['buySampleType']) ? (isset($buySampleType[$data['buySampleType']])?$buySampleType[$data['buySampleType']]:'')  : '';// 购买类型

                 // 商品基础资料 - 产品类型
                 $data_tmp['is_buyer_sample']             = '';// 是否买样
                 $data_tmp['is_custom_made']              = isset($data['isBoutique']) ? (($data['isBoutique']==1)?'是':'否') : '';// 是否定制
                 $data_tmp['sample_price']                = '';// 采样价（￥）

                 // 商品基础资料 - 供应商资料
                 $data_tmp['minimum_order_quantity']      = isset($data['minPurchase']) ? $data['minPurchase'] : '';// 最小起定量
                 $data_tmp['suggested_retail_price']      = isset($data['referencePrice']) ? $data['referencePrice'] : '';// 建议售价（$）
                 if ($res_xiao) {
                     $data_tmp['supplier_code']               =  "****";// 供应商编码
                     $data_tmp['supplier_name']               = "****";// 供应商名称
                 }else {

                     $data_tmp['supplier_code'] = isset($data['providerCode']) ? $data['providerCode'] : '';// 供应商编码
                     $data_tmp['supplier_name'] = isset($data['provideName']) ? $data['provideName'] : '';// 供应商名称
                 }
                 $data_tmp['buy_sampling_link']           = isset($data['productCnLink']) ? $data['productCnLink'] : '';// 采样链接
                 $data_tmp['sampling_link']               = '';// 来样链接

                 // 商品基础资料 - 其他信息
                 $data_tmp['sample_type_package']         = isset($data['packName']) ? $data['packName'] : '';// 来样包装类型
                 $data_tmp['sample_packing_weight']       = isset($data['grossProductWeight']) ? $data['grossProductWeight'] : '';// 样品包装重量
                 $data_tmp['sample_packing_size']         = (isset($data['packProductLength']) && isset($data['packProductWidth']) && isset($data['packProductHeight'])) ? ($data['packProductLength'] .'*'.$data['packProductWidth']."*".$data['packProductHeight']):'';// 样品包装尺寸
                 $data_tmp['sample_goods_net_weight']     = isset($data['productWeight'])?$data['productWeight']:'';// 商品来样净重
                 $data_tmp['sample_remark']               = isset($data['buycompNote']) ? $data['buycompNote'] : '';// 样品备注
                 $data_tmp['packing_list']                = isset($data['packingList']) ? $data['packingList'] : '';// 包装清单

                 // 侵权排查资料
                 $data_tmp['brand_attributes']            = isset($data['bandName']) ? $data['bandName'] : '';// 品牌属性
                 $data_tmp['product_model']               = isset($data['productModel']) ? $data['productModel'] : '';// 产品型号
                 $data_tmp['product_material']            = isset($data['materialCn']) ? $data['materialCn'] : '';// 产品材质
                 $data_tmp['product_usage']               = isset($data['useCn']) ? $data['useCn'] : '';// 产品用途
                 $data_tmp['principle_product']           = isset($data['productPrinciple']) ? $data['productPrinciple'] : '';// 产品原理
                 $data_tmp['whether_price']               = '';// 是否限价
                 $data_tmp['whether_price_list']          = '';// 限价列表
                 $data_tmp['whether_tort']                = isset($data['name']) ? $data['name']?'是':'否' : '';// 是否侵权
                 $data_tmp['whether_tort_list']           = isset($data['name']) ? $data['name'] : '';// 侵权名称列表
                 $data_tmp['whether_ban']                 = '';// 是否禁售
                 $data_tmp['whether_ban_list']            = '';// 禁售列表
                 $data_tmp['whether_authorized']          = '';// 是否授权

                 $data_tmp['appendix_materials']          = $appendix_materials;// 商品附件资料
                 $data_tmp['product_development_picture'] = $product_development_picture;// 商品开发图片

                 // 商品属性资料
                 $data_tmp['basic_attribute']             = '';// 基础属性
                 $data_tmp['special_attribute']           = '';// 特殊属性
                 $data_tmp['battery_attribute']           = '';// 电池属性
                 $data_tmp['prohibited_attributes']       = '';// 违禁属性
                 $data_tmp['morphological_properties']    = '';// 形态属性
                 $data_tmp['overseas_property']           = '';// 海外属性
                 $data_tmp['certificate_type']            = '';// 证书类型
                 $data_tmp['specification']               = isset($data['specification']) ? $data['specification'] : '';// 说明书
                 $data_tmp['skuRelation']                 = isset($data['cSku'])?$data['cSku']:''; // SKU 关联
                 $data_tmp['quality_inspection_notes']    = isset($data['qualityRemark'])?$data['qualityRemark']:'';
                 $data_tmp['special_packing'] = isset($newresult['data']['specialPack'])?$newresult['data']['specialPack']:''; // 特殊包装类型

                 exit(json_encode(['status' => 1, 'data_list' => $data_tmp, 'errorMess' => ''],JSON_UNESCAPED_UNICODE));
             }else{
                 exit(json_encode(['status' => 0, 'errorMess' => '返回的数据格式错误[JAVA服务层出错]']));
             }
         }else{
             exit(json_encode(['status' => 1, 'errorMess' => 'sku 缺失']));
         }
    }


    /**
     * 推送最新采购价，平均运费成本， 加权平均价到产品系统
     * @author Jolon
     */
    public function plan_push_update_sku_price(){
        try{
            $result = $this->Product_model->plan_push_update_sku_price('GS00009');
            if(empty($result['code'])){
                $this->error_json($result['message']);
            }

            $this->success_json();
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }
    }


    /**
     * 标记sku是否异常
     * @author Jaden
     * product_api/sign_product_sku_abnormal
     */
    public function sign_product_sku_abnormal(){
        $where = "( (supplier_name!='' AND supplier_code='')  OR (supplier_code=supplier_name) OR (product_status=4 AND supplier_code='') )";
        $pro_result = $this->db->where($where)->update('product', array('is_abnormal'=>2));
        if($pro_result){
            var_dump('OK');
        }
    }


    /**
     * 定时任务关联1688
        product_api/relation_ali_sku_list
     */
    public function relation_ali_sku_list(){
        set_time_limit(0);
        $this->load->library('alibaba/AliProductApi');
        $this->load->model('ali/Ali_product_model');
        $where = "is_relate_ali=0 AND product_status!=7 AND product_cn_link!='' AND sku!=''";
                   
        $product_list = $this->db->select('product_cn_link,sku')
                ->from('product')
                ->limit(20)
                ->where($where)
                ->order_by('create_time asc')
                ->get()
                ->result_array();
        $success_list = array();
        $failure_list = array();
        if(!empty($product_list)){
            foreach ($product_list as $key => $value) {
                if(strlen($value['product_cn_link'])<15){
                    $failure_list[] = $value['sku'];
                }
                //根据链接获取productId
                $productId_arr = $this->aliproductapi->parseProductIdByLink($value['product_cn_link']);
                if(empty($productId_arr['code']) AND !empty($productId_arr['errorMsg'])){
                    $failure_list[] = $value['sku'];
                }else{
                    $productId = $productId_arr['data'];
                    $ali_product = $this->Ali_product_model->_parse_product($productId);
                    //1688SKU信息
                    if(!empty($ali_product['data']['ali_product']) AND isset($ali_product['data']['ali_product']['skuAttributeList'])){
                        $skuAttributeList = $ali_product['data']['ali_product']['skuAttributeList'];
                        //判断是否是单属性
                        $skuAttributeList_num = count($skuAttributeList);
                        if($skuAttributeList_num==1){
                            $productIds = [
                                $value['sku']=>$value['product_cn_link']
                            ];
                            $specIds = [
                                $value['sku']=>$skuAttributeList[0]['specId']
                            ];
                            $skuIds = [
                                $value['sku']=>$skuAttributeList[0]['skuId']
                            ];
                            $result = $this->Ali_product_model->relate_ali_sku($productIds,$skuIds,$specIds);
                            if($result['code']){
                                $this->db->where('sku',$value['sku'])->update('product',['relate_ali_name' => 'system']);
                                $success_list[] = $value['sku'];    
                            }
                        }else{
                            $failure_list[] = $value['sku'];   
                        }
                    }
                }   
            }
            if(!empty($failure_list)){
                $this->db->where_in('sku',$failure_list)->update('product',['is_relate_ali' => -1]);    
            }
            echo '<pre>';
            print_r($success_list);
            print_r($failure_list);
        }
          
    }

    /**
      * 物流系统SKU 是否商检历史数据和SKU 变动数据推送到采购系统
     **/
    public function product_check() {

        $scree_list = json_decode(file_get_contents('php://input'), true);
        $success_list = $error_list = array();
        if( empty($scree_list) ) {

            $this->error_json('参数缺失');
        }

        $error_str = "采购系统修改失败";
        foreach( $scree_list as $key=>$value ) {

            $product_skus = $this->db->from("product")->where("sku",$value['sku'])->select("id")->get()->row_array();
            if( empty($product_skus) ) {
                $error_str = "采购系统为查询到相关SKU";
                $error_list[] = $value['sku'];
                continue;
            }

            $update = array('is_inspection'=>$value['is_check']);
            $result = $this->db->where("sku",$value['sku'])->update("product",$update);
            if( $result ) {
                $success_list[] = $value['sku'];
            }else{
                $error_list[] = $value['sku'];
            }
        }
        header('Content-type: application/json');

        if( count($scree_list) == count($success_list) ) {

            echo json_encode(['status'=>0,'message'=>'修改成功','success_list'=>$success_list],JSON_UNESCAPED_UNICODE);
        }else{

            echo json_encode(['status'=>1,'message'=>$error_str,'error_list'=>$error_list,'success_list'=>$success_list],JSON_UNESCAPED_UNICODE);
        }

        //$this->success_json();
    }

    /**
      * 接受JAVA 数据中心推送的价格修改日志
     **/
    public function accept_sku_price_log()
    {
            $logs_data = json_decode(file_get_contents('php://input'), true);
            $api_log = [
                'record_number' => 0,
                'api_url' => '/product/accept_sku_price_log',
                'record_type' => '产品信息修改推送到老系统',
                'post_content' => json_encode($logs_data),
                'response_content' => json_encode($logs_data),
                'create_time' => date('Y-m-d H:i:s')
            ];
            $this->db->insert('api_request_log', $api_log);
            $error_data = $success_data = array();
            // 获取SKU 数据修改审核流程
            $sku_audit_proccess = $this->product_model->getAuditProccess();
            $proccess_orders = json_decode(PRODUCT_ALL_CONTENT_PROCCESS,True);
            foreach($logs_data as $key => $logs) {
                if (!empty($logs) && isset($logs['sku_log_id']) && isset($logs['sku'])) {
                    // 如果JAVA 数据中心推送的数据不为空
                    $logs_message = $this->db->from("product_update_log")->where("id", $logs['sku_log_id'])->select("new_supplier_price,old_supplier_price")->get()->row_array();
                    if (!empty($logs_message)) {


                        if($logs_message['new_supplier_price'] != $logs_message['old_supplier_price']){

                            // 如果业务方修改了未税单价，英文标识 productprice


                            // 计算修改比例
                            $price_ratio = ($logs_message['new_supplier_price']-$logs_message['old_supplier_price'])/$logs_message['old_supplier_price'];
                            $price_ratio = round(($price_ratio*100),3);

                            $sku_audit_proccess_now = isset($sku_audit_proccess['productprice'])?$sku_audit_proccess['productprice']:NULL;
                            $audit_proccess = $this->product_model->getSkuProccess($price_ratio,$sku_audit_proccess_now);
                            if(!empty($audit_proccess)){
                                $all_audit_proccess['productprice'] = $audit_proccess;
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
                                    if( NULL == $sku_audit_role){

                                        $audit_status = PRODUCT_UPDATE_LIST_AUDIT_PASS;
                                    }else{
                                        $headProcess_flag = $sku_audit_role;
                                        $headProcess = array_shift($headProcess_flag);
                                        $audit_status = $headProcess['audit_flag'];
                                    }


                                    $update = array(

                                        'all_sku_proccess'  => json_encode($sku_now_proccess),
                                        'audit_level'=>json_encode($sku_audit_role),
                                        'audit_status' => $audit_status
                                    );
                                    $result = $this->db->where("id", $logs['sku_log_id'])->update('product_update_log', $update);
                                    
                                    if ($result) {
                                        $success_data[] = $logs;
                                    } else {
                                        $error_data[] = $logs;
                                    }


                                }

                            }
                        }
                    }else{
                        $error_data[] = $logs;
                    }
                } else {
                    $error_data[] = $logs;
                }
            }

            if( empty($error_data))
            {
                echo json_encode(['status'=>1,'message'=>'审核流程创建成功','error_list'=>$error_data],JSON_UNESCAPED_UNICODE);
            }else{
                echo json_encode(['status'=>0,'message'=>'审核流程创建失败','error_list'=>$error_data],JSON_UNESCAPED_UNICODE);
            }


    }

    public function send_http( $url, $data = NULL ) {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!$data){
            return 'data is null';
        }
        if(is_array($data))
        {
            $data = json_encode($data);
        }

        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
    }

    public function get_sku_inspection() {
        $start_time = isset($_POST['start_time'])?$_POST['start_time']:"2016-01-01";
        $end_time = isset($_POST['end_time'])?$_POST['end_time']:"2018-12-31";
        $token = getOASystemAccessToken();
        $total = 695534;
        $limit =5000;
        $page = ceil($total/$limit);
        $url = "http://rest.java.yibainetwork.com/logistics/logisticsAttr/getCommodifyBySku?access_token=".$token;
        //$url = "http://rest.dev.java.yibainetworklocal.com/logistics/logisticsAttr/getCommodifyBySku?access_token=".$token;
        $num=0;
        for( $i=1;$i<=$page;++$i) {
            //{"0":"审核不通过","1":"刚开发","2":"编辑中","3":"预上线","4":"在售中","5":"已滞销","6":"待清仓","7":"已停售","8":"待买样","9":"待品检","10":"拍摄中","11":"产品信息确认","12":"修图中","14":"设计审核中","15":"文案审核中","16":"文案主管终审中","17":"试卖编辑中","18":"试卖在售中","19":"试卖文案终审中","20":"预上线拍摄中","21":"物流审核中","22":"缺货中","27":"作图审核中","28":"关务审核中","29":"开发检查中","30":"拍摄中、编辑中","31":"拍摄中、编辑中","32":"已编辑，拍摄中","33":"编辑中，已拍摄","100":"未知"}
            $sql = " SELECT distinct sku FROM pur_product WHERE audit_status IN (0,1,2,3)  AND  is_inspection=0  AND  product_status>0 AND is_pull_logis=0 AND create_time>'".$start_time."' AND create_time<'".$end_time."' ORDER BY id DESC LIMIT ".($i-1)*$limit.",".$limit;

            $result = $this->db->query($sql)->result_array();

            if( !empty($result)) {
                $skus = implode(",", array_column($result, "sku"));
                $send['sku'] = $skus;
                $res = $this->send_http($url, $send);
                $res = json_decode($res, True);
                if ( isset($res['code']) && $res['code'] == 0) {

                    foreach ($res['data'] as $k => $v) {
                        ++$num;
                        $update['is_inspection'] = $v['isCommodifyInspection'];
                        $update['sku'] = $v['sku'];
                        $update['is_pull_logis '] =1;
                        $batchUpdate[] = $update;

                        // usleep(50000);
                    }
                    if( !empty($batchUpdate) ) {
                        $this->db->update_batch('product', $batchUpdate, 'sku');

                    }
                }else{

                    print_r($res);
                }
            }else{
                echo "213213";
            }

            usleep(50000);

        }
        echo $num;
    }

    /**
      * function:对比新老采购系统
     **/
    public function set_new_product_data(){

       $oldDb=$this->load->database('oldbase',TRUE);

       $sql = " SELECT sku FROM pur_product WHERE (create_time LIKE '2019%' OR create_time LIKE '2018%' OR create_time LIKE '2017%') AND supplier_code='' AND supplier_name='' AND sku  NOT IN ( SELECT sku FROM pur_product_update_log GROUP BY sku) ";
       $new_product_new = $this->db->query($sql)->result_array();

       if( !empty($new_product_new) ) {
           $skus = array_map(function($sku) {
               return sprintf("'%s'",$sku['sku']);
           },$new_product_new);
           $old_sql = "  SELECT 
                A.sku,B.`suppliercode`,B.`supplierprice`,supplier.supplier_name
                FROM `pur_product_supplier` AS A
                LEFT JOIN `pur_supplier_quotes` AS B ON A.`quotes_id`=B.`id`
                LEFT JOIN pur_supplier AS supplier ON A.supplier_code = supplier.supplier_code 
                WHERE A.`is_supplier`=1 AND A.sku IN (".implode(",",$skus).")";
           $oldskus = $oldDb->query($old_sql)->result_array();
           if( !empty($oldskus) ) {
                $i=0;
               foreach( $oldskus as $key=>$value ) {

                   $flag = $this->db->where("sku",$value['sku'])->where("supplier_code",'')->update('pur_product',['supplier_code'=>$value['suppliercode'],'supplier_name'=>$value['supplier_name']]);
                   if( $flag ) {
                       ++$i;
                       operatorLogInsert(
                           [
                               'type'    => 'product_api',
                               'content' => '定时脚本恢复',
                               'detail'  => json_encode($value)
                           ]);
                   }
               }

               echo $i;
           }
       }

    }

    public function set_new_product_price(){

        $oldDb=$this->load->database('oldbase',TRUE);

        $sql = " SELECT sku FROM pur_product WHERE (create_time LIKE '2019%' OR create_time LIKE '2018%' OR create_time LIKE '2017%') AND purchase_price=0 AND sku  NOT IN ( SELECT sku FROM pur_product_update_log GROUP BY sku) ";

        $new_product_new = $this->db->query($sql)->result_array();

        if( !empty($new_product_new) ) {
            $skus = array_map(function($sku) {
                return sprintf("'%s'",$sku['sku']);
            },$new_product_new);
            $old_sql = "  SELECT 
                A.sku,B.`suppliercode`,B.`supplierprice`
                FROM `pur_product_supplier` AS A
                LEFT JOIN `pur_supplier_quotes` AS B ON A.`quotes_id`=B.`id`
              
                WHERE A.`is_supplier`=1 AND A.sku IN (".implode(",",$skus).") AND B.supplierprice!=0";

            $oldskus = $oldDb->query($old_sql)->result_array();
            if( !empty($oldskus) ) {
                $i=0;
                foreach( $oldskus as $key=>$value ) {

                    $flag = $this->db->where("sku",$value['sku'])->where("purchase_price",0)->update('pur_product',['purchase_price'=>$value['supplierprice']]);
                    if( $flag ) {
                        ++$i;
                        operatorLogInsert(
                            [
                                'type'    => 'product_api',
                                'content' => '定时脚本恢复金额',
                                'detail'  => json_encode($value)
                            ]);
                    }
                }

                echo $i;
            }
        }

    }

    public function send_http_json( $url, $data = NULL ) {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!$data){
            return 'data is null';
        }
        if(is_array($data))
        {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
    }


    private function setProductIsMulti( $key,$skus,$update_data ) {

        if( in_array($key,['multiattribute','multiordinary','multiSingle']) ) {

            $this->db->where_in("sku",$skus)->update("product",['is_multi'=>$update_data,'is_from_multi'=>1]);
        }

        if( in_array($key,['productordinary','productbinding'])){

            $this->db->where_in("sku",$skus)->update("product",['product_type'=>$update_data,'is_from_multi'=>1]);
        }
    }



    /**
     * 根据SKU查询是否多属性
     **/

    public function getProductIsMulti()
    {
        $sql = " SELECT COUNT(id) AS total FROM pur_product WHERE product_status>0 AND is_from_multi IS NULL";
        $total = $this->db->query($sql)->row_array();
        if( $total['total']>0 ) {
            $this->load->config('api_config', FALSE, TRUE);
            $send_url = $this->config->item('java_system_product_url')['java_url'];
            $send_url .="?access_token=".getOASystemAccessToken();
            $limit = 10;
            $page = ceil( $total['total']/$limit);
            for( $i=1;$i<$page;++$i) {
                $searchSql = " SELECT sku FROM pur_product WHERE product_status>0 AND is_from_multi IS NULL LIMIT " . ($i - 1) * $limit . "," . $limit;
                $skusData = $this->db->query($searchSql)->result_array();
                if( !empty($skusData) ) {

                    $skus = array_column( $skusData,"sku");
                    $send_skus = json_encode(['sku'=>$skus]);
                    $res = $this->send_http_json($send_url, $send_skus);

                    if( !empty($res) ) {

                        $result = json_decode($res,True);
                        if( isset($result['code']) && $result['code'] == 200 ) {

                            $multi_data = array();
                            foreach( $result['data'] AS $key=>$value ) {

                                if( $value['productIsMulti'] == 2 ) {

                                    $multi_data['multiattribute']['skus'][] = $value['sku'];
                                    $multi_data['multiattribute']['data'] = 2;
                                }

                                if( $value['productIsMulti'] == 0 ) {

                                    $multi_data['multiordinary']['skus'][] = $value['sku'];
                                    $multi_data['multiordinary']['data']=0;
                                }

                                if( $value['productIsMulti']  == 1){

                                    $multi_data['multiSingle']['skus'][] = $value['sku'];
                                    $multi_data['multiSingle']['data'] =1;
                                }

                                if( $value['productType'] == 1) {

                                    $multi_data['productordinary']['skus'][] = $value['sku'];
                                    $multi_data['productordinary']['data']=1;
                                }
                                if( $value['productType'] == 2) {

                                    $multi_data['productbinding']['skus'][] = $value['sku'];
                                    $multi_data['productbinding']['data']=2;
                                }

                            }
                            if( !empty($multi_data) ) {

                                foreach( $multi_data as $multi_key=>$multi_value ) {
                                    $this->setProductIsMulti($multi_key,$multi_value['skus'],$multi_value['data']);

                                }
                            }
                        }
                    }


                }
            }
        }
    }

    /**
     *function:批量更新商品属性
     **/
    public function setProductMutli() {

        $product_list = json_decode(file_get_contents('php://input'), true);

        if( empty($product_list) || !isset($product_list['data'])) {

            $this->error_json("数据为空");
            die();
        }

        $success = $error = [];
        foreach( $product_list['data']  as $key=>$value ) {

            $updateFlag = $this->db->where("sku",$value['sku'])->update("product",['productismulti'=>$value['productIsMulti'],"producttype"=>$value['productType']]);
            if( $updateFlag ) {

                $success[] = $value['sku'];
            }

            if( !$updateFlag ) {

                $error[] =$value['sku'];
            }
        }

        $this->success_json(['success'=>$success,'error'=>$error]);

    }


    public function update_product_audit_status()
    {
        $total = 263226;
        $limit = 1000;

        $page = ceil( $total/$limit);
        for( $i=1;$i<=$page;++$i)
        {
            $sql = " SELECT sku,audit_status FROM pur_product_update_log  GROUP BY sku  ORDER BY id DESC LIMIT ".($i-1)*$limit.",".$limit;
            $result = $this->db->query($sql)->result_array();
            foreach( $result as $key=>$value)
            {
                $this->db->where("sku",$value['sku'])->update("product",['audit_status_log'=>$value['audit_status']]);
            }
        }
    }
    public function product_new(){

        $sql = " SELECT COUNT(*) AS total FROM pur_product";
        $countResult = $this->db->query($sql)->row_array();
        $total = $countResult['total'];
        $limit = 1000;
        $page = ceil($total/$limit);
        for( $i=1;$i<=$page;++$i ){

            $sql = " SELECT sku FROM pur_product WHERE new_flag = 0 GROUP BY sku ORDER   BY id DESC LIMIT ".($i-1)*$limit.",".$limit;
            $result = $this->db->query($sql)->result_array();
            if(!empty($result)){
                $skus = array_column($result,"sku");
                // 老采购系统
                foreach( $skus as $key=>$sku){
                    $update = [];
                    $old_purchase = $this->Product_model->old_purchase_order($sku);
                    if(empty($old_purchase)){
                        // 获取通途
                        $tongtuResult = $this->Product_model->get_tongtu_data($sku);
                        if(!empty($tongtuResult)){
                            // 通途存在下单记录
                            $update['is_tongtu_purchase'] =1;
                        }else{

                             // 如果通途不存在就查询新采购系统
                            $newResult =  $this->product_model->new_purchase_order($sku);
                            if( !empty($newResult)){
                                $update['is_new_purchase'] =1;
                            }
                        }
                    }else{
                        // 老采购系统存在下单记录
                        $update['is_old_purchase'] =1;
                    }
                    // 如果SKU 历史下单记录没有
                    if(empty($update)){
                        // 三个系统都没有历史下单记录
                        $query = $this->db->from("purchase_order_items AS orders")->join("purchase_order AS pur","orders.purchase_number=pur.purchase_number","left")->where("sku",$sku)->where_in('pur.purchase_order_status', [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE]);
                        $new_result = $query->get()->row_array();
                        if(!empty($new_result)){
                            $update['is_new_purchase'] =1;
                        }
                    }

                    if(!empty($update)){
                        $update['is_new'] =0;
                    }else{
                        $update['is_new'] =1;
                    }
                    $update['new_flag'] =1;
                    $this->db->where("sku",$sku)->update("pur_product",$update);

                }

            }

        }
    }

    /**
      * 判断SKU是否为海外首单（新采购系统是否有下单记录）
     **/

    public function is_overseas_first_order(){

        try{

            $totalQuery = " SELECT COUNT(*) AS total FROM pur_purchase_order AS orders LEFT JOIN pur_purchase_order_items AS items ON orders.purchase_number=items.purchase_number";
            $totalQuery .= " AND orders.purchase_type_id=2 AND orders.purchase_order_status IN (7,10,9,11)";
            $total = $this->db->query($totalQuery)->row_array();
            if(!empty($total)){

                $sum = $total['total'];
                $limit = 1000;
                $page = ceil($sum/$limit);
                for( $i=1;$i<=$page;++$i){

                    $searchQuery = " SELECT distinct sku  FROM pur_purchase_order AS orders LEFT JOIN pur_purchase_order_items AS items ON orders.purchase_number=items.purchase_number";
                    $searchQuery .= " AND orders.purchase_type_id=2 AND orders.purchase_order_status IN (7,10,9,11)";
                    $searchQuery .= " LIMIT ".($i-1)*$limit.",".$limit;
                    $result = $this->db->query($searchQuery)->result_array();
                    if( !empty($result)){

                        $data = array(

                            'is_overseas_first_order' =>0
                        );

                        $skuData = array_filter(array_column( $result,"sku"));
                        $res = $this->db->where_in("sku",$skuData)->update('pur_product',$data);
                        if( $res){
                            echo "yes";
                        }else{
                            echo "no";
                        }
                    }
                }
            }

        }catch ( Exception $exp){

            echo $exp->getMessage();
        }
    }

    /**
      * 获取老采购系统是否海外仓首单
     **/
    public function is_overseas_first_order_oldpurchase(){

        $erpDb=$this->load->database('oldpurchase',TRUE);
        $totalQuery = "SELECT
                    COUNT(*) AS total
                FROM
                    (
                        SELECT
                            COUNT(*)
                        FROM
                            pur_purchase_order AS a
                        LEFT JOIN pur_purchase_order_items AS b ON (a.pur_number = b.pur_number)
                        LEFT JOIN `pur_purchase_demand` AS c ON a.pur_number = c.pur_number
                        LEFT JOIN `pur_platform_summary` AS d ON d.demand_number = c.demand_number
                        AND d.sku = b.sku
                        WHERE
                            a.warehouse_code NOT IN (
                                'de-yida',
                                'wuliaocang',
                                'TCXNC'
                            )
                        AND d.demand_status NOT IN (14)
                        AND a.purchase_type = 2
                        GROUP BY
                            b.sku
                    ) AS s";
        $totalResult = $erpDb->query($totalQuery)->row_array();
        $limit  = 2000;
        $page = ceil($totalResult['total']/$limit);
        $number =0;
        for( $i=1;$i<=$page;++$i){

            $skuQuery = "  SELECT
                            b.sku
                        FROM
                            pur_purchase_order AS a
                        LEFT JOIN pur_purchase_order_items AS b ON (a.pur_number = b.pur_number)
                        LEFT JOIN `pur_purchase_demand` AS c ON a.pur_number = c.pur_number
                        LEFT JOIN `pur_platform_summary` AS d ON d.demand_number = c.demand_number
                        AND d.sku = b.sku
                        WHERE
                            a.warehouse_code NOT IN (
                                'de-yida',
                                'wuliaocang',
                                'TCXNC'
                            )
                        AND d.demand_status NOT IN (14)
                        AND a.purchase_type = 2
                        GROUP BY
                            b.sku LIMIT ".($i-1)*$limit.",".$limit;
            $skuResult = $erpDb->query($skuQuery)->result_array();
            if(!empty($skuResult)){

                $skus = array_column( $skuResult,"sku");
                $skuData = array_map(function($data){

                    return sprintf("'%s'",$data);
                },$skus);
                $sql = " UPDATE pur_product SET is_overseas_first_order=0 WHERE sku IN(".implode(",",$skuData).");";
                echo $sql."\r\n";
                ++$number;
            }
        }
        echo $number;


    }


    public function getSupplierName(){
        $erpDb=$this->load->database('slave',TRUE);

        $sql = " SELECT sku FROM datas";
        $result = $erpDb->query($sql)->result_array();

        foreach( $result as $key=>$value){

            $product_query = " SELECT supplier_name,id,sku FROM pur_product WHERE sku='".$value['sku']."'";
            $product_skus = $this->db->query($product_query)->row_array();

            $sql = " SELECT supplier_code  FROM pur_supplier WHERE supplier_name='".$product_skus['supplier_name']."'";
            $supplier_name = $this->db->query($sql)->row_array();
            if(!empty($supplier_name)){

                $product_sql = " UPDATRE pur_product SET supplier_code='".$supplier_name['supplier_code']."' WHERE id=".$product_skus['id']." AND sku='".$product_skus['sku']."';";
                echo $product_sql."\r\n";
            }else{

                $data['status'] =1;
                $erpDb->where("sku",$value['sku'])->update("datas",$data);
            }

        }


    }

    public function gtest(){


    }

    public function push_purchase_gateway()
    {
        $sql = "SELECT purchase_number,audit_time,purchase_order_status FROM pur_purchase_order WHERE audit_time>='2020-04-21 22:00:00' AND purchase_order_status=7 AND source=1";
        $result = $this->db->query($sql)->result_array();
        if(!empty($result)){
            foreach($result as $key=>$value){
                $open_items = $this->supplier_joint_model->isValidSupplier($value['purchase_number']);

                if($open_items === true){

                }else{

                    if(!empty($open_items)) {

                        $gateways = $this->supplier_joint_model->pushSmcPurchaseData([$value['purchase_number']]);
                        apiRequestLogInsert(
                            [
                                'record_number'    => json_encode([$value['purchase_number']]),
                                'record_type'      => '1688一键下单推送到门户系统',
                                'post_content'     => '1688一键下单推送到门户系统',
                                'response_content' =>$gateways,
                                'status'           => '1',
                            ],
                            'api_request_ali_log'
                        );
                    }
                }
            }
        }
    }



    /**
     * 获取产系统SKU 图片
     * @params $sku   string  商品SKU
     * @author:luxu
     * @time: 2020/9/9
     **/
    public function getProductSystemImage(){

        try{
            $sku        = $this->input->get_post('sku');
            $this->load->library('Product_system_image');
            $paramsData = [
                'sku_list'    => $sku,
                'is_complete' => 1,
                'is_cache'    => 1
            ];
            $token  = $this->product_system_image->create_access_token($sku,$paramsData);
            $quota_url = getConfigItemByName('api_config', 'product_system_image', 'get_image');

            $result = getCurlData($quota_url, $token);
            $result = json_decode($result, true);
            if(isset($result['status']) && $result['status'] == 1 && isset($result['data'])){
                $temp = [];
                foreach ($result['data'] as $key => $value){
                    $assistant = [];
                    if(isset($value['assistant'])){
                        foreach ($value['assistant'] as $val){
                            $val['url'] = erp_sku_img_sku($val['url']);
                            $val['thumb_url'] = erp_sku_img_sku_thumbnail($val['thumb_url']);
                            $assistant[] = $val;
                        }
                    }
                    $value['assistant'] = $assistant;

                    $assistant_no_logo = [];
                    if(isset($value['assistant_no_logo'])){
                        foreach ($value['assistant_no_logo'] as $val){
                            $val['url'] = erp_sku_img_sku($val['url']);
                            $val['thumb_url'] = erp_sku_img_sku_thumbnail($val['thumb_url']);
                            $assistant_no_logo[] = $val;
                        }
                    }
                    $value['assistant_no_logo'] = $assistant_no_logo;

                    $dev_image = [];
                    if(isset($value['dev_image'])){
                        foreach ($value['dev_image'] as $val){
                            $dev_image[] = erp_sku_img_sku($val);
                        }
                    }
                    $value['dev_image'] = $dev_image;

                    $temp[$key] = $value;
                }
                if(count($temp) > 0)$result['data'] = $temp;
            }
            $this->success_json($result);
        }catch (Exception $e){

            $this->error_json($e->getMessage());
        }
    }

    /**
     * 获取产品系统图片方法（接口调试）
     * @user Jolon
     * @link product_api/get_sku_images
     */
    public function get_sku_images(){
        $sku        = $this->input->get_post('sku');
        $isUpdate   = $this->input->get_post('isUpdate');
        $isInit     = $this->input->get_post('isInit');
        $limit      = $this->input->get_post('limit');
        $limit      = $limit?$limit:200;

        echo "Start:".date('Y-m-d H:i:s')."<br/>";
        set_time_limit(0);
        $this->rediss->select(1);

        if($isInit == 'yes'){ // 把数据写入到 redis 再从 redis 取SKU 同步
            $max_id = $this->rediss->getData('GET_SKU_IMAGES_MAX_ID');
            $max_id = ($max_id)?$max_id:1;


            $sku_list = $this->db->select('id,sku')->where('id >=',$max_id)->order_by('id ASC')->limit(20000)->get('product')->result_array();

            if(empty($sku_list)){
                exit('没有待同步的SKU：'.$this->db->last_query());
            }

            $max_id = max(array_column($sku_list,'id'));
            $this->rediss->setData('GET_SKU_IMAGES_MAX_ID',$max_id + 1);
            $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_GET_SKU_IMAGES_MAX_ID');

            foreach($sku_list as $sku_value){
                $this->rediss->lpushData('GET_SKU_IMAGES_SKU_LIST',$sku_value['sku']);
            }

            $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_GET_SKU_IMAGES_SKU_LIST');

            echo '同步成功个数：'.count($sku_list)."<br/>";
            echo "End:".date('Y-m-d H:i:s')."<br/>";
            exit;

        }else{
            $this->load->library('Product_system_image');

            if(!empty($sku)){
                $sku_query = $sku;
            }else{

                $sku_list = [];
                for($i = 0;$i < $limit;$i ++){
                    $sku = $this->rediss->lpopData('GET_SKU_IMAGES_SKU_LIST');

                    if(empty($sku)) break;
                    $sku_list[] = $sku;
                }
                if(empty($sku_list)){
                    echo '所有数据都处理完毕';exit;
                }
                $sku_query = implode(',',$sku_list);
            }

            print_r($sku_query);
            echo "<br/>\n\n";

            $params    = $this->product_system_image->create_access_token($sku_query);
            $quota_url = getConfigItemByName('api_config', 'product_system_image', 'get_image');

            print_r($quota_url);
            echo "<br/>\n\n";
            print_r($params);
            echo "<br/>\n\n";

            $result = getCurlData($quota_url, $params);
            $result = json_decode($result, true);


            if($isUpdate == 'yes'){
                if($result['status'] == 1){
                    $data = $result['data'];
                    foreach($data as $sku_key => $sku_img_val){
                        $first_picture = isset($sku_img_val['first_picture'])?$sku_img_val['first_picture']:'';
                        $thumb_picture = isset($sku_img_val['thumb_picture'])?$sku_img_val['thumb_picture']:'';

                        $update_arr = [
                            'product_img_url' => erp_sku_img_sku($first_picture),
                            'product_thumb_url' => erp_sku_img_sku_thumbnail($thumb_picture)
                        ];

                        $this->db->update('product',$update_arr,"sku='{$sku_key}'",1);
                    }

                }else{
                    echo "图片获取失败";
                    echo "<br/>\n\n";
                }
            }


            print_r($result);

            echo "End:".date('Y-m-d H:i:s')."<br/>";
            exit;
        }

    }



    /**
     * 更新 采购单SKU的图片 —— 上线部署处理异常
     * @user Jolon
     * @link product_api/update_order_sku_image
     */
    public function update_order_sku_image(){
        $this->load->model('purchase/Purchase_order_items_model');

        $purchase_number = $this->input->get_post('purchase_number');
        if(empty($purchase_number)){
            echo '参数缺失';exit;
        }

        $sku_items = $this->Purchase_order_items_model->get_item($purchase_number);

        if($sku_items){
            foreach($sku_items as $sku_value){
                $productInfo = $this->Product_model->get_product_info($sku_value['sku'], $field = 'product_img_url,product_thumb_url');

                if(empty($productInfo)) continue;

                $update_arr = [];
                $update_arr['product_img_url']  = erp_sku_img_sku($productInfo['product_img_url']);
                $update_arr['modify_time']      = $sku_value['modify_time'];
                $update_arr['modify_user_name'] = $sku_value['modify_user_name'];

                $this->Purchase_order_items_model->update_item($sku_value['id'],$update_arr);

            }

            echo 'sss';exit;
        }else{
            echo 'error';exit;
        }
    }

    public function skuCustomized(){

        $total = 425735;
        $limit = 2000;
        $page = ceil(425735/$limit);
        for($i=1;$i<=$page;++$i){

            $sql = " SELECT id FROM pur_product WHERE is_customized=0  ORDER BY id DESC LIMIT ".($i-1)*$limit.",".$limit;
            $data = $this->db->query($sql)->result_array();
            if(!empty($data)){

                $ids = array_column($data,"id");
                $this->db->where_in("id",$ids)->update('product',['is_customized'=>2]);
            }else{

                break;
            }
        }
    }
    /**
     * 112238 【产品 VS 采购】新采购系统向新产品系统主动调取1688关联关系
     * 新创建的sku，erp推送到新采购系统，采购员都需要重新绑定1688，特别浪费采购员的工作效率。
     * 实质上，新产品系统创建的sku在新产品系统都是关联了1688的
       故而，希望新产品系统绑定1688ID之后，直接将绑定关系推送到新采购系统，新采购系统的用户无需再次去关联
     * @author:luxu
     * @time:2020/8/14
     **/

    public function relate_ali_sku(){

        try{
            $this->load->model('ali/Ali_product_model');
            $this->load->model('product/product_update_log_model','product_update_log');
            $this->load->library('alibaba/AliProductApi');

            $this->load->library('alibaba/AliOrderApi');
            $this->load->library('alibaba/AliSupplierApi');
            $this->load->model('product/Product_model');
            $this->load->model('Ali_product_model');
            // 获取未关联1688的SKU 总条数
            $totalResult = $this->db->from("product_sku_releated")->where("is_releated",0)->select(" COUNT(id) as count")->get()->row_array();
            $limit = 100; // 一次关联100条数据
            $page = ceil($totalResult['count']/$limit);
            for($i=1;$i<=$page;++$i){

                $resultQuery = " SELECT * FROM pur_product_sku_releated WHERE is_releated=0 LIMIT ".($i-1)*$limit.",".$limit;
                $result = $this->db->query($resultQuery)->result_array();
                if(!empty($result)){

                    foreach($result as $key=>$value){

                        $productIds[$value['sku']] = $value['product_relate_url'];
                        $skuIds[$value['sku']] = $value['sku_id'];
                        $specIds[$value['sku']] = $value['spec_id'];
                        try {
//                            if ($this->product_update_log->check_in_audit($value['sku'])) {
//                                throw new Exception($value['sku'] . ' 的基本信息在产品列表中处于审核中，请直接前往产品列表中修改');
//                            }
//
//                            $ali_product = $this->aliproductapi->parseProductIdByLink( $value['product_relate_url']);
//
//                            if(empty($ali_product['code'])){
//                                throw new Exception($value['sku'] . $ali_product['errorMsg']);
//                            }else{
//                                $product_id = $ali_product['data'];
//                            }
//
//                            $productInfo = $this->Product_model->get_product_info($value['sku']);
//                            $supplierInfo = $this->supplier_model->get_supplier_info($productInfo['supplier_code'],false);
//                            $ali_supplier = $this->aliproductapi->getSupplierByProductId($product_id);
//                            print_r($ali_supplier);die();
//                            if(empty($ali_supplier['code'])){
//
//                                throw new Exception($value['sku'] . ' '.$ali_supplier['errorMsg']);
//                            }
//                            print_r($supplierInfo['shop_id']);
//                            echo "---------\n";
//                            print_r($ali_supplier['data']['loginId']);
//                            die();
//                            if(empty($supplierInfo['shop_id']) or $supplierInfo['shop_id'] != $ali_supplier['data']['loginId']){
//                                throw new Exception($value['sku'] . ' 不是同一个供应商[loginId]，变更供应商请直接前往产品列表中修改');
//                            }


                            $res = $this->Ali_product_model->relate_ali_sku($productIds, $skuIds, $specIds);
                            if ($res['code'] == true) {
                                // 1688绑定成功
                                $data['is_releated'] = 1;
                                $this->db->where("id", $value['id'])->update('product_sku_releated', $data);
                            }
                        }catch ( Exception $exception){

                            $dataError['error_message'] = $exception->getMessage();
                            $this->db->where("id", $value['id'])->update('product_sku_releated', $dataError);
                        }
                    }
                }
            }
        }catch ( Exception $exp ){

            echo $exp->getMessage();die();
        }
    }

    /**
     * 接受产品系统推送的SKU1688关联信息
     * @author:luxu
     * @time:2020/8/15
     **/
    public function skuRelate(){

        $updateData = json_decode(file_get_contents('php://input'), true);
        if( empty($updateData)) {

            $this->error_json("数据为空");
            die();
        }

        $success = $error = [];
        foreach($updateData as $key=>$value){

            $searchSkuData = $this->db->from("product_sku_releated")->where("sku",$value['sku'])->select("id")->get()->row_array();
            if(!empty($searchSkuData)){

                $result = $this->db->where("sku",$value['sku'])->update("product_sku_releated",$value);
            }else{

                $result = $this->db->insert("product_sku_releated",$value);
            }

            if($result){

                $success[] = $value['sku'];
            }else{
                $error[]= $value['sku'];
            }
        }

        http_response(['status'=>1,'success'=>$success,'error'=>$error]);
    }


    public function is_new(){

        $total = 49678;
        $limit = 1000;

        $page = ceil($total/$limit);

        for($i=1;$i<=$page;++$i){

            $sql = "SELECT purchase_number,sku,min(create_time),id FROM datas  GROUP BY sku HAVING count(*)>1  ORDER BY create_time ASC LIMIT  ".($i-1)*$limit.",".$limit;
            $result = $this->db->query($sql)->result_array();

            foreach($result as $key=>$value){



//                $one = " UPDATE yibai_purchase.pur_purchase_order_items SET is_new=1 WHERE id=".$value['id'].";\r\n";
//                $two = " UPDATE yibai_purchase.pur_purchase_order_items SET is_new=0 WHERE sku='".$value['sku']."' AND id!=".$value['id'].";\r\n";
//                echo $one;
//                echo $two;
            }


        }
    }





    /**
     * 把产品列表的数据的SKU存入MondoDB
     * product_api/product_list_to_mongodb_for_search?init=init
     * @author Jolon
     */
    public function product_list_to_mongodb_for_search(){
        set_time_limit(0);
        $this->load->library('mongo_db');

        $init        = $this->input->get_post('init');

        $now_time = date('Y-m-d H:i:s');
        $max_id_list = $this->mongo_db->where(['pKey' => 'productListToMongoDbForSearch'])->get('keyRecordsList');
        if(!isset($max_id_list[0])){
            $insert_data = [];
            $insert_data['pKey']        = 'productListToMongoDbForSearch';
            $insert_data['max_id']      = 1;
            $insert_data['last_time']   = '0000-00-00 00:00:00';
            $this->mongo_db->insert('keyRecordsList', $insert_data);

            $max_id = 1;
            $last_time = '0000-00-00 00:00:00';
        }else{
            $max_id = $max_id_list[0]->max_id;
            $last_time = $max_id_list[0]->last_time;
        }

        if($init == 'init'){
            $sku_list = $this->db->select('id,sku,product_name')
                ->where('id >',$max_id)
                ->order_by('id asc')
                ->get('pur_product',10000)
                ->result_array();
        }else{
            $sku_list = $this->db->select('id,sku,product_name')
                ->where('update_time >',$last_time)
                ->order_by('id asc')
                ->get('pur_product',10000)
                ->result_array();
        }

        if(empty($sku_list)){
            echo '没有新增的数据';exit;
        }
        $new_max_id = $max_id;

        // 先删除 后插入
        $sku_arr = array_column($sku_list,'sku');
        $this->mongo_db->where_in("p_sku", $sku_arr)->delete_all('productListToMongoDbForSearch');

        pr($new_max_id);
        pr($last_time);
        $count = 0;
        foreach($sku_list as $sku_value){
            $insert_data = [];
            $insert_data['p_id']            = $sku_value['id'];
            $insert_data['p_sku']           = $sku_value['sku'];
            $insert_data['p_product_name']  = $sku_value['product_name'];
            $this->mongo_db->insert('productListToMongoDbForSearch', $insert_data);

            if($insert_data['p_id'] > $new_max_id){
                $new_max_id = $insert_data['p_id'];
            }
            $count ++;
        }
        pr($count);

        $res = $this->mongo_db->where(['pKey' => 'productListToMongoDbForSearch'])->update('keyRecordsList',['max_id' => $new_max_id,'last_time' => $now_time]);
        pr($res);exit;
    }


    /**
     * product_api/test_product_list_to_mongodb_for_search?sku=1233
     */
    public function test_product_list_to_mongodb_for_search(){
        $sku = $this->input->get_post('sku');

        $sku_list = [];
        try{
            // 利用 MongoDB进行查询优化
            $this->_ci     = get_instance();
            $this->_ci->load->config('mongodb');
            $host          = $this->_ci->config->item('mongo_host');
            $port          = $this->_ci->config->item('mongo_port');
            $user          = $this->_ci->config->item('mongo_user');
            $password      = $this->_ci->config->item('mongo_pass');
            $author_db     = $this->_ci->config->item('mongo_db');
            $mongdb_object = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
            $filter        = ['p_sku' => ['$in' => [new MongoDB\BSON\Regex($sku, 'i')]]];
            $query         = new MongoDB\Driver\Query($filter);
            $cursor        = $mongdb_object->executeQuery("{$author_db}.productListToMongoDbForSearch", $query);

            foreach($cursor as $document){
                $sku_list[] = $document->p_sku;
            }
            print_r(count($sku_list));
        }catch(\MongoDB\Driver\Exception\Exception $exception){
            echo $exception->getMessage();
        }catch(Exception $exception2){
            echo $exception2->getMessage();
        }

        echo 'end';exit;
    }

    /**
     * 同步定时任务
     */
    public function examine_estimated_arrive_time()
    {
        $this->supplier_joint_model->examine_estimated_arrive_time();
    }

    /**
     *  【计划系统V1.5.10】新增一个根据sku获取供应商交期的java接口：
     *   入参：sku
         出参：sku，供应商交期
     *   取值逻辑：根据sku到采购系统的产品管理列表中取最新的交期，支持批量
     * @param 需求编号:29851 【计划系统V1.5.10】新增一个根据sku获取供应商交期的java接口
     * @author:luxu
     * @time:2021年1月25号
     **/

    public function getProductDevliery(){

        try{

            $sku_json = file_get_contents('php://input');
            $skus = json_decode(stripslashes($sku_json),True);
            if(empty($skus)){

                throw new  Exception("请传入SKU");
            }

            $result = $this->db->from("product")->where_in("sku",$skus)->select("sku,devliy,is_relate_ali,starting_qty")->get()->result_array();
            $this->success_json($result);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 需求：30282 配合客服系统对接产品列表,根据SKU获取采购员和是否代采的字段
     *  需求描述:客服系统需要对接采购系统,根据SKU获取最新的采购员和是否代采
     * @author:luxu
     * @params:HTTP JSON
     * @time:2021年1月29号
     **/
    public function getSkuBueryName(){
        try{
            $this->load->model('supplier_buyer_model','supplier_buyer_model',false,'supplier');
            $sku_json = file_get_contents('php://input');
            $skus = json_decode(stripslashes($sku_json),True);
            if(empty($skus)){

                throw new  Exception("请传入SKU");
            }

            $result = $this->db->from("product")->where_in("sku",$skus)->select("sku,is_purchasing,supplier_code")->get()->result_array();
            if(!empty($result)){

                $returnData = [];
                foreach( $result as $key=>$value){

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
                    //是否需要代采 1表示否2表示是' ,
                    $is_purchasing_ch = '';
                    if( $value['is_purchasing'] == 1){
                        $is_purchasing_ch = "否";
                    }else{
                        $is_purchasing_ch = "是";
                    }

                    $returnData[] = [

                        'sku' => $value['sku'],
                        'is_purchas_ch' => $is_purchasing_ch,
                        'buyer' => $supplier_buyer_user,
                        'is_purchas' => $value['is_purchasing']
                    ];
                }
            }
            $this->success_json($returnData);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }

    }


    /**
      * 推送供应商修改数据到产品系统
     **/
    public function pushDatatoproduct(){
        $javas_system = $this->config->item('product_system');
        $pushProducturl = $javas_system['updatePurSupplierCode']."?access_token=". getOASystemAccessToken();
        $this->load->library('mongo_db');

        $res = $this->mongo_db
            ->where(['type'=>'updatePurSupplierCode'])->where(['is_push'=>0])
            ->get('pushproductdata');

        foreach($res as $key=>$value){
            $datas = get_object_vars($value);
            $result = send_http($pushProducturl,json_encode($datas['data']));
            $datas['data']['return'] = $result;
            $this->mongo_db->insert('pushproductdatalog', $datas['data']);

            $this->mongo_db->where(["_id"=>$datas['_id']])->update('pushproductdata',['is_push'=>1]);
        }
    }

    /**
     * 推送供应商修改数据到产品系统
     **/
    public function pushpurchasingDatatoproduct(){
        $javas_system = $this->config->item('product_system');
        $pushProducturl = $javas_system['updateIsPurchasingBySku']."?access_token=". getOASystemAccessToken();
        $this->load->library('mongo_db');

        $res = $this->mongo_db
            ->where(['type'=>'updateIsPurchasingBySku'])->where(['is_push'=>0])
            ->get('pushproductdata');

        foreach($res as $key=>$value){
            $datas = get_object_vars($value);
            $result = send_http($pushProducturl,json_encode($datas['data']));
            $datas['data']['return'] = $result;
            $this->mongo_db->insert('pushproductdatalog', $datas['data']);
            $this->mongo_db->where(["_id"=>$datas['_id']])->update('pushproductdata',['is_push'=>1]);
        }
    }

    public function watchpushProductPrice()
    {

        $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSku-updateSkuPrice');
        $this->load->library('mongo_db');

        $res = $this->mongo_db
            ->where(['type' => 'purchasePriceData'])->where(['is_push' => 2])
            ->get('pushproductdata');

        if (!empty($res)) {

            $header = ['Content-Type: application/json'];
            $access_token = getOASystemAccessToken();
            $request_url = $request_url . "?access_token=" . $access_token;
            foreach ($res as $key => $value) {

                $datas = get_object_vars($value);
                $pushDatas = $datas['data'];
                $skus = array_column($pushDatas,"sku");
                if(!empty($pushDatas)){
                    foreach($pushDatas as $skus_key=>$skus_value){
                        $datas = get_object_vars($skus_value);

                        $skuMess = $this->db->from("product")->where("sku",$datas['sku'])->select("purchase_price,sku")->get()->row_array();
                        $sendData = [
                            'sku' => $skuMess['sku'],
                            'newPrice' => $skuMess['purchase_price'],
                            'shipCost' => 0,
                            'avgPrice' => 0
                        ];
                        $results = getCurlData($request_url, json_encode([$sendData]), 'post', $header);
                        $results = json_decode($results,True);

                        if($results['code'] == 200){

                            $mdatas = get_object_vars($value);
                            $this->mongo_db->where(["_id"=>$mdatas['_id']])->update('pushproductdata',['is_push'=>1]);

                            $pushProductPriceDatas = [
                                'sku' => $sendData['sku'],
                                'newPrice' => $sendData['newPrice'],
                                'time' => date('Y-m-d H:i:s', time())
                            ];
                            if (!empty($pushProductPriceDatas)) {
                                $mq = new Rabbitmq();
                                //设置参数
                                $mq->setExchangeName('PRODUCT_PUSH_SYSTEM_PRICE_DATA');
                                $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
                                $mq->sendMessage($pushProductPriceDatas);
                            }
                        }
                        $logs['sku'] = $skus_value;
                        $logs['result'] = $results;
                        $logs['create_time'] = date('Y-m-d H:i:s', time());
                        $logs['mq_data'] = isset($pushProductPriceDatas)?$pushProductPriceDatas:'';
                        $logs['product_data'] = isset($sendData)?$sendData:'';
                        $this->mongo_db->insert('productPriceLogs', $logs);
                    }
                }
            }


        }
    }

    /**
     * 修改产品价格推送产品系统
     **/

    public function pushProductPrice(){

        $this->load->library('mongo_db');

        $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSku-updateSkuPrice');
        $starttime = "2021-07-07";
        $total = $this->db->from("product_update_log")->where("new_supplier_price!=old_supplier_price")
            ->where("audit_status=3")->where("audit_time>=",$starttime)->where_in("is_push_product_system",[0,2])->count_all_results();
        $limit =500;
        $page = ceil($total/$limit);
        $header = ['Content-Type: application/json'];
        $access_token = getOASystemAccessToken();
        $request_url = $request_url."?access_token=".$access_token;
        for($i=1;$i<=$page;++$i){
            $result = $this->db->from("product_update_log")->select("id,sku,new_supplier_price AS newPrice")
                ->where("new_supplier_price!=old_supplier_price")
                ->where("audit_status=3")->where("audit_time>=",$starttime)->where_in("is_push_product_system",[0,2])->limit($limit,($i-1)*$limit)
                ->order_by("audit_time ASC")->group_by("sku")->get()->result_array();

            if(!empty($result)){
                $pushDatas = $logsIds =  [];

                foreach($result as $result_key=>&$result_value){
                    /**
                       这里会重复获取SKU 修改的最新信息。保证获取到最新的数据
                     **/
                    $sql = " SELECT id,sku,new_supplier_price as newPrice,audit_time FROM pur_product_update_log WHERE
                             new_supplier_price!=old_supplier_price AND audit_status=3 AND 
                             sku='{$result_value['sku']}' AND is_push_product_system IN (0,2) 
                             AND audit_time>='{$starttime}'
                             ORDER BY audit_time DESC  
                           ";
                    $resultAscDatas = $this->db->query($sql)->result_array();
                    if(!empty($resultAscDatas)) {
                        $resultAsc = $resultAscDatas[0];
                        if (!empty($resultAsc)) {
                            $pushDatas[] = [
                                'sku' => $resultAsc['sku'],
                                'newPrice' => $resultAsc['newPrice'],
                                'shipCost' => 0,
                                'avgPrice' => 0
                            ];

                            $logsIds[] = $resultAsc['id'];
                        }
                        $alllogsids = array_column($resultAscDatas, "id");
                        $difflogsIds = array_diff($alllogsids,$logsIds);

                        if(!empty($difflogsIds)){

                            $logsDiffs = [

                                'is_push_product_system' => 1,
                                'product_return_data' => '已推送SKU最新数据'
                            ];
                            $this->db->where_in("id",$difflogsIds)->update("product_update_log",$logsDiffs);
                        }

                    }
                }
                $results = getCurlData($request_url, json_encode($pushDatas), 'post', $header);
                $results = json_decode($results,True);
                $updates = [
                    'product_return_data' => json_encode($results)
                ];
                if($results['code'] == 200){
                    $updates['is_push_product_system'] = 1;
                    foreach($pushDatas as $pushDatas_key=>$pushDatas_value) {
                        $pushProductPriceDatas = [
                            'sku' => $pushDatas_value['sku'],
                            'newPrice' => $pushDatas_value['newPrice'],
                            'time' => date('Y-m-d H:i:s', time())
                        ];
                        if (!empty($pushProductPriceDatas)) {
                            $mq = new Rabbitmq();
                            //设置参数
                            $mq->setExchangeName('PRODUCT_PUSH_SYSTEM_PRICE_DATA');
                            $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
                            $mq->sendMessage($pushProductPriceDatas);

                            $sendMq =[

                                'sku' => $pushDatas_value['sku'],
                                'data' => $pushProductPriceDatas,
                                'type' => 'MQ'
                            ];
                            $this->mongo_db->insert('pushproductdatalog', $sendMq);


                        }
                    }

                }else{
                        $updates['is_push_product_system'] = 2;
                }
                if(!empty($logsIds)) {

                    $this->db->where_in("id", $logsIds)->update("product_update_log", $updates);

                }
            }else{
                break;
            }
        }
    }

    /**
     * 推送供应商修改数据到ERP系统
     **/
    public function pushpurchasingDatatoerp(){
        $this->load->config('api_config', FALSE, TRUE);
        $erp_system = $this->config->item('erp_system');
        $to_erp_purchase_price_url = $erp_system['purchase_price_to_erp']."?access_token=". getOASystemAccessToken();
        $this->load->library('mongo_db');

        $res = $this->mongo_db
            ->where(['type'=>'purchase_price_to_erp'])->where(['is_push'=>0])
            ->get('pushproductdata');

        foreach($res as $key=>$value){
            $datas = get_object_vars($value);

            $result = send_http($to_erp_purchase_price_url,$datas['data']);
            $returns['data'] = $datas['data'];
            $returns['return'] = $result;
            $this->mongo_db->insert('pushproductdatalog', $returns);
            $this->mongo_db->where(["_id"=>$datas['_id']])->update('pushproductdata',['is_push'=>1]);
        }
    }

    /**
     * 31564 产品系统的SKU产品状态发生变更时,SKU对应ECN状态变更的变更类型也要一起传值到采购系统
     * @author:luxu
     * @time:2021年3月11号
     **/

    public function sku_change_data(){

        try{


            $sku_json = file_get_contents('php://input');
            $skus = json_decode($sku_json,True);
            if(!empty($skus)){

                $success = $error = [];
                foreach($skus as $key=>$value){

                   $flag = $this->db->where("sku",$value['sku'])->update('product',['sku_change_data'=>$value['sku_change_data']]);
                   $effect = $this->db->affected_rows();
                   if($effect>0){
                       $success[]= $value['sku'];
                   }else{
                       $error[]= $value['sku'];
                   }
                }

                $this->success_json(['success'=>$success,'error'=>$error]);
            }
            throw new Exception("请传入SKU 数据");
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    public function suggest_shua(){

        ini_set('max_execution_time','18000');

        $sql = "SELECT a FROM datas ";
         $slaveDb=$this->load->database('slave',TRUE);
         $total =3586;
         $limit = 1000;
         $page = ceil($total/$limit);
         for($i=1;$i<=$page;++$i){
             $sql = " SELECT pur_sn FROM datas LIMIT ".($i-1)*$limit.",".$limit;
             $result = $slaveDb->query($sql)->result_array();
             $purSns= array_column($result,'pur_sn');

             $purchaseDatas = $this->db->from("purchase_order_items")->where_in("demand_number",$purSns)->select("purchase_number,sku")->get()->result_array();

             if( !empty($purchaseDatas)){

                 foreach( $purchaseDatas as $datas_key=>$datas_value){

                     $details = $this->db->from("purchase_reduced_detail_other")->where("purchase_number",$datas_value['purchase_number'])
                         ->where("sku",$datas_value['sku'])->where("is_effect",1)->get()->row_array();
                     if(!empty($details)) {

                         $update = " UPDATE yibai_purchase.pur_purchase_reduced_detail_other SET is_effect=2 WHERE id=".$details['id'];
                         echo $update."\r\n;";
                     }
                 }
             }
         }
    }

    /**
     * 获取SKU是否超长交期
     * @author:luxu
     * @time:2021年3月29号
     **/
    public function get_sku_long_delivery(){

        try{
            $updateData = json_decode(file_get_contents('php://input'), true);
            if(empty($updateData)){

                $this->error_json('传入SKU为空');
            }

            $results = $this->db->from("product")->where_in("sku",$updateData)->select("sku,long_delivery")->get()->result_array();
            if(!empty($results)){

                foreach( $results as $key=>&$value){

                    if($value['long_delivery'] == 1){

                        $value['long_delivery_ch'] = "否";
                    }else{
                        $value['long_delivery_ch'] = "是";
                    }
                }
            }

            $this->success_json($results);
        }catch ( Exception $exception ){


        }
    }

    /**
     * 更新备货单产品线
     */
    public function updateSuggestProductLine()
    {
        try{
            $demand_number = $this->input->get_post('demand_number');
            $line = $this->input->get_post('line');
            $type = $this->input->get_post('type');
            if(!empty($type)){
                if((empty($demand_number) || empty($line))){
                    $this->error_json("kongde?");
                }
                $res = $this->product_line_model->get_product_line_by_id([], $line);
                if(isset($res['id']) && isset($res['title'])){
                    $this->db->where(["demand_number" => $demand_number])
                        ->update("pur_purchase_suggest", ["product_line_id" => $res['id'], "product_line_name" => $res['title']]);
                }
            }else{
                $date = date('Y-m-d',strtotime('-60 days'))." 00:00:00";
                $data = $this->db->from("purchase_order_items as it")
                    ->select("it.demand_number, d.product_line_id")
                    ->join("pur_purchase_suggest AS su", "it.demand_number = su.demand_number", "left")
                    ->join("pur_product AS d", "it.sku = d.sku", "left")
                    ->where("su.create_time >", $date)
                    ->where_not_in("su.suggest_order_status", [1,2,3,5,6,14])
                    ->get()
                    ->result_array();
                if(!$data || count($data) == 0)exit;
                foreach ($data as $val){
                    $res = $this->product_line_model->get_product_line_by_id([], $val['product_line_id']);
                    if(isset($res['id']) && isset($res['title'])){
                        $this->db->where(["demand_number" => $val['demand_number']])
                            ->update("pur_purchase_suggest", ["product_line_id" => $res['id'], "product_line_name" => $res['title']]);
                    }
                }
            }
        }catch (Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 获取表:sku_wms_data 中status=0 的数据更新产品表字段
     * @author:luxu
     * @time:2021年5月11号
     **/
    public function pullwmstoproduct(){

        $countDatas = $this->db->from("sku_wms_data")->where("status",0)->group_by("sku")->count_all_results();
        $limit = 100;
        $page= ceil($countDatas/$limit);
        $this->load->library('mongo_db');
        for($i=0;$i<=$page;++$i){
            $skusDatas = $this->db->from("sku_wms_data")->where("status",0)->order_by("create_time DESC")->get()->row_array();
            $productDatas = $this->db->from("product")->where("sku",$skusDatas['sku'])->select("id")->get()->row_array();
           // print_r($productDatas);die();
            if(empty($productDatas)){
                continue;
            }
            if(!empty($productDatas)){
                $updateDatas = [
                    'tax_rate'=>$skusDatas['taxRate'],
                    'declare_unit'=>$skusDatas['declareUnit'],
                    'declare_cname' => $skusDatas['declareUnit'],
                    'is_inspection' => $skusDatas['isInspection'],
                    'export_cname' => $skusDatas['exportCname']
                ];
                $result = $this->db->where("sku",$skusDatas['sku'])->update("product",$updateDatas);
                //echo $this->db->last_query();die();
                if($result){
                    $this->db->where("sku",$skusDatas['sku'])->where("status",0)->update("sku_wms_data",['status'=>1]);
                    $updateDatas['sku'] = $skusDatas['sku'];
                    $this->mongo_db->insert('pullwmsdatalog', $updateDatas);
                }

            }else{
                continue;
            }
        }
    }

    private function getWmsMQ(){

        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('sync_product_logistics');
        $mq->setExchangeName('sync_product_logistics');
        $mq->setRouteKey('sync_product_logistics');
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        //构造存入数据
        //存入消息队列
        $queue_obj = $mq->getQueue();
        //处理生产者发送过来的数据
        $envelope = $queue_obj->get();
        $data = NULL;
        if($envelope) {
            $data = $envelope->getBody();

            $queue_obj->ack($envelope->getDeliveryTag());
        }
        $mq->disconnect();
        return $data;
    }

    /**
     * 获取物流系统SKU 的退税率、开票品名、开票单位、出口申报型号、是否商检
     * 需求号:34211 出口审核B2B/出口退税率等数据修改后且通过审核后，
     * 即时推送最新的开票信息到采购系统的产品管理列表,推送数据格式为MQ #3
     * @author:luxu
     * @time:2021年5月11号
     **/
    public function pushwmsData()
    {
        try {
            ini_set('max_execution_time', '18000');
            $i = 0;
            while ($i < 500) {

                if ($i > 500) {

                    break;
                }
                //$data = '{"customsCode":"GS00005","declareUnit":"千克\/个","exportCname":"弹跳器","isInspection":"1","taxRate":"33.00","declare":"","sku":"GS00005"}';
                $data = $this->getWmsMQ();
                $skuDatas = json_decode($data, true);
                if (!empty($skuDatas)) {
                    $productMessage = $this->db->from("product")->where("sku", $skuDatas['sku'])->get()->row_array();
                    if (!empty($productMessage)) {
                        $updateDatas = [
                            'tax_rate' => $skuDatas['taxRate'],
                            'declare_unit' => $skuDatas['declareUnit'],
                            'export_cname' => $skuDatas['exportCname'],
                            'is_inspection' => $skuDatas['isInspection'],
                            'customs_code' => $skuDatas['customsCode']
                        ];
                        $skuDatas['status'] = 1;
                        $result = $this->db->where("sku", $skuDatas['sku'])->update("product", $updateDatas);
                    } else {
                        $skuDatas['create_time'] = date("Y-m-d H:i:s", time());
                        //如果是SKU采购系统不存在的情况
                        $result = $this->db->insert('sku_wms_data', $skuDatas);
                        $skuDatas['status'] = 0;
                    }
                }
                $this->load->library('mongo_db');
                $this->mongo_db->insert('pullwmsdata', $skuDatas);
                ++$i;
            }


        } catch (Exception $exp) {

            echo $exp->getMessage();
        }
    }


    public  function alternativeProduct(){
        ini_set('max_execution_time','18000');
        $this->load->model('product/Alternative_suppliers_model');
        $sku_json = file_get_contents('php://input');
        $skus = json_decode($sku_json,True);
        $logs = $this->db->from("product_update_log")->where("new_supplier_code!=old_supplier_code")
            ->where("old_supplier_code!=''")->where("audit_status=3")->where_in("sku",$skus)->get()->result_array();
        // echo $this->db->last_query();die();
        if(!empty($logs)){

            foreach( $logs as $key=>$val){
                //print_r($val);die();

                $where =[

                    'sku' => $val['sku'],
                    'supplier_code' => $val['old_supplier_code']
                ];

                $alternativeDatas = $this->db->from("alternative_suppliers")->where($where)->get()->row_array();
                if(empty($alternativeDatas)) {
                    $this->Alternative_suppliers_model->product_audit_alternative_log($val, 'product_audit');

                    $pushalternativeDatas = [

                        'sku' => $val['sku'],
                        'supplier_name' => $val['old_supplier_name'], //  供应商名称
                        'supplier_code' => $val['old_supplier_code'], // 供应商CODE
                        'purchase_price' => $val['old_supplier_price'], // 未税单价
                        'starting_qty_unit' => $val['old_starting_qty_unit'], // 最小起订量
                        'is_shipping' => $val['is_old_shipping'], // 是否包邮
                        'delivery' => $val['old_devliy'], // 交期
                        'url' => !empty($val['old_product_link']) ? $val['old_product_link'] : '', // 采购连接
                    ];

                    $this->Alternative_suppliers_model->pushMq($pushalternativeDatas);
                }

            }
        }

    }

    /**
     * 获取智库采购员数据TOKEN
     * @author:luxu
     * @time:2021年5月13号
     **/
    public function get_digitization_jwt(){

        try{
            $jwtDatas = $this->rediss->getData('DIGITIZATION_JWT_TOKEN');
            if(empty($jwtDatas)){

                $url = "http://python2.yibainetwork.com/yibai/python/services/jwt/token?iss=technical_sh_purchase&secret=d2Dw.3Qldacnr4";
                $datas = getCurlData($url,'','GET');
                // $datas = '{"status": 200, "msg": "Success", "exp": "2021-05-14 09:41:04", "jwt":
//"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJ0ZWNobmljYWxfc2hfcHVyY2hhc2UiLCJleHAiOjE2MjA5NTY0NjR9.-KCftQM6yJrre4sKbj8uttkx-52HWZR_rY0g9QdI8wY"}';
                $datas = json_decode($datas,True);
                if(isset($datas['status']) && $datas['status'] == 200){

                    $expiretimedays = round((strtotime($datas['exp']) - strtotime(date("Y-m-d H:i:s")))/86400);
                    $expiretime = 3600*24*$expiretimedays;
                    $this->rediss->setData('DIGITIZATION_JWT_TOKEN', json_encode($datas),$expiretime);
                    return $datas['jwt'];

                }

                throw new Exception("获取TOKE失败");
            }else{
                $jwtDatas = json_decode($jwtDatas,True);
                return $jwtDatas['jwt'];
            }

        }catch ( Exception $exp ){
            throw new Exception($exp);

        }
    }


    public function get_bi_supplieravg_data(){
        ini_set('max_execution_time','18000');

        $getnowtime =  NULL;
        if(isset($_GET['nowtime']) && !empty($_GET['nowtime'])){

            $nowtime = $_GET['nowtime'];
        }else{

            $nowtime =  date("Y-m",time());
        }
        $token = $this->get_digitization_jwt();
        $url = "bi.yibainetwork.com:8000/bi/dwh/supplier/supplier_pur_pages?jwt=".$token;
        $getTotal = $url."&size=1&statis_month=".$nowtime;
        $result = getCurlData($getTotal, [], 'GET');
        $result = json_decode($result, TRUE);
        if(!empty($result)){
            $total = $result['total'];
            if($total>0){

                $limit = 1500;
                $page = ceil($total/$limit);

                for($i=1;$i<=$page;++$i){
                    $pullUrl = $url."&page=".$i."&size=".$limit."&statis_month=".$nowtime;
                    $result = getCurlData($pullUrl, [], 'GET');
                    $result = json_decode($result, TRUE);
                    if(!empty($result)){

                        if($result['status'] == 200 && !empty($result['data'])){
                           // $ntime =  date("Y-m",time());
                            $supplier_codes = array_column($result['data'],"supplier_code");
                            $pmsAvgDatas = $this->db->from("supplier_avg_day")->where_in("supplier_code",$supplier_codes)
                                ->where("statis_month",$nowtime)->select("statis_month,supplier_code,id")->get()->result_array();
                            $insertData = $updateData = [];

                            if(empty($pmsAvgDatas)){
                                foreach($result['data'] as $result_k=>&$result_v){
                                    if(isset($result_v['ds_deliver_day'])){

                                        $result_v['ds_day_avg'] = $result_v['ds_deliver_day'];
                                        unset($result_v['ds_deliver_day']);
                                    }
                                    if(isset($result_v['os_deliver_day'])){

                                        $result_v['os_day_avg'] = $result_v['os_deliver_day'];
                                        unset($result_v['os_deliver_day']);
                                    }
                                }
                                $insertData = $result['data'];
                            }else{
                                $newpmsAvgDatas = [];
                                foreach($pmsAvgDatas as $pmsAvgData_key=>$pmsAvgData_value){

                                    $keys = $pmsAvgData_value['statis_month']."|".$pmsAvgData_value['supplier_code'];
                                    if(!isset($newpmsAvgDatas[$keys])){

                                        $newpmsAvgDatas[$keys] = $pmsAvgData_value['id'];
                                    }
                                }

                                foreach($result['data'] as $result_key=>$result_value){

                                    if(isset($result_value['ds_deliver_day'])){

                                        $result_value['ds_day_avg'] = $result_value['ds_deliver_day'];
                                        unset($result_value['ds_deliver_day']);
                                    }

                                    if(isset($result_value['os_deliver_day'])){

                                        $result_value['os_day_avg'] = $result_value['os_deliver_day'];
                                        unset($result_value['os_deliver_day']);
                                    }

                                    $keys = $result_value['statis_month']."|".$result_value['supplier_code'];

                                    if(isset($newpmsAvgDatas[$keys])){
                                        $result_value['id'] = $newpmsAvgDatas[$keys];
                                        $updateData[] = $result_value;
                                    }else{
                                        $insertData[] = $result_value;
                                    }
                                }
                            }
                            if(!empty($insertData)){

                                $this->db->insert_batch("supplier_avg_day",$insertData);
                            }
                            if(!empty($updateData)){

                                $this->db->update_batch("supplier_avg_day",$updateData,"id");
                            }

                        }
                    }
                }
            }
        }
    }


    public function delDatas(){

        $this->db->where("1=1")->delete("supplier_avg_day");
    }

    public function delrep(){

        $sql = "SELECT sku FROM pur_product_update_log WHERE create_time>='2021-06-19' AND create_user_name='蒋敏12856'  AND audit_status!=3 GROUP BY sku HAVING count(*)>1";

        $total = $this->db->query($sql)->result_array();
        $total = count($total);
        $limit = 500;
        $page = ceil($total/$limit);

        for($i=1;$i<=$page;++$i){

            $sql = "SELECT * FROM pur_product_update_log WHERE create_time>='2021-06-19' AND create_user_name='蒋敏12856'  AND audit_status!=3 GROUP BY sku HAVING count(*)>1";
            $sql .= " LIMIT ".($i-1)*$limit.",".$limit;

            $result = $this->db->query($sql)->result_array();

            if(!empty($result)){

                foreach( $result as $key=>$value){

                   $this->db->where("id!=",$value['id'])->where("sku",$value['sku'])->where("create_time>=",'2021-06-19')
                       ->where("create_user_name","蒋敏12856")->where("audit_status!=",3)
                       ->delete("product_update_log");

                   echo "sku=".$value['sku']."\r\n";
                }
            }
        }
    }

    public function updateuser(){

       $sql = 'SELECT sku,new_supplier_code FROM pur_product_update_log WHERE old_supplier_code!=new_supplier_code AND audit_time>="2021-07-15" AND audit_status=3';
       $result = $this->db->query($sql)->result_array();
       foreach($result as $key=>$value){

           $where = [

               'sku' => $value['sku'],
               'supplier_code' => $value['new_supplier_code'],
               'source_from' =>4
           ];

           $this->db->where($where)->update("alternative_suppliers",["update_time"=>date("Y-m-d H:i:s",time())]);
       }

    }
}
