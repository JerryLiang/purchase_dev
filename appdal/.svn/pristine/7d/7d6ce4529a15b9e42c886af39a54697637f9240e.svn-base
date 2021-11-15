<?php
/**
 * 采购单自动请款
 * User: Jolon
 * Date: 2019/03/20 10:00
 */
class Purchase_auto_payout extends MY_Controller {

    public function __construct(){
        parent::__construct();

        $this->load->model('finance/Purchase_auto_payout_model');
    }

    /**
     * 采购单自动请款【人工自动请款】
     */
    public function auto_payout(){
        $create_notice           = '【人工自动请款】';
        $purchase_number_list    = $this->input->get_post('purchase_numbers');
        $purchase_numbers        = $this->input->get_post('purchase_number');
        if(empty($purchase_number_list)){
            $purchase_number_list = $purchase_numbers;
        }
        if($purchase_number_list){
            if(!is_array($purchase_number_list))
                $purchase_number_list = explode(',',$purchase_number_list);

        }else{
            // 读取缓存的查询SQL
            $new_get_list_querySql = $this->rediss->getData(md5(getActiveUserId().'-new_get_list'));
            if(empty($new_get_list_querySql)) $this->error_json("缓存数据读取异常，请您先点击查询按钮刷新页面后，再操作，谢谢！");
            $new_get_list_querySql = base64_decode($new_get_list_querySql);

            // 截取第一个FROM 和 最后一个GROUP BY 之间的字符串
            $new_get_list_querySql = preg_replace("/(LIMIT)[\w\W]+(\))/",')',$new_get_list_querySql);
            // 在付款状态=未申请付款、驳回（经理驳回、供应链总监驳回、财务驳回、财务主管驳回、财务总监驳回、总经办驳回）这些付款状态下可以点击
            $new_get_list_querySql = "SELECT `ppo`.`purchase_number`
                 FROM pur_purchase_order AS ppo
                 LEFT JOIN pur_purchase_order_items AS poi ON poi.purchase_number=ppo.purchase_number
                 WHERE poi.id IN (".$new_get_list_querySql." ) 
                 GROUP BY `ppo`.`purchase_number`";

            $purchase_number_list  = $this->Purchase_auto_payout_model->purchase_db->query($new_get_list_querySql)->result_array();
            $purchase_number_list  = array_column($purchase_number_list,'purchase_number');

        }
        if(empty($purchase_number_list)) $this->error_json('没有获取到待【自动请款】的数据，请确认操作');

        // 刷新1688金额异常
        $this->load->model('ali/Ali_order_model');
        $this->Ali_order_model->refresh_order_price($purchase_number_list);

        $error_list           = [];
        $success_list         = [];
        $purchase_number_list_tmp = $purchase_number_list;

        // 设置缓存
        foreach($purchase_number_list as $key => $purchase_number){
            $session_key = 'order_auto_payout_' . $purchase_number;
            if (!$this->rediss->getData($session_key)) {
                $this->rediss->setData($session_key, '1', 600); //设置缓存和有效时间
            } else {
                $error_list[] = $purchase_number.'，订单已占用，可能多方同时自动请款，请稍后再操作';
                unset($purchase_number_list[$key]);
            }
        }

        foreach($purchase_number_list as $purchase_number){
            try{
                // 执行自动请款操作
                $result = $this->Purchase_auto_payout_model->do_auto_payout($purchase_number,$create_notice);
                if(empty($result['code'])){
                    throw new Exception($result['message']);
                }
                $success_list[] = $purchase_number.'，自动请款成功';
            }catch(Exception $exception){
                $error_list[] = $purchase_number.'，'.$exception->getMessage();
            }
        }

        $data = [
            'success_list' => $success_list,
            'error_list'   => $error_list
        ];

        // 释放缓存
        foreach($purchase_number_list_tmp as $purchase_number){
            $session_key = 'order_auto_payout_' . $purchase_number;
            $this->rediss->deleteData($session_key);
        }

        $total   = count($purchase_number_list_tmp);
        $success = count($success_list);
        $error   = count($error_list);
        $message = "本次共自动请款成功 {$success} 个PO，失败 {$error} 个PO";

        $this->success_json($data,null,$message);
    }






}