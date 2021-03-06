<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/**
 * 报损信息控制器
 * User: Jaxton
 * Date: 2019/01/17 10:00
 */

class Report_loss extends MY_ApiBaseController{
	public function __construct(){
        parent::__construct();
        $this->load->model('abnormal/Report_loss_model','report_loss_model');
        $this->_modelObj = $this->report_loss_model;
    }

    /**
    * 获取报损数据列表
    * /abnormal/report_loss/get_report_loss_list
    * @author Jaxton 2019/01/17
    */
    public function get_report_loss_list(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_report_loss_list($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);     
    }

    /**
    * 弹出审核页面
    * /abnormal/report_loss/approval
    * @author Jaxton 2019/01/17
    */
    public function approval(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_approval_page($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);     
        
    }

    /**
    * 审核提交
    * /abnormal/report_loss/approval_handle
    * @author Jaxton 2019/01/17
    */
    public function approval_handle(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->approval_handle($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);     
        
    	
    }

    /**
    * 导出
    * /abnormal/report_loss/export_report_loss
    * @author Jaxton 2019/01/18
    */
    public function export_report_loss(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->export_report_loss($params);
        // print_r($data);die;
        // if (is_null($data)) {
        //     $this->_code = $this->getServerErrorCode();
        //     $this->_msg = $this->_modelObj->getErrorMsg();
        // }

        // $this->sendData($data);     
    }

    /**
    * 获取审核状态
    * /abnormal/report_loss/get_approval_status
    * @author Jaxton 2019/01/21
    */
    public function get_approval_status(){
        $list=getReportlossApprovalStatus();
        http_response(response_format(1,$list));
    }

    /**
     * @desc 编辑报损数据预览
     * @author Jeff
     * @Date 2019/6/25 19:42
     * @return
     */
    public function preview_edit_data()
    {
        try {
            $params = $this->_requestParams;
            $data = $this->_modelObj->get_preview_edit_data($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * @desc 编辑报损
     * @author Jeff
     * @Date 2019/6/26 9:51
     * @return
     */
    public function edit_report_loss()
    {
        try {
            $params = $this->_requestParams;
            $data = $this->_modelObj->edit_report_loss($params);
            if (is_null($data)) {
                $this->_code = $this->getServerErrorCode();
                $this->_msg = $this->_modelObj->getErrorMsg();
            }
            $this->sendData();
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 获取报损数据列表
     * /abnormal/report_loss/get_report_loss_list
     * @author Jaxton 2019/01/17
     */
    public function get_report_loss_list_sum(){
        $this->_init_request_param("GET");
        $params = $this->_requestParams;

        $data = $this->_modelObj->get_report_loss_list_sum($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);
    }
    /**
     * 审核提交
     * /abnormal/report_loss/approval_handle
     * @author Jaxton 2019/01/17
     */
    public function batch_approval_handle(){
        $params = $this->_requestParams;

        $data = $this->_modelObj->batch_approval_handle($params);

        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }

        $this->sendData($data);


    }
}