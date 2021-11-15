<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/23
 * Time: 16:30
 */

class Payment_order_pay_api extends MY_API_Controller{
    protected $push_pay_status_to_plan_key = 'push_pay_status_to_plan';//推送付款状态至计划系统

    public function __construct(){
        parent::__construct();
        $this->load->model('Purchase_order_cancel_model','m_cancel',false,'purchase');
        $this->load->model('Purchase_order_items_model','m_order_item',false,'purchase');
        $this->load->model('Purchase_suggest_map_model','m_purchase_suggest',false,'purchase_suggest');
        $this->load->model('Payment_order_pay_api_model');
    }

    /**
     * 推送付款状态,付款时间至计划系统
     * /Payment_order_pay_api/push_pay_status_to_plan
     */
    public function push_pay_status_to_plan()
    {
        if(PUSH_PLAN_SWITCH == false) return true;
        $len = $this->rediss->set_scard($this->push_pay_status_to_plan_key);// 获取集合元素的个数

        if($len){
            $count = ($len > 100)?100:$len;
            $this->load->model('purchase/Purchase_order_model');
            $_SESSION['user_name'] = '系统';// 设置默认用户，getActiveUsername会用到

            for($i = 0;$i < $count;$i ++){
                $redis_value = $this->rediss->set_spop($this->push_pay_status_to_plan_key);
                $purchase_number = current($redis_value);
                try{

                    //根据采购单获取对应的备货单
                    $result = $this->Payment_order_pay_api_model->get_push_pay_info($purchase_number);
                    if (!empty($result)){
                        foreach ($result as $item){
                            if ($item['source_from'] != 1){//只推送计划系统过来的备货单
                                continue;
                            }
                            $push_data[] = [
                                'pur_sn' => $item['demand_number'],
                                'state' => $item['suggest_status'],//需求单状态
                                'pay_status' => $item['pay_status'],//付款状态
                                'pay_time' => $item['pay_time'],//付款时间
                                'business_line' => $item['purchase_type_id']//业务线
                            ];
                        }
                        $this->Payment_order_pay_api_model->push_pay_info($push_data);

                    }else{
                        throw new Exception(sprintf('该采购单:%s,查询结果为空;',$purchase_number));
                    }

                }catch(Exception $e){
                    $this->rediss->set_sadd($this->push_pay_status_to_plan_key,$purchase_number);// 执行失败 下次继续执行
                    echo $e->getMessage();
                }
            }
            exit('执行完毕');
        }else{
            exit("没有需要操作的数据");
        }
    }
}