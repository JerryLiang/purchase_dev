<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Statistics\Web;

use Workerman\Worker;
use Workerman\Protocols\Http\Response;

class MainIndex extends Worker
{

    function __construct($socket_name)
    {
        require_once __DIR__ . '/_init.php';
        parent::__construct($socket_name);
        $this->onMessage = array($this, 'onMessage');
    }

    /**
     * @param $connection
     * @param $recv_buffer //该进程返回值
     *
     */
    public function onMessage($connection, $recv_buffer)
    {
        $req_data= json_decode($recv_buffer,true);
        // 检查是否登录
        check_auth();
        $func = !empty($req_data['fn']) ? $req_data['fn'] :'main';
        $func = "\\Statistics\\Modules\\" . $func;
        if (!function_exists($func)) {
            foreach (glob(ST_ROOT . "/Modules/*") as $php_file) {
                require_once $php_file;
            }
        }

        if (!function_exists($func)) {
            $func = "\\Statistics\\Modules\\main";
//            require_once ST_ROOT."/Modules/main.php";
        }
        $module = isset($req_data['module']) ?? '';
        $interface = isset($req_data['interface']) ?? '';
        $date = isset($req_data['date']) ?? date('Y-m-d');
        $start_time = isset($req_data['start_time']) ?? strtotime(date('Y-m-d'));
        $offset = isset($req_data['offset']) ?? 0;
        $log_count_per_ip = $log_count_per_page = 40;
        if (empty($req_data['count']) && $ip_count = count(\Statistics\Lib\Cache::$ServerIpList)) {
            $log_count_per_ip = ceil($log_count_per_page / $ip_count);
        }

//        $response = new Response(200, [
//            'Content-Type' => 'text/html',
//            'X-Header-One' => 'Header Value'
//        ], call_user_func_array($func, array($module, $interface, $date, $start_time, $offset, $log_count_per_ip)));
        call_user_func_array($func, array($module, $interface, $date, $start_time, $offset, $log_count_per_ip));
//        $response = $func($module, $interface, $date, $start_time, $offset, $log_count_per_ip);
//        echo $response;exit;
//        $connection->send($response);

    }
}

