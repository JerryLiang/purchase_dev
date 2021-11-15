<?php
/**
 * User: Justin
 * Date: 2020/8/3
 * Time: 15:47
 **/

class Product_line_buyer_config_model extends Purchase_model
{
    protected $table_name = 'product_line_buyer_relation';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 查询数据列表
     * @param array $params 查询参数
     * @param int   $offsets
     * @param int   $limit
     * @param int   $page
     * @return array
     */
    public function get_list_data($params, $offsets, $limit, $page)
    {
        $query = $this->purchase_db;
        $query->select('id,product_line_id,product_line_name,non_oversea_buyer_id,non_oversea_buyer_name,oversea_buyer_id,oversea_buyer_name,update_time');
        $query->from($this->table_name);

        $count_qb = clone $query;
        //统计总数要加上前面筛选的条件
        $count_row = $count_qb->select("count(id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;

        //查询数据
        $result = $query->limit($limit, $offsets)->get()->result_array();
        //转换数据
        foreach ($result as &$item) {
            //时间处理
            if ('0000-00-00 00:00:00' == $item['update_time']) {
                $item['update_time'] = '';
            }
            //采购员id处理
            if (0 == $item['non_oversea_buyer_id']) {
                $item['non_oversea_buyer_id'] = '';
            }
            if (0 == $item['oversea_buyer_id']) {
                $item['oversea_buyer_id'] = '';
            }
        }
        //下拉列表采购员
        $this->load->model('Supplier/Supplier_buyer_model', 'buyerModel');
        $buyers = $this->buyerModel->get_buyers();

        return [
            'values' => $result,
            'buyer_list' => $buyers['list'],
            'page_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
                'pages' => ceil($total_count / $limit),
            ],
        ];
    }

    /**
     * 根据一级产品线和业务线，获取候补采购员信息
     * @param int $product_line_id 一级产品线
     * @param int $purchase_type_id 业务线
     * @return array|mixed|null
     */
    public function get_buyer($product_line_id, $purchase_type_id)
    {
        if (empty($product_line_id) or empty($purchase_type_id)) {
            return ['buyer_id' => 0, 'buyer_name' => ''];
        }

        //统一转换为‘海外仓’和‘非海外仓’两大类
        $purchase_type_id = in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG]) ? 2 : 1;

        $data = $this->rediss->getData('PRODUCT_LINE_BUYER_RELATION_' . $product_line_id . '_' . $purchase_type_id);
        if (!empty($data)) {
            return json_decode($data, TRUE);
        }

        if (in_array($purchase_type_id, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {//海外仓
            $filed = 'oversea_buyer_id AS buyer_id,oversea_buyer_name AS buyer_name';
        } else {//非海外仓
            $filed = 'non_oversea_buyer_id AS buyer_id,non_oversea_buyer_name AS buyer_name';
        }

        //查询数据
        $result = $this->purchase_db->select($filed)
            ->from($this->table_name)
            ->where('product_line_id', $product_line_id)
            ->get()->row_array();

        //最终返回结果
        $result = ['buyer_id' => isset($result['buyer_id']) ? $result['buyer_id'] : 0, 'buyer_name' => isset($result['buyer_name']) ? $result['buyer_name'] : ''];
        //缓存数据
        $this->rediss->setData('PRODUCT_LINE_BUYER_RELATION_' . $product_line_id . '_' . $purchase_type_id, json_encode($result));

        return $result;
    }

    /**
     * 批量更新数据
     * @param array $ids  数据ID
     * @param array $data 要更新的数据
     * @throws Exception
     */
    public function batch_edit($ids, $data)
    {
        //验证数据是否存在
        $result = $this->purchase_db->select('id,product_line_id')->where_in('id', $ids)->get($this->table_name)->result_array();
        $ids_data = array_combine($ids, $ids);
        foreach ($result as $item) {
            unset($ids_data[$item['id']]);
        }
        if (count($ids_data)) throw new Exception('ID[' . implode(',', $ids_data) . ']数据不存在');

        //更新数据
        $edit_data = [
            'non_oversea_buyer_id' => $data['non_oversea_buyer_id'],
            'non_oversea_buyer_name' => $data['non_oversea_buyer_name'],
            'oversea_buyer_id' => $data['oversea_buyer_id'],
            'oversea_buyer_name' => $data['oversea_buyer_name'],
            'update_user' => getActiveUserName(),
        ];

        //更新数据
        $update_res = $this->purchase_db->where_in('id', $ids)->update($this->table_name, $edit_data);

        if (empty($update_res)) throw new Exception('编辑失败');

        //更新成功时清空缓存
        foreach ($result as $item) {
            $this->rediss->deleteData('PRODUCT_LINE_BUYER_RELATION_' . $item['product_line_id'] . '_1');
            $this->rediss->deleteData('PRODUCT_LINE_BUYER_RELATION_' . $item['product_line_id'] . '_2');
        }
    }

}