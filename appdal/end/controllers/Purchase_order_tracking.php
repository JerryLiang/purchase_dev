<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/11/6
 * Time: 14:51
 */
class Purchase_order_tracking extends MY_Controller
{

    public function __construct()
    {
        self::$_check_login = false;
        parent::__construct();
    }

    /**
     * 将pur_purchase_order_pay_type表,pur_purchase_progress表
     * 采购单和订单跟踪列表的物流单号和快递公司信息同步到pur_purchase_logistics_info表
     *
     * @author Manson
     */
    public function sync_express_info()
    {
        try{
            set_time_limit(0);
            $this->load->model('Purchase_order_progress_model','m_progress',false,'purchase');
            $this->m_progress->sync_express_info();
        }catch (Exception $e) {
            $errorMess = $e->getMessage();
            log_message('error',$errorMess);
            die($errorMess);
        }
    }

    /**
     * 在途在途是否异常处理
     * /Purchase_order_tracking/handle_on_way_abnormal?debug=0&purchase_number=
     * @author Justin
     */
    public function handle_on_way_abnormal()
    {
        set_time_limit(3600);

        $this->load->model('Purchase_order_progress_model', 'm_progress', false, 'purchase');
        $purchase_number = str_replace('，', ',', $this->input->get_post('purchase_number'));;//手动指定采购单号（多个采购单号用逗号分隔）
        $purchase_number = !empty($purchase_number) ? array_filter(explode(',', $purchase_number)) : array();
        $debug = empty($this->input->get_post('debug')) ? 0 : 1;//调试模式，打印sql和相关数据

        if (!empty($purchase_number)) {
            //处理手工同步数据
            $result = $this->m_progress->handle_on_way_abnormal($purchase_number, $debug);
            exit(date('Y-m-d H:i:s') . ' ' . $result['msg'] . '<br/>');
        } else {
            //处理消息队列数据
            $this->_receive_on_way_mq($debug);
        }
    }

    /**
     * 处理在途是否异常消息队列数据
     * @param $debug
     * @author Justin
     */
    private function _receive_on_way_mq($debug)
    {
        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('PURCHASE_ORDER_INNER_ON_WAY_C1');
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

            $result = $this->m_progress->handle_on_way_abnormal($purchase_number_arr, $debug);
            //处理成功则删除消息队列数据
            if ($result['status']) {
                $push_mq_data = empty($result['push_mq_data']) ? array() : $result['push_mq_data'];
                foreach ($delivery_tag as $key => $tag) {
                    if(!in_array($key,$push_mq_data)){
                        $queue_obj->ack($tag); //手动发送ACK应答，通知消息队列数据已处理，删除该数据
                    }
                }
            }
            echo date('Y-m-d H:i:s') . ' ' . $result['msg'] . '<br/>';
        } else {
            echo date('Y-m-d H:i:s') . ' ' . '消息队列没有可处理的数据' . '<br/>';
        }
        $mq->disconnect();//断开连接

        //处理由于时间差，造成的数据问题
        $result=$this->m_progress->handle_on_way_abnormal_td($debug);
        echo date('Y-m-d H:i:s') . ' ' . $result['msg'] . '<br/>';

        exit();
    }

    /**
     * 在途在途是否异常处理(处理历史数据)，每次默认100条，最多不超过1000条
     * /Purchase_order_tracking/handle_on_way_abnormal_history?debug=1&purchase_number=&limit=
     * @author Justin
     */
    public function handle_on_way_abnormal_history(){
        set_time_limit(3600);
        $this->load->model('Purchase_order_progress_model', 'm_progress', false, 'purchase');

        $purchase_number = str_replace('，', ',', $this->input->get_post('purchase_number'));//手动指定采购单号（多个采购单号用逗号分隔）
        $limit = $this->input->get_post('limit');
        $debug = empty($this->input->get_post('debug')) ? 0 : 1;//调试模式，打印sql和相关数据
        $limit = empty($limit) ? 100 : ((int)$limit > 1000 ? 1000 : $limit);
        $purchase_number = !empty($purchase_number) ? array_filter(explode(',', $purchase_number)) : array();
        if (count($purchase_number) > 1000){
            echo date('Y-m-d H:i:s') . ' ' . '每次处理的PO数量不能超过1000条';
            exit();
        }

        $result = $this->m_progress->handle_on_way_abnormal_history($limit,$purchase_number,$debug);
        echo date('Y-m-d H:i:s') . ' ' . $result['msg'] . '<br/>';
        exit();
    }
}