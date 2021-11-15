<?php
//全局bootstrap事件

use Swoole\Coroutine\Scheduler;

date_default_timezone_set('Asia/Shanghai');

\EasySwoole\EasySwoole\Core::getInstance()->initialize();
$mysqlConfig = new \EasySwoole\ORM\Db\Config(\EasySwoole\EasySwoole\Config::getInstance()->getConf('MYSQL'));
//获取连接
$connection = new \EasySwoole\ORM\Db\Connection($mysqlConfig);
//注入mysql连接
\EasySwoole\Component\Di::getInstance()->set('CodeGeneration.connection',$connection);
//直接注入mysql配置对象
// \EasySwoole\Component\Di::getInstance()->set('CodeGeneration.connection',$mysqlConfig);
//直接注入mysql配置项
//\EasySwoole\Component\Di::getInstance()->set('CodeGeneration.connection',\EasySwoole\EasySwoole\Config::getInstance()->getConf('MYSQL'));

//注入执行目录项,后面的为默认值,initClass不能通过注入改变目录
\EasySwoole\Component\Di::getInstance()->set('CodeGeneration.modelBaseNameSpace',"App\\Model");
\EasySwoole\Component\Di::getInstance()->set('CodeGeneration.controllerBaseNameSpace',"App\\HttpController");
\EasySwoole\Component\Di::getInstance()->set('CodeGeneration.unitTestBaseNameSpace',"UnitTest");
\EasySwoole\Component\Di::getInstance()->set('CodeGeneration.rootPath',getcwd());

// $schedule = new Scheduler();
// $schedule->add(function(){

// });
// $schedule->start();