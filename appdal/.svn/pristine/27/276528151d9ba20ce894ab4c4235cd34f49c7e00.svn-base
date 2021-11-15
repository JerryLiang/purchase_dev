<?php

/**
 * Created by PhpStorm.
 * 采购单支付信息日志
 * User: Jackson
 * Date: 2019/02/19
 */
class ufxfuiou_system_log_model extends Purchase_model
{

    protected $table_name = 'ufxfuiou_system_log';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @desc 记录相关日志信息
     * @author Jackson
     * @param array $object
     * @return void()
     */
    public function save_ufxfuiou_error($object)
    {
        try {
            $data = (object)array();
            $data->pur_tran_no = $object->pur_tran_num;
            $data->contents = $object->contents;
            $data->type = 1;
            $data->create_time = date('Y-m-d H:i:s', time());
            $data->create_user_name = $object->userName;
            return $this->insert((array)$data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

}
