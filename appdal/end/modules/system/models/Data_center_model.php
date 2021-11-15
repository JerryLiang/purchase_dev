<?php
/**
 * 数据中心
 * User: luxu
 * Date: 2020/02/22 15:00
 */

class Data_center_model extends Purchase_model
{
    protected $table_name = 'center_data';// 数据表名称

    public function __construct()
    {
        parent::__construct();
        $this->load->model('user/User_group_model');

    }

    /**
     * 列表
     * @param $clientData
     * @param int $limit
     * @param int $page
     * @param bool $type
     * @return array
     */
    public function getCenterData($clientData, $limit = 20, $page = 1, $type = true)
    {

        $user_id = jurisdiction(); //当前登录用户ID
        $role_name = get_user_role();//当前登录角色
        $data_role = getRole();//数据权限配置
        $res_arr = array_intersect($role_name, $data_role);
//        $query_builder = $this->purchase_db;
        $query = $this->purchase_db->from($this->table_name);
        if ($type) {
            if( !(!empty($res_arr) OR $user_id === true )){
                //$query_builder->where_in('ppo.buyer_id', $user_id);
                $query->where_in("user_id", $user_id);
            }
        }
        // HTTP 客户端传入模块名称
        if (isset($clientData['module_ch_name']) && !empty($clientData['module_ch_name'])) {
            $query->where("module_ch_name", $clientData['module_ch_name']);
        }

        if (isset($clientData['file_name']) && !empty($clientData['file_name'])) {
            $query->where("file_name", $clientData['file_name']);
        }
        // 导出状态
        if (isset($clientData['data_status']) && !empty($clientData['data_status'])) {
            $query->where("data_status", $clientData['data_status']);
        }

        // 审核状态
        if (isset($clientData['examine_status']) && !empty($clientData['examine_status'])) {
            $query->where("examine_status", $clientData['examine_status']);
        }
        // 操作人
        if (isset($clientData['add_user_name']) && !empty($clientData['add_user_name'])) {
            $query->where_in("add_user_name", $clientData['add_user_name']);
        }
        // 审核人
        if (isset($clientData['examine_user_name']) && !empty($clientData['examine_user_name'])) {
            $query->where_in("examine_user_name", $clientData['examine_user_name']);
        }

        // 创建时间
        if (isset($clientData['add_time_start']) && isset($clientData['add_time_end'])) {
            $query->where("add_time>=", $clientData['add_time_start'])->where("add_time<=", $clientData['add_time_end']);
        }
        // 审核时间
        if (isset($clientData['examine_time_start']) && isset($clientData['examine_time_end'])) {
            $query->where("examine_time>=", $clientData['examine_time_start'])->where("examine_time<=", $clientData['examine_time_end']);
        }
        $clone_db = clone($this->purchase_db);
        $total_count = $this->purchase_db->count_all_results();//符合当前查询条件的总记录数
        $this->purchase_db = $clone_db;
        $result = $this->purchase_db->limit($limit, ($page - 1) * $limit)->order_by("id DESC")->get()->result_array();
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $result[$key]['speed_of_progress'] = (($value['progress'] / $value['number']) * 100) . "%";
                switch ($value['data_status']) {
                    case 1:
                        $result[$key]['data_status_ch'] = "正在导出";
                        break;
                    case 2:
                        $result[$key]['data_status_ch'] = "待导出";
                        break;
                    case 3:
                        $result[$key]['data_status_ch'] = "导出完毕";
                        break;
                }

                switch ($value['examine_status']) {
                    case 1:
                        $result[$key]['examine_status_ch'] = "审核通过";
                        break;
                    case 2:
                        $result[$key]['examine_status_ch'] = "待审核";
                        break;
                    case 3:
                        $result[$key]['examine_status_ch'] = "审核驳回";
                        break;
                }
            }
        }
        return $data = array(

            'list' => $result,
            'total' => $total_count
        );
    }

    public function getCenterSelect()
    {

        $get_add_user_name = " SELECT add_user_name FROM pur_" . $this->table_name . " GROUP BY add_user_name";
        $add_user_name = $this->purchase_db->from($this->table_name)->query($get_add_user_name)->result_array();

        $get_examine_user_name = " SELECT examine_user_name FROM pur_" . $this->table_name . " GROUP BY examine_user_name";
        $examine_user_name = $this->purchase_db->from($this->table_name)->query($get_examine_user_name)->result_array();

        return array(

            'user_name' => array_column($add_user_name, "add_user_name"),
            'examine_user_name' => array_column($examine_user_name, "examine_user_name")
        );
    }


    /**
     * 获取导出数据
     * @params $ids    导出请求ID
     * @author:luxu
     * @time:2020/02/26
     **/

    public function getConditions($ids)
    {
        $result = $this->purchase_db->from($this->table_name)->where_in('id', $ids)->get()->result_array();
        return $result;
    }

    /**
     * 更新导出数据
     * @params $ids    导出请求ID
     *         $data array 审核数据
     * @author:luxu
     * @time:2020/02/26
     **/

    public function updateCenterData($ids, $data)
    {
        $result = $this->purchase_db->where_in("id", $ids)->update($this->table_name, $data);
        return $result;
    }

    /**
     * 删除数据
     * @params $ids    导出请求ID
     *
     * @author:luxu
     * @time:2020/02/26
     **/

    public function delete_center_data($ids)
    {
        $result = $this->purchase_db->where_in("id", $ids)->delete($this->table_name);
        return $result;
    }

    public function get_items($params)
    {
        $result = $this->purchase_db->from($this->table_name)->where($params)->get()->result_array();
        return $result;
    }

    public function getQueue()
    {

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
        if ($envelope) {
            $data = $envelope->getBody();

            $queue_obj->ack($envelope->getDeliveryTag());


//        $mq->ack($envelope->getDeliveryTag());
            $mq->disconnect();
        }
        return $data;
    }

    public function inventoryitems_export($data){

        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 2020, 0.5)) {
                $data['swoole_type'] = 'INVENTORYITEMS';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    public function charge_against($data){

        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 2020, 0.5)) {
                $data['swoole_type'] = 'CHARGE';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    public function alternative_data($data){
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 2029, 0.5)) {
                $data['swoole_type'] = 'CHARGE';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }


    }

    public function import_abnormal_data($data){

        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 2025, 0.5)) {
                $data['swoole_type'] = 'CHARGE';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    public function balance($data){

        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 2020, 0.5)) {
                $data['swoole_type'] = 'BALANCE';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }



    public function delivery($data){
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 2021, 0.5)) {
                $data['swoole_type'] = 'DELIVERY';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }

    }

    public function cancel_unarrived_goods_examine_down($data){

        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 2023, 0.5)) {
                $data['swoole_type'] = 'DELIVERY';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }

    }


    public function abnormal($datass){

        if (!empty($data)) {
            $client = new \Swoolesss\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 2024, 0.5)) {
                $data['swoole_type'] = 'DELIVERY';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }


    public function push_product_data($data){

        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 20612, 0.5)) {
                $data['swoole_type'] = 'PUSHPRODUCTDATA';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    /**
     * 少数少款导出
     * @author:luxu
     * @time:2021年1月10号
     **/
    public function lack_download($data){


        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9517, 0.5)) {
                $data['swoole_type'] = 'LACK';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    /**
     * 退款数据导出
     * @author:luxu
     * @time:2021年1月16号
     **/
    public function refund($data){
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9518, 0.5)) {
                $data['swoole_type'] = 'LACK';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }

    }

    /**
     * 备货单导出
     * @author:luxu
     * @time:2021年2月20号
     **/
    public function suggest_data($data){

        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9520, 0.5)) {
                $data['swoole_type'] = 'SUGGEST';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }

    }

    /**
     * 需求单导出单导出
     * @author:luxu
     * @author:luxu
     * @time:2021年3月1号
     **/
    public function demander_data($data){

        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9521, 0.5)) {
                $data['swoole_type'] = 'DEMANDER';
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    /**
     * 筛选mq数据推到后端处理
     * @param string $data
     * @return bool
     * @throws Exception
     */
    public function handle_quene_data($data = '')
    {
        //筛选正在导出的数据
        $down_data = $this->get_items("data_status=1 and swoole_server='".SWOOLE_SERVER."'");
        $down_num = count($down_data);
        //限制5个以内的导出任务
        $total = 5;
        $return = [
            'code' => 1,
            'msg' => '推入下载队列',
        ];
//        $this->load->model('system/Data_center_model');
//        try {
        if ($down_num <= $total) {
//            for ($i = 1; $i <= $total - $down_num; $i++) {
            //从mq获取数据
            if (empty($data)) {
                $data = $this->getQueue();
            }
            if (!empty($data)) {
                $data1 = json_decode(htmlspecialchars_decode($data), true);
                $items = $this->get_items("id = " . $data1['data']['id']);
                if ($items[0]['data_status'] == 2 || $items[0]['data_status'] == 1) {
                    $logs = ['log' => $data];
                    $this->purchase_db->insert('center_log', $logs);
                    $id_array = ['id' => $data1['data']['id']];
                    switch ($data1['data']['module_ch_name']) {
                        case 'PURCHASEORDER':
                            $return = $this->purchase_client($id_array);
                            break;
                        case 'ORDERTACKING':
                            $return = $this->progress_client($id_array);
                            break;
                        case 'PRODUCTDATA':
                        case 'PRODUCTAUDITDATA':
                            $return = $this->product_client($id_array);
                            break;
                        case 'REDUCED':
                            $return = $this->reduced_export($id_array);
                            break;
                        case 'INVENTORYITEMS':
                            $return = $this->inventoryitems_export($id_array);
                            break;

                        case 'CHARGE_AGAINST':
                            $return = $this->charge_against($id_array);
                            break;
                        case 'BALANCE':
                            $return = $this->balance($id_array);
                            break;
                        case 'DELIVERY':
                            $return = $this->delivery($id_array);
                            break;
                        case 'FINANCE_LIST':
                            $return = $this->payable_list_client($id_array);
                            break;
                        case 'FINANCE_PAYMENT_LIST':
                            $return = $this->payment_list_client($id_array);
                            break;
                        case 'UNARRIVED':
                            $return = $this->cancel_unarrived_goods_examine_down($id_array);
                            break;
                        case 'ABNORMAL':
                            $return = $this->abnormal($id_array);
                            break;
                        case 'COMPACT_PDF_EXPORT':// 批量下载合同PDF
                        case 'COMPACT_LIST_EXPORT':// 合同列表下载
                        case 'STATEMENT_PDF_EXPORT':// 对账单PDF文件下载
                            $return = $this->compact_statement_download($id_array);// 大文件下载独立服务器
                            break;
                        case "EXPORT_DELIVERY":// 供应商交期导出
                        case "STATEMENT_EXPORT_CSV":// 对账单导出
                        case "SUPPLIER_BALANCE_ORDER":
                            $return = $this->supplier_balance_download($id_array);
                            break;

                        // 少数少款导出
                        case "LACK":
                            $return = $this->lack_download($id_array);
                            break;
                        // 退款数据导出
                        case "REFUND":
                            $return = $this->refund($id_array);
                            break;
                        // 备货单导出
                        case "SUGGESTDATA":
                            $return = $this->suggest_data($id_array);
                            break;
                         // 需求单导出
                        case "DEMANDER":
                            $return = $this->demander_data($id_array);
                            break;
                        case "ALTERNATIVE":
                            $return = $this->alternative_data($id_array);
                            break;
                        case "import_abnormal":
                            $return = $this->import_abnormal_data($id_array);
                            break;

                        default:
                            break;
                    }
                } else {
                    $return = [
                        'code' => 0,
                        'msg' => '数据库没有此记录',
                    ];
                }
            } else {
                $return = [
                    'code' => 0,
                    'msg' => '队列中没有文件',
                ];
            }
        } else {
            $return = [
                'code' => 0,
                'msg' => "有" . $down_num . "个导出任务在执行，详情查看下载中心任务列表。",
            ];
        }
//        } catch (Exception $exp) {
//            $return = [
//                'code' => 0,
//                'msg' => "下载出错",
//            ];
//        }
        return $return;
    }

    /**
     * @param $data
     * 应付款管理
     */
    public function payable_list_client($data = [])
    {
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9506, 1)) {
                $client->send(json_encode($data));
                $client->recv(35); // 35秒超时
                $client->close();
            }
        } else {
            return False;
        }
    }

    /**
     * @param $data
     * 应付款管理
     */
    public function payment_list_client($data = [])
    {
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9507, 0.5)) {
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    /**
     * @param $data
     * 合同单 对账单
     */
    public function compact_statement_download($data = [])
    {
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9508, 0.5)) {
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    /**
     * @param $data
     * 供应商余额表模块
     */
    public function supplier_balance_download($data = [])
    {
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9512, 0.5)) {
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

    /**
     * @param $data
     * 订单跟踪导出
     */
    public function progress_client($data = [])
    {
        if (!empty($data)) {
            //
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9504, 0.5)) {
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
//            Co\run(function(){
//                $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
//                if ($client->connect(SWOOLE_SERVER, 9504, 0.5))
//                {
////                    echo "connect failed. Error: {$client->errCode}\n";
//                    $client->send(json_encode($data));
//                    echo $client->recv();
//                    $client->close();
//                }
//            });
        } else {
            return False;
        }
    }

    /**
     * @param $data
     * 采购单导出
     */
    public function purchase_client($data = [])
    {
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
//
            if ($client->connect(SWOOLE_SERVER, 9503, 0.5)) {
//                $a = $client->isConnected();
//            var_dump($a);
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
//
//            Co\run(function(){
//                $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
//                if ($client->connect(SWOOLE_SERVER, 9503, 0.5))
//                {
////                    echo "connect failed. Error: {$client->errCode}\n";
//                    $client->send(json_encode($data));
//                    echo $client->recv();
//                    $client->close();
//                }
//
//            });
        } else {
            return False;
        }
    }

    public function reduced_export($data=[]){
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9510, 0.5)) {
//                $a = $client->isConnected();
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }

    }

    public function product_client($data = [])
    {
        if (!empty($data)) {
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP);
            if ($client->connect(SWOOLE_SERVER, 9505, 0.5)) {
//                $a = $client->isConnected();
                $client->send(json_encode($data));
                $client->recv();
                $client->close();
            }
        } else {
            return False;
        }
    }

}