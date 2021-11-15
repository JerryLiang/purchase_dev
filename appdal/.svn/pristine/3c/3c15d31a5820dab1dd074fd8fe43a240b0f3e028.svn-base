<?php

/**
 * Created by PhpStorm.
 * 请款单控制器
 * User: Jolon
 * Date: 2019/01/10 0027 11:17
 */
class Statement_order_pay extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('statement/Purchase_statement_model');

        $this->load->helper('status_supplier');
    }


    /**
     * 对账单-创建请款单-数据预览（第一步）
     * @author Jolon
     * @desc array $_POST['statement_number']         要请款对账单号
     */
    public function statement_pay_order_preview() {
        $this->load->helper('status_order');
        $this->load->helper('status_finance');

        try{
            $this->load->model('finance/purchase_order_pay_model');


            $statement_number         =  $this->input->post('statement_number');// 对账单号
            if(empty($statement_number)){
                throw new Exception('无对账单号不允许申请款');
            }

            $statementInfo = $this->Purchase_statement_model->get_statement($statement_number);

            if(empty($statementInfo) or empty($statementInfo['items_list'])){
                throw new Exception('对账单号：'.$statement_number.' 对账单或对账单明细未找到');
            }

            $result = $this->purchase_order_pay_model->verify_order_status_enable_pay($statement_number);
            if($result !== true){
                throw new Exception('对账单号：'.$statement_number.' 对账单存在未完结的请款单['.$result.']');
            }

            // 对账单为已作废
            if($statementInfo['status_valid'] == 2){
                throw new Exception('对账单号：'.$statement_number.' 作废的对账单不能请款');
            }

            // 对账单状态为已付款/请款中
            if(in_array($statementInfo['statement_pay_status'],[PART_PAID,PAY_PAID])){
                throw new Exception('对账单号：'.$statement_number.' 已经付款不能再请款');
            }

            // 对账单已经上传扫描件
            if(!in_array($statementInfo['statement_pdf_status'],[30,40]) or empty($statementInfo['statement_pdf_url'])){
                throw new Exception('对账单号：'.$statement_number.' 请先上传扫描件');
            }

            $purchase_numbers = array_column($statementInfo['summary_list'],'purchase_number');

            // 已付金额（采购单）
            $paid_price_list = $this->purchase_order_pay_model->get_pay_total_by_purchase_number($purchase_numbers);
            $paid_price_list = arrayKeyToColumn($paid_price_list,'purchase_number');

            // 计算累计与占比
            foreach($statementInfo['summary_list'] as &$summary_value){
                $purchase_number = $summary_value['purchase_number'];
                $summary_value['total_instock_weight'] = format_two_point_price($summary_value['total_instock_weight'] / 1000);// 转成千克

                $summary_value['paid_freight'] = isset($paid_price_list[$purchase_number])?$paid_price_list[$purchase_number]['paid_freight']:0;
                $summary_value['paid_discount'] = isset($paid_price_list[$purchase_number])?$paid_price_list[$purchase_number]['paid_discount']:0;
                $summary_value['paid_process_cost'] = isset($paid_price_list[$purchase_number])?$paid_price_list[$purchase_number]['paid_process_cost']:0;
                $summary_value['paid_commission'] = isset($paid_price_list[$purchase_number])?$paid_price_list[$purchase_number]['paid_commission']:0;

                $result_po = $this->purchase_order_pay_model->verify_order_status_enable_pay_by_po($purchase_number);
                if($result_po !== true) $this->error_json('采购单号：'.$purchase_number.' 存在未完结的请款单['.$result_po.']');

                // 请款时 默认生成对账单时的 运费、优惠额、加工费
                $summary_value['pay_freight']      = $summary_value['order_freight'];
                $summary_value['pay_process_cost'] = $summary_value['order_process_cost'];
                $summary_value['pay_discount']     = $summary_value['order_discount'];

                $summary_value['pay_real_price']  = format_two_point_price($summary_value['pay_product_money'] + $summary_value['pay_freight'] - $summary_value['pay_discount'] + $summary_value['pay_process_cost']);
                $summary_value['freight_total']   = $summary_value['pay_freight'] + $summary_value['paid_freight'];// 总运费
                $summary_value['freight_percent'] = format_two_point_price($summary_value['freight_total'] * 100 / $summary_value['total_instock_price']);

            }

            //删除"采购单明细"的子页面
            //$statementInfo += $this->Purchase_statement_model->get_statement_append_info(array_column($statementInfo['summary_list'],'purchase_number'));


            // 对账单扫描件文件信息
            $compact_url = [];
            $compact_url[] = [
                'file_name' => $statementInfo['statement_pdf_file_name'],
                'file_path' => $statementInfo['statement_pdf_url']
            ];
            if($statementInfo['attachmentPathEsign']){
                $compact_url[] = [
                    'file_name' => $statementInfo['statement_number'].'运费明细.pdf',
                    'file_path' => $statementInfo['attachmentPathEsign']
                ];
            }
            $statementInfo['compact_url'] = $compact_url;

            $this->success_json_format($statementInfo);
        }catch(Exception $exception){
            $this->error_json($exception->getMessage());
        }

    }


    /**
     * 对账单-创建请款单-付款申请书预览（第二步）
     * @author Jolon
     */
    public function statement_pay_order_preview_requisition_payment(){
        try{
            $this->load->model('finance/purchase_order_pay_model');

            $statement_number         =  $this->input->post('statement_number');// 对账单号
            if(empty($statement_number)){
                throw new Exception('无对账单号不允许申请款');
            }

            $statementInfo = $this->Purchase_statement_model->get_statement($statement_number);
            if(empty($statementInfo) or empty($statementInfo['items_list'])){
                throw new Exception('对账单号：'.$statement_number.' 对账单或对账单明细未找到');
            }

            $result = $this->purchase_order_pay_model->verify_order_status_enable_pay($statement_number);
            if($result !== true){
                throw new Exception('对账单号：'.$statement_number.' 对账单存在未完结的请款单['.$result.']');
            }

            // 对账单为已作废
            if($statementInfo['status_valid'] == 2){
                throw new Exception('对账单号：'.$statement_number.' 作废的对账单不能请款');
            }

            // 对账单状态为已付款/请款中
            if(in_array($statementInfo['statement_pay_status'],[PART_PAID,PAY_PAID])){
                throw new Exception('对账单号：'.$statement_number.' 已经付款不能再请款');
            }

            // 请款界面提交的数据
            $pay_items_list                  = $this->input->post('pay_items_list');// PO 维度：请款商品额\请款运费\请款加工费\请款优惠额
            $post_data['commission_percent'] = $this->input->post('commission_percent');// 代采佣金占比
            $post_data['freight_desc']       = $this->input->post('freight_desc');// 运费说明
            $post_data['create_notice']      = $this->input->post('create_notice');// 请款备注
            $compact_url                     = $this->input->post('compact_url');// 上传合同上传扫描件

            if(empty($pay_items_list) or !is_json($pay_items_list)){
                throw new Exception('采购单请款金额明细缺失或不是JSON格式');
            }
            $pay_items_list = json_decode($pay_items_list,true);

            $total_product_money = $total_freight = $total_discount = $total_process_cost = $total_pay_price = $total_loss_product_money = $total_commission = 0;
            foreach($pay_items_list as $pay_item){

                // 验证金额等数据是否为 2 位小数
                if(!isset($pay_item['loss_product_money']) or is_two_decimal($pay_item['loss_product_money']) === false){
                    throw new Exception('报损商品额未设置或不是两位小数');
                }
                if(!isset($pay_item['commission']) or is_two_decimal($pay_item['commission']) === false){
                    throw new Exception('请款代采佣金未设置或不是两位小数');
                }
                if(!isset($pay_item['product_money']) or is_two_decimal($pay_item['product_money']) === false){
                    throw new Exception('请款商品额未设置或不是两位小数');
                }
                if(!isset($pay_item['freight']) or is_two_decimal($pay_item['freight']) === false){
                    throw new Exception('请款运费未设置或不是两位小数');
                }
                if(!isset($pay_item['discount']) or is_two_decimal($pay_item['discount']) === false){
                    throw new Exception('请款优惠额未设置或不是两位小数');
                }
                if(!isset($pay_item['process_cost']) or is_two_decimal($pay_item['process_cost']) === false){
                    throw new Exception('请款加工费未设置或不是两位小数');
                }
                if(!isset($pay_item['pay_price']) or is_two_decimal($pay_item['pay_price']) === false){
                    throw new Exception('请款总额未设置或不是两位小数');
                }

                // 计算请款总额与明细是否相等
                $pay_price_tmp = $pay_item['product_money'] + $pay_item['freight'] + $pay_item['process_cost'] - $pay_item['discount'] + $pay_item['commission'];
                if(bccomp($pay_item['pay_price'],$pay_price_tmp,3) != 0){
                    throw new Exception('请款金额明细之和不等于请款总额');
                }

                // 计算实际请款总金额
                $total_loss_product_money += $pay_item['loss_product_money'];
                $total_product_money      += $pay_item['product_money'];
                $total_freight            += $pay_item['freight'];
                $total_process_cost       += $pay_item['process_cost'];
                $total_discount           += $pay_item['discount'];
                $total_commission         += $pay_item['commission'];
                $total_pay_price          += $pay_item['pay_price'];
            }

            if($statementInfo['is_drawback'] == PURCHASE_IS_DRAWBACK_Y and (abs($total_freight) + abs($total_process_cost) + abs($total_discount) + abs($total_commission)) > 0){
                throw new Exception('退税的对账单不允许请款运费、优惠额、加工费、代采佣金');
            }

            if($total_freight > 0 and empty($post_data['freight_desc'])){// 请了运费才必填运费说明
                throw new Exception('运费说明不能为空');
            }
            if(abs($total_commission) > 0 and empty($post_data['commission_percent'])){// 请了运费才必填运费说明
                throw new Exception('请款代采佣金必须填写代采佣金占比');
            }
            if(empty($post_data['create_notice'])){
                throw new Exception('请款备注不能为空');
            }
            if($compact_url){
                if(!is_json($compact_url)){
                    throw new Exception('对账单扫描件数据格式错误');
                }else{
                    $compact_url = json_decode($compact_url,true);
                    foreach($compact_url as $compact_value){
                        if(!isset($compact_value['file_name']) or empty($compact_value['file_name'])){
                            throw new Exception('对账单扫描件数据文件名错误');
                        }
                        if(!isset($compact_value['file_path']) or empty($compact_value['file_path'])){
                            throw new Exception('对账单扫描件数据文件路径错误');
                        }
                        if(stripos($compact_value['file_name'],$statement_number) !== 0){
                            throw new Exception('上传的对账单扫描件文件名必须以对账单号开头');
                        }
                        $post_data['compact_url'][] = [
                            'file_name' => isset($compact_value['file_name'])?$compact_value['file_name']:'',
                            'file_path' => isset($compact_value['file_path'])?$compact_value['file_path']:'',
                        ];
                    }
                }
            }else{
                throw new Exception('对账单扫描件必须上传');
            }
            if(!isset($post_data['compact_url']) or empty($post_data['compact_url'])){
                throw new Exception('对账单扫描件必须上传');
            }

            // 请款金额汇总
            $post_data['pay_price']          = $total_pay_price;
            $post_data['loss_product_money'] = $total_loss_product_money;
            $post_data['product_money']      = $total_product_money;
            $post_data['freight']            = $total_freight;
            $post_data['discount']           = $total_discount;
            $post_data['process_cost']       = $total_process_cost;
            $post_data['commission']         = $total_commission;

            $post_data['pay_type']           = $statementInfo['pay_type'];// 支付方式
            $post_data['requisition_method'] = '0';// 请款方式
            $post_data['pay_ratio']          = '100%';// 请款比例
            $post_data['is_drawback']        = $statementInfo['is_drawback'];// 是否退税
            $post_data['purchase_account']   = '';
            $post_data['pai_number']         = '';


            // Start：分摊请款金额到请款单明细
            $items_list   = $statementInfo['items_list'];
            $summary_list = $statementInfo['summary_list'];
            // 1、按 PO+SKU 维度汇总入库金额，分摊运费优惠额
            $avg_po_sku_price_list  = [];
            $po_sku_price_list      = [];
            foreach($items_list as $item_value){
                $purchase_number = $item_value['purchase_number'];
                $sku             = $item_value['sku'];
                // 按 入库金额分摊
                if(isset($avg_po_sku_price_list[$purchase_number][$sku])){
                    $avg_po_sku_price_list[$purchase_number][$sku] += $item_value['pay_product_money'];
                }else{
                    $avg_po_sku_price_list[$purchase_number][$sku] = $item_value['pay_product_money'];
                }
            }
            // 2、按 本次报损金额分摊
            foreach($summary_list as $summary_value){
                $purchase_number               = $summary_value['purchase_number'];
                $loss_product_money_sku_detail = json_decode($summary_value['loss_product_money_sku_detail'],true);
                foreach($loss_product_money_sku_detail as $loss_sku_value){
                    $sku             = $loss_sku_value['sku'];
                    // 按 本次报损金额分摊
                    if(isset($avg_po_sku_price_list[$purchase_number][$sku])){
                        $avg_po_sku_price_list[$purchase_number][$sku] += $loss_sku_value['loss_product_money'];
                    }else{
                        $avg_po_sku_price_list[$purchase_number][$sku] = $loss_sku_value['loss_product_money'];
                    }
                }
            }

            $t111 = $t112 = $tll3 = $tll4 = 0;// 汇总总金额，比较分摊前后是否相等
            // 采购单请款金额按照 采购单+SKU维度的入库金额分摊
            foreach($pay_items_list as $po_number => $po_pay_item_value){
                if(!isset($avg_po_sku_price_list[$po_number])) throw new Exception('对账单的采购单明细数据有误');

                // 采购单请款金额按照 采购单+SKU维度的入库金额分摊
                $avg_po_sku_price_list_now              = [];
                $avg_po_sku_price_list_now[$po_number]  = $avg_po_sku_price_list[$po_number];

//                // 分摊请款金额到请款单明细
//                $avg_allot_freight      = amountAverageDistribute($po_pay_item_value['freight'], $avg_po_sku_price_list_now);// 分摊运费
//                $avg_allot_discount     = amountAverageDistribute($po_pay_item_value['discount'], $avg_po_sku_price_list_now);// 分摊优惠额
//                $avg_allot_process_cost = amountAverageDistribute($po_pay_item_value['process_cost'], $avg_po_sku_price_list_now);// 分摊加工费
//                $avg_allot_commission   = amountAverageDistribute($po_pay_item_value['commission'], $avg_po_sku_price_list_now);// 分摊代采佣金
//                $avg_allot_pay_price    = amountAverageDistribute($po_pay_item_value['pay_price'], $avg_po_sku_price_list_now);// 分摊请款总额

                $tll3 += $po_pay_item_value['product_money'];
//                $tll4 += $po_pay_item_value['pay_price'];

                // 获取 PO+SKU 维度 分摊后的 金额
                foreach($avg_po_sku_price_list_now as $average_key => $average_sku_list){
                    foreach($average_sku_list as $sku_key => $sku_value){
                        // 商品金额取 本次请款入库剩余商品金额 + 本次报损商品金额
                        $po_sku_price_list[$average_key][$sku_key]['item_product_money'] = isset($avg_po_sku_price_list[$average_key][$sku_key]) ? $avg_po_sku_price_list[$average_key][$sku_key] : 0;
//                        $po_sku_price_list[$average_key][$sku_key]['item_freight']      = isset($avg_allot_freight[$average_key][$sku_key]) ? $avg_allot_freight[$average_key][$sku_key] : 0;
//                        $po_sku_price_list[$average_key][$sku_key]['item_discount']     = isset($avg_allot_discount[$average_key][$sku_key]) ? $avg_allot_discount[$average_key][$sku_key] : 0;
//                        $po_sku_price_list[$average_key][$sku_key]['item_process_cost'] = isset($avg_allot_process_cost[$average_key][$sku_key]) ? $avg_allot_process_cost[$average_key][$sku_key] : 0;
//                        $po_sku_price_list[$average_key][$sku_key]['item_commission']   = isset($avg_allot_commission[$average_key][$sku_key]) ? $avg_allot_commission[$average_key][$sku_key] : 0;
//
//                        // 请款总金额求和
//                        $po_sku_price_list[$average_key][$sku_key]['item_pay_price']    = format_price(
//                                      $po_sku_price_list[$average_key][$sku_key]['item_product_money']
//                                    + $po_sku_price_list[$average_key][$sku_key]['item_freight']
//                                    - $po_sku_price_list[$average_key][$sku_key]['item_discount']
//                                    + $po_sku_price_list[$average_key][$sku_key]['item_process_cost']
//                                    + $po_sku_price_list[$average_key][$sku_key]['item_commission']
//                        );
                        $t111 += $po_sku_price_list[$average_key][$sku_key]['item_product_money'];
//                        $t112 += $po_sku_price_list[$average_key][$sku_key]['item_pay_price'];
                    }
                }
            }
            if(bccomp(floatval($t111),floatval($tll3),3) != 0 or bccomp(floatval($t112),floatval($tll4),3) != 0){
                throw new Exception('请款商品金额或请款总金额分摊错误，不等于请款总额');
            }

            $post_data['pay_items_list'] = json_encode($pay_items_list);// 采购单维度运费、优惠额、请款总额
            $post_data['item_data_list'] = json_encode($po_sku_price_list);// 备货单（PO+SKU）维度运费、优惠额、请款总额
            // End：分摊请款金额到请款单明细


            $supplier_account_info = $this->Supplier_payment_info_model->check_payment_info($statementInfo['supplier_code'], $statementInfo['is_drawback'], $statementInfo['purchase_type_id']); //支付方式:1.支付宝,2.对公支付，3.对私支付

            $company_info = compactCompanyInfo($statementInfo['purchase_name']);
            if(empty($company_info)) throw new Exception('获取【付款申请书】采购主体失败');

            if (empty($supplier_account_info)) {
                switch($post_data['pay_type']){
                    case PURCHASE_PAY_TYPE_ALIPAY:
                        $error_msg = '线上支付宝';
                        break;
                    case PURCHASE_PAY_TYPE_PRIVATE:
                        $error_msg = '线下境外';
                        break;
                    case PURCHASE_PAY_TYPE_PUBLIC:
                        $error_msg = '线下境内';
                        break;
                    default :
                        $error_msg = '支付方式错误';
                }
                throw new Exception($error_msg. '-收款账号失败');
            }else{

                $supplier_account_info['supplier_name'] = $this->supplier_model->get_supplier_name($statementInfo['supplier_code']);
            }


            if(in_array($post_data['pay_type'],[PURCHASE_PAY_TYPE_PRIVATE])){// 线下境外
                $receive_unit = $supplier_account_info['supplier_name']."(".$supplier_account_info['account_name'].")";
            }else{// 支付宝、 线下境内
                $receive_unit = $supplier_account_info['supplier_name'];
            }

            // 付款申请书 数据
            $from_data = [
                'statement_number'        => $statement_number,
                'invoice_looked_up'       => $company_info['name'],// 抬头
                'receive_unit'            => $receive_unit,// 收款单位
                'payment_platform_branch' => $supplier_account_info['payment_platform_branch'],// 开户行
                'account'                 => $supplier_account_info['account'],// 账号
                'pay_date'                => date('Y年m月d日'),
                'pay_price'               => format_two_point_price($post_data['pay_price']),
                'pay_price_cn'            => numberPriceToCname($post_data['pay_price']),
                'check_department'        => '总经办',
            ];
            // 36253 采购单运费请款时，当支付方式=线下境外，请款方式=运费请款 时 公司主体=香港易佰
            if($post_data['requisition_method'] == PURCHASE_REQUISITION_METHOD_HAND && $post_data['pay_type'] == PURCHASE_PAY_TYPE_PRIVATE){
                $from_data['invoice_looked_up'] = 'YIBAI TECHNOLOGY LTD';
            }
            // 请款摘要
            $from_data['abstract_remark'] = abstractRemarkTemplate(SOURCE_SUBJECT_COMPACT_ORDER,$post_data['product_money'],$post_data['product_money'],0);

            $this->rediss->setData($statement_number.'_post_data',$post_data);
            $this->rediss->setData($statement_number.'_from_data',$from_data);

            $this->success_json($from_data);
        }catch(Exception $exception){
            $this->error_json($exception->getMessage());
        }

    }

    /**
     * 对账单-创建请款单（第三步）
     * @author Jolon
     */
    public function statement_pay_order_create(){
        try{
            $this->load->model('finance/purchase_order_pay_model');
            $statement_number =  $this->input->post('statement_number');// 对账单号
            $payment_reason =  $this->input->post('payment_reason');// 付款原因

            if(empty($statement_number) or empty($payment_reason)){
                throw new Exception('参数【statement_number或payment_reason】缺失');
            }

            $statementInfo = $this->Purchase_statement_model->get_statement($statement_number);
            if(empty($statementInfo) or empty($statementInfo['items_list'])){
                throw new Exception('对账单号：'.$statement_number.' 对账单或对账单明细未找到');
            }

            $result = $this->purchase_order_pay_model->verify_order_status_enable_pay($statement_number);
            if($result !== true){
                throw new Exception('对账单号：'.$statement_number.' 对账单存在未完结的请款单['.$result.']');
            }

            // 对账单为已作废
            if($statementInfo['status_valid'] == 2){
                throw new Exception('对账单号：'.$statement_number.' 作废的对账单不能请款');
            }

            // 对账单状态为已付款/请款中
            if(in_array($statementInfo['statement_pay_status'],[PART_PAID,PAY_PAID])){
                throw new Exception('对账单号：'.$statement_number.' 已经付款不能再请款');
            }

            // 读取缓存的数据
            $post_data = $this->rediss->getData($statement_number.'_post_data');
            $from_data = $this->rediss->getData($statement_number.'_from_data');
            if(empty($post_data) or empty($from_data)){
                throw new Exception('请款信息或付款申请书信息缺失【数据缓存已过有效期（4800秒）】');
            }else{
                $this->rediss->deleteData($statement_number.'_post_data');
                $this->rediss->deleteData($statement_number.'_from_data');
            }

            $data                                = [];
            $data['post_data']                   = $post_data;// 请款数据
            $data['from_data']                   = $from_data;// 付款申请书数据
            $data['statement_number']            = $statement_number;
            $data['purchase_name']               = $statementInfo['purchase_name'];
            $data['from_data']['payment_reason'] = $payment_reason;


            $result = $this->purchase_order_pay_model->statement_pay_order_create($data);
            if($result['code']){
                $this->success_json($result['data']);
            }else{
                throw new Exception($result['msg']);
            }

        }catch(Exception $exception){
            $this->error_json($exception->getMessage());
        }
    }


    /**
     * 请款单、请款审核 查看对账单详情
     */
    public function pay_statement_detail(){
        $requisition_number = $this->input->get_post('requisition_number');
        $statement_number = $this->input->get_post('statement_number');
        if(empty($statement_number) or empty($requisition_number)) $this->error_json('参数错误');

        $this->load->helper('status_order');
        $this->load->helper('status_finance');
        $this->load->model('finance/Purchase_order_pay_model');
        $this->load->model('supplier/Supplier_model');

        // 对账单信息
        $statement = $this->Purchase_statement_model->get_statement($statement_number);
        if(empty($statement)){
            $this->error_json('未获取到数据');
        }
        //请款记录
        $now_pay_record_list = $this->Purchase_order_pay_model->get_pay_records_by_pur_number($statement_number);
        if(empty($now_pay_record_list)){
            $this->error_json('未获取到请款单数据');
        }

        // 供应商是否包邮
        $supplierInfo = $this->Supplier_model->get_supplier_info($statement['supplier_code']);
        $statement['is_postage']    = $supplierInfo['is_postage'];
        $statement['is_postage_cn'] = getIsPostage($supplierInfo['is_postage']);

        // 采购单冲销汇总
        $pay_record_pay_book_list     = [];

        foreach($statement['summary_list'] as $key_summary => $value_summary){
            // 已付金额
            $paid_price_list = $this->Purchase_order_pay_model->get_pay_total_by_purchase_number([$value_summary['purchase_number']]);

            if($paid_price_list and isset($paid_price_list[0])){
                $statement['summary_list'][$key_summary]['paid_product_money'] = $paid_price_list[0]['paid_product_money'];
                $statement['summary_list'][$key_summary]['paid_freight']       = $paid_price_list[0]['paid_freight'];
                $statement['summary_list'][$key_summary]['paid_discount']      = $paid_price_list[0]['paid_discount'];
                $statement['summary_list'][$key_summary]['paid_process_cost']  = $paid_price_list[0]['paid_process_cost'];
                $statement['summary_list'][$key_summary]['paid_real_price']    = $paid_price_list[0]['paid_real_price'];
            }else{
                $statement['summary_list'][$key_summary]['paid_product_money'] = 0;
                $statement['summary_list'][$key_summary]['paid_freight']       = 0;
                $statement['summary_list'][$key_summary]['paid_discount']      = 0;
                $statement['summary_list'][$key_summary]['paid_process_cost']  = 0;
                $statement['summary_list'][$key_summary]['paid_real_price']    = 0;
            }
        }

        // 请款记录
        $pay_record_tmp_list = [];
        $waiting_audit_pay   = [];
        foreach($now_pay_record_list as $now_pay_record){
            $pay_record_tmp = [
                'requisition_number' => $now_pay_record['requisition_number'],
                'pur_number'         => $now_pay_record['pur_number'],
                'freight_desc'       => $now_pay_record['freight_desc'],
                'create_notice'      => $now_pay_record['create_notice'],
                'product_money'      => $now_pay_record['product_money'],
                'freight'            => $now_pay_record['freight'],
                'discount'           => $now_pay_record['discount'],
                'process_cost'       => $now_pay_record['process_cost'],
                'commission'         => $now_pay_record['commission'],
                'commission_percent' => $now_pay_record['commission_percent'],
                'pay_price'          => $now_pay_record['pay_price'],
                'pay_status'         => getPayStatus($now_pay_record['pay_status']),
                'applicant'          => getUserNameById($now_pay_record['applicant']),
                'application_time'   => $now_pay_record['application_time'],
                'loss_product_money' => $statement['total_loss_product_money'],// 请款报损商品额=对账单里面的报损商品额，只会全部付款，每次都相同
            ];

            $auditor_user = $audit_time = $audit_notice = '';
            // 取最新一个审核人
            if(!empty($now_pay_record['general_manager_id'])){
                $auditor_user = $now_pay_record['general_manager_id'];
                $audit_time = $now_pay_record['general_manager_time'];
                $audit_notice = $now_pay_record['general_manager_notice'];
            }elseif(!empty($now_pay_record['financial_officer_id'])){
                $auditor_user = $now_pay_record['financial_officer_id'];
                $audit_time = $now_pay_record['financial_officer_time'];
                $audit_notice = $now_pay_record['financial_officer_notice'];
            }elseif(!empty($now_pay_record['financial_manager_id'])){
                $auditor_user = $now_pay_record['financial_manager_id'];
                $audit_time = $now_pay_record['financial_manager_time'];
                $audit_notice = $now_pay_record['financial_manager_notice'];
            }elseif(!empty($now_pay_record['financial_supervisor_id'])){
                $auditor_user = $now_pay_record['financial_supervisor_id'];
                $audit_time = $now_pay_record['financial_supervisor_time'];
                $audit_notice = $now_pay_record['financial_supervisor_notice'];
            }elseif(!empty($now_pay_record['approver'])){
                $auditor_user = $now_pay_record['approver'];
                $audit_time = $now_pay_record['processing_time'];
                $audit_notice = $now_pay_record['processing_notice'];
            }elseif(!empty($now_pay_record['waiting_id'])){
                $auditor_user = $now_pay_record['waiting_id'];
                $audit_time = $now_pay_record['waiting_time'];
                $audit_notice = $now_pay_record['waiting_notice'];
            }elseif(!empty($now_pay_record['auditor'])){
                $auditor_user = $now_pay_record['auditor'];
                $audit_time = $now_pay_record['review_time'];
                $audit_notice = $now_pay_record['review_notice'];
            }
            $pay_record_tmp['auditor_user'] = getUserNameById($auditor_user);
            $pay_record_tmp['audit_time']   = $audit_time ;
            $pay_record_tmp['audit_notice'] = $audit_notice;
            $pay_record_tmp['is_postage_cn'] = $statement['is_postage_cn'];

            if($now_pay_record['requisition_number'] == $requisition_number){
                $waiting_audit_pay['create_notice'] = $pay_record_tmp['create_notice'];
                $waiting_audit_pay['freight_desc'] = $pay_record_tmp['freight_desc'];
                $waiting_audit_pay['audit_notice'] = $pay_record_tmp['auditor_user']."<br/>".$pay_record_tmp['audit_notice'];
                $waiting_audit_pay['id'] = $now_pay_record['id'];

                $waiting_audit_pay['compact_url_name']      = $now_pay_record['compact_url_name'];
                $waiting_audit_pay['compact_url']           = $now_pay_record['compact_url'];
                $waiting_audit_pay['commission_percent']    = $now_pay_record['commission_percent'];
            }

            $pay_record_tmp_list[]          = $pay_record_tmp;
            $pay_record_pay_book_list[]     = $now_pay_record['requisition_number'];
        }
        // 对账单扫描件
        $compact_url_file_list = [];
        if($waiting_audit_pay['compact_url_name']){
            if(stripos($waiting_audit_pay['compact_url_name'],',') !== false){
                $compact_url_list = explode(',',$waiting_audit_pay['compact_url']);
                $compact_url_name_list = explode(',',$waiting_audit_pay['compact_url_name']);
                foreach($compact_url_name_list as $key => $compact_url_name){
                    $compact_url_file_list[$compact_url_name] = isset($compact_url_list[$key])?$compact_url_list[$key]:'';
                }
            }else{
                $compact_url_file_list[$waiting_audit_pay['compact_url_name']] = $waiting_audit_pay['compact_url'];
            }
            unset($waiting_audit_pay['compact_url_name'],$waiting_audit_pay['compact_url']);
        }

        // 删除"采购单明细"的子页面
        // 采购单明细、采购单冲销汇总
        // $statement += $this->Purchase_statement_model->get_statement_append_info(array_column($statement['summary_list'],'purchase_number'));

        $statement['pay_record_info']                 = $pay_record_tmp_list;// 请款单信息列表
        $statement['pay_record_pay_book_list']        = $pay_record_pay_book_list;// 付款申请书
        $statement['compact_url_file_list']           = $compact_url_file_list;// 对账单扫描件列表
        $statement['waiting_audit_pay']               = $waiting_audit_pay;// 当前待审核的 审核备注


        $this->success_json($statement);
    }


}