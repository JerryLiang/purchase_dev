<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * 权均交期定时计算控制器
 * User: Jaden
 * Date: 2019/01/17 
 */

class Delivery_api extends MY_API_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('arrival_record_model','arrival_record_model',false,'purchase');
        $this->load->model('purchase/delivery_log_model','delivery_log_model');
        $this->load->model('purchase/delivery_model','delivery_model');

    }
    /**
     * 同步采购记录以及PO单审核时间到权均交期日志表
     * /delivery_api/get_arrival_record_list
     * @author Jaden 2019-1-17
    */
    public function get_arrival_record_list($start_time='',$end_time=''){
//        echo phpinfo();exit;
//        $start_time = $this->input->get_post('start_time');
//        $end_time = $this->input->get_post('end_time');
        if(empty($start_time) || empty($end_time)){
            $start_time = date("Y-m-d",strtotime("-1 day"))." 00:00:00";
            $end_time = date("Y-m-d",strtotime("-1 day"))." 23:59:59";
        }else{
            $start_time = $start_time;
            $end_time = $end_time;
        }
//        $start_time = strtotime($start_time);
//        $end_time = strtotime($end_time);
        set_time_limit(0);
        $where = 'p.purchase_order_status>=6 and a.is_check_inland=0 and p.purchase_order_status!=14 and a.create_time>="'.$start_time.'" and a.create_time<="'.$end_time.'"';
        //$total = $this->db->select('id')->from('arrival_record')->where('is_check_inland=0')->count_all_results();
        $total =$this->db->select('a.id')
                ->from('warehouse_results as a')
                ->join('purchase_order as p', 'a.purchase_number=p.purchase_number', 'left')
                ->join('purchase_compact_items as c', 'a.purchase_number=c.purchase_number', 'left')
                ->where($where)->count_all_results();
        $limit = 500;
        $num = ceil($total/$limit);
        echo 'total:'.$num."\n";
        $field = 'a.id,a.purchase_number as purchase_number,a.sku,a.quality_time,p.supplier_name,p.purchase_type_id,p.warehouse_code,p.audit_time,p.source,c.create_time,p.waiting_time,p.purchase_order_status';
        //$arrival_list = $this->arrival_record_model->get_arrival_record_list_by_where($where,$limit,$offset,$field);
        if($total>=1){
            for ($i=1; $i <= $num; $i++) {
                echo 'cur:'.$i;
                $delivery_log_arr = array();
                $offset = ($i - 1) * $limit;
                $arrival_list = $this->delivery_log_model->get_arrival_record_list_by_where($where,$limit,$offset,$field);
                if(!empty($arrival_list)){
                    foreach ($arrival_list as $key => $value) {
                        $add_arr = array();
                        $delivery_log_info = $this->delivery_log_model->get_delivery_log_info($value['purchase_number'],$value['sku']);
                        if(!empty($delivery_log_info)){
                            continue;
                        }

//                        if($value['purchase_order_status']==6){//业务线 国内/FBA的合同审核时变成等待到货而海外变成 待生成进货单
                                                               // 所以时间获取 audit_time
                                                               // 网采与国内/FBA的合同单获取等待到货时间waiting_time totoro
                            $audit_time = $value['audit_time'];
//                        }else{
//                            $audit_time = $value['waiting_time'];
//                        }
                        $add_arr['purchase_number'] = $value['purchase_number'];
                        $add_arr['sku'] = $value['sku'];
                        $add_arr['supplier_name'] = $value['supplier_name'];
                        $add_arr['warehouse_code'] = $value['warehouse_code'];
                        $add_arr['purchase_type_id'] = $value['purchase_type_id'];
                        $add_arr['check_time'] = $value['quality_time'];//质检时间
                        $add_arr['audit_time'] = $audit_time;
                        $add_arr['create_time'] = date('Y-m-d H:i:s');
                        $add_arr['system_at'] = 1;
                        array_push($delivery_log_arr,$add_arr);
                        $this->db->where('id="'.$value['id'].'"')->update('warehouse_results', array('is_check_inland'=>1));
                           
                    }
                    $this->delivery_log_model->insert_delivery_time_log_all($delivery_log_arr);
                    var_dump('OK');
                    echo "\n";
                }
                   
            }
            echo '全部执行完成';
        }else{
            var_dump('没有符合要求的数据');    
        }

    }
    /**
     * sku新增了某个采购仓库的权均交期时,或sku+采购仓库维度的权均交期态发生变化时，推入消息队列
     * @param array $data 权均交期数据
     * @param string $type 类型（insert-新增，update-更新）
     */
    private function _push_rabbitmq($data, $type)
    {
        //推入消息队列
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setExchangeName('SKU_AVG_DELIVERY_TIME_EX_NAME');
        $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
        //构造存入数据
        $push_data = [
            'sku' => $data['sku'],
            'warehouse_code' => $data['warehouse_code'],
            'avg_delivery_time' => $data['avg_delivery_time'],
            'type' => $type,
            'push_time' => time()
        ];
        //存入消息队列
        $mq->sendMessage($push_data);
    }

    /**
     * 计算权均交期
     * /delivery_api/calculation_delivery
     * @author Jaden 2019-1-17
    */
    public function calculation_delivery($limit = 200,$start_time='',$end_time=''){
        set_time_limit(0);
//        $start_time = $this->input->get_post('start_time');
//        $end_time = $this->input->get_post('end_time');
        if(empty($start_time) || empty($end_time)){
            $start_time = date("Y-m-d",strtotime("-1 day"))." 00:00:00";
            $end_time = date("Y-m-d",strtotime("-1 day"))." 23:59:59";

            // $start_time = date('Y-m-d 00:00:00');
            // $end_time = date('Y-m-d 23:59:59');
        }else{
            $start_time = $start_time;
            $end_time = $end_time;
        }
        $where = 'create_time>="'.$start_time.'" and create_time<="'.$end_time.'" and is_calculate=0';
        $field = 'sku,warehouse_code,purchase_type_id';
        $group = 'sku,warehouse_code';
//        $limit = 200;
        $delivery_time_log_list = $this->delivery_log_model->get_delivery_log_list_by_where($where,$group,$field,$limit);
        $delivery_arr = array();
        if(!empty($delivery_time_log_list)){
            echo "需处理数:".count($delivery_time_log_list)."\n";
            foreach ($delivery_time_log_list as $key => $value) {
                $n = $key%100;
                if(empty($n)){
                    echo $key."\n";
                }
//                $avgTimeTotal = $avgTime  = 0;
//                $avgTime_arr = array();
                $avg_where ='sku="'.$value['sku'].'" and warehouse_code="'.$value['warehouse_code'].'"';
                $total_num = $this->db->select('id')->from('sku_avg_delivery_time_log')->where($avg_where)->count_all_results();
                $new_total_num = $total_num;
                if($total_num>=3){
                    $total_num = 3;
                }else{
                    $total_num = $total_num;
                    //$old_total_num = (3-$total_num);
                }

                $avg_field = 'check_time,audit_time,purchase_number,sku,warehouse_code,supplier_name,system_at,purchase_type_id';
                $avgTime_arr = $this->delivery_log_model->get_delivery_log_list_by_where($avg_where,'',$avg_field,$total_num);

                //老采购系统权均交期
//                $post_data['sku'] = $value['sku'];
//                $post_data['warehouse_code'] = $value['warehouse_code'];
//                $post_data['purchase_type_id'] = $value['purchase_type_id'];
//                $post_data['total_num'] = 3;
//                $post_data['token'] = json_encode(stockAuth());
//                //获取老采购权均交期
//                $push_url = getConfigItemByName('api_config','old_purchase','sku-get-delivery_time');
//                $result = getCurlData($push_url,$post_data);
//                $result_arr = json_decode($result,true);
//                if(!empty($result_arr) and !empty($result_arr['success_list'])){
//                    $old_avgTime_arr = $result_arr['success_list'];
//                    $new_avgTime_arr = array_merge($old_avgTime_arr,$avgTime_arr);
//                }else{
                    $new_avgTime_arr = $avgTime_arr; 
//                }
                $calculation_time = $this->calculation_time($new_avgTime_arr);
                //$calculation_time = $this->calculation_time($avgTime_arr,$total_num);
                //如果在新系统入库次数不够3次，取老系统值
                /*
                if($total_num>=3){
                    $avgTime = $calculation_time;
                }else{
                    $post_data['sku'] = $value['sku'];
                    $post_data['warehouse_code'] = $value['warehouse_code'];
                    $post_data['purchase_type_id'] = $value['purchase_type_id'];
                    $post_data['total_num'] = $total_num;
                    $post_data['token'] = json_encode(stockAuth());
                    //获取老采购权均交期
                    $push_url = getConfigItemByName('api_config','old_purchase','sku-get-delivery_time');
                    $result = getCurlData($push_url,$post_data);
                    
                    $old_avgTime = json_decode($result,true);//老采购系统权均交期
                    if(empty($old_avgTime) || $old_avgTime==0){
                        $avgTime = $calculation_time/$new_total_num;
                    }else{
                        $avgTime = ($old_avgTime+$calculation_time)/3;    
                    }   
                }
                */
                $add_arr['sku'] = $value['sku'];
                $add_arr['warehouse_code'] = $value['warehouse_code'];
                $add_arr['purchase_type_id'] = $value['purchase_type_id'];
                $add_arr['avg_delivery_time'] = $calculation_time;
                $add_arr['statistics_date'] = date('Y-m-d H:i:s');
                $delivery_info = $this->delivery_model->get_delivery_info($value['warehouse_code'],$value['sku']);
                if(empty($delivery_info)){
                    $result = $this->delivery_model->insert_delivery_info($add_arr);
                    if($result){
                        //推入消息队列
                        $this->_push_rabbitmq($add_arr, 'insert');
                    }
                }else{
                    $result = $this->delivery_model->update_delivery_info($value['warehouse_code'],$value['sku'],$add_arr);
                    if($result){
                        //推入消息队列
                        $this->_push_rabbitmq($add_arr, 'update');
                    }
                }
                $this->db->where('warehouse_code="'.$value['warehouse_code'].'" and sku="'.$value['sku'].'"')->update('sku_avg_delivery_time_log', array('is_calculate'=>1));
            }
            var_dump('OK');    
        }else{
            var_dump('暂无数据');
        }
        
    }


    //计算平均时间
    public function calculation_time($avgTime_arr){
        $sort_avgTime_arr = $this->arraySequence($avgTime_arr,'check_time');
        $total_num = count($sort_avgTime_arr);
        if($total_num>=3){
            $total_num = 3;
        }else{
            $total_num = $total_num;
        }
        $avgTimeTotal = $avgTime  = 0;
        if(!empty($sort_avgTime_arr)){
            foreach ($sort_avgTime_arr as $key => $value) {
                $delivery_time_log_items_arr = array();
                if($key>2){
                    continue;
                }
                $avgTimeTotal += strtotime($value['check_time'])-strtotime($value['audit_time']);
                //插入明细
                if(isset($value['system_at'])){
                   $system_at = $value['system_at'];
                }else{
                   $system_at = 2; 
                }
                $delivery_time_log_items_arr['purchase_number'] = $value['purchase_number'];
                $delivery_time_log_items_arr['sku'] = $value['sku'];
                $delivery_time_log_items_arr['supplier_name'] = $value['supplier_name'];
                $delivery_time_log_items_arr['warehouse_code'] = $value['warehouse_code'];
                $delivery_time_log_items_arr['purchase_type_id'] = $value['purchase_type_id'];
                $delivery_time_log_items_arr['check_time'] = $value['check_time'];
                $delivery_time_log_items_arr['audit_time'] =  $value['audit_time'];
                $delivery_time_log_items_arr['create_time'] = date('Y-m-d H:i:s');
                $delivery_time_log_items_arr['system_at'] = $system_at;
                $this->db->insert('sku_avg_delivery_time_log_items', $delivery_time_log_items_arr);


            }
            $avgTime = $avgTimeTotal/$total_num;
        }
        return $avgTime; 
    }

    //二维数组根据某个值排序
    public function arraySequence($array, $field, $sort = 'SORT_DESC')
    {
        $arrSort = array();
        foreach ($array as $uniqid => $row) {
            foreach ($row as $key => $value) {
                $arrSort[$key][$uniqid] = $value;
            }
        }
        array_multisort($arrSort[$field], constant($sort), $array);
        return $array;
    }



    /**
     * 采购单标记是否逾期(计算出预计逾期时间)
     * /delivery_api/get_order_list_overdue
     * @author Jaden 2019-1-17
     * 是否逾期交货的标记时间：N=变成等待到货的时间+（权均交期+20天）
    */    
   public function get_order_list_overdue(){
        $debug = $this->input->get_post('debug');
        set_time_limit(0);
        $where = 'p.purchase_order_status in(2,7,10,15) and p.purchase_type_id=1 and a.estimated_overdue_time=0';
        $total =$this->db->select('a.id')
                ->from('purchase_order_items as a')
                ->join('purchase_order as p', 'a.purchase_number=p.purchase_number', 'left')
                ->join('purchase_compact_items as c', 'a.purchase_number=c.purchase_number', 'left')
                ->where($where)->count_all_results(); 
        if($debug){
            echo $total.'<br>';
        }        
        $limit = 500; 
        $num = ceil($total/$limit);
        $field = 'a.id,a.purchase_number,a.sku,p.warehouse_code,p.audit_time,c.create_time,p.source';
        if($total>=1){
            for ($i=1; $i <= $num; $i++) {
                $delivery_log_arr = array();
                $offset = ($i - 1) * $limit;
                $order_list = $this->db->select($field)
                    ->from('purchase_order_items as a')
                    ->join('purchase_order as p', 'a.purchase_number=p.purchase_number', 'left')
                    ->join('purchase_compact_items as c', 'a.purchase_number=c.purchase_number', 'left')
                    ->where($where)
                    ->limit($limit,$offset)
                    ->order_by('a.id ASC')
                    ->get()->result_array();
                    if($debug){
                        echo $this->db->last_query();
                    }
                    
                if($debug){
                    echo '<pre>';
                    print_r($order_list); 
                }
                if(!empty($order_list)){
                    foreach ($order_list as $key => $value) {
                        //合同单，等待到货时间取生成合同时间，其他取审核时间
                        if($value['source'] == 1){
                            $waiting_time = $value['create_time'];
                        }else{
                            $waiting_time = $value['audit_time'];
                        }

                        if(empty($waiting_time) || $waiting_time=='0000-00-00 00:00:00'){
                            continue;
                        }
                        if($debug){
                            echo '等待到货时间:'.$waiting_time.'<br>';
                        }
                        //根据仓库和SKU获取权均交期时间
                        $delivery_time_info = $this->db->select('avg_delivery_time')
                                                   ->from('sku_avg_delivery_time')
                                                   ->where('sku',$value['sku'])
                                                   ->where('warehouse_code',$value['warehouse_code'])
                                                   ->get()->row_array();

                        if( !empty($delivery_time_info) ){
                            $avg_delivery_time = $delivery_time_info['avg_delivery_time'];
                        }else{
                            $avg_delivery_time = 0;
                        }
                        $date_str_arr = $this->dataformat($avg_delivery_time);
                        //N=变成等待到货的时间+（权均交期+20天）
                        $day = $date_str_arr['day']+20;
                        $date_str = $date_str_arr['date_str'];
                        //把剩余时分秒转换成时间戳
                        $surplus_time = strtotime($date_str) - strtotime('today');
                        $endtime = strtotime("$waiting_time+$day day")+$surplus_time;
                        $day_endtime = date('Y-m-d H:i:s',$endtime);
                        if($debug){
                            echo $value['purchase_number'].'------'.$value['sku'].'<br>';
                            echo  $day_endtime;exit;
                        }
                        //记录逾期到货时间
                        $this->db->where('id="'.$value['id'].'"')->update('purchase_order_items', array('estimated_overdue_time'=>$day_endtime));    
                        /*
                        $arrival_record_info = $this->db->select('check_time')
                                   ->from('arrival_record')
                                   ->where('sku',$value['sku'])
                                   ->where('purchase_order_no',$value['purchase_number'])
                                   ->where('check_time>=', $day_endtime)
                                   ->get()->row_array();
                        if(empty($arrival_record_info)){
                            $this->db->where('id="'.$value['id'].'"')->update('purchase_order_items', array('is_overdue'=>1));    
                        }*/         


                    }
                var_dump('OK');    
                }
                   
            }
        }else{
            var_dump('没有符合要求的数据');    
        }  
   }

   /**
     * 标记逾期
     * /delivery_api/calculation_order_overdue
     * @author Jaden 2019-1-17
    */
   public function calculation_order_overdue(){
        set_time_limit(0);
        $start_time = $this->input->get_post('start_time');
        $end_time = $this->input->get_post('end_time');
        if(empty($start_time) || empty($end_time)){
            $start_time = date("Y-m-d",strtotime("-1 day"))." 00:00:00";
            $end_time = date("Y-m-d",strtotime("-1 day"))." 23:59:59";
            // $start_time = date('Y-m-d 00:00:00');
            // $end_time = date('Y-m-d 23:59:59');
        }else{
            $start_time = $start_time;
            $end_time = $end_time;
        }
        $where = 'a.estimated_overdue_time>="'.$start_time.'" and a.estimated_overdue_time<="'.$end_time.'"';
        $total =$this->db->select('a.id')
                ->from('purchase_order_items as a')
                ->join('purchase_order as p', 'a.purchase_number=p.purchase_number', 'left')
                ->where($where)->count_all_results();
        $limit = 500; 
        $num = ceil($total/$limit);
        $field = 'a.id,p.purchase_number,a.sku,p.purchase_order_status,a.estimated_overdue_time';

        if($total>=1){
            for ($i=1; $i <= $num; $i++) {
                $delivery_log_arr = array();
                $offset = ($i - 1) * $limit;
                $order_list = $this->db->select($field)
                    ->from('purchase_order_items as a')
                    ->join('purchase_order as p', 'a.purchase_number=p.purchase_number', 'left')
                    ->where($where)
                    ->limit($limit,$offset)
                    ->order_by('a.id ASC')
                    ->get()->result_array();
                if(!empty($order_list)){
                    foreach ($order_list as $key => $value) {
                        
                        $arrival_record_info = $this->db->select('quality_time')
                                   ->from('warehouse_results')
                                   ->where('sku',$value['sku'])
                                   ->where('purchase_number',$value['purchase_number'])
                                   ->where('quality_time<=', $value['estimated_overdue_time'])
                                   ->get()->row_array();
                        if(empty($arrival_record_info)){
                            $this->db->where('id="'.$value['id'].'"')->update('purchase_order_items', array('is_overdue'=>1));    
                        }   

                    }
                var_dump('OK');    
                }
                   
            }
        }else{
            var_dump('没有符合要求的数据');    
        }


   }




   /** 
     *      把秒数转换为时分秒的格式 
     *      @param Int $times 时间，单位 秒 
     *      @return array 
     */  
    function dataformat($num) {
        $date_str_arr = array();
        $day = floor($num/86400);
        $hour = floor( ($num-$day*86400)/3600 );
        $minute = floor( ($num-($day*86400+$hour*3600))/60 );
        $second = floor( ($num-($day*86400+$hour*3600+$minute*60))%60 );

        $date_str = $hour.':'.$minute.':'.$second;
        $date_str_arr['day'] = $day;
        $date_str_arr['date_str'] = $date_str;
        return $date_str_arr;
    }
