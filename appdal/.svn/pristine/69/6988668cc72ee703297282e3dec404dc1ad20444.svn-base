<?php

//defined('BASEPATH') OR exit('No direct script access allowed');

/**
 *
 * Class Rediss
 *
 * 注意，因$this->redis已被CI使用，故此处Redis类命名以Rediss命名，请勿将类名更改为Redis
 *
 * @desc 此类主要提供三个方法操作redis
 *       setData:保存redis
 *       getData:获取redis
 *       deleteData:删除redis
 * @authoer 凌云
 * @since 2018-06-07
 *
 */
class Rediss {

    /**
     * 保存到redis的key前缀
     */
    private $_prefix = 'PUR_WEB_REDIS_EXPRESS_';

    /**
     * 有效期
     */
    private $_expire_time = '4800';

    /**
     * CI实例
     * @var 	object
     */
    private $_ci;

    /**
     * redis 连接
     * @var		handle
     */
    private $_connection;

    /**
     * 定义当前类是否为debug模式
     * @var		bool
     */
    public $debug = FALSE;

    const CRLF = "\r\n";

    public function __construct($params = array()) {
        //将Redis 初始化写入日志，异常时可以开启
//		log_message('debug', 'Redis Class Initialized');
        $this->_ci = get_instance();
        //获取redis配置
        $this->_ci->load->config('redis');

        //如果初始化时有带redis配置，以初始化参数为redis配置
        if (isset($params['connection_group'])) {
            $config = $this->_ci->config->item('redis_' . $params['connection_group']);
        } elseif (is_array($this->_ci->config->item('redis_default'))) {
            //默认的redis配置
            $config = $this->_ci->config->item('redis_default');
        } else {
            //备用的redis配置
            $config = $this->_ci->config->item('redis_default');
        }

        // 连接redis
        $this->_connection = @fsockopen($config['host'], $config['port'], $errno, $errstr, 3);

        if (!$this->_connection) {
            show_error('Could not connect to Redis at ' . $config['host'] . ':' . $config['port']);
        }

        // redis连接授权认证
        $this->_auth($config['password']);
        //指定连接几号库默认是0号库
        $this->select(0);
    }
    /**
     * hash表中是否包含某键HEXISTS
     */
    public function checkHashData($table=null, $field=null, $value=null)
    {
        if(empty($table) || empty($field))return false;
        if(!empty($value))return $this->hexists($table, $field, $value);
        return $this->hexists($table, $field);
    }

    /**
     * 新增hash表数据
     */
    public function addHashData($table=null, $field=null, $value=null)
    {
        if(empty($table) || empty($field) || empty($value))return false;
        if($this->hset($table, $field, $value))return true;
        return false;
    }

    /**
     * 获取hash数据
     */
    public function getHashData($table=null, $field=null)
    {
        if(empty($table) || empty($field))return false;
        $data = $this->hmget($table, $field);
        if(!empty($data))return json_encode($data, true);
        return false;
    }

    /**
     * 删除hash字段
     */
    public function delHashData($table=null, $field=null)
    {
        if(empty($table) || empty($field))return false;
        $data = $this->hdel($table, $field);
        if(!empty($data))return true;
        return false;
    }

    /**
     * 将数据保存到redis
     * @param string $key
     * @param string|array $value
     * @param int $expire_time
     * @return bool|void
     */
    public function setData($key = '', $value = '', $expire_time = 0) {

        if (empty($key) || empty($value))
            return false;

        /**
         * 判断时间是否为数值，若非数值使用默认时间
         */
        if (!is_numeric($expire_time) || $expire_time == 0) {
            $expire_time = $this->_expire_time;
        }

        //防止存在小数,向上取整
        $expire_time = ceil($expire_time);

        /**
         * 如果要保存的数据为数组格式
         * 转换为序列化字符串存储
         */
        if (is_array($value))
            $value = serialize($value);
        $this->set($this->_prefix . $key, $value);
        $this->expire($this->_prefix . $key, $expire_time);
    }
    /**
     * 获取redis
     * @param string $key
     * @return bool|mixed
     */
    public function getData($key = '') {

        if (empty($key))
            return false;
        $data = $this->get($this->_prefix . $key);
        if (is_serialized($data)) {
            $data = unserialize($data);
        }
        return $data;
    }

