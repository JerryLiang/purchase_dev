<?php
/**
 * Created by PhpStorm.
 * 库存表
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Purchase_avg_model extends Purchase_model {
    protected $table_name   = 'purchase_avg';// 库存表

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase_order_items_model', '', false, 'purchase');
        $this->load->model('stock_model', '', false, 'warehouse');
        $this->load->model('product_model', '', false, 'product');
    }

    public function tableName() {
        return $this->table_name;
    }

    //计算每次入库的平均运费，平均采购成本
    /*
    平均运费=（入库数量*公摊运费+可用库存*原平均运费）/（入库数量+可用库存）
    公摊运费=入库时的备货单的总运费/备货单的采购数量
    原平均运费=就近一次计算出来的平均运费

    平均采购成本（不含运费）=(入库数量*采购单价+可用库存*原平均采购成本（不含运费）)/（入库数量+可用库存）
    原平均采购成本=就近一次计算出来的平均采购成本（不含运费）
    */
    public function calculating_average_freight_作废(array $row){//该方法已经作废
        $update_one = array();
        if(!empty($row)){
            $total_instock_qty = 0;
            //根据采购单号和SKU获取订单明细
            $order_items_info = $this->purchase_order_items_model->get_item($row['purchase_number'],$row['sku'],true);
            $product_info = $this->product_model->get_product_info($row['sku']);
            $instock_qty = isset($row['instock_qty']) ? $row['instock_qty'] : 0;//入库数量
            $purchase_quantity = isset($row['purchase_qty']) ? $row['purchase_qty'] : 0;//采购数量
            $freight = !empty($order_items_info['freight']) ? $order_items_info['freight'] : 0;  //总运费
            $product_base_price = !empty($order_items_info['product_base_price']) ? $order_items_info['product_base_price'] : 0;  //采购单价(不含运费)
            $purchase_unit_price = !empty($order_items_info['purchase_unit_price']) ? $order_items_info['purchase_unit_price'] : 0;  //采购单价(含运费)
            //根据SKU查可用库存
            $stock_info = $this->stock_model->get_stock_total_stock($row['sku']);
            if(!empty($stock_info) && isset($stock_info['available_stock'])){
               $available_stock = $stock_info['available_stock'];//可用库存 
           }else{
               $available_stock = 0;
           }
            //公摊运费
            if($purchase_quantity ==0 ){
                $shared_freight = 0;
            }else{
                $shared_freight = $freight/$purchase_quantity;  
            }
            
            $sku_avg_info = $this->get_info_by_sku($row['sku']);//新采购系统平均运费数据
            $post_data['sku'] = $row['sku'];
            $post_data['token'] = json_encode(stockAuth());

            $old_avg_arr = $this->get_avg_price($sku_avg_info,$old_sku_avg_info);
            $old_average_freight = $old_avg_arr['avg_freight'];   //原平均运费
            $old_avg_purchase_price = $old_avg_arr['avg_purchase_price'];//原平均采购成本
            $old_avg_price = $old_avg_arr['avg_price'];//原平均采购成本(含运费)
            
            /*
            if(!empty($sku_avg_info)){
                $old_average_freight = $sku_avg_info['avg_freight'];//原平均运费
                $old_avg_purchase_price = $sku_avg_info['avg_purchase_price']; //原平均采购成本
            }else{
                $old_average_freight = 0;   
                $old_avg_purchase_price = 0; 
            }
            */
            $total_instock_qty = $instock_qty+$available_stock;//入库数量+可用库存
            if($total_instock_qty!=0){
                $average_freight = (($instock_qty*$shared_freight)+($available_stock*$old_average_freight)) / $total_instock_qty;//计算平均运费
                $avg_purchase_price = (($instock_qty*$product_base_price)+($available_stock*$old_avg_purchase_price)) / $total_instock_qty; //平均采购成本
                $avg_price = (($instock_qty*$purchase_unit_price)+($available_stock*$old_avg_price)) / $total_instock_qty; //平均采购成本(带运费)                
            
                

                $update_data['sku'] = $row['sku'];
                $update_data['avg_freight'] = $average_freight;
                $update_data['avg_purchase_price'] = $avg_purchase_price;
                $update_data['avg_price'] = $avg_price;
                $update_data['latest_purchase_price'] = $product_info['purchase_price'];
                $update_data['update_time'] = date('Y-m-d H:i:s');
                $update_data['is_push_to_erp'] = 0;

                if(empty($sku_avg_info)){
                    $update_data['create_time'] = date('Y-m-d H:i:s');
                    $result = $this->insert_one($update_data);
                }else{
                    $result = $this->update_one($row['sku'],$update_data);
                }

                //日志
                $data_log['sku'] = $row['sku'];
                $data_log['avg_freight'] = $average_freight;
                $data_log['avg_purchase_price'] = $avg_purchase_price;
                $data_log['avg_price'] = $avg_price;
                $data_log['latest_purchase_price'] = $product_info['purchase_price'];
                $data_log['create_time'] = date('Y-m-d H:i:s');
                $this->purchase_db->insert('sku_avg_log',$data_log);

                if(!$result){
                     throw new Exception('计算平均运费出错'); 
                }    
            }

            

        }

    }


    /**
     * 根据 sku查数据
     * @author Jaden
     * @param $warehouse_code
     * @param $sku
     * @return array
     * 2019-03-15
     */
    public function get_info_by_sku($sku){
        if(empty($sku)){
            return [];
        }
        $this->purchase_db->where('sku',$sku);
        $sku_avg_info = $this->purchase_db->get($this->table_name)->row_array();
        return $sku_avg_info;
    }

    /**
     * 更新 数据
     * @author Jaden
     * @param $sku
     * @param $update_data
     * @return bool
     */
    public function update_one($sku,$update_data){

        $result = $this->purchase_db->where('sku',$sku)->update($this->table_name,$update_data);

        return $result?true:false;
    }

    /**
     * 插入数据
     * @author Jaden
     * @param $insert_data
     * @return bool
     */
    public function insert_one($insert_data){

        $result = $this->purchase_db->insert($this->table_name,$insert_data);
        return $result?true:false;
    }


    /**
     * 取最近的平均运费值
     * @author Jaden
     * @param $sku_avg_info 新系统值
     * @param $old_sku_avg_info 老系统值
     * @return array
     */
    public function get_avg_price($sku_avg_info,$old_sku_avg_info){
        $avg_purchase_price = 0;
        $avg_freight = 0;
        $avg_price = 0;
        //平均采购成本
        if( (!empty($sku_avg_info['avg_purchase_price']) && isset($sku_avg_info['avg_purchase_price'])) && (!empty($old_sku_avg_info['avg_purchase_price']) && isset($old_sku_avg_info['avg_purchase_price'])) ){
            if(strtotime($sku_avg_info['update_time'])>strtotime($old_sku_avg_info['create_time'])){
                $avg_purchase_price = $sku_avg_info['avg_purchase_price'];
            }else{
                $avg_purchase_price = $old_sku_avg_info['avg_purchase_price'];
            }         
        }else{
            if(!empty($sku_avg_info['avg_purchase_price']) && isset($sku_avg_info['avg_purchase_price'])){
               $avg_purchase_price = $sku_avg_info['avg_purchase_price']; 
            }
            if(!empty($old_sku_avg_info['avg_purchase_price']) && isset($old_sku_avg_info['avg_purchase_price'])){
               $avg_purchase_price = $old_sku_avg_info['avg_purchase_price']; 
            }
        }

        //平均运费
        if( (!empty($sku_avg_info['avg_freight']) && isset($sku_avg_info['avg_freight'])) && (!empty($old_sku_avg_info['avg_freight']) && isset($old_sku_avg_info['avg_freight'])) ){

            if(strtotime($sku_avg_info['update_time'])>strtotime($old_sku_avg_info['create_time'])){
                $avg_freight = $sku_avg_info['avg_freight'];
            }else{
                $avg_freight = $old_sku_avg_info['avg_freight'];
            }         
        }else{
            if(!empty($sku_avg_info['avg_freight']) && isset($sku_avg_info['avg_freight'])){
               $avg_freight = $sku_avg_info['avg_freight']; 
            }
            if(!empty($old_sku_avg_info['avg_freight']) && isset($old_sku_avg_info['avg_freight'])){
               $avg_freight = $old_sku_avg_info['avg_freight']; 
            }
        }

        //平均采购价格(带运费)
        if( (!empty($sku_avg_info['avg_price']) && isset($sku_avg_info['avg_price'])) && (!empty($old_sku_avg_info['avg_price']) && isset($old_sku_avg_info['avg_price'])) ){

            if(strtotime($sku_avg_info['update_time'])>strtotime($old_sku_avg_info['create_time'])){
                $avg_price = $sku_avg_info['avg_price'];
            }else{
                $avg_price = $old_sku_avg_info['avg_price'];
            }         
        }else{
            if(!empty($sku_avg_info['avg_price']) && isset($sku_avg_info['avg_price'])){
               $avg_price = $sku_avg_info['avg_price']; 
            }
            if(!empty($old_sku_avg_info['avg_price']) && isset($old_sku_avg_info['avg_price'])){
               $avg_price = $old_sku_avg_info['avg_price']; 
            }
        }


        if(empty($sku_avg_info) && !isset($old_sku_avg_info['create_time'])){
            $avg_purchase_price = 0;
            $avg_freight = 0;
            $avg_price = 0;
        }


        $avg_arr = [
            'avg_purchase_price' =>$avg_purchase_price,
            'avg_freight' =>$avg_freight,
            'avg_price' =>$avg_price,

        ];
        return $avg_arr;  
    }














}