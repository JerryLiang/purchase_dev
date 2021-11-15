<?php
require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 入库后退货-采购确认明细列表
 *
 */
class PurchaseConfirmListService extends AbstractList
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
        $this->_ci->load->model('return/Return_after_storage_part_model', 'm_return_part', false, 'purchase');
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
        $db = $this->_ci->m_return_part->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_return_part->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::Return_after_storage_main_SEARCH_EXPORT)->get();
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
        $this->_ci->load->classes('fba/classes/FbaHugeExport');
        $this->_ci->FbaHugeExport
            ->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
        return $this->_ci->FbaHugeExport->run();

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
             * 退货状态
             */
            'return_status'        => [
                'desc'     => '退货状态',
                'name'     => 'return_status',
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
             * 供应商
             */
            'supplier_code'        => [
                'desc'     => '供应商',
                'name'     => 'supplier_code',
                'type'     => 'strval',
            ],

            /**
             * 申请ID
             */
            'part_number'        => [
                'desc'     => '申请ID',
                'name'     => 'part_number',
                'type'     => 'strval',
            ],


            /**
             * 采购员
             */
            'buyer_id'        => [
                'desc'     => '采购员',
                'name'     => 'buyer_id',
                'type'     => 'strval',
            ],

            /**
             * 退货仓库
             */
            'return_warehouse'        => [
                'desc'     => '退货仓库',
                'name'     => 'return_warehouse',
                'type'     => 'strval',
            ],

            /**
             * 快递单号
             */
            'express_number'        => [
                'desc'     => '快递单号',
                'name'     => 'express_number',
                'type'     => 'strval',
            ],

            /**
             * 退货单号
             */
            'return_number'        => [
                'desc'     => '退货单号',
                'name'     => 'return_number',
                'type'     => 'strval',
            ],

        ];
        $this->_cfg = [
            'title' => $this->_ci->lang->myline('return_purchase_confirm_list'),
            'select_cols' => [
                $this->_ci->m_return_part->getTable() => [
                    '*'
                ],
                $this->_ci->m_return_main->getTable() => [
                    'sku, sample_packing_weight, product_name, return_reason, unit_price_without_tax, return_qty'
                ],
                'pur_return_express_info' => [
                    'express_number, status as express_status'
                ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                'purchase_user','return_status','return_warehouse'
            ],
            'user_profile' => 'm_return_part'
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
        $db = $this->_ci->m_return_part->getDatabase();
        //表名
        $part_t = $this->_ci->m_return_part->getTable();
        $main_t = $this->_ci->m_return_main->getTable();
        $express_t = 'pur_return_express_info';

        //将表名拼接查询字段-- tab.字段名
        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };
        $query = $db->from($part_t);
        //关联查询
        $db->join($main_t,"$main_t.main_number = {$part_t}.main_number",'left');
        $db->join($express_t,"$express_t.return_number = {$part_t}.return_number",'left');
      
        //申请人
        if (isset($params['proposer']) and !empty($params['proposer'])) {
            $query->where_in("{$main_t}.proposer", query_string_to_array($params['proposer']));
        }

        //申请时间
        if (isset($params['create_time'])) {
            $query->where("{$main_t}.create_time >=", $params['create_time']['start'])
                ->where("{$main_t}.create_time <=", $params['create_time']['end']);
        }

        //退货状态
        if (isset($params['return_status'])) {
                $query->where_in("{$part_t}.return_status",query_string_to_array($params['return_status']));
        }
        //sku
        if (isset($params['sku'])) {
            $query->where_in("{$main_t}.sku", query_string_to_array($params['sku']));
        }

        //供应商
        if (isset($params['supplier_code'])) {
            $query->where("{$part_t}.supplier_code", $params['supplier_code']);
        }

        //申请子ID
        if (isset($params['part_number']))
        {
            $params['part_number'] = query_string_to_array($params['part_number']);

            if (!empty($params['part_number']) && count($params['part_number']) > 1){
                $query->where_in("{$part_t}.part_number", $params['part_number']);
            }elseif (!empty($params['part_number']) && count($params['part_number']) == 1){
                $query->like("{$part_t}.part_number", $params['part_number'][0]);
            }

        }
        //申请主id
        if (isset($params['main_number']) and !empty($params['main_number'])) {
            $query->where_in("{$main_t}.main_number", query_string_to_array($params['main_number']));
        }
        //退货单号

        //采购员
        if (isset($params['buyer_id'])) {

            $query->where_in("{$main_t}.buyer_id", query_string_to_array($params['buyer_id']));
//
//            if(is_array($params['buyer_id']) and  count($params['buyer_id'])>0){
//                $query->where_in("{$main_t}.buyer_id", $params['buyer_id']);
//            }
//
//            if(is_string($params['buyer_id']) and  !empty($params['buyer_id'])){
//                $query->where("{$main_t}.buyer_id", $params['buyer_id']);
//            }
        }

        //退货仓库
        if (isset($params['return_warehouse']))
        {
            $query->where_in("{$part_t}.return_warehouse_code", query_string_to_array($params['return_warehouse']));
        }

        //快递单号
        if (isset($params['express_number'])) {

            $query->where_in("{$express_t}.express_number", query_string_to_array($params['express_number']));
        }

        //退款流水号
        if(isset($params['refund_number']) and !empty($params['refund_number'])){
            $refund_number = $params['refund_number'];
            $refund_list = $query->query("SELECT return_number FROM pur_return_refund_flow_info WHERE refund_serial_number='{$refund_number}'")->result_array();
            if(!empty($refund_list)){
                $return_number = array_column($refund_list,'return_number');
                $query->where_in("{$express_t}.return_number",$return_number);
            }else{
                $query->where("{$express_t}.return_number",$refund_number);
            }
        }

        //申请id
        if (isset($params['return_number']) and !empty($params['return_number'])) {
            $query->where_in("{$express_t}.return_number", query_string_to_array($params['return_number']));
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
        $query_counter->select("{$part_t}.id");
        $count = $query_counter->count_all_results();
        $page = ceil($count / $params['per_page']);
        /**
         * 导出暂存
         */
        if (isset($params['export_save']))
        {
            $query_export = clone $query;
            $query_export->select($select_cols)->order_by(implode(',', $append_table_prefix($params['sort'], $part_t.'.')));
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PURCHASE_RETURN_CONFIRM_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }

        //执行搜索

        $sql_q = clone $query;
        $statistics = $sql_q->select('sum(weight) as weight, sum(return_cost) as return_cost, sum(return_amount) as price')->get()->result_array();
        $query->select($select_cols)
            ->order_by(implode(',', $append_table_prefix($params['sort'], $part_t.'.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);

//        pr($query->get_compiled_select('',false));exit;
//        pr($query->get());exit;
        $result = $query->get()->result_array();

        if(!empty($result)){
            foreach ($result as $key => $val){
                $audit_user = empty($val['audit_user'])?'':$val['audit_user'];
                $audit_time = $val['audit_time']=='0000-00-00 00:00:00'?'':$val['audit_time'];
                $collection_time = $val['collection_time']=='0000-00-00 00:00:00'?'':$val['collection_time'];
                $wms_return_time = $val['wms_return_time']=='0000-00-00 00:00:00'?'':$val['wms_return_time'];
                $result[$key]['audit_user'] = $audit_user;
                $result[$key]['audit_time'] =$audit_time;
                $result[$key]['collection_time'] =$collection_time;
                $result[$key]['wms_return_time'] =$wms_return_time;
                $supplier = '';
                if(!empty($val['restricted_supplier'])){
                    if(is_json($val['restricted_supplier'])){
                        $restricted_supplier = json_decode($val['restricted_supplier'],true);
                        if(!empty($restricted_supplier)){
                            $supplier  = implode(',',array_values($restricted_supplier));
                        }
                    }
                }
                $result[$key]['restricted_supplier'] =$supplier;
            }
        }

        //设定统一返回格式
        return [
            'page_data'     => [
                'total'     => $count,
                'offset'    => $params['page'],
                'limit'     => $params['per_page'],
                'pages'     => $page
            ],
            'data_list'     => [
                'value'     => &$result
            ],
            "statistics"    => isset($statistics[0]) ? $statistics[0] : [
                "price" => 0,
                "return_cost" => 0,
                "weight" =>0],
        ];
    }

    /**
     * 转换
     *
     * {@inheritDoc}
     * @see AbstractList::translate()
     */
    public function translate($search_result,$is_excel = false)
    {
        if ($is_excel){
            $export_key = array_column($this->_ci->lang->myline('return_purchase_confirm_list'),'key');
        }

        $this->_ci->load->model('Product_model', 'm_product', false, 'product');
        if (!empty($search_result) && isset($search_result['data_list']['value'])){
            foreach ($search_result['data_list']['value'] as $key => &$item){

                $productInfo = $this->_ci->m_product->get_product_info($item['sku'],'product_img_url,product_thumb_url');
                $productInfo_img = '';
                if($productInfo){
//                    $productInfo['product_thumb_url']?$productInfo['product_thumb_url']:$productInfo['product_img_url'];//缩略图地址
                    if($productInfo['product_thumb_url']){
                        $productInfo_img = gettype($productInfo['product_thumb_url']) == "array" ? erp_sku_img_sku_thumbnail($productInfo['product_thumb_url'][0]): erp_sku_img_sku_thumbnail($productInfo['product_thumb_url']);
                    }else{
                        $productInfo_img = gettype($productInfo['product_img_url']) == "array" ? erp_sku_img_sku($productInfo['product_img_url'][0]) : erp_sku_img_sku($productInfo['product_img_url']);
                    }
                }
                $item['product_img_url_thumbnails'] = $productInfo_img;

                $item['freight_payment_type_text'] = getFreightPaymentType($item['freight_payment_type']);//支付类型
                $item['return_reason_text'] = getReturnSeason($item['return_reason']);//申请退货原因
                $item['return_status_text'] = getReturnStatus($item['return_status']);//退货状态
                $item['return_warehouse_code_text'] = getWarehouse($item['return_warehouse_code']);//申请退货仓库
                $item['weight'] = $item['pur_return_qty'] * $item['sample_packing_weight'];//总重量

//                $restricted_supplier = json_decode($item['restricted_supplier'],true);
//                $item['restricted_supplier'] = empty($restricted_supplier)?'':implode(',',array_values($restricted_supplier));;

                //采购经理审核通过->仓库返回退货单号
                if ($item['return_status']>2){
                    if ($item['wms_return_time'] == '0000-00-00 00:00:00'){
                        $endDate = strtotime(date('Y-m-d H:i:s'));//处理时间 当前时间-申请时间

                    }else{
                        $endDate = strtotime($item['wms_return_time']);//处理时间 完结时间-申请时间
                    }
                    $startDate = strtotime($item['audit_time']);
                    $processing_time = floor(($endDate - $startDate)/60/60);
                    $item['wms_processing_time'] = (string)$processing_time;

//                    $item['wms_processing_time'] = //仓库处理时间
                }else{
                    $item['wms_processing_time'] = '0';
                }


                //------ 最后 ----------
                if ($is_excel){//excel导出
                    foreach ($export_key as $col){
                        if (isset($item[$col])){
                            if ($col == 'restricted_supplier'){
                                $item[$col] = implode(' ',$item[$col]);
                            }
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

        $rewrite_cols = ['create_time_start', 'create_time_end'];
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

        $check_cols = ['create_time'];
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
