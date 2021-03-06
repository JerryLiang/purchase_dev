<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 
 */

class Payment_order_pay extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('payment_order_pay_model');
        $this->load->model('payment_order_pay_new_model');
        $this->load->helper('status_finance');
        $this->load->helper('status_order');
        $this->load->helper('status_supplier');
        $this->load->helper('export_csv');
        $this->load->helper('user');
        $this->load->model('finance/Payment_order_contract_pay_model');
    }

    /**
     * 应付款搜索列
     * @author harvin 
     * /finance/payment_order_pay/list_search
     * */
    public function list_search() {
        $data_list['applicant'] = get_buyer_name(); //申请人
        $data_list['pay_status'] = payment_status(); //付款状态
        $data_list['purchase_type_id'] = getPurchaseType(); //业务线
        $data_list['approver'] = get_buyer_name(); //审批人
        $data_list['pay_type'] = getPayType(); //支付方式
        $data_list['settlement_method'] = $this->payment_order_pay_model->get_settlement_method(); //结算方式
        $data_list['is_cross_border'] = getCrossBorder(); //跨境宝供应商
        $this->success_json($data_list);
    }


    /**
     * 刷新1688最新的应付账款时间，并更新状态
     * @author yefanli  2020-07-07
     * /finance/payment_order_pay/refresh_ali_payable
     *
     * @param array pai_number      拍单号列表
     * @return mixed
     */
    public function refresh_ali_payable()
    {
        $pai_number = $this->input->get_post('pai_number');

        if(gettype($pai_number) != "array" || count($pai_number) == 0){
            $this->error_json("请勿非法提交数据");
            return;
        }

        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('finance/Purchase_order_pay_type_model');

        $succList = [];
        foreach($pai_number as $key=>$val){
            $result = $this->aliorderapi->getListOrderDetail(null, $val);
            if(!$result){
                $succList[$val] = "未获得数据!";
                continue;
            }

            if(!isset($result[$val]["code"]) || $result[$val]["code"] != 200){
                $succList[$val] = "无数据!";
                continue;
            }
            $time = false;
            if(isset($result[$val]["data"]["orderBizInfo"]["creditOrderDetail"]["gracePeriodEndTime"]) &&
                !empty($result[$val]["data"]["orderBizInfo"]["creditOrderDetail"]["gracePeriodEndTime"])){
                $time = $result[$val]["data"]["orderBizInfo"]["creditOrderDetail"]["gracePeriodEndTime"];
            }
            if(isset($result[$val]["data"]["orderBizInfo"]["accountPeriodTime"]) && !empty($result[$val]["data"]["orderBizInfo"]["accountPeriodTime"])){
                $time = $result[$val]["data"]["orderBizInfo"]["accountPeriodTime"];
            }

            if($time){
                $this->Purchase_order_pay_type_model->update_ali_accout_period_time(null,$val,$time);
                $succList[$val] = "成功";
            }else{
                $succList[$val] = "无此时间，未更新";
            }
        }

        $this->success_json($succList, "", "请求成功");
    }

    /**
     * 获取请求的参数
     * @return array
     */
    public function get_list_params()
    {
        $params = [];
        $params['supplier_code']      = $this->input->get_post('supplier_code'); //供应商编码
        $params['applicant']          = $this->input->get_post('applicant'); //申请人
        $params['pay_status']         = $this->input->get_post('pay_status'); //付款状态
        $params['purchase_type_id']   = $this->input->get_post('purchase_type_id'); //业务线
        $params['pur_number']         = $this->input->get_post('pur_number'); //采购单号或合同号
        $params['requisition_number'] = $this->input->get_post('requisition_number'); //请款单号
//        $params['approver'] = $this->input->get_post('approver'); //财务审批人
        $params['pay_type']              = $this->input->get_post('pay_type'); //支付方式
        $params['settlement_method']     = $this->input->get_post('settlement_method'); //结算方式
        $params['is_cross_border']       = $this->input->get_post('is_cross_border'); //跨境宝供应商
        $params['pay_category']          = $this->input->get_post('pay_category'); //请款类型
        $params['create_time_start']     = $this->input->get_post('create_time_start'); // 创建时间-开始
        $params['create_time_end']       = $this->input->get_post('create_time_end'); // 创建时间-结束
        $params['id']                    = $this->input->get_post('id'); // 勾选参数
        $params['type']                  = $this->input->get_post('type'); //1为合同 2 网络 3账期
        $params['pay_notice']            = $this->input->get_post('pay_notice_status'); // 付款提醒状态
        $params['need_pay_time_start']   = $this->input->get_post('need_pay_time_start'); // 应付款开始查询时间
        $params['need_pay_time_end']     = $this->input->get_post('need_pay_time_end'); // 应付款结束查询时间
        $params['pay_time_start']        = $this->input->get_post('pay_time_start'); // 实际付款开始查询时间
        $params['pay_time_end']          = $this->input->get_post('pay_time_end'); // 实际付款结束查询时间
        $params['purchase_account']      = $this->input->get_post('purchase_account'); // 账号查询
        $params['pay_user_id']           = $this->input->get_post('pay_user_id'); // 付款人
        $params['statement_number']      = $this->input->get_post('statement_number'); // 对账单号
        $params['payment_platform']      = $this->input->get_post('payment_platform'); // 支付平台
        $params['approver_type']         = $this->input->get_post('approver_type'); //审批类型
        $params['approver_user']         = $this->input->get_post('approver_user'); //审批user_id
        $params['is_drawback']           = $this->input->get_post('is_drawback'); //是否退税
        $params['is_ali_order']          = $this->input->get_post('is_ali_order'); // 是否1688下单
        $params['purchase_account_type'] = $this->input->get_post('purchase_account_type'); // 账号类型
        $params['purchase_order_status'] = $this->input->get_post('purchase_order_status'); // 采购单状态
        $params['pur_tran_num']          = $this->input->get_post('pur_tran_num'); //商户订单号
        $params['trans_orderid']         = $this->input->get_post('trans_orderid'); //平台交易流水号
        $params['pai_number']            = $this->input->get_post('pai_number');            // 拍单号
        $params['account_pay']           = $this->input->get_post('account_pay'); //账号付款人
        $params['is_statement_pay']      = $this->input->get_post('is_statement_pay'); //是否对账单请款
        $params['group_ids']             = $this->input->get_post('group_ids'); //采购组别
        $params['account_number']             = $this->input->get_post('account_number'); //采购组别
        return $params;
    }


    /**
     * 应付款单列表页
     * @author harvin 2019-1-16
     * /finance/payment_order_pay/get_list
     */
    public function get_list() {
        $params = $this->get_list_params();
        $type = $params['type']; //1为合同 2 网络 3账期
        if (empty($type) || !in_array($type, [SOURCE_COMPACT_ORDER, SOURCE_NETWORK_ORDER, SOURCE_PERIOD_ORDER])) {
            $this->error_json('请求参数错误');
        }
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0)
            $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->payment_order_pay_model->new_get_pay_list($params, $type, $offsets, $limit,'',$page, 'list');

        $this->success_json($data_list);
      }
      
     /**
     * 财务导出
     * @author harvin 2019-5-10
     * /finance/payment_order_pay/export_list
     */

    public function export_list_bak(){
        set_time_limit(0);
        ini_set('memory_limit', '3000M');
        $params     = gp();
        if (empty($params['type']) || !in_array($params['type'], [SOURCE_COMPACT_ORDER, SOURCE_NETWORK_ORDER, SOURCE_PERIOD_ORDER])) {
            $this->error_json('请求参数错误');
        }
        $type=$params['type'];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0)
            $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->payment_order_pay_model->get_pay_list_sum($params, $type);
        $total = $data_list['paging_data']['total'];
        $this->success_json($data_list);
        $template_file = 'order_pay_'.date('YmdHis').mt_rand(1000,9999).'.csv';
        if($total>100000){//一次最多导出10W条
            $template_file = 'order_pay.csv';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$template_file;
            $this->success_json($down_file_url);
        }
        $freight = [];
        $discount = [];
        //前端路径
        $webfront_path = dirname(dirname(APPPATH));
        $product_file = $webfront_path.'/webfront/download_csv/'.$template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file,'w');
        $is_head = false;
        $fp = fopen($product_file, "a");
        if($total > 0){
            $per_page = 200;
            $total_page = ceil($total/$per_page);
            for($i = 1;$i<= $total_page;$i++){
                $offset = ($i - 1) * $per_page;
                $info =$this->payment_order_pay_model->export_pay_list($params, $type, $offset, $per_page, '', $page);//导出文件
                if(!empty($info['values'])){
                    //组装需求数据格式
                    $data= $this->payment_order_pay_model->get_assembly_data($info['values']);
                    foreach ($data as $key =>$value) {
                        if(isset($freight[$value['purchase_number']]['freight'])) $value['freight'] = 0;//一个采购单 多个PO 运费只在第一个sku上显示
                        if($value['freight'] > 0) $freight[$value['purchase_number']]['freight'] = $value['freight'];

                        if(isset($freight[$value['purchase_number']]['discount'])) $value['discount'] = 0;//一个采购单 多个PO 优惠额只在第一个sku上显示
                        if($value['discount'] > 0) $freight[$value['purchase_number']]['discount'] = $value['discount'];

                        if(isset($freight[$value['purchase_number']]['process_cost'])) $value['process_cost'] = 0;//一个采购单 多个PO 加工费只在第一个sku上显示
                        if($value['process_cost'] > 0) $freight[$value['purchase_number']]['process_cost'] = $value['process_cost'];

                        $row=[
                            $value['compact_number'],$value['requisition_number'],$value['purchase_number'],$value['purchase_acccount'],$value['pai_number']."\t",
                            $value['warehouse_code'],$value['buyer_name'],$value['create_time'],$value['pay_time'],$value['product_line_id'],$value['sku'],$value['product_name'],$value['purchase_unit_price'],
                            $value['purchase_amount'],$value['arrival_qty'],$value['instock_qty'],$value['loss_amount'],$value['loss_status'],$value['actual_amount'],$value['defective_num'],
                            $value['product_money'],$value['freight'],$value['discount'],$value['process_cost'],$value['pay_price'],$value['supplier_code'],$value['supplier_name'],$value['check_status'],$value['payment_notice'],
                            $value['pay_status'], $value['purchase_order_status_name'],$value['pay_type'],$value['payment_platform'],$value['account_type'],$value['statement_number'],
                            $value['is_ali_order'],$value['applicant'],$value['payer_name'],$value['pur_tran_num'],$value['trans_orderid'],$value['is_statement_pay']
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
                            $heads=[
                                '合同号','请款单号','采购单号','网拍账号','拍单号','采购仓库','采购员','申请日期','付款日期','一级产品线','sku',
                                '产品名称','采购单价','采购数量','到货数量','入库数量','报损数量','报损状态','实际入库数量','次品数量','商品额',
                                '运费','优惠额','加工费','请款金额','供应商编码','供应商名称','验货状态','备注','付款状态','采购单状态',
                                '支付方式','支付平台','结算方式','对账单号','是否1688下单','请款人','付款人','付款回单编号','付款流水号','是否对账单请款'
                            ];
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
                    ob_flush();
                    flush();
                    usleep(100);
                }
            }
        }
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url= $down_host.'download_csv/'.$template_file;
        $this->success_json($down_file_url);
    }


    /**
     * 财务导出  新
     * @author  yefanli
     * @time    2020/09/01
     * /finance/payment_order_pay/export_list_new
     */
    public function export_list()
    {
//        set_time_limit(0);
//        ini_set('memory_limit', '3000M');
        $params = $this->get_list_params();
        if (empty($params['type']) || !in_array($params['type'], [SOURCE_COMPACT_ORDER, SOURCE_NETWORK_ORDER, SOURCE_PERIOD_ORDER])) {
            $this->error_json('请求参数错误1');
        }


        $type = $params['type'];
        $type_str = $type == 1? "合同": "网采";

        $page = 1;
        $limit = 1;
        if (empty($page) or $page < 0)$page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data_list = $this->payment_order_pay_model->new_get_pay_list($params, $type, $offsets, 1, '', $page, 'export_sum');

        if (true) {
            $this->load->model('system/Data_control_config_model');
            $total = $data_list ??0;
            $ext = 'csv';
            try {
                $result = $this->Data_control_config_model->insertDownData($params, 'FINANCE_LIST', '财务管理-'.$type_str, getActiveUserName(), $ext, $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json([], '',"已添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }
            exit;
        }

        $template_file = 'order_pay_' . date('YmdHis') . mt_rand(1000, 9999) . '.csv';
        if (count($data_list) > 100000) {//一次最多导出10W条
            $template_file = 'order_pay.csv';
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url = $down_host . 'download_csv/' . $template_file;
            $this->success_json($down_file_url);
        }
        $freight = [];
        $discount = [];
        //前端路径
        $webfront_path = dirname(dirname(APPPATH));
        $product_file = $webfront_path . '/webfront/download_csv/' . $template_file;
        if (file_exists($product_file)) {
            unlink($product_file);
        }
        fopen($product_file, 'w');
        $is_head = false;
        $fp = fopen($product_file, "a");
        if (count($data_list) > 0) {
            $this->load->model("purchase/Purchase_order_model");
            $per_page = 500; // 每次处理500个采购单
            $p_list = array_column($data_list, 'purchase_number');
            $pur_list = [];
            foreach ($p_list as $k){
                if(!empty($k) && !in_array($k, $pur_list))$pur_list[] = $k;
            }
            $pur_list_k = count($pur_list) > $per_page ? array_chunk($pur_list, $per_page) : [$pur_list];
//            $this->success_json($pur_list_k);
            foreach ($pur_list_k as $val) {
                $data= $this->payment_order_pay_model->get_assembly_data_new($val, $params['type'], $data_list);
                foreach ($data as $key =>$value) {
                    if(isset($freight[$value['purchase_number']]['freight'])) $value['freight'] = 0;//一个采购单 多个PO 运费只在第一个sku上显示
                    if($value['freight'] > 0) $freight[$value['purchase_number']]['freight'] = $value['freight'];

                    if(isset($freight[$value['purchase_number']]['discount'])) $value['discount'] = 0;//一个采购单 多个PO 优惠额只在第一个sku上显示
                    if($value['discount'] > 0) $freight[$value['purchase_number']]['discount'] = $value['discount'];

                    if(isset($freight[$value['purchase_number']]['process_cost'])) $value['process_cost'] = 0;//一个采购单 多个PO 加工费只在第一个sku上显示
                    if($value['process_cost'] > 0) $freight[$value['purchase_number']]['process_cost'] = $value['process_cost'];
                    $row=[
                        $value['compact_number'],$value['requisition_number'],$value['purchase_number'],$value['purchase_acccount'],$value['pai_number']."\t",
                        $value['warehouse_code'],$value['buyer_name'],$value['create_time'],$value['pay_time'],$value['product_line_id'],$value['sku'],$value['product_name'],$value['purchase_unit_price'],
                        $value['purchase_amount'],$value['arrival_qty'],$value['instock_qty'],$value['loss_amount'],$value['loss_status'],$value['actual_amount'],$value['defective_num'],
                        $value['product_money'],$value['freight'],$value['discount'],$value['process_cost'],$value['pay_price'],$value['supplier_code'],$value['supplier_name'],$value['check_status'],$value['payment_notice'],
                        $value['pay_status'], $value['purchase_order_status_name'],$value['pay_type'],$value['payment_platform'],$value['account_type'],$value['statement_number'],
                        $value['is_ali_order'],$value['applicant'],$value['payer_name'],$value['pur_tran_num'],$value['trans_orderid'],$value['is_statement_pay']
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
                        $heads=[
                            '合同号','请款单号','采购单号','网拍账号','拍单号','采购仓库','采购员','申请日期','付款日期','一级产品线','sku',
                            '产品名称','采购单价','采购数量','到货数量','入库数量','报损数量','报损状态','实际入库数量','次品数量','商品额',
                            '运费','优惠额','加工费','请款金额','供应商编码','供应商名称','验货状态','备注','付款状态','采购单状态',
                            '支付方式','支付平台','结算方式','对账单号','是否1688下单','请款人','付款人','付款回单编号','付款流水号','是否对账单请款','支付账号'
                        ];
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
                ob_flush();
                flush();
                usleep(100);
            }
        }
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url= $down_host.'download_csv/'.$template_file;
        $this->success_json($down_file_url);
    }

    /**
     * 网菜单详情付款
     * @author harvin
     * /finance/payment_order_pay/get_net_order_info
     * * */
    public function get_net_order_info() {
        $requisition_number = $this->input->get_post('requisition_number'); //请款单号
        if (empty($requisition_number)) {
            $this->error_json('请求参数错误');
        }
        try {
            $data_list = $this->payment_order_pay_model->net_order_info($requisition_number);
            $this->success_json($data_list);
        } catch (Exception $exc) {
           
            $this->error_json($exc->getMessage());
        }

        
    }
    /**
     * 网采线下付款
     * @author harvin
     * @date 2019-5-9
     */
    public function order_net_pay(){
        $requisition_number = $this->input->get_post('requisition_number'); //请款单号
        $payment_notice=$this->input->get_post('payment_notice'); //付款备注
        if (empty($requisition_number)) {
            $this->error_json('请求参数错误');
        }
        if(empty($payment_notice)){
            $this->error_json('请填写付款备注');
        }

//        if(!empty($payment_notice)){
//            $payment_notice = make_semiangle($payment_notice);
//        }


        $reules=$this->payment_order_pay_model->get_order_net_pay($requisition_number,$payment_notice);

        if($reules['bool']){
            $this->success_json([],NULL,$reules['msg']);
        }else{
            $this->error_json($reules['msg']);
        }         
    }
    /**
     * 网采单驳回 
     * @author harvin 2019-1-18
     * /finance/payment_order_pay/net_order_reject
     * (接口作废)
     * * */
    public function net_order_reject() {
        $id = $this->input->get_post('id'); //参数id int
        if (empty($id)) {
            $this->error_json('参数错误');
        }
        $bool = $this->payment_order_pay_model->get_payment_contract_reject($id);
        if ($bool) {
            $this->success_json([],null, '操作成功');
        } else {
            $this->error_json('操作失败');
        }
    }

    /**
     * 合同单待财务审核页面显示
     * /finance/payment_order_pay/get_contract_order_info
     * * */
    public function get_contract_order_info() {
        try {
            $id = $this->input->get_post('id'); //参数id
            if (empty($id)) {
                $this->error_json('参数错误');
            }
            $data_list = $this->payment_order_pay_model->contract_order_info($id);
            $this->success_json($data_list,null,'操作成功');
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 财务审核操作(合同)
     * @author harvin 2019-1-17
     * /finance/payment_order_pay/contract_order_save
     * */
    public function contract_order_save() {
        try {
            $id = $this->input->get_post('id'); //参数id
            $review_notice = $this->input->get_post('review_notice'); //审核备注
            $type = $this->input->get_post('type'); // 1 通过 2不通过
            $status = $this->input->get_post('status'); // 审核层级 60":"待财务主管审核","61":"待财务经理审核","62":"待财务总监审核","63":"待总经办审核" 30财务审核
            if (empty($id)) {
                $this->error_json('参数错误');
            }
            if (empty($review_notice)) {
                $this->error_json('请填写审核备注');
            }
            if (empty($type)) {
                $this->error_json('请选择审核状态');
            }
            $temp = $this->payment_order_pay_model->get_contract_order_save($id, $review_notice, $type,$status);
            if ($temp['bool']) {
                $this->success_json([],null, $temp['msg']);
            } else {
                $this->error_json($temp['msg']);
            }
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 财务付款显示(合同单 供应商基本信息一部分)
     * @author harvin 2019-1-19
     * http://www.caigou.com/finance/payment_order_pay/payment_contract_list
     * * */
    public function payment_contract_list() {
        $id = $this->input->get_post('id'); //参数id
        if (empty($id)) {
            $this->error_json('参数错误');
        }
        $data_list = $this->payment_order_pay_model->get_payment_contract($id);
        if(!empty($data_list['data'])){
             $this->success_json($data_list['data'],null,$data_list['msg']);
        }else{
            $this->error_json($data_list['msg']);
        }
        
       
    }

    /**
     * 财务付款收款方信息显示 (收款方下拉框联动)
     * http://www.caigou.com/finance/payment_order_pay/payment_linkage
     * * */
    public function payment_linkage() {
        $supplier_code = $this->input->get_post('supplier_code'); //供应商编码
        $pay_type = $this->input->get_post('pay_type'); //支付方式        
        if (empty($supplier_code) || empty($pay_type)) {
            $this->error_json('参数错误');
        }
        $data_list = $this->payment_order_pay_model->get_payment_linkage($supplier_code, $pay_type);
        $this->success_json($data_list);
    }

    /**
     *  财务付款我司付款账号显示 (付款方下拉框联动)
     * @author harvin 2019-1-18
     * http://www.caigou.com/finance/payment_order_pay/payment_bank
     * * */
    public function payment_bank() {
        $account_short = $this->input->get_post('account_short'); //账号简称
        if (empty($account_short)) {
            $this->error_json('参数错误');
        }
        $data_list = $this->payment_order_pay_model->get_payment_bank($account_short);
        $this->success_json($data_list);
    }

    /**
     * 保存财务付款(合同)
     * @author harvin 
     * http://www.caigou.com/finance/payment_order_pay/payment_contract_save
     * */
    public function payment_contract_save() {
        $pay_account = $this->input->get_post('pay_account'); //支付账号
        $pay_branch_bank = $this->input->get_post('pay_branch_bank'); //付款银行
        $pay_number = $this->input->get_post('pay_number'); //付款账号名
        $pay_price = $this->input->get_post('pay_price'); //申请金额
        $id = $this->input->get_post('id'); //参数id
        $images = $this->input->get_post('images'); //付款回执
        $payer_time = $this->input->get_post('payer_time'); //付款时间
        $payment_notice=$this->input->get_post('payment_notice');//付款备注
        if (empty($pay_account) || empty($pay_branch_bank) || empty($pay_number) || empty($pay_price) || empty($id) || empty($images) || empty($payer_time)) {
            $this->error_json('请求参数缺少');
        }
        $temp = $this->payment_order_pay_model->get_payment_contract_save($pay_account, $pay_branch_bank, $pay_number, $pay_price, $id, $images, $payer_time,$payment_notice);
        if ($temp['bool']) {
            $this->success_json([],null,$temp['msg']);
        } else {
            $this->error_json($temp['msg']);
        }
    }

    /**
     * 财务付款驳回（合同）
     * @author harvin 2019-1-18
     * http://www.caigou.com/finance/payment_order_pay/payment_contract_reject
     * * */
    public function payment_contract_reject() {      
        $id = $this->input->get_post('id'); //参数id
        $payment_notice=$this->input->get_post('payment_notice');//付款备注
        if (empty($id)) {
            $this->error_json('参数不能为空');
        }
        if(empty($payment_notice)){
              $this->error_json('驳回必须填写原因');
        }
        $temp = $this->payment_order_pay_model->get_payment_contract_reject($id,$payment_notice);
        if ($temp['bool']) {
            $this->success_json([],null, $temp['msg']);
        } else {
            $this->error_json($temp['msg']);
        }
    }

    /**
     * 导出
     * @author harvin2019-1-19
     * finance/payment_order_pay/contract_export
     * */
    public function contract_export() {
        $id = $this->input->get_post('id'); 
        if (empty($id)) {
            $this->error_json('请勾选需导出的数据');
        }
        $data_list = $this->payment_order_pay_model->get_pay_list([], '', '', '', $id);
        $heads = ['付款状态', '单号', '付款信息', '申请金额/运费', '申请人/申请时间', '审核人/审核时间', '付款人/付款时间', '备注',];
        foreach ($data_list as $key => $value) {
            $datalist[] = [
                $value['pay_status'],
                '申请单号:' . $value['requisition_number'] . "\n\r" . "合同号:" . $value['pur_number'],
                '供应商:' . $value['supplier_code'] . "\n\r" . '支付方式:' . $value['pay_type'] . "\n\r" . '结算方式:' . $value['settlement_method'] . "\n\r" . '支付平台:' . $value['payment_platform'],
                $value['pay_price'],
                '申请人:' . $value['applicant'] . "\n\r" . '申请时间:' . $value['application_time'],
                '审核人:' . $value['auditor'] . "\n\r" . "审核时间:" . $value['review_time'],
                '付款人:' . $value['payer_time'] . "\n\r" . "付款时间:" . $value['payer_time'],
                $value['review_notice'],
            ];
        }
        $filename = '请款单合同-' . date('YmdHis') . '.csv';
        export($heads, $datalist, $filename);
       
    }

    /**
     * 财务-应付款-合同-备注显示
     * @author liwuxue
     * @date 2019/2/13 9:44
     * @param
     * @throws Exception
     */
    public function show_note()
    {
        try {
            /** @var Payment_order_pay_model $model */
            $model = $this->payment_order_pay_model;
            $data = $model->api_get_note((int)$this->input->get("id"));
            $this->success_json($data);
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 财务-应付款-合同-备注
     * @author liwuxue
     * @date 2019/2/13 9:45
     * @param
     * @method POST
     */
    public function add_note()
    {
        try {
            /** @var Payment_order_pay_model $model */
            $model = $this->payment_order_pay_model;
            $id = (int)$this->input->post("id");
            $note = $this->input->post("note");
            $model->api_add_note($id, $note);
            $this->success_json([], "操作成功！");
        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }
    /**
     * 网采单 线下批量付款(显示)
     * @author harvin
     * finance/payment_order_pay/net_offline_payment
     */
    public function net_offline_payment(){
        $ids=$this->input->get_post('ids'); //参数id
          if(empty($ids)){
              $this->error_json('请勾选数据');
          }
          $ids= explode(',', $ids);
          if(!empty($ids) && !is_array($ids)){
              $this->error_json('参数id格式不正确');
          }
         try {
           // 判断是否同一个供应商,结算方式，支付方式 结算比例 是否退税 运费支付 是否一样 
        //  $this->Payment_order_contract_pay_model->get_supplier_ststus($ids,FALSE,SOURCE_NETWORK_ORDER);
          //获取显示数据
          $data= $this->Payment_order_contract_pay_model->get_offline_payment($ids);
          $this->success_json($data,null,'');
          } catch (Exception $exc) {
             $this->error_json($exc->getMessage());
          }  
    }
      /**
       * 线下支付---保存（网采请款单）
       * @author harvin 2019-4-7
       * /finance/payment_order_pay/net_offline_payment_save
       */
      public function net_offline_payment_save(){
        $ids = $this->input->get_post('ids'); //参数id
        $pay_price = $this->input->get_post('pay_price'); //申请金额
        $payer_time = $this->input->get_post('payer_time'); //付款时间
        $account_short = $this->input->get_post('account_short'); //付款简称
        $pay_type=$this->input->get_post('pay_type');//支付方式
        $supplier_code=$this->input->get_post('supplier_code');//供应商编码
        $remarks=$this->input->get_post('remarks'); //付款备注
        $images='';
        if(empty($ids)){
            $this->error_json('参数id格式不能为空');
        }
        if(empty($payer_time)){
            $this->error_json('请选择付款时间');
        }  
        if(empty($account_short)){
            $this->error_json('请选择付款账号简称');
        }
//        if(empty($pay_type)){
//            $this->error_json('请选择支付方式');
//        }
//        if(empty($supplier_code)){
//             $this->error_json('供应商编码不能为空');
//        }

      $temp=  $this->Payment_order_contract_pay_model
                ->get_offline_payment_save($ids,$payer_time,$account_short,$pay_type,$supplier_code,$images,$pay_price,$remarks);
        if($temp['bool']){
            $this->success_json([],null,$temp['msg']);
        }else{
            $this->error_json($temp['msg']);
        }  
      }


    /**
     * 主体账号模糊查询
     */
    public function account_list(){
        $params['purchase_account'] = $this->input->get_post('purchase_account');
        $list = $this->payment_order_pay_model->get_account_list($params);
        $this->success_json($list);

    }

    /**
     * 添加付款申请单备注 wangliang
     * requisition_number 申请单号
     * remark 备注内容
     */
    public function order_pay_remark(){
        $params['requisition_number'] = $this->input->get_post('requisition_number');
        if(empty($params['requisition_number'])) $this->success_json('申请单号不可为空');

        $params['remark'] = $this->input->get_post('remark');
        if(empty($params['remark'])) $this->success_json('请填写备注');

        $return = $this->payment_order_pay_model->remark_save($params);
        if($return['bool']){
            $this->success_json([],null,$return['msg']);
        }else{
            $this->error_json($return['msg']);
        }
    }

    /**
     * 获取申请单备注 wangliang
     */
    public function get_remark_list(){
        $params['requisition_number'] = $this->input->get_post('requisition_number');
        if(empty($params['requisition_number'])) $this->success_json('申请单号不可为空');

        $return = $this->payment_order_pay_model->get_remark_list($params);
        $this->success_json($return);
    }

    /**
     * 添加备注及显示备注
     * @author harvin
     * @date 2019-06-28
     */
    public function get_remark_log_list(){
        $id=(int)$this->input->get_post('id');
        if(empty($id)){
            $this->error_json('参数请求不合法');
        }
        try {
            $data=$this->payment_order_pay_model->remark_log_list($id);
            $this->success_json($data,null,'获取成功');
        } catch (Exception $exc) {
            echo $exc->getTraceAsString();
        } 
    }
    /**
     * 添加请款单日志
     * @author harvin
     * @date 2019-06-28
     */
    public function add_remark_log(){
       $id=(int)$this->input->get_post('id');
       $remark=$this->input->get_post('remark'); 
       if(empty($id)){
           $this->error_json('参数id,错误');
       }
       if(empty($remark)){
           $this->error_json('请填写备注');
       }
       try {
           $bool=$this->payment_order_pay_model->add_remark_log($id,$remark);
           if($bool){
               $this->success_json([],NULL,'保存成功');
           }
       } catch (Exception $exc) {
           $this->error_json($exc->getMessage());
       }
          
       
    }
    
    
}
