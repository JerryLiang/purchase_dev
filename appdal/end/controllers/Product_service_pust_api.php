<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * 接收数据中心推送过来的产品信息
 * User: Jaden
 * Date: 2018/12/27 0027 11:17
 */

class Product_service_pust_api extends MY_Controller{

    public function __construct(){
        self::$_check_login = false;
        parent::__construct();
        $this->load->model('product/Product_model','product_model');
        $this->load->model('product/Product_tax_rate_model','product_tax_rate_model');
        $this->load->model('product/Product_combine_model','product_combine_model');
        $this->load->model('supplier/supplier_model','supplier_model',false,'supplier');
        $this->load->model('user/purchase_user_model','purchase_user_model',false,'user');
        $this->load->model('purchase/purchase_order_items_model','purchase_order_items_model',false,'purchase');
    }

    /**
     * 采购系统purchase_price 字段不为0 的情况
     * avg_fuse表无记录：  pms的product表的字段purchase_price写入avg_fuse表，回传erp
       avg_fuse表有记录：  不做任何操作
     * $param $sku   string   SKU
     *        $purchase_price  float   SKU 未税单价
     * $author: luxu
     **/

    private function set_fuse_avg_data($sku,$purchase_price,$erp_purchase_price)
    {
         try {
             //avg_fuse表无记录：  pms的product表的字段purchase_price写入avg_fuse表，回传erp
             $fuse_avg = $this->db->from("purchase_avg_fuse")->where("sku", $sku)->select("id")->get()->row_array();
             if( empty($fuse_avg))
             {
                 $fuse_avg_data = array(
                     'sku' => $sku,
                     'avg_freight' => 0,
                     'avg_purchase_price' => $erp_purchase_price,
                     'avg_price' => $erp_purchase_price,
                     'latest_purchase_price' => $purchase_price,
                     'create_time' => date("Y-m-d H:i:s", time()),
                     'update_time' => date("Y-m-d H:i:s", time())
                 );
                 $result = $this->db->insert("purchase_avg_fuse",$fuse_avg_data);
                 if( $result)
                 {
                     return $fuse_avg_data;
                 }
                 throw new Exception("插入avg_fuse 表示失败",505);
             }
             //  avg_fuse表有记录：  不做任何操作
             return False;

         }catch ( Exception $exp )
         {
             throw new Exception($exp->getMessage(),505);
         }
    }

    /**
     * 数据中心推送SKU 到新采购系统，处理平均采购成本问题
     * avg_fuse 表没有记录： ERP 开发成本插入 avg_fuse 表，回传 开发成本信息到erp
       表有记录并且 avg_fuse 表 avg_purchase_price，avg_price 都为0：  更新 avg_fuse表，并且回传ERP
       表有记录并且 avg_fuse 表 avg_purchase_price，avg_price 都不为0：回传ERP
     * @param  $data 数据中心推送的SKU 信息
     * @author:luxu
     **/
    private function sku_fuse_avg_data( $skus,$purchase_price )
    {
        try {
            // 判断平均采购成本SKU 是否存在
            $fuse_avg = $this->db->from("purchase_avg_fuse")->where("sku", $skus['sku'])->select("id,sku,avg_freight,avg_purchase_price,avg_price,latest_purchase_price")->get()->row_array();
            // 如果不存在的情况下就插入
            $fuse_avg_data = array(
                'sku' => $skus['sku'],
                'avg_freight' => 0,
                'avg_purchase_price' => $skus['product_cost'],
                'avg_price' => $skus['product_cost'],
                'latest_purchase_price' => $purchase_price,
                'create_time' => date("Y-m-d H:i:s", time()),
                'update_time' => date("Y-m-d H:i:s", time())
            );
            if (empty($fuse_avg)) {
                $result = $this->db->insert('purchase_avg_fuse', $fuse_avg_data);
                if ($result) {
                    // 如果插入成功
                    return array(
                        'sku' => $skus['sku'],
                        'avg_freight' => 0,
                        'avg_purchase_price' => $skus['product_cost'],
                        'avg_price' => $skus['product_cost'],
                        'latest_purchase_price' => $purchase_price
                    );
                }
                throw new Exception($skus['sku']."更新平均采购价表失败",505);
            } else {

                // 如果avg_fuse 表存在记录，判断avg_purchase_price，avg_price 都为0
                if ( ($fuse_avg['avg_purchase_price'] == 0.000 && $fuse_avg['avg_price'] == 0.000 ) || (empty($fuse_avg['avg_purchase_price']) && empty($fuse_avg['avg_price'])) ) {
                    //  表有记录并且 avg_fuse 表 avg_purchase_price，avg_price 都为0：  更新 avg_fuse表，并且回传ERP
                    $result = $this->db->where("sku", $skus['sku'])->update('purchase_avg_fuse', $fuse_avg_data);
                    if ($result) {
                        return array(
                            'sku' => $skus['sku'],
                            'avg_freight' => 0,
                            'avg_purchase_price' => $skus['product_cost'],
                            'avg_price' => $skus['product_cost'],
                            'latest_purchase_price' =>  $purchase_price
                        );
                    }
                    throw new Exception($skus['sku']."更新平均采购价表失败",505);
                } else {
                    //表有记录并且 avg_fuse 表 avg_purchase_price，avg_price 都不为0：回传ERP
                    return $fuse_avg;
                }
            }
        }catch ( Exception $exp)
        {
                throw new Exception( $exp->getMessage());
        }
    }

