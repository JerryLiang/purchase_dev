<?php
/**
 * 异常列表模型类
 * User: Jaxton
 * Date: 2019/01/16 10:06
 */

class Abnormal_list_model extends Purchase_model {
	protected $table_name = 'purchase_warehouse_abnormal';//异常表
//	protected $table_name2019 = 'purchase_warehouse_abnormal_2019';//2019异常备份表
    protected $table_purchase_order_items_name = 'purchase_order_items';//采购单明细表
	private $success=false;
	private $error_msg='';
	private $success_msg='';
	private $handlerData = [
        //异常类型
	    '14'=>[4=>'正常入库',1=>'退货',26=>'转仓',5=>'不做处理',15=>'等待处理',19=>'驳回-图片模糊',20=>'驳回-数量有误',21=>'驳回-描述不清'],
        //2.异常类型=多货(新),来货不符(新)时;处理结果只能选择:正常入库,退货,赠品入库,不做处理,等待处理,驳回-图片模糊,驳回-数量有误,驳回-描述不清
        '16,15'=>[4=>'正常入库',1=>'退货',27=>'赠品入库',5=>'不做处理',15=>'等待处理',19=>'驳回-图片模糊',20=>'驳回-数量有误',21=>'驳回-描述不清'],
        //3.异常类型=质检残次品(新),质检不合格(新),质检残次品-IQC(新),质检不合格-IQC(新)时;处理结果只能选择:正常入库,退货,挑好的入,
        //不做处理,等待处理,驳回-图片模糊,驳回-数量有误,驳回-描述不清,驳回-判定失误
        '18,19,20,21'=>[4=>'正常入库',1=>'退货',6=>'挑好的入',5=>'不做处理',15=>'等待处理',19=>'驳回-图片模糊',20=>'驳回-数量有误',21=>'驳回-描述不清',
        '18'=>'驳回-判断失误'],
        //4.异常类型=分款错误(新)时;处理结果只能选择:内部处理-作废入库单
        '17' => [28=>'内部处理-作废入库单']

    ];
    public function __construct(){
        parent::__construct();
        $this->load->helper(['user','abnormal']);
        $this->load->model('ali/Ali_order_model');
        $this->load->model('user/User_group_model');
        $this->load->model('product/product_model');
        $this->load->model('warehouse/warehouse_model');
        $this->load->model('user/Merchandiser_group_model');




    }

    /**
     * 获取数据的处理类型 需求：30960 异常列表页面,根据异常类型限定处理结果
     * @param $datas  异常列表数据
     *        $handler_type  JSONSTRING  处理结果
     * @author:luxu
     * @time:2021年2月26号

     **/
    public function get_handler_type($datas = array(),$handler_type){

        $handlerKeys = array_keys($this->handlerData);

        foreach($datas as $key=>&$value){
            //1.异常类型=查单异常(新)时;处理结果只能选择:正常入库,退货,转仓,不做处理,等待处理,驳回-图片模糊,驳回-数量有误,驳回-描述不清
            if($value['is_new_wms']==1) {

                $searchKeys = NULL;
                foreach( $handlerKeys as $handlerValue){

                    $handlerValuedata = explode(",",$handlerValue);
                    if(in_array($value['abnormal_type_flag'],$handlerValuedata)){

                        $searchKeys = $handlerValue;
                    }
                }

                if($searchKeys != NULL){

                    $value['handler_datas'] = $this->handlerData[$searchKeys];
                }else{
                    $value['handler_datas'] = $handler_type;
                }
            }else{

                $value['handler_datas'] = $handler_type;
            }
            //供应商/采购部/品控部/开发部/设计部/仓储中心/其他
            $value['department'] = [
                1 => "供应商",
                2 => "采购部",
                3 => "品控部",
                4 => "开发部",
                5 => "设计部",
                6 => "仓储中心",
                7 => "其他"
            ];
        }

        return $datas;
    }

