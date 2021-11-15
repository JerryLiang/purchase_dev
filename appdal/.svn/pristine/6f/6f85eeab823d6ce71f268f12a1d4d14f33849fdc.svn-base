<?php
require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 入库后退货-申请明细列表
 *
 */
class ApplyReturnGoodsListService extends AbstractList
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
        $this->_ci->load->model('return/Return_after_storage_main_model', 'm_return_main', false, 'purchase');
        $this->_ci->load->helper('status_order');
        $this->_ci->load->helper('common');
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
        $db = $this->_ci->m_return_main->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_return_main->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PURCHASE_RETURN_APPLY_LIST_SEARCH_EXPORT)->get();
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
             * 申请人
             */
            'proposer' => [
                'desc'     => '申请人',
                'name'     => 'proposer',
                'type'     => 'strval',
            ],
            /**
             * 申请时间
             */
            'create_time_start'        => [
                'desc'     => '申请时间',
                'name'     => 'create_time',
                'type'     => 'strval',
            ],
            /**
             * 申请时间
             */
            'create_time_end'        => [
                'desc'     => '申请时间',
                'name'     => 'create_time',
                'type'     => 'strval',
            ],



            /**
             * SKU
             */
            'sku'        => [
                'desc'     => 'SKU',
                'name'     => 'sku',
                'type'     => 'strval',
            ],

            /**
             * 采购员
             */
            'buyer_id'        => [
                'desc'     => '申请ID',
                'name'     => 'buyer_id',
                'type'     => 'strval',
            ],
            /**
             * 退货仓库
             */
            'return_warehouse_code'        => [
                'desc'     => '退货仓库',
                'name'     => 'return_warehouse_code',
                'type'     => 'strval',
            ],

            /**
             * 处理状态
             */
            'return_processing_status'   => [
                'desc'     => '处理状态',
                'name'     => 'return_processing_status',
                'type'     => 'strval',
            ],


            /**
             * 处理完结时间
             */
            'end_time_start'        => [
                'desc'     => '处理完结时间',
                'name'     => 'end_time',
                'type'     => 'strval',
            ],
            /**
             * 处理完结时间
             */
            'end_time_end'        => [
                'desc'     => '处理完结时间',
                'name'     => 'end_time',
                'type'     => 'strval',
            ],

            /**
             * 申请ID
             */
            'main_number'        => [
                'desc'     => '申请ID',
                'name'     => 'main_number',
                'type'     => 'strval',
            ],

        ];
        $this->_cfg = [
//            'title' => array_column($this->_ci->lang->myline('return_apply_detail_list'),'label'),
            'title' => $this->_ci->lang->myline('return_apply_detail_list'),
            'select_cols' => [
                ($this->_ci->m_return_main->getTable()) => [
                    '*'
                ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                'purchase_user','return_processing_status','return_warehouse'
            ],
            'user_profile' => 'm_return_main'
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
//pr($params);exit;
        //数据库
        $db = $this->_ci->m_return_main->getDatabase();
        //表名
        $main_t = $this->_ci->m_return_main->getTable();

        //将表名拼接查询字段-- tab.字段名
        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };
        $query = $db->from($main_t);
        //申请人
        if (isset($params['proposer']) and !empty($params['proposer'])) {
            $query->where_in("{$main_t}.proposer", query_string_to_array($params['proposer']));
        }
        
        //申请时间
        if (isset($params['create_time']) and !empty($params['create_time']['start'])) {
            $query->where("{$main_t}.create_time >=", $params['create_time']['start'])
                ->where("{$main_t}.create_time <=", $params['create_time']['end']);
        }

        //sku
        if (isset($params['sku']) and !empty($params['sku'])) {
            $sku = explode(' ', trim($params['sku']));
            $query->where_in("{$main_t}.sku",$sku);
        }

        //采购员
        if (isset($params['buyer_id']) and !empty($params['buyer_id'])) {
            $query->where_in("{$main_t}.buyer_id", query_string_to_array($params['buyer_id']));
        }

        //退货仓库
        if (isset($params['return_warehouse_code']) and !empty($params['return_warehouse_code'])) {
            $query->where("{$main_t}.return_warehouse_code", $params['return_warehouse_code']);
        }

        //处理状态
        if (isset($params['return_processing_status']) and !empty($params['return_processing_status'])) {

            if(is_array($params['return_processing_status']) and count($params['return_processing_status'])>0){
                $query->where_in("{$main_t}.processing_status", $params['return_processing_status']);
            }

            if(is_string($params['return_processing_status']) and  !empty($params['return_processing_status'])){
                $query->where("{$main_t}.processing_status=", $params['return_processing_status']);
            }

        }

        //处理完结时间
        if (isset($params['end_time'])) {
            $query->where("{$main_t}.end_time >=", $params['end_time']['start'])
                ->where("{$main_t}.end_time <=", $params['end_time']['end']);
        }

        //申请主ID
        if (isset($params['main_number']) and !empty($params['main_number'])) {
            $main_number = explode(' ', trim($params['main_number']));
            $query->where_in("{$main_t}.main_number",$main_number);
        }


        //查询字段,从cfg里面获取配置
        $select_cols = '';
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols .= implode(',', $append_table_prefix($cols, $tbl.'.'));
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
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PURCHASE_RETURN_APPLY_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }

        //执行搜索
        $query->select($select_cols)
            ->order_by(implode(',', $append_table_prefix($params['sort'], $main_t.'.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
//
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
            ],
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
        if ($is_excel){
            $export_key = array_column($this->_ci->lang->myline('return_apply_detail_list'),'key');
        }
        if (!empty($search_result) && isset($search_result['data_list']['value'])){
            $sku_list = array_column($search_result['data_list']['value'],'sku');
            $this->_ci->load->model('Warehouse_model', 'm_warehouse', false, 'warehouse');
            $this->_ci->load->model('Product_model', 'm_product', false, 'product');
            $purchase_on_way_map = $this->_ci->m_warehouse->get_total_purchase_on_way($sku_list);

            foreach ($search_result['data_list']['value'] as $key => &$item){
                $item['purchase_on_way'] = $purchase_on_way_map[$item['sku']]??0;//采购在途

                $productInfo = $this->_ci->m_product->get_product_info($item['sku'],'product_img_url,product_thumb_url');
                if($productInfo){
                    $item['product_img_url_thumbnails'] = $productInfo['product_thumb_url']?erp_sku_img_sku_thumbnail($productInfo['product_thumb_url']):erp_sku_img_sku($productInfo['product_img_url']);//缩略图地址
                }else{
                    $item['product_img_url_thumbnails'] = '';
                }

                $item['return_reason_text'] = getReturnSeason($item['return_reason']);//申请退货原因
                $item['return_warehouse_code_text'] = getWarehouse($item['return_warehouse_code']);//申请退货仓库
                $item['processing_status_text'] = getReturnProcessingStatus($item['processing_status']);//处理状态
                //$item['buyer_name'] = get_buyer_name($item['buyer_id']);

                /**
                 * 处理时间
                 */
                if ($item['end_time'] == '0000-00-00 00:00:00'){
                    $item['end_time'] = '';
                    $endDate = strtotime(date('Y-m-d H:i:s'));//处理时间 当前时间-申请时间

                }else{
                    $endDate = strtotime($item['end_time']);//处理时间 完结时间-申请时间
                }
                $startDate = strtotime($item['create_time']);
                $processing_time = floor(($endDate - $startDate)/60/60);
                $item['processing_time'] = (string)$processing_time.'h';


                //------ 最后 ----------
                if ($is_excel){//excel导出
                    foreach ($export_key as $col){
                        if (isset($item[$col])){
                            $excel_data[$col] = $item[$col];
                        }else{
                            $excel_data[$col] = '';
                        }
                    }
                    $item = $excel_data;
                }

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

        $rewrite_cols = ['create_time_start', 'create_time_end', 'end_time_start', 'end_time_end'];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }
        if ($col == 'create_time_start') {
            $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
        }
        if ($col == 'create_time_end') {
            $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
        }

        if ($col == 'end_time_start') {
            $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
        }
        if ($col == 'end_time_end') {
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

        $check_cols = ['create_time', 'end_time'];
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
