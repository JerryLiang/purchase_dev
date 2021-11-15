<?php

/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */
class Supplier extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Supplier_model');
        $this->load->model('Supplier_payment_account_model', 'paymentAccountModel');
        $this->load->model('Supplier_contact_model', 'contactModel');
        $this->load->model('Supplier_images_model', 'imagesModel');
        $this->load->model('Supplier_audit_results_model', 'auditModel');
        $this->load->model('Supplier_product_line_model', 'productLineModel');
        $this->load->model('Supplier_buyer_model', 'buyerModel');
        $this->load->model('Supplier_settlement_model', 'settlementModel');
        $this->load->model('Supplier_address_model', 'addressModel');
        $this->load->model('Supplier_update_log_model', 'updateLogModel');
        $this->load->model('Supplier_payment_info_model','Supplier_payment_info_model');
        $this->load->model('user/Purchase_user_model', 'userModel');
        $this->load->helper('status_supplier');
        $this->_modelObj = $this->Supplier_model;
    }
    public function supplier_data() {
        $params = gp();
        $this->load->model('product/Product_line_model', 'Product_line_model');
                //下拉列表采购员
        $data['down_buyer'] = $this->buyerModel->get_buyers($params);
        if (!empty($data['down_buyer']['list'])) {
            array_unshift($data['down_buyer']['list'], ['buyer_id'=>0,'buyer_name'=>'空']);

        }
        if(isset($params['overseas_ids']) && !empty($params['overseas_ids']))
        {

            $params['overseas_supplier_code'] = ['2'];
                $groupids = $this->buyerModel->getUserData($params['overseas_ids'],2);
                if(!empty($groupids)){

                    $params['overseas_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
                }


        }


        if( isset($params['domestic_ids']) && !empty($params['domestic_ids'])){

            $params['domestic_supplier_code'] = ['2'];
            $groupids = $this->buyerModel->getUserData($params['domestic_ids'],1);
            if(!empty($groupids)){

                $params['domestic_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        //下拉列表供应商
        $data['down_supplier'] = $this->_modelObj->get_by_supplier_list($params);


        //下拉列表创建人
        $data['down_create_user'] = $this->_modelObj->get_create_user_name_by_useid();

        //下拉列表供应商结算方式
        $data['down_settlement'] = $this->settlementModel->get_settlement($params);


        //下拉列表一级产品线
        $data['down_oneline'] = $this->Product_line_model->get_product_line_list_first();


        // 供应商禁用启用
        $data['down_disable'] = getSupplierDisable();
        $data['is_gateway'] = getSupplierGateWays();



        //下拉列表合作状态
        $data['down_cooperation_status'] = getCooperationStatus();

        //下拉列表供应商来源
        $data['down_supplier_source'] = getSupplierSource();

        $data['down_supplier_level'] = getSupplierLevel();
          
        $data['cooperation_letter'] = [1=>'有',2=>'无'];

        $data['is_relation'] = [1=>'是',2=>'否'];


        $data['down_supplier_relation_type'] = getSupplierRelationType();

        $data['down_supplier_relation_reason'] = getSupplierRelationReason();

        $data['down_platform_source']  = [1=>'线下',2=>'线上阿里店铺',3=>'线上淘宝',4=>'线上拼多多',5=>'线上京东',6=>'其他线上平台'];

        $data['down_develop_source']    = [1=>'门户',2=>'产品'];

        $data['down_business_line']    = [1=>'国内',2=>'海外',3=>'国内/海外'];

        $data['down_is_postage']    = getIsPostage();



        $data['down_supplier_visit_status']    =     show_supplier_visit_status();//拜访状态
        $data['down_supplier_visit_depart']    =     show_supplier_visit_depart();//申请部门
        $data['down_supplier_visit_aim']       =     show_supplier_visit_aim();//拜访目的
        $data['down_supplier_visit_apply']     =   $this->userModel->get_user_all_list();//采购系统所有系统人员
        $data['down_supplier_visit_type']      =   [1=>'贸易商',2=>'工厂'];//采购系统所有系统人员
        $data['down_supplier_visit_cate']      =   [1=>'单一类目',2=>'交叉类目'];//采购系统所有系统人员
        $data['down_province']                 =    $this->supplier_model->get_province();
        $data['down_visit_answer_config']      =     $this->supplier_model->get_visit_answer_config();







        $data['total_all'] = $this->_modelObj->supplier_data_sum($params);
        $this->send_data($data, '供应商选择信息', true);
    }
 
    /**
     * @desc 获取供应商信息列表
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function index()
    {

        $params = gp();
        $data = array();

        if(isset($params['overseas_ids']) && !empty($params['overseas_ids']))
        {
            $params['overseas_supplier_code']  = ['2'];
            $groupids = $this->buyerModel->getUserData($params['overseas_ids'],2);
            if(!empty($groupids)){

                $params['overseas_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        if( isset($params['domestic_ids']) && !empty($params['domestic_ids'])){
            $params['domestic_supplier_code'] =['2'];
            $groupids = $this->buyerModel->getUserData($params['domestic_ids'],1);
            if(!empty($groupids)){

                $params['domestic_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }
        $data['data_list'] = $this->_modelObj->get_by_page($params);
        $down_settlement = $this->settlementModel->get_settlement($params);
        $down_settlement = array_column($down_settlement['list'],NULL,'settlement_code');
        $data['data_list']['drop_down_box']['down_settlement'] = $down_settlement;
        $data['data_list']['drop_down_box']['down_tapDateStr_diff'] = ['1' => '未修改','2' => '已修改','3' => '-'];

//        if( !empty($data['data_list']) ) {
//            foreach( $data['data_list']['list'] AS $key=>$value ) {
//                if( isset($down_settlement[$value['supplier_settlement']]) ) {
//
//                    $data['data_list']['list'][$key]['supplier_settlement'] = $down_settlement[$value['supplier_settlement']]['settlement_name'];
//                }
//            }
//        }
//        //增加供应商三个月的统计金额
//        $this->load->model('purchase/Purchase_order_model', 'purchaseOrderModel');
//        $data['data_list']['count'] =1;
//        $this->purchaseOrderModel->statistics_cooperation_amount($data['data_list']);



        $this->send_data($data, '供应商信息列表', true);
    }

    /**
     * @desc 获取供应商信息详情
     * @author Jackson
     * @Date 2019-01-22 16:01:00
     * @return array()
     **/
    public function get_details()
    {

        $settlement_code_name_map = $this->settlementModel->get_code_by_name_list();
        $platform_source_arr = [1=>'线下',2=>'线上阿里店铺',3=>'线上淘宝',4=>'线上拼多多',5=>'线上京东',6=>'其他线上平台'];


        $id = gp('supplier_id');
        if (empty($id)) {
            $this->send_data(null, "supplier_id不能为空", false);
        }
        $all_data = [];
        $data = $this->_modelObj->get_details($id);
        if (!empty($data['list'])) {
            $this->load->helper('status_order');
            $this->load->model('product/Product_line_model', 'Product_line_model');

            $all_data['basis_data'] = $data['list'];
            $supplierCode = $data['list'][0]['supplier_code'];
            $supplier_id = $data['list'][0]['prod_supplier_id'];
            if($all_data['basis_data'] and $all_data['basis_data'][0]){// 产品线转中文
                $all_data['basis_data'][0]['first_product_line']  = $this->Product_line_model->get_product_line_name($all_data['basis_data'][0]['first_product_line']);
                $all_data['basis_data'][0]['second_product_line'] = $this->Product_line_model->get_product_line_name($all_data['basis_data'][0]['second_product_line']);
                $all_data['basis_data'][0]['third_product_line']  = $this->Product_line_model->get_product_line_name($all_data['basis_data'][0]['third_product_line']);
            }

            //创建部门
            $all_data['basis_data'][0]['department_type'] = getSupplierDepartmentType($data['list'][0]['department_type']??'');
            //类目属性
            $all_data['basis_data'][0]['category_type'] = getCategoryType($data['list'][0]['category_type']??'');
            //近一个月预估开发数量
            $all_data['basis_data'][0]['one_mouth_num_type'] = getOneMouthNumType($data['list'][0]['one_mouth_num_type']??'');
            //近六个月预估开发数量
            $all_data['basis_data'][0]['six_mouth_num_type'] = getSixMouthNumType($data['list'][0]['six_mouth_num_type']??'');
            //供应商定级
            $all_data['basis_data'][0]['prod_supplier_level'] = getProdSupplierLevel($data['list'][0]['prod_supplier_level']??'');
           //供应商等级
            $all_data['basis_data'][0]['supplier_level'] = getSupplierLevel($data['list'][0]['supplier_level']??'');

            //供应商等级
            $all_data['basis_data'][0]['supplier_level'] = (getSupplierLevel($data['list'][0]['supplier_level']??''))?:0;

            //平台来源

            $all_data['basis_data'][0]['platform_source'] = $platform_source_arr[$data['list'][0]['platform_source']]??'';


            //包含类目
            $contains_category = $this->_modelObj->get_category_info($supplier_id);
            $contains_category = empty($contains_category)?'':implode(',',$contains_category);
            $all_data['basis_data'][0]['contains_category'] = $contains_category;

            //供应商省市区中文


            if (!empty($all_data['basis_data'][0]['ship_province'])) {
                $all_data['basis_data'][0]['ship_province_name'] = $this->addressModel->get_address_name_by_id($all_data['basis_data'][0]['ship_province']);


            }

            if (!empty($all_data['basis_data'][0]['ship_city'])) {
                    $all_data['basis_data'][0]['ship_city_name'] = $this->addressModel->get_address_name_by_id($all_data['basis_data'][0]['ship_city']);


            }

            if (!empty($all_data['basis_data'][0]['ship_area'])) {
                        $all_data['basis_data'][0]['ship_area_name'] = $this->addressModel->get_address_name_by_id($all_data['basis_data'][0]['ship_area']);


            }


            //获取供应商采购员
            $buyerData = $this->buyerModel->get_buyer_list($supplierCode);
            $all_data['buyer_data'] = $buyerData;
            //供应商等级下拉
            //$all_data['supplier_level'] = getSupplierLevel();
            //供应商来源
            $all_data['supplier_source'] = getSupplierSource();

            //财务结算
            $all_data['payment_data'] = $this->Supplier_payment_info_model->supplier_payment_info($supplierCode);

            if (!empty($all_data['payment_data'])) {
                foreach ($all_data['payment_data'] as $is_tax=>$payment_detail ) {
                    foreach ($payment_detail as $purchase_type_id=> $payment) {
                        if (!empty($payment['supplier_settlement'])) {
                                $settlement_name = $settlement_code_name_map[$all_data['payment_data'][$is_tax][$purchase_type_id]['supplier_settlement']]??'';
                                $settlement_type_name = $settlement_code_name_map[$all_data['payment_data'][$is_tax][$purchase_type_id]['settlement_type']]??'';

                            $all_data['payment_data'][$is_tax][$purchase_type_id]['supplier_settlement_type']=$settlement_type_name.'/'.$settlement_name;



                        }


                    }


                }

            }




            //获取联系方式(供应商联系方式)
            $contactData = $this->contactModel->get_contact_list($supplierCode);
            $logsData = $this->updateLogModel->getUpdateLogs($supplierCode);
            $all_data['contact_data'] = $contactData;
            $relation_info_show = [];
            $relation_info = $this->supplier_model->get_relation_supplier_detail($supplierCode);
            if (!empty($relation_info)) {
                foreach ($relation_info as $re_info) {
                    $relation_info_show[] = $re_info;

                }

            }


            $all_data['relation_info'] = $relation_info_show;






            //获取上传附件(供应商附图)
            $imagesData = $this->imagesModel->get_image_list($supplierCode);
            $new_images_data = [];
            if (!empty($imagesData)) {
                foreach ($imagesData as $single_key=>$single) {
                    if (!empty($single['image_url'])) {
                        $new_images_data[] = $single;

                    }

                }

            }

            $all_data['images_data'] = $new_images_data;


            //历史资料图片
            $history_image = $this->imagesModel->get_image_list($supplierCode,2);
            $all_data['history_image_data'] = $history_image;

            //下拉列表采购员
            $buyers = $this->buyerModel->get_buyers();
            $all_data['down_buyer'] = $buyers['list'];

            //下拉列表创建人
            $all_data['down_create_user'] = $this->_modelObj->get_create_user_name_by_useid();

            //下拉列表部门
            $all_data['down_department'] = getPurchaseType();//$this->buyerModel->get_buyers($params);
            $all_data['suplier_enable_list'] = getSupplierEnable();

            //下拉列表一级产品线
            $productline = $this->Product_line_model->get_product_line_list_first();
            $all_data['down_oneline'] = $productline;

            //下拉列表获取城市-省份-区县
            $address = $this->addressModel->get_address_by_Level();
            $all_data['down_address'] = $address;
            
            //下拉列表结算方式
            $settlement = $this->settlementModel->get_settlement_combine_convert();
            $all_data['down_settlement'] = $settlement;
            //下拉获取开户行主
            $payment_platform=$this->paymentAccountModel->get_payment_platform_bank();
            $all_data['payment_platform_bank'] = $payment_platform;

            //下拉更改结算方式原因
            $all_data['down_settlement_change_res'] = getSettlementChangeRes();

            //关联供应商，关联原因和关联模式
            $all_data['down_supplier_relation_type'] = getSupplierRelationType();
            $all_data['down_supplier_relation_reason'] = getSupplierRelationReason();

       /*     //更新供应商账期
            if ($all_data['basis_data'][0]['shop_id']) {


                $this->supplier_model->update_supplier_quota($all_data['basis_data'][0]['shop_id'],$all_data['basis_data'][0]['supplier_code'],$all_data['basis_data'][0]);
            }*/


            $this->send_data($all_data, '供应商信息详情', true);
        }else{
            $this->send_data([], '未查询到数据', false);
        }

    }


    /**
     * @desc 批量修改采购员
     * @link supplier/supplier/update_supplier_buyer
     * @author Jaden
     * @Date 2019-05-14 16:01:00
     * @return array()
     **/
    public function update_supplier_buyer(){
        $this->load->helper('status_order');
        $parames = gp();
        if (empty($parames)) {
            $this->send_data(null, "数据为空", false);
        }
        $error_msg = '';
        $supplier_code_arr = array();
        $supplier_ids = $parames['supplier_basic'];
        $supplier_ids_arr = explode(',', $supplier_ids['id']);
        $supplier_buyer_list = $parames['supplier_buyer'];    
        try{
            foreach ( $supplier_ids_arr  as  $supplier_id) {
                $old_supplier_basic = $this->Supplier_model->find_supplier_one($supplier_id, null, null, false);
                if(empty($old_supplier_basic)){
                    $error_msg.='该供应商不存在';
                    throw new Exception("该供应商不存在");
                }
                $supplier_code      = $old_supplier_basic['supplier_code'];
                $supplier_name      = $old_supplier_basic['supplier_name'];

           /*     $update_log_record = $this->updateLogModel->get_latest_audit_result($supplier_code);
                if($update_log_record
                    and !in_array($update_log_record['audit_status'],[
                        SUPPLIER_PURCHASE_REJECT,
                        SUPPLIER_SUPPLIER_REJECT,
                        SUPPLIER_FINANCE_REJECT,
                        SUPPLIER_REVIEW_PASSED])){
                    $error_msg.="供应商【".$supplier_name."】存在【未完结】的更新记录";
                    throw new Exception("供应商【".$supplier_name."】存在【未完结】的更新记录");
                }*/
                $supplier_code_arr[] = $supplier_code;
            }
            if(empty($error_msg)){
                $this->load->model('supplier/supplier_model');
                $this->load->model('supplier/supplier_buyer_model');
                $this->load->model('user/purchase_user_model');
                $this->load->model('purchase_suggest/purchase_suggest_model');
                $this->load->model('purchase/purchase_order_model');
                foreach ($supplier_code_arr as $val_supplier_code) {
                    $supplierInfo = $this->supplier_model->get_supplier_info($val_supplier_code,false);
                    foreach ($supplier_buyer_list as $key => $value) {
                        if(empty($value['buyer_id']) or !in_array($value['buyer_type'], [1, 2, 3,10])) continue;
                        $buyer      = $this->buyerModel->get_buyers();
                        $buyer_list = array_column($buyer['list'], 'buyer_name', 'buyer_id');
                        $buyer_name = isset($buyer_list[$value['buyer_id']]) ? $buyer_list[$value['buyer_id']] : '';
                        $updateNew  = [
                            'supplier_code' => $val_supplier_code,
                            'supplier_name' => $supplierInfo['supplier_name'],
                            'buyer_type'    => $value['buyer_type'],
                            'buyer_id'      => $value['buyer_id'],
                            'buyer_name'    => $buyer_name
                        ]; 
                        $updateBefore = $this->supplier_buyer_model->get_buyer_one($val_supplier_code,$value['buyer_type']);

                        $opr_name = in_array($value['buyer_type'],[1,2,3])?getPurchaseType($value['buyer_type']):'对账员';


                        if(empty($updateBefore)){
                            $res = $this->db->insert($this->supplier_buyer_model->table_nameName(),$updateNew);
                            //保存修改日志
                            operatorLogInsert(
                                [
                                    'id'      => $val_supplier_code,
                                    'type'    => 'supplier_update_log',
                                    'content' => "供应商新增采购员:".$opr_name.";".$buyer_name,
                                    'detail'  => "供应商新增采购员:".$opr_name.";".$buyer_name
                                ]
                            );
                            // 采购需求、待采购经理审核中的采购单的采购员都要一起变更
                            $demand_number_list= $this->purchase_order_model->update_order_buyer($value['buyer_type'],$val_supplier_code,$value['buyer_id'],$buyer_name);                  
                            $this->purchase_suggest_model->update_suggest_buyer($value['buyer_type'],$val_supplier_code,$value['buyer_id'],$buyer_name,$demand_number_list);

                        }else{
                            $res = $this->db->where('supplier_code', $val_supplier_code)->where('buyer_type',$value['buyer_type'])->update($this->supplier_buyer_model->table_nameName(), $updateNew);                      
                            //保存修改日志
                            operatorLogInsert(
                                [
                                    'id'      => $val_supplier_code,
                                    'type'    => 'supplier_update_log',
                                    'content' => "供应商修改采购员:".$opr_name.";修改前;".$updateBefore['buyer_name'].',修改后;'.$buyer_name,
                                    'detail'  => "供应商修改采购员:".$opr_name.";修改前;".$updateBefore['buyer_name'].',修改后;'.$buyer_name
                                ]
                            );

                            // 采购需求、待采购经理审核中的采购单的采购员都要一起变更
                            $demand_number_list= $this->purchase_order_model->update_order_buyer($value['buyer_type'],$val_supplier_code,$value['buyer_id'],$buyer_name);                  
                            $this->purchase_suggest_model->update_suggest_buyer($value['buyer_type'],$val_supplier_code,$value['buyer_id'],$buyer_name,$demand_number_list);

                        }
                        $data_list[]=$updateNew; 
                    }
                    
                }
                if($res){
                   
                    $this->send_data(NULL, '批量修改成功!', true);
                }
                
            }
                
        } catch (Exception $e) {
            $this->send_data(NULL, $e->getMessage(), false);
        }



    }
    /**
     * 天眼验证供应商是否正确
     * @author harvin
     * @date 2019-07-13
     */
    public function heaven_suppler($param = [],$type = 1){
        $supplier_source=$this->input->get_post('supplier_source'); //供应商统一代码
        $id = $this->input->get_post('id');
        if(empty($supplier_source) && isset($param['supplier_source']) && $param['supplier_source']){
            $supplier_source = $param['supplier_source'];
        }
        if(empty($id) && isset($param['id']) && $param['id']){
            $id = $param['id'];
        }

        if(empty($supplier_source)||empty($id)){
            $this->error_json('参数错误');
        }

        //信用代码是唯一的  先判断是否已经存在
        $this->load->model('Supplier_model', 'm_supplier', false, 'supplier');
        if (!$this->m_supplier->check_credit_code($supplier_source,$id)){
            $this->error_json('该信用代码已经存在');
        }
        

         //验证天眼查 是否正确  如果是临时 就不需要验证
        $eye_url=EYE_IP_HEAVEN.$supplier_source;
        $header = array('Authorization:57c7572b-4bc9-4c95-a149-6d843cd207a6');
        $result= getCurlData($eye_url,[],'GET',$header);
        $result= json_decode($result,TRUE);
        
        if($type == 2){//更新或者启用时需要验证  直接返回
            return $result;
        }
        if(isset($result['reason'])&&$result['reason']=='ok'){
            $register_date = substr($result['result']['estiblishTime'], 0, -3);
            $register_date = date('Y-m-d H:i:s',$register_date);
            $return_data = [
                'name' =>  $result['result']['name'],
                'register_date' =>  $register_date,//注册日期
                'register_address' => $result['result']['regLocation'],//注册地址
            ];
           $this->success_json($return_data);
        }else{
            $this->error_json('调用天眼失败');
        }     
    }

    /*
     * @desc天眼刷新供应商信息
     */
   public function heaven_refresh_supplier()
    {
        $credit_code = $this->input->get_post('credit_code'); //社会信用码
        $id          = $this->input->get_post('id');
        if (empty($credit_code)||empty($id)) $this->error_json('参数错误');
         $this->db->select('*');
        $this->db->where('id',$id);
        $this->db->from('supplier');
        $supplier_info = $this->db->get()->row_array();


        //if (empty($supplier_info['credit_code'])) $this->error_json('统一社会信用代码缺失');

        $eye_url=EYE_IP_HEAVEN.$credit_code;
        $header = array('Authorization:57c7572b-4bc9-4c95-a149-6d843cd207a6');
        $result= getCurlData($eye_url,[],'GET',$header);
        $return_result= json_decode($result,TRUE);

        if ($return_result['error_code'] == 0) {
            $result = $return_result['result'];

            $name = $result['name'];

            //注册时间
            $register_date = substr($result['estiblishTime'], 0, -3);
            $register_date = date('Y-m-d H:i:s',$register_date);

            $regLocation = $result['regLocation'];//注册地址

            $return_data = array();

            $return_data['name'] = $name;
            $return_data['estiblishTime'] = $register_date;
            $return_data['regLocation'] = $regLocation;


            if ($return_data['name']!=$supplier_info['supplier_name']){
                $return_data['sign'] = 1;//值改变了

            }

            if ($return_data['estiblishTime']!=$supplier_info['register_date']){
                $return_data['sign'] = 1;//值改变了

            }

            if ($return_data['regLocation']!=$supplier_info['register_address']){
                $return_data['sign'] = 1;//值改变了

            }


            //更新供应商基础信息
           // $this->db->where('id',$id);
           // $this->db->update('supplier', ['register_address'=>$return_data['regLocation'],'register_date'=>$return_data['estiblishTime'],'supplier_name'=>$return_data['name']]);
            $this->success_json($return_data);

        } else {
            $this->error_json('请求天眼查失败');
        }







    }

    /**
     * @desc 更新供应商相关信息
     * @author Jackson & Jolon
     * @Date 2019-01-22 16:01:00
     * @return array()
     **/
    public function update_supplier()
    {
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->library('alibaba/AliSupplierApi');
        $parames = gp();
        if (empty($parames)) {
            $this->send_data(null, "数据为空", false);
        }
        $is_change_diversion = 0;//是否临时转常规
        $need_finance_audit = 0;// 是否 需经财务部审核
        $need_supplier_audit = 1;//是否需要经过供应链审核
        $supplier_level_str = array_flip(getSupplierLevel());
        //是否账期优化
        $now_settlement     = [];//现在结算方式









        try {
            //结算方式map name转code
            $this->load->model('supplier/Supplier_settlement_model');
            $settlement_code_name_map = $this->Supplier_settlement_model->get_code_by_name_list();

            $is_force_submit  = isset($parames['is_force_submit'])?$parames['is_force_submit']:null;
            //更新基本信息
            if (isset($parames['supplier_basic']) && !empty($parames['supplier_basic']))  {
                $remark = isset($parames['supplier_basic']['remark']) ? $parames['supplier_basic']['remark'] : '';//申请备注
                $enabale_remark = isset($parames['supplier_basic']['enabale_remark']) ? $parames['supplier_basic']['enabale_remark'] : '';//启用原因

                if(isset($parames['supplier_basic']['remark'])) { unset($parames['supplier_basic']['remark']);};
                $new_supplier_basic      = $parames['supplier_basic'];
                if (!$new_supplier_basic['id'] || !is_numeric($new_supplier_basic['id'])) {
                    throw new Exception("更新ID不能为空或不是数字");
                }

                // 如果供应商为常规时，微信、QQ、电子邮箱 必填
                if($parames['supplier_basic']['supplier_source'] == 1){

                    if(empty($parames['supplier_contact'])){

                        throw new Exception("供应商来源为常规,请填写：微信、QQ、电子邮箱");
                    }
                    foreach($parames['supplier_contact'] as $contact_key=>$contact_value){
                        if( empty($contact_value['qq']) || empty($contact_value['micro_letter']) || empty($contact_value['email'])){
                            throw new Exception("供应商来源为常规,请填写：微信、QQ、电子邮箱");
                        }
                    }
                }
                $supplier_id        = $new_supplier_basic['id'];
                $supplierOldInfo    = $this->Supplier_model->find_supplier_one($supplier_id);
                $old_supplier_basic = $this->Supplier_model->find_supplier_one($supplier_id, null, null, false);
                if(empty($old_supplier_basic)){
                    throw new Exception("该供应商不存在");
                }

                $waiting_review_status_arr = [SUPPLIER_WAITING_PURCHASE_REVIEW,SUPPLIER_WAITING_SUPPLIER_REVIEW,SUPPLIER_FINANCE_REVIEW];
                if(in_array($old_supplier_basic['audit_status'],$waiting_review_status_arr)){
                    throw new Exception('该供应商处于待审状态，暂不可更新资料');
                }

                $supplier_code      = $old_supplier_basic['supplier_code'];

                $update_log_record = $this->updateLogModel->get_latest_audit_result($supplier_code);

                //验证是否已存在供应商名称
                $supplier_name = $new_supplier_basic['supplier_name'];

                $this->db->select('supplier_code,status');
                $this->db->where('supplier_name',$supplier_name);
                $this->db->from('supplier');
                $query = $this->db->get()->result_array();
                $flag = 0;
                $same = [];
                if (!empty($query)){
                    foreach ($query as $value){
                        if($value['supplier_code'] != $supplier_code && $value['status']==IS_ENABLE){
                            $flag++;
                            $same[] = $value['supplier_code'];
                        }
                    }
                }

                if($flag){
                    throw new Exception("与[".$same[0]."]的供应商同名，请禁用原供应商，或者在原供应商基础上进行更新");
                }

                // 验证供应商ID与名称是否一致（获取成功才需要验证）
                if(false and $new_supplier_basic['store_link']){// 屏蔽供应商ID不一致
                    $aliSupplierInfo = $this->alisupplierapi->getSupplierShopInfo(null,$new_supplier_basic['store_link']);
                    $aliSupplierInfo = json_decode($aliSupplierInfo, true);
                    $aliSupplierName = isset($aliSupplierInfo['data']['result']['companyName'])?$aliSupplierInfo['data']['result']['companyName']:'';
                    //（获取成功才需要验证）
                    if($is_force_submit != 1 and $aliSupplierName and trim($aliSupplierName) != trim($new_supplier_basic['supplier_name'])){
                        $this->send_data(['is_supplier_abnormal' => 1], '供应商名称与店铺名称不一致', false);
                    }
                }

                // 供应商  旧信息
                $old_supplier_buyer_list           = $supplierOldInfo['buyer_list'];
                $old_supplier_contact              = $supplierOldInfo['contact_list'];
                $old_supplier_images_list          = $supplierOldInfo['images_list'];
                $old_supplier_payment_info         = $supplierOldInfo['supplier_payment_info'];
                $old_supplier_contact              = arrayKeyToColumn($old_supplier_contact, 'id');
                $old_supplier_buyer_list           = arrayKeyToColumn($old_supplier_buyer_list, 'buyer_type');
                $old_supplier_images_list          = array_column($old_supplier_images_list, 'image_url', 'image_type');

                // 供应商 变更的信息
                $new_supplier_basic      = isset($parames['supplier_basic'])?$parames['supplier_basic']:null;

                //供应商等级转换
                $new_supplier_basic['supplier_level'] = $supplier_level_str[$new_supplier_basic['supplier_level']]??0;


                $new_supplier_contact    = isset($parames['supplier_contact'])?$parames['supplier_contact']:null;
                $new_supplier_payment_info = isset($parames['supplier_payment_info'])?$parames['supplier_payment_info']:null;
                $new_supplier_image      = isset($parames['supplier_image'])?$parames['supplier_image']:null;
                $new_supplier_buyer      = isset($parames['supplier_buyer'])?$parames['supplier_buyer']:null;
                $new_supplier_buyer      = arrayKeyToColumn($new_supplier_buyer, 'buyer_type');
                $payment_method = [];
                //如果是在x1,x2,x3,x4等级需要重新计算等级
                if (in_array($new_supplier_basic['supplier_level'],[10,11,12,13])) {
                    $cal_supplier_payment_info = [];
                    $cal_supplier_image = [];
                    foreach ($new_supplier_payment_info as $pay=>$pay_info) {
                        foreach ($pay_info as $cal_pay) {
                            $cal_supplier_payment_info[]=['supplier_settlement'=>$cal_pay['supplier_settlement']];

                        }
                    }
                    $cal_supplier_image[0]['cooperation_letter'] = $new_supplier_image['cooperation_letter'];

                    $new_supplier_basic['supplier_level'] = $this->_modelObj->cal_supplier_level($cal_supplier_payment_info,$cal_supplier_image);

                }
//pr($new_supplier_payment_info);exit;
                if ($new_supplier_payment_info){
                    foreach ($new_supplier_payment_info as $is_tax => $value){
                        foreach ($value as $business => $val){
                            $payment_method[$val['payment_method']] = 1;//用户选择了哪些支付方式
                            if (isset($val['payment_method']) && $val['payment_method'] == 1){//线上支付宝
                                if (empty($new_supplier_basic['store_link'])){
                                    throw new Exception("支付方式为“线上支付宝”时，“供应商链接”必填");
                                }
                            }
                        }
                    }
                }

                if($new_supplier_basic['supplier_source'] == 2){
                    if(!isset($new_supplier_image['company_reg']) || !$new_supplier_image['company_reg'] || !isset($new_supplier_image['tax_cer']) || !$new_supplier_image['tax_cer']){
                        throw new Exception("供应商来源为海外时，公司注册书、税务证明文件必填");
                    }
                }

                if(isset($new_supplier_basic['credit_code']) && $new_supplier_basic['credit_code']&&!empty($new_supplier_basic['is_complete'])){
                    $heaven_info = $this->heaven_suppler(['supplier_source'=>$new_supplier_basic['credit_code'],'id'=>$new_supplier_basic['id']],2);
//                    apiRequestLogInsert(
//                        [
//                            'record_type'      => '调用天眼数据',
//                            'post_content'     => json_encode(['supplier_source'=>$new_supplier_basic['credit_code'],'id'=>$new_supplier_basic['id']]),
//                            'response_content' => json_encode($heaven_info),
//                            'status'           => '1',
//                        ],
//                        'api_request_ali_log'
//                    );
                    if(!isset($heaven_info['reason']) || $heaven_info['reason'] !='ok') {
                        throw new Exception('调用天眼失败');
                    }
                }

                $delete_contact_ids      = isset($parames['delete_data']['contact']['id'])?$parames['delete_data']['contact']['id']:[];

             //   unset($new_supplier_basic['credit_code']);


                $change_data = [];// 修改的数据
                $insert_data = [];// 新增的数据（一对多关系的）
                $delete_data = [];// 删除的数据
                $change_data_log = [];// 修改日志            
                // 获取  供应商基础信息 改变的内容
                //如果1688账期额度改变，也记录修改
                $insert_payment_log = [];

                if($new_supplier_basic){

                    //如果1688账期里的信息变更记录到表内

                    if (($new_supplier_basic['tap_date_str']!= $old_supplier_basic['tap_date_str'])||($new_supplier_basic['quota']!= $old_supplier_basic['quota'])||($new_supplier_basic['surplus_quota']!= $old_supplier_basic['surplus_quota'])) {
                        $opr_type = $supplierOldInfo['status'] == 1 ? SUPPLIER_NORMAL_UPDATE : ($supplierOldInfo['status'] == 2 ? SUPPLIER_RESTART_FROM_DISABLED :SUPPLIER_RESTART_FROM_FAILED);


                        foreach ($new_supplier_payment_info as $is_tax => $item) {
                            foreach ($item as $purchase_type_id => $value) {
                                if (($old_supplier_payment_info[$is_tax][$purchase_type_id]['supplier_settlement'] == $new_supplier_payment_info[$is_tax][$purchase_type_id]['supplier_settlement'])&&($new_supplier_payment_info[$is_tax][$purchase_type_id]['supplier_settlement'] == 20)) {

                                    $payment_log = [
                                        'supplier_code'=>$supplier_code,
                                        'opr_user'=>getActiveUserName(),
                                        'opr_user_id'  =>getActiveUserId(),
                                        'opr_time'=>date('Y-m-d H:i:s'),
                                        'old_supplier_settlement'=>'1688账期'.($old_supplier_basic['tap_date_str']??''),
                                        'new_supplier_settlement'=>'1688账期'.($new_supplier_basic['tap_date_str']??''),
                                        'opr_type'=> $opr_type,
                                        'remark'  =>'[申请备注]:'.$remark,
                                        'purchase_type_id'=>$purchase_type_id,
                                        'is_tax'=>$is_tax,
                                        'content'=>json_encode(['quota'=>$supplierOldInfo['quota'],'surplus_quota'=>$supplierOldInfo['surplus_quota'],'tap_date_str'=>$supplierOldInfo['tap_date_str']]).','.json_encode(['quota'=>$new_supplier_basic['quota'],'tap_date_str'=>$new_supplier_basic['tap_date_str'],'surplus_quota'=>$new_supplier_basic['surplus_quota']])


                                    ];




                                    $insert_payment_log[] = $payment_log;//支付方式改变

                                }

                            }
                        }


                    }


                    foreach($new_supplier_basic as $key => $value){
                        // 是否开票:由是->否，需经财务部审核。其他变更方向不需要财务审核
                        if($key == 'invoice'){

                            if ($old_supplier_basic[$key] == INVOICE_TYPE_ADDED_VALUE_TAX and $value == INVOICE_TYPE_NONE) {
                                $need_finance_audit = 1;
                            }
                            if($value == INVOICE_TYPE_ADDED_VALUE_TAX){//是否开票=是，那么开票税点≠0，
                                if (empty($new_supplier_basic['invoice_tax_rate'])){
                                    throw new Exception("是否开票=是，那么开票税点不能等于0,开票税点必填");
                                }
                            }
                            $now_invoice = $value;//当前是否开票;
                            if (isset($now_invoice) && $now_invoice == INVOICE_TYPE_NONE){//是否开票=否, 不存在含税
                                $no_tax_data = $old_supplier_payment_info[PURCHASE_IS_DRAWBACK_Y]??[];
                                if (!empty($no_tax_data)){//含税的数据不为空
                                    $no_tax_ids = array_column($no_tax_data,'id');
                                    foreach ($no_tax_ids as $no_tax_id){
                                        //需将历史含税的数据进行删除
                                        $change_data['payment_data'][PURCHASE_IS_DRAWBACK_Y][] = [
                                            'id' => $no_tax_id,
                                            'is_del' => 1,
                                            //更新信息
                                            'update_time' => date('Y-m-d H:i:s'),
                                            'update_user_name' => getActiveUserName(),
                                        ];
                                    }
                                }
                            }
                        }

                        $value = ($value ==''?0:$value);
                        if(isset($old_supplier_basic[$key]) and $old_supplier_basic[$key] != $value){
                            //临时转常规添加标识
                            if ($key=='supplier_source') {
                                if ($old_supplier_basic[$key]==3&&$new_supplier_basic[$key]==1) {
                                    $is_change_diversion = 1;//是否临时转常规标识
                                    //需要更新标识
                                    $change_data['basis_data']['is_diversion'] = 1;
                                    $res = $this->db->where('supplier_code', $supplierOldInfo['supplier_code'])->update('supplier', ['is_diversion_status'=>2]);//变为转化中标识

                                }

                            }

                            //如果是店铺账期那些信息不记录字段
                    /*        if (in_array($key,['tap_date_str','quota','surplus_quota'])) {
                                continue;

                            }*/



                            $change_data['basis_data'][$key] = $value;
                            //修改日志
                            if( in_array($key, array('ship_province','ship_city','ship_area')) ){
                                if(!empty($old_supplier_basic[$key])){
                                    $old_address = $this->addressModel->get_address_name_by_id($old_supplier_basic[$key]);   
                                }else{
                                    $old_address = '';
                                }
                                $new_address = $this->addressModel->get_address_name_by_id($value);
                                $change_data_log['basic'][$key] = '修改前:'.$old_address.';修改后:'.$new_address;
                            }else{
                                switch($key){// 需要转换成 中文
                                    case 'invoice':
                                        $before_v = supplier_ticket($old_supplier_basic[$key]);
                                        $current_v = supplier_ticket($value);
                                        break;
                                    case 'is_cross_border':
                                        $before_v = getCrossBorder($old_supplier_basic[$key]);
                                        $current_v = getCrossBorder($value);
                                        break;
                                    case 'supplier_type':
                                        $before_v = supplier_type($old_supplier_basic[$key]);
                                        $current_v = supplier_type($value);
                                        break;
                                    case 'settlement_type':
                                        $before_v = $this->settlementModel->get_settlement_one($old_supplier_basic[$key]);
                                        $current_v = $this->settlementModel->get_settlement_one($value);
                                        $before_v = isset($before_v['settlement_name'])?$before_v['settlement_name']:'';
                                        $current_v = isset($current_v['settlement_name'])?$current_v['settlement_name']:'';
                                        break;
                                    case 'supplier_settlement':
                                        $before_v = $this->settlementModel->get_settlement_one($old_supplier_basic[$key]);
                                        $current_v = $this->settlementModel->get_settlement_one($value);
                                        $before_v = isset($before_v['settlement_name'])?$before_v['settlement_name']:'';
                                        $current_v = isset($current_v['settlement_name'])?$current_v['settlement_name']:'';
                                        break;
                                    case 'is_complete':
                                        $before_v = getComplete($old_supplier_basic[$key]);
                                        $current_v = getComplete($value);
                                        break;
                                    case 'supplier_level':
                                        $before_v = getSupplierLevel($old_supplier_basic[$key]);
                                        $current_v = getSupplierLevel($value);
                                        break;
                                    case 'is_agent':
                                        $before_v = getIsAgent($old_supplier_basic[$key]);
                                        $current_v = getIsAgent($value);
                                        break;
                                    case 'is_postage':
                                        $before_v = getIsPostage($old_supplier_basic[$key]);
                                        $current_v = getIsPostage($value);
                                        break;
                                    case 'supplier_source':
                                        $before_v = getSupplierSource($old_supplier_basic[$key]);
                                        $current_v = getSupplierSource($value);
                                        break;
                                    default :
                                        $before_v = $old_supplier_basic[$key];
                                        $current_v = $value;
                                }

                                $change_data_log['basic'][$key] = '修改前:'.$before_v.';修改后:'.$current_v;
                            }
                        }
                    }
                }
                // 获取  供应商联系人信息 改变的内容
                if($new_supplier_contact){
                    foreach($new_supplier_contact as $value1){
                        $contact_id = $value1['id'];
                        if(!isset($old_supplier_contact[$contact_id])){
                            $insert_data['contact_data'][] = $value1;
                            continue;
                        }
                        foreach($value1 as $key2 => $value2){
                            if(isset($old_supplier_contact[$contact_id][$key2]) and $old_supplier_contact[$contact_id][$key2] !== $value2){
                                $change_data['contact_data'][$contact_id][$key2] = $value2;
                                //修改日志
                                $change_data_log['contact'][$contact_id][$key2] = '修改前:'.$old_supplier_contact[$contact_id][$key2].';修改后:'.$value2;
                            }
                        }
                    }
                }
                // 获取  供应商基采购员信息 改变的内容
                if($new_supplier_buyer){
                    foreach($new_supplier_buyer as $buyer_type => $value1){
                        if(!in_array($buyer_type,array_keys(getPurchaseType()))) continue;// 执行更新已存在的类型
                        if(!isset($old_supplier_buyer_list[$buyer_type])){
                            $insert_data['buyer_data'][] = $value1;
                            continue;
                        }
                        foreach($value1 as $key2 => $value2){
                            if(isset($old_supplier_buyer_list[$buyer_type][$key2]) and $old_supplier_buyer_list[$buyer_type][$key2] !== $value2){
                                $change_data['buyer_data'][$buyer_type][$key2] = $value2;
                                //修改日志
                                $change_data_log['buyer'][$buyer_type][$key2] = $old_supplier_buyer_list[$buyer_type][$key2].','.$value2;
                            }
                        }
                    }
                }
                // 获取  供应商财务结算信息 改变的内容




                if($new_supplier_payment_info){
                    foreach ($new_supplier_payment_info as $is_tax => $item){
                        foreach ($item as  $purchase_type_id=> $value){
                            $old_payment_info = $old_supplier_payment_info[$is_tax][$purchase_type_id]??[];

                            if (isset($now_invoice) && $now_invoice == INVOICE_TYPE_NONE){//是否开票=否, 不存在含税
                                if ( $is_tax == PURCHASE_IS_DRAWBACK_Y ) {//跳过含税的
                                    continue;
                                }
                            }
/*
                            $now_settlement[] = $value['supplier_settlement'];


                            $history_temp=$value;
                            if (isset($history_temp['id'])) {
                                unset($history_temp['id']);


                            }

                            if (isset($history_temp['supplier_settlement_type'])) {
                                unset($history_temp['supplier_settlement_type']);


                            }

                             $history_temp['is_del'] = 2;
                            $this->Supplier_payment_info_model->update_history_info($history_temp);*/




                            if ($value['payment_method'] == PURCHASE_PAY_TYPE_ALIPAY){// 2. 支付方式选择线上支付宝，供应商店铺ID必填。
                                if (empty($new_supplier_basic['shop_id'])){
                                    throw new Exception('支付方式选择线上支付宝，供应商店铺ID必填');
                                }
                            }elseif ($value['payment_method'] == PURCHASE_PAY_TYPE_PRIVATE){
                                if (empty($value['payment_platform'])){
                                    throw new Exception('支付方式选择线下境外，支付平台必填');
                                }
                            }

                            if (empty($old_payment_info)){
                                //新增
                                $insert_data['payment_data'][$is_tax][$purchase_type_id] = [
                                    'is_tax' => $is_tax,//是否含税
                                    'purchase_type_id' => $purchase_type_id,//业务线
                                    'supplier_code' => $value['supplier_code'],//供应商编码
                                    'settlement_type' => $value['settlement_type'],//结算方式(一级)
                                    'supplier_settlement' => $value['supplier_settlement'],//结算方式(二级)
                                    'payment_method' => $value['payment_method'],//支付方式
                                    'account_name' => trim($value['account_name']??''),//账号名称 收款人姓名
                                    'account' => trim($value['account']??''),//银行账号 收款账号
                                    'currency' => trim($value['currency']??''),//币种
                                    'bank_address' => trim($value['bank_address']??''),//开户行地址
                                    'swift_code' => trim($value['swift_code']??''),//swift代码
                                    'email' => trim($value['email']??''),//邮箱

                                    //线上支付宝
                                    'store_name' => trim($value['store_name']??''),//店铺名称

                                    //线下境内
                                    'payment_platform_bank' => trim($value['payment_platform_bank']??''),//开户行名称
                                    'payment_platform_branch' => trim($value['payment_platform_branch']??''),//开户行名称(支行)

                                    //线下境外
                                    'payment_platform' => $value['payment_platform']??'',//支付平台
                                    'phone_number' => trim($value['phone_number']??''),//收款人手机号
                                    'id_number' => trim($value['id_number']??''),//身份证号码

                                    'settlement_change_res' => $value['settlement_change_res'],//结算方式变更原因
                                    'settlement_change_remark' => $value['settlement_change_remark'],//结算方式变更原因_备注

                                    //更新信息
                                    'update_time' => date('Y-m-d H:i:s'),
                                    'update_user_name' => getActiveUserName(),

                                    //创建信息
                                    'create_time' => date('Y-m-d H:i:s'),
                                    'create_user_name' => getActiveUserName(),

                                ];
                            }else{
                                if (($old_payment_info['settlement_type'] != $value['settlement_type']) || ($old_payment_info['supplier_settlement'] != $value['supplier_settlement'])){
                                    if (empty($value['settlement_change_res'])){
                                        throw new Exception("结算方式变更的，那么结算方式变更原因必填");
                                    }
                                }


                                //更新
                                $change_data['payment_data'][$is_tax][$purchase_type_id] = [
                                    'id' => $old_payment_info['id'],
                                    'settlement_type' => $value['settlement_type'],//结算方式(一级)
                                    'supplier_settlement' => $value['supplier_settlement'],//结算方式(二级)
                                    'payment_method' => $value['payment_method'],//支付方式
                                    'account_name' => trim($value['account_name']??''),//账号名称 收款人姓名
                                    'account' => trim($value['account']??''),//银行账号 收款账号
                                    'currency' => trim($value['currency']??''),//币种
                                    'bank_address' => trim($value['bank_address']??''),//开户行地址
                                    'swift_code' => trim($value['swift_code']??''),//swift代码
                                    'email' => trim($value['email']??''),//邮箱

                                    //线上支付宝
                                    'store_name' => trim($value['store_name']??''),//店铺名称

                                    //线下境内
                                    'payment_platform_bank' => trim($value['payment_platform_bank']??''),//开户行名称
                                    'payment_platform_branch' => trim($value['payment_platform_branch']??''),//开户行名称(支行)

                                    //线下境外
                                    'payment_platform' => $value['payment_platform']??'',//支付平台
                                    'phone_number' => trim($value['phone_number']??''),//收款人手机号
                                    'id_number' => trim($value['id_number']??''),//身份证号码

                                    'settlement_change_res' => empty($value['settlement_change_res'])?0:$value['settlement_change_res'],//结算方式变更原因
                                    'settlement_change_remark' => $value['settlement_change_remark'],//结算方式变更原因_备注

                                    //更新信息
                                    'update_time' => date('Y-m-d H:i:s'),
                                    'update_user_name' => getActiveUserName(),
                                ];
                            }
                            foreach ($value as $k => $val){
                                $val = ($val ==''?0:$val);
                                $old_val = $old_supplier_payment_info[$is_tax][$purchase_type_id][$k]??'';
                                switch($k) {// 需要转换成 中文
                                    case 'settlement_type':
                                        $old_val = $settlement_code_name_map[$old_val]??'';
                                        $val = $settlement_code_name_map[$val]??'';
                                        break;
                                    case 'supplier_settlement':
                                        $old_val = $settlement_code_name_map[$old_val]??'';
                                        $val = $settlement_code_name_map[$val]??'';
                                        break;
                                    case 'payment_method':
                                        $old_val = getPayType($old_val);
                                        $val = getPayType($val);
                                        break;
                                    case 'payment_platform':
                                        $old_val = get_supplier_payment_platform($old_val);
                                        $val = get_supplier_payment_platform($val);
                                        break;
                                    case 'settlement_change_res':
                                        $old_val = getSettlementChangeRes($old_val);
                                        $val = getSettlementChangeRes($val);
                                        break;
                                    default :
                                        break;
                                }
                                if ($old_val != $val){
                                    if ($k == 'supplier_settlement_type') {
                                        continue;

                                    }
                                    $change_meg = sprintf('修改前:%s;修改后:%s',$old_val,$val);
                                    $change_data_log['payment_data'][$is_tax][$purchase_type_id][$k] = $change_meg;
                                    //结算方式如果改变，添加进日志
                                    if ($k == 'supplier_settlement') {
                                        $opr_type = $supplierOldInfo['status'] == 1 ? SUPPLIER_NORMAL_UPDATE : ($supplierOldInfo['status'] == 2 ? SUPPLIER_RESTART_FROM_DISABLED :SUPPLIER_RESTART_FROM_FAILED);
                                 

                                        $payment_log = [
                                            'supplier_code'=>$supplier_code,
                                            'opr_user'=>getActiveUserName(),
                                            'opr_user_id'  =>getActiveUserId(),
                                            'opr_time'=>date('Y-m-d H:i:s'),
                                            'old_supplier_settlement'=>$old_val,
                                            'new_supplier_settlement'=>$val,
                                            'opr_type'=> $opr_type,
                                            'remark'  =>'[申请备注]:'.$remark,
                                            'purchase_type_id'=>$purchase_type_id,
                                            'is_tax'=>$is_tax


                                        ];

                                      if ($old_val == '1688账期') {
                                          $payment_log['content'] = json_encode(['quota'=>$supplierOldInfo['quota'],'tap_date_str'=>$supplierOldInfo['tap_date_str'],'surplus_quota'=>$supplierOldInfo['surplus_quota']]);

                                      } elseif ($val == '1688账期') {
                                          $payment_log['content'] = json_encode(['quota'=>$new_supplier_basic['quota'],'tap_date_str'=>$new_supplier_basic['tap_date_str'],'surplus_quota'=>$new_supplier_basic['surplus_quota']]);

                                      } else {
                                          $payment_log['content'] ='';
                                      }


                                        $insert_payment_log[] = $payment_log;//支付方式改变




                                    }
                                }
                            }
                        }
                    }
                }
                // 获取  供应商图片信息 改变的内容
                if($new_supplier_image){
         
                    foreach($new_supplier_image as $image_type => $value){
                        if(isset($parames['supplier_basic']['is_complete']) && $parames['supplier_basic']['is_complete'] == 0){
                            //资料不全时 不做图片验证
                        }else{
                            if(isset($payment_method[3])){//线下境外,营业执照+委托书+身份证复印件必填（暂时不添加上传身份证复印件的功能，与新产品系统对接后，新产品系统有该字段后再添加）
                                if($new_supplier_basic['supplier_source'] == 1){
                                    if( ($image_type=='busine_licen' && empty($value) ) || ($image_type=='collection_order' && empty($value)) || ($image_type=='idcard_front' && empty($value)) || ($image_type=='idcard_back' && empty($value)) )
                                        throw new Exception("线下境外支付支付,营业执照,委托书,身份证正反面必填");
                                }
                            }

                            if(isset($payment_method[2]) && isset($now_invoice) && $now_invoice == INVOICE_TYPE_ADDED_VALUE_TAX){//线下境内,营业执照+一般纳税人认定书+开票资料
                                if( ($image_type=='busine_licen' && empty($value) && $new_supplier_basic['supplier_source'] != 3) || ($image_type=='verify_book' && empty($value)) || ($image_type=='bank_information' && empty($value)) )
                                    throw new Exception("线下境内支付方式支付,营业执照,一般纳税人认定书,开票资料必填");
                            }

                            if(isset($payment_method[1])){//线上支付宝,营业执照
                                if( ($image_type=='busine_licen' && empty($value) && $new_supplier_basic['supplier_source'] != 3) )
                                    throw new Exception("线上支付宝方式支付,营业执照必填");
                            }
                        }

                        if(!isset($old_supplier_images_list[$image_type])  or empty($old_supplier_images_list[$image_type])){//原图为空 则新增
                            if (empty($value)) continue;//不写入空数据
                            $insert_data['images_data'][$image_type] = $value;
                            $need_finance_audit = 1;
                            continue;
                        }else{//更新
                            if($image_type != 'other_proof'){
                                if(isset($old_supplier_images_list[$image_type]) and $old_supplier_images_list[$image_type] !== $value){
                                    $need_finance_audit = 1;
                                    $change_data['images_data'][$image_type] = $value;
                                    //$change_data_log['images'][$image_type] = '修改前:'.$old_supplier_images_list[$image_type].';修改后:'.$value;
                                    if(!is_array($old_supplier_images_list[$image_type])){
                                        $change_data_log['images'][$image_type] = '修改前:<img src="'.$old_supplier_images_list[$image_type].'" width="40" height="40">;修改后:<img src="'.$value.'" width="40" height="40">';
                                    }else{
                                        $change_data_log['images'][$image_type] = '修改前:<img src="'.$old_supplier_images_list[$image_type][0].'" width="40" height="40">;修改后:<img src="'.$value.'" width="40" height="40">';
                                    }
                                }
                            }else{
                                // 比较出 其他资料 新增/删除的图片
                                if(strrpos($value,';') !== false){
                                    $new_img_list = explode(';',$value);
                                }else{
                                    $new_img_list = [$value];
                                }
                                if(is_array($old_supplier_images_list[$image_type])){// 一种类型有多张图片的
                                    $old_img_list = $old_supplier_images_list[$image_type];
                                }else{
                                    $old_img_list = [$old_supplier_images_list[$image_type]];
                                }

                                $add_url = array_diff($new_img_list,$old_img_list);
                                $del_url = array_diff($old_img_list,$new_img_list);

                                $change_data['images_data'][$image_type] = $value;
                                $other_proof_log = '';
                                if($add_url){
                                    if(is_string($add_url)) $add_url = [$add_url];
                                    $other_proof_log .= '<br/>新增图片:';
                                    foreach($add_url as $add_url_v){
                                        if(stripos($add_url_v,';') !== false){
                                            $images_value_list = explode(';',$add_url_v);
                                        }else{
                                            $images_value_list = [$add_url_v];
                                        }
                                        foreach($images_value_list as $images_value_tmp){
                                            $other_proof_log .= '<br/><img src="'.$images_value_tmp.'"  width="40" height="40">';
                                        }
                                    }
                                }
                                if($del_url){
                                    if(is_string($del_url)) $del_url = [$del_url];
                                    $other_proof_log .= '<br/>删除图片:';
                                    foreach($del_url as $del_url_v){
                                        $other_proof_log .= '<br/><img src="'.$del_url_v.'"  width="40" height="40">';
                                    }
                                }
                                if($other_proof_log)
                                    $change_data_log['images'][$image_type] = trim($other_proof_log,'<br/>');
                            }
                        }



                    }
                }




                if($delete_contact_ids){
                    foreach($delete_contact_ids as $contact_id){
                        if(isset($old_supplier_contact[$contact_id])){
                            $delete_data['contact_data'][] = $contact_id;
                            $delete_data_log['contact_data'][] = $old_supplier_contact[$contact_id];
                        }
                    }
                }

                //记录更新之前的状态值用以在驳回时确定状态变更值（禁用审核不通过以后还是禁用  审核不通过被驳回以后还是审核不通过）
                $change_data['before_check_status'] = $supplierOldInfo['status'];
                $operate_type = $supplierOldInfo['status'] == 1 ? SUPPLIER_NORMAL_UPDATE : ($supplierOldInfo['status'] == 2 ? SUPPLIER_RESTART_FROM_DISABLED :SUPPLIER_RESTART_FROM_FAILED);

                if(empty($change_data) and empty($insert_data) and empty($delete_data)){
                    // 所有 变更的信息
                    $all_change_data = [
                        'change_data' => [],
                    ];
                }else{
                    // 所有 变更的信息
                    $all_change_data = [
                        'change_data' => $change_data,
                        'insert_data' => $insert_data,
                        'delete_data' => $delete_data,
                        'change_data_log' => $change_data_log,
                    ];

                    $change_keys = array_keys($change_data);
                    if(count($change_keys) == 1 && isset($change_keys[0]) && $change_keys[0] == 'buyer_data' && empty($insert_data) && empty($delete_data)) $need_supplier_audit = 0;//只修改采购员不需要经过供应链审核


                    //修改日志
                    $all_change_data_log = [
                        'change_data_log' => $change_data_log,
                        'insert_data' => $insert_data,
                        'delete_data' => isset($delete_data_log)?$delete_data_log:[]
                    ];
                }
           
                // 生成一条 供应商数据变更待审核记录

                if ($is_change_diversion) {
                    $apply_type = 5;
                } else {
                    $apply_type = 2;//更新

                }

                //如果供应商只修改了是否包邮，不需要供应商审核

                if (isset($all_change_data['change_data_log']['basic']['is_postage'])&&(count($all_change_data['change_data_log']['basic'])==1)&&count($all_change_data['change_data_log']) == 1) {
                    $need_supplier_audit = 0;

                }
                $payment_optimization =  $this->supplier_model->check_payment_optimization(array_unique($now_settlement),$old_supplier_payment_info,$change_data['payment_data']);


                $insert_data = [
                    'supplier_code'    => $supplier_code,
                    'action'           => 'supplier/supplier/collect_update_supplier',
                    'message'          => json_encode($all_change_data),
                    'need_finance_audit' => $need_finance_audit,
                    'need_supplier_audit' => $need_supplier_audit,
                    'audit_status'     => SUPPLIER_WAITING_PURCHASE_REVIEW,
                    'create_user_id'   => getActiveUserId(),
                    'create_user_name' => getActiveUserName(),
                    'create_time'      => date('Y-m-d H:i:s'),
                    'apply_type'       =>$apply_type,
                    'apply_no'         =>$this->supplier_model->get_prefix_new_number('gy'),
                    'source'           =>1,
                    'payment_optimization'=>$payment_optimization
                ];



               /* if(isset($parames['flag']) && $parames['flag'] == 2){//是否是启用标识
                    $insert_data['audit_status'] = SUPPLIER_WAITING_SUPPLIER_REVIEW;
                    $insert_data['need_finance_audit'] = 1;
                    $insert_data['status'] = 4;
                    $insert_data['enable_remark'] = $enabale_remark;
                    $apply_type = 3;//启用
                }*/
                //保存修改日志

                operatorLogInsert(
                   [
                        'id'      => $supplier_code,
                        'type'    => 'supplier_update_log',
                        'content' => "供应商信息修改",
                        'detail'  => json_encode(isset($all_change_data_log)?$all_change_data_log:[]),
                        'ext'     => '[申请备注]:'.$remark,
                        'operate_type'  => $operate_type
                    ]
                );

                $result = $this->updateLogModel->insert_one($insert_data,$insert_payment_log);
                if(empty($result)){
                    throw new Exception("保存更新的数据失败");
                }

                $this->auditModel->create_record($supplier_code,$apply_type);// 创建供应商审核记录

           /*     //如果有供应商店铺id同步更新线上账期
                if ($new_supplier_basic['shop_id']){
                    $this->supplier_model->update_supplier_quota($new_supplier_basic['shop_id'],$supplier_code,$supplierOldInfo);

                }*/


            } else {
                throw new Exception("基础数据不能为空");
            }

            //$this->send_data(NULL, '更新数据保存成功!', true);

            $this->success_json('更新数据保存成功');
        } catch (Exception $e) {
            //$this->send_data(NULL, $e->getMessage(), false);
            $this->error_json($e->getMessage());

        }

    }

    /**
     * @desc 获取供应商信息详情-财务结算(供应商支付帐号信息)
     * @author Jackson
     * @Date 2019-01-22 16:01:00
     * @return array()
     **/
    public function get_payment()
    {
        $id = gp('id');
        if (empty($id)) {
            $this->send_data(null, "id不能为空", false);
        }
        $data = $this->_modelObj->get_details($id);
        $this->send_data($data, '供应商信息详情', true);
    }

    /**
     * @desc 获取下拉供应商列表
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function get_supplier_list()
    {
        $params = gp();
        $data   = $this->_modelObj->get_by_supplier_list($params);
        $status_label = [// 显示供应商状态
            '1' => '正常',
            '2' => '禁用',
            '3' => '删除',
            '4' => '待审',
            '5' => '驳回'
        ];
        if($data and is_array($data)){
            foreach($data as &$value){
                $value['supplier_name'] .= '('.(isset($status_label[$value['status']])?$status_label[$value['status']]:'').')';
            }
        }
        $this->send_data($data, '供应商列表', true);
    }

    /**
     * 供应商状态下拉列表
     * @author Jolon
     */
    public function get_status_list()
    {
        $status_type = $this->input->get_post('type');
        $this->load->helper('status_supplier');

        $status = 1;
        switch (strtolower($status_type)) {
            case 'shipping_method':
                $status_type_name = '运输方式';// 供应商运输方式
                $data_list = getShippingMethod();
                break;
            case 'cross_border':
                $status_type_name = '跨境宝供应商';
                $data_list = getCrossBorder();
                break;
            case 'pay_method':
                $status_type_name = '支付方式';// 供应商支付方式
                $data_list = getSupplierPayMethod();
                break;

            default :
                $status = 0;
                $status_type_name = '未知的状态类型';
                $data_list = null;
        }

        if ($status) {
            $this->success_json($data_list);
        } else {
            $this->error_json($status_type_name);
        }
    }

    /**
     * 根据查询条件获取供应商列表
     * @author Jolon
     */
    public function get_list()
    {
        $params['warehouse_code'] = $this->input->get_post('warehouse_code');
        $params['warehouse_name'] = $this->input->get_post('warehouse_name');

        $list = $this->_modelObj->get_list($params);

        $this->success_json($list);
    }

    /**
     * 获取供应商模糊查询
     * @author harvin 2019-1-19
     * @param srting $supplier_name 供应商名称或编码
     * http://www.caigou.com/supplier/Supplier/supplier_list
     * * */
    public function supplier_list()
    {
        $supplier_name = $this->input->get_post('supplier_name');//供应商名称或编码
        $status = $this->input->get_post('status');//状态（0.待审,1.正常,2.禁用,3.删除,4.审核不通过）
        //$supplier_name='深圳市';
        $data_list = [];
        if(strpos(trim($supplier_name)," ")){//多个供应商匹配以空格分隔
            $supplier_name_arr = array_unique(array_filter(explode(" ",$supplier_name)));
            foreach ($supplier_name_arr as $value){
                $result = $this->_modelObj->get_supplier_list($value,$status);
                $data_list = array_merge((array)$data_list,(array)$result);
            }
        }else{
            $data_list = $this->_modelObj->get_supplier_list(trim($supplier_name),$status);
        }

        $this->success_json($data_list);
    }




    /**
     * @desc
     * @author dean
     *
     * * */
    public function history_supplier_list()
    {
        $supplier_code = $this->input->get_post('supplier_code');//供应商编码
        $sku = $this->input->get_post('sku');//产品编码
        if (empty($supplier_code)) {
            $this->error_json('参数错误');

        }
        //从历史审核表找出以往供应商
        $data_list =[];
        $this->db->select('*');
        $this->db->where('sku',$sku);
        $this->db->where('old_supplier_code!=',$supplier_code);
        $this->db->from('product_update_log');
        $result = $this->db->get()->result_array();

        if (!empty($result)) {

            foreach ($result as $supplier_info) {
                $old_supplier_code = $supplier_info['old_supplier_code'];
                $data_list[$old_supplier_code]=$supplier_info['old_supplier_name'];

            }

        }

        $this->success_json($data_list);
    }




    /**
     * 获取供应商待审核的数据
     * @author Jolon
     */
    public function supplier_review_detail()
    {
    
        $id = $this->input->get_post('id');//审核日志id

        if(empty($id)){
            $this->send_data(null, '非法参数', true);
        }

        $settlement_code_name_map = $this->settlementModel->get_code_by_name_list();

        $update_log = $this->updateLogModel->update_log_detail($id);

        if(empty($update_log)){
            $this->send_data(null, '审核记录不存在', true);
        }
        $all_data = json_decode($update_log['message'],True);
        $supplier_code = $update_log['supplier_code'];


        $all_data['similar_contact_suppliers'] = [];//联系方式相似供应商
        $all_data['similar_payment_suppliers'] = [];//支付信息相似供应商
        $all_data['historyData']  = ['basis_data'=>[],'payment_data'=>[],'contact_data'=>[]];
        $all_data['logData']      =  [];//日志

        //历史备注/驳回原因
        $logData = $this->supplier_model->purchase_db->query("SELECT * FROM pur_operator_log WHERE (operate_type =" .SUPPLIER_NORMAL_UPDATE." AND record_number ='{$supplier_code}' AND record_type ='supplier_update_log' AND content='供应商信息修改') OR (operate_type = ".SUPPLIER_NORMAL_AUDIT." AND record_number='{$supplier_code}' AND record_type='supplier_update_log' AND content LIKE '%审核不通过%') order by id desc" )->result_array();
        if (!empty($logData)) {
            foreach ($logData as $log) {
                if ($log['operate_type'] == SUPPLIER_NORMAL_UPDATE ) {
                    $all_data['logData'][] =['operator'=>$log['operator'],'operate_type'=>getSupplierOperateType($log['operate_type']),'operate_time'=>$log['operate_time'],'content_detail'=>$log['ext']];

                } else {
                    $all_data['logData'][] =['operator'=>$log['operator'],'operate_type'=>getSupplierOperateType($log['operate_type']),'operate_time'=>$log['operate_time'],'content_detail'=>$log['content'].$log['content_detail']];


                }



            }

        }

        if (!empty($all_data)&&!empty($all_data['insert_data']['relation_supplier'])) {
            $down_settlement = $this->settlementModel->get_settlement();
            $down_settlement = array_column($down_settlement['list'],'settlement_name','settlement_code');

            foreach ($all_data['insert_data']['relation_supplier'] as &$relation) {
                $relation['relation_info'] = $this->supplier_model->get_relation_supplier_info($relation['relation_code'], $down_settlement);

            }

        }
        $now_contact_data = $this->supplier_contact_model->get_contact_list($supplier_code);

        $now_payment_info = $this->Supplier_payment_info_model->supplier_payment_info($supplier_code);







        //供应商省市区中文


        if (!empty($all_data['change_data']['basis_data']['ship_province'])) {
            $all_data['change_data']['basis_data']['ship_province_name'] = $this->addressModel->get_address_name_by_id($all_data['change_data']['basis_data']['ship_province']);


        }

        if (!empty($all_data['change_data']['basis_data']['ship_city'])) {
            $all_data['change_data']['basis_data']['ship_city_name'] = $this->addressModel->get_address_name_by_id($all_data['change_data']['basis_data']['ship_city']);


        }


        if (!empty($all_data['change_data']['basis_data']['ship_area'])) {
            $all_data['change_data']['basis_data']['ship_area_name'] = $this->addressModel->get_address_name_by_id($all_data['change_data']['basis_data']['ship_area']);


        }






        if(!empty($all_data) && isset($all_data['change_data']['contact_data'])){

            if(!empty($all_data['change_data']['contact_data'])){

                foreach ($all_data['change_data']['contact_data'] as $change_contact_key=>$change_contact_data) {
                    foreach ($now_contact_data as $now_contact_key=>$now_contact) {
                        if ($now_contact['id'] == $change_contact_key) {

                            if (isset($change_contact_data['micro_letter'])) {
                                $now_contact_data[$now_contact_key]['micro_letter'] = $change_contact_data['micro_letter'];


                            }
                            if (isset($change_contact_data['qq'])) {
                                $now_contact_data[$now_contact_key]['qq'] = $change_contact_data['qq'];


                            }

                            if (isset($change_contact_data['want_want'])) {
                                $now_contact_data[$now_contact_key]['want_want'] = $change_contact_data['want_want'];


                            }
                            if (isset($change_contact_data['email'])) {
                                $now_contact_data[$now_contact_key]['email'] = $change_contact_data['email'];


                            }

                            if (isset($change_contact_data['mobile'])) {
                                $now_contact_data[$now_contact_key]['mobile'] = $change_contact_data['mobile'];


                            }

                            if (isset($change_contact_data['contact_number'])) {
                                $now_contact_data[$now_contact_key]['contact_number'] = $change_contact_data['contact_number'];


                            }


                        }

                    }


                }



              /*  $searchCloseWhere = [];
                // 供应商微信号
                $microLetters = array_filter(array_column( $all_data['change_data']['contact_data'],'micro_letter'));
                if( !empty($microLetters) ){

                    $searchCloseWhere['micro_letter'] = $microLetters;
                }
                //  供应商QQ 号
                $supplierQQs = array_filter(array_column( $all_data['change_data']['contact_data'],'qq'));
                if( !empty($supplierQQs) ){

                    $searchCloseWhere['qq'] = $supplierQQs;
                }
                // 旺旺号
                $supplierWants = array_filter(array_column( $all_data['change_data']['contact_data'],'want_want'));
                if( !empty($supplierWants) ){

                    $searchCloseWhere['want_want'] = $supplierWants;
                }

                // 供应商邮箱
                $supplierEmails = array_filter(array_column( $all_data['change_data']['contact_data'],'email'));
                if(!empty($supplierEmails)){
                    $searchCloseWhere['email'] = $supplierEmails;
                }
                // 手机号
                $supplierMobiles = array_filter(array_column( $all_data['change_data']['contact_data'],'mobile'));
                if(!empty($supplierMobiles)){
                    $searchCloseWhere['mobile'] = $supplierMobiles;
                }

                if( !empty($searchCloseWhere)){

                    $colseSuppliers = $this->contactModel->getCloseSupplierData($searchCloseWhere,$supplier_code);
                    if( NULL != $colseSuppliers){
                        foreach($all_data['change_data']['contact_data'] as $contact_key=>$contact_value){

                            if(isset($contact_value['micro_letter']) ) {
                                if (isset($colseSuppliers['micro_letter'][$contact_value['micro_letter']])) {
                                    $all_data['change_data']['contact_data'][$contact_key]['micro_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['micro_letter'][$contact_value['micro_letter']]);
                                } else {
                                    $all_data['change_data']['contact_data'][$contact_key]['micro_suppliers'] = [];
                                }
                            }
                            if(isset($contact_value['qq'])) {

                                if (isset($colseSuppliers['qq'][$contact_value['qq']])
                                ) {
                                    $all_data['change_data']['contact_data'][$contact_key]['qq_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['qq'][$contact_value['qq']]);
                                } else {
                                    $all_data['change_data']['contact_data'][$contact_key]['qq_suppliers'] = [];
                                }
                            }
                            if(isset($contact_value['email']) && !empty($contact_value['email'])) {
                                if (isset($colseSuppliers['email'][$contact_value['email']])

                                ) {
                                    $all_data['change_data']['contact_data'][$contact_key]['email_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['email'][$contact_value['email']]);
                                } else {
                                    $all_data['change_data']['contact_data'][$contact_key]['email_suppliers'] = [];
                                }
                            }
                            if(isset($contact_value['want_want'])) {
                                if (isset($colseSuppliers['want_want'][$contact_value['want_want']])
                                ) {
                                    $all_data['change_data']['contact_data'][$contact_key]['want_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['want_want'][$contact_value['want_want']]);
                                } else {
                                    $all_data['change_data']['contact_data'][$contact_key]['want_suppliers'] = [];
                                }
                            }

                            if( isset($contact_value['mobile'])) {
                                if (isset($colseSuppliers['mobile'][$contact_value['mobile']])) {
                                    $all_data['change_data']['contact_data'][$contact_key]['mobile_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['mobile'][$contact_value['mobile']]);
                                } else {
                                    $all_data['change_data']['contact_data'][$contact_key]['mobile_suppliers'] = [];
                                }
                            }
                        }
                    } else {
                        foreach($all_data['change_data']['contact_data'] as $contact_key=>$contact_value){
                            if(isset($contact_value['micro_letter']) ) {

                                    $all_data['change_data']['contact_data'][$contact_key]['micro_suppliers'] = [];
                            }

                            if(isset($contact_value['qq']) ) {

                                $all_data['change_data']['contact_data'][$contact_key]['qq_suppliers'] = [];
                            }

                            if(isset($contact_value['email']) ) {

                                $all_data['change_data']['contact_data'][$contact_key]['email_suppliers'] = [];
                            }

                            if(isset($contact_value['want_want']) ) {

                                $all_data['change_data']['contact_data'][$contact_key]['want_suppliers'] = [];
                            }

                            if(isset($contact_value['mobile']) ) {

                                $all_data['change_data']['contact_data'][$contact_key]['mobile_suppliers'] = [];
                            }



                        }

                    }

                }*/

            }


        }
        if (!empty($all_data) && isset($all_data['insert_data']['contact_data'])) {

            foreach ($all_data['insert_data']['contact_data'] as $add_contact_data ) {
                $now_contact_data[] = $add_contact_data;


            }







          /*  if(!empty($all_data['insert_data']['contact_data'])){

                $searchCloseWhere = [];
                // 供应商微信号

                $microLetters = array_filter(array_column( $all_data['insert_data']['contact_data'],'micro_letter'));
                if( !empty($microLetters) ){

                    $searchCloseWhere['micro_letter'] = $microLetters;
                }
                //  供应商QQ 号
                $supplierQQs = array_filter(array_column( $all_data['insert_data']['contact_data'],'qq'));
                if( !empty($supplierQQs) ){

                    $searchCloseWhere['qq'] = $supplierQQs;
                }
                // 旺旺号
                $supplierWants = array_filter(array_column( $all_data['insert_data']['contact_data'],'want_want'));
                if( !empty($supplierWants) ){

                    $searchCloseWhere['want_want'] = $supplierWants;
                }


                // 供应商邮箱
                $supplierEmails = array_filter(array_column( $all_data['insert_data']['contact_data'],'email'));
                if(!empty($supplierEmails)){
                    $searchCloseWhere['email'] = $supplierEmails;
                }

                // 手机号
                $supplierMobiles = array_filter(array_column( $all_data['insert_data']['contact_data'],'mobile'));
                if(!empty($supplierMobiles)){
                    $searchCloseWhere['mobile'] = $supplierMobiles;
                }


                if( !empty($searchCloseWhere)){

                    $colseSuppliers = $this->contactModel->getCloseSupplierData($searchCloseWhere,$supplier_code);
                    if( NULL != $colseSuppliers){
                        foreach($all_data['insert_data']['contact_data'] as $contact_key=>$contact_value){

                            if( !empty($contact_value['micro_letter']) &&
                                isset($colseSuppliers['micro_letter'][$contact_value['micro_letter']])){
                                $all_data['insert_data']['contact_data'][$contact_key]['micro_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['micro_letter'][$contact_value['micro_letter']]);
                            }else{
                                $all_data['insert_data']['contact_data'][$contact_key]['micro_suppliers'] = [];
                            }

                            if( !empty($contact_value['qq']) &&
                                isset($colseSuppliers['qq'][$contact_value['qq']])){
                                $all_data['insert_data']['contact_data'][$contact_key]['qq_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['qq'][$contact_value['qq']]);
                            }else{
                                $all_data['insert_data']['contact_data'][$contact_key]['qq_suppliers'] = [];
                            }

                            if(  !empty($contact_value['email']) &&
                                isset($colseSuppliers['email'][$contact_value['email']])){
                                $all_data['insert_data']['contact_data'][$contact_key]['email_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['email'][$contact_value['email']]);
                            }else{
                                $all_data['insert_data']['contact_data'][$contact_key]['email_suppliers'] = [];
                            }

                            if( !empty($contact_value['want_want']) &&
                                isset($colseSuppliers['want_want'][$contact_value['want_want']])){
                                $all_data['insert_data']['contact_data'][$contact_key]['want_suppliers'] =$this->get_unique_supplier_info($colseSuppliers['want_want'][$contact_value['want_want']]);
                            }else{
                                $all_data['insert_data']['contact_data'][$contact_key]['want_suppliers'] = [];
                            }

                            if( !empty($contact_value['mobile']) &&
                                isset($colseSuppliers['mobile'][$contact_value['mobile']])){
                                $all_data['insert_data']['contact_data'][$contact_key]['mobile_suppliers'] = $this->get_unique_supplier_info($colseSuppliers['mobile'][$contact_value['mobile']]);
                            }else{
                                $all_data['insert_data']['contact_data'][$contact_key]['mobile_suppliers'] = [];
                            }



                        }
                    }
                }

            }*/

        }

        //删除的联系方式不验证
        $delete_contact_data = !empty($all_data['delete_data']['contact_data'])?$all_data['delete_data']['contact_data']:[];

        if (!empty($delete_contact_data)&&!empty($now_contact_data)) {
            foreach ($now_contact_data as $now_contact=>$now_contact_info) {
                if (in_array($now_contact_info['id'],$delete_contact_data)) {//删除验证数据
                    unset($now_contact_data[$now_contact]);

                }

            }

        }







        if (!empty($now_contact_data)) {
            $searchCloseWhere = [];
            // 供应商微信号
            $microLetters = array_filter(array_column( $now_contact_data,'micro_letter'),  //相似供应商过滤掉
                function ($value)
                {
                    if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                        return false;

                    }

                    return true;

                });
            if( !empty($microLetters) ){

                $searchCloseWhere['micro_letter'] = $microLetters;
            }
            //  供应商QQ 号
            $supplierQQs = array_filter(array_column( $now_contact_data,'qq'), function ($value)
            {
                if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                    return false;

                }
                return true;

            });
            if( !empty($supplierQQs) ){

                $searchCloseWhere['qq'] = $supplierQQs;
            }
            // 旺旺号
            $supplierWants = array_filter(array_column( $now_contact_data,'want_want'), function ($value)
            {
                if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                    return false;

                }

                return true;

            });
            if( !empty($supplierWants) ){

                $searchCloseWhere['want_want'] = $supplierWants;
            }

            // 供应商邮箱
            $supplierEmails = array_filter(array_column( $now_contact_data,'email'), function ($value)
            {
                if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                    return false;

                }

                return true;

            });
            if(!empty($supplierEmails)){
                $searchCloseWhere['email'] = $supplierEmails;
            }

            // 手机号
            $supplierMobiles = array_filter(array_column( $now_contact_data,'mobile'), function ($value)
            {
                if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                    return false;

                }

                return true;

            });
            if(!empty($supplierMobiles)){
                $searchCloseWhere['mobile'] = $supplierMobiles;
            }

            // 联系方式
            $supplierContactNumbers = array_filter(array_column( $now_contact_data,'contact_number'), function ($value)
            {
                if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                    return false;

                }

                return true;

            });
            if(!empty($supplierContactNumbers)){
                $searchCloseWhere['contact_number'] = $supplierContactNumbers;
            }

            if( !empty($searchCloseWhere)){
                $closeSuppliers = $this->contactModel->getCloseSupplierData($searchCloseWhere,$supplier_code);
                if( NULL != $closeSuppliers){
                    $all_data['similar_contact_suppliers'] = $closeSuppliers;

                }
            }



        }

        //需要校验信息
        $check_payment_info = [];


        if (empty($all_data['change_data']['payment_data'])) {
            $payment_info_check = $now_payment_info;

        } else {


            if (!empty($all_data['change_data']['payment_data'])) {
                foreach ($all_data['change_data']['payment_data'] as $is_tax=>$payment_detail ) {
                    foreach ($payment_detail as $purchase_type_id=>$payment) {

                        if (!empty($payment['supplier_settlement'])) {
                                 $settlement_name =$settlement_code_name_map[$payment['supplier_settlement']]??'';
                                 $settlement_type_name = $settlement_code_name_map[$payment['settlement_type']]??'';

                            $all_data['change_data']['payment_data'][$is_tax][$purchase_type_id]['supplier_settlement_type']=$settlement_type_name.'/'.$settlement_name;


                        }


                    }


                }

            }


            $payment_info_check = $all_data['change_data']['payment_data'];

        }


        if (!empty($payment_info_check[0])) {
            foreach ($payment_info_check[0] as $purchase_type_id=>$payment_data) {
                if ($payment_data['payment_method'] == 3) {
                    $check_payment_info[$purchase_type_id] = $payment_data;

                }


            }


        }

        if ($all_data['insert_data']['payment_data']) {

            foreach ($all_data['insert_data']['payment_data'] as $is_tax=>$payment_detail_ins ) {
                foreach ($payment_detail_ins as $purchase_type_id=>$payment_ins) {

                    if (!empty($payment_ins['supplier_settlement'])) {
                        $settlement_name =$settlement_code_name_map[$payment_ins['supplier_settlement']]??'';
                        $settlement_type_name = $settlement_code_name_map[$payment_ins['settlement_type']]??'';

                        $all_data['insert_data']['payment_data'][$is_tax][$purchase_type_id]['supplier_settlement_type']=$settlement_type_name.'/'.$settlement_name;


                    }


                }


            }

        }

        if ($all_data['insert_data']['payment_data'][0]) {
                   foreach ($all_data['insert_data']['payment_data'][0] as $purchase_type_id=>$add_payment_data) {
                       if ($add_payment_data['payment_method'] == 3) {
                           $check_payment_info[$purchase_type_id] = $payment_data;

                       }


                   }



        }



        if (!empty($check_payment_info)) {
            $searchPayWhere = [];

            $phone_number = array_filter(array_column( $check_payment_info,'phone_number') ,function ($value)
            {
                if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                    return false;

                }


                return true;

            });
            $id_number = array_filter(array_column( $check_payment_info,'id_number'), function ($value)
            {
                if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                    return false;

                }

                return true;

            });
            $account = array_filter(array_column( $check_payment_info,'account'), function ($value)
            {
                if (strtolower($value) == 'no'|| $value =='无'||empty($value)) {
                    return false;

                }

                return true;

            });

            if (!empty($phone_number)) {
                $searchPayWhere['phone_number'] = $phone_number;

            }

            if (!empty($id_number)) {
                $searchPayWhere['id_number'] = $id_number;

            }


            if (!empty($account)) {
                $searchPayWhere['account'] = $account;

            }


            $closePaymentInfo = $this->contactModel->getClosePaymentData($searchPayWhere,$supplier_code);

            $all_data['similar_payment_suppliers']  = $closePaymentInfo;


        }