    /**
    * 获取异常数据列表
    * @param $params
    * @param $offset
    * @param $limit
    * @return array   
    * @author Jaxton 2019/01/16
    */
    public function get_abnormal_list($params,$offset,$limit,$page=1){
        $bind_warehouse_set = ['SZ_AA'=>'塘厦组','HM_AA'=>'虎门组','CX'=>'慈溪组'];//仓库配置
        $this->load->model('purchase/purchase_order_model');
        $res_params = $this->get_abnormal_list_search_params($params);
        if( count($res_params) > 0)return $res_params;


    	$clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数


        $this->purchase_db=$clone_db;
        //当传入自定义排序条件时，根据供应商编码排序，否则按照创建时间排序
        if (isset($params['sort_by']) && !empty($params['sort_by']) && in_array(strtoupper($params['sort_by']), ['ASC', 'DESC'])) {
            $this->purchase_db->order_by('s.supplier_code',$params['sort_by']);
        }else{
            $this->purchase_db->order_by('n.create_time','DESC');
        }
        $result=$this->purchase_db->group_by('n.defective_id')->limit($limit,$offset)->get()->result_array();

        $buyerNames = [];
        if(!empty($result)){

            $buyerNames = array_unique(array_column($result,'buyer'));
            $buyerNames = array_map(function($data){
                return sprintf("'%s'",$data);
            },$buyerNames);
        }
        $buyerName = $this->User_group_model->getNameGroupMessage($buyerNames);
        $buyerName = array_column($buyerName,NULL,'user_name');
        $is_purchasing_arr = [1=>'否',2=>'是'];
        $pai_numbers = [];
        if(!empty($result)) {
            $pur_numbers_data = array_column($result,"pur_number");
            if (!empty($pur_numbers_data)) {
                $pai_numbers = $this->purchase_db->from("purchase_order_pay_type")->where_in("purchase_number", $pur_numbers_data)
                    ->select("purchase_number,pai_number")->get()->result_array();
                if(!empty($pai_numbers)) {
                    $pai_numbers = array_column($pai_numbers, NULL, "purchase_number");
                }
            }
        }

        foreach ($result as &$value){

            if($value['is_new_wms'] == 1 && strstr($value['img_path_data'],'http')){
                $wmsImages = json_decode($value['img_path_data'],true);
                $value['img_path_data'] = !empty($wmsImages)?implode(",",$wmsImages):'';
            }else {
                preg_match('/(?:\")(.*)(?:\")/i', $value['img_path_data'], $matches);
                if (isset($matches[0])) {
                    $value['img_path_data'] = '';
                    $strings = explode(',', $matches[0]);
                    foreach ($strings as $string) {
                        $value['img_path_data'] .= WMS_DOMAIN . trim($string, '"') . ',';
                    }
                    $value['img_path_data'] = trim($value['img_path_data'], ',');
                    $value['img_path_data'] = str_replace("\\", '', $value['img_path_data']);
                }
            }
            $value['abnormal_type_flag'] = $value['abnormal_type'];
            $value['abnormal_type'] = getWarehouseAbnormalType($value['abnormal_type']);
            $value['defective_type'] = getAbnormalDefectiveType($value['defective_type']);
            $value['department_china_ch'] = [];
            $value['product_img_url'] = erp_sku_img_sku($value['product_img_url']);
            $value['pai_number'] = isset($pai_numbers[$value['pur_number']])?$pai_numbers[$value['pur_number']]['pai_number']:'';
            //部门:1 => "供应商",2 => "采购部",3 => "品控部", 4 => "开发部",5 => "设计部",6 => "仓储中心",7 => "其他"
            if(empty($value['department_ch'])){
                $value['department_ch'] = [];
                $value['department_china_ch'] = "";
            }else {
            $department_chs = explode(",",$value['department_ch']);
            //echo getdepartmentData(1);die();
            foreach($department_chs as $department_chs_value){
                $value['department_china_ch'][] = getdepartmentData($department_chs_value);

            }
                $value['department_china_ch'] = array_filter($value['department_china_ch']);
                if(!empty($value['department_china_ch'])) {
            $value['department_china_ch'] = implode(",",$value['department_china_ch']);
            $value['department_ch'] = $department_chs;
                }else{
                    $value['department_china_ch'] = rtrim(implode(",",$department_chs),",");
                    $value['department_ch'] = $department_chs;
                }
            }

            //缺货数量
            $value['lack_quantity']            = isset($value['left_stock'])?$value['left_stock']:'';
            $value['lack_quantity_status']  = intval($value['lack_quantity'])<0 ?'是':'否';

            $value['duty_group']  = getDutyGroup($value['duty_group']);
            $value['deal_used']  = self::get_deal_used_cn($value['deal_used']);

            $value['add_note_time'] = $value['add_note_time']=='0000-00-00 00:00:00'?'':$value['add_note_time'];

            $value['is_purchasing'] = $is_purchasing_arr[$value['is_purchasing']]??'';

            if( isset($value['is_new']) && $value['is_new'] ==1){
                $value['is_new_ch'] = "是";
            }else{
                $value['is_new_ch'] = "否";
            }

            //计算新增俩个字段
            if ($value['handler_type'] == 1) {
                $sku_info = $this->product_model->get_product_info($value['sku']);

                $weight =  ($sku_info['product_weight']*$value['quantity'] )/ 1000;
                $value['product_weight'] = sprintf("%.3f", $weight);;


                //计算参考运费
                $cal_weight = intval($sku_info['product_weight'])/1000 * $value['quantity'];

                $reference_freight = $this->warehouse_model->get_reference_freight($value['handle_warehouse_code'],$value['return_province'],$cal_weight);

                if($reference_freight === false){
                    $value['reference_freight'] = '';

                } else {
                    $value['reference_freight'] = format_two_point_price($reference_freight);
                }

            } else {
                $value['product_weight']='';
                $value['reference_freight']='';

            }



            $express = $value['express_no'];

            $express_arr = explode(' ', $express);
            $express_arr = $arr = array_filter($express_arr);

            $express_company = explode(',', $value['express_company']);
            $track_status = explode(',', $value['track_status']);
            $carrier_code = explode(',', $value['carrier_code']);


            $logistics_info=[];
            foreach ($express_arr as $key => $v){
                $logistics_info[$key]['express_no'] = $v;
                $logistics_info[$key]['express_company'] = isset($express_company[$key])?$express_company[$key]:'';
                $logistics_info[$key]['track_status'] = isset($track_status[$key]) ? $track_status[$key] : '';
                $logistics_info[$key]['track_status_cn'] = isset($track_status[$key]) ? (!empty($track_status[$key]) ? getTrackStatus($track_status[$key]) : '') : '';
                $logistics_info[$key]['carrier_code'] = isset($carrier_code[$key])?$carrier_code[$key]:'';
            }
            $value['groupName']                = isset($buyerName[$value['buyer']])?$buyerName[$value['buyer']]['group_name']:'';
            $value['express_no'] = $logistics_info;
            unset($value['left_stock'] );
            unset($value['lack_quantity'] );
            //旺旺链接
            $value['link_me'] = $this->Ali_order_model->Wangwang($value['supplier_code']);

            $category_all = $this->product_line_model->get_all_parent_category($value['product_line_id']);
            $value['product_line_name']  = $category_all[0]['product_line_name']??'';
            //跟单员显示

            $merchandiser_bind_info = $this->Merchandiser_group_model->get_bind_merchandiser_info($bind_warehouse_set[$value['handle_warehouse_code']],$value['order_buyer_id']??0);
            $value['merchandiser'] = $merchandiser_bind_info['user_name']??'';

        }

        //下拉框产品线
        $this->load->model('product_line_model','product_line',false,'product');
        $product_line_list = $this->product_line->get_product_line_list(0);
        $product_line_id =array_column($product_line_list, 'linelist_cn_name','product_line_id');
        //物流轨迹状态下拉 {"1":"已揽件","2":"已发货","3":"已到提货点","4":"派件中","5":"已签收"}
        $track_status= getTrackStatus();
        unset($track_status[3]);

        $return_data = [
            'data_list'   => [
                'value'   => $result,
                'key'     => [  'ID','产品名称','异常类型','图片','单号','采购单信息','供应商','异常信息','是否处理','处理类型','仓库处理结果',
                                '等待处理中','备注','总重量(KG)','参考运费','退货快递单号','操作'
                            ],
                'drop_down_box' => [
                    'is_handler' => getAbnormalHandleResult(),
                    'handler_type' => getAbnormalHandleType(),
                    'abnormal_type' => getWarehouseAbnormalType(),
                    'defective_type'=>getAbnormalDefectiveType(),
                    'user_list' => getBuyerDropdown(),
                    'province_list' => $this->get_province(),
                    'duty_group' => getDutyGroup(),
                    'is_left_stock' => getIsLeftStock(),
                    'product_line_id' => $product_line_id,
                    'warehouse_code' => getWarehouse(),
                    'handle_warehouse' => getWarehouse(),
                    'track_status' => $track_status,
                    'is_purchasing'=>[1=>'否',2=>'是'],
                    'merchandiser_list'    =>$this->Merchandiser_group_model->get_user_list_down(),
                    'is_new' => [1=>'是',0=>'否'],
                    'department' => [
                        1 => "供应商",
                        2 => "采购部",
                        3 => "品控部",
                        4 => "开发部",
                        5 => "设计部",
                        6 => "仓储中心",
                        7 => "其他"
                    ]
                ]
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit,
            ]
        ];

        $uid = $this->input->get_post('uid');
        $searchData = $this->product_model->get_user_header($uid,'abnormal',$this->header());
        if(empty($params['is_handler']) || in_array(1,$params['is_handler']) || in_array(5,$params['is_handler'])){
            $searchData[] = [

                "name" => "责任部门",
                "status" => 0,
                "index" => 21,
                "key" => "key21",
                "field" => [

                     "department_china_ch"
                    ]
                ];
        }
        $return_data['data_list']['key'] = $searchData;


        return $return_data;

    }

