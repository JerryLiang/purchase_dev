<?php

/**
 * 添加 系统操作错误日志
 * @param $message_list
 * @return mixed
 */
function saveSystemErrorLog($message_list){
    $CI = &get_instance();
    $CI->load->model('system_error_log_model');
    return $CI->system_error_log_model->insertLog($message_list);
}

function getErrorLevels($level){
    $levels = array(
        E_ERROR           => 'Error',
        E_WARNING         => 'Warning',
        E_PARSE           => 'Parsing Error',
        E_NOTICE          => 'Notice',
        E_CORE_ERROR      => 'Core Error',
        E_CORE_WARNING    => 'Core Warning',
        E_COMPILE_ERROR   => 'Compile Error',
        E_COMPILE_WARNING => 'Compile Warning',
        E_USER_ERROR      => 'User Error',
        E_USER_WARNING    => 'User Warning',
        E_USER_NOTICE     => 'User Notice',
        E_STRICT          => 'Runtime Notice'
    );

    return isset($levels[$level]) ? $levels[$level] : '未定义错误类型';
}


/**
 * 自定义错误处理函数（错误自动抛出异常）
 * @author Jolon
 * @param $err_no
 * @param $err_str
 * @param $err_file
 * @param $err_line
 * @throws Exception
 */
function _my_error_handler($err_no, $err_str ,$err_file, $err_line)
{
    if(in_array($err_no,[E_NOTICE,E_USER_NOTICE,E_STRICT,E_USER_WARNING])){
        return true;// NOTICE类型不退出系统
    }
    $severity = getErrorLevels($err_no);

    $message = "错误级别: $severity \n"
        ."$err_file 文件在 $err_line 行发生错误：$err_str";


    // 获取错误追溯信息
    $index    = 0;
    $message_details  = "信息概要：".$message." \n\n";
    $message_details .= "#".($index++)." Severity:    $severity \n";
    $message_details .= "#".($index++)." Message:     $err_str \n";
    $message_details .= "#".($index++)." Filename:    $err_file \n";
    $message_details .= "#".($index++)." Line Number: $err_line \n\n\n";

    $trace          = debug_backtrace();
    $message_details .= "#".($index++)." Backtrace:";
    foreach($trace as $error):
        if(isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0):
            $message_details .= "#".($index++)." File: ".$error['file']."\n";
            $message_details .= "#".($index++)." Line: ".$error['line']."\n";
            $message_details .= "#".($index++)." Function: ".$error['function']."\n\n\n";
        endif;
    endforeach;


    // 屏幕特殊类型的错误
    if(stripos($message_details,'mpdf.php') !== false){// mpdf插件有些warning警告不影响功能但是会提示信息
        return true;
    }

    saveSystemErrorLog($message_details);
    if(CG_ENV == 'prod'){
        http_response(response_format(0, [], '程序发生错误请联系技术处理'));
    }else{
        http_response(response_format(0, [], $message));
    }
}
set_error_handler('_my_error_handler');


// 加载用户信息  @author Jolon
if (file_exists(dirname(__FILE__) . '/user_helper.php')) {
    require(dirname(__FILE__) . '/user_helper.php');
}
// 加载 API公共方法  @author Jolon
if (file_exists(dirname(__FILE__) . '/api_helper.php')) {
    require(dirname(__FILE__) . '/api_helper.php');
}

/**
 * 创建操作日志 便捷方法 (operator_log_model->insert_one_log)
 * @author Jolon
 * @param array $data operator_log_model->insert_one_log
 * @param string $table_name 日志表名（默认 operator_log）
 * @return mixed
 * @example
 *      $data = array(
 *          id              => 目标记录编号（int|string）
 *          type            => 操作类型（如 操作的数据表名，无前缀）
 *          content         => 改变的内容（简略信息,支持搜索）
 *          detail          => 改变的内容（详细信息,文本类型）
 *          user            => 操作人（默认当前用户）
 *          time            => 操作时间（exp.2018-11-23 16:16:16  默认当前时间）
 *          operate_ip      => 操作IP（默认当前用户IP）
 *          operate_route   => 操作路由（默认当前路由）
 *          is_show         => 标记日志类型（1.展示日志，2.非展示日志，默认 1）
 *      )
 */
if (!function_exists('operatorLogInsert')) {
    function operatorLogInsert($data,$table_name = 'operator_log')
    {
        $CI = &get_instance();
        $CI->load->model('operator_log_model');

        return $CI->operator_log_model->insert_one_log($data,$table_name);
    }
}

/**
 * 添加 API 请求 操作日志
 * @author Harvin
 * @param array $data   要保存的数据
 * @param string $table_name 日志表名（默认 api_request_log）
 * @return bool  true.成功,false.失败
 *
 * @example
 *      $data = array(
 *          record_number       => 操作记录编号（int|string）
 *          record_type         => 操作记录类型（如 操作的数据表名）
 *          api_url             => 接口地址
 *          post_content        => 接口推送数据
 *          response_content    => 接口回传数据
 *          create_time         => 操作时间（exp.2018-11-23 16:16:16  默认当前时间）
 *          status              => 接口状态(默认 1 1.success 或 0.fail)
 *      )
 */
