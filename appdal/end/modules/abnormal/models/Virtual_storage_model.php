<?php
/**
 * 虚拟入库列表模型类
 * User: Jaxton
 * Date: 2019/01/16 10:06
 */

class Virtual_storage_model extends Purchase_model {
    protected $table_name = 'virtual_storage';//虚拟入库主表
    protected $detail_table_name = 'virtual_storage_detail';//虚拟入库主表
    protected $warehouse_results = 'warehouse_results';//入库记录表
    protected $warehouse_results_main = 'warehouse_results_main';//入库记录表
    protected $purchase_order_pay_type = 'purchase_order_pay_type';//拍单表
    protected $purchase_order = 'purchase_order';//采购单表
    protected $demand_table = 'purchase_suggest';//备货单表
    protected $order_item = 'purchase_order_items';//备货单表
    protected $log_table  ='virtual_storage_log';
    protected $warehouse_result_method = '/provider/purPush/pushLogisticsInfo';//推送快递单号入库情况



    public function __construct(){
        parent::__construct();
        $this->load->model('product/product_model');
        $this->load->model('product/product_line_model');
        $this->load->model('purchase/purchase_order_model');
        $this->load->model('purchase/purchase_suggest_model');
        $this->load->model('purchase/purchase_order_items_model');
        $this->load->model('supplier/supplier_settlement_model');
        $this->load->model('purchase/purchase_order_determine_model');
        $this->load->helper('status_order');


    }








    //获取虚拟入库信息

    public function get_storage_info($id=0,$storage_number='',$have_item=false)
    {


        if ($id) {
            $where['id'] = $id;

        } else {
            $where['storage_number'] = $storage_number;

        }

        $info = $this->purchase_db->select('*')->from($this->table_name)->where($where)->get()->row_array();


        if (!empty($info)&&$have_item) {
            $info['storage_detail'] = $this->purchase_db->select('*')->from($this->detail_table_name)->where('storage_number',$info['storage_number'])->get()->result_array();


        }

        return $info ;



    }





    //获取审核
    public function view_storage_detail($storage_number)
    {


        $this->load->model('finance/purchase_order_pay_type_model');

        $settlement_list  = $this->supplier_settlement_model->get_settlement();
        $settlement_list  = array_column($settlement_list['list'],null,'settlement_code');
        $pay_list = getPayType();
        $purchase_order_status_list = getPurchaseStatus();
        $this->load->model('warehouse/Warehouse_model');
        $warehouse_list = $this->Warehouse_model->get_warehouse_list();
        $warehouse_list = array_column($warehouse_list,'warehouse_name','warehouse_code');
        $pay_status_list = getPayStatus();



        $storage_info = $this->get_storage_info(0,$storage_number,true);
        if (!empty($storage_info)) {

            $purchase_number_list = [$storage_info['purchase_number']];
            foreach ($purchase_number_list as $purchase_no) {
                $order_temp_info = $this->purchase_order_determine_model->get_order_info($purchase_no);
                $order_temp_info = array_column($order_temp_info,null,'demand_number');
                $purchase_demand_number_info[$purchase_no] = $order_temp_info;


            }



            $purchase_order_info = $this->purchase_order_model->get_one($storage_info['purchase_number'],false);
            $pai_number_info = $this->purchase_order_pay_type_model->get_one($storage_info['purchase_number']);
            $storage_info['purchase_order_status'] = $purchase_order_status_list[$purchase_order_info['purchase_order_status']];
            $storage_info['pay_status'] = $pay_status_list[$purchase_order_info['pay_status']]??'';
            $storage_info['purchase_name'] = !empty($purchase_order_info['purchase_name'])?get_purchase_agent($purchase_order_info['purchase_name']):'';//采购主体

            $storage_info['supplier_code'] = $purchase_order_info['supplier_code']??'';//供应商编码
            $storage_info['supplier_name'] = $purchase_order_info['supplier_name']??'';//供应商名称

            $storage_info['account_type'] = $settlement_list[$purchase_order_info['account_type']]['settlement_name']??'';//结算方式
            $storage_info['account_ratio'] = $settlement_list[$purchase_order_info['account_type']]['settlement_percent']??'';//结算比例

            $storage_info['pay_type'] = $pay_list[$purchase_order_info['pay_type']]??'';//支付方式

            $storage_info['buyer_name'] = $purchase_order_info['buyer_name']??'';//采购员
            $storage_info['warehouse_code'] = $warehouse_list[$purchase_order_info['warehouse_code']]??'';//采购仓库
            $storage_info['pai_number']     = $pai_number_info['pai_number']??'';

            //明细展示
            if (!empty($storage_info['storage_detail'])) {
                foreach ($storage_info['storage_detail'] as $key=>$detail) {


                    $order_num_info =$purchase_demand_number_info[$detail['purchase_number']][$detail['demand_number']]??'';
                    $storage_info['storage_detail'][$key]['confirm_amount'] = $order_num_info['confirm_amount'];
                    $storage_info['storage_detail'][$key]['cancel_num'] = $order_num_info['cancel_ctq'];
                    $storage_info['storage_detail'][$key]['loss_num'] = $order_num_info['loss_amount'];
                    $product_info =$this->product_model->getproduct($detail['sku']);
                    $purchase_order_item_info = $this->purchase_order_items_model->get_item($detail['purchase_number'],$detail['sku'],true);
                    $storage_info['storage_detail'][$key]['purchase_unit_price'] = $purchase_order_item_info['purchase_unit_price'];
                    $storage_info['storage_detail'][$key]['product_name'] = $product_info['product_name'];


                }

            }


        }

        return $storage_info;


    }





