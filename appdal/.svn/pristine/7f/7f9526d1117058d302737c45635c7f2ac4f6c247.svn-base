<?php
/**
 * 采购单取消，作废
 * User: Jaden
 * Date: 2019/2/12 0023
 * Time: 16:20
 */

class Purchase_order_cancel extends MY_Controller{

    public function __construct(){
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('purchase_order_model','purchase_order');
    }

    /**
     * 推送作废订单到数据中心
     * /purchase/purchase_order_cancel/purchasecancel
     * @author Jaden 2019-2-13
    */
    public function purchasecancel(){
        $limit = $this->input->get('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 50;
        }
        //获取作废的 和获取部分到货不等待剩余的
        $where = 'is_push=0 AND  warehouse_code in ("FBA_SZ_AA","SZ_AA","ZDXNC","HW_XNC") AND purchase_order_status in (14) ';
        $query = $this->db->select('purchase_number,purchase_order_status,purchase_type_id,create_time,warehouse_code,transfer_warehouse,buyer_name')->from('pur_purchase_order')->where($where)->limit($limit)->get()->result_array();
        $queryd = $this->getPurchase($query);
        $queryd = ThereArrayTwo($queryd);
        if(!empty($queryd)) {
            try {
                //读取配置文件参数，获取推送地址
                $this->load->config('api_config', FALSE, TRUE);
                if (!empty($this->config->item('service_data'))) {
                    $service_data_info = $this->config->item('service_data');
                    $_url_ip = isset($service_data_info['ip'])?$service_data_info['ip']:'';
                    $_url_push_void = isset($service_data_info['push_purchase_void_order'])?$service_data_info['push_purchase_void_order']:'';
                    if(empty($_url_ip) or empty($_url_push_void)){
                        exit('推送地址缺失');
                    }
                    $url = $_url_ip.$_url_push_void;
                }
                $post_data=[
                   'cancel_data'=>json_encode($queryd),
                   'token'=>json_encode(stockAuth()),
                ];
                $s = getCurlData($url,$post_data);
                //验证json
                $sb = is_json($s);
                if (!$sb) {
                    echo '请检查json' . "\r\n";
                    exit($s);
                } else {
                    $_result = json_decode($s,true);
                    if (isset($_result['failure_list']) && !empty($_result['failure_list'])) {
                        foreach ($_result['failure_list'] as $failValue) {
                            $this->db->where('purchase_number',$failValue['pur_number']);
                            $this->db->update('pur_purchase_order',array('is_push'=>2)); 
                        }
                    }

                    if (isset($_result['success_list']) && !empty($_result['success_list'])) {
                        foreach ($_result['success_list'] as $v) {
                            $this->db->where('purchase_number',$v['pur_number']);
                            $this->db->update('pur_purchase_order',array('is_push'=>1));
                        }

                    } else {
                        var_dump($s);
                    }
                }
            } catch (Exception $e) {

                exit('发生了错误');
            }
        }else{
            exit('没有在途数据推送');
        }


        

    }


