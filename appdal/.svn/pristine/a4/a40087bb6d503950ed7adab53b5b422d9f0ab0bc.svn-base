<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/20
 * Time: 11:43
 */
class Payment_order_pay_api_model extends Purchase_model
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Purchase_order_cancel_model','m_cancel',false,'purchase');
    }

    /**
     * 查询要推送的数据
     * @author Manson
     * @param $purchase_number
     * @return array
     */
    public function get_push_pay_info($purchase_number)
    {
        if (empty($purchase_number)) return [];
        $result = $this->purchase_db->select('a.pay_status, a.pay_time, b.demand_number, c.purchase_type_id, c.suggest_status, c.source_from')
            ->from('purchase_order a')
            ->join('purchase_suggest_map b','a.purchase_number = b.purchase_number','inner')
            ->join('purchase_suggest c','b.demand_number = c.demand_number','inner')
            ->where('a.purchase_number',$purchase_number)
            ->get()->result_array();
        return $result;

    }

    /**
     * 推送付款状态, 付款时间
     * @author Manson
     * @param $push_data
     * @throws Exception
     */
    public function push_pay_info($data)
    {
        $push_data['data_list'] = $data;
        $push_data = json_encode($push_data);
        $access_token = getOASystemAccessToken();

        //推送计划系统
        $url = getConfigItemByName('api_config', 'java_system_plan', 'push_expiration_suggest');

        $url    = $url.'?access_token='.$access_token;
        $header = ['Content-Type: application/json'];
//        echo $url;
//echo ($push_data);exit;
        $res = getCurlData($url, $push_data, 'POST',$header);
        if (!is_json($res)) throw new Exception('计划系统返回的不是json: '.$res);

        $result = json_decode($res, TRUE);

        if (isset($result['code']) && $result['code']!=200){
            throw new Exception('推送计划返回信息: '.$result['msg']);
        }elseif(isset($result['error'])){
            $error_msg = $result['error_description']??$result['message'];
            throw new Exception('推送计划返回信息: '.$error_msg);
        }
    }
}