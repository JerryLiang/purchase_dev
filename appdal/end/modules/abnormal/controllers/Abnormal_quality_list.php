<?php
/**
 * 37539 异常处理模块，新增页面：供应商质量改善列表 #4
 * User: luxu
 * Date: 2021/07/27 10:00
 */

class Abnormal_quality_list extends MY_Controller
{
    public function __construct()
    {
        $this->load->model('Abnormal_quality_model','Abnormal_quality_model');
        $this->load->model('product/Product_line_model', 'Product_line_model');
        $this->load->helper('status_order');

        parent::__construct();

    }

    /**
     * 获取公共仓库的信息
     * @author:luxu
     * @time:2021年7月28号
     **/
    private function get_warehouse(){

        $this->load->model('warehouse/Warehouse_model');
        $pertain_wms_list = $this->Warehouse_model->get_pertain_wms_list();
        $pertain_wms_list = array_column($pertain_wms_list,'pertain_wms_name','pertain_wms_code');
        return $pertain_wms_list;
    }

    /**
     * 获取改善状态
     * @author:luxu
     * @time:2021年7月28号
     **/
    private function get_improve(){

        $result = $this->Abnormal_quality_model->get_improve();
        if(!empty($result)){
            return json_decode($result['pValue'],True);
        }
        return [];
    }

    /**
     * 获取采购系统组别
     * @param GET
     * @author:luxu
     * @time:2020/9/8 11 19
     **/
    public function getGrupData(){
        $this->load->model('user/User_group_model', 'User_group_model');
        $result['alias'] = $this->User_group_model->getGroupList([1,2]);
        $groupByData = $this->User_group_model->getGroupByData([1,2]);

        $result['overseas'] = [];

        foreach( $groupByData as $key=>$value){

            if( $value['category_id'] == 2){

                $result['overseas'][$value['value']] = $value['label'];
            }

            if( $value['category_id'] == 1){

                $result['domestic'][$value['value']] = $value['label'];
            }
        }

        return isset($result['alias'])?$result['alias']:[];
    }

    /**
     * 获取 37539 异常处理模块，新增页面：供应商质量改善列表列表数据
     * @param 无
     * @author:luxu
     * @time:2021年7月27号
     **/