    /**
     * 推送取消未到货
     * /purchase/purchase_order_cancel/pullcancelstock
     * @author Jaden 2019-2-13
    */
    public function pullcancelstock(){
        $limit = $this->input->get('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 50;
        }
        $this->db->select('c.id,c.cancel_id,c.purchase_number,o.warehouse_code,o.transfer_warehouse,c.sku,c.cancel_ctq,o.buyer_name,cd.audit_status,o.purchase_order_status')->from('pur_purchase_order_cancel_detail as c');
        $this->db->join('pur_purchase_order_cancel as cd', 'cd.purchase_number = c.purchase_number');
        $this->db->join('pur_purchase_order as o', 'o.purchase_number = c.purchase_number');
        $query = $this->db->limit($limit)->get()->result_array();
        $data = array();
        if (!empty($query)) {
            $i = 1;
            foreach ($query as $k=>$v) {
                $data[$i]['id'] = $v['id'];
                $data[$i]['cancel_id'] = $v['cancel_id'];
                $data[$i]['purchase_number'] = $v['purchase_number'];
                $data[$i]['warehouse_code'] = $v['warehouse_code'];
                $data[$i]['transfer_warehouse'] = $v['transfer_warehouse'];
                $data[$i]['sku'] = $v['sku'];
                $data[$i]['ctq'] = $v['cancel_ctq'];
                $data[$i]['cancel_operator'] = $v['buyer_name'];
                $data[$i]['type'] = 3; //FBA取消未到货
                $data[$i]['check_operator'] = $v['audit_status'];
                $data[$i]['status'] = $v['purchase_order_status'];
                $i++;
            }
        }
        if(!empty($data)) {
            try {
                //读取配置文件参数，获取推送地址
                $this->load->config('api_config', FALSE, TRUE);
                if (!empty($this->config->item('service_data'))) {
                    $service_data_info = $this->config->item('service_data');
                    $_url_ip = isset($service_data_info['ip'])?$service_data_info['ip']:'';
                    $_url_push_cancel = isset($service_data_info['push_purchase_void_order'])?$service_data_info['push_purchase_void_order']:'';
                    if(empty($_url_ip) or empty($_url_push_cancel)){
                        exit('推送地址缺失');
                    }
                    $url = $_url_ip.$_url_push_cancel;
                }
                $post_data=[
                   'purchase'=>json_encode($query),
                ];
                $s = getCurlData($url,$post_data);
                //验证json
                $sb = Vhelper::is_json($s);
                if (!$sb) {
                    echo '请检查json' . "\r\n";
                    exit($s);
                } else {
                    $_result = Json::decode($s);

                    if (isset($_result['failure_list']) && !empty($_result['failure_list'])) {
                        foreach ($_result['failure_list'] as $failValue) {
                            $this->db->where('id',$failValue['id']);
                            $this->db->update('pur_purchase_order_cancel_detail',array('is_push'=>2));
                        }
                    }
                    if (isset($_result['success_list']) && !empty($_result['success_list'])) {
                        foreach ($_result['success_list'] as $v) {
                            $this->db->where('id',$failValue['id']);
                            $this->db->update('pur_purchase_order_cancel_detail',array('is_push'=>1));
                        }
                    } else {
                        Vhelper::dump($s);
                    }
                }
            } catch (Exception $e) {

                exit('发生了错误');
            }
        }else{
            exit('没有在途数据推送');
        }



    }



    protected function  getPurchase($data, $type=4)
    {

        $queryb= [];
        foreach ($data as $k=>$v)
        {           
            $rs2 = $this->db->select(['id','purchase_number','sku','confirm_amount','upselft_amount'=>'ifnull(upselft_amount,0)'])->from('pur_purchase_order_items')->where_in('purchase_number',array($v['purchase_number']))->get()->result_array();            
            foreach($rs2 as $d=>$c)
            {
                $queryb[$k][$d]['id'] = $c['id'];
                $queryb[$k][$d]['pur_number'] = $c['purchase_number'];
                $queryb[$k][$d]['warehouse_code'] = $v['warehouse_code'];
                $queryb[$k][$d]['transit_warehouse'] = !empty($v['transit_warehouse']) ? $v['transfer_warehouse'] : '';
                $queryb[$k][$d]['sku'] = $c['sku'];
                $queryb[$k][$d]['create_time'] = !empty($v['create_time']) ? $v['create_time'] : '';
                $queryb[$k][$d]['ctq'] = !empty($c['upselft_amount']) ? (($c['confirm_amount'] - $c['upselft_amount'])>=0 ? $c['confirm_amount'] - $c['upselft_amount'] :0) : $c['confirm_amount'];
                $queryb[$k][$d]['cancel_operator'] = !empty($v['buyer_name']) ? $v['buyer_name'] : '';
                $queryb[$k][$d]['type'] = $type; //取消的类型
                $queryb[$k][$d]['check_operator'] = '';
                $queryb[$k][$d]['status'] = !empty($v['purchase_order_status']) ? $v['purchase_order_status'] : '';
                $queryb[$k][$d]['purchase_type'] = !empty($v['purchase_type_id']) ? $v['purchase_type_id'] : '';
            }
        }
        return $queryb;
    }

}