    /**
     * 需求： 37177异常列表新增编辑显示内容
     * 在异常列表管理页面，新增“编辑显示内容”按钮，点击该按钮效果同采购单页面的编辑显示内容一样，可以自由显示字段的顺序
     * @authr:luxu
     * @time:2021年6月30
     **/
    public function header(){

        $header = [
            'key1' => ['name'=>'异常类型/次品类型','status'=>0,'index'=>0,'val'=>['abnormal_type','defective_type']],
            'key2' => ['name' => '图片','status'=>0,'index'=>1,'val'=>['img_path_data']], // 产品图片
            'key3' => ['name' => '单号','status'=>0,'index'=>2,'val'=>['defective_id','express_code','exception_position ']], // 单号
            'key4' => ['name' => '采购单信息','status'=>0,'index'=>3,'val'=>['pur_number','sku','quantity','buyer']],
            'key5' => ['name' => '供应商','status'=>0,'index'=>4,'val'=>['supplier_name','supplier_code','link_me']],
            'key6' => ['name'=>'一级产品线/是否代采','status'=>0,'index'=>5,'val'=>['product_line_name','is_purchasing']],
            'key7' => ['name'=>'异常信息','status'=>0,'index'=>6,'val'=>['create_user_name','create_time']],

            'key8' => ['name'=>'异常描述','status'=>0,'index'=>7,'val'=>['abnormal_depict']],
            'key9' => ['name' => '采购员是否处理','status'=>0,'index'=>8,'val'=>['is_handler']],
            'key10' => ['name'=>'采购仓库/登记仓库','status'=>0,'index'=>9,'val'=>['warehouse_name/handle_warehouse_name']],
            'key11' => ['name'=>'采购员处理结果','status'=>0,'index'=>10,'val'=>['handler_type']],
            'key12' =>['name'=>'仓库处理结果','status'=>0,'index'=>11,'val'=>['warehouse_handler_result','warehouse_handler_time']],
            'key13' => ['name'=>'跟单员/处理人','status'=>0,'index'=>12,'val'=>['merchandiser','handler_person']],
            'key14' => ['name' => '处理时间','status'=>0,'index'=>13,'val'=>['handler_time']],
            'key15' => ['name' => '责任小组','status'=>0,'index'=>15,'val'=>['duty_group']],
            'key16' => ['name' => '备注','status'=>0,'index'=>16,'val'=>['note','add_note_person','add_note_time']],
            'key17' => ['name' => '总重量(KG)/参考运费','status'=>0,'index'=>17,'val'=>['product_weight','reference_freight']],
            'key18' => ['name' => '退货快递单号','status'=>0,'index'=>18,'val'=>['return_express_no']],
            'key19' => ['name' => '处理时效(h)','status'=>0,'index'=>19,'val'=>['deal_used']],
            'key20' => ['name' => '是否新品','status'=>0,'index'=>20,'val'=>['is_new_ch']]


        ];
        return $header;
    }

