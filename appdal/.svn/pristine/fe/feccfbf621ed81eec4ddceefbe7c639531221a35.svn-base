<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * 计算当天入库的平均运费数据
 * User: Jaden
 * Date: 2019/01/17 
 */

class Purchase_avg extends MY_API_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('warehouse/purchase_avg_fuse_model','purchase_avg_fuse_model');
        $this->load->model('product/product_model', 'product_model');
       
    }
    /**
     * 计算当天入库的平均运费数据
     * /Purchase_avg/calculate_sku_avg
     * @author Jaden 2019-1-17
    */
    public function calculate_sku_avg(){
       set_time_limit(0);
       $start_time = $this->input->get_post('start_time');
       $end_time = $this->input->get_post('end_time');
       $push_all = $this->input->get_post('push_all');
       $limit = $this->input->get_post('limit',200);
       if(empty($start_time) || empty($end_time)){
            $start_time = date("Y-m-d",strtotime("-1 day"))." 00:00:00";
            $end_time = date("Y-m-d",strtotime("-1 day"))." 23:59:59";
       }else{
            $start_time = $start_time;
            $end_time = $end_time;
       }
       if($push_all){
            $where = 'is_calculate=0';
       }else{
            $where = 'create_time>="'.$start_time.'" and create_time<="'.$end_time.'" and is_calculate=0';
       }
       //查询当天入库的记录
       $success_list = array();
       $warehouse_results_list = $this->db->select('purchase_number,sku,purchase_qty,instock_qty')
                    ->from('warehouse_results')
                    ->limit($limit)
                    ->where($where)
                    ->get()->result_array();
        if(!empty($warehouse_results_list)){
            //$push_url = getConfigItemByName('api_config','old_purchase','supplier-get-avg_price');
            foreach ($warehouse_results_list as $key => $value) {
                $result = $this->purchase_avg_fuse_model->calculating_average_freight($value);
                $this->db->where('sku', $value['sku'])->where('purchase_number',$value['purchase_number'])->update('warehouse_results',['is_calculate'=>1]);
                $success_list[]=$value['purchase_number'].'_'.$value['sku'];
            }
            var_dump(implode(',', array_unique($success_list)).'计算完成');
        }else{
            var_dump('当天入库记录已经计算完') ;exit;
        }           
                   

    }

    /**
     * 推送前一天计算的平均运费到ERP
     * /purchase_avg/push_avg_price_to_erp
     * @author Jaden 2019-1-17
     */
    public function push_avg_price_to_erp(){
        set_time_limit(0);
        $start_time = $this->input->get_post('start_time');
        $end_time = $this->input->get_post('end_time');
        $push_all = $this->input->get_post('push_all');
        $degbug =true;
        if(empty($start_time) || empty($end_time)){
            $start_time = date("Y-m-d")." 00:00:00";
            $end_time = date("Y-m-d")." 23:59:59";
        }else{
            $start_time = $start_time;
            $end_time = $end_time;
        }
        if($push_all){
            $where = 'is_push_to_erp=0';
        }else{
            $where = 'update_time>="'.$start_time.'" and update_time<="'.$end_time.'" and is_push_to_erp=0';
            //$where = 'is_push_to_erp=0';
        }

        //查询当天入库的记录
        $limit = 2000;
        $success_list = array();
        $pur_purchase_avg_list = $this->db->select('id,sku,avg_freight,avg_purchase_price,avg_price')
            ->from('pur_purchase_avg_fuse')
            ->limit($limit)
            ->where($where)
            ->get()->result_array();


        if(!empty($pur_purchase_avg_list)){

            $avgPriceData = [];

            // 加载 URL 配置项
            $this->load->config('api_config', FALSE, TRUE);
            $erp_system = $this->config->item('erp_system');
            $to_erp_purchase_price_url = $erp_system['purchase_price_to_erp']."?access_token=". getOASystemAccessToken();

            foreach ($pur_purchase_avg_list as $key => $value) {
                $product_info = $this->product_model->get_product_info($value['sku']);
                $pur_purchase_avg_list[$key]['latest_purchase_price'] = !empty($product_info['purchase_price']) ? $product_info['purchase_price'] :0;

                $avgPriceData = [

                    'sku' => $value['sku'],
                    'lastPrice' => $value['avg_purchase_price'], //平均采购价（不含运费）
                    'avgPrice' => $value['avg_price'], // 平均采购价（含运费）
                    'newPrice' =>  !empty($product_info['purchase_price']) ? $product_info['purchase_price'] :0, //最新采购价
                    'shipCost' => $value['avg_freight'] // 平均采购运费
                ];

                $json_encode_string = json_encode($avgPriceData,JSON_UNESCAPED_UNICODE);
                $json_encode_string = str_replace('"0.00"','0.00',$json_encode_string);
                $erp_result_data = send_http($to_erp_purchase_price_url,$json_encode_string);
                //日志

                if($degbug){
                    var_dump($erp_result_data).'<br>';
                }
                $is_push_to_erp_true = $this->db->where('id', $value['id'])->update('purchase_avg_fuse',['is_push_to_erp'=>1,'lastest_push_date'=>date('Y-m-d H:i:s')]);
                if($is_push_to_erp_true){
                    var_dump('推送成功');
                }

            }

        }else{
            var_dump('没有数据要推送') ;exit;
        }


    }


    /**
     * 推送前一天计算的平均运费到ERP
     * /purchase_avg/push_avg_price_to_erp
     * @author Jaden 2019-1-17
    */
    public function push_avg_price_to_erp_1(){
        set_time_limit(0);
        $start_time = $this->input->get_post('start_time');
        $end_time = $this->input->get_post('end_time');
        $push_all = $this->input->get_post('push_all');
        $degbug = $this->input->get_post('degbug');
        if(empty($start_time) || empty($end_time)){
            $start_time = date("Y-m-d")." 00:00:00";
            $end_time = date("Y-m-d")." 23:59:59";
        }else{
            $start_time = $start_time;
            $end_time = $end_time;
        }
        if($push_all){
            $where = 'is_push_to_erp=0';
        }else{
            $where = 'update_time>="'.$start_time.'" and update_time<="'.$end_time.'" and is_push_to_erp=0';
        }
        
        //查询当天入库的记录
       $limit = 300;
       $success_list = array();
       $pur_purchase_avg_list = $this->db->select('id,sku,avg_freight,avg_purchase_price,avg_price')
                    ->from('pur_purchase_avg_fuse')
                    ->limit($limit)
                    ->where($where)
                    ->get()->result_array();


        if(!empty($pur_purchase_avg_list)){
            foreach ($pur_purchase_avg_list as $key => $value) {
                $product_info = $this->product_model->get_product_info($value['sku']);
                $pur_purchase_avg_list[$key]['latest_purchase_price'] = !empty($product_info['purchase_price']) ? $product_info['purchase_price'] :0;
            }
            // 加载 URL 配置项
            $this->load->config('api_config', FALSE, TRUE);
            $push_url = getConfigItemByName('api_config','erp_system','product_scree','push_avg_price_erp');
            if($degbug){
                var_dump($push_url).'<br>';
            }
            $post_data['data'] = json_encode($pur_purchase_avg_list);
            $result = $result = getCurlData($push_url,$post_data);
            $result_arr = json_decode($result,true);
            if($degbug){
                var_dump($result).'<br>';
            }
            //日志
            $api_log=[
                         'record_number'=>'pust_avg_price_to_erp',
                         'api_url'=>$push_url,
                         'record_type'=>'平均运费推送到ERP',
                         'post_content'=>$post_data['data'],
                         'response_content'=>$result,
                         'create_time'=>date('Y-m-d H:i:s')
                         ];
                        $this->db->insert('api_request_log',$api_log);

            if($degbug){
                var_dump($result_arr).'<br>';
            }            
            if(!empty($result_arr) && is_array($result_arr)){
                $is_push_to_erp_true = $this->db->where_in('id', $result_arr)->update('purchase_avg_fuse',['is_push_to_erp'=>1,'lastest_push_date'=>date('Y-m-d H:i:s')]);
                if($is_push_to_erp_true){
                    var_dump('推送成功');
                }
            }else{
                var_dump($result);
            }

        }else{
            var_dump('没有数据要推送') ;exit;
        } 


    }


    /**
     * 获取老系统推送的平均数据
     * /purchase_avg/get_avg_price_list
     * @author Jaden 2019-1-17
    */
    public function get_avg_price_list(){
        $avg_list_json = $_REQUEST['avg_list'];
        $result_msg = array('code' => 0,'msg' => '','success_list' => array());
        if(empty($avg_list_json)){
           $result_msg['code'] = 500;
            $result_msg['msg']  = '数据为空';
            echo json_encode($result_msg);
            exit; 
        }
        $avg_arr_list = json_decode($avg_list_json, true);
        if(!is_array($avg_arr_list)){
            $result_msg['code'] = 500;
            $result_msg['msg']  = '推送数据格式错误!';
            echo json_encode($result_msg);
            exit;
        }

        foreach ($avg_arr_list as $key => $value) {
            $update_data = $data_log = array();
            //获取产品数据
            $product_info = $this->product_model->get_product_info($value['sku']);
            $sku_avg_info = $this->purchase_avg_fuse_model->get_info_by_sku($value['sku']);//获取最新计算的平均运费值

            $update_data['sku'] = $value['sku'];
            $update_data['avg_freight'] = $value['avg_freight'];
            $update_data['avg_purchase_price'] = $value['avg_purchase_price'];
            $update_data['avg_price'] = $value['avg_price'];
            $update_data['latest_purchase_price'] = !empty($product_info['purchase_price']) ? $product_info['purchase_price'] :0;
            $update_data['update_time'] = date('Y-m-d H:i:s');
            $update_data['is_push_to_erp'] = 0;
            $update_data['is_push_to_product'] = 0;
            if(empty($sku_avg_info)){
                $update_data['create_time'] = date('Y-m-d H:i:s');
                $result = $this->purchase_avg_fuse_model->insert_one($update_data);
            }else{
                $result = $this->purchase_avg_fuse_model->update_one($value['sku'],$update_data);
            }

            //日志
            $data_log['sku'] = $value['sku'];
            $data_log['avg_freight'] = $value['avg_freight'];
            $data_log['avg_purchase_price'] = $value['avg_purchase_price'];
            $data_log['avg_price'] = $value['avg_price'];
            $data_log['latest_purchase_price'] = !empty($product_info['purchase_price']) ? $product_info['purchase_price'] :0;
            $data_log['create_time'] = date('Y-m-d H:i:s');
            $this->db->insert('sku_avg_log',$data_log);

            if($result){
                $result_msg['code'] = 200;
                $result_msg['msg']  = '数据插入成功';
                $result_msg['success_list'][] = $value['sku'];
            }else{
                $result_msg['code'] = 500;
                $result_msg['msg']  = '数据插入失败';   
            }
        }
        echo json_encode($result_msg);
        exit;


    }





    /**
     * 获取最新一条平均数据
     * /purchase_avg/get_avg_price_to_old_purchase
     * @author Jaden 2019-1-17
    */
    public function get_avg_price_to_old_purchase(){
        $sku = $this->input->get_post('sku');
        $result_msg = array('code' => 0,'msg' => '','success_list' => array());
        if(empty($sku)){
            $result_msg['code'] = 500;
            $result_msg['msg']  = 'SKU不能为空';
            echo json_encode($result_msg);
            exit;
        }
        $avg_fuse_info = $this->purchase_avg_fuse_model->get_info_by_sku($sku);
        if(!empty($avg_fuse_info)){
            $result_msg['code'] = 200;
            $result_msg['msg']  = '数据获取成功';
            $result_msg['success_list'] = $avg_fuse_info;
            echo json_encode($result_msg);
            exit; 
        }else{
            $result_msg['code'] = 500;
            $result_msg['msg']  = '没有SKU相关数据';
            echo json_encode($result_msg);
            exit;    
        }

    }

    /**
     * function:未入库SKU 平均采购成本推送到产品系统
     * @author:luxu
     * @time:2020/8/4
     **/

    public function pushNoWarehouse(){

        try{

            $totalQuery = "
               SELECT
                    COUNT(*) AS total
                FROM
                    pur_product_update_log
                WHERE
                    new_supplier_price != old_supplier_price
                AND audit_status = 3
                AND sku NOT IN (
                    SELECT
                        sku
                    FROM
                        pur_warehouse_results
                    GROUP BY
                        sku
                )
            ";
            $total = $this->db->query($totalQuery)->row_array();
            $limit = 2000;
            $page = ceil($total['total']/$limit);
            $this->load->config('api_config', FALSE, TRUE);
            $erp_system = $this->config->item('erp_system');
            $to_erp_purchase_price_url = $erp_system['purchase_price_to_erp']."?access_token=". getOASystemAccessToken();
            for($i=1;$i<=$page;++$i){

                $sql = "  SELECT
                    *
                FROM
                    pur_product_update_log
                WHERE
                    new_supplier_price != old_supplier_price
                AND audit_status = 3
                AND sku NOT IN (
                    SELECT
                        sku
                    FROM
                        pur_warehouse_results
                    GROUP BY
                        sku
                ) GROUP BY sku  ORDER BY id DESC LIMIT ".($i-1)*$limit.",".$limit;

                $result = $this->db->query($sql)->result_array();
                if( !empty($result)){

                    foreach($result as $key=>$value){

                        $shipCost = '0.00';
                        $send_data = array(

                            'sku' => $value['sku'],
                            'lastPrice' => (float)$value['new_supplier_price'],
                            'avgPrice'  => (float)$value['new_supplier_price'],
                            'shipCost'  => $shipCost,
                            'newPrice'  => (float)$value['new_supplier_price']
                        );

                        $json_encode_string = json_encode($send_data,JSON_UNESCAPED_UNICODE);
                        $json_encode_string = str_replace('"0.00"','0.00',$json_encode_string);
                        $erp_result_data = send_http($to_erp_purchase_price_url,$json_encode_string);
                    }
                }
            }

        }catch ( Exception $exp ){


        }
    }


    

}