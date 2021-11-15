<?php
/**
 * Created by PhpStorm.
 * 仓库模型
 * User: Jolon
 * Date: 2018/12/29 0029 11:50
 */
class Warehouse_model extends Purchase_model {

    protected $table_name = 'warehouse';
    protected $table_name_address = 'warehouse_address';
    protected $table_name_rule = 'warehouse_freight_rule';


    /**
     * 获取 仓库列表
     * @author Jolon
     * @param array $params
     * @param int  $offset
     * @param int  $limit
     * @param int  $page
     * @return array
     */
    public function get_warehouse_list($params = [],$offset = null,$limit = null,$page = 1){
        $params = filter_array_none($params);

        $query_builder = $this->purchase_db->select('*');
        if($params){
            $query_builder->where($params);
        }
        $warehouse_list = $query_builder->get($this->table_name)
            ->result_array();
        return $warehouse_list;
    }

    /**
     * 获取 仓库列表
     * @author Jolon
     * @return array
     */
    public function get_pertain_wms_list(){
        $pertain_wms_list = $this->rediss->getData('pertain_wms_list');
        if(empty($pertain_wms_list)){
            $pertain_wms_list = $this->purchase_db->select('A.warehouse_code,A.warehouse_name,A.pertain_wms AS pertain_wms_code,B.warehouse_name AS pertain_wms_name')
                ->from($this->table_name.' AS A')
                ->join($this->table_name.' AS B','A.pertain_wms=B.warehouse_code','LEFT')
                ->where('A.pertain_wms != ""')
                ->get()
                ->result_array();
            $this->rediss->setData('pertain_wms_list',base64_encode(json_encode((array)$pertain_wms_list)));
        }else{
            $pertain_wms_list = json_decode(base64_decode($pertain_wms_list),true);
        }

        return $pertain_wms_list;
    }

    /**
     * 获取 仓库基本信息
     * @author Jolon
     * @param string $warehouse_code  仓库编码
     * @param string $field           指定列名（只返回指定字段）
     * @return array
     */
    public function get_warehouse_one($warehouse_code,$field = null){
        $warehouse_info = $this->purchase_db->select('id,warehouse_code,warehouse_name,warehouse_type,pertain_wms')
            ->where('warehouse_code',$warehouse_code)
            ->get($this->table_name)
            ->row_array();
        if(!is_null($field)){
            return isset($warehouse_info[$field])?$warehouse_info[$field]:'';
        }

        return $warehouse_info;
    }

    /**
     * 获取 仓库地址信息
     * @author Jolon
     * @param string $warehouse_code  仓库编码
     * @param string $field           指定列名（只返回指定字段）
     * @return array
     */
    public function get_warehouse_address_one($warehouse_code,$field = null){
        $warehouse_address = $this->purchase_db->select('*')
            ->where('warehouse_code',$warehouse_code)
            ->get($this->table_name_address)
            ->row_array();

        if(!is_null($field)){
            return isset($warehouse_address[$field])?$warehouse_address[$field]:'';
        }

        return $warehouse_address;
    }

    /**
     * 根据 warehouse_code 集合 获取  warehouse_code => warehouse_name 的集合
     * @author liwuxue
     * @date 2019/2/14 16:49
     * @param
     * @return mixed
     * @throws Exception
     */
    public function get_code2name_list(array $codes)
    {
        $rows = $this->get_list_by_codes($codes, "warehouse_code,warehouse_name");
        return array_column($rows, "warehouse_name", "warehouse_code");
    }

    /**
     * 获取所有仓库code => name 结构
     */
    public function get_code_name_list()
    {
        $data = $this->purchase_db->from($this->table_name)->get()->result_array();
        return array_column($data, "warehouse_name", "warehouse_code");
    }

    /**
     * 获取所有物流类型code => name 结构
     */
    public function get_logistics_type()
    {
        $data = $this->purchase_db->from("logistics_logistics_type")->get()->result_array();
        return array_column($data, "type_name", "type_code");
    }

    /**
     * 根据 warehouse_code 集合 获取列表数据
     * @author liwuxue
     * @date 2019/2/14 16:49
     * @param array $codes
     * @param string $field
     * @return array
     */
    public function get_list_by_codes(array $codes, $field = "*")
    {
        $codes = array_filter(array_unique($codes));
        if (empty($codes)) {
            return [];
        }
        $rows = $this->purchase_db
            ->select($field)
            ->where_in("warehouse_code", $codes)
            ->get($this->table_name)
            ->result_array();
        return is_array($rows) ? $rows : [];
    }