/**
 * 获取权均交期小于0的sku
 *
 * @return array
 */
    public function get_lacktime_sku(){
        // $avg_field = 'check_time,audit_time,purchase_number,sku,warehouse_code,supplier_name,system_at,purchase_type_id';
        // $avgTime_arr = $this->delivery_log_model->get_delivery_log_list_by_where($avg_where,'',$avg_field,$total_num);
        $where = 'avg_delivery_time < 0';
        $query = $this->db->select('sku,warehouse_code,avg_delivery_time')
                ->from('sku_avg_delivery_time')
                ->where($where);
        // $count  = $query->count_all_results();
        $skus = $query->get()->result_array();
        // echo $count;exit;
                // >get()->array_result()
        return $skus;
        
    }

    public function get_audittime_sku(){
        $where = "audit_time = '0000-00-00 00:00:00'";
        $query = $this->db->select('purchase_number,sku,warehouse_code,is_calculate')
                ->from('sku_avg_delivery_time_log')
                ->where($where);
        // $count  = $query->count_all_results();
        $skus = $query->get()->result_array();
        // echo $count;exit;
        return $skus;
    }
/**
 * 删除旧日志
 *
 * @param [type] $param
 * @return void
 */
    function refresh_d_sku_deliveries(){
        $lacktime_sku = $this->get_lacktime_sku();
        $skus = array_column($lacktime_sku,'sku');
        // if(count($skus) == 0){
            // $sku_str = 'QCOT19000-7,QC23058,TJ04384,QC23027,QC20018,QC13782,QC23120,JM06071,JM10377-02,JM12864-09,JM12864-08,JM12864-05,JM12864-03,QC16535,JM12230-03,JM05616-01,JM01093-01,QC03908,QC08029,QC07326,QC08309-01,TJB01193-04,TJB01195-04,TJ08397-01,TJ14209-07,TJ08106,TJ04641-05,TJ08422-01,AF00767-02,YS03578,TJ05424,TJ04265-03,TJ05445,TJ05423,QC08125-02,QC07752,QC05747-05,QC09016,QC04721-01,QC17966,QC15876,QC11406,ZM01929-02,QC17469,QC10915,QC11474,QC24341,QC16280,QC15442,QC09296,QC17496,QC00189-02,QC05039,JM08299-01,JM15207-01,JM15007-01,JM18046-03,QC18895,JM16848-01,QC23591,JM05358,QC23182,JM04947-03,QC20814,QC20767,JM02286-01,QC19806,QC20114,JM18111-05,JM01415-03,JM00472,JM18111-04,QC23088,JM01904-03,QC09548-02,JMMJ20400,QC10984,JM14462-02,JM14617-01,JM15319,JM13073-01,JM16891-01,JM16883-02,JM03275-03,JM19788,JM13907-02,JM19746-01,JM09358,JM19715-03,JM08661,JM16886-02,JM07324-02,JM19907-02,JM12510,JM07999-02,QC07238,QC03410,QC09403-01,QC03358-02,QC04236,QC09571,QC02467,QC02734,QC05919,QC02710,QC20929,QC12731-02,QC04556,QC02769,QC25749,JM12864-01,JM12863-05,JM08656-08,JM04625-02,JM12122-04,QC13056,QC16660,QC15346,QC12336,QC23266,QC13633,QC13511,QC17289,JM15295,QC00678,QC18254,QC09014-03,QC07602,QC11261,TJ07600-04,TJA02107-01,QC03446,QC09567,QC09683,QC16798,QC07173-02,QC15760,QC05817,JM04090-03,JM14802,QC12783,TJ10166-01,QC18215-01,QC13082,QC13013,XD06789,JM16922,JM15257-05,JM21377-02,JM15257-03,JM15257-02,JM15257-01,JM15257-04,JM15257-05,JM03434-01,JM15257-02,JM10491-03,JM15261-01,JM07023,JM11941,QC14528,QC14586,JM12005,JM15206,JM01836-02,JM15259-05,JM15006-01,JM18555-02,JM20507,JM04670,QC02156,JM02945-03,JM02945-02,GB-JYA02299,QC00693,QC02463-01,TJB00523,TJOT72000,TJ08422-02,TJ07428-01,TJ07062-03,TJA03043,TJA02072-01,JM09690-01,TJ13920,TJ06682-01,TJ06957,TJ00280-01,AF00532-02,QC06309,AF00007-02,YS03486-07,YS03469-01,XD04698-04,JM09690-02,QC00830,QC07273-01,JM02943-01,JM02957-03,JM14030-03,JM02945-01,JM14021-03,QC17479,QC19863,QC19875,QC02733,QC02517,QC08276-02,TJ00280-01,JM16929,JM02415,JM10444-01,JY08041,JM01245-02,JM18464,YS03307-03,YS03384,YS02294-03,QCOT39300A,TJ10166-03,TJ00100-03,TJ06060-02,TJOT70905,TJ00100-01,TJB00748-01,TJ06816-01,TJ03474,TJHW6400,TJ14207,ZM02095-03,ZM02181-01,ZM00569,US-JYA02301,ZM02530,TJ00047-06,TJA01136-01,JM05077,JM11450,JM12902-02,JM01078,JM00099-03,JM01682,JM04600,JM01063,JM12902-01,JM11455,JM03014,YQ01001-01,TJA00960-03,ZM00921-01,TJ00172,JMOT34801,QC18005,QC02463-02,TJ03585-01,FS08248-08,BB02031-02,TJA02776,QC09744,QC08029,TJOT71700,ZM02406,JM00885,QC01415-04,BB04398-03,YS04519-03,TJ12879,QC11213,QC13831,QC04554-01,TJ12532,TJ05599-02,QC24437,DS04662-03,JYJJ55000-10,TJOT68591,QC16162,QC17789,QC06891,JM13373,QC02059,XD01667,QC16821,TJ11809-02,QC13874,ZM00559,TJ07363-01,TJ07811-04,QC11972,TJ07363-02,QC07846,XD07572,XD00513,TJA02442-01,JM15550,TJOT55700,TJ06800-02,TJ16558-03,JM08061,QC06663,XD00413,QC19821,TJ14228-02,TJ10509-01,JM15512,ZM00478,TJ05762,QC07835-02,QC24055,QC12702,TJ07115-01,QC16533,QC17106,CW01224,TJ13518-03,TJ10151-02,TJ08439,TJOT68508,ZM01515-05,QC13477,TJ08491,YS03307-01,QC01090-02,QC09409,TJOT58700LL,FS08364-08,QC11434,JM06410,QC22263,JY07745,QC18280,TJ07100-02,QC19637,TJ07369,YS03435-03,TJ16082,QC18317,TJ07502-02,TJ06910-01,QC05021,QC02882-02,TJ07100-03,TJ10509-02,BB02031-03,TJ10239-04,ZM00883,QC12766,TJ03855,TJ00322,QC16542,XD06227,QC15640,QC06585,JM06981,QC18044,YS00961-01,QC19898,SJ03332-01,JM01305-05,TJ05517-01,XD00042,QC16573,TJ08397-02,TJ11794-03,BB02018-01,YSOT5800,QC11252,QC17868-01,TJ10489-08,TJA00960-02,BB02030-05,BB04278-01,QT00355,TJ10509-01,JY02708-01,QC19741,TJ09382-01,QC18079,QC02425,QC02882-08,TJ11809-03,TJ00399-03,JM00301-02,TJ04381,QC17790,QC09014-04,QC15770,TJA01138-03,ZM01406-02,QC01072-01,BB02018-03,QC15774,JM01234-04,TJ06990,QC02084,QC13503,TJ14264,TJA00788,QC18052,TJ14281,QC06654,ZM01515-04,JM08691,TJ05423,TJB01133-01,QC09061,QC04948,TJ05168-01,JY05912,BB00563,QC08124,QC13727,TJ12329-02,TJ09147-02,JM01576,GS00495-01,QC21415,QC05775,JM01234-07,QC10460,QC12170,TJ07115-04,QC17235,JYA01241,QC15428-01,AF00171,QC06900,TJ10472-06,TJ10472-04,QC03397,QC05671-02,JM02943-03,TJ06055-02,TJA01075-03,JM01762,TJ14314,JM15538-02,JM03201-02,TJ11677-02,QC16359,BBA00382-01,TJ08192-01,QC15386,TJ14193,TJ14833-03,QC01367,ZM02196-03,QC14187,QC12177,QC21891,QC22322,JM14030-01,TJOT66408,TJ07363-04,JM01078,AF00924-03,QC10151,FS08255-10,TJA00960-02,QC05982,JM15495-01,QC07835-01,QC11393,TJ10489-05,DS06696-03,JM01765,QC19813,AF02835-01,QC16283,QC08315,JYB01506-05,TJB01538-03,JMOT11000LL,TJ14414-03,YSA00191,QC11426,TJ11569,TJA03164-01,JM02265,JM10476,TJ07363-04,JM01077,QC02882-01,JM01244-02,BB00775-03,JYA01420,QC11476,DS02887-02,QC17465,ZM00932,QC17876-01,TJ10411-03,JM01331,QC05770,QC21968,JM18804,ZM00263,JYA01617,JM08436,QC02419,TJ07311-04,XD02339,TJ11846-01,QC15732,JM01110-02,TJ09637-02,TJ03722-02,JM05322,JM10452-07,TJ05528,JYB02254-02,QC16559,JY05904,YS03433-03,QC01274-02,QC09238,QC10694,TJ11891-06,BBB00043-01,TJA01963,TJA00789-03,QC14248-01,YS03561,TJ11809-04,QC02585,JY07797,TJ07115-04,QC14189,QC10966,ZM01365,YS03656-05,TJ00453-02,FS08248-03,QC01282,QC08131-01,XD02818,FS08612-11,BBB00043-04,BC00022,BC00023,BZ00037,BC0017,BC0018,BC0019,BC0020,BC0022,JY06413-03,QC01108,QT00499-10,QT00499-12,BZ00054,BZ00075-03,QC00553,ZP21370,BC0091,SJ00968,GY08619-02,DS06439-02,XD02840-02,SJ00970,BZ00051,SJ00968,QC24959,QC35878,QC09155,YS04515-04,GS17932,GS05664,QC34794-04,1616190001011,QC33570,BZ00075-01,BZ00075-02,YS04518-01,YB00701,QC36751,QC01466,YS05655,PJ-QC06828,BC0054,QC25371,QC27795-02,QC31077,TJ06156-01,TJ06008-03,QC36055-02,QC27842,XD08261,1618190076311,CW06473-03,QC33655,GS20318-01,BC0023,AF00098-01,QC28869,QC34340,1618190147211,1618190118011,QC29805,1618190079311,JY11122-07,JM24950,JM24782,3115190067412,3115190067411,3115190067311,QC33412,ZP16462,QC36055-03,QC34946,JY04664,GS01213,3112190123811,QC04304-03,QC10989,QC35812,QC34946,QC01095,QC37965,QC34314-01,QC27735,QC19073,1618190055711,TJHW26608,XD07931,QC35690,QC35468,QC24808,1617190005311,QC35522,QC20089,QC36665,QC35414,US-GSOT44000-200,1110190044611,1616190052611,1613190005511,1618190055611,1618190242611,1613190001711,1618190012711,1618190224511,1618190154111,1616190064511,1618190223211,1618190058311,2610190063011,QC27974,1618190239512,1618190216511,1613190023911,1618190216211,1618190106911,QC25194,QC28300-03,QC28166,QC23329,1011190272211,JM05340,JM14823-03,JM02758-02,JM01875-01,JM09160-03,JM04831,CW00662-02,CW00703,JY13336-06,TJ18491,2717190018512,QC30957,QC27104,QC34578,QC30192,GS06409,GS00489,GS07929,GS00369,1618190244711,GS06140,QC30522-03,1411200137111,QC31593-02,QC03373-04,GS21518-03,GS21518-02,QC01885-04,QC28902,AF00510,JY13821-01,JY13743-03,XD07899,JY13743-02,DS08458,JM01762,US-JMOT5600,GS08453-02,1910190140412,3117200016613,1910190140112,3117200016611,QC30822,1910190117911,1011190282311,JYA03115,JY40406-03,JY08486-02,JY40418-01,JY40407-04,JY40407-01,CW01660,US-QC20037,TJ20618,3117200020311,3113200014411,GS10245-02,GS07848-01,1618190201111,1618190186011,DS00519,1618190240511,QC34826,TJ12442,GS08114,QC29395,1611200003712,AF01287,GS10015,TJ16565,2716190028214,TJ16565,2716190028211,2716190008111,JY13644-03,TJ07570-01,1618190076611,1614200006011,QC37373-02,2713190012312,1510200005112,1510200005012,1510200005011,YS03942,QCOT18900-7,QC00711-04,BZ00086,JY08840,JY30612,1815190007111,TJ10169-02,QC01413,3112190124811,JY04827,TJ04657,BB00775-01,BB01885-01,JY05512,QC00710-05,QC02110-02,QCOT19000-5,QC01745,QC01768,GS08110,GS03110-01';
            // $skus = explode(',',$sku_str);
            // $sku_str = implode(',',$skus);
        // }
        foreach($skus as $val){
            $this->db->delete('sku_avg_delivery_time_log',['sku' => $val]);// 删除旧日志
        }
        foreach($skus as $val){
            $this->db->delete('sku_avg_delivery_time',['sku' => $val]);// 删除权均交期日志
        }
        echo "done";
    }
