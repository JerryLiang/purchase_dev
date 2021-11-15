<?php
set_time_limit(0);
date_default_timezone_set('PRC');
header("Content-type:text/html;charset=utf-8");

// 相关配置
defined('CG_SYSTEM_APP_DAL_IP')     OR define('CG_SYSTEM_APP_DAL_IP', 'http://www.new_pur_end.net/');// 采购系统后端IP
defined('CEBBANK_DOWNLOADS_FILEPATH') OR define('CEBBANK_DOWNLOADS_FILEPATH', "D:\\workspace\\cebbank\\downloads");// 文件夹路径
defined('CEBBANK_DOWNLOADS_FILEPATH_BAK') OR define('CEBBANK_DOWNLOADS_FILEPATH_BAK', "D:\\workspace\\cebbank\\downloads_bak");// 文件夹路径（备份）
defined("FILE_UPLOAD_URL")          OR define("FILE_UPLOAD_URL",CG_SYSTEM_APP_DAL_IP.'ceb_bank/test_file_upload');

if(!file_exists(CEBBANK_DOWNLOADS_FILEPATH_BAK)){
    mkdir(CEBBANK_DOWNLOADS_FILEPATH_BAK,0777,true);
}

saveCebbankOperatorLog("");
saveCebbankOperatorLog("");
saveCebbankOperatorLog("");
saveCebbankOperatorLog("开始同步文件");
echo "<pre>";
echo "同步文件到JAVA服务器:<br/>";
echo "采购服务器IP：".CG_SYSTEM_APP_DAL_IP."<br/>";
echo "文件夹路径：<br>".CEBBANK_DOWNLOADS_FILEPATH."<br/>";

echo "待处理的文件列表：<br>";
$fileList = readAllFile(CEBBANK_DOWNLOADS_FILEPATH,'pdf',true,true,true);
print_r($fileList);

echo "<br/><br/>";
if(empty($fileList)){
    $log_msg = "没有待同步的数据";
    saveCebbankOperatorLog($log_msg);
    echo $log_msg;exit;
}

foreach($fileList as $file_name){
    $log_msg = $file_name;
    saveCebbankOperatorLog($log_msg);
    echo $log_msg."<br>";
    $result = upload_picture($file_name);

    if(!is_json($result)){
        $log_msg = "文件传输失败，返回不是JSON格式：".var_dump($result);
        saveCebbankOperatorLog($log_msg);
        echo $log_msg."<br/>";
        continue;
    }

    $result = json_decode($result,true);
    if(!isset($result['status']) or $result['status'] != 200){
        $log_msg = "文件传输失败，返回不是JSON格式：".json_encode($result,JSON_UNESCAPED_UNICODE);
        saveCebbankOperatorLog($log_msg);
        echo $log_msg."<br/>";
        continue;
    }else{// 文件同步 采购系统成功
        preg_match('/[\d]{4}-[\d]{1,2}-[\d]{1,2}/',$file_name,$matchs);
        $file_date = isset($matchs['0'])?$matchs['0']:'0000-00-00';
        $dis_file_path = CEBBANK_DOWNLOADS_FILEPATH_BAK.'\\'.$file_date;

        if(!file_exists($dis_file_path)){
            mkdir($dis_file_path,0777,true);
        }
        if(!file_exists($dis_file_path)){
            $log_msg = "文件同步采购系统失败：备份文件夹创建失败";
            saveCebbankOperatorLog($log_msg);
            echo $log_msg.'<br/>';
            continue;
        }else{
            rename($file_name, $dis_file_path.'\\'.basename($file_name));// 重命名移动文件夹

            $log_msg = "文件同步采购系统成功";
            saveCebbankOperatorLog($log_msg);
            echo $log_msg."<br/>";
            continue;
        }
    }
}

saveCebbankOperatorLog("同步完成");
echo "同步完成";exit;


// 以下是相关辅助方法
function saveCebbankOperatorLog($message){
    $log_file = date('Ymd').'.txt';
    $log_time = date('Y-m-d H:i:s');
    file_put_contents(CEBBANK_DOWNLOADS_FILEPATH_BAK.'\\'.$log_file,$log_time.' => '.$message.PHP_EOL,FILE_APPEND);
}

/**
 * 验证json的合法性
 * @param $string
 * @return bool
 */
function is_json($string)
{
    if(is_string($string)){
        json_decode($string);
        $flag = json_last_error() == JSON_ERROR_NONE;
    }else{
        $flag = false;
    }
    return $flag;
}


/**
 * 图片上传接口
 * @param  $path 图片本地路径
 * @author harvin 2019-4-2
 */
function upload_picture($path,$fileName=''){
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_SAFE_UPLOAD, true);
    $data = array('file' => new \CURLFile(realpath($path), $mime = '', $fileName)); //>=5.5
    curl_setopt($curl, CURLOPT_URL, FILE_UPLOAD_URL);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_USERAGENT, "TEST");
    $result = curl_exec($curl);
    $error = curl_error($curl);
    return $result;
}

/**
 * 获取文件夹下所有文件
 * @param string            $fileDir        目标文件夹路径
 * @param array|string      $fileExt        文件类型（默认空，返回所有）
 * @param bool              $isRecursion    是否递归读取子文件夹（默认使用递归）
 * @param bool              $isRealPath     是否返回真实路径
 * @param bool              $onlyFile       是否只是查找文件（默认所有）
 * @return array|bool
 */
function readAllFile($fileDir,$fileExt = '',$isRecursion = true,$isRealPath = true,$onlyFile = false){
    if (!is_dir($fileDir)) return false;

    static  $fileList   = [];

    $handle     = opendir($fileDir);

    if ($handle) {
        while (($nowFile = readdir($handle)) !== false) {
            $temp = $fileDir . DIRECTORY_SEPARATOR . $nowFile;// 文件或文件夹路径

            // 是否读取子文件夹
            if (is_dir($temp) AND $nowFile != '.' AND $nowFile != '..' ) {
                if($onlyFile === false){// 是否返回文件夹
                    if($isRealPath){
                        $fileList[] = $temp;// 返回的是绝对路径
                    }else{
                        $fileList[] = $nowFile;// 返回的是文件名
                    }
                }

                if($isRecursion){// 执行递归
                    readAllFile($temp,$fileExt,$isRecursion,$isRealPath,$onlyFile);
                }
            } else {
                if ($nowFile != '.' AND $nowFile != '..') {
                    if(!empty($fileExt)){// 判断是否是指定的格式的文件
                        if(strrpos($nowFile,'.') === false ) continue;// 指定了文件格式，跳过无格式的文件

                        // 判断文件后缀
                        $suffix = substr($nowFile,strrpos($nowFile,'.') + 1);
                        if(is_array($fileExt)  AND !in_array($suffix,$fileExt)) continue;
                        if(is_string($fileExt) AND $suffix != $fileExt) continue;
                    }

                    if($isRealPath){
                        $fileList[] = $temp;// 返回的是绝对路径
                    }else{
                        $fileList[] = $nowFile;// 返回的是文件名
                    }

                }
            }
        }
    }

    return $fileList;
}

?>
