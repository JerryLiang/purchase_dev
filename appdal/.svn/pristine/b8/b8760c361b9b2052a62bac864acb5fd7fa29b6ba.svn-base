<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/5/19
 * Time: 16:10
 */

defined('BASEPATH') OR exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

class Purchase_shipment_api extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase_shipment/Purchase_shipping_management_model', 'shipment_model');
    }

    /**
     * 推送发运跟踪验货结果数据到计划系统
     * @author Justin
     * /Purchase_shipment_api/pushInspectResultToPlan
     */
    public function pushInspectResultToPlan()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $limit = (int)$this->input->get_post('limit');
        $demand_number = $this->input->get_post('demand_number');
        $new_demand_number = $this->input->get_post('new_demand_number');
        if ($limit <= 0 OR $limit > 200) {
            $limit = 200; //每次推送200条
        }

        try {
            //获取推送数据
            $data_list = $this->shipment_model->getPushToPlanData($limit, $demand_number, $new_demand_number);
            if (empty($data_list)) {
                exit('暂无符合条件的数据');
            }
            //请求Java接口，推送数据
            $post_data = array();
            foreach ($data_list as $item) {
                $post_data[] = array(
                    'demand_number' => $item['new_demand_number'],                                              //备货单号
                    'inspection_result' => $item['judgment_result'],                                            //验货结果
                    'send_date' => 2 == $item['judgment_result'] ? $item['check_expect_time'] : '',             //期望验货日期（验货不合格时传值）
                );
            }

            //请求URL
            $request_url = getConfigItemByName('api_config', 'shipping_management', 'push_inspect_result');
            if (empty($request_url)) exit('请求URL不存在');

            $access_token = getOASystemAccessToken();
            if (empty($access_token)) exit('获取access_token值失败');
            $request_url = $request_url . '?access_token=' . $access_token;
            $header = ['Content-Type: application/json'];
            $res = getCurlData($request_url, json_encode($post_data), 'POST', $header);
            $_result = json_decode($res, true);
            $status = $_result['status']??0;
            if ($status == 0 || $status != 1){
                echo '接口返回失败,失败原因:'.$_result['errorMess']??"".'<br>' ;
                operatorLogInsert(
                    array(
                        'type' => 'SHIPMENT_TRACK_PUSH_TO_PLAN',
                        'content' => '推送接口返回异常',
                        'detail' => $_result['errorMess']??'',
                        'user' => '计划任务',
                    )
                );
            }
            //推送成功的数据
            if (!empty($_result['success_list'])) {
                //推送成功后，更新推送状态和推送时间
                foreach ($_result['success_list'] as $demand_number) {
                    $update_res = $this->shipment_model->updatePushState($demand_number);
                    if (empty($update_res)) {
                        throw new Exception($demand_number . ":备货单推送状态更新失败");
                    } else {
                        echo '备货单推送成功:' . $demand_number . '<br>';
                    }
                }
            }
            //推送失败的处理
            if (!empty($_result['fail_list'])) {
                foreach ($_result['fail_list'] as $demand_number => $error_msg) {
                    echo '备货单推送失败:' . $demand_number . '[' . $error_msg . ']<br>';
                    //写入操作日志表
                    operatorLogInsert(
                        array(
                            'id' => $demand_number,
                            'type' => 'SHIPMENT_TRACK_PUSH_TO_PLAN',
                            'content' => '备货单推送失败',
                            'detail' => $error_msg,
                            'user' => '计划任务',
                        )
                    );
                }
            }
        } catch (Exception $exc) {
            //写入操作日志表
            operatorLogInsert(
                array(
                    'type' => 'SHIPMENT_TRACK_PUSH_TO_PLAN',
                    'content' => '推送异常',
                    'detail' => $exc->getMessage(),
                    'user' => '计划任务',
                )
            );
            exit($exc->getMessage());
        }
    }

    /**
     * 手动修改信息
     * /Purchase_shipment_api/change_push_data
     */
    public function change_push_data()
    {
        $id = $this->input->get_post('id');
        $judgment = $this->input->get_post('judgment_result');
        $plan = $this->input->get_post('plan');
        if($this->shipment_model->purchase_db->where(["id"=>$id])->update("shipment_track_list", ["judgment_result" => $judgment, "push_to_plan" => $plan])){
            exit("修改成功！");
        };
        exit("修改失败！");
    }
}