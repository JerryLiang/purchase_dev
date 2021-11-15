<?php
class Log_strategy{
    private $_strategy = null;

    private $configs = '';
    private $config_file = 'mongo_log_system.php';

    private $collection_prefix = '';

    public function __construct($type)
    {
        $type = ucfirst($type.'_log');
        $logAbstract = new  $type;
        $this->_strategy = $logAbstract;
        $filePath = APPPATH.'config'.DIRECTORY_SEPARATOR.$this->config_file;
        $this->configs = require(APPPATH.'config'.DIRECTORY_SEPARATOR.$this->config_file);
    }


    public function checkByToken($token){
        $system_confgis = array_flip($this->configs['cloud_systems']);
        $token_list = array_keys($system_confgis);
        if(in_array($token,$token_list)){
            $this->collection_prefix = $system_confgis[$token];
            return true;
        }else{
            return false;
        }
    }



    public function addLog($collection = '',$data =[]){
        return $this->_strategy->addLog($this->collection_prefix.'_'.$collection,$data);
    }

    public function listLog($collection = '',$where = [],$page = 1,$pageSize = 20){
        return $this->_strategy->listLog($this->collection_prefix.'_'.$collection,$where,$page,$pageSize);
    }

    public function getTotal($collection ='',$where = []){
        return $this->_strategy->getTotal($this->collection_prefix.'_'.$collection,$where);
    }


}