<?php

/**
 * 消息栏目模块
 * @time:2020/8/12
 * @author:Dean
 **/
class Purchase_news_model extends Purchase_model{

    protected $table_name = 'news_list';
    protected $log_table_name = 'news_read_log';//浏览日志
    protected $thumb_up_table_name = 'news_thumb_up';
    protected $login_log_table_name ='news_login_log';
    protected $system_reject_news = 'purchase_system_reject_news';//系统驳回表
    protected $detail_name = 'news_detail';//系统驳回表



    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_menu_model');


    }





    //获取所有一级类目下的二级类目

    public function get_sec_menu($type,$parent_id)
    {
        $where = ['type'=>$type,'parent_id'=>$parent_id];
        $result = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where($where)
            ->get()
            ->result_array();
        return empty($result)?[]:$result;

    }

    //获取类目下的所有子类目或者他为子目录就返回本身id

    public function get_all_sec_menu_id($id)
    {
        $menu_list = [];
        $result = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where(['id'=>$id])
            ->get()
            ->row_array();
        if ($result) {
            if ($result['parent_id'] != 0) {
                $menu_list[] = $id;

            } else {
                $sec_list =$this->get_sec_menu($result['type'],$id);
                if (!empty($sec_list)) {
                    $menu_list =  array_merge(array_column($sec_list,'id'),$id);

                }

            }


        }
        return $menu_list;


    }



    //编辑消息
    public function opr_news($params)
    {
        $arr = ['code'=>true,'msg'=>''];
        $id = $params['id']??0;
        $data = [];
        //id存在更新
        try{
            if (!empty($params['file_path'])) {
                $temp_file =[];
                foreach ($params['file_path'] as $file_path) {
                    $temp_file[] = $file_path['href'];
                    $temp_file_name[] = $file_path['name'];


                }
                $file_path = implode(',',$temp_file);
                $file_name=  implode(',',$temp_file_name);
            } else {
                $file_path = '';
                $file_name = '';
            }




            if ($id) {

                $data['news_title'] = $params['news_title'];
                $data['menu_id'] = $params['menu_id'];
                $data['type'] = $params['type'];

                //文章内容如果超出1000，就存text
                if (strlen($params['content'])>=1000){
                   $save_tag = $this->save_long_article($id,$params['content']);
                   if (empty($save_tag)) {
                       throw new Exception('保存文章失败');


                   }
                    $data['content'] = '';
                } else {
                    $data['content'] = $params['content'];
                }
                $data['status'] = $params['status'];
                $data['file_path'] = $file_path;
                $data['file_name'] = $file_name;

                $data['update_user_id'] = getActiveUserId();
                $data['update_user'] = getActiveUserName();
                $data['update_time'] = date("Y-m-d H:i:s");


                $flag = $this->purchase_db->where(['id'=>$id])->update($this->table_name,$data);
                if (!$flag) {
                    throw new Exception('编辑失败');

                }
                $arr['msg'] = '编辑成功';

            } else {
                $data['news_title'] = $params['news_title'];
                $data['menu_id'] = $params['menu_id'];
                $data['type'] = $params['type'];
                if (strlen($params['content'])>=1000){

                    $data['content'] = '';
                } else {
                    $data['content'] = $params['content'];
                }

                $data['status'] = $params['status'];
                $data['file_path'] = $file_path;
                $data['file_name'] = $file_name;
                $data['create_user_id'] = getActiveUserId();
                $data['create_user'] = getActiveUserName();
                $data['create_time'] = date('Y-m-d H:i:s');

                $flag = $this->purchase_db->insert($this->table_name,$data);
                if (!$flag) {
                    throw new Exception('新增失败');

                }

                $news_id =$this->purchase_db->insert_id();

                if (strlen($params['content'])>=1000){
                    $save_tag = $this->save_long_article($news_id,$params['content']);
                    if (empty($save_tag)) {
                        throw new Exception('保存文章失败');


                    }
                }


                $arr['msg'] = '新增成功';


            }

        }catch (Exception $e){
            $arr['msg'] = $e->getMessage();
            $arr['code'] = false;


        }

        return $arr;




    }

    public function show_news($params)
    {
        $result = ['code'=>true,'data'=>[],'msg'=>''];
        try{
            $news_info = $this->get_news_detail($params['id']);
            if (empty($news_info)) {
                throw new Exception('消息不存在');
            }
            //查看用户是否点过赞
           
            $parent_info = $this->purchase_menu_model->get_parent_menu_info($news_info['menu_id']);
            $news_info['parent_id'] = $parent_info['id'];

            if (!empty($news_info['file_path'])&&!empty($news_info['file_name'])) {
                $path_back = [];
                $path_list = explode(',',$news_info['file_path']);
                $name_list= explode(',',$news_info['file_name']);

                if (!empty($path_list)) {
                    foreach ($path_list as $key=>$val) {
                        $temp = [];
                        $temp['href'] = $val;
                        $temp['name'] = $name_list[$key];
                        $path_back[] = $temp;

                    }

                }

                $news_info['file_path'] = $path_back;


            } else {
                $news_info['file_path'] = [];

            }
            //如果是首次点击记录
            if (!empty($params['is_read'])) {
                $is_read = $this->purchase_db->select('*')->from($this->log_table_name)->where(['user_id'=>getActiveUserId(),'news_id'=>$params['id']])->get()->row_array();//用户是否点进来看过
                if (empty($is_read)) {
                    $flag = $this->purchase_db->insert($this->log_table_name,['user_id'=>getActiveUserId(),'news_id'=>$params['id']]);
                    if (empty($flag)) {
                        throw new Exception('浏览异常');


                    }

                }

            }

            $result ['data'] = $news_info;




        }catch (Exception $e){
            $result['code'] = false;
            $result['msg'] = $e->getMessage();



        }
        return $result;


    }




    //通过id获取消息详情
    public function get_news_detail($id)
    {
        
       $result = $this->purchase_db->select('news.*,detail.content as detail_content')
            ->from($this->table_name.' news')
            ->join($this->detail_name.' detail','news.id=detail.news_id','left')
            ->where(['news.id'=>$id])
            ->get()
            ->row_array();

        if (!empty($result)) {
            $result['content'] = empty($result['content'])?$result['detail_content']:$result['content'];

        }
        return $result;
    }

    //删除,$num为目录下的消息
    public function  del_menu($menu_list,$num)
    {
        $result = ['code'=>true,'msg'=>''];
        try{
            $flag = $this->purchase_db->where_in('id',$menu_list)->delete($this->table_name);
            if (!$flag) {
                throw new Exception('删除目录失败');
            }
            if ($num>0) {//目录下有消息也要删除
                $flag = $this->purchase_db->where_in('menu_id',$menu_list)->delete('news_list');
                if (!$flag) {
                    throw new Exception('删除消息失败');
                }

            }
            $result['msg'] = '删除成功';

        }catch (Exception $e){
            $result['code']=false;
            $result['msg']=$e->getMessage();

        }
        return $result;


    }


    //是否置顶$is_top =1 置顶 2取消置顶(更改原来逻辑)
    public function set_top($id,$is_top,$type)
    {
        $result = ['code'=>true,'msg'=>''];

        try{
            if (!in_array($is_top,[1,2])) {
                throw new Exception('置顶操作参数有误');
            }
            if ($is_top == 1) {


                $flag = $this->purchase_db->where(['id'=>$id])->update($this->table_name,['sort'=>1]);
                $msg = '置顶';

            } else {
                $flag = $this->purchase_db->where(['id'=>$id])->update($this->table_name,['sort'=>2]);
                $msg='取消置顶';
            }

            if (!$flag) {
                throw new Exception($msg.'失败');

            } else {
                $result['msg'] = $msg.'成功';

            }





        }catch(Exception $e){
            $result['code']=false;
            $result['msg']=$e->getMessage();

        }

        return $result;

    }





    public function format_page_data(){
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


    public function get_show_news($params,$offset=null,$limit=null,$page=1)
    {


        $data_list_temp = [];
        $field='*';

        if (!empty($params['menu_id'])) {
            $menu_temp_list = $this->purchase_menu_model->get_all_sec_menu_id($params['menu_id']);

        }



        $this->purchase_db->select($field)
            ->from($this->table_name);



        if(isset($params['type']) && !empty($params['type'])){
            $this->purchase_db->where('type',$params['type']);

        }
        if(isset($params['news_title']) && !empty($params['news_title'])){
            $this->purchase_db->like('news_title', $params['news_title'], 'both');
        }


        if(isset($params['update_time_start']) && !empty($params['update_time_start'])){
            $this->purchase_db->group_start();
            $start_where = " (update_time='0000-00-00 00:00:00' and create_time>='{$params['update_time_start']}') or (update_time>='{$params['update_time_start']}' and update_time!='0000-00-00 00:00:00')";
            $this->purchase_db->where($start_where);
            $this->purchase_db->group_end();
        }

        if(isset($params['update_time_end']) && !empty($params['update_time_end'])){
            $this->purchase_db->group_start();
            $end_where = " (update_time='0000-00-00 00:00:00' and create_time<='{$params['update_time_end']}') or (update_time<='{$params['update_time_end']}' and update_time!='0000-00-00 00:00:00')";
            $this->purchase_db->where($end_where);
            $this->purchase_db->group_end();
        }

        if(isset($params['menu_id']) && !empty($params['menu_id'])){

            $this->purchase_db->where_in('menu_id',$menu_temp_list);
        }



        $clone_db = clone($this->purchase_db);
        $total=$clone_db->count_all_results();//符合当前查询条件的总记录数
        $result=$this->purchase_db->order_by('sort asc ,update_time desc,create_time desc')->limit($limit,$offset)->get()->result_array();

      

        if (!empty($result)) {

            foreach ($result as $key => $value) {
                //一级分类名称
                $value_temp=[];
                $parent_info = $this->purchase_menu_model->get_parent_menu_info($value['menu_id']);
                $value_temp['id'] = $value['id'];
                $value_temp['menu_name'] = $parent_info['menu_name'];
                $value_temp['news_title'] = $value['news_title'];
                $value_temp['is_top']= $value['sort'];
                $value_temp['update_time'] = $value['update_time']=='0000-00-00 00:00:00'?$value['create_time']:$value['update_time'];
                $read_info = $this->get_reads_info($value['id']);
                $value_temp['logs_num'] = $read_info['logs_num'];
                $value_temp['thumb_num'] = $read_info['thumb_num'];
                $data_list_temp[] = $value_temp;


            }
        }


        $return_data = [
            'data_list'   => [
                'value' => $data_list_temp,
                'key' =>[
                ],
                'drop_down_box' =>$this->purchase_menu_model->get_menu_detail($params['type']),
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit

            ],

        ];

        return $return_data;
    }

    public function get_edit_news($params,$offset=null,$limit=null,$page=1)
    {


        $data_list_temp = [];
        $menu_temp_list =[];
        $field='*';

        if (!empty($params['menu_id'])) {
            $menu_temp_list = $this->purchase_menu_model->get_all_sec_menu_id($params['menu_id']);

        }

        $this->purchase_db->select($field)
            ->from($this->table_name);



        if(isset($params['type']) && !empty($params['type'])){
            $this->purchase_db->where('type',$params['type']);

        }
        if(isset($params['news_title']) && !empty($params['news_title'])){
            $this->purchase_db->like('news_title', $params['news_title'], 'both');
        }


        if(isset($params['update_time_start']) && !empty($params['update_time_start'])){
            $this->purchase_db->group_start();
            $start_where = " (update_time='0000-00-00 00:00:00' and create_time>='{$params['update_time_start']}') or (update_time>='{$params['update_time_start']}' and update_time!='0000-00-00 00:00:00')";
            $this->purchase_db->where($start_where);
            $this->purchase_db->group_end();

        }

        if(isset($params['update_time_end']) && !empty($params['update_time_end'])){
            $this->purchase_db->group_start();
            $end_where = " (update_time='0000-00-00 00:00:00' and create_time<='{$params['update_time_end']}') or (update_time<='{$params['update_time_end']}' and update_time!='0000-00-00 00:00:00')";
            $this->purchase_db->where($end_where);
            $this->purchase_db->group_end();
        }


        if(isset($params['menu_id']) && !empty($params['menu_id'])){

            $this->purchase_db->where_in('menu_id',$menu_temp_list);
        }



        $clone_db = clone($this->purchase_db);
        $total=$clone_db->count_all_results();//符合当前查询条件的总记录数

        $result=$this->purchase_db->order_by('sort asc ,update_time desc,create_time desc ')->limit($limit,$offset)->get()->result_array();





        if (!empty($result)) {



            foreach ($result as $key => $value) {
                //一级分类名称
                $value_temp=[];
                $value_temp['id'] = $value['id'];
                $value_temp['is_top']= $value['sort'];
                $value_temp['news_title'] = $value['news_title'];
                $value_temp['create_user'] = $value['create_user'];
                $value_temp['create_time'] = $value['create_time'];
                $value_temp['update_user'] = !empty($value['update_user'])?$value['update_user']:$value['create_user'];
                $value_temp['update_time'] = $value['update_time']=='0000-00-00 00:00:00'?$value['create_time']:$value['update_time'];
                $data_list_temp[] = $value_temp;


            }
        }


        $return_data = [
            'data_list'   => [
                'value' => $data_list_temp,
                'key' =>[ '序号','消息标题','创建信息','更新信息','操作'
                ],
                'drop_down_box' =>$this->purchase_menu_model->get_menu_detail($params['type']),
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit

            ],

        ];

        return $return_data;
    }
    //根据news_id获取点赞数和已阅读数
    public function get_reads_info($news_id)
    {
        $logs_num =   $this->purchase_db->select('count(*) as num')
            ->from($this->log_table_name)
            ->where('news_id',$news_id)
            ->get()
            ->row_array();

        $thumb_num =   $this->purchase_db->select('count(*) as num')
            ->from($this->thumb_up_table_name)
            ->where('news_id',$news_id)
            ->get()
            ->row_array();

        return ['logs_num'=>$logs_num['num'],'thumb_num'=>$thumb_num['num']];


    }

    public function remark_user_read_time()
    {
        $user_id = getActiveUserId();//用户id
        $is_read = $this->purchase_db->select('*')->from($this->login_log_table_name)->where('user_id',$user_id)->get()->row_array();//用户是否点进来看过
        if (empty($is_read)) {//不存在就新增
             $this->purchase_db->insert($this->login_log_table_name,['user_id'=>$user_id,'read_time'=>date('Y-m-d H:i:s')]);

       } else {
            $this->purchase_db->where('user_id',$user_id)->update($this->login_log_table_name,['read_time'=>date('Y-m-d H:i:s')]);

        }
    }


    //保存文章内容
    public function save_long_article($news_id,$content)
    {
        //查询是否已存在文章标识
        $detail_info =   $this->purchase_db->select('*')->from($this->detail_name)->where('news_id',$news_id)->get()->row_array();
        if (!empty($news_info)) {
            $flag =$this->purchase_db->where('id',$detail_info['id'])->update($this->detail_name,['content'=>$content]);

        } else {
            $flag =$this->purchase_db->insert($this->detail_name,['content'=>$content,'news_id'=>$news_id]);


        }

        return $flag;




    }


/*    //获取当前用户点赞信息
    public function thumb_info()
    {


    }*/

    //系统驳回写入表

    /*public function log_system_reject_notes($module,$opr_type,$opr_title,$remark,$user_id,$select_path,$select_data)
    {
        if ($module) {
            switch ($module){
                case 1:
                    $data=['module'=>1,'opr_type'=>$opr_type,'opr_title'=>$opr_title,'remark'=>$remark,'user_id'=>$user_id,'select_info'=>$select_path,'select_data'=>$select_data];

            }

        }

    }*/














}