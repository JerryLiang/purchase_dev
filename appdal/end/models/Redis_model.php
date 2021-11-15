<?php
/**
 * Created by PhpStorm.
 * 数据推送Redis API 接口类
 * User: Jolon
 * Date: 2019/11/04 11:50
 */
class Redis_model extends Purchase_model {

    /**
     * 接收仓库推送 入库记录 触发采购单状态变更
     * @param $purchase_number
     * @return bool
     */
    public function received_order_status_queue($purchase_number){
        $this->rediss->set_sadd('REC_WARE_RECORDS',$purchase_number);
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_REC_WARE_RECORDS');
        return true;
    }

    /**
     * 把需要更新采购单状态、推送数据等操作放入 redis 队列中
     * @author Jolon
     * @param $purchase_number
     * @return bool
     */
    public function push_send_order_status_queue($purchase_number){
        $this->rediss->set_sadd('SEND_ORDER_STATUS_WARE',$purchase_number);
        $this->rediss->persist('PUR_WEB_REDIS_EXPRESS_SEND_ORDER_STATUS_WARE');
        return true;
    }



}