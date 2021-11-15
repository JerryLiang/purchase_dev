<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class User_group extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/User_group_model');
        $this->load->model('user/purchase_user_model');
    }

    public function get_company_dep()
    {
        $result = $this->purchase_user_model->getCompanyDep();
        if( !empty($result) ) {
            $this->success_json($result);
        }else{
            $this->error_json('未获取到用户数据');
        }
    }

    /**
     * 采购组列表显示
     * /user/user_group/group_list
     * @author Justin
     * @date 2020-06-15
     */
    public function group_list()
    {
        try {
            $category_id = $this->input->get_post('category_id');
            if (!in_array($category_id, [1, 2, 3])) {
                throw new Exception('参数category_id错误');
            }
            $data = $this->User_group_model->get_group_list($category_id);
            $this->success_json($data);
        } catch (Exception $exc) {
            $this->error_json($exc->getMessage());
        }
    }

    /**
     * 组的编辑
     * /user/user_group/group_edit_batch
     * @author Justin
     * @date 2020-06-15
     */
    public function group_edit_batch()
    {
        $data = $this->input->post_get('group_data'); //json格式
        if (empty($data) or !is_json($data)) $this->error_json('请求参数格式错误');

        //验证必填字段
        $data = json_decode($data, true);
        if (empty($data['data']) or !is_array($data['data']) or !isset($data['update_time']) or !isset($data['category_id'])) {
            $this->error_json('请求参数不全');
        }
        if(!empty(array_filter(array_column($data['data'],'id'))) && empty($data['update_time'])){
            $this->error_json('请求参数不全002');
        }
        //清除数组所有字符串元素两边的空格
        $data = TrimArray($data);

        //判断小组名称是否存在重复
        if (count(array_unique(array_column($data['data'], 'group_name'))) != count($data['data'])) {
            $this->error_json('小组名称不能重复');
        }
        //编辑保存小组信息
        $result = $this->User_group_model->group_edit_batch($data);
        if ($result['flag']) {
            $this->success_json([], null, '修改成功');
        } else {
            $this->error_json($result['msg']);
        }
    }

    /**
     * 删除小组
     * /user/user_group/group_del
     * @author Justin
     * @date 2020-06-15
     */
    public function group_del()
    {
        $id = $this->input->post_get('id'); //小组id
        if (empty($id)) {
            $this->error_json('请求参数id缺失');
        }
        //删除小组
        $result = $this->User_group_model->group_del($id);
        if ($result['flag']) {
            $this->success_json([], null, '删除成功');
        } else {
            $this->error_json($result['msg']);
        }
    }

    /**
     * 输入user_code获取用户显示下拉框
     * /user/user_group/user_info
     * @author harvin
     * @date 2019-3-19
     */
    public function user_info()
    {
        $user_code = $this->input->get_post('user_code');
        //调用采购接口 获取采购员名
        $this->load->model('user/Purchase_user_model');
        $resurs = $this->Purchase_user_model->get_user_info_by_staff_code($user_code);
        if (isset($resurs['user_name']) && $resurs['user_name']) {
            $this->success_json(['user_name' => $resurs['user_name']]);
        } else {
            $this->error_json('用户工号不存在');
        }
    }

    /**
     * 组的编辑
     * /user/user_group/user_add_batch
     * @author Justin
     * @date 2020-06-15
     */
    public function user_add_batch()
    {
        $user_data = $this->input->post_get('user_data'); //json格式
        if (empty($user_data) or !is_json($user_data)) $this->error_json('请求参数格式错误');

        //验证必填字段
        $user_data = json_decode($user_data, true);
        if (empty($user_data['data']) or !is_array($user_data['data']) or !isset($user_data['category_id'])) {
            $this->error_json('请求参数不全');
        }
        if (!in_array($user_data['category_id'], [1, 2, 3])) {
            $this->error_json('类型参数错误');
        }

        //判断小组名称是否存在重复
        if (count(array_unique(array_column($user_data['data'], 'user_number'))) != count($user_data['data'])) {
            $this->error_json('提交的用户数据重复');
        }

        foreach ($user_data['data'] as $item) {
            //判断用户工号
            if (empty($item['user_number'])) {
                $this->error_json('请输入用户工号');
            }
            if (empty($item['user_name'])) {
                $this->error_json('请输入用户名');
            }
            //判断联系人电话
            if (empty($item['phone_number'])) {
                $this->error_json('请输入联系方式');
            }
            //验证手机号格式是否正确
            if (!preg_match("/^1[3456789]\d{9}$/", $item['phone_number'])) {
                $this->error_json('手机号码格式不正确');
            }
            //超级数据组添加用户时不用判断以下数据
            if(3 !=$user_data['category_id']){
                //判断是否选择职级
                if (empty($item['rank'])) {
                    $this->error_json('请选择职级');
                } elseif (!in_array($item['rank'], [1, 2, 3, 4])) {
                    $this->error_json('职级参数错误');
                }
                //判断是否选择‘是否离职’
                if (!isset($item['is_leave']) or !in_array($item['is_leave'], [0, 1])) {
                    $this->error_json('是否离职');
                }
                //副经理具有当前类型组下，所有组的权限，不需要选择小组
                if(4 != $item['rank']){
                    //判断是否选择小组
                    if (!isset($item['group_ids']) or count(array_filter($item['group_ids'])) == 0) {
                        $this->error_json('请选择所属小组');
                    }
                    //判断组员所属小组是否多选
                    if (1 == $item['rank'] && count(array_filter($item['group_ids'])) > 1) {
                        $this->error_json('组员[' . $item['user_name'] . ']不允许归属多个小组');
                    }
                }

                //判断是否选择1688账号
                if (empty($item['account_number'])) {
                    $this->error_json('请选择1688账号');
                }
            }
        }

        //保存人员信息
        $result = $this->User_group_model->user_add_batch($user_data['category_id'], $user_data);
        if ($result['flag']) {
            $this->success_json([], null, '添加成功');
        } else {
            $this->error_json($result['msg']);
        }
    }

    /**
     * 编辑用户显示
     * /user/user_group/user_edit_view
     * @author Justin
     * @date 2020-06-18
     */
    public function user_edit_view()
    {
            $id = $this->input->post_get('id'); //id
            if (empty($id)) {
                $this->error_json('参数id缺失');
            }
            $data = $this->User_group_model->get_user_edit($id);
            if($data['flag']){
                $this->success_json($data['data']);
            }else{
                $this->error_json($data['msg']);
            }
    }

    /**
     * 保存用户编辑
     * /user/user_group/user_edit_save
     * @author Justin
     * @date 2020-06-18
     */
    public function user_edit_save()
    {

        $id = $this->input->post_get('id'); //id
        $params['category_id'] = $this->input->post_get('category_id'); //小组类型
        $params['is_leave'] = $this->input->post_get('is_leave'); //是否离职(0-否,1-是)
        $params['rank'] = $this->input->post_get('rank'); //职级（1-组员、2-组长、3-主管、4-副经理）
        $params['account_number'] = $this->input->post_get('account_number'); //1688账号
        $params['phone_number'] = $this->input->post_get('phone_number'); //联系方式
        $params['group_ids'] = $this->input->post_get('group_ids'); //所属小组

        if (empty($id)) {
            $this->error_json('参数id缺失');
        }
        //超级数据组添加用户时不用判断以下数据
        if(3 !=$params['category_id']){
            if (!in_array($params['rank'], [1, 2, 3, 4])) {
                $this->error_json('请选择职级');
            }
            if (is_null($params['is_leave']) or !in_array($params['is_leave'], [0, 1])) {
                $this->error_json('请选择是否离职');
            }
            //副经理具有当前类型组下，所有组的权限，不需要选择小组
            if(4 != $params['rank']){
                //判断是否选择小组
                if(empty($params['group_ids']) OR !is_array($params['group_ids'])){
                    $this->error_json('请选择所属小组');
                }else{
                    $params['group_ids'] = array_filter($params['group_ids']);
                }
                //判断组员所属小组是否多选
                if (1 == $params['rank'] && count($params['group_ids']) > 1) {
                    $this->error_json('职级为“组员”时不允许归属多个小组');
                }
            }

            if (empty($params['account_number'])) {
                $this->error_json('请选择1688账号');
            }
        }

        if (empty($params['phone_number'])) {
            $this->error_json('请输入联系方式');
        }
        //验证手机号格式是否正确
        if (!preg_match("/^1[3456789]\d{9}$/", $params['phone_number'])) {
            $this->error_json('手机号码格式不正确');
        }
        $temp = $this->User_group_model->get_user_edit_save($params, $id);
        if ($temp['flag']) {
            $this->success_json([], null, '编辑成功');
        } else {
            $this->error_json($temp['msg']);
        }
    }

    /**
     * 删除用户(超级数据组用户)
     * /user/user_group/user_del
     * @author harvin
     * @date 2019-3-20
     */
    public function user_del()
    {
        $id = $this->input->post_get('id'); //id
        if (empty($id)) {
            $this->error_json('参数id错误');
        }
        $temp = $this->User_group_model->user_del($id);
        if ($temp['bool']) {
            $this->success_json([], null, $temp['msg']);
        } else {
            $this->error_json($temp['msg']);
        }
    }


    /**
     * 交款人
     * /user/user_group/handover_person
     * @author Justin
     * @date 2020-06-18
     */
    public function handover_person()
    {
        $id = $this->input->get_post('id');//离职人员记录ID

        $handover_user_number = $this->input->get_post('handover_user_number');//交接人工号
        if (empty($handover_user_number)) {
            $this->error_json('请选择交接人');
        }
        if (empty($id)) {
            $this->error_json('参数id缺失');
        }

        $temp = $this->User_group_model->get_handover_person($id, $handover_user_number);
        if ($temp['flag']) {
            $this->success_json([], null, '添加成功');
        } else {
            $this->error_json($temp['msg']);
        }
    }

    /**
     * 获取人员列表数据
     * /user/user_group/get_user_list
     */
    public function get_user_list()
    {
        $params = [
            'category_id' => $this->input->get_post('category_id'), // 类型
            'user_number' => $this->input->get_post('user_number'), // 工号
            'group_id' => $this->input->get_post('group_id'), // 小组id
            'rank' => $this->input->get_post('rank'), // 职级
            'is_leave' => $this->input->get_post('is_leave'),//是否离职
            'handover_user_number' => $this->input->get_post('handover_user_number'),//交接人
            'have_handover_user' => $this->input->get_post('have_handover_user'),//交接人是否为空（1-为空，2-非空）
            'enable_status' => $this->input->get_post('enable_status')//启用禁用状态（0-禁用，1-启用）
        ];

        if (empty($params['category_id'])) {
            $this->error_json('缺少类型参数');
        }

        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data = $this->User_group_model->get_user_list($params, $offsets, $limit, $page);
        $this->success_json($data);
    }

    /**
     * 获取1688账号下拉数据
     * /user/user_group/get_1688_account
     */
    public function get_1688_account()
    {
        $data = $this->User_group_model->get_1688_account();
        $this->success_json($data);
    }

    /**
     * 用户的启用禁用
     * /user/user_group/change_enable_status
     * @author Justin
     * @date 2020-06-18
     */
    public function change_enable_status(){
        $id = $this->input->get_post('id');//记录ID
        $enable_status = $this->input->get_post('enable_status');//启用禁用状态（0-禁用，1-启用）

        if (empty($id)) {
            $this->error_json('参数id缺失');
        }
        if (!in_array($enable_status,[0,1])) {
            $this->error_json('启用禁用状态参数错误');
        }
        $temp = $this->User_group_model->change_enable_status($id, $enable_status);
        if ($temp['flag']) {
            $this->success_json([], null, '操作成功');
        } else {
            $this->error_json($temp['msg']);
        }
    }

    /**
     * 获取采购系统组别
     * @param GET
     * @author:luxu
     * @time:2020/9/8 11 19
     **/
    public function getGrupData(){
        $result['alias'] = $this->User_group_model->getGroupList([1,2]);
        $groupByData = $this->User_group_model->getGroupByData([1,2]);

        $result['overseas'] = [];

        foreach( $groupByData as $key=>$value){

            if( $value['category_id'] == 2){

                $result['overseas'][$value['value']] = $value['label'];
            }

            if( $value['category_id'] == 1){

                $result['domestic'][$value['value']] = $value['label'];
            }
        }


        $this->success_json($result, null, '操作成功');
    }

}