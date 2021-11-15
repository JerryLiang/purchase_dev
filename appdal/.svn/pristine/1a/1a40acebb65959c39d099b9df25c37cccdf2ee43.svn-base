<?php

/**
 * Created by PhpStorm.
 * User: 袁学文
 * Date: 2019/2/20
 * Time: 14:17
 */
class Status_set_model extends Purchase_model {
    protected $table_name = "param_sets";


    /**
     * 菜单单好记录redis缓存中
     * @author 袁学文
     * @data   2019-2-20
     */
    public function get_set_redis(){
        //加载redis
        $this->load->library('rediss');
        $redis = $this->rediss;

        $this->purchase_db->reset_query();// 重置 SELECT，避免在此之前的查询注入 SQL语句

        //获取表中数据
        $key = "STATUS";  //缓存键
        //没有值就查询数据库记录redis中
        $order_set = $this->purchase_db
            ->select('pKey,pValue')
            ->get('param_sets')
            ->result_array();
        if(!$order_set){
            throw new Exception("param_sets表不存在");
        }
        $order_set = array_column($order_set, 'pValue', 'pKey');
        try{
            // 设置需求类型
            $demand = $this->purchase_db
                ->select('demand_type_id,demand_type_name')
                ->get('demand_type')
                ->result_array();
            if($demand && !empty($demand)){
                $demand_list = [];
                foreach ($demand as $val){
                    $demand_list[$val['demand_type_id']] = $val['demand_type_name'];
                }
                if(!empty($demand_list)){
                    $demand_list = json_encode($demand_list);
                    $order_set["PUR_DEMAND_TYPE"] = $demand_list;
                }
            }
        }catch (Exception $e){}
        //存入redis中
        $redis->setData($key, $order_set);

        return $order_set;
    }


}
