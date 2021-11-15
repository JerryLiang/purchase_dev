<?php
    abstract class LogAbstract{
        protected $mongo_db = null;
        protected $subfix = '_log';
        public function __construct()
        {
            $this->mongo_db = new Mongo_db();
        }

        //添加日志
        abstract function addLog($collection ='',$log =[]);
        //获取日志
        abstract function listLog($collection ='',$where = [],$page = 1,$pageSize = 20);

        public function addByMongo($collection = '',$log =[]){
            return $this->mongo_db->insert($collection.$this->subfix,$log);
        }

        public function getTotal($collection ='',$where = []){
            return $this->mongo_db->where($where)->count($collection.$this->subfix);
        }

        public function listByPage($collection = '',$where = [],$page = 1,$pageSize = 20){
            return $this->mongo_db->where($where)->offset($page)->limit($pageSize)->get($collection.$this->subfix);
        }
    }