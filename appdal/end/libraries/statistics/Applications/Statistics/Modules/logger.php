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

namespace Statistics\Modules;

function logger($module, $interface, $date, $start_time, $offset, $count)
{
    $module_str = '';
    $start_time = strtotime($date);
    foreach (\Statistics\Lib\Cache::$modulesDataCache as $mod => $interfaces) {
        if ($mod == 'WorkerMan') {
            continue;
        }
        $module_str .= '<li><a href="/?fn=statistic&module=' . $mod . '">' . $mod . '</a></li>';
        if ($module == $mod) {
            foreach ($interfaces as $if) {
                $module_str .= '<li>&nbsp;&nbsp;<a href="/?fn=statistic&module=' . $mod . '&interface=' . $if . '">' . $if . '</a></li>';
            }
        }
    }
    unset($_GET['start_time'], $_GET['end_time'], $_GET['date'], $_GET['fn'], $_GET['ip'], $_GET['offset']);
    $log_data_arr = getStasticLog($module, $interface, $start_time, $offset, $count);
//        var_dump($log_data_arr);
    $log_str = '';
    foreach ($log_data_arr as $address => $log_data) {
        list($ip, $port) = explode(':', $address);
        $log_str .= $log_data['data'];
        $_GET['ip'][] = $ip;
        $_GET['offset'][] = $log_data['offset'];
    }
    $log_str = nl2br(str_replace("\n", "\n\n", $log_str));

    $next_page_url = http_build_query($_GET);
    $log_str .= "</br><center><a href='/?fn=logger&date=$date&$next_page_url'>下一页</a></center>";
    /*******增加日志筛选功能******/
//    $query = http_build_query($_GET);
    $date_btn_str = '';
    for ($i = 13; $i >= 1; $i--) {
        $the_time = strtotime("-$i day");
        $the_date = date('Y-m-d', $the_time);
        $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
        $date_btn_str .= '<a href="/?fn=logger&date=' . "$the_date&$next_page_url" . '" class="btn ' . '" type="button">' . $html_the_date . '</a>';
        if ($i == 7) {
            $date_btn_str .= '</br>';
        }
        $date_btn_str .= $date == $the_date ? '<a href="/?fn=download&type=logger&date=' . $the_date . '">下载日志文件</a>' : '';
    }
    $the_date = date('Y-m-d');
    $html_the_date = $date == $the_date ? "<b>$the_date</b>" : $the_date;
    $date_btn_str .= '<a href="/?fn=logger&date=' . "$the_date&$next_page_url" . '" class="btn" type="button">' . $html_the_date . '</a>';
    $date_btn_str .= $date == $the_date ? '<a href="/?fn=download&type=logger&date=' . $the_date . '">下载日志文件</a>' : '';

//    $data =['log_str'=>$log_str,'date_btn_str'=>$date_btn_str];
//        return $data;
//    /*******增加日志筛选功能******/
    include ST_ROOT . '/Views/header.tpl.php';
    include ST_ROOT . '/Views/log.tpl.php';
    include ST_ROOT . '/Views/footer.tpl.php';
}

function getStasticLog($module, $interface, $start_time, $offset = '', $count = 10)
{
    $ip_list = (!empty($_GET['ip']) && is_array($_GET['ip'])) ? $_GET['ip'] : \Statistics\Lib\Cache::$ServerIpList;
    $offset_list = (!empty($_GET['offset']) && is_array($_GET['offset'])) ? $_GET['offset'] : array();
    $port = \Statistics\Config::$ProviderPort;
    $request_buffer_array = array();
    foreach ($ip_list as $key => $ip) {
        $offset = isset($offset_list[$key]) ? $offset_list[$key] : 0;
        $request_buffer_array["$ip:$port"] = json_encode(array('cmd' => 'get_log', 'module' => $module, 'interface' => $interface, 'start_time' => $start_time, 'offset' => $offset, 'count' => $count)) . "\n";
    }
    $read_buffer_array = multiRequest($request_buffer_array);
    krsort($read_buffer_array);
    foreach ($read_buffer_array as $address => $buf) {
        list($ip, $port) = explode(':', $address);
        $body_data = json_decode(trim($buf), true);
        $log_data = isset($body_data['data']) ? $body_data['data'] : '';
        $offset = isset($body_data['offset']) ? $body_data['offset'] : 0;
        $read_buffer_array[$address] = array('offset' => $offset, 'data' => $log_data);
    }
    return $read_buffer_array;
}
