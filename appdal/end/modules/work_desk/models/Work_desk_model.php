<?php


class Work_desk_model extends Purchase_model
{
    //region 表名变量设定
    protected $table_purchase_suggest = 'purchase_suggest';
    protected $table_purchase_suggest_map ='purchase_suggest_map';
    protected $table_purchase_order = 'purchase_order';
    protected $table_purchase_order_items = 'purchase_order_items';
    protected $table_order_pay_type = 'purchase_order_pay_type';
    protected $table_supplier_settlement = 'supplier_settlement';
    protected $table_supplier = 'supplier';
    protected $table_statement_warehouse_results = 'statement_warehouse_results';
    protected $table_buyer_performance = 'purchase_sales_performance';
    protected $table_user_info = 'purchase_user_info';
    protected $table_warehouse_abnormal='purchase_warehouse_abnormal';
    protected $table_purchase_progress='purchase_progress';
    protected $table_logistics_info='purchase_logistics_info';
    protected $table_product_scree = 'product_scree';
    protected $table_product_update_log = 'product_update_log';
    protected $table_warehouse_results = 'warehouse_results';

    private $_no_data_msg = '近10分钟内数据已更新，请稍后再操作';
    private $_need_permission = true; //true-需要权限控制（默认），false-不需要权限控制（计划任务时设置）
    private $_authorization_user = [];//用户数据权限
    protected $table_name = 'purchase_user_relation';
    protected $table_group = 'purchase_group';
    protected $table_user_group_relation = 'purchase_user_group_relation';

    //endregion

    public function __construct()
    {
        parent::__construct();
        $this->load->library('mongo_db');
        $this->load->model('user/User_group_model', 'User_group_model');
    }

    /**
     * 更新用户权限控制，设置为 false 则不控制权限
     * @param $permission
     */
    public function set_need_permission($permission){
        $this->_need_permission = $permission;
    }

    /**
     * 获取用户权限
     */
    public function init_authorization()
    {
        //权限控制（根据当前登录用户ID获取自己及其下属的用户ID）
        $user_id = jurisdiction();
        $user_role = get_user_role();//获取登录用户角色

        //只有权限中心的admin角色的用户，才属于超级数据组
        $res_arr = in_array('admin', $user_role) ? 1 : 0;

        $this->_authorization_user =[];
        //数据权限采购员id集合
        if (!(!empty($res_arr) or $user_id === TRUE)) {
            $this->_authorization_user = $user_id;
        }
    }

    /**
     * 获取SKU 屏蔽数
     * @params $userNames  array  申请人
     * @author:luxu
     * @time:2020/10/23
     **/
    public function getConfirmData($userNames){

        $priveData = date("Y-m-d H:i:s",strtotime("+3 day"));
        $result = $this->purchase_db->from("product_scree")->where_in("apply_user",$userNames)->where("status",50)
            ->where("apply_remark",4)->where("estimate_time<=",$priveData)->where("estimate_time>=",date("Y-m-d H:i:s"))->get()->result_array();
        return count($result);
    }

    /**
     * 统计SKU 屏蔽到期数量人数
     * @params $uid  int  申请人ID
     * @author:luxu
     * @time:2020/10/23
     **/
    public function GetConfirmUser($categoryid,$groupid,$buyer_id){

        $searchData = [];
        if(!empty($buyer_id)){
            // 如果采购员ID就直接获取采购员数据
            $searchData = $this->purchase_db->from("purchase_user_info")->where_in("user_id",$buyer_id)
                ->select("user_name")->get()->result_array();
            $searchData = array_unique(array_column($searchData,"user_name"));
            return $searchData;
        }
        if( !empty($groupid)){
            // 如果传入组别信息就直接获取组别下用户ID 数据
            $query = $this->purchase_db;
            $query->select('a.user_name');
            $query->from("{$this->table_user_info} AS a");
            $query->join("{$this->table_name} AS b", 'b.user_id=a.user_id');
            $query->join("{$this->table_user_group_relation} AS c", 'c.user_map_id=b.id','LEFT');
            $query->join("{$this->table_group} AS d", 'd.id=c.group_id','LEFT');
            $result = $query->where("c.group_id",$groupid)->get()->result_array();
            $searchData = array_unique(array_column($result,"user_name"));
            return $searchData;
        }

        if(!empty($categoryid)){
            $query = $this->purchase_db;
            $query->select('a.user_name');
            $query->from("{$this->table_user_info} AS a");
            $query->join("{$this->table_name} AS b", 'b.user_id=a.user_id');
            $query->join("{$this->table_user_group_relation} AS c", 'c.user_map_id=b.id','LEFT');
            $query->join("{$this->table_group} AS d", 'd.id=c.group_id','LEFT');
            $result =  $query->where('b.category_id', $categoryid, false)->get()->result_array();
            $searchData = array_unique(array_column($result,"user_name"));
            return $searchData;
        }
    }

    //region 工作台页面数据
    /**
     * 工作台页面展示数据
     * @param $params
     * @return array
     */
    public function get_data_list($params)
    {

        $this->_insert_logs(['message'=>'程序开始MONDGDB 获取数据','start'=>date("Y-m-d H:i:s",time())]);
        //获取权限
        $authorization_user = $this->User_group_model->get_work_desk_authorization($params['category_id'], getActiveUserId());
        if (empty($authorization_user)) return [];

        //#根据查询条件过滤
        if (!empty(array_filter($params['buyer_id']))) {//如果按采购员查询
            $authorization_user = array_values(array_intersect($authorization_user, $params['buyer_id']));
        } elseif (!empty($params['group_id'])) {//如果只按小组查询，获取小组下所有采购员
            $data = $this->User_group_model->get_buyer_by_group($params['group_id']);
            if (!empty($data)) {
                $authorization_user = array_column($data,'value');
            } else {
                $authorization_user = [];
            }
        }
        //把数组中的所有元素转换成字符串
        if(!empty($authorization_user)){
            $authorization_user = ArrayElementsCTS($authorization_user);
        }

        //#模块键值对数据
        $label_data = [
            '1' => [
                'priority_processing'       => ['module_label' => '待优先处理', 'field' => ['count_num'], 'type' => ['demand_order' => '待生成备货单', 'waiting_quote_order' => '待采购询价', 'ali_overdue_order' => '1688已逾期未完结', 'pur_order_info_change_waiting_audit' => '信息修改待审核', 'pur_order_info_change_reject' => '信息修改驳回', 'ali_abnormal_amount_order' => '1688金额异常', 'unfinished_ca_instock_order' => '入库冲销未完成']],
                'unfinished_follow_up'      => ['module_label' => '未完结跟进', 'field' => ['count_num'], 'type' => ['more_than_20_days' => '超20天未完结', 'more_than_30_days' => '超30天未完结', 'more_than_40_days' => '超40天未完结', 'waiting_handle' => '异常待处理', 'in_exception_handling' => '异常处理中', 'have_express_no_track' => '有快递无轨迹', 'no_express_tracking_number' => '无快递单号']],
                'overdue_delivery'          => ['module_label' => '逾期交货', 'field' => ['count_num'], 'type' => ['more_than_3_days' => '逾期3天以上','more_than_7_days' => '逾期7天以上', 'more_than_15_days' => '逾期15天以上', 'more_than_20_days' => '逾期20天以上', 'more_than_30_days' => '逾期30天以上', 'not_confirmed' => '门户未确认', 'wait_for_shipping' => '门户待发货']],
                'request_payment_follow_up' => ['module_label' => '请款跟进', 'field' => ['count_num'], 'type' => ['no_request_payment' => '网拍未申请付款', 'request_payment_reject' => '网拍申请付款被驳回', 'account_period_request_payment' => '1688账期可请款', 'account_period_request_payment_reject' => '1688账期请款被驳回', 'contract_request_payment_reject' => '合同单请款被驳回', 'contract_paid_yesterday' => '合同单昨日已付款']],
                'refund_follow_up'          => ['module_label' => '退款跟进', 'field' => ['count_num'], 'type' => ['refund_waiting_audit' => '退款待审核', 'screenshot_waiting_upload' => '待上传截图', 'ail_screenshot_waiting_upload' => '1688已退款待上传截图', 'refund_reject' => '退款财务驳回', 'deal_cancelled' => '1688交易取消']],
                'other_pending'             => ['module_label' => '其他待处理', 'field' => ['count_num'], 'type' => ['ecn_reject' => 'ECN变更被驳回', 'sku_screen_reject' => 'SKU屏蔽申请驳回', 'sku_screen_waiting_audit' => 'SKU屏蔽申请待审核', 'sku_info_waiting_audit' => '产品信息待审核', 'sku_info_reject' => '产品信息驳回', 'supplier_info_waiting_audit' => '供应商信息修改待审核', 'supplier_info_reject' => '供应商信息修改驳回',],],
            ],
            '2' => [
                'priority_processing'       => [
                    'module_label' => '待优先处理',
                    'field'        => ['supplier_count', 'count_num'],
                    'type'         => [
                        'to_prepare_the_stock_list'                => '待生成备货单',
                        'to_purchase_inquiry_non_direct_po'        => '待采购询价-非直发po',
                        'to_purchase_inquiry_direct_po'            => '待采购询价-直发po',
                        'waiting_for_the_manager_to_review_the_po' => '待经理审核po',
                        'portal_to_confirm_delivery_sku'           => '门户待确认交期sku',
                        'waiting_to_generate_po'                   => '等待生成进货单po',
                        'same_as_not_uploaded_portal'              => '合同未上传（门户）',
                        'did_not_make_out_an_invoice'              => '未开票',
                        'inventory_write_off_is_not_completed'     => '入库冲销未完成',
                    ],
                ],
                'back_goods_follow_up'      => [
                    'module_label' => '回货跟进',
                    'field'        => ['po_count', 'sku_count', 'count_num'],
                    'type'         => [
                        'not_back_to_the_goods'               => '未回货',
                        'portal_system_is_ready_for_delivery' => '门户系统待发货',
                        'overdue_goods'                       => '逾期未回货',
                        'direct_shipment_not_returned'        => '直发未回货',
                        'no_return_over_40_days'              => '40天以上未回货',
                    ],
                ],
                'unfinished_follow_up'      => [
                    'module_label' => '未完结跟进',
                    'field'        => ['po_count', 'sku_count', 'count_num'],
                    'type'         => [
                        'unfinished'              => '未完结',
                        'over_45_days'            => '45天以上总未完结',
                        '45_60_days_not_finished' => '45-60天未完结',
                        'more_than_60_days'       => '60天以上未完结',
                    ],
                ],
                'abnormal_follow_up'        => [
                    'module_label' => '异常跟进',
                    'field'        => ['count_num'],
                    'type'         => [
                        'exception_not_handled'                    => '异常未处理',
                        'tax_refund_warehouse_not_processed'       => '退税仓未处理',
                        'transit_warehouse_not_processed'          => '中转仓未处理',
                        'amount_of_1688_is_abnormal'               => '1688金额异常',
                        '1688_closed_system_not_finished'          => '1688关闭，系统未完结',
                        'portal_system_waits_supplier_confirm_sku' => '门户系统待供应商确sku'
                    ],
                ],
                'rejected_need_deal_with'   => [
                    'module_label' => '驳回需处理',
                    'field'        => ['count_num'],
                    'type'         => [
                        'po_rejected_for_inquiry'                => '待询价被驳回po',
                        'the_po_was_rejected'                    => '请款被驳回po',
                        'ecn_changes_are_rejected'               => 'ECN变更被驳回',
                        'refund_rejected_by_financial_staff'     => '退款财务驳回',
                        'inspection_exemption_shall_be_rejected' => '验货免检驳回',
                        'supplier_modification_is_rejected'      => '供应商修改被驳回',
                        'product_management_was_rejected'        => '产品管理被驳回',
                    ],
                ],
                'pay_follow_up'             => [
                    'module_label' => '请款跟进',
                    'field'        => ['count_num'],
                    'type'         => [
                        'can_please_the_po' => '可请款po',
                        'rpc_non_account_period' => '可请款对公po-非账期',
                        'rpc_account_period' => '可请款对公po-账期',
                        'rppc_non_account_period' => '可请款对私合同po-非账期',
                        'rppc_account_period' => '可请款对私合同po-账期',
                        'po_can_be_requested' => '可请款网采po',
                        'po_was_rejected' => '请款被驳回PO',
                        'po_was_paid_yesterday' => '昨日已付款PO',
                    ],
                ],
                'refund_follow_up'          => [
                    'module_label' => '退款跟进',
                    'field'        => ['count_num'],
                    'type'         => [
                        'waiting_uploaded'               => '待上传截图',
                        'financial_rejection_po'         => '财务驳回po',
                        'financial_receivables_po'       => '待财务收款po',
                        '1688_returned_waiting_uploaded' => '1688已退，待上传截图',
                        'contract_is_subject_to_refund'  => '合同单待退款',
                    ],
                ],
                'check_goods_follow_up'     => [
                    'module_label' => '验货跟进',
                    'field'        => ['count_num'],
                    'type'         => [
                        'pending_purchase_confirmation'        => '待采购确认',
                        'pending_confirmed_by_quality_control' => '待品控确认',
                        'exempted_from_inspection_to_reject'   => '免检驳回',
                        'nonconformity_to_be_confirmed'        => '不合格待确认',
                        'inspection_is_not_up_to_standard'     => '验货不合格',
                    ],
                ],
                'product_modification'      => [
                    'module_label' => '产品修改',
                    'field'        => ['count_num'],
                    'type'         => [
                        'ecn_data_under_change'                                 => 'ECN资料变更中',
                        'sku_product_information_modification_is_not_completed' => 'SKU产品信息修改未完成',
                        'sku_shield_application_to_be_reviewed'                 => 'SKU屏蔽申请待审核',
                    ],
                ],
                'gateway_statement_invoice' => [
                    'module_label' => '门户对账发票',
                    'field'        => ['count_num'],
                    'type'         => [
                        'portal_reconciliation_has_not_been_confirmed' => '门户对账未确认',
                        'portal_invoice_has_not_been_replied'          => '门户的发票未回复',
                        'portal_invoice_has_not_been_confirmed'        => '门户的发票未确认',
                    ],
                ],
            ],
        ];

        //获取‘非海外组’所有模块的数据
        $this->mongo_db->select(['category_id', 'buyer_id', 'module', 'type', 'data', 'update_time']);
        $this->mongo_db->where(['category_id' => (string)$params['category_id']]);
        $this->mongo_db->where_in('buyer_id', $authorization_user);
        //指定模块查询
        if(!empty(array_filter($params['module']))){
            $this->mongo_db->where_in('module', $params['module']);
        }
        $res = $this->mongo_db->get('work_desk_data_summary');

        //-----------------------------获取跳转接口URL数据-----------------------------1
        //获取缓存数据
        $url_info = $this->rediss->getData('WORK_DESK_URL_INFO');
        if (empty($url_info)) {
            //获取跳转接口URL
            $url_info_tmp = $this->purchase_db->select("category_id,module,type,method,url,params")->get('pur_work_desk_url_info')->result_array();
            $url_info = [];
            if (!empty($url_info_tmp)) {
                foreach ($url_info_tmp as $item) {
                    $_idx = $item['module'] . '-' . $item['type'];
                    if (!empty($item['url'])) {
                        $url_info[$item['category_id']][$_idx]['method'] = $item['method'];
                        $url_info[$item['category_id']][$_idx]['url'] = json_decode($item['url'], TRUE);
                        $url_info[$item['category_id']][$_idx]['params'] = json_decode($item['params'], TRUE);
                    } else {
                        $url_info[$item['category_id']][$_idx] = NULL;
                    }
                }
                unset($url_info_tmp);
                //缓存数据
                $this->rediss->setData('WORK_DESK_URL_INFO', json_encode($url_info), 36000);
            }
        } else {
            $url_info = json_decode($url_info, TRUE);
        }
        //-----------------------------获取跳转接口URL数据-----------------------------0

        //临时结果数据
        $data_list_tmp = [];
        foreach ($res as $item) {
            $_idx = $item->module . '-' . $item->type;
            //累计各项指标值
            $field_arr = $label_data[$params['category_id']][$item->module]['field'];
            $data_tmp = json_decode($item->data, TRUE);
            foreach ($field_arr as $field) {
                if (isset($data_list_tmp['result'][$_idx][$field])) {
                    $data_list_tmp['result'][$_idx][$field] += isset($data_tmp[$field]) ? $data_tmp[$field] : 0;
                } else {
                    $data_list_tmp['result'][$_idx][$field] = isset($data_tmp[$field]) ? $data_tmp[$field] : 0;
                }
            }
            //取出最新更新时间
            if (!isset($data_list_tmp['update_time'][$item->module])) {
                $data_list_tmp['update_time'][$item->module] = $item->update_time;
            } elseif (strtotime($item->update_time) > strtotime($data_list_tmp['update_time'][$item->module])) {
                $data_list_tmp['update_time'][$item->module] = $item->update_time;
            }
        }
        //跳转接口URL数据
        foreach ($url_info[$params['category_id']] as $_idx => $item) {
            $data_list_tmp['url_info'][$_idx] = $item;
        }

        //最终返回结果数据
        $data_list = [];
        if (!empty(array_filter($params['module']))) {//指定模块刷新时，返回指定模块数据
            foreach ($params['module'] as $module) {
                //模块名称
                $data_list[$module]['label'] = $label_data[$params['category_id']][$module]['module_label'];
                //更新时间
                $data_list[$module]['update_time'] = isset($data_list_tmp['update_time'][$module]) ? $data_list_tmp['update_time'][$module] : '';
                //组织模块各个类型的数据
                foreach ($label_data[$params['category_id']][$module]['type'] as $type => $type_label) {
                    $_idx = $module . '-' . $type;                             //数据下标
                    $data_list[$module]['items'][$type]['label'] = $type_label;//类型中文值
                    //组织统计各个类型的结果
                    $field_arr = $label_data[$params['category_id']][$module]['field'];
                    foreach ($field_arr as $field) {
                        $data_list[$module]['items'][$type][$field] = isset($data_list_tmp['result'][$_idx][$field]) ? $data_list_tmp['result'][$_idx][$field] : 0;
                    }
                    //跳转接口URL数据
                    $data_list[$module]['items'][$type]['url_info'] = isset($data_list_tmp['url_info'][$_idx]) ? $data_list_tmp['url_info'][$_idx] : NULL;
                }
            }
        } else {
            foreach ($label_data[$params['category_id']] as $module => $item) {
                //模块名称
                $data_list[$module]['label'] = $item['module_label'];
                //更新时间
                $data_list[$module]['update_time'] = isset($data_list_tmp['update_time'][$module]) ? $data_list_tmp['update_time'][$module] : '';
                //组织模块各个类型的数据
                foreach ($item['type'] as $type => $type_label) {
                    $_idx = $module . '-' . $type;                             //数据下标
                    $data_list[$module]['items'][$type]['label'] = $type_label;//类型中文值
                    //组织统计各个类型的结果
                    $field_arr = $label_data[$params['category_id']][$module]['field'];
                    foreach ($field_arr as $field) {
                        $data_list[$module]['items'][$type][$field] = isset($data_list_tmp['result'][$_idx][$field]) ? $data_list_tmp['result'][$_idx][$field] : 0;
                    }
                    //跳转接口URL数据
                    $data_list[$module]['items'][$type]['url_info'] = isset($data_list_tmp['url_info'][$_idx]) ? $data_list_tmp['url_info'][$_idx] : NULL;
                }
            }
            //非海外仓小组，才获取智库数据

            $this->_insert_logs(['message'=>'程序结束MONDGDB 获取数据','start'=>date("Y-m-d H:i:s",time())]);

            if (1 == $params['category_id']) {
                //获取智库统计数据
                $this->_insert_logs(['message'=>'程序获取智库数据开始','start'=>date("Y-m-d H:i:s",time())]);
                $digitization_data = $this->_digitization_data_summary($params['buyer_id']);
                $this->_insert_logs(['message'=>'程序获取智库数据结束','start'=>date("Y-m-d H:i:s",time())]);
                $data_list = array_merge($data_list, $digitization_data);
            }
        }
        unset($data_list_tmp);
        return $data_list;
    }

