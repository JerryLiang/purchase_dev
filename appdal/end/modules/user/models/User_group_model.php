<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class User_group_model extends Purchase_model
{

    protected $table_name = 'purchase_user_relation';
    protected $table_group = 'purchase_group';
    protected $table_user_group_relation = 'purchase_user_group_relation';
    protected $table_user_info = 'purchase_user_info';

    public function __construct()
    {
        parent::__construct();
        $this->load->model('product/Product_line_model');
        $this->load->helper('user');
        $this->load->helper('status_order');
    }

    /**
     * 编辑小组（预览）
     * @param int $category_id 小组所属类型（1-非海外仓组，2-海外仓组，3-超级数据组）
     * @return array
     * @author Justin 2020-06-15
     */
    public function get_group_list($category_id)
    {
        $query_builder = $this->purchase_db;
        $query_builder->select('id,category_id,group_name,update_time');
        $query_builder->from($this->table_group);
        $query_builder->where('category_id', $category_id);
        $query_builder->where('is_del', 0);
        $results = $query_builder->order_by('create_time', 'DESC')->get()->result_array();
        return [
            'values' => $results,
        ];
    }

    /**
     * 编辑小组（保存）
     * @param $data
     * @return array
     */
    public function group_edit_batch($data)
    {
        $query = $this->purchase_db;
        //开启事务
        $query->trans_begin();
        try {
            $insert_data = array(); //新增的数据
            $update_data = array(); //更新的数据

            $update_time = $data['update_time']; //提交数据的更新时间
            $time = date('Y-m-d H:i:s');
            //获取数据的更新时间，用于判断当前提交的数据，是否在最新数据基础上修改
            if (!empty($update_time)) {
                $time_data = $query->select('update_time')->where(['category_id' => $data['category_id'], 'is_del' => 0])->get($this->table_group)->row_array();
                if (!empty($time_data) && ($time_data['update_time'] != $update_time)) {
                    throw new Exception('数据已发生变化，请关闭页面后重新编辑');
                }
            }
            //组织数据
            foreach ($data['data'] as $key => $item) {
                $tmp['id'] = $item['id'];
                $tmp['category_id'] = $data['category_id'];
                $tmp['group_name'] = $item['group_name'];
                $tmp['update_time'] = $time;
                $tmp['update_user_name'] = getActiveUserName();
                $tmp['is_del'] = 0;
                if (!empty($tmp['id'])) {
                    $update_data[$key] = $tmp;
                } else {
                    $insert_data[$key] = $tmp;
                    $insert_data[$key]['create_user_name'] = getActiveUserName();
                    $insert_data[$key]['create_time'] = $time;
                }
            }

            //更新数据
            foreach ($update_data as $item) {
                $id = $item['id'];
                unset($item['id']);
                unset($item['category_id']);
                $query->update($this->table_group, $item, ['id' => $id]);
            }

            //新增数据
            if (!empty($insert_data)) {
                //判断小组名称是否存在
                $group_info = $this->purchase_db->select('id,group_name,is_del')
                    ->from($this->table_group)
                    ->where_in('group_name', array_column($insert_data, 'group_name'))
                    ->where('category_id', $data['category_id'])
                    ->get()->result_array();
                foreach ($group_info as $item) {
                    if (1 == $item['is_del']) {
                        //更新数据
                        $group_info_tmp[$item['group_name']] = $item;
                    } else {
                        throw new Exception('小组名称[' . $item['group_name'] . ']已存在');
                    }
                }

                foreach ($insert_data as $item) {
                    unset($item['id']);
                    if (!empty($group_info_tmp[$item['group_name']])) {
                        //更新数据
                        $query->update($this->table_group, $item, ['id' => $group_info_tmp[$item['group_name']]['id']]);
                    } else {
                        //插入数据
                        $query->insert($this->table_group, $item);
                    }
                }
            }

            if ($query->trans_status() === false) {
                throw new Exception('编辑失败');
            } else {
                $query->trans_commit();
                $flag = true;
                $msg = '编辑成功';
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $flag = false;
            $msg = $e->getMessage();
        }
        return ['flag' => $flag, 'msg' => $msg];
    }

    /**
     * 删除小组
     * @param $id
     * @return array
     */
    public function group_del($id)
    {
        $query = $this->purchase_db;
        //开启事务
        $query->trans_begin();
        try {
            //判断小组是否存在
            $group_num_rows = $query->select('1')
                ->where(['id' => $id, 'is_del' => 0])
                ->get($this->table_group)->num_rows();
            if (!$group_num_rows) {
                throw new Exception('小组不存在或已被删除');
            }
            //判断小组是否存在“是否离职=是，且交接人=空”，或者“是否离职=否”的人员数据
            $num_rows = $query->select('1')->from("{$this->table_user_info} a")
                ->join("{$this->table_name} b", 'b.user_id=a.user_id')
                ->join("{$this->table_user_group_relation} c", 'c.user_map_id=b.id')
                ->group_start()
                ->group_start()
                ->where(['a.is_leave' => 1, 'b.handover_user_number' => ''])
                ->group_end()
                ->or_where('a.is_leave', 0)
                ->group_end()
                ->where('c.group_id', $id)
                ->get()->num_rows();
            if ($num_rows) {
                throw new Exception('小组存在未离职人员，不允许删除');
            }

            $time = date('Y-m-d H:i:s');
            //更新数据
            $update_data = [
                'update_time' => $time,
                'update_user_name' => getActiveUserName(),
                'is_del' => 1,
            ];
            $query->update($this->table_group, $update_data, ['id' => $id]);
            //删除小组与用户关系数据
            $query->delete($this->table_user_group_relation, ['group_id' => $id]);
            if ($query->trans_status() === false) {
                throw new Exception('删除失败');
            } else {
                $query->trans_commit();
                $flag = true;
                $msg = '删除成功';
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $flag = false;
            $msg = $e->getMessage();
        }
        return ['flag' => $flag, 'msg' => $msg];
    }

    /**
     * 获取用户列表数据
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @return array
     * @author Justin
     */
    public function get_user_list($params, $offsets, $limit, $page)
    {
        $query = $this->purchase_db;
        $query->select('a.user_id,a.user_code,a.user_name,a.phone_number,a.is_leave,a.account_number,b.id,b.rank,b.create_user_name,b.create_time');
        $query->select('b.update_user_name,b.update_time,b.handover_user_number,b.handover_user_name,GROUP_CONCAT(d.group_name) AS group_name,b.is_enable');
        $query->from("{$this->table_user_info} AS a");
        $query->join("{$this->table_name} AS b", 'b.user_id=a.user_id');
        $query->join("{$this->table_user_group_relation} AS c", 'c.user_map_id=b.id', 'LEFT');
        $query->join("{$this->table_group} AS d", 'd.id=c.group_id', 'LEFT');

        //按类型查询（必传）
        $query->where('b.category_id', $params['category_id'], false);
        $query->where('b.is_del', 0); // 只展示未删除的数据
        //按工号查询
        if (!empty($params['user_number'])) {
            $query->where('a.user_code', $params['user_number']);
        }
        //按小组查询
        if (!empty($params['group_id'])) {
            $query->where('c.group_id', $params['group_id'], false);
        }
        //按职级查询
        if (!empty($params['rank'])) {
            if (is_array($params['rank'])) {
                $query->where_in('b.rank', $params['rank'], false);
            } else {
                $query->where('b.rank', $params['rank'], false);
            }
        }
        //按是否离职查询
        if (isset($params['is_leave']) && is_numeric($params['is_leave'])) {
            $query->where('a.is_leave', $params['is_leave'], false);
        }
        //按交接人查询(优先按照交接人查询，再按照是否为空查询)
        if (!empty($params['handover_user_number'])) {
            $query->where('b.handover_user_number', $params['handover_user_number']);
        } elseif (!empty($params['have_handover_user'])) {
            //按交接人是否为空查询（1-为空，2-非空）
            if (1 == $params['have_handover_user']) {
                $query->where('b.handover_user_number', '');
            } else {
                $query->where('b.handover_user_number <>', '');
            }
        }
        //按是否启用状态查询
        if (is_numeric($params['enable_status'])) {
            $query->where('b.is_enable', $params['enable_status'], false);
        }
        //分组
        $query->group_by('a.user_id');

        $count_qb = clone $query;
        //统计总数要加上前面筛选的条件
        $total_count = $count_qb->get()->num_rows();

        //排序
        $this->purchase_db->order_by('b.update_time', 'DESC');

        //查询数据
        $result = $query->limit($limit, $offsets)->get()->result_array();
        //转换数据
        foreach ($result as &$item) {
            //转换职级对应中文
            $item['rank_cn'] = getUserRank($item['rank']);
            //转换是否离职对应中文
            $item['is_leave_cn'] = getUserLeaveStatus($item['is_leave']);
            //超级数据组，统一显示‘所有小组’
            if (3 == $params['category_id']) {
                $item['group_name'] = '所有小组';
            } elseif (2 == $params['category_id'] && 4 == $item['rank']) {
                //海外仓组，副经理所属小组统一显示‘海外仓所有组’
                $item['group_name'] = '海外仓所有组';
            } elseif (1 == $params['category_id'] && 4 == $item['rank']) {
                //非海外仓组，副经理所属小组统一显示‘非海外仓所有组’
                $item['group_name'] = '非海外仓所有组';
            }
        }

        return [
            'values' => $result,
            'page_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
                'pages' => ceil($total_count / $limit),
            ],
            'drop_down_box' => [
                'user_list' => $this->_get_user_list($params['category_id']), //用户下拉列表
                'handover_user_list' => $this->_get_handover_user_list(), //交接人下拉列表
                'user_rank' => getUserRank(), //职级下拉列表
                'user_leave_status' => getUserLeaveStatus(), //是否离职下拉列表
                'handover_user_status' => getHandoverUserStatus(), //交接人是否为空下拉列表
                'group_list' => $this->_get_group_list($params['category_id']), //小组下拉列表
                'enable_status' => (object) ['0' => '否', '1' => '是'],
            ],
        ];
    }

    /**
     * 批量添加人员
     * @param int $category_id 小组所属类型（1-非海外仓组，2-海外仓组，3-超级数据组）
     * @param array $user_data
     * @return array
     */
    public function user_add_batch($category_id, $user_data)
    {
        $query = $this->purchase_db;
        //开启事务
        $query->trans_begin();
        try {
            $time = date('Y-m-d H:i:s');
            $create_user = getActiveUserName();
            $user_insert_data = []; //新增用户表数据
            $relation_insert_data = []; //新增用户小组关系表数据

            //验证数据
            $this->load->model('user/Purchase_user_model');
            foreach ($user_data['data'] as $key => $item) {
                //判断工号是否存在
                $user_info = $this->Purchase_user_model->get_user_info_by_staff_code($item['user_number']);
                if (!$user_info) {
                    throw new Exception('用户工号[' . $item['user_number'] . ']不存在');
                }
                if (3 != $category_id) {
                    //判断用户是否已禁用
                    $num_rows = $query->where(['user_id' => $user_info['user_id'], 'is_enable' => 0])->get($this->table_name)->num_rows();
                    if ($num_rows) {
                        throw new Exception('用户[' . $user_info['user_name'] . ']已被禁用，不允许再次添加');
                    }
                }

                //判断“组员”用户是否已经绑定小组
                //                if (isset($item['rank']) && 1 == $item['rank']) {
                //                    $num_rows = $query->where(['user_id' => $user_info['user_id'], 'rank' => 1])->get($this->table_name)->num_rows();
                //                } else {
                $num_rows = $query->where(['user_id' => $user_info['user_id'], 'category_id' => $category_id])->get($this->table_name)->num_rows();
//                }
                if ($num_rows) {
                    throw new Exception('用户[' . $user_info['user_name'] . ']已绑定本组或其他组');
                }

                //新增权限配置用户主表数据
                $user_insert_data['master'][$key] = [
                    'user_id' => $user_info['user_id'],
                    'user_code' => $item['user_number'],
                    'user_name' => $item['user_name'],
                    'phone_number' => $item['phone_number'],
                    'is_leave' => isset($item['is_leave']) ? $item['is_leave'] : 0,
                    'account_number' => isset($item['account_number']) ? $item['account_number'] : '',
                ];
                //新增权限配置用户关系表数据
                $user_insert_data['map'][$key] = [
                    'user_id' => $user_info['user_id'],
                    'category_id' => $category_id,
                    'rank' => isset($item['rank']) ? $item['rank'] : 0,
                    'handover_user_number' => '',
                    'handover_user_name' => '',
                    'create_user_name' => $create_user,
                    'create_time' => $time,
                    'update_user_name' => $create_user,
                    'update_time' => $time,
                ];
                //超级数据组和职级为‘副经理’的用户，不需要添加小组关系
                if (3 != $category_id && isset($item['rank']) && 4 != $item['rank']) {
                    //新增用户小组关系表数据
                    $relation_insert_data[$user_info['user_id']] = [
                        'user_map_id' => 0,
                        'group_ids' => $item['group_ids'],
                    ];
                }
            }

            //超级数据组和职级为‘副经理’的用户，不需要添加小组关系
            if (3 != $category_id && !empty($relation_insert_data)) {
                //判断小组是否存在
                $group_ids = array_unique(array_reduce(array_column($relation_insert_data, 'group_ids', 'group_ids'), 'array_merge', []));
                $group_num_rows = $this->purchase_db->select('1')
                    ->where_in('id', $group_ids)
                    ->where(['category_id' => $category_id, 'is_del' => 0])
                    ->get($this->table_group)->num_rows();
                if (count($group_ids) != $group_num_rows) {
                    throw new Exception('所选小组已发生数据变化，请关闭页面重新添加');
                }
            }

            //新增数据
            if (!empty($user_insert_data)) {
                //新增权限配置用户主表
                $existed_user = $this->purchase_db->select('user_id')
                    ->where_in('user_id', array_column($user_insert_data['master'], 'user_id'))
                    ->get($this->table_user_info)->result_array();
                if (!empty($existed_user)) {
                    $existed_user = array_column($existed_user, 'user_id', 'user_id');
                }
                foreach ($user_insert_data['master'] as $item) {
                    if (isset($existed_user[$item['user_id']])) {
                        $user_id = $item['user_id'];
                        unset($item['user_id']);
                        $query->update($this->table_user_info, $item, ['user_id' => $user_id]);
                    } else {
                        $query->insert($this->table_user_info, $item);
                    }
                }
                //新增权限配置用户关系表
                foreach ($user_insert_data['map'] as $item) {
                    $query->insert($this->table_name, $item);
                    $user_map_id = $query->insert_id();
                    if (empty($user_map_id)) {
                        throw new Exception('新增权限配置用户关系表失败');
                    }
                    //设置user_map_id
                    if (isset($relation_insert_data[$item['user_id']])) {
                        $relation_insert_data[$item['user_id']]['user_map_id'] = $user_map_id;
                    }
                }
            }

            //新增用户小组关系表
            foreach ($relation_insert_data as $item) {
                foreach ($item['group_ids'] as $group_id) {
                    $query->insert($this->table_user_group_relation, ['user_map_id' => $item['user_map_id'], 'group_id' => $group_id]);
                }
            }

            if ($query->trans_status() === false) {
                throw new Exception('添加失败');
            } else {
                $query->trans_commit();
                $flag = true;
                $msg = '添加成功';
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $flag = false;
            $msg = $e->getMessage();
        }
        return ['flag' => $flag, 'msg' => $msg];
    }

    /**
     * 获取编辑人员信息(预览)
     * @param $id
     * @param int $is_check 是否作为检查人员是否存在使用
     * @return array
     * @throws Exception
     */
    public function get_user_edit($id, $is_check = 0)
    {
        $user_info = $this->purchase_db->select('b.user_id,b.user_code,b.user_name,b.phone_number,b.is_leave,b.account_number,a.id,a.category_id,a.rank,GROUP_CONCAT(c.group_id) AS group_ids')
            ->from("{$this->table_name} a")
            ->join("{$this->table_user_info} b", 'b.user_id=a.user_id')
            ->join("{$this->table_user_group_relation} c", 'c.user_map_id=a.id', 'LEFT')
            ->where('a.id', $id, false)
            ->where('a.is_enable', 1, false)
            ->group_by('a.id')
            ->get()->row_array();
        if (empty($user_info)) {
            return ['flag' => false, 'msg' => '未获取到该人员的数据，或已被禁用'];
        }

        //是否作为检查人员是否存在使用
        if ($is_check) {
            return ['flag' => true, 'data' => $user_info];
        }
        //转换所属小组数据格式
        if (!empty($user_info['group_ids'])) {
            $user_info['group_ids'] = explode(',', $user_info['group_ids']);
        } else {
            $user_info['group_ids'] = [];
        }

        $data = [
            'value' => $user_info,
            'drop_down_box' => [
                'user_rank' => getUserRank(), //职级下拉列表
                'user_leave_status' => getUserLeaveStatus(), //是否离职下拉列表
                'group_list' => $this->_get_group_list($user_info['category_id']), //小组下拉列表
                '1688_account' => $this->get_1688_account(),
            ],
        ];
        return ['flag' => true, 'data' => $data, 'msg' => ''];
    }

    /**
     * 保存编辑用户（保存）
     * @param $params
     * @param $id
     * @return array
     */
    public function get_user_edit_save($params, $id)
    {
        $query = $this->purchase_db;
        //开启事务
        $query->trans_begin();
        try {

            //检查用户是否被禁用
            $res = $this->get_user_edit($id, 1);
            if (!$res['flag']) {
                throw new Exception($res['msg']);
            }

            if (3 != $res['data']['category_id']) {
                //副经理不需要添加小组关系
                if (4 != $params['rank']) {
                    //判断小组是否存在
                    $group_num_rows = $this->purchase_db->select('1')
                        ->where_in('id', $params['group_ids'])
                        ->where(['category_id' => $res['data']['category_id'], 'is_del' => 0])
                        ->get($this->table_group)->num_rows();
                    if (count($params['group_ids']) != $group_num_rows) {
                        throw new Exception('所选小组已发生数据变化，请关闭页面重新编辑');
                    }
                }
                //用户为组员时，判断是否已经绑定其他组
                if (1 == $params['rank']) {
                    $num_rows = $this->purchase_db->where(['id <>' => $id, 'rank' => 1, 'is_del' => 0, 'is_enable' => 1, 'user_id' => $res['data']['user_id']])
                        ->get($this->table_name)->num_rows();
                    if ($num_rows) {
                        throw new Exception('该用户已绑定其他组');
                    }
                }
                //清除当前用户，当前类型小组的旧所属小组关系
                $query->delete($this->table_user_group_relation, ['user_map_id' => $id]);
                //保存用户关系表
                $set = [
                    'rank' => $params['rank'],
                    'update_user_name' => getActiveUserName(),
                    'update_time' => date('Y-m-d H:i:s'),
                ];
                $this->purchase_db->where('id', $id)->update($this->table_name, $set);
                //副经理不需要添加小组关系
                if (4 != $params['rank']) {
                    //保存用户小组关系表
                    foreach ($params['group_ids'] as $group_id) {
                        $this->purchase_db->insert($this->table_user_group_relation, ['user_map_id' => $id, 'group_id' => $group_id]);
                    }
                }
                //用户主表数据
                $set_master = [
                    'phone_number' => $params['phone_number'],
                    'is_leave' => $params['is_leave'],
                    'account_number' => $params['account_number'],
                ];
            } else {
                //用户主表数据
                $set_master = [
                    'phone_number' => $params['phone_number'],
                ];
            }
            //保存用户主表
            $this->purchase_db->where('user_code', $res['data']['user_code'])->update($this->table_user_info, $set_master);

            if ($query->trans_status() === false) {
                throw new Exception('编辑失败');
            } else {
                $query->trans_commit();
                $flag = true;
                $msg = '编辑成功';
            }
        } catch (Exception $e) {
            $query->trans_rollback();
            $flag = false;
            $msg = $e->getMessage();
        }
        return ['flag' => $flag, 'msg' => $msg];
    }

    /**
     * 删除用户（超级数据组用户）
     * @param $id
     * @return array
     */
    public function user_del($id)
    {
        $this->purchase_db->trans_begin();
        try {
            $user_relation = $this->purchase_db->select('*')->where(['id' => $id])->get($this->table_name)->row_array();
            if (!$user_relation) {
                throw new Exception('数据不存在或已被删除');
            }

            if ($user_relation['category_id'] == 3) {
                //删除用户
                $this->purchase_db->delete($this->table_name, ['id' => $id]);
            } else {
                $user_info = $this->purchase_db->select('*')
                    ->where('user_id', $user_relation['user_id'])
                    ->get($this->table_user_info)
                    ->row_array();

                // 验证采购单是否完结
                $order_over = $this->purchase_db->where('buyer_id', $user_relation['user_id'])
                    ->where_not_in('purchase_order_status', [PURCHASE_ORDER_STATUS_ALL_ARRIVED, PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE, PURCHASE_ORDER_STATUS_CANCELED])
                    ->get('purchase_order')
                    ->num_rows();
                if ($order_over > 0) {
                    throw new Exception('删除人员必须先完结其名下所有采购单');
                }

                // 验证交接人
                $handover_user = $this->purchase_db->select('id')
                    ->where('handover_user_number', $user_info['user_code'])
                    ->get($this->table_name)
                    ->num_rows();
                if ($handover_user > 0) {
                    throw new Exception('删除人员所属的交接人必须先转交接给其他人');
                }

                //删除用户
                $this->purchase_db->where('id', $id)->update($this->table_name, ['is_del' => 1]);
                // 权限配置用户小组关系表
                $this->purchase_db->delete($this->table_user_group_relation, ['user_map_id' => $id]);
            }

            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('删除失败');
            } else {
                $this->purchase_db->trans_commit();
                $return = ['msg' => '删除成功', 'flag' => true];
            }
        } catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            $return = ['msg' => $exc->getMessage(), 'flag' => false];
        }
        return $return;
    }

    /**
     * 添加交接人
     * @param int $id
     * @param string $handover_user_number 交接人工号
     * @return array
     */
    public function get_handover_person($id, $handover_user_number)
    {
        //开启事务
        $this->purchase_db->trans_begin();
        try {
            //判断待交接记录是否被禁用
            $user_info = $this->purchase_db->select('a.user_code,a.user_name')
                ->from("{$this->table_user_info} a")
                ->join("{$this->table_name} b", 'b.user_id=a.user_id')
                ->where(['b.id' => $id, 'b.is_enable' => 1])
                ->get()->row_array();
            if (empty($user_info)) {
                throw new Exception('用户不存在或已被禁用，不允许交接');
            }
            if ($user_info['user_code'] == $handover_user_number) {
                throw new Exception('交接人不能与待交接用户相同');
            }
            //判断交接人是否有效
            $handover_user_info = $this->_get_handover_user_list($handover_user_number);
            if (empty($handover_user_info)) {
                throw new Exception('交接人必须为有效的“非海外/海外仓组”在职人员');
            }

            //更新交接人
            $set = [
                'handover_user_number' => $handover_user_number,
                'handover_user_name' => isset($handover_user_info[$handover_user_number]) ? $handover_user_info[$handover_user_number] : '',
                'update_user_name' => getActiveUserName(),
                'update_time' => date('Y-m-d H:i:s'),
            ];
            $this->purchase_db
                ->where('id', $id)
                ->update($this->table_name, $set);

            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('添加失败');
            } else {
                $this->purchase_db->trans_commit();
                $flag = true;
                $msg = '添加成功';
            }
        } catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            $flag = false;
            $msg = $e->getMessage();
        }
        return ['flag' => $flag, 'msg' => $msg];
    }

    /**
     * 数据权限控制（根据用户id获取所属权限【用户id集合】）
     * @param int $user_id
     * @return array|bool 超级数据组用户直接返回true，其他类型小组用户根据实际权限配置获取用户id集合
     */
    public function get_jurisdiction($user_id)
    {
        //用户权限数据
        $authorization_data = [];

        if (empty($user_id)) {
            return $authorization_data;
        }

        //查询用户是否有权限
        $power_data = $this->purchase_db->select("a.id,a.user_id,a.category_id,a.rank,a.handover_user_number,a.is_enable,a.is_del")
            ->select("b.user_code")
            ->from("{$this->table_name} a")
            ->join("{$this->table_user_info} b", 'a.user_id=b.user_id')
            ->where(['a.user_id' => $user_id, 'a.is_enable' => 1, 'a.is_del' => 0])
            ->get()->result_array();

        //数据权限配置中心没有配置该用户，返回自身user_id
        if (empty($power_data)) {
            return [$user_id];
        }

        //1、优先判断用户是否为‘超级数据组用户’(超级数据组用户有全部数据权限)
        $category_id_arr = array_unique(array_column($power_data, 'category_id'));
        if (in_array(3, $category_id_arr)) {
            return true;
        }

        //非海外仓组或海外仓组
        foreach ($power_data as $item) {
            switch ($item['rank']) {
                case 1: //组员
                    //若为组员时，只有组员数据
                    $authorization_data[] = $item['user_id'];
                    break;
                case 2: //组长(拥有分配所在组所有用户的权限集合)
                case 3: //主管(拥有分配所在组所有用户的权限集合)
                    $authorization_data[] = $item['user_id'];
                    //根据用户记录id获取该组下的所有组员user_id
                    $res = $this->_get_group_members($item['id']);
                    if (!empty($res)) {
                        $authorization_data = array_unique(array_merge($authorization_data, $res));
                    }
                    break;
                case 4: //副经理(拥有所在类型的所有小组，所有用户的权限集合)
                    $authorization_data[] = $item['user_id'];
                    $res = $this->_get_group_members($item['category_id'], true);
                    if (!empty($res)) {
                        $authorization_data = array_unique(array_merge($authorization_data, $res));
                    }
                    break;
                default:
                    break;
            }
            //判断用户是否为交接人
            $res = $this->is_handover_user($item['user_code']);
            if (!empty($res)) {
                $authorization_data = array_unique(array_merge($authorization_data, $res));
            }
        }
        return !empty($authorization_data) ? array_values($authorization_data) : [];
    }

    /**
     * 数据权限控制（根据当前登录用户ID获取自己所属的组）
     * 规则：
     *      如果用户配置了 “超级数据组” 或者同时配置了“海外仓组”和“非海外仓组”  权限 则返回 true,不做权限控制
     *      如果用户配置了 “海外仓组”   权限 则返回的结果包含 海外仓业务线
     *      如果用户配置了 “非海外仓组” 权限 则返回的结果包含所有 非海外仓业务线
     *      如果用户没有配置任何组权限 则返回true，不做权限控制
     *
     * @param int $user_id
     * @return array|bool 超级数据组用户直接返回true，其他类型小组返回指定业务线ID
     */
    public function user_group_check($user_id)
    {
        if (!is_int($user_id) and !is_numeric($user_id)) {
            return true;
        }

        $user_id = intval($user_id);
        if (empty($user_id)) {
            return true;
        }

        $user_cache_key = md5('user_group_check' . $user_id);
        $view_purchase_type = $this->rediss->getData($user_cache_key);

        if (empty($view_purchase_type)) {
            $user_category_id = $this->purchase_db->select('user_id,category_id')
                ->where('user_id', $user_id)
                ->where('is_enable', 1)
                ->where('is_del', 0)
                ->get($this->table_name)
                ->result_array();

            if (empty($user_category_id)) { // 没有设置该用户
                $res_views = true;
            } else {
                $purchase_type_ids = array_keys(getPurchaseType());
                $category_ids = array_column($user_category_id, 'category_id', 'category_id');

                if (array_key_exists(3, $category_ids)) { // 超级用户组
                    $res_views = true;
                } else {
                    $view_purchase_type = [];
                    if (array_key_exists(2, $category_ids)) { // 海外仓组
                        $view_purchase_type[] = PURCHASE_TYPE_OVERSEA;
                        $view_purchase_type[] = PURCHASE_TYPE_FBA_BIG;
                    }
                    if (array_key_exists(1, $category_ids)) { // 非海外仓组
                        $view_purchase_type = array_merge($view_purchase_type, array_diff($purchase_type_ids, [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])); // 去除海外业务线的所有业务线
                    }
                    if (count($view_purchase_type) == count($purchase_type_ids)) { // 可以查看所有业务线则不设置条件
                        $res_views = true;
                    } elseif (empty($view_purchase_type)) {
                        $res_views = true;
                    } else {
                        $res_views = $view_purchase_type;
                    }
                }
            }

            $this->rediss->setData($user_cache_key, json_encode($res_views), 1200);

        } else {
            $res_views = json_decode($view_purchase_type, true);
        }

        return $res_views;
    }

    /**
     * 根据用户记录ID或根据副经理所在小组类型ID，查询所拥有的用户权限集合，返回user_id
     * @param int $id 根据用户记录ID或根据副经理所在小组类型ID
     * @param bool $manager 是否为副经理
     * @param string $type 类型（admin-系统admin或超级用户，查询所有人，包括副经理）
     * @return array
     */
    private function _get_group_members($id, $manager = false, $type = '')
    {
        if ($manager) { //副经理(拥有所在小组类型的所有小组，所有用户的权限集合)
            $this->purchase_db->select('a.user_id');
            $this->purchase_db->from("{$this->table_name} a");

            /**之前未统计超级管理员，现在加上**/
            if ('admin' != $type) {
                $this->purchase_db->join("{$this->table_user_group_relation} b", 'a.id=b.user_map_id');
                $this->purchase_db->join("{$this->table_group} c", 'c.id=b.group_id');
                $this->purchase_db->where_in('c.category_id', [$id, 3]);
            } else {
                $this->purchase_db->where_in('a.category_id', [$id, 3]);
            }
            $this->purchase_db->where(['a.is_del' => 0, 'a.is_enable' => 1]);
            $this->purchase_db->group_by('a.user_id');
            $user_ids = $this->purchase_db->get()->result_array();

            // 追加副经理的数据
            $manager_list = $this->purchase_db->select('user_id')
                ->from($this->table_name)
                ->where(['is_del' => 0, 'is_enable' => 1, 'rank' => 4, 'category_id' => $id])
                ->group_by('user_id')
                ->get()
                ->result_array();
            if (!empty($manager_list)) {
                $user_ids = array_merge($user_ids, $manager_list);
            }

        } else {
            //1、先查询用户所在小组
            $group_ids = $this->purchase_db->select('group_id')
                ->where('user_map_id', $id)
                ->group_by('group_id')
                ->get($this->table_user_group_relation)
                ->result_array();
            if (!empty($group_ids)) {
                $group_ids = array_column($group_ids, 'group_id');
            } else {
                return [];
            }
            //2、根据小组id获取小组下的所有组员id
            $user_ids = $this->purchase_db->select('a.user_id')
                ->from("{$this->table_name} a")
                ->join("{$this->table_user_group_relation} b", 'a.id=b.user_map_id')
                ->where_in('b.group_id', $group_ids)
                ->where(['a.is_del' => 0, 'a.is_enable' => 1])
                ->group_by('a.user_id')
                ->get()->result_array();
        }

        if (!empty($user_ids)) {
            $user_ids = array_column($user_ids, 'user_id');
        } else {
            $user_ids = [];
        }
        return $user_ids;
    }

    /**
     * 根据工号判断用户是否为交接人，并返回交接用户id
     * @param $user_number
     * @return array|bool
     */
    public function is_handover_user($user_number)
    {
        $power_data = $this->purchase_db
            ->select('user_id')
            ->where('handover_user_number', $user_number)
            ->get($this->table_name)
            ->result_array();
        if (empty($power_data)) {
            return [];
        }

        return array_column($power_data, 'user_id');
    }

    /**
     * 根据小组所属类型获取用户下拉数据
     * @param $category_id
     * @return array|bool
     */
    private function _get_user_list($category_id)
    {
        $result = $this->purchase_db->select('a.user_code,a.user_name')
            ->from("{$this->table_user_info} a")
            ->join("{$this->table_name} b", 'b.user_id=a.user_id')
            ->where('b.category_id', $category_id)
            ->group_by('a.user_id')
            ->get()->result_array();
        if (!empty($result)) {
            return array_column($result, 'user_name', 'user_code');
        } else {
            return [];
        }
    }

    /**
     * 获取交接人下拉数据(从类型（1-非海外仓组，2-海外仓组）中获取)
     * @param string $user_code 传入用户工号时，用于判断交接人是否有效
     * @return array|bool
     */
    private function _get_handover_user_list($user_code = '')
    {

        $this->purchase_db->select('a.user_code,a.user_name');
        $this->purchase_db->from("{$this->table_user_info} a");
        $this->purchase_db->join("{$this->table_name} b", 'b.user_id=a.user_id');
        $this->purchase_db->where_in('b.category_id', [1, 2]);
        $this->purchase_db->where(['b.is_del' => 0, 'a.is_leave' => 0, 'b.is_enable' => 1]);
        if (!empty($user_code)) {
            $this->purchase_db->where(['a.user_code' => $user_code]);
        }
        $this->purchase_db->group_by('a.user_id');
        $result = $this->purchase_db->get()->result_array();

        if (!empty($result)) {
            return array_column($result, 'user_name', 'user_code');
        } else {
            return [];
        }
    }

    /**
     * 获取小组下拉列表
     * @param $category_id
     * @return array|bool
     */
    private function _get_group_list($category_id)
    {
        if (!is_array($category_id)) {
            $result = $this->purchase_db->select('id,group_name')
                ->from($this->table_group)
                ->where('category_id', $category_id)
                ->where('is_del', 0)
                ->get()->result_array();
        }

        if (is_array($category_id)) {

            $result = $this->purchase_db->select('id,group_name')
                ->from($this->table_group)
                ->where_in('category_id', $category_id)
                ->where('is_del', 0)
                ->get()->result_array();
        }
        if (!empty($result)) {
            return array_column($result, 'group_name', 'id');
        } else {
            return [];
        }
    }

    /**
     * 获取小组下拉列表
     * @param $category_id
     * @return array
     */

    public function getGroupList($category_id)
    {

        return $this->_get_group_list($category_id);
    }

    /**
     * 获取1688账号
     * @return array|bool
     */
    public function get_1688_account()
    {
        //获取1688账号
        $result = $this->purchase_db
            ->select('account')
            ->get('alibaba_account')
            ->result_array();
        if (!empty($result)) {
            return array_column($result, 'account', 'account');
        } else {
            return [];
        }
    }

    /**
     * 启用禁用
     * @param $id
     * @param $enable_status
     * @return array
     */
    public function change_enable_status($id, $enable_status)
    {
        //开启事务
        $this->purchase_db->trans_begin();
        try {
            $record = $this->purchase_db->select('user_id,handover_user_number,is_enable,rank')
                ->where(['id' => $id])
                ->get($this->table_name)->row_array();
            if (empty($record)) {
                throw new Exception('数据不存在');
            } elseif ($record['is_enable'] == $enable_status) {
                if (0 == $enable_status) {
                    throw new Exception('用户已经是禁用状态，请刷新再重试');
                } else {
                    throw new Exception('用户已经是启用状态，请刷新再重试');
                }
            }

            //禁用前是否已交接
            if (0 == $enable_status) {
                if (empty($record['handover_user_number'])) {
                    throw new Exception('交接完成后才允许禁用');
                }
                //判断交接人是否已离职
                $num_rows = $this->purchase_db->select('1')
                    ->where(['user_code' => $record['handover_user_number'], 'is_leave' => 1])
                    ->get($this->table_user_info)->num_rows();
                if ($num_rows) {
                    throw new Exception('交接人已离职不允许禁用');
                }
            } else {
                //启用前判断，其他小组类型中是否存在未禁用的组员，启用组员时判断其他小组类型中是否存在未禁用的用户
                if (1 != $record['rank']) {
                    $this->purchase_db->where('rank', 1);
                }
                $this->purchase_db->where(['user_id' => $record['user_id'], 'is_enable' => 1]);
                $num_rows = $this->purchase_db->get($this->table_name)->num_rows();
                if ($num_rows) {
                    throw new Exception('其他小组存在未禁用的用户，不允许启用');
                }
            }

            //保存数据
            $set = [
                'is_enable' => $enable_status,
                'update_user_name' => getActiveUserName(),
                'update_time' => date('Y-m-d H:i:s'),
            ];
            $this->purchase_db
                ->where('id', $id)
                ->update($this->table_name, $set);

            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('操作失败');
            } else {
                $this->purchase_db->trans_commit();
                $flag = true;
                $msg = '操作成功';
            }
        } catch (Exception $e) {
            $this->purchase_db->trans_rollback();
            $flag = false;
            $msg = $e->getMessage();
        }
        return ['flag' => $flag, 'msg' => $msg];
    }

    /**
     * 获取小组类型下拉数据
     * 返回数据说明['id1_id2'=>'非海外仓组','id1_id2'=>'海外仓组']
     * id1：角色类型（4-属于权限中心‘admin’角色，3-属于‘超级数据组’角色，2-属于’非海外仓组‘或’海外仓组‘角色）
     * id2:小组类型（1-非海外仓组，2-海外仓组）
     * @return array
     */
    public function get_category_dropdown_list()
    {
        $user_id = getActiveUserId(); //获取当前用户id
        //未获取到用户id，返回空数据
        if (empty($user_id)) {
            return [];
        }

        //OA的admin角色也是属于超级数据组
        $user_role = get_user_role(); //获取登录用户角色
        if (in_array('admin', $user_role)) {
            return [
                ['value' => '4_1', 'label' => '非海外仓组'],
                ['value' => '4_2', 'label' => '海外仓组'],
            ];
        }

        //获取用户归属小组类型
        $result = $this->purchase_db->select('category_id')
            ->where(['user_id' => $user_id, 'is_enable' => 1, 'is_del' => 0])
            ->get($this->table_name)->result_array();
        if (empty($result)) {
            return [];
        }

        $category_id_arr = array_unique(array_column($result, 'category_id'));
        if (in_array(3, $category_id_arr)) { //用户属于’超级数据组‘
            $dropdown_data = [
                ['value' => '3_1', 'label' => '非海外仓组'],
                ['value' => '3_2', 'label' => '海外仓组'],
            ];
        } else { //用户属于’非海外仓组‘或’海外仓组‘
            if (2 == count($category_id_arr)) { //如果用户同时属于’非海外仓组‘和’海外仓组‘
                $dropdown_data = [
                    ['value' => '2_1', 'label' => '非海外仓组'],
                    ['value' => '2_2', 'label' => '海外仓组'],
                ];
            } else { //否则用户只属于其中一个组
                $dropdown_data = (1 == $category_id_arr[0]) ? [['value' => '2_1', 'label' => '非海外仓组']] : [['value' => '2_2', 'label' => '海外仓组']];
            }
        }
        return $dropdown_data;
    }

    /**
     * 获取用户所在小组，下拉数据
     * @param int $group_type 角色类型（4-属于权限中心‘admin’角色，3-属于‘超级数据组’角色，2-属于’非海外仓组‘或’海外仓组‘角色）
     * @param int $category_id 小组类型（1-非海外仓组，2-海外仓组）
     * @return array
     */
    public function get_group_dropdown_list($group_type, $category_id)
    {
        $dropdown_data = []; //下拉数据
        $user_id = getActiveUserId(); //获取当前用户id
        //未获取到用户id，返回空数据
        if (empty($user_id) or empty($category_id)) {
            return $dropdown_data;
        }

        //OA的admin角色也是属于超级数据组
        if (4 == $group_type) {
            return $this->_get_all_group($category_id);
        }

        //查询用户是否配置权限
        $this->purchase_db->select("a.id,a.user_id,a.category_id,a.rank");
        $this->purchase_db->from("{$this->table_name} a");
        $this->purchase_db->join("{$this->table_user_info} b", 'a.user_id=b.user_id');
        $this->purchase_db->where(['a.user_id' => $user_id, 'a.is_enable' => 1, 'a.is_del' => 0]);
        //页面传参只有两个下拉数据，所以‘超级数据组’用户查询时，在此处需要转换
        if (3 == $group_type) {
            $this->purchase_db->where('a.category_id', 3);
        } else {
            $this->purchase_db->where('a.category_id', $category_id);
        }

        $this->purchase_db->group_by('a.id');
        $power_data = $this->purchase_db->get()->result_array();

        //数据权限配置中心没有配置该用户，返回空数据
        if (empty($power_data)) {
            return $dropdown_data;
        }

        //1、优先判断用户是否为‘超级数据组用户’(超级数据组用户有全部数据权限，返回查询小组类型下的所有小组)
        $category_id_arr = array_unique(array_column($power_data, 'category_id'));
        if (in_array(3, $category_id_arr)) {
            return $this->_get_all_group($category_id);
        } else { //2、非海外仓组或海外仓组
            foreach ($power_data as $item) {
                if (in_array($item['rank'], [1, 2, 3])) {
                    //1、查询用户所在小组(职级=组员/组长/主管，根据所属关系查询所在小组)
                    $group_info = $this->purchase_db->select('a.id AS group_id,a.group_name,a.category_id')
                        ->from("$this->table_group a")
                        ->join("$this->table_user_group_relation b", 'b.group_id=a.id')
                        ->where('b.user_map_id', $item['id'])
                        ->group_by('group_id')
                        ->get()->result_array();
                } else {
                    //职级=副经理，查询所在小组类型下的所有小组
                    $group_info = $this->purchase_db->select('id AS group_id,group_name,category_id')
                        ->where('category_id', $item['category_id'])
                        ->get($this->table_group)->result_array();
                }
                if (!empty($group_info)) {
                    foreach ($group_info as $key => $value) {
                        $dropdown_data[] = [
                            'value' => $value['group_id'],
                            'label' => $value['group_name'],
                        ];
                    }
                    unset($group_info);
                }
            }
        }
        return $dropdown_data;
    }

    /**
     * 根据小组类型，获取小组下拉框数据
     * @param $category_id
     * @return array|bool
     */
    private function _get_all_group($category_id)
    {
        $dropdown_data = [];
        $group_info = $this->purchase_db->select('id AS group_id,group_name')
            ->where('is_del', 0)
            ->where('category_id', $category_id)
            ->get($this->table_group)
            ->result_array();
        if (empty($group_info)) {
            return $dropdown_data;
        } else {
            foreach ($group_info as $key => $value) {
                $dropdown_data[] = [
                    'value' => $value['group_id'],
                    'label' => $value['group_name'],
                ];
            }
            return $dropdown_data;
        }
    }

    /**
     * 根据小组ID获取采购员下拉框数据
     * @param int|array $group_id
     * @return array|bool
     */
    public function get_buyer_by_group($group_id)
    {
        if (!is_array($group_id)) { // 转换为数组，按数组批量查询
            $group_id = [$group_id];
        }

        $dropdown_data = [];
        $result = $this->purchase_db->select('a.user_id,a.user_name')
            ->from("$this->table_user_info a")
            ->join("$this->table_name b", 'b.user_id=a.user_id')
            ->join("$this->table_user_group_relation c", 'c.user_map_id=b.id')
            ->where_in('c.group_id', $group_id)
            ->where(['b.is_enable' => 1, 'b.is_del' => 0])
            ->get()->result_array();
        if (!empty($result)) {
            foreach ($result as $key => $item) {
                $dropdown_data[] = [
                    'value' => $item['user_id'],
                    'label' => $item['user_name'],
                ];
            }
            return $dropdown_data;
        } else {
            return $dropdown_data;
        }
    }

    /**
     * 根据用户id在权限配置中，获取用户所在小组的全部成员
     * @param $user_id
     * @return array
     */
    public function get_group_members_all($user_id)
    {
        // 在权限配置中，获取用户所在小组的全部成员；如果为超级组用户或admin则返回空数组
        $power_data = $this->purchase_db->select("id")
            ->where(['user_id' => $user_id, 'is_enable' => 1, 'is_del' => 0, 'category_id' => 1])
            ->get($this->table_name)->row_array();
        //数据权限配置中心没有配置该用户，返回空数组
        if (empty($power_data)) {
            return [];
        }
        return $this->_get_group_members($power_data['id']);
    }

    /**
     * 工作台页面数据展示，权限控制
     * 按小组类型查询，获取所属小组类型下，权限范围内所有小组下的所有采购员
     * （超级数据组和admin用户，均按照小组类型获取权限）
     * @param int    $category_id 小组类型
     * @param int    $user_id 采购员id
     * @param string $type 类型（system-表示为定时任务跑数据，主要是为了获取指定小组类型下的所有采购员id）
     * @return array|array[]|bool
     */
    public function get_work_desk_authorization($category_id, $user_id, $type = '')
    {

        if (empty($category_id) or is_null($user_id)) {
            return [];
        }

        $user_role = get_user_role(); //获取登录用户角色
        //只有权限中心的admin角色的用户，才属于超级数据组
        if (in_array('admin', $user_role) or 'system' == $type) {
            //返回所属小组类型下的所有采购员
            return $this->_get_group_members($category_id, true, 'admin');
        }

        //查询用户是否有权限
        $power_data = $this->purchase_db->select("a.id,a.user_id,a.category_id,a.rank,a.handover_user_number,a.is_enable,a.is_del")
            ->select("b.user_code")
            ->from("{$this->table_name} a")
            ->join("{$this->table_user_info} b", 'a.user_id=b.user_id')
            ->where(['a.user_id' => $user_id, 'a.is_enable' => 1, 'a.is_del' => 0])
            ->get()->result_array();

        //数据权限配置中心没有配置该用户，返回空数组
        if (empty($power_data)) {
            return [];
        }
        //处理数据后，判断用户是否有指定‘小组类型’的数据权限
        foreach ($power_data as $key => $item) {
            if (3 != $item['category_id'] && $category_id != $item['category_id']) {
                unset($power_data[$key]);
            }
        }
        //没有权限返回空数组
        if (empty($power_data)) {
            return [];
        }
        //重排数据下标
        $power_data = array_values($power_data);
        //判断用户是否为‘超级数据组用户’
        $category_id_arr = array_unique(array_column($power_data, 'category_id'));
        if (in_array(3, $category_id_arr)) {
            //返回所属小组类型下的所有采购员
            return $this->_get_group_members($category_id, true, 'admin');
        } else {
            if (4 == $power_data[0]['rank']) { //副经理级别，返回所属小组类型下的所有采购员
                $authorization_data[] = $user_id;
                $res = $this->_get_group_members($category_id, true);
                if (!empty($res)) {
                    $authorization_data = array_unique(array_merge($authorization_data, $res));
                }
            } elseif (1 == $power_data[0]['rank']) { //组员
                $authorization_data[] = $user_id;
            } else { //主管，组长，按实际权限查询
                $authorization_data[] = $user_id;
                return $this->_get_group_members($power_data[0]['id']);
            }
            //判断用户是否为交接人
            $res = $this->is_handover_user($user_id);
            if (!empty($res)) {
                $authorization_data = array_unique(array_merge($authorization_data, $res));
            }
            return $authorization_data;
        }
    }

    /**
     * function:获取小组成员信息
     * @param $groupIds  array  小组ID
     * @author:luxu
     * @time:2020年9月8号
     **/
    public function getGroupPersonData($groupIds = null)
    {

        $dropdown_data = [];
        $result = $this->purchase_db->select('a.user_id,a.user_name')
            ->from("$this->table_user_info a")
            ->join("$this->table_name b", 'b.user_id=a.user_id')
            ->join("$this->table_user_group_relation c", 'c.user_map_id=b.id')
            ->where_in('c.group_id', $groupIds)
            ->where(['b.is_enable' => 1, 'b.is_del' => 0])
            ->get()->result_array();

        if (!empty($result)) {
            foreach ($result as $key => $item) {
                $dropdown_data[] = [
                    'value' => $item['user_id'],
                    'label' => $item['user_name'],
                ];
            }
            return $dropdown_data;
        } else {
            return $dropdown_data;
        }
    }

    /**
     * function:获取小组成员信息
     * @param $groupIds  array  小组ID
     * @author:luxu
     * @time:2020年9月8号
     **/
    public function getGroupByData($groupIds = null)
    {

        $dropdown_data = [];
        $result = $this->purchase_db->select('a.user_id,a.user_name,b.category_id')
            ->from("$this->table_user_info a")
            ->join("$this->table_name b", 'b.user_id=a.user_id')
            ->join("$this->table_user_group_relation c", 'c.user_map_id=b.id')
            ->where_in('b.category_id', $groupIds)
            ->where(['b.is_enable' => 1, 'b.is_del' => 0])
            ->get()->result_array();
        if (!empty($result)) {
            foreach ($result as $key => $item) {
                $dropdown_data[] = [
                    'value' => $item['user_id'],
                    'label' => $item['user_name'],
                    'category_id' => $item['category_id'],
                ];
            }
            return $dropdown_data;
        } else {
            return $dropdown_data;
        }
    }

    /**
     * 查询用户所属的组别信息
     * @param null|array $buyerIds 采购员ID，为null则跳过查询条件
     * @param null|array $groupIds 组别ID，为null则跳过查询条件
     * @author Jolon
     * @return array
     */
    public function getBuyerGroupMessage($buyerIds = null, $groupIds = null)
    {
        // 都不能为空
        if ((!is_null($buyerIds) and empty($buyerIds)) or (!is_null($groupIds) and empty($groupIds))) {
            return [];
        }

        $result = $this->purchase_db->select('a.category_id,a.user_id,c.group_name')
            ->from("$this->table_name a")
            ->join("$this->table_user_group_relation b", 'a.id=b.user_map_id')
            ->join("$this->table_group c", 'b.group_id=c.id')
            ->where_in('a.user_id', $buyerIds)
            ->where_in('b.group_id', $groupIds)
            ->where(['a.is_enable' => 1, 'a.is_del' => 0])
            ->get()->result_array();
        return !empty($result) ? $result : [];
    }

    public function getNameGroupMessage($buyerIds)
    {

        if ($buyerIds) {
            $sql = 'SELECT a.category_id,a.user_id,c.group_name,info.user_name FROM pur_purchase_user_relation a
                JOIN pur_purchase_user_group_relation b ON a.id=b.user_map_id
                LEFT JOIN pur_purchase_group c ON b.group_id=c.id
                LEFT JOIN pur_purchase_user_info info ON a.user_id=info.user_id
                WHERE  a.is_enable =1 AND info.user_name IN (' . implode(",", $buyerIds) . ')';
            $result = $this->purchase_db->query($sql)->result_array();
        } else {
            $result = [];
        }
        return $result;
    }

    /**
     * 获取采购组别
     */
    public function getUserGroup()
    {
        $result['alias'] = $this->getGroupList([1, 2]);
        $groupByData = $this->getGroupByData([1, 2]);
        $result['overseas'] = [];
        foreach ($groupByData as $key => $value) {
            if ($value['category_id'] == 2) {
                $result['overseas'][$value['value']] = $value['label'];
            }
            if ($value['category_id'] == 1) {
                $result['domestic'][$value['value']] = $value['label'];
            }
        }
        return $result;
    }
/**
 * 依据组别查成员，目前默认是找货组
 *
 * @param array $groupIds
 * @return void
 */
    public function getUserByGroup(array $groupIds = [16])
    {
        $result = $this->purchase_db->select('a.user_id')
            ->from("{$this->table_name} a")
            ->join("{$this->table_user_group_relation} b", 'b.user_map_id=a.id')
            ->where_in('b.group_id', $groupIds)
            ->where(['a.is_enable' => 1, 'a.is_del' => 0])
            ->group_by('a.user_id')
            ->get()->result_array();
        if (!empty($result)) {
            return array_column($result, 'user_id');
        } else {
            return [];
        }
    }
}