    /**
     * 返回仓库地址信息
     * @return mixed
     */
    public function get_warehouse_address($warehouse_code =''){

        $sql = "select a.purchase_type_id,a.warehouse_code,a.warehouse_name,b.province_text,b.city_text,b.area_text,b.town_text,b.address,b.post_code,b.contacts,b.contact_number
 from pur_warehouse a join pur_warehouse_address b on a.warehouse_code=b.warehouse_code where a.use_status=1 ";
        if(!empty($warehouse_code)){
            $sql.=" and  a.warehouse_code='".$warehouse_code."' limit 1";
        }
        return $this->purchase_db->query($sql)->result_array();
    }

    /**
     * 根据仓库编码更新
     */
    public function updateWarehouseAddress($warehouse_code,$contacts,$contact_number){
        $sql = "update pur_warehouse_address set contacts='".$contacts."',contact_number='".$contact_number."'  where warehouse_code='".$warehouse_code."'";
        return $this->purchase_db->query($sql);
    }

    /**
     * 调用服务层api
     *  post 写操作
     * @author liwuxue
     * @date 2019/1/30 16:51
     * @param $url
     * @param $param
     * @param $method
     * @return mixed
     * @throws Exception
     */
    protected function _curlWriteHandleApi($url, $param, $method = "POST")
    {
        $api_resp = $this->httpRequest($url, $param, $method);
        if (isset($api_resp['status']) && $api_resp['status'] == 1) {
            //操作成功
            $this->_errorMsg = isset($api_resp['errorMess']) ? $api_resp['errorMess'] : "操作成功！！！";
            return ["data_list" => isset($api_resp['data_list']) ? $api_resp['data_list'] : []];
        } else {
            //失败
            $msg = isset($api_resp['errorMess']) ? $api_resp['errorMess'] : "";
            throw new Exception($msg, -1);
        }
    }

    /**
      * 物流系统获取仓库信息接口
     **/
    private function warehouse_data( $warehouse_url,$page =1, $pageSize = 100, $warehouse_code) {

        $token = getOASystemAccessToken();
        $url = $warehouse_url."?access_token=".$token;

        if( !empty($warehouse_code) ) {
            $params['warehouseCode'] = $warehouse_code;
        }
        $params['page'] = $page ;
        $params['pageSize'] =  $pageSize;
        $header        = array('Content-Type: application/json');
        $result = getCurlData($url,json_encode($params,JSON_UNESCAPED_UNICODE),'post',$header);
        if( !empty($result) ) {

            return json_decode($result,True);
        }
        return NULL;
    }

