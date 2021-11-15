<?php
/**
 * 冲销控制器
 * User: Jolon
 * Date: 2020/04/14 10:00
 */

class Charge_against extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('statement/Charge_against_records_model');
        $this->load->model('statement/Charge_against_surplus_model');
        $this->load->library('Export');
    }

    /**
     * 其他冲销审核列表
     * /statement/Charge_against/get_charge_against_list
     */
    public function get_charge_against_list()
    {
        $params = $this->_get_charge_against_list_params();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) OR $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data = $this->Charge_against_records_model->get_charge_against_list($params, $offsets, $limit, $page);
        $this->success_json($data);
    }

    /**
     * 其他冲销审核列表导出
     * /statement/Charge_against/charge_against_list_export
     */
    public function charge_against_list_export()
    {
        $ids = $this->input->get_post('ids');                     //选择多条数据导出时，id用逗号分隔（1,2,3,4）
        $params = array();
        if (!empty($ids)) {
            $params['ids'] = array_filter(explode(',', $ids));
        } else {
            $params = $this->_get_charge_against_list_params();
        }
        //获取记录条数
        $page = 1;
        $limit = 1;
        $offsets = ($page - 1) * $limit;
        $data = $this->Charge_against_records_model->get_charge_against_list($params, $offsets, $limit, $page, true);
        $total = $data['total'];

        //文件名
        $file_name = 'Charge_against_records_' . date('YmdHis');

        //表头字段
        $columns = array(
            'charge_against_number' => '申请编码', 'record_number_relate' => '关联的采购单号', 'record_number' => '关联的取消未到货编码', 'purchase_number' => '采购单号',
            'record_type_cn' => '冲销类型', 'charge_against_amount_total' => '冲销金额-总额', 'charge_against_product' => '冲销金额-商品额',
            'charge_against_process_cost' => '冲销金额-加工费', 'charge_against_status_cn' => '审核状态', 'purchase_name' => '采购主体', 'is_drawback_cn' => '是否退税',
            'supplier_name' => '供应商名称', 'supplier_code' => '供应商编码', 'source_cn' => '采购来源', 'create_user_name' => '申请人', 'create_time' => '申请时间',
            'create_notice' => '申请备注', 'audit_user_name' => '审核人', 'audit_time' => '审核（驳回）时间', 'audit_remark' => '审核备注'
        );
        //创建导出类对象
        $my_export = new Export();
        $down_path = $my_export->ExportCsv($file_name, $total, $columns, $params, $this->Charge_against_records_model, 'get_charge_against_list');

        $this->success_json($down_path);
    }

    /**
     * 其他冲销审核列表和导出查询参数
     * @return array|mixed|string
     */
    private function _get_charge_against_list_params()
    {
        return [
            'supplier_code' => $this->input->get_post('supplier_code'),                                       //供应商编码
            'record_number' => $this->input->get_post('cancel_number'),                                       //取消未到货编码
            'charge_against_number' => $this->input->get_post('charge_against_number'),                       //冲销编码
            'purchase_number_relate' => $this->input->get_post('purchase_number_relate'),                     //关联的采购单号
            'purchase_number' => $this->input->get_post('purchase_number'),                                   //采购单号
            'apply_user_id' => $this->input->get_post('apply_user_id'),                                       //申请人
            'apply_date_start' => $this->input->get_post('apply_date_start'),                                 //申请时间开始
            'apply_date_end' => $this->input->get_post('apply_date_end'),                                     //申请时间结束
            'audit_status' => $this->input->get_post('audit_status'),                                         //审核状态
            'audit_date_start' => $this->input->get_post('audit_date_start'),                                 //审核时间开始
            'audit_date_end' => $this->input->get_post('audit_date_end'),                                     //审核时间结束
            'source' => $this->input->get_post('source'),                                                     //采购来源（1-合同，2-网采）
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
        ];
    }

    /**
     * 【冲销入库明细】采购单 与 入库批次进行冲销 - 自动冲销
     */
    public function inventory_item_charge_against()
    {
        $in_stock_batch_list = $this->input->get_post('in_stock_batch_list');

        if(empty($in_stock_batch_list)){
            $this->load->model('Purchase_inventory_items_model', 'inventory_items_model');
            $params = gp();
            $params['charge_against_status'] = [1,2];
            $data = $this->inventory_items_model->get_data_list($params, 0, 1000, 1);
            if(empty($data['values'])){
                $this->error_json('没有待冲销的数据');
            }
            $inventory_items_list = array_column($data['values'],'purchase_number','instock_batch');
            $in_stock_batch_list = [];
            foreach($inventory_items_list as $instock_batch => $purchase_number){
                $in_stock_batch_list[] = $purchase_number."_".$instock_batch;
            }
            if(empty($in_stock_batch_list)){
                $this->error_json('没有待冲销的数据');
            }
        }

        //        print_r($in_stock_batch_list);exit;
        // 根据入库批次号冲销
        $result = $this->Charge_against_records_model->check_inventory_item_charge_against($in_stock_batch_list);

        $this->success_json($result['data'], null, $result['message']);
    }

    /**
     * 【冲销退款】采购单 与 取消未到货退款 进行冲销 - 显示
     * /statement/Charge_against/refund_charge_against_view
     */
    public function refund_charge_against_view()
    {
        $purchase_number = $this->input->get_post('purchase_number');//采购单号
        if (empty($purchase_number)) {
            $this->error_json('采购单号不能为空');
        }
        $result = $this->Charge_against_records_model->refund_charge_against_view($purchase_number);
        if ($result['status']) {
            $this->success_json($result['data']);
        } else {
            $this->error_json($result['msg']);
        }
    }

    /**
     * 【冲销退款】获取采购单剩余可冲销商品金额
     * /statement/Charge_against/get_able_ca_amount
     */
    public function get_able_ca_amount()
    {
        $purchase_number = $this->input->get_post('purchase_number');              //接收抵冲的采购单号
        $purchase_number_relate = $this->input->get_post('purchase_number_relate');//关联的采购单号
        if (empty($purchase_number) OR empty($purchase_number_relate)) {
            $this->error_json('参数缺失');
        }
        $result = $this->Charge_against_records_model->get_able_ca_amount_check($purchase_number, $purchase_number_relate);
        if ($result['status']) {
            $this->success_json($result['data']);
        } else {
            $this->error_json($result['msg']);
        }
    }

    /**
     * 【冲销退款】采购单 与 取消未到货退款 进行冲销 - 保存
     * /statement/Charge_against/refund_charge_against_save
     */
    public function refund_charge_against_save()
    {
        $purchase_number_relate = $this->input->get_post('purchase_number_relate');          //冲销关联的采购单号
        $cancel_number = $this->input->get_post('cancel_number');                            //冲销关联的取消未到货编码
        $purchase_number = $this->input->get_post('purchase_number');                        //接收抵冲的采购单号
        $charge_against_product = $this->input->get_post('charge_against_product');          // 抵冲商品额
        $charge_against_process_cost = $this->input->get_post('charge_against_process_cost');// 抵冲加工费
        $create_notice = $this->input->get_post('create_notice');                            // 申请备注
        //验证必填参数
        if (empty($purchase_number_relate)) {
            $this->error_json('采购单号不能为空');
        } elseif (empty($cancel_number)) {
            $this->error_json('关联的取消编码不能为空');
        } elseif (empty($purchase_number)) {
            $this->error_json('接收抵冲的采购单号');
        } elseif (!is_numeric($charge_against_product)) {
            $this->error_json('请正确填写抵冲商品额');
        } elseif (!is_numeric($charge_against_process_cost)) {
            $this->error_json('请正确填写抵冲加工费');
        }

        $apply_data = [
            'charge_against_product' => $charge_against_product,
            'charge_against_process_cost' => $charge_against_process_cost,
            'create_notice' => $create_notice
        ];

        //保存冲销申请
        $result = $this->Charge_against_records_model->refund_charge_against_save($purchase_number_relate, $cancel_number, $purchase_number, $apply_data);

        if ($result['status']) {
            $this->success_json([], null, '操作成功');
        } else {
            $this->error_json($result['msg']);
        }
    }

    /**
     * 采购经理审核 - 预览 & 提交
     * 财务经理审核 - 预览 & 提交
     * /statement/Charge_against/charge_against_audit
     */
    public function charge_against_audit()
    {
        $charge_against_id = $this->input->get_post('id');     //*冲销记录ID
        $audit_status = $this->input->get_post('audit_status');//审核状态：agree.同意，disagree.不同意
        $audit_remark = $this->input->get_post('audit_remark');//审核备注
        $type = $this->input->get_post('type');                //*审核类型（purchase-采购经理审核，finance-财务经理审核）


        if (empty($charge_against_id)) {
            $this->error_json('参数 id 缺失');
        }

        // 根据ID 获取冲销记录
        $records = $this->Charge_against_records_model->get_charge_against_records(['id' => $charge_against_id]);
        if (empty($records)) {
            $this->error_json('未找到对应的冲销记录，请核实');
        } else {
            $records = current($records);// 获取第一条数据
        }

        // 验证 是否是 待采购经理审核 状态
        if ('purchase' == $type && $records['charge_against_status'] != CHARGE_AGAINST_STATUE_WAITING_AUDIT) {
            $this->error_json('冲销记录不处于待采购经理审核状态');
        }elseif (  'finance' ==$type && $records['charge_against_status'] != CHARGE_AGAINST_STATUE_WAITING_FINANCE_AUDIT) {// 验证 是否是 待财务经理审核 状态
            $this->error_json('冲销记录不处于待财务经理审核状态');
        }
        // 验证 是否是 退款冲销
        if ($records['record_type'] != 2) {// 2.退款冲销
            $this->error_json('冲销记录不是退款冲销类型');
        }

        if (!empty($audit_status)) {// 更新审核状态
            if (!in_array($audit_status, ['agree', 'disagree'])) {
                $this->error_json('审核状态非法');
            } elseif ('disagree' == $audit_status && empty($audit_remark)) {
                $this->error_json('驳回时，审核备注不能为空');
            } elseif (!in_array($type, ['purchase', 'finance'])) {
                $this->error_json('审核类型非法');
            }

            //判断审核类型
            if ('purchase' == $type) {
                $operation_type = '采购经理';
            } else {
                $operation_type = '财务经理';
            }
            //判断审核状态
            if ('agree' == $audit_status) {
                //采购经理审核通过
                if ('purchase' == $type) {
                    $audit_status = 20;//20.待财务经理审核
                } else {
                    //财务经理审核通过
                    $audit_status = 100;//100.审核通过
                }
                $operation_type .= '审核通过';
            } else {
                $audit_status = 30;//30.审核驳回
                $operation_type .= '驳回';
            }
            //判断是否为财务经理审核通过
            $finance_agree = ('finance' == $type && 100 == $audit_status) ? 1 : 0;
            //审核相关参数
            $params = [
                'id' => $charge_against_id,
                'charge_against_status' => $audit_status,
                'audit_remark' => $audit_remark,
                'operation_type' => $operation_type,
                'finance_agree' => $finance_agree,
                'cancel_number' => $records['record_number'],
                'purchase_number' => $records['purchase_number'],
                'charge_against_product' => $records['charge_against_product'],
                'charge_against_process_cost' => $records['charge_against_process_cost'],
                'record_number_relate' => $records['record_number_relate']
            ];
            $res = $this->Charge_against_records_model->charge_against_audit($params);
            // 返回操作结果
            if ($res['flag']) {

                if($audit_status == 'disagree'){

                    $this->load->model('Message_model');
                    $this->Message_model->AcceptMessage('abnormal',['data'=>[$charge_against_id],'message'=>$audit_remark,'user'=>getActiveUserName(),'type'=>$operation_type]);
                }
                $this->success_json([], null, '操作成功');
            } else {
                $this->error_json($res['msg']);
            }
        } else {// 审核 预览数据
            $data = $this->Charge_against_records_model->get_cancel_charge_against_data($charge_against_id);
            if ($data['flag']) {
                $this->success_json($data['data']);
            } else {
                $this->error_json($data['msg']);
            }
        }
    }

    /**
     * 查看冲销 - （与 采购单冲销汇总表 数据 对应）
     * /statement/Charge_against/view_charge_against
     */
    public function view_charge_against()
    {
        $purchase_number = $this->input->get_post('record_number_relate');
        $charge_against_surplus = $this->Charge_against_surplus_model->get_summary_data_row($purchase_number);
        $this->success_json($charge_against_surplus);
    }

    /**
     * 查看冲销操作日志
     * /statement/Charge_against/view_charge_against_logs
     */
    public function view_charge_against_logs()
    {
        $charge_against_id = $this->input->get_post('id');//冲销记录ID
        $this->load->model('statement/Purchase_statement_note_model');
        // 查找日志（3-记录日志类型：退款冲销记录新建、审核、驳回等）
        $logs = $this->Purchase_statement_note_model->get_remark_list($charge_against_id, 3);
        $this->success_json($logs);
    }

    /**
     * 采购单冲销汇总表
     * /statement/Charge_against/get_summary_data_list
     */
    public function get_summary_data_list()
    {
        $params = $this->_get_params();
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) OR $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;


        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }

        $data = $this->Charge_against_surplus_model->get_summary_data_list($params, $offsets, $limit, $page);
        $this->success_json_format($data);
    }

    /**
     * 采购单冲销汇总表导出
     * /statement/Charge_against/data_list_export
     */
    public function data_list_export()
    {
        $ids = $this->input->get_post('ids');                     //选择多条数据导出时，id用逗号分隔（1,2,3,4）
        $export_type = $this->input->get_post('export_type');     //导出类型（csv-csv格式,excel-excel格式）
        $params = array();
        if (!empty($ids)) {
            $params['ids'] = array_filter(explode(',', $ids));
            $params['source'] = empty($params['source']) ? 1 : $params['source'];//没有传值默认获取合同单的数据
        } else {
            $params = $this->_get_params();
        }

        if( isset($params['group_ids']) && !empty($params['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $params['groupdatas'] = $groupdatas;
        }



        //获取记录条数
        $page = 1;
        $limit = 1;
        $offsets = ($page - 1) * $limit;
        $data = $this->Charge_against_surplus_model->get_summary_data_list($params, $offsets, $limit, $page, true);
        $total = $data['total'];


        $this->load->model('system/Data_control_config_model');

        //文件名
        $file_name = 'Charge_against_summary_' . date('YmdHis');
        if ('excel' == $export_type) {
            //表头字段
            $columns = array(
                'rows' => array(
                    [
                        array('采购单号', ['rowspan' => 2]),
                        array('业务线', ['rowspan' => 2]),
                        array('对账单号', ['rowspan' => 2]),
                        array('审核时间', ['rowspan' => 2]),
                        array('采购来源', ['rowspan' => 2]),
                        array('合同号', ['rowspan' => 2]),
                        array('订单状态', ['rowspan' => 2]),
                        array('付款状态', ['rowspan' => 2]),
                        array('取消状态', ['rowspan' => 2]),
                        array('报损状态', ['rowspan' => 2]),
                        array('供应商名称', ['rowspan' => 2]),
                        array('供应商编码', ['rowspan' => 2]),
                        array('采购金额', ['colspan' => 5, 'bgcolor' => '#8BC34A']),
                        array('入库金额', ['rowspan' => 2]),
                        array('已付金额', ['colspan' => 5, 'bgcolor' => '#8BC34A']),
                        array('取消金额', ['colspan' => 5]),
                        array('退款金额', ['rowspan' => 2, 'bgcolor' => '#8BC34A']),
                        array('报损金额', ['colspan' => 5]),
                        array('入库金额冲销后余额', ['rowspan' => 2, 'bgcolor' => '#8BC34A']),
                        array('采购金额冲销后余额', ['rowspan' => 2]),
                        array('采购员', ['rowspan' => 2]),
                        array('下单时间', ['rowspan' => 2]),
                        array('冲销完结时间', ['rowspan' => 2]),
                        array('是否完结', ['rowspan' => 2])
                    ],
                    [
                        array('总额', ['bgcolor' => '#8BC34A']), array('商品额', ['bgcolor' => '#8BC34A']), array('运费', ['bgcolor' => '#8BC34A']), array('加工费', ['bgcolor' => '#8BC34A']), array('优惠额', ['bgcolor' => '#8BC34A']),
                        array('总额', ['bgcolor' => '#8BC34A']), array('商品额', ['bgcolor' => '#8BC34A']), array('运费', ['bgcolor' => '#8BC34A']), array('加工费', ['bgcolor' => '#8BC34A']), array('优惠额', ['bgcolor' => '#8BC34A']),
                        array('总额'), array('商品额'), array('运费'), array('加工费'), array('优惠额'),
                        array('总额'), array('商品额'), array('运费'), array('加工费'), array('优惠额')
                    ],
                ),
                'keys' => array(
                    'purchase_number', 'purchase_type_cn','statement_number','audit_time', 'source_cn', 'compact_number', 'purchase_order_status_cn', 'pay_status_cn',
                    'cancel_status_cn', 'report_loss_status_cn', 'supplier_name', 'supplier_code',
                    'real_price', 'product_money', 'freight', 'process_cost', 'discount', 'total_instock_price',
                    'paid_real_price', 'paid_product_money', 'paid_freight', 'paid_process_cost', 'paid_discount',
                    'cancel_real_price', 'cancel_product_money', 'cancel_freight', 'cancel_process_cost', 'cancel_discount',
                    'real_refund_amount', 'loss_real_price', 'loss_product_money', 'loss_freight', 'loss_process_cost',
                    'loss_discount', 'instock_price_after_charge_against', 'real_price_after_charge_against', 'buyer_name',
                    'waiting_time', 'finished_time', 'finished_cn')
            );
            //创建导出类对象
            $my_export = new Export();
            $down_path = $my_export->ExportExcel($file_name, $total, $columns, $params, $this->Charge_against_surplus_model, 'get_summary_data_list',[]);
        } else {

            try {
                $user_id = jurisdiction(); //当前登录用户ID
                $user_groups_types = user_group_check(); //当前登录用户ID的权限所属的组
                $role_name = get_user_role();//当前登录角色
                $data_role = getRole();
                $res_arr = array_intersect($role_name, $data_role);
                $authorization_user = [];
                if (!(!empty($res_arr) or $user_id === true)) {
                    $params['swoole_userid'] = $user_id;//数据权限采购员id集合
                }
                if( isset($params['group_ids']) && !empty($params['group_ids'])){

                    $this->load->model('user/User_group_model', 'User_group_model');
                    $groupids = $this->User_group_model->getGroupPersonData($params['group_ids']);
                    $groupdatas = [];
                    if(!empty($groupids)){
                        $groupdatas = array_column($groupids,'value');
                    }

                    $params['groupdatas'] = $groupdatas;
                }
                $params['user_groups_types'] = $user_groups_types;

                $result = $this->Data_control_config_model->insertDownData($params, 'CHARGE_AGAINST', '采购单冲销汇总表', getActiveUserName(), 'csv', $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json("添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }

            die();
            //表头字段
            $columns = array(
                'purchase_number' => '采购单号', 'purchase_type_cn' => '业务线','statement_number' => '对账单号','audit_time' => '审核时间', 'source_cn' => '采购来源', 'compact_number' => '合同号', 'purchase_order_status_cn' => '订单状态', 'pay_status_cn' => '付款状态',
                'cancel_status_cn' => '取消状态', 'report_loss_status_cn' => '报损状态', 'supplier_name' => '供应商名称', 'supplier_code' => '供应商编码',
                'real_price' => '采购金额-总额', 'product_money' => '采购金额-商品总额', 'freight' => '采购金额-运费', 'process_cost' => '采购金额-加工费', 'discount' => '采购金额-优惠额', 'total_instock_price' => '入库金额',
                'paid_real_price' => '已付款金额-总额', 'paid_product_money' => '已付款金额-商品总额', 'paid_freight' => '已付款金额-运费', 'paid_process_cost' => '已付款金额-加工费', 'paid_discount' => '已付款金额-优惠额',
                'cancel_real_price' => '取消金额-总额', 'cancel_product_money' => '取消金额-商品总额', 'cancel_freight' => '取消金额-运费', 'cancel_process_cost' => '取消金额-加工费', 'cancel_discount' => '取消金额-优惠额',
                'real_refund_amount' => '退款金额', 'loss_real_price' => '报损金额-总额', 'loss_product_money' => '报损金额-商品总额', 'loss_freight' => '报损金额-运费', 'loss_process_cost' => '报损金额-加工费',
                'loss_discount' => '报损金额-优惠额', 'instock_price_after_charge_against' => '入库金额冲销后余额', 'real_price_after_charge_against' => '采购金额冲销后余额', 'buyer_name' => '采购员',
                'waiting_time' => '下单时间', 'finished_time' => '冲销完结时间', 'finished_cn' => '是否完结'
            );
            //创建导出类对象
            $my_export = new Export();
            $down_path = $my_export->ExportCsv($file_name, $total, $columns, $params, $this->Charge_against_surplus_model, 'get_summary_data_list');
        }
        $this->success_json($down_path);
    }

    /**
     * 列表和导出查询参数
     * @return array|mixed|string
     */
    private function _get_params()
    {
        $params = [
            'purchase_number' => $this->input->get_post('purchase_number'),                   //采购单号
            'compact_number' => $this->input->get_post('compact_number'),                     //合同号
            'statement_number' => $this->input->get_post('statement_number'),                 //对账单号
            'orders_date_start' => $this->input->get_post('orders_date_start'),               //下单时间开始
            'orders_date_end' => $this->input->get_post('orders_date_end'),                   //下单时间结束
            'finished' => $this->input->get_post('finished'),                                 //是否完结（1-未完结，2-完结）
            'finish_date_start' => $this->input->get_post('finish_date_start'),               //冲账完结时间开始
            'finish_date_end' => $this->input->get_post('finish_date_end'),                   //冲账完结时间结束
            'supplier_code' => $this->input->get_post('supplier_code'),                       //供应商编码
            'buyer_id' => $this->input->get_post('buyer_id'),                                 //采购员
            'purchase_type_id' => $this->input->get_post('purchase_type_id'),                 //业务线
            'source' => $this->input->get_post('source'),                                     //采购来源（1-合同，2-网采）
            'purchase_order_status' => $this->input->get_post('purchase_order_status'),       //采购单状态
            'pay_status' => $this->input->get_post('pay_status'),                             //付款状态
            'cancel_audit_status' => $this->input->get_post('cancel_audit_status'),           //取消未到货状态
            'audit_time_start' => $this->input->get_post('audit_time_start'),                 //审核时间开始
            'audit_time_end' => $this->input->get_post('audit_time_end'),                     //审核时间结束
            'finish_type' => $this->input->get_post('finish_type'),                           //冲账完结类型选择
            'after_surplus_type' => $this->input->get_post('after_surplus_type'),                 //冲销后余额类型选择
            'after_surplus_type_comparator' => $this->input->get_post('after_surplus_type_comparator'),     //冲销后余额类型选择-比较器
            'product_finished_time_start' => $this->input->get_post('product_finished_time_start'),         //商品金额冲账完结时间开始
            'product_finished_time_end' => $this->input->get_post('product_finished_time_end'),             //商品金额冲账完结结束
            'group_ids'                 => $this->input->get_post('group_ids'), // 组别ID
        ];
        $params['source'] = empty($params['source']) ? 1 : $params['source'];//没有传值默认获取合同单的数据
        return $params;
    }


}