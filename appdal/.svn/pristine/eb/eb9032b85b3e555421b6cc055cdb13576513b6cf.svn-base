<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * 采购单自动请款
 * User: Jolon
 * Date: 2019/08/30 10:00
 */
class Purchase_auto_payout_plan extends MY_API_Controller
{
    private $config_type_payout = 'PURCHASE_AUTO_PAYOUT';
    private  $small_dark_room    = 'SMALL_DARK_ROOM';// 小黑屋

    public function __construct(){
        parent::__construct();

        $this->load->model('finance/Purchase_auto_payout_model');
        $this->load->model('system/Data_control_config_model');
    }


    /**
     * 采购单自动请款【系统自动】
     * purchase_auto_payout_plan/system_auto_payout
     */
    public function system_auto_payout(){
        $create_notice      = '【自动请款】';
        //$session_key        = 'PURCHASE_AUTO_PAYOUT_ORIGINAL';// 采购自动请款是否运行过
        $config             = $this->Data_control_config_model->get_control_config($this->config_type_payout);
        $config             = isset($config['config_values']) ? $config['config_values'] : '';

        if(is_json($config)) $config = json_decode($config,true);

        $data_config = [
            'switch_auto_payout'   => isset($config['switch_auto_payout']) ? $config['switch_auto_payout'] : '2',
            'execute_time_list'    => isset($config['execute_time_list']) ? $config['execute_time_list'] : []
        ];

        if($data_config['switch_auto_payout'] == 1){// 开启自动请款计划任务
            $payout_switch = false;
            if($data_config['execute_time_list']){
                foreach($data_config['execute_time_list'] as $execute_time){
                    if(date('H:i:s') > $execute_time){// 判断是否处于 计划执行时间之后
                        $payout_switch = true;
                    }
                }
            }


            if($payout_switch){
                $len = $this->rediss->set_scard($this->config_type_payout);// 获取集合元素的个数
                //$latest_run_date = $this->rediss->getData($session_key);
                if ($len <=0) {// 标记当天已经运行过
                    //$this->rediss->setData($session_key, date('Y-m-d')); //设置缓存和有效时间
                    //$this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$session_key);

                    // 第一次运行的时候 缓存数据到 Redis，之后从Redis里面取数据
                    // 在付款状态=未申请付款、采购来源=网采、结算方式=款到发货、网拍账号=1688账号、是否退税=否、订单状态=等待到货
                    $source = SOURCE_NETWORK_ORDER;
                    $is_drawback = PURCHASE_IS_DRAWBACK_N;
                    $order_status = PURCHASE_ORDER_STATUS_WAITING_ARRIVAL;
                    $pay_status = PAY_UNPAID_STATUS;
                    $new_get_list_querySql = "SELECT `ppo`.`purchase_number`,ppo.buyer_id,ppo.buyer_name
                      FROM pur_purchase_order AS ppo
                      INNER JOIN pur_purchase_order_pay_type AS ppy ON ppo.purchase_number=ppy.purchase_number"
                        ." WHERE 1 "
                        ." AND ppo.pay_status={$pay_status}" // 未申请付款
                        ." AND ppo.source={$source}" // 网采
                        ." AND ppo.is_drawback={$is_drawback}" // 是否退税
                        ." AND ppo.purchase_order_status={$order_status}" // 等待到货
                        ." AND ppo.account_type=10" // 款到发货
                        ." AND ppy.purchase_acccount LIKE 'yibaisuperbuyers%'" // 1688账号
                        ." GROUP BY `ppo`.`purchase_number`";

                    $purchase_number_list  = $this->get_data($new_get_list_querySql);
                    $this->add_data_to_cache($purchase_number_list);
                }

                $send_data = $this->get_and_send_payout($create_notice);
                $success = $send_data['success'];
                $total_count = $send_data['total'];
                $this->success_json("执行成功：$success 个PO，总共：$total_count 个PO");
            }else{
                $this->error_json('自动请款计划任务还未到达运行时间');
            }
        }else{
            $this->error_json('自动请款计划任务暂未开启');
        }
    }

