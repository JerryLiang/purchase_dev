<?php
/**
 * 37539 异常处理模块，新增页面：供应商质量改善列表 #4
 * User: luxu
 * Date: 2021/07/27 10:00
 */


class Abnormal_quality_model extends Purchase_model{

    public function __construct(){
        parent::__construct();
        $this->load->helper('abnormal');

    }

    /**
     * 到货ＳＫＵ数量：按页面统计时间，去采购单页面搜搜该供应商在统计时间内入库的备货单数量；例如：去采购单页面，
     *  搜索：入库时间＝5/1-5/31，供应商名称＝深圳市赢润科技有限公司，搜索出的备货单数量＝3360，则到货ＳＫＵ数量＝3360；
     * @author:luxu
     * @time:2021年7月27号
     **/

    private function get_supplier_warehouse($start,$end,$supplier_codes){

        if(empty($supplier_codes)){

            return [];
        }

        $result_data = [];

        foreach($supplier_codes as $supplier_code_key=>$supplier_code_value){
            $datas = $this->purchase_db->from("purchase_order as orders")
                ->join("pur_purchase_order_items as items","orders.purchase_number=items.purchase_number","left")
                ->join("pur_warehouse_results_main as main","main.items_id=items.id","LEFT")
                ->where("orders.supplier_code",$supplier_code_key)->where("main.instock_date>=",$start)
                ->where("main.instock_date<=",$end)->count_all_results();


            $result_data[$supplier_code_key] = $datas;

        }

        return $result_data;
    }

    /**
     * 获取供应商主产品线ID
     * @params $supplier_codes   array  供应商CODE
     * @time 2021年7月28号
     **/
    private function get_first_product_line($supplier_codes){

        $first_product_lines = $this->purchase_db->from("supplier_analysis_product_line")->where_in("supplier_code",$supplier_codes)
            ->select("first_product_line,supplier_code")->get()->result_array();

        if(!empty($first_product_lines)){

            $first_product_data = array_column($first_product_lines,'first_product_line');
            $product_info = $this->purchase_db->where_in('product_line_id', $first_product_data)->get("product_line")->result_array();
            $return_data = [];
            if(!empty($product_info)) {
                $product_info = array_column($product_info,NULL,"product_line_id");
                foreach ($first_product_lines as $key => $value) {
                    $return_data[$value['supplier_code']] = [
                        "name" => isset($product_info[$value['first_product_line']])?$product_info[$value['first_product_line']]['linelist_cn_name']:'',
                        'line_id' => $value['first_product_line']
                        ];
                }
            }
            return $return_data;
        }

        return [];
    }

    /**
     * 获取采购员所属组别
     * @params  $buyer_ids   array   采购员ID
     * @time:   2021年7月28号
     **/
    private function user_relation_group($buyer_ids = array()){

      $result = $this->purchase_db->from("purchase_user_relation as relation")
          ->join("purchase_user_group_relation as group_relation","relation.id=group_relation.user_map_id","LEFT")
          ->where_in("relation.user_id",$buyer_ids)->select("relation.user_id,group_id")->get()->result_array();
      $buyers = [];
      if(!empty($result)){
          foreach($result as $key=>$value){
              if( isset($buyers[$value['buyer_id']])){
                  $buyers[$value['user_id']] = [];
              }

              $buyers[$value['user_id']][] = $value['group_id'];
          }
      }

      if(!empty($buyers)){
          $result = [];
          foreach($buyers as $buyer_key=>$buyer_value){

              $groupName = $this->purchase_db->from("purchase_group")->where_in("id",$buyer_value)->where("is_del",0)
                  ->select("group_name")->limit(3)->order_by("id DESC")->get()->result_array();

              if(!empty($groupName)){

                  $groupName = array_column($groupName,"group_name");
                  $result[$buyer_key] = implode(",",$groupName);
              }
          }
          return $result;
      }

      return [];

    }


