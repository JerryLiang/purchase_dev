<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Merchandiser_group_model extends Purchase_model {

    protected $table_group_name = 'merchandiser_group';
    protected $table_user_name  = 'merchandiser_user';
    protected $table_user_info  = 'purchase_user_info';





    public function __construct(){
        parent::__construct();
        $this->load->model('product/Product_line_model');
        $this->load->model('supplier/Supplier_buyer_model', 'buyerModel');
        $this->load->helper('user');
        $this->load->helper('status_order');
    }

    /**
     * 编辑小组（预览)
     * @return array
     * @author Justin 2020-06-15
     */
    public function get_group_list()
    {
        $query_builder = $this->purchase_db;
        $query_builder->select('id,group_name,update_time');
        $query_builder->from($this->table_group_name);
        $query_builder->where('is_del', 2);
        $results = $query_builder->order_by('create_time', 'DESC')->get()->result_array();
        $down_buyer = $this->buyerModel->get_buyers();


        return [
            'values' => $results,
            'down_buyer' =>$down_buyer
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
            $insert_data = array();//新增的数据
            $update_data = array();//更新的数据

            $update_time = $data['update_time'];//提交数据的更新时间
            $time = date('Y-m-d H:i:s');
            //获取数据的更新时间，用于判断当前提交的数据，是否在最新数据基础上修改
            if (!empty($update_time)) {
                $time_data = $query->select('update_time')->where(['is_del'=>2])->get($this->table_group_name)->row_array();
                if (!empty($time_data) && ($time_data['update_time'] != $update_time)) {
                    throw new Exception('数据已发生变化，请关闭页面后重新编辑');
                }
            }
            //组织数据
            foreach ($data['data'] as $key => $item) {
                $tmp['id'] = $item['id'];
                $tmp['group_name'] = $item['group_name'];
                $tmp['update_time'] = $time;
                $tmp['update_user_name'] = getActiveUserName();
                $tmp['is_del'] = 2;
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
                $query->update($this->table_group_name, $item, ['id' => $id]);
            }

            //新增数据
            if (!empty($insert_data)) {
                //判断小组名称是否存在
                $group_info = $this->purchase_db->select('id,group_name,is_del')
                    ->from($this->table_group_name)
                    ->where_in('group_name', array_column($insert_data, 'group_name'))
                    ->get()->result_array();
                foreach ($group_info as $item) {
                    if (1 == $item['is_del']) {
                        //更新数据
                        $group_info_tmp [$item['group_name']] = $item;
                    } else {
                        throw new Exception('小组名称[' . $item['group_name'] . ']已存在');
                    }
                }

                foreach ($insert_data as $item) {
                    unset($item['id']);
                    if (!empty($group_info_tmp[$item['group_name']])) {
                        //更新数据
                        $query->update($this->table_group_name, $item, ['id' => $group_info_tmp[$item['group_name']]['id']]);
                    } else {
                        //插入数据
                        $query->insert($this->table_group_name, $item);
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
                ->where([ 'id'=>$id,'is_del' => 2])
                ->get($this->table_group_name)->num_rows();
            if(!$group_num_rows){
                throw new Exception('小组不存在或已被删除');
            }
            //判断小组是否存在“是否离职=是，且交接人=空”，或者“是否离职=否”的人员数据
            $num_rows = $query->select('1')->from("{$this->table_user_name} ")
                ->where('group_id', $id)
                ->get()->num_rows();
            if($num_rows){
                throw new Exception('小组存在未删除跟单员，不允许删除');
            }

            $time = date('Y-m-d H:i:s');
            //更新数据
            $update_data = [
                'update_time' => $time,
                'update_user_name' => getActiveUserName(),
                'is_del' => 1
            ];
            $query->update($this->table_group_name, $update_data, ['id' => $id]);
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
        $query->select('b.user_id,b.user_code,b.user_name,b.phone_number,b.is_leave,b.account_number,a.id,a.rank,a.create_user_name,a.create_time,d.group_name,a.buyer_name,a.is_enable');
        $query->from("{$this->table_user_name} AS a");
        $query->join("{$this->table_user_info} AS b", 'b.user_id=a.user_id');
        $query->join("{$this->table_group_name} AS d", 'd.id=a.group_id','LEFT');

        $query->where('a.is_del', 2);


        //按工号查询
        if (!empty($params['user_number'])) {
            $query->where('b.user_code', $params['user_number']);
        }
        //按小组查询
        if (!empty($params['group_id'])) {
            $query->where('a.group_id', $params['group_id'], false);
        }
        //按职级查询
        if (!empty($params['rank'])) {
            if (is_array($params['rank'])) {
                $query->where_in('a.rank', $params['rank'], false);
            } else {
                $query->where('a.rank', $params['rank'], false);
            }
        }
        //按是否离职查询
        if (isset($params['is_leave']) && is_numeric($params['is_leave'])) {
            $query->where('b.is_leave', $params['is_leave'], false);
        }

        //按是否启用状态查询
        if (is_numeric($params['enable_status'])) {
            $query->where('a.is_enable', $params['enable_status'], false);
        }
        //分组
        $query->group_by('a.user_id');

        $count_qb = clone $query;
        //统计总数要加上前面筛选的条件
        $total_count = $count_qb->get()->num_rows();

        //排序
        $this->purchase_db->order_by('a.update_time', 'DESC');


        //查询数据
        $result = $query->limit($limit, $offsets)->get()->result_array();
        //转换数据


        return [
            'values' => $result,
            'page_data' => [
                'total' => $total_count,
                'offset' => $page,
                'limit' => $limit,
                'pages' => ceil($total_count / $limit)
            ],
            'drop_down_box' => [
                'user_list' => $this->get_user_list_down(),//用户下拉列表
                'user_rank' => getUserRank(),//职级下拉列表
                'user_leave_status' => getUserLeaveStatus(),//是否离职下拉列表
                'group_list' => $this->get_group_list(),//小组下拉列表
                'enable_status' => ['2' => '否', '1' => '是'],
            ]
        ];
    }

    /**
     * 批量添加人员
     * @param array $user_data
     * @param array $buyer_list 所有采购员，提供校验用
     * @return array
     */
    public function user_add_batch( $user_data,$buyer_list,$buyer_group_list)
    {
        $bind_warehouse_set = ['塘厦组'=>'SZ_AA','虎门组'=>'HM_AA','慈溪组'=>'CX'];//仓库配置
        $query = $this->purchase_db;
        //开启事务
        $query->trans_begin();
        try {
            $buyer_list_info = array_count_values($buyer_list);
            $buyer_group_list_info = array_count_values($buyer_group_list);
            $group_info = $this->get_group_list();


            $group_name_list = array_column($group_info['values'],'group_name','id');
            $buyer_name_list = array_column($group_info['down_buyer']['list'],'buyer_name','buyer_id');




            if (!empty($buyer_group_list_info)&&!empty($buyer_list_info)) {
                foreach ($buyer_group_list_info as $group_key =>$group_info) {
                    if ($group_info>=2) {
                        $group_key_arr = explode('-',$group_key);
                        $group_name = $group_name_list[$group_key_arr[0]]??'';
                        $buyer_name = $buyer_name_list[$group_key_arr[1]]??'';
                        throw new Exception('采购员'.$buyer_name.'已经绑定小组'.$group_name.'请重新确认');
                    }

                }

                foreach ($buyer_list_info as $buyer_key=>$buyer_info) {
                    if ($buyer_info>2) {
                        $buyer_name = $buyer_name_list[$buyer_key]??'';
                        throw new Exception('采购员'.$buyer_name.'最多绑定2个跟单员');


                    } elseif ($buyer_info == 1) {
                        //查询是否已经绑定过跟单员
                        $user_info = $this->purchase_db->select('group_id,user_id')->from($this->table_user_name)->where("FIND_IN_SET({$buyer_key},buyer_id)")->get()->result_array();
                        $user_info = empty($user_info)?[]:$user_info;
                        $num = count($user_info);

                        if ($num>=2) {
                            $buyer_name = $buyer_name_list[$buyer_key]??'';
                            throw new Exception('采购员'.$buyer_name.'最多绑定2个跟单员');


                        } elseif($num == 1){
                            //查出他的分组
                            foreach ($buyer_group_list_info as $group_key =>$group_info ) {
                                if (strpos('$buyer_key',$group_key)!==false) {
                                    $str_list = explode('-',$group_key);
                                    $group_id = $str_list[0];

                                    if ($group_id == $user_info[0]['group_id']  ) {
                                        $buyer_name = $buyer_name_list[$buyer_key]??'';
                                        $group_name = $group_name_list[$group_id]??'';


                                        throw new Exception('采购员'.$buyer_name.'绑定的'.$group_name.'同个小组的跟单员绑定的采购员不能重复');


                                    }

                                }


                            }

                        }

                    } elseif($buyer_info == 2){
                        //查询是否已经绑定过跟单员
                        $user_info = $this->purchase_db->select('group_id,user_id')->from($this->table_user_name)->where("FIND_IN_SET({$buyer_key},buyer_id)")->get()->result_array();
                        $user_info = empty($user_info)?[]:$user_info;
                        $num = count($user_info);
                        if ($num>0) {
                            $buyer_name = $buyer_name_list[$buyer_key]??'';
                            throw new Exception('采购员'.$buyer_name.'最多绑定2个跟单员');

                        }


                    }

                }


            }

            $time = date('Y-m-d H:i:s');
            $create_user = getActiveUserName();
            $user_insert_data = [];//新增用户表数据

            //验证数据
            $this->load->model('user/Purchase_user_model');
            foreach ($user_data['data'] as $key => $item) {
                //判断工号是否存在
                $user_info = $this->Purchase_user_model->get_user_info_by_staff_code($item['user_number']);
                if (!$user_info) {
                    throw new Exception('用户工号[' . $item['user_number'] . ']不存在');
                }
                $num_rows = $query->where(['user_id' => $user_info['user_id'], 'is_del' => 2])->get($this->table_user_name)->num_rows();
                if ($num_rows) {
                    throw new Exception('用户[' . $user_info['user_name'] . ']已经存在，不允许再次添加');
                }



                //新增权限配置用户主表数据
                $user_insert_data['master'][$key] = [
                    'user_id' => $user_info['user_id'],
                    'user_code' => $item['user_number'],
                    'user_name' => $item['user_name'],
                    'phone_number' => $item['phone_number'],
                    'is_leave' => isset($item['is_leave']) ? $item['is_leave'] : 0,
                    'account_number' => isset($item['account_number']) ? $item['account_number'] :'',
                ];
                //新增权限配置用户关系表数据
                $user_insert_data['map'][$key] = [
                    'user_id' => $user_info['user_id'],
                    'rank' => isset($item['rank']) ? $item['rank'] : 0,
                    'create_user_name' => $create_user,
                    'create_time' => $time,
                    'update_user_name' => $create_user,
                    'update_time' => $time,
                    'group_id' => $item['group_id'],
                    'buyer_id' => $item['buyer_id'],
                    'buyer_name' => $item['buyer_name'],
                ];
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
                    $item['warehouse_code'] =  $bind_warehouse_set[$group_name_list[$item['group_id']]]??'';    //绑定的仓库
                    $query->insert($this->table_user_name, $item);
                    $user_map_id = $query->insert_id();
                    if (empty($user_map_id)) {
                        throw new Exception('新增权限配置用户关系表失败');
                    }

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
    public function get_user_edit($id)
    {
        $user_info = $this->purchase_db->select('b.user_id,b.user_code,b.user_name,b.phone_number,b.is_leave,b.account_number,a.id,a.rank,a.group_id,a.buyer_id,a.buyer_name')
            ->from("{$this->table_user_name} a")
            ->join("{$this->table_user_info} b", 'b.user_id=a.user_id')
            ->where('a.id', $id, false)
            ->where('a.is_enable', 1, false)
            ->where('a.is_del', 2, false)
            ->group_by('a.id')
            ->get()->row_array();

        if (empty($user_info)) {
            return ['flag' => false, 'msg' => '未获取到该人员的数据，或已被禁用或者删除'];
        }


        //转换采购员数据形式
        if(!empty($user_info['buyer_id'])){
            $user_info['buyer_id'] = explode(',', $user_info['buyer_id']);
        }else{
            $user_info['buyer_id'] =[];
        }



        $data = [
            'value' => $user_info,
            'drop_down_box' => [
                'user_rank' => getUserRank(),//职级下拉列表
                'user_leave_status' => getUserLeaveStatus(),//是否离职下拉列表
                'group_list' => $this->get_group_list(),//小组下拉列表
            ]
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
        $bind_warehouse_set = ['塘厦组'=>'SZ_AA','虎门组'=>'HM_AA','慈溪组'=>'CX'];//仓库配置

        $query = $this->purchase_db;
        //开启事务
        $query->trans_begin();
        try {

                //检查用户是否被禁用
                $res = $this->get_user_edit($id);
                if (!$res['flag']) {
                    throw new Exception($res['msg']);
                }

                if (empty($params['buyer_id'])) {
                    throw new Exception('请绑定采购员');

                }

            $group_info = $this->get_group_list();
            $group_name_list = array_column($group_info['values'],'group_name','id');
            $buyer_name_list = array_column($group_info['down_buyer']['list'],'buyer_name','buyer_id');

            $buyer_list = explode(',',$params['buyer_id']);

                foreach ($buyer_list as $buyer_id) {
                    $bind_user_info = $this->purchase_db->select('group_id,user_id')->from($this->table_user_name)->where("FIND_IN_SET({$buyer_id},buyer_id) and user_id!={$params['user_id']}")->get()->result_array();
                    $bind_user_info = empty($bind_user_info)?[]:$bind_user_info;
                    $num = count($bind_user_info);
                    if ($num == 2) {
                        throw new Exception('采购员'.($buyer_name_list[$buyer_id]).'最多绑定2个跟单员');

                    } elseif ($num == 1) {
                        if ($bind_user_info[0]['group_id'] == $params['group_id']) {
                            throw new Exception('同个小组'.($group_name_list[$params['group_id']]).'绑定的采购员:'.($buyer_name_list[$buyer_id]).'重复了');


                        }

                    }


                }

                //保存用户关系表
                $set = [
                    'rank' => $params['rank'],
                    'update_user_name' => getActiveUserName(),
                    'update_time' => date('Y-m-d H:i:s'),
                    'buyer_id' =>  $params['buyer_id'],
                    'buyer_name'=> $params['buyer_name'],
                    'group_id'  => $params['group_id'],
                    'warehouse_code'=>$bind_warehouse_set[$group_name_list[$params['group_id']]]??''
                ];
                $this->purchase_db->where('id', $id)->update($this->table_user_name, $set);

                //用户主表数据
                $set_master = [
                    'phone_number' => $params['phone_number'],
                    'is_leave' => $params['is_leave'],
                    'account_number' => $params['account_number']
                ];

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
     * 删除用户
     * @param $id
     * @return array
     */
    public function user_del($id)
    {
        $this->purchase_db->trans_begin();
        try {
            $num_rows = $this->purchase_db->select('*')->where(['id' => $id, 'is_del' => 2])->get($this->table_user_name)->num_rows();
            if (!$num_rows) {
                throw new Exception('数据不存在或已被删除');
            }
            //删除用户
            $this->purchase_db->where('id',$id)->update($this->table_user_name, ['is_del' => 1]);

            if ($this->purchase_db->trans_status() === FALSE) {
                throw new Exception('删除失败');
            } else {
                $this->purchase_db->trans_commit();
                $return = ['msg' => '删除成功', 'flag' => TRUE];
            }
        } catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            $return = ['msg' => $exc->getMessage(), 'flag' => FALSE];
        }
        return $return;
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
            $record = $this->purchase_db->select('is_enable')
                ->where(['id' => $id])
                ->get($this->table_user_name)->row_array();
            if (empty($record)) {
                throw new Exception('数据不存在');
            }elseif($record['is_enable'] == $enable_status){
                if (2 == $enable_status) {
                    throw new Exception('用户已经是禁用状态，请刷新再重试');
                }else{
                    throw new Exception('用户已经是启用状态，请刷新再重试');
                }
            }


            //保存数据
            $set = [
                'is_enable' => $enable_status,
                'update_user_name' => getActiveUserName(),
                'update_time' => date('Y-m-d H:i:s')
            ];
            $this->purchase_db
                ->where('id', $id)
                ->update($this->table_user_name, $set);

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
     * 获取已绑定的用户数据
     * @param $category_id
     * @return array|bool
     */
    public function get_user_list_down()
    {
        $result = $this->purchase_db->select('a.user_code,a.user_name')
            ->from("{$this->table_user_info} a")
            ->join("{$this->table_user_name} b", 'b.user_id=a.user_id')
            ->group_by('a.user_id')
            ->get()->result_array();
        if (!empty($result)) {
            return array_column($result, 'user_name', 'user_code');
        } else {
            return [];
        }
    }

    //根据仓库名获取绑定跟单员信息
    public function get_bind_merchandiser_info($group_name,$buyer_id)
    {
        $result = $this->purchase_db->select('a.user_name,a.user_id')
            ->from("{$this->table_user_info} a")
            ->join("{$this->table_user_name} b", 'b.user_id=a.user_id')
            ->join("{$this->table_group_name} c", 'c.id=b.group_id')
            ->where("FIND_IN_SET({$buyer_id},b.buyer_id) !=",0)
            ->where('c.group_name',$group_name)
            ->get()
            ->row_array();



        return $result;



    }

    //根据跟单员获取跟单员绑定数据
    public function get_bind_info_by_user_id($user_id)
    {
        $result = $this->purchase_db->select('buyer_id,warehouse_code')->from($this->table_user_name)->where('user_id',$user_id)->get()->row_array();
        return !empty($result)?$result:[];

    }





}
