<?php
require APPPATH . 'core/MY_ApiBaseController.php';
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Puerchase_unarrived extends MY_ApiBaseController
{

    /** @var Puerchase_unarrived_model */
    private $_modelObj;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('purchase/Puerchase_unarrived_model');
        $this->_modelObj = $this->Puerchase_unarrived_model;

    }

    /**
     * 取消未到货导出
     * @author:luxu
     **/
    public function cancel_unarrived_goods_examine_down(){

        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->cancel_unarrived_goods_examine_down($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 取消未到货列表
     * @author 2019-3-9
     *
     */
    public function cencel_lits()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_cencel_lits($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 取消未到货显示
     * @author harvin 2019-1-8
     * /api/purchase/puerchase_unarrived/cancel_unarrived_goods
     * * */
    public function cancel_unarrived_goods()
    {
        $params = $this->_requestParams;
        if (empty($params['ids'])) {
            $data = ['status' => 0, 'errorMess' => '请勾选数据'];
            $this->sendData($data);
        }
        $result = $this->_modelObj->get_cancel_unarrived_goods($params);
        if (isset($result['status']) && $result['status'] == 1) {
            $this->sendData(["data_list" => isset($result['data_list']) ? $result['data_list'] : []]);
        } else {
            $this->sendError(-1, isset($result['errorMess']) ? $result['errorMess'] : 'api接口返回异常：' . json_encode($result, 256));
        }
    }

    /**
     * 保存取消未到货的操作
     * @author harvin 2019-1-11
     * /api/purchase/puerchase_unarrived/cancel_unarrived_goods_save
     * * */
    public function cancel_unarrived_goods_save()
    {
        $params = $this->_requestParams;
        $params['is_submit'] = true;
        $result = $this->_modelObj->get_cancel_unarrived_goods_save($params);
        $this->sendData($result);

    }

    /**
     * 取消未到货的订单审核操作显示，查询
     * @author liwuxue
     * @date 2019/1/31 9:25
     * @param
     * @method GET
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=926
     */
    public function cancel_unarrived_goods_examine_list()
    {
        try {
            $params = $this->_requestParams;
            $data   = $this->_modelObj->cancel_unarrived_goods_examine_list($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 取消未到货的再编辑
     * @author liwuxue
     * @date 2019/1/31 9:25
     * @param
     * @method GET
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=926
     */
    public function cancel_unarrived_goods_edit()
    {
        try {
            $params = $this->_requestParams;
            $data   = $this->_modelObj->cancel_unarrived_goods_edit($params);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 获取取消未到货操作日志
     * @author harvin
     */
    public function cancel_log_info()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_cancel_log_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }


    }

    /**
     * 取消未到货的订单审核操作显示，查询
     * @author liwuxue
     * @date 2019/1/31 9:25
     * @param
     * @method POST
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=926
     */
    public function cancel_unarrived_goods_examine()
    {
        try {
            $data = $this->_modelObj->cancel_unarrived_goods_examine($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 变更采购员操作经理权限保存--袁学文
     * @author liwuxue
     * @date 2019/1/31 9:25
     * @param
     * @method POST
     * @doc http://1z8580573g.51mypc.cn:33344/web/#/84?page_id=926
     */
    public function change_purchaser()
    {
        try {
            $data = $this->_modelObj->change_purchaser($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 显示变更采购员操作经理权限显示---袁学文
     * @author liwuxue
     * @date 2019/1/31 9:25
     * @param
     * @method GET
     * @doc
     */
    public function change_purchaser_list()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->change_purchaser_list($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     *上传流水截图
     * @author harvin
     * @date 2019-3-14
     * /purchase/puerchase_unarrived/cancel_upload_screenshots
     */
    public function cancel_upload_screenshots()
    {
        try {
            $data = $this->_modelObj->get_cancel_upload_screenshots($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 取消未到货显示详情
     * @author harvin
     * /purchase/puerchase_unarrived/cancel_upload_screenshots
     */
    public function cancel_unarrived_info()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_cancel_unarrived_info($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }


    }

    /**
     * 根据收款信息拉取1688退款信息
     * @author 2019-3-9
     *
     */
    public function refresh_ali_refund()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->refresh_ali_refund($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }

    }

    /**
     * 取消未到货 上传截图预览
     * @author 2019-3-9
     *
     */
    public function cancel_upload_screenshots_preview()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->cancel_upload_screenshots_preview($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }

    }

    /**
     *上传流水截图
     * @author jeff
     * @date 2019-09-26
     * /purchase/puerchase_unarrived/cancel_upload_screenshots
     */
    public function cancel_upload_screenshots_v2()
    {
        try {
            $data = $this->_modelObj->get_cancel_upload_screenshots_v2($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 取消未到货列表
     * @author 2019-3-9
     *
     */
    public function get_cancel_lists_sum()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_cancel_lists_sum($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }


    /**
     * 取消未到货批量上传图片
     */
    public function get_cancel_upload_data()
    {
        try {
            $this->_init_request_param("GET");
            $data = $this->_modelObj->get_cancel_upload_data($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }

    /**
     * 取消未到货批量上传图片 保存数据
     */
    public function save_cancel_upload_data()
    {
        try {
//            $this->_init_request_param("GET");
            $data = $this->_modelObj->save_cancel_upload_data($this->_requestParams);
            $this->sendData($data);
        } catch (Exception $e) {
            $this->sendError(-1, $e->getMessage());
        }
    }
}