<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Reason_config_model extends Purchase_model {
    
      protected $table_name = 'reason_type_config';
      protected $config_table_name = 'reason_config';


    public function __construct() {
        parent::__construct();
        $this->load->helper('status_order');
    }
    /**
     * 原因配置列表
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function reason_type_list($offsets, $limit, $page){
        $query=$this->purchase_db;
        $query->select('id,reason_name');
        $query->from($this->table_name);
        $query->where('status',1);//启用
        $count_qb = clone $query;
        $result = $query->limit($limit, $offsets)->order_by('id','desc')->get()->result_array();

        //统计总数要加上前面筛选的条件
        $count_row = $count_qb->select("count(id) as num")->get()->row_array();
        $total_count = isset($count_row['num']) ? (int)$count_row['num'] : 0;

        $key_table = [
                '序号',
                '内容',
                '操作'];
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
     * 原因配置列表
     * @param array $params
     * @param int $offsets
     * @param int $limit
     * @param int $page
     * @return array
     */
    public function get_cancel_reason_list($param=[]){
        $query=$this->purchase_db;
        $query->select('*');
        $query->from($this->config_table_name);
        $query->where('reason_type',1);//作废原因

        if (isset($param['status']) && !empty($param['status']) ){
            $query->where('status',$param['status']);//启用状态
        }

        $result = $query->order_by('sort')->get()->result_array();

        $key_table = [
            '序号',
            'ID',
            '作废原因',
            '状态',
            '操作'];
        $return_data = [
            'key' => $key_table,
            'values' => $result,
        ];
        return $return_data;

    }

    /**
     * @desc 编辑原因
     * @author Jeff
     * @Date 2019/10/10 10:30
     * @param $id
     * @param $reason_name
     * @throws Exception
     * @return
     */
    public function reason_edit($id, $reason_name)
    {
        $query=$this->purchase_db;
        $query->select('id');
        $query->from($this->config_table_name);
        $query->where('id',$id);
        $result = $query->order_by('sort')->get()->row_array();
        if (empty($result)) throw new Exception('数据不存在');

        $edit_data = [
            'reason_name'=>$reason_name,
            'modify_time'=>date('Y-m-d H:i:s'),
            'modify_user_name'=>getActiveUserName(),
        ];

        $update_res=$this->purchase_db->where('id',$id)->update($this->config_table_name,$edit_data);

        if (empty($update_res)) throw new Exception('编辑失败');
    }

    /**
     * @desc 修改启用状态
     * @author Jeff
     * @Date 2019/10/10 10:30
     * @param $id
     * @param $status
     * @throws Exception
     * @return
     */
    public function reason_status_change($id, $status)
    {
        $query=$this->purchase_db;
        $query->select('id');
        $query->from($this->config_table_name);
        $query->where('id',$id);
        $result = $query->order_by('sort')->get()->row_array();
        if (empty($result)) throw new Exception('数据不存在');

        $edit_data = [
            'status'=>$status,
            'modify_time'=>date('Y-m-d H:i:s'),
            'modify_user_name'=>getActiveUserName(),
        ];

        $update_res=$this->purchase_db->where('id',$id)->update($this->config_table_name,$edit_data);

        if (empty($update_res)) throw new Exception('编辑失败');
    }

    /**
     * @desc 添加原因
     * @author Jeff
     * @Date 2019/10/10 10:30
     * @param $reason_type
     * @param $reason_name
     * @throws Exception
     * @return
     */
    public function reason_add($reason_type, $reason_name)
    {
        $query=$this->purchase_db;
        $query->select('sort');
        $query->from($this->config_table_name);
        $query->where('reason_type',$reason_type);
        $result = $query->order_by('sort desc')->get()->row_array();

        if (empty($result)) throw new Exception('该类型原因数据不存在');

        $insert_data = [
            'reason_name'=>$reason_name,
            'reason_type'=>$reason_type,
            'create_time'=>date('Y-m-d H:i:s'),
            'create_user_name'=>getActiveUserName(),
            'sort'=>$result['sort']+1,
        ];

        $insert_res=$this->purchase_db->insert($this->config_table_name,$insert_data);

        if (empty($insert_res)) throw new Exception('添加失败');
    }

    /**
     * @desc 原因排序
     * @author Jeff
     * @Date 2019/10/10 10:31
     * @param $reason_type
     * @param $sort_type
     * @param $id
     * @throws Exception
     * @return
     */
    public function reason_sort($sort_type, $id)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];

        $this->purchase_db->trans_begin();
        try {
            $query=$this->purchase_db;
            $query->select('id,sort,reason_type');
            $query->from($this->config_table_name);
            $query->where('id',$id);
            $result = $query->order_by('sort')->get()->row_array();
            if (empty($result)) throw new Exception('数据不存在');

            if ($sort_type==1){//上移
                //查询是否还有排在前面的数据
                $query=$this->purchase_db;
                $query->select('id,sort');
                $query->from($this->config_table_name);
                $query->where('sort',$result['sort']-1);
                $query->where('reason_type',$result['reason_type']);
                $query_data = $query->order_by('sort')->get()->row_array();

                if (empty($query_data)) throw new Exception('此数据已是最大排序了');

                $edit_data = [
                    'sort'=>$query_data['sort'],
                    'modify_time'=>date('Y-m-d H:i:s'),
                    'modify_user_name'=>getActiveUserName(),
                ];

                $sort_data = [
                    'sort'=>$result['sort'],
                    'modify_time'=>date('Y-m-d H:i:s'),
                    'modify_user_name'=>getActiveUserName(),
                ];
            }else{
                //查询是否还有排在后面的数据
                $query=$this->purchase_db;
                $query->select('id,sort');
                $query->from($this->config_table_name);
                $query->where('sort',$result['sort']+1);
                $query->where('reason_type',$result['reason_type']);
                $query_data = $query->order_by('sort')->get()->row_array();

                if (empty($query_data)) throw new Exception('此数据已是最小排序了');

                $edit_data = [
                    'sort'=>$query_data['sort'],
                    'modify_time'=>date('Y-m-d H:i:s'),
                    'modify_user_name'=>getActiveUserName(),
                ];

                $sort_data = [
                    'sort'=>$result['sort'],
                    'modify_time'=>date('Y-m-d H:i:s'),
                    'modify_user_name'=>getActiveUserName(),
                ];
            }

            $update_res=$this->purchase_db->where('id',$id)->update($this->config_table_name,$edit_data);
            if (empty($update_res)) throw new Exception('排序失败');

            $sort_res=$this->purchase_db->where('id',$query_data['id'])->update($this->config_table_name,$sort_data);
            if (empty($sort_res)) throw new Exception('排序失败');

            $this->purchase_db->trans_commit();
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }
        return $return;
    }

    /**
     * @desc 编辑原因统一提交(包括新增)
     * @author Jeff
     * @Date 2019/10/10 10:30
     * @param $reason_type
     * @param $reason_name
     * @throws Exception
     * @return
     */
    public function reason_edit_submit($edit_data, $add_data)
    {
        $return = ['code' => false, 'msg' => '', 'data' => ''];
        $edit_data_arr = json_decode($edit_data,true);
        $now_time = date('Y-m-d H:i:s');
        $user_name = getActiveUserName();

        $this->purchase_db->trans_begin();
        try {

            foreach ($edit_data_arr as &$value){
                $value['modify_time'] = $now_time;
                $value['modify_user_name'] = $user_name;
            }
            $update_res=$this->purchase_db->update_batch($this->config_table_name,$edit_data_arr,'id');

            if (empty($update_res)) throw new Exception('编辑失败');

            if (!empty($add_data)){
                $add_data_arr = json_decode($add_data,true);

                foreach ($edit_data_arr as &$value){
                    $value['create_time'] = $now_time;
                    $value['create_user_name'] = $user_name;
                }
                $insert_res=$this->purchase_db->insert_batch($this->config_table_name,$add_data_arr);
                if (empty($insert_res)) throw new Exception('添加失败');
            }

            $this->purchase_db->trans_commit();
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * 获取作废原因数据
     * @param: $cateid   int  作废原因ID
     * @author:luxu
     * @time:2021年3月5号
     **/

    public function get_reason_datas($cateid){

       return $this->purchase_db->from($this->config_table_name)->where_in("id",$cateid)->get()->result_array();
    }

}