<?php
/**
 * Created by PhpStorm.
 * 产品线信息表
 * User: Jolon
 * Date: 2019/01/16 0029 17:50
 */
class Product_line_model extends Purchase_model {
    protected $table_name   = 'product_line';// 数据表名称

    /**
     * 获取产品一级产品线
     * @params:  $linelist_id   string   产品线ID
     **/
    public function get_product_top_line_data( $linelist_id)
    {
        if( empty($linelist_id))
        {
            return  array();
        }
        $product_line_data = $this->rediss->getData('product_top_line'.$linelist_id);
        if( empty($product_line_data)) {
            $top_line_data = [];
            $i = 1;
            while ($i <= 5) {
                $line_data = $this->purchase_db->from($this->table_name)->where("product_line_id", $linelist_id)->select("linelist_cn_name,linelist_parent_id,linelist_level,product_line_id")->get()->row_array();
                if (!empty($line_data)) {
                    if ($line_data['linelist_level'] == 1) {
                        $top_line_data = $line_data;
                        break;
                    } else {

                        $linelist_id = $line_data['linelist_parent_id'];
                    }

                } else {
                    break;
                }
                $i++;
            }
            $this->rediss->setData('product_top_line'.$linelist_id,json_encode($top_line_data));
            return $top_line_data;
        }else{

            return json_decode($product_line_data,true);
        }


    }

    /**
     * 获取 一级产品线 列表
     * @author Jolon
     * @param int $linelist_parent_id 产品线 父级ID
     * @return array|bool
     */
    public function get_product_line_list_first($linelist_parent_id = 0){
        $list = $this->get_product_line_list($linelist_parent_id);

        return $list;
    }

    /**
     * 获取产品线 列表
     * @author Jolon
     * @param int $linelist_parent_id 产品线 父级ID
     * @param string $product_line_name 产品线名称
     * @return array|bool
     */
    public function get_product_line_list($linelist_parent_id = null,$product_line_name = null,$fields = 'product_line_id,linelist_cn_name,linelist_level',$linelist_is_new = 1){
        $query_builder = $this->purchase_db;
        if(!is_null($linelist_parent_id)){
            $query_builder->where('linelist_parent_id',$linelist_parent_id);
        }
        if(!is_null($linelist_is_new)){
            $query_builder->where('linelist_is_new',$linelist_is_new);
        }
        if(!is_null($product_line_name)){
            $query_builder->like('linelist_cn_name',$product_line_name);
        }

        $sql_query = $query_builder->select($fields)->get_compiled_select($this->table_name);
        $sql_query_key = md5(base64_encode($sql_query));

        // 缓存查询
        $product_info = $this->rediss->getData($sql_query_key);
        if(empty($product_info)){
            $product_info = $query_builder->query($sql_query)->result_array();
            $this->rediss->setData($sql_query_key,$product_info,1800);
        }

        return $product_info;
    }

    /**
     * 获取产品线基本信息
     * @author Jolon
     * @param int $product_line_id 产品线ID
     * @return array|bool
     */
    public function get_product_line_one($product_line_id){
        if (empty($product_line_id))
            return false;
        if (is_array($product_line_id)) {      
            $product_info = $this->purchase_db->where_in('product_line_id', $product_line_id)->get($this->table_name)->result_array();
            return $product_info;
        } else {
            $where = ['product_line_id' => $product_line_id];
            $product_info = $this->purchase_db->where($where)->get($this->table_name)->row_array();

            return $product_info;
        }
    }

    /**
     * 获取产品线 名称
     * @author Jolon
     * @param int $product_line_id 产品线ID
     * @return array|bool
     */
    public function get_product_line_name($product_line_id){
          $product_line_info = $this->get_product_line_one($product_line_id);
        if (is_array($product_line_id)) {
            $linelist_cn_name=[];
            foreach ($product_line_info as $row) {
                $linelist_cn_name[]=$row['linelist_cn_name'];
            }
            $linelist_cn_name= implode(',', $linelist_cn_name);
            return $linelist_cn_name;
        } else {
            if ($product_line_info) {
                return $product_line_info['linelist_cn_name'];
            } else {
                return '';
            }
        }
    }


    /**
     * 根据ID查找所有子级id
     * @author Jaden
     * @param int $category_id 产品线ID
     * @return array|bool
     */
    public function get_all_category( $category_id ){
        if(empty($category_id)){
            return false;
        }
        if(is_array($category_id)){

            $category_idData = implode(",",$category_id);
            $category_ids = $this->rediss->getData(md5('get_all_category_'.$category_idData));
        }else {
            $category_ids = $this->rediss->getData(md5('get_all_category_' . $category_id));
        }
        if(empty($category_ids)){
            $this->purchase_db->reset_query();
            if(is_array($category_id))
            {
                $category_ids = NULL;
                $child_category = $this->purchase_db->where_in('linelist_parent_id',$category_id)->select('product_line_id,linelist_parent_id')->get($this->table_name)->result_array();
            }else {
                $category_ids = $category_id . ",";
                $child_category = $this->purchase_db->where('linelist_parent_id = "'.$category_id.'"')->select('product_line_id,linelist_parent_id')->get($this->table_name)->result_array();
            }

            if(!empty($child_category))
            {
                foreach( $child_category as $key => $val ) {
                    $category_ids .=$this->get_all_category( $val["product_line_id"] );
                }
            }

            if( is_array($category_id)){
                $this->rediss->setData(md5('get_all_category_' . $category_idData), $category_ids);
            }else {
                $this->rediss->setData(md5('get_all_category_' . $category_id), $category_ids);
            }
        }
        return $category_ids;

    }

