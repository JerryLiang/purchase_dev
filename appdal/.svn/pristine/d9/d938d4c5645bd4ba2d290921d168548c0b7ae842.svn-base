<?php
/**
 * 下载中心
 * User: luxu
 * Date: 2020/02/22 15:00
 */

class Data_center extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('system/Data_center_model');
    }

    /**
      * 获取下载中心数据
     **/
    public function getCenterData()
    {
        $client_data = [];
        if( !empty($_POST) )
        {
            foreach($_POST as $key=>$value )
            {
                $client_data[$key] = $this->input->get_post($key);
            }
        }

        $limit = isset($client_data['limit'])?$client_data['limit']:20;
        $page  = isset($client_data['page'])?$client_data['page']:1;
        $result = $this->Data_center_model->getCenterData($client_data,$limit,$page);

        $this->success_json($result);
    }

    public function getCenterBoxData()
    {


        $boxmessage = [
            'data_status'=>[['data'=>2,'message'=>'待导出'],['data'=>1,'message'=>'待审核'],['data'=>3,'message'=>'导出完毕']],
            'examine_status' => [['data'=>1,'message'=>'审核通过'],['data'=>2,'message'=>'待审核'],['data'=>3,'message'=>'驳回']]
        ];
        $result['drop_down_box'] = $boxmessage;
        $data = $this->Data_center_model->getCenterSelect();
        $result['drop_down_box']['user_name'] = $data['user_name'];
        $result['drop_down_box']['examine_user_name'] = $data['examine_user_name'];
        $this->success_json($result);
    }

    /**
     * 下载数据
     * @author:luxu
     * @time:2020/02/26
     **/

    public function downExamine()
    {
        try {

            $downNums = $this->Data_center_model->get_items("data_status=1 and swoole_server='".SWOOLE_SERVER."'");
            $down_num = count($downNums);
            //限制5个以内的导出任务
            $total = 5;
            if( $down_num>=$total) {
                $message = "有" . $down_num . "个导出任务在执行，请等待下载完成，在审核。";
                $this->error_json($message);
            }

            $downIds = $this->input->get_post("ids");
            $examine_status = $this->input->get_post("examine_status");
            $remark = $this->input->get_post("remark");
            if (empty($downIds)) {
                $this->error_json("请传入下载ID");
            }

            $log_data = array(

                'examine_time' => date("Y-m-d H:i:s", time()),
                'examine_user_name' => getActiveUserName()
            );
            if ($examine_status == 1) {
                $conditions = $this->Data_center_model->getConditions($downIds);
                if (empty($conditions)) {
                    $this->error_json("导出数据不存在");
                }

               // $this->load->library('Rabbitmq');
                $this->load->library('Rabbitmq');
                foreach ($conditions as $condition_key => $condition_value) {

                //创建消息队列对象
                    $mq = new Rabbitmq();
                    //设置参数
                    $mq->setQueueName('PURCHASE_DATA_DOWN');
                    //构造存入数据
                    $mq->setExchangeName('EXPORTLIST');
                    $mq->setRouteKey('PURCHASE_DATA_DOWN_ON_WAY_R_KEY');
                    $mq->setType(AMQP_EX_TYPE_DIRECT);
                    $condition_value['condition'] = json_decode($condition_value['condition'],True);
                    $push_data = [
                        'data' => $condition_value
                    ];
                    //存入消息队列
                    $mq->sendMessage($push_data);
                    $this->Data_center_model->handle_quene_data();
                }

                $log_data['data_status'] = 1;
            }else {

                if (empty($remark)) {
                    $this->error_json('请填写备注信息');
                }
            }
            $log_data['examine_status'] = $examine_status;
            $log_data['remark'] = $remark;
            $result = $this->Data_center_model->updateCenterData($downIds, $log_data);
            if($result){
                $this->success_json("审核成功");
            }else{
                $this->error_json("审核失败");
            }


        }catch ( Exception $exception ){

            $this->error_json($exception->getMessage());
        }

    }

    public function getQueue(){

        $this->load->library('Rabbitmq');
        //创建消息队列对象
        $mq = new Rabbitmq();
        //设置参数
        $mq->setQueueName('PURCHASE_DATA_DOWN');
        $mq->setExchangeName('EXPORTLIST');
        $mq->setRouteKey('PURCHASE_DATA_DOWN_ON_WAY_R_KEY');
        $mq->setType(AMQP_EX_TYPE_DIRECT);
        //构造存入数据
        //存入消息队列
        $queue_obj = $mq->getQueue();
        //处理生产者发送过来的数据
        $envelope = $queue_obj->get();
        $data = NULL;
        if($envelope) {
            $data = $envelope->getBody();

            $queue_obj->ack($envelope->getDeliveryTag());
        }


//        $mq->ack($envelope->getDeliveryTag());
        $mq->disconnect();
        return $data;
    }

    public function handle_quene_data(){

        $this->Data_center_model->handle_quene_data();
    }

    /**
     * 删除数据
     * @author:luxu
     * @time:2020/02/26
     **/

    public function delete_center_data()
    {
        $downIds = $this->input->get_post("ids");
        $result = $this->Data_center_model->delete_center_data($downIds);
        if($result)
        {
            $this->success_json("删除成功");
        }else{
            $this->error_json("删除失败");
        }
    }
}