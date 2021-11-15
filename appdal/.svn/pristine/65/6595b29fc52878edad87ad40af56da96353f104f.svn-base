<?php
/**
 * Created by PhpStorm.
 * 缺货列表控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Shortage extends MY_Controller{

    public function __construct(){
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('shortage_model');
        $this->load->model('supplier_buyer_model','supplier_buyer_model',false,'supplier');
        $this->load->model('purchase_user_model','purchase_user_model',false,'user');
        $this->load->model('stock_model','stock_model',false,'warehouse');
        $this->load->helper('common');
    }

    /**
     * 缺货列表
     /product/shortage/shortage_list
     * @author Jaden
     */
    public function shortage_list(){
        $this->load->helper('status_product');
        $params = [
            'supplier_code' => $this->input->get_post('supplier_code'), 
            'sku' => $this->input->get_post('sku'),
            'product_status' => $this->input->get_post('product_status'),
            'time_node' => $this->input->get_post('time_node'),//时间节点
            'create_id' => $this->input->get_post('create_id'),//开发人id
            'buyer_name' =>$this->input->get_post('buyer_name'),//采购员
            'think_lack_qty_order' =>$this->input->get_post('think_lack_qty_order'),//缺货数量排序
            'lack_update_time_order' =>$this->input->get_post('lack_update_time_order'),//更新时间排序
            'left_stock_start' =>$this->input->get_post('left_stock_start'),//缺货数量
            'left_stock_end' =>$this->input->get_post('left_stock_end'),//缺货数量
        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $field = 's.id,s.sku,s.think_lack_qty as left_stock,s.think_platform_info as platform_stock,s.think_statis_time as lack_update_time,p.product_name,p.product_img_url,p.supplier_name,p.product_status,p.supply_status,p.supplier_code,p.create_user_name';
        $orders_info = $this->shortage_model->get_shortage_list($params, $offset, $limit,$field);
        $shortage_list = $orders_info['value'];

        //可用库存和在途库存集合
        $skus = array_column($shortage_list, 'sku');
        $stock_list_arr = $this->stock_model->get_stock_list_by_skus($skus);
        foreach ($shortage_list as $key => $value) {
            $supplier_buyer_list = $this->supplier_buyer_model->get_buyer_list($value['supplier_code']);
            $supplier_buyer_user = '';
            $platform_stock = '';
            if(!empty($supplier_buyer_list)){
                foreach ($supplier_buyer_list as $k => $val) {
                    if(PURCHASE_TYPE_INLAND == $val['buyer_type']){
                        $buyer_type_name = '国内仓';
                    }elseif(PURCHASE_TYPE_OVERSEA == $val['buyer_type']){
                        $buyer_type_name = '海外仓';
                    }elseif(PURCHASE_TYPE_FBA_BIG == $val['buyer_type']){
                        $buyer_type_name = 'FBA大货';
                    }elseif(PURCHASE_TYPE_FBA == $val['buyer_type']){
                        $buyer_type_name = 'FBA';
                    }else{
                        $buyer_type_name = '未知';
                    }
                    
                    $supplier_buyer_user.= $buyer_type_name.':'.$val['buyer_name'].',';
                }    
            }
            $orders_info['value'][$key]['supplier_buyer_user'] = rtrim($supplier_buyer_user,',');
            $orders_info['value'][$key]['supply_status'] = !empty($value['supply_status']) ? getProductsupplystatus($value['supply_status']) : '';

            if (!empty($value['platform_stock'])){
                $platform_stock_list = json_decode($value['platform_stock'],true);
                foreach ($platform_stock_list as $k => $val) {
                    $platform_stock.= $k.'('.$val.')'.',';
                }
            }

            $orders_info['value'][$key]['platform_stock'] = substr($platform_stock,0,-1);
            //在途
            $orders_info['value'][$key]['on_the_way_number'] = isset($stock_list_arr[$value['sku']]['on_way_stock'])?$stock_list_arr[$value['sku']]['on_way_stock']:0;
            //可用
            $orders_info['value'][$key]['available_stock'] = isset($stock_list_arr[$value['sku']]['available_stock'])?$stock_list_arr[$value['sku']]['available_stock']:0;


        }
        $orders_info['key'] = array('ID','图片','产品名称','默认供应商名称','SKU','货源状态','产品状态','在途库存','可用库存','缺货数量','开发员','采购员');
        $drop_down_box['product_status'] = getProductStatus();//产品状态
        //采购员
        $user_list = $this->purchase_user_model->get_list();
        $drop_down_box['purchase_user_list'] = array_column($user_list, 'name','id');
        //开发员
        $developer_list = $this->purchase_user_model->get_user_developer_list();
        if(!empty($developer_list) and is_array($developer_list)){
            $drop_down_box['developer_list'] = array_column($developer_list, 'name','id');       
        }
        $orders_info['drop_down_box'] = $drop_down_box;
        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        $page_data = $orders_info['page_data'];
        unset($orders_info['page_data']);

        $this->success_json($orders_info,$page_data);
    }



    /**
     * 缺货列表导出
     /product/shortage/shortage_export
     * @author Jaden
     */
     public function shortage_export(){
        set_time_limit(0);
        $this->load->helper('status_product');
        $ids = $this->input->get_post('ids');
        if(!empty($ids)){
            $params['ids']   = $ids;
        }else{
            $params = [
                'supplier_code' => $this->input->get_post('supplier_code'), 
                'sku' => $this->input->get_post('sku'),
                'product_status' => $this->input->get_post('product_status'),
                'time_node' => $this->input->get_post('time_node'),//时间节点
                'create_user_name' => $this->input->get_post('create_user_name'),//开发人员
                'buyer_name' =>$this->input->get_post('buyer_name'),//采购员
            ];
        }

        $field = 's.id,s.sku,s.think_lack_qty as left_stock,s.think_platform_info as platform_stock,s.think_statis_time as lack_update_time,
        p.product_name,p.product_img_url,p.supplier_name,p.product_status,p.supply_status,p.supplier_code,p.create_user_name';
        $orders_info = $this->shortage_model->get_shortage_list($params,'','',$field,true);
        $shortage_list = $orders_info['value'];
        $tax_list_tmp = [];
        if($shortage_list){
            foreach($shortage_list as $v_value){
                if(!empty($v_value['supplier_code'])){
                    $supplier_buyer_list = $this->supplier_buyer_model->get_buyer_list($v_value['supplier_code']);   
                }
                $supplier_buyer_user = '';
                if(!empty($supplier_buyer_list)){
                    foreach ($supplier_buyer_list as $k => $val) {
                        if(PURCHASE_TYPE_INLAND == $val['buyer_type']){
                            $buyer_type_name = '国内仓';
                        }elseif(PURCHASE_TYPE_OVERSEA == $val['buyer_type']){
                            $buyer_type_name = '海外仓';
                        }elseif(PURCHASE_TYPE_FBA_BIG == $val['buyer_type']){
                            $buyer_type_name = 'FBA大货';
                        }elseif(PURCHASE_TYPE_FBA == $val['buyer_type']){
                            $buyer_type_name = 'FBA';
                        }else{
                            $buyer_type_name = '未知';
                        }
                        $supplier_buyer_user.= $buyer_type_name.':'.$val['buyer_name'].',';
                    }    
                }
                
                //查在途和可用库存
                $stock_list_arr = $this->stock_model->get_stock_total_stock($v_value['sku']);
                $v_value_tmp                       = [];
                $v_value_tmp['product_img_url']                = erp_sku_img_sku($v_value['product_img_url']);
                $v_value_tmp['product_name']  = $v_value['product_name'];
                $v_value_tmp['supplier_name']  = $v_value['supplier_name'];
                $v_value_tmp['sku']                = $v_value['sku'];
                $v_value_tmp['supply_status']       = !empty($v_value['supply_status']) ? getProductsupplystatus($v_value['supply_status']) : '';
                $v_value_tmp['product_status']       = !empty($v_value['product_status']) ? getProductStatus($v_value['product_status']) : '';//产品状态

                $v_value_tmp['on_the_way_number']  = !empty($stock_list_arr)?$stock_list_arr['on_way_stock']:0;
                $v_value_tmp['available_stock']  = !empty($stock_list_arr)?$stock_list_arr['available_stock']:0;
                $v_value_tmp['left_stock']  = $v_value['left_stock'];
                $v_value_tmp['create_user_name']       = $v_value['create_user_name'];
                $v_value_tmp['supplier_buyer_user']       = $supplier_buyer_user;
                $tax_list_tmp[] = $v_value_tmp;
            }
        }
        $this->success_json($tax_list_tmp);

     }



}