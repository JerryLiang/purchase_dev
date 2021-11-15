<?php
/**
 * 发票清单模型类
 * User: Jaxton
 * Date: 2019/03/14 15:06
 */

class Audit_amount_model extends Api_base_model {
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
    * 获取列表数据
    * @param $params
    * @return array   
    * @author Jaxton 2019/03/14
    */
    public function get_audit_amount_list($params){
    	$url= $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params,$url);
    }

    /**
    * 修改金额
    * @param $params
    * @return array   
    * @author Jaxton 2019/03/14
    */
    public function update_amount($params){
    	$url= $this->_baseUrl . $this->_updateUrl;
        return $this->request_http($params,$url);
    }
}

