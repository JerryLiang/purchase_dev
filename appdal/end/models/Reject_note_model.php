<?php

/**
 * Created by PhpStorm.
 * 操作日志记录类
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */
class Reject_note_model extends Purchase_model {

    protected $table_name = 'reject_note';
    protected $table_name_log = 'purchase_cancel_log  ';
    public function __construct() {
        parent::__construct();  
    }

    /**
     * 添加 用户审核记录、驳回备注 日志
     * @author Jolon
     * @param array $data   要保存的数据
     * @return bool  true.成功,false.失败
     *
     * @example
     *      $data = array(
     *          user_id             => 驳回操作人ID
     *          user_name           => 驳回操作人名称
     *          reject_type_id      => 驳回类型ID,1是需求驳回,2是采购单驳回等
     *          link_id             => 涉及到的主键ID
     *          link_code           => 涉及到的编码,例如采购单号，需求单号等
     *          reject_remark       => 原因备注
     *          reject_time         => 操作时间（exp.2018-11-23 16:16:16  默认当前时间）
     *      )
     */
    public function insert_one_note($data) {
        $insert_data = [
            'user_id' => isset($data['user_id']) ? $data['user_id'] : getActiveUserId(),
            'user_name' => isset($data['user_name']) ? $data['user_name'] : getActiveUserName(),
            'reiect_dismissed' => isset($data['reiect_dismissed']) ? $data['reiect_dismissed'] : '',
            'reject_type_id' => isset($data['reject_type_id']) ? $data['reject_type_id'] : '',
            'link_id' => isset($data['link_id']) ? $data['link_id'] : '', // 详细信息转换
            'link_code' => isset($data['link_code']) ? $data['link_code'] : '',
            'reject_remark' => isset($data['reject_remark']) ? $data['reject_remark'] : '',
            'reject_time' => isset($data['reject_time']) ? $data['reject_time'] : date('Y-m-d H:i:s', time()),
            'create_user_name' => isset($data['user_name']) ? $data['user_name'] : getActiveUserName(),
            'create_time' => isset($data['create_time']) ? $data['create_time'] : date('Y-m-d H:i:s', time()),
        ];

        $this->purchase_db->insert($this->table_name, $insert_data);
        return $this->purchase_db->insert_id();
    }

    /**
     * 查询用户审核记录、驳回备注 日志
     * @author Jolon
     * @param array $query  查询条件
     * @param bool $is_only  只查询最新一条
     * @return bool|array   array.结果集，false.查询条件缺失     *
     *
     * @example
     *      $query = array(
     *          user_id             => 驳回操作人ID
     *          user_name           => 驳回操作人名称
     *          reject_type_id      => 驳回类型ID,1是需求驳回,2是采购单驳回等
     *          link_id             => 涉及到的主键ID
     *          link_code           => 涉及到的编码,例如采购单号，需求单号等
     *     )
     */
    public function query_logs($query,$is_only = false) {
        $real_query = [];
        isset($query['user_id']) AND $real_query['user_id'] = $query['user_id'];
        isset($query['user_name']) AND $real_query['user_name'] = $query['user_name'];
        isset($query['reject_type_id']) AND $real_query['reject_type_id'] = $query['reject_type_id'];
        isset($query['link_id']) AND $real_query['link_id'] = $query['link_id'];
        isset($query['link_code']) AND $real_query['link_code'] = $query['link_code'];

        if (empty($real_query))
            return false; // 查询条件缺失

        $this->purchase_db->where($real_query);
        if($is_only){
            $results = $this->purchase_db->order_by('id desc')->get($this->table_name)->row_array();
        }else{
            $results = $this->purchase_db->order_by('id desc')->get($this->table_name)->result_array();
        }

        return $results ? $results : array();
    }

    /**
     * 记录系统操作日志
     * @param array $data <2019-1-4>
     * @author harvin  <2019-1-4>
     **/
    public function get_insert_log($data) {

        $insert_data = [
            'record_number' => $data['record_number'],
            'record_type' => $data['record_type'],
            'content' => $data['content'],
            'content_detail' => $data['content_detail'],
            'operate_ip' => $this->getIp(),
            'operate_route' => $this->router->fetch_class() . '/' . $this->router->fetch_method(),
            'operator' => !empty(getActiveUserName())?getActiveUserName():"system",
            'operate_time' => date('Y-m-d H:i:s'),
            'is_show' => 1
        ];
       
      $this->purchase_db->insert('operator_log', $insert_data);
       
    }
    /**
     * 记录取消未到货操作日志
     * @author harvin
     * @date 2019-06-26
     */
    public function cancel_log($data){
           $insert_data = [
            'cancel_id' => $data['cancel_id'],
            'create_id' => getActiveUserId(),
            'create_user' => !empty(getActiveUserName())?getActiveUserName():"system",
            'operation_type' => $data['operation_type'],
            'operation_content' => $data['operation_content'],
            'create_time' => date('Y-m-d H:i:s'),   
        ];
        $this->purchase_db->insert($this->table_name_log,$insert_data);
    }
    /**
     * 采购需求的审核记录，根据采购的id集合批量获取，避免在循环中单条查找
     * 每条采购记录只获取最新的单条审核日志
     * @author liwuxue
     * @date 2019/2/14 11:08
     * @param $link_ids
     * @param $filed
     * @return mixed
     * @throws Exception
     */
    public function get_newest_purchase_suggest_log(array $link_ids, $filed = "*")
    {
        $rows = $this->purchase_db->where("reject_type_id", 1)
            ->select($filed)
            ->where_in("link_id", $link_ids)
            ->order_by("id asc")
            ->get($this->table_name)
            ->result_array();
        $return = [];
        if (!empty($rows)) {
            $rows = array_column($rows, null, "id");
            //获取每个link_id对应的最大的id
            $id_arr = array_column($rows, "id", "link_id");
            foreach ($link_ids as $link_id) {
                $return[$link_id] = [];
                if (isset($id_arr[$link_id])) {
                    $return[$link_id] = isset($rows[$id_arr[$link_id]]) ? $rows[$id_arr[$link_id]] : [];
                }
            }
        }
        return $return;
    }

    /**
     * 根据条件获取数据
     * @author Jaden 2019-1-4
     **/
    public function getByWhereRejectNote($where,$filed = "*"){
        $rows = $this->purchase_db->select($filed)
            ->where($where)
            ->order_by("id desc")
            ->get($this->table_name)
            ->row_array();
        return $rows;    
    }




    /**
     * 获取客服端IP
     * @author harvin 2019-1-4
     **/
    protected function getIp() {
        if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown"))
            $ip = getenv("HTTP_CLIENT_IP");
        else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown"))
            $ip = getenv("HTTP_X_FORWARDED_FOR");
        else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown"))
            $ip = getenv("REMOTE_ADDR");
        else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown"))
            $ip = $_SERVER['REMOTE_ADDR'];
        else
            $ip = "unknown";
        return $ip;
    }

}
