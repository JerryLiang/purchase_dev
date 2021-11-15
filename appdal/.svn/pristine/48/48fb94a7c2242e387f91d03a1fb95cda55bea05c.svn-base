<?php
/**
 * Created by PhpStorm.
 * 操作日志记录类
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Operator_log_model extends Purchase_model{
    protected $table_name          = 'operator_log';
    protected $api_log_table_name  = 'api_request_log';
    protected $api_page_table_name = 'api_page_circle';

    public function __construct(){
        parent::__construct();

    }


    /**
     * 添加操作日志
     * @author Jolon
     * @param array $data   要保存的数据
     * @param string $table_name 日志表名（默认 operator_log）
     * @return bool  true.成功,false.失败
     *
     * @example
     *      $data = array(
     *          id              => 目标记录编号（int|string）
     *          type            => 操作类型（如 操作的数据表名）
     *          content         => 改变的内容（简略信息,支持搜索）
     *          detail          => 改变的内容（详细信息,文本类型）
     *          user_id         => 操作人ID（默认当前用户）
     *          user            => 操作人（默认当前用户）
     *          time            => 操作时间（exp.2018-11-23 16:16:16  默认当前时间）
     *          operate_ip      => 操作IP（默认当前用户IP）
     *          operate_route   => 操作路由（默认当前路由）
     *          is_show         => 标记日志类型（1.展示日志，2.非展示日志，默认 1）
     *      )
     */
    public function insert_one_log($data,$table_name = 'operator_log'){
        $this->load->helper('url');
        $this->load->helper('user');
        $detail = isset($data['detail'])?$data['detail']:'';
        if(!is_string($detail)){
            $detail = json_encode($data['detail'],JSON_UNESCAPED_UNICODE);
        }

        // 取 操作用户：先去$data参数的值，再取SESSION的值，如果都没有则设置默认值admin
        $user_id = (isset($data['user_id']) and $data['user_id']) ? $data['user_id']: getActiveUserId();
        if(empty($user_id)) $user_id = isset($_SESSION['user_id'])?$_SESSION['user_id']:1;
        $user = (isset($data['user']) and $data['user']) ? $data['user']: getActiveUserName();
        if(empty($user)) $user = isset($_SESSION['user_name'])?$_SESSION['user_name']:'admin';

        $insert_data = [
            'record_number'  => isset($data['id']) ? $data['id'] : '',
            'record_type'    => isset($data['type']) ? strtoupper($data['type']) : '',
            'content'        => isset($data['content']) ? $data['content'] : '',
            'content_detail' => $detail,// 详细信息转换
            'operate_ip'     => isset($data['ip']) ? $data['ip'] : getActiveUserIp(),
            'operate_route'  => isset($data['route']) ? $data['route'] : uri_string(),
            'operator_id'    => $user_id,
            'operator'       => $user,
            'operate_time'   => isset($data['time']) ? $data['time'] : date('Y-m-d H:i:s', time()),
            'is_show'        => isset($data['is_show']) ? $data['is_show'] : 1,
            'ext'            => isset($data['ext']) ? $data['ext'] : '',
            'operate_type'   => isset($data['operate_type']) ? $data['operate_type'] : 0,//操作类型(暂时只有供应商2019-08-07)
        ];

        if(!empty($table_name)) $this->table_name = $table_name;// 支持多种日志表

        return $this->purchase_db->insert($this->table_name,$insert_data);
    }

    /**
     * 添加 API 请求 操作日志
     * @author Harvin
     * @param array $data   要保存的数据
     * @param string $table_name 日志表名（默认 api_request_log）
     * @return bool  true.成功,false.失败
     *
     * @example
     *      $data = array(
     *          record_number       => 操作记录编号（int|string）
     *          record_type         => 操作记录类型（如 操作的数据表名）
     *          api_url             => 接口地址
     *          post_content        => 接口推送数据
     *          response_content    => 接口回传数据
     *          create_time         => 操作时间（exp.2018-11-23 16:16:16  默认当前时间）
     *          status              => 接口状态(默认 1 1.success 或 0.fail)
     *      )
     */
    public function insert_api_request_log($data,$table_name = 'api_request_log'){
        // 接口推送数据
        $post_content = isset($data['post_content']) ? $data['post_content'] : '';
        if(!is_string($post_content))
            $post_content = json_encode($post_content,JSON_UNESCAPED_UNICODE);
        // 接口回传数据
        $response_content = isset($data['response_content']) ? $data['response_content'] : '';
        if(!is_string($response_content))
            $response_content = json_encode($response_content,JSON_UNESCAPED_UNICODE);

        $insert_data = [
            'record_number'    => isset($data['record_number']) ? $data['record_number'] : '',
            'record_type'      => isset($data['record_type']) ? strtoupper($data['record_type']) : '',
            'api_url'          => isset($data['api_url']) ? $data['api_url'] : '',
            'post_content'     => $post_content,
            'response_content' => $response_content,
            'create_time'      => (isset($data['create_time']) and $data['create_time'])?$data['create_time']:date('Y-m-d H:i:s'),
            'status'           => (isset($data['status']) and empty($data['status']))?0:1,// 默认 1
        ];

        if(!empty($table_name)) $this->api_log_table_name = $table_name;// 支持多种日志表

        return $this->purchase_db->insert($this->api_log_table_name, $insert_data);
    }


    /**
     * 一键生成采购单调试日志
     */
    public function create_purchase_log($data, $table_name='purchase_create_log')
    {
        try{
            $this->purchase_db->insert($table_name, $data);
        }catch (Exception $e){}
    }

    /**
     * 添加 API 计划任务分页查询记录器
     * @author Jolon
     * @param array $data   要保存的数据
     * @return bool  true.成功,false.失败
     *
     * @example
     *      $data = array(
     *          page            => 当前页码
     *          api_type        => 操作类型（如 操作的数据表名或 控制器相对路径）
     *          create_time     => 操作时间（exp.2018-11-23 16:16:16  默认当前时间）
     *      )
     */
    public function insert_api_page_circle($data){
        $insert_data = [
            'page'          => isset($data['page']) ? $data['page'] : '',
            'api_type'      => isset($data['api_type']) ? strtolower($data['api_type']) : '',
            'create_time'   => isset($data['time']) ? $data['time'] : date('Y-m-d H:i:s', time()),
        ];

        return $this->purchase_db->insert($this->api_page_table_name,$insert_data);
    }

    /**
     * 查询保存的日志
     * @author Jolon
     * @param array $query  查询条件
     * @param string $field 查询字段
     * @param string $table_name 日志表名（默认 operator_log）
     * @return bool|array   array.结果集，false.查询条件缺失
     *
     * @example
     *      $query = array(
     *          id          => 目标记录编号（int|string）
     *          type        => 操作类型（关联模型）
     *          content     => 改变的内容（简略信息,支持搜索）
     *          user        => 操作人（默认当前用户）
     *          is_show     => 标记日志类型（1.可展示，2.否）
     *          page        => 查询页数
     *          limit       => 查询调速
     *     )
     */
    public function query_logs($query,$field='*',$table_name = null){
        $real_query = [];
        isset($query['id']) AND $real_query['record_number'] = $query['id'];
        isset($query['type']) AND $real_query['record_type'] = strtoupper($query['type']);
        isset($query['content']) AND $real_query['content'] = $query['content'];
        isset($query['user']) AND $real_query['operator'] = $query['user'];

        if(empty($real_query)) return false;// 查询条件缺失
        isset($query['is_show']) AND $real_query['is_show'] = $query['is_show'];
        // 分页查询
        $limit = $offset = null;
        if(isset($query['page']) or isset($query['limit'])){
            $page   = (empty($query['page']) or intval($query['page']) < 1) ? 1 : intval($query['page']);
            $limit  = (empty($query['limit']) or intval($query['limit']) < 1) ? 50 : intval($query['limit']);
            $offset = ($page - 1) * $limit;
        }

        $this->purchase_db->where($real_query);

        if(!empty($table_name)) $this->table_name = $table_name;// 支持多种日志表

        $results = $this->purchase_db->select($field)->order_by("id", "desc")->get($this->table_name,$limit,$offset)->result_array();

        return $results ? $results:array();
    }


    public function get_supplier_log_list($query){
        $this->load->model('Supplier_model');
        $this->load->model('Supplier_payment_account_model', 'paymentAccountModel');
        $this->load->model('Supplier_contact_model', 'contactModel');
        $this->load->model('Supplier_images_model', 'imagesModel');
        $this->load->model('Supplier_buyer_model', 'buyerModel');
        $this->load->model('Supplier_product_line_model', 'productLineModel');

        $types = [
            $this->Supplier_model->table_nameName(),
            $this->contactModel->table_nameName(),
            $this->buyerModel->table_nameName(),
            $this->paymentAccountModel->table_nameName(),
            $this->imagesModel->table_nameName(),
            $this->productLineModel->level_table_nameName(),
        ];

        foreach ($types as &$table){
            $table =  strtoupper('pur_'.$table);
        }


        $real_query = ['is_show'=>1];
        isset($query['supplier_code']) AND $real_query['ext'] = $query['supplier_code'];
        isset($query['content']) AND $real_query['content'] = $query['content'];
        isset($query['user']) AND $real_query['operator'] = $query['user'];

        // 分页查询
        $limit = $offset = null;
        if(isset($query['page']) or isset($query['limit'])){
            $page   = (empty($query['page']) or intval($query['page']) < 1) ? 1 : intval($query['page']);
            $limit  = (empty($query['limit']) or intval($query['limit']) < 1) ? 50 : intval($query['limit']);
            $offset = ($page - 1) * $limit;
        }

        $this->purchase_db->where($real_query);
        $this->purchase_db->where_in('record_type',$types);
        $db = clone $this->purchase_db;
        $total_count = $db->from($this->table_name)->count_all_results();

        $this->purchase_db->from($this->table_name);
        $this->purchase_db->select('id,content,content_detail,operator,operate_time');
        $this->purchase_db->order_by("operate_time", "desc");
        $results = $this->purchase_db->get('',$limit,$offset)->result_array();

        return [
            'data_list'=>[
                'list'=>$results ? $results:array(),
                'count'=>$total_count
            ]
        ];
    }



    public function get_operator_log_survey($params,$limit,$offset){
        $this->purchase_db->select('*')->from($this->table_name);

        if(isset($params['record_type']) && !empty($params['record_type'])){
            $this->purchase_db->where('record_type',$params['record_type']);
        }

        if(isset($params['operator']) && !empty($params['operator'])){
            $this->purchase_db->where('operator',$params['operator']);
        }

        if(isset($params['operate_time_start']) && !empty($params['operate_time_start'])){
            $this->purchase_db->where('operate_time>=',$params['operate_time_start']);
        }

        if(isset($params['operate_time_end']) && !empty($params['operate_time_end'])){
            $this->purchase_db->where('operate_time<=',$params['operate_time_end']);
        }

        if(isset($params['operate_ip']) && !empty($params['operate_ip'])){
            $this->purchase_db->where('operate_ip',$params['operate_ip']);
        }
        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->limit($limit,$offset)->count_all_results();//符合当前查询条件的总记录数
        $this->purchase_db=$clone_db;
        $result=$this->purchase_db->order_by('operate_time','DESC')->limit($limit,$offset)->get()->result_array();
        return [
            'data_list' => $result,
            'page_data' => [
                'total'     => $total,
                'offset'    => $offset,
                'limit'     => $limit,
                'pages'     => ceil($total/$limit)
            ]
        ];
    }

    public function delete_log($id){
        if(!empty($id)){
            $result=$this->purchase_db->where_in('id',$id)->delete($this->table_name);
            if($result){
                return true;
            }else{
                return false;
            }
        }
    }

    public function empty_log(){
        return false;// 极度危险的操作，不允许，Jolon

        $result=$this->purchase_db->delete($this->table_name);
        if($result){
            return true;
        }else{
            return false;
        }
    }
}