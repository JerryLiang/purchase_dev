<?php


namespace EasySwoole\EasySwoole;


use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\ORM\Db\Config as OrmConf;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');
        $configData = new OrmConf(Config::getInstance()->getConf('MYSQL'));
        DbManager::getInstance()->addConnection(new Connection($configData));   
    }

    public static function mainServerCreate(EventRegister $register)
    {
        $register->add($register::onWorkerStart,function (){
            // 链接预热
            // ORM 1.4.31 版本之前请使用 getClientPool() 
            // DbManager::getInstance()->getConnection()->getClientPool()->keepMin();

            DbManager::getInstance()->getConnection()->__getClientPool()->keepMin();
        });
    }
}