    /**
     * redis队列头部加入
     * @param string $key
     * @author harvin 2019-1-5
     * @return bool|void
     * * */
    public function lpushData($key = "", $value = '', $expire_time = 0) {
        if (empty($key) || empty($value))
            return false;
        /**
         * 判断时间是否为数值，若非数值使用默认时间
         */
        if (!is_numeric($expire_time) || $expire_time == 0) {
            $expire_time = $this->_expire_time;
        }

        //防止存在小数,向上取整
        $expire_time = ceil($expire_time);

        /**
         * 如果要保存的数据为数组格式
         * 转换为序列化字符串存储
         */
        if (is_array($value))
            $value = serialize($value);
        $res = $this->lpush($this->_prefix . $key, $value);
        $this->expire($this->_prefix . $key, $expire_time);
        return $res;
    }
    /**
     * 按下标获取list中的数据
     * @author yefanli
     * @return mixed
     */
    public function lrangeData($key="", $start=0, $end=0)
    {
        if(empty($key) || ($start==0 && $end==0))return false;
        return $this->lrange($this->_prefix.$key, $start, $end);
    }

    /**
     * redis 队列获取头部弹出
     * @param string $key
     * @return bool|mixed
     * @author harvin 2019-1-5
     * * */
    public function lpopData($key="") {
        if (empty($key))
            return false;
        $data = $this->lpop($this->_prefix . $key);

        if (is_serialized($data)) {
            $data = unserialize($data);
        }

        return $data;
    }

    /**
     * redis 队列获取尾部弹出
     * @param string $key
     * @return bool|mixed
     * @author harvin 2019-1-5
     * * */
    public function rpopData($key="") {
        if (empty($key))
            return false;
        $data = $this->rpop($this->_prefix . $key);

        if (is_serialized($data)) {
            $data = unserialize($data);
        }

        return $data;
    }

    /**
     * set  命令在指定的 key 不存在时，为 key 设置指定的值。
     * 作用：处理分布式锁
     **/

    public function call_setnx($key,$value,$expire=100){

        $keys = $this->_prefix.$key;
        $result = $this->set($keys, $value, ['nx', 'ex' => $expire]);
        if($result){
            return True;
        }

        return False;
    }

    /**
     * Redis 获取 List 的长度
     * @author Jolon 2019-04-24
     * @param string $key
     * @return bool|mixed
     */
    public function llenData($key = ""){
        if(empty($key))
            return false;
        $data = $this->llen($this->_prefix.$key);

        if(is_serialized($data)){
            $data = unserialize($data);
        }

        return $data;
    }

    /**
     * 向集合添加一个成员
     * @author Jolon 2019-10-30
     * @param $key
     * @param $member
     * @return bool
     */
    public function set_sadd($key,$member){
        if(empty($member)) return false;
        $status = $this->sadd($this->_prefix.$key,$member);

        return $status;
    }

    /**
     * 获取集合的成员数
     * @author Jolon 2019-10-30
     * @param $key
     * @return bool
     */
    public function set_scard($key){
        if(empty($key)) return false;
        $count = $this->scard($this->_prefix.$key);

        return $count;
    }
    /**
     * 判断成员元素是否是集合的成员。
     * @author Jolon 2020-12-21
     * @param $key
     * @return bool
     */
    public function set_sismember($key,$member){
        if(empty($key)) return false;
        $have = $this->sismember($this->_prefix.$key,$member);

        return $have;
    }

    /**
     * 返回集合中的所有成员
     * @author Jolon 2019-10-30
     * @param $key
     * @return bool
     */
    public function set_smembers($key){
        if(empty($key)) return false;
        $count = $this->smembers($this->_prefix.$key);

        return $count;
    }

    /**
     * 移除并返回集合中的一个随机元素
     * @author Jolon 2019-10-30
     * @param $key
     * @param $count
     * @return bool
     */
    public function set_spop($key,$count = 1){
        if(empty($key)) return false;
        $count = $this->spop($this->_prefix.$key,$count);

        return $count;
    }

    /**
     * 获取有序集合的成员数
     * @param $key
     * @return bool
     */
    public function zset_card($key){
        if(empty($key)) return false;
        $count = $this->ZCARD($this->_prefix.$key);
        return $count;
    }

    /**
     * 计算在有序集合中指定区间分数的成员数
     * @param $key
     * @param $min
     * @param $max
     * @return bool
     */
    public function zset_count($key,$min,$max){
        if(empty($key)) return false;
        $count = $this->zcount($this->_prefix.$key,$min,$max);
        return $count;
    }

    /**
     * 向有序集合添加一个成员
     * @param $key
     * @param $score
     * @param $member
     * @return bool
     */
    public function zset_add($key,$score,$member){
        if(empty($key) or empty($member)) return false;
        $status = $this->zadd($this->_prefix.$key,$score,$member);
        return $status;
    }

    /**
     * 移除有序集合中给定的分数区间的所有成员
     * @param $key
     * @param $min
     * @param $max
     * @return bool
     */
    public function zset_remrangebyscore($key,$min,$max){
        if(empty($key) or empty($max)) return false;
        $status = $this->zremrangebyscore($this->_prefix.$key,$min,$max);
        return $status;
    }