    /**
     * 组装搜索条件
     */
    public function get_abnormal_list_search_params($params){
        $this->load->helper('status_order');
        $field='pro.product_img_url,n.department as department_ch,items.is_new,n.return_express_no,pro.product_name,n.is_new_wms,n.handler_time,n.handler_person,n.id,n.handler_type,n.return_province,n.warehouse_code as handle_warehouse_code,n.sku,n.handler_time,n.handler_person,n.handler_describe,n.warehouse_name as handle_warehouse_name,img_path_data,n.quantity,exception_position,defective_id,express_code,n.pur_number,order.buyer_name AS buyer,n.create_user_name,n.create_time,abnormal_depict,is_handler,
        handler_type,abnormal_type,defective_type,warehouse_handler_result,waiting_process,s.left_stock,s.demand_number,duty_group,order.supplier_name,order.supplier_code,order.buyer_id as order_buyer_id,pro.product_line_id,
        s.warehouse_name,n.deal_used,group_concat(r.express_no SEPARATOR \' \') express_no,p.documentary_name,n.warehouse_handler_time,n.note,n.add_note_person,
        n.add_note_time,group_concat(r.express_company) as express_company,group_concat(r.status) AS track_status,group_concat(r.carrier_code) AS carrier_code,pro.is_purchasing';
        $old_time = date("Y",strtotime($params['pull_time_start']));
        if($old_time != date("Y") && $old_time == '2019'){
            $this->table_name = $this->table_name.'_'.$old_time;
            // old_time 年份数据表是否存在
            if(!$this->purchase_db->table_exists($this->table_name)){
                return [
                    "code"  => 0,
                    "msg"   => "没有 ".$old_time." 年度的归档数据."
                ];
            }
        }

        $this->purchase_db->select($field)->from($this->table_name.' as n')
            ->join('purchase_suggest_map as m', 'n.pur_number=m.purchase_number and n.sku=m.sku', 'left')
            ->join('purchase_suggest as s', 's.demand_number=m.demand_number', 'left')
            ->join('purchase_order as order', 'n.pur_number=order.purchase_number', 'left')
            ->join('purchase_order_items AS items','n.pur_number=items.purchase_number AND n.sku=items.sku','left')
            ->join('pur_excep_return_info as r', 'r.excep_number=n.defective_id AND r.is_del=0', 'left')
            ->join('pur_purchase_progress as p', 'p.demand_number=s.demand_number', 'left')
            ->join('pur_product as pro', 'pro.sku=n.sku', 'left')
            ->where('is_reject',0);

        if(isset($params['sku']) && !empty($params['sku'])){//批量，单个
            $skus=query_string_to_array($params['sku']);
            $this->purchase_db->where_in('n.sku',$skus);
        }


        if( isset($params['is_new']) && !empty($params['is_new'])){

            if($params['is_new'] == 2){

                $params['is_new'] = 0;
            }

            $this->purchase_db->where("items.is_new",$params['is_new']);
        }


        if(isset($params['buyer']) && !empty($params['buyer'])){
            if(is_array($params['buyer'])){
                $b_x = 0;
                foreach($params['buyer'] as $k){
                    $reg = "/(\D+)/";
                    preg_match($reg, $k,$m);
                    if($b_x == 0){
                        $this->purchase_db->like('order.buyer_name',$m[0], 'both');
                    }else{
                        $this->purchase_db->or_like('order.buyer_name',$m[0], 'both');
                    }
                    $b_x ++;
                }
            }else{
                $reg = "/(\D+)/";
                preg_match($reg, $params['buyer'],$m);
                $this->purchase_db->like('order.buyer_name',$m[0], 'both');
            }
        }

        if(isset($params['group_ids']) && !empty($params['group_ids'])){
            $this->purchase_db->where_in('order.buyer_name',$params['groupdatas']);
        }

        if( isset($params['handler_person']) && !empty($params['handler_person'])){

            $this->purchase_db->where_in('n.handler_person',$params['handler_person']);
        }

        if( isset($params['handler_time_start']) && !empty($params['handler_time_start'])){

            $this->purchase_db->where('n.handler_time>=',$params['handler_time_start']);
        }

        if( isset($params['handler_time_end']) && !empty($params['handler_time_end'])){

            $this->purchase_db->where('n.handler_time<=',$params['handler_time_end']);
        }

        if( isset($params['product_name']) && !empty($params['product_name'])){
            $this->purchase_db->like('pro.product_name', $params['product_name'], 'both');
        }

        if(isset($params['defective_id']) && !empty($params['defective_id'])){
            if(is_array($params['defective_id'])){
                $this->purchase_db->where_in('defective_id', $params['defective_id']);
            }else{
                $defective_ids = explode(' ', $params['defective_id']);
                $this->purchase_db->where_in('defective_id',$defective_ids);
            }
        }

        if(isset($params['department']) && !empty($params['department'])){
            $this->purchase_db->group_start();
            foreach($params['department'] as $department_value){
                $this->purchase_db->or_like('n.department',$department_value,'both');
            }
            $this->purchase_db->group_end();
        }
        if(isset($params['pur_number']) && !empty($params['pur_number'])){
            if(is_array($params['pur_number'])){
                $this->purchase_db->where_in('n.pur_number',$params['pur_number']);
            }else{
                $defective_ids = explode(' ', $params['pur_number']);
                $this->purchase_db->where_in('n.pur_number',$defective_ids);
            }
        }

        if(isset($params['express_code']) && !empty($params['express_code'])){
            $this->purchase_db->where('express_code',$params['express_code']);
        }

        if(isset($params['is_handler']) && !empty($params['is_handler'])){
            $this->purchase_db->where_in('is_handler',$params['is_handler']);
        }

        if(isset($params['handler_type']) && !empty($params['handler_type'])){
            if( is_array($params['handler_type'])) {
                $this->purchase_db->where_in('handler_type', $params['handler_type']);
            }else{
                $this->purchase_db->where('handler_type', $params['handler_type']);
            }
        }


        if(isset($params['abnormal_type']) && !empty($params['abnormal_type'])){

            if( is_array($params['abnormal_type'])) {
                $this->purchase_db->where_in('abnormal_type', $params['abnormal_type']);
            }else{
                $this->purchase_db->where('abnormal_type', $params['abnormal_type']);
            }
        }

        if(isset($params['defective_type']) && !empty($params['defective_type'])){
            $this->purchase_db->where('defective_type',$params['defective_type']);
        }

        if(isset($params['is_purchasing']) && !empty($params['is_purchasing'])){
            $this->purchase_db->where('pro.is_purchasing',$params['is_purchasing']);
        }

        if(isset($params['pull_time_start']) && !empty($params['pull_time_start'])){
            $this->purchase_db->where('pull_time>=',$params['pull_time_start']);
        }

        if(isset($params['pull_time_end']) && !empty($params['pull_time_end'])){
            $this->purchase_db->where('pull_time<=',$params['pull_time_end']);
        }

        if(isset($params['waiting_process']) && !empty($params['waiting_process'])){
            $this->purchase_db->like('waiting_process',$params['waiting_process'],'after');
        }

        if (( isset($params['deal_used_start']) && !empty($params['deal_used_start']) && isset($params['deal_used_end']) &&
                !empty($params['deal_used_end']) ) && $params['deal_used_start'] == $params['deal_used_end'] ){
            $this->purchase_db->where('deal_used=',$params['deal_used_start']*3600);
        }else{
            if(isset($params['deal_used_start']) && !empty($params['deal_used_start'])){
                $this->purchase_db->where('deal_used>=',$params['deal_used_start']*3600);
            }

            if(isset($params['deal_used_end']) && !empty($params['deal_used_end'])){
                $this->purchase_db->where('deal_used<=',$params['deal_used_end']*3600);
            }
        }

        if(isset($params['is_left_stock']) && is_numeric($params['is_left_stock'])){
            if(intval($params['is_left_stock']) == 1){
                $this->purchase_db->where('s.left_stock <',0);
            }else{
                $this->purchase_db->where('s.left_stock >=',0);
            }
            unset($params['is_left_stock']);
        }

        if(isset($params['duty_group']) && !empty($params['duty_group'])){
            $this->purchase_db->where('duty_group',$params['duty_group']);
        }

        if(isset($params['documentary_id']) && !empty($params['documentary_id'])){//跟单员ID

            if(is_array($params['documentary_id'])){
                $this->purchase_db->where_in('p.documentary_id', $params['documentary_id']);
            }else{
                $buyers = explode(',', $params['documentary_id']);
                $this->purchase_db->where_in('p.documentary_id',$buyers);
            }
        }

        if(isset($params['express_no']) && !empty($params['express_no'])){
            $this->purchase_db->like('r.express_no',$params['express_no']);
        }


        if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {

            $this->purchase_db->where_in('pro.product_line_id', $params['product_line_id']);

            unset($params['product_line_id']);
        }




        if(isset($params['warehouse_code']) && !empty($params['warehouse_code'])){
            if(is_array($params['warehouse_code'])){
                $this->purchase_db->where_in('s.warehouse_code', $params['warehouse_code']);
            }else{
                $this->purchase_db->where_in('s.warehouse_code', array_filter(explode(',', $params['warehouse_code'])));
            }
        }


        if(isset($params['handle_warehouse']) && !empty($params['handle_warehouse'])){
            if(is_array($params['handle_warehouse'])){
                $this->purchase_db->where_in('n.warehouse_code', $params['handle_warehouse']);
            }else{
                $this->purchase_db->where_in('n.warehouse_code', array_filter(explode(',', $params['handle_warehouse'])));
            }
        }

        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('order.supplier_code', $params['supplier_code']);
        }

        if(isset($params['track_status']) && !empty($params['track_status'])){
            if(is_array($params['track_status'])){
                $this->purchase_db->where_in('r.status', $params['track_status'], false);
            }else{
                $this->purchase_db->where_in('r.status', explode(',', $params['track_status']), false);
            }
        }

        if(isset($params['merchandiser_id']) && !empty($params['merchandiser_id'])){
            if (!empty($params['merchandiser_data'])) {
                $buyer_id =  explode(',',$params['merchandiser_data']['buyer_id']);
                $this->purchase_db
                ->join('pur_merchandiser_user as mer', 'mer.warehouse_code=n.warehouse_code ', 'left')->where('mer.warehouse_code is not null')->where_in('order.buyer_id',$buyer_id);

            }


        }

        return [];
    }


    /**
     * @return string
     */
    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
     * 获取当前条件下的总数
     */
    public function get_export_total($params,$offset,$limit,$page=1,$export=false){
        $res_params = $this->get_abnormal_list_search_params($params);
        if(count($res_params) > 0)return 0;
        if(isset($params['ids']) && $params['ids']!=""){

            $this->purchase_db->where_in('n.id',$params['ids']);
        }
//        $this->purchase_db->group_by('n.defective_id');
        $total=$this->purchase_db->count_all_results();
        return $total && $total > 0 ? $total : 0;
    }

