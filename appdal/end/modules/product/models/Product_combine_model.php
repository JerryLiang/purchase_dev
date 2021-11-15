<?php
/**
 * Created by PhpStorm.
 * 产品捆绑销售组合表
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Product_combine_model extends Purchase_model {
    protected $table_name   = 'product_combine';// 产品捆绑销售组合表

    public function __construct(){
        parent::__construct();

    }

    /**
     * 获取基本信息
     * @author Jolon
     * @param string $sku SKU
     * @return array|bool
     */


     public function FindOnes($datass)
    {
        foreach ($datass as $v)
        {
            $combine_info = $this->get_combine_info($v['combine_id']);
            $combine_data['combine_id'] = $v['combine_id'];
            $combine_data['product_id'] = $v['product_id'];
            $combine_data['sku'] = $v['sku'];
            $combine_data['product_qty'] = $v['product_qty'];
            $combine_data['product_combine_id'] = $v['product_combine_id'];
            $combine_data['product_combine_sku'] = $v['product_combine_sku'];

            if (!empty($combine_info))
            {
                $combine_data['update_time'] = date('Y-m-d H:i:s');
                $this->update_combine_one($v['combine_id'],$combine_data);
                $data['success_list'][]         = $v['combine_id'];
                $data['failure_list'][]         = '';
            } else {
                $combine_data['create_time'] = date('Y-m-d H:i:s');
                $combine_data['update_time'] = date('Y-m-d H:i:s');
                $this->insert_tax_one($combine_data);
                $data['success_list'][]         = $v['combine_id'];
                $data['failure_list'][]         = '';
            }
        }

        return $data;


    }


    public function get_combine_info($combine_id){
        if(empty($combine_id)) return [];

        $where = ['combine_id' => $combine_id];
        $combine_info = $this->purchase_db->where($where)->get($this->table_name)->row_array();

        return $combine_info;

    }



    /**
     * 更新 信息
     * @author Jolon
     * @param $sku
     * @param $update_data
     * @return bool
     */
    public function update_combine_one($combine_id,$update_data){

        $result = $this->purchase_db->where('combine_id',$combine_id)->update($this->table_name,$update_data);

        return $result?true:false;
    }


    /**
     * 更新 插入数据
     * @author Jolon
     * @param $insert_data
     * @return bool
     */
    public function insert_tax_one($insert_data){

        $result = $this->purchase_db->insert($this->table_name,$insert_data);

        return $result?true:false;
    }


}