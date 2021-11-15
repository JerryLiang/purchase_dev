<?php

/**
 * 消息栏目模块
 * @time:2020/8/12
 * @author:Dean
 **/
class Purchase_menu_model extends Purchase_model{

    protected $table_name = 'news_menu';

    public function __construct()
    {
        parent::__construct();

    }


    //获取栏目信息
    public function get_menu_list($type)
    {

        $menu_list = $this->get_menu_detail($type);
        return $menu_list;

    }


    /*
     * @desc 获取所有栏目信息
     * @params $type int 栏目类型 $append bool 是否显示二级类目
     * @return array
     */
    public function get_menu_detail($type,$append = true)
    {

        $where = ['type'=>$type,'parent_id'=>0];
        $result = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where($where)
            ->order_by('sort desc')
            ->get()
            ->result_array();
        if (!empty($result)) {
            if ($append){
                foreach ($result as &$parent_menu) {
                    $sec_list = $this->get_sec_menu($type,$parent_menu['id']);
                    $parent_menu['sec_list'] = $sec_list;

                }

            }

        }

        return empty($result)?[]:$result;


    }

    //获取所有一级类目下的二级类目

    public function get_sec_menu($type,$parent_id)
    {
        $where = ['type'=>$type,'parent_id'=>$parent_id];
        $result = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where($where)
            ->order_by('sort desc')
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
                    $menu_list =  array_merge(array_column($sec_list,'id'),[$id]);

                } else {
                    $menu_list[] = $id;
                }


            }


        }
        return $menu_list;


    }


    //编辑
    public function opr_menu($params)
    {
        $arr = ['code'=>true,'msg'=>''];
        $type = $params['type'];
        $menu_name = $params['menu_name'];
        $parent_id = $params['parent_id']??0;
        $id = $params['id']??0;



        $data = [];
        //id存在更新
        try{


            if ($id) {

                $menu_info  = $this->purchase_db->select('*')
                    ->from($this->table_name)
                    ->where(['menu_name'=>$menu_name,'id!='=>$id])
                    ->get()
                    ->row_array();
                if (!empty($menu_info)) {
                    throw new Exception('名称已存在');

                }

                $data['menu_name'] = $menu_name;
                $data['update_time'] = date('Y-m-d H:i:s');
                $data['update_user_id'] = getActiveUserId();
                $data['update_user'] = getActiveUserName();
                $data['parent_id'] = $parent_id;

                $flag = $this->purchase_db->where(['id'=>$id])->update($this->table_name,$data);
                if (!$flag) {
                    throw new Exception('编辑失败');

                }
                $arr['msg'] = '编辑成功';

            } else {

                $menu_info  = $this->purchase_db->select('*')
                    ->from($this->table_name)
                    ->where(['menu_name'=>$menu_name])
                    ->get()
                    ->row_array();
                if (!empty($menu_info)) {
                    throw new Exception('名称已存在');

                }


                $data['type'] = $type;
                $data['menu_name'] = $menu_name;
                $data['parent_id'] = $parent_id;
                $data['create_user_id'] = getActiveUserId();
                $data['create_user'] = getActiveUserName();
                $data['create_time'] = date('Y-m-d H:i:s');

                $flag = $this->purchase_db->insert($this->table_name,$data);
                if (!$flag) {
                    throw new Exception('新增失败');

                }
                $arr['msg'] = '新增成功';


            }

        }catch (Exception $e){
            $arr['msg'] = $e->getMessage();
            $arr['code'] = false;


        }

        return $arr;




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


            $news_info = $this->purchase_db->select('*')
                ->from('news_list')
                ->where_in('menu_id',$menu_list)
                ->get()
                ->result_array();
            if (!empty($news_info)) {//目录下有消息也要删除
                $flag = $this->purchase_db->where_in('menu_id',$menu_list)->delete('news_list');
                $content_flag = $this->purchase_db->where_in('news_id',array_column($news_info,'id'))->delete('news_detail');//删除内容表

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




    //根据id获取父分类信息
    public function get_parent_menu_info($id)
    {
        $parent_info =[];
        $result = $this->purchase_db->select('*')
            ->from($this->table_name)
            ->where(['id'=>$id])
            ->get()
            ->row_array();
        if (!empty($result)) {
            $parent_info =  $result = $this->purchase_db->select('*')
                ->from($this->table_name)
                ->where(['id'=>$result['parent_id']])
                ->get()
                ->row_array();

        }
        return $parent_info;

    }
















}