    public function getRoleData(){


        $buyer_id = getActiveUserId();
        $power_data = $this->purchase_db->select("id,rank")
            ->where(['user_id' => $buyer_id, 'is_enable' => 1, 'is_del' => 0,'category_id'=>1])
            ->get($this->table_name)->row_array();
        if( $power_data['rank'] == 1){

            return True;
        }else{
            return False;
        }

    }


    /**
     * 获取智库统计数据
     * @return array|mixed
     */
    private function _digitization_data_summary($client_buyer_id = NULL)
    {
        //当前采购员id
        $buyer_id = getActiveUserId();
        if( !empty($client_buyer_id) && is_array($client_buyer_id) && count($client_buyer_id) == 1){

            $buyer_id = $client_buyer_id[0];
        }

        //获取缓存数据
        $result = $this->rediss->getData('DIGITIZATION_DATA_' . $buyer_id);
        $this->_insert_logs(['message'=>'程序获取智库数据REDIS数据','data'=>$result]);
        if (!empty($result)) {
            return json_decode($result, TRUE);
        }

        //#模块键值对数据
        $label_data = [
            'yesterday_sale' => ['module_label' => '昨日下单率', 'type' => ['domestic_first' => '国内仓第一名', 'group_first' => '小组内第一名', 'domestic_current_ranking' => '国内仓当前排名', 'group_current_ranking' => '小组内当前排名']],
            'achieve_7_days' => ['module_label' => '7天交期达成率', 'type' => ['domestic_first' => '国内仓第一名', 'group_first' => '小组内第一名', 'domestic_current_ranking' => '国内仓当前排名', 'group_current_ranking' => '小组内当前排名'],],
            'yesterday_sku_num' => ['module_label' => '昨日降价排名', 'type' => ['domestic_first' => '采购部第一名', 'group_first' => '小组内第一名', 'domestic_current_ranking' => '采购部当前排名', 'group_current_ranking' => '小组内当前排名'],],
            'month_sku_num' => ['module_label' => '当月降价排名', 'type' => ['domestic_first' => '采购部第一名', 'group_first' => '小组内第一名', 'domestic_current_ranking' => '采购部当前排名', 'group_current_ranking' => '小组内当前排名'],],
        ];

        //在权限配置中，获取用户所在小组的全部成员
        $buyer_ids = $this->User_group_model->get_group_members_all($buyer_id);
        $this->_insert_logs(['message'=>'程序获取智库数据小组成员数据','data'=>$buyer_ids]);
        //模块键值数据(yesterday_sale-昨日下单率,achieve_7_days-7天交期达成率,month_sku_num-当月降价排名,yesterday_sku_num-昨日降价排名)
        $module_arr = ['yesterday_sale', 'achieve_7_days', 'month_sku_num', 'yesterday_sku_num'];
        //类型键值数据（domestic_first-国内仓第一名或采购部第一名，group_first-小组内第一名，domestic_current_ranking-国内仓当前排名或采购部当前排名，group_current_ranking-小组内当前排名）
        $type_arr = ['domestic_first', 'group_first', 'domestic_current_ranking', 'group_current_ranking'];

        $data_list = [];
        //获取智库模块所有数据
        foreach ($module_arr as $module) {
            $data_list[$module]['label'] = $label_data[$module]['module_label'];//模块名称
            $data_list[$module]['update_time'] = '';                            //更新时间
            //循环统计各项指标数据
            foreach ($type_arr as $type) {
                //模块各个类型数据
                $data_list[$module]['items'][$type] = [
                    'label' => $label_data[$module]['type'][$type],
                    'ranking' => '',
                    'user_name' => '',
                    'value' => '',
                ];

                $this->purchase_db->select("a.sales_id AS user_id,b.user_name,'1' AS ranking,a.{$module} AS value,modify_time");
                $this->purchase_db->from("$this->table_buyer_performance a");
                $this->purchase_db->join("$this->table_user_info b", 'a.sales_id=b.user_id');
                $this->purchase_db->order_by("{$module}", 'DESC');
                //统计小组内第一名和小组内当前排名时，按采购员查询
                if (in_array($type, ['group_first', 'group_current_ranking'])) {
                    if(empty($buyer_ids)){
                        $buyer_ids ='';
                    }
                    $this->purchase_db->where_in('a.sales_id', $buyer_ids);
                }
                if (in_array($type, ['domestic_first', 'group_first'])) {
                    $this->purchase_db->limit(1);
                }
                //数据查询结果
                $res = $this->purchase_db->get()->result_array();
                if (!empty($res)) {
                    if (in_array($type, ['domestic_current_ranking', 'group_current_ranking'])) {
                        //计算当前采购员排名
                        foreach ($res as $key => $item) {
                            if ($buyer_id == $item['user_id']) {
                                //模块各个类型数据
                                //昨日下单率,7天交期达成率数据结果转百分号
                                if (in_array($module, ['yesterday_sale', 'achieve_7_days'])) {
                                    $scale = ('yesterday_sale' == $module) ? 2 : 6;//保留小数点位数
                                    $_value = bcmul($item['value'], 100, $scale) . '%';
                                } else {
                                    $_value = $item['value'];
                                }
                                $data_list[$module]['items'][$type] = [
                                    'label' => $label_data[$module]['type'][$type],
                                    'ranking' => $key + 1,
                                    'user_name' => $item['user_name'],
                                    'value' => $_value,
                                ];
                                //更新时间
                                if (empty($data_list[$module]['update_time'])) {
                                    $data_list[$module]['update_time'] = $item['modify_time'];
                                }
                                break;
                            }
                        }
                    } else {
                        //模块各个类型数据
                        //昨日下单率,7天交期达成率数据结果转百分号
                        if (in_array($module, ['yesterday_sale', 'achieve_7_days'])) {
                            $scale = ('yesterday_sale' == $module) ? 2 : 6;//保留小数点位数
                            $_value = bcmul($res[0]['value'], 100, $scale) . '%';
                        } else {
                            $_value = $res[0]['value'];
                        }
                        $data_list[$module]['items'][$type] = [
                            'label' => $label_data[$module]['type'][$type],
                            'ranking' => $res[0]['ranking'],
                            'user_name' => $res[0]['user_name'],
                            'value' => $_value,
                        ];
                        //更新时间
                        if (empty($data_list[$module]['update_time'])) {
                            $data_list[$module]['update_time'] = $res[0]['modify_time'];
                        }
                    }
                }
            }
        }
        //缓存数据
        $this->rediss->setData('DIGITIZATION_DATA_' . $buyer_id, json_encode($data_list));
        return $data_list;
    }
    //endregion