    /**
     * 删除redis
     * @param string $key
     * @return bool|void
     */
    public function deleteData($key = '') {
        if (empty($key))
            return false;
        return $this->del($this->_prefix . $key);
    }
    /**
     * 清空缓存
     * @return mixed
     */
    protected function deleteAll() {
        return $this->flushall();
    }
    /**
     * 判断是否有缓存键
     * **/
    public function existsData($key = '') {
        if (empty($key))
            return false;
        return $this->exists($this->_prefix . $key);

    }

    /**
     * 将 key 中储存的数字值增一
     * @param string $key
     * @return bool
     */
    public function incrData($key = '') {
        if (empty($key)) return false;
        return $this->incr($this->_prefix . $key);
    }
    /**
     * 将 key 中储存的数字加上指定的增量值。
     * @param string $key
     * @param int $incr_amount
     * @return bool
     */


    public function incrByData($key = '',$incr_amount=1) {
        if (empty($key)) return false;
        return $this->incrBy($this->_prefix . $key,$incr_amount);
    }
    /**
     * Call
     *
     * Catches all undefined methods
     * @param	string	method that was called
     * @param	mixed	arguments that were passed
     * @return 	mixed
     */
    public function __call($method, $arguments) {
        $request = $this->_encode_request($method, $arguments);
        return $this->_write_request($request);
    }

    /**
     * Command
     *
     * Generic command function, just like redis-cli
     * @param	string	full command as a string
     * @return 	mixed
     */
    public function command($string) {
        $slices = explode(' ', $string);
        $request = $this->_encode_request($slices[0], array_slice($slices, 1));

        return $this->_write_request($request);
    }

    /**
     * Auth
     *
     * Runs the AUTH command when password is set
     * @param 	string	password for the Redis server
     * @return 	void
     */
    private function _auth($password = NULL) {

        // Authenticate when password is set
        if (!empty($password)) {

            // See if we authenticated successfully
            if ($this->command('AUTH ' . $password) !== 'OK') {
                show_error('Could not connect to Redis, invalid password');
            }
        }
    }

    /**
     * Clear Socket
     *
     * Empty the socket buffer of theconnection so data does not bleed over
     * to the next message.
     * @return 	NULL
     */
    public function _clear_socket() {
        // Read one character at a time
        fflush($this->_connection);
        return NULL;
    }

    /**
     * Write request
     *
     * Write the formatted request to the socket
     * @param	string 	request to be written
     * @return 	mixed
     */
    private function _write_request($request) {
        if ($this->debug === TRUE) {
            log_message('debug', 'Redis unified request: ' . $request);
        }

        // How long is the data we are sending?
        $value_length = strlen($request);

        // If there isn't any data, just return
        if ($value_length <= 0)
            return NULL;


        // Handle reply if data is less than or equal to 8192 bytes, just send it over
        if ($value_length <= 8192) {
            fwrite($this->_connection, $request);
        } else {
            while ($value_length > 0) {

                // If we have more than 8192, only take what we can handle
                if ($value_length > 8192) {
                    $send_size = 8192;
                }

                // Send our chunk
                fwrite($this->_connection, $request, $send_size);

                // How much is left to send?
                $value_length = $value_length - $send_size;

                // Remove data sent from outgoing data
                $request = substr($request, $send_size, $value_length);
            }
        }

        // Read our request into a variable
        $return = $this->_read_request();

        // Clear the socket so no data remains in the buffer
        $this->_clear_socket();

        return $return;
    }

    /**
     * Read request
     *
     * Route each response to the appropriate interpreter
     * @return 	mixed
     */
    private function _read_request() {
        $type = fgetc($this->_connection);

        // Times we will attempt to trash bad data in search of a
        // valid type indicator
        $response_types = array('+', '-', ':', '$', '*');
        $type_error_limit = 50;
        $try = 0;

        while (!in_array($type, $response_types) && $try < $type_error_limit) {
            $type = fgetc($this->_connection);
            $try++;
        }

        if ($this->debug === TRUE) {
            log_message('debug', 'Redis response type: ' . $type);
        }

        switch ($type) {
            case '+':
                return $this->_single_line_reply();
                break;
            case '-':
                return $this->_error_reply();
                break;
            case ':':
                return $this->_integer_reply();
                break;
            case '$':
                return $this->_bulk_reply();
                break;
            case '*':
                return $this->_multi_bulk_reply();
                break;
            default:
                return FALSE;
        }
    }

    /**
     * Single line reply
     *
     * Reads the reply before the EOF
     * @return 	mixed
     */
    private function _single_line_reply() {
        $value = rtrim(fgets($this->_connection));
        $this->_clear_socket();

        return $value;
    }

