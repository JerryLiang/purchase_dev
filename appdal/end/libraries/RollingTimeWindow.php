<?php

/**
 * Created by PhpStorm.
 * Desc：基于 Redis 实现的滑动时间窗口限流算法
 * User: Jolon
 * Date: 2021/5/13
 * Time: 上午9:22
 */
class RollingTimeWindow {

    protected $_minimum_time_range_size = 1;// 最小限制范围精度1秒钟
    protected $_minimum_time_range_qps  = 5;// 最小范围精度对应的时间内允许的请求数量
    protected $_maximum_qps_one_hour    = 3000;// 一小时范围内允许的请求数量

    protected $rediss = null;// 传递的rediss 实例

    public function __construct(){}

    /**
     * 获取 _minimum_time_range_size
     * @return int
     */
    public function getMinimumTimeRangeSize(){
        return $this->_minimum_time_range_size;
    }

    /**
     * 设置 _minimum_time_range_size
     * @param $size
     */
    public function setMinimumTimeRangeSize($size){
        if($size <= 0) $size = 1;
        $this->_minimum_time_range_size = $size;
    }

    /**
     * 获取 _minimum_time_range_qps
     * @return int
     */
    public function getMinimumTimeRangeQps(){
        return $this->_minimum_time_range_qps;
    }

    /**
     * 设置 _minimum_time_range_qps
     * @param $qps
     */
    public function setMinimumTimeRangeQps($qps){
        if($qps <= 0) $qps = 1;
        $this->_minimum_time_range_qps = $qps;
    }

    /**
     * 获取 _maximum_qps_one_hour
     * @return int
     */
    public function getMaximumQpsOneHour(){
        return $this->_maximum_qps_one_hour;
    }

    /**
     * 设置 _maximum_qps_one_hour
     * @param $qps
     */
    public function setMaximumQpsOneHour($qps){
        if($qps <= 0) $qps = 1;
        $this->_maximum_qps_one_hour = $qps;
    }


    /**
     * 返回当前的毫秒时间戳
     * @return float
     */
    public static function msectime() {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }

    /**
     * 根据 当前的毫秒时间戳 和 随机数组合生成一个 准唯一的ID（简单算法）
     * @return string
     */
    public static  function createMemberByTime(){
        return self::msectime().'_'.mt_rand(10000,99999);
    }


    /**
     * 判断 指定的用户是否需要限流
     *      可控制 _minimum_time_range_size 时间范围内允许 _minimum_time_range_qps 个请求数
     *      可控制 每小时 时间范围内允许 _maximum_qps_one_hour 个请求数
     *
     * @param object $rediss    $this->load->library('rediss') 实例化的对象 $this->>rediss
     * @param string $key       用户缓存请求的KEY（要求全局唯一）
     * @return array
     */
    public function grant($rediss,$key){
        $this->rediss = $rediss;// 需传递Redis实例，这里不再引入了

        //$member     = self::createMemberByTime();// 生成唯一ID
        $member     = uniqid();// 基于微秒生成唯一ID
        $time       = time();
        $message    = '';
        $flagGrant  = false;

        // _minimum_time_range_size  _minimum_time_range_qps 是按1秒钟时间设置的，这里放大十倍 按10秒钟计算
        $size_count = $this->rediss->zset_count($key,$time - $this->_minimum_time_range_size * 10,$time);// 集合中最小粒度范围的成员数量
        if($size_count <= $this->_minimum_time_range_qps * 10){// 每小时范围的成员数量
            $hour_count = $this->rediss->zset_count($key,$time - 3600,$time);// 获取集合中的数量
            if($hour_count <= $this->_maximum_qps_one_hour){
                $this->rediss->zset_add($key,$time,$member);// 每次请求插入一个数据到有序集合
                $flagGrant = true;
            }else{
                $message = 'Exceeded requests per hour';
            }
        }else{
            $message = 'Requests per minute exceeded';
        }

        $this->rediss->zset_remrangebyscore($key,0,$time - 3600);// 删除一小时前插入的成员（清除历史数据）

        return [$flagGrant,$message];
    }




}