if (!function_exists('apiRequestLogInsert')) {
    function apiRequestLogInsert($data,$table_name = 'api_request_log')
    {
        $CI = &get_instance();
        $CI->load->model('operator_log_model');
        return $CI->operator_log_model->insert_api_request_log($data,$table_name);
    }
}

/**
 * 一键生成采购单、1688一键下单、1688一键拍单
 */
if (!function_exists('createPurchaseLog')) {
    function createPurchaseLog($data){
        $CI = &get_instance();
        $CI->load->model('operator_log_model');
        return $CI->operator_log_model->create_purchase_log($data);
    }
}

/**
 * 查询保存的日志
 * @author Jolon
 * @param array $query 查询条件
 * @param string $field 查询字段
 * @param string $table_name 日志表名（默认 operator_log）
 * @return bool|array   array.结果集，false.查询条件缺失
 *
 * @example
 *      $query = array(
 *          id          => 目标记录编号（int|string）
 *          type        => 操作类型（如 操作的数据表名，无前缀）
 *          content     => 改变的内容（简略信息,支持搜索）
 *          user        => 操作人（默认当前用户）
 *          is_show     => 标记日志类型（1.可展示，2.否）
 *          page        => 查询页数
 *          limit       => 查询调速
 *     )
 */
if (!function_exists('operatorLogQuery')) {
    function operatorLogQuery($query,$field = '*',$table_name = null)
    {
        $CI = &get_instance();
        $CI->load->model('operator_log_model');

        return $CI->operator_log_model->query_logs($query,$field,$table_name);
    }
}

/**
 * 添加 用户审核记录、驳回备注 日志
 * @author Jolon
 * @param array $data 要保存的数据
 * @return bool  true.成功,false.失败
 *
 * @example
 *      $data = array(
 *          user_id             => 驳回操作人ID
 *          user_name           => 驳回操作人名称
 *          reject_type_id      => 驳回类型ID,1是需求驳回,2是采购单驳回等
 *          link_id             => 涉及到的主键ID
 *          link_code           => 涉及到的编码,例如采购单号，需求单号等
 *          reject_remark       => 原因备注
 *          reject_time         => 操作时间（exp.2018-11-23 16:16:16  默认当前时间）
 *      )
 */
if (!function_exists('rejectNoteInsert')) {
    function rejectNoteInsert($new_data)
    {
        $CI = &get_instance();
        $CI->load->model('reject_note_model');

        return $CI->reject_note_model->insert_one_note($new_data);
    }
}

/**
 * 查询用户审核记录、驳回备注 日志
 * @author Jolon
 * @param array $query 查询条件
 * @param bool $is_only 只查询最新一条
 * @return bool|array   array.结果集，false.查询条件缺失     *
 *
 * @example
 *      $query = array(
 *          user_id             => 驳回操作人ID
 *          user_name           => 驳回操作人名称
 *          reject_type_id      => 驳回类型ID,1是需求驳回,2是采购单驳回等
 *          link_id             => 涉及到的主键ID
 *          link_code           => 涉及到的编码,例如采购单号，需求单号等
 *     )
 */
if (!function_exists('rejectNoteQuery')) {
    function rejectNoteQuery($query, $is_only = false)
    {
        $CI = &get_instance();
        $CI->load->model('reject_note_model');

        return $CI->reject_note_model->query_logs($query, $is_only);
    }
}

/**
 * 表数据变更 记录日志 便捷方法 (tables_change_model->insert_one_log)
 * @author Jolon
 * @param array $new_data 参考tables_change_model->insert_one_log
 * @return mixed
 *
 * @example
 *      $new_data = array(
 *          record_number   => 目标记录编号（int|string）
 *          table_name      => 操作的表名称
 *          change_type     => 操作类型（1.插入,2.更新,3.删除）
 *          content         => 改变的内容（详细信息，保存为 serialize 处理的结果）
 *          user            => 操作人（默认当前用户）
 *          time            => 操作时间（exp.2018-12-27 16:16:16  默认当前时间）
 *          ip              => 操作IP（默认当前用户IP）
 *          route           => 操作路由（默认当前路由）
 *      )
 */
if (!function_exists('tableChangeLogInsert')) {
    function tableChangeLogInsert($new_data)
    {
        $CI = &get_instance();
        $CI->load->model('tables_change_model');

        return $CI->tables_change_model->insert_one_log($new_data);
    }
}

/**
 * 添加 API 计划任务分页查询记录器
 * @author Jolon
 * @param array $data   要保存的数据
 * @return bool  true.成功,false.失败
 *
 * @example
 *      $data = array(
 *          page            => 当前页码
 *          api_type        => 操作类型（如 操作的数据表名或 控制器相对路径）
 *          create_time     => 操作时间（exp.2018-11-23 16:16:16  默认当前时间）
 *      )
 */