    public function get_Abnormal_list_data($clientDatas){

        $query = $this->purchase_db->from("supplier_quality");
        // 供应商查询
        if( isset($clientDatas['supplier_code']) && !empty($clientDatas['supplier_code'])){

            if(is_array($clientDatas['supplier_code'])){

                $query->where_in("supplier_code",$clientDatas['supplier_code']);
            }else{
                $query->where("supplier_code",$clientDatas['supplier_code']);
            }
        }

        // 等级仓库

        if( isset($clientDatas['warehouse_code']) && !empty($clientDatas['warehouse_code'])){

            if( is_array($clientDatas['warehouse_code'])){

                $query->where_in("warehouse_code",$clientDatas['warehouse_code']);
            }else{
                $query->where("warehouse_code",$clientDatas['warehouse_code']);
            }
        }

        // 采购员
        if( isset($clientDatas['buyer_id']) && !empty($clientDatas['buyer_id'])){

            if( is_array($clientDatas['buyer_id'])){
                $query->where_in("buyer_id",$clientDatas['buyer_id']);
            }else{
                $query->where("buyer_id",$clientDatas['buyer_id']);
            }
        }

        // 主产品线
        if( isset($clientDatas['product_line_id']) && !empty($clientDatas['product_line_id'])){

            $query->where_in("product_line_id",$clientDatas['product_line_id']);
        }

        // 统计时间

        if( isset($clientDatas['create_time_start']) && !empty($clientDatas['create_time_start'])){

            $query->where("create_time>=",$clientDatas['create_time_start']);
        }

        if( isset($clientDatas['create_time_end']) && !empty($clientDatas['create_time_end'])){

            $query->where("create_time<=",$clientDatas['create_time_end']);
        }

        // 更新时间

        if( isset($clientDatas['update_time_start']) && !empty($clientDatas['update_time_start'])){

            $query->where("update_time>=",$clientDatas['update_time_start']);
        }

        if( isset($clientDatas['update_time_end']) && !empty($clientDatas['update_time_end'])){

            $query->where("update_time<=",$clientDatas['update_time_end']);
        }

        // 改善结果

        if( isset($clientDatas['improve_id']) && !empty($clientDatas['improve_id'])){

            $query->where_in("improve",$clientDatas['improve_id']);
        }

        // 异常类型

        if( isset($clientDatas['exception_id']) && !empty($clientDatas['exception_id'])){

            $query->where_in("problem_id",$clientDatas['exception_id']);
        }

        // 采购组别

        if( isset($clientDatas['groupdatas']) && !empty($clientDatas['groupdatas'])){

            $query->where_in("buyer_id",$clientDatas['groupdatas']);
        }

        // 次品类型

        if( isset($clientDatas['defective_id']) && !empty($clientDatas['defective_id'])){

            $query->where_in("abnormal_id",$clientDatas['defective_id']);
        }

        // 更新人

        if( isset($clientDatas['update_buyer_id']) && !empty($clientDatas['update_buyer_id'])){

            $query->where_in("update_buyer_id",$clientDatas['update_buyer_id']);
        }

        // 创建人

        if( isset($clientDatas['create_buyer_id']) && !empty($clientDatas['create_buyer_id'])){

            $query->where_in("create_buyer_id",$clientDatas['create_buyer_id']);
        }

        $countQuery = clone $query;
        $result = $query->order_by("id DESC")->limit($clientDatas['limit'],$clientDatas['offset'])->get()->result_array();

        if(!empty($result)){

            // 获取供应商对应的SKU
            $supplierCodeSkus = array_column($result,"supplier_code");
            $supplier_first = $this->get_first_product_line($supplierCodeSkus);
            $supplierDatas = $this->purchase_db->from("product")->where_in("supplier_code",$supplierCodeSkus)->select("supplier_code,sku")
                ->get()->result_array();

            $suppliers = [];
            foreach($supplierDatas as $supplierData_key=>$supplierData_value){

                if(!isset($suppliers[$supplierData_value['supplier_code']])){

                    $suppliers[$supplierData_value['supplier_code']] = [];
                }
                $suppliers[$supplierData_value['supplier_code']][] = $supplierData_value['sku'];
            }

            $clientDatas['statistics_start'] = isset($clientDatas['statistics_start'])?$clientDatas['statistics_start']:date("Y-m-d",strtotime("-30 days"));
            $clientDatas['statistics_end'] = isset($clientDatas['statistics_end'])?$clientDatas['statistics_end']:date("Y-m-d",time());
            $warehouse_data = $this->get_supplier_warehouse($clientDatas['statistics_start'],$clientDatas['statistics_end'],$suppliers);
            $buyer_ids = array_column($result,'buyer_id');
            $groupNames = $this->user_relation_group($buyer_ids);

            foreach($result as $key=>&$value){

//                $where = [];
//                if( $value['problem_type'] == "Exception"){
//
//                    //如果问题类型为异常类型
//                    $value['problem_name'] =  getWarehouseAbnormalType($value['abnormal_id']);
//                    $where['abnormal_type'] = $value['abnormal_id'];
//                }else{
//                    // 如果问题类型为  次品类型
//                    $value['problem_name'] =  getAbnormalDefectiveType($value['problem_id']);
//                    $where['defective_type'] = $value['problem_id'];
//                }

                $value['problem_name'] = $value['defective_name']." ".$value['problem_name'];

                // 异常SKU数量
                $supplier_skus = isset($suppliers[$value['supplier_code']])?$suppliers[$value['supplier_code']]:[];
                if(empty($supplier_skus)){

                    $value['exception_number'] = 0;
                    $value['proportion'] = "0%";
                }else{
                    /*$exception_number = $this->purchase_db->from("purchase_warehouse_abnormal")->where_in("sku",$supplier_skus)
                        ->where("create_time>=",$clientDatas['statistics_start'])
                        ->where("create_time<=",$clientDatas['statistics_end'])
                        ->where("defective_type",$value['abnormal_id'])->count_all_results();
                    */

                    $exception_where = [];

                    if(!empty($value['abnormal_id'])){

                        $exception_where['defective_type'] = $value['abnormal_id'];
                    }

                    if(!empty($value['problem_id']) && empty($value['abnormal_id'])){

                        $exception_where['abnormal_type'] = $value['problem_id'];
                    }
                    $exception_number_query = $this->purchase_db->from("purchase_warehouse_abnormal")->where($exception_where)
                        ->where("create_time>=",$clientDatas['statistics_start'])
                        ->where("create_time<=",$clientDatas['statistics_end']);

                    if(count($supplier_skus)>2000){

                        $supplier_skus_data = array_chunk($supplier_skus,1000);
                        $exception_number_query->group_start();

                        foreach($supplier_skus_data as $supplier_skus_datum_key=>$supplier_skus_datum_value){

                            $exception_number_query->where_in("sku",$supplier_skus_datum_value);
                        }
                        $exception_number_query->group_end();

                    }else{
                        $exception_number_query->where_in("sku",$supplier_skus);
                    }


                    $exception_number = $exception_number_query->count_all_results();
                    $value['exception_number'] = $exception_number;
                    $warehouse = isset($warehouse_data[$value['supplier_code']])?$warehouse_data[$value['supplier_code']]:0;
                    $value['instock_qty'] = $warehouse;
                    if($warehouse>0) {
                        $value['proportion'] = ((round(($value['exception_number'] / $warehouse), 2)) *100). "%";
                    }else{
                        $value['proportion'] = 0;
                    }

                }
                if(!empty($value['improve'])) {
                    $value['improve_ch'] = $this->get_improve($value['improve']);
                }else{
                    $value['improve_ch'] = "";
                }

                $value['chat_image'] = !empty($value['chat_image'])?json_decode($value['chat_image'],True):[];
                $value['group_name'] = isset($groupNames[$value['buyer_id']])?$groupNames[$value['buyer_id']]:'';
                $value['first_product_line'] = isset($supplier_first[$value['supplier_code']]['name'])?$supplier_first[$value['supplier_code']]['name']:'';
            }
        }

        return [
            'list'=>$result,
            'page_data' => [
                'total' => $countQuery->count_all_results(),
                'limit' => $clientDatas['limit'],
                'offset' => $clientDatas['offset']+1
            ]
        ];

    }

