<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class Abnormal_api extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('abnormal/Abnormal_list_model','abnormal_model');
        $this->load->model('purchase/Purchase_order_model','purchase_order_model');
        $this->load->model('user/Merchandiser_group_model','Merchandiser_group_model');
        $this->load->helper('abnormal');
        $this->load->helper('common');
    }

    /**
     * 添加异常数据(仓库系统调取)
     * /abnormal_api/add_abnormal_data
     * @author Jaxton 2019/01/28
     */
    public function add_abnormal_data(){
        $bind_warehouse_set = ['SZ_AA'=>'塘厦组','HM_AA'=>'虎门组'];//仓库配置

        $params = $this->input->get_post('quality_abnormal_data');
        if (empty($params)){
            echo json_encode(['status'=>0,'message'=>'没有获取到提交的数据[1]']);
            exit;
        }

        if (isset($params['quality_abnormal_data']) && !empty($params['quality_abnormal_data'])){
            $params = $params['quality_abnormal_data'];
        }

        $ci = get_instance();
        $ci->load->config('mongodb');
        $host = $ci->config->item('mongo_host');
        $port = $ci->config->item('mongo_port');
        $user = $ci->config->item('mongo_user');
        $password = $ci->config->item('mongo_pass');
        $author_db = $ci->config->item('mongo_db');
        $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $author_db = $author_db;


        $datas = json_decode($params,true);
        if (empty($datas)){
            echo json_encode(['status'=>0,'message'=>'没有获取到提交的数据[2]']);
            exit;
        }

        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');

        $insert_arr = [];
        $update_arr = [];
        $success_list = [];
        foreach ($datas as $item){

            $bulk = new MongoDB\Driver\BulkWrite();

            $mongodb_result = $bulk->insert($item);
            $mongodb->executeBulkWrite("{$author_db}.normal", $bulk);
            if (!empty($item['defective_id'])){
                if (!empty($item['create_time'])){
                    if (is_numeric($item['create_time'])){
                        $create_time = date('Y-m-d H:i:s',$item['create_time']);
                    }else{
                        $create_time = $item['create_time'];
                    }
                }else{
                    $create_time = date('Y-m-d H:i:s');
                }

                $warehouse_code = isset($item['warehouse_code'])?$item['warehouse_code']:'';

                if(!isset($warehouse_list[$warehouse_code])){
                    echo json_encode(['status'=>0,'message'=>$item['defective_id'].'仓库错误']);
                    exit;
                }
                $warehouse_name = $warehouse_list[$warehouse_code];

                $params=[
                    'sku' => $item['sku']??'',
                    'num' => $item['num']??0,//数量
                    'position' => !empty($item['abnormal_position'])?$item['abnormal_position']:'',//异常货位
                    'defective_type' => $item['defective_type']??0,//次品类型
                    'defective_id' => $item['defective_id'],//异常单号
                    'purchase_order_no' => $item['purchase_order_no']??'',//采购单号
                    'express_code' => $item['express_code']??'',//快递单号(采购单原来的快递单号)
                    'abnormal_type' => $item['abnormal_type']??0,//异常类型
                    'abnormal_depict' => $item['abnormal_depict']??'',//异常原因
                    'img_path_data' => isset($item['img_path_data'])?json_encode($item['img_path_data']):'',//图片地址
                    'purchase_order_username' => $item['purchase_order_username']??'',//采购员名称
                    'add_username' => $item['add_username']??'',//异常信息创建人
                    'create_user_name' => $item['add_username']??'',//异常信息创建人
//                    'can_handle_type_data'=>!empty($item['can_handle_type_data'])?json_encode($item['can_handle_type_data']):'',
                    'warehouse_code' => $warehouse_code,//仓库编码
                    'warehouse_name' => $warehouse_name,//仓库名称
                ];


                if ((int)$params['abnormal_type']>1){
                    //6.次品录入,11.入库错误,12.质检无货,13.开箱短装 仓库不会推
                    $params['abnormal_type'] = (int)$params['abnormal_type']+2;//异常类型: 之前的异常类型为 1查找入库单，2入库有次品，3质检不合格 兼容之前的处理
                }



                $validate_result=$this->abnormal_model->validate_abnormal($params);
                if(!$validate_result['success']){
                    echo json_encode(['status'=>0,'message'=>$params['defective_id'].$validate_result['error_msg']]);
                    exit;
                }else{
                    //筛选重推的单进行更新操作
                    $row = $this->abnormal_model->get_one_abnormal($item['defective_id']);
                    $purchase_order_info = $this->purchase_order_model->get_one($params['purchase_order_no'],false);//原来采购单信息

                    if (!empty($params['purchase_order_no'])&&empty($params['purchase_order_username'])) {
                        $order_buyer = $purchase_order_info['buyer_name']??'';


                    } else {
                        $order_buyer = $params['purchase_order_username']??'';

                    }
                    //绑定的跟单员
                    $bind_user = '';
                    $bind_user_id = 0;
                    //塘厦组就是塘厦小包仓的,虎门组就是虎门小包仓的,就去选择跟单员
                    if (in_array($warehouse_code,['SZ_AA','HM_AA'])) {

                        $merchandiser_bind_info = $this->Merchandiser_group_model->get_bind_merchandiser_info($bind_warehouse_set[$warehouse_code],$purchase_order_info['buyer_id']);
                        $bind_user = $merchandiser_bind_info['user_name']??'';
                        $bind_user_id = $merchandiser_bind_info['user_id']??'';

                    }







                    if($row){
                        $update_arr[] = [
                            'sku' => $params['sku'],
                            'quantity' =>  $params['num'],
                            'exception_position' => $params['position'],
                            'defective_type' => $params['defective_type'],
                            'defective_id' => $params['defective_id'],
                            'pur_number' => $params['purchase_order_no'],
                            'express_code' => $params['express_code'],
                            'abnormal_type' => $params['abnormal_type'],
                            'abnormal_depict' => $params['abnormal_depict'],
                            'img_path_data' => $params['img_path_data'],
                            'buyer' => $order_buyer,
                            'add_username' => $params['add_username'],
                            'create_user_name' => $params['create_user_name'],
//                            'can_handle_type_data'=>$params['can_handle_type_data'],
                            'create_time' => $create_time,//创建时间
                            'modify_time' => date('Y-m-d H:i:s'),//更新时间
                            'pull_time' => date('Y-m-d H:i:s'),
                            'is_handler' => 0,
                            'handler_time' => '0000-00-00 00:00:00',
                            'handler_person' => '',
                            'handler_describe' => '',
                            'handler_type' => null,
                            'warehouse_handler_result' => '',
                            'is_reject' => 0,
                            'reject_reason' => '',
                            'reject_user' => '',
                            'reject_time' => '0000-00-00 00:00:00',
                            'is_push_warehouse' => 0,
                            'warehouse_code' => $params['warehouse_code'],
                            'warehouse_name' => $params['warehouse_name'],
                            'merchandiser'   => $bind_user,
                            'merchandiser_id'  => $bind_user_id
                        ];
                    }else{
                        //exception_position
                        $insert_arr[] = [
                            'sku' => $params['sku'],
                            'quantity' =>  $params['num'],
                            'exception_position' => $params['position'],
                            'defective_type' => $params['defective_type'],
                            'defective_id' => $params['defective_id'],
                            'pur_number' => $params['purchase_order_no'],
                            'express_code' => $params['express_code'],
                            'abnormal_type' => $params['abnormal_type'],

                            'abnormal_depict' => $params['abnormal_depict'],
                            'img_path_data' => $params['img_path_data'],
                            'buyer' => $order_buyer,
                            'add_username' => $params['add_username'],
                            'create_user_name' => $params['create_user_name'],
//                            'can_handle_type_data'=>$params['can_handle_type_data'],
                            'create_time' => $create_time,//创建时间
                            'pull_time' => date('Y-m-d H:i:s'),
                            'warehouse_code' => $params['warehouse_code'],
                            'warehouse_name' => $params['warehouse_name'],
                            'merchandiser'   => $bind_user,
                            'merchandiser_id'  => $bind_user_id
                        ];
                    }

                    $success_list[] = $item['defective_id'];
                }
            }else{
                echo json_encode(['status'=>0,'message'=>'异常单号不能为空']);
                exit;
            }
        }

        if (!empty($update_arr)){
            //更新
            $update_result=$this->abnormal_model->batch_update_abnormal($update_arr);
            if(!$update_result['success']) {
                echo json_encode(['status'=>0,'message'=>$update_result['error_msg']]);
                exit;
            }
        }

        if (!empty($insert_arr)){
            $insert_result=$this->abnormal_model->batch_add_abnormal_data($insert_arr);
            if($insert_result['success']){
                echo json_encode(['success_list'=>$success_list,'status'=>200,'message'=>'OK']);
                exit;
            }else{
                echo json_encode(['status'=>0,'message'=>$insert_result['error_msg']]);
                exit;
            }
        }

        if (empty($update_arr) && empty($insert_arr)){
            echo json_encode(['status'=>0,'message'=>'没有有效的数据']);
            exit;
        }else{
            echo json_encode(['success_list'=>$success_list,'status'=>200,'message'=>'OK']);
        }
    }

    /**
     * 计划任务-推送异常处理结果至仓库系统
     * /abnormal_api/push_handler_res_to_warehouse
     */
    public function push_handler_res_to_warehouse()
    {
        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 || $limit > 1000){
            $limit = 300;//每次推送300条
        }
        $rows = $this->abnormal_model->get_abnormal_list_to_warehouse($limit);

        if(empty($rows)) {
            echo '已经没有数据了';
            exit;
        }

        $oldwmsdata = [];
        $newWmsData = []; // 新仓库系统数据
        foreach($rows as $row) {

            // 数据推送到新仓库系统
            if($row['is_new_wms'] ==1){

                $newWmsData [] = [
                    'defectiveNo' => $row['defective_id'], // 异常单号
                    'poNumber'    => $row['pur_number'], // 采购单号
                    'handleType'  => $row['handler_type'], // 处理类型
                    'purchaseDepict' => $row['handler_describe'], //备注
                    'rebutImg'    => $row['upload_img_data'], // 图片
                    'purchaseUser' => $row['handler_person'], // 处理人
                    'expressNo' => $row['express_code'], // 快递单号
                    'returnPerson' => $row['return_linkman'], // 退货人
                    'returnPhone'  => $row['return_phone'], // 退货人联系方式
                    'returnAddress' => $row['return_address'], //退货人联系方式
                ];
            }else {
                //异常类型=开箱短装的，已处理且处理结果是【正常入库,继续补发，退款，报损】时才推送给仓库，其他处理结果不推送给仓库
                if ($row['abnormal_type']==13 && !in_array($row['handler_type'],[4,22,23,24]) ){
                    continue;
                }

                $oldwmsdata[$row['defective_id']] = [
                'defective_id'      => $row['defective_id'],
                'handler_type'      => (int)$row['handler_type'],
                'abnormal_type'     => $row['abnormal_type'],
                'handler_person'    => $row['handler_person'],
                'purchase_order_no' => $row['pur_number'],
                'handler_time'      => $row['handler_time'],
                'handler_describe'  => $row['handler_describe'],
                'return_province'   => $row['return_province'],
                'return_city'       => $row['return_city'],
                'return_address'    => $row['return_address'],
                'return_linkman'    => $row['return_linkman'],
                'return_phone'      => $row['return_phone'],
                'upload_img_data'   => $row['upload_img_data'],
            ];

                if ($row['abnormal_type'] > 3) {
                    $oldwmsdata[$row['defective_id']]['abnormal_type'] = $row['abnormal_type'] - 2;//异常类型: 之前的异常类型为 1查找入库单，2入库有次品，3质检不合格 兼容之前的处理
                }
            }

            /*if ($data[$row['defective_id']]['abnormal_type']==11 && in_array($row['handler_type'],[4,22,23,24])){//正常入库,继续补发，退款，报损
                $data[$row['defective_id']]['upload_img_data'] = $row['upload_img_data'];//异常类型是开箱短装,且处理类型是继续补发，退款，报损 才加这个图片这个字段
            }*/
        }




        if(!empty($newWmsData)){

            $ci = get_instance();
            $ci->load->config('mongodb');
            $host = $ci->config->item('mongo_host');
            $port = $ci->config->item('mongo_port');
            $user = $ci->config->item('mongo_user');
            $password = $ci->config->item('mongo_pass');
            $author_db = $ci->config->item('mongo_db');
            $mongodb = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
            $author_db = $author_db;
            // 推送新的仓库系统
            $header = array('Content-Type: application/json','w:SZ_AA','org:org_00001');

            $access_taken = getOASystemAccessToken();
            //$url = getConfigItemByName('api_config', 'wms_system', 'push_handler_res_to_warehouse');
            $url = getConfigItemByName('api_config', 'wms_system', 'purchase_processingResult');
            $url = $url . "?access_token=" . $access_taken;
            foreach( $newWmsData as $data) {
                $newWmsresult = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
                $logs = [

                    'defective_id' => $data['defectiveNo'],
                    'returndata' => json_decode($newWmsresult,True),
                    'create_time' => date("Y-m-d H:i:s",time()),

                ];

                $bulk = new MongoDB\Driver\BulkWrite();
                $mongodb_result = $bulk->insert($logs);
                $mongodb->executeBulkWrite("{$author_db}.abnormallogs", $bulk);

                $newWmsresult = json_decode($newWmsresult,True);

                if( $newWmsresult['code'] == 200){
                    $this->abnormal_model->update_abnormals(['defective_id'=>$data['defectiveNo']],['is_push_warehouse' => 1, 'warehouse_handler_result' => '处理结果已推送至仓库系统']);
                }else{

                    $this->abnormal_model->update_abnormal_by_defective_id($data['defectiveNo'],['is_handler'=>2,'warehouse_handler_result'=>'推送仓库失败:'.$newWmsresult['msg']]);
                }
            }

        }
        if(!empty($oldwmsdata)) {

            $postData = [
                'quality_abnormal_result' => json_encode($oldwmsdata),
                'token' => json_encode(stockAuth())
            ];

            //推送仓库
            $url = getConfigItemByName('api_config', 'wms_system', 'push_handler_res_to_warehouse');
            //$url = 'http://1z8580573g.51mypc.cn:33335/Api/Purchase/QualityAbnormal/getQualityAbnormalResult';
            $res = getCurlData($url, $postData, 'POST');
            try {
                $res = json_decode($res, TRUE);
                if (isset($res['error']) && $res['error'] == -1) {

                    echo "<pre>\n---------------------------------接口error返回-1 开始---------------------------------\n";
                    print_r($res);
                    echo "\n---------------------------------接口error返回-1 结束---------------------------------\n";
                    exit;

                }
                if (is_array($res) && !empty($res)) {

                    $defective_ids = [];
                    $failure_list = [];

                    foreach ($res as $k => $v) {
                        if ($v['status'] == 'success') {
                            $defective_ids[] = strval($k);
                        } elseif ($v['status'] == 'fail') {
                            $this->abnormal_model->update_abnormal_by_defective_id($v['defective_id'], ['is_handler' => 2, 'warehouse_handler_result' => '推送仓库失败:' . $v['msgBox']]);

                            $failure_list[] = [
                                'defective_id' => $v['defective_id'],
                                'msgBox' => $v['msgBox'],
                            ];
                        }
                    }


                    if ($defective_ids) {

                        $i = $this->abnormal_model->update_abnormals(['defective_id' => $defective_ids], ['is_push_warehouse' => 1, 'warehouse_handler_result' => '处理结果已推送至仓库系统']);
                    }
                }

            } catch (\Exception $e) {

            }
        }
    }

    //修复一级产品线和二级产品线一致的单
    public function fix_product_line()
    {
        echo '此处修改会导致业务线错误，功能禁用！';exit;
        $sku = $this->input->get_post('sku');
//        $line_id = $this->input->get_post('line_id');
        $limit = (int)$this->input->get_post('limit');

        $where = 'a.two_product_line_id=a.product_line_id AND two_product_line_id!=0 AND a.suggest_status not in(3,4) AND 
        l.linelist_parent_id != 0';

        if(!empty($sku)){
            $where.=' AND a.sku="'.$sku.'"';
        }

        //查找总数
        $count_num = $this->db->select('a.id')
            ->from('purchase_suggest as a')
            ->join('pur_product_line l', 'l.product_line_id=a.product_line_id', 'left')
            ->where($where)
            ->order_by('a.create_time DESC,a.id DESC')
            ->count_all_results();
//        var_dump($count_num);die;
//        echo $this->db->last_query();die;

        if ($limit <= 0 || $limit > 5000){
            $limit = 5000;//每次更新5000条
        }
        $this->load->model('product/product_line_model');
        if($count_num>=1){
            try {
//                for ($i=1; $i <=ceil($count_num/$limit) ; $i++) {
//                    $offset = ($i-1)*$limit;
                    $suggest_list = $this->db->select(
                        'a.id,
                         a.two_product_line_id,
                         '
                    )
                        ->from('purchase_suggest as a')
                        ->join('pur_product_line l', 'l.product_line_id=a.product_line_id', 'left')
                        ->where($where)
                        ->order_by('a.create_time DESC,a.id DESC')
//                        ->limit($limit,$offset)
                        ->limit($limit)
                        ->get()
                        ->result_array();
//                    echo $this->db->last_query();die;
                    $update = [];
                    foreach ($suggest_list as $key => $value) {
                        $top_product_line = $this->product_line_model->get_all_parent_category($value['two_product_line_id']);
                        if (empty($top_product_line)){
                            continue;
                        }
                        if ($top_product_line[0]['product_line_id']==$value['two_product_line_id']){
                            continue;
                        }

                        $tmp['id'] = $value['id'];
                        $tmp['product_line_id'] = $top_product_line[0]['product_line_id'];
                        $tmp['product_line_name'] = $top_product_line[0]['product_line_name'];

                        array_push($update,$tmp);

                    }

                    //var_dump($update);die;
                    if (!empty($update)){
                        $res = $this->db->update_batch('purchase_suggest',$update,'id');
                        var_dump('OK:'.$res);
                    }else{
                        echo "没有数据了2";
                    }


//                }
            }catch (Exception $e) {

                exit($e->getMessage());
            }
        }else{
            echo '没有数据了1';
        }

    }


    public function abnornalData(){
        ini_set('max_execution_time','18000');
        $total = 242264;
        $limit = 1900;

        $ids =[];

        if(isset($_GET['id'])) {
            $ids = explode(",", $_GET['id']);
        }
        if(!empty($ids)){

            $total = count($ids);
        }

        $page =  ceil($total/$limit);

        for($i=1;$i<=$page;++$i){

            if(empty($ids)) {

                $sql = " SELECT * FROM pur_purchase_warehouse_abnormal WHERE buyer=0 ORDER BY create_time DESC LIMIT " . ($i - 1) * $limit . "," . $limit;
            }else{

                $sql = " SELECT * FROM pur_purchase_warehouse_abnormal WHERE buyer=0  AND id IN (".implode(",",$ids).") ORDER BY create_time DESC";
            }
            $result = $this->db->query($sql)->result_array();
            if(!empty($result)){

                foreach( $result as $key=>$value){

                    $sql = "SELECT buyer_name FROM pur_purchase_order WHERE purchase_number='{$value['pur_number']}'";
                    $orders = $this->db->query($sql)->row_array();

                    if(!empty($orders)){

                        $this->db->where("pur_number",$value['pur_number'])->update('purchase_warehouse_abnormal',['buyer'=>$orders['buyer_name']]);
                       // echo $this->db->last_query();die();
                    }
                }
            }
        }

    }

    /**
     * 接受仓库系统推送到退货快递单号数据
     * @author：luxu
     * @time:2021年5月12
     **/
    public function push_return_express_no(){

        try{

            $returnexpress = json_decode(file_get_contents('php://input'), true);
            if(empty($returnexpress)){

                throw new Exception("请传入数据");
            }
            $errorDatas = $success = [];
            foreach($returnexpress as $key=>$value){

                if(!isset($value['returnexpress']) && empty($value['returnexpress'])){

                    $errorDatas[] = $value['defective_id'];
                }

                $datas = $this->db->from("purchase_warehouse_abnormal")->where("defective_id",$value['defective_id'])->select("id")->get()->row_array();
                if(empty($datas)){
                    $errorDatas[] = $value['defective_id'];
                }else {

                    $result = $this->db->where("defective_id", $value['defective_id'])->update('purchase_warehouse_abnormal', ['return_express_no' => $value['returnexpress']]);
                    //echo $this->db->last_query();die();
                    if ($result) {

                        $success[] = $value['defective_id'];
                    } else {
                        $errorDatas[] = $value['defective_id'];
                    }
                }
            }
            header('Content-type: application/json');

            exit(json_encode(['status' => 1, 'success' => $success,'error'=>$errorDatas, 'errorMess' => ''],JSON_UNESCAPED_UNICODE));

        }catch ( Exception $exp ){

            $this->error_json($exp->getMessage());
        }
    }



}