if (!function_exists('apiPageCircleInsert')) {
    function apiPageCircleInsert($data)
    {
        $CI = &get_instance();
        $CI->load->model('operator_log_model');
        return $CI->operator_log_model->insert_api_page_circle($data);
    }
}

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
                if (empty($data['errorMess'])) {
                    $data['errorMess'] = $error_code_conf[$data['errorCode']];
                }
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
        if (!isset($paging_data['pages']) and !empty($paging_data['total']) and !empty(intval($paging_data['limit']))) {
            $paging_data['pages'] = ceil($paging_data['total'] / intval($paging_data['limit']));// 获取总页数
        }
        $return_data['page_data'] = $paging_data;
    }
    return $return_data;
}

/**
 * 生成 指定前缀的 最新编号（自动更新编号记录）
 *      便捷方法 (prefix_number_model->get_prefix_new_number)
 * @author Jolon
 * @param string $prefix 前缀
 * @param int $add_number 增量（默认 1）
 * @param int       $fixed_length   编号长度（默认 6，用来填充）
 * @return bool|string
 */
function get_prefix_new_number($prefix, $add_number = 1,$fixed_length = 6)
{
    $CI = &get_instance();
    $CI->load->model('prefix_number_model'); // 数据表前缀

    $new_number = $CI->prefix_number_model->get_prefix_new_number($prefix, $add_number,$fixed_length);
    return $new_number;
}

/**
 * 权限控制（根据当前登录用户ID获取自己及其下属的用户ID）
 * @author harvin
 * **/
function jurisdiction(){
    $CI = &get_instance();
    $CI->load->model('user/User_group_model'); 
    $user_id= getActiveUserId();//用户ID
    $userid=$CI->User_group_model->get_jurisdiction($user_id);
    if(empty($userid)){
      $userid[]=ORDER_CANCEL_SORSTATUS;
    }
    return $userid;
}

/**
 * 数据权限控制（根据当前登录用户ID获取自己所属的组）
 * @return array|bool 超级数据组用户直接返回true，其他类型小组返回指定业务线ID
 * @author Jolon
 */
if(!function_exists('user_group_check')){
function user_group_check(){
    $CI = &get_instance();
    $CI->load->model('user/User_group_model');
    $user_id= getActiveUserId();//用户ID
    $view_purchase_type = $CI->User_group_model->user_group_check($user_id);
    return $view_purchase_type;
}
}

    /**
     * 获取登录用户角色  OA拉取
     * @author harvin
     */
    function get_user_role(){
        //获取用户角色
        $user_role= getActiveUserRole(); 
        if(empty($user_role['role_data'])){
            return [];
        }
        foreach ($user_role['role_data'] as $v) {
            $role[]=$v['name'];
        }  
        return $role;
    }

/**
 * 格式化金额 输出
 * @author Jolon
 * @param float $price 金额
 * @param int $point 小数点位数
 * @param bool $thousands_sep 是否添加千位分隔符
 * @return float
 */
function format_price($price,$point = 3,$thousands_sep = false)
{
    if($thousands_sep === true){
        return number_format((float)$price, $point, '.', ',');
    }else{
        return number_format((float)$price, $point, '.', '');
    }
}

/**
 * 格式化金额 输出两位小数
 * @author Jolon
 * @param float $price 金额
 * @return float
 */
function format_two_point_price($price)
{
    return format_price($price,2);
}

/**
 * 去除小数点末尾的0（可以去除 浮点数或字符串类型的浮点数）
 * @author Jolon
 * @param mixed $val
 * @param bool $remove_zero     移除末尾的0
 * @param bool $thousands_sep   是否添加千位分隔符
 * @param int $decimals         保留小数点位数
 * @return mixed
 */
function format_price_floatval($val,$remove_zero = true,$thousands_sep = false,$decimals = 3){
    // 不改变原始数据类型
    if(is_float($val)){
        if(true === $remove_zero){
            $val = floatval($val);
        }
        $val = format_price($val,$decimals,$thousands_sep);
    }elseif(is_string($val) && preg_match("/^[-]?[\d]+\.[\d]+$/",$val)){
        $val = format_price($val,$decimals,$thousands_sep);
        if(true === $remove_zero){
            $val = rtrim($val,'0');
            $val = rtrim($val,'.');
        }
    }

    return $val;
}

/**
 * 去除小数点末尾的0（可以去除 浮点数或字符串类型的浮点数）
 *      比 format_price_floatval 更高级，支持递归去除多维数组
 * @author Jolon
 * @param mixed $origin_data_list
 * @param bool $remove_zero    移除末尾的0
 * @param bool $thousands_sep  是否添加千位分隔符
 * @param int $decimals        保留小数点位数
 * @return array|mixed
 */
function format_price_multi_floatval($origin_data_list,$remove_zero = true,$thousands_sep = false,$decimals = 3){
    $data_list_tmp = [];
    if(is_array($origin_data_list)){
        foreach($origin_data_list as $key => $value){
            if(is_array($value)){
                $data_list_tmp[$key] = format_price_multi_floatval($value,$remove_zero,$thousands_sep,$decimals);// 递归
            }else{
                $data_list_tmp[$key] = format_price_floatval($value,$remove_zero,$thousands_sep,$decimals);
            }
        }
    }else{
        $data_list_tmp = format_price_floatval($origin_data_list,$remove_zero,$thousands_sep,$decimals);
    }

    return $data_list_tmp;
}

