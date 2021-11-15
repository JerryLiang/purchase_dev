<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * 对账单功能数据初始化操作
 * User: Jolon
 * Date: 2020/05/06
 */

class Plan_compact extends MY_API_Controller{

    private $operator_key = null;

    public function __construct(){
        parent::__construct();

        $this->load->model('purchase/purchase_order_model');
        $this->load->model('compact/Compact_model');
        $this->load->model('compact/Compact_items_model');
        $this->load->model('statement/Purchase_inventory_items_model','purchaseIIModel');
        $this->load->model('supplier_joint_model');

    }


    /**
     * 刷新合同列表合同状态
     * @link Plan_compact/refresh_compact_status
     */
    public function refresh_compact_status(){
        set_time_limit(0);
        $start_time = time();


        $this->load->library('Rabbitmq');
        $mq = new Rabbitmq();//创建消息队列对象
        $mq->setQueueName('COMPACT_STATUS_REFRESH');//设置参数
        $mq->setExchangeName('COMPACT_STATUS_NAME');
        $mq->setRouteKey('COMPACT_STATUS_UPDATE_R_KEY');
        $mq->setType(AMQP_EX_TYPE_DIRECT);

        $queue_obj = $mq->getQueue();
        $row_count = $queue_obj->declareQueue();// 获得总消息数
        $row_count = ($row_count <= 1)? $row_count : 1;// 每次最多执行 100个

        $data = NULL;
        $po_count = 0;
        $skip_count = 0;// 读取数据异常次数控制器

        // 同步合同状态：已作废  已完结
        for($i = 0; true ;$i ++){
            $envelope = $queue_obj->get();// 从队列中检索下一条消息

            if ($envelope){
                $data = $envelope->getBody();
                $order_data = json_decode($data, true);

                if(empty($order_data)){
                    $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除

                    $skip_count ++;
                    if($skip_count > 3){
                        break;
                    }else{
                        continue;
                    }
                }

                if(isset($order_data['purchase_number'])){
                    $purchase_number = $order_data['purchase_number'];

                    $compact_number = $this->Compact_items_model->get_compact_by_purchase($purchase_number);
                    if($compact_number){
                        $compact_number = $compact_number['compact_number'];
                        $compact_items = $this->Compact_items_model->get_compact_item($compact_number);

                    }else{
                        $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除
                        continue;
                    }

                }else{
                    $compact_number = $order_data['compact_number'];
                    $compact_items = $this->Compact_items_model->get_compact_item($compact_number);
                }

                // 获取合同信息
                $compact_info = $this->Compact_model->get_compact_one($compact_number);

                // 获取采购单状态
                $order_info = $this->Compact_items_model->purchase_db->where_in('purchase_number',array_column($compact_items,'purchase_number'))
                    ->get('purchase_order')
                    ->result_array();

                $order_status_arr = array_unique(array_column($order_info,'purchase_order_status'));


                $compact_status = null;
                // 所有采购单状态为已作废订单
                if(count($order_status_arr) == 1 and current($order_status_arr) == PURCHASE_ORDER_STATUS_CANCELED){
                    $compact_status = SRM_COMPACT_ACCESS_CANCELED;// 已作废
                }elseif($compact_info['payment_status'] == PAY_PAID and $compact_info['is_file_uploaded'] == SRM_COMPACT_ACCESS_STATUS){
                    $compact_status = SRM_COMPACT_ACCESS_FINISHED;// 已完结
                }

                // 更新合同状态
                if(NULL !== $compact_status){
                    $this->Compact_items_model->purchase_db->update('purchase_compact',['is_file_uploaded' => $compact_status],['compact_number' => $compact_number],1);
                    $this->Compact_items_model->purchase_db->update('supplier_web_info',['compact_audit_status' => $compact_status],['compact_num' => $compact_number]);

                    // 获取合同扫描件
                    $img_url = $this->supplier_joint_model->getCompactFile($compact_number);
                    if(empty($img_url)){

                        $img_url = '';
                    }
                    $data = ['data' => [
                        0 => ['ImgUrl' => $img_url, 'compactNum' => $compact_number, 'compactAuditStatus' => $compact_status]
                    ]];


                    $compact_status_method = '/provider/yibaiSupplierCompact/updateCompactStatus';
                    $url = SMC_JAVA_API_URL . $compact_status_method;// supplier_joint_model->compact_status_method


                    $header = array('Content-Type: application/json');
                    $access_taken = getOASystemAccessToken();
                    $url = $url . "?access_token=" . $access_taken;
                    $result = getCurlData($url, json_encode($data, JSON_UNESCAPED_UNICODE), 'post', $header);
                    apiRequestLogInsert(
                        [
                            'record_number' => '',
                            'record_type' => 'pushSmcCompactStatusData',
                            'api_url' => $url,
                            'post_content' => json_encode($data, JSON_UNESCAPED_UNICODE),
                            'response_content' => $result,
                            'status' => 1,
                        ]);
                    $this->supplier_joint_model->RecordGateWayPush(json_decode($result,True),$compact_number,$data,"CompactStatusData");

                }

                $queue_obj->ack($envelope->getDeliveryTag());// 手动确认，消息将删除
            }else{

                $skip_count ++;
                if($skip_count > 3){
                    break;
                }else{
                    continue;
                }
            }
        }

        $mq->disconnect();

        if($po_count == 0 or $row_count == 0){
            echo date('Y-m-d H:i:s') . ' ' . "Plan_compact::refresh_compact_status >>> 没有需要操作的数据<br/>\t\n";
        }else{
            echo date('Y-m-d H:i:s') . ' ' . "Plan_compact::refresh_compact_status >>> 本次执行：".$po_count."个<br/>\t\n";
        }

        exit;
    }


}