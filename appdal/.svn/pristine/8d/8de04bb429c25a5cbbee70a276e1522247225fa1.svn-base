<?php
/**
 * Created by PhpStorm.
 * 采购需求数据库模型类
 * User: Jeff
 * Date: 2019/05/05 11:23
 */

class Suggest_lock_model extends Purchase_model
{

    protected $table_name = 'pur_lock_suggest_config';// 数据表名称

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @desc 获取锁单配置列表
     * @author Jeff
     * @Date 2019/5/5 11:52
     * @param $params
     * @return
     */
    public function get_list($offset = 0,$limit = 0,$page = 1)
    {
        if(!is_null($limit)) {
            $limit = query_limit_range($limit, false);
        }

        $return = [];
        $query_builder = $this->purchase_db;
        $list=$query_builder->select('*')
            ->from($this->table_name)
            ->order_by('id desc')
            ->get('',$limit,$offset)
            ->result_array();
        if (!empty($list)){
            $return['list'] = $list;
        }else{
            $return['list'] = [];
        }
        //数据汇总
        $huizong_arr = $query_builder->select('count(id) AS total_count')->from($this->table_name)->get()->row_array();
        $return['page_data'] = [
            'total'     => $huizong_arr['total_count'],
            'offset'    => $page,
            'limit'     => $limit
        ];

        return $return;
    }

    /**
     * @desc 编辑过期时间
     * @author Jeff
     * @Date 2019/5/5 13:38
     * @param $id
     * @param $expiration
     * @return
     */
    public function create_lock($lock_time_start, $lock_time_end, $not_reduce_day, $purchase_total_fba_inside, $purchase_total_over_sea)
    {
        $return = ['code'=>false,'msg'=>''];

        $start_time = "0000-00-00 ".$lock_time_start;
        $end_time = "0000-00-00 ".$lock_time_end;

        $add_data = [
            'create_user_name' => getActiveUserName(),
            'create_time' => date("Y-m-d H:i:s",time()),
            'lock_time_start' => $start_time,
            'lock_time_end' => $end_time,
            'not_reduce_day' => $not_reduce_day,
            'purchase_total_fba_inside' => $purchase_total_fba_inside,
            'purchase_total_over_sea' => $purchase_total_over_sea,
        ];

        $this->purchase_db->trans_begin();
        try{
            //添加配置
            $res = $this->purchase_db->insert($this->table_name,$add_data);

            if (!$res) throw new Exception('添加配置失败');

            $this->purchase_db->trans_commit();
            $return['code']=true;

        }catch(Exception $e){
            $this->purchase_db->trans_rollback();
            $return['msg']=$e->getMessage();
        }

        return $return;
    }

    //获取一条数据
    public function get_one($id='')
    {
        $query_builder = $this->purchase_db;
        $where = [];
        if ($id){
            $where['id'] = $id;
        }
        $data=$query_builder->select('*')
            ->from($this->table_name)
            ->where($where)
            ->order_by('id desc')
            ->get()
            ->row_array();
        return $data;
    }
    
    //判断是否在锁单时间内
    public function validate_is_lock_time()
    {
        $return = ['code'=>200,'message'=>'','data'=>''];
        $lock_data = $this->get_one();
        if (empty($lock_data)) {
            $return['message'] = '锁单配置不存在';
            $return['code'] = 500;
            return $return;
        }

        $start_time = $lock_data['lock_time_start'];
        $end_time = $lock_data['lock_time_end'];

        $start_time_hour = explode(' ',$start_time);
        $start_time_hour = isset($start_time_hour[1])?$start_time_hour[1]:'';
        if (empty($start_time_hour)){
            $return['message'] = '锁单配置数据存在问题';
            $return['code'] = 500;
            return $return;
        }


        $end_time_hour = explode(' ',$end_time);
        $end_time_hour = isset($end_time_hour[1])?$end_time_hour[1]:'';
        if (empty($end_time_hour)){
            $return['message'] = '锁单配置数据存在问题';
            $return['code'] = 500;
            return $return;
        }

        $now = time();//当前时间戳
        $start_time_unix = strtotime($start_time_hour);//开始时间时间戳
        $end_time_unix = strtotime($end_time_hour);//结束时间时间戳

        if ( $now < $start_time_unix && $now > $end_time_unix){
            //在锁单时间外
            $return['data'] = $lock_data;
            return $return;
        }else{
            $return['data'] = $lock_data;
            $return['message'] = '在锁单时间内';
            return $return;
        }

    }

    //判断是否在锁单时间内
    public function validate_is_before_ten_min($lock_info)
    {
        $return = ['code'=>200,'message'=>''];

        $end_time = $lock_info['lock_time_end'];

        $end_time_hour = explode(' ',$end_time);
        $end_time_hour = isset($end_time_hour[1])?$end_time_hour[1]:'';
        if (empty($end_time_hour)){
            $return['message'] = '锁单配置数据存在问题';
            $return['code'] = 500;
            return $return;
        }

        $now = time();//当前时间戳
        $end_time_unix = strtotime($end_time_hour);

        if ($end_time_unix-$now<600 && ($now<$end_time_unix)){//现在时间是否小于锁单结束时间且相差十分钟之内
            //执行锁单
            return $return;
        }else{
            //不用执行锁单
            $return['message'] = '现在还不用执行实单锁单';
            return $return;
        }

    }

}