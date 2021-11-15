<?php if (!defined('BASEPATH')) {
	exit('No direct script access allowed');
}

/**
 * Api控制器Base Class
 */
class MY_ApiBaseController extends MY_Controller {
    // 请求参数
    protected $_requestParams;

    const DEFAULT_CODE = 0; // 默认返回的错误码
    const STATUS = 1; // 默认返回状态码
    const SERVER_ERROR_CODE = 501; // 上游发生错误时的错误码
    const DEFAULT_MSG  = 'OK'; // 默认消息

    protected $_code; // 返回的错误码
    protected $_msg;  // 返回的错误信息

    // 不允许传递的参数
    protected $_rejectedFields = array(
        'is_delete', 'create_user'
    );

	public function __construct()
    {   
        parent::__construct();
        $this->config->load('url_api',FALSE, TRUE);
        $params = get_request_params();
        // 过滤非法字段
        foreach ($this->_rejectedFields as $field)
        {
            unset($params[$field]);
        }
        $this->_requestParams = $params;

        $this->_code = self::DEFAULT_CODE;
        $this->_msg  = self::DEFAULT_MSG;

        $this->validateParams($params);
	}

    /**
     * 初始化接口参数，构造方法里只支持post，不支持get
     * @author liwuxue
     * @date 2019/1/26 12:01
     * @param string $method    POST / GET
     *  参数值 = get_request_params() 支持的参数值
     *
     */
    protected function _init_request_param($method = "POST")
    {
        $this->_requestParams = null;
        $params = get_request_params($method);
        // 过滤非法字段
        foreach ($this->_rejectedFields as $field)
        {
            unset($params[$field]);
        }
        $this->_requestParams = $params;
	}

    /**
     * 发送数据给前端
     * $data 业务数据
     *
     * @return_param $code int 错误码 0 OK，其它 错误 【10x 权限验证错误 20x 参数传递错误 50x服务器错误】
     * @return_param $msg string 错误消息
     * @return_param $data null 业务数据
     */
	protected function sendData($data = null)
    {
	    $returnData = array(
            'status' => $this->_code == 0 ? 1 : 0,
            'errorMess'  => $this->_msg
        );

	    if (is_array($data))
	    {
	        $returnData = array_merge($returnData, $data);
        }
	    http_response($returnData);
    }

    /**
     * 发送错误消息（通常用于参数校验不通过的处理）
     * @param $code int 错误码 0 OK，其它 错误 【10x 权限验证错误 20x 参数传递错误 50x服务器错误】
     * @param $msg  string 错误消息
     */
    protected function sendError($code, $msg)
    {
	    $this->_code = $code;
	    $this->_msg  = $msg;
	    $this->sendData();
    }

    /**
     * 返回 默认的错误码
     * @return int
     */
    protected function getDefaultCode()
    {
	    return self::DEFAULT_CODE;
    }

    /**
     * 返回 上游服务错误时的错误码
     * @return mixed
     */
    protected function getServerErrorCode()
    {
	    return self::SERVER_ERROR_CODE;
    }

    /**
     * 返回 默认消息
     * @return string
     */
    protected function getDefaultMsg()
    {
	    return self::DEFAULT_MSG;
    }

    /**
     * 验证输入参数
     * 使用方法：
     *  1. 规则配置文件放在该模块的 rules 目录下面，如果控制器在子目录里，那么也要相应创建同名的子目录，放在子目录中
     *  2. 规则文件的命名为类名的下划线命名形式，CurrencyRate =>　currency_rate
     *  3. 书写示例（addOne 为前端访问你接口的路径中的 方法名称）
     *  return array(
            'addOne' => array(
                array(
                    'field' => 'en_name',
                    'label' => '国家英文名称',
                    'rules' => 'required|regex_match[/^[\w ]+$/]'
                ),
                array(
                    'field' => 'cn_name',
                    'label' => '国家中文名称',
                    'rules' => 'required'
                )
            )
        );
     * 4. 不同方法共享相同的规则，可以用 | 把方法名隔开，如 'addOne|editOne'。
     *
     * CI的表单验证书写规则参考：https://codeigniter.org.cn/user_guide/libraries/form_validation.html
     * 更多示例可参考 modules/api/rules 目录下的文件
     */
    protected function validateParams($params)
    {
        $router = $this->router;
        $module = $router->fetch_module(); // 模块名称
        $class  = $router->fetch_class();  // 控制器名称
        $method = $router->fetch_method(); // 方法名称
        $segments = explode('/', $router->fetch_directory()); // fetch_directory()方法返回内容类似于 ../modules/api/controllers/
        array_pop($segments);
        $subdir = end($segments); // 控制器子目录
        $subdir = $subdir == 'controllers' ? '' : $subdir . '/';

        // 0.获取规则配置文件名称，小写下划线命名法
        $fileName = preg_replace_callback("/([A-Z])/", function($v){
            return '_' . strtolower($v[0]);
        },$class);

        // 1.加载配置文件
            $ruleFile = APPPATH . 'modules/' . $module . '/rules/' . $subdir . $fileName . '.php';
        // 文件不存在，返回
        if (!file_exists($ruleFile))
        {
            return;
        }
        $config = include $ruleFile;
        if (empty($config))
        {
            return;
        }

        // 2.获取验证规则
        $rules = [];
        foreach ($config as $k => $v)
        {
            if ($method == $k || in_array($method, explode('|', $k)))
            {
                $rules = array_merge($rules, $v);
            }
        }

        // 没有找到对应方法的规则，返回
        if (empty($rules))
        {
            return;
        }

        // 3.验证
        $this->load->library('form_validation', $rules);
        $validator = $this->form_validation;
        $validator->set_data($params);
        if ($validator->run() == false) {
            $errorArray = $validator->error_array();
            $errmsg = '';
            // 格式化错误信息
            foreach ($errorArray as $k => $v)
            {
                $errmsg .= "[$k]$v";
            }
            $this->sendError(101, $errmsg);
        }
    }
}