    /**
     * 获取异常数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function get_abnormal_list_export($params,$offset,$limit,$page=1,$export=false){
        $is_purchasing_arr = [1=>'否',2=>'是'];
        $res_params = $this->get_abnormal_list_search_params($params);
        if(count($res_params) > 0)return $res_params;
        if(isset($params['ids']) && $params['ids']!=""){
            $this->purchase_db->where_in('n.id',$params['ids']);
        }

        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数
        $this->purchase_db=$clone_db;
        if ($export){
            $result=$this->purchase_db->order_by('create_time','DESC')->group_by('n.defective_id')->get()->result_array();
        }else{
            $result=$this->purchase_db->order_by('create_time','DESC')->group_by('n.defective_id')->limit($limit,$offset)->get()->result_array();
        }
        $buyerNames = [];
        if(!empty($result)){

            $buyerNames = array_unique(array_column($result,'buyer'));
            $buyerNames = array_map(function($data){
                return sprintf("'%s'",$data);
            },$buyerNames);
        }
        $buyerName = $this->User_group_model->getNameGroupMessage($buyerNames);
        $buyerName = array_column($buyerName,NULL,'user_name');

        foreach ($result as &$value){
            if($value['is_new_wms'] == 1 && strstr($value['img_path_data'],'http')){
                $wmsImages = json_decode($value['img_path_data'],true);
                $value['img_path_data'] = !empty($wmsImages)?implode(",",$wmsImages):'';
            }else {
                preg_match('/(?:\")(.*)(?:\")/i', $value['img_path_data'], $matches);
                if (isset($matches[0])) {
                    $value['img_path_data'] = '';
                    $strings = explode(',', $matches[0]);
                    foreach ($strings as $string) {
                        $value['img_path_data'] .= WMS_DOMAIN . trim($string, '"') . ',';
                    }
                    $value['img_path_data'] = trim($value['img_path_data'], ',');
                    $value['img_path_data'] = str_replace("\\", '', $value['img_path_data']);
                }
            }
            
            $value['abnormal_type'] = !empty($value['abnormal_type']) ? getWarehouseAbnormalType($value['abnormal_type']) : '';
            $value['handler_type'] =  !empty($value['handler_type']) ? getAbnormalHandleType($value['handler_type']) : '';
            $value['defective_type'] =  !empty($value['defective_type']) ? getAbnormalDefectiveType($value['defective_type']) : '';



            $value['is_purchasing'] = $is_purchasing_arr[$value['is_purchasing']]??'';
//            $value['defective_type'] = !empty($value['defective_type']) ? getAbnormalDefectiveType($value['defective_type']) : '';
            //缺货数量
            $value['lack_quantity']            = isset($value['left_stock'])?$value['left_stock']:'';
            $value['lack_quantity_status']  = intval($value['lack_quantity'])<0 ?'是':'否';

            $value['duty_group']  = getDutyGroup($value['duty_group']);
            unset($value['left_stock'] );
            $value['deal_used']  = self::get_deal_used_cn($value['deal_used']);

            $value['add_note_time'] = $value['add_note_time']=='0000-00-00 00:00:00'?'':$value['add_note_time'];

            $express = $value['express_no'];

            $express_arr = explode(' ', $express);
            $express_arr = $arr = array_filter($express_arr);

            $value['express_no'] = implode(',',$express_arr);
            //转换轨迹状态对应的中文
            $track_status_arr = explode(' ', $value['track_status']);
            $track_status_arr = array_filter($track_status_arr);
            $track_status_cn_arr = array();
            foreach ($track_status_arr as $item){
                $track_status_cn_arr[] = !empty($item) ? getTrackStatus($item) : '';
            }
            $value['track_status'] = implode(',',$track_status_cn_arr);
            $value['groupName']                = isset($buyerName[$value['buyer']])?$buyerName[$value['buyer']]['group_name']:'';

            //计算新增俩个字段
            if ($value['handler_type'] == '退货') {
                $sku_info = $this->product_model->get_product_info($value['sku']);

                $weight =  ($sku_info['product_weight']*$value['quantity'] )/ 1000;
                $value['product_weight'] = sprintf("%.3f", $weight);;


                //计算参考运费
                $cal_weight = intval($sku_info['product_weight'])/1000 * $value['quantity'];

                $reference_freight = $this->warehouse_model->get_reference_freight($value['handle_warehouse_code'],$value['return_province'],$cal_weight);

                if($reference_freight === false){
                    $value['reference_freight'] = '';

                } else {
                    $value['reference_freight'] = format_two_point_price($reference_freight);
                }

            } else {
                $value['product_weight']='';
                $value['reference_freight']='';

            }
            $value['department_china_ch'] = [];
            //部门:1 => "供应商",2 => "采购部",3 => "品控部", 4 => "开发部",5 => "设计部",6 => "仓储中心",7 => "其他"
            if(empty($value['department_ch'])){
                $value['department_ch'] = [];
                $value['department_china_ch'] = "";
            }else {
                $department_chs = explode(",", $value['department_ch']);

                //echo getdepartmentData(1);die();
                foreach ($department_chs as $department_chs_value) {
                    $value['department_china_ch'][] = getdepartmentData($department_chs_value);

                }
                $value['department_china_ch'] = implode(",", $value['department_china_ch']);
                $value['department_ch'] = $department_chs;
            }
            $category_all = $this->product_line_model->get_all_parent_category($value['product_line_id']);
            $value['product_line_name']  = $category_all[0]['product_line_name']??'';
        }

        $return_data = [
            'data_list'   => [
                'value'   => $result,
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit,
            ]
        ];
        return $return_data;

    }

    /**
     * 获取未推送给仓库的异常数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function get_abnormal_list_to_warehouse($limit=0){
//        $field='express_code,is_new_wms,defective_id,handler_type,abnormal_type,handler_person,pur_number,handler_time,handler_describe,
//                return_province,return_city,return_address,return_linkman,return_phone,upload_img_data';

        $field='express_code,is_new_wms,defective_id,handler_type,abnormal_type,handler_person,pur_number,handler_time,handler_describe,
                return_province,return_city,return_address,return_linkman,return_phone,upload_img_data';
        $this->purchase_db->select($field)->from($this->table_name)
            ->group_start()
            ->where('is_handler',1)
            ->or_where('is_handler',2)
            ->group_end()
            ->where('is_push_warehouse',0);

        if ($limit){
            $result=$this->purchase_db->order_by('create_time','DESC')->get('',$limit)->result_array();
        }else{
            $result=$this->purchase_db->order_by('create_time','DESC')->get()->result_array();
        }

        return $result;
    }

    /**
    * 数据格式化
    * @param $data_list
    * @return array   
    * @author Jaxton 2019/01/18
    */
    public function formart_abnormal_list($data_list){
    	if(!empty($data_list)){
    		foreach($data_list as $key => $val){
    			$data_list[$key]['is_handler']=getAbnormalHandleResult($val['is_handler']);
    			$data_list[$key]['handler_type']=!empty($val['handler_type'])?getAbnormalHandleType($val['handler_type']):'';
    		}
    	}
    	return $data_list;
    }

    /**
    * 获取一条数据
    * @param $defective_id
    * @return array   
    * @author Jaxton 2019/01/16
    */
    public function get_one_abnormal($defective_id){
    	$row=$this->purchase_db->select('n.*,group_concat(r.express_no SEPARATOR \' \') express_no')
    	->from($this->table_name.' as n')
        ->join('pur_excep_return_info as r', 'r.excep_number=n.defective_id', 'left')
        ->group_by('n.defective_id')
    	->where('defective_id',$defective_id)
    	->get()->row_array();
    	if($row){
    		return $row;
    	}else{
    		return false;
    	}
    }

