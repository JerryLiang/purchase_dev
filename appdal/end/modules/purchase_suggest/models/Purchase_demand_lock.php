<?php
/**
 * Created by PhpStorm.
 * 备货单模块一键转换备货单合并需求单进程锁
 * User: luxu
 * Date: 2021年3月5号
 */

class Purchase_demand_lock extends Purchase_model {
    private $lock_value = "lock"; // 定义分布式锁值
    private $lock_keys =  "get_lock"; // 定义分布式KEY值
    public  $lock_id =  NULL; // 进程锁表示
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 获取REDIS进程锁,并且设置进程锁,需求单点击操作定时脚本每次操作就不用循环获取锁。获取一次就可以了
     * @params   $key      string   REDIS 进程锁的KEY值
     *           $value    string|NULL   设置锁的KEY 值
     * @author:luxu
     * @time:2021年3月5号
     **/
    private function _pull_set_lock($key=NULL,$value=NULL){
        if( NULL == $key ){
            $key = $this->lock_keys; // 如果调用者没有传入KEY值就用默认的KEY
        }

        if( NULL == $value ){
            $value = $this->lock_value; // 如果调用者没有传入VALUE 值就用默认的VALUE
        }

        // 加锁成功设置进程锁标识
        $this->lock_id = $key."_".date("Y-m-d H:i:s",time())."_".rand(0,100);
        $value = $value."|".$this->lock_id;
        // 获取REDIS 中进程锁
        $redisLockKey = $this->rediss->getData($key);
        // 如果REDIS KEY 为空就表示没有锁
        if(empty($redisLockKey)){
            // 设置锁
            $setRedisLock = $this->rediss->call_setnx($key,$value,300);
            if(False == $setRedisLock){
                return False;
            }
        }else{
            // 如果REDIS KEY 不为空的话就标识锁存在
            return False;
        }
        return True;
    }

    /**
     * 删除REDIS 进程锁。（私有方法）
     * @params $key   string  REDIS 锁KEY 值
     *         $lock_flag  string  REDIS 进程锁标识
     * @author:luxu
     * @time:2021年3月5号
     **/

    private function _unlock($key = NULL,$lock_flag = NULL){

        if( NULL == $key){

            $key = $this->lock_keys;
        }
        $redisLockKey = $this->rediss->getData($key);
        //如果REDIS 进程锁KEY 为空就说明KEY 已经 超时，直接返回解锁成功
        if(empty($redisLockKey)){

            return True;
        }
        if(NULL == $lock_flag){
            $lock_flag = $this->lock_id;
        }
        $keyValues = explode("|",$redisLockKey);
        if(isset($keyValues[1]) && $keyValues[1] == $lock_flag){

            // 如果和REDIS 进程锁的标识相同，说明解锁的是自己的锁
            $this->rediss->deleteData($key);
        }
        // 如果REDIS 进程锁的标识和解锁的标识不一致的。说明REDIS 锁标识超时，并且其他进程已经获取了锁。那么就不用解锁直接返回成功
        return True;
    }

    /**
     * 获取KEY的进程标识
     * @author:luxu
     * @time:2021年3月5号
     **/
    public function get_lock_id(){
        return $this->lock_id;
    }

    /**
     * 删除REDIS 进程锁。(公开方法)
     * @params $key   string  REDIS 锁KEY 值
     *         $lock_flag  string  REDIS 进程锁标识
     * @author:luxu
     * @time:2021年3月5号
     **/
    public function unlock($key=NULL,$lock_flag=NULL){
        return $this->_unlock($key,$lock_flag);
    }

    /**
     * 设置REDIS 进程锁
     * @params   $key      string   REDIS 进程锁的KEY值
     *           $value    string|NULL   设置锁的KEY 值
     * @author:luxu
     * @time:2021年3月5号
     **/
    public function pull_set_lock($key = NULL,$value=NULL){
        return $this->_pull_set_lock();
    }
    /**
     * 判断REDIS 锁是否存在
     * @author:luxu
     * @time:2021年8月28号
     **/
    public function get_redis_lock_exists($keys = NULL){
        if( NULL == $keys ){
            $keys = $this->lock_keys;
        }
        $redisLockKey = $this->rediss->getData($keys);
        if(!empty($redisLockKey)){
            return True;
        }
        return False;
    }
}