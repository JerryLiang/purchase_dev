<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2020/4/14
 * Time: 16:22
 * 发运跟踪列表
 */

class Shipping_report_loss_model extends Purchase_model
{
    protected $table_name = 'purchase_shipment_reportloss';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper(['user','abnormal','status_order']);


    }

    /*
     * @string 将报损数量分摊到计划系统备货单
     * @params $sys_data  array 系统报损数据  $plan_data array 计划系统备货单
     * @return
     */

    public function apportion_amount_to_plan($sys_data,$plan_data)
    {
        if (!empty($sys_data)&&!empty($plan_data)) {
            $loss_amount = $sys_data['loss_amount'];

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
                $temp_data = $loss_amount-$plan_demand['plan_qty'];

                if ($temp_data<=0) {
                    $plan_demand_data[] = ['shipment_type'=>$plan_demand['shipment_type'],
                                            'bs_number'=>$sys_data['bs_number'],
                                            'new_demand_number'=>$plan_demand['new_demand_number'],
                                            'sku'=>$plan_demand['sku'],
                                            'purchase_number'=>$plan_demand['purchase_number'],
                                            'supplier_name'=>$plan_demand['supplier_name'],
                                            'supplier_code'=>$plan_demand['supplier_code'],
                                            'remark'       =>$sys_data['remark'],
                                            'apply_time'   =>$sys_data['apply_time'],
                                            'apply_person'=>$sys_data['apply_person'],
                                            'loss_amount'=>$loss_amount,
                    ];
                    break;
                }  else {
                    $plan_demand_data[] = [
                        'shipment_type'=>$plan_demand['shipment_type'],
                        'bs_number'=>$sys_data['bs_number'],
                        'new_demand_number'=>$plan_demand['new_demand_number'],
                        'sku'=>$plan_demand['sku'],
                        'purchase_number'=>$plan_demand['purchase_number'],
                        'supplier_name'=>$plan_demand['supplier_name'],
                        'supplier_code'=>$plan_demand['supplier_code'],
                        'remark'       =>$sys_data['remark'],
                        'apply_time'   =>$sys_data['apply_time'],
                        'apply_person'=>$sys_data['apply_person'],
                        'loss_amount'=>$plan_demand['plan_qty']];

                }

                $loss_amount -= $plan_demand['plan_qty'];

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
    public function get_report_loss_list($params,$offset=null,$limit=null,$page=1){
        //发运类型
        $shipment_arr = [1=>'工厂发运',2=>'中转仓发运'];
        $is_drawback_list = [0=>'不退税',1=>'退税'];
        $getReportlossApprovalStatus =  getReportlossApprovalStatus();
        $order_status_list = getPurchaseStatus();


        $data_list_temp = [];
        $field='l.*,pre.status as audit_status';
        $this->purchase_db->select($field)
            ->from($this->table_name.' l')
            ->join('purchase_order_reportloss pre','pre.demand_number=l.demand_number','left');

        if(isset($params['sku']) && !empty($params['sku'])){//批量，单个
            $search_sku=query_string_to_array($params['sku']);
            $this->purchase_db->where_in('l.sku',$search_sku);
        }

        if(isset($params['pur_number']) && !empty($params['pur_number'])){
            $search_pur=query_string_to_array($params['pur_number']);
            $this->purchase_db->where_in('l.purchase_number',$search_pur);
        }

        if(isset($params['bs_number']) && !empty($params['bs_number'])){
            $this->purchase_db->where('l.bs_number',$params['bs_number']);
        }

        if(isset($params['apply_person']) && !empty($params['apply_person'])){
            $this->purchase_db->where('l.apply_person',$params['apply_person']);
        }


        if(isset($params['status']) && is_numeric($params['status'])){
            $this->purchase_db->where('l.status',$params['status']);
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

     /*   echo $this->purchase_db->last_query();
        exit;*/

        $data_list_temp =[];

       if (!empty($result)) {
           foreach ($result as $key => $value) {
               $value_temp = [];
               //查询备货单信息

               $demand_info = $this->purchase_db->select('*')->from('pur_purchase_suggest')->where('demand_number',$value['demand_number'])->get()->row_array();

               $value_temp['bs_number'] = $value['bs_number'];
               $value_temp['shipment_type'] = $shipment_arr[$value['shipment_type']];
               $value_temp['new_demand_number'] = $value['new_demand_number'];
               $value_temp['sku'] = $value['sku'];
               $value_temp['purchase_number'] = $value['purchase_number'];
               $value_temp['supplier_name'] = $value['supplier_name'];
               $value_temp['is_drawback'] = $is_drawback_list[$demand_info['is_drawback']];
               $value_temp['loss_amount'] = $value['loss_amount'];
               $value_temp['remark'] = $value['remark'];
               $value_temp['apply_person'] = $value['apply_person'];
               $value_temp['apply_time'] = $value['apply_time'];
               $value_temp['demand_status'] = $order_status_list[$demand_info['suggest_order_status']];
               $value_temp['audit_status'] = $getReportlossApprovalStatus[$value['audit_status']];
               $data_list_temp[] = $value_temp;


           }
       }


        $return_data = [
            'data_list'   => [
                'value' => $data_list_temp,
                'key' =>[
                    '申请编码','发运类型','新备货单号','sku','采购单号','供应商名称','是否退税','报损数量','申请备注','申请人','申请时间','备货单状态','报损审核状态'
                ],
                'drop_down_box' => [
                    'status_list' => $getReportlossApprovalStatus,
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