<?php

/**
 * Created by PhpStorm.
 * 供应商 结算方式
 * User: Jackson
 * Date: 2019/01/09 0029 11:50
 */
class Supplier_settlement_model extends Purchase_model
{
    protected $table_name = 'supplier_settlement';// 数据表名称

    /**
     * 获取 供应商结算方式-一般用于code和name转换
     * @author Jolon
     * @param int    $settlement_code 结算方式编码
     * @param string $settlement_name 结算方式名称
     * @return mixed
     */
    public function get_settlement_one($settlement_code = null,$settlement_name = null){
        if(empty($settlement_code) and empty($settlement_name)) return [];

        if($settlement_code){
            $this->purchase_db->where('settlement_code',$settlement_code);
        }else{
            $this->purchase_db->where('settlement_name',$settlement_name);
        }
        $row = $this->purchase_db->get($this->table_name)->row_array();

        return $row;
    }

    /**
     * @desc 获取供应商-所有结算方式下拉信息
     * @author Jackson
     * @param $params
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_settlement($params = [])
    {
        //搜索条件
        $condition = [];
        if(isset($params['settlement_code'])){
            $condition['settlement_code'] = $params['settlement_code'];
        }
        if(isset($params['settlement_name'])){
            $condition['settlement_name'] = $params['settlement_name'];
        }
        //状态
        $condition['settlement_status'] = 1;
        //排序
        $orderBy = '';
        //查询字段
        $fields = 'settlement_name,settlement_code,settlement_status,settlement_percent';
        $result = $this->getDataByCondition($condition, $fields, $orderBy);

        return array(
            'list' => $result,
        );
    }

    /**
     * @desc 获取供应商-结算方式（包含一二级 组合方式）
     * @author Jackson
     * @param $params
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_settlement_combine_convert()
    {
        //下拉列表结算方式
        $settlement = $this->get_settlement_combine();
        $disabled_map = $this->get_supplier_settlement_disabled();
        $settlement_tmp = [];
        $first_list = $settlement['first'];
        foreach($first_list as $first => $first_value){
            $children = isset($settlement['second'][$first])?$settlement['second'][$first]:[];
            if($children){
                $children_tmp = [];
                foreach($children as $second => $second_value){
                    $children_tmp[] = [
                        'value'  => $second,
                        'label' => $second_value,
                        'disabled' => $disabled_map[$second]??false
                    ];
                }
                $children = $children_tmp;
            }
            $settlement_tmp[] = [
                'value'  => $first,
                'label' => $first_value,
                'children' => $children,
                'disabled' => $disabled_map[$first]??false
            ];
        }
        $settlement = $settlement_tmp;

        return $settlement;
    }

    /**
     * @desc 获取供应商-结算方式（包含一二级 组合方式）
     * @author Jackson
     * @param $params
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_settlement_combine()
    {
        $list = $this->purchase_db->get($this->table_name)->result_array();

        $settlement_list        = [];
        $settlement_list_first  = [];
        if($list){
            foreach($list as $value){// 找到所有一级分类
                if($value['parent_id'] == 0){
                    $settlement_list_first[$value['settlement_code']] = $value['settlement_name'];
                }
            }
            $settlement_list['first'] = $settlement_list_first;

            // 根据一级找二级分类
            foreach($settlement_list_first as $first_id => $first_name){
                foreach($list as $value){
                    if($value['parent_id'] == $first_id){
                        $settlement_list['second'][$first_id][$value['settlement_code']] = $value['settlement_name'];
                    }
                }
            }
        }

        return $settlement_list;
    }

    /**
     * 通过 settlement_code 集合 获取 settlement_code => settlement_name 集合
     * settlement_code => settlement_name
     * @author liwuxue
     * @date 2019/2/14 17:04
     * @param
     * @return mixed
     * @throws Exception
     */
    public function get_code2name_list(array $codes)
    {
        $data = [];
        $codes = array_unique(array_filter($codes));
        if (!empty($codes)) {
            $rows = $this->purchase_db->select("settlement_code,settlement_name")
                ->where_in("settlement_code", $codes)
                ->get($this->table_name)
                ->result_array();
            $data = is_array($rows) ? array_column($rows, "settlement_name", "settlement_code") : [];
        }
        return $data;
    }

    /*
     *查询结算方式集合  
     * @author Jaden
     * @return array
    */
    public function get_code_by_name_list(){
        $data = [];
        $rows = $this->purchase_db->select("settlement_code,settlement_name")
            ->get($this->table_name)
            ->result_array();
        $data = is_array($rows) ? array_column($rows, "settlement_name", "settlement_code") : [];
        
        return $data;
    }

    /**
     * 获取指定供应商结算方法（一级和二级）
     * @author harvin
     * @param string $code 
     * @return mixed 
     */
    public function get_supplier_settlement_name($code){
        if(empty($code)){
            return '';
        }
        //获取二级结算方式
       $second_name=$this->purchase_db
               ->select('settlement_name,parent_id')
               ->where('settlement_code',$code)
               ->get('supplier_settlement')
               ->row_array();
       if(!empty($second_name)){
            $first_name=$this->purchase_db
               ->select('settlement_name,parent_id')
               ->where('settlement_code',$second_name['parent_id'])
               ->get('supplier_settlement')
               ->row_array(); 
            return $first_name['settlement_name'].'/'.$second_name['settlement_name'];
       }else{
           return '';
       }     
    }

    /**
     * 是否置灰
     * @author Manson
     * @return array
     */
    public function get_supplier_settlement_disabled()
    {
        $map = [];
        $result = $this->purchase_db->select("settlement_code,is_ash")
            ->get($this->table_name)
            ->result_array();
        if (!empty($result)){
            foreach ($result as $key => $item){
                if ($item['is_ash'] == 1){
                    $disabled = true;
                }else{
                    $disabled = false;
                }
                $map[$item['settlement_code']] = $disabled;
            }
        }

        return $map;
    }

    /**
     * 获取供应商的结算方式（数值+中文）
     * @param $supplier_codes
     * @return array
     */
    public function get_supplier_settlement_all($supplier_codes){
        // 获取结算方式
        $down_settlement = $this->get_settlement();
        $down_settlement = array_column($down_settlement['list'],'settlement_name','settlement_code');
        $payment_info = $this->purchase_db->select("supplier_code,group_concat(supplier_settlement) as supplier_settlement")
            ->from('pur_supplier_payment_info')
            ->where_in('supplier_code', $supplier_codes)
            ->where('is_del', 0)
            ->group_by('supplier_code')
            ->get()
            ->result_array();
        $payment_info = array_column($payment_info, NULL, 'supplier_code');
        foreach ($payment_info as $key => &$item){
            if (!empty($item['supplier_settlement'])){
                $supplier_settlement_list = array_unique(explode(',',$item['supplier_settlement']));
                $supplier_settlement_list_cn = [];
                foreach($supplier_settlement_list as $sett_value){
                    if(!isset($down_settlement[$sett_value])) continue;
                    $supplier_settlement_list_cn[] = $down_settlement[$sett_value];
                }
                $item['supplier_settlement'] = implode(',',$supplier_settlement_list);
                $item['supplier_settlement_cn'] = implode(',',$supplier_settlement_list_cn);
            }
        }

        return $payment_info;
    }

    
    
}