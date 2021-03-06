<?php
/**
 * 产品信息修改表
 * User: Jaxton
 * Date: 2018/01/24 
 */
class Product_mod_audit_model extends Api_base_model {
    protected $table_name   = 'product_update_log';// 数据表名称

    private $success=false;
	private $error_msg='';
	private $success_msg='';

    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }
    /**
    * 获取审核数据列表
    * @author Jaxton 2019/01/24
    * @param $params
    * @param $limit
    * @param $offset
    * @return array
    */
    public function get_product_list($params){
        // 1.预处理请求参数
        $params['limit'] = !isset($params['limit']) || intval($params['limit']) <= 0 ?
            $this->_defaultPageSize :
            min(intval($params['limit']), $this->_maxPageSize);

        if (!isset($params['page']) || intval($params['page']) <= 0) {
            $params['page'] = 1;
        }
        // End

        // 2.调用接口
        $url = $this->_baseUrl . $this->_listUrl;

        return $this->request_http($params,$url);
        
    }

    /**
    * 格式化数据列表
    * @author Jaxton 2019/01/24
    * @param $data
    * @return array
    */
    public function formart_product_list($data){
        if(!empty($data)){
            foreach($data as $key => $val){
                $data[$key]['audit_status'] = getProductModStatus($val['audit_status']);
                $data[$key]['is_sample'] = getProductIsSample($val['is_sample']);
                $data[$key]['sample_check_result'] = getProductSampleCheckResult($val['sample_check_result']);
                $data[$key]['product_status'] = getProductStatus($val['product_status']);
            }
        }
        return $data;
    }

    /**
    * 获取下拉列表
	* @param $type
    * @return array
    * @author Jaxton 2019/01/21
    */
    public function get_down_list($type=null){
    	$list=[
    		'is_sample_list' => getProductIsSampleDownBox(),
	    	'sample_check_result_list' => getProductSampleCheckResult(),
	    	'audit_status_list' => getProductModStatus(),
	    	'user_list' => [
	    		'1' => '战三',
	    		'2' => '李四'
	    	]
    	];
    	return isset($type) ? $list[$type] : $list;
    }

    /**
    * 根据条件获取数据
    * @param $where
    * @return array
    * @author Jaxton 2019/01/25
    */
    public function get_info_by_map($where){
    	if(empty($where)) $where='1=1';
    	return $this->purchase_db->select('*')->from($this->table_name)
    	->where($where)
    	->get()->result_array();
    }

    /**
    * 根据id获取数据
    * @param $ids
    * @return array
    * @author Jaxton 2019/01/25
    */
    public function get_info_by_ids($ids){
    	return $this->purchase_db->select('*')->from($this->table_name)
    	->where_in('id',$ids)
    	->get()->result_array();
    }

    /**
    * 审核
	* @param $ids
	* @param $audit_result
	* @param $remark
    * @return array
    * @author Jaxton 2019/01/25
    */
    public function product_audit($params){
        if(empty($params['id'])){
            $this->_errorMsg = '缺少参数ID或者类型错误';
            return;
        }
        if(empty($params['audit_result']) || !in_array($params['audit_result'],[1,2])){
            $this->_errorMsg = '审核结果错误，请检查';
            return;
        }
        if($params['audit_result'] == 2 && empty($params['remark'])){
            $this->_errorMsg = '驳回请填写原因';return;
        }
        // 2.调用接口
        $url = $this->_baseUrl . $this->_audit_handleUrl;

        return $this->request_http($params,$url,'GET',false);
        
    	
    }

    /**
    * 品控审核(产品系统)
    * @param $id
    * @return array
    * @author Jaxton 2019/01/25
    */
    public function product_control_audit($id,$audit_result){
        if($id){
            $order_info = $this->get_info_by_ids($id)[0];
            if(!empty($order_info)){
                if($order_info['audit_status']==2){
                    if($audit_result==1){
                        $edit_data=[
                            'audit_status' => 3
                        ];
                    }else{
                        $edit_data=[
                            'audit_status' => 4
                        ];
                    }
                    $this->purchase_db->trans_begin();
                    $edit_result = $this->purchase_db->where('id',$id)->update($this->table_name,$edit_data);
                    if ($this->purchase_db->trans_status() === FALSE)
                    {
                        $this->purchase_db->trans_rollback();
                        $this->error_msg.='操作失败';
                    }
                    else
                    {
                        $this->purchase_db->trans_commit();
                        $this->success=true;
                    }
                }else{
                    $this->error_msg .= '当前状态不是[待品控审核]';
                }
                
            }else{
                $this->error_msg .= '未获取到相关信息';
            }
            
            return [
                'success' => $this->success,
                'error_msg' => $this->error_msg
            ];
        }
    }


    /**
     * 产品审核列表导出
     * @author Jaden
     * 2019-1-16
     */
    public function web_product_audit_export($params){
        $url = $this->_baseUrl . $this->_audit_export;
//        $url .= '?' . http_build_query($params);
//        $result = $this->httpRequest($url, '', 'GET');
        $result = $this->httpRequest($url, $params);
        if (empty($result) || !isset($result['status'])) {
            $this->_errorMsg = "Request Error:" . json_encode($result);
            return null;
        }
        if (0==$result['status']) {
            $this->_errorMsg = $result['errorMess'];
        }
        if (!$result['status']) {
            return null;
        }
        // End
        return $result;
    }


    public function get_product_log($params) {

        $url = $this->_baseUrl . $this->_get_product_log;
        return $this->request_http($params,$url);
    }

    function getProductAuditLog($params){
        $url = $this->_baseUrl . $this->_getProductAuditLog;
        return $this->request_http($params,$url);

    }
    function get_drop_box($params){
        $url = $this->_baseUrl . $this->_get_drop_box;
        return $this->request_http($params,$url);
    }

    public function get_supplier_avg($params){

        $url = $this->_baseUrl . $this->_get_supplier_avg;
        return $this->request_http($params,$url);
    }
}