    //region ‘非海外’-‘待优先处理’模块
    /**
     * 统计‘非海外’-‘待优先处理’模块数据
     * @return array
     */
    public function non_oversea_priority_processing()
    {
        try {
            $category_id = DAC_GROUP_TYPE_NON_OVERSEA;//小组类型
            $module = 'priority_processing';          //模块

            //统计待生成备货单(备货单号个数)
            $res = $this->demand_order_summary($category_id,$module);
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }
            //统计待采购询价(PO数量，去重统计)
            $res = $this->waiting_quote_order_summary($category_id,$module);
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }
            //1688已逾期未完结(PO数量)
            $res = $this->ali_overdue_order_summary($category_id,$module);
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }
            //统计信息修改待审核(PO个数)
            $res = $this->pur_order_info_change_summary($category_id,$module,'waiting_audit');
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }
            //统计信息修改驳回(PO个数)
            $res = $this->pur_order_info_change_summary($category_id,$module,'reject');
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }
            //统计1688金额异常(PO个数)
            $res = $this->ali_abnormal_amount_order_summary($category_id,$module);
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }
            //统计入库冲销未完成（入库批次号个数）
            $res = $this->unfinished_ca_instock_order_summary($category_id,$module);
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }

            $return = ['flag' => TRUE, 'msg' => '非海外仓-待优先处理-数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计'非海外仓'待生成备货单(备货单号个数)
     * @param int    $category_id
     * @param string $module
     * @return array
     */
    private function demand_order_summary($category_id,$module)
    {
        try {
            //类型
            $type = 'demand_order';
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }

            //子查询sql
            $this->purchase_db->select('buyer_id')->from($this->table_purchase_suggest);
            $this->purchase_db->where('is_create_order', 0);
            $this->purchase_db->where('demand_type_id', 1);
            $this->purchase_db->where('suggest_status', SUGGEST_STATUS_NOT_FINISH);//备货单状态=未完结
            $this->purchase_db->where('audit_status', 1);
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_OVERSEA);//业务线=非海外仓
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);//业务线=非FBA大货
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('demand_number');
            $sub_query = $this->purchase_db->get_compiled_select();

            //数据查询
            $main_query = "SELECT tmp.buyer_id,COUNT(1) AS count_num FROM($sub_query) AS tmp GROUP BY tmp.buyer_id";
            $result = $this->purchase_db->query($main_query)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);
            $return = ['flag' => TRUE, 'msg' => '待生成备货单数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计'非海外仓'待采购询价(PO数量，去重统计)
     * @param int    $category_id
     * @param string $module
     * @return array
     */
    private function waiting_quote_order_summary($category_id,$module)
    {
        try {
            //类型
            $type = 'waiting_quote_order';
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //数据查询
            $this->purchase_db->select('buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->where('purchase_order_status', PURCHASE_ORDER_STATUS_WAITING_QUOTE);
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_OVERSEA);//业务线=非海外仓
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);//业务线=非FBA大货
            $this->purchase_db->not_like('purchase_number', 'YPO', 'after');
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('buyer_id');
            $result = $this->purchase_db->get($this->table_purchase_order)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);
            $return = ['flag' => TRUE, 'msg' => '待采购询价数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计'非海外仓'1688已逾期未完结(PO数量)
     * @param int    $category_id
     * @param string $module
     * @return array
     */
    private function ali_overdue_order_summary($category_id, $module)
    {
        try {
            //类型
            $type = 'ali_overdue_order';
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //结算方式=1688账期
            $account_type = $this->_get_account_type(20);//获取结算方式
            //付款提醒状态=已超期
            $supplier_res = $this->purchase_db->select('supplier_code')
                ->where('supplier_settlement', SUPPLIER_SETTLEMENT_CODE_TAP_DATE)
                ->where('quota <>', 0)
                ->where('surplus_quota >', 0)
                ->get($this->table_supplier)
                ->result_array();
            $supplier_res = !empty($supplier_res) ? array_column($supplier_res, 'supplier_code') : [ORDER_CANCEL_ORSTATUS];
            $supplier_res = array_unique($supplier_res);

            //数据查询
            $this->purchase_db->select('a.buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->from("$this->table_purchase_order a");
            $this->purchase_db->join("$this->table_order_pay_type b", 'b.purchase_number = a.purchase_number');
            $this->purchase_db->not_like('a.purchase_number', 'YPO', 'after');
            //采购单状态=等待到货/部分到货等待剩余/信息修改待审核/信息修改驳回/作废订单待退款/作废订单待审核
            $this->purchase_db->where_in('a.purchase_order_status', [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND, PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT]);
            $account_type = empty($account_type) ? [ORDER_CANCEL_ORSTATUS] : $account_type;
            $this->purchase_db->where_in('a.account_type', $account_type);
            $this->purchase_db->where_in('a.supplier_code', $supplier_res);
            $this->purchase_db->where('b.accout_period_time <>', '0000-00-00 00:00:00');
            $this->purchase_db->where('b.accout_period_time <', date('Y-m-d H:i:s'));
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('a.buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('a.buyer_id');
            $result = $this->purchase_db->get()->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '1688已逾期未完结数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计'非海外仓'信息修改待审核(PO个数),信息修改驳回(PO个数)
     * @param int    $category_id
     * @param string $module
     * @param string $type (waiting_audit-统计待审核数据，reject-统计修改驳回数据)
     * @return array
     */
    private function pur_order_info_change_summary($category_id,$module,$type)
    {
        if (!in_array($type, ['waiting_audit', 'reject'])) {
            return ['flag' => FALSE, 'msg' => '参数错误'];
        } elseif ('waiting_audit' == $type) {
            $purchase_order_status = PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT;//采购单状态
            $type = 'pur_order_info_change_waiting_audit';                           //类型
        } else {
            $purchase_order_status = PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT;//采购单状态
            $type = 'pur_order_info_change_reject';                           //类型
        }

        try {
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //数据查询
            $this->purchase_db->select('buyer_id,COUNT(1) AS count_num');
            //信息修改待审核,信息修改驳回
            $this->purchase_db->where('purchase_order_status', $purchase_order_status);
            $this->purchase_db->not_like('purchase_number', 'YPO', 'after');
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('buyer_id');
            $result = $this->purchase_db->get($this->table_purchase_order)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功' . '[' . $type . ']'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计'非海外仓'1688金额异常(PO个数)
     * @param int    $category_id
     * @param string $module
     * @return array
     */
    private function ali_abnormal_amount_order_summary($category_id,$module)
    {
        try {
            //类型
            $type = 'ali_abnormal_amount_order';
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //数据查询
            $this->purchase_db->select('buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->where('is_ali_price_abnormal', 1);
            //采购单状态=等待到货/部分到货等待剩余/信息修改待审核/信息修改驳回/作废订单待退款/作废订单待审核
            $this->purchase_db->where_in('purchase_order_status', [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL, PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT, PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND, PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT]);
            $this->purchase_db->not_like('purchase_number', 'YPO', 'after');
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('buyer_id');
            $result = $this->purchase_db->get($this->table_purchase_order)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '1688金额异常数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’入库冲销未完成（入库批次号个数）
     * @param int    $category_id
     * @param string $module
     * @return array
     */
    private function unfinished_ca_instock_order_summary($category_id,$module)
    {
        try {
            //类型
            $type = 'unfinished_ca_instock_order';
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //数据查询
            $this->purchase_db->select('c.buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->from("$this->table_statement_warehouse_results a");
            $this->purchase_db->join("$this->table_purchase_order_items b", 'b.id=a.items_id', 'left');
            $this->purchase_db->join("$this->table_purchase_order c", 'c.purchase_number=b.purchase_number', 'left');
            $this->purchase_db->where('a.source', SOURCE_COMPACT_ORDER);
            $this->purchase_db->where_in('c.pay_status', [10, 13, 14, 20, 21, 25, 26, 30, 31, 40, 50, 60, 61, 62, 63, 64, 65, 66, 67, 95]);//付款状态≠已付款-51
            $this->purchase_db->where_in('a.charge_against_status', [1, 2]);      //冲销状态=未冲销、部分已冲销
            $this->purchase_db->where('a.instock_date >=', '2020-06-05 00:00:00');//入库时间
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('c.buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('c.buyer_id');
            $result = $this->purchase_db->get()->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '1688金额异常数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }
    //endregion

    //region ‘非海外’-‘未完结跟进’模块
    /**
     * 统计‘非海外’-‘未完结跟进’模块数据
     * @return array
     */
    public function non_oversea_unfinished_follow_up()
    {
        try {
            $category_id = DAC_GROUP_TYPE_NON_OVERSEA;//小组类型
            $module = 'unfinished_follow_up';          //模块

            //统计超20天未完结，超30天未完结，超40天未完结(PO数量)
            $type_arr = ['more_than_20_days', 'more_than_30_days', 'more_than_40_days'];
            foreach ($type_arr as $type){
                $res = $this->unfinished_overdue_many_days($category_id,$module,$type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            //统计‘非海外仓’异常待处理，异常处理中订单（异常单号个数）
            $type_arr = ['waiting_handle', 'in_exception_handling'];
            foreach ($type_arr as $type) {
                $res = $this->unfinished_exception($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            //统计‘非海外仓’有快递无轨迹,无物流单号(备货单号个数)
            $type_arr = ['have_express_no_track', 'no_express_tracking_number'];
            foreach ($type_arr as $type) {
                $res = $this->unfinished_express($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            $return = ['flag' => TRUE, 'msg' => '非海外仓-未完结跟进-数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’未完结逾期多天订单（PO数量）
     * @param int    $category_id
     * @param string $module
     * @param string $type
     * @return array
     */
    private function unfinished_overdue_many_days($category_id, $module,$type)
    {
        try {

            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //数据查询
            $this->purchase_db->select('buyer_id,COUNT(1) AS count_num');
            //采购单状态=等待到货/信息修改待审核/信息修改待驳回/部分到货等待剩余/作废订单待审核
            $this->purchase_db->where_in('purchase_order_status', [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT, PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND]);
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_OVERSEA);//业务线=非海外仓
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);//业务线=非FBA大货
            $this->purchase_db->not_like('purchase_number', 'YPO', 'after');
            if ('more_than_20_days' == $type) {
                //-- 超20天未完结
                $this->purchase_db->where('TIMESTAMPDIFF(DAY,waiting_time,NOW()) >=', 20);
                $this->purchase_db->where('TIMESTAMPDIFF(DAY,waiting_time,NOW()) <', 30);
            } elseif ('more_than_30_days' == $type) {
                //-- 超30天未完结
                $this->purchase_db->where('TIMESTAMPDIFF(DAY,waiting_time,NOW()) >=', 30);
                $this->purchase_db->where('TIMESTAMPDIFF(DAY,waiting_time,NOW()) <', 40);
            } else {
                //-- 超40天未完结
                $this->purchase_db->where('TIMESTAMPDIFF(DAY,waiting_time,NOW()) >=', 40);
            }
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('buyer_id');
            $result = $this->purchase_db->get($this->table_purchase_order)->result_array();
            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计-‘非海外仓’异常待处理，异常处理中订单（异常单号个数）
     * @param int    $category_id
     * @param string $module
     * @param string  $type
     * @return array
     */
    private function unfinished_exception($category_id, $module, $type)
    {
        try {
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //数据查询
            $this->purchase_db->select('buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->from("$this->table_warehouse_abnormal a");
            $this->purchase_db->join("$this->table_purchase_order b", 'a.pur_number=b.purchase_number', 'left');
            $this->purchase_db->where('is_reject', 0);
            $this->purchase_db->where('pull_time >=', '2020-01-01 00:00:00');
            $this->purchase_db->where('pull_time <=', date('Y-m-d H:i:s'));
            if ('waiting_handle' == $type) {
                //-- 异常待处理
                $this->purchase_db->where('is_handler', 0);
            } else {
                //-- 异常处理中订单
                $this->purchase_db->where('is_handler', 2);
            }
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('b.buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('b.buyer_id');
            $result = $this->purchase_db->get()->result_array();
            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计-‘非海外仓’有快递无轨迹，无快递单号（备货单号个数）
     * @param int    $category_id
     * @param string $module
     * @param string $type
     * @return array
     */
    private function unfinished_express($category_id, $module, $type)
    {
        try {
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //子查询sql
            $this->purchase_db->select('a.buyer_id')->from("$this->table_purchase_progress a");
            $this->purchase_db->join("$this->table_purchase_order b", 'b.purchase_number=a.purchase_number', 'left');
            $this->purchase_db->join("$this->table_logistics_info c", 'c.purchase_number=a.purchase_number AND c.sku=a.sku', 'left');
            //订单状态=等待到货/信息修改待审核/信息修改待驳回/部分到货等待剩余
            $this->purchase_db->where_in('purchase_order_status', [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT]);
            //审核时间=当前时间3天以前
            $this->purchase_db->where('a.create_time >=', '2019-01-01 00:00:00');
            $this->purchase_db->where('a.create_time <=', date('Y-m-d 23:59:59', strtotime('-3 day')));
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_OVERSEA);//业务线=非海外仓
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);//业务线=非FBA大货
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('a.buyer_id', $authorization_user);
            }
            if ('have_express_no_track' == $type) {
                //-- 有快递无轨迹
                $this->purchase_db->where('(c.status IS NULL OR c.status=0)');
                $this->purchase_db->where('(c.express_no <>"" AND c.express_no IS NOT NULL)');
            } else {
                //-- 无物流单号
                $this->purchase_db->where('(c.express_no ="" OR c.express_no IS NULL)');
            }
            $this->purchase_db->group_by('a.demand_number');
            $sub_query = $this->purchase_db->get_compiled_select();

            //数据查询
            $main_query = "SELECT tmp.buyer_id,COUNT(1) AS count_num FROM($sub_query) AS tmp GROUP BY tmp.buyer_id";
            $result = $this->purchase_db->query($main_query)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    //endregion

    //region ‘非海外’-‘逾期交货’模块
    /**
     * 统计‘非海外’-‘逾期交货’模块数据
     * @return array
     */
    public function non_oversea_overdue_delivery()
    {
        try {
            $category_id = DAC_GROUP_TYPE_NON_OVERSEA;//小组类型
            $module = 'overdue_delivery';          //模块

            //统计‘非海外仓’逾期7天以上，逾期10天以上，逾期15天以上，逾期20天以上，逾期30天以上（备货单个数）
            $type_arr = ['more_than_7_days', 'more_than_3_days','more_than_15_days','more_than_20_days','more_than_30_days'];
            foreach ($type_arr as $type) {
                $res = $this->overdue_delivery_many_days($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            //统计‘非海外仓’门户未确认(备货单个数)，门户待发货(sku数量)
            $type_arr = ['not_confirmed', 'wait_for_shipping'];
            foreach ($type_arr as $type) {
                $res = $this->overdue_delivery_gateway($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            $return = ['flag' => TRUE, 'msg' => '非海外仓-逾期交货-数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’逾期7天以上，逾期10天以上，逾期15天以上，逾期20天以上，逾期30天以上（备货单个数）
     * @param int    $category_id
     * @param string $module
     * @param string  $type
     * @return array
     */
    private function overdue_delivery_many_days($category_id, $module, $type)
    {
        try {

            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }

            //子查询sql
            $this->purchase_db->select('a.buyer_id')->from("$this->table_purchase_order a");
            $this->purchase_db->join("$this->table_purchase_order_items b", 'a.purchase_number = b.purchase_number');
            $this->purchase_db->join("$this->table_purchase_suggest_map d", 'd.purchase_number=b.purchase_number AND d.sku=b.sku','left');
            $this->purchase_db->join("$this->table_purchase_suggest e", 'e.demand_number=d.demand_number','left');
            //是否逾期=是
            $this->purchase_db->where('b.is_overdue', 1);
            //业务线=非海外仓
            $this->purchase_db->where('a.purchase_type_id <>', PURCHASE_TYPE_OVERSEA);
            $this->purchase_db->where('a.purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);
            $this->purchase_db->not_like('a.purchase_number', 'YPO', 'after');
            //备货单的采购状态=等待到货、信息修改待审核、信息修改驳回、作废待审核、部分到货等待剩余
            $this->purchase_db->where_in('e.suggest_order_status', [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL, PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT, PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT, PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE]);

            if ('more_than_7_days' == $type) {
                $this->purchase_db->where('b.overdue_days >', 7);
                $this->purchase_db->where('b.overdue_days <=', 15);

            } elseif ('more_than_3_days' == $type) {
                $this->purchase_db->where('b.overdue_days >', 3);
                $this->purchase_db->where('b.overdue_days <=', 7);

            } elseif ('more_than_15_days' == $type) {
                $this->purchase_db->where('b.overdue_days >', 15);
                $this->purchase_db->where('b.overdue_days <=', 20);

            } elseif ('more_than_20_days' == $type) {
                $this->purchase_db->where('b.overdue_days >', 20);
                $this->purchase_db->where('b.overdue_days <=', 30);

            } else {
                $this->purchase_db->where('b.overdue_days >', 30);
            }
            $this->purchase_db->where("b.plan_arrive_time!='0000-00-00 00:00:00'");
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('a.buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('b.purchase_number,b.sku');
            $sub_query = $this->purchase_db->get_compiled_select();

            //数据查询
            $main_query = "SELECT tmp.buyer_id,COUNT(1) AS count_num FROM($sub_query) AS tmp GROUP BY tmp.buyer_id";
            $result = $this->purchase_db->query($main_query)->result_array();
            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’门户未确认(备货单个数)，门户待发货(sku数量)
     * @param int    $category_id
     * @param string $module
     * @param string $type
     * @return array
     */
    private function overdue_delivery_gateway($category_id, $module, $type)
    {
        try {
            if (!in_array($type, ['not_confirmed', 'wait_for_shipping'])) {
                return ['flag' => FALSE, 'msg' => '参数错误'];
            } elseif ('not_confirmed' == $type) {//门户未确认
                $purchase_order_status = [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                    PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT];     //采购单状态
                $gateway_status = 2;                               //门户系统订单状态=待确认
                $group_by = 'b.purchase_number,b.sku';             //按备货单分组
            } else {//门户待发货
                $purchase_order_status = [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                    PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT];//采购单状态
                $gateway_status = 3;                                                                            //门户系统订单状态=待发货
                $group_by = 'b.sku';                                                                            //按sku分组
            }

            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }

            //子查询sql
            $this->purchase_db->select('a.buyer_id')->from("$this->table_purchase_order a");
            $this->purchase_db->join("$this->table_purchase_order_items b", 'b.purchase_number=a.purchase_number');
            $this->purchase_db->not_like('b.purchase_number', 'YPO', 'after');
            $this->purchase_db->where('a.gateway_status', $gateway_status);
            $this->purchase_db->where_in('a.purchase_order_status', $purchase_order_status);
            $this->purchase_db->where('a.purchase_type_id <>', PURCHASE_TYPE_OVERSEA);//业务线=非海外仓
            $this->purchase_db->where('a.purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);//业务线=非FBA大货

            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('a.buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by($group_by);
            $sub_query = $this->purchase_db->get_compiled_select();

            //数据查询
            $main_query = "SELECT tmp.buyer_id,COUNT(1) AS count_num FROM($sub_query) AS tmp GROUP BY tmp.buyer_id";
            $result = $this->purchase_db->query($main_query)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功[' . $type . ']'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }
    //endregion

    //region ‘非海外’-‘请款跟进’模块
    /**
     * 统计‘非海外’-‘请款跟进’模块数据
     * @return array
     */
    public function non_oversea_request_payment_follow_up()
    {
        try {
            $category_id = DAC_GROUP_TYPE_NON_OVERSEA;//小组类型
            $module = 'request_payment_follow_up';          //模块

            //统计‘非海外仓’网拍未申请付款，网拍请款被驳回（PO数量）
            $type_arr = ['no_request_payment', 'request_payment_reject'];

            foreach ($type_arr as $type) {

                $res = $this->pai_request_payment($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            //统计‘非海外仓’1688账期可请款，1688账期请款被驳回（PO数量）
            $type_arr = ['account_period_request_payment' => ['over_time_and_coming_soon', 'without_balance'],
                'account_period_request_payment_reject' => []];
            foreach ($type_arr as $type => $type_val) {
                $res = $this->ail_request_payment($category_id, $module, $type,$type_val);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            //统计‘非海外仓’合同请款被驳回,合同单昨日已付款（po号个数，去重统计 ）
            $type_arr = ['contract_request_payment_reject', 'contract_paid_yesterday'];

            foreach ($type_arr as $type) {

                $res = $this->contract_request_payment($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            $return = ['flag' => TRUE, 'msg' => '非海外仓-请款跟进-数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’网拍未申请付款，网拍请款被驳回（PO数量）
     * @param int    $category_id
     * @param string $module
     * @param string  $type
     * @return array
     */
    private function pai_request_payment($category_id, $module, $type)
    {
        try {

            //结算方式=款到发货
            $account_type = $this->_get_account_type(10);//获取结算方式

            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }

            //数据查询
            $this->purchase_db->select('buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->where('purchase_order_status', PURCHASE_ORDER_STATUS_WAITING_ARRIVAL);
            $this->purchase_db->where('source', SOURCE_NETWORK_ORDER);
            //结算方式=款到发货
            if (!empty($account_type)) {
                $this->purchase_db->where_in('account_type', $account_type);
            }
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_OVERSEA);//业务线=非海外仓
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);//业务线=非FBA大货
            $this->purchase_db->not_like('purchase_number', 'YPO', 'after');

            if ('no_request_payment' == $type) {//网拍未申请付款
                $this->purchase_db->where('pay_status', PAY_UNPAID_STATUS);
            } else {//网拍请款被驳回
                $this->purchase_db->where('pay_status', PAY_FINANCE_REJECT);
            }
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('buyer_id');
            $result = $this->purchase_db->get($this->table_purchase_order)->result_array();
            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’1688账期可请款，1688账期请款被驳回（PO数量）
     * @param int    $category_id
     * @param string $module
     * @param string $type
     * @param array  $type_val
     * @return array
     */
    private function ail_request_payment($category_id, $module, $type, $type_val = [])
    {
        try {
            //获取结算方式,结算方式=1688账期
            $account_type = $this->_get_account_type(20);

            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            $supplier_res = [];
            if ('account_period_request_payment' == $type) {
                //获取供应商
                foreach ($type_val as $pay_notice) {
                    $this->purchase_db->select('supplier_code');
                    $this->purchase_db->where('supplier_settlement', SUPPLIER_SETTLEMENT_CODE_TAP_DATE);
                    $this->purchase_db->where('quota <>', 0);
                    if ('over_time_and_coming_soon' == $pay_notice) {
                        $this->purchase_db->where('surplus_quota >', 0);//付款提醒状态=已超期或即将到期
                    } else {
                        $this->purchase_db->where('surplus_quota <=', 0);//付款提醒状态=额度已满，需紧急支付
                    }
                    $supplier = $this->purchase_db->get($this->table_supplier)->result_array();
                    $supplier = !empty($supplier) ? array_column($supplier, 'supplier_code') : [ORDER_CANCEL_ORSTATUS];
                    $supplier_res[$pay_notice] = array_unique($supplier);
                }
            }
            //数据查询
            $this->purchase_db->select('a.buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->from("$this->table_purchase_order a");
            $this->purchase_db->join("$this->table_order_pay_type b", 'b.purchase_number = a.purchase_number');
            $this->purchase_db->not_like('a.purchase_number', 'YPO', 'after');
            //采购单状态=全部到货/部分到货不等待剩余
            $this->purchase_db->where_in('a.purchase_order_status', [PURCHASE_ORDER_STATUS_ALL_ARRIVED, PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE]);
            $this->purchase_db->where('a.purchase_type_id <>', PURCHASE_TYPE_OVERSEA);//业务线=非海外仓
            $this->purchase_db->where('a.purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);//业务线=非FBA大货

            //结算方式=1688账期
            $account_type = empty($account_type) ? [ORDER_CANCEL_ORSTATUS] : $account_type;
            $this->purchase_db->where_in('a.account_type', $account_type);
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('a.buyer_id', $authorization_user);
            }
            if ('account_period_request_payment' == $type) {
                //付款状态=未申请付款
                $this->purchase_db->where('a.pay_status', PAY_UNPAID_STATUS);
                $this->purchase_db->group_start();//付款提醒状态=额度已满需紧急支付或者付款提醒状态=额度已满需紧急支付已超期,即将到期
                $this->purchase_db->where_in('a.supplier_code', $supplier_res['without_balance']);
                $this->purchase_db->or_group_start();
                $this->purchase_db->where_in('a.supplier_code', $supplier_res['over_time_and_coming_soon']);
                $this->purchase_db->where('b.accout_period_time <>', '0000-00-00 00:00:00');
                $five_days_later = date('Y-m-d H:i:s', strtotime('+5 days'));
                $this->purchase_db->where('b.accout_period_time <', $five_days_later);//该时间段已包含‘已超期’和‘即将到期’
                $this->purchase_db->group_end();
                $this->purchase_db->group_end();
            } else {
                //付款状态=财务驳回
                $this->purchase_db->where('a.pay_status', PAY_FINANCE_REJECT);
            }
            $this->purchase_db->group_by('a.buyer_id');
            $result = $this->purchase_db->get()->result_array();
            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '1688已逾期未完结数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’合同请款被驳回，合同单昨日已付款（po号个数，去重统计 ）
     * @param int    $category_id
     * @param string $module
     * @param string  $type
     * @return array
     */
    private function contract_request_payment($category_id, $module, $type)
    {
        try {
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //数据查询
            $this->purchase_db->select('buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->where('source', SOURCE_COMPACT_ORDER);
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_OVERSEA);//业务线=非海外仓
            $this->purchase_db->where('purchase_type_id <>', PURCHASE_TYPE_FBA_BIG);//业务线=非FBA大货
            $this->purchase_db->not_like('purchase_number', 'YPO', 'after');
            if ('contract_request_payment_reject' == $type) {//财务驳回
                $this->purchase_db->where('pay_status', PAY_FINANCE_REJECT);
            } else {//已付款
                $this->purchase_db->where('pay_status', PAY_PAID);
                //付款时间=昨天
                $date = date('Y-m-d', strtotime('-1 day'));
                $this->purchase_db->where('pay_time >=', $date . ' 00:00:00');
                $this->purchase_db->where('pay_time <=', $date . ' 23:59:59');
            }
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('buyer_id');
            $result = $this->purchase_db->get($this->table_purchase_order)->result_array();
            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }
    //endregion

    //region ‘非海外’-‘退款跟进’模块
    /**
     * 统计‘非海外’-‘退款跟进’模块数据
     * @return array
     */
    public function non_oversea_refund_follow_up()
    {
        try {
            $category_id = DAC_GROUP_TYPE_NON_OVERSEA;//小组类型
            $module = 'refund_follow_up';             //模块

            //统计‘非海外仓’退款待审核，待上传截图，1688已退款待上传截图，退款财务驳回（PO数量）
            $type_arr = ['refund_waiting_audit', 'screenshot_waiting_upload','ail_screenshot_waiting_upload','refund_reject'];
            foreach ($type_arr as $type) {
                $res = $this->refund($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            //统计‘非海外仓’1688交易取消（PO数量）
            $res = $this->ali_deal_cancelled($category_id,$module);
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }
            $return = ['flag' => TRUE, 'msg' => '非海外仓-退款跟进-数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’退款待审核，待上传截图，1688已退款待上传截图，退款财务驳回（PO数量）
     * @param int    $category_id
     * @param string $module
     * @param string  $type
     * @return array
     */
    private function refund($category_id, $module, $type)
    {
        try {
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //根据取消未到货状态，获取取消未到货明细表id
            if ('refund_waiting_audit' == $type) {
                $audit_status = CANCEL_AUDIT_STATUS_CG;
            } elseif ('screenshot_waiting_upload' == $type or 'ail_screenshot_waiting_upload' == $type) {
                $audit_status = CANCEL_AUDIT_STATUS_SCJT;
            } else {
                $audit_status = CANCEL_AUDIT_STATUS_CFBH;
            }

            //子查询sql
            $this->purchase_db->select('d.buyer_id');
            $this->purchase_db->from('pur_purchase_order_cancel_detail a');
            $this->purchase_db->join('pur_purchase_order_cancel b', 'a.cancel_id=b.id');
            $this->purchase_db->join("$this->table_purchase_order_items c", 'c.id=a.items_id');
            $this->purchase_db->join("$this->table_purchase_order d", 'd.purchase_number = c.purchase_number');
            if ('ail_screenshot_waiting_upload' == $type) {
                $this->purchase_db->select('SUM(e.apply_amount) AS apply_amount');
                //获取1688退款金额≠0的采购单
                $this->purchase_db->join("pur_purchase_order_pay_type e", 'e.purchase_number = d.purchase_number');
                $this->purchase_db->having('apply_amount > 0');
            }
            $this->purchase_db->where('b.audit_status', $audit_status);
            $this->purchase_db->not_like('c.purchase_number', 'YPO', 'after');
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('d.buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('c.purchase_number');
            $sub_query = $this->purchase_db->get_compiled_select();

            $main_query = "SELECT tmp.buyer_id,COUNT(1) AS count_num FROM($sub_query) AS tmp GROUP BY tmp.buyer_id";

            $result = $this->purchase_db->query($main_query)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);


            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’1688交易取消（PO数量）
     * @param int    $category_id
     * @param string $module
     * @return array
     */
    private function ali_deal_cancelled($category_id, $module)
    {
        try {
            //类型
            $type = 'deal_cancelled';
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //数据查询
            $this->purchase_db->select('buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->where_in('purchase_order_status', [PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE, PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT,
                PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND, PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT]);
            $this->purchase_db->where('ali_order_status', '交易取消');//1688备注='交易取消'
            $this->purchase_db->not_like('purchase_number', 'YPO', 'after');
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('buyer_id', $authorization_user);
            }
            $this->purchase_db->group_by('buyer_id');
            $result = $this->purchase_db->get($this->table_purchase_order)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }
    //endregion

    //region ‘非海外’-‘其他待处理’模块
    /**
     * 统计‘非海外’-‘其他待处理’模块数据
     * @return array
     */
    public function non_oversea_other_pending()
    {
        try {
            $category_id = DAC_GROUP_TYPE_NON_OVERSEA;//小组类型
            $module = 'other_pending';                //模块

            //ECN变更被驳回
            $res = $this->_get_ecn_data($category_id, $module, 'ecn_reject', 3);
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }

            //统计‘非海外仓’sku屏蔽申请驳回,sku屏蔽申请待审核(申请ID数量)
            $type_arr = ['sku_screen_reject', 'sku_screen_waiting_audit'];
            foreach ($type_arr as $type) {
                $res = $this->sku_scree_stat($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            //统计‘非海外仓’产品信息待审核,产品信息驳回(申请ID数量)
            $type_arr = ['sku_info_waiting_audit', 'sku_info_reject'];

            foreach ($type_arr as $type) {
                $res = $this->sku_info_stat($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            //统计-‘非海外仓’供应商信息修改待审核，供应商信息修改驳回（供应商代码个数）
            $type_arr = ['supplier_info_waiting_audit', 'supplier_info_reject'];

            foreach ($type_arr as $type) {
                $res = $this->supplier_info_change_stat($category_id, $module, $type);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }
            }
            $return = ['flag' => TRUE, 'msg' => '非海外仓-其他待处理-数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计-‘非海外仓’sku屏蔽申请驳回，sku屏蔽申请待审核（申请ID数量）
     * @param int    $category_id
     * @param string $module
     * @param string $type
     * @return array
     */
    private function sku_scree_stat($category_id, $module, $type)
    {
        try {

            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }

            if ('sku_screen_reject' == $type) {
                //-- sku屏蔽申请驳回
                $status = PRODUCT_SCREE_STATUS_PURCHASE_REJECTED;//采购经理驳回
                $three_days_ago = date('Y-m-d', strtotime('-3 day'));
                $this->purchase_db->where('apply_time >=', $three_days_ago . ' 00:00:00');
                $this->purchase_db->where('apply_time <=', date('Y-m-d H:i:s'));
            } else {
                //-- sku屏蔽申请待审核
                $status = PRODUCT_SCREE_STATUS_WAITING_PURCHASE_AUDIT;//待采购经理审核
            }
            //数据查询
            $this->purchase_db->select('apply_user_id AS buyer_id,COUNT(1) AS count_num');
            $this->purchase_db->where('status', $status);
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('apply_user_id', $authorization_user);
            }
            $this->purchase_db->group_by('apply_user_id');
            $result = $this->purchase_db->get($this->table_product_scree)->result_array();
            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计‘非海外仓’产品信息待审核,产品信息驳回(申请ID数量)
     * @param int    $category_id
     * @param string $module
     * @param string $type
     * @return array
     */
    private function sku_info_stat($category_id, $module, $type)
    {
        try {
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            //子查询sql
            $this->purchase_db->select('create_user_id AS buyer_id')->from($this->table_product_update_log);
            if ('sku_info_waiting_audit' == $type) {
                //-- 产品信息待审核
                $this->purchase_db->where_in('audit_status', [PRODUCT_UPDATE_LIST_PURCHASE, PRODUCT_EXECUTIVE_DIRECTOR,
                    PRODUCT_DEPUTY_MANAGER, PRODUCT_PURCHASING_MANAGER,
                    PRODUCT_DEVELOPMENT, PRODUCT_SUPPLIER_DIRECTOR]);
                $group_by = 'create_user_id';

            } else {
                //-- 产品信息驳回
                $this->purchase_db->where('audit_status', PRODUCT_UPDATE_LIST_REJECT);
                $group_by = 'sku,create_user_id';
            }
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('create_user_id', $authorization_user);
            }
            $this->purchase_db->group_by($group_by);
            $sub_query = $this->purchase_db->get_compiled_select();

            //数据查询
            $main_query = "SELECT tmp.buyer_id,COUNT(1) AS count_num FROM($sub_query) AS tmp GROUP BY tmp.buyer_id";
            $result = $this->purchase_db->query($main_query)->result_array();
            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 统计-‘非海外仓’供应商信息修改待审核，供应商信息修改驳回（供应商代码个数）
     * @param int    $category_id
     * @param string $module
     * @param string $type
     * @return array
     */
    private function supplier_info_change_stat($category_id, $module, $type)
    {
        try {
            //过滤可刷新的采购员数据
            $authorization_user = $this->_get_refreshable_users($category_id, $module, $type, $this->_authorization_user);
            if(TRUE === $authorization_user){
                $authorization_user = [];
            }elseif (empty($authorization_user)) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
            if ('supplier_info_waiting_audit' == $type) {
                //-- 供应商信息修改待审核
                $audit_status = [SUPPLIER_WAITING_PURCHASE_REVIEW, SUPPLIER_WAITING_SUPPLIER_REVIEW, SUPPLIER_FINANCE_REVIEW];
            } else {
                //-- 供应商信息修改驳回
                $audit_status = [SUPPLIER_PURCHASE_REJECT, SUPPLIER_SUPPLIER_REJECT, SUPPLIER_FINANCE_REJECT];
            }

            //子查询sql
            $this->purchase_db->select('b.create_user_id AS buyer_id');
            $this->purchase_db->from("$this->table_supplier a");
            $audit_status_join = implode(',', $audit_status);
            $this->purchase_db->join('pur_supplier_update_log b', "a.supplier_code=b.supplier_code AND b.audit_status IN({$audit_status_join})");
            $this->purchase_db->where_in('a.audit_status', $audit_status);
            //数据权限控制
            if (!empty($authorization_user)) {
                $this->purchase_db->where_in('b.create_user_id', $authorization_user);
            }
            $this->purchase_db->group_by('a.id');
            $sub_query = $this->purchase_db->get_compiled_select();

            //数据查询
            $main_query = "SELECT tmp.buyer_id,COUNT(1) AS count_num FROM($sub_query) AS tmp GROUP BY tmp.buyer_id";
            $result = $this->purchase_db->query($main_query)->result_array();

            //结果数据写入MongoDb
            $this->_save_data($result, $category_id, $module, $type);

            $return = ['flag' => TRUE, 'msg' => '数据汇总成功'];
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $return;
    }
    //endregion

    /**
     * 海外仓 原生 SQL 拼接子 SQL
     * @param string $module
     * @param string $type  该值无效的，海外仓按模块更新
     * @param string $sub_query
     * @return string
     */
    private function _oversea_authorization_user($module = NULL, $type = NULL, $sub_query = NULL)
    {
        $authorization_user = $this->_get_refreshable_users(DAC_GROUP_TYPE_OVERSEA, $module, $type, $this->_authorization_user);
        if (TRUE === $authorization_user) {
            return '';
        } elseif (!empty($authorization_user)) {
            $user_ids = implode(',', $authorization_user);
            return " AND $sub_query IN($user_ids)";
        } else {
            return 'no_refreshable_users';
        }
    }

    /**
     * 海外仓  -  待优先处理
     * @return array
     */
    public function oversea_priority_processing()
    {
        $module = 'priority_processing';
        $column_list = [
            'to_prepare_the_stock_list'                => ['column_name'=>'待生成备货单','field_name'=>'ps.buyer_id'],
            'to_purchase_inquiry_non_direct_po'        => ['column_name'=>'待采购询价-非直发po','field_name'=>'A.buyer_id'],
            'to_purchase_inquiry_direct_po'            => ['column_name'=>'待采购询价-直发po','field_name'=>'A.buyer_id'],
            'waiting_for_the_manager_to_review_the_po' => ['column_name'=>'待经理审核po','field_name'=>'A.buyer_id'],
            'portal_to_confirm_delivery_sku'           => ['column_name'=>'门户待确认交期sku','field_name'=>'A.buyer_id'],
            'waiting_to_generate_po'                   => ['column_name'=>'等待生成进货单po','field_name'=>'A.buyer_id'],
            'same_as_not_uploaded_portal'              => ['column_name'=>'合同未上传（门户）','field_name'=>'B.buyer_id'],
            'did_not_make_out_an_invoice'              => ['column_name'=>'未开票','field_name'=>'A.buyer_id'],
            'inventory_write_off_is_not_completed'     => ['column_name'=>'入库冲销未完成','field_name'=>'c.buyer_id'],
        ];
        $statistics_user_data = [];
        foreach($column_list as $column_key => $item){

            $buyer_id_sub_query = '';
            //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
            if ($this->_need_permission) {
                $buyer_id_sub_query = $this->_oversea_authorization_user($module, $column_key, $item['field_name']);// 拼接 查询用户权限SQL
                if ('no_refreshable_users' == $buyer_id_sub_query) {
                    return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
                }
            }

            $query_sql_1 = $query_sql_2 = '';
            switch($column_key){
                case 'to_prepare_the_stock_list':
                    // 待生成备货单-备货单个数
                    $query_sql_1              = "SELECT  ps.buyer_id AS buyer_id,COUNT(1) AS count_num
                                    FROM pur_purchase_suggest AS ps
                                    LEFT JOIN pur_product AS pd ON pd.sku=ps.sku
                                    WHERE ps.is_create_order = 0 AND ps.demand_type_id = 1
                                    AND ps.suggest_status != 3 AND (ps.purchase_type_id = 2 OR ps.purchase_type_id=6)
                                    AND ps.audit_status=1
                                    {$buyer_id_sub_query} GROUP BY ps.buyer_id;";

                    // 待生成备货单-供应商个数
                    $query_sql_2                = "SELECT buyer_id,COUNT(1) AS supplier_count FROM 
                                (
                                    SELECT ps.buyer_id as buyer_id,ps.supplier_code
                                    FROM pur_purchase_suggest AS ps
                                    LEFT JOIN pur_product AS pd ON pd.sku=ps.sku
                                    WHERE ps.is_create_order = 0
                                    AND ps.demand_type_id = 1
                                    AND ps.suggest_status != 3
                                    AND ps.purchase_type_id = 2
                                    AND ps.audit_status=1 
                                    {$buyer_id_sub_query}
                                    GROUP BY ps.buyer_id,ps.supplier_code
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    break;

                case 'to_purchase_inquiry_non_direct_po':
                    // 待采购询价-非直发po-采购单个数
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.purchase_number
                                    FROM pur_purchase_order AS A
                                    INNER JOIN pur_purchase_suggest_map AS B  ON A.purchase_number=B.purchase_number
                                    INNER JOIN pur_purchase_suggest AS C ON C.demand_number=B.demand_number
                                    WHERE  (A.purchase_type_id=2 OR A.purchase_type_id=6) 
                                    AND A.purchase_order_status=1
                                    AND C.shipment_type=2
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.purchase_number
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    // 待采购询价-非直发po-供应商个数
                    $query_sql_2 = "SELECT buyer_id,COUNT(1) AS supplier_count
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.supplier_code
                                    FROM pur_purchase_order AS A
                                    INNER JOIN pur_purchase_suggest_map AS B  ON A.purchase_number=B.purchase_number
                                    INNER JOIN pur_purchase_suggest AS C ON C.demand_number=B.demand_number
                                    WHERE A.purchase_type_id=2
                                    AND A.purchase_order_status=1
                                    AND C.shipment_type=2
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.supplier_code
                                
                                ) AS tmp
                                GROUP BY buyer_id;";
                    break;

                case 'to_purchase_inquiry_direct_po':
                    // 待采购询价-直发po-采购单个数
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.purchase_number
                                    FROM pur_purchase_order AS A
                                    INNER JOIN pur_purchase_suggest_map AS B  ON A.purchase_number=B.purchase_number
                                    INNER JOIN pur_purchase_suggest AS C ON C.demand_number=B.demand_number
                                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6) 
                                    AND A.purchase_order_status=1
                                    AND C.shipment_type=1
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.purchase_number
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    // 待采购询价-直发po-供应商个数
                    $query_sql_2 = "SELECT buyer_id,COUNT(1) AS supplier_count
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.supplier_code
                                    FROM pur_purchase_order AS A
                                    INNER JOIN pur_purchase_suggest_map AS B  ON A.purchase_number=B.purchase_number
                                    INNER JOIN pur_purchase_suggest AS C ON C.demand_number=B.demand_number
                                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6) 
                                    AND A.purchase_order_status=1
                                    AND C.shipment_type=1
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.supplier_code
                                
                                ) AS tmp
                                GROUP BY buyer_id;";
                    break;

                case 'waiting_for_the_manager_to_review_the_po':
                    // 待经理审核po-采购单个数
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.purchase_number
                                    FROM pur_purchase_order AS A
                                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                    AND A.purchase_order_status=3
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.purchase_number
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    // 待经理审核po-供应商个数
                    $query_sql_2 = "SELECT buyer_id,COUNT(1) AS supplier_count
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.supplier_code
                                    FROM pur_purchase_order AS A
                                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                    AND A.purchase_order_status=3
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.supplier_code
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    break;

                case 'portal_to_confirm_delivery_sku':
                    // 门户待确认交期sku-采购单个数
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.purchase_number
                                    FROM pur_purchase_order AS A
                                    INNER JOIN pur_purchase_order_items AS B ON A.purchase_number=B.purchase_number
                                    INNER JOIN pur_supplier_web_audit AS C ON C.purchase_number=B.purchase_number AND C.sku=B.sku
                                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6) 
                                    AND A.purchase_order_status IN(7,6)
                                    AND C.audit_status=1
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.purchase_number
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    // 门户待确认交期sku-供应商个数
                    $query_sql_2 = "SELECT buyer_id,COUNT(1) AS supplier_count
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.supplier_code
                                    FROM pur_purchase_order AS A
                                    INNER JOIN pur_purchase_order_items AS B ON A.purchase_number=B.purchase_number
                                    INNER JOIN pur_supplier_web_audit AS C ON C.purchase_number=B.purchase_number AND C.sku=B.sku
                                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                    AND A.purchase_order_status IN(7,6)
                                    AND C.audit_status=1
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.supplier_code
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    break;

                case 'waiting_to_generate_po':
                    // 等待生成进货单po-采购单个数
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.purchase_number
                                    FROM pur_purchase_order AS A
                                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6) 
                                    AND A.purchase_order_status=6
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.purchase_number
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    // 等待生成进货单po-供应商个数
                    $query_sql_2 = "SELECT buyer_id,COUNT(1) AS supplier_count
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.supplier_code
                                    FROM pur_purchase_order AS A
                                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6) 
                                    AND A.purchase_order_status=6
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.supplier_code
                                
                                ) AS tmp
                                GROUP BY buyer_id;";

                    break;

                case 'same_as_not_uploaded_portal':
                    // 合同未上传（门户）-合同单个数
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM (
                                    SELECT B.buyer_id AS buyer_id,B.purchase_number
                                    FROM pur_purchase_compact AS A
                                    INNER JOIN pur_purchase_compact_items AS B ON A.compact_number=B.compact_number
                                    INNER JOIN pur_purchase_order AS C ON C.purchase_number=B.purchase_number
                                    WHERE A.is_file_uploaded=1
                                    AND C.is_gateway=1
                                    AND (C.purchase_type_id=2 OR C.purchase_type_id=6)
                                    {$buyer_id_sub_query}
                                    GROUP BY B.buyer_id,A.id
                                ) AS tmp
                                GROUP BY buyer_id;";

                    // 合同未上传（门户）-供应商个数
                    $query_sql_2 = "SELECT buyer_id,COUNT(1) AS supplier_count
                                FROM (
                                    SELECT B.buyer_id AS buyer_id,A.supplier_code
                                    FROM pur_purchase_compact AS A
                                    INNER JOIN pur_purchase_compact_items AS B ON A.compact_number=B.compact_number
                                    INNER JOIN pur_purchase_order AS C ON C.purchase_number=B.purchase_number
                                    WHERE A.is_file_uploaded=1
                                    AND C.is_gateway=1
                                    AND (C.purchase_type_id=2 OR C.purchase_type_id=6)
                                    {$buyer_id_sub_query}
                                    GROUP BY B.buyer_id,A.supplier_code
                                ) AS tmp
                                GROUP BY buyer_id;";

                    break;

                case 'did_not_make_out_an_invoice':
                    // 未开票-备货单个数
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.purchase_number
                                    FROM pur_purchase_order AS A
                                    INNER JOIN pur_purchase_order_items AS B ON B.purchase_number=A.purchase_number
                                    WHERE A.purchase_type_id=2
                                    AND A.is_drawback = 1
                                    AND B.invoice_status IN(1, 2, 3)
                                    AND A.purchase_order_status IN(2, 7, 8, 9, 10, 11, 12, 13, 14, 15)
                                    AND A.source = 1
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,B.id
                                ) AS tmp
                                GROUP BY buyer_id;";

                    // 未开票-供应商个数
                    $query_sql_2 = "SELECT buyer_id,COUNT(1) AS supplier_count
                                FROM (
                                    SELECT A.buyer_id AS buyer_id,A.supplier_code
                                    FROM pur_purchase_order AS A
                                    INNER JOIN pur_purchase_order_items AS B ON B.purchase_number=A.purchase_number
                                    WHERE A.purchase_type_id=2
                                    AND A.is_drawback = 1
                                    AND B.invoice_status IN(1, 2, 3)
                                    AND A.purchase_order_status IN(2, 7, 8, 9, 10, 11, 12, 13, 14, 15)
                                    AND A.source = 1
                                    {$buyer_id_sub_query}
                                    GROUP BY A.buyer_id,A.supplier_code
                                ) AS tmp
                                GROUP BY buyer_id;";

                    break;

                case 'inventory_write_off_is_not_completed':
                    // 入库冲销未完成-采购单个数
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM (
                                    SELECT c.buyer_id AS buyer_id,c.purchase_number
                                    FROM pur_statement_warehouse_results a
                                    INNER JOIN pur_purchase_order_items b ON b.id=a.items_id
                                    INNER JOIN pur_purchase_order c ON c.purchase_number=b.purchase_number
                                    WHERE a.source = '1'
                                    AND c.pay_status IN('10', '13', '14', '20', '21', '25', '26', '30', '31', '40', '50', '60', '61', '62', '63', '64','65', '66', '67', '95')
                                    AND a.instock_qty <> 0
                                    AND a.charge_against_status IN('1', '2')
                                    AND c.purchase_type_id=2
                                    {$buyer_id_sub_query}
                                    GROUP BY c.buyer_id,a.instock_batch
                                ) AS tmp
                                GROUP BY buyer_id;";

                    // 入库冲销未完成-供应商个数
                    $query_sql_2 = "SELECT buyer_id,COUNT(1) AS supplier_count
                                FROM (
                                    SELECT c.buyer_id AS buyer_id,c.supplier_code
                                    FROM pur_statement_warehouse_results a
                                    INNER JOIN pur_purchase_order_items b ON b.id=a.items_id
                                    INNER JOIN pur_purchase_order c ON c.purchase_number=b.purchase_number
                                    WHERE a.source = '1'
                                    AND c.pay_status IN('10', '13', '14', '20', '21', '25', '26', '30', '31', '40', '50', '60', '61', '62', '63', '64','65', '66', '67', '95')
                                    AND a.instock_qty <> 0
                                    AND a.charge_against_status IN('1', '2')
                                    AND c.purchase_type_id=2
                                    {$buyer_id_sub_query}
                                    GROUP BY c.buyer_id,c.supplier_code
                                ) AS tmp
                                GROUP BY buyer_id;";

                    break;
            }

            if(empty($query_sql_1) or empty($query_sql_2)){
                return ['flag' => false, 'msg' => $item['column_name'].' SQL 缺失'];
            }

            $buyer_count_num_list = $this->purchase_db->query($query_sql_1)->result_array();
            $buyer_count_num_list = array_column($buyer_count_num_list, 'count_num', 'buyer_id');

            $buyer_supplier_count_list = $this->purchase_db->query($query_sql_2)->result_array();
            $buyer_supplier_count_list = array_column($buyer_supplier_count_list, 'supplier_count', 'buyer_id');

            // 聚合数据
            $buyer_ids = array_unique(array_merge(array_keys($buyer_count_num_list), array_keys($buyer_supplier_count_list)));
            foreach($buyer_ids as $buyer_id){
                $statistics_user_data[$column_key][$buyer_id]['buyer_id']       = $buyer_id;
                $statistics_user_data[$column_key][$buyer_id]['supplier_count'] = isset($buyer_supplier_count_list[$buyer_id]) ? $buyer_supplier_count_list[$buyer_id] : 0;
                $statistics_user_data[$column_key][$buyer_id]['count_num']      = isset($buyer_count_num_list[$buyer_id]) ? $buyer_count_num_list[$buyer_id] : 0;
            }
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'priority_processing';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-待优先处理-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-待优先处理-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 海外仓  -  回货跟进
     * @return array
     */
    public function oversea_back_goods_follow_up(){
        $module = 'back_goods_follow_up';

        $buyer_id_sub_query = '';
        //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
        if ($this->_need_permission) {
            $buyer_id_sub_query = $this->_oversea_authorization_user($module, NULL, "A.buyer_id");// 拼接 查询用户权限SQL
            if ('no_refreshable_users' == $buyer_id_sub_query) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
        }

        $query_sql  = "SELECT A.buyer_id AS buyer_id,A.purchase_number,C.sku,C.demand_number,D.suggest_order_status,A.gateway_status,B.is_overdue,A.waiting_time,
                        B.confirm_amount,D.shipment_type,TIMESTAMPDIFF(DAY,A.waiting_time,NOW()) AS overdue_days,A.completion_time,A.purchase_order_status
                    FROM pur_purchase_order AS A 
                    INNER JOIN pur_purchase_order_items AS B ON B.purchase_number=A.purchase_number
                    INNER JOIN pur_purchase_suggest_map AS C ON C.purchase_number=B.purchase_number AND C.sku=B.sku
                    INNER JOIN pur_purchase_suggest AS D ON D.demand_number=C.demand_number
                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                    AND ( D.suggest_order_status IN(7,2,15) OR A.gateway_status IN(1,2,4) OR A.purchase_order_status IN(7,2,15))
                    {$buyer_id_sub_query}";
        $order_list = $this->purchase_db->query($query_sql)->result_array();

        $order_status   = [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL, PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT, PURCHASE_ORDER_STATUS_CHANGE_INFO_REJECT];
        $gateway_status = [1, 2, 4];

        $this->rediss->select(1);

        // 利用 redis 集合的概念 存储每个 用户 下的 PO/SKU/下单数量，再从 redis 读取数据求和、计算
        foreach($order_list as $value){
            $user_id = $value['buyer_id'];

            if(in_array($value['suggest_order_status'], $order_status)){// 未回货
                $redis_key1 = 'not_back_to_the_goods_poC_'.$user_id;
                $redis_key2 = 'not_back_to_the_goods_skuC_'.$user_id;
                $redis_key3 = 'not_back_to_the_goods_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['demand_number']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key3);
            }

            if (in_array($value['gateway_status'], $gateway_status) && in_array($value['purchase_order_status'], $order_status)
            && '0000-00-00 00:00:00' == $value['completion_time']) {// 门户系统待发货
                $redis_key1 = 'portal_system_is_ready_for_delivery_poC_'.$user_id;
                $redis_key2 = 'portal_system_is_ready_for_delivery_skuC_'.$user_id;
                $redis_key3 = 'portal_system_is_ready_for_delivery_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['demand_number']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key3);
            }

            if(in_array($value['suggest_order_status'], $order_status) and $value['is_overdue'] == 1){// 逾期未回货  是否逾期 0[0] 1[是]
                $redis_key1 = 'overdue_goods_poC_'.$user_id;
                $redis_key2 = 'overdue_goods_skuC_'.$user_id;
                $redis_key3 = 'overdue_goods_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['sku']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key3);
            }

            if(in_array($value['purchase_order_status'], $order_status) and $value['shipment_type'] == 1){// 直发未回货  发运类型(1.工厂发运;2.中转仓发运)
                $redis_key1 = 'direct_shipment_not_returned_poC_'.$user_id;
                $redis_key2 = 'direct_shipment_not_returned_skuC_'.$user_id;
                $redis_key3 = 'direct_shipment_not_returned_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['demand_number']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key3);
            }

            if(in_array($value['purchase_order_status'], $order_status) and $value['overdue_days'] >= 40){// 40天以上未回货
                $redis_key1 = 'no_return_over_40_days_poC_'.$user_id;
                $redis_key2 = 'no_return_over_40_days_skuC_'.$user_id;
                $redis_key3 = 'no_return_over_40_days_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['demand_number']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_back_goods_follow_up_keys_list', $redis_key3);
            }
        }


        $statistics_user_data = [];
        while(1){
            $now_read_key  = $this->rediss->set_spop('oversea_back_goods_follow_up_keys_list');
            $now_read_key  = isset($now_read_key[0])?$now_read_key[0]:'';
            if(empty($now_read_key)){
                break;
            }

            $buyer_id          = substr($now_read_key, strripos($now_read_key, '_') + 1);// 采购员ID
            $now_read_key_new = substr($now_read_key, 0, strripos($now_read_key, '_'));

            if(stripos($now_read_key_new, 'poC') !== false){
                $data_type                                               = str_replace('_poC','',$now_read_key_new);
                $statistics_user_data[$data_type][$buyer_id]['buyer_id'] = $buyer_id;
                $statistics_user_data[$data_type][$buyer_id]['po_count'] = $this->rediss->set_scard($now_read_key);
            }
            if(stripos($now_read_key_new, 'skuC') !== false){
                $data_type                                                = str_replace('_skuC','',$now_read_key_new);
                $statistics_user_data[$data_type][$buyer_id]['buyer_id']  = $buyer_id;
                $statistics_user_data[$data_type][$buyer_id]['sku_count'] = $this->rediss->set_scard($now_read_key);
            }
            if(stripos($now_read_key_new, 'amC') !== false){
                $data_type                                                = str_replace('_amC','',$now_read_key_new);
                $statistics_user_data[$data_type][$buyer_id]['buyer_id']  = $buyer_id;
                $statistics_user_data[$data_type][$buyer_id]['count_num'] = $this->rediss->getData($now_read_key);
            }

            $this->rediss->deleteData($now_read_key);// 清除所有数据
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'back_goods_follow_up';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-回货跟进-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-回货跟进-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 海外仓  -  未完结跟进
     * @return array
     */
    public function oversea_unfinished_follow_up()
    {
        $module = 'unfinished_follow_up';

        $buyer_id_sub_query = '';
        //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
        if ($this->_need_permission) {
            $buyer_id_sub_query = $this->_oversea_authorization_user($module, NULL, "A.buyer_id");// 拼接 查询用户权限SQL
            if ('no_refreshable_users' == $buyer_id_sub_query) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
        }

        $query_sql  = "SELECT A.buyer_id AS buyer_id,A.purchase_number,D.suggest_order_status,B.confirm_amount,C.demand_number,
                        TIMESTAMPDIFF(DAY,A.waiting_time,NOW()) AS overdue_days
                    FROM pur_purchase_order AS A
                    INNER JOIN pur_purchase_order_items AS B ON B.purchase_number=A.purchase_number
                    INNER JOIN pur_purchase_suggest_map AS C ON C.purchase_number=B.purchase_number AND C.sku=B.sku
                    INNER JOIN pur_purchase_suggest AS D ON D.demand_number=C.demand_number
                    WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                    AND A.purchase_order_status IN(7,10,2,15,12,13)
                    {$buyer_id_sub_query}";
        $order_list = $this->purchase_db->query($query_sql)->result_array();

        $this->rediss->select(1);

        // 利用 redis 集合的概念 存储每个 用户 下的 PO/SKU/下单数量，再从 redis 读取数据求和、计算
        foreach($order_list as $value){
            $user_id = $value['buyer_id'];

            if(1){// 未完结
                $redis_key1 = 'unfinished_poC_'.$user_id;
                $redis_key2 = 'unfinished_skuC_'.$user_id;
                $redis_key3 = 'unfinished_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['demand_number']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key3);
            }

            if($value['overdue_days'] >= 45){// 45天以上总未完结
                $redis_key1 = 'over_45_days_poC_'.$user_id;
                $redis_key2 = 'over_45_days_skuC_'.$user_id;
                $redis_key3 = 'over_45_days_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['demand_number']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key3);
            }

            if($value['overdue_days'] >= 45 and $value['overdue_days'] <= 60){// 45-60天未完结
                $redis_key1 = '45_60_days_not_finished_poC_'.$user_id;
                $redis_key2 = '45_60_days_not_finished_skuC_'.$user_id;
                $redis_key3 = '45_60_days_not_finished_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['demand_number']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key3);
            }

            if($value['overdue_days'] > 60){// 60天以上未完结
                $redis_key1 = 'more_than_60_days_poC_'.$user_id;
                $redis_key2 = 'more_than_60_days_skuC_'.$user_id;
                $redis_key3 = 'more_than_60_days_amC_'.$user_id;
                $this->rediss->set_sadd($redis_key1, $value['purchase_number']);
                $this->rediss->set_sadd($redis_key2, $value['demand_number']);
                $this->rediss->incrByData($redis_key3, $value['confirm_amount']);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key1);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key2);
                $this->rediss->set_sadd('oversea_unfinished_follow_up_keys_list', $redis_key3);
            }
        }

        $statistics_user_data = [];
        while(1){
            $now_read_key  = $this->rediss->set_spop('oversea_unfinished_follow_up_keys_list');
            $now_read_key  = isset($now_read_key[0])?$now_read_key[0]:'';
            if(empty($now_read_key)){
                break;
            }

            $buyer_id          = substr($now_read_key, strripos($now_read_key, '_') + 1);// 采购员ID
            $now_read_key_new = substr($now_read_key, 0, strripos($now_read_key, '_'));

            if(stripos($now_read_key_new, 'poC') !== false){
                $data_type                                               = str_replace('_poC','',$now_read_key_new);
                $statistics_user_data[$data_type][$buyer_id]['buyer_id'] = $buyer_id;
                $statistics_user_data[$data_type][$buyer_id]['po_count'] = $this->rediss->set_scard($now_read_key);
            }
            if(stripos($now_read_key_new, 'skuC') !== false){
                $data_type                                                = str_replace('_skuC','',$now_read_key_new);
                $statistics_user_data[$data_type][$buyer_id]['buyer_id']  = $buyer_id;
                $statistics_user_data[$data_type][$buyer_id]['sku_count'] = $this->rediss->set_scard($now_read_key);
            }
            if(stripos($now_read_key_new, 'amC') !== false){
                $data_type                                                = str_replace('_amC','',$now_read_key_new);
                $statistics_user_data[$data_type][$buyer_id]['buyer_id']  = $buyer_id;
                $statistics_user_data[$data_type][$buyer_id]['count_num'] = $this->rediss->getData($now_read_key);
            }

            $this->rediss->deleteData($now_read_key);// 清除所有数据
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'unfinished_follow_up';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-未完结跟进-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-未完结跟进-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;

    }

    /**
     * 海外仓  -  异常跟进
     * @return array
     */
    public function oversea_abnormal_follow_up()
    {
        $module = 'abnormal_follow_up';
        $column_list = [
            'exception_not_handled'                    => ['column_name'=>'异常未处理','field_name'=>'o.buyer_id'],
            'tax_refund_warehouse_not_processed'       => ['column_name'=>'退税仓未处理','field_name'=>'o.buyer_id'],
            'transit_warehouse_not_processed'          => ['column_name'=>'中转仓未处理','field_name'=>'o.buyer_id'],
            'amount_of_1688_is_abnormal'               => ['column_name'=>'1688金额异常','field_name'=>'A.buyer_id'],
            '1688_closed_system_not_finished'          => ['column_name'=>'1688关闭，系统未完结','field_name'=>'A.buyer_id'],
            'portal_system_waits_supplier_confirm_sku' => ['column_name'=>'门户系统待供应商确sku','field_name'=>'a.buyer_id']
        ];

        $statistics_user_data = [];
        foreach($column_list as $column_key => $item){

            $buyer_id_sub_query = '';
            //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
            if ($this->_need_permission) {
                $buyer_id_sub_query = $this->_oversea_authorization_user($module,null,$item['field_name']);// 拼接 查询用户权限SQL
                if ('no_refreshable_users' == $buyer_id_sub_query) {
                    return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
                }
            }

            $query_sql_1 = '';
            switch($column_key){
                case 'exception_not_handled':// 异常未处理
                    $query_sql_1          = "SELECT o.buyer_id,COUNT(1) AS count_num
                                        FROM pur_purchase_warehouse_abnormal AS n
                                        LEFT JOIN pur_purchase_suggest_map AS m ON n.pur_number=m.purchase_number AND n.sku=m.sku
                                        LEFT JOIN pur_purchase_suggest AS s ON s.demand_number=m.demand_number
                                        LEFT JOIN pur_purchase_order AS o ON o.purchase_number=n.pur_number
                                        WHERE is_handler = 0
                                        AND s.warehouse_code NOT IN('HMZZC-TS', 'NBZZC-TS') 
                                        AND n.pull_time BETWEEN '2020-01-01 00:00:00' AND NOW()
                                        {$buyer_id_sub_query}
                                        GROUP BY o.buyer_id;";

                    break;

                case 'tax_refund_warehouse_not_processed':// 退税仓未处理
                    $query_sql_1 = "SELECT o.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_warehouse_abnormal AS n
                                LEFT JOIN pur_purchase_suggest_map AS m ON n.pur_number=m.purchase_number AND n.sku=m.sku
                                LEFT JOIN pur_purchase_suggest AS s ON s.demand_number=m.demand_number
                                LEFT JOIN pur_purchase_order AS o ON o.purchase_number=n.pur_number
                                WHERE is_handler = 0
                                AND s.warehouse_code IN('HMZZC-TS', 'NBZZC-TS') 
                                AND n.pull_time BETWEEN '2020-01-01 00:00:00' AND NOW()
                                {$buyer_id_sub_query}
                                GROUP BY o.buyer_id;";
                    break;

                case 'transit_warehouse_not_processed':// 中转仓未处理
                    $query_sql_1 = "SELECT o.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_warehouse_abnormal AS n
                                LEFT JOIN pur_purchase_suggest_map AS m ON n.pur_number=m.purchase_number AND n.sku=m.sku
                                LEFT JOIN pur_purchase_suggest AS s ON s.demand_number=m.demand_number
                                LEFT JOIN pur_purchase_order AS o ON o.purchase_number=n.pur_number
                                WHERE is_handler = ' 0'
                                AND s.warehouse_code NOT IN('HMZZC-TS', 'NBZZC-TS') 
                                AND n.pull_time BETWEEN '2020-01-01 00:00:00' AND NOW()
                                GROUP BY o.buyer_id;";
                    break;

                case 'amount_of_1688_is_abnormal':// 1688金额异常
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE A.is_ali_price_abnormal=1
                                AND (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";

                    break;

                case '1688_closed_system_not_finished':// 1688关闭，系统未完结
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE A.purchase_order_status IN(7,10,2,15)
                                AND A.ali_order_status='交易取消'
                                AND (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";

                    break;

                case 'portal_system_waits_supplier_confirm_sku':// 门户系统待供应商确sku
                    $query_sql_1 = "SELECT a.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS a
                                INNER JOIN pur_purchase_order_items AS b ON a.purchase_number = b.purchase_number
                                LEFT JOIN pur_supplier_web_audit AS swa ON swa.purchase_number = b.purchase_number AND b.sku=swa.sku 
                                WHERE a.purchase_number NOT LIKE 'YPO%' 
                                AND a.purchase_order_status IN (6,7,2,15) 
                                AND a.is_gateway=1 
                                AND swa.audit_status=1 
                                AND (a.purchase_type_id=2 OR a.purchase_type_id=6)
                                {$buyer_id_sub_query}
                                GROUP BY a.buyer_id";
                    break;
            }

            if(empty($query_sql_1)){
                return ['flag' => false, 'msg' => $item['column_name'].' SQL 缺失'];
            }

            $buyer_count_num_list = $this->purchase_db->query($query_sql_1)->result_array();
            $buyer_count_num_list = array_column($buyer_count_num_list, 'count_num', 'buyer_id');

            // 聚合数据
            $buyer_ids = array_keys($buyer_count_num_list);
            foreach($buyer_ids as $buyer_id){
                $statistics_user_data[$column_key][$buyer_id]['buyer_id']       = $buyer_id;
                $statistics_user_data[$column_key][$buyer_id]['count_num']      = isset($buyer_count_num_list[$buyer_id]) ? $buyer_count_num_list[$buyer_id] : 0;
            }
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'abnormal_follow_up';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-异常跟进-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-异常跟进-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 海外仓  -  驳回需处理
     * @return array
     */
    public function oversea_rejected_need_deal_with(){
        $module = 'rejected_need_deal_with';
        $column_list = [
            'po_rejected_for_inquiry'                => ['column_name'=>'待询价被驳回po','field_name'=>'A.buyer_id'],
            'the_po_was_rejected'                    => ['column_name'=>'请款被驳回po','field_name'=>'A.buyer_id'],
            'ecn_changes_are_rejected'               => ['column_name'=>'ECN变更被驳回','field_name'=>'ps.buyer_id'],
            'refund_rejected_by_financial_staff'     => ['column_name'=>'退款财务驳回','field_name'=>'A.buyer_id'],
            'inspection_exemption_shall_be_rejected' => ['column_name'=>'验货免检驳回','field_name'=>'buyer_id'],
            'supplier_modification_is_rejected'      => ['column_name'=>'供应商修改被驳回','field_name'=>'b.create_user_id'],
            'product_management_was_rejected'        => ['column_name'=>'产品管理被驳回','field_name'=>'create_user_id'],
        ];

        $statistics_user_data = [];
        foreach($column_list as $column_key => $item){

            $buyer_id_sub_query = '';
            //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
            if ($this->_need_permission) {
                $buyer_id_sub_query = $this->_oversea_authorization_user($module,$column_key,$item['field_name']);// 拼接 查询用户权限SQL
                if ('no_refreshable_users' == $buyer_id_sub_query) {
                    return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
                }
            }

            $query_sql_1 = '';
            switch($column_key){
                case 'po_rejected_for_inquiry':// 待询价被驳回po
                    $query_sql_1          = "SELECT A.buyer_id,COUNT(1) AS count_num
                                        FROM pur_purchase_order AS A
                                        WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                        AND A.purchase_order_status=1
                                        AND A.audit_time != '0000-00-00 00:00:00'
                                        {$buyer_id_sub_query}
                                        GROUP BY A.buyer_id;";
                    break;
                case 'the_po_was_rejected':// 请款被驳回po
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                AND A.pay_status IN(26,21,31,64,64,67)
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";
                    break;
                case 'ecn_changes_are_rejected':// ECN变更被驳回
                    $res = $this->_get_ecn_data(DAC_GROUP_TYPE_OVERSEA,$module,'ecn_changes_are_rejected',3);
                    if (!$res['flag']) {
                        return ['flag' => FALSE, 'msg' => $res['msg']];
                    }
                    continue 2;//跳出switch，跳过当前循环，无需执行以下操作
                    break;
                case 'refund_rejected_by_financial_staff':// 退款财务驳回
                    $query_sql_1 = "SELECT buyer_id,COUNT(1)  AS count_num
                            FROM (
                            
                                SELECT A.buyer_id,A.purchase_number
                                FROM pur_purchase_order AS A
                                INNER JOIN pur_purchase_order_cancel_detail AS B ON A.purchase_number=B.purchase_number
                                INNER JOIN pur_purchase_order_cancel AS C ON C.id=B.cancel_id
                                WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                AND C.audit_status IN(40)
                                {$buyer_id_sub_query}
                                GROUP BY A.purchase_number
                            
                            ) AS tmp 
                            GROUP  BY buyer_id;";
                    break;
                case 'inspection_exemption_shall_be_rejected':// 验货免检驳回
                    $query_sql_1 = "SELECT buyer_id,COUNT(1) AS count_num
                                FROM pur_supplier_check 
                                WHERE status=4
                                AND purchase_type_id=2
                                {$buyer_id_sub_query}
                                GROUP BY buyer_id;";
                    break;
                case 'supplier_modification_is_rejected':// 供应商修改被驳回
                    $sub_query = "SELECT b.create_user_id AS buyer_id
                             FROM pur_supplier a
                             JOIN pur_supplier_update_log b ON a.supplier_code=b.supplier_code AND b.audit_status IN(2,4,6)
                             WHERE a.audit_status IN(2,4,6)
                             {$buyer_id_sub_query}
                             GROUP BY a.id";
                    $query_sql_1 = "SELECT tmp.buyer_id,COUNT(1) AS count_num FROM($sub_query) AS tmp GROUP BY tmp.buyer_id";
                    break;
                case 'product_management_was_rejected':// 产品管理被驳回
                    $query_sql_1 = "SELECT create_user_id AS buyer_id,COUNT(1) AS count_num
                                FROM pur_product_update_log
                                WHERE audit_status = 4 
                                {$buyer_id_sub_query}
                                GROUP BY create_user_id,sku;";
                    break;
            }

            if(empty($query_sql_1)){
                return ['flag' => false, 'msg' => $item['column_name'].' SQL 缺失'];
            }
            $buyer_count_num_list = $this->purchase_db->query($query_sql_1)->result_array();
            $buyer_count_num_list = array_column($buyer_count_num_list, 'count_num', 'buyer_id');

            // 聚合数据
            $buyer_ids = array_keys($buyer_count_num_list);
            foreach($buyer_ids as $buyer_id){
                $statistics_user_data[$column_key][$buyer_id]['buyer_id']       = $buyer_id;
                $statistics_user_data[$column_key][$buyer_id]['count_num']      = isset($buyer_count_num_list[$buyer_id]) ? $buyer_count_num_list[$buyer_id] : 0;
            }
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'rejected_need_deal_with';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-驳回需处理-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-驳回需处理-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 海外仓  -  请款跟进
     * @return array
     */
    public function oversea_pay_follow_up(){
        $module = 'pay_follow_up';
        $column_list = [
            'can_please_the_po' => ['column_name' => '可请款po', 'field_name' => 'A.buyer_id'],
            'rpc_non_account_period' => ['column_name' => '可请款对公po-非账期', 'field_name' => 'A.buyer_id'],
            'rpc_account_period' => ['column_name' => '可请款对公po-账期', 'field_name' => 'A.buyer_id'],
            'rppc_non_account_period' => ['column_name' => '可请款对私合同po-非账期', 'field_name' => 'A.buyer_id'],
            'rppc_account_period' => ['column_name' => '可请款对私合同po-账期', 'field_name' => 'A.buyer_id'],
            'po_can_be_requested' => ['column_name' => '可请款网采po', 'field_name' => 'A.buyer_id'],
            'po_was_rejected' => ['column_name' => '请款被驳回PO', 'field_name' => 'A.buyer_id'],
            'po_was_paid_yesterday' => ['column_name' => '昨日已付款PO', 'field_name' => 'buyer_id'],

        ];
        //获取结算方式,结算方式=1688账期
        $account_type = $this->_get_account_type(33);
        $account_type = !empty($account_type) ? implode(',',$account_type) : 0;

        $statistics_user_data = [];
        foreach($column_list as $column_key => $item){

            $buyer_id_sub_query = '';
            //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
            if ($this->_need_permission) {
                $buyer_id_sub_query = $this->_oversea_authorization_user($module,$column_key,$item['field_name']);// 拼接 查询用户权限SQL
                if ('no_refreshable_users' == $buyer_id_sub_query) {
                    return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
                }
            }

            $query_sql_1 = '';
            switch($column_key){
                case 'can_please_the_po':// 可请款po
                    $query_sql_1          = "SELECT A.buyer_id,COUNT(1) AS count_num
                                        FROM pur_purchase_order AS A
                                        WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                        AND A.purchase_order_status IN(9,11)
                                        AND A.pay_status=10
                                        {$buyer_id_sub_query}
                                        GROUP BY A.buyer_id;";
                    break;
                case 'rpc_non_account_period':// 可请款对公po-非账期
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                AND A.source=1
                                AND A.pay_type=2
                                AND A.account_type IN({$account_type})
                                AND A.purchase_order_status IN(2,7,9,10,11,15)
                                AND A.pay_status IN(10,50)
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";
                    break;
                case 'rpc_account_period':// 可请款对公po-账期
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                AND A.source=1
                                AND A.pay_type=2
                                AND A.account_type IN({$account_type})
                                AND A.purchase_order_status IN(2,9,11,12,13,15)
                                AND A.pay_status IN(10,50)
                                AND A.completion_time > '0000-00-00 00:00:00'
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";
                    break;
                case 'rppc_non_account_period':// 可请款对私合同po-非账期
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                AND A.source=1
                                AND A.pay_type=3
                                AND A.account_type IN({$account_type})
                                AND A.purchase_order_status IN(2,7,9,10,11,15)
                                AND A.pay_status IN(10,50)
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";
                    break;
                case 'rppc_account_period':// 可请款对私合同po-账期
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                AND A.source=1
                                AND A.pay_type=3
                                AND A.account_type IN({$account_type})
                                AND A.purchase_order_status IN(2,9,11,12,13,15)
                                AND A.pay_status IN(10,50)
                                AND A.completion_time > '0000-00-00 00:00:00'
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";
                    break;
                case 'po_can_be_requested':// 可请款网采po
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                AND A.source=2
                                AND A.purchase_order_status IN(9,11)
                                AND A.pay_finish_status IN(1,3)
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";
                    break;
                case 'po_was_rejected':// 请款被驳回PO
                    $query_sql_1 = "SELECT A.buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_order AS A
                                WHERE (A.purchase_type_id=2 OR A.purchase_type_id=6)
                                AND A.pay_status IN(26,21,31,64,64,67)
                                {$buyer_id_sub_query}
                                GROUP BY A.buyer_id;";
                    break;
                case 'po_was_paid_yesterday':// 昨日已付款PO
                    //付款时间=昨天
                    $date = date('Y-m-d', strtotime('-1 day'));
                    $query_sql_1 = "SELECT buyer_id, COUNT(1) AS count_num
                                FROM pur_purchase_order
                                WHERE source = 1
                                AND (purchase_type_id=2 OR purchase_type_id=5)
                                AND  purchase_number NOT LIKE 'YPO%' ESCAPE '!'
                                AND pay_status = 51
                                AND pay_time >= '" . $date . ' 00:00:00' . "'
                                AND pay_time <= '" . $date . ' 23:59:59' . "'
                                {$buyer_id_sub_query}
                                GROUP BY buyer_id;";
                    break;
            }
            if(empty($query_sql_1)){
                return ['flag' => false, 'msg' => $item['column_name'].' SQL 缺失'];
            }
            $buyer_count_num_list = $this->purchase_db->query($query_sql_1)->result_array();
            $buyer_count_num_list = array_column($buyer_count_num_list, 'count_num', 'buyer_id');

            // 聚合数据
            $buyer_ids = array_keys($buyer_count_num_list);
            foreach($buyer_ids as $buyer_id){
                $statistics_user_data[$column_key][$buyer_id]['buyer_id']       = $buyer_id;
                $statistics_user_data[$column_key][$buyer_id]['count_num']      = isset($buyer_count_num_list[$buyer_id]) ? $buyer_count_num_list[$buyer_id] : 0;
            }
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'pay_follow_up';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-请款跟进-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-请款跟进-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 海外仓  -  退款跟进
     * @return array
     */
    public function oversea_refund_follow_up(){
        $module = 'refund_follow_up';
        $column_list = [
            'waiting_uploaded'               => '待上传截图',
            'financial_rejection_po'         => '财务驳回po',
            'financial_receivables_po'       => '待财务收款po',
            '1688_returned_waiting_uploaded' => '1688已退，待上传截图',
            'contract_is_subject_to_refund'  => '合同单待退款',
        ];

        $buyer_id_sub_query = '';
        //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
        if ($this->_need_permission) {
            $buyer_id_sub_query = $this->_oversea_authorization_user($module,null,"A.create_user_id");// 拼接 查询用户权限SQL
            if ('no_refreshable_users' == $buyer_id_sub_query) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
        }

        $query_sql  = "SELECT A.create_user_id AS buyer_id,A.cancel_number,A.audit_status,GROUP_CONCAT(DISTINCT E.pai_number) AS pai_number,SUM(E.apply_amount) AS apply_amount,D.source
                    FROM pur_purchase_order_cancel AS A
                    INNER JOIN pur_purchase_order_cancel_detail AS B ON A.id=B.cancel_id
                    LEFT JOIN pur_purchase_order_cancel_to_receipt AS C ON A.id=C.cancel_id
                    LEFT JOIN pur_purchase_order AS D ON B.purchase_number=D.purchase_number
                    LEFT JOIN pur_purchase_order_pay_type AS E ON E.purchase_number=D.purchase_number
                    WHERE A.audit_status IN(50,40,20)
                    {$buyer_id_sub_query}
                    GROUP BY A.cancel_number;";
        $order_list = $this->purchase_db->query($query_sql)->result_array();


        $statistics_user_data = [];

        foreach ($order_list as $value) {
            $buyer_id = $value['buyer_id'];

            if ($value['audit_status'] == 50) {// 待上传截图
                $data_type = 'waiting_uploaded';
                if (isset($statistics_user_data[$data_type][$buyer_id]['count_num'])) {
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] += 1;
                } else {
                    $statistics_user_data[$data_type][$buyer_id]['buyer_id'] = $buyer_id;
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] = 1;
                }
            }
            if ($value['audit_status'] == 40) {// 财务驳回po
                $data_type = 'financial_rejection_po';
                if (isset($statistics_user_data[$data_type][$buyer_id]['count_num'])) {
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] += 1;
                } else {
                    $statistics_user_data[$data_type][$buyer_id]['buyer_id'] = $buyer_id;
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] = 1;
                }
            }
            if ($value['audit_status'] == 20) {// 待财务收款po
                $data_type = 'financial_receivables_po';
                if (isset($statistics_user_data[$data_type][$buyer_id]['count_num'])) {
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] += 1;
                } else {
                    $statistics_user_data[$data_type][$buyer_id]['buyer_id'] = $buyer_id;
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] = 1;
                }
            }
            if ($value['audit_status'] == 50 and !empty($value['pai_number']) and $value['apply_amount'] != 0) {// 1688已退，待上传截图
                $data_type = '1688_returned_waiting_uploaded';
                if (isset($statistics_user_data[$data_type][$buyer_id]['count_num'])) {
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] += 1;
                } else {
                    $statistics_user_data[$data_type][$buyer_id]['buyer_id'] = $buyer_id;
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] = 1;
                }
            }
            if ($value['audit_status'] == 20 && 1 == $value['source']) {// 合同单待退款
                $data_type = 'contract_is_subject_to_refund';
                if (isset($statistics_user_data[$data_type][$buyer_id]['count_num'])) {
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] += 1;
                } else {
                    $statistics_user_data[$data_type][$buyer_id]['buyer_id'] = $buyer_id;
                    $statistics_user_data[$data_type][$buyer_id]['count_num'] = 1;
                }
            }
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'refund_follow_up';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-退款跟进-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-退款跟进-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 海外仓  -  验货跟进
     * @return array
     */
    public function oversea_check_goods_follow_up(){
        $module = 'check_goods_follow_up';
        $column_list = [
            'pending_purchase_confirmation'        => '待采购确认',
            'pending_confirmed_by_quality_control' => '待品控确认',
            'exempted_from_inspection_to_reject'   => '免检驳回',
            'nonconformity_to_be_confirmed'        => '不合格待确认',
            'inspection_is_not_up_to_standard'     => '验货不合格',
        ];

        $buyer_id_sub_query = '';
        //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
        if ($this->_need_permission) {
            $buyer_id_sub_query = $this->_oversea_authorization_user($module,null,"buyer_id");// 拼接 查询用户权限SQL
            if ('no_refreshable_users' == $buyer_id_sub_query) {
                return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
            }
        }

        $query_sql  = "SELECT status,buyer_id,count(1) as count_num
                    FROM pur_supplier_check 
                    WHERE 1 
                    {$buyer_id_sub_query}
                    GROUP BY buyer_id ,status;";
        $order_list = $this->purchase_db->query($query_sql)->result_array();
        $order_list = arrayKeyToColumn($order_list, 'buyer_id');

        $statistics_user_data = [];

        foreach($order_list as $buyer_id => $value){
            $data_type = '';
            if($value['status'] == 1){// 待采购确认
                $data_type                                  = 'pending_purchase_confirmation';
            }
            if($value['status'] == 2){// 待品控确认
                $data_type                                  = 'pending_confirmed_by_quality_control';
            }
            if($value['status'] == 3){// 免检驳回
                $data_type                                  = 'exempted_from_inspection_to_reject';
            }
            if($value['status'] == 6){// 不合格待确认
                $data_type                                  = 'nonconformity_to_be_confirmed';
            }
            if($value['status'] == 11){// 验货不合格
                $data_type                                  = 'inspection_is_not_up_to_standard';
            }

            if($data_type){
                $statistics_user_data[$data_type][$buyer_id]['buyer_id'] = $buyer_id;
                $statistics_user_data[$data_type][$buyer_id]['count_num'] = $value['count_num'];
            }
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'check_goods_follow_up';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-验货跟进-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-验货跟进-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;

    }

    /**
     * 海外仓  -  产品修改
     * @return array
     */
    public function oversea_product_modification(){
        $module = 'product_modification';
        $column_list = [
            'ecn_data_under_change'                                 => ['column_name'=>'ECN资料变更中','field_name'=>''],
            'sku_product_information_modification_is_not_completed' => ['column_name'=>'SKU产品信息修改未完成','field_name'=>'a.create_user_id'],
            'sku_shield_application_to_be_reviewed'                 => ['column_name'=>'SKU屏蔽申请待审核','field_name'=>'scree.apply_user_id'],
        ];

        $statistics_user_data = [];
        foreach($column_list as $column_key => $item){

            $buyer_id_sub_query = '';
            //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
            if ($this->_need_permission) {
                $buyer_id_sub_query = $this->_oversea_authorization_user($module,$column_key,$item['field_name']);// 拼接 查询用户权限SQL
                if ('no_refreshable_users' == $buyer_id_sub_query) {
                    return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
                }
            }

            $query_sql_1 = '';
            switch($column_key){
                case 'ecn_data_under_change':// ECN资料变更中
                    $res = $this->_get_ecn_data(DAC_GROUP_TYPE_OVERSEA,$module,'ecn_data_under_change',2);
                    if (!$res['flag']) {
                        return ['flag' => FALSE, 'msg' => $res['msg']];
                    }
                    continue 2;//跳出switch，跳过当前循环，无需执行以下操作
                    break;
                case 'sku_product_information_modification_is_not_completed':// SKU产品信息修改未完成
                    $query_sql_1 = "SELECT a.create_user_id AS buyer_id,COUNT(1) AS count_num
                                FROM pur_product_update_log a
                                LEFT JOIN pur_product b ON a.sku=b.sku
                                WHERE a.audit_status <> 3
                                {$buyer_id_sub_query}
                                GROUP BY a.create_user_id;";
                    break;
                case 'sku_shield_application_to_be_reviewed':// SKU屏蔽申请待审核
                    $query_sql_1 = "SELECT scree.apply_user_id AS buyer_id,COUNT(1) AS count_num
                                FROM pur_product_scree AS scree
                                LEFT JOIN pur_product AS product ON scree.sku=product.sku
                                WHERE scree.status=10 
                                {$buyer_id_sub_query}
                                GROUP BY scree.apply_user_id;";
                    break;
            }
            if(empty($query_sql_1)){
                return ['flag' => false, 'msg' => $item['column_name'].' SQL 缺失'];
            }
            $buyer_count_num_list = $this->purchase_db->query($query_sql_1)->result_array();
            $buyer_count_num_list = array_column($buyer_count_num_list, 'count_num', 'buyer_id');

            // 聚合数据
            $buyer_ids = array_keys($buyer_count_num_list);
            foreach($buyer_ids as $buyer_id){
                $statistics_user_data[$column_key][$buyer_id]['buyer_id']       = $buyer_id;
                $statistics_user_data[$column_key][$buyer_id]['count_num']      = isset($buyer_count_num_list[$buyer_id]) ? $buyer_count_num_list[$buyer_id] : 0;
            }
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'product_modification';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-产品修改-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-产品修改-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 海外仓  -  门户对账发票
     * @return array
     */
    public function oversea_gateway_statement_invoice(){
        $module = 'gateway_statement_invoice';
        $column_list = [
            'portal_reconciliation_has_not_been_confirmed' => ['column_name'=>'门户对账未确认','field_name'=>'create_user_id'],
            'portal_invoice_has_not_been_replied'          => ['column_name'=>'门户的发票未回复','field_name'=>'a.purchase_user_id'],
            'portal_invoice_has_not_been_confirmed'        => ['column_name'=>'门户的发票未确认','field_name'=>'c.purchase_user_id'],
        ];

        $statistics_user_data = [];
        foreach($column_list as $column_key => $item){

            $buyer_id_sub_query = '';
            //用户手动刷新时需要控制数据权限;定时任务刷数据时，不需要限制数据权限
            if ($this->_need_permission) {
                $buyer_id_sub_query = $this->_oversea_authorization_user($module,$column_key,$item['field_name']);// 拼接 查询用户权限SQL
                if ('no_refreshable_users' == $buyer_id_sub_query) {
                    return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
                }
            }

            $query_sql_1 = '';
            switch($column_key){
                case 'portal_reconciliation_has_not_been_confirmed':// 门户对账未确认
                    $query_sql_1 = "SELECT create_user_id AS buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_statement 
                                WHERE supplier_is_gateway=1
                                AND (purchase_type_id=2 OR purchase_type_id=6)
                                AND statement_pdf_status IN(10,20,25)
                                {$buyer_id_sub_query}
                                GROUP BY create_user_id;";
                    break;
                case 'portal_invoice_has_not_been_replied':// 门户的发票未回复
                    $query_sql_1 = "SELECT a.purchase_user_id AS buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_invoice_list a
                                INNER JOIN pur_purchase_invoice_item c ON a.invoice_number = c.invoice_number
                                INNER JOIN pur_purchase_order d ON c.purchase_number=d.purchase_number
                                WHERE a.is_gateway = 1
                                AND a.audit_status = 2
                                AND d.purchase_type_id=2
                                {$buyer_id_sub_query}
                                GROUP BY a.purchase_user_id;";
                    break;
                case 'portal_invoice_has_not_been_confirmed':// 门户的发票未确认
                    $query_sql_1 = "SELECT c.purchase_user_id AS buyer_id,COUNT(1) AS count_num
                                FROM pur_purchase_items_invoice_info b
                                LEFT JOIN pur_purchase_invoice_item a ON a.invoice_number=b.invoice_number AND a.purchase_number =b.purchase_number AND a.sku = b.sku
                                LEFT JOIN pur_purchase_invoice_list c ON b.invoice_number=c.invoice_number
                                LEFT JOIN pur_purchase_compact_items e ON b.purchase_number = e.purchase_number
                                LEFT JOIN pur_purchase_compact d ON e.compact_number = d.compact_number
                                WHERE  c.is_gateway = 1
                                AND b.audit_status = 2
                                AND c.audit_status IN(3, 6)
                                AND e.bind = 1
                                AND d.source=2
                                {$buyer_id_sub_query}
                                GROUP BY c.purchase_user_id;";
                    break;
            }

            if(empty($query_sql_1)){
                return ['flag' => false, 'msg' => $item['column_name'].' SQL 缺失'];
            }
            $buyer_count_num_list = $this->purchase_db->query($query_sql_1)->result_array();
            $buyer_count_num_list = array_column($buyer_count_num_list, 'count_num', 'buyer_id');


            // 聚合数据
            $buyer_ids = array_keys($buyer_count_num_list);
            foreach($buyer_ids as $buyer_id){
                $statistics_user_data[$column_key][$buyer_id]['buyer_id']       = $buyer_id;
                $statistics_user_data[$column_key][$buyer_id]['count_num']      = isset($buyer_count_num_list[$buyer_id]) ? $buyer_count_num_list[$buyer_id] : 0;
            }
        }

        // 保存到 mongodb 数据库
        try{
            $module = 'gateway_statement_invoice';
            if($statistics_user_data){
                foreach($statistics_user_data as $type => $value){
                    $field_name = array_diff(array_keys(current($value)),['buyer_id']);
                    $this->_save_data($value, DAC_GROUP_TYPE_OVERSEA, $module, $type, $field_name);
                }
            }else{
                throw new Exception('海外仓-门户对账发票-没有刷新任何数据');
            }

            $return = ['flag' => true, 'msg' => '海外仓-门户对账发票-数据汇总成功'];
        }catch(Exception $e){
            $return = ['flag' => false, 'msg' => $e->getMessage()];
        }

        return $return;
    }

    /**
     * 过滤可刷新的采购员数据（10分钟内数据不再查询更新）
     * @param int    $category_id
     * @param string $module
     * @param string $type
     * @param array  $authorization_user
     * @return array|bool
     */
    private function _get_refreshable_users($category_id, $module, $type = NULL, $authorization_user = [])
    {
        $where = [
            'category_id' => (string)$category_id,
            'module' => $module,
        ];
        if (!is_null($type)) {
            $where['type'] = $type;
        }

        if (!empty($authorization_user)) {
            //仅拥有部分权限的用户，根据拥有相应采购员去获取数据
            $res = $this->mongo_db->select(['buyer_id', 'update_time'])
                ->where($where)
                ->where_in('buyer_id', $authorization_user)
                ->get('work_desk_data_summary');
            if(empty($res)){
                return $authorization_user;
            }
        } else {
            //采购系统角色和超级组用户，获取模块内对应类型的全部数据
            $res = $this->mongo_db->select(['buyer_id', 'update_time'])
                ->where($where)
                ->get('work_desk_data_summary');
            if(empty($res)){
                return TRUE;
            }
        }
        //仅当存在已统计相关采购员数据时，才过滤时间范围内的数据
        //判断数据最近是否更新已更新，10分钟内数据不再查询更新
        foreach ($res as $item) {
            //剔除掉更新时间还在10钟内的id
            if (bcsub(time(), strtotime($item->update_time)) < 600) {
                arrayDelElementByVal($authorization_user, $item->buyer_id);
            }
        }
        return $authorization_user;
    }

    /**
     * 新增MongoDB数据
     * @param int    $category_id 小组类型（1-非海外组，2-海外组）
     * @param int    $buyer_id    采购员id
     * @param string $module      模块
     * @param string $type        类型
     * @param array  $data        统计结果数据
     * @return mixed
     */
    private function _insert_mongodb($category_id, $buyer_id, $module, $type, $data)
    {
        $insert_data = array(
            'category_id' => (string)$category_id,
            'buyer_id' => (string)$buyer_id,
            'module' => $module,
            'type' => $type,
            'data' => json_encode($data, 320),
            'update_time' => date('Y-m-d H:i:s'),
        );
        return $this->mongo_db->insert('work_desk_data_summary', $insert_data);
    }


    /**
      * 性能调试代码
     **/

    private function _insert_logs($datas=[]){

        return $this->mongo_db->insert('logs', $datas);
    }

    /**
     * 获取结算方式
     * @param int $account_type
     * @return array|bool
     */
    private function _get_account_type($account_type)
    {
        //结算方式
        $account_type = $this->purchase_db->select('settlement_code')
            ->where('parent_id', $account_type)
            ->or_where('settlement_code', $account_type)
            ->get($this->table_supplier_settlement)
            ->result_array();
        if (empty($account_type)) {
            return [];
        } else {
            return array_column($account_type, 'settlement_code');
        }
    }

    /**
     * 保存数据
     * @param array  $result
     * @param int    $category_id 小组类型（1-非海外组，2-海外组）
     * @param string $module 模块
     * @param string $type 类型
     * @param array $field_name
     * @throws Exception
     */
    private function _save_data($result, $category_id, $module, $type, $field_name = ['count_num'])
    {
        try{
            foreach ($result as $item) {
                //查询采购员是否存在数据，存在则更新数据，否则新增数据
                $where = ['category_id' => (string)$category_id, 'buyer_id' => (string)$item['buyer_id'], 'module' => $module, 'type' => $type];
                $res = $this->mongo_db->select('update_time')->where($where)->get('work_desk_data_summary');
                //插入MongoDb的data数据
                $_data = [];
                foreach ($field_name as $field) {
                    $_data[$field] = $item[$field];
                }
                if (!empty($res)) {//更新操作
                    $data = array('data' => json_encode($_data, 320), 'update_time' => date('Y-m-d H:i:s'));
                    //根据条件，更新MongoDB数据
                    $this->mongo_db->where($where)->update('work_desk_data_summary', $data);
                } else {//新增操作
                    $res = $this->_insert_mongodb($category_id, $item['buyer_id'], $module, $type, $_data);
                    if (!$res) {
                        throw new Exception('写入MongoDb失败[' . $type . ']');
                    }
                }
            }
        }catch (Exception $e){
            //异常写入操作日志表
            operatorLogInsert(
                array(
                    'id' => $category_id . '_' . $module . '_' . $type,
                    'type' => 'WORK_DESK_ALL_BUYER_DATA_SUMMARY',
                    'content' => '工作台数据汇总保存异常',
                    'detail' => getActiveUserName() .':'. $category_id . '_' . $module . '_' . $type,
                    'user' => !empty(getActiveUserName()) ? getActiveUserName() : '计划任务',
                ),
                'pur_work_desk_log'
            );
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 根据采购员工号，查询产品系统ENC数量
     * @param int $category_id 小组类型（1-非海外仓组，2-海外仓组）
     * @param string $module 模块
     * @param string $type 模块指标类型
     * @param int $opera_status 查询类型（2-ECN资料变更中，3-ECN变更被驳回）
     * @return array
     */
    private function _get_ecn_data($category_id, $module, $type, $opera_status)
    {
        try {
            if($this->_need_permission){
                //获取权限
                $authorization_user = $this->User_group_model->get_work_desk_authorization($category_id, getActiveUserId());
                //过滤可刷新的采购员数据
                $authorization_user = $this->_get_refreshable_users($category_id, $module, $type,$authorization_user);
                if (empty($authorization_user) OR TRUE === $authorization_user) {
                    return ['flag' => TRUE, 'msg' => $this->_no_data_msg];
                }
            }else{
                //获取权限(定时任务，获取指定小组类型下的采购员id，指定参数system)
                $authorization_user = $this->User_group_model->get_work_desk_authorization($category_id, 0,'system');
            }

            //根据采购员id获取采购员工号
            $user_number_data = [];
            foreach ($authorization_user as $user_id) {
                $user_number_data[$user_id] = getUserNumberById($user_id);
            }

            //调用产品系统接口，获取数据
            $params = ['userNumber' => array_values(array_unique(array_filter($user_number_data))), 'operaStatus' => $opera_status];
            $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdDataChangeApp');
            $access_token = getOASystemAccessToken();
            $request_url = $request_url . '?access_token=' . $access_token;
            $result = getCurlData($request_url, json_encode($params), 'post', ['Content-Type: application/json']);
            $result = json_decode($result, TRUE);

            //请求接口日志
            operatorLogInsert(
                array(
                    'id' => $category_id . '_' . $module . '_' . $type,
                    'type' => 'WORK_DESK_ECN_API',
                    'content' => 'ECN接口请求结果',
                    'detail' => ['request' => $params, 'result' => $result],
                    'user' => !empty(getActiveUserName()) ? getActiveUserName() : '计划任务',
                ),
                'pur_work_desk_log'
            );

            //提示消息
            if(1==$category_id && 3==$opera_status){
                $msg = '非海外仓-ECN变更被驳回';
            }elseif(2==$category_id && 3==$opera_status){
                $msg = '海外仓-ECN变更被驳回';
            }else{
                $msg = '海外仓-ECN资料变更中';
            }
            //请求结果数据处理
            if (isset($result['code']) && $result['code'] == 200) {
                //没有可处理的数据
                if(empty($result['data'])){
                    return ['flag' => TRUE, 'msg' => $msg . '没有可汇总的数据'];
                }

                //处理要保存的数据
                $result['data'] = array_column($result['data'],'count','createUser');
                $user_id_data = array_flip($user_number_data);
                $save_data = [];
                foreach ($result['data'] as $user_number => $count){
                    $save_data[] = [
                        'buyer_id' => $user_id_data[$user_number],
                        'count_num' => $count,
                    ];
                }

                //结果数据写入MongoDb
                $this->_save_data($save_data, $category_id, $module, $type);
                $return = ['flag' => TRUE, 'msg' => $msg . '-数据汇总成功'];
            } else {
                throw new Exception($msg . ':' . $result['msg']);
            }
        } catch (Exception $e) {
            $return = ['flag' => FALSE, 'data' => [], 'msg' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * 按小组类型和模块删除数据
     * @param int    $category_id 小组类型（1-非海外仓，2-海外仓）
     * @param string $module 模块标识
     * @return mixed
     */
    public function delete_data($category_id, $module)
    {
        try {
            if (empty($category_id) or empty($module)) {
                return ['flag' => FALSE, 'msg' => '参数缺失'];
            }
            //默认删除条件
            $this->mongo_db->where(['category_id' => (string)$category_id, 'module' => $module]);
            //页面上，用户手动刷新数据时，删除10分钟前的数据
            if ($this->_need_permission) {
                //删除10分钟前的数据
                $this->mongo_db->where_lt('update_time', date('Y-m-d H:i:s', strtotime('-10 minute')));
                //空，代表为admin或超级用户权限，则按小组类型和模块进行删除
                if (!empty($this->_authorization_user)) {
                    $this->mongo_db->where_in('buyer_id', $this->_authorization_user);
                }
            }
            $res = $this->mongo_db->delete_all('work_desk_data_summary');
            $result = ['flag' => TRUE, 'msg' => ''];
        } catch (Exception $e) {
            $result = ['flag' => FALSE, 'msg' => $e->getMessage()];
        }
        return $result;
    }

}