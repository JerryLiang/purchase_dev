<?php
/**
 * Created by PhpStorm.
 * 单据总个数计数  前缀记录
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Prefix_number_model extends Purchase_model{
    protected $table_name = 'prefix_number';

    private $_fixed_length = 6;// 固定长度（不足的左边补 0）

    public function __construct(){
        parent::__construct();

    }

    /**
     * 查询指定 前缀的 记录
     * @author Jolon
     * @param string $order_prefix  前缀名
     * @return mixed
     */
    public function get_prefix($order_prefix){
        $where = ['prefix' => $order_prefix];

        $this->purchase_db->where($where);
        $row = $this->purchase_db->get($this->table_name)->row();

        return $row;
    }

    /**
     * 拼接指定格式的 前缀
     * @param $prefix
     * @param $number
     * @param $fixed_length
     * @return string
     */
    public function joinNumberStr($prefix, $number, $fixed_length){
        return $prefix.str_pad($number, !empty($fixed_length) ? $fixed_length : $this->_fixed_length, "0", STR_PAD_LEFT);// 编号不足长度 左边自动补0
    }

    /**
     * 生成 指定前缀的 最新编号（自动更新编号记录）
     * @author Jolon
     * @param string    $order_prefix   前缀
     * @param int       $add_number     增量（默认 1）
     * @param int       $fixed_length   编号长度（默认 6，用来填充）
     * @return bool|string
     */
    public function get_prefix_new_number($order_prefix,$add_number = 1,$fixed_length = 6){
        $operator_key    = strtoupper('get_prefix_new_number_'.$order_prefix);
        $existsKeyNumber = $this->rediss->getData($operator_key);// 命令用于获取指定 key 的值。如果 key 不存在，返回 nil 。如果key 储存的值不是字符串类型，返回一个错误

        if(empty($existsKeyNumber)){
            $result = $this->update_prefix($order_prefix,$add_number);// 自动更新或增加前缀的计数
            if(empty($result)){
                return false;
            }else{
                $row = $this->get_prefix($order_prefix);// 获取更新后的数据
                $number_int_value = $row->number;
                $number = $this->joinNumberStr($order_prefix,$number_int_value,$fixed_length);// 编号不足长度 左边自动补0

                $this->rediss->setData($operator_key,$number_int_value);// 只是存储数字
            }
        }else{
            $this->rediss->incrByData($operator_key,$add_number);// 命令将 key 中储存的数字加上指定的增量值
            $number_int_value = $this->rediss->getData($operator_key);
            $number = $this->joinNumberStr($order_prefix,$number_int_value,$fixed_length);// 编号不足长度 左边自动补0

            // 更新到数据库
            $this->purchase_db->set(['number' => $number_int_value, 'date' => date('Y-m-d')])
                ->where('prefix', $order_prefix)
                ->update($this->table_name);
        }

        return $number;
    }

    /**
     * 增加 指定前缀的 计数值（没有该前缀的记录则自动插入）
     * @author Jolon
     * @param string    $order_prefix   前缀
     * @param int       $add_number     增量（默认 1）
     * @return mixed
     */
    public function update_prefix($order_prefix,$add_number = 1){
        $row = $this->get_prefix($order_prefix);
        if(empty($row)){// 不存在记录则新增记录
            $result = $this->insert_prefix_record($order_prefix,$add_number);// 插入前缀记录
        }else{// 存在记录则获取之前的 编号再增加指定值
            $update_data = [
                'number' => $row->number + $add_number,
                'date'   => date('Y-m-d'),
            ];
            $this->purchase_db->set($update_data);
            $this->purchase_db->where('prefix', $order_prefix);
            $result = $this->purchase_db->update($this->table_name);

        }

        return $result;
    }

    /**
     * 插入指定前缀的记录
     * @author Jolon
     * @param string    $order_prefix   前缀名称
     * @param int       $begin_number   起始值（默认 1）
     * @param string    $note           备注
     * @return mixed
     */
    public function insert_prefix_record($order_prefix,$begin_number = 1,$note = ''){

        $insert_data = [
            'prefix' => $order_prefix,
            'number' => intval($begin_number),
            'date'   => date('Y-m-d'),
            'note'   => $note
        ];

        return $this->purchase_db->insert($this->table_name,$insert_data);
    }

}