    /**
     * 导入时查询sku对应的数据
     * @author Manson
     */
    public function get_import_sku_info($sku_list)
    {
        if (empty($sku_list)){
            return [];
        }
        $map = [];
        $result = $this->purchase_db->select('pro.sku, pro.purchase_price, pro.product_weight, pro.product_name,order.buyer_name, order.buyer_id, order.buyer_name')
            ->from('product pro')
            ->join('purchase_order_items items','pro.sku = items.sku','left')
            ->join('purchase_order order','order.purchase_number = items.purchase_number','left')
            ->where_in('pro.sku',$sku_list)
            ->order_by('order.create_time desc')//降序排 采购时间
            ->get()->result_array();

        foreach ($result as $item){
            if (!isset($map[$item['sku']])){
                $map[$item['sku']] = [
                    'sample_packing_weight' => $item['product_weight']??'',//样品包装重量(克)
                    'product_name' => $item['product_name']??'',//产品名称
                    'unit_price_without_tax' => $item['purchase_price']??0,//未税单价
                    'buyer_id' => $item['buyer_id']??0,
                    'buyer_name' => $item['buyer_name']??''
                ];
            }
        }
        return $map;
    }



    /**
    * 采购员处理提交
    * @param array $params 
    * @return array   
    * @author Jaxton 2019/01/16
    */
    public function buyer_handle_submit($params){
    	$order_info=$this->get_one_abnormal($params['defective_id']);
        $this->load->model('purchase/purchase_order_model');
        $purchase_order = $this->purchase_order_model->get_one($params['pur_number'],false);
        if(empty($purchase_order)){
            $this->error_msg .= '不属于本系统po,请重新填写';
            return [
                'success'=>$this->success,
                'error_msg'=>$this->error_msg
            ];
        }
        $log_data = false;
        if($order_info){
    	    if($params['type']==1){//处理方式为已处理
                if( ($order_info['is_handler']==0 || $order_info['is_handler']==2 || $order_info['is_handler'] == 6) && $order_info['is_reject']==0){
                    $deal_used = time() - strtotime($order_info['pull_time']);// 计算时效（秒）
                    $add_data=[
                        'handler_type'=>$params['handler_type'],
                        'return_province'=>$params['return_province'],
                        'return_city'=>$params['return_city'],
                        'return_county'=>$params['return_county'],
                        'return_address'=>$params['return_address'],
                        'return_linkman'=>$params['return_linkman'],
                        'return_phone'=>$params['return_phone'],
                        'handler_describe'=>$params['handler_describe'],
                        'is_handler'=>1,//改为已处理
                        'handler_person'=>getActiveUserName(),
                        'handler_time'=>date('Y-m-d H:i:s'),
                        'pur_number'=>$params['pur_number'],
                        'is_push_warehouse'=>0,
//                        'duty_group'=>$params['duty_group'],
                        'deal_used'=>$deal_used,//处理时效
                        'department' => implode(",",$params['department'])
                    ];
                    if (!empty($params['upload_image'])){
                        $add_data['upload_img_data'] = json_encode($params['upload_image']);
                    }
                    if($order_info['abnormal_type'] && $params['handler_type']==7){
                        //当异常类型为查找入库单(abnormal_type = 1),而且处理类型是整批退货的时候,转为退货
                        $add_data['handler_type'] = 1;
                    }
                    //残次品-IQC、批量次品-IQC、残次品-加工组、批量次品-加工组 4种异常类型，并且 处理类型为 驳回-判定失误、驳回-描述不清 时，由IQC处理，其他的由质控组处理
                    if ( in_array($params['handler_type'],[18,21]) && in_array($order_info['abnormal_type'],[4,5,9,10])){
                        $add_data['duty_group'] = DUTY_GROUP_IQC;
                    }else{
                        $add_data['duty_group'] = DUTY_GROUP_QUALITY_CONTROL;
                    }
                    // 转入库后退货
                    if( $params['handler_type'] == 25){
                        //按照SKU的维度生成一条“入库后退货”的记录到入库后退货管理列表，进行后续流程；
                        /**
                        （1）SKU：异常数据对应的SKU；
                        （2）申请退货数量：异常数据对应的SKU数量；
                        （3）申请退货原因：库内异常退货；
                        （4）退货仓库：取值为异常单对应的登记仓库；
                         **/
                        // 获取异常数据
                        $abnormalDatas = $this->purchase_db->from("purchase_warehouse_abnormal")->where("defective_id",$params['defective_id'])->get()->row_array();
                        if(!empty($abnormalDatas)){

                            // 不为空的情况下,获取SKU 的sample_packing_weight
                            $sample_package_weight = $this->get_import_sku_info($abnormalDatas['sku']);

                            $main_number = 'TH'.date('YmdHis').round(1,100);
                            $return_qty = $abnormalDatas['quantity'];
                            $manInserData = [
                                'main_number' => $main_number,
                                'sample_packing_weight' => $sample_package_weight[$abnormalDatas['sku']]['sample_packing_weight']??'',
                                'product_name' => $sample_package_weight[$abnormalDatas['sku']]['product_name']??'',
                                'unit_price_without_tax' => $sample_package_weight[$abnormalDatas['sku']]['unit_price_without_tax']??'',
                                'buyer_id' => $sample_package_weight[$abnormalDatas['sku']]['buyer_id']??'',
                                'buyer_name' => $sample_package_weight[$abnormalDatas['sku']]['buyer_name']??"",
                                'create_time' =>  date('Y-m-d H:i:s'),
                                'create_user' => getActiveUserName(),
                                'sku' => $abnormalDatas['sku'],
                                'return_reason' => 3, //库内异常退货
                                'return_warehouse_code' => $abnormalDatas['warehouse_code'],
                                'return_qty' => $return_qty
                            ];

                            $this->purchase_db->insert('return_after_storage_main',$manInserData);
                        }
                    }


                    $this->purchase_db->trans_begin();
                    try{
                        //根據採購單號和sku更新採購單明細表，異常處理狀態
                        if(!empty($order_info['sku'])){
                            $this->purchase_db->where(['purchase_number' => $order_info['pur_number'], 'sku' => $order_info['sku']])
                                ->update($this->table_purchase_order_items_name, ['abnormal_flag' => 0]);
                        }else{
                            $this->purchase_db->where(['purchase_number' => $order_info['pur_number']])
                                ->update($this->table_purchase_order_items_name, ['abnormal_flag_no_sku' => 0]);
                        }
                        $add_result=$this->purchase_db->where('defective_id',$params['defective_id'])->update($this->table_name,$add_data);
                        //推送至仓库系统

                        //存记录
                        $this->load->helper('common');
                        $log_data=[
                            'id'=>$params['defective_id'],
                            'type'=>$this->table_name,
                            'content'=>'采购员处理',
                            'detail'=>'采购员处理提交'//json_encode($add_data)
                        ];

                        $this->purchase_db->trans_commit();
                        $this->success=true;
                    }catch(Exception $e){
                        $this->purchase_db->trans_rollback();
                        $this->error_msg.='操作失败';
                    }

                }else{
                    $this->error_msg.='此异常单已处理，不可再操作';
                }
            }elseif($params['type']==2){//处理方式为处理中
                if( ($order_info['is_handler']==0 ) && $order_info['is_reject']==0){
                    $add_data=[
                        'handler_type'=>15,//等待处理 pur_param_sets表_PUR_PURCHASE_ABNORMAL_ABNORMAL_HANDLE_TYPE
                        'return_province'=>$params['return_province'],
                        'return_city'=>$params['return_city'],
                        'return_county'=>$params['return_county'],
                        'return_address'=>$params['return_address'],
                        'return_linkman'=>$params['return_linkman'],
                        'return_phone'=>$params['return_phone'],
                        'handler_describe'=>$params['handler_describe'],
                        'is_handler'=>2,//改为处理中
                        'handler_person'=>getActiveUserName(),
                        'handler_time'=>date('Y-m-d H:i:s'),
                        'pur_number'=>$params['pur_number'],
//                        'duty_group'=>$params['duty_group'],
                        'department' => implode(",",$params['department'])
                    ];
                    $this->purchase_db->trans_begin();
                    try{
                        $add_result=$this->purchase_db->where('defective_id',$params['defective_id'])->update($this->table_name,$add_data);
                        //推送至仓库系统

                        //存记录
                        $this->load->helper('common');
                        $log_data=[
                            'id'=>$params['defective_id'],
                            'type'=>$this->table_name,
                            'content'=>'采购员处理',
                            'detail'=>'采购员处理中'//json_encode($add_data)
                        ];

                        $this->purchase_db->trans_commit();
                        $this->success=true;
                    }catch(Exception $e){
                        $this->purchase_db->trans_rollback();
                        $this->error_msg.='操作失败';
                    }
                }else{
                    $this->error_msg.='此异常单已为处理中';
                }
            }

            if($log_data)operatorLogInsert($log_data);
    	}else{
    		$this->error_msg.='此异常单号不存在';
    	}
    	return [
    		'success'=>$this->success,
    		'error_msg'=>$this->error_msg
    	];
    }

