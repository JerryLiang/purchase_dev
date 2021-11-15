<?php

/**
 * Created by PhpStorm.
 * 供应商验货验厂
 * author: Jackson
 * Date: 2019/1/23
 */
class Check extends MY_Controller
{

    protected $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('check/Check_model');
        $this->load->model('check/Check_note_model', 'markModel');
        $this->load->model('check/Check_upload_model', 'uploadModel');
        $this->_modelObj = $this->Check_model;
    }

    /**
     * @desc 获取供应商信息列表
     * @author Jackson
     * @Date 2019-01-21 16:01:00
     * @return array()
     **/
    public function index()
    {
        $params = gp();
        $data['data_list'] = $this->_modelObj->get_by_page($params);

        //下拉列表申请人
        $this->load->model('user/Purchase_user_model', 'purchaseUserModel');
        $data['down_apply_user'] = $this->purchaseUserModel->get_user_all_list();

        $this->send_data($data, '供应商验货验厂列表', true);
    }


    /**
     * @desc 创建验货验厂
     * @author Jackson
     * @Date 2019-01-23
     * @return array()
     **/
    public function create()
    {
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }
        $params = gp();
        //事务处理
        $this->db->trans_start();
        try {

            if (isset($params['supplier']) && !empty($params['supplier'])) {

                //增加操作时间及创建人信息
                $times = date('Y-m-d H:i:s');
                $params['supplier']['create_time'] = $times;
                $params['supplier']['create_user_name'] = 12;//$this->session->username();

                $last_Id = 0;
                //保存供货商验货验厂主表数据
                list($status, $msg) = $this->_modelObj->create_inspection($params['supplier'], $last_Id);

                //保存供货商验货验-备注表数据
                if ($status) {
                    $params['supplier']['check_id'] = $last_Id;
                    list($status, $msg) = $this->markModel->create_marks($params['supplier']);
                }

                //判断是否保存成功
                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    throw new Exception("创建失败!");
                }
                //返回结果
                $this->send_data(null, $msg, $status);

            } else {
                $this->send_data(NULL, '提交数据为空或不是数组', false);
            }

        } catch (Exception $e) {
            $this->send_data(NULL, $e->getMessage(), false);
        }

    }

    /**
     * @desc 供应商验货验厂(获取单条数据根据ID)
     * @author Jackson
     * @Date 2019-01-23
     * @return array()
     **/
    public function recorde_by_id()
    {

        if (!IS_GET) {
            $this->send_data(null, '非法请求', false);
        }
        $id = gp('id');
        if (!$id) {
            $this->send_data(null, 'ID不能为空', false);
        }
        $data = $this->_modelObj->check_by_id($id);
        $this->send_data($data, '供应商验货验厂数据编辑', true);

    }

    /**
     * @desc 创建验货验厂(采购信息确认)
     * @author Jackson
     * @Date 2019-01-23
     * @return array()
     **/
    public function confirm()
    {
        if (!IS_POST) {
            $this->send_data(null, '非法请求', false);
        }

        $parames = gp();
        if (!isset($parames['id']) || !$parames['id']) {
            $this->send_data(null, 'ID不能为空', false);
        }

        $id = intval($parames['id']);
        unset($parames['id']);

        //事务处理
        $this->db->trans_start();
        try {
            if (isset($parames['supplier']) && !empty($parames['supplier'])) {

                $parames = $parames['supplier'];

                //增加操作时间及创建人信息
                $times = date('Y-m-d H:i:s');
                $parames['modify_time'] = $times;
                $parames['modify_user_name'] = 13;//$this->session->username();

                //更新主表信息
                list($main_status, $msg) = $this->_modelObj->_update($id, $parames);

                //更新备注表
                list($status, $msg) = $this->markModel->update_marks($id, $parames);

                //只要修改成功一个则视为成功
                if($main_status || $status){
                    $msg = "修改成功!";
                    $status = 1;
                }

                //判断是否保存成功
                $this->db->trans_complete();
                if ($this->db->trans_status() === false) {
                    throw new Exception("更新失败!");
                }

                //返回结果
                $this->send_data(null, $msg, $status);

            } else {
                $this->send_data(NULL, '提交数据为空或不是数组', false);
            }

        } catch (Exception $e) {
            $this->send_data(NULL, $e->getMessage(), false);
        }

    }

    /**
     * @desc 验货验厂数据导出
     * @author Jackson
     * @Date 2019-01-23
     * @return array()
     **/
    public function export()
    {
        if (!IS_GET) {
            $this->send_data(null, '非法请求', false);
        }
        $params = gp();
        try {
            $data = $this->_modelObj->get_export($params);
        }catch(Exception $e){
            $this->send_data(null, $e->getMessage(), false);
        }
        $this->send_data($data, '供应商验货验厂导出数据', true);
    }

    /**
     * @desc 验货验厂获取相关资料
     * @author Jackson
     * @Date 2019-01-24
     * @return array()
     **/
    public function get_meterial()
    {
        if (!IS_GET) {
            $this->send_data(null, '非法请求', false);
        }
        $parames = gp();
        $flag = false;//显示获取

        //判断下载获取,显示获取
        if (isset($parames['type']) && $parames['type']) {
            $flag = true;
            if (!isset($parames['id']) || !$parames['id']) {
                $this->send_data(null, 'id不能为空', false);
            }
        } else {
            if (!isset($parames['check_id']) || !$parames['check_id']) {
                $this->send_data(null, 'check_id不能为空', false);
            }
        }

        $data = $this->uploadModel->get_meterial($parames, $flag);
        $this->send_data($data, '供应商验货验厂相关资料', true);
    }
}