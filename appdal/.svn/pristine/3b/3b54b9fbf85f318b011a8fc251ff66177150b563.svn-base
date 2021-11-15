<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Journal_log extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('journal_log_model');
    }

    /**
     * 显示驳回信息表操作日志
     * http://www.caigou.com/journal/journal_log/get_log_list
     * @author harvin
     * @date <2019-1-4>
     * */
    public function get_log_list() {
        //请求参数
        $demand_number = $this->input->post_get('demand_number'); //需求单号
        $page = $this->input->get_post('offset'); //分页码
        $limit = $this->input->get_post('limit'); //每天显示多少条数
        if (empty($demand_number)) {
            $this->error_json('参数不合法');
        }
        $limit = query_limit_range($limit);
        $page = empty($page) ? 1 : $page;
        $offset = ($page - 1) * $limit;
        $data = $this->journal_log_model->log_list($demand_number, $limit, $offset);
        if (!empty($data)) {
            $this->success_json($data,null, '操作成功');
        } else {
            $this->error_json('请求失败');
        }
    }

    /**
     * 显示系统 操作日志
     * http://www.caigou.com/journal/journal_log/get_operator_log
     * @author harvin 2019-1-5
     * * */
    public function get_operator_log() {
        //请求参数
        $demand_number = $this->input->post_get('demand_number'); //需求单号
         if (empty($demand_number)) {
             $this->error_json('参数不合法');
        }
        $page = $this->input->get_post('offset');
        $limit = $this->input->get_post('limit');
        if (empty($page) or $page < 0) $page = 1;
        $limit = query_limit_range($limit);
        $offsets = ($page - 1) * $limit;
        $data = $this->journal_log_model->operator_log($demand_number, $limit, $offsets,$page);
        if (!empty($data)) {
            $this->success_json($data,null, '操作成功');
        } else {
            $this->error_json('无数据');
        }         
    }
    //http://www.caigou.com/journal/journal_log/get_redis
    public function get_redis(){
        $this->load->library('rediss');
        $redis=$this->rediss;
        $re=$redis->getData('1580');
        echo '<pre>';
        print_r($re);
        die;
    }

}
