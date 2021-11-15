<?php

/**
 * Created by PhpStorm.
 * 供应商 采购员
 * User: Jolon
 * Date: 2019/01/09 0029 11:50
 */
class Supplier_buyer_model extends Purchase_model
{
    protected $table_name = 'supplier_buyer';// 数据表名称


    public function __construct(){
        parent::__construct();
    }

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
     * 获取 供应商 指定部门 的采购员
     * @author Jolon
     * @param string $supplier_code 供应商编码
     * @param int $buyer_type 采购员所属部门（1.国内仓,2.海外仓,3.FBA）
     * @return array|bool
     */
    public function get_buyer_one($supplier_code, $buyer_type = null)
    {
        if (empty($supplier_code)) return false;

        if(in_array($buyer_type,[PURCHASE_TYPE_PFB,PURCHASE_TYPE_PFH]) ) $buyer_type = PURCHASE_TYPE_INLAND;
        if($buyer_type == PURCHASE_TYPE_FBA_BIG)$buyer_type = PURCHASE_TYPE_OVERSEA;

        $where = ['supplier_code' => $supplier_code];
        if ($buyer_type !== null) {
            $where['buyer_type'] = $buyer_type;
        }

        $row = $this->purchase_db->where($where)
            ->where('status',1)
            ->get($this->table_name)
            ->row_array();

        return $row;
    }


    /**
     * 获取 供应商所有采购员
     * @author Jolon
     * @param string $supplier_code 供应商编码
     * @return array|bool
     */
    public function get_buyer_list($supplier_code)
    {
        if (empty($supplier_code)) return false;
        $this->purchase_db->reset_query();
        $list = $this->purchase_db->where('supplier_code', $supplier_code)
            ->where('status',1)
            ->order_by('buyer_type asc')
            ->get($this->table_name)
            ->result_array();

        $list = arrayKeyToColumn($list,'buyer_type');

        return $list;
    }

    public function getUserData($ids,$status){

        $this->purchase_db->reset_query();
        $list = $this->purchase_db->where_in('buyer_id', $ids)
            ->where('status',1)
            ->where('buyer_type',$status)
            ->get($this->table_name)
            ->result_array();

        $list = arrayKeyToColumn($list,'supplier_code');

        return $list;
    }



    /**
     * 根据buyer_id获取数据
     * @author Jolon
     * @param int $根据buyer_id获取数据 采购员id
     * @return array|bool
     */
    public function get_list_by_buyer_id($buyer_id){
        if( empty($buyer_id) ){
            return false;
        }
        $list = $this->purchase_db->where('buyer_id', $buyer_id)
            ->where('status',1)
            ->get($this->table_name)
            ->result_array();
        return $list;    
    }



    /**
     * @desc 获取下拉采购员列表
     * @author Jackson
     * @Date 2019-01-21 17:01:00
     * @return array()
     **/
    public function get_buyers($params = array())
    {
        $this->load->model('user/purchase_user_model');
        $buyer_list = $this->purchase_user_model->get_list();
        $buyer_list_tmp = [];
        if($buyer_list){
            foreach($buyer_list as $value){
                $value_tmp = [
                    'buyer_id' => $value['id'],
                    'buyer_name' => $value['name']
                ];
                $buyer_list_tmp[] = $value_tmp;
            }
        }
        $ids = [];
        if($buyer_list){ $ids = array_column($buyer_list,'id');}
        $builder = $this->purchase_db->select('buyer_id,buyer_name');
        if(!empty($ids)){
            $builder->where_not_in('buyer_id',$ids);
        }
        $already_buyer = $builder->group_by('buyer_id')->get($this->table_name)->result_array();
        if($already_buyer){
            foreach ($already_buyer as $value){
                    $value_tmp = [
                        'buyer_id' => $value['buyer_id'],
                        'buyer_name' => $value['buyer_name']
                    ];
                    $buyer_list_tmp[] = $value_tmp;

            }
        }
        $buyer_list = $buyer_list_tmp;
        return array(
            'list' => $buyer_list,
        );
    }

    /**
     * @desc 获取下拉采购员列表
     * @author Jackson
     * @parame array $object
     * @Date 2019-02-26 19:01:00
     * @return array()
     **/
    public function get_name_by_id(&$object = array())
    {

        if (isset($object['list']) && !empty($object['list'])) {

            $buyer = array_column($object['list'], 'buyer');
            $buyer = array_filter($buyer);
            if (empty($buyer)) {
                return;
            }

            //搜索条件
            $condition["where_in"] = ['buyer_id' => $buyer];
            //排序
            $orderBy = '';
            //查询字段
            $fields = 'buyer_id,buyer_name';
            $group_by = 'buyer_id';
            $result = $this->getDataByCondition($condition, $fields, $orderBy, $group_by);
            if (!empty($result)) {
                $tempKey = array_column($result, 'buyer_id');
                $bueryList = array_combine($tempKey, $result);

                foreach ($object['list'] as $key => &$item) {
                    if (isset($bueryList[$item['buyer']])) {
                        $item['buyer'] = $bueryList[$item['buyer']]['buyer_name'];
                    }

                }
            }

        }
    }

    /**
     * 更新 供应商采购员
     * @author Jolon
     * @param $parames
     * @param $supplier_code
     * @return array
     */
    public function update_supplier_buyer($parames,$supplier_code=''){
        $result = true;
        if($parames){
            $this->load->model('supplier/supplier_model');
            $this->load->model('user/purchase_user_model');
            $this->load->model('purchase_suggest/purchase_suggest_model');
            $this->load->model('purchase/purchase_order_model');
            $buyer_list = $this->purchase_user_model->get_list();
            $buyer_list = array_column($buyer_list,'name','id');
            $supplierInfo = $this->supplier_model->get_supplier_info($supplier_code,false);
            foreach($parames as $param){
                if(empty($param['buyer_name'])){
                    $buyer_name = isset($buyer_list[$param['buyer_id']])?$buyer_list[$param['buyer_id']]:'';
                }else{
                    $buyer_name = $param['buyer_name'];
                }
                $updateNew  = [
                    'supplier_code' => $supplier_code,
                    'supplier_name' => $supplierInfo['supplier_name'],
                    'buyer_type' => $param['buyer_type'],
                    'buyer_id' => $param['buyer_id'],
                    'buyer_name' => $buyer_name
                ];
                $updateBefore = $this->get_buyer_one($supplier_code,$param['buyer_type']);
                $res = $this->purchase_db->insert($this->table_name,$updateNew);
                if(empty($res)){
                    $result = false;
                }else{
                    // 采购需求、待采购经理审核中的采购单的采购员都要一起变更
                   $demand_number_list= $this->purchase_order_model->update_order_buyer($param['buyer_type'],$supplier_code,$param['buyer_id'],$buyer_name);                  
                   $this->purchase_suggest_model->update_suggest_buyer($param['buyer_type'],$supplier_code,$param['buyer_id'],$buyer_name,$demand_number_list);
                    if($updateBefore and $res){
                        $update_log = "修改供应商采购员，从[".$updateBefore['buyer_id']."]改为[".$updateNew['buyer_id']."]";
                        $this->purchase_db->where('id',$updateBefore['id'])->delete($this->table_name);
                        operatorLogInsert(
                            [
                                'id' => $supplier_code,
                                'type' => 'pur_' . $this->table_name,
                                'content' => '修改供应商采购员',
                                'detail' => $update_log,
                                'ext' => $supplier_code,
                            ]);
                    }
                }
            }
        }

        return array($result,$result?'操作成功':'操作失败');
    }

}