/**
 * 权均交期小于0的sku加默认值
 *
 * @return void
 */
    
 
 function refresh_sku_deliveries(){
        $lacktime_sku = $this->get_lacktime_sku();
        $skus = array_column($lacktime_sku,'sku');
        // $data = $this->db->select('a.id')
        // ->from('sku_avg_delivery_time')
        // ->where('purchase_type_id',2)->where_in('sku',$skus)->count_all_results();

        // echo $data;exit;
     foreach($lacktime_sku as $sku){
         $this->db->where_in('purchase_type_id',[1,3,4])->where('sku',$sku['sku'])->where('warehouse_code',$sku['warehouse_code'])->update('sku_avg_delivery_time',['avg_delivery_time'=>604800]);
         $this->db->where('purchase_type_id',2)->where('sku',$sku['sku'])->where('warehouse_code',$sku['warehouse_code'])->update('sku_avg_delivery_time',['avg_delivery_time'=>3456000]);
     }

        echo "done";
    }

/**
 * 权均交期为负数的sku重新刷新日志
 *
 * @return void
 */
    function update_delivery_log_by_sku() {
        $lacktime_sku = $this->get_lacktime_sku();
        $skus = array_column($lacktime_sku,'sku');
        $skus = array_unique($skus);
        $sku_str = '';
        foreach($skus as $v1){
            $sku_str .= "'".$v1."',";
        }
        $sku_str = substr($sku_str,0,strlen($sku_str)-1);
        // var_dump($sku_str);exit;
        //        echo phpinfo();exit;
//        $start_time = $this->input->get_post('start_time');
//        $end_time = $this->input->get_post('end_time');
    $start_time = "2018-01-01 00:00:00";
    $end_time = date("Y-m-d",strtotime("-1 day"))." 23:59:59";
    set_time_limit(0);
    $where = "p.purchase_order_status>=6 and p.purchase_order_status!=14 and a.create_time>='".$start_time."' and a.create_time<='".$end_time."'
     and a.sku in (".$sku_str.")";

//$total = $this->db->select('id')->from('arrival_record')->where('is_check_inland=0')->count_all_results();
//id in('.$successed_res_str.')'
$total =$this->db->select('a.id')
        ->from('warehouse_results as a')
        ->join('purchase_order as p', 'a.purchase_number=p.purchase_number', 'left')
        ->join('purchase_compact_items as c', 'a.purchase_number=c.purchase_number', 'left')
        // ->where($where)->where_in('a.sku',$skus)->count_all_results();
        ->where($where)->count_all_results();
        // echo $this->db->last_query();exit;
$limit = 500;
$num = ceil($total/$limit);
echo 'total:'.$num."\n";
$field = 'a.id,a.purchase_number as purchase_number,a.sku,a.quality_time,p.supplier_name,p.purchase_type_id,p.warehouse_code,p.audit_time,p.source,c.create_time,p.waiting_time,p.purchase_order_status';
//$arrival_list = $this->arrival_record_model->get_arrival_record_list_by_where($where,$limit,$offset,$field);
if($total>=1){
    for ($i=1; $i <= $num; $i++) {
        echo 'cur:'.$i."\n";
        $delivery_log_arr = array();
        $offset = ($i - 1) * $limit;
        $arrival_list = $this->delivery_log_model->get_arrival_record_list_by_where($where,$limit,$offset,$field);
        if(!empty($arrival_list)){
            foreach ($arrival_list as $key => $value) {
                $add_arr = array();
                $delivery_log_info = $this->delivery_log_model->get_delivery_log_info($value['purchase_number'],$value['sku']);
                if(!empty($delivery_log_info)){
                    echo "po:".$value['purchase_number'].",sku:".$value['sku']."\n";
                    continue;
                }

                if($value['purchase_order_status']==6){//业务线 国内/FBA的合同审核时变成等待到货而海外变成 待生成进货单
                                                       // 所以时间获取 audit_time
                                                       // 网采与国内/FBA的合同单获取等待到货时间waiting_time totoro
                    $audit_time = $value['audit_time'];
                }else{
                    $audit_time = $value['waiting_time'];
                }
                $add_arr['purchase_number'] = $value['purchase_number'];
                $add_arr['sku'] = $value['sku'];
                $add_arr['supplier_name'] = $value['supplier_name'];
                $add_arr['warehouse_code'] = $value['warehouse_code'];
                $add_arr['purchase_type_id'] = $value['purchase_type_id'];
                $add_arr['check_time'] = $value['quality_time'];//质检时间
                $add_arr['audit_time'] = $audit_time;
                $add_arr['create_time'] = date('Y-m-d H:i:s');
                $add_arr['system_at'] = 1;
                array_push($delivery_log_arr,$add_arr);
                $this->db->where('id="'.$value['id'].'"')->update('warehouse_results', array('is_check_inland'=>1));
                   
            }
            echo count($delivery_log_arr)."\n";
            $this->delivery_log_model->insert_delivery_time_log_all($delivery_log_arr);
            var_dump('OK');
            echo "\n";
        }
           
    }
    echo '全部执行完成';
}else{
    var_dump('没有符合要求的数据');    
}
    }
}