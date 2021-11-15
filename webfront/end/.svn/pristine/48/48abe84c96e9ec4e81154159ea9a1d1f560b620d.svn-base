<?php
/**
 * 发票清单模型类
 * User: Jaxton
 * Date: 2019/01/10 18:06
 */

class Invoice_list_model extends Api_base_model {
    public function __construct(){
        parent::__construct();
        $this->init();
        $this->setContentType('');
    }

    /**
    * 获取单条发票清单信息
    * @param $invoice_number
    * @return array   
    * @author Jaxton 2019/01/12
    */
    public function get_one_invoice($invoice_number){
    	$row=$this->purchase_db->select('*')
    	->from($this->table_name)
    	->where('invoice_number',$invoice_number)
    	->get()
    	->row_array();
    	if(!empty($row)){
    		return $row;
    	}else{
    		return false;
    	}
    }
    /**
    *获取多条清单详情
    * @param $invoice_number_arr
    * @return array   
    * @author Jaxton 2019/01/12
    */
    public function get_many_invoice_detail($invoice_number_arr,$limit=null,$offset=null){
    	$this->purchase_db->select('*')
    	->from($this->table_name.' a')
    	->join($this->declare_customs_table.' b','a.invoice_number=b.invoice_number')
    	->where_in('a.invoice_number',$invoice_number_arr);
        if($limit && $offset){
            $this->purchase_db->limit($limit,$offset);
        }
        //$clone_db = clone($this->purchase_db);
        //$total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        //$this->purchase_db=$clone_db;
    	$list=$this->purchase_db->get()
    	->result_array();
    	return [
            'list' =>$list,
            // 'total' => $total
        ];
    }

    /**
    * 获取发票清单sku
    * @param $params
    * @return array   
    * @author Jaxton 2019/01/11
    */
    public function get_invoice_detail_list($params){
    	$url= $this->_baseUrl . $this->_btach_invoice_listUrl;
        //return $params;
        return $this->request_http($params,$url);
    }

    
    /**
    * 批量开票提交
    * @param $params
    * @return array   
    * @author Jaxton 2019/01/11
    */
    public function btach_invoice_submit($params){
    	$url= $this->_baseUrl . $this->_btach_invoice_submitUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
    * 财务审核
    * @param $invoice_number
    * @param $remark
    * @param $review_result
    * @return array   
    * @author Jaxton 2019/01/12
    */
    public function invoice_finance_review($params){
    	$url= $this->_baseUrl . $this->_invoice_finance_reviewUrl;
        return $this->request_http($params,$url,'GET',false);
    }

    /**
    * 获取下载发票明细数据
    * @param $params    
    * @return array   
    * @author Jaxton 2019/01/12
    */
    public function get_download_invoice_detail($params){
        $url= $this->_baseUrl . $this->_download_invoice_detailUrl;
        return $this->request_http($params,$url,'GET',false);   	
    }

}