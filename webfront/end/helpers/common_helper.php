<?php

/**
 * 响应http请求，返回json数据
 * Params:
 *      $data   Array   返回的数据
 *      $response_code int http请求状态码
 */
if (!function_exists('http_response')) {

    function http_response($data = array(), $response_code = 200)
    {

        if (isset($data['errorCode'])) {
            $ci = &get_instance();
            $error_code_conf = $ci->config->item('error_code');
            if (!empty($error_code_conf) && isset($error_code_conf[$data['errorCode']])) {
                $data['errorMess'] = $error_code_conf[$data['errorCode']];
            }
        }

        if (extension_loaded('zlib')) {
            if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
                ob_clean();
                ob_start('ob_gzhandler');
            }
        }

        header('Content-Type: application/json;charset=utf-8');

        if (function_exists('set_status_header')) {
            set_status_header($response_code);
        }

        echo json_encode($data);
        exit;

    }

}

/**
 * 格式化响应返回的数据
 * @author Jolon
 * @param int $status 状态码（成功1，失败0）
 * @param array $data_list 数据（values,key,drop_down_box）
 * @param string $errorMess 操作结果状态提示标识
 * @param array $paging_data 分页数据（total,offset,limit）
 * @return array
 */
function response_format($status, $data_list = [], $errorMess = null, $paging_data = null)
{
    $return_data = [];
    $return_data['status'] = ($status == 1) ? $status : 0;// 固定值 1或0
    $return_data['data_list'] = $data_list;

    if (!is_null($errorMess)) {
        $return_data['errorMess'] = $errorMess;
    }
    if (!is_null($paging_data)) {
        if (!isset($paging_data['pages']) and !empty($paging_data['total']) and !empty($paging_data['limit'])) {
            $paging_data['pages'] = ceil($paging_data['total'] / $paging_data['limit']);// 获取总页数
        }
        $return_data['page_data'] = $paging_data;
    }
    return $return_data;
}


//调试打印
if (!function_exists('pr')) {
    function pr($arr, $escape_html = true, $bg_color = '#EEEEE0', $txt_color = '#000000')
    {
        echo sprintf('<pre style="background-color: %s; color: %s;">', $bg_color, $txt_color);
        if ($arr) {
            if ($escape_html) {
                echo htmlspecialchars(print_r($arr, true));
            } else {
                print_r($arr);
            }

        } else {
            var_dump($arr);
        }
        echo '</pre>';
    }
}
//写入日志
if (!function_exists('logger')) {

    function logger($logLever = 'debug', $logTitle = '', $msg = '')
    {

        //日志内容不为空
        if (empty($msg)) return;
        $logMsg = sprintf("#%d %s: %s\r\n", getmypid(), "-----$logTitle-----", $msg);
        log_message($logLever, $logMsg);

    }

}

//将数组按键值升序排序
if (!function_exists('ascSort')) {

    function ascSort($para = '')
    {
        if (is_array($para)) {
            ksort($para);
            reset($para);
        }
        return $para;
    }

}

if (!function_exists('get')) {

    function get($key = '', $urldecode = 0)
    {

        if (!empty($key)) {
            $result = isset($_GET[$key]) ? $_GET[$key] : '';
            $urldecode && $result = urldecode($result);
            return $result;
        } else {
            return $_GET;
        }

    }

}
if (!function_exists('post')) {

    function post($key = '', $urldecode = 0)
    {
//        if(isset($_REQUEST['debug'])) return get($key,$urldecode);
        if (!empty($key)) {
            $result = isset($_POST[$key]) ? $_POST[$key] : '';
            $urldecode && $result = urldecode($result);
            return $result;
        } else {
            return $_POST;
        }

    }

}

if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {

        $ip = false;

        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("^(10|172\.16|192\.168)\.^", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }

        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);

    }
}

