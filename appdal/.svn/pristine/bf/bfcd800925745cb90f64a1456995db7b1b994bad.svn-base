<?php
/**
 * Created by PhpStorm.
 * 采购单与备货单关联信息表
 * User: Jolon
 * Date: 2018/12/27 0027 11:23
 */

class Purchase_suggest_map_model extends Purchase_model {

    protected $table_name   = 'purchase_suggest_map';// 数据表名称

    public function __construct(){
        parent::__construct();

        $this->load->model('purchase_order_model','',false,'purchase');
    }

    /**
     * 获取采购单与需求单 关联记录
     * @author Jolon
     * @param string $purchase_number  采购单号
     * @param string $demand_number     备货单号
     * @param bool $is_only     查询一条
     * @return bool|array
     */
    public function get_one($purchase_number = '',$demand_number = '',$is_only = false){
        if(empty($purchase_number) and empty($demand_number)) return false;

        $query_builder = $this->purchase_db;

        if($purchase_number)    $query_builder->where('purchase_number',$purchase_number);
        if($demand_number) {
            if(is_array($demand_number)){
                $query_builder->where_in('demand_number',$demand_number);
            }else{
                $query_builder->where('demand_number',$demand_number);
            }
        }
        if($is_only){
            $results            = $query_builder->get($this->table_name)->row_array();
        }else{
            $results            = $query_builder->get($this->table_name)->result_array();
        }

        return $results;
    }

    /**
     * 获取采购单与需求单 关联记录
     * @author Jolon
     * @param string $purchase_number  采购单号
     * @param string $sku  sku
     * @param string $demand_number     备货单号
     * @param bool $is_only     查询一条
     * @return bool|array
     */
    public function get_one_by_sku($purchase_number = '',$sku = '',$demand_number = '',$is_only = false){
        if(empty($purchase_number) and empty($demand_number)) return false;

        $query_builder = $this->purchase_db->where('sku',$sku);
        if($purchase_number)    $query_builder->where('purchase_number',$purchase_number);
        if($demand_number)      $query_builder->where('demand_number',$demand_number);
        if($is_only){
            $results            = $query_builder->get($this->table_name)->row_array();
        }else{
            $results            = $query_builder->get($this->table_name)->result_array();
        }

        return $results;
    }


    /**
     * 根据需求单号 找到对应的采购单信息
     * @author Jolon
     * @param $demand_number
     * @return array
     */
    public function get_purchase_order_info($demand_number){
        $map = $this->get_one('',$demand_number,true);
        $return = [];
        if($map){
            $return['map'] = $map;
            $purchase_number = $map['purchase_number'];
            $purchase_order = $this->purchase_order_model->get_one($purchase_number,false);
            if($purchase_order){
                $return['purchase_order'] = $purchase_order;
            }
        }
        return $return;
    }

    /**
     * 根据备货单号 查询采购单信息
     * @author Manson
     * @param $demand_number_list
     * @return array
     */
    public function get_purchase_info_by_demand_number($demand_number_list)
    {
        $result = $this->purchase_db->select('a.demand_number,a.purchase_number,a.confirm_number,b.purchase_order_status')
            ->from($this->table_name. ' a')
            ->join('purchase_order b','a.purchase_number = b.purchase_number','left')
            ->where_in('a.demand_number',$demand_number_list)
            ->get()->result_array();
        $result = empty($result)?[]:array_column($result,NULL,'demand_number');
        return $result;
    }

    /**
     * 保存 采购与需求 的映射关系记录
     * @author Jolon
     * @author Jolon
     * @param string $purchase_number  采购单号
     * @param array $demand_numbers   需求单号
     * @return bool
     */
    public function save_map_order_suggest($purchase_number,$demand_numbers, $userInfo=[]){
        if(empty($purchase_number) or empty($demand_numbers) or !is_array($demand_numbers)) return false;

        $user = getActiveUserName();
        if(!empty($userInfo) && isset($userInfo['username']))$user = $userInfo['username'];
        foreach($demand_numbers as $value_number){
            $have_map = $this->get_one($purchase_number,$value_number,true);
            if($have_map){
                if($have_map['map_status'] == 2){// 2.解除绑定，执行重新绑定
                    $this->update_unbund_status($purchase_number,$value_number,1);
                }
                continue;
            } else{
                $suggest = $this->purchase_db->where('demand_number',$value_number)->get('purchase_suggest')->row_array();

                $insert_data = [
                    'purchase_number'      => $purchase_number,
                    'demand_number'        => $value_number,
                    'sku'                  => $suggest['sku'],
                    'create_user_name'     => $user,
                    'create_time'          => date('Y-m-d H:i:s'),
                    'confirm_number'       => isset($suggest['purchase_amount']) ? $suggest['purchase_amount'] : 0,
                    'purchase_total_price' => isset($suggest['purchase_total_price']) ? $suggest['purchase_total_price'] : 0,
                ];
                $this->purchase_db->insert($this->table_name,$insert_data);

                operatorLogInsert(
                    ['id'      => $suggest['demand_number'],
                     'type'    => 'purchase_suggest',
                     'content' => '生成采购单',
                     'detail'  => '生成采购单'
                    ]);

                $data = [
                    'link_id'          => $suggest['id'],
                    'link_code'        => $value_number,
                    'reject_type_id'   => 1,
                    'reiect_dismissed' => '生成采购单',
                    'reject_remark'    => ''
                ];
                rejectNoteInsert($data);
            }
        }

        return true;
    }


