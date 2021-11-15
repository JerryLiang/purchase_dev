<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/2/1
 * Time: 14:30
 */
class Product_scree extends MY_ApiBaseController
{
    /** @var Product_scree_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('abnormal/Product_scree_model');
        $this->_modelObj = $this->Product_scree_model;
    }

    /**
     * SKU屏蔽列表-占伟龙
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=966
     */
    public function get_scree_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_scree_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 导出CSV-
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method get
     * @doc
     */
    public function scree_export_csv()
    {
        try {
            $this->_init_request_param("GET");
            $this->_modelObj->scree_export_csv($this->_requestParams);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 创建 SKU屏蔽 记录-
     * @author luxu
     * @date 2019/8/6 20:05
     * @param
     * @method POST JSON
     * @doc
     */

    public function set_scree_sku(){

        try{
            $this->_init_request_param("POST");
            $clientData = $this->_requestParams;
            $data = $this->_modelObj->set_scree_create($clientData);
            $data = json_decode($data,True);
            $this->sendData($data);

        }catch ( Exception $e ) {

            $this->sendError($e->getCode(), $e->getMessage());
        }

    }

    /**
     * 创建 SKU屏蔽 记录-
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method POST
     * @doc
     */
    public function scree_create()
    {
        try {
            $data = $this->_modelObj->scree_create($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 采购经理审核
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method POST
     * @doc
     */
    public function scree_audit()
    {
        try {
            $data = $this->_modelObj->scree_audit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 采购确认 - 替换供应商
     * @author liwuxue
     * @date 2019/1/24 20:05
     * @param
     * @method POST
     * @doc
     */
    public function affirm_supplier()
    {
        try {
            $data = $this->_modelObj->affirm_supplier($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 获取SKU 屏蔽操作日志
     * @author  luxu
     * @date  2019/09/05
     * @param
     * @method GET
     **/

    public function get_logs() {

        try{
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_logs($this->_requestParams);
            $this->sendData($data);

        }catch ( Exception $exp ) {

            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    /**
     *  sku屏蔽流程调整——开发部无需审核，采购经理审核通过后，sku审核状态变成“已结束”
     *  @author: luxu
     **/
    public function update_estimate_time()
    {
        try{

            $clientData = $this->_requestParams;

            if( empty($clientData) || !isset($clientData['datas']))
            {
                throw new Exception("请传入相关参数");
            }
            $data = $this->_modelObj->update_estimate_time($clientData);
            $this->sendData($data);


        }catch ( Exception $exp )
        {
            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    public function get_scree_estimatetime()
    {
        try{
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_scree_estimatetime($this->_requestParams);
            $this->sendData($data);

        }catch ( Exception $exp ) {

            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    public function getPrevData(){

        try{
            $this->_init_request_param("GET");
            $data = $this->_modelObj->getPrevData($this->_requestParams);
            $this->sendData($data);

        }catch ( Exception $exp ) {

            $this->sendError($exp->getCode(), $exp->getMessage());
        }
    }

    public function import_progress(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        if(!isset($params['packag_file']) or empty($params['packag_file'])){
            $this->sendError(1, "文件地址参数缺失");
        }
        $file_path = $params['packag_file'];
        if(!file_exists($file_path)){
            $this->sendError(1, "文件不存在[{$file_path}]");
        }
        $params['file_path'] = $file_path;

        try {
            $data = $this->_modelObj->scree_import_data($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

}