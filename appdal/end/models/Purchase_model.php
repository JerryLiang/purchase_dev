<?php

/**
 * Created by PhpStorm.
 * 采购系统数据库操作模型 中间层
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */
class Purchase_model extends MY_Model
{

    protected $db_name = 'default';// 默认数据库名

    protected $table_name = '';// 数据表名称

    /** @var CI_DB_query_builder  */
    public $purchase_db = '';// 数据库实例化对象


    /**
     * Purchase_model constructor.
     * 自动连接 采购系统数据库
     * @author Jolon
     */
    public function __construct()
    {
        parent::__construct();

        $this->purchase_db = $this->_db;

//        if (is_object($this->database)) {
//            // CI DB Connection
//            $this->purchase_db = $this->database;
//        } elseif (is_string($this->database)) {
//            // Cache Mechanism
//            if (isset(self::$_dbCaches[$this->database])) {
//                $this->purchase_db = self::$_dbCaches[$this->database];
//            } else {
//                // CI Database Configuration
//                $this->purchase_db = $this->load->database($this->database, true);
//                self::$_dbCaches[$this->database] = $this->purchase_db;
//            }
//        } else {
//            // Config array for each Model
//            $this->purchase_db = $this->load->database($this->database, true);
//        }

    }

    /**
     * 数据表 字段和注释 对应关系
     * @author Jolon
     */
    public function table_columns()
    {
        return [];
    }

    /**
     * 数据表名称
     * @author Jolon
     */
    public function table_name(){
        return $this->table_name;
    }


    /**
     * 查询参数过滤
     * @author Jolon
     * @param array $params 参数
     * @param bool $skip 默认 false.验证字段,true.不验证字段
     * @return array
     */
    public function table_query_filter($params, $skip = true)
    {
        $params = filter_array_none($params);// 过滤为空的元素
        //$params = array_filter($params);// 过滤为空的元素 会把 0 过滤掉
        if ($skip === true or empty($this->table_columns())) return $params;

        // 取得数据表的有效字段
        $query_columns = array_intersect_key($params, $this->table_columns());
        return $query_columns;
    }

    /**
     * @Desc 查检统计数据是否存在如果存在则更新、反之插入
     * @authr jackson
     * @param $condition
     * @return string
     */
    public function checkDataExsit($condition = '')
    {
        return $this->findOne($condition, 'id');

    }

    /**
     * @Desc 获取更新时新旧值数据信息
     * @authr jackson
     * @param $updateBofore 更新前数据
     * @param $updateDatas 更新后数据
     * @param $filter 过滤字段
     * @return array
     */
    public function checkChangData($updateBofore = '', $updateDatas = '', $filter = array(''))
    {
        $changData = array();
        foreach ($updateBofore as $k => $items) {
            foreach ($items as $key => $item) {
                if (isset($updateDatas[$key]) && $updateDatas[$key] != $item && !in_array($key, $filter)) {
                    $changData[$items['id']]['new'][$key] = $updateDatas[$key];
                    $changData[$items['id']]['old'][$key] = $item;
                }
            }
        }
        return $changData;

    }

    /**
     * 执行查询sql
     * @author Manson
     * @param $quick_sql
     * @return mixed
     */
    public function query_quick_sql($quick_sql)
    {
        return $this->purchase_db->query($quick_sql)->result_array();
    }

    /**
     * 格式化返回数据
     * @author Jolon
     * @param bool      $res_code       true.操作成功，false.操作失败
     * @param string    $res_message    成功或失败的原因消息
     * @param mixed     $res_data       返回数据
     * @return array
     */
    protected function res_data($res_code,$res_message = '',$res_data = ''){
        $return = [
            'code' => $res_code,
            'message' => $res_message,
            'data' => $res_data,
        ];

        return $return;
    }

    public function get_microtime(){
        $a = microtime();
        $b = explode(" ", $a);
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }
}