<?php
/**
 * 预测计划单数据库模型类
 * User: Jaxton
 * Date: 2019/01/03 17:23
 */

class Forecast_plan_model extends Api_base_model {

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }
    
    /**
    * 获取符合条件的预测单
    * @param $params
    * @param $offset
    * @param $limit
    * @return array   
    * @author Jaxton 2019-1-4
    */
    public function get_forecast_list_all($params){
        $url= $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params,$url);
    }

    /**
    * 添加反馈
    * @param $feedback_str
    * @param $suggest_id
    * @return bool   
    * @author Jaxton 2019-1-4
    */
    public function add_feedback($params){
        $url= $this->_baseUrl . $this->_addUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
    * 获取反馈记录
    * @param $suggest_id
    * @return array   
    * @author Jaxton 2019-1-4
    */
    public function get_feedback_list($params){
        $url= $this->_baseUrl . $this->_feedback_listUrl;
        return $this->request_http($params,$url,'GET',false);
    }

}