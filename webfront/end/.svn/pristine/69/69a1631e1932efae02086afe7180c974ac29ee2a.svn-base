<?php
/**
 * Created by PhpStorm.
 * User: Jolon
 * Date: 2019/12/12
 * Time: 15:09
 */

class Offline_receipt_model extends Api_base_model {
    
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 获取 线下收款列表 数据列表
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function get_offline_receipt_list($params){

        $url = $this->_baseUrl . $this->_getOfflineReceiptListUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 确认待收款 界面
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function confirm_waiting_receipt($params){

        $url = $this->_baseUrl . $this->_confirmWaitingReceiptUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 确认待收款 驳回/确认到账
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function confirm_receipted($params){

        $url = $this->_baseUrl . $this->_confirmReceiptedUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 查看 收款详情
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function get_receipt_details($params){

        $url = $this->_baseUrl . $this->_getReceiptDetailsUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 更新 收款详情
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function update_receipt_details($params){

        $url = $this->_baseUrl . $this->_updateReceiptDetailsUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

    /**
     * 导出 收款列表
     * @author Jolon
     * @date   2021/01/13
     * @return mixed
     */
    public function export_list_csv($params){

        $url = $this->_baseUrl . $this->_exportListCsvUrl;
        $res = $this->_curlWriteHandleApi($url, $params, 'POST');
        $res['status'] = 1;
        $res['errorMess'] = $this->_errorMsg;
        return $res;
    }

}