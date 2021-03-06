<?php

/**
 * 所有消息
 * @time:2020/8/12
 * @author:Dean
 **/
class Purchase_news extends MY_Controller{


    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_news_model');


    }



    //新增或者编辑消息
    public function opr_news()
    {
        $params = gp();
        if (empty($params['type'])) {
            $this->error_json('请传类型');

        }
        if (empty($params['news_title'])) {
            $this->error_json('文章名称不能为空');

        }
        if (mb_strlen($params['news_title'],"utf-8")>50) {
            $this->error_json('标题名称不能超过50个字');

        }

        if (empty($params['content'])) {
            $this->error_json('文章内容不能为空');

        }
        if (empty($params['status'])) {
            $this->error_json('保存方式不能为空');

        }

        if (empty($params['menu_id'])) {
            $this->error_json('类目不能为空');

        }

        $result = $this->purchase_news_model->opr_news($params);

        if (empty($result['code'])) {
            $this->error_json($result['msg']);
        } else {
            $this->success_json($result['msg']);

        }

    }


    //展示消息

    public function show_news()
    {
        $params = gp();
        if (empty($params['id'])) {
            $this->error_json('id为空');

        }
       $result = $this->purchase_news_model->show_news($params);

        if (empty($result['code'])) {
            $this->error_json($result['msg']);
        } else {
            $this->success_json($result['data']);

        }



    }



    //置顶和取消置顶
    public function set_top()
    {
        $id = $this->input->get_post('id');
        $is_top = $this->input->get_post('is_top');
        $type = $this->input->get_post('type');


        if (empty($id)||empty($is_top)||empty($type)) {
            $this->error_json('参数异常');

        }
        $result =$this->purchase_news_model->set_top($id,$is_top,$type);
        if (empty($result['code'])) {
            $this->error_json($result['msg']);
        } else {
            $this->success_json($result['msg']);

        }


    }


    //点赞
    public function thumb_up()
    {
        $id = $this->input->get_post('id');

        if (empty($id)) {
            $this->error_json('参数异常');

        }
        try{
            $is_up = $this->purchase_news_model->purchase_db->select('*')->from('news_thumb_up')->where(['user_id'=>getActiveUserId(),'news_id'=>$id])->get()->row_array();//用户是否点进来看过

            if (empty($is_up)) {
                $flag = $this->purchase_news_model->purchase_db->insert('news_thumb_up',['user_id'=>getActiveUserId(),'news_id'=>$id]);
                if (empty($flag)) {
                    throw new Exception('点赞失败');

                }

            }
            $this->success_json('点赞成功');


        }catch (Exception $e){
            $this->error_json($e->getMessage());
        }


    }


    //获取用户消息列表(用户看到的)
    public function get_show_news()
    {
        //记录用户进入的时间，后续又有新增消息，参考这个
        //$this->purchase_news_model->remark_user_read_time();

        $params=[

            'type' => $this->input->get_post('type'),// 公告or操作手册
            'news_title' =>$this->input->get_post('news_title'),
            'update_time_start' =>$this->input->get_post('update_time_start'),
            'update_time_end' =>$this->input->get_post('update_time_end'),
            'menu_id'=>$this->input->get_post('menu_id'),


        ];

        $page_data=$this->format_page_data();
        $result=$this->purchase_news_model->get_show_news($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);
    }

    //获取用户消息列表
    public function get_edit_news()
    {


        $params=[

            'type' => $this->input->get_post('type'),// 公告or操作手册
            'news_title' =>$this->input->get_post('news_title'),
            'update_time_start' =>$this->input->get_post('update_time_start'),
            'update_time_end' =>$this->input->get_post('update_time_end'),
            'menu_id'=>$this->input->get_post('menu_id'),



        ];


        $page_data=$this->format_page_data();
        $result=$this->purchase_news_model->get_edit_news($params,$page_data['offset'],$page_data['limit'],$page_data['page']);
        $this->success_json($result['data_list'],$result['paging_data']);
    }

    public function format_page_data()
    {
        $page = $this->input->get_post('offset');
        $limit          = $this->input->get_post('limit');
        if(empty($page)  or $page < 0 )  $page  = 1;
        $limit         = query_limit_range($limit);
        $offset        = ($page - 1) * $limit;
        return [
            'offset' => $offset,
            'limit' => $limit,
            'page' => $page
        ];
    }


    //删除消息
    public function del_news()
    {
        $id = $this->input->get_post('id');
        if (empty($id)) {
            $this->error_json('参数异常');

        }
        $flag = $this->purchase_news_model->purchase_db->where('id',$id)->delete('news_list');
        $content_flag = $this->purchase_news_model->purchase_db->where('news_id',$id)->delete('news_detail');//删除文章内容表

        if ($flag) {
            $this->success_json('删除成功');
        } else {
            $this->error_json('删除失败');

        }


    }

    //获取用户一定频率浏览新增消息数
    public function get_user_no_read_nums()
    {
        $read_info = $this->purchase_news_model->purchase_db->select('*')->from('news_login_log')->where('user_id',getActiveUserId())->get()->row_array();//用户是否点进来看过
        if (empty($read_info)) {
            $news_info = $this->purchase_news_model->purchase_db->select('count(*) as num')->from('news_list')->get()->row_array();
        } else {
            $news_info = $this->purchase_news_model->purchase_db->select('count(*) as num')->from('news_list')->where('create_time>=',$read_info['read_time'])->get()->row_array();

        }

        $this->success_json($news_info['num']);



    }

















}