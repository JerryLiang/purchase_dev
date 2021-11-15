<?php
/**
 * Created by PhpStorm.
 * User: Justin
 * Date: 2020/4/24
 * Time: 14:58
 */
require APPPATH . 'core/MY_ApiBaseController.php';

class Charge_against extends MY_ApiBaseController
{
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('statement/Charge_against_model');
        $this->_modelObj = $this->Charge_against_model;
    }


    /**
     * 其他冲销审核 列表
     * @author Jolon
     */
    public function get_charge_against_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_charge_against_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 【冲销入库明细】采购单 与 入库批次进行冲销 - 自动冲销
     * @author Jolon
     */
    public function inventory_item_charge_against()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->inventory_item_charge_against($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 冲销审核 - 预览 & 提交
     * @author Justin
     */
    public function charge_against_audit()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->charge_against_audit($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查看冲销 - （与 采购单冲销汇总表 数据 对应）
     * @author Jolon
     */
    public function view_charge_against()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->view_charge_against($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 查看冲销操作日志
     * @author Jolon
     */
    public function view_charge_against_logs()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->view_charge_against_logs($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 核销-采购单冲销汇总表
     * @url /api/statement/Charge_against/get_summary_data_list
     * @method GET
     * @doc http://192.168.71.156/web/#/84?page_id=6618
     * @author Justin
     * @date 2020/04/24
     */
    public function get_summary_data_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_summary_data_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 核销-采购单冲销汇总表导出
     * @url /api/statement/Charge_against/summary_data_list_export
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=6621
     * @author Justin
     * @date 2020/04/24
     */
    public function summary_data_list_export()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->summary_data_list_export($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 核销-采购单与取消未到货退款,进行冲销-显示
     * @url /api/statement/Charge_against/refund_charge_against_view
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=6830
     * @author Justin
     * @date 2020/05/09
     */
    public function refund_charge_against_view()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->refund_charge_against_view($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 核销-采购单与取消未到货退款,进行冲销-显示-获取采购单剩余可冲销商品金额（可申请商品额）
     * @url /api/statement/Charge_against/get_able_ca_amount
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=6832
     * @author Justin
     * @date 2020/05/09
     */
    public function get_able_ca_amount()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->get_able_ca_amount($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 核销-采购单与取消未到货退款,进行冲销-保存
     * @url /api/statement/Charge_against/refund_charge_against_save
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=6833
     * @author Justin
     * @date 2020/05/09
     */
    public function refund_charge_against_save()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->refund_charge_against_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 核销-其他冲销审核列表导出
     * @url /api/statement/Charge_against/charge_against_list_export
     * @method POST
     * @doc http://192.168.71.156/web/#/84?page_id=
     * @author Justin
     * @date 2020/04/24
     */
    public function charge_against_list_export()
    {
        try {
            $this->_init_request_param("POST");
            $data = $this->_modelObj->charge_against_list_export($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage());
        }
    }
}