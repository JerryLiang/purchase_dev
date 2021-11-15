<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Merchandiser_user_group extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/Merchandiser_group_model');
        $this->load->model('user/purchase_user_model');
    }

    /**
     * 仓库组列表显示
     * /user/user_group/group_list
     * @author Justin
     * @date 2020-06-15
     */
    public function group_list()
    {
        try {


            $data = $this->Merchandiser_group_model->get_group_list();

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
        if (empty($data['data']) or !is_array($data['data']) or !isset($data['update_time'])) {
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
        $result = $this->Merchandiser_group_model->group_edit_batch($data);
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
        $result = $this->Merchandiser_group_model->group_del($id);
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
        $buyer_list = [];//所有采购员
        $buyer_group_list = [];//小组带采购员 用于后面数据验证
        $user_data = $this->input->post_get('user_data'); //json格式
        if (empty($user_data) or !is_json($user_data)) $this->error_json('请求参数格式错误');

        //验证必填字段
        $user_data = json_decode($user_data, true);
        if (empty($user_data['data']) or !is_array($user_data['data']) ) {
            $this->error_json('请求参数不全');
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

            if (!isset($item['is_leave']) or !in_array($item['is_leave'], [0, 1])) {
                $this->error_json('是否离职');
            }

            if (empty($item['rank'])) {
                $this->error_json('请选择职级');
            } elseif (!in_array($item['rank'], [1, 2, 3, 4])) {
                $this->error_json('职级参数错误');
            }

            if (empty($item['buyer_id'])) {
                $this->error_json('请选择绑定采购员');
            }

            if (empty($item['group_id'])) {
                $this->error_json('请选择分组');
            }
            $buyer_id_list = explode(',',$item['buyer_id']);

            foreach ($buyer_id_list as $buyer_id) {
                $buyer_list[] = $buyer_id;
                $buyer_group_list[] = $item['group_id'].'-'.$buyer_id;

            }
        }

        //保存人员信息
        $result = $this->Merchandiser_group_model->user_add_batch( $user_data,$buyer_list,$buyer_group_list);
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
        $data = $this->Merchandiser_group_model->get_user_edit($id);
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
        $params['is_leave'] = $this->input->post_get('is_leave'); //是否离职(0-否,1-是)
        $params['rank'] = $this->input->post_get('rank'); //职级（1-组员、2-组长、3-主管、4-副经理）
        $params['account_number'] = $this->input->post_get('account_number'); //1688账号
        $params['phone_number'] = $this->input->post_get('phone_number'); //联系方式
        $params['group_id'] = $this->input->post_get('group_id'); //所属小组
        $params['buyer_id'] = $this->input->post_get('buyer_id');//绑定的采购员
        $params['buyer_name'] = $this->input->post_get('buyer_name');//绑定的采购员姓名
        $params['user_id'] = $this->input->post_get('user_id');//绑定的采购员姓名



        if (empty($id)) {
            $this->error_json('参数id缺失');
        }
        //超级数据组添加用户时不用判断以下数据

            if (!in_array($params['rank'], [1, 2, 3, 4])) {
                $this->error_json('请选择职级');
            }
            if (is_null($params['is_leave']) or !in_array($params['is_leave'], [0, 1])) {
                $this->error_json('请选择是否离职');
            }

            if(empty($params['group_id'])){
                $this->error_json('请选择所属小组');
            }




        if (empty($params['phone_number'])) {
            $this->error_json('请输入联系方式');
        }
        //验证手机号格式是否正确
        if (!preg_match("/^1[3456789]\d{9}$/", $params['phone_number'])) {
            $this->error_json('手机号码格式不正确');
        }
        $temp = $this->Merchandiser_group_model->get_user_edit_save($params, $id);
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
        $temp = $this->Merchandiser_group_model->user_del($id);
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
            'group_id' => $this->input->get_post('group_id'), // 小组id
            'rank' => $this->input->get_post('rank'), // 职级
            'is_leave' => $this->input->get_post('is_leave'),//是否离职
            'enable_status' => $this->input->get_post('enable_status'),//启用禁用状态（0-禁用，1-启用）,
            'user_name' => $this->input->get_post('user_name'),//启用禁用状态（0-禁用，1-启用）
            'user_number' =>  $this->input->get_post('user_number')

        ];


        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) {
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data = $this->Merchandiser_group_model->get_user_list($params, $offsets, $limit, $page);
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
        $enable_status = $this->input->get_post('enable_status');//启用禁用状态（1-启用，2-禁用）

        if (empty($id)) {
            $this->error_json('参数id缺失');
        }
        if (!in_array($enable_status,[1,2])) {
            $this->error_json('启用禁用状态参数错误');
        }
        $temp = $this->Merchandiser_group_model->change_enable_status($id, $enable_status);
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