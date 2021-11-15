<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_API_Controller.php";
/**
 * 删除相关日志
 * User: Jolon
 * Date: 2020/03/06 10:00
 */
class Remove_logs extends MY_API_Controller {

    public function __construct(){
        parent::__construct();
    }


    /**
     * 删除 阿里消息记录日志表的记录 api_request_ali_log
     * @author Jolon
     * @url remove_logs/delete_ali_request_invalid_log
     */
    public function delete_ali_request_invalid_log(){
        $this->load->model('Remove_logs_model');

        $type  = $this->input->get_post('type');// 删除类型
        $limit = $this->input->get_post('limit');// 调试模式
        $type  = (isset($type) and !empty($type))?$type:'graceful';
        $limit = isset($limit)?$limit:1000;

        $data = $this->Remove_logs_model->delete_ali_request_invalid_log($limit,$type);
        $this->success_json($data);
    }

}