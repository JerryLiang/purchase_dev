<?php

/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2018/12/27 0027 11:23
 */
class Purchase_label_model extends Purchase_model
{
    protected $table_name = 'purchase_label_info';

    /**
     * Purchase_order_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/supplier_model'); // 供应商
        $this->load->model('purchase/Purchase_order_model');

    }

    /*
     * @desc 物流标签列表
     */
    public function get_label_list($params = [], $offset = 0, $limit = 0, $page = 1,$export = false)
    {

        $params = $this->table_query_filter($params);
        $query_builder = $this->purchase_db;

        $query_builder->from(' purchase_label_info as la');
        $query_builder->join('purchase_suggest  as su', 'la.demand_number=su.demand_number', 'left');
        $query_builder->join('purchase_compact_items  as compact', 'compact.purchase_number=la.purchase_number and compact.bind=1', 'left');

        if(isset($params['compact_number']) && !empty($params['compact_number'])){
            $search_pur=query_string_to_array($params['compact_number']);
            $this->purchase_db->where_in('compact.compact_number',$search_pur);
        }

        if(isset($params['is_warehouse_update']) && !empty($params['is_warehouse_update'])){
            if ($params['is_warehouse_update']==1) {
                $this->purchase_db->where('la.destination_warehouse!=la.new_des_warehouse');

            }elseif($params['is_warehouse_update']==2){
                $this->purchase_db->where('la.destination_warehouse=la.new_des_warehouse');

            }
        }

        if(isset($params['new_des_warehouse']) && !empty($params['new_des_warehouse'])){
            $this->purchase_db->where_in('la.new_des_warehouse',$params['new_des_warehouse']);
        }



        if (isset($params['sku']) && !empty(trim($params['sku']))) {
            $params['sku'] = trim($params['sku']);
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $query_builder->like('la.sku', $params['sku'], 'both');
            } else {
                $query_builder->where_in('la.sku', $sku);
            }
        }

        if (isset($params['purchase_number']) and trim($params['purchase_number'])) {// 采购单号
            $purchase_numbers = explode(' ', trim($params['purchase_number']));

            $purchase_numbers = array_map(function ($numbers) {

                return sprintf("%s", $numbers);
            }, $purchase_numbers);



            $query_builder->where_in('la.purchase_number', $purchase_numbers);
        }

        if (isset($params['demand_status']) and $params['demand_status']) {// 备货单状态 查询的时候为数组
            if (is_array($params['demand_status'])) {
                $query_builder->where_in('su.suggest_order_status', $params['demand_status']);

            }
        }

        if (isset($params['is_update']) and $params['is_update']) {// 是否更新
            $query_builder->where('la.label_is_update', $params['is_update']);

        }




        if (isset($params['supplier_code']) and $params['supplier_code']) {// 供应商名称
                     $query_builder->where('la.supplier_code', $params['supplier_code']);

        }


        if (isset($params['is_create']) and $params['is_create']) {// 标签是否创建成功
            if ($params['is_create'] == 1) {//生成成功
                $query_builder->where('label!=', '');

            } else {
                $query_builder->where('label', '');


            }

        }


        if (isset($params['is_wrong']) and $params['is_wrong']) {// 下载是否失败
            if ($params['is_wrong'] == 1) {//生成成功
                $query_builder->where('la.error_mes!=', '');

            } else {
                $query_builder->where('la.error_mes', '');


            }

        }


        if (isset($params['shipment_type']) and $params['shipment_type']) {// 发运类型

            $query_builder->where('la.shipment_type', $params['shipment_type']);

        }

        if (isset($params['purchase_is_download']) and $params['purchase_is_download']) {// 采购是否下载

            if ($params['purchase_is_download'] == 1) {//采购下载条码

                $query_builder->where('la.purchase_label_down_time!=', '0000-00-00 00:00:00');

            } else {
                $query_builder->where('la.purchase_label_down_time', '0000-00-00 00:00:00');


            }
        }


