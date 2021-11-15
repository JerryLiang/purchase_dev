<?php

use MongoDB\Driver\BulkWrite;
use MongoDB\Driver\Command;
use MongoDB\Driver\Query;

/**
 * 系统消息
 * @time:2020/8/12
 * @author:Dean
 **/
class System_news extends MX_Controller
{
    private $mongo_db = null;
    private $mongo_db_name = null;

    const NEWS_NOT_HINT_NOT_READ = 1;// 消息未提示并且未读
    const NEW_HINT_NOT_READ = 2;// 消息提示了但是未读
    const NEW_HINT_AND_READ = 3;// 消息提示了并且已经读取

    private $_is_read_arr = [
        self::NEWS_NOT_HINT_NOT_READ => '未读取',
        self::NEW_HINT_NOT_READ => '已提示',
        self::NEW_HINT_AND_READ => '已读取'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->get_mongo_db();


    }

    //加载mogo_db
    private function get_mongo_db()
    {

        $this->_ci = get_instance();
        $this->_ci->load->config('mongodb');
        $host = $this->_ci->config->item('mongo_host');
        $port = $this->_ci->config->item('mongo_port');
        $user = $this->_ci->config->item('mongo_user');
        $password = $this->_ci->config->item('mongo_pass');
        $author_db = $this->_ci->config->item('mongo_db');
        $this->mongo_db = new MongoDB\Driver\Manager("mongodb://{$user}:{$password}@{$host}:{$port}/{$author_db}");
        $this->mongo_db_name = $author_db;


    }

    //操作类型配置
    private function opr_set()
    {
        return [
            1 => '采购单审核',
            2 => '信息修改审核',
            3 => '待采购主管审核',
            4 => '待采购副经理审核',
            5 => '待采购经理审核',
            6 => '待开发经理审核',
            7 => '待供管部审核',
            8 => '待品控部审核',
            9 => '待供应链总监审核',
            10 => '待品控部审核',
            11 => '财务审核',
            12 => '财务主管审核',
            13 => '财务经理审核',
            14 => '作废审核',


        ];


    }

    private function module_set()
    {
        return [
            'purchase' => '采购单',
            'product' => '产品管理',
            'request_funds' => '请款单',
            'cancel' => '取消未到货',
            'report' => '报损',
            'scree' => 'SKU 屏蔽',
            'declare' => '报关发票',
            'check_product' => '验货管理',
            'abnormal' => '异常冲销审核'
        ];

    }

    //url_info 配置

    private function url_set()
    {
        return [
            'purchase' => ['method' => 'post',
                'url' => ['/api/purchase/purchase_order/get_order_list'],
                'params' => [['purchase_number' => '', 'list_type' => 1, 'limit' => 20, 'offset' => 1]],

            ],
            'product' => [
                'method' => 'post',
                'url' => ['/api/product/product_mod_audit/get_product_list'],
                'params' => [['create_user_id' => '', 'create_user_name' => '', 'sku' => '', 'type' => 1, 'is_audit_type' => 2, 'limit' => 20, 'offset' => 1]],
            ],
            //  /api/purchase/purchase_financial_audit_list/financial_audit_list
            'declare' => ['method' => 'post',
                'url' => ['/api/purchase/purchase_financial_audit_list/financial_audit_list'],
                'params' => [['invoice_number' => '', 'limit' => 20, 'offset' => 1]],

            ],

            'cancel' => ['method' => 'get',
                'url' => ['/api/purchase/puerchase_unarrived/cencel_lits'],
                'params' => [['cancel_number' => '', 'limit' => 20, 'offset' => 1]],

            ],

            'report' => ['method' => 'get',
                'url' => ['/api/abnormal/report_loss/get_report_loss_list'],
                'params' => [['bs_number' => '', 'limit' => 20, 'offset' => 1]],

            ],
            'scree' => ['method' => 'get',
                'url' => ['/api/abnormal/product_scree/get_scree_list'],
                'params' => [['sku' => [], 'limit' => 20, 'offset' => 1]],

            ],
            'request_funds' =>
                ['method' => 'get',
                    'url' => ['/api/finance/purchase_order_pay/payment_list'],
                    'params' => [['requisition_number' => '', 'limit' => 20, 'offset' => 1]],

                ],
            'abnormal' =>
                ['method' => 'get',
                    'url' => ['/api/statement/charge_against/get_charge_against_list'],
                    'params' => [['charge_against_number' => '', 'limit' => 20, 'offset' => 1]],

                ],

            'supplier' =>
                ['method' => 'post',
                    'url' => ['/api/supplier/supplier/audit_supplier_list'],
                    'params' => [['apply_no' => '', 'limit' => 20, 'offset' => 1]],

                ],
            'visit_report' =>
                ['method' => 'post',
                    'url' => ['/api/supplier/supplier/supplier_visit_list'],
                    'params' => [['limit' => 20, 'offset' => 1]],
                ]

        ];
    }

