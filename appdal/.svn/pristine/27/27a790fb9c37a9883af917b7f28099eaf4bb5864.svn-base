<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/11/29
 * Time: 14:44
 */

class Logistics_state_config_model extends Purchase_model
{
    public function __construct()
    {
        parent::__construct();
        $this->lang->load('logistics_state_config_lang');
    }

    //快递公司信息表
    protected $table_name = 'pur_logistics_carrier';

    /**
     * @desc 获取物流承运商列表数据
     * @author Justin
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function get_list_data($params, $offsets, $limit, $page)
    {
        $query = $this->purchase_db;
        $query->select('id,carrier_name,carrier_code,rule,update_time,update_user,status');
        $query->from($this->table_name);

        //按物流公司名称模糊查询
        if (!empty($params['name'])) {
            $query->like('carrier_name', $params['name']);
        }
        //按物流公司编码查询
        if (!empty($params['code'])) {
            $query->where('carrier_code', $params['code']);
        }

        $count_qb = clone $query;
        //统计总数要加上前面筛选的条件
        $count_row = $count_qb->select("count(id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;

        //查询数据
        $result = $query->limit($limit, $offsets)->order_by('update_time', 'DESC')->get()->result_array();

        //转换数据
        foreach ($result as &$item) {
            //根据规则转换字段用于列表展示
            $item['collect'] = $item['shipped'] = $item['pick_up_point'] = $item['deliver'] = $item['received'] = '';
            if (!empty($item['rule'])) {
                $rule = json_decode($item['rule'], true);
                if (is_array($rule) && !empty(array_filter($rule))) {
                    $item['collect'] = $rule['collect'];
                    $item['shipped'] = $rule['shipped'];
                    $item['pick_up_point'] = $this->_get_keyword_cn($rule['pick_up_point']['address']) . ' ' . $rule['pick_up_point']['relation'] . ' ' . $rule['pick_up_point']['keyword'];
                    $item['deliver'] = $rule['deliver'];
                    $item['received'] = $rule['received'];
                }
            }
            //转换状态对应中文
            $item['status_cn'] = $this->_get_status($item['status']);
        }

        $key_table = array(
            $this->lang->myline('sequence'),
            $this->lang->myline('carrier_code'),
            $this->lang->myline('carrier_name'),
            $this->lang->myline('collect'),
            $this->lang->myline('shipped'),
            $this->lang->myline('pick_up_point'),
            $this->lang->myline('deliver'),
            $this->lang->myline('received'),
            $this->lang->myline('status'),
            $this->lang->myline('update_user'),
            $this->lang->myline('update_time'),
            $this->lang->myline('operation')
        );
        $return_data = [
            'key' => $key_table,
            'values' => $result,
            'page_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
                'pages' => ceil($total_count / $limit)
            ]
        ];
        return $return_data;

    }

    /**
     * @desc 批量编辑物流轨迹状态匹配规则
     * @author Justin
     * @Date 2019/11/30
     * @param $data
     * @throws Exception
     */
    public function rule_batch_edit($data)
    {
        //物流承运商id
        $ids = $data['ids'];

        //验证数据是否存在
        $result = $this->purchase_db->select('id,carrier_code')->where_in('id', $ids)->get($this->table_name)->result_array();
        $ids_data = array_combine($ids, $ids);
        foreach ($result as $item) {
            unset($ids_data[$item['id']]);
        }
        if (count($ids_data)) throw new Exception('ID[' . implode(',', $ids_data) . ']数据不存在');

        //更新数据
        $edit_data = [
            'rule' => json_encode($data['rule']),
            'update_time' => date('Y-m-d H:i:s'),
            'update_user' => getActiveUserName(),
            'status' => $data['status']
        ];
        foreach (array_chunk($ids, 100) as $item) {
            $update_res = $this->purchase_db->where_in('id', $item)->update($this->table_name, $edit_data);
        }

        if (empty($update_res)) throw new Exception('编辑失败');

        //更新成功时清空缓存
        foreach ($result as $item) {
            $this->rediss->deleteData('RULE_INFO_' . strtoupper($item['carrier_code']));
        }

    }

    /**
     * 获取已到提货点配置规则下拉列表数据
     * @return array
     */
    public function get_drop_down_list()
    {
        $data = array(
            'address' => array(
                FLAG_PROVINCE => $this->lang->myline('province'),
                FLAG_CITY => $this->lang->myline('city'),
                FLAG_AREA => $this->lang->myline('area'),
                FLAG_TOWN => $this->lang->myline('town'),
                FLAG_LESS_PROVINCE => $this->lang->myline('less_province'),
                FLAG_LESS_CITY => $this->lang->myline('less_city'),
                FLAG_LESS_AREA => $this->lang->myline('less_area'),
                FLAG_LESS_TOWN => $this->lang->myline('less_town'),
            ),
            'relation' => array(
                KEYWORD_AND => $this->lang->myline('and'),
                KEYWORD_OR => $this->lang->myline('or'),
            )
        );
        return $data;
    }


    /**
     * 转换状态对应中文
     * @param $status
     * @return mixed
     */
    private function _get_status($status)
    {
        $data = array(
            DISABLE_STATUS => $this->lang->myline('disable'),
            ENABLE_STATUS => $this->lang->myline('enable'),
        );
        return isset($data[$status]) ? $data[$status] : $status;
    }

    /**
     * 转换对应关键字
     * @param $key
     * @return null|string
     */
    private function _get_keyword_cn($key)
    {
        if (empty($key)) return null;
        $key = explode(',', $key);
        $data = array(
            FLAG_PROVINCE => $this->lang->myline('province'),
            FLAG_CITY => $this->lang->myline('city'),
            FLAG_AREA => $this->lang->myline('area'),
            FLAG_TOWN => $this->lang->myline('town'),
            FLAG_LESS_PROVINCE => $this->lang->myline('less_province'),
            FLAG_LESS_CITY => $this->lang->myline('less_city'),
            FLAG_LESS_AREA => $this->lang->myline('less_area'),
            FLAG_LESS_TOWN => $this->lang->myline('less_town'),
        );
        $tmp_data = array();
        foreach ($key as $item) {
            $tmp_data[] = isset($data[$item]) ? $data[$item] : $item;
        }

        return implode(',', $tmp_data);
    }
}