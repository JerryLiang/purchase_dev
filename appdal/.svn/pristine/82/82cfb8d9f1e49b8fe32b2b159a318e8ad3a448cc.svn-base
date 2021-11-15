<?php

/**
 * Created by PhpStorm.
 * 数据控制配置项
 * User: Jolon
 * Date: 2019/12/11 16:20
 * Time: 14:17
 */
class Data_control_config_model extends Purchase_model {
    protected $table_name = "data_control_config";
    protected $Configuration_table_name = "data_center_configuration";
    protected $Configuration_table_name_log="update_data_center_configuration"; // 下载中心配置修改日志表
    protected $data_table_name = 'center_data';// 数据表名称
    protected $product_audit_subject = 'product_audit_subject';  // 产品管理审核主体表
    protected $product_audit_process = 'product_audit_process';  // 产品审核流程数据表

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
    }

    /**
     * 获取 配置数据
     * @param string $config_type
     * @return array
     */
    public function get_control_config($config_type){
        $config = $this->purchase_db->where('config_type', $config_type)->get($this->table_name)->row_array();

        return $config;
    }

    public function getConfiguration($id = NULL)
    {
        $query = $this->purchase_db->from($this->Configuration_table_name);
        if( NULL != $id )
        {
            $result = $query->where("id",$id)->get()->row_array();
        }else {
            $result = $query->get()->result_array();
        }
        return $result;
    }

    public function getConfigurationSum($id = NULL )
    {
        $query = $this->purchase_db->from($this->Configuration_table_name);
        if( NULL != $id )
        {
            $sum = $query->where("id",$id)->count_all_results();
        }else {
            $sum = $query->count_all_results();
        }
        return $sum;
    }

    /**
     * 修改配置信息
     *@param：  $config_id  int   配置ID
     *          $is_exmaine tinyint  是否需要审核1表示需要审核，2表示不需要审核
     *          $condition  int   审核条件
     * @author:luxu
     * @time:2020/02/24
     **/
    public function updateConfiguration($config_id,$is_examine,$condition)
    {
        $log_configuration_message = $this->getConfiguration($config_id);
        $logs_data = array(

            "old_is_examine" => $log_configuration_message['is_examine'],
            "new_is_examine" => $is_examine,
            "old_condition" => $log_configuration_message['examine_condition'],
            "new_condition" => $condition,
            "update_user"   =>getActiveUserName(),
            "update_time" => date("Y-m-d H:i:s"),
            "config_id"  => $config_id
        );

        try {

            $this->purchase_db->trans_begin();
            $result = $this->purchase_db->where("id",$config_id)->update($this->Configuration_table_name,['is_examine'=>$is_examine,'examine_condition'=>$condition,'update_time'=>date("Y-m-d H:i:s"),'update_user'=>getActiveUserName()]);
            $logs_result = $this->purchase_db->insert($this->Configuration_table_name_log,$logs_data);
            if( $result && $logs_result ){
                $this->purchase_db->trans_commit();
                return True;
            }
            throw new Exception("更新失败");

        }catch ( Exception $exception )
        {
            $this->purchase_db->trans_rollback();
            throw new Exception( $exception->getMessage());
        }
    }

    /**
     * 获取下载中心配置日志信息
     * @param $config_id     int    配置ID
     *        $limit         int    多少条数据
     *        $page          int    第几页
     * @author:luxu
     * @time:2020/02/24
     **/

    public function getConfiguration_log($config_id,$limit,$page){

        $result = $this->purchase_db->from($this->Configuration_table_name_log)->where("config_id",$config_id)->order_by("id DESC")->limit($limit,($page-1)*$limit)->get()->result_array();
        return $result;

    }

    public function getCenterSelect()
    {

        $get_add_user_name = " SELECT add_user_name FROM pur_".$this->data_table_name." GROUP BY add_user_name";
        $add_user_name = $this->purchase_db->from($this->table_name)->query($get_add_user_name)->result_array();

        $get_examine_user_name = " SELECT examine_user_name FROM pur_".$this->data_table_name." GROUP BY examine_user_name";
        $examine_user_name = $this->purchase_db->from($this->table_name)->query($get_examine_user_name)->result_array();

        return array(

            'user_name' => $add_user_name,
            'examine_user_name' => $examine_user_name
        );
    }

    public function getCenterData($clientData,$limit=20,$page=1)
    {
        $user_id = jurisdiction(); //当前登录用户ID
        $role_name = get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role_name, $data_role);
