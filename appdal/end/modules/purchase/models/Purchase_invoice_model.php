<?php
/**
 * Created by PhpStorm.
 * 开票清单
 * User: Jaden
 * Date: 2018/12/27 0027 11:23
 */

class Purchase_invoice_model extends Purchase_model {

    protected $table_name   = 'purchase_invoice_list';// 数据表名称
	protected $declare_customs_table = 'declare_customs';
    protected $table_invoice_detail = 'purchase_items_invoice_info';
    protected $table_invoice_item = 'purchase_invoice_item';
    protected $table_purchase_order = 'purchase_order';
    protected $table_product = 'product';
    protected $table_purchase_order_items = 'purchase_order_items';

    public function __construct(){
        parent::__construct();

        $this->load->model('declare_customs_model');
        $this->load->helper('status_order');
    }


     /**
     * 生成发票清单数据
     * @author Jaden 2019-1-10
     * @param array $insert_data  插入数据表的数据
     */
    public function save_purchase_invoice($insert_data){
        if(empty($insert_data)) {
            return false;
        }    
        $this->purchase_db->insert($this->table_name,$insert_data);
        return true;
    }

    /**
     * 获取 发票清单列
     * @author Jaden
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     * 2019-1-8
     */
    public function purchase_invoice_list($params, $offset, $limit,$page=1){
        $this->purchase_db->select('a.*,b.invoice_code_left,b.invoice_code_right');

        $this->purchase_db->from($this->table_name.' a');

      //  if((isset($params['invoice_code_left']) && !empty($params['invoice_code_left'])) || (isset($params['invoice_code_right']) && !empty($params['invoice_code_right']))){
            $this->purchase_db->join($this->declare_customs_table.' b','a.invoice_number=b.invoice_number','left');
    //    }

        if(isset($params['invoice_number']) && !empty($params['invoice_number'])){
            $this->purchase_db->where('a.invoice_number',$params['invoice_number']);
        }
        if(isset($params['purchase_user_id']) && !empty($params['purchase_user_id'])){
            $this->purchase_db->where('a.purchase_user_id',$params['purchase_user_id']);
        }
        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('a.supplier_code',$params['supplier_code']);
        }
        if(isset($params['audit_status']) && !empty($params['audit_status'])){
            $this->purchase_db->where('a.audit_status',$params['audit_status']);
        }
        if(isset($params['create_time_start']) && !empty($params['create_time_start'])){
            $this->purchase_db->where('a.create_time>=',$params['create_time_start']);
        }
        if(isset($params['create_time_end']) && !empty($params['create_time_end'])){
            $this->purchase_db->where('a.create_time<=',$params['create_time_end']);
        }
        if(isset($params['invoice_code_left']) && !empty($params['invoice_code_left'])){
            $this->purchase_db->where('b.invoice_code_left',$params['invoice_code_left']);
        }
        if(isset($params['invoice_code_right']) && !empty($params['invoice_code_right'])){
            $this->purchase_db->where('b.invoice_code_right',$params['invoice_code_right']);
        }
        $this->purchase_db->group_by('a.invoice_number');
        //统计总数
        $clone_db = clone($this->purchase_db);
        $total_count=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数
        $this->purchase_db=$clone_db;
        $results = $this->purchase_db->limit($limit, $offset)->get()->result_array();
        $this->load->model('purchase_suggest/forecast_plan_model');
        $this->load->model('user/Purchase_user_model');
        $user_list=$this->Purchase_user_model->get_user_all_list();
        $return_data = [
            
            'data_list' => [
                'value' => $results,
                'key'   => [
                    'ID','发票清单号','发票总金额','币种','供应商名称','发票代码','发票号码','发票清单时间','采购员','状态','创建人/创建时间','提交人/提交时间',
                    '审核人/审核时间','操作'
                ],
                'drop_down_box' => [
                    'user_list' => array_column($user_list, 'name','id'),
                    //'supplier_list' => $this->forecast_plan_model->get_supplier_down_box(),
                    'status_list' => invoice_number_status(),
                ],
            ],
            'paging_data' => [
                'total'     => $total_count,
                'offset'    => $page,
                'limit'     => $limit,
            ]
        ];
        return $return_data;  
    }

    /**
    * 数据格式化
    * @param $data_list
    * @return array
    * @author Jaxton 2019/01/19
    */
    public function formart_purchase_invoice_list($data_list){
        if(!empty($data_list)){
            foreach($data_list as $key => $val){
                $data_list[$key]['audit_status']=invoice_number_status($val['audit_status']);
            }
        }
        return $data_list;
    }


    /**
     * 提交发票清单
     * @author Manson
     * @param int $states  需要改变的状态码
     * @param string $invoice_number  发票清单号
     */
    public function submit_invoice($invoice_number,$data){
        if( empty($invoice_number) ){
            return false;
        }
        $this->purchase_db->where('invoice_number', $invoice_number);
        $this->purchase_db->where('audit_status', INVOICE_STATES_WAITING_CONFIRM);
        $update_result = $this->purchase_db->update($this->table_name, $data);
        return $update_result;


    }


    /**
     * 根据发票清单号删除数据
     * @author Manson 2019-11-30
     * @param string $invoice_number  发票清单号
     */
    public function delete_invoice($invoice_number){
        if(empty($invoice_number)){
            return false;
        }
        $error_msg = '';
        $result_data = array();
        $invoice_number_arr = explode(',', $invoice_number);
        if (count($invoice_number_arr)>200){
            $result_data['code'] = 0;
            $result_data['msg'] = '撤销数量异常,不能超过200条';
            return $result_data;
        }
        //删除该发票清单
        $invoice_number_list = $this->purchase_db->select('audit_status,invoice_number')->where_in('invoice_number', $invoice_number_arr)->get($this->table_name)->result_array();
        if(!empty($invoice_number_list)){
            $invoice_number_list = array_column($invoice_number_list,'audit_status','invoice_number');
            foreach ($invoice_number_arr as $invoice_number) {
                //查看是否待提交状态，如果不是待提交状态，不能撤销
                if (!isset($invoice_number_list[$invoice_number])){
                    $error_msg .= $invoice_number . '异常,找不到数据;';
                    continue;
                }
                $audit_status =  $invoice_number_list[$invoice_number]??'';
                if ($audit_status != INVOICE_STATES_WAITING_CONFIRM) {
                    $error_msg .= $invoice_number . '不是待提交状态;';
                    continue;
                }
            }
        }else{
            $error_msg.='找不到发票清单数据';
        }

        if(empty($error_msg)){
            //根据发票清单号查询po+sku
            $po_sku_data = $this->purchase_db->select('b.id')
                ->from($this->table_invoice_item. ' a')
                ->join($this->table_purchase_order_items. ' b','a.purchase_number = b.purchase_number AND a.sku = b.sku','left')
                ->where_in('a.invoice_number',$invoice_number_arr)
                ->group_by('b.id')
                ->get()->result_array();

            foreach ($po_sku_data as &$item){
                $item['invoice_status'] = INVOICE_STATUS_NOT;
            }
            $this->purchase_db->trans_start();
            $this->purchase_db->update_batch($this->table_purchase_order_items,$po_sku_data,'id');
            $this->purchase_db->where_in('invoice_number',$invoice_number_arr)->delete($this->table_name);
            $this->purchase_db->where_in('invoice_number',$invoice_number_arr)->delete('purchase_invoice_item');
            $this->purchase_db->trans_complete();
            if ($this->purchase_db->trans_status() === true){
                $result_data['code'] = 1;
                $result_data['msg'] = '撤销成功';
            }else{
                $result_data['code'] = 0;
                $result_data['msg'] = '撤销失败,请稍后重试';
            }
        }else{
            $result_data['code'] = 0;
            $result_data['msg'] = $error_msg;
        }
        return $result_data;
    }


    /**
     * 根据发票清单号获取信息
     * @author Jaden 2019-1-10
     * @param string $invoice_number  发票清单号
     */
    public function get_invoice_one($invoice_number){
        if(empty($invoice_number)){
            return [];
        }
        $this->purchase_db->where('invoice_number', $invoice_number);
        $results = $this->purchase_db->get($this->table_name)->row_array();
        return $results;
    }


    /**
     * 批量开票提交
     */
    public function batch_invoice_submit($insert_data,$update_data,$invoice_number_list)
    {
        $this->purchase_db->trans_start();
        $this->purchase_db->update_batch($this->table_name,$update_data,'invoice_number');
        if(!empty($insert_data)){

            $childrenKeys=0;
            foreach($insert_data as $key=>$value){
                ++$childrenKeys;
               $insert_data[$key]['children_invoice_number'] = $value['invoice_number']."-".$childrenKeys;
            }
        }

        $this->purchase_db->insert_batch($this->table_invoice_detail,$insert_data);
        //开票后 处理未填写开票信息的数据
        $result = $this->purchase_db->select('a.*')
            ->from($this->table_invoice_item. ' a')
            ->join($this->table_invoice_detail. ' b','a.purchase_number = b.purchase_number AND a.sku = b.sku AND a.invoice_number = b.invoice_number','left')
            ->where('b.id',null)
            ->where_in('a.invoice_number',$invoice_number_list)
            ->get()->result_array();

        if (!empty($result)) {
            $insert_data = [];
            $result['error_msg'] = '';
            foreach ($result as $item) {
//                pr($item);exit;
                $result['error_msg'] .= sprintf('备货单:%s未填写发票代码; ',$item['demand_number']??'');


//                $insert_data[] = [
//                    'invoice_number'      => $item['invoice_number'],
//                    'purchase_number'     => $item['purchase_number'],
//                    'sku'                 => $item['sku'],
//                    'demand_number'       => $item['demand_number'],
//                    'create_user'         => getActiveUserName(),
//                    'create_time'         => date('Y-m-d H:i:s'),
//                ];
            }
            $this->purchase_db->trans_rollback();
            $result['success'] = false;
            return $result;
//            $this->purchase_db->insert_batch($this->table_invoice_detail, $insert_data);
        }
        $this->purchase_db->trans_complete();
        if ($this->purchase_db->trans_status() == false){
            $result['error_msg'] = '开票失败,请稍后再试';
        }else{
            $result['success'] = true;
        }
        return $result;
    }

    /**
     * 验证开票的数据是否满足条件
     * @author Manson
     * @param $invoice_code_data
     * @param $invoice_info
     * @return mixed
     */
    public function validate_data_format($invoice_code_data,$invoice_info)
    {
        if (empty($invoice_code_data) || empty($invoice_info)){
            $result['code'] = 0;
            $result['error_msg'] = '数据异常';
            return $result;
        }

        $error_msg = '';
        $update_status = [];
        $success_data = [];
        //验证是否满足开票条件
        foreach ($invoice_code_data as $invoice_number => $item){
            $total_invoice_qty = [];
            $uni_arr = [];//开票清单号+po+sku+发票代码(左)+发票代码(右) 唯一
            foreach ($item as $k => $val){
                if (!is_numeric($val['invoice_code_left'])){
                    $error_msg .= sprintf('发票代码(左):%s,不是数字,请重新填写',$val['invoice_code_left']);
                }
                if (strlen($val['invoice_code_left']) != 10) {
                    $error_msg .= sprintf('发票代码(左):%s,位数限制为10位，请重新填写',$val['invoice_code_left']);
                }
                if (!is_numeric($val['invoice_code_right'])){
                    $error_msg .= sprintf('发票代码(右):%s,不是数字,请重新填写',$val['invoice_code_right']);
                }
                if (strlen($val['invoice_code_right']) != 8) {
                    $error_msg .= sprintf('发票代码(右):%s,位数限制为8位，请重新填写',$val['invoice_code_right']);
                }
                if ($val['invoice_coupon_rate']<0 || $val['invoice_coupon_rate']>1 || !is_numeric($val['invoice_coupon_rate'])){//票面税率
                    $error_msg .= sprintf('发票清单:%s,填写有误,票面税率可修改范围: 0≤票面税率≤1',$invoice_number);
                }
                if (!is_numeric($val['invoice_value'])){
                    $error_msg .= sprintf('发票清单:%s,发票金额不是数字',$invoice_number);
                }
                if (!is_numeric($val['taxes'])){
                    $error_msg .= sprintf('发票清单:%s,税金不是数字',$invoice_number);
                }
                if (!is_numeric($val['invoiced_amount'])){
                    $error_msg .= sprintf('发票清单:%s,已开票金额不是数字',$invoice_number);
                }

                if (!positiveInteger($val['invoiced_qty'],1)){
                    $error_msg .= sprintf('发票清单号为:%s,已开票数量不是正整数',$invoice_number);
                }else {
                    //sum已开票数量≤可开票数量
                    if (isset($total_invoice_qty[$val['demand_number']])) {
                        $total_invoice_qty[$val['demand_number']] += $val['invoiced_qty'];
                    } else {
                        $total_invoice_qty[$val['demand_number']] = $val['invoiced_qty'];
                    }
                }
                $tag = sprintf('%s%s%s%s',$invoice_number,$val['demand_number'],$val['invoice_code_left'],$val['invoice_code_right']);

                if (isset($uni_arr[$tag])){
                    $error_msg .= sprintf('发票清单号:%s,备货单号:%s,发票代码(左):%s,发票代码(右):%s,出现重复',$invoice_number,$val['demand_number'],$val['invoice_code_left'],$val['invoice_code_right']);
                }else{
                    $uni_arr[$tag] = 1;
                }




                $success_data[] = [
                    'invoice_number' => $invoice_number,
                    'purchase_number' => $val['purchase_number'],
                    'sku' => $val['sku'],
                    'demand_number' => $val['demand_number'],
                    'invoiced_qty' => $val['invoiced_qty'],
                    'invoice_coupon_rate' => $val['invoice_coupon_rate'],
                    'invoice_value' => $val['invoice_value'],
                    'taxes' => $val['taxes'],
                    'invoiced_amount' => $val['invoiced_amount'],
                    'invoice_code_left'  => $val['invoice_code_left'],
                    'invoice_code_right'  => $val['invoice_code_right'],
                    'create_user' => getActiveUserName(),
                    'create_time' => date('Y-m-d H:i:s'),
                ];

            }

            foreach ($total_invoice_qty as $key => $_invoice_qty){
                //可开票数量 开票状态
                $tag = sprintf('%s%s',$invoice_number,$key);
                $app_invoice_qty = $invoice_info[$tag]['app_invoice_qty']??0;
                $audit_status = $invoice_info[$tag]['audit_status']??0;
                if ($_invoice_qty > $app_invoice_qty){
                    $error_msg .= sprintf('备货单号:%s,已开票数量>可开票数量',$key);
                }
                if ($audit_status != INVOICE_STATES_WAITING_MAKE_INVOICE){
                    $error_msg .= sprintf('发票清单号:%s,不是待采购开票状态',$invoice_number);
                }
            }
            if (!isset($update_status[$invoice_number])){
                $update_status[$invoice_number] = [
                    'invoice_number' => $invoice_number,
                    'audit_status' => INVOICE_STATES_WAITING_FINANCE_AUDIT
                ];
            }
        }

        if (!empty($error_msg)){
            $result['code'] = 0;
            $result['error_msg'] = $error_msg;
        }else{
            $result['code'] = 1;
            $result['success_data'] = $success_data;
            $result['update_status'] = $update_status;
        }

        return $result;
    }

    public function childrenNumber($where){

        return $this->purchase_db->from($this->table_invoice_detail)->where($where)->select("id,children_invoice_number")->get()->row_array();
    }

    public function invoicePrice($invoNumber){

        return $this->purchase_db->from("purchase_invoice_list")->where("invoice_number",$invoNumber)->select("id,invoice_amount")->get()->row_array();
    }



}