    /**
     * 统计未读信息总数据
     * @author:luxu
     * @time:2021年9月8号
     **/
    private function get_total_no_read()
    {
        $filter['recv_name'] = getActiveUserName();
        $filter['is_read'] = self::NEWS_NOT_HINT_NOT_READ;
        $command = new MongoDB\Driver\Command(['count' => "{$this->mongo_db_name}.message", 'query' => $filter]);
        $result = $this->mongo_db->executeCommand($this->mongo_db_name, $command)->toArray();
        return $result[0]->n;

    }


    /**
     * 系统消息展示列表
     */
    public function news_list(){
        $url_set = $this->url_set();

        $params = [
            'module' => $this->input->get_post('module'),// 公告or操作手册
            'title' => $this->input->get_post('title'),
            'create_time_start' => $this->input->get_post('create_time_start'),
            'create_time_end' => $this->input->get_post('create_time_end'),
            'type' => $this->input->get_post('type')
        ];

        $page_data      = $this->format_page_data();
        $mongo_query    = $this->get_news_list($params, $page_data['offset'], $page_data['limit'], $page_data['page']);
        $cursor         = $this->mongo_db->executeQuery("{$this->mongo_db_name}.message", $mongo_query)->toArray();

        $filter     = ['recv_name' => getActiveUserName()];
        $command    = new MongoDB\Driver\Command(['count' => 'message', 'query' => $filter]);
        $result     = $this->mongo_db->executeCommand($this->mongo_db_name, $command)->toArray();
        $total      = $result[0]->n;


        if (!empty($cursor)) {
            foreach ($cursor as &$message) {
                $message = get_object_vars($message);
                $message['mongo_obj_id'] = base64_encode(serialize($message['_id']));// 根据mongodb_id 生成ID，便于前端传参

                $message['is_read_cn'] = isset($this->_is_read_arr[$message['is_read']])?$this->_is_read_arr[$message['is_read']]:'未知状态';

                $url_info = $url_set[$message['module']]??'';
                if (!empty($url_info)) {
                    foreach ($url_info['params'][0] as $key => &$url_one) {
                        if ($message['module'] != 'product') {
                            if (empty($url_one)) {
                                if ($message['module'] == 'scree') {
                                    $url_one = [$message['param']];
                                } else {
                                    $url_one = $message['param'];
                                }
                            }
                        } else {
                            if ($key == 'create_user_id') {
                                $url_info['params'][0][$key] = (string)$message['apply_id'];
                            } elseif ($key == 'create_user_name') {
                                $url_info['params'][0][$key] = $message['recv_name'];
                            } elseif ($key == 'sku') {
                                $url_info['params'][0][$key] = $message['param'];
                            }
                        }
                    }
                    $message['url_info'] = $url_info;
                }
            }
        }

        $return_data = [
            'data_list' => [
                'value' => $cursor,
                'drop_down_box' => ['opr_set' => $this->opr_set(),
                    'module_set' => $this->module_set()
                ]
            ],
            'paging_data' => [
                'total' => $total,
                'offset' => $page_data['page'],
                'limit' => $page_data['limit']
            ],
        ];

        $this->success_json($return_data['data_list'], $return_data['paging_data']);
    }

    //获取消息列表
    private function get_news_list($params, $offset = null, $limit = null, $page = 1){
        $query = array();
        if (!empty($params['module'])) {
            $query['module'] = $params['module'];
        }
        if (!empty($params['title'])) {
            $query['pushMessage'] = array('$regex' => "^.*" . $params['title'] . ".*?$");
        }
        if (!empty($params['create_time_start'])) {
            $query['create_time'] = array('$gte' => $params['create_time_start']);
        }
        if (!empty($params['create_time_end'])) {
            $query['create_time'] = array('$lte' => $params['create_time_end']);
        }
        if (!empty($params['type'])) {
            $query['type'] = $params['type'];
        }

        $query['recv_name'] = getActiveUserName();//只显示当前接收人消息
        $options['skip']    = $offset;
        $options['limit']   = $limit;
        $options['sort']    = ['create_time' => -1];
        $query              = new MongoDB\Driver\Query($query, $options);

        return $query;
    }

