<?php

/**
 * Created by PhpStorm.
 * 请款单
 * User: jackson
 * Date: 2019/01/10 0027 11:23
 */
class Purchase_order_pay_model extends Api_base_model
{

    protected $table_name = 'purchase_order_pay';

    public function __construct()
    {
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
     * 网采单-创建请款单-数据预览
     * @author jackson
     * @desc array $_POST['purchase_numbers'] 要请款采购单号
     */
    public function network_pay_order_preview($params = array())
    {

        //1.调用接口
        $result = $this->_curlRequestInterface($this->_previewUrl, $params, 'POST');
        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        //add tableTable
      //  $_tableHeader = array();//表头
     //   $result = $this->add_table_header($result, $_tableHeader);
        return $result;

    }

    /**
     * 网采单-请款单
     * @author Jolon
     * @desc array $_POST['purchase_numbers'] 要请款采购单号
     */
    public function network_pay_order_create($params = array())
    {

        //1.调用接口
        $url = $this->_baseUrl . $this->_payOrderCreateUrl;
        $result = $this->httpRequest($url, $params, 'POST');
        return $result;
    }

    /**
     * 合同单-创建请款单-数据预览
     * @author Jolon
     * @desc array $_POST['compact_number']         要请款合同号
     *              $_POST['requisition_payment']    为空：合同请款数据预览，不为空：预览 付款申请书
     */
    public function compact_pay_order_preview($params = array())
    {

        //1.调用接口
        $result = $this->_curlRequestInterface($this->_payOrderPreviewCreateUrl, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        if(isset($result['status']) and $result['status'] == 0){
            return $result;
        }

        //add tableTable
        if (isset($result['data_list']['is_confirm']) && $result['data_list']['is_confirm'] == 1){
            return $result;
        }
        $_tableHeader = array();//表头
        $result = $this->add_table_header($result, $_tableHeader);
        return $result;

    }

    /**
     * 合同单-创建请款单-付款申请书预览
     *      在 compact_pay_order_preview 生成付款申请书之后调用
     * @author Jolon
     * @desc array $_POST[]  要请款合同号
     */
    public function compact_pay_order_create($params = array())
    {

        //1.调用接口
        $result = $this->_curlRequestInterface($this->_paycompactCreateUrl, $params, 'POST');

        //2.判断返回结果
        if (is_null($result)) {
            return null;
        }
        // End

        //add tableTable
        $_compactTableHeader = array();//表头
        return array(
            'data_list' => array(
                'key' => $_compactTableHeader,
                'value' => $result['data_list'],
            )
        );

    }

    /**
     * 增加KEY头
     * @author Jolon
     */
    public function add_table_header($result = array(), $_tableHeader = '')
    {
        $responsed = array();
        //添加key
        if (!empty($result['data_list']) && $_data = $result['data_list']) {

            $responsed['status'] = $result['status'];
            $responsed['data_list'] = array();
            foreach ($_data as $key => $item) {
                if (is_array($_data[$key])) {
                    $responsed['data_list'][$key]['key'] = $_tableHeader;//添加tableHeader
                    $responsed['data_list'][$key]['value'] = $_data[$key];//值
                }
            }

        }
        return $responsed;

    }


    /**
     * 审核保存数据
     * @param array $id 数组id
     * @param string $review_notice 审核备注
     * @param int $type 1 是审核通过  2是审核驳回
     * @author harvin 2019-1-12
     * * */
    public function payment_examine_save($params)
    {
        $url = $this->_baseUrl . $this->_examinetCreateUrl;
        $result = $this->httpRequest($url, $params);
        return $result;

    }


    /** --------------start-------------- */
    protected $_searchApi = "";
    protected $_paymentListApi = "";
    protected $_paymentExamineApi = "";
    protected $_paymentInfoApi = "";
    protected $_paymentExportApi = "";

    /**
     * 获取搜索筛选项的筛选值
     * @author liwuxue
     * @date 2019/1/30 16:04
     * @param
     * @return mixed
     * @throws Exception
     */
    public function get_search_list()
    {
        $url = $this->_baseUrl . $this->_searchApi;
        return $this->_curlReadHandleApi($url, "", "GET");
    }

    /**
     * 获取搜索筛选项的筛选值
     * @author liwuxue
     * @date 2019/1/30 16:04
     * @param $get
     * @return mixed
     * @throws Exception
     */
    public function get_payment_list($get)
    {
        $url = $this->_baseUrl . $this->_paymentListApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", "GET");
    }

    /**
     * 获取搜索筛选项的筛选值
     * @author liwuxue
     * @date 2019/1/30 16:04
     * @param $post
     * @return mixed
     * @throws Exception
     */
    public function payment_examine($post)
    {
        $url = $this->_baseUrl . $this->_paymentExamineApi;
        return $this->_curlWriteHandleApi($url, $post, "POST");
    }

    /**
     * 获取搜索筛选项的筛选值
     * @author liwuxue
     * @date 2019/1/30 16:04
     * @param $get
     * @return mixed
     * @throws Exception
     */
    public function get_payment_info($get)
    {
        $url = $this->_baseUrl . $this->_paymentInfoApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", "GET");
    }
     /**
     * 获取搜索筛选项的筛选值
     * @author liwuxue
     * @date 2019/1/30 16:04
     * @param $get
     * @return mixed
     * @throws Exception
     */
    public function payment_examine_info($get)
    {
        $url = $this->_baseUrl . $this->_paymentexamineInfoApi . "?" . http_build_query($get);
        return $this->_curlReadHandleApi($url, "", "GET");
    }
    /**
     * 获取搜索筛选项的筛选值
     * @author liwuxue
     * @date 2019/1/30 16:04
     * @param $get
     * @return mixed
     * @throws Exception
     */
    public function payment_export($get)
    {
        set_time_limit(0);
        $url = $this->_baseUrl . $this->_paymentExportApi . "?" . http_build_query($get);
        $data = getCurlData($url, "","POST", "",false,array('time_out'=>600));
        $result = json_decode($data,true);
        header('Location:'.$result['data_list']);
//        $title=[];
//        $temp=[];
//        foreach ($data["data_list"] as $key => $value) {
//            if($key==0){
//                 $title=$value;
//            }else{
//                $temp[]=$value;
//            }
//        }
//        //导出csv
//        $this->load->library("CommonHelper");
//        $fileName = '请款单-' . date('YmdHis') . '.csv';
//        CommonHelper::arrayToCsv($title, $temp, $fileName);
    }
    /** --------------end-------------- */


}
