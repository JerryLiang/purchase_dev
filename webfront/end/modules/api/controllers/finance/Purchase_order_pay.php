<?php

require APPPATH . 'core/MY_ApiBaseController.php';

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 * 采购控制器
 */

class Purchase_order_pay extends MY_ApiBaseController
{

    /** @var Purchase_order_pay_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('finance/Purchase_order_pay_model');
        $this->_modelObj = $this->Purchase_order_pay_model;

    }
   
    /**
     * 网采单-创建请款单-数据预览
     * @author Jolon
     * @desc array $_POST['purchase_numbers'] 要请款采购单号
     */
    public function network_pay_order_preview()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->network_pay_order_preview($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }

    /**
     * 网采单-请款单
     * @author Jolon
     * http://www.caigou.com/finance/purchase_order_pay/network_pay_order_create
     * @desc array $_POST['purchase_numbers'] 要请款采购单号
     */
    public function network_pay_order_create()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->network_pay_order_create($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }

    /**
     * 合同单-创建请款单-数据预览
     * @author Jolon
     * @desc array $_POST['compact_number']         要请款合同号
     *              $_POST['requisition_payment']    为空：合同请款数据预览，不为空：预览 付款申请书
     */
    public function compact_pay_order_preview()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->compact_pay_order_preview($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }

    /**
     * 合同单-创建请款单-付款申请书预览
     *      在 compact_pay_order_preview 生成付款申请书之后调用
     * @author Jolon
     * @desc array $_POST[]  要请款合同号
     */
    public function compact_pay_order_create()
    {

        $params = $this->_requestParams;
        $data = $this->_modelObj->compact_pay_order_create($params);
        if (is_null($data)) {
            $this->_code = $this->getServerErrorCode();
            $this->_msg = $this->_modelObj->getErrorMsg();
        }
        $this->sendData($data);

    }

     /**
     * 审核通过及驳回(蓝凌)
     * @author harvin 2019-1-12
     * http://www.caigouapi.com/api/finance/purchase_order_pay/payment_examines
     * * */
    public function payment_examines(){
         $params = $this->_requestParams;
         if(empty($params['ids'])){
            $data=['status'=>0,'errorMess'=>'请勾选数据'];
            $this->sendData($data);
          }
        $idmun=explode(',', $params['ids']);
        if(count($idmun)!=1){
             $data=['status'=>0,'errorMess'=>'必须勾选一条数据'];
            $this->sendData($data);
        }
       $data=$this->_modelObj->payment_examine_save($params);
       if($data['status']==1){
           $this->sendData(['processid'=>$data['data_list']]);
       }else{
           $data=['status'=>0,'errorMess'=>'获取不到数据'];
           $this->sendData($data);
       }
    }


    /**
     * 请款单 -> 搜索列
     * @author liwuxue
     * @date 2019/1/30 15:59
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1014
     */
    public function search()
    {
        try {
            $data = $this->_modelObj->get_search_list();
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 请款单 -> 请款单列列表
     * @author liwuxue
     * @date 2019/1/30 15:59
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1008
     */
    public function payment_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_payment_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 请款单 -> 审核通过及驳回
     * @author liwuxue
     * @date 2019/1/30 15:59
     * @method POST
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1011
     */
    public function payment_examine()
    {
        try {
            $data = $this->_modelObj->payment_examine($this->_requestParams);
            //写操作，给出操作提示
            $this->_code = 0;//会输出 status=1
            $this->_msg = $this->_modelObj->getErrorMsg();
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 请款单 -> 获取请款明细--袁学文
     * @author liwuxue
     * @date 2019/1/30 15:59
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1012
     */
    public function payment_info()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_payment_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
     /**
     * 请款单 -> 获取请款明细--袁学文
     * @author liwuxue
     * @date 2019/1/30 15:59
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1012
     */
    public function payment_examine_info()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->payment_examine_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
    /**
     * 请款单 -> 请款单导出--袁学文
     * @author liwuxue
     * @date 2019/1/30 15:59
     * @method get
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=1013
     */
    public function payment_export()
    {
        set_time_limit(0);
        try {
            $this->_init_request_param("GET");
            $this->_modelObj->payment_export($this->_requestParams);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }


}
