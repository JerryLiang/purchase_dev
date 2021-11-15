<?php

/**
 * 少数少款MODEL
 * User: luxu
 * Date: 2020/11/25
 */
class Purchase_order_lack extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_order_lack_model','lack');

    }

    /**
      * 获取HTTP 客户端POST 参数
     **/
    protected  function get_post(){

        if(!empty($_POST)){

            $postData = [];

            foreach( $_POST as $key=>$value){

                $postData[$key] = $this->input->get_post($key);
            }

            return $postData;
        }

        return NULL;
    }

    public function getGroupName(){

        $this->load->model('user/User_group_model', 'User_group_model');
        $result['alias'] = $this->User_group_model->getGroupList([1,2]);

        return $result['alias'];
    }

    /**
     * 获取少数少款列表数据
     * @param:HTTP 传值
     * @author:luxu
     * @time:2020/11/27
     **/
    public function getLackData(){

        try{
            //boxdata
            $grupName = $this->getGroupName();

            $clientData = [];

            if( !empty($_GET)){

                foreach( $_GET as $key=>$value){

                    $clientData[$key] = $this->input->get_post($key);
                }
            }
            $page = isset($clientData['offset'])?$clientData['offset']:1;
            $limit = isset($clientData['limit'])?$clientData['limit']:20;
            if (empty($page) or $page < 0){
                $page = 1;
            }

            if( isset($clientData['groupname']) && !empty($clientData['groupname'])){

                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($clientData['groupname']);
                $groupdatas = [];
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                }

                $clientData['groupdatas'] = $groupdatas;
            }
            $limit = query_limit_range($limit);
            $offset = ($page - 1) * $limit;
            $result = $this->lack->getLackData($clientData,$offset,$limit);
            $role_name=get_user_role();//当前登录角色
            $data_role= getRolexiao();
            $result = ShieldingData($result,['supplier_name','supplier_code'],$role_name,NULL);
            $result['boxdata']['groupname'] = $this->getGroupName();
            $result['boxdata']['warehouse'] = getWarehouse();

            $this->load->model('supplier/Supplier_settlement_model', 'settlementModel');
            //下拉列表供应商结算方式
            $data = $this->settlementModel->get_settlement();
            $result['boxdata']['account_list']=  isset($data['list'])?$data['list']:[];
            //1合同 2网络【默认】 3账期采购
            $result['boxdata']['source'] = ['1'=>'合同','2'=>'网采'];
            $result['boxdata']['is_gateway'] = ['1'=>'是','0'=>'否'];
            $this->success_json($result);

        }catch ( Exception $exp ){

            echo $exp->getMessage();
        }
    }

    /**
     * 导出少数少款数据接口(CSV 下载)
     * @param
     * @author:luxu
     * @time:2021年1月10号
     **/

    public function exportData_csv(){

        $clientData = [];

        if( !empty($_GET)){

            if(!isset($_GET['ids'])) {
                foreach ($_GET as $key => $value) {

                    $clientData[$key] = $this->input->get_post($key);
                }
            }else{
                $clientData['ids'] = $this->input->get_post('ids');
            }
        }

        $result = $this->lack->getLackData($clientData,0,1);

        $total = $result['pages']['total_all'];

        $this->load->model('system/Data_control_config_model');

        $ext = $clientData['is_csv'] == 1?'csv':'excel';
        $clientData['ext'] = "csv";
        $result = true;
        try {
            $clientData['role_name'] = get_user_role();
            $result = $this->Data_control_config_model->insertDownData($clientData, 'LACK', '少数少款导出', getActiveUserName(), $ext, $total);
        } catch (Exception $exp) {
            $this->error_json($exp->getMessage());
        }
        if ($result) {
            $this->success_json("添加到下载中心");
        } else {
            $this->error_json("添加到下载中心失败");
        }

        die();


    }


    /**
         * 需求:少数规则
             说明：少数少款类型为“少数”的，判断备货单中的SKU采购数量和累计实际到货数量；
                  1、采购数量小于配置数值，少数的标记处理状态为“未处理”；
                  2、采购数量大于等于配置数值，且实际到货数量大于等于（采购数量*50%），少数的处理状态为“未处理”；
                  3、采购数量大于等于配置数值，且实际到货数量小于（采购数量*50%），少数的处理状态为“分批次到货”；
         * 说明：少数少款类型为“少款”的，判断采购单中少款的SKU数量；

         1、判断采购单中的SKU个数在左边数据的哪个区间内；
         2、判断采购单中少款的SKU个数大于右边配置的数量，该采购单中少款的备货单都标记为“分批次到货”；
         3、判断采购单中少款的SKU个数小于等于右边配置的数量，该采购单中少款的备货单都标记为“未处理”；
         * @author:luxu
         * @time: 2020/11/25
     **/
    public function saveConfigData(){

        try{

            $clientData = $this->get_post();

            if(empty($clientData) || !isset($clientData['number']) || !isset($clientData['style'])){

                throw new Exception("缺少参数");
            }


            $result = $this->lack->saveConfig($clientData);
            if($result){
                $this->success_json();
            }

            $this->error_json();

        }catch ( Exception $exp ){

                $this->error_json($exp->getMessage());
        }
    }

    /**
     * 读取少数少款配置信息
     * @param  wu
     * @author:luxu
     * @time: 2020/11/26
     **/
    public function getConfigData(){

        try{

            $result = $this->lack->getConfig();
            $this->success_json($result);
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    public function pushLackData(){

        $this->lack->pushLackData();
    }

    /**
     * 设置分批次接口
     * @param
     * @author :luxu
     * @time: 2020/11/28
     **/
    public function setBatches(){

        try{
            $ids = $this->input->get_post("ids");
            if(empty($ids)){
                throw new Exception("请传入参数");
            }

            $result = $this->lack->setBatches($ids);
            if( True == $result){
                $this->success_json();
            }
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 设置退款接口
     * @param
     * @author :luxu
     * @time: 2020/11/28
     **/
    public function setMoney(){

        try{

            $ids = $this->input->get_post("ids");
            if(empty($ids)){
                throw new Exception("请传入参数");
            }

            $result = $this->lack->setMoney($ids);
            if( True == $result){
                $this->success_json();
            }
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取日志信息
     * @param
     * @author:luxu
     * @time:2020/11/30
     **/
    public function getLogs(){

        try{

            $lackIds = $this->input->get_post('id');
            if(empty($lackIds)){
                throw new Exception("getlackdata传入参数");
            }

            $result = $this->lack->getLogs($lackIds);
            if(!empty($result)){
                foreach($result as $key=>&$value) {
                    if(is_object(json_decode($value['logs']))){
                        $logs = json_decode($value['logs'], true);
                        if($logs['new_processing'] == 3) {

                            $value['type'] = "标记分批次到货";
                            $value['logs'] = '';
                        }
                        if($logs['new_processing'] == 4) {

                            $value['type'] = "标记订单已退款";
                            $value['logs'] = '';
                        }
                    }else{
                        $value['type'] = "批量添加备注";
                        $value['logs'] = $value['logs'];

                    }
                }
            }
            $this->success_json($result);
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 添加少数少款备注功能
     * @param
     * @author:luxu
     * @time:2021年1月10号
     **/
    public function setLockMessage(){

        try{

            $lockIds = $this->input->get_post("ids");
            if( empty($lockIds) && !is_array($lockIds)){
                throw new Exception( "请传入少数少款id");
            }
            $message = $this->input->get_post("message");
            if(empty($message)){
                throw new Exception( "请填写备注信息");
            }
            $imageUrl = $this->input->get_post('imageurl');

            $result = $this->lack->setLockMessage($lockIds,$message,$imageUrl);
            if($result == true){

                $this->success_json();
            }
        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }
}