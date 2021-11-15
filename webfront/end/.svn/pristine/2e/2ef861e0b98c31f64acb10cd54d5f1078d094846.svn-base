<?php
/**
 * 报损信息控制器
 * User: Jolon
 * Date: 2020/03/03 10:00
 */

class Report_loss_unarrived_advance_model extends Api_base_model {

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
    * 获取取消未到货转报损数据
     * @author Jolon 2020/03/03
    */
    public function get_unarrived_to_loss($params){
        $url = $this->_baseUrl . $this->_getUnarrivedToLoss;
        return $this->request_http($params,$url);
    }

    /**
    * 保存取消未到货转报损数据
     * @author Jolon 2020/03/03
    */
    public function set_unarrived_to_loss($params){
        $url = $this->_baseUrl . $this->_setUnarrivedToLoss;
        return $this->request_http($params,$url);
    }

    /**
     * 获取取消未到货转报损数据
     * @author Jolon 2020/03/03
     */
    public function get_loss_to_unarrived($params){
        $url = $this->_baseUrl . $this->_getLossToUnarrived;
        return $this->request_http($params,$url);
    }

    /**
     * 保存取消未到货转报损数据
     * @author Jolon 2020/03/03
     */
    public function set_loss_to_unarrived($params){
        $url = $this->_baseUrl . $this->_setLossToUnarrived;
        return $this->request_http($params,$url);
    }


}