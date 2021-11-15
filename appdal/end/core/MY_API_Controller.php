<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "third_party/MX/Controller.php";

/**
 * MY_Controller 控制器核心类
 * @author:    凌云
 * @since: 2018-09-21
 */
class MY_API_Controller extends MX_Controller
{

    /**
     * 返回前端的数据
     * @Array
     */
    public $data;

    public function __construct()
    {
        /**
         * 接口请求初始状态值，默认为0
         * 成功时需赋值1返回前端
         */
        $this->data['status'] = 0;

        parent::__construct();
    }

    /**
     * 错误提示
     * @author Jolon
     * @date 2019/2/27 9:54
     * @param string $data
     * @param string $error
     *      {"status":0,"errorMess":"$error"}
     */
    protected function error_data_json($data,$error = null)
    {
        http_response(response_format(0, $data, $error));
    }

    /**
     * 错误提示
     * @author liwuxue
     * @date 2019/2/13 9:54
     * @param string $error
     *      {"status":0,"errorMess":"$error"}
     */
    protected function error_json($error)
    {
        http_response(response_format(0, [], $error));
    }

    /**
     * 成功的json输出
     * @author liwuxue
     * @date 2019/2/13 9:56
     * @param array $data
     * @param array $page_data
     * @param string $error
     *  {"status":1,"errorMess":"", "data_list":{}, "paging_data":{}}
     */
    protected function success_json($data = [], $page_data = null, $error = "恭喜您,请求成功")
    {
        http_response(response_format(1, $data, $error, $page_data));
    }

    /**
     * 后端接口兼容restful(url采用/分割key/val和直接get参数?&拼接）
     */
    protected function compatible($method = 'get')
    {
        //额外鉴权参数
        $auth_col = ['token', 'appid', 'session_id', 'session_uid'];

        if ($method == 'get')
        {
            $get = $this->input->get();
            $url = $this->uri->uri_to_assoc(4);
            return array_diff_key(array_merge($get, $url), array_flip($auth_col));
        }
        elseif ($method == 'put')
        {
            $put = $this->input->input_stream();
            $post = $this->input->post();
            return array_diff_key(array_merge($put, $post), array_flip($auth_col));
        }
        else
        {
            return array_diff_key($this->input->post(), array_flip($auth_col));
        }
    }

}