    public function get_storage_list($params,$offset,$limit,$page=1){

        $this->load->model('supplier/supplier_buyer_model','buyerModel');

        $data_list_temp = [];
        $audit_status_arr = [1=>'待审核',2=>'审核通过',3=>'审核驳回'];
        $field='storage.storage_number,storage.audit_status,storage.purchase_number,storage.apply_name,storage.remark,storage.create_time,storage.audit_name,storage.audit_time,type.pai_number,order.purchase_order_status,order.supplier_code,order.supplier_name';
        $this->purchase_db->select($field)
            ->from($this->table_name.' storage')
            ->join($this->purchase_order.' order','order.purchase_number=storage.purchase_number','left')
            ->join($this->purchase_order_pay_type.' type','type.purchase_number=order.purchase_number','left');


        if(isset($params['storage_number']) && !empty($params['storage_number'])){
            $search_pur=query_string_to_array($params['storage_number']);
            $this->purchase_db->where_in('storage.storage_number',$search_pur);
        }



        if(isset($params['audit_status']) && !empty($params['audit_status'])){
            $this->purchase_db->where('storage.audit_status',$params['audit_status']);
        }




        if(isset($params['purchase_number']) && !empty($params['purchase_number'])){
            $search_pur=query_string_to_array($params['purchase_number']);
            $this->purchase_db->where_in('storage.purchase_number',$search_pur);
        }




        if(isset($params['apply_id']) && !empty($params['apply_id'])){
            $this->purchase_db->where('storage.apply_id',$params['apply_id']);
        }




        if(isset($params['create_time_start']) && !empty($params['create_time_start'])){

            $this->purchase_db->where('storage.create_time>=',$params['create_time_start']);


        }

        if(isset($params['create_time_end']) && !empty($params['create_time_end'])){

            $this->purchase_db->where('storage.create_time<=',$params['create_time_end']);

        }

        if(isset($params['audit_id']) && !empty($params['audit_id'])){
            $this->purchase_db->where('storage.audit_id',$params['audit_id']);
        }

        if(isset($params['audit_time_start']) && !empty($params['audit_time_start'])){

            $this->purchase_db->where('storage.audit_time>=',$params['audit_time_start']);


        }

        if(isset($params['audit_time_end']) && !empty($params['audit_time_end'])){

            $this->purchase_db->where('storage.audit_time<=',$params['audit_time_end']);

        }

        if(isset($params['purchase_order_status']) && !empty($params['purchase_order_status'])){

            $this->purchase_db->where('order.purchase_order_status',$params['purchase_order_status']);

        }

        if(isset($params['pai_number']) && !empty($params['pai_number'])){
            $search_pur=query_string_to_array($params['pai_number']);

            $this->purchase_db->where_in('type.pai_number',$search_pur);

        }



        $clone_db = clone($this->purchase_db);
        $total=$clone_db->count_all_results();//符合当前查询条件的总记录数

        $result=$this->purchase_db->order_by('storage.create_time desc ')->limit($limit,$offset)->get()->result_array();



        if (!empty($result)) {

            foreach ($result as $value) {
                $value_temp = [];
                $value_temp['storage_number'] = $value['storage_number'];
                $value_temp['audit_status'] = $audit_status_arr[$value['audit_status']];
                $value_temp['purchase_number'] = $value['purchase_number'];
                $value_temp['purchase_order_status'] = getPurchaseStatus($value['purchase_order_status']);
                $value_temp['pai_number'] = $value['pai_number'];
                $value_temp['supplier_code'] = $value['supplier_code'];//供应商编码
                $value_temp['supplier_name'] = $value['supplier_name'];//供应商名称
                $value_temp['apply_name'] = $value['apply_name'];
                $value_temp['create_time'] = $value['create_time'];
                $value_temp['audit_name'] = $value['audit_name'];
                $value_temp['audit_time'] = $value['audit_time'];
                $value_temp['remark']     = $value['remark'];
                $data_list_temp[] = $value_temp;


            }

        }

        //下拉列表采购员
        $buyers = $this->buyerModel->get_buyers();

        $return_data = [
            'data_list'   => [
                'value' => $data_list_temp,
                'key' =>[ '序号','图片','多货编号','多货状态','退货编号','申请人','申请时间','SKU','产品线','产品名称','入库批次','入库时间','多货数量信息','退货数量'

                ],
                'drop_down_box' =>[
                    'audit_status'=>$audit_status_arr,
                    'buyers'=>$buyers['list'],
                    'purchase_order_status'=>getPurchaseStatus()

                ],
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit

            ],

        ];

        return $return_data;


    }

