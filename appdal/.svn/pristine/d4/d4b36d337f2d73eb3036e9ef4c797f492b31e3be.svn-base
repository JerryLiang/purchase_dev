<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/11/5 0005
 * Time: 17:44
 */

/**
 * 下载一个远程文件到指定的 文件中
 * @param string $ap_fileName 文件
 * @param string $url 远程文件
 */
function downFile($ap_fileName,$url){
    $dir = dirname($ap_fileName);
    isDirAndCreate($dir);

    $fp = @fopen($ap_fileName,'w+');
    $content =  file_get_contents($url);
    //$content = getCurlData($url,'GET');
    fwrite($fp, $content);

}

if(!function_exists('isDirAndCreate')){
    /**
     * 判断文件夹是否存在  如果不存在则自动创建文件夹（使用递归）
     * @param $dir
     */
    function isDirAndCreate($dir){
        if(!is_dir($dir)){// 文件夹不存在则自动创建
            mkdir($dir,0777,true);
        }
    }
}

/**
 * 删除文件夹中的内容(文件和子文件夹)
 * @param $directory
 */
function deleteDir($directory)
{
    $handle_dir = @opendir($directory);
    while ($file_name = @readdir($handle_dir)) {
        if ($file_name != "." && $file_name != "..") {// .代表当前目录 ..代表上级目录
            $full_path = $directory . "/" . $file_name;

            if (!is_dir($full_path)) {
                @unlink($full_path);// 删除文件
            } else {
                @deleteDir($full_path);// 回调
            }
        }
    }
    closedir($handle_dir);
}

/**
 * 判断 字符串内容是否是 XML 格式（可以用来区分 远程获取的内容是否是 文件流）
 * @param $str
 * @return bool|mixed
 */
function xmlParser($str){
    $xml_parser = xml_parser_create();
    if(!xml_parse($xml_parser,$str,true)){
        xml_parser_free($xml_parser);
        return false;
    }else {
        return (json_decode(json_encode(simplexml_load_string($str)),true));
    }
}

/**
 * 生成压缩包
 * @param array $file_name_arr
 * @param string $zip_file_name 保存压缩包的路径及名称
 * @param bool $del_original_file 是否删除原文件（true-删除，false-保留）
 * @return string 返回压缩包文件名，失败时返回‘空’
 */
if (!function_exists('CreateZipFile')) {
    function CreateZipFile($file_name_arr, $zip_file_name, $del_original_file = true)
    {
        if (!is_array($file_name_arr) OR empty(array_filter($file_name_arr)) OR empty($zip_file_name)) return false;

        $zip_file_name = $zip_file_name . '.zip';

        //进行多个文件压缩
        $zip = new ZipArchive();
        $zip->open($zip_file_name, ZipArchive::CREATE);   //打开压缩包
        foreach ($file_name_arr as $file) {
            $zip->addFile($file, basename($file));   //向压缩包中添加文件
        }
        $zip->close();  //关闭压缩包
        unset($zip);

        //删除csv临时文件
        if ($del_original_file) {
            foreach ($file_name_arr as $file) {
                @unlink($file);
            }
        }

        return file_exists($zip_file_name) ? basename($zip_file_name) : '';
    }
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