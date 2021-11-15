<?php
/**
 * 入库时间和对账单自动请款配置计算逻辑
 */

class Calc_pay_time_model extends Purchase_model
{
    /**
     * 基础配置数据
     */
    public function getSetParamData($type='')
    {
        $res = [];
        try{
            $data = $this->purchase_db->from("param_sets")->where(["pType" => $type, 'pSort' => 1])->get()->result_array();
            foreach ($data as $val){
                $pValue = json_decode($val['pValue'], true);
                $res[$pValue['settlement']] = $pValue;
            }
        }catch (Exception $e){}
        return $res;
    }

    /**
     * 应付款时间计算逻辑
     * @param array $set   配置参数，来源于 this->getSetParamData()
     * @param string $params.calc_field 计算字段：audit_time、plan_arrive_time、instock_time
     * @param string $params.calc_date 计算起始时间
     * @param mixed $params.settlement 结算方式
     * @param array $params.source 采购来源
     * @param string $params.purchase_number 采购单号
     * @param string $params.demand_number 备货单号
     * calc_pay_time([], ["calc_field"=>"instock_time"])
     * @return mixed
     */
    public function calc_pay_time($set=[], $params = [])
    {
        $res = ["code" => false, "msg" => '', "data" => false];

        if(!SetAndNotEmpty($params, 'calc_date')){
            $res['msg'] = '计算时间不能为空！';
            return $res;
        }
        $is_date=strtotime($params['calc_date'])?strtotime($params['calc_date']):false;
        if(empty($set) || !SetAndNotEmpty($params, 'calc_field') || !SetAndNotEmpty($params, 'settlement', 'n') || $is_date===false){
            $res['msg'] = '必要的计算数据不能为空！';
            return $res;
        }
        $calc_field = $params['calc_field'];
        $calc_date = $params['calc_date'];
        $settlement = $params['settlement'];
        $source = SetAndNotEmpty($params,'source') ? $params['source'] : false;
        $purchase_number = SetAndNotEmpty($params,'purchase_number') ? $params['purchase_number'] : null;
        $demand_number = SetAndNotEmpty($params,'demand_number') ? $params['demand_number'] : null;

        // 如果是传入采购单号或者备货单号计算
        if(!empty($purchase_number) || !empty($demand_number)){
            $query = [];
            if(!empty($purchase_number))$query['purchase_number'] = $purchase_number;
            if(!empty($demand_number))$query['demand_number'] = $demand_number;
            if(!empty($query)){
                $calc_temp = $this->purchase_db->from("purchase_order_items as it")
                    ->select("o.account_type,o.pay_finish_status,o.audit_time,it.plan_arrive_time")
                    ->join("pur_purchase_order as o", "it.purchase_number=o.purchase_number", "inner")
                    ->where($query)
                    ->get()
                    ->row_array();
                if(SetAndNotEmpty($calc_temp, 'account_type'))$settlement = $calc_temp['account_type'];
                if(SetAndNotEmpty($calc_temp, 'pay_finish_status'))$pay_status = $calc_temp['pay_finish_status'];
                if($calc_field == 'audit_time' && empty($calc_date))$calc_date = $calc_temp['audit_time'];
                if($calc_field == 'plan_arrive_time' && empty($calc_date))$calc_date = $calc_temp['plan_arrive_time'];
            }
        }

        if(!$settlement){
            $res['msg'] = '未传入或未获取到对应的结算方式！';
            return $res;
        }

        $this_set = [];
        foreach ($set as &$val){
            if(SetAndNotEmpty($val, 'settlement') && $settlement == $val['settlement']){
                $this_set = $val;
                break;
            }
        }

        if(empty($this_set) || !SetAndNotEmpty($this_set, 'settlement') || !SetAndNotEmpty($this_set, 'query') || !is_array($this_set['query'])){
            $res['msg'] = '配置的计算参数无效！';
            return $res;
        }

        // 计算
        try{
            foreach ($this_set['query'] as $val){
                if(SetAndNotEmpty($val, 'source') && (!$source || $val['source'] != $source))continue;
                if(SetAndNotEmpty($val, 'calc_field') && $val['calc_field'] != $calc_field)continue; // 不属于本次计算

                // 如果是固定天数
                if(SetAndNotEmpty($val, 'fixed') && $val['fixed'] == 1){
                    if(SetAndNotEmpty($val, 'symbol')){
                        // 如果分上下月
                        $this_day = date("d", strtotime($calc_date));
                        if($val['symbol'] == "1-15" && $this_day > 15)continue;
                        if($val['symbol'] == "16-31" && $this_day <= 15)continue;

                        // 如果刚好是2月份
                        $month = date('m', strtotime ("+{$val['month']} month", strtotime($calc_date)));
                        if(in_array($val['days'], ["30", "31"]) && $month == 2)$val['days'] = "28";

                        $fixed_date = date('Y-m-', strtotime($calc_date." last day of +{$val['month']} month")).$val['days'];
                        $res['data'] = date('Y-m-d 00:00:00', strtotime($fixed_date));
                        break;
                    }else{
                        $fixed_date = date('Y-m-', strtotime($calc_date." last day of +{$val['month']} month")).$val['days'];
                        $res['data'] = date('Y-m-d 00:00:00', strtotime($fixed_date));
                        break;
                    }
                }

                // 如果不是固定天数
                if(SetAndNotEmpty($val, 'fixed') && $val['fixed'] == 2){
                    $calc_str = $val['symbol'].$val['days']." day";
                    $res['data'] = date('Y-m-d', strtotime ($calc_str, strtotime($calc_date)));
                    break;
                }
            }
        }catch (Exception $e){

        }

        if($res['data'])$res['code'] = true;
        return $res;
    }

