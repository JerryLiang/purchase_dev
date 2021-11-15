<?php
/**
 * 系统访问日志
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/15
 * Time: 14:15
 */
class Admin_log extends MY_Controller
{

    /**
     * 记录系统访问日志
     * @author liwuxue
     * @date 2019/2/15 14:15
     * @param
     * @url /system/admin_log/write_log
     */
    public function write_log()
    {
        try {
            $post = [];
            $post['route'] = $this->input->post("route");
            $post['description'] = $this->input->post("description");
            $post['ip'] = $this->input->post("ip");
            $post['user_name'] = $this->input->post("user_name");
            $post['user_role'] = $this->input->post("user_role");

            $this->load->model("Admin_log_model");
            if (!$this->Admin_log_model->write_log($post)) {
                throw new Exception("日志记录失败！");
            }
            $this->success_json([]);

        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

    /**
     * 获取访问日志列表
     * @author liwuxue
     * @date 2019/2/15 14:16
     * @param
     * @url /system/admin_log/get_list
     */
    public function get_list()
    {
        try {
            $get = [];
            $get['user_name'] = $this->input->get("user_name");
            $get['page'] = $this->input->get("offset");
            $get['page_size'] = $this->input->get("limit");

            $this->load->model("Admin_log_model");
            $data = $this->Admin_log_model->get_list($get);
          //  $page_data= $data['paging_data'];
          //  unset($data['paging_data']);
            $this->success_json($data);

        } catch (Exception $e) {
            $this->error_json($e->getMessage());
        }
    }

}