/**
 * 验证是否最多为2位小数 不是的话则报错
 * @author jeff
 * @param float $data 金额
 * @return bool
 */
function is_two_decimal($data)
{
    $data = (float)$data;
    $data = abs($data);
    if (preg_match('/^[0-9]+(.[0-9]{1,2})?$/', $data)) {
        return true;
    }else{
        return false;
    }
}

//调试打印
if (!function_exists('pr')) {
    function pr($arr, $escape_html = true, $bg_color = '#EEEEE0', $txt_color = '#000000')
    {
        echo sprintf('<pre style="background-color: %s; color: %s;">', $bg_color, $txt_color);
        $pr_location = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        echo sprintf('print from %s 第%d行 <br/>', $pr_location['file'], $pr_location['line']);
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
        $remote = $_SERVER["REMOTE_ADDR"] ?? '127.0.0.1';
        return ($ip ? $ip : $remote);

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

if( !function_exists('http_send')) {

    function send_http( $url, $data = NULL ) {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        if(!$data){
            return 'data is null';
        }
        if(is_array($data))
        {
            $data = json_encode($data);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER,array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen($data),
            'Cache-Control: no-cache',
            'Pragma: no-cache'
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($curl);
        $errorno = curl_errno($curl);
        if ($errorno) {
            return $errorno;
        }
        curl_close($curl);
        return $res;
    }
}
if(!function_exists('get_microtime')){
    function get_microtime($is_H=false){
        $a = microtime();
        $b = explode(" ", $a);
        if($is_H){
            return date("H:i:s")."-".($b[0] * 1000);
        }
        return date("Ymd-H:i:s")."-".($b[0] * 1000);
    }
}
if (!function_exists('getCurlData')) {
    function getCurlData($curl, $Data, $method = 'post', $header = '',$type=false,$time_out=array())
    {
        try{

            $ch = curl_init(); //初始化
            curl_setopt($ch, CURLOPT_URL, $curl); //设置访问的URL
            curl_setopt($ch, CURLOPT_HEADER, false); // false 设置不需要头信息 如果 true 连头部信息也输出
            curl_setopt($ch, CURLE_FTP_WEIRD_PASV_REPLY, true);
            if($time_out){
                curl_setopt($ch, CURLOPT_TIMEOUT, isset($time_out['time_out'])?$time_out['time_out']:30); //设置成秒
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, isset($time_out['conn_out'])?$time_out['conn_out']:30);
            }else{
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);//设置成秒
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
            }

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            if($type){
                curl_setopt($ch, CURLOPT_USERPWD, OA_ACCESS_TOKEN_USERPWD); //auth 验证  账号及密码
            }

            if($header){// 所有请求添加 组织代码
                $header[] = 'org:'.PMS_ORG_CODE;
            }else{
                $header = ['org:'.PMS_ORG_CODE];
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

            $res_data = mb_substr($datas, 0, 100, 'utf-8');
            if(empty($datas) and $datas === false){
                $error = curl_error($ch);
                var_dump($error);exit;
            }

            curl_close($ch); //关闭curl，释放资源
            return $datas;
        }catch (\Exception $e){
            $Exception = $e->getMessage();
        } catch (\Error $e) {
            $Error = $e->getMessage();
        }

        return '';
    }
}

if (!function_exists('filter_array_none')) {
    /**
     * 去除多维数组中的空值
     * @author Jolon
     * @param array $arr 目标数组
     * @param array $values 去除的元素（默认去除  '',null,false,[]）
     * @return mixed
     */
    function filter_array_none($arr, $values = ['', null, false, []])
    {
        foreach ($arr as $k => $v) {
            if (is_array($v) && count($v) > 0) {
                $arr[$k] = filter_array_none($v, $values);
            }
            foreach ($values as $value) {
                if ($v === $value) {
                    unset($arr[$k]);
                    break;
                }
            }
        }
        return $arr;
    }
}

/**
 * 查询数据 条数限制
 * @author Jolon
 * @param int $limit 条数
 * @param bool $force 设为false 则原样返回
 * @return mixed
 */
function query_limit_range($limit = null, $force = true)
{
    if ($force !== true) return $limit;

    if (empty($limit) or intval($limit) < 0) $limit = DEFAULT_PAGE_SIZE;// 最小值
    if ($limit > MAX_PAGE_SIZE) $limit = MAX_PAGE_SIZE;// 最大值

    return intval($limit);
}


/**
* 三维数组转换为二维数组
* @param array $arr
* @return array
*/
function ThereArrayTwo($arr = [])
{
    $newArr = array();
    foreach ($arr as $key => $val) {
        foreach ($val as $k => $v) {
            $newArr[] = $v;
        }
    }
    return $newArr;
}

/**
 * @desc 同仓库数据交互的加密签名算法
 * @return array
 */

function stockAuth()
{
    //$this->load->model('system/token_model');
    $CI = &get_instance();
    $CI->load->model('system/token_model');
    $data = array('error' => -1);

    //设置param数组的值
    $param['key'] = UEB_STOCK_KEYID;
    $param['timestamp'] = time();
    $param['ip'] = '';
    ksort($param, SORT_REGULAR);
    $urlStr = http_build_query($param, 'yibai_', '&', PHP_QUERY_RFC1738);
    $token_info = $CI->token_model->get_token();
    $token =$token_info['token'];
    $securityStr = md5($token . $urlStr . $token, false);
    if (!empty($securityStr)) {
        $data['param'] = $param;
        $data['sign'] = $securityStr;
        $data['error'] = 1;
    }

    return $data;
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
 * 查询字符串转成 数组
 * @param string $string
 * @param bool $strict 严格模式（true.去除空元素）
 * @return array
 */
function query_string_to_array($string, $strict = true)
{
    $string = str_replace(' ', ',', trim($string));
    $string = str_replace('，', ',', trim($string));
    $arr = array_unique(explode(',', $string));
    if ($strict) {
        $arr = array_filter($arr);
    }

    if (empty($arr)){
        $arr = [''];
    }
    return $arr;
}

/**
 * 数据拼接成 SQL查询字符串
 * @param array $arr
 * @param bool $strict 严格模式（true.去除空元素）
 * @return string
 */
function query_array_to_string($arr, $strict = true)
{
    if ($strict) {
        $arr = array_filter($arr);
    }

    if (empty($arr)) return '';

    $string = "'" . implode("','", $arr) . "'";
    return $string;
}


/**
 * 根据日期获取该日期的月开始时间和月结束时间
 * @param date $datetime
 * @param bool $strict 严格模式（true.去除空元素）
 * @return array()
 */
function get_month_time($datetime)
{
    $month_time_arr = array();
    $timestamp = strtotime( $datetime );
    $start_time = date( 'Y-m-01 00:00:00', $timestamp );
    $mdays = date( 't', $timestamp );
    $end_time = date( 'Y-m-' . $mdays . ' 23:59:59', $timestamp );
    $month_time_arr['start_time'] = $start_time;
    $month_time_arr['end_time'] = $end_time;
    return $month_time_arr;
}



if (!function_exists('gp')) {
    /**
     * 描述:接受数据
     * @author Jackson
     * @param string $index
     * @return mixed
     */
    function gp($index = '')
    {
        $CI =&get_instance();
        if (empty($index)) {
            $method = $_SERVER['REQUEST_METHOD'];
            return $CI->input->$method() ? $CI->input->$method() : array();
        }
        return $CI->input->get_post($index, true);
    }
}


/**
 * 获取 ERP展示SKU资料界面地址（开发未完结）
 * @author Jolon
 * @param $sku
 * @return string  SKU资料地址
 */
function jump_url_product_base_info($sku){
    $sub_url = '/services/api/system/index/method/getimage/sku/'.$sku;

    return ERP_DOMAIN.$sub_url;
}

/**
 * 获取 ERP SKU图片地址
 * @author Jolon
 * @param $img_url
 * @return $sku_img_url string  SKU资料地址
 */
function erp_sku_img_sku($img_url=''){
//    return false ;// 接口已经废弃，改由JAVA同步 Jolon 2020-05-26 14:38:00
    $sku_img_url = '';
    //带http的地址
//    $img_url = "http://product.yibainetwork.com/end/upload/image/assistant_no_logo/3111210195811/3111210195811-1.jpg";
    //不带http的地址
//    $img_url = "/end/upload/image/assistant_no_logo/1411210228611/1411210228611-1.jpg";
    if(is_array($img_url))return $img_url;
    if(!empty($img_url) && is_string($img_url)){
        $img_arr = parse_url($img_url);
        if(!empty($img_arr['path'])){
            $sku_img_url = PRODUCT_SKU_IMG_URL.$img_arr['path'];
        }
    }
    return $sku_img_url;
}

/**
 * 获取 ERP SKU 缩略图片地址
 * @author Jolon
 * @param $thumb_img_url
 * @return string  SKU资料地址
 * @exp
 *     产品原图地址：http://images.yibainetwork.com/services/api/system/index/method/getimage/sku/GS23099
 *     产品缩略图地址：http://images.yibainetwork.com//upload/image/Thumbnails/GS23099/GS23099-1.jpg
 */
function erp_sku_img_sku_thumbnail($thumb_img_url=''){
//    return false ;// 接口已经废弃，改由JAVA同步 Jolon 2020-05-26 14:38:00
//    $sku_thumbnail_img_url = ERP_SKU_IMG_URL_THUMBNAILS.$sku.DIRECTORY_SEPARATOR.$sku.'-1.jpg';// 固定格式
    $sku_img_url = '';
    if(is_array($thumb_img_url))return $thumb_img_url;
    if(!empty($thumb_img_url) && is_string($thumb_img_url)){
        $img_arr = parse_url($thumb_img_url);
        if(!empty($img_arr['path'])){
            $sku_img_url = PRODUCT_SKU_IMG_URL_THUMBNAILS.$img_arr['path'];
        }
    }
    return $sku_img_url;
}

/**
 * 判断请求URL是否有效
 * @param $url
 * @return bool
 */
function checkUrlIsValid($url){
    $result = get_headers($url,1);
    if(preg_match('/200/',$result[0])){
        return true;
    }else{
        return false;
    }
}

if (!function_exists('is_same_data')) {
    /**
     * 判断批量支付是否是同一个申请人的请款数据
     * @author jackson
     * @param $arr
     * @return Boolean
     */
    function is_same_data($arr)
    {
        if (is_array($arr)) {
            $flag = true;
            $first = $arr[0];
            foreach ($arr as $v) {
                if ($first !== $v) {
                    $flag = false;
                    break;
                }
            }
            return $flag;
        } else {
            return false;
        }
    }
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


/**
 * 加载 配置文件，读取配置文件内容
 * @author Jolon
 * @param string $config_file  配置文件名，如api_config
 * @param string $first_name   多维数组，第一维
 * @param string $second_name  多维数组，第二维
 * @param string $thirdly_name 多维数组，第三维
 * @return string
 */
function getConfigItemByName($config_file = null,$first_name = null,$second_name = null,$thirdly_name = null){
    $CI = & get_instance();

    if(in_array(strtolower($config_file),explode(',',PMS_ORG_CODE_CONFIG_FILES))) return false;

    // 加载配置文件
    if(!is_null($config_file)) $CI->load->config($config_file);

    if(is_null($first_name)) return $CI->config->config;

    $first_name_data = $CI->config->item($first_name);
    if($first_name_data){
        // 读取 第二维
        if(!is_null($second_name) and isset($first_name_data[$second_name])){
            $second_name_data = $first_name_data[$second_name];
            // 读取 第三维
            if(!is_null($thirdly_name) and isset($second_name_data[$thirdly_name])){
                $thirdly_name_data = $second_name_data[$thirdly_name];
                return $thirdly_name_data;
            }
            return $second_name_data;
        }

        return $first_name_data;
    }else{
        return null;
    }
}

/**
 * 二维数组 使用数组中指定的键  作为键名
 * @author Jolon
 * @param $array
 * @param $index
 * @return array|bool
 */
function arrayKeyToColumn($array,$index){
    $array_tmp = [];
    if($array and is_array($array)){
        foreach($array as $key => $value){
            if(!isset($value[$index])){
                continue;
            }
            $array_tmp[$value[$index]] = $value;
        }
    }
    return $array_tmp;
}

/**
 * 二维数组 使用数组中指定的键  作为键名（支持存在多个键的值）
 * @author Jolon
 * @param $array
 * @param $index_first
 * @param $index_second
 * @return array|bool
 */
function arrayKeyToColumnMulti($array,$index_first,$index_second = null){
    $array_tmp = [];
    if($array and is_array($array)){
        foreach($array as $key => $value){
            if(!isset($value[$index_first])){
                continue;
            }
            if(!isset($value[$index_second]) or is_null($index_second)){
                $array_tmp[$value[$index_first]][] = $value;
            }else{
                $array_tmp[$value[$index_first]][$value[$index_second]] = $value;
            }
        }
    }
    return $array_tmp;
}

/**
 * 批量验证 字符串 是否含有 子字符串
 * @author Jolon
 * @param    string $haystack   要搜索的字符串
 * @param array     $needle_arr 子字符串
 * @param bool      $case_insensitive  是否区分大小写（true.否，false.是）
 * @return bool  true.只要匹配到任何一个,false.所有的子字符串都没有匹配到
 */
function multiStrPos($haystack, $needle_arr,$case_insensitive = true){
    if(empty($haystack) or empty($needle_arr) or !is_array($needle_arr)) return false;

    $flag = false;
    foreach($needle_arr as $needle){
        if($case_insensitive){
            if(stripos($haystack,$needle) !== false) $flag = true;
        }else{
            if(strpos($haystack,$needle) !== false) $flag = true;
        }
        if($flag) break;
    }
    return $flag;

}

/**
 * @desc 付款提醒状态格式化
 * @author Jeff
 * @Date 2019/03/16 18:49
 * @param $account_type int 结算类型
 * @param $pay_status int 支付状态
 * @param $need_pay_time string 应支付时间
 * @param $surplus_quota int 供应商可用账期额度
 * @return string
 */
function formatAccoutPeriodTime($account_type, $pay_status, $need_pay_time = null, $surplus_quota = 0){
    if ($pay_status == PAY_PAID || $account_type != SUPPLIER_SETTLEMENT_CODE_TAP_DATE){
        return '';
    }

    if($surplus_quota <= 0){
        return '额度已满，需紧急支付';
    }

    if (empty($need_pay_time) || $need_pay_time == '0000-00-00 00:00:00'){
        return '';
    }

    $now = date('Y-m-d H:i:s');

    if ($need_pay_time < $now){
        return '已超期';
    }

    $time_stamp_np = strtotime($need_pay_time);

    if ($need_pay_time >= $now && $time_stamp_np < strtotime('+5 days')){
        return '即将到期';
    }

    if ($time_stamp_np >= strtotime('+5 days')){
        return '可继续等待';
    }

}

/**
 * 抛线程（执行方法时间过长 程序自动退出错误）
 * @author Jolon
 * @param        $url
 * @param array  $params
 * @param string $type
 * @param int    $timeout
 * @return bool
 */
function throwThreader($url, $params = array(), $type = 'GET', $timeout = 60) {
    $urlInfo = parse_url($url);
    if (!isset($urlInfo['host']) || empty($urlInfo['host']))
        $urlInfo = parse_url($_SERVER['HTTP_HOST']);
    $host = isset($urlInfo['host']) ? $urlInfo['host'] : $_SERVER['HTTP_HOST'];
    $scheme = isset($urlInfo['scheme']) ? $urlInfo['scheme'] : '';
    $hostStr = $scheme . "://" . $host;
    $uri = str_replace($hostStr, '', $url);
    $port = isset($urlInfo['port']) ? $urlInfo['port'] : '80';
    if (empty($host))
        return false;
    $socket = fsockopen($host, $port, $errno, $error, $timeout);
    if (!$socket)
        return false;
    stream_set_blocking($socket, false);
    $data = '';
    $body = '';
    if (is_array($params)) {
        foreach ($params as $key => $value)
            $data .= strval($key) . '=' . strval($value) . '&';
    } else
        $data = $params;
    $header = '';
    if ($type == 'GET') {
        if (strpos($uri, '?') !== false) {
            $uri .= '&' . $data;
        } else {
            $uri .= '?' . $data;
        }
        $header .= "GET " . $uri . ' HTTP/1.0' . "\r\n";
    } else {
        $header .= "POST " . $uri . ' HTTP/1.0' . "\r\n";
        $header .= "Content-length: " . strlen($data) . "\r\n";
        $body = $data;
        //$header .=
    }
    $header .= "Host: " . $host . "\r\n";
    $header .= 'Cache-Control:no-cache' . "\r\n";
    $header .= 'Connection: close' . "\r\n\r\n";
    $header .= $body;
    //file_put_contents('./test.log', $header . "\r\n\r\n", FILE_APPEND);
    fwrite($socket, $header, strlen($header));
    usleep(300);   //解决nginx服务器连接中断的问题
    fclose($socket);
    return true;
}

/**
 * @desc 返回格式化后的字符串 如 "aaa123","bbb123","ccc123"
 * @author Jeff
 * @Date 2019/6/1 14:17
 * @param $data
 * @return
 */
function format_query_string($data){
    $string = '';
    foreach ($data as $value){
        $string .= '"'.$value.'",';
    }
    $string = rtrim($string,",");
    return $string;
}

//取消未到货取消访问方法
if (!function_exists('getCancelCurlData')) {
    function getCancelCurlData($curl, $Data, $method = 'post', $header = '',$type=false)
    {
        $ch = curl_init(); //初始化
        curl_setopt($ch, CURLOPT_URL, $curl); //设置访问的URL
        curl_setopt($ch, CURLOPT_HEADER, false); // false 设置不需要头信息 如果 true 连头部信息也输出
        curl_setopt($ch, CURLE_FTP_WEIRD_PASV_REPLY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);//设置成秒
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        if($type){
            curl_setopt($ch, CURLOPT_USERPWD, OA_ACCESS_TOKEN_USERPWD); //auth 验证  账号及密码
        }

        $header[] = 'org:'.PMS_ORG_CODE;// 所有请求添加 组织代码
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //只获取页面内容，但不输出
        if (strtolower($method) == 'post') {
            curl_setopt($ch, CURLOPT_POST, true); //设置请求是POST方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $Data); //设置POST请求的数据
        }
        $datas = curl_exec($ch); //执行访问，返回结果

        if(empty($datas) and $datas === false){
            $error = curl_error($ch);
            var_dump($error);exit;
        }

        curl_close($ch); //关闭curl，释放资源
        return $datas;
    }
}

/**
 *开票单位有2个单位，且其中一个=kg或者千克，那么需要删掉=kg或者千克。
 * @param:$issuing   string   开票单位
 **/

if(!function_exists('deleteProductData')){

    function deleteProductData( $issuing )
    {
        $declare_units = explode("/",$issuing);

        if( count($declare_units) >=2 )
        {
            $issuing = str_replace("千克/"," ",$issuing);
            $issuing = str_replace("/千克"," ",$issuing);
            $issuing = str_replace("kg/"," ",$issuing);
            $issuing = str_replace("/kg"," ",$issuing);
        }
        return $issuing;
    }
}

/**
 * 将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
 * @access public
 * @param string $str 待转换字串
 * @return string $str 处理后字串
 */

if(!function_exists('make_semiangle')){
    function make_semiangle($str){
        $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4','５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9', 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E','Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O','Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T','Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y','Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd','ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i','ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n','ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z','（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[','】' => ']', '〖' => '[', '〗' => ']', '“' => '"', '”' => '"','‘［' => '[', '］' => ']', '｛' => '{', '｝' => '}', '《' => '<','》' => '>','％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-','：' => ':', '。' => '.', '、' => ',', '，' => '.', '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',  '｀' => '`', '‘' => '`', '｜' => '|', '〃' => '"','　' => ' ',''=> '',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'',''=>'','	'=>'');
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
 * 判断正整数和正数
 * @param $num
 * @param int $case 1: 正整数（包含0）;2:大于0的数;3：正整数（不包含0）; 4:正小数 ; 5: 0>x>=1 ;6: 正数和0
 */
if (!function_exists('positiveInteger')) {
    function positiveInteger(& $num, $case = 1){
        $res = false;
        $num = trim($num);
        if((($case == 1) && $num == 0 ) || $num ) {
            if(is_numeric($num)){
                switch ($case) {
                    case 1:
                        if ($num >= 0 && floor($num) == $num) {
                            $res = true;
                        }
                        break;
                    case 2:
                        if ($num > 0) {
                            $res = true;
                        }
                        break;
                    case 3:
                        if ($num >= 0 && floor($num) == $num) {
                            $res = true;
                        }
                        break;
                    case 4:
                        if($num >0 && floor($num)!=$num){
                            $res = true;
                        }
                        break;
                    case 5:
                        if($num >0 && $num<=1){
                            $res = true;
                        }
                        break;
                }
                return $res;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }
}


function pr_memory(){
    echo round(memory_get_usage() / 1024 / 1024, 2).'MB'."<br/>";
}

function validate_empty($data,$validate_arr)
{
    $return_result = [];
    $return_result['status'] = 0;
    if (empty($data) || empty($validate_arr)){
        $return_result['status'] = 0;
        $return_result['errorMess'] = '调用validate_empty方法的参数不能为空';
        return $return_result;
    }
    foreach ($validate_arr as $v_key => $value){
        if (!isset($data[$v_key])){
            $return_result['status'] = 0;
            $return_result['errorMess'] = sprintf('%s不能为空',$value);
            return $return_result;
        }
    }


    $return_result['status'] = 1;
    return $return_result;
}
// 获取角色配置信息
if( !function_exists('getRoleMessage')){

    function getRoleMessage(){

        $message =[

            ['role_name'=>'采购主管','flag'=>'executive_director','status'=>PRODUCT_EXECUTIVE_DIRECTOR],
            ['role_name'=>'采购副经理','flag'=>'deputy_manager','status'=>PRODUCT_DEPUTY_MANAGER],
            ['role_name'=>'采购经理','flag'=>'purchasing_manager','status'=>PRODUCT_PURCHASING_MANAGER],
            ['role_name'=>'开发经理','flag'=>'Development_Manager','status'=>PRODUCT_DEVELOPMENT],
            ['role_name'=>'供应商管理部门','flag'=>'supplier','status'=>PRODUCT_UPDATE_LIST_PURCHASE],
            ['role_name'=>'供应链总监','flag'=>'supplier_director','status'=>PRODUCT_SUPPLIER_DIRECTOR]

        ];
        return $message;
    }
}

/**
 * 使用生成器迭代
 * @param $data
 * @return Generator
 */
if (!function_exists('yieldData')) {
    function yieldData($data)
    {
        foreach ($data as $val) {
            yield $val;
        }
    }
}

/**
 * 删除数组中指定值的元素
 * @param array $arr 数组
 * @param string $val 值
 * @return boolean
 */
function arrayDelElementByVal(&$arr, $val)
{
    if (empty($val)) return false;
    $key = array_search($val, $arr);
    $keys = array_keys($arr);
    $position = array_search($key, $keys);
    if (false !== $position) {
        array_splice($arr, $position, 1);
        return true;
    }
    return false;
}

/**
 * 清除数组所有字符串元素两边的空格
 * @param $input
 * @return array|string
 */
function TrimArray($input)
{
    if (!is_array($input)) return trim($input);
    return array_map('TrimArray', $input);
}

/**
 * 把数组中的所有元素转换成字符串
 * @param $input
 * @return array|string
 */
function ArrayElementsCTS($input)
{
    if (!is_array($input) && is_numeric($input)) return (string)$input;
    return array_map('ArrayElementsCTS', $input);
}

/**
 * 检测是否设置或为空
 * se isset and not empty
 * s isset
 * e not empty
 * n is_numeric
 * nn not null
 */
if(!function_exists("SetAndNotEmpty")){
    function SetAndNotEmpty($param=[], $str = '', $type = "se")
    {
        if(($type == 'se' || $type=='e') && isset($param[$str]) && !empty($param[$str]))return true;
        if($type == 'n' && isset($param[$str])){
            $param[$str] = trim($param[$str]);
            if(is_numeric($param[$str]))return true;
        }
        if($type == 'ar' && isset($param[$str]) && !empty($param[$str]) && is_array($param[$str]))return true;
        if($type == 's' && isset($param[$str]))return true;
        if($type == '!s' && !isset($param[$str]))return true;
        if($type == 'nn' && !isset($param[$str]) && $param[$str] != null)return true;
        return false;
    }
}