        if (isset($params['supplier_is_download']) and $params['supplier_is_download']) {// 供应商是否下载
            if ($params['supplier_is_download'] == 1) {//采购下载条码

                $query_builder->where('la.supplier_down_time!=', '0000-00-00 00:00:00');

            } else {
                $query_builder->where('la.supplier_down_time', '0000-00-00 00:00:00');


            }

        }

        if (isset($params['enable']) and $params['enable']) {// 使用启用门户系统1 启用，2禁用

            $query_builder->where('la.enable', $params['enable']);

        }

        if (isset($params['is_dispose']) and $params['is_dispose']) {// 是否已处理 1已处理2未处理

            $query_builder->where('la.label_is_dispose', $params['is_dispose']);

        }

        if (isset($params['order_time_start']) and $params['order_time_start']) {
            $query_builder->where('la.order_time >=', $params['order_time_start']);
        }

        if (isset($params['order_time_end']) and $params['order_time_end']) {
            $query_builder->where('la.order_time <=', $params['order_time_end']);
        }


        if (isset($params['update_time_start']) and $params['update_time_start']) {
            $query_builder->where('la.update_time >=', $params['update_time_start']);
        }

        if (isset($params['update_time_end']) and $params['update_time_end']) {
            $query_builder->where('la.update_time <=', $params['update_time_end']);
        }

        if (isset($params['is_promise']) and $params['is_promise']) {
            $query_builder->where('la.is_paste ', $params['is_promise']);
        }

        if (isset($params['is_plan']) and $params['is_plan']) {

            if ($params['is_plan'] == 1) {
                $query_builder->where('su.source_from', 1);

            } else {
                $query_builder->where('su.source_from!= ', 1);

            }

        }




        $limit = query_limit_range($limit, false);
        $query_builder ->order_by('la.order_time','desc');

        $query_builder = $query_builder->select('la.*,su.source_from,compact.compact_number');

        $query_builder_count = clone $query_builder;// 克隆一个查询 用来计数
        $total_count = $query_builder_count->count_all_results();

        if ($export) {//导出查询，不需要传分页
            $results = $query_builder->get('', $limit)->result_array();
        } else {//列表查询
            $query_builder->order_by('la.update_time', 'desc');
            $results = $query_builder->get('', $limit, $offset)->result_array();

        }



