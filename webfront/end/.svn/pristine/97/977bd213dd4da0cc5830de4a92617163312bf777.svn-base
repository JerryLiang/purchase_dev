<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/15
 * Time: 15:10
 */
class Admin_log extends MY_ApiBaseController
{

    /** @var Admin_log_model */
    private $model;

    /**
     * 获取系统访问日志
     * @author liwuxue
     * @date 2019/2/15 15:11
     * @param
     */
    public function get_list()
    {
        try {
            $this->load->model("system/Admin_log_model");
            $this->model = $this->Admin_log_model;
            $this->_init_request_param("GET");
            $data = $this->model->get_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}