//获取历史更改信息

        $require_field = $this->supplier_model->getReqiredFieldsForLog();
        $all_fields = array_keys($require_field['reqFields']);


        $other_logs = $this->updateLogModel->get_other_complete_log($id,$supplier_code,[SUPPLIER_REVIEW_PASSED]);



        if (!empty($other_logs)) {
            foreach ($other_logs as $log) {

                $message = json_decode($log['message'], true);
                foreach ($all_fields as $change => $change_field) {
                    if (isset($require_field['reqFields'][$change_field]) && $require_field['reqFields'][$change_field]) {
                        /**基础信息（pur_supplier）更新开始**/
                        if ($change_field == 'basic') {
                            unset($require_field['reqFields'][$change_field]['id']);

                            foreach ($require_field['reqFields'][$change_field] as $field => $name) {
                                $temp = [];

                                if ($log['apply_type'] == 1) {
                                    if (!empty($message['create_data']['basis_data'][$field])) {

                                        if (in_array($field, array('ship_province', 'ship_city', 'ship_area'))) {
                                            if (!empty($message['create_data']['basis_data'][$field])) {
                                                $address = $this->addressModel->get_address_name_by_id($message['create_data']['basis_data'][$field]);
                                            } else {
                                                $address = '';
                                            }
                                            //{oldVal: '深圳市三嘉达电子科技有限公司', oldTime: '2020-08-31 11:23:30'},
                                            $temp['oldVal'] = $address;
                                            $temp['oldTime'] = $log['audit_time'];

                                            $temp_field = $field . '_name';

                                            $all_data['historyData']['basis_data'][$temp_field][] = $temp;

                                        } else {

                                            switch ($field) {// 需要转换成 中文
                                                case 'invoice':
                                                    $before_v = supplier_ticket($message['create_data']['basis_data'][$field]);
                                                    break;
                                                case 'is_cross_border':
                                                    $before_v = getCrossBorder($message['create_data']['basis_data'][$field]);
                                                    break;
                                                case 'supplier_type':
                                                    $before_v = supplier_type($message['create_data']['basis_data'][$field]);
                                                    break;
                                                case 'settlement_type':
                                                    $before_v = $this->settlementModel->get_settlement_one($message['create_data']['basis_data'][$field]);
                                                    $before_v = isset($before_v['settlement_name']) ? $before_v['settlement_name'] : '';
                                                    break;
                                                case 'supplier_settlement':
                                                    $before_v = $this->settlementModel->get_settlement_one($message['create_data']['basis_data'][$field]);
                                                    $before_v = isset($before_v['settlement_name']) ? $before_v['settlement_name'] : '';
                                                    break;
                                                case 'is_complete':
                                                    $before_v = getComplete($message['create_data']['basis_data'][$field]);
                                                    break;
                                                case 'supplier_level':
                                                    $before_v = getSupplierLevel($message['create_data']['basis_data'][$field]);
                                                    break;
                                                case 'is_agent':
                                                    $before_v = getIsAgent($message['create_data']['basis_data'][$field]);
                                                    break;
                                                case 'is_postage':
                                                    $before_v = getIsPostage($message['create_data']['basis_data'][$field]);
                                                    break;
                                                default :
                                                    $before_v = $message['create_data']['basis_data'][$field];
                                            }
                                            $temp['oldVal'] = $before_v;
                                            $temp['oldTime'] = $log['audit_time'];
                                            $all_data['historyData']['basis_data'][$field][] = $temp;


                                        }
                                    }

                                } else {

                                    if (!empty($message['change_data']['basis_data'][$field])) {

                                        if (in_array($field, array('ship_province', 'ship_city', 'ship_area'))) {
                                            if (!empty($message['change_data']['basis_data'][$field])) {
                                                $address = $this->addressModel->get_address_name_by_id($message['change_data']['basis_data'][$field]);
                                            } else {
                                                $address = '';
                                            }
                                            //{oldVal: '深圳市三嘉达电子科技有限公司', oldTime: '2020-08-31 11:23:30'},
                                            $temp['oldVal'] = $address;
                                            $temp['oldTime'] = $log['audit_time'];

                                            $temp_field = $field . '_name';

                                            $all_data['historyData']['basis_data'][$temp_field][] = $temp;

                                        } else {

                                            switch ($field) {// 需要转换成 中文
                                                case 'invoice':
                                                    $before_v = supplier_ticket($message['change_data']['basis_data'][$field]);
                                                    break;
                                                case 'is_cross_border':
                                                    $before_v = getCrossBorder($message['change_data']['basis_data'][$field]);
                                                    break;
                                                case 'supplier_type':
                                                    $before_v = supplier_type($message['change_data']['basis_data'][$field]);
                                                    break;
                                                case 'settlement_type':
                                                    $before_v = $this->settlementModel->get_settlement_one($message['change_data']['basis_data'][$field]);
                                                    $before_v = isset($before_v['settlement_name']) ? $before_v['settlement_name'] : '';
                                                    break;
                                                case 'supplier_settlement':
                                                    $before_v = $this->settlementModel->get_settlement_one($message['change_data']['basis_data'][$field]);
                                                    $before_v = isset($before_v['settlement_name']) ? $before_v['settlement_name'] : '';
                                                    break;
                                                case 'is_complete':
                                                    $before_v = getComplete($message['change_data']['basis_data'][$field]);
                                                    break;
                                                case 'supplier_level':
                                                    $before_v = getSupplierLevel($message['change_data']['basis_data'][$field]);
                                                    break;
                                                case 'is_agent':
                                                    $before_v = getIsAgent($message['change_data']['basis_data'][$field]);
                                                    break;
                                                case 'is_postage':
                                                    $before_v = getIsPostage($message['change_data']['basis_data'][$field]);
                                                    break;
                                                default :
                                                    $before_v = $message['change_data']['basis_data'][$field];
                                            }
                                            $temp['oldVal'] = $before_v;
                                            $temp['oldTime'] = $log['audit_time'];
                                            $all_data['historyData']['basis_data'][$field][] = $temp;
                                        }

                                    }

                                }
                            }
                        }


                        if ($change_field == 'payment_data') {
                            if (!empty($payment_info_check)) {


                                    foreach ($payment_info_check as $is_tax => $pay_check) {



                                        foreach ($pay_check as $purchase_type_id => $pay) {
                                            if ($log['apply_type'] == 1) {
                                                if (!empty($message['create_data']['payment_data'][$is_tax][$purchase_type_id])) {
                                                    //比较数据是否更改，更改就记录下

                                                    foreach ($pay as $k => $val) {
                                                        $val = ($val == '' ? 0 : $val);
                                                        $old_val = $message['create_data']['payment_data'][$is_tax][$purchase_type_id][$k] ?? '';
                                                        if ($k == 'settlement_type') {
                                                            continue;

                                                        }
                                                        switch ($k) {// 需要转换成 中文

                                                            case 'supplier_settlement':
                                                                $old_val = ($settlement_code_name_map[$message['create_data']['payment_data'][$is_tax][$purchase_type_id]['settlement_type']].'/'.$settlement_code_name_map[$old_val]) ?? '';
                                                                $val = ($settlement_code_name_map[$pay['settlement_type']].'/'.$settlement_code_name_map[$val]) ?? '';
                                                                break;
                                                            case 'payment_method':
                                                                $old_val = getPayType($old_val);
                                                                $val = getPayType($val);
                                                                break;
                                                            case 'payment_platform':
                                                                $old_val = get_supplier_payment_platform($old_val);
                                                                $val = get_supplier_payment_platform($val);
                                                                break;
                                                            case 'settlement_change_res':
                                                                $old_val = getSettlementChangeRes($old_val);
                                                                $val = getSettlementChangeRes($val);
                                                                break;
                                                            default :
                                                                $old_val = $old_val;
                                                                $val = $val;
                                                        }


                                                        if ($old_val != $val) {
                                                            if ($k =='supplier_settlement') {
                                                                $k = 'supplier_settlement_type';

                                                            }



                                                            $all_data['historyData']['payment_data'][$is_tax][$purchase_type_id][$k][]= ['oldVal' => $old_val, 'oldTime' => $log['audit_time']];




                                                        }
                                                    }



                                                }

                                            } else {

                                                if (!empty($message['change_data']['payment_data'][$is_tax][$purchase_type_id])) {
                                                    //比较数据是否更改，更改就记录下

                                                    foreach ($pay as $k => $val) {
                                                        $val = ($val == '' ? 0 : $val);
                                                        $old_val = $message['change_data']['payment_data'][$is_tax][$purchase_type_id][$k] ?? '';
                                                        if ($k == 'settlement_type') {
                                                            continue;

                                                        }
                                                        switch ($k) {// 需要转换成 中文

                                                            case 'supplier_settlement':
                                                                $old_val = ($settlement_code_name_map[$message['change_data']['payment_data'][$is_tax][$purchase_type_id]['settlement_type']].'/'.$settlement_code_name_map[$old_val]) ?? '';
                                                                $val = ($settlement_code_name_map[$pay['settlement_type']].'/'.$settlement_code_name_map[$val]) ?? '';
                                                                break;
                                                            case 'payment_method':
                                                                $old_val = getPayType($old_val);
                                                                $val = getPayType($val);
                                                                break;
                                                            case 'payment_platform':
                                                                $old_val = get_supplier_payment_platform($old_val);
                                                                $val = get_supplier_payment_platform($val);
                                                                break;
                                                            case 'settlement_change_res':
                                                                $old_val = getSettlementChangeRes($old_val);
                                                                $val = getSettlementChangeRes($val);
                                                                break;
                                                            default :
                                                                $old_val = $old_val;
                                                                $val = $val;
                                                        }


                                                        if ($old_val != $val) {
                                                            if ($k =='supplier_settlement') {
                                                                $k = 'supplier_settlement_type';

                                                            }



                                                            $all_data['historyData']['payment_data'][$is_tax][$purchase_type_id][$k][]= ['oldVal' => $old_val, 'oldTime' => $log['audit_time']];




                                                        }
                                                    }



                                                }

                                            }




                                        }

                                    }

                                }



                            }








                        if ($change_field == 'contact') {

                            if (!empty($now_contact_data)) {

                           /*     foreach ($now_contact_data as $contact_key => $contact_data) {
                                    $all_data['historyData']['contact_data'][$contact_key] = [];


                                }*/
                                foreach ($now_contact_data as $contact_key => $contact_data) {

                                    if (!empty($message['change_data']['contact_data'][$contact_data['id']])) {
                                        foreach ($message['change_data']['contact_data'][$contact_data['id']] as $change_contact_key => $change_contact_data) {
                                            $all_data['historyData']['contact_data'][$contact_key][$change_contact_key][]= ['oldVal' => $change_contact_data, 'oldTime' => $log['audit_time']];


                                        }


                                    }

                                }




                            }


                        }



                    }


                }

            }

        }







        if(empty($update_log)){
            $this->send_data(null, '非待审核状态', true);
        }else{
            $update_log['message'] = json_encode($all_data);
            $message = $update_log['message'];
            $message = json_decode($message, true);
            if(!empty($message) && !empty($update_log['enable_remark'])){

                $message['change_data']['basis_data']['enable_remark'] = $update_log['enable_remark'];
            }

            if(!empty($message) && !empty($update_log['enable_desc'])){

                $message['change_data']['basis_data']['enable_desc'] = $update_log['enable_desc'];
            }

            $this->send_data($message, '待审核数据', true);
        }
    }

    /**
     * @desc 供应商审核  审核通过更新供应商信息
     * @author Jackson
     * @Date 2019-01-24 16:01:00
     * @return array()
     **/
    public function supplier_review()
    {
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }


        $parames = gp();
        if (!isset($parames['id']) || empty($parames['id'])) {
            $this->send_data(null, "审核字段id不能为空", false);
        }
        if (!isset($parames['audit_status']) || !in_array($parames['audit_status'], array(1, 2))) {
            $this->send_data(null, "audit_status 不能为空，或 必需要在1,2之间：1审核通过,2驳回", false);
        }
        if($parames['audit_status'] == 2 and empty($parames['remarks'])){
            $this->send_data(null, "驳回必须填写驳回备注", false);
        }

       ;
        $audit_status  = $parames['audit_status'];
        $remarks       = isset($parames['remarks'])?$parames['remarks']:'';
        $is_complete   = isset($parames['is_complete']) ? $parames['is_complete'] : null;
        $update_log = $this->updateLogModel->update_log_detail($parames['id']);

        //查询其他供应商



        $other_logs = $this->updateLogModel->get_other_complete_log($parames['id'],$update_log['supplier_code'],[SUPPLIER_WAITING_PURCHASE_REVIEW,SUPPLIER_WAITING_SUPPLIER_REVIEW,SUPPLIER_FINANCE_REVIEW]);

        if (!empty($other_logs)) {
            $this->send_data(null, "已存在待审审核记录", false);

        }

        //判断审核状态是否同步
        if ($update_log['audit_status']!=$parames['page_audit_status']) {
            $this->send_data(null, "供应商已被审核", false);

        }

        if(empty($update_log) or !in_array($update_log['audit_status'],[SUPPLIER_WAITING_PURCHASE_REVIEW,SUPPLIER_WAITING_SUPPLIER_REVIEW,SUPPLIER_FINANCE_REVIEW])){
            $this->send_data(null, "该供应商非待审核状态", false);
        }



        $need_finance_audit = $update_log['need_finance_audit'];

        // 供应商 下一状态
        $new_status = null;
        if($audit_status == 1){// 审核通过
            if($update_log['audit_status'] == SUPPLIER_WAITING_PURCHASE_REVIEW){
                if($update_log['need_supplier_audit'] == 1){
                    $new_status = SUPPLIER_WAITING_SUPPLIER_REVIEW;
                }else{
                    $new_status = SUPPLIER_REVIEW_PASSED;
                }

            }elseif($update_log['audit_status'] == SUPPLIER_WAITING_SUPPLIER_REVIEW){
                if($need_finance_audit){// 需经财务审核
                    $new_status = SUPPLIER_FINANCE_REVIEW;
                }else{// 无需经财务审核
                    $new_status = SUPPLIER_REVIEW_PASSED;
                }
            }elseif($update_log['audit_status'] == SUPPLIER_FINANCE_REVIEW){
                $new_status = SUPPLIER_REVIEW_PASSED;
            }
        }else{// 驳回
            if($update_log['audit_status'] == SUPPLIER_WAITING_PURCHASE_REVIEW){
                $new_status = SUPPLIER_PURCHASE_REJECT;
            }elseif($update_log['audit_status'] == SUPPLIER_WAITING_SUPPLIER_REVIEW){
                $new_status = SUPPLIER_SUPPLIER_REJECT;
            }elseif($update_log['audit_status'] == SUPPLIER_FINANCE_REVIEW){
                $new_status = SUPPLIER_FINANCE_REJECT;
            }
        }

        $result = $this->updateLogModel->do_update_supplier($parames['id'],$new_status,$remarks,$is_complete);
        if($result['code']){
            $this->send_data(null, "审核成功", true);
        }else{
            $this->send_data(null, $result['message'], false);
        }

    }

    /**
     * @desc 供应商禁用
     * @author Jackson
     * @Date 2019-01-24 16:01:00
     * @return array()
     **/
    public function supplier_disable()
    {
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }

        $parames = gp();
        if (empty($parames['id']) or empty($parames['status']) or empty($parames['remark'])) {
            $this->send_data(null, "参数【ID|状态|备注】缺失", false);
        }

        //供应商禁用时必须填写备注
        if($parames['status'] == IS_DISABLE  && empty(trim($parames['remark']))){
            $this->send_data(null, "请填写禁用备注", false);
        }

        $result = $this->_modelObj->supplier_disable($parames['id'],$parames['status'],$parames['remark']);

        $this->send_data(null, $result['message'], $result['code']);

    }

    /**
     * @desc 供应商启用
     * @author Jackson
     * @Date 2019-01-25 16:01:00
     * @return array()
     **/
    public function supplier_enable()
    {
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }

        $parames = gp();
        if (empty($parames['id']) or empty($parames['status']) or empty($parames['remark'])) {
            $this->send_data(null, "参数【ID|状态|备注】缺失", false);
        }
        $result = $this->_modelObj->supplier_disable($parames['id'],$parames['status'],$parames['remark']);

        $this->send_data(null, $result['message'], $result['code']);

    }

    /**
     * @desc 供应商支付帐号信息删除
     * @author Jackson
     * @Date 2019-01-30 16:01:00
     * @return array()
     **/
    public function delete_payment_account()
    {
        die("已废弃");exit;
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }
        $this->send_data(null, '禁止直接删除支付账号', false);

        $parames = gp();
        if (isset($parames['id']) && empty($parames['id'])) {
            $this->send_data(null, "数据为空", false);
        }
        list($status, $msg) = $this->paymentAccountModel->is_del($parames);

        $this->send_data(null, $msg, $status);

    }

    /**
     * @desc 供应商支付帐号信息删除
     * @author Jackson
     * @Date 2019-01-30 16:01:00
     * @return array()
     **/
    public function get_create_user_information()
    {
        $params = gp();
        $data = $this->_modelObj->get_create_user_name_by_useid($params);
        $this->send_data($data, '创建人信息', true);

    }

    /**
     * @desc 供应商账期信息
     * @author Jeff
     * @Date 2019/03/14 14:17
     * @return array()
     */
    public function get_supplier_quota()
    {
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }

        $parames = gp();

        // 参数错误
        if(!isset($parames['seller_login_id']) || empty($parames['seller_login_id'])) $this->error_json('参数缺失');

        $data = $this->_modelObj->get_supplier_quota_inifo($parames);

        if (isset($data[0]) && $data[0]===false)
        {
            $this->error_json($data[1]);
        }else{
            $this->success_json($data);
        }


    }


    /**
     * @desc 获取供应商信息列表
     * @author bigfong
     * @Date 2019-03-09
     * @return array()
     **/
    public function get_op_log_list()
    {
        $this->load->helper('status_order');
        $supplier_code = gp('supplier_code');
        if (empty($supplier_code)) {
            $this->send_data(null, "supplier_code不能为空", false);
        }
        $params = gp();

        $pageSize = query_limit_range(isset($params['limit'])?$params['limit']:0);
        $page     = !isset($params['offset']) || intval($params['offset']) <= 0 ? 1 : intval($params['offset']);

        $supplier_data = $this->_modelObj->get_supplier_name_bycode($supplier_code);
        if (!empty($supplier_data)) {
            $CI = &get_instance();
            $CI->load->model('operator_log_model');
            $field ='content,content_detail,operator,operate_time,operator_id,ext,operate_type';
            //$data = $CI->operator_log_model->query_logs(['type'=>'SUPPLIER_UPDATE_LOG','id'=>$supplier_code,'page'=>$page,'limit'=>$pageSize],$field);
            $data = $CI->operator_log_model->query_logs(['type'=>'SUPPLIER_UPDATE_LOG','id'=>$supplier_code],$field);

            $reqiredFields = $this->Supplier_model->getReqiredFieldsForLog();


            foreach ($data as $key => $value) {
                $remark = '';
                $data[$key]['operator'] = $value['operator'];
                $data[$key]['operate_type_name'] = getSupplierOperateType($value['operate_type']);
                if(strpos($value['ext'],'申请备注')){$remark = $value['ext'];}
                $content_detail_arr = json_decode($value['content_detail'],true);
                $content_detail = '';
                if(!empty($content_detail_arr) && is_array($content_detail_arr)){
                    foreach ($content_detail_arr as $k => $val) {
                        //基础信息
                        if(isset($val['basic']) && !empty($val['basic'])){
                            foreach ($val['basic'] as $basis_key => $basis_value) {
                                if(!isset($reqiredFields['reqFields']['basic'][$basis_key])) continue;
                                $content_detail.=$reqiredFields['reqFields']['basic'][$basis_key].":".$basis_value.'<br/>';
                            }
                        }
                        //联系方式
                        if(isset($val['contact']) && !empty($val['contact'])){
                            foreach ($val['contact'] as $contact_key => $contact_value) {
                                foreach ($contact_value as $k2 => $value2) {
                                    if(!isset($reqiredFields['reqFields']['contact'][$k2])) continue;
                                    $content_detail.=$reqiredFields['reqFields']['contact'][$k2].":".$value2.'<br/>';
                                }
                                
                            }
                        }

                        //采购员
                        if(isset($val['buyer']) && !empty($val['buyer'])){
                            foreach ($val['buyer'] as $buyer_key => $buyer_value) {
                                foreach ($buyer_value as $buyer_k2 => $buyer_val1) {
                                    $buyer_arr = explode(',', $buyer_val1);
                                    $old_info = $this->userModel->get_user_info_by_id($buyer_arr[0]);
                                    $new_info = $this->userModel->get_user_info_by_id($buyer_arr[1]);
                                    if(!isset($old_info['user_name'])) $old_info['user_name'] = $buyer_arr[0];// 默认为 ID
                                    if(!isset($new_info['user_name'])) $new_info['user_name'] = $buyer_arr[1];// 默认为 ID
                                    $content_detail.= '采购员配置:'.getPurchaseType($buyer_key).'--修改前,'.$old_info['user_name'].';修改后,'.$new_info['user_name'].'<br>';
                                }
                                
                            }
                        }


                        //相关证明资料图片
                        if(isset($val['images']) && !empty($val['images'])){
                            if(is_array($val['images'])){
                                foreach ($val['images'] as $images_key => $images_value) {
                                    if(!isset($reqiredFields['reqFields']['images'][$images_key])) continue;
                                    $content_detail.=$reqiredFields['reqFields']['images'][$images_key].":"."$images_value".";<br>";
                                }
                            }

                            if(is_string($val['images'])) $content_detail .=  $val['images'].";<br>";

                        }

                        //关联供应商
                        if(isset($val['supplier_relation']) && !empty($val['supplier_relation'])){
                            $content_detail.='关联供应商:'.'<br/>';
                            foreach ($val['supplier_relation'] as $relation_k => $re_value) {
                                $content_detail.=$re_value.'<br/>';


                            }
                        }
                    }

                    //是否有新增记录
                    if(!empty($content_detail_arr['insert_data'])){
                        $insert_data = $content_detail_arr['insert_data'];
                        //供应商联系方式
                        if(isset($insert_data['contact_data']) && !empty($insert_data['contact_data'])){
                            foreach ($insert_data['contact_data'] as $ins_contact_key => $ins_contact_value) {
                                $content_detail.='新增供应商联系人:'.$ins_contact_value['contact_person'].'<br>';   
                            }
                        }

                        //相关资质
                        if(isset($insert_data['images_data']) && !empty($insert_data['images_data'])){
                            foreach ($insert_data['images_data'] as $images_key => $images_value) {
                                if(!isset($reqiredFields['reqFields']['images'][$images_key])) continue;
                                if(stripos($images_value,';') !== false){
                                    $images_value_list = explode(';',$images_value);
                                }else{
                                    $images_value_list = [$images_value];
                                }
                                $content_detail.='新增图片:'.$reqiredFields['reqFields']['images'][$images_key].":";
                                foreach($images_value_list as $images_value_tmp){
                                    $content_detail .= '<img src="'.$images_value_tmp.'" width="40" height="40"><br/>';
                                }

                            }
                        }
                    }

                    //是否有删除记录
                    if(!empty($content_detail_arr['delete_data'])){
                        $delete_data = $content_detail_arr['delete_data'];
                        //供应商联系方式
                        if(isset($delete_data['contact_data']) && !empty($delete_data['contact_data'])){
                            foreach ($delete_data['contact_data'] as $del_contact_key => $del_contact_value) {
                                if(!isset($del_contact_value['contact_person'])) continue;
                                $contact_person= isset($del_contact_value['contact_person'])?$del_contact_value['contact_person']:'';
                                $content_detail.='删除供应商联系人:'.$contact_person.'<br>';
                            }
                        }
                    }


                    //财务结算日志
                    if(isset($content_detail_arr['change_data_log']['payment_data']) && !empty($content_detail_arr['change_data_log']['payment_data'])){
                        foreach ($content_detail_arr['change_data_log']['payment_data'] as $is_tax => $payment_value) {
                            foreach ($payment_value as $business_line => $payment_value2) {
                                if (is_array($payment_value2)){
                                    foreach ($payment_value2 as $payment_key => $payment_value3){
                                        if (isset($reqiredFields['reqFields']['payment_data'][$is_tax][$business_line][$payment_key])){
                                            $content_detail .= sprintf('%s[%s]',$reqiredFields['reqFields']['payment_data'][$is_tax][$business_line][$payment_key],$payment_value3).'<br>';
                                        }
                                    }
                                }
                            }
                        }
                    }




                    $data[$key]['content_detail'] = $content_detail;
                }else{
                    $data[$key]['content_detail'] = $value['content'].'：'.$value['content_detail'];
                }
                if($remark){$data[$key]['content_detail'].='<br>'.$remark;}
            }
            $this->send_data($data, '操作日志列表', true);
        }else{
            $this->send_data([], '未查询到供应商数据', false);
        }


    }
    /**
     * @desc 获取供应商信息列表
     * @author bigfong
     * @Date 2019-03-09
     * @return array()
     **/
    public function get_op_log_pretty_list()
    {
        $supplier_codes = gp('supplier_codes');
        $limit = gp('limit');
        $limit = is_null($limit)?5:intval($limit);// 默认5条

        if (empty($supplier_codes)) {
            $this->send_data(null, "supplier_code不能为空", false);
        }
        $supplier_codes = explode(',',$supplier_codes);
        if (empty($supplier_codes)) {
            $this->send_data(null, "supplier_code不能为空", false);
        }

        $this->load->model('operator_log_model');
        $field ='content,content_detail,operator,operate_time,operator_id,ext,operate_type';

        $pretty_data = [];
        foreach($supplier_codes as $key => $supplier_code){
            $supplierInfo = $this->_modelObj->get_supplier_info($supplier_code,false);

            $data = $this->operator_log_model->query_logs(['type'=>'SUPPLIER_UPDATE_LOG','id'=>$supplier_code],$field);
            if(empty($data)){
                $pretty_data[$supplier_code] = [];
                continue;
            }

            $count = 1;
            foreach($data as $value){
                $now_pretty_data['operator'] = $value['operator'];
                $now_pretty_data['operate_time'] = $value['operate_time'];

                //$operate_type = getSupplierOperateType($value['operate_type']);
                if(in_array($value['operate_type'],[1,2]) or stripos($value['content'],'创建') !== false){
                    $operator_type = '创建';
                    $now_pretty_data['operate_time'] = $supplierInfo['create_time'];
                }elseif(stripos($value['content'],'供应链') !== false and stripos($value['content'],'审核') !== false){
                    $operator_type = '供管';
                }elseif(stripos($value['content'],'财务') !== false and stripos($value['content'],'审核') !== false){
                    $operator_type = '财务';
                }elseif(in_array($value['operate_type'],[4,5,9]) or stripos($value['content'],'启用') !== false){
                    $operator_type = '启用';
                }elseif(stripos($value['content'],'转常规') !== false){
                    $operator_type = '转常规';
                }elseif(in_array($value['operate_type'],[10]) or stripos($value['content'],'预禁用') !== false){
                    $operator_type = '预禁用';
                }elseif(in_array($value['operate_type'],[7]) or stripos($value['content'],'禁用') !== false){
                    $operator_type = '禁用';
                }elseif(stripos($value['content'],'黑名单') !== false){
                    $operator_type = '黑名单';
                }elseif(strpos($value['content_detail'],'{') === 0){// { 开头的表示JSON数据
                    $content_detail = json_decode($value['content_detail'], true);
                    if (isset($content_detail['change_data_log']['basic']['supplier_source']) and
                        strpos($content_detail['change_data_log']['basic']['supplier_source'], '修改后:常规') !== false
                    ) {
                        $operator_type = '转常规';
                    }else{
                        continue;
                    }
                } else {
                    continue;
                }

                if($limit > 0 and $count++ > $limit){
                    //continue 2;
                }

                $now_pretty_data['content'] = $operator_type;

                $pretty_data[$supplier_code][] = $now_pretty_data;
            }
        }

        $this->send_data($pretty_data, '供应商管理操作记录优化', true);
    }

    /**
     * 获取支付下拉框
     * @author harvin
     * @Date 2019-04-24
     * @return array()
     */
    public function supplier_opening_bank(){
     $payment_platform_bank=$this->input->get_post('payment_platform_bank');
     $payment_platform_branch=$this->input->get_post('payment_platform_branch');
       if(empty($payment_platform_bank)){
           $this->error_json('请选择开户主行');
       }     
        $branch=$this->paymentAccountModel
                ->get_payment_platform_branch($payment_platform_bank,$payment_platform_branch);
        $this->success_json($branch);
    }

    /**
     * @desc 验证供应商名称是否和启用的供应商重复
     * @author Jeff
     * @Date 2019/6/21 9:28
     */
    public function validate_supplier_name()
    {
        $new_supplier_name=$this->input->get_post('new_supplier_name');//新供应商名称
        $supplier_code=$this->input->get_post('supplier_code');//当前供应商编码

        if (empty($supplier_code)) {
            $this->error_json( "supplier_code不能为空");
        }

        if (empty($new_supplier_name)) {
            $this->error_json( "new_supplier_name不能为空");
        }

        //验证是否已存在供应商名称
        $this->db->select('supplier_code,status');
        $this->db->where('supplier_name',$new_supplier_name);
        $this->db->from('supplier');
        $query = $this->db->get()->result_array();
        $flag = 0;
        $same = [];
        if (!empty($query)){
            foreach ($query as $value){
                if($value['supplier_code'] != $supplier_code && $value['status']==IS_ENABLE){
                    $flag++;
                    $same[] = $value['supplier_code'];
                }
            }
        }
        if($flag){
            $this->error_json( "与[".$same[0]."]的供应商同名，请禁用原供应商，或者在原供应商基础上进行更新");
        }else{
            $this->success_json();
        }
    }

    /**
     * @desc : 逻辑删除供应商图片信息
     * @param : image_id     int    删除图片的ID
     * @return : 成功返回true, 失败返回False
     **/

    public function del_Supplier_Image()
    {
        try {

            // 接受 HTTP 传入图片ID
            $supplier_image_id = $this->input->get_post('image_id');
            if( empty($supplier_image_id) ) {

                throw new Exception("请传入供应商要删除的图片ID",505);
            }
            $result = $this->_modelObj->del_supplier_image($supplier_image_id);
            if( True == $result ) {
                $this->success_json();
                return;
            }
            throw new Exception("删除图片信息失败",505);
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    public function get_Supplier_image()
    {
        try {

            $supplier_image_id = $this->input->get_post('image_id');
            if( empty($supplier_image_id) ) {

                throw new Exception("请传入要保存的图片ID",505);
            }
            $supplier_image_ids = explode(",",$supplier_image_id);
            $images = $this->_modelObj->get_supplier_images($supplier_image_ids);
            //（busine_licen.营业执照,verify_book.一般纳税人认定书,bank_information.银行资料,collection_order.收款委托书,other_proof.其他证明）
            $image_type = array('busine_licen' => '营业执照','verify_book'=>'一般纳税人认定书','bank_information'=>'银行资料','collection'=>'收款委托书','other_proof'=>'其他证明');

            if( NULL != $images && !empty($images) )
            {
                if( isset($image_type[$images['image_type']]) )
                {
                    $images['image_type'] = $image_type[$images['image_type']];
                }else{
                    $images['image_type'] = "其他";
                }
                $this->success_json($images);
            }

            throw new Exception("图片信息查询为空");
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 验证勾选的供应商是否符合修改条件
     */
    public function get_cross(){
        $supplier_ids = $this->input->get_post('supplier_ids');
        if(!is_array($supplier_ids) || empty($supplier_ids)){
            $this->error_json('数据格式不合法');
        }
        $result = $this->_modelObj->check_cross($supplier_ids);
        if($result['code'] == 1){
            $this->success_json();
        }else{
            $this->error_json($result['msg']);
        }
    }

    /**
     * 批量修改跨境宝
     */
    public function update_supplier_cross(){
        $supplier_ids = $this->input->get_post('supplier_ids');
        $is_cross_border = $this->input->get_post('is_cross_border');
        if(!is_array($supplier_ids) || empty($supplier_ids)){
            $this->error_json('数据格式不合法');
        }

        if(!is_numeric($is_cross_border) || !in_array($is_cross_border,[0,1])){
            $this->error_json('是否跨境宝只能是或者否');
        }

        $result = $this->_modelObj->update_supplier_cross($supplier_ids,$is_cross_border);
        if($result['code'] == 1){
            $this->success_json();
        }else{
            $this->error_json($result['msg']);
        }
    }


    /** 根据供应商链接获取店铺id,是否支持跨境宝
     * @return mixed
     */
    public function get_shop_id(){
        $shop_url = $this->input->get_post('shop_url');
        if(empty($shop_url)){
            $this->send_data([],'供应商链接不存在',false);
        }

        $res = $this->_modelObj->get_shop_id($shop_url);
        $this->send_data($res['data'],$res['msg'],$res['code'] == 1 ? true :false);

    }

    /**
     * 供应商联系信息
     *
     * @author Manson
     */
    public function get_supplier_address()
    {
        $supplier_name = $this->input->get_post('supplier_name');

        if(empty($supplier_name)){
            $this->error_json('supplier_name不能传空');
        }

        $res = $this->contactModel->get_supplier_contact_by_name($supplier_name);
        $this->data['status'] = 1;
        $this->data['data_list'] = $res;
        http_response($this->data);
    }


    /*
    * 供应商翻译信息保存
    */
    public function save_trans_info()
    {
        $supplier_code = $this->input->get_post('supplier_code');
        $supplier_name = $this->input->get_post('supplier_name');
        $address = $this->input->get_post('address');
        $person = $this->input->get_post('person');
        $phone = $this->input->get_post('phone');
        if(empty($supplier_code)) $this->error_json('供应商编码缺失');
        if(empty($supplier_name)) $this->error_json('翻译供应商名称缺失');
        if(empty($address)) $this->error_json('翻译地址缺失');
        if(empty($person)) $this->error_json('翻译联系人缺失');
        if(empty($phone)) $this->error_json('联系方式缺失');

        $data = array('supplier_name'=>$supplier_name,'address'=>$address,'person'=>$person,'phone'=>$phone,'supplier_code'=>$supplier_code);

        $supplier_info = $this->supplier_model->purchase_db->select('*')->where('supplier_code',$supplier_code)->get('supplier_trans')->row_array();

        if (!empty($supplier_info)) {//更新
            $flag = $this->db->update('supplier_trans',$data,array('supplier_code'=>$supplier_code));
        } else {


            $flag = $this->db->insert('supplier_trans',$data);

        }

        if ($flag) {
            $this->success_json('保存成功');

        } else {
            $this->error_json('保存失败');

        }





    }



    /*
   * 供应商翻译信息显示
   */

    public function show_trans_info()
    {

        $supplier_code = $this->input->get_post('supplier_code');
        if(empty($supplier_code)) $this->error_json('供应商编码缺失');
        $supplier_info = $this->supplier_model->purchase_db->select('supplier_name,address,person,phone')->where('supplier_code',$supplier_code)->get('supplier_trans')->row_array();
        $this->success_json($supplier_info);

    }

    /**
     * 手动刷新，1688是否支持跨境宝
     * /Supplier/Supplier/refresh_cross_border
     */
    public function refresh_cross_border()
    {
        $supplier_ids = $this->input->get_post('supplier_ids');
        if (!is_array($supplier_ids) OR empty($supplier_ids)) {
            $this->error_json('数据格式不合法');
        }

        $result = $this->_modelObj->sync_1688_cross_border($supplier_ids);
        if ($result['code'] == 1) {
            $this->success_json([], null, $result['msg']);
        } else {
            $this->error_json($result['msg']);
        }
    }


    /**
     * @desc 供应商预禁用
     * @author Jackson
     * @Date 2019-01-24 16:01:00
     * @return array()
     **/
    public function pre_disable()
    {
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }

        $parames = gp();

        if (empty($parames['id']) or empty($parames['status']) or empty($parames['remark'])) {
            $this->send_data(null, "参数【ID|状态|备注】缺失", false);
        }

        //供应商禁用时必须填写备注
        if($parames['status'] == PRE_DISABLE  && empty(trim($parames['remark']))){
            $this->send_data(null, "请填写禁用备注", false);
        }

        $result = $this->_modelObj->pre_disable($parames['id'],$parames['status'],$parames['remark']);

        $this->send_data(null, $result['message'], $result['code']);

    }

    public function get_relation_supplier_info()
    {

        $supplier_code = $this->input->get_post('supplier_code');
        if (empty($supplier_code)) {
            $this->send_data(null, "供应商编码为空", false);

        }
        $down_settlement = $this->settlementModel->get_settlement();
        $down_settlement = array_column($down_settlement['list'],'settlement_name','settlement_code');

        $data = $this->_modelObj->get_relation_supplier_info($supplier_code,$down_settlement);

        $this->success_json($data);


    }

    /**
     * @desc 批量修改供应商等级
     * @author Jaden
     * @Date 2019-05-14 16:01:00
     * @return array()
     **/
    public function update_supplier_level(){
        $parames = gp();
        $supplier_level_list = getSupplierLevel();
        if (empty($parames['supplier_ids'])||empty($parames['supplier_level'])) {
            $this->error_json( "数据异常");
        }
        if (!array_key_exists($parames['supplier_level'],$supplier_level_list)) {
            $this->error_json( "要修改的供应商等级不存在");

        }
        $supplier_ids_arr = $parames['supplier_ids'];


        try{
            foreach ( $supplier_ids_arr  as  $supplier_id) {
                $old_supplier_basic = $this->Supplier_model->find_supplier_one($supplier_id, null, null, false);
                if(empty($old_supplier_basic)){
                    throw new Exception("该供应商不存在");
                }
                $supplier_code      = $old_supplier_basic['supplier_code'];
                $supplier_name      = $old_supplier_basic['supplier_name'];

                $update_log_record = $this->updateLogModel->get_latest_audit_result($supplier_code);
                if($update_log_record
                    and !in_array($update_log_record['audit_status'],[
                        SUPPLIER_PURCHASE_REJECT,
                        SUPPLIER_SUPPLIER_REJECT,
                        SUPPLIER_FINANCE_REJECT,
                        SUPPLIER_REVIEW_PASSED])){
                    throw new Exception("供应商【".$supplier_name."】存在【未完结】的更新记录");
                }
                //修改
                if ($parames['supplier_level']!=$old_supplier_basic['supplier_level']) {
                    $flag = $this->supplier_model->purchase_db->where('id',$supplier_id)->update('supplier',['supplier_level'=>$parames['supplier_level']]);
                    if (empty($flag)) {
                        throw new Exception("供应商【".$supplier_name."】更新供应商等级失败");

                    } else {
                        //记录日志
                        operatorLogInsert(
                            [
                                'id'      => $supplier_code,
                                'type'    => 'supplier_update_log',
                                'content' => "供应商修改供应商等级从:".empty($supplier_level_list[$old_supplier_basic['supplier_level']])?'':$supplier_level_list[$old_supplier_basic['supplier_level']]."修改为".$supplier_level_list[$parames['supplier_level']],
                                'detail'  => "供应商修改供应商等级从:".empty($supplier_level_list[$old_supplier_basic['supplier_level']])?'':$supplier_level_list[$old_supplier_basic['supplier_level']]."修改为".$supplier_level_list[$parames['supplier_level']]
                            ]
                        );
                    }


                }

            }

            $this->success_json('修改成功');

        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }


    }


    //获取不重复的供应商数据
    public function get_unique_supplier_info($list)
    {
        $result = [];
        $unique_list = array_column($list,null,'supplier_code');
        if (!empty($unique_list)){
            foreach ($unique_list as $val) {
                $result[] = $val;

            }
        }

        return $result;


    }


    //获取历史填写的支付信息
    public function get_history_payment_info()
    {
        $tax_map = [1=>0,2=>1];
        $where = [];

        $supplier_code = $this->input->get_post('supplier_code');
        $is_ticket = $this->input->get_post('is_ticket');
        $payment_method = $this->input->get_post('payment_method');
        $purchase_type_id = $this->input->get_post('purchase_type_id');

        if (empty($supplier_code)||empty($is_ticket)) {
            $this->error_json('供应商编码或者是否开票为空');

        }
        if (!in_array($is_ticket,[1,2])) {
            $this->error_json('开票类型缺失');

        }

        if ($is_ticket == 1&&(empty($payment_method)||empty($purchase_type_id))) {

            $this->error_json('请传支付方式或者业务类型');

        }

        if ($is_ticket == 2) {
            $where['supplier_code'] = $supplier_code;
            $where ['is_tax'] = $tax_map[$is_ticket];
            $where ['purchase_type_id'] = 0;
            $where['is_del'] = 2;


        } else {
            $where['supplier_code'] = $supplier_code;
            $where ['is_tax'] = $tax_map[$is_ticket];
            $where ['purchase_type_id'] = $purchase_type_id;
            $where ['payment_method'] = $payment_method;
            $where['is_del'] = 2;



        }

        $data = $this->Supplier_payment_info_model->purchase_db->select('*')
            ->from('supplier_payment_info')
            ->where($where)
            ->get()
            ->row_array();
        if (!empty($data)) {
            $data['settlement_change_res'] = empty($data['settlement_change_res'])?0:$data['settlement_change_res'];

        }

        $data = !empty($data)?$data:(object)[];





        $this->success_json($data);



    }


    /**
     * @desc 加入黑名单
     * @author Jackson
     * @Date 2019-01-24 16:01:00
     * @return array()
     **/
    public function supplier_opr_black_list()
    {

        $parames = gp();
        if (empty($parames['supplier_code']) or empty($parames['reason']) or empty($parames['image_url'])) {
            $this->send_data(null, "参数【供应商编码|原因|图片】缺失", false);
        }


        $result = $this->_modelObj->supplier_opr_black_list($parames);

        if ($result['code']) {
            $this->success_json('供应商加入黑名单成功');


        } else {
            $this->error_json('供应商加入黑名单失败');


        }

    }

    /*
     * @desc 黑名单详情
     */
    public function black_list_detail()
    {
        $parames = gp();
        if (empty($parames['supplier_code'])) {
            $this->error_json('供应商编码缺失');
        }

        $info = $this->_modelObj->purchase_db->select('*')->from('supplier_blacklist')->where('supplier_code',$parames['supplier_code'])->get()->row_array();
        $this->success_json($info);



    }



    /**
     * @desc 供应商黑名单列表
     * @author Dean
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function black_list()
    {
        $params = gp();
        $data = array();

        $data['data_list'] = $this->_modelObj->black_list($params);
        $down_settlement = $this->settlementModel->get_settlement($params);
        $down_settlement = array_column($down_settlement['list'],NULL,'settlement_code');
        $data['data_list']['drop_down_box']['down_settlement'] = $down_settlement;

//        if( !empty($data['data_list']) ) {
//            foreach( $data['data_list']['list'] AS $key=>$value ) {
//                if( isset($down_settlement[$value['supplier_settlement']]) ) {
//
//                    $data['data_list']['list'][$key]['supplier_settlement'] = $down_settlement[$value['supplier_settlement']]['settlement_name'];
//                }
//            }
//        }
//        //增加供应商三个月的统计金额
//        $this->load->model('purchase/Purchase_order_model', 'purchaseOrderModel');
//        $data['data_list']['count'] =1;
//        $this->purchaseOrderModel->statistics_cooperation_amount($data['data_list']);



        $this->send_data($data, '供应商信息列表', true);
    }


    //关联供应商
    public function modify_relation_supplier()
    {
        $parames = gp();
        if (empty($parames['supplier_code'])) {
           $this->error_json('供应商编码不能为空');
        }

        try {

            $old_supplier_info = $this->supplier_model->get_supplier_info($parames['supplier_code'], false);
            $waiting_review_status_arr = [SUPPLIER_WAITING_PURCHASE_REVIEW, SUPPLIER_WAITING_SUPPLIER_REVIEW, SUPPLIER_FINANCE_REVIEW];
            if (in_array($old_supplier_info['audit_status'], $waiting_review_status_arr)) {
                throw new Exception('该供应商处于待审状态，暂不可更新资料');
            }


            $supplier_code = $parames['supplier_code'];

            //关联供应商模块开始
            $relation_supplier_codes = [];//关联供应商编码
            $relation_info = !empty($parames['relationSupplierInfo']) ? $parames['relationSupplierInfo'] : null;//关联供应商
            //历史关联供应商
            $old_relation_suppliers = $this->supplier_model->get_relation_supplier_detail($supplier_code, false);

            if (!empty($relation_info)) {
                $relation_supplier_codes = array_column($relation_info, 'relation_code');
                $num = count(array_unique($relation_supplier_codes));
                if ($num != count($relation_info)) {
                    throw new Exception("关联供应商有重复的");

                }
                foreach ($relation_info as $code) {
                    if ($code['relation_code'] == $supplier_code) {
                        throw new Exception("不能选定自己作为关联供应商");
                    }
                }
            }

            $insert_data = $delete_data = $change_data_log = [];// 初始化变量
            if (!empty($old_relation_suppliers) || !empty($relation_supplier_codes)) {
                $old_supplier_arr = [];
                if (!empty($old_relation_suppliers)) {
                    $old_supplier_arr = array_keys($old_relation_suppliers);
                }

                $add_info = array_diff($relation_supplier_codes, $old_supplier_arr);//添加的关联供应商
                $del_info = array_diff($old_supplier_arr, $relation_supplier_codes);//删除的关联供应商
                $update_info = array_intersect($relation_supplier_codes, $old_supplier_arr);//更新的供应商信息

                if (!empty($add_info)) {
                    $add_str = implode(',', $add_info);
                    $change_data_log['supplier_relation'][] = '添加了关联供应商:' . $add_str;
                }
                if (!empty($del_info)) {
                    $del_str = implode(',', $del_info);
                    $change_data_log['supplier_relation'][] = '删除了关联供应商:' . $del_str;
                }

                if (!empty($update_info)) {
                    foreach ($old_relation_suppliers as $su_key => $old_supplier_info) {
                        foreach ($update_info as $update) {
                            if ($update == $su_key) {
                                foreach ($relation_info as $r_info) {
                                    if ($r_info['relation_code'] == $update) {
                                        $type = $r_info['supplier_type']??'';
                                        $reason = $r_info['supplier_reason']??'';
                                        $type_remark = $r_info['supplier_type_remark']??'';
                                        $reason_remark = $r_info['supplier_reason_remark']??'';
                                    }
                                }

                                if (isset($type) && $type != $old_supplier_info['supplier_type']) {
                                    $change_data_log['supplier_relation'][] = '关联供应商' . $update . '更改了关联供应商关联类型由' . getSupplierRelationType($old_supplier_info['supplier_type']) . '更改成' . getSupplierRelationType($type);
                                }

                                if (isset($reason) && $reason != $old_supplier_info['supplier_reason']) {
                                    $change_data_log['supplier_relation'][] = '关联供应商' . $update . '更改了关联供应商关联原因由' . getSupplierRelationReason($old_supplier_info['supplier_reason']) . '更改成' . getSupplierRelationReason($reason);
                                }

                                if (isset($type_remark) && $type_remark != $old_supplier_info['supplier_type_remark']) {
                                    $change_data_log['supplier_relation'][] = '关联供应商' . $update . '更改了关联供应商关联备注由' . $old_supplier_info['supplier_type_remark'] . '更改成' . $type_remark;
                                }

                                if (isset($reason_remark) && $reason_remark != $old_supplier_info['supplier_reason_remark']) {
                                    $change_data_log['supplier_relation'][] = '关联供应商' . $update . '更改了关联供应商原因备注由' . $old_supplier_info['supplier_reason_remark'] . '更改成' . $reason_remark;
                                }
                            }
                        }
                    }
                }

                if ($relation_info) {//更新供应商
                    foreach ($relation_info as $info) {
                        // 其他资料
                        if(isset($info['other_images']) and !empty($info['other_images'])){
                            $other_images = explode(';',$info['other_images']);
                            $other_images_log = '其他资料:<br>';
                            foreach($other_images as $other_image_val){
                                $other_images_log .= '<img src="'.$other_image_val.'" width="40" height="40"><br>';
                            }
                            $insert_data['images_data']['other_proof'] = $info['other_images']??'';// 添加到其他资料里面

                            $change_data_log['other_proof'] = $other_images_log;
                        }

                        $insert = [];
                        $insert['supplier_code'] = $supplier_code;
                        $insert['relation_code'] = $info['relation_code'];
                        $insert['supplier_type'] = $info['supplier_type'];
                        $insert['supplier_reason'] = $info['supplier_reason'];
                        $insert['supplier_type_remark'] = $info['supplier_type_remark']??'';
                        $insert['supplier_reason_remark'] = $info['supplier_reason_remark']??'';
                        $insert['other_images'] = $info['other_images']??'';

                        $insert_data['relation_supplier'][] = $insert;
                    }
                }

                if (empty($relation_supplier_codes) && !empty($old_supplier_arr)) {//删除供应商
                    $delete_data['relation_supplier'][] = $old_relation_suppliers;
                }
            }


            // 所有 变更的信息
            $all_change_data = [
                'insert_data' => $insert_data,
                'delete_data' => $delete_data,
                'change_data_log' => $change_data_log,
            ];


            //修改日志
            $all_change_data_log = [
                'change_data_log' => $change_data_log,
                'insert_data' => $insert_data,
                'delete_data' => isset($delete_data_log) ? $delete_data_log : [],
            ];


            $apply_type = 4;//关联供应商

            $insert_data = [
                'supplier_code' => $supplier_code,
                'action' => 'supplier/supplier/collect_update_supplier',
                'message' => json_encode($all_change_data),
                'need_supplier_audit' => 1,
                'audit_status' => SUPPLIER_WAITING_PURCHASE_REVIEW,
                'create_user_id' => getActiveUserId(),
                'create_user_name' => getActiveUserName(),
                'create_time' => date('Y-m-d H:i:s'),
                'apply_type' => $apply_type,
                'apply_no' => $this->supplier_model->get_prefix_new_number('gy'),
                'source' => 1
            ];


            $result = $this->updateLogModel->insert_one($insert_data);

            if (empty($result)) {
                throw new Exception("保存更新的数据失败");
            }
            $this->auditModel->create_record($supplier_code, $apply_type);// 创建供应商审核记录*/


            operatorLogInsert(
                [
                    'id' => $supplier_code,
                    'type' => 'supplier_update_log',
                    'content' => "关联供应商修改",
                    'detail' => json_encode(isset($all_change_data_log) ? $all_change_data_log : []),
                    'operate_type' => SUPPLIER_NORMAL_UPDATE
                ]
            );

            $this->success_json('关联成功');

        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }


    public function show_all_relation_supplier()
    {
        $parames = gp();
        if (empty($parames['supplier_code'])) {
            $this->error_json('供应商编码不能为空');
        }

        $relation_info_show = [];
        $relation_info = $this->supplier_model->get_relation_supplier_detail($parames['supplier_code']);
        if (!empty($relation_info)) {
            foreach ($relation_info as $re_info) {
                $relation_info_show[] = $re_info;

            }

        }

        $this->success_json($relation_info_show);




    }


    /**
     * @desc
     * @author Dean
     * @Date 2019-05-14 16:01:00
     * @return array()
     **/
    public function update_supplier_product_line(){

        $line_list = $this->Product_line_model->get_product_line_list_first();
        $line_list = array_column($line_list,'product_line_name','product_line_id');



        $parames = gp();
        if (empty($parames['product_line_id'])||empty($parames['supplier_ids_arr'])) {
            $this->error_json( "数据异常");
        }

        $supplier_ids_arr = $parames['supplier_ids_arr'];



        try{
            foreach ( $supplier_ids_arr  as  $supplier_id) {
                $old_supplier_basic = $this->Supplier_model->find_supplier_one($supplier_id, null, null, false);
                if(empty($old_supplier_basic)){
                    throw new Exception("该供应商不存在");
                }
                $supplier_code      = $old_supplier_basic['supplier_code'];
                $supplier_name      = $old_supplier_basic['supplier_name'];

                $update_log_record = $this->updateLogModel->get_latest_audit_result($supplier_code);
                if($update_log_record
                    and !in_array($update_log_record['audit_status'],[
                        SUPPLIER_PURCHASE_REJECT,
                        SUPPLIER_SUPPLIER_REJECT,
                        SUPPLIER_FINANCE_REJECT,
                        SUPPLIER_REVIEW_PASSED])){
                    throw new Exception("供应商【".$supplier_name."】存在【未完结】的更新记录");
                }

                //查询供应商产品线是否存在
                $product_line_info = $this->supplier_model->purchase_db->select('*')->from('supplier_analysis_product_line')->where('supplier_code',$supplier_code)->get()->row_array();

                $first_product_line_id = $product_line_info['first_product_line_id']??0;

                $first_product_line_name = $line_list[$first_product_line_id]??'';

                $update_product_line_name = $line_list[$parames['product_line_id']];


                if (!empty($product_line_info)) {//更新
                    $data = ['supplier_code'=>$supplier_code,'supplier_name'=>$supplier_name,'first_product_line'=>$parames['product_line_id'],'modify_user_name'=>getActiveUserName(),'modify_time'=>date('Y-m-d H:i:s')];
                   $flag= $this->supplier_model->purchase_db->where('supplier_code',$supplier_code)->update('supplier_analysis_product_line',$data);


                } else {
                    $data = ['supplier_code'=>$supplier_code,'supplier_name'=>$supplier_name,'first_product_line'=>$parames['product_line_id'],'create_user_name'=>getActiveUserName(),'create_time'=>date('Y-m-d H:i:s')];
                  $flag=  $this->supplier_model->purchase_db->insert('supplier_analysis_product_line',$data);



                }
                if (empty($flag)) {
                    throw new Exception("供应商【".$supplier_name."】更新主产品线失败");

                }



                //修改
                if ($first_product_line_id!=$parames['product_line_id']) {

                        //记录日志
                        operatorLogInsert(
                            [
                                'id'      => $supplier_code,
                                'type'    => 'supplier_update_log',
                                'content' => "供应商主产品线从:".$first_product_line_name.'修改为:'.$update_product_line_name,
                                'detail'  =>  "供应商主产品线从:".$first_product_line_name.'修改为:'.$update_product_line_name,
                            ]
                        );
                    }

                }


            $this->success_json('修改成功');

        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }


    }



    /**
     * 获取供应商审核列表
     * /abnormal/abnormal_list/get_abnormal_list
     * @author Dean 2019/01/16
     */
    public function audit_supplier_list(){



        $params=[

            'audit_status' => $this->input->get_post('audit_status'),// 审核状态
            'apply_no' =>$this->input->get_post('apply_no'),//申请编号
            'settlement' =>$this->input->get_post('settlement'),
            'source' =>$this->input->get_post('source'),
            'apply_type'=>$this->input->get_post('apply_type'),
            'apply_id'=>$this->input->get_post('apply_id'),
            'audit_id'=>$this->input->get_post('audit_id'),
            'apply_time_start'=>$this->input->get_post('apply_time_start'),
            'apply_time_end'=>$this->input->get_post('apply_time_end'),
            'audit_time_start'=>$this->input->get_post('audit_time_start'),
            'audit_time_end'=>$this->input->get_post('audit_time_end'),
            'supplier_code'=>$this->input->get_post('supplier_code'),
            'is_basic_change'=>$this->input->get_post('is_basic_change'),
            'is_relation_change'=>$this->input->get_post('is_relation_change'),
            'is_contact_change'=>$this->input->get_post('is_contact_change'),
            'is_payment_change'=>$this->input->get_post('is_payment_change'),
            'is_proof_change'=>$this->input->get_post('is_proof_change'),
            'payment_optimization'=>$this->input->get_post('payment_optimization'),
            'audit_time_sort'=>$this->input->get_post('audit_time_sort'),
            'create_time_sort'=>$this->input->get_post('create_time_sort'),





        ];


        $page_data=$this->format_page_data();
        $result=$this->_modelObj->audit_supplier_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);




    }

    /**
     * 格式化分页参数
     *  @author Jaxton
     */
    protected function format_page_data(){
        $page           = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        if(empty($limit) or $limit < 0 ) $limit = 20;
        $offset        = ($page - 1) * $limit;
        return [
            'offset' => $offset,
            'limit' => $limit,
            'page' => $page
        ];
    }

    



       
       

    /**
     * 获取sku系统 + 验证供应商是否允许禁用
     */
    public function get_confirm_sku_info()
    {
        $supplier_code           =  $this->input->get_post('supplier_code');//供应商等级
        if (empty($supplier_code)) {
            $this->error_json('供应商编码为空');
        }

        //先获取采购系统在售中、试卖在售中、新系统开发中的sku
        $sku_list = $this->supplier_model->get_confirm_sku_info($supplier_code);

        $message = [];
        if($sku_list){
            if(count($sku_list) > 10){
                // 最多返回10个，超过10个则追加一个 ...
                $sku_list = array_merge(array_slice($sku_list,0,10),['...']);
            }
            $message['enable_sku_list'] = implode(',',$sku_list);
        }


        // 验证供应商是否在审核中
        $in_audit_record = $this->updateLogModel->get_latest_audit_result($supplier_code,true);
        if($in_audit_record) $message['in_audit'] = $supplier_code . ' 供应商审核流程未完结，不允许禁用';

        $this->success_json($message);
    }

    //修改供应商等级
    public function modify_supplier_level_grade()
    {


        $supplier_level           =  $this->input->get_post('supplier_level');//供应商等级
        $supplier_grade           =  $this->input->get_post('supplier_grade');//供应商分数
        $supplier_code            =  $this->input->get_post('supplier_code');//供应商编码
        $apply_remark             =   $this->input->get_post('apply_remark');//申请备注


        if (empty($supplier_grade) || empty($supplier_grade)) {
            $this->error_json('供应商等级或者供应商分数不能为空');

        }

        if (empty($apply_remark)) {
            $this->error_json('申请备注不能为空');

        }

        if (empty($supplier_code)) {
            $this->error_json('供应商编码为空');

        }
        //查询是否存在供应商审核记录
        $audit_log  = $this->supplier_model->get_level_grade_log($supplier_code,[SUPPLIER_WAITING_PURCHASE_REVIEW_LEVEL_GRADE,SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE,

            SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE]);

        if (!empty($audit_log)) {
            $this->error_json('存在待审核记录');


        }


        $supplier_info  = $this->supplier_model->find_supplier_one(0,$supplier_code,null,false);

        if ($supplier_info['supplier_level'] == $supplier_level&&$supplier_info['supplier_grade'] == $supplier_grade) {
            $this->error_json('供应商等级和供应商分数都没有改变');

        }

        if ($supplier_info['supplier_level'] != $supplier_level) {
            $before_v = getSupplierLevel($supplier_info['supplier_level']);
            $current_v = getSupplierLevel($supplier_level);
            $change_data_log['basic']['supplier_level'] = '修改前:'.$before_v.';修改后:'.$current_v;


        }

        if ($supplier_info['supplier_grade'] != $supplier_grade) {

            $change_data_log['basic']['supplier_grade'] = '修改前:'.$supplier_info['supplier_grade'].';修改后:'.$supplier_grade;


        }
        $all_change_data_log =['change_data_log'=>$change_data_log];




        //生成一条审核记录
        $insert_data = [
            'supplier_code'=>$supplier_code,
            'audit_status' =>1,
            'create_user_id'=>getActiveUserId(),
            'create_user_name'=>getActiveUserName(),
            'create_time'     =>date('Y-m-d H:i:s'),
            'apply_remark'     =>$apply_remark,
            'apply_no'         =>$this->supplier_model->get_prefix_new_number('dj'),
            'old_supplier_level'=>$supplier_info['supplier_level'],
            'new_supplier_level'=>$supplier_level,
            'old_supplier_grade'=>$supplier_info['supplier_grade'],
            'new_supplier_grade'=>$supplier_grade,

        ];

       $flag = $this->supplier_model->purchase_db->insert('supplier_level_grade_log',$insert_data);

        operatorLogInsert(
            [
                'id'      => $supplier_code,
                'type'    => 'supplier_update_log',
                'content' => "供应商等级修改申请",
                'detail'  => json_encode(isset($all_change_data_log)?$all_change_data_log:[]),
                'ext'     => '[申请备注]:'.$apply_remark,
                'operate_type'  => SUPPLIER_LEVEL_GRADE_OPR
            ]
        );


        if ($flag) {
           $this->success_json('申请成功');

       } else {
           $this->error_json('申请失败');

       }


    }




    /**
     * @desc 供应商等级审核  审核通过更新供应商信息
     * @author Dean
     * @Date 2021-01-16 16:01:00
     * @return array()
     **/
    public function level_grade_review()
    {
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }


        $parames = gp();
        if (!isset($parames['id']) || empty($parames['id'])) {
            $this->error_json("审核字段id不能为空");
        }
        if (!isset($parames['audit_status']) || !in_array($parames['audit_status'], array(1, 2))) {
            $this->error_json("audit_status 不能为空，或 必需要在1,2之间：1审核通过,2驳回");
        }
        if($parames['audit_status'] == 2 and empty($parames['remarks'])){
            $this->error_json("驳回必须填写驳回备注");

        }


        $audit_status  = $parames['audit_status'];
        $remarks       = isset($parames['remarks'])?$parames['remarks']:'';

        //查询其他供应商



        $update_log = $this->supplier_model->level_grade_detail($parames['id']);



        //判断审核状态是否同步
        if ($update_log['audit_status']!=$parames['page_audit_status']) {
            $this->error_json("供应商已被审核");

        }

        if(empty($update_log) or !in_array($update_log['audit_status'],[SUPPLIER_WAITING_PURCHASE_REVIEW_LEVEL_GRADE,SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE,SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE])){
            $this->error_json("该供应商非待审核状态");
        }




        // 供应商 下一状态
        $new_status = null;
        if($audit_status == 1){// 审核通过
            if($update_log['audit_status'] == SUPPLIER_WAITING_PURCHASE_REVIEW_LEVEL_GRADE){

                    $new_status = SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE;


            }elseif($update_log['audit_status'] == SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE){

                 $new_status = SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE;
            }elseif($update_log['audit_status'] == SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE){
                $new_status = SUPPLIER_REVIEW_PASSED_LEVEL_GRADE;
            }
        }else{// 驳回
            if($update_log['audit_status'] == SUPPLIER_WAITING_PURCHASE_REVIEW_LEVEL_GRADE){
                $new_status = SUPPLIER_PURCHASE_REJECT_LEVEL_GRADE;
            }elseif($update_log['audit_status'] == SUPPLIER_WAITING_SUPPLIER_REVIEW_LEVEL_GRADE){
                $new_status = SUPPLIER_SUPPLIER_REJECT_LEVEL_GRADE;
            }elseif($update_log['audit_status'] == SUPPLIER_MANAGE_REVIEW_LEVEL_GRADE){
                $new_status = SUPPLIER_MANAGE_REJECT_LEVEL_GRADE;
            }
        }

        $result = $this->supplier_model->do_level_grade_supplier($parames['id'],$new_status,$remarks);
        if($result['code']){
            $this->success_json("审核成功");
        }else{
            $this->error_json($result['message']);

        }

    }



    /**
     * 获取供应商等级审核列表
     * /abnormal/abnormal_list/get_abnormal_list
     * @author Dean 2019/01/16
     */
    public function audit_supplier_level_grade_list(){



        $params=[

            'audit_status' => $this->input->get_post('audit_status'),// 审核状态
            'apply_no' =>$this->input->get_post('apply_no'),//申请编号
            'old_supplier_level' =>$this->input->get_post('old_supplier_level'),//变更前等级
            'new_supplier_level' =>$this->input->get_post('new_supplier_level'),//变更后等级
            'apply_id'=>$this->input->get_post('apply_id'),
            'audit_id'=>$this->input->get_post('audit_id'),
            'apply_time_start'=>$this->input->get_post('apply_time_start'),
            'apply_time_end'=>$this->input->get_post('apply_time_end'),
            'audit_time_start'=>$this->input->get_post('audit_time_start'),
            'audit_time_end'=>$this->input->get_post('audit_time_end'),
            'supplier_code'=>$this->input->get_post('supplier_code')



        ];


        $page_data=$this->format_page_data();
        $result=$this->_modelObj->audit_supplier_level_grade_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);




    }


    //获取供应商分数等级审核历史记录
    public function get_audit_level_grade_log()
    {
        $supplier_code           =  $this->input->get_post('supplier_code');//供应商编码

        if (empty($supplier_code)) {
            $this->error_json("供应商编码为空");

        }
        $logs  = $this->supplier_model->purchase_db->select('*')->from('operator_log')->where('record_number',$supplier_code)
            ->where('record_type','supplier_update_log')->where('operate_type',SUPPLIER_LEVEL_GRADE_OPR)->order_by('id','desc')->get()->result_array();



        $this->success_json($logs);



    }



    //获取结算方式更改日志
    public function get_settlement_change()
    {
        $supplier_code           =  $this->input->get_post('supplier_code');//供应商编码

        if (empty($supplier_code)) {
            $this->error_json("供应商编码为空");

        }
        $logs  = $this->supplier_model->purchase_db->select('*')->from('supplier_payment_log')->where('supplier_code',$supplier_code)->order_by('id','desc')->get()->result_array();

        if (!empty($logs)) {
            foreach ($logs as $key=>$data) {
                if (!empty($data['content'])) {
                    $logs[$key]['content'] = json_decode($data['content'],true);

                }
                $logs[$key]['opr_type'] = getSupplierOperateType($data['opr_type']);



            }

        }



        $this->success_json($logs);



    }


    /**
     * @desc 拜访供应商表
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()注释
     **/
    public function supplier_visit_list()
    {


        $params = gp();
        $data = array();

        if(isset($params['overseas_ids']) && !empty($params['overseas_ids']))
        {
            $params['overseas_supplier_code']  = ['2'];
            $groupids = $this->buyerModel->getUserData($params['overseas_ids'],2);
            if(!empty($groupids)){

                $params['overseas_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        if( isset($params['domestic_ids']) && !empty($params['domestic_ids'])){
            $params['domestic_supplier_code'] =['2'];
            $groupids = $this->buyerModel->getUserData($params['domestic_ids'],1);
            if(!empty($groupids)){

                $params['domestic_supplier_code'] = array_unique(array_column($groupids,'supplier_code'));
            }
        }

        $result = $this->_modelObj->supplier_visit_list($params);
        $data['data_list']['list'] =$result['list'];
        $data['data_list']['count'] =$result['paging_data']['total'];
        $down_settlement = $this->settlementModel->get_settlement($params);
        $down_settlement = array_column($down_settlement['list'],NULL,'settlement_code');
        $data['data_list']['drop_down_box']['down_settlement'] = $down_settlement;


//        if( !empty($data['data_list']) ) {
//            foreach( $data['data_list']['list'] AS $key=>$value ) {
//                if( isset($down_settlement[$value['supplier_settlement']]) ) {
//
//                    $data['data_list']['list'][$key]['supplier_settlement'] = $down_settlement[$value['supplier_settlement']]['settlement_name'];
//                }
//            }
//        }
//        //增加供应商三个月的统计金额
//        $this->load->model('purchase/Purchase_order_model', 'purchaseOrderModel');
//        $data['data_list']['count'] =1;
//        $this->purchaseOrderModel->statistics_cooperation_amount($data['data_list']);

        $this->send_data($data, '供应商拜访列表', true);


    }


//申请拜访
    public function apply_visit()
    {



        $params=[
            'supplier_code' => $this->input->get_post('supplier_code'),// 供应商编码
            'type' =>$this->input->get_post('type'),//公司类型
            'cate_attr' =>$this->input->get_post('cate_attr'),//类目属性
            'cate' =>$this->input->get_post('cate'),//包含类目
            'cate_name' =>$this->input->get_post('cate_name'),//类目名称，逗号隔开
            'province'=>$this->input->get_post('province'),
            'city'=>$this->input->get_post('city'),
            'area'=>$this->input->get_post('area'),
            'address'=>$this->input->get_post('address'),
            'contact'=>$this->input->get_post('contact'),
            'phone'=>$this->input->get_post('phone'),
            'apply_id'=>$this->input->get_post('apply_id'),
            'participants'=>$this->input->get_post('participants'),
            'apply_name'=>$this->input->get_post('apply_name'),
            'participant_names'=>$this->input->get_post('participant_names'),
            'apply_sector'=>$this->input->get_post('apply_sector'),
            'participating_sector'=>$this->input->get_post('participating_sector'),
            'start_time'=>$this->input->get_post('start_time'),
            'end_time'=>$this->input->get_post('end_time'),
            'visit_aim'=>$this->input->get_post('visit_aim'),
            'remark'=>$this->input->get_post('remark'),
            'visit_id'    =>$this->input->get_post('visit_id'),//拜访id
             'cate_name'   =>$this->input->get_post('cate_name'),
            'position'=>$this->input->get_post('position'),//职位
            'phone_backup'=>$this->input->get_post('phone_backup'),//备用联系方式





        ];

        $result = $this->supplier_model->apply_visit($params);

        if ($result['code']) {
            $this->success_json('操作成功');

        } else {
            $this->error_json($result['msg']);


        }







    }



    /**
     * 获取供应商审核列表
     * /abnormal/abnormal_list/get_abnormal_list
     * @author Dean 2019/01/16
     */
    public function visit_supplier_audit_list(){


        $params = gp();
        $page_data=$this->format_page_data();
        $result=$this->_modelObj->visit_supplier_audit_list($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);




    }


    //批量审核拜访供应商
    public function audit_visit_supplier()
    {
        $ids = $this->input->get_post('ids');
        $remark = $this->input->get_post('remark');
        $audit_status = $this->input->get_post('audit_status');
        $remark = $remark??'';
        $error_list = [];
        if (!empty($ids)) {
            foreach ($ids as $id ) {
                $result =  $this->supplier_model->audit_visit_supplier($id,$audit_status,$remark);
                if (!$result['code']) {
                    $error_list[] = $result['msg'];

                }


            }
            $this->success_json($error_list);

        } else {
            $this->error_json('数据为空');

        }



    }


    //上传拜访报告

    public function upload_visit_report()
    {

        $visit_id = $this->input->get_post('visit_id');//拜访id
        $type     = $this->input->get_post('type');//验证

        //提交参数
        $params = [
            'answer'=>$this->input->get_post('answer'),
            'team_company'=>$this->input->get_post('team_company'),
            'certified_product'=>$this->input->get_post('certified_product'),
            'percentage_turnover'=>$this->input->get_post('percentage_turnover'),
            'supplier_image'     =>$this->input->get_post('supplier_image'),
            'honor_certificate'     =>$this->input->get_post('honor_certificate'),
            'product_line'     =>$this->input->get_post('product_line'),
            'improve_situation'     =>$this->input->get_post('improve_situation'),
            'conclusion'            =>$this->input->get_post('conclusion')//评比结论描述





        ];



        $now_time = time();


        if (empty($visit_id)) {
            $this->error_json('拜访申请id为空');

        }
        if (empty($type)) {
            $this->error_json('申请类型缺失');

        }

        $audit_info  = $this->supplier_model->get_audit_visit_info($visit_id,false);
        $status  = $this->supplier_model->visit_supplier_status($audit_info);//获取拜访状态
        if (in_array($status,[SUPPLIER_VISIT_WAIT_AUDIT,SUPPLIER_VISIT_REJECT])) {
            $this->error_json('该状态下不能提交拜访报告');


        }
        //允许提交拜访报告的人
        $participate_ids = explode(',',$audit_info['participants']);
        array_push($participate_ids,$audit_info['apply_id']);
        $login_id = getActiveUserId();
        if (!in_array($login_id,array_unique($participate_ids))) {
            $this->error_json('只有参与人或者申请可以提交拜访报告');


        }

        //获取最早一次拜访报告提交时间
        $report_early = $this->supplier_model->purchase_db->select('create_time')->from('supplier_visit_report')->where('visit_id',$visit_id)->order_by('id','asc')->limit(1)->get()->row_array();
        if (!empty($report_early)&&($now_time>(strtotime($report_early['create_time'])+864000))) {
            $this->error_json('不能再上传拜访报告了');


        }

        if ($type == 1) {
            $this->success_json($audit_info);


        } else {
            $result  = $this->supplier_model->upload_visit_report($visit_id,$params);

            if ($result['code']) {
                $this->success_json('上传成功');


            } else {
                $this->error_json($result['msg']);
            }

        }




    }

    //获取拜访详情
    public function get_visit_detail_info()
    {
        $visit_id = $this->input->get_post('visit_id');//拜访id
        if (empty($visit_id)) {
            $this->error_json('请传拜访id');

        }
        $data= $this->supplier_model->get_audit_visit_info($visit_id,true);
        $this->success_json($data);







    }



    /**
     * 拜访列表导出CSV
     *      1、勾选-按照 ID 导出
     *      2、未勾选-按照 查询条件导出
     * @author Dean
     */
    public function supplier_visit_list_csv(){
        set_time_limit(0);
        ini_set('memory_limit','2500M');
        $this->load->helper('export_csv');


        $params = gp();

        if (!empty($params['groupname'])) {
            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['groupname']);
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
                $params['groupdatas'] = $groupdatas;
            }

        }
       // ($params,$offset,$limit,$page=1,$export=false)
        $list = $this->supplier_model->supplier_visit_list($params,true);



        if (isset($list['list']) && $item = &$list['list']) {

            foreach ($item as $key => $info) {

                //供应商等级
                $_supplierLevel = getSupplierLevel($info['supplier_level']);
                if ($_supplierLevel) {
                    $item[$key]['supplier_level'] = $_supplierLevel;
                }

                //供应商结算方式
                if (isset($down_settlement)) {
                    if($info['supplier_settlement'] and strpos($info['supplier_settlement'],',') !== false){
                        $supplier_settlements = explode(',',$info['supplier_settlement']);
                        $_supplier_settlement = [];
                        foreach($supplier_settlements as $payment_method){
                            $_supplier_settlement[] = $down_settlement[$payment_method]??'';
                        }
                        $item[$key]['supplier_settlement'] = implode('/',$_supplier_settlement);
                    }else{
                        $_supplier_settlement = $down_settlement[$info['supplier_settlement']]??'';
                        if ($_supplier_settlement) {
                            $item[$key]['supplier_settlement'] = $_supplier_settlement;
                        }
                    }

//                    pr($down_settlement);exit;
//                    $item[$key]['supplier_settlement'] = isset($downSettlement[$info['supplier_settlement']]) ? $downSettlement[$info['supplier_settlement']] : $info['supplier_settlement'];
                }

                //支付方式
                if($info['payment_method'] and strpos($info['payment_method'],',') !== false){
                    $payment_methods = explode(',',$info['payment_method']);
                    $_paymentMethod = [];
                    foreach($payment_methods as $payment_method){
                        $_paymentMethod[] = getPayType($payment_method);
                    }
                    $item[$key]['payment_method'] = implode('/',$_paymentMethod);
                }else{
                    $_paymentMethod = getPayType($info['payment_method']);
                    if ($_paymentMethod) {
                        $item[$key]['payment_method'] = $_paymentMethod;
                    }
                }

                //申请状态
                $_reviewStatus = (!is_null($info['audit_status']) and $info['audit_status']>=0)? show_status($info['audit_status']) : $info['audit_status'];
                if ($_reviewStatus) {
                    $item[$key]['audit_status_name'] = $_reviewStatus;
                }

                //启用或禁用状态
//                $_eableStatus = enable_status($info['status']);
//                if ($_eableStatus) {
//                    $item[$key]['status'] = $_eableStatus;
//                }

            }

        }


        $columns = [
            '供应商来源',
            '开发来源',
            '平台来源',
            '供应商代码',
            '主产品线',
            '供应商',
            '结算方式',
            '支付方式',
            '备用下单次数',
            '申请人',
            '申请部门',
            '拜访状态',
            '申请时间',
            '出发时间',
            '结束时间',
            '前一整月入库总金额',
            '近90天入库总金额',
            '拜访次数',
            '上传报告时间',
            '拜访目的',
            'SKU总数量',
            'SKU在售数量',
            'SKU已停售',
            'SKU其他状态',
            '供应商业务线',
            '是否开启门户推送订单',
            '创建人',
            '创建时间',
            '最新启用时间',
            '报损金额',
            '审核状态',
            '禁用次数',
            '拜访结果评级',
            '拜访结果分数',

        ];

        $data_list_temp = [];
        if($list['list']){
            foreach($list['list'] as $v_value){

                $v_value_tmp['supplier_source_name']       = $v_value['supplier_source_name'];
                $v_value_tmp['develop_source']     = $v_value['develop_source'];
                $v_value_tmp['platform_source']    = $v_value['platform_source'];
                $v_value_tmp['supplier_code']       = $v_value['supplier_code'];
                $v_value_tmp['linelist_cn_name']      =  $v_value['linelist_cn_name'];
                $v_value_tmp['supplier_name']          = $v_value['supplier_name'];
                $v_value_tmp['supplier_settlement']      = $v_value['supplier_settlement'];
                $v_value_tmp['payment_method']  = $v_value['payment_method'];
                $v_value_tmp['order_num'] = $v_value['order_num'];
                $v_value_tmp['apply_name']  = $v_value['apply_name'];
                $v_value_tmp['apply_sector'] = $v_value['apply_sector'];
                $v_value_tmp['visit_status']        = $v_value['visit_status'];
                $v_value_tmp['apply_time']        = $v_value['apply_time'];
                $v_value_tmp['start_time']      = $v_value['start_time'];
                $v_value_tmp['end_time']      = $v_value['end_time'];
                $v_value_tmp['cooperation_amount_one_month']       = $v_value['cooperation_amount_one_month'];
                $v_value_tmp['cooperation_amount']         = $v_value['cooperation_amount'];
                $v_value_tmp['visit_times']         = $v_value['visit_times']['times'];
                $v_value_tmp['report_time']      = $v_value['report_time'];
                $v_value_tmp['visit_aim']        = $v_value['visit_aim'];
                $v_value_tmp['sku_num']        = $v_value['sku_num'];
                $v_value_tmp['sku_sale_num']            = $v_value['sku_sale_num'];
                $v_value_tmp['sku_no_sale_num']         = $v_value['sku_no_sale_num'];
                $v_value_tmp['sku_other_num']         = $v_value['sku_other_num'];
                $v_value_tmp['business_line']         = $v_value['business_line'];
                $v_value_tmp['is_gateway_ch']         = $v_value['is_gateway_ch'];
                $v_value_tmp['create_user_name']         = $v_value['create_user_name'];
                $v_value_tmp['create_time']         = $v_value['create_time'];
                $v_value_tmp['restart_date']         = $v_value['restart_date'];
                $v_value_tmp['loss_money']         = $v_value['loss_money'];
                $v_value_tmp['audit_status_name']         = $v_value['audit_status_name'];
                $v_value_tmp['disabled_times']         = $v_value['disabled_times'];
                $v_value_tmp['visit_level']         = $v_value['visit_level'];
                $v_value_tmp['visit_grade']         = $v_value['visit_grade'];


                $data_list_temp[] = $v_value_tmp;
            }
        }

        unset($data_list);
        $data = [
            'key' => $columns,
            'value' => $data_list_temp,
        ];
        $this->success_json($data);
    }

    public function get_visit_op_log_list()
    {

        $supplier_code  = $this->input->get_post('supplier_code');
        if (empty($supplier_code)) {
            $this->error_json('请传供应商编码');

        }
        $operate_type   = [
            1=>'申请拜访',
            2=>'重新编辑',
            3=>'经理审核',
            4=>'部门负责人审核',
            5=>'提交拜访报告'
        ];
        $CI = &get_instance();
        $CI->load->model('operator_log_model');
        $field ='content,content_detail,operator,operate_time,operator_id,ext,operate_type';
        //$data = $CI->operator_log_model->query_logs(['type'=>'SUPPLIER_UPDATE_LOG','id'=>$supplier_code,'page'=>$page,'limit'=>$pageSize],$field);
        $data = $CI->operator_log_model->query_logs(['type'=>'supplier_visit','id'=>$supplier_code],$field);
        if (!empty($data)) {
            foreach ($data as &$detail) {
                $detail['operate_type'] = $operate_type[$detail['operate_type']];

            }

        }
        $this->success_json($data);






    }

    //下载拜访报告

    public function download_visit_report()
    {
        $visit_id = $this->input->get_post('visit_id');
        $type = $this->input->get_post('type');//1详情 2下载

        if (empty($visit_id)) {

            $this->error_json('请传拜访id');


        }
        if (empty($type)) {

            $this->error_json('请传操作方式');


        }
        if ($type == 1) {
            $visit_info = $this->supplier_model->get_audit_visit_info($visit_id,true);
            if (empty($visit_info['report_info'])) {
                $this->error_json('请先上传拜访报告');

            }
            $this->success_json($visit_info);


        } else {
            $visit_info['apply_info'] = $this->supplier_model->get_audit_visit_info($visit_id,true);
            $visit_info['answer_config'] = $this->supplier_model->get_visit_answer_config();
            $url = getConfigItemByName('api_config', 'cg_system', 'webfornt', 'visit_info');
            $header = array('Content-Type: application/json');
            $html = getCurlData($url,json_encode($visit_info, JSON_UNESCAPED_UNICODE),'post',$header);//翻译结果
            $this->success_json($html);

        }









    }








    public function show_answer()
    {
        $s = [
            //与易佰合作时间
            'cooperation_time'=>'A',
            'average_monthly_storage_amount'=>'B',
            'arrival_accuracy_rate'=>'E',
            'production_affected_by_season'=>'B',
            'delivery_period'=>'A',
            'cooperation_account_period'=>'D',
            'support_dollar'=>'C',
            'support_oem_or_odm'=>'B',
            'authorized_brands_num'=>'A',
            'patented_products_num'=>'B',
            'oversea_brands_num'=>'A',
            'oem_other_companies_num'=>'B',
            'supplier_type'=>'A',
            'major_client'=>'C',
            'business_scale'=>'B',
            'is_design'=>'A',
            'factory_maintenance_status'=>'C',
            'own_factory'=>'A',
            'raw_material_situation'=>'B',
            'average_daily_production_capacity'=>'A'


            ];

        echo json_encode($s);

    }


    public function update_supplier_users(){

        try{
            $datas = $this->input->get_post('datas');
            if(empty($datas)){

                throw new Exception("请传入参数");
            }
            $datas = json_decode($datas,True);
            $result = $this->supplier_model->update_supplier_users($datas);
            if($result){
                $this->success_json();
            }

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 添加 拜访供应商通知人
     * @author:luxu
     * @time:2021年8月24号
     **/

    public function add_supplier_users(){

      try{
          $datas = $this->input->get_post('datas');
          if(empty($datas)){

              throw new Exception("请传入参数");
          }
          $datas = json_decode($datas,True);
          $result = $this->supplier_model->add_supplier_users($datas);
          if($result){
              $this->success_json();
          }

      }catch ( Exception $exp ){

          $this->error_json($exp->getMessage());
      }
    }

    /**
     * 修改拜访供应商配置
     * @author:luxu
     * @time:2021年8月27号
     **/

    public function upd_supplier_users(){

        try{
            $datas = $this->input->get_post('datas');
            if(empty($datas)){

                throw new Exception("请传入参数");
            }
            $datas = json_decode($datas,True);
            $result = $this->supplier_model->upd_supplier_users($datas);
            $this->success_json();

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());

        }
    }

    public function del_supplier_users(){

        try{

            $ids = $this->input->get_post('id');
            $flag = $this->input->get_post('name');

            $result = $this->supplier_model->del_supplier_users($ids,$flag);
            if($result){

                $this->success_json();
            }
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }



    public function show_supplier_users(){

        $result = $this->supplier_model->show_supplier_users();
        $user_list = $this->userModel->get_user_all_list();
        if(!empty($result)){

            foreach($result as $key=>&$value){
                if($value['is_show'] == 2){
                    $value['is_show_ch'] = "禁用";
                }else{
                    $value['is_show_ch'] = "正常";
                }

                if($value['template_type'] == 1){
                    $value['template_type_ch'] = "申请拜访";
                }

                if($value['template_type'] == 2){
                    $value['template_type_ch'] = "提交拜访报告";
                }
            }
        }
        $datas = [

            'list' =>!empty($result)?$result:[],
            'user_list'=>$user_list,
            'template_type' => ['1'=>'申请拜访','2'=>'提交拜访报告']
        ];
        $this->success_json($datas);
    }

}