//构造接口请求参数，将数据转为字符串a=1&b=c&d=j
if (!function_exists('createLinkstring')) {

    function createLinkstring($para)
    {

        $arg = "";

        foreach ($para as $key => $val) {
            //if(empty($val)) continue;
            if ($val === '' || $val === null) continue;
            if (is_array($val)) $val = json_encode($val);
            $arg .= $key . "=" . urlencode($val) . "&";
        }

        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

}

if (!function_exists('is_serialized')) {
    function is_serialized($data)
    {
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}
/**
 * 遍历文件夹下所有文件
 * @param $dir
 * @return array|bool
 */
if (!function_exists('read_all')) {
    function read_all($dir)
    {
        if (!is_dir($dir)) return false;
        $handle = opendir($dir);
        $temp_list = [];
        if ($handle) {
            while (($fl = readdir($handle)) !== false) {
                $temp = $dir . DIRECTORY_SEPARATOR . $fl;
                if (is_dir($temp) && $fl != '.' && $fl != '..') {
                    read_all($temp);
                } else {
                    if ($fl != '.' && $fl != '..') {
                        $temp_list[] = $fl;
                    }
                }
            }
        }
        return $temp_list;
    }
}

/**
 * 获取请求参数
 * @param string $type
 * @return mixed
 */
if (!function_exists('get_request_params')) {
    function get_request_params($method = 'POST')
    {
        $ci = &get_instance();
        //$method = strtoupper($_SERVER['REQUEST_METHOD']);
        if ($method == 'GET') {
            //return $ci->input->get(NULL, true);
            return $ci->input->get(NULL);
        } else if ($method == 'POST') {
            $contentType = !empty($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
            if (strpos($contentType, 'json') > 0) {
                $postRaw = file_get_contents('php://input');
                return json_decode($postRaw, true);
            } else {
                //return $ci->input->post(NULL, true);
                return $ci->input->post(NULL);// $xss_clean 设为 true 会导致URL类型的参数会解析URL编码，导致JSON解码失败
            }
        }else if ($method == 'REQUEST') {
            return $_REQUEST;
        }

        //return $ci->input->get(NULL, true);
        return $ci->input->get(NULL);
    }
}

/**
 * 解析下拉搜索框
 * @param string $type
 * @return mixed
 */
if (!function_exists('check_down_box')) {
    function check_down_box(&$object)
    {
        $_key = '';
        $_val = '';
        if (!empty($object) && is_array($object)) {

            foreach ($object['data'] as $key => $item) {
                if(in_array($key,['down_disable'])) continue;
                if (preg_match("/down_/i", $key) && !empty($item)) {

                    //设置不同下拉框列字段
                    switch ($key) {
                        case 'down_buyer':
                            $_key = 'buyer_id';
                            $_val = 'buyer_name';
                            break;
                        case 'down_supplier':
                            $_key = 'supplier_code';
                            $_val = 'supplier_name';
                            break;
                        case 'down_create_user':
                            $_key = 'create_user_id';
                            $_val = 'create_user_name';
                            break;
                        case 'down_settlement':
                            $_key = 'settlement_code';
                            $_val = 'settlement_name';
                            break;
                        case 'down_oneline':
                            $_key = 'product_line_id';
                            $_val = 'linelist_cn_name';
                            break;
                        case 'down_address':
                            $_key = 'region_code';
                            $_val = 'region_name';
                            break;
                        case 'down_apply_user':
                            $_key = 'id';
                            $_val = 'name';
                            break;
                        default:
                            break;
                    }

                    //组装前端数据
                    if (isset($item['list']) && $_item = $item['list']) {

                        //判断是否为空
                        if (empty($_item)) {
                            continue;
                        }

                        $keys = array_column($_item, $_key);
                        $values = array_column($_item, $_val);
                    } else {

                        //判断是否为空
                        if (empty($item)) {
                            continue;
                        }

                        $keys = array_column($item, $_key);
                        $values = array_column($item, $_val);
                    }

                    $object['data']['drop_down_box'][$key] = array_combine($keys, $values);
                }
            }
        }

    }
}

/**
 * @desc 指定键名，将数组的一个键值作为新数组的键名另一个键值作为键值
 * @author jackson
 * @param array $object
 * @param string $key 键名
 * @param string $key1 键名
 * @return mixed
 */
if (!function_exists('create_key_and_name')) {
    function create_key_and_name($object = array(), $key = '', $key1 = '')
    {
        if (!empty($object)) {
            $keys = array_column($object, $key);
            $values = array_column($object, $key1);
            if (!empty($keys)) {
                return array_combine($keys, $values);
            }
        }
        return array();
    }
}


/**
 * 验证json的合法性
 * @param $string
 * @return bool
 */
function is_json($string)
{
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}

/**
 * 获取暂无文件默认图片
 * @return string
 */
function get_default_no_image(){
    return UPLOAD_DOMAIN.'/no_image.jpg';
}


if(!function_exists('_getStatusList')){
    // 全局变量 快捷获取状态值
    global  $redis_get ;
    $redis_get = new Redis_value_order();

    /**
     * 获取 指定状态的 状态列表
     * @author Jolon
     * @param $status_key
     * @return mixed
     */
    function _getStatusList($status_key){
        global $redis_get;
        $data = $redis_get->redis_set();
        $data = json_decode(isset($data[$status_key])?$data[$status_key]:'', TRUE);
        return $data;
    }
}

if (!function_exists('getCurlData')) {
    function getCurlData($curl, $Data, $method = 'post', $header = '', $type = false,$time_out = array()) {
        $ch = curl_init(); //初始化
        curl_setopt($ch, CURLOPT_URL, $curl); //设置访问的URL
        curl_setopt($ch, CURLOPT_HEADER, false); // false 设置不需要头信息 如果 true 连头部信息也输出
        curl_setopt($ch, CURLE_FTP_WEIRD_PASV_REPLY, true);
        if($time_out){
            curl_setopt($ch, CURLOPT_TIMEOUT, isset($time_out['time_out'])?$time_out['time_out']:30); //设置成秒
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, isset($time_out['conn_out'])?$time_out['conn_out']:30);
        }else{
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置成秒
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if ($type) {
            curl_setopt($ch, CURLOPT_USERPWD, "service:service"); //auth 验证  账号及密码
        }
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //只获取页面内容，但不输出
        if (strtolower($method) == 'post') {
            curl_setopt($ch, CURLOPT_POST, true); //设置请求是POST方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $Data); //设置POST请求的数据   
        }
        $datas = curl_exec($ch); //执行访问，返回结果

        if (empty($datas) and $datas === false) {
            $error = curl_error($ch);
            var_dump($error);
            exit;
        }

        curl_close($ch); //关闭curl，释放资源
        return $datas;
    }

}

function format_price($price,$length = 3)
{
    return number_format((float)$price, $length, '.', '');
}

/**
 * 将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 * @access public
 * @param string $str 待转换字串
 * @return string $str 处理后字串
 */

if(!function_exists('make_semiangle')){
    function make_semiangle($str){
        $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4','５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9', 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E','Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O','Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T','Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y','Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd','ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i','ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n','ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z','（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[','】' => ']', '〖' => '[', '〗' => ']', '“' => '"', '”' => '"','‘［' => '[', '］' => ']', '｛' => '{', '｝' => '}', '《' => '<','》' => '>','％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-','：' => ':', '。' => '.', '、' => ',', '，' => '.', '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',  '｀' => '`', '‘' => '`', '｜' => '|', '〃' => '"','　' => ' ',''=> '',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',' '=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'','	'=>'');
        return strtr($str, $arr);
    }
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

if (!function_exists('get_export_path')) {

    /**
     * 获取上传统一路径，避免因为upload文件夹修改而修改
     * @param string $subDir 子文件夹
     * @return string
     */
    function get_export_path($subDir = null)
    {
        $webfront_path = dirname(dirname(APPPATH));
        if($subDir){
            $webfront_path = $webfront_path.'/webfront/download_csv/'.$subDir.'/';
        }else{
            $webfront_path = $webfront_path.'/webfront/download_csv/';
        }
        isDirAndCreate($webfront_path);

        return $webfront_path;
    }
}


if (!function_exists('get_export_path_replace_host')) {

    /**
     * 用前端域名替换 文件的绝对路径以便下载文件
     * @param string $path 文件夹
     * @param string $webfront_host 前端域名
     * @return string
     */
    function get_export_path_replace_host($path,$webfront_host)
    {
        $str_index = stripos($path,'webfront');
        $host_path = $webfront_host.substr($path,$str_index+9);
        return $host_path;
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
function readAllFile($fileDir,$fileExt = '',$isRecursion = true,$isRealPath = true,$onlyFile = false)
{
    if (!is_dir($fileDir)) return false;

    static  $fileList   = [];

    $handle     = opendir($fileDir);

    if ($handle) {
        while (($nowFile = readdir($handle)) !== false) {
            if(count($fileList) > 3000) break;
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

