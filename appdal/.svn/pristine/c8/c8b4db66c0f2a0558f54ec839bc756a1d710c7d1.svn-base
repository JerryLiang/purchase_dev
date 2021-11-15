<?php
/**
 * Created by PhpStorm.
 * 缺货列表定时任务控制器
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Shortage_api extends MY_Controller{

    public function __construct(){
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('product/Sku_outofstock_statisitics_model','outofstock');
        $this->load->model('Parcel_urgent_model','parcel_urgent_model',false,'warehouse');
        $this->load->model('Shortage_model','shortage_model',false,'product');
    }

    /**
     * 缺货列表
     /shortage_api/get_shortage_list
     * @author Jaden
     */
    public function get_shortage_list(){

        //计算总数
        $total = $this->db->select('sku')->from($this->outofstock->tableName())->group_by('sku')->count_all_results();
        $limit = 500; 
        $num = ceil($total/$limit);
        $field = 'sku,sum(lack_quantity) as left_stock,earlest_outofstock_date';  
        $shortage_arr = array();
        $logistics_arr = array();
        $platform_stock = ''; 
        $this->db->where('1=1')->delete('stock_owes');
        if($total>1){
            for ($i=1; $i <= $num; $i++) { 
                $shortage_arr = array();
                $logistics_arr = array();
                $offset = ($i - 1) * $limit;
                $outofstock_list = $this->outofstock->get_outofstock_list($offset,$limit,$field);
                $skus = array_column($outofstock_list, 'sku');
                //$this->shortage_model->delete_shortage($skus);
                if(!empty($outofstock_list)){
                    foreach ($outofstock_list as $key => $value) {
                        $sku_arr = $this->outofstock->get_platform_code_by_sku($value['sku']);
                        $platform_stock = json_encode( array_column($sku_arr, 'lack_quantity','platform_code') );
                        $add_arr['sku'] = $value['sku'];
                        $add_arr['left_stock'] = $value['left_stock'];
                        $add_arr['statistics_date'] = date('Y-m-d H:i:s');
                        $add_arr['earlest_outofstock_date'] = $value['earlest_outofstock_date'];
                        $add_arr['platform_stock'] = $platform_stock;
                        array_push($shortage_arr,$add_arr);
                               
                    }
                    $this->shortage_model->insert_shortage_batch_all($shortage_arr);
                    $order_list = $this->outofstock->get_order_list($skus);
                    if(!empty($order_list)){
                        foreach ($order_list as $key => $val) {
                            //检测数据是否存在
                            $parcel_info = $this->parcel_urgent_model->get_parcel_urgent_info($val['express_no'],$val['purchase_number']);
                            if(!empty($parcel_info)){
                                continue;
                            }
                            $log_add_arr['logistics_num'] = $val['express_no'];
                            $log_add_arr['purchase_order_num'] = $val['purchase_number'];
                            $log_add_arr['create_id'] = 0;
                            $log_add_arr['create_name'] = '--';
                            $log_add_arr['create_time'] = date('Y-m-d H:i:s');
                            $log_add_arr['update_time'] = date('Y-m-d H:i:s');
                            array_push($logistics_arr,$log_add_arr);
                        }
                        $this->parcel_urgent_model->insert_parcel_batch_all($logistics_arr);
                    }
                }
                usleep(100000);
            }
        }
        var_dump('OK');                    

                            


    }

    /**
     * 智库系统的缺货数量
     * @author Manson
     */

    public function think_shortage_list()
    {
        //查询智库系统的缺货数据
        ini_set('memory_limit','1024M');
        set_time_limit(0);

        //请求URL
        $request_url = getConfigItemByName('api_config', 'think_tank_system', 'owe_list');
        if (empty($request_url)) $this->error_json('请求URL不存在');
        $result = getCurlData($request_url,[],'get');
        if (empty($result)) $this->error_json('接口返回数据为空');
        $result = json_decode($result,true);
        if (isset($result['status']) && $result['status'] == 1 && !empty($result['data_list'])){
            $result = $result['data_list'];
        }else{
            $this->error_json('接口返回数据异常');
        }

        if (isset($result[0]['sku']) && !empty($result[0]['sku']) && isset($result[0]['statis_time']) && !empty($result[0]['statis_time']) ){
            $statis_time = $this->db->select('statis_time')
                ->from('think_lack_info')
                ->where('sku',$result[0]['sku'])
                ->get()->row_array();

            if ($result[0]['statis_time'] == $statis_time['statis_time']??''){
                $this->success_json('没有新数据,本次不更新');
            }
        }else{
            $this->error_json('接口数据格式有问题');
        }


        $this->db->where('1=1')->delete('think_lack_info');
        $this->db->insert_batch('think_lack_info',$result);


        //更新采购系统的缺货列表

        //计算总数
        $total = $this->db->select('sku')->from('think_lack_info')->group_by('sku')->count_all_results();
        $limit = 5000;
        $num = ceil($total/$limit);
        $platform_stock = '';
        $this->db->where('1=1')->delete('stock_owes');
        if($total>1){
            for ($i=1; $i <= $num; $i++) {
                $insert_data = [];
                $offset = ($i - 1) * $limit;

//                $this->db->select('a.sku,sum(a.lack_sum) as lack_sum, a.statis_time, b.earlest_outofstock_date')
                $this->db->select('a.sku,sum(a.lack_sum) as lack_sum, a.statis_time')
                    ->from('think_lack_info a');
//                    ->join('pur_sku_outofstock_statisitics b','a.sku = b.sku AND a.platform_code =  b.platform_code','left');
                $think_list = $this->db->limit($limit, $offset)->group_by('a.id,a.sku')->order_by('a.statis_time','DESC')->get()->result_array();

                if(!empty($think_list)){
                    foreach ($think_list as $key => $value) {
                        if (empty($value['sku'])){
                            continue;
                        }
                        $this->db->select('platform_code,sum(lack_sum) as lack_sum');
                        $this->db->from('think_lack_info');
                        $sku_arr =  $this->db->where('sku',$value['sku'])->group_by('platform_code')->get()->result_array();

                        $platform_stock = json_encode( array_column($sku_arr, 'lack_sum','platform_code') );

                        $insert_data[] = [
                            'sku' => $value['sku'],
                            'think_lack_qty' => $value['lack_sum'],
                            'think_statis_time' => $value['statis_time'],
                            'think_platform_info' => $platform_stock,
                            'update_time' => date('Y-m-d H:i:s'),
                            'earlest_outofstock_date' => $value['statis_time'],
                        ];

                    }

                    $this->shortage_model->insert_shortage_batch_all($insert_data);
                }
                usleep(100000);
            }
        }
        var_dump('OK');
    }
}