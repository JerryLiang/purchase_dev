<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * Created by PhpStorm.
 * 推送采购需求和采购单到数据中心
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_api extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('user/purchase_user_model');
    }




    /**
     * 查询 推送采购需求数据到数据中心
     * @author Jaden
     /purchase_api/push_purchase_list
     */
    public function push_purchase_list(){
        die('功能已废弃-Jolon-2020-03-16');
        set_time_limit(0);
        $demand_number = $this->input->get_post('demand_number');
        $where = 'a.is_push=0 AND b.purchase_number!="" AND c.purchase_order_status=7';
        if(!empty($demand_number)){
            $where.=' AND a.demand_number="'.$demand_number.'"';
        }
        //查找总数
        $count_num = $this->db->select('a.id')
                    ->from('purchase_suggest as a')
                    ->join('purchase_suggest_map as b', 'a.demand_number=b.demand_number', 'left')
                    ->join('purchase_order as c', 'b.purchase_number=c.purchase_number', 'left')
                    ->where($where)
                    ->order_by('a.create_time DESC')
                    ->count_all_results();
        //读取配置文件参数，获取推送地址
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('service_data'))) {
            $service_data_info = $this->config->item('service_data');
            $_url_ip = isset($service_data_info['ip'])?$service_data_info['ip']:'';
            $_url_push_purchase_suggest = isset($service_data_info['push_purchase_suggest'])?$service_data_info['push_purchase_suggest']:'';
            if(empty($_url_ip) or empty($_url_push_purchase_suggest)){
                exit('推送地址缺失');
            }
            $push_url = $_url_push_purchase_suggest;
        }
        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 300;//每次推送300条
        }
        if($count_num>=1){
            try {
                for ($i=1; $i <=ceil($count_num/$limit) ; $i++) { 
                    $offset = ($i-1)*$limit;
                    $purchase_list = $this->db->select(
                        'a.id,
                        a.sku,
                        a.product_name,
                        b.confirm_number as purchase_quantity,
                        a.warehouse_code as purchase_warehouse,
                        a.warehouse_code as transit_warehouse,
                        a.is_transfer_warehouse as is_transit,
                        a.create_user_name as create_id,
                        a.create_time,
                        a.suggest_status,
                        a.demand_number,
                        a.product_category_id as product_category,
                        a.purchase_type_id as purchase_type,
                        a.sales_note,
                        a.buyer_name as buyer,
                        a.supplier_code,
                        a.logistics_type as transport_style,
                        a.destination_warehouse,
                        a.source_from,
                        b.purchase_number as pur_number,
                        c.purchase_order_status'
                    )
                    ->from('pur_purchase_suggest as a')
                    ->limit($limit,$offset)
                    ->join('purchase_suggest_map as b', 'a.demand_number=b.demand_number', 'left')
                    ->join('purchase_order as c', 'b.purchase_number=c.purchase_number', 'left')
                    ->where($where)
                    ->group_by('b.demand_number')
                    ->order_by('a.create_time DESC')
                    ->get()->result_array();

                    if(empty($purchase_list)){
                         var_dump('没有数据要推送') ;exit;
                    }
                    foreach ($purchase_list as $key => $value) {

                        //判断是否采购 2[已采购] 1[未采购]
                        if(in_array($value['purchase_order_status'], array(5,6,7,8,9,10,11,12,13))){
                            $purchase_list[$key]['is_purchase'] = 2;
                        }else{
                            $purchase_list[$key]['is_purchase'] = 1;
                        }
                        $purchase_list[$key]['level_audit_status'] = 1;

                        //转换物流类型
                        if ($value['transport_style'] == 'KJP1'){//空运
                            $purchase_list[$key]['transport_style'] = 57;//ERP 物流类型id
                        }elseif($value['transport_style'] == 'TJP1' ){//铁路散货
                            $purchase_list[$key]['transport_style'] = 15275;
                        }elseif($value['transport_style'] == 'LYSH'){//陆运
                            $purchase_list[$key]['transport_style'] = 16168;
                        }elseif($value['transport_style'] == 'HYSH'){//海运散货
                            $purchase_list[$key]['transport_style'] = 56;
                        }elseif($value['transport_style'] == 'HYZG'){//海运整柜
                            $purchase_list[$key]['transport_style'] = 15274;
                        }elseif($value['transport_style'] == 'TJP2'){//铁路整柜
                            $purchase_list[$key]['transport_style'] = 15273;
                        }else{
                            $purchase_list[$key]['transport_style'] = 0;
                        }

                        if($value['purchase_type'] == 2){//海外仓

                            $purchase_list[$key]['purchase_warehouse'] = $purchase_list[$key]['destination_warehouse'];

                        }else{
                            //海外仓以外,中专仓位空,warehouse_code为数据库本身值
                            $purchase_list[$key]['transit_warehouse'] = '';
                        }

                        $purchase_list[$key]['platform_number'] = 'PUBLIC';//平台号(新采购本身无此字段,数据中心推送特定仓库时需用到此字段,故写死)

                    }
                    $post_data['data'] = json_encode($purchase_list);

                    $post_data['token'] = json_encode(stockAuth());
                    $result = getCurlData($push_url,$post_data);
                    //file_put_contents('aaaaaaa.txt', $result);
                    $api_log=[
                         'record_number'=>$value['pur_number'],
                         'api_url'=>$push_url,
                         'record_type'=>'采购需求单推送到数据中心',
                         'post_content'=>$post_data['data'],
                         'response_content'=>$result,
                         'create_time'=>date('Y-m-d H:i:s')
                         ];
                        $this->db->insert('api_request_log',$api_log);

                    //改变推送状态
                    $_result = json_decode($result,true);
                    if (isset($_result['data']['success']) && !empty($_result['data']['success'])) {
                        $ids = $_result['data']['success'];
                        $this->db->where_in('id', $ids)->update('purchase_suggest',['is_push'=>1]);
                    } else {
                        var_dump($result);exit;
                    }
                    var_dump('OK');
                }    
            }catch (Exception $e) {
                exit($e->getMessage());
            }
            
        }else{
            var_dump('没有数据要推送') ;exit;  
        }
    }


    /**
     * 查询 推送采购需求数据到数据中心(临时解决需求单数量与采购单数量不一致问题)
     * @author Jaden
    /purchase_api/push_purchase_list
     */
    public function push_purchase_list_tmp(){
        die('功能已废弃-Jolon-2020-03-16');
        set_time_limit(0);
        $demand_number = $this->input->get_post('demand_number');
        $status = $this->input->get_post('status');

        $where = 'a.is_push=0 AND b.purchase_number!=""';
        if(!empty($demand_number)){
            $where.=' AND a.demand_number="'.$demand_number.'"';
        }
        if (!empty($status)){
            if($status==9){
                $where.= ' AND a.purchase_type_id!=2 AND c.purchase_order_status = '.$status;
            }else{
                $where.= 'AND c.purchase_order_status = '.$status;
            }

        }else{
            $where.= 'AND c.purchase_order_status in (7,8,10)';
        }
        //查找总数
        $count_num = $this->db->select('a.id')
            ->from('purchase_suggest as a')
            ->join('purchase_suggest_map as b', 'a.demand_number=b.demand_number', 'left')
            ->join('purchase_order as c', 'b.purchase_number=c.purchase_number', 'left')
            ->where($where)
            ->order_by('a.create_time DESC')
            ->count_all_results();

        //读取配置文件参数，获取推送地址
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('service_data'))) {
            $service_data_info = $this->config->item('service_data');
            $_url_ip = isset($service_data_info['ip'])?$service_data_info['ip']:'';
            $_url_push_purchase_suggest = isset($service_data_info['push_purchase_suggest'])?$service_data_info['push_purchase_suggest']:'';
            if(empty($_url_ip) or empty($_url_push_purchase_suggest)){
                exit('推送地址缺失');
            }
            $push_url = $_url_push_purchase_suggest;
        }
        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 300;//每次推送300条
        }
        if($count_num>=1){
            try {
                for ($i=1; $i <=ceil($count_num/$limit) ; $i++) {
                    $offset = ($i-1)*$limit;
                    $purchase_list = $this->db->select(
                        'a.id,
                        a.sku,
                        a.product_name,
                        b.confirm_number as purchase_quantity,
                        a.warehouse_code as purchase_warehouse,
                        a.warehouse_code as transit_warehouse,
                        a.is_transfer_warehouse as is_transit,
                        a.create_user_name as create_id,
                        a.create_time,
                        a.suggest_status,
                        a.demand_number,
                        a.product_category_id as product_category,
                        a.purchase_type_id as purchase_type,
                        a.sales_note,
                        a.buyer_name as buyer,
                        a.supplier_code,
                        a.logistics_type as transport_style,
                        a.destination_warehouse,
                        a.source_from,
                        b.purchase_number as pur_number,
                        c.purchase_order_status'
                    )
                        ->from('pur_purchase_suggest as a')
                        ->limit($limit,$offset)
                        ->join('purchase_suggest_map as b', 'a.demand_number=b.demand_number', 'left')
                        ->join('purchase_order as c', 'b.purchase_number=c.purchase_number', 'left')
                        ->where($where)
                        ->group_by('b.demand_number')
                        ->order_by('a.create_time DESC')
                        ->get()->result_array();

                    if(empty($purchase_list)){
                        var_dump('没有数据要推送') ;exit;
                    }
                    foreach ($purchase_list as $key => $value) {

                        //判断是否采购 2[已采购] 1[未采购]
                        if(in_array($value['purchase_order_status'], array(5,6,7,8,9,10,11,12,13))){
                            $purchase_list[$key]['is_purchase'] = 2;
                        }else{
                            $purchase_list[$key]['is_purchase'] = 1;
                        }
                        $purchase_list[$key]['level_audit_status'] = 1;

                        //转换物流类型
                        if ($value['transport_style'] == 'KJP1'){//空运
                            $purchase_list[$key]['transport_style'] = 57;//ERP 物流类型id
                        }elseif($value['transport_style'] == 'TJP1' ){//铁路散货
                            $purchase_list[$key]['transport_style'] = 15275;
                        }elseif($value['transport_style'] == 'LYSH'){//陆运
                            $purchase_list[$key]['transport_style'] = 16168;
                        }elseif($value['transport_style'] == 'HYSH'){//海运散货
                            $purchase_list[$key]['transport_style'] = 56;
                        }elseif($value['transport_style'] == 'HYZG'){//海运整柜
                            $purchase_list[$key]['transport_style'] = 15274;
                        }elseif($value['transport_style'] == 'TJP2'){//铁路整柜
                            $purchase_list[$key]['transport_style'] = 15273;
                        }else{
                            $purchase_list[$key]['transport_style'] = 0;
                        }

                        if($value['purchase_type'] == 2){//海外仓

                            $purchase_list[$key]['purchase_warehouse'] = $purchase_list[$key]['destination_warehouse'];

                        }else{
                            //海外仓以外,中专仓位空,warehouse_code为数据库本身值
                            $purchase_list[$key]['transit_warehouse'] = '';
                        }

                    }
                    $post_data['data'] = json_encode($purchase_list);
                    $post_data['token'] = json_encode(stockAuth());
                    $result = getCurlData($push_url,$post_data);
                    //file_put_contents('aaaaaaa.txt', $result);
                    $api_log=[
                        'record_number'=>$value['pur_number'],
                        'api_url'=>$push_url,
                        'record_type'=>'采购需求单推送到数据中心',
                        'post_content'=>$post_data['data'],
                        'response_content'=>$result,
                        'create_time'=>date('Y-m-d H:i:s')
                    ];
                    $this->db->insert('api_request_log',$api_log);

                    //改变推送状态
                    $_result = json_decode($result,true);
                    if (isset($_result['data']['success']) && !empty($_result['data']['success'])) {
                        $ids = $_result['data']['success'];
                        $this->db->where_in('id', $ids)->update('purchase_suggest',['is_push'=>1]);
                    } else {
                        var_dump($result);exit;
                    }
                    var_dump('OK');
                }
            }catch (Exception $e) {
                exit($e->getMessage());
            }

        }else{
            var_dump('没有数据要推送') ;exit;
        }
    }


    /**
     * 查询 推送采购单数据到数据中心
     * @author Jaden
    /purchase_api/push_purchase_order_list
     */
    public function push_purchase_order_list(){
        die('功能已废弃-Jolon-2020-03-16');
        $purchase_number = $this->input->get_post('purchase_number');
        $where = 'a.is_push in (0,2) AND a.purchase_order_status in (7)';
        if(!empty($purchase_number)){
            $where.=' AND a.purchase_number="'.$purchase_number.'"';
        }

        //查找总数
        $count_num = $this->db->select('a.id')
                    ->from('purchase_order as a')
                    ->join('purchase_order_pay_type as b', 'a.purchase_number=b.purchase_number', 'left')
                    ->join('purchase_suggest_map as m', 'a.purchase_number=m.purchase_number', 'left')
                    ->join('purchase_suggest as s', 's.demand_number=m.demand_number', 'left')
                    ->where($where)
                    ->group_by('a.purchase_number')
                    ->order_by('a.create_time DESC,a.id DESC')
                    ->count_all_results();

        //读取配置文件参数，获取匹配中转仓规则IP
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('service_data'))) {
            $service_data_info = $this->config->item('service_data');
            $_url_ip = isset($service_data_info['ip'])?$service_data_info['ip']:'';
            $_url_push_purchase_order = isset($service_data_info['push_purchase_order'])?$service_data_info['push_purchase_order']:'';
            if(empty($_url_ip) or empty($_url_push_purchase_order)){
                exit('推送地址缺失');
            }
            $push_url = $_url_push_purchase_order;
        }
        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 50;//每次推送50条
        }
        if($count_num>=1){
            try {
                for ($i=1; $i <=ceil($count_num/$limit) ; $i++) {

                    if ($i==2){
                        echo '每分钟只推50条';
                        break;
                    }

                    $offset = ($i-1)*$limit;
                    $purchase_list = $this->db->select(
                        'a.id,
                         a.purchase_number as pur_number,
                         a.supplier_code,
                         a.supplier_name,
                         a.shipping_method_id as shipping_method,
                         a.create_time as created_at,
                         a.create_user_name as creator,
                         a.currency_code,a.plan_product_arrive_time as date_eta,
                         a.buyer_name as buyer,
                         a.purchase_order_status as purchas_status,
                         a.is_transfer_warehouse as is_transit,
                         a.warehouse_code as transit_warehouse,
                         a.audit_time,
                         a.audit_name as auditor,
                         a.account_type,
                         a.pay_status,
                         a.complete_type,
                         a.purchase_type_id as purchase_type,
                         a.pay_time as payer_time,
                         a.is_expedited,
                         a.is_drawback,
                         a.create_type_id as create_type,
                         a.merchandiser_name as merchandiser,
                         a.pay_type,
                         b.freight as pay_ship_amount,
                         b.express_no as tracking_number,
                         b.real_price as total_price,
                         b.cargo_company_id as carrier,
                         s.source_from,
                         a.buyer_id
                         '
                     )
                    ->from('purchase_order as a')
                    ->limit($limit,$offset)
                    ->join('purchase_order_pay_type as b', 'a.purchase_number=b.purchase_number', 'left')
                    ->join('purchase_suggest_map as m', 'a.purchase_number=m.purchase_number', 'left')
                    ->join('purchase_suggest as s', 's.demand_number=m.demand_number', 'left')
                    ->where($where)
                    ->order_by('a.create_time DESC,a.id DESC')
                    ->group_by('a.purchase_number')
                    ->get()
                    ->result_array();

                    foreach ($purchase_list as $key => $value) {
                        $purchase_list[$key]['submit_time'] = $value['audit_time'];
                        $purchase_list[$key]['is_new'] = 1;//新采购系统推送人数据，数据中心为了区分数据来源

                        //添加员工编号
                        $userData = $this->purchase_user_model->get_user_info_by_id($value['buyer_id']);

                        if($userData){
                            $buyer_id = isset($userData['staff_code'])?$userData['staff_code']:'';
                        }

                        $purchase_list[$key]['buyer_id'] = $buyer_id;

                        //$purchase_list[$key]['pur_type'] = 5;//补货方式(待定)
                        //采购单详情
                        $purchase_order_items_list = array();
                        $purchase_order_items_list = $this->db->select(
                            'a.id,
                            a.purchase_number as pur_number,
                            a.sku,
                            a.product_name as name,
                            a.purchase_unit_price as price,
                            a.confirm_amount as ctq,
                            a.receive_amount as rqy,
                            a.upselft_amount as cty,
                            a.product_img_url as product_img,
                            a.is_exemption,
                            b.demand_number,
                            s.destination_warehouse
                            '
                        )
                        ->from('pur_purchase_order_items as a')
                        ->join('purchase_suggest_map as b', 'a.purchase_number=b.purchase_number and a.sku=b.sku', 'left')
                        ->join('purchase_suggest as s', 'b.demand_number=s.demand_number', 'left')
                        ->where('a.purchase_number="'.$value['pur_number'].'"')
                        ->group_by('a.purchase_number,a.sku')
                        ->get()
                        ->result_array();

                        foreach ($purchase_order_items_list as $k => $val) {
                            $purchase_order_items_list[$k]['items_totalprice'] = $val['price']*$val['ctq'];//单条sku的总金额
                            $purchase_order_items_list[$k]['purchase_status'] = $value['purchas_status'];
                            $purchase_order_items_list[$k]['qty'] = !empty($val['ctq'])?$val['ctq']:0;
                            //$purchase_order_items_list[$k]['qty'] = 5;//预期数量(待定)

                        }
                        $purchase_list[$key]['purchase_order_items_list'] = $purchase_order_items_list;

                        if($value['purchase_type'] == 2){//海外仓

                            if (!isset($purchase_order_items_list[0])){
                                $purchase_list[$key]['warehouse_code'] = '';
                            }else{
                                $purchase_list[$key]['warehouse_code'] = $purchase_order_items_list[0]['destination_warehouse'];
                            }

                        }else{
                            //海外仓以外,中专仓位空,warehouse_code为数据库本身值
                            $purchase_list[$key]['warehouse_code'] = $purchase_list[$key]['transit_warehouse'];
                            $purchase_list[$key]['transit_warehouse'] = '';
                        }

                        //仓库的是否退税是 1表示不退税，2表示退税,需要做转换
                        if($value['is_drawback'] == PURCHASE_IS_DRAWBACK_N){
                            $purchase_list[$key]['is_drawback'] = 1;
                        }elseif($value['is_drawback'] == PURCHASE_IS_DRAWBACK_Y){
                            $purchase_list[$key]['is_drawback'] = 2;
                        }
                    }

                    $post_data['order_data'] = json_encode($purchase_list);
                    $post_data['token'] = json_encode(stockAuth());
                    $result = getCurlData($push_url,$post_data);
                    $api_log=[
                         'record_number'=>$value['pur_number'],
                         'api_url'=>$push_url,
                         'record_type'=>'采购单推送到数据中心',
                         'post_content'=>$post_data['order_data'],
                         'response_content'=>$result,
                         'create_time'=>date('Y-m-d H:i:s')
                         ];
                        $this->db->insert('api_request_log',$api_log);

                    //file_put_contents('order_list.txt', $result,FILE_APPEND);
                    $r_result = json_decode($result,true);
                    if(isset($r_result['success_list']) AND !empty($r_result['success_list'])){
                        $succ_purchase_number_arr = $r_result['success_list'];
                        $this->db->where_in('purchase_number', $succ_purchase_number_arr)->update('purchase_order',['is_push'=>1]);   
                    }
                    if(isset($r_result['failure_list']) AND !empty($r_result['failure_list'])){
                        $fail_purchase_number_arr = $r_result['success_list'];
                        $this->db->where_in('purchase_number', $fail_purchase_number_arr)->update('purchase_order',['is_push'=>2]);   
                    }
                    var_dump('OK');
                }    
            }catch (Exception $e) {

                exit($e->getMessage());
            }
            
        }else{
            var_dump('没有数据要推送') ;exit; 
        }
    }

    /**
     * 查询 推送采购单数据到数据中心
     * @author Jaden
    /purchase_api/push_purchase_order_list
     */
    public function push_purchase_order_list_tmp(){
        die('功能已废弃-Jolon-2020-03-16');
        $purchase_number = $this->input->get_post('purchase_number');
        $where = 'a.is_push in (0,2) AND a.purchase_order_status in (7,10)';
        if(!empty($purchase_number)){
            $where.=' AND a.purchase_number="'.$purchase_number.'"';
        }
        //查找总数
        $count_num = $this->db->select('a.id')
            ->from('purchase_order as a')
            ->join('purchase_order_pay_type as b', 'a.purchase_number=b.purchase_number', 'left')
            ->join('purchase_suggest_map as m', 'a.purchase_number=m.purchase_number', 'left')
            ->join('purchase_suggest as s', 's.demand_number=m.demand_number', 'left')
            ->where($where)
            ->group_by('a.purchase_number')
            ->order_by('a.create_time DESC,a.id DESC')
            ->count_all_results();

        //读取配置文件参数，获取匹配中转仓规则IP
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('service_data'))) {
            $service_data_info = $this->config->item('service_data');
            $_url_ip = isset($service_data_info['ip'])?$service_data_info['ip']:'';
            $_url_push_purchase_order = isset($service_data_info['push_purchase_order'])?$service_data_info['push_purchase_order']:'';
            if(empty($_url_ip) or empty($_url_push_purchase_order)){
                exit('推送地址缺失');
            }
            $push_url = $_url_push_purchase_order;
        }
        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 300;//每次推送300条
        }
        if($count_num>=1){
            try {
                for ($i=1; $i <=ceil($count_num/$limit) ; $i++) {
                    $offset = ($i-1)*$limit;
                    $purchase_list = $this->db->select(
                        'a.id,
                         a.purchase_number as pur_number,
                         a.supplier_code,
                         a.supplier_name,
                         a.shipping_method_id as shipping_method,
                         a.create_time as created_at,
                         a.create_user_name as creator,
                         a.currency_code,a.plan_product_arrive_time as date_eta,
                         a.buyer_name as buyer,
                         a.purchase_order_status as purchas_status,
                         a.is_transfer_warehouse as is_transit,
                         a.warehouse_code as transit_warehouse,
                         a.audit_time,
                         a.audit_name as auditor,
                         a.account_type,
                         a.pay_status,
                         a.complete_type,
                         a.purchase_type_id as purchase_type,
                         a.pay_time as payer_time,
                         a.is_expedited,
                         a.is_drawback,
                         a.create_type_id as create_type,
                         a.merchandiser_name as merchandiser,
                         a.pay_type,
                         b.freight as pay_ship_amount,
                         b.express_no as tracking_number,
                         b.real_price as total_price,
                         b.cargo_company_id as carrier,
                         s.source_from
                         '
                    )
                        ->from('purchase_order as a')
                        ->limit($limit,$offset)
                        ->join('purchase_order_pay_type as b', 'a.purchase_number=b.purchase_number', 'left')
                        ->join('purchase_suggest_map as m', 'a.purchase_number=m.purchase_number', 'left')
                        ->join('purchase_suggest as s', 's.demand_number=m.demand_number', 'left')
                        ->where($where)
                        ->order_by('a.create_time DESC,a.id DESC')
                        ->group_by('a.purchase_number')
                        ->get()
                        ->result_array();

                    foreach ($purchase_list as $key => $value) {
                        $purchase_list[$key]['submit_time'] = $value['audit_time'];
                        $purchase_list[$key]['is_new'] = 1;//新采购系统推送人数据，数据中心为了区分数据来源
                        //$purchase_list[$key]['pur_type'] = 5;//补货方式(待定)
                        //采购单详情
                        $purchase_order_items_list = array();
                        $purchase_order_items_list = $this->db->select(
                            'a.id,
                            a.purchase_number as pur_number,
                            a.sku,
                            a.product_name as name,
                            a.purchase_unit_price as price,
                            a.confirm_amount as ctq,
                            a.receive_amount as rqy,
                            a.upselft_amount as cty,
                            a.product_img_url as product_img,
                            a.is_exemption,
                            b.demand_number,
                            s.destination_warehouse
                            '
                        )
                            ->from('pur_purchase_order_items as a')
                            ->join('purchase_suggest_map as b', 'a.purchase_number=b.purchase_number and a.sku=b.sku', 'left')
                            ->join('purchase_suggest as s', 'b.demand_number=s.demand_number', 'left')
                            ->where('a.purchase_number="'.$value['pur_number'].'"')
                            ->group_by('a.purchase_number,a.sku')
                            ->get()
                            ->result_array();

                        foreach ($purchase_order_items_list as $k => $val) {
                            $purchase_order_items_list[$k]['items_totalprice'] = $val['price']*$val['ctq'];//单条sku的总金额
                            $purchase_order_items_list[$k]['purchase_status'] = $value['purchas_status'];
                            $purchase_order_items_list[$k]['qty'] = !empty($val['ctq'])?$val['ctq']:0;
                            //$purchase_order_items_list[$k]['qty'] = 5;//预期数量(待定)

                        }
                        $purchase_list[$key]['purchase_order_items_list'] = $purchase_order_items_list;

                        if($value['purchase_type'] == 2){//海外仓
                            if (!isset($purchase_order_items_list[0])){
                                $purchase_list[$key]['warehouse_code'] = '';
                            }else{
                                $purchase_list[$key]['warehouse_code'] = $purchase_order_items_list[0]['destination_warehouse'];
                            }
                        }else{
                            //海外仓以外,中专仓位空,warehouse_code为数据库本身值
                            $purchase_list[$key]['warehouse_code'] = $purchase_list[$key]['transit_warehouse'];
                            $purchase_list[$key]['transit_warehouse'] = '';
                        }

                        //仓库的是否退税是 1表示不退税，2表示退税,需要做转换
                        if($value['is_drawback'] == PURCHASE_IS_DRAWBACK_N){
                            $purchase_list[$key]['is_drawback'] = 1;
                        }elseif($value['is_drawback'] == PURCHASE_IS_DRAWBACK_Y){
                            $purchase_list[$key]['is_drawback'] = 2;
                        }
                    }

                    $post_data['order_data'] = json_encode($purchase_list);
                    $post_data['token'] = json_encode(stockAuth());
                    $result = getCurlData($push_url,$post_data);
                    $api_log=[
                        'record_number'=>$value['pur_number'],
                        'api_url'=>$push_url,
                        'record_type'=>'采购单推送到数据中心',
                        'post_content'=>$post_data['order_data'],
                        'response_content'=>$result,
                        'create_time'=>date('Y-m-d H:i:s')
                    ];
                    $this->db->insert('api_request_log',$api_log);

                    //file_put_contents('order_list.txt', $result,FILE_APPEND);
                    $r_result = json_decode($result,true);
                    if(isset($r_result['success_list']) AND !empty($r_result['success_list'])){
                        $succ_purchase_number_arr = $r_result['success_list'];
                        $this->db->where_in('purchase_number', $succ_purchase_number_arr)->update('purchase_order',['is_push'=>1]);
                    }
                    if(isset($r_result['failure_list']) AND !empty($r_result['failure_list'])){
                        $fail_purchase_number_arr = $r_result['success_list'];
                        $this->db->where_in('purchase_number', $fail_purchase_number_arr)->update('purchase_order',['is_push'=>2]);
                    }
                    var_dump('OK');
                }
            }catch (Exception $e) {

                exit($e->getMessage());
            }

        }else{
            var_dump('没有数据要推送') ;exit;
        }
    }

    /**
     * 更新1688应付款日期
     * @user jeff jolon
     * purchase_api/update_order_pay_account_date
     */
    public function update_order_pay_account_date(){
        set_time_limit(0);
        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('finance/purchase_order_pay_type_model');

        $purchase_number = $this->input->get_post('purchase_number');// 执行指定的一个PO
        $debug           = $this->input->get_post('debug');

        $operator_key = 'getPayAccountDate';

        // 验证 redis 里面是否还有要待处理的数据
        $len = $this->rediss->llenData($operator_key);

        if ($len <= 0){
            // 没有数据 则自动增加待处理的数据
            $query_sql = "SELECT `t`.`purchase_number`, `t`.`pai_number`
                          FROM `pur_purchase_order_pay_type` as `t`
                          LEFT JOIN `pur_purchase_order` as `o` ON `t`.`purchase_number`=`o`.`purchase_number`	
                          WHERE o.create_time>'".date('Y-m-d',strtotime('-3 months'))."' 
                          AND `t`.`pai_number` != '' AND `accout_period_time` = '0000-00-00 00:00:00' 
                          AND `o`.`account_type` = 20 AND `o`.`source`=2 AND `o`.`purchase_order_status` NOT IN(1,3,14)
                          AND `t`.`is_request`=0
                          ORDER BY `t`.`id` DESC";

            $query     = $this->db->query($query_sql);
            $ali_order_ids = $query->result_array();
            if($ali_order_ids){
                foreach($ali_order_ids as $order_value){
                    $value = $order_value['purchase_number'].'_'.$order_value['pai_number'];
                    $this->rediss->lpushData($operator_key,$value);
                }

                $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_getPayAccountDate');
            }else{
                echo '没有需要执行的数据-1';exit;
            }
        }

        if($purchase_number){// 执行指定订单
            echo '<pre>';
            $wait_list[] = $purchase_number;
        }else{
            $wait_list = [];
            for($i = 0; $i < 50 ;$i ++){
                $order_id = $this->rediss->rpopData($operator_key);
                if(empty($order_id)) break;
                $wait_list[] = $order_id;
            }

            if(empty($wait_list)){
                echo '没有需要执行的数据-2';exit;
            }
        }


        $wait_list_tmp  = [];
        foreach($wait_list as $now_value){
            $now_value       = explode('_', $now_value);
            $purchase_number = $now_value[0];
            $order_id        = $now_value[1];
            $wait_list_tmp[$purchase_number] = $order_id;
        }
        $wait_list = $wait_list_tmp;
        unset($wait_list_tmp);

        try {
                foreach ($wait_list as $purchase_number => $value) {
                    if(isset($value) && !empty($value)){
                        $order_num_arr = array($value);
                        $result_order_item = $this->aliorderapi->getListOrderDetail(null,$order_num_arr);

                        if($debug) print_r($result_order_item);

                        if(isset($result_order_item[$value]) && $result_order_item[$value]['code'] == 200 && isset($result_order_item[$value]['data']['orderBizInfo']['accountPeriodTime']) && !empty($result_order_item[$value]['data']['orderBizInfo']['accountPeriodTime']) ){
                            $accountPeriodTime = $result_order_item[$value]['data']['orderBizInfo']['accountPeriodTime'];//账期订单交易到账时间(应付款时间)
                            //更新应付款时间
                            $this->purchase_order_pay_type_model->update_ali_accout_period_time(null,$value,$accountPeriodTime);

                        }else{
                            if(isset($result_order_item[$value]['data']['baseInfo']['tradeTypeCode']) or isset($result_order_item[$value]['data']['baseInfo']['status'])){
                                // 采购系统里面是 线上账期，但1688订单实际不是 账期交易的 或 订单关闭的 剔除
                                $tradeTypeCode = isset($result_order_item[$value]['data']['baseInfo']['tradeTypeCode'])?$result_order_item[$value]['data']['baseInfo']['tradeTypeCode']:'';
                                $tradeStatus = isset($result_order_item[$value]['data']['baseInfo']['status'])?$result_order_item[$value]['data']['baseInfo']['status']:'';
                                if( strtolower(trim($tradeTypeCode)) == 'fxassure' or in_array(strtolower(trim($tradeStatus)),['cancel','terminated'])){
                                    $this->db->where('pai_number', $value)->update('purchase_order_pay_type',['is_request'=>1]);// 标记请求结束
                                }
                            }
                            continue;
                        }
                    }

                }

        }catch (Exception $e) {
            exit($e->getMessage());
        }

        echo '恭喜，执行成功';exit;
    }
    /**
     *  推送快递单号,快递公司到仓库(没有全部到货的采购单)
     * #todo
     * @author Manson
     */
    public function push_express_list()
    {
        $purchase_number = $this->input->get_post('purchase_number');

        //查找总数
        $where='pli.express_no!="" AND pli.cargo_company_id!="" AND o.purchase_order_status in(6,7,8,10) AND t.is_push_express in(0,2)';
        if(!empty($purchase_number)){
            $where.=' AND t.purchase_number="'.$purchase_number.'"';
        }
        $count_num = $this->db->select('id')
            ->from('purchase_order_pay_type as t')
            ->join('purchase_order o', 'o.purchase_number=t.purchase_number', 'left')
            ->join('purchase_logistics_info pli', 'pli.purchase_number=t.purchase_number', 'left')
            ->where($where)
            ->group_by('pli.express_no')
            ->order_by('t.id DESC')
            ->count_all_results();

        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 300;//每次300条
        }

        //读取配置文件参数，获取仓库接收快递信息接口地址
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('wms_system'))) {
            $wms_info = $this->config->item('wms_system');
            $_url_relate_express = isset($wms_info['relate_express'])?$wms_info['relate_express']:'';
            if(empty($_url_relate_express)){
                exit('推送地址缺失');
            }
            $push_url = $_url_relate_express;
        }else{
            exit('推送地址缺失');
        }

        if($count_num>=1){
            try {
                for ($i=1; $i <=ceil($count_num/$limit) ; $i++) {

                    $push_data = [];
//                    $offset = ($i-1)*$limit;
                    $offset=0;
                    $purchase_express_list = $this->db->select('t.id,t.purchase_number,pli.express_no,pli.cargo_company_id')
                        ->from('purchase_order_pay_type as t')
                        ->join('purchase_order o', 'o.purchase_number=t.purchase_number', 'left')
                        ->join('purchase_logistics_info pli', 'pli.purchase_number=t.purchase_number', 'left')
                        ->where($where)
                        ->group_by('pli.express_no')
                        ->limit($limit,$offset)
                        ->order_by('t.id DESC')
                        ->get()->result_array();

                    foreach ($purchase_express_list as $key => $value) {
                        $push_data[$key]['express_no']   = $value['express_no'];//快递单号
                        $push_data[$key]['pur_number']   = $value['purchase_number'];//采购单号
                        $push_data[$key]['express_name'] = $value['cargo_company_id'];//快递公司
                    }

                    $post_data['relateExpress'] = json_encode($push_data);
                    $post_data['token'] = json_encode(stockAuth());
                    $result = getCurlData($push_url,$post_data);
                    $api_log=[
                        'api_url'=>$push_url,
                        'record_type'=>'采购单物流信息推送到仓库',
                        'post_content'=>$post_data['relateExpress'],
                        'response_content'=>$result,
                        'create_time'=>date('Y-m-d H:i:s')
                    ];
                    apiRequestLogInsert($api_log);

                    if(is_json($result)){
                        $result = json_decode($result,true);

                        if ($result['error'] != 0) throw new Exception($result['message'].$result['msg']);

                        //更新数据库
                        if (isset($result['data']['success']) && !empty($result['data']['success'])) {
                            //更新推送成功的单
                            $update = [];
                            $update_date = $result['data']['success'];

                            foreach ($update_date as $k => $v){
                                $update[$k]['purchase_number'] = $v['pur_number'];
                                $update[$k]['is_push_express'] = 1;//成功
                            }

                            $this->db->update_batch('purchase_order_pay_type',$update,'purchase_number');

                        }

                        if (isset($result['data']['error']) && !empty($result['data']['error'])) {
                            //更新推送失败的单
                            $update = [];
                            $update_date = $result['data']['error'];

                            foreach ($update_date as $k => $v){
                                $update[$k]['purchase_number'] = $v['pur_number'];
                                $update[$k]['is_push_express'] = 2;//失败
                            }

                            $this->db->update_batch('purchase_order_pay_type',$update,'purchase_number');

                        }
                    }else{
                        throw new Exception($result);
                    }
                    echo 'ok';
                }
            }catch (Exception $e) {
                exit($e->getMessage());
            }
        }else{
            var_dump('没有找到数据') ;exit;
        }
    }
    
    //推送报损成功的数据到仓库
    public function push_success_report_loss()
    {
        $id = $this->input->get_post('id');

        //查找总数
        $where='lo.status=4 AND lo.is_push_wms in(0,2) AND lo.is_lock=1 AND lo.is_reduce=1';
        if(!empty($id)){
            $where.=' AND lo.id='.$id;
        }
        $count_num = $this->db->select('id')
            ->from('purchase_order_reportloss as lo')
//            ->join('purchase_suggest s', 's.demand_number=lo.demand_number', 'left')
//            ->join('purchase_order o', 'o.purchase_number=lo.pur_number', 'left')
            ->where($where)
            ->order_by('lo.id DESC')
            ->count_all_results();
//        var_dump($count_num);die;
        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 300;//每次300条
        }

        //读取配置文件参数，获取仓库接收快递信息接口地址
        $this->load->config('api_config', FALSE, TRUE);
        if (!empty($this->config->item('wms_system'))) {
            $wms_info = $this->config->item('wms_system');
            $_url_report_loss_success = isset($wms_info['report_loss_success_list'])?$wms_info['report_loss_success_list']:'';
            if(empty($_url_report_loss_success)){
                exit('推送地址缺失');
            }
            $push_url = $_url_report_loss_success;
        }else{
            exit('推送地址缺失');
        }

        if($count_num>=1){
            try {
                for ($i=1; $i <=ceil($count_num/$limit) ; $i++) {

//                    $push_data = [];
//                    $offset = ($i-1)*$limit;
                    $offset=0;
                    $report_loss_list = $this->db->select('
                    lo.id,
                    lo.sku,
                    lo.create_user_name as cancel_operator,
                    lo.demand_number,
                    lo.create_time,
                    lo.remark as message,
                    lo.pur_number as purchase_order_no,
                    lo.loss_amount as cancel_num,
                    o.warehouse_code as transit_warehouse,
                    o.warehouse_code,
                    s.destination_warehouse,
                    s.purchase_type_id as purchase_type,
                    s.source_from
                    ')
                        ->from('purchase_order_reportloss as lo')
                        ->join('purchase_suggest s', 's.demand_number=lo.demand_number', 'left')
                        ->join('purchase_order o', 'o.purchase_number=lo.pur_number', 'left')
                        ->where($where)
                        ->limit($limit,$offset)
                        ->order_by('lo.id DESC')
                        ->get()->result_array();
                    
                    foreach ($report_loss_list as $key => &$value) {

                        if($value['purchase_type'] == 2){//海外仓

                            $value['warehouse_code'] = $value['destination_warehouse'];

                        }else{
                            //海外仓以外,中专仓位空,warehouse_code为数据库本身值
                            $value['warehouse_code'] = $value['transit_warehouse'];
                            $value['transit_warehouse'] = '';

                        }
                        unset($value['destination_warehouse']);
                        $value['type'] = 2;//类型:2是报损
                    }

//                    var_dump($report_loss_list);die;

                    $post_data['data'] = json_encode($report_loss_list);
                    $post_data['token'] = json_encode(stockAuth());
//                    var_dump($post_data);die;
                    $result = getCurlData($push_url,$post_data);

                    if(is_json($result)){
                        $result = json_decode($result,true);

                        if ($result['code'] != 1){
                            $api_log=[
                                'api_url'=>$push_url,
                                'record_type'=>'报损成功信息推送到仓库',
                                'post_content'=>$post_data['data'],
                                'response_content'=>$result,
                                'create_time'=>date('Y-m-d H:i:s')
                            ];
                            apiRequestLogInsert($api_log);
                            throw new Exception($result['message'].$result['msg']);
                        }

                        //更新数据库
                        if (isset($result['data']['success_list']) && !empty($result['data']['success_list'])) {
                            //更新推送成功的单
                            $update = [];
                            $update_date = $result['data']['success_list'];

                            foreach ($update_date as $k => $v){
                                $update[$k]['id'] = $v['id'];
                                $update[$k]['is_push_wms'] = 1;//成功
                            }

                            $this->db->update_batch('purchase_order_reportloss',$update,'id');

                        }

                        if (isset($result['data']['fail_list']) && !empty($result['data']['fail_list'])) {
                            //更新推送失败的单
                            $update = [];
                            $update_date = $result['data']['fail_list'];

                            foreach ($update_date as $k => $v){
                                $update[$k]['id'] = $v['id'];
                                $update[$k]['is_push_wms'] = 2;//失败
                            }

                            $this->db->update_batch('purchase_order_reportloss',$update,'id');

                        }
                    }else{
                        $api_log=[
                            'api_url'=>$push_url,
                            'record_type'=>'报损成功信息推送到仓库',
                            'post_content'=>$post_data['data'],
                            'response_content'=>$result,
                            'create_time'=>date('Y-m-d H:i:s')
                        ];
                        apiRequestLogInsert($api_log);
                        throw new Exception($result);
                    }
                    echo 'ok';
                }
            }catch (Exception $e) {
                exit($e->getMessage());
            }
        }else{
            var_dump('没有找到数据') ;exit;
        }
    }

    /**
     * 获取新仓库系统点数数据接口，新仓库系统推送到采购系统
     * 需求：33810 新WMS在点数节点时,就要先推送一次入库日志到采购系统,在上架完成后,再重新推送一次
     * @author:luxu
     * @time:2021年5月5号
     **/

    public function new_warehouse_push_lack_data(){

        try{
            ini_set('max_execution_time','18000');
            $datas = json_decode(file_get_contents('php://input'), true);
            print_r($datas);die();
            if(empty($datas)){
                throw new Exception("请传入数据");
            }

            $ci = get_instance();
            $ci->load->config('mongodb');
            $host = $ci->config->item('mongo_host');
            $port = $ci->config->item('mongo_port');
            $user = $ci->config->item('mongo_user');
            $password = $ci->config->item('mongo_pass');
            $author_db = $ci->config->item('mongo_db');
            $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
            foreach($datas as $dataValue) {
                $bulk = new MongoDB\Driver\BulkWrite();
                $dataValue['create_time'] = date("Y-m-d H:i:s",time());
                $dataValue['is_read'] = 0;
                $mongodb_result = $bulk->insert($dataValue);
                $mongodb->executeBulkWrite("{$author_db}.lackdata", $bulk);
            }



        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }

    }


    public function pushLackData(){
        ini_set('max_execution_time','18000');
        $this->load->model('purchase/purchase_order_lack_model','lack');
        $data = $this->lack->getQueue();

        if(empty($data)){

            $data = isset($_POST['data'])?$_POST['data']:[];
        }
        if(!empty($data)){
            $ci = get_instance();
            $ci->load->config('mongodb');
            $host = $ci->config->item('mongo_host');
            $port = $ci->config->item('mongo_port');
            $user = $ci->config->item('mongo_user');
            $password = $ci->config->item('mongo_pass');
            $author_db = $ci->config->item('mongo_db');
            $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
            $author_db = $author_db;

            $dataArray = json_decode($data,true);
            $id = 0;
            foreach($dataArray as $dataValue) {
                ++$id;
                $bulk = new MongoDB\Driver\BulkWrite();
                $dataValue['create_time'] = date("Y-m-d H:i:s",time());
                $dataValue['is_read'] = 0;
                $dataValue['id'] = rand(0,1000).$id.rand(0,1000).date("YmdHis",time());

                $mongodb_result = $bulk->insert($dataValue);
                $mongodb->executeBulkWrite("{$author_db}.lackdata", $bulk);
            }
           // $this->lack->pushLackData($data);
        }
    }

    /**
     * 时时读取少数少款MONGDB 数据计算
     * @param :
     * @author:luxu
     **/

    public function getMongdbLackData(){

        try{
            ini_set('max_execution_time','18000');
            $this->load->model('purchase/purchase_order_lack_model','lack');
            $ci = get_instance();
            $ci->load->config('mongodb');
            $host = $ci->config->item('mongo_host');
            $port = $ci->config->item('mongo_port');
            $user = $ci->config->item('mongo_user');
            $password = $ci->config->item('mongo_pass');
            $author_db = $ci->config->item('mongo_db');
            $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
            $author_db = $author_db;

            $prevDate = date("Y-m-d",strtotime("-1 day"));
            $filter['create_time'] = array('$lte'=>$prevDate." 23:59:59");
            $filter['is_read']  = 0;
            $options = [];
            $query = new MongoDB\Driver\Query($filter, $options);

            $command = new MongoDB\Driver\Command(
                array(
                    "count" => "lackdata",
                    "query" => $query,
                )
            );

            $count = $mongodb->executeCommand($author_db,$command)->toArray()[0]->n;

            if( !empty($count) && $count >0){

                $limit = 50;
                $page = ceil($count/$limit);

                for( $i=0;$i<=$page;++$i){

                    $options['skip'] = $i * $limit;
                    $options['limit'] = $limit;
                    $query = new MongoDB\Driver\Query($filter, $options);
                    $cursor = $mongodb->executeQuery("{$author_db}.lackdata", $query)->toArray();
                    if(!empty($cursor)){
                        $lackData = [];
                        $isReadIds = [];
                        foreach($cursor as &$data){
                            $message = get_object_vars($data);
                            if( isset($message['_id'])){
                                $isReadIds[] = $message['id'];
                                unset($message['_id']);
                                unset($message['is_read']);
                            }
                            $lackData[] = $message;
                        }
                        if( !empty($lackData)){

                            $childrenData = [];
                            foreach($lackData as $lackKey=>$lackValue) {
                                $childrenPurchase = $this->lack->getSuggestPurchaseData($lackValue['purchase_order_no'],$lackValue['sku']);

                                if(!empty($childrenPurchase)){

                                    foreach($childrenPurchase as $k=>$v) {
                                        $childrenData [] = [

                                            'id' => 0,
                                            'sku' => $v['sku'],
                                            'purchase_order_no' => $v['purchase_order_no'],
                                            "warehouse_code"=>"",
                                            "express_no" => "",
                                            "actual_num" => 0,
                                            "add_username" => "",
                                            "station" => "",
                                            "is_accumulate" => 0,
                                            "defective_id" => "",
                                            "from_system" => 1,
                                            "origin_id" =>"",
                                            "create_time" => date("Y-m-d i:m:s",time())
                                        ];
                                    }
                                }
                            }
                            if(!empty($childrenData)) {
                                $lackData = array_merge($lackData, $childrenData);
                            }

                            $this->lack->pushLackData(json_encode($lackData));

                            //修改

                            $ids = array_column($lackData,'id');
                            $bulk = new MongoDB\Driver\BulkWrite();
                            $filters = ['id'=>['$in'=>$ids],'is_read'=>0];
                            $sets = ['$set' => ['is_read' => 2]];
                            $updateOptions = ['multi' => true, 'upsert' => false];
                            $bulk->update($filters, $sets, $updateOptions);
                            $result = $mongodb->executeBulkWrite("{$author_db}.lackdata", $bulk);
                        }


                    }
                }

            }

        }catch ( Exception $exp ){

          echo $exp->getMessage();
        }
    }


    /**
     * 推送新仓库系统
     * JAVA 接口文档地址:http://192.168.71.156/web/#/127?page_id=19215
     * @author:luxu
     * @time:2020/12/11
     *  PURCHASE_ORDER_STATUS_ALL_ARRIVED,
        PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,
        PURCHASE_ORDER_STATUS_CANCELED
     **/
    public function getDataPushWms(){

        // 按备货单维度推送完结状态
        $this->suggestOrderFinishedPushWms();

        if(time() > 1633017600 + 86400 * 60){// 1633017600=2021-10-01，2021-12-01 之前两个维度推送，之后不再推送采购单维度
            exit;
        }

        try{
            ini_set('max_execution_time','18000');
            $query = $this->db->from("purchase_order_items AS items")

                ->join("purchase_order as orders"," items.purchase_number=orders.purchase_number","LEFT")
                ->join("purchase_suggest_map AS map"," items.purchase_number=map.purchase_number AND map.sku=items.sku","LEFT")
                ->where_in("orders.purchase_order_status",[PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,PURCHASE_ORDER_STATUS_CANCELED])->where('is_wms',0)
                ->where("orders.audit_time>=",PUSH_WMS_START_TIME)

                ->select("map.purchase_number as poNumber,map.sku,map.demand_number as demandNumber");

            $total = $query->count_all_results();
            if($total >0 ){
                $limit = 1000;
                $page = ceil($total/$limit);
                $url = getConfigItemByName('api_config', 'wms_system', 'receivePurchaseOrder');
                $access_taken = getOASystemAccessToken();
                $url = $url . "?access_token=" . $access_taken;
                $header = array('Content-Type: application/json','w:SZ_AA','org:org_00001');

                $this->_ci = get_instance();
                //获取redis配置
                $this->_ci->load->config('mongodb');
                $host = $this->_ci->config->item('mongo_host');
                $port = $this->_ci->config->item('mongo_port');
                $user = $this->_ci->config->item('mongo_user');
                $password = $this->_ci->config->item('mongo_pass');
                $author_db = $this->_ci->config->item('mongo_db');
                $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");


                for( $i=1;$i<=$page;$i++){

                    $offset = ($i-1)*$limit;


                    $result =  $this->db->from("purchase_order_items AS items")

                        ->join("purchase_order as orders"," items.purchase_number=orders.purchase_number","LEFT")
                        ->join("purchase_suggest_map AS map"," items.purchase_number=map.purchase_number AND map.sku=items.sku","LEFT")
                        ->where_in("orders.purchase_order_status",[PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,PURCHASE_ORDER_STATUS_CANCELED])->where('is_wms',0)
                        ->where("orders.audit_time>=",PUSH_WMS_START_TIME)

                        ->select("items.purchase_number as poNumber,items.sku,map.demand_number as demandNumber")
                        ->limit($limit,$offset)->order_by("orders.id DESC")->get()->result_array();

                    $newWmsresult = getCurlData($url, json_encode(['items'=>$result], JSON_UNESCAPED_UNICODE), 'post', $header);
                    $newWmsresult = json_decode($newWmsresult,true);

                    // 记录推送日志
                    $bulk = new MongoDB\Driver\BulkWrite();
                    $mongodb_result = $bulk->insert(['items'=>$result,'return_wms'=>$newWmsresult]);
                    $mongodb->executeBulkWrite("{$author_db}.purchase_to_wms", $bulk);

                    if( $newWmsresult['code'] == 200 ){

                        $purchaseNumbers = array_column($result,'poNumber');
                        $this->db->where_in("purchase_number",$purchaseNumbers)->update("purchase_order",['is_wms'=>2]);
                    }
                }
            }
        }catch ( Exception $exp ){

            echo $exp->getMessage();
        }
    }

    /**
     * 备货单完结后推送完结状态到新仓库（备货单维度）
     * @author Jolon
     */
    public function suggestOrderFinishedPushWms(){
        try{
            ini_set('max_execution_time','18000');
            $query = $this->db->select("map.purchase_number as poNumber,map.sku,map.demand_number as demandNumber")
                ->from("purchase_suggest_map AS map")
                ->join("purchase_suggest AS suggest"," suggest.demand_number=map.demand_number","INNER")
                ->where_in("suggest.suggest_order_status",[PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,PURCHASE_ORDER_STATUS_CANCELED])
                ->where('map.finished_push_wms',1)
                ->where("map.create_time>='2021-03-03 00:00:00'");

            $total = $query->count_all_results();

            if($total >0 ){
                $limit = 1000;
                $page = ceil($total/$limit);
                $url = getConfigItemByName('api_config', 'wms_system', 'receivePurchaseOrder');
                $access_taken = getOASystemAccessToken();
                $url = $url . "?access_token=" . $access_taken;
                $header = array('Content-Type: application/json','w:SZ_AA','org:org_00001');

                $this->_ci = get_instance();
                //获取redis配置
                $this->_ci->load->config('mongodb');
                $host = $this->_ci->config->item('mongo_host');
                $port = $this->_ci->config->item('mongo_port');
                $user = $this->_ci->config->item('mongo_user');
                $password = $this->_ci->config->item('mongo_pass');
                $author_db = $this->_ci->config->item('mongo_db');
                $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");


                for( $i=1;$i<=$page;$i++){

                    $offset = ($i-1)*$limit;

                    $result = $this->db->select("map.purchase_number as poNumber,map.sku,map.demand_number as demandNumber")
                        ->from("purchase_suggest_map AS map")
                        ->join("purchase_suggest AS suggest"," suggest.demand_number=map.demand_number","INNER")
                        ->where_in("suggest.suggest_order_status",[PURCHASE_ORDER_STATUS_ALL_ARRIVED,PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE,PURCHASE_ORDER_STATUS_CANCELED])
                        ->where('map.finished_push_wms',1)
                        ->where("map.create_time>='2021-03-03 00:00:00'")
                        ->limit($limit,$offset)
                        ->order_by("map.id DESC")
                        ->get()
                        ->result_array();


                    $newWmsresult = getCurlData($url, json_encode(['items'=>$result], JSON_UNESCAPED_UNICODE), 'post', $header);
                    $newWmsresult = json_decode($newWmsresult,true);

                    // 记录推送日志
                    $bulk = new MongoDB\Driver\BulkWrite();
                    $mongodb_result = $bulk->insert(['items'=>$result,'return_wms'=>$newWmsresult]);
                    $mongodb->executeBulkWrite("{$author_db}.purchase_to_wms", $bulk);

                    if( $newWmsresult['code'] == 200 ){

                        $demandNumber = array_column($result,'demandNumber');
                        $this->db->where_in("demand_number",$demandNumber)->update("purchase_suggest_map",['finished_push_wms'=>2]);
                    }else{
                        echo isset($newWmsresult['message'])?$newWmsresult['message']:'推送返回失败';
                    }
                }
            }
        }catch ( Exception $exp ){

            echo $exp->getMessage();
        }
    }

    public function deldata(){

        $sql = "SELECT
                sku,id,estimate_time
            FROM
                pur_product_scree AS scree
            WHERE
                apply_time >= '2021-02-04'
            AND apply_time <= '2021-02-08 23:59:59'
            AND scree. STATUS = 20
	   ";

        $result = $this->db->from("product_scree")->query($sql)->result_array();

        foreach($result as $key=>$value){

            $sql = " SELECT * FROM pur_product_scree as scree WHERE   apply_time >= '2021-02-04'
            AND apply_time <= '2021-02-08 23:59:59'
            AND scree.status=50 AND sku='{$value['sku']}' AND estimate_time='{$value['estimate_time']}'";

            $res = $this->db->from("product_scree")->query($sql)->result_array();
            if(!empty($res)) {


                $this->db->where("id",$value['id'])->update("product_scree",['status'=>60]);

            }

        }
    }

    /**
     * 定时刷新1688全部信息
     */
    public function refund_ali_order_info()
    {
        try{

            $order_status = [
                PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE,
                PURCHASE_ORDER_STATUS_ALL_ARRIVED,
                PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE
            ];
            $data = $this->db->select('o.purchase_number')
                ->from("purchase_order as o")
                ->where(["o.pay_status"=>10, "o.account_type"=>20])
                ->where_in('o.purchase_order_status', $order_status)
                ->get()
                ->result_array();
            if($data && !empty($data)){
                $this->load->model('ali/Ali_order_model');
                $data = array_column($data, 'purchase_number');
                $list = count($data) > 10 ?array_chunk($data, 10): [$data];
                foreach ($list as $val){
                    if(count($val))$this->Ali_order_model->refresh_ali_order_data_all($val);
                }
            }
        }catch (Exception $e){}
    }

    /**
     * 需求：33403 采购单页面,一键合单回传计划系统的数据维度为需求单维度，
     *  只处理需求单合单
     * @author:luxu
     * @time:2021年4月21号
     **/
    public function demand_push_plan(){

         try{

             $totalQuery = " SELECT COUNT(*) as total FROM pur_purchase_demand WHERE is_merge_push_plan=1";
             $total = $this->db->query($totalQuery)->row_array();
             if($total['total'] == 0){
                 die();
             }
             $limit = 1000;
             $page = ceil($total['total']/$limit);
             $access_token = getOASystemAccessToken();

             //推送计划系统
             $url = getConfigItemByName('api_config', 'java_system_plan', 'push_audit_suggest');
             $url    = $url.'?access_token='.$access_token;
             $header = ['Content-Type: application/json'];
             $this->load->library('mongo_db');
             for($i=1;$i<=$page;++$i){

                 $sql = " SELECT * FROM pur_purchase_demand WHERE is_merge_push_plan=1 LIMIT ".($i-1)*$limit.",".$limit;

                 $result = $this->db->query($sql)->result_array();
                 if(empty($result)){

                     break;
                 }
                 $push_data = [];
                 foreach($result as $key=>$value){

                     $push_data[] = [
                         'id' => $value['id'],
                         'demand_number' => $value['demand_number'],//备货单号
                         'audit_status' => SUGGEST_AUDITED_PASS,//审核通过
                         'audit_time' => date('Y-m-d H:i:s',time()),//审核时间
                         'business_line' => $value['purchase_type_id'],//业务线
                     ];

                 }

                 if(!empty($push_data)){
                     $logs_push_data = $push_data;
                     $push_data = json_encode($push_data);
                     $res = getCurlData($url, $push_data, 'POST',$header);
                     $result = json_decode($res, TRUE);
                     $logsData['data'] = $push_data;
                     $logsData['return'] = $result;
                     $this->mongo_db->insert('demand_to_plan', $logsData);

                     if ((isset($result['code']) && $result['code']=200)){
                         $update['is_merge_push_plan']=2;

                         $demandIds = array_column($logs_push_data,"id");
                         $this->db->where_in("id",$demandIds)->update("pur_purchase_demand",$update);
                     }
                 }
             }

         }catch ( Exception $exp ){


         }
    }

    /**
     * 需求:35950 分销系统需要对接采购系统采购页面的“预计供货时间”字段接口
     * METHOD : POST
     **/
    public function get_order_plan_arrive_time(){

        ini_set('max_execution_time','18000');
        $sku_json = file_get_contents('php://input');
        $skus = json_decode($sku_json,True);
        if(empty($skus)){
            $this->error_json("请传入SKU");
        }

        if(count($skus)>2000){
            $this->error_json("SKU 个数小于等于2000");
        }

        $result = $this->db->from("purchase_order_items")->where_in("sku",$skus)
        ->select("sku,purchase_number,sku,demand_number,plan_arrive_time")->get()->result_array();
        $this->success_json($result);
    }

    /**
     * 每天早上7点，定时刷新是否有物流轨迹
     */
    public function sync_purchase_track()
    {
        $start = date('Y-m-d',strtotime('-1 days')).' 00:00:00';
        $end = date("Y-m-d").' 07:00:00';
        try{
            $sql = "update pur_purchase_logistics_info as o 
                inner join pur_purchase_order as po on o.purchase_number=po.purchase_number 
                left join pur_purchase_logistics_track_detail as td on o.express_no=td.express_no 
                set po.track_log = 1 
                where td.express_no is not null and td.update_time BETWEEN '{$start}' and '{$end}'";
            if($this->db->query($sql)){
                echo "更新成功！";
            }else{
                echo "更新失败";
            };
        }catch (Exception $e){
            echo "更新失败，原因：".$e->getMessage();
        }
    }

    /**
     * 每天早上6点，生成今日任务栏数据
     * /purchase_api/generate_today_work
     */
    public function generate_today_work()
    {
        $this->load->model('purchase/purchase_order_list_model');
        $data = $this->purchase_order_list_model->generate_today_work();
        if($data){
            echo "修改成功！";
        }else{
            echo "修改失败！";
        }
    }



}
