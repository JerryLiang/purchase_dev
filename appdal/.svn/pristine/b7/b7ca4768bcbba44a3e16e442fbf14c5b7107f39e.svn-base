<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2020/4/14
 * Time: 16:22
 * 发运跟踪列表
 */

class Purchase_shipping_cancel_list_model extends Purchase_model
{
    protected $table_name = 'purchase_shipment_cancel';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['user','abnormal','status_order']);





    }

    /*
     * @string 将取消数量分摊到计划系统备货单
     * @params $sys_data  array 取消数据  $plan_data array 计划系统备货单 $cancel_ctq int 取消数量
     * @return
     */

    public function apportion_amount_to_plan($sys_data,$plan_data,$cancel_ctq)
    {
        if (!empty($sys_data)&&!empty($plan_data)&&$cancel_ctq>0) {

            $shipment_arr = [];
            $plan_qty_arr = [];

            $plan_demand_data = [];//计划系统即将生成的报损单

            foreach ($plan_data as $plan) {
                $shipment_arr[] = $plan['shipment_type'];
                $plan_qty_arr[] = $plan['plan_qty'];

            }
            //将数组排序
            array_multisort($shipment_arr, SORT_DESC, $plan_qty_arr, SORT_DESC, $plan_data);

            foreach ($plan_data as $plan_demand) {
                $temp_data = $cancel_ctq-$plan_demand['plan_qty'];

                if ($temp_data<=0) {
                    $plan_demand_data[] = ['shipment_type'=>$plan_demand['shipment_type'],
                        'cancel_number'=>$sys_data['cancel_number'],
                        'new_demand_number'=>$plan_demand['new_demand_number'],
                        'sku'=>$plan_demand['sku'],
                        'purchase_number'=>$plan_demand['purchase_number'],
                        'supplier_name'=>$plan_demand['supplier_name'],
                        'supplier_code'=>$plan_demand['supplier_code'],
                        'remark'       =>$sys_data['create_note'],
                        'apply_time'   =>$sys_data['create_time'],
                        'apply_person'=>$sys_data['create_user_name'],
                        'cancel_qty'=>$cancel_ctq,
                    ];

                    //更新跟踪表取消数量,并待推送wms
                    $this->purchase_db->where('id', $plan_demand['id'])->update('shipment_track_list', ['plan_qty'=>$plan_demand['plan_qty']-$cancel_ctq,'push_to_wms'=>2]);


                    break;
                }  else {
                    $plan_demand_data[] = [
                        'shipment_type'=>$plan_demand['shipment_type'],
                        'cancel_number'=>$sys_data['cancel_number'],
                        'new_demand_number'=>$plan_demand['new_demand_number'],
                        'sku'=>$plan_demand['sku'],
                        'purchase_number'=>$plan_demand['purchase_number'],
                        'supplier_name'=>$plan_demand['supplier_name'],
                        'supplier_code'=>$plan_demand['supplier_code'],
                        'remark'       =>$sys_data['create_note'],
                        'apply_time'   =>$sys_data['create_time'],
                        'apply_person'=>$sys_data['create_user_name'],
                        'cancel_qty'=>$plan_demand['plan_qty']];
                }

                $this->purchase_db->where('id', $plan_demand['id'])->update('shipment_track_list', ['plan_qty'=>0,'push_to_wms'=>2]);

                $cancel_ctq -= $plan_demand['plan_qty'];


            }
            if (!empty($plan_demand_data)) {
                $this->purchase_db->insert_batch($this->table_name,$plan_demand_data);

            }

        }

    }


    /**
     * 获取报损数据列表
     * @param $params
     * @param $offset
     * @param $limit
     * @return array
     */
    public function get_cancel_list($params,$offset=null,$limit=null,$page=1){

        //发运类型
        $shipment_arr = [1=>'工厂发运',2=>'中转仓发运'];
        $is_drawback_list = [0=>'不退税',1=>'退税'];
        $cancel_status =   get_cancel_status(); //取消未到货状态

        $data_list_temp = [];
        $field='l.*,pre.audit_status';
        $this->purchase_db->select($field)
            ->from($this->table_name.' l')
            ->join('purchase_order_cancel pre','pre.cancel_number=l.cancel_number','left');

        if(isset($params['sku']) && !empty($params['sku'])){//批量，单个
            $search_sku=query_string_to_array($params['sku']);
            $this->purchase_db->where_in('l.sku',$search_sku);
        }

        if(isset($params['pur_number']) && !empty($params['pur_number'])){
            $search_pur=query_string_to_array($params['pur_number']);
            $this->purchase_db->where_in('l.purchase_number',$search_pur);
        }

        if(isset($params['cancel_number']) && !empty($params['cancel_number'])){
            $this->purchase_db->where('l.cancel_number',$params['cancel_number']);
        }

        if(isset($params['apply_person']) && !empty($params['apply_person'])){
            $this->purchase_db->where('l.apply_person',$params['apply_person']);
        }


        if(isset($params['audit_status']) && is_numeric($params['audit_status'])){
            $this->purchase_db->where('pre.audit_status',$params['status']);
        }

        if(isset($params['apply_time_start']) && !empty($params['apply_time_start'])){
            $this->purchase_db->where('l.apply_time>=',$params['apply_time_start']);
        }

        if(isset($params['apply_time_end']) && !empty($params['apply_time_end'])){
            $this->purchase_db->where('l.apply_time<=',$params['apply_time_end']);
        }

        if(isset($params['id']) && !empty($params['id'])){
            $this->purchase_db->where_in('l.id',$params['id']);
        }

        if(isset($params['demand_number']) && !empty($params['demand_number'])){
            $this->purchase_db->where('l.demand_number',$params['demand_number']);
        }


        if(isset($params['supplier_code']) && !empty($params['supplier_code'])){
            $this->purchase_db->where('l.supplier_code',$params['supplier_code']);
        }

        if(isset($params['shipment_type']) && !empty($params['shipment_type'])){
            $this->purchase_db->where('l.shipment_type',$params['shipment_type']);
        }


        $clone_db = clone($this->purchase_db);
        $total=$this->purchase_db->count_all_results();//符合当前查询条件的总记录数
        $this->purchase_db=$clone_db;
        $result=$this->purchase_db->order_by('apply_time','DESC')->limit($limit,$offset)->get()->result_array();


        foreach ($result as $key=>$value){
            $value_temp = [];
            //查询备货单信息
            $demand_info = $this->purchase_db->select('*')->from('pur_purchase_suggest')->where('demand_number',$value['demand_number'])->get()->row_array();
            $value_temp['cancel_number'] = $value['cancel_number'];
            $value_temp['shipment_type'] = $shipment_arr[$value['shipment_type']];
            $value_temp['new_demand_number'] =$value['new_demand_number'] ;
            $value_temp['sku'] =$value['sku'] ;
            $value_temp['purchase_number'] =$value['purchase_number'] ;
            $value_temp['supplier_name'] =$value['supplier_name'] ;
            $value_temp['is_drawback'] =!empty($demand_info)?$is_drawback_list[$demand_info['is_drawback']]:'无' ;
            $value_temp['cancel_qty'] =$value['cancel_qty'] ;
            $value_temp['remark'] =$value['remark'] ;
            $value_temp['apply_person'] =$value['apply_person'] ;
            $value_temp['apply_time'] =$value['apply_time'] ;
            $value_temp['audit_status'] = $cancel_status[$value['audit_status']]??'';
            $data_list_temp[]  =$value_temp;


        }

        $return_data = [
            'data_list'   => [
                'value' => $data_list_temp,
                'key' =>[
                    '申请编码','发运类型','新备货单号','sku','采购单号','供应商名称','是否退税','取消数量','申请备注','申请人','申请时间','取消审核状态'
                ],
                'drop_down_box' => [
                    'status_list' => $cancel_status,
                    'user_list' => getBuyerDropdown(),
                    'shipment'    =>$shipment_arr
                ]
            ],
            'paging_data' => [
                'total'     => $total,
                'offset'    => $page,
                'limit'     => $limit,
            ]
        ];
        return $return_data;
    }









}