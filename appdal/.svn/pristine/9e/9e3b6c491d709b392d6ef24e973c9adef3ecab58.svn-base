<?php
require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 发运管理-计划部取消
 *
 */
class PlanCancelListService extends AbstractList
{
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'id desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Shipment_cancel_list_model', 'm_shipment_cancel', false, 'purchase_shipment');
        $this->_ci->load->model('Purchase_suggest_model', 'm_purchase_suggest', false, 'purchase_suggest');
        $this->_ci->load->helper('status_order');
        $this->_ci->lang->load('common_lang');
        return $this;
    }

    /**
     *
     * @param string $gids 页面选择的gids，为空表示从搜索条件获取
     * @param string $profile 用户选择导出的列
     * @param string $format_type 导出csv的格式， 可读还是用于修改的原生字段
     * @throws \RuntimeException
     * @throws \OverflowException
     * @return unknown
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8' )
    {
        $db = $this->_ci->m_shipment_cancel->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_shipment_cancel->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PURCHASE_SHIPMENT_PLAN_CANCEL_SEARCH_EXPORT)->get();
            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);

            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }

            if ($total > MAX_EXCEL_LIMIT)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出更大数量的数据，请找相关负责人', MAX_EXCEL_LIMIT), 500);
                $quick_sql .= ' limit '.MAX_EXCEL_LIMIT;
            }

            if ($total > MAX_BROWSE_LIMIT)
            {
                //强制转文件模式
                $data_type = VIEW_FILE;
            }
            else
            {
                if ($data_type == VIEW_AUTO)
                {
                    $data_type = VIEW_BROWSER;
                }
            }
        }
        $this->_ci->load->classes('purchase/classes/ApplyReturnGoodsExport');
        $this->_ci->ApplyReturnGoodsExport
            ->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
        return $this->_ci->ApplyReturnGoodsExport->run();

    }

    /**
     * 列表都需要按照key值配置服务， 这里可以放到配置文件
     *
     * {@inheritDoc}
     * @see Listable::get_cfg()
     */
    public function get_cfg() : array
    {
        $search = [
            /**
             * 采购员
             */
            'buyer_id' => [
                'desc'     => '采购员',
                'name'     => 'buyer_id',
                'type'     => 'strval',
            ],
            /**
             * 备货单号
             */
            'demand_number'        => [
                'desc'     => '备货单号',
                'name'     => 'demand_number',
                'type'     => 'strval',
            ],
            /**
             * 采购单号
             */
            'purchase_number'        => [
                'desc'     => '采购单号',
                'name'     => 'purchase_number',
                'type'     => 'strval',
            ],



            /**
             * 供应商
             */
            'supplier_code'        => [
                'desc'     => '供应商',
                'name'     => 'supplier_code',
                'type'     => 'strval',
            ],


            'shipment_type'        => [
                'desc'     => '发运类型',
                'name'     => 'shipment_type',
                'type'     => 'strval',
            ],


            /**
             * 关联的申请编码
             */
            'cancel_number'   => [
                'desc'     => '处理状态',
                'name'     => 'cancel_number',
                'type'     => 'strval',
            ],


            /**
             * 采购审核状态
             */
            'audit_status'        => [
                'desc'     => '采购审核状态',
                'name'     => 'audit_status',
                'type'     => 'strval',
            ],

            /**
             * 申请时间
             */
            'apply_time'        => [
                'desc'     => '申请时间',
                'name'     => 'apply_time',
                'type'     => 'strval',
            ],

        ];
        $this->_cfg = [
            'title' => $this->_ci->lang->myline('shipment_plan_cancel_list'),
            'select_cols' => [
                $this->_ci->m_shipment_cancel->getTable() => [
                    '*'
                ],
                $this->_ci->m_purchase_suggest->getTable() => [
                    'suggest_order_status'
                ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                'shipment_type','shipment_plan_cancel_audit_status'
            ],
            'user_profile' => 'm_shipment_cancel'
        ];
        return $this->_cfg;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {
        $params['export_save'] = 1;
        //数据库
        $db = $this->_ci->m_shipment_cancel->getDatabase();
        //表名
        $main_t = $this->_ci->m_shipment_cancel->getTable();
        $suggest_t = $this->_ci->m_purchase_suggest->getTable();

        //将表名拼接查询字段-- tab.字段名
        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($main_t);
        //关联查询
        $db->join($suggest_t,"$suggest_t.demand_number = {$main_t}.demand_number",'left');

        //采购员
        if (isset($params['buyer_id']))
        {
            $query->where("{$main_t}.buyer_id", $params['buyer_id']);
        }

        //备货单号
        if (isset($params['demand_number']))
        {
            $sns = query_string_to_array($params['demand_number']);
            $query->where_in("{$main_t}.demand_number", $sns);



        }

        //采购单号
        if (isset($params['purchase_number']))
        {
            $sns = query_string_to_array($params['purchase_number']);
            $query->where_in("{$main_t}.purchase_number", $sns);

        }

        //供应商
        if (isset($params['supplier_code']))
        {
            $query->where("{$main_t}.supplier_code", $params['supplier_code']);
        }

        //发运类型
        if (isset($params['shipment_type']))
        {
            $query->where("{$main_t}.shipment_type", $params['shipment_type']);
        }

        //关联的申请编码
        if (isset($params['cancel_number']))
        {
            $query->where("{$main_t}.cancel_number", $params['cancel_number']);
        }

        //采购申请状态
        if (isset($params['audit_status']))
        {
            $query->where("{$main_t}.audit_status", $params['audit_status']);
        }

        //关联的申请编码
        if (isset($params['apply_user_name']))
        {
            $query->like("{$main_t}.apply_user_name", $params['apply_user_name']);
        }

        //申请时间
        if (isset($params['apply_time']))
        {
            $query->where("{$main_t}.apply_time >=", $params['apply_time']['start'])
                ->where("{$main_t}.apply_time <=", $params['apply_time']['end']);
        }


        //查询字段,从cfg里面获取配置
        $select_cols = [];
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols[] = implode(',', $append_table_prefix($cols, $tbl.'.'));
        }
        if (!empty($select_cols)){
            $select_cols = implode(',',$select_cols);
        }else{
            $select_cols = '';
        }

        $query_counter = clone $query;
        $query_counter->select("{$main_t}.id");

        $count = $query_counter->count_all_results();
        $page = ceil($count / $params['per_page']);

        /**
         * 导出暂存
         */
        if (isset($params['export_save']))
        {
            $query_export = clone $query;
            $query_export->select($select_cols)->order_by(implode(',', $append_table_prefix($params['sort'], $main_t.'.')));
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PURCHASE_SHIPMENT_PLAN_CANCEL_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }

        //执行搜索
        $query->select($select_cols)
            ->order_by(implode(',', $append_table_prefix($params['sort'], $main_t.'.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);

//        pr($query->get_compiled_select('',false));exit;
//        pr($query->get());exit;
        $result = $query->get()->result_array();

        //设定统一返回格式
        return [
            'page_data' => [
                'total' => $count,
                'offset' => $params['page'],
                'limit' => $params['per_page'],
                'pages' => $page
            ],
            'data_list'  => [
                'value' => &$result
            ]
        ];
    }

    /**
     * 转换
     *
     * {@inheritDoc}
     * @see AbstractList::translate()
     */
    public function translate($search_result,$is_excel=false)
    {
        if (!empty($search_result) && isset($search_result['data_list']['value']) && !empty($search_result['data_list']['value'])){
//            $demand_number_list = array_column($search_result['data_list']['value'],'demand_number');
//            //备货单状态
//            $this->_ci->load->model('Purchase_suggest_model','',false,'purchase_suggest');
//            $demand_info = $this->_ci->Purchase_suggest_model->get_demand_info($demand_number_list);
//            if (!empty($demand_info)){
//                $demand_info = array_column($demand_info,NULL,'demand_number');
//            }
            foreach ($search_result['data_list']['value'] as $key => &$item){
//                $item['suggest_order_status'] = $demand_info[$item['demand_number']]['suggest_order_status']??'';

                $item['audit_status_text'] = getShipmentPlanCancelAuditStatus($item['audit_status']);//审核状态
                $item['shipment_type_text'] = getShipmentType($item['shipment_type']);//发运类型
                $item['suggest_order_status_text'] = getPurchaseStatus($item['suggest_order_status']??'');//备货单状态
                $item['is_drawback'] = getIsDrawback($item['is_drawback']);//是否退税
            }
        }
        return $search_result;
    }


    /**
     * 用户自定义处理参数的模板方法，由各自实例化类实现。
     *
     * @param unknown $defind_valid_key
     * @param unknown $col
     * @param unknown $val
     * @param unknown $format_params
     * @return boolean
     */
    protected function hook_user_format_params($defind_valid_key, $col, $val, &$format_params)
    {
        if (parent::hook_user_format_params($defind_valid_key, $col, $val, $format_params))
        {
            return true;
        }

        $rewrite_cols = ['apply_time_start', 'apply_time_end'];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }
        if ($col == 'apply_time_start') {
            $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
        }
        if ($col == 'apply_time_end') {
            $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
        }

        return true;
    }

    /**
     * 针对hook_user_format_params生成的参数做检测
     * {@inheritDoc}
     * @see AbstractList::hook_user_format_params()
     */
    protected function hook_user_format_params_check($defind_valid_key, &$format_params)
    {
        parent::hook_user_format_params_check($defind_valid_key, $format_params);

        $check_cols = ['apply_time'];
        foreach ($check_cols as $key)
        {
            if (isset($format_params[$key]))
            {
                if (count($format_params[$key]) == 1) {

                    if (isset($format_params[$key]['start']))
                    {
                        $format_params[$key]['end'] = date('Y-m-d');
                    }
                    else
                    {
                        //$format_params[$key]['start'] = sprintf('%s-%s-%s', date('Y'), '01', '01');
                        $format_params[$key]['start'] = $format_params[$key]['end'];
                    }
                }
                if ($format_params[$key]['start'] > $format_params[$key]['end'])
                {
                    //交换时间
                    $tmp = $format_params[$key]['start'];
                    $format_params[$key]['start'] =  $format_params[$key]['end'];
                    $format_params[$key]['end'] = $tmp;
                    //throw new \InvalidArgumentException(sprintf('开始时间不能晚于结束时间'), 3001);
                }
            }
        }

        return true;
    }
}