    /**
     * 自动对账逻辑计算
     * @param array $set   配置参数，来源于 this->getSetParamData(PURCHASE_ORDER_RECORD_SET)
     * @param string $calc_date 计算起始时间
     * @param mixed $settlement 结算方式
     * @return mixed
     * 返回参数说明：
     *      code=true|false 表示计算成功|失败
     *      data为null表示随时可以请款，data为标准时间格式表示可以在该时间之后可以请款
     */
    public function calc_record($set=[], $settlement=false, $calc_date='', $calc_field='')
    {
        $res = ["code" => false, "msg" => '', "data" => false];

        if(empty($set) || !$settlement){
            $res['msg'] = '必要的计算数据不能为空！';
            return $res;
        }

        $this_set = [];
        foreach ($set as &$val){
            if(SetAndNotEmpty($val, 'settlement') && $settlement == $val['settlement']){
                $this_set = $val;
                break;
            }
        }

        if(empty($this_set) || !SetAndNotEmpty($this_set, 'settlement') || !SetAndNotEmpty($this_set, 'query') || !is_array($this_set['query'])){
            $res['msg'] = '配置的计算参数无效！';
            return $res;
        }

        // 计算
        // is_end 是否需要对账完结:1是，2否
        // funds_scheme 请款方案：1即时自动请款，2按时自动请款
        // month 跨月数
        // days  天数
        try{
            $query_len = count($this_set['query']);
            $is_date= strtotime($calc_date) ? strtotime($calc_date) : false;

            $val = [];
            if($query_len == 1){
                $val = $this_set['query'][0];
            }else if($query_len == 2){
                $this_day = date("d", strtotime($calc_date));
                if($this_day > 15)$val = $this_set['query'][1];
                if($this_day <= 15)$val = $this_set['query'][0];
            }

            if(!SetAndNotEmpty($val, 'is_end', 'n') || !in_array($val['is_end'], [1, 2]))throw new Exception('没有对应的配置信息'); // 不属于本次计算

            // 自动请款
            if(SetAndNotEmpty($val, 'funds_scheme', 'n') && $val['funds_scheme'] == 1){
                $res['data'] = null;
                throw new Exception('success');
            }

            // 不自动请款, 日期格式不对则跳出
            if(!SetAndNotEmpty($val, 'funds_scheme', 'n') || $val['funds_scheme'] != 2 || $is_date === false){
                throw new Exception('不正确的日期格式');
            }

            // 跨月
            // 如果刚好是2月份
            $month = date('m', strtotime ("+{$val['month']} month", strtotime($calc_date)));
            if(in_array($val['days'], ["30", "31"]) && $month == 2)$val['days'] = "28";

            $fixed_date = date('Y-m-', strtotime ("+{$val['month']} month", strtotime($calc_date))).$val['days'];
            $res['data'] = date('Y-m-d 00:00:00', strtotime($fixed_date));
        }catch (Exception $e){
            $res['msg'] = $e->getMessage();
        }

        if($res['data'] !== false)$res['code'] = true;

        return $res;
    }


    /**
     * 按采购单审核 计算应付款时间
     * @author Jolon
     * @param array $set 配置参数，来源于 this->getSetParamData()
     * @param string $account_type 结算方式
     * @param string $source 采购来源
     * @param string $audit_time 审核时间
     * @param string $plan_arrive_time 预计到货时间
     * @return array|mixed
     */
    public function calc_pay_time_audit_service($set,$account_type,$source,$audit_time,$plan_arrive_time){
        if(in_array($account_type,[10])){// 款到发货
            if($source == SOURCE_COMPACT_ORDER){// 合同单：预计到货时间
                if(substr($plan_arrive_time,0,7) == '0000-00') return $this->res_data(false,'预计到货时间错误');
                $params = [
                    'calc_field' => 'plan_arrive_time',
                    'source' => $source,
                    'calc_date' => $plan_arrive_time,
                    'settlement' => $account_type
                ];
            }else{// 网采单：审核时间
                $params = [
                    'calc_field' => 'audit_time',
                    'source' => $source,
                    'calc_date' => $audit_time,
                    'settlement' => $account_type
                ];
            }
        }elseif(in_array($account_type,[17,18,19,39,30,31,32,40])){// 按 审核时间 计算
            $params = [
                'calc_field' => 'audit_time',
                'calc_date' => $audit_time,
                'settlement' => $account_type
            ];
        }elseif(in_array($account_type,[1,7,8,6,9,37,38])){// 不设置 应付款时间，入库后才计算
            $params = [];
        }

        if(!isset($params) or empty($params)){
            return $this->res_data(false,'该结算方式还未配置应付款时间['.$account_type.']');
        }

        if(empty($set) or !is_array($set)){
            return $this->res_data(false,'读取因付款时间配置错误');
        }

        return $this->calc_pay_time($set,$params);
    }