    /**
     * 获取SKU 平均采购成本数据回调数据中心接口
     * @param  $fuse_avg   array   SKU 平均采购成本数据
     *         $url        string  数据中心JAVA 接口地址
     * @author: luxu
     **/

    private function send_fuseavg_java($fuse_avg,$url)
    {
        try{

            // $to_erp_purchase_price_url = $erp_system['purchase_price_to_erp'] . "?access_token=" . getOASystemAccessToken();
            $java_url = $url."?access_token=".getOASystemAccessToken();
            $shipCost = '0.00';
            $send_data = array(

                'sku' => $fuse_avg['sku'],
                'lastPrice' => isset($fuse_avg['avg_purchase_price']) ? (float)$fuse_avg['avg_purchase_price'] : '',
                'avgPrice' => isset($fuse_avg['avg_purchase_price']) ? (float)$fuse_avg['avg_purchase_price'] : '',
                'shipCost' => $shipCost,
                'newPrice' => isset($fuse_avg['latest_purchase_price']) ? (float)$fuse_avg['latest_purchase_price'] : '',
            );

            $json_encode_string = json_encode($send_data, JSON_UNESCAPED_UNICODE);
            $json_encode_string = str_replace('"0.00"', '0.00', $json_encode_string);
            $erp_result_data = send_http($java_url, $json_encode_string);

            $logs_mess = array(
                'sku' => $fuse_avg['sku'],
                'to_erp_message' => json_encode($send_data),
                'return_erp_message' => $erp_result_data
            );
            $this->db->insert('to_erp_message', $logs_mess);

            $erp_result_data = json_decode( $erp_result_data,True);
            if( $erp_result_data['code'] == 200 )
            {
                $this->db->where("sku",$fuse_avg['sku'])->update('purchase_avg_fuse',['back_to_erp'=>2]);
            }else{
                $this->db->where("sku",$fuse_avg['sku'])->update('purchase_avg_fuse',['back_to_erp'=>3]);
            }
            return $erp_result_data;
        }catch ( Exception $exp )
        {
            echo $exp->getMessage();die();
        }
    }

