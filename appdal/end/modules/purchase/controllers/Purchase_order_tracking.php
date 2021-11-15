<?php
/**
 * 采购单管理-订单跟踪
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/10/31
 * Time: 17:06
 */
class Purchase_order_tracking extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Purchase_order_progress_model');
        $this->load->model('Logistics_info_model');
    }

    /**
     * 物流轨迹详情
     * @author Manson
     */
    public function logistics_trace_list()
    {
        $pai_number = $this->input->get_post('pai_number');
        $express_no = $this->input->get_post('express_no');
        $result = $this->Purchase_order_progress_model->get_logistics_trace_info($pai_number,$express_no);
        $this->send_data($result['data'],$result['msg'],$result['code'] == 1 ? 1 :0);
    }

    /**
     * 返回快递100查询快递单链接
     */
    public function get_express_url()
    {
        $carrier_code = $this->input->get_post('carrier_code');
        $express_no = $this->input->get_post('express_no');
        //圆通快递编码转换
        if ('yuantong' == $carrier_code) $carrier_code = 'yt';
        $url = sprintf("https://www.kuaidi100.com/all/%s.shtml?mscomnu=%s",$carrier_code,$express_no);
        $this->send_data($url,'',1);
    }

    /**
     * 刷新物流轨迹状态
     * @author Justin
     */
    public function refresh_logistics_state()
    {
        $express_no = $this->input->get_post('express_no');//快递单号（多个单号用逗号分隔）
        $order_type = $this->input->get_post('order_type');//属于哪个表的快递单数据（1-物流信息表，2-异常采购单退货记录表）

        if (empty($order_type)) $this->error_json('请求参数错误');

        $express_no = array_filter(explode(',', str_replace('，', ',', $express_no)));
        if(empty($express_no)){
            $this->success_json([], null, '没有要处理的数据');
        }

        //查询快递单号数据
        $res = $this->Purchase_order_progress_model->express_is_exists($order_type,$express_no);
        if (!$res['flag']) {
            $this->error_json('快递单号[' . implode(',', $res['data']) . ']不存在');
        }else{
            //过滤状态等于‘已签收’的数据
            foreach ($res['data'] as $key =>$item){
                if( RECEIVED_STATUS == $item){
                    unset($res['data'][$key]);
                }
            }
        }

        $exception_data = array();//更新异常数据
        $success_data = array();//更新成功数据
        $unchanged_data = array();//未发生变化数据

        //快递单号数据对应物流轨迹状态
        $real_express_data = $res['data'];
        $express_no_arr = array_keys($real_express_data);
        $express_no_arr_1688 = array();//成功获取到轨迹状态的1688单快递单号

        //1.根据快递单号判断是否为1688单，是则优先通过1688接口获取轨迹详情
        if(!empty($express_no_arr)){
            $warehouse_address = $this->Logistics_info_model->get_warehouse_address();//所有仓库地址信息,仅当order_type=1时，才需要仓库地址匹配提货点，退货单不需要匹配提货点
            $order_info = $this->Logistics_info_model->get_pai_number_info($express_no_arr);
            $this->load->library('alibaba/AliOrderApi');

            $pai_number_arr = array_column($order_info, 'pai_number');
            foreach (array_chunk($pai_number_arr, 100) as $pai_number) {
                $result = $this->aliorderapi->listLogisticsTraceInfo($pai_number);
                if (!$result['code']) {
                    operatorLogInsert(
                        [
                            'type' => 'PUR_PURCHASE_REFRESH_LOGISTICS_STATE',
                            'content' => '轨迹状态更新失败',
                            'detail' => '1688接口获取轨迹详情失败[code:001]',
                            'user' => getActiveUserName(),
                        ]);
                    $this->error_json('1688接口获取轨迹详情失败[code:001]');
                }

                //循环处理每条快递单数
                foreach ($result['data'] as $key => $item) {
                    if (!isset($order_info[$key])) continue;
                    $carrier_code = $order_info[$key]['carrier_code'];
                    $express_no = $express_no_arr_1688[] = $order_info[$key]['express_no'];
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
                                        'type' => 'PUR_PURCHASE_REFRESH_LOGISTICS_STATE',
                                        'content' => '手动刷新轨迹状态成功',
                                        'detail' => '手动刷新轨迹状态,由' . $old_status . '更新为' . $status,
                                        'user' => getActiveUserName(),
                                    )
                                );
                                $success_data[] = $express_no;
                            } else {
                                //更新失敗
                                //写入操作日志表
                                operatorLogInsert(
                                    array(
                                        'id' => $express_no,
                                        'type' => 'PUR_PURCHASE_REFRESH_LOGISTICS_STATE',
                                        'content' => '手动刷新轨迹状态失败',
                                        'detail' => '手动刷新轨迹状态失败',
                                        'user' => getActiveUserName(),
                                    )
                                );
                                $exception_data[] = $express_no;
                            }
                            break;
                        }
                    }
                }
            }
        }

        //快递单号数据中剔除掉,成功获取到轨迹状态的1688单快递单号
        foreach ($express_no_arr_1688 as $item) {
            unset($real_express_data[$item]);
                    }

        //2.从快递鸟接口请求轨迹详情数据
        if(!empty($real_express_data)) {
            $tmp_express_data = array();
            foreach ($real_express_data as $key => $val) {
                $tmp_express_data[$key]['express_no'] = $key;
                $tmp_express_data[$key]['status'] = $val;
            }

            foreach (array_chunk($tmp_express_data, 10) as $chunk) {
                foreach ($chunk as $item) {
                    $express_no = $item['express_no'];
                    $status = $item['status'];
                    $_result = $this->Logistics_info_model->query_track($express_no,$order_type);
                    $_result = json_decode($_result,true);
                    //处理返回的信息
                    if ($_result && isset($_result['LogisticCode']) && isset($_result['StateEx'])) {

                        //快递鸟返回状态，和我们系统的状态进行转换
                        $StateEx = $this->Logistics_info_model->switch_status($_result['StateEx'], $order_type);
                        if ($StateEx <= $status) {
                            //查询成功，但轨迹状态未变化
                            $unchanged_data[] = $express_no;
                        continue;
                    }
                        //更新轨迹状态
                        $update_data = array('status' => $StateEx, 'update_time' => date('Y-m-d H:i:s'));
                        $res = $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $express_no], $update_data);

                        //如果轨迹状态为5-已签收或者6-问题件，则保存轨迹详情到详情表
                        if ($StateEx >= RECEIVED_STATUS) {
                            $this->Logistics_info_model->save_track_detail($express_no, $_result['ShipperCode'], json_encode($_result,320));
                        }

                        //写入操作日志表
                        operatorLogInsert(
                            array(
                                'id' => $express_no,
                                'type' => 'PUR_PURCHASE_REFRESH_LOGISTICS_STATE',
                                'content' => '手动刷新轨迹状态',
                                'detail' => '手动刷新轨迹状态,由' . $status . '更新为' . $StateEx,
                                'user' => getActiveUserName(),
                            )
                        );

                        if ($res) {
                            //查询成功，并更新成功
                            $success_data[] = $express_no;
                        } else {
                            //查询成功，但更新失败
                            $exception_data[] = $express_no;
                    }
                    } else {
                        $this->success_json([], null, '轨迹详情数据为空');
                }
                    //更新查询时间
                    $this->Logistics_info_model->update_express_order($order_type, ['express_no' => $express_no], ['update_time' => date('Y-m-d H:i:s', time())]);
                }
                //延迟0.1秒
                usleep(100000);
            }
        }

        if (!empty($exception_data)) {
            $this->error_json('快递单号[' . implode(',', $exception_data) . "]轨迹状态更新失败");
        }elseif (!empty($success_data)) {
            $this->success_json($success_data, null, '刷新成功');
        } else {
            $this->success_json($unchanged_data, null, '操作成功,轨迹状态未发生变化');
    }
    }

    /**
     * 物流轨迹详情
     * 合同单和退货单，一律不用传purchase_number
     * 网采单必须传purchase_number，不传的将会从快递鸟接口获取轨迹详情，有可能获取不到
     * @author Justin
     */
    public function logistics_track_detail()
    {
        //退货单由仓库寄出，不用传采购单号，采购单号用于获取拍单号，通过1688接口获取轨迹数据
        $purchase_number = $this->input->get_post('purchase_number');
        $express_no = $this->input->get_post('express_no');
        $order_type = $this->input->get_post('order_type');//属于哪个表的快递单数据（1-物流信息表，2-异常采购单退货记录表）

        if (empty($express_no)) {
            $this->error_json('快递单号不能为空');
        } elseif (empty($order_type)) {
            $this->error_json('参数order_type不能为空');
        }

        //返回数据
        $data_list = array();

        //1.网采单优先从1688接口获取轨迹详情
        if(!empty($purchase_number)) {
            $this->load->library('alibaba/AliOrderApi');
            $this->load->model('finance/purchase_order_pay_type_model');
            //根據採購單判斷是否為1688單，并獲取拍單號
            $order_info = $this->Logistics_info_model->is_1688_order($purchase_number);
            if ($order_info && !empty($order_info['pai_number'])) {
                //根據拍單號通過1688接口獲取軌跡詳情
                $result = $this->aliorderapi->getLogisticsTraceInfo($order_info['pai_number']);
                if ($result['code'] == 200) {
                    //转换对接前端数据格式，兼容通过物流系统接口请求数据格式
                    foreach ($result['data'] as $key => $item){
                        foreach ($item as $detail){
                            $data_list[$key][] = array(
                                'track_content'=> $detail['remark'],
                                'occur_date'=> $detail['acceptTime'],
                            );
                        }
                    }
                    $this->success_json($data_list);
                }
            }
        }

        //2.合同单和退货单从快递鸟接口获取物流轨迹数据
        $_result = $this->Logistics_info_model->query_track($express_no, $order_type);
        $_result = json_decode($_result,true);

        if (empty($_result['Traces'])) {
            $this->error_json('查询成功，轨迹详情数据为空。');
        }

        //轨迹详情
        foreach ($_result['Traces'] as $key => $content) {
           //组织返回前端数据
            $occur_time = $content['AcceptTime'];
            $occur_date = date('Y-m-d',strtotime($occur_time));

            $data_list[$occur_date][] = array(
                'track_content'=> $content['AcceptStation'],
                'occur_date'=> $occur_time,
            );
        }
        $this->success_json($data_list, null, '查询成功');
    }
}