//        $query_builder = $this->purchase_db;
        $query = $this->purchase_db->from("center_data");

        if( !(!empty($res_arr) OR $user_id === true )){
                //$query_builder->where_in('ppo.buyer_id', $user_id);
            $query->where_in("user_id", $user_id);
            }

        // HTTP 客户端传入模块名称
        if(isset($clientData['module_cn_name']) && !empty($clientData['module_cn_name']))
        {
            $module_cn_names = explode(" ",$clientData['module_cn_name']);
            $query->where_in("module_cn_name",$module_cn_names);
        }

        if( isset($clientData['file_name']) && !empty($clientData['file_name']))
        {
            //$query->where("file_name",$clientData['file_name']);
            $query->where('file_name',$clientData['file_name']);
        }
        // 导出状态
        if( isset($clientData['data_status']) && !empty($clientData['data_status']))
        {
            $query->where("data_status",$clientData['data_status']);
        }

        // 审核状态
        if( isset($clientData['examine_status']) && !empty($clientData['examine_status']))
        {
            $query->where("examine_status",$clientData['examine_status']);
        }
        // 操作人
        if( isset($clientData['add_user_name']) && !empty($clientData['add_user_name']))
        {
            $query->where_in("add_user_name",$clientData['add_user_name']);
        }
        // 审核人
        if( isset($clientData['examine_user_name']) && !empty($clientData['examine_user_name'])){
            $query->where_in("examine_user_name",$clientData['examine_user_name']);
        }

        // 创建时间
        if( isset($clientData['add_time_start']) && isset($clientData['add_time_end']))
        {
            $query->where("add_time>=",$clientData['add_time_start'])->where("add_time<=",$clientData['add_time_end']);
        }
        // 审核时间
        if( isset($clientData['examine_time_start']) && isset($clientData['examine_time_end']))
        {
            $query->where("examine_time>=",$clientData['examine_time_start'])->where("examine_time<=",$clientData['examine_time_end']);
        }
        $clone_db = clone($this->purchase_db);
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数
        $this->purchase_db=$clone_db;
        $result = $this->purchase_db->limit($limit,($page-1)*$limit)->order_by("id DESC ")->get()->result_array();
        if( !empty($result) )
        {
            foreach($result as $key=>$value)
            {
                if( !empty($value['number'])) {
                    $value['progress'] = (empty($value['progress']))?0:$value['progress'];
                    $result[$key]['speed_of_progress'] = round((($value['progress'] / $value['number']) * 100),3) . "%";
                }else{
                    $result[$key]['speed_of_progress'] =0;
                }
                switch($value['data_status'])
                {
                    case 1:
                        $result[$key]['data_status_ch'] ="正在导出";
                        break;
                    case 2:
                        $result[$key]['data_status_ch'] = "待导出";
                        break;
                    case 3:
                        $result[$key]['data_status_ch'] = "导出完毕";
                        break;
                }

                switch($value['examine_status'])
                {
                    case 1:
                        $result[$key]['examine_status_ch'] ="审核通过";
                        break;
                    case 2:
                        $result[$key]['examine_status_ch'] = "待审核";
                        break;
                    case 3:
                        $result[$key]['examine_status_ch'] = "审核驳回";
                        break;
                }

                $result[$key]['name'] = NULL;
                if( !empty($value['file_name']))
                {
                    $result[$key]['name'] =basename($value['file_name']);
                }
//                $Progress_percentage =0;
//                if(!empty($value['progress']) && !empty($value['number'])){
//
//                    $Progress_percentage = (($value['progress']/$value['number'])*100);
//                }
//                $result[$key]['Progress_percentage'] =$Progress_percentage;

            }
        }
        return $data = array(

            'list' => $result,
            'page_data' =>['total'=> $total_count]
        );
    }


    /**
     * 获取 配置数据的数据值
     * @param string $config_type
     * @return array
     */
    public function get_control_config_values($config_type){
        $config         = $this->Data_control_config_model->get_control_config($config_type);
        $config_values  = isset($config['config_values'])?$config['config_values']:null;
        return $config_values;
    }

    /**
     * 新增 或 更新 配置数据
     * @param string $config_type
     * @param string $config_values
     * @param string $config_remark
     * @return bool
     */
    public function set_control_config($config_type,$config_values,$config_remark = ''){
        $config = $this->purchase_db->select('id')->where('config_type',$config_type)->get($this->table_name)->row_array();

        if($config){
            $result = $this->purchase_db->where('id',$config['id'])->update($this->table_name,['config_values' => $config_values]);
        }else{
            $insert_data = [
                'config_type'   => $config_type,
                'config_values' => $config_values,
                'config_remark' => $config_remark
            ];

            $result = $this->purchase_db->insert($this->table_name,$insert_data);
        }

        return $result;
    }


    /**
     * 插入下载数据
     * @param  $data    array  下载条件
     *         $modules_en_name   string  模块名称 英文
     *         $modules_ch_name   string  模块名称 中文
     * @author:luxu
     * @time:2020/02/26
     **/
    public function insertDownData($data,$modules_en_name,$modules_ch_name,$add_user_name,$ext='csv',$total=0)
    {
        try {

            $insertData = array(
                'swoole_server' => SWOOLE_SERVER,
                'module_cn_name' => $modules_ch_name,
                'module_ch_name' => $modules_en_name,
                'examine_status' => 2,
                'add_time' => date("Y-m-d H:i:s", time()),
                'add_user_name' => $add_user_name,
                'condition' => $data,
                'ext' => $ext,
                'number' => $total,
                'data_status' => 2,
                'role' => implode(",",getRole()),
                'user_id' => getActiveUserId(),
                'role_name' => implode(",",get_user_role())
            );
            $modules_config = $this->purchase_db->from($this->Configuration_table_name)->where("modules_ch_name", $modules_en_name)->where("is_examine", 1)->select("examine_condition")->get()->row_array();
            if (
            (!empty($modules_config)
                && isset($modules_config['examine_condition'])
                && $modules_config['examine_condition'] > $total)
            || (empty($modules_config))
            ) {

                $this->load->model('system/Data_center_model');
                $downNums = $this->Data_center_model->get_items("data_status=1 and swoole_server='".SWOOLE_SERVER."'");
                $down_num = count($downNums);
                //限制5个以内的导出任务
                $t_total = 5;
                if( $down_num>=$t_total) {
                    $message = "有" . $down_num . ",个导出任务在执行，详情查看下载中心任务列表";
                    throw new Exception($message);
                }


                $insertData['condition'] = json_encode($insertData['condition']);
                $this->load->model('system/Data_center_model');
            //    $this->Data_center_model->handle_quene_data($data);
//            // 直接推送MQ
                $insertData['examine_status'] = 1;
                $insertData['data_status'] = 1;
                $result = $this->purchase_db->insert($this->data_table_name, $insertData);
                $lastId = $this->purchase_db->insert_id($this->data_table_name);
                $insertData['id'] = $lastId;
                $insertData['condition'] = json_decode($insertData['condition'],True);
                $mq = new Rabbitmq();
                //设置参数
                $mq->setQueueName('PURCHASE_DATA_DOWN');
                $mq->setExchangeName('EXPORTLIST');
                $mq->setRouteKey('PURCHASE_DATA_DOWN_ON_WAY_R_KEY');
                $mq->setType(AMQP_EX_TYPE_DIRECT);
    //            //构造存入数据 +
                $push_data = [
                    'data' => $insertData
                ];

                //存入消息队列
                $mq->sendMessage($push_data);
                $this->Data_center_model->handle_quene_data();

            }else{

//                // 如果审核条件为空表示设置不需要审核
//                if(empty($modules_config)) {
//                    $insertData['examine_status'] = 1;
//                    $insertData['data_status'] = 1;
//                }
                $insertData['condition'] = json_encode($insertData['condition']);
                $result = $this->purchase_db->insert($this->data_table_name, $insertData);
            }

            return $result;
        }catch ( Exception $exception ){
            throw new Exception($exception->getMessage());
        }
    }

    /**
     * 获取产品管理模块审核主体信息
     * @param  $clientData    array    HTTP 传入的查询参数
     * @author:luxu
     * @time:2020/3/3
     **/

    public function productAuditSubjectList($clientData){

        $query = $this->purchase_db->from($this->product_audit_subject);
        if( isset($clientData['id']) && !empty($clientData['id'])){
            $query->where_in("id",$clientData['id']);
        }
        $result = $query->get()->result_array();
        return $result;
    }

    /**
     * 获取产品模块审核进度信息
     * @param:  $searchData    array  查询条件
     * @author: luxu
     * @time: 2020/3/
     **/

    public function productAuditProcess($searchData,$searchString ="id,audit_process,subject_id",$result_data="result"){
        $query = $this->purchase_db->from($this->product_audit_process)->where("status",1);
        // 如果查询传入主体表ID
        if( isset($searchData) && !empty($searchData['subjectIds'])){
            $query->where_in("subject_id",$searchData['subjectIds']);
        }

        if( NULL != $searchString){
            $query->select($searchString);
        }

        if( "result" == $result_data ){
            $result = $query->get()->result_array();
        }

        if( "row" == $result_data){
            $result = $query->get()->row_array();
        }
        return $result;
    }

    /**
     * 编辑审核流程
     * @author:luxu
     * @time:2020/3/3
     **/
    public function updateProcess($processData,$subjectIds,$flag) {

        try{

            $searchResult = $this->purchase_db->from($this->product_audit_process)->where("subject_en",$flag)->where("subject_id",$subjectIds)->get()->row_array();
            $insertData = [];
            if(!empty($processData)) {
                $processData = json_decode($processData, True);
                foreach( $processData as $pr_key=>$pr_value) {

                    $insertData[] = array(

                        'audit_start' => isset($pr_value[0]['audit_start'])?$pr_value[0]['audit_start']:0,
                        'audit_end'   => isset($pr_value[0]['audit_end'])?$pr_value[0]['audit_end']:0,
                        'audit_process' => json_encode($pr_value),
                        'subject_id'   => $subjectIds,
                        'subject_en'   => $flag
                    );
                }
            }

            if(empty($searchResult)){
                $result = True;
                if(!empty($insertData)) {
                    // 如果没有记录就执行插入操作
                    $result = $this->purchase_db->insert_batch($this->product_audit_process, $insertData);
                }
                if(!$result){
                    throw new Exception("操作失败");
                }
                $subject_data = [

                    'audit_name'=>getActiveUserName(),
                    'update_time'=>date("Y-m-d H:i:s",time())
                ];
                $this->purchase_db->where("audit_type_en",$flag)->update($this->product_audit_subject,$subject_data);
                return True;
            }else{

                // 删除后再插入，开启事务
                    $this->purchase_db->trans_begin();
                    $deleteData = $this->purchase_db->where("subject_en", $flag)->where("subject_id", $subjectIds)->delete($this->product_audit_process);
                    if(!empty($insertData)) {
                        $result = $this->purchase_db->insert_batch($this->product_audit_process, $insertData);
                    }else{
                        $result = True;
                    }
                    if ($deleteData && $result) {
                        $this->purchase_db->trans_commit();
                        $subject_data = [

                            'audit_name'=>getActiveUserName(),
                            'update_time'=>date("Y-m-d H:i:s",time())
                        ];
                        $this->purchase_db->where("audit_type_en",$flag)->update($this->product_audit_subject,$subject_data);
                        return True;
                    } else {
                        $this->purchase_db->trans_rollback();
                        throw new Exception('事务提交操作失败');
                    }
            }
        }catch ( Exception $exception ){

                throw new Exception($exception->getMessage());
        }
    }



}