    /**
    * 异常数据添加
    * @param array 
    * @return array   
    * @author Jaxton 2019/01/16
    */
    public function add_abnormal_data($data){
        $insert_data=$data;
        $insert_data['pull_time'] = date('Y-m-d H:i:s');
        $insert_data['defective_id'] = generateOrderNo();
       
        $this->purchase_db->trans_begin();
        $this->purchase_db->insert($this->table_name, $insert_data);
        if ($this->purchase_db->trans_status() === FALSE)
        {
            $this->purchase_db->trans_rollback();
            $this->error_msg.='操作失败';
        }
        else
        {
            $this->purchase_db->trans_commit();
            $this->success=true;
        }
        return [
            'success'=>$this->success,
            'error_msg'=>$this->error_msg
        ];
    }

    /**
     * 异常数据批量添加
     * @param array
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function batch_add_abnormal_data($insert_arr){
        $this->purchase_db->trans_begin();
        $this->purchase_db->insert_batch($this->table_name, $insert_arr);

        //根據採購單號和sku更新採購單明細表，異常處理狀態
        foreach ($insert_arr as $item) {
            if (empty($item['pur_number'])) continue;
            if(!empty($item['sku'])){
                $this->purchase_db->where(['purchase_number' => $item['pur_number'], 'sku' => $item['sku']])
                    ->update($this->table_purchase_order_items_name, ['abnormal_flag' => 1]);
            }else{
                $this->purchase_db->where(['purchase_number' => $item['pur_number']])
                    ->update($this->table_purchase_order_items_name, ['abnormal_flag_no_sku' => 1]);
            }
        }

        if ($this->purchase_db->trans_status() === FALSE)
        {
            $this->purchase_db->trans_rollback();
            $this->error_msg.='操作失败';
        }
        else
        {
            $this->purchase_db->trans_commit();
            $this->success=true;
        }
        return [
            'success'=>$this->success,
            'error_msg'=>$this->error_msg
        ];
    }

    /**
    * 异常数据添加验证
    * @param $params 
    * @return array   
    * @author Jaxton 2019/01/24
    */
    public function validate_abnormal($params){
        //'img_path_data','express_code','abnormal_depict','exception_position',
        /*$validate_field = [
            'sku','quantity','defective_type','pur_number','abnormal_type','buyer','add_username','create_user_name','create_time'
        ];*/

        $validate_field = [
            'defective_id','express_code'
        ];

        $flag=0;
        if(!empty($params)){
            foreach($validate_field as $field){
                if(empty($params[$field])){
                    $this->error_msg.='字段:'.$field.'的值为空;';
                    $flag++;
                }
            }
            if(!$flag){
                $this->success=true;
            }else{
                $this->success=false;
            }
        }else{
            $this->error_msg.='没有获取到提交的数据';
        }
        
        return [
            'success' => $this->success,
            'error_msg' => $this->error_msg
        ];
    }

    /**
    * 驳回
    * @param defective_id
    * @param reject_reason  
    * @return array   
    * @author Jaxton 2019/01/16
    */
    public function abnormal_reject($defective_id,$reject_reason){
    	$order_info=$this->get_one_abnormal($defective_id);
    	if($order_info){
    		if($order_info['is_handler']==0 && $order_info['is_reject']==0){
    			$edit_data=[
    				'is_reject'=>1,
    				'reject_reason'=>$reject_reason,
    				'reject_user'=>getActiveUserName(),
    				'reject_time'=>date('Y-m-d H:i:s')
    			];
    			$this->purchase_db->trans_begin();
                try{
                    $edit_result=$this->purchase_db->where('defective_id',$defective_id)->update($this->table_name,$edit_data);
                
                    //推送至仓库系统

                    //存记录
                    $this->load->helper('common');
                    $log_data=[
                        'id'=>$defective_id,
                        'type'=>$this->table_name,
                        'content'=>'异常数据驳回',
                        'detail0'=>'采购员异常数据驳回'//json_encode($add_data)
                    ];
                    operatorLogInsert($log_data);

                    $this->purchase_db->trans_commit();
                    $this->success=true;
                }catch(Exception $e){
                    $this->purchase_db->trans_rollback();
                    $this->error_msg.='操作失败';
                }    					       

    		}else{
    			$this->error_msg.='未处理状态才可驳回';
    		}
    	}else{
    		$this->error_msg.='此异常单号不存在';
    	}
    	return [
    		'success'=>$this->success,
    		'error_msg'=>$this->error_msg
    	];
    }

    /**
    * 查看
    * @param defective_id
    * @param reject_reason  
    * @return array   
    * @author Jaxton 2019/01/16
    */
    function get_look_abnormal($defective_id){
    	$list=$this->get_one_abnormal($defective_id);
    	//return $list;
    	$abnormal_list=[];
    	if($list){
    		$abnormal_list=[
    			'defective_id'=>$list['defective_id'],
    			'exception_position'=>$list['exception_position'],
    			'pur_number'=>$list['pur_number'],
    			'express_code'=>$list['express_code'],
    			'abnormal_depict'=>$list['abnormal_depict'],
    			'handler_person'=>$list['handler_person'],
    			'handler_type'=>getAbnormalHandleType($list['handler_type']),
    			'handler_time'=>$list['handler_time'],
    			'handler_describe'=>$list['handler_describe'],
    			'is_push_warehouse'=>getIsPushWarehouse($list['is_push_warehouse']),
                'express_no'=>$list['express_no'],
    		];

    	}
    	return $abnormal_list;
    }

