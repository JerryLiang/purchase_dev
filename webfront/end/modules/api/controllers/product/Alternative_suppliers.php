<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * Created by PhpStorm.
 * 采购需求控制器
 * User: Jolon
 * Date: 2018/12/27 0027 11:17
 */

class Alternative_suppliers extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('product/Alternative_suppliers_model');
        $this->_modelObj = $this->Alternative_suppliers_model;
    }

    /**
     * 添加备选供应商接口
     * @METHOD POST
     * @author:luxu
     * @time:2021年4月22号
     **/

    public function add_alternative_supplier(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->add_alternative_supplier($params);
        $this->sendData($data);
    }

    /**
     * 获取备选供应商接口
     * @METHOD GET
     * @author:luxu
     * @time:2021年4月22号
     **/
    public function get_alternative_supplier(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_alternative_supplier($params);
        $this->sendData($data);
    }

    public function get_alternative_boxdata(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_alternative_boxdata($params);
        $this->sendData($data);
    }

    /**
     * 修改备货选供应商
     * @METHOD POST
     * @author:luxu
     * @time:2021年4月22号
     **/
    public function save_alternative_supplier(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->save_alternative_supplier($params);
        $this->sendData($data);
    }

    /**
     * 获取备选供应商审核数据接口，33100 备选供应商列表新增子页面"备选供应商待审核"
     * 新增子页面"备选供应商待审核"
        1.备选供应商列表,新增子页面"备选供应商待审核",显示字段和筛选项如图,
        2.勾选数据进行审核,审核通过或驳回的,页面都不再显示,将相关信息记录到供货关系的变更日志中
        3.审核通过时,备选供应商列表的数据同步更新
        4.审核驳回的,通过消息中心,弹窗通知申请人,弹窗信息:"备选供应商列表,SKU****供应商******审核被驳回,驳回原因 *******"
     * @author:luxu
     * @time:2021年4月24号
     **/

    public function alternative_supplier_examine(){

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->alternative_supplier_examine($params);
        $this->sendData($data);
    }

    /**
     * 备选供应商审核接口
     * @method POST
     * @author:luxu
     * @time:2021年4月26号
     **/
    public function audit_alternative_supplier(){

        $this->_init_request_param("POST");
        $params = $this->_requestParams;
        $data = $this->_modelObj->audit_alternative_supplier($params);
        $this->sendData($data);
    }

    /**
     * 备选供应商日志获取
     * @method:get
     * @author:luxu
     * @time:2021年4月27号
     **/
    public function get_alternative_log(){

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->get_alternative_log($params);
        $this->sendData($data);
    }

    public function alternative_import(){

        $this->_init_request_param("GET");
        $params = $this->_requestParams;
        $data = $this->_modelObj->alternative_import($params);
        $this->sendData($data);
    }
}