<?php
// +----------------------------------------------------------------------
// | 跃飞 [ 将来的你一定会感激现在奋斗的自己 ]
// +----------------------------------------------------------------------
// | Author: 钟贵廷
// +----------------------------------------------------------------------
// | Author URI: https://gitee.com/yeafy
// +----------------------------------------------------------------------
// | weChat:gt845272922  qq:845272922
// +----------------------------------------------------------------------

class CommonHelper
{
    /**
     * 描述:格式化数组
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $arr
     */
    public static function p($arr)
    {
        echo '<pre style="color: red;">';
        print_r($arr);
        echo '</pre>';
    }

    /**
     * 描述:打印日志
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param string $logData
     */
    public static function log_output($logData = '')
    {
        $logStr = PHP_EOL . '------------' . date('Y-m-d H:i:s') . '-------------' . PHP_EOL;
        $logStr .= var_export($logData, true);
        $logFileName = 'runLog' . date('y-m-d') . '.log';
        $runtime = $_SERVER['DOCUMENT_ROOT'];
        $logPath = $runtime . '/var/log/run/';
        if (!is_dir($logPath)) {
            mkdir($logPath, 0777, true);
        }
        file_put_contents($runtime . '/var/log/run/' . $logFileName, $logStr, FILE_APPEND);
    }


    /**
     * 描述:upload/image/productImages/1521202418562.xlsx
     * 获取1521202418562.xlsx
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $v
     * @return string
     */
    public static function getLastStr($v)
    {
        return substr(strrchr($v, '/'), 1);
    }

