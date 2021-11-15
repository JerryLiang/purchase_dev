<?php
/**
 * 采购系统数据自动配置
 * User: Jolon
 * Date: 2019/12/11 16:20
 */

class Data_control_config extends MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model('system/Data_control_config_model');
    }

    /**
     * 获取 采购单自动审核配置
     * @author Jolon
     */
    public function get_auto_audit_control_config(){
        $config_type = 'PURCHASE_AUTO_AUDIT';
        $config      = $this->Data_control_config_model->get_control_config($config_type);
        $config      = isset($config['config_values'])?$config['config_values']:'';

        if(is_json($config)) $config = json_decode($config,true);

        $data_config = [
            'purchase_total_price_min'   => isset($config['purchase_total_price_min']) ? $config['purchase_total_price_min'] : '',
            'purchase_total_price_max'   => isset($config['purchase_total_price_max']) ? $config['purchase_total_price_max'] : '',
            'purchase_total_freight_max' => isset($config['purchase_total_freight_max']) ? $config['purchase_total_freight_max'] : '',
        ];

        $this->success_json($data_config);
    }

    /**
     * 设置 采购单自动审核配置
     * @author Jolon
     */
    public function set_auto_audit_control_config(){
        $config_type                = 'PURCHASE_AUTO_AUDIT';
        $purchase_total_price_min   = $this->input->get_post('purchase_total_price_min');
        $purchase_total_price_max   = $this->input->get_post('purchase_total_price_max');
        $purchase_total_freight_max = $this->input->get_post('purchase_total_freight_max');

        if(!empty($purchase_total_price_min) and !preg_match("/^\d*$/",$purchase_total_price_min)){
            $this->error_json('参数 purchase_total_price_min 必须是纯数字');
        }
        if(!empty($purchase_total_price_max) and !preg_match("/^\d*$/",$purchase_total_price_max)){
            $this->error_json('参数 purchase_total_price_max 必须是纯数字');
        }
        if(!empty($purchase_total_freight_max) and !preg_match("/^\d*$/",$purchase_total_freight_max)){
            $this->error_json('参数 purchase_total_freight_max 必须是纯数字');
        }
        $data_config = [
            'purchase_total_price_min'   => strval($purchase_total_price_min),
            'purchase_total_price_max'   => strval($purchase_total_price_max),
            'purchase_total_freight_max' => strval($purchase_total_freight_max),
        ];

        $config_values = json_encode($data_config);

        $result = $this->Data_control_config_model->set_control_config($config_type,$config_values,'采购单自动审核配置');
        if($result){
            $this->success_json();
        }else{
            $this->error_json('保存失败');
        }
    }

    /**
     * 获取 采购单自动请款设置
     * @author Jolon
     */
    public function get_auto_payout_control_config(){
        $config_type = 'PURCHASE_AUTO_PAYOUT';
        $config      = $this->Data_control_config_model->get_control_config($config_type);
        $config      = isset($config['config_values'])?$config['config_values']:'';

        if(is_json($config)) $config = json_decode($config,true);

        $data_config = [
            'switch_auto_payout'   => isset($config['switch_auto_payout']) ? $config['switch_auto_payout'] : '2',
            'execute_time_list'   => isset($config['execute_time_list']) ? $config['execute_time_list'] : []
        ];

        $data_list = [
            'data_config' => $data_config,
            'down_box_list' => [
                'switch_auto' => ['1' => '是','2' => '否']
            ]
        ];

        $this->success_json($data_list);
    }

    /**
     * 设置 采购单自动请款设置
     * @author Jolon
     */
    public function set_auto_payout_control_config(){
        $config_type                = 'PURCHASE_AUTO_PAYOUT';
        $switch_auto_payout         = $this->input->get_post('switch_auto_payout');// 开启定时任务  1.是，2.否
        $execute_time_list          = $this->input->get_post('execute_time_list');

        if($switch_auto_payout != 1 and $switch_auto_payout != 2){
            $this->error_json('请正确填写是否 开启定时任务');
        }
        if($switch_auto_payout == 1 and empty($execute_time_list) or !is_array($execute_time_list)){
            $this->error_json('请正确填写 执行时间');
        }

        if($execute_time_list){
            foreach($execute_time_list as $execute_time){
                preg_match('/^([0-1]?\d|2[0-3]):([0-5]\d):([0-5]\d)?$/', $execute_time,$matches);// 匹配日期格式
                if(empty($matches) or !isset($matches[0]) or empty($matches[0])){
                    $this->error_json('请正确填写 执行时间格式，错误格式：'.$execute_time);
                }
            }
            sort($execute_time_list);
        }

        $data_config = [
            'switch_auto_payout'   => strval($switch_auto_payout),
            'execute_time_list'    => $execute_time_list,
        ];

        $config_values = json_encode($data_config);

        $result = $this->Data_control_config_model->set_control_config($config_type,$config_values,'采购单自动请款设置');
        if($result){
            $this->success_json();
        }else{
            $this->error_json('保存失败');
        }
    }


    /**
     * 获取 1688一键下单配置
     * @author Jolon
     */
    public function get_ali_one_key_control_config(){
        $config_type = 'ALI_AUTO_ONE_KEY';
        $config      = $this->Data_control_config_model->get_control_config($config_type);
        $config      = isset($config['config_values'])?$config['config_values']:'';

        if(is_json($config)) $config = json_decode($config,true);

        $data_config = [
            'purchase_total_price_min'   => isset($config['purchase_total_price_min']) ? $config['purchase_total_price_min'] : '',
            'purchase_total_price_max'   => isset($config['purchase_total_price_max']) ? $config['purchase_total_price_max'] : '',
            'freight'                    => isset($config['freight']) ? $config['freight'] : '',
        ];

        $this->success_json($data_config);
    }


    /**
     * 设置 1688一键下单配置
     * @author Jolon
     */
    public function set_ali_one_key_control_config(){
        $config_type                = 'ALI_AUTO_ONE_KEY';
        $purchase_total_price_min   = $this->input->get_post('purchase_total_price_min');
        $purchase_total_price_max   = $this->input->get_post('purchase_total_price_max');
        $freight                    = $this->input->get_post('freight');

        if(!empty($purchase_total_price_min) and !preg_match("/^\d*$/",$purchase_total_price_min)){
            $this->error_json('参数 采购金额最小值 必须是纯数字');
        }
        if(!empty($purchase_total_price_max) and !preg_match("/^\d*$/",$purchase_total_price_max)){
            $this->error_json('参数 采购金额最大值 必须是纯数字');
        }
        if(!empty($purchase_total_price_max) and !empty($purchase_total_price_min) and $purchase_total_price_max <= $purchase_total_price_min){
            $this->error_json('参数 采购金额最大值 必须大于 采购金额最小值');
        }

        $data_config = [
            'purchase_total_price_min'   => strval($purchase_total_price_min),
            'purchase_total_price_max'   => strval($purchase_total_price_max),
            'freight'                    => strval($freight),
        ];

        $config_values = json_encode($data_config);

        $result = $this->Data_control_config_model->set_control_config($config_type,$config_values,'1688一键下单配置');
        if($result){
            $this->success_json();
        }else{
            $this->error_json('保存失败');
        }
    }

    /**
     * 获取审核配置信息
     **/
    public function getConfiguration()
    {
        $lists = $this->Data_control_config_model->getConfiguration();

        $total =  $this->Data_control_config_model->getConfigurationSum();

        if(!empty($lists))
        {
            foreach($lists as $key=>&$value)
            {
                if( $value['is_examine'] ==1)
                {
                    $value['is_examine'] = "是";
                }else{
                    $value['is_examine'] = "否";
                }
            }
        }
        $result = array(
            'list' => $lists,
            'page_data' =>['total'=>$total]

        );
        $this->success_json($result);
    }

    /**
      * 修改审核配置信息数据
     **/
    public function updateConfiguration()
    {
        $config_id = $this->input->get_post("id"); // 配置ID
        $is_examine = $this->input->get_post("is_examine"); // 配置是否需要审核， 1表示需要审核，2表示不需要审核
        $condition = $this->input->get_post("condition"); // 审核条件
        if(empty($config_id))
        {
            $this->error_json('请传入配置ID');
        }
        if(empty($is_examine) && !in_array($is_examine,[1,2])){
            $this->error_json("请选择是否要审核");
        }

        if( $condition == 1 && $condition == 0){
            $this->error_json("请填写审核限制条数");
        }
        $result = $this->Data_control_config_model->updateConfiguration($config_id,$is_examine,$condition);
        if( $result )
        {
            $this->success_json();
        }else{
            $this->error_json();
        }
    }

    /**
     * 获取下载中心日志信息
     * @author:luxu
     * @time:2020/02/24
     **/
    public function getConfiguration_log()
    {
        $config_id = $this->input->get_post("id"); // 配置ID
        $limit     =  $this->input->get_post("limit");
        $page      = $this->input->get_post("page");
        if(empty($config_id))
        {
            $this->error_json('请传入配置ID');
        }
        $result = $this->Data_control_config_model->getConfiguration_log($config_id,$limit,$page);

        if( !empty($result) )
        {
            $return_data = array();

            foreach( $result as $key=>$value)
            {
                $content = NULL;
                if( $value['old_is_examine'] != $value['new_is_examine']){

                    $content .= "审核条件由 ";
                    if( $value['old_is_examine'] ==1 ){
                        $content .= "审核修改为";
                    }
                    if( $value['old_is_examine'] ==2 ){
                        $content .= "不审核,修改为";
                    }
                    if( $value['new_is_examine'] ==1 ){
                        $content .= "审核";
                    }
                    if( $value['new_is_examine'] ==2 ){
                        $content .= "不审核";
                    }
                }

                if( $value['old_condition'] != $value['new_condition'])
                {
                    $content .="。限制条件 ";
                    if( $value['old_condition'] ==0 )
                    {
                        $content .="不限制，修改为";
                    }else{
                        $content.= $value['old_condition']."条，修改为";
                    }

                    if( $value['new_condition'] ==0 )
                    {
                        $content .="不限制";
                    }else{
                        $content.= $value['new_condition']."条";
                    }
                }

                if( $content == ""){
                    $content = "无修改";
                }
                $result[$key]['content'] = $content;
            }
            $this->success_json($result);
        }else{
            $this->error_json();
        }
    }

    public function getCenterData()
    {
        $clientData = [];
        if( !empty($_POST))
        {
            foreach($_POST as $key=>$value)
            {
                $clientData[$key] = $this->input->get_post($key);
            }
        }

        $limit = isset($clientData['limit'])?$clientData['limit']:20;
        $page = isset($clientData['offset'])?$clientData['offset']:1;
        $result = $this->Data_control_config_model->getCenterData($clientData,$limit,$page);
        $this->success_json($result);
    }

    public function getCenterBoxData()
    {


        $boxmessage = [
            'data_status'=>[['data'=>2,'message'=>'待导出'],['data'=>1,'message'=>'正在导出'],['data'=>3,'message'=>'导出完毕'],['data'=>4,'message'=>'导出失败']],
            'examine_status' => [['data'=>1,'message'=>'审核通过'],['data'=>2,'message'=>'待审核'],['data'=>3,'message'=>'驳回']]
        ];
        $result['drop_down_box'] = $boxmessage;
        $data = $this->Data_control_config_model->getCenterSelect();
        $result['drop_down_box']['user_name'] = $data['user_name'];
        $result['drop_down_box']['examine_user_name'] = $data['examine_user_name'];
        $this->success_json($result);
    }

    /**
     * 获取WEBFRONT 的传入的HTTP 信息
     * @author:luxu
     * @time:2020/3/3
     **/

    private function getClient(){

        $clientData = []; // 接受HTTP 传入的参数
        if( !empty($_POST)){
            foreach( $_POST as $key=>$value){
                $clientData[$key] = $this->input->get_post($key);
            }
        }
        return $clientData;
    }

    /**
     * 获取产品模块审核主体信息
     * @author:luxu
     * @time: 2020/3/3
     **/

    public function productAuditSubjectList()
    {
        try{

            $clientData = $this->getClient();
            // 获取主体信息
            $result = $this->Data_control_config_model->productAuditSubjectList($clientData);
            if(!empty($result))
            {
                $subjectIds = array_column( $result,"id");
                $processData = $this->Data_control_config_model->productAuditProcess($subjectIds);
                // 组装数据
                //$processData = array_column( $processData,NULL,"subject_id");
                if(!empty($processData)){
                    $processDataReturn=[];
                    foreach( $processData as $key=>$value){
                        if( !isset($processDataReturn[$value['subject_id']])){

                            $processDataReturn[$value['subject_id']] = [];
                        }
                        $provalue = json_decode($value['audit_process'],True);
                        $proccess_audit = array_map(function($data){

                            if($data['purchase'] ==1 ){
                                return $data['message'];
                            }
                        },$provalue);

                        $processDataReturn[$value['subject_id']][] = $proccess_audit;
                    }
                }

                foreach( $result as $subject_key=>$subject_value)
                {
                    $result[$subject_key]['process_message'] = NULL;
                    $result[$subject_key]['audit_message'] = "否";

                    $processData = $this->Data_control_config_model->productAuditProcess(['subjectIds'=>$subject_value['id']]);

                    if(!empty($processData)){
                        $message = [];
                        if($subject_value['audit_type_en'] == "productprice"){

                            foreach ($processData as $pr_key => $pr_value) {
                                $now_proccess = json_decode($pr_value['audit_process'], True);
                                if(!empty($now_proccess)){

                                    foreach( $now_proccess as $now_key=>$now_value){
                                        if( $now_value['purchase'] == 1){

                                            $message[] = $now_value['message'];
                                        }
                                    }
                                }
                            }
                            if (!empty($message)) {
                                $result[$subject_key]['process_message'] = implode(",", array_unique($message));
                                $result[$subject_key]['audit_message'] = "是";
                            }

                        }else {

                            foreach ($processData as $pr_key => $pr_value) {
                                $now_proccess = json_decode($pr_value['audit_process'], True);
                                $message[] = $now_proccess[0]['message'];
                            }
                            if (!empty($message)) {
                                $result[$subject_key]['process_message'] = implode(",", $message);
                                $result[$subject_key]['audit_message'] = "是";
                            }
                        }
                    }
                }
                $this->success_json($result);
            }
            throw new Exception("缺少配置数据");
        }catch ( Exception $exception )
        {
            $this->error_json($exception->getMessage());
        }
    }

    /**
     * 获取审核信息
     * @author:luxu
     * @time:2020/3/3
     **/
    public function getAuditData(){

        try {
            $clientData = $this->getClient();
            if( !isset($clientData['id']) || empty($clientData['id'])){
                throw new Exception("请传入ID");
            }
            $process = $this->Data_control_config_model->productAuditProcess(["subjectIds"=>$clientData['id']],"*","result");
            if(!empty($process)) {
                $total = count($process);
                foreach($process as $process_key=>&$process_value) {
                    if (!empty($process_value) && !empty($process_value['audit_process'])) {
                        $process_value['audit_process'] = json_decode($process_value['audit_process'], True);
                        $process[$process_key]['is_audit'] =1;
                    }else if(!empty($process_value) && empty($process_value['audit_process']) ){
                        $process[$process_key]['is_audit'] =0;
                    }
                    if( $process_key == 0 || $process_key == $total-1){
                        if(empty($process_value['audit_end']) || $process_value['audit_end']==0.000){
                            $process[$process_key]['audit_end'] = '';
                        }
                        if(empty($process_value['audit_start']) || $process_value['audit_start']==0.000){
                            $process[$process_key]['audit_start'] = '';
                        }
                    }



                }
            }
            $this->success_json($process);
        }catch ( Exception $exception){
            $this->error_json($exception->getMessage());
        }
    }

    /**
     * 编辑审核流程
     * @author:luxu
     * @time:2020/3/3
     **/

    public function updateProcess()
    {
        try{
            $clientData = $this->getClient();
            if(  !isset($clientData['data']) || !isset($clientData['subject_id']) || !isset($clientData['flag']))
            {
                throw new Exception("缺少参数");
            }
            // 审核流程数据
            $processData = json_decode($clientData['data'],True);
            // 如果HTTP 传入的为未税单价，验证金额修改
            if( $clientData['flag'] == 'productprice') {

                // 验证数据
                if( !isset($clientData['audit_start']) || !isset($clientData['audit_end'])) {

                    throw new Exception("缺少价格区间数据");
                }
            }else{

                // 其他格式验证数据
                if(  empty($clientData['data'])) {
                    $clientData['data'] = [];
                   // throw new Exception("请传入审核流程数据");
                }
            }

            $result = $this->Data_control_config_model->updateProcess($clientData['data'],$clientData['subject_id'],$clientData['flag']);
            if($result){
                $this->success_json("操作成功");
            }
        }catch ( Exception $exception )
        {

            $this->error_json($exception->getMessage());
        }
    }

    /**
       *获取角色信息
     **/
    public function getRoleMessage(){

        $roleMessage = getRoleMessage();
        $this->success_json($roleMessage);
    }




}