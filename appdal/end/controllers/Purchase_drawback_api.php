<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * Created by PhpStorm.
 * 定时任务执行是否退税和产品表是否退税不一致的需求单和采购单
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Purchase_drawback_api extends MY_API_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('product/product_model');
        $this->load->model('purchase_suggest/purchase_suggest_model');
            
    }
    

    /**
     * 查询 未完结的需求单是否退税是否和产品表的一致
     * @author Jaden
     /purchase_drawback_api/get_purchase_sug_drawback
     */
    public function get_purchase_sug_drawback(){
        //未完结的需求单
        set_time_limit(0);
        try {
            $where = 'a.suggest_status=1 AND a.purchase_type_id=2 AND a.is_drawback!=b.is_drawback';
            $purchase_sug_drawback_list = $this->db->select('a.demand_number,a.sku')
                        ->from('purchase_suggest as a')
                        ->join('pur_product as b', 'a.sku=b.sku', 'left')
                        ->limit(50)
                        ->where($where)
                        ->order_by('a.create_time DESC')
                        ->get()
                        ->result_array();
            if(!empty($purchase_sug_drawback_list)){
                $success_list = array();
                foreach ($purchase_sug_drawback_list as $key => $value) {
                    $change_data = array();
                    $product_info = $this->product_model->get_product_info($value['sku']);
                    if(empty($product_info)){
                        continue;
                    }
                    $change_data['new_supplier_price'] = $product_info['purchase_price'];
                    $change_data['new_supplier_code'] = $product_info['supplier_code'];
                    $change_data['new_supplier_name'] = $product_info['supplier_name'];
                    $change_data['new_ticketed_point'] = $product_info['ticketed_point'];
                    $change_data['is_drawback'] = $product_info['is_drawback'];
                    $result = $this->purchase_suggest_model->change_suggest_purchase_price($value['sku'], $change_data);
                    if($result['msg'] == '成功'){
                        $success_list[] = $value['demand_number'].'_'.$value['sku'];
                    }
                }
                if(!empty($success_list)){
                    var_dump(implode(',', array_unique($success_list)).'更新完成');
                }
            }else{
                throw new Exception('暂无数据');
            }

        } catch (Exception $exc) {
            echo $exc->getMessage();
        }            
    }


    /**
     * 查询 已生成采购单，但是在等待到货之前状态的需求单
     * @author Jaden
     /purchase_drawback_api/get_purchase_sug_list
     */
    public function get_purchase_sug_list(){
        //未完结的需求单
        set_time_limit(0);
        try {
            $where = 'c.purchase_order_status in(2,3) AND d.is_drawback!=b.is_drawback AND d.purchase_type_id=2';
            $purchase_sug_drawback_status_list = $this->db->select('a.demand_number,a.sku')
                        ->from('purchase_suggest_map as a')
                        ->join('product as b', 'a.sku=b.sku', 'left')
                        ->join('purchase_order as c', 'c.purchase_number=a.purchase_number', 'left')
                        ->join('purchase_suggest as d', 'd.demand_number=a.demand_number', 'left')
                        ->limit(50)
                        ->where($where)
                        ->order_by('a.create_time DESC')
                        ->get()
                        ->result_array();           
            if(!empty($purchase_sug_drawback_status_list)){
                $success_list = array();
                foreach ($purchase_sug_drawback_status_list as $key => $value) {
                    $change_data = array();
                    $product_info = $this->product_model->get_product_info($value['sku']);
                    if(empty($product_info)){
                        continue;
                    }
                    $change_data['new_supplier_price'] = $product_info['purchase_price'];
                    $change_data['new_supplier_code'] = $product_info['supplier_code'];
                    $change_data['new_supplier_name'] = $product_info['supplier_name'];
                    $change_data['new_ticketed_point'] = $product_info['ticketed_point'];
                    $change_data['is_drawback'] = $product_info['is_drawback'];
                    $result = $this->purchase_suggest_model->change_suggest_purchase_price($value['sku'], $change_data);
                    if($result['msg'] == '成功'){
                        $success_list[] = $value['demand_number'].'_'.$value['sku'];
                    }
                }
                if(!empty($success_list)){
                    var_dump(implode(',', array_unique($success_list)).'更新完成');
                }
            }else{
                throw new Exception('暂无数据');
            }

        } catch (Exception $exc) {
            echo $exc->getMessage();
        }            
    }



    /**
     * 查询 采购单等待到货之前的采购单
     * @author Jaden
     /purchase_drawback_api/get_purchase_order_list
     */
    public function get_purchase_order_list(){
        set_time_limit(0);
        try {
            $where = 'c.purchase_order_status in(2,3) AND c.is_drawback!=b.is_drawback AND c.purchase_type_id=2';
            $purchase_order_list = $this->db->select('a.purchase_number,a.sku')
                        ->from('purchase_order_items as a')
                        ->join('product as b', 'a.sku=b.sku', 'left')
                        ->join('purchase_order as c', 'c.purchase_number=a.purchase_number', 'left')
                        ->limit(50)
                        ->where($where)
                        ->order_by('a.create_time DESC')
                        ->get()
                        ->result_array();                    
            if(!empty($purchase_order_list)){
                $success_list = array();
                
                foreach ($purchase_order_list as $key => $value) {
                    $change_data = array();
                    $is_drawback_arr = array();
                    $product_info = $this->product_model->get_product_info($value['sku']);
                    if(empty($product_info)){
                        continue;
                    }
                    /* 查询如果同一采购单号，有退税的也有不退税的，不做更新 */
                    $order_list = $this->db->select('pa.purchase_number,pa.sku,pb.is_drawback')
                                ->from('purchase_order_items as pa')
                                ->join('product as pb', 'pa.sku=pb.sku', 'left')
                                ->join('purchase_order as pc', 'pa.purchase_number=pc.purchase_number', 'left')
                                ->where('pa.purchase_number="'.$value['purchase_number'].'"')
                                ->order_by('pa.create_time DESC')
                                ->get()
                                ->result_array();          
                    $is_drawback_arr = array_unique( array_column($order_list, 'is_drawback') );
                    if(count($is_drawback_arr) >1){
                        continue;    
                    }

                    $change_data['new_supplier_price'] = $product_info['purchase_price'];
                    $change_data['new_supplier_code'] = $product_info['supplier_code'];
                    $change_data['new_supplier_name'] = $product_info['supplier_name'];
                    $change_data['new_ticketed_point'] = $product_info['ticketed_point'];
                    $change_data['is_drawback'] = $product_info['is_drawback'];
                    $result = $this->purchase_suggest_model->change_suggest_purchase_price($value['sku'], $change_data);
                    if($result['msg'] == '成功'){
                        $success_list[] = $value['purchase_number'].'_'.$value['sku'];
                    }
                }
                if(!empty($success_list)){
                    var_dump(implode(',', array_unique($success_list)).'更新完成');
                }else{
                    throw new Exception('暂无数据-11'); 
                }
            }else{
                throw new Exception('暂无数据');
            }

        } catch (Exception $exc) {
            echo $exc->getMessage();
        }            
    }





     /**
     * 查询 未完结的需求单价格同步定时任务
     * @author Jaden
     /purchase_drawback_api/chang_purchase_suggest_price_list
     */
    public function chang_purchase_suggest_price_list(){
        set_time_limit(0);
        try {
            //未完结的需求单(不退税)
            $where = 'a.suggest_status=1 AND a.is_drawback=0 AND a.purchase_unit_price != ROUND(b.purchase_price,2)';
            $purchase_sug_no_drawback_list = $this->db->select('a.demand_number,a.sku,a.purchase_amount,a.purchase_unit_price,a.purchase_total_price,a.purchase_unit_price,b.purchase_price')
                        ->from('purchase_suggest as a')
                        ->join('pur_product as b', 'a.sku=b.sku', 'left')
                        ->limit(20)
                        ->where($where)
                        ->order_by('a.create_time DESC')
                        ->get()
                        ->result_array();
                     
            //未完结的需求单(退税)                 
            $is_dr_where = 'a.suggest_status=1 AND a.is_drawback=1 AND a.purchase_unit_price != ROUND(b.purchase_price * (1 + b.ticketed_point / 100),2)';
            $purchase_sug_is_drawback_list = $this->db->select('a.demand_number,a.sku,a.purchase_amount,a.purchase_unit_price,a.purchase_total_price,b.purchase_price,b.ticketed_point')
                        ->from('purchase_suggest as a')
                        ->join('pur_product as b', 'a.sku=b.sku', 'left')
                        ->limit(20)
                        ->where($is_dr_where)
                        ->order_by('a.create_time DESC')
                        ->get()
                        ->result_array();

            if(!empty($purchase_sug_no_drawback_list) || !empty($purchase_sug_is_drawback_list)){
                $success_list = array();
                //不退税的需求单
                if(!empty($purchase_sug_no_drawback_list)){
                    foreach ($purchase_sug_no_drawback_list as $key => $value) {
                        $change_data = array();
                        $chang_data_log = $chang_data_log_add = array();
                        $product_info = $this->product_model->get_product_info($value['sku']);
                        if(empty($product_info)){
                            continue;
                        }
                        $change_data['purchase_unit_price'] = format_two_point_price($value['purchase_price']);
                        $change_data['purchase_total_price'] = format_two_point_price($value['purchase_price']*$value['purchase_amount']);
                        $this->db->where('demand_number', $value['demand_number'])->where('sku', $value['sku'])->update('purchase_suggest',$change_data);
                        $success_list[] = $value['demand_number'].'_'.$value['sku'];

                        //日志
                        $chang_data_log['demand_number'] = $value['demand_number'];
                        $chang_data_log['sku'] = $value['sku'];
                        $chang_data_log['old_purchase_unit_price'] = $value['purchase_unit_price'];
                        $chang_data_log['old_purchase_total_price'] = $value['purchase_total_price'];
                        $chang_data_log['new_purchase_unit_price'] = format_two_point_price($value['purchase_price']);
                        $chang_data_log['new_purchase_total_price'] = format_two_point_price($value['purchase_price']*$value['purchase_amount']);

                        $chang_data_log_add['purchase_number'] = $value['demand_number'];
                        $chang_data_log_add['sku'] = $value['sku'];
                        $chang_data_log_add['chang_data_content'] = json_encode($chang_data_log);
                        $chang_data_log_add['create_time'] = date('Y-m-d H:i:s');
                        if(!empty($chang_data_log_add)){
                            $this->db->insert('chang_price_log', $chang_data_log_add);    
                        }
                        

                    }    
                }

                //退税的需求单
                if(!empty($purchase_sug_is_drawback_list)){
                    foreach ($purchase_sug_is_drawback_list as $dr_key => $dr_value) {
                        $change_data_dr = array();
                        $chang_data_dr_log = $chang_data_log_dr_add = array();
                        $product_info = $this->product_model->get_product_info($dr_value['sku']);
                        if(empty($product_info)){
                            continue;
                        }
                        $purchase_unit_price = format_two_point_price($dr_value['purchase_price']*(1+($dr_value['ticketed_point']?$dr_value['ticketed_point']/100:0)));

                        $change_data_dr['purchase_unit_price'] = $purchase_unit_price; 
                        $change_data_dr['purchase_total_price'] = format_two_point_price($purchase_unit_price*$dr_value['purchase_amount']);
                        $this->db->where('demand_number', $dr_value['demand_number'])->where('sku', $dr_value['sku'])->update('purchase_suggest',$change_data_dr);
                        $success_list[] = $dr_value['demand_number'].'_'.$dr_value['sku'];

                        //日志
                        $chang_data_dr_log['demand_number'] = $dr_value['demand_number'];
                        $chang_data_dr_log['sku'] = $dr_value['sku'];
                        $chang_data_dr_log['old_purchase_unit_price'] = $dr_value['purchase_unit_price'];
                        $chang_data_dr_log['old_purchase_total_price'] = $dr_value['purchase_total_price'];
                        $chang_data_dr_log['new_purchase_unit_price'] = $purchase_unit_price;
                        $chang_data_dr_log['new_purchase_total_price'] = format_two_point_price($purchase_unit_price*$dr_value['purchase_amount']);

                        $chang_data_log_dr_add['purchase_number'] = $dr_value['demand_number'];
                        $chang_data_log_dr_add['sku'] = $dr_value['sku'];
                        $chang_data_log_dr_add['chang_data_content'] = json_encode($chang_data_dr_log);
                        $chang_data_log_dr_add['create_time'] = date('Y-m-d H:i:s');
                        if(!empty($chang_data_log_dr_add)){
                            $this->db->insert('chang_price_log', $chang_data_log_dr_add);    
                        }


                    }    
                }
                if(!empty($success_list)){
                    var_dump(implode(',', array_unique($success_list)).'需求单更新完成');
                }
            }else{
                throw new Exception('需求单暂无数据');
            }

        } catch (Exception $exc) {
            echo $exc->getMessage();
        }            
    }




    /**
     * 查询 采购单价格同步定时任务
     * @author Jaden
     /purchase_drawback_api/chang_purchase_order_price_list
     */
    public function chang_purchase_order_price_list(){
        
        set_time_limit(0);
        try {
            //采购单(不退税)
            $where = 'su.is_drawback=0 AND oi.product_base_price != ROUND(pd.purchase_price,2)';
            $purchase_order_no_drawback_list = $this->db->select('oi.purchase_number,oi.sku,oi.confirm_amount,oi.product_base_price,oi.purchase_unit_price,pd.purchase_price,su.demand_number,su.purchase_amount')
                        ->from('purchase_suggest_map as smp')
                        ->join('purchase_order as od','od.purchase_number=smp.purchase_number','left')
                        ->join('purchase_order_items as oi','oi.purchase_number=smp.purchase_number AND oi.sku = smp.sku','left')
                        ->join('purchase_suggest as su','su.demand_number=smp.demand_number','left')
                        ->join('product as pd','pd.sku=oi.sku','left')
                        ->limit(20)
                        ->where($where)
                        ->where_in('od.purchase_order_status', [PURCHASE_ORDER_STATUS_WAITING_QUOTE])
                        ->order_by('oi.create_time DESC')
                        ->get()
                        ->result_array();
            
            //采购单(退税)                 
            $is_dr_where = 'su.is_drawback=1 AND ( oi.purchase_unit_price != ROUND(pd.purchase_price * (1 + pd.ticketed_point / 100),2) or oi.product_base_price != ROUND(pd.purchase_price,2) )';
            $purchase_order_is_drawback_list = $this->db->select('oi.purchase_number,oi.sku,oi.confirm_amount,oi.product_base_price,oi.purchase_unit_price,oi.purchase_unit_price,pd.purchase_price,pd.ticketed_point,su.demand_number,su.purchase_amount')
                        ->from('purchase_suggest_map as smp')
                        ->join('purchase_order as od','od.purchase_number=smp.purchase_number','left')
                        ->join('purchase_order_items as oi','oi.purchase_number=smp.purchase_number AND oi.sku = smp.sku','left')
                        ->join('purchase_suggest as su','su.demand_number=smp.demand_number AND su.sku=smp.sku','left')
                        ->join('product as pd','pd.sku=oi.sku','left')
                        ->limit(20)
                        ->where($is_dr_where)
                        ->where_in('od.purchase_order_status', [PURCHASE_ORDER_STATUS_WAITING_QUOTE])
                        ->order_by('oi.create_time DESC')
                        ->get()
                        ->result_array();       
            if(!empty($purchase_order_no_drawback_list) || !empty($purchase_order_is_drawback_list)){
                $success_list = array();
                //不退税的需求单
                if(!empty($purchase_order_no_drawback_list)){
                    foreach ($purchase_order_no_drawback_list as $key => $value) {
                        $change_data = array();
                        $change_data_sug = array();
                        $chang_data_log = $chang_data_log_add = array();
                        $product_info = $this->product_model->get_product_info($value['sku']);
                        if(empty($product_info)){
                            continue;
                        }
                        $change_data['product_base_price'] = format_two_point_price($value['purchase_price']);
                        $change_data['purchase_unit_price'] = format_two_point_price($value['purchase_price']);
                        //修改采购单价格
                        $this->db->where('purchase_number', $value['purchase_number'])->where('sku', $value['sku'])->update('purchase_order_items',$change_data);
                        //修改需求单价格
                        $change_data_sug['purchase_unit_price'] = format_two_point_price($value['purchase_price']);
                        $change_data_sug['purchase_total_price'] = format_two_point_price($value['purchase_price']*$value['purchase_amount']);
                        $this->db->where('demand_number', $value['demand_number'])->where('sku', $value['sku'])->update('purchase_suggest',$change_data_sug);
                        $success_list[] = $value['purchase_number'].'_'.$value['sku'];

                        //日志
                        $chang_data_log['purchase_number'] = $value['purchase_number'];
                        $chang_data_log['sku'] = $value['sku'];
                        $chang_data_log['old_product_base_price'] = $value['product_base_price'];
                        $chang_data_log['old_purchase_unit_price'] = $value['purchase_unit_price'];
                        $chang_data_log['new_product_base_price'] = format_two_point_price($value['purchase_price']);
                        $chang_data_log['new_purchase_unit_price'] = format_two_point_price($value['purchase_price']);

                        $chang_data_log_add['purchase_number'] = $value['purchase_number'];
                        $chang_data_log_add['sku'] = $value['sku'];
                        $chang_data_log_add['chang_data_content'] = json_encode($chang_data_log);
                        $chang_data_log_add['create_time'] = date('Y-m-d H:i:s');
                        if(!empty($chang_data_log_add)){
                            $this->db->insert('chang_price_log', $chang_data_log_add);    
                        }

                    }    
                }

                //退税的需求单
                if(!empty($purchase_order_is_drawback_list)){
                    foreach ($purchase_order_is_drawback_list as $dr_key => $dr_value) {
                        $change_data_dr = array();
                        $change_data_sug_dr = array();
                        $chang_data_dr_log = $chang_data_log_dr_add = array();
                        $product_info = $this->product_model->get_product_info($dr_value['sku']);
                        if(empty($product_info)){
                            continue;
                        }
                        $purchase_unit_price = format_two_point_price($dr_value['purchase_price']*(1+($dr_value['ticketed_point']?$dr_value['ticketed_point']/100:0)));

                        $change_data_dr['product_base_price'] = format_two_point_price($dr_value['purchase_price']);
                        $change_data_dr['purchase_unit_price'] = $purchase_unit_price;
                        //修改采购单价格
                        $this->db->where('purchase_number', $dr_value['purchase_number'])->where('sku', $dr_value['sku'])->update('purchase_order_items',$change_data_dr);

                        //修改需求单价格
                        $change_data_sug_dr['purchase_unit_price'] = $purchase_unit_price;
                        $change_data_sug_dr['purchase_total_price'] = format_two_point_price($purchase_unit_price*$dr_value['purchase_amount']);
                        $this->db->where('demand_number', $dr_value['demand_number'])->where('sku', $dr_value['sku'])->update('purchase_suggest',$change_data_sug_dr);

                        $success_list[] = $dr_value['purchase_number'].'_'.$dr_value['sku'];

                        //日志
                        $chang_data_dr_log['purchase_number'] = $dr_value['purchase_number'];
                        $chang_data_dr_log['sku'] = $dr_value['sku'];
                        $chang_data_dr_log['old_product_base_price'] = $dr_value['product_base_price'];
                        $chang_data_dr_log['old_purchase_unit_price'] = $dr_value['purchase_unit_price'];
                        $chang_data_dr_log['new_product_base_price'] = format_two_point_price($dr_value['purchase_price']);
                        $chang_data_dr_log['new_purchase_unit_price'] = $purchase_unit_price;;

                        $chang_data_log_dr_add['purchase_number'] = $dr_value['purchase_number'];
                        $chang_data_log_dr_add['sku'] = $dr_value['sku'];
                        $chang_data_log_dr_add['chang_data_content'] = json_encode($chang_data_dr_log);
                        $chang_data_log_dr_add['create_time'] = date('Y-m-d H:i:s');
                        if(!empty($chang_data_log_dr_add)){
                            $this->db->insert('chang_price_log', $chang_data_log_dr_add);    
                        }
                    }    
                }
                if(!empty($success_list)){
                    var_dump(implode(',', array_unique($success_list)).'采购单更新完成');
                }
            }else{
                throw new Exception('采购单暂无数据');
            }

        } catch (Exception $exc) {
            echo $exc->getMessage();
        }            
    }



    /**
     * 根据SKU同步数据
     * @author Jaden
     /purchase_drawback_api/chang_purchase_order_sku
     */

    public function chang_purchase_order_sku(){
        $sku = $this->input->get_post('sku');
        $pay_type = $this->input->get_post('pay_type');
        if(empty($sku)){
            echo 'SKU不能为空';exit;
        }
        $product_info = $this->product_model->get_product_info($sku);
        if(empty($product_info)){
            echo 'SKU异常';exit;
        }
        $success_list = array();
        $change_data['new_supplier_price'] = $product_info['purchase_price'];
        $change_data['new_supplier_code'] = $product_info['supplier_code'];
        $change_data['new_supplier_name'] = $product_info['supplier_name'];
        $change_data['new_ticketed_point'] = $product_info['ticketed_point'];
        $change_data['is_drawback'] = $product_info['is_drawback'];
        $result = $this->purchase_suggest_model->change_suggest_purchase_price($sku, $change_data);
        if($result['msg'] == '成功'){
            echo $sku.'更新成功！';exit;
        }else{
            echo $result['msg'];exit;
        }
        //更新支付方式
        if(!empty($pay_type)){
            $this->purchase_suggest_model->change_suggest_pay_type($sku, $product_info['supplier_code']);
        }


    }



}