    /**
      * 推送SKU 平均采购价到数据中心失败，补偿脚本
     **/
    public function send_java_avgfuse()
    {
        $fuse_avg = $this->db->from("purchase_avg_fuse")->where("back_to_erp",3)->select("id,sku,avg_freight,avg_purchase_price,avg_price,latest_purchase_price")->get()->result_array();
        if( !empty($fuse_avg) )
        {
            $this->load->config('api_config', FALSE, TRUE);
            $erp_system = $this->config->item('erp_system');
            foreach( $fuse_avg as $key=>$value)
            {
                $send_data = array(

                    'sku' => $value['sku'],
                    'lastPrice' => isset($value['avg_purchase_price']) ? (float)$value['avg_purchase_price'] : '',
                    'avgPrice' => isset($value['avg_price']) ? (float)$value['avg_price'] : '',
                    'newPrice' => isset($value['latest_purchase_price']) ? (float)$value['latest_purchase_price'] : '',
                );

                $this->send_fuseavg_java($send_data,$erp_system['purchase_price_to_erp']);

            }
        }
    }

    /**
     * 接收数据中心推送过来的产品信息
     /product_service_pust_api/get_create_product
     * @author Jaden
     */
     public function get_create_product(){
         die('该接口已经废弃 废弃时间  2019-12-31 23:59:59 资料由JAVA服务推送 Jolon');
        $product_info = $_REQUEST['ProductToPurchaseInfo']??[];
        $product_info_list = json_decode($product_info,true);
        if(!empty($product_info_list)){
            $push_url = getConfigItemByName('api_config','old_purchase','supplier-get-default');
            try {
                $sku_arr = array();
                $this->load->config('api_config', FALSE, TRUE);
                $erp_system = $this->config->item('erp_system');
                foreach ($product_info_list as $key => $value) {

                    $change_data = array();
                    $product_data = array();

                    if(empty($value['sku'])){
                        continue;
                    }
                    //获取默认供应商
                    $supplier_name = isset($value['provider_company']) ? $value['provider_company'] : '';         
                    if(!empty($supplier_name)){
                        //根据供应商名称查找supplier_code
                        $supplier_info = $this->supplier_model->get_supplier_by_name($supplier_name,false);
                        $supplier_code = !empty($supplier_info)?$supplier_info['supplier_code']:'';
                    }else{
                        $supplier_code = '';
                    }
                    //根据用户名称查询用户信息
                    if(isset($value['create_user_id']) && !empty($value['create_user_id'])){
                        $user_info = $this->purchase_user_model->get_user_info_by_user_name($value['create_user_id']);    
                    }
                    if(!empty($user_info) && isset($user_info)){
                        $create_id = $user_info['user_id'];
                    }else{
                        $create_id = '';
                    }
                    //产品税率表信息
                    $this->product_tax_rate_model->saveOne($value);
                    $product_data['sku'] = isset($value['sku']) ? $value['sku'] : '';
                    //$product_data['supplier_name'] = $supplier_name;
                    //$product_data['supplier_code'] = $supplier_code;
                    //$product_data['purchase_price'] = isset($value['new_price'])? $value['new_price'] : '';//供应商报价
                    $product_data['product_category_id'] = isset($value['product_category_id']) ? $value['product_category_id'] : '';
                    $product_data['product_cost'] = isset($value['product_cost'])? $value['product_cost'] : '';
                    $product_data['last_price'] = isset($value['last_price'])? $value['last_price'] : '';
                    $product_data['product_status'] = isset($value['product_status'])? $value['product_status'] : '';
                    $product_data['product_type'] = isset($value['product_type'])? $value['product_type'] : '';
                    $product_data['product_package_code'] = isset($value['product_package_code'])? $value['product_package_code'] : '';
                    $product_data['is_multi'] = isset($value['product_is_multi'])? $value['product_is_multi'] : '';
                    $product_data['create_id'] = $create_id;//开发人员ID
                    $product_data['create_user_name'] = isset($value['create_user_id'])? $value['create_user_id'] : '';//开发人员姓名
                    //$product_data['product_cn_link'] = isset($value['product_cn_link'])? $value['product_cn_link'] : '';
                    //$product_data['product_en_link'] = isset($value['product_en_link'])? $value['product_en_link'] : '';
                    //$product_data['product_img_url'] = erp_sku_img_sku($value['sku']);
                    //$product_data['ticketed_point'] = isset($value['ticketed_point'])? $value['ticketed_point'] : '';//税点
                    $product_data['export_cname'] = isset($value['export_cname'])? $value['export_cname'] : '';
                    $product_data['export_model'] = isset($value['product_model']) ? $value['product_modelt'] :'';
                    $product_data['product_line_id'] = isset($value['product_linelist_id'])? $value['product_linelist_id'] : '';
                    $product_data['is_boutique'] = isset($value['is_boutique'])? $value['is_boutique'] : '';
                    $product_data['is_new'] = isset($value['product_is_new'])? $value['product_is_new'] : 0;
                    $product_data['avg_purchase_cost'] = isset($value['ship_cost'])? $value['ship_cost'] : 0;
                    $product_data['purchase_packaging'] = isset($value['product_to_way_package'])? $value['product_to_way_package'] : '';
                    (isset($value['create_time']) && $value['create_time']) ? $product_data['create_time'] =  $value['create_time'] : null;//对接时新增创建时间
                    //取标题(中文)
                    $description_list = isset($value['description'])? $value['description'] : '';
                    if(!empty($description_list) && is_array($description_list)){
                        foreach ($description_list as $des_key => $des_value) {
                            if($des_value['language_code']=='Chinese'){
                               $product_name =  isset($des_value['title'])?$des_value['title']:'';
                            }
                        }
                    }else{
                       $product_name =  ''; 
                    }
                    //属性
                    $attribute_arr = array();
                    $attribute_list = isset($value['attribute'])? $value['attribute'] : '';
                    if(!empty($attribute_list) && is_array($attribute_list)){
                        foreach ($attribute_list as $att_key => $att_value) {
                            if($att_value['attribute_name']=='color'){
                                $attribute_arr['color'][] = $att_value['attribute_value_name'];
                            }
                            if($att_value['attribute_name']=='size'){
                                $attribute_arr['size'][] = $att_value['attribute_value_name'];
                            }
                            if($att_value['attribute_name']=='style'){
                                $attribute_arr['style'][] = $att_value['attribute_value_name'];
                            }

                        }

                    }
                    //销售属性
                    $attribute_arr = !empty($attribute_arr)?$attribute_arr:'';
                    $sale_attribute = json_encode($attribute_arr);
                    $product_data['sale_attribute'] = $sale_attribute;
                    $product_data['product_name'] = $product_name;//产品名称
                    //检测SKU是否存在，存在更新，不存在，插入数据
                    $product_info = $this->product_model->get_product_info($value['sku']);
                    if(!isset($product_info['purchase_price']) or empty($product_info['purchase_price']) or $product_info['purchase_price']==0){
                        $product_data['purchase_price'] = isset($value['product_cost'])? $value['product_cost'] : '';//供应商报价
                    }
                    if(!isset($product_info['product_cn_link']) or empty($product_info['product_cn_link'])){
                        $product_data['product_cn_link'] = isset($value['product_cn_link'])?$value['product_cn_link']:'';//SKU采购链接-中文
                    }
                    if(!isset($product_info['product_en_link']) or empty($product_info['product_en_link'])){
                        $product_data['product_en_link'] = isset($value['product_en_link'])?$value['product_en_link']:'';//SKU采购链接-英文
                    }


                    if(!empty($product_info)){   //更新数据
                        $product_info['product_cost'] = 0.00;
                        //推送记录
                        $pust_data['remarks'] = $value['sku'].'-数据更新';
                        // 判断SKU的未税单价是否为0，如果为0就回调数据中心接口
                        try {
                            if ($product_info['purchase_price'] == 0.000 || empty($product_info['purchase_price'])) {
                                // 采购系统purchase_price 字段为0 的情况
                                $fuse_avg_data = $this->sku_fuse_avg_data($value,$product_info['purchase_price']);
                            } else {
                                // 采购系统purchase_price 字段不为0 的情况
                                $fuse_avg_data = $this->set_fuse_avg_data($value['sku'],$product_info['purchase_price'],$value['product_cost']);
                            }

                            // 调用数据中心JAVA 接口回传给ERP
                            if( $fuse_avg_data ) {
                                $java_return_message = $this->send_fuseavg_java($fuse_avg_data, $erp_system['purchase_price_to_erp']);
                            }
                        }catch ( Exception $exp )
                        {
                            // 记录错误日志
                            $logs_mess = array(
                                'sku' => $value['sku'],
                                'to_erp_message' => json_encode($fuse_avg_data),
                                'return_erp_message' => $exp->getMessage()
                            );
                            $this->db->insert('to_erp_message', $logs_mess);
                        }
                    }else{ //插入数据
                        // 如果是新数据更新供应商名，供应商code,供应商报价这几个值
                        $product_data['supplier_name'] = $supplier_name;
                        $product_data['supplier_code'] = $supplier_code;
                        $product_data['purchase_price'] = isset($value['product_cost'])? $value['product_cost'] : '';//供应商报价
                        $product_data['ticketed_point'] = isset($value['ticketed_point'])? $value['ticketed_point'] : '';//税点

                        $pust_data['remarks'] = $value['sku'].'-数据插入';
                        $warehouse_sku = $this->db->from("warehouse_results_main")->where("sku",$value['sku'])->select("id")->get()->row_array();
                        $send_java_data_flag = False;
                        if( empty($warehouse_sku)) {
                            $to_erp_purchase_price_url = $erp_system['purchase_price_to_erp'] . "?access_token=" . getOASystemAccessToken();
                            $shipCost = '0.00';
                            $send_data = array(

                                'sku' => $value['sku'],
                                'lastPrice' => isset($value['product_cost']) ? (float)$value['product_cost'] : '',
                                'avgPrice' => isset($value['product_cost']) ? (float)$value['product_cost'] : '',
                                'shipCost' => $shipCost,
                                'newPrice' => isset($value['product_cost']) ? (float)$value['product_cost'] : '',
                            );

                            $json_encode_string = json_encode($send_data, JSON_UNESCAPED_UNICODE);
                            $json_encode_string = str_replace('"0.00"', '0.00', $json_encode_string);
                            $erp_result_data = send_http($to_erp_purchase_price_url, $json_encode_string);
                            $logs_mess = array(
                                'sku' => $value['sku'],
                                'to_erp_message' => json_encode($send_data),
                                'return_erp_message' => $erp_result_data
                            );
                            $this->db->insert('to_erp_message', $logs_mess);
                            $send_java_data_flag = True;
                        }

                        try {
                            $fuse_avg_data = $this->set_fuse_avg_data($value['sku'], $value['product_cost'],$value['product_cost']);
                            if( $fuse_avg_data && False == $send_java_data_flag ) {

                                $java_return_message = $this->send_fuseavg_java($fuse_avg_data, $erp_system['purchase_price_to_erp']);
                            }
                        }catch ( Exception $exp )
                        {
                            // 记录错误日志
                            $logs_mess = array(
                                'sku' => $value['sku'],
                                'to_erp_message' => json_encode($fuse_avg_data),
                                'return_erp_message' => $exp->getMessage()
                            );
                            $this->db->insert('to_erp_message', $logs_mess);
                        }
                    }


                    //推送记录
                    $pust_data['sku'] = $value['sku'];
                    $pust_data['pust_conent'] = json_encode($product_data);
                    $pust_data['add_time'] = date('Y-m-d H:i:s');
                    $result = $this->db->insert('product_service_data_pust_log', $pust_data);
                    if($result){
                        $data['success_list'][] = $value['sku'];
                    }else{
                        $data['failure_list'][] = $value['sku'];
                    }
                }
                echo json_encode($data);exit;
            } catch (Exception $e) {
                var_dump($e->getMessage());
            }  
        }
        
     }

