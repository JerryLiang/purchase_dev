<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Approval extends MY_API_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('approval_model');
    }

    /**
     * http://www.caigou.com/approval/return_url
     * 192.168.71.170:85/approval/return_url
     * @author 蓝灵异步回调(采购单)
     * * */
    public function return_url() {
        $data = file_get_contents('php://input'); //接受蓝灵回调数据 
        //判断是否有数据
//           $data='{"CallBack":"http://192.168.66.133/demo/","ProcessID":"1689f2a405b9d897d1f0ce741dd86093","Result":1,"OperateTime":"2019-01-16","UserCode":"","CustomPara":{"purchase_number":"PO00000024"}}';
        $temp = json_decode($data, TRUE);
        if (!empty($temp)) {
            $processid = $temp['ProcessID']; //流程ID
            $purchase_number = $temp['CustomPara']['purchase_number'];
            $this->approval_model->update_blue($temp, $processid,$purchase_number);
            exit(json_encode([
                'Code' => 0,
                'Message' => '更新成功'
            ]));
        } else {
            exit(json_encode([
                'Code' => 1,
                'Message' => '数据接收失败'
            ]));
        }
    }

    /**
     * 網采單 請款
     * @author harvin
     * http://www.caigou.com/approval/return_request
     * * */
    public function return_request() {
        $data = file_get_contents('php://input'); //接受蓝灵回调数据
       // file_put_contents('data.txt', $data);
        //判断是否有数据
         //  $data='{"CallBack":"http://192.168.66.133/demo/","ProcessID":"1689fc252c585134ed4c2634283a29c5","Result":1,"OperateTime":"2019-01-16","UserCode":"","CustomPara":{"requisition_number":"PP000210"}}';
        $temp = json_decode($data, TRUE);
        if (!empty($temp)) {
             $this->approval_model->update_request($temp);
             exit(json_encode([
                'Code' => 0,
                'Message' => '更新成功'
             ]));
        } else {
             exit(json_encode([
                'Code' => 1,
                'Message' => '数据接收失败'
            ]));
        }
    }
    /**
     * @author 2019-1-28
     * http://192.168.71.170:85/approval/return_compact
     * **/
   public function return_compact(){
        $data = file_get_contents('php://input'); //接受蓝灵回调数据
        $temp = json_decode($data, TRUE);
        if (!empty($temp)) {
             $bool=$this->approval_model->update_compact($temp);
             if($bool){
                  exit(json_encode([
                'Code' => 0,
                'Message' => '更新成功'
             ]));
             }else{
                exit(json_encode([
                'Code' => 1,
                'Message' => '更新失败'
               ]));
             }
            
        } else {
             exit(json_encode([
                'Code' => 1,
                'Message' => '数据接收失败'
            ]));
        }   
   }

    /**
     * @desc [计划任务]改变已过期的需求单的需求状态
     * @author Jeff
     * @Date 2019/03/23 16:49
     * @param string $demand_number
     * @return
     */
    public function change_suggest_expired()
    {
        // 获取已过期的采购需求记录
        $expired_suggest =  $this->approval_model->get_expired_suggest();

        $suggest_ids = array_column($expired_suggest,'id');

        if (!empty($expired_suggest) && !is_null($expired_suggest)){

           $this->approval_model->update_suggest($expired_suggest,$suggest_ids);

        }else{
            echo '没有过期需求单啦';
        }

    }

    //仓库驳回异常列表数据
    public function warehouse_abnormal_reject()
    {
        $result = ['status'=>0 , 'msg'=>'', 'data'=>[], 'fail'=>[]];
        $data = file_get_contents('php://input'); //接受蓝灵回调数据
        /*$data = [
            ['defective_id'=>'DZ20190404111303'],
            ['defective_id'=>'DZ20190404111439'],
        ];*/

        if(empty($data)){
            $result['msg'] = '数据不存在，请检查';
            exit(json_encode($result));
        }

        $return = $this->approval_model->warehouse_abnormal_reject(json_decode($data,true));

        exit(json_encode($return));
    }

    //获取仓库异常单退货信息
    public function get_abnormal_return_info()
    {
        $data = $_POST;
        //写入API请求记录表
        apiRequestLogInsert(
            ['record_type' => 'wms_abnormal_returned_logistics_info', 'response_content' => $data],
            'pur_api_request_log'
        );
        $infoSave = json_encode(['successList'=>[],'failList'=>[]]);
        if (isset($data['data']) && !empty($data['data'])){
            $infoSave = $this->approval_model->save_return_data(json_decode($data['data'],true));
        }
        echo $infoSave;
        exit();
    }

    // 定时拉取仓库异常处理结果
    public function get_warehouse_result()
    {
        $ids = $this->approval_model->get_abmomal_list_ids();
        if(empty($ids)) {
            exit('没有数据了');
        }
        //获取仓库异常处理的数据
        $postData = [
            'defective_id_list' => json_encode(array_column($ids,'defective_id')),
            'token' => json_encode(stockAuth())
        ];

        $url = getConfigItemByName('api_config', 'wms_system', 'get_defective_data');
        $res = getCurlData($url, $postData, 'POST');

        try {
            $_result = json_decode($res,true);

            if(isset($_result['success']) && !empty($_result['success'])) {
                $this->load->model('abnormal/abnormal_list_model');
                $lists = $this->approval_model->get_abmomal_list($_result['success']);

                $update_data = [];
                foreach($lists as $list) {
                    $update_data['is_handler'] = 1;
                    $update_data['is_push_warehouse'] = 1;
                    $update_data['warehouse_handler_result'] = '仓库已处理';
                    $update_res = $this->abnormal_list_model->update_abnormals($list['defective_id'],$update_data);

                    $insert_res = operatorLogInsert(
                        [
                            'id' => $list['id'],
                            'type' => 'pur_purchase_warehouse_abnormal',
                            'content' => '更新采购异常单退货记录信息',
                            'detail' => '处理结果由'.$list['warehouse_handler_result'].'更新为仓库已处理',
                            'user' => '计划任务',
                        ]
                    );
                    if(empty($insert_res)) throw new Exception($list['id'].":异常单操作记录添加失败");

                    var_dump($list['defective_id'].'更新:'.$update_res);
                }
            } else {
                echo($res);
            }
        } catch (\Exception $e) {
            echo($e->getMessage());
        }
    }
}
