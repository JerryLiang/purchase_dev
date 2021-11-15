<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * 接收在途和可用库存
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Stock extends MY_API_Controller
{
    public function __construct(){
        parent::__construct();
        $this->load->model('warehouse/stock_model');
    }
    /**
     * 接收可用和在途库存数据
    /stock/create_stock
     * @author Jaden
     */
    public function create_stock(){
        $datas = $_REQUEST['purchaseStock'];
        if(isset($datas) && !empty($datas))
        {
            $data = [];
            $data['success_list'] = [];
            $data['failure_list'] = [];
            echo json_encode($data);exit;
            $datas  = json_decode($datas,true);
            $data   = $this->stock_model->find_ones($datas);
            echo json_encode($data);
        } else {
            return '没有任何的数据过来！';
        }
    }

    /**
     * 调用BI数据同步库存、在途
     * *注意：此处为定时任务，每天00:10更新
     * @author yefanli
     */
    public function sync_stock()
    {
        $header           = array('Content-Type: application/json');
        $cache_jwt = $this->get_bi_jwt();
        $token = false;
        if($cache_jwt && isset($cache_jwt['code']) && $cache_jwt['code'] === 1){
            $token = $cache_jwt['msg'];
        }else{
            echo $cache_jwt['msg'];
            exit;
        }

        $handle = $this->stock_model->get_sync_stock_sku();
        if(!$handle || count($handle) == 0){
            echo "没有需要处理的sku";
            exit;
        }

        $handle_len = 1000;
        if(count($handle) <= $handle_len){
            $handle = [$handle];
        }else{
            $handle = array_chunk($handle, $handle_len);
        }
        $x = 0;

        foreach ($handle as $val){
            try{
                $params = ['sku' => $val];
                $url = 'http://bi.yibainetwork.com:8000/bi/customer_service/warehouse_stock?jwt='.$token;
                $results = getCurlData($url, json_encode($params), 'post', $header);
                $results = json_decode($results, true);
                if(isset($results['status']) && $results['status'] == 200 && isset($results['data']) && count($results['data']) > 0){
                    $handle_data = $this->handle_results_data($results['data']);
                    // 如有，刷新数据
                    if(count($handle_data) > 0)$this->stock_model->update_sync_inventory($handle_data);
                }
                sleep(1);
            }catch (Exception $e){}
            $x ++;
        }
    }

    /**
     * 处理返回数据
     */
    private function handle_results_data($data)
    {
        if(!is_array($data) || count($data) == 0)return [];
        $handle_data = [];
        foreach ($data as $k=>$v){
            if(empty($v))continue;
            if(!in_array($k, array_keys($handle_data)))$handle_data[$k] = [];
            foreach ($v as $val){
                $handle_data[$k][] = [
                    "available_stock"       => isset($val['available_stock'])?$val['available_stock']:0,
                    "can_match_stock"       => isset($val['can_match_stock'])?$val['can_match_stock']:0,
                    "onway_stock"           => isset($val['onway_stock'])?$val['onway_stock']:0,
                    "warehouseCode"        => isset($val['warehouseCode'])?$val['warehouseCode']:0,
                ];
            }
        }
        if(count($handle_data) > 0)return $handle_data;
        return [];
    }

    /**
     * 获取sku在途信息
     */
    public function get_sku_message()
    {
        $res = ["status" => 1, "errorMess" => "暂无数据", "data_list" => []];
        $sku = $this->input->get_post("sku");
        if(empty($sku)){
            $this->error_json("SKU不能为空");
        };
        $this->load->model('purchase/purchase_order_model');
        $result = $this->purchase_order_model->get_sku_message($sku);
        $stocks = [];
        if(SetAndNotEmpty($result, 'stocks_warehouse')){
            $onWays = $this->request_bi_inventory_data(["sku" => [$sku]]);
            if($onWays['code'] == 1 && isset($onWays['msg'][$sku])){
                $warehouse = $this->purchase_order_model->purchase_db->from("warehouse")->select("warehouse_code,warehouse_name")->get()->result_array();
                $ware_list = [];
                foreach ($warehouse as $val){
                    $ware_list[$val['warehouse_code']] = $val['warehouse_name'];
                }
                foreach ($onWays['msg'][$sku] as &$val){
                    $stocks[] = [
                        "on_way_stock"      => $val['onway_stock'], // 在途
                        "available_stock"   => $val['available_stock'], // 可用数量
                        "warehouse_code"    => $val['warehouseCode'],
                        "warehouse_name"    => isset($ware_list[$val['warehouseCode']]) ? $ware_list[$val['warehouseCode']] : "",
                    ];
                }
            }
        }
        if(count($stocks) > 0){
            $result['stocks_warehouse'] = $stocks;
        }
        $this->success_json($result);
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

    /**
     * 发送请求数据
     * @author yefanli
     */
    private function request_bi_inventory_data($params)
    {
        $res = ['code' => 0, 'msg' => ''];
        $header           = array('Content-Type: application/json');
        $request_url      = 'http://python2.yibainetwork.com/yibai/python/services/jwt/token';

        $cache_jwt = $this->get_bi_jwt();
        $token = false;
        if($cache_jwt && isset($cache_jwt['code']) && $cache_jwt['code'] === 1){
            $token = $cache_jwt['msg'];
        }else{
            $res['msg'] = $cache_jwt['msg'];
            return $res;
        }

        $request_url = 'http://bi.yibainetwork.com:8000/bi/customer_service/warehouse_stock';
        $request_url = $request_url.'?jwt='.$token;
        $results = getCurlData($request_url, json_encode($params), 'post', $header);
        $results = json_decode($results, true);
        if(isset($results['status']) && $results['status'] == 200 && isset($results['data']) && count($results['data']) > 0){
            $res['code'] = 1;
            $res['msg'] = $results['data'];
        }else{
            $res['msg'] = $results['message'];
            log_message('error',sprintf('调用查询仓库库存接口异常,入参: %s,结果: %s',json_encode($params),$results));
        }
        return $res;
    }

    /**
     * *注意：此处为定时任务，每天00:10更新
     * 批量从bi获取库存和在途定时任务
     * @author yefanli
     */
    public function sync_stock_by_bi()
    {
        $this->load->library('rediss');
        $this->stock_model->get_update_date_and_cache();
        $cache_keys = 'PUR_WAIT_HANDLE_SYNC_INVENTORY';
        $len = $this->rediss->llenData($cache_keys); // 获取数据长度
        if(!$len || $len == 0){
            echo "没有要处理的数据";
            exit;
        }

        $limit = 1000; // 每次处理数据长度
        $sum = 0;
        $sum_len = ceil($len / $limit);
        for($i=0;$i<$sum_len;$i++){
            try{
                $one_list = [];
                if(($len - $sum )< $limit)$limit = $len - $sum;
                for($j=0;$j<$limit;$j++){
                    $one = $this->rediss->rpopData($cache_keys);
                    if(!$one)continue;
                    $one = explode("###", $one);
                    if(count($one) != 2 || !isset($one[0]) || empty($one[0]) && in_array($one[0], $one_list))continue;
                    $one_list[] = $one[0];
                    echo $j."===".$one[0]."<br />";
                }
                $sum = $sum + $limit;

                // 获取数据
                $request_data = $this->request_bi_inventory_data(["sku" => $one_list]);
                echo $request_data['code'].",msg:".gettype($request_data['msg'])."<br />";

                if($request_data && isset($request_data['code']) && $request_data['code'] == 1 && gettype($request_data['msg']) == 'array' && count($request_data['msg']) > 0){
                    // 如有，刷新数据
                    $this->stock_model->update_sync_inventory($request_data['msg']);
                }
                sleep(2);
                break;
            }catch (Exception $e){}
        }
    }

}