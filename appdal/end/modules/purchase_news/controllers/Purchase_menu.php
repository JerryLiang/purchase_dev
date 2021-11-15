<?php

/**
 * 消息菜单栏控制器
 * @time:2020/8/12
 * @author:Dean
 **/
class Purchase_menu extends MY_Controller{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_menu_model');
    }


    //获取所有栏位列表
    public function get_menu_list()
    {
        $type = $this->input->get_post('type');//类型，消息1公告2
        if (empty($type)) {
            $this->error_json('请传类型');

        }
        $result = $this->purchase_menu_model->get_menu_list($type);
        $this->success_json($result);



    }

    //新增或者编辑类目
    public function opr_menu()
    {


        $params = gp();
        if (empty($params['type'])) {
            $this->error_json('请传类型');

        }
        if (empty($params['menu_name'])) {
            $this->error_json('栏目名称不能为空');

        }
        if (mb_strlen($params['menu_name'],"utf-8")>10) {
            $this->error_json('栏目名称不能超过10个字');

        }

        $result = $this->purchase_menu_model->opr_menu($params);

        if (empty($result['code'])) {
            $this->error_json($result['msg']);
        } else {
            $this->success_json($result['msg']);

        }

    }

    //删除类目的数量
    public function get_menu_news_num()
    {
        $menu_id = $this->input->get_post('menu_id');
        if (empty($menu_id)) {
            $this->error_json('参数异常');

        }

        $menu_list = $this->purchase_menu_model->get_all_sec_menu_id($menu_id);//获取所有子目录
        $result = $this->purchase_menu_model->purchase_db->select('count(*) as num')
            ->from('news_list')
            ->where_in('menu_id',$menu_list)
            ->get()
            ->row_array();


        $this->success_json($result['num']);


    }

    //删除类目
    public function del_menu()
    {
        $menu_id = $this->input->get_post('menu_id');
        if (empty($menu_id)) {
            $this->error_json('参数异常');

        }
        $num = $this->input->get_post('num');

        $menu_list = $this->purchase_menu_model->get_all_sec_menu_id($menu_id);//获取所有子目录
        $result = $this->purchase_menu_model->del_menu($menu_list,$num);//获取所有子目录

        if (empty($result['code'])) {
            $this->error_json($result['msg']);
        } else {
            $this->success_json($result['msg']);

        }


    }


//保存目录排序
    public function save_menu_sort()
    {
        $sort_detail = $this->input->get_post('sort_detail');
        $max = 1000;//排序初始值最大
        $sort_detail = json_decode($sort_detail,true);
        if (!empty($sort_detail)) {
            foreach ($sort_detail as $detail) {
                $this->purchase_menu_model->purchase_db->where('id',$detail['id'])->update('news_menu',['sort'=>$max]);
                $max--;
                if (!empty($detail['sec_list'])) {
                    foreach ($detail['sec_list'] as $sec) {
                        $this->purchase_menu_model->purchase_db->where('id',$sec)->update('news_menu',['sort'=>$max]);
                        $max--;

                    }

                }

            }
            $this->success_json('保存成功');

        } else {
            $this->error_json('数据为空');

        }





    }
















}