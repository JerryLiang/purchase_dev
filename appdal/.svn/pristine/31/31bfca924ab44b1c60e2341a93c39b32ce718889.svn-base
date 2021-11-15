<?php
/**
 * Created by PhpStorm.
 * 数据表内容变更 保存
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Tables_change_model extends Purchase_model{
    protected $table_name = 'tables_change_log';

    protected $change_type = ['1','2','3'];// 操作类型（1.插入,2.更新,3.删除）

    public function __construct(){
        parent::__construct();
        $this->load->helper('url');
    }


    /**
     * 添加 数据表变动 日志（所有字段皆是选填）
     * @author Jolon
     * @param array $data   要保存的数据
     * @return bool  true.成功,false.失败
     *
     * @example
     *      $data = array(
     *          record_number   => 目标记录编号（int|string）
     *          table_name      => 操作的表名称
     *          change_type     => 操作类型（1.插入,2.更新,3.删除）
     *          content         => 改变的内容（详细信息，保存为 serialize 处理的结果）
     *          user            => 操作人
     *          time            => 操作时间（exp.2018-12-27 16:16:16  默认当前时间）
     *          ip              => 操作IP
     *          route           => 操作路由
     *      )
     */
    public function insert_one_log($data){
        $change_content = isset($data['content'])?$data['content']:'';
        if(!is_string($change_content)){
            // 详细信息转换  JSON_UNESCAPED_UNICODE.不转中文
            $change_content = json_encode($change_content,JSON_UNESCAPED_UNICODE);
        }

        $insert_data = [
            'record_number'  => isset($data['record_number']) ? $data['record_number'] : '',
            'table_name'     => isset($data['table_name']) ? $data['table_name'] : '',
            'change_type'    => (isset($data['change_type']) and in_array($data['change_type'],$this->change_type)) ? $data['change_type'] : '1',
            'change_content' => $change_content,// 详细信息转换  JSON_UNESCAPED_UNICODE.不转中文
            'operate_ip'     => isset($data['ip']) ? $data['ip'] : getActiveUserIp(),
            'operate_route'  => isset($data['route']) ? $data['route'] : uri_string(),
            'create_user'    => isset($data['user']) ? $data['user'] : getActiveUserName(),
            'create_time'    => isset($data['time']) ? $data['time'] : date('Y-m-d H:i:s', time()),
        ];

        return $this->purchase_db->insert($this->table_name,$insert_data);
    }


    /**
     * 获取 数据变更的内容
     * @author Jolon
     * @param array $old_data   原数据
     * @param array $new_data   新数据
     * @return array|bool  变更的数据的键值对
     */
    public function get_update_data($new_data,$old_data){
        if(empty($old_data) or empty($new_data)) return false;

        // 对象则转换为数组比较
        if(is_object($old_data)) $old_data = json_decode(json_encode($old_data),true);
        if(is_object($new_data)) $new_data = json_decode(json_encode($new_data),true);

        $update_data = [];
        foreach ($new_data as $field => $value) {
            $from_value = isset($old_data[$field])?$old_data[$field]:'';

            if($from_value == $value) continue;

            $update_data[$field] = $from_value .'->'.$value;
        }

        return $update_data;
    }

}