    public function get_Abnormal_list_data(){

        try{
            $clientDatas = [];
            foreach($_GET as $key=>$value){

                $clientDatas[$key] = $this->input->get_post($key);
            }

            $clientDatas['limit'] = isset($clientDatas['limit'])?$clientDatas['limit']:20;
            $clientDatas['offset'] = isset($clientDatas['offset'])?$clientDatas['offset']-1:0;
            if( isset($clientDatas['group_name']) && !empty($clientDatas['group_name'])){

                $this->load->model('user/User_group_model', 'User_group_model');
                $groupids = $this->User_group_model->getGroupPersonData($clientDatas['group_name']);
                $groupdatas = [];
                if(!empty($groupids)){
                    $groupdatas = array_column($groupids,'value');
                }

                $clientDatas['groupdatas'] = $groupdatas;
            }
            $results = $this->Abnormal_quality_model->get_Abnormal_list_data($clientDatas);
            $results['drop_down_box'] = [
                'warehouse' => $this->get_warehouse(),
                'groupdata' => $this->getGrupData(),
                'down_oneline' => $this->Product_line_model->get_product_line_list_first(),
                'improved' => $this->get_improve(),
                'getAbnormalHandleType' => getWarehouseAbnormalType(),
                'getWarehouseAbnormalType' => getAbnormalDefectiveType()
            ];
            $this->success_json($results);
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 获取 37539 异常处理模块，导出
     * @param 无
     * @author:luxu
     * @time:2021年7月27号
     **/

    public function import_Abnormal_list_data(){

        try{
            $clientDatas = [];
            foreach($_POST as $key=>$value){

                $clientDatas[$key] = $this->input->get_post($key);
            }
            $this->load->model('system/Data_control_config_model');

           // $clientDatas['limit'] = isset($clientDatas['limit'])?$clientDatas['limit']:1;
           // $clientDatas['offset'] = isset($clientDatas['offset'])?$clientDatas['offset']:1;
            $results = $this->Abnormal_quality_model->get_Abnormal_list_data($clientDatas);
            $total = $results['page_data']['total'];
            try {
                $ext = 'csv';
                if($total >= 150000){

                    $this->error_json('产品管理SKU最多只能导出15万条数据，请分批导出');
                }

                $clientDatas['role_name'] = get_user_role();
                $result = $this->Data_control_config_model->insertDownData($clientDatas, 'import_abnormal', '供应商改进数据', getActiveUserName(), $ext, $total);
            } catch (Exception $exp) {
                $this->error_json($exp->getMessage());
            }
            if ($result) {
                $this->success_json("添加到下载中心");
            } else {
                $this->error_json("添加到下载中心失败");
            }

        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());
        }

    }
    /**
     * 异常处理模块，新增页面：供应商质量改善列表 #4
     * * @param 无
     * @author:luxu
     * @time:2021年7月28
     **/
    public function add_Abnoral_list_data(){

        try{

            $clientDatas = json_decode($this->input->get_post('data'),True);
            if(empty($clientDatas)){

               throw new Exception("请传入数据");
            }
            $results = $this->Abnormal_quality_model->add_Abnoral_list_data($clientDatas);
            if(True == $results){

                $this->success_json();
            }

        }catch ( Exception $exp){

            $this->error_json($exp->getMessage());
        }
    }

    /**
     * 批量处理
     * @param  POST
     * @author:luxu
     * @time:2021年7月29
     **/
    public function handler_Abnoral_list_data(){

        try{

            $clientDatas = json_decode($this->input->get_post('data'),True);
            if(empty($clientDatas)){

                throw new Exception("请传入数据");
            }

            $results = $this->Abnormal_quality_model->handler_Abnoral_list_data($clientDatas);
            $this->success_json();
        }catch ( Exception $exp){

            $this->error_json($exp->getMessage());

        }
    }

    public function Abnoral_log(){

        $clientDatas = $this->input->get_post('id');
        if(empty($clientDatas)){

            throw new Exception("请传入数据");
        }

        $results = $this->Abnormal_quality_model->Abnoral_log($clientDatas);
        $this->success_json($results);
    }

    private function get_warehouse_code($warehouseDatas,$name){

        foreach($warehouseDatas as $key=>$value){

            if($value == $name){

                return $key;
            }
        }
    }

    private function get_problem_data($data,$value){

        foreach($data as $data_key=>$data_value){

            if($value == $data_value){

                return [

                    'name' => $data_value,
                    'key' => $data_key
                ];
            }
        }

        return [];
    }

    public function get_abnormal_handler_data($data,$value){
        foreach($data as $data_key=>$data_value){

            if($value == $data_value){

                return [

                    'name' => $data_value,
                    'key' => $data_key
                ];
            }
        }

        return [];
    }


    public function push_import_Abnormal_list_data(){

        $data = file_get_contents('php://input');
        $data = json_decode($data,true);
        $data = $data['data'];

        $clientData = [];
        $warehouseDatas =$this->get_warehouse();


        $typeDatas = getAbnormalDefectiveType();
        $handlerDatas = getWarehouseAbnormalType();//getWarehouseAbnormalType

        foreach($data as $key=>$value){

            if($key == 1){
                continue;
            }

            $warehouse_code = $this->get_warehouse_code($warehouseDatas,$value['A']);
            $data = $this->get_problem_data($handlerDatas,$value['D']);
            $abnormal_id = $abnormal_name = $problem_id = $problem_name='';
            if(!empty($data)){
                $abnormal_id = $data['key'];
                $abnormal_name = $data['name'];
            }
            $handlerData = $this->get_abnormal_handler_data($typeDatas,$value['E']);
            if(!empty($handlerData)){
                $problem_id = $handlerData['key'];
                $problem_name = $handlerData['name'];
            }
            $clientData[] = [

                'warehouse_name' => $value['A'],
                'supplier_name' => $value['B'],
                'supplier_code' => $value['C'],
                'supplier_reply' =>$value['F'],
                'warehouse_code' => $warehouse_code,
                'abnormal_id' => !empty($abnormal_id)?$abnormal_id:0,
                'defective_name' =>!empty($abnormal_name)?$abnormal_name:'',
                'problem_id' => !empty($problem_id)?$problem_id:0,
                'problem_name' => !empty($problem_name)?$problem_name:''
            ];

        }

        try {
            $results = $this->Abnormal_quality_model->add_Abnoral_list_data($clientData);
            if (True == $results) {

                $this->success_json();
            }
        }catch ( Exception $exp ){
            $this->error_json($exp->getMessage());


        }


    }
}