    public function audit_storage_order($audit_result,$storage_number,$remark)
    {

        $storage_info = $this->get_storage_info(0,$storage_number,true);

        $result = ['code'=>false,'msg'=>''];
        //审核通过

        $this->purchase_db->trans_begin();

        try{

            if ($storage_info['audit_status']!=1) {
                throw new Exception('该入库单非待审核状态!');

            }

            if ($audit_result == 1) {//审核通过
                $purchase_order_info = $this->purchase_order_model->get_one($storage_info['purchase_number'],false);

                if(!in_array($purchase_order_info['purchase_order_status'], [PURCHASE_ORDER_STATUS_WAITING_ARRIVAL,PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION,PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE])){

                    throw new Exception('状态不是等待到货、已到货待检测、部分到货等待剩余!');

                };

                $purchase_number_list = [$storage_info['purchase_number']];
                foreach ($purchase_number_list as $purchase_no) {
                    $order_temp_info = $this->purchase_order_determine_model->get_order_info($purchase_no);
                    $order_temp_info = array_column($order_temp_info,null,'demand_number');
                    $purchase_demand_number_info[$purchase_no] = $order_temp_info;

                }


                if (empty($storage_info['storage_detail'])) {
                    throw new Exception('虚拟入库明细不存在!');

                }

                foreach ($storage_info['storage_detail'] as $storage_detail) {

                    //备货单数量
                    $order_num_info =$purchase_demand_number_info[$storage_detail['purchase_number']][$storage_detail['demand_number']]??'';
                    $need_storage_num = $order_num_info['confirm_amount'] - $order_num_info['loss_amount']-$order_num_info['cancel_ctq'];




                    if ($need_storage_num!=$storage_detail['quantity']) {

                        throw new Exception($storage_detail['demand_number'].'该条数据本次入库数量超过未入数量，请核实之后再进行操作！');
                    }



                }


                $update = ['remark'=>$remark,'audit_status'=>2,'audit_time'=>date('Y-m-d H:i:s'),'audit_id'=>getActiveUserId(),'audit_name'=>getActiveUserName()];
                $flag=$this->purchase_db->where('storage_number',$storage_number)->update($this->table_name,$update);

                //
                if ($flag) {//更新成功写入库记录

                    //虚拟入库过程

                    $this->virtual_storage_process($storage_info['storage_detail']);




                } else {
                    throw new Exception('虚拟入库单审核失败');
                }


                $order_update = $this->purchase_order_model->change_status($storage_info['purchase_number']);
                if (empty($order_update)) {
                    throw new Exception($storage_info['purchase_number'].'采购单更新失败');
                }

                //将信息推送门户系统
                $access_taken = getOASystemAccessToken();//访问java token
                foreach ($storage_info['storage_detail'] as $storage_detail) {

                    $post_info = [
                        'purchaseNumber'=>$storage_detail['purchase_number'],
                        'sku'           =>$storage_detail['sku'],
                        'instockDate'   =>date('Y-m-d H:i:s'),
                        'instockQty'    =>$storage_detail['quantity'],

                    ];

                    $post_url = GET_JAVA_DATA.$this->warehouse_result_method."?access_token=".$access_taken;
                    $post_result = getCancelCurlData($post_url, json_encode($post_info), 'post', array('Content-Type: application/json'));



                }





            } elseif ($audit_result == 2) {
                $update = ['remark'=>$remark,'audit_status'=>3,'audit_time'=>date('Y-m-d H:i:s'),'audit_id'=>getActiveUserId(),'audit_name'=>getActiveUserName()];
                $flag=$this->purchase_db->where('storage_number',$storage_number)->update($this->table_name,$update);


            }

            if (!$flag) {

                throw new Exception('审核失败');

            }

            //添加日志
            $ins_log = ['opr_id'=>getActiveUserId(),'opr_user'=>getActiveUserName(),'opr_time'=>date('Y-m-d H:i:s'),'detail'=>$audit_result==1?'审核通过':'审核驳回','storage_number'=>$storage_number];
            $this->purchase_db->insert($this->log_table,$ins_log);






            if ($this->purchase_db->trans_status() === FALSE)
            {
                $this->purchase_db->trans_rollback();
                throw new Exception($storage_info['purchase_number'].'审核调拨单失败');
            }
            else
            {
                $result['code'] = true;
                $this->purchase_db->trans_commit();

            }

        } catch(Exception $e) {
            $this->purchase_db->trans_rollback();
            $result['msg'] = $e->getMessage();


        }


        return $result;




    }




