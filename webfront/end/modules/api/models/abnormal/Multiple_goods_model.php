<?php
/**
 * 异常列表模型类
 * User: Jaxton
 * Date: 2019/01/16 10:06
 */

class Multiple_goods_model extends Api_base_model {
    protected $table_name = 'purchase_warehouse_abnormal';//异常表


    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
        //$this->load->helper(['user','abnormal']);
    }

    /**
     * 获取多货数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function multiple_goods_list($params)
    {
        $url = $this->_baseUrl . $this->_multipleGoodsListUrl;
        return $this->request_http($params, $url);

    }


    /**
     * 获取多货退货数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function get_multiple_return_goods($params)
    {
        $url = $this->_baseUrl . $this->_multipleReturnGoodsUrl;
        return $this->request_http($params, $url);

    }


    /**
     * 获取多货调拨列表数据
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function get_transfer_multiple_list($params)
    {
        $url = $this->_baseUrl . $this->_getTransferMultipleListUrl;
        return $this->request_http($params, $url);

    }


    /**
     * 获取多货调拨列表数据
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function multiple_return_show($params)
    {
        $url = $this->_baseUrl . $this->_multipleReturnShowUrl;
        return $this->request_http($params, $url);

    }


    /**
     * 多货退货信息保存
     */
    public function multiple_return_save($params)
    {
        $url = $this->_baseUrl . $this->_multipleReturnSaveUrl;


         return $this->_curlReadHandleApi($url, $params, 'POST');






    }

    /**
     * 获取生成多货退货信息
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function get_return_multiple_info($params)
    {
        $url = $this->_baseUrl . $this->_getReturnMultipleInfoUrl;
        return $this->request_http($params, $url);

    }


    /**
     * 获取多货退货数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function audit_transfer_show($params)
    {
        $url = $this->_baseUrl . $this->_auditTransferShowUrl;
        return $this->request_http($params, $url);

    }


    /**
     * 获取栏目页面
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function audit_transfer_order($params)
    {
        $url = $this->_baseUrl . $this->_auditTransferOrderUrl;
        //return $this->request_http($params, $url,'POST');
        return  $this->_curlReadHandleApi($url, $params, 'POST');

    }


    /**
     * 获取栏目页面
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/17
     */
    public function batch_audit_transfer_order($params)
    {
        $url = $this->_baseUrl . $this->_batchAuditTransferOrderUrl;
        //return $this->request_http($params, $url,'POST');
        return  $this->_curlReadHandleApi($url, $params, 'POST');

    }



    /**
     * 查看调拨信息
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function view_transfer_detail($params)
    {
        $url = $this->_baseUrl . $this->_viewTransferDetailUrl;
        return $this->request_http($params, $url);

    }

    /**
     * 获取多货总金额
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * @author Dean 2020/01/16
     */
    public function multiple_list_amount_total($params)
    {
        $url = $this->_baseUrl . $this->_multipleListAmountTotalUrl;
        return $this->request_http($params, $url);

    }


    /**
     * 导出CSV
     * @author liwuxue
     * @date 2019/1/26 11:31
     * @param array $get
     * @throws Exception
     *
     */
    public function multiple_export_csv($get)
    {
        $url = $this->_baseUrl . $this->_multipleExportCsvUrl . "?" . http_build_query($get);
        $res = $this->_curlReadHandleApi($url, "", 'GET');

        $this->load->library("CommonHelper");

        if(isset($res['status']) and $res['status'] == 0){
            return $res;
        }else{
            CommonHelper::arrayToCsv(
                isset($res['data_list']['key']) ? $res['data_list']['key'] : '',
                isset($res['data_list']['value']) ? $res['data_list']['value'] : '',
                '多货列表导出-'.date('YmdH_i_s') . ".csv"
            );
        }
    }




























}