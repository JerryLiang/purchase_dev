<?php
/**
 * Created by PhpStorm.
 * SKU降本控制器
 * User: Jaden
 * Date: 2019/01/16 
 */

class Reduced_edition extends MY_Controller{

    public function __construct(){
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('reduced_edition_model','reduced_edition');
        $this->load->model('purchase_order_items_model','order_items');
        $this->load->model('purchase_order_cancel_model','order_cancel_model');
        $this->load->model('purchase_order_model','order');
        $this->load->model('product_line_model','product_line',false,'product');
        $this->load->model('purchase_order_cancel_model','cancel_model',false,'purchase');
        $this->load->helper('status_product');
    }
    /**
     * SKU降本列表
     * /purchase/reduced_edition/sku_reduced_edition_list
     * @author Jaden 2019-1-17
    */
    public function sku_reduced_edition_list(){
        $params = [
            'optimizing_user' => $this->input->get_post('optimizing_user'), // 优化人
            'price_trend' => $this->input->get_post('price_trend'), // 价格变化趋势(1涨价,2降价)
            'supplier_code' => $this->input->get_post('supplier_code'),
            'sku' => $this->input->get_post('sku'), // SKU
            'price_change_time_start' => $this->input->get_post('price_change_time_start'), // 价格变化开始时间
            'price_change_time_end' => $this->input->get_post('price_change_time_end'), // 价格变化结束时间
            'first_calculation_time_start' => $this->input->get_post('first_calculation_time_start'), // 首次计算开始时间
            'first_calculation_time_end' => $this->input->get_post('first_calculation_time_end'), // 首次计算结束时间
            'statistical_time' => $this->input->get_post('statistical_time'),//统计时间
            'effective_purchase_quantity' => $this->input->get_post('effective_purchase_quantity'), //有效采购数量(0[等于0],1[不等于0])
            'purchase_quantity' => $this->input->get_post('purchase_quantity'), //采购数量(0[等于0],1[不等于0])
        ];
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $orders_info = $this->reduced_edition->get_reduced_list($params, $offset, $limit);
        $orders_info['key'] = array('优化人','sku','产品名称','供应商','价格变化时间','首次计算时间','统计时间','原价','现价','价格变化幅度','降本比例','采购数量','取消数量','有效采购数量','价格变化金额');
        $data_list = $orders_info['value'];
        if(!empty($data_list)){
            $all_price_change_total = array_sum(array_column($data_list, 'price_change_total'));
        }else{
            $all_price_change_total = 0.00;
        }
        foreach ($data_list as $key => $value) {
            //降本比例：（现价-原价）/原价
            $reduced_proportion = $value['original_price']!=0 ? (round(($value['present_price']-$value['original_price'])/$value['original_price'],4)*100):0;
            $orders_info['value'][$key]['reduced_proportion'] = (round($reduced_proportion,2).'%');
            
            $start_time = $value['first_calculation_time'];
            $endtime = date("Y-m-d H:i:s",strtotime("$start_time+30 day"));//价格改变后30天时间

            $product_update_info = $this->db->select('sku,new_ticketed_point')->from('product_update_log')
                                            ->where('sku="'.$value['sku'].'" AND audit_time="'.$value['price_change_time'].'"')
                                            ->limit(1)
                                            ->get()
                                            ->row_array();
            $product_base_price = $value['present_price']*(1+($product_update_info['new_ticketed_point']/100)); 

            if(!empty($start_time) OR ($value['price_change_time']!=$value['first_calculation_time'] AND !empty($start_time))){
                $this->db->select('can.cancel_ctq,ppoi.purchase_number,ppoi.sku,ppo.audit_time');
                $this->db->from('purchase_order_items as ppoi');
                $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
                $this->db->join('purchase_order_cancel_detail as can', 'ppoi.purchase_number=can.purchase_number AND ppoi.sku=can.sku', 'left');
                $this->db->where('ppoi.sku', $value['sku']);
                $this->db->where_in('ppo.purchase_order_status', [14]);
                $this->db->where('ppo.audit_time >=', $start_time);
                $this->db->where('ppo.audit_time <=', $endtime);
                $this->db->like('ppo.audit_time',$value['statistical_time']);
                $this->db->where('ppoi.product_base_price="'.$product_base_price.'"');
                $order_list = $this->db->order_by('ppo.audit_time asc')->get()->result_array();    
            }
            $cancel_ctq = 0;
            if(!empty($order_list) AND $value['purchase_quantity']>0){
                $cancel_ctq = array_sum(array_column($order_list, 'cancel_ctq'));                
            }
            if($cancel_ctq>0){
                $orders_info['value'][$key]['cancel_ctq'] = $cancel_ctq;
                $effective_purchase_quantity = $value['purchase_quantity']-$cancel_ctq;
                $orders_info['value'][$key]['effective_purchase_quantity'] = $effective_purchase_quantity;
                $orders_info['value'][$key]['price_change_total'] = $effective_purchase_quantity*$value['price_change'];
            }
            
        }
        $product_line_list = $this->product_line->get_product_line_list(0);
        $drop_down_box['product_line_id'] =array_column($product_line_list, 'linelist_cn_name','product_line_id');
        $drop_down_box['price_trend_list'] =getPriceTrendStatus();
        $drop_down_box['effective_purchase_quantity_list'] = array('0'=>'等于0','1'=>'不等于0');//有效采购数量下拉
        $drop_down_box['purchase_quantity_list'] = array('0'=>'等于0','1'=>'不等于0');//采购数量下拉
        //供应商  优化人
        $drop_down_box += $this->reduced_edition->get_drop_down_box();
        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        $orders_info['page_data']['all_price_change_total'] = round($all_price_change_total,3);
        $orders_info['drop_down_box'] = $drop_down_box;
        $this->success_json($orders_info);
    }