        $return_data = [
            'data_list' => $results,
            'page_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit
            ],
        ];

        return $return_data;
     }


        /**
         * 获取一个指定的标签信息
         * @author Jolon
         * @param string $purchase_number 采购单编号
         * @param bool $have_items 是否附带采购单明细
         * @return mixed
         */
        public function get_one($demand_number)
        {
            $query_builder = $this->purchase_db;
            $query_builder->where('demand_number', $demand_number);
            $results = $query_builder->get($this->table_name)->row_array();

            return $results;
        }


    /**
     * 获取一个指定的标签信息,通过sku和采购单号
     * @return array
     */
    public function get_label_item($purchase_number,$sku)
    {
        $query_builder = $this->purchase_db;
        $query_builder->where('purchase_number', $purchase_number);
        $query_builder->where('sku', $sku);
        $results = $query_builder->get($this->table_name)->row_array();

        return $results;
    }







    /*
* @desc 产品条码列表
*/
    public function get_barcode_list($params = [], $offset = 0, $limit = 0, $page = 1,$export = false)
    {

        $params = $this->table_query_filter($params);
        $query_builder = $this->purchase_db;

        $query_builder->from(' purchase_label_info as la');
        $query_builder->join('purchase_suggest  as su', 'la.demand_number=su.demand_number', 'left');
        $query_builder->join('purchase_compact_items  as compact', 'compact.purchase_number=la.purchase_number and compact.bind=1', 'left');


        if(isset($params['compact_number']) && !empty($params['compact_number'])){
            $search_pur=query_string_to_array($params['compact_number']);
            $this->purchase_db->where_in('compact.compact_number',$search_pur);
        }

        if(isset($params['is_warehouse_update']) && !empty($params['is_warehouse_update'])){
            if ($params['is_warehouse_update']==1) {
                $this->purchase_db->where('la.destination_warehouse!=la.new_des_warehouse');

            }elseif($params['is_warehouse_update']==2){
                $this->purchase_db->where('la.destination_warehouse=la.new_des_warehouse');

            }
        }

        if(isset($params['new_des_warehouse']) && !empty($params['new_des_warehouse'])){
            $this->purchase_db->where_in('la.new_des_warehouse',$params['new_des_warehouse']);
        }

        if (isset($params['sku']) && !empty(trim($params['sku']))) {
            $params['sku'] = trim($params['sku']);
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $query_builder->like('la.sku', $params['sku'], 'both');
            } else {
                $query_builder->where_in('la.sku', $sku);
            }
        }

        if (isset($params['purchase_number']) and trim($params['purchase_number'])) {// 采购单号
            $purchase_numbers = explode(' ', trim($params['purchase_number']));

            $purchase_numbers = array_map(function ($numbers) {

                return sprintf("%s", $numbers);
            }, $purchase_numbers);

            $query_builder->where_in('la.purchase_number', $purchase_numbers);
        }

        if (isset($params['demand_status']) and $params['demand_status']) {// 备货单状态 查询的时候为数组
            if (is_array($params['demand_status'])) {
                $query_builder->where_in('su.suggest_order_status', $params['demand_status']);

            }
        }


        if (isset($params['is_update']) and $params['is_update']) {// 供应商名称
            $query_builder->where('la.barcode_is_update', $params['is_update']);

        }



        if (isset($params['supplier_code']) and $params['supplier_code']) {// 供应商名称
            $query_builder->where('la.supplier_code', $params['supplier_code']);

        }

        if (isset($params['is_create']) and $params['is_create']) {// 标签是否创建成功
            if ($params['is_create'] == 1) {//生成成功
                $query_builder->where('barcode!=', '');

            } else {
                $query_builder->where('barcode', '');


            }

        }


        if (isset($params['is_wrong']) and $params['is_wrong']) {// 下载是否失败
            if ($params['is_wrong'] == 1) {//生成成功
                $query_builder->where('la.error_mes!=', '');

            } else {
                $query_builder->where('la.error_mes', '');


            }

        }


        if (isset($params['shipment_type']) and $params['shipment_type']) {// 发运类型

            $query_builder->where('la.shipment_type', $params['shipment_type']);

        }

        if (isset($params['purchase_is_download']) and $params['purchase_is_download']) {// 采购是否下载
            if ($params['purchase_is_download'] == 1) {//采购下载条码

                $query_builder->where('la.purchase_barcode_down_time!=', '0000-00-00 00:00:00');

            } else {
                $query_builder->where('la.purchase_barcode_down_time', '0000-00-00 00:00:00');


            }


        }


        if (isset($params['supplier_is_download']) and $params['supplier_is_download']) {// 供应商是否下载

            if ($params['supplier_is_download'] == 1) {//采购下载条码

                $query_builder->where('la.supplier_down_time!=', '0000-00-00 00:00:00');

            } else {
                $query_builder->where('la.supplier_down_time', '0000-00-00 00:00:00');


            }

        }

        if (isset($params['enable']) and $params['enable']) {// 使用启用门户系统1 启用，2禁用

            $query_builder->where('la.enable', $params['enable']);

        }

        if (isset($params['is_dispose']) and $params['is_dispose']) {// 是否已处理 1已处理2未处理

            $query_builder->where('la.barcode_is_dispose', $params['is_dispose']);

        }

        if (isset($params['order_time_start']) and $params['order_time_start']) {
            $query_builder->where('la.order_time >=', $params['order_time_start']);
        }

        if (isset($params['order_time_end']) and $params['order_time_end']) {
            $query_builder->where('la.order_time <=', $params['order_time_end']);
        }


        if (isset($params['update_time_start']) and $params['update_time_start']) {
            $query_builder->where('la.update_time >=', $params['update_time_start']);
        }

        if (isset($params['update_time_end']) and $params['update_time_end']) {
            $query_builder->where('la.update_time <=', $params['update_time_end']);
        }

        if (isset($params['is_promise']) and $params['is_promise']) {
            $query_builder->where('la.is_paste ', $params['is_promise']);
        }

        if (isset($params['is_plan']) and $params['is_plan']) {

            if ($params['is_plan'] == 1) {
                $query_builder->where('su.source_from', 1);

            } else {
                $query_builder->where('su.source_from!= ', 1);

            }

        }



        $limit = query_limit_range($limit, false);
        $query_builder ->order_by('la.order_time','desc');
        $query_builder = $query_builder->select('la.*,su.source_from,compact.compact_number');
        $query_builder_count = clone $query_builder;// 克隆一个查询 用来计数
        $total_count = $query_builder_count->count_all_results();

        if ($export) {//导出查询，不需要传分页
            $results = $query_builder->get('', $limit)->result_array();
        } else {//列表查询
            $query_builder->order_by('la.update_time', 'desc');
            $results = $query_builder->get('', $limit, $offset)->result_array();

        }
        $return_data = [
            'data_list'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'offset'    => $page,
                'limit'     => $limit
            ],
