<?php
/**
 * Created by PhpStorm.
 * 用户信息模型
 * User: Jolon
 * Date: 2018/12/28 0028 9:58
 */
class User_model extends MY_Model {

    private $_active_user_uid  = '';// 激活用户 UID
    private $_active_user_id   = '';// 激活用户 ID
    private $_active_user_name = '';// 激活用户名
    private $_active_client_ip = '0.0.0.0';// 用户登录IP
    private $_active_user_info = '';// 用户完整信息
    private $_active_user_role = '';// 当前用户角色

    private $_check_log_error = null;

    public function __construct(){
        parent::__construct();
        $this->load->model('user/Purchase_user_model');
    }

    /**
     * 初始化 当前登录用户信息
     * @author Jolon
     * @param int|string $uid 用户识别码ID
     * @return bool
     */
    public function _init($uid){
        if(empty($uid)){
            $this->_check_log_error = 'UID缺失';
            return false;
        }
        if(!empty($this->_active_user_id)) return true;// 避免重复获取，影响效率

        // 从Redis中获取用户信息
        $user_info = $this->rediss->getData($uid);
        $this->_active_user_role = $user_info;
        if($user_info){
            // 获取员工编号
            $staff_code = isset($user_info['staff_code'])?$user_info['staff_code']:'';
            if(empty($staff_code)){
                $this->_check_log_error = 'Redis用户信息员工编号缺失';
                return false;
            }
            // 根据员工编号查找员工信息
            $user_info = $this->Purchase_user_model->get_user_info_by_staff_code($staff_code);
            if(empty($user_info)){
                $this->_check_log_error = "OA系统用户接口异常[工号：$staff_code]";
                return false;
            }
            if(empty($user_info['user_id']) or empty($user_info['user_name'])){
                $this->_check_log_error = 'OA系统用户接口异常[用户ID或名称缺失]';
                return false;
            }

            $this->_active_user_uid  = isset($user_info['uid'])?$user_info['uid']:'';
            $this->_active_user_id   = $user_info['user_id'];// staff_code
            $this->_active_user_name = isset($user_info['user_name'])?$user_info['user_name']:'';
            $this->_active_client_ip = isset($user_info['client_ip'])?$user_info['client_ip']:'0.0.0.0';
            $this->_active_user_info = $user_info;

            return true;
        }else{
            $this->_check_log_error = 'Redis用户信息已失效[UID:'.$uid.']，请重新登录';
            return false;
        }

    }

    /**
     * 获取用户登录验证错误提示信息
     * @author Jolon
     * @return null
     */
    public function getCheckLoginError(){
        return $this->_check_log_error;
    }

    /**
     * 设置当前登录用户的ID
     * @param $user_id
     * @author Jolon
     * @return int
     */
    public function setActiveId($user_id){
        return $this->_active_user_id = $user_id;
    }

    /**
     * 获取当前登录用户的ID
     * @author Jolon
     * @return int
     */
    public function getActiveId(){
        return $this->_active_user_id;
    }

    /**
     * 设置当前登录用户的用户名
     * @param $user_name
     * @author Jolon
     * @return string
     */
    public function setActiveUsername($user_name){
        return $this->_active_user_name = $user_name;
    }

    /**
     * 获取当前登录用户的用户名
     * @author Jolon
     * @return string
     */
    public function getActiveUsername(){
        return $this->_active_user_name;
    }

    /**
     * 获取当前登录用的 IP 地址
     * @author Jolon
     * @return mixed
     */
    public function getCurrentIp(){
        return $this->_active_client_ip;
    }

    /**
     * 获取当前登录的用户信息
     * @desc  详细内容参见 http://192.168.71.170:85/user/test_user_info?uid=1537
     * @author Jolon
     * @return mixed
     */
    public function getActiveInfo(){
        return $this->_active_user_info;
    }
 /**
     * 获取当前登录的用户角色
     * @author Jolon
     * @return mixed
     */
    public function getActiveRole(){
        return $this->_active_user_role;
    }

}