<?php
/**
 *
 * @author:凌云
 * @desc: 注册树类
 * @since: 20180921
 *
 *      注册：Register::_set('类名','实例化对象');
 *      获取：Register::_get('类名');
 *      删除：Register::_unset('类名');
 *
 *
 */

class Register {

    private static $obj;

    //禁止实例化
    private function __construct() {

    }
    //禁止克隆
    private function __clone() {

    }
    public static function _set($name,$value) {
        self::$obj[$name] = $value;
        return $value;
    }

    public static function _get($name) {
        return self::$obj[$name];
    }

    public static function _unset($name) {
        unset(self::$obj[$name]);
    }

}