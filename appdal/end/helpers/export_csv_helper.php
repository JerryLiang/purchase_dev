<?php

/**
 * 字符串编码转换（用于 array_walk 回调）
 * @author Jolon
 * @param $value
 * @param $key
 */
function csv_my_convert(&$value, $key){
    $value = iconv('gbk', 'utf-8', $value);
}

/**
 * 保存数据到CSV文件中 以便于下载大数据
 *      文件以追加的方式保存数据
 *      （数字或数字字符串长度超过11位则后面会自动添加制表符\t，防止 excel 打开数据失真）
 * @author Jolon
 * @param array     $columns    标题（）
 * @param array     $dataList   数据列表
 * @param string    $filePath   文件路径
 * @return bool
 */
function csv_export_file($columns = array(), $dataList, $filePath){
    if (empty($filePath)) return false;

    // 表头数据输出
    $csv_data = '';
    if ($columns) {
        foreach ($columns as $value) {
            $csv_data .= iconv('utf-8', 'gbk//ignore', $value) . ',';
        }
        $csv_data = rtrim($csv_data, ',');
        $csv_data .= "\n";
    }

    if ($dataList) {
        foreach ($dataList as $k => $row) {
            foreach ($row as $val) {
                // 数字类型的增加制表符 防止EXCEL打开数据失真
                if (is_numeric($val) AND strlen($val) > 11) {
                    $val = $val . "\t";
                }
                $csv_data .= iconv('utf-8', 'gbk//ignore', $val) . ',';
            }
            $csv_data = rtrim($csv_data, ',');
            $csv_data .= "\n";
            unset($dataList[$k]);
        }
    }

    file_put_contents($filePath, $csv_data, FILE_APPEND);
    return $filePath;
}

/**
 * 导出CSV文件
 * @author Jolon
 * @param array $columns 首行数据（如标题）
 * @param array $data 数据
 * @param string $file_name 文件名称
 * @return string
 */
function csv_export($columns = [], $data = [], $file_name = ''){
    if(empty($file_name)){
        $file_name = md5(time()) . '.csv';
    }else{
        $file_name .= '.csv';
    }
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename=' . $file_name);
    header('Cache-Control: max-age=0');
    $fp = fopen('php://output', 'a');

    if (!empty($columns)) {
        foreach ($columns as $key => $value) {
            $columns[$key] = iconv('utf-8', 'gbk', $value);
        }
        fputcsv($fp, $columns);
    }

    $num = 0;
    $limit = 10000;//每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
    $count = count($data);//逐行取出数据，不浪费内存
    if ($count > 0) {
        for ($i = 0; $i < $count; $i++) {
            $num ++;
            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }
            $row = $data[$i];
            foreach ($row as $key => $value) {
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            fputcsv($fp, $row);
        }
    }
    fclose($fp);
    ob_flush();
    flush();
    exit;
}

/**
 * 读取CSV文件中指定的行数
 * @author Jolon
 * @param string    $csv_file   csv 文件路径
 * @param int       $lines      读取的行数(0为返回所有行)
 * @param int       $offset     跳过的行数
 * @return array|bool
 */
function csv_read_lines($csv_file = '', $lines = 0, $offset = 0){
    // 打开并读取文件
    if (!$fp = fopen($csv_file, 'r')) {
        return false;
    }
    $i = $j = 0;
    // 获取指向文件的行数，计算偏移量
    if ($offset > 0) {
        while (++$i <= $offset) {
            if (false !== ($line = fgets($fp))) {
                continue;
            }
            break;
        }
    }
    $data = array();
    if ($lines > 0) {// 大于0则读取 $lines 的行数
        while ((($j++ < $lines) && !feof($fp))) {
            $nowdata = fgetcsv($fp);
            array_walk($nowdata, 'csv_my_convert');// 转码
            $data[] = $nowdata;
        }
    } else {// 读取所用行数据
        while (!feof($fp)) {
            $nowdata = fgetcsv($fp);
            array_walk($nowdata, 'csv_my_convert');
            $data[] = $nowdata;
        }
    }

    fclose($fp);
    return $data;
}
/**
 * 导出文件
 *@param array $heads 表头
 *@param array $datalist 导出内容
 *@param sring $filename 文件名称
 * @author harvin
 **/
   function export($heads,$datalist,$filename){
        set_time_limit(0);
        ini_set('memory_limit', '1000M');
        ini_set('post_max_size', '1000M');
        ini_set('upload_max_filesize', '1000M');
        header("Content-type: text/html; charset=utf-8");
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        $fp = fopen('php://output', 'a');
        @fputcsv($fp, $heads);
        foreach ($datalist as $key => $value) {
            @fputcsv($fp, $value);
        }
        @fclose($fp);
        exit(); 
  }

/**
 * 数据写入csv文件
 * @param $fp
 * @param array $column_keys 表头字段对应$data数据键值
 * @param array $data 要写入的数据
 * @param int $offset_sequence 序号偏移量（分页导出时，为了保持序号连续）
 */
if (!function_exists('writeCsvContent')) {
    function writeCsvContent($fp, $column_keys, $data, $offset_sequence = 0)
    {
        $export_data = [];
        foreach ($data as $k => $v) {
            $sequence = (int)$k + $offset_sequence + 1;
            foreach ($column_keys as $key) {

                if ('sequence' == $key) {
                    $export_data[$k][] = iconv("UTF-8", "GB2312//IGNORE", $sequence);
                } elseif (is_array($v[$key])) {
                    $_str = !empty($v[$key]) ? implode(';', $v[$key]) : '';
                    $export_data[$k][] = iconv("UTF-8", "GB2312//IGNORE", $_str) . "\t";
                }else{
                    $val = $v[$key];
                    // 数字类型的增加制表符 防止EXCEL打开数据失真
                    if (is_numeric($val) && strlen($val) > 11) {
                        $val = $val . "\t";
                    }
                    //处理特殊字符\"，防止跨列错位
                    if($key == 'product_name' and stripos($val,"\"") !== false){
                        $val = $val."\t";
                    }
                    $export_data[$k][] = iconv("UTF-8", "GB2312//IGNORE", $val);
                }
            }
            fputcsv($fp, $export_data[$k]);//将数据通过fputcsv写到文件句柄
            unset($export_data[$k]);
        }
    }
}