    /**
     * SKU降本列表导出
     * /purchase/reduced_edition/reduced_export
     * @author Jaden 2019-1-17
    */
    public function reduced_export(){
        ini_set('memory_limit','4000M');
        set_time_limit(0);
        $ids = $this->input->get_post('ids');
        if(!empty($ids)){
            $params['ids']   = $ids;
        }else{
            $params = [
                'optimizing_user' => $this->input->get_post('optimizing_user'), // 优化人
                'price_trend' => $this->input->get_post('price_trend'), // 价格变化趋势(1涨价,2降价)
                'supplier_code' => $this->input->get_post('supplier_code'),
                'sku' => $this->input->get_post('sku'), // SKU
                'price_change_time_start' => $this->input->get_post('price_change_time_start'), // 价格变化开始时间
                'price_change_time_end' => $this->input->get_post('price_change_time_end'), // 价格变化结束时间
                'first_calculation_time_start' => $this->input->get_post('first_calculation_time_start'), // 首次计算开始时间
                'first_calculation_time_end' => $this->input->get_post('first_calculation_time_end'), // 首次计算结束时间
                'statistical_time' => $this->input->get_post('statistical_time'), // 统计时间
                'effective_purchase_quantity' => $this->input->get_post('effective_purchase_quantity'), //有效采购数量(0[等于0],1[不等于0])
                'purchase_quantity' => $this->input->get_post('purchase_quantity'), //采购数量(0[等于0],1[不等于0])
            ];  
        }
        $orders_info = $this->reduced_edition->get_reduced_sum($params, 1, 1, 'id');
        $total = $orders_info['page_data']['total'];

        //前端路径
        $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        //$file_name = 'reduced_edition.csv';
        $file_name = 'reduced_edition'.date('YmdHis').rand(1000, 9999).'.csv';
        $reduced_file = $webfront_path.'/webfront/download_csv/'.$file_name;
        if (file_exists($reduced_file)) {
            unlink($reduced_file);
        }
        fopen($reduced_file,'w');
        $fp = fopen($reduced_file, "a");
        $heads = ['优化人','SKU','产品名称','供应商','价格变化时间','首次计算时间', '统计时间', '原价','现价','价格变化幅度','降本比例','采购数量','取消数量','有效采购数量','价格变化金额','采购单号','备货单号','采购员','下单时间','备注'];
        foreach($heads as $key => $item) {
            $title[$key] =iconv('UTF-8', 'GBK//IGNORE', $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);

        if($total>=1){
            $page_limit = 5000;
            for ($i=1; $i <=ceil($total/$page_limit) ; $i++) { 
                $export_offset = ($i - 1) * $page_limit;
                $orders_list_info = $this->reduced_edition->get_reduced_list($params, $export_offset, $page_limit, '*');
                $delivery_list = $orders_list_info['value'];
                foreach ($delivery_list as $key => $value) {
                //if($value['first_calculation_time']!='还未下单'){
                    $start_time = $value['first_calculation_time'];
                    $endtime = date("Y-m-d H:i:s",strtotime("$start_time+30 day"));//价格改变后30天时间
                    //2019-06-17 
                    $product_update_info = $this->db->select('sku,new_ticketed_point')->from('product_update_log')
                                            ->where('sku="'.$value['sku'].'" AND audit_time="'.$value['price_change_time'].'"')
                                            ->limit(1)
                                            ->get()
                                            ->row_array();
                    $product_base_price = $value['present_price']*(1+($product_update_info['new_ticketed_point']/100)); 
                    //判断是否就涨价，如果是涨价，数量不累积
                    $sku_is_rise = $this->reduced_edition->get_sku_is_rise($value['sku'],$start_time,$endtime);
                    if(!empty($sku_is_rise) AND ($value['present_price']<$value['original_price'])){
                        $endtime = $sku_is_rise['audit_time'];
                    }else{
                        $endtime = $endtime;
                    }
                    //判断是否降价，如果是降价，数量不累积
                    $sku_is_reduction = $this->reduced_edition->get_sku_is_reduction($value['sku'],$start_time,$endtime);
                    if(!empty($sku_is_reduction) AND ($value['present_price']>$value['original_price'])){
                        $endtime = $sku_is_reduction['audit_time'];
                    }else{
                        $endtime = $endtime;
                    }
                    if( !empty($value['sku']) && !empty($start_time) OR ($value['price_change_time']!=$value['first_calculation_time'] AND !empty($start_time))){
                        $this->db->select('confirm_amount,ppoi.purchase_number,ppoi.sku,ppoi.product_base_price,ppoi.purchase_number,ppo.audit_time,ppo.create_time,ppo.buyer_name,sug.demand_number');
                        $this->db->from('purchase_order_items as ppoi');
                        $this->db->join('purchase_order as ppo', 'ppoi.purchase_number=ppo.purchase_number', 'left');
                        $this->db->join('pur_purchase_suggest_map as sug', 'ppoi.purchase_number=sug.purchase_number AND ppoi.sku=sug.sku', 'left');
                        $this->db->where('ppoi.sku', $value['sku']);
                        $this->db->where_in('ppo.purchase_order_status', [6,7,8,9,10,11,12,13,14]);
                        $this->db->where('ppo.audit_time >=', $start_time);
                        $this->db->where('ppo.audit_time <=', $endtime);
                        $this->db->like('ppo.audit_time',$value['statistical_time']);
                        $this->db->where('(ppoi.product_base_price<="'.$value['present_price'].'" OR ppoi.product_base_price="'.$product_base_price.'")');
                        $order_list = $this->db->order_by('ppo.audit_time asc')->get()->result_array();    
                    }
                    
                    if(!empty($order_list) AND $value['purchase_quantity']>0){
                        foreach ($order_list as $k => $val) {
                            $audit_time = substr($val['audit_time'],0,7);
                            if(!empty($params['statistical_time'])){
                                if($value['statistical_time']!=$audit_time){
                                    continue;
                                }    
                            }
                            $v_value_tmp = [];
                            $v_value_tmp['optimizing_user'] = iconv('UTF-8', 'GBK//IGNORE',$value['optimizing_user']);
                            $v_value_tmp['sku'] = iconv('UTF-8', 'GBK//IGNORE',$val['sku']);
                            $v_value_tmp['product_name'] = iconv('UTF-8', 'GBK//IGNORE',$value['product_name']);
                            $v_value_tmp['supplier_name'] = iconv('UTF-8', 'GBK//IGNORE',$value['supplier_name']);
                            $v_value_tmp['price_change_time'] = iconv('UTF-8', 'GBK//IGNORE',$value['price_change_time']);
                            $v_value_tmp['first_calculation_time'] = iconv('UTF-8', 'GBK//IGNORE',$value['first_calculation_time']);
                            $v_value_tmp['statistical_time'] =  iconv('UTF-8', 'GBK//IGNORE',$value['statistical_time']."\t");
                            $v_value_tmp['original_price'] = iconv('UTF-8', 'GBK//IGNORE',$value['original_price']);             
                            $v_value_tmp['present_price'] = iconv('UTF-8', 'GBK//IGNORE',$value['present_price']);
                            $v_value_tmp['price_change'] = iconv('UTF-8', 'GBK//IGNORE',$value['price_change']);
                            //降本比例：（现价-原价）/原价
                            $reduced_proportion = $value['original_price']!=0 ? (round(($value['present_price']-$value['original_price'])/$value['original_price'],4)*100):0;
                            $reduced_proportion = (round($reduced_proportion,2).'%');
                            $v_value_tmp['reduced_proportion'] = iconv('UTF-8', 'GBK//IGNORE',$reduced_proportion);
                            $v_value_tmp['purchase_quantity'] = iconv('UTF-8', 'GBK//IGNORE',$val['confirm_amount']);//采购数量
                            //取消数量
                            $cancel_ctq = $this->cancel_model->get_cancel_ctq($val['sku'],$val['purchase_number']);
                            $v_value_tmp['cancel_ctq'] = iconv('UTF-8', 'GBK//IGNORE',$cancel_ctq);//取消数量
                            //有效采购数量 = 采购数量-取消数量
                            $effective_purchase_quantity = $val['confirm_amount']-$cancel_ctq;
                            $v_value_tmp['effective_purchase_quantity'] = iconv('UTF-8', 'GBK//IGNORE',$effective_purchase_quantity);//有效采购数量 = 采购数量-取消数量
                            //价格变化金额：采购数量*价格变化幅度
                            $v_value_tmp['price_change_total'] =  iconv('UTF-8', 'GBK//IGNORE',$effective_purchase_quantity*$value['price_change']);
                            $v_value_tmp['purchase_number'] = iconv('UTF-8', 'GBK//IGNORE',$val['purchase_number']);
                            $v_value_tmp['demand_number'] = iconv('UTF-8', 'GBK//IGNORE',$val['demand_number']);//备货单号
                            $v_value_tmp['buyer_name'] = iconv('UTF-8', 'GBK//IGNORE',$val['buyer_name']);
                            $v_value_tmp['create_time'] = iconv('UTF-8', 'GBK//IGNORE',$val['audit_time']);
                            if($value['present_price']!=$val['product_base_price']){
                                $v_value_tmp['diejia'] = iconv('UTF-8', 'GBK//IGNORE','叠加');     
                            }
                            $tax_list_tmp[] = $v_value_tmp;
                            fputcsv($fp, $v_value_tmp);    
                        }    
                    }else{
                        $v_value_tmp = [];
                        $v_value_tmp['optimizing_user'] = iconv('UTF-8', 'GBK//IGNORE',$value['optimizing_user']);
                        $v_value_tmp['sku'] = iconv('UTF-8', 'GBK//IGNORE',$value['sku']);
                        $v_value_tmp['product_name'] = iconv('UTF-8', 'GBK//IGNORE',$value['product_name']);
                        $v_value_tmp['supplier_name'] = iconv('UTF-8', 'GBK//IGNORE',$value['supplier_name']);
                        $v_value_tmp['price_change_time'] = iconv('UTF-8', 'GBK//IGNORE',$value['price_change_time']);
                        $v_value_tmp['first_calculation_time'] = iconv('UTF-8', 'GBK//IGNORE',$value['first_calculation_time']);
                        $v_value_tmp['statistical_time'] =  iconv('UTF-8', 'GBK//IGNORE',$value['statistical_time']."\t");
                        $v_value_tmp['original_price'] = iconv('UTF-8', 'GBK//IGNORE',$value['original_price']);             
                        $v_value_tmp['present_price'] = iconv('UTF-8', 'GBK//IGNORE',$value['present_price']);
                        $v_value_tmp['price_change'] = iconv('UTF-8', 'GBK//IGNORE',$value['price_change']);
                        //降本比例：（现价-原价）/原价
                        $reduced_proportion = $value['original_price']!=0 ? (round(($value['present_price']-$value['original_price'])/$value['original_price'],4)*100):0;
                        $reduced_proportion = (round($reduced_proportion,2).'%');
                        $v_value_tmp['reduced_proportion'] = iconv('UTF-8', 'GBK//IGNORE',$reduced_proportion);
                        $v_value_tmp['purchase_quantity'] = 0;//采购数量
                        //取消数量
                        //$cancel_ctq = $this->cancel_model->get_cancel_ctq($value['sku'],$val['purchase_number']);
                        $v_value_tmp['cancel_ctq'] = 0;//取消数量
                        //有效采购数量 = 采购数量-取消数量
                        $effective_purchase_quantity = 0;
                        $v_value_tmp['effective_purchase_quantity'] = iconv('UTF-8', 'GBK//IGNORE',$effective_purchase_quantity);//有效采购数量 = 采购数量-取消数量
                        //价格变化金额：采购数量*价格变化幅度
                        $v_value_tmp['price_change_total'] =  iconv('UTF-8', 'GBK//IGNORE',$effective_purchase_quantity*$value['price_change']);
                        $v_value_tmp['purchase_number'] = '';
                        $v_value_tmp['demand_number'] = '';//备货单号
                        $v_value_tmp['buyer_name'] = '';
                        $v_value_tmp['create_time'] = '';
                        $tax_list_tmp[] = $v_value_tmp;
                        fputcsv($fp, $v_value_tmp);
                        }
                    }       
                //}
                //每1万条数据就刷新缓冲区
                ob_flush();
                flush();
            }                
        }
        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=$down_host.'download_csv/'.$file_name;
        $this->success_json($down_file_url);



        //$this->success_json($tax_list_tmp);


    }

     /**
     * 获取SKU采购列表
     * /purchase/reduced_edition/get_sku_purchase_list
     * @author Jaden 2019-1-17
    */
    public function get_sku_purchase_list(){
        $sku = $this->input->get_post('sku');// SKU
        if(empty($sku)){
            $this->error_data_json('sku参数不能为空！');
        }
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0){
            $page = 1;
        }
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;
        $field='a.sku,a.purchase_number,a.product_base_price,a.purchase_amount,a.upselft_amount,b.audit_time as create_time,';
        $orders_info = $this->order_items->getByskulist($sku, $offset, $limit,$field);
        $data_list = $orders_info['value'];
        $where='';
        $order_field='supplier_name,buyer_name';
        foreach ($data_list as $key => $value) {
            $where ='purchase_number="'.$value['purchase_number'].'"';
            $order_info = $this->order->getByWhereorderlist($where,$order_field);
            $orders_info['value'][$key]['supplier_name'] = $order_info['supplier_name'];
            $orders_info['value'][$key]['buyer_name'] = $order_info['buyer_name'];
        }
        $orders_info['page_data']['pages'] = ceil($orders_info['page_data']['total']/$limit);
        $orders_info['page_data']['offset'] = $page;
        $orders_info['key'] = array('供应商','采购单号','单价','采购数量','入库数量','采购日期','采购员');
        $this->success_json($orders_info);
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

    /**
     * 获取采购系统新版本SKU 降本优化记录信息
     * @author:luxu
     * @return  jsonstring
     **/

    public function get_reduced_data()
    {
        try
        {
            // HTTP 客户端传入数据缓存区
            $clientData = [];

            if( !empty($_GET) )
            {
                foreach( $_GET as $key=>$value)
                {
                    $clientData[$key] = $this->input->get_post($key);
                }
            }

            if( !isset($clientData['page']) || empty($clientData['page']))
            {
                $clientData['page'] =1;
            }

            if(  !isset($clientData['offset']) || empty($clientData['offset']) )
            {
                $clientData['offset'] =20;
            }
            $limit = query_limit_range($clientData['offset']);
            $offset = ($clientData['page'] - 1) * $limit;
            $result = $this->reduced_edition->get_reduced_list_data($clientData,$limit,$offset);
            $this->success_json($result);
        }catch ( Exception $exp )
        {
           $this->error_json($exp->getMessage());

        }
    }

    /**
      * function:SKU 降本配置
     **/
    public function get_reduced_config()
    {

        $result = $this->reduced_edition->get_reduced_config();
        $this->success_json($result);

    }

    /**
      * function: SKU 降本配置修改
     **/
    public function update_reduced_config()
    {
        // HTTP 客户端传入数据缓存区
        $clientData = [];

        if( !empty($_POST) )
        {
            foreach( $_POST as $key=>$value)
            {
                $clientData[$key] = $this->input->get_post($key);
            }
        }
        $username =  getActiveUserName();
        $result =  $this->reduced_edition->update_reduced_config($clientData,$username);
        $this->success_json();
    }

    /**
     * function: SKU 降本配置修改LOG日志
     **/
    public function get_reduced_log()
    {

        $result =  $this->reduced_edition->get_reduced_log();
        $return_data = [];
        if(!empty($result))
        {
            foreach( $result as $key=>&$value )
            {
                $before_content = json_decode( $value['before_content'],True);
                $after_content  = json_decode( $value['after_content'],True);

                $old_str = $new_str = '';
                $old_str.=" 修改前: 国内仓:".$before_content['domestic_days']." FBA:".$before_content['fba_days']." 海外:".$before_content['overseas_days']." 生效时间:".$before_content['create_time'];
                $new_str.=" 修改后: 国内仓:".$after_content['domestic_days']." FBA:".$after_content['domestic_days']." 海外:".$after_content['overseas_days']." 生效时间:".$after_content['create_time'];
                $value['new_str_log'] =  $new_str;
                $value['old_str_log'] =  $old_str;
                unset($value['before_content']);
                unset($value['after_content']);
            }
        }
        $this->success_json($result);
    }

    /**
      * 获取SKU 降本优化记录
     **/
    public function get_reduced_list()
    {
        try
        {
            // 缓存客户端传入信息
            $clientData = [];
            if( !empty($_GET) )
            {
                foreach( $_GET as $key=>$value )
                {
                    $clientData[$key] = $this->input->get_post($key);
                }
            }
            if( !isset($clientData['offset']) && empty($clientData['offset']) )
            {
                $clientData['offset'] =1;
            }
            if( !isset($clientData['limit']) && empty($clientData['limit']) )
            {
                $clientData['limit'] = 20;
            }
            if( isset($_GET['sku'])) {
               $skus = $this->input->get_post('sku');

               if( !empty($skus) && isset($skus[0]) && !empty($skus[0]))
               {
                   $clientData['sku'] = $skus;
               }else{
                   unset($clientData['sku']);
               }
            }
            $offset = ( $clientData['offset'] - 1) *  $clientData['limit'];
            $result = $this->reduced_edition->get_reduced_data_list( $clientData,"new.*,SUM(main.instock_qty) AS instock_qty ",$offset,$clientData['limit'],True);
            $role_name=get_user_role();//当前登录角色
            $result['value'] = ShieldingData($result['value'],['supplier_name','supplier_code'],$role_name,NULL);
            $this->success_json($result);
        }catch ( Exception $exp )
        {
            $this->success_json($exp->getMessage());


        }
    }

    /**
     * function:获取SKU 降本明细
     **/

    public function get_reduced_detail()
    {
        try{
            // 缓存客户端传入信息
            $clientData = [];
            if( !empty($_GET))
            {
                foreach( $_GET as $key=>$value )
                {
                    $clientData[$key] = $this->input->get_post($key);
                }
            }
            if( isset($_GET['sku']) && !empty($_GET['sku']))
            {
                $clientData['sku'] = $this->input->get_post('sku');
            }

            if( !isset($clientData['offset']) && empty($clientData['offset']) )
            {
                $clientData['offset'] =1;
            }

            if( !isset($clientData['limit']) && empty($clientData['limit']) )
            {
                $clientData['limit'] = 20;
            }


            if( isset($clientData['group_ids']) && !empty($clientData['group_ids'])){

                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($clientData['group_ids']);
                $groupdatas = [];
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                }

                $clientData['groupdatas'] = $groupdatas;
            }
            $result = $this->reduced_edition->get_reduced_detail( $clientData );
            $role_name=get_user_role();//当前登录角色
            $result['list'] = ShieldingData($result['list'],['supplier_name','supplier_code'],$role_name,NULL);
            $this->success_json($result);
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * SKU 降本导出CSV格式添加到下载中心
     * @METHOD POST
     * @author:luxu
     * @time:2020/5/23
     **/
    public function reduced_export_data(){
        $clientData= [];
        if( !empty($_POST))
        {

            foreach( $_POST as $key=>$value )
            {

                $clientData[$key] = $this->input->get_post($key);
            }
        }
        if( isset($_POST['ids']) && !empty($_POST['ids'])) {
            $ids = $this->input->get_post('ids');
            $clientData['ids'] = explode(",", $ids);
        }

        if( isset($clientData['group_ids']) && !empty($clientData['group_ids'])){

            $this->load->model('user/User_group_model', 'User_group_model');
            $groupids = $this->User_group_model->getGroupPersonData($clientData['group_ids']);
            $groupdatas = [];
            if(!empty($groupids)){
                $groupdatas = array_column($groupids,'value');
            }

            $clientData['groupdatas'] = $groupdatas;
        }

        $clientData['offset'] =1;
        $clientData['limit'] = 1;
        $result = $this->reduced_edition->get_reduced_detail( $clientData );
        $total = $result['page_data']['total'];
        try {
            $this->load->model('system/Data_control_config_model');
            $ext = 'csv';
            //$result = True;
            $clientData['role_name'] = get_user_role();
            $result = $this->Data_control_config_model->insertDownData($clientData, 'REDUCED', 'SKU降本数据', getActiveUserName(), $ext, $total);
        } catch (Exception $exp) {
            $this->error_json($exp->getMessage());
        }
        if ($result) {
            $this->success_json("添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }

    }


    /**
     * SKU 降本数据从MONGODB 导出
     *
     **/
    public function reduced_export_data_10()
    {
        ini_set('memory_limit','4000M');
        ini_set('max_execution_time','9000');
        $clientData = [];
        if( !empty($_POST))
        {

            foreach( $_POST as $key=>$value )
            {

                $clientData[$key] = $this->input->get_post($key);
            }
        }

        if( isset($_POST['ids']) && !empty($_POST['ids'])) {
            $ids = $this->input->get_post('ids');
            $clientData['ids'] = explode(",",$ids);
        }
        $this->_ci = get_instance();
        $this->_ci->load->config('mongodb');
        $host = $this->_ci->config->item('mongo_host');
        $port = $this->_ci->config->item('mongo_port');
        $user = $this->_ci->config->item('mongo_user');
        $password = $this->_ci->config->item('mongo_pass');
        $author_db = $this->_ci->config->item('mongo_db');
        $mongdb_object = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $filter = $this->get_mongdb_key($clientData);

        $command =  new MongoDB\Driver\Command(['count' => 'reduced_detail','query'=>$filter]);
        $result = $mongdb_object->executeCommand($author_db,$command)->toArray();
        $total =$result[0]->n;
        $limit  = 1000;
        $page = ceil($total/$limit);
        $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        //$file_name = 'reduced_edition.csv';
        $file_name = 'SKU降本'.date('YmdHis').rand(1000, 9999).'.csv';
        $reduced_file = $webfront_path.'/webfront/download_csv/'.$file_name;
        if (file_exists($reduced_file)) {
            unlink($reduced_file);
        }
        fopen($reduced_file,'w');
        $fp = fopen($reduced_file, "a");
        $heads = [
            '优化人','部门','职位','工号','SKU','产品名称','供应商','价格变化时间','首次计算时间',
            '近6个月最低价','入库时间','原价','现价','价格变化幅度','降本比例','采购数量','入库数量',
            '价格变化金额1','取消数量','有效采购数量','价格变化金额2', '结束统计时间','是否结束统计','订单是否有效',
            '采购单号','备货单号','采购员','下单时间','采购订单状态',  '是否叠加','订单完结时间','是否新模块数据','是否代采'
        ];
        foreach($heads as $key => $item) {
            $title[$key] =iconv('UTF-8', 'GBK//IGNORE', $item);
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);
        for( $i=1;$i<=$page;++$i) {
            $options['skip'] = ($i-1) * $limit;
            $options['limit'] = $limit;
            $query = new MongoDB\Driver\Query($filter,$options);
            $mongodb_result =$mongdb_object->executeQuery("{$author_db}.reduced_detail", $query)->toArray();
            $result = $this->reduced_edition->get_reduced_mongodb_data($mongodb_result);
            foreach($result as $value) {
                $v_value_tmp = [];
                $v_value_tmp['optimizing_user'] = iconv('UTF-8', 'GBK//IGNORE', $value['optimizing_user']);
                $v_value_tmp['department'] = (isset($value['department']))?iconv('UTF-8', 'GBK//IGNORE', $value['department']):'';
                $v_value_tmp['position'] = ( isset( $value['position']))?iconv('UTF-8', 'GBK//IGNORE', $value['position']):'';
                $v_value_tmp['caff_code'] = (isset($value['job_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['job_number']):'';
                $v_value_tmp['sku'] = iconv('UTF-8', 'GBK//IGNORE', $value['sku']);
                $v_value_tmp['product_name'] = iconv('UTF-8', 'GBK//IGNORE', $value['product_name']);
                $v_value_tmp['supplier_name'] = iconv('UTF-8', 'GBK//IGNORE', $value['supplier_name']);
                $v_value_tmp['price_change_time'] = (isset($value['price_change_time']))?$value['price_change_time']:'';
                $v_value_tmp['first_calculation_time'] = iconv('UTF-8', 'GBK//IGNORE',$value['first_calculation_time']);
                $v_value_tmp['six_minimum_price'] =  (isset($value['six_minimum_price']))?$value['six_minimum_price']:0;
                $v_value_tmp['instock_date'] = (isset($value['instock_date']))?$value['instock_date']:'';
                $v_value_tmp['original_price'] = $value['old_price'];
                $v_value_tmp['present_price'] = $value['new_price'];
                $v_value_tmp['price_change'] = $value['range_price'];
                $reduced_proportion = $value['old_price'] != 0 ? (round(($value['new_price'] - $value['old_price']) / $value['old_price'], 4) * 100) : 0;
                $reduced_proportion = (round($reduced_proportion, 2) . '%');
                $v_value_tmp['reduced_proportion'] = $reduced_proportion;

                $v_value_tmp['purchase_quantity'] = (isset($value['purchase_num']))?$value['purchase_num']:'';//采购数量
                $v_value_tmp['instock_qty'] = (isset($value['instock_qty']))?iconv('UTF-8', 'GBK//IGNORE', $value['instock_qty']):'';// 总入库数量
                // $v_value_tmp['breakage_number'] = (isset($value['breakage_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['breakage_number']):NULL;
                //$v_value_tmp['actual_number'] =  (isset($value['actual_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['actual_number']):NULL; //实际入库数量
                //价格变化金额：采购数量*价格变化幅度
                $v_value_tmp['price_change_total_1'] = (isset($value['price_change_1']))?$value['price_change_1']:'';
                $v_value_tmp['cancel_ctq'] = (isset($value['cancel_ctq']))?$value['cancel_ctq']:'';//取消数量
                $v_value_tmp['effective_purchase_num'] = (isset($value['effective_purchase_num']))?$value['effective_purchase_num']:''; // 有效采购数量
                $v_value_tmp['price_change_total_2'] = (isset($value['price_change_2']))?$value['price_change_2']:'';
                $v_value_tmp['end_time'] = (isset($value['end_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['end_time']):'';// 结束统计时间
                $v_value_tmp['is_end_name'] = (isset($value['is_end_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_end_name']):'';//是否结束统计
                $v_value_tmp['is_effect_name'] = (isset($value['is_effect_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_effect_name']):'';

                $v_value_tmp['purchase_number'] =  (isset($value['purchase_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_number']):'';
                $v_value_tmp['demand_number'] = (isset($value['demand_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['demand_number']):'';//备货单号

                $v_value_tmp['buyer_name'] = (isset($value['puchare_person']))?iconv('UTF-8', 'GBK//IGNORE', $value['puchare_person']):'';
                $v_value_tmp['create_time'] = (isset($value['product_audit_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['product_audit_time']):'';
                $v_value_tmp['purchase_status_mess'] = (isset($value['purchase_status_mess']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_status_mess']):'';

                $v_value_tmp['is_superposition_name'] = (isset($value['is_superposition_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_superposition_name']):'';
                $v_value_tmp['completion_time'] = ( isset($value['completion_time']))?iconv('UTF-8', 'GBK//IGNORE',$value['completion_time']):NULL;
                $v_value_tmp['is_new_data_ch'] = ( isset($value['is_new_data_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_new_data_ch']):NULL;
                $v_value_tmp['is_purchasing_ch'] = ( isset($value['is_purchasing_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_purchasing_ch']):NULL;
                fputcsv($fp, $v_value_tmp);
            }
            //每1万条数据就刷新缓冲区
            ob_flush();
            flush();
        }

        $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
        $down_file_url=$down_host.'download_csv/'.$file_name;
        $this->success_json($down_file_url);
    }

    function get_mongdb_key($clientData)
    {
        $filter = [];
        // 如果传入优化人
        if( isset($clientData['person_name']) && !empty($clientData['person_name']))
        {
            $filter['person_name'] = "{$clientData['person_name']}";
        }

        // 如果传入ID

        if( isset($clientData['ids']) && !empty($clientData['ids']))
        {
            $filter['id'] = array('$in'=>$clientData['ids']);

        }

        // 传入供应商

        if( isset($clientData['supplier_code']) && !empty($clientData['supplier_code']))
        {
            $filter['supplier_code'] = "{$clientData['supplier_code']}";
        }
        // 传入SKU
        if( isset( $clientData['sku']) && !empty($clientData['sku']) )
        {
            if (count($clientData['sku']) == 1 && !empty($clientData['sku'][0])) {  //单个sku时使用模糊搜索
                $filter['sku'] = $clientData['sku'][0];
            } else if(count($clientData['sku']) > 1) {
                $filter['sku'] = array('$in'=>$clientData['sku']);
            }
        }

        // 传入价格变化的开始时间
        if( (isset($clientData['price_change_end_time']) && !empty($clientData['price_change_end_time'])) &&
            isset($clientData['price_change_start_time']) && !empty($clientData['price_change_start_time']) ){
            $filter['price_change_time'] = array('$gte'=>$clientData['price_change_start_time'],'$lte'=>$clientData['price_change_end_time']);

        }

        //下单时间
        if( isset($clientData['audit_time_start'])  && !empty($clientData['audit_time_start'])

            && isset($clientData['audit_time_end']) && !empty($clientData['audit_time_end'])
        ){
            $filter['product_audit_time'] = array('$gte'=>$clientData['audit_time_start'],'$lte'=>$clientData['audit_time_end']);
        }

        //采购单号
        //purchase_number
        if( isset($clientData['purchase_number']) && !empty($clientData['purchase_number']))
        {
            $filter['purchase_number'] = array('$in'=>$clientData['purchase_number']);
        }

        // 备货单
        if( isset($clientData['demand_number']) && !empty($clientData['demand_number']))
        {
            $filter['demand_number'] = array('$in'=>$clientData['demand_number']);
        }

        // 采购数量
        if( isset($clientData['purchase_num']) && !empty($clientData['purchase_num']) )
        {
            // 表示不等于0
            if( $clientData['purchase_num'] == 2 )
            {
                $filter['purchase_num_flag'] = 1;
            }else{
                // 等于0
                $filter['purchase_num_flag'] = 0;
            }
        }
        // 入库时间

        if( isset($clientData['warehouse_start_time']) && !empty($clientData['warehouse_start_time'])
           && isset($clientData['warehouse_end_time']) && !empty($clientData['warehouse_end_time'])
        )
        {
            $filter['instock_date'] = array('$gte'=>$clientData['warehouse_start_time'],'$lte'=>$clientData['warehouse_end_time']);
        }

        // 首次计算开始时间

        if( isset($clientData['first_calculation_start_time']) && !empty($clientData['first_calculation_start_time']) &&
            isset($clientData['first_calculation_end_time']) && !empty($clientData['first_calculation_end_time']) )
        {
            $filter['first_calculation_time'] = array('$gte'=>$clientData['first_calculation_start_time'],'$lte'=>$clientData['first_calculation_end_time']);
        }

        //是否叠加
        if( isset($clientData['is_superposition']) && !empty($clientData['is_superposition']) )
        {
            $filter['is_superposition'] = "{$clientData['is_superposition']}";
        }

        // 价格变化趋势
        if( isset($clientData['gain']) && !empty($clientData['gain']) )
        {
            // 表示涨价
            if( $clientData['gain'] == 1 )
            {
                $filter['range_price'] = array('$gte'=>'0');
            }else{
                // 降价
                $filter['range_price'] = array('$lte'=>'0');
            }
        }
        // 订单是否有效

        if( isset($clientData['is_effect']) && !empty($clientData['is_effect']))
        {
            $filter['is_effect'] = "{$clientData['is_effect']}";
        }

        // 是否结束统计

        if( isset($clientData['is_end']) && !empty($clientData['is_end']) )
        {
            // 表示结束
            if( $clientData['is_end'] == 1 )
            {
                $filter['purchase_order_status'] = array('$in'=>["9","11","14"]);
            }else{
                //未结束
                $filter['purchase_order_status'] = array('$nin'=>["9","11","14"]);
            }
        }

        //订单完结时间stock_owes

        if( isset($clientData['completion_time_start']) && !empty($clientData['completion_time_start'])

            && isset($clientData['completion_time_end']) && !empty($clientData['completion_time_end']))
        {
            //11,9,14
            $filter['purchase_order_status'] = array('$in'=>["9","11","14"]);
            $filter['completion_time'] = array('$gte'=>$clientData['completion_time_start'],'$lte'=>$clientData['completion_time_end']);
        }

        // 是否新模块数据

        if( isset($clientData['is_new_data']) && !empty($clientData['is_new_data']))
        {
            $filter['is_new_data'] = "{$clientData['is_new_data']}";
        }

        //是否代采

        if( isset($clientData['is_purchasing']) && !empty($clientData['is_purchasing']))
        {
            $filter['is_purchasing'] = "{$clientData['is_purchasing']}";
        }

        return $filter;
    }


    /**
     * SKU 降本导出
     * @author: luxu
     **/
    public function reduced_export_data_1()
    {
        try
        {
            ini_set('memory_limit','4000M');
            set_time_limit(0);
            $clientData = [];
            if( !empty($_POST))
            {

                foreach( $_POST as $key=>$value )
                {

                    $clientData[$key] = $this->input->get_post($key);
                }
            }

            if( isset($_POST['ids']) && !empty($_POST['ids']))
            {
                if( !empty($clientData)) {

                    unset($clientData);
                }

                $ids = $this->input->get_post('ids');
                $clientData['ids'] = explode(",",$ids);
            }

            $filter = $this->get_mongdb_key($clientData);

            $order_sum =10000;
            //前端路径
            $webfront_path = dirname(dirname(dirname(dirname(dirname(dirname(__FILE__))))));
            //$file_name = 'reduced_edition.csv';
            $file_name = 'SKU降本'.date('YmdHis').rand(1000, 9999).'.csv';
            $reduced_file = $webfront_path.'/webfront/download_csv/'.$file_name;
            if (file_exists($reduced_file)) {
                unlink($reduced_file);
            }
            fopen($reduced_file,'w');
            $fp = fopen($reduced_file, "a");
            $heads = [
                        '优化人','部门','职位','工号','SKU','产品名称','供应商','价格变化时间','首次计算时间',
                        '近6个月最低价','入库时间','原价','现价','价格变化幅度','降本比例','采购数量','入库数量',
                        '价格变化金额1','取消数量','有效采购数量','价格变化金额2', '结束统计时间','是否结束统计','订单是否有效',
                        '采购单号','备货单号','采购员','下单时间','采购订单状态',  '是否叠加','订单完结时间','模块类型'
            ];
            foreach($heads as $key => $item) {
                $title[$key] =iconv('UTF-8', 'GBK//IGNORE', $item);
            }
            //将标题写到标准输出中
            fputcsv($fp, $title);
            if($order_sum>=1){
                $clientData['limit'] = 50;
                for ($i=1; $i <=ceil($order_sum/ $clientData['limit']) ; $i++) {
                    $clientData['offset'] = $i;
                    $orders_list_info = $this->reduced_edition->get_reduced_detail($clientData);
                    $delivery_list = $orders_list_info['list'];
                    foreach ($delivery_list as $key => $value) {
                        $v_value_tmp = [];
                        $v_value_tmp['optimizing_user'] = iconv('UTF-8', 'GBK//IGNORE', $value['optimizing_user']);
                        $v_value_tmp['department'] = (isset($value['department']))?iconv('UTF-8', 'GBK//IGNORE', $value['department']):'';
                        $v_value_tmp['position'] = ( isset( $value['position']))?iconv('UTF-8', 'GBK//IGNORE', $value['position']):'';
                        $v_value_tmp['caff_code'] = (isset($value['job_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['job_number']):'';
                        $v_value_tmp['sku'] = iconv('UTF-8', 'GBK//IGNORE', $value['sku']);
                        $v_value_tmp['product_name'] = iconv('UTF-8', 'GBK//IGNORE', $value['product_name']);
                        $v_value_tmp['supplier_name'] = iconv('UTF-8', 'GBK//IGNORE', $value['supplier_name']);
                        $v_value_tmp['price_change_time'] = (isset($value['price_change_time']))?$value['price_change_time']:'';
                        $v_value_tmp['first_calculation_time'] = iconv('UTF-8', 'GBK//IGNORE',$value['first_calculation_time']);
                        $v_value_tmp['six_minimum_price'] =  (isset($value['six_minimum_price']))?$value['six_minimum_price']:0;
                        $v_value_tmp['instock_date'] = (isset($value['instock_date']))?$value['instock_date']:'';
                        $v_value_tmp['original_price'] = $value['old_price'];
                        $v_value_tmp['present_price'] = $value['new_price'];
                        $v_value_tmp['price_change'] = $value['range_price'];
                        $reduced_proportion = $value['old_price'] != 0 ? (round(($value['new_price'] - $value['old_price']) / $value['old_price'], 4) * 100) : 0;
                        $reduced_proportion = (round($reduced_proportion, 2) . '%');
                        $v_value_tmp['reduced_proportion'] = $reduced_proportion;

                        $v_value_tmp['purchase_quantity'] = (isset($value['purchase_num']))?$value['purchase_num']:'';//采购数量
                        $v_value_tmp['instock_qty'] = (isset($value['instock_qty']))?iconv('UTF-8', 'GBK//IGNORE', $value['instock_qty']):'';// 总入库数量
                       // $v_value_tmp['breakage_number'] = (isset($value['breakage_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['breakage_number']):NULL;
                        //$v_value_tmp['actual_number'] =  (isset($value['actual_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['actual_number']):NULL; //实际入库数量
                        //价格变化金额：采购数量*价格变化幅度
                        $v_value_tmp['price_change_total_1'] = (isset($value['price_change_1']))?$value['price_change_1']:'';
                        $v_value_tmp['cancel_ctq'] = (isset($value['cancel_ctq']))?$value['cancel_ctq']:'';//取消数量
                        $v_value_tmp['effective_purchase_num'] = (isset($value['effective_purchase_num']))?$value['effective_purchase_num']:''; // 有效采购数量
                        $v_value_tmp['price_change_total_2'] = (isset($value['price_change_2']))?$value['price_change_2']:'';
                        $v_value_tmp['end_time'] = (isset($value['end_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['end_time']):'';// 结束统计时间
                        $v_value_tmp['is_end_name'] = (isset($value['is_end_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_end_name']):'';//是否结束统计
                        $v_value_tmp['is_effect_name'] = (isset($value['is_effect_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_effect_name']):'';

                        $v_value_tmp['purchase_number'] =  (isset($value['purchase_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_number']):'';
                        $v_value_tmp['demand_number'] = (isset($value['demand_number']))?iconv('UTF-8', 'GBK//IGNORE', $value['demand_number']):'';//备货单号
                        $v_value_tmp['buyer_name'] = (isset($value['puchare_person']))?iconv('UTF-8', 'GBK//IGNORE', $value['puchare_person']):'';
                        $v_value_tmp['create_time'] = (isset($value['product_audit_time']))?iconv('UTF-8', 'GBK//IGNORE', $value['product_audit_time']):'';
                        $v_value_tmp['purchase_status_mess'] = (isset($value['purchase_status_mess']))?iconv('UTF-8', 'GBK//IGNORE', $value['purchase_status_mess']):'';

                        $v_value_tmp['is_superposition_name'] = (isset($value['is_superposition_name']))?iconv('UTF-8', 'GBK//IGNORE', $value['is_superposition_name']):'';
                        $v_value_tmp['completion_time'] = ( isset($value['completion_time']))?iconv('UTF-8', 'GBK//IGNORE',$value['completion_time']):NULL;
                        $v_value_tmp['is_new_data_ch'] = ( isset($value['is_new_data_ch']))?iconv('UTF-8', 'GBK//IGNORE',$value['is_new_data_ch']):NULL;
                        fputcsv($fp, $v_value_tmp);
                    }
                    //每1万条数据就刷新缓冲区
                    ob_flush();
                    flush();
                }
            }
            $down_host = CG_SYSTEM_WEB_FRONT_IP; //前端域名
            $down_file_url=$down_host.'download_csv/'.$file_name;
            $this->success_json($down_file_url);
        }catch ( Exception $exp )
        {

        }
    }

    /**
     * 表示SKU 降本明细数据是新老数据
     * @author:luxu
     **/

    public function set_reduced_data()
    {
        try{

            $ids = $this->input->get_post('ids');
            $is_new_data = $this->input->get_post('is_new_data');
            if( empty($ids) || !is_array($ids) || empty($is_new_data))
            {
                throw new Exception("请传入正确的参数格式");
            }
            // 更新处理数据
            $result = $this->reduced_edition->set_reduced_data($ids,$is_new_data);
            if( $result == True)
            {
                $this->success_json("设置成功");
            }else{
                throw new Exception("设置失败");
            }

        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取SKU 降本设置新老模块日志数据
     * @author:luxu
     **/

    public function get_set_reduced_data_log()
    {
        try {
            $ids = $this->input->get_post('id');
            if( empty($ids))
            {
                throw new Exception("参数传入错误");
            }

            $result_logs = $this->reduced_edition->get_set_reduced_data_log($ids);
            $this->success_json($result_logs);
        }catch ( Exception $exp )
        {
            $this->error_json($exp->getMessage());
        }
    }

}