    /*
     * @desc 虚拟入库过程
     * @param $purchase_number string $storage_detail array
     * @return array
     */
    public function virtual_storage_process($storage_detail)
    {
        $insert_warehouse =[];
        $insert_warehouse_main =[];
        $update_demand = [];
        $order_item_update = [];
        if ($storage_detail) {
            foreach ($storage_detail as $key=> $detail) {
                $warehouse_results = $this->purchase_db->select('*')->from($this->warehouse_results)->where(['sku'=>$detail['sku'],'purchase_number'=>$detail['purchase_number']])->get()->row_array();
                $purchase_order_item_info = $this->purchase_order_items_model->get_item($detail['purchase_number'],$detail['sku'],true);
                if (empty($purchase_order_item_info)) {
                    throw new Exception('采购单明细不存在');

                }

                $order_item_update[] = ['id'=>$purchase_order_item_info['id'],'receive_amount'=>$purchase_order_item_info['receive_amount']+$detail['quantity'],'upselft_amount'=>$purchase_order_item_info['upselft_amount']+$detail['quantity']];
                if (!empty($warehouse_results)) {
                    throw new Exception('入库记录已存在');

                }
                $insert_warehouse[] = [
                    'items_id'=>$purchase_order_item_info['id'],
                    'purchase_number'=>$detail['purchase_number'],
                    'sku'=>$detail['sku'],
                    'instock_batch'=>$detail['storage_number'].'-'.($key+1),
                    'purchase_qty'=>$purchase_order_item_info['confirm_amount'],
                    'arrival_qty'=>$detail['quantity'],
                    'qurchase_num'=>$purchase_order_item_info['confirm_amount'],
                    'quality_time'=>date('Y-m-d H:i:s'),
                    'upper_end_time'=>date('Y-m-d H:i:s'),
                    'count_time'=>date('Y-m-d H:i:s'),
                    'arrival_date'=>date('Y-m-d H:i:s'),
                    'instock_qty'=>$detail['quantity'],
                    'instock_date'=>date('Y-m-d H:i:s'),
                    'create_time'=>date('Y-m-d H:i:s'),
                    'update_time'=>date('Y-m-d H:i:s'),
                    'instock_type'=>7,
                    'instock_node'=>100,


                ];

                $warehouse_results_main = $this->purchase_db->select('*')->from($this->warehouse_results_main)->where(['sku'=>$detail['sku'],'purchase_number'=>$detail['purchase_number']])->get()->row_array();
                if (!empty($warehouse_results_main)) {
                    throw new Exception('入库记录已存在');

                }

                $insert_warehouse_main[] = [
                    'items_id'=>$purchase_order_item_info['id'],
                    'purchase_number'=>$detail['purchase_number'],
                    'sku'=>$detail['sku'],
                    'purchase_qty'=>$purchase_order_item_info['confirm_amount'],
                    'arrival_qty'=>$detail['quantity'],
                    'arrival_date'=>date('Y-m-d H:i:s'),
                    'instock_qty'=>$detail['quantity'],
                    'instock_date'=>date('Y-m-d H:i:s'),
                    'create_time'=>date('Y-m-d H:i:s'),

                ];




            }


            if (!empty($insert_warehouse)) {
                $this->purchase_db->insert_batch($this->warehouse_results,$insert_warehouse);

            }
            if (!empty($insert_warehouse_main)) {
                $this->purchase_db->insert_batch($this->warehouse_results_main,$insert_warehouse_main);

            }



            if (!empty($order_item_update)) {
                $this->purchase_db->update_batch($this->order_item,$order_item_update,'id');

            }

        }




    }


    //获取日志
    public function view_log($storage_number)
    {
        return $this->purchase_db->select('*')->from($this->log_table)->where('storage_number',$storage_number)->order_by('id','DESC')->get()->result_array();



    }




















}