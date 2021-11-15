<?php
class Purchase_setting_model extends Purchase_model
{
    protected $table_name = 'param_sets';// 数据表名称

    /**
     * 配置项key
     */
    protected $pertain_set = 'PURCHASE_ORDER_PERTAIN_SET'; // 公共仓限制修改项
    protected $cancel_set = 'PURCHASE_ORDER_CANCEL_SET'; // 自动取消配置
    protected $pay_time_set = 'PURCHASE_ORDER_PAY_TIME_SET'; // 应付款时间配置
    protected $record_set = 'PURCHASE_ORDER_RECORD_SET'; // 应付款时间配置 record_auto

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('status_order');
    }

    /**
     * 获取修改公共仓配置列表
     */
    public function pertain_set_list($id=false)
    {
        $query = [
            "pType" => $this->pertain_set,
        ];
        if($id){
            $query['id'] = $id;
        }
        $data = $this->purchase_db->from($this->table_name)->where($query)->get()->result_array();

        if(!$data || count($data) == 0)return [];
        $data_temp = [];
        foreach ($data as $val){
            $row = !empty($val['pValue']) ? json_decode($val['pValue'],  true) : [];
            $data_temp[] = [
                "id"                    => $val['id'],
                "purchase_type_id"      => $row['purchase_type_id'],
                "purchase_type_id_cn"   => getPurchaseType($row['purchase_type_id']),
                "logistics_type"        => $row['logistics_type']??"",
                "is_fumigation"         => $row['is_fumigation']??"",
                "destination_warehouse" => $row['destination_warehouse']??"",
                "pertain_wms"           => $row['pertain_wms']??"",
                "user"                  => $row['user'],
                "updateTime"            => $val['updateTime'],
                "status"                => $val['pSort'],
            ];
        }
        return $data_temp;
    }

    /**
     * 编辑/新增公共仓配置数据
     */
    public function pertain_set_edit($params=[])
    {
        $res = ["code" => 0, "msg" => "新增/修改失败"];
        if(empty($params)){
            $res['msg'] = '新增参数不能为空！';
            return $res;
        }

        try {
            $this->purchase_db->trans_begin();
            $uid = getActiveUserId();
            $user = getActiveUserName();
            foreach ($params as $val){
                $row = [];
                $pVal = [
                    "purchase_type_id"      => $val['purchase_type_id'],
                    "pertain_wms"           => $val['pertain_wms'],
                    "uid"                   => $uid,
                    "user"                  => $user,
                ];

                if(SetAndNotEmpty($val, 'destination_warehouse'))$pVal['destination_warehouse'] = $val['destination_warehouse'];
                if(SetAndNotEmpty($val, 'logistics_type'))$pVal['logistics_type'] = $val['logistics_type'];
                if(SetAndNotEmpty($val, 'is_fumigation', 'n'))$pVal['is_fumigation'] = $val['is_fumigation'];

                $row['pValue']      = json_encode($pVal);
                $row['updateTime']  = date("Y-m-d H:i:s");
                if(SetAndNotEmpty($val, "id", 'n') && $val['id'] > 0){
                    $this->purchase_db->where(['id' => $val['id'], "pType" => $this->pertain_set])->update("param_sets", $row);
                }else{
                    $row['pKey']        = "POPS".time().rand(1000, 9999);
                    $row['pType']       = $this->pertain_set;
                    $row['createTime']  = date("Y-m-d H:i:s");
                    $this->purchase_db->insert("param_sets", $row);
                }
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('提交事务失败!');
            }
            $res['code'] = 1;
            $res['msg'] = '新增成功！';
            $this->purchase_db->trans_commit();
        }catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            $res['msg'] = $exc->getMessage();
        }
        return $res;
    }

    /**
     * 获取自动取消配置列表
     */
    public function cancel_auto_list($id=false)
    {
        $query = [
            "pType" => $this->cancel_set,
        ];
        if($id){
            $query['id'] = $id;
        }
        $data = $this->purchase_db->from($this->table_name)->where($query)->get()->result_array();

        if(!$data || count($data) == 0)return [];
        $data_temp = [];
        foreach ($data as $val){
            $row = !empty($val['pValue']) ? json_decode($val['pValue'],  true) : [];
            $data_temp[] = [
                "id"                    => $val['id'],
                "source"                => $row['source'],
                "track_status"          => $row['track_status'],
                "long_delivery"         => $row['long_delivery'],
                "purchase_order_status" => $row['purchase_order_status'],
                "suggest_order_status"  => $row['suggest_order_status'],
                "plan_arrive_time"      => $row['plan_arrive_time'],
                "overdue_day"           => $row['overdue_day'],
                "user"                  => $row['user'],
                "updateTime"            => $val['updateTime'],
                "status"                => $val['pSort'],
            ];
        }
        return $data_temp;
    }

    /**
     * 编辑/新增自动取消配置
     */
    public function cancel_auto_edit($params=[])
    {
        $res = ["code" => 0, "msg" => "新增/修改失败"];
        if(empty($params)){
            $res['msg'] = '新增参数不能为空！';
            return $res;
        }

        try {
            $this->purchase_db->trans_begin();
            $uid = getActiveUserId();
            $user = getActiveUserName();
            foreach ($params as $val){
                $row = [];
                $pVal = [
                    "source"                => $val['source'],
                    "track_status"          => 0,
                    "long_delivery"         => $val['long_delivery'],
                    "purchase_order_status" => 7,
                    "suggest_order_status"  => 7,
                    "user"                  => $user,
                ];

                if(SetAndNotEmpty($val, 'plan_arrive_time'))$pVal['plan_arrive_time'] = $val['plan_arrive_time'];
                if(SetAndNotEmpty($val, 'overdue_day'))$pVal['overdue_day'] = $val['overdue_day'];

                $row['pValue']      = json_encode($pVal);
                $row['updateTime']  = date("Y-m-d H:i:s");
                if(SetAndNotEmpty($val, "id", 'n') && $val['id'] > 0){
                    $this->purchase_db->where(['id' => $val['id'], "pType" => $this->cancel_set])->update("param_sets", $row);
                }else{
                    $row['pKey']        = "POCAS".time().rand(1000, 9999);
                    $row['pType']       = $this->cancel_set;
                    $row['createTime']  = date("Y-m-d H:i:s");
                    $this->purchase_db->insert("param_sets", $row);
                }
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('提交事务失败!');
            }
            $res['code'] = 1;
            $res['msg'] = '新增成功！';
            $this->purchase_db->trans_commit();
        }catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            $res['msg'] = $exc->getMessage();
        }
        return $res;
    }

    /**
     * 应付款时间配置列表
     */
    public function need_pay_time_list($id=false)
    {
        $query = [
            "pType" => $this->pay_time_set,
        ];
        if($id){
            $query['id'] = $id;
        }
        $data = $this->purchase_db->from($this->table_name)->where($query)->get()->result_array();

        if(!$data || count($data) == 0)return [];
        $data_temp = [];
        $settlement = $this->get_settlement_list();
        $display = $this->get_display_pay_list('pay');
        foreach ($data as $val){
            $row = !empty($val['pValue']) ? json_decode($val['pValue'],  true) : [];
            $set = $row['settlement'];
            $data_temp[] = [
                "id"                    => $val['id'],
                "updateTime"            => $val['updateTime'],
                "status"                => $val['pSort'],
                "settlement"            => $set,
                "display_field"         => isset($display[$set]) ? $display[$set] : "",
                "settlement_cn"         => isset($settlement[$set]) ? $settlement[$set] : "",
                "query"                 => $row['query'],
                "user"                  => $row['user'],
            ];

            // 公共方法
        }
        return $data_temp;
    }

    /**
     * 显示的字符
     * @return array
     */
    private function get_display_pay_list($type='')
    {
        if(!in_array($type, ['pay', 'record']))return [];
        $res = [
            "pay" => [
                10 => '采购来源＝网采，应付款时间＝采购单审核时间 + %ss% 天；采购来源＝合同，应付款时间＝预计到货时间 - %ss% 天', // 款到发货
                1 => '应付款时间＝入库时间 + %ss% 天', // 货到付款
                7 => '应付款时间＝入库时间 + %ss% 天', // 周结
                39 => '未付订金时（付款完结状态＝未付），应付款时间＝审核时间 + %ss% 天；付订金后(付款完结状态＝部分已付)，应付款时间＝预计到货时间 - %ss% 天', // 10%订金+90%尾款款到发货
                19 => '未付订金时（付款完结状态＝未付），应付款时间＝审核时间 + %ss% 天；付订金后(付款完结状态＝部分已付)，应付款时间＝入库时间 + %ss% 天', // 10%订金+90%尾款货到付款
                17 => '未付订金时（付款完结状态＝未付），应付款时间＝审核时间 + %ss% 天；付订金后(付款完结状态＝部分已付)，应付款时间＝入库时间的次月 %ss% 号', // 10%订金+90%尾款半月结
                18 => '未付订金时（付款完结状态＝未付），应付款时间＝审核时间 + %ss% 天；付订金后(付款完结状态＝部分已付)，应付款时间＝入库时间的次月 %ss% 号', // 10%订金+90%尾款月结
                40 => '未付订金时（付款完结状态＝未付），应付款时间＝审核时间 + %ss% 天；付订金后(付款完结状态＝部分已付)，应付款时间＝预计到货时间 - %ss% 天', // 30%订金+70%尾款款到发货
                32 => '未付订金时（付款完结状态＝未付），应付款时间＝审核时间 + %ss% 天；付订金后(付款完结状态＝部分已付)，应付款时间＝入库时间 + %ss% 天', // 30%订金+70%尾款货到付款
                30 => '未付订金时（付款完结状态＝未付），应付款时间＝审核时间 + %ss% 天；付订金后(付款完结状态＝部分已付)，应付款时间＝入库时间的次月 %ss% 号', // 30%订金+70%尾款半月结
                31 => '未付订金时（付款完结状态＝未付），应付款时间＝审核时间 + %ss% 天；付订金后(付款完结状态＝部分已付)，应付款时间＝入库时间的次月 %ss% 号', // 30%订金+70%尾款月结
                8 => '上半月1-15号入库，应付款时间＝当月 %ss% 号；下半月16-31号入库，应付款时间＝次月 %ss% 号', // 半月结
                37 => '应付款时间＝入库时间的次月 %ss% 号', // 月结15天
                9 => '应付款时间＝入库时间的次月 %ss% 号', // 月结30天
                6 => '入库时间后第二个月的 %ss% 号，例如：3.1-3.31号入库，应付款时间＝5月30号', // 月结60天
                38 => '入库时间后第三个月的 %ss% 号，例如：3.1-3.31号入库，应付款时间＝6月30号', // 月结90天
            ],
            "record" => [
                1 => '对账单完结时，即时自动请款', // 货到付款
                7 => '对账单完结时，即时自动请款', // 周结
                19 => '对账单完结时，即时自动请款', // 10%订金+90%尾款货到付款
                32 => '对账单完结时，即时自动请款', // 30%订金+70%尾款货到付款
                17 => '对账单完结时，自动请款时间＝对账单入库月份的次月 %ss% 号', // 10％订金+90％尾款半月结
                18 => '对账单完结时，自动请款时间＝对账单入库月份的次月 %ss% 号', // 10％订金+90％尾款月结
                30 => '对账单完结时，自动请款时间＝对账单入库月份的次月 %ss% 号', // 30％订金+70％尾款半月结
                31 => '对账单完结时，自动请款时间＝对账单入库月份的次月 %ss% 号', // 30％订金+70％尾款月结
                37 => '对账单完结时，自动请款时间＝对账单入库月份的次月 %ss% 号', // 月结15天
                9 => '对账单完结时，自动请款时间＝对账单入库月份的次月 %ss% 号', // 月结30天
                8 => '对账单完结时，上半月1-15号入库，自动请款时间＝对账单入库月份的 %ss% 号；对账单完结时，下半月16-31号入库，自动请款时间＝对账单入库月份的次月 %ss% 号', // 半月结
                6 => '对账单完结时，自动请款时间＝对账单入库月份的后第二个月的 %ss% 号，例如：3.1-3.31号入库，请款时间＝5月18号', // 月结60天
                38 => '对账单完结时，自动请款时间＝对账单入库月份的后第三个月的 %ss% 号，例如：3.1-3.31号入库，请款时间＝6月18号', // 月结90天
            ]
        ];
        return $res[$type];
    }

    /**
     * 编辑/新增应付款时间配置
     */
    public function need_pay_time_edit($params=[])
    {
        $res = ["code" => 0, "msg" => "新增/修改失败"];
        if(empty($params)){
            $res['msg'] = '新增参数不能为空！';
            return $res;
        }

        try {
            $this->purchase_db->trans_begin();
            $uid = getActiveUserId();
            $user = getActiveUserName();
            foreach ($params as $val){
                $row = [];
                $pVal = [
                    "settlement"            => $val['settlement'], // 结算方式，
                    "user"                  => $user,
                ];

                $pVal['query'] = $this->assembleSettlementQuery($val['query']);

                $row['pValue']      = json_encode($pVal);
                $row['updateTime']  = date("Y-m-d H:i:s");
                if(SetAndNotEmpty($val, "id", 'n') && $val['id'] > 0){
                    $this->purchase_db->where(['id' => $val['id'], "pType" => $this->pay_time_set])->update("param_sets", $row);
                }else{
                    $row['pKey']        = "PURCHASE_ORDER_PAY_TIME_SET_".$val['settlement'];
                    $row['pType']       = $this->pay_time_set;
                    $row['createTime']  = date("Y-m-d H:i:s");
                    $this->purchase_db->insert("param_sets", $row);
                }
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('提交事务失败!');
            }
            $res['code'] = 1;
            $res['msg'] = '新增成功！';
            $this->purchase_db->trans_commit();
        }catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            $res['msg'] = $exc->getMessage();
        }
        return $res;
    }

    /**
     * 获取所有的结算方式
     */
    public function get_settlement_list()
    {
        $settlement_data =  $this->purchase_db->from("pur_supplier_settlement")
            ->select("settlement_name,settlement_code")
            //->where("parent_id !=", 0)
            ->get()
            ->result_array();
        $settlement = [];
        foreach ($settlement_data as $val){
            $settlement[$val['settlement_code']] = $val['settlement_name'];
        }
        return $settlement;
    }

    /**
     * 组装应付款时间条件
     */
    private function assembleSettlementQuery($query=[])
    {
        $res = [];
        foreach ($query as &$val){
            $row = [];

            if(SetAndNotEmpty($val, 'source'))$row['source'] = $val['source']; // 采购来源
            if(SetAndNotEmpty($val, 'pay_status'))$row['pay_status'] = $val['pay_status']; // 付款完结状态
            if(SetAndNotEmpty($val, 'deposit'))$row['deposit'] = $val['deposit']; // 是否支付了订金

            $calc_field = ["audit_time", "plan_arrive_time", "instock_time"];
            if(SetAndNotEmpty($val, 'calc_field') && in_array($val['calc_field'], $calc_field))$row['calc_field'] = $val['calc_field']; // 计算字段
            if(SetAndNotEmpty($val, 'symbol'))$row['symbol'] = $val['symbol']; // 计算符号
            if(SetAndNotEmpty($val, 'days', 'n'))$row['days'] = $val['days']; // 天数
            if(SetAndNotEmpty($val, 'fixed'))$row['fixed'] = $val['fixed']; // 是否为固定天数
            if(SetAndNotEmpty($val, 'month', 'n') && $val['month'] > 0)$row['month'] = $val['month']; // 跨月数：默认0

            if(count($row) > 0)$res[] = $row;
        }
        return $res;
    }

    /**
     * 自动对账配置
     */
    public function record_auto_list($id=false)
    {
        $query = [
            "pType" => $this->record_set,
        ];
        if($id){
            $query['id'] = $id;
        }
        $data = $this->purchase_db->from($this->table_name)->where($query)->get()->result_array();

        if(!$data || count($data) == 0)return [];
        $data_temp = [];
        $settlement = $this->get_settlement_list();
        $display = $this->get_display_pay_list('record');
        foreach ($data as $val){
            $row = !empty($val['pValue']) ? json_decode($val['pValue'],  true) : [];
            $st = $row['settlement'];
            $data_temp[] = [
                "id"                    => $val['id'],
                "updateTime"            => $val['updateTime'],
                "status"                => $val['pSort'],
                "settlement"            => $st,
                "display_field"         => isset($display[$st]) ? $display[$st] : "",
                "settlement_cn"         => isset($settlement[$st]) ? $settlement[$st] : "",
                "query"                 => $row['query'],
                "user"                  => $row['user'],
            ];

            // 公共方法
        }
        return $data_temp;
    }

    /**
     * 自动对账配置
     */
    public function record_auto_edit($params=[])
    {
        $res = ["code" => 0, "msg" => "新增/修改失败"];
        if(empty($params)){
            $res['msg'] = '新增参数不能为空！';
            return $res;
        }

        try {
            $this->purchase_db->trans_begin();
            $uid = getActiveUserId();
            $user = getActiveUserName();
            foreach ($params as $val){
                $row = [];
                $pVal = [
                    "settlement"            => $val['settlement'], // 结算方式，
                    "user"                  => $user,
                ];

                $pVal['query'] = $this->assembleRecordQuery($val['query']);

                $row['pValue']      = json_encode($pVal);
                $row['updateTime']  = date("Y-m-d H:i:s");
                if(SetAndNotEmpty($val, "id", 'n') && $val['id'] > 0){
                    $this->purchase_db->where(['id' => $val['id'], "pType" => $this->record_set])->update("param_sets", $row);
                }else{
                    $row['pKey']        = "PURCHASE_ORDER_RECORD_SET_".$val['settlement'];
                    $row['pType']       = $this->record_set;
                    $row['createTime']  = date("Y-m-d H:i:s");
                    $this->purchase_db->insert("param_sets", $row);
                }
            }
            if ($this->purchase_db->trans_status() === false) {
                throw new Exception('提交事务失败!');
            }
            $res['code'] = 1;
            $res['msg'] = '新增成功！';
            $this->purchase_db->trans_commit();
        }catch (Exception $exc) {
            $this->purchase_db->trans_rollback();
            $res['msg'] = $exc->getMessage();
        }
        return $res;
    }

    /**
     * 组装自动对账条件
     */
    private function assembleRecordQuery($query=[])
    {
        $res = [];
        foreach ($query as &$val){
            $row = [];

            if(SetAndNotEmpty($val, 'is_end'))$row['is_end'] = $val['is_end']; // 是否需要对账完结:1是，2否
            if(SetAndNotEmpty($val, 'funds_scheme'))$row['funds_scheme'] = $val['funds_scheme']; // 请款方案：1即时自动请款，2按时自动请款
            if(SetAndNotEmpty($val, 'month', 'n') && $val['month'] > 0)$row['month'] = $val['month']; // 跨月数：默认0
            if(SetAndNotEmpty($val, 'days', 'n'))$row['days'] = $val['days']; // 固定请款日期

            if(count($row) > 0)$res[] = $row;
        }
        return $res;
    }
}