    /**
    * 获取省
    * @return array   
    * @author Jaxton 2019/01/23
    */
    public function get_province(){
        $list=$this->purchase_db->select('id,region_name,region_code')->from('region')
        ->where('region_type',REGION_TYPE_PROVINCE)
        ->get()->result_array();
        $new_data=[];
        if(!empty($list)){
            foreach($list as $k => $v){
                $new_data[$v['region_code']]=$v['region_name'];
            }
        }
        return $new_data;
    }

    /**
    * 获取市
    * @return array   
    * @author Jaxton 2019/01/23
    */
    public function get_city_county($pid){
        $list=$this->purchase_db->select('id,region_name,region_code')->from('region')
        ->where('pid',$pid)
        ->get()->result_array();
        $new_data=[];
        if(!empty($list)){
            foreach($list as $k => $v){
                $new_data[$v['region_code']]=$v['region_name'];
            }
        }
        return $new_data;
    }

    /**
     * 根据异常单号更新记录
     * @param $defective_id
     * @param array $update
     * @return bool
     */
    public function update_abnormal_by_defective_id($defective_id,$update=[]){
        $edit_result=$this->purchase_db->where('defective_id',$defective_id)->update($this->table_name,$update);
        return $edit_result;
    }

    /**
     * 根据多个异常单号更新
     * @param array $where
     * @param array $update
     * @return bool
     */
    public function update_abnormals($where = [],$update=[]){
        if (empty($where)){
            return false;
        }
        if (!empty($where['defective_id'])){
            $this->purchase_db->where_in('defective_id',$where['defective_id']);
        }

        $edit_result=$this->purchase_db->update($this->table_name,$update);
        return $edit_result;
    }

    /**
     * @desc 批量更新异常订单号
     * @author Jeff
     * @Date 2019/6/14 11:32
     * @return
     */
    public function batch_update_abnormal($update_arr)
    {
        $this->purchase_db->trans_begin();
        $this->purchase_db->update_batch($this->table_name, $update_arr,'defective_id');

        //根據採購單號和sku更新採購單明細表，異常處理狀態
        foreach ($update_arr as $item) {
            if (empty($item['pur_number'])) continue;
            if(!empty($item['sku'])){
                $this->purchase_db->where(['purchase_number' => $item['pur_number'], 'sku' => $item['sku']])
                    ->update($this->table_purchase_order_items_name, ['abnormal_flag' => 1]);
            }else{
                $this->purchase_db->where(['purchase_number' => $item['pur_number']])
                    ->update($this->table_purchase_order_items_name, ['abnormal_flag_no_sku' => 1]);
            }
        }

        if ($this->purchase_db->trans_status() === FALSE)
        {
            $this->purchase_db->trans_rollback();
            $this->error_msg.='批量更新操作失败';
        }
        else
        {
            $this->purchase_db->trans_commit();
            $this->success=true;
        }
        return [
            'success'=>$this->success,
            'error_msg'=>$this->error_msg
        ];
    }

    /**
     * 处理时效（秒）
     * @author jeff
     * @date 2019/1/24 18:37
     * @param int $deal_used
     * @return mixed|string
     */
    public static function get_deal_used_cn($deal_used)
    {
        return round(ceil(($deal_used/3600) * 100) / 100, 1);
    }

    /**
     * @desc 获取异常单操作日志
     * @author jeff
     * @parame  $defective_id
     * @Date 2019-02-01 10:01:00
     * @return array()
     **/
    public function get_abnormal_operator_log($defective_id,$limit,$offset){
        $list=$this->purchase_db->select('*')->from('operator_log')
            ->where('record_type',$this->table_name)
            ->where('record_number',$defective_id)
            ->order_by('operate_time','desc')
            ->limit($limit,$offset)
            ->get()->result_array();
        if(!empty($list)){
            $new_data=[];
            foreach($list as $key => $val){
                $new_data[]=[
                    'operator'=>$val['operator'],
                    'operate_time'=>$val['operate_time'],
                    'operate_type'=>$val['content'],
                    'operate_detail'=>$val['content_detail']
                ];
            }

            //退货信息
            $excep_list=$this->purchase_db->select('*')->from('pur_excep_return_info')
                ->where('excep_number',$defective_id)
                ->order_by('create_time','desc')
                ->limit($limit,$offset)
                ->get()->result_array();
            if (!empty($excep_list)){
                foreach ($excep_list as $key => $value){
                    $new_data[]=[
                        'operator'=>$value['return_user'],
                        'operate_time'=>$value['return_time'],
                        'operate_type'=>'仓库更新采购异常单退货记录信息',
                        'operate_detail'=>"仓库退货时间:{$value['return_time']},退货物流单号:{$value['express_no']}"
                    ];
                }
            }

            return $new_data;

        }else{
            return false;
        }
    }

    public function get_sum_data()
    {
        $field='n.id';

        $today_create_db = $this->purchase_db->select($field)->from($this->table_name.' as n')
            ->where('create_time >=',date('Y-m-d 00:00:00'))
            ->where('create_time <=',date('Y-m-d 23:59:59'));
        $today_create = $today_create_db->count_all_results();//当日新增

        $today_handler_db = $this->purchase_db->select($field)->from($this->table_name.' as n')
            ->where('handler_time >=',date('Y-m-d 00:00:00'))
            ->where('handler_time <=',date('Y-m-d 23:59:59'));
        $today_handler = $today_handler_db->count_all_results();//当日处理

        $this_week_create_db = $this->purchase_db->select($field)->from($this->table_name.' as n')
            ->where('create_time >=',date('Y-m-d 00:00:00',strtotime('-1 sunday', time() ) ) )
            ->where('create_time <=',date('Y-m-d 23:59:59',strtotime("+0 week Saturday")));
        $this_week_create = $this_week_create_db->count_all_results();//本周新增

        $this_week_handler_db = $this->purchase_db->select($field)->from($this->table_name.' as n')
            ->where('handler_time >=',date('Y-m-d 00:00:00',strtotime('-1 sunday', time() ) ) )
            ->where('handler_time <=',date('Y-m-d 23:59:59',strtotime("+0 week Saturday")));
        $this_week_handler = $this_week_handler_db->count_all_results();//本周处理

        $return['today_create']      = $today_create;
        $return['today_handler']     = $today_handler;
        $return['this_week_create']  = $this_week_create;
        $return['this_week_handler'] = $this_week_handler;

        return $return;
    }

    /**
     * 添加异常备注
     * @param defective_id
     * @param reject_reason
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function add_abnormal_note($defective_id,$abnormal_note){
        $order_info=$this->get_one_abnormal($defective_id);
        if($order_info){

                $edit_data=[
                    'note'=>$abnormal_note,
                    'add_note_person'=>getActiveUserName(),
                    'add_note_time'=>date('Y-m-d H:i:s'),
                ];
                $this->purchase_db->trans_begin();
                try{
                    $edit_result=$this->purchase_db->where('defective_id',$defective_id)->update($this->table_name,$edit_data);
                    if (empty($edit_result)) throw new Exception("添加失败");

                    $this->purchase_db->trans_commit();
                    $this->success=true;
                }catch(Exception $e){
                    $this->purchase_db->trans_rollback();
                    $this->error_msg.='操作失败';
                }


        }else{
            $this->error_msg.='此异常单号不存在';
        }
        return [
            'success'=>$this->success,
            'error_msg'=>$this->error_msg
        ];
    }
    
}