<?php
/**
 * Created by PhpStorm.
 * sku降本模型
 * User: Jaden
 * Date: 2019/01/16 
 */
class Reduced_edition_model extends Purchase_model {

    protected $table_name   = 'sku_reduced_edition'; // 采购系统SKU降本老版本数据库
    protected $new_table_name   = 'sku_reduced_edition_new_other'; // 采购系统SKU降本新版本数据库

    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function tableName() {
        return $this->table_name;
    }
    public function get_reduced_sum($params, $offset, $limit,$field='*',$export=false) {
        $this->purchase_db->select(" COUNT(*) AS total ");
        $this->purchase_db->from($this->table_name);

        $this->purchase_db->where('original_price!=present_price');
        if(!empty($params['optimizing_user'])){
            $this->purchase_db->where('optimizing_user', $params['optimizing_user']);
        }
        if(!empty($params['price_trend'])){
            if(1==$params['price_trend']){
                $this->purchase_db->where('price_change >', 0);
            }elseif (2==$params['price_trend']) {
                $this->purchase_db->where('price_change <', 0);
            }
        }
        if(!empty($params['supplier_code'])){
            $this->purchase_db->where('supplier_code', $params['supplier_code']);
        }
        if(!empty($params['statistical_time'])){
            $this->purchase_db->where('statistical_time', $params['statistical_time']);
        }
        //统计时间
        if(!empty($params['statistical_time'])){
            $this->purchase_db->where('statistical_time', $params['statistical_time']);
        }
        //采购数量
        if(isset($params['purchase_quantity']) and $params['purchase_quantity']!==''){
            if($params['purchase_quantity']==0){
                $this->purchase_db->where('purchase_quantity=0');
            }else{
                $this->purchase_db->where('purchase_quantity!=0');
            }
        }
        //有效采购数量
        if(isset($params['effective_purchase_quantity']) and $params['effective_purchase_quantity']!==''){
            if($params['effective_purchase_quantity']==0){
                $this->purchase_db->where('effective_purchase_quantity=0');
            }else{
                $this->purchase_db->where('effective_purchase_quantity!=0');
            }
        }

        if (isset($params['sku']) and $params['sku']) {// SKU
            $sku = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('sku', $sku);
        }

        if(!empty($params['price_change_time_start']) and !empty($params['price_change_time_end'])){
            $this->purchase_db->where('price_change_time >=', $params['price_change_time_start']);
            $this->purchase_db->where('price_change_time <=', $params['price_change_time_end']);
        }

        if(!empty($params['price_change_time_start'])){
            $this->purchase_db->where('price_change_time >=', $params['price_change_time_start']);
        }

        if(!empty($params['price_change_time_end'])){
            $this->purchase_db->where('price_change_time <=', $params['price_change_time_end']);
        }

        if(!empty($params['first_calculation_time_start'])){
            $this->purchase_db->where('first_calculation_time >=', $params['first_calculation_time_start']);
        }

        if(!empty($params['first_calculation_time_end'])){
            $this->purchase_db->where('first_calculation_time <=', $params['first_calculation_time_end']);
        }


        if(!empty($params['ids'])){
            $this->purchase_db->where_in('id', explode(',', $params['ids']));
        }
        //统计总数
        $clone_db = clone($this->purchase_db);
        $query_builder_sum      = clone($this->purchase_db);// 克隆一个查询 用来做数据汇总
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数

        $huizong_arr            = $query_builder_sum->select('sum(price_change_total) as all_list_price_change_total')->get()->row_array();

        $this->purchase_db=$clone_db;

        $results = $this->purchase_db->get()->result_array();

        //echo $this->purchase_db->last_query();exit;
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
                'all_list_price_change_total' => isset($huizong_arr['all_list_price_change_total']) ? $huizong_arr['all_list_price_change_total']:0,
            ]
        ];

        return $return_data;
    }

    /**
     * 权均交期列表
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-16
     */

    public function get_reduced_list($params, $offset, $limit,$field='*',$export=false) {
        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name);

        $this->purchase_db->where('original_price!=present_price');
        if(!empty($params['optimizing_user'])){
            $this->purchase_db->where('optimizing_user', $params['optimizing_user']);
        }
        if(!empty($params['price_trend'])){
            if(1==$params['price_trend']){
                $this->purchase_db->where('price_change >', 0);
            }elseif (2==$params['price_trend']) {
                $this->purchase_db->where('price_change <', 0);
            }
        }
        if(!empty($params['supplier_code'])){
            $this->purchase_db->where('supplier_code', $params['supplier_code']);
        }
        if(!empty($params['statistical_time'])){
            $this->purchase_db->where('statistical_time', $params['statistical_time']);
        }
        //统计时间
        if(!empty($params['statistical_time'])){
            $this->purchase_db->where('statistical_time', $params['statistical_time']);
        }
        //采购数量
        if(isset($params['purchase_quantity']) and $params['purchase_quantity']!==''){
            if($params['purchase_quantity']==0){
                $this->purchase_db->where('purchase_quantity=0');
            }else{
                $this->purchase_db->where('purchase_quantity!=0');
            }
        }
        //有效采购数量
        if(isset($params['effective_purchase_quantity']) and $params['effective_purchase_quantity']!==''){
            if($params['effective_purchase_quantity']==0){
                $this->purchase_db->where('effective_purchase_quantity=0');
            }else{
                $this->purchase_db->where('effective_purchase_quantity!=0');
            }
        }

        if (isset($params['sku']) and $params['sku']) {// SKU
            $sku = query_string_to_array($params['sku']);
            $this->purchase_db->where_in('sku', $sku);
        }

        if(!empty($params['price_change_time_start']) and !empty($params['price_change_time_end'])){
            $this->purchase_db->where('price_change_time >=', $params['price_change_time_start']);
            $this->purchase_db->where('price_change_time <=', $params['price_change_time_end']);
        }

        if(!empty($params['price_change_time_start'])){
            $this->purchase_db->where('price_change_time >=', $params['price_change_time_start']);
        }

        if(!empty($params['price_change_time_end'])){
            $this->purchase_db->where('price_change_time <=', $params['price_change_time_end']);
        }

        if(!empty($params['first_calculation_time_start'])){
            $this->purchase_db->where('first_calculation_time >=', $params['first_calculation_time_start']);
        }

        if(!empty($params['first_calculation_time_end'])){
            $this->purchase_db->where('first_calculation_time <=', $params['first_calculation_time_end']);
        }


        if(!empty($params['ids'])){
            $this->purchase_db->where_in('id', explode(',', $params['ids']));
        }
        //统计总数
        $clone_db = clone($this->purchase_db);
        $query_builder_sum      = clone($this->purchase_db);// 克隆一个查询 用来做数据汇总
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        
        $huizong_arr            = $query_builder_sum->select('sum(price_change_total) as all_list_price_change_total')->get()->row_array();    

        $this->purchase_db=$clone_db;
        if($export){//导出不要分页查询
            $results = $this->purchase_db->order_by('statistical_time','desc')->get()->result_array();
        }else{
            $results = $this->purchase_db->order_by('statistical_time','desc')->limit($limit, $offset)->get()->result_array();    
        }
        //echo $this->purchase_db->last_query();exit; 
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total_count,
                'limit'     => $limit,
                'all_list_price_change_total' => isset($huizong_arr['all_list_price_change_total']) ? $huizong_arr['all_list_price_change_total']:0,
            ]
        ];

        return $return_data;
    }

    /**
     * 下拉框 ==> 供应商 和 优化人
     * @author liwuxue
     * @date 2019/2/12 11:51
     * @param
     * @return array
     * @throws Exception
     */
    public function get_drop_down_box()
    {
        $resp = [
            'optimizing_user' => [],//优化人
            'supplier_code' => [],//供应商
        ];
        $res = $this->purchase_db->select("optimizing_user,supplier_code,supplier_name")->get($this->table_name)->result_array();
        if (!empty($res)) {
            $resp['optimizing_user'] = array_filter(array_unique(array_column($res, 'optimizing_user', 'optimizing_user')));
            $resp['supplier_code'] = array_column($res, 'supplier_name', 'supplier_code');
        }
        return $resp;
    }

    /**
     * 新SKU 降本下拉框 ==> 供应商 和 优化人
     * @author liwuxue
     * @date 2019/2/12 11:51
     * @param
     * @return array
     * @throws Exception
     */
    public function new_get_drop_down_box()
    {
        $resp = [
            'optimizing_user' => []//优化人
        ];
        $result = $this->purchase_db->select("optimizing_user as person_name")->group_by("optimizing_user")->get($this->new_table_name)->result_array();
        return $result;
    }




    //根据sku查询时间段有没有升价记录
    public function get_sku_is_rise($sku,$starttime,$endtime){
        if(empty($sku) || empty($starttime) || empty($endtime)){
            return [];
        }
        $this->purchase_db->select('sku,old_supplier_price,new_supplier_price,audit_time');
        $this->purchase_db->from('product_update_log');
        $this->purchase_db->where('sku',$sku);
        $this->purchase_db->where('audit_time >=',$starttime);
        $this->purchase_db->where('audit_time <=', $endtime);
        $this->purchase_db->where('(new_supplier_price-old_supplier_price)>0');
        $results = $this->purchase_db->order_by('audit_time asc')->limit(1)->get()->row_array();
        if(!empty($results)){
            return $results;
        }else{
            return [];
        }
    }

    //根据sku查询时间段有没有降价记录
    public function get_sku_is_reduction($sku,$starttime,$endtime){
        if(empty($sku) || empty($starttime) || empty($endtime)){
            return [];
        }
        $this->purchase_db->select('sku,old_supplier_price,new_supplier_price,audit_time');
        $this->purchase_db->from('product_update_log');
        $this->purchase_db->where('sku',$sku);
        $this->purchase_db->where('audit_time >=',$starttime);
        $this->purchase_db->where('audit_time <=', $endtime);
        $this->purchase_db->where('(new_supplier_price-old_supplier_price)<0');
        $results = $this->purchase_db->order_by('audit_time asc')->limit(1)->get()->row_array();
        if(!empty($results)){
            return $results;
        }else{
            return [];
        }
    }



    /**
    * 插入数据
    * @param $data
    * @return array
    * @author Jaden 2019/02/16
    */
    public function insert_sku_reduced_data($data){       
        if(!empty($data['create_user_name'])){
            $sku_reduced_edition_data['optimizing_user']=$data['create_user_name'];
        }
        if(!empty($data['sku'])){
            $sku_reduced_edition_data['sku']=$data['sku'];
        }
        if(!empty($data['product_name'])){
            $sku_reduced_edition_data['product_name']=$data['product_name'];
        }
        if(!empty($data['new_supplier_name'])){
            $sku_reduced_edition_data['supplier_name']=$data['new_supplier_name'];
        }
        if(!empty($data['new_supplier_code'])){
            $sku_reduced_edition_data['supplier_code']=$data['new_supplier_code'];
        }
        $sku_reduced_edition_data['price_change_time']=date('Y-m-d H:i:s');
        //$sku_reduced_edition_data['first_calculation_time']=date('Y-m-d H:i:s');
        if(!empty($data['old_supplier_price'])){
            $sku_reduced_edition_data['original_price']=$data['old_supplier_price'];
        }
        if(!empty($data['new_supplier_price'])){
            $sku_reduced_edition_data['present_price']=$data['new_supplier_price'];
        }
        if(!empty($data['old_supplier_price']) and !empty($data['new_supplier_price'])){
           $sku_reduced_edition_data['price_change']=$data['new_supplier_price']-$data['old_supplier_price']; 
        }
        if(!empty($data['id'])){
            $sku_reduced_edition_data['app_id']=$data['id'];
        }
        $this->purchase_db->insert($this->table_name,$sku_reduced_edition_data);

        if(!empty($data['old_supplier_price'])){
            $sku_reduced_edition_data['product_price']=$data['old_supplier_price'];
        }
            $this->load->model('purchase/Reduced_model','reduced');

            $six_time_min_price = $this->reduced->get_six_minimum_price($data['sku'], $sku_reduced_edition_data['price_change_time']);
            $sku_reduced_edition_data['six_minimum_price'] = $six_time_min_price;
            $res = $this->purchase_db->insert($this->new_table_name, $sku_reduced_edition_data);

        $person_code = NULL;
        if (preg_match('/[\x{4e00}-\x{9fa5}]+/u', trim($data['create_user_name']), $arr)) {
            $person_code = str_replace($arr[0],'',$data['create_user_name']);
        }


//        $empty_detail_message = array(
//            'product_name' => $data['product_name'],
//            'supplier_name' => $data['new_supplier_name'],
//            'supplier_code' => $data['new_supplier_code'],
//            'person_name'   =>$data['create_user_name'],
//            'person_code'  => $person_code,
//            'sku' => $data['sku'],
//            'price_change_time' =>$sku_reduced_edition_data['price_change_time'],
//            'new_price' =>$data['new_supplier_price'],
//            'old_price' => $data['old_supplier_price'],
//            'range_price' =>$sku_reduced_edition_data['price_change'],
//            'optimizing_user' => $data['create_user_name'],
//            'job_number' => $person_code,
//            'app_id'=>$data['id'],
//            'is_default' =>1,
//            'six_minimum_price'=>$six_time_min_price,
//        );
//
//        $this->purchase_db->insert("purchase_reduced_detail",$empty_detail_message);

    }
    //删除数据
    public function delete_reduced_edition($starttime,$endtime){
        if(empty($starttime) || empty($endtime)) {
            return false;
        }    
        $result = $this->purchase_db->where('price_change_time >=',$starttime)->where('price_change_time <=', $endtime)->where('old_type=0')->delete($this->table_name);
        return $result;
    }
    /**
     * 插入数据
     * @author Jaden
     *  @param array $insert_data  插入数据表的数据
     * @return bool
     * 2019-1-16
     */
    public function insert_reduced_batch_all($insert_data){
        if(empty($insert_data)) {
            return false;
        }    
        $result = $this->purchase_db->insert_batch($this->table_name,$insert_data);
        return $result;
    }

    //根据查询时间段查询有降价记录sku列表
    public function get_sku_list_is_reduction($supplier_codes,$starttime,$endtime){
        if(empty($starttime) || empty($endtime)){
            return [];
        }

        $supplier_codes_string = format_query_string($supplier_codes);//将数组转换为字符串

        $query_db = $this->purchase_db;

        $sql = 'select sku,old_supplier_price,new_supplier_price,audit_time from pur_product_update_log where 
              new_supplier_code in('.$supplier_codes_string.')  and 
              audit_time >="'.$starttime.'" and audit_time <="'.$endtime.'"
               and (new_supplier_price-old_supplier_price)<0 order by audit_time asc';

        $results = $query_db->query($sql)->result_array();

        if(!empty($results)){
            return $results;
        }else{
            return [];
        }
    }


    /**
     * 获取采购系统SKU 降本新版本优化数据
     * @author: luxu
     * @param:  $clientData    array   客户端传入参数
     * @return  Object
     **/
    public function get_reduced_list_data($clientData =array(),$limit,$offset)
    {

        $query = $this->purchase_db->from("sku_reduced_edition_new_other");
        // 优化人
        if( isset($clientData['optimizing_user']) && !empty($clientData['optimizing_user']))
        {
            $query->where('optimizing_user',$clientData['optimizing_user']);
        }
        /**
           *  客户端传入价格变化趋势
         **/
        if( isset($clientData['trend']) && !empty($clientData['trend']))
        {
            // 如果价格变化趋势传入1 表示 涨价 ， 2表示降价
            $trendFlag = ($clientData['trend'] == 1)? 'price_change>':'price_change<';
            $query->where($trendFlag,0);
        }

        /**
           *  客户端传入供应商信息
         **/
        if( isset($clientData['supplier_code']) && !empty($clientData['supplier_code']))
        {
            $query->where("supplier_code",$clientData['supplier_code']);
        }

        /***
           *  客户端传入SKU 信息
         **/
        if( isset($clientData['sku']) && !empty($clientData['sku']))
        {
            $query->where_in('sku',$clientData['sku']);
        }

        /**
          * 价格变化开始时间
         **/
        if( isset($clientData['price_change_start_time']) && !empty($clientData['price_change_start_time']))
        {
            $query->where('price_change_time>=',$clientData['price_change_time']);
        }

        /**
         * 价格变化结束时间
         **/
        if( isset($clientData['price_change_end_time']) && !empty($clientData['price_change_end_time']))
        {
            $query->where('price_change_time<=',$clientData['price_change_time']);
        }

        /**
          * 有效入库数量
         **/
        if( isset($clientData['warehous_quantity']) && !empty($clientData['warehous_quantity']))
        {
            // 传入1表示 不等于0，反之表示等于0
            if( $clientData['warehous_quantity'] == 1 )
            {
                $query->where('warehous_quantity>',0);
            }else{
                $query->where('warehous_quantity=',0);
            }
        }else{
            $query->where('warehous_quantity>',0);
        }

        /**
          * 采购数量
         **/
        if( isset($clientData['purchase_quantity']) && !empty($clientData['purchase_quantity']) )
        {
            // 传入1表示 不等于0，反之表示等于0
            if( $clientData['purchase_quantity'] == 1 )
            {
                $query->where('purchase_quantity>',0);
            }else{
                $query->where('purchase_quantity=',0);
            }
        }else{
            $query->where('purchase_quantity>',0);
        }

        /**
         * 首次下单时间
         **/
        if( isset($clientData['first_calculation_start_time']) && !empty($clientData['first_calculation_start_time']) )
        {
            $query->where("first_calculation_time>=",$clientData['first_calculation_start_time']);
        }

        if( isset($clientData['first_calculation_end_time']) && !empty($clientData['first_calculation_end_time']) )
        {
            $query->where("first_calculation_time<=",$clientData['first_calculation_end_time']);
        }

        if( isset($clientData['is_end']) && !empty($clientData['is_end']) )
        {
            if( $clientData['is_end'] == 1)
            {
                $query->where('is_end',0);
            }
            if( $clientData['is_end'] == 2)
            {
                $query->where('is_end',1);
            }
        }

        $total_query = clone $query;
        $result_list = $query->limit($limit,$offset)->get()->result_array();
        $total_sum = $total_query->count_all_results();
        return array(

            'list'=>$result_list,
            'total' => $total_sum
        );

    }

    /**
      * function:获取SKU 降本信息
     **/
    public function get_reduced_config()
    {
        return $this->purchase_db->from("sku_reduced_config")->get()->result_array();
    }

    /**
      * function:更新SKU 降本信息
     **/
    public function update_reduced_config($clientData,$username)
    {
        if( !isset($clientData['id']) || empty($clientData['id']))
        {
           return False;
        }

        $id = $clientData['id'];
        unset($clientData['id']);
        unset($clientData['uid']);
        $message = $this->purchase_db->from("sku_reduced_config")->where("id",$id)->get()->row_array();
        $result = $this->purchase_db->where("id",$id)->update('sku_reduced_config',$clientData);
        if( $result ){

            $logs = array(

                'before_content' => json_encode($message),
                'after_content'  => json_encode($clientData),
                'create_user_name' => $username,
                'create_time'     => date("Y-m-d H:i:s",time())
            );

            $this->purchase_db->insert('sku_reduced_config_log',$logs);
            return True;
        }

        return False;
    }

    public function get_reduced_log()
    {

        $result = $this->purchase_db->from("sku_reduced_config_log")->get()->result_array();
        return $result;
    }

    /**
     * function：获取采购单的取消数量
     * @param : $skus   array   SKU
     *          $purchase_number  array  采购单
     * @return array
     **/
    private function get_purchase_cancel($skus,$purchase_number)
    {
        $query = $this->purchase_db->from("purchase_order_cancel_detail")->select(" sum(cancel_ctq) AS number,sku,purchase_number")->where_in("sku", $skus)->where_in("purchase_number", $purchase_number);
        $cancal_result = $query->where("is_push=1")->group_by("sku,purchase_number")->get()->result_array();
        $cancal = array();
        if (!empty($cancal_result)) {

            foreach ($cancal_result as $key => $value) {
                $cancal[$value['sku'] . $value['purchase_number']] = $value['number'];
            }
        }

    }

    /**
     * 获取降本商品SKU信息
     * @param:  $params     array   传入参数
     *          $offset     int     每页多少条数据
     *          $page       int     第几页
     *          $field      string  获取字段信息
     * @return  array
     **/
    public function get_product_list( $params,$page,$offset,$field='a.*,b.sku as product_sku,b.product_img_url,b.product_thumb_url' )
    {
        return $this->product_mod_audit_model->get_product_list($params, $offset,$page,$field);
    }


    /**
     * 获取SKU 降本优化记录信息
     * @param   $clientData      array   HTTP 客户端传入参数
     * @return  array
     **/

    public function get_reduced_data_list( $params,$field='new.*,main.instock_qty',$limit,$offset,$export=False )
    {
        $this->load->model('Purchase/Reduced_orders','reduced_model');
        $reduced_config = $this->reduced_model->is_reduced_config();
        $this->purchase_db->select($field);
        $this->purchase_db->from("sku_reduced_edition_new_other AS new");
        $this->purchase_db->join("purchase_reduced_detail_other AS detail"," new.sku=detail.sku AND new.app_id=detail.app_id",'LEFT');
        $this->purchase_db->join("warehouse_results_main AS  main","detail.sku=main.sku AND detail.purchase_number=main.purchase_number","LEFT");

        $this->purchase_db->where('new.product_price!=new.present_price');
        // 优化人,传入姓名+工号
        if( isset($params['optimizing_user']) &&!empty($params['optimizing_user'])){

            if( is_array($params['optimizing_user'])) {
                $this->purchase_db->where_in('new.optimizing_user', $params['optimizing_user']);
            }else{
                $this->purchase_db->where('new.optimizing_user', $params['optimizing_user']);
            }
        }

        // 价格变化趋势， 1表示涨价，2表示降价
        if( isset($params['price_trend']) && !empty($params['price_trend'])){
            if(1==$params['price_trend']){
                $this->purchase_db->where('new.price_change >', 0);
            }elseif (2==$params['price_trend']) {
                $this->purchase_db->where('new.price_change <', 0);
            }
        }

        // 供应商CODE
        if( isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('new.supplier_code', $params['supplier_code']);
        }

        //价格变化时间,开始时间
        if(isset($params['statistical_start_time']) && !empty($params['statistical_start_time'])){
            $this->purchase_db->where('new.statistical_time>=', $params['statistical_start_time']);
        }
        //价格变化时间,结束时间
        if(isset($params['statistical_end_time']) && !empty($params['statistical_end_time'])){
            $this->purchase_db->where('new.statistical_time<=', $params['statistical_end_time']);
        }

        //采购数量
        if(isset($params['purchase_quantity']) and $params['purchase_quantity']!==''){
            if($params['purchase_quantity']==1){
                $this->purchase_db->where('new.purchase_quantity=0');
            }else{
                $this->purchase_db->where('new.purchase_quantity!=0');
            }
        }
        //有效采购数量
        if(isset($params['effective_purchase_quantity']) and $params['effective_purchase_quantity']!==''){
            if($params['effective_purchase_quantity']==1){
                $this->purchase_db->where('new.effective_purchase_quantity=0');
            }else{
                $this->purchase_db->where('new.effective_purchase_quantity!=0');
            }
        }
        // SKU
        if (isset($params['sku']) and $params['sku']) {// SKU
            //$this->purchase_db->where_in('sku', $params['sku']);
            if (count($params['sku']) == 1) {  //单个sku时使用模糊搜索
                $this->purchase_db->like('new.sku', $params['sku'][0],'both');
            } else {
                $this->purchase_db->where_in('new.sku', $params['sku']);
            }
        }
        // 价格变化开始时间
        if( isset( $params['price_change_time_start'] ) && !empty($params['price_change_time_start'])){
            $this->purchase_db->where('new.price_change_time >=', $params['price_change_time_start']);
        }
        // 价格变化结束时间
        if( isset($params['price_change_time_end']) &&!empty($params['price_change_time_end'])){
            $this->purchase_db->where('new.price_change_time <=', $params['price_change_time_end']);
        }
        //首次计算时间开始时间
        if( isset($params['first_calculation_time_start']) && !empty($params['first_calculation_time_start'])){
            $this->purchase_db->where('new.first_calculation_time >=', $params['first_calculation_time_start']);
        }
        // 首次计算时间结束时间
        if( isset($params['first_calculation_time_end']) && !empty($params['first_calculation_time_end'])){
            $this->purchase_db->where('new.first_calculation_time <=', $params['first_calculation_time_end']);
        }

        //是否结束统计

        if( isset($params['is_end']) && !empty($params['is_end']) )
        {
            // 等于1表示 结束统计
            $is_end = ( $params['is_end'] == 1)?1:0;
            $this->purchase_db->where('new.is_end =', $is_end);
        }

        //有效入库数量 warehous_quantity

        if( isset($params['warehous_quantity']) && !empty($params['warehous_quantity']) )
        {
            // 1表示等于0,2表示不等于0
            if($params['warehous_quantity']==1){
                $this->purchase_db->where('main.instock_qty IS NULL');
            }else{
                $this->purchase_db->where('main.instock_qty>0');
            }
        }


        if(!empty($params['ids'])){
            $this->purchase_db->where_in('new.id', explode(',', $params['ids']));
        }
        $clone_db = clone($this->purchase_db);
        $sum_purchase = clone $this->purchase_db;
        $total_count = $clone_db->group_by("new.app_id")->distinct()->get()->result_array();
        if($export){//导出不要分页查询
            $results = $this->purchase_db->order_by('new.statistical_time','desc')->limit( $offset,$limit)->group_by("new.app_id")->distinct()->get()->result_array();
        }else{
            $results = $this->purchase_db->order_by('new.statistical_time','desc')->group_by("new.app_id")->get()->result_array();

        }
        if( $export )
        {
            $price_change_1 = 0;
            if( !empty($results) )
            {
                $warehouse_data = [];
                foreach( $results as $key=>$value )
                {
                    if( !isset($warehouse_data[$value['app_id']]))
                    {
                        $warehouse_data[$value['app_id']] = [];
                    }
                    $warehouse_data[$value['app_id']][] = $value['instock_qty'];

                }

                foreach( $results as $key=>&$value )
                {

                    //价格变化金额2=有效采购数量*价格变化幅度 
                    $value['price_change_2'] = sprintf("%.2f",$value['effective_purchase_quantity']*($value['present_price']-$value['product_price']));
                    // 实际入库数量:总入库数量-总报损数量
//                    $value['sctual_warehouse_number'] = $value['warehous_quantity'] - $value['breakage_number'];
                    $value['sctual_warehouse_number'] = $value['warehous_quantity'];
                    if( $value['sctual_warehouse_number'] <0 )
                    {
                        $value['sctual_warehouse_number'] =0;
                    }
                    // 降本比例
                    if($value['product_price'] != '0.000') {
                        $value['reduced_proportion'] = (sprintf("%.2f", ($value['present_price'] - $value['product_price']) / $value['product_price']))*100 . "%";
                    }else{
                        $value['reduced_proportion'] = NULL;
                    }
                    //，0表示未结束 1表示结束

                    //$is_end_flag = $this->reduced_model->get_purchase_detail($value['app_id']);
                    if( $value['is_end'] == 1)
                    {
                        $value['is_end'] = "是";
                    }else if($value['is_end'] == 0) {
                        $value['is_end'] = "否";
                    }
                    if( empty($value['deadline_time']) || $value['deadline_time'] == '0000-00-00 00:00:00' ) {

                        $value['deadline_time'] = NULL;
                    }
                    $effective_warehouse_quantity = isset( $warehouse_data[$value['app_id']] )? $warehouse_data[$value['app_id']]:0;

                    if( is_array($effective_warehouse_quantity)) {
                        $effective_warehouse_quantity = array_sum($effective_warehouse_quantity);
                    }
                    $value['effective_warehouse_quantity'] = $effective_warehouse_quantity;
                    // 价格变化金额1：=有效入库数量*价格变化幅度 
                    $value['price_change_1'] = sprintf("%.2f", $value['effective_warehouse_quantity']*($value['present_price']-$value['product_price']));
                    $price_change_1 += $value['price_change_1'];
                    if( empty($value['first_calculation_time']) || $value['first_calculation_time'] ==0 )
                    {
                        $value['first_calculation_time'] = "还未下单";
                    }
                }
            }
        }
        $total_number = 0;
        $results_total = $sum_purchase->group_by("new.app_id")->get()->result_array();
        $warehouse_data_total = [];
        $total_price_data = 0;
        foreach( $results_total as $k=>$v )
        {
            if( !isset($warehouse_data_total[$v['app_id']]))
            {
                $warehouse_data_total[$v['app_id']] = [];
            }
            $warehouse_data_total[$v['app_id']][] = $v['instock_qty'];
        }

        foreach( $results_total as $ke=>$va )
        {
            $effective_warehouse_quantity = isset( $warehouse_data_total[$va['app_id']] )? $warehouse_data_total[$va['app_id']]:0;
            if( is_array($effective_warehouse_quantity)) {
                $effective_warehouse_quantity = array_sum($effective_warehouse_quantity);
            }
            $va['effective_warehouse_quantity'] = $effective_warehouse_quantity;
            // 价格变化金额1：=有效入库数量*价格变化幅度 
            $va['price_change_1'] = sprintf("%.2f", $va['effective_warehouse_quantity']*($va['present_price']-$va['product_price']));
            $total_price_data += $va['price_change_1'];
        }

        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'=>count($total_count),'limit'=>$params['limit'],'offset'=>$params['offset'],
                'all_list_price_change_total' => sprintf("%.2f", $total_price_data ),
                'now_list_price_change_total'=>sprintf("%.2f",$price_change_1),
                'now_total'=>count($results)
                ]
        ];
        return $return_data;

    }

    /**
     *获取采购入库信息
     * @param  $purchase_number    string    采购单号
     *         $sku                string    商品SKU
     *         $end_time_days      string    统计结束时间
     **/
    private function get_warehouse_data($purchase_number,$sku,$end_time_days=NULL)
    {
        $query = $this->purchase_db->from("warehouse_results_main")->where("purchase_number",$purchase_number)->where("sku",$sku);
        $result = $query->select(" instock_qty,instock_date")->get()->row_array();
        return $result;
    }

    private function get_cancel_data($purchase_number,$sku)
    {
        $query = $this->purchase_db->from("purchase_order_cancel_detail")->where("purchase_number",$purchase_number)->where("sku",$sku);
        $result = $query->select("SUM(cancel_ctq) AS cancel_ctp ")->get()->row_array();
        return $result;
    }


    public function get_reduced_mongodb_data($result)
    {
        $this->load->model('Purchase/Purchase_order_cancel_model');
        $this->load->model('Purchase/Reduced_orders','reduced_model');
        $this->load->model('user/Purchase_user_model', 'userModel');
        $this->load->model('Purchase/Reduced_model','reduced');
        $this->load->model('warehouse/Warehouse_storage_record_model');
        $reduced_config = $this->reduced_model->is_reduced_config();
        $price_change_1=0;
        $purchase_order_status = getPurchaseStatus();
        $orders_status = array_column($result,"purchase_number");
        $orders_message =[];
        if( !empty($orders_status)) {
            $orders_message = $this->reduced_model->get_purchase_message($orders_status);
        }
        $order_status_data = [];
        if(!empty($orders_message))
        {
            $order_status_data = array_column($orders_message,NULL,"purchase_number");
        }
        $skus = array_column( $result,"sku");
        $purchase_number = array_column( $result,"purchase_number");
        $job_number_ids = array_column( $result,"job_number");
        $jobs = $this->userModel->get_user_job($job_number_ids);
        if(!empty($jobs))
        {
            $jobs = array_column( $jobs,NULL,"userNumber");
        }
        foreach ($result as $key => &$value) {
            $value = get_object_vars($value);
            if( $value['is_new_data'] == 1)
            {
                $value['is_new_data_ch'] ="新模块";
            }

            if( $value['is_new_data'] == 2)
            {
                $value['is_new_data_ch'] ="老模块";
            }

            if( $value['is_purchasing'] == 1 || empty($value['is_purchasing']))
            {
                $value['is_purchasing_ch'] = "否";
            }

            if( $value['is_purchasing'] == 2)
            {
                $value['is_purchasing_ch'] = "是";
            }
            if( empty($value['six_minimum_price']) || $value['six_minimum_price'] == 0.00){

                $value['six_minimum_price'] = NULL;
            }
            $end_time_days = NULL;
            if ($value['type_id'] == 1) {

                $end_time_days = $reduced_config['domestic_days'];
            }

            if ($value['type_id'] == 2) {

                $end_time_days = $reduced_config['overseas_days'];
            }

            if ($value['type_id'] == 3) {

                $end_time_days = $reduced_config['fba_days'];
            }

            $orders_value_status = isset($order_status_data[$value['purchase_number']]['purchase_order_status']) ? $order_status_data[$value['purchase_number']]['purchase_order_status'] : NULL;
            if (NULL != $end_time_days) {
                $end_days = $this->reduced->get_change_price_six_time($value['product_audit_time'], $end_time_days, 'next');
            } else {
                $end_days = NULL;
            }
            $warehouse_data = $this->get_warehouse_data($value['purchase_number'], $value['sku'],$end_time_days);
            $value['instock_qty'] = $warehouse_data['instock_qty']; // 入库数量 instock_date
            if( empty($value['instock_qty']))
            {
                $value['instock_qty'] =0;
            }
            $value['instock_date'] = $warehouse_data['instock_date']; // 入库时间 instock_date
            $value['cancel_ctq'] = $this->Purchase_order_cancel_model->get_cancel_ctq_new($value['sku'], $value['purchase_number']); // 取消数量

            //$value['breakage_number'] = $this->Warehouse_storage_record_model->get_loss_info_new($value['purchase_number'], $value['sku']);//报损
            $value['actual_number'] = (int)$warehouse_data['instock_qty']; // 实际入库数量
            if( $value['actual_number'] <0 )
            {
                $value['actual_number'] = 0;
            }
            $value['price_change_1'] = sprintf("%.2f",$value['instock_qty'] * $value['range_price']); // 价格变化1
            $price_change_1 =  $price_change_1 + $value['price_change_1'];
            // 有效采购数量
            $value['effective_purchase_num'] = (int)$value['purchase_num'] - (int)$value['cancel_ctq'];
            $value['purchase_status_mess'] = isset($purchase_order_status[$orders_value_status]) ? $purchase_order_status[$orders_value_status] : '';   //采购单状态
            // 取消数量
            $cancel_number = $this->get_cancel_data($value['purchase_number'], $value['sku']);
            $value['cancel_number'] = $cancel_number['cancel_ctp']; // 取消数量
            $value['price_change_2'] = sprintf("%.2f", ((int)$value['purchase_num'] - (int)$value['cancel_ctq']) * $value['range_price']); // 价格变化2
            $value['position'] = isset($jobs[$value['job_number']]['jobName']) ? $jobs[$value['job_number']]['jobName'] : NULL; // 职位
            $value['department'] = isset($jobs[$value['job_number']]['deptName']) ? $jobs[$value['job_number']]['deptName'] : NULL;// 部门
            // $is_effect = $this->reduced_model->is_reduced_orders_effective($value['product_audit_time'], $value['type_id'], $reduced_config, $value['product_status'], $value['purchase_number'],$value['sku']);
            //近6个月最低价格
            $six_prev_time = $this->reduced->get_change_price_six_time($value['product_audit_time'], $end_time_days, 'prev');

            if (1 == $value['is_effect']) {
                $value['is_effect_name'] = "有效";
            } else if(2 == $value['is_effect']){
                $value['is_effect_name'] = "无效";
            }else{
                $value['is_effect_name'] = "无效";
            }

            $is_end = $this->reduced_model->is_reduced_orders_end($value['purchase_number']);
            if (True == $is_end) {
                $value['is_end_name'] = "是";
            } else {
                $value['is_end_name'] = "否";
            }

            $purchase_order_status_time = $this->reduced_model->get_purchase_message([$value['purchase_number']],"ppo.purchase_order_status,ppo.audit_time");
            if( !empty($purchase_order_status_time) && in_array($purchase_order_status_time[0]['purchase_order_status'],[9,11,14]) && $purchase_order_status_time[0]['audit_time'] >= $value['end_days'] ) {

                $value['end_days'] = $purchase_order_status_time[0]['audit_time'];
                $value['is_end_name'] = "是";

            }
            if ($value['is_superposition'] == 1) {
                $value['is_superposition_name'] = "叠加无PO";
            }
            if ($value['is_superposition'] == 2) {
                $value['is_superposition_name'] = "叠加";
            }
            if ($value['is_superposition'] == 3) {
                $value['is_superposition_name'] = "非叠加";
            }

            // SKU 降本明细计算降本比例
            if ($value['old_price'] != '0.000' && $value['old_price']!=0) {
                $value['reduced_proportion'] = (sprintf("%.2f", ($value['new_price'] - $value['old_price']) / $value['old_price'])*100) . "%";
            } else {
                $value['reduced_proportion'] = NULL;
            }
            // 兼容前端采购员
            $value['puchare_person'] = $value['purchase_person'];
            // 结束统计时间
            if ($value['purchase_type'] == PURCHASE_TYPE_INLAND) {

                $end_time_days = $reduced_config['domestic_days'];
            }

            if ($value['purchase_type'] == PURCHASE_TYPE_OVERSEA) {

                $end_time_days = $reduced_config['overseas_days'];
            }

            if ($value['purchase_type'] == PURCHASE_TYPE_FBA) {
                $end_time_days = $reduced_config['fba_days'];
            }
            $value['end_time'] = $this->reduced->get_change_price_six_time($value['product_audit_time'], $end_time_days, 'next');
            // = isset($confirm_amounts[$value['purchase_number']]) ? $confirm_amounts[$value['purchase_number']]['confirm_amount'] : 0;
            $value['message_confirm_amount'] = $value['purchase_num'];
            if (strstr($value['end_time'], "1970-01-01")) {
                $value['end_time'] = NULL;
            }

            if(!empty($value['optimizing_user']) && !empty($value['job_number']) && strstr($value['optimizing_user'],$value['job_number']))
            {
                $value['optimizing_user'] = $value['optimizing_user'];
            }else {
                if($value['job_number'] !=0)
                {
                    $value['optimizing_user'] = $value['optimizing_user'] . $value['job_number'];
                }else {
                    $value['optimizing_user'] = $value['optimizing_user'];
                }
            }


            if( ($value['new_price'] > $value['old_price']) && $value['is_superposition'] == 1)
            {
                $value['end_time'] = NULL;
                $value['effective_purchase_num'] = NULL;
                $value['purchase_number'] =$value['demand_number'] = NULL;
                if( isset($value['puchare_person']))
                {
                    $value['puchare_person'] = NULL;
                }
                $value['product_audit_time'] = NULL;
                $value['purchase_status_mess'] = NULL;
                $value['is_superposition_name'] = NULL;

                if( isset($value['purchase_num']))
                {
                    $value['purchase_num'] =0;
                }

                if( isset($value['instock_qty']))
                {
                    $value['instock_qty'] =0;
                }
                if( isset($value['actual_number']))
                {
                    $value['actual_number'] =0;
                }
                if( isset($value['cancel_ctq']))
                {
                    $value['cancel_ctq'] =0;
                }
                $value['price_change_2'] = $value['price_change_1'] =0;
                $value['first_calculation_time'] = "未下单";
            }

            if($value['is_superposition'] == 1)
            {
                $value['first_calculation_time'] = "未下单";
            }

        }
        return $result;
    }

    /**
     * 获取SKU 是否代采
     * @param: $sku  array SKU
     * @author:luxu
     **/
    public function get_sku_is_purchaseing($skus)
    {
        if( empty($skus))
        {
            return [];
        }

        return $this->purchase_db->from("product")->where_in("sku",$skus)->select("sku,is_purchasing")->get()->result_array();
    }

    /**
     * 获取SKU 降本明细信息
     **/
    public function get_reduced_detail($clientData,$flag = 'list' )
    {
//        $log_file = APPPATH . 'logs/yefanli_' . date('Ymd') . '.txt';
//        file_put_contents($log_file, $this->get_microtime()."**start get_reduced_detail******\n", FILE_APPEND);
        //--获取权限控制数据--start
        $user_id = jurisdiction(); //当前登录用户ID
        $role_name = get_user_role();//当前登录角色
        $data_role = getRole();
        $res_arr = array_intersect($role_name, $data_role);
        $authorization_user = [];
        if (!(!empty($res_arr) or $user_id === true)) {
            $authorization_user = $user_id;//数据权限采购员id集合
        }
        //--获取权限控制数据--end

        $query = $this->purchase_db->from("purchase_reduced_detail_other AS detail");
        $this->load->model('Purchase/Purchase_order_cancel_model');
        $this->load->model('Purchase/Reduced_orders','reduced_model');
        $this->load->model('user/Purchase_user_model', 'userModel');
        $this->load->model('Purchase/Reduced_model','reduced');
        $this->load->model('warehouse/Warehouse_storage_record_model');
        $qMain = false;$qLoss = false;$qCancal = false;

        //数据权限控制
        if($authorization_user){

            if(!isset($clientData['is_swoole'])) {
                $query->where_in('purorders.buyer_id', $authorization_user);
            }
        }

        if(isset($clientData['group_ids']) && !empty($clientData['group_ids'])){

            if(count($clientData['groupdatas'])>2000){

                $clientData['groupdatas'] = array_chunk($clientData['groupdatas'],200);

                foreach($clientData['groupdatas'] as $groupdataValue){

                    $query->where_in('purorders.buyer_id',$groupdataValue);
                }
            }else {
                $query->where_in('purorders.buyer_id', $clientData['groupdatas']);
            }
        }

        // 传入优化人姓名
        if( isset($clientData['person_name']) && !empty($clientData['person_name']) )
        {
            if(is_array($clientData['person_name'])) {
                $query->where_in(" detail.person_name", $clientData['person_name']);
            }else{
                $query->where(" detail.person_name", $clientData['person_name']);
            }
        }

        if( isset($clientData['ids']) && !empty($clientData['ids']))
        {
            $query->where_in("detail.id",$clientData['ids']);
        }

        //采购号查询
        if( isset($clientData['purchase_number']) && !empty($clientData['purchase_number']))
        {
            $query->where_in("detail.purchase_number",$clientData['purchase_number']);
        }

        //采购单备单号
        if( isset($clientData['demand_number']) && !empty($clientData['demand_number']))
        {
            $query->where_in("detail.demand_number",$clientData['demand_number']);
        }

        //下单开始时间
        if( isset($clientData['audit_time_start']) && !empty($clientData['audit_time_start']))
        {
            $query->where("detail.product_audit_time>=",$clientData['audit_time_start']);
        }

        //下单结束时间
        if( isset($clientData['audit_time_end']) && !empty($clientData['audit_time_end'])) {

            $query->where("detail.product_audit_time<=",$clientData['audit_time_end']);
        }
        // 传入供应商

        if( isset($clientData['supplier_code']) && !empty($clientData['supplier_code']))
        {
            $query->where("detail.supplier_code",$clientData['supplier_code']);
        }
        // 传入商品的SKU
        if( isset($clientData['sku']) && !empty($clientData['sku']) )
        {
            if (count($clientData['sku']) == 1 && !empty($clientData['sku'][0])) {  //单个sku时使用模糊搜索
                $query->like('detail.sku', $clientData['sku'][0], 'both');
            } else if(count($clientData['sku']) > 1) {
                $query->where_in('detail.sku', $clientData['sku']);
            }
        }
        // 传入价格变化的开始时间
        if( isset($clientData['price_change_start_time']) && !empty($clientData['price_change_start_time']))
        {
            $query->where("detail.price_change_time>=",$clientData['price_change_start_time']);
        }
        // 价格变化的结束时间
        if( isset($clientData['price_change_end_time']) && !empty($clientData['price_change_end_time']))
        {
            $query->where("detail.price_change_time<=",$clientData['price_change_end_time']);
        }

        // 实际入库
        if( isset($clientData['warehouse_number']) && !empty($clientData['warehouse_number']) )
        {

            // 表示不等于0
            if( $clientData['warehouse_number'] == 2 )
            {
                $query->where_in("loss.status",[0,2,4]);
                $query->where("main.instock_qty > loss.loss_amount");
            }else{
                // 等于0
                $query->where("(main.instock_qty IS NULL ")->or_where("  main.instock_qty = loss.loss_amount )");
            }
            $qLoss = true;
        }

        // 入库开始时间

        if( isset($clientData['warehouse_start_time']) && !empty($clientData['warehouse_start_time']))
        {
            $query->where("main.instock_date>=",$clientData['warehouse_start_time']);
        }
        // 入库结束时间
        if( isset($clientData['warehouse_end_time']) && !empty($clientData['warehouse_end_time']))
        {
            $query->where("main.instock_date<=",$clientData['warehouse_end_time']);
        }
        // 首次计算开始时间

        if( isset($clientData['first_calculation_start_time']) && !empty($clientData['first_calculation_start_time']) )
        {
            $query->where("detail.first_calculation_time>=",$clientData['first_calculation_start_time']);
        }
        // 首次计算结束时间

        if( isset($clientData['first_calculation_end_time']) && !empty($clientData['first_calculation_end_time']) )
        {
            $query->where("detail.first_calculation_time<=",$clientData['first_calculation_end_time']);
        }
        //是否叠加
        if( isset($clientData['is_superposition']) && !empty($clientData['is_superposition']) )
        {
            $query->where("detail.is_superposition=",$clientData['is_superposition']);
        }
        // 价格变化趋势
        if( isset($clientData['gain']) && !empty($clientData['gain']) )
        {
            // 表示涨价
            if( $clientData['gain'] == 1 )
            {
                $query->where("detail.range_price>0");
            }else{
                // 降价
                $query->where("detail.range_price<0");
            }
        }
        // 价格变化历史ID

        if( isset($clientData['app_id']) && !empty($clientData['app_id']))
        {
            $query->where("detail.app_id=",$clientData['app_id']);
        }

        // 订单是否有效

        if( isset($clientData['is_effect']) && !empty($clientData['is_effect']))
        {
            $query->where("detail.is_effect",$clientData['is_effect']);
        }

        // 是否结束统计

        if( isset($clientData['is_end']) && !empty($clientData['is_end']) )
        {
            $isEnd = "";
            // 表示结束
            if( $clientData['is_end'] != 1 )$isEnd = "NOT";
            $query->where("purorders.purchase_order_status {$isEnd} IN (9,11,14)");
        }
        if( isset($clientData['purchas_price']) && !empty($clientData['purchas_price']))
        {
            $query->where("detail.purchase_price",$clientData['purchas_price']);
        }

        // 订单完结时间查询
        if( isset($clientData['completion_time_start']) && !empty($clientData['completion_time_start'])
            && isset($clientData['completion_time_end']) && !empty($clientData['completion_time_end']) )
        {
            $query->where_in("purorders.purchase_order_status",[11,9,14])
                ->where("completion_time>=",$clientData['completion_time_start'])
                ->where("completion_time<=",$clientData['completion_time_end']);
        }
        // 是否为新模块数据

        if( isset($clientData['is_new_data']) && !empty($clientData['is_new_data']))
        {
            $query->where("detail.is_new_data",$clientData['is_new_data']);
        }

        // 是否代采

        if( isset($clientData['is_purchasing']) && !empty($clientData['is_purchasing']))
        {
            $query->where("prod.is_purchasing",$clientData['is_purchasing']);
        }

        // 采购数量
        if( isset($clientData['purchase_num']) && !empty($clientData['purchase_num']) )
        {
            // 表示不等于0
            if( $clientData['purchase_num'] == 2 )
            {
                //$query->where("(detail.purchase_num > cancal.cancel_ctq ")->or_where(" cancal.cancel_ctq IS NULL)");
                $query->where('(detail.purchase_num > (select sum(cancel_ctq) as cancel_ctq from pur_purchase_order_cancel_detail as cc
                where detail.sku = cc.sku AND detail.purchase_number=cc.purchase_number GROUP BY cc.sku, cc.purchase_number)', '', false)
                    ->or_where('(select sum(cancel_ctq) as cancel_ctq from pur_purchase_order_cancel_detail as cca
                    where detail.sku=cca.sku AND detail.purchase_number=purchase_number GROUP BY cca.sku, cca.purchase_number) is null)', '', false);

            }else{
                // 等于0
                //$query->where(" detail.purchase_num = cancal.cancel_ctq ");
                $query->where('detail.purchase_num = (
                select sum(cancel_ctq) as cancel_ctq  from pur_purchase_order_cancel_detail as cancal
                where detail.sku=cancal.sku
                AND detail.purchase_number=cancal.purchase_number GROUP BY cancal.sku, cancal.purchase_number', '', false);

            }
        }

        $query->join("warehouse_results_main AS main","main.purchase_number=detail.purchase_number AND main.sku=detail.sku","LEFT");
        if($qLoss)$query->join("purchase_order_reportloss AS loss","detail.purchase_number=loss.pur_number AND detail.sku=loss.sku","LEFT");
        $query->join("purchase_order AS purorders","detail.purchase_number=purorders.purchase_number","LEFT");
        $query->join("product AS prod","detail.sku=prod.sku","LEFT");

        /*if($qCancal)$query->join("(SELECT
            sum(cancel_ctq) AS cancel_ctq,
            sku,
            purchase_number
            FROM
                pur_purchase_order_cancel_detail
            GROUP BY
                sku,
		purchase_number) AS cancal","detail.sku=cancal.sku AND detail.purchase_number=cancal.purchase_number","LEFT");
        */

        if( 'list' == $flag )
        {
            $total_query = clone $query;
            $sum_query = clone $query;

            $query = $query->limit($clientData['limit'],($clientData['offset']-1)*$clientData['limit']);

            $result = $query->select("detail.*,purorders.completion_time,prod.is_purchasing,purorders.buyer_id")->order_by("detail.id DESC")->get()->result_array();
            if(isset($clientData['flag']) && $clientData['flag'] == 'sku')
            {
                $total_query->group_by("detail.purchase_number");
            }
            $total = $total_query->count_all_results();
            $reduced_config = $this->reduced_model->is_reduced_config();
            $price_change_1=0;
            if( !empty($result) )
            {
                $return_skus = [];
                $app_id = array_column( $result,"app_id");
                $app_id=  array_unique($app_id);
                // 获取优化人职位信息
                $job_number_ids = array_column( $result,"job_number");
                $jobs = $this->userModel->get_user_job($job_number_ids);
                if(!empty($jobs))
                {
                    $jobs = array_column( $jobs,NULL,"userNumber");
                }
                $purchase_order_status = getPurchaseStatus();
                $orders_status = array_column($result,"purchase_number");
                $orders_message = $this->reduced_model->get_purchase_message($orders_status);
                $order_status_data = [];
                if(!empty($orders_message))
                {
                    $order_status_data = array_column($orders_message,NULL,"purchase_number");
                }

                /*
                                $skus = array_column( $result,"sku");
                                $purchase_number = array_column( $result,"purchase_number");
                                //$confir_numbers = $this->reduced_model->get_purchase_message($purchase_number,"ppo.confir_number,ppo.purchase_number");

                //                $confirm_amounts = $this->purchase_db->from("purchase_order_items")->where_in("purchase_number",$purchase_number)->select(" SUM(confirm_amount) AS confirm_amount,purchase_number")->where("is_cancel=1")->get()->result_array();
                //                $confirm_amounts = array_column( $confirm_amounts,NULL,"purchase_number");
                //                $cancel_numbers = $this->get_purchase_cancel($skus,$purchase_number);
                */

                $buyerIds =  array_filter(array_unique(array_column($result,"buyer_id")));
                $buyerName = $this->User_group_model->getBuyerGroupMessage($buyerIds);
                $buyerName = array_column($buyerName,NULL,'user_id');
                foreach ($result as $key => &$value) {

                    if( empty($value['six_minimum_price']) || $value['six_minimum_price'] == 0.00){

                        $value['six_minimum_price'] = NULL;
                    }
                    $end_time_days = NULL;
                    if( strtotime($value['product_audit_time']) >= strtotime('2020-03-01')) {
                        if ($value['type_id'] == 1) {

                            $end_time_days = $reduced_config['domestic_days'];
                        }
                        if ($value['type_id'] == 2) {

                            $end_time_days = $reduced_config['overseas_days'];
                        }

                        if ($value['type_id'] == 3) {

                            $end_time_days = $reduced_config['fba_days'];
                        }
                    }else{
                        if ($value['type_id'] == 1) {

                            $end_time_days = 60;
                        }
                        if ($value['type_id'] == 2) {

                            $end_time_days = 90;
                        }

                        if ($value['type_id'] == 3) {

                            $end_time_days = 60;
                        }

                    }
                    if( $value['is_purchasing'] == 1 || empty($value['is_purchasing']))
                    {
                        $value['is_purchasing_ch'] = "否";
                    }

                    if( $value['is_purchasing'] == 2)
                    {
                        $value['is_purchasing_ch'] = "是";
                    }

                    if( $value['is_new_data'] == 1)
                    {
                        $value['is_new_data_ch'] ="是";
                    }

                    if( $value['is_new_data'] == 2)
                    {
                        $value['is_new_data_ch'] ="否";
                    }

                    $orders_value_status = isset($order_status_data[$value['purchase_number']]['purchase_order_status']) ? $order_status_data[$value['purchase_number']]['purchase_order_status'] : NULL;
                    if (NULL != $end_time_days) {
                        $end_days = $this->reduced->get_change_price_six_time($value['product_audit_time'], $end_time_days, 'next');
                    } else {
                        $end_days = NULL;
                    }

                    $warehouse_data = $this->get_warehouse_data($value['purchase_number'], $value['sku'],$end_time_days);
                    $value['instock_qty'] = $warehouse_data['instock_qty']; // 入库数量 instock_date
                    if( empty($value['instock_qty']))
                    {
                        $value['instock_qty'] =0;
                    }

                    $value['instock_date'] = $warehouse_data['instock_date']; // 入库时间 instock_date
                    $value['cancel_ctq'] = $this->Purchase_order_cancel_model->get_cancel_ctq_new($value['sku'], $value['purchase_number']); // 取消数量

                    //$value['breakage_number'] = $this->Warehouse_storage_record_model->get_loss_info_new($value['purchase_number'], $value['sku']);//报损
                    $value['actual_number'] = (int)$warehouse_data['instock_qty']; // 实际入库数量
                    if( $value['actual_number'] <0 )
                    {
                        $value['actual_number'] = 0;
                    }
                    $value['price_change_1'] = sprintf("%.2f",$value['instock_qty'] * $value['range_price']); // 价格变化1
                    $price_change_1 =  $price_change_1 + $value['price_change_1'];
                    // 有效采购数量
                    $value['effective_purchase_num'] = (int)$value['purchase_num'] - (int)$value['cancel_ctq'];
                    $value['purchase_status_mess'] = isset($purchase_order_status[$orders_value_status]) ? $purchase_order_status[$orders_value_status] : '';   //采购单状态
                    // 取消数量
                    $cancel_number = $this->get_cancel_data($value['purchase_number'], $value['sku']);
                    $value['cancel_number'] = $cancel_number['cancel_ctp']; // 取消数量
                    $value['price_change_2'] = sprintf("%.2f", ((int)$value['purchase_num'] - (int)$value['cancel_ctq']) * $value['range_price']); // 价格变化2
                    $value['position'] = isset($jobs[$value['job_number']]['jobName']) ? $jobs[$value['job_number']]['jobName'] : NULL; // 职位
                    $value['department'] = isset($jobs[$value['job_number']]['deptName']) ? $jobs[$value['job_number']]['deptName'] : NULL;// 部门
                    // $is_effect = $this->reduced_model->is_reduced_orders_effective($value['product_audit_time'], $value['type_id'], $reduced_config, $value['product_status'], $value['purchase_number'],$value['sku']);
                    //近6个月最低价格
//                    $six_prev_time = $this->reduced->get_change_price_six_time($value['product_audit_time'], $end_time_days, 'prev'); // yefanli   ？ 无效数据？

                    $value['is_effect_name'] = "无效";
                    if (1 == $value['is_effect']) {
                        $value['is_effect_name'] = "有效";
                    }

//                    if ($value['is_superposition'] == 1) {
//                        $value['is_effect_name'] = NULL;
//                    }

                    $is_end = $this->reduced_model->is_reduced_orders_end($value['purchase_number']);
                    if (True == $is_end) {
                        $value['is_end_name'] = "是";
                    } else {
                        $value['is_end_name'] = "否";
                    }

                    $purchase_order_status_time = $this->reduced_model->get_purchase_message([$value['purchase_number']],"ppo.purchase_order_status,ppo.audit_time");
                    if( !empty($purchase_order_status_time) && in_array($purchase_order_status_time[0]['purchase_order_status'],[9,11,14]) && $purchase_order_status_time[0]['audit_time'] >= $value['end_days'] ) {

                        $value['end_days'] = $purchase_order_status_time[0]['audit_time'];
                        $value['is_end_name'] = "是";

                    }
                    if ($value['is_superposition'] == 1) {
                        $value['is_superposition_name'] = "叠加无PO";
                    }
                    if ($value['is_superposition'] == 2) {
                        $value['is_superposition_name'] = "叠加";
                    }
                    if ($value['is_superposition'] == 3) {
                        $value['is_superposition_name'] = "非叠加";
                    }

                    // SKU 降本明细计算降本比例
                    if ($value['old_price'] != '0.000' && $value['old_price']!=0) {
                        $value['reduced_proportion'] = (sprintf("%.2f", ($value['new_price'] - $value['old_price']) / $value['old_price'])*100) . "%";
                    } else {
                        $value['reduced_proportion'] = NULL;
                    }
                    // 兼容前端采购员
                    $value['puchare_person'] = $value['purchase_person'];
                    // 结束统计时间
                    if ($value['purchase_type'] == PURCHASE_TYPE_INLAND) {

                        $end_time_days = $reduced_config['domestic_days'];
                    }

                    if (in_array($value['purchase_type'], [PURCHASE_TYPE_OVERSEA, PURCHASE_TYPE_FBA_BIG])) {
                        $end_time_days = $reduced_config['overseas_days'];
                    }

                    if ($value['purchase_type'] == PURCHASE_TYPE_FBA) {
                        $end_time_days = $reduced_config['fba_days'];
                    }
                    $value['end_time'] = $this->reduced->get_change_price_six_time($value['product_audit_time'], $end_time_days, 'next');
                    // = isset($confirm_amounts[$value['purchase_number']]) ? $confirm_amounts[$value['purchase_number']]['confirm_amount'] : 0;
                    $value['message_confirm_amount'] = $value['purchase_num'];
                    if (strstr($value['end_time'], "1970-01-01")) {
                        $value['end_time'] = NULL;
                    }

                    if(!empty($value['optimizing_user']) && !empty($value['job_number']) && strstr($value['optimizing_user'],$value['job_number']))
                    {
                        $value['optimizing_user'] = $value['optimizing_user'];
                    }else {
                        if($value['job_number'] !=0)
                        {
                            $value['optimizing_user'] = $value['optimizing_user'] . $value['job_number'];
                        }
                    }
                    if(isset($clientData['flag']) && $clientData['flag'] == 'sku')
                    {
                        $value['new_price'] = $value['purchase_price'];
                    }

                    if( ($value['new_price'] > $value['old_price']) && $value['is_superposition'] == 1)
                    {
                        $value['end_time'] = NULL;
                        $value['effective_purchase_num'] = NULL;
                        $value['purchase_number'] =$value['demand_number'] = NULL;
                        if( isset($value['puchare_person']))
                        {
                            $value['puchare_person'] = NULL;
                        }
                        $value['product_audit_time'] = NULL;
                        $value['purchase_status_mess'] = NULL;
                        $value['is_superposition_name'] = NULL;

                        if( isset($value['purchase_num']))
                        {
                            $value['purchase_num'] =0;
                        }

                        if( isset($value['instock_qty']))
                        {
                            $value['instock_qty'] =0;
                        }
                        if( isset($value['actual_number']))
                        {
                            $value['actual_number'] =0;
                        }
                        if( isset($value['cancel_ctq']))
                        {
                            $value['cancel_ctq'] =0;
                        }
                        $value['price_change_2'] = $value['price_change_1'] =0;
                        //$value['first_calculation_time'] = "未下单";
                    }

                    if($value['is_superposition'] == 1)
                    {
                       // $value['first_calculation_time'] = "未下单";
                    }

                    $value['groupName']                = isset($buyerName[$value['buyer_id']])?$buyerName[$value['buyer_id']]['group_name']:'';

                }
            }
            if( !isset($clientData['is_swoole'])) {

                $reduced_prices = $sum_query->select("SUM(main.instock_qty * detail.range_price) AS reduced_prices")->where("main.instock_qty>0")->get()->row_array();
                $total_change_price1 = $reduced_prices['reduced_prices'];

                return array(
                    'list' => $result,
                    'page_data' => ['total'=>$total,'limit'=>$clientData['limit'],'offset'=>$clientData['offset'],
                        'all_list_price_change_total' => sprintf("%.2f",$total_change_price1),
                        'now_list_price_change_total'=>sprintf("%.2f",$price_change_1),'now_total'=>count($result)
                    ],
                );
            }

            if(isset($clientData['is_swoole']) && $clientData['is_swoole'] == true){

                return array(
                    'list' => $result,
                );
            }

        }else{
            return $query->count_all_results();
        }
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
     * 表示SKU 降本明细数据是新老数据
     * @param:  ids   array   SKU 降本明细ID
     *          is_new_data  int  是否为新老数据  1表示是 2表示是否
     * @author:luxu
     **/

    public function set_reduced_data($ids,$is_new_data)
    {
        try {
            if (!empty($ids) && !empty($is_new_data)) {

                $this->purchase_db->trans_begin();
                $success_data = $error_data = [];
                foreach($ids as $key=>$id) {
                    $result = $this->purchase_db->where_in("id", $id)->where("is_new_data!=", $is_new_data)->update("purchase_reduced_detail_other", ['is_new_data' => $is_new_data]);
                    if($result)
                    {
                        $success_data[] = $id;
                    }else{
                        $error_data[] = $id;
                    }
                    // 记录修改日志
                    $content_detail = "改为".($is_new_data ==1?"是":"否");
                    $update = array(
                        "operator" => getActiveUserName(),
                        "content_detail" => $content_detail,
                        "record_type" => "SET_REDUCED_DATA",
                        "operate_time" =>date("Y-m-d H:i:s",time()),
                        "record_number" => $id
                    );
                    $this->purchase_db->insert("pur_operator_log",$update);
                }

                if( empty($error_data))
                {
                    $this->purchase_db->trans_commit();
                    return True;
                }else{
                    throw new Exception("更新失败",500);
                }
            }

            throw new Exception("数据传入错误",404);
        }catch ( Exception $exp )
        {
            if( $exp->getCode() == 500)
            {
                $this->purchase_db->trans_rollback();
            }

            throw new Exception( $exp->getMessage());
        }
    }

    /**
     * 获取SKU 降本设置新老模块日志数据
     * @author:luxu
     **/

    public function get_set_reduced_data_log($ids)
    {
        return $this->purchase_db->from("operator_log")->select("operator,operate_time,content_detail")->where("record_type","SET_REDUCED_DATA")->where("record_number",$ids)->order_by("id DESC")->get()->result_array();
    }

}