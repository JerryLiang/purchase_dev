<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2019/12/3
 * Time: 11:17
 */

class Logistics_info_model extends Purchase_model
{
    //快递公司信息表
    protected $logistics_carrier_table_name = 'logistics_carrier';
    //采购单物流信息表
    protected $logistics_info_table_name = 'purchase_logistics_info';
    //异常采购单退货记录表
    protected $exception_return_info_table = 'excep_return_info';
    //采购单主表
    protected $purchase_order_table_name = 'purchase_order';
    //仓库地址表
    protected $warehouse_address_table_name = 'warehouse_address';
    //订单跟踪信息表
    protected $purchase_progress_table_name = 'purchase_progress';
    //采购单信息确认-请款金额相关信息表
    protected $table_purchase_order_pay_type = 'purchase_order_pay_type';
    //物流轨迹详情表
    protected $table_logistics_track_detail = 'purchase_logistics_track_detail';
    //多货退货表
    protected $multiple_return_info_table = 'multiple_return_goods';




    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Purchase_order_model');
        $this->load->model('warehouse/Warehouse_model');
    }

    /**
     * 获取未订阅的（非1688单）快递单信息(从物流信息表获取)
     * @param array $express_no
     * @param int $limit
     * @return array
     */
    public function get_express_info($express_no = array(), $limit = 100)
    {
        $query = $this->purchase_db;
        $query->select('a.id,a.cargo_company_id AS carrier_name,a.carrier_code,a.express_no,a.purchase_number,c.warehouse_code,"" AS customer_name');
        $query->from("{$this->logistics_info_table_name} AS a");
        $query->join("{$this->table_purchase_order_pay_type} AS b", 'a.purchase_number=b.purchase_number');
        $query->join("{$this->purchase_order_table_name} AS c", 'a.purchase_number=c.purchase_number');
        $query->where('a.is_push', 0);
        $query->where_not_in('a.carrier_code', ['','other', 'JD']);//暂时不处理京东快递
        $query->group_start();
        $query->or_where("c.source", 1);//合同单
        $query->or_where_in('b.purchase_acccount', ['琦LL114', '琦LL115', '琦LL113', '琦LL213', '琦LL214', '琦LL217', '']);//非1688单
        $query->group_end();

        if (!empty($express_no)) {
            $query->where_in('a.express_no', $express_no);
        } else {
            $query->where('a.express_no <>', '');
            $query->limit($limit);
        }
        $query->group_by('a.express_no');
        $result = $query->get()->result_array();
        foreach ($result as &$item) {
            //顺丰快递时需要获取收件人手机号后四位
            if ($item['carrier_code'] == 'SF') {
                $contact_number = $this->_get_contact_number($item['warehouse_code'], $item['purchase_number']);
                if (empty($contact_number)) {
                    unset($item);//未获取到手机号，从结果中删掉顺丰快递单数据
                    continue;
                }
                $item['customer_name'] = $contact_number;
            }
        }
        return $result;
    }

    /**
     * 获取订阅的快递单信息(从异常采购单退货记录表获取)
     * @param array $express_no
     * @param int $limit
     * @return array
     */
    public function get_exception_express_info($express_no = array(), $limit = 100)
    {
        $query = $this->purchase_db;
        $query->select('id,express_company AS carrier_name,carrier_code,express_no,"" AS customer_name');
        $query->where('is_push', 0);
        $query->where_not_in('carrier_code', ['','other','SF']);//暂时不订阅顺丰快递（系统没有存收件人或寄件人的手机号）
        if (!empty($express_no)) {
            $query->where_in('express_no', $express_no);
        } else {
            $query->where('express_no <>', '');
            $query->limit($limit);
        }
        $query->group_by('express_no');
        $result = $query->get($this->exception_return_info_table)->result_array();
        return $result;
    }

    /**
     * 根据指定条件，更新物流信息表或异常采购单退货记录表数据
     * @param int $order_type
     * @param array $where
     * @param array $update_data
     * @return mixed
     */
    public function update_express_order($order_type, $where, $update_data)
    {
        if (1 == $order_type) {
            $table_name = $this->logistics_info_table_name;
        }elseif(3 == $order_type){
            $table_name = $this->multiple_return_info_table;

        } else {
            $table_name = $this->exception_return_info_table;
        }
        $query = $this->purchase_db;
        $query->where($where);
        $query->update($table_name, $update_data);
        return $query->affected_rows();
    }

    /**
     * 根据快递公司编码，获取匹配规则，并缓存Redis（编辑匹配规则时会清空缓存）
     * @param $code
     * @return array|bool
     */
    private function _get_rule_by_code($code)
    {
        if (empty($code)) return false;

        $result = $this->rediss->getData('RULE_INFO_' . strtoupper($code));
        if (empty($result) OR !is_array($result)) {
            $query = $this->purchase_db;
            $query->select('rule');
            $query->where('status', 1);
            $query->where('carrier_code', $code);
            $result = $query->get($this->logistics_carrier_table_name)->row_array();
            //缓存规则
            $this->rediss->setData('RULE_INFO_' . strtoupper($code), $result);
        }

        return $result;
    }

    /**
     * 根据快递单号，获取仓库地址，并缓存Redis
     * @param $express_no
     * @return array|bool
     */
    private function _get_warehouse_code($express_no)
    {
        if (empty($express_no)) return false;

        $result = $this->rediss->getData('EXPRESS_INFO_' . $express_no);
        if (empty($result) OR !is_array($result)) {
            $query = $this->purchase_db;
            $query->select('b.express_no,a.warehouse_code');
            $query->from("{$this->purchase_order_table_name} a");
            $query->join("{$this->logistics_info_table_name} b", 'b.purchase_number=a.purchase_number');
            $query->where('b.express_no', $express_no);
            $query->group_by('a.purchase_number');
            $result = $query->get()->row_array();
            //缓存快递单号对应仓库编码
            $this->rediss->setData('EXPRESS_INFO_' . $express_no, $result);
        }

        return $result;
    }

    /**
     * 获取所有仓库地址，并缓存Redis
     * @return array
     */
    public function get_warehouse_address()
    {
        $result = $this->rediss->getData('WAREHOUSE_ADDRESS');
        if (empty($result) OR !is_array($result)) {
            $query = $this->purchase_db;
            $query->select('warehouse_code,province_text,city_text,area_text,town_text,address');
            $query->from($this->warehouse_address_table_name);
            $result_tmp = $query->get()->result_array();
            $result = array();
            foreach ($result_tmp as $item) {
                $result[$item['warehouse_code']] = $item;
            }
            unset($result_tmp);

            //缓存仓库地址信息
            $this->rediss->setData('WAREHOUSE_ADDRESS', $result);
        }

        return $result;
    }

    /**
     * 解析轨迹详情，匹配轨迹状态
     * @param array|int $warehouse_address 仓库地址数据|或者等于2
     * @param string $carrier_code 快递公司编码
     * @param string $express_no 快递单号
     * @param string $content 要解析的轨迹信息
     * @return bool|int
     */
    public function resolve_logistics_tracks($warehouse_address, $carrier_code, $express_no, $content)
    {
        //1.根据快递公司编码，获取匹配规则
        $rule_data = $this->_get_rule_by_code($carrier_code);

        if (!$rule_data OR !isset($rule_data['rule'])) return false;

        $rule_data = json_decode($rule_data['rule'], true);
        if (!is_array($rule_data) OR !isset($rule_data['collect']) OR !isset($rule_data['shipped']) OR !isset($rule_data['pick_up_point'])
            OR !isset($rule_data['pick_up_point']['address']) OR !isset($rule_data['pick_up_point']['relation']) OR !isset($rule_data['pick_up_point']['keyword'])
            OR !isset($rule_data['deliver']) OR !isset($rule_data['received'])) {
            return false;
        }

        $status = 0;
        foreach ($rule_data as $key => $item) {
            switch ($key) {
                case 'received' :
                    //匹配“已签收”
                    $flag = $this->_mapping($item, $content);
                    if ($flag) $status = 5;
                    break;
                case 'deliver' :
                    //匹配“派件中”
                    $flag = $this->_mapping($item, $content);
                    if ($flag) $status = 4;
                    break;
                case 'pick_up_point' :
                    //为退货快递单时(用$warehouse_address等于2做判断)，不需要匹配‘已到提货点’
                    if (2 == $warehouse_address) break;
                    //匹配“已到提货点”
                    //2.匹配规则中若设置‘地址’匹配规则，则获取匹配规则中对应的仓库地址进行匹配
                    if (!empty($item['address']) && !empty($item['keyword'])) {
                        $express_info = $this->_get_warehouse_code($express_no);//根据快递单号获取仓库编码
                        if (!$express_info OR !isset($express_info['warehouse_code']) OR !isset($warehouse_address[$express_info['warehouse_code']])) break;
                        //对应仓库的实际地址
                        $province_text = isset($warehouse_address[$express_info['warehouse_code']]['province_text']) ? $warehouse_address[$express_info['warehouse_code']]['province_text'] : '';//省
                        $city_text = isset($warehouse_address[$express_info['warehouse_code']]['city_text']) ? $warehouse_address[$express_info['warehouse_code']]['city_text'] : '';//市
                        $area_text = isset($warehouse_address[$express_info['warehouse_code']]['area_text']) ? $warehouse_address[$express_info['warehouse_code']]['area_text'] : '';//区
                        $town_text = isset($warehouse_address[$express_info['warehouse_code']]['town_text']) ? $warehouse_address[$express_info['warehouse_code']]['town_text'] : '';//镇
                        //根据规则转换对应地址数据
                        $address = array();
                        foreach (explode(',', $item['address']) as $keyword_flag) {
                            switch ($keyword_flag) {
                                case FLAG_LESS_PROVINCE:
                                    array_push($address, str_replace('省', '', $province_text));//关键字标识“省（不含“省”字）”
                                    break;
                                case FLAG_LESS_CITY:
                                    array_push($address, str_replace('市', '', $city_text));//关键字标识“省（不含“市”字）”
                                    break;
                                case FLAG_LESS_AREA:
                                    array_push($address, str_replace('区', '', $area_text));//关键字标识“省（不含“区”字）”
                                    break;
                                case FLAG_LESS_TOWN:
                                    array_push($address, str_replace('镇', '', $town_text));//关键字标识“省（不含“镇”字）”
                                    break;
                                case FLAG_PROVINCE:
                                    array_push($address, $province_text);//关键字标识“省”
                                    break;
                                case FLAG_CITY:
                                    array_push($address, $city_text);//关键字标识“市”
                                    break;
                                case FLAG_AREA:
                                    array_push($address, $area_text);//关键字标识“区”
                                    break;
                                case FLAG_TOWN:
                                    array_push($address, $town_text);//关键字标识“镇”
                                    break;

                            }
                        }
                        $address = implode(',', array_filter($address));
                        //匹配状态
                        $flag1 = $this->_mapping($address, $content);
                        $flag2 = $this->_mapping($item['keyword'], $content);
                        //提货点地址与关键词的满足关系
                        if ('AND' == strtoupper($item['relation'])) {
                            if ($flag1 && $flag2) $status = 3;
                        } else {
                            if ($flag1 OR $flag2) $status = 3;
                        }
                    } elseif (!empty($item['address']) && empty($item['keyword'])) {
                        $flag = $this->_mapping($item['address'], $content);
                        if ($flag) $status = 3;
                    } elseif (empty($item['address']) && !empty($item['keyword'])) {
                        $flag = $this->_mapping($item['keyword'], $content);
                        if ($flag) $status = 3;
                    }
                    break;
                case 'shipped' :
                    //匹配“已发货”
                    $flag = $this->_mapping($item, $content);
                    if ($flag) $status = 2;
                    break;
                case 'collect' :
                    //匹配“已揽收”
                    $flag = $this->_mapping($item, $content);
                    if ($flag) $status = 1;
                    break;
            }
        }
        return $status;
    }

    /**
     * 匹配关键词是否存在，存在返回true，否则返回false
     * @param $keywords
     * @param $content
     * @return bool
     */
    private function _mapping($keywords, $content)
    {
        $flag = false;
        foreach (explode(',', $keywords) as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $flag = true;
                break;
            }
        }
        return $flag;
    }

    /**
     * 获取未完成签收的快递单信息(1688单)
     * @param int $limit 每页条数
     * @param int $page 页码
     * @param int $hour 间隔小时数（正整数）
     * @param int $type 1-查询除已签收外的数据，2-查询已发货状态的数据，用于匹配‘已到提货点’状态（默认2）
     * @return array
     * @author Justin
     */
    public function get_purchase_order_info($limit = 200, $page = 1, $hour = 5, $type = 2)
    {
        $page = $page ? $page : 1;
        $limit = $limit ? $limit : 200;
        //起始位置处理
        if ($page > 1) {
            $offset = $limit * ($page - 1);
        } else {
            $offset = 0;
        }
        //时间间隔
        $hour = (is_numeric($hour) && $hour > 0) ? intval($hour) : 5;

        //结果数据
        $result = array();

        $query = $this->purchase_db;
        $query->select('a.express_no,a.status,a.carrier_code,b.pai_number');
        $query->from("{$this->logistics_info_table_name} AS a");
        $query->join("{$this->table_purchase_order_pay_type} AS b", 'a.purchase_number=b.purchase_number');
        $query->join("{$this->purchase_order_table_name} AS c", 'a.purchase_number=c.purchase_number');
        if (2 == $type) {
            $query->where('a.status', SHIPPED_STATUS, false);
        } elseif (3 == $type) {
            $query->where('a.status', 0, false);//處理歷史數據
        } else {
            $query->where('a.status <', RECEIVED_STATUS, false);
        }
        $query->where('a.carrier_code <>','');
        $query->group_start();
        $query->where('a.update_time', '0000-00-00 00:00:00');
        $query->or_where("a.update_time <= DATE_SUB(NOW(),INTERVAL {$hour} HOUR) ");//HOUR MINUTE unit
        $query->group_end();
        $query->where("c.source", 2);//网采单
        $query->where_not_in('b.purchase_acccount', ['琦LL114', '琦LL115', '琦LL113', '琦LL213', '琦LL214', '琦LL217', '']);//排除非1688账号
        $query->group_by('a.express_no');

        $query->limit($limit, $offset);
        $result_tmp = $query->get()->result_array();

        foreach ($result_tmp as $key => $item) {
            if (empty($item['express_no']) OR empty($item['pai_number'])) {
                continue;
            }
            $result['data_list'][$item['express_no']] = $item;
        }
        return $result;
    }

    /**
     * 根据快递单号获取拍单号
     * @param array $express_no
     * @return array
     * @author Justin
     */
    public function get_pai_number_info($express_no = array())
    {
        //结果数据
        $result = array();
        $query = $this->purchase_db;
        $query->select('a.express_no,a.status,a.carrier_code,b.pai_number,b.purchase_acccount');
        $query->from("{$this->logistics_info_table_name} AS a");
        $query->join("{$this->table_purchase_order_pay_type} AS b", 'a.purchase_number=b.purchase_number');
        $query->join("{$this->purchase_order_table_name} AS c", 'a.purchase_number=c.purchase_number');
        $query->where('a.status <', RECEIVED_STATUS, false);
        $query->where("c.source", 2);//网采单
        $query->where_in('a.express_no', $express_no);
        $query->where_not_in('b.purchase_acccount', ['琦LL114', '琦LL115', '琦LL113', '琦LL213', '琦LL214', '琦LL217', '']);
        $query->group_by('a.express_no');
        $result_tmp = $query->get()->result_array();

        foreach ($result_tmp as $key => $item) {
            if (empty($item['express_no']) OR empty($item['pai_number'])) {
                continue;
            }
            $result[$item['express_no']] = $item;
        }
        return $result;
    }

    /**
     * 获取未完成签收的快递单信息(非1688单)
     * @param int $limit 每页条数
     * @param int $hour 间隔小时数（正整数）
     * @return array
     * @author Justin
     */
    public function get_not_1688_order($limit = 100, $hour = 2)
    {
        $limit = $limit ? $limit : 100;

        //时间间隔
        $hour = (is_numeric($hour) && $hour > 0) ? intval($hour) : 2;

        $query = $this->purchase_db;
        $query->select('a.express_no,a.status,a.carrier_code,a.purchase_number,c.warehouse_code,"" AS customer_name');
        $query->from("{$this->logistics_info_table_name} AS a");
        $query->join("{$this->table_purchase_order_pay_type} AS b", 'a.purchase_number=b.purchase_number');
        $query->join("{$this->purchase_order_table_name} AS c", 'a.purchase_number=c.purchase_number');
        $query->where('a.status <', RECEIVED_STATUS);
        $query->where('a.is_push', 1);
        $query->where_not_in('a.carrier_code', ['','other','JD']);//暂时不处理京东快递
        $query->where("a.update_time <= DATE_SUB(NOW(),INTERVAL {$hour} HOUR) ");//HOUR MINUTE unit
        $query->group_start();
        $query->or_where("c.source", 1);//合同单
        $query->or_where_in('b.purchase_acccount', ['琦LL114', '琦LL115', '琦LL113', '琦LL213', '琦LL214', '琦LL217', '']);//非1688单
        $query->group_end();
        $query->group_by('a.express_no');
        $query->limit($limit);
        $result = $query->get()->result_array();

        foreach ($result as &$item) {
            //顺丰快递时需要获取收件人手机号后四位
            if ($item['carrier_code'] == 'SF') {
                $contact_number = $this->_get_contact_number($item['warehouse_code'], $item['purchase_number']);

                if (empty($contact_number)) {
                    unset($item);//未获取到手机号，从结果中删掉顺丰快递单数据
                    continue;
                }
                $item['customer_name'] = $contact_number;
            }
        }
        return $result;
    }

    /**
     * 获取未完成签收的快递单信息(从异常采购单退货记录表获取)
     * @param array $express_no
     * @param int $limit
     * @return array
     */
    public function get_exception_order($express_no = array(), $limit = 100)
    {
        $query = $this->purchase_db;
        $query->select('id,express_company AS carrier_name,carrier_code,express_no,status,"" AS customer_name');
        $query->where('is_push', 1);
        $query->where('status <', RECEIVED_STATUS);
        $query->where_not_in('carrier_code', ['','other','SF']);//暂时不订阅顺丰快递（系统没有存收件人或寄件人的手机号）
        if (!empty($express_no)) {
            $query->where_in('express_no', $express_no);
        } else {
            $query->where('express_no <>', '');
            $query->limit($limit);
        }
        $query->group_by('express_no');
        $result = $query->get($this->exception_return_info_table)->result_array();
        return $result;
    }

    /**
     * 保存轨迹详情
     * @param $express_no
     * @param $carrier_code
     * @param $detail
     */
    public function save_track_detail($express_no, $carrier_code, $detail)
    {
        $update_time = date('Y-m-d H:i:s');
        $sql = "INSERT INTO pur_purchase_logistics_track_detail (express_no,carrier_code,track_detail,update_time) VALUES ('{$express_no}','{$carrier_code}','{$detail}','{$update_time}')";
        $sql .= " ON DUPLICATE KEY UPDATE track_detail='{$detail}',update_time='{$update_time}'";
        $this->db->query($sql);
    }

    /**
     * 根据采购单号判断是否为1688单，并返回拍单号
     * @param $purchase_number
     * @return array
     */
    public function is_1688_order($purchase_number)
    {
        $query = $this->purchase_db;
        $query->select('b.pai_number');
        $query->from("{$this->purchase_order_table_name} AS a");
        $query->join("{$this->table_purchase_order_pay_type} AS b", 'a.purchase_number=b.purchase_number');
        $query->where("a.source", 2);//网采单
        $query->where_not_in('b.purchase_acccount', ['琦LL114', '琦LL115', '琦LL113', '琦LL213', '琦LL214', '琦LL217', '']);//排除非1688账号
        $query->where("a.purchase_number", $purchase_number);
        return $query->get()->row_array();
    }

    /**
     * 获取收件人手机号后4位
     * @param $warehouse_code
     * @param string $purchase_number 退货单不用传采购单号
     * @return bool|string
     */
    private function _get_contact_number($warehouse_code, $purchase_number='')
    {
        //先获取仓库联系人手机号
        $warehouse_address = $this->warehouse_model->get_warehouse_address($warehouse_code);
        if (!empty($warehouse_address)) {
            $phone = isset($warehouse_address['contact_number']) ? $warehouse_address['contact_number'] : '';
        }
        //若没有获取到，再获取采购员手机号
        if (empty($phone) && $purchase_number) {
            $userInfo = $this->purchase_order_model->get_access_purchaser_information($purchase_number);
            $phone = isset($userInfo['iphone']) ? $userInfo['iphone'] : '';
        }
        return !empty($phone) ? substr($phone, -4, 4) : '';
    }

    /**
     * 根据快递单号获取，收件人手机号后4位（不适用退货单）
     * @param $express_no
     * @return bool|string
     */
    private function _get_phone_number($express_no)
    {
        $query = $this->purchase_db;

        //获取仓库编码和采购单号
        $res = $query->select('a.purchase_number,b.warehouse_code')
            ->from("{$this->logistics_info_table_name} AS a")
            ->join("{$this->purchase_order_table_name} AS b",'a.purchase_number=b.purchase_number')
            ->where('a.express_no',$express_no)->get()->row_array();

        if(empty($res['purchase_number']) && empty($res['warehouse_code'])){
            return '';
        }

        return $this->_get_contact_number($res['warehouse_code'],$res['purchase_number']);
    }

    /**
     * 通过快递鸟即时查询接口获取轨迹详情
     * @param $express_no
     * @param $carrier_code
     * @param $customer_name
     * @return mixed
     */
    public function get_track_by_kdbird($express_no,$carrier_code,$customer_name=''){
        if(empty($express_no) OR empty($carrier_code)) return false;
        //请求URL
        $request_url = getConfigItemByName('api_config', 'kd_bird', 'get_order_traces');
        if (empty($request_url)) return false;

//        $requestData = "{'CustomerName':'{$customer_name}','ShipperCode':'{$carrier_code}','LogisticCode':'{$express_no}'}";
        $requestData = array(
            'CustomerName' => $customer_name,
            'ShipperCode' => $carrier_code,
            'LogisticCode' => trim($express_no)
        );
        $requestData = json_encode($requestData);

        $post_data = array(
            'EBusinessID' => EBusinessID,
            'RequestType' => '8001',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $post_data['DataSign'] = $this->_encrypt($requestData, AppKey);

        return $this->_sendPost($request_url, $post_data);
    }

    /**
     * 已签收的订单从表里获取数据
     * 未签收的订单从接口获取数据
     * @param $express_no
     * @param int $order_type|（使用orderType字段区分两个表的快递单数据，1-物流信息表，2-异常采购单退货记录表）
     * @return array|bool|mixed
     */
    public function query_track($express_no, $order_type)
    {
        $query = $this->purchase_db;

        if(1==$order_type){
            $table_name = $this->logistics_info_table_name;
        }elseif(2==$order_type){
            $table_name = $this->exception_return_info_table;
        }elseif(3 == $order_type){
            $table_name = $this->multiple_return_info_table;


        }else{
            return false;
        }



        $res = $query->select('carrier_code,status')->from($table_name)
            ->where('express_no',$express_no)->get()->row_array();

        if($res['carrier_code'] == 'SF'){
            //根据快递单号获取仓库手机号后4位或采购员手机号后4位
            if(1==$order_type) {
                $customer_name = $this->_get_phone_number($express_no);
            }else{
                return false;//除了物流信息表的数据查询SF快递,其他的暂时不查（没有地方获取手机号数据）
            }
        }elseif($res['carrier_code'] == 'JD' OR $res['carrier_code'] == 'other'){
            //京东快递直接不查询（暂时没有京东商家编码，无法查询）
            return false;
        }else{
            $customer_name = '';
        }

        if($res['status'] == RECEIVED_STATUS){
            $data = $query->select('track_detail')->from($this->table_logistics_track_detail)
                ->where('express_no',$express_no)->where('carrier_code',$res['carrier_code'])->get()->row_array();
            $result = $data['track_detail'];
        }else{
            $result= $this->get_track_by_kdbird($express_no,$res['carrier_code'],$customer_name);
        }
        return $result;
    }

    /**
     * 快递鸟接口post提交数据
     * @param string $url 请求Url
     * @param array $datas 提交的数据
     * @return string url响应返回的html
     */
    private function _sendPost($url, $datas)
    {
        $temps = array();
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = "POST " . $url_info['path'] . " HTTP/1.0\r\n";
        $httpheader .= "Host:" . $url_info['host'] . "\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= "Content-Length:" . strlen($post_data) . "\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = "";
        $headerFlag = true;
        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * 电商Sign签名生成
     * @param $data |内容
     * @param $app_key |Appkey
     * @return string DataSign签名
     */
    private function _encrypt($data, $app_key)
    {
        return urlencode(base64_encode(md5($data . $app_key)));
    }

    /**
     * 通过快递鸟订阅轨迹跟踪
     * @param $express_no
     * @param $carrier_code
     * @param $order_type
     * @param string $customer_name
     * @return bool
     */
    public function order_traces_subscribe($express_no, $carrier_code, $order_type, $customer_name = '')
    {
        if (empty($express_no) OR empty($carrier_code) OR empty($order_type)) return false;

        //请求URL
        $request_url = getConfigItemByName('api_config', 'kd_bird', 'traces_subscribe');
        if (empty($request_url)) return false;

        $requestData = array(
            'CustomerName' => $customer_name,
            'ShipperCode' => $carrier_code,
            'LogisticCode' => trim($express_no),
            'Callback' => $order_type
        );
        $requestData = json_encode($requestData);

        $post_data = array(
            'EBusinessID' => EBusinessID,
            'RequestType' => '8008',
            'RequestData' => urlencode($requestData),
            'DataType' => '2',
        );
        $post_data['DataSign'] = $this->_encrypt($requestData, AppKey);
        $_result = $this->_sendPost($request_url, $post_data);
        return json_decode($_result, true);
    }

    /**
     * 快递鸟返回状态，和我们系统的状态进行转换
     * @param int $StateEx 快递鸟返回轨迹状态码
     * @param int $OrderType 区分两个表的快递单数据，1-物流信息表，2-异常采购单退货记录表
     * @return int
     */
    public function switch_status($StateEx, $OrderType = 1)
    {
        switch ($StateEx) {
            case 1:
                $status = 1;//1-已揽收->1-已揽件
                break;
            case 2:
                $status = 2;//2-在途中->2-已发货
                break;
            case 201:
                $status = 3;//201-到达派件城市->3-已到提货点
                break;
            case 202:
            case 203:
                $status = 4;//202-派件中，203-已放入快递柜或驿站->4-派送中
                break;
            case 3:
            case 301:
            case 302:
            case 304:
            case 311:
                $status = 5;//3-已签收，301-正常签收，302-派件异常后最终签收，304-代收签收已签收，311-快递柜或驿站签收->5-已签收
                break;
            case 4:
            case 401:
            case 402:
            case 403:
            case 404:
            case 405:
            case 406:
                $status = 6;//4-问题件,401-发货无信息,402-超时未签收,403-超时未更新,404-拒收(退件),405-派件异常,406-退货签收,407-退货未签收->6-问题件
                break;
            default:
                $status = 0;
                break;
        }

        //退货发货的没有‘已到提货点’状态
        if ($OrderType == 2 && $status == 3) {
            $status = 4;
        }
        return $status;
    }

    /**
     * 获取推送到新wms的快递单信息
     * @param int $limit
     * @return array
     */
    public function get_push_express_info($limit = 200, $purchase_number= [])
    {
        $date = date('Y-m-d H:i:s',strtotime('-7 days'));
        $query = $this->purchase_db->from($this->logistics_info_table_name)
            ->select('id,purchase_number,carrier_code,express_no')
            ->limit($limit)
            ->where('is_push_wms=', 0)
            ->where('express_no <>', '')
            ->group_by('express_no,purchase_number')
            ->order_by("id desc");

        if(is_array($purchase_number) && count($purchase_number) >0){
            $query->where_in('purchase_number', $purchase_number);
        }else{
            $query->where("create_time >= ", $date);
        }

        $data = $query->get()->result_array();
        return $data;
    }

    /**
     * 获取物流公司
     */
    public function get_ship_company()
    {
        $data = $this->purchase_db->from('logistics_carrier')
            ->select("carrier_code as code,carrier_name as name")
            ->get()->result_array();
        $data_temp = [];
        foreach ($data as $val){
            $data_temp[$val['code']] = $val['name'];
        }
        return $data_temp;
    }


    /**
     * 获取订阅的快递单信息(从多货退货记录表获取)
     * @param array $express_no
     * @param int $limit
     * @return array
     */
    public function get_multiple_return_express_info($express_no = array(), $limit = 100)
    {
        $query = $this->purchase_db;
        $query->select('id, carrier_name,carrier_code, express_no,"" AS customer_name');
        $query->where('is_push', 0);
        $query->where_not_in('carrier_code', ['','other','SF']);//暂时不订阅顺丰快递（系统没有存收件人或寄件人的手机号）
        if (!empty($express_no)) {
            $query->where_in('express_no', $express_no);
        } else {
            $query->where('express_no <>', '');
            $query->limit($limit);
        }
        $query->group_by('express_no');
        $result = $query->get($this->multiple_return_info_table)->result_array();
        return $result;
    }


}