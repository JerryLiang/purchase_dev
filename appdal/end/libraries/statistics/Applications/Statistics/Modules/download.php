<?php

namespace Statistics\Modules;

use Statistics\Config;
// use Workerman\Protocols\Http\Response;

function download()
{
    $log_dir = 'statistic/log/';
    $type = isset($_GET['type']) ? $_GET['type'] : 'logger';
    $date = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");
    $err_msg = $notice_msg = $suc_msg = $ip_list_str = '';
    switch ($type) {
        case "logger":
            $log_file = Config::$dataPath . $log_dir . $date;
//            echo $log_file;
            if (file_exists($log_file)) {
       header("location:http://$log_file");
                // return $log_file;
//                header("Content-Disposition:attachment; filename=" . $log_file);
//                header("Content-Length:" . filesize($log_file));
               readfile($log_file);
            } else {
                header("HTTP/1.1 404 Not Found");
            }
            break;
        default:
            break;
    }

}