    /**
     * 获取请求数据
     */
    private function get_data($data)
    {
        return $this->Purchase_auto_payout_model->purchase_db->query($data)->result_array();
    }

    /**
     * 添加和检测小黑屋
     */
    private function add_data_to_cache($data)
    {
        foreach($data as $purchase_order){
            // 查看小黑屋是否有数据
            $have_small_dark_room = $this->rediss->getData($this->small_dark_room.$purchase_order['purchase_number']);
            if(isset($have_small_dark_room) and !empty($have_small_dark_room)){
                continue;
            }
            $this->rediss->set_sadd($this->config_type_payout,base64_encode(json_encode($purchase_order,JSON_UNESCAPED_UNICODE)));
        }
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_'.$this->config_type_payout);
    }

    /**
     * 获取缓存数据并执行请款
     */
    private function get_and_send_payout($create_notice, $handle_user = false)
    {
        $success = $total_count = 0;
        for($i = 0;$i < 200;$i ++){
            $purchase_order = $this->rediss->set_spop($this->config_type_payout);
            if(empty($purchase_order) || !isset($purchase_order[0])) break;// 执行完毕

            $purchase_order = $purchase_order[0];
            $purchase_order = json_decode(base64_decode($purchase_order),true);

            setActiveUserById(1);// 设置当前登录用户为采购员。便于记录操作人

            if(empty(getActiveUserId()) || empty(getActiveUserName())){// 用户登录激活失败
                $this->rediss->set_sadd($this->config_type_payout,base64_encode(json_encode($purchase_order,JSON_UNESCAPED_UNICODE)));// 执行失败 下次继续执行
                continue;
            }
            // 执行自动请款操作
            $result = $this->Purchase_auto_payout_model->do_auto_payout($purchase_order['purchase_number'],$create_notice, $handle_user);
            if($result['code']){
                $success ++;
                /* // 记录操作日志
                $old_status_text = getPayStatus(PAY_UNPAID_STATUS);
                $new_status_text = getPayStatus(PAY_WAITING_FINANCE_PAID);
                $detail          = "修改支付状态，从[{$old_status_text}]改为[{$new_status_text}]";

                // 记录操作日志
                operatorLogInsert(
                    [
                        'id'        => $purchase_order['purchase_number'],
                        'type'      => 'purchase_order',
                        'content'   => '采购单支付状态',
                        'detail'    => $detail,
                        'user'      => 'admin',
                        'user_id'   => 1
                    ]
                );  */
            }else{
                $total_count ++;
                $this->rediss->setData($this->small_dark_room.$purchase_order['purchase_number'],1,3600);// 放入小黑屋，一小时内不再请款
            }

            apiRequestLogInsert(
                [
                    'record_number'    => $purchase_order['purchase_number'],
                    'record_type'      => 'PURCHASE_AUTO_FUNDS',
                    'api_url'          => 'Purchase_auto_payout_plan/purchase_auto_funds',
                    'post_content'     => $result['code'] ? '请款成功' : '请款失败',
                    'response_content' => $result['code'] ? '' : $result['message'],
                    'status'           => 1,
                ]
            );
        }
    }

    /**
     * 采购单自动请款   每隔30分钟执行一次   需求号：28776
     */
    public function purchase_auto_funds()
    {
        try{
            $td = date("Y-m-d");
            $time = $td < date('Y-m-d', strtotime("2021-01-25"))? "2020-11-01 00:00:00": date("Y-m-d H:i:s", strtotime("-8 hour")); // 上线第一天，跑11月之后的数据
            $create_notice = '【自动请款】';
            $source = SOURCE_NETWORK_ORDER;
            $is_drawback = PURCHASE_IS_DRAWBACK_N;
            $order_status = [PURCHASE_ORDER_STATUS_ALL_ARRIVED, PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE]; // 全部到货和部分到货不等待剩余
            $pay_status = PAY_UNPAID_STATUS;
            $order_status = implode(",", $order_status);
            $querySql = "SELECT `ppo`.`purchase_number`,ppo.buyer_id,ppo.buyer_name
                      FROM pur_purchase_order AS ppo
                      INNER JOIN pur_purchase_order_pay_type AS ppy ON ppo.purchase_number=ppy.purchase_number"
                ." WHERE ppo.pay_status={$pay_status}" // 未申请付款
                ." AND ppo.source={$source}" // 网采
                ." AND ppo.is_drawback={$is_drawback}" // 是否退税
                ." AND ppo.purchase_order_status in ({$order_status})"
                ." AND ppo.account_type in (20, 34)" // 1688账期
                ." AND ppy.purchase_acccount LIKE 'yibaisuperbuyers%'" // 1688账号
                ." AND ppo.create_time >= '{$time}'"
                ." GROUP BY `ppo`.`purchase_number` limit 1500";

            $purchase_number_list  = $this->get_data($querySql);
            $this->add_data_to_cache($purchase_number_list);
            $this->get_and_send_payout($create_notice, 'admin');
        }catch (Exception $e){}


        // 内嵌计划任务，不新增
        $this->statement_order_auto_payout();
    }

