<?php
/**
 * 产品信息修改表
 * User: Jaxton
 * Date: 2018/01/24 
 */
class Product_mod_audit_model extends Purchase_model {
    protected $table_name   = 'product_update_log';// 数据表名称

    private $success=false;
	private $error_msg='';
	private $success_msg='';

    public function __construct(){
        parent::__construct();
        $this->load->model('user/purchase_user_model');
        $this->load->model('purchase/reduced_edition_model');
        $this->load->model('purchase_suggest/purchase_suggest_model');
        $this->load->model('product/product_model');
        $this->load->model('product/Alternative_suppliers_model');
        $this->load->model('product/product_line', 'product_line_model');
        $this->load->helper(['user', 'status_product']);
        $this->load->model('user/User_group_model');
        $this->load->model('Supplier_model','Supplier_model',false,'supplier');
        $this->load->model('Message_model');
        $this->load->library('mongo_db');
        $this->load->helper('common');
        $this->load->library('Monolog');
        $this->monolog = new Monolog(['channel'=>'product_mod_audit_model']);

    }

    /**
     * 推送数据写入MONDGDB
     * @params datas  array   数据
     * @author:luxu
     * @time:2021年2月20号
     **/

    private function _push_product($datas=[]){

        return $this->mongo_db->insert('pushproductdata', $datas);
    }

