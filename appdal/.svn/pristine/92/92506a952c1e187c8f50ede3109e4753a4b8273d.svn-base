<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * 接收在途和可用库存
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Supplier_product_line_calculate extends MY_API_Controller
{
    public function __construct(){
        parent::__construct();

    }




    /**
     * *注意：此处为定时任务，每天00:00更新
     * 定时更新BI接口token
     * @author yefanli
     */
    public function sync_bi_token()
    {
        $header           = array('Content-Type: application/json');
        $request_url      = 'http://python2.yibainetwork.com/yibai/python/services/jwt/token';
        $iss = 'technical_sh_purchase';     // 签发者
        $secret = 'd2Dw.3Qldacnr4';         // 密匙
        $request_url = $request_url.'?iss='.$iss.'&secret='.$secret;
        try{
            $result = getCurlData($request_url, '', 'get', $header);
            $results = json_decode($result, true);
            if(isset($results['status']) && $results['status'] == 200 && isset($results['jwt']) && !empty($results['jwt'])){
                $this->load->library('rediss');
                $cache = [
                    "jwt"   => $results['jwt'],
                    "exp"   => $results['exp'],
                ];
                if($this->rediss->setData('SYNC_BI_INVENTORY_REQUEST_TOKEN', json_encode($cache)))echo 'success';
            }
        }catch (Exception $e){
            echo '同步数据出错！原因：'.$e->getMessage();
        }
    }

    /**
     * 获取缓存数据
     * @author yefanli
     */
    private function get_bi_jwt()
    {
        $res = ["code" => 0, "msg" => ''];
        $this->load->library('rediss');
        $cache_jwt = $this->rediss->getData('SYNC_BI_INVENTORY_REQUEST_TOKEN');
        if(!$cache_jwt){
            $this->sync_bi_token();
            $cache_jwt = $this->rediss->getData('SYNC_BI_INVENTORY_REQUEST_TOKEN');
            if(!$cache_jwt){
                $res['msg'] = '没有获取到相应的token，请联系BI处理。';
                return $res;
            }
        }
        $cache_jwt = json_decode($cache_jwt, true);
        if(!isset($cache_jwt['jwt'])){
            $res['msg'] = '没有获取到token，请联系IT人员处理。';
            return $res;
        }
        if(isset($cache_jwt['exp']) && $cache_jwt['exp'] < date('Y-m-d H:i:s')){
            $res['msg'] = 'token 超时，请重试。';
            return $res;
        }
        $res['code'] = 1;
        $res['msg'] = $cache_jwt['jwt'];
        return $res;
    }
    /*
     * @desc 从bi拉取供应商产品线数据
     * @author Dean
     */
    public function sync_supplier_product_line()
    {

        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $now_time = date('Y-m-d H:i:s');
        $header           = array('Content-Type: application/json');
        $cache_jwt = $this->get_bi_jwt();
        $token = false;
        if($cache_jwt && isset($cache_jwt['code']) && $cache_jwt['code'] === 1){
            $token = $cache_jwt['msg'];
        }else{
            echo $cache_jwt['msg'];
            exit;
        }
        $flag= true;
        $page = 1;
        //开始拉取

        do{


            $insert_batch = [];
            $update_batch = [];
            $params = ['page'=>$page,'size'=>500];
            $request_url = 'bi.yibainetwork.com:8000/bi/dwh/odl_pur_product_query';
            $request_url = $request_url.'?jwt='.$token.'&'.http_build_query($params);
            $results = getCurlData($request_url, '', 'get', ['Content-Type: application/json']);
            $results = json_decode($results, true);




            if ($results['status']==200&&!empty($results['data_list'])) {

                foreach ($results['data_list'] as $info) {
                    //查询供应商产品线是否
                    $supplier_info = $this->db->select('*')->from('pur_supplier_analysis_product_line')->where('supplier_code',$info['supplier_code'])->get()->result_array();
                    if (empty($supplier_info)) {
                        $insert_data = [
                            'supplier_code' => $info['supplier_code'],
                            'supplier_name' => $info['supplier_name'],
                            'first_product_line' => $info['first_product_line'],
                            'second_product_line' => $info['second_product_line'],
                            'create_user_name'    => 'bi_system',
                            'create_time'         =>$now_time

                        ];
                        $insert_batch[] = $insert_data;

                    } else {

                         $update_data = [
                            'supplier_code' => $info['supplier_code'],
                            'supplier_name' => $info['supplier_name'],
                            'first_product_line' => $info['first_product_line'],
                            'second_product_line' => $info['second_product_line'],
                            'modify_user_name'    => 'bi_system',

                        ];
                        $update_batch[] = $update_data;

                    }


                }

                if (!empty($insert_batch)) {
                    $this->db->insert_batch('pur_supplier_analysis_product_line',$insert_batch);

                }

                if (!empty($update_batch)) {
                    $this->db->update_batch('pur_supplier_analysis_product_line',$update_batch,'supplier_code');

                }


            } else {
                $flag = false; break;
            }
            $page++;
            sleep(1);




        }while($flag);


        echo 'done';






    }


    public function sync_supplier_level_grade()
    {

        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->load->helper('status_supplier');
        $this->load->model('supplier/supplier_model');

        $cache_jwt = $this->get_bi_jwt();
        if($cache_jwt && isset($cache_jwt['code']) && $cache_jwt['code'] === 1){
            $token = $cache_jwt['msg'];
        }else{
            echo $cache_jwt['msg'];
            exit;
        }

        $level_list  = getSupplierLevel();

        $level_list_flip  = array_flip($level_list);


        $request_url = 'bi.yibainetwork.com:8000/bi/dwh/yibai_supplier_level';
        $request_url = $request_url.'?jwt='.$token;
        $results = getCurlData($request_url, '', 'get', ['Content-Type: application/json']);
        $results = json_decode($results, true);


        if ($results['status'] == 200 &&!empty($results['data'])) {
            $data_list = array_chunk($results['data'],100);


            foreach ($data_list as $data) {
                $update_batch = [];
                foreach ($data as $data_detail) {
                              $supplier_info = $this->supplier_model->get_supplier_info($data_detail['supplier_code'],false);
                              if (!empty($supplier_info)) {
                                  $change_data_log = [];
                                  if ($supplier_info['supplier_level']!=$level_list_flip[$data_detail['supplier_level']]) {
                                      $before_v = !empty($supplier_info['supplier_level'])?$level_list[$supplier_info['supplier_level']]:'';
                                      $current_v = $data_detail['supplier_level'];
                                      $change_data_log['basic']['supplier_level'] = '修改前:'.$before_v.';修改后:'.$current_v;

                                  }

                                  if ($supplier_info['supplier_grade'] != $data_detail['total_score']) {

                                      $change_data_log['basic']['supplier_grade'] = '修改前:'.$supplier_info['supplier_grade'].';修改后:'.$data_detail['total_score'];


                                  }
                                  if (!empty($change_data_log)) {
                                      $all_change_data_log =['change_data_log'=>$change_data_log];
                                      $update_batch[] = ['supplier_code'=>$data_detail['supplier_code'],'supplier_grade'=>$data_detail['total_score'],'supplier_level'=>$level_list_flip[$data_detail['supplier_level']]??0];
                                      operatorLogInsert(
                                          [
                                              'id'      => $data_detail['supplier_code'],
                                              'type'    => 'supplier_update_log',
                                              'content' => "供应商信息修改",
                                              'detail'  => json_encode($all_change_data_log),
                                              'operate_type'  => SUPPLIER_LEVEL_GRADE_OPR,
                                              'user_id'=>0,
                                              'user'  =>'BI_SYSTEM'

                                          ]
                                      );


                                  }

                              }


                }
                if (!empty($update_batch)) {
                    $this->db->update_batch('supplier',$update_batch,'supplier_code');

                }

            }



        }



        echo 'done';





    }

    //跑审核信息历史数据
    public function history_update_log_set()
    {

        //获取所有历史数据
        set_time_limit(0);
        ini_set('memory_limit', '1024M');


        $logs = $this->db->select('id,message')->from('supplier_update_log')->get()->result_array();



        if (!empty($logs)) {
            foreach ($logs as $data ) {
                $message = json_decode($data['message'],true);

                if (!empty($message)) {
                    $update_data = [];
                    if(!empty($message['change_data']['basis_data'])) $update_data['is_basic_change'] = 1;
                    if(!empty($message['change_data_log']['supplier_relation'])) $update_data['is_relation_change'] = 1;
                    if(!empty($message['change_data_log']['contact'])||!empty($message['insert_data']['contact_data'])) $update_data['is_contact_change'] = 1;
                    if(!empty($message['change_data_log']['payment_data'])||!empty($message['insert_data']['payment_data'])) $update_data['is_payment_change'] = 1;
                    if(!empty($message['change_data_log']['images'])||!empty($message['insert_data']['images_data'])) $update_data['is_proof_change'] = 1;
                    if (!empty($update_data)) {
                        $this->db->where('id',$data['id'])->update('supplier_update_log',$update_data);


                    }


                }



            }

        }

        echo 'done';




    }

    //每日凌晨更新供应商业务线
    public function update_supplier_business_line()
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $all = $this->input->get_post('all');
        $all = empty($all)?0:$all;
        $this->load->model('supplier/supplier_model');
        $this->supplier_model->update_supplier_business_line($all);
        $this->success_json();

    }

    //供应商sku_num
    public function sync_supplier_sku_num_info()
    {


        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->load->helper('status_product');
        $product_status = getProductStatus();
        $product_status_list =array_keys($product_status);

        $supplier_list = $this->db->select('supplier_code')->from('supplier')->get()->result_array();
        $supplier_arr = array_chunk($supplier_list,200);
        foreach ($supplier_arr as $part) {
            $update_batch = [];
            $status_arr_num = [];
            $supplier_codes = array_column($part,'supplier_code');


            $status_result  = $this->db->select('supplier_code,product_status,count(*) as num')->from('product')->where_in('supplier_code',$supplier_codes)->where('product_type',1)->where('is_multi!=2')->group_by('supplier_code,product_status')->get()->result_array();
            if (!empty($status_result)) {
                foreach ($status_result as $status_info) {
                    $status_arr_num[$status_info['supplier_code'].'-'.$status_info['product_status']] = $status_info['num'];

                }
                foreach ($supplier_codes as $code) {
                    $sku_num = 0;
                    $sku_sale_num = 0;
                    $sku_no_sale_num = 0;
                    $sku_other_num   = 0;

                    foreach ($product_status_list as $product_status) {
                        $sku_status_num = $status_arr_num[$code.'-'.$product_status]??0;
                        if (in_array($product_status,[4,18])) {
                            $sku_sale_num+=$sku_status_num;

                        }elseif($product_status == 7){
                            $sku_no_sale_num+=$sku_status_num;

                        }elseif(!in_array($product_status,[7,4,18])) {
                            $sku_other_num+=$sku_status_num;

                        }
                        $sku_num +=$sku_status_num;


                    }
                    $update_batch[] = ['supplier_code'=>$code,'sku_num'=>$sku_num,'sku_sale_num'=>$sku_sale_num,'sku_no_sale_num'=>$sku_no_sale_num,'sku_other_num'=>$sku_other_num];

                }


            } else {
                foreach ($supplier_codes as $code) {
                    $update_batch[] = ['supplier_code'=>$code,'sku_num'=>0,'sku_sale_num'=>0,'sku_no_sale_num'=>0,'sku_other_num'=>0];

                }

            }

            $this->db->update_batch('supplier',$update_batch,'supplier_code');



        }

        echo 'done';


    }




}