    /**
     * Error reply
     *
     * Write error to log and return false
     * @return 	bool
     */
    private function _error_reply() {
        // Extract the error message
        $error = substr(rtrim(fgets($this->_connection)), 4);

        log_message('error', 'Redis server returned an error: ' . $error);
        $this->_clear_socket();

        return FALSE;
    }

    /**
     * Integer reply
     *
     * Returns an integer reply
     * @return 	int
     */
    private function _integer_reply() {
        return (int) rtrim(fgets($this->_connection));
    }

    /**
     * Bulk reply
     *
     * Reads to amount of bits to be read and returns value within
     * the pointer and the ending delimiter
     * @return  string
     */
    private function _bulk_reply() {

        // How long is the data we are reading? Support waiting for data to
        // fully return from redis and enter into socket.
        $value_length = (int) fgets($this->_connection);

        if ($value_length <= 0)
            return NULL;

        $response = '';

        // Handle reply if data is less than or equal to 8192 bytes, just read it
        if ($value_length <= 8192) {
            $response = fread($this->_connection, $value_length);
        } else {
            $data_left = $value_length;

            // If the data left is greater than 0, keep reading
            while ($data_left > 0) {

                // If we have more than 8192, only take what we can handle
                if ($data_left > 8192) {
                    $read_size = 8192;
                } else {
                    $read_size = $data_left;
                }

                // Read our chunk
                $chunk = fread($this->_connection, $read_size);

                // Support reading very long responses that don't come through
                // in one fread

                $chunk_length = strlen($chunk);
                while ($chunk_length < $read_size) {
                    $keep_reading = $read_size - $chunk_length;
                    $chunk .= fread($this->_connection, $keep_reading);
                    $chunk_length = strlen($chunk);
                }

                $response .= $chunk;

                // Re-calculate how much data is left to read
                $data_left = $data_left - $read_size;
            }
        }

        // Clear the socket in case anything remains in there
        $this->_clear_socket();

        return isset($response) ? $response : FALSE;
    }

    /**
     * Multi bulk reply
     *
     * Reads n bulk replies and return them as an array
     * @return 	array
     */
    private function _multi_bulk_reply() {
        // Get the amount of values in the response
        $response = array();
        $total_values = (int) fgets($this->_connection);

        // Loop all values and add them to the response array
        for ($i = 0; $i < $total_values; $i++) {
            // Remove the new line and carriage return before reading
            // another bulk reply
            fgets($this->_connection, 2);

            // If this is a second or later pass, we also need to get rid
            // of the $ indicating a new bulk reply and its length.
            if ($i > 0) {
                fgets($this->_connection);
                fgets($this->_connection, 2);
            }

            $response[] = $this->_bulk_reply();
        }

        // Clear the socket
        $this->_clear_socket();

        return isset($response) ? $response : FALSE;
    }

    /**
     * Encode request
     *
     * Encode plain-text request to Redis protocol format
     * @link 	http://redis.io/topics/protocol
     * @param 	string 	request in plain-text
     * @param   string  additional data (string or array, depending on the request)
     * @return 	string 	encoded according to Redis protocol
     */
    private function _encode_request($method, $arguments = array()) {
        $request = '$' . strlen($method) . self::CRLF . $method . self::CRLF;
        $_args = 1;

        // Append all the arguments in the request string
        foreach ($arguments as $argument) {
            if (is_array($argument)) {
                foreach ($argument as $key => $value) {
                    // Prepend the key if we're dealing with a hash
                    if (!is_int($key)) {
                        $request .= '$' . strlen($key) . self::CRLF . $key . self::CRLF;
                        $_args++;
                    }

                    $request .= '$' . strlen($value) . self::CRLF . $value . self::CRLF;
                    $_args++;
                }
            } else {
                $request .= '$' . strlen($argument) . self::CRLF . $argument . self::CRLF;
                $_args++;
            }
        }

        $request = '*' . $_args . self::CRLF . $request;

        return $request;
    }

    /**
     * Info
     *
     * Overrides the default Redis response, so we can return a nice array
     * of the server info instead of a nasty string.
     * @return 	array
     */
    public function info($section = FALSE) {
        if ($section !== FALSE) {
            $response = $this->command('INFO ' . $section);
        } else {
            $response = $this->command('INFO');
        }

        $data = array();
        $lines = explode(self::CRLF, $response);

        // Extract the key and value
        foreach ($lines as $line) {
            $parts = explode(':', $line);
            if (isset($parts[1]))
                $data[$parts[0]] = $parts[1];
        }

        return $data;
    }

    /**
     * Debug
     *
     * Set debug mode
     * @param	bool 	set the debug mode on or off
     * @return 	void
     */
    public function debug($bool) {
        $this->debug = (bool) $bool;
    }

    /**
     * Destructor
     *
     * Kill the connection
     * @return 	void
     */
    function __destruct() {
        if ($this->_connection)
            fclose($this->_connection);
    }
    //

}