//           
        ];



        return $return_data;
    }


    public function get_combine_list($params = [], $offset = 0, $limit = 0, $page = 1,$export = false)
    {
        $params = $this->table_query_filter($params);
        $query_builder = $this->purchase_db;

        $query_builder->from(' purchase_label_info as la');
        $query_builder->join('purchase_suggest  as su', 'la.demand_number=su.demand_number', 'left');
        $query_builder->join('purchase_compact_items  as compact', 'compact.purchase_number=la.purchase_number and compact.bind=1', 'left');


        if(isset($params['compact_number']) && !empty($params['compact_number'])){
            $search_pur=query_string_to_array($params['compact_number']);
            $this->purchase_db->where_in('compact.compact_number',$search_pur);
        }

        if(isset($params['is_warehouse_update']) && !empty($params['is_warehouse_update'])){
            if ($params['is_warehouse_update']==1) {
                $this->purchase_db->where('la.destination_warehouse!=la.new_des_warehouse');

            }elseif($params['is_warehouse_update']==2){
                $this->purchase_db->where('la.destination_warehouse=la.new_des_warehouse');

            }
        }

        if(isset($params['new_des_warehouse']) && !empty($params['new_des_warehouse'])){
            $this->purchase_db->where_in('la.new_des_warehouse',$params['new_des_warehouse']);
        }



        if (isset($params['sku']) && !empty(trim($params['sku']))) {
            $params['sku'] = trim($params['sku']);
            $sku = query_string_to_array($params['sku']);
            if (count($sku) == 1) {  //单个sku时使用模糊搜索
                $query_builder->like('la.sku', $params['sku'], 'both');
            } else {
                $query_builder->where_in('la.sku', $sku);
            }
        }

        if (isset($params['purchase_number']) and trim($params['purchase_number'])) {// 采购单号
            $purchase_numbers = explode(' ', trim($params['purchase_number']));

            $purchase_numbers = array_map(function ($numbers) {

                return sprintf("%s", $numbers);
            }, $purchase_numbers);

            $query_builder->where_in('la.purchase_number', $purchase_numbers);
        }

        if (isset($params['demand_status']) and $params['demand_status']) {// 备货单状态 查询的时候为数组
            if (is_array($params['demand_status'])) {
                $query_builder->where_in('su.suggest_order_status', $params['demand_status']);

            }
        }


        if (isset($params['is_update']) and $params['is_update']) {// 供应商名称
            $query_builder->where('la.combine_is_update', $params['is_update']);

        }



        if (isset($params['supplier_code']) and $params['supplier_code']) {// 供应商名称
            $query_builder->where('la.supplier_code', $params['supplier_code']);

        }

        if (isset($params['is_create']) and $params['is_create']) {// 标签是否创建成功
            if ($params['is_create'] == 1) {//生成成功
                $query_builder->where('combine_label!=', '');

            } else {
                $query_builder->where('combine_label', '');


            }

        }


        if (isset($params['is_wrong']) and $params['is_wrong']) {// 下载是否失败
            if ($params['is_wrong'] == 1) {//生成成功
                $query_builder->where('la.combine_error_msg!=', '');

            } else {
                $query_builder->where('la.combine_error_msg', '');


            }

        }


        if (isset($params['shipment_type']) and $params['shipment_type']) {// 发运类型

            $query_builder->where('la.shipment_type', $params['shipment_type']);

        }

        if (isset($params['purchase_is_download']) and $params['purchase_is_download']) {// 采购是否下载
            if ($params['purchase_is_download'] == 1) {//采购下载条码

                $query_builder->where('la.purchase_barcode_down_time!=', '0000-00-00 00:00:00');

            } else {
                $query_builder->where('la.purchase_barcode_down_time', '0000-00-00 00:00:00');


            }


        }


        if (isset($params['supplier_is_download']) and $params['supplier_is_download']) {// 供应商是否下载

            if ($params['supplier_is_download'] == 1) {//采购下载条码

                $query_builder->where('la.supplier_down_time!=', '0000-00-00 00:00:00');

            } else {
                $query_builder->where('la.supplier_down_time', '0000-00-00 00:00:00');


            }

        }

        if (isset($params['enable']) and $params['enable']) {// 使用启用门户系统1 启用，2禁用

            $query_builder->where('la.enable', $params['enable']);

        }

        if (isset($params['is_dispose']) and $params['is_dispose']) {// 是否已处理 1已处理2未处理

            $query_builder->where('la.combine_is_dispose', $params['is_dispose']);

        }

        if (isset($params['order_time_start']) and $params['order_time_start']) {
            $query_builder->where('la.order_time >=', $params['order_time_start']);
        }

        if (isset($params['order_time_end']) and $params['order_time_end']) {
            $query_builder->where('la.order_time <=', $params['order_time_end']);
        }


        if (isset($params['update_time_start']) and $params['update_time_start']) {
            $query_builder->where('la.update_time >=', $params['update_time_start']);
        }

        if (isset($params['update_time_end']) and $params['update_time_end']) {
            $query_builder->where('la.update_time <=', $params['update_time_end']);
        }

        if (isset($params['is_promise']) and $params['is_promise']) {
            $query_builder->where('la.is_paste ', $params['is_promise']);
        }

        if (isset($params['is_plan']) and $params['is_plan']) {

            if ($params['is_plan'] == 1) {
                $query_builder->where('su.source_from', 1);

            } else {
                $query_builder->where('su.source_from!= ', 1);

            }

        }

        if (!empty($params['is_charged'])) {

            if ($params['is_charged'] == 1) {
                $query_builder->where('la.sku in (SELECT sku FROM pur_prod_logistics_audit_attr WHERE (new_product_attributes_value  LIKE \'%22%\') OR (new_product_attributes_value  LIKE \'%23%\') OR (new_product_attributes_value  LIKE \'%24%\')
) ');


            } else {
                $query_builder->where('la.sku not in (SELECT sku FROM pur_prod_logistics_audit_attr WHERE (new_product_attributes_value  LIKE \'%22%\') OR (new_product_attributes_value  LIKE \'%23%\') OR (new_product_attributes_value  LIKE \'%24%\')
)') ;


            }

        }



        $limit = query_limit_range($limit, false);
        $query_builder ->order_by('la.order_time','desc');
        $query_builder = $query_builder->select('la.*,su.source_from,compact.compact_number');
        $query_builder_count = clone $query_builder;// 克隆一个查询 用来计数
        $total_count = $query_builder_count->count_all_results();

        if ($export) {//导出查询，不需要传分页
            $results = $query_builder->get('', $limit)->result_array();
        } else {//列表查询
            $query_builder->order_by('la.update_time', 'desc');
            $results = $query_builder->get('', $limit, $offset)->result_array();


        }

        $return_data = [
            'data_list'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'offset'    => $page,
                'limit'     => $limit
            ],
//
        ];


        return $return_data;
    }


    //获取物流属性是否带电
    public function check_is_charged($sku_list)
    {
        $result = $this->purchase_db->select('sku')->from('prod_logistics_audit_attr')->where('(new_product_attributes_value  LIKE \'%22%\') OR (new_product_attributes_value  LIKE \'%23%\') OR (new_product_attributes_value  LIKE \'%24%\')')->where_in('sku',$sku_list)->get()->result_array();
        return !empty($result)?array_unique(array_column($result,'sku')):[];




    }





}