    /**
     * 对账单自动请款计划任务
     * purchase_auto_payout_plan/statement_order_auto_payout
     * @author Jolon
     * @throws Exception
     */
    public function statement_order_auto_payout(){
        $this->load->model('statement/Purchase_statement_model');
        $this->load->model('supplier/Supplier_payment_info_model');
        $this->load->model('finance/purchase_order_pay_model');
        $this->load->model('Calc_pay_time_model');

        $statement_number =  $this->input->post('statement_number');// 对账单号
        $set = $this->Calc_pay_time_model->getSetParamData('PURCHASE_ORDER_RECORD_SET');

        echo "Starting auto payout...<br/>";

        // 查找目标数据
        $querySql = "SELECT PS.statement_number,PS.status,PS.accout_period_time,PS.statement_pdf_status,PS.instock_month,
            PS.total_instock_price,PS.total_pay_price,PS.total_freight,PS.total_discount,PS.total_process_cost,PS.total_loss_product_money,
            PS.settlement_method,PS.pay_type,PS.is_drawback,
            PS.statement_pdf_file_name,PS.statement_pdf_url,PS.attachmentPathEsign,
            SP.supplier_name,PS.statement_user_id,PS.statement_user_name
        FROM pur_purchase_statement AS PS
        INNER JOIN pur_supplier AS SP ON PS.supplier_code=SP.supplier_code
        WHERE PS.status IN(".STATEMENT_STATUS_SIGN_OFFLINE.",".STATEMENT_STATUS_SIGN_ONLINE.")
        AND PS.is_purchasing=1
        AND SP.is_postage=1
        AND PS.instock_month != '0000-00-00' 
        AND PS.settlement_method > 0 
        AND PS.statement_pay_status=".PAY_UNPAID_STATUS;

        if($statement_number) $querySql .= " AND PS.statement_number='$statement_number'";

        $statementOrderList  = $this->get_data($querySql);
        if(empty($statementOrderList)){
            exit('success,not found records');
        }

        foreach($statementOrderList as $statementOrder){
            try{
                // 判断是否到达自动请款时间，参考 对账单自动请款配置
                $return = $this->Calc_pay_time_model->calc_record($set,$statementOrder['settlement_method'],$statementOrder['instock_month']);
                if($return['code'] === false){// 配置错误
                    throw new Exception($return['msg']);
                }
                if($return['data'] !== null and strtotime($return['data']) > time()){// 时候未到
                    throw new Exception('还未到达自动请款时间，自动请款时间为：'.$return['data'].'<br/>');
                }
                if(empty($statementOrder['statement_pdf_url']) or empty($statementOrder['statement_pdf_file_name'])){
                    throw new Exception('对账单签署文件还未获取成功，请稍等<br/>');
                }


                // 开始请款-Starting
                // 特别注意，创建请款单方法不再验证数据，无比保证提交的数据准确
                // 请款金额汇总
                $post_data                       = [];
                $post_data['freight_desc']       = '';// 运费说明
                $post_data['compact_url'][]      = [// 上传合同上传扫描件
                    'file_name' => $statementOrder['statement_pdf_file_name'],
                    'file_path' => $statementOrder['statement_pdf_url'],
                ];
                if($statementOrder['attachmentPathEsign']){
                    $post_data['compact_url'][] = [
                        'file_name' => $statementOrder['statement_number'].'运费明细.pdf',
                        'file_path' => $statementOrder['attachmentPathEsign']
                    ];
                }

                $post_data['loss_product_money'] = $statementOrder['total_loss_product_money'];
                $post_data['product_money']      = $statementOrder['total_instock_price'] + $statementOrder['total_loss_product_money'];
                $post_data['freight']            = $statementOrder['total_freight'];
                $post_data['discount']           = $statementOrder['total_discount'];
                $post_data['process_cost']       = $statementOrder['total_process_cost'];
                $post_data['commission']         = 0;
                $post_data['commission_percent'] = 0;// 代采佣金占比
                $post_data['pay_type']           = $statementOrder['pay_type'];// 支付方式
                $post_data['requisition_method'] = '0';// 请款方式
                $post_data['pay_ratio']          = '100%';// 请款比例
                $post_data['is_drawback']        = $statementOrder['is_drawback'];// 是否退税
                $post_data['purchase_account']   = '';
                $post_data['pai_number']         = '';
                // 实际应付款总金额
                $post_data['pay_price']          = format_price($post_data['product_money']
                    + $statementOrder['total_freight']
                    + $statementOrder['total_process_cost']
                    - $statementOrder['total_discount']);


                $statementInfo = $this->Purchase_statement_model->get_statement($statementOrder['statement_number']);

                // 付款申请书数据
                $avg_po_sku_price_list  = [];// SKU的请款金额 汇总
                foreach($statementInfo['items_list'] as $item_value){
                    $purchase_number = $item_value['purchase_number'];
                    $sku             = $item_value['sku'];
                    // 按 入库金额分摊
                    if(isset($avg_po_sku_price_list[$purchase_number][$sku])){
                        $avg_po_sku_price_list[$purchase_number][$sku]['item_product_money'] += $item_value['pay_product_money'];
                    }else{
                        $avg_po_sku_price_list[$purchase_number][$sku]['item_product_money'] = $item_value['pay_product_money'];
                    }
                }


                $pay_items_list = [];
                foreach($statementInfo['summary_list'] as $summ_value){
                    $purchase_number = $summ_value['purchase_number'];

                    // SKU请款金额累加请款报损金额
                    $loss_product_money_sku_detail = json_decode($summ_value['loss_product_money_sku_detail'],true);
                    foreach($loss_product_money_sku_detail as $loss_sku_value){
                        $sku             = $loss_sku_value['sku'];
                        if(isset($avg_po_sku_price_list[$purchase_number][$sku])){
                            $avg_po_sku_price_list[$purchase_number][$sku]['item_product_money'] += $loss_sku_value['loss_product_money'];
                        }else{
                            $avg_po_sku_price_list[$purchase_number][$sku]['item_product_money'] = $loss_sku_value['loss_product_money'];
                        }
                    }

                    $pay_items_tmp['product_money'] = $summ_value['instock_price_after_charge_against'] + $summ_value['loss_product_money'];
                    $pay_items_tmp['freight']       = $summ_value['order_freight'];
                    $pay_items_tmp['discount']      = $summ_value['order_discount'];
                    $pay_items_tmp['process_cost']  = $summ_value['order_process_cost'];
                    $pay_items_tmp['commission']    = 0;
                    $pay_items_tmp['pay_price']     = format_two_point_price($pay_items_tmp['product_money'] + $pay_items_tmp['freight'] - $pay_items_tmp['discount'] + $pay_items_tmp['process_cost']);
                    $pay_items_tmp['loss_product_money'] = $summ_value['loss_product_money'];
                    $pay_items_list[$purchase_number] = $pay_items_tmp;
                }

                // 请款备注
                $total_pay_total    = array_sum(array_column($pay_items_list,'pay_price'));
                $total_freight      = array_sum(array_column($pay_items_list,'freight'));
                $total_discount     = array_sum(array_column($pay_items_list,'discount'));
                $create_notice      = '申请支付'.date('Y').'年'.date('n',strtotime($statementInfo['instock_month'])).'月货款'.$total_pay_total.'元%s；供应商承担手续费；结算方式：'.$statementInfo['settlement_method_cn'];
                if($total_freight > 0 or $total_discount > 0){
                    $sub_notice = [];
                    if($total_freight > 0) $sub_notice[] = '含加工费'.$total_freight.'元';
                    if($total_discount > 0) $sub_notice[] = '已扣除优惠金额是'.$total_discount.'元';
                    $create_notice = sprintf($create_notice,'（'.implode(',',$sub_notice).'）');
                }else{
                    $create_notice = sprintf($create_notice,'');
                }


                $post_data['applicant']         = $statementOrder['statement_user_id'];// 申请人=对账人
                $post_data['application_time']  = date('Y-m-d H:i:s');// 请款备注
                $post_data['create_user_name']  = 'admin';// 创建人
                $post_data['create_notice']  = $create_notice;// 请款备注
                $post_data['pay_items_list'] = json_encode($pay_items_list);// 采购单维度运费、优惠额、请款总额
                $post_data['item_data_list'] = json_encode($avg_po_sku_price_list);// 备货单（PO+SKU）维度运费、优惠额、请款总额
                $post_data['is_auto']        = 1;//自动请款


                // 付款申请书 数据
                $company_info = compactCompanyInfo($statementInfo['purchase_name']);
                if(empty($company_info)) throw new Exception('获取【付款申请书】采购主体失败');

                $supplier_account_info = $this->Supplier_payment_info_model->check_payment_info($statementInfo['supplier_code'], $statementInfo['is_drawback'], $statementInfo['purchase_type_id']); //支付方式:1.支付宝,2.对公支付，3.对私支付
                if (empty($supplier_account_info)) {
                    throw new Exception($post_data['pay_type']. '-收款账号失败');
                }
                if (empty($supplier_account_info['payment_platform_branch']) or empty($supplier_account_info['account'])) {
                    throw new Exception($post_data['pay_type']. '-收款账号信息缺失');
                }

                if(in_array($post_data['pay_type'],[PURCHASE_PAY_TYPE_PRIVATE])){// 线下境外
                    $receive_unit = $statementOrder['supplier_name']."(".$supplier_account_info['account_name'].")";
                }else{// 支付宝、 线下境内
                    $receive_unit = $statementOrder['supplier_name'];
                }

                $abstractRemark = abstractRemarkTemplate(SOURCE_SUBJECT_COMPACT_ORDER,$post_data['product_money'],$post_data['product_money'],0);
                $from_data = [
                    'statement_number'        => $statementOrder['statement_number'],
                    'invoice_looked_up'       => $company_info['name'],// 抬头
                    'receive_unit'            => $receive_unit,// 收款单位
                    'payment_platform_branch' => $supplier_account_info['payment_platform_branch'],// 开户行
                    'account'                 => $supplier_account_info['account'],// 账号
                    'pay_date'                => date('Y年m月d日'),
                    'pay_price'               => format_two_point_price($post_data['pay_price']),
                    'pay_price_cn'            => numberPriceToCname($post_data['pay_price']),
                    'check_department'        => '总经办',
                    'abstract_remark'         => $abstractRemark,// 请款摘要
                ];

                $data                                = [];
                $data['post_data']                   = $post_data;// 请款数据
                $data['from_data']                   = $from_data;// 付款申请书数据
                $data['statement_number']            = $statementOrder['statement_number'];
                $data['purchase_name']               = $statementInfo['purchase_name'];
                $data['from_data']['payment_reason'] = $post_data['create_notice'];


                $result = $this->purchase_order_pay_model->statement_pay_order_create($data);
                if($result['code']){
                    echo $statementOrder['statement_number'].' 自动请款成功<br/>';
                }else{
                    throw new Exception($result['msg']);
                }

                // 请款结束-Ending
            }catch (Exception $e){
                echo $statementOrder['statement_number'].' '.$e->getMessage().'<br/>';
            }
        }

        exit('success!!!');
    }

}