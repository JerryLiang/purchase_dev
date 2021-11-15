<?php

require APPPATH . 'core/MY_ApiBaseController.php';

/**
 * Created by PhpStorm.
 * User: liwuxue
 * Date: 2019/1/31
 * Time: 13:51
 */
class Receivables extends MY_ApiBaseController
{
    /** @var Receivables_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Receivables_model');
        $this->_modelObj = $this->Receivables_model;
    }

    /**
     * 列表显示----袁学文
     * @url /api/finance/receivables/rece_list
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method GET
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1108
     */
    public function rece_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->rece_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 待收款显示---袁学文
     * @url /api/finance/receivables/receivable
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method POST
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1109
     */
    public function receivable()
    {
        try {
            $data = $this->_modelObj->receivable($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 保存待收款操作---袁学文
     * @url /api/finance/receivables/receivable
     * @author liwuxue
     * @date 2019/1/31 10:55
     * @method POST
     * @param
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1110
     */
    public function receivable_save()
    {
        try {
            $data = $this->_modelObj->receivable_save($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
     /**
     * 查看收款详情
     * @author harvin 2019-1-19
     * http://www.caigou.com/finance/receivables/receivable_info
     * * */
     public function receivable_info(){
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->receivable_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
     }
     /**
      * 根据申请单号获取详细信息
      */
     public function  get_receivable_info_item(){
         try {
             $this->_init_request_param("GET");
             $data = $this->_modelObj->get_receivable_info_item($this->_requestParams);
             $this->sendData($data);
         } catch (Exception $e) {
             $this->sendError(-1, $e->getMessage());
         }
     }
    /**
     * 应收款单列表页 -- 导出
     * @author justin
     * @date 2019/9/26 10:48
     * @param
     * @return mixed
     * @throws Exception
     */
    public function export_list()
    {
        set_time_limit(0);
        try {
            $this->_init_request_param("GET");
            $data= $this->_modelObj->export_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError($e->getCode(), $e->getMessage()."导出出现异常!");
        }
    }
    /**
     * 修改收款的备注
     * @author totoro
     * @date 2019/12/10 10:48
     **/
    public function edit_receivable_note(){
        $this->_init_request_param("GET");
        $data = $this->_modelObj->edit_receivable_note($this->_requestParams);
        http_response($data);
    }
}