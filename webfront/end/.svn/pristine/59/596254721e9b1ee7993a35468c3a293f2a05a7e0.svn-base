<?php
/**
 * Created by PhpStorm.
 * User: 袁学文
 * Date: 2019/2/20
 * Time: 15:09
 */

class Status_set_model extends Api_base_model
{
    
    
     public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }
     /**
     * 设置菜单单状态
     * @author 袁学文
     * @date 2019/2/20 
     * @return mixed
     * @throws Exception
     */
    public function get_set($data){
         try {
            $url = $this->_baseUrl . $this->_setApi;
            $this->_curlWriteHandleApi($url,$data, 'POST');
        } catch (Exception $e) {

        }
        return true;
    }
    
    
    
}