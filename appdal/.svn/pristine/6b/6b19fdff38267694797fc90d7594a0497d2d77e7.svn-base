<?php
/**
 * Created by PhpStorm.
 * 报关详情
 * User: Jaden
 * Date: 2018/12/29 0029 11:50
 */
class Declare_customs_model extends Purchase_model {

    protected $table_name   = 'pur_declare_customs';// 数据表名称

    public function __construct(){
        parent::__construct();
        $this->load->helper('status_order');
    }

    /**
     * 返回表名
     * @author Jaden 2019-1-16
     */
    public function tableName() {
        return 'declare_customs';
    }


    //根据采购单号和SKU查询入库记录
    public function declare_customs_list($purchase_number,$sku,$offset, $limit,$field='*'){
        $this->purchase_db->select($field);
        $this->purchase_db->from($this->table_name);
        $this->purchase_db->where('purchase_number',$purchase_number);
        $this->purchase_db->where('sku',$sku);
        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        $this->purchase_db=$clone_db;
        $results=$this->purchase_db->limit($limit, $offset)->order_by('customs_time','DESC')->get()->result_array();
        //echo $this->purchase_db->last_query();exit;
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }

    /**
     * 根据条件查询发票清单号
     * @author Jaden 2019-1-10
     * @param string $where  查询条件
     * @param string $field  查询字段
     */
    public function get_invoiced_list($where,$offset, $limit,$field){
        $this->load->model('purchase_invoice_model');
        if(empty($where)){
            return false;
        }
        $this->purchase_db->select($field);
        $this->purchase_db->from('declare_customs as cu');
        $this->purchase_db->join('purchase_invoice_list as in', 'cu.invoice_number=in.invoice_number', 'left');
        $this->purchase_db->where($where);
        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        $this->purchase_db=$clone_db;
        $results = $this->purchase_db->limit($limit, $offset)->get()->result_array();
         $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }



    /**
     * 保存发票清单号
     * @author Jaden 2019-1-10
     * @param array $purchase_number  采购单号
     * @param string $invoice_number  发票清单号
     */
    public function save_invoice_number($purchase_number,$invoice_number){
        if(empty($purchase_number) or empty($invoice_number)){
            return flase;
        }
        $this->purchase_db->where_in('purchase_number', $purchase_number);
        $this->purchase_db->where('invoice_number', '');
        $this->purchase_db->update($this->table_name, array('invoice_number'=>$invoice_number,'is_invoice'=>1));
    }

