<?php

/**
 * Created by PhpStorm.
 * 供应商 产品线
 * User: Jolon
 * Date: 2019/01/09 0029 11:50
 */
class Supplier_product_line_model extends Purchase_model
{
    protected $table_name = 'product_line';// 数据表名称
    protected $_level_table_name = 'supplier_product_line';//产品线级别主表

    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function table_nameName()
    {
        return $this->table_name;
    }

    /**
     * 返回表名
     * MY_Model 中的 filterNotExistFields() 方法需要
     * @return string
     */
    public function level_table_nameName()
    {
        return $this->_level_table_name;
    }

    /**
     * 获取 供应商 产品线
     * @author Jolon
     * @param string $supplier_code 供应商编码
     * @return array|bool
     */
    public function get_product_line_one($supplier_code)
    {
        if (empty($supplier_code)) return false;

        $where = ['supplier_code' => $supplier_code];

        $row = $this->purchase_db->where($where)
            ->get($this->_level_table_name)
            ->row_array();

        return $row;
    }

    /**
     * @desc 更新数据
     * @author Jackson
     * @parame array $parames  参数
     * @Date 2019-01-26 17:01:00
     * @return array()
     **/
    public function update_product_line($parames=array())
    {

        //覆盖表
        $this->table_name = $this->_level_table_name;

        //更新条件
        $condition = [];
        if (!empty($parames)) {

            //更新数据
            $data = $parames;
            if (isset($data['supplier_code']) && $data['supplier_code']) {
                $condition['supplier_code'] = $data['supplier_code'];
            } else {
                return array(false, "产品线ID不能为空");
            }

            //增加用户信息
            $data['modify_user_name'] = '';//用户名
            analyzeUserInfo($data);
            $data['modify_time'] = date("Y-m-d H:i:s");

            //过虑不存在字段
            $data = $this->filterNotExistFields($data);

            //更新数据
            unset($data['supplier_code']);
            unset($data['id']);

            //获取更新前的数据
            $updateBefore = $this->getDataByCondition($condition);
            $result = $this->update($data, $condition);

            //记录日志
            if ($result) {
                $ids = array_column($updateBefore, 'id');
                if (is_array($ids)) {

                    //删除不必要字段
                    $delFilter = array('create_time');
                    foreach ($parames as $key => $val) {
                        if (in_array($key, $delFilter)) {
                            unset($parames[$key]);
                        }
                    }

                    //解析更新前后数据
                    $changDatas = $this->checkChangData($updateBefore, $parames, $delFilter);
                    if (!empty($changDatas)) {
                        foreach ($ids as $key => $_id) {

                            operatorLogInsert(
                                [
                                    'id' => $_id,
                                    'type' => 'pur_' . $this->_level_table_name,
                                    'content' => '供应商产品线主表更新',
                                    'detail' => $changDatas[$_id],
                                ]);

                            tableChangeLogInsert(
                                ['record_number' => $_id,
                                    'table_name' => 'pur_' . $this->_level_table_name,
                                    'change_type' => 2,
                                    'change_content' => $changDatas[$_id],
                                ]);
                        }
                    }
                }

            }

            return $this->getAffectedRows() > 0 ? array(true, "更新成功") : array(false, "操作失败:" . $this->getAffectedRows());

        }
        return array(false, "参数不能为空");
    }


    /**
     * 添加产品线
     * @author Jolon
     * @param $data
     * @return array
     */
    public function insert_product_line($data){
        if(isset($data['id'])) unset($data['id']);

        $this->table_name = $this->_level_table_name;//覆盖表

        $data = $this->filterNotExistFields($data);
        $result = $this->insert($data);
        if($result){
            return array(true, "添加成功");
        }else{
            return array(true, "添加失败");
        }
    }


    /** 供应商审核完成生成产品线数据
     * @param $supplier_code 供应商编码
     * @return array
     */
    public function generate_product_line($supplier_code){
        $result = $this->get_product_line_one($supplier_code);
        if($result){
            return ['code' => 1,'msg'=>'已存在产品线记录'];
        }

        //如果不存在产品线记录  产品线=与该供应商绑定的-创建时间最早的那个sku的产品线
        $first_product = $this->purchase_db->select('product_line_id,supplier_name,create_user_name')
                                            ->where('supplier_code',$supplier_code)
                                            ->where('product_line_id > ',0)
                                            ->order_by('create_time','asc')
                                            ->get('pur_product')
                                            ->row_array();

        if(empty($first_product['product_line_id'])){
            return ['code' => 1,'msg' => '不存在产品线记录'];
        }

        $this->load->model('product/Product_line_model');
        //只获取当前产品线所有父级产品线（单个产品线父级产品线唯一 ）
        $all_line = $this->Product_line_model->get_all_parent_category((int)$first_product['product_line_id']);
        if(empty($all_line)){
            return ['code'=>1,'msg'=>'产品线数据未找到'];
        }

        $product_line_arr = [];//产品线数组 目前最多三级
        foreach ($all_line as $key => $value){
            $product_line_arr[] = isset($value['product_line_id']) ? $value['product_line_id'] : '';
        }

        $insert_data = [
            'supplier_code'         => $supplier_code,
            'supplier_name'         => $first_product['supplier_name'],
            'first_product_line'    => isset($product_line_arr[0]) ? $product_line_arr[0] : '',
            'second_product_line'   => isset($product_line_arr[1]) ? $product_line_arr[1] : '',
            'third_product_line'    => isset($product_line_arr[2]) ? $product_line_arr[2] : '',
            'status'                => 1,
            'create_user_name'      => $first_product['create_user_name'],
            'create_time'           => date('Y-m-d H:i:s'),
            'modify_user_name'      => getActiveUserName() ? getActiveUserName() : 'admin',
        ];

        list($status, $msg) = $this->insert_product_line($insert_data);
        if($status === true ){
            $return =  ['code' => 1,'msg' => $msg];
        }else{
            $return =  ['code' => 0,'msg' => $msg];
        }
        $log = ['insert_data'=>$insert_data,'res'=>$return];
        //操作日志
        operatorLogInsert([
            'id'            => $supplier_code,
            'type'          => 'generate_supplier_product_line',
            'content'       => '新增供应商产品线',
            'detail'        => json_encode($log),
        ]);

        return $return;
    }

}