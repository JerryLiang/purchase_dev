<?php

/**
 * Created by JoLon.
 * 文件夹处理
 * User: JoLon
 * Date: 2016/10/12
 * Time: 8:55
 */

class Filedirdeal
{

    /**
     * 获取文件夹下所有文件
     * @param string            $fileDir        目标文件夹路径
     * @param array|string      $fileExt        文件类型（默认空，返回所有）
     * @param bool              $isRecursion    是否递归读取子文件夹（默认使用递归）
     * @param bool              $isRealPath     是否返回真实路径
     * @param bool              $onlyFile       是否只是查找文件（默认所有）
     * @return array|bool
     */
    public function readAllFile($fileDir,$fileExt = '',$isRecursion = true,$isRealPath = true,$onlyFile = false)
    {
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
                        $this->readAllFile($temp,$fileExt,$isRecursion,$isRealPath,$onlyFile);
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


    public function mkdirs($dir, $mode = 0777){

        if (is_dir($dir) || @mkdir($dir, $mode)) return TRUE;

        if (!$this->mkdirs(dirname($dir), $mode)) return FALSE;

        return @mkdir($dir, $mode);

    }

    /**
     * 删除一个文件夹（递归删除）
     * @param $dirName
     * @return bool
     */
    public function removeDir($dirName){
        if(!is_dir($dirName)){
            return false;
        }
        $handle = @opendir($dirName);
        while(($file = @readdir($handle)) !== false){
            //判断是不是文件 .表示当前文件夹 ..表示上级文件夹 =2
            if($file != '.' && $file != '..'){
                $dir = $dirName.'/'.$file;
                if(is_dir($dir)){
                    $this->removeDir($dir);
                    @rmdir($dir);// 删除所有文件后删除文件夹
                }else{
                    @unlink($dir);
                }
            }
        }
        closedir($handle);
        return true;
    }


}