    /**
     * 按部分付款 计算应付款时间
     * @author Jolon
     * @param array $set 配置参数，来源于 this->getSetParamData()
     * @param string $account_type 结算方式
     * @param string $source 采购来源
     * @param string $instock_time 入库时间
     * @param string $plan_arrive_time 预计到货时间
     * @return array|mixed
     */
    public function calc_pay_time_paid_service($set,$account_type,$source,$instock_time,$plan_arrive_time){
        if(in_array($account_type,[17,18,19,30,31,32])){// 按 审核时间 计算
            if(substr($instock_time,0,7) == '0000-00') return $this->res_data(false,'入库时间错误');
            $params = [
                'calc_field' => 'instock_time',
                'calc_date' => $instock_time,
                'settlement' => $account_type
            ];
        }elseif(in_array($account_type,[39,40])){// 不设置 应付款时间，入库后才计算
            if(substr($plan_arrive_time,0,7) == '0000-00') return $this->res_data(false,'预计到货时间错误');
            $params = [
                'calc_field' => 'plan_arrive_time',
                'calc_date' => $plan_arrive_time,
                'settlement' => $account_type
            ];
        }

        if(!isset($params) or empty($params)){
            return $this->res_data(false,'该结算方式还未配置应付款时间['.$account_type.']');
        }

        if(empty($set) or !is_array($set)){
            return $this->res_data(false,'读取因付款时间配置错误');
        }

        return $this->calc_pay_time($set,$params);
    }


    /**
     * 按入库记录 计算应付款时间
     * @author Jolon
     * @param array $set 配置参数，来源于 this->getSetParamData()
     * @param string $account_type 结算方式
     * @param string $source 采购来源
     * @param string $pay_finish_status 付款完结状态：初始值=未付。包含：1.未付、2.无需付、3.部分已付、4.已付4个数值
     * @param string $audit_time 审核时间
     * @param string $instock_time 入库时间
     * @param string $plan_arrive_time 预计到货时间
     * @return array|mixed
     */
    public function calc_pay_time_in_service($set,$account_type,$source,$pay_finish_status,$audit_time,$instock_time,$plan_arrive_time){
        if(in_array($account_type,[10])){// 款到发货
            if($source == SOURCE_COMPACT_ORDER){// 合同单：预计到货时间
                $params = [
                    'calc_field' => 'plan_arrive_time',
                    'source' => $source,
                    'calc_date' => $plan_arrive_time,
                    'settlement' => $account_type
                ];
            }else{// 网采单：审核时间
                $params = [
                    'calc_field' => 'audit_time',
                    'source' => $source,
                    'calc_date' => $audit_time,
                    'settlement' => $account_type
                ];
            }
        }elseif(in_array($account_type,[1,7,8,37,9,6,38])){
            $params = [
                'calc_field' => 'instock_time',
                'calc_date' => $instock_time,
                'settlement' => $account_type
            ];
        }elseif(in_array($account_type,[17,18,19,30,31,32])){
            if($pay_finish_status == 1){// 未付
                $params = [
                    'calc_field' => 'audit_time',
                    'calc_date' => $audit_time,
                    'settlement' => $account_type
                ];
            }elseif($pay_finish_status == 3){// 部分付款
                $params = [
                    'calc_field' => 'instock_time',
                    'calc_date' => $instock_time,
                    'settlement' => $account_type,
                ];
            }
        }elseif(in_array($account_type,[39,40])){
            if($pay_finish_status == 1){// 未付
                $params = [
                    'calc_field' => 'audit_time',
                    'calc_date' => $audit_time,
                    'settlement' => $account_type
                ];
            }elseif($pay_finish_status == 3){// 部分付款
                $params = [
                    'calc_field' => 'plan_arrive_time',
                    'calc_date' => $plan_arrive_time,
                    'settlement' => $account_type,
                ];
            }
        }

        if(!isset($params) or empty($params)){
            return $this->res_data(false,'该结算方式还未配置应付款时间['.$account_type.'-'.$pay_finish_status.']');
        }

        if(empty($set) or !is_array($set)){
            return $this->res_data(false,'读取因付款时间配置错误');
        }

        return $this->calc_pay_time($set,$params);
    }
}