<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/12/4
 * Time: 10:50
 */
class Return_process_log_model extends Purchase_model
{

    protected $table_name = 'return_process_log';//流程日志表

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 通过子id查询日志
     * @author Manson
     */
    public function get_log_by_part_number($part_number)
    {
        if (empty($part_number)){
            return [];
        }
        $result = $this->purchase_db->select('*')
            ->where('part_number',$part_number)
            ->order_by('id desc')
            ->get($this->table_name)->result_array();
        return $result;
    }
}