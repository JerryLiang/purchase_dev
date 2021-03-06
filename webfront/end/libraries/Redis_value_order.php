<?php

/**
 * Redis 获取指定键的内容
 * @author Harvin
 * @param string $type
 * @return array|mixed
 */
class Redis_value_order{

    private $_data;

    /**
     * 获取配置值
     * @param string $data_type
     * @return mixed
     */
    public function redis_set($data_type = 'STATUS'){
        if(!empty($this->_data)) return $this->_data;

        $CI = &get_instance();
        $CI->load->library('rediss');
        $reids = $CI->rediss;
        $this->_data  = $reids->getData($data_type);
        if(empty($this->_data)){
            die('Redis获取相关数据失败');
        }
        return $this->_data;
    }
}