    /**
    * 获取审核数据列表
    * @author Jaxton 2019/01/24
    * @param $params
    * @param $limit
    * @param $offset
    * @return array
    */
    public function get_product_list($params,$limit,$offset,$field='*',$page=1,$export=false,$reduced= False,$desc='DESC',$flase = NULL)
    {

        $scree_datas = [];
        if( (isset($params['scree_time_start']) && !empty($params['scree_time_start'])) ||
            (isset($params['scree_time_end']) && !empty($params['scree_time_end']))
         ){

            $sql = " SELECT sku,MAX(estimate_time) AS estimate_time FROM pur_product_scree WHERE status=".PRODUCT_SCREE_STATUS_END." GROUP BY sku HAVING";
            $sql .= " MAX(estimate_time)>='".$params['scree_time_start']."'";
            $sql .= " AND MAX(estimate_time)<='".$params['scree_time_end']."'";
            $scree_datas = $this->purchase_db->query($sql)->result_array();
            if(!empty($scree_datas)){
                $scree_datas = array_column($scree_datas,"sku");
            }

        }


        $db_query = $this->purchase_db;
        $logs_id = array();
        $category_all_ids = [];
        if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {
            $category_all_ids = $this->product_line_model->get_all_category($params['product_line_id']);
        }
        $db_query->select($field)->from($this->table_name . ' a')
            ->join('product b', 'a.sku=b.sku', 'left');
        if (NULL == $flase) {
            if (isset($params['sku']) && !empty($params['sku'])) {
                $sku = query_string_to_array($params['sku']);
                if (count($sku) == 1 && false == $reduced) {  //单个sku时使用模糊搜索
                    $sku_list = $this->product_model->get_search_sku_list_from_mongodb($params['sku']);
                    if ($sku_list !== false) {
                        if ($sku_list) {
                            if (count($sku_list) > 500) {
                                $return['code'] = false;
                                $return['message'] = 'SKU模糊查询数据过多，请输入更多字符';
                                return $return;
                            } else {
                                $this->purchase_db->where_in('a.sku', $sku_list);
                            }
                        } else {
                            $this->purchase_db->where('1=0');
                        }
                    } else {// Mongodb加载失败时使用 MySQL模糊查询
                        $this->purchase_db->like('a.sku', $params['sku'], 'both');
                    }
                } else {
                    $this->purchase_db->where_in('a.sku', $sku);
                }
            }
        } else if ($flase == 'reduced_api') {
            $this->purchase_db->where('a.sku', $params['sku']);
        }

        if( isset($params['create_time_start']) && !empty($params['create_time_start'])){

            $this->purchase_db->where('a.create_time>=',$params['create_time_start']);
        }

        if( isset($params['create_time_end']) && !empty($params['create_time_end'])){

            $this->purchase_db->where('a.create_time<=',$params['create_time_end']);
        }

        if (isset($params['product_line_id']) && !empty($params['product_line_id'])) {

            $children_id = $category_all_ids;
            $children_ids = explode(",", $children_id);
            $children_ids = array_filter($children_ids);
            if (empty($children_ids)) {
                $children_ids = $params['product_line_id'];
            }
            $this->purchase_db->where_in('b.product_line_id', $children_ids);

            unset($params['product_line_id']);
        }

        if(!empty($scree_datas)){

            if(count($scree_datas)>2000){
                $scree_datas_arr = array_chunk($scree_datas,2000);
                $this->purchase_db->group_start();
                foreach($scree_datas_arr as $scree_data_arr_key=>$scree_data_value){
                    $sku_list_str = "'" . implode("','", $scree_data_value) . "'";
                    $this->purchase_db->or_where("a.sku IN({$sku_list_str})");
                }
                $this->purchase_db->group_end();
            }else{

                $this->purchase_db->where_in("a.sku",$scree_datas);
            }
        }

        if( (isset($params['scree_time_start']) && !empty($params['scree_time_start'])) ||
            (isset($params['scree_time_end']) && !empty($params['scree_time_end']))
        ){

            if(empty($scree_datas)){

                $this->purchase_db->where("a.sku",1);
            }
        }

        if (isset($params['is_audit_type']) && !empty($params['is_audit_type']) && $params['is_audit_type'] == 1) {

            $this->purchase_db->where('a.audit_status!=', 3)->where('a.audit_status!=', 4);
        }

        if (isset($params['reason']) && !empty($params['reason'])) {

            $reasons = array_map(function ($reason) {

                return sprintf("'%s'", $reason);
            }, $params['reason']);
            $reasons = implode(",", $reasons);
            $this->purchase_db->join("( SELECT log_id FROM pur_log_reason WHERE reason IN ({$reasons}) GROUP BY log_id  ) AS pur_log_reason ", "pur_log_reason.log_id=a.id");
        }

        if( isset($params['create_time_start']) && !empty($params['create_time_start'])){

            $this->purchase_db->where("a.create_time>=",$params['create_time_start']);
        }


        if( isset($params['create_time_end']) && !empty($params['create_time_end'])){

            $this->purchase_db->where("a.create_time<=",$params['create_time_start']);
        }




        if (isset($params['create_user_id']) && !empty($params['create_user_id']) && !empty($params['create_user_name'])) {

            if (is_array($params['create_user_id']) && is_array($params['create_user_name'])) {
                $name = [];
                foreach ($params['create_user_name'] as $create_user_name) {
                    if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($create_user_name), $arr)) {
                        $documentary_name_flag = $arr[0];
                        $name[] =$documentary_name_flag;
                    }
                }
                $db_query->where("(a.create_user_id IN (" . implode(",", $params['create_user_id']) . ")");

                if(!empty($name)){
                    foreach($name as $nameValue){

                        $db_query->or_like("a.create_user_name",$nameValue);
                    }
                    $db_query->where("1=1)");
                }

            } else {

            $name = NULL;
            if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($params['create_user_name']), $arr)) {
                $documentary_name_flag = $arr[0];
                $name = $documentary_name_flag;
            }
            $db_query->where("(a.create_user_id={$params['create_user_id']}")->or_like("a.create_user_name", $name)->where("1=1)");
          }
        }
        if( isset($params['is_purchaseing']) && !empty($params['is_purchaseing']))
        {
            $db_query->where('a.new_is_purchasing',$params['is_purchaseing']);
        }


        if(isset($params['group_ids']) && !empty($params['group_ids'])){

            $db_query->where_in('a.create_user_id',$params['groupdatas']);
        }




        if(isset($params['new_supplier_code']) && !empty($params['new_supplier_code'])){
            $db_query->where('a.new_supplier_code',$params['new_supplier_code']);
        }
        if(isset($params['old_supplier_code']) && !empty($params['old_supplier_code'])){
            $db_query->where('a.old_supplier_code',$params['old_supplier_code']);
        }

        if(isset($params['is_sample']) && is_numeric($params['is_sample'])){
            $db_query->where('a.is_sample',$params['is_sample']);
        }

        if(isset($params['sample_check_result']) && !empty($params['sample_check_result'])){
            $db_query->where('a.sample_check_result',$params['sample_check_result']);
        }
        if(isset($params['type']) && !empty($params['type'])){
            $db_query->where('a.type',$params['type']);
        }
        if(isset($params['apply_department']) && !empty($params['apply_department'])){
            $db_query->where('a.apply_department',$params['apply_department']);
        }

        if( isset($params['is_type']) && !empty($params['is_type']))
        {
            // 涨价

            if( in_array(1,$params['is_type']))
            {
                $db_query->where('a.new_supplier_price>a.old_supplier_price');
              
            }

            //降价

            if( in_array(2,$params['is_type']))
            {

                $db_query->where("a.new_supplier_price<a.old_supplier_price");

            }

            //修改开票点

            if( in_array(3,$params['is_type']))
            {

                $db_query->where("a.old_ticketed_point!=a.new_ticketed_point");

            }

            //修改采购链接
            if( in_array(4,$params['is_type']))
            {

                $db_query->where("a.old_product_link!=a.new_product_link");

            }
            //修改供应商
            if( in_array(5,$params['is_type']))
            {
                $db_query->where("a.old_supplier_code!=a.new_supplier_code");

            }
            //修改起订量
            if( in_array(6,$params['is_type']))
            {

                $db_query->where("(a.old_starting_qty!=a.new_starting_qty")->or_where("a.old_starting_qty_unit!=a.new_starting_qty_unit")->or_where("old_ali_ratio_out!=new_ali_ratio_out)");

            }

            //修改票面税率
            if( in_array(7,$params['is_type']))
            {

                $db_query->where("new_coupon_rate!=old_coupon_rate");

            }

            // 修改交期

            if( in_array(8,$params['is_type'])){

                $db_query->where("new_devliy!=old_devliy");
            }

            // 是否定制
            if( in_array(9,$params['is_type'])){

                $db_query->where("new_is_customized!=old_is_customized");
            }

            // 是否包邮

            if( in_array(10,$params['is_type'])){

                $db_query->where("is_new_shipping!=is_old_shipping");
            }
            // 修改货源状态
            if( in_array(11,$params['is_type'])){

                $db_query->where("old_supply_status!=new_supply_status");
            }

        }
        if(isset($params['audit_status']) && !empty($params['audit_status'])){
            if(is_array($params['audit_status'])){
                $db_query->where_in('a.audit_status',$params['audit_status']);
            }else{
                $db_query->where('a.audit_status',$params['audit_status']);
            }
        }
        if(isset($params['integrate_status']) && !empty($params['integrate_status'])){
            $db_query->where('a.integrate_status',$params['integrate_status']);
        }
        if(isset($params['old_type']) and $params['old_type']!==''){
            $this->purchase_db->where('a.old_type', $params['old_type']);
        }

        if(isset($params['create_time_start']) && !empty($params['create_time_start'])){
            $db_query->where('a.create_time >=',$params['create_time_start']);
        }
        if(isset($params['create_time_end']) && !empty($params['create_time_end'])){
            $db_query->where('a.create_time <=',$params['create_time_end']);
        }
        if(isset($params['audit_time_start']) && !empty($params['audit_time_start'])){
            $db_query->where('a.audit_time >=',$params['audit_time_start']);
        }
        if(isset($params['audit_time_end']) && !empty($params['audit_time_end'])){
            $db_query->where('a.audit_time <=',$params['audit_time_end']);
        }

        if(isset($params['contrast_price']) && !empty($params['contrast_price'])){
             $db_query->where('a.old_supplier_price !=a.new_supplier_price');
        }

        if( isset($params['product_change']) && !empty($params['product_change']) ) {

            if( $params['product_change'] == 1) {
                $db_query->where('a.new_supplier_price>a.old_supplier_price');
            }

            if( $params['product_change'] == 2 ) {

                $db_query->where('a.new_supplier_price<a.old_supplier_price');
            }
            if( $params['product_change'] == 3 ) {

                $db_query->where('a.new_supplier_price=a.old_supplier_price');
            }
        }

        if(isset($params['id']) && !empty($params['id'])){
            if(is_array($params['id'])){
                $db_query->where_in('a.id',$params['id']);
            }else{
                $db_query->where('a.id',$params['id']);
            }
        }

        //供应商搜索
        if(isset($params['supplier']) && !empty($params['supplier'])){
            $db_query->group_start();
            if(is_array($params['supplier'])){
                $db_query->where_in('a.old_supplier_code',$params['supplier']);
                $db_query->or_where_in('a.new_supplier_code',$params['supplier']);
            }else{
                $db_query->where('a.old_supplier_code',$params['supplier']);
                $db_query->or_where('a.new_supplier_code',$params['supplier']);
            }

            $db_query->group_end();
        }

        // 降价比例查询
        if( (isset($params['start_ratio']) && $params['start_ratio']!=NULL ) || (isset($params['end_ratio']) && $params['end_ratio'] != NULL )){

            $db_query->where("a.old_supplier_price!=a.new_supplier_price");
            if(isset($params['start_ratio']) && $params['start_ratio']!=NULL){

                $start_ratio = $params['start_ratio']/100;
                $db_query->where("((a.new_supplier_price-a.old_supplier_price)/a.old_supplier_price)>=",$start_ratio);
            }

            if(isset($params['end_ratio']) && $params['end_ratio']!=NULL){

                $end_ratio = $params['end_ratio']/100;
                $db_query->where("((a.new_supplier_price-a.old_supplier_price)/a.old_supplier_price)<=",$end_ratio);
            }

        }

        $clone_db = clone($db_query);
        $total=$db_query->count_all_results();//符合当前查询条件的总记录数  
        $db_query=$clone_db;
        if($export){//导出不需要分页查询
//            $result=$db_query->order_by('a.create_time','DESC')->get()->result_array();
            $result = [];
        }else{
            $result=$db_query->order_by('a.id',$desc)->limit($limit,$offset)->get()->result_array();
        }

        if(!empty($result)){
            if(!empty($result)){

                $buyerNames = array_unique(array_column($result,'create_user_name'));
                $buyerNames = array_map(function($data){
                    return sprintf("'%s'",$data);
                },$buyerNames);
            }
            $buyerName = $this->User_group_model->getNameGroupMessage($buyerNames);
            $buyerName = array_column($buyerName,NULL,'user_name');
            $screeSkus = array_column($result,"sku");


            foreach ($result as $key => $value) {

                $screeDatas = $this->purchase_db->from("product_scree")->where_in("sku",$value['sku'])->where("status",PRODUCT_SCREE_STATUS_END)
                    ->select("sku,MAX(estimate_time) AS estimate_time")->group_by("sku")
                    ->get()->result_array();

                if(!empty($screeDatas)){
                    $screeDatas = array_column($screeDatas,NULL,"sku");
                }
                $result[$key]['groupName'] = isset($buyerName[$value['create_user_name']]) ? $buyerName[$value['create_user_name']]['group_name'] : '';
                $result[$key]['scree_time_ch'] = isset($screeDatas[$value['sku']])?$screeDatas[$value['sku']]['estimate_time']:'';
                $result[$key]['supply_status_ch'] = '';

                if($value['supply_status'] == 1){

                    $result[$key]['supply_status_ch'] = "正常";
                }

                if($value['supply_status'] == 2){

                    $result[$key]['supply_status_ch'] = "停产";
                }

                if($value['supply_status'] == 3){

                    $result[$key]['supply_status_ch'] = "断货";
                }
            }
        }
        return [
            'data_list' => $result,
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit
            ] 
        ];
    }

    /**
    * 格式化数据列表
    * @author Jaxton 2019/01/24
    * @param $data
    * @return array
    */
    public function formart_product_list($data){
        if(!empty($data)){
            foreach($data as $key => $val){
                $audit_status = getProductModStatus($val['audit_status']);
                if(!empty($audit_status) && !in_array($audit_status,['审核通过','审核驳回'])){
                    $audit_status = "待".$audit_status;
                }
                isset($data[$key]['audit_status']) and $data[$key]['audit_status'] = $audit_status;
                isset($data[$key]['is_sample']) and $data[$key]['is_sample'] = getProductIsSample($val['is_sample']);
                isset($data[$key]['sample_check_result']) and $data[$key]['sample_check_result'] = getProductSampleCheckResult($val['sample_check_result']);
                isset($data[$key]['product_status']) and $data[$key]['product_status'] = getProductStatus($val['product_status']);
                isset($data[$key]['integrate_status']) and $data[$key]['integrate_status'] = getProductIntegrateStatus($val['integrate_status']);

                // 相关图片处理
                isset($data[$key]['related_pictures']) and $data[$key]['related_pictures'] = explode(',', $val['related_pictures']);
                // 相关缩略图处理
                isset($data[$key]['related_thumbnails']) and $data[$key]['related_thumbnails'] = explode(',', $val['related_thumbnails']);
                $string_data = [];

                $data[$key]['product_img_url'] = isset($val['product_img_url']) ? erp_sku_img_sku($val['product_img_url']):'';
                $data[$key]['product_img_url_thumbnails'] = isset($val['product_thumb_url']) ? erp_sku_img_sku_thumbnail($val['product_thumb_url']): '';
                $data[$key]['product_thumb_url'] = $data[$key]['product_img_url_thumbnails'];

                if( $val['old_supplier_price'] > $val['new_supplier_price'])
                {
                    $string_data[] = "降价";
                }else if($val['old_supplier_price'] < $val['new_supplier_price']){
                    $string_data[] = "涨价";
                }

                if( $val['old_supplier_code'] != $val['new_supplier_code']){
                    $string_data[] = "修改供应商";
                }

                if( $val['old_ticketed_point'] != $val['new_ticketed_point']){

                    $string_data[] = "修改开票点";
                }

                if( $val['old_product_link'] != $val['new_product_link']){
                    $string_data[] ="修改采购链接";
                }
                if( $val['old_starting_qty'] != $val['new_starting_qty'])
                {
                    $string_data[]= "修改最小起订量";
                }

                if( $val['old_starting_qty_unit'] != $val['new_starting_qty_unit'])
                {
                    $string_data[] = "修改最小起订量单位";
                }

                if( $val['old_ali_ratio_own'] != $val['new_ali_ratio_own'])
                {
                    $string_data[] = "修改单位对应关系（内部)";
                }

                if( $val['old_ali_ratio_out'] != $val['new_ali_ratio_out'])
                {
                    $string_data[] = "修改单位对应关系（外部）";
                }
                if( $val['new_coupon_rate'] != $val['old_coupon_rate'])
                {
                    $string_data[] = "修改票面税率";
                }

                if( $val['old_is_purchasing'] == 1)
                {
                    $data[$key]['old_is_purchasing_ch'] = "否";
                }else{
                    $data[$key]['old_is_purchasing_ch'] = "是";
                }
                if( $val['new_is_purchasing'] == 1)
                {
                    $data[$key]['new_is_purchasing_ch'] = "否";
                }else{
                    $data[$key]['new_is_purchasing_ch'] = "是";
                }
                //货源状态(1.正常,2.停产,3.断货,10:停产找货中)
                if( $val['old_supply_status'] == 1){

                    $data[$key]['old_supply_status_ch'] = "正常";
                }
                if( $val['old_supply_status'] == 2){

                    $data[$key]['old_supply_status_ch'] = "停产";
                }
                if( $val['old_supply_status'] == 3){

                    $data[$key]['old_supply_status_ch'] = "断货";
                }
                if( $val['old_supply_status'] == 10){

                    $data[$key]['old_supply_status_ch'] = "停产找货中";
                }

                if( $val['new_supply_status'] == 1){

                    $data[$key]['new_supply_status_ch'] = "正常";
                }
                if( $val['new_supply_status'] == 2){

                    $data[$key]['new_supply_status_ch'] = "停产";
                }
                if( $val['new_supply_status'] == 3){

                    $data[$key]['old_supply_status_ch'] = "断货";
                }
                if( $val['new_supply_status'] == 10){

                    $data[$key]['new_supply_status_ch'] = "停产找货中";
                }

                if( $val['new_is_customized'] == 1)
                {
                    $data[$key]['new_is_customized_ch'] = "是";
                }else{
                    $data[$key]['new_is_customized_ch'] = "否";
                }

                if( $val['new_long_delivery'] == 1){

                    $data[$key]['new_long_delivery'] = "否";
                }else{
                    $data[$key]['new_long_delivery'] = "是";
                }

                if( $val['old_long_delivery'] == 1){

                    $data[$key]['old_long_delivery'] = "否";
                }else{
                    $data[$key]['old_long_delivery'] = "是";
                }

                if( $val['old_is_customized'] == 1)
                {
                    $data[$key]['old_is_customized_ch'] = "是";
                }else{
                    $data[$key]['old_is_customized_ch'] = "否";
                }

                if( $val['maintain_ticketed_point'] ==1 && $val['new_ticketed_point'] == 0.000)
                {
                    $data[$key]['new_ticketed_point'] = NULL;
                }

                if( $val['is_new_shipping'] == 2){

                    $data[$key]['is_new_shipping_ch'] = "不包邮";
                }else{
                    $data[$key]['is_new_shipping_ch'] = "包邮";
                }
                if( $val['is_old_shipping'] == 1){

                    $data[$key]['is_old_shipping_ch'] = "包邮";
                }else{
                    $data[$key]['is_old_shipping_ch'] = "不包邮";
                }
                //(修改前价格-修改后价格)/修改前价格*100%;

                if($val['old_supplier_price'] != $val['new_supplier_price'] && $val['old_supplier_price']!=0 && $val['new_supplier_price']!=0) {
                    $Cuttheprice = ($val['new_supplier_price'] - $val['old_supplier_price']) / $val['old_supplier_price'];

                    $Cuttheprice = round(($Cuttheprice * 100), 2) . "%";
                }else{
                    $Cuttheprice =  "0%";
                }

                $data[$key]['cuttheprice'] = $Cuttheprice;

                $data[$key]['update_content'] = $string_data;

                $old_settlement_method = $this->Supplier_model->get_supplier_payment($val['old_supplier_code']);
                $data[$key]['old_settlement_method'] = $old_settlement_method;

                $new_settlement_method = $this->Supplier_model->get_supplier_payment($val['new_supplier_code']);
                $data[$key]['new_settlement_method'] = $new_settlement_method;

                // 新供应商交货天数、交货率”

               // $nowMonth = date("Y-m");

                $new_supplierAvg = $this->purchase_db->from("supplier_avg_day")->where("supplier_code",$val['new_supplier_code'])
                    //->where("statis_month",$nowMonth)
                        ->order_by("statis_month DESC")
                    ->get()->result_array();
                if(!empty($new_supplierAvg)){

                    $new_supplierAvg = $new_supplierAvg[0];
                }
                $data[$key]['new_ds_day_avg'] = isset($new_supplierAvg['ds_day_avg'])?$new_supplierAvg['ds_day_avg']:'-';
                $data[$key]['new_os_day_avg'] = isset($new_supplierAvg['os_day_avg'])?$new_supplierAvg['os_day_avg']:'-';
                $data[$key]['new_ds_deliverrate'] = isset($new_supplierAvg['ds_deliverrate'])?round(($new_supplierAvg['ds_deliverrate']*100),4)."%":'-';
                $data[$key]['new_os_deliverrate'] = isset($new_supplierAvg['os_deliverrate'])?round(($new_supplierAvg['os_deliverrate']*100),4)."%":'-';

                $old_supplierAvg = $this->purchase_db->from("supplier_avg_day ")->where("supplier_code",$val['old_supplier_code'])
                    ->order_by("statis_month DESC")
                    ->get()->result_array();

                if(!empty($old_supplierAvg)){

                    $old_supplierAvg = $old_supplierAvg[0];
                }

                $data[$key]['old_ds_day_avg'] = isset($old_supplierAvg['ds_day_avg'])?$old_supplierAvg['ds_day_avg']:'-';
                $data[$key]['old_os_day_avg'] = isset($old_supplierAvg['os_day_avg'])?$old_supplierAvg['os_day_avg']:'-';
                $data[$key]['old_ds_deliverrate'] = isset($old_supplierAvg['ds_deliverrate'])?round($old_supplierAvg['ds_deliverrate']*100,4)."%":'-';
                $data[$key]['old_os_deliverrate'] = isset($old_supplierAvg['os_deliverrate'])?round($old_supplierAvg['os_deliverrate']*100,4)."%":'-';

            }
        }
        return $data;
    }

    /**
    * 获取下拉列表
	* @param $type
    * @return array
    * @author Jaxton 2019/01/21
    */
    public function get_down_list($type=null){
        $user_list = $this->purchase_user_model->get_user_all_list();
        $user_list = array_column($user_list,'name','id');
        $reason_list = $this->product_model->get_product_reason();
        $status_list = getProductModStatus();
        if(!empty($status_list)){

           foreach( $status_list as $key=>$value){

               if( !empty($value) && !in_array($value,['审核通过','审核驳回','请选择'])){
                   $status_list[$key]="待".$value;
               }
           }
        }
        $product_line_list = $this->product_line_model->get_product_line_list(0);
        //$drop_down_box['product_line_id'] =array_column($product_line_list, 'linelist_cn_name','product_line_id');
    	$list=[
    		'is_sample_list' => getProductIsSampleDownBox(),
	    	'sample_check_result_list' => getProductSampleCheckResult(),
	    	'audit_status_list' => $status_list,
	    	'user_list' => $user_list,
            'product_price_change' => getProductPriceChange(),
            'is_purchasing' =>  [['status'=>2,'message'=>'是'],['status'=>1,'message'=>'否']],
            'is_type' => [['status'=>1,'message'=>'涨价'],['status'=>2,'message'=>'降价'],['status'=>3,'message'=>'修改开票点'],
                ['status'=>4,'message'=>'修改采购链接'],['status'=>5,'message'=>'修改供应商'],['status'=>6,'message'=>'修改起订量'],
                ['status'=>7,'message'=>'修改票面税率'],['status'=>8,'message'=>'修改交期'],['status'=>9,'message'=>'修改是否定制'],
                ['status' =>10,'message'=>'修改是否包邮'],['status' =>11,'message'=>'修改货源状态']
                ],
            'reason_list' => $reason_list,
            'product_line_id' => array_column($product_line_list, 'linelist_cn_name','product_line_id')
    	];

    	return isset($type) ? $list[$type] : $list;
    }

    /**
    * 根据条件获取数据
    * @param $where
    * @return array
    * @author Jaxton 2019/01/25
    */
    public function get_info_by_map($where){
    	if(empty($where)) $where='1=1';
    	return $this->purchase_db->select('*')->from($this->table_name)
    	->where($where)
    	->get()->result_array();
    }

    /**
    * 根据id获取数据
    * @param $ids
    * @return array
    * @author Jaxton 2019/01/25
    */
    public function get_info_by_ids($ids){
    	return $this->purchase_db->select('*')->from($this->table_name)
    	->where_in('id',$ids)
    	->get()->result_array();
    }

    /**
     *更新采购单表交期，是否超长交期
     * 需求： 28241 采购单页面,增加显示字段和筛选项:"是否超长交期""轨迹状态"
     * 2.增加显示字段:"交期",取值=采购单审核通过时,跟据SKU抓取产品管理列表的"交期",保存数值,后续不在跟随产品列表的数据更新,若数据为0或空,则显示"-"
     * 3.增加显示字段:"是否超长交期",取值=采购单审核通过时,跟据SKU抓取产品管理列表的"是否超长交期",保存数值,后续不在跟随产品列表
     * @param $sku   string   产品SKU
     *        $new_log_delivery tinyint 是否超长交期
     *        $new_devliy  int  交期
     **/
    public function updateOrderDeliver($sku,$new_long_delivery,$new_devliy){

        $result = $this->purchase_db->from("purchase_order as orders")->join("purchase_order_items as items","orders.purchase_number=items.purchase_number","LEFT")
            ->where_in("orders.purchase_order_status",[PURCHASE_ORDER_STATUS_WAITING_QUOTE,PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT])
            ->where("items.sku",$sku)->select("items.id as itemsid")->get()->result_array();
        if(!empty($result)){

            $itemsIds = array_column($result,"itemsid");
            $update =[

                'is_long_delivery' => $new_long_delivery,
                'new_devliy' => $new_devliy
            ];

            $this->purchase_db->where_in("id",$itemsIds)->update('purchase_order_items',$update);
        }
    }

    /**
       获取用户角色信息
     **/
    private function getUserRole(){
        $uid = $this->input->get_post("uid");
        $authData = $this->rediss->getData($uid);
        if(isset($authData['role_data']) && !empty($authData['role_data'])){

            $return_data = array_map(function($data){

                return $data['en_name'];

            },$authData['role_data']);

            return array_filter($return_data);
        }
        return NULL;

    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }

    /**
    * 审核
	* @param $ids
	* @param $audit_result
	* @param $remark
    * @return array
    * @author Jaxton 2019/01/25
    */
    public function product_audit($id, $audit_result, $remark, $tflag = false)
    {
        $demandflag = False;
        if(isset($_GET['demand_flag']) && $_GET['demand_flag'] == true){

            $demandflag = True;
        }
    	if (!empty($id)) {
            $id = query_string_to_array($id);
            $log_file = APPPATH . 'logs/product_audit_save_'.date('Ymd').'.txt';
            //echo $log_file;die();
            file_put_contents($log_file, $this->get_microtime() . "** start {$id} change_status on model......\n", FILE_APPEND);
            $order_info = $this->get_info_by_ids($id);
            if (!empty($order_info)) {
                $this->purchase_db->trans_begin();
                $flag = 0;
                try {
                    $this->load->config('api_config', FALSE, TRUE);
                    $erp_system = $this->config->item('erp_system');
                    $this->load->model('ali/ali_product_model');
                    $proccess_orders = json_decode(PRODUCT_ALL_CONTENT_PROCCESS,True);
                    $proccess_orders_key = array_column($proccess_orders,NULL,"audit_flag");
                    $userNowRole = $this->getUserRole();

                    $pushProductData = $pushSupplierData= $PurchasePriceData =[];
                    foreach ($order_info as $key => $val) {
                        if (false == $tflag) {

                             if( isset($proccess_orders_key[$val['audit_status']]) && !empty($proccess_orders_key[$val['audit_status']]))
                             {
                                //  SKU 当前审核流程信息
                                 $skuNowProccess = $proccess_orders_key[$val['audit_status']];
                                 // 判断用户审核角色和SKU 当前审核处于角色
                                 // 如果是供应链总监可以审核所有

                                 if( !in_array($skuNowProccess['nameflag'],$userNowRole) && !in_array("supplier_director",$userNowRole)){
                                     throw new Exception("sku:".$val['sku'].",当前位于".$skuNowProccess['name']."审核状态");
                                 }
                                 $edit_data = [
                                     'audit_user_id' => getActiveUserId(),
                                     'audit_user_name' => getActiveUserName(),
                                     'audit_remark' => $remark
                                 ];
                                 $edit_data['audit_time'] = date('Y-m-d H:i:s');
                                 // 审核通过
                                 if( $audit_result == 1){

                                     $audit_level = json_decode( $val['audit_level'],True);
                                     if(empty($audit_level) && $val['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS){

                                         $edit_data['audit_status'] = PRODUCT_UPDATE_LIST_AUDIT_PASS;
                                     }
                                     // 获取下一个审核流程
                                     if(!empty($audit_level)) {
                                         if(count($audit_level) == 1){
                                             $edit_data['audit_status'] = PRODUCT_UPDATE_LIST_AUDIT_PASS;
                                             $edit_data['audit_level'] = NULL;
                                         }else {

                                             $nextProccess = array_shift($audit_level);
                                             if( $nextProccess['audit_flag'] == $val['audit_status']){

                                                 $nextProccess = reset($audit_level);

                                             }
                                             $edit_data['audit_status'] = $nextProccess['audit_flag'];
                                             $edit_data['audit_level'] = json_encode($audit_level);
                                         }
                                     }
                                     // 如果是供应商总监审核就全部通过
                                     if(in_array("supplier_director",$userNowRole)){

                                         $edit_data['audit_status'] = PRODUCT_UPDATE_LIST_AUDIT_PASS;
                                         $edit_data['audit_level'] = NULL;
                                     }else{

                                         if( $edit_data['audit_level'] != NULL) {
                                             // 如果是采购经理审核，那么同时可以审核采购副经理，采购主管
                                             $newedit_data = json_decode($edit_data['audit_level'],true);
                                             if (in_array("purchasing_manager", $userNowRole)) {

                                                 if (isset($newedit_data['deputy_manager']) && !empty($newedit_data['deputy_manager'])){

                                                     unset($newedit_data['deputy_manager']);
                                                 }

                                                 if (isset($newedit_data['purchasing_manager']) && !empty($newedit_data['purchasing_manager'])){

                                                     unset($newedit_data['purchasing_manager']);
                                                 }

                                                 if (isset($newedit_data['executive_director']) && !empty($newedit_data['executive_director'])){

                                                     unset($newedit_data['executive_director']);
                                                 }
                                             }

                                             if( in_array("deputy_manager",$userNowRole)){

                                                 if (isset($newedit_data['deputy_manager']) && !empty($newedit_data['deputy_manager'])){

                                                     unset($newedit_data['deputy_manager']);
                                                 }

                                                 if (isset($newedit_data['executive_director']) && !empty($newedit_data['executive_director'])){

                                                     unset($newedit_data['executive_director']);
                                                 }
                                             }

                                             if( !empty($newedit_data)) {
                                                 $new_status = reset($newedit_data);
                                                 $edit_data['audit_status'] = $new_status['audit_flag'];
                                                 $edit_data['audit_level']  = json_encode($newedit_data);
                                             }else{

                                                 $edit_data['audit_status'] = PRODUCT_UPDATE_LIST_AUDIT_PASS;
                                                 $edit_data['audit_level']  = NULL;
                                             }

                                         }
                                     }

                                 }else{
                                     // 驳回
                                     $edit_data['audit_status'] = PRODUCT_UPDATE_LIST_REJECT;


                                     $this->Message_model->AcceptMessage('product',['data'=>[$val['id']],'message'=>$remark,'user'=>getActiveUserName(),'type'=>'待'.$skuNowProccess['name']]);
                                 }

                             }else{

                                 if($val['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS){
                                     throw new Exception($val['sku'] . "已经审核完成");
                                 }else {
                                     throw new Exception($val['sku'] . "审核流程错误");
                                 }
                             }
                        }else{
                                $edit_data['audit_status'] = PRODUCT_UPDATE_LIST_AUDIT_PASS;

                            }

                            $this->purchase_db->where('id', $val['id'])->update($this->table_name, $edit_data);
                        // 添加审核日志 pur_product_audit_log
                        //'audit_user_name' => getActiveUserName(),
                        // 'audit_remark' => $remark

//                        $audit_log = [
//                             'username' => getActiveUserName(),
//                             'time'     => date("Y-m-d H:i:s",time()),
//                             'type'     => isset($skuNowProccess['name'])?$skuNowProccess['name']:'',
//                             'status'   => $audit_result,
//                             'content'  =>!empty($remark)?$remark:'',
//                             'sku'      => $val['sku'],
//                             'log_id'   =>$val['id']
//                        ];
//                        $this->purchase_db->insert("pur_product_audit_log",$audit_log);
                            //同步更新产品表审核状态
                        $this->purchase_db->where('sku',$val['sku'])->update('product',['audit_status'=>$edit_data['audit_status']]);

                        if($edit_data['audit_status'] == PRODUCT_UPDATE_LIST_AUDIT_PASS){
                            file_put_contents($log_file, $this->get_microtime() . "  审核通过,操作过1......\n", FILE_APPEND);
                            // 链接变更后，是否关联1688自动变成否，系统自动取消关联1688
                            if($val['old_product_link']!=$val['new_product_link']){
                                $this->ali_product_model->remove_relate_ali_sku(null,$val['sku']);
                            }

                            //审核通过之后插入降本数据
                            if ($val['old_supplier_price'] != $val['new_supplier_price']) {
                                $this->reduced_edition_model->insert_sku_reduced_data($val);
                            }
                            //降价审核通过,解锁备货单
                            if ($val['new_supplier_price'] < $val['old_supplier_price']) {
                                $this->purchase_suggest_model->unlock_suggest_from_product_reduce($val['sku']);
                            }

                            if( $val['new_supplier_code'] != $val['old_supplier_code']){
                            //    print_r($val);die();
                                // 插入备选供应商表
                                $this->Alternative_suppliers_model->product_audit_alternative($val,'product_audit');

                                $pushalternativeDatas = [

                                    'sku' => $val['sku'],
                                    'supplier_name' => $val['new_supplier_name'], //  供应商名称
                                    'supplier_code' => $val['new_supplier_code'], // 供应商CODE
                                    'purchase_price' => $val['new_supplier_price'], // 未税单价
                                    'starting_qty_unit' => $val['new_starting_qty_unit'], // 最小起订量
                                    'is_shipping' => $val['is_new_shipping'], // 是否包邮
                                    'delivery' => $val['new_devliy'], // 交期
                                    'url' => $val['new_product_link'], // 采购连接
                                ];

                                $this->Alternative_suppliers_model->pushMq($pushalternativeDatas);
                            }

                            // 货源状态更新
                            if( $val['old_supply_status'] != $val['new_supply_status']){
                                $this->product_model->_push_rabbitmq_data($val['sku'],$val['old_supply_status'], $val['new_supply_status']);
                                $this->product_model->update_erp_purchase_supplier_status($val['sku'], $val['new_supply_status']);
                                $this->product_model->update_product_supply_status($val['sku'],$val['new_supply_status'],$val['old_supply_status']);
                                // 货源状态修改锁定需求单，不让其自动合单
                                $this->purchase_db->where_in("demand_status",[DEMAND_SKU_STATUS_CONFIR,SUGGEST_STATUS_NOT_FINISH])
                                    ->where("sku",$val['sku'])
                                    ->update("purchase_demand",['is_abnormal_lock'=>1]);
                            }
                            // 更新最新起订量
                            $this->product_model->change_min_order_qty($val['sku'], $val['new_starting_qty'], $val['new_starting_qty_unit'], $val['old_starting_qty'], $val['old_starting_qty_unit']);
                            //更新最新起订量成功后，推入消息队列

                            if( $val['new_starting_qty'] != $val['old_starting_qty'] || $val['new_starting_qty_unit'] != $val['old_starting_qty_unit']) {
                                $this->_push_rabbitmq([
                                    'sku' => $val['sku'],
                                    'new_sku_order_qty' => $val['new_starting_qty'],
                                    'new_sku_order_unit' => $val['new_starting_qty_unit'],
                                    'old_sku_order_qty' => $val['old_starting_qty'],
                                    'old_sku_order_unit' => $val['old_starting_qty_unit']
                                ]);
                            }
                            file_put_contents($log_file, $this->get_microtime() . "  审核通过,操作过1结束......\n", FILE_APPEND);
                            //修改产品表
                            file_put_contents($log_file, $this->get_microtime() . "  审核通过修备货单表开始......\n", FILE_APPEND);
                            $this->update_product_data($val, $edit_data['audit_status']);
                            $this->purchase_suggest_model->change_purchase_ticketed_type($val['sku'], $val['maintain_ticketed_point']);
                            $this->purchase_suggest_model->change_suggest_purchasing($val['sku'], $val['new_is_purchasing']);//同步产品信息是否待采购
                            //财务审核通过后，修改采购单价格
                            //计算是否退税
                            $product_info = $this->product_model->get_product_info($val['sku']);
                               
                            $is_drawback = $this->product_model->getProductIsBackTaxNew($val['new_supplier_code'],$product_info['tax_rate'], $val['new_ticketed_point']);

                            $change_data['new_supplier_price'] = $val['new_supplier_price'];
                            $change_data['new_supplier_code'] = $val['new_supplier_code'];
                            $change_data['new_supplier_name'] = $val['new_supplier_name'];
                            $change_data['new_ticketed_point'] = $val['new_ticketed_point'];
                            $change_data['new_is_customized'] = $val['new_is_customized'];
                            $change_data['is_drawback'] = $is_drawback;
                            $old_data['old_supplier_code'] = $val['old_supplier_code'];
                            $old_data['old_supplier_price'] = $val['old_supplier_price'];
                            $old_data['old_is_customized'] = $val['old_is_customized'];

                            if($val['new_supplier_price'] != $val['old_supplier_price'] || $val['new_supplier_code'] != $val['old_supplier_code']

                            || $val['new_ticketed_point'] != $val['old_ticketed_point'] || $val['old_is_customized'] != $val['new_is_customized']) {
                                $this->purchase_suggest_model->change_suggest_purchase_price($val['sku'], $change_data, $old_data);

                             //   echo "hello....";
                                $this->purchase_suggest_model->change_demand_purchase_price($val['sku'],$change_data,$old_data);
                            }

                            if( $demandflag == True){

                                $this->purchase_suggest_model->change_demand_purchase_price($val['sku'],$change_data,$old_data);

                            }
                            file_put_contents($log_file, $this->get_microtime() . "  审核通过修备货单表结束......\n", FILE_APPEND);
                            //支付方式/结算方式

                            if($val['old_supplier_code'] != $val['new_supplier_code']) {
                                $this->purchase_suggest_model->change_suggest_pay_type($val['sku'], $val['new_supplier_code']);
                                $this->purchase_suggest_model->change_demand_pay_type($val['sku'],$val['new_supplier_code']);
                                $this->ali_product_model->verify_supplier_equal($val['sku']);// 刷新供应商是否一致
                            }
                            if($demandflag == true){
                                $this->purchase_suggest_model->change_demand_pay_type($val['sku'],$val['new_supplier_code']);
                            }

                            /*
                             *  更新采购单表交期，是否超长交期
                             * 2.增加显示字段:"交期",取值=采购单审核通过时,
                             * 跟据SKU抓取产品管理列表的"交期",
                             * 保存数值,后续不在跟随产品列表的数据更新,若数据为0或空,则显示"-"
                               3.增加显示字段:"是否超长交期",取值=采购单审核通过时,
                               跟据SKU抓取产品管理列表的"是否超长交期",保存数值,后续不在跟随产品列表
                            */
                            file_put_contents($log_file, $this->get_microtime() . "  审核通过修修改采购单超长交期并且数据推送ERP开始......\n", FILE_APPEND);
                            if($val['new_long_delivery'] != $val['old_long_delivery']) {
                                $this->updateOrderDeliver($val['sku'], $val['new_long_delivery'], $val['new_devliy']);
                            }


                            // 数据从ERP 推送到采购系统

                            $avg_flag = $this->get_product_sku_avg($val['sku']);
                            if (False == $avg_flag) {

                                $to_erp_purchase_price_url = $erp_system['purchase_price_to_erp']."?access_token=". getOASystemAccessToken();
                                $shipCost = '0.00';
                                $send_data = array(

                                    'sku' => $val['sku'],
                                    'lastPrice' => (float)$val['new_supplier_price'],
                                    'avgPrice'  => (float)$val['new_supplier_price'],
                                    'shipCost'  => $shipCost,
                                    'newPrice'  => (float)$val['new_supplier_price']
                                 );
                                //$match_json = preg_match_all('/([^{}:,\]]"[^{}:,\]])/', json_encode($send_data,JSON_UNESCAPED_UNICODE), $matchs);
                                $json_encode_string = json_encode($send_data,JSON_UNESCAPED_UNICODE);
                                $json_encode_string = str_replace('"0.00"','0.00',$json_encode_string);
                                    $toerp['type'] = 'purchase_price_to_erp';
                                    $toerp['is_push'] = 0; // 表示还未推送
                                    $toerp['data'] = $json_encode_string;
                                    $this->_push_product($toerp);

                                file_put_contents($log_file, $this->get_microtime() . "  审核通过修修改采购单超长交期并且数据推送ERP结束......\n", FILE_APPEND);

                                    //$erp_result_data = send_http($to_erp_purchase_price_url,$json_encode_string);
//                                    $logs_mess = array(
//                                        'sku' => $val['sku'],
//                                        'to_erp_message' => json_encode($send_data),
//                                        'return_erp_message' => $erp_result_data
//                                    );
//
//                                    $this->purchase_db->insert('to_erp_message',$logs_mess);

                            }


                            // SKU 审核通过，并且修改是否代采，推送数据给产品系统
                            if( $val['new_is_purchasing'] != $val['old_is_purchasing']){

                                //$pushProducturl
                                $pushProductData[] = [

                                    'sku' => $val['sku'],
                                    'isPurchasing' => $val['new_is_purchasing']
                                ];

                            }

                            if( $val['old_supplier_code'] != $val['new_supplier_code']){

                                $pushSupplierData[] = [

                                    'sku' => $val['sku'],
                                    'purSupplierCode' => $val['new_supplier_code']
                                ];
                            }
                            if( $val['old_supplier_price'] != $val['new_supplier_price']) {
                                //审核后最新采购价推送至新产品系统
                                $PurchasePriceData[] = [
                                    'sku' => $val['sku'],
                                    'newPrice' => $val['new_supplier_price'],
                                    'shipCost' => 0,
                                    'avgPrice' => 0
                                ];
                            }
                        }
                        $this->purchase_db->where("sku",$val['sku'])->update('product',['audit_status_log'=>$edit_data['audit_status']]);
                        //存日志
                        if ( isset($PurchasePriceData) && !empty($PurchasePriceData)) {

                            $toPurchasePrictDataString = json_encode($PurchasePriceData);
                        }else{
                            $toPurchasePrictDataString = '';
                        }
                        $log_data = [
                            'id' => $val['id'],
                            'type' => $this->table_name,
                            'content' => '修改产品信息审核',
                            'detail' => '修改产品信息审核,审核状态:'.$val['audit_status'].",审核人:".getActiveUserName()."sku:".$val['sku']."修改数据：".$toPurchasePrictDataString
                        ];
                        operatorLogInsert($log_data);

                        $this->purchase_db->trans_commit();
                        $this->success = true;
                        $flag++;

                    }

                    if ( isset($PurchasePriceData) && !empty($PurchasePriceData)) {


                        $topushsupplierdata['type'] = 'purchasePriceData';
                        $topushsupplierdata['is_push'] = 0; // 表示还未推送
                        $topushsupplierdata['data'] = $PurchasePriceData;
                        $this->_push_product($topushsupplierdata);
                        //存日志
                       /* $log_data = [
                            'id' => $val['id'],
                            'type' => $this->table_name,
                            'content' => 'sku价格修改写入MONGODB',
                            'detail' => '修改数据:'.json_encode($topushsupplierdata)
                        ];
                        operatorLogInsert($log_data);*/
//                        file_put_contents($log_file, $this->get_microtime() . "  审核通过推送数据......\n", FILE_APPEND);
//                        $header = ['Content-Type: application/json'];
//                        $request_url = getConfigItemByName('api_config', 'product_system', 'yibaiProdSku-updateSkuPrice');
//                        $access_token = getOASystemAccessToken();
//                        $request_url = $request_url . '?access_token=' . $access_token;
//                        $results = getCurlData($request_url, json_encode($PurchasePriceData), 'post', $header);
//                        $results = json_decode($results, true);
//                        if (isset($results['code'])) {
//                            if ($results['code'] == 200) {
//                                $this->success = true;
//                            } else {
//                                $this->error_msg .= $results['msg'];
//                            }
//                            apiRequestLogInsert(
//                                [
//                                    'record_number' => '',
//                                    'record_type' => 'push_new_product_price',
//                                    'api_url' => $request_url,
//                                    'post_content' => json_encode($PurchasePriceData),
//                                    'response_content' => $results,
//                                    'status' => !empty($this->success) ? 1 : 0
//                                ]);
//                        } else {
//                            $this->error_msg .= isset($results['message']) ? $results['message'] : '执行出错';
//                        }
                    }
                    if(!empty($pushProductData)){

                        $topushproduct['type'] = 'updateIsPurchasingBySku';
                        $topushproduct['is_push'] = 0; // 表示还未推送
                        $topushproduct['data'] = $pushProductData;
                        $this->_push_product($topushproduct);
//                        $javas_system = $this->config->item('product_system');
//                        $pushProducturl = $javas_system['updateIsPurchasingBySku']."?access_token=". getOASystemAccessToken();
//                        $result = send_http($pushProducturl,json_encode($pushProductData));
                    }
                    if( !empty($pushSupplierData)){
                        $topushsupplierdata['type'] = 'updatePurSupplierCode';
                        $topushsupplierdata['is_push'] = 0; // 表示还未推送
                        $topushsupplierdata['data'] = $pushSupplierData;
                        $this->_push_product($topushsupplierdata);

//                        $javas_system = $this->config->item('product_system');
//                        $pushProducturl = $javas_system['updatePurSupplierCode']."?access_token=". getOASystemAccessToken();
//                        $result = send_http($pushProducturl,json_encode($pushSupplierData));
//                        $pushSupplierDataLogs = [
//
//                            'id' => '1',
//                            'type' =>'pur_product',
//                            'content' => '修改SKU供应商信息推送到产品系统日志',
//                            'detail' => json_encode($pushSupplierData)."--".json_encode($result)
//                        ];
//                        operatorLogInsert($pushSupplierDataLogs);
                    }
                } catch (Exception $e) {
                    $this->purchase_db->trans_rollback();
                    $this->error_msg .= $e->getMessage();
                }
                if (!$flag) {
                    $this->error_msg .= '没有可审核的数据';
                }
            } else {
                $this->error_msg .= '未获取到相关信息';
            }
        } else {
            $this->error_msg .= '缺少参数-ID';
        }
        file_put_contents($log_file, $this->get_microtime() . "  审核结束......\n", FILE_APPEND);
        return [
            'success' => $this->success,
            'error_msg' => $this->error_msg
        ];
    }

    /**
    * 修改产品表信息
    * @param $data
    * @return array
    * @author Jaxton 2019/02/16
    */
    public function update_product_data($data,$product_status){
        if(!empty($data['new_supplier_price'])){
            $product_data['purchase_price']=$data['new_supplier_price'];
            $product_data['last_price']=$data['new_supplier_price'];
        }
        if(!empty($data['new_supplier_code'])){
            $product_data['supplier_code']=$data['new_supplier_code'];
        }
        if(!empty($data['new_supplier_name'])){
            $product_data['supplier_name']=$data['new_supplier_name'];
        }
        if(!empty($data['new_ticketed_point'])){
            $product_data['ticketed_point']=$data['new_ticketed_point'];
        }
        if(!empty($data['new_product_link'])){
            $product_data['product_cn_link']=$data['new_product_link'];
        }
        if(isset($data['is_sample']) and $data['is_sample']!==''){
            $product_data['is_sample']=$data['is_sample'];
        }
        if(!empty($data['new_ali_ratio_own'])){
            $product_data['ali_ratio_own']=$data['new_ali_ratio_own'];
        }
        if(!empty($data['new_ali_ratio_out'])){
            $product_data['ali_ratio_out']=$data['new_ali_ratio_out'];
        }

        if( !empty($data['new_coupon_rate'])){
            $product_data['coupon_rate']=$data['new_coupon_rate'];
        }

        if(!empty($data['new_box_size'])){
            $product_data['box_size'] = $data['new_box_size'];
        }

        if( !empty($data['new_inside_number'])){
            $product_data['inside_number'] = $data['new_inside_number'];
        }
        if( isset($data['maintain_ticketed_point']) && ($data['maintain_ticketed_point']==1 || $data['maintain_ticketed_point']==0) )
        {
            $product_data['maintain_ticketed_point'] = $data['maintain_ticketed_point'];
        }

        if( !empty($data['new_is_purchasing']))
        {
            $product_data['is_purchasing'] = $data['new_is_purchasing'];
        }

        if( !empty($data['product_volume'])){
            $product_data['product_volume'] = $data['product_volume'];
        }

        if( !empty($data['new_devliy'])){
            $product_data['devliy'] = $data['new_devliy'];
        }

        if( !empty($data['new_is_customized'])){

            $product_data['is_customized'] = $data['new_is_customized'];
        }

        if( !empty($data['new_long_delivery'])){

            $product_data['long_delivery'] = $data['new_long_delivery'];
        }
        $product_data['outer_box_volume'] = $data['outer_box_volume'];
        $product_data['product_volume'] = $data['product_volume'];

        $product_data['is_shipping'] = $data['is_new_shipping'];

        //计算是否退税
        $product_info = $this->product_model->get_product_info($data['sku']);
        $is_drawback = $this->product_model->getProductIsBackTaxNew($data['new_supplier_code'],$product_info['tax_rate'],$data['new_ticketed_point']);
        $product_data['is_drawback']=$is_drawback;
        //是否异常
        $is_abnormal = $this->check_sku_abnormal($data,$product_info['product_status']);
        $product_data['is_abnormal']=$is_abnormal;

        $product_table='product';
        $product_data['audit_status_log'] = $product_status;
        $logs = array(

            'sku' => $data['sku'],
            'param' => json_encode($product_data),
            'time' => date("Y-m-d H:i:s")
        );
        $this->purchase_db->insert('product_log',$logs);
        $this->purchase_db->where('sku',$data['sku'])->update($product_table,$product_data);

    }

    /**
    * 检测SKU是否异常
    * @param $data
    * @return array
    * @author Jaden 2019/08/07
    1.有供应商名称，但是供应商代码缺失的，是否异常=是
    2.供应商代码=供应商中文名的，默认供应商代码为空。是否异常=是
    3.sku产品状态=在售中，但是供应商名称为空的，是否异常=是
    */
    public function check_sku_abnormal($data,$product_status){
        $is_abnormal = 1;
        if(!empty($data['new_supplier_name']) and empty($data['new_supplier_code'])){
            $is_abnormal = 2;
        }
        if($data['new_supplier_name']==$data['new_supplier_code']){
            $is_abnormal = 2;
        }
        if($product_status==4 and empty($data['new_supplier_code'])){
            $is_abnormal = 2;
        }
        return $is_abnormal;

    }




    /**
    * 品控审核(产品系统)
    * @param $id
    * @return array
    * @author Jaxton 2019/01/25
    */
    public function product_control_audit($id,$audit_result){
        if($id){
            $order_info = $this->get_info_by_ids($id)[0];
            if(!empty($order_info)){
                if($order_info['audit_status']==PRODUCT_UPDATE_LIST_QUALITY_AUDIT){
                    if($audit_result==1){
                        $edit_data=[
                            'audit_status' => PRODUCT_UPDATE_LIST_AUDIT_PASS
                        ];
                    }else{
                        $edit_data=[
                            'audit_status' => PRODUCT_UPDATE_LIST_REJECT
                        ];
                    }
                    $this->purchase_db->trans_begin();
                    $edit_result = $this->purchase_db->where('id',$id)->update($this->table_name,$edit_data);
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
                }else{
                    $this->error_msg .= '当前状态不是[待品控审核]';
                }
                
            }else{
                $this->error_msg .= '未获取到相关信息';
            }
            
            return [
                'success' => $this->success,
                'error_msg' => $this->error_msg
            ];
        }
    }

    /**
     * function:判断商品SKU 是否入库
     * @param  $sku    string    商品SKU
     * @return Bool 有入库返回TRUE，没有入库返回FALSE
     **/
    public function get_product_sku_avg($sku)
    {
        $result = $this->purchase_db->from("warehouse_results_main")->where("sku",$sku)->select("id")->get()->row_array();
        if( !empty($result) )
        {
            return True;
        }
        return False;
    }

    public function get_product_log($sku) {

        $sql = " SELECT audit_user_name,old_supplier_price,new_supplier_price,create_remark,audit_time,create_time,create_user_name FROM pur_product_update_log  WHERE sku='".$sku."' AND audit_status=3 AND old_supplier_price<>new_supplier_price";
        $logs = $this->purchase_db->query($sql)->result_array();
        return $logs;
    }

    /**
     * 获取审核日志信息
     * @author :luxu
     * @time:2020/3/10
     **/
    public function getProductAuditLog($sku,$id = NULL){

        $query = $this->purchase_db->from("product_audit_log")->where("sku",$sku);
        if( NULL != $id){
            $query->where("log_id",$id);
        }

        return $query->order_by("id DESC")->get()->result_array();
    }

    /**
     * sku起订量发生变化时，推入消息队列
     * @param array $data
     */
    private function _push_rabbitmq($data)
    {
        //推入消息队列
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setExchangeName('SKU_ORDER_QTY_EX_NAME');
        $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发
        //构造存入数据
        $push_data = [
            'sku' => $data['sku'],
            'new_sku_order_qty' => $data['new_sku_order_qty'],
            'new_sku_order_unit' => $data['new_sku_order_unit'],
            'old_sku_order_qty' => $data['old_sku_order_qty'],
            'old_sku_order_unit' => $data['old_sku_order_unit'],
            'update_time' => time()
        ];
        //存入消息队列
        $mq->sendMessage($push_data);
    }

    public function headerlog(){

        $header = [
            'key4' => ['name'=>'SKU','status'=>0,'index'=>0,'val'=>['sku']], // SKU
            'key1' => ['name' => '审核状态','status'=>0,'index'=>1,'val'=>['audit_status','audit_user_name','audit_time','audit_remark']], // 产品图片
            'key2' => ['name' => '申请人/申请时间','status'=>0,'index'=>2,'val'=>['create_user_name','create_time']], // 产品名称
            'key3' => ['name' => '产品图片','status'=>0,'index'=>3,'val'=>['product_img_url','product_img_url_thumbnails','product_thumb_url']],
            'key5' => ['name' => '产品名称','status'=>0,'index'=>4,'val'=>['product_name']],
            'key6' => ['name' => '箱内数','status'=>0,'index'=>5,'val'=>['new_inside_number','old_inside_number']],
            'key7' => ['name'=>'外箱尺寸(cm)','status'=>0,'index'=>6,'val'=>['new_box_size','old_box_size']],
            'key8' => ['name'=>'产品信息','status'=>0,'index'=>7,'val'=>['product_status','product_line_name','create_time']],
            'key9' => ['name' => '系统与1688对应关系','status'=>8,'index'=>8,'val'=>['new_ali_ratio_out','old_ali_ratio_out','new_ali_ratio_own','old_ali_ratio_own']],
            'key10' => ['name'=>'最小起订量','status'=>0,'index'=>9,'val'=>['new_starting_qty','old_starting_qty','new_starting_qty_unit','old_starting_qty_unit']],
            'key11' => ['name'=>'未税单价/价格变化比例','status'=>10,'index'=>10,'val'=>['new_supplier_price','old_supplier_price','cuttheprice']],
            'key12' => ['name' => '是否包邮','status'=>0,'index'=>11,'val'=>['is_new_shipping_ch','is_old_shipping_ch']],
            'key13' => ['name' => '开票点','status'=>0,'index'=>12,'val'=>['new_ticketed_point','old_ticketed_point']],
            'key14' =>['name'=>'票面税率','status'=>0,'index'=>13,'val'=>['new_coupon_rate','old_coupon_rate']],
            'key15' => ['name'=>'供应商名称','status'=>0,'index'=>14,'val'=>['new_supplier_name','old_supplier_name']],
            'key16' => ['name'=>'结算方式','status'=>0,'index'=>15,'val'=>['new_settlement_method','old_settlement_method']],
            'key17' => ['name' =>'连接','status'=>0,'index'=>16,'val'=>['new_product_link','old_product_link']],
            'key18' => ['name' => '是否定制','status'=>0,'index'=>17,'val'=>['new_is_customized_ch','old_is_customized_ch']],
            'key19' => ['name' => '是否拿样','status'=>0,'index'=>18,'val'=>['is_sample']],
            'key20' => ['name' => '是否代采','status'=>0,'index'=>19,'val'=>['new_is_purchasing_ch','old_is_purchasing_ch']],
            'key21' => ['name' => '样品检验结果','status'=>0,'index'=>20,'val'=>['sample_check_result','sample_user_name','sample_time','sample_remark']],
            'key22' => ['name' => '交期(天)','status'=>0,'index'=>21,'val'=>['new_devliy','old_devliy']],
            'key23' => ['name' => '是否超长交期','status'=>0,'index'=>22,'val'=>['new_long_delivery','old_long_delivery']],
            'key24' => ['name' => '货源状态','status'=>0,'index'=>23,'val'=>['reason']],
            'key26' => ['name' => '申请备注','status'=>0,'index'=>24,'val'=>['create_remark']],
            'key25' => ['name'=>'修改原因','status'=>0,'index'=>25,'val'=>['reason']],
            'key27' => ['name' => '国内仓交付天数','status'=>0,'index'=>26,'val'=>['new_ds_day_avg,old_ds_day_avg']],
            'key28' => ['name' => '海外仓交付天数','status'=>0,'index'=>27,'val'=>['new_os_day_avg,old_os_day_avg']],
            'key29' => ['name' => '10天国内仓交付率','status'=>0,'index'=>29,'val'=>['new_ds_deliverrate,old_ds_deliverrate']],
            'key30' => ['name' => '40天海外仓交付率','status'=>0,'index'=>30,'val'=>['new_os_deliverrate,old_os_deliverrate']],
            'key31' =>  ['name' => '预计供货时间','status'=>0,'index'=>31,'val'=>['scree_time_ch']],
        ];
        return $header;
    }

    public function get_supplier_avg($supplier_code){

        $result = $this->purchase_db->from("supplier_avg_day")->where("supplier_code",$supplier_code)
            ->order_by("statis_month DESC")->get()->result_array();
        if(empty($result)){

            return NULL;
        }

        foreach($result as $key=>&$value){

            $months = $value['statis_month'];

            $value['months'] = date("Y-m-01", strtotime($months))."--". date("Y-m-t", strtotime($months));

            $supplierName = $this->purchase_db->from("supplier")->where("supplier_code",$value['supplier_code'])
                ->select("supplier_name")->get()->row_array();
            $value['supplier_name'] = $supplierName['supplier_name'];
            $value['ds_day_avg'] = $value['ds_day_avg'];
            $value['os_day_avg'] = $value['os_day_avg'];
            $value['ds_deliverrate'] = round($value['ds_deliverrate']*100,1)."%";
            $value['os_deliverrate'] = round($value['os_deliverrate']*100,1)."%";

        }
        return $result;
    }

}