     /**
     * SKU捆绑信息
     /product_service_pust_api/get_create_binding
     * @author Jaden
     */
     public function get_create_binding(){
        $product_combine_info = $_REQUEST['ProductCombine'];
        $product_combine_info_list = json_decode($product_combine_info,true);
        if(!empty($product_combine_info_list)){
            $data = $this->product_combine_model->FindOnes($product_combine_info_list);
        }else{
             return '没有任何的数据过来！';
        }
        echo json_encode($data);exit;
     }


     /**
     * 返回 是否可退税 退税率-税点≥1% ? '可退税' : '不可'
     * @param float $tax_rate 出口退税率
     * @param float $pur_ticketed_point 税点
     * @return number 
     */
    public function getProductIsBackTax($tax_rate, $pur_ticketed_point) {
        if (empty($tax_rate) || $pur_ticketed_point==0 || $tax_rate==0 || empty($pur_ticketed_point)) {
            return 0;
        }
        return $tax_rate - $pur_ticketed_point >= 1 ? 1 : 0;
    }


    function curl_post($url, $data){ 
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, $url); 
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($ch, CURLOPT_POST, 1); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $output = curl_exec($ch); 
        curl_close($ch); 
        return $output; 
    }


    //临时解决数据中心推送过来的数据，退税问题
    //product_service_pust_api/chang_is_drawback
    function chang_is_drawback(){
        $success_list = array();
        //退税
        $where = "(tax_rate-ticketed_point)>=1 AND ticketed_point !=0 AND is_drawback=0";
        $product_list = $this->db->select(
                        'sku,
                        tax_rate,
                        ticketed_point,
                        is_drawback'
                    )
                    ->from('pur_product')
                    ->limit(300)
                    ->where($where)
                    ->get()->result_array();
        if(!empty($product_list)){
            foreach ($product_list as $key => $value) {
                $success_list[] = $value['sku'];
                $this->db->where('sku', $value['sku'])->update('pur_product',['is_drawback'=>1]);
            }    
        }

        //不退税
        $no_is_drawback_where = "(ticketed_point=0 or tax_rate=0 or (tax_rate-ticketed_point)<1) AND is_drawback=1";
        $no_is_drawback_product_list = $this->db->select(
                        'sku,
                        tax_rate,
                        ticketed_point,
                        is_drawback'
                    )
                    ->from('pur_product')
                    ->limit(300)
                    ->where($no_is_drawback_where)
                    ->order_by('id','DESC')
                    ->get()->result_array();
        //echo '<pre>';
        //print_r($no_is_drawback_product_list);exit;   
        if(!empty($no_is_drawback_product_list)){
            foreach ($no_is_drawback_product_list as $k => $val) {
                $success_list[] = $val['sku'];
                $this->db->where('sku', $val['sku'])->update('pur_product',['is_drawback'=>0]);
            }    
        }
        if(!empty($success_list)){
            echo '<pre>';
            echo '本次更新成功'.count($success_list).'个SKU'.'<br>';
            //print_r($success_list);exit;
        }else{
            var_dump('没有数据更新');
        }           
                    
    }

    /**
     * 获取ERP产品线数据详情
     * @author wangliang 2019-06-11
     */
    public function update_product_line_data(){
        try{
            $erp_system =  $this->config->item('erp_system');
            $url = isset($erp_system['product_scree']['get_product_line']) ? $erp_system['product_scree']['get_product_line'] : '';
            if(!$url) throw new Exception('接口url不存在');

            $result = $this->curl_post($url,[]);
            $data = json_decode($result,true);
            if(!is_array($data)) throw new Exception('数据不存在'.$result);

            $this->load->model('product/Product_line_model');
            $return = $this->Product_line_model->save_product_line($data);
            if($return['status'] !== 1) throw new Exception($return['msg']);

        }catch (Exception $e){
            exit($e->getMessage());
        }
        exit('数据同步成功');
    }

}