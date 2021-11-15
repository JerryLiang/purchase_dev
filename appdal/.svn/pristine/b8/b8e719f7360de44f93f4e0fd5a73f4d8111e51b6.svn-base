<?php
/**
 * 系统版本控制器
 * User: Jaxton
 * Date: 2019/01/24 10:00
 */

class System_version extends MY_Controller{
	public function __construct(){
        parent::__construct();
        $this->config->load('system', FALSE, TRUE);
    }

    /**
    * /system/system_version/get_system_version
    */
    public function get_system_version(){
    	$system_version=$this->config->config['system_version'];
    	$return_data=[
    		'status'=>1,
    		'data_list'=>[
    			'key'=>['Web服务版本','内部版本号','定时任务服务版本','权限中心版本'],
    			'value'=>$system_version
    		]
    	];
    	if($return_data['status']){
            $this->success_json($return_data['data_list']);
        }else{
    	    $this->error_json('');
        }
    }
}