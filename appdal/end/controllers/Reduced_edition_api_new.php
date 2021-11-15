<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * SKU降本定时任务(新)
 * User: Jaden
 * Date: 2019/01/17 
 */

class Reduced_edition_api_new extends MY_API_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('purchase/Reduced_edition_model','reduced_edition');
        $this->load->model('purchase/Purchase_order_items_model','purchase_order_items_model');
        $this->load->model('purchase/Purchase_order_cancel_model','purchase_order_cancel_model');
        $this->load->model('product/Product_mod_audit_model','product_mod_audit_model');

    }
    
    /**
     * SKU降本计划任务(新)
     * /reduced_edition_api/get_reduced_edition_sku_list
     * @author Jaden 2019-1-17
    */
    public function get_reduced_edition_sku_list(){
        set_time_limit(0);
        $start_time = $this->input->get_post('start_time');
        $end_time = $this->input->get_post('end_time');
        $now_time = date('Y-m-d H:i:s');

        if(!empty($start_time) AND !empty($end_time)){
            $params = [
                'audit_time_start' => $start_time, // 价格变化开始时间
                'audit_time_end' => $end_time, // 价格变化结束时间
                //'sku' => 'JY00020-04',
                'contrast_price'=>1,
                'audit_status'=>3,
                'old_type'=>0
            ];    
        }else{
            $params = [
                //'audit_time_start' => $starttime, // 价格变化开始时间
                //'audit_time_end' => $endtime, // 价格变化结束时间
                //'sku' => 'JY00020-04',
                'contrast_price'=>1,
                'audit_status'=>3,
                'old_type'=>0
            ];  
        }
        
        //$total = $this->db->select('sku')->from('product_update_log')->where('audit_time>="'.$starttime.'" AND audit_time<="'.$endtime.'" AND old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=0')->count_all_results();
        if(!empty($start_time) AND !empty($end_time)){
            $total = $this->db->select('sku')->from('product_update_log')->where('audit_time>="'.$start_time.'" AND audit_time<="'.$end_time.'" AND old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=0')->count_all_results();
        }else{
            $total = $this->db->select('sku')->from('product_update_log')->where('old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=0')->count_all_results();    
        }
        $limit = 500; 
        $num = ceil($total/$limit);
        $field ='a.*,b.sku as product_sku,b.product_thumb_url';
        if($total>1){
            //$this->reduced_edition->delete_reduced_edition($starttime,$endtime_st);
            for ($i=1; $i <= $num; $i++) { 
                $edition_arr =array();
                $offset = ($i - 1) * $limit;
                $orders_info = $this->product_mod_audit_model->get_product_list($params, $limit,$offset,$field);
                $reduced_edition_list = $orders_info['data_list'];
                if(!empty($reduced_edition_list)){
                    foreach ($reduced_edition_list as $key => $value) {
                        $confirm_amount1 = $cancel_ctq1 = 0;
                        $confirm_amount2 = $cancel_ctq2 = 0;
                        $where = '';
                        $app_id_arr1 = $app_id_arr2 =  array();
                        $confirm_amount_arr_one1 = $confirm_amount_arr_one2 = array();
                        $add_arr1 = $add_arr2 = array();
                        $price_change_time = $value['audit_time'];//价格改变时间
                        //echo $value['sku'].'-----'.$price_change_time.'<br>';
                        $endtime = date("Y-m-d H:i:s",strtotime("$price_change_time+30 day"));//价格改变后30天时间
                        //价格改变之后是否有下单
                        $change_price = $value['new_supplier_price'];
                        $product_base_price = $value['new_supplier_price']*(1+($value['new_ticketed_point']/100));
                        $confirm_amount_arr_one1 = $this->get_confirm_amount_one($value['sku'], $price_change_time,$change_price,$product_base_price);

                        if(empty($confirm_amount_arr_one1)){//改变价格之后没有数据,跳过计算
                            continue;
                        }else{
                            if(!empty($value['cost_begin_time'])){
                                $days = $this->diffBetweenTwoDays($now_time,$value['cost_begin_time']);
                                if($days>30 AND $value['cost_begin_time']!='NULL'){
                                    continue;
                                }else{
                                    $app_id_arr1[] = $value['id'];
                                    //更新pur_product_update_log 降本开始计算时间
                                    $this->db->where('id', $value['id'])->update('product_update_log',['cost_begin_time'=>$confirm_amount_arr_one1['audit_time']]);
                                    //获取分段计算时间
                                    $order_start_time1 = $confirm_amount_arr_one1['audit_time'];
                                    $countTime1 = self::getPurNumTime($order_start_time1);
                                    //echo '<pre>';
                                    //echo $value['sku'].'-----'.$order_start_time1.'<br>';
                                    //print_R($countTime1);
                                    $countTime2 = !empty($countTime1) ? self::getPurNumTime($countTime1['end_time'],$countTime1['limit']) : [];

                                    $dateArray=[];
                                    if(!empty($countTime1)){
                                        $dateArray[] = date('Y-m-01 00:00:00',strtotime($countTime1['begin_time']));
                                    }
                                    if(!empty($countTime2)){
                                        $dateArray[] = date('Y-m-01 00:00:00',strtotime($countTime2['begin_time']));
                                    }

                                    //判断是否降价之后涨价，如果是涨价，数量不累积
                                    $sku_is_rise1 = $this->reduced_edition->get_sku_is_rise($value['sku'],$price_change_time,$endtime);
                                    if(!empty($sku_is_rise1) AND ($value['new_supplier_price']<$value['old_supplier_price']) AND $sku_is_rise1['audit_time']<=$countTime1['end_time']){
                                        $countTime1['end_time'] = $sku_is_rise1['audit_time'];
                                    }else{
                                        $countTime1['end_time'] = $countTime1['end_time'];
                                    }


                                    //判断是否降价，如果是降价，数量不累积
                                    $sku_is_reduction1 = $this->reduced_edition->get_sku_is_reduction($value['sku'],$price_change_time,$endtime);
                                    if(!empty($sku_is_reduction1) AND ($value['new_supplier_price']>$value['old_supplier_price']) AND $sku_is_reduction1['audit_time']<=$countTime1['end_time']){
                                        $countTime1['end_time'] = $sku_is_reduction1['audit_time'];
                                    }else{
                                        $countTime1['end_time'] = $countTime1['end_time'];
                                    }

                                    //月开始时间
                                    $month_date = get_month_time($order_start_time1);
                                    $confirm_amount_arr1 = $this->get_confirm_amount_by_sku($value['sku'], $countTime1['begin_time'], $countTime1['end_time'],$change_price,$product_base_price);
                                    $confirm_amount1 = array_sum( array_column($confirm_amount_arr1, 'confirm_amount') );
                                    $cancel_ctq1 = array_sum( array_column($confirm_amount_arr1, 'cancel_ctq') );
                                    if(isset($confirm_amount_arr1[0]['confirm_amount']) AND !empty($confirm_amount_arr1[0]['confirm_amount'])){
                                        $purchase_quantity1 = $confirm_amount1;
                                    }else{
                                        $purchase_quantity1 = 0;
                                    }
                                    //获取取消数量
                                    /*
                                    $order_cancel_arr1 = $this->purchase_order_cancel_model->get_cancel_ctq_by_sku($value['sku'], $countTime1['begin_time'], $countTime1['end_time']);
                                    if(isset($order_cancel_arr1['cancel_ctq'])){
                                        $cancel_ctq1 = $order_cancel_arr1['cancel_ctq'];
                                    }else{
                                        $cancel_ctq1 = 0;
                                    }
                                    */

                                    $statistical_time1 = substr($order_start_time1,0,7);//统计时间

                                    $add_arr1['sku'] = $value['sku'];
                                    $add_arr1['optimizing_user'] = $value['create_user_name'];
                                    $add_arr1['product_name'] = $value['product_name'];
                                    $add_arr1['supplier_code'] = $value['new_supplier_code'];
                                    $add_arr1['sku'] = $value['sku'];
                                    $add_arr1['supplier_name'] = $value['new_supplier_name'];
                                    $add_arr1['price_change_time'] = $value['audit_time'];
                                    $add_arr1['first_calculation_time'] = isset($order_start_time1) ? $order_start_time1 : '还未下单';
                                    $add_arr1['statistical_time'] = $statistical_time1;
                                    $add_arr1['original_price'] = $value['old_supplier_price'];//原价
                                    $add_arr1['present_price'] = $value['new_supplier_price'];//现价
                                    $add_arr1['price_change'] = $value['new_supplier_price']-$value['old_supplier_price'];//价格变化幅度(现价-原价)
                                    $add_arr1['price_change_total'] =round(($value['new_supplier_price']-$value['old_supplier_price'])*($purchase_quantity1-$cancel_ctq1),3);//价格变化金额
                                    $add_arr1['purchase_quantity'] = $purchase_quantity1;//采购数量
                                    $add_arr1['cancel_ctq'] = $cancel_ctq1;//取消数量
                                    $add_arr1['effective_purchase_quantity'] = $purchase_quantity1-$cancel_ctq1;//有效采购数量
                                    $add_arr1['app_id'] = $value['id'];
                                    $add_arr1['old_type'] = 0;
                                    array_push($edition_arr,$add_arr1);
                                    //删除数数据，重新计算
                                    //$app_id_arr1 = array_column($reduced_edition_list, 'id');
                                    //$this->db->where('statistical_time="'.$statistical_time2.'" AND old_type=0')->where_in('app_id', $app_id_arr2)->delete($this->reduced_edition->tableName());
                                    $this->db->where('old_type=0')->where_in('app_id', $app_id_arr1)->delete($this->reduced_edition->tableName());

                                    //跨幅度两个月的情况
                                    if(isset($dateArray[1])){
                                        $app_id_arr2[] = $value['id'];
                                        //判断是否就涨价，如果是涨价，数量不累积
                                        $sku_is_rise2 = $this->reduced_edition->get_sku_is_rise($value['sku'],$price_change_time,$endtime);
                                        if(!empty($sku_is_rise2) AND ($value['new_supplier_price']<$value['old_supplier_price']) AND $sku_is_rise2['audit_time']<=$countTime2['end_time']){
                                            $countTime2['end_time'] = $sku_is_rise2['audit_time'];
                                        }else{
                                            $countTime2['end_time'] = $countTime2['end_time'];
                                        }

                                        //判断是否降价，如果是降价，数量不累积
                                        $sku_is_reduction2 = $this->reduced_edition->get_sku_is_reduction($value['sku'],$price_change_time,$endtime);
                                        if(!empty($sku_is_reduction2) AND ($value['new_supplier_price']>$value['old_supplier_price']) AND $sku_is_reduction2['audit_time']<=$countTime2['end_time']){
                                            $countTime2['end_time'] = $sku_is_reduction2['audit_time'];
                                        }else{
                                            $countTime2['end_time'] = $countTime2['end_time'];
                                        }

                                        $confirm_amount_arr2 = $this->get_confirm_amount_by_sku($value['sku'], $countTime2['begin_time'], $countTime2['end_time'],$change_price,$product_base_price);
                                        $confirm_amount2 = array_sum( array_column($confirm_amount_arr2, 'confirm_amount') );
                                        $cancel_ctq2 = array_sum( array_column($confirm_amount_arr2, 'cancel_ctq') );
                                        if(isset($confirm_amount_arr2[0]['confirm_amount']) AND !empty($confirm_amount_arr2[0]['confirm_amount'])){
                                            $purchase_quantity2 = $confirm_amount2;
                                        }else{
                                            $purchase_quantity2 = 0;
                                        }
                                        //获取取消数量
                                        /*
                                        $order_cancel_arr2 = $this->purchase_order_cancel_model->get_cancel_ctq_by_sku($value['sku'], $dateArray[1], $endtime);
                                        if(isset($order_cancel_arr2['cancel_ctq'])){
                                            $cancel_ctq2 = $order_cancel_arr2['cancel_ctq'];
                                        }else{
                                            $cancel_ctq2 = 0;
                                        }
                                        */

                                        $now_date = date('Y-m-d H:i:s');
                                        $statistical_time2 = substr($dateArray[1],0,7);//统计时间
                                        if(isset($purchase_quantity2) && isset($cancel_ctq2) && $now_date>$dateArray[1]){
                                            $add_arr2['sku'] = $value['sku'];
                                            $add_arr2['optimizing_user'] = $value['create_user_name'];
                                            $add_arr2['product_name'] = $value['product_name'];
                                            $add_arr2['supplier_code'] = $value['new_supplier_code'];
                                            $add_arr2['sku'] = $value['sku'];
                                            $add_arr2['supplier_name'] = $value['new_supplier_name'];
                                            $add_arr2['price_change_time'] = $value['audit_time'];
                                            $add_arr2['first_calculation_time'] = isset($order_start_time1) ? $order_start_time1 : '还未下单';
                                            $add_arr2['statistical_time'] = $statistical_time2;
                                            $add_arr2['original_price'] = $value['old_supplier_price'];//原价
                                            $add_arr2['present_price'] = $value['new_supplier_price'];//现价
                                            $add_arr2['price_change'] = $value['new_supplier_price']-$value['old_supplier_price'];//价格变化幅度(现价-原价)
                                            $add_arr2['price_change_total'] =round(($value['new_supplier_price']-$value['old_supplier_price'])*($purchase_quantity2-$cancel_ctq2),3);//价格变化金额
                                            $add_arr2['purchase_quantity'] = $purchase_quantity2;//采购数量
                                            $add_arr2['cancel_ctq'] = $cancel_ctq2;//取消数量
                                            $add_arr2['effective_purchase_quantity'] = $purchase_quantity2-$cancel_ctq2;//有效采购数量
                                            $add_arr2['app_id'] = $value['id'];
                                            $add_arr2['old_type'] = 0;
                                            array_push($edition_arr,$add_arr2);
                                                
                                        }
                                        //删除数数据，重新计算
                                    //$app_id_arr2 = array_column($reduced_edition_list, 'id');
                                    //$this->db->where('statistical_time="'.$statistical_time2.'" AND old_type=0')->where_in('app_id', $app_id_arr2)->delete($this->reduced_edition->tableName());
                                    $this->db->where('old_type=0')->where_in('app_id', $app_id_arr2)->delete($this->reduced_edition->tableName());
                                    }
                                }    
                            }else{
                                continue;
                            } 
                        }
                    }
                    
                    if(!empty($edition_arr)){
                        //echo '<pre>';
                        //print_r($edition_arr);exit;     
                        $this->reduced_edition->insert_reduced_batch_all($edition_arr);    
                    }
                    
                }
                usleep(100000);
                var_dump('OK');
            }
        }else{
            var_dump('查不到符合条件数据');
        }

       
            
    }

    /**
     * 老系统SKU降本计划任务
     * /reduced_edition_api/get_old_edition_sku_list
     * @author Jaden 2019-1-17
    */
    public function get_old_edition_sku_list(){
        set_time_limit(0);
        $now_time = date('Y-m-d H:i:s');
        $start_time = $this->input->get_post('start_time');
        $end_time = $this->input->get_post('end_time');
        $now_time = date('Y-m-d H:i:s');

        if(!empty($start_time) AND !empty($end_time)){
            $params = [
                'audit_time_start' => $start_time, // 价格变化开始时间
                'audit_time_end' => $end_time, // 价格变化结束时间
                //'sku' => 'JY00020-04',
                'contrast_price'=>1,
                'audit_status'=>3,
                'old_type'=>1
            ];    
        }else{
            $params = [
                //'audit_time_start' => $starttime, // 价格变化开始时间
                //'audit_time_end' => $endtime, // 价格变化结束时间
                //'sku' => 'JY00020-04',
                'contrast_price'=>1,
                'audit_status'=>3,
                'old_type'=>1
            ];  
        }
        //$total = $this->db->select('sku')->from('product_update_log')->where('audit_time>="'.$starttime.'" AND audit_time<="'.$endtime.'" AND old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=1')->count_all_results();
        if(!empty($start_time) AND !empty($end_time)){
            $total = $this->db->select('sku')->from('product_update_log')->where('audit_time>="'.$start_time.'" AND audit_time<="'.$end_time.'" AND old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=1')->count_all_results();
        }else{
            $total = $this->db->select('sku')->from('product_update_log')->where('old_supplier_price!=new_supplier_price AND audit_status=3 AND old_type=1')->count_all_results();    
        }
        $limit = 500; 
        $num = ceil($total/$limit);
        $field ='a.*,b.sku as product_sku,b.product_thumb_url';
        if($total>1){
            //$this->reduced_edition->delete_reduced_edition($starttime,$endtime);
            for ($i=1; $i <= $num; $i++) { 
                $edition_arr =array();
                $offset = ($i - 1) * $limit;
                $orders_info = $this->product_mod_audit_model->get_product_list($params, $limit,$offset,$field);
                $reduced_edition_list = $orders_info['data_list'];
                if(!empty($reduced_edition_list)){
                    foreach ($reduced_edition_list as $key => $value) {
                        $confirm_amount1 = $cancel_ctq1 = 0;
                        $confirm_amount2 = $cancel_ctq2 = 0;
                        $where = '';
                        $app_id_arr1 = $app_id_arr2 = array();
                        $confirm_amount_arr_one1 = $confirm_amount_arr_one2 = array();
                        $add_arr1 = $add_arr2 = array();
                        $price_change_time = $value['audit_time'];//价格改变时间
                        $endtime = date("Y-m-d H:i:s",strtotime("$price_change_time+30 day"));//价格改变后30天时间
                        //价格改变之后是否有下单
                        $change_price = $value['new_supplier_price'];
                        $product_base_price = $value['new_supplier_price']*(1+($value['new_ticketed_point']/100));
                        $confirm_amount_arr_one1 = $this->get_confirm_amount_one($value['sku'], $price_change_time,$change_price,$product_base_price);
                        if(empty($confirm_amount_arr_one1)){//改变价格之后没有数据,跳过计算
                            continue;
                        }else{
                            $days = $this->diffBetweenTwoDays($now_time,$value['cost_begin_time']);
                            if($days>30 AND !empty($value['cost_begin_time'])){
                                continue;
                            }else{
                                $app_id_arr1[] = $value['id'];
                                //更新pur_product_update_log 降本开始计算时间
                                $this->db->where('id', $value['id'])->update('product_update_log',['cost_begin_time'=>$confirm_amount_arr_one1['audit_time']]);
                                //获取分段计算时间
                                $order_start_time1 = $confirm_amount_arr_one1['audit_time'];
                                $countTime1 = self::getPurNumTime($order_start_time1);
                                $countTime2 = !empty($countTime1) ? self::getPurNumTime($countTime1['end_time'],$countTime1['limit']) : [];

                                $dateArray=[];
                                if(!empty($countTime1)){
                                    $dateArray[] = date('Y-m-01 00:00:00',strtotime($countTime1['begin_time']));
                                }
                                if(!empty($countTime2)){
                                    $dateArray[] = date('Y-m-01 00:00:00',strtotime($countTime2['begin_time']));
                                }

                                //判断是否就涨价，如果是涨价，数量不累积
                                $sku_is_rise1 = $this->reduced_edition->get_sku_is_rise($value['sku'],$price_change_time,$endtime);
                                if(!empty($sku_is_rise1) AND ($value['new_supplier_price']<$value['old_supplier_price']) AND $sku_is_rise1['audit_time']<=$countTime1['end_time']){
                                    $countTime1['end_time'] = $sku_is_rise1['audit_time'];
                                }else{
                                    $countTime1['end_time'] = $countTime1['end_time'];
                                }

                                //判断是否降价，如果是降价，数量不累积
                                $sku_is_reduction1 = $this->reduced_edition->get_sku_is_reduction($value['sku'],$price_change_time,$endtime);
                                if(!empty($sku_is_reduction1) AND ($value['new_supplier_price']>$value['old_supplier_price']) AND $sku_is_reduction1['audit_time']<=$countTime1['end_time']){
                                    $countTime1['end_time'] = $sku_is_reduction1['audit_time'];
                                }else{
                                    $countTime1['end_time'] = $countTime1['end_time'];
                                }


                                //月开始时间
                                $month_date = get_month_time($order_start_time1);
                                $confirm_amount_arr1 = $this->get_confirm_amount_by_sku($value['sku'], $countTime1['begin_time'], $countTime1['end_time'],$change_price,$product_base_price);
                                $confirm_amount1 = array_sum( array_column($confirm_amount_arr1, 'confirm_amount') );
                                $cancel_ctq1 = array_sum( array_column($confirm_amount_arr1, 'cancel_ctq') );
                                if(isset($confirm_amount_arr1[0]['confirm_amount']) AND !empty($confirm_amount_arr1[0]['confirm_amount'])){
                                    $purchase_quantity1 = $confirm_amount1;
                                }else{
                                    $purchase_quantity1 = 0;
                                }
                                //获取取消数量
                                /*
                                $order_cancel_arr1 = $this->purchase_order_cancel_model->get_cancel_ctq_by_sku($value['sku'], $countTime1['begin_time'], $countTime1['end_time']);
                                if(isset($order_cancel_arr1['cancel_ctq'])){
                                    $cancel_ctq1 = $order_cancel_arr1['cancel_ctq'];
                                }else{
                                    $cancel_ctq1 = 0;
                                }
                                */

                                $statistical_time1 = substr($order_start_time1,0,7);//统计时间

                                $add_arr1['sku'] = $value['sku'];
                                $add_arr1['optimizing_user'] = $value['create_user_name'];
                                $add_arr1['product_name'] = $value['product_name'];
                                $add_arr1['supplier_code'] = $value['new_supplier_code'];
                                $add_arr1['sku'] = $value['sku'];
                                $add_arr1['supplier_name'] = $value['new_supplier_name'];
                                $add_arr1['price_change_time'] = $value['audit_time'];
                                $add_arr1['first_calculation_time'] = isset($order_start_time1) ? $order_start_time1 : '还未下单';
                                $add_arr1['statistical_time'] = $statistical_time1;
                                $add_arr1['original_price'] = $value['old_supplier_price'];//原价
                                $add_arr1['present_price'] = $value['new_supplier_price'];//现价
                                $add_arr1['price_change'] = $value['new_supplier_price']-$value['old_supplier_price'];//价格变化幅度(现价-原价)
                                $add_arr1['price_change_total'] =round(($value['new_supplier_price']-$value['old_supplier_price'])*($purchase_quantity1-$cancel_ctq1),3);//价格变化金额
                                $add_arr1['purchase_quantity'] = $purchase_quantity1;//采购数量
                                $add_arr1['cancel_ctq'] = $cancel_ctq1;//取消数量
                                $add_arr1['effective_purchase_quantity'] = $purchase_quantity1-$cancel_ctq1;//有效采购数量
                                $add_arr1['app_id'] = $value['id'];
                                $add_arr1['old_type'] = 1;
                                array_push($edition_arr,$add_arr1);
                                //删除数数据，重新计算
                                //$app_id_arr1 = array_column($reduced_edition_list, 'id');
                                $this->db->where('old_type=1')->where_in('app_id', $app_id_arr1)->delete($this->reduced_edition->tableName());

                                //跨幅度两个月的情况
                                if(isset($dateArray[1])){
                                    $app_id_arr2[] = $value['id'];
                                    //判断是否就涨价，如果是涨价，数量不累积
                                    $sku_is_rise2 = $this->reduced_edition->get_sku_is_rise($value['sku'],$price_change_time,$endtime);
                                    if(!empty($sku_is_rise2) AND ($value['new_supplier_price']<$value['old_supplier_price']) AND $sku_is_rise2['audit_time']<=$countTime2['end_time']){
                                        $countTime2['end_time'] = $sku_is_rise2['audit_time'];
                                    }else{
                                        $countTime2['end_time'] = $countTime2['end_time'];
                                    }

                                    //判断是否降价，如果是降价，数量不累积
                                    $sku_is_reduction2 = $this->reduced_edition->get_sku_is_reduction($value['sku'],$price_change_time,$endtime);
                                    if(!empty($sku_is_reduction2) AND ($value['new_supplier_price']>$value['old_supplier_price']) AND $sku_is_reduction2['audit_time']<=$countTime2['end_time']){
                                        $countTime2['end_time'] = $sku_is_reduction2['audit_time'];
                                    }else{
                                        $countTime2['end_time'] = $countTime2['end_time'];
                                    }

                                    $confirm_amount_arr2 = $this->get_confirm_amount_by_sku($value['sku'], $countTime2['begin_time'], $countTime2['end_time'],$change_price,$product_base_price);
                                    $confirm_amount2 = array_sum( array_column($confirm_amount_arr2, 'confirm_amount') );
                                    $cancel_ctq2 = array_sum( array_column($confirm_amount_arr2, 'cancel_ctq') );
                                    if(isset($confirm_amount_arr2[0]['confirm_amount']) AND !empty($confirm_amount_arr2[0]['confirm_amount'])){
                                        $purchase_quantity2 = $confirm_amount2;
                                    }else{
                                        $purchase_quantity2 = 0;
                                    }
                                    //获取取消数量
                                    /*
                                    $order_cancel_arr2 = $this->purchase_order_cancel_model->get_cancel_ctq_by_sku($value['sku'], $dateArray[1], $endtime);
                                    if(isset($order_cancel_arr2['cancel_ctq'])){
                                        $cancel_ctq2 = $order_cancel_arr2['cancel_ctq'];
                                    }else{
                                        $cancel_ctq2 = 0;
                                    }
                                    */

                                    $now_date = date('Y-m-d H:i:s');
                                    $statistical_time2 = substr($dateArray[1],0,7);//统计时间
                                    if(isset($purchase_quantity2) && isset($cancel_ctq2) && $now_date>$dateArray[1]){
                                        $add_arr2['sku'] = $value['sku'];
                                        $add_arr2['optimizing_user'] = $value['create_user_name'];
                                        $add_arr2['product_name'] = $value['product_name'];
                                        $add_arr2['supplier_code'] = $value['new_supplier_code'];
                                        $add_arr2['sku'] = $value['sku'];
                                        $add_arr2['supplier_name'] = $value['new_supplier_name'];
                                        $add_arr2['price_change_time'] = $value['audit_time'];
                                        $add_arr2['first_calculation_time'] = isset($order_start_time1) ? $order_start_time1 : '还未下单';
                                        $add_arr2['statistical_time'] = $statistical_time2;
                                        $add_arr2['original_price'] = $value['old_supplier_price'];//原价
                                        $add_arr2['present_price'] = $value['new_supplier_price'];//现价
                                        $add_arr2['price_change'] = $value['new_supplier_price']-$value['old_supplier_price'];//价格变化幅度(现价-原价)
                                        $add_arr2['price_change_total'] =round(($value['new_supplier_price']-$value['old_supplier_price'])*($purchase_quantity2-$cancel_ctq2),3);//价格变化金额
                                        $add_arr2['purchase_quantity'] = $purchase_quantity2;//采购数量
                                        $add_arr2['cancel_ctq'] = $cancel_ctq2;//取消数量
                                        $add_arr2['effective_purchase_quantity'] = $purchase_quantity2-$cancel_ctq2;//有效采购数量
                                        $add_arr2['app_id'] = $value['id'];
                                        $add_arr2['old_type'] = 1;
                                        array_push($edition_arr,$add_arr2);
                                            
                                    }
                                    //删除数数据，重新计算
                                //$app_id_arr2 = array_column($reduced_edition_list, 'id');
                                $this->db->where('old_type=1')->where_in('app_id', $app_id_arr2)->delete($this->reduced_edition->tableName());
                                }
                            }    
                             
                        }
                    }
                    
                    if(!empty($edition_arr)){
                        //echo '<pre>';
                        //print_r($edition_arr);exit;     
                        $this->reduced_edition->insert_reduced_batch_all($edition_arr);    
                    }
                }
                usleep(100000);
                var_dump('OK');

            }
        }else{
            var_dump('查不到符合条件数据');
        }




    }



    /**
     * 根据SKU和时间段统计审核通过的采购数量
     * @author Jaden
     * @date 2019/3/14 17:21
     * @param string $sku
     * @param string $start_time
     * @param string $end_time
     * @return array
     */
    public function get_confirm_amount_by_sku($sku,$start_time,$end_time,$change_price,$product_base_price){
        if(empty($sku) || empty($start_time) || empty($end_time) || empty($change_price)){
            return [];
        }
        $this->db->select('ppoi.confirm_amount as confirm_amount,ppoi.purchase_number,ppoi.sku,ppo.audit_time,');
        $this->db->from('purchase_order_items as ppoi');
        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->db->where('ppoi.sku', $sku);
        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
        $this->db->where('ppo.audit_time >=', $start_time);
        $this->db->where('ppo.audit_time <=', $end_time);
        $this->db->where('(ppoi.product_base_price<="'.$change_price.'" OR ppoi.product_base_price="'.$product_base_price.'")');
        $results = $this->db->order_by('ppo.audit_time asc')->get()->result_array();
        foreach ($results as $key => $value) {
            $cancel_ctq = $this->purchase_order_cancel_model->get_cancel_ctq($value['sku'],$value['purchase_number']);
            $results[$key]['cancel_ctq'] = $cancel_ctq;
        }
        return $results;
      
    }




    public function get_confirm_amount_one($sku,$start_time,$change_price,$product_base_price){
        if( empty($sku) || empty($start_time) ){
            return [];
        }
        $this->db->select('ppoi.confirm_amount as confirm_amount,ppoi.purchase_number,ppo.audit_time');
        $this->db->from('purchase_order_items as ppoi');
        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
        $this->db->where('ppoi.sku', $sku);
        $this->db->where('ppo.audit_time >=', $start_time);
        $this->db->where_in('ppo.purchase_order_status', [7,8,9,10,11]);
        $this->db->where('(ppoi.product_base_price<="'.$change_price.'" OR ppoi.product_base_price="'.$product_base_price.'")');
        $results = $this->db->order_by('ppo.audit_time asc')->limit(1)->get()->row_array();
        //echo $this->db->last_query();exit;
        return $results;  
    }

    //计算两个时间相差天数
    function diffBetweenTwoDays ($day1, $day2)
    {
      $second1 = strtotime($day1);
      $second2 = strtotime($day2);
        
      if ($second1 < $second2) {
        $tmp = $second2;
        $second2 = $second1;
        $second1 = $tmp;
      }
      return ($second1 - $second2) / 86400;
    }



    //根据开始时间和天数间隔计算采购数据计算时间;
    public static function getPurNumTime($begin_time,$limit=30){
        if($limit<0){
            return [];
        }
        $month_big = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
        //开始的月份
        $date_month_old = (int)date('m',strtotime($begin_time));
        //下个月的月份
        $year = $date_month_old==12 ? date('Y',strtotime($begin_time)) +1 : date('Y',strtotime($begin_time));
        $date_time_new = strtotime('1 '.$month_big[$date_month_old%12].' '.$year);
        //今天的时间戳
        $date_time_old = strtotime(date('d',strtotime($begin_time)).' '.$month_big[$date_month_old-1].' '.date('Y',strtotime($begin_time)));
        //距下月剩余时间
        //var_dump($limit);
        $time_new = ($date_time_new - $date_time_old)/24/60/60;
        $old_limit=$limit;
        $limit-=$time_new;
        if($limit>0){
            $array['begin_time'] = $begin_time;
            $array['end_time'] = date('Y-m-d H:i:s',$date_time_new);
            $array['limit']    = $limit;
        }else{
            $array['begin_time'] = $begin_time;
            $array['end_time'] = date('Y-m-d 00:00:00',strtotime("$begin_time + $old_limit day"));
            $array['limit']    = $limit;
        }
        return $array;
    }




}