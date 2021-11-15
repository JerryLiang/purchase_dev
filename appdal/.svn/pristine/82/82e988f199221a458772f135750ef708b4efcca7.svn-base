<?php
/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Warehouse extends MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('warehouse_model');
    }

    /**
     * 仓库 相关状态 下拉框列表
     * @author  Jolon
     * @param  string $status_type
     * @param bool $get_all
     * @return array|bool|mixed|null
     */
    public function status_list($status_type,$get_all = false){
        $this->load->helper('status_product');

        if($get_all){
            $status_type = ['warehouse_type'];
        }else{
            $status_type = [$status_type];
        }

        $data_list_all = [];
        foreach($status_type as $v){
            switch(strtolower($v)){
                case 'warehouse_type':
                    $data_list = getWarehouseType();
                    break;

                default :
                    $data_list        = null;
            }
            if($get_all){
                $data_list_all[$v] = $data_list;
            }else{
                $data_list_all = $data_list;
            }
        }
        return $data_list_all;
    }

    /**
     * 仓库 相关状态 下拉框列表
     * @author  Jolon
     * @return mixed
     */
    public function get_status_list(){
        $status_type  = $this->input->get_post('type');
        $get_all     = $this->input->get_post('get_all');

        $data_list = $this->status_list($status_type,$get_all);
        if($data_list){
            $this->success_json($data_list);
        }else{
            $this->error_json('未知的状态类型');
        }
    }


    /**
     * 获取仓库搜索框列表
     * @author  Jolon
     * @return mixed
     */
    public function get_search_list(){
        $warehouse_type  = $this->input->get_post('warehouse_type');
        $list = $this->warehouse_model->get_warehouse_list(['warehouse_type' => $warehouse_type]);

        if($list){
            $list_tmp = [];
            foreach($list as $v_list){
                $v_list_tmp = [];
                $v_list_tmp['id']             = $v_list['id'];
                $v_list_tmp['warehouse_code'] = $v_list['warehouse_code'];
                $v_list_tmp['warehouse_name'] = $v_list['warehouse_name'];

                $list_tmp[$v_list['warehouse_code']] = $v_list_tmp;
            }
            $list = $list_tmp;
        }

        $this->success_json(['value' => $list]);
    }

    /**
      * 物流系统获取仓库信息
     **/
    public function get_warehouse_data() {

        $warehouse_code = $this->input->get_post('warehouse_code');
        $list_result = $this->warehouse_model->get_warehouse_data( $warehouse_code );
        $this->success_json();
    }

    /**
      * 获取仓库数据
     **/
    public function get_warehouse_list() {

        //$warehouse_code = $this->input->get_post('warehouse_code');
        $warehouse_code = [];
        foreach($_GET as $key=>$value) {

            $warehouse_code[$key] = $this->input->get_post($key);
        }
        $result = $this->warehouse_model->get_warehouse_list_data($warehouse_code);
        $this->success_json([
            'purchase_type'     => getPurchaseType(),
            'list'              =>$result['list'],
            'total'             =>$result['total'],
            'page'              =>$result['page'],
            'limit'             =>$result['limit']
        ]);
    }

    public function set_warehouse_address() {

        try{

            $params = [];
            foreach( $_POST as $key=>$value ) {

                $params[$key] = $this->input->get_post($key);
            }
            if( !isset($params['warehouse_code']) ) {

                throw new Exception("请传入仓库编号");
            }

            if( !isset($params['region_name']) || !isset($params['city_name']) || !isset($params['county_name']) || !isset($params['area_name'])) {

                throw new Exception("请填写省市县区信息");
            }

            if( !isset($params['address']) || empty($params['address']) ) {

                throw new Exception("请填写详细地址");
            }

            if( !isset($params['zipcode']) && empty($params['zipcode'])) {

                throw new Exception("请填写邮编");
            }

            if( !isset($params['purchase_type_id']) && empty($params['purchase_type_id'])) {

                throw new Exception("请选择业务线");
            }


            $result = $this->warehouse_model->set_warehouse_address($params);

            if( $result ) {

                $this->success_json("设置成功");
            }else{
                $this->error_json("设置失败");
            }
        }catch ( Exception $exp ) {

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取仓库信息修改信息日志
     **/

    public function get_warehouse_log(){

        $params = [];
        foreach( $_POST as $key=>$value ) {

            $params[$key] = $this->input->get_post($key);
        }

        $result = $this->warehouse_model->get_warehouse_log($params);

        $logs = [];
        if( !empty($result) ) {

            foreach( $result as $key=>$value ) {
               $log_message = json_decode( $value['content_detail'],True);
               $logs[$key]['operaton_name'] = $value['operator'];
               $logs[$key]['operator_time'] = $value['operate_time'];
               if( !empty( $log_message) ) {

                   $collects = explode("|",$log_message[0]);
                       $logs[$key]['collector']['old'] = $collects[0];
                       $logs[$key]['collector']['new'] = $collects[1];

                   $phone = explode("|",$log_message[1]);
                       $logs[$key]['phone']['old'] = $phone[0];
                       $logs[$key]['phone']['new'] = $phone[1];

                   $address = explode("|",$log_message[2]);
                       $logs[$key]['address']['old'] = $address[0];
                       $logs[$key]['address']['new'] = $address[1];

                   $zipcode = explode("|",$log_message[3]);

                       $logs[$key]['zipcode']['old'] = $zipcode[0];
                       $logs[$key]['zipcode']['new'] = $zipcode[1];

                   if( isset($log_message[4])) {
                       $purchase_type_id = explode("|",$log_message[4]);

                       $logs[$key]['purchase_type_id']['old'] = $purchase_type_id[0];
                       $logs[$key]['purchase_type_id']['new'] = $purchase_type_id[1];
                   }else{
                       $logs[$key]['purchase_type_id']['old'] = '';
                       $logs[$key]['purchase_type_id']['new'] = '';
                   }


               }
            }
        }
        $this->success_json(['list'=>$logs]);

    }

    /**
     * @desc 获取运费配置
     * @author Jeff
     * @Date 2019/10/28 15:17
     * @return
     */
    public function get_fright_rule()
    {
        $warehouse_code = $this->input->get_post('warehouse_code');//仓库id
        if (empty($warehouse_code)) $this->error_json("仓库code缺失");
        $result = $this->warehouse_model->get_fright_rule($warehouse_code);
        $this->success_json(['list'=>$result]);

    }

    /**
     * @desc 创建运费配置
     * @author Jeff
     * @Date 2019/10/28 16:34
     * @return
     */
    public function create_fright_rule()
    {
        $post_data = $this->input->get_post('post_data');//编辑数据 json
        if (empty($post_data)) $this->error_json("参数缺失");
        $result = $this->warehouse_model->create_fright_rule($post_data);
        if ($result['code']){
            $this->success_json([],null,'编辑成功');
        }else{
            $this->error_json('编辑失败');
        }
    }

    /**
     * @desc 批量配置参考运费
     * @author Jeff
     * @Date 2019/10/28 16:34
     * @return
     */
    public function set_fright_rule_batch()
    {
        $post_data = $this->input->get_post('post_data');//编辑数据 json
        $warehouse_code = $this->input->get_post('warehouse_codes');//仓库code
        if (empty($warehouse_code)) $this->error_json("参数缺失");
        if (empty($post_data)) $this->error_json("参数缺失");

        $warehouse_code_arr = explode(',',$warehouse_code);
        $warehouse_code_arr = array_unique($warehouse_code_arr);

        $result = $this->warehouse_model->set_fright_rule_batch($post_data, $warehouse_code_arr);
        if ($result['code']){
            $this->success_json([],null,'编辑成功');
        }else{
            $this->error_json($result['msg']);
        }
    }


}