    /**
      *  从物流系统获取仓库信息
     **/
    public function get_warehouse_data( $warehouse_code ) {
            $this->load->config('api_config', FALSE, TRUE);
            $warehouse_url = $this->config->item('logistics_rule');
            $warehouse_url = $warehouse_url['java_warehouse_url'];
            if (!empty($warehouse_url)) {

                $limit = 10;
                $result = $this->warehouse_data($warehouse_url, 1, $limit, $warehouse_code);

                if (NULL != $result && !empty($result['data']['records'])) {

                    if ($result['data']['total'] > $limit) {

                        $page = ceil($result['data']['total'] / $limit);
                        $result_total = [];
                        for ($i = 0; $i <= $page; $i++) {

                            $warehouse_result = $this->warehouse_data($warehouse_url, $i, $limit,$warehouse_code);
                            if (!empty($warehouse_result) && !empty($warehouse_result['data']['records'])) {

                                $result_total[] = $warehouse_result['data']['records'];
                            }
                        }

                        $result_total[] = $result['data']['records'];
                        $warehouse_exists_result = [];
                        if (!empty($result_total)) {
                            $warehouse_codes_exists = [];
                            foreach ($result_total as $warehouse_key => $warehouse_value) {

                                $warehouse_codes[] = array_column($warehouse_value, "warehouseCode");
                                $warehouse_exists = $this->purchase_db->from("pur_warehouse_address")->select("warehouse_code")->where_in('warehouse_code', $warehouse_code)->get()->result_array();
                                $warehouse_codes_exists = [];
                                if (!empty($warehouse_exists)) {

                                    $warehouse_codes_exists = array_column($warehouse_exists, "warehouse_code");
                                }


                                $insert_warehouse_address_data =$insert_warehouse_data  = $update_warehouse_data = $update_data = [];

                                foreach ($warehouse_value as $key => $value) {

                                    if (!in_array($value['warehouseCode'], $warehouse_codes_exists)) {

                                        $insert_warehouse_data[] = array(
                                            "warehouse_name" => $value['warehouseName'],
                                            "warehouse_code" => $value['warehouseCode'],
                                            "use_status" => $value['useStatus'],
                                            "pertain_wms" => trim($value['pertainWms']),
                                            "create_time" => $value['createTime'],
                                            "create_user" => $value['createUser']
                                        );
                                        $insert_warehouse_address_data[] = array(
                                            "warehouse_code" => $value['warehouseCode'],
                                            "warehouse_type" => $value['warehouseType'],
                                            "after_type" => $value['afterType']

                                        );
                                    } else {
                                        $update_warehouse_data[] = array(
                                            "warehouse_name" => $value['warehouseName'],
                                            "warehouse_code" => $value['warehouseCode'],
                                            "use_status" => $value['useStatus'],
                                            "pertain_wms" => trim($value['pertainWms']),
                                            "create_time" => $value['createTime'],
                                            "create_user" => $value['createUser']
                                        );
                                        $update_data[] = array(

                                            "warehouse_code" => $value['warehouseCode'],
                                            "warehouse_type" => $value['warehouseType'],
                                            "after_type" => $value['afterType'],

                                        );
                                    }
                                }
                                if (!empty($insert_warehouse_data)) {
                                    $this->purchase_db->insert_batch('pur_warehouse', $insert_warehouse_data);
                                    $this->purchase_db->insert_batch('pur_warehouse_address', $insert_warehouse_address_data);
                                }

                                if (!empty($update_data)) {
                                    $this->purchase_db->update_batch('pur_warehouse', $update_warehouse_data, 'warehouse_code');
                                    $this->purchase_db->update_batch('pur_warehouse_address', $update_data, 'warehouse_code');
                                }

                            }

                        }
                    }

                }
            }
    }

