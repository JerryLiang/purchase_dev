<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

include_once APPPATH . "core/MY_Oauth_Controller.php";

/**
 * Class Oauth_test
 * 继承 MY_Oauth_Controller 控制器
 *
 */
class Oauth_test extends MY_Oauth_Controller {

    public function __construct() {
        parent::__construct();
    }


    /**
     * 测试接口
     */
    public function test_oauth_access(){

        // 请求数据
        $resquestData = $this->_requestData;

        // 响应数据
        $responseData = ['list' => 500,'count' => 100];

        // 返回响应数据
        if('请求操作成功'){// 操作成功返回
            $this->success_json($this->_OK,$responseData);
        }else{// 操作失败返回
            $this->error_json($this->_BadRequest,'错误提示信息');
        }
    }


}