    /**
     * 前端长轮训获取接收人消息
     */
    public function receive_news(){
        $user_id = getActiveUserId();//登录用户id

        $url_set = $this->url_set();

        // 获取 Mongodb中 系统消息的数据
        $query = [];
        $query['recv_name'] = getActiveUserName();
        $query['is_read'] = self::NEWS_NOT_HINT_NOT_READ;// 未提示未读取的消息

        $options = [
            'sort' => ['create_time' => -1], //排序 -1 为降序 ，1 升序
            "limit" => 5, //分页查询的数量
        ];
        $query = new MongoDB\Driver\Query($query, $options);
        $cursor = $this->mongo_db->executeQuery("{$this->mongo_db_name}.message", $query)->toArray();

        if (!empty($cursor)) {
            foreach ($cursor as $key_1 => &$message) {
                $message = get_object_vars($message);
                $url_info = $url_set[$message['module']];

                if (!empty($url_info)) {
                    foreach ($url_info['params'][0] as $key => &$url_one) {
                        if ($message['module'] != 'product') {
                            if (empty($url_one)) {
                                $url_one = $message['param'];
                            }
                        } else {
                            if ($key == 'create_user_id') {
                                $url_info['params'][0][$key] = (string)$message['apply_id'];
                            } elseif ($key == 'create_user_name') {
                                $url_info['params'][0][$key] = $message['recv_name'];
                            } elseif ($key == 'sku') {
                                $url_info['params'][0][$key] = $message['param'];
                            }
                        }
                    }
                    $message['url_info'] = $url_info;
                }
            }
        }


        $this->load->model('purchase_news_model');

        //用户消息
        $read_info = $this->purchase_news_model->purchase_db->select('*')
            ->from('news_login_log')
            ->where('user_id', $user_id)
            ->get()
            ->row_array();

        //用户是否点进来看过（上一次登录到当前新增的消息记录）
        // 上次一登录时间，如果没有登录过则查询最近三个月的消息
        $create_time = !empty($read_info)?$read_info['read_time']:date('Y-m-d H:i:s',strtotime('-3 days'));

        // 在 pur_news_list 中但是没有改用户阅读记录的 list
        $querySql = "SELECT A.*
            FROM pur_news_list AS A
            LEFT JOIN pur_news_read_log AS B ON A.id=B.news_id AND B.is_read IN(".self::NEW_HINT_NOT_READ.",".self::NEW_HINT_AND_READ.") AND B.user_id = {$user_id}
            WHERE A.create_time > '{$create_time}'
            AND B.id IS NULL
            ORDER BY A.id DESC";

        $not_read_infos = $this->purchase_news_model->purchase_db->query($querySql)->result_array();

        // 查询总数
        $querySqlNum = "SELECT COUNT(1) AS num
            FROM pur_news_list AS A
            LEFT JOIN pur_news_read_log AS B ON A.id=B.news_id AND B.is_read IN(".self::NEW_HINT_AND_READ.") AND B.user_id = {$user_id}
            WHERE A.create_time > '{$create_time}'
            AND B.id IS NULL";
        $notReadInfosNum = $this->purchase_news_model->purchase_db->query($querySqlNum)->row_array();
        $notReadInfosNum = !empty($notReadInfosNum)?$notReadInfosNum['num']:0;

        $read_news_list = [];// 需要展示的未提示消息
        $batch_insert_read = [];
        foreach($not_read_infos as $items_value){
            $batch_insert_read[] = [
                'user_id' => $user_id,
                'news_id' => $items_value['id'],
                'is_read' => self::NEW_HINT_NOT_READ
            ];

            if(count($read_news_list) < 5){// 只展示最新5条数（ID降序排序查询）
                $read_news_list[] = $items_value;
            }

            if(count($batch_insert_read) > 500){
                $this->purchase_news_model->purchase_db->insert_batch('news_read_log',$batch_insert_read);
            }
        }

        if($batch_insert_read){
            $this->purchase_news_model->purchase_db->insert_batch('news_read_log',$batch_insert_read);
        }


        //系统消息数
        $filter = [];
        $filter['recv_name'] = getActiveUserName();
        $filter['is_read'] = ['$lt' => self::NEW_HINT_AND_READ];// 查询状态 is_read < 3 的数据，3.未读取
        $command = new MongoDB\Driver\Command(['count' => "message", 'query' => $filter]);
        $result = $this->mongo_db->executeCommand($this->mongo_db_name, $command)->toArray();

        $info = [
            'no_read_news_num' => strval($notReadInfosNum),// 总的未提示+未读的消息数量
            'no_system_news_num' => strval($result[0]->n),// 总的未提示+未读的系统消息数量
            'news_list' => $cursor,// 需要提示的 消息列表
            'read_news_list' => $read_news_list,// 需要提示的 系统消息列表
        ];
        if($info['no_read_news_num'] > 99) $info['no_read_news_num'] = '99+';
        if($info['no_system_news_num'] > 99) $info['no_system_news_num'] = '99+';

        // 修改 update，未提示且未读的 更新为 已提示未读
        $bulk = new MongoDB\Driver\BulkWrite();
        $filters = ['recv_name' => getActiveUserName(), 'is_read' => self::NEWS_NOT_HINT_NOT_READ];
        $sets = ['$set' => ['is_read' => self::NEW_HINT_NOT_READ]];
        $updateOptions = ['multi' => true, 'upsert' => false];
        $bulk->update($filters, $sets, $updateOptions);
        $result = $this->mongo_db->executeBulkWrite("{$this->mongo_db_name}.message", $bulk);

        $this->success_json($info);
    }


