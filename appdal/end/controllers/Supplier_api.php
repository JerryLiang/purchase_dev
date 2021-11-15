<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Supplier_api extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('supplier/supplier_model');
    }

    /**
     * 更新 供应商的SKU 数量
     * @author Jolon
     * @url http://192.168.71.170:85/supplier_api/update_sku_num
     */
    public function update_sku_num(){
        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $update_sql = "UPDATE pur_supplier,
              (SELECT supplier_code,COUNT(1) AS sku_num FROM `pur_product` WHERE 1 GROUP BY supplier_code) AS tmp
            SET pur_supplier.sku_num=tmp.sku_num
            WHERE pur_supplier.supplier_code=tmp.supplier_code";

        $query = $this->supplier_model->purchase_db->query($update_sql);
        if($query){
            $this->success_json();
        }else{
            $this->error_json('FAILED');
        }
    }


    /**
     * 获取新增供应商列表
     * @author Jolon
     * @url http://192.168.71.156/web/#/86?page_id=2470
     * @url http://192.168.71.170:85/supplier_api/plan_get_supplier_list
     */
    public function plan_get_supplier_list(){
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $_SESSION['user_name'] = '系统';// 设置默认用户，getActiveUsername会用到
        try{
            $this->supplier_model->plan_get_supplier_list();

            $this->success_json();
        }catch(Exception $e){
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 根据供应商编码更新供应商审核状态
     * @author Jolon
     * @url http://192.168.71.170:85/supplier_api/plan_update_supplier_status
     */
    public function plan_update_supplier_status(){
        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $operator_key = 'push_sup_status_to_product';
        $len = $this->rediss->llenData($operator_key);

        if($len){
            for($i = 0;$i < $len;$i ++){
                $supplier_code = $this->rediss->rpopData($operator_key);
                try{
                    $result = $this->supplier_model->plan_update_supplier_status($supplier_code);
                    if(empty($result['code'])){
                        throw new Exception($result['message']);
                    }
                }catch(Exception $e){
                    $this->rediss->lpushData($operator_key,$supplier_code);// 执行失败 下次继续执行
                }
            }

            $this->success_json();
        }else{
            $this->error_json('没有需要操作的数据');
        }
    }

    /**
     * 推送审核更新之后供应商信息到产品系统
     * @author Jolon
     */
    public function plan_push_update_supplier_info(){
        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $operator_key = 'push_sup_info_to_product';
        $len          = $this->rediss->llenData($operator_key);

        if($len){
            for($i = 0;$i < 200;$i ++){
                $supplier_code = $this->rediss->rpopData($operator_key);
                try{
                    $result = $this->supplier_model->plan_push_update_supplier_info($supplier_code);
                    if(empty($result['code'])){
                        throw new Exception($result['message']);
                    }
                }catch(Exception $e){
                    $this->rediss->lpushData($operator_key,$supplier_code);// 执行失败 下次继续执行
                }
            }

            $this->success_json();
        }else{
            $this->error_json('没有需要操作的数据');
        }
    }

    /**
     * 供应商临时解决方案  推送 供应商代码 老采购系统 -> 新采购系统
     * @author Jolon
     * @url http://192.168.71.170:85/supplier_api/receive_supplier_code
     */
    public function receive_supplier_code(){
        exit("不推老采购系统了  Jolon 2020-06-04 14:56");
        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $create_time = date('Y-m-d H:i:s');
        $url    = getConfigItemByName('api_config','old_purchase','supplier-get-change-supplier-list');

        // 获取状态变更列表
        $url_status = $url.'?type=status';
        $result     = getCurlData($url_status, [], 'get');
        $result     = json_decode($result, true);
        if($result['code'] == 200){
            $supplier_list = $result['supplier_list'];
            if($supplier_list){
                foreach($supplier_list as $supplier_code){
                    $this->rediss->lpushData('receive_supplier_code_status',$supplier_code);
                }
            }
            $status = 1;
        }else{
            $status = 0;
        }
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_receive_supplier_code_status');
        apiRequestLogInsert(
            ['record_number'    => 'receive_supplier_code',
             'record_type'      => 'receive_supplier_code_status',
             'post_content'     => $url_status,
             'response_content' => $result,
             'create_time'      => $create_time,
             'status'           => $status,
            ]);


        // 获取信息变更列表
        $old_get_result = $this->db->select('create_time')
            ->where('record_type','receive_supplier_code_change')
            ->where('status',1)
            ->order_by('id DESC')
            ->get('api_request_log')
            ->row_array();
        $start_time = isset($old_get_result['create_time']) ? $old_get_result['create_time'] : '2019-04-20 00:00:00';
        $start_time = strtotime($start_time);
        //$create_time = date('Y-m-d H:i:s', strtotime($create_time) - 2 * 86400);
        $url_status  = $url.'?type=change&start_time='.$start_time;
        $result      = getCurlData($url_status, [], 'get');
        $result      = json_decode($result, true);


        if($result['code'] == 200){
            $supplier_list = $result['supplier_list'];
            if($supplier_list){
                foreach($supplier_list as $supplier_code){
                    $this->rediss->lpushData('receive_supplier_code_change',$supplier_code);
                }
            }
            $status = 1;
        }else{
            $status = 0;
        }
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_receive_supplier_code_change');
        apiRequestLogInsert(
            ['record_number'    => 'receive_supplier_code',
             'record_type'      => 'receive_supplier_code_change',
             'post_content'     => $url_status,
             'response_content' => $result,
             'create_time'      => $create_time,
             'status'           => $status,
            ]);

        $this->success_json();
    }


    public function findShipAddress($string,$type,$pid = null){
        $this->load->model('supplier/Supplier_address_model');

        $purchase_db = $this->Supplier_address_model->purchase_db;

        $string = trim($string);
        if(empty($string)) return '';

        $remove = ['省','市','区','新区','县'];
        foreach($remove as $value){
            $string = str_replace("$value",'',$string);
        }

        if($pid){
            $ship_address_id = $purchase_db
                ->where('region_type', $type)
                ->where('pid', $pid)
                ->like('region_name', $string)
                ->get('region')
                ->row_array();
        }else{
            $ship_address_id = $purchase_db
                ->where('region_type', $type)
                ->like('region_name', $string)
                ->get('region')
                ->row_array();
        }

        return $ship_address_id?$ship_address_id['region_code']:'';
    }

    /**
     * 已废弃(2020/03/12 供应商财务结算模块大改动)
     * 供应商临时解决方案  推送 供应商代码 老采购系统 -> 新采购系统
     * 计划任务 从老系统 获取供应商信息（新增供应商）
     * @author Jolon
     * @url http://192.168.71.170:85/supplier_api/get_supplier_info_from_old_purchase
     */
    public function get_supplier_info_from_old_purchase(){
        exit("不推老采购系统了  Jolon 2020-06-04 14:56");
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $this->load->model('supplier/Supplier_model');
        $this->load->model('supplier/Supplier_payment_account_model', 'paymentAccountModel');
        $this->load->model('supplier/Supplier_contact_model', 'contactModel');
        $this->load->model('supplier/Supplier_images_model', 'imagesModel');
        $this->load->model('supplier/Supplier_audit_results_model', 'auditModel');
        $this->load->model('supplier/Supplier_product_line_model', 'productLineModel');
        $this->load->model('supplier/Supplier_buyer_model', 'buyerModel');
        $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
        $this->load->model('user/Purchase_user_model');
        $this->load->model('finance/Bank_info_model');
        $this->load->library('Upload_image');

        $operator_key = 'receive_supplier_code_change';

        $len = $this->rediss->llenData($operator_key);
        if($len){
            // 状态映射关系
            $supplier_settlement_map = [
                '2'  => '10',
                '3'  => '10',
                '10' => '27',
                '11' => '21',
                '12' => '25',
                '13' => '30',
                '14' => '18',
                '15' => '31',
                '16' => '1',
                '17' => '32',
                '18' => '31',
                '19' => '15',
                '20' => '20',
                '21' => '13',
                '22' => '26',
                '23' => '17',
                '24' => '12',
            ];

            for($i = 0;$i < 30;$i ++){
                $supplier_code = $this->rediss->rpopData($operator_key);
                if(empty($supplier_code)) break;

                $supplierInfo = $this->Supplier_model->get_supplier_info($supplier_code,false);
                if($supplierInfo) continue;// 已经存在的供应商不更新

                try{
                    $url    = getConfigItemByName('api_config','old_purchase','supplier-get-supplier-one');
                    $result = getCurlData($url,(['supplier_code' => $supplier_code]),'post');
                    $result = json_decode($result,true);

                    if($result['code'] == 404){
                        continue;
                    }
                    if($result['code'] == 200){

                        $supplier              = $result['supplier'];
                        $supplier_pay_list     = $result['supplier_pay_list'];
                        $supplier_contact_list = $result['supplier_contact_list'];
                        $supplier_img_list     = $result['supplier_img_list'];
                        $supplier_buyer_list   = $result['supplier_buyer_list'];
                        $supplier_line         = $result['supplier_line'];

                        $supplier_settlement = isset($supplier_settlement_map[intval($supplier['supplier_settlement'])])?$supplier_settlement_map[intval($supplier['supplier_settlement'])]:$supplier['supplier_settlement'];
                        $settlement_type = $this->settlementModel->get_settlement_one($supplier_settlement);
                        if($settlement_type){
                            $settlement_type = $settlement_type['parent_id'];
                        }else{
                            $settlement_type = 0;
                        }

                        if($supplier){// 更新供应商基础信息
                            //创建人 修改人新老系统不对应处理
                            $create_user_name = isset($supplier['create_user_name'])?$supplier['create_user_name']:'';
                            $create_user_id = $supplier['create_id'];
                            $update_user_name = isset($supplier['update_user_name'])?$supplier['update_user_name']:'';
                            $update_user_id = $supplier['update_id'];

                            if(isset($supplier['create_user_staff_code']) && $supplier['create_user_staff_code']){// 根据工号获取
                                $create_user_info = $this->Purchase_user_model->get_user_info_by_staff_code($supplier['create_user_staff_code']);
                                $create_user_id = isset($create_user_info['user_id']) ? $create_user_info['user_id'] : 0;
                                $create_user_name = isset($create_user_info['user_name']) ? $create_user_info['user_name'] : '';
                            }

                            if(isset($supplier['update_user_staff_code']) && $supplier['update_user_staff_code']){// 根据工号获取
                                $update_user_info = $this->Purchase_user_model->get_user_info_by_staff_code($supplier['update_user_staff_code']);
                                $update_user_id = isset($update_user_info['user_id']) ? $update_user_info['user_id'] : 0;
                                $update_user_name = isset($update_user_info['user_name']) ? $update_user_info['user_name']: '';
                            }

                            $supplier_basic = [
                                'supplier_code'          => $supplier['supplier_code'],
                                'supplier_name'          => $supplier['supplier_name'],
                                'supplier_level'         => $supplier['supplier_level'],
                                'supplier_type'          => $supplier['supplier_type'],
                                'settlement_type'        => $settlement_type,
                                'supplier_settlement'    => $supplier_settlement,
                                'payment_method'         => intval($supplier['payment_method']),
                                'cooperation_type'       => $supplier['cooperation_type'],
                                'payment_cycle'          => $supplier['payment_cycle'],
                                'transport_party'        => $supplier['transport_party'],
                                'product_handling'       => $supplier['product_handling'],
                                'commission_ratio'       => $supplier['commission_ratio'],
                                'purchase_amount'        => intval($supplier['purchase_amount']),
                                'merchandiser'           => intval($supplier['merchandiser']),
                                'main_category'          => $supplier['main_category'],
                                'business_scope'         => $supplier['business_scope'],
                                'province'               => $supplier['province'],
                                'city'                   => $supplier['city'],
                                'area'                   => $supplier['area'],
                                'complete_address'       => $supplier['supplier_address'],
                                'register_address'       => $supplier['supplier_address'],
                                'register_date'          => '',
                                'store_link'             => $supplier['store_link'],
                                'is_taxpayer'            => $supplier['is_taxpayer'],
                                'taxrate'                => $supplier['taxrate'],
                                'invoice'                => $supplier['invoice'],
                                'credit_code'            => $supplier['credit_code'],
//                                'contract_notice'        => $supplier['contract_notice'],
                                'first_cooperation_time' => $supplier['first_cooperation_time'],
                                'cooperation_time'       => $supplier['cooperation_time'],
                                'purchase_time'          => $supplier['purchase_time'],
                                'cooperation_price'      => $supplier['cooperation_price'],
                                'sku_num'                => $supplier['sku_num'],
                                'status'                 => $supplier['status'],
                                'audit_status'           => SUPPLIER_WAITING_SUPPLIER_REVIEW,
                                'search_status'          => ($supplier['status'] == 2 or $supplier['status'] == 3) ? 2 : 1,
                                'source'                 => $supplier['source'],
                                'is_cross_border'        => $supplier['supplier_special_flag'],
                                'create_user_id'         => $create_user_id,
                                'create_user_name'       => $create_user_name,
                                'create_time'            => date('Y-m-d H:i:s',$supplier['create_time']),
                                'modify_user_id'         => $update_user_id,
                                'modify_user_name'       => $update_user_name,
                                'modify_time'            => $supplier['modify_time'],
                                'shop_id'                => $supplier['shop_id'],
                                'tap_date_str'           => $supplier['tap_date_str'],
                                'quota'                  => $supplier['quota'],
                                'surplus_quota'          => $supplier['surplus_quota'],
                            ];
                            if(empty($supplierInfo)){
                                list($basic_status, $msg) = $this->Supplier_model->insert_supplier($supplier_basic);
                            }else{
                                list($basic_status, $msg) = $this->Supplier_model->update_supplier($supplier_basic, 0, true);
                            }
                            if(empty($basic_status)){
                                throw new Exception('供应商数据更新失败');
                            }

                            // 生成一条 供应商数据变更待审核记录
                            $insert_data = [
                                'supplier_code'    => $supplier_code,
                                'action'           => 'supplier/supplier/collect_update_supplier',
                                'message'          => json_encode(['change_data' => [],]),
                                'need_finance_audit' => 1,
                                'audit_status'     => SUPPLIER_WAITING_SUPPLIER_REVIEW,
                                'create_user_id'   => 1,
                                'create_user_name' => 'admin',
                                'create_time'      => date('Y-m-d H:i:s'),
                            ];
                            $result = $this->Supplier_model->purchase_db->insert('supplier_update_log',$insert_data);

                            operatorLogInsert(
                                [
                                    'id'            => $supplier_code,
                                    'type'          => 'supplier_update_log',
                                    'content'       => getSupplierOperateType(SUPPLIER_CREATE_FROM_ERP),
                                    'detail'        => $supplierInfo ? "供应商信息修改(ERP)" : "供应商创建(ERP)",
                                    'operate_type'  => SUPPLIER_CREATE_FROM_ERP,
                                    'user'          => $create_user_name
                                ]
                            );
                            $this->load->model('supplier/Supplier_audit_results_model');
                            $this->Supplier_audit_results_model->create_record($supplier_code,4);// 创建供应商审核记录 类型为新建(erp)

                        }
                        if($supplier_line){// 更新产品线
                            $supplier_product_line = [
                                'supplier_code' => $supplier_code,
                                'supplier_name' => !empty($supplier['supplier_name'])?$supplier['supplier_name']:'',
                                'first_product_line' => !empty($supplier_line['first_product_line'])?$supplier_line['first_product_line']:'',
                                'second_product_line' => !empty($supplier_line['second_product_line'])?$supplier_line['second_product_line']:'',
                                'third_product_line' => !empty($supplier_line['third_product_line'])?$supplier_line['third_product_line']:'',
                                'status' => 1
                            ];
                            $old_product_line = $this->productLineModel->get_product_line_one($supplier_code);
                            if(empty($old_product_line)){
                                list($_productLineStatus, $proLineMsg) = $this->productLineModel->insert_product_line($supplier_product_line);
                            }else{
                                list($_productLineStatus, $proLineMsg) = $this->productLineModel->update_product_line($supplier_product_line);
                            }
                            if(empty($_productLineStatus)){
                                throw new Exception('供应商产品线数据更新失败');
                            }
                        }
                        if($supplier_contact_list){
                            $this->db->delete('supplier_contact',['supplier_code' => $supplier_code]);// 删除旧的联系人（采用新增模式）
                            $update_ship_address = [];
                            foreach($supplier_contact_list as $supplier_contact){
                                $contact = [
                                    'id' => -1,
                                    'supplier_code' => $supplier_code,
                                    'contact_person' => $supplier_contact['contact_person'],
                                    'contact_number' => $supplier_contact['contact_number'],
                                    'contact_fax' => $supplier_contact['contact_fax'],
                                    'cn_address' => $supplier_contact['chinese_contact_address'],
                                    'en_address' => $supplier_contact['english_address'],
                                    'contact_zip' => $supplier_contact['contact_zip'],
                                    'qq' => $supplier_contact['qq'],
                                    'micro_letter' => $supplier_contact['micro_letter'],
                                    'want_want' => $supplier_contact['want_want'],
                                    'skype' => $supplier_contact['skype'],
                                    'sex' => $supplier_contact['sex'],
                                    'email' => $supplier_contact['email'],
                                    'corporate' => $supplier_contact['corporate'],
                                ];
                                if(empty($update_ship_address) and !empty($supplier_contact['province'])){// 更新供应商 发货地址（只读取第一个）
                                    $update_ship_address['supplier_code'] = $supplier_code;
                                    $update_ship_address['ship_province'] = $this->findShipAddress($supplier_contact['province_text'],1);
                                    $update_ship_address['ship_city']     = $this->findShipAddress($supplier_contact['city_text'],2,$update_ship_address['ship_province']);
                                    $update_ship_address['ship_area']     = $this->findShipAddress($supplier_contact['area_text'],3,$update_ship_address['ship_city']);
                                    $update_ship_address['ship_address']  = $supplier_contact['chinese_contact_address'];
                                    list($basic_status, $msg) = $this->Supplier_model->update_supplier($update_ship_address, 0, true);
                                }

                                list($_contactStatus, $contactMsg) = $this->contactModel->update_supplier_contact($contact, $supplier_code);

                                if(empty($_contactStatus)){
                                    throw new Exception('供应商联系人据添加失败');
                                }
                            }
                        }
                        if($supplier_buyer_list){// 更新采购员
                            $supplier_buyer_tmp = [];
                            foreach($supplier_buyer_list as $buyer_value){
                                if(empty($buyer_value['buyer'])) continue;
                                $buyer_key = $buyer_value['type'];
                                $buyer_info = [];

                                if(isset($buyer_value['staff_code']) and $buyer_value['staff_code']){// 根据工号获取
                                    $buyer_info = $this->Purchase_user_model->get_user_info_by_staff_code($buyer_value['staff_code']);
                                }
                                if(empty($buyer_info)){// 根据姓名获取
                                    $buyer_info = $this->Purchase_user_model->get_user_info_by_user_name($buyer_value['buyer']);
                                }
                                if(empty($buyer_info)){// 去除 姓名中的员工编号等字符
                                    $buyer = preg_replace('/[0-9A-Za-z]/','',$buyer_value['buyer']);//去除字母和数字（工号）
                                    $buyer_value['buyer'] = $buyer;
                                    $buyer_info = $this->Purchase_user_model->get_user_info_by_user_name($buyer);
                                }
                                if($buyer_info){
                                    $staff_code = isset($buyer_info['staff_code']) ? $buyer_info['staff_code'] : '';
                                    if(!preg_match('/[0-9A-Za-z]/',$buyer_value['buyer'])){//如果没有工号则带上工号
                                        $buyer_value['buyer'] .= $staff_code;
                                    }
                                    $buyer_id = $buyer_info['user_id'];
                                    $supplier_buyer_tmp[$buyer_key]['buyer_id']   = $buyer_id;
                                    $supplier_buyer_tmp[$buyer_key]['buyer_name'] = $buyer_value['buyer'];
                                    $supplier_buyer_tmp[$buyer_key]['buyer_type'] = $buyer_value['type'];
                                }else{
                                    throw new Exception('供应商采购员数据添加失败-采购员ID获取失败');
                                }
                            }
                            if($supplier_buyer_tmp){
                                list($_buyerStatus, $buyerMsg) = $this->buyerModel->update_supplier_buyer($supplier_buyer_tmp, $supplier_code);
                                if(empty($_buyerStatus)){
                                    throw new Exception('供应商采购员数据添加失败');
                                }
                            }
                        }
                        if($supplier_pay_list){
                            $this->db->delete('supplier_payment_account',['supplier_code' => $supplier_code]);// 删除旧的结算方式（采用新增模式）
                            $supplier_payment_account_tmp = [];
                            foreach($supplier_pay_list as $payment_key => $payment_value){
                                if($payment_value['payment_method'] == 1 or empty($payment_value['account_type'])){
                                    $payment_method = 1;
                                }else{
                                    if($payment_value['account_type'] == 1){
                                        $payment_method = 2;
                                    }elseif($payment_value['account_type'] == 2){
                                        $payment_method = 3;
                                    }else{
                                        $payment_method = 1;
                                    }
                                }

                                $bank_info = $this->Bank_info_model->get_one_bank_info($payment_value['payment_platform_bank']);
                                if($bank_info){
                                    $payment_platform_bank = $bank_info['master_bank_name'];
                                }else{
                                    $payment_platform_bank = $payment_value['payment_platform_bank'];
                                }


                                $pay_tmp = [
                                    'id' => -1,
                                    'supplier_code' => $supplier_code,
                                    'supplier_name' => $supplier['supplier_name'],
                                    'status' => $payment_value['status'],
                                    'payment_platform' => $payment_value['payment_platform'],
                                    'account' => $payment_value['account'],
                                    'account_name' => $payment_value['account_name'],
                                    'id_number' => $payment_value['id_number'],
                                    'payment_platform_bank' => $payment_platform_bank,
                                    'payment_platform_branch' => $payment_value['payment_platform_branch'],
                                    'phone_number' => $payment_value['phone_number'],
                                    'payment_method' => $payment_method,
                                    'account_type' => $payment_value['account_type'],
                                ];
                                $supplier_payment_account_tmp[] = $pay_tmp;
                            }
                            if($supplier_payment_account_tmp){
                                list($_settlementStatus, $settlementMsg) = $this->paymentAccountModel->update_supplier_payment($supplier_payment_account_tmp,$supplier_code);
                                if(empty($_settlementStatus)){
                                    throw new Exception('供应商财务结算数据添加失败');
                                }
                            }
                            if(isset($supplier['payment_method']) and $supplier['payment_method'] == 2){// 支付方式生成一个线上支付宝的记录
                                $this->db->query("INSERT INTO `pur_supplier_payment_account`(supplier_code,supplier_name,payment_method)
                                                SELECT supplier_code,supplier_name,1
                                                FROM pur_supplier
                                                WHERE supplier_code='{$supplier_code}'
                                                AND (SELECT COUNT(1) FROM pur_supplier_payment_account AS tmp WHERE tmp.supplier_code=supplier_code AND tmp.payment_method=1)<=0"
                                );
                            }
                        }
                        if($supplier_img_list){
                            $this->db->update('supplier_images',['image_status' => 2],['supplier_code' => $supplier_code]);// 之前的图片都改为历史图片（采用新增模式）
                            $img_map = [
                                'public_busine_licen_url'  => 'busine_licen',
                                'private_busine_licen_url' => 'busine_licen',
                                'busine_licen_url'         => 'busine_licen',
                                'receipt_entrust_book_url' => 'collection_order',
                                'verify_book_url'          => 'verify_book',
                                'bank_scan_price_url'      => 'bank_information',
                                'ticket_data_url'          => 'bank_information',
                                'card_copy_piece_url'      => 'other_proof',
                                'fuyou_record_data_url'    => 'other_proof',
                                'image_url'                => 'other_proof',
                            ];
                            $supplier_img_list_tmp = [];
                            foreach($supplier_img_list as $key => $supplier_img){
                                if($supplier_img['image_status'] == 2) continue;// 禁用的跳过
                                foreach($img_map as $old_key => $new_key){
                                    $url = isset($supplier_img[$old_key])?$supplier_img[$old_key]:'';
                                    if($url and strlen($url) > 10){
                                        if(strpos($url,';') !== false){
                                            $url = explode(';',$url);
                                        }else{
                                            $url = [$url];
                                        }
                                        foreach($url as $url_value){
                                            if(empty($url_value)) continue;
                                            $imageInfo                  = [];
                                            $imageInfo['id']            = -1;
                                            $imageInfo['supplier_code'] = $supplier_code;
                                            $imageInfo['supplier_name'] = $supplier['supplier_name'];
                                            $imageInfo['image_type']    = $new_key;
                                            $imageInfo['image_url']     = $url_value;
                                            $imageInfo['image_status']  = $supplier_img['image_status'];
                                            $imageInfo['image_id']      = $supplier_img['image_id'];
                                            $imageInfo['old_image_type'] = $old_key;
                                            $supplier_img_list_tmp[] = $imageInfo;
                                        }
                                    }
                                }
                            }

                            if($supplier_img_list_tmp){
                                foreach($supplier_img_list_tmp as $value){
                                    $url = OLD_PURCHASE.$value['image_url'];
                                    $result = $this->upload_image->push_image_to_fastdfs($url);
                                    if($result['code'] == 404){ continue;}
                                    if($result['code'] == 500){
                                        throw new Exception('供应商图片数据上传DFS失败'.$result['message'].$url);
                                    }
                                    $value['image_url'] = $result['data'];
                                    list($_imageStatus, $imageMsg) = $this->imagesModel->update_supplier_image($value,$supplier_code);
                                    if(empty($_imageStatus)){
                                        throw new Exception('供应商图片数据添加失败');
                                    }
                                }
                            }

                        }

                    }else{
                        throw new Exception('供应商数据获取失败');
                    }
                    $this->rediss->lpushData('push_sup_info_to_product',$supplier_code);//从老采购系统同步过来的供应商需要推送至新产品系统
                    apiRequestLogInsert(
                        ['record_number' => $supplier_code,
                         'record_type'   => 'get_supplier_info_from_old_purchase',
                         'post_content'  => '数据更新成功'
                        ]);
                }catch(Exception $e){
                    $this->rediss->lpushData($operator_key,$supplier_code);// 执行失败 下次继续执行
                    apiRequestLogInsert(
                        ['record_number' => $supplier_code,
                         'record_type'   => 'get_supplier_info_from_old_purchase',
                         'post_content'  => $e->getMessage(),
                         'response_content' => isset($result)?$result:'',
                         'status'        => 0,
                        ]);
                }
                unset($result);
            }

            $this->success_json();
        }else{
            $this->error_json('没有需要操作的数据');
        }
    }


    /**
     * 供应商临时解决方案  推送 供应商代码 老采购系统 -> 新采购系统
     * 计划任务 从老系统 获取供应商信息（供应商状态）
     * @author Jolon
     * @url http://192.168.71.170:85/supplier_api/get_supplier_status_from_old_purchase
     */
    public function get_supplier_status_from_old_purchase(){
        exit("不推老采购系统了  Jolon 2020-06-04 14:56");
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $this->load->model('supplier/Supplier_model');
        $operator_key = 'receive_supplier_code_status';
        $len = $this->rediss->llenData($operator_key);

        if($len){
            // 状态映射关系

            for($i = 0;$i < $len;$i ++){
                $supplier_code = $this->rediss->rpopData($operator_key);
                try{
                    $url    = getConfigItemByName('api_config','old_purchase','supplier-get-supplier-status');
                    $result = getCurlData($url,(['supplier_code' => $supplier_code]),'post');
                    $result = json_decode($result,true);

                    if($result['code'] == 404){
                        continue;
                    }
                    if($result['code'] == 200){
                        $supplierInfo = $this->Supplier_model->get_supplier_info($supplier_code,false);
                        if(empty($supplierInfo)){
                            throw new Exception('供应商状态数据获取失败-供应商未同步');
                        }

                        $update_status = [
                            'status'       =>$result['status'],
                            'audit_status' => $result['audit_status'],
                            'search_status' => ($result['status'] == 2 or $result['status'] == 3) ? 2 : 1,
                        ];

                        list($basic_status, $msg) = $this->Supplier_model->update_supplier($update_status, $supplierInfo['id']);
                        if(empty($basic_status)){
                            throw new Exception('供应商状态更新成功');
                        }
                    }else{
                        throw new Exception('供应商状态数据获取失败');
                    }

                    apiRequestLogInsert(
                        ['record_number' => $supplier_code,
                         'record_type'   => 'get_supplier_status_from_old_purchase',
                         'post_content'  => '供应商状态更新成功',
                         'response_content' => $result,
                        ]);
                }catch(Exception $e){
                    $this->rediss->lpushData($operator_key,$supplier_code);// 执行失败 下次继续执行
                    apiRequestLogInsert(
                        ['record_number' => $supplier_code,
                         'record_type'   => 'get_supplier_status_from_old_purchase',
                         'post_content'  => $e->getMessage(),
                         'status'        => 0,
                         'response_content' => isset($result)?$result:'',
                        ]);
                }
                unset($result);
            }

            $this->success_json();
        }else{
            $this->error_json('没有需要操作的数据');
        }

    }

    /**
     * 计划任务 计算供应商 本月的采购金额
     * @url http://192.168.71.170:85/supplier_api/plan_calculate_purchase_amount?date=2019-03-01
     */
    public function plan_calculate_purchase_amount(){
        set_time_limit(0);
        ini_set('memory_limit','1024M');

        $date = $this->input->get('date');

        $this->load->model('supplier/Supplier_purchase_amount');
        $this->Supplier_purchase_amount->calculate_purchase_amount($date);

        $this->success_json();
    }

    /**
     * 测试环境手动添加待推送供应商数据
     */
    public function set_supplier_code(){
        set_time_limit(0);
        $operator_key = 'push_sup_info_to_product';
        $page_key = 'push_sup_to_product_page';
        $per_page = 1000;
        $page = $this->rediss->getData($page_key);
        if(empty($page)){
            $page = 1;
            $this->rediss->setData($page_key,$page);
        }
        $start = ($page - 1) * $per_page;
        $supplier_code = $this->db->select('supplier_code')->order_by('id','desc')->limit($per_page,$start)->get('pur_supplier')->result_array();
        if(empty($supplier_code)) exit('数据为空');
        foreach ($supplier_code as $v){
            if($v['supplier_code']){
                $this->rediss->lpushData($operator_key,$v['supplier_code']);
            }
        }
        $this->rediss->setData($page_key,$page + 1);
        $len = $this->rediss->llenData($operator_key);
        var_dump($this->rediss->getData($page_key));
        var_dump($len);die;
    }


    /**
     * 测试环境手动添加需更新数据
     */
    public function set_is_complete(){
        set_time_limit(0);
        $operator_key = 'waiting_update_is_complete';
        $page_key  = 'is_complete_page';
        $per_page = 1000;

        $page = $this->rediss->getData($page_key);
        if(empty($page)){
            $page = 1;
            $this->rediss->setData($page_key,$page);
        }
        $start = ($page - 1) * $per_page;
        $supplier_code = $this->db->select('supplier_code')->where('supplier_source',1)->order_by('id','desc')->limit($per_page,$start)->get('pur_supplier')->result_array();
        if(empty($supplier_code)) exit('数据为空');
        foreach ($supplier_code as $v){
            if($v['supplier_code']){
                $this->rediss->lpushData($operator_key,$v['supplier_code']);
            }
        }
        $this->rediss->setData($page_key,$page + 1);
        $len = $this->rediss->llenData($operator_key);
        var_dump($this->rediss->getData($page_key));
        var_dump($len);die;
    }

    /**
     * 更新供应商资料是否齐全（一次性）
     */
    public function get_is_complete(){
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $operator_key = 'waiting_update_is_complete';
        $len          = $this->rediss->llenData($operator_key);

        if($len){
            for($i = 0;$i < 100;$i ++){
                $supplier_code = $this->rediss->rpopData($operator_key);
                try{
                    $result = $this->supplier_model->get_is_complete($supplier_code);
                    if(empty($result['code'])){
                        throw new Exception($result['message']);
                    }
                }catch(Exception $e){
                    $this->rediss->lpushData($operator_key,$supplier_code);// 执行失败 下次继续执行
                }
            }

            $this->success_json();
        }else{
            $this->error_json('没有需要操作的数据');
        }

    }

    /**
     *添加待更新数据
     */
    public function plan_set_store_link_supplier(){
        set_time_limit(0);
        ini_set('memory_limit','128M');
        $operate_key = 'waiting_update_store_link_code';
        $page_key = 'waiting_update_store_link_page';
        $page = $this->rediss->getData($page_key);
        $pagSize = 1000;
        if(!$page){
            $page = 1;
            $this->rediss->setData($page_key,$page);
        }
        $start = ($page - 1) * $pagSize;
        $supplier_list = $this->db->select('supplier_code')->where('store_link!=','')->limit($pagSize,$start)->order_by('id','asc')->get('pur_supplier')->result_array();

        if(empty($supplier_list)) exit('暂无需要处理的数据');

        foreach ($supplier_list as $v){
            if($v['supplier_code']) $this->rediss->lpushData($operate_key,$v['supplier_code']);
        }
        $this->rediss->setData($page_key,$page+1);
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_waiting_update_store_link_code');
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_waiting_update_store_link_page');
        var_dump($this->rediss->getData($page_key));
        var_dump($this->rediss->llenData($operate_key));

    }

    /**
     * 根据供应商链接更新店铺ID 和旺旺号 wangliang
     */
    public function update_supplier_by_store_link(){
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $operator_key = 'waiting_update_store_link_code';
        $len          = $this->rediss->llenData($operator_key);

        if($len){
            for($i = 0;$i < 200;$i ++){
                $supplier_code = $this->rediss->rpopData($operator_key);
                try{
                    $result = $this->supplier_model->update_supplier_by_store_link($supplier_code);
                    if(empty($result['code'])){
                        throw new Exception($result['message']);
                    }
                }catch(Exception $e){
                    $this->rediss->lpushData($operator_key,$supplier_code);// 执行失败 下次继续执行
                    $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_waiting_update_store_link_code');
                }
            }

            $this->success_json();
        }else{
            $this->error_json('没有需要操作的数据');
        }
    }

    /**
     * 更新供应商账期信息
     * supplier_api/update_supplier_quota_info
     */
    public function update_supplier_quota_info(){
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $this->supplier_model->update_supplier_quota_info();
    }

    /**
     * 按天生成供应商合作金额数据
     *      计算指定日期或前一天的供应商入库金额（重复执行 则更新原记录）
     * @author 王亮 Jolon
     */
    public function get_new_purchase_cooperation_amount(){
        set_time_limit(0);
        if(empty($date)) $date = $this->input->get_post('date');
        if(empty($date)){// Jolon 数据初始化上线的时候用的，现已过期，无需考虑
            $length = $this->rediss->llenData('old_purchase_cooperation_amount_days');
            if($length) $date = $this->rediss->lpopData('old_purchase_cooperation_amount_days');
        }
        if(empty($date)) $date = date('Y-m-d',strtotime("-1 day"));// 前一天的
        $this->load->model('supplier/Supplier_cooperation_amount_model');
        $res = $this->Supplier_cooperation_amount_model->make_cooperation_record(1,'',$date);
        var_dump($res);
    }

    /**
     * 获取老系统供应商合作金额
     */
    public function get_old_purchase_cooperation_amount(){
        if(empty($date)) $date = $this->input->get_post('date');
        if(empty($date)){// Jolon 数据初始化上线的时候用的，现已过期，无需考虑
            $length = $this->rediss->llenData('old_purchase_cooperation_amount_days');
            if($length) $date = $this->rediss->lpopData('old_purchase_cooperation_amount_days');
        }
        if(empty($date)) $date = date('Y-m-d',strtotime("-1 day"));// 前一天的
        $url    = getConfigItemByName('api_config','old_purchase','supplier-get-cooperation-amount');
        $url    = $url.'?date='.$date;
        $result = getCurlData($url,'','get');
        $result = json_decode($result,true);
        $return = [];
        if($result['code'] == 200){
            if(isset($result['list']) && !empty($result['list'])){
                foreach ($result['list'] as $supplier_code => $amount){
                    $return[] = [
                        'supplier_code'     => $supplier_code,
                        'old_purchase'      => $amount,
                    ];
                }
            }
            $this->load->model('supplier/Supplier_cooperation_amount_model');
            $res = $this->Supplier_cooperation_amount_model->make_cooperation_record(2,$return,$date);
            if($res['code'] == 0){
                $this->rediss->rpush('old_purchase_cooperation_amount_days',$date);
            }

        }else{
            var_dump($result);
        }

        apiRequestLogInsert(
            [
                'api_url'       => $url,
                'record_type'   => 'get_old_purchase_cooperation_amount',
                'post_content'  => '获取供应商合作金额',
                'response_content' => $result,
            ]);
        var_dump($result);
    }


    /**
     * 初始化90天供应商合作金额数据
     * @author Jolon 数据初始化上线的时候用的，现已过期，无需考虑
     */
    public function get_ninety_days(){
        $days = [];
        $end = strtotime(date('Y-m-d',strtotime("+1 day")));
        $begin = strtotime("-90 days");
        while (($begin+=86400) < $end){
            $date = date('Y-m-d',$begin);
            $days[] = $date;
            $this->rediss->lpushData('old_purchase_cooperation_amount_days',$date);
            $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_old_purchase_cooperation_amount_days');
        }
        var_dump($this->rediss->llenData('old_purchase_cooperation_amount_days'));
        pr($days);
    }

    /**
     * 计划任务 生成供应商产品线数据（每次处理100条数据）
     * @author wangliang
     * @url http://192.168.71.170:85/supplier_api/plan_generate_product_line
     */
    public function plan_generate_product_line(){
        set_time_limit(0);
        $this->load->model('product/Supplier_product_line_model');
		$lock_key = 'supplier_product_line_lock';
		$lock = $this->rediss->getData($lock_key);
		if($lock){
			 $return = ['code'=>1,'msg'=> '数据正在处理中...请稍后重试','data'=>[]];
			 exit(json_encode($return));
		}
		
		$this->rediss->setData($lock_key,1);
        $supplier_codes = $this->db->select('DISTINCT(s.supplier_code) as supplier_code')->from('pur_supplier as s')
                        ->join('pur_supplier_product_line p','s.supplier_code = p.supplier_code','left')
                        ->join('pur_product pr','s.supplier_code = pr.supplier_code','left')
                        ->where('p.id is NULL')
                        ->where('pr.id is not NULL')
                        ->where('pr.product_line_id > ',0)
                        ->where('s.supplier_code is not NULL')
                        ->where('s.supplier_code !=','')
                        ->order_by('s.id','desc')
                        ->limit(100)
                        ->get()
                        ->result_array();

        if(empty($supplier_codes)){
            $return = ['code'=>1,'msg'=> '暂无需要处理的数据','data'=>[]];
        }else{
            $supplier_codes = array_unique(array_column($supplier_codes,'supplier_code'));
            $data = [];
            foreach ($supplier_codes as $supplier_code){
                $res = $this->Supplier_product_line_model->generate_product_line($supplier_code);
                $data[$supplier_code] = $res;
            }
            $return = ['code' => 1,'msg'=>'处理成功','data'=>$data];
        }
		$this->rediss->deleteData($lock_key);
        exit(json_encode($return));
    }
	
	/**
 * 刷新供应商是否支持跨境宝
 *
 * @return void
 */
    public function refresh_kjb(){
        $this->load->model('product/Supplier_model');
        $suppliers = $this->Supplier_model->purchase_db->select('id,supplier_code,store_link,status')->from('supplier')->where_in('status',[1,4,5])->limit(10)->get()->result_array();
        if(!empty($suppliers)){
            try{
        foreach($suppliers as $v){
        // $shop_url = 'https://longsudianzi.1688.com/?spm=a261y.7663282.autotrace-topNav.1.4def6f22tQqQIy';
        // $shop_url = 'https://detail.1688.com/offer/571283468066.html?spm=a262jy.sw15.mof001.43.314424fejtx6aw';
        if(isset($v['store_link'])){
        $result = $this->Supplier_model->get_shop_id($v['store_link']);
        if(isset($result['data']['kuaJingBao'])){
            if($result['data']['kuaJingBao']){
                $is_cross_border = 1;
            }elseif($result['data']['kuaJingBao'] == false){
                $is_cross_border = 0;
            }
        $ret = $this->Supplier_model->purchase_db->update('supplier',['is_cross_border'=>$is_cross_border],['id'=>$v['id']]);
        }
    }
        //update('purchase_label_info', $updateData,['id' => $label_info['id']]);
    }
    }catch(Exception $e){
        echo $e->getMessage();
    }
}
    echo 'success';
}

    /**
     * 供应商所绑定银行卡信息到结汇系统
     * @url /supplier_api/push_payment_info_to_exchange
     * @author Justin
     */
    public function push_payment_info_to_exchange()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $this->load->model('supplier/Supplier_payment_info_model', 'paymentInfoModel');

        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 OR $limit > 200) {
            $limit = 200; //每次推送200条
        }

        try {
            //获取推送数据
            $data_list = $this->paymentInfoModel->get_push_to_exchange_data($limit);
            if (empty($data_list)) {
                exit('暂无符合条件的数据');
            }
            //请求Java接口，推送数据
            $post_data = array();
            foreach ($data_list as $item) {
                if (empty($item['account_type']) OR empty($item['account_name']) OR empty($item['city_code'])
                    OR empty($item['bank_code']) OR empty($item['id_number']) OR empty($item['account'])
                    OR empty($item['create_time']) OR is_null($item['is_del']) OR empty($item['supplier_code'])) {
                    echo '数据不完整:' . $item['supplier_code'] . '_' . $item['account'] . '<br>';
                    //写入操作日志表
                    operatorLogInsert(
                        array(
                            'id' => $item['supplier_code'],
                            'type' => 'PUR_SUPPLIER_PAYMENT_INFO_PUSH_EXC',
                            'content' => '推送数据不完整',
                            'detail' => json_encode($item,320),
                            'user' => '计划任务',
                        )
                    );
                    continue;
                }
                $post_data[] = array(
                    'accountPurpose' => 2,                                                            //账号用途 1.转出账户 2.转入账户(固定)
                    'accountType' => (int)$item['account_type'],                                      //账户类型
                    'accountName' => $item['account_name'],                                           //账户户名
                    'country' => '中国',                                                                //付/收款人常驻国家（固定）
                    'countryCode' => 'CHN',                                                           //国家代码（固定）
                    'city' => $item['city_name'],                                                     //城市名称
                    'cityCode' => $item['city_code'],                                                 //城市代码
                    'bankName' => $item['branch_bank_name'],                                          //开户银行名称
                    'bankCode' => $item['bank_code'],                                                 //开户银行CODE
                    'accountId' => $item['id_number'],                                                //用户账户证件号
                    'bankAccount' => $item['account'],                                                //开户银行账号
                    'createTime' => $item['create_time'],                                             //创建时间
                    'isDel' => (int)$item['is_del'],                                                  //是否删除（0-未删除，1-已删除）
                    'createUser' => '',                                                               //创建人
                    'currency' => !empty($item['currency']) ? $item['currency'] : 'CNY',              //币种
                    'supplierCode' => $item['supplier_code'],                                         //供应商编码
                );
            }
            //请求URL
            $request_url = getConfigItemByName('api_config', 'java_system_exchange', 'purPushSupplierBank');
            if (empty($request_url)) exit('请求URL不存在');
            $access_token = getOASystemAccessToken();
            if (empty($access_token)) exit('获取access_token值失败');
            $request_url = $request_url . '?access_token=' . $access_token;
            $header = ['Content-Type: application/json'];
            $res = getCurlData($request_url, json_encode($post_data), 'POST', $header);
            $_result = json_decode($res, true);
            if (isset($_result['code']) && $_result['code'] == 200) {
                //推送成功后，更新推送状态
                $success_list = isset($_result['data']['successList']) && is_array($_result['data']['successList']) ? $_result['data']['successList'] : [];
                if (!empty($success_list)) {
                    foreach ($success_list as $item) {
                        $update_res = $this->paymentInfoModel->update_push_exc_time($item['bankAccount'], $item['supplierCode']);
                        if (empty($update_res)) {
                            throw new Exception($item['supplierCode'] . '_' . $item['bankAccount'] . ":推送时间更新失败");
                        } else {
                            echo '推送成功:' . $item['supplierCode'] . '_' . $item['bankAccount'] . '<br>';
                        }
                    }
                }
                //推送失败的处理
                $fail_list = isset($_result['data']['failureList']) && is_array($_result['data']['failureList']) ? $_result['data']['failureList'] : [];
                if (!empty($fail_list)) {
                    foreach ($fail_list as $item) {
                        echo '推送失败:' . $item['supplierCode'] . '_' . $item['bankAccount'] . ' [' . $item['msg'] . ']<br>';
                    }
                    //写入操作日志表
                    operatorLogInsert(
                        array(
                            'type' => 'PUR_SUPPLIER_PAYMENT_INFO_PUSH_EXC',
                            'content' => '银行卡信息推送失败',
                            'detail' => $fail_list,
                            'user' => '计划任务',
                        )
                    );
                }
            } else {
                $msg = isset($res['msg']) ? $res['msg'] : '请求推送接口异常';
                echo $msg . '<br>';
                echo $res;
            }
        } catch (Exception $exc) {
            //写入操作日志表
            operatorLogInsert(
                array(
                    'type' => 'PUR_SUPPLIER_PAYMENT_INFO_PUSH_EXC',
                    'content' => '推送异常',
                    'detail' => $exc->getMessage(),
                    'user' => '计划任务',
                )
            );
            exit($exc->getMessage());
        }
    }

    /**
     * 同步1688是否支持跨境宝(针对是否支持跨境宝=否的供应商)
     * @url /supplier_api/sync_supplier_cross_border
     * @author Justin
     */
    public function sync_supplier_cross_border()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        try {
            $len = $this->rediss->llenData('SUPPLIER_CROSS_BORDER');
            if (!$len) {
                //获取数据存入Redis
                $len = $this->supplier_model->get_supplier_store_link();
                if (empty($len)) {
                    exit('暂无符合条件的数据');
                }
            }

            //循环处理Redis数据
            while (1) {
                $supplier_data = $this->rediss->rpopData('SUPPLIER_CROSS_BORDER');
                if (!empty($supplier_data) && is_array($supplier_data)) {
                    foreach ($supplier_data as $item) {
                        if (empty($item['store_link'])) {
                            continue;
                        }
                        //根据‘供应商链接’获取供应商‘是否支持跨境宝’
                        $_result = $this->supplier_model->get_shop_id(trim($item['store_link']));

                        //查询结果失败，或1688返回‘是否支持跨境宝’为否(说明与原始数据一样)，跳过当前数据不处理
                        if (!$_result['code'] OR !$_result['data']['kuaJingBao']) {
                            continue;
                        }
                        //根据‘供应商数据id’更新‘是否支持跨境宝’字段
                        list($_status, $_message) = $this->supplier_model->update_supplier(['is_cross_border' => 1], $item['id']);
                        if ($_status) {
                            echo '更新成功:' . $item['supplier_code'] . '<br>';
                        } else {
                            echo '更新失败:' . $item['supplier_code'] . '<br>';
                            //写入操作日志表
                            operatorLogInsert(
                                array(
                                    'id' => $item['supplier_code'],
                                    'type' => 'sync_supplier_cross_border',
                                    'content' => '同步1688是否支持跨境宝更新失败' . $_message,
                                    'detail' => json_encode($_result, 320),
                                    'user' => '计划任务',
                                )
                            );
                        }
                    }
                }
                //判断数据是否处理完成
                $len = $this->rediss->llenData('SUPPLIER_CROSS_BORDER');
                if (!$len) {
                    break;
                }
            }
        } catch (Exception $exc) {
            //写入操作日志表
            operatorLogInsert(
                array(
                    'type' => 'sync_supplier_cross_border',
                    'content' => '同步1688是否支持跨境宝异常',
                    'detail' => $exc->getMessage(),
                    'user' => '计划任务',
                )
            );
            exit($exc->getMessage());
        }
    }

    /**
     * 产品系统推送备选供应商到采购系统
     * 需求号：33099 产品列表新增"备选供应商列表" #2
     * 产品系统,同新SKU一起推送过来,数据来源=开发添加
     * @author:luxu
     * @time:2021年4月22号
     *
     **/
    public function product_push_alternative_suppliers(){
        $this->load->model('product/Alternative_suppliers_model');
        try{
            $this->_ci = get_instance();
            $this->_ci->load->config('rabbitmq');
            $config['rabbitmq_host'] = $this->_ci->config->item('product_rabbitmq_host');
            $config['rabbitmq_port'] = $this->_ci->config->item('product_rabbitmq_port');

            $config['rabbitmq_vhost']   = $this->_ci->config->item('product_rabbitmq_vhost');

            $config['rabbitmq_user']    =  $this->_ci->config->item('product_rabbitmq_user');

            $config['rabbitmq_password'] =  $this->_ci->config->item('product_rabbitmq_password');
            $this->load->library('Rabbitmq',$config);
//            //创建消息队列对象
            $mq = new Rabbitmq($config);

            $mq->setExchangeName('sync_spare_supplier');  //STOCKLIST
            $mq->setRouteKey('sync_spare_supplier');
            $mq->setQueueName('sync_spare_supplier'); //  DEMAND_STOCKLIST_DATA
            $mq->setType(AMQP_EX_TYPE_DIRECT);
            //构造存入数据
            //存入消息队列
            $queue_obj = $mq->getQueue();
            //处理生产者发送过来的数据
            $envelope = $queue_obj->get();
            if ($envelope) {
                $data = $envelope->getBody();
                $datas = json_decode($data,true);
                if(!empty($datas)){
                    foreach($datas as $key=>$value){
                        $productData = [
                            'sku' => $value['sku'],
                            'new_supplier_name' =>$value['supplier_name'],
                            'new_supplier_code' => $value['supplier_code'],
                            'new_supplier_price' =>$value['purchase_price'],
                            'new_starting_qty' => $value['starting_qty_unit'],
                            'new_devliy' => $value['delivery'],
                            'new_product_link' => !empty($value['url'])?$value['url']:'',
                            'source_from' =>2,
                            'audit_status' =>1,
                        ];
                        $this->Alternative_suppliers_model->product_audit_alternative($productData,"product");
                    }
                }
                $queue_obj->ack($envelope->getDeliveryTag());
                $mq->disconnect();
            }

            $isshowcount = $this->db->from("alternative_suppliers")->where("is_show",2)->group_by("sku")->count_all_results();
            if($isshowcount>0){

                $limit = 200;
                $page = ceil($isshowcount/$limit);
                for($i=1;$i<=$page;++$i){

                    $sql = " SELECT sku FROM pur_alternative_suppliers WHERE is_show=2 GROUP BY sku ORDER BY id  DESC LIMIT ".($i-1)*$limit.",".$limit;
                    $skuMessage = $this->db->query($sql)->result_array();
                    if(!empty($skuMessage)){

                        $skuDatas = array_column($skuMessage,"sku");
                        $productExists = $this->db->from("product")->where_in("sku",$skuDatas)->select("sku")->get()->result_array();
                        if(!empty($productExists)){
                            $productExists = array_column($productExists,"sku");
                            $skuExistsdatas = $this->db->from("alternative_suppliers")->where_in("sku",$productExists)->get()->result_array();
                            foreach($skuExistsdatas as $sku_key=>$sku_value){
                                $productData = [
                                    'sku' => $sku_value['sku'],
                                    'new_supplier_name' =>$sku_value['supplier_name'],
                                    'new_supplier_code' => $sku_value['supplier_code'],
                                    'new_supplier_price' =>$sku_value['purchase_price'],
                                    'new_starting_qty' => $sku_value['starting_qty_unit'],
                                    'new_devliy' => $sku_value['delivery'],
                                    'new_product_link' => !empty($sku_value['url'])?$sku_value['url']:'',
                                    'source_from' =>2,
                                    'audit_status' =>1,
                                ];
                                $this->Alternative_suppliers_model->product_audit_alternative($productData);
                            }
                        }
                    }
                }
            }
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }

    }

    public function del_supplier_relation_log()
    {

        $result  = $this->db->select('*')
            ->where_in('record_number',['V21020100003','V21020100004','V20010400001','V21030500001','V21022500010','QS021136'])
            ->where_in('operate_time',['2021-03-16 18:30:04','2021-03-16 18:30:04','2021-03-10 16:15:03','2021-03-11 10:15:52','2021-03-05 09:16:51'])
            ->where('record_type','supplier_update_log')
            ->get('operator_log')
            ->result_array();


        if (!empty($result)) {
            foreach ($result as $result_info) {
                $message = json_decode($result_info['content_detail'],true);
                if (isset($message['change_data_log']['supplier_relation'])) {
                    unset($message['change_data_log']['supplier_relation']);
                    $message_now = json_encode($message);

                    $this->db->where('id',$result_info['id'])->update('operator_log',['content_detail' => $message_now]);


                }



            }

        }

        echo 'done';


    }

    /**
     *39087 拜访供应商审核优化
    拜访供应商后（申请拜访的结束时间），10天若没上传“提交拜访报告”不能锁定不能提交报告。
     * 通过系统消息每天9:00发送消息提醒一次申请人、参与人、审批人，直至“提交拜访报告”上传完成后不再提醒，
     * 消息内容为：“***申请拜访供应商：XXXXX，拜访结束已超过10天，请尽快提交拜访报告！”。***表示具体申请人、XXXXX表示具体供应商名称
     * @param
     * @author:luxu
     **/
    public function supplier_report_push_message(){

        $nowData = date("Y-m-d H:i:s",time());
        $reportDatas = $this->db->from("supplier_visit_apply")->where("visit_status",6)->where("end_time>",$nowData)
            ->get()->result_array();
        // $reportDatas = $this->db->from("supplier_visit_apply")->get()->result_array();

        if(!empty($reportDatas)) {
            $agent_id = 193670347;

            foreach ($reportDatas as $key => $value) {

                $supplierNames = $this->db->from("supplier")->where("supplier_code", $value['supplier_code'])
                    ->select("supplier_name")->get()->row_array();
                /*$pushIds[] = $value['apply_id']; // 申请人ID
                $pushIds[] = $value['participants']*/
                $msg = $value['apply_name'] . "申请拜访供应商:" . $supplierNames['supplier_name'] . "，拜访结束已超过10天，请尽快提交拜访报告！";
                $pushIds[] = getUserNumberById($value['apply_id']);
                $participantsDatas = explode(",", $value['participants']);
                foreach ($participantsDatas as $participantsData_value) {

                    $pushIds[] = getUserNumberById($participantsData_value);
                }
                $pushIds[] = getUserNumberById($value['audit_id_manage']);
                $userNumbers = implode(",", $pushIds);
                $url = "http://dingtalk.yibainetwork.com/personalnews/Personal_news/personalNews?agent_id=" . $agent_id . "&userNumber={$userNumbers}&msg=" . $msg;
                echo $url."\r\n";
                getCurlData($url, '', 'GET');
            }
        }
    }

}