     /**
     * 把该发票清单号清空
     * @author Jaden 2019-1-10
     * @param array $invoice_number  发票清单号
     */
    public function update_invoice_number($invoice_number){
        if(empty($invoice_number)){
            return flase;
        }
        $this->purchase_db->where_in('invoice_number', $invoice_number);
        $this->purchase_db->update($this->table_name, array('invoice_number'=>'','is_invoice'=>0));
    }
    /**
     * 获取下载发票清单明细
     * @author Jaden 
     * @param array $invoice_number
     * @param type $offset
     * @param type $limit
     * @param type $filed
     * @param type $is_left_join
     * @return boolean|array
     */
    public function getByinvoice_number_list($invoice_number,$offset, $limit,$filed='*',$is_left_join=false){
        if(empty($invoice_number)){
            return false;
        }
        $this->purchase_db->select($filed);
        if($is_left_join){
            $this->purchase_db->from($this->table_name.' as a'); 
            $this->purchase_db->join('purchase_order_items as o', 'a.purchase_number=o.purchase_number AND a.sku=o.sku', 'left');
        }else{
            $this->purchase_db->from($this->table_name);    
        }
        if(is_array($invoice_number) && !empty($invoice_number)){
            $this->purchase_db->where_in('a.invoice_number',$invoice_number);
        }else{
             $this->purchase_db->where('a.invoice_number',$invoice_number);
        }
        
        
        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        $this->purchase_db=$clone_db;
        if ($limit == FALSE && $offset == FALSE){//导出查询不限制limit
            $this->purchase_db->order_by('customs_time,invoice_number','DESC');
        }else{
            $this->purchase_db->limit($limit, $offset);
            $this->purchase_db->order_by('customs_time','DESC');
        }
        $results=$this->purchase_db->get()->result_array();
//        echo $this->purchase_db->last_query();exit;
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total,
                'limit'     => $limit,
            ]
        ];
        return $return_data;

    }


    //根据开票清单号取数据
    public function getByinvoice_list($invoice_number,$filed='*'){
        if(empty($invoice_number)){
            return false;
        }
        $this->purchase_db->select($filed);
        $this->purchase_db->from($this->table_name);
        $this->purchase_db->where_in('invoice_number',explode(',', $invoice_number));
        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数  
        $this->purchase_db=$clone_db;
        $results=$this->purchase_db->order_by('customs_time','DESC')->get()->result_array();
        //echo $this->purchase_db->last_query();exit;
        $return_data = [
            'value'   => $results,
            'page_data' => [
                'total'     => $total,
            ]
        ];
        return $return_data;

    }


    /**
     * 根据条件更新发票代码
     * @author Jaden 2019-1-10
     * @param array $where_data  更新条件
     * @param array $update_data  需要更新的数据
     */
    public function update_invoice_code($where_data,$update_data){
        if(empty($where_data) or empty($update_data)){
            return false;
        }
        $this->purchase_db->where($where_data);
        $result = $this->purchase_db->update($this->table_name, $update_data);
        return $result;
    }

    /**
     * 根据条件查询记录
     * @author Jaden 2019-1-10
     * @param string $where  查询条件
     * @param string $field  查询字段
     */
    public function getInvoiceByWhere($where,$field='*'){
        if(empty($where)){
            return false;
        }
       $results = $this->purchase_db
                ->select($field)
                ->where($where)->get($this->table_name)->row_array();
        return $results;
    }
   /**
    * 根据查询获取报关数量
    * @param  $purchase_number_list 
    * @author harvin 2019-3-1
    * @return array
    */
   public function getInvoiceByWherelist(array $purchase_number_list,$is_invoice=null){
       if (empty($purchase_number_list))
            return [];
        $this->purchase_db->select('customs_quantity,sku,purchase_number');
        $this->purchase_db->from($this->table_name);
        if($is_invoice==1){
            $this->purchase_db->where('is_invoice',$is_invoice);  
        }
        $this->purchase_db->where_in('purchase_number', $purchase_number_list);
        $results = $this->purchase_db->get()->result_array();
        if (empty($results))
            return [];
        foreach ($results as  $row) {
            $data[$row['purchase_number']."_".$row['sku']][]=$row['customs_quantity'];
        }
        //统计每个sku对应采购单号 报关总数
        foreach ($data as  $key=>$value) {
            $data[$key]= array_sum($value);
        }
        return isset($data)?$data:[];
    }
    /**
     * 计算开票数量
     * @author Jaden 2019-1-10
     * @param string $where  查询条件
     * @param string $field  查询字段
     */
    public function calculation_invoice($where,$field='*'){
        if(empty($where)){
            return false;
        }
        $this->purchase_db->select($field);
        $this->purchase_db->from('declare_customs as cu');
        $this->purchase_db->join('purchase_invoice_list as in', 'cu.invoice_number=in.invoice_number', 'left');
        $this->purchase_db->where($where);
        $results = $this->purchase_db->get()->row_array();

        //echo $this->purchase_db->last_query();exit;
        return $results;
    }
   /**
    * 计算发票数量集合
    * @param  $invoice_number_list
    * @author harvin
    * @return array 
    */
   public function calculation_invoice_list(array $invoice_number_list){
      //先判断状态 4[已审核] ,
        if (empty($invoice_number_list))
            return [];
        $purchase_invoice_list = $this->purchase_db
                ->select('invoice_number,states')
                ->where_in('invoice_number', $invoice_number_list)
                ->get('purchase_invoice_list')
                ->result_array();
        if (empty($purchase_invoice_list))
            return [];
        $data=[];
        foreach ($purchase_invoice_list as $v) {
            if($v['states']==4){
                $declare_info = $this->getInvoiceByWhere('invoice_number="'.$v['invoice_number'].'"','purchase_number');
                $data[]=$declare_info['purchase_number'];
            }
        }
        if(empty($data)) return [];
        //在统计开票数量
       $invoice_data = $this->getInvoiceByWherelist(array_unique($data));
       return $invoice_data;
       
   }
    /**
     * 获取开票清单
     * @author Jaden 2019-1-10
     * @param string $where  查询条件
     * @param string $field  查询字段
     */
    public function get_invoice_number_list($where,$field){
        $this->load->model('purchase_invoice_model');
        if(empty($where)){
            return [];
        }
        $this->purchase_db->select($field);
        $this->purchase_db->from('declare_customs as cu');
        $this->purchase_db->join('purchase_invoice_list as in', 'cu.invoice_number=in.invoice_number', 'left');
        $this->purchase_db->where($where);
        $results = $this->purchase_db->get()->result_array();
        $invoice_number_arr = array();
        foreach ($results as $key => $value) {
            if(!empty($value['invoice_number'])){
                $invoice_number_arr['datalist'][$value['invoice_number']] = !empty($value['states']) ? invoice_number_status($value['states']):'';
            }
        }
        return $invoice_number_arr;
    }


    /**
     * 根据发票清单号查所有含税订单数据是否完结
     * @author Jaden 2019-1-10
     * @param array $invoice_number_arr  发票清单号
     */
    public function check_purchase_order_is_end($invoice_number_arr){
        if(empty($invoice_number_arr)){
            return [];
        }
        $this->load->model('purchase/purchase_order_items_model');
        $this->purchase_db->select('purchase_number,sku,invoice_number,customs_unit,customs_quantity');
        $this->purchase_db->from($this->table_name);
        $this->purchase_db->where_in('invoice_number',$invoice_number_arr);
        $results = $this->purchase_db->get()->result_array();
        $purchase_number_list = array_column(isset($results)?$results:[], 'purchase_number');
        $declare_customs_info=$this->getInvoiceByWherelist($purchase_number_list); //报关数量集合
        $kai_invoice_info=$this->calculation_invoice_list($invoice_number_arr); //已开票数量结合
        foreach ($results as $key => $value) {
            $purchase_order_items_info = $this->purchase_order_items_model->get_item($value['purchase_number'],$value['sku'],1);
            if($purchase_order_items_info['upselft_amount']=$declare_customs_info[$value['purchase_number'].'_'.$value['sku']]=$kai_invoice_info[$value['purchase_number'].'_'.$value['sku']]){
                $this->db->where('purchase_number="'.$value['purchase_number'].'" AND sku="'.$value['sku'].'"')->update('purchase_order_items',array('is_end'=>1));
            }
            //记录入库数据pur_purchase_product_invoice
            $invoice_data['sku'] = $value['sku'];
            $invoice_data['items_id'] = $purchase_order_items_info['id'];
            $invoice_data['product_name'] = $purchase_order_items_info['product_name'];
            $invoice_data['export_tax_rebate_rate'] = '';
            $invoice_data['invoice_name'] = $value['invoice_number'];
            $invoice_data['issuing_office'] = $value['customs_unit'];
            $invoice_data['invoices_issued'] = $value['customs_quantity'];
            $invoice_data['invoiced_amount'] = $value['customs_quantity']*$purchase_order_items_info['purchase_unit_price'];
            $invoice_data['create_time'] = date('Y-m-d H:i:s');
            $this->purchase_db->insert('purchase_product_invoice',$invoice_data);

        }

    }


    /**
     * 根据采购单号 查询出报关详情
     * @author Manson
     * @param array $purchase_number_list
     * @return array
     */
    public function get_customs_clearance_details(array $purchase_number_list){
        if (empty($purchase_number_list))
            return [];
        $this->purchase_db->select('customs_unit,customs_name,customs_quantity,sku,customs_number,purchase_number,customs_type, customs_time')
            ->from($this->table_name)
            ->where_in('purchase_number', $purchase_number_list)
            ->order_by('customs_time','asc');
        $results = $this->purchase_db->get()->result_array();
        if (empty($results))
            return [];
        foreach ($results as  $item) {
            $_key = sprintf('%s_%s',$item['purchase_number'],$item['sku']);
            //
            $data[$_key]['customs_number'][] = $item['customs_number'];//报关单号
            if (isset($data[$_key]['customs_quantity']) && !empty($data[$_key]['customs_quantity']))//报关数量
            {
                $data[$_key]['customs_quantity'] += $item['customs_quantity'];
            }else{
                $data[$_key]['customs_quantity'] = $item['customs_quantity'];
            }
            $data[$_key]['customs_name'] = $item['customs_name'];//报关品名
            $data[$_key]['customs_type'] = $item['customs_type'];//报关型号
            $data[$_key]['customs_unit'] = $item['customs_unit'];//报关单位
            $data[$_key]['customs_time'] = $item['customs_time'];//报关时间
        }
        return isset($data)?$data:[];
    }

    public function get_customs_qty($purchase_number,$sku){
        if (empty($purchase_number) || empty($sku))
            return [];
        $this->purchase_db->select('sum(customs_quantity) as customs_qty');
        $this->purchase_db->from($this->table_name);
        $this->purchase_db->where('purchase_number', $purchase_number);
        $this->purchase_db->where('sku', $sku);
        $result = $this->purchase_db->get()->row_array();
        if (empty($result) || !isset($result['customs_qty']) || empty($result['customs_qty'])){
            return 0;
        }
        return $result['customs_qty'];
    }


}