    /**
     * （递归查询）根据 ID 查找所有父级 产品线
     * @author Jolon
     * @param int $category_id 产品线ID
     * @return array
     */
    public function get_all_parent_category( $category_id, $sort='asc' ){
        $list = [];
        if(empty($category_id)){
            return [];
        }

        $list = $this->rediss->getData(md5($category_id.'_'.$sort));
        if(empty($list)){
            $category = $this->purchase_db
                ->select('product_line_id,linelist_parent_id,linelist_cn_name')
                ->where("product_line_id",$category_id)
                ->get($this->table_name)
                ->row_array();
            if($category){
                $list_tmp = $this->get_all_parent_category($category['linelist_parent_id']);
                if($list_tmp) $list = $list_tmp;

                // 合并 父级与自己
                $list[] = ['product_line_id' => $category['product_line_id'],'product_line_name' => $category['linelist_cn_name']];

                if ($sort=='asc'){
                    $product_line_ids = array_column($list,'product_line_id');
                    array_multisort($product_line_ids,SORT_ASC,$list);//升序
                }else{
                    $product_line_ids = array_column($list,'product_line_id');
                    array_multisort($product_line_ids,SORT_DESC,$list);//降序
                }
                $this->rediss->setData(md5($category_id.'_'.$sort),$list);
            }
        }

        return $list;
    }

    /**同步产品线数据
     * @author wangliang 2019-06-11
     * @param $data
     * @return array
     */
    public function save_product_line($data){
        try{
            foreach ($data as $v) {
                $save_arr = [
                    'product_line_id'       => $v['id'],
                    'linelist_parent_id'    => $v['linelist_parent_id'],
                    'linelist_cn_name'      => $v['linelist_cn_name'],
                    'linelist_level'        => $v['linelist_level']
                ];
                $where = ['product_line_id'=>$v['id']];
                $result = $this->purchase_db->where($where)->get($this->tableName())->row_array();
                if($result){
                    $res = $this->purchase_db->where($where)->update($this->tableName(),$save_arr);
                }else{
                    $save_arr['create_time'] = date('Y-m-d H:i:s');
                    $res = $this->purchase_db->insert($this->tableName(),$save_arr);
                }
                if(!$res) throw new Exception('数据更新失败,产品线ID:'.$v['id'].'SQL:'.$this->purchase_db->last_query());
            }
            $return = ['status'=>1,'msg'=>'更新成功'];
        }catch (Exception $e){
            $return = ['status'=>0,'msg'=>$e->getMessage()];
        }
        return $return;
    }



    /**
     * 获取并缓存所有产品线
     */
    public function get_cache_product_line()
    {
        try{
            $keys = 'PURCHASE_PRODUCT_LINE_CACHE';
            $data = $this->rediss->getData($keys);
            if(empty($data)){
                $data = $this->purchase_db
                    ->select('product_line_id as id,linelist_parent_id as pid,linelist_cn_name as title')
                    ->from('product_line')
                    ->where(['linelist_is_new' => 1])
                    ->get()->result_array();
                $this->rediss->setData($keys, json_encode($data));
            }else{
                $data = json_decode($data, true);
            }
            return $data;
        }catch (ErrorException $e){}
        return [];
    }

    /**
     * 获取缓存中的数据
     */
    public function get_product_line_by_id($base = [], $id=0, $res=false)
    {
        if(count($base) == 0)$base = $this->get_cache_product_line();
        foreach ($base as $val){
            if($val['id'] == $id && $val['pid'] != 0){
                $res = $this->get_product_line_by_id($base, $val['pid']);
            }
            if($val['id'] == $id && $val['pid'] == 0)$res = $val;
            if($res)break;
        }
        return $res;
    }

    /**
     * 二维化产品线对应的一级产品线
     */
    public function cache_product_line($base=[])
    {
        $list=[];
        $master = [];
        if(count($base) == 0)$base = $this->get_cache_product_line();
        foreach ($base as $val){
            $master[$val['id']] = $val['title'];

            if($val['pid'] == 0){
                $list[$val['id']] = $val['id'];
            }else{
                $list[$val['id']] = $val['pid'];
            }
        }

        $line = $this->er_cache_product_line($list, 1);

        return ["line" => $line, "master" => $master];
    }

    private function er_cache_product_line($list, $leve)
    {
        if($leve >= 6)return $list;// 最大循环次数
        $temp = [];
        foreach ($list as $k=>$v){
            $temp[$k] = isset($list[$v])? $list[$v]:$v;
        }
        $leve ++;
        return $this->er_cache_product_line($temp, $leve);
    }
}