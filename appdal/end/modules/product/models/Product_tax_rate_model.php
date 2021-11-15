<?php
/**
 * Created by PhpStorm.
 * 产品税率表
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Product_tax_rate_model extends Purchase_model {
    protected $table_name   = 'product_tax_rate';// 产品税率表

    public function __construct(){
        parent::__construct();

        $this->load->model('product_tax_rate_model','',false,'product_tax_model');
    }

    /**
     * 获取基本信息
     * @author Jolon
     * @param string $sku SKU
     * @return array|bool
     */


    public function saveOne($v)
    {
        
        $tax_info = $this->get_tax_rate_info($v['sku']);
        //如果存在
        if (!empty($tax_info)) {
            $tax_data['tax_rate'] = isset($v['tax_rate'])?$v['tax_rate']:false; //出口退税税率
            $tax_data['quality_random'] = isset($v['quality_random'])?$v['quality_random']:''; //
            $tax_data['quality_level'] = isset($v['quality_level'])?$v['quality_level']:''; //
            $tax_data['update_time'] = date('Y-m-d H:i:s',time());  //修改时间
            $this->update_tax_one($v['sku'],$tax_data);           

        } else if (!empty($v['sku'])) {

            $tax_data['sku'] = trim($v['sku']);
            $tax_data['tax_rate'] = isset($v['tax_rate'])?$v['tax_rate']:false; //出口退税税率
            $tax_data['quality_random'] = isset($v['quality_random'])?$v['quality_random']:''; //
            $tax_data['quality_level'] = isset($v['quality_level'])?$v['quality_level']:''; //
            $tax_data['update_time'] = date('Y-m-d H:i:s',time());  //修改时间
            $tax_data['create_time'] = date('Y-m-d H:i:s',time()); //开发时间
            $this->insert_tax_one($tax_data); 
        } else {
            return false;
        }
    }


    public function get_tax_rate_info($sku){
        if(empty($sku)) return false;

        $where = ['sku' => $sku];
        $product_info = $this->purchase_db->where($where)->get($this->table_name)->row_array();

        return $product_info;

    }



    /**
     * 更新 信息
     * @author Jolon
     * @param $sku
     * @param $update_data
     * @return bool
     */
    public function update_tax_one($sku,$update_data){

        $result = $this->purchase_db->where('sku',$sku)->update($this->table_name,$update_data);

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