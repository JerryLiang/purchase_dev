<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/12/2
 * Time: 15:07
 */
class Logistics_info_api extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Logistics_info_model');
        $this->load->model('purchase/Purchase_order_progress_model');
    }

    /**
     * 推送快递单数据到新wms系统
     * @author Justin
     * /Logistics_info_api/pushExpressInfoToNewWms
     */
    public function pushExpressInfoToNewWms()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $limit = (int)$this->input->get_post('limit');
        $purchase_number = $this->input->get_post('purchase_number');
        if ($limit <= 0 OR $limit > 200) {
            $limit = 200; //每次推送200条
        }

        try {
            //获取推送物流信息数据
            $express_info_list = $this->Logistics_info_model->get_push_express_info($limit, $purchase_number);
            $ship_code = $this->Logistics_info_model->get_ship_company();

            if (!empty($express_info_list)) {
                //请求Java接口，推送快递单数据
                $post_data = $new_push_data = array();
                foreach ($express_info_list as $item) {
                    if (empty(trim($item['express_no'])) OR empty(trim($item['carrier_code']))) continue;
                    $post_data[] = array(
                        'expressNo' => trim($item['express_no']),           //快递单号
                        'purchaseOrderNo' => trim($item['purchase_number']),//采购单
                        'carrierCode' => $item['carrier_code']           //快递公司编码

                    );

                    $carrier_code = trim($item['carrier_code']);
                    $new_push_data[] =array(
                        'poNumber' => trim($item['purchase_number']), // 采购单号
                        'expressNo' => trim($item['express_no']),           //快递单号
                        'expressSupplier' => isset($ship_code[$carrier_code]) ? $ship_code[$carrier_code] : trim($item['cargo_company_id']), // 快递公司
                        'expressSupplierCode' => $carrier_code, // 快递公司编码
                        'createName' => ''  // 操作人
                    );
                }
                if(!empty($new_push_data)) {
                    // 推送新仓库系统
                    $this->Purchase_order_progress_model->push_receive_bind_express(NULL, $new_push_data);
                }
                //请求URL
                $request_url = getConfigItemByName('api_config', 'wms_system', 'push_express_info_to_wms_new');
                if (empty($request_url)) exit('请求URL不存在');

                $access_token = getOASystemAccessToken();
                if (empty($access_token)) exit('获取access_token值失败');
                $request_url = $request_url . '?access_token=' . $access_token;
                $header = ['Content-Type: application/json', 'org:org_00001'];
                $res = getCurlData($request_url, json_encode($post_data), 'POST', $header);
                $_result = json_decode($res, true);

                if (isset($_result['code']) && $_result['code'] == 200) {
                    //推送成功后，更新推送状态
                    $success_list = isset($_result['data']['successList']) && is_array($_result['data']['successList']) ? $_result['data']['successList'] : [];
                    foreach ($success_list as $item) {
                        $update_data['is_push_wms'] = 1;
                        $update_data['update_time'] = date('Y-m-d H:i:s');
                        try {
                            $update_res = $this->Logistics_info_model->update_express_order(1, ['express_no' => trim($item['expressNo'])], $update_data);
                            if (empty($update_res)) {
                                echo $item['expressNo'] . ":快递单推送状态更新失败";//throw new Exception($item['expressNo'] . ":快递单推送状态更新失败");
                            } else {
                                echo '快递单推送成功:' . $item['expressNo'] . '<br>';
                            }
                        }catch (Exception $e){}
                    }
                    //推送失败的处理
                    $fail_list = isset($_result['data']['failList']) && is_array($_result['data']['failList']) ? $_result['data']['failList'] : [];
                    if (!empty($fail_list)) {
                        foreach ($fail_list as $item) {
                            echo '快递单推送失败:' . $item['expressNo'] . '<br>';
                            //写入操作日志表
                            operatorLogInsert(
                                array(
                                    'id' => $item['expressNo'],
                                    'type' => 'PUR_PURCHASE_PUSH_EXPRESS_INFO',
                                    'content' => '快递单推送失败',
                                    'detail' => $fail_list,
                                    'user' => '计划任务',
                                )
                            );
                        }
                    }
                } else {
                    $msg = isset($res['msg']) ? $res['msg'] : '请求推送接口异常';
                    echo $msg . '<br>';
                    echo $res;
                }
            } else {
                throw new Exception('暂无数据');
            }
        } catch (Exception $exc) {
            //写入操作日志表
            operatorLogInsert(
                array(
                    'type' => 'PUR_PURCHASE_PUSH_EXPRESS_INFO',
                    'content' => '推送异常',
                    'detail' => $exc->getMessage(),
                    'user' => '计划任务',
                )
            );
            exit($exc->getMessage());
        }
    }

    /**
     * (接口暂时作废)
     * (被动)接收“物流系统”定时推送数据，并按照设置规则匹配物流轨迹状态
     * @author Justin
     */
    public function receive_logistics_tracks()
    {
        exit('接口已作废');
        set_time_limit(3600);
        ini_set('memory_limit', '512M');

        $_result = file_get_contents("php://input");

        $_result = json_decode($_result, TRUE);

        if (empty($_result) OR !is_array($_result)) {
            operatorLogInsert(
                [
                    'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_FAILURE',
                    'content' => '轨迹状态更新失败',
                    'detail' => '请求参数错误',
                    'user' => '定时计划',
                ]);
            $this->error_json('请求参数错误');
        }

        if (!isset($_result['data']) OR !is_array($_result['data'])) {
            operatorLogInsert(
                [
                    'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_FAILURE',
                    'content' => '轨迹状态更新失败',
                    'detail' => '轨迹详情数据格式错误[code:001]',
                    'user' => '定时计划',
                ]);
            $this->error_json('轨迹详情数据格式错误[code:001]');
        } elseif (empty($_result['data'])) {
            operatorLogInsert(
                [
                    'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_FAILURE',
                    'content' => '轨迹状态更新失败',
                    'detail' => '轨迹详情数据为空',
                    'user' => '定时计划',
                ]);
            $this->error_json('轨迹详情数据为空');
        }

        //所有仓库地址信息
        $warehouse_address = $this->Logistics_info_model->get_warehouse_address();

        //更新异常数据
        $exception_data = array();

        //循环处理每条快递单数
        foreach ($_result['data'] as $item) {
            if (!isset($item['companyCode']) OR !isset($item['trackingNumber']) OR !isset($item['tracksContent']) OR !isset($item['orderType'])) $this->error_json('轨迹详情数据格式错误[code:002]');
            $carrier_code = $item['companyCode']; //快递公司编码
            $tracking_number = $item['trackingNumber']; //快递单号
            $order_type = $item['orderType'];//属于哪个表的快递单数据（1-物流信息表，2-异常采购单退货记录表）

            //查询快递单号数据
            $this->load->model('purchase/Purchase_order_progress_model');
            $res = $this->Purchase_order_progress_model->express_is_exists($order_type, [$tracking_number]);
            if (!$res['flag']) {
                continue;
            }

            //快递单号数据对应物流轨迹状态
            $real_express_data = $res['data'];
            if (!isset($real_express_data[$item['trackingNumber']])) {
                operatorLogInsert(
                    [
                        'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_FAILURE',
                        'content' => '轨迹状态更新失败',
                        'detail' => '轨迹详情数据异常',
                        'user' => '定时计划',
                    ]);
                $this->error_json('轨迹详情数据异常');
            }
            //快递单旧轨迹状态
            $old_status = $real_express_data[$tracking_number];


            //轨迹详情（最新记录！！必须！！在前面！！！匹配效率最高）
            $tracks_content = json_decode($item['tracksContent'], true);
            //获取排序标识,最新记录在前面用于提高匹配效率
            $first = isset($tracks_content[0]['eventTime']) ? $tracks_content[0]['eventTime'] : '';
            $last = isset($tracks_content[count($tracks_content) - 1]['eventTime']) ? $tracks_content[count($tracks_content) - 1]['eventTime'] : '';
            if (!empty($first) && !empty($last)) {
                //如果原始记录是时间较早的排在前面，则需要反转顺序
                if ((int)strtotime($first) < (int)strtotime($last)) {
                    $tracks_content = array_reverse($tracks_content);
                }
            }
            foreach ($tracks_content as $key => $content) {
                if (!isset($content['eventThing'])) {
                    operatorLogInsert(
                        [
                            'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_FAILURE',
                            'content' => '轨迹状态更新失败',
                            'detail' => '轨迹详情数据格式错误[code:003]',
                            'user' => '定时计划',
                        ]);
                    $this->error_json('轨迹详情数据格式错误[code:003]');
                } elseif (empty($content['eventThing'])) {
                    continue;
                }

                //根据配置规则匹配轨迹状态(仅当order_type=1时，才需要仓库地址匹配提货点，退货单不需要匹配提货点)
                $status = $this->Logistics_info_model->resolve_logistics_tracks(($order_type == 2) ? 2 : $warehouse_address, $carrier_code, $tracking_number, $content['eventThing']);
                //匹配成功，更新快递单物流数据状态(重新抓取轨迹状态未发生变化则不更新)
                if ($status && ($status > $old_status)) {
                    $where = array('express_no' => $tracking_number);
                    $update_data = array('status' => $status, 'update_time' => date('Y-m-d H:i:s'));
                    $update_res = $this->Logistics_info_model->update_express_order($order_type, $where, $update_data);
                    if ($update_res) {
                        //写入操作日志表
                        operatorLogInsert(
                            array(
                                'id' => $tracking_number,
                                'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_SUCCESS',
                                'content' => '轨迹状态更新成功',
                                'detail' => '轨迹状态更新成功,由' . $old_status . '更新为' . $status,
                                'user' => '定时计划',
                            )
                        );
                    } else {
                        //更新失敗
                        //写入操作日志表
                        operatorLogInsert(
                            array(
                                'id' => $tracking_number,
                                'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_FAILURE',
                                'content' => '轨迹状态更新失败',
                                'detail' => '轨迹状态更新失败',
                                'user' => '定时计划',
                            )
                        );
                        $exception_data[] = $tracking_number;
                    }
                    break;
                }
            }
        }
        $this->success_json([], null, '解析成功');

    }

    /**
     * (定时任务)获取1688订单，并按照设置规则匹配物流轨迹状态
     * @author Justin
     */
    public function receive_logistics_tracks_from_1688()
    {
        exit('接口已作废');
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        //每次处理的条数
        $total = $this->input->get_post('total');
        $total = (empty($total) OR $total > 1000) ? 1000 : intval($total);
        //提供两种类型的数据查询（1-查询除已签收外的数据，2-查询已发货状态的数据，用于匹配‘已到提货点’状态（默认2））
        $type = $this->input->get_post('type');
        $type = empty($type) ? 2 : $type;

        //距离上次更新的时间间隔
        $hour = $this->input->get_post('hour');
        $hour = (empty($hour) && intval($hour)) > 0 ? $hour : 5;


        $limit = 200;
        $total_page = ceil($total / $limit);

        $this->load->library('alibaba/AliOrderApi');
        $this->load->model('purchase/Purchase_order_progress_model');
        //所有仓库地址信息
        $warehouse_address = $this->Logistics_info_model->get_warehouse_address();
        //一页一页取出数据处理
        for ($page = 1; $page <= $total_page; $page++) {
            //-- 1.取出拍单号与快递单号的关系
            $rsl = $this->Logistics_info_model->get_purchase_order_info($limit, $page, $hour, $type);

            if (!isset($rsl['data_list']) && empty($rsl['data_list'])) exit('没有可处理的数据');
            $order_info = $rsl['data_list'];
            $pai_number_arr = array_column($order_info, 'pai_number');
            foreach (array_chunk($pai_number_arr, 100) as $pai_number) {
                $result = $this->aliorderapi->listLogisticsTraceInfo($pai_number);
                if (!$result['code']) {
                    operatorLogInsert(
                        [
                            'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_FAILURE',
                            'content' => '轨迹状态更新失败',
                            'detail' => '1688接口获取轨迹详情失败[code:001]',
                            'user' => '定时计划',
                        ]);
                    $this->error_json('1688接口获取轨迹详情失败[code:001]');
                }

                //更新异常数据
                $exception_data = array();
                $success_data = array();

                //循环处理每条快递单数
                foreach ($result['data'] as $key => $item) {
                    if (!isset($order_info[$key])) continue;
                    $carrier_code = $order_info[$key]['carrier_code'];
                    $express_no = $order_info[$key]['express_no'];
                    $old_status = $order_info[$key]['status'];
                    $order_type = 1;

                    //轨迹详情（最新记录！！必须！！在前面！！！匹配效率最高）
                    foreach ($item['remark'] as $remark) {
                        $status = $this->Logistics_info_model->resolve_logistics_tracks($warehouse_address, $carrier_code, $express_no, $remark);

                        //匹配成功，更新快递单物流数据状态(重新抓取轨迹状态未发生变化则不更新)
                        if ($status && ($status > $old_status)) {
                            $where = array('express_no' => $express_no);
                            $update_data = array('status' => $status, 'update_time' => date('Y-m-d H:i:s'));
                            $update_res = $this->Logistics_info_model->update_express_order($order_type, $where, $update_data);
                            if ($update_res) {
                                //写入操作日志表
                                operatorLogInsert(
                                    array(
                                        'id' => $express_no,
                                        'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_SUCCESS',
                                        'content' => '轨迹状态更新成功',
                                        'detail' => '从1688接口获取轨迹详情,更新轨迹状态成功,由' . $old_status . '更新为' . $status,
                                        'user' => '定时计划',
                                    )
                                );
                                $success_data[] = $express_no;
                            } else {
                                //更新失敗
                                //写入操作日志表
                                operatorLogInsert(
                                    array(
                                        'id' => $express_no,
                                        'type' => 'PUR_PURCHASE_RESOLVE_LOGISTICS_STATE_FAILURE',
                                        'content' => '轨迹状态更新失败',
                                        'detail' => '从1688接口获取轨迹详情，更新轨迹状态失败',
                                        'user' => '定时计划',
                                    )
                                );
                                $exception_data[] = $express_no;
                            }
                            break;
                        }
                    }
                    //更新查询时间
                    $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $express_no], ['update_time' => date('Y-m-d H:i:s', time())]);
                }
                if (!empty($exception_data)) {
                    echo date('Y-m-d H:i:s') . '更新失败' . count($exception_data) . '条[' . implode(',', $exception_data) . "]" . '<br>';
                }

                echo date('Y-m-d H:i:s') . '解析成功' . count($success_data) . '条[' . implode(',', $success_data) . "]" . '<br>';

            }
        }
    }

    /**
     * 轨迹订阅（包括非1688订单和合同订单）
     * 分别从物流信息表pur_purchase_logistics_info和异常采购单退货记录表pur_excep_return_info
     * 获取推送数据（使用orderType字段区分两个表的快递单数据，1-物流信息表，2-异常采购单退货记录表）
     * @author Justin
     * /Logistics_info_api/orderTracesSub?order_type=1&express_no=123456,654321,321456
     */
    public function orderTracesSub()
    {
        ini_set('memory_limit', '512M');

        $order_type = $this->input->get_post('order_type');//该参数用于指定推送哪个表的数据（1-物流信息表，2-异常采购单退货记录表）
        $express_no = $this->input->get_post('express_no');//手动推送，指定单号（多个单号用逗号分隔）
        $express_no = array_filter(explode(',', str_replace('，', ',', $express_no)));

        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 OR $limit > 100) {
            $limit = 100; //每次查询100条
        }

        try {
            if (1 == $order_type) {
                //指定只订阅物流信息表数据
                $express_info_list = $this->Logistics_info_model->get_express_info($express_no, $limit);
            } elseif (2 == $order_type) {
                //指定只订阅异常采购单退货记录表数据
                $express_info_list = $this->Logistics_info_model->get_exception_express_info($express_no, $limit);
            } elseif (3 == $order_type){
                $express_info_list = $this->Logistics_info_model->get_multiple_return_express_info($express_no, $limit);

            } else {
                //不指定类型则，先订阅物流信息表数据
                $express_info_list = $this->Logistics_info_model->get_express_info($express_no, $limit);
                $order_type = 1;

                //再订阅异常采购单退货记录表数据
                if (empty($express_info_list)) {
                    $express_info_list = $this->Logistics_info_model->get_exception_express_info($express_no, $limit);
                    $order_type = 2;
                }

                if (empty($express_info_list)) {
                    $express_info_list = $this->Logistics_info_model->get_multiple_return_express_info($express_no, $limit);
                    $order_type = 3;
                }


            }

            if (empty($express_info_list)) {
                throw new Exception('暂无可订阅数据');
            }

            //不支持批量订阅，并发不超过 30 次/S
            foreach (array_chunk($express_info_list, 20) as $chunk) {
                foreach ($chunk as $item) {
                    if (empty(trim($item['express_no'])) OR empty(trim($item['carrier_code']))) continue;
                    $carrier_code = $item['carrier_code'];
                    $express_no = $item['express_no'];
                    $customer_name = isset($item['customer_name']) ? $item['customer_name'] : '';

                    $_result = $this->Logistics_info_model->order_traces_subscribe($express_no, $carrier_code, $order_type, $customer_name);

                    if (isset($_result['Success']) && $_result['Success']) {
                        $update_data = array(
                            'is_push' => 1,
                            'update_time' => date('Y-m-d H:i:s')
                        );
                        //订阅成功后，更新订阅状态
                        $update_res = $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $express_no], $update_data);
                        //写入操作日志表
                        operatorLogInsert(
                            array(
                                'id' => $express_no,
                                'type' => 'kdniao_traces_subscribe',
                                'content' => '快递鸟轨迹订阅',
                                'detail' => '快递单轨迹订阅成功',
                                'user' => '计划任务',
                            )
                        );
                        echo '快递单订阅成功:' . $express_no . '<br>';
                    } else {
                        $update_data = array(
                            'is_push' => 2,
                            'update_time' => date('Y-m-d H:i:s')
                        );
                        //订阅失败后，更新订阅状态为2-失败
                        $update_res = $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $express_no], $update_data);
                        operatorLogInsert(
                            array(
                                'id' => $express_no,
                                'type' => 'kdniao_traces_subscribe',
                                'content' => '快递单轨迹订阅失败',
                                'detail' => $_result,
                                'user' => '计划任务',
                            )
                        );
                        echo '快递单订阅失败:' . $express_no . '<br>';
                    }
                }
                //延迟0.1秒
                usleep(100000);
            }
        } catch (Exception $exc) {
            exit($exc->getMessage());
        }
    }

    /**
     * 接收订阅数据
     * /Logistics_info_api/orderTracesSubscribeReceive
     */
    public function orderTracesSubscribeReceive()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '512M');

        if ($this->input->method(TRUE) != 'POST') {
            $res = array(
                'EBusinessID' => '',
                'UpdateTime' => date('Y-m-d H:i:s', time()),
                'Success' => false,
                'Reason' => '接口支持的消息接收方式为HTTP POST'
            );
            echo json_encode($res);
            exit;
        } elseif (empty($_POST['RequestData'])) {
            $res = array(
                'EBusinessID' => '',
                'UpdateTime' => date('Y-m-d H:i:s', time()),
                'Success' => false,
                'Reason' => '参数RequestData值为空'
            );
            echo json_encode($res);
            exit;
        }

        $_result = json_decode(urldecode($_POST['RequestData']), TRUE);

        if (empty($_result['Data'])) {
            $res = array(
                'EBusinessID' => isset($_result['EBusinessID']) ? $_result['EBusinessID'] : '',
                'UpdateTime' => date('Y-m-d H:i:s', time()),
                'Success' => false,
                'Reason' => '推送轨迹订阅数据为空'
            );
            echo json_encode($res);
            exit;
        }

        //处理接口返回数据
        foreach ($_result['Data'] as $item) {
            //保存日志
            operatorLogInsert(
                [
                    'id' => $item['LogisticCode'],
                    'ext' => $item['StateEx'],
                    'type' => 'kdniao_traces_subscribe_receive',
                    'content' => '接收快递鸟订阅数据',
                    'detail' => $item,
                    'user' => '定时计划',
                ]);

            if (!isset($item['CallBack'])) continue;
            $order_type = $item['CallBack'];

            //快递鸟返回状态，和我们系统的状态进行转换
            $StateEx = $this->Logistics_info_model->switch_status($item['StateEx'], $order_type);
            //更新轨迹状态
            $update_data = array('status' => $StateEx, 'update_time' => date('Y-m-d H:i:s'));
            $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $item['LogisticCode']], $update_data);


            //如果轨迹状态为5-已签收或者6-问题件，则保存轨迹详情到详情表
            if ($StateEx >= RECEIVED_STATUS) {
                $this->Logistics_info_model->save_track_detail($item['LogisticCode'], $item['ShipperCode'], json_encode($item,320));
            }
        }

        $res = array(
            'EBusinessID' => isset($_result['EBusinessID']) ? $_result['EBusinessID'] : '',
            'UpdateTime' => date('Y-m-d H:i:s', time()),
            'Success' => true,
            'Reason' => ''
        );
        echo json_encode($res);
    }

    /**
     * 自动查询订单物流轨迹，获取物流轨迹状态（同一快递单避免2小时内重复查询）
     * /Logistics_info_api/autoGetOrderTraces
     */
    public function autoGetOrderTraces()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $order_type = $this->input->get_post('order_type');//该参数用于指定推送哪个表的数据（1-物流信息表，2-异常采购单退货记录表）
        $limit = $this->input->get_post('limit');
        $hour = $this->input->get_post('hour');

        $limit = empty($limit) ? 100 : ((int)$limit > 100 ? 100 : $limit);

        if (1 == $order_type) {
            //指定只查询物流信息表数据
            $express_info_list = $this->Logistics_info_model->get_not_1688_order($limit,$hour);
        } elseif (2 == $order_type) {
            //指定只查询异常采购单退货记录表数据
            $express_info_list = $this->Logistics_info_model->get_exception_order($limit);
        } else {
            //不指定类型则，先查询物流信息表数据
            $express_info_list = $this->Logistics_info_model->get_not_1688_order($limit,$hour);
            $order_type = 1;

            //再查询异常采购单退货记录表数据
            if (empty($express_info_list)) {
                $express_info_list = $this->Logistics_info_model->get_exception_order();
                $order_type = 2;
            }
        }

        if (empty($express_info_list)) {
            exit('没有符合条件的数据了');
        }

        //不支持批量查询，并发不超过 10 次/S
        foreach (array_chunk($express_info_list, 10) as $chunk) {
            foreach ($chunk as $item) {
                $ShipperCode = $item['carrier_code'];
                $LogisticCode = trim($item['express_no']);
                $CustomerName = isset($item['customer_name']) ? $item['customer_name'] : '';
                $status = $item['status'];

                $result = $this->Logistics_info_model->get_track_by_kdbird($LogisticCode, $ShipperCode, $CustomerName);
                $result = json_decode($result,true);



                //处理返回的信息
                if ($result && isset($result['LogisticCode']) && isset($result['StateEx'])) {
                    //快递鸟返回状态，和我们系统的状态进行转换
                    $StateEx = $this->Logistics_info_model->switch_status($result['StateEx'], $order_type);
                    if ($StateEx <= $status) {
                        //更新查询时间
                        $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $LogisticCode], ['update_time' => date('Y-m-d H:i:s')]);
                        echo '查询成功，但轨迹状态未变化' . '<br>';
                        continue;
                    }
                    //更新轨迹状态
                    $update_data = array('status' => $StateEx, 'update_time' => date('Y-m-d H:i:s'));
                    $res = $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $LogisticCode], $update_data);

                    //如果轨迹状态为5-已签收或者6-问题件，则保存轨迹详情到详情表
                    if ($StateEx >= RECEIVED_STATUS) {
                        $this->Logistics_info_model->save_track_detail($LogisticCode, $ShipperCode, json_encode($result,320));
                    }

                    if ($res) {
                        //保存日志
                        operatorLogInsert(
                            [
                                'id' => $LogisticCode,
                                'ext' => $StateEx,
                                'type' => 'kdniao_auto_get_order_traces_success',
                                'content' => '快递鸟即时查询接口',
                                'detail' => '查询成功，并更新成功' . '[' . $LogisticCode . ':' . $StateEx . ']',
                                'user' => '定时计划',
                            ]);
                        echo '查询成功，并更新成功' . '[' . $LogisticCode . ':' . $StateEx . ']' . '<br>';
                    } else {
                        //保存日志
                        operatorLogInsert(
                            [
                                'id' => $LogisticCode,
                                'ext' => $StateEx,
                                'type' => 'kdniao_auto_get_order_traces_error',
                                'content' => '快递鸟即时查询接口' ,
                                'detail' => '查询成功，但更新失败' . '[' . $LogisticCode . ':' . $StateEx . ']' ,
                                'user' => '定时计划',
                            ]);
                        echo '查询成功，但更新失败' . '[' . $LogisticCode . ':' . $StateEx . ']' . '<br>';
                    }
                } else {
                    //更新查询时间
                    $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $LogisticCode], ['update_time' => date('Y-m-d H:i:s')]);
                    echo '轨迹详情为空' . '[' . $LogisticCode . ']' . '<br>';
                }
            }
            //延迟0.1秒
            usleep(100000);
        }
    }

    /**
     * 推送门户系统的快递单数据到wms系统
     * @author Justin
     * /Logistics_info_api/pushGatewayExpressInfoToWms
     */
    public function pushGatewayExpressInfoToWms(){
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $limit = (int)$this->input->get_post('limit');
        if ($limit <= 0 OR $limit > 200) {
            $limit = 200; //每次推送200条
        }
        $express_no = $this->input->get_post('express_no');//提供指定快递单号推送

        try {
            $this->load->model('Purchase_order_progress_model','m_progress',false,'purchase');
            //获取推送物流信息数据
            $pushData = $this->m_progress->get_gateway_express_order($limit,$express_no);
            if(empty($pushData)){
                echo date('Y-m-d H:i:s') . '暂无可推送数据';
                exit();
            }
            if(!$this->m_progress->push_express_info_to_wms($pushData)){
                echo date('Y-m-d H:i:s') . '推送失败';
                exit();
            }else{
                foreach ($pushData as $item){
                    $this->m_progress->update_push_status($item['expressNo'],$item['purchaseOrderNo']);
                }
                echo date('Y-m-d H:i:s') . '推送到成功';
            }
        } catch (Exception $exc) {
            //写入操作日志表
            operatorLogInsert(
                array(
                    'type' => 'PUR_PURCHASE_PUSH_EXPRESS_INFO',
                    'content' => '推送门户系统的快递单数据到wms系统异常',
                    'detail' => $exc->getMessage(),
                    'user' => '计划任务',
                )
            );
            exit($exc->getMessage());
        }
    }


    public function refund_rate_import()
    {
        set_time_limit(0);
        ini_set('memory_limit','1024M');
        $data = $this->input->get_post("data");
        if(!is_array($data) || count($data) == 0)exit('提交参数不能为空');

        $this->load->model('supplier/supplier_check_model');
        $res = $this->supplier_check_model->refund_rate_import($data);

        if($res['code'] == 1)exit("导入成功");
        exit("导入失败");
    }

    /**
     * 兼容旧数据推送到产品系统
     */
    public function handle_old_data_push_product()
    {
        $id = $this->input->get_post("id");
        $time = $this->input->get_post("time");
        $status = $this->input->get_post("status");
        $push = $this->input->get_post("push");
        $type = $this->input->get_post("type");
        $this->load->model('supplier/supplier_check_model');
        if($id == '' || $time == '' || $status == '')exit('提交参数不能为空');
        $res = $this->supplier_check_model->handle_old_data_push_product($id, $time, $status, $push, $type);

        if($res['code'] == 1)exit("修改成功");
        exit("修改失败");
    }

    /**
     * 兼容旧数据推送到产品系统
     */
    public function change_product_image()
    {
        $sku = $this->input->get_post("sku");
        $images = $this->input->get_post("images");
        $images_big = $this->input->get_post("images_big");
        $this->load->model('supplier/supplier_check_model');
        if($sku == '')exit('提交参数不能为空');
        $res = $this->supplier_check_model->change_product_image($sku, $images, $images_big);

        if($res['code'] == 1)exit("修改成功");
        exit("修改失败");
    }

}