    public function get_improve($type = NULL){

        $result = $this->purchase_db->from("param_sets")->where("pKey","IMPROVED_RESULTS")->select("pValue")->get()->row_array();

        if(NULL != $type){

            $result = json_decode($result['pValue'],True);
            return isset($result[$type])?$result[$type]:'';
        }
        return $result;
    }

    /**
     * 添加供应商质量改善
     * 小包仓_塘厦和小包仓_虎门的取国内仓
      中转仓_慈溪和中转仓_虎门的取海外仓
     * @author:luxu
     * @time:2021年7月29
     **/

    public function add_Abnoral_list_data($clientDatas){

        try{

            if(empty($clientDatas) && !is_array($clientDatas)){

                throw new Exception("请传入数据");
            }

            foreach($clientDatas as $key=>&$value){

                $value['chat_image'] = !empty($value['chat_image'])?implode(",",$value['chat_image']):'';
                $value['create_buyer_id'] = getActiveUserId(); // 创建人ID
                $value['create_buyer_name'] = getActiveUserName(); // 创建人名称
                $value['create_time'] = date("Y-m-d H:i:s",time()); // 创建时间
                $value['defective_name'] = $value['exceptioin_name'];
                $value['abnormal_id'] = $value['exception_id'];
                unset($value['exceptioin_name']);
                unset($value['exception_id']);
                // 获取采购员信息
                $purchase_type_id = 0;
                if( in_array($value['warehouse_name'],['小包仓_塘厦','小包仓_虎门'])){

                    $purchase_type_id = 1;
                }else{
                    $purchase_type_id = 2;
                }

                $sku_info = $this->purchase_db->select('
               
                c.buyer_id,
                c.buyer_name
                ')
                    ->from('pur_supplier b')
                    ->join('pur_supplier_buyer c', 'b.supplier_code=c.supplier_code and c.status=1 and c.buyer_type='.$purchase_type_id, 'left')
                    ->where(['b.supplier_code' => $value['supplier_code']])
                    ->get()
                    ->row_array();

                if(!empty($sku_info)){
                    $value['buyer_id'] = !empty($sku_info['buyer_id'])?$sku_info['buyer_id']:0;
                    $value['buyer_name'] = !empty($sku_info['buyer_name'])?$sku_info['buyer_name']:'';
                }else{
                    $value['buyer_id'] = 0;
                    $value['buyer_name'] = '';
                }

                $lineDatas = $this->get_first_product_line($value['supplier_code']);
                $value['product_line_id'] = isset($lineDatas[$value['supplier_code']]['line_id'])?$lineDatas[$value['supplier_code']]['line_id']:0;
                //supplier_quality
                if(!empty($value['problem_id'])){

                    $problemDatas = $this->purchase_db->from("supplier_quality")->where("supplier_code",$value['supplier_code'])
                        ->where("problem_id",$value['problem_id'])->select("id")->get()->row_array();
                    if(!empty($problemDatas)){
                        throw new Exception("供应商:".$value['supplier_name'].",对应的异常类型已经存在");
                    }
                }

                if(!empty($value['abnormal_id'])){

                    $problemDatas = $this->purchase_db->from("supplier_quality")->where("supplier_code",$value['supplier_code'])
                        ->where("abnormal_id",$value['abnormal_id'])->select("id")->get()->row_array();
                    if(!empty($problemDatas)){
                        throw new Exception("供应商:".$value['supplier_name'].",对应的次品类型已经存在");
                    }
                }
            }

            foreach($clientDatas as $clientDatas_key=>$clientDatas_value){

                $result = $this->purchase_db->insert("supplier_quality",$clientDatas_value);
                $gid=$this->db->insert_id('supplier_quality');




                $logs = [

                    'user_id' => getActiveUserId(),
                    'user_name' => getActiveUserName(),
                    'type' =>0,
                    'old_improve' => 0,
                    'new_improve' => 0,
                    'create_time' => date("Y-m-d H:i:s",time()),
                    'quality_id' =>$gid
                ];

                $this->purchase_db->insert("supplier_quality_log",$logs);
            }

            return True;

            //throw new Exception("添加失败");
        }catch ( Exception $exp ){

            throw new Exception($exp->getMessage());
        }
    }

    public function handler_Abnoral_list_data($clientDatas){

        $start_month = date("Y-m-01",time());
        $end_month = date("Y-m-d H:i:s",time());
        foreach($clientDatas as $key=>$value){
            $logs = $this->purchase_db->from("supplier_quality_log")->where("create_time>=",$start_month)->where("create_time<=",$end_month)
                ->where("quality_id",$value['quality_id'])->where("type",1)->get()->result_array();
            if(count($logs)>=2){

                throw new Exception("改善结果每个月最多修改２次，请下个月再修改！");
            }

            $update = [
                'improve' => $value['improve'],
                'supplier_reply' => $value['supplier_reply'],
                'chat_image' => json_encode($value['chat_image']),
                'update_time' => date("Y-m-d H:i:s",time()),
                'update_buyer_id' => getActiveUserId(),
                'update_buyer_name' => getActiveUserName()
            ];

            $old_improve = $this->purchase_db->from("supplier_quality")->where("id",$value["quality_id"])->select("improve")->get()->row_array();


            $updateDatas = $this->purchase_db->where("id",$value['quality_id'])->update('supplier_quality',$update);
            $logs = [

                'user_id' => getActiveUserId(),
                'user_name' => getActiveUserName(),
                'type' =>1,
                'old_improve' => $old_improve['improve'],
                'new_improve' => $value['improve'],
                'create_time' => date("Y-m-d H:i:s",time()),
                'quality_id' =>$value['quality_id']
            ];

            $this->purchase_db->insert("supplier_quality_log",$logs);
        }
    }

    public function Abnoral_log($id){

        $result = $this->purchase_db->from("supplier_quality_log")->where("quality_id",$id)->get()->result_array();
        if(!empty($result)){
            $improveDatas = $this->get_improve();
            $improveDatas = json_decode($improveDatas['pValue'],True);
            foreach($result as $key=>&$value){
                $value['old_improve_ch'] = isset($improveDatas[$value['old_improve']])?$improveDatas[$value['old_improve']]:'';
                $value['new_improve_ch'] = isset($improveDatas[$value['new_improve']])?$improveDatas[$value['new_improve']]:'';
                if($value['type'] == 0){
                    $value['type_ch'] = "新增";
                }else{
                    $value['type_ch'] = "批量修改";
                }

            }
            return $result;
        }
        return [];
    }

    public function get_expection(){

        $sql = 'SELECT * FROM pur_param_sets WHERE pKey="PUR_PURCHASE_ABNORMAL_ABNORMAL_HANDLE_TYPE"';
        $result = $this->purchase_db->query($sql)->row_array();
        //echo $result['pValue'];die();
        $data = json_decode($result['pValue']);
        print_r($data);die();
    }
}