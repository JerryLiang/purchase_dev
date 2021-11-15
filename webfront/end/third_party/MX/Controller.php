<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/** load the CI class for Modular Extensions **/
require dirname(__FILE__).'/Base.php';

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library replaces the CodeIgniter Controller class
 * and adds features allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Controller.php
 *
 * @copyright	Copyright (c) 2015 Wiredesignz
 * @version 	5.5
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class MX_Controller 
{
	public $autoload = array();

    /**
     * 主题
     */
    protected $_theme = '';

	public function __construct() 
	{
        /**
         * 校验请求是否合法
         */
        $this->client_ip = get_client_ip();
        $allow_ip_validate = $this->config->item('allow_ip_validate');

        if($allow_ip_validate && (! $this->_check_ip($this->client_ip))) {
            http_response(array(
                'status' => 0,
                'errorMess' => 'Your current IP or host is not allowed to access this site!',
                'http_status_code' => 401
            ),401);
        }

		$class = str_replace(CI::$APP->config->item('controller_suffix'), '', get_class($this));
		log_message('debug', $class." MX_Controller Initialized");
		Modules::$registry[strtolower($class)] = $this;	
		
		/* copy a loader instance and initialize */
		$this->load = clone load_class('Loader');
		$this->load->initialize($this);	
		
		/* autoload module items */
		$this->load->_autoloader($this->autoload);

//        $this->lang->load('errorinfo_lang');

        if (RSA_VALIDATE){
            $this->rsaDecryptValidate();
        }
	}

    /**
     * 校验ip是否合法
     * String $ip
     */
    private function _check_ip($ip='') {

        if(empty($ip)) return false;

        $alow_ip_list = $this->config->item('allow_ip_list');
        $allow = false;
        foreach($alow_ip_list as $client_ip){
            if( strpos($ip, str_replace('*','',$client_ip)) !== false ){
                $allow = true;
                break;
            }
        }
        return $allow;

    }

	public function __get($class) 
	{
		return CI::$APP->$class;
	}

    public function __destruct()
    {
        foreach ($this as $index => $value) unset($this->$index);
    }

    public function error_info($error_code = ''){
        if (empty($error_code)) return '';

        $error_msg = $this->lang->myline('ECode_'.$error_code);

        return   empty($error_msg) ? '' : $error_msg;
    }

    //RSA校验
    public function rsaDecryptValidate(){
        //判断是否开启验证
        if (RSA_VALIDATE){
            $is_home_page = basename($_SERVER['REQUEST_URI']) == '';

            $encrypt_params = $this->input->post_get('encrypt_params');

            if (!empty($encrypt_params)){
                $this->load->library(array('rsa'));
                $encrypt_params =  $this->rsa->privateDecrypt($encrypt_params);

                if (empty($encrypt_params)){
                    http_response(
                        array(
                            'status' => 0,
                            'errorMess' => '接口访问非法！请联系管理人员!',
                            'http_status_code' => 401
                        )
                    );
                }

                $encrypt_params = json_decode(urldecode($encrypt_params),true);
                
                if ($_SERVER['REQUEST_METHOD'] == 'GET'){
                    foreach ($encrypt_params as $k => $param){
                        $_GET[$k] = $param;
                    }
                }else{
                    foreach ($encrypt_params as $k => $param){
                        $_POST[$k] = $param;
                    }
                }

            }elseif(!$is_home_page){
                $access_address = explode('/',$_SERVER['REQUEST_URI']);

                //判断访问方法是否允许直接通过
                $allow_access_method = $this->config->item('allow_access_method');
                if (!(isset($access_address[3]) && in_array(strtolower($access_address[3]),$allow_access_method)) && !(isset($access_address[2]) && in_array(strtolower($access_address[2]),array('resource')))){
                    http_response(
                        array(
                            'status' => 0,
                            'errorMess' => 'Your data is invalid!',
                            'http_status_code' => 200
                        ),200
                    );
                }
            }
        }
    }

}