    /**
     * 解除 采购单与需求单绑定
     *      没设置需求单号则解除 该采购单下所有关联
     * @author Jolon
     * @param string    $purchase_number 采购单号
     * @param array     $demand_numbers  需求单号
     * @return bool
     */
    public function unbund_map_suggest($purchase_number,$demand_numbers = []){
        if(empty($demand_numbers)){
            $demand_numbers = $this->get_one($purchase_number);
            $demand_numbers = array_column($demand_numbers,'demand_number');
        }
        if(empty($purchase_number)) return false;

        if($demand_numbers){
            foreach($demand_numbers as $demand_number){
                $result = $this->update_unbund_status($purchase_number,$demand_number,2);
            }
        }

        return true;
    }
    /**
     * 驳回更新需求采购单价
     * @author harvin
     * @param array $demand_map_numbers
     * @return boolean
     * @throws Exception
     */
    public function update_suggest(array $demand_map_numbers){
         $this->load->model('product/Product_model');
        if (empty($demand_map_numbers)) {
            throw new Exception('备货单号不存在');
        }
        foreach ($demand_map_numbers as $row) {
            $suggest = $this->purchase_db
                    ->select('sku,is_drawback,purchase_amount')
                    ->where('demand_number', $row)
                    ->get('purchase_suggest')
                    ->row_array();
            if(empty($suggest)){
                throw new Exception('需求表数据不存在');
            }
         
            $sku_info = $this->Product_model->get_product_info($suggest['sku']);
            if ($suggest['is_drawback'] == PURCHASE_IS_DRAWBACK_Y) {
                $purchase_unit_price = format_two_point_price($sku_info['purchase_price'] * (1 + $sku_info['ticketed_point'] / 100)); // 含税价
            } else {
                $purchase_unit_price = format_two_point_price($sku_info['purchase_price']);
            }
            $data = [
                'purchase_unit_price' => $purchase_unit_price,
                'purchase_total_price' => $suggest['purchase_amount'] * $purchase_unit_price,
            ];
            $this->purchase_db->where('demand_number', $row)->where('sku', $suggest['sku'])->update('purchase_suggest', $data);
        }
        return true;
    }
    /**
     * 更新 采购单与需求单号关联状态
     * @author Jolon
     * @param string   $purchase_number  采购单号
     * @param string   $demand_number    需求单号
     * @param int      $map_status       关联状态，1.关联,2.解除关联
     * @return mixed
     */
    public function update_unbund_status($purchase_number,$demand_number,$map_status = 2){
        if(!is_array($demand_number))$demand_number = [$demand_number];
        if($map_status == 2){// 2.解除关联 则直接删除关联关系
            $result = $this->purchase_db->where(['purchase_number' => $purchase_number])
                ->where_in('demand_number', $demand_number)
                ->delete($this->table_name);
        }else{
            $result = $this->purchase_db->where(['purchase_number' => $purchase_number])
                ->where_in('demand_number', $demand_number)
                ->update($this->table_name,['map_status' => $map_status]);
        }
        if($result){
            if($map_status == 1){
                $content = '绑定需求与采购单';
            }else{
                $content = '解除需求与采购单绑定';
            }
            operatorLogInsert(
                ['id'      => $purchase_number,
                    'type'    => $this->table_name,
                    'content' => $content,
                    'detail'  => '需求单号：'.implode(',', $demand_number),
                    'is_show' => 2
                ]);
        }

        return $result;
    }
    /**
     * 根据采购单号获取备货单号
     * @access harvin 20195-24
     * @param type $purchase_number
     * @return array
     */
    public function get_demand_number_list($purchase_number){
        if(empty($purchase_number)){
            return [];
        }
       $suggest_map= $this->purchase_db
               ->select('demand_number')
               ->where('purchase_number',$purchase_number)
               ->get($this->table_name)
               ->result_array();
       if(empty($suggest_map)){
          return [];
       }
       return array_column($suggest_map, 'demand_number');         
    }
}