    /**
     * 格式化页面数据
     * @return array
     */
    public function format_page_data(){
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offset = ($page - 1) * $limit;

        return [
            'offset' => $offset,
            'limit' => $limit,
            'page' => $page
        ];
    }


    //获取用户一定频率浏览新增消息数
    public function get_user_no_read_nums()
    {
        $query['recv_name'] = getActiveUserName();
        $query['is_read'] = self::NEWS_NOT_HINT_NOT_READ;
        $mongo_query = new MongoDB\Driver\Query($query);
        $command = new MongoDB\Driver\Command(['count' => 'message', 'query' => $mongo_query]);
        $result = $this->mongo_db->executeCommand($this->mongo_db_name, $command)->toArray();
        $total = $result[0]->n;
        $this->success_json($total);
    }

    /**
     * 成功的json输出
     * @author liwuxue
     * @date 2019/2/13 9:56
     * @param array $data
     * @param array $page_data
     * @param string $error
     *  {"status":1,"errorMess":"", "data_list":{}, "paging_data":{}}
     */
    protected function success_json($data = [], $page_data = null, $error = "恭喜您,请求成功")
    {
        http_response(response_format(1, $data, $error, $page_data));
    }

    /**
     * 设置消息为已读
     */
    public function set_news_read(){
        $obj_id = $this->input->get_post('obj_id');
        if (empty($obj_id)) {
            header('Content-Type: application/json;charset=utf-8');
            echo json_encode(['status' => 0, 'data_list' => [], 'errorMess' => '参数ID缺失']);
            exit;
        }

        $this->load->model('purchase_news_model');
        $user_id = getActiveUserId();//登录用户id

        if (is_numeric($obj_id)) {// ID为数字的 为 news_list 的记录

            $have_id = $this->purchase_news_model->purchase_db->select('*')
                ->from('news_read_log')
                ->where('news_id', $obj_id)
                ->where('user_id', $user_id)
                ->where('is_read', self::NEW_HINT_AND_READ)
                ->get()
                ->row_array();

            // 没有已读记录时生成一条记录
            if (empty($have_id)) {
                $this->purchase_news_model->purchase_db->insert('news_read_log', ['user_id' => $user_id, 'news_id' => $obj_id,'is_read' => self::NEW_HINT_AND_READ]);
            }

        } else {
            // 更新 Mongodb 数据库状态为已读
            $mongo_obj_id = unserialize(base64_decode($obj_id));
            $bulk = new MongoDB\Driver\BulkWrite();
            $bulk->update(['_id' => $mongo_obj_id], ['$set' => ['is_read' => self::NEW_HINT_AND_READ]]);
            $result = $this->mongo_db->executeBulkWrite("{$this->mongo_db_name}.message", $bulk);
        }

        $this->success_json();
    }


}