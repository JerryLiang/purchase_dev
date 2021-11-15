<?php

/**
 * Created by PhpStorm.
 * 银行卡管理表
 * User: Jolon
 * Date: 2019/01/16 0029 11:50
 */
class Bank_card_model extends Purchase_model
{
    protected $table_name = 'bank_card';// 数据表名称

    /**
     * Bank_card_model constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取 银行卡账号简称列表
     * @author Jolon
     */
    public function get_account_short_list()
    {
        $list = $this->purchase_db->select('account_short')
            ->group_by('account_short')
            ->get($this->table_name)
            ->result_array();

        return $list ? array_column($list, 'account_short', 'account_short') : [];
    }

    /**
     * 获取 银行卡信息
     * @param int $card_id 记录ID
     * @return bool
     * @author Jolon
     */
    public function get_card($card_id)
    {
        if (empty($card_id)) return false;

        $query_builder = $this->purchase_db;
        if (is_array($card_id)) {
            $query_builder->where_in('id', $card_id);
            $results = $query_builder->get($this->table_name)->result_array();
        } else {
            $query_builder->where('id', $card_id);
            $results = $query_builder->get($this->table_name)->row_array();
        }

        return $results;
    }

    /**
     * 分页  获取银行卡列表
     * @param      $params
     * @param int $offset
     * @param int $limit
     * @return array
     * @author Jolon
     */
    public function get_card_list($params, $offset = null, $limit = null, $page = 1)
    {
        $params = $this->table_query_filter($params);// 过滤为空的元素

        $query_builder = $this->purchase_db;
        $query_builder->where('status !=', 0);// 已删除的不展示
        if (isset($params['account_number']) and !empty($params['account_number'])) {
            $query_builder->like('account_number', $params['account_number']);
            unset($params['account_number']);
        }
        if (isset($params['branch']) and !empty($params['branch'])) {
            $query_builder->like('branch', $params['branch']);
            unset($params['branch']);
        }
        if (isset($params['account_holder']) and !empty($params['account_holder'])) {
            $query_builder->like('account_holder', $params['account_holder']);
            unset($params['account_holder']);
        }
        if (isset($params['account_short']) and !empty($params['account_short'])) {
            $query_builder->like('account_short', $params['account_short']);
            unset($params['account_short']);
        }

        $query_builder->where($params);

        $query_builder_count = clone $query_builder;// 克隆一个查询 用来计数
        $total_count = $query_builder_count->count_all_results($this->table_name);
        $results = $query_builder->order_by('create_time', 'desc')->get($this->table_name, $limit, $offset)->result_array();

        $return_data = [
            'data_list' => $results,
            'paging_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
            ]
        ];

