<?php
/**
 * 异常列表模型类
 * User: Jaxton
 * Date: 2019/01/16 10:06
 */

class Abnormal_list_model extends Api_base_model {
	protected $table_name = 'purchase_warehouse_abnormal';//异常表

	private $success=false;
	private $error_msg='';
	private $success_msg='';

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
        //$this->load->helper(['user','abnormal']);
    }

    /**
    * 获取异常数据列表
    * @param $params
    * @param $offset
    * @param $limit
    * @return array   
    * @author Jaxton 2019/01/16
    */
    public function get_abnormal_list($params){
        $url = $this->_baseUrl . $this->_listUrl;
        return $this->request_http($params,$url);
    	
    }


    /**
    * 采购员处理提交
    * @param array $params 
    * @return array   
    * @author Jaxton 2019/01/16
    */
    public function buyer_handle_submit($params){
        if(empty($params['defective_id'])) {$this->_errorMsg = '缺少异常单号';return;}
        if(empty($params['handler_type'])) {$this->_errorMsg = '处理类型必须选择';return;}
//        if(empty($params['pur_number'])) {$this->_errorMsg = '采购单号必须填写';return;}
//        if(empty($params['return_province']) || empty($params['return_city']) || empty($params['return_county']))
//            {$this->_errorMsg = '退货省、市、县(区)必须填';return;}
//        if(empty($params['return_address'])) {$this->_errorMsg = '退货详细地址必填';return;}
//        if(empty($params['return_linkman']) || empty($params['return_phone'])) {$this->_errorMsg = '退货联系人和联系电话必填';return;}

        $url = $this->_baseUrl . $this->_buyer_handleUrl;
        return $this->request_http($params,$url,'GET',false);
    	
    }

    
    /**
    * 驳回
    * @param defective_id
    * @param reject_reason  
    * @return array   
    * @author Jaxton 2019/01/16
    */
    public function abnormal_reject($params){
    	if(empty($params['defective_id'])){
            $this->_errorMsg = '异常单号必须';return;
        } 
        if(empty($params['reject_reason'])){
            $this->_errorMsg = '驳回原因必填';return;
        }
        // 2.调用接口
        $url = $this->_baseUrl . $this->_rejectUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
    * 查看
    * @param defective_id
    * @param reject_reason  
    * @return array   
    * @author Jaxton 2019/01/16
    */
    function get_look_abnormal($params){
    	if(empty($params['defective_id'])){
            $this->_errorMsg = '异常单号必须';return;
        }
        // 2.调用接口
        $url = $this->_baseUrl . $this->_lookUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
    * 获取省
    * @return array   
    * @author Jaxton 2019/01/23
    */
    public function get_province(){
        // 2.调用接口
        $url = $this->_baseUrl . $this->_provinceUrl;    
        return $this->request_http($params,$url,'GET',false);
    }

    /**
    * 获取市
    * @return array   
    * @author Jaxton 2019/01/23
    */
    public function get_city_county($params){
        if(empty($params['pid'])){
            $this->_errorMsg = '请选择上一级行政区';
            return;
        }
        // 2.调用接口
        $url = $this->_baseUrl . $this->_city_countyUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
     * 异常列表导出
     * @author jeff
     * 2019-1-16
     */
    public function web_abnormal_export_list($params){
        $url = $this->_baseUrl . $this->_abnormal_exportUrl;
        $url .= '?' . http_build_query($params);
        $data = getCurlData($url, "","POST", "",false,array('time_out'=>600,'conn_out'=>600));

        if($params['type'] == 1) {
            $result = json_decode($data, true);
            return $result;
        }else{
            $this->load->helper('export_excel');
            $data = json_decode($data,True);
            if( !empty($data) && isset($data) ) {
                $data_list = $data['data_list'];
                if ( isset($data['status']) && $data['status'] == 1) {  //正常下载
                    export_excel($data['data_list']['heads'], $data['data_list']['data_values'], $data['data_list']['file_name'], $data['data_list']['field_img_name'], $data['data_list']['field_img_key']);
                } else {  //超出限制下载固定模板
                    header('location:' . $data['errorMess']);
                }
            }else{
                echo "导出错误";

            }
        }


    }

    /**
     * @desc 异常单操作日志
     * @author Jeff
     * @Date 2019/7/26 16:57
     * @param $params
     * @return array|mixed|null
     * @return
     */
    public function get_abnormal_operator_log($params)
    {
        $url = $this->_baseUrl . $this->_getoperator_logUrl;
        return $this->request_http($params, $url);
    }

    /**
     * @desc 异常单统计数据
     * @author Jeff
     * @Date 2019/7/26 16:57
     * @param $params
     * @return array|mixed|null
     * @return
     */
    public function get_sum_data($params)
    {
        $url = $this->_baseUrl . $this->_get_sum_dataUrl;
        return $this->request_http($params, $url);
    }



    /**
     * 添加异常备注
     * @param defective_id
     * @param reject_reason
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function add_abnormal_note($params){
        if(empty($params['defective_id'])){
            $this->_errorMsg = '异常单号必须';return;
        }
        if(empty($params['abnormal_note'])){
            $this->_errorMsg = '备注必填';return;
        }
        // 2.调用接口
        $url = $this->_baseUrl . $this->_addAbnormalNoteUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
     * 判断采购单是否存在
     * @param array $params
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function is_order_exist($params){
        if(empty($params['purchase_number'])) {$this->_errorMsg = '请输入采购单号';return;}

        $url = $this->_baseUrl . $this->_isOrderExistUrl;
        return $this->request_http($params,$url,'GET',false);

    }


    public function get_headerlog($params){
        $url = $this->_baseUrl . $this->_get_headerlog;
        return $this->request_http($params, $url);

    }

    public function save_table_list($params){

        $url = $this->_baseUrl . $this->_save_table_list;
        $result = $this->_curlWriteHandleApi($url, $params, 'POST');
        return $result;
    }


    /**
     * 采购员处理提交
     * @param array $params
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function batch_buyer_handle($params)
    {



        $url = $this->_baseUrl . $this->_batchBuyerHandleUrl;
        return $this->_curlReadHandleApi($url, $params, 'POST');


    }

    /**
     * 智能分析地址阿里接口
     * @param array $params
     * @return array
     * @author Jaxton 2019/01/16
     */
    public function analysis_return_address($params){

        $url = $this->_baseUrl . $this->_analysisReturnAddress;
        return $this->_curlReadHandleApi($url, $params, 'POST');

    }

    
}