    /**
     * 描述:清除指定目录的所有文件
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param string $dir 要清除的目录名
     */
    public static function clearPic($dir)
    {
        $dh = opendir($dir);
        while (!!$file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    unlink($fullpath);   //删除不是目录的文件，如tmp/20141231142112.JPG
                } else {
                    self::clearPic($fullpath);  //递归删除子目录下的文件，$fullpath=tmp/1
                    rmdir($fullpath);  //删除空目录
                }
            }
        }
        closedir($dh);
    }


    /**
     * 示例数据：
     * $data = array(
     * array(NULL, 2010, 2011, 2012),
     * array('Q1',   12,   15,   21),
     * array('Q2',   56,   73,   86),
     * array('Q3',   52,   61,   69),
     * array('Q4',   30,   32,    0),
     * );
     */

    /**
     * 描述:导出excel格式的文件
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param array $data 需要生成excel文件的数组
     * @param string $filename 生成的excel文件名
     */
    public static function array2excel(array $data, $filename = 'simple.xls')
    {
        ini_set('max_execution_time', '0');
        include_once $_SERVER['DOCUMENT_ROOT'] . "/end/third_party/PHPExcel.php";
        $suffixName = CommonHelper::getSuffixName($filename); //获取文件后缀
        $filename = str_replace('.' . $suffixName, '', $filename) . '.' . $suffixName;
        $phpexcel = new PHPExcel();
        $phpexcel->getProperties()
            ->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $phpexcel->getActiveSheet()->fromArray($data);
        $phpexcel->getActiveSheet()->setTitle('Sheet1');
        $phpexcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header("Content-Disposition: attachment;filename=$filename");
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0


        if (strtolower($suffixName) == 'xlsx') {
            $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel2007');
        } elseif (strtolower($suffixName) == 'xls') {
            $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        } elseif (strtolower($suffixName) == 'csv') {
            $objwriter = PHPExcel_IOFactory::createWriter($phpexcel, 'CSV');
        }
        $objwriter->save('php://output');
        exit;
    }


    /**
     * 描述:将excel表数据转成数组
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param string $filePath
     * @param int $sheet
     * @return array
     */
    public static function excel2array($filePath = '', $sheet = 0, $titleKey = true)
    {
        if (empty($filePath) or !file_exists($filePath)) {
            die('file not exists');
        }
        include_once $_SERVER['DOCUMENT_ROOT'] . "/end/third_party/PHPExcel/PHPExcel.php";
        $PHPReader = new PHPExcel_Reader_Excel2007();        //建立reader对象
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }
        $PHPExcel = $PHPReader->load($filePath);        //建立excel对象
        $currentSheet = $PHPExcel->getSheet($sheet);        //**读取excel文件中的指定工作表*/
        $allColumn = $currentSheet->getHighestColumn();        //**取得最大的列号*/
        $allRow = $currentSheet->getHighestRow();        //**取得一共有多少行*/
        $data = array();
        for ($rowIndex = 1; $rowIndex <= $allRow; $rowIndex++) {        //循环读取每个单元格的内容。注意行从1开始，列从A开始
            for ($colIndex = 'A'; $colIndex <= $allColumn; $colIndex++) {
                $addr = $colIndex . $rowIndex;
                $cell = $currentSheet->getCell($addr)->getValue();
                if ($cell instanceof PHPExcel_RichText) { //富文本转换字符串
                    $cell = $cell->__toString();
                }
//            $data[$rowIndex][$colIndex] = $cell;
                $data[$rowIndex][] = $cell;
            }
        }

        //将第一行做为数组的键
        if ($titleKey == true) {
            $arr = [];
            foreach ($data as $k => $v) {
                if ($k == 1) {
                    continue;
                }
                //空表跳出
                if (empty(trim($v[0]))) {
                    break;
                }

                foreach ($data[1] as $k1 => $v1) {
                    $arr[$k][$v1] = $v[$k1];
                }

            }
        } else {
            $arr = $data;
        }

        return $arr;
    }


    /**
     * 描述:导入大表execl,超过Z列的excel
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $filePath
     * @return array
     */
    public static function  convertExcelToArr($filePath)
    {
        if (empty($filePath) or !file_exists($filePath)) {
            die('file not exists');
        }
        /*导入phpExcel核心类 */
        include_once $_SERVER['DOCUMENT_ROOT'] . "/end/third_party/PHPExcel/PHPExcel.php";
        //默认用excel2007读取excel，若格式不对，则用之前的版本进行读取
        $reader = new PHPExcel_Reader_Excel2007();
        if (!$reader->canRead($filePath)) {
            $reader = new PHPExcel_Reader_Excel5();
            if (!$reader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }

        $PHPExcel = $reader->load($filePath); // 载入excel文件
        $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn(); // 取得总列数
        $end_index = PHPExcel_Cell::columnIndexFromString($highestColumm);//由列名转为列数('AB'->28)
        /** 循环读取每个单元格的数据 */
        $data = array();  //声明数组
        for ($row = 1; $row <= $highestRow; $row++) {//行数是以第1行开始
            $temp = array();
            for ($column = 0; $column < $end_index; $column++) {//列数是以A列开始
                $col_name = PHPExcel_Cell::stringFromColumnIndex($column);//由列数反转列名(0->'A')
                $temp[] = $sheet->getCell($col_name . $row)->getValue();
            }
            $data[] = $temp;
        }
        return $data;
    }


    /**
     * 描述:将大写开头的驼峰命名转下划线命名
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $str
     * @return string
     */
    public static function toUnderScore($str)
    {
        $dstr = preg_replace_callback('/([A-Z]+)/', function ($matchs) {
            return '_' . strtolower($matchs[0]);
        }, $str);
        return trim(preg_replace('/_{2,}/', '_', $dstr), '_');
    }

    /**
     * 描述:描述:将下划线命名转换成大写开头的驼峰命名
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $str
     * @return string
     */
    public static function toCamelCase($str)
    {
        $array = explode('_', $str);
        $result = '';
        $len = count($array);
        if ($len > 1) {
            for ($i = 0; $i < $len; $i++) {
                $result .= ucfirst($array[$i]);
            }
        }
        return $result;
    }

    /**
     * 描述:将下划线命名转换成小写开头的驼峰命名
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $str
     * @return string
     */
    public static function camelCase($uncamelized_words, $separator = '_')
    {
        $uncamelized_words = $separator . str_replace($separator, " ", strtolower($uncamelized_words));
        return ltrim(str_replace(" ", "", ucwords($uncamelized_words)), $separator);
    }


    /**
     * 描述:将驼峰命名的键转换成下划线的键
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $arr
     * @return array
     */
    public static function convertToUnderlineKey($arr)
    {
        $data = [];
        foreach ($arr as $k => $v) {
            $data[self::toUnderScore($k)] = $v;
        }
        return $data;
    }

    /**
     * 描述:将下划线的key装成大写的驼峰
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $arr
     * @return array
     */
    public static function convertToUpperKey($arr)
    {
        $data = [];
        foreach ($arr as $k => $v) {
            if (strpos($k, '_') !== false) {
                $data[self::toCamelCase($k)] = $v;
            } else {
                $data[$k] = $v;
            }
        }
        return $data;
    }

    /**
     * 描述:格式化订单.防止订单号是科学算法
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $str
     * @return string
     */
    public static function formatJson($str)
    {
        $p = '/:[\s]?(\d+),/';
        $replacement = ':"${1}",';
        return preg_replace($p, $replacement, $str);
    }

    /**
     * 描述:发送get请求
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $url
     * @return mixed
     */
    public static function getRequest($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //0表示把结果输出，1不输出结果，而是返回
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $result;
        }
    }


    /**
     * 描述:将数组导出到csv文件
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param array $title 一维数组
     * @param array $data 二维数组
     * @param string $filename
     */
    public static function arrayToCsv($title = [], $data = [], $filename = 'sample.csv')
    {
        //$filename = iconv('utf-8', 'gbk', $filename);//客户端不识别gbk编码
        header("Content-type:text/csv");
        header("Content-Disposition:attachment;filename=" . $filename);
        header('Cache-Control:must-revalidate,post-check=0,pre-check=0');
        header('Expires:0');
        header('Pragma:public');
        $fp = fopen('php://output', 'a');
        //一维数组
        if (!empty($title)) {
            foreach ($title as $key => $val) {
                $title[$key] = iconv('utf-8', 'gbk', $val);
            }
            @fputcsv($fp, $title);
        }

        //二维数组
        if (!empty($data)) {
            $row = [];
            foreach ($data as $k => $v) {
                foreach ($v as $k1 => $v1) {
                    $row[$k1] = iconv('utf-8', 'gbk//TRANSLIT//IGNORE', $v1);
                }
                @fputcsv($fp, $row);
            }
        }
        @fclose($fp);
        exit();
    }

    /**
     * 描述:输出csv到指定目录
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param array $title
     * @param array $data
     * @param string $filename
     */
    public static function arrayToDirCsv($title = [], $data = [], $filename = 'sample.csv')
    {
        $filename = iconv('utf-8', 'gbk', $filename);
        $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/excel/zip';
        if (!file_exists($path)) {
            mkdir($path, 0777);
        }
        $fileName = $path . "/" . $filename;
        $fp = fopen($fileName, 'a');
        //一维数组
        if (!empty($title)) {
            foreach ($title as $key => $val) {
                $title[$key] = iconv('utf-8', 'gbk', $val);
            }
            @fputcsv($fp, $title);
        }

        //二维数组
        if (!empty($data)) {
            $row = [];
            foreach ($data as $k => $v) {
                foreach ($v as $k1 => $v1) {
                    $row[$k1] = iconv('utf-8', 'gbk', $v1);
                }
                @fputcsv($fp, $row);
            }
        }
        @fclose($fp);
//        exit();
    }

    /**
     * 描述:将csv文件打包成zip格式在浏览器输出
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     */
    public static function csvToZip()
    {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/upload/excel/zip/';
        $files = self::getFiles($path);
        $filename = $path . date('YmdHis') . ".zip";
        //开始打包
        if (!file_exists($filename)) {
            $zip = new ZipArchive();
            if ($zip->open($filename, ZIPARCHIVE::CREATE) !== TRUE) {
                exit('无法打开文件，或者文件创建失败');
            }
            foreach ($files as $val) {
                if (file_exists($val)) {
                    $zip->addFile($val, basename($val));//第二个参数是放在压缩包中的文件名称，如果文件可能会有重复，就需要注意一下
                }
            }
            $zip->close();//关闭
        }
        if (!file_exists($filename)) {
            exit("无法找到文件");
        }
        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header('Content-disposition: attachment; filename=' . basename($filename)); //文件名
        header("Content-Type: application/zip"); //zip格式的
        header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
        header('Content-Length: ' . filesize($filename)); //告诉浏览器，文件大小
        @readfile($filename);
        //删除目录下的所有文件
        self::clearPic($path);
    }


    /**
     * 描述:获取目录下的文件
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $dir
     * @return array
     */
    public static function getFiles($dir)
    {
        $result = array();
        if (is_dir($dir)) {
            $file_dir = scandir($dir);
            foreach ($file_dir as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                } elseif (is_dir($dir . $file)) {
                    $result = array_merge($result, self::getFiles($dir . $file . '/'));
                } else {
                    array_push($result, $dir . $file);
                }
            }
        } else {
            echo "非法目录";
            exit;
        }
        return $result;
    }


    /**
     * 描述:将csv文件转化成数组
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param string $fileName 绝对路径
     * @return array  多维数组
     */
    public static function csvToArray($fileName)
    {
        $arr = [];
        $file = fopen($fileName, 'r');
        while (($data = fgetcsv($file)) !== false) {
            $arr[] = $data;
        }
        fclose($file);
        return $arr;
    }


    /**
     * 描述:获取数据库的全部表名
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param string $host
     * @param string $username
     * @param string $password
     * @param string $dbName 数据库
     * @param int $port 端口
     * @return array
     */
    public static function getTables($host, $username, $password, $dbName, $port = 3306)
    {
        if (!mysql_connect($host . ':' . $port, $username, $password)) {
            die('Could not connect to mysql');
        }
        mysql_query("SET NAMES utf8");
        $result = mysql_list_tables($dbName);
        if (!$result) {
            die(mysql_error());
        }
        $tables = [];
        while ($row = mysql_fetch_row($result)) {
            $tables[] = $row[0];
        }
        mysql_free_result($result);
        return $tables;
    }


    /**
     * 描述:获取数据表的详细信息（字段，类型，注释）
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $host
     * @param $username
     * @param $password
     * @param $dbName
     * @param $tableName
     * @param int $port
     * @return array
     */
    public static function getTablesDetail($host, $username, $password, $dbName, $tableName, $port = 3306)
    {
        if (!mysql_connect($host . ':' . $port, $username, $password)) {
            die('Could not connect to mysql');
        }
        mysql_query("SET NAMES utf8");
        $res_columns = mysql_query("SHOW FULL COLUMNS FROM {$dbName}.$tableName");
        $arr = [];
        $i = 0;
        while ($row = mysql_fetch_array($res_columns)) {
            $arr[$i]['field'] = $row['Field'];
            $arr[$i]['type'] = $row['Type'];
//            $arr[$i]['comment'] =$row['Comment'];
            $i++;
        }
        sort($arr);
        return $arr;
    }

    /**
     * 描述:格式化数字小数点最多2位
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $num
     * @return string
     */
    public static function formatFloatNumber($num)
    {
        $num = (float)rtrim(sprintf('%0.2f', $num), 0);
        if ($num == 0) {
            $num = 0.01;
        }
        return $num;
    }

    /**
     * 描述:设置ses
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $str
     */
    public static function setSes($str)
    {
        $_SESSION = json_decode(base64_decode($str), true);
    }


    /**
     * 描述:设置请求状态码
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $num
     */
    public static function setHttpStatus($num)
    {
        $http = array(
            //Informational 1xx
            100 => '100 Continue',
            101 => '101 Switching Protocols',
            //Successful 2xx
            200 => '200 OK',
            201 => '201 Created',
            202 => '202 Accepted',
            203 => '203 Non-Authoritative Information',
            204 => '204 No Content',
            205 => '205 Reset Content',
            206 => '206 Partial Content',
            226 => '226 IM Used',
            //Redirection 3xx
            300 => '300 Multiple Choices',
            301 => '301 Moved Permanently',
            302 => '302 Found',
            303 => '303 See Other',
            304 => '304 Not Modified',
            305 => '305 Use Proxy',
            306 => '306 (Unused)',
            307 => '307 Temporary Redirect',
            //Client Error 4xx
            400 => '400 Bad Request',
            401 => '401 Unauthorized',
            402 => '402 Payment Required',
            403 => '403 Forbidden',
            404 => '404 Not Found',
            405 => '405 Method Not Allowed',
            406 => '406 Not Acceptable',
            407 => '407 Proxy Authentication Required',
            408 => '408 Request Timeout',
            409 => '409 Conflict',
            410 => '410 Gone',
            411 => '411 Length Required',
            412 => '412 Precondition Failed',
            413 => '413 Request Entity Too Large',
            414 => '414 Request-URI Too Long',
            415 => '415 Unsupported Media Type',
            416 => '416 Requested Range Not Satisfiable',
            417 => '417 Expectation Failed',
            418 => '418 I\'m a teapot',
            422 => '422 Unprocessable Entity',
            423 => '423 Locked',
            426 => '426 Upgrade Required',
            428 => '428 Precondition Required',
            429 => '429 Too Many Requests',
            431 => '431 Request Header Fields Too Large',
            //Server Error 5xx
            500 => '500 Internal Server Error',
            501 => '501 Not Implemented',
            502 => '502 Bad Gateway',
            503 => '503 Service Unavailable',
            504 => '504 Gateway Timeout',
            505 => '505 HTTP Version Not Supported',
            506 => '506 Variant Also Negotiates',
            510 => '510 Not Extended',
            511 => '511 Network Authentication Required'
        );
        echo $http[$num];
        header('HTTP/1.1 ' . $http[$num]);
        die;
    }

    /**
     * 描述:获取post数据包括$HTTP_RAW_POST_DATA，文件数据除外
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @return bool|string
     */
    public static function getPost()
    {
        return file_get_contents("php://input");
    }

    /**
     * 描述:获取字符串的偶数
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $str
     * @return string
     */
    public static function getOddStr($str)
    {
        $_str = '';
        if (strlen($str) > 10)  //大于10解密
        {
            $arr = str_split($str);
            foreach ($arr as $k => $v) {
                if ($k % 2 == 0) {
                    $_str .= $arr[$k];
                }
            }
        } else {
            $_str = $str;
        }

        return $_str;
    }

    /**
     * 描述:格式化钉钉时间
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $time_strap
     * @return false|string
     */
    public static function dingTalkFormatDate($time_strap)
    {
        $time = substr($time_strap, 0, 10);
        return date('Y-m-d H:i:s', $time);
    }


    /**
     * 描述:获取时间周期
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param string $startDay 2018-07-01
     * @param string $endDay 2018-08-01
     * @return array
     * @throws string
     */
    public static function getDate($startDay, $endDay)
    {
        $start = new DateTime($startDay);
        $end = new DateTime($endDay);
        $arr = [];
        foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $d) {
            $arr[] = $d->format('Y-m-d');
        }
        return $arr;
    }

    /**
     * 描述:将考勤时间转换成分钟
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $str
     * @return float|int
     */
    public static function getMinute($str)
    {
        if (strpos($str, '小时') !== false) {
            preg_match_all('/(.*?)小时(.*?)分钟/', $str, $matches);
            $minute = $matches[1][0] * 60 + $matches[2][0];
        } elseif (strpos($str, '分钟') !== false) {
            preg_match_all('/(.*?)分钟/', $str, $matches);
            $minute = $matches[1][0];
        } else {
            $minute = 0;
        }
        return $minute;
    }


    public static function convertMin($time)
    {
        $h = floor($time / 60);
        $m = $time % 60;
        if ($h != '0') {
            $str = $h . '小时' . $m . '分';
        } else {
            $str = $m . '分';
        }
        return $str;
    }


    /**
     * 描述:获取文件后缀名
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @shili  xxxx.png输出png;xxx.gif输出gif;xxxxx.exe输出exe
     * @param $filename
     * @return string
     */
    public static function getSuffixName($filename)
    {
        return substr(strrchr($filename, "."), 1);
    }


    /**
     * 描述:随机抽取8张图片，含主图，三种算法
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $images
     * @return array
     */
    public static function getImageList($images)
    {
//        $images = ['1.jpg','2.jpg','3.jpg','4.jpg','5.jpg','6.jpg'];
//        $images = ['1.jpg','2.jpg','3.jpg','4.jpg','5.jpg','6.jpg','7.jpg','8.jpg','9.jpg','10.jpg','11.jpg'];
//        $images = ['1.jpg','2.jpg','3.jpg','4.jpg','5.jpg','6.jpg','7.jpg','8.jpg','9.jpg','10.jpg','11.jpg','12.jpg','13.jpg','14.jpg','15.jpg','16.jpg'];
        //图片小于8张
        $count = count($images);
        if ($count <= 8) {
            shuffle($images);
            $resImages = $images;
        } else {
            $images1 = array_slice($images, 0, 8);
            $firstImage = [$images[array_rand($images1)]];
            $min = $count - 8;
            if ($min >= 7) {
                $resImages = array_slice($images, 8, 7);
                shuffle($resImages);
                $resImages = array_merge($firstImage, $resImages);
            } else {
                $diffImage = array_diff($images1, $firstImage);
                $image2 = array_slice($images, 8, $min);
                $image3 = array_rand($diffImage, 8 - $min - 1);
                foreach ($image3 as $k => $v) {
                    $data[] = $images[$v];
                }
                $resImages = array_merge($image2, $data);
                shuffle($resImages);
                $resImages = array_merge($firstImage, $resImages);
            }
        }
        return $resImages;
    }


    /**
     * 描述:随机拼接标题，打乱
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param string $str 标题
     * @param string $keywordStr 关键词
     * @param int $len 长度
     * @return string
     */
    public static function getRandTitle($str, $keywordStr, $len = 60)
    {
        $kData = explode(',', $keywordStr);
        $keyword = trim($kData[array_rand($kData)]);
        $length = $len - strlen($keyword) - 1;
        $str1 = substr($str, 0, $length);
        $data1 = preg_split('/ /', $str1);
        $data2 = preg_split('/ /', $str);
        $result = array_intersect($data2, $data1);
        $res = implode(' ', $result);
        $arr = array_merge([$res], [$keyword]);
        shuffle($arr);
        $res = implode(' ', $arr);
        return $res;
    }


    // 1 x Ignition Coil
    // 1*Towel disinfection cabinet
    // 1 * Portable Pill Box
    // 1*Towel disinfection cabinet
    /**
     * 描述:抓取包装信息
     * @author 钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $description
     * @return string
     */
    public static function getPackageList($description)
    {
        $path = '/>(\d[\s]?[\*|x](.*?))</s';
        preg_match_all($path, $description, $matches);
        $str = implode('</br>', $matches[1]);
        return $str;
    }


    /**
     * 描述:校验描述,防止没有域名的图片刊登
     * @author    钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param $accountId
     * @param $array
     * @return mixed
     */
    public static function validateDes($data)
    {
        $path = '/<img.*?src=\"(.*?)\".*?>/s';
        preg_match_all($path, $data, $images);
        $form_image = $images[1];
        if (!empty($form_image)) {
            $image = [];
            foreach ($form_image as $k => $v) {
                if (strpos($v, 'http') !== 0) {
                    $image[$v] = "http://images.yibainetwork.com" . $v;
                }
            }
            if (!empty($image)) {
                $str = str_replace(array_keys($image), array_values($image), $data);
                return $str;
            } else {
                return $data;
            }
        } else {
            return $data;
        }
    }


    /**
     * 描述:设置调试信息
     * @author 钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     */
    public static function setDebug()
    {
        ini_set("display_errors", "On");
        error_reporting(E_ALL & ~E_NOTICE);
    }


    /**
     * 描述:将数组转换成xml格式
     * @author 钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param array $bodyContent 数组
     * @param string $item 最外层标记
     * @return mixed
     */
    public static function arrayToXML(array $bodyContent, $item = 'item')
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $item . '/>');
        self::addToNode($bodyContent, $xml);
        return $xml->asXML();
    }

    /**
     * @param array|string $content
     * @param \SimpleXMLElement $node
     * @param \SimpleXMLElement|null $parentNode
     */
    private static function addToNode($content, SimpleXMLElement $node, SimpleXMLElement $parentNode = null)
    {
        if (is_array($content)) {
            foreach ($content as $argument => $value) {
                if (0 === $argument) {
                    $newNode = $node;
                } elseif (is_numeric($argument) && null !== $parentNode) {
                    $newNode = $parentNode->addChild($node->getName());
                } else {
                    $newNode = $node->addChild($argument);
                }
                self::addToNode($value, $newNode, $node);
            }
        } else {
            dom_import_simplexml($node)->nodeValue = htmlspecialchars($content);
        }
    }


    /**
     * 描述:转换成json对象
     * @author 钟贵廷
     * @WeChat gt845272922
     * @qq 845272922
     * @param string $data
     * @param string $message
     * @param string $status
     */
    public static function convertToJson($data = '', $message = '', $status = false)
    {
        header("Content-type:application/json; charset=utf-8");
        $jsonData = array(
            'status' => $status,
            'message' => $message,
            'data' => $data,
        );
        if (empty($data) && empty($message)) {
            $jsonData['message'] = '参数不能为空';
        }
        echo json_encode($jsonData, JSON_UNESCAPED_UNICODE);
        die;
    }

    /**
     * 描述:文件下载
     * @author jackson
     * @param string $fileName
     */
    public static function downloadFile($fileName = '')
    {
        //获取要下载的文件名
        $filename = $fileName;
        $mime = 'application/force-download';
        header('Pragma: public'); // required
        header('Expires: 0'); // no cache
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: private', false);
        header('Content-Type: ' . $mime);
        //设置头信息
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Connection: close');
        //读取文件并写入到输出缓冲
        readfile($filename); // push it out

        //删除zip文件
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        exit();
    }

    /**
     * 描述:获取图片名称
     * @author jackson
     * @param string $fileName
     */
    public static function getFileInformation($filePath = '')
    {
        //获取远程文件内容
        $fileName = file_get_contents($filePath);
        //获取文件名称
        $info = pathinfo($filePath);
        $_fileName = $info['basename'];
        //保存文件
        file_put_contents($_fileName, $fileName);
        return $_fileName;
    }

    /**
     * 描述:文件下载
     * @author jackson
     * @param string $fileName
     * @param string $_zip
     */
    public static function generateZipFile($fileName, $_zip = 'test.zip')
    {
        $zip = new ZipArchive();
        $zip->open($_zip, ZipArchive::CREATE);//创建一个空的zip文件
        // if array
        if (is_array($fileName)) {
            foreach ($fileName as $file) {
                self::_addFile($zip, $file);
            }
        } else {
            //if single file
            if (pathinfo($fileName, PATHINFO_EXTENSION)) {
                self::_addFile($zip, $fileName);
            }
            //if dir
            if (is_dir($fileName)) {
                self::zipDirFile($zip, $fileName);
            }
        }
        $zip->close();//关闭压缩包

        //主动删除文件
        if (!empty($fileName)) {
            foreach ($fileName as $key => $file) {
                unlink($file);
            }
        }

        //下载zip文件
        self::downloadFile($_zip);

    }

    /**
     * 描述:增加多个要下载的文件到zip包中
     * @author jackson
     * @param object $zip
     * @param string $file
     */
    public static function _addFile($zip, $file)
    {
        $zip->addFile($file);//向压缩包中添加文件
    }

    /**
     * 描述:打包目录下的所有文件
     * @author jackson
     * @param object $zip
     * @param string $file
     */
    public static function zipDirFile($zip, $file)
    {
        $handler = opendir($file);//打开目录文件
        while (($filename = readdir($handler)) !== false) {//遍历目录文件
            if ($filename != "." && $filename != "..") {//文件夹名字为"." 或 ".." 将不对其操作
                if (is_dir($file . "/" . $filename)) {//如果文件是目录，递归
                    self::zipDirFile($zip, $file . "/" . $filename);
                } else {
                    self::_addFile($zip, $file . "/" . $filename);
                }
            }
        }
        @closedir($handler);
    }
}