        return $return_data;
    }


    /**
     * 创建或更新 银行卡信息
     * @param $card_data
     * @return array
     * @author Jolon
     */
    public function card_create($card_data)
    {
        $return = ['code' => false, 'data' => '', 'msg' => ''];

        $id = isset($card_data['id']) ? $card_data['id'] : 0;
        if ($id) {// 传入 银行卡ID且该ID的记录存在则更新 银行卡
            $card_info = $this->get_card($id);
            $id = empty($card_info) ? 0 : $card_info['id'];
        }
        unset($card_data['id']);// 删除ID

        if ($this->check_account_exists($card_data['account_number'], $card_data['account_holder'], $id)) {
            $return['msg'] = '账号+开户人已存在其他账户信息，请保持组合字段的唯一性';
            return $return;
        }
        if ($this->check_account_short_exists($card_data['account_short'], $id)) {
            $return['msg'] = '账号简称已存在其他账户信息，请保持它的唯一性';
            return $return;
        }

        $this->purchase_db->trans_begin();
        if ($id) {// 更新
            // 更新状态 调用统一接口
            if (isset($card_data['status']) and isset($card_info['status']) and $card_info['status'] != $card_data['status']) {
                $result = $this->change_status($id, $card_data['status']);
                if ($result['code']) {
                    unset($card_data['status']);
                }
            }

            $card_data['update_id'] = getActiveUserId();
            $card_data['update_user_name'] = getActiveUserName();
            $card_data['update_time'] = date('Y-m-d H:i:s');
            $result = $this->purchase_db->where('id', $id)->update($this->table_name, $card_data);
        } else {
            $card_data['create_id'] = getActiveUserId();
            $card_data['create_user_name'] = getActiveUserName();
            $card_data['create_time'] = date('Y-m-d H:i:s');
            $result = $this->purchase_db->insert($this->table_name, $card_data);
            $id = $this->purchase_db->insert_id();
        }

        if ($result) {
            $this->purchase_db->trans_commit();
            $return['code'] = true;
            $return['data_list'] = $id;
        } else {
            $this->purchase_db->trans_rollback();
            $return['msg'] = '数据保存时出错';
        }

        return $return;
    }

    /**
     * 验证 账号+开户人 是否已经存在
     * @param string $account_number 账号
     * @param string $account_holder 开户人
     * @param int $card_id 组合验证（验证除指定ID之外是否还有 相同账号）
     * @return bool
     * @author Jolon
     */
    public function check_account_exists($account_number, $account_holder, $card_id = null)
    {
        if (empty($card_id)) {
            $result = $this->purchase_db
                ->where('account_number', $account_number)
                ->where('account_holder', $account_holder)
                ->get($this->table_name)
                ->row_array();
        } else {
            $result = $this->purchase_db
                ->where('account_number', $account_number)
                ->where('account_holder', $account_holder)
                ->where('id !=', $card_id)
                ->get($this->table_name)
                ->row_array();
        }

        if ($result) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 验证 账号简称 是否已经存在
     * @param string $account_short 账号简称
     * @param int $card_id 组合验证（验证除指定ID之外是否还有 相同账号）
     * @return bool
     * @author Jolon
     */
    public function check_account_short_exists($account_short, $card_id = null)
    {
        if (empty($card_id)) {
            $result = $this->purchase_db
                ->where('account_short', $account_short)
                ->get($this->table_name)
                ->row_array();
        } else {
            $result = $this->purchase_db
                ->where('account_short', $account_short)
                ->where('id !=', $card_id)
                ->get($this->table_name)
                ->row_array();
        }

        if ($result) {
            return true;
        } else {
            return false;
        }

    }

    /**
     * 变更 银行卡状态
     * @param int $card_id 银行卡ID
     * @param int $status 审核状态（1.可用,2.禁用）
     * @return array
     * @author Jolon
     */
    public function change_status($card_id, $status)
    {
        $return = ['code' => false, 'data' => '', 'msg' => ''];

        $card_info = $this->get_card($card_id);
        if (empty($card_info)) {
            $return['msg'] = '银行卡不存在';
            return $return;
        }
        if ($card_info['status'] == $status) {
            $return['msg'] = '状态未发生变更';
            return $return;
        }

        $result = $this->purchase_db->where('id', $card_id)->update($this->table_name, ['status' => $status]);
        if ($result) {
            $change_status = '修改状态，从【' . bankCardStatus($card_info['status']) . '】改为【' . bankCardStatus($status) . '】';
            operatorLogInsert(
                ['id' => $card_id,
                    'type' => $this->table_name,
                    'content' => '启用/禁用',
                    'detail' => $change_status
                ]);
            $return['code'] = true;
        } else {
            $return['msg'] = '修改状态时数据出错';
        }

        return $return;
    }

    /**
     * 根据支付平台 获取支付银行卡信息
     * @param string $platform 平台类型
     * @param string $account 平台对应的账号
     * @return array
     * @author Jolon
     */
    public function get_payment_account_by_platform($platform, $account = null)
    {
        $payCardId = getPaymentIdByPlatform($platform, $account);
        if ($payCardId) {
            $bank = $this->get_card($payCardId);
            if (empty($bank)) {
                return [];
            } else {
                return $bank;
            }
        } else {
            return [];
        }
    }

    public function get_account_number()
    {
        $list = $this->purchase_db->select('account_number')
            ->group_by('account_number')
            ->get($this->table_name)
            ->result_array();

        return $list ? array_column($list, 'account_number', 'account_number') : [];
    }
}