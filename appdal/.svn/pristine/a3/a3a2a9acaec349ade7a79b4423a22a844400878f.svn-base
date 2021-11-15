<?php
defined('BASEPATH') OR exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";

class Check_product_api extends MY_API_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('supplier/check/Check_product_model', 'check_product_model');
    }

    /**
     * 自动创建验货单
     * /Check_product_api/auto_create_inspection
     */
    public function auto_create_inspection()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '512M');
        $limit = $this->input->get_post('limit');//手动指定限制条数

        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();
        $mq->setQueueName('PUR_SUPPLIER_CHECK_Q_NAME');
        $mq->setExchangeName('PUR_SUPPLIER_CHECK_EX_NAME');
        $mq->setRouteKey('PUR_SUPPLIER_CHECK_R_KEY');
        $mq->setType(AMQP_EX_TYPE_DIRECT);

        //接收消息
        $queue_obj = $mq->getQueue();
        $total = $queue_obj->declareQueue();

        if ($total) {
            for ($i = 1; $i <= $total; $i++) {
                //每次处理限制条数
                if (!empty($limit) && is_numeric($limit) && $i > $limit) {
                    break;
                }
                //处理生产者发送过来的数据
                $envelope = $queue_obj->get();
                $data = $envelope->getBody();
                $data = json_decode($data, true);
                $purchase_number = $data[0];

                //删除空数据
                if (empty($purchase_number)) {
                    $queue_obj->ack($envelope->getDeliveryTag()); //手动发送ACK应答，通知消息队列数据已处理，删除该数据
                    continue;
                }
                //根据验货规则，判断是否需要验货
                $result = $this->check_product_model->auto_create_inspection($purchase_number, 'system');
                $queue_obj->ack($envelope->getDeliveryTag()); //手动发送ACK应答，通知消息队列数据已处理，删除该数据

                if ($result['flag']) {
                    //创建验货单成功，写入日志
                    operatorLogInsert(
                        [
                            'id' => $purchase_number,
                            'type' => 'AUTO_CREATE_INSPECTION_SUCCESS',
                            'content' => '系统自动创建验货单成功',
                            'detail' => $result['msg'],
                            'user' => '定时计划',
                        ]);
                } else {
                    //创建验货单失败，或者不满足验货条件，写入日志
                    operatorLogInsert(
                        [
                            'id' => $purchase_number,
                            'type' => 'AUTO_CREATE_INSPECTION_FAILURE',
                            'content' => '系统自动创建验货单失败',
                            'detail' => $result['msg'],
                            'user' => '定时计划',
                        ]);
                }
                echo date('Y-m-d H:i:s') . ' ' . $result['msg'] . '<br/>';

                //延迟0.1秒
                usleep(100000);
            }
        } else {
            echo date('Y-m-d H:i:s') . ' ' . '没有可处理的数据' . '<br/>';
        }
        $mq->disconnect();//断开连接
        exit();
    }

    /**
     * 检测是否sku异常
     * /Check_product_api/check_is_abnormal
     */
    public function check_is_abnormal()
    {
        set_time_limit(3600);
        ini_set('memory_limit', '512M');

        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('PUR_SUPPLIER_CHECK_C1');
        $mq->setExchangeName('PURCHASE_ORDER_INNER_ON_WAY_EX_NAME');
        $mq->setRouteKey('PURCHASE_ORDER_INNER_ON_WAY_R_KEY');
        $mq->setType(AMQP_EX_TYPE_FANOUT);//设置为多消费者模式 分发

        //接收消息
        $queue_obj = $mq->getQueue();
        $total = $queue_obj->declareQueue();

        if ($total) {
            $purchase_number_arr = array();//要处理的采购单号
            $delivery_tag = array();//已处理的消息队列数据标识
            for ($i = 1; $i <= $total; $i++) {
                //处理生产者发送过来的数据
                $envelope = $queue_obj->get();
                $data = $envelope->getBody();
                $order_data = json_decode($data, true);
                $purchase_number = $order_data['purchase_number'];
                //删除空数据
                if (empty($purchase_number)) {
                    $queue_obj->ack($envelope->getDeliveryTag()); //手动发送ACK应答，通知消息队列数据已处理，删除该数据
                    continue;
                }
                $purchase_number_arr = array_unique(array_merge($purchase_number_arr, [$purchase_number]));

                $delivery_tag[$purchase_number] = $envelope->getDeliveryTag();
                //每次从消息队列取出PO号数量大于200则停止取出
                if (count($purchase_number_arr) > 200) break;
            }

            if (empty($purchase_number_arr)) {
                $mq->disconnect();//断开连接
                exit(date('Y-m-d H:i:s') . ' ' . '消息队列没有可处理的数据<br/>');
            }

            $result = $this->check_product_model->check_is_abnormal($purchase_number_arr, 'system');
            //处理成功，删除消息队列
            if ($result['flag']) {
                foreach ($delivery_tag as $key => $tag) {
                    $queue_obj->ack($tag); //手动发送ACK应答，通知消息队列数据已处理，删除该数据
                }
            }
            echo date('Y-m-d H:i:s') . ' ' . $result['msg'] . '<br/>';
        } else {
            echo date('Y-m-d H:i:s') . ' ' . '消息队列没有可处理的数据' . '<br/>';
        }
        $mq->disconnect();//断开连接
        exit();
    }

    /**
     * 处理产品系统推送验货结果数据
     * /Check_product_api/inspect_result_receive
     */
    public function inspect_result_receive()
    {
        set_time_limit(1800);
        ini_set('memory_limit', '512M');

        if ($this->input->method(TRUE) != 'POST') {
            $this->_return_json(false, '接口支持的消息接收方式为HTTP POST');
        }

        $_result = file_get_contents("php://input");
        $_result = json_decode($_result, TRUE);
        //推送数据写入日志
        operatorLogInsert([
            'type' => 'INSPECT_RESULT_RECEIVE',
            'content' => '产品系统推送验货结果数据',
            'detail' => json_encode($_result, 320),
        ]);

        if (empty($_result['data']) OR empty(array_filter($_result['data']))) {
            $this->_return_json(false, '推送数据不能为空');
        } elseif (empty($_result['type']) OR !in_array($_result['type'], [1, 2, 3, 4, 5])) {
            $this->_return_json(false, '推送类型type错误');
        }

        $success = 0;//成功标识
        foreach ($_result['data'] as $item) {
            if (empty($item['applyNo'])) {
                $this->_return_json(false, '验货编码不能为空');
            } elseif (empty($item['batch'])) {
                $this->_return_json(false, '验货批次batch不能为空');
            }

            switch ($_result['type']) {
                case 1:
                    //处理免检审核操作
                    $params = $this->_exemption_audit_handle($item);
                    break;
                case 2:
                    //处理产品系统排期分配任务，或者重新分配任务操作
                    $params = $this->_schedule_handle($item);
                    break;
                case 3:
                    //处理产品系统转合格操作
                    $params = $this->_qualify_for_apply_handle($item);
                    break;
                case 4:
                    //处理产品系统特批出货操作
                    $params = $this->_special_shipment_handle($item);
                    break;
                case 5:
                    //处理产品系统推送验货结果操作
                    $params = $this->_result_handle($item);
                    break;
            }
            $result = $this->check_product_model->update_order($_result['type'], $params);

            if ($result['flag']) {
                $success = 1;
            } else {
                $this->_return_json(false, $result['msg']);
            }
            //延迟0.1秒
            usleep(100000);
        }

        if ($success) {
            $this->_return_json(true, '推送成功');
        } else {
            $this->_return_json(false, '推送失败');
        }
    }

    /**
     * 处理免检审核操作
     * @param $data
     * @return array
     */
    private function _exemption_audit_handle($data)
    {
        if (!isset($data['result']) OR !isset($data['approverCode']) OR !isset($data['approver'])
            OR !isset($data['approvalRemark']) OR !isset($data['approvalTime'])
            OR !isset($data['assignCode']) OR !isset($data['assignName'])) {
            $res = array(
                'status' => false,
                'msg' => '请求参数不全[type:1]'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $user_id = getUserIDByStaffCode($data['approverCode']);
        $params = array(
            'check_code' => $data['applyNo'],
            'batch_no' => $data['batch'],
            'status' => $data['result'] ? CHECK_ORDER_STATUS_EXEMPTION : CHECK_ORDER_STATUS_EXEMPTION_REJECT,//状态：同意免检->8-免检，否则->4-免检驳回
            'judgment_result' => $data['result'] ? CHECK_ORDER_RESULT_EXEMPTION : CHECK_ORDER_RESULT_EXEMPTION_REJECT,//结果：同意免检->2-免检，否则->1-免检驳回
            'approval_user_id' => $user_id ? $user_id : 0,
            'approval_user_name' => $data['approver'],
            'approval_remark' => $data['approvalRemark'],
            'approval_time' => $data['approvalTime'],
            'assigner_code' => $data['assignCode'],//分配员工号（用于回传产品系统）
            'assigner_name' => $data['assignName'],//分配员（用于回传产品系统）
        );
        return $params;
    }

    /**
     * 处理产品系统排期分配任务，或者重新分配任务操作
     * @param $data
     * @return array
     */
    private function _schedule_handle($data)
    {
        if (!isset($data['inspectorCode']) OR !isset($data['inspector']) OR !isset($data['scheduleInspectTime'])
            OR !isset($data['approverCode']) OR !isset($data['approver'])) {
            $res = array(
                'status' => false,
                'msg' => '请求参数不全[type:2]'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $user_id = getUserIDByStaffCode($data['approverCode']);
        $params = array(
            'check_code' => $data['applyNo'],
            'batch_no' => $data['batch'],
            'status' => CHECK_ORDER_STATUS_QUALITY_CHECKING,
            'inspector_number' => $data['inspectorCode'],//验货员工号
            'inspector' => $data['inspector'],//验货员
            'schedule_inspect_time' => $data['scheduleInspectTime'],
            'operator_id' => $user_id ? $user_id : 0,//审核人(操作人，分配人)id
            'operator' => $data['approver'],//审核人(操作人，分配人)
            'operator_code' => $data['approverCode'],//审核人(操作人，分配人)工号
        );
        return $params;
    }

    /**
     * 处理产品系统转合格操作
     * @param $data
     * @return array
     */
    private function _qualify_for_apply_handle($data)
    {
        if (empty($data['items']) OR !is_array($data['items']) OR empty($data['approvalTime'])
            OR empty($data['approverCode']) OR empty($data['approver']) OR empty($data['assignCode'])
            OR empty($data['assignName'])) {
            $res = array(
                'status' => false,
                'msg' => '请求参数错误[type:3]'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $status_tmp = CHECK_ORDER_STATUS_QUALIFIED;//默认状态-验货合格
        $result_tmp = CHECK_ORDER_RESULT_QUALIFIED;//默认结果-合格
        $sku_data_tmp = array();
        $approval_remark = array();//主表审批备注
        foreach ($data['items'] as $item) {
            if (!isset($item['result']) OR empty($item['sku']) OR !isset($item['approvalRemark'])) {
                $res = array(
                    'status' => false,
                    'msg' => '请求参数items数据格式不符[type:3]'
                );
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                exit;
            }
            //状态：同意转合格->10-验货合格，否则->11-验货不合格
            $status = $item['result'] ? CHECK_ORDER_STATUS_QUALIFIED : CHECK_ORDER_STATUS_UNQUALIFIED;
            //结果：同意转合格->3-合格，否则->4-不合格
            $result = $item['result'] ? CHECK_ORDER_RESULT_QUALIFIED : CHECK_ORDER_RESULT_UNQUALIFIED;
            $sku_data_tmp[] = array(
                'sku' => $item['sku'],
                'status' => $status,
                'judgment_result' => $result,
                'approval_remark' => $item['approvalRemark']
            );
            //验货单验货结果,只要sku验货结果‘不合格’≥1，则验货单验货结果为‘不合格’
            if (CHECK_ORDER_RESULT_UNQUALIFIED == $result) {
                $result_tmp = CHECK_ORDER_RESULT_UNQUALIFIED;
                $approval_remark[] = trim($item['approvalRemark']);//只保留审批不合格的审批备注
            }
            //验货单验货状态,只要sku验货结果‘不合格’≥1，则验货单验货状态为‘验货不合格’
            if (CHECK_ORDER_RESULT_UNQUALIFIED == $result) {
                $status_tmp = CHECK_ORDER_STATUS_UNQUALIFIED;
            }
        }
        $user_id = getUserIDByStaffCode($data['approverCode']);
        $params = array(
            'check_code' => $data['applyNo'],
            'batch_no' => $data['batch'],
            'status' => $status_tmp,
            'judgment_result' => $result_tmp,
            'approval_time' => $data['approvalTime'],
            'approver_code' => $data['approverCode'],
            'approver' => $data['approver'],
            'approver_id' => $user_id ? $user_id : 0,
            'approval_remark' => implode(';', array_unique($approval_remark)),
            'items' => $sku_data_tmp,
            'assigner_code' => $data['assignCode'],
            'assigner_name' => $data['assignName'],
        );
        return $params;
    }

    /**
     * 处理产品系统特批出货操作
     * @param $data
     * @return array
     */
    private function _special_shipment_handle($data)
    {
        if (empty($data['sku']) OR empty($data['attachment']) OR empty($data['approverCode'])
            OR empty($data['approver']) OR !isset($data['approvalRemark']) OR empty($data['approvalTime'])) {
            $res = array(
                'status' => false,
                'msg' => '请求参数不全[type:4]'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }
        $data['attachment'] = json_decode($data['attachment'], true);
        if(empty($data['attachment'])){
            $res = array(
                'status' => false,
                'msg' => '附件信息不能为空'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        $user_id = getUserIDByStaffCode($data['approverCode']);
        $params = array(
            'check_code' => $data['applyNo'],
            'batch_no' => $data['batch'],
            'is_special' => 1,
            'approval_user_id' => $user_id ? $user_id : 0,
            'approval_user_name' => $data['approver'],
            'approval_remark' => $data['approvalRemark'],
            'approval_time' => $data['approvalTime'],
            'sku' => $data['sku'],
            'attachment' => $data['attachment']
        );
        return $params;
    }

    /**
     * 处理产品系统推送验货结果操作
     * @param $data
     * @return array
     */
    private function _result_handle($data)
    {
        if (empty($data['items']) OR !is_array($data['items'])) {
            $res = array(
                'status' => false,
                'msg' => '请求参数错误[type:5]'
            );
            echo json_encode($res, JSON_UNESCAPED_UNICODE);
            exit;
        }

        //产品系统推送验货结果（1-取消验货，2-免检，3-合格，4-不合格，5-转IQC）
        $sku_data_tmp = array();
        $attachment_tmp = array();
        $include_unqualified = 0;//结果是否包含合格
        $include_qualified = 0;//结果是否包含不合格
        $include_iqc = 0;//结果是否包含转IQC
        $include_exemption = 0;//结果是否包含免检

        foreach ($data['items'] as $key => $item) {
            if (!isset($item['sku']) OR !isset($item['result']) OR !isset($item['receivedQty']) OR !isset($item['inspectorRemark']) OR !isset($item['defectiveType'])
                OR !isset($item['defectiveReason']) OR !isset($item['improvementMeasure']) OR !isset($item['responsiblePerson']) OR !isset($item['responsibleDepartment'])
                OR !isset($item['attachment']) OR !isset($item['defectiveQty'])) {
                $res = array(
                    'status' => false,
                    'msg' => '请求参数items数据格式不符[type:5]'
                );
                echo json_encode($res, JSON_UNESCAPED_UNICODE);
                exit;
            }
            //验货单状态和结果判断标识
            if (3 == $item['result']) {
                $include_qualified = 1;
            } elseif (4 == $item['result']) {
                $include_unqualified = 1;
            } elseif (5 == $item['result']) {
                $include_iqc = 1;
            } else {
                $include_exemption = 1;
            }

            //sku验货状态
            switch ($item['result']) {
                case 1:
                case 2:
                    //1取消验货->8-免检
                    //2免检->8-免检
                    $sku_data_tmp[$key]['status'] = CHECK_ORDER_STATUS_EXEMPTION;
                    break;
                case 3:
                    //3合格->10-验货合格
                    $sku_data_tmp[$key]['status'] = CHECK_ORDER_STATUS_QUALIFIED;
                    break;
                case 4:
                    //4不合格->11-验货不合格
                    $sku_data_tmp[$key]['status'] = CHECK_ORDER_STATUS_UNQUALIFIED;
                    break;
                case 5:
                    //5转IQC->转IQC验货
                    $sku_data_tmp[$key]['status'] = CHECK_ORDER_STATUS_IQC;
                    break;
                default:
                    $sku_data_tmp[$key]['status'] = 0;
                    break;
            }
            //sku验货结果
            switch ($item['result']) {
                case 1:
                case 2:
                    //1取消验货->2-免检
                    //2免检->2-免检
                    $sku_data_tmp[$key]['judgment_result'] = CHECK_ORDER_RESULT_EXEMPTION;
                    break;
                case 3:
                    //3合格->3-合格
                    $sku_data_tmp[$key]['judgment_result'] = CHECK_ORDER_RESULT_QUALIFIED;
                    break;
                case 4:
                    //4不合格->4-不合格
                    $sku_data_tmp[$key]['judgment_result'] = CHECK_ORDER_RESULT_UNQUALIFIED;
                    break;
                case 5:
                    //5转IQC->转IQC
                    $sku_data_tmp[$key]['judgment_result'] = CHECK_ORDER_RESULT_IQC;
                    break;
                default:
                    $sku_data_tmp[$key]['judgment_result'] = 0;
                    break;
            }
            $sku_data_tmp[$key]['sku'] = $item['sku'];
            $sku_data_tmp[$key]['received_qty'] = $item['receivedQty'];
            $sku_data_tmp[$key]['inspector_remark'] = $item['inspectorRemark'];
            $sku_data_tmp[$key]['defective_type'] = $item['defectiveType'];
            $sku_data_tmp[$key]['defective_qty'] = $item['defectiveQty'];
            $sku_data_tmp[$key]['defective_reason'] = $item['defectiveReason'];
            $sku_data_tmp[$key]['improvement_measure'] = $item['improvementMeasure'];
            $sku_data_tmp[$key]['responsible_person'] = $item['responsiblePerson'];
            $sku_data_tmp[$key]['responsible_department'] = $item['responsibleDepartment'];
            // 33109
            $sku_data_tmp[$key]['box_all'] = $item['boxAll'];// 总箱数
            $sku_data_tmp[$key]['box_psc'] = $item['boxPsc']; // 箱内数
            $sku_data_tmp[$key]['box_size'] = $item['boxSize'];// 外箱尺寸(cm)
            $sku_data_tmp[$key]['box_weight'] = $item['boxWeight']; // 外箱毛重(Kg)
            $sku_data_tmp[$key]['box_tail_size'] = $item['boxTailSize']; // 尾箱尺寸(cm)
            $sku_data_tmp[$key]['box_tail_weight'] = $item['boxTailWeight']; // 尾箱毛重(Kg)
            $sku_data_tmp[$key]['box_tail_psc'] = $item['boxTailPsc']; // 尾数
            $sku_data_tmp[$key]['box_lock_number'] = $item['boxLockNumber']; // 封柜锁编号

            $attachment_tmp[$item['sku']] = json_decode($item['attachment'], true);
        }

        //验货单状态判断,验货单结果判断
        if ($include_unqualified && 1 == $data['batch']) {
            //≥1个不合格，首次推送结果，那么编码状态=不合格待确认;
            $status_tmp = CHECK_ORDER_STATUS_UNQUALIFIED_WAITING_CONFIRM;
            $result_tmp = CHECK_ORDER_RESULT_UNQUALIFIED;
            $approval_remark = '不合格';
        } elseif ($include_unqualified) {
            //≥1个不合格，重验推送结果，编码状态=验货不合格;
            $status_tmp = CHECK_ORDER_STATUS_UNQUALIFIED;
            $result_tmp = CHECK_ORDER_RESULT_UNQUALIFIED;
            $approval_remark = '不合格';
        } elseif ($include_qualified) {
            //所有的都是合格+免检/转IQC，那么状态=合格
            $status_tmp = CHECK_ORDER_STATUS_QUALIFIED;
            $result_tmp = CHECK_ORDER_RESULT_QUALIFIED;
            $approval_remark = '合格';
        } elseif ($include_iqc) {
            //所有的都是转IQC+免检，那么验货状态=转IQC
            $status_tmp = CHECK_ORDER_STATUS_IQC;
            $result_tmp = CHECK_ORDER_RESULT_IQC;
            $approval_remark = '转IQC';
        } else {
            //所有的都是免检，那么验货状态=免检
            $status_tmp = CHECK_ORDER_STATUS_EXEMPTION;
            $result_tmp = CHECK_ORDER_RESULT_EXEMPTION;
            $approval_remark = '取消验货/免检';
        }
        $user_id = getUserIDByStaffCode($data['inspectorCode']);
        $params = array(
            'check_code' => $data['applyNo'],
            'batch_no' => $data['batch'],
            'status' => $status_tmp,
            'judgment_result' => $result_tmp,
            'approval_user_id' => $user_id ? $user_id : 0,
            'approval_user_name' => $data['inspector'],
            'inspector_code' => $data['inspectorCode'],
            'approval_remark' => $approval_remark,
            'approval_time' => $data['operationTime'],
            'inspect_time' => $data['inspectTime'],
            'sku' => $sku_data_tmp,
            'attachment' => array_filter($attachment_tmp)
        );
        return $params;
    }

    /**
     * 返回json格式消息
     * @param bool $flag
     * @param string $msg
     */
    private function _return_json($flag = false, $msg = '')
    {
        $res = array(
            'status' => $flag,
            'msg' => $msg
        );
        echo json_encode($res, JSON_UNESCAPED_UNICODE);
        exit;
    }
}