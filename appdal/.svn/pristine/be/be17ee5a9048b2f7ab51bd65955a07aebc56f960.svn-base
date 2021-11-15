<?php

/**
 * Created by PhpStorm.
 * 富有请求日志
 * User: Jackson
 * Date: 2019/02/15
 */
class Purchase_order_pay_ufxfuiou_request_log_model extends Purchase_model
{

    protected $table_name = 'ufxfuiou_request_log';

    public function __construct()
    {
        parent::__construct();

    }

    /**
     * @desc 保存富有请求日志
     * @author Jackson
     * @parames array $response 返回数据
     * @parames string $refreshTranNo 交易流水号
     * @parames string $postParams 请求参数
     * @parames string $type 类型(1发出请求,2接受请求,3手动获取支付状态)
     * @Date 2019-02-12 18:01:00
     * @return array()
     **/
    public function payfuiou_request_log($response, $refreshTranNo, $postParams = '', $type = 1)
    {

        //事务开始
        $this->db->trans_start();
        try {
            $data = array();
            $data['create_time'] = date('Y-m-d H:i:s', time());
            $data['create_user_name'] = $type == 2 ? '富友银行卡转账回调' : getActiveUserName();
            $data['request_response'] = !empty($response['response']) ? $response['response'] : '无回调数据';
            $data['post_params'] = $postParams;
            $data['type'] = $type;
            $data['pur_tran_no'] = $refreshTranNo;
            $this->insert($data);

            //判断是否保存成功
            $this->db->trans_complete();
            if ($this->db->trans_status() === false) {
                throw new Exception('更新数据失败-富友支付请求与接受数据表');
            }

        } catch (Eexception $e) {
            throw new Eexception($e->getMessage());
        }
        return true;
    }
}