    /**
      * 获取采购系统的仓库信息
     **/
    public function get_warehouse_list_data($params) {
        $this->load->helper('status_order');

        if( !isset($params['offset']) && empty($params['offset'])) {

            $params['offset']  = 1;
        }

        if( !isset($params['limit']) && empty($params['limit'])) {

            $params['limit'] = 20;
        }

        $query = $this->purchase_db->select("warehouse.warehouse_type,address.after_type,address.address AS address_message,warehouse.warehouse_code,warehouse.use_status,address.province_text AS state,address.city_text AS city,address.area_text AS count_name ,address.town_text AS area_name,
                                   warehouse.address,warehouse.warehouse_name,warehouse.create_user,address.contacts AS collector,warehouse.create_user,address.contact_number AS phone,address.post_code AS zipcode, warehouse.purchase_type_id,
                                   warehouse.pertain_wms,ware_tmp.warehouse_name AS pertain_wms_name,warehouse.is_drawback")
            ->from("pur_warehouse AS warehouse")
            ->join("pur_warehouse_address AS address","warehouse.warehouse_code=address.warehouse_code","LEFT")
            ->join("pur_warehouse AS ware_tmp","ware_tmp.warehouse_code=warehouse.pertain_wms","LEFT");

        if( isset($params['warehouse_code']) && !empty($params['warehouse_code'])) {
            $query->where("warehouse.warehouse_code",$params['warehouse_code']);
        }
        if( isset($params['warehouse_name']) && !empty($params['warehouse_name'])) {
            $query->like("warehouse.warehouse_name",$params['warehouse_name']);
        }
        $query_sum = clone $query;
        $result = $query->limit($params['limit'],($params['offset']-1)*$params['limit'])->get()->result_array();
        if( !empty($result) ) {

            foreach( $result as $key=>&$value ) {

                if( $value['use_status'] == 0) {

                    $value['use_status_en'] = "停用";
                }

                if( $value['use_status'] == 1) {

                    $value['use_status_en'] = "启用";
                }

                if( $value['is_drawback'] == 0 ){

                    $value['is_drawback_ch'] = '否';
                }else{
                    $value['is_drawback_ch'] = '是';
                }

                if( $value['is_drawback'] == 0 ){

                    $value['is_drawback'] = 2;
                }else{
                    $value['is_drawback'] = 1;
                }


                $value['region_name'] =  $value['state'];
                $value['city_name'] =  $value['city'];


                if( $value['warehouse_code'] == 'AML' || $value['warehouse_code'] == 'MBB_BL' ){

                    if( $value['warehouse_type'] == 3){

                        $value['warehouse_type'] = "第三方仓库";
                    }
                    if( $value['warehouse_type'] == 2){

                        $value['warehouse_type'] = "海外仓";
                    }

                    if( $value['warehouse_type'] == 1){

                        $value['warehouse_type'] = "国内仓";
                    }

                }else{

                    $value['warehouse_type'] = "本地仓库";
                }
                $value['address'] = $value['state'].$value['city'].$value['count_name'].$value['area_name'].$value['address_message'];
                $value['purchase_type_id'] = $this->string_to_cn($value['purchase_type_id']);
            }

        }
        $total = $query_sum->get()->num_rows();
        return array(

            'list' => $result,
            'total' => $total,
            'page' =>$params['offset'],
            'limit' =>$params['limit']
        );
    }
    /**
      * 设置仓库地址
     **/

    public function set_warehouse_address( $params ) {

        $this->load->helper('status_order');
        try {
            $this->purchase_db->trans_begin();
            $update = array(

                "province_text" => $params['region_name'],
                "city_text" => $params['city_name'],
                "area_text" => $params['county_name'],
                "town_text" => $params['area_name'],
                "address" => $params['address'],
                "contacts" => isset($params['collector']) ? $params['collector'] : '',
                "contact_number" => isset($params['phone']) ? $params['phone'] : '',
                "post_code" => $params['zipcode'],
            );

            $warehouse_data_log = $this->get_warehouse_address( $params['warehouse_code'] );

            //$warehouse_data_log = $this->purchase_db->from("pur_warehouse")->where("warehouse_code",$params['warehouse_code'])->get()->row_array();
            $logs_string[]= $warehouse_data_log[0]['contacts']."|".$update['contacts'];
            $logs_string[]= $warehouse_data_log[0]['contact_number']."|".$update['contact_number'];
            $logs_string[]= $warehouse_data_log[0]['province_text'].$warehouse_data_log[0]['city_text'].$warehouse_data_log[0]['area_text'].$warehouse_data_log[0]['town_text'].$warehouse_data_log[0]['address']."|".$update['province_text'].$update['city_text'].$update['area_text'].$update['town_text'].$update['address'];
            $logs_string[]= $warehouse_data_log[0]['post_code']."|".$update['post_code'];
            $logs_string[]= $this->string_to_cn($warehouse_data_log[0]['purchase_type_id'])."|".$this->string_to_cn($params['purchase_type_id'],true);


            $result = $this->purchase_db->update("pur_warehouse_address", $update, ['warehouse_code' => $params['warehouse_code']]);
            if (!$result){
                throw new Exception('仓库地址信息更新失败');
            }
            $params['is_drawback'] = ($params['is_drawback'] ==2)?0:$params['is_drawback'];
            $result = $this->purchase_db->update("pur_warehouse", ['purchase_type_id' => $params['purchase_type_id'],'is_drawback'=>$params['is_drawback']], ['warehouse_code' => $params['warehouse_code']]);

            if (!$result){
                throw new Exception('仓库信息更新失败');
            }
            $update = array(
                "operator" => getActiveUserName(),
                "content_detail" => json_encode($logs_string),
                "record_type" => "PUR_WAREHOUSE_ADDRESS",
                "content" => $params['warehouse_code'],
                "operate_time" =>date("Y-m-d H:i:s",time())
            );
            $result_log = $this->purchase_db->insert("pur_operator_log",$update);
            if( $result && $result_log ) {

                $this->purchase_db->trans_commit();
                return True;
            }else{

                $this->purchase_db->trans_rollback();
            }
            return False;
        }catch ( Exception $exp ) {
            $this->purchase_db->trans_rollback();
            return False;
        }
    }

    public function string_to_cn($purchase_type_id,$is_check=false)
    {
        $purchase_type = query_string_to_array($purchase_type_id);
        foreach ($purchase_type as &$value){
            $value = getPurchaseType($value);
            if (empty($value) && $is_check){
                throw new Exception("请选择业务线");
            }
        }
        $purchase_type = implode(',',$purchase_type);
        return $purchase_type;
    }

    /**
     * function: 获取仓库修改日志信息
     * @param: $params     array    获取日志条件
     **/
    public function get_warehouse_log( $params ) {
        return $this->purchase_db->from("pur_operator_log")->select("content_detail,operator,operate_time")->where("record_type","PUR_WAREHOUSE_ADDRESS")->where("content",$params['warehouse_code'])->order_by(" id DESC ")->get()->result_array();
    }

    /**
     * function: 获取仓库信息
     * param : $warehouse_code    string     仓库编码
     **/
    public function get_warehouse_address_message( $warehouse_code =NULL ) {

        $query = $this->purchase_db->from("pur_warehouse_address AS address ")->join(" pur_warehouse AS warehouse","warehouse.warehouse_code=address.warehouse_code");

        if( NULL != $warehouse_code ) {
            $query->where("address.warehouse_code",$warehouse_code);
        }
        $result = $query->get()->result_array();
        return $result;
    }

    /**
     * @desc 获取运费配置
     * @author Jeff
     * @Date 2019/10/28 16:34
     * @param $warehouse_code
     * @return array
     * @return
     */
    public function get_fright_rule($warehouse_code)
    {
        $query_builder = $this->purchase_db->select('a.id,a.warehouse_code,a.area_id,a.region_code,a.first_weight_cost,
        a.additional_weight_cost,b.area_name');
        $query_builder->from($this->table_name_rule.' a');
        $query_builder->join('warehouse_area b','a.area_id=b.id');
        $query_builder->where('warehouse_code',$warehouse_code);

        $rule_list = $query_builder->get()->result_array();

        if (empty($rule_list)){
            //构造返回数据
            $query_builder = $this->purchase_db->select('id as area_id,area_name,region_code');
            $query_builder->from('warehouse_area');

            $rule_list = $query_builder->get()->result_array();
            foreach ($rule_list as $key=>&$value){
                $value['warehouse_code'] = $warehouse_code;
                $value['first_weight_cost'] = 0;//首重花费
                $value['additional_weight_cost'] = 0;//次重花费
                $value['id'] = 0;//规则id
            }
        }
        return $rule_list;
    }

    public function create_fright_rule($post_data)
    {
        $return = ['code'=>false,'msg'=>''];
        $post_data_arr = json_decode($post_data,true);
        $now_time = date('Y-m-d H:i:s',time());
        $user_name = getActiveUserName();
        $this->purchase_db->trans_begin();
        try {
            if ($post_data_arr[0]['id']==0){//新增
                $add_data = [];
                foreach ($post_data_arr as $key => &$value){
                    unset($value['id']);
                    $value['create_user_id'] = $user_name;
                    $value['create_time'] = $now_time;
                    $add_data[] = $value;
                }
                $add_res = $this->purchase_db->insert_batch($this->table_name_rule,$add_data);
                if (empty($add_res)) throw new Exception("编辑失败");
            }else{//修改
                $update_data = [];
                foreach ($post_data_arr as $key => &$value){
                    $value['modify_user_id'] = $user_name;
                    $value['modify_time'] = $now_time;
                    $update_data[] = $value;
                }
                $update_res = $this->purchase_db->update_batch($this->table_name_rule,$update_data,'id');
                if (empty($update_res)) throw new Exception("编辑失败");
            }

            $this->purchase_db->trans_commit();
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }

    /**
     * @desc 批量配置参考运费
     * @author Jeff
     * @Date 2019/10/29 17:48
     * @param $post_data
     * @param $warehouse_codes
     * @return array
     * @return
     */
    public function set_fright_rule_batch($post_data, $warehouse_codes)
    {
        $return = ['code'=>false,'msg'=>''];
        $post_data_arr = json_decode($post_data,true);
        $now_time = date('Y-m-d H:i:s',time());
        $user_name = getActiveUserName();
        $this->purchase_db->trans_begin();
        try {

            foreach ($warehouse_codes as $warehouse_code){
                $query_builder = $this->purchase_db->select('a.id,a.warehouse_code,a.area_id,a.region_code,a.first_weight_cost,a.additional_weight_cost');
                $query_builder->from($this->table_name_rule.' a');
                $query_builder->where('warehouse_code',$warehouse_code);
                $rule_list = $query_builder->get()->result_array();

                $add_data = [];
                $update_data = [];

                //判断是否存在,存在更新,不存在,插入
                if (empty($rule_list)){
                    foreach ($post_data_arr as $key => &$value){
                        unset($value['id']);
                        $value['create_user_id'] = $user_name;
                        $value['create_time'] = $now_time;
                        $value['warehouse_code'] = $warehouse_code;
                        $add_data[] = $value;
                    }
                    $add_res = $this->purchase_db->insert_batch($this->table_name_rule,$add_data);
                    if (empty($add_res)) throw new Exception("新增失败");
                }else{
                    $area_data = array_column($rule_list,'id','area_id');
                    foreach ($post_data_arr as $key => &$value){
                        $value['modify_user_id'] = $user_name;
                        $value['modify_time'] = $now_time;
                        $value['warehouse_code'] = $warehouse_code;
                        $value['id'] = $area_data[$value['area_id']];
                        $update_data[] = $value;
                    }
                    $update_res = $this->purchase_db->update_batch($this->table_name_rule,$update_data,'id');
                    if (empty($update_res)) throw new Exception("更新失败");
                }
            }
            $this->purchase_db->trans_commit();
            $return['code'] = true;
        } catch (\Exception $e) {
            $this->purchase_db->trans_rollback();
            $return['msg'] = $e->getMessage();
        }

        return $return;
    }




    /**
     * @desc 获取运费配置
     * @author Jeff
     * @Date 2019/10/28 16:34
     * @param string $warehouse_code 仓库代码
     * @param string $region_code 供应商地区编码
     * @return array
     * @return
     */
    public function get_fright_rule_by_warehouse_code($warehouse_code, $region_code)
    {
        $query_builder = $this->purchase_db->select('a.first_weight_cost,a.additional_weight_cost');
        $query_builder->from($this->table_name_rule.' a');
        $query_builder->where('warehouse_code',$warehouse_code);
        $query_builder->where('region_code',$region_code);

        $rule_list = $query_builder->get()->row_array();
        return $rule_list;
    }

    /**
     * 根据运费配置 计算参考运费
     * @author Jolon
     * @param string $warehouse_code 仓库代码
     * @param string $ship_province  供应商发货地址-省（供应商资料-发货地址）
     * @param float  $weight         总量（单位：千克）
     * @return bool|float|int|mixed  如果返回 false 表示运费配置错误，否则表示参考运费
     */
    public function get_reference_freight($warehouse_code,$ship_province,$weight){
        $freight_rule = $this->get_fright_rule_by_warehouse_code($warehouse_code,$ship_province);
        if (empty($freight_rule)){
            return false;
        }else{
            return $freight_rule['first_weight_cost']+(ceil(format_two_point_price($weight))-1)*$freight_rule['additional_weight_cost'];
        }
    }

    /**
     * 查询仓库映射信息
     * @author Manson
     * @return array
     */
    public function get_warehouse_map()
    {
        $data = $this->purchase_db->select("a.warehouse_name, a.warehouse_code, a.use_status, a.purchase_type_id, b.warehouse_type")
            ->from("pur_warehouse a")
            ->join("pur_warehouse_address b","a.warehouse_code = b.warehouse_code",'LEFT')
            ->where('use_status', 1)
            ->get()->result_array();
        return $data;

    }


    /**
     * 查询sku的总的采购在途
     * @author Manson
     * @param $sku_list
     * @return array
     */
    public function get_total_purchase_on_way($sku_list)
    {
        if (empty($sku_list)){
            return [];
        }
        $map = [];
        // 获取SKU 仓库和库存信息
        $result = $this->purchase_db->from("warehouse as warehouse")
            ->JOIN("stock AS stock","stock.warehouse_code=warehouse.warehouse_code","LEFT")
            ->select("stock.sku, SUM(stock.on_way_stock) AS on_way_stock")
            ->where_in("stock.sku",$sku_list)
            ->group_by("warehouse.warehouse_name")
            ->get()->result_array();

        if (empty($result)){
            return [];
        }
       foreach ($result as $item){
            if (isset($map[$item['sku']])){
                $map[$item['sku']] += $item['on_way_stock'];
            }else{
                $map[$item['sku']] = $item['on_way_stock'];
            }
       }
        return $map;
    }

    /**
     * 仓库code=>name
     * @author Manson
     */
    public function warehouse_code_to_name()
    {
        $result = $this->purchase_db->select('warehouse_code, warehouse_name')
            ->from($this->table_name)->get()->result_array();
        return empty($result)?[]:array_column($result,'warehouse_name','warehouse_code');
    }

    /**
     * 获取仓库推送信息
     * @params $warehouse_code   array   仓库CODE 信息
     * @author:luxu
     * @time:2020/5/21
     **/

    public function getWarehouseData($warehouse_code){

        $result = $this->purchase_db->from("warehouse")->where_in("warehouse_code",$warehouse_code)->select("is_drawback,warehouse_code")->get()->result_array();
        if(!empty($result))
        {
            return array_column($result,NULL,"warehouse_code");
        }
        return NULL;
    }

}