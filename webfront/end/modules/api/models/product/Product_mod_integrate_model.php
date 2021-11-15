<?php
/**
 * 产品信息修改表
 * User: Jaxton
 * Date: 2018/01/24 
 */
class Product_mod_integrate_model extends Api_base_model {
    private $success     = false;
    private $error_msg   = '';
    private $success_msg = '';

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取供应商整合列表
     * /product/product_mod_integrate/get_integrate_list
     * @author Jolon
     * @param array $params
     * @return mixed
     */
    public function get_integrate_list($params){
        $url    = $this->_baseUrl . $this->_integrateListUrl;
        $url   .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 整合成功 或 整合失败
     * /product/product_mod_integrate/integrate_result
     * @author Jolon
     * @param array $params
     * @return mixed
     */
    public function integrate_result($params){
        $url    = $this->_baseUrl . $this->_integrateResultUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 添加整合备注
     * /product/product_mod_integrate/integrate_note_add
     * @author Jolon
     * @param array $params
     * @return mixed
     */
    public function integrate_note_add($params){
        $url    = $this->_baseUrl . $this->_integrateNoteAddUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 取消供应商整合
     * /product/product_mod_integrate/integrate_cancel
     * @author Jolon
     * @param array $params
     * @return mixed
     */
    public function integrate_cancel($params){
        $url    = $this->_baseUrl . $this->_integrateCancelUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        return $result;
    }

    /**
     * 供应商整合导出
     * /product/product_mod_integrate/integrate_cancel
     * @author Jolon
     * @param array $params
     * @return mixed
     */
    public function integrate_export($params){
        $url = $this->_baseUrl . $this->_integrateExportUrl;
        $url .= '?' . http_build_query($params);
        $result = $this->httpRequest($url, '', 'GET');
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['data_list'];
        }
        if (!$result['status']) {
            return null;
        }
        return $result;
    }




}