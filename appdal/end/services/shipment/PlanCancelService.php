<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2020/1/15
 * Time: 17:52
 */
class PlanCancelService
{
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Shipment_cancel_list_model', '', false, 'purchase_shipment');
        return $this;
    }

    /**
     * 详情页
     * @author Manson
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function cancel_detail($params)
    {
        if (empty($params['ids'])){
            throw new Exception('参数异常');
        }
        $ids = explode(',',$params['ids']);
        if (empty($ids)){
            throw new Exception('参数异常');
        }


        $db = $this->_ci->Shipment_cancel_list_model->getDatabase();
        $result = $db->select('b.id')
            ->from($this->_ci->Shipment_cancel_list_model->table_name().' a')
            ->join('purchase_order_items b','a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
            ->where_in('a.id',$ids)
            ->get()->result_array();

        if (empty($result)){
            throw new Exception('未查询到对应的数据');
        }
        //根据采order_items表id获取详情
        $purchase_order_items_ids = array_column($result,'id');
        //同采购单的取消未到货功能
        $this->_ci->load->model('purchase_order_determine_model','',false,'purchase');
        $data_list = $this->_ci->purchase_order_determine_model->get_cancel_unarrived_goods($purchase_order_items_ids);
        return $data_list;
    }

    /**
     * 驳回
     * @author Manson
     * @param $params
     * @throws Exception
     */
    public function purchase_reject($params)
    {
        if (empty($params['ids'])){
            throw new Exception('参数异常');
        }
        if (empty($params['remark'])){
            throw new Exception('备注信息必填');
        }
        $ids = explode(',',$params['ids']);
        if (empty($ids)){
            throw new Exception('参数异常');
        }

        $total = $total_success = $total_fail = 0;
        $update_data = $push_data = $success_demand_number_list = $fail_demand_number_list = [];
        $this->_ci->load->model('Shipment_cancel_list_model','',false,'purchase_shipment');

        $total = count($ids);
        //分批处理
        for ($i = 0; $i < $total; $i+=300){
            $audit_status_map = $this->_ci->Shipment_cancel_list_model->check_audit_status(array_slice($ids,$i,300));

            foreach ($ids as $id){
                if (!isset($audit_status_map[$id])){
                    throw new Exception('未查询到对应的数据');
                }
                $cancel_list_info = $audit_status_map[$id];

                if ($cancel_list_info['audit_status'] != 1){
//                    throw new Exception(sprintf('%s不是待采购审核状态',$cancel_list_info['new_demand_number']));
                    $fail_demand_number_list[$cancel_list_info['new_demand_number']] = '不是待采购审核状态';
                    continue;
                }

                $push_data[$cancel_list_info['new_demand_number']] = [
                    'itemsId' => $id,
                    'purchaseNumber' => $cancel_list_info['purchase_number'],
                    'sku' => $cancel_list_info['sku'],
                    'cancelCtq' => 0,//驳回 实际取消数量为0
                    'demandNumber' => $cancel_list_info['new_demand_number'],
                    'cancelType' => 2//写死, 计划取消
                ];
            }

            if (empty($push_data)){
                continue;
//                throw new Exception('没有要执行的数据');
            }


            $push_result = $this->push_act_cancel_qty_to_plan($push_data);
            if ($push_result === false){
                throw new Exception('推送计划系统,java接口返回失败');
            }else{
                //推送成功
                $success_list = $push_result['successList']??[];
                foreach ($success_list as $val){
                    if (isset($push_data[$val])){
                        $update_data[$push_data[$val]['demandNumber']] = [
                            'id' => $push_data[$val]['itemsId'],
                            'audit_time' => date('Y-m-d H:i:s'),
                            'audit_user_name' =>  getActiveUserName(),
                            'audit_status' => 3,//驳回
                            'audit_remark' => $params['remark']
                        ];

                        $success_demand_number_list[$push_data[$val]['demandNumber']] = 1;
                    }
                }
            }
        }

        $db = $this->_ci->Shipment_cancel_list_model->getDatabase();
        $db->trans_begin();
        if (!empty($update_data)){
            $db->update_batch($this->_ci->Shipment_cancel_list_model->table_name(),$update_data,'id');
        }

        if ($db->trans_status() === false) {
            throw new Exception('事务提交出错');
        } else {
            $db->trans_commit();
            unset($update_data);
            $total_success = count($success_demand_number_list);
            $total_fail = $total - $total_success;
            $fail_reason = '';
            foreach ($fail_demand_number_list as $key => $value){
                $fail_reason .= sprintf('新备货单号[%s],失败原因[%s];',$key,$value);
            }

            return ['status' => 1, 'errorMess' => sprintf('操作成功,操作总数:%s,成功:%s,失败:%s,失败原因:%s', $total,$total_success,$total_fail,$fail_reason)];
        }

    }

    /**
     * 推送实际取消数量给计划系统
     * http://192.168.71.156/web/#/87?page_id=17123
     * @author Manson
     * @param $supplier_code_list
     */
    public function push_act_cancel_qty_to_plan($push_data)
    {
        $push_data = json_encode(array_values($push_data));

        $access_token = getOASystemAccessToken();
        //推送计划系统
        $url = getConfigItemByName('api_config', 'java_system_plan', 'push_act_cancel_qty');
        $url    = $url.'?access_token='.$access_token;
        $header = ['Content-Type: application/json'];
        $res = getCurlData($url, $push_data, 'POST',$header);
        $result = json_decode($res, TRUE);

        if (isset($result['code']) && $result['code']==200 && isset($result['data']) && !empty($result['data'])){
            return $result['data'];

        }else{
            return false;
        }
    }
}



