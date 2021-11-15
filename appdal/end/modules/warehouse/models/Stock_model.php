<?php
/**
 * Created by PhpStorm.
 * 库存表
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Stock_model extends Purchase_model {
    protected $table_name   = 'stock';// 库存表

    public function __construct(){
        parent::__construct();
    }

    public function tableName() {
        return $this->table_name;
    }

    public function find_ones($datas){

        foreach ($datas as $k=>$v)
        {

            $stock_info= $this->get_stock_info($v['warehouse_code'],$v['sku']);
            $insert_data['sku'] = $v['sku'];
            $insert_data['real_stock'] = $v['stock'];
            $insert_data['on_way_stock'] = $v['on_way_stock'];
            $insert_data['available_stock'] = $v['available_stock'];
            $insert_data['warehouse_code'] = $v['warehouse_code'];
            $insert_data['created_at'] = date('Y-m-d H:i:s',time());
            if (empty($stock_info))
            {//不存在插入，存在更新
                $result = $this->purchase_db->insert($this->table_name, $insert_data);
            } else {
                $insert_data['update_at'] = date('Y-m-d H:i:s',time());
                $where = 'warehouse_code="'.$v['warehouse_code'].'" and sku="'.$v['sku'].'"';
                $result = $this->update_stock($where,$insert_data);
            }
            if($result){
                $data['success_list'][$k]['warehouse_code']       = $v['warehouse_code'];
                $data['success_list'][$k]['sku']                  = $v['sku'];
            }else{
                $data['failure_list'][$k]['warehouse_code']       = $v['warehouse_code'];
                $data['failure_list'][$k]['sku']                  = $v['sku'];
            }

        }
        return $data;

    }

    /**
     * 根据 仓库code和sku查数据
     * @author Jaden
     * @param $warehouse_code
     * @param $sku
     * @return array
     * 2019-03-15
     */
    public function get_stock_info($warehouse_code,$sku){
        if(empty($warehouse_code) || empty($sku)){
            return false;
        }
        $this->purchase_db->where('warehouse_code',$warehouse_code);
        $this->purchase_db->where('sku',$sku);
        $stock_info = $this->purchase_db->get($this->table_name)->row_array();
        return $stock_info;
    }

    /**
     * 根据 sku查数据
     * @author Jaden
     * @param $warehouse_code
     * @param $sku
     * @return array
     * 2019-03-15
     */
    public function get_stock_info_by_sku($sku){
        if(empty($sku)){
            return [];
        }
        $this->purchase_db->where('sku',$sku);
        $stock_info = $this->purchase_db->get($this->table_name)->row_array();
        return $stock_info;
    }

    /**
     * 获取 sku 的总库存数
     * @author Jolon
     * @param $sku
     * @return array
     * 2019-03-15
     */
    public function get_stock_total_stock($sku){
        if(empty($sku)) return false;
        $this->purchase_db->select('sum(real_stock) as real_stock,sum(on_way_stock) as on_way_stock,sum(available_stock) as available_stock');
        $this->purchase_db->where('sku',$sku);
        $stock_info = $this->purchase_db->get($this->table_name)
            ->row_array();
        return $stock_info;
    }

    /**
     * sku查数据
     * @author Jaden
     * @param $skus
     * @return array
     * 2019-03-15
     */
    public function get_stock_list_by_skus($skus){
        if( empty($skus) ){
            return false;
        }
        $stock_list = $this->purchase_db->select('sku,SUM(available_stock) available_stock, SUM(on_way_stock) on_way_stock')
            ->where_in('sku',$skus)
            ->group_by('sku')
            ->get($this->table_name)->result_array();
        $stock_sku_arr = array();
        if (!empty($stock_list)){
            $stock_sku_arr = array_column($stock_list,NULL,'sku');
        }
        return $stock_sku_arr;
    }

     /**
     * 修改数据
     * @author Jaden
     * @param strint $where  
     * @param array $update_data  需要改变数据
     * @return bool
     * 2019-1-16
     */
     public function update_stock($where,$update_data){
        if(empty($where) || empty($update_data)) {
            return false;
        }    
        $result = $this->purchase_db->where($where)->update($this->table_name, $update_data);
        return $result;
    }

    /**
     * 查询库存
     * http://192.168.71.156/web/#/105?page_id=3895
     * @author Manson
     * @return array
     */
    public function get_stock_by_java($params)
    {
        //入参
        $header           = array('Content-Type: application/json');
        $request_url      = getConfigItemByName('api_config', 'java_es_api', 'get_stock');
        $access_token     = getOASystemAccessToken();
        $request_url      = $request_url.'?access_token='.$access_token;
        $results          = getCurlData($request_url, json_encode($params), 'post', $header);
        $results          = json_decode($results, true);
//pr($results);exit;
        if(isset($results['code']) && $results['code'] == 200 && isset($results['data']['content'])){
            $data['total'] = $results['data']['totalElements'];
            $data['data'] = $results['data']['content'];
            $data['sortId'] = end($results['data']['content'])['id'];
            return $data;
        }else{
            $results = json_encode($results);
            log_message('error',sprintf('调用查询仓库库存接口异常,入参: %s,结果: %s',json_encode($params),$results));
            return [];
        }
    }

    public function sync_stock($result)
    {
        $data = [];
        foreach ($result as $k => $v){
            $stock_info= $this->stock_model->get_stock_info($v['warehouseCode'],$v['sku']);
            $insert_data['sku'] = $v['sku'];
            $insert_data['real_stock'] = $v['stock'];
            $insert_data['on_way_stock'] = $v['onWayStock'];
            $insert_data['available_stock'] = $v['availableStock'];
            $insert_data['warehouse_code'] = $v['warehouseCode'];

            if (empty($stock_info)){//不存在插入，存在更新
                $insert_data['created_at'] = date('Y-m-d H:i:s',time());
                $insert_res = $this->purchase_db->insert($this->table_name, $insert_data);
            } else {
                $insert_data['update_at'] = date('Y-m-d H:i:s',time());
                $where = 'warehouse_code="'.$v['warehouseCode'].'" and sku="'.$v['sku'].'"';
                $insert_res = $this->update_stock($where,$insert_data);
            }
            if($insert_res){
                $data['success_list'][$k]['warehouse_code']       = $v['warehouseCode'];
                $data['success_list'][$k]['sku']                  = $v['sku'];
            }else{
                $data['failure_list'][$k]['warehouse_code']       = $v['warehouseCode'];
                $data['failure_list'][$k]['sku']                  = $v['sku'];
            }
            unset($result[$k]);
        }
        return $data;
    }

    /**
     * 获取需要批量更新库存和在途的sku，并缓存
     * @author yefanli
     */
    public function get_update_date_and_cache()
    {
        $this->load->library('rediss');
        $cache_keys = 'PUR_WAIT_HANDLE_SYNC_INVENTORY';
        $next = true;
        $x = 1;
        $y = 0;
        $limit = 10000; // 每次处理 10000 条数据
        echo date('Y-m-d H:i:s');
        do{
            $start = ($x - 1) * $limit;
            $status = [2,7,8,10,12,15];
            $data = $this->purchase_db->from('purchase_order_items as it')
                ->join("pur_purchase_order as o", "it.purchase_number=o.purchase_number", "inner")
                ->select('o.warehouse_code,it.sku')
                ->where_in("o.purchase_order_status", $status)
                ->offset($start)
                ->limit($limit)
                ->get()
                ->result_array();
            if($data && count($data) > 0){
                foreach ($data as $val){
                    $str = $val['sku'].'###'.$val['warehouse_code'];
                    $this->rediss->lpushData($cache_keys, $str);
                    $y ++;
                }
                $x ++;
            }else{
                $next = false;
            }
        } while ($next);
        echo date('Y-m-d H:i:s');
        echo "处理了：".$y;
    }

    /**
     * 获取到数据后更新相应的库存数据
     * @author yefanli
     */
    public function update_sync_inventory($data=[])
    {
        $res = ['code' => 0, "success" => [], "error" => []];
        if(count($data) == 0)return $res;
        $sku = array_keys($data);
        $warehouse = [];
        foreach ($sku as $v){
            if(!$data[$v] || count($data[$v]) == 0)continue;
            foreach ($data[$v] as $val){
                if(!in_array($val['warehouseCode'], $warehouse))$warehouse[] = $val['warehouseCode'];
            }
        }

        $has_inventory = $this->check_inventory($sku, $warehouse);
        $date = date('Y-m-d H:i:s');

        foreach ($sku as $v){
            if(!$data[$v] || count($data[$v]) == 0)continue;
            foreach ($data[$v] as $val){
                // 设定更新和新增项
                $str = $v.$val['warehouseCode'];
                $handle = [
                    "on_way_stock"      => $val['onway_stock'], // 在途
                    "real_stock"        => $val['available_stock'], // 实际库存数量
                    "available_stock"   => $val['available_stock'], // 可用数量
                ];
                if(in_array($str, $has_inventory)){
                    // 更新
                    try{
                        $handle["update_at"] = $date;
                        $where = ["warehouse_code" => $val['warehouseCode'], "sku" =>$v];
                        $this->purchase_db->where($where)->update($this->table_name, $handle);
                        echo $v."更新<br />";
                    }catch (Exception $e){
                        $res['error'] = 'SKU:'.$v."，仓库:".$val['warehouseCode']."新增失败。";
                    }
                }else{
                    // 新增
                    try{
                        $handle["created_at"] = $date;
                        $handle["sku"] = $v;
                        $handle["warehouse_code"] = $val['warehouseCode'];
                        $this->purchase_db->insert($this->table_name, $handle);
                        echo $v."新增<br />";
                    }catch (Exception $e){
                        $res['error'] = 'SKU:'.$v."，仓库:".$val['warehouseCode']."更新失败。";
                    }
                }
            }
        }
        $res['code'] = 1;
        return $res;
    }

    /**
     * 检查库存
     * @author yefanli
     */
    private function check_inventory($sku=[], $warehouse=[])
    {
        $res = [];
        if(count($sku) == 0 || count($warehouse) == 0)return $res;
        $data = $this->purchase_db->from($this->table_name)
            ->select('sku,warehouse_code')
            ->where_in('warehouse_code', $warehouse)
            ->where_in('sku', $sku)
            ->get()
            ->result_array();
        if($data && count($data) > 0){
            foreach ($data as $val){
                $str = $val['sku'].$val['warehouse_code'];
                if(!in_array($str, $res))$res[] = $str;
            }
        }
        return $res;
    }

    /**
     * 获取可调拨sku
     */
    public function get_sync_stock_sku()
    {
        $res = [];
        $data = $this->purchase_db->from('purchase_order_items')->select('DISTINCT(sku) as sku')->get()->result_array();
        if($data && count($data) > 0)$res = array_column($data, 'sku');
        return $res;
    }

}