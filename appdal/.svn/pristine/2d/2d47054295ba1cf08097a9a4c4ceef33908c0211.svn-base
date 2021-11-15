<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/1/3 0003 16:10
 */


/**
 * 设置当前登录用户 通过USER ID
 * @author Jolon
 * @param $user_id
 * @param $user_name
 */
function setActiveUserById($user_id = 1,$user_name = 'admin')
{
    $CI = &get_instance();
    $CI->load->model('user_model', '', false, 'user');
    $CI->load->model('user/Purchase_user_model');
    if($user_id === 1){// 1 为Admin
        $CI->user_model->setActiveId(1);
        $CI->user_model->setActiveUsername('admin');
    }elseif($user_id === 0){// 0 为非系统用户
        $CI->user_model->setActiveId(0);
        $CI->user_model->setActiveUsername($user_name);
    }else{
        $user_info = $CI->Purchase_user_model->get_user_info($user_id);

        $CI->user_model->setActiveId($user_info['user_id']);
        $CI->user_model->setActiveUsername($user_info['user_name']);
    }
}

/**
 * 设置当前登录用户 通过USER 工号
 * @author Jolon
 * @param $staff_code
 */
function setActiveUserByStaffCode($staff_code)
{
    $CI = &get_instance();
    $CI->load->model('user_model', '', false, 'user');
    $CI->load->model('user/Purchase_user_model');
    $user_info = $CI->Purchase_user_model->get_user_info_by_staff_code($staff_code);

    $CI->user_model->setActiveId($user_info['user_id']);
    $CI->user_model->setActiveUsername($user_info['user_name']);
}

function getActiveUID(){
    return isset($_GET['uid'])?$_GET['uid']:(isset($_POST['uid'])?$_POST['uid']:0);
}

/**
 * 获取当前登录用户的ID
 * @author Jolon
 * @return mixed
 */
function getActiveUserId()
{
    $CI = &get_instance();
    $CI->load->model('user_model', '', false, 'user');
    if($CI->user_model->getActiveId()){
        return $CI->user_model->getActiveId();
    }else{
        $CI->user_model->_init(getActiveUID());
        return $CI->user_model->getActiveId();
    }
}

/**
 * 获取当前登录用户的 姓名
 * @author Jolon
 * @return mixed
 */
function getActiveUserName()
{
    $CI = &get_instance();
    $CI->load->model('user_model', '', false, 'user');
    if($CI->user_model->getActiveUsername()){
        return $CI->user_model->getActiveUsername();
    }else{
        $CI->user_model->_init(getActiveUID());
        return $CI->user_model->getActiveUsername();
    }
}

/**
 * 获取当前登录用户的 完整信息
 * @author Jolon
 * @return mixed
 */
function getActiveUserInfo()
{
    $CI = &get_instance();
    $CI->load->model('user_model', '', false, 'user');
    $CI->user_model->_init(getActiveUID());

    return $CI->user_model->getActiveInfo();
}

/**
 *获取登录 用户角色
 * @author harvin
 * @return mixed
 */
function getActiveUserRole(){
    $CI = &get_instance();
    $CI->load->model('user_model', '', false, 'user');
    $CI->user_model->_init(getActiveUID());
     return $CI->user_model->getActiveRole();
    
}

/**
 * 获取当前登录用户的 IP地址
 * @author Jolon
 * @return mixed
 */
function getActiveUserIp()
{
    $CI = &get_instance();
    $CI->load->model('user_model', '', false, 'user');
    $CI->user_model->_init(getActiveUID());

    return $CI->user_model->getCurrentIp();
}

/**
 * 获取用户ID根据用户名称
 * @author Jolon
 * @param int $userId 用户ID
 * @return mixed
 */
function getUserNameById($userId = 0)
{
    $CI = &get_instance();
    $CI->load->model('purchase_user_model', '', false, 'user');

    return $CI->purchase_user_model->get_user_info($userId,null,'user_name');
}

/**
 * 分析用户信息
 * @author Jolon
 * @return mixed
 */
function analyzeUserInfo(&$parames)
{
    $parames['modify_user_name'] = getActiveUserName();
    $parames['modify_user_id'] = getActiveUserId();
}

/**
 * 获取采购员下拉数据
 * @author Jaxton
 * @return mixed
 */
function getBuyerDropdown($user_id = null)
{
    $CI = &get_instance();
    $CI->load->model('user/Purchase_user_model');
    $data = $CI->Purchase_user_model->get_list();
    if($data and is_array($data)){
        $user_list = isset($data) ? array_column($data, 'name', 'id') : [];
        if (!is_null($user_id)) {
            return isset($user_list[$user_id]) ? $user_list[$user_id] : '';
        }
        return $user_list;
    }else{
        return [];
    }
}

/**
 * 获取采购员下拉数据
 * @author Jaxton
 * @return mixed
 */
function getAllUserDropDown($user_id = null)
{
    $CI = &get_instance();
    $CI->load->model('user/Purchase_user_model');
    $data = $CI->Purchase_user_model->get_user_all_list();
    if($data && is_array($data)){
        $user_list = isset($data) ? array_column($data, 'name', 'id') : [];
        if (!is_null($user_id)) {
            return isset($user_list[$user_id]) ? $user_list[$user_id] : '';
        }
        return $user_list;
    }else{
        return [];
    }
}

/**
 * 根据用户ID获取员工工号
 * @author Justin
 * @param int $userId 用户ID
 * @return mixed
 */
function getUserNumberById($userId = 0)
{
    $CI = &get_instance();
    $CI->load->model('purchase_user_model', '', false, 'user');

    return $CI->purchase_user_model->get_user_info($userId,null,'staff_code');
}

/**
 * 根据员工工号获取用户ID
 * @author Justin
 * @param string $staffCode 工号
 * @return mixed
 */
function getUserIDByStaffCode($staffCode ='')
{
    $CI = &get_instance();
    $CI->load->model('purchase_user_model', '', false, 'user');

    return $CI->purchase_user_model->get_user_info($staffCode,null,'id');
}